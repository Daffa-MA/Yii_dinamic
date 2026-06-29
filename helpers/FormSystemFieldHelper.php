<?php

namespace app\helpers;

use app\components\SystemFieldService;

class FormSystemFieldHelper
{
    private const BOOLEAN_FIELD_NAME_PATTERN = '/^(status|is_active|active|enabled|published|visible)$/i';

    public static function systemFields(): array
    {
        return SystemFieldService::auditFields();
    }

    public static function isSystemField($fieldName): bool
    {
        return SystemFieldService::isAuditField((string)$fieldName);
    }

    public static function isSystemFieldData(array $field): bool
    {
        return SystemFieldService::isSystemFieldData($field);
    }

    public static function inputTypeFromColumnType($columnType): ?string
    {
        $type = self::normalizeColumnType((string)$columnType);
        if ($type === '') {
            return null;
        }

        if (self::isBooleanColumnTypeString($type)) {
            return 'boolean';
        }

        if (strpos($type, 'date') === 0) {
            return 'date';
        }

        if (strpos($type, 'time') === 0) {
            return 'time';
        }

        if ($type === 'datetime' || $type === 'timestamp') {
            return 'datetime-local';
        }

        return null;
    }

    public static function resolveFieldInputType(array $field): string
    {
        $componentType = strtolower(trim((string)($field['type'] ?? $field['field_type'] ?? '')));
        if (in_array($componentType, ['select', 'radio', 'checkboxes'], true)) {
            return $componentType;
        }
        if ($componentType === 'camera') {
            return 'camera';
        }
        if ($componentType === 'gps_camera') {
            return 'gps_camera';
        }
        $explicitComponentType = strtolower(trim((string)($field['component_type'] ?? $field['componentType'] ?? '')));
        if ($explicitComponentType === 'camera') {
            return 'camera';
        }
        if ($explicitComponentType === 'gps_camera') {
            return 'gps_camera';
        }

        if (self::isBooleanColumn($field)) {
            return 'boolean';
        }

        $columnType = self::firstNonEmptyString($field, [
            'source_column_db_type',
            'source_column_column_type',
            'source_column_data_type',
            'column_type',
            'db_type',
            'dbType',
            'data_type',
            'type',
            'base_type',
        ]);
        $dateTimeInputType = self::inputTypeFromColumnType($columnType);
        if ($dateTimeInputType !== null) {
            return $dateTimeInputType;
        }

        $type = strtolower(trim((string)($field['inputType'] ?? $field['type'] ?? 'text')));
        if (in_array($type, ['boolean', 'bool'], true)) {
            return 'boolean';
        }
        if ($type === 'datetime') {
            return 'datetime-local';
        }
        if (in_array($type, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'decimal', 'float', 'double', 'real', 'serial'], true)) {
            return 'number';
        }
        return $type;
    }

    public static function isBooleanColumn(array $field): bool
    {
        $columnType = self::firstNonEmptyString($field, [
            'source_column_db_type',
            'source_column_column_type',
            'source_column_data_type',
            'column_type',
            'db_type',
            'dbType',
            'data_type',
            'type',
            'base_type',
        ]);
        if ($columnType !== '' && self::isBooleanColumnTypeString($columnType)) {
            return true;
        }

        $length = self::firstNonEmptyString($field, ['source_column_length', 'length', 'size', 'precision']);
        if ($length !== '' && (int)$length === 1) {
            $normalizedType = self::normalizeColumnType($columnType);
            if (in_array($normalizedType, ['tinyint', 'bit', 'boolean', 'bool'], true)) {
                return true;
            }
        }

        $fieldName = strtolower(trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? '')));
        if ($fieldName !== '' && preg_match(self::BOOLEAN_FIELD_NAME_PATTERN, $fieldName) === 1) {
            return self::isBooleanColumnTypeString($columnType);
        }

        return false;
    }

    private static function normalizeColumnType(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/\s+(unsigned|zerofill)\b/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;
        return $normalized;
    }

    private static function isBooleanColumnTypeString(string $columnType): bool
    {
        $normalized = self::normalizeColumnType($columnType);
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['bool', 'boolean'], true)) {
            return true;
        }

        if (preg_match('/^bit\(1\)$/i', $normalized) === 1) {
            return true;
        }

        if (preg_match('/^tinyint\(1\)$/i', $normalized) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $field
     * @param array<int, string> $keys
     */
    private static function firstNonEmptyString(array $field, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $field)) {
                continue;
            }

            $value = trim((string)$field[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public static function filterFields(array $fields): array
    {
        return SystemFieldService::filterFields($fields);
    }

    public static function filterBuilderData(array $builderData): array
    {
        return SystemFieldService::filterBuilderData($builderData);
    }
}
