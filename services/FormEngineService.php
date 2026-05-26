<?php

namespace app\services;

use app\models\MasterForm;
use app\models\DbTableColumn;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
use app\helpers\FormSystemFieldHelper;
use app\components\SystemFieldService;
use yii\helpers\Json;

class FormEngineService
{
    public function getResolvedFormSchema(MasterForm $form): array
    {
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
            $resolvedField = $this->normalizeResolvedField([
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
            ], $form, (int)$field->sort_order, $targetSchema);

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
        $resolvedField['is_foreign_key'] = !empty($fieldData['is_foreign_key']) || !empty($fieldData['fk_referenced_table']) || !empty($fieldData['foreign_key_table']);

        if ($resolvedField['is_foreign_key']) {
            $resolvedField['fk_referenced_table'] = (string)($fieldData['fk_referenced_table'] ?? $fieldData['foreign_key_table'] ?? $fieldData['referenced_table_name'] ?? '');
            $resolvedField['fk_referenced_column'] = (string)($fieldData['fk_referenced_column'] ?? $fieldData['foreign_key_column'] ?? $fieldData['referenced_column_name'] ?? '');
            $resolvedField['fk_display_column'] = (string)($fieldData['fk_display_column'] ?? $fieldData['label_column'] ?? $resolvedField['fk_display_column'] ?? '');
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
        $candidates = [];
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
            $fieldData['relation_config']['local_column'] ?? null,
            $fieldData['relation_config']['source_column'] ?? null,
            $fieldData['relation_config']['column_name'] ?? null,
            $fieldData['relation_config']['original_column'] ?? null,
            $fieldData['relation_config']['field_name'] ?? null,
            $fieldData['relation_config']['field_key'] ?? null,
            $fieldData['label'] ?? null,
            $fieldData['field_label'] ?? null,
            $fieldData['labelText'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $candidates[] = trim($candidate);
            }
        }

        if ($sourceColumn !== null && trim((string)$sourceColumn->name) !== '') {
            $candidates[] = (string)$sourceColumn->name;
        }

        $fallback = 'field_' . ($index + 1);
        if ($schema === null || empty($schema->columns)) {
            return $this->chooseBestFieldNameCandidate($candidates, [$fallback => $fallback]) ?: $fallback;
        }

        $schemaLookup = $this->buildSchemaNameLookup($schema);
        $resolved = $this->chooseBestFieldNameCandidate($candidates, $schemaLookup);
        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        return $fallback;
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

                $score = 0.0;
                if ($normalizedAlias === $normalizedCandidate) {
                    $score = 100.0;
                } elseif (str_contains($normalizedAlias, $normalizedCandidate) || str_contains($normalizedCandidate, $normalizedAlias)) {
                    $score = 80.0;
                } else {
                    $aliasTokens = array_values(array_filter(explode('_', $normalizedAlias)));
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
}

