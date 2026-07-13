<?php

$params = require __DIR__ . '/params.php';
$dbBundle = require __DIR__ . '/db.php';
$db = $dbBundle['db'] ?? $dbBundle;

if (!function_exists('appTrustedProxyCidrs')) {
    /**
     * Returns proxy CIDRs that are allowed to forward secure headers.
     *
     * Override with `YII_TRUSTED_PROXY_CIDRS` when the proxy network is known.
     * Defaults cover localhost and common private/Docker ranges used by Traefik/Coolify.
     *
     * @return string[]
     */
    function appTrustedProxyCidrs(): array
    {
        $configured = getenv('YII_TRUSTED_PROXY_CIDRS');
        if ($configured === false || trim($configured) === '') {
            $configured = getenv('TRUSTED_PROXY_CIDRS');
        }

        if (is_string($configured) && trim($configured) !== '') {
            return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $configured) ?: [])));
        }

        return [
            '127.0.0.1',
            '::1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '100.64.0.0/10',
            'fc00::/7',
        ];
    }
}

if (!function_exists('appSessionCookieParams')) {
    /**
     * Returns cookie params that keep Commander auth available across subdomains
     * while staying safe on localhost/dev hosts.
     *
     * @return array<string, mixed>
     */
    function appSessionCookieParams(): array
    {
        $params = [
            'path' => '/',
            'httpOnly' => true,
            'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
        ];

        $configured = getenv('APP_COOKIE_DOMAIN');
        if (!is_string($configured) || trim($configured) === '') {
            $configured = getenv('YII_COOKIE_DOMAIN');
        }

        $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $isLocalHost = $host === '' || $host === 'localhost' || $host === '127.0.0.1' || str_ends_with($host, '.local');

        if (is_string($configured) && trim($configured) !== '') {
            $domain = trim($configured);
            $params['domain'] = str_starts_with($domain, '.') ? $domain : '.' . ltrim($domain, '.');
            $params['secure'] = true;
            return $params;
        }

        if ($isLocalHost) {
            $params['secure'] = false;
            return $params;
        }

        $rootDomain = getenv('APP_ROOT_DOMAIN');
        if ($rootDomain === false || trim($rootDomain) === '') {
            $rootDomain = (string)($GLOBALS['params']['rootDomain'] ?? 'appforge.web.id');
        }
        $rootDomain = strtolower(trim((string)$rootDomain));
        $rootDomain = trim($rootDomain, '.');

        if ($rootDomain !== '' && ($host === $rootDomain || str_ends_with($host, '.' . $rootDomain))) {
            $params['domain'] = '.' . $rootDomain;
            $params['secure'] = true;
        }

        return $params;
    }
}

if (!function_exists('appCacheComponentConfig')) {
    /**
     * Returns the most suitable cache backend available in the current runtime.
     * Falls back to FileCache when Redis/Memcached is unavailable.
     *
     * @return array<string, mixed>
     */
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
            if ($driver === 'redis') {
                Yii::warning('YII_CACHE_DRIVER=redis requested but yii2-redis is not available. Falling back to FileCache.', 'app');
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
            if ($driver === 'memcached') {
                Yii::warning('YII_CACHE_DRIVER=memcached requested but memcached/memcache is not available. Falling back to FileCache.', 'app');
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
    'id' => 'basic',
    'name' => 'Architectural Editor',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'app\\components\\DomainProjectResolver', 'app\\components\\ProjectAccessBootstrap', 'app\\components\\AppSecurityBootstrap'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@storage' => dirname(__DIR__) . '/storage',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'blwvTdeGu2Ngh7Y3AaB_BbXDgKv5f1im',
            // Traefik/Coolify terminates TLS and forwards X-Forwarded-* headers.
            // Cloudflare must use SSL mode Full or Full strict, not Flexible.
            'trustedHosts' => appTrustedProxyCidrs(),
        ],
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'cache' => appCacheComponentConfig(),
        'session' => [
            'class' => 'app\components\NoSetPathSession',
            'cookieParams' => appSessionCookieParams(),
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            // Keep Commander logout verification deterministic: do not restore
            // superadmin from the Yii identity cookie after /site/logout.
            'enableAutoLogin' => false,
            'identityCookie' => array_merge(['name' => '_identity'], appSessionCookieParams()),
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
                'tables/dropdown-options/<table_id:\d+>' => 'table-builder/dropdown-options',
                'tables/foreign-key-options/<columnId:\d+>' => 'table-builder/get-foreign-key-options',
                'tables/view/<id:\d+>' => 'table-builder/view',
                'tables/export/<id:\d+>' => 'table-builder/export',
                'tables/update/<id:\d+>' => 'table-builder/update',
                'tables/execute/<id:\d+>' => 'table-builder/execute-sql',
                'tables/preview/<id:\d+>' => 'table-builder/preview-sql',
                'tables/delete/<id:\d+>' => 'table-builder/delete',
                'tables/get-column-metadata' => 'table-builder/get-column-metadata',

                // Master Datatable routes
                'master-datatable' => 'master-datatable/index',
                'master-datatable/create' => 'master-datatable/create',
                'master-datatable/update/<id:\d+>' => 'master-datatable/update',
                'master-datatable/delete/<id:\d+>' => 'master-datatable/delete',
                'master-datatable/reload/<id:\d+>' => 'master-datatable/reload',
                'master-datatable/export/<id:\d+>' => 'master-datatable/export',
                'master-datatable/export-table/<table_id:\d+>' => 'master-datatable/export-table',
                'master-datatable/approve-row/<id:\d+>' => 'master-datatable/approve-row',
                'master-datatable/delete-row/<table_id:\d+>' => 'master-datatable/delete-row',

                // Master Form routes
                'master-form' => 'master-form/index',
                'master-form/create' => 'master-form/create',
                'master-form/view/<id:\d+>' => 'master-form/view',
                'master-form/preview/<id:\d+>' => 'master-form/preview',
                'master-form/submit/<id:\d+>' => 'master-form/submit',
                'master-form/update/<id:\d+>' => 'master-form/update',
                'master-form/delete/<id:\d+>' => 'master-form/delete',
                'master-form/duplicate/<id:\d+>' => 'master-form/duplicate',
                'master-form/relation-picker-data' => 'master-form/relation-picker-data',
                'master-form/relation-picker-search' => 'master-form/relation-picker-search',
                'master-form/resolve-autofill' => 'master-form/resolve-autofill',

                // Project routes (main landing after login)
                'project-list' => 'project/index',
                'project-list/update' => 'project/update',
                'project-list/delete' => 'project/delete',
                'project-list/select/<id:\d+>' => 'project/select',
                'project-list/activate/<id:\d+>' => 'project/activate',
                'project-list/open-workspace/<id:\d+>' => 'project/open-workspace',
                'project-list/update/<id:\d+>' => 'project/update',
                'project-list/delete/<id:\d+>' => 'project/delete',
                'project-list/firebase-users' => 'project/firebase-users',
                'project/profile' => 'project/profile',
                'project/login/<id:\d+>' => 'project/login',
                'project/login' => 'project/login',
                'project/change-password' => 'project/change-password',
                'project/logout' => 'project/logout',

                // Multi-project Dashboard routes (pretty URL for internal workspace dashboard)
                'workspace-dashboard' => 'dashboard/index',
                'workspace-dashboard/<project_id:\d+>' => 'dashboard/index',
                'workspace-dashboard/handle-menu' => 'dashboard/handle-menu',
                'workspace-dashboard/get-forms' => 'dashboard/get-forms',
                'workspace-dashboard/render-page' => 'dashboard/render-page',

                // Site routes
                'dashboard' => 'site/dashboard',
                'profile' => 'site/profile',

                // Card Widget routes
                'card/get-config' => 'card/get-config',
                'card/get-tables' => 'card/get-tables',
                'card/get-columns' => 'card/get-columns',
                'card/preview' => 'card/preview',
                'card/search-icons' => 'card/search-icons',
                'card/get-registries' => 'card/get-registries',

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
                'master-form/relation-picker-data' => 'master-form/relation-picker-data',
                'master-form/relation-picker-search' => 'master-form/relation-picker-search',
                'master-form/resolve-autofill' => 'master-form/resolve-autofill',

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

                // Workspace Settings routes
                'settings/workspace' => 'workspace-settings/index',
                'settings/workspace/permissions' => 'workspace-settings/permissions',
                'settings/workspace/permission-inspector' => 'workspace-settings/permission-inspector',
                'settings/workspace/save' => 'workspace-settings/save',
                'settings/workspace/reset' => 'workspace-settings/reset',
            ],
        ],
    ],
    'params' => $params,
    'on beforeRequest' => function () {
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
            Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Authorization');
            Yii::$app->response->setStatusCode(200);
            Yii::$app->end();
        }
    },
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
