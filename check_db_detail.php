<?php
$db = new PDO('sqlite:runtime/app.db');
$result = $db->query('PRAGMA table_info(master_menu)')->fetchAll(PDO::FETCH_ASSOC);

echo "All columns in master_menu:\n";
echo "===========================\n";
foreach ($result as $col) {
    echo $col['cid'] . ": " . $col['name'] . " (" . $col['type'] . ")\n";
}

// Try direct query to see columns
echo "\n\nDirect table info:\n";
$result2 = $db->query('PRAGMA table_info(master_menu)')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($result2, JSON_PRETTY_PRINT);
