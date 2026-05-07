<?php

use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Page Builder View
 * 
 * @var yii\web\View $this
 * @var app\models\MasterPage $page
 * @var array $availableForms
 */

// Check if $page is defined, fallback to empty values if not
if (!isset($page)) {
    $page = null;
}
if (!isset($availableForms)) {
    $availableForms = [];
}
$this->params['breadcrumbs'][] = ['label' => 'Master Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $page->title, 'url' => ['view', 'id' => $page->id]];
$this->params['breadcrumbs'][] = 'Builder';

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_END]);

$pageId = $page->id;
$initialLayout = $page->layout_json ? $page->layout_json : '{"components":[]}';

$formsList = [];
foreach ($availableForms as $form) {
    $formsList[$form->id] = [
        'id' => $form->id,
        'name' => $form->name,
        'storage' => $form->storage_type,
    ];
}
$formsJson = Json::encode($formsList);

$componentTypes = [
    ['type' => 'heading', 'label' => 'Heading', 'icon' => 'title', 'default' => ['level' => 'h2', 'text' => 'Heading Title', 'align' => 'left']],
    ['type' => 'text', 'label' => 'Text Block', 'icon' => 'notes', 'default' => ['content' => 'Enter your text here...']],
    ['type' => 'form', 'label' => 'Form', 'icon' => 'dynamic_form', 'default' => ['formId' => null, 'showTitle' => true]],
    ['type' => 'image', 'label' => 'Image', 'icon' => 'image', 'default' => ['src' => '', 'alt' => 'Image', 'size' => 'medium', 'align' => 'center']],
    ['type' => 'card', 'label' => 'Card', 'icon' => 'square', 'default' => ['title' => 'Card Title', 'content' => 'Card content goes here...', 'showShadow' => true]],
    ['type' => 'spacer', 'label' => 'Spacer', 'icon' => 'space_bar', 'default' => ['height' => 'md']],
    ['type' => 'divider', 'label' => 'Divider', 'icon' => 'horizontal_rule', 'default' => ['style' => 'solid', 'color' => '#e2e8f0']],
    ['type' => 'button', 'label' => 'Button', 'icon' => 'smart_button', 'default' => ['text' => 'Click Me', 'url' => '#', 'style' => 'primary', 'size' => 'md', 'align' => 'center']],
    ['type' => 'grid', 'label' => 'Grid (2 Col)', 'icon' => 'grid_view', 'default' => ['columns' => 2, 'gap' => 'md', 'items' => []]],
    ['type' => 'video', 'label' => 'Video', 'icon' => 'videocam', 'default' => ['url' => '', 'aspect' => '16:9']],
    ['type' => 'table', 'label' => 'Table', 'icon' => 'table_chart', 'default' => ['headers' => ['Column 1', 'Column 2'], 'rows' => [['Data 1', 'Data 2']]]],
    ['type' => 'tabs', 'label' => 'Tabs', 'icon' => 'tab', 'default' => ['tabs' => [['label' => 'Tab 1', 'content' => 'Content 1'], ['label' => 'Tab 2', 'content' => 'Content 2']]]],
];
$componentsJson = Json::encode($componentTypes);

$alignOptions = ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'];
$sizeOptions = ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large', 'full' => 'Full Width'];
$heightOptions = ['sm' => 'Small (16px)', 'md' => 'Medium (32px)', 'lg' => 'Large (48px)', 'xl' => 'Extra Large (64px)'];
$styleOptions = ['primary' => 'Primary', 'secondary' => 'Secondary', 'outline' => 'Outline', 'ghost' => 'Ghost'];
?>

<style>
    .page-builder {
        height: calc(100vh - 60px);
        display: flex;
        flex-direction: column;
        background: #f8fafc;
    }

    .builder-toolbar {
        height: 56px;
        background: white;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        padding: 0 16px;
        gap: 12px;
        flex-shrink: 0;
    }

    .builder-main {
        flex: 1;
        display: flex;
        overflow: hidden;
    }

    .builder-sidebar-left {
        width: 260px;
        background: white;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .builder-canvas {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        background: #f1f5f9;
    }

    .builder-sidebar-right {
        width: 300px;
        background: white;
        border-left: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        overflow-y: auto;
    }

    .component-palette {
        padding: 16px;
    }

    .component-palette h3 {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 12px;
        letter-spacing: 0.5px;
    }

    .component-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .component-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 12px 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: grab;
        transition: all 0.15s ease;
    }

    .component-item:hover {
        border-color: #6366f1;
        background: #eef2ff;
        transform: translateY(-1px);
    }

    .component-item:active {
        cursor: grabbing;
    }

    .component-item .material-symbols-outlined {
        font-size: 24px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .component-item span {
        font-size: 11px;
        color: #64748b;
        text-align: center;
    }

    .canvas-area {
        max-width: 900px;
        margin: 0 auto;
        min-height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        padding: 24px;
        position: relative;
    }

    .canvas-drop-zone {
        min-height: 400px;
        border: 2px dashed #e2e8f0;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .canvas-drop-zone.drag-over {
        border-color: #6366f1;
        background: #eef2ff;
    }

    .canvas-drop-zone.empty {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #94a3b8;
    }

    .canvas-drop-zone.empty .material-symbols-outlined {
        font-size: 48px;
        margin-bottom: 8px;
    }

    .canvas-block {
        position: relative;
        padding: 16px;
        margin-bottom: 12px;
        border: 2px solid transparent;
        border-radius: 8px;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .canvas-block:hover {
        border-color: #cbd5e1;
    }

    .canvas-block.selected {
        border-color: #6366f1;
        background: #eef2ff;
    }

    .canvas-block-handle {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .canvas-block:hover .canvas-block-handle {
        opacity: 1;
    }

    .canvas-block-handle button {
        width: 28px;
        height: 28px;
        border: none;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: background 0.15s ease;
    }

    .canvas-block-handle button:hover {
        background: #f1f5f9;
    }

    .canvas-block-handle button.delete-btn:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    .block-content {
        pointer-events: none;
    }

    .block-content>* {
        pointer-events: auto;
    }

    .properties-panel {
        padding: 16px;
    }

    .properties-panel h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    .property-group {
        margin-bottom: 16px;
    }

    .property-group label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 6px;
    }

    .property-group input,
    .property-group select,
    .property-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        transition: border-color 0.15s ease;
    }

    .property-group input:focus,
    .property-group select:focus,
    .property-group textarea:focus {
        outline: none;
        border-color: #6366f1;
    }

    .property-row {
        display: flex;
        gap: 8px;
    }

    .device-buttons {
        display: flex;
        background: #f1f5f9;
        border-radius: 8px;
        padding: 4px;
    }

    .device-btn {
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        color: #64748b;
        transition: all 0.15s ease;
    }

    .device-btn.active {
        background: white;
        color: #1e293b;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .device-btn:hover:not(.active) {
        color: #1e293b;
    }

    .canvas-desktop {
        max-width: 100%;
    }

    .canvas-tablet {
        max-width: 768px;
        margin: 0 auto;
    }

    .canvas-mobile {
        max-width: 375px;
        margin: 0 auto;
    }

    .no-selection {
        padding: 24px;
        text-align: center;
        color: #94a3b8;
    }

    .no-selection .material-symbols-outlined {
        font-size: 48px;
        margin-bottom: 8px;
    }

    .form-select-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .form-select-item {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .form-select-item:hover {
        background: #f8fafc;
    }

    .form-select-item.selected {
        background: #eef2ff;
        border-color: #6366f1;
    }

    .form-select-item:last-child {
        border-bottom: none;
    }

    .sortable-ghost {
        opacity: 0.4;
        background: #eef2ff;
    }

    .sortable-drag {
        background: white;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
</style>

<div class="page-builder">
    <div class="builder-toolbar">
        <?= Html::a('<span class="material-symbols-outlined">arrow_back</span>', ['view', 'id' => $pageId], ['class' => 'btn-toolbar', 'title' => 'Back to Page']) ?>

        <div class="flex-1">
            <span class="font-semibold text-slate-800"><?= Html::encode($page->title) ?></span>
            <span class="text-slate-400 mx-2">|</span>
            <span class="text-sm text-slate-500">Page Builder</span>
        </div>

        <div class="device-buttons">
            <button class="device-btn active" data-device="desktop">Desktop</button>
            <button class="device-btn" data-device="tablet">Tablet</button>
            <button class="device-btn" data-device="mobile">Mobile</button>
        </div>

        <?= Html::button('<span class="material-symbols-outlined text-sm mr-1">visibility</span> Preview', [
            'class' => 'px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium flex items-center gap-1',
            'id' => 'preview-btn',
        ]) ?>

        <?= Html::button('<span class="material-symbols-outlined text-sm mr-1">save</span> Save', [
            'class' => 'px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-1',
            'id' => 'save-btn',
        ]) ?>
    </div>

    <div class="builder-main">
        <div class="builder-sidebar-left">
            <div class="component-palette">
                <h3>Components</h3>
                <div class="component-list" id="component-list">
                    <?php foreach ($componentTypes as $comp): ?>
                        <div class="component-item" draggable="true" data-type="<?= $comp['type'] ?>">
                            <span class="material-symbols-outlined"><?= $comp['icon'] ?></span>
                            <span><?= $comp['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="builder-canvas">
            <div class="canvas-area canvas-desktop" id="canvas-area">
                <div class="canvas-drop-zone" id="canvas-drop-zone">
                    <div class="empty" id="empty-state">
                        <span class="material-symbols-outlined">add_box</span>
                        <p>Drag components here to build your page</p>
                    </div>
                    <div id="canvas-content"></div>
                </div>
            </div>
        </div>

        <div class="builder-sidebar-right">
            <div class="properties-panel" id="properties-panel">
                <div class="no-selection" id="no-selection">
                    <span class="material-symbols-outlined">touch_app</span>
                    <p>Pilih komponen di canvas untuk edit properties</p>
                </div>
                <div id="properties-content" class="hidden"></div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="page-id" value="<?= $pageId ?>">
<input type="hidden" id="layout-json" value="<?= Html::encode($initialLayout) ?>">

<?php
$script = <<<JS
const pageId = document.getElementById('page-id').value;
const layoutJsonInput = document.getElementById('layout-json');
const canvasContent = document.getElementById('canvas-content');
const emptyState = document.getElementById('empty-state');
const propertiesContent = document.getElementById('properties-content');
const noSelection = document.getElementById('no-selection');
const canvasDropZone = document.getElementById('canvas-drop-zone');
const saveBtn = document.getElementById('save-btn');
const previewBtn = document.getElementById('preview-btn');

let layout = JSON.parse(layoutJsonInput.value || '{"components":[]}');
let selectedBlockId = null;

const components = $componentsJson;
const formsList = $formsJson;

function generateId() {
    return 'block_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function renderCanvas() {
    canvasContent.innerHTML = '';
    
    if (!layout.components || layout.components.length === 0) {
        emptyState.classList.remove('hidden');
        canvasContent.classList.add('hidden');
    } else {
        emptyState.classList.add('hidden');
        canvasContent.classList.remove('hidden');
        
        layout.components.forEach((comp, index) => {
            const block = createBlockElement(comp, index);
            canvasContent.appendChild(block);
        });
    }
    
    initSortable();
}

function createBlockElement(comp, index) {
    const block = document.createElement('div');
    block.className = 'canvas-block' + (selectedBlockId === comp.id ? ' selected' : '');
    block.dataset.id = comp.id;
    block.dataset.index = index;
    
    block.innerHTML = `
        <div class="canvas-block-handle">
            <button class="move-btn" title="Move"><span class="material-symbols-outlined text-base">drag_indicator</span></button>
            <button class="duplicate-btn" title="Duplicate"><span class="material-symbols-outlined text-base">content_copy</span></button>
            <button class="delete-btn" title="Delete"><span class="material-symbols-outlined text-base">delete</span></button>
        </div>
        <div class="block-content">
            ${renderBlockContent(comp)}
        </div>
    `;
    
    block.addEventListener('click', function(e) {
        if (!e.target.closest('button')) {
            selectBlock(comp.id);
        }
    });
    
    block.querySelector('.delete-btn').addEventListener('click', function() {
        deleteBlock(comp.id);
    });
    
    block.querySelector('.duplicate-btn').addEventListener('click', function() {
        duplicateBlock(comp.id);
    });
    
    return block;
}

function renderBlockContent(comp) {
    switch(comp.type) {
        case 'heading':
            const Tag = comp.props.level || 'h2';
            const align = comp.props.align || 'left';
            return '<' + Tag + ' style="text-align:' + align + ';margin:0;">' + (comp.props.text || 'Heading') + '</' + Tag + '>';
        
        case 'text':
            return '<div style="color:#475569;line-height:1.6;">' + (comp.props.content || 'Text block content') + '</div>';
        
        case 'form':
            const formName = comp.props.formId && formsList[comp.props.formId] ? formsList[comp.props.formId].name : 'Select Form';
            return '<div class="p-4 bg-slate-50 rounded-lg border border-slate-200"><span class="material-symbols-outlined text-slate-400 mr-2">dynamic_form</span>' + formName + '</div>';
        
        case 'image':
            if (comp.props.src) {
                const width = {sm:'150px',md:'250px',lg:'350px',full:'100%'}[comp.props.size] || '250px';
                return '<img src="' + comp.props.src + '" alt="' + (comp.props.alt || '') + '" style="width:' + width + ';display:block;margin:' + (comp.props.align === 'center' ? '0 auto' : comp.props.align === 'right' ? '0 0 0 auto' : '0') + '">';
            }
            return '<div class="p-8 bg-slate-100 rounded-lg text-center text-slate-400"><span class="material-symbols-outlined text-4xl">image</span><p>No image set</p></div>';
        
        case 'card':
            const shadow = comp.props.showShadow ? 'box-shadow:0 4px6px-1px rgba(0,0,0,0.1)' : '';
            return '<div style="border:1px solid #e2e8f0;border-radius:8px;padding:16px;' + shadow + '"><h4 style="margin:0 0 8px;font-weight:600">' + (comp.props.title || 'Card Title') + '</h4><p style="margin:0;color:#64748b">' + (comp.props.content || 'Card content') + '</p></div>';
        
        case 'spacer':
            const height = {sm:'16px',md:'32px',lg:'48px',xl:'64px'}[comp.props.height] || '32px';
            return '<div style="height:' + height + ';background:#f8fafc;border-radius:4px;"><span style="font-size:10px;color:#94a3b8;padding:4px;">Spacer</span></div>';
        
        case 'divider':
            return '<hr style="border:none;border-top:1px solid ' + (comp.props.color || '#e2e8f0') + ';margin:8px 0;">';
        
        case 'button':
            const btnStyle = comp.props.style || 'primary';
            const colors = {primary:'background:#6366f1;color:white',secondary:'background:#64748b;color:white',outline:'border:1px solid #6366f1;color:#6366f1;background:transparent',ghost:'color:#6366f1;background:transparent'};
            return '<div style="text-align:' + (comp.props.align || 'center') + '"><button style="' + (colors[btnStyle] || colors.primary) + ';border-radius:6px;padding:10px 20px;border:none;cursor:pointer">' + (comp.props.text || 'Button') + '</button></div>';
        
        case 'grid':
            return '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;padding:16px;background:#f8fafc;border-radius:8px;"><div style="text-align:center;color:#94a3b8"><span class="material-symbols-outlined">add</span></div><div style="text-align:center;color:#94a3b8"><span class="material-symbols-outlined">add</span></div></div>';
        
        case 'video':
            if (comp.props.url) {
                return '<div style="aspect-ratio:16/9;background:#000;border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;">Video: ' + comp.props.url + '</div>';
            }
            return '<div class="p-8 bg-slate-100 rounded-lg text-center text-slate-400"><span class="material-symbols-outlined text-4xl">videocam</span><p>Enter video URL</p></div>';
        
        case 'table':
            const headers = comp.props.headers || ['Column 1', 'Column 2'];
            const rows = comp.props.rows || [['Data 1', 'Data 2']];
            let html = '<table style="width:100%;border-collapse:collapse"><thead><tr>';
            headers.forEach(h => html += '<th style="padding:10px;background:#f1f5f9;border:1px solid #e2e8f0;text-align:left">' + h + '</th>');
            html += '</tr></thead><tbody>';
            rows.forEach(row => {
                html += '<tr>';
                row.forEach(cell => html += '<td style="padding:10px;border:1px solid #e2e8f0">' + cell + '</td>');
                html += '</tr>';
            });
            html += '</tbody></table>';
            return html;
        
        case 'tabs':
            const tabs = comp.props.tabs || [{label:'Tab 1',content:'Content 1'}];
            let tabHtml = '<div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden"><div style="display:flex;background:#f8fafc;border-bottom:1px solid #e2e8f0">';
            tabs.forEach((t,i) => tabHtml += '<div style="padding:12px 16px;' + (i===0?'background:white;border-bottom:2px solid #6366f1':'') + '">' + t.label + '</div>');
            tabHtml += '</div><div style="padding:16px">';
            if (tabs[0]) tabHtml += tabs[0].content;
            tabHtml += '</div></div>';
            return tabHtml;
        
        default:
            return '<div>Unknown component: ' + comp.type + '</div>';
    }
}

function selectBlock(blockId) {
    selectedBlockId = blockId;
    renderCanvas();
    renderProperties(blockId);
}

function renderProperties(blockId) {
    const comp = layout.components.find(c => c.id === blockId);
    if (!comp) return;
    
    noSelection.classList.add('hidden');
    propertiesContent.classList.remove('hidden');
    
    let html = '<h3>Properties - ' + comp.type + '</h3>';
    
    switch(comp.type) {
        case 'heading':
            html += renderPropertyGroup('Level', '<select id="prop-level"><option value="h1">H1</option><option value="h2">H2</option><option value="h3">H3</option><option value="h4">H4</option><option value="h5">H5</option><option value="h6">H6</option></select>', 'level');
            html += renderPropertyGroup('Text', '<input type="text" id="prop-text" value="' + (comp.props.text || '') + '">', 'text');
            html += renderPropertyGroup('Alignment', '<select id="prop-align"><?php foreach($alignOptions as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?></select>', 'align');
            break;
            
        case 'text':
            html += renderPropertyGroup('Content', '<textarea id="prop-content" rows="4">' + (comp.props.content || '') + '</textarea>', 'content');
            break;
            
        case 'form':
            html += '<div class="property-group"><label>Select Form</label><div class="form-select-list" id="form-select-list">';
            Object.values(formsList).forEach(f => {
                html += '<div class="form-select-item' + (comp.props.formId === f.id ? ' selected' : '') + '" data-form-id="' + f.id + '"><strong>' + f.name + '</strong><span class="text-xs text-slate-500 ml-2">(' + f.storage + ')</span></div>';
            });
            html += '</div></div>';
            html += renderPropertyGroup('Show Title', '<input type="checkbox" id="prop-showTitle" ' + (comp.props.showTitle ? 'checked' : '') + '>', 'showTitle');
            break;
            
        case 'image':
            html += renderPropertyGroup('Image URL', '<input type="text" id="prop-src" value="' + (comp.props.src || '') + '">', 'src');
            html += renderPropertyGroup('Alt Text', '<input type="text" id="prop-alt" value="' + (comp.props.alt || '') + '">', 'alt');
            html += renderPropertyGroup('Size', '<select id="prop-size"><?php foreach($sizeOptions as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?></select>', 'size');
            html += renderPropertyGroup('Alignment', '<select id="prop-align"><?php foreach($alignOptions as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?></select>', 'align');
            break;
            
        case 'card':
            html += renderPropertyGroup('Title', '<input type="text" id="prop-title" value="' + (comp.props.title || '') + '">', 'title');
            html += renderPropertyGroup('Content', '<textarea id="prop-content" rows="3">' + (comp.props.content || '') + '</textarea>', 'content');
            html += renderPropertyGroup('Show Shadow', '<input type="checkbox" id="prop-showShadow" ' + (comp.props.showShadow ? 'checked' : '') + '>', 'showShadow');
            break;
            
        case 'spacer':
            html += renderPropertyGroup('Height', '<select id="prop-height"><?php foreach($heightOptions as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?></select>', 'height');
            break;
            
        case 'divider':
            html += renderPropertyGroup('Color', '<input type="color" id="prop-color" value="' + (comp.props.color || '#e2e8f0') + '">', 'color');
            break;
            
        case 'button':
            html += renderPropertyGroup('Text', '<input type="text" id="prop-text" value="' + (comp.props.text || '') + '">', 'text');
            html += renderPropertyGroup('URL', '<input type="text" id="prop-url" value="' + (comp.props.url || '') + '">', 'url');
            html += renderPropertyGroup('Style', '<select id="prop-style"><?php foreach($styleOptions as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?></select>', 'style');
            html += renderPropertyGroup('Alignment', '<select id="prop-align"><?php foreach($alignOptions as $k=>$v) echo "<option value=\"$k\">$v</option>"; ?></select>', 'align');
            break;
            
        case 'video':
            html += renderPropertyGroup('Video URL', '<input type="text" id="prop-url" value="' + (comp.props.url || '') + '" placeholder="YouTube or MP4 URL">', 'url');
            break;
            
        default:
            html += '<p class="text-sm text-slate-500">No properties available</p>';
    }
    
    propertiesContent.innerHTML = html;
    
    // Attach event listeners
    attachPropertyListeners(comp.type, comp.props);
}

function renderPropertyGroup(label, input, propKey) {
    return '<div class="property-group"><label>' + label + '</label>' + input.replace('value="', 'value="' + (selectedBlockProps ? selectedBlockProps[propKey] : '')) + '</div>';
}

let selectedBlockProps = null;

function attachPropertyListeners(type, props) {
    selectedBlockProps = props;
    
    const comp = layout.components.find(c => c.id === selectedBlockId);
    if (!comp) return;
    
    const propMap = {
        'heading': {level:'prop-level', text:'prop-text', align:'prop-align'},
        'text': {content:'prop-content'},
        'image': {src:'prop-src', alt:'prop-alt', size:'prop-size', align:'prop-align'},
        'card': {title:'prop-title', content:'prop-content', showShadow:'prop-showShadow'},
        'spacer': {height:'prop-height'},
        'divider': {color:'prop-color'},
        'button': {text:'prop-text', url:'prop-url', style:'prop-style', align:'prop-align'},
        'video': {url:'prop-url'},
    };
    
    const map = propMap[type] || {};
    Object.keys(map).forEach(key => {
        const el = document.getElementById(map[key]);
        if (el) {
            if (el.type === 'checkbox') {
                el.addEventListener('change', function() {
                    comp.props[key] = this.checked;
                    saveLayout();
                    renderCanvas();
                    renderProperties(selectedBlockId);
                });
            } else {
                el.addEventListener('input', function() {
                    comp.props[key] = this.value;
                    saveLayout();
                    renderCanvas();
                });
                el.addEventListener('change', function() {
                    renderProperties(selectedBlockId);
                });
            }
        }
    });
    
    // Form select
    if (type === 'form') {
        document.querySelectorAll('.form-select-item').forEach(item => {
            item.addEventListener('click', function() {
                const formId = parseInt(this.dataset.formId);
                comp.props.formId = formId;
                saveLayout();
                renderCanvas();
                renderProperties(selectedBlockId);
            });
        });
        
        const showTitleEl = document.getElementById('prop-showTitle');
        if (showTitleEl) {
            showTitleEl.addEventListener('change', function() {
                comp.props.showTitle = this.checked;
                saveLayout();
                renderCanvas();
            });
        }
    }
}

function deleteBlock(blockId) {
    layout.components = layout.components.filter(c => c.id !== blockId);
    selectedBlockId = null;
    saveLayout();
    renderCanvas();
    propertiesContent.innerHTML = '';
    propertiesContent.classList.add('hidden');
    noSelection.classList.remove('hidden');
}

function duplicateBlock(blockId) {
    const comp = layout.components.find(c => c.id === blockId);
    if (comp) {
        const newComp = JSON.parse(JSON.stringify(comp));
        newComp.id = generateId();
        layout.components.push(newComp);
        saveLayout();
        renderCanvas();
    }
}

function saveLayout() {
    layoutJsonInput.value = JSON.stringify(layout);
}

function initSortable() {
    if (typeof Sortable === 'undefined') return;
    
    new Sortable(canvasContent, {
        animation: 150,
        handle: '.move-btn',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            const item = layout.components.splice(evt.oldIndex, 1)[0];
            layout.components.splice(evt.newIndex, 0, item);
            saveLayout();
        }
    });
}

// Component palette drag & drop
const componentList = document.getElementById('component-list');
componentList.querySelectorAll('.component-item').forEach(item => {
    item.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('component-type', this.dataset.type);
    });
});

canvasDropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('drag-over');
});

canvasDropZone.addEventListener('dragleave', function(e) {
    this.classList.remove('drag-over');
});

canvasDropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    
    const type = e.dataTransfer.getData('component-type');
    const compDef = components.find(c => c.type === type);
    
    if (compDef) {
        const newComp = {
            id: generateId(),
            type: type,
            props: JSON.parse(JSON.stringify(compDef.default))
        };
        
        layout.components.push(newComp);
        saveLayout();
        renderCanvas();
        selectBlock(newComp.id);
    }
});

// Device buttons
document.querySelectorAll('.device-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const canvasArea = document.getElementById('canvas-area');
        canvasArea.className = 'canvas-area canvas-' + this.dataset.device;
    });
});

// Save button
saveBtn.addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-sm mr-1">sync</span> Saving...';
    
    fetch('/master-page/save-layout', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'page_id=' + pageId + '&layout_json=' + encodeURIComponent(JSON.stringify(layout))
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm mr-1">save</span> Save';
        if (data.success) {
            btn.className = 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium flex items-center gap-1';
            setTimeout(() => {
                btn.className = 'px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium flex items-center gap-1';
            }, 2000);
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Preview button
previewBtn.addEventListener('click', function() {
    window.open('/page/view?id=' + pageId + '&preview=1', '_blank');
});

// Initial render
renderCanvas();
JS;
$this->registerJs($script);
