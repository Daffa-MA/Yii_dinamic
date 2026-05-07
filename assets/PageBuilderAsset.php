<?php

/**
 * Page Builder Architecture
 * 
 * Single Source of Truth: JavaScript State (pageState)
 * No hardcoded HTML, no static templates
 * All UI logic in JavaScript, PHP only for CRUD
 */

namespace app\assets;

use yii\web\AssetBundle;

class PageBuilderAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $js = [
        'js/page-builder/state-manager.js',      // State management
        'js/page-builder/component-library.js',  // Component definitions
        'js/page-builder/render-engine.js',      // Render logic
        'js/page-builder/properties-panel.js',   // Properties editor
        'js/page-builder/form-builder.js',       // Form builder
        'js/page-builder/frontend-renderer.js',  // Frontend rendering
        'js/page-builder/builder.js',            // Main builder
    ];

    public $css = [
        'css/page-builder.css',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
}
