<?php

$appEnv = strtolower((string) (getenv('YII_ENV') ?: getenv('APP_ENV') ?: 'prod'));
if (in_array($appEnv, ['dev', 'development', 'local'], true)) {
    $yiiEnv = 'dev';
} elseif (in_array($appEnv, ['test', 'testing'], true)) {
    $yiiEnv = 'test';
} else {
    $yiiEnv = 'prod';
}

$debugEnv = getenv('YII_DEBUG');
if ($debugEnv === false || $debugEnv === '') {
    $yiiDebug = ($yiiEnv === 'dev');
} else {
    $yiiDebug = filter_var($debugEnv, FILTER_VALIDATE_BOOLEAN);
}

defined('YII_DEBUG') or define('YII_DEBUG', $yiiDebug);
defined('YII_ENV') or define('YII_ENV', $yiiEnv);
defined('YII_ENV_DEV') or define('YII_ENV_DEV', YII_ENV === 'dev');
defined('YII_ENV_PROD') or define('YII_ENV_PROD', YII_ENV === 'prod');
defined('YII_ENV_TEST') or define('YII_ENV_TEST', YII_ENV === 'test');

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
