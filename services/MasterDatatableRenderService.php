<?php

namespace app\services;

use app\components\ActiveProjectContext;
use app\components\ProjectPermissionService;
use app\components\ProjectSchema;
use app\components\SystemFieldService;
use app\helpers\FormSystemFieldHelper;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterForm;
use app\models\MasterDatatable;
use Yii;
use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Response;

class MasterDatatableRenderService
{
    /** @var array<string, array<string, mixed>|null> */
    private static array $renderDataCache = [];

    public function renderByPresetId(int $presetId, array $options = []): string
    {
        $preset = MasterDatatable::findScoped()->andWhere(['id' => $presetId, 'is_active' => 1])->one();
        if (!$preset instanceof MasterDatatable) {
            return $this->renderNotice('Datatable tidak tersedia.', 'Preset datatable tidak ditemukan atau sedang nonaktif.');
        }

        $config = $preset->toComponentConfig();
        $config['_preset_loaded'] = true;
        return $this->renderFromConfig($config, $options);
    }

    public function renderAjaxByPresetId(int $presetId): array
    {
        $preset = MasterDatatable::findScoped()->andWhere(['id' => $presetId, 'is_active' => 1])->one();
        if (!$preset instanceof MasterDatatable) {
            return [
                'success' => false,
                'message' => 'Preset datatable tidak ditemukan atau sedang nonaktif.',
            ];
        }

        $data = $this->buildRenderData($preset->toComponentConfig());
        if ($data === null) {
            return [
                'success' => false,
                'message' => 'Datatable tidak dapat dimuat.',
            ];
        }

        $state = $data['state'];
        $totalPages = max(1, (int)ceil(($state['total'] ?: 0) / $state['pageSize']));
        $footHtml = '';
        if ($state['paginationEnabled']) {
            $footHtml = '<div class="dt-foot"><span>Page ' . (int)$state['page'] . ' of ' . $totalPages . '</span><div class="dt-page">';
            if ($state['page'] > 1) {
                $footHtml .= '<a href="' . Html::encode($this->pageUrl($state['pageParam'], $state['page'] - 1)) . '" target="_top">Previous</a>';
            }
            if ($state['page'] < $totalPages) {
                $footHtml .= '<a href="' . Html::encode($this->pageUrl($state['pageParam'], $state['page'] + 1)) . '" target="_top">Next</a>';
            }
            $footHtml .= '</div></div>';
        }
        return [
            'success' => true,
            'tableId' => (int)$data['table']->id,
            'tableName' => (string)$data['table']->name,
            'tbodyHtml' => $this->renderRowsHtml(
                $data['table'],
                $data['columns'],
                $data['rows'],
                $data['actions'],
                $data['primaryKeys'],
                $data['displayLookup'],
                $data['colspan'],
                (int)$preset->id,
                $data['workflow']
            ),
            'footHtml' => $footHtml,
            'total' => (int)$state['total'],
            'subtitle' => (int)$state['total'] . ' row' . ((int)$state['total'] === 1 ? '' : 's') . ' from ' . (string)$data['table']->name,
        ];
    }

    public function renderFromConfig(array $config, array $options = []): string
    {
        $presetId = (int)($config['datatableId'] ?? $config['datatable_id'] ?? 0);
        
        // ===== BUG 4 FIX: Jika presetId tidak ada, cari preset berdasarkan tableId =====
        // Saat render dari page builder, mungkin hanya tableId yang tersedia
        if ($presetId <= 0) {
            $tableId = (int)($config['tableId'] ?? $config['table_id'] ?? 0);
            if ($tableId > 0) {
                // Cari preset yang menggunakan table ini
                // PERBAIKAN BUG 4: kolom model adalah table_id, bukan source_table_id
                $preset = MasterDatatable::findScoped()
                    ->andWhere(['table_id' => $tableId, 'is_active' => 1])
                    ->orderBy(['id' => SORT_DESC])
                    ->one();
                if ($preset instanceof MasterDatatable) {
                    $presetId = (int)$preset->id;
                    // Load config dari preset jika belum di-load
                    if (empty($config['_preset_loaded'])) {
                        $config = array_replace_recursive($preset->toComponentConfig(), $config);
                        $config['_preset_loaded'] = true;
                    }
                }
            }
        }
        
        if ($presetId > 0 && empty($config['_preset_loaded'])) {
            $preset = MasterDatatable::findScoped()->andWhere(['id' => $presetId, 'is_active' => 1])->one();
            if ($preset instanceof MasterDatatable) {
                $config = array_replace_recursive($preset->toComponentConfig(), $config);
                $config['_preset_loaded'] = true;
            }
        }

        $data = $this->buildRenderData($config);
        if ($data === null) {
            return $this->renderNotice('No data available', 'Source table tidak ditemukan atau tidak dapat diakses.');
        }

        return $this->renderTable($data['uid'], $data['table'], $data['columns'], $data['rows'], $data['actions'], $data['editMode'], $data['editForm'], $data['primaryKeys'], $data['state'], $presetId, $data['filters'], $data['stats'], $data['workflow'], $data['exports'], $data['displayLookup'], $data['displayValues']);
    }

    private function buildRenderData(array $config): ?array
    {
        $tableId = (int)($config['tableId'] ?? $config['table_id'] ?? 0);
        $pageParam = 'dt_page_' . $tableId;
        $searchParam = 'dt_search_' . $tableId;
        $cacheKey = md5(Json::encode([
            'tableId' => $tableId,
            'columns' => $config['columns'] ?? [],
            'actions' => $config['actions'] ?? [],
            'filters' => $config['filters'] ?? [],
            'stats' => $config['stats'] ?? [],
            'workflow' => $config['workflow'] ?? [],
            'ownership' => $config['ownership'] ?? [],
            'editFormId' => $config['editFormId'] ?? $config['edit_form_id'] ?? null,
            'search' => $config['search'] ?? $config['search_enabled'] ?? true,
            'pagination' => $config['pagination'] ?? $config['pagination_enabled'] ?? true,
            'pageSize' => $config['pageSize'] ?? $config['page_size'] ?? 10,
            'page' => Yii::$app->request->get($pageParam, 1),
            'searchQuery' => Yii::$app->request->get($searchParam, ''),
            'filterQuery' => Yii::$app->request->get(),
            'projectId' => (new ActiveProjectContext())->getActiveProjectId(),
            'userId' => Yii::$app->user->isGuest ? 0 : (int)Yii::$app->user->id,
            'dsn' => (string)Yii::$app->db->dsn,
        ]));
        if (array_key_exists($cacheKey, self::$renderDataCache)) {
            return self::$renderDataCache[$cacheKey];
        }

        if ($tableId <= 0) {
            return self::$renderDataCache[$cacheKey] = null;
        }

        $table = DbTable::find()->where(['id' => $tableId])->one();
        if (!$table instanceof DbTable || !$this->canUseTable($table)) {
            return self::$renderDataCache[$cacheKey] = null;
        }

        $table = $this->syncTableMetadataIfPhysical($table);
        $tableSchema = Yii::$app->db->schema->getTableSchema($table->name, true);
        if ($tableSchema === null) {
            return self::$renderDataCache[$cacheKey] = null;
        }

        $columns = $this->resolveColumns($table, $config);
        if (empty($columns)) {
            return self::$renderDataCache[$cacheKey] = null;
        }

        $searchEnabled = (bool)($config['search'] ?? $config['search_enabled'] ?? true);
        $paginationEnabled = (bool)($config['pagination'] ?? $config['pagination_enabled'] ?? true);
        $pageSize = max(1, min(100, (int)($config['pageSize'] ?? $config['page_size'] ?? 10)));
        $page = max(1, (int)Yii::$app->request->get($pageParam, 1));
        $search = trim((string)Yii::$app->request->get($searchParam, ''));
        $fields = array_column($columns, 'field');
        $filters = $this->resolveFilters($table, $config);
        $statsConfig = $this->resolveStatsConfig($table, $config);
        $workflow = $this->resolveWorkflowConfig($table, $config);

        $query = (new Query())->from($table->name);
        $this->applyOwnershipToQuery($query, $table->name, $config);
        if ($searchEnabled && $search !== '') {
            $or = ['or'];
            foreach ($fields as $field) {
                $or[] = ['like', $field, $search];
            }
            // Also search on FK display values (related table display columns)
            foreach ($columns as $col) {
                $displayMode = $col['fk_display_mode'] ?? 'raw_id';
                if ($displayMode === 'related_column') {
                    $refTable = $col['referenced_table'] ?? '';
                    $refColumn = $col['referenced_column'] ?? '';
                    $displayCol = $col['related_display_column'] ?? '';
                    $colField = $col['field'] ?? '';
                    if ($refTable !== '' && $refColumn !== '' && $displayCol !== '' && $colField !== '') {
                        $subQuery = (new Query())
                            ->select($refColumn)
                            ->from($refTable)
                            ->where(['like', $displayCol, $search]);
                        $or[] = ['in', $colField, $subQuery];
                    }
                }
            }
            if (count($or) > 1) {
                $query->andWhere($or);
            }
        }
        foreach ($filters as $index => $filter) {
            $field = (string)$filter['field'];
            $param = 'dt_filter_' . $tableId . '_' . $field;
            $value = trim((string)Yii::$app->request->get($param, ''));
            $filters[$index]['param'] = $param;
            $filters[$index]['value'] = $value;
            if ($value !== '') {
                $query->andWhere([$field => $value]);
            }
            $filters[$index]['options'] = $this->buildFilterOptions($table, $field, $filter);
        }

        $total = (int)(clone $query)->count('*', Yii::$app->db);
        $stats = $this->buildStats($query, $statsConfig);
        if ($paginationEnabled) {
            $query->limit($pageSize)->offset(($page - 1) * $pageSize);
        } else {
            $query->limit(max(1, min(5000, (int)($config['pageSize'] ?? $config['page_size'] ?? 500))));
        }

        $rows = $query->all(Yii::$app->db);
        $actions = $this->resolveActions($config);
        $editMode = $this->resolveEditMode($config);
        $editForm = $this->resolveEditForm($config);
        $primaryKeys = !empty($tableSchema->primaryKey) ? array_values($tableSchema->primaryKey) : [];
        $uid = 'dt-' . $tableId . '-' . substr(md5(json_encode($config)), 0, 8);
        $hasActions = in_array(true, $actions, true) && !empty($primaryKeys);
        $displayLookup = $this->buildRelatedDisplayLookup($columns, $rows);
        // Pre-compute display values untuk semua rows — single source of truth untuk UI dan Export
        $displayValues = $this->buildAllDisplayValues($columns, $rows, $displayLookup);

        // ===== BUG 4 FIX: Normalize exports dengan default seperti model MasterDatatable =====
        // Pastikan exports tidak pernah kosong/null - selalu punya default values untuk semua format
        $rawExports = $config['exports'] ?? [];
        if (!is_array($rawExports)) {
            $rawExports = [];
        }
        $defaultExports = ['csv' => true, 'excel' => true, 'pdf' => true, 'print' => true];
        $normalizedExports = [];
        foreach ($defaultExports as $fmt => $defaultValue) {
            if (array_key_exists($fmt, $rawExports)) {
                $normalizedExports[$fmt] = !empty($rawExports[$fmt]);
            } else {
                // Jika format export tidak disebutkan dalam config, gunakan default true
                $normalizedExports[$fmt] = $defaultValue;
            }
        }

        return self::$renderDataCache[$cacheKey] = [
            'uid' => $uid,
            'table' => $table,
            'columns' => $columns,
            'rows' => $rows,
            'actions' => $actions,
            'editMode' => $editMode,
            'editForm' => $editForm,
            'primaryKeys' => $primaryKeys,
            'displayLookup' => $displayLookup,
            'displayValues' => $displayValues,
            'colspan' => count($columns) + ($hasActions ? 1 : 0),
            'filters' => $filters,
            'stats' => $stats,
            'workflow' => $workflow,
            'exports' => $normalizedExports,
            'state' => [
                'searchEnabled' => $searchEnabled,
                'paginationEnabled' => $paginationEnabled,
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'search' => $search,
                'pageParam' => $pageParam,
                'searchParam' => $searchParam,
            ],
        ];
    }

    private function resolveFilters(DbTable $table, array $config): array
    {
        $metadataMap = [];
        foreach ($table->getColumns()->all() as $column) {
            $metadataMap[(string)$column->name] = $column;
        }

        $filters = [];
        foreach ((array)($config['filters'] ?? $config['filters_config'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $field = trim((string)($item['field'] ?? ''));
            if ($field === '' || !isset($metadataMap[$field])) {
                continue;
            }
            $filters[] = [
                'field' => $field,
                'label' => trim((string)($item['label'] ?? '')) ?: ($metadataMap[$field]->label ?: $field),
                'param' => '',
                'value' => '',
                'options' => [],
                'display_mode' => trim((string)($item['display_mode'] ?? $item['fkDisplayMode'] ?? '')),
                'related_display_column' => trim((string)($item['related_display_column'] ?? $item['relatedDisplayColumn'] ?? '')),
            ];
        }

        return $filters;
    }

    private function resolveStatsConfig(DbTable $table, array $config): array
    {
        $metadataMap = [];
        foreach ($table->getColumns()->all() as $column) {
            $metadataMap[(string)$column->name] = $column;
        }

        $stats = [];
        foreach ((array)($config['stats'] ?? $config['stats_config'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $field = trim((string)($item['field'] ?? ''));
            if ($field === '' || !isset($metadataMap[$field])) {
                continue;
            }
            $stats[] = [
                'field' => $field,
                'label' => trim((string)($item['label'] ?? '')) ?: ($metadataMap[$field]->label ?: $field),
            ];
        }

        return $stats;
    }

    private function resolveWorkflowConfig(DbTable $table, array $config): array
    {
        $workflow = is_array($config['workflow'] ?? null) ? $config['workflow'] : [];
        $enabled = !empty($workflow['approval_enabled']) || !empty($workflow['enabled']);
        $statusField = trim((string)($workflow['status_field'] ?? ''));
        $schema = Yii::$app->db->schema->getTableSchema((string)$table->name, true);
        if (!$enabled || $statusField === '' || $schema === null || !isset($schema->columns[$statusField])) {
            return ['approval_enabled' => false];
        }

        return [
            'approval_enabled' => true,
            'status_field' => $statusField,
            'approved_value' => trim((string)($workflow['approved_value'] ?? 'approved')),
            'pending_value' => trim((string)($workflow['pending_value'] ?? 'pending')),
            'button_label' => trim((string)($workflow['button_label'] ?? 'Approve')) ?: 'Approve',
        ];
    }

    private function buildFilterOptions(DbTable $table, string $field, array $filterConfig = []): array
    {
        try {
            $rows = (new Query())
                ->from((string)$table->name)
                ->select([$field])
                ->distinct()
                ->orderBy([$field => SORT_ASC])
                ->limit(300)
                ->column(Yii::$app->db);
        } catch (\Throwable $e) {
            Yii::warning('Failed loading datatable filter options: ' . $e->getMessage(), 'master-datatable');
            return [];
        }

        $options = [];
        $displayLookup = $this->buildFilterDisplayLookup($table, $field, $rows, $filterConfig);
        foreach ($rows as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $options[] = [
                'value' => (string)$value,
                'label' => $displayLookup[(string)$value] ?? $this->formatValue($value),
            ];
        }

        return $options;
    }

    private function buildFilterDisplayLookup(DbTable $table, string $field, array $values, array $filterConfig = []): array
    {
        $metadataColumn = DbTableColumn::find()
            ->where(['table_id' => (int)$table->id, 'name' => $field])
            ->one();
        if (!$metadataColumn instanceof DbTableColumn || !SystemFieldService::isForeignKey($metadataColumn)) {
            return [];
        }

        $referencedTable = trim((string)($metadataColumn->hasAttribute('referenced_table_name') ? $metadataColumn->getAttribute('referenced_table_name') : ''));
        $referencedColumn = trim((string)($metadataColumn->hasAttribute('referenced_column_name') ? $metadataColumn->getAttribute('referenced_column_name') : 'id'));
        if ($referencedTable === '') {
            return [];
        }

        $schema = Yii::$app->db->schema->getTableSchema($referencedTable, true);
        if ($schema === null) {
            return [];
        }
        if ($referencedColumn === '' || !isset($schema->columns[$referencedColumn])) {
            $referencedColumn = !empty($schema->primaryKey) ? (string)$schema->primaryKey[0] : (string)array_key_first($schema->columns);
        }

        $displayColumn = '';
        $displayMode = trim((string)($filterConfig['display_mode'] ?? $filterConfig['fkDisplayMode'] ?? ''));
        if ($displayMode === 'related_column') {
            $displayColumn = trim((string)($filterConfig['related_display_column'] ?? $filterConfig['relatedDisplayColumn'] ?? ''));
        }
        if ($displayColumn === '' || !isset($schema->columns[$displayColumn])) {
            $displayColumn = $this->guessForeignKeyLabelColumn($schema, $referencedColumn);
        }

        try {
            $rows = (new Query())
                ->from($referencedTable)
                ->select(array_values(array_unique([$referencedColumn, $displayColumn])))
                ->where([$referencedColumn => array_values(array_filter($values, static function ($value): bool {
                    return $value !== null && $value !== '';
                }))])
                ->all(Yii::$app->db);
        } catch (\Throwable $e) {
            Yii::warning('Failed loading datatable FK filter labels: ' . $e->getMessage(), 'master-datatable');
            return [];
        }

        $lookup = [];
        foreach ($rows as $row) {
            $key = isset($row[$referencedColumn]) ? (string)$row[$referencedColumn] : '';
            if ($key === '') {
                continue;
            }
            $lookup[$key] = $this->formatValue($row[$displayColumn] ?? $key);
        }

        return $lookup;
    }

    private function buildStats(Query $filteredQuery, array $statsConfig): array
    {
        $stats = [];
        foreach ($statsConfig as $item) {
            $field = (string)($item['field'] ?? '');
            if ($field === '') {
                continue;
            }

            try {
                $rows = (clone $filteredQuery)
                    ->select([$field, 'total' => new \yii\db\Expression('COUNT(*)')])
                    ->groupBy($field)
                    ->orderBy(['total' => SORT_DESC])
                    ->limit(8)
                    ->all(Yii::$app->db);
            } catch (\Throwable $e) {
                Yii::warning('Failed building datatable stats: ' . $e->getMessage(), 'master-datatable');
                continue;
            }

            $stats[] = [
                'field' => $field,
                'label' => (string)($item['label'] ?? $field),
                'rows' => $rows,
            ];
        }

        return $stats;
    }

    public function deleteRow(int $tableId, array $rowKey): bool
    {
        $table = DbTable::find()->where(['id' => $tableId])->one();
        if (!$table instanceof DbTable || !$this->canUseTable($table)) {
            return false;
        }

        $schema = Yii::$app->db->schema->getTableSchema($table->name, true);
        if ($schema === null || empty($schema->primaryKey)) {
            return false;
        }

        $where = [];
        foreach ($schema->primaryKey as $key) {
            if (!array_key_exists($key, $rowKey)) {
                return false;
            }
            $where[$key] = $rowKey[$key];
        }

        return Yii::$app->db->createCommand()->delete($table->name, $where)->execute() > 0;
    }

    public function approveRow(MasterDatatable $preset, array $rowKey): bool
    {
        $config = $preset->toComponentConfig();
        $table = DbTable::find()->where(['id' => (int)$preset->table_id])->one();
        if (!$table instanceof DbTable || !$this->canUseTable($table)) {
            return false;
        }

        $workflow = $this->resolveWorkflowConfig($table, $config);
        if (empty($workflow['approval_enabled'])) {
            return false;
        }

        $schema = Yii::$app->db->schema->getTableSchema((string)$table->name, true);
        if ($schema === null || empty($schema->primaryKey)) {
            return false;
        }

        $where = [];
        foreach ($schema->primaryKey as $key) {
            if (!array_key_exists($key, $rowKey)) {
                return false;
            }
            $where[$key] = $rowKey[$key];
        }

        $permission = new ProjectPermissionService();
        if (!$permission->canAccessRoute('table-builder/update', (new ActiveProjectContext())->getActiveProjectId())) {
            return false;
        }

        // Ownership enforcement: apply the framework OwnershipRuntime constraint
        // to this mutation ONLY when the preset's ownership config is enabled.
        // When disabled (or no identity / no project) the runtime contributes
        // nothing, so existing behavior is preserved exactly.
        $ownershipConfig = $config['ownership'] ?? null;
        if (is_array($ownershipConfig) && !empty($ownershipConfig['enabled']) && Yii::$app->has('ownership')) {
            $ownership = Yii::$app->ownership->resolveConstraint(
                (string)$table->name,
                (new ActiveProjectContext())->getActiveProjectId()
            );
            if (!empty($ownership['condition'])) {
                // Any transform row not owned by the Current Identity is excluded.
                // A fail-closed deny resolves to ['1' => '0'] -> zero rows affected.
                $where = ['and', $where, $ownership['condition']];
            }
        }

        return Yii::$app->db->createCommand()
            ->update((string)$table->name, [(string)$workflow['status_field'] => (string)$workflow['approved_value']], $where)
            ->execute() > 0;
    }

    public function exportPreset(MasterDatatable $preset, string $format = 'csv'): Response
    {
        $format = strtolower(trim($format));
        $config = $preset->toComponentConfig();
        $config['pagination'] = false;
        $config['page_size'] = 5000;

        return $this->exportFromConfig($config, $format, (string)$preset->name);
    }

    /**
     * PERBAIKAN BUG 4: Export langsung dari table/config page builder tanpa Master Datatable preset.
     */
    public function exportFromConfig(array $config, string $format = 'csv', ?string $filename = null): Response
    {
        $format = strtolower(trim($format));
        $config['pagination'] = false;
        $config['page_size'] = 5000;

        // ===== PRESET LOOKUP: Gunakan konfigurasi kolom yang sama dengan UI =====
        // Pastikan export menggunakan columns config yang sama dengan Datatable UI
        $presetTableId = (int)($config['tableId'] ?? $config['table_id'] ?? 0);
        $presetDatatableId = (int)($config['datatableId'] ?? $config['datatable_id'] ?? 0);
        if ($presetDatatableId <= 0 && $presetTableId > 0 && empty($config['_preset_loaded'])) {
            $exportPreset = MasterDatatable::findScoped()
                ->andWhere(['table_id' => $presetTableId, 'is_active' => 1])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            if ($exportPreset instanceof MasterDatatable) {
                $config = array_replace_recursive($exportPreset->toComponentConfig(), $config);
                $config['_preset_loaded'] = true;
            }
        }

        $data = $this->buildRenderData($config);
        if ($data === null) {
            Yii::$app->response->statusCode = 404;
            return Yii::$app->response;
        }

        $tableName = (string)($data['table']->label ?: $data['table']->name);
        $filename = preg_replace('/[^a-z0-9_-]+/i', '-', (string)($filename ?: $tableName)) ?: 'datatable';

        if ($format === 'pdf' || $format === 'print') {
            Yii::$app->response->format = Response::FORMAT_HTML;
            Yii::$app->response->content = $this->renderPrintableExport($data, $tableName);
            return Yii::$app->response;
        }

        $extension = $format === 'excel' ? 'xlsx' : 'csv';
        $contentType = $format === 'excel' 
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
            : 'text/csv; charset=UTF-8';

        // PERBAIKAN BUG 1: Jika format excel diminta via URL, kirim XML Spreadsheet yang VALID
        if ($format === 'excel') {
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
            Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xls"');
            Yii::$app->response->content = $this->renderExcelXmlExport($data, $tableName);
            return Yii::$app->response;
        }

        $lines = [];
        $lines[] = array_map(static fn(array $column): string => (string)($column['label'] ?? $column['field'] ?? ''), $data['columns']);
        foreach ($data['rows'] as $index => $row) {
            $line = [];
            foreach ($data['columns'] as $column) {
                $field = (string)($column['field'] ?? '');
                $line[] = isset($data['displayValues'][$index][$field]) ? (string)$data['displayValues'][$index][$field] : '';
            }
            $lines[] = $line;
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', $contentType);
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.' . $extension . '"');
        Yii::$app->response->content = "\xEF\xBB\xBF" . (string)$csv;
        return Yii::$app->response;
    }

    private function syncTableMetadataIfPhysical(DbTable $table): DbTable
    {
        if (Yii::$app->db->schema->getTableSchema((string)$table->name, true) === null) {
            return $table;
        }

        try {
            $scope = [];
            $projectId = (new ActiveProjectContext())->getActiveProjectId();
            if (ProjectSchema::supportsProjectContext() && $projectId !== null && $table->hasAttribute('project_id')) {
                $scope['project_id'] = $projectId;
            }
            if ($table->hasAttribute('user_id') && $table->user_id !== null) {
                $scope['user_id'] = (int)$table->user_id;
            }

            return (new DatabaseSchemaSyncService(
                Yii::$app->db,
                $scope,
                $table->hasAttribute('user_id') ? (int)$table->user_id : null,
                $projectId
            ))->syncTable((string)$table->name);
        } catch (\Throwable $e) {
            Yii::warning('Datatable metadata sync failed: ' . $e->getMessage(), 'table-builder-sync');
            return $table;
        }
    }

    private function resolveColumns(DbTable $table, array $config): array
    {
        $metadataColumns = $table->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        $metadataMap = [];
        foreach ($metadataColumns as $column) {
            $metadataMap[$column->name] = $column;
        }

        $configured = is_array($config['columns'] ?? null) ? $config['columns'] : [];
        $columns = [];
        foreach ($configured as $item) {
            if (!is_array($item)) {
                continue;
            }
            $field = trim((string)($item['field'] ?? $item['name'] ?? ''));
            if ($field === '' || !isset($metadataMap[$field]) || isset($columns[$field])) {
                continue;
            }
            if (array_key_exists('visible', $item) && !$item['visible']) {
                continue;
            }
            $columns[$field] = [
                'field' => $field,
                'label' => trim((string)($item['label'] ?? '')) ?: ($metadataMap[$field]->label ?: $field),
                'display_mode' => (string)($item['display_mode'] ?? 'text'),
                'link_text' => (string)($item['link_text'] ?? ''),
                'badge_color' => (string)($item['badge_color'] ?? '#3b82f6'),
            ] + $this->resolveForeignKeyDisplayConfig($metadataMap[$field], $item);
        }

        if (!empty($columns)) {
            return array_values($columns);
        }

        foreach ($metadataColumns as $column) {
            if ((bool)$column->is_primary || in_array($column->name, ['created_by', 'created_at', 'updated_by', 'updated_at'], true)) {
                continue;
            }
            $columns[] = [
                'field' => $column->name,
                'label' => $column->label ?: $column->name,
                'display_mode' => 'text',
                'link_text' => '',
                'badge_color' => '#3b82f6',
            ] + $this->resolveForeignKeyDisplayConfig($column, []);
        }

        return array_slice($columns, 0, 6);
    }

    private function resolveForeignKeyDisplayConfig(DbTableColumn $metadataColumn, array $item): array
    {
        $isForeignKey = $metadataColumn->hasAttribute('is_foreign_key') && (bool)$metadataColumn->getAttribute('is_foreign_key');

        $referencedTable = $metadataColumn->hasAttribute('referenced_table_name')
            ? trim((string)$metadataColumn->getAttribute('referenced_table_name'))
            : '';
        $referencedColumn = $metadataColumn->hasAttribute('referenced_column_name')
            ? trim((string)$metadataColumn->getAttribute('referenced_column_name'))
            : '';

        // Fallback: deteksi FK berdasarkan konvensi penamaan (_id suffix)
        // Ini menangani 'logical FK' yang tidak memiliki CONSTRAINT di database fisik
        if (!$isForeignKey && $referencedTable === '') {
            $colName = $metadataColumn->name ?? '';
            if (strlen($colName) > 3 && substr_compare($colName, '_id', -3) === 0) {
                $guessedTable = substr($colName, 0, -3);
                $guessedSchema = Yii::$app->db->schema->getTableSchema($guessedTable, true);
                if ($guessedSchema !== null) {
                    $referencedTable = $guessedTable;
                    $referencedColumn = !empty($guessedSchema->primaryKey) ? (string)$guessedSchema->primaryKey[0] : 'id';
                }
            }
        }

        if ($referencedTable === '') {
            return [];
        }

        $displayMode = strtolower(trim((string)($item['fkDisplayMode'] ?? $item['fk_display_mode'] ?? 'raw_id')));
        if (!in_array($displayMode, ['raw_id', 'related_column'], true)) {
            $displayMode = 'raw_id';
        }

        $relatedDisplayColumn = trim((string)($item['relatedDisplayColumn'] ?? $item['related_display_column'] ?? ''));
        $schema = Yii::$app->db->schema->getTableSchema($referencedTable, true);
        if ($schema === null) {
            return [];
        }

        if ($referencedColumn === '' || !isset($schema->columns[$referencedColumn])) {
            $referencedColumn = !empty($schema->primaryKey) ? (string)$schema->primaryKey[0] : (string)array_key_first($schema->columns);
        }

        // Gunakan stored metadata dari DbTableColumn jika tidak ada explicit config
        // Ini memastikan export menggunakan konfigurasi sistem yang sama dengan Datatable UI
        if ($relatedDisplayColumn === '' && $metadataColumn->hasAttribute('related_display_column')) {
            $storedDisplayColumn = trim((string)$metadataColumn->getAttribute('related_display_column'));
            if ($storedDisplayColumn !== '' && isset($schema->columns[$storedDisplayColumn]) && $storedDisplayColumn !== $referencedColumn) {
                $relatedDisplayColumn = $storedDisplayColumn;
                $displayMode = 'related_column';
            }
        }

        // Auto-guess display column — LAST RESORT fallback ketika tidak ada config atau metadata
        if ($relatedDisplayColumn === '' || !isset($schema->columns[$relatedDisplayColumn]) || $relatedDisplayColumn === $referencedColumn) {
            $guessedColumn = $this->guessForeignKeyLabelColumn($schema, $referencedColumn);
            if ($guessedColumn !== $referencedColumn && isset($schema->columns[$guessedColumn])) {
                $relatedDisplayColumn = $guessedColumn;
                // Override display mode to related_column when we auto-guessed
                $displayMode = 'related_column';
            } else {
                $relatedDisplayColumn = $referencedColumn;
            }
        }

        return [
            'fk_display_mode' => $displayMode,
            'related_display_column' => $relatedDisplayColumn,
            'referenced_table' => $referencedTable,
            'referenced_column' => $referencedColumn,
        ];
    }

    private function buildRelatedDisplayLookup(array $columns, array $rows): array
    {
        $lookup = [];
        if (empty($rows)) {
            return $lookup;
        }

        foreach ($columns as $column) {
            // Build display lookup for ALL FK columns regardless of fk_display_mode
            // This ensures export (CSV/Excel/PDF/Print) uses same resolved values as the datatable
            if (empty($column['referenced_table']) || empty($column['referenced_column'])) {
                continue;
            }

            $field = (string)($column['field'] ?? '');
            $referencedTable = (string)($column['referenced_table'] ?? '');
            $referencedColumn = (string)($column['referenced_column'] ?? '');
            $displayColumn = (string)($column['related_display_column'] ?? '');
            if ($field === '' || $referencedTable === '' || $referencedColumn === '' || $displayColumn === '') {
                continue;
            }

            $ids = [];
            foreach ($rows as $row) {
                $rawValue = $row[$field] ?? null;
                if ($rawValue === null || $rawValue === '') {
                    continue;
                }
                $ids[(string)$rawValue] = $rawValue;
            }
            if (empty($ids)) {
                continue;
            }

            try {
                $relatedRows = (new Query())
                    ->from($referencedTable)
                    ->select(array_values(array_unique([$referencedColumn, $displayColumn])))
                    ->where([$referencedColumn => array_values($ids)])
                    ->all(Yii::$app->db);
            } catch (\Throwable $e) {
                Yii::warning('Failed to build datatable FK display lookup: ' . $e->getMessage(), 'master-datatable');
                continue;
            }

            foreach ($relatedRows as $relatedRow) {
                $key = isset($relatedRow[$referencedColumn]) ? (string)$relatedRow[$referencedColumn] : '';
                if ($key === '') {
                    continue;
                }
                $lookup[$field][$key] = $relatedRow[$displayColumn] ?? null;
            }
        }

        return $lookup;
    }

    private function resolveActions(array $config): array
    {
        $requested = is_array($config['actions'] ?? null) ? $config['actions'] : [];
        $permission = new ProjectPermissionService();
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();

        return [
            'view' => !array_key_exists('view', $requested) || (bool)$requested['view']
                ? $permission->canAccessRoute('table-builder/view', $activeProjectId)
                : false,
            'edit' => !empty($requested['edit']) && $permission->canAccessRoute('table-builder/update', $activeProjectId),
            'delete' => !empty($requested['delete']) && $permission->canAccessRoute('table-builder/delete', $activeProjectId),
        ];
    }

    private function resolveEditMode(array $config): string
    {
        $requested = is_array($config['actions'] ?? null) ? $config['actions'] : [];
        $mode = strtolower(trim((string)($requested['edit_mode'] ?? $requested['editMode'] ?? 'custom')));
        return in_array($mode, ['default', 'custom'], true) ? $mode : 'custom';
    }

    private function resolveEditForm(array $config): array
    {
        $requested = is_array($config['actions'] ?? null) ? $config['actions'] : [];
        $formId = (int)($requested['edit_form_id'] ?? $requested['editFormId'] ?? $config['editFormId'] ?? 0);
        if ($formId <= 0) {
            return [];
        }

        $form = MasterForm::findByIdScoped($formId);
        if (!$form instanceof MasterForm) {
            return [];
        }

        $engine = new FormEngineService();
        $schema = $engine->getResolvedFormSchema($form);
        $renderer = new FormRenderService();
        $renderPayload = $renderer->buildRenderPayload($form, (array)($schema['fields'] ?? []), $schema['layout'] ?? null);
        $fields = [];
        $resolvedFields = is_array($renderPayload['fields'] ?? null) ? $renderPayload['fields'] : [];
        foreach ($resolvedFields as $field) {
            if (!is_array($field) || FormSystemFieldHelper::isSystemFieldData($field)) {
                continue;
            }

            $fieldName = trim((string)($field['name'] ?? $field['field_name'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            $fields[] = [
                'field' => $fieldName,
                'name' => $fieldName,
                'field_name' => $fieldName,
                'field_key' => $fieldName,
                'column_name' => $fieldName,
                'label' => trim((string)($field['label'] ?? $fieldName)) ?: $fieldName,
                'inputType' => (string)($field['inputType'] ?? FormSystemFieldHelper::resolveFieldInputType($field)),
                'placeholder' => (string)($field['placeholder'] ?? ''),
                'required' => !empty($field['required']),
                'defaultValue' => $field['default_value'] ?? null,
                'options' => $this->normalizeFormFieldOptions($field),
                'componentType' => (string)($field['component_type'] ?? ($field['type'] ?? 'text')),
                'is_foreign_key' => !empty($field['is_foreign_key']),
            ];
        }

        return [
            'id' => (int)$form->id,
            'name' => (string)$form->form_name,
            'fields' => $fields,
            'customHtml' => (string)($renderPayload['customHtml'] ?? ''),
            'customCss' => (string)($renderPayload['customCss'] ?? ''),
            'customJs' => (string)($renderPayload['customJs'] ?? ''),
            'useCustomCode' => !empty($renderPayload['useCustomCode']),
        ];
    }

    private function canUseTable(DbTable $table): bool
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        return $activeProjectId === null || !$table->hasAttribute('project_id') || (int)$table->project_id === (int)$activeProjectId;
    }

    /**
     * Delegate ownership scoping to the framework Ownership Runtime. The runtime
     * resolves the relationship to the Current Identity automatically (never
     * here) and applies the constraint via andWhere(). When ownership is not
     * enabled for this datatable, or the user has no identity to scope by, the
     * query is left untouched.
     */
    private function applyOwnershipToQuery(Query $query, string $tableName, array $config): void
    {
        $ownership = $config['ownership'] ?? null;
        if (!is_array($ownership) || empty($ownership['enabled'])) {
            return;
        }

        if (!Yii::$app->has('ownership')) {
            Yii::warning('Ownership diaktifkan pada datatable tetapi komponen ownership belum terdaftar.', 'ownership-runtime');
            return;
        }

        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        Yii::$app->ownership->applyToQuery($query, $tableName, $projectId);
    }

    private function renderTable(string $uid, DbTable $table, array $columns, array $rows, array $actions, string $editMode, array $editForm, array $primaryKeys, array $state, int $presetId = 0, array $filters = [], array $stats = [], array $workflow = [], array $exports = [], array $displayLookup = [], array $displayValues = []): string
    {
        $hasWorkflowAction = !empty($workflow['approval_enabled']) && !empty($primaryKeys);
        $hasActions = (in_array(true, $actions, true) || $hasWorkflowAction) && !empty($primaryKeys);
        $colspan = count($columns) + ($hasActions ? 1 : 0);
        $totalPages = max(1, (int)ceil(($state['total'] ?: 0) / $state['pageSize']));
        $rowFields = $this->resolveRowFields($table, $columns);
        $detailFields = $this->resolveDetailFields($columns);
        $reloadUrl = $presetId > 0 ? Url::to(['/master-datatable/reload', 'id' => $presetId]) : '';
        $exportUrls = [];
        foreach (['csv', 'excel', 'pdf', 'print'] as $fmt) {
            // PERBAIKAN BUG 4: URL export riil via preset ATAU langsung dari table_id
            if ($presetId > 0) {
                $exportUrls[$fmt] = Url::to(['/master-datatable/export', 'id' => $presetId, 'format' => $fmt] + Yii::$app->request->get());
            } else {
                $exportUrls[$fmt] = Url::to(['/master-datatable/export-table', 'table_id' => (int)$table->id, 'format' => $fmt] + Yii::$app->request->get());
            }
        }

        ob_start();
        ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.x/tabler-icons.min.css">
        
        <section
            class="master-datatable"
            id="<?= Html::encode($uid) ?>"
            data-datatable-table-id="<?= (int)$table->id ?>"
            data-datatable-primary-keys="<?= Html::encode(Json::encode($primaryKeys)) ?>"
            data-datatable-columns="<?= Html::encode(Json::encode($columns)) ?>"
            data-datatable-has-actions="<?= $hasActions ? '1' : '0' ?>"
            data-component="datatable"
            data-table="<?= Html::encode($table->name) ?>"
            data-reload-url="<?= Html::encode($reloadUrl) ?>"
            data-delete-url="<?= Html::encode(Url::to(['/master-datatable/delete-row', 'table_id' => $table->id])) ?>"
            data-approve-url="<?= $presetId > 0 ? Html::encode(Url::to(['/master-datatable/approve-row', 'id' => $presetId])) : '' ?>"
            data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>"
            data-csrf-token="<?= Html::encode(Yii::$app->request->csrfToken) ?>"
        >
            <style>
                #<?= Html::encode($uid) ?> { margin: 24px 0; border: 1px solid #e2e8f0; border-radius: 18px; background: #fff; overflow: hidden; box-shadow: 0 16px 36px rgba(15,23,42,.08); }
                #<?= Html::encode($uid) ?> .dt-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); }
                #<?= Html::encode($uid) ?> .dt-title { margin:0; font-size:17px; font-weight:800; color:#0f172a; }
                #<?= Html::encode($uid) ?> .dt-subtitle { margin:4px 0 0; font-size:12px; color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-search { min-width:260px; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; color:#0f172a; }
                #<?= Html::encode($uid) ?> .dt-tools { display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:flex-end; }
                #<?= Html::encode($uid) ?> .dt-filters { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; }
                #<?= Html::encode($uid) ?> .dt-filter label { display:block; margin-bottom:5px; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
                #<?= Html::encode($uid) ?> .dt-filter select { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:9px 10px; color:#0f172a; background:#fff; }
                #<?= Html::encode($uid) ?> .dt-stats { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
                #<?= Html::encode($uid) ?> .dt-stat { border:1px solid #e2e8f0; border-radius:14px; background:#fff; padding:12px; }
                #<?= Html::encode($uid) ?> .dt-stat-title { margin:0 0 8px; color:#475569; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
                #<?= Html::encode($uid) ?> .dt-stat-row { display:flex; justify-content:space-between; gap:10px; color:#0f172a; font-size:13px; padding:4px 0; border-top:1px solid #f1f5f9; }
                #<?= Html::encode($uid) ?> .dt-wrap { overflow-x:auto; }
                #<?= Html::encode($uid) ?> table { width:100%; border-collapse:collapse; }
                #<?= Html::encode($uid) ?> th { padding:13px 16px; background:#f8fafc; color:#475569; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
                #<?= Html::encode($uid) ?> td { padding:14px 16px; border-bottom:1px solid #f1f5f9; color:#1e293b; font-size:14px; vertical-align:top; }
                #<?= Html::encode($uid) ?> tr:hover td { background:#f8fafc; }
                #<?= Html::encode($uid) ?> .dt-actions { display:flex; flex-wrap:wrap; gap:8px; }
                #<?= Html::encode($uid) ?> .dt-btn { border:1px solid #dbe3ef; border-radius:10px; padding:7px 10px; background:#fff; color:#334155; font-size:12px; font-weight:700; text-decoration:none; cursor:pointer; }
                #<?= Html::encode($uid) ?> .dt-btn-danger { color:#b91c1c; border-color:#fecaca; background:#fff7f7; }
                #<?= Html::encode($uid) ?> .dt-empty { padding:42px 20px; text-align:center; color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-empty strong { display:block; color:#0f172a; font-size:16px; margin-bottom:4px; }
                #<?= Html::encode($uid) ?> .dt-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 20px; color:#64748b; font-size:12px; background:#fff; }
                #<?= Html::encode($uid) ?> .dt-page { display:flex; gap:8px; align-items:center; }
                #<?= Html::encode($uid) ?> .dt-page a { border:1px solid #dbe3ef; border-radius:10px; padding:7px 10px; color:#334155; text-decoration:none; font-weight:700; }
                #<?= Html::encode($uid) ?> .dt-row-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:24px; background:rgba(15,23,42,.6); backdrop-filter:blur(10px); z-index:9999; }
                #<?= Html::encode($uid) ?> .dt-row-modal.open { display:flex; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card { width:min(980px, 100%); max-height:min(90vh, 920px); overflow:hidden; display:flex; flex-direction:column; border-radius:24px; background:#fff; box-shadow:0 28px 90px rgba(15,23,42,.3); border:1px solid #e2e8f0; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme { background:#fff; border-color:#e2e8f0; box-shadow:0 28px 90px rgba(15,23,42,.3); }
                #<?= Html::encode($uid) ?> .dt-row-modal-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; padding:20px 22px; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-head { background:#fff; border-bottom-color:#e2e8f0; }
                #<?= Html::encode($uid) ?> .dt-row-modal-title { margin:0; color:#0f172a; font-size:18px; font-weight:800; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-title { color:#0f172a; font-weight:800; font-size:18px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-subtitle { margin:4px 0 0; color:#64748b; font-size:13px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-subtitle { color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-row-modal-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-badge { background:#eff6ff; color:#1d4ed8; font-weight:800; }
                #<?= Html::encode($uid) ?> .dt-row-modal-close,
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-close { border:1px solid #94a3b8; border-radius:12px; background:#cbd5e1; color:#1e293b; padding:8px 12px; font-weight:700; cursor:pointer; width:auto; height:auto; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 1px 2px rgba(15,23,42,.05); }
                #<?= Html::encode($uid) ?> .dt-row-modal-body { padding:22px; overflow:auto; background:linear-gradient(180deg,#fbfdff 0%,#fff 18%); }
                #<?= Html::encode($uid) ?> .dt-row-modal-body.view-mode { padding:18px 22px 22px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-body,
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-body.view-mode { background:#fff; }
                #<?= Html::encode($uid) ?> .dt-row-view-shell { display:block; }
                #<?= Html::encode($uid) ?> .dt-row-view-aside { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-view-main { display:block; }
                #<?= Html::encode($uid) ?> .dt-row-view-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
                #<?= Html::encode($uid) ?> .dt-row-view-item { border:1px solid #e2e8f0; border-radius:14px; background:#fff; padding:16px; min-height:74px; box-shadow:none; }
                #<?= Html::encode($uid) ?> .dt-row-view-item--lead { background:#fff; }
                #<?= Html::encode($uid) ?> .dt-row-view-label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
                #<?= Html::encode($uid) ?> .dt-row-view-value { color:#0f172a; font-size:14px; line-height:1.5; word-break:break-word; font-weight:600; }
                #<?= Html::encode($uid) ?> .dt-row-view-badge { display:inline-flex; align-items:center; gap:6px; }
                #<?= Html::encode($uid) ?> .dt-row-view-badge-circle { width:22px; height:22px; border-radius:999px; background:#eff6ff; color:#2563eb; font-size:12px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
                #<?= Html::encode($uid) ?> .dt-row-view-chips { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-summary { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-view-hero { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-view-panel { display:none; }
                #<?= Html::encode($uid) ?> .dt-summary-card { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-form-note { margin:0 0 16px; color:#64748b; font-size:13px; line-height:1.6; }
                #<?= Html::encode($uid) ?> .dt-row-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-form-grid-default { grid-template-columns:1fr; gap:12px; }
                #<?= Html::encode($uid) ?> .dt-row-form-grid-custom { grid-template-columns:repeat(2,minmax(0,1fr)); }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-shell { display:grid; gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-card { border:1px solid #e2e8f0; border-radius:22px; background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%); box-shadow:0 16px 34px rgba(15,23,42,.05); overflow:hidden; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-head { padding:18px 20px 16px; border-bottom:1px solid #e2e8f0; background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color:#fff; position:relative; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-head::after { content:''; position:absolute; left:0; right:0; bottom:0; height:3px; background:linear-gradient(90deg,#60a5fa 0%,#22c55e 100%); }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-kicker { display:inline-flex; align-items:center; gap:6px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#bfdbfe; margin-bottom:8px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-title { margin:0; font-size:18px; font-weight:800; color:#fff; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-desc { margin:8px 0 0; font-size:13px; line-height:1.6; color:rgba(255,255,255,.82); max-width:720px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-body { padding:18px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-form-grid { display:grid; grid-template-columns:1fr; gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-field { border:1px solid #e2e8f0; border-radius:18px; background:#fff; padding:16px 18px; box-shadow:0 8px 20px rgba(15,23,42,.03); }
                #<?= Html::encode($uid) ?> .dt-row-custom-field label { display:block; color:#334155; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-label-required { color:#dc2626; margin-left:4px; }
                #<?= Html::encode($uid) ?> .dt-row-custom-control { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:11px 12px; font-size:14px; color:#0f172a; background:#fff; box-sizing:border-box; transition:border-color .15s ease, box-shadow .15s ease; }
                #<?= Html::encode($uid) ?> .dt-row-custom-control:focus { outline:none; border-color:#64748b; box-shadow:0 0 0 3px rgba(100,116,139,.12); }
                #<?= Html::encode($uid) ?> .dt-row-custom-textarea { min-height:120px; resize:vertical; }
                #<?= Html::encode($uid) ?> .dt-row-custom-hint { margin-top:8px; font-size:12px; line-height:1.5; color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-row-choice-list { display:grid; gap:10px; }
                #<?= Html::encode($uid) ?> .dt-row-choice-item { display:flex; align-items:flex-start; gap:10px; padding:11px 12px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; color:#0f172a; font-size:14px; }
                #<?= Html::encode($uid) ?> .dt-row-choice-item input { margin-top:3px; accent-color:#0f172a; }
                #<?= Html::encode($uid) ?> .dt-row-fk-badge { display:inline-flex; align-items:center; gap:6px; margin-top:10px; padding:6px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
                #<?= Html::encode($uid) ?> .dt-row-custom-inline-note { margin-top:8px; font-size:12px; color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-row-field { border:1px solid #e2e8f0; border-radius:16px; background:#fff; padding:14px 16px; box-shadow:0 8px 20px rgba(15,23,42,.03); }
                #<?= Html::encode($uid) ?> .dt-row-field-default { padding:16px 18px; }
                #<?= Html::encode($uid) ?> .dt-row-field.wide { grid-column:1 / -1; }
                #<?= Html::encode($uid) ?> .dt-row-field label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
                #<?= Html::encode($uid) ?> .dt-row-field input,
                #<?= Html::encode($uid) ?> .dt-row-field select,
                #<?= Html::encode($uid) ?> .dt-row-field textarea { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:14px; color:#0f172a; background:#fff; }
                #<?= Html::encode($uid) ?> .dt-row-field textarea { min-height:92px; resize:vertical; }
                #<?= Html::encode($uid) ?> .dt-row-field .dt-check { display:flex; align-items:center; gap:10px; font-weight:700; color:#0f172a; }
                #<?= Html::encode($uid) ?> .dt-row-edit-shell { display:grid; gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-edit-chipset { display:flex; flex-wrap:wrap; gap:8px; }
                #<?= Html::encode($uid) ?> .dt-row-edit-chip { display:inline-flex; align-items:center; gap:6px; padding:7px 10px; border-radius:999px; background:#f8fafc; color:#475569; border:1px solid #e2e8f0; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
                #<?= Html::encode($uid) ?> .dt-row-muted { color:#64748b; font-size:13px; line-height:1.6; }
                #<?= Html::encode($uid) ?> .dt-row-modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 22px; border-top:1px solid #e2e8f0; background:#fff; }
                #<?= Html::encode($uid) ?> .dt-row-modal-footer.is-hidden { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-modal-footer.view-footer { justify-content:space-between; align-items:center; gap:16px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-footer { background:#fff; border-top-color:#e2e8f0; }
                #<?= Html::encode($uid) ?> .dt-row-modal-footer-info { display:flex; align-items:center; gap:8px; color:#a3a3a3; font-size:12px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-footer-info { color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-row-modal-footer-actions { display:flex; gap:8px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-footer-info { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-footer-actions { justify-content:flex-start; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-footer-actions .dt-btn { color:#0f172a; background:#fff; border-color:#cbd5e1; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-modal-footer-actions .dt-btn-primary { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-modal-footer .dt-btn { min-width:120px; }
                #<?= Html::encode($uid) ?> .dt-btn-primary { border:0.5px solid rgba(0,0,0,0.15); background:#111; color:#fff; font-weight:500; }
                #<?= Html::encode($uid) ?> .dt-btn-primary:hover { opacity:.85; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-btn:not(.dt-row-modal-close) { border-color:#5a554e; background:transparent; color:#e5dfd2; font-weight:500; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-btn-primary { background:#f4efe6; color:#111; border-color:#f4efe6; }
                #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-btn-primary:hover { opacity:1; background:#fff; }
                #<?= Html::encode($uid) ?> .dt-row-mode { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-mode.active { display:block; }
                @media (max-width: 768px) {
                    #<?= Html::encode($uid) ?> .dt-row-view-shell,
                    #<?= Html::encode($uid) ?> .dt-row-summary,
                    #<?= Html::encode($uid) ?> .dt-row-view-grid,
                    #<?= Html::encode($uid) ?> .dt-row-form-grid { grid-template-columns:1fr; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-card.view-theme .dt-row-view-hero h5 { font-size:20px; }
                    #<?= Html::encode($uid) ?> .dt-row-modal { padding:12px; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-body { padding:16px; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-head,
                    #<?= Html::encode($uid) ?> .dt-row-modal-footer { padding:16px; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-footer.view-footer { flex-direction:column; align-items:stretch; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-footer-actions { width:100%; justify-content:flex-end; }
                }
            </style>
            <div class="dt-head">
                <div>
                    <h3 class="dt-title"><?= Html::encode($table->label ?: $table->name) ?></h3>
                    <p class="dt-subtitle" data-datatable-subtitle><?= (int)$state['total'] ?> row<?= (int)$state['total'] === 1 ? '' : 's' ?> from <?= Html::encode($table->name) ?></p>
                </div>
                <div class="dt-tools">
                    <?php // PERBAIKAN BUG 4: Tombol export selalu punya URL riil (preset atau table_id) ?>
                    <?php $hasExports = !empty($exports) && (in_array(true, $exports, true) || count(array_filter($exports)) > 0); ?>
                    <?php if ($hasExports): ?>
                        <?php $exportLabels = ['csv' => 'CSV', 'excel' => 'Excel', 'pdf' => 'PDF', 'print' => 'Print']; ?>
                        <?php foreach ($exportLabels as $fmt => $label): ?>
                            <?php if (!isset($exports[$fmt]) || !empty($exports[$fmt])): ?>
                            <?php $exportUrl = $exportUrls[$fmt] ?? ''; ?>
                            <a class="dt-btn dt-export-btn" href="<?= Html::encode($exportUrl !== '' ? $exportUrl : '#') ?>" data-export-format="<?= Html::encode($fmt) ?>" data-export-url="<?= Html::encode($exportUrl) ?>" <?= $fmt === 'print' || $fmt === 'pdf' ? 'target="_blank" rel="noopener"' : '' ?>><?= Html::encode($label) ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($state['searchEnabled']): ?>
                    <form method="get" class="dt-search-form" action="<?= Html::encode(Url::current()) ?>" target="_top">
                        <?php foreach (Yii::$app->request->get() as $key => $value): ?>
                            <?php if (strpos((string)$key, 'dt_') === 0 && $key !== $state['searchParam'] && $key !== $state['pageParam']): ?>
                                <input type="hidden" name="<?= Html::encode($key) ?>" value="<?= Html::encode((string)$value) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input class="dt-search" type="search" name="<?= Html::encode($state['searchParam']) ?>" value="<?= Html::encode($state['search']) ?>" placeholder="Search...">
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($filters)): ?>
                <form class="dt-filters" method="get" action="<?= Html::encode(Url::current()) ?>" target="_top">
                    <?php foreach (Yii::$app->request->get() as $key => $value): ?>
                        <?php $filterPrefix = 'dt_filter_' . (int)$table->id . '_'; ?>
                        <?php if (strncmp((string)$key, $filterPrefix, strlen($filterPrefix)) !== 0 && $key !== $state['pageParam'] && strpos((string)$key, 'dt_') === 0): ?>
                            <input type="hidden" name="<?= Html::encode((string)$key) ?>" value="<?= Html::encode((string)$value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php foreach ($filters as $filter): ?>
                        <div class="dt-filter">
                            <label><?= Html::encode((string)$filter['label']) ?></label>
                            <select name="<?= Html::encode((string)$filter['param']) ?>">
                                <option value="">Semua</option>
                                <?php foreach ((array)$filter['options'] as $option): ?>
                                    <option value="<?= Html::encode((string)$option['value']) ?>" <?= (string)$filter['value'] === (string)$option['value'] ? 'selected' : '' ?>><?= Html::encode((string)$option['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>
            <?php if (!empty($stats)): ?>
                <div class="dt-stats">
                    <?php foreach ($stats as $stat): ?>
                        <div class="dt-stat">
                            <p class="dt-stat-title"><?= Html::encode((string)$stat['label']) ?></p>
                            <?php foreach ((array)$stat['rows'] as $row): ?>
                                <div class="dt-stat-row">
                                    <span><?= Html::encode($this->formatValue($row[$stat['field']] ?? '-')) ?></span>
                                    <strong><?= (int)($row['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="dt-wrap">
                <table>
                    <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?= Html::encode($column['label']) ?></th>
                        <?php endforeach; ?>
                        <?php if ($hasActions): ?><th>Actions</th><?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?= $this->renderRowsHtml($table, $columns, $rows, $actions, $primaryKeys, $displayLookup, $colspan, $presetId, $workflow, $displayValues) ?>
                    </tbody>
                </table>
            </div>
            <?php if ($state['paginationEnabled']): ?>
                <div class="dt-foot">
                    <span>Page <?= (int)$state['page'] ?> of <?= (int)$totalPages ?></span>
                    <div class="dt-page">
                        <?php if ($state['page'] > 1): ?>
                            <a href="<?= Html::encode($this->pageUrl($state['pageParam'], $state['page'] - 1)) ?>" target="_top">Previous</a>
                        <?php endif; ?>
                        <?php if ($state['page'] < $totalPages): ?>
                            <a href="<?= Html::encode($this->pageUrl($state['pageParam'], $state['page'] + 1)) ?>" target="_top">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="dt-row-modal" data-row-modal aria-hidden="true">
                <div class="dt-row-modal-card" role="dialog" aria-modal="true" aria-labelledby="<?= Html::encode($uid) ?>-row-modal-title">
                    <div class="dt-row-modal-head">
                        <div>
                            <div class="dt-row-modal-badge">Row detail</div>
                            <h4 class="dt-row-modal-title" id="<?= Html::encode($uid) ?>-row-modal-title">Row Details</h4>
                            <p class="dt-row-modal-subtitle" data-row-modal-subtitle>Ringkasan data yang jelas, rapi, dan mudah dipindai.</p>
                        </div>
                        <button type="button" class="dt-row-modal-close dt-btn" data-row-modal-close>Tutup</button>
                    </div>
                    <div class="dt-row-modal-body">
                        <div class="dt-row-mode" data-row-view-mode>
                            <div class="dt-row-view-shell">
                                <aside class="dt-row-view-aside">
                                    <div class="dt-row-view-hero">
                                        <p class="dt-row-view-hero-kicker">Row detail</p>
                                        <h5 data-row-hero-title>Data Row</h5>
                                        <p data-row-hero-subtitle>Ringkasan data yang jelas, rapi, dan mudah dipindai.</p>
                                        <div class="dt-row-view-chips" data-row-hero-chips></div>
                                    </div>
                                    <div class="dt-row-view-panel dt-row-view-panel--soft">
                                        <h5>Ringkasan</h5>
                                        <p data-row-reference class="dt-row-muted">Detail row akan tampil di sini.</p>
                                    </div>
                                    <div class="dt-row-summary" data-row-summary></div>
                                </aside>
                                <section class="dt-row-view-main">
                                    <div class="dt-row-view-grid" data-row-view-grid></div>
                                </section>
                            </div>
                        </div>
                        <form class="dt-row-mode" data-row-edit-mode id="<?= Html::encode($uid) ?>-row-form" method="post">
                            <input type="hidden" name="<?= Html::encode(Yii::$app->request->csrfParam) ?>" value="<?= Html::encode(Yii::$app->request->csrfToken) ?>">
                            <input type="hidden" name="table_id" value="<?= (int)$table->id ?>">
                            <input type="hidden" name="operation" value="upsert_row">
                            <input type="hidden" name="row_key" value="" data-row-key-input>
                            <p class="dt-row-form-note" data-row-form-note></p>
                            <div class="dt-row-form-grid" data-row-form-grid></div>
                        </form>
                    </div>
                    <div class="dt-row-modal-footer view-footer" data-row-footer>
                        <span class="dt-row-modal-footer-info" data-row-footer-info>
                            <i class="ti ti-database" style="font-size:14px;" aria-hidden="true"></i>
                            1 record
                        </span>
                        <div class="dt-row-modal-footer-actions">
                            <button type="submit" class="dt-btn" data-row-save-btn form="<?= Html::encode($uid) ?>-row-form">Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <script>
            (function() {
                const root = document.getElementById(<?= Json::encode($uid) ?>);
                if (!root) {
                    return;
                }

                const payload = <?= Json::encode([
                    'tableId' => (int)$table->id,
                    'saveUrl' => Url::to(['/table-builder/spreadsheet-action', 'id' => $table->id]),
                    'csrfToken' => Yii::$app->request->csrfToken,
                'editMode' => $editMode,
                'editForm' => $editForm,
                'fields' => $rowFields,
                'detailFields' => $detailFields,
                'columns' => $columns,
                'primaryKeys' => $primaryKeys,
                'hasActions' => $hasActions,
                'actions' => $actions,
                'deleteUrl' => Url::to(['/master-datatable/delete-row', 'table_id' => $table->id]),
                'csrfParam' => Yii::$app->request->csrfParam,
                'csrfToken' => Yii::$app->request->csrfToken,
                'assetBaseUrl' => self::ASSET_BASE_URL,
            ]) ?>;
                const modal = root.querySelector('[data-row-modal]');
                const viewMode = root.querySelector('[data-row-view-mode]');
                const editFormEl = root.querySelector('[data-row-edit-mode]');
                const summary = root.querySelector('[data-row-summary]');
                const viewGrid = root.querySelector('[data-row-view-grid]');
                const formGrid = root.querySelector('[data-row-form-grid]');
                const keyInput = root.querySelector('[data-row-key-input]');
                const saveButton = root.querySelector('[data-row-save-btn]');
                const modalFooter = root.querySelector('.dt-row-modal-footer');
                const footerInfo = root.querySelector('[data-row-footer-info]');
                const modalCard = root.querySelector('.dt-row-modal-card');
                const heroTitle = root.querySelector('[data-row-hero-title]');
                const heroSubtitle = root.querySelector('[data-row-hero-subtitle]');
                const modalTitle = root.querySelector('.dt-row-modal-title');
                const modalSubtitle = root.querySelector('[data-row-modal-subtitle]');
                const rowReference = root.querySelector('[data-row-reference]');
                const formNote = root.querySelector('[data-row-form-note]');
                let activeRow = null;

                function escapeHtml(value) {
                    return String(value === null || value === undefined ? '' : value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function getRowData(row) {
                    try {
                        return JSON.parse(row.getAttribute('data-row-values') || '{}') || {};
                    } catch (error) {
                        return {};
                    }
                }

                function getRowDisplayData(row) {
                    try {
                        return JSON.parse(row.getAttribute('data-row-display-values') || '{}') || {};
                    } catch (error) {
                        return {};
                    }
                }

                function getRowKey(row) {
                    try {
                        return JSON.parse(row.getAttribute('data-row-key') || '{}') || {};
                    } catch (error) {
                        return {};
                    }
                }

                function getColumnsMeta() {
                    return Array.isArray(payload.columns) ? payload.columns : [];
                }

                function getPrimaryKeys() {
                    return Array.isArray(payload.primaryKeys) ? payload.primaryKeys : [];
                }

                function stringifyValue(value) {
                    if (value === null || value === undefined || value === '') {
                        return '';
                    }
                    if (Array.isArray(value)) {
                        return value.join(', ');
                    }
                    if (typeof value === 'object') {
                        try {
                            return JSON.stringify(value);
                        } catch (error) {
                            return '';
                        }
                    }
                    return String(value);
                }

                function buildRowKeyFromData(rowData) {
                    const rowKey = {};
                    const keys = getPrimaryKeys();
                    keys.forEach(function(key) {
                        if (Object.prototype.hasOwnProperty.call(rowData || {}, key)) {
                            rowKey[key] = rowData[key];
                        }
                    });
                    return rowKey;
                }

                function buildRowDisplayDataFromMessage(data, rowData) {
                    const displayData = {};
                    const sourceDisplay = data && data.submittedDisplayData && typeof data.submittedDisplayData === 'object'
                        ? data.submittedDisplayData
                        : {};
                    const sourceInserted = data && data.insertedData && typeof data.insertedData === 'object'
                        ? data.insertedData
                        : {};
                    const sourceSubmitted = data && data.submittedData && typeof data.submittedData === 'object'
                        ? data.submittedData
                        : {};
                    const columns = getColumnsMeta();

                    columns.forEach(function(column) {
                        const field = String(column.field || '');
                        if (!field) {
                            return;
                        }

                        if (Object.prototype.hasOwnProperty.call(sourceDisplay, field)) {
                            displayData[field] = sourceDisplay[field];
                            return;
                        }

                        if (Object.prototype.hasOwnProperty.call(sourceInserted, field)) {
                            displayData[field] = sourceInserted[field];
                            return;
                        }

                        if (Object.prototype.hasOwnProperty.call(sourceSubmitted, field)) {
                            displayData[field] = sourceSubmitted[field];
                            return;
                        }

                        if (Object.prototype.hasOwnProperty.call(rowData || {}, field)) {
                            displayData[field] = rowData[field];
                        }
                    });

                    return displayData;
                }

                function normalizeAssetUrl(url) {
                    if (!url) return url;
                    if (/^(https?:\/\/|\/\/|\/)/i.test(url)) return url;
                    return (payload.assetBaseUrl || '/uploads/workspace/') + url;
                }

                function buildRowCellsHtml(rowData, rowDisplayData) {
                    const columns = getColumnsMeta();
                    return columns.map(function(column) {
                        const field = String(column.field || '');
                        const rawValue = Object.prototype.hasOwnProperty.call(rowData || {}, field) ? rowData[field] : null;
                        const displayValue = Object.prototype.hasOwnProperty.call(rowDisplayData || {}, field) ? rowDisplayData[field] : rawValue;
                        const displayMode = String(column.display_mode || 'text');
                        const value = rawValue !== null && rawValue !== '' ? String(rawValue) : '';
                        if (displayMode === 'image' && value) {
                            return '<td><img src="' + escapeHtml(normalizeAssetUrl(value)) + '" alt="" style="max-width:120px;max-height:80px;border-radius:6px;object-fit:cover;background:#f1f5f9;" loading="lazy"></td>';
                        }
                        if (displayMode === 'file' && value) {
                            const fileName = value.split('/').pop() || value;
                            return '<td><a href="' + escapeHtml(normalizeAssetUrl(value)) + '" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;color:#2563eb;text-decoration:none;"><span style="font-size:14px;">&#128206;</span> ' + escapeHtml(fileName) + '</a></td>';
                        }
                        if (displayMode === 'link' && value) {
                            const linkText = String(column.link_text || value);
                            return '<td><a href="' + escapeHtml(normalizeAssetUrl(value)) + '" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:none;">' + escapeHtml(linkText) + '</a></td>';
                        }
                        if (displayMode === 'badge' && value) {
                            const badgeColor = String(column.badge_color || '#3b82f6');
                            return '<td><span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;color:#fff;background:' + escapeHtml(badgeColor) + ';">' + escapeHtml(value) + '</span></td>';
                        }
                        return '<td>' + escapeHtml(stringifyValue(displayValue)) + '</td>';
                    }).join('');
                }

                function buildActionCellHtml(rowKey) {
                    if (!payload.hasActions) {
                        return '';
                    }

                    const rowKeyJson = JSON.stringify(rowKey || {});
                    let html = '<td><div class="dt-actions">';
                    if (payload.actions && payload.actions.view) {
                        html += '<button type="button" class="dt-btn" data-row-action="view">View</button>';
                    }
                    if (payload.actions && payload.actions.edit) {
                        html += '<button type="button" class="dt-btn" data-row-action="edit">Edit</button>';
                    }
                    if (payload.actions && payload.actions.delete) {
                        html += '<button class="dt-btn dt-btn-danger" type="button" data-row-action="delete">Delete</button>';
                    }
                    html += '</div></td>';
                    return html;
                }

                function buildRowHtmlFromData(rowData, rowDisplayData) {
                    const rowKey = buildRowKeyFromData(rowData);
                    const hasRowKey = Object.keys(rowKey).length > 0;
                    const rowKeyJson = JSON.stringify(rowKey);
                    const rowValuesJson = JSON.stringify(rowData || {});
                    const rowDisplayJson = JSON.stringify(rowDisplayData || {});
                    const cells = buildRowCellsHtml(rowData, rowDisplayData);
                    const actionCell = payload.hasActions ? buildActionCellHtml(rowKey) : '';
                    return '<tr data-row-key="' + escapeHtml(rowKeyJson) + '" data-row-values="' + escapeHtml(rowValuesJson) + '" data-row-display-values="' + escapeHtml(rowDisplayJson) + '"' + (hasRowKey ? '' : ' data-row-generated="1"') + '>' +
                        cells +
                        actionCell +
                    '</tr>';
                }

                function updateEmptyStateAfterInsert() {
                    const emptyCell = root.querySelector('tbody tr td .dt-empty');
                    if (!emptyCell) {
                        return;
                    }
                    const emptyRow = emptyCell.closest('tr');
                    if (emptyRow) {
                        emptyRow.remove();
                    }
                }

                function ensureTbody() {
                    return root.querySelector('tbody');
                }

                function upsertRowFromSubmit(data) {
                    if (!data || !data.success) {
                        return false;
                    }
                    const targetTableId = parseInt(data.targetTableId || payload.tableId || '0', 10);
                    if (targetTableId !== parseInt(payload.tableId, 10)) {
                        return false;
                    }

                    const rowData = data.insertedData && typeof data.insertedData === 'object'
                        ? data.insertedData
                        : (data.submittedData && typeof data.submittedData === 'object' ? data.submittedData : null);
                    if (!rowData) {
                        return false;
                    }

                    const rowDisplayData = buildRowDisplayDataFromMessage(data, rowData);
                    const rowKey = data.insertedRowKey && typeof data.insertedRowKey === 'object'
                        ? data.insertedRowKey
                        : buildRowKeyFromData(rowData);
                    const tbody = ensureTbody();
                    if (!tbody) {
                        return false;
                    }

                    updateEmptyStateAfterInsert();

                    const rowKeyJson = JSON.stringify(rowKey || {});
                    if (rowKeyJson) {
                        const existingRows = tbody.querySelectorAll('tr[data-row-key]');
                        for (let index = 0; index < existingRows.length; index += 1) {
                            const existingRow = existingRows[index];
                            if ((existingRow.getAttribute('data-row-key') || '') === rowKeyJson) {
                                existingRow.outerHTML = buildRowHtmlFromData(rowData, rowDisplayData);
                                return true;
                            }
                        }
                    }

                    tbody.insertAdjacentHTML('afterbegin', buildRowHtmlFromData(rowData, rowDisplayData));
                    return true;
                }

                function syncRowActionBindings() {
                    return;
                }

                function openModal() {
                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    modal.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    activeRow = null;
                    if (document.activeElement && modal.contains(document.activeElement)) {
                        root.focus();
                    }
                }

                function formatViewValue(field, value) {
                    if (value === null || value === undefined || value === '') {
                        return '<span class="dt-row-muted">-</span>';
                    }

                    if (field.inputType === 'boolean') {
                        const active = String(value) === '1' || String(value).toLowerCase() === 'true';
                        return '<span class="dt-btn" style="display:inline-flex;align-items:center;gap:6px;">' + (active ? 'Aktif' : 'Nonaktif') + '</span>';
                    }

                    const displayMode = field.display_mode || 'text';
                    if (displayMode === 'image') {
                        const url = normalizeAssetUrl(String(value));
                        if (url.match(/^https?:\/\//i) || url.match(/^\//) || url.match(/^data:image/i) || url.match(/[.](jpg|jpeg|png|gif|webp|svg|bmp)([?#]|$)/i)) {
                            return '<img src="' + escapeHtml(url) + '" alt="" style="max-width:200px;max-height:120px;border-radius:8px;object-fit:cover;background:#f1f5f9;" loading="lazy">';
                        }
                    }
                    if (displayMode === 'file') {
                        const url = normalizeAssetUrl(String(value));
                        const fileName = url.split('/').pop() || url;
                        return '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;color:#2563eb;text-decoration:none;">'
                            + '<span style="font-size:16px;">&#128206;</span> ' + escapeHtml(fileName) + '</a>';
                    }
                    if (displayMode === 'link') {
                        const url = normalizeAssetUrl(String(value));
                        const linkText = (field.link_text || url);
                        return '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:none;">' + escapeHtml(linkText) + '</a>';
                    }
                    if (displayMode === 'badge') {
                        const color = field.badge_color || '#3b82f6';
                        return '<span style="display:inline-block;padding:2px 12px;border-radius:999px;font-size:11px;font-weight:600;color:#fff;background:' + escapeHtml(color) + ';">' + escapeHtml(value) + '</span>';
                    }

                    if (typeof value === 'object') {
                        return '<pre style="margin:0;white-space:pre-wrap;word-break:break-word;">' + escapeHtml(JSON.stringify(value, null, 2)) + '</pre>';
                    }

                    return escapeHtml(value);
                }

                function getDisplayValue(field, value) {
                    if (value === null || value === undefined || value === '') {
                        return '-';
                    }
                    if (field && (field.inputType === 'boolean' || field.inputType === 'checkbox')) {
                        return (String(value) === '1' || String(value).toLowerCase() === 'true') ? 'Aktif' : 'Nonaktif';
                    }
                    if (field && field.inputType === 'select' && Array.isArray(field.options) && field.options.length) {
                        const option = field.options.find(function(item) {
                            return String(item && item.value !== undefined ? item.value : '') === String(value);
                        });
                        if (option) {
                            const optionLabel = String(option.label ?? option.value ?? value);
                            if (optionLabel.trim() !== '') {
                                return optionLabel;
                            }
                        }
                    }
                    if (Array.isArray(value)) {
                        return value.join(', ');
                    }
                    return String(value);
                }

                function usesRelatedColumnDisplay(field) {
                    return !!(field && field.is_foreign_key && String(field.fk_display_mode || 'raw_id') === 'related_column');
                }

                function getRowDetailDisplayValue(field, rowData, rowDisplayData) {
                    const fieldName = field.field || field.name || '';
                    if (Object.prototype.hasOwnProperty.call(rowDisplayData || {}, fieldName)) {
                        const displayValue = rowDisplayData[fieldName];
                        if (displayValue !== null && displayValue !== undefined && String(displayValue) !== '') {
                            return String(displayValue);
                        }
                    }

                    const rawValue = rowData[fieldName];
                    if (field && field.is_foreign_key) {
                        return rawValue === null || rawValue === undefined || rawValue === '' ? '-' : String(rawValue);
                    }

                    return getDisplayValue(field, rawValue);
                }

                function getInitials(text) {
                    const raw = String(text || '').trim();
                    if (!raw) return 'RD';
                    const parts = raw.split(/\s+/).filter(Boolean);
                    const first = parts[0] ? parts[0].charAt(0) : 'R';
                    const second = parts[1] ? parts[1].charAt(0) : (parts[0] ? parts[0].charAt(1) : 'D');
                    return (first + second).toUpperCase().slice(0, 2);
                }

                function inputValue(field, value) {
                    if (value === null || value === undefined) {
                        return '';
                    }
                    if (Array.isArray(value)) {
                        return value.join(', ');
                    }
                    if (typeof value === 'object') {
                        try {
                            return JSON.stringify(value);
                        } catch (error) {
                            return '';
                        }
                    }
                    if (field.inputType === 'datetime' || field.inputType === 'datetime-local') {
                        return String(value).slice(0, 16).replace(' ', 'T');
                    }
                    return String(value);
                }

                function getSelectedValue(field, value) {
                    if (Array.isArray(value)) {
                        return value.map(function(item) {
                            return String(item);
                        });
                    }
                    if (value === null || value === undefined) {
                        return '';
                    }
                    return String(value);
                }

                function renderChoiceItems(field, fieldName, value, multiple) {
                    const options = Array.isArray(field.options) ? field.options : [];
                    const selectedValues = Array.isArray(value) ? value.map(function(item) { return String(item); }) : [String(value)];
                    if (!options.length) {
                        return '<input type="text" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(Array.isArray(value) ? value.join(', ') : value) + '">';
                    }

                    return '<div class="dt-row-choice-list">' + options.map(function(option, index) {
                        const optionValue = String(option.value ?? option.label ?? index);
                        const optionLabel = String(option.label ?? option.value ?? optionValue);
                        const checked = selectedValues.indexOf(optionValue) !== -1 ? ' checked' : '';
                        const nameAttr = multiple ? fieldName + '[]' : fieldName;
                        return '<label class="dt-row-choice-item">' +
                            '<input type="' + (multiple ? 'checkbox' : 'radio') + '" name="' + escapeHtml(nameAttr) + '" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(optionValue) + '"' + checked + (field.readonly ? ' disabled' : '') + '>' +
                            '<span>' + escapeHtml(optionLabel) + '</span>' +
                        '</label>';
                    }).join('') + '</div>';
                }

                function renderCustomField(field, rowData) {
                    const fieldName = field.field || field.name || '';
                    const label = field.label || fieldName || 'Field';
                    const value = rowData[fieldName];
                    const normalizedValue = inputValue(field, value);
                    const required = field.required ? ' <span class="dt-row-custom-label-required">*</span>' : '';
                    const hint = field.placeholder ? '<div class="dt-row-custom-hint">' + escapeHtml(field.placeholder) + '</div>' : '';
                    const isFk = field.componentType === 'foreign_key' || field.is_foreign_key;
                    let control = '';

                    if (field.inputType === 'textarea') {
                        control = '<textarea class="dt-row-custom-control dt-row-custom-textarea" data-row-field="' + escapeHtml(fieldName) + '" rows="4"' + (field.readonly ? ' readonly' : '') + '>' + escapeHtml(normalizedValue) + '</textarea>';
                    } else if (field.inputType === 'select') {
                        const options = Array.isArray(field.options) ? field.options : [];
                        const optionHtml = ['<option value="">-- Pilih --</option>'].concat(options.map(function(option, index) {
                            const optionValue = String(option.value ?? index);
                            const optionLabel = String(option.label ?? option.value ?? optionValue);
                            const selected = String(getSelectedValue(field, value)) === optionValue ? ' selected' : '';
                            return '<option value="' + escapeHtml(optionValue) + '"' + selected + '>' + escapeHtml(optionLabel) + '</option>';
                        })).join('');
                        control = '<select class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '"' + (field.readonly ? ' disabled' : '') + '>' + optionHtml + '</select>';
                    } else if (field.inputType === 'boolean' || field.inputType === 'checkbox') {
                        const checked = String(value) === '1' || String(value).toLowerCase() === 'true' ? ' checked' : '';
                        control = '<label class="dt-row-choice-item form-check form-switch" style="margin:0;display:flex;align-items:center;gap:8px;">' +
                            '<input type="checkbox" class="form-check-input" data-row-field="' + escapeHtml(fieldName) + '" value="1"' + checked + (field.readonly ? ' disabled' : '') + '>' +
                            '<span>' + escapeHtml(label) + '</span>' +
                        '</label>';
                    } else if (field.inputType === 'radio') {
                        control = renderChoiceItems(field, fieldName, value, false);
                    } else if (field.inputType === 'checkboxes') {
                        control = renderChoiceItems(field, fieldName, Array.isArray(value) ? value : (String(value).trim() !== '' ? String(value).split(',').map(function(item) { return item.trim(); }) : []), true);
                    } else if (field.inputType === 'date') {
                        control = '<input type="date" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(normalizedValue) + '"' + (field.readonly ? ' readonly' : '') + '>';
                    } else if (field.inputType === 'datetime' || field.inputType === 'datetime-local') {
                        control = '<input type="datetime-local" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(normalizedValue) + '"' + (field.readonly ? ' readonly' : '') + '>';
                    } else if (field.inputType === 'number') {
                        control = '<input type="number" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(normalizedValue) + '"' + (field.readonly ? ' readonly' : '') + '>';
                    } else if (field.inputType === 'password') {
                        control = '<input type="password" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(normalizedValue) + '"' + (field.readonly ? ' readonly' : '') + '>';
                    } else if (field.inputType === 'file') {
                        control = '<input type="file" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '"' + (field.readonly ? ' disabled' : '') + '>';
                    } else {
                        control = '<input type="text" class="dt-row-custom-control" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(normalizedValue) + '"' + (field.readonly ? ' readonly' : '') + '>';
                    }

                    return '<div class="dt-row-custom-field' + ((field.inputType === 'textarea' || field.inputType === 'checkboxes') ? ' wide' : '') + '">' +
                        '<label>' + escapeHtml(label) + required + '</label>' +
                        control +
                        hint +
                        (isFk ? '<div class="dt-row-fk-badge"><span class="material-symbols-outlined" style="font-size:12px;">link</span> Foreign key field</div>' : '') +
                    '</div>';
                }

                function getHeroMetaText(rowData, rowDisplayData) {
                    const priorityFields = (payload.detailFields || payload.fields || []).filter(function(field) {
                        const label = String(field.label || field.field || '').toLowerCase();
                        const name = String(field.field || '').toLowerCase();
                        return /^(user|role|status|type|tipe|kelas|level|kategori|group|grup|jabatan|bagian)$/.test(label)
                            || /(user|role|status|type|tipe|kelas|level|kategori|group|grup|jabatan|bagian)/.test(name);
                    });

                    if (priorityFields.length) {
                        const candidate = priorityFields[0];
                        const candidateValue = getRowDetailDisplayValue(candidate, rowData, rowDisplayData || {});
                        if (candidateValue && candidateValue !== '-') {
                            return candidateValue;
                        }
                    }

                    if (payload.editForm && payload.editForm.name) {
                        return String(payload.editForm.name).trim();
                    }

                    return 'record detail';
                }

                function renderSummary(rowKey, rowData, rowDisplayData) {
                    const detailFields = payload.detailFields || payload.fields || [];
                    const primaryField = detailFields[0] || null;
                    const secondaryField = detailFields[1] || null;
                    const primaryValue = primaryField ? getRowDetailDisplayValue(primaryField, rowData || {}, rowDisplayData || {}) : '';
                    const secondaryValue = secondaryField ? getRowDetailDisplayValue(secondaryField, rowData || {}, rowDisplayData || {}) : '';
                    const displayName = primaryValue && primaryValue !== '-' ? primaryValue : (primaryField ? primaryField.label : 'Data Row');
                    const roleText = getHeroMetaText(rowData || {}, rowDisplayData || {});
                    const idText = Object.keys(rowKey || {}).length ? 'ID #' + Object.values(rowKey).join(' · ') : 'Record detail';
                    const initials = getInitials(displayName);

                    if (heroTitle) {
                        heroTitle.textContent = displayName;
                    }
                    if (heroSubtitle) {
                        heroSubtitle.textContent = roleText + ' · ' + idText;
                    }
                    if (rowReference) {
                        rowReference.textContent = 'Data ini ditampilkan dengan ringkasan singkat di kiri dan detail field di kanan, supaya mudah dipindai tanpa terasa penuh.';
                    }
                    summary.innerHTML = '<div class="dt-summary-card primary">' +
                        '<span class="dt-summary-label">Ringkasan utama</span>' +
                        '<div class="dt-summary-main">' +
                            '<div class="dt-summary-avatar">' + escapeHtml(initials) + '</div>' +
                            '<div class="dt-summary-meta">' +
                                '<div class="dt-summary-name">' + escapeHtml(displayName) + '</div>' +
                                '<div class="dt-summary-role">' + escapeHtml(roleText) + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                }

                function renderView(rowData, rowDisplayData) {
                    const gridFields = payload.detailFields || payload.fields || [];

                    viewGrid.innerHTML = gridFields.map(function(field, index) {
                        const value = rowData[field.field];
                        const displayValue = getRowDetailDisplayValue(field, rowData, rowDisplayData || {});
                        const isGenderLike = /^(jk|jenis kelamin|gender|sex)$/i.test(String(field.label || field.field || ''));
                        const icon = isGenderLike && displayValue ? String(displayValue).trim().charAt(0).toUpperCase() : null;
                        const valueHtml = isGenderLike && icon
                            ? '<div class="dt-row-view-badge"><span class="dt-row-view-badge-circle">' + escapeHtml(icon) + '</span><span class="dt-row-view-value">' + escapeHtml(displayValue) + '</span></div>'
                            : '<div class="dt-row-view-value">' + (usesRelatedColumnDisplay(field) || field.inputType === 'select' || field.is_foreign_key ? escapeHtml(displayValue) : formatViewValue(field, value)) + '</div>';
                        return '<div class="dt-row-view-item' + (index === 0 ? ' dt-row-view-item--lead' : '') + '">' +
                            '<span class="dt-row-view-label">' + escapeHtml(field.label) + '</span>' +
                            valueHtml +
                        '</div>';
                    }).join('');
                }

                function getCustomFormFields() {
                    if (payload.editForm && Array.isArray(payload.editForm.fields) && payload.editForm.fields.length) {
                        return payload.editForm.fields;
                    }
                    return payload.fields;
                }

                function getControlByFieldName(rootEl, fieldName) {
                    return rootEl.querySelector('[data-row-field="' + fieldName + '"], [name="' + fieldName + '"], [name="' + fieldName + '[]"]');
                }

                function stripOuterFormTags(html) {
                    return String(html || '')
                        .replace(/<form\b/gi, '<div')
                        .replace(/<\/form>/gi, '</div>');
                }

                function applyValuesToCustomMarkup(rootEl, rowData) {
                    const fields = getCustomFormFields();
                    fields.forEach(function(field) {
                        const fieldName = field.field || field.name || '';
                        if (!fieldName) {
                            return;
                        }
                        const inputs = rootEl.querySelectorAll('[data-row-field="' + fieldName + '"], [name="' + fieldName + '"], [name="' + fieldName + '[]"]');
                        if (!inputs || !inputs.length) {
                            return;
                        }

                        const rawValue = rowData[fieldName];
                        const normalized = inputValue(field, rawValue);
                        const selectedArray = Array.isArray(rawValue) ? rawValue.map(function(item) { return String(item); }) : String(normalized || '').split(',').map(function(item) { return item.trim(); }).filter(Boolean);

                        Array.prototype.forEach.call(inputs, function(input) {
                            const type = (input.type || '').toLowerCase();
                            if (type === 'checkbox') {
                                if (field.inputType === 'checkboxes') {
                                    input.checked = selectedArray.indexOf(String(input.value)) !== -1;
                                } else {
                                    input.checked = String(rawValue) === '1' || String(rawValue).toLowerCase() === 'true';
                                }
                                return;
                            }
                            if (type === 'radio') {
                                input.checked = String(input.value) === String(rawValue);
                                return;
                            }
                            if (type === 'date') {
                                input.value = String(normalized).slice(0, 10);
                                return;
                            }
                            if (type === 'datetime-local') {
                                input.value = String(normalized).slice(0, 16).replace(' ', 'T');
                                return;
                            }
                            if (input.tagName === 'SELECT' && field.inputType === 'checkboxes') {
                                Array.prototype.forEach.call(input.options, function(option) {
                                    option.selected = selectedArray.indexOf(String(option.value)) !== -1;
                                });
                                return;
                            }
                            input.value = normalized;
                        });
                    });
                }

                function collectValuesFromCustomMarkup(rootEl) {
                    const values = {};
                    const fields = getCustomFormFields();
                    fields.forEach(function(field) {
                        const fieldName = field.field || field.name || '';
                        if (!fieldName) {
                            return;
                        }
                        const inputs = rootEl.querySelectorAll('[data-row-field="' + fieldName + '"], [name="' + fieldName + '"], [name="' + fieldName + '[]"]');
                        if (!inputs || !inputs.length) {
                            return;
                        }

                        const firstInput = inputs[0];
                        const type = (firstInput.type || '').toLowerCase();
                        if (field.inputType === 'checkboxes') {
                            values[fieldName] = Array.prototype.slice.call(inputs)
                                .filter(function(input) { return input.checked; })
                                .map(function(input) { return input.value; });
                            return;
                        }
                        if (field.inputType === 'radio') {
                            const selected = Array.prototype.slice.call(inputs).find(function(input) {
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

                function logModalFieldChange(input, fieldName, field) {
                    const selected = input && input.tagName === 'SELECT' ? input.options[input.selectedIndex] : null;
                    console.debug('[MasterDatatable] FIELD CHANGE', {
                        row_id: activeRow ? (activeRow.getAttribute('data-row-key') || '') : '',
                        column: fieldName,
                        value: input && (input.type || '').toLowerCase() === 'checkbox' ? (input.checked ? 1 : 0) : (input ? input.value : ''),
                        option_label: selected ? selected.textContent : '',
                        field_type: field ? (field.inputType || field.componentType || '') : (input ? (input.type || input.tagName.toLowerCase()) : '')
                    });
                }

                function bindModalChangeDebug(rootEl) {
                    const fields = getCustomFormFields();
                    fields.forEach(function(field) {
                        const fieldName = field.field || field.name || '';
                        if (!fieldName) {
                            return;
                        }
                        const inputs = rootEl.querySelectorAll('[data-row-field="' + fieldName + '"], [name="' + fieldName + '"], [name="' + fieldName + '[]"]');
                        Array.prototype.forEach.call(inputs, function(input) {
                            if (input.__dtDebugBound) {
                                return;
                            }
                            input.__dtDebugBound = true;
                            input.addEventListener('change', function() {
                                logModalFieldChange(input, fieldName, field);
                            });
                        });
                    });
                }

                function suppressCustomSubmitControls(rootEl) {
                    rootEl.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(function(button) {
                        button.style.display = 'none';
                        button.setAttribute('aria-hidden', 'true');
                        button.setAttribute('tabindex', '-1');
                    });
                }

                function renderCustomEdit(rowData) {
                    formGrid.className = 'dt-row-custom-form-shell';
                    if (formNote) {
                        const formName = payload.editForm && payload.editForm.name ? payload.editForm.name : 'form terpilih';
                        formNote.textContent = 'Custom form modal memakai layout asli dari ' + formName + ' dan mengikuti struktur form yang kamu buat.';
                    }
                    const customHtml = (payload.editForm && payload.editForm.customHtml) ? String(payload.editForm.customHtml) : '';
                    if (customHtml.trim() !== '') {
                        const customCss = (payload.editForm && payload.editForm.customCss) ? String(payload.editForm.customCss) : '';
                        const customJs = (payload.editForm && payload.editForm.customJs) ? String(payload.editForm.customJs) : '';
                        formGrid.innerHTML = '<div class="dt-row-custom-form-card dt-row-custom-form-live">' +
                            (customCss.trim() !== '' ? '<style>' + customCss + '</style>' : '') +
                            stripOuterFormTags(customHtml) +
                        '</div>';
                        if (customJs.trim() !== '') {
                            const script = document.createElement('script');
                            script.textContent = '(function(){try{' + customJs + '}catch(e){console.error(e);}})();';
                            formGrid.appendChild(script);
                        }
                        applyValuesToCustomMarkup(formGrid, rowData);
                        suppressCustomSubmitControls(formGrid);
                        bindModalChangeDebug(formGrid);
                        return;
                    }
                    const fields = getCustomFormFields();
                    formGrid.innerHTML = '<div class="dt-row-custom-form-card">' +
                        '<div class="dt-row-custom-form-head">' +
                            '<div class="dt-row-custom-form-kicker">Custom Form Modal</div>' +
                            '<h5 class="dt-row-custom-form-title">' + escapeHtml((payload.editForm && payload.editForm.name) ? payload.editForm.name : 'Form terpilih') + '</h5>' +
                            '<p class="dt-row-custom-form-desc">Struktur modal ini mengikuti schema form yang kamu pilih, jadi admin melihat layout yang sama seperti form aslinya, bukan grid input generik.</p>' +
                        '</div>' +
                        '<div class="dt-row-custom-form-body">' +
                            '<div class="dt-row-custom-form-grid">' +
                                fields.map(function(field) {
                                    return renderCustomField(field, rowData);
                                }).join('') +
                            '</div>' +
                        '</div>' +
                    '</div>';
                    bindModalChangeDebug(formGrid);
                }

                function renderDefaultEdit(rowData) {
                    formGrid.className = 'dt-row-form-grid dt-row-form-grid-default';
                    if (formNote) {
                        formNote.textContent = 'Default modal menggunakan layout sederhana dan ringkas untuk input yang lebih familiar.';
                    }
                    formGrid.innerHTML = payload.fields.map(function(field) {
                        const fieldName = field.field || field.name || '';
                        const value = inputValue(field, rowData[fieldName]);
                        const wide = field.inputType === 'textarea';
                        let control = '';

                        if (field.inputType === 'boolean' || field.inputType === 'checkbox') {
                            const checked = String(value) === '1' || String(value).toLowerCase() === 'true' ? ' checked' : '';
                            control = '<label class="dt-check form-check form-switch" style="display:flex;align-items:center;gap:8px;"><input type="checkbox" class="form-check-input" data-row-field="' + escapeHtml(fieldName) + '" value="1"' + checked + (field.readonly ? ' disabled' : '') + '> ' + escapeHtml(field.label || 'Aktif / Nonaktif') + '</label>';
                        } else if (field.inputType === 'select') {
                            const options = Array.isArray(field.options) && field.options.length ? field.options : [];
                            const optionHtml = ['<option value="">-- Pilih --</option>'].concat(options.map(function(option) {
                                const selected = String(option.value ?? '') === String(value) ? ' selected' : '';
                                return '<option value="' + escapeHtml(option.value ?? '') + '"' + selected + '>' + escapeHtml(option.label ?? option.value ?? '') + '</option>';
                            })).join('');
                            control = '<select data-row-field="' + escapeHtml(fieldName) + '"' + (field.readonly ? ' disabled' : '') + '>' + optionHtml + '</select>';
                        } else if (field.inputType === 'date') {
                            control = '<input type="date" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'datetime' || field.inputType === 'datetime-local') {
                            control = '<input type="datetime-local" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'number') {
                            control = '<input type="number" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'textarea') {
                            control = '<textarea data-row-field="' + escapeHtml(fieldName) + '" rows="3"' + (field.readonly ? ' readonly' : '') + '>' + escapeHtml(value) + '</textarea>';
                        } else {
                            control = '<input type="text" data-row-field="' + escapeHtml(fieldName) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        }

                        return '<div class="dt-row-field dt-row-field-default' + (wide ? ' wide' : '') + '">' +
                            '<label>' + escapeHtml(field.label) + '</label>' +
                            control +
                        '</div>';
                    }).join('');
                    bindModalChangeDebug(formGrid);
                }

                function openRow(row, mode) {
                    if (!modal || !viewMode || !editFormEl) { return; }
                    const rowData = getRowData(row);
                    const rowDisplayData = getRowDisplayData(row);
                    const rowKey = getRowKey(row);
                    activeRow = row;
                    const editModeLabel = payload.editMode === 'default' ? 'Default modal' : 'Custom form modal';
                    const formName = payload.editForm && payload.editForm.name ? payload.editForm.name : '';
                    modalTitle.textContent = mode === 'edit' ? 'Edit Row' : 'View Row';
                    modalSubtitle.textContent = mode === 'edit'
                        ? 'Ubah data langsung dari modal yang sudah terisi nilai lama. Mode: ' + editModeLabel + (formName ? ' · ' + formName : '')
                        : '';
                    renderSummary(rowKey, rowData, rowDisplayData);
                    renderView(rowData, rowDisplayData);
                    if (mode === 'edit') {
                        if (payload.editMode === 'default') {
                            renderDefaultEdit(rowData);
                        } else {
                            renderCustomEdit(rowData);
                        }
                    }
                    keyInput.value = JSON.stringify(rowKey || {});
                    viewMode.classList.toggle('active', mode === 'view');
                    editFormEl.classList.toggle('active', mode === 'edit');
                    if (modalCard) {
                        modalCard.classList.toggle('view-theme', mode === 'view');
                    }
                    saveButton.style.display = mode === 'edit' ? 'inline-flex' : 'none';
                    if (modalFooter) {
                        modalFooter.classList.remove('is-hidden');
                        modalFooter.classList.toggle('view-footer', mode === 'view');
                        if (footerInfo) {
                            footerInfo.innerHTML = '<i class="ti ti-database" style="font-size:14px;" aria-hidden="true"></i> 1 record';
                        }
                    }
                    const modalBody = root.querySelector('.dt-row-modal-body');
                    if (modalBody) {
                        modalBody.classList.toggle('view-mode', mode === 'view');
                    }
                    openModal();
                }

                syncRowActionBindings();

                function dtConfirm(msg, onConfirm) {
                    var existing = document.getElementById('dt-confirm-overlay');
                    if (existing) existing.remove();
                    var overlay = document.createElement('div');
                    overlay.id = 'dt-confirm-overlay';
                    overlay.style.cssText = 'position:fixed;inset:0;z-index:99998;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;';
                    var box = document.createElement('div');
                    box.style.cssText = 'background:#fff;border-radius:16px;padding:28px 32px;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,0.25);text-align:center;';
                    var p = document.createElement('p');
                    p.style.cssText = 'margin:0 0 22px;font-size:15px;color:#1e293b;font-weight:600;';
                    p.textContent = msg;
                    var wrap = document.createElement('div');
                    wrap.style.cssText = 'display:flex;gap:10px;justify-content:center;';
                    var cancelBtn = document.createElement('button');
                    cancelBtn.textContent = 'Batal';
                    cancelBtn.style.cssText = 'padding:8px 22px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#475569;font-size:13px;font-weight:600;cursor:pointer;';
                    var okBtn = document.createElement('button');
                    okBtn.textContent = 'Yakin';
                    okBtn.style.cssText = 'padding:8px 22px;border:none;border-radius:10px;background:#dc2626;color:#fff;font-size:13px;font-weight:600;cursor:pointer;';
                    function close() { if (overlay.parentNode) overlay.parentNode.removeChild(overlay); }
                    cancelBtn.addEventListener('click', close);
                    okBtn.addEventListener('click', function() { close(); onConfirm(); });
                    overlay.addEventListener('click', function(e) { if (e.target === overlay) close(); });
                    wrap.appendChild(cancelBtn);
                    wrap.appendChild(okBtn);
                    box.appendChild(p);
                    box.appendChild(wrap);
                    overlay.appendChild(box);
                    document.body.appendChild(overlay);
                }

                function dtDeleteRow(row) {
                    var rowKey = getRowKey(row);
                    if (!rowKey || Object.keys(rowKey).length === 0) { dtNotify('Tidak dapat menghapus: data kunci tidak ditemukan.', true); return; }
                    var deleteUrl = root.getAttribute('data-delete-url') || (payload.deleteUrl || '');
                    if (!deleteUrl) { dtNotify('Tidak dapat menghapus: URL hapus tidak tersedia.', true); return; }
                    dtConfirm('Hapus baris ini?', function() {
                        var csrfParam = root.getAttribute('data-csrf-param') || payload.csrfParam || '_csrf';
                        var csrfToken = root.getAttribute('data-csrf-token') || payload.csrfToken || '';
                        var formData = new URLSearchParams();
                        formData.set(csrfParam, csrfToken);
                        formData.set('row_key', JSON.stringify(rowKey));
                        fetch(deleteUrl, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                            credentials: 'same-origin'
                        }).then(function(r) { return r.json(); })
                        .then(function(result) {
                            console.log('[Delete] Response:', result);
                            if (result && result.success) {
                                dtNotify('Data berhasil dihapus.', false);
                                if (reloadUrl) reloadTable(reloadUrl);
                            } else {
                                dtNotify((result && result.message) ? result.message : 'Gagal menghapus data', true);
                            }
                        }).catch(function(err) {
                            console.error('[Delete] Error:', err);
                            dtNotify('Gagal menghapus data. Cek koneksi atau coba lagi.', true);
                        });
                    });
                }

                function dtApproveRow(row) {
                    var rowKey = getRowKey(row);
                    if (!rowKey || Object.keys(rowKey).length === 0) { dtNotify('Tidak dapat memproses: data kunci tidak ditemukan.', true); return; }
                    var approveUrl = root.getAttribute('data-approve-url') || '';
                    if (!approveUrl) { dtNotify('Tidak dapat memproses: URL tidak tersedia.', true); return; }
                    dtConfirm('Proses baris ini?', function() {
                        var csrfParam = root.getAttribute('data-csrf-param') || payload.csrfParam || '_csrf';
                        var csrfToken = root.getAttribute('data-csrf-token') || payload.csrfToken || '';
                        var formData = new URLSearchParams();
                        formData.set(csrfParam, csrfToken);
                        formData.set('row_key', JSON.stringify(rowKey));
                        fetch(approveUrl, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                            credentials: 'same-origin'
                        }).then(function(r) { return r.json(); })
                        .then(function(result) {
                            if (result && result.success) {
                                dtNotify('Data berhasil diproses.', false);
                                if (reloadUrl) reloadTable(reloadUrl);
                            } else {
                                dtNotify((result && result.message) ? result.message : 'Gagal memproses data', true);
                            }
                        }).catch(function() {
                            dtNotify('Gagal memproses data. Cek koneksi atau coba lagi.', true);
                        });
                    });
                }

                root.addEventListener('click', function(event) {
                    var actionButton = event.target && event.target.closest ? event.target.closest('[data-row-action]') : null;
                    if (actionButton && root.contains(actionButton)) {
                        var action = actionButton.getAttribute('data-row-action');
                        var row = actionButton.closest('tr');
                        if (!row) return;
                        if (action === 'delete') {
                            dtDeleteRow(row);
                        } else if (action === 'approve') {
                            dtApproveRow(row);
                        } else {
                            openRow(row, action === 'edit' ? 'edit' : 'view');
                        }
                        return;
                    }

                    const closeButton = event.target && event.target.closest ? event.target.closest('[data-row-modal-close]') : null;
                    if (closeButton && root.contains(closeButton)) {
                        closeModal();
                    }
                });

                root.addEventListener('click', dtHandlePageClick, true);

                if (modal) {
                    modal.addEventListener('click', function(event) {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });
                }

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && modal && modal.classList.contains('open')) {
                        closeModal();
                    }
                });

                window.addEventListener('message', function(event) {
                    const data = event && event.data ? event.data : null;
                    if (!data || data.type !== 'custom-form-submit-success') {
                        return;
                    }
                    if (parseInt(data.targetTableId || 0, 10) !== parseInt(payload.tableId, 10)) {
                        return;
                    }
                    if (upsertRowFromSubmit(data)) {
                        syncRowActionBindings();
                    }
                });

                if (editFormEl) {
                editFormEl.addEventListener('submit', function(event) {
                    event.preventDefault();
                    if (!activeRow) {
                        return;
                    }

                    const values = payload.editMode === 'custom'
                        ? collectValuesFromCustomMarkup(editFormEl)
                        : {};
                    if (payload.editMode !== 'custom') {
                        payload.fields.forEach(function(field) {
                            const fieldName = field.field || field.name || '';
                            const inputs = editFormEl.querySelectorAll('[data-row-field="' + fieldName + '"]');
                            if (!inputs || !inputs.length) {
                                return;
                            }

                            const firstInput = inputs[0];

                            if (field.inputType === 'checkboxes') {
                                values[fieldName] = Array.prototype.slice.call(inputs)
                                    .filter(function(input) { return input.checked; })
                                    .map(function(input) { return input.value; });
                                return;
                            }

                            if (field.inputType === 'radio') {
                                const selectedRadio = Array.prototype.slice.call(inputs).find(function(input) {
                                    return input.checked;
                                });
                                values[fieldName] = selectedRadio ? selectedRadio.value : '';
                                return;
                            }

                            if (field.inputType === 'boolean' || field.inputType === 'checkbox') {
                                values[fieldName] = firstInput.checked ? 1 : 0;
                                return;
                            }

                            values[fieldName] = firstInput.value;
                        });
                    }

                    const request = new FormData();
                    request.append('_csrf', payload.csrfToken || '');
                    request.append('table_id', String(payload.tableId));
                    request.append('operation', 'upsert_row');
                    request.append('row_key', keyInput.value || '{}');
                    request.append('row_data', JSON.stringify(values));

                    console.debug('[MasterDatatable] SAVE', {
                        row_id: activeRow ? (activeRow.getAttribute('data-row-key') || '') : '',
                        row_key: keyInput.value || '{}',
                        raw_row_data: values,
                        changed_fields: values,
                        payload_update: {
                            table_id: String(payload.tableId),
                            operation: 'upsert_row',
                            row_key: keyInput.value || '{}',
                            row_data: values
                        }
                    });

                    saveButton.disabled = true;
                    const previousLabel = saveButton.textContent;
                    saveButton.textContent = 'Menyimpan...';

                    fetch(payload.saveUrl, {
                        method: 'POST',
                        body: request,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(function(response) {
                        return response.json();
                    }).then(function(data) {
                        if (!data || !data.success) {
                            throw new Error((data && data.message) ? data.message : 'Gagal menyimpan data');
                        }
                        const updatedRowData = Object.assign({}, values);
                        const rowKeyData = getRowKey(activeRow) || {};
                        Object.keys(rowKeyData).forEach(function(key) {
                            updatedRowData[key] = rowKeyData[key];
                        });
                        const updatedDisplayData = buildRowDisplayDataFromMessage(data, updatedRowData);
                        activeRow.setAttribute('data-row-values', JSON.stringify(updatedRowData));
                        activeRow.setAttribute('data-row-display-values', JSON.stringify(updatedDisplayData));
                        activeRow.outerHTML = buildRowHtmlFromData(updatedRowData, updatedDisplayData);
                        closeModal();
                        syncRowActionBindings();
                    }).catch(function(error) {
                        dtNotify(error && error.message ? error.message : 'Gagal menyimpan data', true);
                    }).finally(function() {
                        saveButton.disabled = false;
                        saveButton.textContent = previousLabel;
                    });
                });
                }

                // PERBAIKAN BUG 4: Pastikan tombol export kustom memicu navigasi ke URL export riil
                root.querySelectorAll('.dt-export-btn').forEach(function(exportBtn) {
                    exportBtn.addEventListener('click', function(event) {
                        const exportUrl = (exportBtn.getAttribute('data-export-url') || exportBtn.getAttribute('href') || '').trim();
                        if (!exportUrl || exportUrl === '#') {
                            event.preventDefault();
                            console.warn('Export URL belum tersedia untuk datatable ini.');
                            return;
                        }
                        if (exportBtn.getAttribute('target') === '_blank') {
                            return;
                        }
                        event.preventDefault();
                        window.location.href = exportUrl;
                    });
                });

                // === AJAX interceptors: convert form submissions and link
                // clicks to AJAX/fetch calls so Search, Filter, Pagination,
                // and Delete never cause a full page reload.

                var reloadUrl = root.getAttribute('data-reload-url') || '';

                function addQueryParam(url, name, value) {
                    var sep = url.indexOf('?') >= 0 ? '&' : '?';
                    return url + sep + encodeURIComponent(name) + '=' + encodeURIComponent(value);
                }

                function dtUpdatePagination(newSection) {
                    var currentFoot = root.querySelector('.dt-foot');
                    var newFoot = newSection ? newSection.querySelector('.dt-foot') : null;
                    if (currentFoot && newFoot) {
                        currentFoot.innerHTML = newFoot.innerHTML;
                    }
                }

                function reloadTable(url) {
                    if (!url) return;
                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }).then(function(r) {
                        var ct = r.headers.get('Content-Type') || '';
                        if (ct.indexOf('json') !== -1) {
                            return r.json();
                        }
                        return r.text().then(function(html) {
                            var parser = new DOMParser();
                            var doc = parser.parseFromString(html, 'text/html');
                            var currentTbody = root.querySelector('tbody');
                            var newSection = doc.querySelector('.master-datatable');
                            if (newSection) {
                                var newTbody = newSection.querySelector('tbody');
                                if (currentTbody && newTbody) {
                                    currentTbody.innerHTML = newTbody.innerHTML;
                                }
                                var newSubtitle = newSection.querySelector('[data-datatable-subtitle]');
                                if (newSubtitle) {
                                    var currentSubtitle = root.querySelector('[data-datatable-subtitle]');
                                    if (currentSubtitle) {
                                        currentSubtitle.textContent = newSubtitle.textContent;
                                    }
                                }
                                dtUpdatePagination(newSection);
                            }
                            return null;
                        });
                    }).then(function(result) {
                        if (result && result.success) {
                            var tbody = root.querySelector('tbody');
                            if (tbody && result.tbodyHtml) {
                                tbody.innerHTML = result.tbodyHtml;
                            }
                            var subtitle = root.querySelector('[data-datatable-subtitle]');
                            if (subtitle && result.subtitle) {
                                subtitle.textContent = result.subtitle;
                            }
                            if (result.footHtml) {
                                var currentFoot = root.querySelector('.dt-foot');
                                if (currentFoot) {
                                    currentFoot.innerHTML = result.footHtml;
                                }
                            }
                        }
                    }).catch(function() {});
                }

                var dtState = {};
                function dtGetBaseUrl() {
                    if (reloadUrl) return reloadUrl;
                    var form = root.querySelector('.dt-search-form');
                    return form ? (form.getAttribute('action') || '') : '';
                }
                function dtBuildReloadUrl(extraParams) {
                    var base = dtGetBaseUrl();
                    if (!base) return '';
                    Object.keys(extraParams).forEach(function(k) {
                        dtState[k] = extraParams[k];
                    });
                    Object.keys(dtState).forEach(function(k) {
                        base = addQueryParam(base, k, dtState[k]);
                    });
                    return base;
                }

                // Intercept search form submission via delegation (capture phase to beat dynamic runtime)
                // Component Builder (reloadUrl set) → AJAX; Custom Code → let native form submit handle it
                root.addEventListener('submit', function(e) {
                    var form = e.target && e.target.closest ? e.target.closest('.dt-search-form') : null;
                    if (!form || !root.contains(form)) return;
                    if (!reloadUrl) return; // native full-page navigation for Custom Code
                    e.preventDefault();
                    e.stopPropagation();
                    var searchInput = form.querySelector('input[type="search"]');
                    if (!searchInput) return;
                    var param = searchInput.getAttribute('name') || 'search';
                    var extra = {};
                    extra[param] = searchInput.value;
                    var url = dtBuildReloadUrl(extra);
                    if (url) reloadTable(url);
                }, true);

                // Intercept filter select changes via delegation (capture phase)
                root.addEventListener('change', function(e) {
                    var sel = e.target && e.target.closest ? e.target.closest('.dt-filters select') : null;
                    if (!sel || !root.contains(sel)) return;
                    if (!reloadUrl) {
                        // Custom Code: navigate top window (form.submit() ignores target="_top" inside iframe)
                        e.stopPropagation();
                        var url = window.top ? window.top.location.href : window.location.href;
                        root.querySelectorAll('.dt-filters select').forEach(function(s) {
                            var name = s.getAttribute('name') || '';
                            if (name) url = addQueryParam(url, name, s.value);
                        });
                        (window.top || window).location.href = url;
                        return;
                    }
                    e.stopPropagation();
                    var extra = {};
                    root.querySelectorAll('.dt-filters select').forEach(function(s) {
                        var name = s.getAttribute('name') || '';
                        if (name) extra[name] = s.value;
                    });
                    var url = dtBuildReloadUrl(extra);
                    if (url) reloadTable(url);
                }, true);

                // Intercept pagination link clicks via delegation (survives DOM replacement)
                // Component Builder → AJAX; Custom Code → let native link navigation handle it
                function dtHandlePageClick(e) {
                    var link = e.target && e.target.closest ? e.target.closest('.dt-page a') : null;
                    if (!link || !root.contains(link)) return;
                    if (!reloadUrl) return; // native full-page navigation for Custom Code
                    e.preventDefault();
                    e.stopPropagation();
                    var href = link.getAttribute('href') || '';
                    var pageMatch = href.match(/[?&](dt_page_\d+)=(\d+)/);
                    if (pageMatch) {
                        var extra = {};
                        extra[pageMatch[1]] = pageMatch[2];
                        var url = dtBuildReloadUrl(extra);
                        if (url) reloadTable(url);
                    }
                }

                function dtNotify(message, isError) {
                    var existing = document.getElementById('dt-notify');
                    if (existing) existing.remove();
                    var el = document.createElement('div');
                    el.id = 'dt-notify';
                    el.textContent = message;
                    el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,0.18);cursor:pointer;max-width:400px;' +
                        (isError
                            ? 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;'
                            : 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;');
                    el.addEventListener('click', function() { el.remove(); });
                    setTimeout(function() { if (el.parentNode) el.remove(); }, 6000);
                    document.body.appendChild(el);
                }

            })();
        </script>
        </section>
        <?php
        return (string)ob_get_clean();
    }

    private function renderRowsHtml(DbTable $table, array $columns, array $rows, array $actions, array $primaryKeys, array $displayLookup, int $colspan, int $presetId = 0, array $workflow = [], array $displayValues = []): string
    {
        $hasWorkflowAction = !empty($workflow['approval_enabled']) && $presetId > 0;
        $hasActions = (in_array(true, $actions, true) || $hasWorkflowAction) && !empty($primaryKeys);
        ob_start();
        ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="<?= (int)$colspan ?>"><div class="dt-empty"><strong>No data available</strong>This table does not have any data yet.</div></td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php $rowKey = $this->buildRowKeyFromRow($row, $primaryKeys); ?>
                <?php $rowDisplayValues = $this->buildRowDisplayValues($row, $columns, $displayLookup); ?>
                <tr data-row-key="<?= Html::encode(Json::encode($rowKey)) ?>" data-row-values="<?= Html::encode(Json::encode($row)) ?>" data-row-display-values="<?= Html::encode(Json::encode($rowDisplayValues)) ?>">
                    <?php foreach ($columns as $column): ?>
                        <td><?= $this->renderCellValue($row, $column, $displayLookup) ?></td>
                    <?php endforeach; ?>
                    <?php if ($hasActions): ?>
                        <td>
                            <div class="dt-actions">
                                <?php if ($actions['view']): ?>
                                    <button type="button" class="dt-btn" data-row-action="view">View</button>
                                <?php endif; ?>
                                <?php if ($actions['edit']): ?>
                                    <button type="button" class="dt-btn" data-row-action="edit">Edit</button>
                                <?php endif; ?>
                                <?php if ($actions['delete']): ?>
                                    <button class="dt-btn dt-btn-danger" type="button" data-row-action="delete">Delete</button>
                                <?php endif; ?>
                                <?php if ($hasWorkflowAction && (string)($row[$workflow['status_field']] ?? '') !== (string)$workflow['approved_value']): ?>
                                    <button class="dt-btn" type="button" data-row-action="approve"><?= Html::encode((string)$workflow['button_label']) ?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
        return trim((string)ob_get_clean());
    }

    private function renderNotice(string $title, string $message): string
    {
        return '<div style="margin:24px 0;padding:28px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;text-align:center;color:#64748b;"><strong style="display:block;color:#0f172a;font-size:16px;margin-bottom:4px;">'
            . Html::encode($title)
            . '</strong>'
            . Html::encode($message)
            . '</div>';
    }

    private function renderPrintableExport(array $data, string $title): string
    {
        ob_start();
        ?>
        <!doctype html>
        <html lang="id">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= Html::encode($title) ?></title>
            <style>
                body { margin: 32px; font-family: Arial, sans-serif; color: #0f172a; }
                h1 { margin: 0 0 6px; font-size: 24px; }
                p { margin: 0 0 20px; color: #64748b; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #dbe3ef; padding: 8px 10px; font-size: 12px; text-align: left; vertical-align: top; }
                th { background: #f8fafc; text-transform: uppercase; letter-spacing: .04em; }
                .print-actions { margin-bottom: 18px; }
                .print-actions button { border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
                @media print { .print-actions { display: none; } body { margin: 12mm; } }
            </style>
        </head>
        <body>
            <div class="print-actions"><button type="button" onclick="window.print()">Print / Save PDF</button></div>
            <h1><?= Html::encode($title) ?></h1>
            <p><?= (int)($data['state']['total'] ?? count($data['rows'] ?? [])) ?> data</p>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($data['columns'] as $column): ?>
                            <th><?= Html::encode((string)($column['label'] ?? $column['field'] ?? '')) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['rows'] as $printIndex => $row): ?>
                        <tr>
                            <?php foreach ($data['columns'] as $column): ?>
                                <td><?= Html::encode(isset($data['displayValues'][$printIndex][(string)($column['field'] ?? '')]) ? (string)$data['displayValues'][$printIndex][(string)($column['field'] ?? '')] : '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * PERBAIKAN BUG 1: Excel XML Spreadsheet 2003 (Sangat kompatibel dengan Excel tanpa library PHP eksternal)
     */
    private function renderExcelXmlExport(array $data, string $title): string
    {
        $columns = $data['columns'];
        $rows = $data['rows'];
        $lookup = $data['displayLookup'];

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
        $xml .= 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
        $xml .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $xml .= '  <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">';
        $xml .= '    <Title>' . htmlspecialchars($title) . '</Title>';
        $xml .= '    <Created>' . date('Y-m-d\TH:i:s\Z') . '</Created>';
        $xml .= '  </DocumentProperties>' . "\n";
        
        $xml .= '  <Styles>';
        $xml .= '    <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Borders/><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/><Interior/><NumberFormat/><Protection/></Style>';
        $xml .= '    <Style ss:ID="Header"><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/><Interior ss:Color="#4F46E5" ss:Pattern="Solid"/></Style>';
        $xml .= '  </Styles>' . "\n";
        
        $xml .= '  <Worksheet ss:Name="Sheet1">' . "\n";
        $xml .= '    <Table>' . "\n";
        
        // Header
        $xml .= '      <Row ss:Height="20">' . "\n";
        foreach ($columns as $column) {
            $label = (string)($column['label'] ?? $column['field'] ?? '');
            $xml .= '        <Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($label) . '</Data></Cell>' . "\n";
        }
        $xml .= '      </Row>' . "\n";
        
        // Data Rows
        foreach ($rows as $excelIndex => $row) {
            $xml .= '      <Row>' . "\n";
            foreach ($columns as $column) {
                $field = (string)($column['field'] ?? '');
                $val = isset($data['displayValues'][$excelIndex][$field]) ? (string)$data['displayValues'][$excelIndex][$field] : '';
                $type = is_numeric($val) && (strlen($val) < 11 || $val[0] !== '0') ? 'Number' : 'String';
                $xml .= '        <Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($val) . '</Data></Cell>' . "\n";
            }
            $xml .= '      </Row>' . "\n";
        }
        
        $xml .= '    </Table>' . "\n";
        $xml .= '  </Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }

    private function formatValue($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }
        return (string)$value;
    }

    private function formatDisplayValue(array $row, array $column, array $displayLookup): string
    {
        $field = (string)($column['field'] ?? '');
        $rawValue = $field !== '' && array_key_exists($field, $row) ? $row[$field] : null;
        // Always check displayLookup first, regardless of fk_display_mode
        // This ensures export (CSV/Excel/PDF/Print) uses same resolved values as datatable
        $lookupKey = $rawValue === null ? '' : (string)$rawValue;
        if ($field !== '' && $lookupKey !== '' && array_key_exists($lookupKey, $displayLookup[$field] ?? [])) {
            $displayValue = $displayLookup[$field][$lookupKey];
            if ($displayValue !== null && $displayValue !== '') {
                return $this->formatValue($displayValue);
            }
        }

        return $this->formatValue($rawValue);
    }

    private function renderCellValue(array $row, array $column, array $displayLookup): string
    {
        $field = (string)($column['field'] ?? '');
        $rawValue = $field !== '' && array_key_exists($field, $row) ? $row[$field] : null;
        $displayMode = (string)($column['display_mode'] ?? 'text');

        if ($displayMode === 'image') {
            $url = $rawValue !== null && $rawValue !== '' ? (string)$rawValue : '';
            if ($url === '') {
                return '-';
            }
            $url = $this->normalizeAssetUrl($url);
            $escapedUrl = Html::encode($url);
            return '<img src="' . $escapedUrl . '" alt="" style="max-width:120px;max-height:80px;border-radius:6px;object-fit:cover;background:#f1f5f9;" loading="lazy">';
        }

        if ($displayMode === 'file') {
            $url = $rawValue !== null && $rawValue !== '' ? (string)$rawValue : '';
            if ($url === '') {
                return '-';
            }
            $url = $this->normalizeAssetUrl($url);
            $fileName = basename($url);
            $escapedUrl = Html::encode($url);
            $escapedName = Html::encode($fileName);
            return '<a href="' . $escapedUrl . '" target="_blank" rel="noopener" class="dt-file-link" style="display:inline-flex;align-items:center;gap:4px;color:#2563eb;text-decoration:none;">'
                . '<span style="font-size:14px;">&#128206;</span> ' . $escapedName . '</a>';
        }

        if ($displayMode === 'link') {
            $url = $rawValue !== null && $rawValue !== '' ? (string)$rawValue : '';
            if ($url === '') {
                return '-';
            }
            $url = $this->normalizeAssetUrl($url);
            $displayText = (string)($column['link_text'] ?? $url);
            $displayText = $displayText === $url ? $url : $displayText;
            $escapedUrl = Html::encode($url);
            $escapedText = Html::encode($displayText);
            return '<a href="' . $escapedUrl . '" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:none;">' . $escapedText . '</a>';
        }

        if ($displayMode === 'badge') {
            $value = $rawValue !== null && $rawValue !== '' ? (string)$rawValue : '';
            if ($value === '') {
                return '-';
            }
            $badgeColor = (string)($column['badge_color'] ?? '#3b82f6');
            $escapedValue = Html::encode($value);
            return '<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;color:#fff;background:' . Html::encode($badgeColor) . ';">' . $escapedValue . '</span>';
        }

        $displayValue = $this->formatDisplayValue($row, $column, $displayLookup);
        return Html::encode($displayValue);
    }

        private function buildAllDisplayValues(array $columns, array $rows, array $displayLookup): array
    {
        $allValues = [];
        foreach ($rows as $index => $row) {
            $allValues[$index] = [];
            foreach ($columns as $column) {
                $field = (string)($column['field'] ?? '');
                if ($field === '') {
                    continue;
                }
                $allValues[$index][$field] = $this->formatDisplayValue($row, $column, $displayLookup);
            }
        }
        return $allValues;
    }

    private function buildRowDisplayValues(array $row, array $columns, array $displayLookup): array
    {
        $values = [];
        foreach ($columns as $column) {
            $field = (string)($column['field'] ?? '');
            if ($field === '') {
                continue;
            }
            $values[$field] = $this->formatDisplayValue($row, $column, $displayLookup);
        }

        return $values;
    }

    private function resolveDetailFields(array $columns): array
    {
        $fields = [];
        foreach ($columns as $column) {
            $fieldName = trim((string)($column['field'] ?? ''));
            if ($fieldName === '') {
                continue;
            }

            $isForeignKey = !empty($column['referenced_table']) || !empty($column['referenced_column']) || !empty($column['fk_display_mode']);
            $fields[] = [
                'field' => $fieldName,
                'name' => $fieldName,
                'field_name' => $fieldName,
                'field_key' => $fieldName,
                'column_name' => $fieldName,
                'label' => (string)($column['label'] ?? $fieldName),
                'inputType' => 'text',
                'componentType' => $isForeignKey ? 'foreign_key' : 'field',
                'is_foreign_key' => $isForeignKey,
                'fk_display_mode' => (string)($column['fk_display_mode'] ?? 'raw_id'),
                'related_display_column' => (string)($column['related_display_column'] ?? ''),
                'display_mode' => (string)($column['display_mode'] ?? 'text'),
                'link_text' => (string)($column['link_text'] ?? ''),
                'badge_color' => (string)($column['badge_color'] ?? '#3b82f6'),
            ];
        }

        return $fields;
    }

    private function buildRowKeyFromRow(array $row, array $primaryKeys): array
    {
        $key = [];
        foreach ($primaryKeys as $primaryKey) {
            if (array_key_exists($primaryKey, $row)) {
                $key[$primaryKey] = $row[$primaryKey];
            }
        }

        return $key;
    }

    private function resolveRowFields(DbTable $table, array $columns): array
    {
        $schema = Yii::$app->db->schema->getTableSchema($table->name, true);
        $metadataColumns = $table->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        $metadataMap = [];
        foreach ($metadataColumns as $metadataColumn) {
            $metadataMap[$metadataColumn->name] = $metadataColumn;
        }

        $fields = [];
        foreach ($columns as $column) {
            $fieldName = trim((string)($column['resolved_name'] ?? $column['resolved_column_name'] ?? $column['field'] ?? $column['name'] ?? $column['field_name'] ?? $column['field_key'] ?? $column['column_name'] ?? ''));
            if ($fieldName === '' || !isset($metadataMap[$fieldName])) {
                continue;
            }

            $metadataColumn = $metadataMap[$fieldName];
            $schemaColumn = $schema !== null && isset($schema->columns[$fieldName]) ? $schema->columns[$fieldName] : null;
            if (SystemFieldService::shouldHideFromForm($metadataColumn, $schemaColumn)) {
                continue;
            }

            $fields[] = [
                'field' => $fieldName,
                'field_name' => $fieldName,
                'field_key' => $fieldName,
                'column_name' => $fieldName,
                'label' => (string)($column['resolved_label'] ?? $column['label'] ?? $metadataColumn->label ?? $fieldName),
                'inputType' => $this->inferInputType($metadataColumn, $schemaColumn),
                'options' => $this->inferFieldOptions($metadataColumn, $schemaColumn),
                'componentType' => SystemFieldService::isForeignKey($metadataColumn, $schemaColumn) ? 'foreign_key' : 'field',
                'is_foreign_key' => SystemFieldService::isForeignKey($metadataColumn, $schemaColumn),
                'fk_display_mode' => (string)($column['fk_display_mode'] ?? 'raw_id'),
                'related_display_column' => (string)($column['related_display_column'] ?? ''),
                'display_mode' => (string)($column['display_mode'] ?? 'text'),
                'link_text' => (string)($column['link_text'] ?? ''),
                'badge_color' => (string)($column['badge_color'] ?? '#3b82f6'),
                'sourceColumn' => $fieldName,
                'readonly' => SystemFieldService::shouldBeReadonlyInGrid($metadataColumn, $schemaColumn),
            ];
        }

        return $fields;
    }

    private function inferInputType(DbTableColumn $metadataColumn, $schemaColumn = null): string
    {
        $type = strtoupper((string)($schemaColumn->type ?? $metadataColumn->type ?? 'TEXT'));
        $length = (int)($schemaColumn->size ?? $metadataColumn->length ?? 0);

        if (SystemFieldService::isForeignKey($metadataColumn, $schemaColumn)) {
            return 'select';
        }

        if (in_array($type, ['BOOLEAN', 'BOOL', 'BIT', 'TINYINT'], true) && ($length <= 1 || in_array($type, ['BOOLEAN', 'BOOL'], true))) {
            return 'boolean';
        }

        if (in_array($type, ['DATE'], true)) {
            return 'date';
        }

        if (in_array($type, ['DATETIME', 'TIMESTAMP'], true)) {
            return 'datetime';
        }

        if (in_array($type, ['INT', 'BIGINT', 'SMALLINT', 'MEDIUMINT', 'DECIMAL', 'FLOAT', 'DOUBLE', 'REAL', 'SERIAL'], true)) {
            return 'number';
        }

        if (in_array($type, ['JSON'], true)) {
            return 'textarea';
        }

        if ($length >= 255 || in_array($type, ['TEXT', 'MEDIUMTEXT', 'LONGTEXT'], true)) {
            return 'textarea';
        }

        return 'text';
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function inferFieldOptions(DbTableColumn $metadataColumn, $schemaColumn = null): array
    {
        if (!SystemFieldService::isForeignKey($metadataColumn, $schemaColumn)) {
            return [];
        }

        $referencedTable = strtolower(trim((string)($metadataColumn->hasAttribute('referenced_table_name') ? $metadataColumn->getAttribute('referenced_table_name') : '')));
        $referencedColumn = strtolower(trim((string)($metadataColumn->hasAttribute('referenced_column_name') ? $metadataColumn->getAttribute('referenced_column_name') : '')));
        if ($referencedTable === '') {
            return [];
        }

        $db = Yii::$app->db;
        $schema = $db->schema->getTableSchema($referencedTable, true);
        if ($schema === null) {
            return [];
        }

        $valueColumn = $referencedColumn !== '' && isset($schema->columns[$referencedColumn])
            ? $referencedColumn
            : (!empty($schema->primaryKey) ? (string)$schema->primaryKey[0] : (string)array_key_first($schema->columns));
        if ($valueColumn === '' || !isset($schema->columns[$valueColumn])) {
            return [];
        }

        $labelColumn = $this->guessForeignKeyLabelColumn($schema, $valueColumn);
        $rows = (new Query())
            ->from($referencedTable)
            ->select(array_values(array_unique(array_filter([$valueColumn, $labelColumn]))))
            ->limit(500)
            ->all($db);

        $options = [];
        foreach ($rows as $row) {
            $value = $row[$valueColumn] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $label = $labelColumn !== '' ? ($row[$labelColumn] ?? $value) : $value;
            $options[] = [
                'value' => (string)$value,
                'label' => trim((string)$label) !== '' ? (string)$label : (string)$value,
            ];
        }

        return $options;
    }

    private function guessForeignKeyLabelColumn(\yii\db\TableSchema $schema, string $valueColumn): string
    {
        foreach (['name', 'title', 'label', 'slug', 'username', 'email', 'form_name', 'table_name', 'nama', 'nama_lengkap', 'judul', 'deskripsi', 'keterangan'] as $candidate) {
            if ($candidate !== $valueColumn && isset($schema->columns[$candidate])) {
                return $candidate;
            }
        }

        return $valueColumn;
    }

    private function normalizeFormFieldOptions(array $field): array
    {
        $raw = $field['options'] ?? $field['field_options'] ?? $field['dropdown_options'] ?? [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }

        if (!is_array($raw)) {
            return [];
        }

        $options = [];
        foreach ($raw as $option) {
            if (is_array($option)) {
                $value = $option['value'] ?? $option['id'] ?? $option['key'] ?? '';
                $label = $option['label'] ?? $option['name'] ?? $value;
                if ($value === '' && $label === '') {
                    continue;
                }
                $options[] = [
                    'value' => (string)$value,
                    'label' => (string)$label,
                ];
                continue;
            }

            if (is_scalar($option)) {
                $options[] = [
                    'value' => (string)$option,
                    'label' => (string)$option,
                ];
            }
        }

        return $options;
    }

    private const ASSET_BASE_URL = '/uploads/workspace/';

    private function normalizeAssetUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }
        if (preg_match('#^(https?://|//|/)#i', $url)) {
            return $url;
        }
        return self::ASSET_BASE_URL . ltrim($url);
    }

    private function pageUrl(string $pageParam, int $page): string
    {
        $params = [];
        foreach (Yii::$app->request->get() as $key => $value) {
            if (strpos((string)$key, 'dt_') === 0) {
                $params[$key] = $value;
            }
        }
        $params[$pageParam] = $page;
        return Url::current($params);
    }
}