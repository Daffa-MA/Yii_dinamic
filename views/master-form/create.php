<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\MasterPage;

$this->title = 'Buat Form Baru';
$this->params['breadcrumbs'][] = ['label' => 'Master Forms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$tableList = [];
$pages = [];
$pageList = [];
$resolvedTargetTableId = 0;
/** @var string[] $formNameErrors */
$formNameErrors = $model->getErrors('form_name');
/** @var string[] $allFormErrors */
$allFormErrors = $model->getFirstErrors();
if ($model->hasAttribute('db_table_id') && !empty($model->getAttribute('db_table_id'))) {
    $resolvedTargetTableId = (int)$model->getAttribute('db_table_id');
}
if ($resolvedTargetTableId <= 0 && !empty($model->table_id)) {
    $resolvedTargetTableId = (int)$model->table_id;
}

$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js', ['position' => \yii\web\View::POS_END]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css');
?>

<style>
:root {
    --tpl-bg: #ffffff;
    --tpl-bg-secondary: #f5f5f3;
    --tpl-text-primary: #111110;
    --tpl-text-secondary: #6b6b68;
    --tpl-accent: #534AB7;
    --tpl-accent-light: #EEEDFE;
    --tpl-radius-md: 8px;
    --tpl-radius-lg: 12px;
    --tpl-radius-xl: 16px;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: 400;
    font-style: normal;
    font-size: 24px;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    word-wrap: normal;
    direction: ltr;
}

body.dashboard-main-page {
    overflow: hidden;
}

.dashboard-main {
    height: 100vh;
    overflow: hidden;
    box-sizing: border-box;
    padding-bottom: 0;
}

.dashboard-main > .container-fluid {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.page-builder {
    flex: 1 1 auto;
    height: auto;
    min-height: 0;
    display: flex;
    background: #0f172a;
    overflow: hidden;
}

.builder-sidebar-left {
    width: 280px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    border-right: 1px solid #e5e7eb;
    min-height: 0;
    overflow-y: auto;
}

.builder-canvas {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f1f5f9;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
}

.canvas-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.canvas-toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.canvas-toolbar-right {
    font-size: 12px;
}

.canvas-device-switcher {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 4px;
    border-radius: 8px;
}

.device-btn {
    border: none;
    background: transparent;
    color: #64748b;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.device-btn:hover {
    background: #e2e8f0;
    color: #475569;
}

.device-btn.active {
    background: #ffffff;
    color: #3b82f6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.device-btn svg {
    width: 18px;
    height: 18px;
}

.canvas-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
    padding: 20px;
    min-height: 0;
    overflow-x: auto;
    overflow-y: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
}

.canvas-frame {
    width: 100%;
    max-width: 100%;
    height: 100%;
    min-height: 0;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transition: width 0.25s ease, max-width 0.25s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.canvas-frame.device-tablet {
    width: 820px;
    max-width: 820px;
}

.canvas-frame.device-mobile {
    width: 390px;
    max-width: 390px;
}

.canvas-content {
    flex: 1 1 auto;
    min-height: 0;
    padding: 20px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.canvas-content > form {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.canvas-content > form > div:first-of-type {
    flex-shrink: 0;
}

.builder-properties {
    width: 380px;
    height: 100%;
    background: #ffffff;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    min-height: 0;
    overflow: hidden;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 700;
    color: #1f2937;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.component-item {
    padding: 14px 16px;
    margin: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #f9fafb;
    cursor: grab;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #374151;
}

.component-item:hover {
    background: #f3f4f6;
    border-color: #6366f1;
    transform: translateX(4px);
}

.component-item:active {
    cursor: grabbing;
}

.component-item .material-symbols-outlined,
.component-item .ti {
    font-size: 22px;
    color: #6366f1;
}

.component-item span:last-child {
    font-size: 14px;
    font-weight: 500;
}

.component-section-title {
    padding: 16px 20px 8px;
    font-size: 10px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.canvas-drop-zone {
    flex: 1 1 auto;
    height: 100%;
    min-height: 0;
    padding: 16px;
    background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    overflow: hidden;
}

#fields-container {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 4px;
}

.canvas-drop-zone.drag-over {
    border-color: #6366f1;
    background: #eef2ff;
}

.field-item {
    position: relative;
    padding: 14px 16px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.field-item:hover {
    border-color: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
}

.field-item.selected {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15), 0 8px 24px rgba(99, 102, 241, 0.2);
}

.field-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.field-item-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
    flex: 1;
    min-width: 0;
}

.field-item-label .material-symbols-outlined,
.field-item-label .ti {
    font-size: 18px;
    color: #6366f1;
    flex-shrink: 0;
}

.field-item-required {
    color: #ef4444;
    font-size: 12px;
    font-weight: 600;
    background: #fef2f2;
    padding: 2px 8px;
    border-radius: 999px;
}

.field-actions {
    display: none;
    gap: 6px;
    align-items: center;
}

.field-item:hover .field-actions {
    display: flex;
}

.field-actions-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    color: #64748b;
}

.field-actions-btn:hover {
    background: #f1f5f9;
    border-color: #6366f1;
    color: #6366f1;
}

.field-actions-btn.delete:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
}

.field-actions-btn .material-symbols-outlined,
.field-actions-btn .ti {
    font-size: 16px;
}

.field-drag-handle {
    cursor: grab;
    color: #cbd5e1;
    padding: 4px;
    border-radius: 6px;
    transition: all 0.15s;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.field-drag-handle:hover {
    color: #6366f1;
    background: #eef2ff;
}

.field-drag-handle:active {
    cursor: grabbing;
}

.field-drag-handle .material-symbols-outlined,
.field-drag-handle .ti {
    font-size: 18px;
}

.field-preview {
    padding: 12px 14px;
    background: #f9fafb;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    font-size: 13px;
    color: #64748b;
    margin-top: 10px;
}

.field-preview input,
.field-preview select,
.field-preview textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    color: #374151;
    background: #ffffff;
    box-sizing: border-box;
}

.field-preview input:focus,
.field-preview select:focus,
.field-preview textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.field-name {
    margin-top: 8px;
    font-size: 11px;
    color: #94a3b8;
    padding-left: 2px;
}

.field-name {
    margin-top: 8px;
    font-size: 11px;
    color: #94a3b8;
}

.block-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #eff6ff;
    color: #1e40af;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    text-transform: uppercase;
}

.field-drag-handle {
    cursor: grab;
    color: #cbd5e1;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.15s;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.field-drag-handle:hover {
    color: #6366f1;
    background: #eef2ff;
}

.field-drag-handle:active {
    cursor: grabbing;
}

.sortable-ghost {
    opacity: 0.4;
    background: #e0e7ff !important;
    border: 2px dashed #6366f1 !important;
}

.sortable-chosen {
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.prop-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
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

.prop-section {
    border-bottom: 1px solid #f1f5f9;
    padding: 0;
}

.prop-section-title {
    padding: 14px 20px 10px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.prop-group {
    padding: 12px 20px;
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
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.prop-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s;
    box-sizing: border-box;
}

.prop-select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
    accent-color: #6366f1;
}

.prop-options-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.prop-option-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 32px;
    gap: 8px;
    align-items: center;
}

.prop-option-remove,
.prop-option-add {
    border: 1px solid #e5e7eb;
    background: #ffffff;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.prop-option-remove {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.prop-option-remove:hover {
    background: #fef2f2;
    border-color: #ef4444;
    color: #ef4444;
}

.prop-option-add {
    margin-top: 10px;
    width: 100%;
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 600;
}

.prop-option-add:hover {
    background: #eef2ff;
    border-color: #6366f1;
    color: #4f46e5;
}

.no-selection {
    padding: 40px 20px;
    text-align: center;
    color: #94a3b8;
}

.no-selection .material-symbols-outlined {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.4;
}

.builder-toolbar {
    height: 56px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    flex-shrink: 0;
    border-left: 1px solid #e5e7eb;
}

.builder-toolbar-title {
    font-weight: 700;
    color: #1f2937;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.builder-toolbar-title .material-symbols-outlined {
    color: #6366f1;
}

.builder-toolbar-actions {
    display: flex;
    gap: 12px;
}

.btn-cancel {
    padding: 10px 24px;
    background: #f9fafb;
    color: #6b7280;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s;
    text-decoration: none;
}

.btn-cancel:hover {
    background: #f3f4f6;
    color: #374151;
}

.btn-save {
    padding: 10px 24px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.15s;
}

.btn-save:hover {
    background: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.builder-workspace {
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.builder-workspace-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    background: rgba(255, 255, 255, 0.92);
}

.builder-workspace-hint {
    font-size: 12px;
    color: #64748b;
    text-align: right;
}

.builder-canvas-surface {
    flex: 1 1 auto;
    min-height: 0;
    padding: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    overflow: hidden;
}

.btn-action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.btn-action:hover {
    background: #f1f5f9;
    color: #64748b;
}

.btn-action.delete:hover {
    background: #fef2f2;
    color: #ef4444;
}

#properties-panel {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    gap: 0;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 16px;
}

#properties-panel > * {
    flex-shrink: 0;
}

.prop-tabs {
    display: flex;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.prop-tab-btn {
    flex: 1;
    padding: 12px;
    border: none;
    background: none;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.15s;
}

.prop-tab-btn:hover {
    background: #f1f5f9;
    color: #374151;
}

.prop-tab-btn.active {
    color: #6366f1;
    border-bottom-color: #6366f1;
}

.prop-tab-content {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

#properties-code-tab {
    min-height: 0;
}

#monaco-editor-container {
    flex: 1 1 auto;
    min-height: 0 !important;
}

.prop-tab-content:not(.active) {
    display: none;
}

.code-editor-header {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
    padding: 10px 15px;
    background: #1e293b;
    border-bottom: 1px solid #334155;
}

.code-scope-buttons {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.code-scope-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #475569;
    background: transparent;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
}

.code-scope-btn:hover {
    background: #334155;
    color: #ffffff;
}

.code-scope-btn.active {
    background: #6366f1;
    border-color: #6366f1;
    color: #ffffff;
}

.code-editor-tools {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.code-lang-buttons {
    display: flex;
    gap: 4px;
}

.code-mode-hint {
    padding: 8px 12px;
    background: #0f172a;
    color: #cbd5e1;
    border-bottom: 1px solid #334155;
    font-size: 11px;
    line-height: 1.5;
}

.code-lang-btn {
    padding: 5px 12px;
    border-radius: 4px;
    border: 1px solid #475569;
    background: transparent;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.code-lang-btn:hover {
    background: #334155;
    color: white;
}

.code-lang-btn.active {
    background: #6366f1;
    border-color: #6366f1;
    color: white;
}

.btn-reset-base {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #475569;
    background: transparent;
    color: #94a3b8;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-reset-base:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: white;
}

        .is-hidden {
            display: none !important;
        }

        .field-badge-fk {
            background: #fef3c7;
            color: #92400e;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 6px;
        }

        .field-badge-auto {
            background: #e0e7ff;
            color: #3730a3;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 6px;
        }

        .fk-options-loading {
            font-size: 11px;
            color: #64748b;
            padding: 4px 8px;
        }
</style>

<!-- FORM BUILDER INTERFACE -->
<div class="page-builder">
    <!-- LEFT PANEL: Field Types -->
    <div class="builder-sidebar-left">
        <div class="sidebar-header">
            <span class="material-symbols-outlined" style="font-size:20px;vertical-align:middle;margin-right:8px">widgets</span>
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
            <span>Checkboxes</span>
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

    <!-- CANVAS: Main Area -->
    <div class="builder-canvas">
        <div class="canvas-toolbar">
            <div class="canvas-toolbar-left">
                <span class="material-symbols-outlined" style="color:#6366f1">dashboard</span>
                <span style="font-weight:600;color:#1e2937;font-size:14px;">Form Builder</span>
            </div>
            <div class="canvas-device-switcher">
                <button type="button" class="device-btn active" data-device="desktop" onclick="setDevice('desktop')" title="Desktop">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h6v2H8v2h8v-2h-2v-2h6c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H3V4h18v12z"></path></svg>
                </button>
                <button type="button" class="device-btn" data-device="tablet" onclick="setDevice('tablet')" title="Tablet">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H3V6h18v12zM7 20h10v-2H7v2z"></path></svg>
                </button>
                <button type="button" class="device-btn" data-device="mobile" onclick="setDevice('mobile')" title="Mobile">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V3h10v16z"></path></svg>
                </button>
            </div>
            <div class="canvas-toolbar-right">
                <span style="color:#94a3b8;font-size:12px;">Tambah field dengan drag atau klik</span>
            </div>
        </div>

        <div class="canvas-wrapper">
            <div id="canvas-frame" class="canvas-frame device-desktop">
                <div class="canvas-content">
                    <form id="master-form-form" method="post">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                        <input type="hidden" name="MasterForm[form_data]" id="form-data-input" value="[]">
                        <input type="hidden" name="MasterForm[use_custom_code]" id="use-custom-code-input" value="0">
                        <textarea name="MasterForm[custom_html]" id="custom-html-input" style="display:none;"></textarea>
                        <textarea name="MasterForm[custom_css]" id="custom-css-input" style="display:none;"></textarea>
                        <textarea name="MasterForm[custom_js]" id="custom-js-input" style="display:none;"></textarea>
                        <input type="hidden" name="MasterForm[table_id]" id="table-id-input" value="<?= $resolvedTargetTableId > 0 ? $resolvedTargetTableId : '' ?>">
                        <?php if (!empty($model->id)): ?>
                        <input type="hidden" name="MasterForm[form_id]" id="form-id-input" value="<?= $model->id ?>">
                        <?php endif; ?>
                        <?php if (!empty($allFormErrors)): ?>
                        <div style="margin:16px 20px 0;padding:12px 14px;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:10px;font-size:13px;line-height:1.6;">
                            <?php foreach ($allFormErrors as $errorMessage): ?>
                            <div><?= Html::encode($errorMessage) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display:flex;gap:12px;padding:16px 20px;align-items:center;flex-wrap:wrap;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <select id="table-selector" class="prop-input" style="width:200px;">
                                <option value="">-- Pilih Tabel --</option>
                            </select>
                            <div style="display:flex;flex-direction:column;flex:1;max-width:300px;gap:6px;">
                                <input type="text" name="MasterForm[form_name]" class="prop-input" value="<?= Html::encode($model->form_name) ?>" placeholder="Nama Form" style="<?= !empty($formNameErrors) ? 'border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.12);' : '' ?>">
                                <?php if (!empty($formNameErrors)): ?>
                                <div style="font-size:12px;color:#dc2626;line-height:1.4;"><?= Html::encode($formNameErrors[0]) ?></div>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="generate-from-table" class="btn-cancel" style="display:flex;align-items:center;gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:18px;">autorenew</span>
                                Generate Fields
                            </button>
                        </div>

                        <div class="builder-workspace" id="workspace">
                            <div class="builder-workspace-header">
                                <span style="font-weight:600;color:#1e2937;font-size:14px;">Form Fields</span>
                                <span class="builder-workspace-hint" id="field-count-hint">0 fields</span>
                            </div>
                            <div class="builder-canvas-surface">
                                <div id="canvas-drop-zone" class="canvas-drop-zone">
                                    <div id="canvas-placeholder" style="text-align:center;padding:60px 20px;color:#94a3b8;">
                                        <span class="material-symbols-outlined" style="font-size:48px;opacity:0.4;display:block;margin-bottom:12px;">dynamic_form</span>
                                        <p style="font-size:14px;">Drag field dari panel kiri atau klik untuk menambahkan</p>
                                    </div>
                                    <div id="fields-container"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: Properties -->
    <div class="builder-properties">
        <div class="prop-tabs" style="display: flex; background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
            <button class="prop-tab-btn active" data-tab="design" style="flex: 1; padding: 12px; border: none; background: none; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid #6366f1;">Design</button>
            <button class="prop-tab-btn" data-tab="code" style="flex: 1; padding: 12px; border: none; background: none; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent;">Custom Code</button>
        </div>

        <div id="properties-design-tab" class="prop-tab-content" style="display: flex; flex: 1; flex-direction: column;">
            <div id="properties-panel">
                <div class="no-selection">
                    <span class="material-symbols-outlined">touch_app</span>
                    <p style="font-size:14px">Pilih field untuk edit</p>
                </div>
            </div>
        </div>

        <div id="properties-code-tab" class="prop-tab-content" style="display: none; flex: 1; flex-direction: column;">
            <div class="code-editor-header">
                <div class="code-scope-buttons">
                    <button type="button" class="code-scope-btn active" data-scope="component" onclick="setCodeScope('component')">Component Code</button>
                    <button type="button" class="code-scope-btn" data-scope="page" onclick="setCodeScope('page')">Page Source</button>
                </div>
                <div class="code-editor-tools" id="component-code-tools">
                    <div class="code-lang-buttons">
                        <button type="button" class="code-lang-btn active" data-lang="html" onclick="switchCodeLang('html')">HTML</button>
                        <button type="button" class="code-lang-btn" data-lang="css" onclick="switchCodeLang('css')">CSS</button>
                        <button type="button" class="code-lang-btn" data-lang="js" onclick="switchCodeLang('js')">JS</button>
                    </div>
                    <button type="button" class="btn-reset-base" onclick="resetFieldCode()">
                        <span class="material-symbols-outlined" style="font-size:14px">refresh</span>
                        Reset Base
                    </button>
                </div>
                <div class="code-editor-tools" id="page-code-tools" style="display:none;">
                    <button type="button" class="btn-reset-base" onclick="resetPageSourceCode()">
                        <span class="material-symbols-outlined" style="font-size:14px">refresh</span>
                        Reset Base
                    </button>
                </div>
            </div>
            <div id="code-mode-hint" class="code-mode-hint">Edit custom code untuk field yang dipilih (HTML/CSS/JS terpisah).</div>
            <div id="monaco-editor-container" style="flex: 1; min-height: 300px; background: #1e1e1e;"></div>
        </div>
    </div>
</div>

<!-- Toolbar -->
<div class="builder-toolbar">
    <div class="builder-toolbar-title">
        <span class="material-symbols-outlined">dynamic_form</span>
        Form Builder
    </div>
    <div class="builder-toolbar-actions">
        <a href="<?= Url::to(['index']) ?>" class="btn-cancel">Batal</a>
        <button type="submit" form="master-form-form" class="btn-save">
            <span class="material-symbols-outlined">save</span>
            Simpan Form
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const dropZone = document.getElementById('canvas-drop-zone');
    const container = document.getElementById('fields-container');
    const placeholder = document.getElementById('canvas-placeholder');
    const propsPanel = document.getElementById('properties-panel');
    const formDataInput = document.getElementById('form-data-input');
    const fieldCountHint = document.getElementById('field-count-hint');
    const componentItems = document.querySelectorAll('.component-item');
    
    let formFields = [];
    let selectedIndex = null;
    let currentDevice = 'desktop';
    let dropdownSourceTables = [];
    const dropdownSourceColumnsCache = {};
    
    // Field Configuration
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
        checkboxes: { label: 'Checkboxes', inputType: 'checkboxes', options: [{value:'opt1',label:'Opsi 1'}, {value:'opt2',label:'Opsi 2'}] },
        date: { label: 'Date', inputType: 'date' },
        time: { label: 'Time', inputType: 'time' },
        datetime: { label: 'Date Time', inputType: 'datetime-local' },
        file: { label: 'File Upload', inputType: 'file' },
        hidden: { label: 'Hidden', inputType: 'hidden' }
    };
    
    // Field Icons
    const fieldIcons = {
        text: 'text_fields', email: 'email', password: 'lock', number: 'pin',
        tel: 'phone', url: 'link', textarea: 'notes', select: 'arrow_drop_down_circle',
        radio: 'radio_button_checked', checkbox: 'check_box', checkboxes: 'checklist',
        date: 'calendar_today', time: 'schedule', datetime: 'event',
        file: 'upload_file', hidden: 'visibility_off'
    };
    
    // Device Switching
    window.setDevice = function(device) {
        currentDevice = device;
        const frame = document.getElementById('canvas-frame');
        if (frame) {
            frame.classList.remove('device-desktop', 'device-tablet', 'device-mobile');
            frame.classList.add('device-' + device);
        }
        document.querySelectorAll('.device-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.device === device);
        });
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    function getDropdownSourceMode(field) {
        return field.dropdown_source === 'table' ? 'table' : 'manual';
    }

    function hasResolvedRelationConfig(field) {
        if (!field || typeof field !== 'object') {
            return false;
        }

        const relationConfig = field.relation_config && typeof field.relation_config === 'object'
            ? field.relation_config
            : (field.relationConfig && typeof field.relationConfig === 'object' ? field.relationConfig : {});

        const localColumn = String(field.local_column || field.name || field.field_name || field.field_key || relationConfig.local_column || '').trim();
        const referencedTable = String(field.fk_referenced_table || field.source_table_name || field.referenced_table_name || relationConfig.referenced_table || relationConfig.referenced_table_name || '').trim();
        const referencedValueColumn = String(field.fk_referenced_column || field.value_column || field.dropdown_value_column || field.referenced_value_column || field.referenced_column_name || relationConfig.referenced_value_column || relationConfig.value_column || relationConfig.referenced_column || '').trim();

        return localColumn !== '' && referencedTable !== '' && referencedValueColumn !== '';
    }

    function syncRelationConfig(field) {
        if (!field || typeof field !== 'object') {
            return field;
        }

        const localColumn = String(field.name || field.field_name || field.field_key || field.column_name || field.local_column || '').trim();
        const referencedTable = String(field.fk_referenced_table || field.source_table_name || field.referenced_table_name || '').trim();
        const referencedValueColumn = String(field.fk_referenced_column || field.value_column || field.dropdown_value_column || field.referenced_column_name || '').trim();
        const displayColumn = String(field.fk_display_column || field.label_column || field.dropdown_label_column || '').trim();

        field.local_column = localColumn;
        field.source_column = localColumn;
        field.source_column_name = field.source_column_name || localColumn;
        field.referenced_table_name = referencedTable;
        field.referenced_value_column = referencedValueColumn;
        field.referenced_column_name = referencedValueColumn;
        field.display_column = displayColumn;
        field.value_column = referencedValueColumn;
        field.dropdown_value_column = referencedValueColumn;
        field.label_column = displayColumn;
        field.dropdown_label_column = displayColumn;
        field.fk_referenced_table = referencedTable;
        field.fk_referenced_column = referencedValueColumn;
        field.fk_display_column = displayColumn;
        field.relation_table_name = referencedTable;
        field.relation_target_column = localColumn;
        field.relation_value_column = referencedValueColumn;
        field.relation_display_column = displayColumn;
        field.relation_config = Object.assign({}, field.relation_config || {}, {
            local_column: localColumn,
            source_column: localColumn,
            column_name: localColumn,
            referenced_table: referencedTable,
            referenced_table_name: referencedTable,
            referenced_value_column: referencedValueColumn,
            referenced_column: referencedValueColumn,
            referenced_column_name: referencedValueColumn,
            value_column: referencedValueColumn,
            display_column: displayColumn,
            display_column_name: displayColumn
        });

        return field;
    }

    function normalizeRelationMetadata(field) {
        if (!field || typeof field !== 'object') {
            return field;
        }

        const relationConfig = field.relation_config && typeof field.relation_config === 'object'
            ? field.relation_config
            : (field.relationConfig && typeof field.relationConfig === 'object' ? field.relationConfig : {});

        const fieldName = String(field.name || field.field_name || field.field_key || field.column_name || '').trim();
        if (fieldName) {
            field.name = fieldName;
            field.field_name = fieldName;
            field.field_key = fieldName;
            field.column_name = fieldName;
        }

        field.source_table_id = field.source_table_id || field.dropdown_table_id || '';
        field.source_table_name = field.source_table_name || field.fk_referenced_table || field.referenced_table_name || relationConfig.referenced_table || relationConfig.referenced_table_name || '';
        field.value_column = field.value_column || field.dropdown_value_column || field.fk_referenced_column || field.referenced_value_column || field.referenced_column_name || relationConfig.referenced_value_column || relationConfig.value_column || relationConfig.referenced_column || relationConfig.referenced_column_name || '';
        field.label_column = field.label_column || field.dropdown_label_column || field.fk_display_column || relationConfig.display_column || relationConfig.display_column_name || '';
        field.fk_referenced_table = field.fk_referenced_table || field.source_table_name || field.referenced_table_name || relationConfig.referenced_table || relationConfig.referenced_table_name || '';
        field.fk_referenced_column = field.fk_referenced_column || field.value_column || field.referenced_value_column || field.referenced_column_name || relationConfig.referenced_value_column || relationConfig.value_column || relationConfig.referenced_column || relationConfig.referenced_column_name || '';
        field.fk_display_column = field.fk_display_column || field.label_column || relationConfig.display_column || relationConfig.display_column_name || '';
        field.relation_table_name = field.relation_table_name || field.source_table_name || field.fk_referenced_table || '';
        field.relation_target_column = field.relation_target_column || relationConfig.local_column || relationConfig.source_column || field.name || '';
        field.relation_value_column = field.relation_value_column || field.value_column || field.fk_referenced_column || relationConfig.referenced_value_column || relationConfig.value_column || '';
        field.relation_display_column = field.relation_display_column || field.label_column || field.fk_display_column || relationConfig.display_column || relationConfig.display_column_name || '';

        if (field.is_foreign_key || String(field.fk_referenced_table || '').trim() !== '' || Array.isArray(field.fk_options)) {
            field.is_foreign_key = true;
        }
        if (!hasResolvedRelationConfig(field)) {
            field.is_foreign_key = false;
        }

        return syncRelationConfig(field);
    }

    function isRelationField(field) {
        return !!field && (
            hasResolvedRelationConfig(field) ||
            (!!field.is_foreign_key && String(field.fk_referenced_table || '').trim() !== '')
        );
    }

    function normalizeFieldState(field) {
        field = normalizeRelationMetadata(field);
        if (!field || typeof field !== 'object') {
            return field;
        }

        if (!field.type && field.inputType) {
            field.type = field.inputType;
        }
        if (!field.inputType && field.type) {
            field.inputType = field.type;
        }

        if (field.type === 'select' && isRelationField(field)) {
            field.options = Array.isArray(field.fk_options) && field.fk_options.length > 0
                ? field.fk_options
                : (Array.isArray(field.options) ? field.options : []);
        }

        return field;
    }

    function getFieldConfiguredOptions(field) {
        field = normalizeFieldState(field);
        if (!field) return [];

        if (isRelationField(field)) {
            if (Array.isArray(field.fk_options) && field.fk_options.length > 0) {
                return field.fk_options;
            }
            if (Array.isArray(field.options) && field.options.length > 0) {
                return field.options;
            }
            return [];
        }

        if (field.dropdown_source === 'table' && Array.isArray(field.options) && field.options.length > 0) {
            return field.options;
        }

        if (Array.isArray(field.options) && field.options.length > 0) {
            return field.options;
        }

        return [];
    }

    function normalizeChoiceOptions(field) {
        field = normalizeFieldState(field);
        if (isRelationField(field)) {
            return getFieldConfiguredOptions(field);
        }
        if (!Array.isArray(field.options) || field.options.length === 0) {
            field.options = [
                { value: 'opt1', label: 'Opsi 1' },
                { value: 'opt2', label: 'Opsi 2' }
            ];
        }
        field.options = field.options.map((opt, index) => ({
            value: opt.value ?? ('opt' + (index + 1)),
            label: opt.label ?? ('Opsi ' + (index + 1))
        }));
        return field.options;
    }

    function getCurrentBuilderTableId() {
        const hiddenInput = document.getElementById('table-id-input');
        const selector = document.getElementById('table-selector');
        const rawValue = (hiddenInput && hiddenInput.value) || (selector && selector.value) || '';
        const parsed = parseInt(rawValue, 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function isGenericDropdownName(value) {
        const normalized = String(value || '').trim().toLowerCase();
        if (!normalized) {
            return true;
        }
        return /^(field|select|dropdown|pilihan)(_\d+)?$/.test(normalized);
    }

    function isGenericDropdownLabel(value) {
        const normalized = String(value || '').trim().toLowerCase();
        if (!normalized) {
            return true;
        }
        return ['dropdown', 'select', 'pilihan', 'opsi', 'dropdown manual'].includes(normalized);
    }

    function getCurrentTableForeignKeyColumnsSync() {
        const tableId = getCurrentBuilderTableId();
        if (!tableId) {
            return [];
        }
        const columns = dropdownSourceColumnsCache[String(tableId)] || [];
        return columns.filter(column => !!column && !!column.id && !!column.is_foreign_key);
    }

    function buildCurrentTableForeignKeyOptions(selectedColumnId) {
        const columns = getCurrentTableForeignKeyColumnsSync();
        let html = '<option value="">Pilih kolom foreign key...</option>';
        columns.forEach(column => {
            const label = column.label || column.name;
            html += '<option value="' + escapeAttr(column.id) + '"' + boolAttr('selected', String(selectedColumnId || '') === String(column.id)) + '>' + escapeHtml(label + ' (' + column.name + ')') + '</option>';
        });
        return html;
    }

    function getCurrentTableForeignKeyPanelState() {
        const currentTableId = getCurrentBuilderTableId();
        const cacheKey = String(currentTableId || '');
        const isLoaded = !!(currentTableId && dropdownSourceColumnsCache[cacheKey]);
        const columns = currentTableId ? getCurrentTableForeignKeyColumnsSync() : [];
        return {
            tableId: currentTableId,
            isLoaded: isLoaded,
            columns: columns,
        };
    }

    function buildDropdownColumnOptions(field, selectedColumn) {
        const tableId = field.source_table_id || field.dropdown_table_id || '';
        const columns = dropdownSourceColumnsCache[String(tableId)] || [];
        let html = '<option value="">Pilih kolom...</option>';
        columns.forEach(column => {
            html += '<option value="' + escapeAttr(column.name) + '"' + boolAttr('selected', String(selectedColumn || '') === String(column.name)) + '>' + escapeHtml((column.label || column.name) + ' (' + column.name + ')') + '</option>';
        });
        return html;
    }

    function findDropdownTableByName(tableName) {
        const normalized = String(tableName || '').trim().toLowerCase();
        if (!normalized) {
            return null;
        }
        return dropdownSourceTables.find(table => String(table.name || '').trim().toLowerCase() === normalized) || null;
    }

    function getPreferredDisplayColumn(columns, tableName, valueColumn, preferredColumn) {
        const normalizedValue = String(valueColumn || '').trim().toLowerCase();
        const normalizedPreferred = String(preferredColumn || '').trim().toLowerCase();
        const normalizedTable = String(tableName || '').trim().toLowerCase();
        const priorities = ['name', 'nama', 'title', 'label'];
        if (normalizedTable) {
            priorities.push('nama_' + normalizedTable);
        }
        priorities.push('kode');

        if (normalizedPreferred && columns.some(column => String(column.name || '').toLowerCase() === normalizedPreferred)) {
            return preferredColumn;
        }

        const preferredMatch = priorities.find(candidate => candidate !== normalizedValue && columns.some(column => String(column.name || '').toLowerCase() === candidate));
        if (preferredMatch) {
            return preferredMatch;
        }

        const readableColumn = columns.find(column => {
            const columnName = String(column.name || '').trim().toLowerCase();
            if (!columnName || columnName === normalizedValue || column.is_primary) {
                return false;
            }
            if (['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'].includes(columnName)) {
                return false;
            }
            if (columnName.endsWith('_id')) {
                return false;
            }
            return ['string', 'integer', 'double'].includes(String(column.php_type || '').toLowerCase());
        });
        if (readableColumn) {
            return readableColumn.name;
        }

        const fallback = columns.find(column => String(column.name || '').trim() !== '' && String(column.name || '').trim().toLowerCase() !== normalizedValue);
        return fallback ? fallback.name : (valueColumn || '');
    }

    function ensureRelationTableContext(field) {
        field = normalizeFieldState(field);
        return ensureDropdownSourceTablesLoaded().then(function() {
            if (!field.source_table_id && field.fk_referenced_table) {
                const table = findDropdownTableByName(field.fk_referenced_table);
                if (table) {
                    field.source_table_id = parseInt(table.id, 10);
                    field.dropdown_table_id = field.source_table_id;
                    field.source_table_name = table.name;
                }
            }

            const tableId = field.source_table_id || field.dropdown_table_id || '';
            if (!tableId) {
                return [];
            }

            return ensureDropdownSourceColumnsLoaded(tableId).then(function(columns) {
                const hasValueColumn = columns.some(column => String(column.name || '') === String(field.value_column || field.fk_referenced_column || ''));
                const hasDisplayColumn = columns.some(column => String(column.name || '') === String(field.label_column || field.fk_display_column || ''));
                const primaryColumn = columns.find(column => column.is_primary) || columns.find(column => String(column.name || '').toLowerCase() === 'id') || columns[0] || null;
                if ((!field.value_column || !hasValueColumn) && primaryColumn) {
                    field.value_column = primaryColumn.name;
                }
                if (!field.fk_referenced_column && field.value_column) {
                    field.fk_referenced_column = field.value_column;
                }
                const displayColumn = getPreferredDisplayColumn(columns, field.fk_referenced_table || field.source_table_name, field.value_column || field.fk_referenced_column, field.fk_display_column || field.label_column);
                if ((!field.label_column || !hasDisplayColumn) && displayColumn) {
                    field.label_column = displayColumn;
                }
                if ((!field.fk_display_column || !hasDisplayColumn) && displayColumn) {
                    field.fk_display_column = displayColumn;
                }
                syncRelationConfig(field);
                return columns;
            });
        });
    }

    function ensureDropdownSourceTablesLoaded() {
        if (dropdownSourceTables.length > 0) return Promise.resolve(dropdownSourceTables);
        return fetch('/tables/get-tables?t=' + Date.now(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                dropdownSourceTables = Array.isArray(data.tables) ? data.tables : [];
                return dropdownSourceTables;
            })
            .catch(() => []);
    }

    function ensureDropdownSourceColumnsLoaded(tableId) {
        tableId = String(tableId || '');
        if (!tableId) return Promise.resolve([]);
        if (dropdownSourceColumnsCache[tableId]) return Promise.resolve(dropdownSourceColumnsCache[tableId]);
        return fetch('/tables/columns/' + encodeURIComponent(tableId) + '?t=' + Date.now(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                dropdownSourceColumnsCache[tableId] = Array.isArray(data.columns) ? data.columns : [];
                return dropdownSourceColumnsCache[tableId];
            })
            .catch(() => []);
    }

    function ensureCurrentTableForeignKeyColumnsLoaded() {
        const tableId = getCurrentBuilderTableId();
        if (!tableId) {
            return Promise.resolve([]);
        }
        return ensureDropdownSourceColumnsLoaded(tableId).then(function(columns) {
            return (Array.isArray(columns) ? columns : []).filter(column => {
                return !!column && !!column.id && !!column.is_foreign_key;
            });
        });
    }

    function resetDropdownTableSourceField(field) {
        delete field.source_column_id;
        delete field.local_column;
        delete field.source_table_id;
        delete field.dropdown_table_id;
        delete field.source_table_name;
        delete field.value_column;
        delete field.label_column;
        delete field.dropdown_value_column;
        delete field.dropdown_label_column;
        delete field.fk_options;
        delete field.fk_referenced_table;
        delete field.fk_referenced_column;
        delete field.fk_display_column;
        delete field.relation_table_name;
        delete field.relation_target_column;
        delete field.relation_value_column;
        delete field.relation_display_column;
        delete field.relation_config;
        delete field.relationConfig;
        delete field.dynamic_options_loaded;
        field.is_foreign_key = false;
    }

    function applyForeignKeyColumnToDropdownField(field, fkColumn, fkData) {
        const relationLabel = fkColumn.label || fkColumn.name;
        if (isGenericDropdownName(field.name) || !field.name) {
            field.name = fkColumn.name;
            field.field_name = fkColumn.name;
            field.field_key = fkColumn.name;
        }
        if (isGenericDropdownLabel(field.label)) {
            field.label = relationLabel;
        }

        field.dropdown_source = 'table';
        field.is_foreign_key = true;
        field.source_column_id = fkColumn.id;
        field.local_column = fkColumn.name;
        field.column_name = fkColumn.name;
        field.field_name = fkColumn.name;
        field.field_key = fkColumn.name;
        field.name = fkColumn.name;
        field.source_table_id = fkData.referenced_table_id || field.source_table_id || '';
        field.dropdown_table_id = field.source_table_id || '';
        field.source_table_name = fkData.referenced_table || fkColumn.referenced_table_name || '';
        field.fk_referenced_table = fkData.referenced_table || fkColumn.referenced_table_name || '';
        field.fk_referenced_column = fkData.referenced_value_column || fkColumn.referenced_column_name || '';
        field.value_column = field.fk_referenced_column;
        field.dropdown_value_column = field.fk_referenced_column;
        field.fk_display_column = fkData.display_column || field.fk_display_column || '';
        field.label_column = field.fk_display_column || '';
        field.dropdown_label_column = field.label_column || '';
        field.relation_table_name = field.fk_referenced_table || '';
        field.relation_target_column = field.local_column || field.name || '';
        field.relation_value_column = field.fk_referenced_column || '';
        field.relation_display_column = field.fk_display_column || '';
        field.options = Array.isArray(fkData.options) ? fkData.options : [];
        field.fk_options = Array.isArray(fkData.options) ? fkData.options : [];
        field.dynamic_options_loaded = true;
        syncRelationConfig(field);
    }

    function loadDropdownSourceFromForeignKey(field, columnId) {
        const targetColumnId = parseInt(columnId, 10);
        const fkColumn = getCurrentTableForeignKeyColumnsSync().find(column => String(column.id) === String(targetColumnId));
        if (!fkColumn) {
            return Promise.resolve(false);
        }

        return fetch('/tables/foreign-key-options/' + encodeURIComponent(targetColumnId) + '?t=' + Date.now(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Gagal memuat opsi foreign key.');
                }
                applyForeignKeyColumnToDropdownField(field, fkColumn, data);
                return true;
            });
    }

    function refreshDropdownOptionsFromTable(field) {
        field = normalizeFieldState(field);
        return ensureRelationTableContext(field).then(function() {
            const tableId = field.source_table_id || field.dropdown_table_id || '';
            const valueColumn = field.value_column || field.dropdown_value_column || field.fk_referenced_column || '';
            const labelColumn = field.label_column || field.dropdown_label_column || field.fk_display_column || '';
            if (!tableId || !valueColumn || !labelColumn) return [];

            const url = '/tables/dropdown-options/' + encodeURIComponent(tableId)
                + '?value_column=' + encodeURIComponent(valueColumn)
                + '&label_column=' + encodeURIComponent(labelColumn)
                + '&t=' + Date.now();
            return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.json())
                .then(data => {
                    if (data.success && Array.isArray(data.options)) {
                        field.options = data.options;
                        field.fk_options = data.options;
                        field.fk_referenced_table = field.fk_referenced_table || field.source_table_name || data.table_name || '';
                        field.source_table_name = field.source_table_name || data.table_name || '';
                        field.value_column = data.value_column || valueColumn;
                        field.dropdown_value_column = field.value_column;
                        field.fk_referenced_column = field.value_column;
                        field.label_column = data.label_column || labelColumn;
                        field.dropdown_label_column = field.label_column;
                        field.fk_display_column = field.label_column;
                        field.dynamic_options_loaded = true;
                        syncRelationConfig(field);
                        return data.options;
                    }
                    field.options = [];
                    field.fk_options = [];
                    syncRelationConfig(field);
                    return [];
                })
                .catch(() => []);
        });
    }

    function attr(name, value) {
        if (value === undefined || value === null || value === '') return '';
        return ' ' + name + '="' + escapeAttr(value) + '"';
    }

    function boolAttr(name, enabled) {
        return enabled ? ' ' + name : '';
    }

    function buildSelectOptionsMarkup(field) {
        const options = getFieldConfiguredOptions(field);
        if (!options.length) {
            return '';
        }

        return options.map(function(opt) {
            const value = escapeAttr(opt.value ?? '');
            const label = escapeHtml(opt.label ?? opt.value ?? '');
            if (!value) return '';
            return '<option value="' + value + '">' + label + '</option>';
        }).join('');
    }

    function isRelationSelectField(field) {
        return !!field && String(field.type || '').toLowerCase() === 'select' && (
            hasResolvedRelationConfig(field) ||
            (!!field.is_foreign_key && String(field.fk_referenced_table || '').trim() !== '')
        );
    }

    function looksLikeDummySelectCode(code) {
        const normalized = String(code || '');
        return normalized.includes('Opsi 1') && normalized.includes('Opsi 2') && normalized.includes('<select');
    }

    // Render Preview Input
    function renderPreview(field) {
        if (!field) return '';
        field = normalizeFieldState(field);
        
        // Check for custom code
        if (field.customHtml || field.customCss || field.customJs) {
            const id = 'preview-' + field.id;
            const srcDoc = '<!DOCTYPE html><html><head><style>' + (field.customCss || '') + '</style></head><body>' + (field.customHtml || '') + '<script>' + (field.customJs || '') + '<\/script></body></html>';
            return `<div class="field-preview" style="padding:0;background:transparent;border:none;">` +
                `<iframe id="${id}" srcdoc="${srcDoc.replace(/"/g, '&quot;')}" style="width:100%;min-height:80px;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;" sandbox="allow-scripts"></iframe>` +
                `</div>`;
        }
        
        const type = field.type || 'text';
        const placeholders = {
            text: 'Input text...',
            email: 'email@example.com',
            password: '******',
            number: '0',
            tel: '+62 xxx',
            url: 'https://',
            textarea: 'Enter text...',
            select: 'Pilih opsi...',
            date: 'Pilih tanggal',
            time: 'Pilih waktu',
            datetime: 'Pilih tanggal & waktu',
        };
        const inputType = {
            text: 'text', email: 'email', password: 'password', number: 'number',
            tel: 'tel', url: 'url', textarea: 'textarea', select: 'select',
            radio: 'radio', checkbox: 'checkbox', checkboxes: 'checkbox',
            date: 'date', time: 'time', datetime: 'datetime-local',
            file: 'file', hidden: 'hidden'
        };

        const commonAttrs = attr('placeholder', field.placeholder || placeholders[type] || '') +
            attr('value', field.default_value || '') +
            attr('name', field.name || '') +
            attr('minlength', field.min_length) +
            attr('maxlength', field.max_length) +
            attr('pattern', field.pattern) +
            boolAttr('required', field.required) +
            boolAttr('readonly', field.readonly) +
            boolAttr('disabled', true);

        if (type === 'select') {
            const options = getFieldConfiguredOptions(field);
            let optionsHtml = '<option value="">-- Pilih --</option>';
            if (options.length > 0) {
                options.forEach(opt => {
                    const value = String(opt.value ?? '');
                    if (!value) return;
                    optionsHtml += '<option value="' + escapeAttr(value) + '"' + boolAttr('selected', String(field.default_value || '') === value) + '>' + escapeHtml(opt.label ?? value) + '</option>';
                });
            }
            return '<div class="field-preview"><select' + attr('name', field.name || '') + boolAttr('required', field.required) + boolAttr('disabled', true) + '>' + optionsHtml + '</select></div>';
        }

        if (type === 'radio' || type === 'checkboxes') {
            const options = normalizeChoiceOptions(field);
            const optionHtml = options.map((opt, index) => {
                const inputType = type === 'radio' ? 'radio' : 'checkbox';
                return '<label style="display:flex;align-items:center;gap:8px;margin:6px 0;color:#475569;">' +
                    '<input type="' + inputType + '" name="' + escapeAttr(field.name || field.id || 'option_group') + '"' + boolAttr('checked', String(field.default_value || '') === String(opt.value) || (index === 0 && type === 'radio' && !field.default_value)) + boolAttr('disabled', true) + '>' +
                    '<span>' + escapeHtml(opt.label) + '</span>' +
                    '</label>';
            }).join('');
            return '<div class="field-preview">' + optionHtml + '</div>';
        }

        if (type === 'textarea') {
            return '<div class="field-preview"><textarea rows="' + escapeAttr(field.rows || 4) + '"' + commonAttrs + '>' + escapeHtml(field.default_value || '') + '</textarea></div>';
        }

        if (type === 'checkbox') {
            return '<div class="field-preview"><label style="display:flex;align-items:center;gap:8px;color:#475569;"><input type="checkbox"' + attr('name', field.name || '') + boolAttr('checked', field.default_checked) + boolAttr('required', field.required) + boolAttr('disabled', true) + '><span>' + escapeHtml(field.labelText || field.label || 'Checkbox') + '</span></label></div>';
        }

        if (type === 'file') {
            return '<div class="field-preview"><input type="file"' + attr('name', field.name || '') + attr('accept', field.accept) + boolAttr('multiple', field.multiple) + boolAttr('required', field.required) + boolAttr('disabled', true) + '></div>';
        }

        if (type === 'hidden') {
            return '<div class="field-preview" style="background:#f8fafc;color:#64748b;">Hidden value: ' + escapeHtml(field.default_value || '(empty)') + '</div>';
        }

        const numericAttrs = type === 'number' ? attr('min', field.min) + attr('max', field.max) + attr('step', field.step) : '';
        const dateAttrs = ['date', 'time', 'datetime'].includes(type) ? attr('min', field.min) + attr('max', field.max) : '';

        return '<div class="field-preview"><input type="' + escapeAttr(inputType[type] || 'text') + '"' + commonAttrs + numericAttrs + dateAttrs + '></div>';
    }
    
    // Initialize Sortable
    function initSortable() {
        if (!container) return;
        if (window.formSortableInstance) {
            window.formSortableInstance.destroy();
        }
        window.formSortableInstance = new Sortable(container, {
            animation: 150,
            handle: '.field-drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const item = formFields.splice(evt.oldIndex, 1)[0];
                formFields.splice(evt.newIndex, 0, item);
                selectedIndex = evt.newIndex;
                renderFields();
                updateData();
            }
        });
    }
    
    // Select Field
    window.selectField = function(index) {
        if (index === null || index === undefined) return;
        selectedIndex = index;
        renderFields();
        if (formFields[index]) {
            renderPropsPanel(formFields[index]);
        }
    };
    
    // Render Properties Panel (Design Tab)
    function renderPropsPanel(field) {
        field = normalizeFieldState(field);
        const panel = document.getElementById('properties-panel');
        if (!panel) return;
        
        if (!field) {
            panel.innerHTML = '<div class="no-selection"><span class="material-symbols-outlined">touch_app</span><p style="font-size:14px">Pilih field untuk edit</p></div>';
            return;
        }
        
        const icons = {
            text: 'text_fields', email: 'email', password: 'lock', number: 'pin',
            tel: 'phone', url: 'link', textarea: 'notes', select: 'arrow_drop_down_circle',
            radio: 'radio_button_checked', checkbox: 'check_box', checkboxes: 'checklist',
            date: 'calendar_today', time: 'schedule', datetime: 'event',
            file: 'upload_file', hidden: 'visibility_off'
        };
        
        let html = '<div class="prop-header"><span class="material-symbols-outlined">' + (icons[field.type] || 'text_fields') + '</span><span class="block-type-badge">' + field.type + '</span></div>';

        html += '<div class="prop-section"><div class="prop-section-title">Label & Name</div>';
        html += '<div class="prop-group"><label class="prop-label">Label</label><input type="text" class="prop-input" value="' + escapeAttr(field.label || '') + '" data-prop="label" onchange="updateFieldProp(\'label\', this.value)"></div>';
        html += '<div class="prop-group"><label class="prop-label">Name (Database)</label><input type="text" class="prop-input" value="' + escapeAttr(field.name || '') + '" data-prop="name" onchange="updateFieldProp(\'name\', this.value)"></div>';
        html += '<div class="prop-group"><label class="prop-label">Placeholder</label><input type="text" class="prop-input" value="' + escapeAttr(field.placeholder || '') + '" data-prop="placeholder" onchange="updateFieldProp(\'placeholder\', this.value)"></div></div>';
        
        html += '<div class="prop-section"><div class="prop-section-title">Konfigurasi</div>';
        html += '<div class="prop-group"><label class="prop-label">Tipe Input</label><select class="prop-select" data-prop="type" onchange="updateFieldProp(\'type\', this.value)">';
        const types = ['text', 'email', 'password', 'number', 'tel', 'url', 'textarea', 'select', 'radio', 'checkbox', 'checkboxes', 'date', 'time', 'datetime', 'file', 'hidden'];
        const labels = ['Text Input', 'Email', 'Password', 'Number', 'Phone/Tel', 'URL', 'Textarea', 'Dropdown Select', 'Radio Button', 'Checkbox', 'Checkboxes', 'Date', 'Time', 'Date Time', 'File Upload', 'Hidden'];
        types.forEach((t, i) => {
            html += '<option value="' + t + '" ' + (field.type === t ? 'selected' : '') + '>' + labels[i] + '</option>';
        });
        html += '</select></div>';
        html += '<div class="prop-group"><label class="prop-label">Default Value</label><input type="text" class="prop-input" value="' + escapeAttr(field.default_value || '') + '" data-prop="default_value" onchange="updateFieldProp(\'default_value\', this.value)"></div></div>';

        if (['text', 'email', 'password', 'tel', 'url'].includes(field.type)) {
            html += '<div class="prop-section"><div class="prop-section-title">Input Rules</div>';
            html += '<div class="prop-group"><label class="prop-label">Min Length</label><input type="number" class="prop-input" min="0" value="' + escapeAttr(field.min_length || '') + '" onchange="updateFieldProp(\'min_length\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Max Length</label><input type="number" class="prop-input" min="1" value="' + escapeAttr(field.max_length || '') + '" onchange="updateFieldProp(\'max_length\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Pattern Regex</label><input type="text" class="prop-input" value="' + escapeAttr(field.pattern || '') + '" placeholder="e.g. [A-Za-z0-9]+" onchange="updateFieldProp(\'pattern\', this.value)"></div>';
            html += '</div>';
        }

        if (field.type === 'number') {
            html += '<div class="prop-section"><div class="prop-section-title">Number Rules</div>';
            html += '<div class="prop-group"><label class="prop-label">Min</label><input type="number" class="prop-input" value="' + escapeAttr(field.min || '') + '" onchange="updateFieldProp(\'min\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Max</label><input type="number" class="prop-input" value="' + escapeAttr(field.max || '') + '" onchange="updateFieldProp(\'max\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Step</label><input type="text" class="prop-input" value="' + escapeAttr(field.step || '') + '" placeholder="1, 0.01, any" onchange="updateFieldProp(\'step\', this.value)"></div>';
            html += '</div>';
        }

        if (field.type === 'textarea') {
            html += '<div class="prop-section"><div class="prop-section-title">Textarea</div>';
            html += '<div class="prop-group"><label class="prop-label">Rows</label><input type="number" class="prop-input" min="2" max="20" value="' + escapeAttr(field.rows || 4) + '" onchange="updateFieldProp(\'rows\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Max Length</label><input type="number" class="prop-input" min="1" value="' + escapeAttr(field.max_length || '') + '" onchange="updateFieldProp(\'max_length\', this.value)"></div>';
            html += '</div>';
        }

        if (['date', 'time', 'datetime'].includes(field.type)) {
            html += '<div class="prop-section"><div class="prop-section-title">Range</div>';
            html += '<div class="prop-group"><label class="prop-label">Min</label><input type="' + (field.type === 'datetime' ? 'datetime-local' : field.type) + '" class="prop-input" value="' + escapeAttr(field.min || '') + '" onchange="updateFieldProp(\'min\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Max</label><input type="' + (field.type === 'datetime' ? 'datetime-local' : field.type) + '" class="prop-input" value="' + escapeAttr(field.max || '') + '" onchange="updateFieldProp(\'max\', this.value)"></div>';
            html += '</div>';
        }

        if (field.type === 'file') {
            html += '<div class="prop-section"><div class="prop-section-title">File Upload</div>';
            html += '<div class="prop-group"><label class="prop-label">Accept</label><input type="text" class="prop-input" value="' + escapeAttr(field.accept || '') + '" placeholder=".jpg,.png,application/pdf" onchange="updateFieldProp(\'accept\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.multiple ? 'checked' : '') + ' onchange="updateFieldProp(\'multiple\', this.checked)">Allow Multiple Files</label></div>';
            html += '</div>';
        }

        if (field.type === 'checkbox') {
            html += '<div class="prop-section"><div class="prop-section-title">Checkbox</div>';
            html += '<div class="prop-group"><label class="prop-label">Checkbox Text</label><input type="text" class="prop-input" value="' + escapeAttr(field.labelText || '') + '" placeholder="Centang ini" onchange="updateFieldProp(\'labelText\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.default_checked ? 'checked' : '') + ' onchange="updateFieldProp(\'default_checked\', this.checked)">Checked by Default</label></div>';
            html += '</div>';
        }

        if (field.type === 'select') {
            const sourceMode = getDropdownSourceMode(field);
            html += '<div class="prop-section"><div class="prop-section-title">Dropdown Source</div>';
            html += '<div class="prop-group"><label class="prop-label">Sumber Opsi</label><select class="prop-select" onchange="setDropdownSourceMode(this.value)">';
            html += '<option value="manual"' + boolAttr('selected', sourceMode === 'manual') + '>Manual Options</option>';
            html += '<option value="table"' + boolAttr('selected', sourceMode === 'table') + '>Ambil dari Table</option>';
            html += '</select></div>';
            if (sourceMode === 'table') {
                const fkPanelState = getCurrentTableForeignKeyPanelState();
                if (!fkPanelState.tableId) {
                    html += '<small style="display:block;color:#b45309;line-height:1.5;">Pilih target table form terlebih dulu. Source dari table hanya tersedia jika form sudah memakai table aktif.</small>';
                } else if (!fkPanelState.isLoaded) {
                    html += '<small style="display:block;color:#64748b;line-height:1.5;">Memuat kolom foreign key dari table aktif...</small>';
                } else if (fkPanelState.columns.length === 0) {
                    html += '<small style="display:block;color:#b45309;line-height:1.5;">Table aktif tidak memiliki kolom foreign key. Ambil dari table tidak tersedia untuk dropdown ini.</small>';
                } else {
                    html += '<div class="prop-group"><label class="prop-label">Kolom FK dari Table Aktif</label><select class="prop-select" onchange="setDropdownSourceForeignKey(this.value)">' + buildCurrentTableForeignKeyOptions(field.source_column_id) + '</select></div>';
                    html += '<small style="display:block;color:#64748b;line-height:1.5;">Pilih salah satu kolom FK dari table aktif. Setelah dipilih, dropdown akan memakai data relasi itu.</small>';
                }
            }
            html += '</div>';
        }

        if (['select', 'radio', 'checkboxes'].includes(field.type) && !field.is_foreign_key && getDropdownSourceMode(field) !== 'table') {
            const options = normalizeChoiceOptions(field);
            html += '<div class="prop-section"><div class="prop-section-title">Options</div>';
            html += '<div class="prop-group">';
            html += '<div class="prop-options-list">';
            options.forEach((opt, index) => {
                html += '<div class="prop-option-row">';
                html += '<input type="text" class="prop-input" value="' + escapeAttr(opt.label) + '" placeholder="Label" onchange="updateFieldOption(' + index + ', \'label\', this.value)">';
                html += '<input type="text" class="prop-input" value="' + escapeAttr(opt.value) + '" placeholder="Value" onchange="updateFieldOption(' + index + ', \'value\', this.value)">';
                html += '<button type="button" class="prop-option-remove" onclick="removeFieldOption(' + index + ')" title="Remove option"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button>';
                html += '</div>';
            });
            html += '</div>';
            html += '<button type="button" class="prop-option-add" onclick="addFieldOption()">+ Add option</button>';
            html += '</div></div>';
        }

        html += '<div class="prop-section"><div class="prop-section-title">Validasi</div>';
        html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.required ? 'checked' : '') + ' data-prop="required" onchange="updateFieldProp(\'required\', this.checked)">Wajib Diisi (Required)</label></div>';
        html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.readonly ? 'checked' : '') + ' data-prop="readonly" onchange="updateFieldProp(\'readonly\', this.checked)">Read-only</label></div>';
        html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.disabled ? 'checked' : '') + ' data-prop="disabled" onchange="updateFieldProp(\'disabled\', this.checked)">Disabled</label></div></div>';
        
        if (field.type === 'select' && field.is_foreign_key) {
            html += '<div class="prop-section"><div class="prop-section-title">Foreign Key</div>';
            html += '<div class="prop-group"><label class="prop-label">Local Column / Kolom Form</label><input type="text" class="prop-input" value="' + escapeAttr(field.local_column || field.name || '-') + '" readonly style="background:#f1f5f9;"></div>';
            html += '<div class="prop-group"><label class="prop-label">Referenced Table</label><input type="text" class="prop-input" value="' + escapeAttr(field.fk_referenced_table || '-') + '" readonly style="background:#f1f5f9;"></div>';
            html += '<div class="prop-group"><label class="prop-label">Referenced Value Column</label><input type="text" class="prop-input" value="' + escapeAttr(field.fk_referenced_column || field.value_column || field.dropdown_value_column || '-') + '" readonly style="background:#f1f5f9;"></div>';
            html += '<div class="prop-group"><label class="prop-label">Display Column</label><select class="prop-select" onchange="setForeignKeyColumn(\'display\', this.value)">' + buildDropdownColumnOptions(field, field.fk_display_column || field.label_column || field.dropdown_label_column) + '</select></div>';
            html += '<div class="prop-group"><button type="button" class="prop-option-add" onclick="reloadForeignKeyOptions()">Refresh dropdown relasi</button></div>';
            if (field.fk_options && field.fk_options.length > 0) {
                html += '<div class="prop-group"><label class="prop-label">Options (' + field.fk_options.length + ' items)</label><div style="max-height:120px;overflow-y:auto;font-size:11px;color:#64748b;">';
                field.fk_options.forEach(opt => {
                    html += '<div style="padding:2px 0;">' + opt.label + ' (value: ' + opt.value + ')</div>';
                });
                html += '</div></div>';
            } else {
                html += '<div class="prop-group"><label class="prop-label">Options</label><span class="fk-options-loading">Loading options...</span></div>';
            }
            html += '</div>';
        }
        
        html += '<div class="prop-section"><div class="prop-section-title">System</div>';
        html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.excluded ? 'checked' : '') + ' data-prop="excluded" onchange="updateFieldProp(\'excluded\', this.checked)">Exclude from Form (Hide)</label></div>';
        if (field.is_primary) {
            html += '<div class="prop-group"><span class="field-badge-auto">Primary Key</span></div>';
        }
        if (field.is_auto_increment) {
            html += '<div class="prop-group"><span class="field-badge-auto">Auto Increment</span></div>';
        }
        html += '</div>';
        
        propsPanel.innerHTML = html;

        if (field.type === 'select' && field.is_foreign_key) {
            const tableId = field.source_table_id || field.dropdown_table_id || '';
            ensureDropdownSourceTablesLoaded().then(function() {
                if (selectedIndex === null || formFields[selectedIndex] !== field) return;
                if (tableId && !dropdownSourceColumnsCache[String(tableId)]) {
                    ensureDropdownSourceColumnsLoaded(tableId).then(function() {
                        if (selectedIndex !== null && formFields[selectedIndex] === field) {
                            renderPropsPanel(field);
                        }
                    });
                }
            });
        }
    }
    
    // Update Field Property
    window.updateFieldProp = function(propName, value) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        formFields[selectedIndex][propName] = value;
        normalizeFieldState(formFields[selectedIndex]);
        if (propName === 'type') {
            formFields[selectedIndex].inputType = getInputType(value);
            if (['select', 'radio', 'checkboxes'].includes(value)) {
                normalizeChoiceOptions(formFields[selectedIndex]);
            }
            if (value !== 'select') {
                formFields[selectedIndex].dropdown_source = 'static_options';
            }
        }
        renderFields();
        renderPropsPanel(formFields[selectedIndex]);
        updateData();
    };

    window.setForeignKeyColumn = function(kind, value) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        if (kind === 'display') {
            field.label_column = value;
            field.dropdown_label_column = value;
            field.fk_display_column = value;
        }
        syncRelationConfig(field);
        refreshDropdownOptionsFromTable(field).then(function() {
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        });
    };

    window.reloadForeignKeyOptions = function() {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        refreshDropdownOptionsFromTable(field).then(function() {
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        });
    };

    window.setDropdownSourceMode = function(mode) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        field.dropdown_source = mode === 'table' ? 'table' : 'static_options';
        if (mode !== 'table') {
            resetDropdownTableSourceField(field);
            normalizeChoiceOptions(field);
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
            return;
        }

        ensureCurrentTableForeignKeyColumnsLoaded().then(function(columns) {
            if (!field.source_column_id) {
                field.is_foreign_key = false;
            }
            renderPropsPanel(field);
            updateData();
        });
    };

    window.setDropdownSourceForeignKey = function(columnId) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        if (!columnId) {
            field.dropdown_source = 'table';
            field.source_column_id = '';
            resetDropdownTableSourceField(field);
            field.dropdown_source = 'table';
            field.options = [];
            renderFields();
            renderPropsPanel(field);
            updateData();
            return;
        }

        loadDropdownSourceFromForeignKey(field, columnId)
            .then(function() {
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
            })
            .catch(function() {
                alert('Gagal memuat konfigurasi foreign key dari kolom yang dipilih.');
            });
    };

    window.setDropdownSourceTable = function(tableId) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        const table = dropdownSourceTables.find(item => String(item.id) === String(tableId));
        field.dropdown_source = 'table';
        field.source_table_id = tableId ? parseInt(tableId, 10) : '';
        field.source_table_name = table ? table.name : '';
        field.value_column = '';
        field.label_column = '';
        field.options = [];
        field.fk_options = [];
        field.fk_referenced_table = table ? table.name : '';
        field.fk_referenced_column = '';
        field.fk_display_column = '';
        field.relation_table_name = table ? table.name : '';
        field.relation_target_column = field.name || '';
        field.relation_value_column = '';
        field.relation_display_column = '';
        field.is_foreign_key = true;
        ensureDropdownSourceColumnsLoaded(tableId).then(function(columns) {
            const primary = columns.find(column => column.is_primary) || columns.find(column => String(column.name || '').toLowerCase() === 'id') || columns[0] || null;
            const labelColumn = getPreferredDisplayColumn(columns, field.fk_referenced_table || field.source_table_name, primary ? primary.name : '', '');
            if (primary) field.value_column = primary.name;
            if (labelColumn) field.label_column = labelColumn;
            field.fk_referenced_column = field.value_column || '';
            field.fk_display_column = field.label_column || '';
            field.relation_value_column = field.value_column || '';
            field.relation_display_column = field.label_column || '';
            return refreshDropdownOptionsFromTable(field);
        }).then(function() {
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        });
    };

    window.setDropdownSourceColumn = function(propName, value) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        field.dropdown_source = 'table';
        field[propName] = value;
        refreshDropdownOptionsFromTable(field).then(function() {
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        });
    };

    window.reloadDropdownSourceOptions = function() {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        refreshDropdownOptionsFromTable(field).then(function() {
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        });
    };

    window.updateFieldOption = function(index, key, value) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        normalizeChoiceOptions(field);
        if (!field.options[index]) return;
        field.options[index][key] = value;
        normalizeFieldState(field);
        renderFields();
        renderPropsPanel(field);
        updateData();
    };

    window.addFieldOption = function() {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        const options = normalizeChoiceOptions(field);
        const nextIndex = options.length + 1;
        options.push({ value: 'opt' + nextIndex, label: 'Opsi ' + nextIndex });
        normalizeFieldState(field);
        renderFields();
        renderPropsPanel(field);
        updateData();
    };

    window.removeFieldOption = function(index) {
        if (selectedIndex === null || !formFields[selectedIndex]) return;
        const field = formFields[selectedIndex];
        normalizeChoiceOptions(field);
        if (field.options.length <= 1) return;
        field.options.splice(index, 1);
        normalizeFieldState(field);
        renderFields();
        renderPropsPanel(field);
        updateData();
    };

    // Reset Field Code
    window.resetFieldCode = function() {
        if (!formFields[selectedIndex]) return;
        if (confirm('Reset custom code field ini ke base template?')) {
            delete formFields[selectedIndex].customHtml;
            delete formFields[selectedIndex].customCss;
            delete formFields[selectedIndex].customJs;
            renderFields();
            updateData();
        }
    };

    window.resetPageSourceCode = function() {
        if (!confirm('Reset Page Source ke template generated terbaru?')) return;
        fullFormCustomHtml = generatePageSource();
        fullFormCustomCss = '';
        fullFormCustomJs = '';
        if (monacoEditor) {
            isSyncingCode = true;
            monacoEditor.setValue(fullFormCustomHtml);
            isSyncingCode = false;
        }
        updateCustomCodeInputs();
        renderCanvasMode();
    };

    // Tab Switching Logic
    document.querySelectorAll('.prop-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            var tab = this.dataset.tab;
            document.querySelectorAll('.prop-tab-btn').forEach(function(b) {
                b.classList.toggle('active', b === btn);
                b.style.borderBottomColor = (b.classList.contains('active')) ? '#6366f1' : 'transparent';
            });
            document.querySelectorAll('.prop-tab-content').forEach(function(c) {
                c.style.display = (c.id === 'properties-' + tab + '-tab') ? 'flex' : 'none';
            });
            if (tab === 'code') {
                initMonacoEditor();
            }
        });
    });

    // Monaco Editor Logic
    var monacoEditor = null;
    var currentCodeLang = 'html';
    var isSyncingCode = false;
    var activeCodeScope = 'component';
    var fullFormCustomHtml = '';
    var fullFormCustomCss = '';
    var fullFormCustomJs = '';

    window.initMonacoEditor = function() {
        if (monacoEditor) {
            if (activeCodeScope === 'page') {
                loadFormCustomCodeFromState();
            } else {
                loadFieldCodeFromState();
            }
            renderCanvasMode();
            return;
        }

        require.config({
            paths: {
                vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs'
            }
        });
        require(['vs/editor/editor.main'], function() {
            monacoEditor = monaco.editor.create(document.getElementById('monaco-editor-container'), {
                value: '',
                language: 'html',
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: { enabled: false },
                fontSize: 12,
                lineNumbers: 'on',
                scrollBeyondLastLine: false,
                padding: { top: 10 }
            });

            monacoEditor.onDidChangeModelContent(function() {
                if (isSyncingCode) return;
                if (activeCodeScope === 'page') {
                    updateFormCustomCodeInState();
                } else {
                    updateFieldCodeInState();
                }
            });

            if (activeCodeScope === 'page') {
                loadFormCustomCodeFromState();
            } else {
                loadFieldCodeFromState();
            }
            setCodeScope(activeCodeScope);
        });
    };

    window.loadFieldCodeFromState = function() {
        if (!monacoEditor || !formFields[selectedIndex]) return;

        var field = formFields[selectedIndex];
        isSyncingCode = true;

        var codeKey = 'custom' + currentCodeLang.charAt(0).toUpperCase() + currentCodeLang.slice(1);
        var code = field[codeKey] || '';
        if (isRelationSelectField(field) && looksLikeDummySelectCode(code)) {
            code = '';
        }

        // Load base code template if no custom code exists
        if (!code) {
            code = getFieldBaseCode(field.type, currentCodeLang);
        }

        monacoEditor.setValue(code || '');
        isSyncingCode = false;
    };

    // Base code templates per field type
    function getFieldBaseCode(fieldType, lang) {
        var baseCodeTemplates = {
            text: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="text" name="{name}" class="field-input" placeholder="{placeholder}" />\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                js: ''
            },
            email: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="email" name="{name}" class="field-input" placeholder="{placeholder}" />\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                js: ''
            },
            password: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="password" name="{name}" class="field-input" />\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                js: ''
            },
            number: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="number" name="{name}" class="field-input" placeholder="{placeholder}" />\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                js: ''
            },
            textarea: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <textarea name="{name}" class="field-textarea" rows="4" placeholder="{placeholder}"></textarea>\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-textarea {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  resize: vertical;\n}',
                js: ''
            },
            select: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <select name="{name}" class="field-select">\n    <option value="">Pilih...</option>\n    {options}\n  </select>\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-select {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  background: white;\n}',
                js: ''
            },
            checkbox: {
                html: '<div class="field-wrapper">\n  <label class="field-checkbox">\n    <input type="checkbox" name="{name}" />\n    <span>{label}</span>\n  </label>\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-checkbox {\n  display: flex;\n  align-items: center;\n  gap: 8px;\n  cursor: pointer;\n}\n.field-checkbox input {\n  width: 18px;\n  height: 18px;\n}',
                js: ''
            },
            date: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="date" name="{name}" class="field-input" />\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                js: ''
            },
            file: {
                html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <div class="file-upload">\n    <input type="file" name="{name}" class="field-input" />\n    <span class="file-hint">Klik atau drag file ke sini</span>\n  </div>\n</div>',
                css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.file-upload {\n  border: 2px dashed #e2e8f0;\n  border-radius: 8px;\n  padding: 24px;\n  text-align: center;\n}\n.file-hint {\n  display: block;\n  color: #94a3b8;\n  font-size: 13px;\n  margin-top: 8px;\n}',
                js: ''
            }
        };

        var template = baseCodeTemplates[fieldType] || baseCodeTemplates.text;
        var code = template[lang] || '';
        
        // Replace placeholders with field values
        if (formFields[selectedIndex]) {
            code = code.replace(/{label}/g, formFields[selectedIndex].label || 'Label');
            code = code.replace(/{placeholder}/g, formFields[selectedIndex].placeholder || '');
            code = code.replace(/{name}/g, formFields[selectedIndex].name || getFieldTokenName(formFields[selectedIndex], selectedIndex));
            code = code.replace(/{type}/g, formFields[selectedIndex].type || 'text');
        }
        
        return code;
    }

    window.updateFieldCodeInState = function() {
        if (!monacoEditor || !formFields[selectedIndex]) return;

        var code = monacoEditor.getValue();
        var codeKey = 'custom' + currentCodeLang.charAt(0).toUpperCase() + currentCodeLang.slice(1);
        formFields[selectedIndex][codeKey] = code;

        clearTimeout(window.fieldCodeUpdateTimer);
        window.fieldCodeUpdateTimer = setTimeout(function() {
            updateData();
        }, 500);
    };

    window.switchCodeLang = function(lang) {
        currentCodeLang = lang;
        document.querySelectorAll('.code-lang-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.lang === lang);
            btn.style.background = btn.classList.contains('active') ? '#6366f1' : 'transparent';
            btn.style.color = btn.classList.contains('active') ? 'white' : '#94a3b8';
            btn.style.borderColor = btn.classList.contains('active') ? '#6366f1' : '#475569';
        });

        if (monacoEditor && typeof monaco !== 'undefined') {
            var model = monacoEditor.getModel();
            if (model) {
                var language = currentCodeLang === 'js' ? 'javascript' : currentCodeLang;
                monaco.editor.setModelLanguage(model, language);
            }
        }

        loadFieldCodeFromState();
    };

    window.setCodeScope = function(scope) {
        activeCodeScope = scope === 'page' ? 'page' : 'component';
        document.querySelectorAll('.code-scope-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.scope === activeCodeScope);
        });
        const hint = document.getElementById('code-mode-hint');
        if (hint) {
            hint.textContent = activeCodeScope === 'page'
                ? 'Preview canvas memakai Page Source ini. HTML/CSS di sini akan menggantikan UI builder default.'
                : 'Edit custom code untuk field yang dipilih (HTML/CSS/JS terpisah).';
        }
        const tools = document.getElementById('component-code-tools');
        if (tools) {
            tools.style.display = activeCodeScope === 'page' ? 'none' : 'flex';
        }
        const pageTools = document.getElementById('page-code-tools');
        if (pageTools) {
            pageTools.style.display = activeCodeScope === 'page' ? 'flex' : 'none';
        }
        
        // Handle code scope switching
        if (activeCodeScope === 'page') {
            // Generate and display page source
            if (monacoEditor) {
                const pageSource = fullFormCustomHtml || generatePageSource();
                isSyncingCode = true;
                monacoEditor.setValue(pageSource);
                isSyncingCode = false;
                
                // Update language to HTML and hide component-specific tools
                if (typeof monaco !== 'undefined') {
                    var model = monacoEditor.getModel();
                    if (model) {
                        monaco.editor.setModelLanguage(model, 'html');
                    }
                }
            }
        } else if (activeCodeScope === 'component') {
            // Show component code
            if (monacoEditor && formFields[selectedIndex]) {
                loadFieldCodeFromState();
            }
        }
        renderCanvasMode();
    };

    function updateFormCustomCodeInState() {
        if (!monacoEditor) return;
        fullFormCustomHtml = monacoEditor.getValue();
        fullFormCustomCss = '';
        fullFormCustomJs = '';
        updateCustomCodeInputs();
        renderCanvasMode();
    }

    function loadFormCustomCodeFromState() {
        if (!monacoEditor) return;
        isSyncingCode = true;
        monacoEditor.setValue(fullFormCustomHtml || generatePageSource());
        isSyncingCode = false;
        renderCanvasMode();
    }

    function updateCustomCodeInputs() {
        const useInput = document.getElementById('use-custom-code-input');
        const htmlInput = document.getElementById('custom-html-input');
        const cssInput = document.getElementById('custom-css-input');
        const jsInput = document.getElementById('custom-js-input');
        const useCustom = activeCodeScope === 'page' && (fullFormCustomHtml || '').trim() !== '';
        if (useInput) useInput.value = useCustom ? '1' : '0';
        if (htmlInput) htmlInput.value = useCustom ? fullFormCustomHtml : '';
        if (cssInput) cssInput.value = useCustom ? fullFormCustomCss : '';
        if (jsInput) jsInput.value = useCustom ? fullFormCustomJs : '';
    }

    function getCustomSourceForCanvas() {
        const source = activeCodeScope === 'page' && monacoEditor ? monacoEditor.getValue() : fullFormCustomHtml;
        return resolveFormSourceTokens((source || '').trim() || generatePageSource());
    }

    function humanizeFieldName(value) {
        return String(value || '')
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, function(match) {
                return match.toUpperCase();
            });
    }

    function getFieldTokenName(field, index) {
        return String(field.name || field.field_name || field.column_name || field.id || ('field_' + (index + 1)))
            .trim()
            .replace(/[^a-zA-Z0-9_]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function getFieldLabel(field, index) {
        return field.label || field.field_label || field.labelText || humanizeFieldName(getFieldTokenName(field, index));
    }

    function getFieldPlaceholder(field, index) {
        if (field.placeholder) return field.placeholder;
        const type = field.type || 'text';
        if (type === 'date') return '';
        if (type === 'select') return 'Pilih ' + getFieldLabel(field, index).toLowerCase();
        return 'Masukkan ' + getFieldLabel(field, index).toLowerCase();
    }

    function escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function applyFieldTokensToCode(code, field, index) {
        let resolved = String(code || '')
            .replace(/\{label\}/g, getFieldLabel(field, index))
            .replace(/\{placeholder\}/g, getFieldPlaceholder(field, index))
            .replace(/\{name\}/g, field.name || getFieldTokenName(field, index))
            .replace(/\{type\}/g, field.type || 'text');
        if (String(code || '').indexOf('{options}') !== -1) {
            resolved = resolved.replace(/\{options\}/g, buildSelectOptionsMarkup(field));
        }
        return resolved;
    }

    function normalizeGeneratedFieldMarkup(markup, field, index) {
        let resolved = String(markup || '');
        const fieldName = field.name || getFieldTokenName(field, index);
        const fieldLabel = getFieldLabel(field, index);
        const fieldPlaceholder = getFieldPlaceholder(field, index);

        resolved = resolved.replace(/(<label\b[^>]*class="field-label"[^>]*>)([\s\S]*?)(<\/label>)/i, function(_, open, _text, close) {
            return open + escapeHtml(fieldLabel) + close;
        });

        resolved = resolved.replace(/<(input|select|textarea)\b([^>]*)\bname=(["'])(.*?)\3([^>]*)>/i, function(match, tag, before, quote, _name, after) {
            return '<' + tag + before + 'name=' + quote + escapeAttr(fieldName) + quote + after + '>';
        });

        if (String(field.type || '').toLowerCase() === 'select') {
            const optionsMarkup = buildSelectOptionsMarkup(field);
            const placeholderLabel = escapeHtml(field.placeholder || 'Pilih...');
            resolved = resolved.replace(/<select\b([\s\S]*?)>[\s\S]*?<\/select>/i, function(_match, attrs) {
                return '<select' + attrs + '>\n    <option value="">' + placeholderLabel + '</option>' + (optionsMarkup ? '\n    ' + optionsMarkup.split('\n').join('\n    ') + '\n  ' : '\n  ') + '</select>';
            });
        } else if (resolved.includes('placeholder=')) {
            resolved = resolved.replace(/placeholder=(["'])(.*?)\1/i, 'placeholder="' + escapeAttr(fieldPlaceholder) + '"');
        } else if (/<(input|textarea)\b/i.test(resolved) && String(field.type || '').toLowerCase() !== 'date') {
            resolved = resolved.replace(/<(input|textarea)\b([^>]*)>/i, function(match, tag, attrs) {
                if (tag.toLowerCase() === 'input' || tag.toLowerCase() === 'textarea') {
                    return '<' + tag + attrs + ' placeholder="' + escapeAttr(fieldPlaceholder) + '">';
                }
                return match;
            });
        }

        return resolved;
    }

    function resolveFormSourceTokens(source) {
        let resolved = String(source || '');

        formFields.forEach(function(field, index) {
            const name = getFieldTokenName(field, index);
            const label = getFieldLabel(field, index);
            const placeholder = getFieldPlaceholder(field, index);
            resolved = resolved
                .replace(new RegExp('\\{' + name + '_label\\}', 'g'), label)
                .replace(new RegExp('\\{' + name + '_placeholder\\}', 'g'), placeholder)
                .replace(new RegExp('\\{' + name + '_name\\}', 'g'), field.name || name)
                .replace(new RegExp('\\{' + name + '_id\\}', 'g'), field.id || name);

            const fieldName = field.name || name;
            const namePattern = escapeRegExp(fieldName);
            const labelPattern = new RegExp('(<label\\b[^>]*>)\\{label\\}(<\\/label>[\\s\\S]*?<(?:input|select|textarea)\\b(?=[^>]*\\bname=[\"\\\']' + namePattern + '[\"\\\']))', 'gi');
            const placeholderPattern = new RegExp('(<(?:input|textarea)\\b(?=[^>]*\\bname=[\"\\\']' + namePattern + '[\"\\\'])(?=[^>]*\\bplaceholder=[\"\\\'])[^>]*\\bplaceholder=[\"\\\'])\\{placeholder\\}([\"\\\'][^>]*>)', 'gi');
            resolved = resolved
                .replace(labelPattern, '$1' + label + '$2')
                .replace(placeholderPattern, '$1' + placeholder + '$2');
        });

        return resolved;
    }

    function renderCanvasMode() {
        const workspace = document.querySelector('.builder-workspace');
        if (!workspace || !workspace.parentNode) return;
        const generatorToolbar = workspace.previousElementSibling;

        let preview = document.getElementById('custom-code-canvas-preview');
        if (activeCodeScope !== 'page') {
            if (generatorToolbar) {
                generatorToolbar.style.display = '';
            }
            workspace.style.display = '';
            if (preview) preview.remove();
            return;
        }

        if (generatorToolbar) {
            generatorToolbar.style.display = 'none';
        }
        workspace.style.display = 'none';
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'custom-code-canvas-preview';
            preview.style.cssText = 'display:flex;flex:1 1 auto;min-height:620px;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,0.08);';
            workspace.parentNode.insertBefore(preview, workspace.nextSibling);
        }

        preview.innerHTML = '';
        const iframe = document.createElement('iframe');
        iframe.title = 'Custom Code Preview';
        iframe.srcdoc = getCustomSourceForCanvas();
        iframe.setAttribute('sandbox', 'allow-scripts allow-forms allow-same-origin');
        iframe.style.cssText = 'display:block;width:100%;height:100%;min-height:620px;border:0;background:#fff;';
        preview.appendChild(iframe);
    }

    function logSubmitPayload(form) {
        if (!window.console || !console.debug) return;
        const payload = new FormData(form);
        console.debug('MasterForm custom code payload', {
            use_custom_code: payload.get('MasterForm[use_custom_code]'),
            custom_html_length: String(payload.get('MasterForm[custom_html]') || '').length,
            custom_css_length: String(payload.get('MasterForm[custom_css]') || '').length,
            custom_js_length: String(payload.get('MasterForm[custom_js]') || '').length
        });
    }
    
    // Generate full page HTML source from all fields
    function generatePageSource() {
        const lines = [];
        lines.push('<!-- Generated Form Layout -->');
        lines.push('<form class="auto-generated-form" method="POST">');
        lines.push('  <div class="form-container" style="max-width: 600px; margin: 0 auto;">');
        
        formFields.forEach((field, index) => {
            if (field.excluded) return;
            lines.push('');
            lines.push('    <!-- Field ' + (index + 1) + ': ' + field.label + ' -->');
            
            if (field.customHtml && !(isRelationSelectField(field) && looksLikeDummySelectCode(field.customHtml))) {
                lines.push('    ' + normalizeGeneratedFieldMarkup(applyFieldTokensToCode(field.customHtml, field, index), field, index).split('\n').join('\n    '));
            } else {
                // Use base template
                const baseCode = getFieldBaseCode(field.type, 'html');
                lines.push('    ' + normalizeGeneratedFieldMarkup(applyFieldTokensToCode(baseCode, field, index), field, index).split('\n').join('\n    '));
            }
        });
        
        lines.push('');
        lines.push('    <!-- Submit Button -->');
        lines.push('    <div style="margin-top: 24px;">');
        lines.push('      <button type="submit" class="btn-submit" style="width: 100%; padding: 12px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">');
        lines.push('        Submit');
        lines.push('      </button>');
        lines.push('    </div>');
        lines.push('  </div>');
        lines.push('</form>');
        lines.push('');
        lines.push('<!-- Embedded Styles -->');
        lines.push('<style>');
        lines.push('.form-container { padding: 24px; background: #ffffff; border-radius: 12px; }');
        
        // Collect all custom CSS
        formFields.forEach((field, index) => {
            if (field.customCss) {
                lines.push('');
                lines.push('/* Field ' + (index + 1) + ' */');
                lines.push(field.customCss);
            } else {
                const baseCode = getFieldBaseCode(field.type, 'css');
                if (baseCode) {
                    lines.push('');
                    lines.push('/* Field ' + (index + 1) + ' Default Styles */');
                    lines.push(baseCode);
                }
            }
        });
        
        lines.push('</style>');
        
        // Check if there's any JS
        const hasJs = formFields.some(f => f.customJs);
        if (hasJs) {
            lines.push('');
            lines.push('<!-- Embedded Scripts -->');
            lines.push('<script>');
            formFields.forEach((field, index) => {
                if (field.customJs) {
                    lines.push('');
                    lines.push('// Field ' + (index + 1) + ' - ' + field.label);
                    lines.push(field.customJs);
                }
            });
            lines.push('<\\/script>');
        }
        
        return lines.join('\n');
    }
    
    
    // Add direct and delegated handlers so dynamically refreshed panels still switch renderer.
    document.querySelectorAll('.code-scope-btn').forEach(function(btn) {
        btn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            setCodeScope(this.dataset.scope);
        });
    });
    document.addEventListener('click', function(event) {
        const scopeButton = event.target.closest('.code-scope-btn');
        if (!scopeButton) return;
        event.preventDefault();
        setCodeScope(scopeButton.dataset.scope);
    });
    
    // Render Fields
    function renderFields() {
        if (activeCodeScope === 'page') {
            renderCanvasMode();
        }

        if (formFields.length === 0) {
            if (placeholder) placeholder.style.display = 'block';
            if (container) container.innerHTML = '';
            if (fieldCountHint) fieldCountHint.textContent = '0 fields';
            if (window.formSortableInstance) {
                window.formSortableInstance.destroy();
                window.formSortableInstance = null;
            }
            renderPropsPanel(null);
            return;
        }
        
        if (placeholder) placeholder.style.display = 'none';
        if (fieldCountHint) fieldCountHint.textContent = formFields.length + ' field' + (formFields.length > 1 ? 's' : '');
        
        container.innerHTML = formFields.map((field, i) => {
            const selected = selectedIndex === i ? 'selected' : '';
            const isExcluded = field.excluded === true;
            return '<div class="field-item ' + selected + '" data-index="' + i + '" data-field-id="' + field.id + '">' +
                '<div class="field-item-header">' +
                '<div class="field-item-label">' +
                '<span class="material-symbols-outlined field-drag-handle" data-drag="' + i + '">drag_indicator</span>' +
                '<span class="material-symbols-outlined">' + (fieldIcons[field.type] || 'text_fields') + '</span>' +
                field.label +
                (field.required ? '<span class="field-item-required">*</span>' : '') +
                (field.is_foreign_key ? '<span class="field-badge-fk">FK</span>' : '') +
                '</div>' +
                '<div class="field-actions">' +
                (isExcluded ? '<span class="field-badge-auto" style="margin-right:4px;">Hidden</span>' : '') +
                '<button class="field-actions-btn" data-duplicate="' + i + '" title="Duplicate"><span class="material-symbols-outlined">content_copy</span></button>' +
                '<button class="field-actions-btn" data-settings="' + i + '" title="Settings"><span class="material-symbols-outlined">tune</span></button>' +
                '<button class="field-actions-btn delete" data-delete="' + i + '" title="Delete"><span class="material-symbols-outlined">delete</span></button>' +
                '</div>' +
                '</div>' +
                (isExcluded ? '<div class="field-preview" style="background:#fef3c7;border-color:#fcd34d;"><span style="color:#92400e;font-size:12px;">Field disembunyikan dari form (excluded)</span></div>' : renderPreview(field)) +
                '<div class="field-name">Name: ' + field.name + (field.is_foreign_key ? ' <span class="field-badge-fk">→ ' + field.fk_referenced_table + '</span>' : '') + '</div>' +
                '</div>';
        }).join('');
        
        // Event listeners
        container.querySelectorAll('.field-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.dataset.delete || e.target.closest('[data-delete]')) return;
                selectField(parseInt(this.dataset.index));
            });
        });
        
        container.querySelectorAll('[data-delete]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                deleteField(parseInt(this.dataset.delete));
            });
        });
        
        container.querySelectorAll('[data-duplicate]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                duplicateField(parseInt(this.dataset.duplicate));
            });
        });
        
        container.querySelectorAll('[data-settings]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                selectField(parseInt(this.dataset.settings));
            });
        });
        
        initSortable();
    }
    
    // Duplicate Field
    function duplicateField(index) {
        if (index >= 0 && index < formFields.length) {
            const original = formFields[index];
            const newField = JSON.parse(JSON.stringify(original));
            newField.id = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            newField.name = original.name + '_copy';
            newField.label = original.label + ' (Copy)';
            normalizeFieldState(newField);
            formFields.splice(index + 1, 0, newField);
            renderFields();
            updateData();
        }
    }
    
    // Delete Field
    function deleteField(index) {
        if (index >= 0 && index < formFields.length) {
            formFields.splice(index, 1);
            if (selectedIndex === index) selectedIndex = null;
            else if (selectedIndex > index) selectedIndex--;
            renderFields();
            updateData();
        }
    }
    
    // Add Field
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
        normalizeFieldState(field);
        
        formFields.push(field);
        renderFields();
        updateData();
        selectField(formFields.length - 1);
    }
    
    // Update Data
    function updateData() {
        const removedSystemFields = removeSystemFieldsFromState();
        const input = document.getElementById('form-data-input');
        if (input) {
            formFields = formFields.map(normalizeFieldState);
            input.value = JSON.stringify(formFields);
        }
        if (removedSystemFields) {
            renderFields();
            if (selectedIndex === null && propsPanel) {
                propsPanel.innerHTML = '<div class="no-selection"><span class="material-symbols-outlined">touch_app</span><p style="font-size:14px">Pilih field untuk edit</p></div>';
            }
        }
    }

    function removeSystemFieldsFromState() {
        const beforeCount = formFields.length;
        formFields = formFields.map(normalizeFieldState);
        formFields = formFields.filter(field => !isSystemField(field.name || field.field_name || field.field_key));
        if (formFields.length !== beforeCount || (selectedIndex !== null && !formFields[selectedIndex])) {
            selectedIndex = null;
        }
        return formFields.length !== beforeCount;
    }
    
    // Drag handlers
    componentItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('fieldType', this.dataset.fieldType);
        });
        item.addEventListener('click', function() {
            addField(this.dataset.fieldType);
        });
    });
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    
    dropZone.addEventListener('dragleave', function() {
        dropZone.classList.remove('drag-over');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const fieldType = e.dataTransfer.getData('fieldType');
        if (fieldType) addField(fieldType);
    });
    
    // Form submit
    document.getElementById('master-form-form').addEventListener('submit', function(e) {
        const formName = this.querySelector('[name="MasterForm[form_name]"]').value.trim();
        if (!formName) {
            e.preventDefault();
            alert('Masukkan nama form');
            return;
        }
        const customSource = activeCodeScope === 'page' && monacoEditor ? monacoEditor.getValue().trim() : (fullFormCustomHtml || '').trim();
        if (formFields.length === 0 && customSource === '') {
            e.preventDefault();
            alert('Tambahkan minimal satu field atau custom code');
            return;
        }
        
        const slug = formName.toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        
        let slugInput = this.querySelector('input[name="auto_slug"]');
        if (!slugInput) {
            slugInput = document.createElement('input');
            slugInput.type = 'hidden';
            slugInput.name = 'MasterForm[slug]';
            this.appendChild(slugInput);
        }
        slugInput.value = slug;
        
        updateData();
        if (activeCodeScope === 'page' && monacoEditor) {
            updateFormCustomCodeInState();
        } else {
            updateCustomCodeInputs();
        }
        logSubmitPayload(this);
    });

    // Load tables
    fetch('/tables/get-tables', {
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(res => res.json())
    .then(data => {
        const selector = document.getElementById('table-selector');
        const currentTableId = getCurrentBuilderTableId();
        if (data.tables && data.tables.length > 0) {
            data.tables.forEach(table => {
                const opt = document.createElement('option');
                opt.value = table.id;
                opt.textContent = table.label;
                opt.dataset.name = table.name;
                selector.appendChild(opt);
            });
        }

        if (currentTableId) {
            selector.value = String(currentTableId);
            document.getElementById('table-id-input').value = String(currentTableId);
            ensureDropdownSourceColumnsLoaded(currentTableId);
        }
    });
    
    // Generate fields from table
    document.getElementById('generate-from-table').addEventListener('click', function() {
        const tableId = parseInt(document.getElementById('table-selector').value, 10);
        if (!tableId || isNaN(tableId)) {
            alert('Pilih tabel terlebih dahulu');
            return;
        }
        
        document.getElementById('table-id-input').value = tableId;
        
        fetch('/tables/columns/' + tableId + '?t=' + Date.now(), {
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error: ' + data.error);
                return;
            }
            if (data.columns && data.columns.length > 0) {
                formFields = [];
                let fkPromises = [];
                
                data.columns.forEach(col => {
                    const isPrimaryKey = !!(col.is_primary);
                    const isAutoIncrement = !!(col.is_auto_increment);
                    const isForeignKey = !!(col.is_foreign_key);
                    
                    if (col.is_system_field || isSystemField(col.name) || isPrimaryKey || isAutoIncrement) {
                        return;
                    }

                    const colName = (col.name || '').toLowerCase();
                    
                    let fieldType = 'text';
                    const colType = (col.base_type || col.type || '').toUpperCase();
                    
                    if (isForeignKey) {
                        fieldType = 'select';
                    } else if (colType.includes('INT') || colType.includes('DECIMAL') || colType.includes('FLOAT') || colType.includes('DOUBLE')) {
                        fieldType = 'number';
                    } else if (colType.includes('TEXT') || colType.includes('VARCHAR') || colType.includes('CHAR')) {
                        if (colName.includes('email')) fieldType = 'email';
                        else if (colName.includes('url') || colName.includes('website')) fieldType = 'url';
                        else if (colName.includes('phone') || colName.includes('telepon')) fieldType = 'tel';
                    } else {
                        fieldType = getFieldTypeFromColumnType(colType);
                    }
                    
                    const fieldId = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                    const fieldData = {
                        id: fieldId,
                        type: fieldType,
                        inputType: getInputType(fieldType),
                        label: col.label || col.name,
                        name: col.name,
                        field_name: col.name,
                        field_key: col.name,
                        column_name: col.name,
                        required: !col.is_nullable,
                        placeholder: '',
                        default_value: col.default_value || '',
                        excluded: false,
                        source_column_id: col.id,
                        source_column_type: colType,
                        is_foreign_key: isForeignKey,
                        is_primary: isPrimaryKey,
                        is_auto_increment: isAutoIncrement,
                        local_column: col.name,
                        fk_referenced_table: isForeignKey ? col.referenced_table_name : null,
                        fk_referenced_column: isForeignKey ? col.referenced_column_name : null,
                        fk_display_column: isForeignKey ? col.referenced_column_name : null,
                        relation_table_name: isForeignKey ? col.referenced_table_name : null,
                        relation_target_column: col.name,
                        relation_value_column: isForeignKey ? col.referenced_column_name : null,
                        relation_display_column: isForeignKey ? col.referenced_column_name : null,
                        relation_config: isForeignKey ? {
                            local_column: col.name,
                            source_column: col.name,
                            column_name: col.name,
                            referenced_table: col.referenced_table_name || '',
                            referenced_table_name: col.referenced_table_name || '',
                            referenced_value_column: col.referenced_column_name || '',
                            referenced_column: col.referenced_column_name || '',
                            referenced_column_name: col.referenced_column_name || '',
                            value_column: col.referenced_column_name || '',
                            display_column: col.referenced_column_name || '',
                            display_column_name: col.referenced_column_name || ''
                        } : null,
                        fk_options: isForeignKey ? [] : null,
                        fk_options_loaded: false,
                    };
                    
                    normalizeFieldState(fieldData);
                    formFields.push(fieldData);
                    
                    if (isForeignKey && col.id) {
                        fkPromises.push(
                            fetch('/tables/foreign-key-options/' + col.id + '?t=' + Date.now(), {
                                headers: {
                                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                }
                            })
                            .then(res => res.json())
                            .then(fkData => {
                                if (fkData.success && fkData.options) {
                                    const fkField = formFields.find(f => f.source_column_id === col.id);
                                    if (fkField) {
                                        fkField.local_column = fkData.local_column || fkField.name;
                                        fkField.source_table_id = fkData.referenced_table_id || fkField.source_table_id || '';
                                        fkField.dropdown_table_id = fkField.source_table_id || fkField.dropdown_table_id || '';
                                        fkField.source_table_name = fkData.referenced_table || fkField.source_table_name || '';
                                        fkField.fk_options = fkData.options;
                                        fkField.fk_options_loaded = true;
                                        fkField.fk_display_column = fkData.display_column;
                                        fkField.fk_referenced_table = fkData.referenced_table;
                                        fkField.fk_referenced_column = fkData.referenced_value_column || col.referenced_column_name || fkField.fk_referenced_column;
                                        fkField.value_column = fkField.fk_referenced_column;
                                        fkField.dropdown_value_column = fkField.fk_referenced_column;
                                        fkField.label_column = fkData.display_column || fkField.label_column;
                                        fkField.dropdown_label_column = fkField.label_column;
                                        fkField.relation_table_name = fkData.referenced_table;
                                        fkField.relation_display_column = fkData.display_column;
                                        fkField.relation_value_column = fkField.fk_referenced_column;
                                        fkField.relation_config = Object.assign({}, fkField.relation_config || {}, {
                                            local_column: fkField.local_column || fkField.name,
                                            source_column: fkField.local_column || fkField.name,
                                            column_name: fkField.local_column || fkField.name,
                                            referenced_table: fkField.fk_referenced_table || '',
                                            referenced_table_name: fkField.fk_referenced_table || '',
                                            referenced_value_column: fkField.fk_referenced_column || '',
                                            referenced_column: fkField.fk_referenced_column || '',
                                            referenced_column_name: fkField.fk_referenced_column || '',
                                            value_column: fkField.fk_referenced_column || '',
                                            display_column: fkField.fk_display_column || '',
                                            display_column_name: fkField.fk_display_column || ''
                                        });
                                    }
                                }
                            })
                            .catch(err => {
                                console.error('Error loading FK options for column', col.name, ':', err);
                            })
                        );
                    }
                });
                
                Promise.all(fkPromises).then(() => {
                    renderFields();
                    updateData();
                });
                
                const formNameInput = document.querySelector('[name="MasterForm[form_name]"]');
                if (!formNameInput.value && data.table_label) {
                    formNameInput.value = data.table_label + ' Form';
                }
            }
        })
        .catch(err => {
            console.error('Error fetching columns:', err);
            alert('Gagal mengambil kolom tabel');
        });
    });
    
    function getInputType(fieldType) {
        const types = {
            text: 'text', email: 'email', password: 'password', number: 'number',
            tel: 'tel', url: 'url', textarea: 'textarea', select: 'select',
            radio: 'radio', checkbox: 'checkbox', checkboxes: 'checkbox',
            date: 'date', time: 'time', datetime: 'datetime-local',
            file: 'file', hidden: 'hidden'
        };
        return types[fieldType] || 'text';
    }

    function getFieldTypeFromColumnType(columnType) {
        const normalizedType = String(columnType || '').trim().toUpperCase().match(/^[A-Z]+/)?.[0] || '';
        if (normalizedType === 'DATE') return 'date';
        if (normalizedType === 'TIME') return 'time';
        if (normalizedType === 'DATETIME' || normalizedType === 'TIMESTAMP') return 'datetime';
        return 'text';
    }

    function isSystemField(fieldName) {
        const normalizedName = String(fieldName || '').trim().toLowerCase();
        const systemFields = [
            'created_by',
            'updated_by',
            'deleted_by',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_ip',
            'updated_ip'
        ];

        return systemFields.includes(normalizedName);
    }
});
</script>

<?php if (!empty($formNameErrors)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    alert(<?= json_encode($formNameErrors[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
});
</script>
<?php endif; ?>
