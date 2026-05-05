<?php
/**
 * Run this to add status column to master_menu table
 */
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$db = Yii::$app->db;
try {
    // Check if status column exists
    $columns = $db->createCommand("SHOW COLUMNS FROM master_menu")->queryAll();
    $hasStatus = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $hasStatus = true;
            break;
        }
    }
    
    if (!$hasStatus) {
        $db->createCommand("ALTER TABLE master_menu ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1")->execute();
        echo "Status column added successfully!\n";
    } else {
        echo "Status column already exists.\n";
    }
    
    // Check if route column exists
    $hasRoute = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'route') {
            $hasRoute = true;
            break;
        }
    }
    
    if (!$hasRoute) {
        $db->createCommand("ALTER TABLE master_menu ADD COLUMN route VARCHAR(255) DEFAULT NULL")->execute();
        $db->createCommand("ALTER TABLE master_menu ADD COLUMN menu_key VARCHAR(50) DEFAULT NULL")->execute();
        echo "Route and menu_key columns added successfully!\n";
    } else {
        echo "Route column already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}