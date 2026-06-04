<?php

namespace app\services;

use Yii;
use yii\helpers\Json;

/**
 * Generic dynamic-form behavior: multiple-row insert, detail card, submit logging.
 */
class DynamicFormBehaviorService
{
    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $formConfig
     * @return array{submit_mode: string, multiple_row_field: string, repeat_field_names: array<int, string>}
     */
    public function resolveDynamicBehavior(array $fields, array $formConfig = []): array
    {
        $formBehavior = $this->extractBehaviorFromConfig($formConfig);
        $multipleRowField = trim((string)($formBehavior['multiple_row_field'] ?? $formBehavior['multipleRowField'] ?? ''));
        $submitMode = strtolower(trim((string)($formBehavior['submit_mode'] ?? $formBehavior['submitMode'] ?? '')));

        $markedFieldNames = [];
        foreach ($fields as $index => $field) {
            if (!is_array($field) || !$this->fieldHasMultipleRowMarker($field)) {
                continue;
            }
            $fieldName = $this->resolveFieldIdentity($field, (int)$index);
            if ($fieldName !== '') {
                $markedFieldNames[] = $fieldName;
            }
        }
        $markedFieldNames = array_values(array_unique($markedFieldNames));

        if ($multipleRowField === '' && count($markedFieldNames) === 1) {
            $multipleRowField = $markedFieldNames[0];
        }

        if ($multipleRowField === '' && in_array($submitMode, ['multiple_row_insert', 'multiple-row-insert'], true)) {
            $multipleRowField = $this->autoDetectMultipleRowField($fields);
        }

        if ($submitMode === '' && ($multipleRowField !== '' || !empty($markedFieldNames))) {
            $submitMode = 'multiple_row_insert';
        }

        $repeatFieldNames = array_values(array_unique(array_filter(array_merge(
            $multipleRowField !== '' ? [$multipleRowField] : [],
            $markedFieldNames
        ))));

        $isMultipleRowInsert = !empty($repeatFieldNames)
            || in_array($submitMode, ['multiple_row_insert', 'multiple-row-insert'], true);

        return [
            'submit_mode' => $isMultipleRowInsert ? 'multiple_row_insert' : 'single_row',
            'multiple_row_field' => $multipleRowField !== '' ? $multipleRowField : ($markedFieldNames[0] ?? ''),
            'repeat_field_names' => $repeatFieldNames,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array{submit_mode?: string, multiple_row_field?: string, repeat_field_names?: array<int, string>} $behavior
     * @param callable(array<string, mixed>, int): string $resolveFieldName
     * @return array<int, string>
     */
    public function resolveRepeatFieldNames(array $fields, array $behavior, callable $resolveFieldName): array
    {
        $repeatFieldNames = [];
        foreach ((array)($behavior['repeat_field_names'] ?? []) as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                $repeatFieldNames[] = $candidate;
            }
        }

        $configuredField = trim((string)($behavior['multiple_row_field'] ?? ''));
        if ($configuredField !== '') {
            $repeatFieldNames[] = $configuredField;
        }

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            if (!$this->fieldHasMultipleRowMarker($field) && $configuredField === '') {
                continue;
            }

            $resolved = trim((string)$resolveFieldName($field, (int)$index));
            if ($resolved === '') {
                $resolved = $this->resolveFieldIdentity($field, (int)$index);
            }
            if ($resolved === '') {
                continue;
            }

            if ($this->fieldHasMultipleRowMarker($field)) {
                $repeatFieldNames[] = $resolved;
                continue;
            }

            if ($configuredField !== '' && $this->fieldMatchesIdentity($field, $configuredField, $resolved)) {
                $repeatFieldNames[] = $resolved;
            }
        }

        return array_values(array_unique(array_filter($repeatFieldNames)));
    }

    public function fieldHasMultipleRowMarker(array $field): bool
    {
        foreach (array_merge([
            $field['multiple_row_field'] ?? null,
            $field['multipleRowField'] ?? null,
            $field['is_multiple_row_field'] ?? null,
            $field['isMultipleRowField'] ?? null,
            $field['save_as_multiple_rows'] ?? null,
            $field['saveAsMultipleRows'] ?? null,
            $field['repeat_rows'] ?? null,
            $field['repeatRows'] ?? null,
            $field['expand_rows'] ?? null,
            $field['expandRows'] ?? null,
            $field['repeat_on_multiple'] ?? null,
            $field['repeatOnMultiple'] ?? null,
            $field['multi_row'] ?? null,
            $field['multiRow'] ?? null,
            $field['submit_mode'] ?? null,
            $field['submitMode'] ?? null,
            $field['behavior'] ?? null,
            $field['field_behavior'] ?? null,
        ], $this->extractNestedBehaviorCandidates($field)) as $candidate) {
            if (is_bool($candidate) && $candidate) {
                return true;
            }
            if (is_int($candidate) && $candidate === 1) {
                return true;
            }
            if (is_string($candidate)) {
                $normalized = strtolower(trim($candidate));
                if (in_array($normalized, ['1', 'true', 'yes', 'on', 'multiple_row_insert', 'multiple-row-insert'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function normalizeMultipleRowValues($rawValue): array
    {
        if (is_array($rawValue)) {
            return $this->normalizeSubmittedArrayValues($rawValue);
        }

        if (is_string($rawValue)) {
            $trimmed = trim($rawValue);
            if ($trimmed === '') {
                return [];
            }
            if (str_contains($trimmed, ',')) {
                return $this->normalizeSubmittedArrayValues(explode(',', $trimmed));
            }
            return $this->normalizeSubmittedArrayValues([$trimmed]);
        }

        if ($rawValue === null || $rawValue === '') {
            return [];
        }

        if (is_bool($rawValue)) {
            return [$rawValue ? '1' : '0'];
        }

        if (is_scalar($rawValue)) {
            return $this->normalizeSubmittedArrayValues([(string)$rawValue]);
        }

        return [];
    }

    /**
     * @return mixed
     */
    public function coerceInsertFieldValue($postedValue, bool $isRepeatField)
    {
        if ($isRepeatField) {
            $values = $this->normalizeMultipleRowValues($postedValue);
            return !empty($values) ? $values : $postedValue;
        }

        if (is_array($postedValue)) {
            $values = $this->normalizeSubmittedArrayValues($postedValue);
            if (empty($values)) {
                return '';
            }
            return implode(',', $values);
        }

        return $postedValue;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array{submit_mode?: string, multiple_row_field?: string, repeat_field_names?: array<int, string>} $behavior
     * @param array<int, string> $repeatFieldNames
     * @param array<int, array<string, mixed>> $fields
     * @return array<int, array<string, mixed>>
     */
    public function buildSubmissionRows(array $insertData, array $behavior, array $repeatFieldNames, array $fields = []): array
    {
        $submitMode = strtolower(trim((string)($behavior['submit_mode'] ?? '')));
        $configuredRepeatField = trim((string)($behavior['multiple_row_field'] ?? ''));
        $hasRepeatField = !empty($repeatFieldNames) || $configuredRepeatField !== '';
        $isMultipleRowInsert = $hasRepeatField || in_array($submitMode, ['multiple_row_insert', 'multiple-row-insert'], true);
        if (!$isMultipleRowInsert) {
            return [$this->collapseNonRepeatArrays($insertData, [])];
        }

        $repeatFieldName = $this->resolveMultipleRowFieldName($insertData, $behavior, $repeatFieldNames, $fields);
        if ($repeatFieldName === null) {
            return [$this->collapseNonRepeatArrays($insertData, $repeatFieldNames)];
        }

        $repeatValues = $this->normalizeMultipleRowValues($insertData[$repeatFieldName]);
        if (empty($repeatValues)) {
            return [$this->collapseNonRepeatArrays($insertData, $repeatFieldNames)];
        }

        if (count($repeatValues) === 1) {
            $insertData[$repeatFieldName] = $repeatValues[0];
            return [$this->normalizeSubmissionRow($insertData, $repeatFieldNames, $repeatFieldName)];
        }

        $rows = [];
        foreach ($repeatValues as $repeatValue) {
            $row = $insertData;
            $row[$repeatFieldName] = $repeatValue;
            $rows[] = $this->normalizeSubmissionRow($row, $repeatFieldNames, $repeatFieldName);
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array<int, array<string, mixed>> $submissionRows
     * @return array<string, mixed>
     */
    public function buildMultipleRowSubmitDebug(array $behavior, array $insertData, array $submissionRows): array
    {
        $multipleRowField = trim((string)($behavior['multiple_row_field'] ?? ''));
        $selectedValues = [];
        $rawValue = null;

        if ($multipleRowField !== '' && array_key_exists($multipleRowField, $insertData)) {
            $rawValue = $insertData[$multipleRowField];
            $selectedValues = $this->normalizeMultipleRowValues($rawValue);
        } else {
            foreach ((array)($behavior['repeat_field_names'] ?? []) as $fieldName) {
                if (!array_key_exists($fieldName, $insertData)) {
                    continue;
                }
                $multipleRowField = (string)$fieldName;
                $rawValue = $insertData[$fieldName];
                $selectedValues = $this->normalizeMultipleRowValues($rawValue);
                break;
            }
        }

        return [
            'submit_mode' => ($behavior['submit_mode'] ?? '') === 'multiple_row_insert' ? 'multiple_row_insert' : 'single_row',
            'multiple_row_field' => $multipleRowField,
            'raw_value' => $rawValue,
            'selected_values' => $selectedValues,
            'insert_count' => count($submissionRows),
        ];
    }

    public function logMultipleRowSubmit(string $submitPath, int $formId, array $behavior, array $debug): void
    {
        Yii::info([
            'form_id' => $formId,
            'submit_path' => $submitPath,
            'submit_mode' => $debug['submit_mode'] ?? ($behavior['submit_mode'] ?? ''),
            'multiple_row_field' => $debug['multiple_row_field'] ?? ($behavior['multiple_row_field'] ?? ''),
            'raw_value' => $debug['raw_value'] ?? null,
            'selected_values' => $debug['selected_values'] ?? [],
            'insert_row_count' => $debug['insert_count'] ?? 0,
        ], 'dynamic-form-submit');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractDetailCardConfig(array $field): ?array
    {
        $sources = [$field];
        foreach ([
            'detail_card', 'detailCard', 'detail-card',
            'relation_config', 'relationConfig', 'relation',
            'picker_config', 'pickerConfig',
            'field_config', 'fieldConfig', 'field_settings', 'fieldSettings',
            'settings', 'config', 'behavior_config', 'behaviorConfig',
        ] as $key) {
            $value = $field[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                try {
                    $decoded = Json::decode($value, true);
                    $value = is_array($decoded) ? $decoded : null;
                } catch (\Throwable $e) {
                    $value = null;
                }
            }
            if (is_array($value)) {
                $sources[] = $value;
                if (isset($value['detail_card']) && is_array($value['detail_card'])) {
                    $sources[] = $value['detail_card'];
                }
                if (isset($value['detailCard']) && is_array($value['detailCard'])) {
                    $sources[] = $value['detailCard'];
                }
            }
        }

        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            foreach (['detail_card', 'detailCard', 'detail-card'] as $key) {
                if (!isset($source[$key]) || !is_array($source[$key])) {
                    continue;
                }
                $config = $source[$key];
                if ($this->detailCardHasRenderableItems($config)) {
                    return $config;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $detailCardConfig
     * @param array<int, array<string, mixed>> $candidateRows
     * @param array<string, mixed> $triggerField
     * @return array<string, mixed>
     */
    public function buildDetailCardDisplayPayload(array $detailCardConfig, array $candidateRows, array $triggerField = []): array
    {
        $items = [];
        $configuredItems = $detailCardConfig['items'] ?? $detailCardConfig['fields'] ?? $detailCardConfig['columns'] ?? [];
        if (!is_array($configuredItems)) {
            $configuredItems = [];
        }

        foreach ($configuredItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string)($item['label'] ?? $item['title'] ?? $item['name'] ?? ''));
            $value = $this->resolveDetailCardItemValue($item, $candidateRows);
            if ($value === null || $value === '') {
                continue;
            }
            if ($label === '') {
                $label = trim((string)($item['column'] ?? $item['source_column'] ?? $item['field'] ?? 'Detail'));
            }
            if ($this->isBlockedDetailLabel($label) || $this->isBlockedDetailLabel($value)) {
                continue;
            }
            $items[] = ['label' => $label, 'value' => $value];
        }

        if (empty($items)) {
            return ['enabled' => false, 'items' => [], 'from_detail_card' => true];
        }

        $title = trim((string)($detailCardConfig['detail_title'] ?? $detailCardConfig['title'] ?? $triggerField['label'] ?? $triggerField['field_label'] ?? 'Detail'));

        return [
            'enabled' => true,
            'from_detail_card' => true,
            'detail_title' => $title !== '' ? $title : 'Detail',
            'items' => $items,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function normalizeSubmittedArrayValues(array $values): array
    {
        return array_values(array_filter(array_map(static function ($value): string {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            if (is_scalar($value)) {
                return trim((string)$value);
            }
            return '';
        }, $values), static function (string $value): bool {
            return $value !== '';
        }));
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function extractBehaviorFromConfig(array $config): array
    {
        $merged = [];
        $sources = [$config];
        foreach ([
            'behavior', 'submit_behavior', 'submitBehavior',
            'dynamic_behavior', 'dynamicBehavior',
            'detected_behavior', 'detectedBehavior',
            'form_config', 'formConfig', 'settings', 'config',
        ] as $key) {
            $value = $config[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                try {
                    $decoded = Json::decode($value, true);
                    $value = is_array($decoded) ? $decoded : null;
                } catch (\Throwable $e) {
                    $value = null;
                }
            }
            if (is_array($value)) {
                $sources[] = $value;
            }
        }

        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            foreach (['submit_mode', 'submitMode', 'multiple_row_field', 'multipleRowField'] as $key) {
                if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                    $normalizedKey = $key === 'submitMode' ? 'submit_mode' : ($key === 'multipleRowField' ? 'multiple_row_field' : $key);
                    $merged[$normalizedKey] = $source[$key];
                }
            }
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<int, mixed>
     */
    private function extractNestedBehaviorCandidates(array $field): array
    {
        $candidates = [];
        foreach ([
            'field_config', 'fieldConfig', 'field_settings', 'fieldSettings',
            'settings', 'config', 'behavior_config', 'behaviorConfig',
            'dynamic_behavior', 'dynamicBehavior', 'detected_behavior', 'detectedBehavior',
        ] as $key) {
            $config = $field[$key] ?? null;
            if (is_string($config) && trim($config) !== '') {
                try {
                    $decoded = Json::decode($config, true);
                    $config = is_array($decoded) ? $decoded : [];
                } catch (\Throwable $e) {
                    $config = [];
                }
            }
            if (!is_array($config)) {
                continue;
            }

            foreach ([
                'multiple_row_field', 'multipleRowField', 'is_multiple_row_field', 'isMultipleRowField',
                'save_as_multiple_rows', 'saveAsMultipleRows', 'repeat_rows', 'repeatRows',
                'expand_rows', 'expandRows', 'repeat_on_multiple', 'repeatOnMultiple',
                'multi_row', 'multiRow', 'submit_mode', 'submitMode', 'behavior', 'field_behavior',
            ] as $candidateKey) {
                if (array_key_exists($candidateKey, $config)) {
                    $candidates[] = $config[$candidateKey];
                }
            }
        }

        return $candidates;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private function autoDetectMultipleRowField(array $fields): string
    {
        foreach ($fields as $index => $field) {
            if (!is_array($field) || !$this->fieldHasMultipleRowMarker($field)) {
                continue;
            }
            $fieldName = $this->resolveFieldIdentity($field, (int)$index);
            if ($fieldName !== '') {
                return $fieldName;
            }
        }

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }
            if (!$this->isMultiValueInputField($field)) {
                continue;
            }
            $fieldName = $this->resolveFieldIdentity($field, (int)$index);
            if ($fieldName !== '') {
                return $fieldName;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $field
     */
    private function isMultiValueInputField(array $field): bool
    {
        $inputType = strtolower(trim((string)($field['inputType'] ?? $field['type'] ?? $field['field_type'] ?? $field['component_type'] ?? '')));
        if (in_array($inputType, ['checkboxes', 'multiselect', 'multi_select', 'multi-select', 'checkbox-group', 'checkbox_group'], true)) {
            return true;
        }
        if (!empty($field['multiple']) || !empty($field['is_multiple'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $field
     */
    private function resolveFieldIdentity(array $field, int $index): string
    {
        foreach ([
            'resolved_name', 'resolved_column_name', 'name', 'field_name', 'field_key', 'column_name',
        ] as $key) {
            $value = trim((string)($field[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'field_' . ($index + 1);
    }

    /**
     * @param array<string, mixed> $field
     */
    private function fieldMatchesIdentity(array $field, string $configuredField, string $resolvedField): bool
    {
        $configured = strtolower(trim($configuredField));
        if ($configured === '') {
            return false;
        }

        $candidates = array_filter(array_unique([
            $resolvedField,
            (string)($field['name'] ?? ''),
            (string)($field['field_name'] ?? ''),
            (string)($field['field_key'] ?? ''),
            (string)($field['column_name'] ?? ''),
            (string)($field['resolved_name'] ?? ''),
            (string)($field['resolved_column_name'] ?? ''),
        ]));

        foreach ($candidates as $candidate) {
            if (strtolower(trim($candidate)) === $configured) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array<int, string> $repeatFieldNames
     * @return array<string, mixed>
     */
    private function collapseNonRepeatArrays(array $insertData, array $repeatFieldNames): array
    {
        $singleRow = [];
        foreach ($insertData as $columnName => $value) {
            if (is_array($value)) {
                if (in_array($columnName, $repeatFieldNames, true)) {
                    $singleRow[$columnName] = $this->normalizeSubmittedArrayValues($value);
                    continue;
                }
                $singleRow[$columnName] = implode(',', array_map(static function ($item): string {
                    return is_scalar($item) ? (string)$item : '';
                }, $value));
                continue;
            }
            $singleRow[$columnName] = $value;
        }

        return $singleRow;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array{submit_mode?: string, multiple_row_field?: string, repeat_field_names?: array<int, string>} $behavior
     * @param array<int, string> $repeatFieldNames
     * @param array<int, array<string, mixed>> $fields
     * @return string|null
     */
    private function resolveMultipleRowFieldName(array $insertData, array $behavior, array $repeatFieldNames, array $fields = []): ?string
    {
        $preferredField = trim((string)($behavior['multiple_row_field'] ?? ''));
        $candidates = array_values(array_unique(array_filter(array_merge(
            $preferredField !== '' ? [$preferredField] : [],
            $repeatFieldNames
        ))));

        foreach ($candidates as $candidateFieldName) {
            if ($candidateFieldName === '' || !array_key_exists($candidateFieldName, $insertData)) {
                continue;
            }

            if ($this->normalizeMultipleRowValues($insertData[$candidateFieldName]) !== []) {
                return $candidateFieldName;
            }
        }

        foreach ($fields as $fieldIndex => $field) {
            if (!is_array($field) || !$this->fieldHasMultipleRowMarker($field)) {
                continue;
            }

            $fieldName = $this->resolveFieldIdentity($field, (int)$fieldIndex);
            if ($fieldName === '' || !array_key_exists($fieldName, $insertData)) {
                continue;
            }

            if ($this->normalizeMultipleRowValues($insertData[$fieldName]) !== []) {
                return $fieldName;
            }
        }

        if (in_array(strtolower(trim((string)($behavior['submit_mode'] ?? ''))), ['multiple_row_insert', 'multiple-row-insert'], true)) {
            foreach ($insertData as $columnName => $value) {
                if ($columnName === '' || !is_array($value) && !is_string($value) && !is_scalar($value)) {
                    continue;
                }

                $values = $this->normalizeMultipleRowValues($value);
                if (count($values) > 1) {
                    return (string)$columnName;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $repeatFieldNames
     * @param string|null $repeatFieldName
     * @return array<string, mixed>
     */
    private function normalizeSubmissionRow(array $row, array $repeatFieldNames, ?string $repeatFieldName): array
    {
        $normalized = [];
        foreach ($row as $columnName => $value) {
            $isRepeatField = ($repeatFieldName !== null && $columnName === $repeatFieldName) || in_array($columnName, $repeatFieldNames, true);
            if ($isRepeatField) {
                if (is_array($value)) {
                    $values = $this->normalizeMultipleRowValues($value);
                    $normalized[$columnName] = $values[0] ?? '';
                    continue;
                }
                $values = $this->normalizeMultipleRowValues($value);
                if (!empty($values)) {
                    $normalized[$columnName] = $values[0];
                    continue;
                }
                $normalized[$columnName] = is_scalar($value) ? trim((string)$value) : '';
                continue;
            }

            $normalized[$columnName] = $this->scalarizeInsertValue($value, false);
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function scalarizeInsertValue($value, bool $isRepeatField)
    {
        if ($isRepeatField) {
            return $this->coerceInsertFieldValue($value, true);
        }
        if (is_array($value)) {
            return implode(',', array_map(static function ($item): string {
                return is_scalar($item) ? (string)$item : '';
            }, $value));
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function detailCardHasRenderableItems(array $config): bool
    {
        $items = $config['items'] ?? $config['fields'] ?? $config['columns'] ?? null;
        return is_array($items) && !empty($items);
    }

    /**
     * @param array<string, mixed> $item
     * @param array<int, array<string, mixed>> $candidateRows
     */
    private function resolveDetailCardItemValue(array $item, array $candidateRows): ?string
    {
        if (array_key_exists('value', $item) && is_scalar($item['value']) && (string)$item['value'] !== '') {
            return (string)$item['value'];
        }

        $columnKeys = ['column', 'source_column', 'source', 'field', 'value_column', 'from', 'name'];
        foreach ($columnKeys as $key) {
            $column = trim((string)($item[$key] ?? ''));
            if ($column === '' || in_array($key, ['label', 'title'], true)) {
                continue;
            }

            foreach ($candidateRows as $rowEntry) {
                $row = is_array($rowEntry['row'] ?? null) ? $rowEntry['row'] : [];
                if (!array_key_exists($column, $row)) {
                    continue;
                }
                $value = $row[$column];
                if ($value === null || $value === '') {
                    continue;
                }
                if ($this->isSensitiveOrAuditColumn($column)) {
                    continue;
                }
                return is_scalar($value) ? (string)$value : Json::encode($value);
            }
        }

        return null;
    }

    private function isSensitiveOrAuditColumn(string $columnName): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/', '_', $columnName) ?? $columnName);
        foreach ([
            'password', 'token', 'secret', 'auth_key', 'api_key', 'remember_token',
            'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by',
        ] as $blocked) {
            if ($normalized === $blocked || str_contains($normalized, $blocked)) {
                return true;
            }
        }

        return false;
    }

    private function isBlockedDetailLabel(string $label): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/', '_', $label) ?? $label);
        $normalized = trim($normalized, '_');
        if ($normalized === '') {
            return true;
        }

        if ($normalized === 'id' || str_ends_with($normalized, '_id') || str_contains($normalized, '_id_')) {
            return true;
        }

        return $this->isSensitiveOrAuditColumn($normalized);
    }
}
