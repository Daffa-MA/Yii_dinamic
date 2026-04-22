<?php
$config = require 'config/db.php';
$dsn = $config['dsn'];
$user = $config['username'];
$password = $config['password'];

try {
    $pdo = new PDO($dsn, $user, $password);
    
    // Get table schema
    $stmt = $pdo->query('DESCRIBE siswa');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Siswa Table Schema:\n";
    echo str_repeat('=', 100) . "\n";
    foreach ($columns as $col) {
        echo "Field: " . str_pad($col['Field'], 20) . " | Type: " . str_pad($col['Type'], 30) . " | Null: " . $col['Null'] . " | Key: " . $col['Key'] . "\n";
    }
    
    // Check db_table_columns for id_kelas definition
    echo "\n\nID_KELAS Column Configuration:\n";
    echo str_repeat('=', 100) . "\n";
    $stmt = $pdo->query("
        SELECT dt.name AS table_name, dtc.name AS column_name, dtc.type, dtc.length 
        FROM db_table_columns dtc
        JOIN db_tables dt ON dtc.table_id = dt.id
        WHERE dt.name = 'siswa' AND dtc.name = 'id_kelas'
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($result)) {
        foreach ($result as $row) {
            print_r($row);
        }
    } else {
        echo "No column configuration found in db_table_columns.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
