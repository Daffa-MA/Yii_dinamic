<?php

namespace app\components;

use Yii;
use yii\db\Command;
use yii\db\Connection;

class BackupSyncCommand extends Command
{
    /** @var array<string, Connection> */
    private static $backupConnectionCache = [];

    public function execute()
    {
        $affectedRows = parent::execute();
        $this->syncToBackupIfNeeded();

        return $affectedRows;
    }

    private function syncToBackupIfNeeded(): void
    {
        if (getenv('YII_DB_BACKUP_SYNC') === '0') {
            return;
        }

        $sql = (string)$this->getSql();
        if (!$this->isSyncCandidateSql($sql)) {
            return;
        }

        if (!Yii::$app->has('dbBackup')) {
            return;
        }

        $backup = Yii::$app->get('dbBackup', false);
        if (!$backup instanceof Connection) {
            return;
        }

        if ($this->db === $backup) {
            return;
        }

        try {
            $targetBackup = $this->resolveBackupConnectionForCurrentDatabase($backup);
            if (!$targetBackup instanceof Connection) {
                return;
            }
            $targetBackup->createCommand($sql, $this->params)->execute();
        } catch (\Throwable $e) {
            $message = 'Sinkronisasi ke database backup gagal: ' . $e->getMessage();
            if (getenv('YII_DB_BACKUP_SYNC_STRICT') === '1') {
                throw new \RuntimeException($message, 0, $e);
            }

            Yii::warning($message, __METHOD__);
        }
    }

    private function isSyncCandidateSql(string $sql): bool
    {
        $trimmedSql = ltrim($sql);
        if ($trimmedSql === '') {
            return false;
        }

        $trimmedSql = (string)(preg_replace('/^\(+/', '', $trimmedSql) ?? $trimmedSql);
        if (preg_match('/^([a-zA-Z]+)/', $trimmedSql, $matches) !== 1) {
            return false;
        }

        $keyword = strtoupper((string)$matches[1]);
        return in_array($keyword, [
            'INSERT',
            'UPDATE',
            'DELETE',
            'REPLACE',
            'CREATE',
            'ALTER',
            'DROP',
            'TRUNCATE',
            'RENAME',
        ], true);
    }

    private function resolveBackupConnectionForCurrentDatabase(Connection $backup): Connection
    {
        if (!$this->isMysqlDsn((string)$this->db->dsn) || !$this->isMysqlDsn((string)$backup->dsn)) {
            return $backup;
        }

        $activeDatabase = $this->resolveCurrentDatabaseName($this->db);
        if ($activeDatabase === '') {
            return $backup;
        }

        $backupDatabase = $this->resolveCurrentDatabaseName($backup);
        if ($backupDatabase === $activeDatabase) {
            return $backup;
        }

        $targetDsn = $this->replaceDatabaseInDsn((string)$backup->dsn, $activeDatabase);
        if ($targetDsn === (string)$backup->dsn) {
            return $backup;
        }

        if (isset(self::$backupConnectionCache[$targetDsn])) {
            $cachedConnection = self::$backupConnectionCache[$targetDsn];
            if ($cachedConnection instanceof Connection && $cachedConnection->isActive) {
                return $cachedConnection;
            }
            unset(self::$backupConnectionCache[$targetDsn]);
        }

        $newConnection = Yii::createObject([
            'class' => Connection::class,
            'dsn' => $targetDsn,
            'username' => $backup->username,
            'password' => $backup->password,
            'charset' => $backup->charset,
            'tablePrefix' => $backup->tablePrefix,
            'attributes' => $backup->attributes,
            'enableSchemaCache' => $backup->enableSchemaCache,
            'schemaCacheDuration' => $backup->schemaCacheDuration,
            'schemaCacheExclude' => $backup->schemaCacheExclude,
            'schemaCache' => $backup->schemaCache,
            'enableQueryCache' => $backup->enableQueryCache,
            'queryCacheDuration' => $backup->queryCacheDuration,
            'queryCache' => $backup->queryCache,
        ]);
        $newConnection->open();

        self::$backupConnectionCache[$targetDsn] = $newConnection;
        return $newConnection;
    }

    private function resolveCurrentDatabaseName(Connection $connection): string
    {
        if (preg_match('/dbname=([^;]+)/i', (string)$connection->dsn, $matches) === 1) {
            return trim((string)$matches[1]);
        }

        try {
            $dbName = (string)$connection->createCommand('SELECT DATABASE()')->queryScalar();
            return trim($dbName);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function replaceDatabaseInDsn(string $dsn, string $databaseName): string
    {
        if (preg_match('/dbname=([^;]+)/i', $dsn)) {
            return (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $databaseName, $dsn, 1);
        }

        return rtrim($dsn, ';') . ';dbname=' . $databaseName;
    }

    private function isMysqlDsn(string $dsn): bool
    {
        return stripos($dsn, 'mysql:') === 0;
    }
}
