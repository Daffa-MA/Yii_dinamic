<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\db\Connection;
use yii\helpers\Url;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\components\SystemFieldService;
use app\models\ProjectUser;
use app\components\ProjectSchema;
use app\services\DatabaseSchemaSyncService;
use app\services\TableExistenceService;

class SqlEditorExecutionException extends \RuntimeException
{
    public array $context = [];
}

class TableBuilderController extends Controller
{
    public $layout = 'dashboard';
    
    private const IDENTIFIER_PATTERN = '/^[a-z][a-z0-9_]*$/';
    private const DB_TABLE_COLUMNS_TABLE = 'db_table_columns';
    private ?DatabaseSchemaSyncService $databaseSchemaSyncService = null;

    /**
     * Refresh db_table_columns schema because schema cache is enabled.
     * This ensures newly-migrated FK metadata columns are recognized immediately.
     */
    private function refreshDbTableColumnsSchema(): void
    {
        Yii::$app->db->schema->refresh();
        Yii::$app->db->schema->refreshTableSchema(self::DB_TABLE_COLUMNS_TABLE);
        try {
            $physicalDb = $this->getPhysicalDb();
            $physicalDb->schema->refresh();
            $physicalDb->schema->refreshTableSchema(self::DB_TABLE_COLUMNS_TABLE);
        } catch (\Throwable $e) {
            Yii::warning('Failed refreshing physical db_table_columns schema: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function isFkDebugEnabled(): bool
    {
        $request = Yii::$app->request;
        $raw = $request->post('fk_debug', $request->get('fk_debug', '0'));
        return (string)$raw === '1';
    }

    private function isCommanderSuperAdmin(): bool
    {
        return (new CommanderAuthContext())->isSuperAdmin();
    }

    private function getWorkspaceAuthenticatedUser(?int $projectId = null): ?ProjectUser
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $resolvedProjectId = $projectId ?? $this->getActiveProjectId();
        if ($resolvedProjectId === null) {
            return null;
        }

        return (new ProjectAuthContext())->getAuthenticatedUser($resolvedProjectId);
    }

    private function getEffectiveUserId(): ?int
    {
        $workspaceUser = $this->getWorkspaceAuthenticatedUser();
        if ($workspaceUser !== null) {
            return (int)$workspaceUser->id;
        }

        if (!Yii::$app->user->isGuest && Yii::$app->user->id !== null) {
            return (int)Yii::$app->user->id;
        }

        $commanderUser = (new CommanderAuthContext())->getUser();
        if ($commanderUser !== null && $commanderUser->id !== null) {
            return (int)$commanderUser->id;
        }

        return null;
    }

    private function assignMetadataOwner(DbTable $model): void
    {
        if (!$model->hasAttribute('user_id')) {
            return;
        }

        $effectiveUserId = $this->getEffectiveUserId();
        if ($effectiveUserId !== null) {
            $model->user_id = $effectiveUserId;
        }
    }

    private function canAccessWorkspaceBuilder(): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        if (!Yii::$app->user->isGuest && method_exists(Yii::$app->user->identity, 'isSuperAdmin') && Yii::$app->user->identity->isSuperAdmin()) {
            return true;
        }

        return $this->getWorkspaceAuthenticatedUser() !== null;
    }

    private function logFkDebug(string $stage, array $context = []): void
    {
        if (!$this->isFkDebugEnabled()) {
            return;
        }

        Yii::info([
            'stage' => $stage,
            'context' => $context,
            'route' => Yii::$app->requestedRoute,
        ], 'table_builder_fk_debug');
    }

    private function hasForeignKeyPayload(array $columns): bool
    {
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            $isForeignKey = (bool)($column['is_foreign_key'] ?? false);
            $referencedTable = trim((string)($column['referenced_table_name'] ?? $column['referenced_table'] ?? ''));
            $referencedColumn = trim((string)($column['referenced_column_name'] ?? $column['referenced_column'] ?? ''));

            if ($isForeignKey || $referencedTable !== '' || $referencedColumn !== '') {
                return true;
            }
        }

        return false;
    }

    private function supportsForeignKeyMetadataColumns(): bool
    {
        $schema = $this->getPhysicalDb()->schema->getTableSchema(self::DB_TABLE_COLUMNS_TABLE, true);
        if ($schema === null) {
            return false;
        }

        $requiredColumns = [
            'is_foreign_key',
            'referenced_table_name',
            'referenced_column_name',
            'on_delete_action',
            'on_update_action',
        ];

        foreach ($requiredColumns as $requiredColumn) {
            if (!isset($schema->columns[$requiredColumn])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function getMissingForeignKeyMetadataColumns(): array
    {
        $schema = $this->getPhysicalDb()->schema->getTableSchema(self::DB_TABLE_COLUMNS_TABLE, true);
        if ($schema === null) {
            return [
                'is_foreign_key',
                'referenced_table_name',
                'referenced_column_name',
                'on_delete_action',
                'on_update_action',
            ];
        }

        $missing = [];
        foreach ([
            'is_foreign_key',
            'referenced_table_name',
            'referenced_column_name',
            'on_delete_action',
            'on_update_action',
        ] as $columnName) {
            if (!isset($schema->columns[$columnName])) {
                $missing[] = $columnName;
            }
        }

        return $missing;
    }

    private function assertForeignKeyMetadataSupport(array $columns): void
    {
        if (!$this->hasForeignKeyPayload($columns)) {
            return;
        }

        if ($this->supportsForeignKeyMetadataColumns()) {
            return;
        }

        $this->repairForeignKeyMetadataColumns();
        $this->refreshDbTableColumnsSchema();

        if ($this->supportsForeignKeyMetadataColumns()) {
            return;
        }

        Yii::warning([
            'stage' => 'fk_payload_without_metadata_columns',
            'columns' => $columns,
            'table' => self::DB_TABLE_COLUMNS_TABLE,
            'database' => $this->getDatabaseInfo(),
            'missing_columns' => $this->getMissingForeignKeyMetadataColumns(),
        ], 'table_builder_fk_debug');

        $databaseInfo = $this->getDatabaseInfo();
        $databaseLabel = trim((string)($databaseInfo['name'] ?? '')) !== '' ? (string)$databaseInfo['name'] : 'database aktif';
        $missingColumns = $this->getMissingForeignKeyMetadataColumns();

        throw new \RuntimeException(
            "Konfigurasi Foreign Key terdeteksi, tetapi metadata FK di tabel '" . self::DB_TABLE_COLUMNS_TABLE . "' pada {$databaseLabel} belum lengkap. " .
            'Kolom yang belum tersedia: ' . implode(', ', $missingColumns) . '. ' .
            'Verifikasi database yang dipakai aplikasi, lalu jalankan migration repair.'
        );
    }

    private function repairForeignKeyMetadataColumns(): void
    {
        $this->repairForeignKeyMetadataColumnsOnConnection(Yii::$app->db);
        $this->repairForeignKeyMetadataColumnsOnConnection($this->getPhysicalDb());
    }

    private function repairForeignKeyMetadataColumnsOnConnection(Connection $db): void
    {
        $schema = $db->schema->getTableSchema(self::DB_TABLE_COLUMNS_TABLE, true);
        if ($schema === null) {
            return;
        }

        $columns = $schema->columns;
        if (!isset($columns['is_foreign_key'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'is_foreign_key',
                $db->schema->createColumnSchemaBuilder('boolean')->notNull()->defaultValue(false)
            )->execute();
        }

        if (!isset($columns['referenced_table_name'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'referenced_table_name',
                $db->schema->createColumnSchemaBuilder('string', 100)
            )->execute();
        }

        if (!isset($columns['referenced_column_name'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'referenced_column_name',
                $db->schema->createColumnSchemaBuilder('string', 100)
            )->execute();
        }

        if (!isset($columns['on_delete_action'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'on_delete_action',
                $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('RESTRICT')
            )->execute();
        }

        if (!isset($columns['on_update_action'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'on_update_action',
                $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('RESTRICT')
            )->execute();
        }

        $db->schema->refresh();
        $db->schema->refreshTableSchema(self::DB_TABLE_COLUMNS_TABLE);
    }

    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    private function assignActiveProject(DbTable $model): void
    {
        if (!$model->hasAttribute('project_id')) {
            return;
        }

        $activeProjectId = $this->getActiveProjectId();
        $model->project_id = $activeProjectId !== null ? (int)$activeProjectId : null;
    }

    private function getDatabaseInfo(): array
    {
        $db = $this->getPhysicalDb();
        $dsn = (string)$db->dsn;
        $host = null;
        $port = null;
        $name = null;

        if (preg_match('/host=([^;]+)/i', $dsn, $matches) === 1) {
            $host = trim((string)$matches[1]);
        }
        if (preg_match('/port=([^;]+)/i', $dsn, $matches) === 1) {
            $port = trim((string)$matches[1]);
        }
        if (preg_match('/dbname=([^;]+)/i', $dsn, $matches) === 1) {
            $name = trim((string)$matches[1]);
        }

        if ($name === null || $name === '') {
            try {
                $resolvedName = $db->createCommand('SELECT DATABASE()')->queryScalar();
                $name = $resolvedName !== false ? trim((string)$resolvedName) : null;
            } catch (\Throwable $e) {
                $name = null;
            }
        }

        return [
            'name' => $name ?: null,
            'host' => $host ?: null,
            'port' => $port ?: null,
        ];
    }

    private function hasPhysicalTableByName(string $tableName): bool
    {
        $db = $this->getPhysicalDb();
        return $this->tableExistsInPhysicalDatabase($db, $tableName);
    }

    private function getTableExistenceService(?Connection $db = null): TableExistenceService
    {
        return new TableExistenceService($db ?? $this->getPhysicalDb());
    }

    private function getDatabaseSchemaSyncService(): DatabaseSchemaSyncService
    {
        if ($this->databaseSchemaSyncService === null) {
            $this->databaseSchemaSyncService = new DatabaseSchemaSyncService(
                $this->getPhysicalDb(),
                $this->getMetadataScope(),
                $this->getEffectiveUserId(),
                $this->getActiveProjectId()
            );
        }

        return $this->databaseSchemaSyncService;
    }

    private function getMetadataScope(): array
    {
        $scope = [];
        $effectiveUserId = $this->getEffectiveUserId();
        if ($effectiveUserId !== null) {
            $scope['user_id'] = $effectiveUserId;
        }
        if (ProjectSchema::supportsProjectContext()) {
            $activeProjectId = $this->getActiveProjectId();
            if ($activeProjectId !== null) {
                $scope['project_id'] = $activeProjectId;
            }
        }

        return $scope;
    }

    private function hasPhysicalTable(DbTable $model): bool
    {
        return $this->hasPhysicalTableByName($model->name);
    }

    /**
     * Keep metadata status in sync with actual physical table existence.
     */
    private function syncTableCreationState(DbTable $model, bool $save = true): bool
    {
        $exists = $this->hasPhysicalTable($model);
        $current = (bool)$model->is_created;
        if ($current !== $exists) {
            $model->is_created = $exists;
            if ($save) {
                $model->save(false, ['is_created']);
            }
        }

        return $exists;
    }

    private function setTableLifecycleState(DbTable $model, string $status, ?string $errorMessage = null, bool $save = true): void
    {
        if ($model->hasAttribute('table_status')) {
            $model->setAttribute('table_status', $status);
        }
        if ($model->hasAttribute('last_error_message')) {
            $normalizedError = trim((string)$errorMessage);
            $model->setAttribute('last_error_message', $normalizedError !== '' ? mb_substr($normalizedError, 0, 1000, 'UTF-8') : null);
        }

        if ($save) {
            $attributes = [];
            if ($model->hasAttribute('table_status')) {
                $attributes[] = 'table_status';
            }
            if ($model->hasAttribute('last_error_message')) {
                $attributes[] = 'last_error_message';
            }
            if (!empty($attributes)) {
                $model->save(false, $attributes);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTableLifecycleState(DbTable $model): array
    {
        $physicalExists = $this->syncTableCreationState($model);
        $columnCount = (int)$model->getColumns()->count();
        $storedStatus = strtolower(trim((string)($model->hasAttribute('table_status') ? $model->getAttribute('table_status') : '')));
        $lastError = trim((string)($model->hasAttribute('last_error_message') ? $model->getAttribute('last_error_message') : ''));

        if ($physicalExists && $columnCount > 0) {
            $resolvedStatus = 'active';
        } elseif ($lastError !== '') {
            $resolvedStatus = 'failed';
        } elseif ($columnCount <= 0) {
            $resolvedStatus = 'incomplete';
        } elseif ($storedStatus === 'pending' || $storedStatus === 'active' || $storedStatus === '') {
            $resolvedStatus = 'pending';
        } else {
            $resolvedStatus = $storedStatus;
        }

        $labels = [
            'active' => 'Active',
            'failed' => 'Gagal dibuat',
            'incomplete' => 'Metadata tidak lengkap',
            'pending' => 'Pending',
        ];
        $notes = [
            'active' => 'Table fisik tersedia di database.',
            'failed' => $lastError !== '' ? $lastError : 'Table fisik belum tersedia atau proses sinkronisasi gagal.',
            'incomplete' => 'Metadata table belum lengkap.',
            'pending' => 'Metadata sudah tersimpan, tetapi table fisik belum dibuat.',
        ];

        if ($model->hasAttribute('table_status') && $storedStatus !== $resolvedStatus) {
            $this->setTableLifecycleState($model, $resolvedStatus, $lastError !== '' ? $lastError : null);
        }

        return [
            'code' => $resolvedStatus,
            'label' => $labels[$resolvedStatus] ?? ucfirst($resolvedStatus),
            'note' => $notes[$resolvedStatus] ?? '',
            'physical_exists' => $physicalExists,
            'column_count' => $columnCount,
            'last_error_message' => $lastError,
        ];
    }

    /**
     * Determine whether a metadata column should behave as AUTO_INCREMENT.
     */
    private function isAutoIncrementColumn(DbTableColumn $column): bool
    {
        $integerTypes = ['INT', 'BIGINT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'];
        if ($column->hasAttribute('is_auto_increment')) {
            return (bool)$column->getAttribute('is_auto_increment');
        }

        // Backward compatibility for environments where metadata column doesn't exist yet.
        return (bool)$column->is_primary && in_array(strtoupper((string)$column->type), $integerTypes, true);
    }

    private function isForeignKeyColumn(DbTableColumn $column): bool
    {
        return $column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key');
    }

    private function validateForeignKeyConfig(DbTableColumn $column): void
    {
        if (!$this->isForeignKeyColumn($column)) {
            if ($column->hasAttribute('referenced_table_name')) {
                $column->setAttribute('referenced_table_name', null);
            }
            if ($column->hasAttribute('referenced_column_name')) {
                $column->setAttribute('referenced_column_name', null);
            }
            if ($column->hasAttribute('on_delete_action')) {
                $column->setAttribute('on_delete_action', 'RESTRICT');
            }
            if ($column->hasAttribute('on_update_action')) {
                $column->setAttribute('on_update_action', 'RESTRICT');
            }
            return;
        }

        $referencedTableName = strtolower(trim((string)$column->getAttribute('referenced_table_name')));
        $referencedColumnName = strtolower(trim((string)$column->getAttribute('referenced_column_name')));
        $onDeleteAction = strtoupper(trim((string)$column->getAttribute('on_delete_action')));
        $onUpdateAction = strtoupper(trim((string)$column->getAttribute('on_update_action')));
        if ($onDeleteAction === '') {
            $onDeleteAction = 'RESTRICT';
        }
        if ($onUpdateAction === '') {
            $onUpdateAction = 'RESTRICT';
        }

        if ($referencedTableName === '') {
            $column->addError('referenced_table_name', 'Referenced table name is required when Foreign Key is enabled.');
        } elseif (!preg_match(self::IDENTIFIER_PATTERN, $referencedTableName)) {
            $column->addError('referenced_table_name', 'Referenced table name must start with a letter and contain only lowercase letters, numbers, and underscores.');
        }

        if ($referencedColumnName === '') {
            $column->addError('referenced_column_name', 'Referenced column name is required when Foreign Key is enabled.');
        } elseif (!preg_match(self::IDENTIFIER_PATTERN, $referencedColumnName)) {
            $column->addError('referenced_column_name', 'Referenced column name must start with a letter and contain only lowercase letters, numbers, and underscores.');
        }

        if (!in_array($onDeleteAction, DbTableColumn::FOREIGN_KEY_ACTIONS, true)) {
            $column->addError('on_delete_action', 'Invalid ON DELETE action. Allowed values: ' . implode(', ', DbTableColumn::FOREIGN_KEY_ACTIONS) . '.');
        }

        if (!in_array($onUpdateAction, DbTableColumn::FOREIGN_KEY_ACTIONS, true)) {
            $column->addError('on_update_action', 'Invalid ON UPDATE action. Allowed values: ' . implode(', ', DbTableColumn::FOREIGN_KEY_ACTIONS) . '.');
        }

        $column->setAttribute('referenced_table_name', $referencedTableName ?: null);
        $column->setAttribute('referenced_column_name', $referencedColumnName ?: null);
        $column->setAttribute('on_delete_action', $onDeleteAction !== '' ? $onDeleteAction : 'RESTRICT');
        $column->setAttribute('on_update_action', $onUpdateAction !== '' ? $onUpdateAction : 'RESTRICT');
    }

    /**
     * Validate FK references against the physical database and existing metadata before creating tables.
     *
     * @param array<int, DbTableColumn> $columnModels
     * @return array<int, string>
     */
    private function validateForeignKeyReferences(array $columnModels, ?DbTable $currentTable = null): array
    {
        $db = $this->getPhysicalDb();
        $errors = [];

        foreach ($columnModels as $column) {
            if (!$this->isForeignKeyColumn($column)) {
                continue;
            }

            $referencedTableName = strtolower(trim((string)$column->getAttribute('referenced_table_name')));
            $referencedColumnName = strtolower(trim((string)$column->getAttribute('referenced_column_name')));

            if ($referencedTableName === '' || $referencedColumnName === '') {
                $errors[] = "Column '{$column->name}' membutuhkan tabel dan kolom referensi untuk Foreign Key.";
                continue;
            }

            $referencedSchema = $db->schema->getTableSchema($referencedTableName, true);
            if ($referencedSchema !== null) {
                if (!isset($referencedSchema->columns[$referencedColumnName])) {
                    $errors[] = "Column '{$column->name}' mereferensikan kolom '{$referencedColumnName}' pada tabel '{$referencedTableName}', tetapi kolom itu tidak ditemukan di database fisik.";
                    continue;
                }

                $referencedColumn = $referencedSchema->columns[$referencedColumnName];
                if (!$this->isForeignKeyTypeCompatible($column, $referencedColumn)) {
                    $errors[] = "Column '{$column->name}' tidak kompatibel dengan '{$referencedTableName}.{$referencedColumnName}'. Samakan tipe data dan atribut unsigned/length bila diperlukan.";
                    continue;
                }

                if (!empty($referencedSchema->primaryKey) && in_array($referencedColumnName, $referencedSchema->primaryKey, true)) {
                    continue;
                }

                $uniqueColumns = $this->getUniqueColumnsFromTable($referencedTableName);
                if (isset($uniqueColumns[$referencedColumnName])) {
                    continue;
                }
            }

            if ($currentTable !== null) {
                $referencedTableQuery = DbTable::find()->with(['columns'])->where(['name' => $referencedTableName]);
                if (!$this->isCommanderSuperAdmin()) {
                    $effectiveUserId = $this->getEffectiveUserId();
                    if ($effectiveUserId !== null) {
                        $referencedTableQuery->andWhere(['user_id' => $effectiveUserId]);
                    }
                }
                if (ProjectSchema::supportsProjectContext() && $currentTable->project_id !== null) {
                    $referencedTableQuery->andWhere(['project_id' => $currentTable->project_id]);
                }

                $referencedTableModel = $referencedTableQuery->one();
                if ($referencedTableModel !== null) {
                    $referencedColumns = [];
                    foreach ($referencedTableModel->columns as $refColumn) {
                        $referencedColumns[strtolower((string)$refColumn->name)] = $refColumn;
                    }

                    if (!isset($referencedColumns[$referencedColumnName])) {
                        $errors[] = "Column '{$column->name}' mereferensikan kolom '{$referencedColumnName}' pada tabel '{$referencedTableName}', tetapi kolom itu tidak ditemukan di metadata.";
                        continue;
                    }

                    $refColumnModel = $referencedColumns[$referencedColumnName];
                    if ((bool)$refColumnModel->is_primary || (bool)$refColumnModel->is_unique) {
                        continue;
                    }

                    $errors[] = "Column '{$column->name}' mereferensikan '{$referencedTableName}.{$referencedColumnName}', tetapi kolom referensi harus PRIMARY KEY atau UNIQUE.";
                    continue;
                }
            }

            $errors[] = "Column '{$column->name}' mereferensikan tabel '{$referencedTableName}', tetapi tabel tersebut belum ditemukan di metadata atau database fisik.";
        }

        return $errors;
    }

    private function isForeignKeyTypeCompatible(DbTableColumn $column, $referencedColumn): bool
    {
        $localType = strtoupper(trim((string)$column->type));
        $referencedDbType = strtoupper(trim((string)($referencedColumn->dbType ?? $referencedColumn->type ?? '')));
        $referencedBaseType = strtoupper(trim((string)($referencedColumn->type ?? '')));

        $integerTypes = ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT'];
        $stringTypes = ['CHAR', 'VARCHAR'];

        if (in_array($localType, $integerTypes, true)) {
            $referencedIsInteger = false;
            foreach ($integerTypes as $candidate) {
                if (preg_match('/\b' . preg_quote($candidate, '/') . '\b/i', $referencedDbType) === 1 || $referencedBaseType === strtolower($candidate)) {
                    $referencedIsInteger = true;
                    break;
                }
            }
            if (!$referencedIsInteger) {
                return false;
            }
            $localUnsigned = stripos((string)$column->comment, 'unsigned') !== false;
            $referencedUnsigned = stripos($referencedDbType, 'UNSIGNED') !== false;
            return $localUnsigned === $referencedUnsigned || !$localUnsigned;
        }

        if (in_array($localType, $stringTypes, true)) {
            $referencedIsString = preg_match('/\b(CHAR|VARCHAR)\b/i', $referencedDbType) === 1 || in_array(strtoupper($referencedBaseType), $stringTypes, true);
            if (!$referencedIsString) {
                return false;
            }
            if ((int)$column->length > 0 && preg_match('/\((\d+)\)/', $referencedDbType, $matches) === 1) {
                return (int)$column->length === (int)$matches[1];
            }
            return true;
        }

        return stripos($referencedDbType, $localType) !== false || $referencedBaseType === strtolower($localType);
    }

    private function buildForeignKeyConstraintName(string $tableName, string $columnName, array &$usedConstraintNames): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9_]+/', '_', 'fk_' . $tableName . '_' . $columnName));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'fk_constraint';
        }

        // Append a short unique hash based on the current time to avoid collisions with
        // backup tables or previous versions of the same table during sync/rebuild.
        $base .= '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 4);

        $maxLength = 64;
        $candidate = substr($base, 0, $maxLength);
        $suffix = 1;
        while (isset($usedConstraintNames[$candidate])) {
            $suffixText = '_' . $suffix;
            $candidate = substr($base, 0, $maxLength - strlen($suffixText)) . $suffixText;
            $suffix++;
        }

        $usedConstraintNames[$candidate] = true;
        return $candidate;
    }

    private function parseEnumValues(?string $rawValues): array
    {
        $rawValues = trim((string)$rawValues);
        if ($rawValues === '') {
            return [];
        }

        $values = array_map(static function ($value) {
            $value = trim((string)$value);
            return trim($value, " \t\n\r\0\x0B'\"");
        }, preg_split('/,/', $rawValues) ?: []);
        $values = array_filter($values, static function ($value) {
            return $value !== '';
        });

        return array_values(array_unique($values));
    }

    private function buildEnumSetType(DbTableColumn $column, \yii\db\Connection $db): string
    {
        $rawValues = $column->hasAttribute('enum_values')
            ? (string)$column->getAttribute('enum_values')
            : '';
        $values = $this->parseEnumValues($rawValues);
        if (empty($values)) {
            throw new \RuntimeException("Column '{$column->name}' requires values for {$column->type}.");
        }

        $quotedValues = array_map([$db, 'quoteValue'], $values);
        return strtoupper((string)$column->type) . '(' . implode(',', $quotedValues) . ')';
    }

    private function getForeignKeyReferenceMap(): array
    {
        $activeProjectId = $this->getActiveProjectId();
        $tablesQuery = DbTable::find()
            ->with(['columns'])
            ->orderBy(['name' => SORT_ASC]);

        if (!$this->isCommanderSuperAdmin()) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $tablesQuery->where(['user_id' => $effectiveUserId]);
            }
        }

        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $tablesQuery->andWhere(['project_id' => $activeProjectId]);
        }

        $tables = $tablesQuery->all();
        $referenceMap = [];

        foreach ($tables as $table) {
            $referenceMap[(string)$table->name] = array_values(array_filter(array_map(
                static function (DbTableColumn $column): string {
                    return (string)$column->name;
                },
                $table->columns
            )));
        }

        return $referenceMap;
    }

    private function buildCreateTableSql(DbTable $model, array $columns, bool $includeForeignKeys = true): string
    {
        $db = $this->getPhysicalDb();
        $columnDefs = [];
        $primaryKeys = [];
        $autoIncrementCandidates = [];
        $usedConstraintNames = [];

        foreach ($columns as $col) {
            $type = $this->resolveColumnSqlType($col);
            $typeName = strtoupper((string)$col->type);
            if (in_array($typeName, ['ENUM', 'SET'], true)) {
                $type = $this->buildEnumSetType($col, $db);
            }
            $isAutoIncrement = $this->isAutoIncrementColumn($col);
            $nullable = ($col->is_primary || $isAutoIncrement) ? 'NOT NULL' : ($col->is_nullable ? 'NULL' : 'NOT NULL');
            $default = ($isAutoIncrement || $col->default_value === null) ? '' : 'DEFAULT ' . $db->quoteValue($col->default_value);
            $comment = $col->comment ? 'COMMENT ' . $db->quoteValue($col->comment) : '';
            $autoIncrementSql = $isAutoIncrement ? 'AUTO_INCREMENT' : '';

            $def = "`{$col->name}` {$type} {$nullable} {$default} {$autoIncrementSql} {$comment}";
            $columnDefs[] = trim($def);

            if ($col->is_primary) {
                $primaryKeys[] = "`{$col->name}`";
            }
            if ($isAutoIncrement) {
                $autoIncrementCandidates[] = $col->name;
            }
        }

        if (count($autoIncrementCandidates) > 1) {
            throw new \RuntimeException('Only one AUTO_INCREMENT column is allowed per table.');
        }
        if (!empty($autoIncrementCandidates) && count($primaryKeys) !== 1) {
            throw new \RuntimeException('AUTO_INCREMENT requires exactly one primary key column.');
        }

        if (!empty($primaryKeys)) {
            $columnDefs[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }

        foreach ($columns as $col) {
            if ($col->is_unique && !$col->is_primary) {
                $columnDefs[] = "UNIQUE KEY `uk_{$col->name}` (`{$col->name}`)";
            }
        }

        if ($includeForeignKeys) {
            $columnDefs = array_merge($columnDefs, $this->buildForeignKeyConstraintSqlParts($model, $columns, $usedConstraintNames));
        }

        return "CREATE TABLE `{$model->name}` (\n    " . implode(",\n    ", $columnDefs) . "\n) ENGINE={$model->engine} DEFAULT CHARSET={$model->charset} COLLATE={$model->collation}";
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @param array<string, bool> $usedConstraintNames
     * @return array<int, string>
     */
    private function buildForeignKeyConstraintSqlParts(DbTable $model, array $columns, array &$usedConstraintNames): array
    {
        $foreignKeyDefs = [];

        foreach ($columns as $col) {
            if (!$this->isForeignKeyColumn($col)) {
                continue;
            }

            [$referencedTableName, $referencedColumnName, $onDeleteAction, $onUpdateAction] = $this->resolveForeignKeySqlConfig($col);
            $constraintName = $this->buildForeignKeyConstraintName($model->name, $col->name, $usedConstraintNames);
            $foreignKeyDefs[] = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$col->name}`) REFERENCES `{$referencedTableName}` (`{$referencedColumnName}`) ON DELETE {$onDeleteAction} ON UPDATE {$onUpdateAction}";
        }

        return $foreignKeyDefs;
    }

    /**
     * @return array{0:string,1:string,2:string,3:string}
     */
    private function resolveForeignKeySqlConfig(DbTableColumn $column): array
    {
        $referencedTableName = strtolower(trim((string)$column->getAttribute('referenced_table_name')));
        $referencedColumnName = strtolower(trim((string)$column->getAttribute('referenced_column_name')));
        $onDeleteAction = strtoupper(trim((string)$column->getAttribute('on_delete_action')));
        $onUpdateAction = strtoupper(trim((string)$column->getAttribute('on_update_action')));

        if ($onDeleteAction === '') {
            $onDeleteAction = 'RESTRICT';
        }
        if ($onUpdateAction === '') {
            $onUpdateAction = 'RESTRICT';
        }

        if ($referencedTableName === '' || $referencedColumnName === '') {
            throw new \RuntimeException("Foreign key column '{$column->name}' requires referenced table and column names.");
        }
        if (!preg_match(self::IDENTIFIER_PATTERN, $referencedTableName) || !preg_match(self::IDENTIFIER_PATTERN, $referencedColumnName)) {
            throw new \RuntimeException("Foreign key column '{$column->name}' has invalid referenced table or column format.");
        }
        if (!in_array($onDeleteAction, DbTableColumn::FOREIGN_KEY_ACTIONS, true)) {
            throw new \RuntimeException("Foreign key column '{$column->name}' has invalid ON DELETE action.");
        }
        if (!in_array($onUpdateAction, DbTableColumn::FOREIGN_KEY_ACTIONS, true)) {
            throw new \RuntimeException("Foreign key column '{$column->name}' has invalid ON UPDATE action.");
        }

        return [$referencedTableName, $referencedColumnName, $onDeleteAction, $onUpdateAction];
    }

    /**
     * @param array<int, DbTableColumn> $columns
     */
    private function addForeignKeyConstraints(DbTable $model, array $columns): void
    {
        $db = $this->getPhysicalDb();
        $usedConstraintNames = [];

        foreach ($columns as $column) {
            if (!$this->isForeignKeyColumn($column)) {
                continue;
            }

            [$referencedTableName, $referencedColumnName, $onDeleteAction, $onUpdateAction] = $this->resolveForeignKeySqlConfig($column);
            $constraintName = $this->buildForeignKeyConstraintName($model->name, $column->name, $usedConstraintNames);
            try {
                $this->ensureForeignKeyColumnIndex($model->name, (string)$column->name);
                $db->createCommand(
                    "ALTER TABLE `{$model->name}` ADD CONSTRAINT `{$constraintName}` " .
                    "FOREIGN KEY (`{$column->name}`) REFERENCES `{$referencedTableName}` (`{$referencedColumnName}`) " .
                    "ON DELETE {$onDeleteAction} ON UPDATE {$onUpdateAction}"
                )->execute();
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    "Column '{$column->name}' gagal dibuat sebagai Foreign Key ke '{$referencedTableName}.{$referencedColumnName}': " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    private function ensureForeignKeyColumnIndex(string $tableName, string $columnName): void
    {
        $db = $this->getPhysicalDb();
        if (stripos((string)$db->dsn, 'mysql:') !== 0) {
            return;
        }

        $escapedTable = str_replace('`', '``', $tableName);
        $escapedColumn = str_replace('`', '``', $columnName);
        $rows = $db->createCommand("SHOW INDEX FROM `{$escapedTable}` WHERE Column_name = :column_name")
            ->bindValue(':column_name', $columnName)
            ->queryAll();

        if (!empty($rows)) {
            return;
        }

        $indexName = substr('idx_' . preg_replace('/[^a-z0-9_]+/i', '_', $tableName . '_' . $columnName), 0, 64);
        $escapedIndex = str_replace('`', '``', $indexName);
        $db->createCommand("ALTER TABLE `{$escapedTable}` ADD INDEX `{$escapedIndex}` (`{$escapedColumn}`)")->execute();
    }

    private function resolveColumnSqlType(DbTableColumn $column): string
    {
        if (!$this->isForeignKeyColumn($column)) {
            return $column->getMySQLType();
        }

        $referencedTableName = strtolower(trim((string)$column->getAttribute('referenced_table_name')));
        $referencedColumnName = strtolower(trim((string)$column->getAttribute('referenced_column_name')));
        if ($referencedTableName === '' || $referencedColumnName === '') {
            return $column->getMySQLType();
        }

        $physicalType = $this->getReferencedPhysicalColumnType($referencedTableName, $referencedColumnName);
        if ($physicalType !== null) {
            return $physicalType;
        }

        $metadataColumn = $this->findReferencedMetadataColumn($referencedTableName, $referencedColumnName, $column->table_id !== null ? (int)$column->table_id : null);
        return $metadataColumn !== null ? $metadataColumn->getMySQLType() : $column->getMySQLType();
    }

    private function getReferencedPhysicalColumnType(string $tableName, string $columnName): ?string
    {
        $schema = $this->getPhysicalDb()->schema->getTableSchema($tableName, true);
        if ($schema === null || !isset($schema->columns[$columnName])) {
            return null;
        }

        $dbType = trim((string)$schema->columns[$columnName]->dbType);
        if ($dbType === '') {
            return null;
        }

        return preg_replace('/\s+auto_increment\b/i', '', $dbType) ?: null;
    }

    private function findReferencedMetadataColumn(string $tableName, string $columnName, ?int $sourceTableId = null): ?DbTableColumn
    {
        $query = DbTable::find()->with(['columns'])->where(['name' => $tableName]);

        if (!$this->isCommanderSuperAdmin()) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $query->andWhere(['user_id' => $effectiveUserId]);
            }
        }

        if (ProjectSchema::supportsProjectContext()) {
            $projectId = null;
            if ($sourceTableId !== null) {
                $sourceTable = DbTable::findOne($sourceTableId);
                $projectId = $sourceTable !== null && $sourceTable->hasAttribute('project_id') ? $sourceTable->project_id : null;
            }
            if ($projectId === null) {
                $projectId = $this->getActiveProjectId();
            }
            if ($projectId !== null) {
                $query->andWhere(['project_id' => $projectId]);
            }
        }

        $table = $query->one();
        if ($table === null) {
            return null;
        }

        foreach ($table->columns as $column) {
            if (strcasecmp((string)$column->name, $columnName) === 0) {
                return $column;
            }
        }

        return null;
    }

    private function getPhysicalDb(): Connection
    {
        $metadataDb = Yii::$app->db;
        $project = null;
        
        // Try to get active project safely
        try {
            if (class_exists('app\components\ActiveProjectContext')) {
                $context = new \app\components\ActiveProjectContext();
                $project = $context->getActiveProject();
            }
        } catch (\Throwable $e) {
            // Fallback to metadata db
        }
        
        // If no active project, use metadata db
        if ($project === null) {
            return $metadataDb;
        }

        $databaseContext = new ActiveDatabaseContext();
        $legacyDatabaseName = sprintf('proj_u%d_p%d', (int)$project->user_id, (int)$project->id);
        $customDatabaseName = strtolower(trim((string)$project->name));
        $customDatabaseName = preg_replace('/[^a-z0-9]+/i', '_', $customDatabaseName) ?? '';
        $customDatabaseName = trim($customDatabaseName, '_');
        if ($customDatabaseName === '') {
            $customDatabaseName = 'project';
        }
        if (preg_match('/^[0-9]/', $customDatabaseName) === 1) {
            $customDatabaseName = 'project_' . $customDatabaseName;
        }
        if (strlen($customDatabaseName) > 64) {
            $customDatabaseName = rtrim(substr($customDatabaseName, 0, 64), '_');
        }

        $targetDatabase = $databaseContext->databaseExistsOnCurrentServer($legacyDatabaseName)
            && !$databaseContext->databaseExistsOnCurrentServer($customDatabaseName)
            ? $legacyDatabaseName
            : $customDatabaseName;

        if ($targetDatabase === '') {
            return $metadataDb;
        }

        $dsn = (string)$metadataDb->dsn;
        if (stripos($dsn, 'mysql:') !== 0) {
            return $metadataDb;
        }

        if (preg_match('/dbname=([^;]+)/i', $dsn, $matches) === 1 && trim((string)$matches[1]) === $targetDatabase) {
            return $metadataDb;
        }

        $projectDsn = preg_match('/dbname=([^;]+)/i', $dsn)
            ? (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $targetDatabase, $dsn, 1)
            : rtrim($dsn, ';') . ';dbname=' . $targetDatabase;

        $connection = Yii::createObject([
            'class' => Connection::class,
            'dsn' => $projectDsn,
            'username' => $metadataDb->username,
            'password' => $metadataDb->password,
            'charset' => $metadataDb->charset,
            'tablePrefix' => $metadataDb->tablePrefix,
            'attributes' => $metadataDb->attributes,
            'enableSchemaCache' => $metadataDb->enableSchemaCache,
            'schemaCacheDuration' => $metadataDb->schemaCacheDuration,
            'schemaCacheExclude' => $metadataDb->schemaCacheExclude,
            'schemaCache' => $metadataDb->schemaCache,
            'enableQueryCache' => $metadataDb->enableQueryCache,
            'queryCacheDuration' => $metadataDb->queryCacheDuration,
            'queryCache' => $metadataDb->queryCache,
        ]);
        $connection->open();

        return $connection;
    }

    private function buildColumnModels(array $columns, int $tableId): array
    {
        $this->logFkDebug('buildColumnModels.input', [
            'table_id' => $tableId,
            'columns_count' => count($columns),
            'columns' => $columns,
        ]);

        $columnModels = [];
        $seenNames = [];

        foreach ($columns as $index => $colData) {
            if (empty($colData['name']) && empty($colData['label']) && empty($colData['type'])) {
                continue;
            }

            $column = new DbTableColumn();
            $column->table_id = $tableId;
            $column->name = strtolower(trim((string)($colData['name'] ?? '')));
            $column->label = trim((string)($colData['label'] ?? ''));
            $column->type = (string)($colData['type'] ?? '');
            $column->length = $colData['length'] !== '' && $colData['length'] !== null ? (int)$colData['length'] : null;
            $column->is_nullable = (bool)($colData['is_nullable'] ?? false);
            $column->is_primary = (bool)($colData['is_primary'] ?? false);
            $column->is_unique = (bool)($colData['is_unique'] ?? false);
            if ($column->hasAttribute('is_auto_increment')) {
                $column->setAttribute('is_auto_increment', (bool)($colData['is_auto_increment'] ?? false));
            }
            if ($column->hasAttribute('is_foreign_key')) {
                $rawReferencedTable = $colData['referenced_table_name']
                    ?? $colData['referenced_table']
                    ?? $colData['referencedTable']
                    ?? null;
                $rawReferencedColumn = $colData['referenced_column_name']
                    ?? $colData['referenced_column']
                    ?? $colData['referencedColumn']
                    ?? null;
                $rawOnDeleteAction = $colData['on_delete_action']
                    ?? $colData['on_delete']
                    ?? $colData['onDelete']
                    ?? 'RESTRICT';
                $rawOnUpdateAction = $colData['on_update_action']
                    ?? $colData['on_update']
                    ?? $colData['onUpdate']
                    ?? 'RESTRICT';

                $column->setAttribute('is_foreign_key', (bool)($colData['is_foreign_key'] ?? false));
                $column->setAttribute('referenced_table_name', (string)($rawReferencedTable ?? ''));
                $column->setAttribute('referenced_column_name', (string)($rawReferencedColumn ?? ''));
                $column->setAttribute('on_delete_action', (string)$rawOnDeleteAction);
                $column->setAttribute('on_update_action', (string)$rawOnUpdateAction);
                $this->validateForeignKeyConfig($column);
                $this->logFkDebug('buildColumnModels.fk_resolved', [
                    'index' => $index,
                    'column_name' => $column->name,
                    'is_foreign_key' => (bool)$column->getAttribute('is_foreign_key'),
                    'referenced_table_name' => (string)$column->getAttribute('referenced_table_name'),
                    'referenced_column_name' => (string)$column->getAttribute('referenced_column_name'),
                    'on_delete_action' => (string)$column->getAttribute('on_delete_action'),
                    'on_update_action' => (string)$column->getAttribute('on_update_action'),
                ]);
            } elseif (
                isset($colData['is_foreign_key'])
                || isset($colData['referenced_table_name'])
                || isset($colData['referenced_table'])
                || isset($colData['referenced_column_name'])
                || isset($colData['referenced_column'])
            ) {
                $this->logFkDebug('buildColumnModels.fk_attributes_missing', [
                    'index' => $index,
                    'column_name' => $column->name,
                    'payload' => $colData,
                ]);
            }
            if ($column->hasAttribute('enum_values')) {
                $rawEnumValues = $colData['enum_values'] ?? $colData['enumValues'] ?? '';
                $rawEnumValues = trim((string)$rawEnumValues);
                $type = strtoupper((string)$column->type);
                if (in_array($type, ['ENUM', 'SET'], true)) {
                    $column->setAttribute('enum_values', $rawEnumValues);
                } else {
                    $column->setAttribute('enum_values', null);
                }
            }

            $column->default_value = $colData['default_value'] !== '' ? (string)$colData['default_value'] : null;
            $column->comment = $colData['comment'] !== '' ? (string)$colData['comment'] : null;
            $column->sort_order = $index;

            if ($this->isAutoIncrementColumn($column)) {
                $type = strtoupper((string)$column->type);
                if (!in_array($type, ['INT', 'BIGINT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'], true)) {
                    $column->addError('is_auto_increment', 'Auto increment is only supported for TINYINT, SMALLINT, MEDIUMINT, INT, or BIGINT.');
                }
                $column->is_primary = true;
                $column->is_nullable = false;
            }

            if ($column->label === '' && $column->name !== '') {
                $column->label = ucwords(str_replace('_', ' ', $column->name));
            }

            if ($column->name !== '' && !preg_match('/^[a-z][a-z0-9_]*$/', $column->name)) {
                $column->addError('name', 'Column name must start with a letter and contain only lowercase letters, numbers, and underscores.');
            }

            if ($column->name !== '') {
                if (isset($seenNames[$column->name])) {
                    $column->addError('name', "Duplicate column name '{$column->name}' is not allowed.");
                }
                $seenNames[$column->name] = true;
            }

            $columnModels[] = $column;
        }

        $this->logFkDebug('buildColumnModels.output', [
            'table_id' => $tableId,
            'column_models_count' => count($columnModels),
        ]);

        return $columnModels;
    }

    private function collectColumnErrors(array $columnModels): array
    {
        $errors = [];

        foreach ($columnModels as $column) {
            if (!$column->validate(null, false)) {
                $identifier = $column->label ?: $column->name ?: 'Unnamed column';
                $errors[] = "Column '{$identifier}': " . implode(', ', $column->getErrorSummary(true));
            }
        }

        return $errors;
    }

    /**
     * Rebuild physical SQL table after metadata update and migrate overlapping data.
     */
    private function syncUpdatedPhysicalTable(DbTable $model, string $oldTableName, array $columnModels): void
    {
        $db = $this->getPhysicalDb();
        $newTableName = (string)$model->name;

        if (!$this->hasPhysicalTableByName($oldTableName)) {
            $model->is_created = false;
            $model->save(false, ['is_created']);
            return;
        }

        if ($newTableName !== $oldTableName && $this->hasPhysicalTableByName($newTableName)) {
            throw new \RuntimeException("Cannot rename table to '{$newTableName}' because it already exists in database.");
        }

        $backupTableName = $oldTableName . '__bak_' . time();
        if ($this->hasPhysicalTableByName($backupTableName)) {
            $backupTableName .= '_' . mt_rand(1000, 9999);
        }

        $renamedToBackup = false;
        $newTableCreated = false;
        $incomingFks = [];

        try {
            // 1. Detect incoming foreign keys from other tables that point to this table
            $incomingFks = $this->getIncomingForeignKeys($oldTableName);

            // Disable foreign key checks during table sync/rebuild to allow renaming parent tables
            // and avoiding immediate constraint violations during the reconstruction process.
            $db->createCommand("SET FOREIGN_KEY_CHECKS = 0")->execute();

            $db->createCommand("RENAME TABLE `{$oldTableName}` TO `{$backupTableName}`")->execute();
            $renamedToBackup = true;

            $sql = $this->buildCreateTableSql($model, $columnModels, false);
            $db->createCommand($sql)->execute();
            $newTableCreated = true;

            $backupSchema = $db->schema->getTableSchema($backupTableName, true);
            $newSchema = $db->schema->getTableSchema($newTableName, true);

            if ($backupSchema && $newSchema) {
                $oldColumns = array_keys($backupSchema->columns);
                $newColumns = array_keys($newSchema->columns);
                $commonColumns = array_values(array_intersect($oldColumns, $newColumns));

                if (!empty($commonColumns)) {
                    $quotedCols = implode(', ', array_map(static function ($col) {
                        return "`{$col}`";
                    }, $commonColumns));
                    $db->createCommand("INSERT INTO `{$newTableName}` ({$quotedCols}) SELECT {$quotedCols} FROM `{$backupTableName}`")->execute();
                }
            }

            // 2. Update incoming foreign keys to point to the new table
            // After RENAME, MySQL automatically updated these FKs to point to $backupTableName.
            // We need to point them back to $newTableName.
            foreach ($incomingFks as $fk) {
                try {
                    $db->createCommand("ALTER TABLE `{$fk['table_name']}` DROP FOREIGN KEY `{$fk['constraint_name']}`")->execute();
                    $db->createCommand("ALTER TABLE `{$fk['table_name']}` ADD CONSTRAINT `{$fk['constraint_name']}` 
                        FOREIGN KEY (`{$fk['column_name']}`) REFERENCES `{$newTableName}` (`{$fk['referenced_column_name']}`) 
                        ON DELETE {$fk['delete_rule']} ON UPDATE {$fk['update_rule']}")->execute();
                } catch (\Throwable $fkError) {
                    Yii::error("Failed to re-map foreign key {$fk['constraint_name']} from {$fk['table_name']}: " . $fkError->getMessage());
                }
            }

            $this->addForeignKeyConstraints($model, $columnModels);

            $db->createCommand("DROP TABLE `{$backupTableName}`")->execute();
            $model->is_created = true;
            $model->save(false, ['is_created']);
        } catch (\Throwable $e) {
            try {
                if ($newTableCreated && $this->hasPhysicalTableByName($newTableName)) {
                    $db->createCommand("DROP TABLE `{$newTableName}`")->execute();
                }
                if ($renamedToBackup && $this->hasPhysicalTableByName($backupTableName)) {
                    // Try to restore FKs to backup before renaming back
                    $db->createCommand("RENAME TABLE `{$backupTableName}` TO `{$oldTableName}`")->execute();
                }
            } catch (\Throwable $rollbackError) {
                Yii::error('Rollback after table sync failure also failed: ' . $rollbackError->getMessage(), 'app');
            }

            throw $e;
        } finally {
            try {
                $db->createCommand("SET FOREIGN_KEY_CHECKS = 1")->execute();
            } catch (\Throwable $ignore) {
            }
        }
    }

    /**
     * Find all foreign keys from other tables pointing to the specified table.
     */
    private function getIncomingForeignKeys(string $tableName): array
    {
        $db = $this->getPhysicalDb();
        $dbName = (string)$db->createCommand('SELECT DATABASE()')->queryScalar();
        
        if ($dbName === '') {
            return [];
        }

        $sql = "
            SELECT 
                kcu.TABLE_NAME as table_name,
                kcu.COLUMN_NAME as column_name,
                kcu.CONSTRAINT_NAME as constraint_name,
                kcu.REFERENCED_COLUMN_NAME as referenced_column_name,
                rc.UPDATE_RULE as update_rule,
                rc.DELETE_RULE as delete_rule
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
            JOIN 
                INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE 
                kcu.REFERENCED_TABLE_NAME = :tableName
                AND kcu.TABLE_SCHEMA = :dbName
        ";

        return $db->createCommand($sql, [
            ':tableName' => $tableName,
            ':dbName' => $dbName,
        ])->queryAll();
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            return $this->canAccessWorkspaceBuilder();
                        },
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Apply active workspace database first so layout/theme and builder data
        // read from the same project context as other workspace admin pages.
        $databaseContext = new ActiveDatabaseContext();
        $databaseContext->resolveAndApply();
        $this->repairForeignKeyMetadataColumns();
        $this->refreshDbTableColumnsSchema();

        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = $this->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('tableBuilderWarning', 'Pilih atau buat project terlebih dahulu sebelum mengelola table.');
            $this->redirect(['project/index']);
            return false;
        }

        return true;
    }

    public function actionIndex()
    {
        try {
            $this->getDatabaseSchemaSyncService()->syncAllPhysicalTables();
            $this->refreshDbTableColumnsSchema();
        } catch (\Throwable $syncError) {
            Yii::warning('Auto sync from physical database failed on table index: ' . $syncError->getMessage(), 'table-builder-sync');
            Yii::$app->session->setFlash('tableBuilderWarning', 'Sinkronisasi dari database fisik gagal: ' . $this->buildFriendlyTableBuilderErrorMessage($syncError));
        }

        $activeProjectId = $this->getActiveProjectId();
        $databaseInfo = $this->getDatabaseInfo();
        $tablesQuery = DbTable::find()
            ->with(['columns'])
            ->orderBy(['created_at' => SORT_DESC]);
        if (!$this->isCommanderSuperAdmin()) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $tablesQuery->where(['user_id' => $effectiveUserId]);
            }
        }
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $tablesQuery->andWhere(['project_id' => $activeProjectId]);
        }
        if (DbTable::getTableSchema() !== null && isset(DbTable::getTableSchema()->columns['is_created'])) {
            $tablesQuery->andWhere(['is_created' => true]);
        }
        $tables = $tablesQuery->all();

        // Build array with tables and their columns
        $tablesWithColumns = [];
        foreach ($tables as $table) {
            $item = new \stdClass();
            $item->table = $table;
            $item->columns = $table->columns;
            $item->statusMeta = (object)$this->resolveTableLifecycleState($table);
            $tablesWithColumns[] = $item;
        }

        return $this->render('index', [
            'tables' => $tablesWithColumns,
            'databaseInfo' => $databaseInfo,
        ]);
    }

    public function actionCreate()
    {
        $this->refreshDbTableColumnsSchema();

        $model = new DbTable();
        $this->assignMetadataOwner($model);
        $this->assignActiveProject($model);
        $model->engine = 'InnoDB';
        $model->charset = 'utf8mb4';
        $model->collation = 'utf8mb4_unicode_ci';

        // Preserve column data for re-rendering on validation failure
        $savedColumns = [];
        $foreignKeyReferenceMap = $this->getForeignKeyReferenceMap();
        $builderMode = trim((string)Yii::$app->request->post('builder_mode', 'manual'));
        $rawSql = trim((string)Yii::$app->request->post('raw_sql', ''));

        if ($builderMode === 'sql' && Yii::$app->request->isPost) {
            try {
                $executionResult = $this->executeRawSchemaSql($rawSql);
                $responsePayload = [
                    'success' => true,
                    'stage' => $executionResult['current_stage'] ?? 'created_then_synced',
                    'message' => $executionResult['message'] ?? 'Tabel berhasil dibuat dan metadata berhasil disinkronkan.',
                    'sql_error' => null,
                    'failed_statement' => null,
                    'parsed_columns' => $executionResult['parsed_columns'] ?? [],
                    'created_table_name' => $executionResult['created_table_name'] ?? null,
                    'table_name' => $executionResult['created_table_name'] ?? null,
                    'active_database' => $executionResult['active_database'] ?? null,
                    'physical_table_exists' => $executionResult['physical_table_exists'] ?? null,
                    'metadata_table_exists' => $executionResult['metadata_table_exists'] ?? null,
                    'existed_before_execute' => $executionResult['existed_before_execute'] ?? null,
                    'exists_after_execute' => $executionResult['exists_after_execute'] ?? null,
                    'executed_statement_count' => $executionResult['executed_statement_count'] ?? null,
                    'redirect_url' => Url::to(['table-builder/index']),
                ];

                if (Yii::$app->request->isAjax || stripos((string)Yii::$app->request->headers->get('accept', ''), 'application/json') !== false) {
                    return $this->asJson($responsePayload);
                }

                Yii::$app->session->setFlash('success', $responsePayload['message']);
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                $sqlDebug = $this->buildSqlEditorDebugPayload($e, $rawSql);

                if (Yii::$app->request->isAjax || stripos((string)Yii::$app->request->headers->get('accept', ''), 'application/json') !== false) {
                    return $this->asJson($sqlDebug);
                }

                return $this->render('create', [
                    'model' => $model,
                    'savedColumns' => $savedColumns,
                    'foreignKeyReferenceMap' => $foreignKeyReferenceMap,
                    'databaseInfo' => $this->getDatabaseInfo(),
                    'builderMode' => 'sql',
                    'rawSql' => $rawSql,
                    'sqlError' => $sqlDebug['message'] ?? $this->buildFriendlyTableBuilderErrorMessage($e),
                    'sqlDebug' => $sqlDebug,
                ]);
            }
        }

        if ($model->load(Yii::$app->request->post())) {
            $this->assignMetadataOwner($model);
            $this->assignActiveProject($model);
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }

            $this->logFkDebug('actionCreate.post_columns', [
                'columns_count' => count($columns),
                'columns' => $columns,
            ]);

            // Save columns for restoring on validation failure
            $savedColumns = $columns;

            try {
                $this->assertForeignKeyMetadataSupport($columns);

                $transaction = Yii::$app->db->beginTransaction();
                $physicalCreated = false;
                try {
                    if (!$model->save()) {
                        throw new \RuntimeException('Please fix the errors below: ' . implode(', ', $model->getErrorSummary(true)));
                    }

                    if ($this->hasPhysicalTable($model)) {
                        throw new \RuntimeException("Table '{$model->name}' sudah ada di database fisik aktif.");
                    }

                    $columnModels = $this->buildColumnModels($columns, (int)$model->id);

                    if (empty($columnModels)) {
                        throw new \RuntimeException('Please add at least one valid column before creating the table.');
                    }

                    $columnErrors = $this->collectColumnErrors($columnModels);
                    if (!empty($columnErrors)) {
                        throw new \RuntimeException(implode('<br>', $columnErrors));
                    }

                    $foreignKeyErrors = $this->validateForeignKeyReferences($columnModels, $model);
                    if (!empty($foreignKeyErrors)) {
                        throw new \RuntimeException(implode('<br>', $foreignKeyErrors));
                    }

                    foreach ($columnModels as $column) {
                        if (!$column->save(false)) {
                            throw new \RuntimeException("Failed to save column '{$column->name}'.");
                        }
                    }

                    $db = $this->getPhysicalDb();
                    $sql = $this->buildCreateTableSql($model, $columnModels, false);
                    $db->createCommand($sql)->execute();
                    $physicalCreated = true;
                    $this->addForeignKeyConstraints($model, $columnModels);

                    $model = $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
                    $this->refreshDbTableColumnsSchema();
                    $transaction->commit();

                    Yii::$app->session->setFlash('tableBuilderSuccess', "Table '{$model->name}' berhasil dibuat di database fisik dan metadata berhasil disinkronkan.");
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    if ($physicalCreated && $this->hasPhysicalTable($model)) {
                        try {
                            $this->getPhysicalDb()->createCommand("DROP TABLE `{$model->name}`")->execute();
                        } catch (\Throwable $dropError) {
                            Yii::warning('Failed dropping table after manual create failure: ' . $dropError->getMessage(), 'table-builder');
                        }
                    }
                    Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($e));
                }
            } catch (\yii\db\IntegrityException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $model->addError('name', 'A table with this name already exists. Please choose a different name.');
                    Yii::$app->session->setFlash('tableBuilderError', 'A table with this name already exists. Please choose a different name.');
                } else {
                    $model->addError('name', $this->buildFriendlyTableBuilderErrorMessage($e));
                    Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($e));
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($e));
            }
        }

        return $this->render('create', [
            'model' => $model,
            'savedColumns' => $savedColumns,
            'foreignKeyReferenceMap' => $foreignKeyReferenceMap,
            'databaseInfo' => $this->getDatabaseInfo(),
            'builderMode' => $builderMode !== '' ? $builderMode : 'manual',
            'rawSql' => $rawSql,
            'sqlError' => null,
        ]);
    }

    public function actionView($id)
    {
        $this->refreshDbTableColumnsSchema();

        $model = $this->findModel($id);
        if ($this->hasPhysicalTable($model)) {
            try {
                $model = $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
                $this->refreshDbTableColumnsSchema();
            } catch (\Throwable $syncError) {
                Yii::warning('Auto sync from physical database failed on table view: ' . $syncError->getMessage(), 'table-builder-sync');
                Yii::$app->session->setFlash('tableBuilderWarning', 'Sinkronisasi dari database fisik gagal: ' . $this->buildFriendlyTableBuilderErrorMessage($syncError));
            }
        }
        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        $db = $this->getPhysicalDb();
        $tableSchema = null;
        $fkCount = 0;
        foreach ($columns as $column) {
            if ($column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key')) {
                $fkCount++;
            }
        }
        $this->logFkDebug('actionView.columns_loaded', [
            'table_id' => (int)$model->id,
            'table_name' => (string)$model->name,
            'columns_count' => count($columns),
            'fk_count' => $fkCount,
        ]);
        $isCreated = $this->syncTableCreationState($model);

        // Fetch actual data from the database table if created.
        // Do not assume an "id" column exists; many custom tables use a different primary key.
        $tableData = [];
        if ($isCreated) {
            try {
                $tableSchema = $db->schema->getTableSchema($model->name, true);
                if ($tableSchema !== null) {
                    $orderColumn = null;
                    if (isset($tableSchema->columns['id'])) {
                        $orderColumn = 'id';
                    } elseif (!empty($tableSchema->primaryKey)) {
                        $primaryKeyColumn = (string)$tableSchema->primaryKey[0];
                        if (isset($tableSchema->columns[$primaryKeyColumn])) {
                            $orderColumn = $primaryKeyColumn;
                        }
                    }

                    $escapedTableName = str_replace('`', '``', (string)$model->name);
                    $sql = "SELECT * FROM `{$escapedTableName}`";
                    if ($orderColumn !== null) {
                        $escapedOrderColumn = str_replace('`', '``', $orderColumn);
                        $sql .= " ORDER BY `{$escapedOrderColumn}` DESC";
                    }
                    $sql .= ' LIMIT 100';

                    $tableData = $db->createCommand($sql)->queryAll();
                }
            } catch (\Throwable $e) {
                Yii::warning('Failed loading live table data: ' . $e->getMessage(), __METHOD__);
                $tableData = [];
            }
        }
        $spreadsheetContext = $this->buildSpreadsheetContext($model, $columns, $tableSchema, $tableData);
        $liveTableRows = $this->buildLiveTableRows($model, $columns, $tableSchema, $tableData);

        return $this->render('view', [
            'model' => $model,
            'columns' => $columns,
            'tableData' => $tableData,
            'liveTableRows' => $liveTableRows,
            'databaseInfo' => $this->getDatabaseInfo(),
            'spreadsheetContext' => $spreadsheetContext,
        ]);
    }

    public function actionSpreadsheetAction()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $tableId = (int)Yii::$app->request->post('table_id', 0);
        $operation = strtolower(trim((string)Yii::$app->request->post('operation', 'upsert_row')));
        $model = $this->findModel($tableId);

        if (!$this->syncTableCreationState($model, false)) {
            return ['success' => false, 'message' => 'Table ini belum dibuat di database.'];
        }

        $db = $this->getPhysicalDb();
        $tableSchema = $db->schema->getTableSchema($model->name, true);
        if ($tableSchema === null) {
            return ['success' => false, 'message' => 'Schema tabel fisik tidak ditemukan.'];
        }

        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        $columnMap = [];
        foreach ($columns as $column) {
            $columnMap[$column->name] = $column;
        }

        $keyColumns = $this->detectSpreadsheetKeyColumns($tableSchema, $columns);
        $payload = $this->normalizeSpreadsheetPayload(Yii::$app->request->post('row_data', []));
        $rowKey = $this->normalizeSpreadsheetPayload(Yii::$app->request->post('row_key', []));
        $rowKeys = $this->normalizeSpreadsheetPayload(Yii::$app->request->post('row_keys', []));
        $statusValue = Yii::$app->request->post('status_value', null);

        Yii::info([
            'table_id' => $tableId,
            'table_name' => (string)$model->name,
            'operation' => $operation,
            'schema_columns' => array_keys($tableSchema->columns),
            'key_columns' => $keyColumns,
            'raw_row_key' => $rowKey,
            'raw_row_keys' => $rowKeys,
            'raw_payload' => $payload,
            'raw_payload_keys' => array_keys($payload),
        ], 'table-spreadsheet-debug');

        try {
            if ($operation === 'delete_rows') {
                $deleted = $this->deleteSpreadsheetRows($db, $model, $keyColumns, $rowKeys);
                return ['success' => true, 'message' => "Berhasil menghapus {$deleted} baris.", 'deleted' => $deleted];
            }

            if ($operation === 'duplicate_rows') {
                $duplicated = $this->duplicateSpreadsheetRows($db, $model, $keyColumns, $rowKeys, $columnMap);
                return ['success' => true, 'message' => "Berhasil menggandakan {$duplicated} baris.", 'duplicated' => $duplicated];
            }

            if ($operation === 'bulk_paste') {
                $rows = $this->normalizeSpreadsheetPayload(Yii::$app->request->post('rows', []));
                $inserted = $this->bulkPasteSpreadsheetRows($db, $model, $columns, $rows, $keyColumns);
                return ['success' => true, 'message' => "Berhasil menambahkan {$inserted} baris.", 'inserted' => $inserted];
            }

            if ($operation === 'bulk_status') {
                $affected = $this->bulkUpdateSpreadsheetStatus($db, $model, $keyColumns, $rowKeys, $statusValue);
                return ['success' => true, 'message' => "Status diperbarui untuk {$affected} baris.", 'affected' => $affected];
            }

            $result = $this->upsertSpreadsheetRow($db, $model, $columns, $tableSchema, $columnMap, $keyColumns, $payload, $rowKey);
            return $result;
        } catch (\Throwable $e) {
            Yii::error('Spreadsheet action failed: ' . $e->getMessage(), 'table-spreadsheet');
            return ['success' => false, 'message' => $this->buildFriendlyTableBuilderErrorMessage($e)];
        }
    }

    public function actionUpdate($id)
    {
        $this->refreshDbTableColumnsSchema();

        $model = $this->findModel($id);
        $oldTableName = (string)$model->name;
        $wasPhysicallyCreated = $this->syncTableCreationState($model);
        $savedColumns = array_map(static function (DbTableColumn $column) {
            return [
                'name' => $column->name,
                'label' => $column->label,
                'type' => $column->type,
                'length' => $column->length,
                'is_nullable' => (bool)$column->is_nullable,
                'is_primary' => (bool)$column->is_primary,
                'is_unique' => (bool)$column->is_unique,
                'is_auto_increment' => $column->hasAttribute('is_auto_increment') ? (bool)$column->getAttribute('is_auto_increment') : false,
                'is_foreign_key' => $column->hasAttribute('is_foreign_key') ? (bool)$column->getAttribute('is_foreign_key') : false,
                'referenced_table_name' => $column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : null,
                'referenced_column_name' => $column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : null,
                'on_delete_action' => $column->hasAttribute('on_delete_action') ? $column->getAttribute('on_delete_action') : 'RESTRICT',
                'on_update_action' => $column->hasAttribute('on_update_action') ? $column->getAttribute('on_update_action') : 'RESTRICT',
                'referenced_table' => $column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : null,
                'referenced_column' => $column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : null,
                'on_delete' => $column->hasAttribute('on_delete_action') ? $column->getAttribute('on_delete_action') : 'RESTRICT',
                'on_update' => $column->hasAttribute('on_update_action') ? $column->getAttribute('on_update_action') : 'RESTRICT',
                'default_value' => $column->default_value,
                'comment' => $column->comment,
                'enum_values' => $column->hasAttribute('enum_values') ? $column->getAttribute('enum_values') : null,
            ];
        }, $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all());
        $foreignKeyReferenceMap = $this->getForeignKeyReferenceMap();

        if ($model->load(Yii::$app->request->post())) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $model->user_id = $effectiveUserId;
            }
            $this->assignActiveProject($model);
            $columns = Yii::$app->request->post('columns', []);
            // Handle JSON-encoded columns data
            if (is_string($columns)) {
                $columns = json_decode($columns, true) ?: [];
            }
            $savedColumns = $columns;
            $this->logFkDebug('actionUpdate.post_columns', [
                'table_id' => (int)$model->id,
                'columns_count' => count($columns),
                'columns' => $columns,
            ]);
             
            try {
                $this->assertForeignKeyMetadataSupport($columns);

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (!$model->save()) {
                        throw new \RuntimeException('Failed to save table: ' . implode(', ', $model->getErrorSummary(true)));
                    }

                    $columnModels = $this->buildColumnModels($columns, (int)$model->id);

                    if (empty($columnModels)) {
                        throw new \RuntimeException('Please keep at least one valid column on the table.');
                    }

                    $columnErrors = $this->collectColumnErrors($columnModels);
                    if (!empty($columnErrors)) {
                        throw new \RuntimeException(implode('<br>', $columnErrors));
                    }

                    $foreignKeyErrors = $this->validateForeignKeyReferences($columnModels, $model);
                    if (!empty($foreignKeyErrors)) {
                        throw new \RuntimeException(implode('<br>', $foreignKeyErrors));
                    }

                    DbTableColumn::deleteAll(['table_id' => $model->id]);

                    foreach ($columnModels as $column) {
                        if (!$column->save(false)) {
                            throw new \RuntimeException("Failed to save column '{$column->name}'.");
                        }
                    }

                    if ($wasPhysicallyCreated) {
                        $this->syncUpdatedPhysicalTable($model, $oldTableName, $columnModels);
                        $model = $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
                        $this->refreshDbTableColumnsSchema();
                        Yii::$app->session->setFlash('tableBuilderSuccess', "Table updated successfully and synced from database table '{$model->name}'.");
                    } else {
                        $this->setTableLifecycleState($model, 'pending', null, false);
                        Yii::$app->session->setFlash('tableBuilderSuccess', 'Table definition updated as pending metadata.');
                    }

                    $transaction->commit();
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    $friendlyError = $this->buildFriendlyTableBuilderErrorMessage($e);
                    Yii::error('Table update failed before metadata commit: ' . $e->getMessage(), 'table-builder');
                    Yii::$app->session->setFlash('tableBuilderError', $friendlyError);
                }

                if (!$model->hasErrors()) {
                    $model->addError('name', Yii::$app->session->getFlash('tableBuilderError', null, false) ?: 'Table update failed.');
                }
            } catch (\yii\db\IntegrityException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $model->addError('name', 'A table with this name already exists. Please choose a different name.');
                } else {
                    $model->addError('name', $this->buildFriendlyTableBuilderErrorMessage($e));
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($e));
            }
        }

        return $this->render('update', [
            'model' => $model,
            'savedColumns' => $savedColumns,
            'foreignKeyReferenceMap' => $foreignKeyReferenceMap,
            'databaseInfo' => $this->getDatabaseInfo(),
        ]);
    }

    public function actionExecuteSql($id)
    {
        $this->refreshDbTableColumnsSchema();

        $model = $this->findModel($id);

        if ($this->syncTableCreationState($model)) {
            try {
                $this->syncImportedTable((string)$model->name);
                $this->getPhysicalDb()->schema->refresh();
                $this->getPhysicalDb()->schema->refreshTableSchema((string)$model->name);
                Yii::$app->session->setFlash('tableBuilderSuccess', "Tabel '{$model->name}' sudah ada di database aktif dan metadata sudah sinkron.");
            } catch (\Throwable $syncError) {
                Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($syncError));
            }
            return $this->redirect(['view', 'id' => $id]);
        }

        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if (empty($columns)) {
            Yii::$app->session->setFlash('tableBuilderError', 'Table must have at least one column!');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $db = $this->getPhysicalDb();
            if (!$model->validate(['name', 'engine', 'charset', 'collation'])) {
                throw new \RuntimeException(implode(', ', $model->getErrorSummary(true)));
            }

            $foreignKeyErrors = $this->validateForeignKeyReferences($columns, $model);
            if (!empty($foreignKeyErrors)) {
                throw new \RuntimeException(implode('<br>', $foreignKeyErrors));
            }

            $sql = $this->buildCreateTableSql($model, $columns, false);
            $this->logFkDebug('actionExecuteSql.generated_sql', [
                'table_id' => (int)$model->id,
                'table_name' => (string)$model->name,
                'sql' => $sql,
            ]);

            $createdPhysicalTable = false;
            $db->createCommand("SET FOREIGN_KEY_CHECKS = 0")->execute();
            try {
                $db->createCommand($sql)->execute();
                $createdPhysicalTable = true;
                $this->addForeignKeyConstraints($model, $columns);
            } catch (\Throwable $createError) {
                if ($createdPhysicalTable && $this->hasPhysicalTable($model)) {
                    try {
                        $db->createCommand("DROP TABLE `{$model->name}`")->execute();
                    } catch (\Throwable $dropError) {
                        Yii::error('Failed dropping table after FK creation failure: ' . $dropError->getMessage(), 'app');
                    }
                }
                throw $createError;
            } finally {
                $db->createCommand("SET FOREIGN_KEY_CHECKS = 1")->execute();
            }

            if (!$this->hasPhysicalTable($model)) {
                throw new \RuntimeException("Table '{$model->name}' was not found after SQL execution.");
            }

            $model = $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
            $this->refreshDbTableColumnsSchema();

            $dbName = $db->createCommand('SELECT DATABASE()')->queryScalar();
            Yii::$app->session->setFlash('tableBuilderSuccess', "Table '{$model->name}' created successfully in database '{$dbName}'.");
            
        } catch (\Exception $e) {
            $this->setTableLifecycleState($model, 'failed', $this->buildFriendlyTableBuilderErrorMessage($e));
            Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($e));
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            $this->getPhysicalDb()->createCommand("DROP TABLE IF EXISTS `{$model->name}`")->execute();
        } catch (\Exception $e) {
            Yii::warning('Failed dropping physical table during metadata cleanup: ' . $e->getMessage(), 'table-builder');
        }
        
        $model->delete();
        Yii::$app->session->setFlash('tableBuilderSuccess', 'Table deleted successfully!');

        return $this->redirect(['index']);
    }

    public function actionSyncFromDatabase($id = null)
    {
        try {
            if ($id !== null) {
                $model = $this->findModel((int)$id);
                $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
                Yii::$app->session->setFlash('tableBuilderSuccess', "Metadata '{$model->name}' berhasil disinkronkan dari database fisik.");
                return $this->redirect(['view', 'id' => (int)$id]);
            }

            $result = $this->getDatabaseSchemaSyncService()->syncAllPhysicalTables();
            Yii::$app->session->setFlash('tableBuilderSuccess', 'Sinkronisasi dari database fisik selesai. Table tersinkron: ' . (int)($result['count'] ?? 0) . '.');
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('tableBuilderError', $this->buildFriendlyTableBuilderErrorMessage($e));
        }

        return $this->redirect(['index']);
    }

    public function actionPreviewSql($id)
    {
        $this->refreshDbTableColumnsSchema();

        $model = $this->findModel($id);
        $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if (empty($columns)) {
            return $this->asJson(['sql' => '-- No columns defined']);
        }

        $sql = $this->buildCreateTableSql($model, $columns);
        $this->logFkDebug('actionPreviewSql.generated_sql', [
            'table_id' => (int)$model->id,
            'table_name' => (string)$model->name,
            'sql' => $sql,
        ]);

        return $this->asJson(['sql' => $sql]);
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @param array<int, array<string, mixed>> $tableData
     */
    private function buildSpreadsheetContext(DbTable $model, array $columns, ?\yii\db\TableSchema $tableSchema, array $tableData): array
    {
        $roleOptions = strtolower((string)$model->name) === 'users' ? $this->loadUsersRoleOptions() : [];
        $columnConfigs = strtolower((string)$model->name) === 'users'
            ? $this->buildUsersSpreadsheetColumnConfigs($columns, $roleOptions)
            : $this->buildGenericSpreadsheetColumnConfigs($model, $columns);

        $keyColumns = $this->detectSpreadsheetKeyColumns($tableSchema, $columns);
        $rows = [];
        foreach ($tableData as $rowIndex => $row) {
            $rows[] = $this->buildSpreadsheetRow($model, $row, $keyColumns, $columns, $rowIndex);
        }

        return [
            'columns' => $columnConfigs,
            'rows' => $rows,
            'keyColumns' => $keyColumns,
            'roleOptions' => $roleOptions,
            'hasKeyColumns' => !empty($keyColumns),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $tableData
     * @param array<int, DbTableColumn> $columns
     * @return array<int, array<string, mixed>>
     */
    private function buildLiveTableRows(DbTable $model, array $columns, ?\yii\db\TableSchema $tableSchema, array $tableData): array
    {
        $keyColumns = $this->detectSpreadsheetKeyColumns($tableSchema, $columns);
        $rows = [];
        foreach ($tableData as $rowIndex => $row) {
            $key = [];
            foreach ($keyColumns as $keyColumn) {
                $key[$keyColumn] = $row[$keyColumn] ?? null;
            }
            $rows[] = [
                'index' => $rowIndex,
                'key' => $key,
                'values' => $row,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $roleOptions
     */
    /**
     * @param array<int, array<string, mixed>> $roleOptions
     * @return array<int, array<string, mixed>>
     */
    private function buildUsersSpreadsheetColumnConfigs(array $columns, array $roleOptions): array
    {
        $configs = [];
        foreach ($columns as $column) {
            $name = strtolower((string)$column->name);
            if (in_array($name, ['id', 'password_hash', 'created_at', 'updated_at', 'must_change_password'], true)) {
                continue;
            }

            if ($name === 'password') {
                continue;
            }

            if ($name === 'role') {
                $configs[] = [
                    'name' => 'role',
                    'label' => 'Role',
                    'type' => 'TEXT',
                    'inputType' => 'datalist',
                    'options' => $roleOptions,
                    'isPrimary' => false,
                    'isUnique' => false,
                    'isAutoIncrement' => false,
                    'isForeignKey' => false,
                    'isNullable' => false,
                    'readOnly' => false,
                    'isSystem' => false,
                    'sourceColumn' => 'role',
                ];
                $configs[] = [
                    'name' => 'password',
                    'label' => 'Password',
                    'type' => 'PASSWORD',
                    'inputType' => 'password',
                    'options' => [],
                    'isPrimary' => false,
                    'isUnique' => false,
                    'isAutoIncrement' => false,
                    'isForeignKey' => false,
                    'isNullable' => true,
                    'readOnly' => false,
                    'isSystem' => false,
                    'sourceColumn' => 'password_hash',
                ];
                continue;
            }

            $configs[] = $this->buildSpreadsheetColumnConfig($column);
        }

        $hasStatus = false;
        foreach ($configs as $config) {
            if (($config['name'] ?? '') === 'status') {
                $hasStatus = true;
                break;
            }
        }
        if (!$hasStatus) {
            $configs[] = [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'BOOLEAN',
                'inputType' => 'boolean',
                'options' => [],
                'isPrimary' => false,
                'isUnique' => false,
                'isAutoIncrement' => false,
                'isForeignKey' => false,
                'isNullable' => false,
                'readOnly' => false,
                'isSystem' => false,
                'sourceColumn' => 'status',
            ];
        }

        return $configs;
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @return array<int, array<string, mixed>>
     */
    private function buildGenericSpreadsheetColumnConfigs(DbTable $model, array $columns): array
    {
        $configs = [];
        $tableSchema = Yii::$app->db->schema->getTableSchema((string)$model->name, true);
        $schemaLookup = $tableSchema !== null ? $this->buildSpreadsheetSchemaColumnLookup($tableSchema) : [];
        foreach ($columns as $column) {
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }
            $physicalName = (string)$column->name;
            if ($tableSchema !== null) {
                $resolvedName = $this->resolveSpreadsheetSchemaColumnName((string)$column->name, $tableSchema, $schemaLookup);
                if ($resolvedName === null || !isset($tableSchema->columns[$resolvedName])) {
                    Yii::warning([
                        'table_name' => (string)$model->name,
                        'metadata_column' => (string)$column->name,
                        'reason' => 'spreadsheet_column_not_in_physical_schema',
                        'schema_columns' => array_keys($tableSchema->columns),
                    ], 'table-spreadsheet-debug');
                    continue;
                }
                $physicalName = $resolvedName;
            }
            $configs[] = $this->buildSpreadsheetColumnConfig($column, $physicalName);
        }

        return $configs;
    }

    private function buildSpreadsheetColumnConfig(DbTableColumn $column, ?string $physicalName = null): array
    {
        $physicalName = $physicalName !== null && $physicalName !== '' ? $physicalName : (string)$column->name;
        $type = strtoupper((string)$column->type);
        $isPrimary = (bool)$column->is_primary;
        $isUnique = (bool)$column->is_unique;
        $isAutoIncrement = $column->hasAttribute('is_auto_increment') ? (bool)$column->getAttribute('is_auto_increment') : false;
        $isForeignKey = $column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key');
        $inputType = 'text';
        $options = [];

        if ($isForeignKey) {
            $inputType = 'select';
            $options = $this->loadForeignKeyOptions($column);
        } elseif (in_array($type, ['ENUM', 'SET'], true)) {
            $inputType = 'select';
            $options = $this->parseEnumOptions((string)$column->getAttribute('enum_values'));
        } elseif (
            in_array($type, ['BOOLEAN', 'BOOL', 'BIT', 'TINYINT'], true)
            && ((int)$column->length <= 1 || in_array($type, ['BOOLEAN', 'BOOL'], true))
        ) {
            $inputType = 'boolean';
        } elseif (in_array($type, ['DATE'], true)) {
            $inputType = 'date';
        } elseif (in_array($type, ['DATETIME', 'TIMESTAMP'], true)) {
            $inputType = 'datetime';
        } elseif (in_array($type, ['INT', 'BIGINT', 'SMALLINT', 'MEDIUMINT', 'DECIMAL', 'FLOAT', 'DOUBLE', 'REAL', 'SERIAL'], true)) {
            $inputType = 'number';
        } elseif (in_array($type, ['JSON'], true)) {
            $inputType = 'textarea';
        }

        return [
            'name' => $physicalName,
            'field_key' => $physicalName,
            'field_name' => $physicalName,
            'column_name' => $physicalName,
            'resolved_name' => $physicalName,
            'resolved_column_name' => $physicalName,
            'metadata_column_name' => $column->name,
            'label' => $column->label ?: $column->name,
            'resolved_label' => $column->label ?: $column->name,
            'type' => $type,
            'length' => $column->length,
            'isPrimary' => $isPrimary,
            'isUnique' => $isUnique,
            'isAutoIncrement' => $isAutoIncrement,
            'isForeignKey' => $isForeignKey,
            'is_foreign_key' => $isForeignKey,
            'isNullable' => (bool)$column->is_nullable,
            'inputType' => $inputType,
            'options' => $options,
            'relation_config' => $isForeignKey ? [
                'target_table' => (string)($column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : ''),
                'source_column' => $physicalName,
                'local_column' => $physicalName,
                'referenced_table' => (string)($column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : ''),
                'referenced_column' => (string)($column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : ''),
                'value_column' => (string)($column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : ''),
                'display_column' => $this->guessLabelColumnForForeignKey($column),
            ] : [],
            'source_column' => $physicalName,
            'source_column_name' => $physicalName,
            'source_column_label' => $column->label ?: $column->name,
            'source_column_type' => $type,
            'option_value' => $isForeignKey ? (string)($column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : '') : $column->name,
            'option_label' => $isForeignKey ? $this->guessLabelColumnForForeignKey($column) : ($column->label ?: $column->name),
            'readOnly' => SystemFieldService::shouldBeReadonlyInGrid($column),
            'isSystem' => SystemFieldService::isSystemManagedField($column),
            'sourceColumn' => $column->name,
        ];
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @return array<int, string>
     */
    private function detectSpreadsheetKeyColumns(?\yii\db\TableSchema $tableSchema, array $columns): array
    {
        $keyColumns = [];
        if ($tableSchema !== null && !empty($tableSchema->primaryKey)) {
            foreach ($tableSchema->primaryKey as $primaryKeyColumn) {
                $keyColumns[] = (string)$primaryKeyColumn;
            }
        }

        if (empty($keyColumns)) {
            foreach ($columns as $column) {
                if ((bool)$column->is_primary || (bool)$column->is_unique) {
                    $keyColumns[] = (string)$column->name;
                }
            }
        }

        return array_values(array_unique(array_filter($keyColumns)));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $keyColumns
     * @param array<int, DbTableColumn> $columns
     */
    private function buildSpreadsheetRow(DbTable $model, array $row, array $keyColumns, array $columns, int $rowIndex): array
    {
        $key = [];
        foreach ($keyColumns as $keyColumn) {
            $key[$keyColumn] = $row[$keyColumn] ?? null;
        }

        $values = [];
        if (strtolower((string)$model->name) === 'users') {
            $values = $this->buildUsersSpreadsheetRowValues($row);
        } else {
            $tableSchema = Yii::$app->db->schema->getTableSchema((string)$model->name, true);
            $schemaLookup = $tableSchema !== null ? $this->buildSpreadsheetSchemaColumnLookup($tableSchema) : [];
            foreach ($columns as $column) {
                if (SystemFieldService::shouldHideFromForm($column)) {
                    continue;
                }
                $physicalName = (string)$column->name;
                if ($tableSchema !== null) {
                    $resolvedName = $this->resolveSpreadsheetSchemaColumnName((string)$column->name, $tableSchema, $schemaLookup);
                    if ($resolvedName === null) {
                        continue;
                    }
                    $physicalName = $resolvedName;
                }
                $values[$physicalName] = $row[$physicalName] ?? ($row[$column->name] ?? null);
            }
        }

        return [
            'index' => $rowIndex,
            'key' => $key,
            'values' => $values,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function buildUsersSpreadsheetRowValues(array $row): array
    {
        return [
            'name' => $row['name'] ?? '',
            'username' => $row['username'] ?? '',
            'email' => $row['email'] ?? '',
            'role' => $row['role'] ?? '',
            'status' => $row['status'] ?? 1,
            'password' => '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadUsersRoleOptions(): array
    {
        $rows = (new \yii\db\Query())
            ->select(['role'])
            ->from('users')
            ->where(['and', ['not', ['role' => null]], ['<>', 'role', '']])
            ->groupBy(['role'])
            ->orderBy(['role' => SORT_ASC])
            ->all($this->getPhysicalDb());

        $options = [];
        foreach ($rows as $row) {
            $role = strtolower(trim((string)($row['role'] ?? '')));
            if ($role === '' || in_array($role, ['super_admin', 'superadmin'], true)) {
                continue;
            }

            $options[] = [
                'value' => $role,
                'label' => ucfirst(str_replace(['_', '-'], ' ', $role)),
            ];
        }

        return $options;
    }

    private function generateSpreadsheetTempPassword(int $length = 12): string
    {
        $raw = bin2hex(random_bytes(8));
        return substr($raw, 0, max(8, $length));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseEnumOptions(?string $enumValues): array
    {
        $enumValues = trim((string)$enumValues);
        if ($enumValues === '') {
            return [];
        }

        $values = array_filter(array_map('trim', preg_split('/\s*,\s*/', trim($enumValues, " \t\n\r\0\x0B'\"")) ?: []));
        $options = [];
        foreach ($values as $value) {
            $options[] = [
                'value' => $value,
                'label' => $value,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadForeignKeyOptions(DbTableColumn $column): array
    {
        $referencedTable = strtolower(trim((string)($column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : '')));
        $referencedColumn = strtolower(trim((string)($column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : '')));
        if ($referencedTable === '') {
            return [];
        }

        $db = $this->getPhysicalDb();
        $schema = $db->schema->getTableSchema($referencedTable, true);
        if ($schema === null) {
            return [];
        }

        $valueColumn = $referencedColumn !== '' && isset($schema->columns[$referencedColumn])
            ? $referencedColumn
            : ($schema->primaryKey[0] ?? array_key_first($schema->columns));
        if ($valueColumn === null || $valueColumn === '') {
            return [];
        }

        $labelColumn = $this->guessLabelColumn($schema, (string)$valueColumn);
        $rows = (new \yii\db\Query())
            ->from($referencedTable)
            ->select(array_values(array_unique(array_filter([$valueColumn, $labelColumn]))))
            ->limit(200)
            ->all($db);

        $options = [];
        foreach ($rows as $row) {
            $value = $row[$valueColumn] ?? null;
            $label = $labelColumn !== '' ? ($row[$labelColumn] ?? $value) : $value;
            $options[] = [
                'value' => $value,
                'label' => trim((string)$label) !== '' ? (string)$label : (string)$value,
            ];
        }

        return $options;
    }

    private function resolveForeignKeySchema(DbTableColumn $column): ?\yii\db\TableSchema
    {
        $referencedTable = strtolower(trim((string)($column->hasAttribute('referenced_table_name') ? $column->getAttribute('referenced_table_name') : '')));
        if ($referencedTable === '') {
            return null;
        }

        try {
            return $this->getPhysicalDb()->schema->getTableSchema($referencedTable, true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function guessLabelColumnForForeignKey(DbTableColumn $column): string
    {
        $schema = $this->resolveForeignKeySchema($column);
        $referencedColumn = strtolower(trim((string)($column->hasAttribute('referenced_column_name') ? $column->getAttribute('referenced_column_name') : '')));
        if ($schema === null) {
            return $referencedColumn !== '' ? $referencedColumn : (string)$column->name;
        }

        if ($referencedColumn !== '' && isset($schema->columns[$referencedColumn])) {
            return $this->guessLabelColumn($schema, $referencedColumn);
        }

        $primaryKey = !empty($schema->primaryKey) ? (string)$schema->primaryKey[0] : (string)array_key_first($schema->columns);
        return $this->guessLabelColumn($schema, $primaryKey);
    }

    private function guessLabelColumn(\yii\db\TableSchema $schema, string $valueColumn): string
    {
        foreach (['name', 'title', 'label', 'slug', 'username', 'email', 'form_name', 'table_name'] as $candidate) {
            if (isset($schema->columns[$candidate]) && $candidate !== $valueColumn) {
                return $candidate;
            }
        }

        return $valueColumn;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeSpreadsheetPayload($payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @return string
     */
    private function normalizeSpreadsheetColumnKey($value): string
    {
        return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', (string)$value), '_'));
    }

    /**
     * @return array<string, string>
     */
    private function buildSpreadsheetSchemaColumnLookup(\yii\db\TableSchema $tableSchema): array
    {
        $lookup = [];
        foreach ($tableSchema->columns as $columnName => $column) {
            $columnName = (string)$columnName;
            foreach (array_filter([
                $columnName,
                $this->normalizeSpreadsheetColumnKey($columnName),
                ucwords(str_replace('_', ' ', $columnName)),
                $column->comment ?? null,
            ]) as $alias) {
                $normalized = $this->normalizeSpreadsheetColumnKey($alias);
                if ($normalized !== '' && !isset($lookup[$normalized])) {
                    $lookup[$normalized] = $columnName;
                }
            }
        }

        return $lookup;
    }

    private function resolveSpreadsheetSchemaColumnName(string $columnName, \yii\db\TableSchema $tableSchema, array $schemaLookup): ?string
    {
        if (isset($tableSchema->columns[$columnName])) {
            return $columnName;
        }

        $normalizedColumnName = $this->normalizeSpreadsheetColumnKey($columnName);
        if ($normalizedColumnName === '') {
            return null;
        }

        if (isset($schemaLookup[$normalizedColumnName])) {
            return $schemaLookup[$normalizedColumnName];
        }

        $candidateTokens = array_values(array_filter(explode('_', $normalizedColumnName)));
        $bestColumn = null;
        $bestScore = 0.0;
        foreach ($schemaLookup as $schemaAlias => $schemaColumnName) {
            $normalizedAlias = $this->normalizeSpreadsheetColumnKey($schemaAlias);
            if ($normalizedAlias === '') {
                continue;
            }

            $score = 0.0;
            if ($normalizedAlias === $normalizedColumnName) {
                $score = 100.0;
            } elseif (
                $normalizedAlias !== 'id'
                && $normalizedColumnName !== 'id'
                && (str_contains($normalizedAlias, $normalizedColumnName) || str_contains($normalizedColumnName, $normalizedAlias))
            ) {
                $score = 80.0;
            } else {
                $aliasTokens = array_values(array_filter(explode('_', $normalizedAlias)));
                if (!empty($candidateTokens) && !empty($aliasTokens)) {
                    sort($candidateTokens);
                    sort($aliasTokens);
                    if ($candidateTokens === $aliasTokens) {
                        $score = 95.0;
                    } else {
                        $intersection = array_intersect($candidateTokens, $aliasTokens);
                        $union = array_unique(array_merge($candidateTokens, $aliasTokens));
                        if (!empty($union)) {
                            $score = (count($intersection) / count($union)) * 70.0;
                        }
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestColumn = $schemaColumnName;
            }
        }

        return $bestScore >= 70.0 ? $bestColumn : null;
    }

    /**
     * @return array<int, string>
     */
    private function spreadsheetColumnAliases(DbTableColumn $column): array
    {
        $aliases = [];
        foreach ([
            $column->name,
            $column->label,
        ] as $candidate) {
            $normalized = $this->normalizeSpreadsheetColumnKey($candidate);
            if ($normalized !== '' && !in_array($normalized, $aliases, true)) {
                $aliases[] = $normalized;
            }
        }

        return $aliases;
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @return array<string, DbTableColumn>
     */
    private function buildSpreadsheetColumnLookup(array $columns): array
    {
        $lookup = [];
        foreach ($columns as $column) {
            foreach ($this->spreadsheetColumnAliases($column) as $alias) {
                $lookup[$alias] = $column;
            }
        }

        return $lookup;
    }

    /**
     * @param array<string, mixed> $payload
     * @return mixed
     */
    private function resolveSpreadsheetPayloadValue(array $payload, DbTableColumn $column)
    {
        $lookupKeys = $this->spreadsheetColumnAliases($column);
        $lookupKeys[] = $column->name;
        $lookupKeys[] = $column->label;

        foreach ($lookupKeys as $candidate) {
            $normalized = $this->normalizeSpreadsheetColumnKey($candidate);
            if ($normalized !== '' && array_key_exists($normalized, $payload)) {
                return $payload[$normalized];
            }
            if ($candidate !== '' && array_key_exists($candidate, $payload)) {
                return $payload[$candidate];
            }
        }

        foreach ($payload as $payloadKey => $value) {
            if (!is_string($payloadKey)) {
                continue;
            }
            if ($this->normalizeSpreadsheetColumnKey($payloadKey) === $this->normalizeSpreadsheetColumnKey($column->name)) {
                return $value;
            }
            if ($this->normalizeSpreadsheetColumnKey($payloadKey) === $this->normalizeSpreadsheetColumnKey($column->label)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, DbTableColumn> $columns
     * @return array<string, mixed>
     */
    private function normalizeSpreadsheetPayloadForColumns(array $payload, array $columns): array
    {
        $normalized = [];
        $lookup = $this->buildSpreadsheetColumnLookup($columns);

        foreach ($columns as $column) {
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }

            $canonicalName = (string)$column->name;
            $candidates = array_values(array_unique(array_filter([
                $canonicalName,
                (string)$column->label,
                $this->resolveSpreadsheetPayloadAlias($column, 'field_key'),
                $this->resolveSpreadsheetPayloadAlias($column, 'field_name'),
                $this->resolveSpreadsheetPayloadAlias($column, 'column_name'),
                $this->resolveSpreadsheetPayloadAlias($column, 'resolved_name'),
                $this->resolveSpreadsheetPayloadAlias($column, 'resolved_column_name'),
                $this->resolveSpreadsheetPayloadAlias($column, 'source_column'),
                $this->resolveSpreadsheetPayloadAlias($column, 'source_column_name'),
                $this->resolveSpreadsheetPayloadAlias($column, 'original_column'),
                $this->resolveSpreadsheetPayloadAlias($column, 'local_column'),
            ])));

            foreach ($candidates as $candidate) {
                $normalizedCandidate = $this->normalizeSpreadsheetColumnKey($candidate);
                if ($normalizedCandidate === '') {
                    continue;
                }

                if (array_key_exists($candidate, $payload)) {
                    $normalized[$canonicalName] = $payload[$candidate];
                    continue 2;
                }

                if (array_key_exists($normalizedCandidate, $payload)) {
                    $normalized[$canonicalName] = $payload[$normalizedCandidate];
                    continue 2;
                }
            }

            foreach ($payload as $payloadKey => $value) {
                if (!is_string($payloadKey)) {
                    continue;
                }
                $normalizedKey = $this->normalizeSpreadsheetColumnKey($payloadKey);
                if ($normalizedKey !== '' && isset($lookup[$normalizedKey]) && $lookup[$normalizedKey]->name === $canonicalName) {
                    $normalized[$canonicalName] = $value;
                    break;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param DbTableColumn $column
     * @param string $key
     * @return string
     */
    private function resolveSpreadsheetPayloadAlias(DbTableColumn $column, string $key): string
    {
        if (!$column->hasAttribute($key)) {
            return '';
        }

        $value = $column->getAttribute($key);
        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, DbTableColumn> $columns
     * @return array<string, mixed>
     */
    private function buildSpreadsheetRowDataFromPayload(array $payload, array $columns): array
    {
        $payload = $this->normalizeSpreadsheetPayloadForColumns($payload, $columns);
        $rowData = [];
        foreach ($columns as $column) {
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }

            $columnName = (string)$column->name;
            $hasValue = false;
            foreach ($this->spreadsheetColumnAliases($column) as $alias) {
                if (array_key_exists($alias, $payload)) {
                    $hasValue = true;
                    break;
                }
            }

            if (!$hasValue) {
                foreach ($payload as $payloadKey => $_value) {
                    if (!is_string($payloadKey)) {
                        continue;
                    }
                    if ($this->normalizeSpreadsheetColumnKey($payloadKey) === $this->normalizeSpreadsheetColumnKey($columnName)) {
                        $hasValue = true;
                        break;
                    }
                }
            }

            if (!$hasValue) {
                continue;
            }

            $rowData[$columnName] = $this->normalizeSpreadsheetCellValue($column, $this->resolveSpreadsheetPayloadValue($payload, $column));
        }

        return $rowData;
    }

    /**
     * @param array<string, mixed> $rowData
     * @return array<string, mixed>
     */
    private function filterSpreadsheetRowDataBySchema(array $rowData, \yii\db\TableSchema $tableSchema): array
    {
        $filtered = [];
        $schemaLookup = $this->buildSpreadsheetSchemaColumnLookup($tableSchema);
        foreach ($rowData as $columnName => $value) {
            if (!is_string($columnName)) {
                continue;
            }
            $resolvedColumnName = $this->resolveSpreadsheetSchemaColumnName($columnName, $tableSchema, $schemaLookup);
            if ($resolvedColumnName === null || !isset($tableSchema->columns[$resolvedColumnName])) {
                continue;
            }
            $filtered[$resolvedColumnName] = $value;
        }

        return $filtered;
    }

    /**
     * @param DbTableColumn|null $column
     * @param mixed $value
     * @return mixed
     */
    private function normalizeSpreadsheetCellValue(?DbTableColumn $column, $value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if ($column === null) {
            return is_string($value) ? trim($value) : $value;
        }

        $type = strtoupper((string)$column->type);
        if (in_array($type, ['BOOLEAN', 'TINYINT'], true) && ((int)$column->length <= 1 || $column->type === 'BOOLEAN')) {
            return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on', 'aktif', 'active'], true) ? 1 : 0;
        }

        if (in_array($type, ['INT', 'BIGINT', 'SMALLINT', 'MEDIUMINT', 'SERIAL'], true)) {
            return is_numeric($value) ? (int)$value : null;
        }

        if (in_array($type, ['DECIMAL', 'FLOAT', 'DOUBLE', 'REAL'], true)) {
            return is_numeric($value) ? $value + 0 : null;
        }

        if (in_array($type, ['DATE', 'DATETIME', 'TIMESTAMP'], true)) {
            return trim((string)$value);
        }

        if (in_array($type, ['JSON'], true)) {
            if (is_array($value)) {
                return json_encode($value);
            }
            return trim((string)$value);
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * @param array<int, string> $keyColumns
     * @param array<string, DbTableColumn> $columnMap
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $rowKey
     */
    private function upsertSpreadsheetRow(\yii\db\Connection $db, DbTable $model, array $columns, \yii\db\TableSchema $tableSchema, array $columnMap, array $keyColumns, array $payload, array $rowKey): array
    {
        $isUsersTable = strtolower((string)$model->name) === 'users';
        $rawPayload = $payload;
        $rawRowKey = $rowKey;
        $where = [];
        foreach ($keyColumns as $keyColumn) {
            $keyValue = $rowKey[$keyColumn] ?? ($payload[$keyColumn] ?? null);
            if ($keyValue !== null && $keyValue !== '') {
                $where[$keyColumn] = $this->normalizeSpreadsheetCellValue($columnMap[$keyColumn] ?? null, $keyValue);
            }
        }

        $rowData = $isUsersTable
            ? $this->buildUsersSpreadsheetRowData($payload, $columns, empty($where))
            : $this->buildGenericSpreadsheetRowData($payload, $columns);

        if (!empty($rowData)) {
            $rowData = $this->filterSpreadsheetRowDataBySchema($rowData, $tableSchema);
        }

        $beforeRow = !empty($where) ? (new \yii\db\Query())->from($model->name)->where($where)->one($db) : null;
        $rejectedFields = array_values(array_diff(array_keys($rawPayload), array_keys($rowData)));
        $fkColumnsInPayload = [];
        $fkColumnsInRowData = [];
        foreach ($columns as $col) {
            if ($col->hasAttribute('is_foreign_key') && (bool)$col->getAttribute('is_foreign_key')) {
                $colName = (string)$col->name;
                $fkColumnsInPayload[$colName] = array_key_exists($colName, $rawPayload) ? $rawPayload[$colName] : '__missing__';
                $fkColumnsInRowData[$colName] = array_key_exists($colName, $rowData) ? $rowData[$colName] : '__missing__';
            }
        }
        Yii::info([
            'table_name' => (string)$model->name,
            'operation' => empty($where) ? 'insert' : 'update',
            'row_key' => $rawRowKey,
            'resolved_where' => $where,
            'raw_payload' => $rawPayload,
            'normalized_row_data' => $rowData,
            'rejected_fields' => $rejectedFields,
            'fk_payload_keys' => $fkColumnsInPayload,
            'fk_rowdata_keys' => $fkColumnsInRowData,
            'before_row' => $beforeRow,
            'schema_columns' => array_keys($tableSchema->columns),
        ], 'table-spreadsheet-debug');

        if (empty($where)) {
            $validation = $isUsersTable
                ? $this->validateUsersSpreadsheetInsertData($rowData)
                : $this->validateSpreadsheetInsertData($tableSchema, $rowData);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'code' => 'incomplete_row',
                    'message' => 'Belum lengkap',
                    'missing_fields' => $validation['missing_fields'],
                ];
            }

            $lengthErrors = $this->validateSpreadsheetRowLengths($tableSchema, $rowData);
            if (!empty($lengthErrors)) {
                return [
                    'success' => false,
                    'code' => 'invalid_length',
                    'message' => implode(' ', $lengthErrors),
                ];
            }

            if (!$isUsersTable) {
                $manualPrimaryValues = [];
                foreach ($tableSchema->primaryKey ?? [] as $primaryKeyColumn) {
                    $primaryKeyColumn = (string)$primaryKeyColumn;
                    if (isset($tableSchema->columns[$primaryKeyColumn]) && empty($tableSchema->columns[$primaryKeyColumn]->autoIncrement) && array_key_exists($primaryKeyColumn, $rowData)) {
                        $manualPrimaryValues[$primaryKeyColumn] = $rowData[$primaryKeyColumn];
                    }
                }
                $rowData = SystemFieldService::applyCreateValues($rowData, $tableSchema->columns);
                foreach ($manualPrimaryValues as $primaryKeyColumn => $primaryValue) {
                    $rowData[$primaryKeyColumn] = $primaryValue;
                }
                $rowData = $this->filterSpreadsheetRowDataBySchema($rowData, $tableSchema);
                foreach ($rowData as $columnName => $value) {
                    if ($value === null) {
                        unset($rowData[$columnName]);
                    }
                }
            }

            if (empty($rowData)) {
                return [
                    'success' => false,
                    'code' => 'incomplete_row',
                    'message' => 'Belum lengkap',
                    'missing_fields' => [],
                ];
            }

            $insertResult = $db->createCommand()->insert($model->name, $rowData)->execute();
            $insertId = $db->getLastInsertID();
            $afterRow = !empty($tableSchema->primaryKey) && $insertId !== false && $insertId !== null
                ? (new \yii\db\Query())->from($model->name)->where([$tableSchema->primaryKey[0] => $insertId])->one($db)
                : null;
            Yii::info([
                'table_name' => (string)$model->name,
                'sql_result' => $insertResult,
                'insert_id' => $insertId,
                'final_row_data' => $rowData,
                'after_row' => $afterRow,
            ], 'table-spreadsheet-debug');
            return [
                'success' => true,
                'message' => 'Baris berhasil disimpan.',
                'operation' => 'insert',
                'row_key' => $this->buildRowKeyFromInsert($tableSchema, $keyColumns, $rowData, $insertId),
                'row_data' => $rowData,
            ];
        }

        if ($isUsersTable) {
            $validation = $this->validateUsersSpreadsheetUpdateData($rowData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'code' => 'incomplete_row',
                    'message' => 'Belum lengkap',
                    'missing_fields' => $validation['missing_fields'],
                ];
            }
        }

        $lengthErrors = $this->validateSpreadsheetRowLengths($tableSchema, $rowData);
        if (!empty($lengthErrors)) {
            return [
                'success' => false,
                'code' => 'invalid_length',
                'message' => implode(' ', $lengthErrors),
            ];
        }

        $rowData = SystemFieldService::applyUpdateValues($rowData, $tableSchema->columns);
        $rowData = $this->filterSpreadsheetRowDataBySchema($rowData, $tableSchema);
        if (empty($rowData)) {
            Yii::warning([
                'table_name' => (string)$model->name,
                'operation' => 'update',
                'row_key' => $rawRowKey,
                'raw_payload' => $rawPayload,
                'rejected_fields' => array_keys($rawPayload),
                'schema_columns' => array_keys($tableSchema->columns),
            ], 'table-spreadsheet-debug');
            return [
                'success' => false,
                'code' => 'empty_update_payload',
                'message' => 'Tidak ada field valid untuk disimpan.',
            ];
        }
        $updateCommand = $db->createCommand()->update($model->name, $rowData, $where);
        $updateSql = $updateCommand->getRawSql();
        $updateResult = $updateCommand->execute();
        $afterRow = (new \yii\db\Query())->from($model->name)->where($where)->one($db);
        $mismatchedFields = [];
        if (!is_array($afterRow) || empty($afterRow)) {
            $mismatchedFields[] = '__row_not_found__';
        } else {
            foreach ($rowData as $columnName => $expectedValue) {
                if (!array_key_exists($columnName, $afterRow)) {
                    $mismatchedFields[$columnName] = [
                        'expected' => $expectedValue,
                        'actual' => null,
                        'reason' => 'missing_after_column',
                    ];
                    continue;
                }
                if (!$this->spreadsheetValuesEqual($expectedValue, $afterRow[$columnName])) {
                    $mismatchedFields[$columnName] = [
                        'expected' => $expectedValue,
                        'actual' => $afterRow[$columnName],
                    ];
                }
            }
        }
        Yii::info([
            'table_name' => (string)$model->name,
            'sql_result' => $updateResult,
            'sql_update' => $updateSql,
            'affected_rows' => $updateResult,
            'final_row_data' => $rowData,
            'before_row' => $beforeRow,
            'after_row' => $afterRow,
            'mismatched_fields' => $mismatchedFields,
        ], 'table-spreadsheet-debug');
        if (!empty($mismatchedFields)) {
            return [
                'success' => false,
                'code' => 'db_update_not_applied',
                'message' => 'Update database tidak terkonfirmasi. Nilai setelah update tidak sesuai payload.',
                'row_key' => $where,
                'row_data' => is_array($afterRow) ? $afterRow : [],
                'mismatched_fields' => $mismatchedFields,
                'affected_rows' => $updateResult,
            ];
        }
        return [
            'success' => true,
            'message' => 'Baris berhasil disimpan.',
            'operation' => 'update',
            'row_key' => $where,
            'row_data' => is_array($afterRow) ? $afterRow : $rowData,
            'affected_rows' => $updateResult,
        ];
    }

    private function spreadsheetValuesEqual($expected, $actual): bool
    {
        if ($expected === null || $expected === '') {
            return $actual === null || $actual === '';
        }
        if (is_numeric($expected) && is_numeric($actual)) {
            return (string)(0 + $expected) === (string)(0 + $actual);
        }
        return trim((string)$expected) === trim((string)$actual);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, DbTableColumn> $columns
     * @return array<string, mixed>
     */
    private function buildGenericSpreadsheetRowData(array $payload, array $columns): array
    {
        return $this->buildSpreadsheetRowDataFromPayload($payload, $columns);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, DbTableColumn> $columns
     * @return array<string, mixed>
     */
    private function buildUsersSpreadsheetRowData(array $payload, array $columns, bool $isInsert): array
    {
        $rowData = [];
        $columnLookup = [];
        foreach ($columns as $column) {
            $columnLookup[strtolower((string)$column->name)] = $column;
        }
        $now = date('Y-m-d H:i:s');

        $name = trim((string)($payload['name'] ?? ''));
        $username = trim((string)($payload['username'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $role = trim((string)($payload['role'] ?? ''));
        $status = $payload['status'] ?? 1;
        $password = trim((string)($payload['password'] ?? ''));

        $rowData['name'] = $name !== '' ? $name : ($username !== '' ? $username : null);
        $rowData['username'] = $username !== '' ? $username : null;
        $rowData['email'] = $email !== '' ? $email : ($username !== '' ? $username . '@local' : null);
        $rowData['role'] = $role !== '' ? $role : null;
        $rowData['status'] = $this->normalizeSpreadsheetCellValue($columnLookup['status'] ?? null, $status ?? 1);

        if ($password !== '') {
            $rowData['password_hash'] = Yii::$app->security->generatePasswordHash($password);
            $rowData['must_change_password'] = 0;
        } elseif ($isInsert) {
            $tempPassword = $this->generateSpreadsheetTempPassword();
            $rowData['password_hash'] = Yii::$app->security->generatePasswordHash($tempPassword);
            $rowData['must_change_password'] = 1;
        }

        if (isset($columnLookup['created_at']) && ($isInsert || empty($payload['created_at'] ?? null))) {
            $rowData['created_at'] = $now;
        }
        if (isset($columnLookup['updated_at'])) {
            $rowData['updated_at'] = $now;
        }
        if (isset($columnLookup['must_change_password']) && !isset($rowData['must_change_password']) && $isInsert) {
            $rowData['must_change_password'] = 1;
        }

        return $rowData;
    }

    /**
     * @param array<string, mixed> $rowData
     * @return array{valid:bool,missing_fields:array<int,string>}
     */
    private function validateUsersSpreadsheetInsertData(array $rowData): array
    {
        $missing = [];
        foreach (['username', 'role'] as $requiredField) {
            if (trim((string)($rowData[$requiredField] ?? '')) === '') {
                $missing[] = $requiredField;
            }
        }

        return [
            'valid' => empty($missing),
            'missing_fields' => $missing,
        ];
    }

    /**
     * @param array<string, mixed> $rowData
     * @return array{valid:bool,missing_fields:array<int,string>}
     */
    private function validateUsersSpreadsheetUpdateData(array $rowData): array
    {
        return $this->validateUsersSpreadsheetInsertData($rowData);
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @param array<string, mixed> $rowData
     * @return array{valid:bool,missing_fields:array<int,string>}
     */
    private function validateSpreadsheetInsertData(\yii\db\TableSchema $tableSchema, array $rowData): array
    {
        $missing = [];
        $primaryKeys = array_map(static function ($key) {
            return strtolower(trim((string)$key));
        }, (array)($tableSchema->primaryKey ?? []));
        foreach ($tableSchema->columns as $columnName => $schemaColumn) {
            $normalizedColumnName = $this->normalizeSpreadsheetColumnKey($columnName);
            if (!empty($schemaColumn->autoIncrement)) {
                continue;
            }
            if (SystemFieldService::isAuditField($normalizedColumnName)) {
                continue;
            }
            if (in_array($normalizedColumnName, $primaryKeys, true) && (!isset($rowData[$normalizedColumnName]) || $rowData[$normalizedColumnName] === '')) {
                $missing[] = $normalizedColumnName;
                continue;
            }
            $defaultValue = property_exists($schemaColumn, 'defaultValue') ? $schemaColumn->defaultValue : null;
            if ($schemaColumn->allowNull || $defaultValue !== null && $defaultValue !== '') {
                continue;
            }
            $value = $rowData[$normalizedColumnName] ?? null;
            if ($value === null || $value === '') {
                $missing[] = $normalizedColumnName;
            }
        }

        return [
            'valid' => empty($missing),
            'missing_fields' => $missing,
        ];
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @param array<string, mixed> $rowData
     * @return array<int, string>
     */
    private function validateSpreadsheetRowLengths(\yii\db\TableSchema $tableSchema, array $rowData): array
    {
        $errors = [];
        foreach ($tableSchema->columns as $columnName => $schemaColumn) {
            $normalizedColumnName = $this->normalizeSpreadsheetColumnKey($columnName);
            $maxLength = (int)($schemaColumn->size ?? 0);
            $type = strtoupper((string)($schemaColumn->type ?? ''));
            if ($maxLength <= 0 || !in_array($type, ['CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT'], true)) {
                continue;
            }

            $value = $rowData[$normalizedColumnName] ?? null;
            if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
                continue;
            }

            if (mb_strlen(trim((string)$value), 'UTF-8') > $maxLength) {
                $errors[] = "Field {$normalizedColumnName} maksimal hanya boleh {$maxLength} karakter.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param array<string, mixed> $rowData
     * @return array<string, mixed>
     */
    /**
     * @param array<int, string> $keyColumns
     * @param array<string, mixed> $rowData
     */
    private function buildRowKeyFromInsert(\yii\db\TableSchema $tableSchema, array $keyColumns, array $rowData, $insertId): array
    {
        $key = [];
        foreach ($keyColumns as $keyColumn) {
            if (isset($rowData[$keyColumn])) {
                $key[$keyColumn] = $rowData[$keyColumn];
            }
        }

        if (empty($key) && !empty($tableSchema->primaryKey) && $insertId !== false && $insertId !== null) {
            $key[$tableSchema->primaryKey[0]] = $insertId;
        }

        return $key;
    }

    /**
     * @param array<int, string> $keyColumns
     * @param array<int, mixed> $rowKeys
     */
    private function deleteSpreadsheetRows(\yii\db\Connection $db, DbTable $model, array $keyColumns, array $rowKeys): int
    {
        if (empty($keyColumns) || empty($rowKeys)) {
            return 0;
        }

        $deleted = 0;
        foreach ($rowKeys as $rowKey) {
            $criteria = [];
            $keyPayload = $this->normalizeSpreadsheetPayload($rowKey);
            foreach ($keyColumns as $keyColumn) {
                if (!array_key_exists($keyColumn, $keyPayload)) {
                    $criteria = [];
                    break;
                }
                $criteria[$keyColumn] = $keyPayload[$keyColumn];
            }

            if (!empty($criteria)) {
                $deleted += $db->createCommand()->delete($model->name, $criteria)->execute();
            }
        }

        return $deleted;
    }

    /**
     * @param array<int, string> $keyColumns
     * @param array<int, mixed> $rowKeys
     * @param array<string, DbTableColumn> $columnMap
     */
    private function duplicateSpreadsheetRows(\yii\db\Connection $db, DbTable $model, array $keyColumns, array $rowKeys, array $columnMap): int
    {
        if (empty($keyColumns) || empty($rowKeys)) {
            return 0;
        }

        $duplicated = 0;
        foreach ($rowKeys as $rowKey) {
            $criteria = [];
            $keyPayload = $this->normalizeSpreadsheetPayload($rowKey);
            foreach ($keyColumns as $keyColumn) {
                if (!array_key_exists($keyColumn, $keyPayload)) {
                    $criteria = [];
                    break;
                }
                $criteria[$keyColumn] = $keyPayload[$keyColumn];
            }

            if (empty($criteria)) {
                continue;
            }

            $existingRow = (new \yii\db\Query())->from($model->name)->where($criteria)->one($db);
            if (!$existingRow) {
                continue;
            }

            foreach ($keyColumns as $keyColumn) {
                unset($existingRow[$keyColumn]);
            }
            foreach ($columnMap as $columnName => $column) {
                if (SystemFieldService::shouldHideFromForm($column)) {
                    unset($existingRow[$columnName]);
                }
            }
            $tableSchema = $db->schema->getTableSchema($model->name, true);
            if ($tableSchema !== null) {
                $existingRow = SystemFieldService::applyCreateValues($existingRow, $tableSchema->columns);
                $existingRow = $this->filterSpreadsheetRowDataBySchema($existingRow, $tableSchema);
            }

            if (!empty($existingRow)) {
                $db->createCommand()->insert($model->name, $existingRow)->execute();
                $duplicated++;
            }
        }

        return $duplicated;
    }

    /**
     * @param array<int, DbTableColumn> $columns
     * @param array<int, mixed> $rows
     * @param array<int, string> $keyColumns
     */
    private function bulkPasteSpreadsheetRows(\yii\db\Connection $db, DbTable $model, array $columns, array $rows, array $keyColumns): int
    {
        $editableColumns = [];
        foreach ($columns as $column) {
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }
            $editableColumns[] = $column;
        }

        if (empty($editableColumns) || empty($rows)) {
            return 0;
        }

        $tableSchema = $db->schema->getTableSchema($model->name, true);
        if ($tableSchema === null) {
            return 0;
        }

        $columnLookup = $this->buildSpreadsheetColumnLookup($editableColumns);
        $headerMap = [];
        $normalizedRows = $rows;
        if (!empty($normalizedRows) && is_array($normalizedRows[0])) {
            $firstRow = $normalizedRows[0];
            $nonEmptyCells = 0;
            $matchedCells = 0;
            foreach ($firstRow as $index => $cell) {
                if (trim((string)$cell) === '') {
                    continue;
                }
                $nonEmptyCells++;
                $normalizedCell = $this->normalizeSpreadsheetColumnKey($cell);
                if ($normalizedCell !== '' && isset($columnLookup[$normalizedCell])) {
                    $matchedCells++;
                    $headerMap[(int)$index] = (string)$columnLookup[$normalizedCell]->name;
                }
            }

            if ($nonEmptyCells > 0 && $matchedCells === $nonEmptyCells) {
                array_shift($normalizedRows);
            } else {
                $headerMap = [];
            }
        }

        $inserted = 0;
        $isUsersTable = strtolower((string)$model->name) === 'users';
        foreach ($normalizedRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($isUsersTable) {
                $payload = [];
                foreach ($editableColumns as $index => $column) {
                    $sourceColumn = $headerMap[$index] ?? $column->name;
                    $value = $row[$index] ?? $row[$sourceColumn] ?? $row[$column->name] ?? null;
                    $payload[$column->name] = $value;
                    if ($sourceColumn !== $column->name) {
                        $payload[$sourceColumn] = $value;
                    }
                }
                $rowData = $this->buildUsersSpreadsheetRowData($payload, $columns, true);
                $validation = $this->validateUsersSpreadsheetInsertData($rowData);
                if (!$validation['valid']) {
                    continue;
                }
                $rowData = $this->finalizeUsersSpreadsheetInsertDataForBulk($rowData);
            } else {
                $rowData = [];
                foreach ($editableColumns as $index => $column) {
                    $sourceColumn = $headerMap[$index] ?? $column->name;
                    $value = $row[$index] ?? $row[$sourceColumn] ?? $row[$column->name] ?? null;
                    $rowData[$column->name] = $this->normalizeSpreadsheetCellValue($column, $value);
                }

                $rowData = $this->filterSpreadsheetRowDataBySchema($rowData, $tableSchema);
                $validation = $this->validateSpreadsheetInsertData($tableSchema, $rowData);
                if (!$validation['valid']) {
                    continue;
                }
            }

            foreach ($columns as $column) {
                $columnName = (string)$column->name;
                if (($rowData[$columnName] ?? null) === null && !$column->is_nullable && !$column->is_primary) {
                    unset($rowData[$columnName]);
                }
            }

            if (!empty($rowData)) {
                $manualPrimaryValues = [];
                foreach ($tableSchema->primaryKey ?? [] as $primaryKeyColumn) {
                    $primaryKeyColumn = (string)$primaryKeyColumn;
                    if (isset($tableSchema->columns[$primaryKeyColumn]) && empty($tableSchema->columns[$primaryKeyColumn]->autoIncrement) && array_key_exists($primaryKeyColumn, $rowData)) {
                        $manualPrimaryValues[$primaryKeyColumn] = $rowData[$primaryKeyColumn];
                    }
                }
                $rowData = SystemFieldService::applyCreateValues($rowData, $tableSchema->columns);
                foreach ($manualPrimaryValues as $primaryKeyColumn => $primaryValue) {
                    $rowData[$primaryKeyColumn] = $primaryValue;
                }
                $rowData = $this->filterSpreadsheetRowDataBySchema($rowData, $tableSchema);
                $db->createCommand()->insert($model->name, $rowData)->execute();
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * @param array<string, mixed> $rowData
     * @return array<string, mixed>
     */
    private function finalizeUsersSpreadsheetInsertDataForBulk(array $rowData): array
    {
        $now = date('Y-m-d H:i:s');
        if (empty($rowData['name']) && !empty($rowData['username'])) {
            $rowData['name'] = $rowData['username'];
        }
        if (empty($rowData['email']) && !empty($rowData['username'])) {
            $rowData['email'] = $rowData['username'] . '@local';
        }
        if (empty($rowData['status'])) {
            $rowData['status'] = 1;
        }
        if (!isset($rowData['must_change_password'])) {
            $rowData['must_change_password'] = 1;
        }
        if (!isset($rowData['created_at'])) {
            $rowData['created_at'] = $now;
        }
        if (!isset($rowData['updated_at'])) {
            $rowData['updated_at'] = $now;
        }

        return $rowData;
    }

    /**
     * @param array<int, string> $keyColumns
     * @param array<int, mixed> $rowKeys
     */
    private function bulkUpdateSpreadsheetStatus(\yii\db\Connection $db, DbTable $model, array $keyColumns, array $rowKeys, $statusValue): int
    {
        if (empty($keyColumns) || empty($rowKeys)) {
            return 0;
        }

        $statusValue = is_numeric($statusValue) ? (int)$statusValue : (int)in_array(strtolower((string)$statusValue), ['1', 'true', 'yes', 'on', 'active', 'aktif'], true);
        $affected = 0;
        foreach ($rowKeys as $rowKey) {
            $criteria = [];
            $keyPayload = $this->normalizeSpreadsheetPayload($rowKey);
            foreach ($keyColumns as $keyColumn) {
                if (!array_key_exists($keyColumn, $keyPayload)) {
                    $criteria = [];
                    break;
                }
                $criteria[$keyColumn] = $keyPayload[$keyColumn];
            }

            if (!empty($criteria)) {
                $updateData = ['status' => $statusValue];
                $tableSchema = $db->schema->getTableSchema($model->name, true);
                if ($tableSchema !== null) {
                    $updateData = SystemFieldService::applyUpdateValues($updateData, $tableSchema->columns);
                }
                $affected += $db->createCommand()->update($model->name, $updateData, $criteria)->execute();
            }
        }

        return $affected;
    }

    private function buildFriendlyTableBuilderErrorMessage(\Throwable $exception): string
    {
        $message = trim((string)$exception->getMessage());
        if ($message === '') {
            return 'Terjadi kesalahan saat memproses table builder.';
        }

        if (preg_match('/^(Column|Kolom)\s+\'/i', $message) === 1) {
            return $message;
        }

        if (stripos($message, 'foreign key') !== false) {
            if (stripos($message, 'incompatible') !== false || stripos($message, 'Referencing column') !== false) {
                return 'Foreign key tidak dapat dibuat karena tipe data kolom relasi tidak cocok dengan kolom referensi. Samakan tipe, panjang, dan atribut unsigned bila diperlukan.';
            }
            if (stripos($message, 'must be unique') !== false || stripos($message, 'unique') !== false) {
                return 'Foreign key harus mengarah ke kolom PRIMARY KEY atau UNIQUE di tabel referensi.';
            }
            if (stripos($message, 'doesn\'t exist') !== false || stripos($message, 'unknown column') !== false || stripos($message, 'not found') !== false) {
                return 'Konfigurasi foreign key tidak valid: tabel atau kolom referensi belum tersedia di database fisik.';
            }
            return $message;
        }

        if (preg_match('/Data too long for column \'?([a-zA-Z0-9_]+)\'?/i', $message, $matches) === 1) {
            $columnLabel = Inflector::camel2words((string)$matches[1]);
            return "Input terlalu panjang pada field {$columnLabel}. Mohon sesuaikan dengan panjang kolom.";
        }

        if (stripos($message, 'Duplicate entry') !== false) {
            return 'Data gagal disimpan karena ada nilai yang harus unik tetapi sudah digunakan.';
        }

        if (stripos($message, 'SQLSTATE') !== false || stripos($message, 'syntax error') !== false) {
            $details = $this->extractDbErrorDetails($exception);
            $parts = ['Terjadi kesalahan SQL saat memproses table.'];
            if (($details['sqlstate'] ?? '') !== '') {
                $parts[] = 'SQLSTATE: ' . $details['sqlstate'] . '.';
            }
            if (($details['error_code'] ?? '') !== '') {
                $parts[] = 'Error code: ' . $details['error_code'] . '.';
            }
            if (($details['sql_error'] ?? '') !== '') {
                $parts[] = 'Detail: ' . $details['sql_error'];
            }
            $parts[] = 'Periksa struktur kolom, foreign key, atau statement SQL Anda.';
            return implode(' ', $parts);
        }

        return $message;
    }

    private function buildSqlEditorDebugPayload(\Throwable $exception, string $sql, array $context = []): array
    {
        if ($exception instanceof SqlEditorExecutionException) {
            $context = array_merge($exception->context, $context);
        }

        $dbError = $this->extractDbErrorDetails($exception);
        $failedStatement = trim((string)($context['failed_statement'] ?? $context['last_statement'] ?? ''));
        $createdTableName = $context['created_table_name'] ?? null;
        $tableName = $context['table_name'] ?? $createdTableName;
        $parsedColumns = $context['parsed_columns'] ?? [];
        $stage = (string)($context['stage'] ?? 'execution');
        $activeDatabase = $context['active_database'] ?? null;
        $existedBeforeExecute = $context['existed_before_execute'] ?? null;
        $existsAfterExecute = $context['exists_after_execute'] ?? null;
        $executedStatementCount = $context['executed_statement_count'] ?? null;
        $currentStage = $context['current_stage'] ?? $stage;
        $executionSource = $context['execution_source'] ?? null;
        $physicalTableExists = $context['physical_table_exists'] ?? null;
        $metadataTableExists = $context['metadata_table_exists'] ?? null;
        $diagnostics = $context['diagnostics'] ?? [];
        $suggestedFix = $context['suggested_fix'] ?? null;
        $overrideSqlState = $context['sqlstate'] ?? null;
        $overrideErrorCode = $context['error_code'] ?? null;
        $overrideSqlError = $context['sql_error'] ?? null;

        $message = trim((string)$exception->getMessage());
        if ($stage === 'metadata') {
            $message = 'SQL berhasil dijalankan di database, tetapi sinkronisasi metadata gagal: ' . ($message !== '' ? $message : 'unknown error');
        } elseif ($message === '') {
            $message = 'SQL gagal dijalankan.';
        }

        if (is_string($overrideSqlError) && trim($overrideSqlError) !== '') {
            $dbError['sql_error'] = trim($overrideSqlError);
        }
        if (is_string($overrideSqlState) && trim($overrideSqlState) !== '') {
            $dbError['sqlstate'] = trim($overrideSqlState);
        }
        if ($overrideErrorCode !== null && $overrideErrorCode !== '') {
            $dbError['error_code'] = (string)$overrideErrorCode;
        }
        if ($dbError['sql_error'] !== '') {
            $message .= ' Database error: ' . $dbError['sql_error'];
        }

        if ($suggestedFix === null) {
            if ($metadataTableExists === true && $physicalTableExists === false) {
                $suggestedFix = 'Metadata internal ditemukan tetapi tabel fisik tidak ada. Metadata orphans harus dibersihkan lalu CREATE TABLE dijalankan ulang.';
            } elseif ($physicalTableExists === true) {
                $suggestedFix = 'Tabel sudah ada di database aktif. Jika memang ingin membuat ulang, hapus tabel fisiknya terlebih dahulu.';
            }
        }

        return [
            'success' => false,
            'message' => $message,
            'sql_error' => $dbError['sql_error'],
            'sqlstate' => $dbError['sqlstate'],
            'error_code' => $dbError['error_code'],
            'database_error' => $dbError['database_error'],
            'failed_statement' => $failedStatement !== '' ? $failedStatement : null,
            'parsed_columns' => $parsedColumns,
            'created_table_name' => $createdTableName,
            'table_name' => $tableName,
            'active_database' => $activeDatabase,
            'existed_before_execute' => $existedBeforeExecute,
            'exists_after_execute' => $existsAfterExecute,
            'executed_statement_count' => $executedStatementCount,
            'current_stage' => $currentStage,
            'execution_source' => $executionSource,
            'physical_table_exists' => $physicalTableExists,
            'metadata_table_exists' => $metadataTableExists,
            'diagnostics' => $diagnostics,
            'suggested_fix' => $suggestedFix,
            'query_sql' => $sql,
            'stage' => $stage,
        ];
    }

    private function extractDbErrorDetails(\Throwable $exception): array
    {
        $sqlError = '';
        $sqlState = '';
        $errorCode = (string)$exception->getCode();
        $databaseError = trim((string)$exception->getMessage());
        $source = $exception;

        while ($source !== null) {
            if ($source instanceof \yii\db\Exception && is_array($source->errorInfo ?? null)) {
                $errorInfo = $source->errorInfo;
                $sqlState = (string)($errorInfo[0] ?? '');
                $errorCode = (string)($errorInfo[1] ?? $errorCode);
                $sqlError = trim((string)($errorInfo[2] ?? ''));
                if ($sqlError === '' && $databaseError !== '') {
                    $sqlError = $databaseError;
                }
                break;
            }

            if (property_exists($source, 'errorInfo') && is_array($source->errorInfo ?? null)) {
                $errorInfo = $source->errorInfo;
                $sqlState = (string)($errorInfo[0] ?? '');
                $errorCode = (string)($errorInfo[1] ?? $errorCode);
                $sqlError = trim((string)($errorInfo[2] ?? ''));
                if ($sqlError === '' && $databaseError !== '') {
                    $sqlError = $databaseError;
                }
                break;
            }

            $source = $source->getPrevious();
        }

        if ($sqlError === '') {
            $sqlError = $databaseError;
        }

        return [
            'sqlstate' => $sqlState !== '' ? $sqlState : null,
            'error_code' => $errorCode !== '' ? $errorCode : null,
            'sql_error' => $sqlError,
            'database_error' => $databaseError !== '' ? $databaseError : $sqlError,
        ];
    }

    private function executeRawSchemaSql(string $sql): array
    {
        $sql = trim($sql);
        if ($sql === '') {
            throw new \RuntimeException('SQL editor is empty.');
        }

        $statements = $this->splitSqlStatements($sql);
        if (empty($statements)) {
            throw new \RuntimeException('Tidak ada statement SQL yang valid untuk dijalankan.');
        }

        $db = $this->getPhysicalDb();
        $activeDatabase = $this->getCurrentDatabaseName($db);
        $executionSource = $this->getSqlEditorExecutionSource();
        $tablesToSync = [];
        $createdTables = [];
        $lastStatement = '';
        $lastStatementIndex = -1;
        $parsedColumns = [];
        $metadataStarted = false;
        $safeCreateEnabled = (string)Yii::$app->request->post('safe_create', '0') === '1';
        $executedStatementCount = 0;
        $primaryCreateTableName = null;
        $primaryExistedBeforeExecute = null;
        $primaryExistsAfterExecute = null;
        $primaryRecoveredFrom1050 = false;

        try {
            foreach ($statements as $index => $statement) {
                $lastStatement = $statement;
                $lastStatementIndex = $index;
                $validationError = $this->validateSchemaStatement($statement);
                if ($validationError !== null) {
                    throw new \RuntimeException('Statement #' . ($index + 1) . ': ' . $validationError);
                }

                $statement = $this->maybeApplySafeCreate($statement, $safeCreateEnabled);
                $statementTableName = $this->extractCreatedTableName($statement);
                if ($statementTableName !== null) {
                    $physicalExists = $this->tableExistsInPhysicalDatabase($db, $statementTableName);
                    $metadataExists = $this->tableExistsInMetadata($statementTableName);
                    if ($primaryCreateTableName === null) {
                        $primaryCreateTableName = $statementTableName;
                        $primaryExistedBeforeExecute = $physicalExists;
                    }
                    Yii::info([
                        'stage' => 'sql_editor_precheck',
                        'database' => $activeDatabase,
                        'table_name' => $statementTableName,
                        'physical_table_exists' => $physicalExists,
                        'metadata_table_exists' => $metadataExists,
                        'safe_create' => $safeCreateEnabled,
                        'executed_statement_count' => $executedStatementCount,
                        'execution_source' => $executionSource,
                    ], 'table-builder-sql');

                    if ($physicalExists) {
                        Yii::warning([
                            'stage' => isset($createdTables[$statementTableName])
                                ? 'sql_editor_duplicate_create_skipped'
                                : 'sql_editor_existing_create_skipped',
                            'active_database' => $activeDatabase,
                            'table_name' => $statementTableName,
                            'physical_table_exists' => true,
                            'metadata_table_exists' => $metadataExists,
                            'executed_statement_count' => $executedStatementCount,
                            'execution_source' => $executionSource,
                        ], 'table-builder-sql');

                        $tablesToSync[$statementTableName] = true;
                        $primaryExistsAfterExecute = true;
                        continue;
                    }

                    if ($metadataExists && !$physicalExists) {
                        $this->cleanupOrphanMetadataByTableName($statementTableName);
                        $this->refreshDbTableColumnsSchema();
                    }
                }

                Yii::info([
                    'stage' => 'sql_editor_execute',
                    'statement_index' => $index + 1,
                    'statement' => $statement,
                    'sql' => $sql,
                    'active_database' => $activeDatabase,
                    'execution_source' => $executionSource,
                ], 'table-builder-sql');

                $statementExecuted = false;
                try {
                    $db->createCommand($statement)->execute();
                    $statementExecuted = true;
                } catch (\Throwable $statementError) {
                    $statementErrorDetails = $this->extractDbErrorDetails($statementError);
                    $statementCode = (int)($statementErrorDetails['error_code'] ?? $statementError->getCode());
                    $statementTableName = $this->extractCreatedTableName($statement);
                    if ($statementTableName !== null && $statementCode === 1050) {
                        $existsAfterExecute = $this->tableExistsInPhysicalDatabase($db, $statementTableName);
                        if ($primaryCreateTableName === null) {
                            $primaryCreateTableName = $statementTableName;
                            $primaryExistedBeforeExecute = false;
                        }
                        if ($existsAfterExecute && $primaryExistedBeforeExecute !== true) {
                            $statementExecuted = true;
                            $primaryRecoveredFrom1050 = true;
                            Yii::warning([
                                'stage' => 'sql_editor_recovered_1050',
                                'database' => $activeDatabase,
                                'table_name' => $statementTableName,
                                'exists_after_execute' => $existsAfterExecute,
                                'executed_statement_count' => $executedStatementCount,
                                'execution_source' => $executionSource,
                                'error_code' => $statementCode,
                                'sqlstate' => $statementErrorDetails['sqlstate'] ?? null,
                                'error' => $statementErrorDetails['sql_error'] ?? $statementError->getMessage(),
                            ], 'table-builder-sql');
                        } else {
                            throw $statementError;
                        }
                    } else {
                        throw $statementError;
                    }
                }

                if ($statementExecuted) {
                    $executedStatementCount++;
                }

                if ($statementTableName !== null) {
                    $createdTables[$statementTableName] = true;
                    $primaryExistsAfterExecute = $this->tableExistsInPhysicalDatabase($db, $statementTableName);
                    $tablesToSync[$statementTableName] = true;
                    $db->schema->refreshTableSchema($statementTableName);
                }

                $affectedTableName = $this->extractAffectedTableName($statement);
                if ($affectedTableName !== null) {
                    $tablesToSync[$affectedTableName] = true;
                    $db->schema->refreshTableSchema($affectedTableName);
                }
            }

            if (empty($tablesToSync)) {
                throw new \RuntimeException('SQL berhasil dijalankan, tetapi tidak ada table yang bisa disinkronkan.');
            }

            $db->schema->refresh();
            $metadataStarted = true;
            $syncedTables = $this->syncImportedTables(array_keys($tablesToSync), $parsedColumns);
            foreach (array_keys($tablesToSync) as $syncedTableName) {
                $db->schema->refreshTableSchema($syncedTableName);
            }

            $primaryTableName = $primaryCreateTableName ?? (!empty($createdTables) ? array_key_first($createdTables) : (!empty($tablesToSync) ? array_key_first($tablesToSync) : null));
            $primaryMetadataExistsAfterSync = $primaryTableName !== null ? $this->tableExistsInMetadata($primaryTableName) : null;
            $primaryPhysicalExistsAfterSync = $primaryTableName !== null ? $this->tableExistsInPhysicalDatabase($db, $primaryTableName) : null;
            $stage = $primaryExistedBeforeExecute === true
                ? 'existing_table_synced'
                : ($primaryRecoveredFrom1050 ? 'created_but_executor_reported_1050' : 'created_then_synced');

            return [
                'statements' => $statements,
                'tables' => $syncedTables,
                'parsed_columns' => $parsedColumns,
                'created_table_name' => $primaryTableName,
                'table_name' => $primaryTableName,
                'last_statement' => $lastStatement,
                'active_database' => $activeDatabase,
                'existed_before_execute' => $primaryExistedBeforeExecute,
                'exists_after_execute' => $primaryExistsAfterExecute,
                'physical_table_exists' => $primaryPhysicalExistsAfterSync,
                'metadata_table_exists' => $primaryMetadataExistsAfterSync,
                'executed_statement_count' => $executedStatementCount,
                'current_stage' => $stage,
                'execution_source' => $executionSource,
                'message' => $stage === 'existing_table_synced'
                    ? 'Tabel sudah ada, CREATE TABLE dilewati, metadata disinkronkan.'
                    : 'Tabel berhasil dibuat dan metadata berhasil disinkronkan.',
            ];
        } catch (\Throwable $e) {
            $stage = $metadataStarted ? 'metadata' : 'execution';
            if (!empty($createdTables)) {
                if ($stage === 'execution') {
                    $this->cleanupSqlEditorArtifacts(array_keys($createdTables));
                }
            }
            $context = [
                'stage' => $stage,
                'failed_statement' => $lastStatement,
                'last_statement' => $lastStatement,
                'created_table_name' => $primaryCreateTableName ?? (!empty($createdTables) ? array_key_first($createdTables) : null),
                'table_name' => $primaryCreateTableName ?? (!empty($createdTables) ? array_key_first($createdTables) : ($this->extractCreatedTableName($lastStatement) ?? null)),
                'parsed_columns' => $parsedColumns,
                'statement_index' => $lastStatementIndex,
                'active_database' => $activeDatabase,
                'existed_before_execute' => $primaryExistedBeforeExecute,
                'exists_after_execute' => $primaryExistsAfterExecute,
                'executed_statement_count' => $executedStatementCount,
                'execution_source' => $executionSource,
                'current_stage' => $stage,
            ];
            $diagnosticsTable = !empty($createdTables) ? array_key_first($createdTables) : ($this->extractCreatedTableName($lastStatement) ?? null);
            if ($diagnosticsTable !== null) {
                $context['diagnostics'] = $this->collectSqlEditorTableDiagnostics($db, $diagnosticsTable);
                $context['physical_table_exists'] = $context['diagnostics']['physical_table_exists'] ?? null;
                $context['metadata_table_exists'] = $context['diagnostics']['metadata_table_exists'] ?? null;
                $context['suggested_fix'] = $context['diagnostics']['suggested_fix'] ?? null;
            }
            $payload = $this->buildSqlEditorDebugPayload($e, $sql, $context);
            Yii::error([
                'stage' => $stage,
                'sql' => $sql,
                'failed_statement' => $lastStatement,
                'statement_index' => $lastStatementIndex + 1,
                'active_database' => $activeDatabase,
                'error' => $payload,
            ], 'table-builder-sql');
            $runtime = new SqlEditorExecutionException($payload['message'] ?? $e->getMessage(), (int)$e->getCode(), $e);
            $runtime->context = $context;
            if (property_exists($e, 'errorInfo') && is_array($e->errorInfo ?? null)) {
                $runtime->context['errorInfo'] = $e->errorInfo;
            }
            throw $runtime;
        }
    }

    private function getSqlEditorExecutionSource(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        foreach ($trace as $frame) {
            $class = (string)($frame['class'] ?? '');
            if ($class === __CLASS__) {
                continue;
            }

            return [
                'class' => $class !== '' ? $class : null,
                'function' => $frame['function'] ?? null,
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
            ];
        }

        return [
            'class' => null,
            'function' => null,
            'file' => null,
            'line' => null,
        ];
    }

    private function getCurrentDatabaseName(Connection $db): ?string
    {
        return $this->getTableExistenceService($db)->getCurrentDatabaseName();
    }

    private function tableExistsInPhysicalDatabase(Connection $db, string $tableName): bool
    {
        return $this->getTableExistenceService($db)->physicalExists($tableName);
    }

    private function tableExistsInMetadata(string $tableName): bool
    {
        return $this->getTableExistenceService()->metadataExists($tableName, $this->getMetadataScope());
    }

    private function cleanupOrphanMetadataByTableName(string $tableName): void
    {
        $criteria = [
            'name' => strtolower(trim($tableName)),
        ];
        $effectiveUserId = $this->getEffectiveUserId();
        if ($effectiveUserId !== null) {
            $criteria['user_id'] = $effectiveUserId;
        }
        if (ProjectSchema::supportsProjectContext()) {
            $activeProjectId = $this->getActiveProjectId();
            if ($activeProjectId !== null) {
                $criteria['project_id'] = $activeProjectId;
            }
        }

        $tables = DbTable::find()->where($criteria)->all();
        foreach ($tables as $table) {
            DbTableColumn::deleteAll(['table_id' => $table->id]);
            $table->delete();
        }
    }

    private function maybeApplySafeCreate(string $statement, bool $safeCreateEnabled): string
    {
        if (!$safeCreateEnabled) {
            return $statement;
        }

        if (preg_match('/^\s*CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS\b)/i', $statement) !== 1) {
            return $statement;
        }

        return preg_replace('/^\s*CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $statement, 1) ?? $statement;
    }

    private function collectSqlEditorTableDiagnostics(Connection $db, string $tableName): array
    {
        $activeDatabase = $this->getCurrentDatabaseName($db);
        $escaped = str_replace('`', '``', strtolower($tableName));
        $physicalExists = $this->tableExistsInPhysicalDatabase($db, $tableName);
        $metadataExists = $this->tableExistsInMetadata($tableName);
        $showFullTables = [];
        $showCreateTable = null;
        $createTableError = null;

        try {
            $showFullTables = $db->createCommand("SHOW FULL TABLES LIKE :table_name", [':table_name' => $tableName])->queryAll();
        } catch (\Throwable $e) {
            $showFullTables = [['error' => $e->getMessage()]];
        }

        if ($physicalExists) {
            try {
                $showCreateTable = $db->createCommand("SHOW CREATE TABLE `{$escaped}`")->queryOne();
            } catch (\Throwable $e) {
                $createTableError = $e->getMessage();
            }
        }

        return [
            'active_database' => $activeDatabase,
            'table_name' => strtolower($tableName),
            'physical_table_exists' => $physicalExists,
            'metadata_table_exists' => $metadataExists,
            'show_full_tables' => $showFullTables,
            'show_create_table' => $showCreateTable,
            'show_create_table_error' => $createTableError,
            'suggested_fix' => $physicalExists
                ? 'Tabel benar-benar ada di database aktif. Hapus atau rename tabel terlebih dahulu.'
                : ($metadataExists
                    ? 'Metadata lama ditemukan tanpa tabel fisik. Metadata orphan harus dibersihkan lalu CREATE TABLE dijalankan ulang.'
                    : 'Tabel fisik tidak ditemukan. Periksa koneksi database aktif dan schema cache Yii.'),
        ];
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $singleQuoted = false;
        $doubleQuoted = false;
        $backticked = false;
        $lineComment = false;
        $blockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($lineComment) {
                if ($char === "\n") {
                    $lineComment = false;
                    $buffer .= $char;
                }
                continue;
            }

            if ($blockComment) {
                if ($char === '*' && $next === '/') {
                    $blockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$singleQuoted && !$doubleQuoted && !$backticked) {
                if ($char === '-' && $next === '-') {
                    $lineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $lineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $blockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === "'" && !$doubleQuoted && !$backticked) {
                $escaped = $i > 0 && $sql[$i - 1] === '\\';
                if (!$escaped) {
                    $singleQuoted = !$singleQuoted;
                }
                $buffer .= $char;
                continue;
            }

            if ($char === '"' && !$singleQuoted && !$backticked) {
                $escaped = $i > 0 && $sql[$i - 1] === '\\';
                if (!$escaped) {
                    $doubleQuoted = !$doubleQuoted;
                }
                $buffer .= $char;
                continue;
            }

            if ($char === '`' && !$singleQuoted && !$doubleQuoted) {
                $backticked = !$backticked;
                $buffer .= $char;
                continue;
            }

            if ($char === ';' && !$singleQuoted && !$doubleQuoted && !$backticked) {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    private function validateSchemaStatement(string $statement): ?string
    {
        $normalized = trim($statement);
        if ($normalized === '') {
            return 'statement kosong.';
        }

        $compact = strtoupper(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
        $bannedPatterns = [
            '/^\s*DROP\s+DATABASE\b/',
            '/^\s*DROP\s+TABLE\b/',
            '/^\s*TRUNCATE\b/',
            '/^\s*DELETE\b/',
            '/^\s*UPDATE\b/',
            '/^\s*INSERT\b/',
            '/^\s*REPLACE\b/',
            '/^\s*CREATE\s+DATABASE\b/',
            '/^\s*ALTER\s+DATABASE\b/',
            '/^\s*GRANT\b/',
            '/^\s*REVOKE\b/',
            '/^\s*CALL\b/',
            '/^\s*LOAD\s+DATA\b/',
            '/^\s*HANDLER\b/',
            '/^\s*LOCK\s+TABLES\b/',
            '/^\s*UNLOCK\s+TABLES\b/',
            '/^\s*START\s+TRANSACTION\b/',
            '/^\s*COMMIT\b/',
            '/^\s*ROLLBACK\b/',
        ];

        foreach ($bannedPatterns as $pattern) {
            if (preg_match($pattern, $compact) === 1) {
                return 'statement berbahaya diblokir.';
            }
        }

        if (preg_match('/^\s*CREATE\s+TABLE\b/i', $normalized) === 1) {
            return null;
        }

        if (preg_match('/^\s*ALTER\s+TABLE\b/i', $normalized) === 1) {
            if (preg_match('/\bDROP\s+(COLUMN|INDEX|KEY)\b/i', $normalized) === 1) {
                return 'ALTER TABLE dengan DROP diblokir.';
            }
            if (preg_match('/\bDROP\s+PRIMARY\s+KEY\b/i', $normalized) === 1) {
                return 'ALTER TABLE dengan DROP PRIMARY KEY diblokir.';
            }
            return null;
        }

        return 'hanya CREATE TABLE dan ALTER TABLE yang diperbolehkan.';
    }

    private function extractAffectedTableName(string $statement): ?string
    {
        if (preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $statement, $matches) === 1) {
            return strtolower($matches[1]);
        }

        if (preg_match('/^\s*ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private function extractCreatedTableName(string $statement): ?string
    {
        if (preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $statement, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return null;
    }

    private function syncImportedTables(array $tableNames, array &$parsedColumns = []): array
    {
        $synced = [];
        foreach (array_values(array_unique($tableNames)) as $tableName) {
            $synced[] = $this->syncImportedTable($tableName);
            $parsedColumns[$tableName] = $this->describeImportedTableColumns($tableName);
        }

        return $synced;
    }

    private function syncImportedTable(string $tableName): string
    {
        $model = $this->getDatabaseSchemaSyncService()->syncTable($tableName);
        $this->getPhysicalDb()->schema->refreshTableSchema((string)$model->name);
        return (string)$model->name;
    }

    private function describeImportedTableColumns(string $tableName): array
    {
        $db = $this->getPhysicalDb();
        $databaseName = $this->getCurrentDatabaseName($db);
        if ($databaseName === null) {
            throw new \RuntimeException("Database aktif tidak ditemukan saat membaca metadata kolom untuk '{$tableName}'.");
        }

        $columns = (new \yii\db\Query())
            ->select([
                'column_name' => 'c.COLUMN_NAME',
                'data_type' => 'c.DATA_TYPE',
                'column_type' => 'c.COLUMN_TYPE',
                'is_nullable' => 'c.IS_NULLABLE',
                'column_default' => 'c.COLUMN_DEFAULT',
                'extra' => 'c.EXTRA',
                'comment' => 'c.COLUMN_COMMENT',
                'ordinal_position' => 'c.ORDINAL_POSITION',
            ])
            ->from(['c' => 'INFORMATION_SCHEMA.COLUMNS'])
            ->where([
                'c.TABLE_SCHEMA' => $databaseName,
                'c.TABLE_NAME' => strtolower($tableName),
            ])
            ->orderBy(['c.ORDINAL_POSITION' => SORT_ASC])
            ->all($db);

        if (empty($columns)) {
            throw new \RuntimeException("Table '{$tableName}' tidak ditemukan setelah sinkronisasi metadata.");
        }

        $schema = $db->schema->getTableSchema($tableName, true);
        $primaryKeyColumns = array_flip(array_map('strtolower', (array)($schema->primaryKey ?? [])));
        $uniqueColumns = $this->getUniqueColumnsFromTable($tableName);
        $foreignKeyMap = $this->getForeignKeyMetadataFromTable($tableName);
        $parsed = [];
        $sortOrder = 1;

        foreach ($columns as $row) {
            $columnName = strtolower((string)($row['column_name'] ?? ''));
            if ($columnName === '') {
                continue;
            }

            [$type, $length, $enumValues] = $this->inferImportedColumnType((string)($row['column_type'] ?? $row['data_type'] ?? 'TEXT'));
            $rawDbType = trim((string)($row['column_type'] ?? ''));
            $parsed[] = [
                'name' => $columnName,
                'type' => $type,
                'length' => $length,
                'enum_values' => $enumValues,
                'db_type' => $rawDbType !== '' ? $rawDbType : null,
                'column_type' => $rawDbType !== '' ? $rawDbType : null,
                'data_type' => strtoupper(trim((string)($row['data_type'] ?? $type))),
                'allow_null' => strtoupper(trim((string)($row['is_nullable'] ?? 'YES'))) === 'YES',
                'default_value' => $row['column_default'] !== null ? (string)$row['column_default'] : null,
                'auto_increment' => stripos((string)($row['extra'] ?? ''), 'auto_increment') !== false,
                'is_primary' => isset($primaryKeyColumns[$columnName]),
                'is_unique' => isset($uniqueColumns[$columnName]),
                'comment' => $row['comment'] !== null ? (string)$row['comment'] : null,
                'foreign_key' => $foreignKeyMap[$columnName] ?? null,
                'sort_order' => $sortOrder,
            ];
            $sortOrder++;
        }

        return $parsed;
    }

    private function cleanupSqlEditorArtifacts(array $tableNames): void
    {
        $db = $this->getPhysicalDb();

        foreach (array_values(array_unique($tableNames)) as $tableName) {
            try {
                $db->createCommand('DROP TABLE IF EXISTS `' . str_replace('`', '``', strtolower($tableName)) . '`')->execute();
            } catch (\Throwable $dropError) {
                Yii::warning('Failed dropping SQL editor table artifact: ' . $dropError->getMessage(), 'table-builder');
            }

            try {
                $criteria = ['name' => strtolower($tableName)];
                $effectiveUserId = $this->getEffectiveUserId();
                if ($effectiveUserId !== null) {
                    $criteria['user_id'] = $effectiveUserId;
                }
                if (ProjectSchema::supportsProjectContext()) {
                    $activeProjectId = $this->getActiveProjectId();
                    if ($activeProjectId !== null) {
                        $criteria['project_id'] = $activeProjectId;
                    }
                }

                $model = DbTable::findOne($criteria);
                if ($model !== null) {
                    DbTableColumn::deleteAll(['table_id' => $model->id]);
                    $model->delete();
                }
            } catch (\Throwable $metadataError) {
                Yii::warning('Failed deleting SQL editor metadata artifact: ' . $metadataError->getMessage(), 'table-builder');
            }
        }
    }

    private function buildImportedColumnModel(int $tableId, $columnSchema, int $sortOrder, array $primaryKeyColumns, array $uniqueColumns, array $foreignKeyMap): DbTableColumn
    {
        $column = new DbTableColumn();
        $column->table_id = $tableId;
        $column->name = strtolower((string)$columnSchema->name);
        $column->label = ucwords(str_replace('_', ' ', $column->name));

        [$type, $length, $enumValues] = $this->inferImportedColumnType((string)($columnSchema->dbType ?? $columnSchema->type ?? 'TEXT'));
        $column->type = $type;
        $column->length = $length;
        $column->is_nullable = (bool)($columnSchema->allowNull ?? true);
        $column->is_primary = isset($primaryKeyColumns[$column->name]);
        $column->is_unique = isset($uniqueColumns[$column->name]) || $column->is_primary;

        if ($column->hasAttribute('is_auto_increment')) {
            $column->setAttribute('is_auto_increment', (bool)($columnSchema->autoIncrement ?? false));
        }

        $column->default_value = $columnSchema->defaultValue !== null ? (string)$columnSchema->defaultValue : null;
        $column->comment = $columnSchema->comment !== null ? (string)$columnSchema->comment : null;
        $column->sort_order = $sortOrder;

        if ($column->hasAttribute('enum_values')) {
            $column->setAttribute('enum_values', $enumValues);
        }

        if ($column->hasAttribute('is_foreign_key')) {
            $fk = $foreignKeyMap[$column->name] ?? null;
            $column->setAttribute('is_foreign_key', $fk !== null);
            $column->setAttribute('referenced_table_name', $fk['referenced_table_name'] ?? null);
            $column->setAttribute('referenced_column_name', $fk['referenced_column_name'] ?? null);
            $column->setAttribute('on_delete_action', $fk['on_delete_action'] ?? 'RESTRICT');
            $column->setAttribute('on_update_action', $fk['on_update_action'] ?? 'RESTRICT');
        }

        return $column;
    }

    private function inferImportedColumnType(string $dbType): array
    {
        $normalized = strtolower(trim($dbType));
        $normalized = preg_replace('/\s+unsigned$/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+zerofill$/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        if (in_array($normalized, ['bool', 'boolean', 'bit(1)', 'tinyint(1)'], true)) {
            return ['BOOLEAN', 1, null];
        }

        if (preg_match('/^([a-z]+)\(([^)]*)\)$/i', $normalized, $matches) === 1) {
            $type = strtoupper($matches[1]);
            $args = trim($matches[2]);

            if (in_array($type, ['ENUM', 'SET'], true)) {
                preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $args, $enumMatches);
                $values = array_map(static function ($value) {
                    return str_replace("\\'", "'", $value);
                }, $enumMatches[1] ?? []);
                return [$type, null, implode(',', $values)];
            }

            $firstArg = trim(explode(',', $args)[0] ?? '');
            $length = is_numeric($firstArg) ? (int)$firstArg : null;
            return [$type, $length, null];
        }

        $type = strtoupper($normalized);
        if ($type === '') {
            $type = 'TEXT';
        }

        return [$type, null, null];
    }

    private function getUniqueColumnsFromTable(string $tableName): array
    {
        $db = $this->getPhysicalDb();
        if (stripos((string)$db->dsn, 'mysql:') !== 0) {
            return [];
        }

        $rows = $db->createCommand('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '`')->queryAll();

        $uniqueColumns = [];
        foreach ($rows as $row) {
            if ((int)($row['Non_unique'] ?? 1) === 0 && strcasecmp((string)($row['Key_name'] ?? ''), 'PRIMARY') !== 0) {
                $uniqueColumns[strtolower((string)($row['Column_name'] ?? ''))] = true;
            }
        }

        return $uniqueColumns;
    }

    private function getForeignKeyMetadataFromTable(string $tableName): array
    {
        $db = $this->getPhysicalDb();
        if (stripos((string)$db->dsn, 'mysql:') !== 0) {
            return [];
        }

        $databaseName = (string)$db->createCommand('SELECT DATABASE()')->queryScalar();
        if ($databaseName === '') {
            return [];
        }

        $rows = (new \yii\db\Query())
            ->select([
                'column_name' => 'kcu.COLUMN_NAME',
                'referenced_table_name' => 'kcu.REFERENCED_TABLE_NAME',
                'referenced_column_name' => 'kcu.REFERENCED_COLUMN_NAME',
                'on_update_action' => 'rc.UPDATE_RULE',
                'on_delete_action' => 'rc.DELETE_RULE',
            ])
            ->from(['kcu' => 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE'])
            ->innerJoin(['rc' => 'INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS'], 'kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME')
            ->where([
                'kcu.CONSTRAINT_SCHEMA' => $databaseName,
                'kcu.TABLE_NAME' => $tableName,
            ])
            ->andWhere(['not', ['kcu.REFERENCED_TABLE_NAME' => null]])
            ->all($db);

        $map = [];
        foreach ($rows as $row) {
            $columnName = strtolower((string)($row['column_name'] ?? ''));
            if ($columnName === '') {
                continue;
            }

            $map[$columnName] = [
                'referenced_table_name' => strtolower((string)($row['referenced_table_name'] ?? '')),
                'referenced_column_name' => strtolower((string)($row['referenced_column_name'] ?? '')),
                'on_delete_action' => strtoupper((string)($row['on_delete_action'] ?? 'RESTRICT')),
                'on_update_action' => strtoupper((string)($row['on_update_action'] ?? 'RESTRICT')),
            ];
        }

        return $map;
    }

    protected function findModel($id)
    {
        $activeProjectId = $this->getActiveProjectId();
        $criteria = [
            'id' => (int)$id,
        ];
        if (!$this->isCommanderSuperAdmin()) {
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $criteria['user_id'] = $effectiveUserId;
            }
        }
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $criteria['project_id'] = $activeProjectId;
        }

        $model = DbTable::findOne($criteria);

        if ($model !== null) {
            return $model;
        }

        if (DbTable::find()->where(['id' => (int)$id])->exists()) {
            throw new ForbiddenHttpException('You are not allowed to access this table.');
        }

        throw new NotFoundHttpException('The requested table does not exist.');
    }
    
    /**
     * Get columns for a table (JSON API for form builder)
     */
    public function actionGetColumns($id)
    {
        try {
            $this->refreshDbTableColumnsSchema();
            
            $model = $this->findModel($id);
            if ($model === null) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Table not found for id: ' . $id
                ]);
            }
            if ($this->hasPhysicalTable($model)) {
                $model = $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
                $this->refreshDbTableColumnsSchema();
            }
            
            $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
            $tableSchema = null;
            try {
                $tableSchema = $this->getPhysicalDb()->schema->getTableSchema($model->name, true);
            } catch (\Throwable $schemaError) {
                $tableSchema = null;
            }
            
            $columnData = [];
            $debugColumns = [];
            foreach ($columns as $col) {
                $schemaColumn = $tableSchema !== null ? ($tableSchema->columns[$col->name] ?? null) : null;
                SystemFieldService::debugDecision($col, 'table-builder/get-columns', $schemaColumn);
                $debugColumns[] = SystemFieldService::decisionPayload($col, 'table-builder/get-columns', $schemaColumn);

                if (SystemFieldService::shouldHideFromForm($col, $schemaColumn)) {
                    continue;
                }

                $columnData[] = [
                    'id' => $col->id,
                    'name' => $col->name,
                    'label' => $col->label ?: $col->name,
                    'type' => $col->type,
                    'base_type' => $col->type,
                    'db_type' => $schemaColumn !== null ? (string)($schemaColumn->dbType ?? '') : $col->type,
                    'data_type' => $schemaColumn !== null ? (string)($schemaColumn->type ?? '') : $col->type,
                    'column_type' => $schemaColumn !== null ? (string)($schemaColumn->dbType ?? '') : $col->type,
                    'is_nullable' => (bool)$col->is_nullable,
                    'is_primary' => SystemFieldService::isPrimaryKey($col, $schemaColumn),
                    'is_system_field' => SystemFieldService::isSystemManagedField($col, $schemaColumn),
                    'is_auto_increment' => SystemFieldService::isAutoIncrement($col, $schemaColumn),
                    'is_foreign_key' => SystemFieldService::isForeignKey($col, $schemaColumn),
                    'referenced_table_name' => $col->hasAttribute('referenced_table_name') ? $col->getAttribute('referenced_table_name') : null,
                    'referenced_column_name' => $col->hasAttribute('referenced_column_name') ? $col->getAttribute('referenced_column_name') : null,
                    'debug_system_field' => SystemFieldService::decisionPayload($col, 'table-builder/get-columns', $schemaColumn),
                    'default_value' => $col->default_value,
                    'max_length' => $col->length,
                    'length' => $col->length,
                ];
            }
            
            return $this->asJson([
                'success' => true,
                'table_id' => $model->id,
                'table_name' => $model->name,
                'table_label' => $model->label,
                'columns' => $columnData,
                'system_field_debug' => $debugColumns,
            ]);
        } catch (\Throwable $e) {
            return $this->asJson([
                'success' => false,
                'error' => $this->buildFriendlyTableBuilderErrorMessage($e),
            ]);
        }
    }
    
/**
     * Get table definitions from metadata (DbTable model) for form builder dropdown
     * This returns tables defined in Master Table (table-builder), not physical database tables
     */
    public function actionGetTables()
    {
        try {
            $this->getDatabaseSchemaSyncService()->syncAllPhysicalTables();
            $this->refreshDbTableColumnsSchema();

            $activeProjectId = $this->getActiveProjectId();
            
            // Get table definitions from DbTable model (like table-builder index does)
            $tablesQuery = DbTable::find()
                ->orderBy(['created_at' => SORT_DESC]);

            if (!$this->isCommanderSuperAdmin()) {
                $effectiveUserId = $this->getEffectiveUserId();
                if ($effectiveUserId !== null) {
                    $tablesQuery->where(['user_id' => $effectiveUserId]);
                }
            }
                
            if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
                $tablesQuery->andWhere(['project_id' => $activeProjectId]);
            }
            if (DbTable::getTableSchema() !== null && isset(DbTable::getTableSchema()->columns['is_created'])) {
                $tablesQuery->andWhere(['is_created' => true]);
            }
            
            $tables = $tablesQuery->all();
            
            $tableList = [];
            foreach ($tables as $table) {
                $this->syncTableCreationState($table);
                $tableList[] = [
                    'id' => $table->id,
                    'name' => $table->name,
                    'label' => $table->label ?: $table->name,
                ];
            }
            
            return $this->asJson([
                'success' => true,
                'tables' => $tableList,
                'source' => 'db_table_metadata',
                'count' => count($tableList)
            ]);
            
        } catch (\Throwable $e) {
            return $this->asJson([
                'success' => false,
                'error' => $this->buildFriendlyTableBuilderErrorMessage($e),
            ]);
        }
    }
    
    /**
     * Get columns from table definition (metadata) for form builder
     */
    public function actionGetTableColumns($id)
    {
        try {
            $this->refreshDbTableColumnsSchema();
            
            // Find the table by ID
            $model = DbTable::findOne((int)$id);
            if ($model === null) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Table not found with id: ' . $id
                ]);
            }
            if ($this->hasPhysicalTable($model)) {
                $model = $this->getDatabaseSchemaSyncService()->syncTable((string)$model->name);
                $this->refreshDbTableColumnsSchema();
            }
            
            // Get columns relation
            $columns = $model->getColumns()->orderBy(['sort_order' => SORT_ASC])->all();
            $tableSchema = null;
            try {
                $tableSchema = $this->getPhysicalDb()->schema->getTableSchema($model->name, true);
            } catch (\Throwable $schemaError) {
                $tableSchema = null;
            }
            
            $columnData = [];
            $debugColumns = [];
            foreach ($columns as $col) {
                $schemaColumn = $tableSchema !== null ? ($tableSchema->columns[$col->name] ?? null) : null;
                SystemFieldService::debugDecision($col, 'table-builder/get-table-columns', $schemaColumn);
                $debugColumns[] = SystemFieldService::decisionPayload($col, 'table-builder/get-table-columns', $schemaColumn);

                if (SystemFieldService::shouldHideFromForm($col, $schemaColumn)) {
                    continue;
                }

                $isFk = $col->hasAttribute('is_foreign_key') ? (bool)$col->getAttribute('is_foreign_key') : false;
                $columnData[] = [
                    'id' => $col->id,
                    'name' => $col->name,
                    'label' => $col->label ?: $col->name,
                    'type' => $col->type,
                    'base_type' => preg_match('/^(\w+)/', $col->type ?? '', $m) ? $m[1] : ($col->type ?? 'text'),
                    'is_nullable' => (bool)$col->is_nullable,
                    'is_primary' => SystemFieldService::isPrimaryKey($col, $schemaColumn),
                    'is_auto_increment' => SystemFieldService::isAutoIncrement($col, $schemaColumn),
                    'is_foreign_key' => SystemFieldService::isForeignKey($col, $schemaColumn),
                    'is_system_field' => SystemFieldService::isSystemManagedField($col, $schemaColumn),
                    'debug_system_field' => SystemFieldService::decisionPayload($col, 'table-builder/get-table-columns', $schemaColumn),
                    'referenced_table_name' => $isFk ? $col->getAttribute('referenced_table_name') : null,
                    'referenced_column_name' => $isFk ? $col->getAttribute('referenced_column_name') : null,
                    'default_value' => $col->default_value,
                ];
            }
            
            return $this->asJson([
                'success' => true,
                'table_id' => $model->id,
                'table_name' => $model->name,
                'table_label' => $model->label ?: $model->name,
                'columns' => $columnData,
                'system_field_debug' => $debugColumns,
            ]);
        } catch (\Throwable $e) {
            return $this->asJson([
                'success' => false,
                'error' => $this->buildFriendlyTableBuilderErrorMessage($e),
            ]);
        }
    }

    /**
     * Build dropdown options from any dynamic table in the active workspace.
     */
    public function actionDropdownOptions(int $table_id)
    {
        try {
            $this->refreshDbTableColumnsSchema();

            $table = DbTable::findOne((int)$table_id);
            if ($table === null) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Table not found.',
                ]);
            }

            $db = $this->getPhysicalDb();
            $schema = $db->schema->getTableSchema((string)$table->name, true);
            if ($schema === null) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Physical table is not available yet.',
                ]);
            }

            $valueColumn = trim((string)Yii::$app->request->get('value_column', ''));
            $labelColumn = trim((string)Yii::$app->request->get('label_column', ''));
            if ($valueColumn === '') {
                $valueColumn = $this->resolvePrimaryOrFirstColumn($schema);
            }
            if ($labelColumn === '') {
                $labelColumn = $this->resolveFkDisplayColumn($db, (string)$table->name, $valueColumn) ?: $valueColumn;
            }

            if (!isset($schema->columns[$valueColumn]) || !isset($schema->columns[$labelColumn])) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Selected value/label column is not valid for this table.',
                ]);
            }

            $rows = (new \yii\db\Query())
                ->select([
                    'value' => $valueColumn,
                    'label' => $labelColumn,
                ])
                ->from((string)$table->name)
                ->orderBy([$labelColumn => SORT_ASC])
                ->limit(500)
                ->all($db);

            $options = [];
            foreach ($rows as $row) {
                $value = isset($row['value']) ? (string)$row['value'] : '';
                if ($value === '') {
                    continue;
                }
                $label = trim((string)($row['label'] ?? ''));
                $options[] = [
                    'value' => $value,
                    'label' => $label !== '' ? $label : $value,
                ];
            }

            return $this->asJson([
                'success' => true,
                'table_id' => (int)$table->id,
                'table_name' => (string)$table->name,
                'value_column' => $valueColumn,
                'label_column' => $labelColumn,
                'options' => $options,
            ]);
        } catch (\Throwable $e) {
            return $this->asJson([
                'success' => false,
                'error' => $this->buildFriendlyTableBuilderErrorMessage($e),
            ]);
        }
    }

    private function resolvePrimaryOrFirstColumn(\yii\db\TableSchema $schema, string $preferredColumn = ''): string
    {
        $preferredColumn = trim($preferredColumn);
        if ($preferredColumn !== '' && isset($schema->columns[$preferredColumn])) {
            return $preferredColumn;
        }

        if (isset($schema->columns['id'])) {
            return 'id';
        }

        foreach ($schema->columns as $name => $column) {
            if ($column->isPrimaryKey) {
                return (string)$name;
            }
        }

        foreach ($schema->columns as $name => $column) {
            return (string)$name;
        }

        return 'id';
    }
    
    /**
     * Get foreign key dropdown options for a specific column
     */
    public function actionGetForeignKeyOptions(int $columnId)
    {
        try {
            $this->refreshDbTableColumnsSchema();
            
            $column = DbTableColumn::findOne((int)$columnId);
            if ($column === null) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Column not found with id: ' . $columnId
                ]);
            }
            
            $isFk = $column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key');
            if (!$isFk) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Column is not a foreign key'
                ]);
            }
            
            $refTable = $column->getAttribute('referenced_table_name');
            $refColumn = $column->getAttribute('referenced_column_name');
            
            if (empty($refTable) || empty($refColumn)) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Referenced table or column not configured'
                ]);
            }
            
            $db = $this->getPhysicalDb();
            $schema = $db->schema->getTableSchema($refTable, true);
            if ($schema === null) {
                return $this->asJson([
                    'success' => false,
                    'error' => 'Referenced table not found in database'
                ]);
            }
            
            $referencedTableModel = DbTable::find()->where(['name' => $refTable])->one();
            $valueColumn = $this->resolvePrimaryOrFirstColumn($schema, (string)$refColumn);
            $displayColumn = $this->resolveFkDisplayColumn($db, $refTable, $valueColumn);
            $columnOptions = [];
            foreach ($schema->columns as $columnName => $columnSchema) {
                $columnOptions[] = [
                    'name' => (string)$columnName,
                    'label' => (string)$columnName,
                    'is_primary' => (bool)$columnSchema->isPrimaryKey,
                    'php_type' => strtolower((string)$columnSchema->phpType),
                ];
            }
            
            $rows = (new \yii\db\Query())
                ->select([
                    'value' => $valueColumn,
                    'label' => $displayColumn ?: $valueColumn,
                ])
                ->from($refTable)
                ->orderBy([$displayColumn ?: $valueColumn => SORT_ASC])
                ->limit(500)
                ->all($db);
            
            $options = [];
            foreach ($rows as $row) {
                $value = isset($row['value']) ? (string)$row['value'] : '';
                if ($value === '') continue;
                
                $label = isset($row['label']) ? trim((string)$row['label']) : '';
                if ($label === '' || ($displayColumn === null && preg_match('/^\d+$/', $label))) {
                    $label = 'Record #' . $value;
                }
                
                $options[] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }
            
            return $this->asJson([
                'success' => true,
                'column_name' => $column->name,
                'local_column' => $column->name,
                'referenced_table_id' => $referencedTableModel !== null ? (int)$referencedTableModel->id : null,
                'referenced_table' => $refTable,
                'referenced_value_column' => $valueColumn,
                'display_column' => $displayColumn,
                'columns' => $columnOptions,
                'options' => $options,
            ]);
            
        } catch (\Throwable $e) {
            return $this->asJson([
                'success' => false,
                'error' => $this->buildFriendlyTableBuilderErrorMessage($e),
            ]);
        }
    }
    
    private function resolveFkDisplayColumn($db, string $tableName, string $valueColumn): ?string
    {
        $schema = $db->schema->getTableSchema($tableName, true);
        if ($schema === null) {
            return null;
        }
        
        $normalizedTableName = strtolower(trim($tableName));
        $priorities = array_filter(array_unique([
            'name',
            'nama',
            'title',
            'label',
            $normalizedTableName !== '' ? 'nama_' . $normalizedTableName : '',
            'kode',
            'judul',
            'deskripsi',
            'description',
        ]));
        foreach ($priorities as $candidate) {
            if ($candidate === $valueColumn) continue;
            if (isset($schema->columns[$candidate])) {
                return $candidate;
            }
        }
        
        foreach ($schema->columns as $colName => $colSchema) {
            $phpType = strtolower((string)$colSchema->phpType);
            if ($colName === $valueColumn || $colSchema->isPrimaryKey) continue;
            $normalizedCol = strtolower((string)$colName);
            if (
                $phpType === 'string'
                && stripos($normalizedCol, 'id') === false
                && !in_array($normalizedCol, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'], true)
            ) {
                return $colName;
            }
        }
        
        foreach ($schema->columns as $colName => $colSchema) {
            if ($colName !== $valueColumn && !$colSchema->isPrimaryKey) {
                return $colName;
            }
        }
        
        return null;
    }
}
