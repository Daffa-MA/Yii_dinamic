<?php

namespace app\components;

use Yii;
use yii\db\Connection;
use yii\db\Query;

class ActiveDatabaseContext
{
    public const SESSION_KEY = 'active_dashboard_database';

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

        $targetDatabase = $requestedDatabase !== '' ? $requestedDatabase : $sessionDatabase;
        if ($targetDatabase === '') {
            $targetDatabase = $defaultDatabase;
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
}
