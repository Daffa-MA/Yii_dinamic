<?php
/**
 * Test Edge Cases - Dynamic CMS Validation
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== EDGE CASE VALIDATOR TEST ===\n\n";

$validator = new \app\services\EdgeCaseValidator();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. GET SIDEBAR VALID MENUS (filter inactive)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$sidebar = $validator->getSidebarValidMenus();
echo "Total valid menus: {$sidebar['total']}\n";
foreach ($sidebar['valid_menus'] as $menu) {
    echo "  - [{$menu['type']}] {$menu['name']}\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "2. GET INACTIVE PAGE MENUS (warning)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$inactiveMenus = $validator->getInactivePageMenus();
if (empty($inactiveMenus)) {
    echo "✓ Tidak ada menu dengan page nonaktif\n";
} else {
    foreach ($inactiveMenus as $item) {
        echo "  ⚠ Menu: {$item['menu_name']}\n";
        echo "    Page: {$item['page_title']} (inactive)\n";
        echo "    Activate: {$item['activate_url']}\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "3. QUICK MENU CHECK (sebuah menu)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$check = \app\services\EdgeCaseValidator::quickMenuCheck(5);
echo json_encode($check, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "4. QUICK PAGE CHECK (sebuah page)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$check2 = \app\services\EdgeCaseValidator::quickPageCheck(8);
echo json_encode($check2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "5. VALIDATE PAGE (edge cases)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$page = \app\models\MasterPage::findOne(8);
if ($page) {
    $validation = $validator->validatePage($page);
    echo "Page: {$page->title}\n";
    echo "Is Valid: " . ($validation['is_valid'] ? 'Yes' : 'No') . "\n";
    echo "Form Count: {$validation['page_forms_count']}\n";
    echo "Connected Menus: {$validation['connected_menus_count']}\n";
    
    if (!empty($validation['warnings'])) {
        echo "\nWarnings:\n";
        foreach ($validation['warnings'] as $w) {
            echo "  [{$w['severity']}] {$w['message']}\n";
            echo "    Suggestion: {$w['suggestion']}\n";
        }
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "6. CIRCULAR PARENT CHECK\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$menu = \app\models\MasterMenu::findOne(3);
if ($menu) {
    echo "Menu: {$menu->name} (ID: {$menu->id})\n";
    echo "Parent ID: {$menu->parent_id}\n";
    $circular = $validator->checkCircularParent($menu);
    echo "Is Circular: " . ($circular['is_circular'] ? 'YES (ERROR!)' : 'No') . "\n";
    if (!empty($circular['path'])) {
        echo "Path: ";
        foreach ($circular['path'] as $p) {
            echo "{$p['name']} → ";
        }
        echo "\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "7. PAGE DISPLAY - INACTIVE PAGE HANDLING\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$service = new \app\services\PageDisplayService();
$result = $service->handleMenuClick(5); // Menu dengan page_id=2 (page mungkin inactive)
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n=== TEST COMPLETE ===\n";