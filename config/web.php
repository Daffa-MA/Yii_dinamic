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
            'cachePath' => '@runtime/cache',
            'defaultDuration' => 86400,
            'directoryLevel' => 1,
        ],
        'session' => [
            'class' => 'app\components\NoSetPathSession',
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
        'metadataDb' => $db,
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
                'tables/get-tables' => 'table-builder/get-tables',
                'tables/get-columns/<id:\d+>' => 'table-builder/get-table-columns',
                'tables/columns/<id:\d+>' => 'table-builder/get-columns',
                'tables/foreign-key-options/<columnId:\d+>' => 'table-builder/get-foreign-key-options',
                'tables/view/<id:\d+>' => 'table-builder/view',
                'tables/update/<id:\d+>' => 'table-builder/update',
                'tables/execute/<id:\d+>' => 'table-builder/execute-sql',
                'tables/preview/<id:\d+>' => 'table-builder/preview-sql',
                'tables/delete/<id:\d+>' => 'table-builder/delete',

                // Master Form routes
                'master-form' => 'master-form/index',
                'master-form/create' => 'master-form/create',
                'master-form/view/<id:\d+>' => 'master-form/view',
                'master-form/preview/<id:\d+>' => 'master-form/preview',
                'master-form/submit/<id:\d+>' => 'master-form/submit',
                'master-form/update/<id:\d+>' => 'master-form/update',
                'master-form/delete/<id:\d+>' => 'master-form/delete',
                'master-form/duplicate/<id:\d+>' => 'master-form/duplicate',

                // Project routes (main landing after login)
                'project-list' => 'project/index',
                'project-list/select/<id:\d+>' => 'project/select',
                'project-list/firebase-users' => 'project/firebase-users',
                'project/profile' => 'project/profile',

                // Multi-project Dashboard routes (NEW - untuk dynamic)
                'dashboard' => 'dashboard/index',
                'dashboard/<project_id:\d+>' => 'dashboard/index',
                'dashboard/handle-menu' => 'dashboard/handle-menu',
                'dashboard/get-forms' => 'dashboard/get-forms',
                'dashboard/render-page' => 'dashboard/render-page',

                // Site routes
                'dashboard' => 'site/dashboard',
                'profile' => 'site/profile',

                // Master Page routes
                'master-page' => 'master-page/index',
                'master-page/create' => 'master-page/create',
                'master-page/view/<id:\d+>' => 'master-page/view',
                'master-page/update/<id:\d+>' => 'master-page/update',
                'master-page/delete/<id:\d+>' => 'master-page/delete',
                'master-page/toggle/<id:\d+>' => 'master-page/toggle',
                'master-page/get-pages' => 'master-page/get-pages',

                // Master Form routes
                'master-form' => 'master-form/index',
                'master-form/create' => 'master-form/create',
                'master-form/view/<id:\d+>' => 'master-form/view',
                'master-form/preview/<id:\d+>' => 'master-form/preview',
                'master-form/submit/<id:\d+>' => 'master-form/submit',
                'master-form/update/<id:\d+>' => 'master-form/update',
                'master-form/delete/<id:\d+>' => 'master-form/delete',
                'master-form/duplicate/<id:\d+>' => 'master-form/duplicate',

                // Master Menu routes
                'master-menu' => 'master-menu/index',
                'master-menu/create' => 'master-menu/create',
                'master-menu/view/<id:\d+>' => 'master-menu/view',
                'master-menu/update/<id:\d+>' => 'master-menu/update',
                'master-menu/delete/<id:\d+>' => 'master-menu/delete',
                'master-menu/toggle/<id:\d+>' => 'master-menu/toggle',
                'master-menu/get-all-menus' => 'master-menu/get-all-menus',

                // Form Placement routes
                'form-placement/save-placement' => 'form-placement/save-placement',
                'form-placement/get-placement' => 'form-placement/get-placement',
                'form-placement/get-menu-list' => 'form-placement/get-menu-list',
                'form-placement/create-menu' => 'form-placement/create-menu',
                'form-placement/update-menu' => 'form-placement/update-menu',
                'form-placement/delete-menu' => 'form-placement/delete-menu',
                'form-placement/get-menu-tree' => 'form-placement/get-menu-tree',
                'form-placement/update-order' => 'form-placement/update-order',
                'form-placement/get-icons' => 'form-placement/get-icons',
                'form-placement/view/<slug:[a-zA-Z0-9-_]+>' => 'form-placement/view',

                // Dynamic Page routes
                'page/view/<id:\d+>' => 'page/view',
                'page/<slug:[a-zA-Z0-9-_]+>' => 'master-page/view-dynamic',
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
    // Hanya aktifkan debug jika diperlukan untuk debugging
    $enableDebug = false; // Set ke true hanya saat perlu debugging
    
    if ($enableDebug && class_exists('yii\debug\Module')) {
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
