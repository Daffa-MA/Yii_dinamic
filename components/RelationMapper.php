<?php

namespace app\components;

use app\models\DbTable;
use Yii;
use yii\db\Query;
use yii\db\TableSchema;

class RelationMapper
{
    /**
     * Build FK configuration map indexed by source column name.
     *
     * @return array<string, array<string, mixed>>
     */
    public function buildForeignKeyConfig(DbTable $table): array
    {
        $tableName = (string)$table->name;
        if ($tableName === '' || Yii::$app->db->driverName !== 'mysql') {
            return [];
        }

        $foreignKeys = $this->detectForeignKeys($tableName);
        if (empty($foreignKeys)) {
            return [];
        }

        $columnLabels = [];
        $columns = $table->getColumns()->asArray()->all();
        foreach ($columns as $column) {
            $columnName = (string)($column['name'] ?? '');
            if ($columnName === '') {
                continue;
            }
            $columnLabels[$columnName] = (string)($column['label'] ?? $this->humanizeColumnName($columnName));
        }

        $config = [];
        foreach ($foreignKeys as $fk) {
            $sourceColumn = (string)$fk['column_name'];
            $referencedTable = (string)$fk['referenced_table_name'];
            $referencedColumn = (string)$fk['referenced_column_name'];
            $displayColumn = $this->resolveDisplayColumn($referencedTable, $referencedColumn);
            $options = $this->loadReferenceOptions($referencedTable, $referencedColumn, $displayColumn);

            $config[$sourceColumn] = [
                'field' => $sourceColumn,
                'fieldLabel' => $columnLabels[$sourceColumn] ?? $this->humanizeColumnName($sourceColumn),
                'constraintName' => (string)$fk['constraint_name'],
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
        $dbName = Yii::$app->db->createCommand('SELECT DATABASE()')->queryScalar();
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
            ->all();
    }

    private function resolveDisplayColumn(string $tableName, string $referencedColumn): ?string
    {
        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
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
        $tableSchema = Yii::$app->db->schema->getTableSchema($tableName, true);
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
            ->all();

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
        $schema = Yii::$app->db->schema->getTableSchema($tableName, true);
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
