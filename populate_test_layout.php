<?php

/**
 * Populate test page with layout data to verify UPDATE mode restoration
 * Run from: php yii populate-layout
 */

// This file is designed to work within Yii framework context
if (PHP_SAPI !== 'cli') {
    die('This script must be run from command line');
}

// Boot Yii application
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

use app\models\MasterPage;

// Test layout data - simple structure with a few blocks
$testLayout = [
    [
        'id' => 'h1',
        'type' => 'heading',
        'props' => [
            'level' => 'h1',
            'text' => 'Welcome to Test Page',
            'align' => 'center',
            'fontSize' => '48',
            'color' => '#1f2937'
        ]
    ],
    [
        'id' => 't1',
        'type' => 'text',
        'props' => [
            'content' => 'This is a test paragraph. The layout data has been saved and should restore when you open this page in UPDATE mode.',
            'align' => 'center',
            'fontSize' => '16',
            'color' => '#4b5563'
        ]
    ],
    [
        'id' => 's1',
        'type' => 'spacer',
        'props' => [
            'height' => '24'
        ]
    ],
    [
        'id' => 'b1',
        'type' => 'button',
        'props' => [
            'text' => 'Click Me',
            'url' => '#',
            'style' => 'primary'
        ]
    ]
];

// Find or create a test page
$pageId = 4;
$model = MasterPage::findOne($pageId);

if (!$model) {
    echo "Creating new page with ID $pageId...\n";
    $model = new MasterPage();
    $model->id = $pageId;
    $model->name = 'Test Page for UPDATE Mode';
    $model->title = 'Test Page for UPDATE Mode';
    $model->slug = 'test-page-update';
    $model->layout = 'blank';
    $model->is_active = 1;
} else {
    echo "Found existing page ID $pageId: {$model->name}\n";
}

// Set the layout JSON
$model->layout_json = json_encode($testLayout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Save
if ($model->save(false)) {
    echo "✓ Successfully saved test layout to page ID $pageId\n";
    echo "  Layout JSON length: " . strlen($model->layout_json) . " chars\n";
    echo "  Blocks count: " . count($testLayout) . "\n";
    echo "\nNow open: http://localhost:8080/master-page/dynamic-update?id=$pageId\n";
    echo "The canvas should show the restored blocks.\n";
} else {
    echo "✗ Failed to save:\n";
    var_dump($model->getErrors());
}
