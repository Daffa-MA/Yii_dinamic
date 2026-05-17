<?php

namespace app\components;

use Yii;

class RedirectDebugLogger
{
    public static function log(string $reason, string $targetUrl = '', ?int $projectId = null, string $sessionKey = ''): void
    {
        try {
            $session = Yii::$app->session;
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            $authState = [];

            if ($projectId !== null && $projectId > 0) {
                $projectAuth = new ProjectAuthContext();
                $sessionKey = $sessionKey !== '' ? $sessionKey : $projectAuth->getSessionKey($projectId);
                $projectSession = $session->get($sessionKey, []);
                $authState['project_session_present'] = is_array($projectSession) && !empty($projectSession['user_id']);
            }

            $authState['commander_authenticated'] = (new CommanderAuthContext())->isAuthenticated();
            $authState['commander_superadmin'] = (new CommanderAuthContext())->isSuperAdmin();

            $line = [
                'time' => date('Y-m-d H:i:s'),
                'host' => Yii::$app->request->getHostName(),
                'current_url' => Yii::$app->request->url,
                'target_url' => $targetUrl,
                'project_id' => $projectId,
                'active_project' => $activeProjectId,
                'isGuest' => Yii::$app->user->isGuest,
                'session_key' => $sessionKey,
                'auth_state' => $authState,
                'reason' => $reason,
            ];

            $file = Yii::getAlias('@runtime/logs/redirect-debug.log');
            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            @file_put_contents($file, json_encode($line, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $e) {
            Yii::warning('Redirect debug log failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
