<?php

namespace app\services;

use app\components\ActiveProjectContext;
use app\components\ProjectPermissionService;
use app\components\ProjectSchema;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\MasterDatatable;
use Yii;
use yii\db\Query;
use yii\helpers\Html;
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
        $primaryKey = $tableSchema->primaryKey[0] ?? null;
        $uid = 'dt-' . $tableId . '-' . substr(md5(json_encode($config)), 0, 8);

        return $this->renderTable($uid, $table, $columns, $rows, $actions, $primaryKey, [
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

    private function canUseTable(DbTable $table): bool
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        return $activeProjectId === null || !$table->hasAttribute('project_id') || (int)$table->project_id === (int)$activeProjectId;
    }

    private function renderTable(string $uid, DbTable $table, array $columns, array $rows, array $actions, ?string $primaryKey, array $state): string
    {
        $hasActions = in_array(true, $actions, true) && $primaryKey !== null;
        $colspan = count($columns) + ($hasActions ? 1 : 0);
        $totalPages = max(1, (int)ceil(($state['total'] ?: 0) / $state['pageSize']));

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
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td><?= Html::encode($this->formatValue($row[$column['field']] ?? null)) ?></td>
                                <?php endforeach; ?>
                                <?php if ($hasActions): ?>
                                    <td>
                                        <div class="dt-actions">
                                            <?php $rowKey = [$primaryKey => $row[$primaryKey] ?? null]; ?>
                                            <?php if ($actions['view']): ?>
                                                <a class="dt-btn" href="<?= Html::encode(Url::to(['/table-builder/view', 'id' => $table->id, 'row_key' => json_encode($rowKey)])) ?>">View</a>
                                            <?php endif; ?>
                                            <?php if ($actions['edit']): ?>
                                                <a class="dt-btn" href="<?= Html::encode(Url::to(['/table-builder/view', 'id' => $table->id, 'row_key' => json_encode($rowKey), 'edit' => 1])) ?>">Edit</a>
                                            <?php endif; ?>
                                            <?php if ($actions['delete']): ?>
                                                <form method="post" action="<?= Html::encode(Url::to(['/master-datatable/delete-row', 'table_id' => $table->id])) ?>" onsubmit="return confirm('Delete this row?');">
                                                    <input type="hidden" name="<?= Html::encode(Yii::$app->request->csrfParam) ?>" value="<?= Html::encode(Yii::$app->request->csrfToken) ?>">
                                                    <input type="hidden" name="row_key" value="<?= Html::encode(json_encode($rowKey)) ?>">
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

    private function pageUrl(string $pageParam, int $page): string
    {
        $params = Yii::$app->request->get();
        $params[$pageParam] = $page;
        return Url::current($params);
    }
}
