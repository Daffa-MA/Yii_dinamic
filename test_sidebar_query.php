<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

// Test the query with backticks
$menus = Yii::$app->db->createCommand(
    "SELECT `id`, `parent_id`, `type`, `page_id`, `name`, `icon`, `route` 
     FROM `master_menu` 
     WHERE `is_active` = 1 
     ORDER BY `order`, `sort_order`"
)->queryAll();

echo "=== QUERY TEST ===\n";
echo "Total menus: " . count($menus) . "\n\n";

if (count($menus) > 0) {
    echo "First menu:\n";
    print_r($menus[0]);
} else {
    echo "No menus found\n";
}