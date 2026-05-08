<?php
// Quick verification script
$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbname = 'testing';

try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1) Check column exists
    $stmt = $db->query("SHOW COLUMNS FROM master_page LIKE 'layout_json'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Column check: " . ($col ? "EXISTS ({$col['Type']})" : "MISSING!") . PHP_EOL;
    
    // 2) Check data
    echo PHP_EOL . "All pages data:" . PHP_EOL;
    $pages = $db->query("SELECT id, name, LENGTH(IFNULL(layout_json, '')) as json_len, IF(layout_json IS NULL, 'NULL', 'HAS_DATA') as status FROM master_page ORDER BY id");
    while ($p = $pages->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID {$p['id']}: {$p['name']} - layout_json: {$p['status']} (len: {$p['json_len']})" . PHP_EOL;
    }
    
    // 3) Reset schema cache
    echo PHP_EOL . "Try running this to clear Yii schema cache:" . PHP_EOL;
    echo "  php yii cache/flush-all" . PHP_EOL;
    echo "  OR delete: " . __DIR__ . "/runtime/cache/*" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}