<?php
// Add missing layout_json column to master_page in the current project database
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbname = 'testing'; // Current project database

try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM master_page LIKE 'layout_json'");
    if ($stmt->fetch()) {
        echo "Column 'layout_json' already exists in {$dbname}.master_page\n";
    } else {
        $db->exec("ALTER TABLE master_page ADD COLUMN layout_json LONGTEXT NULL AFTER layout");
        echo "Column 'layout_json' added successfully to {$dbname}.master_page\n";
    }
    
    // Show current data
    $stmt = $db->query("SELECT id, name, layout_json FROM master_page WHERE id = 9");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "\nPage ID 9 data:\n";
        echo "  name: " . $row['name'] . "\n";
        echo "  layout_json: " . (is_null($row['layout_json']) ? 'NULL' : 'HAS DATA (' . strlen($row['layout_json']) . ' chars)') . "\n";
        
        if ($row['layout_json'] === null) {
            echo "\nNOTE: layout_json is NULL. You need to save the page again from the builder.\n";
            echo "Or copy data from the main database if it exists there.\n";
        }
    }
    
    echo "\nDone!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}