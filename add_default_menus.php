<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';

$dbConfig = require __DIR__ . '/config/db.php';
$db = $dbConfig['db'];

$pdo = new PDO($db['dsn'], $db['username'], $db['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$now = date('Y-m-d H:i:s');

$menus = [
    ['name' => 'Dashboard', 'icon' => 'dashboard', 'sort_order' => 1, 'menu_key' => 'dashboard', 'route' => '/site/dashboard'],
    ['name' => 'Table Builder', 'icon' => 'table_chart', 'sort_order' => 2, 'menu_key' => 'table-builder', 'route' => '/table-builder/index'],
    ['name' => 'Forms', 'icon' => 'description', 'sort_order' => 3, 'menu_key' => 'forms', 'route' => '/form/index'],
    ['name' => 'Profile', 'icon' => 'person', 'sort_order' => 4, 'menu_key' => 'profile', 'route' => '/site/profile'],
    ['name' => 'Master Data', 'icon' => 'settings', 'sort_order' => 5, 'menu_key' => 'master-data', 'route' => '/master-menu/index'],
];

foreach ($menus as $menu) {
    $stmt = $pdo->prepare("INSERT INTO master_menu (parent_id, page_id, name, icon, sort_order, status, menu_key, route, created_at, updated_at) VALUES (NULL, NULL, ?, ?, ?, 1, ?, ?, ?, ?)");
    $stmt->execute([$menu['name'], $menu['icon'], $menu['sort_order'], $menu['menu_key'], $menu['route'], $now, $now]);
    echo "Inserted: " . $menu['name'] . "\n";
}

echo "Done! Default menus inserted.\n";