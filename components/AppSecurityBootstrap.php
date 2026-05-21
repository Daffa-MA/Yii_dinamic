<?php

namespace app\components;

use Yii;
use yii\base\ActionEvent;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Response;

class AppSecurityBootstrap implements BootstrapInterface
{
    /**
     * Routes that should not be rate-limited or decorated with browser headers
     * because they are public infrastructure endpoints.
     *
     * @var string[]
     */
    private const SKIP_ROUTE_PREFIXES = [
        'site/error',
        'debug',
        'gii',
        'assets',
    ];

    private function isRateLimitDisabled(): bool
    {
        $value = getenv('YII_DISABLE_RATE_LIMIT');
        if ($value === false || $value === '') {
            $value = getenv('APP_DISABLE_RATE_LIMIT');
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function bootstrap($app)
    {
        $this->attachSecurityHeaders();

        $app->on(Application::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
            if ($this->isRateLimitDisabled()) {
                $this->attachSecurityHeaders();
                return;
            }

            $route = trim((string)Yii::$app->requestedRoute, '/');
            if ($this->shouldSkipForRoute($route)) {
                return;
            }

            $this->attachSecurityHeaders();

            $limit = $this->resolveRateLimit($route);
            if ($limit === null) {
                return;
            }

            $result = $this->consumeRateLimit(
                $limit['bucket'],
                $this->resolveClientIdentifier(),
                (int)$limit['window'],
                (int)$limit['max']
            );

            if ($result['allowed']) {
                return;
            }

            $event->isValid = false;
            $event->handled = true;

            $response = Yii::$app->response;
            $response->statusCode = 429;
            $response->headers->set('Retry-After', (string)$result['retryAfter']);
            $response->headers->set('X-RateLimit-Limit', (string)$limit['max']);
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string)$result['resetAt']);

            if ($response->format === Response::FORMAT_JSON || Yii::$app->request->isAjax) {
                $response->format = Response::FORMAT_JSON;
                $response->data = [
                    'success' => false,
                    'message' => 'Terlalu banyak request. Coba lagi beberapa saat.',
                ];
                return;
            }

            $response->content = '<!doctype html><html lang="id"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                . '<title>Too Many Requests</title>'
                . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f8fafc;color:#0f172a;padding:24px}'
                . '.card{max-width:560px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:28px;box-shadow:0 12px 40px rgba(15,23,42,.08)}'
                . 'h1{margin:0 0 10px;font-size:28px}p{margin:0;color:#475569;line-height:1.6}</style></head><body>'
                . '<main class="card"><h1>Too Many Requests</h1><p>Permintaan terlalu sering. Silakan tunggu sebentar lalu coba lagi.</p></main></body></html>';
        });
    }

    private function attachSecurityHeaders(): void
    {
        $response = Yii::$app->response;
        if ($response === null) {
            return;
        }

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        if ($this->isSecureRequest()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

    private function isSecureRequest(): bool
    {
        $request = Yii::$app->request;
        if ($request->isSecureConnection) {
            return true;
        }

        $forwardedProto = strtolower(trim((string)$request->headers->get('x-forwarded-proto', '')));
        return $forwardedProto === 'https';
    }

    private function shouldSkipForRoute(string $route): bool
    {
        if ($route === '') {
            return true;
        }

        foreach (self::SKIP_ROUTE_PREFIXES as $prefix) {
            if ($route === $prefix || strpos($route, $prefix . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{bucket:string,window:int,max:int}|null
     */
    private function resolveRateLimit(string $route): ?array
    {
        $normalized = strtolower(trim($route, '/'));
        if ($normalized === '') {
            return null;
        }

        $writePrefixes = [
            'create',
            'update',
            'delete',
            'save',
            'submit',
            'upload',
            'reset',
            'duplicate',
            'toggle',
            'execute-sql',
            'preview-sql',
            'delete-row',
            'mark-read',
            'mark-all-read',
        ];

        if (preg_match('/^(site\/login|project\/login|project\/change-password)$/', $normalized) === 1) {
            return ['bucket' => 'auth', 'window' => 300, 'max' => 12];
        }

        if (strpos($normalized, 'api/notification/') === 0) {
            return ['bucket' => 'notification', 'window' => 60, 'max' => 180];
        }

        foreach ($writePrefixes as $prefix) {
            if (strpos($normalized, $prefix) !== false) {
                return ['bucket' => 'write', 'window' => 60, 'max' => 60];
            }
        }

        if (
            strpos($normalized, 'master-page/preview') === 0
            || strpos($normalized, 'master-form/preview') === 0
            || strpos($normalized, 'master-form/submit') === 0
            || strpos($normalized, 'page/view') === 0
            || strpos($normalized, 'workspace-dashboard/') === 0
            || strpos($normalized, 'dashboard/') === 0
        ) {
            return ['bucket' => 'preview', 'window' => 60, 'max' => 120];
        }

        return ['bucket' => 'default', 'window' => 60, 'max' => 240];
    }

    /**
     * @return array{allowed:bool,retryAfter:int,resetAt:int}
     */
    private function consumeRateLimit(string $bucket, string $identifier, int $window, int $max): array
    {
        $cache = Yii::$app->cache;
        $now = time();
        $key = 'rate-limit:' . md5($bucket . '|' . $identifier);
        $record = $cache->get($key);

        if (!is_array($record) || !isset($record['count'], $record['resetAt']) || (int)$record['resetAt'] <= $now) {
            $record = [
                'count' => 0,
                'resetAt' => $now + $window,
            ];
        }

        $record['count'] = (int)$record['count'] + 1;
        $remainingTtl = max(1, (int)$record['resetAt'] - $now);
        $cache->set($key, $record, $remainingTtl);

        return [
            'allowed' => (int)$record['count'] <= $max,
            'retryAfter' => max(1, (int)$record['resetAt'] - $now),
            'resetAt' => (int)$record['resetAt'],
        ];
    }

    private function resolveClientIdentifier(): string
    {
        $request = Yii::$app->request;
        if (!Yii::$app->user->isGuest && Yii::$app->user->id !== null) {
            return 'u:' . (string)Yii::$app->user->id;
        }

        $ip = trim((string)$request->userIP);
        if ($ip === '') {
            $ip = 'unknown-ip';
        }

        $agent = trim((string)$request->userAgent);
        if ($agent !== '') {
            $agent = substr(hash('sha256', $agent), 0, 16);
        }

        return 'ip:' . $ip . ':' . $agent;
    }
}
