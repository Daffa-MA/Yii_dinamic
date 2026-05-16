<?php

use yii\helpers\Html;
use app\assets\PageBuilderAsset;

/**
 * Page Update with Visual Builder
 * 
 * @var yii\web\View $this
 * @var app\models\MasterPage $model
 */

// Safety check
if (!isset($model)) {
    throw new \yii\web\NotFoundHttpException('Page model not found');
}

$this->title = 'Edit Page - Visual Builder';
$this->params['breadcrumbs'][] = ['label' => 'Master Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';

// Register Page Builder Assets
PageBuilderAsset::register($this);

// Load existing layout if available
$initialData = $model->layout_json ? $model->layout_json : '[]';

// Initialize PageBuilder with existing data
$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    // Initialize with existing state
    window.pageBuilder = new PageBuilder({
        pageId: {$model->id},
        isUpdate: true,
        initialData: $initialData,
        mode: 'update'
    });
    
    // Setup save button handler - skip URL validation
    document.getElementById('builder-save-btn').addEventListener('click', function() {
        // Store title for error recovery
        const titleInput = document.getElementById('save-title');
        if (titleInput) sessionStorage.setItem('visualBuilderPendingTitle', titleInput.value);
        
        window.pageBuilder.savePageUpdate();
    });
    
    // Check for saveError and show appropriate message
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('saveError') === '1') {
        window.history.replaceState({}, '', window.location.pathname + window.location.hash);
        
        // Restore title from storage
        const savedTitle = sessionStorage.getItem('visualBuilderPendingTitle');
        const titleInput = document.getElementById('save-title');
        if (savedTitle && titleInput) {
            titleInput.value = savedTitle;
        }
        
        // Show error message
        setTimeout(function() {
            alert('Gagal menyimpan halaman. Silakan periksa data dan coba lagi.');
        }, 100);
    }
    
    // Store initial title
    const initTitleInput = document.getElementById('save-title');
    if (initTitleInput) sessionStorage.setItem('visualBuilderPendingTitle', initTitleInput.value);
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);

?>

<!-- Warning Modal for Button URL Validation -->
<div id="warningModalOverlay" class="warning-modal-overlay">
    <div class="warning-modal">
        <div class="warning-modal-header">
            <div class="warning-modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="warning-modal-title">Peringatan URL Kosong</h3>
                <p class="warning-modal-subtitle">Beberapa button belum memiliki URL yang valid</p>
            </div>
        </div>
        <div class="warning-modal-body">
            <div id="warningModalList" class="warning-modal-list"></div>
        </div>
        <div class="warning-modal-footer">
            <button type="button" class="warning-modal-btn warning-modal-btn-cancel" onclick="document.getElementById('warningModalOverlay').classList.remove('open')">Perbaiki Dulu</button>
            <button type="button" class="warning-modal-btn warning-modal-btn-proceed" id="warningModalProceedBtn">Tetap Simpan</button>
        </div>
    </div>
</div>

<style>
.warning-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}
.warning-modal-overlay.open {
    opacity: 1;
    visibility: visible;
}
.warning-modal {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: scale(0.95);
    transition: transform 0.2s ease;
}
.warning-modal-overlay.open .warning-modal {
    transform: scale(1);
}
.warning-modal-header {
    padding: 24px 24px 0;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}
.warning-modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.warning-modal-icon svg {
    width: 24px;
    height: 24px;
    color: #f59e0b;
}
.warning-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 4px;
}
.warning-modal-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}
.warning-modal-body {
    padding: 16px 24px;
}
.warning-modal-list {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 12px 16px;
    max-height: 200px;
    overflow-y: auto;
}
.warning-modal-list-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #fecaca;
    font-size: 14px;
    color: #991b1b;
}
.warning-modal-list-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.warning-modal-list-item::before {
    content: "⚠️";
    font-size: 12px;
}
.warning-modal-footer {
    padding: 0 24px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}
.warning-modal-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s ease;
}
.warning-modal-btn-cancel {
    background: #f3f4f6;
    color: #374151;
}
.warning-modal-btn-cancel:hover {
    background: #e5e7eb;
}
.warning-modal-btn-proceed {
    background: #6366f1;
    color: white;
}
.warning-modal-btn-proceed:hover {
    background: #4f46e5;
}
</style>

<div class="master-page-visual-update">
    <style>
        .visual-builder-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .visual-builder-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .visual-builder-actions {
            display: flex;
            gap: 8px;
        }

        .page-builder-container {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            gap: 0;
            height: calc(100vh - 100px);
            background: #f8fafc;
        }

        .builder-left-panel,
        .builder-right-panel {
            background: white;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            padding: 16px;
        }

        .builder-center-panel {
            background: #f1f5f9;
            overflow-y: auto;
            padding: 24px;
        }

        @media (max-width: 1024px) {
            .page-builder-container {
                grid-template-columns: 1fr;
                height: auto;
                min-height: calc(100vh - 100px);
            }

            .builder-left-panel,
            .builder-center-panel,
            .builder-right-panel {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                min-height: 400px;
            }
        }
    </style>

    <!-- Header -->
    <div class="visual-builder-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p style="margin: 4px 0 0 0; font-size: 14px; color: #666;">Page: <strong><?= Html::encode($model->title) ?></strong></p>
        </div>
        <div class="visual-builder-actions">
            <?= Html::a('←  Back', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::button('💾 Update Page', ['id' => 'builder-save-btn', 'class' => 'btn btn-success']) ?>
        </div>
    </div>

    <!-- Builder Container -->
    <div class="page-builder-container">
        <!-- Left Panel: Component Library -->
        <div id="component-library" class="builder-left-panel">
            <h5 class="mb-3">📦 Components</h5>
            <p class="text-muted small">Loading components...</p>
        </div>

        <!-- Center Panel: Canvas -->
        <div id="builder-canvas" class="builder-center-panel">
            <div class="text-center text-muted" style="padding: 40px;">
                <p>Loading page content...</p>
            </div>
        </div>

        <!-- Right Panel: Properties -->
        <div id="properties-panel" class="builder-right-panel">
            <h5 class="mb-3">⚙️ Properties</h5>
            <p class="text-muted small">Select a component to edit its properties</p>
            <div id="form-builder-panel"></div>
        </div>
    </div>

    <!-- Hidden form for backend submission -->
    <?= Html::beginForm('visual-save', 'post', ['id' => 'page-save-form']) ?>
    <?= Html::hiddenInput('pageId', $model->id) ?>
    <?= Html::hiddenInput('title', $model->title, ['id' => 'save-title']) ?>
    <?= Html::hiddenInput('slug', $model->slug, ['id' => 'save-slug']) ?>
    <?= Html::hiddenInput('content', null, ['id' => 'save-content']) ?>
    <?= Html::endForm() ?>
</div>