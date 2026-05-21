<?php

$params = require __DIR__ . '/params.php';
$dbBundle = require __DIR__ . '/db.php';
$db = $dbBundle['db'] ?? $dbBundle;

if (!function_exists('appCacheComponentConfig')) {
    function appCacheComponentConfig(): array
    {
        $driver = strtolower(trim((string)(getenv('YII_CACHE_DRIVER') ?: getenv('APP_CACHE_DRIVER') ?: 'file')));

        if ($driver === 'redis' || $driver === 'auto') {
            if (class_exists('yii\\redis\\Cache') && class_exists('yii\\redis\\Connection')) {
                return [
                    'class' => 'yii\\redis\\Cache',
                    'redis' => [
                        'class' => 'yii\\redis\\Connection',
                        'hostname' => getenv('YII_REDIS_HOST') ?: getenv('REDIS_HOST') ?: '127.0.0.1',
                        'port' => (int)(getenv('YII_REDIS_PORT') ?: getenv('REDIS_PORT') ?: 6379),
                        'database' => (int)(getenv('YII_REDIS_DATABASE') ?: getenv('REDIS_DATABASE') ?: 0),
                    ],
                    'keyPrefix' => (string)(getenv('YII_CACHE_PREFIX') ?: getenv('APP_CACHE_PREFIX') ?: 'yii-dynamic:'),
                ];
            }
        }

        if ($driver === 'memcached' || $driver === 'auto') {
            if (class_exists('yii\\caching\\MemCache') && (extension_loaded('memcached') || extension_loaded('memcache'))) {
                return [
                    'class' => 'yii\\caching\\MemCache',
                    'useMemcached' => extension_loaded('memcached'),
                    'servers' => [[
                        'host' => getenv('YII_MEMCACHED_HOST') ?: getenv('MEMCACHED_HOST') ?: '127.0.0.1',
                        'port' => (int)(getenv('YII_MEMCACHED_PORT') ?: getenv('MEMCACHED_PORT') ?: 11211),
                        'weight' => 100,
                    ]],
                    'keyPrefix' => (string)(getenv('YII_CACHE_PREFIX') ?: getenv('APP_CACHE_PREFIX') ?: 'yii-dynamic:'),
                ];
            }
        }

        return [
            'class' => 'yii\\caching\\FileCache',
            'cachePath' => '@runtime/cache',
            'defaultDuration' => 86400,
            'directoryLevel' => 1,
            'keyPrefix' => (string)(getenv('YII_CACHE_PREFIX') ?: getenv('APP_CACHE_PREFIX') ?: 'yii-dynamic:'),
        ];
    }
}

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => appCacheComponentConfig(),
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'metadataDb' => $db,
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@app/migrations',
        ],
    ],
    'params' => $params,
];

if (!empty($dbBundle['dbBackup']) && is_array($dbBundle['dbBackup'])) {
    $config['components']['dbBackup'] = $dbBundle['dbBackup'];
}

if (YII_ENV_DEV) {
    if (class_exists('yii\gii\Module')) {
        // configuration adjustments for 'dev' environment
        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
        ];
    }

    // requires version `2.1.21` of yii2-debug module
    if (class_exists('yii\debug\Module')) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }
}

return $config;
