<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

// Try using ActiveRecord instead
$menus = \app\models\MasterMenu::find()
    ->where(['is_active' => 1])
    ->orderBy(['sort_order' => SORT_ASC, 'order' => SORT_ASC])
    ->all();

echo "=== TEST WITH ACTIVERECORD ===\n";
echo "Total menus: " . count($menus) . "\n\n";

if (count($menus) > 0) {
    echo "First menu:\n";
    $m = $menus[0];
    echo "  id: " . $m->id . "\n";
    echo "  name: " . $m->name . "\n";
    echo "  type: " . $m->type . "\n";
}