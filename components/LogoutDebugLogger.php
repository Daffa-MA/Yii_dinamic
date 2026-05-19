<?php

namespace app\components;

use Yii;

class LogoutDebugLogger
{
    public static function log(string $stage, array $context = []): void
    {
        $file = Yii::getAlias('@runtime/logs/logout-debug.log');
        $payload = [
            'time' => date('Y-m-d H:i:s'),
            'stage' => $stage,
            'route' => trim((string)Yii::$app->requestedRoute, '/'),
            'path' => trim((string)Yii::$app->request->pathInfo, '/'),
            'host' => Yii::$app->request->hostName,
            'url' => Yii::$app->request->absoluteUrl,
            'method' => Yii::$app->request->method,
            'role' => (new CommanderAuthContext())->getRole(),
        ] + $context;

        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }
}
