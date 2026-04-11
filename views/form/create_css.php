<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */

use yii\bootstrap5\Html;

$this->title = 'Visual Builder';

// Register dependencies
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/aos@2.3.1/dist/aos.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://unpkg.com/aos@2.3.1/dist/aos.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', ['position' => \yii\web\View::POS_HEAD]);
?>
<style>
/* ============================================
   PUCK EDITOR INSPIRED UI - ALL CSS
   ============================================ */
:root {
    --puck-color-primary: #5056f0;
    --puck-color-primary-hover: #4449d9;
    --puck-color-bg: #f3f3f6;
    --puck-color-surface: #ffffff;
    --puck-color-border: #e2e2e8;
    --puck-color-border-hover: #d2d2d8;
    --puck-color-text: #1a1a24;
    --puck-color-text-secondary: #6b6b7b;
    --puck-color-text-muted: #9e9eae;
    --puck-color-danger: #e5484d;
    --puck-color-success: #30a46c;
    --puck-color-warning: #f5a623;
    --puck-sidebar-width: 280px;
    --puck-right-sidebar-width: 320px;
    --puck-header-height: 56px;
    --puck-radius-sm: 4px;
    --puck-radius-md: 8px;
    --puck-radius-lg: 12px;
    --puck-shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
    --puck-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
    --puck-transition: 150ms ease;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    color: var(--puck-color-text);
    background: var(--puck-color-bg);
    -webkit-font-smoothing: antialiased;
}

/* ============ BUILDER WRAPPER ============ */
.builder-wrapper {
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
    background: var(--puck-color-bg);
}

/* ============ HEADER ============ */
.builder-toolbar {
    height: var(--puck-header-height);
    background: var(--puck-color-surface);
    border-bottom: 1px solid var(--puck-color-border);
    display: flex;
    align-items: center;
    padding: 0 16px;
    flex-shrink: 0;
    z-index: 100;
    gap: 12px;
}

.toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 200px;
}

.toolbar-logo {
    font-weight: 600;
    font-size: 15px;
    color: var(--puck-color-text);
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.toolbar-logo svg {
    width: 18px;
    height: 18px;
    color: var(--puck-color-primary);
}

.toolbar-divider {
    width: 1px;
    height: 24px;
    background: var(--puck-color-border);
    flex-shrink: 0;
}

.toolbar-center {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    justify-content: center;
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 200px;
    justify-content: flex-end;
}

/* Select & Inputs */
.toolbar-select {
    height: 36px;
    padding: 0 12px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md);
    background: var(--puck-color-surface);
    font-size: 13px;
    color: var(--puck-color-text);
    cursor: pointer;
    transition: border-color var(--puck-transition);
    outline: none;
}

.toolbar-select:hover {
    border-color: var(--puck-color-border-hover);
}

.toolbar-select:focus {
    border-color: var(--puck-color-primary);
    box-shadow: 0 0 0 2px rgba(80, 86, 240, 0.15);
}

/* Buttons */
.btn-toolbar {
    height: 36px;
    padding: 0 14px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md);
    background: var(--puck-color-surface);
    font-size: 13px;
    font-weight: 500;
    color: var(--puck-color-text-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all var(--puck-transition);
    white-space: nowrap;
    outline: none;
}

.btn-toolbar:hover {
    background: var(--puck-color-bg);
    border-color: var(--puck-color-border-hover);
    color: var(--puck-color-text);
}

.btn-toolbar:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.btn-toolbar-primary {
    background: var(--puck-color-primary);
    border-color: var(--puck-color-primary);
    color: white;
    font-weight: 600;
}

.btn-toolbar-primary:hover {
    background: var(--puck-color-primary-hover);
    border-color: var(--puck-color-primary-hover);
    color: white;
}

.btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md);
    background: var(--puck-color-surface);
    color: var(--puck-color-text-secondary);
    cursor: pointer;
    transition: all var(--puck-transition);
    outline: none;
}

.btn-icon:hover {
    background: var(--puck-color-bg);
    border-color: var(--puck-color-border-hover);
    color: var(--puck-color-text);
}

.btn-icon.active {
    background: var(--puck-color-primary);
    border-color: var(--puck-color-primary);
    color: white;
}

.device-group {
    display: flex;
    gap: 2px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-md);
    padding: 2px;
    border: 1px solid var(--puck-color-border);
}

.device-group .btn-icon {
    border: none;
    background: transparent;
    width: 32px;
    height: 32px;
}

.device-group .btn-icon.active {
    background: var(--puck-color-surface);
    color: var(--puck-color-primary);
    box-shadow: var(--puck-shadow-sm);
}

/* ============ MAIN LAYOUT ============ */
.builder-main {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ============ LEFT SIDEBAR ============ */
.builder-sidebar-left {
    width: var(--puck-sidebar-width);
    background: var(--puck-color-surface);
    border-right: 1px solid var(--puck-color-border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}

.sidebar-search {
    padding: 12px;
    border-bottom: 1px solid var(--puck-color-border);
}

.sidebar-search input {
    width: 100%;
    height: 36px;
    padding: 0 12px 0 36px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md);
    font-size: 13px;
    color: var(--puck-color-text);
    background: var(--puck-color-bg);
    outline: none;
    transition: all var(--puck-transition);
}

.sidebar-search input:focus {
    border-color: var(--puck-color-primary);
    background: var(--puck-color-surface);
    box-shadow: 0 0 0 2px rgba(80, 86, 240, 0.15);
}

.sidebar-search input::placeholder {
    color: var(--puck-color-text-muted);
}

.sidebar-search-wrapper {
    position: relative;
}

.sidebar-search-wrapper svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    color: var(--puck-color-text-muted);
    pointer-events: none;
}

.sidebar-categories {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.sidebar-categories::-webkit-scrollbar {
    width: 6px;
}

.sidebar-categories::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-categories::-webkit-scrollbar-thumb {
    background: var(--puck-color-border);
    border-radius: 3px;
}

.block-category {
    margin-bottom: 4px;
}

.block-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 600;
    color: var(--puck-color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    border-radius: var(--puck-radius-sm);
    transition: background var(--puck-transition);
    user-select: none;
}

.block-category-header:hover {
    background: var(--puck-color-bg);
}

.block-category-header svg {
    width: 14px;
    height: 14px;
    transition: transform var(--puck-transition);
}

.block-category.open .block-category-header svg {
    transform: rotate(180deg);
}

.block-category-items {
    display: none;
    padding: 4px 0;
}

.block-category.open .block-category-items {
    display: block;
}

.block-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    margin: 2px 0;
    border-radius: var(--puck-radius-md);
    cursor: grab;
    transition: all var(--puck-transition);
    user-select: none;
}

.block-item:hover {
    background: var(--puck-color-bg);
}

.block-item:active {
    cursor: grabbing;
}

.block-item-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    color: var(--puck-color-text-secondary);
    flex-shrink: 0;
    transition: all var(--puck-transition);
}

.block-item:hover .block-item-icon {
    background: var(--puck-color-surface);
    color: var(--puck-color-primary);
    box-shadow: var(--puck-shadow-sm);
}

.block-item-info {
    flex: 1;
    min-width: 0;
}

.block-item-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--puck-color-text);
}

.block-item-desc {
    font-size: 11px;
    color: var(--puck-color-text-muted);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.block-item-drag {
    color: var(--puck-color-text-muted);
    opacity: 0;
    transition: opacity var(--puck-transition);
}

.block-item:hover .block-item-drag {
    opacity: 1;
}

/* ============ CENTER CANVAS ============ */
.builder-canvas {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--puck-color-bg);
}

.canvas-scroll-area {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 32px;
    display: flex;
    justify-content: center;
}

.canvas-scroll-area::-webkit-scrollbar {
    width: 8px;
}

.canvas-scroll-area::-webkit-scrollbar-track {
    background: transparent;
}

.canvas-scroll-area::-webkit-scrollbar-thumb {
    background: var(--puck-color-border);
    border-radius: 4px;
}

.canvas-wrapper {
    width: 100%;
    max-width: 1200px;
    background: var(--puck-color-surface);
    border-radius: var(--puck-radius-lg);
    box-shadow: var(--puck-shadow-md);
    min-height: 600px;
    transition: max-width 0.3s ease;
    flex-shrink: 0;
}

.canvas-wrapper.tablet {
    max-width: 768px;
}

.canvas-wrapper.mobile {
    max-width: 375px;
}

.canvas-header {
    padding: 24px 32px;
    border-bottom: 1px solid var(--puck-color-border);
}

.canvas-form-name {
    font-size: 20px;
    font-weight: 600;
    border: none;
    outline: none;
    width: 100%;
    background: transparent;
    color: var(--puck-color-text);
    padding: 4px 0;
}

.canvas-form-name::placeholder {
    color: var(--puck-color-text-muted);
}

.canvas-body {
    padding: 32px;
    min-height: 400px;
}

.canvas-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    border: 2px dashed var(--puck-color-border);
    border-radius: var(--puck-radius-lg);
    color: var(--puck-color-text-muted);
    transition: all var(--puck-transition);
}

.canvas-empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.canvas-empty-text {
    font-size: 15px;
    font-weight: 500;
    color: var(--puck-color-text-secondary);
    margin-bottom: 4px;
}

.canvas-empty-hint {
    font-size: 13px;
    color: var(--puck-color-text-muted);
}

/* Canvas Blocks */
.canvas-block {
    position: relative;
    margin-bottom: 12px;
    border: 2px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md);
    transition: all var(--puck-transition);
    cursor: pointer;
    background: var(--puck-color-surface);
}

.canvas-block:hover {
    border-color: var(--puck-color-primary);
    box-shadow: 0 0 0 2px rgba(80, 86, 240, 0.1);
}

.canvas-block.selected {
    border-color: var(--puck-color-primary);
    box-shadow: 0 0 0 2px rgba(80, 86, 240, 0.15);
}

.canvas-block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--puck-color-bg);
    border-bottom: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md) var(--puck-radius-md) 0 0;
}

.canvas-block-type {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--puck-color-text-secondary);
}

.drag-handle {
    cursor: grab;
    color: var(--puck-color-text-muted);
    display: flex;
    align-items: center;
}

.drag-handle:active {
    cursor: grabbing;
}

.canvas-block-actions {
    display: flex;
    gap: 4px;
}

.canvas-block-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    background: var(--puck-color-surface);
    color: var(--puck-color-text-secondary);
    cursor: pointer;
    transition: all var(--puck-transition);
}

.canvas-block-btn:hover {
    background: var(--puck-color-bg);
    border-color: var(--puck-color-border-hover);
    color: var(--puck-color-text);
}

.canvas-block-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.canvas-block-preview {
    padding: 16px;
    min-height: 40px;
}

/* Block Preview Styles */
.preview-heading {
    margin: 0;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-text {
    margin: 0;
    color: var(--puck-color-text-secondary);
    line-height: 1.6;
}

.preview-divider {
    border: none;
    border-top: 1px solid var(--puck-color-border);
    margin: 8px 0;
}

.preview-spacer {
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
}

.preview-image {
    width: 100%;
    height: 120px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--puck-color-text-muted);
}

.preview-button {
    display: inline-block;
    padding: 10px 20px;
    background: var(--puck-color-primary);
    color: white;
    border-radius: var(--puck-radius-sm);
    font-size: 13px;
    font-weight: 500;
}

.preview-list, .preview-quote, .preview-code {
    padding: 12px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    color: var(--puck-color-text-secondary);
}

.preview-code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: 12px;
}

.preview-accordion-item {
    padding: 10px 14px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    margin-bottom: 8px;
    font-size: 13px;
    color: var(--puck-color-text);
}

.preview-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 1px solid var(--puck-color-border);
    margin-bottom: 12px;
}

.preview-tab {
    padding: 8px 16px;
    font-size: 13px;
    color: var(--puck-color-text-secondary);
    border-bottom: 2px solid transparent;
}

.preview-tab.active {
    color: var(--puck-color-primary);
    border-bottom-color: var(--puck-color-primary);
    font-weight: 500;
}

.preview-progress-bar {
    height: 8px;
    background: var(--puck-color-bg);
    border-radius: 4px;
    overflow: hidden;
}

.preview-progress-fill {
    height: 100%;
    background: var(--puck-color-primary);
    border-radius: 4px;
}

.preview-timeline-item {
    display: flex;
    gap: 12px;
    padding: 8px 0;
}

.preview-timeline-dot {
    width: 12px;
    height: 12px;
    background: var(--puck-color-primary);
    border-radius: 50%;
    margin-top: 4px;
    flex-shrink: 0;
}

.preview-timeline-content {
    flex: 1;
}

.preview-timeline-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-timeline-desc {
    font-size: 12px;
    color: var(--puck-color-text-muted);
    margin-top: 2px;
}

.preview-countdown {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.preview-countdown-item {
    padding: 12px 16px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    text-align: center;
    min-width: 60px;
}

.preview-countdown-number {
    font-size: 20px;
    font-weight: 700;
    color: var(--puck-color-text);
}

.preview-countdown-label {
    font-size: 10px;
    text-transform: uppercase;
    color: var(--puck-color-text-muted);
    margin-top: 2px;
}

.preview-alert {
    padding: 12px 16px;
    border-radius: var(--puck-radius-sm);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.preview-alert-info {
    background: #e8f0fe;
    color: #1a73e8;
}

.preview-alert-success {
    background: #e6f4ea;
    color: var(--puck-color-success);
}

.preview-alert-warning {
    background: #fef7e0;
    color: var(--puck-color-warning);
}

.preview-alert-error {
    background: #fce8e6;
    color: var(--puck-color-danger);
}

.preview-social-links {
    display: flex;
    gap: 12px;
}

.preview-social-link {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--puck-color-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--puck-color-text-secondary);
}

.preview-embed {
    padding: 40px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    text-align: center;
    color: var(--puck-color-text-muted);
}

.preview-form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--puck-color-text);
    margin-bottom: 6px;
}

.preview-input, .preview-textarea, .preview-select, .preview-date {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    font-size: 13px;
    background: var(--puck-color-bg);
    color: var(--puck-color-text);
    pointer-events: none;
}

.preview-textarea {
    min-height: 80px;
}

.preview-checkbox, .preview-radio {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--puck-color-text);
}

.preview-file {
    padding: 24px;
    border: 2px dashed var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    text-align: center;
    color: var(--puck-color-text-muted);
    background: var(--puck-color-bg);
}

.preview-price {
    font-size: 28px;
    font-weight: 700;
    color: var(--puck-color-text);
}

.preview-price-original {
    font-size: 16px;
    color: var(--puck-color-text-muted);
    text-decoration: line-through;
    margin-left: 8px;
}

.preview-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.preview-badge-sale {
    background: #fce8e6;
    color: var(--puck-color-danger);
}

.preview-badge-new {
    background: #e6f4ea;
    color: var(--puck-color-success);
}

.preview-stars {
    color: #fbbf24;
    font-size: 16px;
}

.preview-hero {
    padding: 32px;
    text-align: center;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
}

.preview-hero-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--puck-color-text);
    margin-bottom: 8px;
}

.preview-hero-subtitle {
    font-size: 14px;
    color: var(--puck-color-text-secondary);
}

.preview-team-member {
    text-align: center;
    padding: 16px;
}

.preview-team-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--puck-color-bg);
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--puck-color-text-muted);
}

.preview-team-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-team-role {
    font-size: 12px;
    color: var(--puck-color-text-muted);
}

.preview-testimonial {
    padding: 20px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    font-style: italic;
    color: var(--puck-color-text-secondary);
}

.preview-testimonial-author {
    margin-top: 12px;
    font-style: normal;
    font-size: 13px;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-pricing {
    padding: 24px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-md);
    text-align: center;
}

.preview-pricing-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--puck-color-text);
    margin-bottom: 8px;
}

.preview-pricing-amount {
    font-size: 32px;
    font-weight: 700;
    color: var(--puck-color-text);
}

.preview-pricing-period {
    font-size: 13px;
    color: var(--puck-color-text-muted);
}

.preview-pricing-features {
    list-style: none;
    padding: 0;
    margin: 16px 0;
    text-align: left;
}

.preview-pricing-features li {
    padding: 6px 0;
    font-size: 13px;
    color: var(--puck-color-text-secondary);
}

.preview-pricing-features li::before {
    content: "✓ ";
    color: var(--puck-color-success);
    font-weight: 600;
}

.preview-faq-item {
    padding: 12px 0;
    border-bottom: 1px solid var(--puck-color-border);
}

.preview-faq-question {
    font-size: 14px;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-faq-answer {
    font-size: 13px;
    color: var(--puck-color-text-secondary);
    margin-top: 4px;
}

.preview-stats {
    display: flex;
    justify-content: space-around;
    padding: 24px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
}

.preview-stat {
    text-align: center;
}

.preview-stat-number {
    font-size: 28px;
    font-weight: 700;
    color: var(--puck-color-text);
}

.preview-stat-label {
    font-size: 12px;
    color: var(--puck-color-text-muted);
    margin-top: 4px;
}

.preview-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 16px;
}

.preview-feature {
    text-align: center;
    padding: 16px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
}

.preview-feature-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.preview-feature-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-feature-desc {
    font-size: 12px;
    color: var(--puck-color-text-muted);
    margin-top: 4px;
}

.preview-contact-card {
    padding: 20px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
}

.preview-contact-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--puck-color-text);
}

.preview-contact-role {
    font-size: 13px;
    color: var(--puck-color-text-muted);
    margin-top: 2px;
}

.preview-contact-info {
    margin-top: 12px;
    font-size: 13px;
    color: var(--puck-color-text-secondary);
}

.preview-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.preview-gallery-item {
    aspect-ratio: 1;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--puck-color-text-muted);
}

.preview-video {
    padding: 40px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    text-align: center;
    color: var(--puck-color-text-muted);
}

.preview-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: var(--puck-color-bg);
    border-radius: var(--puck-radius-sm);
    color: var(--puck-color-text-secondary);
    font-size: 24px;
}

.preview-avatar {
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--puck-color-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--puck-color-text-muted);
}

.preview-avatar-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--puck-color-text);
}

.preview-avatar-role {
    font-size: 12px;
    color: var(--puck-color-text-muted);
}

/* ============ RIGHT SIDEBAR ============ */
.builder-sidebar-right {
    width: var(--puck-right-sidebar-width);
    background: var(--puck-color-surface);
    border-left: 1px solid var(--puck-color-border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}

.properties-header {
    padding: 16px;
    border-bottom: 1px solid var(--puck-color-border);
    font-size: 14px;
    font-weight: 600;
    color: var(--puck-color-text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.properties-tabs {
    display: flex;
    border-bottom: 1px solid var(--puck-color-border);
    padding: 0 8px;
}

.properties-tab {
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 500;
    color: var(--puck-color-text-muted);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all var(--puck-transition);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.properties-tab:hover {
    color: var(--puck-color-text-secondary);
}

.properties-tab.active {
    color: var(--puck-color-primary);
    border-bottom-color: var(--puck-color-primary);
}

.properties-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.properties-content::-webkit-scrollbar {
    width: 6px;
}

.properties-content::-webkit-scrollbar-track {
    background: transparent;
}

.properties-content::-webkit-scrollbar-thumb {
    background: var(--puck-color-border);
    border-radius: 3px;
}

.props-tab {
    display: none;
}

.props-tab.active {
    display: block;
}

.property-section {
    margin-bottom: 20px;
}

.property-section-title {
    font-size: 11px;
    font-weight: 600;
    color: var(--puck-color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.property-group {
    margin-bottom: 14px;
}

.property-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--puck-color-text-secondary);
    margin-bottom: 6px;
}

.property-input {
    width: 100%;
    height: 36px;
    padding: 0 10px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    font-size: 13px;
    color: var(--puck-color-text);
    background: var(--puck-color-surface);
    outline: none;
    transition: all var(--puck-transition);
}

.property-input:focus {
    border-color: var(--puck-color-primary);
    box-shadow: 0 0 0 2px rgba(80, 86, 240, 0.15);
}

.property-textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    font-size: 13px;
    color: var(--puck-color-text);
    background: var(--puck-color-surface);
    outline: none;
    resize: vertical;
    min-height: 80px;
    transition: all var(--puck-transition);
}

.property-textarea:focus {
    border-color: var(--puck-color-primary);
    box-shadow: 0 0 0 2px rgba(80, 86, 240, 0.15);
}

.property-select {
    width: 100%;
    height: 36px;
    padding: 0 10px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    font-size: 13px;
    color: var(--puck-color-text);
    background: var(--puck-color-surface);
    outline: none;
    cursor: pointer;
}

.property-color {
    width: 100%;
    height: 36px;
    padding: 2px;
    border: 1px solid var(--puck-color-border);
    border-radius: var(--puck-radius-sm);
    cursor: pointer;
    outline: none;
}

.property-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
}

.property-checkbox input {
    width: 16px;
    height: 16px;
    accent-color: var(--puck-color-primary);
}

.property-checkbox label {
    font-size: 13px;
    color: var(--puck-color-text);
    cursor: pointer;
}

#props-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: var(--puck-color-text-muted);
    text-align: center;
}

#props-empty svg {
    width: 48px;
    height: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

#props-empty p {
    font-size: 13px;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1200px) {
    :root {
        --puck-sidebar-width: 240px;
        --puck-right-sidebar-width: 280px;
    }
}

@media (max-width: 992px) {
    .builder-sidebar-right {
        display: none;
    }
}

@media (max-width: 768px) {
    .builder-sidebar-left {
        position: absolute;
        z-index: 50;
        height: calc(100vh - var(--puck-header-height));
        box-shadow: var(--puck-shadow-md);
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scroll animations */
.scroll-animate {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease-out;
}

.scroll-animate.in-view {
    opacity: 1;
    transform: translateY(0);
}

.scroll-animate-left {
    opacity: 0;
    transform: translateX(-30px);
    transition: all 0.6s ease-out;
}

.scroll-animate-left.in-view {
    opacity: 1;
    transform: translateX(0);
}

.scroll-animate-right {
    opacity: 0;
    transform: translateX(30px);
    transition: all 0.6s ease-out;
}

.scroll-animate-right.in-view {
    opacity: 1;
    transform: translateX(0);
}

.scroll-animate-scale {
    opacity: 0;
    transform: scale(0.9);
    transition: all 0.6s ease-out;
}

.scroll-animate-scale.in-view {
    opacity: 1;
    transform: scale(1);
}

.scroll-stagger .scroll-animate {
    transition-delay: calc(var(--i, 0) * 0.1s);
}
</style>
