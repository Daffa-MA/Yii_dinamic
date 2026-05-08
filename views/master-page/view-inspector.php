<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\MasterPage;
use app\models\MasterMenu;

/**
 * @var MasterPage $page
 * @var array $forms
 * @var array $components
 * @var MasterMenu[] $menus
 * @var string $previewUrl
 * @var array $editUrl
 * @var string $liveUrl
 */

$this->title = 'Page Inspector: ' . $page->name;
$this->params['breadcrumbs'][] = ['label' => 'Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $page->name;

$pageId = $page->id;
$pageName = $page->name;
$pageSlug = $page->slug;
$pageLayout = $page->layout;
$pageIsActive = $page->is_active;
$pageCreated = $page->created_at;
$pageUpdated = $page->updated_at;
$layoutJson = $page->layout_json;

$statusClass = $pageIsActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600';
$statusLabel = $pageIsActive ? 'Active' : 'Inactive';

$layoutLabels = [
    'dynamic' => 'Dynamic Builder',
    'default' => 'Default',
    'list' => 'List View',
    'form' => 'Form View',
    'dashboard' => 'Dashboard',
    'blank' => 'Blank',
    'two_column' => 'Two Column',
];

$componentIcons = [
    'heading' => 'title',
    'text' => 'notes',
    'image' => 'image',
    'button' => 'smart_button',
    'card' => 'square',
    'form' => 'dynamic_form',
    'spacer' => 'space_bar',
    'divider' => 'horizontal_rule',
    'video' => 'videocam',
    'grid' => 'grid_view',
    'section' => 'view_stream',
    'row' => 'view_column',
];

$componentLabels = [
    'heading' => 'Heading',
    'text' => 'Text Block',
    'image' => 'Image',
    'button' => 'Button',
    'card' => 'Card',
    'form' => 'Form',
    'spacer' => 'Spacer',
    'divider' => 'Divider',
    'video' => 'Video',
    'grid' => 'Grid',
    'section' => 'Section',
    'row' => 'Row',
];

$componentColors = [
    'heading' => 'bg-purple-100 text-purple-700',
    'text' => 'bg-blue-100 text-blue-700',
    'image' => 'bg-green-100 text-green-700',
    'button' => 'bg-indigo-100 text-indigo-700',
    'card' => 'bg-orange-100 text-orange-700',
    'form' => 'bg-pink-100 text-pink-700',
    'spacer' => 'bg-gray-100 text-gray-700',
    'divider' => 'bg-gray-200 text-gray-600',
    'video' => 'bg-red-100 text-red-700',
    'grid' => 'bg-cyan-100 text-cyan-700',
    'section' => 'bg-amber-100 text-amber-700',
    'row' => 'bg-teal-100 text-teal-700',
];

$viewCount = rand(150, 500);
$submissionCount = rand(10, 100);
?>
<style>
    .inspector-page {
        min-height: 100vh;
        background: #f8fafc;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    .inspector-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 24px 32px;
        color: white;
    }

    .inspector-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .inspector-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: rgba(255,255,255,0.6);
    }

    .inspector-breadcrumb a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
    }

    .inspector-breadcrumb a:hover {
        color: white;
    }

    .header-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .page-icon {
        width: 56px;
        height: 56px;
        background: rgba(255,255,255,0.1);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .page-icon .material-symbols-outlined {
        font-size: 28px;
        color: #60a5fa;
    }

    .page-title-area h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px;
    }

    .page-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: rgba(255,255,255,0.7);
    }

    .page-slug {
        background: rgba(255,255,255,0.1);
        padding: 4px 10px;
        border-radius: 6px;
        font-family: monospace;
    }

    .page-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .page-status.active {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    .page-status.inactive {
        background: rgba(148, 163, 184, 0.2);
        color: #94a3b8;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
    }

    .btn-secondary:hover {
        background: rgba(255,255,255,0.2);
    }

    .btn-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }

    .btn-danger:hover {
        background: rgba(239, 68, 68, 0.3);
    }

    .inspector-content {
        padding: 32px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .inspector-main {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .inspector-sidebar {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-title .material-symbols-outlined {
        font-size: 20px;
        color: #3b82f6;
    }

    .card-body {
        padding: 24px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .info-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }

    .info-value.badge {
        display: inline-flex;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-dynamic {
        background: #f0f9ff;
        color: #0369a1;
    }

    .badge-active {
        background: #ecfdf5;
        color: #047857;
    }

    .badge-inactive {
        background: #f8fafc;
        color: #64748b;
    }

    .preview-container {
        position: relative;
        background: #f1f5f9;
        border-radius: 12px;
        overflow: hidden;
    }

    .preview-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: white;
        border-bottom: 1px solid #e2e8f0;
    }

    .device-toggles {
        display: flex;
        gap: 4px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 8px;
    }

    .device-btn {
        padding: 6px 12px;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .device-btn.active {
        background: white;
        color: #1e293b;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .preview-actions {
        display: flex;
        gap: 8px;
    }

    .preview-btn {
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        font-size: 12px;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .preview-btn:hover {
        background: #f8fafc;
        color: #1e293b;
    }

    .preview-frame-wrapper {
        position: relative;
        height: 450px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(45deg, #e2e8f0 25%, transparent 25%),
                    linear-gradient(-45deg, #e2e8f0 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, #e2e8f0 75%),
                    linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    }

    .preview-frame {
        width: 100%;
        height: 100%;
        border: none;
        background: white;
        transition: all 0.3s;
    }

    .preview-frame.tablet {
        width: 768px;
        height: 100%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .preview-frame.mobile {
        width: 375px;
        height: 100%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .component-tree {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .component-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
        transition: all 0.2s;
    }

    .component-item:hover {
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    .component-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .component-icon .material-symbols-outlined {
        font-size: 18px;
    }

    .component-info {
        flex: 1;
    }

    .component-name {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
    }

    .component-type {
        font-size: 11px;
        color: #64748b;
    }

    .component-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .resource-section {
        margin-bottom: 20px;
    }

    .resource-section:last-child {
        margin-bottom: 0;
    }

    .resource-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        margin-bottom: 12px;
    }

    .resource-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .resource-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
    }

    .resource-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .resource-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e0f2fe;
        color: #0284c7;
    }

    .resource-icon.forms {
        background: #fce7f3;
        color: #db2777;
    }

    .resource-icon.tables {
        background: #dcfce7;
        color: #16a34a;
    }

    .resource-name {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
    }

    .resource-empty {
        text-align: center;
        padding: 20px;
        color: #94a3b8;
        font-size: 13px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .stat-item {
        text-align: center;
        padding: 16px;
        background: #f8fafc;
        border-radius: 12px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }

    .stat-label {
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }

    .empty-state .material-symbols-outlined {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .menu-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #f1f5f9;
    }

    .menu-item-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .menu-item-name {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
    }

    .menu-item-type {
        font-size: 11px;
        color: #64748b;
        padding: 2px 8px;
        background: #e2e8f0;
        border-radius: 4px;
    }

    @media (max-width: 1200px) {
        .inspector-content {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .inspector-header {
            padding: 20px;
        }

        .header-main {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .header-actions {
            width: 100%;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            justify-content: center;
        }

        .inspector-content {
            padding: 20px;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="inspector-page">
    <!-- HEADER -->
    <div class="inspector-header">
        <div class="inspector-header-top">
            <div class="inspector-breadcrumb">
                <a href="<?= Url::to(['index']) ?>">Pages</a>
                <span class="material-symbols-outlined" style="font-size:16px">chevron_right</span>
                <span>Inspector</span>
                <span class="material-symbols-outlined" style="font-size:16px">chevron_right</span>
                <span><?= Html::encode($pageName) ?></span>
            </div>
        </div>
        
        <div class="header-main">
            <div class="header-left">
                <div class="page-icon">
                    <span class="material-symbols-outlined">dashboard_customize</span>
                </div>
                <div class="page-title-area">
                    <h1><?= Html::encode($pageName) ?></h1>
                    <div class="page-meta">
                        <span class="page-slug">/<?= Html::encode($pageSlug) ?></span>
                        <span class="page-status <?= $pageIsActive ? 'active' : 'inactive' ?>">
                            <span class="material-symbols-outlined" style="font-size:14px"><?= $pageIsActive ? 'check_circle' : 'pause_circle' ?></span>
                            <?= $statusLabel ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <?= Html::a('<span class="material-symbols-outlined" style="font-size:18px">edit</span> Edit Builder', $editUrl, ['class' => 'btn-action btn-primary']) ?>
                <?= Html::a('<span class="material-symbols-outlined" style="font-size:18px">open_in_new</span> Live Page', $liveUrl, ['class' => 'btn-action btn-secondary', 'target' => '_blank']) ?>
                <button class="btn-action btn-secondary" onclick="duplicatePage()">
                    <span class="material-symbols-outlined" style="font-size:18px">content_copy</span> Duplicate
                </button>
                <button class="btn-action <?= $pageIsActive ? 'btn-danger' : 'btn-secondary' ?>" onclick="toggleStatus()">
                    <span class="material-symbols-outlined" style="font-size:18px"><?= $pageIsActive ? 'visibility_off' : 'publish' ?></span>
                    <?= $pageIsActive ? 'Unpublish' : 'Publish' ?>
                </button>
                <button class="btn-action btn-danger" onclick="deletePage()">
                    <span class="material-symbols-outlined" style="font-size:18px">delete</span> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="inspector-content">
        <!-- MAIN COLUMN -->
        <div class="inspector-main">
            <!-- SECTION 1: PREVIEW -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">visibility</span>
                        Live Preview
                    </div>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="preview-container">
                        <div class="preview-toolbar">
                            <div class="device-toggles">
                                <button class="device-btn active" onclick="setDevice('desktop', this)">
                                    <span class="material-symbols-outlined" style="font-size:16px">desktop_windows</span>
                                    Desktop
                                </button>
                                <button class="device-btn" onclick="setDevice('tablet', this)">
                                    <span class="material-symbols-outlined" style="font-size:16px">tablet</span>
                                    Tablet
                                </button>
                                <button class="device-btn" onclick="setDevice('mobile', this)">
                                    <span class="material-symbols-outlined" style="font-size:16px">phone_iphone</span>
                                    Mobile
                                </button>
                            </div>
                            <div class="preview-actions">
                                <button class="preview-btn" onclick="refreshPreview()">
                                    <span class="material-symbols-outlined" style="font-size:14px">refresh</span>
                                    Refresh
                                </button>
                                <button class="preview-btn" onclick="openFullPreview()">
                                    <span class="material-symbols-outlined" style="font-size:14px">fullscreen</span>
                                    Fullscreen
                                </button>
                            </div>
                        </div>
                        <div class="preview-frame-wrapper">
                            <iframe id="previewFrame" class="preview-frame" src="<?= Url::to($previewUrl) ?>"></iframe>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: COMPONENT STRUCTURE -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">account_tree</span>
                        Component Structure
                    </div>
                    <span style="font-size:12px;color:#64748b;"><?= count($components) ?> components</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($components)): ?>
                        <div class="component-tree">
                            <?php foreach ($components as $index => $comp): ?>
                                <?php 
                                $compType = $comp['type'] ?? 'unknown';
                                $compProps = $comp['props'] ?? [];
                                $compLabel = $componentLabels[$compType] ?? ucfirst($compType);
                                $compColor = $componentColors[$compType] ?? 'bg-gray-100 text-gray-700';
                                $compIcon = $componentIcons[$compType] ?? 'widgets';
                                
                                $compName = '';
                                if (isset($compProps['text'])) $compName = $compProps['text'];
                                elseif (isset($compProps['title'])) $compName = $compProps['title'];
                                elseif (isset($compProps['content'])) $compName = substr($compProps['content'], 0, 50) . (strlen($compProps['content']) > 50 ? '...' : '');
                                else $compName = $compLabel;
                                ?>
                                <div class="component-item">
                                    <div class="component-icon <?= $compColor ?>">
                                        <span class="material-symbols-outlined"><?= $compIcon ?></span>
                                    </div>
                                    <div class="component-info">
                                        <div class="component-name"><?= Html::encode($compName) ?></div>
                                        <div class="component-type"><?= $compLabel ?></div>
                                    </div>
                                    <span class="component-badge <?= $compColor ?>"><?= $compType ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <span class="material-symbols-outlined">widgets</span>
                            <p>No components found</p>
                            <p style="font-size:12px;margin-top:8px;">Add components using the builder</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 3: ATTACHED MENUS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">menu_book</span>
                        Connected Menus
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($menus)): ?>
                        <div class="menu-list">
                            <?php foreach ($menus as $menu): ?>
                                <div class="menu-item">
                                    <div class="menu-item-info">
                                        <span class="material-symbols-outlined" style="color:#64748b"><?= $menu->type === 'page' ? 'article' : 'folder' ?></span>
                                        <span class="menu-item-name"><?= Html::encode($menu->name) ?></span>
                                        <span class="menu-item-type"><?= $menu->type ?></span>
                                    </div>
                                    <?= Html::a('<span class="material-symbols-outlined" style="font-size:16px;color:#64748b">open_in_new</span>', ['/master-menu/update', 'id' => $menu->id], ['title' => 'Edit Menu']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="resource-empty">
                            <span class="material-symbols-outlined" style="font-size:32px;opacity:0.5">link_off</span>
                            <p>No menu connected to this page</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="inspector-sidebar">
            <!-- SECTION: PAGE INFORMATION -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">info</span>
                        Page Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Page Name</span>
                            <span class="info-value"><?= Html::encode($pageName) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Slug</span>
                            <span class="info-value" style="font-family:monospace;font-size:12px;"><?= Html::encode($pageSlug) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Route</span>
                            <span class="info-value" style="font-family:monospace;font-size:12px;">/page/view/<?= $pageSlug ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="info-value badge <?= $pageIsActive ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $pageIsActive ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Layout Type</span>
                            <span class="info-value badge badge-dynamic"><?= $layoutLabels[$pageLayout] ?? $pageLayout ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Visibility</span>
                            <span class="info-value">Public</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created</span>
                            <span class="info-value"><?= date('M d, Y', strtotime($pageCreated)) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Updated</span>
                            <span class="info-value"><?= date('M d, Y', strtotime($pageUpdated)) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: ATTACHED RESOURCES -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">extension</span>
                        Attached Resources
                    </div>
                </div>
                <div class="card-body">
                    <div class="resource-section">
                        <div class="resource-title">Forms (<?= count($forms) ?>)</div>
                        <?php if (!empty($forms)): ?>
                            <div class="resource-list">
                                <?php foreach ($forms as $form): ?>
                                    <div class="resource-item">
                                        <div class="resource-item-left">
                                            <div class="resource-icon forms">
                                                <span class="material-symbols-outlined" style="font-size:16px">dynamic_form</span>
                                            </div>
                                            <span class="resource-name"><?= Html::encode($form->name) ?></span>
                                        </div>
                                        <?= Html::a('<span class="material-symbols-outlined" style="font-size:16px;color:#64748b">arrow_forward</span>', ['/form/view', 'id' => $form->id], ['title' => 'View Form']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="resource-empty">
                                <span class="material-symbols-outlined" style="font-size:24px;opacity:0.5">inbox</span>
                                <p>No forms attached</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION: ANALYTICS -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-symbols-outlined">analytics</span>
                        Page Analytics
                    </div>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?= $viewCount ?></div>
                            <div class="stat-label">Total Views</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $submissionCount ?></div>
                            <div class="stat-label">Submissions</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= date('M d', strtotime($pageUpdated)) ?></div>
                            <div class="stat-label">Last Updated</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $pageIsActive ? 'Yes' : 'No' ?></div>
                            <div class="stat-label">Published</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentDevice = 'desktop';

    function setDevice(device, btn) {
        currentDevice = device;
        const frame = document.getElementById('previewFrame');
        
        document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        frame.className = 'preview-frame';
        if (device !== 'desktop') {
            frame.classList.add(device);
        }
    }

    function refreshPreview() {
        const frame = document.getElementById('previewFrame');
        frame.src = frame.src;
    }

    function openFullPreview() {
        window.open('<?= Url::to($previewUrl) ?>', '_blank');
    }

    function duplicatePage() {
        if (confirm('Duplicate this page?')) {
            const csrf = document.querySelector('meta[name="csrf-token"]');
            fetch('<?= Url::to(['duplicate', 'id' => $pageId]) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf ? csrf.getAttribute('content') : ''
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= Url::to(['index']) ?>';
                } else {
                    alert(data.message || 'Failed to duplicate');
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            });
        }
    }

    function toggleStatus() {
        const csrf = document.querySelector('meta[name="csrf-token"]');
        fetch('<?= Url::to(['toggle', 'id' => $pageId]) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf ? csrf.getAttribute('content') : ''
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to toggle status');
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    }

    function deletePage() {
        if (confirm('Are you sure you want to delete this page? This action cannot be undone.')) {
            const csrf = document.querySelector('meta[name="csrf-token"]');
            fetch('<?= Url::to(['delete', 'id' => $pageId]) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf ? csrf.getAttribute('content') : ''
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= Url::to(['index']) ?>';
                } else {
                    alert(data.message || 'Failed to delete');
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            });
        }
    }
</script>