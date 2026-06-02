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
                $field['inputType'] = 'select';
                $field['type'] = 'select';
                $field['field_type'] = 'select';
                $field['is_foreign_key'] = true;
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
        $relationConfig = self::resolveRelationConfig($field);
        $isForeignKey = self::isRelationField($field);
        if ($isForeignKey) {
            $field['is_foreign_key'] = true;
            $field['type'] = 'select';
            $field['field_type'] = 'select';
            $field['inputType'] = 'select';
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

            $field['options'] = $options;
            $field['fk_options'] = $options;
            $field['inputType'] = $isForeignKey ? 'select' : ($field['inputType'] ?? $type);
            $field['type'] = $isForeignKey ? 'select' : ($field['type'] ?? $type);
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
            ]), static fn($value): bool => $value !== null && $value !== '');
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve dynamic dropdown options: ' . $e->getMessage(), 'form-render');
        }

        return $field;
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
            $field['type'] = 'select';
            $field['field_type'] = 'select';
            $field['inputType'] = 'select';
            if (!empty($relationConfig)) {
                $field['relation_config'] = $relationConfig;
            }
        }

        return $field;
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
        $nonFallback = array_values(array_filter($candidates, static fn(string $v): bool => $v !== '' && !self::looksLikeFallbackFieldName($v)));
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
        ], static fn($value): bool => $value !== null && $value !== '');
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
        ]), static fn($value): bool => $value !== null && $value !== '');
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
        var controls = document.querySelectorAll('input[name], select[name], textarea[name]');
        controls.forEach(function(control) {
            if (control.form === form || control.disabled || !control.name) return;
            if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
            if (control.name.charAt(0) === '_' && control.type === 'hidden') return;
            var alreadyPresent = false;
            Array.prototype.forEach.call(form.elements, function(existing) {
                if (existing.name === control.name) alreadyPresent = true;
            });
            if (alreadyPresent) return;
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = control.name;
            hidden.value = control.value;
            form.appendChild(hidden);
        });
    }

    function isEmbeddedCustomForm(form) {
        return !!(form && form.querySelector('input[name="_embedded"]'));
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
        if (!form || form.__customSubmitting) return;
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
                        formId: form.getAttribute('data-form-id') ? parseInt(form.getAttribute('data-form-id'), 10) : null,
                        submittedData: submittedData,
                        submittedDisplayData: submittedDisplayData,
                        insertedData: data && data.insertedData ? data.insertedData : null,
                        insertedRowKey: data && data.insertedRowKey ? data.insertedRowKey : null,
                        duplicate: !!(data && data.duplicate)
                    }, '*');
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
        $fieldNames = array_values(array_filter($fieldNames, static fn(string $name): bool => $name !== ''));
        if (empty($fieldNames)) {
            return $source;
        }

        preg_match_all('/\bname\s*=\s*([\'"])(.*?)\1/i', $source, $nameMatches);
        $existingNames = $nameMatches[2] ?? [];
        $missingNames = array_filter($fieldNames, static fn(string $name): bool => !in_array($name, $existingNames, true));
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

