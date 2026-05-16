<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Workspace Settings';
$this->params['breadcrumbs'][] = ['label' => 'Akses Workspace', 'url' => ['permissions']];
$this->params['breadcrumbs'][] = $this->title;

$cssVars = $model->getCssVars();
$loginBackgroundAsset = $model->getLoginBackgroundAsset();
?>

<style>
    .ws-page {
        background:
            radial-gradient(circle at top right, rgba(79, 70, 229, 0.06), transparent 24%),
            radial-gradient(circle at left top, rgba(15, 23, 42, 0.04), transparent 22%),
            #f4f7fb;
        min-height: 100vh;
        padding: 28px 20px 40px;
    }
    
    .ws-container {
        max-width: 1240px;
        margin: 0 auto;
        width: 100%;
    }
    
    .ws-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        margin-bottom: 18px;
        color: #64748b;
    }
    
    .ws-breadcrumb a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .ws-breadcrumb a:hover { color: #334155; }
    .ws-breadcrumb .separator { color: #cbd5e1; }
    .ws-breadcrumb .current { color: #0f172a; font-weight: 600; }
    
    .ws-header {
        margin-bottom: 26px;
    }
    
    .ws-header-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        background: rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 999px;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .ws-header h1 {
        font-size: 30px;
        font-weight: 750;
        color: #0f172a;
        margin: 0 0 10px;
        letter-spacing: -0.03em;
    }
    
    .ws-header p {
        max-width: 58rem;
        font-size: 14px;
        line-height: 1.7;
        color: #64748b;
        margin: 0;
    }
    
    .ws-layout {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    
    .ws-top-row {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 22px;
        align-items: start;
    }
    
    @media (max-width: 1024px) {
        .ws-top-row {
            grid-template-columns: 1fr;
        }
    }
    
    .ws-preview-wrapper {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .ws-preview-card {
        background: linear-gradient(135deg, <?= Html::encode($model->sidebar_bg_start ?? '#f8fafc') ?> 0%, <?= Html::encode($model->sidebar_bg_end ?? '#f1f5f9') ?> 100%);
        border-radius: 22px;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 30px;
        box-shadow: 0 22px 40px -16px rgba(15, 23, 42, 0.42);
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    
    .ws-preview-card::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60%;
        background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.18) 100%);
        pointer-events: none;
    }
    
    .ws-preview-content {
        position: relative;
        z-index: 1;
    }
    
    .ws-preview-logo {
        width: <?= Html::encode($model->workspace_logo_width ?? 56) ?>px;
        height: <?= Html::encode($model->workspace_logo_height ?? 56) ?>px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        transition: all 0.3s;
    }
    
    .ws-preview-logo .material-symbols-outlined {
        font-size: 28px;
        color: white;
    }
    
    .ws-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: <?= Html::encode($model->sidebar_active_bg_start ?? '#4f46e5') ?>;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: <?= Html::encode($model->sidebar_active_text ?? '#ffffff') ?>;
        margin-bottom: 12px;
    }
    
    .ws-preview-title {
        font-size: 28px;
        font-weight: 800;
        color: <?= Html::encode($model->sidebar_text_color ?? '#475569') ?>;
        margin: 0 0 8px;
        line-height: 1.2;
    }
    
    .ws-preview-subtitle {
        font-size: 14px;
        color: <?= Html::encode($model->sidebar_text_muted ?? '#64748b') ?>;
        margin: 0;
    }
    
    .ws-preview-stats {
        display: flex;
        gap: 24px;
        margin-top: 28px;
        padding-top: 22px;
        border-top: 1px solid <?= Html::encode($model->sidebar_border_color ?? 'rgba(148, 163, 184, 0.16)') ?>;
    }
    
    .ws-preview-stat-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: <?= Html::encode($model->sidebar_text_muted ?? '#64748b') ?>;
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .ws-preview-stat-value {
        font-size: 18px;
        font-weight: 700;
        color: <?= Html::encode($model->sidebar_text_color ?? '#475569') ?>;
    }
    
    .ws-nav-card {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 18px;
        padding: 10px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(226, 232, 240, 0.9);
        backdrop-filter: blur(10px);
    }
    
    .ws-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 15px;
        border-radius: 13px;
        color: #475569;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }
    
    .ws-nav-item:hover {
        background: rgba(241, 245, 249, 0.88);
        color: #0f172a;
    }
    
    .ws-nav-item.active {
        background: linear-gradient(135deg, #334155 0%, #1f2937 100%);
        color: white;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
    }
    
    .ws-nav-item .material-symbols-outlined {
        font-size: 20px;
    }
    
    .ws-cards-column {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    
    .ws-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(226, 232, 240, 0.9);
        scroll-margin-top: 80px;
    }
    
    #section-logo {
        scroll-margin-top: 80px;
    }
    
    #section-workspace {
        scroll-margin-top: 80px;
    }
    
    #section-sidebar {
        scroll-margin-top: 80px;
    }
    
    #section-active {
        scroll-margin-top: 80px;
    }
    
    #section-hover {
        scroll-margin-top: 80px;
    }
    
    #section-topnav {
        scroll-margin-top: 80px;
    }
    
    #section-light {
        scroll-margin-top: 80px;
    }
    
    .ws-card-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 22px;
        padding-bottom: 18px;
        border-bottom: 1px solid #eef2f7;
    }
    
    .ws-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(51, 65, 85, 0.10) 0%, rgba(15, 23, 42, 0.08) 100%);
        color: #334155;
        flex-shrink: 0;
    }
    
    .ws-card-icon .material-symbols-outlined {
        font-size: 22px;
    }

    .ws-card-icon.ws-card-icon-login {
        background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.18);
    }
    
    .ws-card-title-group { flex: 1; }
    
    .ws-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    
    .ws-card-subtitle {
        font-size: 13px;
        color: #64748b;
        margin: 4px 0 0;
    }
    
    .ws-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .ws-section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #eef2f7;
    }
    
    .ws-form-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .ws-form-row-2 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    @media (max-width: 768px) {
        .ws-form-row { grid-template-columns: 1fr; }
        .ws-form-row-2 { grid-template-columns: 1fr; }
    }
    
    .ws-form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .ws-form-group label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }
    
    .ws-form-control {
        height: 44px;
        padding: 0 16px;
        border: 1px solid #dde5ee;
        border-radius: 12px;
        font-size: 14px;
        color: #0f172a;
        background: white;
        transition: all 0.2s ease;
    }
    
    .ws-form-control:focus {
        outline: none;
        border-color: #64748b;
        box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.10);
    }
    
    .ws-form-control::placeholder {
        color: #9ca3af;
    }
    
    .ws-color-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    
    @media (max-width: 600px) {
        .ws-color-row { grid-template-columns: 1fr; }
    }
    
    .ws-color-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .ws-color-group label {
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
    }
    
    .ws-color-picker {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .ws-color-picker input[type="color"] {
        width: 46px;
        height: 46px;
        border: 1px solid #dde5ee;
        border-radius: 12px;
        padding: 4px;
        cursor: pointer;
        background: white;
        flex-shrink: 0;
    }
    
    .ws-color-picker input[type="color"]::-webkit-color-swatch-wrapper { padding: 2px; }
    .ws-color-picker input[type="color"]::-webkit-color-swatch { border-radius: 8px; border: none; }
    
    .ws-color-picker .ws-form-control {
        flex: 1;
        height: 46px;
    }
    
    .ws-logo-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        margin-top: 22px;
        padding: 22px;
        background: #f8fafc;
        border-radius: 18px;
        border: 1px solid #eef2f7;
    }
    
    .ws-logo-preview-box {
        width: 80px;
        height: 80px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        transition: all 0.3s;
    }
    
    .ws-logo-preview-box .material-symbols-outlined {
        font-size: 36px;
        color: white;
    }
    
    .ws-logo-preview-label {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 500;
    }
    
    .ws-logo-mode-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 18px;
    }
    
    .ws-logo-tab {
        flex: 1;
        padding: 10px 16px;
        border: 1px solid #dde5ee;
        background: #f8fafc;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .ws-logo-tab:hover {
        background: #eef2f7;
        color: #0f172a;
    }
    
    .ws-logo-tab.active {
        background: linear-gradient(135deg, #334155 0%, #1f2937 100%);
        color: white;
        border-color: transparent;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
    }
    
    .ws-logo-mode-content {
        transition: all 0.3s;
    }
    
    .ws-logo-upload-area {
        border: 1.5px dashed #d8e1ea;
        border-radius: 16px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
    }
    
    .ws-logo-upload-area:hover {
        border-color: #94a3b8;
        background: rgba(248, 250, 252, 0.9);
    }
    
    .ws-logo-upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    
    .ws-logo-upload-placeholder .material-symbols-outlined {
        font-size: 48px;
        color: #94a3b8;
    }
    
    .ws-logo-upload-placeholder p {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
        margin: 0;
    }
    
    .ws-logo-upload-hint {
        font-size: 12px;
        color: #94a3b8;
    }
    
    .ws-logo-image-preview {
        position: relative;
        display: inline-block;
    }
    
    .ws-logo-image-preview {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        touch-action: none;
    }
    
    .ws-logo-image-preview img {
        max-width: 180px;
        max-height: 180px;
        border-radius: 14px;
        object-fit: contain;
        transition: all 0.2s ease;
        cursor: grab;
        border: 2px solid #e2e8f0;
    }
    
    .ws-logo-image-preview img:active {
        cursor: grabbing;
    }
    
    .ws-logo-image-preview.resizing img {
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    }
    
    .ws-logo-resize-handle {
        position: absolute;
        bottom: -6px;
        right: -6px;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        border-radius: 8px;
        border: 2px solid white;
        cursor: se-resize;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        opacity: 0;
        transition: opacity 0.2s ease;
        user-select: none;
        touch-action: none;
    }
    
    .ws-logo-image-preview:hover .ws-logo-resize-handle {
        opacity: 1;
    }
    
    .ws-logo-resize-handle::after {
        content: '';
        width: 8px;
        height: 8px;
        border-bottom: 2px solid white;
        border-right: 2px solid white;
        transform: rotate(-45deg);
    }
    
    .ws-logo-remove-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid white;
        background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        transition: all 0.2s;
    }
    
    .ws-logo-remove-btn:hover {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
    }
    
    .ws-logo-remove-btn .material-symbols-outlined {
        font-size: 18px;
    }
    
    .ws-logo-upload-status {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px;
        background: rgba(51, 65, 85, 0.08);
        border-radius: 10px;
        margin-top: 12px;
        font-size: 13px;
        color: #334155;
    }
    
    .ws-logo-upload-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid #e2e8f0;
        border-top-color: #334155;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .ws-divider {
        height: 1px;
        background: #eef2f7;
        margin: 28px 0;
    }
    
    .ws-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding: 24px 0 8px;
    }
    
    .ws-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }
    
    .ws-btn .material-symbols-outlined { font-size: 18px; }
    
    .ws-btn-primary {
        background: linear-gradient(135deg, #334155 0%, #1f2937 100%);
        color: white;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.16);
    }
    
    .ws-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 24px rgba(15, 23, 42, 0.20);
    }
    
    .ws-btn-secondary {
        background: white;
        color: #64748b;
        border: 1px solid #dde5ee;
    }
     
    .ws-btn-secondary:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
    }
     
    .ws-logo-sizing-controls {
        margin-top: 24px;
        padding: 22px;
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, rgba(99, 102, 241, 0.04) 100%);
        border: 1px solid rgba(79, 70, 229, 0.12);
        border-radius: 16px;
    }
     
    .ws-size-slider {
        width: 100%;
        height: 8px;
        border-radius: 10px;
        background: linear-gradient(to right, #e2e8f0, #cbd5e1);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }
     
    .ws-size-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        border: 2px solid white;
        transition: all 0.2s ease;
    }
     
    .ws-size-slider::-webkit-slider-thumb:hover {
        transform: scale(1.15);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
    }
     
    .ws-size-slider::-moz-range-thumb {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        border: 2px solid white;
        transition: all 0.2s ease;
    }
     
    .ws-size-slider::-moz-range-thumb:hover {
        transform: scale(1.15);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
    }
     
    .ws-size-slider::-moz-range-track {
        background: transparent;
        border: none;
    }
     
    .ws-size-slider::-moz-range-progress {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        height: 8px;
        border-radius: 10px;
    }

    .ws-media-picker {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) 1.15fr;
        gap: 16px;
        align-items: start;
    }

    .ws-media-preview {
        min-height: 180px;
        border-radius: 22px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.04), rgba(255, 255, 255, 0.85));
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
    }

    .ws-media-preview img,
    .ws-media-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .ws-media-placeholder {
        text-align: center;
        padding: 18px;
        color: #64748b;
    }

    .ws-media-placeholder .material-symbols-outlined {
        font-size: 34px;
        margin-bottom: 8px;
        color: #94a3b8;
    }

    .ws-media-actions {
        display: grid;
        gap: 10px;
    }

    .ws-media-actions .ws-form-control[type="file"] {
        padding: 11px 12px;
        background: #fff;
        border-radius: 14px;
    }

    .ws-media-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ws-media-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(79, 70, 229, 0.08);
        border: 1px solid rgba(79, 70, 229, 0.14);
        color: #4338ca;
        font-size: 12px;
        font-weight: 600;
    }

    .ws-media-help {
        font-size: 12px;
        line-height: 1.6;
        color: #64748b;
        margin: 0;
    }

    @media (max-width: 860px) {
        .ws-media-picker {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="ws-page">
    <div class="ws-container">
        <div class="ws-breadcrumb">
            <a href="<?= \yii\helpers\Url::to(['site/dashboard']) ?>">
                <span class="material-symbols-outlined" style="font-size: 16px;">home</span>
            </a>
            <span class="separator">/</span>
            <a href="<?= \yii\helpers\Url::to(['permissions']) ?>">Akses Workspace</a>
            <span class="separator">/</span>
            <span class="current">Workspace Settings</span>
        </div>
        
        <div class="ws-header">
            <div class="ws-header-badge">
                <span class="material-symbols-outlined">settings</span>
                Tampilan Workspace
            </div>
            <h1>Workspace Settings</h1>
            <p>Atur logo, warna sidebar, state aktif, dan navigasi workspace dari satu tempat dengan tampilan yang lebih tenang dan rapi.</p>
            <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
<a href="<?= \yii\helpers\Url::to(['permissions']) ?>" class="btn btn-dark" style="border-radius:14px;padding:10px 16px;font-weight:700;">Akses Workspace</a>
                <a href="<?= \yii\helpers\Url::to(['project/index']) ?>" class="btn btn-outline-secondary" style="border-radius:14px;padding:10px 16px;font-weight:700;">Kembali ke Project List</a>
            </div>
        </div>
        
        <?php $form = ActiveForm::begin(['id' => 'workspace-settings-form', 'action' => ['save'], 'options' => ['enctype' => 'multipart/form-data']]); ?>
        
        <div class="ws-layout">
            <div class="ws-top-row">
                <div class="ws-preview-wrapper">
                    <div class="ws-preview-card">
                        <div class="ws-preview-content">
                            <div class="ws-preview-logo" id="preview-logo-box" style="<?= !empty($model->workspace_logo_image) ? 'background: none;' : 'background: linear-gradient(135deg, ' . Html::encode($model->workspace_logo_bg) . ' 0%, ' . Html::encode($model->workspace_logo_bg) . ' 100%);' ?>">
                                <?php if (!empty($model->workspace_logo_image)): ?>
                                    <img src="<?= Yii::getAlias('@web/uploads/workspace/') . Html::encode($model->workspace_logo_image) ?>" alt="Logo" id="preview-logo-img" style="width: 100%; height: 100%; object-fit: contain; border-radius: 0;">
                                <?php else: ?>
                                    <span class="material-symbols-outlined" id="preview-logo-icon"><?= Html::encode($model->workspace_logo_icon) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ws-preview-badge">
                                <span class="material-symbols-outlined" style="font-size: 14px;">dashboard</span>
                                <span id="preview-badge"><?= Html::encode($model->workspace_badge) ?></span>
                            </div>
                            <h2 class="ws-preview-title" id="preview-title"><?= Html::encode($model->workspace_title) ?></h2>
                            <p class="ws-preview-subtitle" id="preview-subtitle"><?= Html::encode($model->workspace_subtitle) ?></p>
                            <div class="ws-preview-stats">
                                <div>
                                    <div class="ws-preview-stat-label">Menus</div>
                                    <div class="ws-preview-stat-value">12</div>
                                </div>
                                <div>
                                    <div class="ws-preview-stat-label">Pages</div>
                                    <div class="ws-preview-stat-value">8</div>
                                </div>
                                <div>
                                    <div class="ws-preview-stat-label">Forms</div>
                                    <div class="ws-preview-stat-value">24</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ws-nav-card">
                        <button type="button" class="ws-nav-item" data-section="logo">
                            <span class="material-symbols-outlined">image</span>
                            Logo
                        </button>
                        <button type="button" class="ws-nav-item active" data-section="workspace">
                            <span class="material-symbols-outlined">dashboard</span>
                            Workspace
                        </button>
                        <button type="button" class="ws-nav-item" data-section="login">
                            <span class="material-symbols-outlined">lock</span>
                            Login
                        </button>
                        <button type="button" class="ws-nav-item" data-section="sidebar">
                            <span class="material-symbols-outlined">view_sidebar</span>
                            Sidebar
                        </button>
                        <button type="button" class="ws-nav-item" data-section="active">
                            <span class="material-symbols-outlined">check_circle</span>
                            Active State
                        </button>
                        <button type="button" class="ws-nav-item" data-section="hover">
                            <span class="material-symbols-outlined">touch_app</span>
                            Hover State
                        </button>
                        <button type="button" class="ws-nav-item" data-section="topnav">
                            <span class="material-symbols-outlined">menu</span>
                            Top Navigation
                        </button>
                        <button type="button" class="ws-nav-item" data-section="light">
                            <span class="material-symbols-outlined">light_mode</span>
                            Light Theme
                        </button>
                    </div>
                </div>
                
                <div class="ws-cards-column">
                    <div class="ws-card" id="section-logo">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">image</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Workspace Logo</h3>
                                <p class="ws-card-subtitle">Logo dan identitas visual workspace</p>
                            </div>
                        </div>
                        
                        <div class="ws-logo-mode-tabs">
                            <button type="button" class="ws-logo-tab active" data-mode="icon">Icon Mode</button>
                            <button type="button" class="ws-logo-tab" data-mode="image">Image Upload</button>
                        </div>
                        
                        <div id="logo-mode-icon" class="ws-logo-mode-content">
                            <div class="ws-form-row">
                                <div class="ws-form-group">
                                    <label for="workspacesettings-workspace_logo_icon">Icon Name</label>
                                    <select class="ws-form-control" name="WorkspaceSettings[workspace_logo_icon]" id="logo-icon-select">
                                        <option value="folder_open" <?= $model->workspace_logo_icon === 'folder_open' ? 'selected' : '' ?>>folder_open</option>
                                        <option value="dashboard" <?= $model->workspace_logo_icon === 'dashboard' ? 'selected' : '' ?>>dashboard</option>
                                        <option value="workspace_premium" <?= $model->workspace_logo_icon === 'workspace_premium' ? 'selected' : '' ?>>workspace_premium</option>
                                        <option value="apps" <?= $model->workspace_logo_icon === 'apps' ? 'selected' : '' ?>>apps</option>
                                        <option value="grid_view" <?= $model->workspace_logo_icon === 'grid_view' ? 'selected' : '' ?>>grid_view</option>
                                        <option value="home" <?= $model->workspace_logo_icon === 'home' ? 'selected' : '' ?>>home</option>
                                        <option value="inbox" <?= $model->workspace_logo_icon === 'inbox' ? 'selected' : '' ?>>inbox</option>
                                        <option value="inventory_2" <?= $model->workspace_logo_icon === 'inventory_2' ? 'selected' : '' ?>>inventory_2</option>
                                        <option value="category" <?= $model->workspace_logo_icon === 'category' ? 'selected' : '' ?>>category</option>
                                        <option value="store" <?= $model->workspace_logo_icon === 'store' ? 'selected' : '' ?>>store</option>
                                        <option value="account_tree" <?= $model->workspace_logo_icon === 'account_tree' ? 'selected' : '' ?>>account_tree</option>
                                        <option value="hub" <?= $model->workspace_logo_icon === 'hub' ? 'selected' : '' ?>>hub</option>
                                        <option value="layers" <?= $model->workspace_logo_icon === 'layers' ? 'selected' : '' ?>>layers</option>
                                        <option value="extension" <?= $model->workspace_logo_icon === 'extension' ? 'selected' : '' ?>>extension</option>
                                        <option value="waving_hand" <?= $model->workspace_logo_icon === 'waving_hand' ? 'selected' : '' ?>>waving_hand</option>
                                    </select>
                                </div>
                                <div class="ws-form-group">
                                    <label>Icon Background</label>
                                    <div class="ws-color-picker">
                                        <input type="color" id="logo-bg-color" name="WorkspaceSettings[workspace_logo_bg]" value="<?= Html::encode($model->workspace_logo_bg) ?>">
                                        <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[workspace_logo_bg]" value="<?= Html::encode($model->workspace_logo_bg) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ws-logo-preview">
                                <div class="ws-logo-preview-box" id="logo-preview-box" style="background: linear-gradient(135deg, <?= Html::encode($model->workspace_logo_bg) ?> 0%, <?= Html::encode($model->workspace_logo_bg) ?> 100%);">
                                    <span class="material-symbols-outlined" id="logo-preview-icon"><?= Html::encode($model->workspace_logo_icon) ?></span>
                                </div>
                                <span class="ws-logo-preview-label">Live Preview</span>
                            </div>
                        </div>
                        
                        <div id="logo-mode-image" class="ws-logo-mode-content" style="display: none;">
                            <div class="ws-logo-upload-area" id="logo-upload-area">
                                <?php if (!empty($model->workspace_logo_image)): ?>
                                    <div class="ws-logo-image-preview" id="logo-image-preview-container">
                                        <img src="<?= Yii::getAlias('@web/uploads/workspace/') . Html::encode($model->workspace_logo_image) ?>" alt="Workspace Logo" id="uploaded-logo-preview">
                                        <div class="ws-logo-resize-handle" id="logo-resize-handle"></div>
                                        <button type="button" class="ws-logo-remove-btn" id="remove-logo-btn">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="ws-logo-upload-placeholder">
                                        <span class="material-symbols-outlined">cloud_upload</span>
                                        <p>Drop your logo here or click to upload</p>
                                        <span class="ws-logo-upload-hint">JPG, PNG, WEBP (max 2MB)</span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="logo-image-input" accept=".jpg,.jpeg,.png,.webp" style="display: none;">
                            </div>
                            
                            <div id="logo-sizing-controls" class="ws-logo-sizing-controls" style="<?= empty($model->workspace_logo_image) ? 'display: none;' : '' ?>">
                                <div class="ws-section-title">Logo Size</div>
                                <div class="ws-form-row ws-form-row-2">
                                    <div class="ws-form-group">
                                        <label for="logo-width">Width (px)</label>
                                        <input type="number" id="logo-width" class="ws-form-control logo-size-input" value="<?= Html::encode($model->workspace_logo_width ?? 120) ?>" min="40" max="300">
                                    </div>
                                    <div class="ws-form-group">
                                        <label for="logo-height">Height (px)</label>
                                        <input type="number" id="logo-height" class="ws-form-control logo-size-input" value="<?= Html::encode($model->workspace_logo_height ?? 120) ?>" min="40" max="300">
                                    </div>
                                </div>
                                <div class="ws-form-group">
                                    <label for="logo-size-slider">Quick Size: <span id="size-percentage">100</span>%</label>
                                    <input type="range" id="logo-size-slider" class="ws-size-slider" value="100" min="30" max="250" step="1">
                                </div>
                            </div>
                            
                            <div class="ws-logo-upload-status" id="logo-upload-status" style="display: none;">
                                <span class="ws-logo-upload-spinner"></span>
                                <span>Uploading...</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="WorkspaceSettings[workspace_logo_image]" id="workspace-logo-image-input" value="<?= Html::encode($model->workspace_logo_image ?? '') ?>">
                        <input type="hidden" name="WorkspaceSettings[workspace_logo_width]" id="workspace-logo-width-input" value="<?= Html::encode($model->workspace_logo_width ?? 120) ?>">
                        <input type="hidden" name="WorkspaceSettings[workspace_logo_height]" id="workspace-logo-height-input" value="<?= Html::encode($model->workspace_logo_height ?? 120) ?>">
                    </div>
                    
                    <div class="ws-card" id="section-workspace">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">dashboard</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Workspace Identity</h3>
                                <p class="ws-card-subtitle">Nama, subtitle, dan label utama workspace</p>
                            </div>
                        </div>
                        
                        <div class="ws-form-row ws-form-row-2">
                            <div class="ws-form-group">
                                <label for="workspacesettings-workspace_title">Workspace Title</label>
                                <input type="text" id="workspacesettings-workspace_title" class="ws-form-control preview-input" 
                                       name="WorkspaceSettings[workspace_title]" 
                                       value="<?= Html::encode($model->workspace_title) ?>" 
                                       data-preview="#preview-title"
                                       placeholder="Enter workspace title">
                            </div>
                            <div class="ws-form-group">
                                <label for="workspacesettings-workspace_subtitle">Subtitle</label>
                                <input type="text" id="workspacesettings-workspace_subtitle" class="ws-form-control preview-input" 
                                       name="WorkspaceSettings[workspace_subtitle]" 
                                       value="<?= Html::encode($model->workspace_subtitle) ?>"
                                       data-preview="#preview-subtitle"
                                       placeholder="Enter subtitle">
                            </div>
                            <div class="ws-form-group">
                                <label for="workspacesettings-workspace_badge">Badge Text</label>
                                <input type="text" id="workspacesettings-workspace_badge" class="ws-form-control preview-input" 
                                       name="WorkspaceSettings[workspace_badge]" 
                                       value="<?= Html::encode($model->workspace_badge) ?>"
                                       data-preview="#preview-badge"
                                       placeholder="e.g. Workspace">
                            </div>
                        </div>
                    </div>

                    <div class="ws-card" id="section-login">
                        <div class="ws-card-header">
                            <div class="ws-card-icon ws-card-icon-login">
                                <span class="material-symbols-outlined">lock</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Login Appearance</h3>
                                <p class="ws-card-subtitle">Branding dan warna khusus halaman login aplikasi</p>
                            </div>
                        </div>

                        <div class="ws-form-row ws-form-row-2">
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_title">Login Title</label>
                                <input type="text" id="workspacesettings-login_title" class="ws-form-control" 
                                       name="WorkspaceSettings[login_title]" 
                                       value="<?= Html::encode($model->login_title) ?>" 
                                       placeholder="Login Aplikasi">
                            </div>
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_subtitle">Login Subtitle</label>
                                <input type="text" id="workspacesettings-login_subtitle" class="ws-form-control" 
                                       name="WorkspaceSettings[login_subtitle]" 
                                       value="<?= Html::encode($model->login_subtitle) ?>" 
                                       placeholder="Masuk ke aplikasi Anda">
                            </div>
                        </div>

                        <div class="ws-section-title">Background</div>
                        <div class="ws-color-row">
                            <div class="ws-color-group">
                                <label>Background Start</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[login_background_start]" value="<?= Html::encode($model->login_background_start) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[login_background_start]" value="<?= Html::encode($model->login_background_start) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Background End</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[login_background_end]" value="<?= Html::encode($model->login_background_end) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[login_background_end]" value="<?= Html::encode($model->login_background_end) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="ws-media-picker" style="margin-top: 14px;">
                            <div class="ws-media-preview" id="login-background-preview">
                                <?php if (($loginBackgroundAsset['type'] ?? 'none') === 'video'): ?>
                                    <video autoplay muted loop playsinline>
                                        <source src="<?= Html::encode($loginBackgroundAsset['url']) ?>">
                                    </video>
                                <?php elseif (($loginBackgroundAsset['type'] ?? 'none') === 'image'): ?>
                                    <img src="<?= Html::encode($loginBackgroundAsset['url']) ?>" alt="Login Background Preview">
                                <?php else: ?>
                                    <div class="ws-media-placeholder">
                                        <span class="material-symbols-outlined">wallpaper</span>
                                        <div>Preview background login</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ws-media-actions">
                                <div class="ws-form-group" style="margin: 0;">
                                    <label for="workspacesettings-login_background_image">Background Link</label>
                                    <input type="text" id="workspacesettings-login_background_image" class="ws-form-control" 
                                           name="WorkspaceSettings[login_background_image]" 
                                           value="<?= Html::encode($model->login_background_image) ?>" 
                                           placeholder="Direct URL image/video atau nama file hasil upload">
                                </div>
                                <div class="ws-form-group" style="margin: 0;">
                                    <label for="workspacesettings-login_background_upload">Upload File</label>
                                    <input type="file" id="workspacesettings-login_background_upload" class="ws-form-control" 
                                           name="WorkspaceSettings[login_background_upload]" 
                                           accept="image/*,video/*">
                                </div>
                                <div class="ws-media-toolbar">
                                    <span class="ws-media-chip" id="login-background-file-chip"><?= !empty($model->login_background_image) ? Html::encode($model->login_background_image) : 'No media selected' ?></span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="login-background-clear-btn" style="border-radius: 12px;">Clear</button>
                                </div>
                                <p class="ws-media-help">Pilih file dari perangkat atau masukkan direct link media. Preview di kiri akan langsung mengikuti media yang dipilih.</p>
                            </div>
                        </div>

                        <div class="ws-divider"></div>

                        <div class="ws-form-row ws-form-row-2">
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_button_color">Button Color</label>
                                <input type="text" id="workspacesettings-login_button_color" class="ws-form-control" 
                                       name="WorkspaceSettings[login_button_color]" 
                                       value="<?= Html::encode($model->login_button_color) ?>" 
                                       placeholder="#2563eb">
                            </div>
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_accent_color">Accent Color</label>
                                <input type="text" id="workspacesettings-login_accent_color" class="ws-form-control" 
                                       name="WorkspaceSettings[login_accent_color]" 
                                       value="<?= Html::encode($model->login_accent_color) ?>" 
                                       placeholder="#4f46e5">
                            </div>
                        </div>

                        <div class="ws-form-row ws-form-row-2">
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_card_color">Card Color</label>
                                <input type="text" id="workspacesettings-login_card_color" class="ws-form-control" 
                                       name="WorkspaceSettings[login_card_color]" 
                                       value="<?= Html::encode($model->login_card_color) ?>" 
                                       placeholder="rgba(255,255,255,0.96)">
                            </div>
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_text_color">Text Color</label>
                                <input type="text" id="workspacesettings-login_text_color" class="ws-form-control" 
                                       name="WorkspaceSettings[login_text_color]" 
                                       value="<?= Html::encode($model->login_text_color) ?>" 
                                       placeholder="#0f172a">
                            </div>
                        </div>

                        <div class="ws-form-row ws-form-row-2">
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_border_radius">Border Radius</label>
                                <input type="number" id="workspacesettings-login_border_radius" class="ws-form-control" 
                                       name="WorkspaceSettings[login_border_radius]" 
                                       min="0" max="64" step="1" 
                                       value="<?= Html::encode($model->login_border_radius) ?>">
                            </div>
                            <div class="ws-form-group">
                                <label for="workspacesettings-login_theme">Theme</label>
                                <select id="workspacesettings-login_theme" class="ws-form-control" name="WorkspaceSettings[login_theme]">
                                    <option value="dark" <?= $model->login_theme === 'dark' ? 'selected' : '' ?>>Dark</option>
                                    <option value="light" <?= $model->login_theme === 'light' ? 'selected' : '' ?>>Light</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ws-card" id="section-sidebar">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">view_sidebar</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Sidebar Background</h3>
                                <p class="ws-card-subtitle">Warna dasar sidebar dan teks pendukung</p>
                            </div>
                        </div>
                        
                        <div class="ws-section-title">Background Colors</div>
                        <div class="ws-color-row">
                            <div class="ws-color-group">
                                <label>Background Start</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_bg_start]" value="<?= Html::encode($model->sidebar_bg_start) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_bg_start]" value="<?= Html::encode($model->sidebar_bg_start) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Background End</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_bg_end]" value="<?= Html::encode($model->sidebar_bg_end) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_bg_end]" value="<?= Html::encode($model->sidebar_bg_end) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Border Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_border_color]" value="#cbd5e1">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_border_color]" value="<?= Html::encode($model->sidebar_border_color) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="ws-divider"></div>
                        
                        <div class="ws-section-title">Text Colors</div>
                        <div class="ws-color-row" style="grid-template-columns: repeat(2, 1fr);">
                            <div class="ws-color-group">
                                <label>Text Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_text_color]" value="<?= Html::encode($model->sidebar_text_color) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_text_color]" value="<?= Html::encode($model->sidebar_text_color) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Muted Text</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_text_muted]" value="<?= Html::encode($model->sidebar_text_muted) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_text_muted]" value="<?= Html::encode($model->sidebar_text_muted) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ws-card" id="section-active">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">check_circle</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Active State</h3>
                                <p class="ws-card-subtitle">Tampilan menu yang sedang aktif</p>
                            </div>
                        </div>
                        
                        <div class="ws-section-title">Active Background</div>
                        <div class="ws-color-row">
                            <div class="ws-color-group">
                                <label>Background Start</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_active_bg_start]" value="<?= Html::encode($model->sidebar_active_bg_start) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_active_bg_start]" value="<?= Html::encode($model->sidebar_active_bg_start) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Background End</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_active_bg_end]" value="<?= Html::encode($model->sidebar_active_bg_end) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_active_bg_end]" value="<?= Html::encode($model->sidebar_active_bg_end) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Active Text Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_active_text]" value="<?= Html::encode($model->sidebar_active_text) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_active_text]" value="<?= Html::encode($model->sidebar_active_text) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="ws-divider"></div>
                        
                        <div class="ws-form-group">
                            <label>Active Shadow</label>
                            <input type="text" class="ws-form-control" name="WorkspaceSettings[sidebar_active_shadow]" 
                                   value="<?= Html::encode($model->sidebar_active_shadow) ?>" 
                                   placeholder="e.g. 0 8px 24px rgba(37, 99, 235, 0.28)">
                        </div>
                    </div>
                    
                    <div class="ws-card" id="section-hover">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">touch_app</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Hover State</h3>
                                <p class="ws-card-subtitle">Efek saat pointer berada di menu</p>
                            </div>
                        </div>
                        
                        <div class="ws-color-row" style="grid-template-columns: repeat(2, 1fr);">
                            <div class="ws-color-group">
                                <label>Hover Background</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_hover_bg]" value="#e2e8f0">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_hover_bg]" value="<?= Html::encode($model->sidebar_hover_bg) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Hover Text Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_hover_text]" value="<?= Html::encode($model->sidebar_hover_text) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[sidebar_hover_text]" value="<?= Html::encode($model->sidebar_hover_text) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ws-card" id="section-topnav">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">menu</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Top Navigation</h3>
                                <p class="ws-card-subtitle">Warna dan batas top navigation</p>
                            </div>
                        </div>
                        
                        <div class="ws-color-row">
                            <div class="ws-color-group">
                                <label>Background</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[topnav_bg]" value="<?= Html::encode($model->topnav_bg) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[topnav_bg]" value="<?= Html::encode($model->topnav_bg) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Border Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[topnav_border_color]" value="#e2e8f0">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[topnav_border_color]" value="<?= Html::encode($model->topnav_border_color) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Text Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[topnav_text_color]" value="<?= Html::encode($model->topnav_text_color) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[topnav_text_color]" value="<?= Html::encode($model->topnav_text_color) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ws-card" id="section-light">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">light_mode</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Light Theme</h3>
                                <p class="ws-card-subtitle">Colors used in light theme mode</p>
                            </div>
                        </div>
                        
                        <div class="ws-color-row" style="grid-template-columns: repeat(2, 1fr);">
                            <div class="ws-color-group">
                                <label>Sidebar Background</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[light_sidebar_bg]" value="<?= Html::encode($model->light_sidebar_bg) ?>">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[light_sidebar_bg]" value="<?= Html::encode($model->light_sidebar_bg) ?>">
                                </div>
                            </div>
                            <div class="ws-color-group">
                                <label>Border Color</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[light_sidebar_border]" value="#e2e8f0">
                                    <input type="text" class="ws-form-control color-text" name="WorkspaceSettings[light_sidebar_border]" value="<?= Html::encode($model->light_sidebar_border) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ws-actions">
                        <button type="button" class="ws-btn ws-btn-secondary" id="ws-reset">
                            <span class="material-symbols-outlined">restart_alt</span>
                            Reset
                        </button>
                        <button type="submit" class="ws-btn ws-btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.ws-nav-item');
    const sections = document.querySelectorAll('.ws-card[id]');
    const SCROLL_OFFSET = 100;
    
    function scrollToSection(targetId) {
        const target = document.getElementById(targetId);
        if (!target) return false;
        
        const rect = target.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const targetPosition = rect.top + scrollTop - SCROLL_OFFSET;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
        
        return true;
    }
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = 'section-' + this.dataset.section;
            
            navItems.forEach(n => n.classList.remove('active'));
            this.classList.add('active');
            
            scrollToSection(targetId);
        });
    });
    
    document.querySelectorAll('.preview-input').forEach(input => {
        input.addEventListener('input', function() {
            const target = document.querySelector(this.dataset.preview);
            if (target) target.textContent = this.value || this.placeholder;
        });
    });

    const logoTabs = document.querySelectorAll('.ws-logo-tab');
    const logoModeIcon = document.getElementById('logo-mode-icon');
    const logoModeImage = document.getElementById('logo-mode-image');
    const logoImageInput = document.getElementById('logo-image-input');
    const logoUploadArea = document.getElementById('logo-upload-area');
    const logoUploadStatus = document.getElementById('logo-upload-status');
    const logoImageHiddenInput = document.getElementById('workspace-logo-image-input');
    const previewLogoBox = document.getElementById('preview-logo-box');
    const previewLogoImg = document.getElementById('preview-logo-img');
    const previewLogoIcon = document.getElementById('preview-logo-icon');
    const logoPreviewBox = document.getElementById('logo-preview-box');
    const logoPreviewIcon = document.getElementById('logo-preview-icon');
    const sidebarLogoBox = document.getElementById('sidebar-logo-box');

    function getCurrentLogoImageUrl() {
        const logoFile = logoImageHiddenInput ? logoImageHiddenInput.value : '';
        return logoFile ? '<?= Yii::getAlias('@web/uploads/workspace/') ?>' + logoFile : '';
    }

    function clampLogoSize(value) {
        const parsed = parseInt(value, 10);
        if (isNaN(parsed)) return 120;
        return Math.max(40, Math.min(300, parsed));
    }

    function updateSidebarLogoBox(width, height, imageUrl, icon, bgColor) {
        if (!sidebarLogoBox) return;

        sidebarLogoBox.style.width = width + 'px';
        sidebarLogoBox.style.height = height + 'px';
        sidebarLogoBox.style.fontSize = Math.round((width / 44) * 22) + 'px';

        if (imageUrl) {
            sidebarLogoBox.style.background = 'transparent';
            sidebarLogoBox.style.boxShadow = 'none';
            sidebarLogoBox.innerHTML = '<img id="sidebar-logo-image" src="' + imageUrl + '" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 14px;">';
            return;
        }

        sidebarLogoBox.style.background = 'linear-gradient(135deg, ' + bgColor + ' 0%, ' + bgColor + ' 100%)';
        sidebarLogoBox.style.boxShadow = '0 12px 24px rgba(79, 70, 229, 0.28)';
        sidebarLogoBox.innerHTML = '<span id="sidebar-logo-icon" class="material-symbols-outlined">' + icon + '</span>';
    }

    function updateLogoPreviewBox(box, iconNode, imgNode, width, height, imageUrl, icon, bgColor) {
        if (!box) return;

        box.style.width = width + 'px';
        box.style.height = height + 'px';

        if (imageUrl) {
            box.style.background = 'none';
            box.innerHTML = '<img src="' + imageUrl + '" alt="Workspace Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 0;">';
            return;
        }

        box.style.background = 'linear-gradient(135deg, ' + bgColor + ' 0%, ' + bgColor + ' 100%)';
        box.innerHTML = '<span class="material-symbols-outlined">' + icon + '</span>';
    }

    function updateAllLogoViews(options) {
        const width = clampLogoSize(options.width);
        const height = clampLogoSize(options.height);
        const imageUrl = typeof options.imageUrl === 'string' ? options.imageUrl : getCurrentLogoImageUrl();
        const bgColor = options.bgColor || document.querySelector('input[name="WorkspaceSettings[workspace_logo_bg]"]')?.value || '#4f46e5';
        const icon = options.icon || document.getElementById('logo-icon-select')?.value || 'folder_open';

        if (previewLogoBox) {
            updateLogoPreviewBox(previewLogoBox, previewLogoIcon, previewLogoImg, width, height, imageUrl, icon, bgColor);
        }

        if (logoPreviewBox) {
            updateLogoPreviewBox(logoPreviewBox, logoPreviewIcon, null, width, height, imageUrl, icon, bgColor);
        }

        updateSidebarLogoBox(width, height, imageUrl, icon, bgColor);
    }

    function updateSizingInputs(width, height) {
        width = clampLogoSize(width);
        height = clampLogoSize(height);

        const widthInput = document.getElementById('logo-width');
        const heightInput = document.getElementById('logo-height');

        if (widthInput) widthInput.value = width;
        if (heightInput) heightInput.value = height;

        const baseSize = 120;
        const percentage = Math.round((width / baseSize) * 100);
        const sizePercentage = document.getElementById('size-percentage');
        if (sizePercentage) sizePercentage.textContent = percentage;

        const slider = document.getElementById('logo-size-slider');
        if (slider) slider.value = Math.max(30, Math.min(250, percentage));

        const widthHiddenInput = document.getElementById('workspace-logo-width-input');
        const heightHiddenInput = document.getElementById('workspace-logo-height-input');
        if (widthHiddenInput) widthHiddenInput.value = width;
        if (heightHiddenInput) heightHiddenInput.value = height;

        updateAllLogoViews({
            width: width,
            height: height
        });
    }

    document.querySelectorAll('input[type="color"]').forEach(colorInput => {
        colorInput.addEventListener('input', function() {
            const textInput = this.closest('.ws-color-picker')?.querySelector('.color-text');
            if (textInput) textInput.value = this.value;

            if (this.id === 'logo-bg-color' || this.name === 'WorkspaceSettings[workspace_logo_bg]') {
                updateAllLogoViews({
                    width: document.getElementById('workspace-logo-width-input')?.value || document.getElementById('logo-width')?.value || 120,
                    height: document.getElementById('workspace-logo-height-input')?.value || document.getElementById('logo-height')?.value || 120,
                    bgColor: this.value
                });
            }
        });
    });

    const iconSelect = document.getElementById('logo-icon-select');
    if (iconSelect) {
        iconSelect.addEventListener('change', function() {
            updateAllLogoViews({
                width: document.getElementById('workspace-logo-width-input')?.value || document.getElementById('logo-width')?.value || 120,
                height: document.getElementById('workspace-logo-height-input')?.value || document.getElementById('logo-height')?.value || 120,
                icon: this.value
            });
        });
    }

    function updateLogoTabState(mode) {
        logoTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.mode === mode);
        });
        if (logoModeIcon && logoModeImage) {
            logoModeIcon.style.display = mode === 'icon' ? 'block' : 'none';
            logoModeImage.style.display = mode === 'image' ? 'block' : 'none';
        }
    }
    
    logoTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            updateLogoTabState(this.dataset.mode);
        });
    });
    
    const currentLogoImage = logoImageHiddenInput?.value;
    if (currentLogoImage) {
        updateLogoTabState('image');
    }
    
    if (logoUploadArea && logoImageInput) {
        logoUploadArea.addEventListener('click', function() {
            logoImageInput.click();
        });
        
        logoUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#4f46e5';
            this.style.background = 'rgba(79, 70, 229, 0.08)';
        });
        
        logoUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
        });
        
        logoUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleLogoUpload(files[0]);
            }
        });
        
        logoImageInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleLogoUpload(this.files[0]);
            }
        });
    }
    
    function handleLogoUpload(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Please upload JPG, PNG, or WEBP.');
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            alert('File too large. Maximum size is 2MB.');
            return;
        }
        
        if (logoUploadStatus) {
            logoUploadStatus.style.display = 'flex';
        }
        
        const formData = new FormData();
        formData.append('workspace_logo_image', file);
        
        fetch('<?= \yii\helpers\Url::to(['workspace-settings/upload-logo']) ?>', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (logoUploadStatus) {
                logoUploadStatus.style.display = 'none';
            }
            
            if (data.success) {
                logoImageHiddenInput.value = data.logoFile;
                
                const uploadArea = document.getElementById('logo-upload-area');
                if (uploadArea) {
                    uploadArea.innerHTML = `
                        <div class="ws-logo-image-preview" id="logo-image-preview-container">
                            <img src="${data.logoUrl}" alt="Workspace Logo" id="uploaded-logo-preview">
                            <div class="ws-logo-resize-handle" id="logo-resize-handle"></div>
                            <button type="button" class="ws-logo-remove-btn" id="remove-logo-btn">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('remove-logo-btn').addEventListener('click', handleLogoRemove);
                    initializeLogoResize();
                    showSizingControls();
                }
                
                updateAllLogoViews({
                    width: document.getElementById('workspace-logo-width-input')?.value || document.getElementById('logo-width')?.value || 120,
                    height: document.getElementById('workspace-logo-height-input')?.value || document.getElementById('logo-height')?.value || 120,
                    imageUrl: data.logoUrl
                });
            } else {
                alert(data.message || 'Failed to upload logo');
            }
        })
        .catch(error => {
            if (logoUploadStatus) {
                logoUploadStatus.style.display = 'none';
            }
            alert('Upload failed: ' + error.message);
        });
    }
    
    function handleLogoRemove(e) {
        e.stopPropagation();
        
        if (!confirm('Remove the uploaded logo?')) return;
        
        fetch('<?= \yii\helpers\Url::to(['workspace-settings/remove-logo']) ?>', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                logoImageHiddenInput.value = '';
                
                const uploadArea = document.getElementById('logo-upload-area');
                if (uploadArea) {
                    uploadArea.innerHTML = `
                        <div class="ws-logo-upload-placeholder">
                            <span class="material-symbols-outlined">cloud_upload</span>
                            <p>Drop your logo here or click to upload</p>
                            <span class="ws-logo-upload-hint">JPG, PNG, WEBP (max 2MB)</span>
                        </div>
                    `;
                }
                
                updateAllLogoViews({
                    width: document.getElementById('workspace-logo-width-input')?.value || document.getElementById('logo-width')?.value || 120,
                    height: document.getElementById('workspace-logo-height-input')?.value || document.getElementById('logo-height')?.value || 120,
                    imageUrl: null
                });
                hideSizingControls();
                updateLogoTabState('icon');
            }
        })
        .catch(error => {
            alert('Failed to remove logo: ' + error.message);
        });
    }
    
    const removeLogoBtn = document.getElementById('remove-logo-btn');
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', handleLogoRemove);
    }
    
    const storedLogoImage = logoImageHiddenInput?.value;
    if (storedLogoImage) {
        updateAllLogoViews({
            width: document.getElementById('workspace-logo-width-input')?.value || document.getElementById('logo-width')?.value || 120,
            height: document.getElementById('workspace-logo-height-input')?.value || document.getElementById('logo-height')?.value || 120,
            imageUrl: '<?= Yii::getAlias('@web/uploads/workspace/') ?>' + storedLogoImage
        });
    }
    
    document.querySelectorAll('.color-text').forEach(textInput => {
        textInput.addEventListener('input', function() {
            const colorInput = this.closest('.ws-color-picker').querySelector('input[type="color"]');
            if (colorInput && /^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                colorInput.value = this.value;

                if (this.name === 'WorkspaceSettings[workspace_logo_bg]') {
                    updateAllLogoViews({
                        width: document.getElementById('workspace-logo-width-input')?.value || document.getElementById('logo-width')?.value || 120,
                        height: document.getElementById('workspace-logo-height-input')?.value || document.getElementById('logo-height')?.value || 120,
                        bgColor: this.value
                    });
                }
            }
        });
    });
    
    const resetBtn = document.getElementById('ws-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (confirm('Reset all settings to default?')) {
                fetch('<?= \yii\helpers\Url::to(['workspace-settings/reset']) ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                }).then(() => location.reload());
            }
        });
    }
    
    let isScrolling = false;
    const observer = new IntersectionObserver((entries) => {
        if (!isScrolling) {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio > 0.3) {
                    const id = entry.target.id.replace('section-', '');
                    navItems.forEach(n => {
                        n.classList.toggle('active', n.dataset.section === id);
                    });
                }
            });
        }
    }, { threshold: 0.3 });
    
    sections.forEach(section => observer.observe(section));
    
    window.addEventListener('scroll', function() {
        isScrolling = true;
        clearTimeout(window.scrollTimeout);
        window.scrollTimeout = setTimeout(() => {
            isScrolling = false;
        }, 300);
    }, { passive: true });
    
    // Logo Resizing and Sizing Controls
    let isResizing = false;
    let startX, startY, startWidth, startHeight;

    function initializeLogoResize() {
        const resizeHandle = document.getElementById('logo-resize-handle');
        const logoImg = document.getElementById('uploaded-logo-preview');
        const previewContainer = document.getElementById('logo-image-preview-container');

        if (!resizeHandle || !logoImg) return;

        resizeHandle.addEventListener('mousedown', startResize);
        resizeHandle.addEventListener('touchstart', startResize);

        function startResize(e) {
            e.preventDefault();
            isResizing = true;
            startX = e.clientX || e.touches?.[0]?.clientX || 0;
            startY = e.clientY || e.touches?.[0]?.clientY || 0;
            startWidth = logoImg.offsetWidth;
            startHeight = logoImg.offsetHeight;

            if (previewContainer) {
                previewContainer.classList.add('resizing');
            }

            document.addEventListener('mousemove', doResize);
            document.addEventListener('touchmove', doResize, { passive: false });
            document.addEventListener('mouseup', stopResize);
            document.addEventListener('touchend', stopResize);
        }

        function doResize(e) {
            if (!isResizing) return;

            e.preventDefault();
            const currentX = e.clientX || e.touches?.[0]?.clientX || 0;
            const currentY = e.clientY || e.touches?.[0]?.clientY || 0;

            const diffX = currentX - startX;
            const diffY = currentY - startY;
            const diff = Math.max(diffX, diffY);
            const newWidth = Math.max(40, Math.min(300, startWidth + diff));
            const newHeight = Math.max(40, Math.min(300, startHeight + diff));

            logoImg.style.maxWidth = newWidth + 'px';
            logoImg.style.maxHeight = newHeight + 'px';
            updateSizingInputs(newWidth, newHeight);
        }

        function stopResize() {
            isResizing = false;
            if (previewContainer) {
                previewContainer.classList.remove('resizing');
            }

            document.removeEventListener('mousemove', doResize);
            document.removeEventListener('touchmove', doResize);
            document.removeEventListener('mouseup', stopResize);
            document.removeEventListener('touchend', stopResize);
        }
    }

    function showSizingControls() {
        const controls = document.getElementById('logo-sizing-controls');
        if (controls) {
            controls.style.display = 'block';
        }
    }

    function hideSizingControls() {
        const controls = document.getElementById('logo-sizing-controls');
        if (controls) {
            controls.style.display = 'none';
        }
    }

    const widthInput = document.getElementById('logo-width');
    const heightInput = document.getElementById('logo-height');
    const sizeSlider = document.getElementById('logo-size-slider');
    const widthHiddenInput = document.getElementById('workspace-logo-width-input');
    const heightHiddenInput = document.getElementById('workspace-logo-height-input');

    if (widthInput) {
        widthInput.addEventListener('input', function() {
            updateSizingInputs(this.value, heightInput ? heightInput.value : 120);
        });
    }

    if (heightInput) {
        heightInput.addEventListener('input', function() {
            updateSizingInputs(widthInput ? widthInput.value : 120, this.value);
        });
    }

    if (sizeSlider) {
        sizeSlider.addEventListener('input', function() {
            const baseSize = 120;
            const percentage = parseInt(this.value, 10) || 100;
            const newSize = Math.round((percentage / 100) * baseSize);
            updateSizingInputs(newSize, newSize);
        });
    }

    const existingLogo = document.getElementById('uploaded-logo-preview');
    if (existingLogo) {
        initializeLogoResize();
        showSizingControls();
    }

    updateSizingInputs(widthHiddenInput ? widthHiddenInput.value : (widthInput ? widthInput.value : 120), heightHiddenInput ? heightHiddenInput.value : (heightInput ? heightInput.value : 120));

    const loginBackgroundPreview = document.getElementById('login-background-preview');
    const loginBackgroundUploadInput = document.getElementById('workspacesettings-login_background_upload');
    const loginBackgroundLinkInput = document.getElementById('workspacesettings-login_background_image');
    const loginBackgroundChip = document.getElementById('login-background-file-chip');
    const loginBackgroundClearBtn = document.getElementById('login-background-clear-btn');
    let loginBackgroundObjectUrl = null;

    function detectMediaTypeFromUrl(url) {
        const cleanUrl = String(url || '').split('?')[0].split('#')[0];
        const match = cleanUrl.match(/\.([a-z0-9]+)$/i);
        const ext = match ? match[1].toLowerCase() : '';
        if (['mp4', 'webm', 'ogg'].includes(ext)) {
            return 'video';
        }
        return 'image';
    }

    function renderLoginBackgroundPlaceholder() {
        if (!loginBackgroundPreview) {
            return;
        }
        loginBackgroundPreview.innerHTML = `
            <div class="ws-media-placeholder">
                <span class="material-symbols-outlined">wallpaper</span>
                <div>Preview background login</div>
            </div>
        `;
    }

    function revokeLoginBackgroundObjectUrl() {
        if (loginBackgroundObjectUrl && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
            URL.revokeObjectURL(loginBackgroundObjectUrl);
        }
        loginBackgroundObjectUrl = null;
    }

    function renderLoginBackgroundPreview(src, type, label) {
        if (!loginBackgroundPreview) {
            return;
        }

        if (!src) {
            renderLoginBackgroundPlaceholder();
            if (loginBackgroundChip) {
                loginBackgroundChip.textContent = 'No media selected';
            }
            return;
        }

        if (type === 'video') {
            loginBackgroundPreview.innerHTML = `
                <video autoplay muted loop playsinline>
                    <source src="${src}">
                </video>
            `;
        } else {
            loginBackgroundPreview.innerHTML = `<img src="${src}" alt="Login Background Preview">`;
        }

        if (loginBackgroundChip) {
            const chipLabel = type === 'video' ? 'Video selected' : 'Image selected';
            loginBackgroundChip.textContent = label ? chipLabel + ': ' + label : chipLabel;
        }
    }

    if (loginBackgroundUploadInput) {
        loginBackgroundUploadInput.addEventListener('change', function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                return;
            }

            revokeLoginBackgroundObjectUrl();
            if (typeof URL === 'undefined' || typeof URL.createObjectURL !== 'function') {
                renderLoginBackgroundPlaceholder();
                if (loginBackgroundChip) {
                    loginBackgroundChip.textContent = file.name;
                }
                return;
            }
            loginBackgroundObjectUrl = URL.createObjectURL(file);
            const mediaType = file.type && file.type.startsWith('video/') ? 'video' : 'image';
            renderLoginBackgroundPreview(loginBackgroundObjectUrl, mediaType, file.name);
        });
    }

    if (loginBackgroundLinkInput) {
        loginBackgroundLinkInput.addEventListener('input', function () {
            const value = this.value.trim();
            if (!value) {
                if (!loginBackgroundUploadInput || !loginBackgroundUploadInput.files || !loginBackgroundUploadInput.files[0]) {
                    renderLoginBackgroundPlaceholder();
                    if (loginBackgroundChip) {
                        loginBackgroundChip.textContent = 'No media selected';
                    }
                }
                return;
            }

            renderLoginBackgroundPreview(value, detectMediaTypeFromUrl(value), value.split('/').pop());
        });
    }

    if (loginBackgroundClearBtn) {
        loginBackgroundClearBtn.addEventListener('click', function () {
            if (loginBackgroundUploadInput) {
                loginBackgroundUploadInput.value = '';
            }
            if (loginBackgroundLinkInput) {
                loginBackgroundLinkInput.value = '';
            }
            revokeLoginBackgroundObjectUrl();
            renderLoginBackgroundPlaceholder();
        });
    }

    const existingLoginBackground = loginBackgroundLinkInput ? loginBackgroundLinkInput.value.trim() : '';
    if (existingLoginBackground) {
        renderLoginBackgroundPreview(existingLoginBackground, detectMediaTypeFromUrl(existingLoginBackground), existingLoginBackground.split('/').pop());
    } else {
        renderLoginBackgroundPlaceholder();
    }

    // Ensure hidden inputs are updated before form submit
    const form = document.getElementById('workspace-settings-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const widthInput = document.getElementById('logo-width');
            const heightInput = document.getElementById('logo-height');
            const widthHiddenInput = document.getElementById('workspace-logo-width-input');
            const heightHiddenInput = document.getElementById('workspace-logo-height-input');
             
            if (widthInput && widthHiddenInput) {
                let val = parseInt(widthInput.value) || 120;
                val = Math.max(40, Math.min(300, val));
                widthHiddenInput.value = val;
            }
             
            if (heightInput && heightHiddenInput) {
                let val = parseInt(heightInput.value) || 120;
                val = Math.max(40, Math.min(300, val));
                heightHiddenInput.value = val;
            }
        });
    }
});
</script>
