<?php
$db = new PDO('sqlite:runtime/app.db');
$columns = $db->query('PRAGMA table_info(master_menu)')->fetchAll(PDO::FETCH_ASSOC);

echo "Master Menu Columns:\n";
echo "====================\n";
foreach ($columns as $col) {
    echo $col['name'] . ' (' . $col['type'] . ')' . PHP_EOL;
}

echo "\n\nChecking if 'icon' column exists: ";
$hasIcon = false;
foreach ($columns as $col) {
    if ($col['name'] === 'icon') {
        $hasIcon = true;
        break;
    }
}
echo $hasIcon ? "YES\n" : "NO\n";
