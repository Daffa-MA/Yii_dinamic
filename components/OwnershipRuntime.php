<?php

namespace app\components;

use app\models\DbTable;
use app\models\DbTableColumn;
use Yii;
use yii\db\Query;
use yii\db\TableSchema;

/**
 * OwnershipRuntime - the single framework mechanism that derives the ownership
 * constraint for a business table from the logged-in user's Current Identity.
 *
 * Every framework component (DataTable, Form, Card, Chart, Calendar, Timeline,
 * Export, API, Workflow, Dashboard) asks THIS component for the constraint and
 * applies it - components never resolve ownership themselves and never know how
 * it is built. The runtime is the only place that understands:
 *
 *   - Current Identity        (read through Yii::$app->currentIdentity)
 *   - Relationship metadata   (FK graph from db_table_columns)
 *   - Ownership resolution    (child->parent path to the identity table)
 *   - Ownership constraint    (a where-part applied via andWhere)
 *
 * The runtime is metadata-driven and module-agnostic. It never hardcodes a
 * table name, column name, module or role, and never reads the session or issues
 * an extra `users` query. A multi-hop path is expressed as a nested IN-subquery
 * chain so the constraint composes via andWhere() without colliding with the
 * caller's own joins, search, filter or pagination.
 *
 * Fail-safe contract (consistently fail-closed):
 *   - no active project                        -> status "no_project" (not applied)
 *   - no Current Identity (admin/commander)    -> status "skip"       (not applied)
 *   - identity + relationship found            -> status "owned"      (constraint applied)
 *   - identity but relationship missing/broken -> status "deny"       (1=0, fail CLOSED)
 *
 * Every combination (missing relationship, corrupt metadata, missing identity,
 * invalid mapping, dropped table, changed FK) either yields a safe "deny" or a
 * "skip"/"no_project" that scopes nothing away - never a data leak, never a page
 * error, never a malformed query. See resolveConstraint().
 *
 * Everything is cached per project for the duration of the request (graph is
 * loaded ONCE; each table's constraint resolved ONCE); no N+1, no repeated
 * metadata traversal, nothing reused across requests.
 */
class OwnershipRuntime
{
    private const CACHE_PREFIX = 'ownership:';

    /** Max relationship hops from a source table back to the identity table. */
    private const MAX_PATH_LENGTH = 4;

    public const STATUS_NO_PROJECT = 'no_project';
    public const STATUS_SKIP = 'skip';
    public const STATUS_OWNED = 'owned';
    public const STATUS_DENY = 'deny';

    /** @var array<string, array{children: array<string, array<int, array{parent: string, fk: string, parent_ref: string}>>, pks: array<string, string>}> */
    private static $graphCache = [];

    /** @var array<string, array<string, mixed>> */
    private static $constraintCache = [];

    /**
     * Apply the ownership constraint for `$sourceTable` onto `$query`.
     *
     * The caller stays a security of the where-clause; it only knows that the
     * framework said either "apply this" or "there is nothing to apply". It never
     * learns how the constraint was built.
     *
     * @return bool true when ownership is active for this user (a constraint was
     *   appended, including the fail-closed deny); false when ownership does not
     *   apply (no identity / no project). When true the caller MUST NOT fall back
     *   to showing the whole table.
     */
    public function applyToQuery(\yii\db\Query $query, string $sourceTable, ?int $projectId = null): bool
    {
        $result = $this->resolveConstraint($sourceTable, $projectId);

        if (!in_array($result['status'], [self::STATUS_OWNED, self::STATUS_DENY], true)) {
            return false;
        }

        $query->andWhere($result['condition']);

        if ($result['status'] === self::STATUS_DENY) {
            Yii::warning($this->summarizeDeny($result), 'ownership-runtime');
        }

        return true;
    }

    /**
     * Resolve (and cache) the ownership constraint for a source table.
     *
     * Consistent, never-null result shape:
     *   status:     no_project|skip|owned|deny
     *   condition:  where-part when owned/deny, else null
     *   has_identity: bool
     *   source_table, identity_table, identity_record_id
     *   reason:     stable developer-readable reason (safe, no PII)
     *   relationship: <int|string> the resolved path as a compact chain description,
     *                 null when no identity or no path was used.
     *
     * @return array<string, mixed>
     */
    public function resolveConstraint(string $sourceTable, ?int $projectId = null): array
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return $this->result(self::STATUS_NO_PROJECT, $sourceTable, false, null, null, null, null);
        }

        $sourceTable = strtolower(trim($sourceTable));
        if ($sourceTable === '') {
            return $this->result(self::STATUS_NO_PROJECT, '', false, null, null, null, null);
        }

        $cacheKey = self::buildCacheKey($projectId, $sourceTable);
        if (isset(self::$constraintCache[$cacheKey])) {
            return self::$constraintCache[$cacheKey];
        }

        // Current Identity is the single source of truth - never resolve the user again.
        $identity = null;
        if (Yii::$app->has('currentIdentity')) {
            $identity = Yii::$app->currentIdentity->get($projectId);
        }

        $identityTable = is_array($identity) ? strtolower(trim((string)($identity['identity_table'] ?? ''))) : '';
        $identityRecordId = is_array($identity) ? trim((string) ($identity['identity_record_id'] ?? '')) : '';
        $hasIdentity = $identityTable !== '' && $identityRecordId !== '';

        $result = $this->buildConstraint($sourceTable, $identityTable, $identityRecordId, $hasIdentity, $projectId);
        $result['source_table'] = $sourceTable;
        $result['project_id'] = $projectId;
        self::$constraintCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Developer diagnostics. Returns a payload that explains WHY ownership
     * applied/skipped/denied: identity state, identity table, source table,
     * relationship path, and the resulting condition.
     *
     * The method is inert by design: it only re-reads the already-cached
     * resolution (never re-queries, never changes the query) and does not run on
     * the normal data path.
     *
     * Detail is emitted only when the request is allowed to see it
     * (YII_DEBUG or dev environment, or a framework administrator). For everyone
     * else a safe, technical-free summary is returned.
     *
     * @return array<string, mixed>
     */
    public function diagnose(string $sourceTable, ?int $projectId = null): array
    {
        $result = $this->resolveConstraint($sourceTable, $projectId);
        $allowed = $this->canShowDiagnostics();

        $payload = [
            'source_table' => (string) $result['source_table'],
            'status' => (string) $result['status'],
        ];

        if (!$allowed) {
            $payload['status'] = 'restricted';
            $payload['reason'] = $this->reasonFor($result['status']);
            return $payload;
        }

        $payload['project_id'] = $result['project_id'] ?? null;
        $payload['reason'] = $this->reasonFor($result['status']);
        $payload['has_identity'] = (bool) $result['has_identity'];
        $payload['identity_table'] = $result['identity_table'] ?? null;
        $payload['identity_record_id'] = $result['identity_record_id'] ?? null;
        $payload['relationship'] = $result['relationship'] ?? null;

        if ($result['status'] === self::STATUS_OWNED) {
            $payload['relationship'] = $result['relationship'] ?? null;
            $payload['condition'] = $result['condition'] ?? null;
        }

        return $payload;
    }

    /**
     * Whether the current request may surface detailed ownership diagnostics.
     * True only on debug mode, a dev/nonproduction environment, or a framework
     * administrator/framework - never for ordinary end users. Components that
     * render ownership debug UI must gate on this before calling diagnose().
     */
    public function canShowDiagnostics(): bool
    {
        if (defined('YII_DEBUG') && YII_DEBUG === true) {
            return true;
        }
        $env = strtolower((string) getenv('YII_ENV'));
        if (in_array($env, ['dev', 'development'], true)) {
            return true;
        }
        if (class_exists(CommanderAuthContext::class)) {
            try {
                if ((new CommanderAuthContext())->isSuperAdmin()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // inauthenticated console; treat as non-diagnostic.
            }
        }

        return false;
    }

    /** Clear the request-scoped caches (tests/console/logout). */
    public function reset(): void
    {
        self::$graphCache = [];
        self::$constraintCache = [];
    }

    public function getActiveProjectId(): ?int
    {
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        return $activeProjectId !== null && $activeProjectId > 0 ? (int) $activeProjectId : null;
    }

    // -------------------------------------------------------------------------
    // Resolution internals
    // -------------------------------------------------------------------------

    private function resolveProjectId(?int $projectId = null): ?int
    {
        if ($projectId !== null && $projectId > 0) {
            return $projectId;
        }
        return $this->getActiveProjectId();
    }

    private function result(string $status, string $sourceTable, bool $hasIdentity, ?string $identityTable, ?string $identityRecordId, $condition, $relationship): array
    {
        return [
            'status' => $status,
            'condition' => $condition,
            'source_table' => $sourceTable,
            'identity_table' => $identityTable,
            'identity_record_id' => $identityRecordId,
            'has_identity' => $hasIdentity,
            'relationship' => $relationship,
            'reason' => $this->reasonFor($status),
        ];
    }

    private function buildConstraint(string $sourceTable, string $identityTable, string $identityRecordId, bool $hasIdentity, ?int $projectId): array
    {
        // No domain identity (admin/commander/guest): nothing is scoped and
        // nothing is leaked - there is simply no identity to scope against.
        if (!$hasIdentity) {
            return $this->result(self::STATUS_SKIP, $sourceTable, false, null, null, null, null);
        }

        // Source IS the identity table: the current row only.
        if ($sourceTable === $identityTable) {
            $pk = $this->pkOf($identityTable, $projectId);
            if ($pk === null) {
                return $this->denied($sourceTable, true, $identityTable, $identityRecordId);
            }
            return $this->result(self::STATUS_OWNED, $sourceTable, true, $identityTable, $identityRecordId, [$pk => $identityRecordId], $identityTable . '.' . $pk);
        }

        $edges = $this->findPathToIdentity($sourceTable, $identityTable, $projectId);

        if ($edges === null || $edges === []) {
            return $this->denied($sourceTable, true, $identityTable, $identityRecordId);
        }

        $condition = $this->composePath($edges, $identityRecordId, $projectId);
        if ($condition === null) {
            return $this->denied($sourceTable, true, $identityTable, $identityRecordId);
        }

        return $this->result(self::STATUS_OWNED, $sourceTable, true, $identityTable, $identityRecordId, $condition, $this->describePath($edges));
    }

    private function denied(string $sourceTable, bool $hasIdentity, string $identityTable, string $identityRecordId): array
    {
        return $this->result(self::STATUS_DENY, $sourceTable, $hasIdentity, $identityTable, $identityRecordId, ['1' => '0'], null);
    }

    /**
     * Compose a WHERE condition for the source table from an ordered hop list
     * ($edges[0] ties to the source table; each later hop moves one table toward
     * the identity table). A single hop becomes a plain equality; deeper hops
     * become a nested IN-subquery chain, so no joins are introduced.
     *
     * Every table put into a subquery is validated against the physical schema
     * first (a dropped table or broken FK metadata returns null -> fail-closed),
     * so ownership can never emit a query against a missing table.
     *
     * @param array<int, array{child: string, parent: string, fk: string, parent_ref: string}> $edges
     */
    private function composePath(array $edges, string $identityRecordId, ?int $projectId): ?array
    {
        $count = count($edges);
        if ($count === 0) {
            return null;
        }

        $hop0 = $edges[0];

        // One hop: source.fk references the identity table directly.
        if ($count === 1) {
            if (!$this->columnExists($hop0['child'], $hop0['fk'], $projectId)) {
                return null;
            }
            return [$hop0['fk'] => $identityRecordId];
        }

        // Multi-hop: build the deepest child that references the identity, then
        // unwind from the source. Edges[k] = child T_k -> parent T_{k+1} via
        // column edges[k].fk on child T_k.
        $deepest = $edges[$count - 1];
        if ($this->tableMissing($deepest['child'], $projectId)) {
            return null;
        }

        $set = (new Query())
            ->select([$this->pkOf($deepest['child'], $projectId) ?? $deepest['parent_ref']])
            ->from($deepest['child'])
            ->where([$deepest['fk'] => $identityRecordId]);

        for ($i = $count - 2; $i >= 1; $i--) {
            $hop = $edges[$i];
            $fromTable = $hop['child'];
            if ($this->tableMissing($fromTable, $projectId)) {
                return null;
            }
            $set = (new Query())
                ->select([$this->pkOf($fromTable, $projectId) ?? $hop['parent_ref']])
                ->from($fromTable)
                ->where(['in', $hop['fk'], $set]);
        }

        if ($this->columnExists($hop0['child'], $hop0['fk'], $projectId) === false) {
            return null;
        }

        // Edge 0 belongs to the source table itself: source.fk0 IN (set of T1.pk).
        return ['in', $hop0['fk'], $set];
    }

    /**
     * Find a child->parent relationship path from $sourceTable to $target using
     * FK metadata (BFS, length-capped). Returns null when no path exists.
     *
     * @return array<int, array{child: string, parent: string, fk: string, parent_ref: string}>|null
     */
    private function findPathToIdentity(string $sourceTable, string $target, ?int $projectId): ?array
    {
        $graph = $this->graphFor($projectId);
        $children = $graph['children'] ?? [];

        if ($sourceTable === $target) {
            // handled by caller (identity-as-source); never reached, but safe.
            return [];
        }
        if (!isset($children[$sourceTable])) {
            return null;
        }

        $queue = [[$sourceTable, []]];
        $visited = [$sourceTable => true];

        while ($queue !== []) {
            $node = array_shift($queue);
            $table = $node[0];
            $path = $node[1];

            if (!isset($children[$table])) {
                continue;
            }

            foreach ($children[$table] as $hop) {
                $parent = $hop['parent'];
                $nextPath = $path;
                $nextPath[] = [
                    'child' => $table,
                    'parent' => $parent,
                    'fk' => $hop['fk'],
                    'parent_ref' => $hop['parent_ref'],
                ];

                if ($parent === $target) {
                    return $nextPath;
                }

                if (!isset($visited[$parent]) && count($nextPath) < self::MAX_PATH_LENGTH) {
                    $visited[$parent] = true;
                    $queue[] = [$parent, $nextPath];
                }
            }
        }

        return null;
    }

    /**
     * Load (and cache per project) the FK child->parent adjacency graph plus the
     * primary-key column per table, straight from the framework metadata. Built
     * ONCE per request; shared by every subsequent resolution in that request.
     *
     * @return array{children: array<string, array<int, array{parent: string, fk: string, parent_ref: string}>>, pks: array<string, string>}
     */
    private function graphFor(?int $projectId): array
    {
        $cacheKey = self::buildCacheKey($projectId, '__graph__');
        if (isset(self::$graphCache[$cacheKey])) {
            return self::$graphCache[$cacheKey];
        }

        $this->ensureDatabaseContext();

        $graph = ['children' => [], 'pks' => []];
        self::$graphCache[$cacheKey] = $graph;

        try {
            $query = DbTableColumn::find()
                ->joinWith('table')
                ->where([DbTableColumn::tableName() . '.is_foreign_key' => 1])
                ->andWhere(['not', [DbTableColumn::tableName() . '.referenced_table_name' => null]]);

            if ($projectId !== null && ProjectSchema::supportsProjectContext()) {
                $query->andWhere([DbTable::tableName() . '.project_id' => $projectId]);
            }

            $rows = $query
                ->select([
                    DbTable::tableName() . '.name AS table_name',
                    DbTableColumn::tableName() . '.name AS column_name',
                    DbTableColumn::tableName() . '.referenced_table_name',
                    DbTableColumn::tableName() . '.referenced_column_name',
                ])
                ->asArray()
                ->all();
        } catch (\Throwable $e) {
            Yii::warning('Ownership metadata load failed: ' . $e->getMessage(), 'ownership-runtime');
            $rows = [];
        }

        foreach ($rows as $row) {
            $child = strtolower(trim((string) ($row['table_name'] ?? '')));
            $refTable = strtolower(trim((string) ($row['referenced_table_name'] ?? '')));
            $fk = strtolower(trim((string) ($row['column_name'] ?? '')));
            $parentRef = strtolower(trim((string) ($row['referenced_column_name'] ?? ''))) ?: 'id';

            if ($child === '' || $refTable === '' || $fk === '') {
                continue;
            }
            if (DbTable::isSystemTable($child) || DbTable::isSystemTable($refTable)) {
                continue;
            }
            if ($child === $refTable) {
                continue;
            }

            $graph['children'][$child][] = [
                'parent' => $refTable,
                'fk' => $fk,
                'parent_ref' => $parentRef,
            ];
            $graph['pks'][$refTable] = $this->pkOf($refTable, $projectId) ?? 'id';
            $graph['pks'][$child] = $this->pkOf($child, $projectId) ?? 'id';
        }

        self::$graphCache[$cacheKey] = $graph;

        return $graph;
    }

    /**
     * Primary-key column of a physical table, cached per request.
     */
    private function pkOf(string $tableName, ?int $projectId): ?string
    {
        $cacheKey = self::buildCacheKey($projectId, '__graph__');
        if (!isset(self::$graphCache[$cacheKey])) {
            self::$graphCache[$cacheKey] = ['children' => [], 'pks' => []];
        }
        if (isset(self::$graphCache[$cacheKey]['pks'][$tableName])) {
            return self::$graphCache[$cacheKey]['pks'][$tableName];
        }

        $schema = $this->tableSchema($tableName);
        if (!$schema instanceof TableSchema) {
            self::$graphCache[$cacheKey]['pks'][$tableName] = 'id';
            return 'id';
        }

        $pk = $schema->primaryKey;
        if (is_array($pk) && $pk !== []) {
            $pk = $pk[0];
        }
        if (!is_string($pk) || $pk === '') {
            $pk = 'id';
        }
        if (!isset($schema->columns[$pk])) {
            $pk = array_key_first($schema->columns) ?? 'id';
        }

        $pk = strtolower($pk);
        self::$graphCache[$cacheKey]['pks'][$tableName] = $pk;

        return $pk;
    }

    private function tableSchema(string $tableName): ?TableSchema
    {
        if (!isset(Yii::$app) || !Yii::$app->has('db')) {
            return null;
        }
        try {
            return Yii::$app->db->schema->getTableSchema($tableName, true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function tableMissing(string $tableName, ?int $projectId): bool
    {
        return !$this->metadataKnowsTable($tableName, $projectId);
    }

    private function columnExists(string $tableName, string $columnName, ?int $projectId): bool
    {
        if (!$this->metadataHasColumn($tableName, $columnName, $projectId)) {
            return false;
        }
        $schema = $this->tableSchema($tableName);
        if ($schema === null) {
            return true;
        }
        return isset($schema->columns[$columnName]);
    }

    private function metadataHasColumn(string $tableName, string $columnName, ?int $projectId): bool
    {
        $graph = $this->graphFor($projectId);
        foreach ($graph['children'][$tableName] ?? [] as $hop) {
            if (($hop['fk'] ?? '') === $columnName) {
                return true;
            }
        }
        return false;
    }

    private function metadataKnowsTable(string $tableName, ?int $projectId): bool
    {
        $graph = $this->graphFor($projectId);
        if (isset($graph['children'][$tableName])) {
            return true;
        }
        if (isset($graph['pks'][$tableName])) {
            return true;
        }
        foreach ($graph['children'] as $hops) {
            foreach ($hops as $hop) {
                if (($hop['parent'] ?? '') === $tableName) {
                    return true;
                }
            }
        }
        return false;
    }

    private function ensureDatabaseContext(): void
    {
        if (!Yii::$app->has('db')) {
            return;
        }
        (new ActiveDatabaseContext())->resolveAndApply();
    }

    // -------------------------------------------------------------------------
    // Small helpers
    // -------------------------------------------------------------------------

    private static function buildCacheKey(int $projectId, string $key): string
    {
        return self::CACHE_PREFIX . $projectId . ':' . $key;
    }

    private function summarizeDeny(array $result): string
    {
        $sb = 'Ownership [deny] source=' . $result['source_table'];
        if (!empty($result['identity_table'])) {
            $sb .= ' identity=' . $result['identity_table'] . '#' . $result['identity_record_id'];
        }
        $sb .= ' | ' . $this->reasonFor($result['status']);
        return $sb;
    }

    private function reasonFor(string $status): string
    {
        switch ($status) {
            case self::STATUS_NO_PROJECT:
                return 'Tidak ada project aktif untuk meresolve ownership.';
            case self::STATUS_SKIP:
                return 'Tidak ada Current Identity untuk user ini; ownership tidak diterapkan.';
            case self::STATUS_DENY:
                return 'Akses ditolak (fail-closed): tidak ada relasi ke data, tidak ada data yang ditampilkan.';
            case self::STATUS_OWNED:
                return 'Ownership diterapkan: hanya data milik Current Identity yang ditampilkan.';
            default:
                return 'Status ownership tidak dikenal.';
        }
    }

    private function describePath(array $edges): string
    {
        $parts = [];
        foreach ($edges as $i => $edge) {
            $arrow = $i === 0 ? $edge['child'] . '.' . $edge['fk'] . ' -> ' . $edge['parent'] : ' -> ' . $edge['parent'];
            $parts[] = $arrow;
        }
        return implode(' ', $parts);
    }
}