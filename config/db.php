<?php

$envFile = dirname(__DIR__) . '/.env';
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
    }
}

$configuredDriver = getenv('YII_DB_DRIVER');

if ($configuredDriver) {
    $driver = strtolower($configuredDriver);
} else {
    $driver = 'mysql';
}

if ($driver === 'mysql') {
    return [
        'class' => 'yii\db\Connection',
        'dsn' => sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            getenv('YII_DB_HOST') ?: '127.0.0.1',
            getenv('YII_DB_PORT') ?: '3306',
            getenv('YII_DB_NAME') ?: 'yii2basic'
        ),
        'username' => getenv('YII_DB_USER') ?: 'root',
        'password' => getenv('YII_DB_PASSWORD') ?: '',
        'charset' => 'utf8',

        // Schema cache options (for production environment)
        //'enableSchemaCache' => true,
        //'schemaCacheDuration' => 60,
        //'schemaCache' => 'cache',
    ];
}

throw new RuntimeException(sprintf('Unsupported YII_DB_DRIVER value: %s', $driver));
