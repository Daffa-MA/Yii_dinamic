<?php

namespace app\components;

use Yii;

class PermissionDebugLogger
{
    public static function log(array $payload): void
    {
        try {
            $file = Yii::getAlias('@runtime/logs/permission-debug.log');
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $payload['time'] = $payload['time'] ?? date('c');
            @file_put_contents(
                $file,
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $e) {
            Yii::warning('Permission debug log failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
