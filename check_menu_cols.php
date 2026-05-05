<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$cols = Yii::$app->db->getSchema()->getTableSchema('master_menu')->columns;
echo "master_menu columns:\n";
foreach ($cols as $col) { echo "  - " . $col->name . "\n"; }