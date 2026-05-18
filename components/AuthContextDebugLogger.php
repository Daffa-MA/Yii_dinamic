<?php

namespace app\components;

use Yii;

class AuthContextDebugLogger
{
    public static function log(string $reason, array $extra = []): void
    {
        try {
            $commanderAuth = new CommanderAuthContext();
            $projectContext = new ActiveProjectContext();
            $projectAuth = new ProjectAuthContext();
            $host = Yii::$app->request->getHostName();
            $projectId = $projectContext->getActiveProjectId();
            $resolvedProjectId = $projectContext->getResolvedDomainProjectId();

            $payload = [
                'time' => date('c'),
                'host' => $host,
                'currentRoute' => trim((string)Yii::$app->requestedRoute, '/'),
                'commander_authenticated' => $commanderAuth->isAuthenticated(),
                'commander_role' => $commanderAuth->getRole(),
                'commander_superadmin' => $commanderAuth->isSuperAdmin(),
                'project_id' => $projectId,
                'resolved_project_id' => $resolvedProjectId,
                'project_auth_detected' => $projectId !== null ? $projectAuth->isAuthenticated($projectId) : false,
                'superadmin_mode' => $projectContext->isSuperAdminMode(),
                'reason' => $reason,
            ];

            if ($extra !== []) {
                $payload['extra'] = $extra;
            }

            $file = Yii::getAlias('@runtime/logs/auth-context-debug.log');
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            Yii::warning('Auth context debug log failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
