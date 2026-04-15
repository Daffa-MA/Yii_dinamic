<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$fullPath = __DIR__ . $path;

// Serve existing files directly (css, js, assets, images, etc.)
if ($path !== '/' && is_file($fullPath)) {
    return false;
}

// Route all other requests through Yii entry script
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
