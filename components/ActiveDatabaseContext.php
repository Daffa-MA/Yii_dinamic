<?php

namespace app\components;

use app\models\Project;
use Yii;
use yii\db\Connection;
use yii\db\Query;

class ActiveDatabaseContext
{
    public const SESSION_KEY = 'active_dashboard_database';

    /**
     * Host portion of a mysql: DSN (for user-facing hints about which server holds a database).
     */
    public function mysqlHostFromConnection(?Connection $connection = null): string
    {
        $connection = $connection ?? Yii::$app->db;
        $dsn = (string)$connection->dsn;
        if (preg_match('/host=([^;]+)/i', $dsn, $matches) === 1) {
            return trim($matches[1], "[]");
        }

        return '';
    }

    /**
     * Resolve the active database from request/session and switch db connection when needed.
     */
    public function resolveAndApply(): array
    {
        $request = Yii::$app->request;
        $session = Yii::$app->session;
        $currentConnection = Yii::$app->db;

        $defaultDatabase = $this->resolveCurrentDatabaseName($currentConnection);
        $requestedDatabase = trim((string)($request->get('database', $request->get('db', ''))));
        $sessionDatabase = trim((string)$session->get(self::SESSION_KEY, ''));
        $projectDatabase = $this->resolveActiveProjectDatabaseName();

        $targetDatabase = $requestedDatabase !== '' ? $requestedDatabase : $sessionDatabase;
        if ($targetDatabase === '') {
            $targetDatabase = $projectDatabase !== '' ? $projectDatabase : $defaultDatabase;
        }

        if (!$this->isValidDatabaseName($targetDatabase)) {
            $targetDatabase = $defaultDatabase;
        }

        $isSwitched = false;
        $switchError = null;
        $isMysql = $this->isMysqlDsn($currentConnection->dsn);

        if (
            $targetDatabase !== ''
            && $defaultDatabase !== ''
            && $targetDatabase !== $defaultDatabase
            && $isMysql
        ) {
            try {
                $this->switchConnectionDatabase($currentConnection, $targetDatabase);
                $isSwitched = true;
            } catch (\Throwable $e) {
                $switchError = $e->getMessage();
                $targetDatabase = $defaultDatabase;
            }
        } elseif (
            $targetDatabase !== ''
            && $defaultDatabase !== ''
            && $targetDatabase !== $defaultDatabase
            && !$isMysql
        ) {
            $switchError = 'Dynamic database switching saat ini hanya tersedia untuk MySQL.';
            $targetDatabase = $defaultDatabase;
        }

        if ($requestedDatabase !== '' && $targetDatabase !== '' && $targetDatabase !== $defaultDatabase) {
            $session->set(self::SESSION_KEY, $targetDatabase);
        } elseif ($targetDatabase === '' || $targetDatabase === $defaultDatabase) {
            $session->remove(self::SESSION_KEY);
        }

        return [
            'activeDatabase' => $targetDatabase ?: $defaultDatabase,
            'defaultDatabase' => $defaultDatabase,
            'requestedDatabase' => $requestedDatabase,
            'isSwitched' => $isSwitched,
            'switchError' => $switchError,
        ];
    }

    /**
     * Create a new MySQL database on the current server.
     */
    public function createDatabase(string $databaseName): void
    {
        $databaseName = trim($databaseName);
        if (!$this->isValidDatabaseName($databaseName)) {
            throw new \RuntimeException("Nama database '{$databaseName}' tidak valid.");
        }

        $connection = Yii::$app->db;
        if (!$this->isMysqlDsn($connection->dsn)) {
            throw new \RuntimeException('Pembuatan database baru hanya didukung untuk MySQL.');
        }

        $quotedDatabaseName = '`' . str_replace('`', '``', $databaseName) . '`';
        $sql = "CREATE DATABASE {$quotedDatabaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        if (!$this->databaseExists($connection, $databaseName)) {
            $connection->createCommand($sql)->execute();
        }

        $backup = Yii::$app->get('dbBackup', false);
        if ($backup instanceof Connection && getenv('YII_DB_BACKUP_SYNC') !== '0') {
            try {
                if (!$this->databaseExists($backup, $databaseName)) {
                    $backup->createCommand($sql)->execute();
                }
            } catch (\Throwable $e) {
                Yii::warning(
                    "Gagal membuat database backup '{$databaseName}': {$e->getMessage()}",
                    __METHOD__
                );
            }
        }
    }

    /**
     * Check if a database exists on the current server.
     */
    public function databaseExistsOnCurrentServer(string $databaseName): bool
    {
        $databaseName = trim($databaseName);
        if (!$this->isValidDatabaseName($databaseName)) {
            return false;
        }

        return $this->databaseExists(Yii::$app->db, $databaseName);
    }

    private function switchConnectionDatabase(Connection $connection, string $databaseName): void
    {
        if (!$this->databaseExists($connection, $databaseName)) {
            throw new \RuntimeException("Database '{$databaseName}' tidak ditemukan pada server aktif.");
        }

        $dsn = $this->replaceDatabaseInDsn($connection->dsn, $databaseName);

        $newConfig = [
            'class' => Connection::class,
            'dsn' => $dsn,
            'username' => $connection->username,
            'password' => $connection->password,
            'charset' => $connection->charset,
            'tablePrefix' => $connection->tablePrefix,
            'attributes' => $connection->attributes,
            'enableSchemaCache' => $connection->enableSchemaCache,
            'schemaCacheDuration' => $connection->schemaCacheDuration,
            'schemaCacheExclude' => $connection->schemaCacheExclude,
            'schemaCache' => $connection->schemaCache,
            'enableQueryCache' => $connection->enableQueryCache,
            'queryCacheDuration' => $connection->queryCacheDuration,
            'queryCache' => $connection->queryCache,
        ];

        $newConnection = Yii::createObject($newConfig);
        $newConnection->open();

        $connection->close();
        Yii::$app->set('db', $newConnection);
    }

    private function databaseExists(Connection $connection, string $databaseName): bool
    {
        if (!$this->isMysqlDsn($connection->dsn)) {
            return false;
        }

        return (new Query())
            ->from('INFORMATION_SCHEMA.SCHEMATA')
            ->where(['SCHEMA_NAME' => $databaseName])
            ->exists($connection);
    }

    private function isMysqlDsn(string $dsn): bool
    {
        return stripos($dsn, 'mysql:') === 0;
    }

    private function resolveCurrentDatabaseName(Connection $connection): string
    {
        if (preg_match('/dbname=([^;]+)/i', $connection->dsn, $matches)) {
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

    private function isValidDatabaseName(string $databaseName): bool
    {
        return $databaseName !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $databaseName) === 1;
    }

    private function resolveActiveProjectDatabaseName(): string
    {
        $project = (new ActiveProjectContext())->getActiveProject();
        if (!$project instanceof Project) {
            return '';
        }

        $legacyDatabaseName = sprintf('proj_u%d_p%d', (int)$project->user_id, (int)$project->id);
        $customDatabaseName = $this->buildCustomProjectDatabaseName((string)$project->name);

        if (
            $this->databaseExistsOnCurrentServer($legacyDatabaseName)
            && !$this->databaseExistsOnCurrentServer($customDatabaseName)
        ) {
            return $legacyDatabaseName;
        }

        return $customDatabaseName;
    }

    private function buildCustomProjectDatabaseName(string $projectName): string
    {
        $normalized = strtolower(trim($projectName));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            $normalized = 'project';
        }

        if (preg_match('/^[0-9]/', $normalized) === 1) {
            $normalized = 'project_' . $normalized;
        }

        if (strlen($normalized) > 64) {
            $normalized = rtrim(substr($normalized, 0, 64), '_');
        }

        return $normalized !== '' ? $normalized : 'project';
    }
}
