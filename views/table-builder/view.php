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
if ($fkDebugEnabled) {
    $indexRoute['fk_debug'] = 1;
    $updateRoute['fk_debug'] = 1;
    $viewRoute['fk_debug'] = 1;
    $executeRoute['fk_debug'] = 1;
    $previewSqlRoute['fk_debug'] = 1;
}
?>

<style>
.table-detail-page {
    --ink: #142033;
    --muted: #60708a;
    --line: #d9e2ef;
    --panel: #ffffff;
    --panel-soft: #f6f8fc;
    --accent: #1d4ed8;
    --accent-soft: #dbeafe;
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
        radial-gradient(circle at top left, rgba(29, 78, 216, 0.08), transparent 28%),
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
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: var(--accent);
    border: 1px solid #bfdbfe;
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
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    color: #fff;
    border-color: #1d4ed8;
}

.table-detail-page .btn-primary-clean:hover {
    border-color: #1d4ed8;
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
    background: #eff6ff;
    color: #1d4ed8;
}

.table-detail-page .flag-badge {
    background: #eef2ff;
    color: #4338ca;
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
                                        <?php foreach ($columns as $col): ?>
                                            <th><?= Html::encode($col->name) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableData as $rowIndex => $row): ?>
                                        <tr>
                                            <td><?= $rowIndex + 1 ?></td>
                                            <?php foreach ($columns as $col): ?>
                                                <td>
                                                    <?php
                                                    $value = $row[$col->name] ?? null;
                                                    if ($value === null) {
                                                        echo '<span class="null-value">NULL</span>';
                                                    } elseif (($col->type === 'BOOLEAN' || $col->type === 'TINYINT') && ($value === 0 || $value === 1 || $value === '0' || $value === '1')) {
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
});
</script>
