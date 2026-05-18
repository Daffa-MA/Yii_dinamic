<?php

namespace app\components;

use Yii;

class FormFlowDebugLogger
{
    private const RENDER_LOG = 'form-render-debug.log';
    private const SUBMIT_LOG = 'form-submit-debug.log';

    public static function logRender(array $payload): void
    {
        self::write(self::RENDER_LOG, $payload);
    }

    public static function logSubmit(array $payload): void
    {
        self::write(self::SUBMIT_LOG, $payload);
    }

    private static function write(string $fileName, array $payload): void
    {
        $logDir = Yii::getAlias('@runtime/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $payload['timestamp'] = date('c');
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = '{"timestamp":"' . date('c') . '","error":"failed_to_encode_debug_payload"}';
        }

        file_put_contents($logDir . DIRECTORY_SEPARATOR . $fileName, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
