<?php

namespace app\services;

use app\components\ProjectSchema;
use app\models\DbTable;
use app\models\DbTableColumn;
use Yii;
use yii\db\Connection;
use yii\db\Query;

class DatabaseSchemaSyncService
{
    private Connection $physicalDb;
    private array $scope;
    private ?int $userId;
    private ?int $projectId;

    public function __construct(Connection $physicalDb, array $scope = [], ?int $userId = null, ?int $projectId = null)
    {
        $this->physicalDb = $physicalDb;
        $this->scope = $scope;
        $this->userId = $userId;
        $this->projectId = $projectId;
    }

    public function syncAllPhysicalTables(): array
    {
        $tableNames = $this->getPhysicalTableNames();
        $synced = [];
        foreach ($tableNames as $tableName) {
            $synced[] = $this->syncTable($tableName)->name;
        }

        $this->markMissingMetadataTables($tableNames);

        return [
            'tables' => $synced,
            'count' => count($synced),
        ];
    }

    public function syncTable(string $tableName): DbTable
    {
        $tableName = strtolower(trim($tableName));
        if ($tableName === '') {
            throw new \RuntimeException('Nama table kosong saat sinkronisasi schema.');
        }

        $schema = $this->physicalDb->schema->getTableSchema($tableName, true);
        if ($schema === null) {
            $this->markMissingMetadataTable($tableName);
            throw new \RuntimeException("Table '{$tableName}' tidak ditemukan di database fisik aktif.");
        }

        $this->physicalDb->schema->refreshTableSchema($tableName);
        $model = $this->resolveMetadataTable($tableName);
        $model->name = $tableName;
        if (trim((string)$model->label) === '') {
            $model->label = $this->humanize($tableName);
        }
        if (trim((string)$model->engine) === '') {
            $model->engine = 'InnoDB';
        }
        if (trim((string)$model->charset) === '') {
            $model->charset = 'utf8mb4';
        }
        if (trim((string)$model->collation) === '') {
            $model->collation = 'utf8mb4_unicode_ci';
        }
        if ($model->hasAttribute('is_created')) {
            $model->is_created = true;
        }
        if ($model->hasAttribute('table_status')) {
            $model->setAttribute('table_status', 'active');
        }
        if ($model->hasAttribute('last_error_message')) {
            $model->setAttribute('last_error_message', null);
        }

        if (!$model->save()) {
            throw new \RuntimeException("Gagal menyimpan metadata table '{$tableName}': " . implode(', ', $model->getErrorSummary(true)));
        }

        DbTableColumn::deleteAll(['table_id' => (int)$model->id]);
        $primaryKeyColumns = array_flip(array_map('strtolower', (array)($schema->primaryKey ?? [])));
        $uniqueColumns = $this->getUniqueColumns($tableName);
        $foreignKeyMap = $this->getForeignKeyMap($tableName);
        $sortOrder = 1;

        foreach ($schema->columns as $columnSchema) {
            $column = $this->buildColumnMetadata((int)$model->id, $columnSchema, $sortOrder, $primaryKeyColumns, $uniqueColumns, $foreignKeyMap);
            if (!$column->save(false)) {
                throw new \RuntimeException("Gagal menyimpan metadata kolom '{$column->name}' pada '{$tableName}'.");
            }
            $sortOrder++;
        }

        return $model;
    }

    public function tableExists(string $tableName): bool
    {
        return $this->physicalDb->schema->getTableSchema(strtolower(trim($tableName)), true) !== null;
    }

    public function getPhysicalTableNames(): array
    {
        $databaseName = $this->getCurrentDatabaseName();
        if ($databaseName === null || stripos((string)$this->physicalDb->dsn, 'mysql:') !== 0) {
            return array_map('strtolower', $this->physicalDb->schema->getTableNames());
        }

        try {
            $rows = (new Query())
                ->select(['table_name' => 'TABLE_NAME'])
                ->from('INFORMATION_SCHEMA.TABLES')
                ->where([
                    'TABLE_SCHEMA' => $databaseName,
                    'TABLE_TYPE' => 'BASE TABLE',
                ])
                ->orderBy(['TABLE_NAME' => SORT_ASC])
                ->all($this->physicalDb);
        } catch (\Throwable $e) {
            Yii::warning('Failed reading INFORMATION_SCHEMA.TABLES: ' . $e->getMessage(), 'table-builder-sync');
            return array_map('strtolower', $this->physicalDb->schema->getTableNames());
        }

        $names = [];
        foreach ($rows as $row) {
            $name = strtolower(trim((string)($row['table_name'] ?? '')));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    public function getCurrentDatabaseName(): ?string
    {
        try {
            $name = $this->physicalDb->createCommand('SELECT DATABASE()')->queryScalar();
            $name = $name !== false ? trim((string)$name) : '';
            return $name !== '' ? $name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveMetadataTable(string $tableName): DbTable
    {
        $criteria = array_merge(['name' => $tableName], $this->scope);
        $models = DbTable::find()->where($criteria)->orderBy(['id' => SORT_ASC])->all();
        $model = $models[0] ?? null;

        foreach (array_slice($models, 1) as $duplicate) {
            DbTableColumn::deleteAll(['table_id' => (int)$duplicate->id]);
            $duplicate->delete();
        }

        if ($model instanceof DbTable) {
            return $model;
        }

        $model = new DbTable();
        $model->name = $tableName;
        $model->label = $this->humanize($tableName);
        $model->description = 'Synced from physical database';
        $model->engine = 'InnoDB';
        $model->charset = 'utf8mb4';
        $model->collation = 'utf8mb4_unicode_ci';
        if ($model->hasAttribute('user_id') && $this->userId !== null) {
            $model->user_id = $this->userId;
        }
        if ($model->hasAttribute('project_id') && ProjectSchema::supportsProjectContext() && $this->projectId !== null) {
            $model->project_id = $this->projectId;
        }

        return $model;
    }

    private function markMissingMetadataTables(array $physicalTableNames): void
    {
        $physicalLookup = array_fill_keys(array_map('strtolower', $physicalTableNames), true);
        $query = DbTable::find();
        foreach ($this->scope as $key => $value) {
            $query->andWhere([$key => $value]);
        }

        foreach ($query->all() as $model) {
            $name = strtolower(trim((string)$model->name));
            if ($name !== '' && !isset($physicalLookup[$name])) {
                $this->applyMissingState($model);
            }
        }
    }

    private function markMissingMetadataTable(string $tableName): void
    {
        $criteria = array_merge(['name' => strtolower(trim($tableName))], $this->scope);
        $models = DbTable::find()->where($criteria)->all();
        foreach ($models as $model) {
            $this->applyMissingState($model);
        }
    }

    private function applyMissingState(DbTable $model): void
    {
        $attributes = [];
        if ($model->hasAttribute('is_created')) {
            $model->is_created = false;
            $attributes[] = 'is_created';
        }
        if ($model->hasAttribute('table_status')) {
            $model->setAttribute('table_status', 'missing');
            $attributes[] = 'table_status';
        }
        if ($model->hasAttribute('last_error_message')) {
            $model->setAttribute('last_error_message', 'Table fisik tidak ditemukan di database aktif.');
            $attributes[] = 'last_error_message';
        }
        if (!empty($attributes)) {
            $model->save(false, array_values(array_unique($attributes)));
        }
    }

    private function buildColumnMetadata(int $tableId, $columnSchema, int $sortOrder, array $primaryKeyColumns, array $uniqueColumns, array $foreignKeyMap): DbTableColumn
    {
        $column = new DbTableColumn();
        $column->table_id = $tableId;
        $column->name = strtolower((string)$columnSchema->name);
        $column->label = $this->humanize($column->name);

        [$type, $length, $enumValues] = $this->inferColumnType((string)($columnSchema->dbType ?? $columnSchema->type ?? 'TEXT'));
        $column->type = $type;
        $column->length = $length;
        $column->is_nullable = (bool)($columnSchema->allowNull ?? true);
        $column->is_primary = isset($primaryKeyColumns[$column->name]);
        $column->is_unique = isset($uniqueColumns[$column->name]) || $column->is_primary;
        $column->default_value = $columnSchema->defaultValue !== null ? (string)$columnSchema->defaultValue : null;
        $column->comment = $columnSchema->comment !== null ? (string)$columnSchema->comment : null;
        $column->sort_order = $sortOrder;

        if ($column->hasAttribute('is_auto_increment')) {
            $column->setAttribute('is_auto_increment', (bool)($columnSchema->autoIncrement ?? false));
        }
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

    private function inferColumnType(string $dbType): array
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
            return [$type, is_numeric($firstArg) ? (int)$firstArg : null, null];
        }

        $type = strtoupper($normalized);
        return [$type !== '' ? $type : 'TEXT', null, null];
    }

    private function getUniqueColumns(string $tableName): array
    {
        if (stripos((string)$this->physicalDb->dsn, 'mysql:') !== 0) {
            return [];
        }

        $rows = $this->physicalDb->createCommand('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '`')->queryAll();
        $uniqueColumns = [];
        foreach ($rows as $row) {
            if ((int)($row['Non_unique'] ?? 1) === 0 && strcasecmp((string)($row['Key_name'] ?? ''), 'PRIMARY') !== 0) {
                $uniqueColumns[strtolower((string)($row['Column_name'] ?? ''))] = true;
            }
        }

        return $uniqueColumns;
    }

    private function getForeignKeyMap(string $tableName): array
    {
        if (stripos((string)$this->physicalDb->dsn, 'mysql:') !== 0) {
            return [];
        }

        $databaseName = $this->getCurrentDatabaseName();
        if ($databaseName === null) {
            return [];
        }

        $rows = (new Query())
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
            ->all($this->physicalDb);

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

    private function humanize(string $name): string
    {
        return ucwords(str_replace('_', ' ', strtolower($name)));
    }
}
