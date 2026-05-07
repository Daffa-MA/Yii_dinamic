<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/yii.php';

$config = require __DIR__ . '/config/web.php';
$app = new yii\web\Application($config);

// Get DB connection
$db = Yii::$app->db;

echo "Database Driver: " . $db->driverName . "\n";
echo "Database DSN: " . $db->dsn . "\n\n";

// Get table schema
$table = $db->getTableSchema('master_menu');

if ($table) {
    echo "Master Menu Columns:\n";
    echo "===================\n";
    foreach ($table->columns as $col) {
        echo "- " . $col->name . " (" . $col->dbType . ")\n";
    }
    
    // Check if icon exists
    $iconExists = isset($table->columns['icon']);
    echo "\n'icon' column exists: " . ($iconExists ? "YES" : "NO") . "\n";
    
    if (!$iconExists) {
        echo "\nAdding 'icon' column...\n";
        $db->createCommand()->addColumn('master_menu', 'icon', 'VARCHAR(50)')->execute();
        echo "Column added successfully!\n";
        
        // Verify
        $table = $db->getTableSchema('master_menu', true);
        echo "'icon' column now exists: " . (isset($table->columns['icon']) ? "YES" : "NO") . "\n";
    }
} else {
    echo "Table 'master_menu' not found!\n";
}
