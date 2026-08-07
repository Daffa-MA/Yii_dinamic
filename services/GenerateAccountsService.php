<?php

namespace app\services;

use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\DatabaseSchemaInitializer;
use app\components\ProjectSchema;
use app\models\DbTable;
use app\models\DbTableColumn;
use Yii;
use yii\db\Connection;
use yii\db\Query;

/**
 * GenerateAccountsService - Mass-create login accounts from a domain table
 * (siswa, guru, pegawai, customer, anggota, ...) without being hardcoded to any
 * module.
 *
 * Authentication stays on the framework `users` table. Each domain record keeps
 * its data on its own table; the link is a Foreign Key column `user_id` that
 * points to `users.id` (never `identity_record_id`, never a side mapping).
 *
 * Everything is metadata-driven:
 *  - Source tables come from metadata (non-system tables).
 *  - Username columns come from metadata column definitions.
 *  - Roles come from the values actually used at runtime (users.role).
 *
 * Generation uses keyset chunking (no full load, no N+1), one transaction per
 * batch, skips already-existing usernames and never stops on a single failure.
 */
class GenerateAccountsService
{
    public const USERS_TABLE = 'users';
    public const USER_ID_COLUMN = 'user_id';
    public const DEFAULT_CHUNK_SIZE = 500;

    /**
     * Fallback email domain when nothing else is configured. Prefers the app's own
     * domain (params rootDomain / projectDomainSuffix) so generated addresses look
     * real (e.g. `bud1@appforge.web.id`) instead of a placeholder `@local`.
     */
    public const DEFAULT_EMAIL_DOMAIN = 'example.com';

    /** Column types that are usable as a source for usernames (metadata read). */
    public const USERNAME_TYPE_POOL = [
        'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT',
        'ENUM', 'SET',
        'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT',
        'DECIMAL', 'FLOAT', 'DOUBLE', 'REAL', 'YEAR',
    ];

    /** @var int[] */
    private const EXCLUDED_COLUMNS = ['user_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Candidate domain tables for Generate Accounts: any non-system table known
     * to metadata, each with the physical FK column state and a record count.
     *
     * @return array<int, array{
     *   name: string,
     *   label: string,
     *   has_user_id: bool,
     *   total_records: int,
     * }>
     */
    public function getSourceTables(?int $projectId = null): array
    {
        $tables = TableService::getUserTables(null, $projectId);
        $result = [];

        foreach ($tables as $table) {
            if (!$table instanceof DbTable) {
                continue;
            }

            $name = strtolower(trim((string)$table->name));
            if ($name === '' || DbTable::isSystemTable($name)) {
                continue;
            }

            $result[] = [
                'name' => $name,
                'label' => (string)($table->label !== '' && $table->label !== null ? $table->label : $table->name),
                'has_user_id' => $this->hasUserIdColumn($name),
                'total_records' => $this->countTableRows($name),
            ];
        }

        return $result;
    }

    /**
     * Candidate username columns for a table, read from the LIVE physical table
     * schema (not metadata). Metadata can go stale (e.g. a column renamed from
     * `nama` to `nama_siswa`), so the real DB is the single source of truth and
     * the returned column names are guaranteed to exist — the wizard and the
     * preview/generate validation never collide.
     *
     * Metadata is only used to decorate the column label/sort; the column name
     * always comes from the actual table.
     *
     * @return array<int, array{name: string, label: string, type: string}>
     */
    public function getColumnsForTable(string $tableName): array
    {
        $tableName = strtolower(trim($tableName));
        if ($tableName === '') {
            return [];
        }

        $this->ensureDatabaseContext();
        $schema = Yii::$app->db->getTableSchema($tableName, true);
        if ($schema === null) {
            return [];
        }

        // Metadata column lookup, used only to decorate the label when the
        // physical name matches. Never decides the column name itself.
        $metaColumns = [];
        $dbTable = $this->findMetadataTable($tableName);
        if ($dbTable !== null) {
            foreach ($dbTable->getColumns()->all() as $column) {
                if (!$column instanceof DbTableColumn) {
                    continue;
                }
                $metaColumns[strtolower(trim((string)$column->name))] = $column;
            }
        }

        $primaryKeys = [];
        foreach ((array)$schema->primaryKey as $pk) {
            $primaryKeys[strtolower(trim((string)$pk))] = true;
        }

        $columns = [];
        foreach ($schema->columns as $columnName => $column) {
            $name = strtolower(trim((string)$columnName));
            if ($name === '' || in_array($name, self::EXCLUDED_COLUMNS, true)) {
                continue;
            }
            if (isset($primaryKeys[$name]) || !empty($column->autoIncrement)) {
                continue;
            }
            if (!$this->isUsernameCandidateType($column)) {
                continue;
            }

            $meta = $metaColumns[$name] ?? null;
            $metaLabel = $meta !== null ? trim((string)$meta->getAttribute('label')) : '';
            $columns[] = [
                'name' => $name,
                'label' => $metaLabel !== '' ? $metaLabel : $this->humanize($name),
                'type' => (string)($column->dbType !== null && trim((string)$column->dbType) !== '' ? $column->dbType : $column->type),
            ];
        }

        return array_values($columns);
    }

    /**
     * Whether a physical column is usable as a username source. Text and numeric
     * columns are accepted; structural/derived types (binary, blob, datetime,
     * json, etc.) are not.
     */
    private function isUsernameCandidateType($column): bool
    {
        $abstractType = strtolower((string)($column->type ?? ''));
        if (in_array($abstractType, ['string', 'integer'], true)) {
            return true;
        }

        $dbType = strtolower(trim((string)($column->dbType ?? '')));
        if ($dbType === '') {
            return false;
        }
        foreach (self::USERNAME_TYPE_POOL as $pool) {
            if (strpos($dbType, strtolower($pool)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Roles usable at runtime, read straight from the values present in
     * `users.role`. Commander-only roles (superadmin) are never offered.
     *
     * @return array<int, array{name: string, label: string}>
     */
    public function getRoles(): array
    {
        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        if ($db->getTableSchema(self::USERS_TABLE, true) === null) {
            return [['name' => 'admin', 'label' => 'Admin']];
        }

        $rows = (new Query())
            ->select(['role'])
            ->from(self::USERS_TABLE)
            ->where(['and', ['not', ['role' => null]], ['<>', 'role', '']])
            ->groupBy(['role'])
            ->orderBy(['role' => SORT_ASC])
            ->all($db);

        $roles = [];
        foreach ($rows as $row) {
            $role = strtolower(trim((string)($row['role'] ?? '')));
            if (!self::isValidRoleName($role)) {
                continue;
            }
            $roles[] = [
                'name' => $role,
                'label' => ucfirst(str_replace(['_', '-'], ' ', $role)),
            ];
        }

        if (empty($roles)) {
            $roles[] = ['name' => 'admin', 'label' => 'Admin'];
        }

        return $roles;
    }

    /**
     * Whether a string is a usable runtime role name. Only word-like
     * identifiers count: letters, digits, underscore and hyphen, starting
     * with a letter and containing at least one alphabetic character. This
     * rejects numeric garbage ("1") and column-style leftovers ("id_kelas")
     * that end up in `users.role` after a mis-mapped import.
     */
    public static function isValidRoleName(string $role): bool
    {
        $role = strtolower(trim($role));
        if ($role === '' || in_array($role, ['super_admin', 'superadmin'], true)) {
            return false;
        }
        if (!preg_match('/^[a-z][a-z0-9_-]*$/i', $role)) {
            return false;
        }
        if (!preg_match('/[a-z]/i', $role)) {
            return false;
        }
        if (strpos($role, 'id_') === 0) {
            return false;
        }
        return true;
    }

    /**
     * Dry-run preview: how many records exist, how many are already linked,
     * how many accounts would be created, and how many would be skipped.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   table: string,
     *   username_column: string,
     *   total: int,
     *   already_linked: int,
     *   eligible: int,
     *   distinct_usernames: int,
     *   username_exists: int,
     *   to_create: int,
     *   skipped: int,
     *   has_user_id: bool,
     * }
     */
    public function preview(string $tableName, string $usernameColumn): array
    {
        $tableName = strtolower(trim($tableName));
        $usernameColumn = strtolower(trim($usernameColumn));

        $validation = $this->validateInputs($tableName, $usernameColumn);
        if (!$validation['success']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'table' => $tableName,
                'username_column' => $usernameColumn,
                'total' => 0,
                'already_linked' => 0,
                'eligible' => 0,
                'distinct_usernames' => 0,
                'username_exists' => 0,
                'to_create' => 0,
                'skipped' => 0,
                'has_user_id' => false,
            ];
        }

        $this->ensureDatabaseContext();
        DatabaseSchemaInitializer::ensureDomainUserIdColumn(Yii::$app->db, $tableName);

        $db = Yii::$app->db;
        $schema = $db->getTableSchema($tableName, true);
        $hasUserId = isset($schema->columns[self::USER_ID_COLUMN]);

        $total = (int)(new Query())->from($tableName)->count('*', $db);

        $eligibleConditions = ['and'];
        if ($hasUserId) {
            $eligibleConditions[] = [self::USER_ID_COLUMN => null];
            $alreadyLinked = (int)(new Query())
                ->from($tableName)
                ->where(['not', [self::USER_ID_COLUMN => null]])
                ->count('*', $db);
        } else {
            $alreadyLinked = 0;
        }
        $eligibleConditions[] = ['not', [$usernameColumn => null]];
        $eligibleConditions[] = ['<>', $usernameColumn, ''];

        $eligible = (int)(new Query())
            ->from($tableName)
            ->where($eligibleConditions)
            ->count('*', $db);

        $distinctUsernames = (int)(new Query())
            ->select([new \yii\db\Expression('COUNT(DISTINCT ' . $db->quoteColumnName($usernameColumn) . ')')])
            ->from($tableName)
            ->where($eligibleConditions)
            ->scalar($db);

        $usernameExists = $this->countExistingUsernames($tableName, $usernameColumn, $eligibleConditions);

        $toCreate = max(0, $distinctUsernames - $usernameExists);
        $skipped = max(0, $total - $alreadyLinked - $toCreate);

        return [
            'success' => true,
            'message' => 'Preview siap.',
            'table' => $tableName,
            'username_column' => $usernameColumn,
            'total' => $total,
            'already_linked' => $alreadyLinked,
            'eligible' => $eligible,
            'distinct_usernames' => $distinctUsernames,
            'username_exists' => $usernameExists,
            'to_create' => $toCreate,
            'skipped' => $skipped,
            'has_user_id' => $hasUserId,
        ];
    }

    /**
     * Execute the mass account generation. One transaction per chunk, keyset
     * pagination (no full-table load, no N+1), usernames are checked against a
     * single pre-loaded set per chunk. Duplicate/conflicting records are skipped
     * and reported; a failing row never aborts the run.
     *
     * @param string $passwordMode 'fixed' or 'random'
     * @param string $emailDomain Email domain for generated accounts. Empty
     *        means "use the app's configured domain" (params rootDomain /
     *        projectDomainSuffix, else example.com). Never `@local`.
     * @return array{
     *   success: bool,
     *   message: string,
     *   table: string,
     *   username_column: string,
     *   role: string,
     *   created: int,
     *   skipped_existing: int,
     *   skipped_no_username: int,
     *   failed: int,
     *   examples: array<int, array{pk: int|string, user_id: int, username: string}>,
     * }
     */
    public function generate(string $tableName, string $usernameColumn, string $role, string $passwordMode = 'fixed', string $fixedPassword = '123456', string $emailDomain = ''): array
    {
        $tableName = strtolower(trim($tableName));
        $usernameColumn = strtolower(trim($usernameColumn));
        $role = strtolower(trim($role));
        $emailDomain = $this->normalizeEmailDomain($emailDomain);

        $validation = $this->validateInputs($tableName, $usernameColumn);
        if (!$validation['success']) {
            return $this->emptyGenerateResult($tableName, $usernameColumn, $role, $validation['message']);
        }

        if (!self::isValidRoleName($role)) {
            return $this->emptyGenerateResult($tableName, $usernameColumn, $role, 'Role tidak valid untuk akun yang dibuat.');
        }

        if ($passwordMode !== 'random') {
            $passwordMode = 'fixed';
        }
        if ($passwordMode === 'fixed' && trim($fixedPassword) === '') {
            return $this->emptyGenerateResult($tableName, $usernameColumn, $role, 'Password awal tidak boleh kosong.');
        }

        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        DatabaseSchemaInitializer::ensureProjectAuthColumns($db);
        DatabaseSchemaInitializer::ensureDomainUserIdColumn($db, $tableName);

        $schema = $db->getTableSchema($tableName, true);
        $pk = $this->resolvePkColumn($schema);
        if ($pk === null || !isset($schema->columns[self::USER_ID_COLUMN])) {
            return $this->emptyGenerateResult($tableName, $usernameColumn, $role, 'Kolom FK user_id tidak tersedia pada tabel ' . $tableName . '.');
        }

        $now = date('Y-m-d H:i:s');
        $usedUsernames = [];
        $stats = [
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_no_username' => 0,
            'failed' => 0,
            'examples' => [],
        ];

        // Some legacy/base `users` tables (e.g. the Yii2 basic template) ship an
        // `auth_key` column without a default. Supply a value only when present so
        // the direct INSERT never fails on it.
        $usersSchema = $db->getTableSchema(self::USERS_TABLE, true);
        $usersHaveAuthKey = $usersSchema !== null && isset($usersSchema->columns['auth_key']);

        $lastPk = null;
        do {
            $query = (new Query())
                ->select([$pk, $usernameColumn])
                ->from($tableName)
                ->where([self::USER_ID_COLUMN => null])
                ->andWhere(['not', [$usernameColumn => null]])
                ->andWhere(['<>', $usernameColumn, ''])
                ->orderBy([$pk => SORT_ASC])
                ->limit(self::DEFAULT_CHUNK_SIZE);
            if ($lastPk !== null) {
                $query->andWhere(['>', $pk, $lastPk]);
            }

            $rows = $query->all($db);
            if (empty($rows)) {
                break;
            }

            $existingUsernames = $this->loadExistingUsernames($rows, $pk, $usernameColumn, $db);

            $transaction = $db->beginTransaction();
            try {
                foreach ($rows as $row) {
                    $pkValue = $row[$pk] ?? null;
                    $username = strtolower(trim((string)($row[$usernameColumn] ?? '')));

                    if ($pkValue === null || $pkValue === '') {
                        $stats['skipped_no_username']++;
                        continue;
                    }
                    if ($username === '') {
                        $stats['skipped_no_username']++;
                        continue;
                    }
                    if (isset($usedUsernames[$username]) || isset($existingUsernames[$username])) {
                        $stats['skipped_existing']++;
                        continue;
                    }

                    $password = $passwordMode === 'random'
                        ? $this->randomPassword()
                        : $fixedPassword;
                    $passwordHash = Yii::$app->security->generatePasswordHash($password);

                    try {
                        $userData = [
                            'name' => $username,
                            'username' => $username,
                            'email' => $username . '@' . $emailDomain,
                            'password_hash' => $passwordHash,
                            'role' => $role,
                            'status' => 1,
                            'must_change_password' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                            // Persist the runtime Identity mapping directly on the
                            // account so Current Identity resolves in O(1) right away
                            // (this also marks the account as "Terhubung" on Users).
                            'identity_table' => $tableName,
                            'identity_record_id' => (string)$pkValue,
                        ];
                        if ($usersHaveAuthKey) {
                            $userData['auth_key'] = Yii::$app->security->generateRandomString();
                        }
                        $db->createCommand()->insert(self::USERS_TABLE, $userData)->execute();
                    } catch (\Throwable $e) {
                        Yii::warning('Generate Accounts: insert skipped for username ' . $username . ': ' . $e->getMessage(), 'generate-accounts');
                        $stats['skipped_existing']++;
                        $usedUsernames[$username] = true;
                        $existingUsernames[$username] = true;
                        continue;
                    }

                    $userId = (int)$db->getLastInsertID();
                    $db->createCommand()
                        ->update($tableName, [self::USER_ID_COLUMN => $userId], [$pk => $pkValue])
                        ->execute();

                    $usedUsernames[$username] = true;
                    $stats['created']++;
                    $stats['examples'][] = [
                        'pk' => $pkValue,
                        'user_id' => $userId,
                        'username' => $username,
                    ];
                    if (count($stats['examples']) > 10) {
                        array_shift($stats['examples']);
                    }
                }

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error('Generate Accounts: batch transaction failed: ' . $e->getMessage(), 'generate-accounts');
                return $this->emptyGenerateResult($tableName, $usernameColumn, $role, 'Batch transaksi gagal: ' . $e->getMessage());
            }

            $lastPk = $row[$pk] ?? null;
        } while ($lastPk !== null);

        try {
            if (Yii::$app->has('currentIdentity')) {
                Yii::$app->currentIdentity->reset();
            }
        } catch (\Throwable $e) {
            Yii::warning('Current Identity cache reset failed after Generate Accounts: ' . $e->getMessage(), 'current-identity');
        }

        $skipped = $stats['skipped_existing'] + $stats['skipped_no_username'] + $stats['failed'];

        return [
            'success' => true,
            'message' => 'Generate Accounts selesai.',
            'table' => $tableName,
            'username_column' => $usernameColumn,
            'role' => $role,
            'created' => $stats['created'],
            'skipped_existing' => $stats['skipped_existing'],
            'skipped_no_username' => $stats['skipped_no_username'],
            'failed' => $stats['failed'],
            'skipped' => $skipped,
            'examples' => $stats['examples'],
        ];
    }

    /**
     * Whether the physical table already has the user_id FK column.
     */
    public function hasUserIdColumn(string $tableName): bool
    {
        try {
            $this->ensureDatabaseContext();
            $schema = Yii::$app->db->getTableSchema($tableName, true);
            return $schema !== null && isset($schema->columns[self::USER_ID_COLUMN]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function validateInputs(string $tableName, string $usernameColumn): array
    {
        if ($tableName === '' || $usernameColumn === '') {
            return ['success' => false, 'message' => 'Tabel sumber dan kolom username wajib dipilih.'];
        }

        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        $schema = $db->getTableSchema($tableName, true);
        if ($schema === null) {
            return ['success' => false, 'message' => 'Tabel "' . $tableName . '" tidak tersedia pada database aplikasi.'];
        }
        if (DbTable::isSystemTable($tableName)) {
            return ['success' => false, 'message' => 'Tabel sistem tidak dapat digunakan sebagai sumber akun.'];
        }
        if (!isset($schema->columns[$usernameColumn])) {
            return ['success' => false, 'message' => 'Kolom "' . $usernameColumn . '" tidak ditemukan pada tabel "' . $tableName . '".'];
        }

        return ['success' => true, 'message' => 'OK'];
    }

    /**
     * Count how many distinct eligible usernames already exist in `users`.
     */
    private function countExistingUsernames(string $tableName, string $usernameColumn, array $eligibleConditions, ?Connection $db = null): int
    {
        $db = $db ?? Yii::$app->db;

        $subQuery = (new Query())
            ->select([$db->quoteColumnName($usernameColumn)])
            ->from($tableName)
            ->where($eligibleConditions)
            ->distinct();

        return (int)(new Query())
            ->from(self::USERS_TABLE)
            ->where(['in', 'username', $subQuery])
            ->count('DISTINCT username', $db);
    }

    /**
     * Pre-load usernames already present in `users` for the current batch, so
     * uniqueness checks never become N+1. Falls back to lowercased comparison.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, true>
     */
    private function loadExistingUsernames(array $rows, string $pk, string $usernameColumn, ?Connection $db = null): array
    {
        $db = $db ?? Yii::$app->db;
        $candidates = [];
        foreach ($rows as $row) {
            $username = strtolower(trim((string)($row[$usernameColumn] ?? '')));
            if ($username !== '') {
                $candidates[$username] = true;
            }
        }
        if (empty($candidates)) {
            return [];
        }

        $existingRows = (new Query())
            ->select(['username'])
            ->from(self::USERS_TABLE)
            ->where(['in', 'username', array_keys($candidates)])
            ->all($db);

        $existing = [];
        foreach ($existingRows as $row) {
            $existing[strtolower(trim((string)($row['username'] ?? '')))] = true;
        }

        return $existing;
    }

    private function randomPassword(int $length = 8): string
    {
        $length = max(6, min(24, $length));
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }
        return $password;
    }

    private function resolvePkColumn($schema): ?string
    {
        $primaryKey = $schema->primaryKey;
        if (is_string($primaryKey) && $primaryKey !== '') {
            return $primaryKey;
        }
        if (is_array($primaryKey) && $primaryKey !== []) {
            return (string)$primaryKey[0];
        }
        return null;
    }

    private function findMetadataTable(string $tableName): ?DbTable
    {
        return DbTable::find()->where(['name' => $tableName])->one();
    }

    private function countTableRows(string $tableName): int
    {
        try {
            $this->ensureDatabaseContext();
            return (int)(new Query())->from($tableName)->count('*', Yii::$app->db);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }

    private function ensureDatabaseContext(): void
    {
        (new ActiveDatabaseContext())->resolveAndApply();
        DatabaseSchemaInitializer::ensureProjectAuthColumns(Yii::$app->db);
    }

    public function getActiveProjectId(): ?int
    {
        if (!class_exists(ActiveProjectContext::class) || !ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        return $projectId !== null && $projectId > 0 ? $projectId : null;
    }

    /**
     * The email domain used for generated accounts. Empty input means "use the
     * app's own configured domain"; never `@local`. Used to pre-fill the wizard
     * and to build each generated account's address.
     */
    public function getEmailDomain(): string
    {
        return $this->normalizeEmailDomain('');
    }

    /**
     * Normalize/validate an email domain. Backticks, whitespace and protocol
     * remnants are stripped; when empty it falls back to the app's configured
     * domain (params). A leading dot is trimmed.
     */
    private function normalizeEmailDomain(string $domain): string
    {
        $domain = strtolower(trim((string)$domain));
        $domain = preg_replace('/^[a-z]+:\/\//i', '', (string)$domain) ?? $domain;
        $domain = ltrim($domain, '.');
        $domain = trim($domain, '/');
        $domain = preg_replace('/[^a-z0-9.-]/', '', (string)$domain) ?? $domain;
        $domain = trim($domain, '.');

        if ($domain !== '') {
            return $domain;
        }

        $params = Yii::$app->params;
        foreach (['generatedUserEmailDomain', 'projectDomainSuffix', 'rootDomain'] as $key) {
            if (isset($params[$key]) && is_string($params[$key])) {
                $candidate = $this->normalizeEmailDomain((string)$params[$key]);
                if ($candidate !== '' && strpos($candidate, '.') !== false) {
                    return $candidate;
                }
            }
        }

        return self::DEFAULT_EMAIL_DOMAIN;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyGenerateResult(string $tableName, string $usernameColumn, string $role, string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'table' => $tableName,
            'username_column' => $usernameColumn,
            'role' => $role,
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_no_username' => 0,
            'failed' => 0,
            'skipped' => 0,
            'examples' => [],
        ];
    }
}
