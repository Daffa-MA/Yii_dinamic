<?php

namespace app\services;

use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterForm;
use Yii;

class DynamicFormBehaviorDetector
{
    public function detect(MasterForm $form, array $fields): array
    {
        $table = $this->targetTable($form);
        if ($table === null) {
            return [];
        }

        $targetTable = (string)$table->name;
        $fieldMap = $this->fieldMap($fields);
        $fieldNames = array_keys($fieldMap);
        $fkFields = [];
        foreach ($fieldNames as $fieldName) {
            $relation = $this->relation($targetTable, $fieldName);
            if ($relation !== null) {
                $fkFields[$fieldName] = $relation;
            }
        }
        if (empty($fkFields)) {
            return $this->detectInsertOnlyBehavior($fields);
        }

        $autoFillRules = [];
        $detailCard = null;
        $primaryTrigger = array_key_first($fkFields);
        foreach ($fkFields as $triggerField => $relation) {
            $directColumns = $this->tableColumns((string)$relation['table']);
            $nestedRelations = $this->tableRelations((string)$relation['table']);

            foreach ($fieldNames as $targetField) {
                if ($targetField === $triggerField) {
                    continue;
                }

                if (isset($directColumns[$targetField])) {
                    $autoFillRules[] = [
                        'trigger_field' => $triggerField,
                        'target_field' => $targetField,
                        'source_path' => $triggerField . '.' . $targetField,
                    ];
                    continue;
                }

                foreach ($nestedRelations as $nestedField => $nestedRelation) {
                    $nestedColumns = $this->tableColumns((string)$nestedRelation['table']);
                    $matchedColumn = $this->bestColumnMatch($targetField, array_keys($nestedColumns));
                    if ($matchedColumn === null) {
                        continue;
                    }
                    $autoFillRules[] = [
                        'trigger_field' => $triggerField,
                        'target_field' => $targetField,
                        'source_path' => $triggerField . '.' . $nestedField . '.' . $matchedColumn,
                        'fill_when_empty' => $this->isLikelyUserEditable($fieldMap[$targetField]),
                    ];
                    break;
                }
            }

            if ($detailCard === null) {
                $detailItems = $this->detailItems($triggerField, (string)$relation['table'], $nestedRelations);
                if (!empty($detailItems)) {
                    $detailCard = [
                        'enabled' => true,
                        'trigger_field' => $triggerField,
                        'title' => 'Detail',
                        'items' => $detailItems,
                    ];
                }
            }
        }

        $behavior = $this->detectInsertOnlyBehavior($fields);
        if (!empty($autoFillRules)) {
            $behavior['auto_fill_rules'] = $this->uniqueRules($autoFillRules);
        }
        if ($detailCard !== null) {
            $behavior['detail_card'] = $detailCard;
        }
        $summary = $this->summaryConfig($fields, $behavior['auto_fill_rules'] ?? []);
        if (!empty($summary)) {
            $behavior['calculated_summary'] = $summary;
        }
        $uniqueRules = $this->uniqueValidationRules($targetTable, $fieldNames);
        if (!empty($uniqueRules)) {
            $behavior['unique_validation_rules'] = $uniqueRules;
        }

        return array_filter($behavior, static fn($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    private function detectInsertOnlyBehavior(array $fields): array
    {
        $multipleField = '';
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $type = strtolower((string)($field['type'] ?? $field['inputType'] ?? ''));
            $name = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? ''));
            if ($name !== '' && $type === 'checkboxes' && (($field['option_preset'] ?? '') === 'calendar_months' || !empty($field['saveAsMultipleRows']))) {
                $multipleField = $name;
                break;
            }
        }

        return $multipleField !== ''
            ? ['submit_mode' => 'multiple_row_insert', 'multiple_row_field' => $multipleField]
            : ['submit_mode' => 'normal_insert'];
    }

    private function summaryConfig(array $fields, array $autoFillRules): array
    {
        $multipleField = '';
        foreach ($fields as $field) {
            $type = strtolower((string)($field['type'] ?? $field['inputType'] ?? ''));
            if ($type === 'checkboxes') {
                $multipleField = (string)($field['name'] ?? $field['field_name'] ?? '');
                break;
            }
        }
        if ($multipleField === '') {
            return [];
        }

        $amountField = '';
        foreach ($autoFillRules as $rule) {
            $target = (string)($rule['target_field'] ?? '');
            $source = (string)($rule['source_path'] ?? '');
            $terminal = substr($source, (int)strrpos($source, '.') + 1);
            if ($target !== '' && $this->looksMoneyLike($terminal)) {
                $amountField = $target;
                break;
            }
        }
        if ($amountField === '') {
            return [];
        }

        return [
            'enabled' => true,
            'items' => [
                ['label' => 'Jumlah dipilih', 'type' => 'count_selected', 'field' => $multipleField],
                ['label' => 'Nominal per item', 'type' => 'field_value', 'field' => $amountField, 'format' => 'currency_idr'],
                [
                    'label' => 'Total',
                    'type' => 'multiply',
                    'left' => ['type' => 'field_value', 'field' => $amountField],
                    'right' => ['type' => 'count_selected', 'field' => $multipleField],
                    'format' => 'currency_idr',
                    'highlight' => true,
                ],
            ],
        ];
    }

    private function detailItems(string $triggerField, string $tableName, array $nestedRelations): array
    {
        $items = [];
        foreach ($this->preferredDisplayColumns($tableName) as $column) {
            $items[] = ['label' => ucwords(str_replace('_', ' ', $column)), 'source_path' => $triggerField . '.' . $column];
        }
        foreach ($nestedRelations as $nestedField => $relation) {
            foreach (array_slice($this->preferredDisplayColumns((string)$relation['table']), 0, 2) as $column) {
                $items[] = [
                    'label' => ucwords(str_replace('_', ' ', $column)),
                    'source_path' => $triggerField . '.' . $nestedField . '.' . $column,
                    'format' => $this->looksMoneyLike($column) ? 'currency_idr' : '',
                ];
            }
        }
        return array_slice($items, 0, 5);
    }

    private function preferredDisplayColumns(string $tableName): array
    {
        $columns = $this->tableColumns($tableName);
        $preferred = [];
        foreach (array_keys($columns) as $column) {
            $normalized = strtolower($column);
            if (in_array($normalized, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true)) {
                continue;
            }
            if (str_ends_with($normalized, '_id')) {
                continue;
            }
            $preferred[] = $column;
        }
        return $preferred;
    }

    private function targetTable(MasterForm $form): ?DbTable
    {
        $tableId = $form->hasAttribute('db_table_id') ? (int)$form->getAttribute('db_table_id') : 0;
        if ($tableId <= 0) {
            $tableId = (int)$form->table_id;
        }
        return $tableId > 0 ? DbTable::findOne($tableId) : null;
    }

    private function fieldMap(array $fields): array
    {
        $map = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $name = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? ''));
            if ($name !== '') {
                $map[$name] = $field;
            }
        }
        return $map;
    }

    private function tableRelations(string $tableName): array
    {
        $relations = [];
        foreach (array_keys($this->tableColumns($tableName)) as $column) {
            $relation = $this->relation($tableName, $column);
            if ($relation !== null) {
                $relations[$column] = $relation;
            }
        }
        return $relations;
    }

    private function relation(string $tableName, string $columnName): ?array
    {
        $metadataTable = DbTable::find()->where(['name' => $tableName])->one();
        if ($metadataTable !== null) {
            $metadataColumn = DbTableColumn::find()->where(['table_id' => (int)$metadataTable->id, 'name' => $columnName])->one();
            if ($metadataColumn !== null && $metadataColumn->hasAttribute('referenced_table_name') && (string)$metadataColumn->getAttribute('referenced_table_name') !== '') {
                return [
                    'table' => (string)$metadataColumn->getAttribute('referenced_table_name'),
                    'column' => (string)($metadataColumn->hasAttribute('referenced_column_name') ? $metadataColumn->getAttribute('referenced_column_name') : 'id') ?: 'id',
                ];
            }
        }

        try {
            $row = Yii::$app->db->createCommand(
                'SELECT REFERENCED_TABLE_NAME AS referenced_table, REFERENCED_COLUMN_NAME AS referenced_column
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME = :column
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1',
                [':table' => $tableName, ':column' => $columnName]
            )->queryOne();
            return $row && !empty($row['referenced_table'])
                ? ['table' => (string)$row['referenced_table'], 'column' => (string)($row['referenced_column'] ?? 'id') ?: 'id']
                : null;
        } catch (\Throwable $e) {
            Yii::warning('Failed detecting relation: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    private function tableColumns(string $tableName): array
    {
        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        return $schema !== null ? $schema->columns : [];
    }

    private function bestColumnMatch(string $targetField, array $columns): ?string
    {
        if (in_array($targetField, $columns, true)) {
            return $targetField;
        }
        $targetTokens = $this->tokens($targetField);
        $best = null;
        $bestScore = 0;
        foreach ($columns as $column) {
            $score = count(array_intersect($targetTokens, $this->tokens($column)));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $column;
            }
        }
        return $bestScore > 0 ? $best : null;
    }

    private function tokens(string $value): array
    {
        return array_values(array_filter(explode('_', strtolower(preg_replace('/[^a-z0-9]+/i', '_', $value) ?? $value))));
    }

    private function uniqueRules(array $rules): array
    {
        $seen = [];
        $result = [];
        foreach ($rules as $rule) {
            $key = ($rule['trigger_field'] ?? '') . '|' . ($rule['target_field'] ?? '') . '|' . ($rule['source_path'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $rule;
            }
        }
        return $result;
    }

    private function isLikelyUserEditable(array $field): bool
    {
        return empty($field['readonly']) && empty($field['disabled']) && empty($field['hidden']) && (($field['type'] ?? '') !== 'hidden');
    }

    private function looksMoneyLike(string $column): bool
    {
        return preg_match('/(nominal|amount|price|fee|total|bayar|biaya)/i', $column) === 1;
    }

    private function uniqueValidationRules(string $tableName, array $fieldNames): array
    {
        try {
            $rows = Yii::$app->db->createCommand(
                'SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND NON_UNIQUE = 0
                   AND INDEX_NAME <> :primary
                 ORDER BY INDEX_NAME, SEQ_IN_INDEX',
                [':table' => $tableName, ':primary' => 'PRIMARY']
            )->queryAll();
        } catch (\Throwable $e) {
            Yii::warning('Failed detecting unique validation rules: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        $indexes = [];
        foreach ($rows as $row) {
            $index = (string)($row['INDEX_NAME'] ?? '');
            $column = (string)($row['COLUMN_NAME'] ?? '');
            if ($index !== '' && $column !== '') {
                $indexes[$index][] = $column;
            }
        }

        $fieldLookup = array_flip($fieldNames);
        $rules = [];
        foreach ($indexes as $columns) {
            if (count($columns) < 2) {
                continue;
            }
            $missing = array_diff($columns, array_keys($fieldLookup));
            if (!empty($missing)) {
                continue;
            }
            $rules[] = [
                'fields' => array_values($columns),
                'message' => 'Data dengan kombinasi field tersebut sudah pernah disimpan.',
            ];
        }

        return $rules;
    }
}
