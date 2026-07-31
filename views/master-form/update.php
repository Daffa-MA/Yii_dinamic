<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\MasterPage;

/* @var $this yii\web\View */
/* @var $model app\models\MasterForm */

$this->title = 'Edit Form: ' . $model->form_name;
$this->params['breadcrumbs'][] = ['label' => 'Master Forms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// **CRITICAL**: Load existing form data for hydration
// PERBAIKAN BUG 2: form_data di DB sudah JSON string; gunakan getFormDataArray() agar tidak double-encode
$formDataArray = $model->getFormDataArray();
$existingFields = !empty($formDataArray)
    ? json_encode($formDataArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : '[]';
$activeLayout = $model->getActiveLayout()->one();
$existingUseCustomCode = !empty($model->custom_code_mode)
    || ($model->hasAttribute('use_custom_code') && !empty($model->use_custom_code))
    || ($activeLayout && $activeLayout->hasAttribute('use_custom_code') && !empty($activeLayout->use_custom_code));
$existingCustomHtml = $model->hasAttribute('custom_html') && (string)$model->custom_html !== '' ? (string)$model->custom_html : ($activeLayout ? (string)$activeLayout->custom_html : '');
$existingCustomCss = $model->hasAttribute('custom_css') && (string)$model->custom_css !== '' ? (string)$model->custom_css : ($activeLayout ? (string)$activeLayout->custom_css : '');
$existingCustomJs = $model->hasAttribute('custom_js') && (string)$model->custom_js !== '' ? (string)$model->custom_js : ($activeLayout ? (string)$activeLayout->custom_js : '');
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

    .dashboard-main>.container-fluid {
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
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
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

    .canvas-content>form {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .canvas-content>form>div:first-of-type {
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

    .cond-rule-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 6px;
        overflow: hidden;
    }

    .cond-rule-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #f8fafc;
    }

    .cond-rule-header .cond-summary {
        font-size: 11px;
        color: #334155;
        line-height: 1.5;
    }

    .cond-remove-btn {
        border: none;
        background: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 2px 6px;
        font-size: 13px;
        line-height: 1;
        border-radius: 4px;
        transition: all 0.15s;
        flex-shrink: 0;
    }

    .cond-remove-btn:hover {
        background: #fef2f2;
        color: #ef4444;
    }

    /* Step builder */
    .cond-builder-step {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 6px;
        overflow: hidden;
    }

    .cond-builder-step .cond-step-label {
        font-size: 10px;
        font-weight: 700;
        color: #6366f1;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 8px 12px 4px;
    }

    .cond-builder-step .cond-step-body {
        padding: 4px 12px 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .cond-keyword {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }

    .cond-keyword-op {
        font-size: 13px;
        font-weight: 600;
        color: #6366f1;
        white-space: nowrap;
    }

    .cond-builder-select {
        flex: 1;
        min-width: 120px;
        font-size: 12px;
        padding: 6px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        color: #0f172a;
    }

    .cond-builder-select:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
    }

    .cond-builder-input {
        flex: 1;
        min-width: 100px;
        font-size: 12px;
        padding: 6px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        color: #0f172a;
    }

    .cond-builder-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
    }

    .cond-save-btn {
        flex: 1;
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        background: #6366f1;
        color: #fff;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .cond-save-btn:hover {
        background: #4f46e5;
    }

    .cond-cancel-btn {
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .cond-cancel-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
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

    #properties-panel>* {
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
        <div class="component-item" draggable="true" data-field-type="phone">
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
        <div class="component-item" draggable="true" data-field-type="dropdown">
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
        <div class="component-item" draggable="true" data-field-type="toggle">
            <span class="material-symbols-outlined">toggle_on</span>
            <span>Switch Toggle</span>
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
        <div class="component-item" draggable="true" data-field-type="file_upload">
            <span class="material-symbols-outlined">upload_file</span>
            <span>File Upload</span>
        </div>
        <div class="component-item" draggable="true" data-field-type="camera">
            <span class="material-symbols-outlined">photo</span>
            <span>Camera</span>
        </div>
        <div class="component-item" draggable="true" data-field-type="gps_camera">
            <span class="material-symbols-outlined">photo_camera</span>
            <span>GPS Camera</span>
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
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h6v2H8v2h8v-2h-2v-2h6c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H3V4h18v12z"></path>
                    </svg>
                </button>
                <button type="button" class="device-btn" data-device="tablet" onclick="setDevice('tablet')" title="Tablet">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H3V6h18v12zM7 20h10v-2H7v2z"></path>
                    </svg>
                </button>
                <button type="button" class="device-btn" data-device="mobile" onclick="setDevice('mobile')" title="Mobile">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V3h10v16z"></path>
                    </svg>
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
                        <input type="hidden" name="MasterForm[form_data]" id="form-data-input" value="<?= Html::encode($existingFields) ?>">
                        <input type="hidden" name="MasterForm[use_custom_code]" id="use-custom-code-input" value="<?= $existingUseCustomCode ? '1' : '0' ?>">
                        <textarea name="MasterForm[custom_html]" id="custom-html-input" style="display:none;"><?= Html::encode($existingCustomHtml) ?></textarea>
                        <textarea name="MasterForm[custom_css]" id="custom-css-input" style="display:none;"><?= Html::encode($existingCustomCss) ?></textarea>
                        <textarea name="MasterForm[custom_js]" id="custom-js-input" style="display:none;"><?= Html::encode($existingCustomJs) ?></textarea>
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
                            <select id="table-selector" class="prop-input" style="width:200px;display:none;">
                                <option value="">-- Pilih Tabel --</option>
                            </select>
                            <div style="display:flex;flex-direction:column;flex:1;max-width:300px;gap:6px;">
                                <input type="text" name="MasterForm[form_name]" class="prop-input" value="<?= Html::encode($model->form_name) ?>" style="<?= !empty($formNameErrors) ? 'border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.12);' : '' ?>">
                                <?php if (!empty($formNameErrors)): ?>
                                    <div style="font-size:12px;color:#dc2626;line-height:1.4;"><?= Html::encode($formNameErrors[0]) ?></div>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="generate-from-table" class="btn-cancel" style="display:none;align-items:center;gap:6px;">
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
        Update Form: <?= Html::encode($model->form_name) ?>
    </div>
    <div class="builder-toolbar-actions">
        <a href="<?= Url::to(['index']) ?>" class="btn-cancel">Batal</a>
        <button type="submit" form="master-form-form" class="btn-save">
            <span class="material-symbols-outlined">save</span>
            Perbarui Form
        </button>
    </div>
</div>

<script src="/js/master-form-builder.js?v=<?= filemtime(Yii::getAlias('@webroot') . '/js/master-form-builder.js') ?>"></script>

<?php if (!empty($formNameErrors)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert(<?= json_encode($formNameErrors[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
        });
    </script>
<?php endif; ?>
