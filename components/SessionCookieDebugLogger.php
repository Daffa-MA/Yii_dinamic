<?php

namespace app\components;

use Yii;

class SessionCookieDebugLogger
{
    public static function log(string $stage, array $context = []): void
    {
        try {
            $session = Yii::$app->session;
            if (!$session->isActive) {
                $session->open();
            }

            $cookieParams = $session->cookieParams;
            $commanderAuth = new CommanderAuthContext();
            $activeProjectContext = new ActiveProjectContext();

            $payload = array_merge([
                'stage' => $stage,
                'time' => date('Y-m-d H:i:s'),
                'host' => (new DomainContext())->currentHost(),
                'route' => (string)Yii::$app->requestedRoute,
                'session_name' => $session->name,
                'session_id_exists' => $session->id !== '',
                'cookie_domain_config' => (string)($cookieParams['domain'] ?? ''),
                'cookie_path_config' => (string)($cookieParams['path'] ?? ''),
                'cookie_secure_config' => array_key_exists('secure', $cookieParams) ? (bool)$cookieParams['secure'] : null,
                'cookie_same_site_config' => (string)($cookieParams['sameSite'] ?? ''),
                'commander_auth' => $session->get(CommanderAuthContext::SESSION_KEY_AUTH, null),
                'commander_role' => $commanderAuth->getRole(),
                'active_project_id' => $activeProjectContext->getActiveProjectId(),
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
                Yii::getAlias('@runtime/logs/session-cookie-debug.log'),
                implode("\n", $lines) . "\n\n",
                FILE_APPEND
            );
        } catch (\Throwable $e) {
            Yii::warning('Session cookie debug log failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
