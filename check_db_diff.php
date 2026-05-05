<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== CHECK DB vs METADATA_DB ===\n\n";

// Check main db
echo "Main db (Yii::\$app->db):\n";
$cols1 = Yii::$app->db->getSchema()->getTableSchema('master_menu')->columns;
foreach ($cols1 as $col) { echo "  - " . $col->name . "\n"; }

// Check metadataDb if exists
if (Yii::$app->has('metadataDb')) {
    echo "\nmetadataDb:\n";
    try {
        $cols2 = Yii::$app->metadataDb->getSchema()->getTableSchema('master_menu')->columns;
        foreach ($cols2 as $col) { echo "  - " . $col->name . "\n"; }
    } catch (\Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nmetadataDb not configured\n";
}