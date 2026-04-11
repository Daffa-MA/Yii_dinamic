<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Create Database Table';
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<style>
:root {
    --primary: #2563eb;
    --primary-light: #3b82f6;
    --primary-dark: #1d4ed8;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-600: #4b5563;
    --gray-800: #1f2937;
}

.builder-container {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 120px);
    gap: 24px;
    animation: fadeInUp 0.6s ease;
}

.table-info-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-200);
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i { color: var(--primary); }

.builder-main {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    flex: 1;
}

.columns-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-200);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.columns-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--gray-50);
}

.columns-list {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    min-height: 200px;
}

.column-item {
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
}

.column-item:hover {
    border-color: var(--primary-light);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.column-item.selected {
    border-color: var(--primary);
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.column-item.sortable-ghost {
    opacity: 0.4;
    border: 2px dashed var(--primary) !important;
    background: #eff6ff;
}

.column-item.sortable-chosen {
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.column-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--gray-100);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-600);
}

.column-actions {
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s;
}

.column-item:hover .column-actions,
.column-item.selected .column-actions {
    opacity: 1;
}

.column-action-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    background: white;
    color: var(--gray-600);
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.column-action-btn:hover { background: var(--gray-100); }
.column-action-btn.delete:hover { background: #fee2e2; color: var(--danger); border-color: var(--danger); }

.drag-handle {
    cursor: grab;
    color: var(--gray-300);
    font-size: 18px;
    margin-right: 8px;
    user-select: none;
}

.drag-handle:active { cursor: grabbing; }

.properties-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-200);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 24px;
    max-height: calc(100vh - 200px);
}

.properties-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.properties-tabs {
    display: flex;
    border-bottom: 1px solid var(--gray-200);
}

.properties-tab {
    flex: 1;
    padding: 12px;
    text-align: center;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-600);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}

.properties-tab:hover { color: var(--gray-800); background: var(--gray-50); }

.properties-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: #eff6ff;
}

.properties-content {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}

.property-group { margin-bottom: 24px; animation: fadeIn 0.3s ease; }

.property-group-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-600);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-200);
}

.property-field { margin-bottom: 16px; }

.property-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-800);
    margin-bottom: 6px;
}

.property-input, .property-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.property-input:focus, .property-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.property-select { background: white; cursor: pointer; }

.property-hint {
    font-size: 12px;
    color: var(--gray-600);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.property-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--gray-50);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.property-checkbox:hover { background: var(--gray-100); }
.property-checkbox input { width: 18px; height: 18px; accent-color: var(--primary); }

.btn-add-column {
    padding: 12px 20px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-add-column:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
}

.btn-save-table {
    padding: 14px 32px;
    background: linear-gradient(135deg, var(--success), #059669);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-save-table:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
}

.empty-columns {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-600);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes bounceIn {
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); opacity: 1; }
}

.bounce-in { animation: bounceIn 0.5s ease; }

@media (max-width: 1200px) {
    .builder-main { grid-template-columns: 1fr; }
    .properties-panel { position: static; max-height: none; }
}
</style>

<div class="table-builder-create">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">🗄️ Create Database Table</h1>
        <a href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Tables
        </a>
    </div>

    <?php $form = ActiveForm::begin(['id' => 'table-form', 'enableClientValidation' => false]); ?>

    <?php if (!empty($model->getFirstError('name'))): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Validation Error:</strong> <?= $model->getFirstError('name') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="builder-container">
        <!-- Table Info -->
        <div class="table-info-section">
            <div class="section-title"><i class="bi bi-info-circle"></i> Table Information</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="property-field mb-0">
                        <label class="property-label">Table Name *</label>
                        <?= $form->field($model, 'name')->textInput(['class' => 'property-input', 'placeholder' => 'users, products...'])->label(false) ?>
                        <div class="property-hint"><i class="bi bi-info-circle"></i> Lowercase, underscores allowed</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="property-field mb-0">
                        <label class="property-label">Display Label *</label>
                        <?= $form->field($model, 'label')->textInput(['class' => 'property-input', 'placeholder' => 'Users Table...'])->label(false) ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="property-field mb-0">
                        <label class="property-label">Description</label>
                        <?= $form->field($model, 'description')->textInput(['class' => 'property-input', 'placeholder' => 'Optional...'])->label(false) ?>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <div class="property-field mb-0">
                        <label class="property-label">Engine</label>
                        <?= $form->field($model, 'engine')->dropDownList(['InnoDB' => 'InnoDB', 'MyISAM' => 'MyISAM'], ['class' => 'property-select'])->label(false) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="property-field mb-0">
                        <label class="property-label">Charset</label>
                        <?= $form->field($model, 'charset')->dropDownList(['utf8mb4' => 'utf8mb4', 'utf8' => 'utf8'], ['class' => 'property-select'])->label(false) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="property-field mb-0">
                        <label class="property-label">Collation</label>
                        <?= $form->field($model, 'collation')->dropDownList(['utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci', 'utf8mb4_general_ci' => 'utf8mb4_general_ci'], ['class' => 'property-select'])->label(false) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Builder -->
        <div class="builder-main">
            <!-- Columns Panel -->
            <div class="columns-panel">
                <div class="columns-header">
                    <div class="section-title mb-0"><i class="bi bi-list-check"></i> Columns</div>
                    <button type="button" class="btn-add-column" id="btn-add-column">
                        <i class="bi bi-plus-circle"></i> Add Column
                    </button>
                </div>
                <div class="columns-list" id="columns-list">
                    <div class="empty-columns" id="empty-columns-msg">
                        <div style="font-size: 48px; margin-bottom: 12px;">📋</div>
                        <p>No columns added. Click "Add Column" to start.</p>
                    </div>
                </div>
            </div>

            <!-- Properties Panel -->
            <div class="properties-panel">
                <div class="properties-header">
                    <div class="section-title mb-0"><i class="bi bi-sliders"></i> Column Properties</div>
                </div>
                <div class="properties-tabs">
                    <div class="properties-tab active" data-tab="general">General</div>
                    <div class="properties-tab" data-tab="validation">Validation</div>
                    <div class="properties-tab" data-tab="advanced">Advanced</div>
                </div>
                <div class="properties-content">
                    <div id="properties-empty" class="text-center text-muted py-5">
                        <div style="font-size: 48px; margin-bottom: 12px;">🎯</div>
                        <p>Select a column to edit</p>
                    </div>
                    <div id="properties-general" class="properties-tab-content" style="display:none;">
                        <div class="property-group">
                            <div class="property-group-title">Column Settings</div>
                            <div class="property-field">
                                <label class="property-label">Column Name</label>
                                <input type="text" class="property-input" id="prop-name" placeholder="column_name">
                                <div class="property-hint"><i class="bi bi-info-circle"></i> Auto-generated from label</div>
                            </div>
                            <div class="property-field">
                                <label class="property-label">Display Label</label>
                                <input type="text" class="property-input" id="prop-label" placeholder="Column Label">
                            </div>
                            <div class="property-field">
                                <label class="property-label">Data Type</label>
                                <select class="property-select" id="prop-type">
                                    <?php foreach (\app\models\DbTableColumn::$columnTypes as $key => $val): ?>
                                        <option value="<?= $key ?>"><?= $val ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="property-field">
                                <label class="property-label">Length/Precision</label>
                                <input type="number" class="property-input" id="prop-length" placeholder="255" min="1">
                            </div>
                        </div>
                    </div>
                    <div id="properties-validation" class="properties-tab-content" style="display:none;">
                        <div class="property-group">
                            <div class="property-group-title">Validation Rules</div>
                            <label class="property-checkbox mb-3"><input type="checkbox" id="prop-nullable"><span>Allow NULL values</span></label>
                            <label class="property-checkbox mb-3"><input type="checkbox" id="prop-unique"><span>Unique values only</span></label>
                            <div class="property-field">
                                <label class="property-label">Default Value</label>
                                <input type="text" class="property-input" id="prop-default" placeholder="NULL or value">
                            </div>
                        </div>
                    </div>
                    <div id="properties-advanced" class="properties-tab-content" style="display:none;">
                        <div class="property-group">
                            <div class="property-group-title">Advanced Settings</div>
                            <label class="property-checkbox mb-3"><input type="checkbox" id="prop-primary"><span>Primary Key</span></label>
                            <div class="property-field">
                                <label class="property-label">Comment</label>
                                <textarea class="property-input" id="prop-comment" rows="3" placeholder="Column description..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>" class="btn btn-outline-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancel</a>
            <button type="submit" class="btn-save-table"><i class="bi bi-check-circle"></i> Create Table</button>
        </div>
    </div>

    <input type="hidden" name="columns" id="columns-json" value="[]">
    <?php ActiveForm::end(); ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let columns = [];
    let selectedIndex = -1;
    let sortableInstance = null;

    const columnsList = document.getElementById('columns-list');
    const columnsJson = document.getElementById('columns-json');

    // Restore columns from POST data if validation failed
    const savedColumns = <?= !empty($savedColumns) ? \yii\helpers\Json::encode($savedColumns) : '[]' ?>;
    if (savedColumns.length > 0) {
        columns = savedColumns;
        columns.forEach(function(col, i) {
            addColumnToDOM(col, i);
        });
        updateEmptyState();
        updateSchema();
    }

    // Initialize Sortable ONCE
    sortableInstance = new Sortable(columnsList, {
        animation: 200,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function(evt) {
            // Rebuild columns array from DOM order
            const newOrder = [];
            columnsList.querySelectorAll('.column-item').forEach(function(el) {
                const idx = parseInt(el.getAttribute('data-index'));
                newOrder.push(columns[idx]);
            });
            columns = newOrder;
            // Update data-index attributes
            columnsList.querySelectorAll('.column-item').forEach(function(el, i) {
                el.setAttribute('data-index', i);
            });
            updateSchema();
        }
    });

    document.getElementById('btn-add-column').addEventListener('click', function() {
        const idx = columns.length;
        const column = {
            name: 'column_' + (idx + 1),
            label: 'Column ' + (idx + 1),
            type: 'VARCHAR',
            length: 255,
            is_nullable: true,
            is_primary: false,
            is_unique: false,
            default_value: '',
            comment: ''
        };
        columns.push(column);
        addColumnToDOM(column, idx);
        selectColumn(idx);
        updateEmptyState();
        updateSchema();
    });

    function addColumnToDOM(col, idx) {
        const div = document.createElement('div');
        div.className = 'column-item';
        div.setAttribute('data-index', idx);
        div.innerHTML = buildColumnHTML(col, idx);
        attachColumnEvents(div, idx);
        columnsList.appendChild(div);

        // Animate
        requestAnimationFrame(function() {
            div.classList.add('bounce-in');
            setTimeout(function() { div.classList.remove('bounce-in'); }, 600);
        });
    }

    function buildColumnHTML(col, idx) {
        const icon = getTypeIcon(col.type);
        return `
            <div class="column-header">
                <div class="d-flex align-items-center">
                    <span class="drag-handle" title="Drag to reorder">⠿</span>
                    <strong>${escapeHtml(col.label)}</strong>
                    <span class="column-type-badge ms-2">${icon} ${col.type}${col.length ? '(' + col.length + ')' : ''}</span>
                    ${col.is_primary ? '<span class="badge bg-primary ms-2">PK</span>' : ''}
                    ${col.is_unique ? '<span class="badge bg-info ms-1">UQ</span>' : ''}
                </div>
                <div class="column-actions">
                    <button type="button" class="column-action-btn move-up" ${idx === 0 ? 'disabled' : ''} title="Move Up">↑</button>
                    <button type="button" class="column-action-btn move-down" ${idx === columns.length - 1 ? 'disabled' : ''} title="Move Down">↓</button>
                    <button type="button" class="column-action-btn duplicate" title="Duplicate">⧉</button>
                    <button type="button" class="column-action-btn delete" title="Delete">✕</button>
                </div>
            </div>`;
    }

    function attachColumnEvents(div, idx) {
        div.addEventListener('click', function(e) {
            if (!e.target.closest('.column-action-btn') && !e.target.closest('.drag-handle')) {
                selectColumn(idx);
            }
        });

        const upBtn = div.querySelector('.move-up');
        const downBtn = div.querySelector('.move-down');
        const dupBtn = div.querySelector('.duplicate');
        const delBtn = div.querySelector('.delete');

        if (upBtn) upBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (idx > 0) {
                [columns[idx], columns[idx - 1]] = [columns[idx - 1], columns[idx]];
                refreshAllColumns();
                selectColumn(idx - 1);
                updateSchema();
            }
        });

        if (downBtn) downBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (idx < columns.length - 1) {
                [columns[idx], columns[idx + 1]] = [columns[idx + 1], columns[idx]];
                refreshAllColumns();
                selectColumn(idx + 1);
                updateSchema();
            }
        });

        if (dupBtn) dupBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const copy = Object.assign({}, columns[idx]);
            copy.name = copy.name + '_copy';
            columns.splice(idx + 1, 0, copy);
            refreshAllColumns();
            selectColumn(idx + 1);
            updateSchema();
        });

        if (delBtn) delBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (confirm('Delete this column?')) {
                columns.splice(idx, 1);
                if (selectedIndex === idx) { selectedIndex = -1; hideProperties(); }
                else if (selectedIndex > idx) selectedIndex--;
                refreshAllColumns();
                updateEmptyState();
                updateSchema();
            }
        });
    }

    function refreshAllColumns() {
        // Remove all column items from DOM
        columnsList.querySelectorAll('.column-item').forEach(function(el) { el.remove(); });
        // Re-add in order
        columns.forEach(function(col, i) {
            addColumnToDOM(col, i);
        });
        updateEmptyState();
    }

    function selectColumn(idx) {
        selectedIndex = idx;
        columnsList.querySelectorAll('.column-item').forEach(function(el) {
            el.classList.toggle('selected', parseInt(el.getAttribute('data-index')) === idx);
        });
        showProperties();
        updateProperties();
    }

    function showProperties() {
        document.getElementById('properties-empty').style.display = 'none';
        document.querySelectorAll('.properties-tab-content').forEach(function(el) { el.style.display = 'none'; });
        document.querySelector('.properties-tab[data-tab="general"]').classList.add('active');
        document.getElementById('properties-general').style.display = '';
    }

    function hideProperties() {
        document.getElementById('properties-empty').style.display = '';
        document.querySelectorAll('.properties-tab-content').forEach(function(el) { el.style.display = 'none'; });
    }

    function updateProperties() {
        if (selectedIndex < 0) { hideProperties(); return; }
        const col = columns[selectedIndex];
        document.getElementById('prop-name').value = col.name;
        document.getElementById('prop-label').value = col.label;
        document.getElementById('prop-type').value = col.type;
        document.getElementById('prop-length').value = col.length || '';
        document.getElementById('prop-nullable').checked = col.is_nullable;
        document.getElementById('prop-unique').checked = col.is_unique;
        document.getElementById('prop-primary').checked = col.is_primary;
        document.getElementById('prop-default').value = col.default_value || '';
        document.getElementById('prop-comment').value = col.comment || '';
    }

    function updateEmptyState() {
        const msg = document.getElementById('empty-columns-msg');
        if (columns.length === 0) {
            msg.style.display = '';
        } else {
            msg.style.display = 'none';
        }
    }

    function updateSchema() {
        columnsJson.value = JSON.stringify(columns);
    }

    function getTypeIcon(type) {
        const icons = { VARCHAR:'📝', CHAR:'📝', TEXT:'📄', INT:'🔢', BIGINT:'🔢', TINYINT:'🔢', DECIMAL:'💰', FLOAT:'💰', DATE:'📅', DATETIME:'📅', TIMESTAMP:'⏰', TIME:'⏰', BOOLEAN:'☑️', JSON:'📋', BLOB:'📦' };
        return icons[type] || '📝';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Property input listeners
    ['prop-name','prop-label','prop-type','prop-length','prop-nullable','prop-unique','prop-primary','prop-default','prop-comment'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', syncProperty);
            el.addEventListener('change', syncProperty);
        }
    });

    function syncProperty() {
        if (selectedIndex < 0) return;
        const col = columns[selectedIndex];
        col.label = document.getElementById('prop-label').value;
        col.name = col.label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'column';
        col.type = document.getElementById('prop-type').value;
        col.length = document.getElementById('prop-length').value || null;
        col.is_nullable = document.getElementById('prop-nullable').checked;
        col.is_unique = document.getElementById('prop-unique').checked;
        col.is_primary = document.getElementById('prop-primary').checked;
        col.default_value = document.getElementById('prop-default').value;
        col.comment = document.getElementById('prop-comment').value;

        document.getElementById('prop-name').value = col.name;
        refreshAllColumns();
        selectColumn(selectedIndex);
        updateSchema();
    }

    // Properties tabs
    document.querySelectorAll('.properties-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.properties-tab').forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.properties-tab-content').forEach(function(el) { el.style.display = 'none'; });
            document.getElementById('properties-' + this.dataset.tab).style.display = '';
        });
    });

    // Form submit
    document.getElementById('table-form').addEventListener('submit', function(e) {
        if (columns.length === 0) {
            e.preventDefault();
            alert('Please add at least one column!');
            return false;
        }
        columnsJson.value = JSON.stringify(columns);
    });

    updateEmptyState();
});
</script>
