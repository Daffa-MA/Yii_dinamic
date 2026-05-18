<?php

namespace app\components;

use app\models\Project;
use Yii;

class DomainDebugLogger
{
    public static function log(string $host, string $prefix, ?Project $project, string $reason = ''): void
    {
        try {
            $context = new ActiveProjectContext();
            $activeProjectId = $context->getActiveProjectId();
            $payload = [
                'time' => date('c'),
                'host' => $host,
                'extractedPrefix' => $prefix,
                'projectFound' => $project !== null,
                'projectId' => $project !== null ? (int)$project->id : null,
                'customDomain' => $project !== null ? (string)($project->custom_domain ?? '') : null,
                'customDomainPrefix' => $project !== null ? (string)($project->custom_domain_prefix ?? '') : null,
                'activeProject' => $activeProjectId,
                'reason' => $reason,
            ];

            $logFile = Yii::getAlias('@runtime/logs/domain-debug.log');
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }

            file_put_contents(
                $logFile,
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $e) {
            Yii::warning('Failed to write domain debug log: ' . $e->getMessage(), __METHOD__);
        }
    }
}
