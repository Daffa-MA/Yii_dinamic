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
    
    // Setup save button handler
    document.getElementById('builder-save-btn').addEventListener('click', function() {
        window.pageBuilder.savePageUpdate();
    });
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);

?>

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