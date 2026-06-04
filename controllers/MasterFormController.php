<?php

namespace app\controllers;

use Yii;
use app\models\MasterForm;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
use app\models\MasterFormActivityLog;
use app\models\MasterPage;
use app\models\MasterMenu;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\DatabaseSchemaInitializer;
use app\components\ProjectSchema;
use app\components\ProjectPermissionService;
use app\components\SystemFieldService;
use app\helpers\FormSystemFieldHelper;
use app\components\FormFlowDebugLogger;
use app\services\FormActivityLogService;
use app\services\FormEngineService;
use app\services\FormRenderService;
use yii\data\ActiveDataProvider;
use yii\db\IntegrityException;
use yii\db\Query;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

class MasterFormController extends Controller
{
    public $layout = 'dashboard';
    private FormEngineService $formEngineService;
    private FormRenderService $formRenderService;
    private FormActivityLogService $activityLogService;

    public function init()
    {
        parent::init();
        $this->formEngineService = new FormEngineService();
        $this->formRenderService = new FormRenderService();
        $this->activityLogService = new FormActivityLogService();
    }

    private function assignActiveProject(MasterForm $model): void
    {
        if (!$model->hasAttribute('project_id')) {
            return;
        }

        $activeProjectId = $this->getActiveProjectId();
        $model->project_id = $activeProjectId !== null ? (int)$activeProjectId : null;
    }

    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     */
    private function validateInsertDataLengths(array $insertData, array $schemaColumns): ?string
    {
        $invalidFields = [];
        foreach ($insertData as $columnName => $value) {
            if (!isset($schemaColumns[$columnName])) {
                continue;
            }

            $column = $schemaColumns[$columnName];
            $type = strtoupper((string)($column->type ?? ''));
            $maxLength = (int)($column->size ?? 0);
            if ($maxLength <= 0 || !in_array($type, ['CHAR', 'VARCHAR', 'TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT'], true)) {
                continue;
            }

            if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
                continue;
            }

            if (mb_strlen(trim((string)$value), 'UTF-8') > $maxLength) {
                $invalidFields[] = $this->formatColumnLabel((string)$columnName) . " maksimal {$maxLength} karakter";
            }
        }

        if (!empty($invalidFields)) {
            return 'Nilai pada field berikut terlalu panjang: ' . implode(', ', $invalidFields) . '.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     */
    private function validateInsertDataColumns(array $insertData, array $schemaColumns): ?string
    {
        $unknownFields = [];
        foreach (array_keys($insertData) as $columnName) {
            if (isset($schemaColumns[$columnName])) {
                continue;
            }

            $unknownFields[] = $this->formatColumnLabel((string)$columnName);
        }

        if (!empty($unknownFields)) {
            return 'Field berikut tidak ditemukan di tabel target: ' . implode(', ', $unknownFields) . '. Mohon sinkronkan ulang field form dengan tabel.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $insertData
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     */
    private function validateRequiredInsertData(array $insertData, array $schemaColumns): ?string
    {
        $missingFields = [];
        foreach ($schemaColumns as $columnName => $column) {
            if ($this->isSubmitSystemColumn((string)$columnName, $column)) {
                continue;
            }

            if (!empty($column->allowNull) || $column->defaultValue !== null) {
                continue;
            }

            if (array_key_exists((string)$columnName, $insertData)) {
                continue;
            }

            $missingFields[] = $this->formatColumnLabel((string)$columnName);
        }

        if (!empty($missingFields)) {
            return 'Field berikut wajib diisi karena kolom target tidak mengizinkan nilai kosong: ' . implode(', ', $missingFields) . '.';
        }

        return null;
    }

    private function formatColumnLabel(string $columnName): string
    {
        return ucwords(str_replace('_', ' ', $this->normalizeDatabaseColumnName($columnName)));
    }

    /**
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     */
    private function buildFriendlySaveErrorMessage(\Throwable $e, array $schemaColumns = []): string
    {
        $message = $this->sanitizeDatabaseErrorMessage((string)$e->getMessage());
        if (preg_match("/Field '([^']+)' doesn't have a default value/i", $message, $matches) === 1) {
            $columnName = $this->normalizeDatabaseColumnName((string)$matches[1]);
            $label = ucwords(str_replace('_', ' ', $columnName));
            return "Field {$label} wajib diisi karena kolom target tidak memiliki default value.";
        }

        if (preg_match("/Column '([^']+)' cannot be null/i", $message, $matches) === 1) {
            $columnName = $this->normalizeDatabaseColumnName((string)$matches[1]);
            $label = ucwords(str_replace('_', ' ', $columnName));
            return "Field {$label} wajib diisi karena kolom target tidak mengizinkan nilai kosong.";
        }

        if (preg_match("/Incorrect (date|datetime|time) value: .* for column '([^']+)'/i", $message, $matches) === 1) {
            $columnName = $this->normalizeDatabaseColumnName((string)$matches[2]);
            $label = ucwords(str_replace('_', ' ', $columnName));
            return "Format nilai pada field {$label} tidak sesuai dengan tipe kolom target.";
        }

        if (preg_match("/Data truncated for column '([^']+)'/i", $message, $matches) === 1) {
            $columnName = $this->normalizeDatabaseColumnName((string)$matches[1]);
            $label = ucwords(str_replace('_', ' ', $columnName));
            return "Nilai pada field {$label} tidak sesuai dengan pilihan atau format kolom target.";
        }

        if (preg_match('/Data too long for column [`"]?([^`"]+)[`"]?/i', $message, $matches) === 1) {
            $columnName = $this->normalizeDatabaseColumnName((string)$matches[1]);
            $label = ucwords(str_replace('_', ' ', $columnName));
            $maxLength = (int)($schemaColumns[$columnName]->size ?? 0);
            if ($maxLength > 0) {
                return "Nilai pada field {$label} terlalu panjang. Maksimal {$maxLength} karakter.";
            }

            return "Nilai pada field {$label} terlalu panjang. Mohon ringkas isinya dan coba lagi.";
        }

        if (preg_match("/Duplicate entry '.*' for key '([^']+)'/i", $message, $matches) === 1) {
            $keyName = $this->normalizeDatabaseColumnName((string)$matches[1]);
            $label = ucwords(str_replace(['_', '.'], ' ', $keyName));
            return "Data tidak bisa disimpan karena nilai pada {$label} harus unik dan sudah digunakan.";
        }

        if (stripos($message, 'foreign key constraint fails') !== false) {
            return 'Data tidak bisa disimpan karena nilai relasi tidak ditemukan di tabel referensi.';
        }

        if (preg_match("/Unknown column '([^']+)'/i", $message, $matches) === 1) {
            $columnName = $this->normalizeDatabaseColumnName((string)$matches[1]);
            $label = ucwords(str_replace('_', ' ', $columnName));
            return "Field {$label} tidak ditemukan di tabel target. Mohon sinkronkan ulang field form dengan tabel.";
        }

        if (
            stripos($message, 'string or binary data would be truncated') !== false
            || stripos($message, 'value too long') !== false
            || stripos($message, 'out of range') !== false
        ) {
            return 'Data yang dimasukkan terlalu panjang atau tidak sesuai dengan format kolom. Mohon periksa kembali input Anda.';
        }

        if (
            stripos($message, 'SQLSTATE') !== false
            || stripos($message, 'The SQL being executed was') !== false
            || stripos($message, 'Integrity constraint violation') !== false
        ) {
            return 'Data gagal disimpan. Mohon periksa kembali input Anda.';
        }

        return 'Data gagal disimpan. Mohon periksa kembali input Anda.';
    }

    private function sanitizeDatabaseErrorMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return $message;
        }

        $message = preg_replace('/\s+At Row\s+\d+.*$/i', '', $message) ?? $message;
        $message = preg_replace('/\s+The SQL being executed was:.*$/is', '', $message) ?? $message;
        $message = preg_replace('/\s+SQLSTATE\[[^\]]+\].*$/i', '', $message) ?? $message;

        return trim($message);
    }

    private function normalizeDatabaseColumnName(string $columnName): string
    {
        $columnName = trim($columnName);
        $columnName = preg_replace('/\s+At Row\s+\d+.*$/i', '', $columnName) ?? $columnName;
        $columnName = preg_replace('/\s+The SQL being executed was:.*$/is', '', $columnName) ?? $columnName;
        $columnName = trim($columnName, " \t\n\r\0\x0B`'\"");

        return trim($columnName);
    }

    private function normalizeSubmitKey(string $key): string
    {
        return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $key), '_'));
    }

    private function getSubmissionTokenStore(): array
    {
        $store = Yii::$app->session->get('master_form_submission_tokens', []);
        return is_array($store) ? $store : [];
    }

    private function hasProcessedSubmissionToken(int $formId, string $token): bool
    {
        $token = trim($token);
        if ($formId <= 0 || $token === '') {
            return false;
        }

        $store = $this->getSubmissionTokenStore();
        return !empty($store[(string)$formId][$token]);
    }

    private function reserveSubmissionToken(int $formId, string $token): bool
    {
        $token = trim($token);
        if ($formId <= 0 || $token === '') {
            return true;
        }

        $store = $this->getSubmissionTokenStore();
        if (!isset($store[(string)$formId]) || !is_array($store[(string)$formId])) {
            $store[(string)$formId] = [];
        }
        if (!empty($store[(string)$formId][$token])) {
            return false;
        }

        $store[(string)$formId][$token] = [
            'status' => 'processing',
            'reserved_at' => date('Y-m-d H:i:s'),
        ];
        Yii::$app->session->set('master_form_submission_tokens', $store);
        return true;
    }

    private function releaseSubmissionToken(int $formId, string $token): void
    {
        $token = trim($token);
        if ($formId <= 0 || $token === '') {
            return;
        }

        $store = $this->getSubmissionTokenStore();
        if (isset($store[(string)$formId][$token])) {
            unset($store[(string)$formId][$token]);
            Yii::$app->session->set('master_form_submission_tokens', $store);
        }
    }

    private function markSubmissionTokenProcessed(int $formId, string $token): void
    {
        $token = trim($token);
        if ($formId <= 0 || $token === '') {
            return;
        }

        $store = $this->getSubmissionTokenStore();
        if (!isset($store[(string)$formId]) || !is_array($store[(string)$formId])) {
            $store[(string)$formId] = [];
        }
        $store[(string)$formId][$token] = [
            'status' => 'processed',
            'processed_at' => date('Y-m-d H:i:s'),
        ];
        Yii::$app->session->set('master_form_submission_tokens', $store);
    }

    private function normalizeSchemaKey(string $key): string
    {
        return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $key), '_'));
    }

    private function resolveTargetTableSchema(MasterForm $model)
    {
        $tableId = $this->resolveTargetTableId($model);
        if ($tableId <= 0) {
            return null;
        }

        $table = DbTable::findOne(['id' => $tableId]);
        if ($table === null || !(bool)$table->is_created) {
            return null;
        }

        try {
            return Yii::$app->db->schema->getTableSchema((string)$table->name, true);
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve target schema for master form ' . (int)$model->id . ': ' . $e->getMessage(), 'submit_debug');
            return null;
        }
    }

    /**
     * @param mixed $schema
     * @return array<string, string>
     */
    private function buildSchemaColumnLookup($schema): array
    {
        $lookup = [];
        if ($schema === null || empty($schema->columns)) {
            return $lookup;
        }

        foreach ($schema->columns as $columnName => $column) {
            $columnName = (string)$columnName;
            $aliases = [
                $columnName,
                $this->normalizeSchemaKey($columnName),
                $this->normalizeSchemaKey(ucwords(str_replace('_', ' ', $columnName))),
            ];

            if (str_ends_with($this->normalizeSchemaKey($columnName), '_id')) {
                $aliases[] = substr($this->normalizeSchemaKey($columnName), 0, -3);
            }

            foreach ([
                $column->label ?? null,
                $column->comment ?? null,
            ] as $aliasValue) {
                if (is_string($aliasValue) && trim($aliasValue) !== '') {
                    $aliases[] = trim($aliasValue);
                    $aliases[] = $this->normalizeSchemaKey(trim($aliasValue));
                }
            }

            foreach (array_values(array_filter($aliases)) as $alias) {
                $normalizedAlias = $this->normalizeSchemaKey((string)$alias);
                if ($normalizedAlias === '' || isset($lookup[$normalizedAlias])) {
                    continue;
                }
                $lookup[$normalizedAlias] = $columnName;
            }
        }

        return $lookup;
    }

    /**
     * @param array<int, string> $candidates
     * @param array<string, string> $lookup
     */
    private function matchSchemaColumnCandidate(array $candidates, array $lookup): ?string
    {
        $bestMatch = null;
        $bestScore = 0.0;
        foreach (array_values(array_unique(array_filter(array_map('trim', $candidates)))) as $candidate) {
            $normalizedCandidate = $this->normalizeSchemaKey($candidate);
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
                $normalizedAlias = $this->normalizeSchemaKey($alias);
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

    private function labelMatchesField(string $label, string $fieldName): bool
    {
        $labelTokens = array_values(array_filter(explode('_', $this->normalizeSchemaKey($label))));
        $fieldTokens = array_values(array_filter(explode('_', $this->normalizeSchemaKey($fieldName))));
        if (empty($labelTokens) || empty($fieldTokens)) {
            return false;
        }

        return count(array_intersect($labelTokens, $fieldTokens)) > 0;
    }

    private function resolveTargetTableId(MasterForm $model): int
    {
        if ($model->hasAttribute('db_table_id')) {
            $dbTableId = (int)$model->getAttribute('db_table_id');
            if ($dbTableId > 0) {
                return $dbTableId;
            }
        }

        return (int)$model->table_id;
    }

    private function cleanSystemFieldsFromModel(MasterForm $model): bool
    {
        $original = $model->getFormDataArray();
        $clean = $this->filterSystemFieldsForModel($original, $model);
        $model->form_data = $clean;

        return $clean !== $original;
    }

    private function filterSystemFieldsForModel(array $builderData, MasterForm $model): array
    {
        $filter = function (array $fields) use ($model): array {
            $filtered = [];
            foreach ($fields as $field) {
                if (!is_array($field) || $this->isSystemFieldDataForModel($field, $model)) {
                    continue;
                }
                $filtered[] = $field;
            }
            return $filtered;
        };

        if (isset($builderData['fields']) && is_array($builderData['fields'])) {
            $builderData['fields'] = $filter($builderData['fields']);
            return $builderData;
        }

        if ($this->isListArray($builderData)) {
            return $filter($builderData);
        }

        return $builderData;
    }

    private function isSystemFieldDataForModel(array $fieldData, MasterForm $model): bool
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

        if (!empty($model->table_id)) {
            $fieldName = $fieldData['name'] ?? $fieldData['field_name'] ?? $fieldData['field_key'] ?? '';
            if ($fieldName !== '') {
                $sourceColumn = DbTableColumn::find()
                    ->where(['table_id' => (int)$model->table_id, 'name' => (string)$fieldName])
                    ->one();
                if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findScopedModel($id): MasterForm
    {
        $model = MasterForm::findByIdScoped($id);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    private function resolveRelationPickerField(MasterForm $model, string $fieldName): ?array
    {
        $target = trim($fieldName);
        if ($target === '') {
            return null;
        }

        $fields = [];
        try {
            $schema = $this->formEngineService->getResolvedFormSchema($model);
            $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
        } catch (\Throwable $e) {
            $fields = [];
        }
        if (empty($fields)) {
            $fields = $this->extractFieldsFromBuilderData($this->normalizeBuilderData($model));
        }

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }
            $field = FormRenderService::normalizeFieldForRender($field, (int)$index);
            if (!FormRenderService::isRelationField($field) && empty($field['is_foreign_key'])) {
                continue;
            }
            $candidates = array_filter(array_unique([
                (string)($field['resolved_name'] ?? ''),
                (string)($field['resolved_column_name'] ?? ''),
                (string)($field['name'] ?? ''),
                (string)($field['field_name'] ?? ''),
                (string)($field['field_key'] ?? ''),
                (string)($field['column_name'] ?? ''),
                (string)($field['local_column'] ?? ''),
                (string)($field['source_column'] ?? ''),
                (string)(is_array($field['relation_config'] ?? null) ? ($field['relation_config']['local_column'] ?? '') : ''),
                (string)(is_array($field['relation_config'] ?? null) ? ($field['relation_config']['source_column'] ?? '') : ''),
            ]));
            if (in_array($target, $candidates, true)) {
                return $field;
            }
        }

        return null;
    }

    private function buildRelationPickerConfig(array $field): ?array
    {
        $relationConfig = $field['relation_config'] ?? [];
        if (is_string($relationConfig)) {
            $relationConfig = Json::decode($relationConfig, true);
        }
        if (!is_array($relationConfig)) {
            $relationConfig = [];
        }

        $pickerConfig = $field['picker_config'] ?? [];
        if (is_string($pickerConfig)) {
            $pickerConfig = Json::decode($pickerConfig, true);
        }
        if (!is_array($pickerConfig)) {
            $pickerConfig = [];
        }

        $tableName = trim((string)($field['fk_referenced_table'] ?? $field['source_table_name'] ?? $field['referenced_table_name'] ?? $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? ''));
        $valueColumn = trim((string)($field['fk_referenced_column'] ?? $field['value_column'] ?? $relationConfig['referenced_value_column'] ?? $relationConfig['value_column'] ?? $relationConfig['referenced_column'] ?? 'id'));
        if ($tableName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            return null;
        }

        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        if ($schema === null || !isset($schema->columns[$valueColumn])) {
            return null;
        }

        $displayColumn = trim((string)($pickerConfig['display_column'] ?? $field['fk_display_column'] ?? $field['label_column'] ?? $relationConfig['display_column'] ?? ''));
        $displayColumn = $this->resolvePickerDisplayColumn($schema->columns, $valueColumn, $displayColumn);
        $searchColumns = $this->normalizePickerColumnList($pickerConfig['search_columns'] ?? [], $schema->columns, 5);
        if (empty($searchColumns)) {
            $searchColumns = $this->resolvePickerSearchColumns($schema->columns, $valueColumn, $displayColumn);
        }
        $displayColumns = $this->normalizePickerColumnList($pickerConfig['display_columns'] ?? [], $schema->columns, 8);
        if (empty($displayColumns)) {
            $displayColumns = $this->resolvePickerDisplayColumns($schema->columns, $valueColumn, $displayColumn);
        }

        return [
            'main_table' => $tableName,
            'value_column' => $valueColumn,
            'display_column' => $displayColumn,
            'search_columns' => $searchColumns,
            'display_columns' => $displayColumns,
            'page_size' => min(50, max(1, (int)($pickerConfig['page_size'] ?? 10))),
        ];
    }

    private function queryRelationPickerRows(array $config, string $keyword, int $page, int $pageSize): array
    {
        $tableName = (string)$config['main_table'];
        $valueColumn = (string)$config['value_column'];
        $displayColumn = (string)$config['display_column'];
        $displayColumns = array_values(array_unique(array_merge([$valueColumn, $displayColumn], $config['display_columns'] ?? [])));
        $select = [];
        foreach ($displayColumns as $column) {
            if (preg_match('/^[A-Za-z0-9_]+$/', (string)$column)) {
                $select[] = $column;
            }
        }

        $query = (new Query())->select(array_values(array_unique($select)))->from($tableName);
        if ($keyword !== '') {
            $or = ['or'];
            foreach (($config['search_columns'] ?? []) as $column) {
                $or[] = ['like', $column, $keyword];
            }
            if (count($or) > 1) {
                $query->andWhere($or);
            }
        }

        $total = (int)(clone $query)->count('*', Yii::$app->db);
        $rows = $query
            ->orderBy([$displayColumn => SORT_ASC])
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all(Yii::$app->db);

        return [
            'total' => $total,
            'rows' => array_map(function (array $row) use ($valueColumn, $displayColumn, $displayColumns): array {
                $display = [];
                foreach ($displayColumns as $column) {
                    if (array_key_exists($column, $row)) {
                        $display[$this->humanizePickerColumn($column)] = $row[$column];
                    }
                }
                return [
                    'value' => (string)($row[$valueColumn] ?? ''),
                    'label' => (string)($row[$displayColumn] ?? $row[$valueColumn] ?? ''),
                    'display' => $display,
                ];
            }, $rows),
        ];
    }

    private function resolvePickerDisplayColumn(array $columns, string $valueColumn, string $preferred = ''): string
    {
        if ($preferred !== '' && isset($columns[$preferred]) && $this->isPickerSafeColumn($preferred, $columns[$preferred])) {
            return $preferred;
        }
        foreach (['name', 'nama', 'title', 'judul', 'label', 'kode', 'code', 'email'] as $candidate) {
            if (isset($columns[$candidate]) && $this->isPickerSafeColumn($candidate, $columns[$candidate])) {
                return $candidate;
            }
        }
        foreach ($columns as $name => $column) {
            if ($name !== $valueColumn && $this->isPickerSafeColumn((string)$name, $column)) {
                return (string)$name;
            }
        }
        return $valueColumn;
    }

    private function resolvePickerSearchColumns(array $columns, string $valueColumn, string $displayColumn): array
    {
        $result = [];
        foreach (array_unique([$displayColumn, 'kode', 'code', 'nomor', 'number', 'no', 'name', 'nama', 'title', 'label', 'email', $valueColumn]) as $candidate) {
            if (isset($columns[$candidate]) && $this->isPickerSafeColumn($candidate, $columns[$candidate])) {
                $result[] = $candidate;
            }
            if (count($result) >= 5) {
                break;
            }
        }
        return $result;
    }

    private function resolvePickerDisplayColumns(array $columns, string $valueColumn, string $displayColumn): array
    {
        $result = [];
        foreach (array_unique([$displayColumn, 'kode', 'code', 'name', 'nama', 'title', 'label', $valueColumn]) as $candidate) {
            if (isset($columns[$candidate]) && $this->isPickerSafeColumn($candidate, $columns[$candidate])) {
                $result[] = $candidate;
            }
            if (count($result) >= 6) {
                break;
            }
        }
        return $result;
    }

    private function normalizePickerColumnList($columns, array $schemaColumns, int $limit): array
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($columns)) {
            return [];
        }
        $result = [];
        foreach ($columns as $column) {
            $name = trim((string)$column);
            if ($name !== '' && isset($schemaColumns[$name]) && $this->isPickerSafeColumn($name, $schemaColumns[$name])) {
                $result[] = $name;
            }
            if (count($result) >= $limit) {
                break;
            }
        }
        return array_values(array_unique($result));
    }

    private function isPickerSafeColumn(string $name, $column): bool
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            return false;
        }
        if (preg_match('/password|token|secret|remember|auth|salt|hash/i', $name)) {
            return false;
        }
        $type = strtolower((string)($column->type ?? ''));
        return in_array($type, ['string', 'text', 'integer', 'bigint', 'smallint', 'tinyint'], true);
    }

    private function humanizePickerColumn(string $column): string
    {
        return ucwords(str_replace('_', ' ', $column));
    }

    private function normalizeBuilderData(MasterForm $model): array
    {
        $formData = $this->filterSystemFieldsForModel($model->getFormDataArray(), $model);
        if (!empty($formData['fields']) && is_array($formData['fields'])) {
            return $formData;
        }

        return [
            'fields' => $formData,
        ];
    }

    private function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    private function extractFieldsFromBuilderData(array $builderData): array
    {
        if (isset($builderData['fields']) && is_array($builderData['fields'])) {
            return $builderData['fields'];
        }

        if ($this->isListArray($builderData)) {
            return $builderData;
        }

        return [];
    }

    private function normalizeFieldName(array $field, int $index, $schema = null, int $targetTableId = 0): string
    {
        $identityCandidates = array_filter(array_unique([
            (string)($field['resolved_name'] ?? ''),
            (string)($field['resolved_column_name'] ?? ''),
            (string)($field['name'] ?? ''),
            (string)($field['field_name'] ?? ''),
            (string)($field['field_key'] ?? ''),
            (string)($field['column_name'] ?? ''),
            (string)($field['original_column'] ?? ''),
            (string)($field['local_column'] ?? ''),
            (string)($field['source_column'] ?? ''),
            (string)($field['source_column_name'] ?? ''),
        ]));
        $labelCandidates = array_filter(array_unique([
            (string)($field['label'] ?? ''),
            (string)($field['field_label'] ?? ''),
            (string)($field['labelText'] ?? ''),
        ]));

        $sourceColumnId = (int)($field['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = DbTableColumn::findOne($sourceColumnId);
            if ($sourceColumn !== null && trim((string)$sourceColumn->name) !== '') {
                array_unshift($identityCandidates, (string)$sourceColumn->name);
            }
        }

        $relationConfig = [];
        foreach (['relation_config', 'relationConfig', 'relation'] as $relationKey) {
            if (isset($field[$relationKey]) && is_array($field[$relationKey])) {
                $relationConfig = $field[$relationKey];
                break;
            }
        }
        foreach ([
            (string)($relationConfig['local_column'] ?? ''),
            (string)($relationConfig['source_column'] ?? ''),
            (string)($relationConfig['column_name'] ?? ''),
            (string)($relationConfig['original_column'] ?? ''),
            (string)($relationConfig['field_name'] ?? ''),
            (string)($relationConfig['field_key'] ?? ''),
        ] as $candidate) {
            if ($candidate !== '') {
                $identityCandidates[] = $candidate;
            }
        }

        $name = null;
        if ($schema !== null) {
            $lookup = $this->buildSchemaColumnLookup($schema);
            $name = $this->matchSchemaColumnCandidate(array_values($identityCandidates), $lookup);
            if (($name === null || $name === '') && !empty($labelCandidates)) {
                $name = $this->matchSchemaColumnCandidate(array_values($labelCandidates), $lookup);
            }
        }

        if (($name === null || $name === '') && $schema !== null) {
            $fkColumn = $this->resolveFkColumnFromRelationConfig($field, $schema, $targetTableId);
            if ($fkColumn !== null) {
                $name = $fkColumn;
            }
        }

        if ($name === null || $name === '') {
            $name = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
        }
        if ($name === '') {
            $name = 'field_' . ($index + 1);
        }

        return $name;
    }

    private function resolveFkColumnFromRelationConfig(array $field, $targetSchema, int $targetTableId): ?string
    {
        $relationConfig = [];
        foreach (['relation_config', 'relationConfig', 'relation'] as $relationKey) {
            if (isset($field[$relationKey]) && is_array($field[$relationKey])) {
                $relationConfig = $field[$relationKey];
                break;
            }
        }
        if (empty($relationConfig) && empty($field['is_foreign_key']) && empty($field['fk_referenced_table'])) {
            return null;
        }
        $referencedTable = $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? $field['fk_referenced_table'] ?? $field['foreign_key_table'] ?? null;
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
            $normalized = $this->normalizeSchemaKey((string)$candidate);
            if (isset($targetSchema->columns[$candidate])) {
                return $candidate;
            }
            if (isset($targetSchema->columns[$normalized])) {
                return $normalized;
            }
            foreach ($targetSchema->columns as $colName => $col) {
                if ($this->normalizeSchemaKey($colName) === $normalized) {
                    return $colName;
                }
            }
        }
        if ($targetTableId > 0) {
            $fkColumn = DbTableColumn::find()
                ->where(['table_id' => $targetTableId, 'is_foreign_key' => true])
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

    private function looksLikeFallbackFieldName(string $name): bool
    {
        $normalized = strtolower(trim($name));
        return preg_match('/^field[\s_-]*\d+$/', $normalized) === 1
            || preg_match('/^kolom[\s_-]*\d+$/', $normalized) === 1;
    }

    private function resolveFieldLabel(array $field, string $fieldName): string
    {
        $label = trim((string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['labelText'] ?? ''));
        $sourceColumnId = (int)($field['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = DbTableColumn::findOne($sourceColumnId);
            if ($sourceColumn !== null) {
                $sourceLabel = trim((string)($sourceColumn->label ?? ''));
                if ($sourceLabel === '' || $this->looksLikeFallbackFieldName($sourceLabel)) {
                    $sourceLabel = trim((string)($sourceColumn->name ?? ''));
                }
                if ($sourceLabel !== '') {
                    $label = $sourceLabel;
                }
            }
        }

        if (($label === '' || $this->looksLikeFallbackFieldName($label)) && $fieldName !== '') {
            $label = $fieldName;
        }

        if ($label === '' || $this->looksLikeFallbackFieldName($label)) {
            $relationConfig = [];
            foreach (['relation_config', 'relationConfig', 'relation'] as $relationKey) {
                if (isset($field[$relationKey]) && is_array($field[$relationKey])) {
                    $relationConfig = $field[$relationKey];
                    break;
                }
            }
            if (!empty($relationConfig)) {
                $fkReferencedTable = $relationConfig['referenced_table'] ?? $relationConfig['referenced_table_name'] ?? '';
                $displayColumn = $relationConfig['display_column'] ?? $relationConfig['display_column_name'] ?? '';
                $localColumn = $relationConfig['local_column'] ?? $relationConfig['source_column'] ?? $relationConfig['column_name'] ?? '';
                if ($localColumn !== '' && !$this->looksLikeFallbackFieldName($localColumn)) {
                    $label = $localColumn;
                } elseif ($fkReferencedTable !== '') {
                    $label = $fkReferencedTable;
                }
            }
        }

        if ($label !== '' && !$this->looksLikeFallbackFieldName($label)) {
            return ucwords(str_replace('_', ' ', $label));
        }

        if ($fieldName !== '' && !$this->looksLikeFallbackFieldName($fieldName)) {
            return ucwords(str_replace('_', ' ', $fieldName));
        }

        return 'Field';
    }

    private function extractCustomCodePost(): array
    {
        $post = Yii::$app->request->post();
        $modelPost = $post['MasterForm'] ?? [];
        $useCustomCode = (int)($modelPost['use_custom_code'] ?? $post['use_custom_code'] ?? $modelPost['custom_code_mode'] ?? 0) === 1;

        return [
            'use_custom_code' => $useCustomCode ? 1 : 0,
            'custom_html' => (string)($modelPost['custom_html'] ?? $post['custom_html'] ?? ''),
            'custom_css' => (string)($modelPost['custom_css'] ?? $post['custom_css'] ?? ''),
            'custom_js' => (string)($modelPost['custom_js'] ?? $post['custom_js'] ?? ''),
        ];
    }

    private function assignCustomCodeToModel(MasterForm $model, array $customCode): void
    {
        $useCustomCode = !empty($customCode['use_custom_code']) ? 1 : 0;

        if ($model->hasAttribute('custom_html')) {
            $model->custom_html = $useCustomCode ? (string)($customCode['custom_html'] ?? '') : '';
        }
        if ($model->hasAttribute('custom_css')) {
            $model->custom_css = $useCustomCode ? (string)($customCode['custom_css'] ?? '') : '';
        }
        if ($model->hasAttribute('custom_js')) {
            $model->custom_js = $useCustomCode ? (string)($customCode['custom_js'] ?? '') : '';
        }
        if ($model->hasAttribute('use_custom_code')) {
            $model->use_custom_code = $useCustomCode;
        }
        if ($model->hasAttribute('custom_code_mode')) {
            $model->custom_code_mode = $useCustomCode;
        }
    }

    private function syncFormArchitecture(MasterForm $model, ?array $customCode = null): void
    {
        $builderData = $this->normalizeBuilderData($model);
        $fields = $this->extractFieldsFromBuilderData($builderData);
        $previousLayout = $model->getActiveLayout()->one();
        $targetSchema = $this->resolveTargetTableSchema($model);
        $syncDebug = [
            'form_id' => (int)$model->id,
            'target_table_id' => $this->resolveTargetTableId($model),
            'target_table' => null,
            'raw_fields_count' => count($fields),
            'raw_fields' => [],
            'saved_fields' => [],
            'skipped_fields' => [],
        ];
        if ($targetSchema !== null) {
            $syncDebug['target_table'] = $targetSchema->name ?? null;
        }
        if ($customCode === null) {
            $customCode = [
                'use_custom_code' => $model->hasAttribute('use_custom_code') ? (!empty($model->use_custom_code) ? 1 : 0) : (!empty($model->custom_code_mode) ? 1 : 0),
                'custom_html' => $model->hasAttribute('custom_html') ? (string)$model->custom_html : ($previousLayout ? (string)$previousLayout->custom_html : ''),
                'custom_css' => $model->hasAttribute('custom_css') ? (string)$model->custom_css : ($previousLayout ? (string)$previousLayout->custom_css : ''),
                'custom_js' => $model->hasAttribute('custom_js') ? (string)$model->custom_js : ($previousLayout ? (string)$previousLayout->custom_js : ''),
            ];
        }

        MasterFormField::deleteAll(['form_id' => $model->id]);
        MasterFormLayout::deleteAll(['form_id' => $model->id]);
        $sourceTableId = $this->resolveTargetTableId($model);

        foreach ($fields as $index => $fieldData) {
            if (!is_array($fieldData)) {
                $syncDebug['skipped_fields'][] = [
                    'index' => (int)$index,
                    'reason' => 'not_array',
                ];
                continue;
            }
            $syncDebug['raw_fields'][] = [
                'index' => (int)$index,
                'name' => $fieldData['name'] ?? $fieldData['field_name'] ?? $fieldData['field_key'] ?? $fieldData['column_name'] ?? null,
                'label' => $fieldData['label'] ?? $fieldData['field_label'] ?? null,
                'type' => $fieldData['inputType'] ?? $fieldData['type'] ?? $fieldData['field_type'] ?? null,
                'relation_config' => $fieldData['relation_config'] ?? null,
                'is_foreign_key' => !empty($fieldData['is_foreign_key']),
                'excluded' => !empty($fieldData['excluded']),
            ];

            $field = new MasterFormField();
            $fieldName = $this->normalizeFieldName($fieldData, (int)$index, $targetSchema, $sourceTableId);
            if ($this->isSystemFieldDataForModel($fieldData, $model)) {
                $syncDebug['skipped_fields'][] = [
                    'index' => (int)$index,
                    'name' => $fieldName,
                    'reason' => 'system_field',
                    'relation_config' => $fieldData['relation_config'] ?? null,
                ];
                continue;
            }

            $sourceColumn = null;
            if ($sourceTableId > 0 && $fieldName !== '') {
                $sourceColumn = DbTableColumn::find()
                    ->where(['table_id' => $sourceTableId, 'name' => $fieldName])
                    ->one();
            }
            $relationConfig = [];
            foreach (['relation_config', 'relationConfig', 'relation'] as $relationKey) {
                if (isset($fieldData[$relationKey]) && is_array($fieldData[$relationKey])) {
                    $relationConfig = $fieldData[$relationKey];
                    break;
                }
            }
            $isFkField = $sourceColumn !== null && $sourceColumn->hasAttribute('is_foreign_key') && (bool)$sourceColumn->getAttribute('is_foreign_key');
            if (!$isFkField && $targetSchema !== null) {
                $hasFkIndicators = !empty($fieldData['is_foreign_key'])
                    || !empty($fieldData['fk_referenced_table'])
                    || !empty($fieldData['foreign_key_table'])
                    || !empty($fieldData['referenced_table_name'])
                    || !empty($relationConfig)
                    || !empty($fieldData['options']);
                if ($hasFkIndicators && $sourceTableId > 0) {
                    $resolvedFkCol = $this->resolveFkColumnFromRelationConfig($fieldData, $targetSchema, $sourceTableId);
                    if ($resolvedFkCol !== null && $resolvedFkCol !== $fieldName) {
                        $fieldName = $resolvedFkCol;
                        $sourceColumn = DbTableColumn::find()
                            ->where(['table_id' => $sourceTableId, 'name' => $fieldName])
                            ->one();
                        $isFkField = $sourceColumn !== null && $sourceColumn->hasAttribute('is_foreign_key') && (bool)$sourceColumn->getAttribute('is_foreign_key');
                    }
                }
            }
            if ($isFkField) {
                $referencedTable = $sourceColumn->hasAttribute('referenced_table_name') ? (string)$sourceColumn->getAttribute('referenced_table_name') : '';
                $referencedColumn = (string)($fieldData['fk_referenced_column'] ?? $fieldData['referenced_value_column'] ?? $fieldData['value_column'] ?? '');
                if ($referencedColumn === '') {
                    $referencedColumn = $sourceColumn->hasAttribute('referenced_column_name') ? (string)$sourceColumn->getAttribute('referenced_column_name') : '';
                }
                $relationConfig = array_filter(array_merge($relationConfig, [
                    'local_column' => $fieldName,
                    'source_column' => $fieldName,
                    'column_name' => $fieldName,
                    'referenced_table' => $referencedTable,
                    'referenced_table_name' => $referencedTable,
                    'referenced_value_column' => $referencedColumn,
                    'referenced_column' => $referencedColumn,
                    'referenced_column_name' => $referencedColumn,
                    'value_column' => $referencedColumn,
                    'display_column' => (string)($fieldData['fk_display_column'] ?? $fieldData['label_column'] ?? ''),
                ]), static fn($value): bool => $value !== null && $value !== '');
                $fieldData['is_foreign_key'] = true;
                $fieldData['relation_config'] = $relationConfig;
                $fieldData['fk_referenced_table'] = $referencedTable;
                $fieldData['fk_referenced_column'] = $referencedColumn;
                $fieldData['value_column'] = $referencedColumn;
            }

            $fieldType = (string)($fieldData['type'] ?? $fieldData['field_type'] ?? 'text');
            $field->form_id = (int)$model->id;
            $field->field_key = $fieldName;
            $field->field_name = $fieldName;
            $field->field_label = $this->resolveFieldLabel($fieldData, $fieldName);
            $field->field_type = $fieldType;
            $field->component_type = (string)($fieldData['component_type'] ?? $fieldData['inputType'] ?? $fieldType);
            $field->is_required = !empty($fieldData['required'] ?? $fieldData['is_required'] ?? null) ? 1 : 0;
            $field->placeholder = (string)($fieldData['placeholder'] ?? '');
            $field->default_value = isset($fieldData['default_value']) ? (string)$fieldData['default_value'] : null;
            $field->dropdown_source = (string)($fieldData['dropdown_source'] ?? (!empty($fieldData['fk_options']) ? 'foreign_key' : (!empty($fieldData['options']) ? 'static_options' : '')));
            $field->foreign_key_table = isset($fieldData['fk_referenced_table'])
                ? (string)$fieldData['fk_referenced_table']
                : (isset($fieldData['source_table_name'])
                    ? (string)$fieldData['source_table_name']
                    : (isset($relationConfig['referenced_table'])
                        ? (string)$relationConfig['referenced_table']
                        : (isset($relationConfig['referenced_table_name']) ? (string)$relationConfig['referenced_table_name'] : null)));
            $field->foreign_key_column = isset($fieldData['fk_display_column'])
                ? (string)$fieldData['fk_display_column']
                : (isset($fieldData['label_column'])
                    ? (string)$fieldData['label_column']
                    : (isset($relationConfig['display_column'])
                        ? (string)$relationConfig['display_column']
                        : (isset($relationConfig['display_column_name']) ? (string)$relationConfig['display_column_name'] : null)));
            $field->validation_rules = Json::encode([
                'required' => !empty($fieldData['required'] ?? $fieldData['is_required'] ?? null),
                'rules' => $fieldData['validation_rules'] ?? null,
            ]);
            $field->field_config = Json::encode($fieldData);
            $field->field_settings = Json::encode($fieldData);
            $field->sort_order = (int)$index;
            $field->save(false);
            $syncDebug['saved_fields'][] = [
                'index' => (int)$index,
                'name' => $fieldName,
                'column_name' => $fieldName,
                'label' => $field->field_label,
                'type' => $field->field_type,
                'component_type' => $field->component_type,
                'foreign_key_table' => $field->foreign_key_table,
                'foreign_key_column' => $field->foreign_key_column,
                'relation_config' => $fieldData['relation_config'] ?? null,
            ];

        }

        $layout = new MasterFormLayout();
        $layout->form_id = (int)$model->id;
        $layout->layout_name = $model->form_name . ' Layout';
        $layout->layout_type = (string)($model->form_type ?: 'builder');
        $layout->layout_json = Json::encode([
            'form' => $model->getAttributes(),
            'builder' => $builderData,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $layout->custom_html = !empty($customCode['use_custom_code']) ? (string)($customCode['custom_html'] ?? '') : '';
        $layout->custom_css = !empty($customCode['use_custom_code']) ? (string)($customCode['custom_css'] ?? '') : '';
        $layout->custom_js = !empty($customCode['use_custom_code']) ? (string)($customCode['custom_js'] ?? '') : '';
        if ($layout->hasAttribute('use_custom_code')) {
            $layout->use_custom_code = !empty($customCode['use_custom_code']) ? 1 : 0;
        }
        $layout->builder_state = Json::encode($builderData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $layout->is_default = 1;
        $layout->sort_order = 0;
        $layout->save(false);

        $this->assignCustomCodeToModel($model, $customCode);
        $attributes = array_values(array_filter(['custom_html', 'custom_css', 'custom_js', 'use_custom_code', 'custom_code_mode'], fn($attribute) => $model->hasAttribute($attribute)));
        if (!empty($attributes)) {
            $model->save(false, $attributes);
        }
        Yii::info($syncDebug, 'form-render-fields');
    }

    public function actionRelationPickerData($form_id = null, $field_name = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $model = $this->findScopedModel((int)$form_id);
            $field = $this->resolveRelationPickerField($model, (string)$field_name);
            if ($field === null) {
                return ['success' => false, 'message' => 'Field relasi tidak ditemukan.'];
            }

            $config = $this->buildRelationPickerConfig($field);
            if ($config === null) {
                return ['success' => false, 'message' => 'Konfigurasi picker relasi belum valid.'];
            }

            $keyword = trim((string)Yii::$app->request->get('q', ''));
            $page = max(1, (int)Yii::$app->request->get('page', 1));
            $pageSize = min(50, max(1, (int)($config['page_size'] ?? 10)));
            $payload = $this->queryRelationPickerRows($config, $keyword, $page, $pageSize);

            return [
                'success' => true,
                'config' => $config,
                'rows' => $payload['rows'],
                'pagination' => [
                    'page' => $page,
                    'page_size' => $pageSize,
                    'total' => $payload['total'],
                    'has_next' => ($page * $pageSize) < $payload['total'],
                ],
            ];
        } catch (\Throwable $e) {
            Yii::error([
                'relation_picker_data_error' => true,
                'form_id' => $form_id,
                'field_name' => $field_name,
                'error' => $e->getMessage(),
            ], 'relation-picker');
            return ['success' => false, 'message' => 'Gagal memuat data relasi.'];
        }
    }

    public function actionRelationPickerSearch($form_id = null, $field_name = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $model = $this->findScopedModel((int)$form_id);
            $field = $this->resolveRelationPickerField($model, (string)$field_name);
            if ($field === null) {
                return ['success' => false, 'message' => 'Field relasi tidak ditemukan.', 'matches' => []];
            }

            $config = $this->buildRelationPickerConfig($field);
            if ($config === null) {
                return ['success' => false, 'message' => 'Konfigurasi picker relasi belum valid.', 'matches' => []];
            }

            $keyword = trim((string)Yii::$app->request->get('q', ''));
            $limit = min(20, max(1, (int)Yii::$app->request->get('limit', 10)));
            $payload = $this->queryRelationPickerRows($config, $keyword, 1, $limit);

            return [
                'success' => true,
                'matches' => array_map(static function (array $row): array {
                    return [
                        'value' => $row['value'],
                        'label' => $row['label'],
                        'display_text' => $row['label'],
                        'display' => $row['display'],
                    ];
                }, $payload['rows']),
            ];
        } catch (\Throwable $e) {
            Yii::error([
                'relation_picker_search_error' => true,
                'form_id' => $form_id,
                'field_name' => $field_name,
                'error' => $e->getMessage(),
            ], 'relation-picker');
            return ['success' => false, 'message' => 'Gagal mencari data relasi.', 'matches' => []];
        }
    }

    public function actionResolveAutofill($form_id = null, $trigger_field = null, $trigger_value = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $model = $this->findScopedModel((int)$form_id);
            $field = $this->resolveRelationPickerField($model, (string)$trigger_field);
            if ($field === null) {
                return ['success' => false, 'message' => 'Field relasi tidak ditemukan.', 'values' => new \stdClass(), 'display' => ['enabled' => false, 'items' => []]];
            }

            $config = $this->buildRelationPickerConfig($field);
            if ($config === null) {
                return ['success' => false, 'message' => 'Konfigurasi relasi belum valid.', 'values' => new \stdClass(), 'display' => ['enabled' => false, 'items' => []]];
            }

            $triggerValue = trim((string)$trigger_value);
            if ($triggerValue === '') {
                return ['success' => true, 'values' => new \stdClass(), 'display' => ['enabled' => false, 'items' => []]];
            }

            $sourceRow = $this->loadRelationPickerSourceRow($config, $triggerValue);
            if ($sourceRow === null) {
                return ['success' => false, 'message' => 'Data relasi tidak ditemukan.', 'values' => new \stdClass(), 'display' => ['enabled' => false, 'items' => []]];
            }

            $schema = $this->formEngineService->getResolvedFormSchema($model);
            $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
            $values = $this->buildAutofillResponseValues($fields, $field, $sourceRow);
            $display = $this->buildAutofillDisplayPayload($sourceRow, $config, $field);

            return [
                'success' => true,
                'values' => $values,
                'display' => $display,
            ];
        } catch (\Throwable $e) {
            Yii::error([
                'resolve_autofill_error' => true,
                'form_id' => $form_id,
                'trigger_field' => $trigger_field,
                'error' => $e->getMessage(),
            ], 'relation-picker');
            return ['success' => false, 'message' => 'Gagal memuat auto-fill.', 'values' => new \stdClass(), 'display' => ['enabled' => false, 'items' => []]];
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>|null
     */
    private function loadRelationPickerSourceRow(array $config, string $triggerValue): ?array
    {
        $tableName = trim((string)($config['main_table'] ?? ''));
        $valueColumn = trim((string)($config['value_column'] ?? 'id'));
        if ($tableName === '' || $valueColumn === '') {
            return null;
        }

        $query = (new Query())
            ->from($tableName)
            ->where([$valueColumn => $triggerValue])
            ->limit(1);

        $row = $query->one(Yii::$app->db);
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $triggerField
     * @param array<string, mixed> $sourceRow
     * @return array<string, mixed>
     */
    private function buildAutofillResponseValues(array $fields, array $triggerField, array $sourceRow): array
    {
        $values = [];
        $sourceLookup = [];
        foreach ($sourceRow as $columnName => $value) {
            $normalized = $this->normalizeSchemaKey((string)$columnName);
            if ($normalized !== '' && !isset($sourceLookup[$normalized])) {
                $sourceLookup[$normalized] = (string)$columnName;
            }
            $humanized = $this->normalizeSchemaKey($this->humanizePickerColumn((string)$columnName));
            if ($humanized !== '' && !isset($sourceLookup[$humanized])) {
                $sourceLookup[$humanized] = (string)$columnName;
            }
        }

        $triggerCandidates = array_filter(array_unique([
            (string)($triggerField['resolved_name'] ?? ''),
            (string)($triggerField['resolved_column_name'] ?? ''),
            (string)($triggerField['name'] ?? ''),
            (string)($triggerField['field_name'] ?? ''),
            (string)($triggerField['field_key'] ?? ''),
            (string)($triggerField['column_name'] ?? ''),
        ]));

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $field = FormRenderService::normalizeFieldForRender($field, (int)$index);
            $fieldName = trim((string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            if (in_array($fieldName, $triggerCandidates, true)) {
                continue;
            }

            $candidateColumns = [];
            foreach ([
                $fieldName,
                (string)($field['auto_fill_source_column'] ?? ''),
                (string)($field['autofill_source_column'] ?? ''),
                (string)($field['auto_fill_from'] ?? ''),
                (string)($field['autofill_from'] ?? ''),
                (string)($field['source_column'] ?? ''),
                (string)($field['source_column_name'] ?? ''),
                (string)($field['local_column'] ?? ''),
                (string)($field['display_column'] ?? ''),
                (string)($field['value_column'] ?? ''),
                (string)($field['label_column'] ?? ''),
                (string)($field['field_label'] ?? ''),
                (string)($field['label'] ?? ''),
            ] as $candidate) {
                $candidate = trim((string)$candidate);
                if ($candidate !== '') {
                    $candidateColumns[] = $candidate;
                }
            }

            $fieldBehavior = strtolower(trim((string)($field['field_behavior'] ?? $field['behavior'] ?? '')));
            $fieldMeta = $this->extractAutofillFieldConfig($field);
            if (!empty($fieldMeta['source_column'])) {
                $candidateColumns[] = (string)$fieldMeta['source_column'];
            }
            if (!empty($fieldMeta['display_column'])) {
                $candidateColumns[] = (string)$fieldMeta['display_column'];
            }
            if (!empty($fieldMeta['value_column'])) {
                $candidateColumns[] = (string)$fieldMeta['value_column'];
            }
            if (!empty($fieldMeta['source_field'])) {
                $candidateColumns[] = (string)$fieldMeta['source_field'];
            }

            $matchedColumn = $this->matchSourceColumnForAutofill($candidateColumns, $sourceRow, $sourceLookup);
            if ($matchedColumn === null && in_array($fieldBehavior, ['readonly', 'display_only', 'display-only'], true)) {
                $matchedColumn = $this->matchSourceColumnForAutofill(array_keys($sourceRow), $sourceRow, $sourceLookup);
            }

            if ($matchedColumn === null) {
                continue;
            }

            $values[$fieldName] = $sourceRow[$matchedColumn];
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function extractAutofillFieldConfig(array $field): array
    {
        foreach (['auto_fill_rules', 'autofill_rules', 'auto_fill', 'autofill'] as $key) {
            $value = $field[$key] ?? null;
            if (is_array($value)) {
                if ($this->isListArray($value)) {
                    foreach ($value as $rule) {
                        if (!is_array($rule)) {
                            continue;
                        }
                        $candidate = array_filter([
                            'source_column' => $rule['source_column'] ?? $rule['source'] ?? $rule['from'] ?? $rule['column'] ?? null,
                            'display_column' => $rule['display_column'] ?? $rule['display'] ?? null,
                            'value_column' => $rule['value_column'] ?? $rule['value'] ?? null,
                            'source_field' => $rule['source_field'] ?? $rule['field'] ?? null,
                        ], static fn($value): bool => $value !== null && $value !== '');
                        if (!empty($candidate)) {
                            return $candidate;
                        }
                    }
                }

                return array_filter([
                    'source_column' => $value['source_column'] ?? $value['source'] ?? $value['from'] ?? $value['column'] ?? null,
                    'display_column' => $value['display_column'] ?? $value['display'] ?? null,
                    'value_column' => $value['value_column'] ?? $value['value'] ?? null,
                    'source_field' => $value['source_field'] ?? $value['field'] ?? null,
                ], static fn($item): bool => $item !== null && $item !== '');
            }
        }

        return [];
    }

    /**
     * @param array<int, string> $candidateColumns
     * @param array<string, mixed> $sourceRow
     * @param array<string, string> $sourceLookup
     */
    private function matchSourceColumnForAutofill(array $candidateColumns, array $sourceRow, array $sourceLookup): ?string
    {
        foreach (array_values(array_unique(array_filter(array_map('trim', $candidateColumns)))) as $candidate) {
            if (isset($sourceRow[$candidate])) {
                return $candidate;
            }

            $normalized = $this->normalizeSchemaKey($candidate);
            if ($normalized !== '' && isset($sourceLookup[$normalized])) {
                return $sourceLookup[$normalized];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $sourceRow
     * @param array<string, mixed> $config
     * @param array<string, mixed> $triggerField
     * @return array<string, mixed>
     */
    private function buildAutofillDisplayPayload(array $sourceRow, array $config, array $triggerField = []): array
    {
        $items = [];
        foreach ($sourceRow as $columnName => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $items[] = [
                'label' => $this->humanizePickerColumn((string)$columnName),
                'value' => is_scalar($value) ? (string)$value : Json::encode($value),
            ];
        }

        $title = trim((string)($triggerField['label'] ?? $triggerField['field_label'] ?? $config['display_column'] ?? $config['main_table'] ?? 'Detail'));
        return [
            'enabled' => !empty($items),
            'detail_title' => $title !== '' ? $title : 'Detail',
            'items' => $items,
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $dbContext = new ActiveDatabaseContext();
        $dbContext->resolveAndApply();
        $schemaChanged = DatabaseSchemaInitializer::ensureMasterFormStructure(Yii::$app->db);
        if ($schemaChanged) {
            Yii::$app->db->schema->refresh();
        }

        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = $this->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola form.');
            $this->redirect(['project/index']);
            return false;
        }

        return true;
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = MasterForm::findScoped()->with('page');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id = null)
    {
        if ($id === null || (int)$id <= 0) {
            Yii::$app->session->setFlash('error', 'Form tidak ditemukan. Silakan pilih form dari daftar.');
            return $this->redirect(['index']);
        }
        $model = $this->findScopedModel($id);
        if ($this->cleanSystemFieldsFromModel($model)) {
            $model->save(false, ['form_data']);
            $this->syncFormArchitecture($model);
        }
        $logs = [];
        if (Yii::$app->db->getTableSchema(MasterFormActivityLog::tableName(), true) !== null) {
            $logs = MasterFormActivityLog::find()
                ->where(['form_id' => (int)$model->id])
                ->orderBy(['id' => SORT_DESC])
                ->limit(15)
                ->all();
        }
        return $this->render('view', [
            'model' => $model,
            'activityLogs' => $logs,
        ]);
    }

    public function actionCreate()
    {
        $model = new MasterForm();
        $this->assignActiveProject($model);

        if ($model->load(Yii::$app->request->post())) {
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $customCode = $this->extractCustomCodePost();
            $this->assignCustomCodeToModel($model, $customCode);
            $this->assignActiveProject($model);
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            $this->cleanSystemFieldsFromModel($model);
            
            if (!empty($model->table_id)) {
                $model->table_id = (int)$model->table_id;
            }
            
            if (empty($model->slug) && !empty($model->form_name)) {
                $model->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $model->form_name)));
            }
            if ($model->hasAttribute('database_context')) {
                $model->database_context = (string)($dbContext['activeDatabase'] ?? '');
            }
            if ($model->hasAttribute('form_type') && empty($model->form_type)) {
                $model->form_type = 'dynamic';
            }
            
            try {
                if ($model->save()) {
                    $this->syncFormArchitecture($model, $customCode);
                    $this->activityLogService->log($model, 'form_created', 'success', 'Form created and synced.');
                    Yii::$app->session->setFlash('success', 'Form berhasil dibuat dan struktur fields/layout tersimpan.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $e) {
                if (stripos($e->getMessage(), 'form_name') !== false || stripos($e->getMessage(), 'slug') !== false || stripos($e->getMessage(), 'duplicate') !== false) {
                    $model->addError('form_name', 'Nama form sudah dipakai. Gunakan nama form lain.');
                } else {
                    throw $e;
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'pages' => MasterPage::find()
                ->select(['id', 'name', 'slug', 'is_active'])
                ->orderBy(['name' => SORT_ASC, 'id' => SORT_ASC])
                ->all(),
        ]);
    }
    
    public function actionUpdate($id = null)
    {
        if ($id === null || (int)$id <= 0) {
            Yii::$app->session->setFlash('error', 'Form tidak ditemukan. Silakan pilih form dari daftar.');
            return $this->redirect(['index']);
        }
        $model = $this->findScopedModel($id);
        if ($this->cleanSystemFieldsFromModel($model)) {
            $model->save(false, ['form_data']);
            $this->syncFormArchitecture($model);
        }

        if ($model->load(Yii::$app->request->post())) {
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $customCode = $this->extractCustomCodePost();
            $this->assignCustomCodeToModel($model, $customCode);
            $this->assignActiveProject($model);
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            $this->cleanSystemFieldsFromModel($model);
            
            if (!empty($model->table_id)) {
                $model->table_id = (int)$model->table_id;
            }
            
            if (empty($model->slug) && !empty($model->form_name)) {
                $model->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $model->form_name)));
            }
            if ($model->hasAttribute('database_context')) {
                $model->database_context = (string)($dbContext['activeDatabase'] ?? '');
            }
            
            try {
                if ($model->save()) {
                    $this->syncFormArchitecture($model, $customCode);
                    $this->activityLogService->log($model, 'form_updated', 'success', 'Form updated and synced.');
                    Yii::$app->session->setFlash('success', 'Form berhasil diperbarui dan struktur fields/layout disinkronkan.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $e) {
                if (stripos($e->getMessage(), 'form_name') !== false || stripos($e->getMessage(), 'slug') !== false || stripos($e->getMessage(), 'duplicate') !== false) {
                    $model->addError('form_name', 'Nama form sudah dipakai. Gunakan nama form lain.');
                } else {
                    throw $e;
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'pages' => MasterPage::find()
                ->select(['id', 'name', 'slug', 'is_active'])
                ->orderBy(['name' => SORT_ASC, 'id' => SORT_ASC])
                ->all(),
        ]);
    }

    public function actionDelete($id = null)
    {
        if ($id === null || (int)$id <= 0) {
            Yii::$app->session->setFlash('error', 'Form tidak ditemukan. Silakan pilih form dari daftar.');
            return $this->redirect(['index']);
        }
        $model = $this->findScopedModel($id);
        MasterFormField::deleteAll(['form_id' => $model->id]);
        MasterFormLayout::deleteAll(['form_id' => $model->id]);
        $model->delete();
        return $this->redirect(['index']);
    }
    
    public function actionDuplicate($id = null)
    {
        if ($id === null || (int)$id <= 0) {
            Yii::$app->session->setFlash('error', 'Form tidak ditemukan. Silakan pilih form dari daftar.');
            return $this->redirect(['index']);
        }
        $source = $this->findScopedModel($id);
        
        $copy = new MasterForm();
        $copy->form_name = $source->form_name . ' (Copy)';
        $copy->form_data = $source->form_data;
        $copy->form_type = $source->form_type ?? 'dynamic';
        $copy->database_context = $source->database_context ?? null;
        if ($copy->hasAttribute('custom_html') && $source->hasAttribute('custom_html')) {
            $copy->custom_html = $source->custom_html;
        }
        if ($copy->hasAttribute('custom_css') && $source->hasAttribute('custom_css')) {
            $copy->custom_css = $source->custom_css;
        }
        if ($copy->hasAttribute('custom_js') && $source->hasAttribute('custom_js')) {
            $copy->custom_js = $source->custom_js;
        }
        if ($copy->hasAttribute('use_custom_code') && $source->hasAttribute('use_custom_code')) {
            $copy->use_custom_code = $source->use_custom_code;
        }
        $copy->custom_code_mode = $source->custom_code_mode;
        $copy->page_id = $source->page_id;
        $copy->table_id = $source->table_id;
        $this->assignActiveProject($copy);
        $copy->is_active = 0;
        
        if ($copy->save()) {
            $this->syncFormArchitecture($copy);
            return $this->redirect(['view', 'id' => $copy->id]);
        }
        
        return $this->redirect(['view', 'id' => $source->id]);
    }
    
    public function actionPreview($id = null)
    {
        if ($id === null || (int)$id <= 0) {
            Yii::$app->session->setFlash('error', 'Preview form membutuhkan ID form yang valid.');
            return $this->redirect(['index']);
        }
        $model = $this->findScopedModel($id);
        if ($this->cleanSystemFieldsFromModel($model)) {
            $model->save(false, ['form_data']);
            $this->syncFormArchitecture($model);
        }
        $schema = $this->formEngineService->getResolvedFormSchema($model);
        $renderPayload = $this->formRenderService->buildRenderPayload($model, $schema['fields'], $schema['layout']);
        if (!empty($schema['autoSynced'])) {
            $this->activityLogService->log($model, 'auto_sync', 'success', 'Legacy form_data auto-synced to relational tables.');
        }
        $this->activityLogService->log($model, 'preview_opened', 'success', 'Preview opened.');
        return $this->render('preview', [
            'model' => $model,
            'renderPayload' => $renderPayload,
        ]);
    }
    
    public function actionSubmit($id = null)
    {
        if ($id === null || (int)$id <= 0) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Submit form membutuhkan ID form yang valid.'];
            }
            Yii::$app->session->setFlash('error', 'Submit form membutuhkan ID form yang valid.');
            return $this->redirect(['index']);
        }
        $model = $this->findScopedModel($id);
        
        if (Yii::$app->request->isPost) {
            $isEmbedded = (int)Yii::$app->request->post('_embedded', 0) === 1;
            $isAjax = Yii::$app->request->isAjax || $isEmbedded;
            if ($isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            }

            if ($isEmbedded && !$this->canSubmitEmbeddedPageForm((int)$model->id)) {
                $message = 'Form ini belum terhubung ke halaman yang bisa Anda akses.';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }

            // APPLY DATABASE CONTEXT - ini kunci fix!
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $db = Yii::$app->db;
            $dbDsn = $db->dsn;
            
            \Yii::info([
                '=== SUBMIT DEBUG ===' => true,
                'original_dsn' => $dbDsn,
                'database_context' => $dbContext,
            ], 'submit_debug');
            
            $schema = $this->formEngineService->getResolvedFormSchema($model);
            $fields = $schema['fields'];
            $postData = Yii::$app->request->post();
            $submissionToken = trim((string)Yii::$app->request->post('_submit_request_id', ''));
            
            $tableId = $this->resolveTargetTableId($model);
            if (!$tableId) {
                $message = 'Target table not configured for this form.';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $dbTable = DbTable::findOne(['id' => $tableId]);
            if (ProjectSchema::supportsProjectContext() && $model->hasAttribute('project_id') && (int)$model->project_id > 0) {
                $dbTableQuery = DbTable::find()
                    ->where([
                        'id' => $tableId,
                    ])
                    ->andWhere(['project_id' => (int)$model->project_id]);
                $scopedDbTable = $dbTableQuery->one();
                if ($scopedDbTable !== null) {
                    $dbTable = $scopedDbTable;
                }
            }
            if (!$dbTable) {
                $message = 'Target table metadata not found.';
                FormFlowDebugLogger::logSubmit([
                    'host' => Yii::$app->request->hostInfo,
                    'project_id' => $this->getActiveProjectId(),
                    'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                    'form_id' => (int)$model->id,
                    'target_table_id' => $tableId,
                    'resolved_table_name' => null,
                    'metadata_found' => false,
                    'metadata_source' => 'master_form.table_id',
                    'submitted_fields' => array_keys($postData),
                    'system_fields_applied' => [],
                    'insert_result' => 'metadata_missing',
                    'error' => $message,
                ]);
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $tableName = $dbTable->name;
            \Yii::info("Target table: $tableName, DB: $dbDsn", 'submit_debug');

            if ($submissionToken !== '' && $this->hasProcessedSubmissionToken((int)$model->id, $submissionToken)) {
                $message = 'Pengiriman duplikat diabaikan.';
                if ($isAjax) {
                    return [
                        'success' => true,
                        'message' => $message,
                        'duplicate' => true,
                    ];
                }
                Yii::$app->session->setFlash('success', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $columns = $db->schema->getTableSchema($tableName, true);
            if (!$columns) {
                $message = 'Target table "' . $tableName . '" not found in database "' . $dbDsn . '".';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $colNames = array_keys($columns->columns);
            \Yii::info("Table columns found: " . implode(', ', $colNames), 'submit_debug');
            
            \Yii::info("POST data received: " . json_encode(array_keys($postData)), 'submit_debug');
            
            $insertData = [];
            $fieldMappingDebug = [];
            
            foreach ($fields as $fieldIndex => $field) {
                if (!is_array($field)) {
                    continue;
                }
                $rawFieldName = (string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? '');
                $fieldName = $this->normalizeFieldName($field, (int)$fieldIndex, $columns, (int)$tableId);
                $fieldType = $field['type'] ?? 'text';
                $isExcluded = !empty($field['excluded']);
                
                $isFk = !empty($field['is_foreign_key']) || !empty($field['fk_referenced_table']) || !empty($field['foreign_key_table']) || !empty($field['referenced_table_name']) || !empty($field['relation_config']) || !empty($field['relationConfig']) || !empty($field['relation']);
                $postedValue = $this->resolvePostedFieldValue($postData, $field, $fieldName);

                if (!$fieldName || !isset($columns->columns[$fieldName]) || $isExcluded || FormSystemFieldHelper::isSystemFieldData($field)) {
                    $fieldMappingDebug[] = [
                        'raw_field' => $rawFieldName,
                        'label' => (string)($field['label'] ?? $field['field_label'] ?? ''),
                        'resolved_column' => (string)$fieldName,
                        'column_exists' => $fieldName !== '' && isset($columns->columns[$fieldName]),
                        'field_type' => $fieldType,
                        'skipped' => true,
                        'skip_reason' => !$fieldName ? 'empty_resolved_column' : (!isset($columns->columns[$fieldName]) ? 'resolved_column_not_in_schema' : ($isExcluded ? 'excluded' : 'system_field')),
                        'relation_config' => $field['relation_config'] ?? null,
                        'is_foreign_key' => $isFk,
                        'posted_value' => $postedValue,
                    ];
                    if ($isFk && $postedValue !== null) {
                        \Yii::warning([
                            'FK_FIELD_SKIPPED' => true,
                            'raw_field' => $rawFieldName,
                            'resolved_column' => (string)$fieldName,
                            'posted_value' => $postedValue,
                            'schema_columns' => $colNames,
                            'relation_config' => $field['relation_config'] ?? null,
                        ], 'submit_debug');
                    }
                    continue;
                }
                
                $repeatableField = $this->shouldExpandSubmissionField($field);
                if (is_array($postedValue)) {
                    $values = array_values(array_filter(array_map(static fn($value) => is_scalar($value) ? trim((string)$value) : '', $postedValue), static fn(string $value): bool => $value !== ''));
                    if ($repeatableField) {
                        $insertData[$fieldName] = $values;
                    } elseif (!empty($values)) {
                        $insertData[$fieldName] = implode(',', $values);
                    }
                } elseif ($postedValue !== null && $postedValue !== '') {
                    $insertData[$fieldName] = $postedValue;
                }

                $fieldMappingDebug[] = [
                    'raw_field' => (string)($field['original_name'] ?? $rawFieldName),
                    'label' => (string)($field['label'] ?? $field['field_label'] ?? ''),
                    'resolved_column' => (string)$fieldName,
                    'column_name' => (string)$fieldName,
                    'column_exists' => true,
                    'field_type' => $fieldType,
                    'posted_value' => $postedValue,
                    'relation_config' => $field['relation_config'] ?? null,
                    'is_foreign_key' => $isFk,
                ];
            }

            $rawPostedTableData = $this->extractRawPostedTableData($postData, $columns->columns);
            $postedForeignKeyData = $this->extractPostedForeignKeyData($postData, $tableId, $columns->columns);
            if (!empty($rawPostedTableData)) {
                foreach ($rawPostedTableData as $columnName => $postedValue) {
                    if (!array_key_exists($columnName, $insertData)) {
                        $insertData[$columnName] = $postedValue;
                    }
                }
            }
            if (!empty($postedForeignKeyData)) {
                foreach ($postedForeignKeyData as $columnName => $postedValue) {
                    if (!array_key_exists($columnName, $insertData) || $insertData[$columnName] === null || $insertData[$columnName] === '') {
                        $insertData[$columnName] = $postedValue;
                    }
                }
            }
            $resolvedMissingForeignKeys = $this->resolveMissingForeignKeyValues($postData, $tableId, $columns->columns, $insertData);
            if (!empty($resolvedMissingForeignKeys)) {
                foreach ($resolvedMissingForeignKeys as $columnName => $postedValue) {
                    if (!array_key_exists($columnName, $insertData) || $insertData[$columnName] === null || $insertData[$columnName] === '') {
                        $insertData[$columnName] = $postedValue;
                    }
                }
            }
            $preSystemInsertData = $insertData;
            if (empty($preSystemInsertData)) {
                $postedFieldNames = array_keys($postData);
                $formFieldNames = array_column($fields, 'name');
                $message = 'No data extracted.';
                FormFlowDebugLogger::logSubmit([
                    'host' => Yii::$app->request->hostInfo,
                    'project_id' => $this->getActiveProjectId(),
                    'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                    'form_id' => (int)$model->id,
                    'target_table_id' => $tableId,
                    'resolved_table_name' => $tableName,
                    'metadata_found' => true,
                    'metadata_source' => 'master_form.table_id',
                    'submitted_fields' => $postedFieldNames,
                    'system_fields_applied' => [],
                    'insert_result' => 'no_data',
                    'error' => $message,
                ]);
                if ($isAjax) {
                    return ['success' => false, 'message' => $message . ' POST: ' . implode(', ', $postedFieldNames)];
                }
                Yii::$app->session->setFlash('warning', 'No data extracted. POST: ' . implode(', ', $postedFieldNames) . ' | Form fields: ' . implode(', ', $formFieldNames));
                $this->activityLogService->log($model, 'submit', 'warning', 'No submission data extracted.');
                return $this->redirect(['preview', 'id' => $id]);
            }
            $insertData = SystemFieldService::applyCreateValues($insertData, $columns->columns);
            $systemFieldsApplied = array_values(array_diff(array_keys($insertData), array_keys($preSystemInsertData)));
            $fkDebugInfo = [];
            foreach ($fields as $fi) {
                if (is_array($fi) && (!empty($fi['is_foreign_key']) || !empty($fi['fk_referenced_table']) || !empty($fi['relation_config']))) {
                    $fkDebugInfo[] = [
                        'name' => $fi['name'] ?? $fi['field_name'] ?? null,
                        'resolved_column' => isset($fi['resolved_name']) ? $fi['resolved_name'] : (isset($fi['resolved_column_name']) ? $fi['resolved_column_name'] : null),
                        'fk_table' => $fi['fk_referenced_table'] ?? $fi['foreign_key_table'] ?? null,
                        'relation_config' => $fi['relation_config'] ?? null,
                        'in_final_payload' => false,
                    ];
                }
            }
            foreach ($fkDebugInfo as &$fd) {
                if (isset($fd['resolved_column']) && array_key_exists($fd['resolved_column'], $insertData)) {
                    $fd['in_final_payload'] = true;
                    $fd['final_value'] = $insertData[$fd['resolved_column']];
                }
                if (isset($fd['name']) && array_key_exists($fd['name'], $insertData)) {
                    $fd['in_final_payload'] = true;
                    $fd['final_value'] = $insertData[$fd['name']];
                }
            }
            unset($fd);
            Yii::info([
                'target_table' => $tableName,
                'schema_columns' => $colNames,
                'raw_post_keys' => array_keys($postData),
                'raw_post_payload' => $postData,
                'raw_posted_table_data' => $rawPostedTableData,
                'posted_foreign_key_data' => $postedForeignKeyData,
                'resolved_missing_foreign_keys' => $resolvedMissingForeignKeys,
                'normalized_payload' => $insertData,
                'rejected_fields' => array_values(array_diff(array_keys($postData), array_keys($insertData))),
                'field_mapping' => $fieldMappingDebug,
                'fk_debug' => $fkDebugInfo,
            ], 'submit_debug');
            $submissionRows = $this->buildSubmissionRows($fields, $insertData, $columns->columns);
            if (empty($submissionRows)) {
                $submissionRows = [$insertData];
            }

            foreach ($submissionRows as $rowIndex => $rowPayload) {
                $columnError = $this->validateInsertDataColumns($rowPayload, $columns->columns);
                if ($columnError !== null) {
                    FormFlowDebugLogger::logSubmit([
                        'host' => Yii::$app->request->hostInfo,
                        'project_id' => $this->getActiveProjectId(),
                        'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                        'form_id' => (int)$model->id,
                        'target_table_id' => $tableId,
                        'resolved_table_name' => $tableName,
                        'metadata_found' => true,
                        'metadata_source' => 'master_form.table_id',
                        'submitted_fields' => array_keys($postData),
                        'system_fields_applied' => $systemFieldsApplied,
                        'insert_result' => 'schema_mismatch',
                        'error' => $columnError,
                    ]);
                    Yii::warning([
                        'target_table' => $tableName,
                        'schema_columns' => $colNames,
                        'raw_post_keys' => array_keys($postData),
                        'raw_post_payload' => $postData,
                        'normalized_payload' => $rowPayload,
                        'rejected_fields' => array_values(array_diff(array_keys($postData), array_keys($rowPayload))),
                        'field_mapping' => $fieldMappingDebug,
                    ], 'submit_debug');
                    if ($isAjax) {
                        return ['success' => false, 'message' => $columnError];
                    }
                    Yii::$app->session->setFlash('error', $columnError);
                    return $this->redirect(['preview', 'id' => $id]);
                }

                $requiredError = $this->validateRequiredInsertData($rowPayload, $columns->columns);
                if ($requiredError !== null) {
                    if ($isAjax) {
                        return ['success' => false, 'message' => $requiredError];
                    }
                    Yii::$app->session->setFlash('error', $requiredError);
                    return $this->redirect(['preview', 'id' => $id]);
                }

                $lengthError = $this->validateInsertDataLengths($rowPayload, $columns->columns);
                if ($lengthError !== null) {
                    if ($isAjax) {
                        return ['success' => false, 'message' => $lengthError];
                    }
                    Yii::$app->session->setFlash('error', $lengthError);
                    return $this->redirect(['preview', 'id' => $id]);
                }
            }
            
            if (!empty($insertData)) {
                try {
                    if ($submissionToken !== '' && !$this->reserveSubmissionToken((int)$model->id, $submissionToken)) {
                        $message = 'Pengiriman duplikat diabaikan.';
                        if ($isAjax) {
                            return [
                                'success' => true,
                                'message' => $message,
                                'duplicate' => true,
                            ];
                        }
                        Yii::$app->session->setFlash('success', $message);
                        return $this->redirect(['preview', 'id' => $id]);
                    }

                    $dbDsn = $db->dsn;
                    \Yii::info("=== SUBMIT DEBUG ===", 'submit_debug');
                    \Yii::info("DB DSN: $dbDsn", 'submit_debug');
                    \Yii::info("Target table: $tableName", 'submit_debug');
                    \Yii::info("Data to insert: " . json_encode($insertData), 'submit_debug');
                    
                    $transaction = $db->beginTransaction();
                    $insertedRowKeys = [];
                    $insertedRowsCount = 0;
                    try {
                        foreach ($submissionRows as $rowPayload) {
                            $cmd = $db->createCommand()->insert($tableName, $rowPayload);
                            $sql = $cmd->getSql();
                            \Yii::info("SQL: $sql", 'submit_debug');

                            $cmd->execute();
                            $insertedRowsCount++;

                            $insertedRowKey = [];
                            if (!empty($columns->primaryKey)) {
                                $primaryKeys = array_values((array)$columns->primaryKey);
                                if (count($primaryKeys) === 1) {
                                    $primaryKey = (string)$primaryKeys[0];
                                    if (array_key_exists($primaryKey, $rowPayload) && $rowPayload[$primaryKey] !== null && $rowPayload[$primaryKey] !== '') {
                                        $insertedRowKey[$primaryKey] = $rowPayload[$primaryKey];
                                    } else {
                                        $lastInsertId = $db->getLastInsertID();
                                        if ($lastInsertId !== null && $lastInsertId !== '') {
                                            $insertedRowKey[$primaryKey] = is_numeric($lastInsertId) ? (string)(int)$lastInsertId : (string)$lastInsertId;
                                        }
                                    }
                                } else {
                                    foreach ($primaryKeys as $primaryKey) {
                                        $primaryKey = (string)$primaryKey;
                                        if (array_key_exists($primaryKey, $rowPayload) && $rowPayload[$primaryKey] !== null && $rowPayload[$primaryKey] !== '') {
                                            $insertedRowKey[$primaryKey] = $rowPayload[$primaryKey];
                                        }
                                    }
                                }
                            }
                            if (!empty($insertedRowKey)) {
                                $insertedRowKeys[] = $insertedRowKey;
                            }
                        }
                        $transaction->commit();
                    } catch (\Throwable $rowException) {
                        if ($transaction->isActive) {
                            $transaction->rollBack();
                        }
                        throw $rowException;
                    }
                    FormFlowDebugLogger::logSubmit([
                        'host' => Yii::$app->request->hostInfo,
                        'project_id' => $this->getActiveProjectId(),
                        'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                        'form_id' => (int)$model->id,
                        'target_table_id' => $tableId,
                        'resolved_table_name' => $tableName,
                        'metadata_found' => true,
                        'metadata_source' => 'master_form.table_id',
                        'submitted_fields' => array_keys($postData),
                        'system_fields_applied' => $systemFieldsApplied,
                        'insert_result' => count($submissionRows) > 1 ? 'success_multiple_rows' : 'success',
                        'inserted_rows' => $insertedRowsCount,
                        'error' => null,
                    ]);
                    
                    \Yii::info("Insert executed successfully", 'submit_debug');
                    
                    $colNames = array_keys($columns->columns);
                    $orderBy = in_array('id', $colNames) ? 'ORDER BY id DESC' : (in_array('created_at', $colNames) ? 'ORDER BY created_at DESC' : '');
                    if ($orderBy) {
                        $checkRows = $db->createCommand("SELECT * FROM $tableName $orderBy LIMIT 1")->queryAll();
                        \Yii::info("Last row after insert: " . json_encode($checkRows), 'submit_debug');
                    }

                    $successMessage = $insertedRowsCount > 1
                        ? $insertedRowsCount . ' data berhasil disimpan.'
                        : 'Data berhasil dikirim.';
                    if ($isAjax) {
                        if ($submissionToken !== '') {
                            $this->markSubmissionTokenProcessed((int)$model->id, $submissionToken);
                        }
                        return [
                            'success' => true,
                            'message' => $successMessage,
                            'table' => $tableName,
                            'tableName' => $tableName,
                            'tableId' => (int)$tableId,
                            'insertedData' => $insertData,
                            'insertedRows' => $submissionRows,
                            'insertedRowsCount' => $insertedRowsCount,
                            'insertedRowKey' => !empty($insertedRowKeys) ? $insertedRowKeys[0] : null,
                            'insertedRowKeys' => !empty($insertedRowKeys) ? $insertedRowKeys : null,
                        ];
                    }
                    if ($submissionToken !== '') {
                        $this->markSubmissionTokenProcessed((int)$model->id, $submissionToken);
                    }
                    $savedMessage = $insertedRowsCount > 1
                        ? $insertedRowsCount . ' data berhasil disimpan.'
                        : 'Data saved! Fields: ' . implode(', ', array_keys($insertData));
                    Yii::$app->session->setFlash('success', $savedMessage);
                    $this->activityLogService->log($model, 'submit', 'success', 'Submission saved to target table.', [
                        'target_table' => $tableName,
                        'fields' => array_keys($insertData),
                        'rows_inserted' => $insertedRowsCount,
                    ]);
                } catch (\Exception $e) {
                    if ($submissionToken !== '') {
                        $this->releaseSubmissionToken((int)$model->id, $submissionToken);
                    }
                    $message = $this->buildFriendlySaveErrorMessage($e, $columns->columns);
                    FormFlowDebugLogger::logSubmit([
                        'host' => Yii::$app->request->hostInfo,
                        'project_id' => $this->getActiveProjectId(),
                        'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                        'form_id' => (int)$model->id,
                        'target_table_id' => $tableId,
                        'resolved_table_name' => $tableName,
                        'metadata_found' => true,
                        'metadata_source' => 'master_form.table_id',
                        'submitted_fields' => array_keys($postData),
                        'system_fields_applied' => $systemFieldsApplied,
                        'insert_result' => 'error',
                        'error' => $e->getMessage(),
                    ]);
                    if ($isAjax) {
                        return ['success' => false, 'message' => $message];
                    }
                    Yii::$app->session->setFlash('error', $message);
                    $this->activityLogService->log($model, 'submit', 'failed', 'Submission failed: ' . $e->getMessage(), [
                        'target_table' => $tableName,
                    ]);
                }
            } else {
                $postedFieldNames = array_keys($postData);
                $formFieldNames = array_column($fields, 'name');
                $message = 'No data extracted.';
                FormFlowDebugLogger::logSubmit([
                    'host' => Yii::$app->request->hostInfo,
                    'project_id' => $this->getActiveProjectId(),
                    'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                    'form_id' => (int)$model->id,
                    'target_table_id' => $tableId,
                    'resolved_table_name' => $tableName,
                    'metadata_found' => true,
                    'metadata_source' => 'master_form.table_id',
                    'submitted_fields' => $postedFieldNames,
                    'system_fields_applied' => $systemFieldsApplied,
                    'insert_result' => 'no_data',
                    'error' => $message,
                ]);
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('warning', 'No data extracted. POST: ' . implode(', ', $postedFieldNames) . ' | Form fields: ' . implode(', ', $formFieldNames));
                $this->activityLogService->log($model, 'submit', 'warning', 'No submission data extracted.');
            }
            
            if ($isAjax) {
                return ['success' => false, 'message' => 'Submit tidak diproses.'];
            }
            return $this->redirect(['preview', 'id' => $id]);
        }
        
        return $this->redirect(['preview', 'id' => $id]);
    }

    private function resolvePostedFieldValue(array $postData, array $field, string $fieldName)
    {
        $relationConfig = [];
        foreach (['relation_config', 'relationConfig', 'relation'] as $relationKey) {
            if (isset($field[$relationKey]) && is_array($field[$relationKey])) {
                $relationConfig = $field[$relationKey];
                break;
            }
        }

        $candidates = array_filter(array_unique([
            $fieldName,
            $fieldName !== '' ? '__fk_display_' . $fieldName : '',
            $fieldName !== '' ? '__fk_submit_' . $fieldName : '',
            (string)($field['resolved_name'] ?? ''),
            !empty($field['resolved_name']) ? '__fk_display_' . (string)$field['resolved_name'] : '',
            !empty($field['resolved_name']) ? '__fk_submit_' . (string)$field['resolved_name'] : '',
            (string)($field['resolved_column_name'] ?? ''),
            !empty($field['resolved_column_name']) ? '__fk_display_' . (string)$field['resolved_column_name'] : '',
            !empty($field['resolved_column_name']) ? '__fk_submit_' . (string)$field['resolved_column_name'] : '',
            (string)($field['name'] ?? ''),
            !empty($field['name']) ? '__fk_display_' . (string)$field['name'] : '',
            !empty($field['name']) ? '__fk_submit_' . (string)$field['name'] : '',
            (string)($field['field_name'] ?? ''),
            !empty($field['field_name']) ? '__fk_display_' . (string)$field['field_name'] : '',
            !empty($field['field_name']) ? '__fk_submit_' . (string)$field['field_name'] : '',
            (string)($field['column_name'] ?? ''),
            !empty($field['column_name']) ? '__fk_display_' . (string)$field['column_name'] : '',
            !empty($field['column_name']) ? '__fk_submit_' . (string)$field['column_name'] : '',
            (string)($field['field_key'] ?? ''),
            !empty($field['field_key']) ? '__fk_display_' . (string)$field['field_key'] : '',
            !empty($field['field_key']) ? '__fk_submit_' . (string)$field['field_key'] : '',
            (string)($field['original_column'] ?? ''),
            !empty($field['original_column']) ? '__fk_display_' . (string)$field['original_column'] : '',
            !empty($field['original_column']) ? '__fk_submit_' . (string)$field['original_column'] : '',
            (string)($field['local_column'] ?? ''),
            !empty($field['local_column']) ? '__fk_display_' . (string)$field['local_column'] : '',
            !empty($field['local_column']) ? '__fk_submit_' . (string)$field['local_column'] : '',
            (string)($field['source_column'] ?? ''),
            !empty($field['source_column']) ? '__fk_display_' . (string)$field['source_column'] : '',
            !empty($field['source_column']) ? '__fk_submit_' . (string)$field['source_column'] : '',
            (string)($field['source_column_name'] ?? ''),
            !empty($field['source_column_name']) ? '__fk_display_' . (string)$field['source_column_name'] : '',
            !empty($field['source_column_name']) ? '__fk_submit_' . (string)$field['source_column_name'] : '',
            (string)($field['label'] ?? ''),
            (string)($field['field_label'] ?? ''),
            (string)($field['labelText'] ?? ''),
            (string)($relationConfig['local_column'] ?? ''),
            !empty($relationConfig['local_column']) ? '__fk_display_' . (string)$relationConfig['local_column'] : '',
            !empty($relationConfig['local_column']) ? '__fk_submit_' . (string)$relationConfig['local_column'] : '',
            (string)($relationConfig['source_column'] ?? ''),
            !empty($relationConfig['source_column']) ? '__fk_display_' . (string)$relationConfig['source_column'] : '',
            !empty($relationConfig['source_column']) ? '__fk_submit_' . (string)$relationConfig['source_column'] : '',
            (string)($relationConfig['column_name'] ?? ''),
            !empty($relationConfig['column_name']) ? '__fk_display_' . (string)$relationConfig['column_name'] : '',
            !empty($relationConfig['column_name']) ? '__fk_submit_' . (string)$relationConfig['column_name'] : '',
            (string)($relationConfig['original_column'] ?? ''),
            !empty($relationConfig['original_column']) ? '__fk_display_' . (string)$relationConfig['original_column'] : '',
            !empty($relationConfig['original_column']) ? '__fk_submit_' . (string)$relationConfig['original_column'] : '',
            (string)($relationConfig['field_name'] ?? ''),
            !empty($relationConfig['field_name']) ? '__fk_display_' . (string)$relationConfig['field_name'] : '',
            !empty($relationConfig['field_name']) ? '__fk_submit_' . (string)$relationConfig['field_name'] : '',
            (string)($relationConfig['field_key'] ?? ''),
            !empty($relationConfig['field_key']) ? '__fk_display_' . (string)$relationConfig['field_key'] : '',
            !empty($relationConfig['field_key']) ? '__fk_submit_' . (string)$relationConfig['field_key'] : '',
        ]));

        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $postData)) {
                $value = $postData[$candidate];
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            $normalizedCandidate = $this->normalizeSubmitKey((string)$candidate);
            foreach ($postData as $postedKey => $postedValue) {
                if (!is_string($postedKey)) {
                    continue;
                }

                if ($this->normalizeSubmitKey($postedKey) === $normalizedCandidate) {
                    if ($postedValue !== null && $postedValue !== '') {
                        return $postedValue;
                    }
                }
            }
        }

        return null;
    }

    private function shouldExpandSubmissionField(array $field): bool
    {
        $candidates = [
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
        ];

        foreach ($candidates as $candidate) {
            if (is_bool($candidate) && $candidate) {
                return true;
            }
            if (is_string($candidate)) {
                $normalized = strtolower(trim($candidate));
                if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                    return true;
                }
            }
            if (is_int($candidate) && $candidate === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $insertData
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     * @return array<int, array<string, mixed>>
     */
    private function buildSubmissionRows(array $fields, array $insertData, array $schemaColumns): array
    {
        $repeatFieldName = null;
        $repeatValues = [];

        if ($repeatFieldName === null) {
            foreach ($fields as $fieldIndex => $field) {
                if (!is_array($field)) {
                    continue;
                }

                if (!$this->shouldExpandSubmissionField($field)) {
                    continue;
                }

                $fieldName = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
                if ($fieldName === '') {
                    $fieldName = $this->normalizeFieldName($field, (int)$fieldIndex, null, 0);
                }
                if ($fieldName === '' || !array_key_exists($fieldName, $insertData) || !is_array($insertData[$fieldName])) {
                    continue;
                }

                $repeatFieldName = $fieldName;
                $repeatValues = $this->normalizeSubmittedArrayValues($insertData[$fieldName]);
                break;
            }
        }

        if ($repeatFieldName === null || empty($repeatValues)) {
            $singleRow = [];
            foreach ($insertData as $columnName => $value) {
                if (is_array($value)) {
                    $singleRow[$columnName] = implode(',', array_map(static fn($item) => is_scalar($item) ? (string)$item : '', $value));
                    continue;
                }
                $singleRow[$columnName] = $value;
            }

            return [$singleRow];
        }

        $rows = [];
        foreach ($repeatValues as $repeatValue) {
            $row = [];
            foreach ($insertData as $columnName => $value) {
                if ($columnName === $repeatFieldName) {
                    $row[$columnName] = $repeatValue;
                    continue;
                }

                if (is_array($value)) {
                    $row[$columnName] = implode(',', array_map(static fn($item) => is_scalar($item) ? (string)$item : '', $value));
                    continue;
                }

                $row[$columnName] = $value;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeSubmittedArrayValues(array $values): array
    {
        return array_values(array_filter(array_map(static function ($value): string {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            if (is_scalar($value)) {
                return trim((string)$value);
            }
            return '';
        }, $values), static fn(string $value): bool => $value !== ''));
    }

    private function extractRawPostedTableData(array $postData, array $columns): array
    {
        $data = [];
        foreach ($columns as $columnName => $column) {
            if ($this->isSubmitSystemColumn((string)$columnName, $column)) {
                continue;
            }

            $postedValue = $this->resolvePostedColumnValue($postData, (string)$columnName);
            if ($postedValue === null || $postedValue === '') {
                continue;
            }

            $data[(string)$columnName] = is_array($postedValue) ? implode(',', $postedValue) : $postedValue;
        }

        return $data;
    }

    private function resolvePostedColumnValue(array $postData, string $columnName)
    {
        if (array_key_exists($columnName, $postData)) {
            $exactValue = $postData[$columnName];
            if ($exactValue !== null && $exactValue !== '') {
                return $exactValue;
            }
        }

        $displayKey = '__fk_display_' . $columnName;
        $submitKey = '__fk_submit_' . $columnName;
        $baseAlias = $this->baseForeignKeyAlias($columnName);
        if (array_key_exists($displayKey, $postData)) {
            $displayValue = $postData[$displayKey];
            if ($displayValue !== null && $displayValue !== '') {
                return $displayValue;
            }
        }
        if (array_key_exists($submitKey, $postData)) {
            $submitValue = $postData[$submitKey];
            if ($submitValue !== null && $submitValue !== '') {
                return $submitValue;
            }
        }
        if ($baseAlias !== '' && $baseAlias !== $columnName && array_key_exists($baseAlias, $postData)) {
            $baseValue = $postData[$baseAlias];
            if ($baseValue !== null && $baseValue !== '') {
                return $baseValue;
            }
        }
        if ($baseAlias !== '' && $baseAlias !== $columnName && array_key_exists('__fk_display_' . $baseAlias, $postData)) {
            $baseDisplayValue = $postData['__fk_display_' . $baseAlias];
            if ($baseDisplayValue !== null && $baseDisplayValue !== '') {
                return $baseDisplayValue;
            }
        }
        if ($baseAlias !== '' && $baseAlias !== $columnName && array_key_exists('__fk_submit_' . $baseAlias, $postData)) {
            $baseSubmitValue = $postData['__fk_submit_' . $baseAlias];
            if ($baseSubmitValue !== null && $baseSubmitValue !== '') {
                return $baseSubmitValue;
            }
        }

        $normalizedColumn = $this->normalizeSubmitKey($columnName);
        $normalizedBaseAlias = $baseAlias !== '' ? $this->normalizeSubmitKey($baseAlias) : '';
        foreach ($postData as $key => $value) {
            $normalizedKey = $this->normalizeSubmitKey((string)$key);
            if ($normalizedKey === $normalizedColumn
                || $normalizedKey === $this->normalizeSubmitKey($displayKey)
                || $normalizedKey === $this->normalizeSubmitKey($submitKey)
                || ($normalizedBaseAlias !== '' && $normalizedKey === $normalizedBaseAlias)
                || ($normalizedBaseAlias !== '' && $normalizedKey === $this->normalizeSubmitKey('__fk_display_' . $baseAlias))
                || ($normalizedBaseAlias !== '' && $normalizedKey === $this->normalizeSubmitKey('__fk_submit_' . $baseAlias))
                || str_starts_with($normalizedKey, $normalizedColumn . '_')
                || str_ends_with($normalizedKey, '_' . $normalizedColumn)
            ) {
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $postData
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     * @return array<string, mixed>
     */
    private function extractPostedForeignKeyData(array $postData, int $tableId, array $schemaColumns): array
    {
        if ($tableId <= 0) {
            return [];
        }

        $fkColumns = DbTableColumn::find()
            ->where(['table_id' => $tableId, 'is_foreign_key' => true])
            ->all();

        if (empty($fkColumns)) {
            return [];
        }

        $data = [];
        foreach ($fkColumns as $fkColumn) {
            $columnName = trim((string)$fkColumn->name);
            if ($columnName === '' || !isset($schemaColumns[$columnName]) || $this->isSubmitSystemColumn($columnName, $schemaColumns[$columnName])) {
                continue;
            }

            $candidateKeys = array_filter(array_unique([
                $columnName,
                '__fk_display_' . $columnName,
                '__fk_submit_' . $columnName,
                $this->baseForeignKeyAlias($columnName),
                ($base = $this->baseForeignKeyAlias($columnName)) !== '' ? '__fk_display_' . $base : '',
                ($base = $this->baseForeignKeyAlias($columnName)) !== '' ? '__fk_submit_' . $base : '',
                trim((string)($fkColumn->label ?? '')),
                trim((string)$fkColumn->getAttribute('referenced_table_name')),
                ($refTable = trim((string)$fkColumn->getAttribute('referenced_table_name'))) !== '' ? '__fk_display_' . $refTable : '',
                ($refTable = trim((string)$fkColumn->getAttribute('referenced_table_name'))) !== '' ? '__fk_submit_' . $refTable : '',
            ]));

            $value = $this->resolvePostedValueFromCandidates($postData, $candidateKeys);
            if ($value === null || $value === '') {
                continue;
            }

            $data[$columnName] = is_array($value) ? implode(',', $value) : $value;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $postData
     * @param array<int, string> $candidates
     * @return mixed|null
     */
    private function resolvePostedValueFromCandidates(array $postData, array $candidates)
    {
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (array_key_exists($candidate, $postData)) {
                $value = $postData[$candidate];
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            $normalizedCandidate = $this->normalizeSubmitKey($candidate);
            foreach ($postData as $postedKey => $postedValue) {
                if (!is_string($postedKey)) {
                    continue;
                }

                $normalizedPostedKey = $this->normalizeSubmitKey($postedKey);
                if (
                    $normalizedPostedKey === $normalizedCandidate
                    || str_starts_with($normalizedPostedKey, $normalizedCandidate . '_')
                    || str_ends_with($normalizedPostedKey, '_' . $normalizedCandidate)
                ) {
                    if ($postedValue !== null && $postedValue !== '') {
                        return $postedValue;
                    }
                }
            }
        }

        return null;
    }

    private function baseForeignKeyAlias(string $columnName): string
    {
        $normalized = trim($columnName);
        if ($normalized === '') {
            return '';
        }

        if (str_ends_with(strtolower($normalized), '_id')) {
            return substr($normalized, 0, -3);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $postData
     * @param array<string, \yii\db\ColumnSchema> $schemaColumns
     * @param array<string, mixed> $insertData
     * @return array<string, mixed>
     */
    private function resolveMissingForeignKeyValues(array $postData, int $tableId, array $schemaColumns, array $insertData): array
    {
        if ($tableId <= 0) {
            return [];
        }

        $fkColumns = DbTableColumn::find()
            ->where(['table_id' => $tableId, 'is_foreign_key' => true])
            ->all();

        if (empty($fkColumns)) {
            return [];
        }

        $resolved = [];
        $missingColumns = [];
        foreach ($fkColumns as $fkColumn) {
            $columnName = trim((string)$fkColumn->name);
            if ($columnName === '' || !isset($schemaColumns[$columnName])) {
                continue;
            }

            if (array_key_exists($columnName, $insertData) && $insertData[$columnName] !== null && $insertData[$columnName] !== '') {
                continue;
            }

            $missingColumns[] = $fkColumn;
        }

        if (empty($missingColumns)) {
            return [];
        }

        $unusedPostedScalars = $this->collectUnusedPostedScalars($postData, $insertData);
        foreach ($missingColumns as $fkColumn) {
            $columnName = trim((string)$fkColumn->name);
            $baseAlias = $this->baseForeignKeyAlias($columnName);
            $referencedTable = trim((string)$fkColumn->getAttribute('referenced_table_name'));
            $label = trim((string)($fkColumn->label ?? ''));

            $matchedValue = null;
            foreach ($unusedPostedScalars as $postedKey => $postedValue) {
                $normalizedPostedKey = $this->normalizeSubmitKey($postedKey);
                $candidates = array_filter([
                    $this->normalizeSubmitKey($columnName),
                    $baseAlias !== '' ? $this->normalizeSubmitKey($baseAlias) : '',
                    $referencedTable !== '' ? $this->normalizeSubmitKey($referencedTable) : '',
                    $label !== '' ? $this->normalizeSubmitKey($label) : '',
                ]);

                foreach ($candidates as $candidate) {
                    if (
                        $normalizedPostedKey === $candidate
                        || str_contains($normalizedPostedKey, $candidate)
                        || str_contains($candidate, $normalizedPostedKey)
                    ) {
                        $matchedValue = $postedValue;
                        unset($unusedPostedScalars[$postedKey]);
                        break 2;
                    }
                }
            }

            if (($matchedValue === null || $matchedValue === '') && count($missingColumns) === 1 && count($unusedPostedScalars) === 1) {
                $matchedValue = reset($unusedPostedScalars);
                $firstKey = array_key_first($unusedPostedScalars);
                if ($firstKey !== null) {
                    unset($unusedPostedScalars[$firstKey]);
                }
            }

            if ($matchedValue !== null && $matchedValue !== '') {
                $resolved[$columnName] = $matchedValue;
            }
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $postData
     * @param array<string, mixed> $insertData
     * @return array<string, scalar>
     */
    private function collectUnusedPostedScalars(array $postData, array $insertData): array
    {
        $usedKeys = [];
        foreach (array_keys($insertData) as $insertKey) {
            $usedKeys[$this->normalizeSubmitKey((string)$insertKey)] = true;
            $usedKeys[$this->normalizeSubmitKey('__fk_display_' . (string)$insertKey)] = true;
            $usedKeys[$this->normalizeSubmitKey('__fk_submit_' . (string)$insertKey)] = true;
            $baseAlias = $this->baseForeignKeyAlias((string)$insertKey);
            if ($baseAlias !== '') {
                $usedKeys[$this->normalizeSubmitKey($baseAlias)] = true;
                $usedKeys[$this->normalizeSubmitKey('__fk_display_' . $baseAlias)] = true;
                $usedKeys[$this->normalizeSubmitKey('__fk_submit_' . $baseAlias)] = true;
            }
        }

        $ignoredKeys = [
            Yii::$app->request->csrfParam,
            '_csrf',
            '_embedded',
            'render_context',
            'return_url',
            'page_id',
            'menu_id',
            'project_id',
            'workspace_role',
        ];

        $result = [];
        foreach ($postData as $postedKey => $postedValue) {
            if (!is_string($postedKey) || in_array($postedKey, $ignoredKeys, true)) {
                continue;
            }
            if (is_array($postedValue) || $postedValue === null || $postedValue === '') {
                continue;
            }

            $normalizedKey = $this->normalizeSubmitKey($postedKey);
            if ($normalizedKey === '' || isset($usedKeys[$normalizedKey])) {
                continue;
            }

            $result[$postedKey] = $postedValue;
        }

        return $result;
    }

    private function isSubmitSystemColumn(string $columnName, $column): bool
    {
        $systemColumns = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
            'deleted_by',
            'project_id',
            'workspace_id',
        ];

        return in_array($columnName, $systemColumns, true) || !empty($column->isPrimaryKey) || !empty($column->autoIncrement);
    }

    private function canSubmitEmbeddedPageForm(int $formId): bool
    {
        $renderContext = (string)Yii::$app->request->post('render_context', '');
        if ($renderContext !== 'page_content') {
            return true;
        }

        $pageId = (int)Yii::$app->request->post('page_id', Yii::$app->request->get('page_id', 0));
        if ($pageId <= 0) {
            $menuId = (int)Yii::$app->request->post('menu_id', Yii::$app->request->get('menu_id', 0));
            if ($menuId <= 0) {
                $menuId = (int)Yii::$app->session->get('active_menu', 0);
            }
            if ($menuId > 0) {
                $menu = MasterMenu::findOne($menuId);
                if ($menu !== null && !empty($menu->page_id)) {
                    $pageId = (int)$menu->page_id;
                }
            }
        }
        if ($formId <= 0 || $pageId <= 0) {
            return false;
        }

        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        if ($projectId === null) {
            return false;
        }

        $permissionService = new ProjectPermissionService();
        return $permissionService->canUseFormAsPageContent($formId, $pageId, $projectId)
            || $permissionService->canUseLegacyFormAsPageContent($formId, $pageId, $projectId);
    }
    
}
