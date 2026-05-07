<?php

use yii\helpers\Html;
use app\assets\PageBuilderAsset;

/* @var $this yii\web\View */
/* @var $model app\models\MasterPage */

$this->title = 'Create Page - Visual Builder';
$this->params['breadcrumbs'][] = ['label' => 'Master Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Register Page Builder Assets
PageBuilderAsset::register($this);

// Initialize PageBuilder with empty state
$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    // Initialize with empty state
    window.pageBuilder = new PageBuilder({
        pageId: null,
        isCreate: true,
        initialData: [],
        mode: 'create'
    });
    
    // Setup save button handler
    document.getElementById('builder-save-btn').addEventListener('click', function() {
        window.pageBuilder.savePage();
    });
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);

?>

<div class="master-page-visual-create">
    <style>
        * {
            box-sizing: border-box;
        }

        .visual-builder-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid #334155;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        .visual-builder-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .visual-builder-header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            margin: 6px 0 0 0;
        }

        .visual-builder-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .visual-builder-actions .btn {
            font-size: 13px;
            padding: 8px 16px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .visual-builder-actions .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
        }

        .visual-builder-actions .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            text-decoration: none;
        }

        .visual-builder-actions .btn-success {
            background: #10b981;
            border: none;
            color: white;
        }

        .visual-builder-actions .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .page-builder-container {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            gap: 0;
            height: calc(100vh - 100px);
            background: #ffffff;
        }

        .builder-left-panel,
        .builder-right-panel {
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .builder-left-panel h5,
        .builder-right-panel h5 {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .builder-center-panel {
            background: #ffffff;
            overflow-y: auto;
            padding: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
        }

        .builder-center-panel .text-center {
            color: #9ca3af;
            font-size: 16px;
        }

        .builder-center-panel .text-muted {
            color: #d1d5db;
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
                border-bottom: 1px solid #334155;
                min-height: 400px;
            }

            .visual-builder-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .visual-builder-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }

        @media (max-width: 768px) {
            .visual-builder-header {
                padding: 16px;
            }

            .visual-builder-header h1 {
                font-size: 22px;
            }

            .page-builder-container {
                height: auto;
            }

            .builder-left-panel,
            .builder-right-panel {
                display: none;
            }

            .builder-center-panel {
                padding: 20px;
            }
        }
    </style>

    <!-- Header -->
    <div class="visual-builder-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p style="margin: 4px 0 0 0; font-size: 14px; color: #666;">Design your page with drag & drop components</p>
        </div>
        <div class="visual-builder-actions">
            <?= Html::a('←  Back', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::button('💾 Save Page', ['id' => 'builder-save-btn', 'class' => 'btn btn-success']) ?>
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
                <p>Drag components from the left panel to start building</p>
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
    <?= Html::beginForm('visual-create-submit', 'post', ['id' => 'page-save-form']) ?>
    <?= Html::hiddenInput('title', null, ['id' => 'save-title']) ?>
    <?= Html::hiddenInput('slug', null, ['id' => 'save-slug']) ?>
    <?= Html::hiddenInput('content', null, ['id' => 'save-content']) ?>
    <?= Html::endForm() ?>
</div>