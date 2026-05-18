<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\db\Connection;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\components\SystemFieldService;
use app\models\ProjectUser;
use app\components\ProjectSchema;

class TableBuilderController extends Controller
{
    public $layout = 'dashboard';
    
    private const IDENTIFIER_PATTERN = '/^[a-z][a-z0-9_]*$/';
    private const DB_TABLE_COLUMNS_TABLE = 'db_table_columns';

    /**
     * Refresh db_table_columns schema because schema cache is enabled.
     * This ensures newly-migrated FK metadata columns are recognized immediately.
     */
    private function refreshDbTableColumnsSchema(): void
    {
        Yii::$app->db->schema->refreshTableSchema(self::DB_TABLE_COLUMNS_TABLE);
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

        return null;
    }

    private function canAccessWorkspaceBuilder(): bool
    {
        if ($this->isCommanderSuperAdmin()) {
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
        $schema = Yii::$app->db->schema->getTableSchema(self::DB_TABLE_COLUMNS_TABLE, true);
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

    private function assertForeignKeyMetadataSupport(array $columns): void
    {
        if (!$this->hasForeignKeyPayload($columns)) {
            return;
        }

        if ($this->supportsForeignKeyMetadataColumns()) {
            return;
        }

        Yii::warning([
            'stage' => 'fk_payload_without_metadata_columns',
            'columns' => $columns,
            'table' => self::DB_TABLE_COLUMNS_TABLE,
        ], 'table_builder_fk_debug');

        throw new \RuntimeException(
            "Konfigurasi Foreign Key terdeteksi, tetapi kolom metadata FK di tabel '" . self::DB_TABLE_COLUMNS_TABLE . "' belum tersedia. " .
            "Jalankan migration: php yii migrate/up"
        );
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
        return $this->getPhysicalDb()->schema->getTableSchema($tableName, true) !== null;
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

    private function buildCreateTableSql(DbTable $model, array $columns): string
    {
        $db = $this->getPhysicalDb();
        $columnDefs = [];
        $primaryKeys = [];
        $autoIncrementCandidates = [];
        $foreignKeyDefs = [];
        $usedConstraintNames = [];

        foreach ($columns as $col) {
            $type = $col->getMySQLType();
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

        foreach ($columns as $col) {
            if (!$this->isForeignKeyColumn($col)) {
                continue;
            }

            $referencedTableName = strtolower(trim((string)$col->getAttribute('referenced_table_name')));
            $referencedColumnName = strtolower(trim((string)$col->getAttribute('referenced_column_name')));
            $onDeleteAction = strtoupper(trim((string)$col->getAttribute('on_delete_action')));
            $onUpdateAction = strtoupper(trim((string)$col->getAttribute('on_update_action')));
            if ($onDeleteAction === '') {
                $onDeleteAction = 'RESTRICT';
            }
            if ($onUpdateAction === '') {
                $onUpdateAction = 'RESTRICT';
            }

            if ($referencedTableName === '' || $referencedColumnName === '') {
                throw new \RuntimeException("Foreign key column '{$col->name}' requires referenced table and column names.");
            }
            if (!preg_match(self::IDENTIFIER_PATTERN, $referencedTableName) || !preg_match(self::IDENTIFIER_PATTERN, $referencedColumnName)) {
                throw new \RuntimeException("Foreign key column '{$col->name}' has invalid referenced table or column format.");
            }
            if (!in_array($onDeleteAction, DbTableColumn::FOREIGN_KEY_ACTIONS, true)) {
                throw new \RuntimeException("Foreign key column '{$col->name}' has invalid ON DELETE action.");
            }
            if (!in_array($onUpdateAction, DbTableColumn::FOREIGN_KEY_ACTIONS, true)) {
                throw new \RuntimeException("Foreign key column '{$col->name}' has invalid ON UPDATE action.");
            }

            $constraintName = $this->buildForeignKeyConstraintName($model->name, $col->name, $usedConstraintNames);
            $foreignKeyDefs[] = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$col->name}`) REFERENCES `{$referencedTableName}` (`{$referencedColumnName}`) ON DELETE {$onDeleteAction} ON UPDATE {$onUpdateAction}";
        }

        if (!empty($foreignKeyDefs)) {
            $columnDefs = array_merge($columnDefs, $foreignKeyDefs);
        }

        return "CREATE TABLE `{$model->name}` (\n    " . implode(",\n    ", $columnDefs) . "\n) ENGINE={$model->engine} DEFAULT CHARSET={$model->charset} COLLATE={$model->collation}";
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

            $sql = $this->buildCreateTableSql($model, $columnModels);
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
        $tables = $tablesQuery->all();

        // Build array with tables and their columns
        $tablesWithColumns = [];
        foreach ($tables as $table) {
            $this->syncTableCreationState($table);
            $item = new \stdClass();
            $item->table = $table;
            $item->columns = $table->columns;
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
        $effectiveUserId = $this->getEffectiveUserId();
        if (!$this->isCommanderSuperAdmin() && $effectiveUserId !== null) {
            $model->user_id = $effectiveUserId;
        }
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
                Yii::$app->session->setFlash(
                    'success',
                    'SQL schema berhasil dijalankan dan sinkron ke metadata untuk: ' . implode(', ', $executionResult['tables'])
                );
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                return $this->render('create', [
                    'model' => $model,
                    'savedColumns' => $savedColumns,
                    'foreignKeyReferenceMap' => $foreignKeyReferenceMap,
                    'databaseInfo' => $this->getDatabaseInfo(),
                    'builderMode' => 'sql',
                    'rawSql' => $rawSql,
                    'sqlError' => $e->getMessage(),
                ]);
            }
        }

        if ($model->load(Yii::$app->request->post())) {
            if (!$this->isCommanderSuperAdmin() && $effectiveUserId !== null) {
                $model->user_id = $effectiveUserId;
            }
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

                if ($model->save()) {
                    $transaction = Yii::$app->db->beginTransaction();

                    try {
                        $columnModels = $this->buildColumnModels($columns, (int)$model->id);

                        if (empty($columnModels)) {
                            throw new \RuntimeException('Please add at least one valid column before creating the table.');
                        }

                        $columnErrors = $this->collectColumnErrors($columnModels);
                        if (!empty($columnErrors)) {
                            throw new \RuntimeException(implode('<br>', $columnErrors));
                        }

                        foreach ($columnModels as $column) {
                            if (!$column->save(false)) {
                                throw new \RuntimeException("Failed to save column '{$column->name}'.");
                            }
                        }

                        $transaction->commit();
                        Yii::$app->session->setFlash('tableBuilderSuccess', "Table '{$model->name}' definition saved successfully. Status: pending database creation.");

                        return $this->redirect(['index']);
                    } catch (\Throwable $e) {
                        $transaction->rollBack();
                        $model->delete();
                        Yii::$app->session->setFlash('tableBuilderError', $e->getMessage());
                    }
                } else {
                    // Model validation failed - show error and preserve columns
                    Yii::$app->session->setFlash('tableBuilderError', 'Please fix the errors below: ' . implode(', ', $model->getErrorSummary(true)));
                }
            } catch (\yii\db\IntegrityException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $model->addError('name', 'A table with this name already exists. Please choose a different name.');
                    Yii::$app->session->setFlash('tableBuilderError', 'A table with this name already exists. Please choose a different name.');
                } else {
                    $model->addError('name', 'Database error: ' . $e->getMessage());
                    Yii::$app->session->setFlash('tableBuilderError', 'Database error: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('tableBuilderError', 'Error: ' . $e->getMessage());
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
            return ['success' => false, 'message' => $e->getMessage()];
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

                if ($model->save()) {
                    $transaction = Yii::$app->db->beginTransaction();
                    $columnModels = [];

                    try {
                        $columnModels = $this->buildColumnModels($columns, (int)$model->id);

                        if (empty($columnModels)) {
                            throw new \RuntimeException('Please keep at least one valid column on the table.');
                        }

                        $columnErrors = $this->collectColumnErrors($columnModels);
                        if (!empty($columnErrors)) {
                            throw new \RuntimeException(implode('<br>', $columnErrors));
                        }

                        DbTableColumn::deleteAll(['table_id' => $model->id]);

                        foreach ($columnModels as $column) {
                            if (!$column->save(false)) {
                                throw new \RuntimeException("Failed to save column '{$column->name}'.");
                            }
                        }

                        $transaction->commit();

                        if ($wasPhysicallyCreated) {
                            try {
                                $this->syncUpdatedPhysicalTable($model, $oldTableName, $columnModels);
                                Yii::$app->session->setFlash('tableBuilderSuccess', "Table updated successfully and synced to database table '{$model->name}'.");
                            } catch (\Throwable $syncError) {
                                Yii::error('Failed to sync updated table to database: ' . $syncError->getMessage(), 'app');
                                Yii::$app->session->setFlash('tableBuilderWarning', 'Table definition was updated, but failed to sync physical database table: ' . $syncError->getMessage());
                            }
                        } else {
                            Yii::$app->session->setFlash('tableBuilderSuccess', 'Table updated successfully.');
                        }

                        return $this->redirect(['view', 'id' => $model->id]);
                    } catch (\Throwable $e) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('tableBuilderError', $e->getMessage());
                    }
                } else {
                    Yii::$app->session->setFlash('tableBuilderError', 'Failed to save table: ' . implode(', ', $model->getErrorSummary(true)));
                }
            } catch (\yii\db\IntegrityException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $model->addError('name', 'A table with this name already exists. Please choose a different name.');
                } else {
                    $model->addError('name', 'Database error: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('tableBuilderError', 'Error: ' . $e->getMessage());
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
            Yii::$app->session->setFlash('tableBuilderWarning', 'Table already exists in database!');
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

            $sql = $this->buildCreateTableSql($model, $columns);
            $this->logFkDebug('actionExecuteSql.generated_sql', [
                'table_id' => (int)$model->id,
                'table_name' => (string)$model->name,
                'sql' => $sql,
            ]);

            $db->createCommand("SET FOREIGN_KEY_CHECKS = 0")->execute();
            try {
                $db->createCommand($sql)->execute();
            } finally {
                $db->createCommand("SET FOREIGN_KEY_CHECKS = 1")->execute();
            }

            if (!$this->hasPhysicalTable($model)) {
                throw new \RuntimeException("Table '{$model->name}' was not found after SQL execution.");
            }

            $model->is_created = true;
            $model->save(false, ['is_created']);

            $dbName = $db->createCommand('SELECT DATABASE()')->queryScalar();
            Yii::$app->session->setFlash('tableBuilderSuccess', "Table '{$model->name}' created successfully in database '{$dbName}'.");
            
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('tableBuilderError', 'Failed to create table: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->is_created) {
            try {
                $this->getPhysicalDb()->createCommand("DROP TABLE IF EXISTS `{$model->name}`")->execute();
            } catch (\Exception $e) {
                // Ignore drop errors
            }
        }
        
        $model->delete();
        Yii::$app->session->setFlash('tableBuilderSuccess', 'Table deleted successfully!');

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
        foreach ($columns as $column) {
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }
            $configs[] = $this->buildSpreadsheetColumnConfig($column);
        }

        return $configs;
    }

    private function buildSpreadsheetColumnConfig(DbTableColumn $column): array
    {
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
        } elseif (in_array($type, ['BOOLEAN', 'TINYINT'], true) && ((int)$column->length <= 1 || $column->type === 'BOOLEAN')) {
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
            'name' => $column->name,
            'label' => $column->label ?: $column->name,
            'type' => $type,
            'length' => $column->length,
            'isPrimary' => $isPrimary,
            'isUnique' => $isUnique,
            'isAutoIncrement' => $isAutoIncrement,
            'isForeignKey' => $isForeignKey,
            'isNullable' => (bool)$column->is_nullable,
            'inputType' => $inputType,
            'options' => $options,
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
            foreach ($columns as $column) {
                if (SystemFieldService::shouldHideFromForm($column)) {
                    continue;
                }
                $values[$column->name] = $row[$column->name] ?? null;
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

        if (empty($where)) {
            $validation = $isUsersTable
                ? $this->validateUsersSpreadsheetInsertData($rowData)
                : $this->validateSpreadsheetInsertData($columns, $rowData);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'code' => 'incomplete_row',
                    'message' => 'Belum lengkap',
                    'missing_fields' => $validation['missing_fields'],
                ];
            }

            if (!$isUsersTable) {
                $rowData = SystemFieldService::applyCreateValues($rowData, $tableSchema->columns);
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

            $db->createCommand()->insert($model->name, $rowData)->execute();
            $insertId = $db->getLastInsertID();
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

        $rowData = SystemFieldService::applyUpdateValues($rowData, $tableSchema->columns);
        $db->createCommand()->update($model->name, $rowData, $where)->execute();
        return [
            'success' => true,
            'message' => 'Baris berhasil disimpan.',
            'operation' => 'update',
            'row_key' => $where,
            'row_data' => $rowData,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, DbTableColumn> $columns
     * @return array<string, mixed>
     */
    private function buildGenericSpreadsheetRowData(array $payload, array $columns): array
    {
        $rowData = [];
        foreach ($columns as $column) {
            $columnName = (string)$column->name;
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }
            $rowData[$columnName] = $this->normalizeSpreadsheetCellValue($column, $payload[$columnName] ?? null);
        }

        return $rowData;
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
    private function validateSpreadsheetInsertData(array $columns, array $rowData): array
    {
        $missing = [];
        foreach ($columns as $column) {
            $columnName = (string)$column->name;
            if (SystemFieldService::shouldHideFromForm($column)) {
                continue;
            }
            if ((bool)$column->is_nullable) {
                continue;
            }
            if ($column->default_value !== null && $column->default_value !== '') {
                continue;
            }
            $value = $rowData[$columnName] ?? null;
            if ($value === null || $value === '') {
                $missing[] = $columnName;
            }
        }

        return [
            'valid' => empty($missing),
            'missing_fields' => $missing,
        ];
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

        $inserted = 0;
        $isUsersTable = strtolower((string)$model->name) === 'users';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($isUsersTable) {
                $payload = [];
                foreach ($editableColumns as $index => $column) {
                    $value = $row[$index] ?? $row[$column->name] ?? null;
                    $payload[$column->name] = $value;
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
                    $value = $row[$index] ?? $row[$column->name] ?? null;
                    $rowData[$column->name] = $this->normalizeSpreadsheetCellValue($column, $value);
                }

                $validation = $this->validateSpreadsheetInsertData($columns, $rowData);
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
                $tableSchema = $db->schema->getTableSchema($model->name, true);
                if ($tableSchema !== null) {
                    $rowData = SystemFieldService::applyCreateValues($rowData, $tableSchema->columns);
                }
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
        $tablesToSync = [];

        foreach ($statements as $index => $statement) {
            $validationError = $this->validateSchemaStatement($statement);
            if ($validationError !== null) {
                throw new \RuntimeException('Statement #' . ($index + 1) . ': ' . $validationError);
            }

            $db->createCommand($statement)->execute();

            $tableName = $this->extractAffectedTableName($statement);
            if ($tableName !== null) {
                $tablesToSync[$tableName] = true;
                $db->schema->refreshTableSchema($tableName);
            }
        }

        if (empty($tablesToSync)) {
            throw new \RuntimeException('SQL berhasil dijalankan, tetapi tidak ada table yang bisa disinkronkan.');
        }

        return [
            'statements' => $statements,
            'tables' => $this->syncImportedTables(array_keys($tablesToSync)),
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

    private function syncImportedTables(array $tableNames): array
    {
        $synced = [];
        foreach (array_values(array_unique($tableNames)) as $tableName) {
            $synced[] = $this->syncImportedTable($tableName);
        }

        return $synced;
    }

    private function syncImportedTable(string $tableName): string
    {
        $db = $this->getPhysicalDb();
        $schema = $db->schema->getTableSchema($tableName, true);
        if ($schema === null) {
            throw new \RuntimeException("Table '{$tableName}' tidak ditemukan setelah SQL dijalankan.");
        }

        $activeProjectId = $this->getActiveProjectId();
        $criteria = [
            'name' => strtolower($tableName),
        ];
        $effectiveUserId = $this->getEffectiveUserId();
        if ($effectiveUserId !== null) {
            $criteria['user_id'] = $effectiveUserId;
        }
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $criteria['project_id'] = $activeProjectId;
        }

        $model = DbTable::findOne($criteria);
        if ($model === null) {
            $model = new DbTable();
            if ($effectiveUserId !== null) {
                $model->user_id = $effectiveUserId;
            }
            $this->assignActiveProject($model);
            $model->name = strtolower($tableName);
            $model->label = ucwords(str_replace('_', ' ', strtolower($tableName)));
            $model->description = 'Imported from SQL editor';
            $model->engine = 'InnoDB';
            $model->charset = 'utf8mb4';
            $model->collation = 'utf8mb4_unicode_ci';
        }

        $model->is_created = true;
        if (!$model->save()) {
            throw new \RuntimeException("Gagal menyimpan metadata table '{$tableName}': " . implode(', ', $model->getErrorSummary(true)));
        }

        DbTableColumn::deleteAll(['table_id' => $model->id]);

        $primaryKeyColumns = array_flip(array_map('strtolower', (array)($schema->primaryKey ?? [])));
        $uniqueColumns = $this->getUniqueColumnsFromTable($tableName);
        $foreignKeyMap = $this->getForeignKeyMetadataFromTable($tableName);
        $sortOrder = 1;

        foreach ($schema->columns as $columnSchema) {
            $column = $this->buildImportedColumnModel($model->id, $columnSchema, $sortOrder, $primaryKeyColumns, $uniqueColumns, $foreignKeyMap);
            if (!$column->save(false)) {
                throw new \RuntimeException("Gagal menyimpan metadata kolom '{$column->name}' pada '{$tableName}'.");
            }
            $sortOrder++;
        }

        $db->schema->refreshTableSchema($tableName);
        return $model->name;
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
                    'is_nullable' => (bool)$col->is_nullable,
                    'is_primary' => SystemFieldService::isPrimaryKey($col, $schemaColumn),
                    'is_system_field' => SystemFieldService::isSystemManagedField($col, $schemaColumn),
                    'is_auto_increment' => SystemFieldService::isAutoIncrement($col, $schemaColumn),
                    'is_foreign_key' => SystemFieldService::isForeignKey($col, $schemaColumn),
                    'debug_system_field' => SystemFieldService::decisionPayload($col, 'table-builder/get-columns', $schemaColumn),
                    'default_value' => $col->default_value,
                    'max_length' => $col->length,
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
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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
                'error' => $e->getMessage()
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
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
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
            
            $displayColumn = $this->resolveFkDisplayColumn($db, $refTable, $refColumn);
            
            $rows = (new \yii\db\Query())
                ->select([
                    'value' => $refColumn,
                    'label' => $displayColumn ?: $refColumn,
                ])
                ->from($refTable)
                ->orderBy([$displayColumn ?: $refColumn => SORT_ASC])
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
                'referenced_table' => $refTable,
                'display_column' => $displayColumn,
                'options' => $options,
            ]);
            
        } catch (\Throwable $e) {
            return $this->asJson([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function resolveFkDisplayColumn($db, string $tableName, string $valueColumn): ?string
    {
        $schema = $db->schema->getTableSchema($tableName, true);
        if ($schema === null) {
            return null;
        }
        
        $priorities = ['name', 'nama', 'title', 'judul', 'label', 'deskripsi', 'description'];
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
            if ($phpType === 'string' && stripos($normalizedCol, 'id') === false) {
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
