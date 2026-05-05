<?php
require __DIR__ . '/vendor/autoload.php';
$db = require __DIR__ . '/config/db.php';
$pdo = new PDO($db['db']['dsn'], $db['db']['username'], $db['db']['password']);
$tables = ['master_page', 'master_form', 'master_menu'];
foreach ($tables as $t) {
    $exists = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
    echo "$t: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}
