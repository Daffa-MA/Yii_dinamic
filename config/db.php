<?php

$configuredDriver = getenv('YII_DB_DRIVER');

if ($configuredDriver) {
    $driver = $configuredDriver;
} elseif (extension_loaded('pdo_sqlite')) {
    $driver = 'sqlite';
} elseif (extension_loaded('pdo_mysql')) {
    $driver = 'mysql';
} else {
    throw new RuntimeException('No supported PDO driver found. Enable pdo_mysql or pdo_sqlite.');
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
} elseif ($driver === 'sqlite') {
    return [
        'class' => 'yii\db\Connection',
        'dsn' => 'sqlite:@app/runtime/app.db',
        'charset' => 'utf8',
    ];
}

throw new RuntimeException(sprintf('Unsupported YII_DB_DRIVER value: %s', $driver));
