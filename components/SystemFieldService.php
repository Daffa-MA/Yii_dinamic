<?php

namespace app\components;

use Yii;

class SystemFieldService
{
    private const AUDIT_FIELDS = [
        'created_by',
        'creator_id',
        'created_user_id',
        'created_by_id',
        'updated_by',
        'updater_id',
        'updated_user_id',
        'updated_by_id',
        'deleted_by',
        'deleter_id',
        'deleted_user_id',
        'deleted_by_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_ip',
        'updated_ip',
    ];

    public static function auditFields(): array
    {
        return self::AUDIT_FIELDS;
    }

    public static function isPrimaryKey($column, $schemaColumn = null): bool
    {
        if ($schemaColumn !== null && self::isPrimaryKey($schemaColumn)) {
            return true;
        }

        if (is_array($column)) {
            return !empty($column['is_primary']) || !empty($column['isPrimary']) || !empty($column['isPrimaryKey']) || !empty($column['primaryKey']);
        }

        if (is_object($column)) {
            if (isset($column->is_primary) && (bool)$column->is_primary) {
                return true;
            }
            if (isset($column->isPrimaryKey) && (bool)$column->isPrimaryKey) {
                return true;
            }
        }

        return false;
    }

    public static function isAutoIncrement($column, $schemaColumn = null): bool
    {
        if ($schemaColumn !== null && self::isAutoIncrement($schemaColumn)) {
            return true;
        }

        if (is_array($column)) {
            return !empty($column['is_auto_increment']) || !empty($column['isAutoIncrement']) || !empty($column['autoIncrement']);
        }

        if (is_object($column)) {
            if (method_exists($column, 'hasAttribute') && $column->hasAttribute('is_auto_increment')) {
                return (bool)$column->getAttribute('is_auto_increment');
            }

            if (isset($column->is_auto_increment) && (bool)$column->is_auto_increment) {
                return true;
            }

            if (isset($column->autoIncrement) && (bool)$column->autoIncrement) {
                return true;
            }
        }

        return false;
    }

    public static function isAuditField(string $columnName): bool
    {
        $name = strtolower(trim($columnName));
        if ($name === '') {
            return false;
        }

        if (in_array($name, self::AUDIT_FIELDS, true)) {
            return true;
        }

        return preg_match('/^(created|updated|deleted)_(by|by_id|user_id|ip|at)$/', $name) === 1
            || preg_match('/^(creator|updater|deleter)(_id)?$/', $name) === 1;
    }

    public static function isForeignKey($column, $schemaColumn = null): bool
    {
        if (is_array($column)) {
            return !empty($column['is_foreign_key']) || !empty($column['isForeignKey']);
        }

        if (is_object($column)) {
            if (method_exists($column, 'hasAttribute') && $column->hasAttribute('is_foreign_key')) {
                return (bool)$column->getAttribute('is_foreign_key');
            }

            if (isset($column->is_foreign_key) && (bool)$column->is_foreign_key) {
                return true;
            }
        }

        return false;
    }

    public static function hasCurrentTimestampDefault($column): bool
    {
        $default = strtolower(self::stringValue(self::columnValue($column, ['default_value', 'defaultValue', 'default'])));
        $dbType = strtolower(self::stringValue(self::columnValue($column, ['dbType', 'db_type', 'type'])));
        return strpos($default, 'current_timestamp') !== false || strpos($dbType, 'default current_timestamp') !== false;
    }

    public static function hasCurrentTimestampDefaultFrom($column, $schemaColumn = null): bool
    {
        return self::hasCurrentTimestampDefault($column)
            || ($schemaColumn !== null && self::hasCurrentTimestampDefault($schemaColumn));
    }

    public static function hasOnUpdateCurrentTimestamp($column): bool
    {
        $onUpdate = strtolower(self::stringValue(self::columnValue($column, ['on_update', 'onUpdate', 'on_update_value'])));
        $dbType = strtolower(self::stringValue(self::columnValue($column, ['dbType', 'db_type'])));
        return strpos($onUpdate, 'current_timestamp') !== false || strpos($dbType, 'on update current_timestamp') !== false;
    }

    public static function hasOnUpdateCurrentTimestampFrom($column, $schemaColumn = null): bool
    {
        return self::hasOnUpdateCurrentTimestamp($column)
            || ($schemaColumn !== null && self::hasOnUpdateCurrentTimestamp($schemaColumn));
    }

    public static function isSystemManagedField($column, $schemaColumn = null): bool
    {
        $columnName = self::columnName($column);
        if ($columnName === '' && $schemaColumn !== null) {
            $columnName = self::columnName($schemaColumn);
        }

        return self::isPrimaryKey($column, $schemaColumn)
            || self::isAutoIncrement($column, $schemaColumn)
            || self::isAuditField($columnName)
            || (self::hasOnUpdateCurrentTimestampFrom($column, $schemaColumn) && self::isAuditField($columnName));
    }

    public static function shouldHideFromForm($column, $schemaColumn = null): bool
    {
        return self::isSystemManagedField($column, $schemaColumn);
    }

    public static function shouldBeReadonlyInGrid($column, $schemaColumn = null): bool
    {
        return self::isSystemManagedField($column, $schemaColumn);
    }

    public static function isSystemFieldData(array $field): bool
    {
        $fieldName = (string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['sourceColumn'] ?? $field['source_column'] ?? '');
        $normalizedName = strtolower(trim($fieldName));
        if (self::isAuditField($normalizedName)) {
            return true;
        }

        return !empty($field['is_primary'])
            || !empty($field['isPrimary'])
            || !empty($field['primaryKey'])
            || !empty($field['isPrimaryKey'])
            || !empty($field['is_auto_increment'])
            || !empty($field['isAutoIncrement'])
            || !empty($field['autoIncrement'])
            || !empty($field['is_system_field'])
            || !empty($field['isSystem']);
    }

    public static function debugDecision($column, string $source = '', $schemaColumn = null): void
    {
        $payload = self::decisionPayload($column, $source, $schemaColumn);
        $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($line === false) {
            return;
        }

        @file_put_contents(Yii::getAlias('@runtime/logs/system-field-debug.log'), $line . PHP_EOL, FILE_APPEND);
    }

    public static function decisionPayload($column, string $source = '', $schemaColumn = null): array
    {
        $field = self::columnName($column);
        if ($field === '' && $schemaColumn !== null) {
            $field = self::columnName($schemaColumn);
        }
        return [
            'time' => date('Y-m-d H:i:s'),
            'source' => $source,
            'field' => $field,
            'type' => self::columnValue($schemaColumn ?? $column, ['type', 'dbType', 'db_type']),
            'metadata_type' => self::columnValue($column, ['type', 'dbType', 'db_type']),
            'schema_type' => $schemaColumn !== null ? self::columnValue($schemaColumn, ['type', 'dbType', 'db_type']) : null,
            'is_primary' => self::isPrimaryKey($column, $schemaColumn),
            'is_auto_increment' => self::isAutoIncrement($column, $schemaColumn),
            'is_foreign_key' => self::isForeignKey($column, $schemaColumn),
            'default_current_timestamp' => self::hasCurrentTimestampDefaultFrom($column, $schemaColumn),
            'on_update_current_timestamp' => self::hasOnUpdateCurrentTimestampFrom($column, $schemaColumn),
            'is_audit' => self::isAuditField($field),
            'is_system' => self::isSystemManagedField($column, $schemaColumn),
            'hide_from_form' => self::shouldHideFromForm($column, $schemaColumn),
        ];
    }

    public static function filterFields(array $fields): array
    {
        $filtered = [];
        foreach ($fields as $field) {
            if (!is_array($field) || self::isSystemFieldData($field)) {
                continue;
            }

            $filtered[] = $field;
        }

        return $filtered;
    }

    public static function filterBuilderData(array $builderData): array
    {
        if (isset($builderData['fields']) && is_array($builderData['fields'])) {
            $builderData['fields'] = self::filterFields($builderData['fields']);
            return $builderData;
        }

        if (array_keys($builderData) === range(0, count($builderData) - 1)) {
            return self::filterFields($builderData);
        }

        return $builderData;
    }

    public static function applyCreateValues(array $data, array $columns): array
    {
        foreach ($columns as $name => $column) {
            $columnName = is_string($name) ? $name : self::columnName($column);
            if ($columnName === '') {
                continue;
            }

            if (self::isPrimaryKey($column) || self::isAutoIncrement($column)) {
                unset($data[$columnName]);
                continue;
            }

            if (!self::isAuditField($columnName)) {
                continue;
            }

            $value = self::systemValueFor($columnName, $column, true);
            if ($value !== null || !self::allowsNull($column)) {
                $data[$columnName] = $value ?? 0;
            }
        }

        return $data;
    }

    public static function applyUpdateValues(array $data, array $columns): array
    {
        foreach ($columns as $name => $column) {
            $columnName = is_string($name) ? $name : self::columnName($column);
            if ($columnName === '') {
                continue;
            }

            if (self::isPrimaryKey($column) || self::isAutoIncrement($column) || in_array(strtolower($columnName), ['created_by', 'created_at', 'created_ip'], true)) {
                unset($data[$columnName]);
                continue;
            }

            if (!self::isAuditField($columnName)) {
                continue;
            }

            $value = self::systemValueFor($columnName, $column, false);
            if ($value !== null || !self::allowsNull($column)) {
                $data[$columnName] = $value ?? 0;
            }
        }

        return $data;
    }

    public static function systemValueFor(string $columnName, $column = null, bool $isCreate = true)
    {
        $name = strtolower(trim($columnName));
        if (in_array($name, ['created_at', 'updated_at', 'deleted_at'], true)) {
            if ($name === 'deleted_at') {
                return null;
            }

            return self::dateValueForColumn($column);
        }

        if (in_array($name, ['created_by', 'updated_by', 'deleted_by'], true)) {
            return $name === 'deleted_by' ? null : self::effectiveUserId();
        }

        if (in_array($name, ['created_ip', 'updated_ip'], true)) {
            return Yii::$app->request->userIP ?: '0.0.0.0';
        }

        return null;
    }

    public static function effectiveUserId(): int
    {
        if (class_exists(ProjectSchema::class) && ProjectSchema::supportsProjectContext()) {
            $projectId = (new ActiveProjectContext())->getActiveProjectId();
            if ($projectId !== null) {
                $workspaceUser = (new ProjectAuthContext())->getAuthenticatedUser($projectId);
                if ($workspaceUser !== null) {
                    return (int)$workspaceUser->id;
                }
            }
        }

        $commanderAuth = new CommanderAuthContext();
        if ($commanderAuth->isAuthenticated()) {
            $commanderUser = $commanderAuth->getUser();
            if ($commanderUser !== null) {
                return (int)$commanderUser->id;
            }
        }

        if (!Yii::$app->user->isGuest && Yii::$app->user->id !== null) {
            return (int)Yii::$app->user->id;
        }

        return 0;
    }

    public static function columnName($column): string
    {
        if (is_array($column)) {
            return (string)($column['name'] ?? $column['column_name'] ?? '');
        }

        if (is_object($column) && isset($column->name)) {
            return (string)$column->name;
        }

        return '';
    }

    private static function columnValue($column, array $keys)
    {
        if (is_array($column)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $column)) {
                    return $column[$key];
                }
            }
            return null;
        }

        if (is_object($column)) {
            foreach ($keys as $key) {
                if (isset($column->{$key})) {
                    return $column->{$key};
                }
                if (method_exists($column, 'hasAttribute') && $column->hasAttribute($key)) {
                    return $column->getAttribute($key);
                }
            }
        }

        return null;
    }

    private static function stringValue($value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_object($value)) {
            if (isset($value->expression)) {
                return (string)$value->expression;
            }
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }
        }

        return '';
    }

    private static function dateValueForColumn($column): string
    {
        $type = strtolower((string)(is_object($column) ? ($column->type ?? '') : ($column['type'] ?? '')));
        $dbType = strtolower((string)(is_object($column) ? ($column->dbType ?? '') : ($column['dbType'] ?? $column['db_type'] ?? '')));
        if ($type === 'date' && strpos($dbType, 'time') === false) {
            return date('Y-m-d');
        }
        if ($type === 'time') {
            return date('H:i:s');
        }

        return date('Y-m-d H:i:s');
    }

    private static function allowsNull($column): bool
    {
        if (is_array($column)) {
            return !empty($column['is_nullable']) || !empty($column['allowNull']);
        }

        if (is_object($column)) {
            if (isset($column->is_nullable)) {
                return (bool)$column->is_nullable;
            }
            if (isset($column->allowNull)) {
                return (bool)$column->allowNull;
            }
        }

        return true;
    }
}
