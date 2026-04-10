<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */

use yii\bootstrap5\Html;

$this->title = 'Visual Website Builder';

// Register dependencies
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<style>
:root {
    --primary: #6366f1;
    --primary-light: #818cf8;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --sidebar-width: 300px;
    --toolbar-height: 60px;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-800: #1f2937;
    --gray-900: #111827;
}

* { box-sizing: border-box; }

.builder-wrapper {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
    background: var(--gray-100);
}

/* ============ TOP TOOLBAR ============ */
.builder-toolbar {
    height: var(--toolbar-height);
    background: white;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    flex-shrink: 0;
    z-index: 1000;
}

.toolbar-left { display: flex; align-items: center; gap: 12px; }
.toolbar-center { display: flex; align-items: center; gap: 8px; }
.toolbar-right { display: flex; align-items: center; gap: 10px; }

.toolbar-logo {
    font-weight: 700;
    font-size: 18px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.toolbar-divider { width: 1px; height: 28px; background: var(--gray-200); margin: 0 8px; }

.device-btn {
    padding: 8px 10px;
    border: none;
    background: none;
    border-radius: 8px;
    cursor: pointer;
    color: var(--gray-400);
    transition: all 0.2s;
    font-size: 18px;
}
.device-btn:hover { background: var(--gray-100); color: var(--gray-600); }
.device-btn.active { background: var(--primary); color: white; }

.zoom-select {
    padding: 6px 10px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 13px;
    background: white;
}

.btn-toolbar {
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid var(--gray-200);
    background: white;
    color: var(--gray-600);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-toolbar:hover { background: var(--gray-50); }
.btn-toolbar-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border-color: transparent;
}
.btn-toolbar-primary:hover { opacity: 0.9; }

/* ============ MAIN LAYOUT ============ */
.builder-main { display: flex; flex: 1; overflow: hidden; }

/* ============ LEFT SIDEBAR - BLOCKS ============ */
.builder-sidebar-left {
    width: var(--sidebar-width);
    background: white;
    border-right: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}

.sidebar-search {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-200);
}
.sidebar-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 13px;
}
.sidebar-search input:focus { outline: none; border-color: var(--primary); }

.sidebar-categories {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.block-category {
    margin-bottom: 8px;
}

.block-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.2s;
    user-select: none;
}
.block-category-header:hover { background: var(--gray-50); }

.block-category-title {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 8px;
}

.block-category-arrow {
    font-size: 10px;
    color: var(--gray-400);
    transition: transform 0.2s;
}
.block-category.open .block-category-arrow { transform: rotate(90deg); }

.block-category-items {
    display: none;
    padding: 4px 0;
}
.block-category.open .block-category-items { display: block; }

.block-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    margin: 2px 0;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    cursor: grab;
    transition: all 0.2s;
    user-select: none;
}
.block-item:hover {
    border-color: var(--primary);
    background: #f5f3ff;
    transform: translateX(4px);
}
.block-item:active { cursor: grabbing; }

.block-item-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gray-100);
    border-radius: 8px;
    font-size: 16px;
    flex-shrink: 0;
}

.block-item-info { flex: 1; min-width: 0; }
.block-item-name { font-size: 13px; font-weight: 500; color: var(--gray-800); }
.block-item-desc { font-size: 11px; color: var(--gray-400); margin-top: 2px; }

.block-item-drag { color: var(--gray-300); font-size: 14px; }

/* ============ CENTER CANVAS ============ */
.builder-canvas {
    flex: 1;
    overflow: auto;
    padding: 30px;
    display: flex;
    justify-content: center;
    background: var(--gray-100);
}

.canvas-wrapper {
    width: 100%;
    max-width: 900px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    min-height: 600px;
    transition: max-width 0.3s;
    overflow: hidden;
}
.canvas-wrapper.tablet { max-width: 600px; }
.canvas-wrapper.mobile { max-width: 375px; }

.canvas-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.canvas-form-name {
    font-size: 18px;
    font-weight: 600;
    border: none;
    outline: none;
    width: 100%;
    background: transparent;
    color: var(--gray-900);
}

.canvas-body {
    padding: 24px;
    min-height: 500px;
}

.canvas-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    border: 2px dashed var(--gray-300);
    border-radius: 12px;
    color: var(--gray-400);
    text-align: center;
}
.canvas-empty-icon { font-size: 56px; margin-bottom: 16px; }
.canvas-empty-text { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: var(--gray-600); }
.canvas-empty-hint { font-size: 14px; }

/* ============ CANVAS BLOCKS ============ */
.canvas-block {
    position: relative;
    margin-bottom: 8px;
    border: 2px solid transparent;
    border-radius: 8px;
    transition: all 0.2s;
    cursor: pointer;
}
.canvas-block:hover { border-color: var(--primary-light); }
.canvas-block.selected {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.canvas-block-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: 8px 8px 0 0;
    border-bottom: 1px solid var(--gray-200);
}

.canvas-block-type {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    color: var(--gray-600);
}

.canvas-block-actions {
    display: flex;
    gap: 4px;
}

.canvas-block-btn {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--gray-200);
    border-radius: 4px;
    background: white;
    color: var(--gray-400);
    cursor: pointer;
    font-size: 12px;
    transition: all 0.15s;
}
.canvas-block-btn:hover { background: var(--gray-100); color: var(--gray-600); }
.canvas-block-btn.delete:hover { background: #fee2e2; color: var(--danger); border-color: var(--danger); }

.canvas-block-preview {
    padding: 16px;
    min-height: 40px;
}

/* Block preview styles */
.preview-heading { font-size: 24px; font-weight: 700; color: var(--gray-900); margin: 0; }
.preview-subheading { font-size: 18px; font-weight: 600; color: var(--gray-700); margin: 0; }
.preview-text { color: var(--gray-600); margin: 0; line-height: 1.6; }
.preview-image { width: 100%; height: 200px; background: var(--gray-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gray-400); font-size: 32px; }
.preview-video { width: 100%; height: 250px; background: var(--gray-900); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; }
.preview-button { display: inline-block; padding: 10px 24px; background: var(--primary); color: white; border-radius: 8px; font-weight: 500; text-decoration: none; }
.preview-divider { border: none; border-top: 2px solid var(--gray-200); margin: 16px 0; }
.preview-spacer { height: 32px; background: var(--gray-50); border-radius: 4px; }
.preview-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.preview-grid-item { height: 80px; background: var(--gray-100); border-radius: 8px; }
.preview-product-card { border: 1px solid var(--gray-200); border-radius: 12px; overflow: hidden; }
.preview-product-img { height: 120px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; font-size: 32px; }
.preview-product-body { padding: 12px; }
.preview-product-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.preview-product-price { color: var(--primary); font-weight: 700; font-size: 16px; }
.preview-price { font-size: 24px; font-weight: 700; color: var(--primary); }
.preview-price-old { font-size: 16px; color: var(--gray-400); text-decoration: line-through; margin-left: 8px; }
.preview-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.preview-badge-sale { background: #fee2e2; color: var(--danger); }
.preview-badge-new { background: #d1fae5; color: var(--success); }
.preview-badge-hot { background: #fef3c7; color: var(--warning); }
.preview-stars { color: #fbbf24; font-size: 18px; }
.preview-input { width: 100%; padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 8px; font-size: 14px; background: var(--gray-50); pointer-events: none; }
.preview-textarea { width: 100%; padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 8px; font-size: 14px; min-height: 80px; background: var(--gray-50); pointer-events: none; }
.preview-select { width: 100%; padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 8px; font-size: 14px; background: var(--gray-50); pointer-events: none; }
.preview-checkbox { display: flex; align-items: center; gap: 8px; }
.preview-radio { display: flex; align-items: center; gap: 8px; }
.preview-date { width: 100%; padding: 10px 14px; border: 1px solid var(--gray-300); border-radius: 8px; font-size: 14px; background: var(--gray-50); pointer-events: none; }
.preview-file { width: 100%; padding: 20px; border: 2px dashed var(--gray-300); border-radius: 8px; text-align: center; color: var(--gray-400); background: var(--gray-50); }
.preview-gallery { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.preview-gallery-item { height: 60px; background: var(--gray-100); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.preview-team { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--gray-200); border-radius: 12px; }
.preview-team-avatar { width: 60px; height: 60px; background: var(--gray-200); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.preview-team-name { font-weight: 600; }
.preview-team-role { font-size: 13px; color: var(--gray-400); }
.preview-testimonial { padding: 20px; background: var(--gray-50); border-radius: 12px; border-left: 4px solid var(--primary); }
.preview-testimonial-text { font-style: italic; color: var(--gray-600); margin-bottom: 12px; }
.preview-testimonial-author { font-weight: 600; }
.preview-faq-item { padding: 16px; border: 1px solid var(--gray-200); border-radius: 8px; margin-bottom: 8px; }
.preview-faq-question { font-weight: 600; margin-bottom: 8px; }
.preview-faq-answer { color: var(--gray-600); font-size: 14px; }
.preview-pricing { padding: 24px; border: 2px solid var(--gray-200); border-radius: 16px; text-align: center; }
.preview-pricing-name { font-weight: 600; font-size: 18px; margin-bottom: 8px; }
.preview-pricing-price { font-size: 36px; font-weight: 700; color: var(--primary); margin-bottom: 16px; }
.preview-pricing-features { list-style: none; padding: 0; margin-bottom: 20px; }
.preview-pricing-features li { padding: 8px 0; color: var(--gray-600); }
.preview-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; text-align: center; }
.preview-stat-number { font-size: 32px; font-weight: 700; color: var(--primary); }
.preview-stat-label { font-size: 13px; color: var(--gray-400); }
.preview-social { display: flex; gap: 12px; }
.preview-social-icon { width: 40px; height: 40px; background: var(--gray-100); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.preview-newsletter { padding: 24px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 12px; color: white; text-align: center; }
.preview-newsletter-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
.preview-newsletter-input { width: 100%; max-width: 300px; padding: 10px 16px; border: none; border-radius: 8px; margin: 12px 0; }
.preview-countdown { display: flex; gap: 12px; justify-content: center; }
.preview-countdown-item { padding: 12px 16px; background: var(--gray-900); color: white; border-radius: 8px; text-align: center; min-width: 60px; }
.preview-countdown-number { font-size: 24px; font-weight: 700; }
.preview-countdown-label { font-size: 10px; text-transform: uppercase; color: var(--gray-400); }
.preview-progress { height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden; }
.preview-progress-bar { height: 100%; background: var(--primary); border-radius: 4px; }
.preview-timeline { position: relative; padding-left: 24px; }
.preview-timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: var(--gray-200); }
.preview-timeline-item { position: relative; margin-bottom: 16px; padding-left: 16px; }
.preview-timeline-dot { position: absolute; left: -20px; top: 4px; width: 12px; height: 12px; background: var(--primary); border-radius: 50%; }
.preview-table { width: 100%; border-collapse: collapse; }
.preview-table th, .preview-table td { padding: 10px 12px; border: 1px solid var(--gray-200); text-align: left; }
.preview-table th { background: var(--gray-50); font-weight: 600; }
.preview-alert { padding: 16px; border-radius: 8px; margin-bottom: 12px; }
.preview-alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid var(--info); }
.preview-alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--success); }
.preview-alert-warning { background: #fef3c7; color: #92400e; border-left: 4px solid var(--warning); }
.preview-alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--danger); }
.preview-accordion-item { border: 1px solid var(--gray-200); border-radius: 8px; margin-bottom: 8px; overflow: hidden; }
.preview-accordion-header { padding: 12px 16px; background: var(--gray-50); font-weight: 600; }
.preview-accordion-body { padding: 12px 16px; color: var(--gray-600); }
.preview-tabs { display: flex; border-bottom: 2px solid var(--gray-200); margin-bottom: 16px; }
.preview-tab { padding: 10px 20px; font-weight: 500; color: var(--gray-400); border-bottom: 2px solid transparent; margin-bottom: -2px; }
.preview-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.preview-map { height: 200px; background: var(--gray-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 48px; }
.preview-contact-card { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--gray-200); border-radius: 12px; }
.preview-contact-icon { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.preview-badge-tag { display: inline-block; padding: 4px 10px; background: var(--gray-100); border-radius: 20px; font-size: 12px; color: var(--gray-600); margin: 2px; }
.preview-icon-box { text-align: center; padding: 20px; }
.preview-icon-circle { width: 56px; height: 56px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 12px; }
.preview-list { list-style: none; padding: 0; }
.preview-list li { padding: 8px 0; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; gap: 8px; }
.preview-quote { padding: 20px; border-left: 4px solid var(--primary); background: var(--gray-50); border-radius: 0 8px 8px 0; font-style: italic; color: var(--gray-600); }
.preview-code { background: var(--gray-900); color: #a5f3fc; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 13px; overflow-x: auto; }
.preview-chart { height: 150px; background: var(--gray-50); border-radius: 8px; display: flex; align-items: flex-end; padding: 16px; gap: 8px; }
.preview-chart-bar { flex: 1; background: var(--primary); border-radius: 4px 4px 0 0; }

/* ============ RIGHT SIDEBAR - PROPERTIES ============ */
.builder-sidebar-right {
    width: 320px;
    background: white;
    border-left: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}

.properties-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.properties-tabs {
    display: flex;
    border-bottom: 1px solid var(--gray-200);
}

.properties-tab {
    flex: 1;
    padding: 12px;
    text-align: center;
    font-size: 12px;
    font-weight: 500;
    color: var(--gray-400);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}
.properties-tab:hover { color: var(--gray-600); }
.properties-tab.active { color: var(--primary); border-bottom-color: var(--primary); }

.properties-content { flex: 1; overflow-y: auto; padding: 16px; }

.property-section { margin-bottom: 20px; }
.property-section-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400);
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-100);
}

.property-field { margin-bottom: 14px; }
.property-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-600);
    margin-bottom: 6px;
}
.property-input, .property-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.2s;
}
.property-input:focus, .property-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.property-select { background: white; }
.property-textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 13px;
    min-height: 60px;
    resize: vertical;
}
.property-textarea:focus { outline: none; border-color: var(--primary); }
.property-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: 8px;
    cursor: pointer;
}
.property-checkbox input { accent-color: var(--primary); }
.property-hint { font-size: 11px; color: var(--gray-400); margin-top: 4px; }
.property-color {
    width: 40px;
    height: 32px;
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    cursor: pointer;
    padding: 2px;
}

/* ============ ANIMATIONS ============ */
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }

.canvas-block { animation: slideIn 0.3s ease; }
.sortable-ghost { opacity: 0.4; border: 2px dashed var(--primary) !important; }

/* ============ RESPONSIVE ============ */
@media (max-width: 1200px) {
    :root { --sidebar-width: 260px; }
    .builder-sidebar-right { width: 280px; }
}
@media (max-width: 992px) {
    .builder-sidebar-right { display: none; }
}
</style>

<div class="builder-wrapper">
    <!-- TOOLBAR -->
    <div class="builder-toolbar">
        <div class="toolbar-left">
            <span class="toolbar-logo">⚡ Visual Builder</span>
            <div class="toolbar-divider"></div>
            <span style="font-size:14px;color:var(--gray-600);"><?= $model->isNewRecord ? 'New Page' : Html::encode($model->name) ?></span>
        </div>

        <div class="toolbar-center">
            <button class="device-btn active" data-device="desktop" title="Desktop">🖥️</button>
            <button class="device-btn" data-device="tablet" title="Tablet">📱</button>
            <button class="device-btn" data-device="mobile" title="Mobile">📲</button>
            <div class="toolbar-divider"></div>
            <select class="zoom-select" id="zoom-select">
                <option value="100">100%</option>
                <option value="75">75%</option>
                <option value="50">50%</option>
            </select>
        </div>

        <div class="toolbar-right">
            <button class="btn-toolbar" id="btn-undo" title="Undo">↩️</button>
            <button class="btn-toolbar" id="btn-redo" title="Redo">↪️</button>
            <div class="toolbar-divider"></div>
            <?= Html::a('👁️ Preview', ['form/render', 'id' => $model->id], ['class' => 'btn-toolbar', 'id' => 'btn-preview', 'style' => $model->isNewRecord ? 'display:none' : '']) ?>
            <button class="btn-toolbar btn-toolbar-primary" id="btn-save">
                💾 <?= $model->isNewRecord ? 'Publish' : 'Update' ?>
            </button>
        </div>
    </div>

    <!-- MAIN -->
    <div class="builder-main">
        <!-- LEFT SIDEBAR - BLOCKS -->
        <div class="builder-sidebar-left">
            <div class="sidebar-search">
                <input type="text" id="block-search" placeholder="🔍 Search blocks...">
            </div>
            <div class="sidebar-categories" id="blocks-container">

                <!-- LAYOUT BLOCKS -->
                <div class="block-category open" data-category="layout">
                    <div class="block-category-header">
                        <span class="block-category-title">📐 Layout</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="container"><div class="block-item-icon">📦</div><div class="block-item-info"><div class="block-item-name">Container</div><div class="block-item-desc">Wrapper container</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="columns"><div class="block-item-icon">🔲</div><div class="block-item-info"><div class="block-item-name">Columns</div><div class="block-item-desc">Multi-column layout</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="grid"><div class="block-item-icon">⊞</div><div class="block-item-info"><div class="block-item-name">Grid</div><div class="block-item-desc">Grid layout</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="section"><div class="block-item-icon">📄</div><div class="block-item-info"><div class="block-item-name">Section</div><div class="block-item-desc">Full-width section</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="divider"><div class="block-item-icon">➖</div><div class="block-item-info"><div class="block-item-name">Divider</div><div class="block-item-desc">Horizontal line</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="spacer"><div class="block-item-icon">↕️</div><div class="block-item-info"><div class="block-item-name">Spacer</div><div class="block-item-desc">Empty space</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- TYPOGRAPHY -->
                <div class="block-category open" data-category="typography">
                    <div class="block-category-header">
                        <span class="block-category-title">🔤 Typography</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="heading"><div class="block-item-icon"></div><div class="block-item-info"><div class="block-item-name">Heading</div><div class="block-item-desc">H1-H6 heading</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="text"><div class="block-item-icon">📝</div><div class="block-item-info"><div class="block-item-name">Text</div><div class="block-item-desc">Paragraph text</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="richtext"><div class="block-item-icon">📄</div><div class="block-item-info"><div class="block-item-name">Rich Text</div><div class="block-item-desc">Formatted text</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="list"><div class="block-item-icon">📋</div><div class="block-item-info"><div class="block-item-name">List</div><div class="block-item-desc">Bullet/numbered list</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="quote"><div class="block-item-icon">💬</div><div class="block-item-info"><div class="block-item-name">Quote</div><div class="block-item-desc">Blockquote</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="code"><div class="block-item-icon">💻</div><div class="block-item-info"><div class="block-item-name">Code</div><div class="block-item-desc">Code block</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- MEDIA -->
                <div class="block-category open" data-category="media">
                    <div class="block-category-header">
                        <span class="block-category-title">🖼️ Media</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="image"><div class="block-item-icon">🖼️</div><div class="block-item-info"><div class="block-item-name">Image</div><div class="block-item-desc">Single image</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="gallery"><div class="block-item-icon">🖼️</div><div class="block-item-info"><div class="block-item-name">Gallery</div><div class="block-item-desc">Image gallery</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="video"><div class="block-item-icon">🎬</div><div class="block-item-info"><div class="block-item-name">Video</div><div class="block-item-desc">Video embed</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="icon"><div class="block-item-icon">⭐</div><div class="block-item-info"><div class="block-item-name">Icon</div><div class="block-item-desc">Icon element</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="avatar"><div class="block-item-icon">👤</div><div class="block-item-info"><div class="block-item-name">Avatar</div><div class="block-item-desc">User avatar</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- FORM ELEMENTS -->
                <div class="block-category open" data-category="forms">
                    <div class="block-category-header">
                        <span class="block-category-title">📋 Form Elements</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="text-input"><div class="block-item-icon">📝</div><div class="block-item-info"><div class="block-item-name">Text Input</div><div class="block-item-desc">Single line text</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="textarea"><div class="block-item-icon">📄</div><div class="block-item-info"><div class="block-item-name">Textarea</div><div class="block-item-desc">Multi-line text</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="email"><div class="block-item-icon">📧</div><div class="block-item-info"><div class="block-item-name">Email</div><div class="block-item-desc">Email input</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="number"><div class="block-item-icon">🔢</div><div class="block-item-info"><div class="block-item-name">Number</div><div class="block-item-desc">Number input</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="password"><div class="block-item-icon">🔒</div><div class="block-item-info"><div class="block-item-name">Password</div><div class="block-item-desc">Password field</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="select"><div class="block-item-icon">📋</div><div class="block-item-info"><div class="block-item-name">Dropdown</div><div class="block-item-desc">Select dropdown</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="checkbox"><div class="block-item-icon">☑️</div><div class="block-item-info"><div class="block-item-name">Checkbox</div><div class="block-item-desc">Checkbox input</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="radio"><div class="block-item-icon">🔘</div><div class="block-item-info"><div class="block-item-name">Radio</div><div class="block-item-desc">Radio buttons</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="date"><div class="block-item-icon">📅</div><div class="block-item-info"><div class="block-item-name">Date</div><div class="block-item-desc">Date picker</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="file"><div class="block-item-icon">📎</div><div class="block-item-info"><div class="block-item-name">File Upload</div><div class="block-item-desc">File upload field</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="hidden"><div class="block-item-icon">👁️‍🗨️</div><div class="block-item-info"><div class="block-item-name">Hidden</div><div class="block-item-desc">Hidden field</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="submit"><div class="block-item-icon">🚀</div><div class="block-item-info"><div class="block-item-name">Submit Button</div><div class="block-item-desc">Form submit button</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- E-COMMERCE -->
                <div class="block-category open" data-category="ecommerce">
                    <div class="block-category-header">
                        <span class="block-category-title">🛒 E-Commerce</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="product-card"><div class="block-item-icon">📦</div><div class="block-item-info"><div class="block-item-name">Product Card</div><div class="block-item-desc">Single product</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="product-grid"><div class="block-item-icon">⊞</div><div class="block-item-info"><div class="block-item-name">Product Grid</div><div class="block-item-desc">Product grid</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="price"><div class="block-item-icon">💰</div><div class="block-item-info"><div class="block-item-name">Price</div><div class="block-item-desc">Price display</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="add-to-cart"><div class="block-item-icon">🛒</div><div class="block-item-info"><div class="block-item-name">Add to Cart</div><div class="block-item-desc">Cart button</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="product-badge"><div class="block-item-icon">🏷️</div><div class="block-item-info"><div class="block-item-name">Badge</div><div class="block-item-desc">Sale/New/Hot badge</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="stars"><div class="block-item-icon">⭐</div><div class="block-item-info"><div class="block-item-name">Star Rating</div><div class="block-item-desc">Star rating</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="stock-status"><div class="block-item-icon">📊</div><div class="block-item-info"><div class="block-item-name">Stock Status</div><div class="block-item-desc">In/Out of stock</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="buy-now"><div class="block-item-icon">⚡</div><div class="block-item-info"><div class="block-item-name">Buy Now</div><div class="block-item-desc">Buy now button</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- BUSINESS -->
                <div class="block-category open" data-category="business">
                    <div class="block-category-header">
                        <span class="block-category-title">💼 Business</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="hero"><div class="block-item-icon">🎯</div><div class="block-item-info"><div class="block-item-name">Hero Section</div><div class="block-item-desc">Hero banner</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="team"><div class="block-item-icon">👥</div><div class="block-item-info"><div class="block-item-name">Team Member</div><div class="block-item-desc">Team card</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="testimonial"><div class="block-item-icon">💬</div><div class="block-item-info"><div class="block-item-name">Testimonial</div><div class="block-item-desc">Customer review</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="pricing"><div class="block-item-icon">💎</div><div class="block-item-info"><div class="block-item-name">Pricing Card</div><div class="block-item-desc">Pricing plan</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="faq"><div class="block-item-icon">❓</div><div class="block-item-info"><div class="block-item-name">FAQ</div><div class="block-item-desc">FAQ item</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="stats"><div class="block-item-icon">📊</div><div class="block-item-info"><div class="block-item-name">Stats Counter</div><div class="block-item-desc">Number counter</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="features"><div class="block-item-icon">✨</div><div class="block-item-info"><div class="block-item-name">Features</div><div class="block-item-desc">Feature list</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="contact-card"><div class="block-item-icon">📞</div><div class="block-item-info"><div class="block-item-name">Contact Card</div><div class="block-item-desc">Contact info</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- INTERACTIVE -->
                <div class="block-category open" data-category="interactive">
                    <div class="block-category-header">
                        <span class="block-category-title">⚡ Interactive</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="button"><div class="block-item-icon">🔘</div><div class="block-item-info"><div class="block-item-name">Button</div><div class="block-item-desc">CTA button</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="link"><div class="block-item-icon">🔗</div><div class="block-item-info"><div class="block-item-name">Link</div><div class="block-item-desc">Text link</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="tabs"><div class="block-item-icon">📑</div><div class="block-item-info"><div class="block-item-name">Tabs</div><div class="block-item-desc">Tab container</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="accordion"><div class="block-item-icon">📂</div><div class="block-item-info"><div class="block-item-name">Accordion</div><div class="block-item-desc">Collapsible content</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="progress"><div class="block-item-icon">📊</div><div class="block-item-info"><div class="block-item-name">Progress Bar</div><div class="block-item-desc">Progress indicator</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="timeline"><div class="block-item-icon">📅</div><div class="block-item-info"><div class="block-item-name">Timeline</div><div class="block-item-desc">Timeline item</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- MARKETING -->
                <div class="block-category open" data-category="marketing">
                    <div class="block-category-header">
                        <span class="block-category-title">📢 Marketing</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="newsletter"><div class="block-item-icon">📬</div><div class="block-item-info"><div class="block-item-name">Newsletter</div><div class="block-item-desc">Email signup</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="countdown"><div class="block-item-icon">⏰</div><div class="block-item-info"><div class="block-item-name">Countdown</div><div class="block-item-desc">Countdown timer</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="alert"><div class="block-item-icon">⚠️</div><div class="block-item-info"><div class="block-item-name">Alert</div><div class="block-item-desc">Notification banner</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="cta"><div class="block-item-icon">🎯</div><div class="block-item-info"><div class="block-item-name">Call to Action</div><div class="block-item-desc">CTA section</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- SOCIAL -->
                <div class="block-category open" data-category="social">
                    <div class="block-category-header">
                        <span class="block-category-title">🌐 Social</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="social-links"><div class="block-item-icon">🔗</div><div class="block-item-info"><div class="block-item-name">Social Links</div><div class="block-item-desc">Social media icons</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="share-buttons"><div class="block-item-icon">📤</div><div class="block-item-info"><div class="block-item-name">Share Buttons</div><div class="block-item-desc">Social share</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="embed"><div class="block-item-icon">📺</div><div class="block-item-info"><div class="block-item-name">Embed</div><div class="block-item-desc">YouTube/Instagram</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- DATA -->
                <div class="block-category open" data-category="data">
                    <div class="block-category-header">
                        <span class="block-category-title">📊 Data</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="table"><div class="block-item-icon">📋</div><div class="block-item-info"><div class="block-item-name">Table</div><div class="block-item-desc">Data table</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="badge"><div class="block-item-icon">🏷️</div><div class="block-item-info"><div class="block-item-name">Badge/Tag</div><div class="block-item-desc">Label badge</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="chart"><div class="block-item-icon">📈</div><div class="block-item-info"><div class="block-item-name">Chart</div><div class="block-item-desc">Bar chart placeholder</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="map"><div class="block-item-icon">🗺️</div><div class="block-item-info"><div class="block-item-name">Map</div><div class="block-item-desc">Google Map embed</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

                <!-- ADVANCED -->
                <div class="block-category open" data-category="advanced">
                    <div class="block-category-header">
                        <span class="block-category-title">🔧 Advanced</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" data-type="html"><div class="block-item-icon">💻</div><div class="block-item-info"><div class="block-item-name">Custom HTML</div><div class="block-item-desc">Raw HTML code</div></div><span class="block-item-drag">⠿</span></div>
                        <div class="block-item" data-type="template"><div class="block-item-icon">📄</div><div class="block-item-info"><div class="block-item-name">Template</div><div class="block-item-desc">Reusable template</div></div><span class="block-item-drag">⠿</span></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- CENTER CANVAS -->
        <div class="builder-canvas">
            <div class="canvas-wrapper" id="canvas-wrapper">
                <?= Html::beginForm(['form/' . ($model->isNewRecord ? 'create' : 'update'), 'id' => $model->isNewRecord ? null : $model->id], 'post', ['id' => 'builder-form']) ?>

                <div class="canvas-header">
                    <input type="text" class="canvas-form-name" name="Form[name]" placeholder="Page title..." value="<?= Html::encode($model->name) ?>">
                </div>

                <?= Html::hiddenInput('FormSchema', $model->isNewRecord ? '[]' : htmlspecialchars($model->schema_json, ENT_QUOTES, 'UTF-8'), ['id' => 'schema-json']) ?>

                <div class="canvas-body" id="canvas-body">
                    <div class="canvas-empty" id="canvas-empty">
                        <div class="canvas-empty-icon">🧩</div>
                        <div class="canvas-empty-text">Drag & Drop Blocks Here</div>
                        <div class="canvas-empty-hint">or click blocks from the left panel to add them</div>
                    </div>
                    <div id="canvas-blocks" style="min-height:50px;"></div>
                </div>

                <div style="padding:16px 24px;border-top:1px solid var(--gray-200);display:flex;justify-content:flex-end;gap:12px;">
                    <?= Html::a('Cancel', ['form/index'], ['class' => 'btn-toolbar']) ?>
                    <button type="submit" class="btn-toolbar btn-toolbar-primary">💾 <?= $model->isNewRecord ? 'Publish Page' : 'Update Page' ?></button>
                </div>

                <?= Html::endForm() ?>
            </div>
        </div>

        <!-- RIGHT SIDEBAR - PROPERTIES -->
        <div class="builder-sidebar-right">
            <div class="properties-header">⚙️ Properties</div>
            <div class="properties-tabs">
                <div class="properties-tab active" data-tab="content">Content</div>
                <div class="properties-tab" data-tab="style">Style</div>
                <div class="properties-tab" data-tab="advanced">Advanced</div>
            </div>
            <div class="properties-content">
                <div id="props-empty" class="text-center text-muted py-5">
                    <div style="font-size:48px;margin-bottom:12px;">🎯</div>
                    <p>Select a block to edit</p>
                </div>

                <div id="props-content" class="props-tab" style="display:none;">
                    <div class="property-section">
                        <div class="property-section-title">Content</div>
                        <div class="property-field">
                            <label class="property-label">Label/Title</label>
                            <input type="text" class="property-input" id="prop-label" placeholder="Enter text...">
                        </div>
                        <div class="property-field">
                            <label class="property-label">Description</label>
                            <textarea class="property-textarea" id="prop-desc" placeholder="Enter description..."></textarea>
                        </div>
                        <div class="property-field">
                            <label class="property-label">URL/Link</label>
                            <input type="text" class="property-input" id="prop-url" placeholder="https://...">
                        </div>
                        <div class="property-field">
                            <label class="property-label">Image URL</label>
                            <input type="text" class="property-input" id="prop-image" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <div id="props-style" class="props-tab" style="display:none;">
                    <div class="property-section">
                        <div class="property-section-title">Appearance</div>
                        <div class="property-field">
                            <label class="property-label">Background Color</label>
                            <input type="color" class="property-color" id="prop-bg-color" value="#ffffff">
                        </div>
                        <div class="property-field">
                            <label class="property-label">Text Color</label>
                            <input type="color" class="property-color" id="prop-text-color" value="#1f2937">
                        </div>
                        <div class="property-field">
                            <label class="property-label">Padding</label>
                            <select class="property-select" id="prop-padding">
                                <option value="none">None</option>
                                <option value="sm">Small</option>
                                <option value="md" selected>Medium</option>
                                <option value="lg">Large</option>
                                <option value="xl">Extra Large</option>
                            </select>
                        </div>
                        <div class="property-field">
                            <label class="property-label">Border Radius</label>
                            <select class="property-select" id="prop-radius">
                                <option value="none">None</option>
                                <option value="sm">Small</option>
                                <option value="md" selected>Medium</option>
                                <option value="lg">Large</option>
                                <option value="full">Full (Circle)</option>
                            </select>
                        </div>
                        <div class="property-field">
                            <label class="property-label">Text Alignment</label>
                            <select class="property-select" id="prop-align">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="props-advanced" class="props-tab" style="display:none;">
                    <div class="property-section">
                        <div class="property-section-title">Advanced</div>
                        <div class="property-field">
                            <label class="property-checkbox"><input type="checkbox" id="prop-hidden"><span>Hidden</span></label>
                        </div>
                        <div class="property-field">
                            <label class="property-label">CSS Class</label>
                            <input type="text" class="property-input" id="prop-class" placeholder="custom-class">
                        </div>
                        <div class="property-field">
                            <label class="property-label">Custom CSS</label>
                            <textarea class="property-textarea" id="prop-css" placeholder="color: red;"></textarea>
                        </div>
                    </div>
                </div>

                <div id="props-delete-section" class="property-section" style="display:none;">
                    <button class="btn-toolbar w-100" style="border-color:var(--danger);color:var(--danger);" id="btn-delete-block">🗑️ Delete Block</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let blocks = <?= $model->isNewRecord ? '[]' : $model->schema_json ?>;
    let selectedIndex = -1;
    let sortableInstance = null;
    let undoStack = [];
    let redoStack = [];

    const canvasBlocks = document.getElementById('canvas-blocks');
    const canvasEmpty = document.getElementById('canvas-empty');
    const schemaJson = document.getElementById('schema-json');

    // Initialize Sortable ONCE
    sortableInstance = new Sortable(canvasBlocks, {
        animation: 200,
        ghostClass: 'sortable-ghost',
        handle: '.drag-handle',
        onEnd: function() {
            const newOrder = [];
            canvasBlocks.querySelectorAll('.canvas-block').forEach(function(el) {
                newOrder.push(blocks[parseInt(el.dataset.index)]);
            });
            blocks = newOrder;
            updateCanvasIndices();
            saveState();
        }
    });

    // Block search
    document.getElementById('block-search').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.block-item').forEach(function(item) {
            const name = item.querySelector('.block-item-name').textContent.toLowerCase();
            const desc = item.querySelector('.block-item-desc').textContent.toLowerCase();
            item.style.display = (name.indexOf(query) > -1 || desc.indexOf(query) > -1) ? '' : 'none';
        });
    });

    // Category toggle
    document.querySelectorAll('.block-category-header').forEach(function(header) {
        header.addEventListener('click', function() {
            this.parentElement.classList.toggle('open');
        });
    });

    // Block click to add
    document.querySelectorAll('.block-item').forEach(function(item) {
        item.addEventListener('click', function() {
            addBlock(this.dataset.type);
        });
    });

    // Device buttons
    document.querySelectorAll('.device-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.device-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            const wrapper = document.getElementById('canvas-wrapper');
            wrapper.classList.remove('tablet', 'mobile');
            if (this.dataset.device !== 'desktop') wrapper.classList.add(this.dataset.device);
        });
    });

    // Properties tabs
    document.querySelectorAll('.properties-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.properties-tab').forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.props-tab').forEach(function(el) { el.style.display = 'none'; });
            document.getElementById('props-' + this.dataset.tab).style.display = '';
        });
    });

    function addBlock(type) {
        saveState();
        const block = getDefaultBlock(type);
        blocks.push(block);
        renderBlock(block, blocks.length - 1);
        updateEmptyState();
        selectBlock(blocks.length - 1);
    }

    function getDefaultBlock(type) {
        const defaults = {
            container: { type:'container', label:'Container', content:'', bgColor:'#ffffff', padding:'md' },
            columns: { type:'columns', label:'Columns', columns:3, content:'' },
            grid: { type:'grid', label:'Grid', columns:3, content:'' },
            section: { type:'section', label:'Section', bgColor:'#f9fafb', content:'' },
            divider: { type:'divider', label:'Divider', style:'solid' },
            spacer: { type:'spacer', label:'Spacer', height:32 },
            heading: { type:'heading', label:'Heading', content:'Your Heading Here', level:'h2', align:'left' },
            text: { type:'text', label:'Text', content:'Your text content goes here. You can edit this in the properties panel.' },
            richtext: { type:'richtext', label:'Rich Text', content:'<p>Your <strong>rich</strong> text here.</p>' },
            list: { type:'list', label:'List', items:'Item 1\nItem 2\nItem 3', ordered:false },
            quote: { type:'quote', label:'Quote', content:'"This is a great quote."', author:'- Author Name' },
            code: { type:'code', label:'Code', content:'console.log("Hello World");' },
            image: { type:'image', label:'Image', src:'', alt:'Image', caption:'' },
            gallery: { type:'gallery', label:'Gallery', images:4, columns:4 },
            video: { type:'video', label:'Video', url:'https://youtube.com/embed/...', autoplay:false },
            icon: { type:'icon', label:'Icon', icon:'⭐', size:48, color:'#6366f1' },
            avatar: { type:'avatar', label:'Avatar', src:'', name:'User', role:'Role' },
            'text-input': { type:'text-input', label:'Text Input', placeholder:'Enter text...', required:false },
            textarea: { type:'textarea', label:'Textarea', placeholder:'Enter message...', required:false },
            email: { type:'email', label:'Email', placeholder:'email@example.com', required:false },
            number: { type:'number', label:'Number', placeholder:'0', required:false },
            password: { type:'password', label:'Password', placeholder:'••••••••', required:false },
            select: { type:'select', label:'Dropdown', options:'Option 1\nOption 2\nOption 3', required:false },
            checkbox: { type:'checkbox', label:'Checkbox', text:'I agree to the terms', required:false },
            radio: { type:'radio', label:'Radio', options:'Option 1\nOption 2\nOption 3', required:false },
            date: { type:'date', label:'Date', required:false },
            file: { type:'file', label:'File Upload', accept:'*', required:false },
            hidden: { type:'hidden', label:'Hidden Field', name:'hidden_field', value:'' },
            submit: { type:'submit', label:'Submit Button', text:'Submit', variant:'primary' },
            'product-card': { type:'product-card', label:'Product', name:'Product Name', price:'99.99', image:'', badge:'sale' },
            'product-grid': { type:'product-grid', label:'Product Grid', products:4, columns:3 },
            price: { type:'price', label:'Price', amount:'99.99', currency:'$', oldPrice:'149.99' },
            'add-to-cart': { type:'add-to-cart', label:'Add to Cart', text:'Add to Cart', variant:'primary' },
            'product-badge': { type:'product-badge', label:'Badge', text:'SALE', variant:'sale' },
            stars: { type:'stars', label:'Stars', rating:4, max:5 },
            'stock-status': { type:'stock-status', label:'Stock', status:'instock', text:'In Stock' },
            'buy-now': { type:'buy-now', label:'Buy Now', text:'Buy Now' },
            hero: { type:'hero', label:'Hero', title:'Welcome to Our Site', subtitle:'Build amazing things', buttonText:'Get Started', buttonUrl:'#' },
            team: { type:'team', label:'Team Member', name:'John Doe', role:'CEO', image:'' },
            testimonial: { type:'testimonial', label:'Testimonial', text:'"Amazing service!"', author:'Jane Smith', role:'Customer' },
            pricing: { type:'pricing', label:'Pricing', name:'Pro Plan', price:'29', period:'/month', features:'Feature 1\nFeature 2\nFeature 3', highlighted:false },
            faq: { type:'faq', label:'FAQ', question:'Frequently Asked Question?', answer:'This is the answer to the question.' },
            stats: { type:'stats', label:'Stats', number:'1000', label:'Happy Customers', suffix:'+' },
            features: { type:'features', label:'Feature', icon:'✨', title:'Feature Title', description:'Feature description goes here.' },
            'contact-card': { type:'contact-card', label:'Contact', icon:'📞', title:'Phone', info:'+1 234 567 890' },
            button: { type:'button', label:'Button', text:'Click Me', url:'#', variant:'primary' },
            link: { type:'link', label:'Link', text:'Click here', url:'#' },
            tabs: { type:'tabs', label:'Tabs', tabs:'Tab 1\nTab 2\nTab 3' },
            accordion: { type:'accordion', label:'Accordion', question:'Accordion Title', answer:'Accordion content goes here.' },
            progress: { type:'progress', label:'Progress', value:75, color:'#6366f1' },
            timeline: { type:'timeline', label:'Timeline', title:'Event Title', date:'2024', description:'Event description.' },
            newsletter: { type:'newsletter', label:'Newsletter', title:'Subscribe to Newsletter', placeholder:'Enter your email', buttonText:'Subscribe' },
            countdown: { type:'countdown', label:'Countdown', targetDate:'2024-12-31' },
            alert: { type:'alert', label:'Alert', message:'This is an alert message.', variant:'info' },
            cta: { type:'cta', label:'CTA', title:'Ready to get started?', subtitle:'Join thousands of happy users', buttonText:'Start Now', buttonUrl:'#' },
            'social-links': { type:'social-links', label:'Social Links', platforms:'Facebook\nTwitter\nInstagram\nLinkedIn' },
            'share-buttons': { type:'share-buttons', label:'Share Buttons', platforms:'Facebook\nTwitter\nLinkedIn' },
            embed: { type:'embed', label:'Embed', url:'https://youtube.com/embed/...' },
            table: { type:'table', label:'Table', headers:'Name,Age,Email', rows:'John,30,john@email.com\nJane,25,jane@email.com' },
            badge: { type:'badge', label:'Badge', text:'Badge', variant:'primary' },
            chart: { type:'chart', label:'Chart', data:'20,35,25,45,30' },
            map: { type:'map', label:'Map', embedUrl:'https://maps.google.com/maps?q=...' },
            html: { type:'html', label:'Custom HTML', code:'<div>Custom HTML here</div>' },
            template: { type:'template', label:'Template', templateId:'' }
        };
        return Object.assign({ type: type }, defaults[type] || {});
    }

    function renderBlock(block, index) {
        const div = document.createElement('div');
        div.className = 'canvas-block';
        div.dataset.index = index;
        div.innerHTML = buildBlockHTML(block, index);
        attachBlockEvents(div, index);
        canvasBlocks.appendChild(div);
    }

    function buildBlockHTML(block, index) {
        const typeIcons = {container:'📦',columns:'🔲',grid:'⊞',section:'📄',divider:'➖',spacer:'↕️',heading:'',text:'📝',richtext:'📄',list:'📋',quote:'💬',code:'💻',image:'🖼️',gallery:'🖼️',video:'🎬',icon:'⭐',avatar:'👤','text-input':'📝',textarea:'📄',email:'📧',number:'🔢',password:'🔒',select:'📋',checkbox:'☑️',radio:'🔘',date:'📅',file:'📎',hidden:'👁️‍🗨️',submit:'🚀','product-card':'📦','product-grid':'⊞',price:'💰','add-to-cart':'🛒','product-badge':'🏷️',stars:'⭐','stock-status':'📊','buy-now':'⚡',hero:'🎯',team:'👥',testimonial:'💬',pricing:'💎',faq:'❓',stats:'📊',features:'✨','contact-card':'📞',button:'🔘',link:'🔗',tabs:'📑',accordion:'📂',progress:'📊',timeline:'📅',newsletter:'📬',countdown:'⏰',alert:'⚠️',cta:'🎯','social-links':'🔗','share-buttons':'📤',embed:'📺',table:'📋',badge:'🏷️',chart:'📈',map:'🗺️',html:'💻',template:'📄'};

        const preview = buildPreview(block);
        const selected = index === selectedIndex ? ' selected' : '';

        return `
            <div class="canvas-block-header">
                <div class="canvas-block-type">
                    <span class="drag-handle" title="Drag to reorder">⠿</span>
                    <span>${typeIcons[block.type] || '🧩'}</span>
                    <span>${escapeHtml(block.label)}</span>
                </div>
                <div class="canvas-block-actions">
                    <button type="button" class="canvas-block-btn move-up" ${index===0?'disabled':''}>↑</button>
                    <button type="button" class="canvas-block-btn move-down" ${index===blocks.length-1?'disabled':''}>↓</button>
                    <button type="button" class="canvas-block-btn duplicate">⧉</button>
                    <button type="button" class="canvas-block-btn delete">✕</button>
                </div>
            </div>
            <div class="canvas-block-preview">${preview}</div>`;
    }

    function buildPreview(block) {
        switch(block.type) {
            case 'container': return '<div style="padding:20px;border:1px dashed var(--gray-300);border-radius:8px;">Container</div>';
            case 'columns': return '<div style="display:grid;grid-template-columns:repeat('+(block.columns||3)+',1fr);gap:8px;">'+Array(block.columns||3).fill('<div style="height:60px;background:var(--gray-100);border-radius:6px;"></div>').join('')+'</div>';
            case 'grid': return '<div style="display:grid;grid-template-columns:repeat('+(block.columns||3)+',1fr);gap:8px;">'+Array(block.columns||3).fill('<div style="height:60px;background:var(--gray-100);border-radius:6px;"></div>').join('')+'</div>';
            case 'section': return '<div style="padding:40px;background:var(--gray-50);border-radius:8px;text-align:center;color:var(--gray-400);">Section</div>';
            case 'divider': return '<hr class="preview-divider">';
            case 'spacer': return '<div class="preview-spacer" style="height:'+(block.height||32)+'px;"></div>';
            case 'heading': return '<'+(block.level||'h2')+' class="preview-heading" style="text-align:'+(block.align||'left')+';">'+escapeHtml(block.content||'Heading')+'</'+(block.level||'h2')+'>';
            case 'text': return '<p class="preview-text">'+escapeHtml(block.content||'Text content')+'</p>';
            case 'richtext': return '<div>'+block.content+'</div>';
            case 'list':
                const items = (block.items||'Item 1\nItem 2').split('\n');
                const tag = block.ordered ? 'ol' : 'ul';
                return '<'+tag+' class="preview-list">'+items.map(function(i){return '<li>'+escapeHtml(i.trim())+'</li>';}).join('')+'</'+tag+'>';
            case 'quote': return '<blockquote class="preview-quote">'+escapeHtml(block.content||'Quote')+'<br><small>'+escapeHtml(block.author||'')+'</small></blockquote>';
            case 'code': return '<pre class="preview-code">'+escapeHtml(block.content||'code')+'</pre>';
            case 'image': return block.src ? '<img src="'+escapeHtml(block.src)+'" style="width:100%;height:200px;object-fit:cover;border-radius:8px;" alt="'+escapeHtml(block.alt||'')+'">' : '<div class="preview-image">🖼️</div>';
            case 'gallery': return '<div class="preview-gallery">'+Array(block.images||4).fill('<div class="preview-gallery-item">🖼️</div>').join('')+'</div>';
            case 'video': return '<div class="preview-video">▶️</div>';
            case 'icon': return '<div class="preview-icon-box"><div class="preview-icon-circle" style="background:'+(block.color||'var(--primary)')+';">'+(block.icon||'⭐')+'</div><div>'+escapeHtml(block.label||'Icon')+'</div></div>';
            case 'avatar': return '<div class="preview-team"><div class="preview-team-avatar">👤</div><div><div class="preview-team-name">'+escapeHtml(block.name||'User')+'</div><div class="preview-team-role">'+escapeHtml(block.role||'')+'</div></div></div>';
            case 'text-input': return '<div class="property-label">'+escapeHtml(block.label||'Text')+(block.required?' <span style="color:var(--danger)">*</span>':'')+'</div><input class="preview-input" placeholder="'+escapeHtml(block.placeholder||'')+'" disabled>';
            case 'textarea': return '<div class="property-label">'+escapeHtml(block.label||'Textarea')+'</div><textarea class="preview-textarea" placeholder="'+escapeHtml(block.placeholder||'')+'" disabled></textarea>';
            case 'email': return '<div class="property-label">'+escapeHtml(block.label||'Email')+'</div><input type="email" class="preview-input" placeholder="'+escapeHtml(block.placeholder||'')+'" disabled>';
            case 'number': return '<div class="property-label">'+escapeHtml(block.label||'Number')+'</div><input type="number" class="preview-input" placeholder="'+escapeHtml(block.placeholder||'')+'" disabled>';
            case 'password': return '<div class="property-label">'+escapeHtml(block.label||'Password')+'</div><input type="password" class="preview-input" placeholder="••••••••" disabled>';
            case 'select':
                const opts = (block.options||'Option 1\nOption 2').split('\n');
                return '<div class="property-label">'+escapeHtml(block.label||'Select')+'</div><select class="preview-select" disabled>'+opts.map(function(o){return '<option>'+escapeHtml(o.trim())+'</option>';}).join('')+'</select>';
            case 'checkbox': return '<div class="preview-checkbox"><input type="checkbox" disabled><span>'+escapeHtml(block.text||block.label||'Checkbox')+'</span></div>';
            case 'radio':
                const radios = (block.options||'Option 1\nOption 2').split('\n');
                return '<div class="property-label">'+escapeHtml(block.label||'Radio')+'</div>'+radios.map(function(o){return '<div class="preview-radio"><input type="radio" disabled><span>'+escapeHtml(o.trim())+'</span></div>';}).join('');
            case 'date': return '<div class="property-label">'+escapeHtml(block.label||'Date')+'</div><input type="date" class="preview-date" disabled>';
            case 'file': return '<div class="property-label">'+escapeHtml(block.label||'File')+'</div><div class="preview-file">📎 Click to upload or drag and drop</div>';
            case 'hidden': return '<div style="padding:8px;background:var(--gray-100);border-radius:4px;font-size:12px;color:var(--gray-400);">👁️🗨️ Hidden: '+escapeHtml(block.name||'')+'</div>';
            case 'submit':
                const variants = {primary:'var(--primary)',success:'var(--success)',warning:'var(--warning)',danger:'var(--danger)'};
                return '<button class="preview-button" style="background:'+(variants[block.variant]||'var(--primary)')+';">'+escapeHtml(block.text||'Submit')+'</button>';
            case 'product-card': return '<div class="preview-product-card"><div class="preview-product-img">📦</div><div class="preview-product-body"><div class="preview-product-name">'+escapeHtml(block.name||'Product')+'</div><div class="preview-product-price">$'+escapeHtml(block.price||'0')+'</div></div></div>';
            case 'product-grid': return '<div style="display:grid;grid-template-columns:repeat('+(block.columns||3)+',1fr);gap:12px;">'+Array(block.products||4).fill('<div class="preview-product-card"><div class="preview-product-img">📦</div><div class="preview-product-body"><div class="preview-product-name">Product</div><div class="preview-product-price">$99</div></div></div>').join('')+'</div>';
            case 'price': return '<div><span class="preview-price">'+escapeHtml(block.currency||'$')+escapeHtml(block.amount||'0')+'</span>'+(block.oldPrice?'<span class="preview-price-old">'+escapeHtml(block.currency||'$')+escapeHtml(block.oldPrice)+'</span>':'')+'</div>';
            case 'add-to-cart': return '<button class="preview-button" style="background:var(--success);">🛒 '+escapeHtml(block.text||'Add to Cart')+'</button>';
            case 'product-badge':
                const badgeVars = {sale:'preview-badge-sale',new:'preview-badge-new',hot:'preview-badge-hot'};
                return '<span class="preview-badge '+(badgeVars[block.variant]||'preview-badge-sale')+'">'+escapeHtml(block.text||'SALE')+'</span>';
            case 'stars': return '<div class="preview-stars">'+'★'.repeat(block.rating||4)+'☆'.repeat((block.max||5)-(block.rating||4))+'</div>';
            case 'stock-status': return '<span style="color:'+(block.status==='instock'?'var(--success)':'var(--danger)')+';">'+(block.status==='instock'?'✅':'❌')+' '+escapeHtml(block.text||'In Stock')+'</span>';
            case 'buy-now': return '<button class="preview-button" style="background:var(--warning);color:#000;">⚡ '+escapeHtml(block.text||'Buy Now')+'</button>';
            case 'hero': return '<div style="text-align:center;padding:40px 20px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;color:white;"><h2 style="margin:0 0 8px;">'+escapeHtml(block.title||'Hero')+'</h2><p style="margin:0 0 16px;opacity:0.9;">'+escapeHtml(block.subtitle||'')+'</p><span class="preview-button" style="background:white;color:#667eea;">'+escapeHtml(block.buttonText||'Get Started')+'</span></div>';
            case 'team': return '<div class="preview-team"><div class="preview-team-avatar">👤</div><div><div class="preview-team-name">'+escapeHtml(block.name||'Name')+'</div><div class="preview-team-role">'+escapeHtml(block.role||'')+'</div></div></div>';
            case 'testimonial': return '<div class="preview-testimonial"><div class="preview-testimonial-text">'+escapeHtml(block.text||'Testimonial')+'</div><div class="preview-testimonial-author">'+escapeHtml(block.author||'')+'</div></div>';
            case 'pricing':
                const feats = (block.features||'').split('\n');
                return '<div class="preview-pricing"><div class="preview-pricing-name">'+escapeHtml(block.name||'Plan')+'</div><div class="preview-pricing-price">'+escapeHtml(block.currency||'$')+escapeHtml(block.price||'0')+'<small style="font-size:14px;color:var(--gray-400);">'+escapeHtml(block.period||'')+'</small></div><ul class="preview-pricing-features">'+feats.map(function(f){return '<li>✓ '+escapeHtml(f.trim())+'</li>';}).join('')+'</ul><button class="preview-button">Choose Plan</button></div>';
            case 'faq': return '<div class="preview-faq-item"><div class="preview-faq-question">❓ '+escapeHtml(block.question||'Question?')+'</div><div class="preview-faq-answer">'+escapeHtml(block.answer||'Answer')+'</div></div>';
            case 'stats': return '<div style="text-align:center;padding:20px;"><div class="preview-stat-number">'+escapeHtml(block.number||'0')+escapeHtml(block.suffix||'')+'</div><div class="preview-stat-label">'+escapeHtml(block.label||'')+'</div></div>';
            case 'features': return '<div style="display:flex;gap:12px;align-items:start;padding:16px;border:1px solid var(--gray-200);border-radius:12px;"><div style="font-size:24px;">'+(block.icon||'✨')+'</div><div><div style="font-weight:600;">'+escapeHtml(block.title||'Feature')+'</div><div style="font-size:13px;color:var(--gray-400);">'+escapeHtml(block.description||'')+'</div></div></div>';
            case 'contact-card': return '<div class="preview-contact-card"><div class="preview-contact-icon">'+(block.icon||'📞')+'</div><div><div style="font-weight:600;">'+escapeHtml(block.title||'')+'</div><div style="font-size:13px;color:var(--gray-400);">'+escapeHtml(block.info||'')+'</div></div></div>';
            case 'button':
                const btnVars = {primary:'var(--primary)',success:'var(--success)',warning:'var(--warning)',danger:'var(--danger)',outline:'transparent;border:2px solid var(--primary);color:var(--primary)'};
                return '<a class="preview-button" style="background:'+(btnVars[block.variant]||'var(--primary)')+';" href="#">'+escapeHtml(block.text||'Button')+'</a>';
            case 'link': return '<a href="#" style="color:var(--primary);">'+escapeHtml(block.text||'Link')+' →</a>';
            case 'tabs':
                const tabs = (block.tabs||'Tab 1\nTab 2').split('\n');
                return '<div class="preview-tabs">'+tabs.map(function(t,i){return '<div class="preview-tab'+(i===0?' active':'')+'">'+escapeHtml(t.trim())+'</div>';}).join('')+'</div><div style="padding:16px;color:var(--gray-400);">Tab content here</div>';
            case 'accordion': return '<div class="preview-accordion-item"><div class="preview-accordion-header">▸ '+escapeHtml(block.question||'Question?')+'</div><div class="preview-accordion-body">'+escapeHtml(block.answer||'')+'</div></div>';
            case 'progress': return '<div><div class="property-label" style="font-size:13px;">'+escapeHtml(block.label||'Progress')+': '+(block.value||0)+'%</div><div class="preview-progress"><div class="preview-progress-bar" style="width:'+(block.value||0)+'%;background:'+(block.color||'var(--primary)')+';"></div></div></div>';
            case 'timeline': return '<div class="preview-timeline"><div class="preview-timeline-item"><div class="preview-timeline-dot"></div><div style="font-weight:600;">'+escapeHtml(block.title||'')+'</div><div style="font-size:12px;color:var(--gray-400);">'+escapeHtml(block.date||'')+'</div><div style="font-size:13px;color:var(--gray-600);">'+escapeHtml(block.description||'')+'</div></div></div>';
            case 'newsletter': return '<div class="preview-newsletter"><div class="preview-newsletter-title">'+escapeHtml(block.title||'Newsletter')+'</div><input class="preview-newsletter-input" placeholder="'+escapeHtml(block.placeholder||'Email')+'"><br><button class="preview-button" style="background:white;color:var(--primary);">'+escapeHtml(block.buttonText||'Subscribe')+'</button></div>';
            case 'countdown': return '<div class="preview-countdown"><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Days</div></div><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Hours</div></div><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Mins</div></div><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Secs</div></div></div>';
            case 'alert':
                const alertVars = {info:'preview-alert-info',success:'preview-alert-success',warning:'preview-alert-warning',error:'preview-alert-error'};
                return '<div class="preview-alert '+(alertVars[block.variant]||'preview-alert-info')+'">'+escapeHtml(block.message||'Alert')+'</div>';
            case 'cta': return '<div style="text-align:center;padding:40px 20px;background:var(--gray-50);border-radius:12px;"><h3 style="margin:0 0 8px;">'+escapeHtml(block.title||'CTA')+'</h3><p style="margin:0 0 16px;color:var(--gray-400);">'+escapeHtml(block.subtitle||'')+'</p><button class="preview-button">'+escapeHtml(block.buttonText||'Start Now')+'</button></div>';
            case 'social-links':
                const platforms = (block.platforms||'Facebook\nTwitter').split('\n');
                const icons = {'facebook':'📘','twitter':'🐦','instagram':'📷','linkedin':'💼','youtube':'▶️','tiktok':'🎵'};
                return '<div class="preview-social">'+platforms.map(function(p){return '<div class="preview-social-icon">'+(icons[p.trim().toLowerCase()]||'🔗')+'</div>';}).join('')+'</div>';
            case 'share-buttons': return '<div style="display:flex;gap:8px;">'+['📘','','💼'].map(function(i){return '<div class="preview-social-icon">'+i+'</div>';}).join('')+'</div>';
            case 'embed': return '<div style="height:200px;background:var(--gray-900);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:32px;">📺 Embed</div>';
            case 'table':
                const headers = (block.headers||'Col1,Col2').split(',');
                const rows = (block.rows||'').split('\n');
                return '<table class="preview-table"><thead><tr>'+headers.map(function(h){return '<th>'+escapeHtml(h.trim())+'</th>';}).join('')+'</tr></thead><tbody>'+rows.map(function(r){return '<tr>'+r.split(',').map(function(c){return '<td>'+escapeHtml(c.trim())+'</td>';}).join('')+'</tr>';}).join('')+'</tbody></table>';
            case 'badge':
                const badgeColors = {primary:'background:var(--primary);color:white;',success:'background:var(--success);color:white;',warning:'background:var(--warning);color:white;',danger:'background:var(--danger);color:white;',info:'background:var(--info);color:white;'};
                return '<span class="preview-badge-tag" style="'+(badgeColors[block.variant]||'background:var(--gray-100);color:var(--gray-600);')+'">'+escapeHtml(block.text||'Badge')+'</span>';
            case 'chart':
                const data = (block.data||'20,35,25').split(',');
                const max = Math.max.apply(null, data.map(Number));
                return '<div class="preview-chart">'+data.map(function(v){return '<div class="preview-chart-bar" style="height:'+((v/max)*100)+'%;"></div>';}).join('')+'</div>';
            case 'map': return '<div class="preview-map">🗺️</div>';
            case 'html': return '<div class="preview-code">'+escapeHtml(block.code||'<!-- HTML -->')+'</div>';
            case 'template': return '<div style="padding:20px;border:2px dashed var(--gray-300);border-radius:8px;text-align:center;color:var(--gray-400);">📄 Template: '+(block.templateId||'None')+'</div>';
            default: return '<div style="padding:20px;text-align:center;color:var(--gray-400);">Block: '+escapeHtml(block.type)+'</div>';
        }
    }

    function attachBlockEvents(div, index) {
        div.addEventListener('click', function(e) {
            if (!e.target.closest('.canvas-block-btn') && !e.target.closest('.drag-handle')) {
                selectBlock(index);
            }
        });

        const upBtn = div.querySelector('.move-up');
        const downBtn = div.querySelector('.move-down');
        const dupBtn = div.querySelector('.duplicate');
        const delBtn = div.querySelector('.delete');

        if (upBtn) upBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (index > 0) {
                saveState();
                [blocks[index], blocks[index-1]] = [blocks[index-1], blocks[index]];
                refreshAllBlocks();
                selectBlock(index - 1);
            }
        });

        if (downBtn) downBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (index < blocks.length - 1) {
                saveState();
                [blocks[index], blocks[index+1]] = [blocks[index+1], blocks[index]];
                refreshAllBlocks();
                selectBlock(index + 1);
            }
        });

        if (dupBtn) dupBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            saveState();
            const copy = Object.assign({}, blocks[index]);
            copy.label = copy.label + ' (copy)';
            blocks.splice(index + 1, 0, copy);
            refreshAllBlocks();
            selectBlock(index + 1);
        });

        if (delBtn) delBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (confirm('Delete this block?')) {
                saveState();
                blocks.splice(index, 1);
                if (selectedIndex === index) { selectedIndex = -1; hideProperties(); }
                else if (selectedIndex > index) selectedIndex--;
                refreshAllBlocks();
                updateEmptyState();
            }
        });
    }

    function refreshAllBlocks() {
        canvasBlocks.innerHTML = '';
        blocks.forEach(function(block, i) {
            renderBlock(block, i);
        });
        updateEmptyState();
    }

    function updateCanvasIndices() {
        canvasBlocks.querySelectorAll('.canvas-block').forEach(function(el, i) {
            el.dataset.index = i;
        });
    }

    function selectBlock(index) {
        selectedIndex = index;
        canvasBlocks.querySelectorAll('.canvas-block').forEach(function(el) {
            el.classList.toggle('selected', parseInt(el.dataset.index) === index);
        });
        showProperties();
        updateProperties();
    }

    function showProperties() {
        document.getElementById('props-empty').style.display = 'none';
        document.querySelectorAll('.props-tab').forEach(function(el) { el.style.display = ''; });
        document.getElementById('props-delete-section').style.display = '';
    }

    function hideProperties() {
        document.getElementById('props-empty').style.display = '';
        document.querySelectorAll('.props-tab').forEach(function(el) { el.style.display = 'none'; });
        document.getElementById('props-delete-section').style.display = 'none';
    }

    function updateProperties() {
        if (selectedIndex < 0) { hideProperties(); return; }
        const block = blocks[selectedIndex];
        document.getElementById('prop-label').value = block.label || '';
        document.getElementById('prop-desc').value = block.content || block.description || block.subtitle || block.text || '';
        document.getElementById('prop-url').value = block.url || block.buttonUrl || block.src || '';
        document.getElementById('prop-image').value = block.src || block.image || '';
        document.getElementById('prop-bg-color').value = block.bgColor || '#ffffff';
        document.getElementById('prop-text-color').value = block.color || block.textColor || '#1f2937';
        document.getElementById('prop-padding').value = block.padding || 'md';
        document.getElementById('prop-radius').value = block.radius || 'md';
        document.getElementById('prop-align').value = block.align || 'left';
        document.getElementById('prop-hidden').checked = block.hidden || false;
        document.getElementById('prop-class').value = block.cssClass || '';
        document.getElementById('prop-css').value = block.customCSS || '';
    }

    function updateEmptyState() {
        canvasEmpty.style.display = blocks.length === 0 ? '' : 'none';
    }

    // Property input sync
    ['prop-label','prop-desc','prop-url','prop-image','prop-padding','prop-radius','prop-align','prop-class','prop-css'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', syncProperty);
    });
    ['prop-bg-color','prop-text-color'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', syncProperty);
    });
    document.getElementById('prop-hidden').addEventListener('change', syncProperty);

    function syncProperty() {
        if (selectedIndex < 0) return;
        const block = blocks[selectedIndex];
        block.label = document.getElementById('prop-label').value;
        block.bgColor = document.getElementById('prop-bg-color').value;
        block.color = document.getElementById('prop-text-color').value;
        block.padding = document.getElementById('prop-padding').value;
        block.radius = document.getElementById('prop-radius').value;
        block.align = document.getElementById('prop-align').value;
        block.hidden = document.getElementById('prop-hidden').checked;
        block.cssClass = document.getElementById('prop-class').value;
        block.customCSS = document.getElementById('prop-css').value;

        // Sync content fields
        const desc = document.getElementById('prop-desc').value;
        if (block.content !== undefined) block.content = desc;
        if (block.description !== undefined) block.description = desc;
        if (block.subtitle !== undefined) block.subtitle = desc;
        if (block.text !== undefined && !block.content) block.text = desc;

        const url = document.getElementById('prop-url').value;
        if (block.url !== undefined) block.url = url;
        if (block.buttonUrl !== undefined) block.buttonUrl = url;
        if (block.src !== undefined && !block.image) block.src = url;

        const img = document.getElementById('prop-image').value;
        if (block.src !== undefined) block.src = img;
        if (block.image !== undefined) block.image = img;

        refreshAllBlocks();
        selectBlock(selectedIndex);
        saveState();
    }

    // Delete block
    document.getElementById('btn-delete-block').addEventListener('click', function() {
        if (selectedIndex >= 0 && confirm('Delete this block?')) {
            saveState();
            blocks.splice(selectedIndex, 1);
            selectedIndex = -1;
            hideProperties();
            refreshAllBlocks();
            updateEmptyState();
        }
    });

    // Undo/Redo
    function saveState() {
        undoStack.push(JSON.stringify(blocks));
        if (undoStack.length > 50) undoStack.shift();
        redoStack = [];
    }

    document.getElementById('btn-undo').addEventListener('click', function() {
        if (undoStack.length > 0) {
            redoStack.push(JSON.stringify(blocks));
            blocks = JSON.parse(undoStack.pop());
            selectedIndex = -1;
            hideProperties();
            refreshAllBlocks();
            updateEmptyState();
        }
    });

    document.getElementById('btn-redo').addEventListener('click', function() {
        if (redoStack.length > 0) {
            undoStack.push(JSON.stringify(blocks));
            blocks = JSON.parse(redoStack.pop());
            selectedIndex = -1;
            hideProperties();
            refreshAllBlocks();
            updateEmptyState();
        }
    });

    // Save form
    document.getElementById('btn-save').addEventListener('click', function() {
        document.getElementById('schema-json').value = JSON.stringify(blocks);
        document.getElementById('builder-form').submit();
    });

    document.getElementById('builder-form').addEventListener('submit', function() {
        document.getElementById('schema-json').value = JSON.stringify(blocks);
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // Render existing blocks on load (for update mode)
    if (blocks.length > 0) {
        blocks.forEach(function(block, i) {
            renderBlock(block, i);
        });
        updateEmptyState();
    }

    updateEmptyState();
});
</script>
