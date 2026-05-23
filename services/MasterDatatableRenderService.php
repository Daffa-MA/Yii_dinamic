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
        $editForm = $this->resolveEditForm($config);
        $primaryKeys = !empty($tableSchema->primaryKey) ? array_values($tableSchema->primaryKey) : [];
        $uid = 'dt-' . $tableId . '-' . substr(md5(json_encode($config)), 0, 8);

        return $this->renderTable($uid, $table, $columns, $rows, $actions, $editMode, $editForm, $primaryKeys, [
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
        foreach ((array)($schema['fields'] ?? []) as $field) {
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
                'label' => trim((string)($field['label'] ?? $fieldName)) ?: $fieldName,
                'inputType' => FormSystemFieldHelper::resolveFieldInputType($field),
                'placeholder' => (string)($field['placeholder'] ?? ''),
                'required' => !empty($field['required']),
                'defaultValue' => $field['default_value'] ?? null,
                'options' => $this->normalizeFormFieldOptions($field),
                'componentType' => (string)($field['component_type'] ?? ($field['type'] ?? 'text')),
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

    private function renderTable(string $uid, DbTable $table, array $columns, array $rows, array $actions, string $editMode, array $editForm, array $primaryKeys, array $state): string
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
                #<?= Html::encode($uid) ?> .dt-row-view-shell { display:grid; grid-template-columns:minmax(260px, 0.92fr) minmax(0, 1.4fr); gap:16px; align-items:start; }
                #<?= Html::encode($uid) ?> .dt-row-view-aside { display:grid; gap:12px; align-content:start; }
                #<?= Html::encode($uid) ?> .dt-row-view-hero { border:1px solid #dbe3ef; border-radius:22px; background:linear-gradient(135deg,#0f172a 0%,#1e293b 58%,#0f172a 100%); padding:18px; color:#fff; box-shadow:0 18px 36px rgba(15,23,42,.14); position:relative; overflow:hidden; }
                #<?= Html::encode($uid) ?> .dt-row-view-hero::after { content:''; position:absolute; inset:auto -20% -28px auto; width:180px; height:180px; border-radius:999px; background:radial-gradient(circle, rgba(96,165,250,.22) 0%, rgba(96,165,250,0) 70%); pointer-events:none; }
                #<?= Html::encode($uid) ?> .dt-row-view-hero-kicker { display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:999px; background:rgba(255,255,255,.08); color:#bfdbfe; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:12px; }
                #<?= Html::encode($uid) ?> .dt-row-view-hero h5 { margin:0 0 8px; color:#fff; font-size:18px; font-weight:800; line-height:1.25; }
                #<?= Html::encode($uid) ?> .dt-row-view-hero p { margin:0; color:rgba(255,255,255,.78); font-size:13px; line-height:1.6; }
                #<?= Html::encode($uid) ?> .dt-row-view-panel { border:1px solid #e2e8f0; border-radius:18px; background:#fff; padding:16px 18px; box-shadow:0 10px 24px rgba(15,23,42,.04); }
                #<?= Html::encode($uid) ?> .dt-row-view-panel h5 { margin:0 0 10px; color:#0f172a; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
                #<?= Html::encode($uid) ?> .dt-row-view-panel p { margin:0; color:#475569; font-size:13px; line-height:1.6; }
                #<?= Html::encode($uid) ?> .dt-row-view-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
                #<?= Html::encode($uid) ?> .dt-row-view-chip { display:inline-flex; align-items:center; gap:6px; padding:7px 10px; border-radius:999px; background:rgba(255,255,255,.08); color:#e2e8f0; font-size:11px; font-weight:700; }
                #<?= Html::encode($uid) ?> .dt-row-view-panel--soft { background:linear-gradient(180deg,#fff 0%,#f8fafc 100%); }
                #<?= Html::encode($uid) ?> .dt-row-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
                #<?= Html::encode($uid) ?> .dt-summary-card { border:1px solid #e2e8f0; border-radius:16px; background:linear-gradient(180deg,#f8fafc 0%,#fff 100%); padding:14px 16px; box-shadow:0 8px 20px rgba(15,23,42,.04); }
                #<?= Html::encode($uid) ?> .dt-summary-card.primary { background:linear-gradient(135deg,#eff6ff 0%,#ffffff 100%); border-color:#bfdbfe; }
                #<?= Html::encode($uid) ?> .dt-summary-label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
                #<?= Html::encode($uid) ?> .dt-summary-value { color:#0f172a; font-size:14px; font-weight:700; word-break:break-word; }
                #<?= Html::encode($uid) ?> .dt-row-view-main { display:grid; gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-view-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
                #<?= Html::encode($uid) ?> .dt-row-view-item { border:1px solid #e2e8f0; border-radius:18px; background:#fff; padding:16px 18px; box-shadow:0 8px 20px rgba(15,23,42,.03); position:relative; overflow:hidden; }
                #<?= Html::encode($uid) ?> .dt-row-view-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:linear-gradient(180deg,#2563eb 0%,#22c55e 100%); }
                #<?= Html::encode($uid) ?> .dt-row-view-label { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; padding-left:4px; }
                #<?= Html::encode($uid) ?> .dt-row-view-value { color:#0f172a; font-size:14px; line-height:1.7; word-break:break-word; padding-left:4px; }
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
                    'editForm' => $editForm,
                    'fields' => $rowFields,
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
                        control = '<label class="dt-row-choice-item" style="margin:0;">' +
                            '<input type="checkbox" data-row-field="' + escapeHtml(fieldName) + '" value="1"' + checked + (field.readonly ? ' disabled' : '') + '>' +
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
                    const primaryField = payload.fields[0] || null;
                    const primaryValue = primaryField ? rowData[primaryField.field] : '';
                    const detailFields = primaryField ? payload.fields.slice(1) : payload.fields;

                    viewGrid.innerHTML = '<div class="dt-row-view-item dt-row-view-item--lead">' +
                        '<span class="dt-row-view-label">Ringkasan utama</span>' +
                        '<div class="dt-row-view-value">' + escapeHtml(primaryField ? primaryField.label : 'Data row') + '</div>' +
                        '<div class="dt-row-custom-inline-note" style="margin-top:8px;">' + formatViewValue(primaryField || {inputType: 'text'}, primaryValue) + '</div>' +
                    '</div>' +
                    detailFields.map(function(field) {
                        const value = rowData[field.field];
                        return '<div class="dt-row-view-item">' +
                            '<span class="dt-row-view-label">' + escapeHtml(field.label) + '</span>' +
                            '<div class="dt-row-view-value">' + formatViewValue(field, value) + '</div>' +
                        '</div>';
                    }).join('');

                    const leadNote = root.querySelector('.dt-row-view-panel p[data-row-reference]');
                    if (leadNote) {
                        leadNote.textContent = 'Data ini ditampilkan dengan ringkasan singkat di kiri dan detail field di kanan, supaya mudah dipindai tanpa terasa penuh.';
                    }
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
                            control = '<label class="dt-check"><input type="checkbox" data-row-field="' + escapeHtml(fieldName) + '" value="1"' + checked + (field.readonly ? ' disabled' : '') + '> Aktif</label>';
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
                }

                function openRow(row, mode) {
                    const rowData = getRowData(row);
                    const rowKey = getRowKey(row);
                    activeRow = row;
                    const editModeLabel = payload.editMode === 'default' ? 'Default modal' : 'Custom form modal';
                    const formName = payload.editForm && payload.editForm.name ? payload.editForm.name : '';
                    modalTitle.textContent = mode === 'edit' ? 'Edit Row' : 'View Row';
                    modalSubtitle.textContent = mode === 'edit'
                        ? 'Ubah data langsung dari modal yang sudah terisi nilai lama. Mode: ' + editModeLabel + (formName ? ' · ' + formName : '')
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
                    editFormEl.classList.toggle('active', mode === 'edit');
                    saveButton.style.display = mode === 'edit' ? 'inline-flex' : 'none';
                    if (modalFooter) {
                        modalFooter.classList.toggle('is-hidden', mode === 'edit' && payload.editMode === 'custom');
                    }
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

    private function pageUrl(string $pageParam, int $page): string
    {
        $params = Yii::$app->request->get();
        $params[$pageParam] = $page;
        return Url::current($params);
    }
}
