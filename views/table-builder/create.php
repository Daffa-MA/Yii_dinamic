<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable $model */
/** @var array $savedColumns */
/** @var string|null $pageTitle */
/** @var string|null $pageHeading */
/** @var string|null $heroText */
/** @var string|null $submitLabel */

use app\models\DbTableColumn;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$pageTitle = $pageTitle ?? 'Create Database Table';
$pageHeading = $pageHeading ?? 'Create Database Table';
$heroText = $heroText ?? 'Define the table metadata and column structure here. This page saves the definition into the application database first. The physical SQL table is created afterwards from the saved metadata.';
$submitLabel = $submitLabel ?? 'Save Table Definition';
$executionStatusLabel = $model->is_created ? 'Created' : 'Pending';
$executionStatusNote = $model->is_created
    ? 'Physical SQL table already exists in the database'
    : 'Physical SQL table is created after save';

$this->title = $pageTitle;
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
?>

<style>
.table-create-page {
    --ink: #142033;
    --muted: #60708a;
    --line: #d9e2ef;
    --panel: #ffffff;
    --accent: #1d4ed8;
    --shadow: 0 20px 55px rgba(20, 32, 51, 0.08);
    color: var(--ink);
}

.table-create-page .page-shell {
    display: grid;
    gap: 24px;
}

.table-create-page .hero,
.table-create-page .panel {
    background: var(--panel);
    border: 1px solid #e4ebf3;
    border-radius: 24px;
    box-shadow: var(--shadow);
}

.table-create-page .hero {
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(29, 78, 216, 0.08), transparent 28%),
        linear-gradient(180deg, #ffffff, #f8fbff);
}

.table-create-page .hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
}

.table-create-page .hero-title {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.table-create-page .hero-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: var(--accent);
    border: 1px solid #bfdbfe;
}

.table-create-page h1 {
    margin: 0 0 8px;
    font-size: 34px;
    line-height: 1.08;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.table-create-page .hero-text,
.table-create-page .panel-subtitle,
.table-create-page .field-note,
.table-create-page .footer-note,
.table-create-page .columns-meta,
.table-create-page .empty-text {
    color: var(--muted);
    font-size: 14px;
}

.table-create-page .hero-actions,
.table-create-page .columns-toolbar,
.table-create-page .footer-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.table-create-page .hero-actions {
    justify-content: flex-end;
}

.table-create-page .btn-clean {
    border-radius: 12px;
    padding: 11px 16px;
    border: 1px solid var(--line);
    background: #fff;
    color: var(--ink);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.table-create-page .btn-clean:hover {
    border-color: #bfd0e6;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(20, 32, 51, 0.08);
}

.table-create-page .btn-primary-clean {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
    border-color: #1d4ed8;
    color: #fff;
}

.table-create-page .btn-primary-clean:hover {
    color: #fff;
}

.table-create-page .hero-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.table-create-page .stat-card {
    padding: 18px;
    border-radius: 18px;
    border: 1px solid #e7edf5;
    background: rgba(255, 255, 255, 0.84);
}

.table-create-page .stat-label {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted);
    font-weight: 700;
    margin-bottom: 10px;
}

.table-create-page .stat-value {
    font-size: 26px;
    line-height: 1;
    font-weight: 800;
    margin-bottom: 6px;
}

.table-create-page .stat-note {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

.table-create-page .workspace {
    display: grid;
    grid-template-columns: minmax(0, 1.9fr) minmax(320px, 0.95fr);
    gap: 24px;
}

.table-create-page .stack {
    display: grid;
    gap: 24px;
}

.table-create-page .panel-header {
    padding: 20px 24px;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    background: linear-gradient(180deg, #ffffff, #f9fbfd);
}

.table-create-page .panel-title {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 760;
    letter-spacing: -0.02em;
}

.table-create-page .panel-body {
    padding: 24px;
}

.table-create-page .info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px 16px;
}

.table-create-page .field-group,
.table-create-page .property-grid,
.table-create-page .checkbox-grid {
    display: grid;
    gap: 10px;
}

.table-create-page .field-span-3 {
    grid-column: span 3;
}

.table-create-page .field-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #344256;
}

.table-create-page .field-input,
.table-create-page .field-select,
.table-create-page textarea.field-input {
    width: 100%;
    border: 1px solid #d8e2ee;
    border-radius: 14px;
    padding: 12px 14px;
    font-size: 14px;
    color: var(--ink);
    background: #fff;
    transition: all 0.2s ease;
}

.table-create-page .field-input:focus,
.table-create-page .field-select:focus,
.table-create-page textarea.field-input:focus {
    outline: none;
    border-color: #90b6f7;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
}

.table-create-page .columns-list,
.table-create-page .meta-list {
    display: grid;
    gap: 12px;
}

.table-create-page .empty-columns,
.table-create-page .placeholder-panel {
    border: 1px dashed #ccd8e8;
    background: #f9fbfe;
    border-radius: 18px;
    padding: 32px 24px;
    text-align: center;
}

.table-create-page .empty-columns .material-symbols-outlined,
.table-create-page .placeholder-panel .material-symbols-outlined {
    font-size: 40px;
    color: #8ea0b8;
    margin-bottom: 10px;
}

.table-create-page .empty-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px;
}

.table-create-page .column-item {
    display: grid;
    gap: 14px;
    border: 1px solid #dfe8f2;
    background: #fff;
    border-radius: 18px;
    padding: 16px 18px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.table-create-page .column-item:hover {
    border-color: #b8cce7;
    box-shadow: 0 10px 24px rgba(20, 32, 51, 0.06);
}

.table-create-page .column-item.selected {
    border-color: #90b6f7;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    background: #fbfdff;
}

.table-create-page .column-item.sortable-ghost {
    opacity: 0.45;
}

.table-create-page .column-top,
.table-create-page .column-main,
.table-create-page .column-title,
.table-create-page .column-actions,
.table-create-page .column-summary {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.table-create-page .column-top {
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
}

.table-create-page .column-main {
    gap: 12px;
    align-items: flex-start;
    min-width: 0;
}

.table-create-page .column-title {
    align-items: center;
}

.table-create-page .drag-handle {
    color: #8ea0b8;
    cursor: grab;
    user-select: none;
    margin-top: 2px;
}

.table-create-page .drag-handle:active {
    cursor: grabbing;
}

.table-create-page .column-label {
    font-size: 15px;
    font-weight: 700;
}

.table-create-page .column-name {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: 12px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
}

.table-create-page .type-badge,
.table-create-page .flag-badge,
.table-create-page .summary-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
}

.table-create-page .type-badge {
    background: #eff6ff;
    color: #1d4ed8;
}

.table-create-page .flag-badge {
    background: #eef2ff;
    color: #4338ca;
}

.table-create-page .summary-chip {
    background: #f3f7fb;
    color: #41536d;
    font-weight: 600;
}

.table-create-page .icon-btn {
    border: 1px solid #d7e0eb;
    background: #fff;
    color: #314155;
    border-radius: 12px;
    padding: 9px 11px;
    line-height: 1;
    transition: all 0.2s ease;
}

.table-create-page .icon-btn:hover {
    border-color: #b8cce7;
    transform: translateY(-1px);
}

.table-create-page .icon-btn.delete:hover {
    border-color: #f0b1b1;
    background: #fff7f7;
    color: #b91c1c;
}

.table-create-page .icon-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
}

.table-create-page .check-card {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #e1e8f0;
    background: #fff;
    border-radius: 14px;
    padding: 12px 14px;
}

.table-create-page .check-card input {
    width: 16px;
    height: 16px;
    accent-color: var(--accent);
}

.table-create-page .check-card span {
    font-size: 14px;
    font-weight: 600;
}

.table-create-page .meta-list {
    gap: 0;
    border: 1px solid #e9eff6;
    border-radius: 18px;
    overflow: hidden;
}

.table-create-page .meta-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 16px;
    border-bottom: 1px solid #eef3f8;
    background: #fff;
    font-size: 14px;
}

.table-create-page .meta-row:last-child {
    border-bottom: 0;
}

.table-create-page .meta-label {
    color: var(--muted);
    font-weight: 600;
}

.table-create-page .meta-value {
    font-weight: 700;
    color: var(--ink);
    text-align: right;
}

.table-create-page .footer-actions {
    justify-content: space-between;
}

.table-create-page .error-box {
    border: 1px solid #f5c2c2;
    background: #fff7f7;
    color: #842029;
    border-radius: 16px;
    padding: 14px 16px;
    margin-bottom: 18px;
}

@media (max-width: 1100px) {
    .table-create-page .workspace {
        grid-template-columns: 1fr;
    }

    .table-create-page .hero-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .table-create-page .hero,
    .table-create-page .panel-body {
        padding: 20px;
    }

    .table-create-page .hero-top {
        flex-direction: column;
    }

    .table-create-page .hero-actions {
        justify-content: flex-start;
        width: 100%;
    }

    .table-create-page .hero-stats,
    .table-create-page .info-grid {
        grid-template-columns: 1fr;
    }

    .table-create-page .field-span-3 {
        grid-column: auto;
    }

    .table-create-page .column-top {
        flex-direction: column;
    }
}
</style>

<div class="table-create-page">
    <?php $form = ActiveForm::begin(['id' => 'table-form', 'enableClientValidation' => false]); ?>

    <div class="page-shell">
        <?php if (!empty($model->getFirstError('name'))): ?>
            <div class="error-box">
                <?= Html::encode($model->getFirstError('name')) ?>
            </div>
        <?php endif; ?>

        <section class="hero">
            <div class="hero-top">
                <div class="hero-title">
                    <div class="hero-icon">
                        <span class="material-symbols-outlined">table_chart</span>
                    </div>
                    <div>
                        <h1><?= Html::encode($pageHeading) ?></h1>
                        <p class="hero-text"><?= Html::encode($heroText) ?></p>
                    </div>
                </div>

                <div class="hero-actions">
                    <?= Html::a('Back to Tables', ['table-builder/index'], ['class' => 'btn-clean']) ?>
                    <button type="submit" class="btn-clean btn-primary-clean"><?= Html::encode($submitLabel) ?></button>
                </div>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-label">Columns</span>
                    <div class="stat-value" id="summary-column-count">0</div>
                    <p class="stat-note">Current column definitions in this draft</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Primary Keys</span>
                    <div class="stat-value" id="summary-primary-count">0</div>
                    <p class="stat-note">Columns marked as primary key</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Storage</span>
                    <div class="stat-value" style="font-size:22px;" id="summary-engine">InnoDB</div>
                    <p class="stat-note">Metadata saved to application database</p>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Execution</span>
                    <div class="stat-value" style="font-size:22px;"><?= Html::encode($executionStatusLabel) ?></div>
                    <p class="stat-note"><?= Html::encode($executionStatusNote) ?></p>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Table Metadata</h2>
                    <p class="panel-subtitle">These values are stored in the application and used later to generate the SQL table.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="info-grid">
                    <div class="field-group">
                        <label class="field-label">Table Name</label>
                        <?= $form->field($model, 'name')->textInput(['class' => 'field-input', 'placeholder' => 'pelanggan', 'autocomplete' => 'off'])->label(false) ?>
                        <p class="field-note">Lowercase letters, numbers, and underscores only.</p>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Display Label</label>
                        <?= $form->field($model, 'label')->textInput(['class' => 'field-input', 'placeholder' => 'Data Pelanggan', 'autocomplete' => 'off'])->label(false) ?>
                        <p class="field-note">Used across the interface.</p>
                    </div>

                    <div class="field-group field-span-3">
                        <label class="field-label">Description</label>
                        <?= $form->field($model, 'description')->textarea(['class' => 'field-input', 'rows' => 3, 'placeholder' => 'Optional description for this table.'])->label(false) ?>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Engine</label>
                        <?= $form->field($model, 'engine')->dropDownList(['InnoDB' => 'InnoDB', 'MyISAM' => 'MyISAM'], ['class' => 'field-select', 'id' => 'table-engine'])->label(false) ?>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Charset</label>
                        <?= $form->field($model, 'charset')->dropDownList(['utf8mb4' => 'utf8mb4', 'utf8' => 'utf8'], ['class' => 'field-select'])->label(false) ?>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Collation</label>
                        <?= $form->field($model, 'collation')->dropDownList(['utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci', 'utf8mb4_general_ci' => 'utf8mb4_general_ci'], ['class' => 'field-select'])->label(false) ?>
                    </div>
                </div>
            </div>
        </section>

        <div class="workspace">
            <div class="stack">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Columns</h2>
                            <p class="panel-subtitle">This list is the actual column structure that will be saved to `db_table_columns`.</p>
                        </div>
                        <div class="columns-toolbar">
                            <div class="columns-meta" id="columns-meta-text">No columns added yet.</div>
                            <button type="button" class="btn-clean btn-primary-clean" id="btn-add-column">Add Column</button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="columns-list" id="columns-list">
                            <div class="empty-columns" id="empty-columns-msg">
                                <span class="material-symbols-outlined">view_column</span>
                                <p class="empty-title">No columns defined</p>
                                <p class="empty-text">Add at least one column before saving the table definition.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Column Properties</h2>
                            <p class="panel-subtitle">Edit the selected column and the changes are applied immediately.</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div id="properties-empty" class="placeholder-panel">
                            <span class="material-symbols-outlined">tune</span>
                            <p class="empty-title">Select a column</p>
                            <p class="empty-text">Choose a column from the left to edit its actual saved properties.</p>
                        </div>

                        <div id="properties-form" style="display:none;">
                            <div class="property-grid">
                                <div class="field-group">
                                    <label class="field-label" for="prop-name">Column Name</label>
                                    <input type="text" class="field-input" id="prop-name" autocomplete="off">
                                    <p class="field-note">Saved as the real column identifier in the database.</p>
                                </div>

                                <div class="field-group">
                                    <label class="field-label" for="prop-label">Display Label</label>
                                    <input type="text" class="field-input" id="prop-label" autocomplete="off">
                                </div>

                                <div class="field-group">
                                    <label class="field-label" for="prop-type">Data Type</label>
                                    <select class="field-select" id="prop-type">
                                        <?php foreach (DbTableColumn::$columnTypes as $key => $value): ?>
                                            <option value="<?= Html::encode($key) ?>"><?= Html::encode($value) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label" for="prop-length">Length / Precision</label>
                                    <input type="number" class="field-input" id="prop-length" min="1">
                                </div>

                                <div class="field-group">
                                    <label class="field-label" for="prop-default">Default Value</label>
                                    <input type="text" class="field-input" id="prop-default" autocomplete="off">
                                </div>

                                <div class="field-group">
                                    <label class="field-label" for="prop-comment">Comment</label>
                                    <textarea class="field-input" id="prop-comment" rows="3"></textarea>
                                </div>

                                <div class="checkbox-grid">
                                    <label class="check-card"><input type="checkbox" id="prop-nullable"><span>Allow NULL values</span></label>
                                    <label class="check-card"><input type="checkbox" id="prop-unique"><span>Unique values only</span></label>
                                    <label class="check-card"><input type="checkbox" id="prop-primary"><span>Primary key</span></label>
                                    <label class="check-card"><input type="checkbox" id="prop-auto-increment"><span>Auto increment</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <div>
                            <h2 class="panel-title">Draft Summary</h2>
                            <p class="panel-subtitle">Live summary of the current draft before it is stored in the database.</p>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="meta-list">
                            <div class="meta-row">
                                <div class="meta-label">Table name</div>
                                <div class="meta-value" id="draft-table-name">-</div>
                            </div>
                            <div class="meta-row">
                                <div class="meta-label">Display label</div>
                                <div class="meta-value" id="draft-table-label">-</div>
                            </div>
                            <div class="meta-row">
                                <div class="meta-label">Columns</div>
                                <div class="meta-value" id="draft-column-count">0</div>
                            </div>
                            <div class="meta-row">
                                <div class="meta-label">Primary keys</div>
                                <div class="meta-value" id="draft-primary-count">0</div>
                            </div>
                            <div class="meta-row">
                                <div class="meta-label">Unique columns</div>
                                <div class="meta-value" id="draft-unique-count">0</div>
                            </div>
                            <div class="meta-row">
                                <div class="meta-label">Connection flow</div>
                                <div class="meta-value">Metadata first, SQL execution after save</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="panel">
            <div class="panel-body">
                <div class="footer-actions">
                    <p class="footer-note">Saving this form stores the table definition and column metadata in the application database. The actual SQL table can then be created from the saved definition.</p>
                    <div class="hero-actions">
                        <?= Html::a('Cancel', ['table-builder/index'], ['class' => 'btn-clean']) ?>
                        <button type="submit" class="btn-clean btn-primary-clean"><?= Html::encode($submitLabel) ?></button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <input type="hidden" name="columns" id="columns-json" value="[]">
    <?php ActiveForm::end(); ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    let columns = [];
    let selectedIndex = -1;
    let isSyncingProperties = false;

    const columnsList = document.getElementById('columns-list');
    const columnsJson = document.getElementById('columns-json');
    const emptyColumnsMsg = document.getElementById('empty-columns-msg');
    const propertiesEmpty = document.getElementById('properties-empty');
    const propertiesForm = document.getElementById('properties-form');
    const tableNameInput = document.getElementById('dbtable-name');
    const tableLabelInput = document.getElementById('dbtable-label');
    const tableEngineInput = document.getElementById('table-engine');

    const propertyFieldIds = ['prop-name', 'prop-label', 'prop-type', 'prop-length', 'prop-nullable', 'prop-unique', 'prop-primary', 'prop-auto-increment', 'prop-default', 'prop-comment'];

    const savedColumns = <?= !empty($savedColumns) ? \yii\helpers\Json::encode($savedColumns) : '[]' ?>;
    if (savedColumns.length > 0) {
        columns = savedColumns.map(normalizeColumn);
        refreshAllColumns();
    }

    if (typeof Sortable !== 'undefined') {
        new Sortable(columnsList, {
            animation: 180,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                const reorderedColumns = [];
                columnsList.querySelectorAll('.column-item').forEach(function (element) {
                    const index = parseInt(element.getAttribute('data-index'), 10);
                    reorderedColumns.push(columns[index]);
                });
                columns = reorderedColumns;
                refreshAllColumns();
                if (selectedIndex >= 0) {
                    selectColumn(Math.min(selectedIndex, columns.length - 1));
                }
                updateSchema();
                updateSummary();
            }
        });
    }

    document.getElementById('btn-add-column').addEventListener('click', function () {
        const nextIndex = columns.length + 1;
        const newColumn = normalizeColumn({
            name: 'column_' + nextIndex,
            label: 'Column ' + nextIndex,
            type: 'VARCHAR',
            length: 255,
            is_nullable: true,
            is_primary: false,
            is_unique: false,
            default_value: '',
            comment: ''
        });

        columns.push(newColumn);
        refreshAllColumns();
        selectColumn(columns.length - 1);
        updateSchema();
        updateSummary();
    });

    propertyFieldIds.forEach(function (fieldId) {
        const element = document.getElementById(fieldId);
        if (!element) {
            return;
        }
        element.addEventListener('input', syncProperty);
        element.addEventListener('change', syncProperty);
    });

    [tableNameInput, tableLabelInput, tableEngineInput].forEach(function (element) {
        if (!element) {
            return;
        }
        element.addEventListener('input', updateSummary);
        element.addEventListener('change', updateSummary);
    });

    document.getElementById('table-form').addEventListener('submit', function (event) {
        if (columns.length === 0) {
            event.preventDefault();
            alert('Add at least one column before saving the table definition.');
            return false;
        }

        columnsJson.value = JSON.stringify(columns);
    });

    updateEmptyState();
    updateSummary();

    function normalizeColumn(column) {
        return {
            name: sanitizeColumnName(column.name || ''),
            label: (column.label || column.name || 'Column').toString(),
            type: column.type || 'VARCHAR',
            length: column.length !== '' && column.length !== null ? column.length : 255,
            is_nullable: toBoolean(column.is_nullable, true),
            is_primary: toBoolean(column.is_primary, false),
            is_unique: toBoolean(column.is_unique, false),
            is_auto_increment: toBoolean(column.is_auto_increment, false),
            default_value: column.default_value || '',
            comment: column.comment || ''
        };
    }

    function toBoolean(value, fallback) {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }
        if (typeof value === 'boolean') {
            return value;
        }
        return value === 1 || value === '1' || value === 'true' || value === true;
    }

    function refreshAllColumns() {
        columnsList.querySelectorAll('.column-item').forEach(function (element) {
            element.remove();
        });

        columns.forEach(function (column, index) {
            const element = document.createElement('div');
            element.className = 'column-item';
            element.setAttribute('data-index', index);
            element.innerHTML = buildColumnHtml(column, index);
            attachColumnEvents(element, index);
            columnsList.appendChild(element);
        });

        updateColumnActionState();
        updateEmptyState();
    }

    function renderColumnItem(index) {
        const element = columnsList.querySelector('.column-item[data-index="' + index + '"]');
        if (!element || !columns[index]) {
            return;
        }

        element.innerHTML = buildColumnHtml(columns[index], index);
        attachColumnEvents(element, index);
        updateColumnActionState();
    }

    function buildColumnHtml(column, index) {
        const flags = [];
        if (column.is_primary) {
            flags.push('<span class="flag-badge">PK</span>');
        }
        if (column.is_unique) {
            flags.push('<span class="flag-badge">UQ</span>');
        }
        if (column.is_auto_increment) {
            flags.push('<span class="flag-badge">AI</span>');
        }

        const summary = [];
        summary.push('<span class="summary-chip">' + escapeHtml(column.is_nullable ? 'Nullable' : 'Required') + '</span>');
        if (column.default_value) {
            summary.push('<span class="summary-chip">Default: ' + escapeHtml(column.default_value) + '</span>');
        }
        if (column.comment) {
            summary.push('<span class="summary-chip">Comment set</span>');
        }

        return '' +
            '<div class="column-top">' +
                '<div class="column-main">' +
                    '<span class="material-symbols-outlined drag-handle" title="Drag to reorder">drag_indicator</span>' +
                    '<div>' +
                        '<div class="column-title">' +
                            '<span class="column-label">' + escapeHtml(column.label || column.name || 'Untitled Column') + '</span>' +
                            '<span class="type-badge">' + escapeHtml(formatColumnType(column)) + '</span>' +
                            flags.join('') +
                        '</div>' +
                        '<p class="column-name">' + escapeHtml(column.name) + '</p>' +
                    '</div>' +
                '</div>' +
                '<div class="column-actions">' +
                    '<button type="button" class="icon-btn move-up" title="Move Up"' + (index === 0 ? ' disabled' : '') + '><span class="material-symbols-outlined" style="font-size:18px;">arrow_upward</span></button>' +
                    '<button type="button" class="icon-btn move-down" title="Move Down"' + (index === columns.length - 1 ? ' disabled' : '') + '><span class="material-symbols-outlined" style="font-size:18px;">arrow_downward</span></button>' +
                    '<button type="button" class="icon-btn duplicate" title="Duplicate"><span class="material-symbols-outlined" style="font-size:18px;">content_copy</span></button>' +
                    '<button type="button" class="icon-btn delete" title="Delete"><span class="material-symbols-outlined" style="font-size:18px;">delete</span></button>' +
                '</div>' +
            '</div>' +
            '<div class="column-summary">' + summary.join('') + '</div>';
    }

    function formatColumnType(column) {
        return column.length ? column.type + ' (' + column.length + ')' : column.type;
    }

    function attachColumnEvents(element, index) {
        element.addEventListener('click', function (event) {
            if (!event.target.closest('.icon-btn') && !event.target.closest('.drag-handle')) {
                selectColumn(index);
            }
        });

        const moveUp = element.querySelector('.move-up');
        const moveDown = element.querySelector('.move-down');
        const duplicate = element.querySelector('.duplicate');
        const remove = element.querySelector('.delete');

        if (moveUp) {
            moveUp.addEventListener('click', function (event) {
                event.stopPropagation();
                if (index > 0) {
                    swapColumns(index, index - 1);
                    selectColumn(index - 1);
                }
            });
        }

        if (moveDown) {
            moveDown.addEventListener('click', function (event) {
                event.stopPropagation();
                if (index < columns.length - 1) {
                    swapColumns(index, index + 1);
                    selectColumn(index + 1);
                }
            });
        }

        if (duplicate) {
            duplicate.addEventListener('click', function (event) {
                event.stopPropagation();
                const source = columns[index];
                const copy = normalizeColumn({
                    name: source.name + '_copy',
                    label: source.label + ' Copy',
                    type: source.type,
                    length: source.length,
                    is_nullable: source.is_nullable,
                    is_primary: false,
                    is_unique: false,
                    is_auto_increment: false,
                    default_value: source.default_value,
                    comment: source.comment
                });
                columns.splice(index + 1, 0, copy);
                refreshAllColumns();
                selectColumn(index + 1);
                updateSchema();
                updateSummary();
            });
        }

        if (remove) {
            remove.addEventListener('click', function (event) {
                event.stopPropagation();
                if (!confirm('Delete this column from the table definition?')) {
                    return;
                }

                columns.splice(index, 1);

                if (selectedIndex === index) {
                    selectedIndex = -1;
                    hideProperties();
                } else if (selectedIndex > index) {
                    selectedIndex--;
                }

                refreshAllColumns();
                if (selectedIndex >= 0 && columns[selectedIndex]) {
                    selectColumn(selectedIndex);
                }
                updateSchema();
                updateSummary();
            });
        }
    }

    function swapColumns(firstIndex, secondIndex) {
        const first = columns[firstIndex];
        columns[firstIndex] = columns[secondIndex];
        columns[secondIndex] = first;
        refreshAllColumns();
        updateSchema();
        updateSummary();
    }

    function updateColumnActionState() {
        columnsList.querySelectorAll('.column-item').forEach(function (element, index) {
            element.setAttribute('data-index', index);
            const moveUp = element.querySelector('.move-up');
            const moveDown = element.querySelector('.move-down');
            if (moveUp) {
                moveUp.disabled = index === 0;
            }
            if (moveDown) {
                moveDown.disabled = index === columns.length - 1;
            }
        });
    }

    function selectColumn(index) {
        selectedIndex = index;
        columnsList.querySelectorAll('.column-item').forEach(function (element) {
            element.classList.toggle('selected', parseInt(element.getAttribute('data-index'), 10) === index);
        });
        showProperties();
        updateProperties();
    }

    function showProperties() {
        propertiesEmpty.style.display = 'none';
        propertiesForm.style.display = '';
    }

    function hideProperties() {
        propertiesEmpty.style.display = '';
        propertiesForm.style.display = 'none';
    }

    function updateProperties() {
        if (selectedIndex < 0 || !columns[selectedIndex]) {
            hideProperties();
            return;
        }

        const column = columns[selectedIndex];
        isSyncingProperties = true;
        document.getElementById('prop-name').value = column.name || '';
        document.getElementById('prop-label').value = column.label || '';
        document.getElementById('prop-type').value = column.type;
        document.getElementById('prop-length').value = column.length || '';
        document.getElementById('prop-nullable').checked = column.is_nullable;
        document.getElementById('prop-unique').checked = column.is_unique;
        document.getElementById('prop-primary').checked = column.is_primary;
        document.getElementById('prop-auto-increment').checked = toBoolean(column.is_auto_increment, false);
        document.getElementById('prop-default').value = column.default_value || '';
        document.getElementById('prop-comment').value = column.comment || '';
        isSyncingProperties = false;
    }

    function syncProperty() {
        if (selectedIndex < 0 || !columns[selectedIndex] || isSyncingProperties) {
            return;
        }

        const column = columns[selectedIndex];
        const nameInput = document.getElementById('prop-name');

        column.name = sanitizeColumnName(nameInput.value, true);
        column.label = document.getElementById('prop-label').value;
        column.type = document.getElementById('prop-type').value;
        column.length = document.getElementById('prop-length').value || null;
        column.is_nullable = document.getElementById('prop-nullable').checked;
        column.is_unique = document.getElementById('prop-unique').checked;
        column.is_primary = document.getElementById('prop-primary').checked;
        column.is_auto_increment = document.getElementById('prop-auto-increment').checked;
        column.default_value = document.getElementById('prop-default').value;
        column.comment = document.getElementById('prop-comment').value;

        const integerTypes = ['INT', 'BIGINT', 'TINYINT'];
        if (column.is_auto_increment && integerTypes.indexOf(column.type) === -1) {
            column.is_auto_increment = false;
            document.getElementById('prop-auto-increment').checked = false;
        }
        if (column.is_auto_increment) {
            column.is_primary = true;
            column.is_nullable = false;
            document.getElementById('prop-primary').checked = true;
            document.getElementById('prop-nullable').checked = false;
        }

        if (column.is_primary) {
            column.is_nullable = false;
            document.getElementById('prop-nullable').checked = false;
        }

        isSyncingProperties = true;
        nameInput.value = column.name;
        isSyncingProperties = false;

        renderColumnItem(selectedIndex);
        const activeColumn = columnsList.querySelector('.column-item[data-index="' + selectedIndex + '"]');
        if (activeColumn) {
            activeColumn.classList.add('selected');
        }
        updateSchema();
        updateSummary();
    }

    function updateEmptyState() {
        emptyColumnsMsg.style.display = columns.length === 0 ? '' : 'none';
        document.getElementById('columns-meta-text').textContent = columns.length === 0
            ? 'No columns added yet.'
            : columns.length + ' column' + (columns.length === 1 ? '' : 's') + ' in this draft.';
    }

    function updateSchema() {
        columnsJson.value = JSON.stringify(columns);
    }

    function updateSummary() {
        const primaryCount = columns.filter(function (column) { return column.is_primary; }).length;
        const uniqueCount = columns.filter(function (column) { return column.is_unique; }).length;
        const tableName = (tableNameInput.value || '').trim();
        const tableLabel = (tableLabelInput.value || '').trim();
        const engine = (tableEngineInput.value || '').trim();

        document.getElementById('summary-column-count').textContent = String(columns.length);
        document.getElementById('summary-primary-count').textContent = String(primaryCount);
        document.getElementById('summary-engine').textContent = engine || '-';
        document.getElementById('draft-table-name').textContent = tableName || '-';
        document.getElementById('draft-table-label').textContent = tableLabel || '-';
        document.getElementById('draft-column-count').textContent = String(columns.length);
        document.getElementById('draft-primary-count').textContent = String(primaryCount);
        document.getElementById('draft-unique-count').textContent = String(uniqueCount);
    }

    function sanitizeColumnName(value, allowEmpty) {
        const sanitized = (value || '')
            .toLowerCase()
            .replace(/[^a-z0-9_]+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '');

        if (allowEmpty) {
            return sanitized;
        }

        return sanitized || 'column';
    }

    function escapeHtml(value) {
        const container = document.createElement('div');
        container.textContent = value;
        return container.innerHTML;
    }
});
</script>
