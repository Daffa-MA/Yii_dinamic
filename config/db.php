<?php

if (!function_exists('dbBootstrapLog')) {
    function dbBootstrapLog(string $message): void
    {
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

$envFile = dirname(__DIR__) . '/.env';
$loadedDotenv = false;
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
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

[$hostKey, $hostValue] = dbEnvValue(['YII_DB_HOST', 'MYSQLHOST', 'RAILWAY_MYSQL_HOST']);
[$portKey, $portValue] = dbEnvValue(['YII_DB_PORT', 'MYSQLPORT', 'RAILWAY_MYSQL_PORT']);
[$nameKey, $nameValue] = dbEnvValue(['YII_DB_NAME', 'MYSQLDATABASE', 'RAILWAY_MYSQL_DATABASE']);
[$userKey, $userValue] = dbEnvValue(['YII_DB_USER', 'MYSQLUSER', 'RAILWAY_MYSQL_USER']);
[$passwordKey, $passwordValue] = dbEnvValue(['YII_DB_PASSWORD', 'MYSQLPASSWORD', 'RAILWAY_MYSQL_PASSWORD']);

$dbHost = $parsedDatabaseUrl['host'] ?? $hostValue ?? '127.0.0.1';
$dbPort = $parsedDatabaseUrl['port'] ?? $portValue ?? '3306';
$dbName = $parsedDatabaseUrl['dbname'] ?? $nameValue ?? 'yii2basic';
$dbUser = $parsedDatabaseUrl['username'] ?? $userValue ?? 'root';
$dbPassword = $parsedDatabaseUrl['password'] ?? $passwordValue ?? '';

dbBootstrapLog(sprintf(
    'DB config loaded. dotenv=%s driver=%s source=%s host=%s port=%s db=%s user=%s',
    $loadedDotenv ? 'yes' : 'no',
    $driver,
    $sourceKey ?: ($hostKey ?: 'fallback'),
    $dbHost,
    $dbPort,
    $dbName,
    $dbUser
));

return [
    'class' => 'yii\db\Connection',
    'dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s', $dbHost, $dbPort, $dbName),
    'username' => $dbUser,
    'password' => $dbPassword,
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
