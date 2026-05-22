<?php

namespace app\services;

use app\components\ActiveProjectContext;
use app\components\ProjectPermissionService;
use app\components\ProjectSchema;
use app\components\SystemFieldService;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterDatatable;
use Yii;
use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

class MasterDatatableRenderService
{
    public function renderByPresetId(int $presetId, array $options = []): string
    {
        $preset = MasterDatatable::findScoped()->andWhere(['id' => $presetId, 'is_active' => 1])->one();
        if (!$preset instanceof MasterDatatable) {
            return $this->renderNotice('Datatable tidak tersedia.', 'Preset datatable tidak ditemukan atau sedang nonaktif.');
        }

        return $this->renderFromConfig($preset->toComponentConfig(), $options);
    }

    public function renderFromConfig(array $config, array $options = []): string
    {
        $presetId = (int)($config['datatableId'] ?? $config['datatable_id'] ?? 0);
        if ($presetId > 0) {
            $preset = MasterDatatable::findScoped()->andWhere(['id' => $presetId, 'is_active' => 1])->one();
            if ($preset instanceof MasterDatatable) {
                $config = array_replace_recursive($preset->toComponentConfig(), $config);
            }
        }

        $tableId = (int)($config['tableId'] ?? $config['table_id'] ?? 0);
        if ($tableId <= 0) {
            return $this->renderNotice('No data available', 'Pilih source table untuk menampilkan datatable.');
        }

        $table = DbTable::find()->where(['id' => $tableId])->one();
        if (!$table instanceof DbTable || !$this->canUseTable($table)) {
            return $this->renderNotice('No data available', 'Source table tidak ditemukan atau tidak dapat diakses.');
        }

        $tableSchema = Yii::$app->db->schema->getTableSchema($table->name, true);
        if ($tableSchema === null) {
            return $this->renderNotice('No data available', 'Physical SQL table belum dibuat.');
        }

        $columns = $this->resolveColumns($table, $config);
        if (empty($columns)) {
            return $this->renderNotice('No data available', 'Belum ada kolom yang dipilih untuk datatable ini.');
        }

        $searchEnabled = (bool)($config['search'] ?? $config['search_enabled'] ?? true);
        $paginationEnabled = (bool)($config['pagination'] ?? $config['pagination_enabled'] ?? true);
        $pageSize = max(1, min(100, (int)($config['pageSize'] ?? $config['page_size'] ?? 10)));
        $pageParam = 'dt_page_' . $tableId;
        $searchParam = 'dt_search_' . $tableId;
        $page = max(1, (int)Yii::$app->request->get($pageParam, 1));
        $search = trim((string)Yii::$app->request->get($searchParam, ''));
        $fields = array_column($columns, 'field');

        $query = (new Query())->from($table->name);
        if ($searchEnabled && $search !== '') {
            $or = ['or'];
            foreach ($fields as $field) {
                $or[] = ['like', $field, $search];
            }
            if (count($or) > 1) {
                $query->andWhere($or);
            }
        }

        $total = (int)(clone $query)->count('*', Yii::$app->db);
        if ($paginationEnabled) {
            $query->limit($pageSize)->offset(($page - 1) * $pageSize);
        } else {
            $query->limit(500);
        }

        $rows = $query->all(Yii::$app->db);
        $actions = $this->resolveActions($config);
        $editMode = $this->resolveEditMode($config);
        $primaryKeys = !empty($tableSchema->primaryKey) ? array_values($tableSchema->primaryKey) : [];
        $uid = 'dt-' . $tableId . '-' . substr(md5(json_encode($config)), 0, 8);

        return $this->renderTable($uid, $table, $columns, $rows, $actions, $editMode, $primaryKeys, [
            'searchEnabled' => $searchEnabled,
            'paginationEnabled' => $paginationEnabled,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'search' => $search,
            'pageParam' => $pageParam,
            'searchParam' => $searchParam,
        ]);
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
            ];
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
            ];
        }

        return array_slice($columns, 0, 6);
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
        $mode = strtolower(trim((string)($requested['edit_mode'] ?? 'custom')));
        return in_array($mode, ['default', 'custom'], true) ? $mode : 'custom';
    }

    private function canUseTable(DbTable $table): bool
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        return $activeProjectId === null || !$table->hasAttribute('project_id') || (int)$table->project_id === (int)$activeProjectId;
    }

    private function renderTable(string $uid, DbTable $table, array $columns, array $rows, array $actions, string $editMode, array $primaryKeys, array $state): string
    {
        $hasActions = in_array(true, $actions, true) && !empty($primaryKeys);
        $colspan = count($columns) + ($hasActions ? 1 : 0);
        $totalPages = max(1, (int)ceil(($state['total'] ?: 0) / $state['pageSize']));
        $rowFields = $this->resolveRowFields($table, $columns);

        ob_start();
        ?>
        <section class="master-datatable" id="<?= Html::encode($uid) ?>">
            <style>
                #<?= Html::encode($uid) ?> { margin: 24px 0; border: 1px solid #e2e8f0; border-radius: 18px; background: #fff; overflow: hidden; box-shadow: 0 16px 36px rgba(15,23,42,.08); }
                #<?= Html::encode($uid) ?> .dt-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); }
                #<?= Html::encode($uid) ?> .dt-title { margin:0; font-size:17px; font-weight:800; color:#0f172a; }
                #<?= Html::encode($uid) ?> .dt-subtitle { margin:4px 0 0; font-size:12px; color:#64748b; }
                #<?= Html::encode($uid) ?> .dt-search { min-width:260px; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:13px; color:#0f172a; }
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
                #<?= Html::encode($uid) ?> .dt-row-modal-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; padding:20px 22px; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); }
                #<?= Html::encode($uid) ?> .dt-row-modal-title { margin:0; color:#0f172a; font-size:18px; font-weight:800; }
                #<?= Html::encode($uid) ?> .dt-row-modal-subtitle { margin:4px 0 0; color:#64748b; font-size:13px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; }
                #<?= Html::encode($uid) ?> .dt-row-modal-close { border:1px solid #dbe3ef; border-radius:12px; background:#fff; color:#334155; padding:8px 12px; font-weight:700; cursor:pointer; }
                #<?= Html::encode($uid) ?> .dt-row-modal-body { padding:22px; overflow:auto; background:linear-gradient(180deg,#fbfdff 0%,#fff 18%); }
                #<?= Html::encode($uid) ?> .dt-row-view-shell { display:grid; grid-template-columns:minmax(220px, 0.85fr) minmax(0, 1.6fr); gap:16px; }
                #<?= Html::encode($uid) ?> .dt-row-view-aside { display:grid; gap:12px; align-content:start; }
                #<?= Html::encode($uid) ?> .dt-row-view-panel { border:1px solid #e2e8f0; border-radius:18px; background:#fff; padding:16px 18px; box-shadow:0 10px 24px rgba(15,23,42,.04); }
                #<?= Html::encode($uid) ?> .dt-row-view-panel h5 { margin:0 0 10px; color:#0f172a; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
                #<?= Html::encode($uid) ?> .dt-row-view-panel p { margin:0; color:#475569; font-size:13px; line-height:1.6; }
                #<?= Html::encode($uid) ?> .dt-row-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
                #<?= Html::encode($uid) ?> .dt-summary-card { border:1px solid #e2e8f0; border-radius:16px; background:linear-gradient(180deg,#f8fafc 0%,#fff 100%); padding:14px 16px; box-shadow:0 8px 20px rgba(15,23,42,.04); }
                #<?= Html::encode($uid) ?> .dt-summary-label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
                #<?= Html::encode($uid) ?> .dt-summary-value { color:#0f172a; font-size:14px; font-weight:700; word-break:break-word; }
                #<?= Html::encode($uid) ?> .dt-row-view-main { display:grid; gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-view-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-view-item { border:1px solid #e2e8f0; border-radius:16px; background:#fff; padding:14px 16px; box-shadow:0 8px 20px rgba(15,23,42,.03); }
                #<?= Html::encode($uid) ?> .dt-row-view-label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
                #<?= Html::encode($uid) ?> .dt-row-view-value { color:#0f172a; font-size:14px; line-height:1.6; word-break:break-word; }
                #<?= Html::encode($uid) ?> .dt-row-form-note { margin:0 0 16px; color:#64748b; font-size:13px; line-height:1.6; }
                #<?= Html::encode($uid) ?> .dt-row-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-form-grid-default { grid-template-columns:1fr; gap:12px; }
                #<?= Html::encode($uid) ?> .dt-row-form-grid-custom { grid-template-columns:repeat(2,minmax(0,1fr)); }
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
                #<?= Html::encode($uid) ?> .dt-row-modal-footer .dt-btn { min-width:120px; }
                #<?= Html::encode($uid) ?> .dt-row-mode { display:none; }
                #<?= Html::encode($uid) ?> .dt-row-mode.active { display:block; }
                @media (max-width: 768px) {
                    #<?= Html::encode($uid) ?> .dt-row-view-shell,
                    #<?= Html::encode($uid) ?> .dt-row-summary,
                    #<?= Html::encode($uid) ?> .dt-row-view-grid,
                    #<?= Html::encode($uid) ?> .dt-row-form-grid { grid-template-columns:1fr; }
                    #<?= Html::encode($uid) ?> .dt-row-modal { padding:12px; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-body { padding:16px; }
                    #<?= Html::encode($uid) ?> .dt-row-modal-head,
                    #<?= Html::encode($uid) ?> .dt-row-modal-footer { padding:16px; }
                }
            </style>
            <div class="dt-head">
                <div>
                    <h3 class="dt-title"><?= Html::encode($table->label ?: $table->name) ?></h3>
                    <p class="dt-subtitle"><?= (int)$state['total'] ?> row<?= (int)$state['total'] === 1 ? '' : 's' ?> from <?= Html::encode($table->name) ?></p>
                </div>
                <?php if ($state['searchEnabled']): ?>
                    <form method="get">
                        <?php foreach (Yii::$app->request->get() as $key => $value): ?>
                            <?php if ($key !== $state['searchParam'] && $key !== $state['pageParam']): ?>
                                <input type="hidden" name="<?= Html::encode($key) ?>" value="<?= Html::encode((string)$value) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input class="dt-search" type="search" name="<?= Html::encode($state['searchParam']) ?>" value="<?= Html::encode($state['search']) ?>" placeholder="Search...">
                    </form>
                <?php endif; ?>
            </div>
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
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="<?= (int)$colspan ?>"><div class="dt-empty"><strong>No data available</strong>This table does not have any data yet.</div></td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php $rowKey = $this->buildRowKeyFromRow($row, $primaryKeys); ?>
                            <tr data-row-key="<?= Html::encode(Json::encode($rowKey)) ?>" data-row-values="<?= Html::encode(Json::encode($row)) ?>">
                                <?php foreach ($columns as $column): ?>
                                    <td><?= Html::encode($this->formatValue($row[$column['field']] ?? null)) ?></td>
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
                                                <form method="post" action="<?= Html::encode(Url::to(['/master-datatable/delete-row', 'table_id' => $table->id])) ?>" onsubmit="return confirm('Delete this row?');">
                                                    <input type="hidden" name="<?= Html::encode(Yii::$app->request->csrfParam) ?>" value="<?= Html::encode(Yii::$app->request->csrfToken) ?>">
                                                    <input type="hidden" name="row_key" value="<?= Html::encode(Json::encode($rowKey)) ?>">
                                                    <button class="dt-btn dt-btn-danger" type="submit">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($state['paginationEnabled']): ?>
                <div class="dt-foot">
                    <span>Page <?= (int)$state['page'] ?> of <?= (int)$totalPages ?></span>
                    <div class="dt-page">
                        <?php if ($state['page'] > 1): ?>
                            <a href="<?= Html::encode($this->pageUrl($state['pageParam'], $state['page'] - 1)) ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($state['page'] < $totalPages): ?>
                            <a href="<?= Html::encode($this->pageUrl($state['pageParam'], $state['page'] + 1)) ?>">Next</a>
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
                                    <div class="dt-row-view-panel">
                                        <h5>Reference</h5>
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
                    <div class="dt-row-modal-footer">
                        <button type="button" class="dt-btn" data-row-modal-close>Batal</button>
                        <button type="submit" class="dt-btn" data-row-save-btn form="<?= Html::encode($uid) ?>-row-form">Simpan Perubahan</button>
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
                    'fields' => $rowFields,
                ]) ?>;
                const modal = root.querySelector('[data-row-modal]');
                const viewMode = root.querySelector('[data-row-view-mode]');
                const editForm = root.querySelector('[data-row-edit-mode]');
                const summary = root.querySelector('[data-row-summary]');
                const viewGrid = root.querySelector('[data-row-view-grid]');
                const formGrid = root.querySelector('[data-row-form-grid]');
                const keyInput = root.querySelector('[data-row-key-input]');
                const saveButton = root.querySelector('[data-row-save-btn]');
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

                function getRowKey(row) {
                    try {
                        return JSON.parse(row.getAttribute('data-row-key') || '{}') || {};
                    } catch (error) {
                        return {};
                    }
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
                }

                function formatViewValue(field, value) {
                    if (value === null || value === undefined || value === '') {
                        return '<span class="dt-row-muted">-</span>';
                    }

                    if (field.inputType === 'boolean') {
                        const active = String(value) === '1' || String(value).toLowerCase() === 'true';
                        return '<span class="dt-btn" style="display:inline-flex;align-items:center;gap:6px;">' + (active ? 'Aktif' : 'Nonaktif') + '</span>';
                    }

                    if (typeof value === 'object') {
                        return '<pre style="margin:0;white-space:pre-wrap;word-break:break-word;">' + escapeHtml(JSON.stringify(value, null, 2)) + '</pre>';
                    }

                    return escapeHtml(value);
                }

                function inputValue(field, value) {
                    if (value === null || value === undefined) {
                        return '';
                    }
                    if (field.inputType === 'datetime') {
                        return String(value).slice(0, 16).replace(' ', 'T');
                    }
                    return String(value);
                }

                function renderSummary(rowKey) {
                    const items = Object.keys(rowKey || {});
                    if (!items.length) {
                        summary.innerHTML = '<div class="dt-summary-card"><span class="dt-summary-label">Row</span><div class="dt-summary-value">Primary key tidak tersedia</div></div>';
                        if (rowReference) {
                            rowReference.textContent = 'Baris ini tidak memiliki primary key yang terbaca.';
                        }
                        return;
                    }
                    if (rowReference) {
                        rowReference.textContent = 'Primary key row ini memudahkan admin meninjau, mengedit, atau menelusuri data dengan cepat.';
                    }
                    summary.innerHTML = items.map(function(key) {
                        return '<div class="dt-summary-card"><span class="dt-summary-label">' + escapeHtml(key) + '</span><div class="dt-summary-value">' + escapeHtml(rowKey[key]) + '</div></div>';
                    }).join('');
                }

                function renderView(rowData) {
                    viewGrid.innerHTML = payload.fields.map(function(field) {
                        const value = rowData[field.field];
                        return '<div class="dt-row-view-item">' +
                            '<span class="dt-row-view-label">' + escapeHtml(field.label) + '</span>' +
                            '<div class="dt-row-view-value">' + formatViewValue(field, value) + '</div>' +
                        '</div>';
                    }).join('');
                }

                function renderCustomEdit(rowData) {
                    formGrid.className = 'dt-row-form-grid dt-row-form-grid-custom';
                    if (formNote) {
                        formNote.textContent = 'Custom form modal menampilkan input yang lebih terstruktur dan cocok untuk edit cepat.';
                    }
                    formGrid.innerHTML = payload.fields.map(function(field) {
                        const value = inputValue(field, rowData[field.field]);
                        const wide = field.inputType === 'textarea';
                        let control = '';

                        if (field.inputType === 'boolean') {
                            const checked = String(value) === '1' || String(value).toLowerCase() === 'true' ? ' checked' : '';
                            control = '<label class="dt-check"><input type="checkbox" data-row-field="' + escapeHtml(field.field) + '" value="1"' + checked + (field.readonly ? ' disabled' : '') + '> Aktif</label>';
                        } else if (field.inputType === 'date') {
                            control = '<input type="date" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'datetime') {
                            control = '<input type="datetime-local" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'number') {
                            control = '<input type="number" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'textarea') {
                            control = '<textarea data-row-field="' + escapeHtml(field.field) + '" rows="4"' + (field.readonly ? ' readonly' : '') + '>' + escapeHtml(value) + '</textarea>';
                        } else {
                            control = '<input type="text" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        }

                        return '<div class="dt-row-field' + (wide ? ' wide' : '') + '">' +
                            '<label>' + escapeHtml(field.label) + '</label>' +
                            control +
                        '</div>';
                        }).join('');
                }

                function renderDefaultEdit(rowData) {
                    formGrid.className = 'dt-row-form-grid dt-row-form-grid-default';
                    if (formNote) {
                        formNote.textContent = 'Default modal menggunakan layout sederhana dan ringkas untuk input yang lebih familiar.';
                    }
                    formGrid.innerHTML = payload.fields.map(function(field) {
                        const value = inputValue(field, rowData[field.field]);
                        const wide = field.inputType === 'textarea';
                        let control = '';

                        if (field.inputType === 'boolean') {
                            const checked = String(value) === '1' || String(value).toLowerCase() === 'true' ? ' checked' : '';
                            control = '<label class="dt-check"><input type="checkbox" data-row-field="' + escapeHtml(field.field) + '" value="1"' + checked + (field.readonly ? ' disabled' : '') + '> Aktif</label>';
                        } else if (field.inputType === 'date') {
                            control = '<input type="date" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'datetime') {
                            control = '<input type="datetime-local" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'number') {
                            control = '<input type="number" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        } else if (field.inputType === 'textarea') {
                            control = '<textarea data-row-field="' + escapeHtml(field.field) + '" rows="3"' + (field.readonly ? ' readonly' : '') + '>' + escapeHtml(value) + '</textarea>';
                        } else {
                            control = '<input type="text" data-row-field="' + escapeHtml(field.field) + '" value="' + escapeHtml(value) + '"' + (field.readonly ? ' readonly' : '') + '>';
                        }

                        return '<div class="dt-row-field dt-row-field-default' + (wide ? ' wide' : '') + '">' +
                            '<label>' + escapeHtml(field.label) + '</label>' +
                            control +
                        '</div>';
                    }).join('');
                }

                function openRow(row, mode) {
                    const rowData = getRowData(row);
                    const rowKey = getRowKey(row);
                    activeRow = row;
                    const editModeLabel = payload.editMode === 'default' ? 'Default modal' : 'Custom form modal';
                    modalTitle.textContent = mode === 'edit' ? 'Edit Row' : 'View Row';
                    modalSubtitle.textContent = mode === 'edit'
                        ? 'Ubah data langsung dari modal yang sudah terisi nilai lama. Mode: ' + editModeLabel
                        : 'Lihat detail row dalam format yang lebih nyaman dibaca.';
                    renderSummary(rowKey);
                    renderView(rowData);
                    if (mode === 'edit') {
                        if (payload.editMode === 'default') {
                            renderDefaultEdit(rowData);
                        } else {
                            renderCustomEdit(rowData);
                        }
                    }
                    keyInput.value = JSON.stringify(rowKey || {});
                    viewMode.classList.toggle('active', mode === 'view');
                    editForm.classList.toggle('active', mode === 'edit');
                    saveButton.style.display = mode === 'edit' ? 'inline-flex' : 'none';
                    openModal();
                }

                root.querySelectorAll('[data-row-action]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const row = button.closest('tr');
                        if (!row) {
                            return;
                        }
                        openRow(row, button.getAttribute('data-row-action') === 'edit' ? 'edit' : 'view');
                    });
                });

                root.querySelectorAll('[data-row-modal-close]').forEach(function(button) {
                    button.addEventListener('click', closeModal);
                });

                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && modal.classList.contains('open')) {
                        closeModal();
                    }
                });

                editForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    if (!activeRow) {
                        return;
                    }

                    const values = {};
                    payload.fields.forEach(function(field) {
                        const input = editForm.querySelector('[data-row-field="' + field.field + '"]');
                        if (!input) {
                            return;
                        }

                        if (field.inputType === 'boolean') {
                            values[field.field] = input.checked ? 1 : 0;
                            return;
                        }

                        values[field.field] = input.value;
                    });

                    const request = new FormData();
                    request.append('_csrf', payload.csrfToken || '');
                    request.append('table_id', String(payload.tableId));
                    request.append('operation', 'upsert_row');
                    request.append('row_key', keyInput.value || '{}');
                    request.append('row_data', JSON.stringify(values));

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
                        window.location.reload();
                    }).catch(function(error) {
                        alert(error && error.message ? error.message : 'Gagal menyimpan data');
                    }).finally(function() {
                        saveButton.disabled = false;
                        saveButton.textContent = previousLabel;
                    });
                });
            })();
        </script>
        </section>
        <?php
        return (string)ob_get_clean();
    }

    private function renderNotice(string $title, string $message): string
    {
        return '<div style="margin:24px 0;padding:28px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;text-align:center;color:#64748b;"><strong style="display:block;color:#0f172a;font-size:16px;margin-bottom:4px;">'
            . Html::encode($title)
            . '</strong>'
            . Html::encode($message)
            . '</div>';
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
            $fieldName = (string)($column['field'] ?? '');
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
                'label' => (string)($column['label'] ?? $metadataColumn->label ?? $fieldName),
                'inputType' => $this->inferInputType($metadataColumn, $schemaColumn),
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
            return 'text';
        }

        if (in_array($type, ['BOOLEAN', 'TINYINT'], true) && ($length <= 1 || $type === 'BOOLEAN')) {
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

    private function pageUrl(string $pageParam, int $page): string
    {
        $params = Yii::$app->request->get();
        $params[$pageParam] = $page;
        return Url::current($params);
    }
}
