<?php

if (!function_exists('dbBootstrapLog')) {
    function dbBootstrapLog(string $message): void
    {
        if (getenv('DB_BOOTSTRAP_DEBUG') !== '1') {
            return;
        }

        $logFile = dirname(__DIR__) . '/runtime/logs/db-bootstrap.log';

        if (!is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0777, true);
        }

        @file_put_contents(
            $logFile,
            sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $message, PHP_EOL),
            FILE_APPEND
        );
    }
}

if (!function_exists('dbStartsWith')) {
    function dbStartsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('dbEndsWith')) {
    function dbEndsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('dbEnvValue')) {
    function dbEnvValue(array $keys): array
    {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                return [$key, $value];
            }

            if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
                return [$key, $_ENV[$key]];
            }

            if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
                return [$key, $_SERVER[$key]];
            }
        }

        return [null, null];
    }
}

if (!function_exists('dbParseUrl')) {
    function dbParseUrl(?string $url): array
    {
        if (!$url) {
            return [];
        }

        $parsed = parse_url($url);
        if (!$parsed) {
            return [];
        }

        $database = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';

        return [
            'host' => $parsed['host'] ?? null,
            'port' => $parsed['port'] ?? null,
            'dbname' => $database ? rawurldecode($database) : null,
            'username' => isset($parsed['user']) ? rawurldecode($parsed['user']) : null,
            'password' => isset($parsed['pass']) ? rawurldecode($parsed['pass']) : null,
        ];
    }
}

if (!function_exists('dbNormalizeMysqlHost')) {
    function dbNormalizeMysqlHost(?string $host): string
    {
        $h = strtolower(trim((string)$host));
        if ($h === 'localhost') {
            return '127.0.0.1';
        }

        return $h;
    }
}

if (!function_exists('dbMysqlHostsEquivalent')) {
    function dbMysqlHostsEquivalent(?string $a, ?string $b): bool
    {
        return dbNormalizeMysqlHost($a) === dbNormalizeMysqlHost($b);
    }
}

if (!function_exists('dbMysqlConnectionOptions')) {
    /**
     * @return array<string, mixed>
     */
    function dbMysqlConnectionOptions(): array
    {
        return [
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCache' => 'cache',
            'schemaCacheDuration' => 86400,
            'enableQueryCache' => true,
            'queryCacheDuration' => 120,
        ];
    }
}

if (!function_exists('dbBuildMysqlConnectionFromParts')) {
    /**
     * @return array<string, mixed>
     */
    function dbBuildMysqlConnectionFromParts(
        string $host,
        $port,
        string $dbname,
        string $username,
        string $password
    ): array {
        $port = (string)$port;

        return array_merge([
            'class' => 'yii\db\Connection',
            'dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $dbname),
            'username' => $username,
            'password' => $password,
        ], dbMysqlConnectionOptions());
    }
}

if (!function_exists('dbBuildMysqlConnectionFromParsedUrl')) {
    /**
     * @param array{host?: ?string, port?: ?int, dbname?: ?string, username?: ?string, password?: ?string} $parsed
     * @return array<string, mixed>|null
     */
    function dbBuildMysqlConnectionFromParsedUrl(array $parsed, string $fallbackDbName = 'mysql'): ?array
    {
        $host = isset($parsed['host']) ? trim((string)$parsed['host']) : '';
        if ($host === '') {
            return null;
        }

        $port = (string)($parsed['port'] ?? '3306');
        $dbname = trim((string)($parsed['dbname'] ?? ''));
        if ($dbname === '') {
            $dbname = $fallbackDbName;
        }

        $username = (string)($parsed['username'] ?? 'root');
        $password = (string)($parsed['password'] ?? '');

        return dbBuildMysqlConnectionFromParts($host, $port, $dbname, $username, $password);
    }
}

if (!function_exists('dbCanConnect')) {
    /**
     * Best-effort connectivity probe for optional failover decision.
     *
     * @param array<string, mixed> $connectionConfig
     */
    function dbCanConnect(array $connectionConfig): bool
    {
        $dsn = isset($connectionConfig['dsn']) ? (string)$connectionConfig['dsn'] : '';
        if ($dsn === '') {
            return false;
        }

        $username = isset($connectionConfig['username']) ? (string)$connectionConfig['username'] : '';
        $password = isset($connectionConfig['password']) ? (string)$connectionConfig['password'] : '';

        try {
            new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            return true;
        } catch (Throwable $e) {
            dbBootstrapLog('DB connectivity probe failed: ' . $e->getMessage());
            return false;
        }
    }
}

$envFile = dirname(__DIR__) . '/.env';
$loadedDotenv = false;
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || dbStartsWith($line, '#') || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if ((dbStartsWith($value, '"') && dbEndsWith($value, '"')) || (dbStartsWith($value, "'") && dbEndsWith($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        $loadedDotenv = true;
    }
}

$configuredDriver = getenv('YII_DB_DRIVER');
$driver = $configuredDriver ? strtolower($configuredDriver) : 'mysql';

if ($driver !== 'mysql') {
    dbBootstrapLog(sprintf('Unsupported DB driver requested: %s', $driver));
    throw new RuntimeException(sprintf('Unsupported YII_DB_DRIVER value: %s', $driver));
}

[$sourceKey, $databaseUrl] = dbEnvValue([
    // For apps hosted outside Railway (e.g. Render), always prefer public DB URL first.
    'DATABASE_PUBLIC_URL',
    'MYSQL_PUBLIC_URL',
    'RAILWAY_DATABASE_PUBLIC_URL',
    'RAILWAY_MYSQL_PUBLIC_URL',
    'DATABASE_URL',
    'MYSQL_URL',
    'RAILWAY_DATABASE_URL',
    'RAILWAY_MYSQL_URL',
]);
$parsedDatabaseUrl = dbParseUrl($databaseUrl);

[$hostKey, $hostValue] = dbEnvValue(['YII_DB_HOST', 'DB_HOST', 'MYSQLHOST', 'MYSQL_HOST', 'RAILWAY_MYSQL_HOST']);
[$portKey, $portValue] = dbEnvValue(['YII_DB_PORT', 'DB_PORT', 'MYSQLPORT', 'MYSQL_PORT', 'RAILWAY_MYSQL_PORT']);
[$nameKey, $nameValue] = dbEnvValue(['YII_DB_NAME', 'DB_DATABASE', 'MYSQLDATABASE', 'MYSQL_DATABASE', 'RAILWAY_MYSQL_DATABASE']);
[$userKey, $userValue] = dbEnvValue(['YII_DB_USER', 'DB_USERNAME', 'MYSQLUSER', 'MYSQL_USER', 'RAILWAY_MYSQL_USER']);
[$passwordKey, $passwordValue] = dbEnvValue(['YII_DB_PASSWORD', 'DB_PASSWORD', 'MYSQLPASSWORD', 'MYSQL_PASSWORD', 'RAILWAY_MYSQL_PASSWORD']);

// Match Yii2 basic template: YII_ENV_DEV is defined in entry scripts (web/index.php, yii).
$isLocalDev = (defined('YII_ENV_DEV') && YII_ENV_DEV)
    || (defined('YII_ENV') && YII_ENV === 'dev');
$useUrlInLocalDev = getenv('YII_DB_USE_URL_IN_DEV') === '1';
$forceLocalPrimary = getenv('YII_DB_FORCE_LOCAL_PRIMARY') === '1';

// Primary app DB: localhost when forcing local primary (boss workflow) or classic local dev.
$useLocalPrimary = $forceLocalPrimary || ($isLocalDev && !$useUrlInLocalDev);

if ($useLocalPrimary) {
    $dbHost = $hostValue ?? '127.0.0.1';
    $dbPort = $portValue ?? '3306';
    $dbName = $nameValue ?? 'yii2basic';
    $dbUser = $userValue ?? 'root';
    $dbPassword = $passwordValue ?? '';
    $dbSource = $forceLocalPrimary ? 'local-forced-primary' : ($hostKey ?: 'local-default');
} else {
    // URL-based primary (e.g. containerized deploy on Coolify internal network).
    $dbHost = $parsedDatabaseUrl['host'] ?? $hostValue ?? 'mysql-database-xb782ufttxvm1k992vvkup98';
    $dbPort = (string)($parsedDatabaseUrl['port'] ?? $portValue ?? '3306');
    $dbName = $parsedDatabaseUrl['dbname'] ?? $nameValue ?? 'default';
    $dbUser = $parsedDatabaseUrl['username'] ?? $userValue ?? 'mysql';
    $dbPassword = $parsedDatabaseUrl['password'] ?? $passwordValue ?? '';
    $dbSource = $sourceKey ?: ($hostKey ?: 'fallback');
}

$dbPrimary = dbBuildMysqlConnectionFromParts($dbHost, $dbPort, $dbName, $dbUser, $dbPassword);

$dbBackup = null;
$backupSyncDisabled = getenv('YII_DB_BACKUP_SYNC') === '0';
if (!$backupSyncDisabled) {
    [, $explicitBackupUrl] = dbEnvValue([
        'YII_DB_BACKUP_URL',
        'YII_DB_REMOTE_BACKUP_URL',
        'MYSQL_BACKUP_URL',
    ]);

    $candidateBackupUrl = $explicitBackupUrl ?: '';
    if ($candidateBackupUrl === '' && $useLocalPrimary) {
        [, $candidateBackupUrl] = dbEnvValue([
            'DATABASE_PUBLIC_URL',
            'MYSQL_PUBLIC_URL',
            'RAILWAY_DATABASE_PUBLIC_URL',
            'RAILWAY_MYSQL_PUBLIC_URL',
            'DATABASE_URL',
            'MYSQL_URL',
            'RAILWAY_DATABASE_URL',
            'RAILWAY_MYSQL_URL',
        ]);
    }

    if ($candidateBackupUrl !== '') {
        $parsedBackup = dbParseUrl($candidateBackupUrl);
        $backupHost = isset($parsedBackup['host']) ? trim((string)$parsedBackup['host']) : '';
        if ($backupHost !== '' && !dbMysqlHostsEquivalent($backupHost, $dbHost)) {
            $dbBackup = dbBuildMysqlConnectionFromParsedUrl($parsedBackup);
        }
    }
}

$allowFailoverToBackup = getenv('YII_DB_FAILOVER_TO_BACKUP') === '1';
if ($allowFailoverToBackup && is_array($dbBackup) && !dbCanConnect($dbPrimary)) {
    dbBootstrapLog('Primary DB unreachable. Failover activated to backup connection.');
    $failedPrimary = $dbPrimary;
    $dbPrimary = $dbBackup;
    $dbBackup = $failedPrimary;
}

if (is_array($dbBackup) && !$backupSyncDisabled) {
    $dbPrimary['commandClass'] = 'app\components\BackupSyncCommand';
}

dbBootstrapLog(sprintf(
    'DB config loaded. dotenv=%s driver=%s source=%s host=%s port=%s db=%s user=%s backup=%s',
    $loadedDotenv ? 'yes' : 'no',
    $driver,
    $dbSource,
    $dbHost,
    $dbPort,
    $dbName,
    $dbUser,
    $dbBackup ? 'yes' : 'no'
));

return [
    'db' => $dbPrimary,
    'dbBackup' => $dbBackup,
];
