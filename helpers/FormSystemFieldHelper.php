<?php

namespace app\helpers;

class FormSystemFieldHelper
{
    private const SYSTEM_FIELDS = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function systemFields(): array
    {
        return self::SYSTEM_FIELDS;
    }

    public static function isSystemField($fieldName): bool
    {
        $name = strtolower(trim((string)$fieldName));
        return $name !== '' && in_array($name, self::SYSTEM_FIELDS, true);
    }

    public static function isSystemFieldData(array $field): bool
    {
        $fieldName = $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? '';
        return self::isSystemField($fieldName);
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
        $filtered = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            if (self::isSystemFieldData($field)) {
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
}
