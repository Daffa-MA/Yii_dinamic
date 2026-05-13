<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Workspace Settings';
$this->params['breadcrumbs'][] = ['label' => 'System Builder', 'url' => ['master-menu/index']];
$this->params['breadcrumbs'][] = $this->title;

$cssVars = $model->getCssVars();
?>

<style>
    .ws-page {
        background: #f1f5f9;
        min-height: 100vh;
        padding: 32px 24px;
    }
    
    .ws-container {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }
    
    .ws-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        margin-bottom: 20px;
    }
    
    .ws-breadcrumb a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .ws-breadcrumb a:hover { color: #4f46e5; }
    .ws-breadcrumb .separator { color: #cbd5e1; }
    .ws-breadcrumb .current { color: #0f172a; font-weight: 500; }
    
    .ws-header {
        margin-bottom: 32px;
    }
    
    .ws-header-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(99, 102, 241, 0.1);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 8px;
        color: #4f46e5;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .ws-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px;
    }
    
    .ws-header p {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }
    
    .ws-layout {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    
    .ws-top-row {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 24px;
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
        background: linear-gradient(135deg, #07111f 0%, #1e1b4b 50%, #111827 100%);
        border-radius: 24px;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 32px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }
    
    .ws-preview-card::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60%;
        background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.5) 100%);
        pointer-events: none;
    }
    
    .ws-preview-content {
        position: relative;
        z-index: 1;
    }
    
    .ws-preview-logo {
        width: 56px;
        height: 56px;
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
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.9);
        margin-bottom: 12px;
    }
    
    .ws-preview-title {
        font-size: 28px;
        font-weight: 800;
        color: white;
        margin: 0 0 8px;
        line-height: 1.2;
    }
    
    .ws-preview-subtitle {
        font-size: 14px;
        color: rgba(255,255,255,0.7);
        margin: 0;
    }
    
    .ws-preview-stats {
        display: flex;
        gap: 24px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .ws-preview-stat-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255,255,255,0.5);
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .ws-preview-stat-value {
        font-size: 18px;
        font-weight: 700;
        color: white;
    }
    
    .ws-nav-card {
        background: white;
        border-radius: 20px;
        padding: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
    }
    
    .ws-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 14px;
        color: #64748b;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }
    
    .ws-nav-item:hover {
        background: #f8fafc;
        color: #0f172a;
    }
    
    .ws-nav-item.active {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
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
        background: white;
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
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
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .ws-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(79, 102, 241, 0.12) 0%, rgba(99, 102, 241, 0.18) 100%);
        color: #4f46e5;
        flex-shrink: 0;
    }
    
    .ws-card-icon .material-symbols-outlined {
        font-size: 22px;
    }
    
    .ws-card-title-group { flex: 1; }
    
    .ws-card-title {
        font-size: 16px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
    }
    
    .ws-card-subtitle {
        font-size: 13px;
        color: #94a3b8;
        margin: 4px 0 0;
    }
    
    .ws-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94a3b8;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .ws-section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #f1f5f9;
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
        font-weight: 500;
        color: #374151;
    }
    
    .ws-form-control {
        height: 46px;
        padding: 0 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 14px;
        color: #0f172a;
        background: white;
        transition: all 0.2s;
    }
    
    .ws-form-control:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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
        border: 1px solid #e5e7eb;
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
        margin-top: 24px;
        padding: 24px;
        background: #f8fafc;
        border-radius: 16px;
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
    
    .ws-divider {
        height: 1px;
        background: #f1f5f9;
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
        transition: all 0.2s;
        border: none;
    }
    
    .ws-btn .material-symbols-outlined { font-size: 18px; }
    
    .ws-btn-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: white;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    }
    
    .ws-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.45);
    }
    
    .ws-btn-secondary {
        background: white;
        color: #64748b;
        border: 1px solid #e5e7eb;
    }
    
    .ws-btn-secondary:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #d1d5db;
    }
</style>

<div class="ws-page">
    <div class="ws-container">
        <div class="ws-breadcrumb">
            <a href="<?= \yii\helpers\Url::to(['site/dashboard']) ?>">
                <span class="material-symbols-outlined" style="font-size: 16px;">home</span>
            </a>
            <span class="separator">/</span>
            <a href="<?= \yii\helpers\Url::to(['master-menu/index']) ?>">System Builder</a>
            <span class="separator">/</span>
            <span class="current">Workspace Settings</span>
        </div>
        
        <div class="ws-header">
            <div class="ws-header-badge">
                <span class="material-symbols-outlined">settings</span>
                Configuration
            </div>
            <h1>Workspace Settings</h1>
            <p>Customize the appearance and behavior of your workspace interface.</p>
        </div>
        
        <?php $form = ActiveForm::begin(['id' => 'workspace-settings-form', 'action' => ['save']]); ?>
        
        <div class="ws-layout">
            <div class="ws-top-row">
                <div class="ws-preview-wrapper">
                    <div class="ws-preview-card">
                        <div class="ws-preview-content">
                            <div class="ws-preview-logo" id="preview-logo-box" style="background: linear-gradient(135deg, <?= Html::encode($model->workspace_logo_bg) ?> 0%, <?= Html::encode($model->workspace_logo_bg) ?> 100%);">
                                <span class="material-symbols-outlined" id="preview-logo-icon"><?= Html::encode($model->workspace_logo_icon) ?></span>
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
                                <p class="ws-card-subtitle">Customize the workspace icon appearance</p>
                            </div>
                        </div>
                        
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
                    
                    <div class="ws-card" id="section-workspace">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">dashboard</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Workspace Identity</h3>
                                <p class="ws-card-subtitle">Basic information about your workspace</p>
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
                    
                    <div class="ws-card" id="section-sidebar">
                        <div class="ws-card-header">
                            <div class="ws-card-icon">
                                <span class="material-symbols-outlined">view_sidebar</span>
                            </div>
                            <div class="ws-card-title-group">
                                <h3 class="ws-card-title">Sidebar Background</h3>
                                <p class="ws-card-subtitle">Configure sidebar gradient colors</p>
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
                                    <input type="color" name="WorkspaceSettings[sidebar_border_color]" value="#1e293b">
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
                                <p class="ws-card-subtitle">Styling for the currently active menu item</p>
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
                                <p class="ws-card-subtitle">Styling when hovering over menu items</p>
                            </div>
                        </div>
                        
                        <div class="ws-color-row" style="grid-template-columns: repeat(2, 1fr);">
                            <div class="ws-color-group">
                                <label>Hover Background</label>
                                <div class="ws-color-picker">
                                    <input type="color" name="WorkspaceSettings[sidebar_hover_bg]" value="#ffffff">
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
                                <p class="ws-card-subtitle">Configure the top navigation bar appearance</p>
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
    
    document.querySelectorAll('input[type="color"]').forEach(colorInput => {
        colorInput.addEventListener('input', function() {
            const textInput = this.closest('.ws-color-picker').querySelector('.color-text');
            if (textInput) textInput.value = this.value;
            
            const previewBox = document.getElementById('preview-logo-box');
            const logoPreviewBox = document.getElementById('logo-preview-box');
            if ((this.id === 'logo-bg-color' || this.name === 'WorkspaceSettings[workspace_logo_bg]') && previewBox) {
                const color = this.value;
                previewBox.style.background = 'linear-gradient(135deg, ' + color + ' 0%, ' + color + ' 100%)';
                if (logoPreviewBox) logoPreviewBox.style.background = 'linear-gradient(135deg, ' + color + ' 0%, ' + color + ' 100%)';
            }
        });
    });
    
    const iconSelect = document.getElementById('logo-icon-select');
    if (iconSelect) {
        iconSelect.addEventListener('change', function() {
            const previewIcon = document.getElementById('preview-logo-icon');
            const logoPreviewIcon = document.getElementById('logo-preview-icon');
            if (previewIcon) previewIcon.textContent = this.value;
            if (logoPreviewIcon) logoPreviewIcon.textContent = this.value;
        });
    }
    
    document.querySelectorAll('.color-text').forEach(textInput => {
        textInput.addEventListener('input', function() {
            const colorInput = this.closest('.ws-color-picker').querySelector('input[type="color"]');
            if (colorInput && /^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                colorInput.value = this.value;
                
                const previewBox = document.getElementById('preview-logo-box');
                const logoPreviewBox = document.getElementById('logo-preview-box');
                if (this.name === 'WorkspaceSettings[workspace_logo_bg]' && previewBox) {
                    const color = this.value;
                    previewBox.style.background = 'linear-gradient(135deg, ' + color + ' 0%, ' + color + ' 100%)';
                    if (logoPreviewBox) logoPreviewBox.style.background = 'linear-gradient(135deg, ' + color + ' 0%, ' + color + ' 100%)';
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
});
</script>