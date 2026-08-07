<?php

namespace app\components;

use app\services\UserMappingService;
use Yii;

/**
 * CurrentIdentityContext - request-scoped accessor for the resolved "Current
 * Identity" of the authenticated workspace user inside the business domain.
 *
 * This context is the SINGLE source of truth for the domain identity of the
 * currently logged-in project user. Framework components (DataTable, Form,
 * Card, Chart, Dashboard, etc.) MUST NOT resolve identity relationships
 * themselves; they only read this context through Yii::$app->currentIdentity.
 *
 * The Current Identity is a pure O(1) read: every account carries its own
 * User Mapping (identity_table + identity_record_id stored directly on the
 * `users` row), so there is no scanning, no first-match and no looping over
 * tables. The mapping is created at account time in User Management (or via
 * mass import), never discovered per-request.
 *
 * The context is built lazily on first access and cached for the duration of
 * the request, so repeated reads never cause duplicate queries. It is primed
 * right after a successful login via ProjectAuthContext::login() so it is
 * available to the whole framework during the request.
 *
 * Authentication is NEVER modified by this context. When an account has no
 * mapping, resolution fails, or the user cannot be authenticated, the context
 * simply returns null and the login session is left untouched.
 */
class CurrentIdentityContext
{
    private const CACHE_PREFIX = 'current_identity_context:';

    /** @var array<string, array<string, mixed>|null> */
    private static $requestCache = [];

    /**
     * Resolve (or return the cached) Current Identity for the active project.
     *
     * @param int|null $projectId Explicit project id; falls back to the active project.
     * @param int|null $userId Explicit authenticated user id; falls back to the
     *   authenticated workspace user. Passing a user id bypasses the extra user
     *   lookup (used by the login warm-up).
     * @return array{
     *   user_id: int,
     *   identity_table: string,
     *   identity_record_id: string,
     *   record: array<string, mixed>,
     * }|null
     */
    public function get(?int $projectId = null, ?int $userId = null): ?array
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . $projectId;
        if (array_key_exists($cacheKey, self::$requestCache)) {
            return self::$requestCache[$cacheKey];
        }

        $identity = $this->build($projectId, $userId);
        self::$requestCache[$cacheKey] = $identity;
        return $identity;
    }

    public function getCurrentIdentity(?int $projectId = null, ?int $userId = null): ?array
    {
        return $this->get($projectId, $userId);
    }

    /**
     * Explicitly build and cache the Current Identity. Best-effort: never
     * throws and never affects authentication status.
     */
    public function build(?int $projectId = null, ?int $userId = null): ?array
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return null;
        }

        try {
            $resolved = (new UserMappingService())->resolveCurrentIdentity($projectId, $userId);
        } catch (\Throwable $e) {
            Yii::warning('Current identity resolution failed: ' . $e->getMessage(), 'current-identity');
            return null;
        }

        if (!is_array($resolved)) {
            return null;
        }

        return [
            'user_id' => (int)$resolved['user_id'],
            'identity_table' => (string)$resolved['table_name'],
            'identity_record_id' => (string)$resolved['identity_record_id'],
            'record' => $resolved['record'] ?? [],
        ];
    }

    /**
     * Debug/validation helper: resolves Current Identity through the exact same
     * path as get() (single query, single resolver) and returns a structured
     * diagnostic with a human-readable reason for every outcome. Never throws.
     *
     * The panel that renders this output must NOT resolve relationships itself;
     * it only reads the result of this method.
     *
     * @param int|null $projectId Explicit project id; falls back to the active project.
     * @param int|null $userId Explicit authenticated user id; falls back to the
     *   authenticated workspace user.
     * @return array{
     *   project_id: ?int,
     *   status: string,
     *   reason: string,
     *   config: array<string, mixed>,
     *   identity: array<string, mixed>|null,
     * }
     */
    public function diagnose(?int $projectId = null, ?int $userId = null): array
    {
        $projectId = $this->resolveProjectId($projectId);

        $result = [
            'project_id' => $projectId,
            'status' => 'no_project',
            'reason' => 'Tidak ada project aktif.',
            'config' => [],
            'identity' => null,
        ];

        if ($projectId === null) {
            return $result;
        }

        $cacheKey = self::CACHE_PREFIX . $projectId;
        $cached = self::$requestCache[$cacheKey] ?? null;

        // Reuse the request cache when the identity was already resolved so the
        // debug panel never triggers a duplicate query.
        if (is_array($cached)) {
            return [
                'project_id' => $projectId,
                'status' => 'resolved',
                'reason' => 'Current Identity diambil dari cache request (resolve sebelumnya).',
                'config' => [],
                'identity' => $cached,
            ];
        }

        $diagnostic = [];
        try {
            $identity = (new UserMappingService())->resolveCurrentIdentity($projectId, $userId, $diagnostic);
            self::$requestCache[$cacheKey] = $identity;
        } catch (\Throwable $e) {
            Yii::warning('Current Identity diagnosis failed: ' . $e->getMessage(), 'current-identity');
            $diagnostic['status'] = 'error';
            $diagnostic['reason'] = 'Diagnosis gagal: ' . $e->getMessage();
            $identity = null;
        }

        $result = [
            'project_id' => $projectId,
            'status' => (string)($diagnostic['status'] ?? ($identity !== null ? 'resolved' : 'unknown')),
            'reason' => (string)($diagnostic['reason'] ?? ($identity !== null ? 'Current Identity berhasil di-resolve.' : 'Current Identity tidak dapat di-resolve.')),
            'config' => is_array($diagnostic['mapping'] ?? null) ? $diagnostic['mapping'] : [],
            'identity' => $identity,
        ];

        return $result;
    }

    public function getUserId(?int $projectId = null): ?int
    {
        $identity = $this->get($projectId);
        return $identity !== null ? (int)$identity['user_id'] : null;
    }

    public function getIdentityTable(?int $projectId = null): ?string
    {
        $identity = $this->get($projectId);
        return $identity !== null ? (string)$identity['identity_table'] : null;
    }

    /**
     * @return int|string|null
     */
    public function getIdentityRecordId(?int $projectId = null)
    {
        $identity = $this->get($projectId);
        return $identity !== null ? $identity['identity_record_id'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRecord(?int $projectId = null): ?array
    {
        $identity = $this->get($projectId);
        return $identity !== null ? $identity['record'] : null;
    }

    public function isResolved(?int $projectId = null): bool
    {
        return $this->get($projectId) !== null;
    }

    /**
     * Clear the request cache. Used on logout and in console/tests between
     * scenarios so a stale identity is never reused.
     */
    public function reset(): void
    {
        self::$requestCache = [];
    }

    private function resolveProjectId(?int $projectId = null): ?int
    {
        if ($projectId !== null && $projectId > 0) {
            return $projectId;
        }

        if (!class_exists(ActiveProjectContext::class) || !class_exists(ProjectSchema::class) || !ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        return $activeProjectId !== null && $activeProjectId > 0 ? $activeProjectId : null;
    }
}
