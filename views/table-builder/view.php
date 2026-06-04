<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable $model */
/** @var app\models\DbTableColumn[] $columns */
/** @var array $tableData */
/** @var array $databaseInfo */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Table Details: ' . $model->name;
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

$rowCount = count($tableData);
$columnCount = count($columns);
$primaryColumns = array_filter($columns, static function ($column) {
    return (bool)$column->is_primary;
});
$uniqueColumns = array_filter($columns, static function ($column) {
    return (bool)$column->is_unique;
});
$foreignKeyColumns = array_filter($columns, static function ($column) {
    return $column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key');
});
$databaseInfo = $databaseInfo ?? [];
$databaseName = $databaseInfo['name'] ?? null;
$databaseHost = $databaseInfo['host'] ?? null;
$databasePort = $databaseInfo['port'] ?? null;
$databaseTarget = $databaseName ?: '-';
if ($databaseHost) {
    $databaseTarget .= ' @ ' . $databaseHost;
    if ($databasePort) {
        $databaseTarget .= ':' . $databasePort;
    }
}
$displayedRowsText = $model->is_created
    ? ($rowCount === 100 ? 'Showing latest 100 rows' : "Showing {$rowCount} row" . ($rowCount === 1 ? '' : 's'))
    : 'Table has not been created in the database yet';
$fkDebugEnabled = Yii::$app->request->get('fk_debug') === '1';
$indexRoute = ['table-builder/index'];
$updateRoute = ['table-builder/update', 'id' => $model->id];
$viewRoute = ['table-builder/view', 'id' => $model->id];
$executeRoute = ['table-builder/execute-sql', 'id' => $model->id];
$previewSqlRoute = ['table-builder/preview-sql', 'id' => $model->id];
$syncRoute = ['table-builder/sync-from-database', 'id' => $model->id];
if ($fkDebugEnabled) {
    $indexRoute['fk_debug'] = 1;
    $updateRoute['fk_debug'] = 1;
    $viewRoute['fk_debug'] = 1;
    $executeRoute['fk_debug'] = 1;
    $previewSqlRoute['fk_debug'] = 1;
    $syncRoute['fk_debug'] = 1;
}

$spreadsheetContext = $spreadsheetContext ?? [];
$sheetColumns = $spreadsheetContext['columns'] ?? [];
$sheetRows = $spreadsheetContext['rows'] ?? [];
$sheetKeyColumns = $spreadsheetContext['keyColumns'] ?? [];
$sheetHasKeyColumns = !empty($spreadsheetContext['hasKeyColumns']);
$sheetActionRoute = ['table-builder/spreadsheet-action', 'id' => $model->id];
$liveTableRows = $liveTableRows ?? [];
$formColumnsMeta = array_values(array_filter(array_map(static function ($col) {
    return [
        'name' => $col->name,
        'type' => $col->type,
    ];
}, $columns), static function ($col) {
    return strtolower((string)($col['name'] ?? '')) !== 'id';
}));
?>

<style>
.table-detail-page {
    --ink: #142033;
    --muted: #60708a;
    --line: #d9e2ef;
    --panel: #ffffff;
    --panel-soft: #f6f8fc;
    --accent: var(--ws-sidebar-active-bg-start, #2563eb);
    --accent-strong: var(--ws-sidebar-active-bg-end, #1d4ed8);
    --accent-soft: var(--ws-sidebar-hover-bg, rgba(37, 99, 235, 0.12));
    --accent-ghost: var(--ws-light-sidebar-bg, #eff6ff);
    --success: #15803d;
    --success-soft: #dcfce7;
    --warning: #b45309;
    --warning-soft: #fef3c7;
    --danger: #b91c1c;
    --shadow: 0 18px 50px rgba(20, 32, 51, 0.08);
    color: var(--ink);
}

.table-detail-page .hero {
    background:
        radial-gradient(circle at top left, var(--accent-soft), transparent 28%),
        linear-gradient(180deg, #ffffff, #f7f9fc);
    border: 1px solid #e5ebf3;
    border-radius: 24px;
    padding: 28px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
}

.table-detail-page .hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
}

.table-detail-page .hero-title {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.table-detail-page .hero-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--accent-ghost), var(--accent-soft));
    color: var(--accent);
    border: 1px solid var(--line);
    flex-shrink: 0;
}

.table-detail-page h1 {
    font-size: 34px;
    line-height: 1.1;
    margin: 0 0 8px;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.table-detail-page .table-name {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 15px;
    color: var(--muted);
    margin: 0 0 6px;
}

.table-detail-page .hero-description {
    max-width: 760px;
    color: var(--muted);
    margin: 0;
    font-size: 14px;
}

.table-detail-page .actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.table-detail-page .btn-clean {
    border-radius: 12px;
    padding: 11px 16px;
    font-weight: 600;
    font-size: 14px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    text-decoration: none;
    transition: all 0.2s ease;
}

.table-detail-page .btn-clean:hover {
    border-color: #bfd0e6;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(20, 32, 51, 0.08);
}

.table-detail-page .btn-primary-clean {
    background: linear-gradient(135deg, var(--accent-strong), var(--accent));
    color: #fff;
    border-color: var(--accent-strong);
}

.table-detail-page .btn-primary-clean:hover {
    border-color: var(--accent-strong);
    color: #fff;
}

.table-detail-page .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.table-detail-page .status-created {
    background: var(--success-soft);
    color: var(--success);
}

.table-detail-page .status-pending {
    background: var(--warning-soft);
    color: var(--warning);
}

.table-detail-page .hero-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.table-detail-page .stat-card {
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid #e7edf5;
    border-radius: 18px;
    padding: 18px;
}

.table-detail-page .stat-label {
    display: block;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
}

.table-detail-page .stat-value {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 6px;
}

.table-detail-page .stat-note {
    color: var(--muted);
    font-size: 13px;
    margin: 0;
}

.table-detail-page .layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(320px, 0.95fr);
    gap: 24px;
}

.table-detail-page .stack {
    display: grid;
    gap: 24px;
}

.table-detail-page .panel {
    background: var(--panel);
    border: 1px solid #e3eaf3;
    border-radius: 22px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.table-detail-page .panel-header {
    padding: 20px 24px;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    background: linear-gradient(180deg, #ffffff, #f9fbfd);
}

.table-detail-page .panel-title {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 750;
    letter-spacing: -0.02em;
}

.table-detail-page .panel-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

.table-detail-page .panel-body {
    padding: 0;
}

.table-detail-page .table-wrap {
    overflow: auto;
}

.table-detail-page table {
    width: 100%;
    border-collapse: collapse;
}

.table-detail-page th,
.table-detail-page td {
    padding: 14px 18px;
    border-bottom: 1px solid #eef3f8;
    vertical-align: top;
    font-size: 14px;
}

.table-detail-page th {
    background: #f8fafc;
    color: #44536a;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}

.table-detail-page tbody tr:hover {
    background: #fbfdff;
}

.table-detail-page code {
    background: #f3f7fb;
    color: #1e3a5f;
    padding: 2px 7px;
    border-radius: 8px;
    font-size: 12px;
}

.table-detail-page .type-badge,
.table-detail-page .flag-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
}

.table-detail-page .type-badge {
    background: var(--accent-ghost);
    color: var(--accent-strong);
}

.table-detail-page .flag-badge {
    background: var(--accent-ghost);
    color: var(--accent-strong);
    margin-right: 6px;
    margin-bottom: 6px;
}

.table-detail-page .meta-list {
    display: grid;
    gap: 0;
}

.table-detail-page .meta-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 22px;
    border-bottom: 1px solid #eef3f8;
    font-size: 14px;
}

.table-detail-page .meta-row:last-child {
    border-bottom: 0;
}

.table-detail-page .meta-label {
    color: var(--muted);
    font-weight: 600;
}

.table-detail-page .meta-value {
    text-align: right;
    font-weight: 600;
    color: var(--ink);
}

.table-detail-page .empty-state {
    padding: 44px 24px;
    text-align: center;
}

.table-detail-page .empty-state .material-symbols-outlined {
    font-size: 42px;
    color: #8ea0b8;
    margin-bottom: 12px;
}

.table-detail-page .empty-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px;
}

.table-detail-page .empty-text {
    color: var(--muted);
    margin: 0;
    font-size: 14px;
}

.table-detail-page .sql-box {
    background: #0f172a;
    color: #dbe7ff;
    padding: 18px 20px;
    margin: 0;
    font-size: 12px;
    line-height: 1.7;
    max-height: 340px;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-word;
}

.table-detail-page .panel-footer {
    padding: 16px 20px;
    border-top: 1px solid #eaf0f6;
    background: #fbfcfe;
}

.table-detail-page .muted-inline {
    color: var(--muted);
    font-size: 13px;
}

.table-detail-page .relation-detail {
    display: block;
    margin-top: 6px;
    color: var(--muted);
    font-size: 12px;
}

.table-detail-page .null-value {
    color: #c2410c;
    font-weight: 700;
}

.table-detail-page .bool-yes {
    color: var(--success);
    font-weight: 700;
}

.table-detail-page .bool-no {
    color: var(--muted);
    font-weight: 700;
}

.table-detail-page .mode-switcher {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
}

.table-detail-page .mode-btn {
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    border-radius: 999px;
    padding: 10px 14px;
    font-weight: 700;
    cursor: pointer;
}

.table-detail-page .mode-btn.active {
    background: var(--ink);
    color: #fff;
    border-color: var(--ink);
}

.table-detail-page .sheet-shell {
    display: none;
}

.table-detail-page .sheet-toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.table-detail-page .sheet-toolbar-left,
.table-detail-page .sheet-toolbar-right {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.table-detail-page .sheet-note {
    color: var(--muted);
    font-size: 13px;
}

.table-detail-page .sheet-btn {
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 700;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    cursor: pointer;
}

.table-detail-page .sheet-btn.primary {
    background: var(--ink);
    color: #fff;
    border-color: var(--ink);
}

.table-detail-page .sheet-table-wrap {
    overflow: auto;
    border: 1px solid #e3eaf3;
    border-radius: 18px;
    background: #fff;
    box-shadow: var(--shadow);
}

.table-detail-page .sheet-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

.table-detail-page .sheet-table th,
.table-detail-page .sheet-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eef3f8;
    border-right: 1px solid #eef3f8;
    vertical-align: middle;
}

.table-detail-page .sheet-table th:last-child,
.table-detail-page .sheet-table td:last-child {
    border-right: 0;
}

.table-detail-page .sheet-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
    font-size: 12px;
    color: #44536a;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.table-detail-page .sheet-row:hover td {
    background: #fcfdff;
}

.table-detail-page .sheet-control {
    width: 100%;
    border: 1px solid transparent;
    background: transparent;
    border-radius: 10px;
    padding: 9px 10px;
    font-size: 14px;
    color: var(--ink);
    outline: none;
}

.table-detail-page .sheet-control:focus {
    border-color: #93c5fd;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
}

.table-detail-page .sheet-control[readonly] {
    color: var(--muted);
    background: #f8fafc;
}

.table-detail-page .sheet-checkbox {
    width: 16px;
    height: 16px;
}

.table-detail-page .sheet-state {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
}

.table-detail-page .sheet-state.saved {
    color: var(--success);
}

.table-detail-page .sheet-state.error {
    color: var(--danger);
}

.table-detail-page .sheet-state.pending {
    color: var(--warning);
}

.table-detail-page .sheet-draft {
    background: #f8fafc;
}

.table-detail-page .sheet-draft td:first-child {
    color: var(--accent);
    font-weight: 800;
}

.table-detail-page .sheet-empty {
    padding: 28px;
    color: var(--muted);
}

.table-detail-page .paste-area {
    width: 100%;
    min-height: 180px;
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
}

.table-detail-page .sheet-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.44);
    z-index: 50;
    padding: 24px;
}

.table-detail-page .sheet-modal.open {
    display: flex;
    align-items: center;
    justify-content: center;
}

.table-detail-page .sheet-modal-card {
    width: min(760px, 100%);
    background: #fff;
    border-radius: 22px;
    border: 1px solid #e3eaf3;
    box-shadow: var(--shadow);
    padding: 20px;
}

.table-detail-page .sheet-modal-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 12px;
}

@media (max-width: 1100px) {
    .table-detail-page .layout {
        grid-template-columns: 1fr;
    }

    .table-detail-page .hero-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .table-detail-page .hero {
        padding: 20px;
    }

    .table-detail-page .hero-top {
        flex-direction: column;
    }

    .table-detail-page .actions {
        width: 100%;
        justify-content: flex-start;
    }

    .table-detail-page .hero-stats {
        grid-template-columns: 1fr;
    }

    .table-detail-page th,
    .table-detail-page td {
        padding: 12px 14px;
    }
}
</style>

<div class="table-detail-page">
    <section class="hero">
        <div class="hero-top">
            <div class="hero-title">
                <div class="hero-icon">
                    <span class="material-symbols-outlined">table_chart</span>
                </div>
                <div>
                    <div class="<?= $model->is_created ? 'status-pill status-created' : 'status-pill status-pending' ?>">
                        <span class="material-symbols-outlined" style="font-size:16px;"><?= $model->is_created ? 'check_circle' : 'schedule' ?></span>
                        <?= $model->is_created ? 'Created in Database' : 'Pending Database Creation' ?>
                    </div>
                    <h1><?= Html::encode($model->label ?: $model->name) ?></h1>
                    <p class="table-name"><?= Html::encode($model->name) ?></p>
                    <?php if ($model->description): ?>
                        <p class="hero-description"><?= Html::encode($model->description) ?></p>
                    <?php else: ?>
                        <p class="hero-description">This page shows the actual table structure stored in metadata and the latest rows currently available in the database.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="actions">
                <?= Html::a('Back to Tables', $indexRoute, ['class' => 'btn-clean']) ?>
                <?= Html::a('Edit Structure', $updateRoute, ['class' => 'btn-clean']) ?>
                <?= Html::a('Sync From Database', $syncRoute, [
                    'class' => 'btn-clean',
                    'data' => [
                        'method' => 'post',
                    ],
                ]) ?>
                <?php if (!$model->is_created): ?>
                    <?= Html::a('Create in Database', $executeRoute, [
                        'class' => 'btn-clean btn-primary-clean',
                        'data' => [
                            'confirm' => 'Create this table in the database now?',
                            'method' => 'post',
                        ],
                    ]) ?>
                <?php else: ?>
                    <?= Html::a('Refresh Data', $viewRoute, ['class' => 'btn-clean']) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-stats">
            <div class="stat-card">
                <span class="stat-label">Columns</span>
                <div class="stat-value"><?= $columnCount ?></div>
                <p class="stat-note"><?= count($primaryColumns) ?> primary key, <?= count($uniqueColumns) ?> unique, <?= count($foreignKeyColumns) ?> foreign key</p>
            </div>
            <div class="stat-card">
                <span class="stat-label">Rows Loaded</span>
                <div class="stat-value"><?= $rowCount ?></div>
                <p class="stat-note"><?= Html::encode($displayedRowsText) ?></p>
            </div>
            <div class="stat-card">
                <span class="stat-label">Engine</span>
                <div class="stat-value" style="font-size:22px;"><?= Html::encode($model->engine) ?></div>
                <p class="stat-note"><?= Html::encode($model->charset) ?> / <?= Html::encode($model->collation) ?></p>
            </div>
            <div class="stat-card">
                <span class="stat-label">Created</span>
                <div class="stat-value" style="font-size:22px;"><?= Html::encode(date('d M Y', strtotime($model->created_at))) ?></div>
                <p class="stat-note"><?= Html::encode(date('H:i', strtotime($model->created_at))) ?></p>
            </div>
            <div class="stat-card">
                <span class="stat-label">Database</span>
                <div class="stat-value" style="font-size:22px;"><?= Html::encode($databaseName ?: '-') ?></div>
                <p class="stat-note"><?= Html::encode($databaseHost ? ($databaseHost . ($databasePort ? ':' . $databasePort : '')) : '-') ?></p>
            </div>
        </div>
    </section>

    <div class="mode-switcher" role="tablist" aria-label="Table view modes">
        <button type="button" class="mode-btn active" data-mode-btn="form">Form View</button>
        <button type="button" class="mode-btn" data-mode-btn="sheet">Spreadsheet View</button>
    </div>

    <div class="sheet-shell" data-mode-panel="sheet">
        <div class="sheet-toolbar">
            <div class="sheet-toolbar-left">
                <button type="button" class="sheet-btn primary" id="add-row-btn">Tambah Baris</button>
                <button type="button" class="sheet-btn" id="paste-excel-btn">Paste dari Excel</button>
                <button type="button" class="sheet-btn" id="delete-selected-btn">Hapus Dipilih</button>
                <button type="button" class="sheet-btn" id="duplicate-selected-btn">Duplicate Row</button>
            </div>
            <div class="sheet-toolbar-right">
                <div class="sheet-note">Klik cell untuk edit. Enter, Tab, dan panah dipakai untuk navigasi.</div>
            </div>
        </div>

        <div class="sheet-table-wrap">
            <?php if (!$model->is_created): ?>
                <div class="sheet-empty">
                    Table belum dibuat di database. Spreadsheet mode aktif setelah table fisik tersedia.
                </div>
            <?php elseif (empty($sheetColumns)): ?>
                <div class="sheet-empty">
                    Tidak ada kolom yang bisa diedit.
                </div>
            <?php else: ?>
                <table class="sheet-table" id="sheet-table">
                    <thead>
                        <tr>
                            <th style="width:48px;">
                                <input type="checkbox" id="sheet-check-all" class="sheet-checkbox">
                            </th>
                            <?php foreach ($sheetColumns as $sheetColumn): ?>
                                <th>
                                    <?= Html::encode($sheetColumn['label']) ?>
                                </th>
                            <?php endforeach; ?>
                            <th style="width:110px;">Status</th>
                            <th style="width:150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="sheet-body">
                        <?php if (!empty($sheetRows)): ?>
                            <?php foreach ($sheetRows as $rowIndex => $sheetRow): ?>
                                        <tr class="sheet-row" data-row-index="<?= (int)$rowIndex ?>" data-row-state="clean" data-row-key="<?= Html::encode(json_encode($sheetRow['key'])) ?>" data-row-values="<?= Html::encode(json_encode($sheetRow['values'])) ?>">
                                    <td>
                                        <input type="checkbox" class="sheet-checkbox sheet-row-check">
                                    </td>
                                    <?php foreach ($sheetColumns as $sheetColumn): ?>
                                        <?php
                                        $name = (string)$sheetColumn['name'];
                                        $value = $sheetRow['values'][$name] ?? null;
                                        $inputId = 'sheet-' . $rowIndex . '-' . $name;
                                        ?>
                                        <td data-column="<?= Html::encode($name) ?>">
                                            <?= $this->render('//table-builder/_sheet-cell', [
                                                'model' => $model,
                                                'column' => $sheetColumn,
                                                'value' => $value,
                                                'inputId' => $inputId,
                                                'rowIndex' => $rowIndex,
                                            ]) ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="sheet-state" data-row-status>Belum tersimpan</td>
                                    <td>
                                        <button type="button" class="sheet-btn primary sheet-save-row-btn" data-save-row-btn>Simpan</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div data-mode-panel="form">
    <div class="layout">
        <div class="stack">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Live Table Data</h2>
                        <p class="panel-subtitle">Rendered from the actual database table at request time.</p>
                    </div>
                    <div class="muted-inline"><?= Html::encode($displayedRowsText) ?></div>
                </div>
                <div class="panel-body">
                    <?php if (!$model->is_created): ?>
                        <div class="empty-state">
                            <span class="material-symbols-outlined">database</span>
                            <p class="empty-title">Table is not in the database yet</p>
                            <p class="empty-text">Only the metadata exists. Use “Create in Database” to execute the generated SQL.</p>
                        </div>
                    <?php elseif (empty($tableData)): ?>
                        <div class="empty-state">
                            <span class="material-symbols-outlined">inbox</span>
                            <p class="empty-title">No rows found</p>
                            <p class="empty-text">The table exists, but it currently has no data.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:72px;">#</th>
                                        <?php foreach ($formColumnsMeta as $col): ?>
                                            <th><?= Html::encode($col['name']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody id="form-table-body">
                            <?php foreach ($liveTableRows as $rowIndex => $row): ?>
                                        <tr data-form-row="1" data-form-row-index="<?= (int)$rowIndex ?>" data-form-row-key="<?= Html::encode(json_encode($row['key'] ?? [])) ?>">
                                            <td><?= $rowIndex + 1 ?></td>
                                            <?php foreach ($formColumnsMeta as $col): ?>
                                                <td data-form-cell data-column="<?= Html::encode($col['name']) ?>">
                                                    <?php
                                                    $value = $row['values'][$col['name']] ?? null;
                                                    if ($value === null) {
                                                        echo '<span class="null-value">NULL</span>';
                                                    } elseif (($col['type'] === 'BOOLEAN' || $col['type'] === 'TINYINT') && ($value === 0 || $value === 1 || $value === '0' || $value === '1')) {
                                                        echo (string)$value === '1'
                                                            ? '<span class="bool-yes">Yes</span>'
                                                            : '<span class="bool-no">No</span>';
                                                    } elseif (is_array($value)) {
                                                        echo '<code>' . Html::encode(json_encode($value)) . '</code>';
                                                    } else {
                                                        echo Html::encode((string)$value);
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Column Structure</h2>
                        <p class="panel-subtitle">Actual metadata used to build this table.</p>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (empty($columns)): ?>
                        <div class="empty-state">
                            <span class="material-symbols-outlined">view_column</span>
                            <p class="empty-title">No columns defined</p>
                            <p class="empty-text">Add fields in the table builder before creating the table.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:72px;">#</th>
                                        <th>Name</th>
                                        <th>Label</th>
                                        <th>Type</th>
                                        <th>Nullable</th>
                                        <th>Default</th>
                                        <th>Flags</th>
                                        <th>Relation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($columns as $index => $col): ?>
                                        <?php
                                        $isForeignKey = $col->hasAttribute('is_foreign_key') && (bool)$col->getAttribute('is_foreign_key');
                                        $referencedTable = $col->hasAttribute('referenced_table_name') ? (string)$col->getAttribute('referenced_table_name') : '';
                                        $referencedColumn = $col->hasAttribute('referenced_column_name') ? (string)$col->getAttribute('referenced_column_name') : '';
                                        $onDeleteAction = $col->hasAttribute('on_delete_action') ? (string)$col->getAttribute('on_delete_action') : 'RESTRICT';
                                        $onUpdateAction = $col->hasAttribute('on_update_action') ? (string)$col->getAttribute('on_update_action') : 'RESTRICT';
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><code><?= Html::encode($col->name) ?></code></td>
                                            <td><?= Html::encode($col->label) ?></td>
                                            <td>
                                                <span class="type-badge">
                                                    <?= Html::encode($col->type) ?><?= $col->length ? ' (' . (int)$col->length . ')' : '' ?>
                                                </span>
                                            </td>
                                            <td><?= $col->is_nullable ? 'Yes' : 'No' ?></td>
                                            <td>
                                                <?= $col->default_value !== null && $col->default_value !== '' ? Html::encode($col->default_value) : '<span class="null-value">NULL</span>' ?>
                                            </td>
                                            <td>
                                                <?php if ($col->is_primary): ?>
                                                    <span class="flag-badge">PK</span>
                                                <?php endif; ?>
                                                <?php if ($col->is_unique): ?>
                                                    <span class="flag-badge">UQ</span>
                                                <?php endif; ?>
                                                <?php if ($isForeignKey): ?>
                                                    <span class="flag-badge">FK</span>
                                                <?php endif; ?>
                                                <?php if (!$col->is_primary && !$col->is_unique && !$isForeignKey): ?>
                                                    <span class="muted-inline">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isForeignKey && $referencedTable !== '' && $referencedColumn !== ''): ?>
                                                    <code><?= Html::encode($referencedTable) ?>.<?= Html::encode($referencedColumn) ?></code>
                                                    <span class="relation-detail">ON DELETE <?= Html::encode($onDeleteAction ?: 'RESTRICT') ?> / ON UPDATE <?= Html::encode($onUpdateAction ?: 'RESTRICT') ?></span>
                                                <?php elseif ($isForeignKey): ?>
                                                    <span class="muted-inline">FK belum lengkap</span>
                                                <?php else: ?>
                                                    <span class="muted-inline">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Table Metadata</h2>
                        <p class="panel-subtitle">Current facts stored for this table definition.</p>
                    </div>
                </div>
                <div class="meta-list">
                    <div class="meta-row">
                        <div class="meta-label">Status</div>
                        <div class="meta-value"><?= $model->is_created ? 'Created in database' : 'Metadata only' ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Database</div>
                        <div class="meta-value"><?= Html::encode($databaseTarget) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Engine</div>
                        <div class="meta-value"><?= Html::encode($model->engine) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Charset</div>
                        <div class="meta-value"><?= Html::encode($model->charset) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Collation</div>
                        <div class="meta-value"><?= Html::encode($model->collation) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Loaded rows</div>
                        <div class="meta-value"><?= $rowCount ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Foreign keys</div>
                        <div class="meta-value"><?= count($foreignKeyColumns) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Created at</div>
                        <div class="meta-value"><?= Html::encode(date('d M Y H:i', strtotime($model->created_at))) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Updated at</div>
                        <div class="meta-value"><?= Html::encode(date('d M Y H:i', strtotime($model->updated_at))) ?></div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Generated SQL</h2>
                        <p class="panel-subtitle">Preview of the exact SQL built from the current metadata.</p>
                    </div>
                </div>
                <pre id="sql-preview" class="sql-box">Loading SQL preview...</pre>
                <div class="panel-footer">
                    <button type="button" class="btn-clean" id="copy-sql-btn">Copy SQL</button>
                </div>
            </section>
    </div>
    </div>
    </div>

    <div class="sheet-modal" id="sheet-paste-modal" aria-hidden="true">
        <div class="sheet-modal-card" role="dialog" aria-modal="true" aria-labelledby="sheet-paste-title">
            <div class="sheet-modal-head">
                <div>
                    <div id="sheet-paste-title" class="panel-title" style="margin-bottom: 6px;">Paste dari Excel</div>
                    <div class="panel-subtitle">Tempel data per baris. Kolom dibaca sesuai urutan Spreadsheet View.</div>
                </div>
                <button type="button" class="sheet-btn" id="close-paste-modal-btn">Tutup</button>
            </div>
            <textarea id="sheet-paste-input" class="paste-area" placeholder="Contoh:
Aldo	a@email.com	admin	aktif
Budi	b@email.com	guru	aktif"></textarea>
            <div class="sheet-toolbar" style="margin-top: 14px;">
                <div class="sheet-note">Pisahkan kolom dengan tab, baris dengan enter.</div>
                <div class="sheet-toolbar-right">
                    <button type="button" class="sheet-btn" id="submit-paste-btn">Import Baris</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sqlPreview = document.getElementById('sql-preview');
    const copySqlBtn = document.getElementById('copy-sql-btn');
    const fkDebugEnabled = <?= $fkDebugEnabled ? 'true' : 'false' ?> || window.localStorage.getItem('tb_fk_debug') === '1';
    const columnMetadata = <?= \yii\helpers\Json::encode(array_map(static function ($column) {
        return [
            'name' => $column->name,
            'is_foreign_key' => $column->hasAttribute('is_foreign_key') ? (bool)$column->getAttribute('is_foreign_key') : false,
            'referenced_table_name' => $column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : null,
            'referenced_column_name' => $column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : null,
            'on_delete_action' => $column->hasAttribute('on_delete_action') ? $column->getAttribute('on_delete_action') : null,
            'on_update_action' => $column->hasAttribute('on_update_action') ? $column->getAttribute('on_update_action') : null,
        ];
    }, $columns)) ?>;

    function fkDebugLog(stage, payload) {
        if (!fkDebugEnabled) {
            return;
        }
        try {
            console.groupCollapsed('[TableBuilder FK Debug] ' + stage);
            console.log(payload);
            console.groupEnd();
        } catch (error) {
            console.log('[TableBuilder FK Debug]', stage, payload);
        }
    }

    fkDebugLog('view.columns_metadata', {
        tableId: <?= (int)$model->id ?>,
        tableName: '<?= Html::encode($model->name) ?>',
        columnsCount: columnMetadata.length,
        fkColumnsCount: columnMetadata.filter(function (column) { return !!column.is_foreign_key; }).length,
        columns: columnMetadata,
    });

    fetch('<?= Url::to($previewSqlRoute) ?>')
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            sqlPreview.textContent = data.sql || '-- SQL preview unavailable';
            fkDebugLog('view.preview_sql_response', data);
        })
        .catch(function () {
            sqlPreview.textContent = '-- Failed to load SQL preview';
            fkDebugLog('view.preview_sql_error', { message: 'Failed to load SQL preview' });
        });

    copySqlBtn.addEventListener('click', function () {
        const sql = sqlPreview.textContent;
        const originalText = copySqlBtn.textContent;

        navigator.clipboard.writeText(sql).then(function () {
            copySqlBtn.textContent = 'Copied';
            setTimeout(function () {
                copySqlBtn.textContent = originalText;
            }, 1500);
        }).catch(function () {
            copySqlBtn.textContent = 'Copy failed';
            setTimeout(function () {
                copySqlBtn.textContent = originalText;
            }, 1500);
        });
    });

    const sheetActionUrl = '<?= Url::to($sheetActionRoute) ?>';
    const sheetTable = document.getElementById('sheet-table');
    const sheetBody = document.getElementById('sheet-body');
    const sheetColumns = <?= \yii\helpers\Json::encode($sheetColumns) ?>;
    const formColumns = <?= \yii\helpers\Json::encode($formColumnsMeta) ?>;
    const sheetHasKeyColumns = <?= $sheetHasKeyColumns ? 'true' : 'false' ?>;
    const modeButtons = document.querySelectorAll('[data-mode-btn]');
    const formPanel = document.querySelector('[data-mode-panel="form"]');
    const sheetPanel = document.querySelector('[data-mode-panel="sheet"]');
    let formTableBody = document.getElementById('form-table-body');
    const addRowBtn = document.getElementById('add-row-btn');
    const pasteExcelBtn = document.getElementById('paste-excel-btn');
    const deleteSelectedBtn = document.getElementById('delete-selected-btn');
    const duplicateSelectedBtn = document.getElementById('duplicate-selected-btn');
    const checkAll = document.getElementById('sheet-check-all');
    const pasteModal = document.getElementById('sheet-paste-modal');
    const closePasteModalBtn = document.getElementById('close-paste-modal-btn');
    const submitPasteBtn = document.getElementById('submit-paste-btn');
    const pasteInput = document.getElementById('sheet-paste-input');
    const csrfToken = '<?= Yii::$app->request->getCsrfToken() ?>';
    let draftRowCounter = 0;
    let formRowsState = <?= \yii\helpers\Json::encode($liveTableRows) ?>;
    const tableName = '<?= Html::encode($model->name) ?>';

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderFormCellValue(columnName, value) {
        const column = formColumns.find(function (item) {
            return item.name === columnName;
        }) || { type: 'text' };

        if (value === null || value === undefined || value === '') {
            return '<span class="null-value">NULL</span>';
        }

        if ((column.type === 'BOOLEAN' || column.type === 'TINYINT') && (value === 0 || value === 1 || value === '0' || value === '1' || value === true || value === false)) {
            return String(value) === '1' || value === true
                ? '<span class="bool-yes">Yes</span>'
                : '<span class="bool-no">No</span>';
        }

        if (Array.isArray(value)) {
            return '<code>' + escapeHtml(JSON.stringify(value)) + '</code>';
        }

        if (typeof value === 'object') {
            return '<code>' + escapeHtml(JSON.stringify(value)) + '</code>';
        }

        return escapeHtml(value);
    }

    function rowKeyToString(rowKey) {
        try {
            return JSON.stringify(rowKey || {});
        } catch (error) {
            return '{}';
        }
    }

    function renderFormRow(row, rowIndex) {
        let html = '<td>' + (rowIndex + 1) + '</td>';
        formColumns.forEach(function (column) {
            const value = row.values ? row.values[column.name] : null;
            html += '<td data-form-cell data-column="' + escapeHtml(column.name) + '">' + renderFormCellValue(column.name, value) + '</td>';
        });
        return html;
    }

    function renderFormTableFromState() {
        const tbody = ensureFormTableBody();
        if (!tbody) {
            return;
        }

        if (!formRowsState.length) {
            tbody.innerHTML = '<tr><td colspan="' + (formColumns.length + 1) + '"><div class="empty-state"><span class="material-symbols-outlined">inbox</span><p class="empty-title">No rows found</p><p class="empty-text">The table exists, but it currently has no data.</p></div></td></tr>';
            return;
        }

        tbody.innerHTML = formRowsState.map(function (row, index) {
            const key = rowKeyToString(row.key || {});
            return '<tr data-form-row="1" data-form-row-index="' + index + '" data-form-row-key="' + escapeHtml(key) + '">' + renderFormRow(row, index) + '</tr>';
        }).join('');
    }

    function ensureFormTableBody() {
        if (formTableBody) {
            return formTableBody;
        }

        const formPanelBody = formPanel ? formPanel.querySelector('.panel-body') : null;
        if (!formPanelBody) {
            return null;
        }

        formPanelBody.innerHTML = '<div class="table-wrap"><table><thead><tr><th style="width:72px;">#</th>' + formColumns.map(function (column) {
            return '<th>' + escapeHtml(column.name) + '</th>';
        }).join('') + '</tr></thead><tbody id="form-table-body"></tbody></table></div>';
        formTableBody = formPanelBody.querySelector('#form-table-body');
        return formTableBody;
    }

    function syncFormViewRow(rowKey, rowData) {
        const tbody = ensureFormTableBody();
        if (!tbody) {
            return;
        }

        const targetKey = rowKeyToString(rowKey);
        const normalizedRow = {
            key: rowKey || {},
            values: rowData || {}
        };
        const existingIndex = formRowsState.findIndex(function (item) {
            return rowKeyToString(item.key || {}) === targetKey;
        });

        if (existingIndex >= 0) {
            formRowsState[existingIndex] = normalizedRow;
        } else {
            formRowsState.unshift(normalizedRow);
        }

        renderFormTableFromState();
    }

    function setMode(mode) {
        modeButtons.forEach(function (button) {
            button.classList.toggle('active', button.getAttribute('data-mode-btn') === mode);
        });
        if (formPanel) {
            formPanel.style.display = mode === 'form' ? 'block' : 'none';
        }
        if (sheetPanel) {
            sheetPanel.style.display = mode === 'sheet' ? 'block' : 'none';
        }
    }

    function showPasteModal() {
        pasteModal.classList.add('open');
        pasteModal.setAttribute('aria-hidden', 'false');
        setTimeout(function () {
            pasteInput.focus();
        }, 50);
    }

    function hidePasteModal() {
        pasteModal.classList.remove('open');
        pasteModal.setAttribute('aria-hidden', 'true');
    }

    function getRowKey(row) {
        try {
            return JSON.parse(row.getAttribute('data-row-key') || '{}') || {};
        } catch (error) {
            return {};
        }
    }

    function setRowStatus(row, status, message) {
        const statusCell = row.querySelector('[data-row-status]');
        if (!statusCell) {
            return;
        }
        statusCell.className = 'sheet-state' + (status ? ' ' + status : '');
        statusCell.textContent = message || 'Tersimpan';
    }

    function hasMeaningfulValue(row) {
        return Array.from(row.querySelectorAll('[data-sheet-field]')).some(function (field) {
            if (field.type === 'checkbox') {
                return field.checked;
            }
            return String(field.value || '').trim() !== '';
        });
    }

    function getRowValues(row) {
        try {
            return JSON.parse(row.getAttribute('data-row-values') || '{}') || {};
        } catch (error) {
            return {};
        }
    }

    function setRowValues(row, values) {
        if (!row) {
            return;
        }
        row.setAttribute('data-row-values', JSON.stringify(values || {}));
    }

    function collectRowData(row) {
        const data = {};
        row.querySelectorAll('[data-sheet-field]').forEach(function (field) {
            const column = field.getAttribute('data-column');
            if (!column) {
                return;
            }
            if (field.type === 'checkbox') {
                data[column] = field.checked ? 1 : 0;
            } else {
                data[column] = field.value;
            }
        });
        return data;
    }

    function getChangedFields(row, currentData) {
        const previousData = getRowValues(row);
        const changes = {};
        const keys = new Set(Object.keys(previousData).concat(Object.keys(currentData || {})));
        keys.forEach(function (key) {
            const beforeValue = previousData[key];
            const afterValue = currentData[key];
            if (String(beforeValue ?? '') !== String(afterValue ?? '')) {
                changes[key] = {
                    before: beforeValue,
                    after: afterValue
                };
            }
        });
        return changes;
    }

    function buildFieldHtml(column, value, rowIndex, isDraft) {
        const columnName = column.name;
        const fieldId = 'sheet-' + rowIndex + '-' + columnName + '-' + (isDraft ? 'draft' : 'row');
        const safeValue = value === null || value === undefined ? '' : String(value).replace(/"/g, '&quot;');
        const readonlyAttr = column.readOnly ? ' readonly disabled' : '';

        if (column.inputType === 'boolean') {
            const selectedValue = safeValue === '1' || String(safeValue).toLowerCase() === 'true' ? '1' : '0';
            return '<select id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '"' + readonlyAttr + '>' +
                '<option value="1"' + (selectedValue === '1' ? ' selected' : '') + '>Aktif</option>' +
                '<option value="0"' + (selectedValue === '0' ? ' selected' : '') + '>Nonaktif</option>' +
            '</select>';
        }

        if (column.inputType === 'datalist') {
            const listId = fieldId + '-list';
            let options = '';
            (column.options || []).forEach(function (option) {
                const optionValue = String(option.value ?? '');
                options += '<option value="' + optionValue.replace(/"/g, '&quot;') + '"></option>';
            });
            return '<input type="text" list="' + listId + '" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="' + safeValue + '"' + readonlyAttr + '><datalist id="' + listId + '">' + options + '</datalist>';
        }

        if (column.inputType === 'datalist') {
            const listId = fieldId + '-list';
            let options = '';
            (column.options || []).forEach(function (option) {
                const optionValue = String(option.value ?? '');
                options += '<option value="' + optionValue.replace(/"/g, '&quot;') + '"></option>';
            });
            return '<input type="text" list="' + listId + '" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="' + safeValue + '"' + readonlyAttr + '><datalist id="' + listId + '">' + options + '</datalist>';
        }

        if (column.inputType === 'select') {
            let options = '<option value="">--</option>';
            (column.options || []).forEach(function (option) {
                const optionValue = String(option.value ?? '');
                const selected = optionValue === safeValue ? ' selected' : '';
                options += '<option value="' + optionValue.replace(/"/g, '&quot;') + '"' + selected + '>' + String(option.label ?? optionValue) + '</option>';
            });
            return '<select id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '"' + readonlyAttr + '>' + options + '</select>';
        }

        if (column.inputType === 'password') {
            return '<input type="password" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="" placeholder="••••••••"' + readonlyAttr + '>';
        }

        if (column.inputType === 'password') {
            return '<input type="password" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" placeholder="••••••••" value=""' + readonlyAttr + '>';
        }

        if (column.inputType === 'date') {
            return '<input type="date" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="' + safeValue + '"' + readonlyAttr + '>';
        }

        if (column.inputType === 'datetime') {
            const datetimeValue = safeValue ? safeValue.slice(0, 16).replace(' ', 'T') : '';
            return '<input type="datetime-local" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="' + datetimeValue + '"' + readonlyAttr + '>';
        }

        if (column.inputType === 'number') {
            return '<input type="number" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="' + safeValue + '"' + readonlyAttr + '>';
        }

        if (column.inputType === 'textarea') {
            return '<textarea id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" rows="1"' + readonlyAttr + '>' + safeValue + '</textarea>';
        }

        return '<input type="text" id="' + fieldId + '" class="sheet-control sheet-field" data-sheet-field data-column="' + columnName + '" value="' + safeValue + '"' + readonlyAttr + '>';
    }

    function createDraftRow() {
        if (!sheetBody || !sheetColumns.length) {
            return;
        }

        const rowIndex = 'draft-' + (++draftRowCounter);
        const row = document.createElement('tr');
        row.className = 'sheet-row sheet-draft';
        row.setAttribute('data-row-index', rowIndex);
        row.setAttribute('data-row-state', 'draft');
        row.setAttribute('data-row-key', '{}');

        let html = '<td><input type="checkbox" class="sheet-checkbox sheet-row-check"></td>';
        sheetColumns.forEach(function (column) {
            html += '<td data-column="' + column.name + '">' + buildFieldHtml(column, '', rowIndex, true) + '</td>';
        });
        html += '<td class="sheet-state" data-row-status>Draft</td>';
        html += '<td><button type="button" class="sheet-btn primary sheet-save-row-btn" data-save-row-btn>Simpan</button></td>';
        row.innerHTML = html;
        sheetBody.appendChild(row);
        bindSheetRow(row);
        const firstField = row.querySelector('[data-sheet-field]');
        if (firstField) {
            firstField.focus();
        }
    }

    function markRowDirty(row) {
        if (!row || !hasMeaningfulValue(row)) {
            return;
        }
        if (row.getAttribute('data-row-state') === 'saved') {
            row.setAttribute('data-row-state', 'dirty');
        }
        setRowStatus(row, 'pending', 'Belum disimpan');
    }

    function saveRow(row) {
        if (!row || !hasMeaningfulValue(row)) {
            return;
        }

        const rowId = row.getAttribute('data-row-index') || '';
        const rawRowData = collectRowData(row);
        const changedFields = getChangedFields(row, rawRowData);
        const rowKey = getRowKey(row);
        console.debug('[Spreadsheet] saveRow', {
            row_id: rowId,
            row_key: rowKey,
            raw_row_data: rawRowData,
            changed_fields: changedFields
        });

        const formData = new FormData();
        formData.append('_csrf', csrfToken);
        formData.append('table_id', '<?= (int)$model->id ?>');
        formData.append('operation', 'upsert_row');
        formData.append('row_data', JSON.stringify(rawRowData));
        formData.append('row_key', JSON.stringify(rowKey));

        console.debug('[Spreadsheet] payload', {
            row_id: rowId,
            row_key: rowKey,
            payload_update: {
                table_id: '<?= (int)$model->id ?>',
                operation: 'upsert_row',
                row_key: rowKey,
                row_data: rawRowData
            }
        });

        setRowStatus(row, '', 'Menyimpan...');

        fetch(sheetActionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (!data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Gagal menyimpan baris');
            }
            row.setAttribute('data-row-key', JSON.stringify(data.row_key || {}));
            setRowValues(row, data.row_data || rawRowData);
            row.setAttribute('data-row-state', 'saved');
            row.classList.remove('sheet-draft');
            setRowStatus(row, 'saved', 'Tersimpan');
            syncFormViewRow(data.row_key || rowKey, data.row_data || rawRowData);
            setMode('form');
            if (tableName === 'users') {
                const passwordField = row.querySelector('[data-column="password"] [data-sheet-field]');
                if (passwordField) {
                    passwordField.value = '';
                }
            }
        }).catch(function (error) {
            row.setAttribute('data-row-state', 'error');
            if (error && error.message === 'Belum lengkap') {
                setRowStatus(row, 'pending', 'Belum lengkap');
                return;
            }
            setRowStatus(row, 'error', error.message || 'Gagal');
        });
    }

    function bindSheetRow(row) {
        row.querySelectorAll('[data-sheet-field]').forEach(function (field) {
            field.addEventListener('change', function () {
                const column = field.getAttribute('data-column') || '';
                const currentValue = field.type === 'checkbox' ? (field.checked ? 1 : 0) : field.value;
                const selectedOptionLabel = field.tagName && field.tagName.toLowerCase() === 'select'
                    ? (field.options && field.selectedIndex >= 0 && field.options[field.selectedIndex] ? String(field.options[field.selectedIndex].text || '') : '')
                    : '';
                console.debug('[Spreadsheet] field change', {
                    row_id: row.getAttribute('data-row-index') || '',
                    column: column,
                    value: currentValue,
                    option_label: selectedOptionLabel,
                    field_type: field.type || field.tagName.toLowerCase()
                });
                markRowDirty(row);
            });
            field.addEventListener('blur', function () {
                markRowDirty(row);
            });
            field.addEventListener('keydown', function (event) {
                const key = event.key;
                const navigable = ['Enter', 'Tab', 'ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'];
                if (!navigable.includes(key)) {
                    return;
                }

                if (key === 'ArrowLeft' || key === 'ArrowUp' || key === 'ArrowRight' || key === 'ArrowDown') {
                    if (field.tagName.toLowerCase() === 'select') {
                        return;
                    }
                }

                event.preventDefault();
                const fields = Array.from(sheetBody.querySelectorAll('[data-sheet-field]:not([disabled])'));
                const currentIndex = fields.indexOf(field);
                if (currentIndex === -1) {
                    return;
                }

                let nextIndex = currentIndex;
                if (key === 'ArrowLeft' || key === 'ArrowUp' || (key === 'Tab' && event.shiftKey)) {
                    nextIndex = Math.max(0, currentIndex - 1);
                } else {
                    nextIndex = Math.min(fields.length - 1, currentIndex + 1);
                }

                const nextField = fields[nextIndex];
                if (nextField) {
                    nextField.focus();
                    if (nextField.select) {
                        nextField.select();
                    }
                }
            });
        });

        const saveButton = row.querySelector('[data-save-row-btn]');
        if (saveButton) {
            saveButton.addEventListener('click', function () {
                saveRow(row);
            });
        }
    }

    if (sheetTable && sheetBody) {
        sheetBody.querySelectorAll('.sheet-row').forEach(bindSheetRow);
    }

    modeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.getAttribute('data-mode-btn'));
        });
    });
    setMode('form');

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            if (!sheetBody) {
                return;
            }
            const checked = checkAll.checked;
            sheetBody.querySelectorAll('.sheet-row-check').forEach(function (input) {
                input.checked = checked;
            });
        });
    }

    if (addRowBtn) {
        addRowBtn.addEventListener('click', createDraftRow);
    }

    if (pasteExcelBtn) {
        pasteExcelBtn.addEventListener('click', showPasteModal);
    }
    if (closePasteModalBtn) {
        closePasteModalBtn.addEventListener('click', hidePasteModal);
    }
    if (pasteModal) {
        pasteModal.addEventListener('click', function (event) {
            if (event.target === pasteModal) {
                hidePasteModal();
            }
        });
    }

    if (submitPasteBtn) {
        submitPasteBtn.addEventListener('click', function () {
            const raw = pasteInput.value || '';
            const rows = raw.split(/\r?\n/).map(function (line) {
                return line.split('\t');
            }).filter(function (row) {
                return row.some(function (value) {
                    return String(value || '').trim() !== '';
                });
            });

            if (!rows.length) {
                return;
            }

            const formData = new FormData();
            formData.append('_csrf', csrfToken);
            formData.append('table_id', '<?= (int)$model->id ?>');
            formData.append('operation', 'bulk_paste');
            formData.append('rows', JSON.stringify(rows));

            fetch(sheetActionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Gagal import');
                }
                hidePasteModal();
                window.location.reload();
            }).catch(function (error) {
                alert(error.message || 'Gagal import data');
            });
        });
    }

    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function () {
            if (!sheetBody) {
                return;
            }
            const selectedRows = Array.from(sheetBody.querySelectorAll('.sheet-row-check:checked')).map(function (checkbox) {
                return checkbox.closest('.sheet-row');
            }).filter(Boolean);

            if (!selectedRows.length) {
                return;
            }

            const rowKeys = selectedRows.map(function (row) {
                return getRowKey(row);
            }).filter(function (key) {
                return Object.keys(key).length > 0;
            });

            if (!rowKeys.length) {
                return;
            }

            let confirmMessage = 'Hapus baris yang dipilih?';
            if (tableName === 'users') {
                const hasAdmin = selectedRows.some(function (row) {
                    const values = getRowValues(row);
                    const usernameField = row.querySelector('[data-column="username"] [data-sheet-field]');
                    const roleField = row.querySelector('[data-column="role"] [data-sheet-field]');
                    const username = String((usernameField && usernameField.value) || values.username || '').toLowerCase();
                    const role = String((roleField && roleField.value) || values.role || '').toLowerCase();
                    return username === 'admin' || role === 'admin';
                });

                confirmMessage = hasAdmin
                    ? 'Anda akan menghapus data user penting. Lanjutkan hanya jika benar-benar yakin.'
                    : 'Hapus baris user yang dipilih?';
            }

            if (!window.confirm(confirmMessage)) {
                return;
            }

            const formData = new FormData();
            formData.append('_csrf', csrfToken);
            formData.append('table_id', '<?= (int)$model->id ?>');
            formData.append('operation', 'delete_rows');
            formData.append('row_keys', JSON.stringify(rowKeys));

            fetch(sheetActionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Gagal menghapus baris');
                }
                window.location.reload();
            }).catch(function (error) {
                alert(error.message || 'Gagal menghapus baris');
            });
        });
    }

    if (duplicateSelectedBtn) {
        duplicateSelectedBtn.addEventListener('click', function () {
            if (!sheetBody) {
                return;
            }
            const rowKeys = Array.from(sheetBody.querySelectorAll('.sheet-row-check:checked')).map(function (checkbox) {
                const row = checkbox.closest('.sheet-row');
                return getRowKey(row);
            }).filter(function (key) {
                return Object.keys(key).length > 0;
            });

            if (!rowKeys.length) {
                return;
            }

            const formData = new FormData();
            formData.append('_csrf', csrfToken);
            formData.append('table_id', '<?= (int)$model->id ?>');
            formData.append('operation', 'duplicate_rows');
            formData.append('row_keys', JSON.stringify(rowKeys));

            fetch(sheetActionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Gagal menggandakan baris');
                }
                window.location.reload();
            }).catch(function (error) {
                alert(error.message || 'Gagal menggandakan baris');
            });
        });
    }
});
</script>
