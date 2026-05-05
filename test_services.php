<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== TESTING SERVICES ===\n\n";

// Test MenuService
echo "1. Testing MenuService...\n";
try {
    $menuService = new \app\services\MenuService();
    echo "   ✓ MenuService loaded\n";
    
    // Test validation
    $testMenu = new \app\models\MasterMenu();
    $testMenu->name = 'Test Menu';
    $testMenu->type = 'page';
    $testMenu->page_id = 999; // Non-existent
    
    $validation = $menuService->validateMenu($testMenu);
    echo "   ✓ Validation works: " . ($validation['success'] ? 'PASS' : 'FAIL') . "\n";
    if (!$validation['success']) {
        echo "   Errors: " . implode(", ", $validation['errors']) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test PageService
echo "\n2. Testing PageService...\n";
try {
    $pageService = new \app\services\PageService();
    echo "   ✓ PageService loaded\n";
    
    // Test layout options
    $layouts = $pageService::getLayoutOptions();
    echo "   ✓ Layout options: " . count($layouts) . " layouts\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test PageForms model
echo "\n3. Testing PageForms model...\n";
try {
    $count = \app\models\PageForms::find()->count();
    echo "   ✓ PageForms model works (count: $count)\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== ALL TESTS COMPLETED ===\n";