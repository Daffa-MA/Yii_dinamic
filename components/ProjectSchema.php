<?php

namespace app\components;

use Yii;

class ProjectSchema
{
    private static ?bool $projectContextSupported = null;
    private static array $tableExistsCache = [];
    private static array $columnExistsCache = [];

    public static function supportsProjectContext(): bool
    {
        if (self::$projectContextSupported !== null) {
            return self::$projectContextSupported;
        }

        self::$projectContextSupported = self::hasTable('projects')
            && self::hasColumn('forms', 'project_id')
            && self::hasColumn('db_tables', 'project_id');

        return self::$projectContextSupported;
    }

    public static function hasTable(string $tableName): bool
    {
        if (array_key_exists($tableName, self::$tableExistsCache)) {
            return self::$tableExistsCache[$tableName];
        }

        try {
            $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
            self::$tableExistsCache[$tableName] = $schema !== null;
        } catch (\Throwable $e) {
            Yii::warning("Failed checking table '{$tableName}': " . $e->getMessage(), 'app');
            self::$tableExistsCache[$tableName] = false;
        }

        return self::$tableExistsCache[$tableName];
    }

    public static function hasColumn(string $tableName, string $columnName): bool
    {
        $cacheKey = $tableName . '.' . $columnName;
        if (array_key_exists($cacheKey, self::$columnExistsCache)) {
            return self::$columnExistsCache[$cacheKey];
        }

        try {
            $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
            self::$columnExistsCache[$cacheKey] = $schema !== null && isset($schema->columns[$columnName]);
        } catch (\Throwable $e) {
            Yii::warning("Failed checking column '{$cacheKey}': " . $e->getMessage(), 'app');
            self::$columnExistsCache[$cacheKey] = false;
        }

        return self::$columnExistsCache[$cacheKey];
    }
}

