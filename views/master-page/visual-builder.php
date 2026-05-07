<?php

use yii\helpers\Html;
use app\assets\PageBuilderAsset;

/* @var $this yii\web\View */
/* @var $model app\models\MasterPage */

$this->title = 'Create Page with Visual Builder';
$this->params['breadcrumbs'][] = ['label' => 'Master Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Register Page Builder Assets
PageBuilderAsset::register($this);

// Register Page Builder initialization script
$initialData = '[]'; // Empty for create
$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    // Initialize page builder
    window.pageBuilder = new PageBuilder({
        pageId: null,
        initialData: $initialData
    });
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);

?>

<div class="master-page-builder">
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900">Visual Page Builder</h1>
        <p class="text-sm text-gray-500">Build your page with drag & drop - no coding required!</p>
    </div>

    <!-- Toolbar -->
    <div id="builder-toolbar" class="mb-3"></div>

    <!-- Page Form (hidden, untuk submit ke backend) -->
    <?= Html::beginForm('create', 'post', ['id' => 'page-form', 'style' => 'display:none;']) ?>
    <?= Html::hiddenInput('title') ?>
    <?= Html::hiddenInput('slug') ?>
    <?= Html::hiddenInput('status', 1) ?>
    <!-- Content akan di-set oleh builder saat save -->
    <?= Html::endForm() ?>

    <!-- Builder Container -->
    <div class="page-builder-container">
        <!-- Left Panel: Component Library -->
        <div id="component-library" class="builder-left-panel"></div>

        <!-- Center Panel: Canvas -->
        <div id="builder-canvas" class="builder-center-panel"></div>

        <!-- Right Panel: Properties -->
        <div id="properties-panel" class="builder-right-panel">
            <p class="text-muted small">Select a component to edit</p>
            <div id="form-builder-panel"></div>
        </div>
    </div>

    <!-- JavaScript fallback -->
    <noscript>
        <div class="alert alert-danger mt-3">
            JavaScript is required for the page builder
        </div>
    </noscript>
</div>