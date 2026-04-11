<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

// Auto-create runtime directories if they don't exist
$runtimeDirs = [
    __DIR__ . '/../runtime',
    __DIR__ . '/../runtime/cache',
    __DIR__ . '/../runtime/debug',
    __DIR__ . '/../runtime/logs',
    __DIR__ . '/../runtime/session',
];

foreach ($runtimeDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

(new yii\web\Application($config))->run();
