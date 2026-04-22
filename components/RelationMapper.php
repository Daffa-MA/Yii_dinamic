<?php

namespace app\components;

use app\models\DbTable;
use Yii;
use yii\db\Query;
use yii\db\TableSchema;

class RelationMapper
{
    /** @var \yii\db\Connection|null */
    private $db;

    public function __construct(?\yii\db\Connection $db = null)
    {
        $this->db = $db;
    }

    private function getDb(): \yii\db\Connection
    {
        return $this->db ?? Yii::$app->db;
    }

    /**
     * Build FK configuration map indexed by source column name.
     *
     * @return array<string, array<string, mixed>>
     */
    public function buildForeignKeyConfig(DbTable $table): array
    {
        $db = $this->getDb();
        $tableName = (string)$table->name;
        if ($tableName === '') {
            return [];
        }

        // Prioritize Metadata from the system instead of just physical DB
        // This ensures dropdowns work even if physical FKs are not yet synced or slightly different.
        $config = [];
        $columns = $table->getColumns()->all();
        
        foreach ($columns as $column) {
            if (!$column->hasAttribute('is_foreign_key') || !(bool)$column->getAttribute('is_foreign_key')) {
                continue;
            }

            $sourceColumn = (string)$column->name;
            $referencedTable = (string)$column->getAttribute('referenced_table_name');
            $referencedColumn = (string)$column->getAttribute('referenced_column_name');

            if ($referencedTable === '' || $referencedColumn === '') {
                continue;
            }

            $displayColumn = $this->resolveDisplayColumn($referencedTable, $referencedColumn);
            $options = $this->loadReferenceOptions($referencedTable, $referencedColumn, $displayColumn);

            $config[$sourceColumn] = [
                'field' => $sourceColumn,
                'fieldLabel' => (string)($column->label ?? $this->humanizeColumnName($sourceColumn)),
                'constraintName' => 'fk_' . $tableName . '_' . $sourceColumn,
                'referencedTable' => $referencedTable,
                'referencedColumn' => $referencedColumn,
                'displayColumn' => $displayColumn,
                'quickAddFields' => $this->resolveQuickAddFields($referencedTable, $referencedColumn, $displayColumn),
                'options' => $options,
            ];
        }

        return $config;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function detectForeignKeys(string $tableName): array
    {
        $db = $this->getDb();
        $dbName = $db->createCommand('SELECT DATABASE()')->queryScalar();
        if (!is_string($dbName) || $dbName === '') {
            return [];
        }

        return (new Query())
            ->select([
                'column_name' => 'kcu.COLUMN_NAME',
                'referenced_table_name' => 'kcu.REFERENCED_TABLE_NAME',
                'referenced_column_name' => 'kcu.REFERENCED_COLUMN_NAME',
                'constraint_name' => 'kcu.CONSTRAINT_NAME',
            ])
            ->from(['kcu' => 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE'])
            ->where([
                'kcu.TABLE_SCHEMA' => $dbName,
                'kcu.TABLE_NAME' => $tableName,
            ])
            ->andWhere(['not', ['kcu.REFERENCED_TABLE_NAME' => null]])
            ->orderBy(['kcu.ORDINAL_POSITION' => SORT_ASC])
            ->all($db);
    }

    private function resolveDisplayColumn(string $tableName, string $referencedColumn): ?string
    {
        $db = $this->getDb();
        $schema = $db->schema->getTableSchema($tableName, true);
        if (!$schema instanceof TableSchema) {
            return null;
        }

        $priorities = ['name', 'nama', 'title', 'judul', 'label', 'deskripsi', 'description'];
        foreach ($priorities as $candidate) {
            if ($candidate === $referencedColumn) {
                continue;
            }
            if (isset($schema->columns[$candidate])) {
                return $candidate;
            }
        }

        foreach ($schema->columns as $columnName => $columnSchema) {
            $phpType = strtolower((string)$columnSchema->phpType);
            if ($columnName === $referencedColumn || $columnSchema->isPrimaryKey) {
                continue;
            }
            $normalizedColumn = strtolower((string)$columnName);
            if (
                in_array($phpType, ['string'], true)
                && stripos($normalizedColumn, 'id') === false
                && !in_array($normalizedColumn, ['created_at', 'updated_at', 'deleted_at'], true)
            ) {
                return $columnName;
            }
        }

        foreach ($schema->columns as $columnName => $columnSchema) {
            if ($columnName !== $referencedColumn && !$columnSchema->isPrimaryKey) {
                return $columnName;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function loadReferenceOptions(string $tableName, string $valueColumn, ?string $labelColumn): array
    {
        $db = $this->getDb();
        $tableSchema = $db->schema->getTableSchema($tableName, true);
        if (!$tableSchema instanceof TableSchema || !isset($tableSchema->columns[$valueColumn])) {
            return [];
        }

        $resolvedLabelColumn = $labelColumn;
        if ($resolvedLabelColumn === null || !isset($tableSchema->columns[$resolvedLabelColumn])) {
            $resolvedLabelColumn = $valueColumn;
        }

        $rows = (new Query())
            ->select([
                'value' => $valueColumn,
                'label' => $resolvedLabelColumn,
            ])
            ->from($tableName)
            ->orderBy([$resolvedLabelColumn => SORT_ASC])
            ->limit(500)
            ->all($db);

        $options = [];
        foreach ($rows as $row) {
            $value = isset($row['value']) ? (string)$row['value'] : '';
            if ($value === '') {
                continue;
            }

            $label = isset($row['label']) ? trim((string)$row['label']) : '';
            if ($label === '' || ($resolvedLabelColumn === $valueColumn && preg_match('/^\d+$/', $label))) {
                $label = 'Record #' . $value;
            }

            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{name:string,label:string,inputType:string,required:bool}>
     */
    private function resolveQuickAddFields(string $tableName, string $valueColumn, ?string $displayColumn): array
    {
        $db = $this->getDb();
        $schema = $db->schema->getTableSchema($tableName, true);
        if (!$schema instanceof TableSchema) {
            return [];
        }

        $fields = [];
        $requiredColumns = [];
        foreach ($schema->columns as $columnName => $columnSchema) {
            if ($columnSchema->autoIncrement) {
                continue;
            }
            if ($columnName === $valueColumn && $columnSchema->autoIncrement) {
                continue;
            }
            if (!$columnSchema->allowNull && $columnSchema->defaultValue === null) {
                $requiredColumns[] = $columnName;
            }
        }

        if ($displayColumn !== null && isset($schema->columns[$displayColumn]) && !in_array($displayColumn, $requiredColumns, true)) {
            array_unshift($requiredColumns, $displayColumn);
        }

        $requiredColumns = array_values(array_unique($requiredColumns));

        foreach ($requiredColumns as $columnName) {
            if (!isset($schema->columns[$columnName])) {
                continue;
            }
            $columnSchema = $schema->columns[$columnName];
            $fields[] = [
                'name' => $columnName,
                'label' => $this->humanizeColumnName($columnName),
                'inputType' => $this->mapInputType((string)$columnSchema->type),
                'required' => true,
            ];
        }

        return $fields;
    }

    private function mapInputType(string $dbType): string
    {
        $type = strtolower($dbType);
        if (strpos($type, 'int') !== false || strpos($type, 'decimal') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false) {
            return 'number';
        }
        if (strpos($type, 'date') !== false && strpos($type, 'time') === false) {
            return 'date';
        }
        if (strpos($type, 'time') !== false || strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
            return 'datetime-local';
        }

        return 'text';
    }

    private function humanizeColumnName(string $columnName): string
    {
        return ucwords(str_replace('_', ' ', $columnName));
    }
}
