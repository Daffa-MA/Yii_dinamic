<?php

namespace app\services;

use app\components\CustomCodeSandbox;
use app\helpers\FormSystemFieldHelper;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterForm;
use app\models\MasterFormLayout;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

class FormRenderService
{
    /** @var array<string, array<int, array<string, string>>> */
    private static array $dynamicChoiceOptionsCache = [];
    private static bool $gpsCameraScriptInjected = false;

    public function hasCustomCodePayload(array $renderPayload, ?MasterForm $form = null): bool
    {
        $customHtml = trim((string)($renderPayload['customHtml'] ?? ''));
        $customCss = trim((string)($renderPayload['customCss'] ?? ''));
        $customJs = trim((string)($renderPayload['customJs'] ?? ''));

        return !empty($renderPayload['useCustomCode']) || ($form !== null && !empty($form->custom_code_mode));
    }

    public function renderCustomCodeOnly(array $renderPayload): string
    {
        $customHtml = (string)($renderPayload['customHtml'] ?? '');
        $customCss = trim((string)($renderPayload['customCss'] ?? ''));
        $customJs = trim((string)($renderPayload['customJs'] ?? ''));
        $fields = is_array($renderPayload['fields'] ?? null) ? $renderPayload['fields'] : [];
        $formId = (int)($renderPayload['formId'] ?? 0);
        if (!empty($fields)) {
            $customHtml = self::hydrateCustomDropdownOptions($customHtml, $fields);
        }
        if ($formId > 0) {
            $customHtml = self::prepareCustomFormSubmission($customHtml, $formId);
        }

        if ($customHtml !== '' && preg_match('/^\s*(<!doctype html|<html)\b/i', $customHtml) === 1) {
            return $customHtml;
        }

        $html = '';
        if ($customCss !== '') {
            $html .= '<style>' . $customCss . '</style>';
        }

        $html .= $customHtml;

        if ($customJs !== '') {
            $html .= '<script>(function(){try{' . $customJs . '}catch(e){console.error(e);}})();</script>';
        }
        if ($formId > 0) {
            $html = self::appendCustomFormSubmitCollectorScript($html);
        }

        return $html;
    }

    public function buildRenderPayload(MasterForm $form, array $fields, ?MasterFormLayout $layout): array
    {
        $formCustomHtml = $form->hasAttribute('custom_html') ? (string)$form->custom_html : '';
        $formCustomCss = $form->hasAttribute('custom_css') ? (string)$form->custom_css : '';
        $formCustomJs = $form->hasAttribute('custom_js') ? (string)$form->custom_js : '';
        $layoutCustomHtml = $layout ? (string)$layout->custom_html : '';
        $layoutCustomCss = $layout ? (string)$layout->custom_css : '';
        $layoutCustomJs = $layout ? (string)$layout->custom_js : '';
        $customHtml = CustomCodeSandbox::sanitizeHtml($formCustomHtml !== '' ? $formCustomHtml : $layoutCustomHtml);
        $customCss = CustomCodeSandbox::sanitizeCss($formCustomCss !== '' ? $formCustomCss : $layoutCustomCss);
        $customJs = CustomCodeSandbox::sanitizeJs($formCustomJs !== '' ? $formCustomJs : $layoutCustomJs);
        $useCustomCode = $layout !== null
            ? ($layout->hasAttribute('use_custom_code') && !empty($layout->use_custom_code))
                || ($form->hasAttribute('use_custom_code') && !empty($form->use_custom_code))
                || !empty($form->custom_code_mode)
            : ($form->hasAttribute('use_custom_code') && !empty($form->use_custom_code)) || !empty($form->custom_code_mode);

        $rawFieldDebug = [];
        $normalizedFields = [];
        foreach (FormSystemFieldHelper::filterFields($fields) as $index => $field) {
            if (!is_array($field)) {
                continue;
            }
            $normalizedFields[] = self::normalizeFieldForRender($field, (int)$index);
            $rawFieldDebug[] = [
                'index' => (int)$index,
                'raw_name' => (string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''),
                'raw_type' => (string)($field['inputType'] ?? $field['type'] ?? $field['field_type'] ?? ''),
                'relation_config' => $field['relation_config'] ?? null,
                'is_foreign_key' => !empty($field['is_foreign_key']),
            ];
        }

        $fields = array_map(static function (array $field): array {
            $resolvedName = trim((string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
            if ($resolvedName !== '') {
                $field['resolved_name'] = $resolvedName;
                $field['resolved_column_name'] = $resolvedName;
                $field['name'] = $resolvedName;
                $field['field_name'] = $resolvedName;
                $field['field_key'] = $resolvedName;
                $field['column_name'] = $resolvedName;
            }
            $resolvedLabel = trim((string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? ''));
            if ($resolvedLabel !== '') {
                $field['resolved_label'] = $resolvedLabel;
                $field['label'] = $resolvedLabel;
                $field['field_label'] = $resolvedLabel;
            }
            $field['inputType'] = FormSystemFieldHelper::resolveFieldInputType($field);
            if (self::isRelationField($field)) {
                $field['is_foreign_key'] = true;
                if (!self::shouldPreserveChoiceTypeForTableSource($field)) {
                    $field['inputType'] = 'select';
                    $field['type'] = 'select';
                    $field['field_type'] = 'select';
                }
            }
            if (in_array($field['inputType'], ['date', 'time', 'datetime-local'], true)) {
                $field['type'] = $field['inputType'];
            }
            $field = self::resolveDynamicChoiceOptions($field);
            return $field;
        }, $normalizedFields);
        Yii::info([
            'form_id' => (int)$form->id,
            'target_table' => $form->table_id ?? null,
            'raw_fields' => $rawFieldDebug,
            'normalized_fields' => array_map(static function (array $field): array {
                return [
                    'name' => $field['name'] ?? null,
                    'column_name' => $field['column_name'] ?? null,
                    'label' => $field['label'] ?? null,
                    'type' => $field['type'] ?? null,
                    'inputType' => $field['inputType'] ?? null,
                    'relation_config' => $field['relation_config'] ?? null,
                    'is_foreign_key' => !empty($field['is_foreign_key']),
                    'options_count' => is_array($field['options'] ?? null) ? count($field['options']) : 0,
                ];
            }, $fields),
        ], 'form-render-fields');
        $customHtml = self::normalizeCustomFieldNames($customHtml, $fields);
        $customHtml = self::resolveFormSourceTokens($customHtml, $fields);
        $customHtml = self::hydrateCustomDropdownOptions($customHtml, $fields);

        return [
            'fields' => $fields,
            'formId' => (int)$form->id,
            'hasOverride' => $useCustomCode,
            'useCustomCode' => $useCustomCode,
            'customHtml' => $customHtml,
            'customCss' => $customCss,
            'customJs' => $customJs,
        ];
    }

    public static function resolveDynamicChoiceOptions(array $field): array
    {
        $field = self::resolvePresetChoiceOptions($field);
        $relationConfig = self::resolveRelationConfig($field);
        $isForeignKey = self::isRelationField($field);
        if ($isForeignKey) {
            $field['is_foreign_key'] = true;
            if (!self::shouldPreserveChoiceTypeForTableSource($field)) {
                $field['type'] = 'select';
                $field['field_type'] = 'select';
                $field['inputType'] = 'select';
            }
        }

        $type = (string)($field['type'] ?? $field['field_type'] ?? '');
        $source = (string)($field['dropdown_source'] ?? $field['options_source'] ?? '');
        if (!in_array($type, ['select', 'radio', 'checkboxes'], true) || ($source !== 'table' && !$isForeignKey)) {
            return $field;
        }

        $tableId = (int)($field['source_table_id'] ?? $field['dropdown_table_id'] ?? 0);
        $fkTableName = trim((string)($field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? $field['referenced_table_name'] ?? $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? $relationConfig['target_table'] ?? ''));
        $valueColumn = trim((string)($field['value_column'] ?? $field['dropdown_value_column'] ?? $field['fk_referenced_column'] ?? $field['referenced_column_name'] ?? $relationConfig['referenced_value_column'] ?? $relationConfig['value_column'] ?? $relationConfig['referenced_column'] ?? $relationConfig['referenced_column_name'] ?? ''));
        $labelColumn = trim((string)($field['display_column'] ?? $field['label_column'] ?? $field['dropdown_label_column'] ?? $field['fk_display_column'] ?? $relationConfig['display_column'] ?? $relationConfig['display_column_name'] ?? ''));

        try {
            $tableName = '';
            if ($tableId > 0) {
                $table = DbTable::findOne($tableId);
                if ($table === null) {
                    return $field;
                }
                $tableName = (string)$table->name;
            } elseif ($fkTableName !== '') {
                $tableName = $fkTableName;
            }

            $db = Yii::$app->db;
            if ($tableName === '') {
                return $field;
            }

            $schema = $db->schema->getTableSchema($tableName, true);
            if ($schema === null) {
                return $field;
            }

            $valueColumn = self::resolveValueColumnForSchema($schema, $valueColumn);
            if ($valueColumn === '' || !isset($schema->columns[$valueColumn])) {
                return $field;
            }

            $labelColumn = self::resolveDisplayColumnForSchema($schema, $tableName, $valueColumn, $labelColumn);

            $cacheKey = (string)$db->dsn . '|' . $tableName . '|' . $valueColumn . '|' . $labelColumn;
            if (isset(self::$dynamicChoiceOptionsCache[$cacheKey])) {
                $options = self::$dynamicChoiceOptionsCache[$cacheKey];
            } else {
                $rows = (new \yii\db\Query())
                    ->select([
                        'value' => $valueColumn,
                        'label' => $labelColumn,
                    ])
                    ->from($tableName)
                    ->orderBy([$labelColumn => SORT_ASC])
                    ->limit(500)
                    ->all($db);

                $options = [];
                foreach ($rows as $row) {
                    $value = isset($row['value']) ? (string)$row['value'] : '';
                    if ($value === '') {
                        continue;
                    }

                    $label = trim((string)($row['label'] ?? ''));
                    $options[] = [
                        'value' => $value,
                        'label' => $label !== '' ? $label : $value,
                    ];
                }
                self::$dynamicChoiceOptionsCache[$cacheKey] = $options;
            }

            $field['options'] = $options;
            $field['fk_options'] = $options;
            $field['inputType'] = ($isForeignKey && !self::shouldPreserveChoiceTypeForTableSource($field)) ? 'select' : ($field['inputType'] ?? $type);
            $field['type'] = ($isForeignKey && !self::shouldPreserveChoiceTypeForTableSource($field)) ? 'select' : ($field['type'] ?? $type);
            $field['dynamic_options_loaded'] = true;
            $field['value_column'] = $valueColumn;
            $field['dropdown_value_column'] = $valueColumn;
            $field['fk_referenced_column'] = $valueColumn;
            $field['display_column'] = $labelColumn;
            $field['label_column'] = $labelColumn;
            $field['dropdown_label_column'] = $labelColumn;
            $field['fk_display_column'] = $labelColumn;
            $field['fk_referenced_table'] = $tableName;
            $field['relation_config'] = array_filter(array_merge($relationConfig, [
                'local_column' => (string)($field['resolved_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''),
                'source_column' => (string)($field['resolved_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''),
                'column_name' => (string)($field['resolved_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''),
                'referenced_table' => $tableName,
                'referenced_table_name' => $tableName,
                'referenced_value_column' => $valueColumn,
                'referenced_column' => $valueColumn,
                'referenced_column_name' => $valueColumn,
                'value_column' => $valueColumn,
                'display_column' => $labelColumn,
                'display_column_name' => $labelColumn,
            ]), static function ($value): bool {
                return $value !== null && $value !== '';
            });
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve dynamic dropdown options: ' . $e->getMessage(), 'form-render');
        }

        return $field;
    }

    private static function resolvePresetChoiceOptions(array $field): array
    {
        $type = (string)($field['type'] ?? $field['field_type'] ?? $field['inputType'] ?? '');
        $source = (string)($field['option_source'] ?? '');
        $preset = (string)($field['option_preset'] ?? '');
        if (($source !== 'preset' && $preset === '') || $preset !== 'calendar_months') {
            return $field;
        }
        if (!in_array($type, ['select', 'radio', 'checkboxes'], true)) {
            $field['type'] = 'checkboxes';
            $field['field_type'] = 'checkboxes';
            $field['inputType'] = 'checkboxes';
        }

        $field['options'] = self::calendarMonthOptions();
        $field['dropdown_source'] = 'preset';
        $field['option_source'] = 'preset';
        $field['option_preset'] = 'calendar_months';

        return $field;
    }

    private static function calendarMonthOptions(): array
    {
        return [
            ['value' => '01', 'label' => 'Januari'],
            ['value' => '02', 'label' => 'Februari'],
            ['value' => '03', 'label' => 'Maret'],
            ['value' => '04', 'label' => 'April'],
            ['value' => '05', 'label' => 'Mei'],
            ['value' => '06', 'label' => 'Juni'],
            ['value' => '07', 'label' => 'Juli'],
            ['value' => '08', 'label' => 'Agustus'],
            ['value' => '09', 'label' => 'September'],
            ['value' => '10', 'label' => 'Oktober'],
            ['value' => '11', 'label' => 'November'],
            ['value' => '12', 'label' => 'Desember'],
        ];
    }

    public static function normalizeFieldForRender(array $field, int $index = 0): array
    {
        $relationConfig = self::resolveRelationConfig($field);
        $relationName = trim((string)($relationConfig['local_column'] ?? $relationConfig['source_column'] ?? $relationConfig['column_name'] ?? $relationConfig['field_name'] ?? $relationConfig['field_key'] ?? ''));
        $metadataName = trim((string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['column_name'] ?? $field['local_column'] ?? $field['source_column'] ?? $field['source_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? ''));
        $name = self::isRelationField($field) && $relationName !== ''
            ? $relationName
            : $metadataName;
        if (self::looksLikeFallbackFieldName($name) && $relationName !== '') {
            $name = $relationName;
        }
        if ($name === '') {
            $name = 'field_' . ($index + 1);
        }
        if (self::looksLikeFallbackFieldName($name) && self::isRelationField($field)) {
            $resolvedFkName = self::resolveFkNameFromField($field);
            if ($resolvedFkName !== null && !self::looksLikeFallbackFieldName($resolvedFkName)) {
                $name = $resolvedFkName;
            }
        }
        $label = trim((string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? ''));
        if ($label === '' || self::looksLikeFallbackFieldName($label)) {
            if (self::isRelationField($field)) {
                $relationConfig = self::extractRelationConfig($field);
                $localColumn = $relationConfig['local_column'] ?? $relationConfig['source_column'] ?? $relationConfig['column_name'] ?? '';
                $referencedTable = $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? $field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? '';
                if ($localColumn !== '' && !self::looksLikeFallbackFieldName($localColumn)) {
                    $label = $localColumn;
                } elseif ($referencedTable !== '') {
                    $label = $referencedTable;
                }
            }
        }
        if ($label === '' || self::looksLikeFallbackFieldName($label)) {
            $resolvedFkName = self::resolveFkNameFromField($field);
            if ($resolvedFkName !== null && !self::looksLikeFallbackFieldName($resolvedFkName)) {
                $label = $resolvedFkName;
            }
        }
        if ($label === '' || self::looksLikeFallbackFieldName($label)) {
            $label = ucwords(str_replace('_', ' ', $name));
        }

        $field['resolved_name'] = $name;
        $field['resolved_column_name'] = $name;
        $field['name'] = $name;
        $field['field_name'] = $name;
        $field['field_key'] = $name;
        $field['column_name'] = $name;
        $field['resolved_label'] = $label;
        $field['label'] = $label;
        $field['field_label'] = $label;

        if (self::isRelationField($field)) {
            $field['is_foreign_key'] = true;
            if (!self::shouldPreserveChoiceTypeForTableSource($field)) {
                $field['type'] = 'select';
                $field['field_type'] = 'select';
                $field['inputType'] = 'select';
            }
            if (!empty($relationConfig)) {
                $field['relation_config'] = $relationConfig;
            }
        }

        return $field;
    }

    private static function shouldPreserveChoiceTypeForTableSource(array $field): bool
    {
        $type = (string)($field['type'] ?? $field['field_type'] ?? $field['inputType'] ?? '');
        $source = (string)($field['option_source'] ?? $field['dropdown_source'] ?? '');

        return in_array($type, ['radio', 'checkboxes'], true)
            && in_array($source, ['table'], true);
    }

    public static function isRelationField(array $field): bool
    {
        $relationConfig = self::resolveRelationConfig($field);
        if (self::isCompleteRelationConfig($relationConfig)) {
            return true;
        }

        $localColumn = trim((string)($field['resolved_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? $field['local_column'] ?? $field['source_column'] ?? ''));
        $referencedTable = trim((string)($field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? $field['referenced_table_name'] ?? ''));
        $referencedValueColumn = trim((string)($field['fk_referenced_column'] ?? $field['referenced_value_column'] ?? $field['referenced_column_name'] ?? $field['value_column'] ?? ''));

        return $localColumn !== '' && $referencedTable !== '' && $referencedValueColumn !== '';
    }

    public static function looksLikeFallbackFieldName(string $name): bool
    {
        $normalized = strtolower(trim($name));
        return preg_match('/^field[\s_-]*\d+$/', $normalized) === 1
            || preg_match('/^kolom[\s_-]*\d+$/', $normalized) === 1;
    }

    public static function resolveFkNameFromField(array $field): ?string
    {
        $relationConfig = self::extractRelationConfig($field);
        $candidates = array_filter(array_unique([
            $relationConfig['local_column'] ?? null,
            $relationConfig['source_column'] ?? null,
            $relationConfig['column_name'] ?? null,
            $relationConfig['original_column'] ?? null,
            $relationConfig['field_name'] ?? null,
            $relationConfig['field_key'] ?? null,
            $field['source_column_name'] ?? null,
            $field['local_column'] ?? null,
            $field['source_column'] ?? null,
            $field['relation_target_column'] ?? null,
            $field['name'] ?? null,
            $field['field_name'] ?? null,
            $field['column_name'] ?? null,
            $field['field_key'] ?? null,
        ]));
        $nonFallback = array_values(array_filter($candidates, static function (string $v): bool {
            return $v !== '' && !self::looksLikeFallbackFieldName($v);
        }));
        if (!empty($nonFallback)) {
            return $nonFallback[0];
        }
        $referencedTable = $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? $field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? '';
        if ($referencedTable !== '') {
            $candidate = strtolower(trim($referencedTable)) . '_id';
            if (!self::looksLikeFallbackFieldName($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    public static function resolveValueColumnForSchema(\yii\db\TableSchema $schema, string $requestedColumn = ''): string
    {
        $requestedColumn = trim($requestedColumn);
        if ($requestedColumn !== '' && isset($schema->columns[$requestedColumn])) {
            return $requestedColumn;
        }

        foreach (['id'] as $candidate) {
            if (isset($schema->columns[$candidate])) {
                return $candidate;
            }
        }

        if (!empty($schema->primaryKey)) {
            $primaryKey = (string)$schema->primaryKey[0];
            if ($primaryKey !== '' && isset($schema->columns[$primaryKey])) {
                return $primaryKey;
            }
        }

        foreach ($schema->columns as $columnName => $columnSchema) {
            if ($columnSchema->isPrimaryKey) {
                return (string)$columnName;
            }
        }

        foreach ($schema->columns as $columnName => $columnSchema) {
            return (string)$columnName;
        }

        return 'id';
    }

    public static function resolveDisplayColumnForSchema(\yii\db\TableSchema $schema, string $tableName, string $valueColumn, string $requestedColumn = ''): string
    {
        $requestedColumn = trim($requestedColumn);
        if ($requestedColumn !== '' && isset($schema->columns[$requestedColumn])) {
            return $requestedColumn;
        }

        $normalizedTable = strtolower(trim($tableName));
        $priorityCandidates = array_filter(array_unique([
            'name',
            'nama',
            'title',
            'label',
            $normalizedTable !== '' ? 'nama_' . $normalizedTable : '',
            'kode',
        ]));

        foreach ($priorityCandidates as $candidate) {
            if ($candidate === $valueColumn) {
                continue;
            }
            if (isset($schema->columns[$candidate])) {
                return (string)$candidate;
            }
        }

        foreach ($schema->columns as $columnName => $columnSchema) {
            if (self::isReadableDisplayColumn($columnName, $columnSchema, $valueColumn)) {
                return (string)$columnName;
            }
        }

        foreach ($schema->columns as $columnName => $columnSchema) {
            if ((string)$columnName !== $valueColumn) {
                return (string)$columnName;
            }
        }

        return $valueColumn;
    }

    private static function isReadableDisplayColumn(string $columnName, \yii\db\ColumnSchema $columnSchema, string $valueColumn): bool
    {
        if ($columnName === $valueColumn || $columnSchema->isPrimaryKey) {
            return false;
        }

        $normalized = strtolower(trim($columnName));
        if (in_array($normalized, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'], true)) {
            return false;
        }

        if (substr($normalized, -3) === '_id') {
            return false;
        }

        $phpType = strtolower((string)$columnSchema->phpType);
        return in_array($phpType, ['string', 'integer', 'double'], true);
    }

    private static function extractRelationConfig(array $field): array
    {
        foreach (['relation_config', 'relationConfig', 'relation'] as $key) {
            $value = $field[$key] ?? null;
            if (is_array($value)) {
                return $value;
            }
            if (is_string($value) && trim($value) !== '') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    private static function resolveRelationConfig(array $field): array
    {
        $relationConfig = self::extractRelationConfig($field);
        if (self::isCompleteRelationConfig($relationConfig)) {
            return self::normalizeRelationConfigKeys($relationConfig, $field);
        }

        $metadataConfig = self::buildRelationConfigFromMetadata($field);
        if (self::isCompleteRelationConfig($metadataConfig)) {
            return self::normalizeRelationConfigKeys(array_merge($relationConfig, $metadataConfig), $field);
        }

        return [];
    }

    private static function buildRelationConfigFromMetadata(array $field): array
    {
        $localColumn = trim((string)($field['resolved_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? $field['local_column'] ?? $field['source_column'] ?? ''));
        $sourceColumnId = (int)($field['source_column_id'] ?? 0);
        $sourceColumn = $sourceColumnId > 0 ? DbTableColumn::findOne($sourceColumnId) : null;

        $referencedTable = trim((string)($field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? $field['referenced_table_name'] ?? ''));
        $referencedValueColumn = trim((string)($field['fk_referenced_column'] ?? $field['referenced_value_column'] ?? $field['referenced_column_name'] ?? $field['value_column'] ?? ''));
        $displayColumn = trim((string)($field['fk_display_column'] ?? $field['display_column'] ?? $field['label_column'] ?? ''));

        if ($sourceColumn instanceof DbTableColumn) {
            if ($localColumn === '') {
                $localColumn = trim((string)$sourceColumn->name);
            }
            if ($referencedTable === '' && $sourceColumn->hasAttribute('referenced_table_name')) {
                $referencedTable = trim((string)$sourceColumn->getAttribute('referenced_table_name'));
            }
            if ($referencedValueColumn === '' && $sourceColumn->hasAttribute('referenced_column_name')) {
                $referencedValueColumn = trim((string)$sourceColumn->getAttribute('referenced_column_name'));
            }
        }

        if ($referencedTable === '' || $referencedValueColumn === '' || $localColumn === '') {
            return [];
        }

        if ($displayColumn === '') {
            try {
                $schema = Yii::$app->db->schema->getTableSchema($referencedTable, true);
                if ($schema !== null) {
                    $displayColumn = self::resolveDisplayColumnForSchema($schema, $referencedTable, $referencedValueColumn, '');
                }
            } catch (\Throwable $e) {
                $displayColumn = '';
            }
        }

        return array_filter([
            'local_column' => $localColumn,
            'source_column' => $localColumn,
            'column_name' => $localColumn,
            'referenced_table' => $referencedTable,
            'referenced_table_name' => $referencedTable,
            'referenced_value_column' => $referencedValueColumn,
            'referenced_column' => $referencedValueColumn,
            'referenced_column_name' => $referencedValueColumn,
            'value_column' => $referencedValueColumn,
            'display_column' => $displayColumn,
            'display_column_name' => $displayColumn,
        ], static function ($value): bool {
            return $value !== null && $value !== '';
        });
    }

    private static function isCompleteRelationConfig(array $config): bool
    {
        $localColumn = trim((string)($config['local_column'] ?? $config['source_column'] ?? $config['column_name'] ?? ''));
        $referencedTable = trim((string)($config['referenced_table'] ?? $config['referenced_table_name'] ?? ''));
        $referencedValueColumn = trim((string)($config['referenced_value_column'] ?? $config['referenced_column'] ?? $config['referenced_column_name'] ?? $config['value_column'] ?? ''));

        return $localColumn !== '' && $referencedTable !== '' && $referencedValueColumn !== '';
    }

    private static function normalizeRelationConfigKeys(array $config, array $field = []): array
    {
        $localColumn = trim((string)($config['local_column'] ?? $config['source_column'] ?? $config['column_name'] ?? $field['resolved_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
        $referencedTable = trim((string)($config['referenced_table'] ?? $config['referenced_table_name'] ?? ''));
        $referencedValueColumn = trim((string)($config['referenced_value_column'] ?? $config['referenced_column'] ?? $config['referenced_column_name'] ?? $config['value_column'] ?? ''));
        $displayColumn = trim((string)($config['display_column'] ?? $config['display_column_name'] ?? ''));

        return array_filter(array_merge($config, [
            'local_column' => $localColumn,
            'source_column' => $localColumn,
            'column_name' => $localColumn,
            'referenced_table' => $referencedTable,
            'referenced_table_name' => $referencedTable,
            'referenced_value_column' => $referencedValueColumn,
            'referenced_column' => $referencedValueColumn,
            'referenced_column_name' => $referencedValueColumn,
            'value_column' => $referencedValueColumn,
            'display_column' => $displayColumn,
            'display_column_name' => $displayColumn,
        ]), static function ($value): bool {
            return $value !== null && $value !== '';
        });
    }

    public static function prepareCustomFormSubmission(string $html, int $formId, array $hiddenInputs = []): string
    {
        if ($formId <= 0 || trim($html) === '' || stripos($html, '<form') === false) {
            return $html;
        }

        $action = Url::to(['/master-form/submit', 'id' => $formId], true);
        $csrfParam = Yii::$app->request->csrfParam;
        $csrfToken = Yii::$app->request->getCsrfToken();
        $hidden = '<input type="hidden" name="' . Html::encode($csrfParam) . '" value="' . Html::encode($csrfToken) . '">';
        foreach ($hiddenInputs as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $hidden .= '<input type="hidden" name="' . Html::encode((string)$name) . '" value="' . Html::encode((string)$value) . '">';
        }

        $hasCsrfInput = stripos($html, 'name="' . $csrfParam . '"') !== false || stripos($html, "name='" . $csrfParam . "'") !== false;
        $prepared = preg_replace_callback('/<form\b([^>]*)>/i', static function (array $matches) use ($action, $hidden, $hasCsrfInput): string {
            $attrs = $matches[1] ?? '';
            if (!preg_match('/\bmethod\s*=/i', $attrs)) {
                $attrs .= ' method="post"';
            }
            if (!preg_match('/\baction\s*=\s*([\'"])[^\'"]+\1/i', $attrs)) {
                $attrs .= ' action="' . Html::encode($action) . '"';
            }

            $openTag = '<form' . $attrs . '>';
            if ($hasCsrfInput) {
                return $openTag;
            }

            return $openTag . $hidden;
        }, $html) ?? $html;

        return self::appendCustomFormSubmitCollectorScript($prepared);
    }

    public static function renderGpsCameraField(array $field, bool $interactive = true, bool $includeLabel = true): string
    {
        $field = self::normalizeFieldForRender($field);
        $name = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? 'gps_camera'));
        $name = $name !== '' ? $name : 'gps_camera';
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name) ?: 'gps_camera';
        $label = trim((string)($field['label'] ?? $field['field_label'] ?? 'GPS Camera'));
        $label = $label !== '' ? $label : 'GPS Camera';
        $required = !empty($field['required']);
        $readonly = !empty($field['readonly']) || !empty($field['readOnly']);
        $hidden = !empty($field['hidden']) || !empty($field['excluded']);
        $previewImage = !array_key_exists('preview_image', $field) || !empty($field['preview_image']) || (string)($field['preview_image'] ?? '') === '1';
        $autoGps = !array_key_exists('auto_capture_gps', $field) || !empty($field['auto_capture_gps']) || (string)($field['auto_capture_gps'] ?? '') === '1';
        $autoTimestamp = !array_key_exists('auto_capture_timestamp', $field) || !empty($field['auto_capture_timestamp']) || (string)($field['auto_capture_timestamp'] ?? '') === '1';
        $timezone = new \DateTimeZone(Yii::$app->timeZone ?: 'Asia/Jakarta');
        $serverNow = new \DateTimeImmutable('now', $timezone);
        $serverDate = $serverNow->format('Y-m-d');
        $serverTime = $serverNow->format('H:i:s');
        $serverAt = $serverNow->format(DATE_ATOM);
        $targetTable = trim((string)($field['target_table_name'] ?? ''));
        $targetColumn = trim((string)($field['target_column_name'] ?? ''));
        $metadataUrl = Url::to(['/form/gps-camera-metadata']);
        $defaultPayload = json_decode(trim((string)($field['default_value'] ?? '')), true);
        $defaultPayload = is_array($defaultPayload) ? $defaultPayload : [];
        $initialPreview = trim((string)($defaultPayload['photo_url'] ?? $defaultPayload['photo_path'] ?? ''));
        $initialPayload = array_filter([
            'field_name' => $name,
            'photo_path' => (string)($defaultPayload['photo_path'] ?? ''),
            'photo_url' => (string)($defaultPayload['photo_url'] ?? ''),
            'latitude' => (string)($defaultPayload['latitude'] ?? ''),
            'longitude' => (string)($defaultPayload['longitude'] ?? ''),
            'gps_accuracy' => (string)($defaultPayload['gps_accuracy'] ?? ''),
            'captured_date' => (string)($defaultPayload['captured_date'] ?? ''),
            'captured_time' => (string)($defaultPayload['captured_time'] ?? ''),
            'photo_name' => (string)($defaultPayload['photo_name'] ?? ''),
            'photo_mime' => (string)($defaultPayload['photo_mime'] ?? ''),
            'photo_size' => (string)($defaultPayload['photo_size'] ?? ''),
            'captured_at_server' => (string)($defaultPayload['captured_at_server'] ?? ''),
        ], static fn($value) => $value !== null && $value !== '');
        $hiddenPayload = !empty($defaultPayload) && !empty($initialPayload)
            ? json_encode($initialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $wrapperStyle = $hidden ? 'display:none;' : '';
        $controlsDisabled = ($readonly || !$interactive) ? ' disabled aria-disabled="true"' : '';
        $requiredAttr = ($required && $interactive && !$readonly) ? ' required' : '';
        $buttonStyle = ($readonly || !$interactive) ? 'opacity:.55;cursor:not-allowed;' : '';
        $previewStyle = $previewImage ? '' : 'display:none;';

        $html = '<div class="gps-camera-field" data-gps-camera-component="1" data-field-name="' . Html::encode($name) . '" data-metadata-url="' . Html::encode($metadataUrl) . '" data-auto-gps="' . ($autoGps ? '1' : '0') . '" data-auto-timestamp="' . ($autoTimestamp ? '1' : '0') . '" data-preview-image="' . ($previewImage ? '1' : '0') . '" data-server-date="' . Html::encode($serverDate) . '" data-server-time="' . Html::encode($serverTime) . '" data-server-at="' . Html::encode($serverAt) . '" style="' . $wrapperStyle . 'margin-bottom:14px;position:relative;">'
            . ($includeLabel ? '<label style="display:block;font-size:12px;color:#334155;margin-bottom:6px;font-weight:600;">' . Html::encode($label) . ($required ? ' <span style="color:#dc2626;">*</span>' : '') . '</label>' : '')
            . '<input type="hidden" name="' . Html::encode($name) . '" value="' . Html::encode($hiddenPayload ?: '{}') . '" data-gps-camera-payload="1">'
            . '<input type="file" name="__gps_camera_file_' . Html::encode($safeName) . '" accept="image/*" capture="environment" data-gps-camera-file="1" style="display:none;"' . $controlsDisabled . $requiredAttr . '>'
            . '<div style="display:flex;flex-direction:column;gap:10px;padding:12px;border:1px solid #cbd5e1;border-radius:12px;background:#f8fafc;">'
            . '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">'
            . '<button type="button" data-gps-camera-trigger="1" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:none;border-radius:10px;background:#4f46e5;color:#fff;font-weight:700;cursor:pointer;' . $buttonStyle . '"' . $controlsDisabled . '>Ambil Foto</button>'
            . '<button type="button" data-gps-camera-clear="1" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#334155;font-weight:700;cursor:pointer;' . $buttonStyle . '"' . $controlsDisabled . '>Reset</button>'
            . '<span data-gps-camera-status="1" style="font-size:12px;color:#64748b;">' . Html::encode($targetTable !== '' || $targetColumn !== '' ? trim($targetTable . ' ' . $targetColumn) : 'Foto dan GPS akan disiapkan otomatis.') . '</span>'
            . '</div>'
            . '<div data-gps-camera-preview-wrap="1" style="' . $previewStyle . 'display:flex;flex-direction:column;gap:8px;">'
            . '<img data-gps-camera-preview="1" src="' . Html::encode($initialPreview) . '" alt="Preview foto" style="' . ($initialPreview !== '' ? '' : 'display:none;') . 'max-width:100%;border-radius:12px;border:1px solid #e2e8f0;background:#fff;object-fit:cover;">'
            . '<div data-gps-camera-meta="1" style="font-size:12px;color:#475569;line-height:1.6;"></div>'
            . '</div>'
            . '</div>'
            . '<div data-gps-camera-modal="1" hidden style="display:none;position:fixed;inset:0;z-index:12000;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.66);backdrop-filter:blur(4px);">'
            . '<div style="width:min(920px,100%);max-height:min(90vh,920px);background:#0f172a;border-radius:18px;overflow:hidden;box-shadow:0 30px 80px rgba(15,23,42,.42);display:flex;flex-direction:column;">'
            . '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;background:#111827;color:#fff;">'
            . '<strong style="font-size:14px;">Kamera GPS</strong>'
            . '<button type="button" data-gps-camera-modal-close="1" style="border:1px solid rgba(255,255,255,.18);background:transparent;color:#fff;border-radius:10px;padding:8px 12px;font-weight:700;cursor:pointer;">Tutup</button>'
            . '</div>'
            . '<div style="position:relative;background:#000;">'
            . '<video data-gps-camera-video="1" autoplay playsinline muted style="width:100%;max-height:72vh;object-fit:cover;display:block;background:#000;"></video>'
            . '<div data-gps-camera-live-overlay="1" style="position:absolute;left:14px;right:14px;bottom:14px;padding:10px 12px;border-radius:14px;background:rgba(15,23,42,.72);color:#fff;font-size:12px;line-height:1.55;box-shadow:0 8px 24px rgba(0,0,0,.25);"></div>'
            . '</div>'
            . '<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:12px 16px;background:#fff;">'
            . '<button type="button" data-gps-camera-modal-capture="1" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:none;border-radius:10px;background:#4f46e5;color:#fff;font-weight:700;cursor:pointer;">Ambil Foto</button>'
            . '<button type="button" data-gps-camera-modal-cancel="1" style="display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#334155;font-weight:700;cursor:pointer;">Batal</button>'
            . '<span data-gps-camera-modal-status="1" style="font-size:12px;color:#64748b;">Siap mengambil foto.</span>'
            . '</div>'
            . '<canvas data-gps-camera-canvas="1" hidden></canvas>'
            . '</div>'
            . '</div>'
            . '</div>';

        if (!self::$gpsCameraScriptInjected) {
            self::$gpsCameraScriptInjected = true;
            $html .= <<<'HTML'
<script>
(function(){
    if (window.__gpsCameraBinderInstalled) {
        return;
    }
    window.__gpsCameraBinderInstalled = true;
    var cameraStateMap = new WeakMap();

    function hasPayloadData(payload) {
        if (!payload) {
            return false;
        }
        return Object.keys(payload).some(function(key) {
            var value = payload[key];
            return value !== null && value !== undefined && String(value).trim() !== '';
        });
    }

    function encodePayload(payload) {
        try {
            return JSON.stringify(payload || {});
        } catch (e) {
            return '{}';
        }
    }

    function setPreview(wrapper, src) {
        var image = wrapper.querySelector('[data-gps-camera-preview]');
        if (!image) {
            return;
        }
        if (src) {
            image.src = src;
            image.style.display = 'block';
            return;
        }
        image.removeAttribute('src');
        image.style.display = 'none';
    }

    function setStatus(wrapper, message, modal) {
        var node = wrapper.querySelector(modal ? '[data-gps-camera-modal-status]' : '[data-gps-camera-status]');
        if (node) {
            node.textContent = message || '';
        }
    }

    function setMeta(wrapper, payload) {
        var node = wrapper.querySelector('[data-gps-camera-meta]');
        if (!node) {
            return;
        }
        var rows = [];
        if (payload.photo_name) rows.push('Nama file: ' + payload.photo_name);
        if (payload.location_text) rows.push('Lokasi: ' + payload.location_text);
        if (payload.latitude || payload.longitude) rows.push('Koordinat: ' + (payload.latitude || '-') + ', ' + (payload.longitude || '-'));
        if (payload.gps_accuracy) rows.push('Akurasi: ' + payload.gps_accuracy + ' m');
        if (payload.captured_date || payload.captured_time) rows.push('Waktu Jepret: ' + (payload.captured_date || '-') + (payload.captured_time ? ' ' + payload.captured_time : ''));
        node.innerHTML = rows.map(function(row){ return '<div>' + String(row).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>'; }).join('');
    }

    function applyPayload(wrapper, payload) {
        var input = wrapper.querySelector('[data-gps-camera-payload]');
        if (input) {
            input.value = hasPayloadData(payload) ? encodePayload(payload) : '';
        }
        setMeta(wrapper, payload || {});
    }

    function getState(wrapper) {
        var state = cameraStateMap.get(wrapper);
        if (!state) {
            state = {
                stream: null,
                contextPromise: null,
                context: null
            };
            cameraStateMap.set(wrapper, state);
        }
        return state;
    }

    function stopStream(state) {
        if (state && state.stream) {
            try {
                state.stream.getTracks().forEach(function(track) {
                    try { track.stop(); } catch (e) {}
                });
            } catch (e) {}
        }
        if (state) {
            state.stream = null;
        }
    }

    function getServerSnapshot(wrapper) {
        return {
            server_date: wrapper.getAttribute('data-server-date') || '',
            server_time: wrapper.getAttribute('data-server-time') || '',
            server_at: wrapper.getAttribute('data-server-at') || ''
        };
    }

    function getClientTimestamp() {
        var now = new Date();
        var pad = function(value) {
            return String(value).padStart(2, '0');
        };
        var year = now.getFullYear();
        var month = pad(now.getMonth() + 1);
        var day = pad(now.getDate());
        var hours = pad(now.getHours());
        var minutes = pad(now.getMinutes());
        var seconds = pad(now.getSeconds());

        return {
            captured_date: year + '-' + month + '-' + day,
            captured_time: hours + ':' + minutes + ':' + seconds,
            captured_at: year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds
        };
    }

    function normalizeText(value) {
        return String(value == null ? '' : value).replace(/\s+/g, ' ').trim();
    }

    function firstNonEmpty(values) {
        for (var i = 0; i < values.length; i++) {
            var value = normalizeText(values[i]);
            if (value !== '') {
                return value;
            }
        }
        return '';
    }

    function buildLocationLabel(address) {
        address = address || {};
        var district = firstNonEmpty([
            address.district,
            address.city_district,
            address.suburb,
            address.town,
            address.village,
            address.municipality,
            address.subdistrict
        ]);
        var regency = firstNonEmpty([
            address.county,
            address.city,
            address.state_district,
            address.state,
            address.region
        ]);
        var parts = [];
        if (district) {
            parts.push(/^kecamatan\s+/i.test(district) ? district : 'Kecamatan ' + district);
        }
        if (regency) {
            parts.push(/^(kabupaten|kota)\s+/i.test(regency) ? regency : 'Kabupaten/Kota ' + regency);
        }
        return parts.join(', ');
    }

    function fetchWithTimeout(url, options, timeoutMs) {
        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timer = null;
        if (controller && timeoutMs > 0) {
            timer = setTimeout(function() { controller.abort(); }, timeoutMs);
        }
        var requestOptions = options || {};
        if (controller) {
            requestOptions.signal = controller.signal;
        }
        return fetch(url, requestOptions).finally(function() {
            if (timer) {
                clearTimeout(timer);
            }
        });
    }

    function captureGps(wrapper) {
        if (wrapper.getAttribute('data-auto-gps') === '0' || !navigator.geolocation) {
            return Promise.resolve({});
        }
        return new Promise(function(resolve) {
            navigator.geolocation.getCurrentPosition(function(position) {
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    gps_accuracy: position.coords.accuracy
                });
            }, function() {
                resolve({});
            }, {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 5000
            });
        });
    }

    function fetchMetadata(wrapper, gps) {
        var endpoint = wrapper.getAttribute('data-metadata-url') || '';
        var params = new URLSearchParams();
        if (gps && gps.latitude !== undefined && gps.latitude !== null && gps.latitude !== '') {
            params.set('lat', gps.latitude);
        }
        if (gps && gps.longitude !== undefined && gps.longitude !== null && gps.longitude !== '') {
            params.set('lon', gps.longitude);
        }
        if (gps && gps.gps_accuracy !== undefined && gps.gps_accuracy !== null && gps.gps_accuracy !== '') {
            params.set('accuracy', gps.gps_accuracy);
        }

        var fallback = {
            server_date: wrapper.getAttribute('data-server-date') || '',
            server_time: wrapper.getAttribute('data-server-time') || '',
            server_at: wrapper.getAttribute('data-server-at') || '',
            latitude: gps && gps.latitude !== undefined && gps.latitude !== null ? String(gps.latitude) : '',
            longitude: gps && gps.longitude !== undefined && gps.longitude !== null ? String(gps.longitude) : '',
            gps_accuracy: gps && gps.gps_accuracy !== undefined && gps.gps_accuracy !== null ? String(gps.gps_accuracy) : '',
            location_text: '',
            location_address: ''
        };

        if (!endpoint) {
            return Promise.resolve(fallback);
        }

        var url = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + params.toString();
        return fetchWithTimeout(url, {
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        }, 6000)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('metadata_failed');
                }
                return response.json();
            })
            .then(function(data) {
                return {
                    server_date: data && data.server_date ? String(data.server_date) : fallback.server_date,
                    server_time: data && data.server_time ? String(data.server_time) : fallback.server_time,
                    server_at: data && data.server_at ? String(data.server_at) : fallback.server_at,
                    latitude: data && data.latitude !== undefined && data.latitude !== null && String(data.latitude) !== '' ? String(data.latitude) : fallback.latitude,
                    longitude: data && data.longitude !== undefined && data.longitude !== null && String(data.longitude) !== '' ? String(data.longitude) : fallback.longitude,
                    gps_accuracy: data && data.gps_accuracy !== undefined && data.gps_accuracy !== null && String(data.gps_accuracy) !== '' ? String(data.gps_accuracy) : fallback.gps_accuracy,
                    location_text: data && data.location_text ? String(data.location_text) : fallback.location_text,
                    location_address: data && data.location_address ? String(data.location_address) : fallback.location_address
                };
            })
            .catch(function() {
                return fallback;
            });
    }

    function fileToDataUrl(file) {
        return new Promise(function(resolve, reject) {
            var reader = new FileReader();
            reader.onload = function() { resolve(String(reader.result || '')); };
            reader.onerror = function() { reject(reader.error || new Error('file_read_error')); };
            reader.readAsDataURL(file);
        });
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise(function(resolve) {
            if (!canvas || !canvas.toBlob) {
                resolve(null);
                return;
            }
            canvas.toBlob(function(blob) {
                resolve(blob || null);
            }, type || 'image/jpeg', quality || 0.92);
        });
    }

    function blobToFile(blob, fileName, mimeType) {
        if (!blob) {
            return null;
        }
        try {
            return new File([blob], fileName, { type: mimeType || blob.type || 'image/jpeg' });
        } catch (e) {
            try {
                blob.name = fileName;
                blob.lastModified = Date.now();
            } catch (err) {}
            return blob;
        }
    }

    function setFileInputFile(wrapper, file) {
        var fileInput = wrapper.querySelector('[data-gps-camera-file]');
        if (!fileInput || !file) {
            return false;
        }

        try {
            var transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            return true;
        } catch (e) {
            return false;
        }
    }

    function clearFileInput(wrapper) {
        var fileInput = wrapper.querySelector('[data-gps-camera-file]');
        if (fileInput) {
            fileInput.value = '';
        }
    }

    function resetWrapper(wrapper) {
        var state = getState(wrapper);
        stopStream(state);
        clearFileInput(wrapper);
        applyPayload(wrapper, {});
        setPreview(wrapper, '');
        setStatus(wrapper, 'Foto belum dipilih.');
        setStatus(wrapper, 'Siap mengambil foto.', true);
        var modal = wrapper.querySelector('[data-gps-camera-modal]');
        if (modal) {
            modal.hidden = true;
            modal.style.display = 'none';
        }
    }

    function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
        var words = String(text || '').split(/\s+/);
        var line = '';
        var currentY = y;
        for (var n = 0; n < words.length; n++) {
            var testLine = line + words[n] + ' ';
            var metrics = ctx.measureText(testLine);
            if (metrics.width > maxWidth && n > 0) {
                ctx.fillText(line, x, currentY);
                line = words[n] + ' ';
                currentY += lineHeight;
            } else {
                line = testLine;
            }
        }
        ctx.fillText(line, x, currentY);
        return currentY + lineHeight;
    }

    function setLiveOverlay(wrapper, context) {
        var node = wrapper.querySelector('[data-gps-camera-live-overlay]');
        if (!node) {
            return;
        }
        var lines = [];
        if (context.location_text) {
            lines.push(context.location_text);
        }
        if (context.latitude || context.longitude) {
            lines.push('Lat ' + (context.latitude || '-') + ' | Lon ' + (context.longitude || '-'));
        }
        if (context.gps_accuracy) {
            lines.push('Akurasi ' + context.gps_accuracy + ' m');
        }
        if (context.server_date || context.server_time) {
            lines.push('Server ' + (context.server_date || '-') + ' ' + (context.server_time || '-') + ' WIB');
        }
        node.innerHTML = lines.length ? lines.map(function(line) {
            return '<div>' + String(line).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>';
        }).join('') : '<div>Menunggu GPS dan waktu server...</div>';
    }

    async function prepareCaptureContext(wrapper) {
        var state = getState(wrapper);
        if (state.context) {
            return state.context;
        }

        var serverInfo = getServerSnapshot(wrapper);
        var gps = await captureGps(wrapper);
        var geo = await fetchMetadata(wrapper, gps);

        state.context = {
            latitude: geo.latitude || (gps.latitude !== undefined && gps.latitude !== null ? String(gps.latitude) : ''),
            longitude: geo.longitude || (gps.longitude !== undefined && gps.longitude !== null ? String(gps.longitude) : ''),
            gps_accuracy: geo.gps_accuracy || (gps.gps_accuracy !== undefined && gps.gps_accuracy !== null ? String(gps.gps_accuracy) : ''),
            server_date: geo.server_date || serverInfo.server_date,
            server_time: geo.server_time || serverInfo.server_time,
            server_at: geo.server_at || serverInfo.server_at,
            location_text: geo.location_text || '',
            location_address: geo.location_address || ''
        };
        setLiveOverlay(wrapper, state.context);
        return state.context;
    }

    async function openCamera(wrapper) {
        var state = getState(wrapper);
        var modal = wrapper.querySelector('[data-gps-camera-modal]');
        var video = wrapper.querySelector('[data-gps-camera-video]');
        if (!modal || !video) {
            return false;
        }

        modal.hidden = false;
        modal.style.display = 'flex';
        setStatus(wrapper, 'Meminta akses kamera...');
        setStatus(wrapper, 'Menyiapkan kamera...', true);
        setLiveOverlay(wrapper, {});

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus(wrapper, 'Browser tidak mendukung kamera. Membuka pemilih file...');
            setStatus(wrapper, 'Browser tidak mendukung kamera.', true);
            clearFileInput(wrapper);
            var fallbackFileInput = wrapper.querySelector('[data-gps-camera-file]');
            if (fallbackFileInput && !fallbackFileInput.disabled) {
                fallbackFileInput.click();
            }
            return false;
        }

        try {
            stopStream(state);
            state.context = null;
            state.contextPromise = prepareCaptureContext(wrapper);
            state.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' }
                },
                audio: false
            });
            video.srcObject = state.stream;
            await video.play();
            setStatus(wrapper, 'Kamera aktif. Arahkan objek lalu ambil foto.');
            setStatus(wrapper, 'Arahkan kamera lalu ambil foto.', true);
            state.contextPromise.then(function(context) {
                if (context) {
                    setLiveOverlay(wrapper, context);
                }
            }).catch(function() {});
            return true;
        } catch (error) {
            stopStream(state);
            setStatus(wrapper, 'Akses kamera ditolak atau tidak tersedia. Membuka pemilih file...');
            setStatus(wrapper, 'Akses kamera tidak tersedia.', true);
            modal.hidden = true;
            modal.style.display = 'none';
            var fallback = wrapper.querySelector('[data-gps-camera-file]');
            if (fallback && !fallback.disabled) {
                fallback.click();
            }
            return false;
        }
    }

    async function captureFromCamera(wrapper) {
        var state = getState(wrapper);
        var video = wrapper.querySelector('[data-gps-camera-video]');
        var canvas = wrapper.querySelector('[data-gps-camera-canvas]');
        if (!video || !canvas) {
            return;
        }
        if (!video.videoWidth || !video.videoHeight) {
            setStatus(wrapper, 'Kamera belum siap.');
            setStatus(wrapper, 'Kamera belum siap.', true);
            return;
        }

        setStatus(wrapper, 'Memproses foto dan watermark...');
        setStatus(wrapper, 'Memproses foto...', true);

        var shotTime = getClientTimestamp();
        var context = {};
        state.context = null;
        try {
            context = await prepareCaptureContext(wrapper);
        } catch (e) {
            context = state.context || {};
        }
        state.context = context;

        var maxWidth = 1600;
        var width = video.videoWidth;
        var height = video.videoHeight;
        if (width > maxWidth) {
            height = Math.round(height * (maxWidth / width));
            width = maxWidth;
        }
        canvas.width = width;
        canvas.height = height;

        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, width, height);

        var boxPadding = Math.max(18, Math.round(width * 0.02));
        var boxWidth = width - (boxPadding * 2);
        var boxHeight = Math.max(150, Math.round(height * 0.18));
        var boxX = boxPadding;
        var boxY = height - boxPadding - boxHeight;

        ctx.save();
        ctx.fillStyle = 'rgba(15, 23, 42, .68)';
        ctx.fillRect(boxX, boxY, boxWidth, boxHeight);
        ctx.strokeStyle = 'rgba(255,255,255,.18)';
        ctx.strokeRect(boxX, boxY, boxWidth, boxHeight);
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 ' + Math.max(18, Math.round(width * 0.018)) + 'px Arial, sans-serif';
        ctx.fillText('GPS Camera', boxX + 18, boxY + 28);
        ctx.font = Math.max(13, Math.round(width * 0.013)) + 'px Arial, sans-serif';
        var lines = [];
        if (context.location_text) {
            lines.push(context.location_text);
        }
        if (context.latitude || context.longitude) {
            lines.push('Lat ' + (context.latitude || '-') + ' | Lon ' + (context.longitude || '-'));
        }
        if (context.gps_accuracy) {
            lines.push('Akurasi ' + context.gps_accuracy + ' m');
        }
        if (shotTime.captured_date || shotTime.captured_time) {
            lines.push('Waktu Jepret ' + shotTime.captured_date + ' ' + shotTime.captured_time);
        }
        if (!lines.length) {
            lines.push('Lokasi dan waktu jepret sedang diproses.');
        }
        var currentY = boxY + 56;
        var maxTextWidth = boxWidth - 36;
        for (var i = 0; i < lines.length; i++) {
            currentY = wrapText(ctx, lines[i], boxX + 18, currentY, maxTextWidth, 18);
        }
        ctx.restore();

        var mimeType = 'image/jpeg';
        var quality = 0.92;
        var blob = await canvasToBlob(canvas, mimeType, quality);
        var fileName = 'gps-camera-' + (wrapper.getAttribute('data-field-name') || 'capture') + '-' + (shotTime.captured_at ? shotTime.captured_at.replace(/[: ]/g, '-') : Date.now()) + '.jpg';
        var fallbackDataUrl = '';
        try {
            fallbackDataUrl = canvas.toDataURL(mimeType, quality);
        } catch (e) {
            fallbackDataUrl = '';
        }

        var file = blobToFile(blob, fileName, mimeType);
        var payload = {
            photo_name: fileName,
            photo_mime: mimeType,
            photo_size: blob && blob.size ? blob.size : 0,
            latitude: context.latitude || '',
            longitude: context.longitude || '',
            gps_accuracy: context.gps_accuracy || '',
            captured_date: shotTime.captured_date || '',
            captured_time: shotTime.captured_time || '',
            captured_at: shotTime.captured_at || '',
            captured_at_server: context.server_at || '',
            location_text: context.location_text || '',
            location_address: context.location_address || ''
        };

        var fileAssigned = file ? setFileInputFile(wrapper, file) : false;
        if (!fileAssigned && fallbackDataUrl) {
            payload.photo_data = fallbackDataUrl;
        }

        applyPayload(wrapper, payload);
        if ((wrapper.getAttribute('data-preview-image') !== '0') && fallbackDataUrl) {
            setPreview(wrapper, fallbackDataUrl);
        }
        setStatus(wrapper, 'Foto, lokasi, dan waktu berhasil ditangkap.');
        setStatus(wrapper, 'Foto siap disimpan dengan watermark.', true);
        resetCameraModal(wrapper);
    }

    function resetCameraModal(wrapper) {
        var state = getState(wrapper);
        stopStream(state);
        var video = wrapper.querySelector('[data-gps-camera-video]');
        if (video) {
            try {
                video.pause();
            } catch (e) {}
            video.srcObject = null;
        }
        var modal = wrapper.querySelector('[data-gps-camera-modal]');
        if (modal) {
            modal.hidden = true;
            modal.style.display = 'none';
        }
    }

    document.addEventListener('click', function(event) {
        var trigger = event.target.closest('[data-gps-camera-trigger]');
        if (trigger) {
            event.preventDefault();
            var wrapper = trigger.closest('[data-gps-camera-component]');
            if (wrapper && !trigger.disabled) {
                openCamera(wrapper);
            }
            return;
        }

        var clearButton = event.target.closest('[data-gps-camera-clear]');
        if (clearButton) {
            event.preventDefault();
            var clearWrapper = clearButton.closest('[data-gps-camera-component]');
            if (clearWrapper && !clearButton.disabled) {
                resetWrapper(clearWrapper);
            }
        }

        var closeButton = event.target.closest('[data-gps-camera-modal-close], [data-gps-camera-modal-cancel]');
        if (closeButton) {
            event.preventDefault();
            var closeWrapper = closeButton.closest('[data-gps-camera-component]');
            if (closeWrapper) {
                resetCameraModal(closeWrapper);
            }
            return;
        }

        var captureButton = event.target.closest('[data-gps-camera-modal-capture]');
        if (captureButton) {
            event.preventDefault();
            var captureWrapper = captureButton.closest('[data-gps-camera-component]');
            if (captureWrapper && !captureButton.disabled) {
                captureFromCamera(captureWrapper).catch(function() {
                    setStatus(captureWrapper, 'Gagal mengambil foto.');
                    setStatus(captureWrapper, 'Gagal mengambil foto.', true);
                });
            }
        }
    });

    document.addEventListener('change', function(event) {
        var fileInput = event.target.closest('[data-gps-camera-file]');
        if (!fileInput) {
            return;
        }
        var wrapper = fileInput.closest('[data-gps-camera-component]');
        if (!wrapper) {
            return;
        }
        var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) {
            resetWrapper(wrapper);
            return;
        }
        (async function() {
            var status = wrapper.querySelector('[data-gps-camera-status]');
            if (status) {
                status.textContent = 'Memproses foto dan GPS...';
            }
            var imageSrc = '';
            try {
                imageSrc = await fileToDataUrl(file);
            } catch (e) {
                imageSrc = '';
            }
            var gps = await captureGps(wrapper);
            var meta = await fetchMetadata(wrapper, gps);
            var shotTime = getClientTimestamp();
            var payload = {
                photo_name: file.name || '',
                photo_mime: file.type || '',
                photo_size: file.size || 0,
                photo_data: imageSrc,
                latitude: meta.latitude || gps.latitude || '',
                longitude: meta.longitude || gps.longitude || '',
                gps_accuracy: meta.gps_accuracy || gps.gps_accuracy || '',
                captured_date: shotTime.captured_date || '',
                captured_time: shotTime.captured_time || '',
                captured_at: shotTime.captured_at || '',
                captured_at_server: meta.server_at || wrapper.getAttribute('data-server-at') || '',
                location_text: meta.location_text || '',
                location_address: meta.location_address || ''
            };
            if ((wrapper.getAttribute('data-preview-image') !== '0') && imageSrc) {
                setPreview(wrapper, imageSrc);
            }
            applyPayload(wrapper, payload);
            if (status) {
                status.textContent = 'Foto dan GPS siap disimpan.';
            }
        })().catch(function() {
            var status = wrapper.querySelector('[data-gps-camera-status]');
            if (status) {
                status.textContent = 'Gagal memproses foto.';
            }
        });
    });

    document.querySelectorAll('[data-gps-camera-component]').forEach(function(wrapper) {
        var payloadInput = wrapper.querySelector('[data-gps-camera-payload]');
        var payload = {};
        if (payloadInput && payloadInput.value) {
            try {
                payload = JSON.parse(payloadInput.value) || {};
            } catch (e) {
                payload = {};
            }
        }
        if (payload.photo_url || payload.photo_path) {
            setPreview(wrapper, payload.photo_url || payload.photo_path);
        }
        setMeta(wrapper, payload);
        setStatus(wrapper, 'Siap mengambil foto.', true);
    });
})();
</script>
HTML;
        }

        return $html;
    }

    public static function isGpsCameraField(array $field): bool
    {
        $type = strtolower(trim((string)($field['type'] ?? $field['field_type'] ?? $field['inputType'] ?? $field['component_type'] ?? '')));
        return $type === 'gps_camera';
    }

    public static function attachAjaxSubmitHandler(string $html): string
    {
        return self::appendCustomFormSubmitCollectorScript($html);
    }

    private static function appendCustomFormSubmitCollectorScript(string $html): string
    {
        $script = self::customFormSubmitCollectorScript();
        if (stripos($html, 'window.__customFormSubmitCollectorInstalled') !== false) {
            return $html;
        }
        if (stripos($html, '</body>') !== false) {
            return (string)preg_replace('/<\/body>/i', $script . '</body>', $html, 1);
        }
        return $html . $script;
    }

    private static function customFormSubmitCollectorScript(): string
    {
        return <<<'HTML'
<script>
(function(){
    if (window.__customFormSubmitCollectorInstalled) return;
    window.__customFormSubmitCollectorInstalled = true;

    function collectInto(form) {
        if (!form || form.tagName !== 'FORM') return;
        Array.prototype.forEach.call(form.querySelectorAll('[data-collected-control="1"]'), function(node) {
            node.remove();
        });

        var externalCheckboxGroups = {};
        var controls = document.querySelectorAll('input[name], select[name], textarea[name]');
        controls.forEach(function(control) {
            if (control.form === form || control.disabled || !control.name) return;
            if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (control.name.charAt(0) === '_' && control.type === 'hidden') return;

            var controlName = control.name;
            var isCheckboxGroup = control.type === 'checkbox' && /\[\]$/.test(controlName);
            if (isCheckboxGroup) {
                if (!externalCheckboxGroups[controlName]) {
                    externalCheckboxGroups[controlName] = [];
                }
                externalCheckboxGroups[controlName].push(control);
                return;
            }

            var alreadyPresent = false;
            Array.prototype.forEach.call(form.elements, function(existing) {
                if (existing.name === controlName) alreadyPresent = true;
            });
            if (alreadyPresent) return;

            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = controlName;
            hidden.value = control.value;
            hidden.setAttribute('data-collected-control', '1');
            form.appendChild(hidden);
        });

        Object.keys(externalCheckboxGroups).forEach(function(groupName) {
            var hasGroupInForm = false;
            Array.prototype.forEach.call(form.elements, function(existing) {
                if (existing.name === groupName) {
                    hasGroupInForm = true;
                }
            });
            if (hasGroupInForm) {
                return;
            }

            externalCheckboxGroups[groupName].forEach(function(control) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = groupName;
                hidden.value = control.value;
                hidden.setAttribute('data-collected-control', '1');
                form.appendChild(hidden);
            });
        });
    }

    function isEmbeddedCustomForm(form) {
        return !!(form && form.querySelector('input[name="_embedded"]'));
    }

    function createSubmitRequestId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'submit_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2);
    }

    function rotateSubmitRequestId(form) {
        var tokenInput = form ? form.querySelector('input[name="_submit_request_id"]') : null;
        if (tokenInput) {
            tokenInput.value = createSubmitRequestId();
        }
    }

    function getCustomFormFields() {
        var fields = [];
        var seen = {};
        var controls = document.querySelectorAll('input[name], select[name], textarea[name]');
        controls.forEach(function(control) {
            var name = String(control.name || '').replace(/\[\]$/, '');
            if (!name || name.charAt(0) === '_' || seen[name]) {
                return;
            }
            seen[name] = true;
            var type = (control.type || '').toLowerCase();
            var tag = String(control.tagName || '').toLowerCase();
            fields.push({
                field: name,
                name: name,
                inputType: tag === 'select' ? 'select' : (type || tag || 'text')
            });
        });
        return fields;
    }

    function collectValuesFromCustomMarkup(rootEl) {
        var values = {};
        var fields = getCustomFormFields();
        fields.forEach(function(field) {
            var fieldName = field.field || field.name || '';
            if (!fieldName) {
                return;
            }
            var inputs = rootEl.querySelectorAll('[data-row-field="' + fieldName + '"], [name="' + fieldName + '"], [name="' + fieldName + '[]"]');
            if (!inputs || !inputs.length) {
                return;
            }

            var firstInput = inputs[0];
            var type = (firstInput.type || '').toLowerCase();
            if (field.inputType === 'checkboxes') {
                values[fieldName] = Array.prototype.slice.call(inputs)
                    .filter(function(input) { return input.checked; })
                    .map(function(input) { return String(input.value || ''); });
                return;
            }
            if (field.inputType === 'radio') {
                var selected = Array.prototype.slice.call(inputs).find(function(input) {
                    return input.checked;
                });
                values[fieldName] = selected ? selected.value : '';
                return;
            }
            if (field.inputType === 'boolean' || field.inputType === 'checkbox' || type === 'checkbox') {
                values[fieldName] = firstInput.checked ? 1 : 0;
                return;
            }
            values[fieldName] = firstInput.value;
        });
        return values;
    }

    function collectDisplayValuesFromCustomMarkup(rootEl) {
        var values = {};
        var fields = getCustomFormFields();
        fields.forEach(function(field) {
            var fieldName = field.field || field.name || '';
            if (!fieldName) {
                return;
            }
            var inputs = rootEl.querySelectorAll('[data-row-field="' + fieldName + '"], [name="' + fieldName + '"], [name="' + fieldName + '[]"]');
            if (!inputs || !inputs.length) {
                return;
            }

            var firstInput = inputs[0];
            var type = (firstInput.type || '').toLowerCase();
            if (field.inputType === 'checkboxes') {
                values[fieldName] = Array.prototype.slice.call(inputs)
                    .filter(function(input) { return input.checked; })
                    .map(function(input) {
                        var label = input && input.closest ? input.closest('label') : null;
                        return label ? String(label.textContent || '').trim() : String(input.value || '');
                    })
                    .filter(function(item) {
                        return String(item || '').trim() !== '';
                    });
                return;
            }
            if (field.inputType === 'radio') {
                var selected = Array.prototype.slice.call(inputs).find(function(input) {
                    return input.checked;
                });
                if (!selected) {
                    values[fieldName] = '';
                    return;
                }
                var selectedLabel = selected.closest ? selected.closest('label') : null;
                values[fieldName] = selectedLabel ? String(selectedLabel.textContent || '').trim() : String(selected.value || '');
                return;
            }
            if (field.inputType === 'boolean' || field.inputType === 'checkbox' || type === 'checkbox') {
                values[fieldName] = firstInput.checked ? 1 : 0;
                return;
            }
            if (firstInput.tagName === 'SELECT') {
                var selectedOption = firstInput.options && firstInput.selectedIndex >= 0 ? firstInput.options[firstInput.selectedIndex] : null;
                values[fieldName] = selectedOption ? String(selectedOption.textContent || selectedOption.value || '').trim() : String(firstInput.value || '');
                return;
            }
            values[fieldName] = firstInput.value;
        });
        return values;
    }

    function showCustomFormAlert(type, message) {
        var existing = document.getElementById('custom-form-submit-alert');
        if (existing) existing.remove();

        var isSuccess = type === 'success';
        var alert = document.createElement('div');
        alert.id = 'custom-form-submit-alert';
        alert.setAttribute('role', 'status');
        alert.style.cssText = [
            'position:fixed',
            'top:22px',
            'right:22px',
            'z-index:2147483647',
            'width:min(420px,calc(100vw - 32px))',
            'background:#ffffff',
            'color:#0f172a',
            'border:1px solid ' + (isSuccess ? '#bbf7d0' : '#fecaca'),
            'border-left:5px solid ' + (isSuccess ? '#22c55e' : '#ef4444'),
            'border-radius:14px',
            'box-shadow:0 24px 60px rgba(15,23,42,.22)',
            'font-family:Inter,Segoe UI,Arial,sans-serif',
            'overflow:hidden',
            'transform:translateY(-8px)',
            'opacity:0',
            'transition:opacity .18s ease, transform .18s ease'
        ].join(';');

        alert.innerHTML =
            '<div style="display:flex;gap:12px;align-items:flex-start;padding:16px 18px;">' +
                '<div style="width:34px;height:34px;border-radius:999px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:' + (isSuccess ? '#dcfce7;color:#15803d' : '#fee2e2;color:#b91c1c') + ';font-weight:800;font-size:18px;">' + (isSuccess ? '&#10003;' : '!') + '</div>' +
                '<div style="min-width:0;flex:1;">' +
                    '<div style="font-size:15px;font-weight:800;margin-bottom:3px;">' + (isSuccess ? 'Data berhasil dikirim' : 'Gagal mengirim data') + '</div>' +
                    '<div style="font-size:13px;line-height:1.5;color:#475569;">' + escapeAlertText(message || (isSuccess ? 'Terima kasih, data sudah tersimpan.' : 'Silakan periksa kembali isian form.')) + '</div>' +
                '</div>' +
                '<button type="button" aria-label="Tutup" style="border:0;background:transparent;color:#94a3b8;font-size:22px;line-height:1;cursor:pointer;padding:0 0 0 8px;">&times;</button>' +
            '</div>';

        alert.querySelector('button').addEventListener('click', function() {
            alert.remove();
        });

        document.body.appendChild(alert);
        requestAnimationFrame(function() {
            alert.style.opacity = '1';
            alert.style.transform = 'translateY(0)';
        });

        clearTimeout(window.__customFormAlertTimer);
        window.__customFormAlertTimer = setTimeout(function() {
            if (!alert.parentNode) return;
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(function() {
                if (alert.parentNode) alert.remove();
            }, 220);
        }, isSuccess ? 4200 : 6500);
    }

    function escapeAlertText(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setSubmitting(form, submitting) {
        if (form) {
            form.dataset.submitting = submitting ? 'true' : 'false';
        }
        var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])');
        buttons.forEach(function(button) {
            if (submitting) {
                button.dataset.originalText = button.tagName === 'INPUT' ? button.value : button.innerHTML;
                if (button.tagName === 'INPUT') button.value = 'Mengirim...';
                else button.innerHTML = 'Mengirim...';
                button.disabled = true;
            } else {
                if (button.dataset.originalText !== undefined) {
                    if (button.tagName === 'INPUT') button.value = button.dataset.originalText;
                    else button.innerHTML = button.dataset.originalText;
                }
                button.disabled = false;
            }
        });
    }

    function submitEmbeddedForm(form) {
        if (!form || form.__customSubmitting || form.dataset.submitting === 'true') return;
        form.__customSubmitting = true;
        collectInto(form);
        setSubmitting(form, true);

        fetch(form.action || window.location.href, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function(response) {
                return response.text().then(function(text) {
                    var data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (error) {
                        data = {
                            success: response.ok,
                            message: response.ok ? 'Data berhasil dikirim.' : text
                        };
                    }
                    if (!response.ok && data.success !== true) {
                        data.success = false;
                    }
                    return data;
                });
            })
            .then(function(data) {
                showCustomFormAlert(data && data.success ? 'success' : 'error', data && data.message ? data.message : '');
                if (data && data.success && !data.duplicate && window.parent && window.parent !== window) {
                    var targetTableInput = form.querySelector('input[name="_datatable_target_table_id"]');
                    var targetTableId = targetTableInput ? parseInt(targetTableInput.value || '0', 10) : 0;
                    var submittedData = collectValuesFromCustomMarkup(form);
                    var submittedDisplayData = collectDisplayValuesFromCustomMarkup(form);
                    window.parent.postMessage({
                        type: 'custom-form-submit-success',
                        source: 'embedded-custom-form',
                        targetTableId: targetTableId > 0 ? targetTableId : null,
                        targetTableName: data && data.table ? data.table : (data && data.tableName ? data.tableName : null),
                        formId: form.getAttribute('data-form-id') ? parseInt(form.getAttribute('data-form-id'), 10) : null,
                        submittedData: submittedData,
                        submittedDisplayData: submittedDisplayData,
                        insertedData: data && data.insertedData ? data.insertedData : null,
                        insertedRowKey: data && data.insertedRowKey ? data.insertedRowKey : null,
                        duplicate: !!(data && data.duplicate)
                    }, '*');
                }
                if (data && data.success && !data.duplicate) {
                    form.reset();
                    rotateSubmitRequestId(form);
                }
            })
            .catch(function(error) {
                showCustomFormAlert('error', error && error.message ? error.message : 'Terjadi kesalahan jaringan.');
            })
            .finally(function() {
                form.__customSubmitting = false;
                setSubmitting(form, false);
            });
    }

    document.addEventListener('submit', function(event) {
        var form = event.target;
        collectInto(form);
        if (!isEmbeddedCustomForm(form)) return;
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
        submitEmbeddedForm(form);
    }, true);

    document.addEventListener('click', function(event) {
        var button = event.target && event.target.closest ? event.target.closest('button, input[type="submit"]') : null;
        if (!button) return;
        var form = button.form || button.closest('form') || document.querySelector('form');
        collectInto(form);
    }, true);

    document.addEventListener('formdata', function(event) {
        var form = event.target;
        collectInto(form);
        Array.prototype.forEach.call(form.elements, function(control) {
            if (!control.name || control.disabled) return;
            if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (!event.formData.has(control.name)) event.formData.append(control.name, control.value);
        });
    });

    if (window.HTMLFormElement && !window.__customFormSubmitPatched) {
        window.__customFormSubmitPatched = true;
        var nativeSubmit = window.HTMLFormElement.prototype.submit;
        window.HTMLFormElement.prototype.submit = function() {
            collectInto(this);
            if (isEmbeddedCustomForm(this)) {
                submitEmbeddedForm(this);
                return;
            }
            return nativeSubmit.call(this);
        };
    }
})();
</script>
HTML;
    }

    private static function resolveFormSourceTokens(string $source, array $fields): string
    {
        foreach ($fields as $index => $field) {
            $name = self::fieldTokenName($field, $index);
            $label = self::escapeTokenValue(self::fieldLabel($field, $index));
            $placeholder = self::escapeTokenValue(self::fieldPlaceholder($field, $index));
            $fieldName = self::escapeTokenValue((string)($field['name'] ?? $name));
            $fieldId = self::escapeTokenValue((string)($field['id'] ?? $name));

            $source = preg_replace('/\{' . preg_quote($name, '/') . '_label\}/', $label, $source) ?? $source;
            $source = preg_replace('/\{' . preg_quote($name, '/') . '_placeholder\}/', $placeholder, $source) ?? $source;
            $source = preg_replace('/\{' . preg_quote($name, '/') . '_name\}/', $fieldName, $source) ?? $source;
            $source = preg_replace('/\{' . preg_quote($name, '/') . '_id\}/', $fieldId, $source) ?? $source;

            $namePattern = preg_quote((string)($field['name'] ?? $name), '/');
            $source = preg_replace(
                '/(<label\b[^>]*>)\{label\}(<\/label>[\s\S]*?<(?:input|select|textarea)\b(?=[^>]*\bname=["\']' . $namePattern . '["\']))/i',
                '$1' . $label . '$2',
                $source
            ) ?? $source;
            $source = preg_replace(
                '/(<(?:input|textarea)\b(?=[^>]*\bname=["\']' . $namePattern . '["\'])(?=[^>]*\bplaceholder=["\'])[^>]*\bplaceholder=["\'])\{placeholder\}(["\'][^>]*>)/i',
                '$1' . $placeholder . '$2',
                $source
            ) ?? $source;
        }

        return $source;
    }

    private static function normalizeCustomFieldNames(string $source, array $fields): string
    {
        if (trim($source) === '' || empty($fields) || stripos($source, '<form') === false) {
            return $source;
        }

        $fieldNames = [];
        foreach ($fields as $index => $field) {
            $fieldNames[] = (string)($field['name'] ?? self::fieldTokenName($field, $index));
        }
        $fieldNames = array_values(array_filter($fieldNames, static function (string $name): bool {
            return $name !== '';
        }));
        if (empty($fieldNames)) {
            return $source;
        }

        preg_match_all('/\bname\s*=\s*([\'"])(.*?)\1/i', $source, $nameMatches);
        $existingNames = $nameMatches[2] ?? [];
        $missingNames = array_filter($fieldNames, static function (string $name) use ($existingNames): bool {
            return !in_array($name, $existingNames, true);
        });
        if (empty($missingNames)) {
            return $source;
        }

        $fieldIndex = 0;
        return preg_replace_callback('/<(input|select|textarea)\b[^>]*>/i', static function (array $matches) use (&$fieldIndex, $fieldNames): string {
            $tag = $matches[0];
            $tagName = strtolower($matches[1] ?? '');
            $type = 'text';
            if ($tagName === 'input' && preg_match('/\btype\s*=\s*([\'"])(.*?)\1/i', $tag, $typeMatch)) {
                $type = strtolower((string)$typeMatch[2]);
            }

            if (in_array($type, ['hidden', 'submit', 'button', 'reset', 'image'], true)) {
                return $tag;
            }

            if (!isset($fieldNames[$fieldIndex])) {
                return $tag;
            }

            $name = Html::encode($fieldNames[$fieldIndex]);
            $fieldIndex++;

            if (preg_match('/\bname\s*=/i', $tag)) {
                return preg_replace('/\bname\s*=\s*([\'"])[^\'"]*\1/i', 'name="' . $name . '"', $tag, 1) ?? $tag;
            }

            return preg_replace('/^<' . preg_quote($tagName, '/') . '\b/i', '<' . $tagName . ' name="' . $name . '"', $tag, 1) ?? $tag;
        }, $source) ?? $source;
    }

    private static function fieldTokenName(array $field, int $index): string
    {
        $name = (string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['column_name'] ?? $field['id'] ?? 'field_' . ($index + 1));
        $name = trim((string)preg_replace('/[^a-zA-Z0-9_]+/', '_', $name), '_');
        return $name !== '' ? $name : 'field_' . ($index + 1);
    }

    private static function fieldLabel(array $field, int $index): string
    {
        $label = (string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? '');
        return $label !== '' ? $label : self::humanizeFieldName(self::fieldTokenName($field, $index));
    }

    private static function fieldPlaceholder(array $field, int $index): string
    {
        $placeholder = (string)($field['placeholder'] ?? '');
        if ($placeholder !== '') {
            return $placeholder;
        }

        $type = (string)($field['type'] ?? $field['inputType'] ?? 'text');
        if (in_array($type, ['date', 'time', 'datetime-local'], true)) {
            return '';
        }

        $label = strtolower(self::fieldLabel($field, $index));
        return $type === 'select' ? 'Pilih ' . $label : 'Masukkan ' . $label;
    }

    private static function humanizeFieldName(string $value): string
    {
        $value = trim((string)preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $value)));
        return ucwords(strtolower($value));
    }

    private static function escapeTokenValue(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private static function hydrateCustomDropdownOptions(string $html, array $fields): string
    {
        if (trim($html) === '' || empty($fields) || stripos($html, '<select') === false) {
            return $html;
        }

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldName = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
            $fieldType = strtolower(trim((string)($field['type'] ?? $field['field_type'] ?? '')));
            if ($fieldName === '' || $fieldType !== 'select') {
                continue;
            }

            $options = self::resolveCustomDropdownOptions($field);
            if (empty($options)) {
                continue;
            }

            $optionHtml = '<option value="">' . htmlspecialchars('Pilih...', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
            foreach ($options as $option) {
                if (!is_array($option)) {
                    continue;
                }

                $value = trim((string)($option['value'] ?? ''));
                if ($value === '') {
                    continue;
                }

                $label = trim((string)($option['label'] ?? $value));
                $optionHtml .= '<option value="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($label !== '' ? $label : $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
            }

            $pattern = '/(<select\b(?=[^>]*\bname=["\']' . preg_quote($fieldName, '/') . '["\'])[^>]*>)([\s\S]*?)(<\/select>)/i';
            $html = preg_replace_callback($pattern, static function (array $matches) use ($optionHtml): string {
                return ($matches[1] ?? '') . $optionHtml . ($matches[3] ?? '</select>');
            }, $html) ?? $html;
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<int, array{value:string,label:string}>
     */
    private static function resolveCustomDropdownOptions(array $field): array
    {
        $options = [];
        foreach (['fk_options', 'options'] as $sourceKey) {
            $source = $field[$sourceKey] ?? null;
            if (is_string($source)) {
                $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $source) ?: []));
                foreach ($lines as $line) {
                    $options[] = ['value' => $line, 'label' => $line];
                }
            } elseif (is_array($source)) {
                foreach ($source as $option) {
                    if (!is_array($option)) {
                        continue;
                    }
                    $value = trim((string)($option['value'] ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    $label = trim((string)($option['label'] ?? $value));
                    $options[] = ['value' => $value, 'label' => $label !== '' ? $label : $value];
                }
            }

            if (!empty($options)) {
                return $options;
            }
        }

        return [];
    }
}

