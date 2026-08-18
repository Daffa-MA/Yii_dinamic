<?php

namespace app\components;

use Yii;

/**
 * CommanderLoginLimiter - lightweight, cache-backed brute-force protection for
 * the Commander (superadmin) login form.
 *
 * Strategy (architecture-appropriate: reuses the application cache component,
 * no new dependency, no schema change, no permanent lockouts):
 *
 *  - Failed attempts are tracked per normalized username AND per client IP in
 *    the cache with a short TTL (default 15 minutes).
 *  - When either counter reaches the limit (default 5), the login is refused
 *    with a "try again later" message until the window expires.
 *  - A successful login for the target username clears that username's
 *    counter, so legitimate users are never locked out permanently.
 *  - Malformed attempts (empty username or empty password) are not counted,
 *    which prevents an attacker from denying a victim by flooding empty
 *    submissions.
 *  - The block window is bounded by TTL: without further failed attempts the
 *    lock always expires on its own.
 */
class CommanderLoginLimiter
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_SECONDS = 900;

    private const PREFIX = 'commander-login:';

    /**
     * Optional namespacing for the per-username counter. When set, the
     * username counter is scoped to this value (e.g. 'project:12') so that
     * identical usernames in different tenants/workspaces do not share a
     * lockout. The client-IP counter always stays global.
     */
    private ?string $scope = null;

    public function __construct(?string $scope = null)
    {
        $this->scope = $scope;
    }

    /**
     * Whether the current username/IP is currently blocked.
     *
     * @return array{message: string}|null Lock payload when blocked, null otherwise.
     */
    public function isLocked(string $username): ?array
    {
        $username = strtolower(trim($username));
        $cache = $this->cache();
        if ($cache === null) {
            return null;
        }

        if ($username !== '') {
            $userData = $this->readCounter($this->key('user', $username));
            if ($this->isBlocked($userData)) {
                return $this->lockPayload($userData);
            }
        }

        $ipData = $this->readCounter($this->key('ip', $this->clientIp()));
        if ($this->isBlocked($ipData)) {
            return $this->lockPayload($ipData);
        }

        return null;
    }

    /**
     * Records a failed login attempt. Empty-username / empty-password requests
     * are ignored so a flood of malformed requests cannot lock out an account.
     */
    public function onFailure(string $username, string $password): void
    {
        $username = strtolower(trim($username));
        if ($username === '' || trim($password) === '') {
            return;
        }

        $cache = $this->cache();
        if ($cache === null) {
            return;
        }

        $this->incrementCounter($this->key('user', $username));
        $this->incrementCounter($this->key('ip', $this->clientIp()));
    }

    /**
     * Clears the failure counter for a username after a successful login.
     */
    public function onSuccess(string $username): void
    {
        $username = strtolower(trim($username));
        if ($username === '') {
            return;
        }

        $cache = $this->cache();
        if ($cache === null) {
            return;
        }

        $cache->delete($this->key('user', $username));
    }

    private function cache(): ?\yii\caching\CacheInterface
    {
        try {
            return Yii::$app->cache instanceof \yii\caching\CacheInterface
                ? Yii::$app->cache
                : null;
        } catch (\Throwable $e) {
            Yii::warning('Commander login limiter cache unavailable: ' . $e->getMessage(), 'auth');
            return null;
        }
    }

    private function key(string $type, string $value): string
    {
        if ($type === 'user' && $this->scope !== null && $this->scope !== '') {
            $value = $this->scope . '|' . $value;
        }

        return self::PREFIX . $type . ':' . md5($value);
    }

    private function clientIp(): string
    {
        try {
            $ip = (string)Yii::$app->request->getUserIP();
        } catch (\Throwable $e) {
            return '0.0.0.0';
        }

        return $ip !== '' ? $ip : '0.0.0.0';
    }

    /**
     * @return array{count: int, expires_at: int}
     */
    private function readCounter(string $key): array
    {
        $data = $this->cache()->get($key);
        if (!is_array($data) || !isset($data['count']) || !isset($data['expires_at'])) {
            return ['count' => 0, 'expires_at' => 0];
        }

        return [
            'count' => (int)$data['count'],
            'expires_at' => (int)$data['expires_at'],
        ];
    }

    private function incrementCounter(string $key): void
    {
        $now = time();
        $data = $this->readCounter($key);
        $count = (int)$data['count'] + 1;

        $stored = $this->cache()->set($key, [
            'count' => $count,
            'expires_at' => $now + self::WINDOW_SECONDS,
        ], self::WINDOW_SECONDS);

        if ($stored === false) {
            Yii::warning('Commander login limiter could not persist attempt counter (cache store failure).', 'auth');
        }
    }

    private function isBlocked(array $data): bool
    {
        return (int)$data['count'] >= self::MAX_ATTEMPTS;
    }

    /**
     * @param array{count: int, expires_at: int} $data
     * @return array{message: string}
     */
    private function lockPayload(array $data): array
    {
        $remaining = max(1, (int)$data['expires_at'] - time());

        return [
            'message' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . $this->humanize($remaining) . '.',
        ];
    }

    private function humanize(int $seconds): string
    {
        $minutes = (int)ceil($seconds / 60);
        if ($minutes >= 60) {
            return sprintf('%d jam', (int)ceil($minutes / 60));
        }

        return $minutes <= 1 ? 'beberapa saat' : sprintf('%d menit', $minutes);
    }
}
