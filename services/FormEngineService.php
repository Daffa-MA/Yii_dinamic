<?php

namespace app\services;

use app\models\MasterForm;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
use app\helpers\FormSystemFieldHelper;
use app\components\ActiveDatabaseContext;
use app\components\DatabaseSchemaInitializer;
use app\components\SystemFieldService;
use Yii;
use yii\helpers\Json;

class FormEngineService
{
    public function getResolvedFormSchema(MasterForm $form): array
    {
        if (Yii::$app instanceof \yii\web\Application || Yii::$app->has('session', true)) {
            (new ActiveDatabaseContext())->resolveAndApply();
            if (DatabaseSchemaInitializer::ensureMasterFormStructure(Yii::$app->db)) {
                Yii::$app->db->schema->refresh();
            }
        }

        $fields = $form->getFields()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $layout = $form->getActiveLayout()->one();
        $autoSynced = false;
        $targetTable = $this->resolveTargetTable($form);
        $targetSchema = $targetTable !== null ? Yii::$app->db->schema->getTableSchema((string)$targetTable->name, true) : null;

        if (empty($fields)) {
            $this->syncLegacyToRelational($form);
            $fields = $form->getFields()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
            $layout = $form->getActiveLayout()->one();
            $autoSynced = true;
        }

        $fieldRows = [];
        foreach ($fields as $field) {
            $settings = $this->decodeJson($field->field_settings);
            $baseFieldData = [
                'id' => $field->id,
                'name' => $field->field_name ?: $field->field_key,
                'field_name' => $field->field_name ?: $field->field_key,
                'field_key' => $field->field_key ?: $field->field_name,
                'column_name' => $field->field_name ?: $field->field_key,
                'label' => $field->field_label,
                'field_label' => $field->field_label,
                'type' => $field->field_type,
                'field_type' => $field->field_type,
                'component_type' => $field->component_type,
                'inputType' => $field->component_type ?: $field->field_type,
                'required' => (bool)$field->is_required,
                'placeholder' => $field->placeholder,
                'default_value' => $field->default_value,
                'dropdown_source' => $field->dropdown_source,
                'fk_referenced_table' => $field->foreign_key_table,
                'fk_display_column' => $field->foreign_key_column,
                'field_config' => $settings,
                'field_settings' => $settings,
            ];
            $resolvedField = $this->normalizeResolvedField(array_merge($settings, $baseFieldData), $form, (int)$field->sort_order, $targetSchema);

            if ($this->isSystemFieldForForm($resolvedField, $form)) {
                $field->delete();
                $autoSynced = true;
                continue;
            }

            $fieldRows[] = array_merge($settings, $resolvedField, [
                'id' => $field->id,
                'name' => $resolvedField['name'],
                'field_name' => $resolvedField['field_name'],
                'field_key' => $resolvedField['field_key'],
                'column_name' => $resolvedField['column_name'],
                'label' => $resolvedField['label'],
                'field_label' => $resolvedField['field_label'],
                'type' => $resolvedField['type'],
                'inputType' => $resolvedField['inputType'],
                'required' => (bool)$field->is_required,
                'placeholder' => $resolvedField['placeholder'],
                'default_value' => $resolvedField['default_value'],
                'is_foreign_key' => !empty($field->foreign_key_table) || !empty($resolvedField['fk_referenced_table']),
                'fk_referenced_table' => $resolvedField['fk_referenced_table'],
                'fk_display_column' => $resolvedField['fk_display_column'],
            ]);
        }

        return [
            'fields' => $fieldRows,
            'layout' => $layout,
            'autoSynced' => $autoSynced,
        ];
    }

    public function syncLegacyToRelational(MasterForm $form): void
    {
        $formData = FormSystemFieldHelper::filterBuilderData($form->getFormDataArray());
        $fields = isset($formData['fields']) && is_array($formData['fields']) ? $formData['fields'] : (is_array($formData) ? $formData : []);
        $fields = array_values(array_filter($fields, fn($field) => is_array($field) && !$this->isSystemFieldForForm($field, $form)));
        $targetTable = $this->resolveTargetTable($form);
        $targetSchema = $targetTable !== null ? Yii::$app->db->schema->getTableSchema((string)$targetTable->name, true) : null;
        if (empty($fields)) {
            return;
        }

        MasterFormField::deleteAll(['form_id' => $form->id]);
        MasterFormLayout::deleteAll(['form_id' => $form->id]);

        foreach ($fields as $index => $fieldData) {
            if (!is_array($fieldData)) {
                continue;
            }

            $resolvedField = $this->normalizeResolvedField($fieldData, $form, (int)$index, $targetSchema);
            if ($this->isSystemFieldForForm($resolvedField, $form)) {
                continue;
            }

            $fieldType = (string)($fieldData['type'] ?? $fieldData['field_type'] ?? 'text');

            $field = new MasterFormField();
            $field->form_id = (int)$form->id;
            $field->field_key = (string)$resolvedField['field_key'];
            $field->field_name = (string)$resolvedField['field_name'];
            $field->field_label = (string)$resolvedField['field_label'];
            $field->field_type = (string)$resolvedField['type'];
            $field->component_type = (string)($resolvedField['component_type'] ?? $fieldData['component_type'] ?? $fieldType);
            $field->is_required = !empty($fieldData['required']) ? 1 : 0;
            $field->placeholder = (string)($resolvedField['placeholder'] ?? '');
            $field->default_value = isset($resolvedField['default_value']) ? (string)$resolvedField['default_value'] : null;
            $field->dropdown_source = (string)($resolvedField['dropdown_source'] ?? '');
            $field->foreign_key_table = isset($resolvedField['fk_referenced_table']) ? (string)$resolvedField['fk_referenced_table'] : null;
            $field->foreign_key_column = isset($resolvedField['fk_display_column']) ? (string)$resolvedField['fk_display_column'] : null;
            $field->validation_rules = Json::encode(['required' => !empty($fieldData['required'])]);
            $field->field_config = Json::encode(array_merge($fieldData, $resolvedField));
            $field->field_settings = Json::encode(array_merge($fieldData, $resolvedField));
            $field->sort_order = (int)$index;
            $field->save(false);
        }

        $layout = new MasterFormLayout();
        $layout->form_id = (int)$form->id;
        $layout->layout_name = $form->form_name . ' Layout';
        $layout->layout_type = (string)($form->form_type ?: 'builder');
        $layout->layout_json = Json::encode(['builder' => $formData]);
        $layout->custom_html = '';
        $layout->custom_css = '';
        $layout->custom_js = '';
        if ($layout->hasAttribute('use_custom_code')) {
            $layout->use_custom_code = 0;
        }
        $layout->builder_state = Json::encode($formData);
        $layout->is_default = 1;
        $layout->sort_order = 0;
        $layout->save(false);

        $form->custom_code_mode = 0;
        $form->save(false, ['custom_code_mode']);
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        $decoded = Json::decode($value);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveTargetTable(MasterForm $form): ?DbTable
    {
        $tableId = 0;
        if ($form->hasAttribute('db_table_id')) {
            $tableId = (int)$form->getAttribute('db_table_id');
        }
        if ($tableId <= 0 && $form->table_id > 0) {
            $tableId = (int)$form->table_id;
        }
        if ($tableId <= 0) {
            return null;
        }

        return DbTable::findOne(['id' => $tableId]);
    }

    /**
     * @param array<string, mixed> $fieldData
     * @param \yii\db\TableSchema|null $schema
     * @return array<string, mixed>
     */
    private function normalizeResolvedField(array $fieldData, MasterForm $form, int $index, $schema = null): array
    {
        $sourceColumn = null;
        $sourceColumnId = (int)($fieldData['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = DbTableColumn::findOne($sourceColumnId);
        }

        $resolvedName = $this->resolveCanonicalFieldName($fieldData, $index, $schema, $sourceColumn);
        if ($sourceColumn === null) {
            $sourceColumn = $this->findTargetColumn($form, $resolvedName);
        }
        if (($sourceColumn === null || $resolvedName === '' || $this->looksLikeFallbackFieldName($resolvedName))
            && $schema !== null
            && (!empty($fieldData['is_foreign_key']) || !empty($fieldData['fk_referenced_table'])
                || !empty($fieldData['foreign_key_table']) || !empty($fieldData['referenced_table_name'])
                || !empty($this->extractRelationConfig($fieldData)))
        ) {
            $fkColumn = $this->resolveFkColumnFromRelationConfig($fieldData, $schema, $form);
            if ($fkColumn !== null) {
                $resolvedName = $fkColumn;
                if ($sourceColumn === null) {
                    $sourceColumn = $this->findTargetColumn($form, $resolvedName);
                }
            }
        }
        $resolvedLabel = $this->resolveCanonicalFieldLabel($fieldData, $resolvedName, $sourceColumn);
        $resolvedType = (string)($fieldData['field_type'] ?? $fieldData['type'] ?? 'text');
        $componentType = (string)($fieldData['component_type'] ?? $fieldData['inputType'] ?? $resolvedType);
        $resolvedField = $fieldData;
        $resolvedField['original_name'] = trim((string)($fieldData['original_name'] ?? $fieldData['name'] ?? ''));
        $resolvedField['resolved_name'] = $resolvedName;
        $resolvedField['resolved_column_name'] = $resolvedName;
        $resolvedField['resolved_label'] = $resolvedLabel;
        $resolvedField['name'] = $resolvedName;
        $resolvedField['field_name'] = $resolvedName;
        $resolvedField['field_key'] = $resolvedName;
        $resolvedField['column_name'] = $resolvedName;
        $resolvedField['label'] = $resolvedLabel;
        $resolvedField['field_label'] = $resolvedLabel;
        $resolvedField['type'] = $resolvedType;
        $resolvedField['field_type'] = $resolvedType;
        $resolvedField['component_type'] = $componentType;
        $resolvedField['inputType'] = $componentType;
        $resolvedField['source_column_name'] = $sourceColumn !== null ? (string)$sourceColumn->name : (string)($fieldData['source_column_name'] ?? '');
        $resolvedField['source_column_label'] = $sourceColumn !== null ? (string)($sourceColumn->label ?? $sourceColumn->name) : (string)($fieldData['source_column_label'] ?? '');
        $resolvedField['source_column_type'] = $sourceColumn !== null ? (string)($sourceColumn->type ?? '') : (string)($fieldData['source_column_type'] ?? '');
        $relationConfig = $this->extractRelationConfig($fieldData);
        $isMetadataFk = $sourceColumn !== null && $sourceColumn->hasAttribute('is_foreign_key') && (bool)$sourceColumn->getAttribute('is_foreign_key');
        $resolvedField['is_foreign_key'] = !empty($fieldData['is_foreign_key']) || !empty($fieldData['fk_referenced_table']) || !empty($fieldData['foreign_key_table']) || $isMetadataFk || !empty($relationConfig);

        if ($resolvedField['is_foreign_key']) {
            $referencedTable = (string)($fieldData['fk_referenced_table'] ?? $fieldData['foreign_key_table'] ?? $fieldData['referenced_table_name'] ?? $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? '');
            $referencedColumn = (string)($fieldData['fk_referenced_column'] ?? $fieldData['referenced_column_name'] ?? $relationConfig['referenced_column'] ?? $relationConfig['referenced_column_name'] ?? $relationConfig['value_column'] ?? '');
            $displayColumn = (string)($fieldData['fk_display_column'] ?? $fieldData['label_column'] ?? $relationConfig['display_column'] ?? $relationConfig['display_column_name'] ?? '');

            if ($sourceColumn !== null) {
                if ($referencedTable === '' && $sourceColumn->hasAttribute('referenced_table_name')) {
                    $referencedTable = (string)$sourceColumn->getAttribute('referenced_table_name');
                }
                if ($referencedColumn === '' && $sourceColumn->hasAttribute('referenced_column_name')) {
                    $referencedColumn = (string)$sourceColumn->getAttribute('referenced_column_name');
                }
            }

            if ($referencedColumn === '' && $referencedTable !== '') {
                $referencedColumn = $this->resolveReferencedValueColumn($referencedTable);
            }
            if ($displayColumn === '' && $referencedTable !== '') {
                $displayColumn = $this->resolveReferencedDisplayColumn($referencedTable, $referencedColumn);
            }

            $resolvedField['fk_referenced_table'] = $referencedTable;
            $resolvedField['fk_referenced_column'] = $referencedColumn;
            $resolvedField['fk_display_column'] = $displayColumn;
            $resolvedField['value_column'] = $referencedColumn;
            $resolvedField['display_column'] = $displayColumn;
            $resolvedField['relation_config'] = array_filter(array_merge($relationConfig, [
                'local_column' => $resolvedName,
                'source_column' => $resolvedName,
                'column_name' => $resolvedName,
                'referenced_table' => $referencedTable,
                'referenced_table_name' => $referencedTable,
                'referenced_column' => $referencedColumn,
                'referenced_column_name' => $referencedColumn,
                'value_column' => $referencedColumn,
                'display_column' => $displayColumn,
            ]), static fn($value): bool => $value !== null && $value !== '');
        }

        return $resolvedField;
    }

    /**
     * @param array<string, mixed> $fieldData
     * @param \yii\db\TableSchema|null $schema
     * @param DbTableColumn|null $sourceColumn
     */
    private function resolveCanonicalFieldName(array $fieldData, int $index, $schema = null, ?DbTableColumn $sourceColumn = null): string
    {
        $relationConfig = $this->extractRelationConfig($fieldData);
        $identityCandidates = [];
        $labelCandidates = [];
        foreach ([
            $fieldData['name'] ?? null,
            $fieldData['field_name'] ?? null,
            $fieldData['field_key'] ?? null,
            $fieldData['column_name'] ?? null,
            $fieldData['original_column'] ?? null,
            $fieldData['local_column'] ?? null,
            $fieldData['source_column'] ?? null,
            $fieldData['source_column_name'] ?? null,
            $fieldData['relation_target_column'] ?? null,
            $fieldData['relation_value_column'] ?? null,
            $relationConfig['local_column'] ?? null,
            $relationConfig['source_column'] ?? null,
            $relationConfig['column_name'] ?? null,
            $relationConfig['original_column'] ?? null,
            $relationConfig['field_name'] ?? null,
            $relationConfig['field_key'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $identityCandidates[] = trim($candidate);
            }
        }

        if ($sourceColumn !== null && trim((string)$sourceColumn->name) !== '') {
            array_unshift($identityCandidates, (string)$sourceColumn->name);
        }

        foreach ([
            $fieldData['label'] ?? null,
            $fieldData['field_label'] ?? null,
            $fieldData['labelText'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $labelCandidates[] = trim($candidate);
            }
        }

        $fallback = 'field_' . ($index + 1);
        if ($schema === null || empty($schema->columns)) {
            $schemaLikeCandidate = $this->chooseSchemaLikeFieldNameCandidate($identityCandidates);
            if ($schemaLikeCandidate !== null && $schemaLikeCandidate !== '') {
                return $schemaLikeCandidate;
            }

            return $this->chooseBestFieldNameCandidate($identityCandidates, [$fallback => $fallback]) ?: $fallback;
        }

        $schemaLookup = $this->buildSchemaNameLookup($schema);
        $resolved = $this->chooseBestFieldNameCandidate($identityCandidates, $schemaLookup);
        if (($resolved === null || $resolved === '') && !empty($labelCandidates)) {
            $resolved = $this->chooseBestFieldNameCandidate($labelCandidates, $schemaLookup);
        }
        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $fieldData
     * @return array<string, mixed>
     */
    private function extractRelationConfig(array $fieldData): array
    {
        foreach (['relation_config', 'relationConfig', 'relation'] as $key) {
            if (!array_key_exists($key, $fieldData)) {
                continue;
            }
            $relationConfig = $fieldData[$key];
            if (is_array($relationConfig)) {
                return $relationConfig;
            }
            if (is_string($relationConfig) && trim($relationConfig) !== '') {
                $decoded = Json::decode($relationConfig);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    private function resolveCanonicalFieldLabel(array $fieldData, string $fieldName, ?DbTableColumn $sourceColumn = null): string
    {
        $label = trim((string)($fieldData['label'] ?? $fieldData['field_label'] ?? $fieldData['labelText'] ?? ''));
        if ($sourceColumn !== null) {
            $sourceLabel = trim((string)($sourceColumn->label ?? ''));
            if ($sourceLabel !== '' && ($label === '' || !$this->labelMatchesFieldName($label, $fieldName))) {
                $label = $sourceLabel;
            }
        }

        if ($label === '' || !$this->labelMatchesFieldName($label, $fieldName)) {
            $label = $fieldName !== '' ? ucwords(str_replace('_', ' ', $fieldName)) : 'Field';
        }

        return $label;
    }

    /**
     * @return array<string, string>
     */
    private function buildSchemaNameLookup($schema): array
    {
        $lookup = [];
        foreach ($schema->columns as $columnName => $column) {
            $columnName = (string)$columnName;
            $aliases = [
                $columnName,
                $this->normalizeKey($columnName),
                $this->normalizeKey(ucwords(str_replace('_', ' ', $columnName))),
            ];

            $columnLabel = trim((string)($column->label ?? $column->comment ?? ''));
            if ($columnLabel !== '') {
                $aliases[] = $columnLabel;
                $aliases[] = $this->normalizeKey($columnLabel);
            }

            if (substr($this->normalizeKey($columnName), -3) === '_id') {
                $aliases[] = substr($this->normalizeKey($columnName), 0, -3);
            }

            foreach ($aliases as $alias) {
                $alias = $this->normalizeKey((string)$alias);
                if ($alias === '') {
                    continue;
                }
                if (!isset($lookup[$alias])) {
                    $lookup[$alias] = $columnName;
                }
            }
        }

        return $lookup;
    }

    /**
     * @param array<int, string> $candidates
     * @param array<string, string> $lookup
     */
    private function chooseBestFieldNameCandidate(array $candidates, array $lookup): ?string
    {
        $bestMatch = null;
        $bestScore = 0.0;
        foreach (array_values(array_unique(array_filter(array_map('trim', $candidates)))) as $candidate) {
            $normalizedCandidate = $this->normalizeKey($candidate);
            if ($normalizedCandidate === '') {
                continue;
            }

            if (isset($lookup[$candidate])) {
                return $lookup[$candidate];
            }
            if (isset($lookup[$normalizedCandidate])) {
                return $lookup[$normalizedCandidate];
            }

            $candidateTokens = array_values(array_filter(explode('_', $normalizedCandidate)));
            foreach ($lookup as $alias => $columnName) {
                $normalizedAlias = $this->normalizeKey($alias);
                if ($normalizedAlias === '') {
                    continue;
                }
                $aliasTokens = array_values(array_filter(explode('_', $normalizedAlias)));

                $score = 0.0;
                if ($normalizedAlias === $normalizedCandidate) {
                    $score = 100.0;
                } elseif (count($candidateTokens) > 1 && count($aliasTokens) > 1 && empty(array_diff($candidateTokens, $aliasTokens)) && empty(array_diff($aliasTokens, $candidateTokens))) {
                    $score = 98.0;
                } elseif (
                    $normalizedAlias !== 'id'
                    && $normalizedCandidate !== 'id'
                    && (str_contains($normalizedAlias, $normalizedCandidate) || str_contains($normalizedCandidate, $normalizedAlias))
                ) {
                    $score = 80.0;
                } else {
                    $intersection = array_intersect($candidateTokens, $aliasTokens);
                    $union = array_unique(array_merge($candidateTokens, $aliasTokens));
                    if (!empty($union)) {
                        $score = (count($intersection) / count($union)) * 70.0;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $columnName;
                }
            }
        }

        return $bestScore >= 45.0 ? $bestMatch : null;
    }

    /**
     * @param array<int, string> $candidates
     */
    private function chooseSchemaLikeFieldNameCandidate(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate === '') {
                continue;
            }

            $normalized = strtolower($candidate);
            if (preg_match('/^[a-z][a-z0-9_]*$/', $normalized) !== 1) {
                continue;
            }

            return $normalized;
        }

        return null;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $value), '_'));
    }

    private function labelMatchesFieldName(string $label, string $fieldName): bool
    {
        $labelTokens = array_values(array_filter(explode('_', $this->normalizeKey($label))));
        $fieldTokens = array_values(array_filter(explode('_', $this->normalizeKey($fieldName))));
        if (empty($labelTokens) || empty($fieldTokens)) {
            return false;
        }

        return count(array_intersect($labelTokens, $fieldTokens)) > 0;
    }

    private function findTargetColumn(MasterForm $form, string $columnName): ?DbTableColumn
    {
        $tableId = $form->hasAttribute('db_table_id') ? (int)$form->getAttribute('db_table_id') : 0;
        if ($tableId <= 0) {
            $tableId = (int)$form->table_id;
        }
        if ($tableId <= 0 || trim($columnName) === '') {
            return null;
        }

        return DbTableColumn::find()
            ->where(['table_id' => $tableId, 'name' => $columnName])
            ->one();
    }

    private function resolveReferencedValueColumn(string $tableName): string
    {
        try {
            $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        } catch (\Throwable $e) {
            return '';
        }
        if ($schema === null || empty($schema->columns)) {
            return '';
        }

        return !empty($schema->primaryKey) ? (string)$schema->primaryKey[0] : (string)array_key_first($schema->columns);
    }

    private function resolveReferencedDisplayColumn(string $tableName, string $valueColumn): string
    {
        try {
            $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        } catch (\Throwable $e) {
            return $valueColumn;
        }
        if ($schema === null || empty($schema->columns)) {
            return $valueColumn;
        }

        foreach (['name', 'title', 'label', 'slug', 'username', 'email', 'form_name', 'table_name'] as $candidate) {
            if ($candidate !== $valueColumn && isset($schema->columns[$candidate])) {
                return $candidate;
            }
        }

        return $valueColumn !== '' ? $valueColumn : (string)array_key_first($schema->columns);
    }

    private function isSystemFieldForForm(array $fieldData, MasterForm $form): bool
    {
        if (FormSystemFieldHelper::isSystemFieldData($fieldData)) {
            return true;
        }

        $sourceColumnId = (int)($fieldData['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = DbTableColumn::findOne($sourceColumnId);
            if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                return true;
            }
        }

        if (!empty($form->table_id)) {
            $fieldName = $fieldData['name'] ?? $fieldData['field_name'] ?? $fieldData['field_key'] ?? '';
            if ($fieldName !== '') {
                $sourceColumn = DbTableColumn::find()
                    ->where(['table_id' => (int)$form->table_id, 'name' => (string)$fieldName])
                    ->one();
                if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function looksLikeFallbackFieldName(string $name): bool
    {
        $normalized = strtolower(trim($name));
        return preg_match('/^field[\s_-]*\d+$/', $normalized) === 1
            || preg_match('/^kolom[\s_-]*\d+$/', $normalized) === 1;
    }

    private function resolveFkColumnFromRelationConfig(array $field, $targetSchema, MasterForm $form): ?string
    {
        $relationConfig = $this->extractRelationConfig($field);
        $referencedTable = $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? $field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? $field['referenced_table_name'] ?? null;
        if (empty($referencedTable) || $targetSchema === null) {
            return null;
        }
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
            $field['name'] ?? null,
            $field['field_name'] ?? null,
            $field['column_name'] ?? null,
            $field['relation_target_column'] ?? null,
        ]));
        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeKey((string)$candidate);
            if (isset($targetSchema->columns[$candidate])) {
                return $candidate;
            }
            if (isset($targetSchema->columns[$normalized])) {
                return $normalized;
            }
            foreach ($targetSchema->columns as $colName => $col) {
                if ($this->normalizeKey($colName) === $normalized) {
                    return $colName;
                }
            }
        }
        $tableId = 0;
        if ($form->hasAttribute('db_table_id')) {
            $tableId = (int)$form->getAttribute('db_table_id');
        }
        if ($tableId <= 0) {
            $tableId = (int)$form->table_id;
        }
        if ($tableId > 0) {
            $fkColumn = DbTableColumn::find()
                ->where(['table_id' => $tableId, 'is_foreign_key' => true])
                ->andWhere(['referenced_table_name' => $referencedTable])
                ->one();
            if ($fkColumn !== null && !empty($fkColumn->name)) {
                return $fkColumn->name;
            }
        }
        $refTableNormalized = str_replace(['_', '-'], '', strtolower($referencedTable));
        foreach ($targetSchema->columns as $colName => $col) {
            $colNormalized = str_replace(['_', '-'], '', strtolower($colName));
            if ($colNormalized === $refTableNormalized . 'id' || $colNormalized === $refTableNormalized) {
                return $colName;
            }
            if (substr($colName, -3) === '_id') {
                $baseName = substr($colName, 0, -3);
                if (str_replace(['_', '-'], '', strtolower($baseName)) === $refTableNormalized) {
                    return $colName;
                }
            }
        }
        return null;
    }
}

