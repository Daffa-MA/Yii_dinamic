<?php

namespace app\components;

use Yii;

/**
 * AutoFillRuntime - the single framework mechanism that derives a field value
 * automatically from the current runtime context right before a record is
 * persisted.
 *
 * This is a framework core service, NOT a Form feature, NOT a PKL module and
 * NOT tied to any specific module. Every component that later saves data (Dynamic
 * Form, Workflow, Approval, Import, API, Automation, Background Job) asks THIS
 * runtime for an auto-filled value; components never read the session and never
 * resolve the value themselves.
 *
 * Responsibilities:
 *   1. read the runtime context (Current Identity, Current User, Current Time...)
 *   2. decide whether a field requests auto fill
 *   3. produce the value
 *   4. hand the value back to the storage pipeline (the caller fills its payload)
 *
 * The runtime is metadata-driven and module-agnostic: it never hardcodes a table
 * name, column name, role or module. A field opts in through its `auto_fill`
 * property (a source code string, e.g. 'current_identity'); when the field value
 * is already provided the runtime does NOT overwrite it.
 *
 * Sources are advertised through {@see sources()} for builder UI in friendly,
 * non-technical terms. Only the minimal set required by the framework is
 * implemented; the resolution switch is the single place to extend it later
 * (UUID, random token, environment, workspace...).
 *
 * Fail-safe contract (consistent with the framework):
 *   - the runtime never throws and never fills a wrong value
 *   - no identity resolve -> the value is skipped (nothing is written)
 *   - unknown / empty source -> the field is left untouched, a clear log is kept
 *   - it only fills EMPTY fields, so it can never clobber user input
 *
 * Performance: all reads reuse the request-scoped CurrentIdentityContext (which
 * is cached O(1) per request); no extra query, no duplicate resolver, no object
 * churn. @see CurrentIdentityContext
 */
class AutoFillRuntime
{
    // Source codes (single source of truth). Add new codes only inside the
    // resolve() switch and sources() so the whole framework stays consistent.
    public const SOURCE_NONE = 'none';
    public const SOURCE_CURRENT_IDENTITY = 'current_identity';
    public const SOURCE_CURRENT_USER = 'current_user';
    public const SOURCE_CURRENT_DATE = 'current_date';
    public const SOURCE_CURRENT_TIME = 'current_time';
    public const SOURCE_CURRENT_TIMESTAMP = 'current_timestamp';

    /**
     * Advertise the available sources in friendly, non-technical terms so the
     * builder UI can render a simple dropdown.
     *
     * @return array<string, array{code: string, label: string, description: string}>
     */
    public function sources(): array
    {
        return [
            self::SOURCE_NONE => [
                'code' => self::SOURCE_NONE,
                'label' => 'Tidak digunakan',
                'description' => 'Nilai diisi manual oleh pengguna.',
            ],
            self::SOURCE_CURRENT_IDENTITY => [
                'code' => self::SOURCE_CURRENT_IDENTITY,
                'label' => 'Current Identity',
                'description' => 'Diisi otomatis dari identitas pengguna yang sedang masuk.',
            ],
            self::SOURCE_CURRENT_USER => [
                'code' => self::SOURCE_CURRENT_USER,
                'label' => 'Current User',
                'description' => 'Diisi otomatis dari akun pengguna yang sedang masuk.',
            ],
            self::SOURCE_CURRENT_DATE => [
                'code' => self::SOURCE_CURRENT_DATE,
                'label' => 'Current Date',
                'description' => 'Diisi otomatis dari tanggal hari ini.',
            ],
            self::SOURCE_CURRENT_TIME => [
                'code' => self::SOURCE_CURRENT_TIME,
                'label' => 'Current Time',
                'description' => 'Diisi otomatis dari waktu saat ini.',
            ],
            self::SOURCE_CURRENT_TIMESTAMP => [
                'code' => self::SOURCE_CURRENT_TIMESTAMP,
                'label' => 'Current Timestamp',
                'description' => 'Diisi otomatis dari tanggal dan waktu saat ini.',
            ],
        ];
    }

    /**
     * Apply auto fill to a pending payload.
     *
     * Iterates the same field definitions that the storage pipeline already has
     * in scope. For every field that (a) declares an `auto_fill` source and
     * (b) currently has no value, resolves the value and fills it.
     *
     * Never throws. On an unresolvable source the offending field is logged and
     * left untouched - no wrong value, no random value, no broken save.
     *
     * @param array<string, mixed> $data   flat payload keyed by resolved column name
     * @param array<int, array<string, mixed>> $fields field definitions (already normalized)
     * @param int|null $projectId active project id (falls back to session context)
     * @return array<string, mixed> the (possibly augmented) payload
     */
    public function apply(array $data, array $fields, ?int $projectId = null): array
    {
        $projectId = $this->resolveProjectId($projectId);
        if ($projectId === null) {
            return $data;
        }

        $context = $this->runtimeContext($projectId);

        foreach ($fields as $field) {
            if (!is_array($field) || empty($field['auto_fill'])) {
                continue;
            }

            $column = $this->targetColumn($field);
            if ($column === '' || $this->hasValue($data, $column)) {
                continue;
            }

            $value = $this->resolveValue((string) $field['auto_fill'], $context);
            if ($value === null) {
                continue;
            }

            $data[$column] = $this->castForColumn($value, $field);
        }

        return $data;
    }

    /**
     * Builder helper: human-readable label for a source code (or empty when the
     * code is not recognized).
     */
    public function sourceLabel(string $code): string
    {
        $sources = $this->sources();
        return ($sources[$code]['label'] ?? '') !== '' ? $sources[$code]['label'] : 'Tidak digunakan';
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve a single source to a concrete value from the runtime context.
     *
     * This switch is the ONLY place that knows each source. Extend it here when a
     * new source is needed; the rest of the framework needs no change.
     *
     * @return mixed null when the source cannot produce a value (fail-safe).
     */
    private function resolveValue(string $source, array $context)
    {
        switch ($source) {
            case self::SOURCE_CURRENT_IDENTITY:
                $recordId = $context['identity_record_id'] ?? null;
                if ($recordId === null || $recordId === '') {
                    $this->logSkipped($source, 'Current Identity tidak ter-resolve untuk pengguna ini.');
                    return null;
                }
                return $recordId;

            case self::SOURCE_CURRENT_USER:
                $userId = $context['user_id'] ?? null;
                if ($userId === null) {
                    $this->logSkipped($source, 'Current User tidak tersedia.');
                    return null;
                }
                return $userId;

            case self::SOURCE_CURRENT_DATE:
                return date('Y-m-d');

            case self::SOURCE_CURRENT_TIME:
                return date('H:i:s');

            case self::SOURCE_CURRENT_TIMESTAMP:
                return date('Y-m-d H:i:s');

            default:
                Yii::warning('AutoFillRuntime: source tidak dikenal `' . $source . '`; field dilewati.', 'autofill');
                return null;
        }
    }

    // -------------------------------------------------------------------------
    // Runtime context (single cached fetch)
    // -------------------------------------------------------------------------

    /**
     * Build (and cache per request) the runtime context the sources need. Reuses
     * the request-scoped CurrentIdentityContext (already cached, O(1)); no
     * duplicate resolver, no duplicate query.
     *
     * @return array{user_id: int|null, identity_table: string|null, identity_record_id: string|null, role: string|null}
     */
    private function runtimeContext(int $projectId): array
    {
        $identity = [];
        if (Yii::$app->has('currentIdentity')) {
            $resolved = Yii::$app->currentIdentity->get($projectId);
            if (is_array($resolved)) {
                $identity = $resolved;
            }
        }

        return [
            'user_id' => isset($identity['user_id']) ? (string) $identity['user_id'] : null,
            'identity_table' => isset($identity['identity_table']) ? (string) $identity['identity_table'] : null,
            'identity_record_id' => isset($identity['identity_record_id']) ? (string) $identity['identity_record_id'] : null,
            'role' => isset($identity['role']) ? (string) $identity['role'] : null,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveProjectId(?int $projectId): ?int
    {
        if ($projectId !== null && $projectId > 0) {
            return (int) $projectId;
        }

        if (!class_exists(ProjectSchema::class) || !ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        return $activeProjectId !== null && $activeProjectId > 0 ? (int) $activeProjectId : null;
    }

    /**
     * Resolve the target column name out of a normalized field definition.
     */
    private function targetColumn(array $field): string
    {
        foreach (['resolved_name', 'resolved_column_name', 'column_name', 'name', 'field_name', 'field_key'] as $key) {
            if (isset($field[$key]) && is_string($field[$key]) && trim($field[$key]) !== '') {
                return trim((string) $field[$key]);
            }
        }
        return '';
    }

    private function hasValue(array $data, string $column): bool
    {
        if (!array_key_exists($column, $data)) {
            return false;
        }
        $value = $data[$column];
        // 0 is a legitimate value (e.g. an id); NULL and '' mean "not filled".
        return $value !== null && $value !== '';
    }

    /**
     * Coerce a resolved value to a reasonable scalar for the target column.
     * Numeric-looking strings (e.g. identity ids, user ids) become ints so the
     * insert into an INT column stays clean; everything else stays as-is.
     *
     * @param mixed $value
     * @return mixed
     */
    private function castForColumn($value, array $field): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $isNumeric = ctype_digit(ltrim($value, '+-'));
        $columnName = strtolower((string) ($field['resolved_name'] ?? $field['column_name'] ?? $field['name'] ?? ''));
        $columnType = strtolower((string) ($field['source_column_db_type'] ?? $field['source_column_type'] ?? $field['field_type'] ?? ''));

        $looksLikeFk = str_ends_with($columnName, '_id') || str_ends_with($columnName, '_by');
        $looksLikeIntColumn = strpos($columnType, 'int') !== false
            || (preg_match('/^(big|small|medium|tiny)int/i', $columnType) === 1);

        if ($isNumeric && ($looksLikeFk || $looksLikeIntColumn) && (int) $value < PHP_INT_MAX) {
            return (int) $value;
        }

        return $value;
    }

    private function logSkipped(string $source, string $reason): void
    {
        Yii::warning('AutoFillRuntime [' . $source . '] dilewati: ' . $reason, 'autofill');
    }
}