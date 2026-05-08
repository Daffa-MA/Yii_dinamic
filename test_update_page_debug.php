<?php

/**
 * Test script to debug why UPDATE page canvas is empty
 * Run from command line: php test_update_page_debug.php
 */

require __DIR__ . '/yii.php';

use app\models\MasterPage;

// Get page with ID 4
$model = MasterPage::findOne(4);

if (!$model) {
    die("Model not found\n");
}

echo "=== DEBUG: MasterPage ID 4 ===\n";
echo "ID: " . $model->id . "\n";
echo "isNewRecord: " . ($model->isNewRecord ? 'true' : 'false') . "\n";
echo "layout_json exists: " . (!empty($model->layout_json) ? 'yes' : 'no') . "\n";
echo "layout_json length: " . strlen($model->layout_json ?? '') . " chars\n";
echo "layout_json value:\n";
echo $model->layout_json . "\n";
echo "\n";

// Try to decode
$decoded = json_decode($model->layout_json, true);
echo "JSON Decoded:\n";
echo "- Is array: " . (is_array($decoded) ? 'yes' : 'no') . "\n";
echo "- Array length: " . (is_array($decoded) ? count($decoded) : 'N/A') . "\n";
echo "- Decoded value:\n";
var_dump($decoded);
echo "\n";

// Check if it's a valid state
$hasLayout = !empty($model->layout_json) && $model->layout_json !== 'null';
echo "hasLayout check result: " . ($hasLayout ? 'true' : 'false') . "\n";

if (is_array($decoded) && count($decoded) > 0) {
    echo "\nFirst block in layout:\n";
    var_dump($decoded[0]);
}
