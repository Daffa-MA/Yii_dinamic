<?php

namespace app\services;

use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\DatabaseSchemaInitializer;
use app\components\ProjectAuthContext;
use app\components\ProjectSchema;
use app\components\RelationMapper;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\models\ProjectUser;
use Yii;
use yii\db\Query;

/**
 * UserMappingService - manages the direct link between a login account (a row
 * in the `users` table) and its domain record.
 *
 * Authentication (login, session, password, role, security) remains owned by
 * ProjectLoginForm / ProjectUser / ProjectAuthContext and is never touched here.
 *
 * Every account may carry at most one mapping, stored directly on the `users`
 * row as `identity_table` + `identity_record_id`. This makes Current Identity a
 * pure O(1) read: no scanning, no first-match, no looping over tables, and no
 * per-request metadata resolution. The mapping is created at account time
 * (User Management or mass import), not discovered at request time.
 *
 * Everything is metadata-driven: the list of possible entities comes from
 * metadata (tables that reference `users` via a FK column), never hardcoded.
 */
class UserMappingService
{
    public const USERS_TABLE = 'users';

    /**
     * Candidate entities a login account can be mapped to: any non-system
     * table that has at least one FK column in metadata pointing to `users`.
     *
     * @return array<int, array{
     *   name: string,
     *   label: string,
     *   relationships: array<int, array{
     *     relationship_id: int,
     *     table_name: string,
     *     fk_column: string,
     *     referenced_column: string,
     *   }>
     * }>
     */
    public function getEntities(?int $projectId = null): array
    {
        $projectId = $projectId ?? $this->getActiveProjectId();

        $query = DbTableColumn::find()
            ->joinWith('table')
            ->where([DbTableColumn::tableName() . '.is_foreign_key' => 1])
            ->andWhere('LOWER(' . DbTableColumn::tableName() . '.referenced_table_name) = :refTable', [':refTable' => self::USERS_TABLE])
            ->orderBy([
                DbTable::tableName() . '.label' => SORT_ASC,
                DbTableColumn::tableName() . '.name' => SORT_ASC,
            ]);

        foreach ($this->metadataScope($projectId) as $attribute => $value) {
            $query->andWhere([DbTable::tableName() . '.' . $attribute => $value]);
        }

        $entities = [];
        foreach ($query->all() as $column) {
            $table = $column->table;
            if (!$table instanceof DbTable) {
                continue;
            }

            $tableName = strtolower(trim((string)$table->name));
            if ($tableName === '' || DbTable::isSystemTable($tableName)) {
                continue;
            }

            $entities[$tableName]['name'] = $tableName;
            $entities[$tableName]['label'] = (string)($table->label !== '' && $table->label !== null ? $table->label : $table->name);
            $entities[$tableName]['relationships'][] = [
                'relationship_id' => (int)$column->id,
                'table_name' => $tableName,
                'fk_column' => strtolower(trim((string)$column->name)),
                'referenced_column' => strtolower(trim((string)$column->referenced_column_name)) ?: 'id',
            ];
        }

        return array_values($entities);
    }

    /**
     * Read the mapping stored directly on a `users` row. Single indexed PK
     * lookup - O(1), no joins, no scanning.
     *
     * @return array{identity_table: string, identity_record_id: string}|null
     */
    public function getMapping(?int $projectId, int $userId): ?array
    {
        if ($projectId === null || $userId <= 0) {
            return null;
        }

        $this->ensureDatabaseContext();

        $row = (new Query())
            ->select(['identity_table', 'identity_record_id'])
            ->from(self::USERS_TABLE)
            ->where(['id' => $userId])
            ->one(Yii::$app->db);

        if (!is_array($row)) {
            return null;
        }

        $identityTable = strtolower(trim((string)($row['identity_table'] ?? '')));
        $identityRecordId = trim((string)($row['identity_record_id'] ?? ''));
        if ($identityTable === '' || $identityRecordId === '') {
            return null;
        }

        return [
            'identity_table' => $identityTable,
            'identity_record_id' => $identityRecordId,
        ];
    }

    /**
     * Resolve the "Current Identity" of the authenticated workspace user from
     * its stored mapping. O(1): one PK lookup on `users`, then one PK lookup on
     * the mapped entity table. No scanning, no first-match.
     *
     * @param int|null $projectId Explicit project id; falls back to the active project.
     * @param int|null $userId Explicit authenticated user id; falls back to the
     *   authenticated workspace user.
     * @param array|null $diagnostic Diagnostic buffer filled by reference.
     * @return array{
     *   record: array<string, mixed>,
     *   table_name: string,
     *   identity_record_id: string,
     *   user_id: int,
     * }|null
     */
    public function resolveCurrentIdentity(?int $projectId = null, ?int $userId = null, ?array &$diagnostic = null): ?array
    {
        $diagnostic = is_array($diagnostic) ? $diagnostic : [];
        $diagnostic['status'] = 'unknown';

        $projectId = $projectId ?? $this->getActiveProjectId();
        if ($projectId === null) {
            $diagnostic['status'] = 'no_project';
            $diagnostic['reason'] = 'Tidak ada project aktif untuk meresolve Current Identity.';
            return null;
        }
        $diagnostic['project_id'] = $projectId;

        $this->ensureDatabaseContext();

        if ($userId === null) {
            $user = (new ProjectAuthContext())->getAuthenticatedUser($projectId);
            $userId = $user instanceof ProjectUser ? (int)$user->id : null;
        }
        if ($userId === null || $userId <= 0) {
            $diagnostic['status'] = 'not_authenticated';
            $diagnostic['reason'] = 'Tidak ada user workspace yang sedang login.';
            return null;
        }
        $diagnostic['user_id'] = $userId;

        $mapping = $this->getMapping($projectId, $userId);
        if ($mapping === null) {
            $diagnostic['status'] = 'not_mapped';
            $diagnostic['reason'] = 'Akun ini belum dihubungkan dengan data domainnya (User Mapping belum diatur).';
            return null;
        }
        $diagnostic['mapping'] = $mapping;

        $db = Yii::$app->db;
        $schema = $db->schema->getTableSchema($mapping['identity_table'], true);
        if ($schema === null) {
            $diagnostic['status'] = 'schema_missing';
            $diagnostic['reason'] = 'Tabel identity ' . $mapping['identity_table'] . ' tidak ditemukan pada database.';
            return null;
        }

        $primaryKey = $schema->primaryKey;
        if (is_string($primaryKey) && $primaryKey !== '') {
            $primaryKey = [$primaryKey];
        }
        $pkColumn = is_array($primaryKey) && $primaryKey !== [] ? $primaryKey[0] : 'id';
        if (!isset($schema->columns[$pkColumn])) {
            $diagnostic['status'] = 'schema_missing';
            $diagnostic['reason'] = 'Primary key ' . $pkColumn . ' tidak ditemukan pada tabel ' . $mapping['identity_table'] . '.';
            return null;
        }

        $record = (new Query())
            ->from($mapping['identity_table'])
            ->where([$pkColumn => $mapping['identity_record_id']])
            ->limit(1)
            ->one($db);

        if (!is_array($record)) {
            $diagnostic['status'] = 'record_not_found';
            $diagnostic['reason'] = 'Record data tidak ditemukan: ' . $mapping['identity_table'] . '.' . $pkColumn . ' = ' . $mapping['identity_record_id'] . '.';
            return null;
        }

        $diagnostic['status'] = 'resolved';
        $diagnostic['reason'] = 'Current Identity berhasil di-resolve dari mapping akun (tabel ' . $mapping['identity_table'] . ').';

        return [
            'record' => $record,
            'table_name' => $mapping['identity_table'],
            'identity_record_id' => $mapping['identity_record_id'],
            'user_id' => $userId,
        ];
    }

    /**
     * Persist the mapping for an account. Validates that the entity is a known
     * candidate (metadata-driven, FK to `users`) and that the record exists in
     * the physical database.
     *
     * @param int|null $projectId
     * @param int $userId
     * @param string $entityTable
     * @param string $recordId
     * @return array{success: bool, message: string}
     */
    public function saveMapping(?int $projectId, int $userId, string $entityTable, string $recordId): array
    {
        $entityTable = strtolower(trim($entityTable));
        $recordId = trim($recordId);

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User tidak valid.'];
        }

        if ($entityTable === '' || $recordId === '') {
            return ['success' => false, 'message' => 'Entity dan record wajib dipilih.'];
        }

        $knownEntities = $this->getEntities($projectId);
        $entityName = '';
        foreach ($knownEntities as $entity) {
            if ((string)$entity['name'] === $entityTable) {
                $entityName = (string)$entity['name'];
                break;
            }
        }

        if ($entityName === '') {
            return ['success' => false, 'message' => 'Entity "' . $entityTable . '" tidak dikenali pada metadata.'];
        }

        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        $schema = $db->schema->getTableSchema($entityName, true);
        if ($schema === null) {
            return ['success' => false, 'message' => 'Tabel "' . $entityName . '" tidak tersedia pada database aplikasi.'];
        }

        $primaryKey = $schema->primaryKey;
        if (is_string($primaryKey) && $primaryKey !== '') {
            $primaryKey = [$primaryKey];
        }
        $pkColumn = is_array($primaryKey) && $primaryKey !== [] ? $primaryKey[0] : 'id';

        $exists = (new Query())
            ->from($entityName)
            ->where([$pkColumn => $recordId])
            ->exists($db);

        if (!$exists) {
            return ['success' => false, 'message' => 'Record "' . $recordId . '" tidak ditemukan pada tabel "' . $entityName . '".'];
        }

        $db->createCommand()
            ->update(self::USERS_TABLE, [
                'identity_table' => $entityName,
                'identity_record_id' => $recordId,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $userId])
            ->execute();

        try {
            if (Yii::$app->has('currentIdentity')) {
                Yii::$app->currentIdentity->reset();
            }
        } catch (\Throwable $e) {
            Yii::warning('Current Identity cache reset failed after mapping save: ' . $e->getMessage(), 'current-identity');
        }

        return ['success' => true, 'message' => 'Mapping akun berhasil disimpan.'];
    }

    /**
     * Remove the mapping from an account.
     *
     * @param int|null $projectId
     * @param int $userId
     * @return array{success: bool, message: string}
     */
    public function clearMapping(?int $projectId, int $userId): array
    {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User tidak valid.'];
        }

        $this->ensureDatabaseContext();

        Yii::$app->db->createCommand()
            ->update(self::USERS_TABLE, [
                'identity_table' => null,
                'identity_record_id' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $userId])
            ->execute();

        try {
            if (Yii::$app->has('currentIdentity')) {
                Yii::$app->currentIdentity->reset();
            }
        } catch (\Throwable $e) {
            Yii::warning('Current Identity cache reset failed after mapping clear: ' . $e->getMessage(), 'current-identity');
        }

        return ['success' => true, 'message' => 'Mapping akun berhasil dihapus.'];
    }

    /**
     * Records for the "Pilih Record" dropdown of an entity. The display column
     * is resolved by the framework's RelationMapper (never hardcoded); falls
     * back to the primary key. Only the PK + display column are selected
     * (never SELECT *), with realtime search, pagination and lazy loading.
     *
     * @param string $entityTable
     * @param string $search
     * @param int $page 1-based page number.
     * @param int $pageSize Rows per page (clamped 1..200).
     * @param int|null $projectId
     * @return array{
     *   success: bool,
     *   entity: string,
     *   pk_column: string,
     *   display_column: string,
     *   rows: array<int, array{id: int|string, label: string}>,
     *   pagination: array{page: int, page_size: int, total: int, has_next: bool},
     * }
     */
    public function getRecordsForEntity(string $entityTable, string $search = '', int $page = 1, int $pageSize = 50, ?int $projectId = null): array
    {
        $entityTable = strtolower(trim($entityTable));
        $page = max(1, (int)$page);
        $pageSize = max(1, min(200, (int)$pageSize));
        $search = trim($search);

        if ($entityTable === '') {
            return $this->emptyLookup($page, $pageSize);
        }

        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        $schema = $db->schema->getTableSchema($entityTable, true);
        if ($schema === null) {
            return $this->emptyLookup($page, $pageSize);
        }

        $pkColumn = $this->resolvePkColumn($schema);
        if ($pkColumn === null || !isset($schema->columns[$pkColumn])) {
            return $this->emptyLookup($page, $pageSize);
        }

        $displayColumn = (new RelationMapper($db))->resolveDisplayColumn($entityTable, $pkColumn);
        if ($displayColumn === null || !isset($schema->columns[$displayColumn])) {
            $displayColumn = $pkColumn;
        }

        $query = (new Query())
            ->select([$pkColumn, $displayColumn])
            ->from($entityTable);

        if ($search !== '') {
            $query->andWhere(['like', $displayColumn, $search]);
        }

        try {
            $total = (int)(clone $query)->count('*', $db);
            $rows = $query
                ->orderBy([$displayColumn => SORT_ASC])
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->all($db);
        } catch (\Throwable $e) {
            Yii::warning('Record lookup failed for entity ' . $entityTable . ': ' . $e->getMessage(), 'user-mapping');
            return $this->emptyLookup($page, $pageSize);
        }

        $records = [];
        foreach ($rows as $row) {
            $id = $row[$pkColumn] ?? null;
            if ($id === null || $id === '') {
                continue;
            }
            $label = isset($row[$displayColumn]) && $row[$displayColumn] !== null && trim((string)$row[$displayColumn]) !== ''
                ? (string)$row[$displayColumn]
                : '';
            if ($label === '' || ($displayColumn === $pkColumn && preg_match('/^\d+$/', $label))) {
                $label = 'Record #' . $id;
            }
            $records[] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        return [
            'success' => true,
            'entity' => $entityTable,
            'pk_column' => $pkColumn,
            'display_column' => $displayColumn,
            'rows' => $records,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'has_next' => ($page * $pageSize) < $total,
            ],
        ];
    }

    /**
     * Human-friendly label for the currently stored mapping of an account, used
     * to render an informative status (record display value + table). Read-only:
     * it never mutates the mapping, only reads it.
     *
     * @param int|null $projectId
     * @param int $userId
     * @return array{
     *   mapped: bool,
     *   identity_table: string,
     *   identity_record_id: string,
     *   display_column: string,
     *   label: string,
     * }
     */
    public function getMappingDisplayLabel(?int $projectId, int $userId): array
    {
        $mapping = $this->getMapping($projectId, $userId);
        if ($mapping === null) {
            return [
                'mapped' => false,
                'identity_table' => '',
                'identity_record_id' => '',
                'display_column' => '',
                'label' => '',
            ];
        }

        $entityTable = $mapping['identity_table'];
        $recordId = $mapping['identity_record_id'];
        $pkColumn = '';
        $displayColumn = '';
        $label = '';

        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        $schema = $db->schema->getTableSchema($entityTable, true);
        if ($schema !== null) {
            $pkColumn = $this->resolvePkColumn($schema);
            if ($pkColumn !== null && isset($schema->columns[$pkColumn])) {
                $displayColumn = (new RelationMapper($db))->resolveDisplayColumn($entityTable, $pkColumn);
                if ($displayColumn === null || !isset($schema->columns[$displayColumn])) {
                    $displayColumn = $pkColumn;
                }

                $row = (new Query())
                    ->select([$pkColumn, $displayColumn])
                    ->from($entityTable)
                    ->where([$pkColumn => $recordId])
                    ->limit(1)
                    ->one($db);
                if (is_array($row) && isset($row[$displayColumn]) && $row[$displayColumn] !== null) {
                    $label = trim((string)$row[$displayColumn]);
                }
            }
        }

        if ($label === '' || ($displayColumn !== '' && $displayColumn === $pkColumn && preg_match('/^\d+$/', $label))) {
            $label = 'Record #' . $recordId;
        }

        return [
            'mapped' => true,
            'identity_table' => $entityTable,
            'identity_record_id' => $recordId,
            'display_column' => $displayColumn,
            'label' => $label,
        ];
    }

    /**
     * Mass-import helper: map every account whose domain record points to it via
     * a FK column, without any per-account manual configuration. The FK
     * relationship is auto-detected from metadata when not provided.
     *
     * @param int|null $projectId
     * @param string $entityTable
     * @param string|null $fkColumn Optional FK column pointing to `users`.
     * @return array{success: bool, message: string, mapped: int, total: int}
     */
    public function autoMapImport(?int $projectId, string $entityTable, ?string $fkColumn = null): array
    {
        $entityTable = strtolower(trim($entityTable));
        if ($entityTable === '') {
            return ['success' => false, 'message' => 'Entity tidak valid.', 'mapped' => 0, 'total' => 0];
        }

        if ($fkColumn === null) {
            foreach ($this->getEntities($projectId) as $entity) {
                if ((string)$entity['name'] === $entityTable && !empty($entity['relationships'])) {
                    $fkColumn = $entity['relationships'][0]['fk_column'];
                    break;
                }
            }
        }

        $fkColumn = strtolower(trim((string)$fkColumn));
        if ($fkColumn === '') {
            return ['success' => false, 'message' => 'Kolom FK tidak diketahui untuk entity "' . $entityTable . '".', 'mapped' => 0, 'total' => 0];
        }

        $this->ensureDatabaseContext();
        $db = Yii::$app->db;
        $schema = $db->schema->getTableSchema($entityTable, true);
        if ($schema === null || !isset($schema->columns[$fkColumn])) {
            return ['success' => false, 'message' => 'Tabel/kolom tidak tersedia: ' . $entityTable . '.' . $fkColumn . '.', 'mapped' => 0, 'total' => 0];
        }

        $primaryKey = $schema->primaryKey;
        if (is_string($primaryKey) && $primaryKey !== '') {
            $primaryKey = [$primaryKey];
        }
        $pkColumn = is_array($primaryKey) && $primaryKey !== [] ? $primaryKey[0] : 'id';

        $rows = (new Query())
            ->select([$pkColumn, $fkColumn])
            ->from($entityTable)
            ->where(['not', [$fkColumn => null]])
            ->all($db);

        $total = count($rows);
        $mapped = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $row) {
            $userId = (int)($row[$fkColumn] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $userExists = (new Query())->from(self::USERS_TABLE)->where(['id' => $userId])->exists($db);
            if (!$userExists) {
                continue;
            }

            $db->createCommand()->update(self::USERS_TABLE, [
                'identity_table' => $entityTable,
                'identity_record_id' => (string)$row[$pkColumn],
                'updated_at' => $now,
            ], ['id' => $userId])->execute();

            $mapped++;
        }

        try {
            if (Yii::$app->has('currentIdentity')) {
                Yii::$app->currentIdentity->reset();
            }
        } catch (\Throwable $e) {
            Yii::warning('Current Identity cache reset failed after auto-map: ' . $e->getMessage(), 'current-identity');
        }

        return [
            'success' => true,
            'message' => "Auto-map selesai: {$mapped} dari {$total} record dihubungkan ke akun login.",
            'mapped' => $mapped,
            'total' => $total,
        ];
    }

    /**
     * Human-readable reason when a stored mapping cannot be used (used by the
     * debug panel and the runtime test).
     */
    public function describeMappingFailure(array $mapping): string
    {
        $tableName = strtolower(trim((string)($mapping['identity_table'] ?? '')));
        $recordId = trim((string)($mapping['identity_record_id'] ?? ''));

        if ($tableName === '') {
            return 'Akun belum dihubungkan dengan data domainnya.';
        }
        if ($recordId === '') {
            return 'Record data belum dipilih untuk akun ini.';
        }

        return 'Mapping "' . $tableName . '#' . $recordId . '" tidak dapat digunakan.';
    }

    /**
     * Resolve the primary-key column of a physical table schema.
     *
     * @param mixed $schema
     */
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

    /**
     * Standard empty lookup payload used by getRecordsForEntity.
     *
     * @return array<string, mixed>
     */
    private function emptyLookup(int $page, int $pageSize): array
    {
        return [
            'success' => false,
            'entity' => '',
            'pk_column' => '',
            'display_column' => '',
            'rows' => [],
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => 0,
                'has_next' => false,
            ],
        ];
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

    private function metadataScope(?int $projectId): array
    {
        if ($projectId !== null && ProjectSchema::supportsProjectContext()) {
            return ['project_id' => $projectId];
        }

        if (!Yii::$app->user->isGuest && Yii::$app->user->id !== null) {
            return ['user_id' => (int)Yii::$app->user->id];
        }

        return [];
    }
}
