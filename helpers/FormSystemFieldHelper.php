<?php

namespace app\helpers;

use app\components\SystemFieldService;

class FormSystemFieldHelper
{
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
        $type = strtoupper(trim((string)$columnType));
        if ($type === '') {
            return null;
        }

        if (preg_match('/^([A-Z]+)/', $type, $matches) === 1) {
            $type = $matches[1];
        }

        if ($type === 'DATE') {
            return 'date';
        }

        if ($type === 'TIME') {
            return 'time';
        }

        if ($type === 'DATETIME' || $type === 'TIMESTAMP') {
            return 'datetime-local';
        }

        return null;
    }

    public static function resolveFieldInputType(array $field): string
    {
        $columnType = $field['source_column_type'] ?? $field['base_type'] ?? $field['db_type'] ?? null;
        $dateTimeInputType = self::inputTypeFromColumnType($columnType);
        if ($dateTimeInputType !== null) {
            return $dateTimeInputType;
        }

        $type = strtolower(trim((string)($field['inputType'] ?? $field['type'] ?? 'text')));
        return $type === 'datetime' ? 'datetime-local' : $type;
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
