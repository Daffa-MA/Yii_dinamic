<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */

use yii\bootstrap5\Html;

$this->title = 'Visual Website Builder';
$this->registerJs("document.body.classList.add('dashboard-main-page');", \yii\web\View::POS_READY);

// Register dependencies - MUST be before other scripts
$this->registerCssFile('https://unpkg.com/grapesjs@0.21.7/dist/css/grapes.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://unpkg.com/grapesjs@0.21.7/dist/grapes.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://unpkg.com/grapesjs-preset-webpage@1.0.3/dist/grapesjs-preset-webpage.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://unpkg.com/grapesjs-preset-webpage@1.0.3/dist/grapesjs-preset-webpage.min.js', ['position' => \yii\web\View::POS_HEAD]);

// Register Fonts and Icons - MUST be before closing PHP tag
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght=400;500;600;700;800&display=swap', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', ['position' => \yii\web\View::POS_HEAD]);

// Register dependencies
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', ['position' => \yii\web\View::POS_HEAD]);
// Scroll animation libraries
$this->registerCssFile('https://unpkg.com/aos@2.3.1/dist/aos.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://unpkg.com/aos@2.3.1/dist/aos.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', ['position' => \yii\web\View::POS_HEAD]);

// Tailwind CSS
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'on-surface': '#0b1c30',
                    'on-surface-variant': '#464555',
                    'surface': '#fafbfe',
                    'surface-container-lowest': '#ffffff',
                    'surface-container-low': '#f8fafd',
                    'surface-container': '#f0f4f9',
                    'surface-container-high': '#e8eef7',
                    'primary-container': '#4f46e5',
                    'primary': '#3525cd',
                    'secondary': '#006c49',
                    'tertiary': '#7e3000',
                    'surface-tint': '#4d44e3',
                    'outline-variant': '#c7c4d8',
                    'outline': '#777587',
                    'error': '#ba1a1a',
                },
                fontFamily: {
                    'headline': ['Manrope'],
                    'body': ['Inter'],
                }
            }
        }
    }
</script>

<style>
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 18px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-feature-settings: 'liga';
        font-feature-settings: 'liga';
        -webkit-font-smoothing: antialiased;
        vertical-align: middle;
    }

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
    }
    
    /* Exclude icon fonts from global reset */
    .material-symbols-outlined,
    [class^="fas"],
    [class^="fab"] {
        margin: initial;
        padding: initial;
        box-sizing: content-box;
    }

    html,
    body {
        margin: 0;
        padding: 0;
    }

.builder-wrapper {
display: flex;
flex-direction: column;
height: calc(120vh - 140px);
overflow: hidden;
background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 50%, #f5f5f5 100%);
margin-top: -1px;
border-radius: 0;
border: none;
box-shadow: none;
transition: all 0.3s ease;
}

.builder-wrapper:hover {
box-shadow: none;
}

/* ============ TOP TOOLBAR ============ */
.builder-toolbar {
height: var(--toolbar-height);
background: white;
border-bottom: 1px solid #e8eef7;
display: flex;
align-items: center;
justify-content: center;
padding: 0 24px;
flex-shrink: 0;
z-index: 10;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
gap: 20px;
flex-wrap: nowrap;
max-width: 100%;
margin: 0 auto;
}

.toolbar-left {
display: flex;
align-items: center;
gap: 16px;
order: 1;
}

.toolbar-center {
display: flex;
align-items: center;
gap: 12px;
order: 2;
flex: 0 0 auto;
}

.toolbar-right {
display: flex;
align-items: center;
gap: 12px;
order: 3;
margin-left: auto;
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
width: 240px;
background: white;
border-right: 1px solid var(--gray-100);
display: flex;
flex-direction: column;
overflow: hidden;
flex-shrink: 0;
order: 1;
border-left: 1px solid var(--gray-100);
border-right: 1px solid var(--gray-100);
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

.block-item[draggable="true"] {
    cursor: move;
}

.block-item:active[draggable="true"] {
    cursor: grabbing;
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
background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f8f9fa 100%);
transition: all 0.2s ease;
order: 2;
align-items: center;
padding-top: 24px;
padding-bottom: 24px;
border-left: 1px solid #e8eef7;
border-right: 1px solid #e8eef7;
}

.builder-canvas.drag-over {
background: linear-gradient(to bottom, var(--puck-accent), rgba(2, 118, 255, 0.05));
}

.canvas-scroll-area {
flex: 1;
overflow-y: auto;
overflow-x: hidden;
display: flex;
justify-content: center;
align-items: flex-start;
width: 100%;
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
max-width: 800px;
background: white;
border-radius: 12px;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.12);
min-height: 500px;
transition: all 0.3s ease;
overflow: visible;
height: auto;
margin: 0 auto;
}

    .canvas-wrapper.tablet {
        max-width: 750px;
    }

    .canvas-wrapper.mobile {
        max-width: 480px;
    }

.canvas-header {
padding: 32px 24px 16px 24px;
border-bottom: none;
background: white;
}

/* Multi-page Form Navigation */
.form-pages-nav {
    margin-top: 16px;
    padding: 12px 0;
}

.pages-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 12px;
}

.page-tab {
    padding: 8px 16px;
    background: #f3f4f6;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.page-tab:hover {
    background: #e5e7eb;
    color: #374151;
}

.page-tab.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

.page-tab .page-name {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.page-tab .delete-page-btn {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.3);
    color: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.2s ease;
    padding: 0;
}

.page-tab .delete-page-btn:hover {
    background: rgba(239, 68, 68, 0.8);
    color: white;
}

.page-tab.active .delete-page-btn {
    background: rgba(255, 255, 255, 0.2);
}

.page-tab.active .delete-page-btn:hover {
    background: rgba(239, 68, 68, 1);
}

.add-page-btn {
    padding: 8px 16px;
    background: white;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.add-page-btn:hover {
    background: #f9fafb;
    border-color: #4f46e5;
    color: #4f46e5;
}

/* Custom Code Editor Styles */
.custom-code-editor {
    background: #1e1e1e;
    color: #d4d4d4;
    border: 2px solid #2d2d2d;
    border-radius: 8px;
    padding: 12px;
    font-family: 'Courier New', Consolas, monospace;
    font-size: 13px;
    line-height: 1.6;
    resize: vertical;
    transition: all 0.2s ease;
}

.custom-code-editor:focus {
    border-color: #4f46e5;
    outline: none;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.custom-code-editor::placeholder {
    color: #6a6a6a;
}

.apply-custom-design-btn,
.save-design-btn {
    width: 100%;
    padding: 12px 20px;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 12px;
}

.apply-custom-design-btn:hover,
.save-design-btn:hover {
    background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.apply-custom-design-btn:active,
.save-design-btn:active {
    transform: translateY(0);
}

/* Preview Modal */
.preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.preview-modal.active {
    display: flex;
}

.preview-modal-content {
    width: 90%;
    max-width: 1200px;
    height: 90vh;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;
}

.preview-modal-header {
    padding: 20px 24px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.preview-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #111827;
}

.preview-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s ease;
}

.preview-modal-close:hover {
    background: #e5e7eb;
    color: #111827;
}

.preview-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    background: #f9fafb;
}

.preview-modal-body iframe {
    width: 100%;
    height: 100%;
    border: none;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.canvas-form-name {
font-size: 32px;
font-weight: 500;
color: #202124;
letter-spacing: 0.3px;
border: none;
background: transparent;
padding: 0;
margin: 0;
width: 100%;
font-family: 'Manrope', sans-serif;
outline: none;
transition: color 0.2s;
}

.canvas-form-name:focus {
outline: none;
color: #202124;
}

.canvas-form-name::placeholder {
color: #9e9e9e;
}

.canvas-body {
padding: 24px 20px;
min-height: 400px;
max-height: none;
transition: all 0.2s cubic-bezier(0.2, 0, 0.38, 0.9);
overflow-y: visible;
height: auto;
border: 2px dashed transparent;
border-radius: 12px;
}

.canvas-body.drag-over {
border-color: #4f46e5 !important;
background: rgba(79, 70, 229, 0.05) !important;
box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.canvas-empty {
display: flex;
flex-direction: column;
align-items: center;
justify-content: center;
min-height: 450px;
border: 2px dashed #e0e0e0;
border-radius: 8px;
color: #9e9e9e;
text-align: center;
background: #fafafa;
transition: all 0.2s cubic-bezier(0.2, 0, 0.38, 0.9);
}

.canvas-empty:hover {
border-color: var(--gray-300);
background: var(--gray-100);
}

.canvas-empty-icon {
font-size: 64px;
margin-bottom: 20px;
opacity: 0.6;
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
        border-radius: 0;
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
width: 320px;
background: white;
border-left: 1px solid var(--gray-100);
display: flex;
flex-direction: column;
overflow: hidden;
flex-shrink: 0;
order: 3;
border-right: 1px solid var(--gray-100);
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
    opacity: 0;
    pointer-events: none;
}

.sortable-drag {
    opacity: 0.92;
    transform: rotate(1.5deg) scale(1.03) translateY(-2px);
    box-shadow:
        0 22px 65px rgba(0, 0, 0, 0.22),
        0 8px 24px rgba(79, 70, 229, 0.18),
        0 0 0 1px rgba(79, 70, 229, 0.08);
    z-index: 10000 !important;
    cursor: grabbing !important;
    backdrop-filter: blur(8px);
    transition: transform 120ms cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 180ms ease;
}

.sortable-drag * {
    cursor: grabbing !important;
}

.sortable-insert-indicator {
    height: 5px;
    background: linear-gradient(90deg, transparent 0%, #4f46e5 20%, #4f46e5 80%, transparent 100%);
    border-radius: 3px;
    margin: 12px 20px;
    position: relative;
    box-shadow: 0 0 24px rgba(79, 70, 229, 0.7);
    animation: indicator-pulse 0.9s ease-in-out infinite alternate;
    transform-origin: center;
}

.sortable-insert-indicator::before {
    content: '';
    position: absolute;
    left: -6px;
    top: -5px;
    width: 15px;
    height: 15px;
    background: #4f46e5;
    border-radius: 50%;
    box-shadow: 0 0 16px rgba(79, 70, 229, 0.8);
    animation: indicator-dot 0.7s ease-in-out infinite alternate;
}

.sortable-insert-indicator::after {
    content: '';
    position: absolute;
    right: -6px;
    top: -5px;
    width: 15px;
    height: 15px;
    background: #4f46e5;
    border-radius: 50%;
    box-shadow: 0 0 16px rgba(79, 70, 229, 0.8);
    animation: indicator-dot 0.7s ease-in-out infinite alternate;
    animation-delay: 0.15s;
}

.canvas-block {
    transition: transform 140ms cubic-bezier(0.25, 0.46, 0.45, 0.94),
                box-shadow 160ms ease,
                margin 180ms cubic-bezier(0.34, 1.56, 0.64, 1);
    will-change: transform, margin;
}

.canvas-block:hover {
    transform: translateY(-1px);
}

@keyframes indicator-pulse {
    0% {
        opacity: 0.65;
        transform: scaleX(0.85);
    }
    100% {
        opacity: 1;
        transform: scaleX(1.08);
    }
}
@keyframes indicator-dot {
    0% {
        transform: scale(0.8);
        opacity: 0.7;
    }
    100% {
        transform: scale(1.15);
        opacity: 1;
    }
}
/* Disable text selection only during drag operation */
body.sortable-dragging-active {
    user-select: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
}

/* ✅ Exception: Allow pointer events on dummy drop target */
.sortable-empty-dropzone {
    pointer-events: auto !important;
    display: block !important;
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

<body class="bg-gradient-to-br from-[#f9fafb] via-[#f3f4f6] to-[#ede9fe] font-body text-on-surface" style="background-attachment: fixed;">

    <nav class="app-shell-nav fixed top-0 right-0 z-50 flex items-center justify-between px-8 h-20 bg-gradient-to-r from-[#ffffff]/95 via-[#fafbfd]/95 to-[#f3f5fb]/95 backdrop-blur-2xl shadow-[0_8px_32px_rgba(11,28,48,0.08), 0_1px_0px_rgba(0,0,0,0.05)]" style="left: var(--app-sidebar-width, 16rem); transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
        <div class="flex items-center gap-6">
            <div class="flex items-center bg-gradient-to-r from-[#f0f4f9]/40 to-[#e8eef7]/40 px-4 py-2 rounded-xl gap-3 backdrop-blur-sm border border-[#c7c4d8]/20">
                <span class="material-symbols-outlined text-[#3525cd] text-[20px]">edit_square</span>
                <span class="text-sm text-[#464555] font-semibold"><?= $model->isNewRecord ? 'Create Form' : 'Update Form' ?></span>
            </div>
            <div class="w-px h-8 bg-gradient-to-b from-transparent via-[#c7c4d8]/30 to-transparent"></div>
            <span class="text-xs text-[#777587] font-medium tracking-wide">Form Builder</span>
            <div class="w-px h-8 bg-gradient-to-b from-transparent via-[#c7c4d8]/30 to-transparent"></div>
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">folder_open</span> Projects', ['project/index'], [
                'class' => 'text-[#464555] hover:text-[#3525cd] px-3 py-2 rounded-lg hover:bg-[#f8fafd] transition-all flex items-center gap-2 text-sm font-medium no-underline',
                'encode' => false
            ]) ?>
        </div>
        <div class="flex items-center gap-3">
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">list</span> Forms', ['form/index'], [
                'class' => 'bg-white text-[#464555] px-4 py-2 rounded-lg font-medium flex items-center gap-2 hover:bg-[#f8fafd] transition-all hover:shadow-[0_4px_12px_rgba(99,102,241,0.08)] active:scale-95 text-sm no-underline border border-[#e2e2e8] hover:border-[#d2d2d8]'
            ]) ?>
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> New Form', ['form/create'], [
                'class' => 'bg-gradient-to-r from-[#3525cd] to-[#0276ff] text-white px-5 py-2 rounded-lg font-semibold flex items-center gap-2 hover:shadow-[0_8px_20px_rgba(99,102,241,0.3)] transition-all hover:scale-105 active:scale-95 text-sm no-underline border border-[#0276ff]/20'
            ]) ?>
        </div>
    </nav>

    <?= $this->render('../layouts/_sidebar', ['activeMenu' => 'forms']) ?>

    <main class="app-shell-main pt-8 min-h-screen" style="padding-left: var(--app-sidebar-width, 16rem); transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
        <div class="max-w-7xl mx-auto px-6 py-6">

            <div class="builder-wrapper">
                <!-- TOOLBAR -->
                <div class="builder-toolbar">
                    <div class="toolbar-left">
                        <span class="toolbar-logo"><span class="material-symbols-outlined" style="font-size:18px;">bolt</span> Visual Builder</span>
                        <div class="toolbar-divider"></div>
                        <span style="font-size:14px;color:var(--gray-600);"><?= $model->isNewRecord ? 'New Page' : Html::encode($model->name) ?></span>
                    </div>

                    <div class="toolbar-center">
                        <!-- Database Table Selection -->
                        <?php
                        $tablesQuery = \app\models\DbTable::find()
                            ->where(['user_id' => Yii::$app->user->id])
                            ->orderBy(['id' => SORT_ASC]);
                        if (\app\components\ProjectSchema::supportsProjectContext()) {
                            $activeProjectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
                            $tablesQuery->andWhere(['project_id' => $activeProjectId]);
                        }
                        $tables = $tablesQuery->asArray()->all();
                        $hasTable = !empty($tables);
                        $selectedTableId = $model->hasAttribute('db_table_id')
                            ? (int)$model->getAttribute('db_table_id')
                            : (int)$model->table_id;
                        $selectedTableValue = $selectedTableId > 0 ? (string)$selectedTableId : '';
                        $insertToTableChecked = $model->hasAttribute('insert_to_table')
                            ? ((int)$model->getAttribute('insert_to_table') === 1)
                            : ($model->storage_type === 'database');
                        ?>
                        <span class="text-xs font-semibold text-[#4b5563] tracking-wide">Database Connection</span>
                        <select id="table-selector" class="zoom-select" title="Select table to auto-generate form fields" <?= !$hasTable ? 'disabled' : '' ?>>
                            <option value=""><span class="material-symbols-outlined" style="font-size:16px;margin-right:6px;">storage</span>Select a table...</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= $table['id'] ?>" data-name="<?= Html::encode($table['name']) ?>" <?= $selectedTableValue !== '' && $selectedTableId === (int)$table['id'] ? 'selected' : '' ?>>
                                    <span class="material-symbols-outlined" style="font-size:16px;margin-right:6px;">table_chart</span><?= Html::encode($table['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($hasTable): ?>
                            <button class="btn-toolbar" id="btn-auto-generate" title="Auto-generate form fields from table">
                                <span class="material-symbols-outlined" style="font-size:18px;">tune</span> Auto-Generate
                            </button>
                        <?php else: ?>
                            <button class="btn-toolbar" id="btn-auto-generate" disabled title="No tables available - Create a table first">
                                <span class="material-symbols-outlined" style="font-size:18px;">block</span> No Tables
                            </button>
                        <?php endif; ?>
                        <label class="flex items-center gap-2 text-xs font-medium text-[#374151]">
                            <input type="checkbox" id="insert-to-table-toggle" class="h-4 w-4 rounded border-gray-300 text-[#3525cd] focus:ring-[#3525cd]" <?= $insertToTableChecked ? 'checked' : '' ?>>
                            Insert langsung ke tabel database (bukan ke form_submissions)
                        </label>

                        <div class="toolbar-divider"></div>
                        <button class="device-btn active" data-device="desktop" title="Desktop"><span class="material-symbols-outlined" style="font-size:18px;">desktop_windows</span></button>
                        <button class="device-btn" data-device="tablet" title="Tablet"><span class="material-symbols-outlined" style="font-size:18px;">tablet_mac</span></button>
                        <button class="device-btn" data-device="mobile" title="Mobile"><span class="material-symbols-outlined" style="font-size:18px;">phone_android</span></button>
                        <div class="toolbar-divider"></div>
                        <select class="zoom-select" id="zoom-select">
                            <option value="100">100%</option>
                            <option value="75">75%</option>
                            <option value="50">50%</option>
                        </select>
                    </div>

        <div class="toolbar-right">
            <button class="btn-toolbar" id="btn-undo" title="Undo"><span class="material-symbols-outlined" style="font-size:18px;">undo</span></button>
            <button class="btn-toolbar" id="btn-redo" title="Redo"><span class="material-symbols-outlined" style="font-size:18px;">redo</span></button>
            <div class="toolbar-divider"></div>
            <?= Html::a('<span class="material-symbols-outlined" style="font-size:18px;">visibility</span> Preview', ['form/render', 'id' => $model->id], ['class' => 'btn-toolbar', 'id' => 'btn-preview', 'style' => $model->isNewRecord ? 'display:none' : '']) ?>
        </div>
    </div>

                <!-- MAIN -->
                <div class="builder-main">
                    <!-- RIGHT SIDEBAR - BLOCKS -->
                    <div class="builder-sidebar-left">
                        <div class="sidebar-search">
                            <input type="text" id="block-search" placeholder="🔍 Search blocks...">
                        </div>
                        <div class="sidebar-categories" id="blocks-container">

                            <!-- FORM FIELDS -->
                            <div class="block-category open" data-category="form">
                                <div class="block-category-header">
                                    <span class="block-category-title"><span class="material-symbols-outlined" style="font-size:16px;">note_add</span> Form Fields</span>
                                    <span class="block-category-arrow">▶</span>
                                </div>
                                <div class="block-category-items">
                                    <div class="block-item" draggable="true" data-type="text">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">short_text</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Short Answer</div>
                                            <div class="block-item-desc">Single line text</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="textarea">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">notes</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Paragraph</div>
                                            <div class="block-item-desc">Multi-line text</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="radio">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">radio_button_checked</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Multiple Choice</div>
                                            <div class="block-item-desc">Select one option</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="checkbox">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">check_box</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Checkboxes</div>
                                            <div class="block-item-desc">Select multiple</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="select">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">arrow_drop_down_circle</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Dropdown</div>
                                            <div class="block-item-desc">Select from list</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="number">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">pin</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Number</div>
                                            <div class="block-item-desc">Numeric input</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="email">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">email</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Email</div>
                                            <div class="block-item-desc">Email address</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="date">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">calendar_today</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Date</div>
                                            <div class="block-item-desc">Date picker</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="time">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">schedule</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Time</div>
                                            <div class="block-item-desc">Time picker</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="phone">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">phone</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Phone Number</div>
                                            <div class="block-item-desc">Phone input</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="url">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">language</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">URL</div>
                                            <div class="block-item-desc">Website link</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="file">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">upload_file</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">File Upload</div>
                                            <div class="block-item-desc">Attach files</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="rating">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">star_rate</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Rating</div>
                                            <div class="block-item-desc">Star rating</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="scale">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">linear_scale</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Linear Scale</div>
                                            <div class="block-item-desc">1 to 5 scale</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENT & LAYOUT -->
                            <div class="block-category open" data-category="content">
                                <div class="block-category-header">
                                    <span class="block-category-title"><span class="material-symbols-outlined" style="font-size:16px;">layers</span> Content</span>
                                    <span class="block-category-arrow">▶</span>
                                </div>
                                <div class="block-category-items">
                                    <div class="block-item" draggable="true" data-type="heading">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">dns</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Section Header</div>
                                            <div class="block-item-desc">Title & description</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="text_block">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">text_fields</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Text</div>
                                            <div class="block-item-desc">Add heading or text</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="image">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">image</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Image</div>
                                            <div class="block-item-desc">Add images</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="video">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">play_circle</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Video</div>
                                            <div class="block-item-desc">Embed YouTube/Vimeo</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="divider">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">remove</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Divider</div>
                                            <div class="block-item-desc">Separator line</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="page_break">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">last_page</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Page Break</div>
                                            <div class="block-item-desc">Multi-page form</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ADVANCED -->
                            <div class="block-category" data-category="advanced">
                                <div class="block-category-header">
                                    <span class="block-category-title"><span class="material-symbols-outlined" style="font-size:16px;">settings</span> Advanced</span>
                                    <span class="block-category-arrow">▶</span>
                                </div>
                                <div class="block-category-items">
                                    <div class="block-item" draggable="true" data-type="signature">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">edit</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Signature</div>
                                            <div class="block-item-desc">Digital signature</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="matrix">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">table_chart</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Matrix</div>
                                            <div class="block-item-desc">Grid of options</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="ranking">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">trending_up</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Ranking</div>
                                            <div class="block-item-desc">Rank items</div>
                                        </div>
                                    </div>
                                    <div class="block-item" draggable="true" data-type="toggle">
                                        <div class="block-item-icon"><span class="material-symbols-outlined" style="font-size:18px;">toggle_on</span></div>
                                        <div class="block-item-info">
                                            <div class="block-item-name">Toggle Switch</div>
                                            <div class="block-item-desc">Yes/No switch</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- CENTER CANVAS AREA -->
                    <div class="builder-canvas">
                        <div class="canvas-scroll-area">
                            <div class="canvas-wrapper" id="canvas-wrapper">
                                <?= Html::beginForm(['form/' . ($model->isNewRecord ? 'create' : 'update'), 'id' => $model->isNewRecord ? null : $model->id], 'post', ['id' => 'builder-form']) ?>

                                <div class="canvas-header">
                                    <input type="text" class="canvas-form-name" name="Form[name]" placeholder="Page title..." value="<?= Html::encode($model->name) ?>">
                                    
                                    <!-- Multi-page Form Navigation -->
                                    <div class="form-pages-nav" id="form-pages-nav">
                                        <div class="pages-tabs" id="pages-tabs">
                                            <!-- Pages will be rendered here -->
                                        </div>
                                        <button type="button" class="add-page-btn" id="add-page-btn" title="Add new page">
                                            <i class="fas fa-plus"></i> Add Page
                                        </button>
                                    </div>
                                </div>

                    <?php
                    $selectedTableId = $model->hasAttribute('db_table_id')
                        ? (int)$model->getAttribute('db_table_id')
                        : (int)$model->table_id;
                    $selectedTableValue = $selectedTableId > 0 ? (string)$selectedTableId : '';
                    $insertToTable = $model->hasAttribute('insert_to_table')
                        ? ((int)$model->getAttribute('insert_to_table') === 1)
                        : ($model->storage_type === 'database');
                    ?>
                    <?= Html::hiddenInput('Form[schema_js]', $model->isNewRecord ? '[]' : Html::encode($model->schema_js), ['id' => 'schema-js']) ?>
                    <?= Html::hiddenInput('Form[table_id]', $selectedTableValue, ['id' => 'table-id']) ?>
                    <?= Html::hiddenInput('Form[db_table_id]', $selectedTableValue, ['id' => 'db-table-id']) ?>
                    <?= Html::hiddenInput('Form[storage_type]', $insertToTable ? 'database' : 'json', ['id' => 'storage-type']) ?>
                    <?= Html::hiddenInput('Form[insert_to_table]', $insertToTable ? 1 : 0, ['id' => 'insert-to-table']) ?>
                    <?= Html::hiddenInput('form_pages', '', ['id' => 'form-pages-data']) ?>

                                <div class="canvas-body" id="canvas-body">
                                    <div class="canvas-empty" id="canvas-empty">
                                        <div class="canvas-empty-icon">🧩</div>
                                        <div class="canvas-empty-text">Drag & Drop Blocks Here</div>
                                        <div class="canvas-empty-hint">or click blocks from the right panel to add them</div>
                                    </div>
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
                    <button type="submit" formid="builder-form" id="btn-save" class="btn-toolbar btn-toolbar-primary"><i class="fas fa-save"></i> <?= $model->isNewRecord ? 'Publish Page' : 'Update Page' ?></button>
                </div>
            </div>
        </div>

                    <!-- RIGHT SIDEBAR - PROPERTIES -->
                    <div class="builder-sidebar-right">
                        <div class="properties-header">
                            <span class="material-symbols-outlined" style="font-size:18px;">tune</span> Properties
                        </div>
                        <div class="properties-tabs">
                            <div class="properties-tab active" data-tab="content">Content</div>
                            <div class="properties-tab" data-tab="style">Style</div>
                            <div class="properties-tab" data-tab="advanced">Advanced</div>
                            <div class="properties-tab" data-tab="custom-code">Custom UI/UX</div>
                            <div class="properties-tab" data-tab="json">Script JS</div>
                        </div>
                        <div class="properties-content">
                            <div id="props-empty" class="text-center text-muted py-5">
                                <div style="font-size:48px;margin-bottom:12px;"><i class="fas fa-crosshairs"></i></div>
                                <p>Select a block to edit</p>
                            </div>

                            <!-- Custom Code Editor Panel -->
                            <div id="props-custom-code" class="props-tab" style="display:none;">
                                <div class="property-section">
                                    <div class="property-section-title">
                                        <i class="fas fa-palette"></i> Custom Form Design
                                    </div>
                                    <p style="font-size:12px;color:#666;margin:8px 0;">
                                        Add custom HTML, CSS, and JavaScript to customize your form's UI/UX.
                                    </p>
                                    
                                    <div class="property-field">
                                        <label class="property-label">Custom CSS</label>
                                        <textarea class="property-textarea custom-code-editor" id="custom-css" 
                                            placeholder="/* Add your custom CSS here */
.form-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px;
}
.form-field {
    border-radius: 8px;
}"
                                            style="min-height:200px;font-family:'Courier New',monospace;font-size:12px;"></textarea>
                                    </div>
                                    
                                    <div class="property-field">
                                        <label class="property-label">Custom HTML (Before Form)</label>
                                        <textarea class="property-textarea custom-code-editor" id="custom-html-before" 
                                            placeholder="<!-- Add custom HTML before form -->
<div class='form-header'>
    <h1>Welcome to Our Form</h1>
    <p>Please fill out all required fields</p>
</div>"
                                            style="min-height:150px;font-family:'Courier New',monospace;font-size:12px;"></textarea>
                                    </div>
                                    
                                    <div class="property-field">
                                        <label class="property-label">Custom HTML (After Form)</label>
                                        <textarea class="property-textarea custom-code-editor" id="custom-html-after" 
                                            placeholder="<!-- Add custom HTML after form -->
<div class='form-footer'>
    <p>Thank you for your submission!</p>
</div>"
                                            style="min-height:150px;font-family:'Courier New',monospace;font-size:12px;"></textarea>
                                    </div>
                                    
                                    <div class="property-field">
                                        <label class="property-label">Custom JavaScript</label>
                                        <textarea class="property-textarea custom-code-editor" id="custom-js" 
                                            placeholder="// Add custom JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Your custom interactions
    console.log('Form loaded!');
});"
                                            style="min-height:200px;font-family:'Courier New',monospace;font-size:12px;"></textarea>
                                    </div>
                                    
                                    <button type="button" class="apply-custom-design-btn" id="apply-custom-design-btn">
                                        <i class="fas fa-magic"></i> Apply & Preview Design
                                    </button>
                                </div>
                                
                                <div class="property-section" style="margin-top:20px;">
                                    <div class="property-section-title">
                                        <i class="fas fa-save"></i> Save Design
                                    </div>
                                    <p style="font-size:12px;color:#666;margin:8px 0;">
                                        Save your custom design to apply it when publishing.
                                    </p>
                                    <button type="button" class="save-design-btn" id="save-design-btn">
                                        <i class="fas fa-save"></i> Save Custom Design
                                    </button>
                                </div>
                            </div>

                            <div id="props-content" class="props-tab" style="display:none;">
                                <div class="property-section">
                                    <div class="property-section-title">Basic</div>
                                    <div class="property-field">
                                        <label class="property-label">Label/Title</label>
                                        <input type="text" class="property-input" id="prop-label" placeholder="Enter label...">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Helper Text</label>
                                        <textarea class="property-textarea" id="prop-helper" placeholder="Additional guidance text..."></textarea>
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Placeholder</label>
                                        <input type="text" class="property-input" id="prop-placeholder" placeholder="e.g. Your name...">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Description</label>
                                        <textarea class="property-textarea" id="prop-desc" placeholder="Detailed description..."></textarea>
                                    </div>
                                </div>

                                <div class="property-section">
                                    <div class="property-section-title">Settings</div>
                                    <div class="property-field">
                                        <label class="property-checkbox"><input type="checkbox" id="prop-required"><span>Required field</span></label>
                                    </div>
                                    <div class="property-field">
                                        <label class="property-checkbox"><input type="checkbox" id="prop-readonly"><span>Read-only</span></label>
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Default Value</label>
                                        <input type="text" class="property-input" id="prop-default" placeholder="Initial value...">
                                    </div>
                                </div>

                                <div class="property-section">
                                    <div class="property-section-title">Validation</div>
                                    <div class="property-field">
                                        <label class="property-label">Min. Length</label>
                                        <input type="number" class="property-input" id="prop-min-length" placeholder="0" min="0">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Max. Length</label>
                                        <input type="number" class="property-input" id="prop-max-length" placeholder="No limit" min="0">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Pattern/Regex</label>
                                        <input type="text" class="property-input" id="prop-pattern" placeholder="^[a-zA-Z0-9]+$">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Error Message</label>
                                        <input type="text" class="property-input" id="prop-error-msg" placeholder="This field is invalid">
                                    </div>
                                </div>

                                <div class="property-section">
                                    <div class="property-section-title">Numeric</div>
                                    <div class="property-field">
                                        <label class="property-label">Minimum Value</label>
                                        <input type="number" class="property-input" id="prop-min-value" placeholder="No minimum">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Maximum Value</label>
                                        <input type="number" class="property-input" id="prop-max-value" placeholder="No maximum">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Decimal Places</label>
                                        <input type="number" class="property-input" id="prop-decimals" placeholder="0" min="0">
                                    </div>
                                </div>

                                <div class="property-section">
                                    <div class="property-section-title">Options</div>
                                    <div class="property-field">
                                        <label class="property-label">Multiple Select</label>
                                        <label class="property-checkbox"><input type="checkbox" id="prop-multiple"><span>Allow multiple selections</span></label>
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Option List</label>
                                        <textarea class="property-textarea" id="prop-options" placeholder="One option per line&#10;Option 1&#10;Option 2&#10;Option 3"></textarea>
                                    </div>
                                </div>

                                <div class="property-section">
                                    <div class="property-section-title">File & Media</div>
                                    <div class="property-field">
                                        <label class="property-label">URL/Link</label>
                                        <input type="text" class="property-input" id="prop-url" placeholder="https://...">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Image URL</label>
                                        <input type="text" class="property-input" id="prop-image" placeholder="https://...">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Video URL</label>
                                        <input type="text" class="property-input" id="prop-video" placeholder="https://youtube.com/...">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Accepted File Types</label>
                                        <input type="text" class="property-input" id="prop-accept" placeholder=".pdf, .docx, .jpg">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Max File Size (MB)</label>
                                        <input type="number" class="property-input" id="prop-max-filesize" placeholder="10" min="0">
                                    </div>
                                </div>

                                <div class="property-section">
                                    <div class="property-section-title">Scale & Rating</div>
                                    <div class="property-field">
                                        <label class="property-label">Scale From</label>
                                        <input type="number" class="property-input" id="prop-scale-from" placeholder="1" min="1">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Scale To</label>
                                        <input type="number" class="property-input" id="prop-scale-to" placeholder="5" min="1">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">Low Label</label>
                                        <input type="text" class="property-input" id="prop-scale-low" placeholder="Not satisfied">
                                    </div>
                                    <div class="property-field">
                                        <label class="property-label">High Label</label>
                                        <input type="text" class="property-input" id="prop-scale-high" placeholder="Very satisfied">
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
                                <div class="property-section" style="padding:0;">
                                    <!-- Mode Toggle -->
                                    <div style="display:flex;gap:8px;margin-bottom:16px;background:var(--gray-100);padding:8px;border-radius:8px;">
                                        <button id="btn-mode-object" class="btn-toolbar" style="flex:1;background:var(--primary);color:white;border:none;font-size:12px;font-weight:600;padding:8px 12px;border-radius:6px;">🎨 Object Mode</button>
                                        <button id="btn-mode-code" class="btn-toolbar" style="flex:1;background:var(--gray-200);color:var(--gray-600);border:none;font-size:12px;font-weight:600;padding:8px 12px;border-radius:6px;">⚡ Code Mode</button>
                                    </div>

                                    <!-- Object Mode Help -->
                                    <div id="help-object-mode" style="font-size:12px;color:var(--gray-600);margin-bottom:12px;line-height:1.5;padding:0 4px;">
                                        <strong>🎨 Object Mode:</strong> Define block properties as JavaScript object
                                    </div>

                                    <!-- Code Mode Help -->
                                    <div id="help-code-mode" style="display:none;font-size:12px;color:var(--warning);margin-bottom:12px;line-height:1.5;background:var(--warning-light);padding:12px;border-radius:8px;">
                                        <strong>⚡ Code Mode:</strong> Write executable JavaScript code<br>
                                        💡 Use <code style="background:var(--gray-200);padding:2px 6px;border-radius:4px;font-size:11px;">this</code> to access block data<br>
                                        💡 Return HTML string or DOM element to render
                                    </div>

                                    <!-- Code Editor -->
                                    <div style="position:relative;margin-bottom:12px;">
                                        <textarea id="prop-json" style="font-family:'Courier New', monospace;font-size:12px;height:280px;border:1px solid var(--gray-300);padding:12px;background:var(--gray-50);width:100%;resize:vertical;border-radius:8px;line-height:1.5;tab-size:2;" placeholder="{
  type: 'columns',
  label: 'Columns',
  columns: 3,
  content: 'Your content'
}" spellcheck="false"></textarea>
                                        <div style="position:absolute;top:8px;right:8px;font-size:10px;color:var(--gray-400);pointer-events:none;">JavaScript</div>
                                    </div>

                                    <!-- Code Mode Example -->
                                    <div id="code-example" style="display:none;background:var(--info-light);padding:12px;border-radius:8px;margin-bottom:12px;border-left:4px solid var(--info);">
                                        <div style="font-size:11px;font-weight:600;color:var(--info);margin-bottom:8px;">💡 Example - Custom Rendering:</div>
                                        <pre style="font-family:'Courier New',monospace;font-size:11px;margin:0;color:var(--gray-700);white-space:pre-wrap;line-height:1.5;background:var(--gray-50);padding:10px;border-radius:6px;">// Access block data
const { columns, content } = this;

// Create HTML
let html = '&lt;div style="display:grid;grid-template-columns:repeat(' + columns + ',1fr);gap:16px;"&gt;';
for (let i = 0; i &lt; columns; i++) {
  html += '&lt;div style="padding:10px;border:1px solid #ccc;"&gt;' + (content || 'Col ' + (i+1)) + '&lt;/div&gt;';
}
html += '&lt;/div&gt;';

// Return rendered HTML
return html;</pre>
                                    </div>

                                    <!-- Apply Button -->
                                    <button id="btn-apply-json" class="btn-toolbar" style="width:100%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;border:none;font-weight:600;padding:12px;border-radius:8px;font-size:13px;margin-bottom:8px;">⚡ Apply Changes</button>

                                    <!-- Delete Button -->
                                    <div id="props-delete-section" class="property-section" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--gray-200);">
                                        <button class="btn-toolbar w-100" style="border-color:var(--danger);color:var(--danger);background:var(--danger-light);" id="btn-delete-block">🗑️ Delete Block</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Initialize builder state from existing form schema when editing.
            $schemaData = $model->isNewRecord ? [] : json_decode((string) $model->schema_js, true);
            $pages = $schemaData['pages'] ?? null;
            $customDesign = $schemaData['customDesign'] ?? [];

            if (!$pages) {
                // Backward compatibility:
                // 1) Newer shape without pages: {blocks: [...], customDesign: {...}}
                // 2) Legacy shape: [...]
                if (isset($schemaData['blocks']) && is_array($schemaData['blocks'])) {
                    $blocksData = $schemaData['blocks'];
                } elseif (is_array($schemaData) && array_values($schemaData) === $schemaData) {
                    $blocksData = $schemaData;
                } else {
                    $blocksData = [];
                }
                $pages = [[
                    'id' => 'page_1',
                    'name' => 'Page 1',
                    'blocks' => $blocksData,
                ]];
            }

            $initialBlocks = $pages[0]['blocks'] ?? [];
            ?>
            <script>
                const initialFormPages = <?= json_encode($pages) ?>;
                const initialCustomDesign = <?= json_encode($customDesign) ?>;
                const initialBlocks = <?= json_encode($initialBlocks) ?>;
                const isEditMode = <?= $model->isNewRecord ? 'false' : 'true' ?>;

                // ===== GLOBAL HELPER FUNCTIONS =====
                // Define escapeHtml globally so it can be used by all functions
                function escapeHtml(str) {
                    const div = document.createElement('div');
                    div.textContent = str || '';
                    return div.innerHTML;
                }
                
                // ===== GLOBAL CORE VARIABLES =====
                // These must be global so initFormPages and other functions can access them
                let blocks = JSON.parse(JSON.stringify(initialBlocks || []));
                let selectedIndex = -1;
                let undoStack = [];
                let redoStack = [];
                let canvasBlocks = null;
                let schemaJson = null;
                let tableSelector = null;
                let tableIdInput = null;
                let btnAutoGenerate = null;
                
                // ===== GLOBAL UTILITY FUNCTIONS =====
                // Must be global to be accessible from all scopes
                
                // Notification system
                function showNotification(message, type = 'info', duration = 3000) {
                    const notification = document.createElement('div');
                    notification.className = `notification notification-${type}`;
                    notification.innerHTML = message;
                    
                    const colors = {
                        success: 'linear-gradient(135deg, #10b981, #059669)',
                        error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                        warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
                        info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                    };
                    
                    notification.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 16px 24px;
                        background: ${colors[type] || colors.info};
                        color: white;
                        border-radius: 12px;
                        font-weight: 600;
                        font-size: 14px;
                        z-index: 100000;
                        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                        animation: slideInRight 0.3s ease;
                        max-width: 400px;
                    `;
                    
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.style.animation = 'slideOutRight 0.3s ease';
                        setTimeout(() => notification.remove(), 300);
                    }, duration);
                }
                
                // Update empty state
                function updateEmptyState() {
                    const emptyState = document.getElementById('canvas-empty');
                    if (emptyState) {
                        emptyState.style.display = blocks.length === 0 ? 'flex' : 'none';
                    }
                }
                
                // Hide properties panel
                function hideProperties() {
                    const emptyState = document.getElementById('props-empty');
                    if (emptyState) emptyState.style.display = '';
                    document.querySelectorAll('.props-tab').forEach(function(el) {
                        el.style.display = 'none';
                    });
                    const deleteSection = document.getElementById('props-delete-section');
                    if (deleteSection) deleteSection.style.display = 'none';
                }
                
                // Fallback renderer before full interactive renderer is initialized.
                function renderBlockFallback(block, index) {
                    const div = document.createElement('div');
                    div.className = 'canvas-block';
                    div.dataset.index = index;
                    div.draggable = true;
                    
                    // Simple preview without helper functions
                    let previewHTML = '';
                    switch(block.type) {
                        case 'text-input':
                        case 'email':
                        case 'number':
                            previewHTML = `<input type="text" placeholder="${escapeHtml(block.placeholder || '')}" disabled style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;">`;
                            break;
                        case 'textarea':
                            previewHTML = `<textarea rows="3" placeholder="${escapeHtml(block.placeholder || '')}" disabled style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;"></textarea>`;
                            break;
                        case 'checkbox':
                        case 'radio':
                            previewHTML = `<div style="display:flex;flex-direction:column;gap:8px;"><label style="display:flex;align-items:center;gap:8px;"><input type="${block.type}" disabled> Option 1</label></div>`;
                            break;
                        default:
                            previewHTML = `<div style="color:#6b7280;font-size:12px;">${escapeHtml(block.type)}</div>`;
                    }
                    
                    div.innerHTML = `
                        <div class="canvas-block-header">
                            <span class="canvas-block-title">${escapeHtml(block.label || block.type)}</span>
                            <div class="canvas-block-actions">
                                <button type="button" class="btn-delete" onclick="deleteBlock(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="canvas-block-content">
                            ${previewHTML}
                        </div>
                    `;
                    
                    // Don't add event listeners if handlers don't exist
                    if (typeof handleDragStart === 'function') {
                        div.addEventListener('dragstart', handleDragStart);
                    }
                    if (typeof handleDragEnd === 'function') {
                        div.addEventListener('dragend', handleDragEnd);
                    }
                    
                    if (canvasBlocks) {
                        canvasBlocks.appendChild(div);
                    }
                }

                function renderBlock(block, index) {
                    return renderBlockFallback(block, index);
                }
                
                document.addEventListener('DOMContentLoaded', function() {
                    // ===== SAFE DEBUG LOGGER (Prevents console errors) =====
                    const DEBUG = false; // Set to false in production
                    const safeLog = {
                        log: function() {
                            if (DEBUG && typeof console !== 'undefined' && console.log) {
                                console.log.apply(console, arguments);
                            }
                        },
                        warn: function() {
                            if (DEBUG && typeof console !== 'undefined' && console.warn) {
                                console.warn.apply(console, arguments);
                            }
                        },
                        error: function() {
                            if (DEBUG && typeof console !== 'undefined' && console.error) {
                                console.error.apply(console, arguments);
                            }
                        },
                        info: function() {
                            if (DEBUG && typeof console !== 'undefined' && console.info) {
                                console.info.apply(console, arguments);
                            }
                        }
                    };

                    // Add animation styles if not present (notification styles already in global scope)
                    if (!document.getElementById('notification-styles')) {
                        const style = document.createElement('style');
                        style.id = 'notification-styles';
                        style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(400px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(400px); opacity: 0; }
                }
            `;
                        document.head.appendChild(style);
                    }

                    // ===== EDITOR MODE TOGGLE =====
                    let editorMode = 'object'; // 'object' or 'code'

                    const btnModeObject = document.getElementById('btn-mode-object');
                    const btnModeCode = document.getElementById('btn-mode-code');
                    const helpObjectMode = document.getElementById('help-object-mode');
                    const helpCodeMode = document.getElementById('help-code-mode');
                    const codeExample = document.getElementById('code-example');
                    const propJson = document.getElementById('prop-json');

                    function setEditorMode(mode) {
                        editorMode = mode;

                        if (mode === 'object') {
                            btnModeObject.style.background = 'var(--primary)';
                            btnModeObject.style.color = 'white';
                            btnModeCode.style.background = 'var(--gray-200)';
                            btnModeCode.style.color = 'var(--gray-600)';
                            helpObjectMode.style.display = 'block';
                            helpCodeMode.style.display = 'none';
                            codeExample.style.display = 'none';
                            propJson.placeholder = '{\n  type: \'columns\',\n  label: \'Columns\',\n  columns: 3,\n  content: \'Your content\'\n}';
                        } else {
                            btnModeCode.style.background = 'var(--warning)';
                            btnModeCode.style.color = 'white';
                            btnModeObject.style.background = 'var(--gray-200)';
                            btnModeObject.style.color = 'var(--gray-600)';
                            helpObjectMode.style.display = 'none';
                            helpCodeMode.style.display = 'block';
                            codeExample.style.display = 'block';
                            propJson.placeholder = '// Write your JavaScript code here\n// Use this to access block data\n// Return HTML string or DOM element\n\nconst { columns, content } = this;\n// ... your code ...\nreturn html;';
                        }
                    }

                    if (btnModeObject) {
                        btnModeObject.addEventListener('click', () => setEditorMode('object'));
                    }
                    if (btnModeCode) {
                        btnModeCode.addEventListener('click', () => setEditorMode('code'));
                    }

                    // ===== CORE SCRIPT =====
                    // Assign values to global variables (already declared globally)
                    blocks = JSON.parse(JSON.stringify(initialBlocks || []));
                    selectedIndex = -1;
                    undoStack = [];
                    redoStack = [];
                    let isInitialized = false; // Prevent double initialization

                    schemaJson = document.getElementById('schema-js');
                    tableSelector = document.getElementById('table-selector');
                    tableIdInput = document.getElementById('table-id');
                    const dbTableIdInput = document.getElementById('db-table-id');
                    const storageTypeInput = document.getElementById('storage-type');
                    const insertToTableInput = document.getElementById('insert-to-table');
                    const insertToTableToggle = document.getElementById('insert-to-table-toggle');
                    btnAutoGenerate = document.getElementById('btn-auto-generate');
                    canvasBlocks = document.getElementById('canvas-body');

                    if (!schemaJson || !canvasBlocks) {
                        safeLog.error('FATAL: Required elements missing!');
                        return;
                    }

                    if (insertToTableToggle && insertToTableInput) {
                        insertToTableToggle.checked = insertToTableInput.value === '1';
                        insertToTableToggle.addEventListener('change', function() {
                            const shouldInsert = this.checked;
                            insertToTableInput.value = shouldInsert ? '1' : '0';
                            if (storageTypeInput) {
                                storageTypeInput.value = shouldInsert ? 'database' : 'json';
                            }
                        });
                    }

                    // Initialize Sortable for canvas blocks with reordering support
                    // ==============================
                    // ✅ CUSTOM FORM BUILDER ENGINE (No GrapesJS conflicts)
                    // ==============================
                    
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

                    // ===== DRAG AND DROP FUNCTIONALITY =====
                    let draggedType = null;
                    let dragOverCanvas = false;

                    // Add dragstart event to all block items
                    document.querySelectorAll('.block-item').forEach(function(item) {
                        item.addEventListener('dragstart', function(e) {
                            draggedType = this.dataset.type;
                            e.dataTransfer.effectAllowed = 'copy';
                            e.dataTransfer.setData('text/plain', draggedType);

                            // Add visual feedback
                            this.style.opacity = '0.5';

                            safeLog.log('Drag started for block type:', draggedType);
                        });

                        item.addEventListener('dragend', function(e) {
                            this.style.opacity = '1';
                            draggedType = null;

                            // Remove drag over state from canvas
                            if (canvasBody) {
                                canvasBody.classList.remove('drag-over');
                            }
                            safeLog.log('Drag ended');
                        });
                    });

                    // Add drop zone events to canvas body
                    const canvasBody = document.getElementById('canvas-body');
                    if (canvasBody) {
                        canvasBody.addEventListener('dragover', function(e) {
                            e.preventDefault(); // Necessary to allow drop
                            e.dataTransfer.dropEffect = 'copy';
                            dragOverCanvas = true;

                            // Add visual feedback
                            this.classList.add('drag-over');
                            this.style.borderColor = '#4f46e5';
                            this.style.background = 'rgba(79, 70, 229, 0.05)';

                            // Highlight the nearest block position
                            highlightNearestBlockPosition(e.clientY);

                            safeLog.log('Drag over canvas');
                        });

                        canvasBody.addEventListener('dragleave', function(e) {
                            // Only remove if actually leaving the canvas body
                            const rect = this.getBoundingClientRect();
                            const x = e.clientX;
                            const y = e.clientY;

                            if (x < rect.left || x >= rect.right || y < rect.top || y >= rect.bottom) {
                                dragOverCanvas = false;
                                this.classList.remove('drag-over');
                                this.style.borderColor = '';
                                this.style.background = '';
                                
                                // Remove position indicator
                                removePositionIndicator();

                                safeLog.log('Drag left canvas');
                            }
                        });

                        canvasBody.addEventListener('drop', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            // Remove drag over styling
                            this.classList.remove('drag-over');
                            this.style.borderColor = '';
                            this.style.background = '';
                            dragOverCanvas = false;

                            // Get the dragged type
                            const blockType = e.dataTransfer.getData('text/plain') || draggedType;

                            if (blockType) {
                                safeLog.log('Dropped block type:', blockType);

                                // Calculate insert position
                                const insertIndex = getInsertIndex(e.clientY);

                                // Add the block at the calculated position
                                addBlockAtPosition(blockType, insertIndex);

                                // Remove position indicator
                                removePositionIndicator();

                                // Note: Notification already shown by autoFillCodeEditors in selectBlock
                            } else {
                                safeLog.warn('No block type found in drop event');
                            }
                        });
                    }

                    // Helper: Calculate which index to insert at based on mouse Y position
                    function getInsertIndex(mouseY) {
                        const canvasBlocks = document.querySelectorAll('#canvas-body .canvas-block');
                        
                        if (canvasBlocks.length === 0) {
                            return 0;
                        }

                        for (let i = 0; i < canvasBlocks.length; i++) {
                            const block = canvasBlocks[i];
                            const rect = block.getBoundingClientRect();
                            const blockMiddle = rect.top + rect.height / 2;
                            
                            // If mouse is above the middle of this block, insert before it
                            if (mouseY < blockMiddle) {
                                return i;
                            }
                        }
                        
                        // If mouse is below all blocks, add at the end
                        return blocks.length;
                    }

                    // Helper: Highlight the nearest block position
                    function highlightNearestBlockPosition(mouseY) {
                        removePositionIndicator();
                        
                        const canvasBlocks = document.querySelectorAll('#canvas-body .canvas-block');
                        const insertIndex = getInsertIndex(mouseY);
                        
                        // Create position indicator
                        const indicator = document.createElement('div');
                        indicator.id = 'drop-position-indicator';
                        indicator.style.cssText = `
                            height: 4px;
                            background: linear-gradient(90deg, #4f46e5, #7c3aed);
                            border-radius: 2px;
                            margin: 8px 16px;
                            box-shadow: 0 0 12px rgba(79, 70, 229, 0.5);
                            transition: all 0.2s ease;
                        `;
                        
                        const canvasBody = document.getElementById('canvas-body');
                        if (insertIndex === 0) {
                            // Insert at the beginning
                            if (canvasBlocks.length > 0) {
                                canvasBlocks[0].parentNode.insertBefore(indicator, canvasBlocks[0]);
                            } else {
                                canvasBody.appendChild(indicator);
                            }
                        } else if (insertIndex >= blocks.length) {
                            // Insert at the end
                            canvasBody.appendChild(indicator);
                        } else {
                            // Insert before specific block
                            if (canvasBlocks[insertIndex]) {
                                canvasBlocks[insertIndex].parentNode.insertBefore(indicator, canvasBlocks[insertIndex]);
                            }
                        }
                    }

                    // Helper: Remove position indicator
                    function removePositionIndicator() {
                        const indicator = document.getElementById('drop-position-indicator');
                        if (indicator) {
                            indicator.remove();
                        }
                    }

                    // Helper: Add block at specific position
                    function addBlockAtPosition(type, index) {
                        saveState();
                        const block = getDefaultBlock(type);
                        
                        // Insert at specific position
                        blocks.splice(index, 0, block);
                        
                        // Re-render all blocks
                        refreshAllBlocks();
                        
                        // Select the new block
                        selectBlock(index);
                    }

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
                        
                        // Auto-generate code template for new block
                        generateBlockCodeTemplate(block);
                    }

                    // Make addBlockAtPosition globally accessible if needed
                    window.addBlockAtPosition = addBlockAtPosition;

                    // Helper: Add block at specific position
                    function addBlockAtPosition(type, index) {
                        saveState();
                        const block = getDefaultBlock(type);
                        
                        // Insert at specific position
                        blocks.splice(index, 0, block);
                        
                        // Re-render all blocks
                        refreshAllBlocks();
                        
                        // Select the new block
                        selectBlock(index);
                        
                        // Auto-generate code template for new block
                        generateBlockCodeTemplate(block);
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
                        // If block has custom rendered HTML, use it
                        if (block.render) {
                            return `
                    <div class="block-header">
                        <div class="block-type-icon"><span class="material-symbols-outlined" style="font-size:18px;">code</span></div>
                        <div class="block-title">${escapeHtml(block.label || 'Custom Block')}</div>
                        <div class="block-actions">
                            <button class="block-action" onclick="moveBlockUp(${index})" title="Move Up"><i class="fas fa-chevron-up"></i></button>
                            <button class="block-action" onclick="moveBlockDown(${index})" title="Move Down"><i class="fas fa-chevron-down"></i></button>
                            <button class="block-action" onclick="duplicateBlock(${index})" title="Duplicate"><i class="fas fa-copy"></i></button>
                            <button class="block-action btn-delete" onclick="deleteBlock(${index})" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="block-body">
                        <div class="custom-rendered-content">${block.render}</div>
                    </div>
                `;
                        }

                        const typeIcons = {
                            container: '<span class="material-symbols-outlined" style="font-size:18px;">check_box_outline_blank</span>',
                            columns: '<span class="material-symbols-outlined" style="font-size:18px;">view_column</span>',
                            grid: '<span class="material-symbols-outlined" style="font-size:18px;">grid_view</span>',
                            section: '<span class="material-symbols-outlined" style="font-size:18px;">crop_landscape</span>',
                            divider: '<span class="material-symbols-outlined" style="font-size:18px;">horizontal_rule</span>',
                            spacer: '<span class="material-symbols-outlined" style="font-size:18px;">height</span>',
                            heading: '<span class="material-symbols-outlined" style="font-size:18px;">title</span>',
                            text: '<span class="material-symbols-outlined" style="font-size:18px;">text_fields</span>',
                            richtext: '<span class="material-symbols-outlined" style="font-size:18px;">description</span>',
                            list: '<span class="material-symbols-outlined" style="font-size:18px;">format_list_bulleted</span>',
                            quote: '<span class="material-symbols-outlined" style="font-size:18px;">format_quote</span>',
                            code: '<span class="material-symbols-outlined" style="font-size:18px;">code</span>',
                            image: '<span class="material-symbols-outlined" style="font-size:18px;">image</span>',
                            gallery: '<span class="material-symbols-outlined" style="font-size:18px;">image</span>',
                            video: '<span class="material-symbols-outlined" style="font-size:18px;">videocam</span>',
                            icon: '<span class="material-symbols-outlined" style="font-size:18px;">star</span>',
                            avatar: '<span class="material-symbols-outlined" style="font-size:18px;">account_circle</span>',
                            'text-input': '<span class="material-symbols-outlined" style="font-size:18px;">keyboard</span>',
                            textarea: '<span class="material-symbols-outlined" style="font-size:18px;">format_align_left</span>',
                            email: '<span class="material-symbols-outlined" style="font-size:18px;">mail</span>',
                            number: '<span class="material-symbols-outlined" style="font-size:18px;">tag</span>',
                            password: '<span class="material-symbols-outlined" style="font-size:18px;">lock</span>',
                            select: '<span class="material-symbols-outlined" style="font-size:18px;">list</span>',
                            checkbox: '<span class="material-symbols-outlined" style="font-size:18px;">check_box</span>',
                            radio: '<span class="material-symbols-outlined" style="font-size:18px;">radio_button_unchecked</span>',
                            date: '<span class="material-symbols-outlined" style="font-size:18px;">calendar_today</span>',
                            file: '<span class="material-symbols-outlined" style="font-size:18px;">attach_file</span>',
                            hidden: '<span class="material-symbols-outlined" style="font-size:18px;">visibility_off</span>',
                            submit: '<span class="material-symbols-outlined" style="font-size:18px;">check</span>',
                            'product-card': '<span class="material-symbols-outlined" style="font-size:18px;">shopping_bag</span>',
                            'product-grid': '<span class="material-symbols-outlined" style="font-size:18px;">grid_on</span>',
                            price: '<span class="material-symbols-outlined" style="font-size:18px;">payments</span>',
                            'add-to-cart': '<span class="material-symbols-outlined" style="font-size:18px;">shopping_cart</span>',
                            'product-badge': '<span class="material-symbols-outlined" style="font-size:18px;">local_offer</span>',
                            stars: '<span class="material-symbols-outlined" style="font-size:18px;">star</span>',
                            'stock-status': '<span class="material-symbols-outlined" style="font-size:18px;">check_box_outline_blank</span>',
                            'buy-now': '<span class="material-symbols-outlined" style="font-size:18px;">bolt</span>',
                            hero: '<span class="material-symbols-outlined" style="font-size:18px;">target</span>',
                            team: '<span class="material-symbols-outlined" style="font-size:18px;">group</span>',
                            testimonial: '<span class="material-symbols-outlined" style="font-size:18px;">format_quote</span>',
                            pricing: '<span class="material-symbols-outlined" style="font-size:18px;">diamond</span>',
                            faq: '<span class="material-symbols-outlined" style="font-size:18px;">help</span>',
                            stats: '<span class="material-symbols-outlined" style="font-size:18px;">bar_chart</span>',
                            features: '<span class="material-symbols-outlined" style="font-size:18px;">star</span>',
                            'contact-card': '<span class="material-symbols-outlined" style="font-size:18px;">phone</span>',
                            button: '<span class="material-symbols-outlined" style="font-size:18px;">radio_button_unchecked</span>',
                            link: '<span class="material-symbols-outlined" style="font-size:18px;">link</span>',
                            tabs: '<i class="fas fa-folder"></i>',
                            accordion: '<i class="fas fa-folder-open"></i>',
                            progress: '<span class="material-symbols-outlined" style="font-size:18px;">bar_chart</span>',
                            timeline: '<span class="material-symbols-outlined" style="font-size:18px;">calendar_today</span>',
                            newsletter: '<i class="fas fa-envelope-open"></i>',
                            countdown: '<i class="fas fa-hourglass-end"></i>',
                            alert: '<i class="fas fa-exclamation-triangle"></i>',
                            cta: '<span class="material-symbols-outlined" style="font-size:18px;">target</span>',
                            'social-links': '<i class="fas fa-share-alt"></i>',
                            'share-buttons': '<i class="fas fa-share-nodes"></i>',
                            embed: '<i class="fas fa-tv"></i>',
                            table: '<span class="material-symbols-outlined" style="font-size:18px;">table_chart</span>',
                            badge: '<span class="material-symbols-outlined" style="font-size:18px;">local_offer</span>',
                            chart: '<i class="fas fa-chart-line"></i>',
                            map: '<i class="fas fa-map"></i>',
                            html: '<span class="material-symbols-outlined" style="font-size:18px;">code</span>',
                            template: '<i class="fas fa-file-contract"></i>'
                        };

                        const preview = buildPreview(block);
                        const selected = index === selectedIndex ? ' selected' : '';

                        return `
            <div class="canvas-block-header">
                <div class="canvas-block-type">
                    <span class="drag-handle" title="Drag to reorder"><span class="material-symbols-outlined" style="font-size:18px;">more_vert</span></span>
                    <span>${typeIcons[block.type] || '<i class="fas fa-cube"></i>'}</span>
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
                    return block.src ? '<img src="' + escapeHtml(block.src) + '" style="width:100%;height:200px;object-fit:cover;border-radius:8px;" alt="' + escapeHtml(block.alt || '') + '">' : '<div class="preview-image"><span class="material-symbols-outlined" style="font-size:18px;">image</span></div>';
                case 'gallery':
                    return '<div class="preview-gallery">' + Array(block.images || 4).fill('<div class="preview-gallery-item"><span class="material-symbols-outlined" style="font-size:18px;">image</span></div>').join('') + '</div>';
                case 'video':
                    return '<div class="preview-video"><i class="fas fa-play"></i></div>';
                case 'icon':
                    return '<div class="preview-icon-box"><div class="preview-icon-circle" style="background:' + (block.color || 'var(--primary)') + ';">' + escapeHtml(block.icon || '✨') + '</div><div>' + escapeHtml(block.label || 'Icon') + '</div></div>';
                case 'avatar':
                    return '<div class="preview-team"><div class="preview-team-avatar"><span class="material-symbols-outlined" style="font-size:18px;">account_circle</span></div><div><div class="preview-team-name">' + escapeHtml(block.name || 'User') + '</div><div class="preview-team-role">' + escapeHtml(block.role || '') + '</div></div></div>';
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
                    return '<div style="padding:8px;background:var(--gray-100);border-radius:4px;font-size:12px;color:var(--gray-400);"><span class="material-symbols-outlined" style="font-size:18px;">visibility_off</span> Hidden: ' + escapeHtml(block.name || '') + '</div>';
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
                    return '<div class="preview-team"><div class="preview-team-avatar"><span class="material-symbols-outlined" style="font-size:18px;">account_circle</span></div><div><div class="preview-team-name">' + escapeHtml(block.name || 'Name') + '</div><div class="preview-team-role">' + escapeHtml(block.role || '') + '</div></div></div>';
                case 'testimonial':
                    return '<div class="preview-testimonial"><div class="preview-testimonial-text">' + escapeHtml(block.text || 'Testimonial') + '</div><div class="preview-testimonial-author">' + escapeHtml(block.author || '') + '</div></div>';
                case 'pricing':
                    const feats = (block.features || '').split('\n');
                    return '<div class="preview-pricing"><div class="preview-pricing-name">' + escapeHtml(block.name || 'Plan') + '</div><div class="preview-pricing-price">' + escapeHtml(block.currency || '$') + escapeHtml(block.price || '0') + '<small style="font-size:14px;color:var(--gray-400);">' + escapeHtml(block.period || '') + '</small></div><ul class="preview-pricing-features">' + feats.map(function(f) {
                        return '<li><i class="fas fa-check" style="color:var(--success);margin-right:6px;"></i>' + escapeHtml(f.trim()) + '</li>';
                    }).join('') + '</ul><button class="preview-button">Choose Plan</button></div>';
                case 'faq':
                    return '<div class="preview-faq-item"><div class="preview-faq-question"><span class="material-symbols-outlined" style="font-size:18px;">help</span> ' + escapeHtml(block.question || 'Question?') + '</div><div class="preview-faq-answer">' + escapeHtml(block.answer || 'Answer') + '</div></div>';
                case 'stats':
                    return '<div style="text-align:center;padding:20px;"><div class="preview-stat-number">' + escapeHtml(block.number || '0') + escapeHtml(block.suffix || '') + '</div><div class="preview-stat-label">' + escapeHtml(block.label || '') + '</div></div>';
                case 'features':
                    return '<div style="display:flex;gap:12px;align-items:start;padding:16px;border:1px solid var(--gray-200);border-radius:12px;"><div style="font-size:24px;color:var(--primary);">' + (block.icon || '<span class="material-symbols-outlined" style="font-size:18px;">star</span>') + '</div><div><div style="font-weight:600;">' + escapeHtml(block.title || 'Feature') + '</div><div style="font-size:13px;color:var(--gray-400);">' + escapeHtml(block.description || '') + '</div></div></div>';
                case 'contact-card':
                    const iconMap = {
                        'phone': '<span class="material-symbols-outlined" style="font-size:18px;">phone</span>',
                        'star': '<span class="material-symbols-outlined" style="font-size:18px;">star</span>',
                        'mail': '<span class="material-symbols-outlined" style="font-size:18px;">mail</span>',
                        'location': '<i class="fas fa-map-marker-alt"></i>'
                    };
                    return '<div class="preview-contact-card"><div class="preview-contact-icon">' + (iconMap[block.icon] || '<span class="material-symbols-outlined" style="font-size:18px;">phone</span>') + '</div><div><div style="font-weight:600;">' + escapeHtml(block.title || '') + '</div><div style="font-size:13px;color:var(--gray-400);">' + escapeHtml(block.info || '') + '</div></div></div>';
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
                        return '<div class="preview-social-icon">' + (icons[p.trim().toLowerCase()] || '<span class="material-symbols-outlined" style="font-size:18px;">link</span>') + '</div>';
                    }).join('') + '</div>';
                case 'share-buttons':
                    return '<div style="display:flex;gap:8px;"><div class="preview-social-icon"><i class="fab fa-facebook"></i></div><div class="preview-social-icon"><i class="fab fa-twitter"></i></div><div class="preview-social-icon"><i class="fab fa-linkedin"></i></div></div>';
                case 'embed':
                    return '<div style="height:200px;background:var(--gray-900);border-radius:8px;display:flex;align-items:center;justify-content:center;color:white;font-size:32px;"><span class="material-symbols-outlined" style="font-size:18px;">videocam</span> Embed</div>';
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
                    return '<div style="padding:20px;border:2px dashed var(--gray-300);border-radius:8px;text-align:center;color:var(--gray-400);"><span class="material-symbols-outlined" style="font-size:18px;">crop_landscape</span> Template: ' + (block.templateId || 'None') + '</div>';
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

                    // Expose full interactive renderer globally so edit-mode initializer
                    // (outside this closure) always uses the same renderer as create-mode.
                    window.renderBlock = renderBlock;

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
                        
                        // Auto-generate code template for selected block
                        generateBlockCodeTemplate(blocks[index]);
                    }

                    // ===== AUTO-GENERATE BLOCK CODE TEMPLATES =====
                    function generateBlockCodeTemplate(block) {
                        if (!block) return;
                        
                        const blockType = block.type;
                        const blockLabel = block.label || 'Block';
                        const fieldName = block.name || blockLabel.toLowerCase().replace(/\s+/g, '_');
                        
                        // Code templates for each block type
                        const templates = {
                            // INPUT FIELDS
                            'text-input': {
                                html: `<!-- Text Input: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <input 
        type="text" 
        id="${fieldName}" 
        name="${fieldName}" 
        class="form-control" 
        placeholder="${block.placeholder || 'Enter text...'}"
        ${block.required ? 'required' : ''}
    >
    ${block.helper ? `<small class="form-text text-muted">${block.helper}</small>` : ''}
</div>`,
                                css: `/* Text Input: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.form-field-${fieldName}:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.form-field-${fieldName} .form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
    font-size: 14px;
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-field-${fieldName} .form-text {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
}`,
                                js: `// Text Input: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('${fieldName}');
    
    if (input) {
        // Real-time validation
        input.addEventListener('input', function() {
            const value = this.value;
            
            // Example: Character count
            const charCount = value.length;
            console.log('${blockLabel}: ' + charCount + ' characters');
            
            // Add your custom validation here
            if (value.length > 0) {
                this.style.borderColor = '#10b981'; // Green when has value
            } else {
                this.style.borderColor = '#e5e7eb'; // Default when empty
            }
        });
        
        // Focus effect
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    }
});`,
                            },
                            
                            'textarea': {
                                html: `<!-- Textarea: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <textarea 
        id="${fieldName}" 
        name="${fieldName}" 
        class="form-control" 
        rows="${block.rows || 4}"
        placeholder="${block.placeholder || 'Enter your message...'}"
        ${block.required ? 'required' : ''}
    ></textarea>
    ${block.helper ? `<small class="form-text text-muted">${block.helper}</small>` : ''}
</div>`,
                                css: `/* Textarea: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-field-${fieldName} .form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}`,
                                js: `// Textarea: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('${fieldName}');
    
    if (textarea) {
        // Auto-resize based on content
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Character count
            const charCount = this.value.length;
            console.log('${blockLabel}: ' + charCount + ' characters');
        });
    }
});`,
                            },
                            
                            'email': {
                                html: `<!-- Email Input: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <input 
        type="email" 
        id="${fieldName}" 
        name="${fieldName}" 
        class="form-control" 
        placeholder="${block.placeholder || 'example@email.com'}"
        ${block.required ? 'required' : ''}
    >
</div>`,
                                css: `/* Email Input: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-field-${fieldName} .form-control:invalid:not(:placeholder-shown) {
    border-color: #ef4444;
}`,
                                js: `// Email Input: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('${fieldName}');
    
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const email = this.value;
            
            // Email validation
            const emailRegex = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.setCustomValidity('Please enter a valid email address');
                this.style.borderColor = '#ef4444';
            } else {
                this.setCustomValidity('');
                if (email) {
                    this.style.borderColor = '#10b981';
                }
            }
        });
    }
});`,
                            },
                            
                            'number': {
                                html: `<!-- Number Input: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <input 
        type="number" 
        id="${fieldName}" 
        name="${fieldName}" 
        class="form-control" 
        placeholder="${block.placeholder || '0'}"
        min="${block.min || '0'}"
        max="${block.max || '100'}"
        ${block.required ? 'required' : ''}
    >
</div>`,
                                css: `/* Number Input: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}`,
                                js: `// Number Input: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const numberInput = document.getElementById('${fieldName}');
    
    if (numberInput) {
        numberInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            const min = parseInt(this.min);
            const max = parseInt(this.max);
            
            if (value < min) {
                this.setCustomValidity('Value is too small');
                this.style.borderColor = '#ef4444';
            } else if (value > max) {
                this.setCustomValidity('Value is too large');
                this.style.borderColor = '#ef4444';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '#10b981';
            }
        });
    }
});`,
                            },
                            
                            // SELECTION FIELDS
                            'checkbox': {
                                html: `<!-- Checkbox: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label class="form-label">${blockLabel}</label>
    <div class="checkbox-group">
        ${(block.options || 'Option 1\\nOption 2').split('\\n').map((opt, i) => `
        <label class="checkbox-item">
            <input type="checkbox" name="${fieldName}[]" value="${opt.trim()}">
            <span class="checkbox-label">${opt.trim()}</span>
        </label>`).join('\\n')}
    </div>
</div>`,
                                css: `/* Checkbox: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
}

.form-field-${fieldName} .form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
    color: #374151;
}

.form-field-${fieldName} .checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-field-${fieldName} .checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .checkbox-item:hover {
    background: #f3f4f6;
}

.form-field-${fieldName} .checkbox-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #4f46e5;
}

.form-field-${fieldName} .checkbox-label {
    font-size: 14px;
    color: #374151;
}`,
                                js: `// Checkbox: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="${fieldName}[]"]');
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const checked = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            console.log('${blockLabel} selected:', checked);
            
            // Add animation when checked
            if (this.checked) {
                this.parentElement.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.parentElement.style.transform = 'scale(1)';
                }, 200);
            }
        });
    });
});`,
                            },
                            
                            'radio': {
                                html: `<!-- Radio: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label class="form-label">${blockLabel}</label>
    <div class="radio-group">
        ${(block.options || 'Option 1\\nOption 2').split('\\n').map((opt, i) => `
        <label class="radio-item">
            <input type="radio" name="${fieldName}" value="${opt.trim()}" ${i === 0 ? 'checked' : ''}>
            <span class="radio-label">${opt.trim()}</span>
        </label>`).join('\\n')}
    </div>
</div>`,
                                css: `/* Radio: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
}

.form-field-${fieldName} .radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-field-${fieldName} .radio-item {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .radio-item:hover {
    background: #f3f4f6;
}

.form-field-${fieldName} .radio-item input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #4f46e5;
}

.form-field-${fieldName} .radio-label {
    font-size: 14px;
    color: #374151;
}`,
                                js: `// Radio: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="${fieldName}"]');
    
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            console.log('${blockLabel} selected:', this.value);
            
            // Add highlight animation
            this.parentElement.style.background = '#e0e7ff';
            setTimeout(() => {
                this.parentElement.style.background = 'transparent';
            }, 500);
        });
    });
});`,
                            },
                            
                            'select': {
                                html: `<!-- Select: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <select id="${fieldName}" name="${fieldName}" class="form-control" ${block.multiple ? 'multiple' : ''}>
        <option value="">-- Select an option --</option>
        ${(block.options || 'Option 1\\nOption 2').split('\\n').map(opt => `
        <option value="${opt.trim()}">${opt.trim()}</option>`).join('\\n')}
    </select>
</div>`,
                                css: `/* Select: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-field-${fieldName} .form-control option {
    padding: 8px;
}`,
                                js: `// Select: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('${fieldName}');
    
    if (select) {
        select.addEventListener('change', function() {
            const selectedValue = this.value;
            console.log('${blockLabel} selected:', selectedValue);
            
            // Add animation
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    }
});`,
                            },
                            
                            // SPECIAL FIELDS
                            'file': {
                                html: `<!-- File Upload: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <div class="file-upload-wrapper">
        <input 
            type="file" 
            id="${fieldName}" 
            name="${fieldName}" 
            class="form-control"
            ${block.required ? 'required' : ''}
        >
        <div class="file-upload-preview"></div>
    </div>
</div>`,
                                css: `/* File Upload: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
}

.form-field-${fieldName} .file-upload-wrapper {
    position: relative;
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 24px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-field-${fieldName} .form-control:hover {
    border-color: #4f46e5;
    background: #e0e7ff;
}

.form-field-${fieldName} .file-upload-preview {
    margin-top: 12px;
}`,
                                js: `// File Upload: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('${fieldName}');
    const preview = fileInput?.parentElement.querySelector('.file-upload-preview');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Show file info
                const fileInfo = \`
                    <div style="padding: 12px; background: #f0fdf4; border-radius: 8px; margin-top: 12px;">
                        <p style="margin: 0; font-weight: 600;">\${file.name}</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280;">
                            Size: \${(file.size / 1024).toFixed(2)} KB
                        </p>
                    </div>
                \`;
                
                if (preview) {
                    preview.innerHTML = fileInfo;
                }
                
                console.log('${blockLabel}:', file.name);
            }
        });
    }
});`,
                            },
                            
                            'date': {
                                html: `<!-- Date Picker: ${blockLabel} -->
<div class="form-group form-field-${fieldName}">
    <label for="${fieldName}" class="form-label">${blockLabel}</label>
    <input 
        type="date" 
        id="${fieldName}" 
        name="${fieldName}" 
        class="form-control"
        ${block.required ? 'required' : ''}
    >
</div>`,
                                css: `/* Date Picker: ${blockLabel} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
}

.form-field-${fieldName} .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-field-${fieldName} .form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}`,
                                js: `// Date Picker: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('${fieldName}');
    
    if (dateInput) {
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            console.log('${blockLabel} selected:', selectedDate.toLocaleDateString());
            
            // Add animation
            this.parentElement.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.parentElement.style.transform = 'scale(1)';
            }, 200);
        });
    }
});`,
                            },
                            
                            // CONTENT BLOCKS
                            'heading': {
                                html: `<!-- Heading: ${blockLabel} -->
<div class="form-heading form-field-${fieldName}">
    <${block.level || 'h2'} class="heading-text">
        ${block.content || blockLabel}
    </${block.level || 'h2'}>
</div>`,
                                css: `/* Heading: ${blockLabel} */
.form-field-${fieldName} {
    margin: 32px 0;
    padding: 24px;
    text-align: ${block.align || 'left'};
}

.form-field-${fieldName} .heading-text {
    font-size: ${block.level === 'h1' ? '32px' : block.level === 'h3' ? '24px' : '28px'};
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1.3;
}`,
                                js: `// Heading: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const heading = document.querySelector('.form-field-${fieldName}');
    
    if (heading) {
        // Add fade-in animation
        heading.style.opacity = '0';
        heading.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            heading.style.transition = 'all 0.6s ease';
            heading.style.opacity = '1';
            heading.style.transform = 'translateY(0)';
        }, 300);
    }
});`,
                            },
                            
                            'image': {
                                html: `<!-- Image: ${blockLabel} -->
<div class="form-image form-field-${fieldName}">
    <img 
        src="${block.src || 'https://via.placeholder.com/600x400'}" 
        alt="${block.alt || blockLabel}"
        class="image-wrapper"
    >
    ${block.caption ? `<p class="image-caption">${block.caption}</p>` : ''}
</div>`,
                                css: `/* Image: ${blockLabel} */
.form-field-${fieldName} {
    margin: 24px 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.form-field-${fieldName} .image-wrapper {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.3s ease;
}

.form-field-${fieldName}:hover .image-wrapper {
    transform: scale(1.05);
}

.form-field-${fieldName} .image-caption {
    margin: 12px 0 0;
    padding: 12px;
    text-align: center;
    font-size: 14px;
    color: #6b7280;
    background: #f9fafb;
}`,
                                js: `// Image: ${blockLabel}
document.addEventListener('DOMContentLoaded', function() {
    const imageContainer = document.querySelector('.form-field-${fieldName}');
    const img = imageContainer?.querySelector('img');
    
    if (img) {
        // Lazy loading effect
        img.addEventListener('load', function() {
            this.style.opacity = '0';
            this.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                this.style.opacity = '1';
            }, 100);
        });
        
        // Click to zoom
        imageContainer?.addEventListener('click', function() {
            this.style.cursor = 'zoom-in';
            console.log('${blockLabel} clicked');
        });
    }
});`,
                            }
                        };
                        
                        // Get template for block type, fallback to generic template
                        const template = templates[blockType] || generateGenericTemplate(block);
                        
                        // Auto-fill the custom code editors if Custom UI/UX tab is active
                        autoFillCodeEditors(template, blockType, blockLabel);
                    }

                    // Generic template for block types without specific templates
                    function generateGenericTemplate(block) {
                        const fieldName = block.name || block.label.toLowerCase().replace(/\s+/g, '_');
                        
                        return {
                            html: `<!-- ${block.label || block.type} -->
<div class="form-group form-field-${fieldName}">
    <label class="form-label">${block.label || block.type}</label>
    <div class="form-content">
        <!-- Add your custom content here -->
    </div>
</div>`,
                            css: `/* ${block.label || block.type} */
.form-field-${fieldName} {
    margin-bottom: 24px;
    padding: 16px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.form-field-${fieldName}:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}`,
                            js: `// ${block.label || block.type}
document.addEventListener('DOMContentLoaded', function() {
    const element = document.querySelector('.form-field-${fieldName}');
    
    if (element) {
        console.log('${block.label || block.type} loaded');
        
        // Add your custom interactions here
        element.addEventListener('click', function() {
            console.log('${block.label || block.type} clicked');
        });
    }
});`
                        };
                    }

                    function appendUniqueSnippet(currentValue, snippet) {
                        const base = (currentValue || '').trim();
                        const next = (snippet || '').trim();

                        if (!next) return base;
                        if (!base) return next;

                        // Prevent duplicate template blocks from being appended repeatedly.
                        if (base.includes(next)) return base;

                        return base + '\n\n' + next;
                    }

                    // Auto-fill code editors with template
                    function autoFillCodeEditors(template, blockType, blockLabel) {
                        const cssEditor = document.getElementById('custom-css');
                        const htmlBeforeEditor = document.getElementById('custom-html-before');
                        const htmlAfterEditor = document.getElementById('custom-html-after');
                        const jsEditor = document.getElementById('custom-js');

                        if (!cssEditor || !htmlBeforeEditor || !htmlAfterEditor || !jsEditor) return;

                        // Get current values
                        const currentCSS = cssEditor.value;
                        const currentHTMLBefore = htmlBeforeEditor.value;
                        const currentHTMLAfter = htmlAfterEditor.value;
                        const currentJS = jsEditor.value;

                        // Check if editors are empty
                        const isEmpty = !currentCSS && !currentHTMLBefore && !currentHTMLAfter && !currentJS;

                        // Automatically fill or append unique snippets.
                        if (isEmpty) {
                            cssEditor.value = (template.css || '').trim();
                            // Keep custom HTML editors empty by default to avoid duplicate form markup.
                            htmlBeforeEditor.value = '';
                            htmlAfterEditor.value = '';
                            jsEditor.value = (template.js || '').trim();
                        } else {
                            cssEditor.value = appendUniqueSnippet(currentCSS, template.css);
                            // Do not auto-append generated HTML to prevent duplicated form output.
                            htmlBeforeEditor.value = currentHTMLBefore;
                            htmlAfterEditor.value = currentHTMLAfter;
                            jsEditor.value = appendUniqueSnippet(currentJS, template.js);
                        }

                        // Show notification
                        showNotification(
                            `✓ Code for "${blockLabel}" auto-generated & added!`,
                            'success',
                            2000
                        );

                        // Highlight the custom code tab to show it has content
                        const tab = document.querySelector('[data-tab="custom-code"]');
                        if (tab) {
                            tab.style.background = 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)';
                            tab.style.color = 'white';
                            tab.style.position = 'relative';
                            
                            // Add a small badge indicator
                            if (!tab.querySelector('.code-badge')) {
                                const badge = document.createElement('span');
                                badge.className = 'code-badge';
                                badge.textContent = '✓';
                                badge.style.cssText = `
                                    position: absolute;
                                    top: -5px;
                                    right: -5px;
                                    background: #10b981;
                                    color: white;
                                    width: 18px;
                                    height: 18px;
                                    border-radius: 50%;
                                    font-size: 10px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-weight: bold;
                                `;
                                tab.style.position = 'relative';
                                tab.appendChild(badge);
                            }
                            
                            setTimeout(() => {
                                tab.style.background = '';
                                tab.style.color = '';
                            }, 2000);
                        }
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
                        const canvasEmpty = document.getElementById('canvas-empty');
                        if (canvasEmpty) {
                            canvasEmpty.style.display = blocks.length === 0 ? '' : 'none';
                        }
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
                                if (jsonArea) jsonArea.value = objectToJavaScript(blocks[selectedIndex]);
                            }
                        });
                    });

                    // APPLY JAVASCRIPT/OBJECT BUTTON
                    const applyJsonBtn = document.getElementById('btn-apply-json');
                    if (applyJsonBtn) {
                        applyJsonBtn.addEventListener('click', function() {
                            if (selectedIndex < 0) {
                                alert('🔴 Please select a block first!');
                                return;
                            }

                            const code = document.getElementById('prop-json').value;

                            if (!code.trim()) {
                                alert('⚠️ Please enter some properties or code!');
                                return;
                            }

                            try {
                                let parsed;

                                if (editorMode === 'code') {
                                    // CODE MODE: Execute JavaScript code
                                    parsed = executeJavaScriptCode(code, blocks[selectedIndex]);
                                } else {
                                    // OBJECT MODE: Parse as object
                                    parsed = parseJavaScript(code);
                                }

                                // Merge with existing block properties
                                blocks[selectedIndex] = Object.assign({}, blocks[selectedIndex], parsed);

                                // If code mode and result has render function or HTML, store it
                                if (editorMode === 'code') {
                                    blocks[selectedIndex].customCode = code;
                                }

                                saveState();
                                refreshAllBlocks();
                                selectBlock(selectedIndex);

                                // Show success notification
                                const modeLabel = editorMode === 'code' ? 'Code' : 'Properties';
                                showNotification('✓ ' + modeLabel + ' applied successfully!', 'success');
                            } catch (e) {
                                // Show detailed error
                                const errorMessage = e.message.replace(/\n/g, '<br>');
                                showNotification('<strong>❌ Invalid Input</strong><br><br>' + errorMessage, 'error', 6000);
                            }
                        });
                    }

                    // Helper: Execute JavaScript code with sandboxed document
                    function executeJavaScriptCode(code, blockData) {
                        // Create a sandbox container
                        const sandbox = document.createElement('div');
                        sandbox.style.display = 'none';
                        document.body.appendChild(sandbox);

                        // Track created elements
                        let createdElements = [];
                        let lastReturnedValue = null;

                        try {
                            // Override document methods to capture DOM creation
                            const originalCreateElement = document.createElement;
                            const originalBodyAppendChild = document.body.appendChild;
                            const originalAppendChild = Element.prototype.appendChild;
                            const originalInnerHTML = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');

                            // Mock createElement to track and redirect to sandbox
                            document.createElement = function(tagName) {
                                const element = originalCreateElement.call(document, tagName);
                                createdElements.push(element);

                                // Override appendChild to add to sandbox instead of body
                                const originalAppendChildMethod = element.appendChild.bind(element);
                                element.appendChild = function(child) {
                                    // If trying to append to body, redirect to sandbox
                                    if (this === document.body) {
                                        return sandbox.appendChild(child);
                                    }
                                    return originalAppendChildMethod(child);
                                };

                                return element;
                            };

                            // Mock document.body.appendChild to add to sandbox
                            document.body.appendChild = function(child) {
                                return sandbox.appendChild(child);
                            };

                            // Execute the code with blockData as 'this'
                            const fn = new Function(code);
                            const boundFn = fn.bind(blockData);
                            const result = boundFn();
                            lastReturnedValue = result;

                            // Restore original methods
                            document.createElement = originalCreateElement;
                            document.body.appendChild = originalBodyAppendChild;
                            Element.prototype.appendChild = originalAppendChild;

                            // Determine what to return
                            if (typeof result === 'string') {
                                // Returned HTML string
                                return {
                                    render: result,
                                    customCode: code
                                };
                            } else if (result instanceof Element) {
                                // Returned DOM element
                                return {
                                    render: result.outerHTML,
                                    customCode: code
                                };
                            } else if (result && typeof result === 'object') {
                                // Returned object with properties
                                return result;
                            } else if (sandbox.innerHTML) {
                                // Capture sandbox content
                                return {
                                    render: sandbox.innerHTML,
                                    customCode: code
                                };
                            } else {
                                throw new Error('Code did not return a renderable result. Return HTML string, DOM element, or object with render property.');
                            }
                        } finally {
                            // Cleanup sandbox
                            document.body.removeChild(sandbox);
                        }
                    }

                    // Helper: Convert object to JavaScript object literal string
                    function objectToJavaScript(obj) {
                        return JSON.stringify(obj, null, 2)
                            .replace(/"([^"]+)":/g, '$1:')
                            .replace(/"([^"]*?)"/g, "'$1'");
                    }

                    // Helper: Parse JavaScript object (supports JS syntax, JSON, and even code with functions)
                    function parseJavaScript(code) {
                        // Clean up the code
                        code = code.trim();

                        if (!code) {
                            throw new Error('❌ Empty input! Please enter a JavaScript object.');
                        }

                        // VALIDATION: Detect and block executable code patterns
                        const disallowedPatterns = [{
                                pattern: /document\./gi,
                                name: 'document.*',
                                hint: 'DOM manipulation is not allowed'
                            },
                            {
                                pattern: /window\./gi,
                                name: 'window.*',
                                hint: 'Browser API is not allowed'
                            },
                            {
                                pattern: /\.appendChild\(/gi,
                                name: '.appendChild()',
                                hint: 'DOM manipulation is not allowed'
                            },
                            {
                                pattern: /\.innerHTML/gi,
                                name: '.innerHTML',
                                hint: 'DOM manipulation is not allowed'
                            },
                            {
                                pattern: /\.addEventListener\(/gi,
                                name: '.addEventListener()',
                                hint: 'Event binding is not allowed'
                            },
                            {
                                pattern: /alert\(/gi,
                                name: 'alert()',
                                hint: 'Alerts are not allowed'
                            },
                            {
                                pattern: /console\./gi,
                                name: 'console.*',
                                hint: 'Console logging is not allowed'
                            },
                            {
                                pattern: /fetch\(/gi,
                                name: 'fetch()',
                                hint: 'Network requests are not allowed'
                            },
                            {
                                pattern: /\.getElement/gi,
                                name: '.getElement*',
                                hint: 'DOM queries are not allowed'
                            },
                            {
                                pattern: /\.querySelector/gi,
                                name: '.querySelector*',
                                hint: 'DOM queries are not allowed'
                            },
                            {
                                pattern: /function\s+\w+\s*\(/gi,
                                name: 'function declarations',
                                hint: 'Function definitions are not allowed'
                            },
                            {
                                pattern: /=>\s*\{/gi,
                                name: 'arrow functions',
                                hint: 'Function bodies are not allowed'
                            },
                        ];

                        for (const check of disallowedPatterns) {
                            if (check.pattern.test(code)) {
                                throw new Error(
                                    '❌ Invalid: Found ' + check.name + '\n\n' +
                                    '💡 ' + check.hint + '\n\n' +
                                    '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
                                    '📝 PURPOSE: This editor is for defining block PROPERTIES only\n' +
                                    '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n' +
                                    '✅ CORRECT USAGE - Just define the object:\n\n' +
                                    '{\n' +
                                    '  type: "columns",\n' +
                                    '  columns: 3,\n' +
                                    '  label: "My Columns",\n' +
                                    '  content: "Your content here"\n' +
                                    '}\n\n' +
                                    '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n' +
                                    '❌ WRONG - Don\'t write executable code:\n\n' +
                                    'const data = { type: "columns" };\n' +
                                    'document.body.appendChild(createColumns(data));\n\n' +
                                    '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n' +
                                    '💡 TIP: Think of this as filling a form, not writing a program!'
                                );
                            }
                        }

                        // DETECT: Is this a code block with statements or an object literal?
                        const hasStatements = code.includes('const ') || code.includes('let ') ||
                            code.includes('var ') || code.includes('function ') ||
                            code.includes('return ') || code.includes(';');

                        const isCodeBlock = hasStatements && !code.match(/^\s*\{[^}]*\}\s*$/);

                        if (isCodeBlock) {
                            // This is a CODE BLOCK - execute it and get the result
                            try {
                                let jsCode = code;

                                // Strategy: Execute as function body and auto-return the last object
                                if (!jsCode.includes('return ')) {
                                    const lines = jsCode.split('\n');
                                    let lastMeaningfulLine = '';

                                    // Find the last meaningful line (skip comments and empty lines)
                                    for (let i = lines.length - 1; i >= 0; i--) {
                                        const line = lines[i].trim();
                                        if (line && !line.startsWith('//') && line !== '{' && line !== '}' && line !== '') {
                                            lastMeaningfulLine = line;
                                            break;
                                        }
                                    }

                                    // Check if it's a variable declaration
                                    const varMatch = lastMeaningfulLine.match(/^(const|let|var)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=/);
                                    if (varMatch) {
                                        const varName = varMatch[2];
                                        jsCode = jsCode + '\nreturn ' + varName + ';';
                                    }
                                    // Check if it's a simple variable reference
                                    else if (/^[a-zA-Z_$][a-zA-Z0-9_$]*;?\s*$/.test(lastMeaningfulLine.replace(/;$/, ''))) {
                                        const varName = lastMeaningfulLine.replace(/;$/, '').trim();
                                        jsCode = jsCode + '\nreturn ' + varName + ';';
                                    }
                                    // Check if last line is an object literal
                                    else if (lastMeaningfulLine.startsWith('{') && lastMeaningfulLine.endsWith('}')) {
                                        jsCode = jsCode + '\nreturn ' + lastMeaningfulLine.replace(/;$/, '') + ';';
                                    }
                                }

                                // Execute the code
                                const fn = new Function(jsCode);
                                const result = fn();

                                if (typeof result === 'object' && result !== null) {
                                    return result;
                                } else {
                                    throw new Error('Code did not return an object. Add "return data;" at the end.');
                                }
                            } catch (e) {
                                throw new Error(
                                    '❌ Cannot execute JavaScript code.\n\n' +
                                    '📝 HOW TO FIX:\n\n' +
                                    '✅ Option 1 - Use OBJECT LITERAL (simplest):\n' +
                                    '  {\n' +
                                    '    type: "columns",\n' +
                                    '    columns: 3,\n' +
                                    '    label: "Columns"\n' +
                                    '  }\n\n' +
                                    '✅ Option 2 - Declare variable + RETURN it:\n' +
                                    '  const data = {\n' +
                                    '    type: "columns",\n' +
                                    '    columns: 3\n' +
                                    '  };\n' +
                                    '  return data;\n\n' +
                                    '✅ Option 3 - Use JSON format:\n' +
                                    '  {\n' +
                                    '    "type": "columns",\n' +
                                    '    "columns": 3\n' +
                                    '  }\n\n' +
                                    '❌ DO NOT include:\n' +
                                    '  - document.body.appendChild()\n' +
                                    '  - Function calls that modify DOM\n' +
                                    '  - console.log()\n\n' +
                                    '💡 TIP: This editor is for defining BLOCK PROPERTIES only,\n' +
                                    '     not for executing code.\n\n' +
                                    'Error: ' + e.message
                                );
                            }
                        }

                        // This is an OBJECT LITERAL or JSON - parse it
                        // Try 1: Parse as JSON first (fastest)
                        try {
                            const result = JSON.parse(code);
                            if (typeof result === 'object' && result !== null) {
                                return result;
                            }
                        } catch (e) {
                            // Not valid JSON, continue to next method
                        }

                        // Try 2: Convert JS object literal syntax to JSON and parse
                        try {
                            let normalized = code;

                            // Remove trailing commas
                            normalized = normalized.replace(/,\s*([}\]])/g, '$1');

                            // Convert single quotes to double quotes
                            normalized = normalized.replace(/'/g, '"');

                            // Add quotes to unquoted property names
                            normalized = normalized.replace(/([{,]\s*)([a-zA-Z_$][a-zA-Z0-9_$]*)\s*:/g, '$1"$2":');

                            // Remove semicolons at the end
                            normalized = normalized.replace(/;\s*$/, '');

                            const result = JSON.parse(normalized);
                            if (typeof result === 'object' && result !== null) {
                                return result;
                            }
                        } catch (e) {
                            // Failed to convert
                        }

                        // Try 3: Evaluate as expression
                        try {
                            const wrappedCode = `(${code})`;
                            const fn = new Function('return ' + wrappedCode);
                            const result = fn();

                            if (typeof result === 'object' && result !== null) {
                                return result;
                            } else {
                                throw new Error('Result is not an object');
                            }
                        } catch (e) {
                            throw new Error(
                                '❌ Cannot parse JavaScript object.\n\n' +
                                '📝 Please use one of these formats:\n\n' +
                                '✅ Object literal:\n' +
                                '  { type: "columns", columns: 3 }\n\n' +
                                '✅ JSON:\n' +
                                '  { "type": "columns", "columns": 3 }\n\n' +
                                'Error: ' + e.message
                            );
                        }
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

                    // Save form handler - single authoritative save handler
                    const btnSave = document.getElementById('btn-save');
                    if (btnSave && !btnSave._hasSaveHandler) {
                        btnSave._hasSaveHandler = true; // Prevent duplicate handlers
                        btnSave.addEventListener('click', function(e) {
                            e.preventDefault();
                            // Update schema before saving
                            document.getElementById('schema-js').value = JSON.stringify(blocks);
                            const selectedTable = tableSelector ? tableSelector.value : '';
                            document.getElementById('table-id').value = selectedTable;
                            if (dbTableIdInput) {
                                dbTableIdInput.value = selectedTable;
                            }
                            if (insertToTableInput && storageTypeInput) {
                                const shouldInsert = insertToTableInput.value === '1';
                                storageTypeInput.value = shouldInsert ? 'database' : 'json';
                            }
                            
                            // Submit the form
                            const form = document.getElementById('builder-form');
                            if (form) {
                                form.submit();
                            }
                        });
                    }

                    // Form submit handler - ensures schema is always updated
                    const form = document.getElementById('builder-form');
                    if (form && !form._hasSubmitHandler) {
                        form._hasSubmitHandler = true; // Prevent duplicate handlers
                        form.addEventListener('submit', function() {
                            document.getElementById('schema-js').value = JSON.stringify(blocks);
                            const selectedTable = tableSelector ? tableSelector.value : '';
                            document.getElementById('table-id').value = selectedTable;
                            if (dbTableIdInput) {
                                dbTableIdInput.value = selectedTable;
                            }
                            if (insertToTableInput && storageTypeInput) {
                                const shouldInsert = insertToTableInput.value === '1';
                                storageTypeInput.value = shouldInsert ? 'database' : 'json';
                            }
                        });
                    }

                    // Table selector change - update hidden input
                    document.getElementById('table-selector').addEventListener('change', function() {
                        document.getElementById('table-id').value = this.value;
                        if (dbTableIdInput) {
                            dbTableIdInput.value = this.value;
                        }
                    });

                    // Auto-generate form fields from table
                    if (btnAutoGenerate) {
                        btnAutoGenerate.addEventListener('click', function() {
                            const tableId = tableSelector ? tableSelector.value : '';
                            const tableName = tableSelector ? tableSelector.options[tableSelector.selectedIndex].text : 'NONE';

                            safeLog.log('%c=== AUTO-GENERATE START ===', 'color: #00aa00; font-weight: bold;');
                            safeLog.log('Time:', new Date().toLocaleTimeString());
                            safeLog.log('Table ID:', tableId);
                            safeLog.log('Table Name:', tableName);

                            if (!tableId || tableId === '') {
                                safeLog.warn('❌ No table selected!');
                                alert('❌ Please select a table from the dropdown first');
                                if (tableSelector) tableSelector.focus();
                                safeLog.log('%c=== AUTO-GENERATE CANCELLED ===', 'color: #ff5500;');
                                return;
                            }

                            safeLog.log('✅ Sending fetch request...');
                            safeLog.log('Payload:', JSON.stringify({
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
                                    safeLog.log('Response Status:', r.status, r.statusText);
                                    return r.json();
                                })
                                .then(data => {
                                    safeLog.log('Response Data:', data);

                                    if (data.success && data.columns && data.columns.length > 0) {
                                        safeLog.log('%c✅ SUCCESS! Creating ' + data.columns.length + ' fields...', 'color: #00aa00;');

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
                                            safeLog.log(`  [${idx + 1}] ${fieldType} - ${col.name}`);
                                        });

                                        updateEmptyState();
                                        if (storageTypeInput) {
                                            storageTypeInput.value = 'database';
                                        }
                                        alert('✅ Form generated successfully with ' + data.columns.length + ' field(s)!');
                                        safeLog.log('%c=== AUTO-GENERATE SUCCESS ===', 'color: #00aa00; font-weight: bold;');
                                    } else {
                                        safeLog.error('%c❌ Error Response:', 'color: #ff0000;', data);
                                        const errorMsg = data.error || data.message || 'Failed to load table columns';
                                        alert('❌ Error: ' + errorMsg);
                                        safeLog.log('%c=== AUTO-GENERATE FAILED ===', 'color: #ff0000;');
                                    }
                                })
                                .catch(err => {
                                    safeLog.error('%c❌ Network/Fetch Error:', 'color: #ff0000;', err);
                                    alert('❌ Network Error: ' + err.message);
                                    safeLog.log('%c=== AUTO-GENERATE ERROR ===', 'color: #ff0000;');
                                })
                                .finally(() => {
                                    this.disabled = false;
                                    this.textContent = '⚙️ Auto-Generate';
                                });
                        });
                    } else {
                        safeLog.warn('❌ Auto-generate button not found');
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

                    updateEmptyState();

                    // Initialize Multi-Page Form & Custom Code Editor
                    // (actual call happens in the final DOMContentLoaded block below)

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

        <!-- Publish Modal -->
        <div id="publish-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:16px;max-width:500px;width:90%;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="background:linear-gradient(135deg,#006c49,#00a773);color:#fff;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                    <h3 style="margin:0;font-size:18px;display:flex;align-items:center;gap:8px;">
                        <span class="material-symbols-outlined">public</span>
                        Publish Form
                    </h3>
                    <button onclick="document.getElementById('publish-modal').style.display='none'" style="background:transparent;border:none;color:#fff;cursor:pointer;font-size:24px;padding:4px;">&times;</button>
                </div>
                <div style="padding:24px;">
                    <?php if ($model->isNewRecord): ?>
                    <!-- For new forms, we need to save first -->
                    <div style="background:#fff3cd;border-left:4px solid #f5a623;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
                        <p style="margin:0;font-size:13px;color:#856404;"><strong>Note:</strong> You need to save the form first before publishing. Click "Publish" button below to save and then you can publish from the form list.</p>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:12px;">
                        <button type="button" onclick="document.getElementById('publish-modal').style.display='none'"
                            style="padding:12px 24px;background:#f0f4f9;border:none;border-radius:12px;font-size:14px;font-weight:600;color:#464555;cursor:pointer;">Cancel</button>
                        <button type="submit" form="builder-form" name="publish_now" value="1"
                            style="padding:12px 24px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                            <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                            Save Form First
                        </button>
                    </div>
                    <?php else: ?>
                    <?= Html::beginForm(['form/publish', 'id' => $model->id], 'post', ['id' => 'publish-form-modal']) ?>
                    <?= Html::hiddenInput('form_pages', '', ['id' => 'publish-form-pages-data']) ?>
                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-weight:600;margin-bottom:8px;color:#0b1c30;">Published Name</label>
                        <input type="text" name="name" value="<?= Html::encode($model->name) ?>" maxlength="255" required
                            style="width:100%;padding:12px 16px;border:1px solid #c7c4d8;border-radius:12px;font-size:14px;transition:border 0.2s;"
                            placeholder="Enter published form name..."
                            onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#c7c4d8'">
                    </div>
                    <div style="background:#e5eeff;border-left:4px solid #4f46e5;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
                        <p style="margin:0;font-size:13px;color:#464555;"><strong>Note:</strong> This will publish your form and make it accessible via a public URL.</p>
                    </div>
                    <div style="display:flex;justify-content:flex-end;gap:12px;">
                        <button type="button" onclick="document.getElementById('publish-modal').style.display='none'"
                            style="padding:12px 24px;background:#f0f4f9;border:none;border-radius:12px;font-size:14px;font-weight:600;color:#464555;cursor:pointer;">Cancel</button>
                        <button type="submit" id="publish-form-submit-btn"
                            style="padding:12px 24px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                            <span class="material-symbols-outlined" style="font-size:18px;">public</span>
                            Publish
                        </button>
                    </div>
                    <?= Html::endForm() ?>
                    <script>
                    const publishModalForm = document.getElementById('publish-form-modal');
                    const publishNameInput = publishModalForm.querySelector('input[name="name"]');
                    const publishSubmitButton = document.getElementById('publish-form-submit-btn');

                    // Keep Enter behavior identical to clicking Publish button.
                    if (publishNameInput && publishSubmitButton) {
                        publishNameInput.addEventListener('keydown', function(event) {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                publishSubmitButton.click();
                            }
                        });
                    }

                    publishModalForm.addEventListener('submit', function(e) {
                        const formName = publishNameInput ? publishNameInput.value.trim() : '';
                        if (!formName) {
                            e.preventDefault();
                            alert('Please enter a name for the published form.');
                            return false;
                        }

                        // CRITICAL: Capture current customDesign and formPages before submit
                        const cssEditor = document.getElementById('custom-css');
                        const htmlBeforeEditor = document.getElementById('custom-html-before');
                        const htmlAfterEditor = document.getElementById('custom-html-after');
                        const jsEditor = document.getElementById('custom-js');

                        // Update customDesign with current editor values
                        customDesign.css = cssEditor ? cssEditor.value : customDesign.css || '';
                        customDesign.htmlBefore = htmlBeforeEditor ? htmlBeforeEditor.value : customDesign.htmlBefore || '';
                        customDesign.htmlAfter = htmlAfterEditor ? htmlAfterEditor.value : customDesign.htmlAfter || '';
                        customDesign.js = jsEditor ? jsEditor.value : customDesign.js || '';

                        // Update current page blocks
                        updateCurrentPageBlocks();

                        // Prepare pages data with custom design
                        const pagesData = {
                            pages: formPages,
                            customDesign: customDesign
                        };

                        // Set the hidden input value
                        document.getElementById('publish-form-pages-data').value = JSON.stringify(pagesData);
                    });
                    </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Live Preview Modal -->
    <div class="preview-modal" id="preview-modal">
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h3><i class="fas fa-eye"></i> Live Form Preview</h3>
                <button class="preview-modal-close" id="preview-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="preview-modal-body" id="preview-modal-body">
                <!-- Preview content will be injected here -->
            </div>
        </div>
    </div>

    <script>
    // ===== MULTI-PAGE FORM MANAGEMENT=====
    let formPages = JSON.parse(JSON.stringify(initialFormPages || [
        { id: 'page_1', name: 'Page 1', blocks: [] }
    ]));
    let currentPageIndex = 0;
    const persistedCustomDesign = JSON.parse(JSON.stringify(initialCustomDesign || {
        css: '',
        htmlBefore: '',
        htmlAfter: '',
        js: ''
    }));
    let customDesign = {
        css: '',
        htmlBefore: '',
        htmlAfter: '',
        js: ''
    };
    let customCodeEditorInitialized = false;

    function readCustomDesignFromEditors() {
        const cssEditor = document.getElementById('custom-css');
        const htmlBeforeEditor = document.getElementById('custom-html-before');
        const htmlAfterEditor = document.getElementById('custom-html-after');
        const jsEditor = document.getElementById('custom-js');

        return {
            css: cssEditor ? cssEditor.value : '',
            htmlBefore: htmlBeforeEditor ? htmlBeforeEditor.value : '',
            htmlAfter: htmlAfterEditor ? htmlAfterEditor.value : '',
            js: jsEditor ? jsEditor.value : ''
        };
    }

    function isCustomDesignEmpty(design) {
        return !((design.css || '').trim() || (design.htmlBefore || '').trim() || (design.htmlAfter || '').trim() || (design.js || '').trim());
    }

    function resolveCustomDesign(preserveExistingOnEmpty = false) {
        const editorDesign = readCustomDesignFromEditors();
        if (isEditMode && preserveExistingOnEmpty && isCustomDesignEmpty(editorDesign)) {
            return JSON.parse(JSON.stringify(persistedCustomDesign));
        }
        return editorDesign;
    }

    // Initialize form pages
    function initFormPages() {
        renderPagesTabs();
        loadPageBlocks(currentPageIndex);
        
        // Add page button
        document.getElementById('add-page-btn').addEventListener('click', function() {
            addNewPage();
        });
    }

    // Render page tabs
    function renderPagesTabs() {
        const tabsContainer = document.getElementById('pages-tabs');
        if (!tabsContainer) return;
        
        tabsContainer.innerHTML = '';
        
        formPages.forEach(function(page, index) {
            const tab = document.createElement('div');
            tab.className = 'page-tab' + (index === currentPageIndex ? ' active' : '');
            tab.dataset.pageIndex = index;
            tab.innerHTML = `
                <span class="page-name">${escapeHtml(page.name)}</span>
                ${formPages.length > 1 ? `<button type="button" class="delete-page-btn" data-page-index="${index}" title="Delete page">
                    <i class="fas fa-times"></i>
                </button>` : ''}
            `;
            
            tab.addEventListener('click', function(e) {
                if (!e.target.closest('.delete-page-btn')) {
                    switchToPage(index);
                }
            });
            
            tabsContainer.appendChild(tab);
        });
        
        // Attach delete handlers
        tabsContainer.querySelectorAll('.delete-page-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const pageIndex = parseInt(this.dataset.pageIndex);
                deletePage(pageIndex);
            });
        });
    }

    // Switch to a different page
    function switchToPage(index) {
        // Save current page blocks first
        updateCurrentPageBlocks();
        
        // Switch page
        currentPageIndex = index;
        
        // Load page blocks
        loadPageBlocks(index);
        
        // Update UI
        renderPagesTabs();
    }

    // Update current page blocks from canvas
    function updateCurrentPageBlocks() {
        if (formPages[currentPageIndex]) {
            formPages[currentPageIndex].blocks = JSON.parse(JSON.stringify(blocks));
        }
    }

    // Load page blocks to canvas
    function loadPageBlocks(index) {
        const page = formPages[index];
        if (!page) return;
        
        // Clear canvas
        canvasBlocks.innerHTML = '';
        blocks = [];
        selectedIndex = -1;
        
        // Load blocks from page
        if (page.blocks && page.blocks.length > 0) {
            blocks = JSON.parse(JSON.stringify(page.blocks));
            blocks.forEach(function(block, i) {
                renderBlock(block, i);
            });
        }
        
        updateEmptyState();
        hideProperties();
    }

    // Add new page
    function addNewPage() {
        updateCurrentPageBlocks();
        
        const newPageIndex = formPages.length;
        const newPage = {
            id: 'page_' + (newPageIndex + 1),
            name: 'Page ' + (newPageIndex + 1),
            blocks: []
        };
        
        formPages.push(newPage);
        
        // Switch to new page
        currentPageIndex = newPageIndex;
        loadPageBlocks(newPageIndex);
        renderPagesTabs();
        
        showNotification('✓ New page added successfully!', 'success', 2000);
    }

    // Delete page
    function deletePage(index) {
        if (formPages.length <= 1) {
            showNotification('⚠️ Cannot delete the last page!', 'warning', 2000);
            return;
        }
        
        if (confirm('Are you sure you want to delete this page? All blocks on this page will be removed.')) {
            formPages.splice(index, 1);
            
            // Adjust current page index if needed
            if (currentPageIndex >= formPages.length) {
                currentPageIndex = formPages.length - 1;
            }
            
            loadPageBlocks(currentPageIndex);
            renderPagesTabs();
            
            showNotification('✓ Page deleted successfully!', 'success', 2000);
        }
    }

    // ===== CUSTOM CODE EDITOR =====
    function initCustomCodeEditor() {
        console.log('=== initCustomCodeEditor() CALLED ===');

        if (customCodeEditorInitialized) {
            console.log('Custom code editor already initialized, skip.');
            return;
        }
        customCodeEditorInitialized = true;
        
        const cssEditor = document.getElementById('custom-css');
        const htmlBeforeEditor = document.getElementById('custom-html-before');
        const htmlAfterEditor = document.getElementById('custom-html-after');
        const jsEditor = document.getElementById('custom-js');
        const applyBtn = document.getElementById('apply-custom-design-btn');
        const saveBtn = document.getElementById('save-design-btn');

        console.log('CSS Editor found:', !!cssEditor);
        console.log('HTML Before Editor found:', !!htmlBeforeEditor);
        console.log('HTML After Editor found:', !!htmlAfterEditor);
        console.log('JS Editor found:', !!jsEditor);
        console.log('Apply Button found:', !!applyBtn);
        console.log('Save Button found:', !!saveBtn);

        // Load saved design
        loadSavedDesign();

        // Apply design button
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                customDesign = resolveCustomDesign(true);
                
                openPreviewModal();
            });

            console.log('✅ Apply design button listener attached');
        } else {
            console.log('❌ Apply design button not found');
        }
        
        // Save design button
        if (saveBtn) {
            console.log('✅ Save Custom Design button found!');

            saveBtn.addEventListener('click', function() {
                customDesign = resolveCustomDesign(true);

                console.log('=== SAVE CUSTOM DESIGN CLICKED ===');
                console.log('Custom Design values:');

                const formatForLog = function(value) {
                    if (!value) {
                        return '(empty)';
                    }

                    const maxLen = 500;
                    return value.length > maxLen ? value.substring(0, maxLen) + '...' : value;
                };

                console.log('  CSS:', formatForLog(customDesign.css));
                console.log('  HTML Before:', formatForLog(customDesign.htmlBefore));
                console.log('  HTML After:', formatForLog(customDesign.htmlAfter));
                console.log('  JS:', formatForLog(customDesign.js));

                const localStorageKey = 'formCustomDesign_' + '<?= $model->isNewRecord ? 'new' : $model->id ?>';
                const localStoragePayload = JSON.stringify(customDesign);

                console.log('localStorage key:', localStorageKey);
                console.log('localStorage value to save:', localStoragePayload);

                // Always save to localStorage first
                localStorage.setItem(localStorageKey, localStoragePayload);

                const savedValue = localStorage.getItem(localStorageKey) || '';
                const savedPreview = savedValue.length > 120 ? savedValue.substring(0, 120) + '...' : savedValue;
                console.log('✅ Saved to localStorage. Verify:', savedPreview);

                // Try to save to database via AJAX if form exists
                const formId = <?= $model->isNewRecord ? '0' : $model->id ?>;
                console.log('Form ID:', formId);

                if (formId > 0) {
                    // Save to database via AJAX
                    updateCurrentPageBlocks();

                    fetch('<?= \yii\helpers\Url::to(["form/save-design", "id" => $model->isNewRecord ? 0 : $model->id]) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
                        },
                        body: JSON.stringify({
                            pages: formPages,
                            customDesign: customDesign
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Save design API response:', data);

                        if (data.success) {
                            showNotification('✓ Design saved to database successfully!', 'success', 2000);
                        } else {
                            showNotification('⚠ Design saved locally. Will be saved to database when you publish the form.', 'info', 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Failed to save design to database:', error);
                        showNotification('⚠ Design saved locally. Will be saved to database when you publish the form.', 'info', 3000);
                    });
                } else {
                    // New form - just save to localStorage
                    showNotification('✓ Design saved locally! It will be saved to database when you publish the form.', 'success', 3000);
                }
            });
        } else {
            console.log('❌ Save Custom Design button not found');
        }
    }

    // Load saved design
    function loadSavedDesign() {
        if (isEditMode) {
            // In edit mode, don't auto-populate custom design editors.
            // Existing design is only used when user clicks Apply/Preview or submits without changes.
            return;
        }

        const saved = localStorage.getItem('formCustomDesign_' + '<?= $model->isNewRecord ? 'new' : $model->id ?>');
        if (saved) {
            try {
                customDesign = JSON.parse(saved);
                
                const cssEditor = document.getElementById('custom-css');
                const htmlBeforeEditor = document.getElementById('custom-html-before');
                const htmlAfterEditor = document.getElementById('custom-html-after');
                const jsEditor = document.getElementById('custom-js');
                
                if (cssEditor) cssEditor.value = customDesign.css || '';
                if (htmlBeforeEditor) htmlBeforeEditor.value = customDesign.htmlBefore || '';
                if (htmlAfterEditor) htmlAfterEditor.value = customDesign.htmlAfter || '';
                if (jsEditor) jsEditor.value = customDesign.js || '';
            } catch (e) {
                console.error('Failed to load saved design:', e);
            }
        }
    }

    // ===== LIVE PREVIEW MODAL =====
    function openPreviewModal() {
        const modal = document.getElementById('preview-modal');
        if (!modal) return;
        
        // Generate preview HTML
        const previewHTML = generatePreviewHTML();
        
        // Set preview content
        const body = document.getElementById('preview-modal-body');
        if (body) {
            // Create iframe for isolated preview
            const iframe = document.createElement('iframe');
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.border = 'none';
            
            body.innerHTML = '';
            body.appendChild(iframe);
            
            // Write content to iframe
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(previewHTML);
            iframeDoc.close();
        }
        
        // Show modal
        modal.classList.add('active');
        
        // Close handlers
        document.getElementById('preview-modal-close').addEventListener('click', closePreviewModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePreviewModal();
            }
        });
    }

    function closePreviewModal() {
        const modal = document.getElementById('preview-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // Generate preview HTML
    function generatePreviewHTML() {
        updateCurrentPageBlocks();

        // Check if custom design has any content
        const hasCustomDesign = customDesign.css || customDesign.htmlBefore || customDesign.htmlAfter || customDesign.js;

        let html = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Preview - <?= Html::encode($model->name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">`;

        // Only add default styles if NO custom CSS is provided
        if (!customDesign.css) {
            html += `
    <style>
        body {
            background: #f9fafb;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .form-preview-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .form-preview-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #111827;
        }
        .form-block {
            margin-bottom: 24px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }
        .form-block-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        .form-block-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-block-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .btn-nav {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-prev {
            background: #f3f4f6;
            color: #374151;
        }
        .btn-prev:hover {
            background: #e5e7eb;
        }
        .btn-next {
            background: #4f46e5;
            color: white;
        }
        .btn-next:hover {
            background: #4338ca;
        }
    </style>`;
        }

        // Add custom CSS if provided
        if (customDesign.css) {
            html += `<style>${customDesign.css}</style>`;
        }

        html += `</head>
<body>`;

        // Only add default HTML structure if NO custom HTML is provided
        if (!customDesign.htmlBefore && !customDesign.htmlAfter) {
            html += `
    <div class="form-preview-container">
        <h1 class="form-preview-title"><?= Html::encode($model->name) ?></h1>`;

            // Render current page blocks
            if (formPages[currentPageIndex]) {
                html += `<div id="form-page-content">`;
                formPages[currentPageIndex].blocks.forEach(function(block) {
                    html += renderBlockToHTML(block);
                });
                html += `</div>`;
            }

            // Add navigation for multi-page
            if (formPages.length > 1) {
                html += `<div class="form-navigation">`;
                if (currentPageIndex > 0) {
                    html += `<button class="btn-nav btn-prev" onclick="navigateToPage(${currentPageIndex - 1})"><i class="fas fa-arrow-left"></i> Previous</button>`;
                } else {
                    html += `<div></div>`;
                }
                if (currentPageIndex < formPages.length - 1) {
                    html += `<button class="btn-nav btn-next" onclick="navigateToPage(${currentPageIndex + 1})">Next <i class="fas fa-arrow-right"></i></button>`;
                } else {
                    html += `<button class="btn-nav btn-next" onclick="submitForm()" style="background:linear-gradient(135deg,#10b981,#059669);">Submit Form <i class="fas fa-check"></i></button>`;
                }
                html += `</div>`;

                // Add page indicators
                html += `<div style="display:flex;justify-content:center;gap:8px;margin-top:16px;">`;
                formPages.forEach(function(page, idx) {
                    const activeStyle = idx === currentPageIndex ? 'background:#4f46e5;' : 'background:#d1d5db;';
                    html += `<div style="width:10px;height:10px;border-radius:50%;${activeStyle}cursor:pointer;transition:all 0.3s;" onclick="navigateToPage(${idx})"></div>`;
                });
                html += `</div>`;
            }

            html += `</div>`;
        } else {
            // Use custom HTML
            if (customDesign.htmlBefore) {
                html += customDesign.htmlBefore;
            }
            if (customDesign.htmlAfter) {
                html += customDesign.htmlAfter;
            }
        }

        // Only add default JS if NO custom JS is provided
        if (!customDesign.js) {
            html += `
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"><\/script>
    <script>
        // Page navigation data
        const totalPages = ${formPages.length};
        let currentPage = ${currentPageIndex};
        const pagesData = ${JSON.stringify(formPages)};

        // Navigation function
        function navigateToPage(pageIndex) {
            if (pageIndex < 0 || pageIndex >= totalPages) return;

            // In preview mode, we'll just show an alert with page info
            const page = pagesData[pageIndex];
            const blockCount = page.blocks ? page.blocks.length : 0;

            if (confirm('Navigate to ' + page.name + '? (This page has ' + blockCount + ' blocks)')) {
                // In actual implementation, this would switch pages
                alert('Navigated to ' + page.name + '!');
            }
        }

        // Submit form function
        function submitForm() {
            if (confirm('Are you ready to submit this form?')) {
                alert('Form submitted successfully! (This is a preview - in the actual published form, this would submit the data)');
            }
        }
    <\/script>`;
        }
        
        // Add custom JS
        if (customDesign.js) {
            html += `<script>${customDesign.js}<\/script>`;
        }

        html += `
</body>
</html>`;
        
        return html;
    }

    // Render single block to HTML for preview
    function renderBlockToHTML(block) {
        const label = escapeHtml(block.label || block.type);
        const placeholder = escapeHtml(block.placeholder || '');
        const required = block.required ? '<span style="color:red;">*</span>' : '';
        
        // Render different HTML based on block type
        switch (block.type) {
            case 'text-input':
            case 'email':
            case 'number':
            case 'password':
            case 'url':
            case 'phone':
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <input type="${block.type === 'text-input' ? 'text' : block.type}" class="form-block-input" placeholder="${placeholder || 'Enter ' + label.toLowerCase() + '...'}">
                    ${block.helper ? `<small style="color:#6b7280;font-size:12px;">${escapeHtml(block.helper)}</small>` : ''}
                </div>`;
            
            case 'textarea':
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <textarea class="form-block-input" rows="4" placeholder="${placeholder || 'Enter ' + label.toLowerCase() + '...'}"></textarea>
                    ${block.helper ? `<small style="color:#6b7280;font-size:12px;">${escapeHtml(block.helper)}</small>` : ''}
                </div>`;
            
            case 'select':
                const options = (block.options || 'Option 1\nOption 2').split('\n');
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <select class="form-block-input">
                        <option value="">-- Select an option --</option>
                        ${options.map(opt => `<option value="${escapeHtml(opt.trim())}">${escapeHtml(opt.trim())}</option>`).join('')}
                    </select>
                </div>`;
            
            case 'checkbox':
                const checkboxOptions = (block.options || 'Option 1\nOption 2').split('\n');
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        ${checkboxOptions.map(opt => `
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" style="width:18px;height:18px;">
                                <span>${escapeHtml(opt.trim())}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>`;
            
            case 'radio':
                const radioOptions = (block.options || 'Option 1\nOption 2').split('\n');
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        ${radioOptions.map((opt, i) => `
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="radio" name="${block.label}" style="width:18px;height:18px;" ${i === 0 ? 'checked' : ''}>
                                <span>${escapeHtml(opt.trim())}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>`;
            
            case 'file':
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <input type="file" class="form-block-input" style="padding:10px;">
                </div>`;
            
            case 'date':
            case 'time':
            case 'datetime':
                return `<div class="form-block">
                    <label class="form-block-label">${label} ${required}</label>
                    <input type="${block.type}" class="form-block-input">
                </div>`;
            
            case 'heading':
                const level = block.level || 'h2';
                return `<div style="margin:32px 0 16px;">
                    <${level} style="font-size:${level === 'h1' ? '32px' : level === 'h3' ? '24px' : '28px'};font-weight:700;color:#111827;margin:0;">${label}</${level}>
                    ${block.content ? `<p style="color:#6b7280;margin-top:8px;">${escapeHtml(block.content)}</p>` : ''}
                </div>`;
            
            case 'text_block':
            case 'text':
                return `<div style="margin:16px 0;">
                    <p style="color:#374151;line-height:1.6;">${escapeHtml(block.content || '')}</p>
                </div>`;
            
            case 'image':
                return `<div style="margin:24px 0;text-align:center;">
                    ${block.src ? `<img src="${escapeHtml(block.src)}" style="max-width:100%;height:auto;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">` : '<div style="background:#f3f4f6;padding:40px;border-radius:12px;color:#9ca3af;">📷 Image Placeholder</div>'}
                    ${block.caption ? `<p style="color:#6b7280;font-size:14px;margin-top:12px;">${escapeHtml(block.caption)}</p>` : ''}
                </div>`;
            
            case 'divider':
                return `<hr style="border:none;border-top:2px solid #e5e7eb;margin:24px 0;">`;
            
            case 'spacer':
                return `<div style="height:${block.height || 32}px;"></div>`;
            
            default:
                return `<div class="form-block">
                    <label class="form-block-label">${label}</label>
                    <input type="text" class="form-block-input" placeholder="Enter ${label.toLowerCase()}...">
                </div>`;
        }
    }

    // Initialize everything - called from main DOMContentLoaded
    // initFormPages() dan initCustomCodeEditor() akan dipanggil dari main script

    // Save form pages data before submit - MUST run after DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.__builderEditInitialized) {
            initFormPages();
            initCustomCodeEditor();
            window.__builderEditInitialized = true;
        }

        const form = document.getElementById('builder-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                customDesign = resolveCustomDesign(true);

                // Save to localStorage for future sessions
                localStorage.setItem('formCustomDesign_' + '<?= $model->isNewRecord ? 'new' : $model->id ?>', JSON.stringify(customDesign));

                // Update current page blocks
                updateCurrentPageBlocks();

                // Prepare pages data with custom design - ENSURE customDesign is included
                const pagesData = {
                    pages: formPages,
                    customDesign: customDesign
                };

                document.getElementById('form-pages-data').value = JSON.stringify(pagesData);
            });
        } else {
            console.error('builder-form element not found.');
        }
    });
    </script>
</body>
