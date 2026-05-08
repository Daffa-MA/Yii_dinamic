<?php
// Check what columns master_page has in the current database
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/web.php';

try {
    // Use a simple PDO connection
    $db = new PDO('mysql:host=127.0.0.1;port=3306;dbname=testing', 'root', '');
    $stmt = $db->query('SHOW COLUMNS FROM master_page');
    echo "Columns in master_page (testing database):\n";
    echo "========================================\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s %-15s %s\n", $row['Field'], $row['Type'], $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
    
    echo "\n\nChecking if layout_json exists via SHOW CREATE TABLE:\n";
    echo "========================================\n";
    $stmt2 = $db->query('SHOW CREATE TABLE master_page');
    $create = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo $create['Create Table'] . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}