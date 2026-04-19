<?php

$params = require __DIR__ . '/params.php';
$dbBundle = require __DIR__ . '/db.php';
$db = $dbBundle['db'] ?? $dbBundle;

$config = [
    'id' => 'basic',
    'name' => 'Architectural Editor',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'blwvTdeGu2Ngh7Y3AaB_BbXDgKv5f1im',
            'trustedHosts' => ['*'],
        ],
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'session' => [
            'class' => 'yii\web\Session',
            'savePath' => __DIR__ . '/../runtime/session',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\swiftmailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Notification API endpoints
                'api/notification/count' => 'notification/count',
                'api/notification/list' => 'notification/list',
                'api/notification/mark-read' => 'notification/mark-read',
                'api/notification/mark-all-read' => 'notification/mark-all-read',
                'api/notification/delete' => 'notification/delete',

                // Form routes
                'form/<id:\d+>' => 'form/view',
                'form/create' => 'form/create',
                'form/update/<id:\d+>' => 'form/update',
                'form/render/<id:\d+>' => 'form/render',
                'form/public-render/<id:\d+>' => 'form/public-render',
                'form/submit/<id:\d+>' => 'form/submit',
                'form/submissions/<id:\d+>' => 'form/submissions',
                'form/export/<id:\d+>' => 'form/export',
                'form/duplicate/<id:\d+>' => 'form/duplicate',

                // Table builder routes
                'tables' => 'table-builder/index',
                'tables/create' => 'table-builder/create',
                'tables/update/<id:\d+>' => 'table-builder/update',
                'tables/execute/<id:\d+>' => 'table-builder/execute-sql',
                'tables/preview/<id:\d+>' => 'table-builder/preview-sql',
                'tables/delete/<id:\d+>' => 'table-builder/delete',

                // Project routes
                'projects' => 'project/index',
                'projects/select/<id:\d+>' => 'project/select',
                'project/profile' => 'project/profile',

                // Site routes
                'dashboard' => 'site/dashboard',
                'profile' => 'site/profile',
            ],
        ],
    ],
    'params' => $params,
];

if (!empty($dbBundle['dbBackup']) && is_array($dbBundle['dbBackup'])) {
    $config['components']['dbBackup'] = $dbBundle['dbBackup'];
}

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    if (class_exists('yii\debug\Module')) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }

    if (class_exists('yii\gii\Module')) {
        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }
}

return $config;
