<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);
$tables = Yii::$app->db->getSchema()->getTableNames();
echo "Existing tables:\n";
foreach($tables as $t) { echo "  - $t\n"; }