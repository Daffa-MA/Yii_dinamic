<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\MasterPage;

/* @var $this yii\web\View */
/* @var $model app\models\MasterForm */

$this->title = 'Edit Form: ' . $model->form_name;
$this->params['breadcrumbs'][] = ['label' => 'Master Forms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$pages = MasterPage::find()->where(['is_active' => 1])->all();
$pageList = \yii\helpers\ArrayHelper::map($pages, 'id', 'title');

$existingFields = !empty($model->form_data) ? json_encode($model->form_data) : '[]';

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_END]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');
?>

<style>
.page-builder {
    display: flex;
    height: calc(100vh - 140px);
    background: #f8fafc;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.builder-sidebar-left {
    width: 220px;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

.builder-canvas {
    flex: 1;
    min-width: 0;
    background: #f1f5f9;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 24px;
    display: flex;
    flex-direction: column;
}

.builder-properties {
    width: 280px;
    background: #ffffff;
    border-left: 1px solid #e5e7eb;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

.sidebar-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 700;
    color: #1f2937;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-header .material-symbols-outlined {
    font-size: 18px;
    color: #3b82f6;
}

.component-section-title {
    padding: 12px 20px 8px;
    font-size: 10px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.component-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    cursor: pointer;
    transition: all 0.15s;
    color: #4b5563;
    font-size: 13px;
}

.component-item:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.component-item .material-symbols-outlined {
    font-size: 18px;
    color: #6b7280;
}

.prop-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 700;
    color: #1f2937;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.prop-header .material-symbols-outlined {
    font-size: 18px;
    color: #6366f1;
}

.prop-group {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
}

.prop-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.prop-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    transition: all 0.15s;
    box-sizing: border-box;
}

.prop-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.prop-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
}

.prop-checkbox input {
    width: 16px;
    height: 16px;
    accent-color: #3b82f6;
}

.no-selection {
    padding: 40px 20px;
    text-align: center;
    color: #94a3b8;
}

.no-selection .material-symbols-outlined {
    font-size: 40px;
    margin-bottom: 12px;
    opacity: 0.4;
}

.canvas-drop-zone {
    background: #ffffff;
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    min-height: 500px;
    padding: 20px;
    transition: all 0.15s;
    overflow-x: hidden;
    box-sizing: border-box;
    width: 100%;
}

.canvas-drop-zone.drag-over {
    border-color: #3b82f6;
    background: #eff6ff;
}

.field-item {
    position: relative;
    padding: 16px;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.15s;
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

.field-item:hover {
    border-color: #d1d5db;
}

.field-item.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.field-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.field-item-label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
    flex: 1;
    min-width: 0;
}

.field-item-label .material-symbols-outlined {
    font-size: 16px;
    color: #6b7280;
}

.field-item-required {
    color: #ef4444;
    font-size: 12px;
    font-weight: 600;
}

.field-item-delete {
    color: #9ca3af;
    cursor: pointer;
    transition: color 0.15s;
}

.field-item-delete:hover {
    color: #ef4444;
}

.field-preview {
    padding: 10px 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    color: #6b7280;
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    word-wrap: break-word;
}

.field-preview input,
.field-preview select,
.field-preview textarea {
    box-sizing: border-box;
    max-width: 100%;
}

.field-preview label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #4b5563;
    box-sizing: border-box;
    max-width: 100%;
    overflow-x: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.field-preview > div {
    box-sizing: border-box;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

.field-name {
    margin-top: 8px;
    font-size: 11px;
    color: #9ca3af;
}

/* Drag & Drop Styles */
.field-drag-handle {
    cursor: grab;
    color: #9ca3af;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.15s;
    flex-shrink: 0;
}

.field-drag-handle:hover {
    color: #3b82f6;
    background: #eff6ff;
}

.field-drag-handle:active {
    cursor: grabbing;
}

.field-item.dragging {
    opacity: 0.5;
    background: #f3f4f6;
}

.field-item.sortable-ghost {
    opacity: 0.4;
    background: #eff6ff;
    border-style: dashed;
}

.field-item.sortable-chosen {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    transform: scale(1.02);
}

.sortable-drag {
    opacity: 0;
}

.builder-toolbar {
    height: 56px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    border-radius: 12px 12px 0 0;
}

.builder-toolbar-title {
    font-weight: 700;
    color: #1f2937;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.builder-toolbar-title .material-symbols-outlined {
    color: #3b82f6;
}

.builder-toolbar-actions {
    display: flex;
    gap: 10px;
}

.btn-save {
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s;
}

.btn-save:hover {
    background: #2563eb;
}

.btn-cancel {
    padding: 10px 20px;
    background: #ffffff;
    color: #6b7280;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s;
    text-decoration: none;
}

.btn-cancel:hover {
    background: #f9fafb;
    color: #374151;
}

.form-meta-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 16px 20px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}
</style>

<!-- FORM BUILDER INTERFACE -->
<div class="bg-gray-100 py-6 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header & Toolbar -->
        <div class="bg-white rounded-xl shadow-sm mb-4">
            <div class="builder-toolbar">
                <div class="builder-toolbar-title">
                    <span class="material-symbols-outlined">dynamic_form</span>
                    Edit Form: <?= Html::encode($model->form_name) ?>
                </div>
                <div class="builder-toolbar-actions">
                    <a href="<?= Url::to(['index']) ?>" class="btn-cancel">Batal</a>
                    <button type="submit" form="master-form-form" class="btn-save">
                        <span class="material-symbols-outlined">save</span>
                        Update Form
                    </button>
                </div>
            </div>
            
            <!-- Form Meta -->
            <form id="master-form-form" method="post">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                <input type="hidden" name="MasterForm[page_id]" value="<?= $model->page_id ?>">
                <input type="hidden" name="MasterForm[form_data]" id="form-data-input" value="<?= Html::encode($existingFields) ?>">
                
                <div class="form-meta-row">
                    <div>
                        <label class="prop-label">Nama Form</label>
                        <input type="text" name="MasterForm[form_name]" class="prop-input" value="<?= Html::encode($model->form_name) ?>">
                    </div>
                    <div>
                        <label class="prop-label">Slug</label>
                        <input type="text" name="MasterForm[slug]" class="prop-input" value="<?= Html::encode($model->slug) ?>">
                    </div>
                    <div>
                        <label class="prop-label">Status</label>
                        <select name="MasterForm[is_active]" class="prop-input">
                            <option value="1" <?= $model->is_active == 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= $model->is_active == 0 ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Builder -->
        <div class="page-builder">
            <!-- LEFT PANEL: Component Library -->
            <div class="builder-sidebar-left">
                <div class="sidebar-header">
                    <span class="material-symbols-outlined">widgets</span>
                    Fields
                </div>

                <div class="component-section-title">Basic Input</div>
                <div class="component-item" draggable="true" data-field-type="text">
                    <span class="material-symbols-outlined">text_fields</span>
                    <span>Text Input</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="email">
                    <span class="material-symbols-outlined">email</span>
                    <span>Email</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="password">
                    <span class="material-symbols-outlined">lock</span>
                    <span>Password</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="number">
                    <span class="material-symbols-outlined">pin</span>
                    <span>Number</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="tel">
                    <span class="material-symbols-outlined">phone</span>
                    <span>Phone</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="url">
                    <span class="material-symbols-outlined">link</span>
                    <span>URL</span>
                </div>

                <div class="component-section-title">Text Area</div>
                <div class="component-item" draggable="true" data-field-type="textarea">
                    <span class="material-symbols-outlined">notes</span>
                    <span>Textarea</span>
                </div>

                <div class="component-section-title">Selection</div>
                <div class="component-item" draggable="true" data-field-type="select">
                    <span class="material-symbols-outlined">arrow_drop_down_circle</span>
                    <span>Dropdown</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="radio">
                    <span class="material-symbols-outlined">radio_button_checked</span>
                    <span>Radio Group</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="checkbox">
                    <span class="material-symbols-outlined">check_box</span>
                    <span>Checkbox</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="checkboxes">
                    <span class="material-symbols-outlined">checklist</span>
                    <span>Checkbox Group</span>
                </div>

                <div class="component-section-title">Date & Time</div>
                <div class="component-item" draggable="true" data-field-type="date">
                    <span class="material-symbols-outlined">calendar_today</span>
                    <span>Date</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="time">
                    <span class="material-symbols-outlined">schedule</span>
                    <span>Time</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="datetime">
                    <span class="material-symbols-outlined">event</span>
                    <span>Date Time</span>
                </div>

                <div class="component-section-title">File & Hidden</div>
                <div class="component-item" draggable="true" data-field-type="file">
                    <span class="material-symbols-outlined">upload_file</span>
                    <span>File Upload</span>
                </div>
                <div class="component-item" draggable="true" data-field-type="hidden">
                    <span class="material-symbols-outlined">visibility_off</span>
                    <span>Hidden</span>
                </div>
            </div>

            <!-- CENTER: Canvas -->
            <div class="builder-canvas">
                <div id="canvas-drop-zone" class="canvas-drop-zone">
                    <p id="canvas-placeholder" style="text-align: center; color: #94a3b8; padding: 60px 20px;">
                        <span style="font-size:40px;display:block;margin-bottom:12px">📋</span>
                        Drag form fields dari panel kiri ke sini<br>
                        atau klik untuk menambah field
                    </p>
                    <div id="form-fields-container"></div>
                </div>
            </div>

            <!-- RIGHT PANEL: Properties -->
            <div class="builder-properties">
                <div class="prop-header">
                    <span class="material-symbols-outlined">settings</span>
                    Properties
                </div>
                <div id="properties-panel">
                    <div class="no-selection">
                        <span class="material-symbols-outlined">touch_app</span>
                        <p style="font-size:13px">Pilih field untuk edit</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$script = <<<'JS'
(function() {
    const dropZone = document.getElementById('canvas-drop-zone');
    const container = document.getElementById('form-fields-container');
    const placeholder = document.getElementById('canvas-placeholder');
    const propsPanel = document.getElementById('properties-panel');
    const formDataInput = document.getElementById('form-data-input');
    const componentItems = document.querySelectorAll('.component-item');
    
    let formFields = JSON.parse(formDataInput.value || '[]');
    let selectedIndex = null;
    
    const fieldConfig = {
        text: { label: 'Text Input', inputType: 'text', placeholder: 'Masukkan teks...' },
        email: { label: 'Email', inputType: 'email', placeholder: 'email@example.com' },
        password: { label: 'Password', inputType: 'password', placeholder: '' },
        number: { label: 'Number', inputType: 'number', placeholder: '' },
        tel: { label: 'Phone', inputType: 'tel', placeholder: '+62 xxx' },
        url: { label: 'URL', inputType: 'url', placeholder: 'https://...' },
        textarea: { label: 'Textarea', inputType: 'textarea', rows: 4, placeholder: 'Masukkan teks panjang...' },
        select: { label: 'Dropdown', inputType: 'select', options: [{value:'',label:'Pilih...'}, {value:'opt1',label:'Opsi 1'}] },
        radio: { label: 'Radio Group', inputType: 'radio', options: [{value:'opt1',label:'Opsi 1'}, {value:'opt2',label:'Opsi 2'}] },
        checkbox: { label: 'Checkbox', inputType: 'checkbox', labelText: 'Centang ini' },
        checkboxes: { label: 'Checkbox Group', inputType: 'checkboxes', options: [{value:'opt1',label:'Opsi 1'}, {value:'opt2',label:'Opsi 2'}] },
        date: { label: 'Date', inputType: 'date' },
        time: { label: 'Time', inputType: 'time' },
        datetime: { label: 'Date Time', inputType: 'datetime-local' },
        file: { label: 'File Upload', inputType: 'file' },
        hidden: { label: 'Hidden', inputType: 'hidden' }
    };
    
    const fieldIcons = {
        text: 'text_fields', email: 'email', password: 'lock', number: 'pin',
        tel: 'phone', url: 'link', textarea: 'notes', select: 'arrow_drop_down_circle',
        radio: 'radio_button_checked', checkbox: 'check_box', checkboxes: 'checklist',
        date: 'calendar_today', time: 'schedule', datetime: 'event',
        file: 'upload_file', hidden: 'visibility_off'
    };
    
    componentItems.forEach(item => {
        item.addEventListener('dragstart', e => {
            e.dataTransfer.setData('fieldType', item.dataset.fieldType);
        });
        item.addEventListener('click', () => {
            addField(item.dataset.fieldType);
        });
    });
    
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });
    
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const fieldType = e.dataTransfer.getData('fieldType');
        if (fieldType) addField(fieldType);
    });
    
    function addField(type, props = {}) {
        const cfg = fieldConfig[type];
        if (!cfg) return;
        
        const field = {
            id: 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
            type: type,
            inputType: cfg.inputType,
            label: props.label || cfg.label,
            name: props.name || 'field_' + (formFields.length + 1),
            required: props.required || false,
            placeholder: props.placeholder || cfg.placeholder || '',
            ...(cfg.options ? { options: [...cfg.options] } : {}),
            rows: cfg.rows || props.rows
        };
        
        formFields.push(field);
        renderFields();
        updateData();
    }
    
    function renderFields() {
        if (formFields.length === 0) {
            placeholder.style.display = 'block';
            container.innerHTML = '';
            if (sortableInstance) {
                sortableInstance.destroy();
                sortableInstance = null;
            }
            return;
        }
        
        placeholder.style.display = 'none';
        
        container.innerHTML = formFields.map((field, i) => {
            const selected = selectedIndex === i ? 'selected' : '';
            return `
                <div class="field-item ${selected}" data-index="${i}" data-field-id="${field.id}">
                    <div class="field-item-header">
                        <div class="field-item-label">
                            <span class="material-symbols-outlined field-drag-handle" data-drag="${i}">drag_indicator</span>
                            <span class="material-symbols-outlined">${fieldIcons[field.type]}</span>
                            ${field.label}
                            ${field.required ? '<span class="field-item-required">*</span>' : ''}
                        </div>
                        <span class="material-symbols-outlined field-item-delete" data-delete="${i}">delete</span>
                    </div>
                    ${renderPreview(field)}
                    <div class="field-name">Name: ${field.name}</div>
                </div>
            `;
        }).join('');
        
        container.querySelectorAll('.field-item').forEach(item => {
            item.addEventListener('click', e => {
                if (e.target.dataset.delete || e.target.closest('[data-delete]')) return;
                selectField(parseInt(item.dataset.index));
            });
        });
        
        container.querySelectorAll('[data-delete]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                deleteField(parseInt(btn.dataset.delete));
            });
        });
        
        initSortable();
    }
    
    function renderPreview(field) {
        let html = '';
        const baseClass = 'field-preview';
        const boxStyle = 'width:100%;max-width:100%;box-sizing:border-box;overflow-x:hidden;word-wrap:break-word';
        
        switch(field.inputType) {
            case 'text':
            case 'email':
            case 'password':
            case 'number':
            case 'tel':
            case 'url':
            case 'date':
            case 'time':
            case 'datetime-local':
                html = `<input type="${field.inputType}" class="${baseClass}" placeholder="${field.placeholder || ''}" disabled style="${boxStyle}">`;
                break;
            case 'textarea':
                html = `<textarea class="${baseClass}" rows="${field.rows || 4}" disabled style="${boxStyle}">${field.placeholder || ''}</textarea>`;
                break;
            case 'select':
                const selOpts = field.options || [{value:'',label:'Pilih...'}];
                html = `<select class="${baseClass}" disabled style="${boxStyle}">
                    ${selOpts.map(o => `<option value="${o.value}">${o.label}</option>`).join('')}
                </select>`;
                break;
            case 'radio':
            case 'checkboxes':
                const radOpts = field.options || [{value:'opt1',label:'Opsi 1'}, {value:'opt2',label:'Opsi 2'}];
                html = `<div style="display:flex;flex-direction:column;gap:6px;max-width:100%;overflow-x:hidden">
                    ${radOpts.map((o, idx) => `
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#4b5563;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <input type="${field.inputType}" ${field.inputType==='radio'?'name="preview_'+field.name+'_'+idx+'"':''}>
                            <span style="overflow:hidden;text-overflow:ellipsis">${o.label}</span>
                        </label>
                    `).join('')}
                `;
                break;
            case 'checkbox':
                html = `<label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#4b5563;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <input type="checkbox" ${field.labelText ? '' : 'disabled'}>
                    <span style="overflow:hidden;text-overflow:ellipsis">${field.labelText || field.label}</span>
                </label>`;
                break;
            case 'file':
                html = `<div class="${baseClass}" style="text-align:center;padding:20px;border-style:dashed;max-width:100%;overflow-x:hidden">📁 Klik atau drag file</div>`;
                break;
            case 'hidden':
                html = `<input type="hidden" disabled>`;
                break;
        }
        
        return html;
    }
    
    function selectField(index) {
        selectedIndex = index;
        renderFields();
        renderProperties(formFields[index]);
    }
    
    function renderProperties(field) {
        const isOptionType = ['select', 'radio', 'checkboxes'].includes(field.inputType);
        
        propsPanel.innerHTML = `
            <div class="prop-group">
                <label class="prop-label">Label</label>
                <input type="text" id="prop-label" class="prop-input" value="${field.label || ''}">
            </div>
            <div class="prop-group">
                <label class="prop-label">Field Name</label>
                <input type="text" id="prop-name" class="prop-input" value="${field.name || ''}">
            </div>
            ${!['select', 'radio', 'checkboxes', 'checkbox', 'file', 'hidden'].includes(field.inputType) ? `
            <div class="prop-group">
                <label class="prop-label">Placeholder</label>
                <input type="text" id="prop-placeholder" class="prop-input" value="${field.placeholder || ''}">
            </div>
            ` : ''}
            <div class="prop-group">
                <label class="prop-checkbox">
                    <input type="checkbox" id="prop-required" ${field.required ? 'checked' : ''}>
                    Wajib diisi
                </label>
            </div>
            ${isOptionType ? `
            <div class="prop-group">
                <label class="prop-label">Options (value|label, satu per baris)</label>
                <textarea id="prop-options" class="prop-input" rows="5" style="resize:vertical">${formatOptions(field.options)}</textarea>
            </div>
            ` : ''}
            ${field.inputType === 'number' ? `
            <div class="prop-group" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <div>
                    <label class="prop-label">Min</label>
                    <input type="number" id="prop-min" class="prop-input" value="${field.min || ''}">
                </div>
                <div>
                    <label class="prop-label">Max</label>
                    <input type="number" id="prop-max" class="prop-input" value="${field.max || ''}">
                </div>
            </div>
            ` : ''}
            ${field.inputType === 'textarea' ? `
            <div class="prop-group">
                <label class="prop-label">Rows</label>
                <input type="number" id="prop-rows" class="prop-input" value="${field.rows || 4}">
            </div>
            ` : ''}
        `;
        
        const listen = (id, key, fn) => {
            document.getElementById(id)?.addEventListener('input', e => {
                formFields[selectedIndex][key] = fn(e.target.value);
                renderFields();
                updateData();
            });
            document.getElementById(id)?.addEventListener('change', e => {
                if (key === 'required') {
                    formFields[selectedIndex][key] = e.target.checked;
                    renderFields();
                    updateData();
                }
            });
        };
        
        listen('prop-label', 'label', v => v);
        listen('prop-name', 'name', v => v);
        listen('prop-placeholder', 'placeholder', v => v);
        listen('prop-required', 'required', v => v);
        listen('prop-options', 'options', v => parseOptions(v));
        listen('prop-min', 'min', v => v);
        listen('prop-max', 'max', v => v);
        listen('prop-rows', 'rows', v => parseInt(v));
    }
    
    function formatOptions(opts) {
        if (!opts) return '';
        return opts.map(o => `${o.value}|${o.label}`).join('\n');
    }
    
    function parseOptions(text) {
        if (!text) return [];
        return text.split('\n').map(line => {
            const parts = line.split('|');
            return { value: parts[0]?.trim() || '', label: (parts[1] || parts[0] || '').trim() };
        }).filter(o => o.value);
    }
    
    function deleteField(index) {
        if (confirm('Hapus field ini?')) {
            formFields.splice(index, 1);
            selectedIndex = null;
            renderFields();
            propsPanel.innerHTML = `
                <div class="no-selection">
                    <span class="material-symbols-outlined">touch_app</span>
                    <p style="font-size:13px">Pilih field untuk edit</p>
                </div>
            `;
            updateData();
        }
    }
    
    function updateData() {
        formDataInput.value = JSON.stringify(formFields);
    }
    
    // Initialize Sortable for drag & drop
    let sortableInstance = null;
    
    function initSortable() {
        if (typeof Sortable === 'undefined') return;
        
        if (sortableInstance) {
            sortableInstance.destroy();
        }
        
        sortableInstance = new Sortable(container, {
            animation: 180,
            handle: '.field-drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            direction: 'vertical',
            forceFallback: true,
            onEnd: function(evt) {
                const fromIndex = evt.oldIndex;
                const toIndex = evt.newIndex;
                
                if (fromIndex === toIndex) return;
                
                const [movedField] = formFields.splice(fromIndex, 1);
                formFields.splice(toIndex, 0, movedField);
                
                selectedIndex = toIndex;
                
                renderFields();
                initSortable();
                
                selectField(toIndex);
                
                updateData();
            }
        });
    }
    
    document.getElementById('master-form-form').addEventListener('submit', function(e) {
        const formName = this.querySelector('[name="MasterForm[form_name]"]').value.trim();
        if (!formName) {
            e.preventDefault();
            alert('Masukkan nama form');
            return;
        }
        if (formFields.length === 0) {
            e.preventDefault();
            alert('Tambahkan minimal satu field');
            return;
        }
        updateData();
    });
    
    renderFields();
})();
JS;

$this->registerJs($script, \yii\web\View::POS_END);