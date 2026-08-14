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
$liveTableTotal = $liveTableTotal ?? 0;
$liveTablePageSize = $liveTablePageSize ?? 10;
$liveTableFirst = $liveTableTotal > 0 ? 1 : 0;
$liveTableLast = min($liveTablePageSize, $liveTableTotal);
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
    ? ($liveTableTotal === 0
        ? 'Table kosong, belum ada data.'
        : 'Menampilkan ' . $liveTableFirst . '–' . $liveTableLast . ' dari ' . $liveTableTotal . ' baris')
    : 'Table has not been created in the database yet';
$fkDebugEnabled = Yii::$app->request->get('fk_debug') === '1';
$indexRoute = ['table-builder/index'];
$updateRoute = ['table-builder/update', 'id' => $model->id];
$viewRoute = ['table-builder/view', 'id' => $model->id];
$executeRoute = ['table-builder/execute-sql', 'id' => $model->id];
$previewSqlRoute = ['table-builder/preview-sql', 'id' => $model->id];
$syncRoute = ['table-builder/sync-from-database', 'id' => $model->id];
$exportCsvRoute = ['table-builder/export', 'id' => $model->id, 'format' => 'csv'];
$exportPrintRoute = ['table-builder/export', 'id' => $model->id, 'format' => 'print'];
$deleteRoute = ['table-builder/delete', 'id' => $model->id];
if ($fkDebugEnabled) {
    $indexRoute['fk_debug'] = 1;
    $updateRoute['fk_debug'] = 1;
    $viewRoute['fk_debug'] = 1;
    $executeRoute['fk_debug'] = 1;
    $previewSqlRoute['fk_debug'] = 1;
    $syncRoute['fk_debug'] = 1;
    $exportCsvRoute['fk_debug'] = 1;
    $exportPrintRoute['fk_debug'] = 1;
    $deleteRoute['fk_debug'] = 1;
}

$spreadsheetContext = $spreadsheetContext ?? [];
$sheetColumns = $spreadsheetContext['columns'] ?? [];
$sheetRows = $spreadsheetContext['rows'] ?? [];
$sheetKeyColumns = $spreadsheetContext['keyColumns'] ?? [];
$sheetHasKeyColumns = !empty($spreadsheetContext['hasKeyColumns']);
$sheetActionRoute = ['table-builder/spreadsheet-action', 'id' => $model->id];
$liveTableRoute = ['table-builder/live-table-data', 'id' => $model->id];
$importMetaRoute = ['table-builder/import-meta', 'id' => $model->id];
$importExampleRoute = ['table-builder/import-example', 'id' => $model->id];
$importPreviewRoute = ['table-builder/import-preview', 'id' => $model->id];
$importExecuteRoute = ['table-builder/import-execute', 'id' => $model->id];
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

.table-detail-page .btn-danger-clean {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.table-detail-page .btn-danger-clean:hover {
    border-color: #fca5a5;
    background: #fee2e2;
    color: #7f1d1d;
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

.table-detail-page .live-table-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    padding: 12px 14px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
}

.table-detail-page .live-table-tools {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.table-detail-page .live-search {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 0 12px;
    background: #f8fafc;
    min-width: 200px;
}

.table-detail-page .live-search .material-symbols-outlined {
    font-size: 18px;
    color: var(--muted);
    flex-shrink: 0;
}

.table-detail-page .live-search input {
    border: none;
    outline: none;
    background: transparent;
    padding: 9px 0;
    font-size: 13.5px;
    color: var(--ink);
    width: 100%;
}

.table-detail-page .live-search:focus-within {
    border-color: var(--accent);
    background: #fff;
    box-shadow: 0 0 0 3px var(--accent-ghost);
}

.table-detail-page .live-page-size {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: var(--muted);
    font-weight: 600;
}

.table-detail-page .live-page-size select {
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--ink);
    background: #fff;
    cursor: pointer;
    outline: none;
}

.table-detail-page .live-page-size select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-ghost);
}

.table-detail-page .live-pager {
    display: flex;
    align-items: center;
    gap: 6px;
}

.table-detail-page .live-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    cursor: pointer;
}

.table-detail-page .live-page-btn:hover:not(:disabled) {
    background: var(--accent-ghost);
    border-color: var(--accent);
    color: var(--accent-strong);
}

.table-detail-page .live-page-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.table-detail-page .live-page-btn .material-symbols-outlined {
    font-size: 20px;
}

.table-detail-page .live-pages {
    display: flex;
    align-items: center;
    gap: 5px;
}

.table-detail-page .live-page-num {
    min-width: 36px;
    height: 36px;
    padding: 0 8px;
    border-radius: 10px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.table-detail-page .live-page-num:hover:not(.active) {
    background: var(--accent-ghost);
    border-color: var(--accent);
    color: var(--accent-strong);
}

.table-detail-page .live-page-num.active {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
}

.table-detail-page .live-ellipsis {
    color: var(--muted);
    font-size: 13px;
    font-weight: 700;
    padding: 0 2px;
}

.table-detail-page .live-table-bar.is-loading {
    opacity: 0.6;
    pointer-events: none;
}

@media (max-width: 720px) {
    .table-detail-page .live-table-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .table-detail-page .live-table-tools {
        justify-content: space-between;
    }

    .table-detail-page .live-search {
        min-width: 0;
        flex: 1;
    }

    .table-detail-page .live-pager {
        justify-content: center;
    }
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

.table-detail-page .sheet-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.table-detail-page .import-dropzone {
    position: relative;
    border: 1.5px dashed var(--line);
    border-radius: 16px;
    background: #fbfcfe;
    padding: 22px 18px;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.table-detail-page .import-dropzone:hover,
.table-detail-page .import-dropzone.dragover {
    border-color: var(--accent);
    background: var(--accent-ghost);
}

.table-detail-page .import-dropzone-empty {
    display: flex;
    align-items: center;
    gap: 14px;
    text-align: left;
}

.table-detail-page .import-dropzone-icon {
    font-size: 30px;
    color: var(--accent);
    flex-shrink: 0;
}

.table-detail-page .import-dropzone-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.table-detail-page .import-dropzone-text strong {
    color: var(--ink);
    font-size: 14px;
    line-height: 1.3;
}

.table-detail-page .import-dropzone-text span {
    color: var(--muted);
    font-size: 12.5px;
}

.table-detail-page .import-dropzone-formats {
    margin-left: auto;
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--muted);
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 5px 11px;
}

.table-detail-page .import-dropzone-filled {
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-detail-page .import-file-badge {
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--accent-strong);
    background: var(--accent-ghost);
    border: 1px solid var(--accent-soft);
    border-radius: 9px;
    padding: 6px 9px;
}

.table-detail-page .import-file-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.table-detail-page .import-file-meta strong {
    color: var(--ink);
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table-detail-page .import-file-meta span {
    color: var(--muted);
    font-size: 12px;
}

.table-detail-page .import-file-actions {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.table-detail-page .import-file-replace {
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    font-weight: 700;
    font-size: 12.5px;
    border-radius: 10px;
    padding: 7px 12px;
    cursor: pointer;
}

.table-detail-page .import-file-replace:hover {
    border-color: var(--accent);
    color: var(--accent-strong);
    background: var(--accent-ghost);
}

.table-detail-page .import-file-remove {
    width: 30px;
    height: 30px;
    border: 0;
    background: transparent;
    color: var(--muted);
    font-size: 20px;
    line-height: 1;
    border-radius: 8px;
    cursor: pointer;
}

.table-detail-page .import-file-remove:hover {
    background: var(--danger-soft, #fee2e2);
    color: var(--danger);
}

.table-detail-page .import-dropzone-error {
    margin-top: 12px;
    border-top: 1px solid var(--line);
    padding-top: 10px;
    color: var(--danger);
    font-size: 12.5px;
}

.table-detail-page .import-example-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--accent-strong);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 10px;
    padding: 8px 10px;
    margin-left: -10px;
}

.table-detail-page .import-example-link:hover {
    background: var(--accent-ghost);
}

.table-detail-page .import-stepper {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    padding: 12px 14px;
    background: #f8fafc;
    border: 1px solid var(--line);
    border-radius: 14px;
}

.table-detail-page .import-step-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
}

.table-detail-page .import-step-num {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    background: #e6ecf5;
    color: var(--muted);
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s ease, color 0.15s ease;
}

.table-detail-page .import-step-label {
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
}

.table-detail-page .import-step-item.active {
    color: var(--ink);
}

.table-detail-page .import-step-item.active .import-step-num {
    background: var(--ink);
    color: #fff;
}

.table-detail-page .import-step-item.done .import-step-num {
    background: #d1fae5;
    color: #047857;
}

.table-detail-page .import-step-connector {
    flex: 1;
    height: 2px;
    min-width: 18px;
    background: #e6ecf5;
    border-radius: 2px;
}

.table-detail-page .import-step-connector.done {
    background: #a7f3d0;
}

.table-detail-page .import-mapping-head {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin-bottom: 12px;
}

.table-detail-page .import-mapping-head strong {
    color: var(--ink);
    font-size: 15px;
}

.table-detail-page .import-file-summary {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 14px;
    margin-bottom: 12px;
}

.table-detail-page .import-mapping-scroll {
    overflow: auto;
    max-height: 330px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #fff;
}

.table-detail-page .import-mapping-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 560px;
}

.table-detail-page .import-mapping-table th,
.table-detail-page .import-mapping-table td {
    padding: 11px 13px;
    border-bottom: 1px solid #eef3f8;
    vertical-align: middle;
    text-align: left;
}

.table-detail-page .import-mapping-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
    font-size: 11.5px;
    color: #44536a;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 800;
}

.table-detail-page .import-mapping-table tbody tr:last-child td {
    border-bottom: 0;
}

.table-detail-page .import-mapping-table tbody tr.is-matched {
    background: #fbfdff;
}

.table-detail-page .import-mapping-table tbody tr.is-matched td:first-child {
    box-shadow: inset 3px 0 0 var(--accent);
}

.table-detail-page .import-mapping-col-num {
    width: 34px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}

.table-detail-page .import-mapping-filecol {
    display: flex;
    flex-direction: column;
    gap: 3px;
    align-items: flex-start;
}

.table-detail-page .import-mapping-filecol-name {
    font-weight: 700;
    font-size: 13.5px;
    color: var(--ink);
    font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
    word-break: break-all;
}

.table-detail-page .import-mapping-match-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 800;
    color: #047857;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 999px;
    padding: 3px 8px;
}

.table-detail-page .import-mapping-match-tag .material-symbols-outlined {
    font-size: 13px;
}

.table-detail-page .import-mapping-select {
    position: relative;
}

.table-detail-page .import-mapping-select select {
    width: 100%;
    min-width: 200px;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 10px;
    padding: 9px 30px 9px 11px;
    font-size: 13px;
    color: var(--ink);
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2360708a' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}

.table-detail-page .import-mapping-select select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.table-detail-page .import-mapping-select select:invalid,
.table-detail-page .import-mapping-select select:required {
    color: var(--ink);
}

.table-detail-page .import-mapping-check {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border-radius: 999px;
    background: #e6ecf5;
    pointer-events: none;
}

.table-detail-page .import-mapping-check.ok {
    background: #34d399;
}

.table-detail-page .import-mapping-sample {
    font-size: 12.5px;
    color: var(--muted);
    max-width: 220px;
}

.table-detail-page .import-mapping-sample code {
    font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
    font-size: 12px;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    padding: 3px 7px;
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}

.table-detail-page .import-mapping-empty {
    color: #b3bfd1;
}

.table-detail-page .import-mapping-alert {
    display: flex;
    gap: 9px;
    align-items: flex-start;
    border-radius: 12px;
    padding: 10px 13px;
    margin-top: 10px;
    font-size: 13px;
    line-height: 1.45;
}

.table-detail-page .import-mapping-alert .material-symbols-outlined {
    font-size: 18px;
    flex-shrink: 0;
    margin-top: 1px;
}

.table-detail-page .import-mapping-alert.alert-warn {
    color: #92400e;
    background: #fffbeb;
    border: 1px solid #fde68a;
}

.table-detail-page .import-mapping-alert.alert-info {
    color: #1e3a8a;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
}

.table-detail-page .import-mapping-unknown-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 800;
    color: #b45309;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 999px;
    padding: 3px 8px;
}

.table-detail-page .import-mapping-unknown-tag .material-symbols-outlined {
    font-size: 13px;
}

.table-detail-page .import-mapping-select-hint {
    display: block;
    margin-top: 5px;
    font-size: 11.5px;
    line-height: 1.35;
    color: #b45309;
}

.table-detail-page .import-result-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 14px;
    padding: 14px 16px;
    border: 1px solid var(--line);
    background: #fff;
}

.table-detail-page .import-result-banner.ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.table-detail-page .import-result-banner.has-errors {
    background: #fef2f2;
    border-color: #fecaca;
}

.table-detail-page .import-result-banner-icon {
    font-size: 26px;
    flex-shrink: 0;
}

.table-detail-page .import-result-banner.ok .import-result-banner-icon {
    color: #059669;
}

.table-detail-page .import-result-banner.has-errors .import-result-banner-icon {
    color: #dc2626;
}

.table-detail-page .import-result-banner-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.table-detail-page .import-result-banner-text strong {
    font-size: 14.5px;
    color: var(--ink);
}

.table-detail-page .import-result-banner-text span {
    font-size: 12.5px;
    color: var(--muted);
}

.table-detail-page .import-result-head {
    margin: 16px 0 10px;
}

.table-detail-page .import-result-head-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-weight: 800;
    font-size: 14px;
    color: var(--ink);
    margin-bottom: 3px;
}

.table-detail-page .import-result-head-title .material-symbols-outlined {
    font-size: 18px;
    color: #dc2626;
}

.table-detail-page .import-result-errors-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 260px;
    overflow: auto;
    padding-right: 2px;
}

.table-detail-page .import-result-error-card {
    display: flex;
    flex-direction: column;
    gap: 3px;
    border: 1px solid #fecaca;
    background: #fff;
    border-radius: 12px;
    padding: 10px 13px;
}

.table-detail-page .import-result-error-card.is-hidden {
    display: none;
}

.table-detail-page .import-result-error-row {
    font-size: 11.5px;
    font-weight: 800;
    color: #dc2626;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.table-detail-page .import-result-error-msg {
    font-size: 13px;
    color: var(--ink);
    line-height: 1.4;
}

.table-detail-page .import-result-more {
    margin-top: 10px;
    width: 100%;
    border: 1px dashed var(--line);
    background: #f8fafc;
    color: var(--accent-strong);
    font-weight: 700;
    font-size: 13px;
    border-radius: 12px;
    padding: 10px;
    cursor: pointer;
}

.table-detail-page .import-result-more:hover {
    background: var(--accent-ghost);
}

.table-detail-page .import-result-all-ok {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    padding: 12px 14px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    color: #15803d;
    font-size: 13.5px;
    font-weight: 700;
}

.table-detail-page .import-result-all-ok .material-symbols-outlined {
    font-size: 20px;
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
                    <?= Html::a('Export CSV', $exportCsvRoute, ['class' => 'btn-clean']) ?>
                    <?= Html::a('Print/PDF', $exportPrintRoute, ['class' => 'btn-clean', 'target' => '_blank']) ?>
                    <?= Html::a('Delete Table', $deleteRoute, [
                        'class' => 'btn-clean btn-danger-clean',
                        'data' => [
                            'confirm' => 'Hapus table ini? Physical table, metadata kolom, form, dan datatable terkait akan ikut dibersihkan.',
                            'method' => 'post',
                        ],
                    ]) ?>
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
                <span class="stat-label">Total Rows</span>
                <div class="stat-value"><?= $liveTableTotal ?></div>
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
                <button type="button" class="sheet-btn" id="import-file-btn">Import File</button>
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
                                        <tr class="sheet-row" data-row-index="<?= (int)$rowIndex ?>" data-row-state="saved" data-row-key="<?= Html::encode(json_encode($sheetRow['key'])) ?>" data-row-values="<?= Html::encode(json_encode($sheetRow['values'])) ?>">
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
                                    <td class="sheet-state saved" data-row-status>Tersimpan</td>
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
                    <div class="muted-inline" id="live-table-count"><?= Html::encode($displayedRowsText) ?></div>
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
                        <div class="live-table-bar" id="live-pagination">
                            <div class="live-table-tools">
                                <div class="live-search">
                                    <span class="material-symbols-outlined">search</span>
                                    <input type="text" id="live-search-input" placeholder="Cari data..." aria-label="Cari data di tabel">
                                </div>
                                <div class="live-page-size">
                                    <label for="live-page-size-select">Baris per halaman</label>
                                    <select id="live-page-size-select" aria-label="Baris per halaman">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                            <div class="live-pager" id="live-pager" <?php if ($liveTableTotal <= $liveTablePageSize): ?>style="display:none;"<?php endif; ?>>
                                <button type="button" class="live-page-btn" id="live-prev-btn" aria-label="Halaman sebelumnya"><span class="material-symbols-outlined">chevron_left</span></button>
                                <div class="live-pages" id="live-pages"></div>
                                <button type="button" class="live-page-btn" id="live-next-btn" aria-label="Halaman berikutnya"><span class="material-symbols-outlined">chevron_right</span></button>
                            </div>
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
                        <div class="meta-label">Total rows</div>
                        <div class="meta-value"><?= $liveTableTotal ?></div>
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

    <div class="sheet-modal" id="sheet-import-modal" aria-hidden="true">
        <div class="sheet-modal-card" role="dialog" aria-modal="true" aria-labelledby="sheet-import-title">
            <div class="sheet-modal-head">
                <div>
                    <div id="sheet-import-title" class="panel-title" style="margin-bottom: 6px;">Import dari File</div>
                    <div class="panel-subtitle" id="sheet-import-subtitle">Pilih file CSV / XLSX / XLS untuk diimpor ke tabel ini.</div>
                </div>
                <button type="button" class="sheet-btn" id="close-import-modal-btn">Tutup</button>
            </div>

            <div class="import-stepper" id="import-stepper" aria-label="Langkah impor">
                <div class="import-step-item" data-import-step="choose">
                    <span class="import-step-num">1</span>
                    <span class="import-step-label">Pilih File</span>
                </div>
                <span class="import-step-connector"></span>
                <div class="import-step-item" data-import-step="mapping">
                    <span class="import-step-num">2</span>
                    <span class="import-step-label">Petakan Kolom</span>
                </div>
                <span class="import-step-connector"></span>
                <div class="import-step-item" data-import-step="result">
                    <span class="import-step-num">3</span>
                    <span class="import-step-label">Hasil</span>
                </div>
            </div>

            <div id="import-step-choose">
                <div class="import-dropzone" id="import-dropzone" role="button" tabindex="0" aria-label="Pilih file untuk diimpor">
                    <input type="file" id="import-file-input" accept=".csv,.xlsx,.xls" hidden>
                    <div class="import-dropzone-empty" id="import-dropzone-empty">
                        <span class="material-symbols-outlined import-dropzone-icon">upload_file</span>
                        <div class="import-dropzone-text">
                            <strong>Pilih file untuk diimpor</strong>
                            <span>atau seret file ke area ini</span>
                        </div>
                        <span class="import-dropzone-formats">CSV &bull; XLSX &bull; XLS</span>
                    </div>
                    <div class="import-dropzone-filled" id="import-dropzone-filled" style="display:none;">
                        <span class="import-file-badge" id="import-file-badge">CSV</span>
                        <div class="import-file-meta">
                            <strong id="import-file-name"></strong>
                            <span id="import-file-size"></span>
                        </div>
                        <div class="import-file-actions">
                            <button type="button" class="import-file-replace" id="import-file-replace-btn">Ganti</button>
                            <button type="button" class="import-file-remove" id="import-file-remove-btn" aria-label="Hapus file">&times;</button>
                        </div>
                    </div>
                    <div class="import-dropzone-error" id="import-file-error" style="display:none;"></div>
                </div>
                <div class="sheet-note" id="import-file-note">Baris pertama dibaca sebagai header kolom.</div>
                <div class="sheet-toolbar" style="margin-top: 14px;">
                    <a href="<?= Url::to($importExampleRoute) ?>" class="import-example-link" id="import-example-link" download>
                        <span class="material-symbols-outlined" style="font-size:15px;">download</span>
                        Unduh Contoh File
                    </a>
                    <div class="sheet-toolbar-right">
                        <button type="button" class="sheet-btn primary" id="import-preview-btn" disabled>Lanjut</button>
                    </div>
                </div>
            </div>

            <div id="import-step-mapping" style="display:none;">
                <div class="import-mapping-head">
                    <div>
                        <strong>Petakan Kolom File ke Kolom Tabel</strong>
                        <div class="sheet-note">Cocokkan setiap kolom dari file Anda dengan kolom tujuan pada tabel.</div>
                    </div>
                </div>

                <div class="import-file-summary" id="import-file-summary">
                    <span class="import-file-badge" id="import-map-file-badge">CSV</span>
                    <div class="import-file-meta">
                        <strong id="import-map-file-name"></strong>
                        <span id="import-map-file-meta"></span>
                    </div>
                </div>

                <div class="import-mapping-scroll" id="import-mapping-table-wrap"></div>

                <div id="import-mapping-warnings"></div>

                <div class="sheet-toolbar" style="margin-top: 16px;">
                    <div class="sheet-note" id="import-mapping-hint">Kolom bertanda <strong style="color:var(--accent-strong);">*</strong> wajib dipetakan.</div>
                    <div class="sheet-toolbar-right">
                        <button type="button" class="sheet-btn" id="import-back-preview-btn">Kembali</button>
                        <button type="button" class="sheet-btn primary" id="import-execute-btn">Impor Sekarang</button>
                    </div>
                </div>
            </div>

            <div id="import-step-result" style="display:none;">
                <div id="import-result-summary"></div>
                <div id="import-result-errors"></div>
                <div class="sheet-toolbar" style="margin-top: 16px;">
                    <div class="sheet-note">Periksa hasil impor sebelum menutup modal.</div>
                    <div class="sheet-toolbar-right">
                        <button type="button" class="sheet-btn primary" id="import-done-btn">Selesai</button>
                    </div>
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
    const importMetaUrl = '<?= Url::to($importMetaRoute) ?>';
    const importPreviewUrl = '<?= Url::to($importPreviewRoute) ?>';
    const importExecuteUrl = '<?= Url::to($importExecuteRoute) ?>';
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
    const importModal = document.getElementById('sheet-import-modal');
    const closeImportModalBtn = document.getElementById('close-import-modal-btn');
    const importFileBtn = document.getElementById('import-file-btn');
    const importFileInput = document.getElementById('import-file-input');
    const importDropzone = document.getElementById('import-dropzone');
    const importDropzoneEmpty = document.getElementById('import-dropzone-empty');
    const importDropzoneFilled = document.getElementById('import-dropzone-filled');
    const importFileBadge = document.getElementById('import-file-badge');
    const importFileName = document.getElementById('import-file-name');
    const importFileSize = document.getElementById('import-file-size');
    const importFileError = document.getElementById('import-file-error');
    const importFileReplaceBtn = document.getElementById('import-file-replace-btn');
    const importFileRemoveBtn = document.getElementById('import-file-remove-btn');
    const importPreviewBtn = document.getElementById('import-preview-btn');
    const importBackPreviewBtn = document.getElementById('import-back-preview-btn');
    const importExecuteBtn = document.getElementById('import-execute-btn');
    const importDoneBtn = document.getElementById('import-done-btn');
    const importFileNote = document.getElementById('import-file-note');
    const importStepChoose = document.getElementById('import-step-choose');
    const importStepMapping = document.getElementById('import-step-mapping');
    const importStepResult = document.getElementById('import-step-result');
    const importMappingTableWrap = document.getElementById('import-mapping-table-wrap');
    const importMappingWarnings = document.getElementById('import-mapping-warnings');
    const importResultSummary = document.getElementById('import-result-summary');
    const importResultErrors = document.getElementById('import-result-errors');
    const csrfToken = '<?= Yii::$app->request->getCsrfToken() ?>';
    let draftRowCounter = 0;
    let formRowsState = <?= \yii\helpers\Json::encode($liveTableRows) ?>;
    const tableName = '<?= Html::encode($model->name) ?>';
    const liveTableUrl = '<?= Url::to($liveTableRoute) ?>';
    const liveTableCount = document.getElementById('live-table-count');
    const livePagination = document.getElementById('live-pagination');
const livePager = document.getElementById('live-pager');
    const liveSearchInput = document.getElementById('live-search-input');
    const livePageSizeSelect = document.getElementById('live-page-size-select');
    const livePagesEl = document.getElementById('live-pages');
    const livePrevBtn = document.getElementById('live-prev-btn');
    const liveNextBtn = document.getElementById('live-next-btn');
    let livePage = 1;
    let livePageSize = <?= (int)$liveTablePageSize ?>;
    let liveTotal = <?= (int)$liveTableTotal ?>;
    let livePages = Math.max(1, Math.ceil(liveTotal / livePageSize));
    let liveBase = 0;
    let liveSearchTerm = '';
    let liveLoading = false;
    let liveSearchTimer = null;

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
        let html = '<td>' + (liveBase + rowIndex + 1) + '</td>';
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

    function updateLiveCountText() {
        if (!liveTableCount) {
            return;
        }
        if (liveTotal === 0) {
            liveTableCount.textContent = 'Table kosong, belum ada data.';
            return;
        }
        const first = liveBase + 1;
        const last = Math.min(liveBase + formRowsState.length, liveTotal);
        liveTableCount.textContent = 'Menampilkan ' + first + '–' + last + ' dari ' + liveTotal + ' baris';
    }

    function renderLivePagination() {
        if (livePagesEl) {
            const windowSize = 2;
            let html = '';
            const start = Math.max(1, livePage - windowSize);
            const end = Math.min(livePages, livePage + windowSize);
            if (start > 1) {
                html += '<button type="button" class="live-page-num" data-live-page="1">1</button>';
                if (start > 2) {
                    html += '<span class="live-ellipsis">…</span>';
                }
            }
            for (let i = start; i <= end; i++) {
                html += '<button type="button" class="live-page-num' + (i === livePage ? ' active' : '') + '" data-live-page="' + i + '">' + i + '</button>';
            }
            if (end < livePages) {
                if (end < livePages - 1) {
                    html += '<span class="live-ellipsis">…</span>';
                }
                html += '<button type="button" class="live-page-num" data-live-page="' + livePages + '">' + livePages + '</button>';
            }
            livePagesEl.innerHTML = html;
            livePagesEl.querySelectorAll('[data-live-page]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    loadLivePage(parseInt(btn.getAttribute('data-live-page'), 10));
                });
            });
        }
        if (livePrevBtn) {
            livePrevBtn.disabled = livePage <= 1;
        }
        if (liveNextBtn) {
            liveNextBtn.disabled = livePage >= livePages;
        }
        if (livePager) {
            livePager.style.display = (livePages > 1) ? 'flex' : 'none';
        }
    }

    function loadLivePage(page) {
        if (liveLoading) {
            return;
        }
        const targetPage = Math.max(1, Math.min(page, livePages));
        liveLoading = true;
        if (livePagination) {
            livePagination.classList.add('is-loading');
        }
        const params = new URLSearchParams();
        params.set('page', String(targetPage));
        params.set('page_size', String(livePageSize));
        if (liveSearchTerm) {
            params.set('q', liveSearchTerm);
        }
        fetch(liveTableUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (!data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Gagal memuat data');
            }
            livePage = data.page || targetPage;
            livePageSize = data.page_size || livePageSize;
            liveTotal = data.total !== undefined ? data.total : liveTotal;
            livePages = data.pages > 0 ? data.pages : 1;
            liveBase = (livePage - 1) * livePageSize;
            formRowsState = data.rows || [];
            renderFormTableFromState();
            updateLiveCountText();
            renderLivePagination();
        }).catch(function (error) {
            alert(error.message || 'Gagal memuat data');
        }).finally(function () {
            liveLoading = false;
            if (livePagination) {
                livePagination.classList.remove('is-loading');
            }
        });
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

    let importMetaData = null;
    let importPreviewData = null;
    let importSelectedFile = null;

    function importSwitchStep(stepName) {
        importStepChoose.style.display = stepName === 'choose' ? 'block' : 'none';
        importStepMapping.style.display = stepName === 'mapping' ? 'block' : 'none';
        importStepResult.style.display = stepName === 'result' ? 'block' : 'none';
        const order = ['choose', 'mapping', 'result'];
        const currentIdx = order.indexOf(stepName);
        const stepper = document.getElementById('import-stepper');
        if (!stepper) {
            return;
        }
        stepper.querySelectorAll('[data-import-step]').forEach(function (item) {
            const idx = order.indexOf(item.getAttribute('data-import-step'));
            item.classList.toggle('active', idx === currentIdx);
            item.classList.toggle('done', idx < currentIdx);
        });
        stepper.querySelectorAll('.import-step-connector').forEach(function (conn, i) {
            conn.classList.toggle('done', i < currentIdx);
        });
    }

function showImportModal() {
        importModal.classList.add('open');
        importModal.setAttribute('aria-hidden', 'false');
        importSwitchStep('choose');
        importSelectedFile = null;
        if (importFileInput) {
            importFileInput.value = '';
        }
        if (importDropzoneEmpty) {
            importDropzoneEmpty.style.display = 'flex';
        }
        if (importDropzoneFilled) {
            importDropzoneFilled.style.display = 'none';
        }
        if (importFileError) {
            importFileError.style.display = 'none';
        }
        if (importPreviewBtn) {
            importPreviewBtn.disabled = true;
        }
        if (importFileNote) {
            importFileNote.textContent = 'Baris pertama dibaca sebagai header kolom.';
        }
        if (!importMetaData) {
            fetch(importMetaUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (data && data.success) {
                    importMetaData = data;
                    if (data.max_file_size_display && importFileNote) {
                        importFileNote.textContent = 'Baris pertama dibaca sebagai header. Maksimal ukuran file: ' + data.max_file_size_display;
                    }
                }
            }).catch(function () {});
        }
    }

    function hideImportModal() {
        importModal.classList.remove('open');
        importModal.setAttribute('aria-hidden', 'true');
    }

    function importFormatBytes(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1048576) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function importFileBadgeForName(name) {
        const ext = String(name).split('.').pop().toUpperCase();
        return ['CSV', 'XLSX', 'XLS'].indexOf(ext) >= 0 ? ext : 'FILE';
    }

    function validateImportFile(file) {
        const ext = String(file.name).split('.').pop().toLowerCase();
        if (['csv', 'xlsx', 'xls'].indexOf(ext) < 0) {
            return { ok: false, error: 'Format file tidak didukung. Gunakan file CSV, XLSX, atau XLS.' };
        }
        if (importMetaData && importMetaData.max_file_size && file.size > importMetaData.max_file_size) {
            return { ok: false, error: 'Ukuran file melebihi batas maksimal ' + importMetaData.max_file_size_display + '.' };
        }
        return { ok: true, error: '' };
    }

    function renderImportSelectedFile(file) {
        if (importFileError) {
            importFileError.style.display = 'none';
            importFileError.textContent = '';
        }
        if (importDropzone) {
            importDropzone.classList.remove('dragover');
        }
        if (!file) {
            importSelectedFile = null;
            if (importDropzoneEmpty) {
                importDropzoneEmpty.style.display = 'flex';
            }
            if (importDropzoneFilled) {
                importDropzoneFilled.style.display = 'none';
            }
            if (importPreviewBtn) {
                importPreviewBtn.disabled = true;
            }
            return;
        }

        const validation = validateImportFile(file);
        if (!validation.ok) {
            importSelectedFile = null;
            if (importDropzoneEmpty) {
                importDropzoneEmpty.style.display = 'flex';
            }
            if (importDropzoneFilled) {
                importDropzoneFilled.style.display = 'none';
            }
            if (importFileError) {
                importFileError.textContent = validation.error;
                importFileError.style.display = 'block';
            }
            if (importPreviewBtn) {
                importPreviewBtn.disabled = true;
            }
            return;
        }

        importSelectedFile = file;
        if (importFileBadge) {
            importFileBadge.textContent = importFileBadgeForName(file.name);
        }
        if (importFileName) {
            importFileName.textContent = file.name;
        }
        if (importFileSize) {
            importFileSize.textContent = importFormatBytes(file.size);
        }
        if (importDropzoneEmpty) {
            importDropzoneEmpty.style.display = 'none';
        }
        if (importDropzoneFilled) {
            importDropzoneFilled.style.display = 'flex';
        }
        if (importPreviewBtn) {
            importPreviewBtn.disabled = false;
        }
    }

    function importColumnLabel(column) {
        const requiredMark = column.required ? ' *' : '';
        return escapeHtml(column.label) + ' (' + escapeHtml(column.name) + ')' + requiredMark;
    }

    function renderImportMapping() {
        const data = importPreviewData;
        if (!data) {
            return;
        }
        const columns = data.columns || [];
        const headers = data.headers || [];
        const mapping = data.mapping || {};
        const previewRow = (data.preview_rows && data.preview_rows.length) ? data.preview_rows[0] : null;
        const knownNames = columns.map(function (column) {
            return String(column.name).trim().toLowerCase();
        });

        const options = ['<option value="">- Pilih kolom tujuan -</option>'].concat(columns.map(function (column) {
            return '<option value="' + escapeHtml(column.name) + '">' + importColumnLabel(column) + '</option>';
        })).join('');

        let html = '<table class="import-mapping-table">'
            + '<thead><tr><th class="import-mapping-col-num">#</th><th>Kolom di File</th><th>Kolom Tujuan</th><th>Contoh Data</th></tr></thead><tbody>';
        headers.forEach(function (header, index) {
            const sampleCell = previewRow ? String(previewRow.values[index] !== undefined ? previewRow.values[index] : '') : '';
            const matchedName = mapping[index] || '';
            const isAuto = matchedName !== '' && String(header).trim().toLowerCase() === String(matchedName).toLowerCase();
            const isUnknown = knownNames.indexOf(String(header).trim().toLowerCase()) < 0;
            html += '<tr class="import-mapping-row' + (isAuto ? ' is-matched' : '') + '">'
                + '<td class="import-mapping-col-num">' + (index + 1) + '</td>'
                + '<td><div class="import-mapping-filecol"><span class="import-mapping-filecol-name">' + escapeHtml(header) + '</span>'
                + (isAuto ? '<span class="import-mapping-match-tag"><span class="material-symbols-outlined">check_circle</span>Cocok</span>' : '')
                + (isUnknown && !isAuto ? '<span class="import-mapping-unknown-tag"><span class="material-symbols-outlined">help_outline</span>Tidak dikenali</span>' : '')
                + '</div></td>'
                + '<td><div class="import-mapping-select">'
                + '<select data-import-header-index="' + index + '">' + options + '</select>'
                + '<span class="import-mapping-check' + (isAuto ? ' ok' : '') + '"></span>'
                + (isUnknown ? '<span class="import-mapping-select-hint">Pilih kolom tujuan yang sesuai, atau biarkan kosong untuk mengabaikan kolom ini.</span>' : '')
                + '</div></td>'
                + '<td class="import-mapping-sample">' + (sampleCell !== '' ? '<code>' + escapeHtml(sampleCell) + '</code>' : '<span class="import-mapping-empty">-</span>') + '</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        if (importMappingTableWrap) {
            importMappingTableWrap.innerHTML = html;
        }

        Object.keys(mapping).forEach(function (index) {
            const select = importMappingTableWrap.querySelector('[data-import-header-index="' + index + '"]');
            if (select && mapping[index]) {
                select.value = mapping[index];
            }
        });

        const fileBadge = document.getElementById('import-map-file-badge');
        const fileNameEl = document.getElementById('import-map-file-name');
        const fileMetaEl = document.getElementById('import-map-file-meta');
        if (importSelectedFile && (fileBadge || fileNameEl || fileMetaEl)) {
            if (fileBadge) {
                fileBadge.textContent = importFileBadgeForName(importSelectedFile.name);
            }
            if (fileNameEl) {
                fileNameEl.textContent = importSelectedFile.name;
            }
            if (fileMetaEl) {
                const parts = [];
                if (data.file && data.file.sheet) {
                    parts.push('Sheet: ' + data.file.sheet);
                }
                if (data.file && data.file.delimiter) {
                    parts.push('Delimiter: ' + data.file.delimiter);
                }
                if (data.file && data.file.total_rows !== undefined) {
                    parts.push(data.file.total_rows + ' baris data');
                }
                parts.push(importFormatBytes(importSelectedFile.size));
                fileMetaEl.textContent = parts.join(' • ');
            }
        }

        const warnings = [];
        if (data.missing_required && data.missing_required.length) {
            warnings.push('<div class="import-mapping-alert alert-warn"><span class="material-symbols-outlined">warning</span><div><strong>Kolom wajib belum dipetakan:</strong> ' + data.missing_required.map(escapeHtml).join(', ') + '.</div></div>');
        }
        const previewErrRows = (data.preview_rows || []).filter(function (row) {
            return row.errors && row.errors.length;
        });
        if (previewErrRows.length) {
            const firstRow = previewErrRows[0];
            const firstRowNo = (firstRow.index + 2);
            warnings.push('<div class="import-mapping-alert alert-warn"><span class="material-symbols-outlined">warning</span><div><strong>Ada nilai yang bermasalah di file Anda.</strong> Contoh: baris ' + firstRowNo + ': ' + escapeHtml(firstRow.errors[0]) + ' Baris yang bermasalah tidak akan ikut diimpor.</div></div>');
        }
        if (data.file && data.file.total_rows === 0) {
            warnings.push('<div class="import-mapping-alert alert-info"><span class="material-symbols-outlined">info</span><div>File tidak mengandung baris data untuk diimpor.</div></div>');
        }
        if (importMappingWarnings) {
            importMappingWarnings.innerHTML = warnings.join(' ');
        }
    }

    function importCollectMapping() {
        const mapping = [];
        if (importMappingTableWrap) {
            const selects = importMappingTableWrap.querySelectorAll('[data-import-header-index]');
            selects.forEach(function (select) {
                mapping[parseInt(select.getAttribute('data-import-header-index'), 10)] = select.value;
            });
        }
        return mapping;
    }

    function importUploadForm(extraFields) {
        const formData = new FormData();
        formData.append('_csrf', csrfToken);
        formData.append('file', importSelectedFile, importSelectedFile.name);
        Object.keys(extraFields || {}).forEach(function (key) {
            formData.append(key, extraFields[key]);
        });
        return formData;
    }

    function importRenderResult(data) {
        const inserted = data.inserted || 0;
        const failed = data.failed || 0;
        const total = data.total !== undefined ? data.total : (inserted + failed);
        const errors = data.errors || [];
        const truncated = !!data.truncated_errors;
        const hasErrors = failed > 0 || errors.length > 0;

        let summary = '<div class="import-result-banner' + (hasErrors ? ' has-errors' : ' ok') + '">'
            + '<span class="import-result-banner-icon material-symbols-outlined">' + (hasErrors ? 'error' : 'task_alt') + '</span>'
            + '<div class="import-result-banner-text">'
            + '<strong>' + (hasErrors ? 'Impor selesai dengan ' + failed + ' baris gagal.' : 'Semua ' + inserted + ' baris berhasil diimpor.') + '</strong>'
            + '<span>' + inserted + ' baris berhasil &middot; ' + failed + ' baris gagal &middot; ' + total + ' baris diproses</span>'
            + '</div></div>';
        if (data.unmapped_headers && data.unmapped_headers.length) {
            summary += '<div class="import-mapping-alert alert-info" style="margin-top:10px;"><span class="material-symbols-outlined">info</span><div><strong>Kolom berikut tidak ikut diimpor</strong> (tidak dipetakan ke kolom tabel): ' + data.unmapped_headers.map(escapeHtml).join(', ') + '.</div></div>';
        }
        if (importResultSummary) {
            importResultSummary.innerHTML = summary;
        }

        let html = '';
        if (errors.length) {
            html += '<div class="import-result-head">'
                + '<div class="import-result-head-title"><span class="material-symbols-outlined">report</span> Baris yang perlu diperbaiki</div>'
                + '<div class="sheet-note">Baris berikut tidak ikut disimpan. Perbaiki nilainya di file lalu impor ulang, atau isi langsung melalui layar tabel.</div>'
                + '</div>';
            html += '<div class="import-result-errors-list" id="import-result-errors-list">';
            errors.forEach(function (error, idx) {
                html += '<div class="import-result-error-card' + (idx >= 8 ? ' is-hidden' : '') + '">'
                    + '<span class="import-result-error-row">Baris ' + escapeHtml(error.row) + '</span>'
                    + '<span class="import-result-error-msg">' + escapeHtml(error.message) + '</span>'
                    + '</div>';
            });
            html += '</div>';
            if (errors.length > 8) {
                html += '<button type="button" class="import-result-more" id="import-result-more-btn">Tampilkan semua kesalahan (' + errors.length + (truncated ? '+' : '') + ')</button>';
            }
            if (truncated) {
                html += '<div class="sheet-note" style="margin-top:8px;">Hanya 200 kesalahan pertama yang ditampilkan.</div>';
            }
        } else {
            html = '<div class="import-result-all-ok"><span class="material-symbols-outlined">check_circle</span> Tidak ada baris yang gagal.</div>';
        }
        if (importResultErrors) {
            importResultErrors.innerHTML = html;
        }

        const moreBtn = document.getElementById('import-result-more-btn');
        if (moreBtn) {
            moreBtn.addEventListener('click', function () {
                if (importResultErrors) {
                    importResultErrors.querySelectorAll('.import-result-error-card.is-hidden').forEach(function (card) {
                        card.classList.remove('is-hidden');
                    });
                }
                moreBtn.remove();
            });
        }
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
    renderLivePagination();

    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', function () {
            clearTimeout(liveSearchTimer);
            liveSearchTimer = setTimeout(function () {
                liveSearchTerm = liveSearchInput.value.trim();
                livePage = 1;
                liveBase = 0;
                loadLivePage(1);
            }, 350);
        });
    }
    if (livePageSizeSelect) {
        livePageSizeSelect.addEventListener('change', function () {
            const newSize = parseInt(livePageSizeSelect.value, 10) || 10;
            livePageSize = newSize;
            livePage = 1;
            liveBase = 0;
            loadLivePage(1);
        });
    }
    if (livePrevBtn) {
        livePrevBtn.addEventListener('click', function () {
            if (livePage > 1) {
                loadLivePage(livePage - 1);
            }
        });
    }
    if (liveNextBtn) {
        liveNextBtn.addEventListener('click', function () {
            if (livePage < livePages) {
                loadLivePage(livePage + 1);
            }
        });
    }

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

    if (importFileBtn) {
        importFileBtn.addEventListener('click', showImportModal);
    }
    if (closeImportModalBtn) {
        closeImportModalBtn.addEventListener('click', hideImportModal);
    }
    if (importModal) {
        importModal.addEventListener('click', function (event) {
            if (event.target === importModal) {
                hideImportModal();
            }
        });
    }
    if (importMappingTableWrap) {
        importMappingTableWrap.addEventListener('change', function (event) {
            const select = event.target;
            if (!select || !select.matches('select[data-import-header-index]')) {
                return;
            }
            const row = select.closest('.import-mapping-row');
            if (!row) {
                return;
            }
            const index = parseInt(select.getAttribute('data-import-header-index'), 10);
            const header = (importPreviewData.headers && importPreviewData.headers[index]) ? importPreviewData.headers[index] : '';
            const value = select.value || '';
            const isAuto = value !== '' && String(header).trim().toLowerCase() === String(value).toLowerCase();
            const knownNames = (importPreviewData.columns || []).map(function (column) {
                return String(column.name).trim().toLowerCase();
            });
            const isUnknown = knownNames.indexOf(String(header).trim().toLowerCase()) < 0;
            row.classList.toggle('is-matched', isAuto);
            const check = row.querySelector('.import-mapping-check');
            if (check) {
                check.classList.toggle('ok', isAuto);
            }
            const tag = row.querySelector('.import-mapping-match-tag');
            if (isAuto && !tag) {
                const cell = row.querySelector('.import-mapping-filecol');
                if (cell) {
                    const span = document.createElement('span');
                    span.className = 'import-mapping-match-tag';
                    span.innerHTML = '<span class="material-symbols-outlined">check_circle</span>Cocok';
                    cell.appendChild(span);
                }
            } else if (!isAuto && tag) {
                tag.remove();
            }
            const unknownTag = row.querySelector('.import-mapping-unknown-tag');
            if (isUnknown && !isAuto && !unknownTag) {
                const cell = row.querySelector('.import-mapping-filecol');
                if (cell) {
                    const span = document.createElement('span');
                    span.className = 'import-mapping-unknown-tag';
                    span.innerHTML = '<span class="material-symbols-outlined">help_outline</span>Tidak dikenali';
                    cell.appendChild(span);
                }
            } else if ((!isUnknown || isAuto) && unknownTag) {
                unknownTag.remove();
            }
        });
    }
    if (importFileInput) {
        importFileInput.addEventListener('change', function () {
            const file = importFileInput.files && importFileInput.files.length ? importFileInput.files[0] : null;
            renderImportSelectedFile(file);
        });
    }
    if (importDropzone) {
        importDropzone.addEventListener('click', function (event) {
            if (event.target === importFileReplaceBtn || event.target === importFileRemoveBtn || importFileReplaceBtn.contains(event.target) || importFileRemoveBtn.contains(event.target)) {
                return;
            }
            importFileInput.click();
        });
        importDropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                importFileInput.click();
            }
        });
        ['dragover', 'dragenter'].forEach(function (eventName) {
            importDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                importDropzone.classList.add('dragover');
            });
        });
        importDropzone.addEventListener('dragleave', function () {
            importDropzone.classList.remove('dragover');
        });
        importDropzone.addEventListener('drop', function (event) {
            event.preventDefault();
            importDropzone.classList.remove('dragover');
            const files = event.dataTransfer && event.dataTransfer.files;
            if (files && files.length) {
                renderImportSelectedFile(files[0]);
            }
        });
    }
    if (importFileReplaceBtn) {
        importFileReplaceBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            importFileInput.click();
        });
    }
    if (importFileRemoveBtn) {
        importFileRemoveBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            importFileInput.value = '';
            renderImportSelectedFile(null);
        });
    }
    if (importPreviewBtn) {
        importPreviewBtn.addEventListener('click', function () {
            if (!importSelectedFile) {
                alert('Pilih file terlebih dahulu.');
                return;
            }
            if (importMetaData && importMetaData.max_file_size && importSelectedFile.size > importMetaData.max_file_size) {
                alert('Ukuran file melebihi batas maksimal ' + importMetaData.max_file_size_display + '.');
                return;
            }
            importPreviewBtn.disabled = true;
            importPreviewBtn.textContent = 'Memeriksa...';
            fetch(importPreviewUrl, {
                method: 'POST',
                body: importUploadForm({}),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Gagal membaca file');
                }
                importPreviewData = data;
                renderImportMapping();
                importSwitchStep('mapping');
            }).catch(function (error) {
                alert(error.message || 'Gagal membaca file');
            }).finally(function () {
                importPreviewBtn.disabled = false;
                importPreviewBtn.textContent = 'Lanjut';
            });
        });
    }
    if (importBackPreviewBtn) {
        importBackPreviewBtn.addEventListener('click', function () {
            importSwitchStep('choose');
        });
    }
    if (importExecuteBtn) {
        importExecuteBtn.addEventListener('click', function () {
            if (!importSelectedFile) {
                alert('Pilih file terlebih dahulu.');
                return;
            }
            const mapping = importCollectMapping();
            const unmapped = [];
            (importPreviewData.headers || []).forEach(function (header, index) {
                if (!mapping[index]) {
                    unmapped.push(header);
                }
            });
            const required = (importPreviewData && importPreviewData.missing_required) || [];
            if (required.length) {
                alert('Kolom wajib belum dipetakan: ' + required.join(', ') + '.');
                return;
            }
            if (unmapped.length) {
                if (!window.confirm('Kolom berikut tidak akan diimpor: ' + unmapped.join(', ') + '. Lanjutkan?')) {
                    return;
                }
            }
            importExecuteBtn.disabled = true;
            importExecuteBtn.textContent = 'Mengimpor...';
            fetch(importExecuteUrl, {
                method: 'POST',
                body: importUploadForm({ mapping: JSON.stringify(mapping) }),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Gagal impor');
                }
                importRenderResult(data);
                importSwitchStep('result');
            }).catch(function (error) {
                alert(error.message || 'Gagal impor data');
            }).finally(function () {
                importExecuteBtn.disabled = false;
                importExecuteBtn.textContent = 'Impor Sekarang';
            });
        });
    }
    if (importDoneBtn) {
        importDoneBtn.addEventListener('click', function () {
            hideImportModal();
            window.location.reload();
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
