<?php

namespace app\services;

use Yii;
use yii\db\Connection;

class TableExistenceService
{
    private Connection $physicalDb;

    public function __construct(Connection $physicalDb)
    {
        $this->physicalDb = $physicalDb;
    }

    public function physicalExists(string $tableName): bool
    {
        $tableName = strtolower(trim($tableName));
        $databaseName = $this->getCurrentDatabaseName();
        if ($databaseName === null || $tableName === '') {
            return false;
        }

        try {
            $count = $this->physicalDb->createCommand(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tableName',
                [':tableName' => $tableName]
            )->queryScalar();

            return (int)$count > 0;
        } catch (\Throwable $e) {
            Yii::warning('Physical table existence check failed: ' . $e->getMessage(), 'table-builder-sql');
            return $this->physicalDb->schema->getTableSchema($tableName, true) !== null;
        }
    }

    public function metadataExists(string $tableName, array $scope = []): bool
    {
        $criteria = array_filter(array_merge([
            'name' => strtolower(trim($tableName)),
        ], $scope), static function ($value): bool {
            return $value !== null && $value !== '';
        });

        return \app\models\DbTable::find()->where($criteria)->exists();
    }

    public function getCurrentDatabaseName(): ?string
    {
        try {
            $name = $this->physicalDb->createCommand('SELECT DATABASE()')->queryScalar();
            $name = $name !== false ? trim((string)$name) : '';
            return $name !== '' ? $name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
