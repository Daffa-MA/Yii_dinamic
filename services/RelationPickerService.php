<?php

namespace app\services;

use app\models\MasterForm;
use app\models\DbTable;
use app\models\DbTableColumn;
use Yii;
use yii\db\Query;
use yii\helpers\Json;

/**
 * RelationPickerService — clean, metadata-driven implementation of the
 * Interactive Modal Search (Relation Picker) module.
 *
 * Architecture layers:
 *   1. Field Resolver       → resolve the field from the form schema
 *   2. Config Resolver      → build picker config from field metadata (metadata-driven)
 *   3. Query Builder        → build & execute the search query
 *   4. Response Builder     → format rows into final {value, label, display} shape
 *   5. FK Resolver          → resolve foreign-key metadata & display-column lookup
 *   6. Autofill Resolver    → resolve autofill data for a selected row
 *
 * No hardcoded table names, no field-name-specific switches, no duplicate logic.
 * Every resolver is metadata-driven from DbTableColumn + picker_config metadata.
 */
class RelationPickerService
{
    private FormEngineService $formEngineService;
    private DynamicFormBehaviorService $dynamicFormBehaviorService;

    public function __construct()
    {
        $this->formEngineService = new FormEngineService();
        $this->dynamicFormBehaviorService = new DynamicFormBehaviorService();
    }

    // =========================================================================
    //  PUBLIC API — called from MasterFormController actions
    // =========================================================================

    /**
     * Get paginated picker data for the Interactive Modal Search grid.
     *
     * @return array{success: bool, config?: array, rows?: array, pagination?: array, message?: string}
     */
    public function getPickerData(int $formId, string $fieldName, string $keyword = '', int $page = 1, int $pageSize = 10): array
    {
        $model = $this->loadForm($formId);
        if ($model === null) {
            return ['success' => false, 'message' => 'Form tidak ditemukan.'];
        }

        $field = $this->resolveField($model, $fieldName);
        if ($field === null) {
            return ['success' => false, 'message' => 'Field relasi tidak ditemukan.'];
        }

        $config = $this->resolveConfig($field);
        if ($config === null) {
            return ['success' => false, 'message' => 'Konfigurasi picker relasi belum valid.'];
        }

        $pageSize = min(50, max(1, $pageSize));
        $payload = $this->queryData($config, $keyword, $page, $pageSize);

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
    }

    /**
     * Quick-search for autocomplete. Returns up to $limit matches.
     *
     * @return array{success: bool, matches?: array, message?: string}
     */
    public function search(int $formId, string $fieldName, string $keyword = '', int $limit = 10): array
    {
        $model = $this->loadForm($formId);
        if ($model === null) {
            return ['success' => false, 'message' => 'Form tidak ditemukan.', 'matches' => []];
        }

        $field = $this->resolveField($model, $fieldName);
        if ($field === null) {
            return ['success' => false, 'message' => 'Field relasi tidak ditemukan.', 'matches' => []];
        }

        $config = $this->resolveConfig($field);
        if ($config === null) {
            return ['success' => false, 'message' => 'Konfigurasi picker relasi belum valid.', 'matches' => []];
        }

        $limit = min(20, max(1, $limit));
        $payload = $this->queryData($config, $keyword, 1, $limit);

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
    }

    /**
     * Resolve the display label for a single relation value (used to hydrate
     * the picker display for an already-selected value in custom-code /
     * page-source mode, where the stored markup is not regenerated).
     *
     * @return array{success: bool, value?: string, label?: string, message?: string}
     */
    public function resolveLabel(int $formId, string $fieldName, string $value): array
    {
        $model = $this->loadForm($formId);
        if ($model === null) {
            return ['success' => false, 'message' => 'Form tidak ditemukan.'];
        }

        $field = $this->resolveField($model, $fieldName);
        if ($field === null) {
            return ['success' => false, 'message' => 'Field relasi tidak ditemukan.'];
        }

        $config = $this->resolveConfig($field);
        if ($config === null) {
            return ['success' => false, 'message' => 'Konfigurasi picker relasi belum valid.'];
        }

        $value = trim($value);
        if ($value === '') {
            return ['success' => true, 'value' => '', 'label' => ''];
        }

        $row = $this->loadSourceRow($config, $value);
        if ($row === null) {
            return ['success' => true, 'value' => $value, 'label' => ''];
        }

        $valueColumn = (string)($config['value_column'] ?? 'id');
        $displayColumn = (string)($config['display_column'] ?? $valueColumn);
        $label = (string)($row[$displayColumn] ?? $row[$valueColumn] ?? $value);

        return [
            'success' => true,
            'value' => $value,
            'label' => $label !== '' ? $label : $value,
        ];
    }

    /**
     * Resolve autofill data when a relation picker value is selected.
     *
     * @return array{success: bool, values?: \stdClass, display?: array, readonly_fields?: array, labels?: array, message?: string}
     */
    public function resolveAutofill(int $formId, string $triggerField, string $triggerValue): array
    {
        $model = $this->loadForm($formId);
        if ($model === null) {
            return $this->emptyAutofillResponse('Form tidak ditemukan.');
        }

        $field = $this->resolveField($model, $triggerField);
        if ($field === null) {
            return $this->emptyAutofillResponse('Field relasi tidak ditemukan.');
        }

        $config = $this->resolveConfig($field);
        if ($config === null) {
            return $this->emptyAutofillResponse('Konfigurasi relasi belum valid.');
        }

        $triggerValue = trim($triggerValue);
        if ($triggerValue === '') {
            return $this->emptyAutofillResponse();
        }

        $sourceRow = $this->loadSourceRow($config, $triggerValue);
        if ($sourceRow === null) {
            return $this->emptyAutofillResponse('Data relasi tidak ditemukan.');
        }

        $candidateRows = $this->buildAutofillCandidates($config, $sourceRow);

        $schema = $this->formEngineService->getResolvedFormSchema($model);
        $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];

        $resolution = $this->buildAutofillValues($fields, $field, $candidateRows);

        $detailCardConfig = $this->dynamicFormBehaviorService->extractDetailCardConfig($field);
        $display = $detailCardConfig !== null
            ? $this->dynamicFormBehaviorService->buildDetailCardDisplayPayload($detailCardConfig, $candidateRows, $field)
            : ['enabled' => false, 'items' => []];

        return [
            'success' => true,
            'values' => $resolution['values'],
            'readonly_fields' => $resolution['readonly_fields'],
            'labels' => $resolution['labels'],
            'display' => $display,
            'trigger_label' => $this->buildTriggerLabel($config, $sourceRow),
        ];
    }

    /**
     * Build the display label for the trigger (the row currently selected in the
     * picker), so the picker display input can be hydrated at load time.
     */
    private function buildTriggerLabel(array $config, array $sourceRow): string
    {
        $valueColumn = (string)($config['value_column'] ?? 'id');
        $displayColumn = (string)($config['display_column'] ?? $valueColumn);
        $label = (string)($sourceRow[$displayColumn] ?? $sourceRow[$valueColumn] ?? '');
        return $label;
    }

    // =========================================================================
    //  LAYER 1: FIELD RESOLVER
    // =========================================================================

    /**
     * Resolve a field from the form schema by matching the field name.
     */
    private function resolveField(MasterForm $model, string $fieldName): ?array
    {
        $fieldName = trim($fieldName);
        if ($fieldName === '') {
            return null;
        }

        $fields = $this->resolveFormFields($model);

        foreach ($fields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }
            $field = FormRenderService::resolveDynamicChoiceOptions(
                FormRenderService::normalizeFieldForRender($field, (int)$index)
            );
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
            ]));
            if (in_array($fieldName, $candidates, true)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Get resolved form fields, with fallback to builder data.
     */
    private function resolveFormFields(MasterForm $model): array
    {
        try {
            $schema = $this->formEngineService->getResolvedFormSchema($model);
            $fields = is_array($schema['fields'] ?? null) ? $schema['fields'] : [];
            if (!empty($fields)) {
                return $fields;
            }
        } catch (\Throwable $e) {
            // fall through to builder-data extraction
        }

        $formData = $model->getFormDataArray();
        if (!empty($formData['fields']) && is_array($formData['fields'])) {
            return $formData['fields'];
        }
        if ($this->isListArray($formData)) {
            return $formData;
        }

        return [];
    }

    // =========================================================================
    //  LAYER 2: CONFIG RESOLVER  (metadata-driven, no hardcoded column names)
    // =========================================================================

    /**
     * Build a normalized picker configuration from field metadata.
     *
     * Resolution order (metadata-driven):
     *   1. picker_config from form-builder UI
     *   2. field-level FK metadata (fk_referenced_table, fk_display_column, etc.)
     *   3. relation_config from field definition
     *   4. schema-driven auto-detection (column types, positions — no name hardcodes)
     */
    private function resolveConfig(array $field): ?array
    {
        $relationConfig = $this->normalizeJsonField('relation_config', $field, []);
        $pickerConfig = $this->normalizeJsonField('picker_config', $field, []);

        $tableName = trim((string)(
            $field['fk_referenced_table'] ??
            $field['source_table_name'] ??
            $field['referenced_table_name'] ??
            $relationConfig['referenced_table'] ??
            $relationConfig['referenced_table_name'] ??
            ''
        ));
        $valueColumn = trim((string)(
            $field['fk_referenced_column'] ??
            $field['value_column'] ??
            $relationConfig['referenced_value_column'] ??
            $relationConfig['value_column'] ??
            $relationConfig['referenced_column'] ??
            'id'
        ));

        if ($tableName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            return null;
        }

        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        if ($schema === null || !isset($schema->columns[$valueColumn])) {
            return null;
        }

        // --- Display Column (metadata-driven) ---
        $preferredDisplay = trim((string)(
            $pickerConfig['display_column'] ??
            $field['fk_display_column'] ??
            $field['label_column'] ??
            $relationConfig['display_column'] ??
            ''
        ));
        $displayColumn = $this->resolveDisplayColumn($schema->columns, $valueColumn, $preferredDisplay);

        // --- Search Columns (metadata-driven) ---
        $searchTarget = strtolower(trim((string)($pickerConfig['search_target'] ?? '')));
        $searchColumns = $this->resolveSearchColumns($schema->columns, $valueColumn, $displayColumn, $searchTarget, $pickerConfig);

        // --- Display / Grid Columns (metadata-driven) ---
        $displayColumns = $this->resolveDisplayColumns($schema->columns, $valueColumn, $displayColumn, $pickerConfig);

        // --- FK Display Columns (metadata-driven) ---
        $fkDisplayColumns = $this->resolveFkDisplayColumns(
            $pickerConfig['picker_fk_display_columns'] ?? [],
            $tableName,
            $schema->columns
        );

        return [
            'main_table' => $tableName,
            'value_column' => $valueColumn,
            'display_column' => $displayColumn,
            'search_target' => $searchTarget !== '' ? $searchTarget : 'custom',
            'search_columns' => $searchColumns,
            'display_columns' => $displayColumns,
            'picker_fk_display_columns' => $fkDisplayColumns,
            'page_size' => min(50, max(1, (int)($pickerConfig['page_size'] ?? 10))),
        ];
    }

    /**
     * Resolve the display column.
     *
     * 1. Use preferred column if it exists and is a safe display column.
     * 2. Auto-detect: exclude value column, FK columns, system columns,
     *    then pick the first string/text column from the schema.
     */
    private function resolveDisplayColumn(array $columns, string $valueColumn, string $preferred = ''): string
    {
        if ($preferred !== '') {
            $matched = $this->findCaseInsensitiveColumnKey($columns, $preferred);
            if ($matched !== null && $this->isSafeDisplayColumn($matched, $columns[$matched])) {
                return $matched;
            }
        }

        // Auto-detect: first string/text column that is not the value column,
        // not an FK column, and not a system column.
        foreach ($columns as $name => $column) {
            $name = (string)$name;
            if ($name === $valueColumn) {
                continue;
            }
            if ($this->isSystemColumn($name)) {
                continue;
            }
            if (substr($name, -3) === '_id') {
                continue;
            }
            if ($this->isSafeDisplayColumn($name, $column)) {
                return $name;
            }
        }

        return $valueColumn;
    }

    /**
     * Resolve search columns.
     *
     * 1. If search_target is 'value_only' → [valueColumn]
     * 2. If 'display_only' → [displayColumn]
     * 3. If 'value_and_display' → [valueColumn, displayColumn]
     * 4. If 'custom' or empty → use pickerConfig.search_columns
     * 5. Fallback auto-detect: displayColumn + text columns (max 5)
     */
    private function resolveSearchColumns(array $columns, string $valueColumn, string $displayColumn, string $searchTarget, array $pickerConfig): array
    {
        if ($searchTarget === 'value_only') {
            return [$valueColumn];
        }
        if ($searchTarget === 'display_only') {
            return [$displayColumn];
        }
        if ($searchTarget === 'value_and_display') {
            return array_values(array_unique(array_filter([$valueColumn, $displayColumn])));
        }

        // Custom or empty — use configured search_columns
        $configured = $this->normalizeColumnList($pickerConfig['search_columns'] ?? [], $columns, 5);
        if (!empty($configured)) {
            return $configured;
        }

        // Auto-detect: start with display column, add other readable columns up to 5
        return $this->autoDetectSearchColumns($columns, $valueColumn, $displayColumn);
    }

    /**
     * Resolve display grid columns.
     *
     * 1. Use configured display_columns from pickerConfig
     * 2. Fallback auto-detect: displayColumn + readable non-FK columns (max 8)
     */
    private function resolveDisplayColumns(array $columns, string $valueColumn, string $displayColumn, array $pickerConfig): array
    {
        $configured = $this->normalizeColumnList($pickerConfig['display_columns'] ?? [], $columns, 8);
        if (!empty($configured)) {
            return $configured;
        }

        // Auto-detect: display column + readable columns (avoiding FK/system)
        return $this->autoDetectDisplayColumns($columns, $valueColumn, $displayColumn);
    }

    /**
     * Resolve FK display columns mapping.
     *
     * For each column in the picker_fk_display_columns config, validate
     * the FK metadata and build a clean mapping.
     */
    private function resolveFkDisplayColumns($mapping, string $tableName, array $schemaColumns): array
    {
        if (is_string($mapping)) {
            $mapping = Json::decode($mapping, true);
        }
        if (!is_array($mapping)) {
            return [];
        }

        $fkMetadata = $this->resolveFkMetadata($tableName);
        $result = [];

        foreach ($mapping as $column => $config) {
            $column = trim((string)$column);
            if (!$this->isSafeIdentifier($column) || !isset($schemaColumns[$column]) || !isset($fkMetadata[$column])) {
                continue;
            }
            if (!is_array($config)) {
                $config = [];
            }

            $metadata = $fkMetadata[$column];
            $mode = (string)($config['mode'] ?? 'raw_id');

            if ($mode !== 'relation_display') {
                $result[$column] = [
                    'mode' => 'raw_id',
                    'referenced_table' => $metadata['referenced_table'],
                    'referenced_column' => $metadata['referenced_column'],
                    'display_column' => '',
                ];
                continue;
            }

            $displayColumn = trim((string)($config['display_column'] ?? ''));
            $referencedSchema = Yii::$app->db->schema->getTableSchema($metadata['referenced_table'], true);
            $matchedDisplayColumn = null;

            if ($referencedSchema !== null && $displayColumn !== '') {
                foreach (array_keys($referencedSchema->columns) as $cn) {
                    if (strcasecmp((string)$cn, $displayColumn) === 0) {
                        $matchedDisplayColumn = (string)$cn;
                        break;
                    }
                }
            }

            if ($matchedDisplayColumn === null || !$this->isSafeDisplayColumn($matchedDisplayColumn, $referencedSchema->columns[$matchedDisplayColumn] ?? null)) {
                $result[$column] = [
                    'mode' => 'raw_id',
                    'referenced_table' => $metadata['referenced_table'],
                    'referenced_column' => $metadata['referenced_column'],
                    'display_column' => '',
                ];
                continue;
            }

            $result[$column] = [
                'mode' => 'relation_display',
                'referenced_table' => $metadata['referenced_table'],
                'referenced_column' => $metadata['referenced_column'],
                'display_column' => $matchedDisplayColumn,
            ];
        }

        return $result;
    }

    /**
     * Resolve FK metadata for a table.
     *
     * Sources (metadata-driven):
     *   1. DbTableColumn metadata (has FK indicators, referenced_table_name, etc.)
     *   2. Live schema foreignKeys
     */
    private function resolveFkMetadata(string $tableName): array
    {
        $tableName = trim($tableName);
        if (!$this->isSafeIdentifier($tableName)) {
            return [];
        }

        $result = [];

        // Source 1: DbTableColumn metadata
        $dbTable = DbTable::find()->where(['name' => $tableName])->one();
        if ($dbTable !== null) {
            $columns = $dbTable->getColumns()->all();
            foreach ($columns as $column) {
                if (!$column instanceof DbTableColumn
                    || !$column->hasAttribute('is_foreign_key')
                    || !(bool)$column->getAttribute('is_foreign_key')) {
                    continue;
                }
                $name = trim((string)$column->name);
                $refTable = $column->hasAttribute('referenced_table_name')
                    ? trim((string)$column->getAttribute('referenced_table_name')) : '';
                $refColumn = $column->hasAttribute('referenced_column_name')
                    ? trim((string)$column->getAttribute('referenced_column_name')) : '';
                if ($this->isSafeIdentifier($name) && $this->isSafeIdentifier($refTable) && $this->isSafeIdentifier($refColumn)) {
                    $result[$name] = [
                        'referenced_table' => $refTable,
                        'referenced_column' => $refColumn,
                    ];
                }
            }
        }

        // Source 2: Live schema foreign keys (only for columns not already found)
        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
        if ($schema !== null && !empty($schema->foreignKeys)) {
            foreach ($schema->foreignKeys as $foreignKey) {
                if (!is_array($foreignKey) || empty($foreignKey[0])) {
                    continue;
                }
                $refTable = trim((string)$foreignKey[0]);
                foreach ($foreignKey as $col => $refCol) {
                    if (is_int($col)) {
                        continue;
                    }
                    $col = trim((string)$col);
                    $refCol = trim((string)$refCol);
                    if ($this->isSafeIdentifier($col) && $this->isSafeIdentifier($refTable)
                        && $this->isSafeIdentifier($refCol) && !isset($result[$col])) {
                        $result[$col] = [
                            'referenced_table' => $refTable,
                            'referenced_column' => $refCol,
                        ];
                    }
                }
            }
        }

        return $result;
    }

    // =========================================================================
    //  LAYER 3: QUERY BUILDER
    // =========================================================================

    /**
     * Build and execute the picker query.
     *
     * Constructs SELECT with aliases, LEFT JOINs for FK display columns,
     * WHERE for keyword search, and ORDER BY + pagination.
     */
    private function queryData(array $config, string $keyword, int $page, int $pageSize): array
    {
        $tableName = (string)$config['main_table'];
        $valueColumn = (string)$config['value_column'];
        $displayColumn = (string)$config['display_column'];
        $displayColumns = array_values(array_unique(array_merge(
            [$valueColumn, $displayColumn],
            $config['display_columns'] ?? []
        )));

        $db = Yii::$app->db;
        $mainAlias = 'rp';

        // Build SELECT with aliases
        $select = [];
        foreach ($displayColumns as $col) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $col)) {
                $select['main__' . $col] = $mainAlias . '.' . $col;
            }
        }

        // Build FK display JOINs
        $fkDisplayColumns = is_array($config['picker_fk_display_columns'] ?? null)
            ? $config['picker_fk_display_columns'] : [];
        $joinAliases = [];
        $joinIndex = 0;

        $query = (new Query())->from([$mainAlias => $tableName]);

        foreach ($displayColumns as $col) {
            $colLower = strtolower($col);
            $mapping = null;
            foreach ($fkDisplayColumns as $origKey => $origMapping) {
                if (strtolower((string)$origKey) === $colLower) {
                    $mapping = $origMapping;
                    break;
                }
            }
            if ($mapping === null || ($mapping['mode'] ?? 'raw_id') !== 'relation_display') {
                continue;
            }

            $refTable = (string)($mapping['referenced_table'] ?? '');
            $refCol = (string)($mapping['referenced_column'] ?? '');
            $display = (string)($mapping['display_column'] ?? '');
            if (!$this->isSafeIdentifier($refTable) || !$this->isSafeIdentifier($refCol)
                || !$this->isSafeIdentifier($display)) {
                continue;
            }

            $alias = 'rpfk' . $joinIndex++;
            $select['fk__' . $col] = $alias . '.' . $display;
            $joinAliases[$col] = 'fk__' . $col;
            $query->leftJoin(
                [$alias => $refTable],
                $db->quoteColumnName($alias . '.' . $refCol)
                . ' = ' . $db->quoteColumnName($mainAlias . '.' . $col)
            );
        }

        $query->select($select);

        // WHERE — keyword search on search_columns
        if ($keyword !== '') {
            $or = ['or'];
            foreach (($config['search_columns'] ?? []) as $col) {
                if (preg_match('/^[A-Za-z0-9_]+$/', (string)$col)) {
                    $or[] = ['like', $mainAlias . '.' . $col, $keyword];
                }
            }
            if (count($or) > 1) {
                $query->andWhere($or);
            }
        }

        $total = (int)(clone $query)->count('*', $db);

        $rows = $query
            ->orderBy([$mainAlias . '.' . $displayColumn => SORT_ASC])
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all($db);

        return [
            'total' => $total,
            'rows' => $this->buildRows($rows, $valueColumn, $displayColumn, $displayColumns, $joinAliases),
        ];
    }

    // =========================================================================
    //  LAYER 4: RESPONSE BUILDER
    // =========================================================================

    /**
     * Transform raw query rows into {value, label, display} format.
     */
    private function buildRows(array $rows, string $valueColumn, string $displayColumn, array $displayColumns, array $joinAliases): array
    {
        return array_map(function (array $row) use ($valueColumn, $displayColumn, $displayColumns, $joinAliases): array {
            $display = [];
            foreach ($displayColumns as $column) {
                $mainKey = 'main__' . $column;
                $fkKey = $joinAliases[$column] ?? null;
                if ($fkKey !== null && array_key_exists($fkKey, $row)) {
                    $display[$this->humanizeColumn($column)] = $row[$fkKey];
                } elseif (array_key_exists($mainKey, $row)) {
                    $display[$this->humanizeColumn($column)] = $row[$mainKey];
                }
            }
            $labelKey = $joinAliases[$displayColumn] ?? ('main__' . $displayColumn);
            return [
                'value' => (string)($row['main__' . $valueColumn] ?? ''),
                'label' => (string)($row[$labelKey] ?? $row['main__' . $displayColumn] ?? $row['main__' . $valueColumn] ?? ''),
                'display' => $display,
            ];
        }, $rows);
    }

    private function humanizeColumn(string $column): string
    {
        return ucwords(str_replace('_', ' ', $column));
    }

    // =========================================================================
    //  LAYER 5: AUTO-DETECT HELPERS (metadata-driven)
    // =========================================================================

    /**
     * Auto-detect search columns (max 5).
     * Start with displayColumn, then add other readable columns.
     */
    private function autoDetectSearchColumns(array $columns, string $valueColumn, string $displayColumn): array
    {
        $result = [];
        $seen = [];

        $candidates = array_unique([$displayColumn, $valueColumn]);

        foreach ($candidates as $c) {
            if (isset($columns[$c]) && $this->isSafeDisplayColumn($c, $columns[$c]) && !isset($seen[$c])) {
                $result[] = $c;
                $seen[$c] = true;
            }
        }

        foreach ($columns as $name => $column) {
            if (count($result) >= 5) {
                break;
            }
            $name = (string)$name;
            if (isset($seen[$name]) || $name === $valueColumn || $this->isSystemColumn($name)
                || substr($name, -3) === '_id') {
                continue;
            }
            if ($this->isSafeDisplayColumn($name, $column)) {
                $result[] = $name;
                $seen[$name] = true;
            }
        }

        return $result;
    }

    /**
     * Auto-detect display grid columns (max 8).
     * Start with displayColumn, then add other readable non-FK/system columns.
     */
    private function autoDetectDisplayColumns(array $columns, string $valueColumn, string $displayColumn): array
    {
        $result = [];
        $seen = [];

        $candidates = array_unique([$displayColumn]);

        foreach ($candidates as $c) {
            if (isset($columns[$c]) && !isset($seen[$c])) {
                $result[] = $c;
                $seen[$c] = true;
            }
        }

        foreach ($columns as $name => $column) {
            if (count($result) >= 6) {
                break;
            }
            $name = (string)$name;
            if (isset($seen[$name]) || $name === $valueColumn || $this->isSystemColumn($name)
                || substr($name, -3) === '_id') {
                continue;
            }
            if ($this->isSafeDisplayColumn($name, $column)) {
                $result[] = $name;
                $seen[$name] = true;
            }
        }

        return $result;
    }

    // =========================================================================
    //  LAYER 6: AUTOFILL RESOLVER
    // =========================================================================

    /**
     * Load the source row from the referenced table.
     */
    private function loadSourceRow(array $config, string $triggerValue): ?array
    {
        $tableName = trim((string)($config['main_table'] ?? ''));
        $valueColumn = trim((string)($config['value_column'] ?? 'id'));
        if ($tableName === '' || $valueColumn === '') {
            return null;
        }

        $row = (new Query())
            ->from($tableName)
            ->where([$valueColumn => $triggerValue])
            ->limit(1)
            ->one(Yii::$app->db);

        return is_array($row) ? $row : null;
    }

    /**
     * Build autofill candidate rows (source row + traversed FK relations).
     */
    private function buildAutofillCandidates(array $config, array $sourceRow, int $maxDepth = 2): array
    {
        $mainTable = trim((string)($config['main_table'] ?? ''));
        $rows = [[
            'table' => $mainTable,
            'row' => $sourceRow,
            'depth' => 0,
            'display_column' => trim((string)($config['display_column'] ?? '')),
        ]];

        $queue = $rows;
        $visited = [];

        while (!empty($queue)) {
            $entry = array_shift($queue);
            $tableName = trim((string)($entry['table'] ?? ''));
            $row = is_array($entry['row'] ?? null) ? $entry['row'] : [];
            $depth = (int)($entry['depth'] ?? 0);
            if ($tableName === '' || empty($row) || $depth >= $maxDepth) {
                continue;
            }

            foreach ($this->findFKColumnsForTable($tableName) as $fk) {
                $localColumn = trim((string)($fk['local_column'] ?? ''));
                $referencedTable = trim((string)($fk['referenced_table'] ?? ''));
                $referencedColumn = trim((string)($fk['referenced_column'] ?? 'id'));
                if ($localColumn === '' || $referencedTable === '' || !array_key_exists($localColumn, $row)) {
                    continue;
                }
                $fkValue = $row[$localColumn];
                if ($fkValue === null || $fkValue === '') {
                    continue;
                }

                $visitKey = strtolower($referencedTable . '|' . $referencedColumn . '|' . (string)$fkValue);
                if (isset($visited[$visitKey])) {
                    continue;
                }
                $visited[$visitKey] = true;

                $refRow = (new Query())
                    ->from($referencedTable)
                    ->where([$referencedColumn => $fkValue])
                    ->limit(1)
                    ->one(Yii::$app->db);
                if (!is_array($refRow)) {
                    continue;
                }

                $displayColumn = $this->resolveDisplayColumn(
                    Yii::$app->db->schema->getTableSchema($referencedTable, true)->columns ?? [],
                    $referencedColumn,
                    ''
                );

                $next = [
                    'table' => $referencedTable,
                    'row' => $refRow,
                    'depth' => $depth + 1,
                    'via_column' => $localColumn,
                    'display_column' => $displayColumn,
                ];
                $rows[] = $next;
                $queue[] = $next;
            }
        }

        return $rows;
    }

    /**
     * Find FK columns for a table using metadata.
     */
    private function findFKColumnsForTable(string $tableName): array
    {
        $tableName = trim($tableName);
        if ($tableName === '') {
            return [];
        }

        $table = DbTable::find()->where(['name' => $tableName])->one();
        if ($table === null) {
            return [];
        }

        $columns = DbTableColumn::find()
            ->where(['table_id' => (int)$table->id, 'is_foreign_key' => true])
            ->all();

        $result = [];
        foreach ($columns as $column) {
            $localCol = trim((string)($column->name ?? ''));
            $refTable = $column->hasAttribute('referenced_table_name')
                ? trim((string)$column->getAttribute('referenced_table_name')) : '';
            $refCol = $column->hasAttribute('referenced_column_name')
                ? trim((string)$column->getAttribute('referenced_column_name')) : '';
            if ($localCol === '' || $refTable === '') {
                continue;
            }
            $result[] = [
                'local_column' => $localCol,
                'referenced_table' => $refTable,
                'referenced_column' => $refCol !== '' ? $refCol : 'id',
            ];
        }

        return $result;
    }

    /**
     * Build autofill values by matching field names to candidate column values.
     */
    private function buildAutofillValues(array $fields, array $triggerField, array $candidateRows): array
    {
        $values = [];
        $readonlyFields = [];
        $labels = [];
        $candidates = $this->buildColumnCandidates($candidateRows);

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
            $fieldName = trim((string)(
                $field['resolved_name'] ?? $field['resolved_column_name'] ??
                $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''
            ));
            if ($fieldName === '') {
                continue;
            }
            if (in_array($fieldName, $triggerCandidates, true)) {
                continue;
            }

            $candidateCols = array_filter(array_unique([
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
            ]));

            $fieldBehavior = strtolower(trim((string)($field['field_behavior'] ?? $field['behavior'] ?? '')));
            $fieldMeta = $this->extractAutofillFieldConfig($field);
            foreach (['source_column', 'display_column', 'value_column', 'source_field'] as $k) {
                if (!empty($fieldMeta[$k])) {
                    $candidateCols[] = (string)$fieldMeta[$k];
                }
            }

            $matched = $this->matchAutofillColumn($candidateCols, $candidates);
            if ($matched === null && in_array($fieldBehavior, ['readonly', 'display_only', 'display-only'], true)) {
                $matched = $this->matchAutofillColumn([$fieldName], $candidates);
            }
            if ($matched === null) {
                continue;
            }

            $values[$fieldName] = $matched['value'];
            $readonlyFields[] = $fieldName;
            $labels[$fieldName] = $matched['label'];
        }

        return [
            'values' => $values,
            'readonly_fields' => array_values(array_unique($readonlyFields)),
            'labels' => $labels,
        ];
    }

    /**
     * Build column candidates from autofill rows.
     */
    private function buildColumnCandidates(array $candidateRows): array
    {
        $candidates = [];
        foreach ($candidateRows as $entry) {
            $row = is_array($entry['row'] ?? null) ? $entry['row'] : [];
            $tableName = trim((string)($entry['table'] ?? ''));
            $depth = (int)($entry['depth'] ?? 0);
            foreach ($row as $columnName => $value) {
                $columnName = (string)$columnName;
                if ($value === null || $value === '' || $this->isSensitiveOrAuditColumn($columnName)) {
                    continue;
                }
                $candidates[] = [
                    'table' => $tableName,
                    'column' => $columnName,
                    'normalized' => $this->normalizeAutofillKey($columnName),
                    'raw_normalized' => $this->normalizeKey($columnName),
                    'value' => $value,
                    'label' => is_scalar($value) ? (string)$value : '...',
                    'depth' => $depth,
                ];
            }
        }
        return $candidates;
    }

    /**
     * Match a field's candidate columns to source candidates.
     */
    private function matchAutofillColumn(array $candidateColumns, array $sourceCandidates): ?array
    {
        $needles = array_values(array_unique(array_filter(array_map('trim', $candidateColumns))));
        if (empty($needles) || empty($sourceCandidates)) {
            return null;
        }

        $matches = [];
        foreach ($needles as $needleIndex => $needle) {
            $needleRaw = $this->normalizeKey($needle);
            $needleNormalized = $this->normalizeAutofillKey($needle);
            if ($needleRaw === '' && $needleNormalized === '') {
                continue;
            }
            foreach ($sourceCandidates as $source) {
                $sourceRaw = (string)($source['raw_normalized'] ?? '');
                $sourceNormalized = (string)($source['normalized'] ?? '');
                if ($sourceRaw === '' && $sourceNormalized === '') {
                    continue;
                }
                $score = 0;
                if ($needleRaw !== '' && $needleRaw === $sourceRaw) {
                    $score = 100;
                } elseif ($needleNormalized !== '' && $needleNormalized === $sourceNormalized) {
                    $score = 90;
                } elseif ($this->safeContains($needleNormalized, $sourceNormalized)) {
                    $score = 70;
                } elseif ($this->safeContains($needleRaw, $sourceRaw)) {
                    $score = 65;
                }
                if ($score <= 0) {
                    continue;
                }
                $score -= ((int)($source['depth'] ?? 0) * 5);
                $score -= ($needleIndex * 2);
                $matches[] = ['score' => $score, 'source' => $source];
            }
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, static fn(array $a, array $b): int => (int)$b['score'] - (int)$a['score']);
        return $matches[0]['source'];
    }

    /**
     * Extract autofill field configuration.
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
                        $c = array_filter([
                            'source_column' => $rule['source_column'] ?? $rule['source'] ?? $rule['from'] ?? $rule['column'] ?? null,
                            'display_column' => $rule['display_column'] ?? $rule['display'] ?? null,
                            'value_column' => $rule['value_column'] ?? $rule['value'] ?? null,
                            'source_field' => $rule['source_field'] ?? $rule['field'] ?? null,
                        ], static fn($v): bool => $v !== null && $v !== '');
                        if (!empty($c)) {
                            return $c;
                        }
                    }
                }
                return array_filter([
                    'source_column' => $value['source_column'] ?? $value['source'] ?? $value['from'] ?? $value['column'] ?? null,
                    'display_column' => $value['display_column'] ?? $value['display'] ?? null,
                    'value_column' => $value['value_column'] ?? $value['value'] ?? null,
                    'source_field' => $value['source_field'] ?? $value['field'] ?? null,
                ], static fn($v): bool => $v !== null && $v !== '');
            }
        }
        return [];
    }

    // =========================================================================
    //  UTILITY HELPERS
    // =========================================================================

    private function loadForm(int $formId): ?MasterForm
    {
        return MasterForm::findByIdScoped($formId);
    }

    private function emptyAutofillResponse(string $message = ''): array
    {
        return [
            'success' => $message === '',
            'message' => $message,
            'values' => new \stdClass(),
            'display' => ['enabled' => false, 'items' => []],
            'readonly_fields' => [],
            'labels' => [],
            'trigger_label' => '',
        ];
    }

    /**
     * Safely decode a JSON field that could be string, array, or null.
     */
    private function normalizeJsonField(string $key, array $data, $default = null)
    {
        $value = $data[$key] ?? $default;
        if (is_string($value)) {
            $decoded = Json::decode($value, true);
            return is_array($decoded) ? $decoded : $default;
        }
        return is_array($value) ? $value : $default;
    }

    /**
     * Normalize a column list from mixed format (string, array) to filtered array.
     */
    private function normalizeColumnList($columns, array $schemaColumns, int $limit): array
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($columns)) {
            return [];
        }
        $result = [];
        foreach ($columns as $col) {
            $name = trim((string)$col);
            if ($name !== '' && isset($schemaColumns[$name]) && $this->isSafeDisplayColumn($name, $schemaColumns[$name])) {
                $result[] = $name;
            }
            if (count($result) >= $limit) {
                break;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * Check whether a column is safe to use as a display/search column.
     *
     * - Must be a safe identifier.
     * - Must not be a sensitive column (password, token, etc.).
     * - Must be a readable type (string, text, integer, bigint, smallint, tinyint).
     */
    private function isSafeDisplayColumn(string $name, $column): bool
    {
        if (!$this->isSafeIdentifier($name)) {
            return false;
        }
        if (preg_match('/password|token|secret|remember|auth|salt|hash/i', $name)) {
            return false;
        }
        // If column has a type property (live schema or DbTableColumn)
        $type = null;
        if (is_object($column) && isset($column->type)) {
            $type = strtolower((string)$column->type);
        } elseif (is_array($column) && isset($column['type'])) {
            $type = strtolower((string)$column['type']);
        }
        if ($type !== null) {
            return in_array($type, ['string', 'text', 'integer', 'bigint', 'smallint', 'tinyint'], true);
        }
        return true;
    }

    /**
     * Check if a column name is a system/audit column.
     */
    private function isSystemColumn(string $name): bool
    {
        $normalized = strtolower(trim($name));
        return in_array($normalized, [
            'created_at', 'updated_at', 'deleted_at',
            'created_by', 'updated_by', 'deleted_by',
        ], true);
    }

    /**
     * Check if a column is sensitive or an audit column (for autofill).
     */
    private function isSensitiveOrAuditColumn(string $name): bool
    {
        $normalized = strtolower(trim($name));
        if ($normalized === '') {
            return true;
        }
        foreach (['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by',
            'password', 'passwd', 'token', 'secret', 'auth_key', 'api_key', 'remember_token'] as $blocked) {
            if ($normalized === $blocked || str_contains($normalized, $blocked)) {
                return true;
            }
        }
        return false;
    }

    private function isSafeIdentifier(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
    }

    /**
     * Case-insensitive column key lookup.
     */
    private function findCaseInsensitiveColumnKey(array $columns, string $search): ?string
    {
        foreach (array_keys($columns) as $key) {
            if (strcasecmp((string)$key, $search) === 0) {
                return (string)$key;
            }
        }
        return null;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $value), '_'));
    }

    private function normalizeAutofillKey(string $key): string
    {
        $normalized = $this->normalizeKey($key);
        $normalized = preg_replace('/(^|_)id_/', '$1', $normalized) ?? $normalized;
        $normalized = preg_replace('/(^|_)(fk|ref)_/', '$1', $normalized) ?? $normalized;
        $normalized = preg_replace('/_id$/', '', $normalized) ?? $normalized;
        return trim($normalized, '_');
    }

    private function safeContains(string $left, string $right): bool
    {
        $left = trim($left, '_');
        $right = trim($right, '_');
        if ($left === '' || $right === '' || $left === $right) {
            return false;
        }
        $short = strlen($left) <= strlen($right) ? $left : $right;
        $long = $short === $left ? $right : $left;
        if (strlen($short) < 4) {
            return false;
        }
        return str_contains('_' . $long . '_', '_' . $short . '_')
            || str_starts_with($long, $short . '_')
            || str_ends_with($long, '_' . $short);
    }

    private function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }
}
