<?php

namespace app\components;

use Yii;

class ProjectOpenDebugLogger
{
    public static function log(string $stage, array $context = []): void
    {
        try {
            $session = Yii::$app->session;
            if (!$session->isActive) {
                $session->open();
            }

            $commanderAuth = new CommanderAuthContext();
            $activeProjectContext = new ActiveProjectContext();
            $payload = array_merge([
                'stage' => $stage,
                'time' => date('Y-m-d H:i:s'),
                'route' => (string)Yii::$app->requestedRoute,
                'path' => trim((string)Yii::$app->request->pathInfo, '/'),
                'current_host' => (new DomainContext())->currentHost(),
                'commander_auth' => $session->get(CommanderAuthContext::SESSION_KEY_AUTH, null),
                'commander_role' => $commanderAuth->getRole(),
                'commander_superadmin' => $commanderAuth->isSuperAdmin(),
                'active_project_id' => $activeProjectContext->getActiveProjectId(),
                'resolved_domain_project_id' => $activeProjectContext->getResolvedDomainProjectId(),
                'superadmin_mode' => $activeProjectContext->isSuperAdminMode(),
            ], $context);

            $lines = [str_repeat('-', 72)];
            foreach ($payload as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } elseif ($value === null) {
                    $value = 'null';
                }

                $lines[] = $key . '=' . (string)$value;
            }

            file_put_contents(
                Yii::getAlias('@runtime/logs/project-open-debug.log'),
                implode("\n", $lines) . "\n\n",
                FILE_APPEND
            );
        } catch (\Throwable $e) {
            Yii::warning('Project open debug log failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
