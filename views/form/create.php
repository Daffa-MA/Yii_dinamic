<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */

use yii\bootstrap5\Html;

$this->title = 'Visual Website Builder';

// Register dependencies
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', ['position' => \yii\web\View::POS_HEAD]);
// Scroll animation libraries
$this->registerCssFile('https://unpkg.com/aos@2.3.1/dist/aos.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://unpkg.com/aos@2.3.1/dist/aos.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<style>
    :root {
            --primary: #0276ff;
            --primary-light: #3b95ff;
            --primary-dark: #005fcc;
            --success: #30a46c;
            --warning: #f5a623;
            --danger: #e5484d;
            --info: #3b82f6;
            --sidebar-width: 280px;
            --right-sidebar-width: 320px;
            --toolbar-height: 56px;
            --puck-bg: #f3f3f6;
            --puck-surface: #ffffff;
            --puck-border: #e2e2e8;
            --puck-border-hover: #d2d2d8;
            --puck-text: #1a1a24;
            --puck-text-secondary: #6b6b7b;
            --puck-text-muted: #9e9eae;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-600: #4b5563;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --puck-accent: rgba(2, 118, 255, 0.12);
            --puck-accent-hover: rgba(2, 118, 255, 0.20);
        }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    html, body {
        margin: 0;
        padding: 0;
    }

    .builder-wrapper {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 60px);
        overflow: hidden;
        background: var(--gray-100);
    }

    /* ============ TOP TOOLBAR ============ */
    .builder-toolbar {
        height: var(--toolbar-height);
        background: white;
        border-bottom: 1px solid var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        flex-shrink: 0;
        z-index: 1000;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .toolbar-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .toolbar-center {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toolbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toolbar-logo {
        font-weight: 700;
        font-size: 18px;
        color: var(--gray-900);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .toolbar-divider {
        width: 1px;
        height: 28px;
        background: var(--gray-100);
        margin: 0 4px;
    }

    .device-btn {
        padding: 8px 12px;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        color: var(--gray-400);
        transition: all 0.2s;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .device-btn i {
        font-size: 16px;
    }

    .device-btn:hover {
        background: var(--gray-50);
        color: var(--gray-600);
    }

    .device-btn.active {
        background: var(--primary);
        color: white;
    }

    .zoom-select {
        padding: 8px 12px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 13px;
        background: white;
        color: var(--gray-700);
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .zoom-select:hover {
        border-color: var(--gray-300);
    }

    .btn-toolbar {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid var(--gray-200);
        background: white;
        color: var(--gray-600);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .btn-toolbar i {
        font-size: 13px;
    }

    .btn-toolbar:hover {
        background: var(--gray-50);
        border-color: var(--gray-300);
        color: var(--gray-700);
    }

    .btn-toolbar-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-color: transparent;
        font-weight: 600;
    }

    .btn-toolbar-primary:hover {
        opacity: 0.95;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        transform: translateY(-1px);
    }

    /* ============ MAIN LAYOUT ============ */
    .builder-main {
        display: flex;
        flex: 1;
        overflow: auto;
        min-height: 0;
    }

    /* ============ LEFT SIDEBAR - BLOCKS ============ */
    .builder-sidebar-left {
        width: var(--sidebar-width);
        background: white;
        border-right: 1px solid var(--gray-100);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        flex-shrink: 0;
    }

    /* Modern scrollbar styling */
    .builder-sidebar-left::-webkit-scrollbar,
    .sidebar-categories::-webkit-scrollbar,
    .builder-canvas::-webkit-scrollbar,
    .properties-content::-webkit-scrollbar,
    .builder-sidebar-right::-webkit-scrollbar {
        width: 12px;
    }

    .builder-sidebar-left::-webkit-scrollbar-track,
    .sidebar-categories::-webkit-scrollbar-track,
    .builder-canvas::-webkit-scrollbar-track,
    .properties-content::-webkit-scrollbar-track,
    .builder-sidebar-right::-webkit-scrollbar-track {
        background: transparent;
    }

    .builder-sidebar-left::-webkit-scrollbar-thumb,
    .sidebar-categories::-webkit-scrollbar-thumb,
    .builder-canvas::-webkit-scrollbar-thumb,
    .properties-content::-webkit-scrollbar-thumb,
    .builder-sidebar-right::-webkit-scrollbar-thumb {
        background: #b4b9c4;
        border-radius: 6px;
        border: 3px solid transparent;
        background-clip: padding-box;
    }

    .builder-sidebar-left::-webkit-scrollbar-thumb:hover,
    .sidebar-categories::-webkit-scrollbar-thumb:hover,
    .builder-canvas::-webkit-scrollbar-thumb:hover,
    .properties-content::-webkit-scrollbar-thumb:hover,
    .builder-sidebar-right::-webkit-scrollbar-thumb:hover {
        background: #8b92a0;
        background-clip: padding-box;
    }

    .sidebar-search {
        padding: 14px 12px;
        border-bottom: 1px solid var(--gray-100);
    }

    .sidebar-search input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 13px;
        background: white;
        color: var(--gray-800);
        transition: all 0.2s;
    }

    .sidebar-search input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .sidebar-search input::placeholder {
        color: var(--gray-400);
    }

    .sidebar-categories {
        flex: 1;
        overflow-y: auto;
        padding: 6px 6px;
    }

    .block-category {
        margin-bottom: 8px;
    }

    .block-category-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s;
        user-select: none;
        background: var(--gray-50);
    }

    .block-category-header:hover {
        background: var(--gray-100);
    }

    .block-category-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--gray-600);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .block-category-arrow {
        font-size: 11px;
        color: var(--gray-400);
        transition: transform 0.2s;
    }

    .block-category.open .block-category-arrow {
        transform: rotate(90deg);
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
        padding: 10px 12px;
        margin: 4px 0;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        cursor: grab;
        transition: all 0.2s cubic-bezier(0.2, 0, 0.38, 0.9);
        user-select: none;
    }

    .block-item:hover {
            border-color: var(--primary);
            background: var(--puck-accent);
            transform: translateX(4px);
            box-shadow: 0 2px 8px var(--puck-accent-hover);
        }

    .block-item:active {
        cursor: grabbing;
    }

    .block-item-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gray-100);
        border-radius: 8px;
        font-size: 16px;
        flex-shrink: 0;
        transition: all 0.2s;
        color: var(--gray-600);
    }

    .block-item-icon i {
        font-size: 16px;
    }

    .block-item:hover .block-item-icon {
        background: var(--primary);
        color: white;
        transform: scale(1.1);
    }

    .block-item-info {
        flex: 1;
        min-width: 0;
    }

    .block-item-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-800);
    }

    .block-item-desc {
        font-size: 11px;
        color: var(--gray-500);
        margin-top: 2px;
    }

    .block-item-drag {
        color: var(--gray-300);
        font-size: 14px;
        transition: color 0.2s;
    }

    .block-item:hover .block-item-drag {
        color: var(--primary-light);
    }

    /* ============ CENTER CANVAS ============ */
    .builder-canvas {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        background: linear-gradient(to bottom, var(--gray-50), var(--gray-100));
        transition: all 0.2s ease;
    }

    .builder-canvas.drag-over {
        background: linear-gradient(to bottom, var(--puck-accent), rgba(2, 118, 255, 0.05));
    }

    .canvas-scroll-area {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 24px 20px;
        display: block;
        min-height: 0;
    }

    .canvas-scroll-area::-webkit-scrollbar {
        width: 8px;
    }

    .canvas-scroll-area::-webkit-scrollbar-track {
        background: transparent;
    }

    .canvas-scroll-area::-webkit-scrollbar-thumb {
        background: var(--gray-300);
        border-radius: 4px;
    }

    .canvas-scroll-area::-webkit-scrollbar-thumb:hover {
        background: var(--gray-400);
    }

    .canvas-wrapper {
        width: 100%;
        max-width: 1280px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
        min-height: 500px;
        transition: all 0.3s ease;
        overflow: visible;
        height: auto;
        margin: 0 auto;
    }

    .canvas-wrapper.tablet {
        max-width: 850px;
    }

    .canvas-wrapper.mobile {
        max-width: 480px;
    }

    .canvas-header {
        padding: 16px 24px;
        border-bottom: 1px solid var(--gray-100);
        background: white;
    }

    .canvas-form-name {
        font-size: 24px;
        font-weight: 700;
        border: none;
        outline: none;
        width: 100%;
        background: transparent;
        color: var(--gray-900);
        padding: 6px 0;
        transition: color 0.2s;
        letter-spacing: -0.5px;
    }

    .canvas-form-name:focus {
        color: var(--primary);
    }

    .canvas-form-name::placeholder {
        color: var(--gray-300);
    }

    .canvas-body {
        padding: 24px 20px;
        min-height: 400px;
        max-height: none;
        transition: all 0.2s cubic-bezier(0.2, 0, 0.38, 0.9);
        overflow-y: visible;
        height: auto;
    }

    .canvas-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 450px;
        border: 2px dashed var(--gray-200);
        border-radius: 12px;
        color: var(--gray-400);
        text-align: center;
        background: var(--gray-50);
        transition: all 0.2s cubic-bezier(0.2, 0, 0.38, 0.9);
    }

    .canvas-empty:hover {
        border-color: var(--gray-300);
        background: var(--gray-100);
    }

    .canvas-empty-icon {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .canvas-empty-icon i {
        color: var(--gray-300);
    }

    .canvas-empty-text {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--gray-600);
    }

    .canvas-empty-hint {
        font-size: 15px;
    }

    /* ============ CANVAS BLOCKS ============ */
    .canvas-block {
        position: relative;
        margin-bottom: 16px;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        transition: all 0.2s cubic-bezier(0.2, 0, 0.38, 0.9);
        cursor: pointer;
        background: white;
        overflow: hidden;
    }

    .canvas-block:hover {
        border-color: var(--primary-light);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.12);
        transform: translateY(-2px);
    }

    .canvas-block.selected {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1), 0 4px 12px rgba(99, 102, 241, 0.15);
    }

    .canvas-block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-100);
    }

    .canvas-block-type {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-600);
    }

    .canvas-block-type i {
        font-size: 13px;
    }

    .canvas-block-type .drag-handle i {
        opacity: 0.6;
    }

    .canvas-block-actions {
        display: flex;
        gap: 6px;
    }

    .canvas-block-btn {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--gray-200);
        border-radius: 6px;
        background: white;
        color: var(--gray-400);
        cursor: pointer;
        font-size: 12px;
        transition: all 0.15s;
    }

    .canvas-block-btn:hover {
        background: var(--gray-100);
        color: var(--gray-600);
        border-color: var(--gray-300);
    }

    .canvas-block-btn.delete:hover {
        background: #fee2e2;
        color: var(--danger);
        border-color: #fecaca;
    }

    .canvas-block-btn i {
        font-size: 12px;
    }

    .canvas-block-preview {
        padding: 28px 24px;
        min-height: 60px;
    }

    /* Block preview styles */
    .preview-heading {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0;
    }

    .preview-subheading {
        font-size: 18px;
        font-weight: 600;
        color: var(--gray-700);
        margin: 0;
    }

    .preview-text {
        color: var(--gray-600);
        margin: 0;
        line-height: 1.6;
    }

    .preview-image {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-400);
        font-size: 32px;
        border: 1px solid var(--gray-200);
    }

    .preview-video {
        width: 100%;
        height: 250px;
        background: var(--gray-900);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 48px;
        border: 1px solid var(--gray-800);
    }

    .preview-button {
        display: inline-block;
        padding: 12px 28px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }

    .preview-divider {
        border: none;
        border-top: 2px solid var(--gray-200);
        margin: 16px 0;
    }

    .preview-spacer {
        height: 32px;
        background: linear-gradient(90deg, var(--gray-50), var(--gray-100), var(--gray-50));
        border-radius: 4px;
    }

    .preview-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .preview-grid-item {
        height: 80px;
        background: linear-gradient(135deg, var(--gray-100), var(--gray-50));
        border-radius: 8px;
        border: 1px solid var(--gray-200);
    }

    .preview-product-card {
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.2s;
        background: white;
    }

    .preview-product-card:hover {
        border-color: var(--primary-light);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.12);
    }

    .preview-product-img {
        height: 120px;
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }

    .preview-product-body {
        padding: 12px;
    }

    .preview-product-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .preview-product-price {
        color: var(--primary);
        font-weight: 700;
        font-size: 16px;
    }

    .preview-price {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary);
    }

    .preview-price-old {
        font-size: 16px;
        color: var(--gray-400);
        text-decoration: line-through;
        margin-left: 8px;
    }

    .preview-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .preview-badge-sale {
        background: #fee2e2;
        color: var(--danger);
    }

    .preview-badge-new {
        background: #d1fae5;
        color: var(--success);
    }

    .preview-badge-hot {
        background: #fef3c7;
        color: var(--warning);
    }

    .preview-stars {
        color: #fbbf24;
        font-size: 18px;
    }

    .preview-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 14px;
        background: var(--gray-50);
        pointer-events: none;
    }

    .preview-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 14px;
        min-height: 80px;
        background: var(--gray-50);
        pointer-events: none;
    }

    .preview-select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 14px;
        background: var(--gray-50);
        pointer-events: none;
    }

    .preview-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .preview-radio {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .preview-date {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 14px;
        background: var(--gray-50);
        pointer-events: none;
    }

    .preview-file {
        width: 100%;
        padding: 24px;
        border: 2px dashed var(--gray-300);
        border-radius: 12px;
        text-align: center;
        color: var(--gray-500);
        background: var(--gray-50);
        transition: all 0.2s;
    }

    .preview-file:hover {
        border-color: var(--primary);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.02), rgba(99, 102, 241, 0.05));
    }

    .preview-gallery {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }

    .preview-gallery-item {
        height: 60px;
        background: var(--gray-100);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .preview-team {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
    }

    .preview-team-avatar {
        width: 60px;
        height: 60px;
        background: var(--gray-200);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .preview-team-name {
        font-weight: 600;
    }

    .preview-team-role {
        font-size: 13px;
        color: var(--gray-400);
    }

    .preview-testimonial {
        padding: 20px;
        background: var(--gray-50);
        border-radius: 12px;
        border-left: 4px solid var(--primary);
    }

    .preview-testimonial-text {
        font-style: italic;
        color: var(--gray-600);
        margin-bottom: 12px;
    }

    .preview-testimonial-author {
        font-weight: 600;
    }

    .preview-faq-item {
        padding: 16px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        margin-bottom: 8px;
    }

    .preview-faq-question {
        font-weight: 600;
        margin-bottom: 8px;
    }

    .preview-faq-answer {
        color: var(--gray-600);
        font-size: 14px;
    }

    .preview-pricing {
        padding: 24px;
        border: 2px solid var(--gray-200);
        border-radius: 16px;
        text-align: center;
    }

    .preview-pricing-name {
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 8px;
    }

    .preview-pricing-price {
        font-size: 36px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 16px;
    }

    .preview-pricing-features {
        list-style: none;
        padding: 0;
        margin-bottom: 20px;
    }

    .preview-pricing-features li {
        padding: 8px 0;
        color: var(--gray-600);
    }

    .preview-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        text-align: center;
    }

    .preview-stat-number {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary);
    }

    .preview-stat-label {
        font-size: 13px;
        color: var(--gray-400);
    }

    .preview-social {
        display: flex;
        gap: 12px;
    }

    .preview-social-icon {
        width: 40px;
        height: 40px;
        background: var(--gray-100);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .preview-newsletter {
        padding: 24px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 12px;
        color: white;
        text-align: center;
    }

    .preview-newsletter-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .preview-newsletter-input {
        width: 100%;
        max-width: 300px;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        margin: 12px 0;
    }

    .preview-countdown {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .preview-countdown-item {
        padding: 12px 16px;
        background: var(--gray-900);
        color: white;
        border-radius: 8px;
        text-align: center;
        min-width: 60px;
    }

    .preview-countdown-number {
        font-size: 24px;
        font-weight: 700;
    }

    .preview-countdown-label {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--gray-400);
    }

    .preview-progress {
        height: 8px;
        background: var(--gray-200);
        border-radius: 4px;
        overflow: hidden;
    }

    .preview-progress-bar {
        height: 100%;
        background: var(--primary);
        border-radius: 4px;
    }

    .preview-timeline {
        position: relative;
        padding-left: 24px;
    }

    .preview-timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--gray-200);
    }

    .preview-timeline-item {
        position: relative;
        margin-bottom: 16px;
        padding-left: 16px;
    }

    .preview-timeline-dot {
        position: absolute;
        left: -20px;
        top: 4px;
        width: 12px;
        height: 12px;
        background: var(--primary);
        border-radius: 50%;
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
    }

    .preview-table th,
    .preview-table td {
        padding: 10px 12px;
        border: 1px solid var(--gray-200);
        text-align: left;
    }

    .preview-table th {
        background: var(--gray-50);
        font-weight: 600;
    }

    .preview-alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 12px;
    }

    .preview-alert-info {
        background: #dbeafe;
        color: #1e40af;
        border-left: 4px solid var(--info);
    }

    .preview-alert-success {
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid var(--success);
    }

    .preview-alert-warning {
        background: #fef3c7;
        color: #92400e;
        border-left: 4px solid var(--warning);
    }

    .preview-alert-error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid var(--danger);
    }

    .preview-accordion-item {
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        margin-bottom: 8px;
        overflow: hidden;
    }

    .preview-accordion-header {
        padding: 12px 16px;
        background: var(--gray-50);
        font-weight: 600;
    }

    .preview-accordion-body {
        padding: 12px 16px;
        color: var(--gray-600);
    }

    .preview-tabs {
        display: flex;
        border-bottom: 2px solid var(--gray-200);
        margin-bottom: 16px;
    }

    .preview-tab {
        padding: 10px 20px;
        font-weight: 500;
        color: var(--gray-400);
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }

    .preview-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .preview-map {
        height: 200px;
        background: var(--gray-100);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
    }

    .preview-contact-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
    }

    .preview-contact-icon {
        width: 40px;
        height: 40px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .preview-badge-tag {
        display: inline-block;
        padding: 4px 10px;
        background: var(--gray-100);
        border-radius: 20px;
        font-size: 12px;
        color: var(--gray-600);
        margin: 2px;
    }

    .preview-icon-box {
        text-align: center;
        padding: 20px;
    }

    .preview-icon-circle {
        width: 56px;
        height: 56px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin: 0 auto 12px;
    }

    .preview-list {
        list-style: none;
        padding: 0;
    }

    .preview-list li {
        padding: 8px 0;
        border-bottom: 1px solid var(--gray-100);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .preview-quote {
        padding: 20px;
        border-left: 4px solid var(--primary);
        background: var(--gray-50);
        border-radius: 0 8px 8px 0;
        font-style: italic;
        color: var(--gray-600);
    }

    .preview-code {
        background: var(--gray-900);
        color: #a5f3fc;
        padding: 16px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 13px;
        overflow-x: auto;
    }

    .preview-chart {
        height: 150px;
        background: var(--gray-50);
        border-radius: 8px;
        display: flex;
        align-items: flex-end;
        padding: 16px;
        gap: 8px;
    }

    .preview-chart-bar {
        flex: 1;
        background: var(--primary);
        border-radius: 4px 4px 0 0;
    }

    /* ============ RIGHT SIDEBAR - PROPERTIES ============ */
    .builder-sidebar-right {
        width: 360px;
        background: white;
        border-left: 1px solid var(--gray-100);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        flex-shrink: 0;
    }

    .properties-header {
        padding: 24px 24px;
        border-bottom: 1px solid var(--gray-100);
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-900);
    }

    .properties-tabs {
        display: flex;
        border-bottom: 1px solid var(--gray-100);
        background: var(--gray-50);
    }

    .properties-tab {
        flex: 1;
        padding: 14px 12px;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-500);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .properties-tab:hover {
        color: var(--gray-700);
    }

    .properties-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: white;
    }

    .properties-content {
        flex: 1;
        overflow-y: auto;
        padding: 16px 18px 32px 18px;
    }

    .property-section {
        margin-bottom: 24px;
    }

    .property-section-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--gray-500);
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-100);
    }

    .property-field {
        margin-bottom: 18px;
    }

    .property-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 6px;
        text-transform: capitalize;
    }

    .property-input,
    .property-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 13px;
        transition: all 0.2s;
    }

    .property-input:focus,
    .property-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .property-select {
        background: white;
    }

    .property-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 13px;
        min-height: 60px;
        resize: vertical;
    }

    .property-textarea:focus {
        outline: none;
        border-color: var(--primary);
    }

    .property-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: var(--gray-50);
        border-radius: 8px;
        cursor: pointer;
    }

    .property-checkbox input {
        accent-color: var(--primary);
    }

    .property-hint {
        font-size: 11px;
        color: var(--gray-400);
        margin-top: 4px;
    }

    .property-color {
        width: 40px;
        height: 32px;
        border: 1px solid var(--gray-200);
        border-radius: 6px;
        cursor: pointer;
        padding: 2px;
    }

    /* ============ ANIMATIONS ============ */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
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

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes zoomIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .canvas-block {
        animation: slideIn 0.3s ease;
    }

    .canvas-block[data-aos] {
        opacity: 0;
    }

    .canvas-block[data-aos].aos-animate {
        animation: slideInUp 0.6s ease forwards;
    }

    /* Scroll animation effects */
    .scroll-animate {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .scroll-animate.in-view {
        opacity: 1;
        transform: translateY(0);
    }

    .scroll-animate-left {
        opacity: 0;
        transform: translateX(-40px);
        transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .scroll-animate-left.in-view {
        opacity: 1;
        transform: translateX(0);
    }

    .scroll-animate-right {
        opacity: 0;
        transform: translateX(40px);
        transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .scroll-animate-right.in-view {
        opacity: 1;
        transform: translateX(0);
    }

    .scroll-animate-scale {
        opacity: 0;
        transform: scale(0.95);
        transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .scroll-animate-scale.in-view {
        opacity: 1;
        transform: scale(1);
    }

    /* Parallax effect */
    .parallax-element {
        will-change: transform;
    }

    /* Stagger effect for multiple elements */
    .scroll-stagger .canvas-block:nth-child(1) {
        transition-delay: 0s;
    }

    .scroll-stagger .canvas-block:nth-child(2) {
        transition-delay: 0.1s;
    }

    .scroll-stagger .canvas-block:nth-child(3) {
        transition-delay: 0.2s;
    }

    .scroll-stagger .canvas-block:nth-child(4) {
        transition-delay: 0.3s;
    }

    .scroll-stagger .canvas-block:nth-child(5) {
        transition-delay: 0.4s;
    }

    .scroll-stagger .canvas-block:nth-child(n+6) {
        transition-delay: 0.5s;
    }

    /* Canvas wrapper smooth scroll */
    .builder-canvas {
        scroll-behavior: smooth;
    }

    /* Enhanced canvas block animations on scroll */
    .canvas-block {
        transform-origin: center;
    }

    .canvas-block.fade-in-entrance {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .canvas-block.slide-in-entrance {
        animation: slideInLeft 0.6s ease-out forwards;
    }

    .canvas-block.scale-in-entrance {
        animation: scaleIn 0.6s ease-out forwards;
    }

    /* Parallax container */
    .parallax-container {
        perspective: 1000px;
        overflow: hidden;
    }

    .parallax-item {
        will-change: transform;
        transform: translateZ(0);
    }

    .sortable-ghost {
        opacity: 0.4;
        border: 2px dashed var(--primary) !important;
    }

    /* Mobile smooth scrolling */
    @supports (scroll-behavior: smooth) {
        .builder-canvas {
            scroll-behavior: smooth;
        }
    }

    /* Optimize animations for reduced motion preference */
    @media (prefers-reduced-motion: reduce) {

        .scroll-animate,
        .scroll-animate-left,
        .scroll-animate-right,
        .scroll-animate-scale,
        .canvas-block {
            animation: none !important;
            transition: none !important;
            opacity: 1 !important;
            transform: none !important;
        }
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 1200px) {
        :root {
            --sidebar-width: 260px;
        }

        .builder-sidebar-right {
            width: 280px;
        }
    }

    @media (max-width: 992px) {
        .builder-sidebar-right {
            display: none;
        }
    }
</style>

<div class="builder-wrapper">
    <!-- TOOLBAR -->
    <div class="builder-toolbar">
        <div class="toolbar-left">
            <span class="toolbar-logo"><i class="fas fa-bolt"></i> Visual Builder</span>
            <div class="toolbar-divider"></div>
            <span style="font-size:14px;color:var(--gray-600);"><?= $model->isNewRecord ? 'New Page' : Html::encode($model->name) ?></span>
        </div>

        <div class="toolbar-center">
            <!-- Database Table Selection -->
            <?php
            $tables = \app\models\DbTable::find()
                ->where(['user_id' => Yii::$app->user->id])
                ->orderBy(['id' => SORT_ASC])
                ->asArray()
                ->all();
            $hasTable = !empty($tables);
            ?>
            <select id="table-selector" class="zoom-select" title="Select table to auto-generate form fields" <?= !$hasTable ? 'disabled' : '' ?>>
                <option value=""><i class="fas fa-database" style="margin-right:6px;"></i>Select a table...</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?= $table['id'] ?>" data-name="<?= Html::encode($table['name']) ?>" <?= $model->table_id == $table['id'] ? 'selected' : '' ?>>
                        <i class="fas fa-table" style="margin-right:6px;"></i><?= Html::encode($table['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($hasTable): ?>
                <button class="btn-toolbar" id="btn-auto-generate" title="Auto-generate form fields from table">
                    <i class="fas fa-sliders-h"></i> Auto-Generate
                </button>
            <?php else: ?>
                <button class="btn-toolbar" id="btn-auto-generate" disabled title="No tables available - Create a table first">
                    <i class="fas fa-ban"></i> No Tables
                </button>
            <?php endif; ?>

            <div class="toolbar-divider"></div>
            <button class="device-btn active" data-device="desktop" title="Desktop"><i class="fas fa-desktop"></i></button>
            <button class="device-btn" data-device="tablet" title="Tablet"><i class="fas fa-tablet-alt"></i></button>
            <button class="device-btn" data-device="mobile" title="Mobile"><i class="fas fa-mobile-alt"></i></button>
            <div class="toolbar-divider"></div>
            <select class="zoom-select" id="zoom-select">
                <option value="100">100%</option>
                <option value="75">75%</option>
                <option value="50">50%</option>
            </select>
        </div>

        <div class="toolbar-right">
            <button class="btn-toolbar" id="btn-undo" title="Undo"><i class="fas fa-undo"></i></button>
            <button class="btn-toolbar" id="btn-redo" title="Redo"><i class="fas fa-redo"></i></button>
            <div class="toolbar-divider"></div>
            <?= Html::a('<i class="fas fa-eye"></i> Preview', ['form/render', 'id' => $model->id], ['class' => 'btn-toolbar', 'id' => 'btn-preview', 'style' => $model->isNewRecord ? 'display:none' : '']) ?>
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
                        <span class="block-category-title"><i class="fas fa-layer-group"></i> Layout</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="container">
                            <div class="block-item-icon"><i class="fas fa-box"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Container</div>
                                <div class="block-item-desc">Wrapper container</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="columns">
                            <div class="block-item-icon"><i class="fas fa-columns"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Columns</div>
                                <div class="block-item-desc">Multi-column layout</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="grid">
                            <div class="block-item-icon"><i class="fas fa-grip"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Grid</div>
                                <div class="block-item-desc">Grid layout</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="section">
                            <div class="block-item-icon"><i class="fas fa-file"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Section</div>
                                <div class="block-item-desc">Full-width section</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="divider">
                            <div class="block-item-icon"><i class="fas fa-minus"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Divider</div>
                                <div class="block-item-desc">Horizontal line</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="spacer">
                            <div class="block-item-icon"><i class="fas fa-arrows-alt-v"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Spacer</div>
                                <div class="block-item-desc">Empty space</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- TYPOGRAPHY -->
                <div class="block-category open" data-category="typography">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-type"></i> Typography</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="heading">
                            <div class="block-item-icon"><i class="fas fa-heading"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Heading</div>
                                <div class="block-item-desc">H1-H6 heading</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="text">
                            <div class="block-item-icon"><i class="fas fa-font"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Text</div>
                                <div class="block-item-desc">Paragraph text</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="richtext">
                            <div class="block-item-icon"><i class="fas fa-file-alt"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Rich Text</div>
                                <div class="block-item-desc">Formatted text</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="list">
                            <div class="block-item-icon"><i class="fas fa-list-ul"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">List</div>
                                <div class="block-item-desc">Bullet/numbered list</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="quote">
                            <div class="block-item-icon"><i class="fas fa-quote-left"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Quote</div>
                                <div class="block-item-desc">Blockquote</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="code">
                            <div class="block-item-icon"><i class="fas fa-code"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Code</div>
                                <div class="block-item-desc">Code block</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- MEDIA -->
                <div class="block-category open" data-category="media">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-images"></i> Media</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="image">
                            <div class="block-item-icon"><i class="fas fa-image"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Image</div>
                                <div class="block-item-desc">Single image</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="gallery">
                            <div class="block-item-icon"><i class="fas fa-images"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Gallery</div>
                                <div class="block-item-desc">Image gallery</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="video">
                            <div class="block-item-icon"><i class="fas fa-video"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Video</div>
                                <div class="block-item-desc">Video embed</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="icon">
                            <div class="block-item-icon"><i class="fas fa-star"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Icon</div>
                                <div class="block-item-desc">Icon element</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="avatar">
                            <div class="block-item-icon"><i class="fas fa-user-circle"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Avatar</div>
                                <div class="block-item-desc">User avatar</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- FORM ELEMENTS -->
                <div class="block-category open" data-category="forms">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-list"></i> Form Elements</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="text-input">
                            <div class="block-item-icon"><i class="fas fa-keyboard"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Text Input</div>
                                <div class="block-item-desc">Single line text</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="textarea">
                            <div class="block-item-icon"><i class="fas fa-align-left"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Textarea</div>
                                <div class="block-item-desc">Multi-line text</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="email">
                            <div class="block-item-icon"><i class="fas fa-envelope"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Email</div>
                                <div class="block-item-desc">Email input</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="number">
                            <div class="block-item-icon"><i class="fas fa-hashtag"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Number</div>
                                <div class="block-item-desc">Number input</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="password">
                            <div class="block-item-icon"><i class="fas fa-lock"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Password</div>
                                <div class="block-item-desc">Password field</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="select">
                            <div class="block-item-icon"><i class="fas fa-list"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Dropdown</div>
                                <div class="block-item-desc">Select dropdown</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="checkbox">
                            <div class="block-item-icon"><i class="fas fa-check-square"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Checkbox</div>
                                <div class="block-item-desc">Checkbox input</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="radio">
                            <div class="block-item-icon"><i class="fas fa-circle"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Radio</div>
                                <div class="block-item-desc">Radio buttons</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="date">
                            <div class="block-item-icon"><i class="fas fa-calendar"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Date</div>
                                <div class="block-item-desc">Date picker</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="file">
                            <div class="block-item-icon"><i class="fas fa-paperclip"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">File Upload</div>
                                <div class="block-item-desc">File upload field</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="hidden">
                            <div class="block-item-icon"><i class="fas fa-eye-slash"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Hidden</div>
                                <div class="block-item-desc">Hidden field</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="submit">
                            <div class="block-item-icon"><i class="fas fa-check"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Submit Button</div>
                                <div class="block-item-desc">Form submit button</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- E-COMMERCE -->
                <div class="block-category open" data-category="ecommerce">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-shopping-bag"></i> E-Commerce</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="product-card">
                            <div class="block-item-icon"><i class="fas fa-shopping-bag"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Product Card</div>
                                <div class="block-item-desc">Single product</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="product-grid">
                            <div class="block-item-icon"><i class="fas fa-th"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Product Grid</div>
                                <div class="block-item-desc">Product grid</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="price">
                            <div class="block-item-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Price</div>
                                <div class="block-item-desc">Price display</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="add-to-cart">
                            <div class="block-item-icon"><i class="fas fa-shopping-cart"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Add to Cart</div>
                                <div class="block-item-desc">Cart button</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="product-badge">
                            <div class="block-item-icon"><i class="fas fa-tag"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Badge</div>
                                <div class="block-item-desc">Sale/New/Hot badge</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="stars">
                            <div class="block-item-icon"><i class="fas fa-star"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Star Rating</div>
                                <div class="block-item-desc">Star rating</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="stock-status">
                            <div class="block-item-icon"><i class="fas fa-box"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Stock Status</div>
                                <div class="block-item-desc">In/Out of stock</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="buy-now">
                            <div class="block-item-icon"><i class="fas fa-bolt"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Buy Now</div>
                                <div class="block-item-desc">Buy now button</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- BUSINESS -->
                <div class="block-category open" data-category="business">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-briefcase"></i> Business</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="hero">
                            <div class="block-item-icon"><i class="fas fa-bullseye"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Hero Section</div>
                                <div class="block-item-desc">Hero banner</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="team">
                            <div class="block-item-icon"><i class="fas fa-users"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Team Member</div>
                                <div class="block-item-desc">Team card</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="testimonial">
                            <div class="block-item-icon"><i class="fas fa-comments"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Testimonial</div>
                                <div class="block-item-desc">Customer review</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="pricing">
                            <div class="block-item-icon"><i class="fas fa-gem"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Pricing Card</div>
                                <div class="block-item-desc">Pricing plan</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="faq">
                            <div class="block-item-icon"><i class="fas fa-question-circle"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">FAQ</div>
                                <div class="block-item-desc">FAQ item</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="stats">
                            <div class="block-item-icon"><i class="fas fa-chart-bar"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Stats Counter</div>
                                <div class="block-item-desc">Number counter</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="features">
                            <div class="block-item-icon"><i class="fas fa-star"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Features</div>
                                <div class="block-item-desc">Feature list</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="contact-card">
                            <div class="block-item-icon"><i class="fas fa-phone"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Contact Card</div>
                                <div class="block-item-desc">Contact info</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- INTERACTIVE -->
                <div class="block-category open" data-category="interactive">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-bolt"></i> Interactive</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="button">
                            <div class="block-item-icon"><i class="fas fa-circle"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Button</div>
                                <div class="block-item-desc">CTA button</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="link">
                            <div class="block-item-icon"><i class="fas fa-link"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Link</div>
                                <div class="block-item-desc">Text link</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="tabs">
                            <div class="block-item-icon"><i class="fas fa-folder"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Tabs</div>
                                <div class="block-item-desc">Tab container</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="accordion">
                            <div class="block-item-icon"><i class="fas fa-folder-open"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Accordion</div>
                                <div class="block-item-desc">Collapsible content</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="progress">
                            <div class="block-item-icon"><i class="fas fa-chart-bar"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Progress Bar</div>
                                <div class="block-item-desc">Progress indicator</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="timeline">
                            <div class="block-item-icon"><i class="fas fa-stream"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Timeline</div>
                                <div class="block-item-desc">Timeline item</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- MARKETING -->
                <div class="block-category open" data-category="marketing">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-bullhorn"></i> Marketing</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="newsletter">
                            <div class="block-item-icon"><i class="fas fa-envelope"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Newsletter</div>
                                <div class="block-item-desc">Email signup</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="countdown">
                            <div class="block-item-icon"><i class="fas fa-hourglass-end"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Countdown</div>
                                <div class="block-item-desc">Countdown timer</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="alert">
                            <div class="block-item-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Alert</div>
                                <div class="block-item-desc">Notification banner</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="cta">
                            <div class="block-item-icon"><i class="fas fa-bullseye"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Call to Action</div>
                                <div class="block-item-desc">CTA section</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- SOCIAL -->
                <div class="block-category open" data-category="social">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-share-alt"></i> Social</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="social-links">
                            <div class="block-item-icon"><i class="fas fa-share-alt"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Social Links</div>
                                <div class="block-item-desc">Social media icons</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="share-buttons">
                            <div class="block-item-icon"><i class="fas fa-share-nodes"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Share Buttons</div>
                                <div class="block-item-desc">Social share</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="embed">
                            <div class="block-item-icon"><i class="fas fa-tv"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Embed</div>
                                <div class="block-item-desc">YouTube/Instagram</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- DATA -->
                <div class="block-category open" data-category="data">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-chart-bar"></i> Data</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="table">
                            <div class="block-item-icon"><i class="fas fa-table"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Table</div>
                                <div class="block-item-desc">Data table</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="badge">
                            <div class="block-item-icon"><i class="fas fa-tag"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Badge/Tag</div>
                                <div class="block-item-desc">Label badge</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="chart">
                            <div class="block-item-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Chart</div>
                                <div class="block-item-desc">Bar chart placeholder</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="map">
                            <div class="block-item-icon"><i class="fas fa-map"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Map</div>
                                <div class="block-item-desc">Google Map embed</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

                <!-- ADVANCED -->
                <div class="block-category open" data-category="advanced">
                    <div class="block-category-header">
                        <span class="block-category-title"><i class="fas fa-tools"></i> Advanced</span>
                        <span class="block-category-arrow">▶</span>
                    </div>
                    <div class="block-category-items">
                        <div class="block-item" draggable="true" data-type="html">
                            <div class="block-item-icon"><i class="fas fa-code"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Custom HTML</div>
                                <div class="block-item-desc">Raw HTML code</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                        <div class="block-item" draggable="true" data-type="template">
                            <div class="block-item-icon"><i class="fas fa-file-contract"></i></div>
                            <div class="block-item-info">
                                <div class="block-item-name">Template</div>
                                <div class="block-item-desc">Reusable template</div>
                            </div><span class="block-item-drag"><i class="fas fa-ellipsis-v"></i></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- CENTER CANVAS -->
        <div class="builder-canvas">
            <div class="canvas-scroll-area">
                <div class="canvas-wrapper" id="canvas-wrapper">
                    <?= Html::beginForm(['form/' . ($model->isNewRecord ? 'create' : 'update'), 'id' => $model->isNewRecord ? null : $model->id], 'post', ['id' => 'builder-form']) ?>

                    <div class="canvas-header">
                        <input type="text" class="canvas-form-name" name="Form[name]" placeholder="Page title..." value="<?= Html::encode($model->name) ?>">
                    </div>

                    <?= Html::hiddenInput('FormSchema', $model->isNewRecord ? '[]' : Html::encode($model->schema_json), ['id' => 'schema-json']) ?>
                    <?= Html::hiddenInput('Form[table_id]', $model->table_id, ['id' => 'table-id']) ?>

                    <div class="canvas-body" id="canvas-body">
                        <div class="canvas-empty" id="canvas-empty">
                            <div class="canvas-empty-icon">🧩</div>
                            <div class="canvas-empty-text">Drag & Drop Blocks Here</div>
                            <div class="canvas-empty-hint">or click blocks from the left panel to add them</div>
                        </div>
                        <div id="canvas-blocks" style="min-height:50px;"></div>
                        <!-- Padding spacer to prevent blocks from being cut off -->
                        <div style="height: 80px; flex-shrink: 0;"></div>
                    </div>

                    <?= Html::endForm() ?>
                </div>
            </div>

            <!-- Bottom Action Bar (outside scroll area, always visible) -->
            <div class="canvas-action-bar">
                <div style="display:flex;justify-content:flex-end;gap:12px;max-width:1280px;margin:0 auto;padding:0 40px;">
                    <?= Html::a('Cancel', ['form/index'], ['class' => 'btn-toolbar']) ?>
                    <button type="submit" formid="builder-form" class="btn-toolbar btn-toolbar-primary"><i class="fas fa-save"></i> <?= $model->isNewRecord ? 'Publish Page' : 'Update Page' ?></button>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDEBAR - PROPERTIES -->
        <div class="builder-sidebar-right">
            <div class="properties-header"><i class="fas fa-sliders-h"></i> Properties</div>
            <div class="properties-tabs">
                <div class="properties-tab active" data-tab="content">Content</div>
                <div class="properties-tab" data-tab="style">Style</div>
                <div class="properties-tab" data-tab="advanced">Advanced</div>
                <div class="properties-tab" data-tab="json">JSON</div>
            </div>
            <div class="properties-content">
                <div id="props-empty" class="text-center text-muted py-5">
                    <div style="font-size:48px;margin-bottom:12px;"><i class="fas fa-crosshairs"></i></div>
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

                <div id="props-json" class="props-tab" style="display:none;">
                    <div class="property-section">
                        <div class="property-section-title">Block JSON (Full Control)</div>
                        <div style="font-size:11px;color:var(--gray-500);margin-bottom:12px;">Edit block as JSON for complete flexibility!</div><textarea id="prop-json" style="font-family:monospace;font-size:11px;height:280px;border:1px solid var(--gray-300);padding:12px;background:var(--gray-50);width:100%;"></textarea><button id="btn-apply-json" class="btn-toolbar" style="margin-top:12px;width:100%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;border:none;font-weight:600;">⚡ Apply JSON</button>
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
        // ===== SAFE ELEMENT CHECK =====
        const elementsToCheck = {
            'canvas-blocks': document.getElementById('canvas-blocks'),
            'canvas-empty': document.getElementById('canvas-empty'),
            'schema-json': document.getElementById('schema-json'),
            'table-selector': document.getElementById('table-selector'),
            'table-id': document.getElementById('table-id'),
            'btn-auto-generate': document.getElementById('btn-auto-generate'),
            'block-search': document.getElementById('block-search'),
            'builder-form': document.getElementById('builder-form'),
            'btn-save': document.getElementById('btn-save'),
            'canvas-wrapper': document.getElementById('canvas-wrapper')
        };

        console.log('Elements check:', Object.entries(elementsToCheck).map(([k, v]) => k + ': ' + (v ? 'Found' : 'MISSING')).join(', '));

        // ===== CORE SCRIPT =====
        let blocks = [];
        let selectedIndex = -1;
        let sortableInstance = null;
        let undoStack = [];
        let redoStack = [];

        const canvasBlocks = elementsToCheck['canvas-blocks'];
        const canvasEmpty = elementsToCheck['canvas-empty'];
        const schemaJson = elementsToCheck['schema-json'];
        const tableSelector = elementsToCheck['table-selector'];
        const tableIdInput = elementsToCheck['table-id'];
        const btnAutoGenerate = elementsToCheck['btn-auto-generate'];

        if (!canvasBlocks || !schemaJson) {
            console.error('FATAL: Required elements missing!');
            return;
        }

        // ===== AUTO-SELECT FIRST TABLE IF EXISTS =====
        if (tableSelector && tableSelector.options.length > 1) {
            // If no table is selected and model has no table_id, auto-select first table
            if (!tableSelector.value) {
                // Try to find "toko" table first (it has columns)
                let selectedIdx = -1;
                for (let i = 1; i < tableSelector.options.length; i++) {
                    const optionText = tableSelector.options[i].text;
                    if (optionText.includes('toko')) {
                        selectedIdx = i;
                        break;
                    }
                }

                // If not found, select first available table
                if (selectedIdx === -1) {
                    for (let i = 1; i < tableSelector.options.length; i++) {
                        if (tableSelector.options[i].value) {
                            selectedIdx = i;
                            break;
                        }
                    }
                }

                if (selectedIdx > 0) {
                    tableSelector.selectedIndex = selectedIdx;
                    console.log('Auto-selected table: ' + tableSelector.options[selectedIdx].text + ' (ID: ' + tableSelector.options[selectedIdx].value + ')');
                }
            }
        }

        // Initialize Sortable for canvas blocks ONLY
        if (canvasBlocks) {
            sortableInstance = new Sortable(canvasBlocks, {
                animation: 200,
                ghostClass: 'sortable-ghost',
                handle: '.drag-handle',
                group: 'blocks',
                onEnd: function(evt) {
                    const newOrder = [];
                    canvasBlocks.querySelectorAll('.canvas-block').forEach(function(el, i) {
                        const idx = parseInt(el.dataset.index);
                        if (idx >= 0 && idx < blocks.length) {
                            newOrder.push(blocks[idx]);
                        }
                    });
                    blocks = newOrder;
                    updateCanvasIndices();
                    saveState();
                }
            });
        }

        // Drag from sidebar blocks to canvas (using event delegation on parent)
        const sidebarCategories = document.querySelector('.sidebar-categories');

        if (sidebarCategories) {
            sidebarCategories.addEventListener('dragstart', function(e) {
                if (e.target.classList.contains('block-item')) {
                    e.dataTransfer.effectAllowed = 'copy';
                    e.dataTransfer.setData('blockType', e.target.dataset.type);
                    e.target.style.opacity = '0.5';
                }
            });

            sidebarCategories.addEventListener('dragend', function(e) {
                if (e.target.classList.contains('block-item')) {
                    e.target.style.opacity = '1';
                }
            });
        }

        // dragover - uses document since it bubbles
        document.addEventListener('dragover', function(e) {
            if (e.dataTransfer.types.includes('blockType')) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                const canvasBody = document.getElementById('canvas-body');
                const builderCanvas = document.querySelector('.builder-canvas');
                if (canvasBody && canvasBody.contains(e.target)) {
                    builderCanvas.classList.add('drag-over');
                    canvasBlocks.style.borderColor = 'var(--primary)';
                    canvasBlocks.style.backgroundColor = 'rgba(99, 102, 241, 0.08)';
                    canvasBlocks.style.boxShadow = '0 0 0 2px rgba(99, 102, 241, 0.1)';
                    if (canvasEmpty) {
                        canvasEmpty.style.borderColor = 'var(--primary)';
                        canvasEmpty.style.backgroundColor = '#f5f3ff';
                        canvasEmpty.style.transform = 'scale(1.01)';
                    }
                }
            }
        });

        // dragleave
        document.addEventListener('dragleave', function(e) {
            const canvasBody = document.getElementById('canvas-body');
            const builderCanvas = document.querySelector('.builder-canvas');
            if (canvasBody && !canvasBody.contains(e.relatedTarget)) {
                builderCanvas.classList.remove('drag-over');
                canvasBlocks.style.borderColor = '';
                canvasBlocks.style.backgroundColor = '';
                canvasBlocks.style.boxShadow = '';
                if (canvasEmpty) {
                    canvasEmpty.style.borderColor = '';
                    canvasEmpty.style.backgroundColor = '';
                    canvasEmpty.style.transform = '';
                }
            }
        });

        // drop
        document.addEventListener('drop', function(e) {
            e.preventDefault();
            const blockType = e.dataTransfer.getData('blockType');
            const canvasBody = document.getElementById('canvas-body');
            const builderCanvas = document.querySelector('.builder-canvas');
            if (blockType && canvasBody && canvasBody.contains(e.target)) {
                builderCanvas.classList.remove('drag-over');
                canvasBlocks.style.borderColor = '';
                canvasBlocks.style.backgroundColor = '';
                canvasBlocks.style.boxShadow = '';
                if (canvasEmpty) {
                    canvasEmpty.style.borderColor = '';
                    canvasEmpty.style.backgroundColor = '';
                    canvasEmpty.style.transform = '';
                }
                addBlock(blockType);
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
                document.querySelectorAll('.device-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                const wrapper = document.getElementById('canvas-wrapper');
                wrapper.classList.remove('tablet', 'mobile');
                if (this.dataset.device !== 'desktop') wrapper.classList.add(this.dataset.device);
            });
        });

        // Properties tabs
        document.querySelectorAll('.properties-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.properties-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                this.classList.add('active');
                document.querySelectorAll('.props-tab').forEach(function(el) {
                    el.style.display = 'none';
                });
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
                container: {
                    type: 'container',
                    label: 'Container',
                    content: '',
                    bgColor: '#ffffff',
                    padding: 'md'
                },
                columns: {
                    type: 'columns',
                    label: 'Columns',
                    columns: 3,
                    content: ''
                },
                grid: {
                    type: 'grid',
                    label: 'Grid',
                    columns: 3,
                    content: ''
                },
                section: {
                    type: 'section',
                    label: 'Section',
                    bgColor: '#f9fafb',
                    content: ''
                },
                divider: {
                    type: 'divider',
                    label: 'Divider',
                    style: 'solid'
                },
                spacer: {
                    type: 'spacer',
                    label: 'Spacer',
                    height: 32
                },
                heading: {
                    type: 'heading',
                    label: 'Heading',
                    content: 'Your Heading Here',
                    level: 'h2',
                    align: 'left'
                },
                text: {
                    type: 'text',
                    label: 'Text',
                    content: 'Your text content goes here. You can edit this in the properties panel.'
                },
                richtext: {
                    type: 'richtext',
                    label: 'Rich Text',
                    content: '<p>Your <strong>rich</strong> text here.</p>'
                },
                list: {
                    type: 'list',
                    label: 'List',
                    items: 'Item 1\nItem 2\nItem 3',
                    ordered: false
                },
                quote: {
                    type: 'quote',
                    label: 'Quote',
                    content: '"This is a great quote."',
                    author: '- Author Name'
                },
                code: {
                    type: 'code',
                    label: 'Code',
                    content: 'console.log("Hello World");'
                },
                image: {
                    type: 'image',
                    label: 'Image',
                    src: '',
                    alt: 'Image',
                    caption: ''
                },
                gallery: {
                    type: 'gallery',
                    label: 'Gallery',
                    images: 4,
                    columns: 4
                },
                video: {
                    type: 'video',
                    label: 'Video',
                    url: 'https://youtube.com/embed/...',
                    autoplay: false
                },
                icon: {
                    type: 'icon',
                    label: 'Icon',
                    icon: '⭐',
                    size: 48,
                    color: '#6366f1'
                },
                avatar: {
                    type: 'avatar',
                    label: 'Avatar',
                    src: '',
                    name: 'User',
                    role: 'Role'
                },
                'text-input': {
                    type: 'text-input',
                    label: 'Text Input',
                    placeholder: 'Enter text...',
                    required: false
                },
                textarea: {
                    type: 'textarea',
                    label: 'Textarea',
                    placeholder: 'Enter message...',
                    required: false
                },
                email: {
                    type: 'email',
                    label: 'Email',
                    placeholder: 'email@example.com',
                    required: false
                },
                number: {
                    type: 'number',
                    label: 'Number',
                    placeholder: '0',
                    required: false
                },
                password: {
                    type: 'password',
                    label: 'Password',
                    placeholder: '••••••••',
                    required: false
                },
                select: {
                    type: 'select',
                    label: 'Dropdown',
                    options: 'Option 1\nOption 2\nOption 3',
                    required: false
                },
                checkbox: {
                    type: 'checkbox',
                    label: 'Checkbox',
                    text: 'I agree to the terms',
                    required: false
                },
                radio: {
                    type: 'radio',
                    label: 'Radio',
                    options: 'Option 1\nOption 2\nOption 3',
                    required: false
                },
                date: {
                    type: 'date',
                    label: 'Date',
                    required: false
                },
                file: {
                    type: 'file',
                    label: 'File Upload',
                    accept: '*',
                    required: false
                },
                hidden: {
                    type: 'hidden',
                    label: 'Hidden Field',
                    name: 'hidden_field',
                    value: ''
                },
                submit: {
                    type: 'submit',
                    label: 'Submit Button',
                    text: 'Submit',
                    variant: 'primary'
                },
                'product-card': {
                    type: 'product-card',
                    label: 'Product',
                    name: 'Product Name',
                    price: '99.99',
                    image: '',
                    badge: 'sale'
                },
                'product-grid': {
                    type: 'product-grid',
                    label: 'Product Grid',
                    products: 4,
                    columns: 3
                },
                price: {
                    type: 'price',
                    label: 'Price',
                    amount: '99.99',
                    currency: '$',
                    oldPrice: '149.99'
                },
                'add-to-cart': {
                    type: 'add-to-cart',
                    label: 'Add to Cart',
                    text: 'Add to Cart',
                    variant: 'primary'
                },
                'product-badge': {
                    type: 'product-badge',
                    label: 'Badge',
                    text: 'SALE',
                    variant: 'sale'
                },
                stars: {
                    type: 'stars',
                    label: 'Stars',
                    rating: 4,
                    max: 5
                },
                'stock-status': {
                    type: 'stock-status',
                    label: 'Stock',
                    status: 'instock',
                    text: 'In Stock'
                },
                'buy-now': {
                    type: 'buy-now',
                    label: 'Buy Now',
                    text: 'Buy Now'
                },
                hero: {
                    type: 'hero',
                    label: 'Hero',
                    title: 'Welcome to Our Site',
                    subtitle: 'Build amazing things',
                    buttonText: 'Get Started',
                    buttonUrl: '#'
                },
                team: {
                    type: 'team',
                    label: 'Team Member',
                    name: 'John Doe',
                    role: 'CEO',
                    image: ''
                },
                testimonial: {
                    type: 'testimonial',
                    label: 'Testimonial',
                    text: '"Amazing service!"',
                    author: 'Jane Smith',
                    role: 'Customer'
                },
                pricing: {
                    type: 'pricing',
                    label: 'Pricing',
                    name: 'Pro Plan',
                    price: '29',
                    period: '/month',
                    features: 'Feature 1\nFeature 2\nFeature 3',
                    highlighted: false
                },
                faq: {
                    type: 'faq',
                    label: 'FAQ',
                    question: 'Frequently Asked Question?',
                    answer: 'This is the answer to the question.'
                },
                stats: {
                    type: 'stats',
                    label: 'Stats',
                    number: '1000',
                    label: 'Happy Customers',
                    suffix: '+'
                },
                features: {
                    type: 'features',
                    label: 'Feature',
                    icon: 'star',
                    title: 'Feature Title',
                    description: 'Feature description goes here.'
                },
                'contact-card': {
                    type: 'contact-card',
                    label: 'Contact',
                    icon: 'phone',
                    title: 'Phone',
                    info: '+1 234 567 890'
                },
                button: {
                    type: 'button',
                    label: 'Button',
                    text: 'Click Me',
                    url: '#',
                    variant: 'primary'
                },
                link: {
                    type: 'link',
                    label: 'Link',
                    text: 'Click here',
                    url: '#'
                },
                tabs: {
                    type: 'tabs',
                    label: 'Tabs',
                    tabs: 'Tab 1\nTab 2\nTab 3'
                },
                accordion: {
                    type: 'accordion',
                    label: 'Accordion',
                    question: 'Accordion Title',
                    answer: 'Accordion content goes here.'
                },
                progress: {
                    type: 'progress',
                    label: 'Progress',
                    value: 75,
                    color: '#6366f1'
                },
                timeline: {
                    type: 'timeline',
                    label: 'Timeline',
                    title: 'Event Title',
                    date: '2024',
                    description: 'Event description.'
                },
                newsletter: {
                    type: 'newsletter',
                    label: 'Newsletter',
                    title: 'Subscribe to Newsletter',
                    placeholder: 'Enter your email',
                    buttonText: 'Subscribe'
                },
                countdown: {
                    type: 'countdown',
                    label: 'Countdown',
                    targetDate: '2024-12-31'
                },
                alert: {
                    type: 'alert',
                    label: 'Alert',
                    message: 'This is an alert message.',
                    variant: 'info'
                },
                cta: {
                    type: 'cta',
                    label: 'CTA',
                    title: 'Ready to get started?',
                    subtitle: 'Join thousands of happy users',
                    buttonText: 'Start Now',
                    buttonUrl: '#'
                },
                'social-links': {
                    type: 'social-links',
                    label: 'Social Links',
                    platforms: 'Facebook\nTwitter\nInstagram\nLinkedIn'
                },
                'share-buttons': {
                    type: 'share-buttons',
                    label: 'Share Buttons',
                    platforms: 'Facebook\nTwitter\nLinkedIn'
                },
                embed: {
                    type: 'embed',
                    label: 'Embed',
                    url: 'https://youtube.com/embed/...'
                },
                table: {
                    type: 'table',
                    label: 'Table',
                    headers: 'Name,Age,Email',
                    rows: 'John,30,john@email.com\nJane,25,jane@email.com'
                },
                badge: {
                    type: 'badge',
                    label: 'Badge',
                    text: 'Badge',
                    variant: 'primary'
                },
                chart: {
                    type: 'chart',
                    label: 'Chart',
                    data: '20,35,25,45,30'
                },
                map: {
                    type: 'map',
                    label: 'Map',
                    embedUrl: 'https://maps.google.com/maps?q=...'
                },
                html: {
                    type: 'html',
                    label: 'Custom HTML',
                    code: '<div>Custom HTML here</div>'
                },
                template: {
                    type: 'template',
                    label: 'Template',
                    templateId: ''
                }
            };
            return Object.assign({
                type: type
            }, defaults[type] || {});
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
            const typeIcons = {
                container: '<i class="fas fa-box"></i>',
                columns: '<i class="fas fa-columns"></i>',
                grid: '<i class="fas fa-grip"></i>',
                section: '<i class="fas fa-file"></i>',
                divider: '<i class="fas fa-minus"></i>',
                spacer: '<i class="fas fa-arrows-alt-v"></i>',
                heading: '<i class="fas fa-heading"></i>',
                text: '<i class="fas fa-font"></i>',
                richtext: '<i class="fas fa-file-alt"></i>',
                list: '<i class="fas fa-list-ul"></i>',
                quote: '<i class="fas fa-quote-left"></i>',
                code: '<i class="fas fa-code"></i>',
                image: '<i class="fas fa-image"></i>',
                gallery: '<i class="fas fa-images"></i>',
                video: '<i class="fas fa-video"></i>',
                icon: '<i class="fas fa-star"></i>',
                avatar: '<i class="fas fa-user-circle"></i>',
                'text-input': '<i class="fas fa-keyboard"></i>',
                textarea: '<i class="fas fa-align-left"></i>',
                email: '<i class="fas fa-envelope"></i>',
                number: '<i class="fas fa-hashtag"></i>',
                password: '<i class="fas fa-lock"></i>',
                select: '<i class="fas fa-list"></i>',
                checkbox: '<i class="fas fa-check-square"></i>',
                radio: '<i class="fas fa-circle"></i>',
                date: '<i class="fas fa-calendar"></i>',
                file: '<i class="fas fa-paperclip"></i>',
                hidden: '<i class="fas fa-eye-slash"></i>',
                submit: '<i class="fas fa-check"></i>',
                'product-card': '<i class="fas fa-shopping-bag"></i>',
                'product-grid': '<i class="fas fa-th"></i>',
                price: '<i class="fas fa-dollar-sign"></i>',
                'add-to-cart': '<i class="fas fa-shopping-cart"></i>',
                'product-badge': '<i class="fas fa-tag"></i>',
                stars: '<i class="fas fa-star"></i>',
                'stock-status': '<i class="fas fa-box"></i>',
                'buy-now': '<i class="fas fa-bolt"></i>',
                hero: '<i class="fas fa-bullseye"></i>',
                team: '<i class="fas fa-users"></i>',
                testimonial: '<i class="fas fa-quote-left"></i>',
                pricing: '<i class="fas fa-gem"></i>',
                faq: '<i class="fas fa-question-circle"></i>',
                stats: '<i class="fas fa-chart-bar"></i>',
                features: '<i class="fas fa-star"></i>',
                'contact-card': '<i class="fas fa-phone"></i>',
                button: '<i class="fas fa-circle"></i>',
                link: '<i class="fas fa-link"></i>',
                tabs: '<i class="fas fa-folder"></i>',
                accordion: '<i class="fas fa-folder-open"></i>',
                progress: '<i class="fas fa-chart-bar"></i>',
                timeline: '<i class="fas fa-calendar"></i>',
                newsletter: '<i class="fas fa-envelope-open"></i>',
                countdown: '<i class="fas fa-hourglass-end"></i>',
                alert: '<i class="fas fa-exclamation-triangle"></i>',
                cta: '<i class="fas fa-bullseye"></i>',
                'social-links': '<i class="fas fa-share-alt"></i>',
                'share-buttons': '<i class="fas fa-share-nodes"></i>',
                embed: '<i class="fas fa-tv"></i>',
                table: '<i class="fas fa-table"></i>',
                badge: '<i class="fas fa-tag"></i>',
                chart: '<i class="fas fa-chart-line"></i>',
                map: '<i class="fas fa-map"></i>',
                html: '<i class="fas fa-code"></i>',
                template: '<i class="fas fa-file-contract"></i>'
            };

            const preview = buildPreview(block);
            const selected = index === selectedIndex ? ' selected' : '';

            return `
            <div class="canvas-block-header">
                <div class="canvas-block-type">
                    <span class="drag-handle" title="Drag to reorder"><i class="fas fa-ellipsis-v"></i></span>
                    <span>${typeIcons[block.type] || '<i class="fas fa-cube"></i>'}</span>
                    <span>${escapeHtml(block.label)}</span>
                </div>
                <div class="canvas-block-actions">
                    <button type="button" class="canvas-block-btn move-up" ${index===0?'disabled':''}><i class="fas fa-chevron-up"></i></button>
                    <button type="button" class="canvas-block-btn move-down" ${index===blocks.length-1?'disabled':''}><i class="fas fa-chevron-down"></i></button>
                    <button type="button" class="canvas-block-btn duplicate"><i class="fas fa-copy"></i></button>
                    <button type="button" class="canvas-block-btn delete"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="canvas-block-preview">${preview}</div>`;
        }

        function buildPreview(block) {
            switch (block.type) {
                case 'container':
                    return '<div style="padding:20px;border:1px dashed var(--gray-300);border-radius:8px;">Container</div>';
                case 'columns':
                    return '<div style="display:grid;grid-template-columns:repeat(' + (block.columns || 3) + ',1fr);gap:8px;">' + Array(block.columns || 3).fill('<div style="height:60px;background:var(--gray-100);border-radius:6px;"></div>').join('') + '</div>';
                case 'grid':
                    return '<div style="display:grid;grid-template-columns:repeat(' + (block.columns || 3) + ',1fr);gap:8px;">' + Array(block.columns || 3).fill('<div style="height:60px;background:var(--gray-100);border-radius:6px;"></div>').join('') + '</div>';
                case 'section':
                    return '<div style="padding:40px;background:var(--gray-50);border-radius:8px;text-align:center;color:var(--gray-400);">Section</div>';
                case 'divider':
                    return '<hr class="preview-divider">';
                case 'spacer':
                    return '<div class="preview-spacer" style="height:' + (block.height || 32) + 'px;"></div>';
                case 'heading':
                    return '<' + (block.level || 'h2') + ' class="preview-heading" style="text-align:' + (block.align || 'left') + ';">' + escapeHtml(block.content || 'Heading') + '</' + (block.level || 'h2') + '>';
                case 'text':
                    return '<p class="preview-text">' + escapeHtml(block.content || 'Text content') + '</p>';
                case 'richtext':
                    return '<div>' + block.content + '</div>';
                case 'list':
                    const items = (block.items || 'Item 1\nItem 2').split('\n');
                    const tag = block.ordered ? 'ol' : 'ul';
                    return '<' + tag + ' class="preview-list">' + items.map(function(i) {
                        return '<li>' + escapeHtml(i.trim()) + '</li>';
                    }).join('') + '</' + tag + '>';
                case 'quote':
                    return '<blockquote class="preview-quote">' + escapeHtml(block.content || 'Quote') + '<br><small>' + escapeHtml(block.author || '') + '</small></blockquote>';
                case 'code':
                    return '<pre class="preview-code">' + escapeHtml(block.content || 'code') + '</pre>';
                case 'image':
                    return block.src ? '<img src="' + escapeHtml(block.src) + '" style="width:100%;height:200px;object-fit:cover;border-radius:8px;" alt="' + escapeHtml(block.alt || '') + '">' : '<div class="preview-image"><i class="fas fa-image"></i></div>';
                case 'gallery':
                    return '<div class="preview-gallery">' + Array(block.images || 4).fill('<div class="preview-gallery-item"><i class="fas fa-image"></i></div>').join('') + '</div>';
                case 'video':
                    return '<div class="preview-video"><i class="fas fa-play"></i></div>';
                case 'icon':
                    return '<div class="preview-icon-box"><div class="preview-icon-circle" style="background:' + (block.color || 'var(--primary)') + ';">' + escapeHtml(block.icon || '✨') + '</div><div>' + escapeHtml(block.label || 'Icon') + '</div></div>';
                case 'avatar':
                    return '<div class="preview-team"><div class="preview-team-avatar"><i class="fas fa-user-circle"></i></div><div><div class="preview-team-name">' + escapeHtml(block.name || 'User') + '</div><div class="preview-team-role">' + escapeHtml(block.role || '') + '</div></div></div>';
                case 'text-input':
                    return '<div class="property-label">' + escapeHtml(block.label || 'Text') + (block.required ? ' <span style="color:var(--danger)">*</span>' : '') + '</div><input class="preview-input" placeholder="' + escapeHtml(block.placeholder || '') + '" disabled>';
                case 'textarea':
                    return '<div class="property-label">' + escapeHtml(block.label || 'Textarea') + '</div><textarea class="preview-textarea" placeholder="' + escapeHtml(block.placeholder || '') + '" disabled></textarea>';
                case 'email':
                    return '<div class="property-label">' + escapeHtml(block.label || 'Email') + '</div><input type="email" class="preview-input" placeholder="' + escapeHtml(block.placeholder || '') + '" disabled>';
                case 'number':
                    return '<div class="property-label">' + escapeHtml(block.label || 'Number') + '</div><input type="number" class="preview-input" placeholder="' + escapeHtml(block.placeholder || '') + '" disabled>';
                case 'password':
                    return '<div class="property-label">' + escapeHtml(block.label || 'Password') + '</div><input type="password" class="preview-input" placeholder="••••••••" disabled>';
                case 'select':
                    const opts = (block.options || 'Option 1\nOption 2').split('\n');
                    return '<div class="property-label">' + escapeHtml(block.label || 'Select') + '</div><select class="preview-select" disabled>' + opts.map(function(o) {
                        return '<option>' + escapeHtml(o.trim()) + '</option>';
                    }).join('') + '</select>';
                case 'checkbox':
                    return '<div class="preview-checkbox"><input type="checkbox" disabled><span>' + escapeHtml(block.text || block.label || 'Checkbox') + '</span></div>';
                case 'radio':
                    const radios = (block.options || 'Option 1\nOption 2').split('\n');
                    return '<div class="property-label">' + escapeHtml(block.label || 'Radio') + '</div>' + radios.map(function(o) {
                        return '<div class="preview-radio"><input type="radio" disabled><span>' + escapeHtml(o.trim()) + '</span></div>';
                    }).join('');
                case 'date':
                    return '<div class="property-label">' + escapeHtml(block.label || 'Date') + '</div><input type="date" class="preview-date" disabled>';
                case 'file':
                    return '<div class="property-label">' + escapeHtml(block.label || 'File') + '</div><div class="preview-file">📎 Click to upload or drag and drop</div>';
                case 'hidden':
                    return '<div style="padding:8px;background:var(--gray-100);border-radius:4px;font-size:12px;color:var(--gray-400);"><i class="fas fa-eye-slash"></i> Hidden: ' + escapeHtml(block.name || '') + '</div>';
                case 'submit':
                    const variants = {
                        primary: 'var(--primary)',
                        success: 'var(--success)',
                        warning: 'var(--warning)',
                        danger: 'var(--danger)'
                    };
                    return '<button class="preview-button" style="background:' + (variants[block.variant] || 'var(--primary)') + ';">' + escapeHtml(block.text || 'Submit') + '</button>';
                case 'product-card':
                    return '<div class="preview-product-card"><div class="preview-product-img">📦</div><div class="preview-product-body"><div class="preview-product-name">' + escapeHtml(block.name || 'Product') + '</div><div class="preview-product-price">$' + escapeHtml(block.price || '0') + '</div></div></div>';
                case 'product-grid':
                    return '<div style="display:grid;grid-template-columns:repeat(' + (block.columns || 3) + ',1fr);gap:12px;">' + Array(block.products || 4).fill('<div class="preview-product-card"><div class="preview-product-img">📦</div><div class="preview-product-body"><div class="preview-product-name">Product</div><div class="preview-product-price">$99</div></div></div>').join('') + '</div>';
                case 'price':
                    return '<div><span class="preview-price">' + escapeHtml(block.currency || '$') + escapeHtml(block.amount || '0') + '</span>' + (block.oldPrice ? '<span class="preview-price-old">' + escapeHtml(block.currency || '$') + escapeHtml(block.oldPrice) + '</span>' : '') + '</div>';
                case 'add-to-cart':
                    return '<button class="preview-button" style="background:var(--success);">🛒 ' + escapeHtml(block.text || 'Add to Cart') + '</button>';
                case 'product-badge':
                    const badgeVars = {
                        sale: 'preview-badge-sale',
                        new: 'preview-badge-new',
                        hot: 'preview-badge-hot'
                    };
                    return '<span class="preview-badge ' + (badgeVars[block.variant] || 'preview-badge-sale') + '">' + escapeHtml(block.text || 'SALE') + '</span>';
                case 'stars':
                    return '<div class="preview-stars">' + '★'.repeat(block.rating || 4) + '☆'.repeat((block.max || 5) - (block.rating || 4)) + '</div>';
                case 'stock-status':
                    return '<span style="color:' + (block.status === 'instock' ? 'var(--success)' : 'var(--danger)') + ';\"><i class="fas fa-' + (block.status === 'instock' ? 'check-circle' : 'times-circle') + '" style="margin-right:6px;"></i>' + escapeHtml(block.text || 'In Stock') + '</span>';
                case 'buy-now':
                    return '<button class="preview-button" style="background:var(--warning);color:#000;"><i class="fas fa-bolt" style="margin-right:6px;"></i>' + escapeHtml(block.text || 'Buy Now') + '</button>';
                case 'hero':
                    return '<div style="text-align:center;padding:40px 20px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;color:white;"><h2 style="margin:0 0 8px;">' + escapeHtml(block.title || 'Hero') + '</h2><p style="margin:0 0 16px;opacity:0.9;">' + escapeHtml(block.subtitle || '') + '</p><span class="preview-button" style="background:white;color:#667eea;">' + escapeHtml(block.buttonText || 'Get Started') + '</span></div>';
                case 'team':
                    return '<div class="preview-team"><div class="preview-team-avatar"><i class="fas fa-user-circle"></i></div><div><div class="preview-team-name">' + escapeHtml(block.name || 'Name') + '</div><div class="preview-team-role">' + escapeHtml(block.role || '') + '</div></div></div>';
                case 'testimonial':
                    return '<div class="preview-testimonial"><div class="preview-testimonial-text">' + escapeHtml(block.text || 'Testimonial') + '</div><div class="preview-testimonial-author">' + escapeHtml(block.author || '') + '</div></div>';
                case 'pricing':
                    const feats = (block.features || '').split('\n');
                    return '<div class="preview-pricing"><div class="preview-pricing-name">' + escapeHtml(block.name || 'Plan') + '</div><div class="preview-pricing-price">' + escapeHtml(block.currency || '$') + escapeHtml(block.price || '0') + '<small style="font-size:14px;color:var(--gray-400);">' + escapeHtml(block.period || '') + '</small></div><ul class="preview-pricing-features">' + feats.map(function(f) {
                        return '<li><i class="fas fa-check" style="color:var(--success);margin-right:6px;"></i>' + escapeHtml(f.trim()) + '</li>';
                    }).join('') + '</ul><button class="preview-button">Choose Plan</button></div>';
                case 'faq':
                    return '<div class="preview-faq-item"><div class="preview-faq-question"><i class="fas fa-question-circle"></i> ' + escapeHtml(block.question || 'Question?') + '</div><div class="preview-faq-answer">' + escapeHtml(block.answer || 'Answer') + '</div></div>';
                case 'stats':
                    return '<div style="text-align:center;padding:20px;"><div class="preview-stat-number">' + escapeHtml(block.number || '0') + escapeHtml(block.suffix || '') + '</div><div class="preview-stat-label">' + escapeHtml(block.label || '') + '</div></div>';
                case 'features':
                    return '<div style="display:flex;gap:12px;align-items:start;padding:16px;border:1px solid var(--gray-200);border-radius:12px;"><div style="font-size:24px;color:var(--primary);">' + (block.icon || '<i class="fas fa-star"></i>') + '</div><div><div style="font-weight:600;">' + escapeHtml(block.title || 'Feature') + '</div><div style="font-size:13px;color:var(--gray-400);">' + escapeHtml(block.description || '') + '</div></div></div>';
                case 'contact-card':
                    const iconMap = {
                        'phone': '<i class="fas fa-phone"></i>',
                        'star': '<i class="fas fa-star"></i>',
                        'mail': '<i class="fas fa-envelope"></i>',
                        'location': '<i class="fas fa-map-marker-alt"></i>'
                    };
                    return '<div class="preview-contact-card"><div class="preview-contact-icon">' + (iconMap[block.icon] || '<i class="fas fa-phone"></i>') + '</div><div><div style="font-weight:600;">' + escapeHtml(block.title || '') + '</div><div style="font-size:13px;color:var(--gray-400);">' + escapeHtml(block.info || '') + '</div></div></div>';
                case 'button':
                    const btnVars = {
                        primary: 'var(--primary)',
                        success: 'var(--success)',
                        warning: 'var(--warning)',
                        danger: 'var(--danger)',
                        outline: 'transparent;border:2px solid var(--primary);color:var(--primary)'
                    };
                    return '<a class="preview-button" style="background:' + (btnVars[block.variant] || 'var(--primary)') + ';" href="#">' + escapeHtml(block.text || 'Button') + '</a>';
                case 'link':
                    return '<a href="#" style="color:var(--primary);">' + escapeHtml(block.text || 'Link') + ' →</a>';
                case 'tabs':
                    const tabs = (block.tabs || 'Tab 1\nTab 2').split('\n');
                    return '<div class="preview-tabs">' + tabs.map(function(t, i) {
                        return '<div class="preview-tab' + (i === 0 ? ' active' : '') + '">' + escapeHtml(t.trim()) + '</div>';
                    }).join('') + '</div><div style="padding:16px;color:var(--gray-400);">Tab content here</div>';
                case 'accordion':
                    return '<div class="preview-accordion-item"><div class="preview-accordion-header">▸ ' + escapeHtml(block.question || 'Question?') + '</div><div class="preview-accordion-body">' + escapeHtml(block.answer || '') + '</div></div>';
                case 'progress':
                    return '<div><div class="property-label" style="font-size:13px;">' + escapeHtml(block.label || 'Progress') + ': ' + (block.value || 0) + '%</div><div class="preview-progress"><div class="preview-progress-bar" style="width:' + (block.value || 0) + '%;background:' + (block.color || 'var(--primary)') + ';"></div></div></div>';
                case 'timeline':
                    return '<div class="preview-timeline"><div class="preview-timeline-item"><div class="preview-timeline-dot"></div><div style="font-weight:600;">' + escapeHtml(block.title || '') + '</div><div style="font-size:12px;color:var(--gray-400);">' + escapeHtml(block.date || '') + '</div><div style="font-size:13px;color:var(--gray-600);">' + escapeHtml(block.description || '') + '</div></div></div>';
                case 'newsletter':
                    return '<div class="preview-newsletter"><div class="preview-newsletter-title">' + escapeHtml(block.title || 'Newsletter') + '</div><input class="preview-newsletter-input" placeholder="' + escapeHtml(block.placeholder || 'Email') + '"><br><button class="preview-button" style="background:white;color:var(--primary);">' + escapeHtml(block.buttonText || 'Subscribe') + '</button></div>';
                case 'countdown':
                    return '<div class="preview-countdown"><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Days</div></div><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Hours</div></div><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Mins</div></div><div class="preview-countdown-item"><div class="preview-countdown-number">00</div><div class="preview-countdown-label">Secs</div></div></div>';
                case 'alert':
                    const alertVars = {
                        info: 'preview-alert-info',
                        success: 'preview-alert-success',
                        warning: 'preview-alert-warning',
                        error: 'preview-alert-error'
                    };
                    return '<div class="preview-alert ' + (alertVars[block.variant] || 'preview-alert-info') + '">' + escapeHtml(block.message || 'Alert') + '</div>';
                case 'cta':
                    return '<div style="text-align:center;padding:40px 20px;background:var(--gray-50);border-radius:12px;"><h3 style="margin:0 0 8px;">' + escapeHtml(block.title || 'CTA') + '</h3><p style="margin:0 0 16px;color:var(--gray-400);">' + escapeHtml(block.subtitle || '') + '</p><button class="preview-button">' + escapeHtml(block.buttonText || 'Start Now') + '</button></div>';
                case 'social-links':
                    const platforms = (block.platforms || 'Facebook\nTwitter').split('\n');
                    const icons = {
                        'facebook': '<i class="fab fa-facebook"></i>',
                        'twitter': '<i class="fab fa-twitter"></i>',
                        'instagram': '<i class="fab fa-instagram"></i>',
                        'linkedin': '<i class="fab fa-linkedin"></i>',
                        'youtube': '<i class="fab fa-youtube"></i>',
                        'tiktok': '<i class="fab fa-tiktok"></i>'
                    };
                    return '<div class="preview-social">' + platforms.map(function(p) {
                        return '<div class="preview-social-icon">' + (icons[p.trim().toLowerCase()] || '<i class="fas fa-link"></i>') + '</div>';
                    }).join('') + '</div>';
                case 'share-buttons':
                    return '<div style="display:flex;gap:8px;"><div class="preview-social-icon"><i class="fab fa-facebook"></i></div><div class="preview-social-icon"><i class="fab fa-twitter"></i></div><div class="preview-social-icon"><i class="fab fa-linkedin"></i></div></div>';
                case 'embed':
                    return '<div style="height:200px;background:var(--gray-900);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:32px;"><i class="fas fa-video"></i> Embed</div>';
                case 'table':
                    const headers = (block.headers || 'Col1,Col2').split(',');
                    const rows = (block.rows || '').split('\n');
                    return '<table class="preview-table"><thead><tr>' + headers.map(function(h) {
                        return '<th>' + escapeHtml(h.trim()) + '</th>';
                    }).join('') + '</tr></thead><tbody>' + rows.map(function(r) {
                        return '<tr>' + r.split(',').map(function(c) {
                            return '<td>' + escapeHtml(c.trim()) + '</td>';
                        }).join('') + '</tr>';
                    }).join('') + '</tbody></table>';
                case 'badge':
                    const badgeColors = {
                        primary: 'background:var(--primary);color:white;',
                        success: 'background:var(--success);color:white;',
                        warning: 'background:var(--warning);color:white;',
                        danger: 'background:var(--danger);color:white;',
                        info: 'background:var(--info);color:white;'
                    };
                    return '<span class="preview-badge-tag" style="' + (badgeColors[block.variant] || 'background:var(--gray-100);color:var(--gray-600);') + '">' + escapeHtml(block.text || 'Badge') + '</span>';
                case 'chart':
                    const data = (block.data || '20,35,25').split(',');
                    const max = Math.max.apply(null, data.map(Number));
                    return '<div class="preview-chart">' + data.map(function(v) {
                        return '<div class="preview-chart-bar" style="height:' + ((v / max) * 100) + '%;"></div>';
                    }).join('') + '</div>';
                case 'map':
                    return '<div class="preview-map"><i class="fas fa-map"></i></div>';
                case 'html':
                    return '<div class="preview-code">' + escapeHtml(block.code || '<!-- HTML -->') + '</div>';
                case 'template':
                    return '<div style="padding:20px;border:2px dashed var(--gray-300);border-radius:8px;text-align:center;color:var(--gray-400);"><i class="fas fa-file"></i> Template: ' + (block.templateId || 'None') + '</div>';
                default:
                    return '<div style="padding:20px;text-align:center;color:var(--gray-400);">Block: ' + escapeHtml(block.type) + '</div>';
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
                    [blocks[index], blocks[index - 1]] = [blocks[index - 1], blocks[index]];
                    refreshAllBlocks();
                    selectBlock(index - 1);
                }
            });

            if (downBtn) downBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (index < blocks.length - 1) {
                    saveState();
                    [blocks[index], blocks[index + 1]] = [blocks[index + 1], blocks[index]];
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
                showDeleteConfirmModal(function() {
                    saveState();
                    blocks.splice(index, 1);
                    if (selectedIndex === index) {
                        selectedIndex = -1;
                        hideProperties();
                    } else if (selectedIndex > index) selectedIndex--;
                    refreshAllBlocks();
                    updateEmptyState();
                });
            });
        }

        // Custom Delete Confirmation Modal
        function showDeleteConfirmModal(callback) {
            const modal = document.createElement('div');
            modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        `;
            modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease;">
                <div style="font-size: 48px; margin-bottom: 16px;">🗑️</div>
                <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600; color: var(--gray-900);">Delete this block?</h3>
                <p style="margin: 0 0 24px; color: var(--gray-600); font-size: 14px;">This action cannot be undone. Are you sure?</p>
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button id="cancel-btn" style="padding: 10px 18px; border: 1px solid var(--gray-200); border-radius: 8px; background: white; color: var(--gray-600); font-weight: 500; cursor: pointer; transition: all 0.2s;">Cancel</button>
                    <button id="confirm-btn" style="padding: 10px 18px; border: none; border-radius: 8px; background: var(--danger); color: white; font-weight: 500; cursor: pointer; transition: all 0.2s;">Delete</button>
                </div>
            </div>
            <style>
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            </style>
        `;

            document.body.appendChild(modal);

            const cancelBtn = modal.querySelector('#cancel-btn');
            const confirmBtn = modal.querySelector('#confirm-btn');

            cancelBtn.addEventListener('click', function() {
                modal.style.animation = 'fadeIn 0.3s ease reverse';
                setTimeout(() => modal.remove(), 300);
            });

            confirmBtn.addEventListener('click', function() {
                modal.style.animation = 'fadeIn 0.3s ease reverse';
                setTimeout(() => {
                    modal.remove();
                    callback();
                }, 300);
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
            document.querySelectorAll('.props-tab').forEach(function(el) {
                el.style.display = '';
            });
            document.getElementById('props-delete-section').style.display = '';
        }

        function hideProperties() {
            document.getElementById('props-empty').style.display = '';
            document.querySelectorAll('.props-tab').forEach(function(el) {
                el.style.display = 'none';
            });
            document.getElementById('props-delete-section').style.display = 'none';
        }

        function updateProperties() {
            if (selectedIndex < 0) {
                hideProperties();
                return;
            }
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
        ['prop-label', 'prop-desc', 'prop-url', 'prop-image', 'prop-padding', 'prop-radius', 'prop-align', 'prop-class', 'prop-css'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', syncProperty);
        });
        ['prop-bg-color', 'prop-text-color'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', syncProperty);
        });
        document.getElementById('prop-hidden').addEventListener('change', syncProperty);
        // JSON TAB HANDLER
        document.querySelectorAll('.properties-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                document.querySelectorAll('.properties-tab').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.props-tab').forEach(el => el.style.display = 'none');
                const activePanel = document.getElementById('props-' + tabName);
                if (activePanel) activePanel.style.display = '';

                if (tabName === 'json' && selectedIndex >= 0) {
                    const jsonArea = document.getElementById('prop-json');
                    if (jsonArea) jsonArea.value = JSON.stringify(blocks[selectedIndex], null, 2);
                }
            });
        });

        // APPLY JSON BUTTON
        const applyJsonBtn = document.getElementById('btn-apply-json');
        if (applyJsonBtn) {
            applyJsonBtn.addEventListener('click', function() {
                if (selectedIndex < 0) {
                    alert('?? No block selected');
                    return;
                }
                try {
                    const jsonText = document.getElementById('prop-json').value;
                    const parsed = JSON.parse(jsonText);
                    blocks[selectedIndex] = Object.assign(blocks[selectedIndex], parsed);
                    saveState();
                    refreshAllBlocks();
                    selectBlock(selectedIndex);
                    alert('? JSON applied successfully!');
                } catch (e) {
                    alert('? Invalid JSON: ' + e.message);
                }
            });
        }

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
            if (selectedIndex >= 0) {
                showDeleteConfirmModal(function() {
                    saveState();
                    blocks.splice(selectedIndex, 1);
                    selectedIndex = -1;
                    hideProperties();
                    refreshAllBlocks();
                    updateEmptyState();
                });
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
            document.getElementById('table-id').value = document.getElementById('table-selector').value;
            document.getElementById('builder-form').submit();
        });

        document.getElementById('builder-form').addEventListener('submit', function() {
            document.getElementById('schema-json').value = JSON.stringify(blocks);
            document.getElementById('table-id').value = document.getElementById('table-selector').value;
        });

        // Table selector change - update hidden input
        document.getElementById('table-selector').addEventListener('change', function() {
            document.getElementById('table-id').value = this.value;
        });

        // Auto-generate form fields from table
        if (btnAutoGenerate) {
            btnAutoGenerate.addEventListener('click', function() {
                const tableId = tableSelector ? tableSelector.value : '';
                const tableName = tableSelector ? tableSelector.options[tableSelector.selectedIndex].text : 'NONE';

                console.log('%c=== AUTO-GENERATE START ===', 'color: #00aa00; font-weight: bold;');
                console.log('Time:', new Date().toLocaleTimeString());
                console.log('Table ID:', tableId);
                console.log('Table Name:', tableName);

                if (!tableId || tableId === '') {
                    console.warn('❌ No table selected!');
                    alert('❌ Please select a table from the dropdown first');
                    if (tableSelector) tableSelector.focus();
                    console.log('%c=== AUTO-GENERATE CANCELLED ===', 'color: #ff5500;');
                    return;
                }

                console.log('✅ Sending fetch request...');
                console.log('Payload:', JSON.stringify({
                    table_id: parseInt(tableId)
                }));

                this.disabled = true;
                this.textContent = '⏳ Generating...';

                fetch('<?= \yii\helpers\Url::to(['form/get-table-columns']) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= Yii::$app->request->getCsrfToken() ?>'
                        },
                        body: JSON.stringify({
                            table_id: parseInt(tableId)
                        })
                    })
                    .then(r => {
                        console.log('Response Status:', r.status, r.statusText);
                        return r.json();
                    })
                    .then(data => {
                        console.log('Response Data:', data);

                        if (data.success && data.columns && data.columns.length > 0) {
                            console.log('%c✅ SUCCESS! Creating ' + data.columns.length + ' fields...', 'color: #00aa00;');

                            // Clear existing blocks
                            blocks = [];
                            canvasBlocks.innerHTML = '';

                            // Create form fields for each column
                            data.columns.forEach(function(col, idx) {
                                const fieldType = getFieldTypeForColumn(col);
                                const block = {
                                    type: fieldType,
                                    label: col.label || col.name,
                                    name: col.name,
                                    placeholder: 'Enter ' + (col.label || col.name).toLowerCase(),
                                    required: !col.is_nullable,
                                    content: col.label || col.name
                                };
                                blocks.push(block);
                                renderBlock(block, blocks.length - 1);
                                console.log(`  [${idx + 1}] ${fieldType} - ${col.name}`);
                            });

                            updateEmptyState();
                            alert('✅ Form generated successfully with ' + data.columns.length + ' field(s)!');
                            console.log('%c=== AUTO-GENERATE SUCCESS ===', 'color: #00aa00; font-weight: bold;');
                        } else {
                            console.error('%c❌ Error Response:', 'color: #ff0000;', data);
                            const errorMsg = data.error || data.message || 'Failed to load table columns';
                            alert('❌ Error: ' + errorMsg);
                            console.log('%c=== AUTO-GENERATE FAILED ===', 'color: #ff0000;');
                        }
                    })
                    .catch(err => {
                        console.error('%c❌ Network/Fetch Error:', 'color: #ff0000;', err);
                        alert('❌ Network Error: ' + err.message);
                        console.log('%c=== AUTO-GENERATE ERROR ===', 'color: #ff0000;');
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.textContent = '⚙️ Auto-Generate';
                    });
            });
        } else {
            console.warn('❌ Auto-generate button not found');
        }

        // Map database column types to form field types
        function getFieldTypeForColumn(col) {
            const type = col.type.toUpperCase();
            if (type.includes('INT')) return 'number';
            if (type.includes('VARCHAR') || type.includes('CHAR')) return 'text-input';
            if (type.includes('TEXT')) return 'textarea';
            if (type.includes('DATE')) return 'date';
            if (type.includes('DECIMAL') || type.includes('FLOAT')) return 'number';
            if (type.includes('BOOLEAN')) return 'checkbox';
            return 'text-input';
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        updateEmptyState();

        // ============ INTERACTIVE SCROLL ANIMATIONS ============
        // Initialize AOS for scroll animations
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 600,
                easing: 'ease-out-cubic',
                once: false,
                mirror: true,
                offset: 80,
                disable: window.innerWidth < 768 ? 'phone' : false
            });
        }

        // Register GSAP ScrollTrigger
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
            gsap.registerPlugin(ScrollTrigger);
        }

        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        };

        const scrollAnimationObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    // Add animation class based on data attribute
                    const animType = entry.target.dataset.scrollAnimate || 'fade-up';
                    entry.target.classList.add('in-view');
                    entry.target.style.animationName = 'fadeInUp';

                    // Parallax effect
                    if (entry.target.classList.contains('parallax-item')) {
                        const parallaxValue = entry.target.dataset.parallax || 30;
                        entry.target.style.transform = `translateY(${parallaxValue * 0.5}px)`;
                    }
                } else {
                    // Remove animation when out of view (for mirror effect)
                    entry.target.classList.remove('in-view');
                    entry.target.style.animationName = '';
                }
            });
        }, observerOptions);

        // Apply scroll animations to canvas blocks
        function applyScrollAnimations() {
            const canvasBlocks = document.querySelectorAll('.canvas-block');
            canvasBlocks.forEach(function(block, index) {
                // Set animation type based on index for variety
                const animTypes = ['fade-up', 'slide-left', 'slide-right', 'scale'];
                const animType = animTypes[index % animTypes.length];
                block.dataset.scrollAnimate = animType;
                block.classList.add('scroll-animate', 'parallax-item');
                block.dataset.parallax = 20;

                // Add stagger effect
                block.style.animationDelay = `${index * 0.1}s`;

                scrollAnimationObserver.observe(block);
            });
        }

        // Apply animations to existing blocks
        applyScrollAnimations();

        // Parallax scroll effect on canvas blocks
        const canvasBody = document.getElementById('canvas-body');
        if (canvasBody) {
            window.addEventListener('scroll', function() {
                if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
                    const canvasBlocks = document.querySelectorAll('.canvas-block.parallax-item');
                    canvasBlocks.forEach(function(block) {
                        const scrollPos = window.scrollY;
                        const blockTop = block.getBoundingClientRect().top + window.scrollY;
                        const distance = blockTop - scrollPos;

                        // Light parallax effect (3-5% of scroll distance)
                        if (distance > -500 && distance < window.innerHeight + 500) {
                            const parallaxY = (distance - window.innerHeight / 2) * 0.03;
                            block.style.transform = `translateY(${parallaxY}px)`;
                        }
                    });
                }
            }, {
                passive: true
            });
        }

        // Smooth scroll initialization
        document.documentElement.style.scrollBehavior = 'smooth';

        // Observe canvas blocks when new blocks are added
        const originalRenderBlock = window.renderBlock;
        window.renderBlock = function(block, index) {
            originalRenderBlock.call(this, block, index);
            const newBlock = document.querySelector(`[data-block-id="${block.id || index}"]`);
            if (newBlock) {
                newBlock.classList.add('scroll-animate', 'parallax-item');
                newBlock.dataset.scrollAnimate = ['fade-up', 'slide-left', 'slide-right', 'scale'][index % 4];
                newBlock.dataset.parallax = 20;
                scrollAnimationObserver.observe(newBlock);

                // Trigger animation on new blocks
                setTimeout(function() {
                    newBlock.classList.add('in-view');
                }, 50);
            }
        };

        // Performance optimization: Throttle scroll event
        let scrollTimeout;
        let lastScrollPos = 0;
        window.addEventListener('scroll', function() {
            const currentScrollPos = window.scrollY;
            if (Math.abs(currentScrollPos - lastScrollPos) > 50) {
                lastScrollPos = currentScrollPos;

                // Update parallax positions with throttling
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    // Parallax update logic here (already in scroll listener above)
                }, 1000 / 60); // 60fps throttle
            }
        }, {
            passive: true
        });

        // Mobile optimization: Disable parallax on mobile for better performance
        if (window.innerWidth < 768) {
            document.querySelectorAll('.parallax-item').forEach(function(elem) {
                elem.classList.remove('parallax-item');
            });
        }

        // Resize observer to reapply animations on responsive changes
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(function() {
                applyScrollAnimations();
            }).observe(document.querySelector('.builder-canvas'));
        }

        // Fade-in animation for canvas wrapper on load
        const canvasWrapper = document.querySelector('.canvas-wrapper');
        if (canvasWrapper) {
            canvasWrapper.style.animation = 'fadeInUp 0.8s ease-out';
        }
    });
</script>