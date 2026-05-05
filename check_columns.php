<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

// Check master_menu columns
$cols = Yii::$app->db->getSchema()->getTableSchema('master_menu')->columns;
echo "master_menu columns:\n";
foreach ($cols as $col) { echo "  - " . $col->name . "\n"; }

// Check master_page columns  
$cols2 = Yii::$app->db->getSchema()->getTableSchema('master_page')->columns;
echo "\nmaster_page columns:\n";
foreach ($cols2 as $col) { echo "  - " . $col->name . "\n"; }

// Check page_forms columns
$cols3 = Yii::$app->db->getSchema()->getTableSchema('page_forms')->columns;
echo "\npage_forms columns:\n";
foreach ($cols3 as $col) { echo "  - " . $col->name . "\n"; }