<?php
/**
 * Test SidebarService - Demo output JSON dan HTML
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== SIDEBAR SERVICE TEST ===\n\n";

$service = new \app\services\SidebarService();

// 1. Get JSON Output
echo "📋 1. JSON OUTPUT (getMenuJson):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$json = $service->getMenuJson(true);
echo $json;
echo "\n\n";

// 2. Get Array Tree
echo "📋 2. ARRAY TREE (getMenuTree):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$tree = $service->getMenuTree(true);
echo "Total root menus: " . count($tree) . "\n";
echo "Total active menus: " . $service->countActiveMenus() . "\n";
echo "Total active pages: " . $service->countActivePages() . "\n\n";

// 3. Print detailed tree
echo "📋 3. DETAILED TREE STRUCTURE:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
function printTree($items, $indent = 0) {
    foreach ($items as $item) {
        $arrow = $item['has_children'] ? '📁' : '📄';
        $prefix = str_repeat('   ', $indent);
        $type = strtoupper($item['type']);
        echo "{$prefix}{$arrow} [{$type}] {$item['name']} (order: {$item['order']})\n";
        
        if (!empty($item['children'])) {
            printTree($item['children'], $indent + 1);
        }
    }
}
printTree($tree);

// 4. Get breadcrumb example
echo "\n📋 4. BREADCRUMB EXAMPLE:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
// Get first submenu as example
foreach ($tree as $root) {
    if (!empty($root['children'])) {
        $firstChild = $root['children'][0];
        $breadcrumb = $service->getBreadcrumb($firstChild['id']);
        echo "Path untuk '{$firstChild['name']}':\n";
        foreach ($breadcrumb as $crumb) {
            echo "  → {$crumb['name']}\n";
        }
        break;
    }
}

echo "\n=== TEST COMPLETE ===\n";