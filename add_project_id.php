<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

// Add project_id columns if not exist
$schema = Yii::$app->db->schema;

// Check master_menu
$menuCols = Yii::$app->db->getSchema()->getTableSchema('master_menu')->columns;
if (!isset($menuCols['project_id'])) {
    Yii::$app->db->createCommand()->addColumn('master_menu', 'project_id', 'INTEGER NULL')->execute();
    echo "Added project_id to master_menu\n";
} else {
    echo "master_menu already has project_id\n";
}

// Check master_page
$pageCols = Yii::$app->db->getSchema()->getTableSchema('master_page')->columns;
if (!isset($pageCols['project_id'])) {
    Yii::$app->db->createCommand()->addColumn('master_page', 'project_id', 'INTEGER NULL')->execute();
    echo "Added project_id to master_page\n";
} else {
    echo "master_page already has project_id\n";
}

// Check page_forms
$pfCols = Yii::$app->db->getSchema()->getTableSchema('page_forms')->columns;
if (!isset($pfCols['project_id'])) {
    Yii::$app->db->createCommand()->addColumn('page_forms', 'project_id', 'INTEGER NULL')->execute();
    echo "Added project_id to page_forms\n";
} else {
    echo "page_forms already has project_id\n";
}

echo "\nDone! Check columns:\n";
$menuCols = Yii::$app->db->getSchema()->getTableSchema('master_menu')->columns;
echo "master_menu: " . implode(', ', array_keys($menuCols)) . "\n";