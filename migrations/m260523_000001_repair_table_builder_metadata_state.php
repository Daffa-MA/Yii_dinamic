<?php

use yii\db\Migration;

class m260523_000001_repair_table_builder_metadata_state extends Migration
{
    private function hasColumn(string $tableName, string $columnName): bool
    {
        $schema = $this->db->schema->getTableSchema($tableName, true);
        return $schema !== null && isset($schema->columns[$columnName]);
    }

    public function safeUp()
    {
        $dbTableColumns = $this->db->schema->getTableSchema('db_table_columns', true);
        if ($dbTableColumns !== null) {
            if (!$this->hasColumn('db_table_columns', 'is_foreign_key')) {
                $this->addColumn('db_table_columns', 'is_foreign_key', $this->boolean()->notNull()->defaultValue(false));
            }
            if (!$this->hasColumn('db_table_columns', 'referenced_table_name')) {
                $this->addColumn('db_table_columns', 'referenced_table_name', $this->string(100));
            }
            if (!$this->hasColumn('db_table_columns', 'referenced_column_name')) {
                $this->addColumn('db_table_columns', 'referenced_column_name', $this->string(100));
            }
            if (!$this->hasColumn('db_table_columns', 'on_delete_action')) {
                $this->addColumn('db_table_columns', 'on_delete_action', $this->string(20)->notNull()->defaultValue('RESTRICT'));
            }
            if (!$this->hasColumn('db_table_columns', 'on_update_action')) {
                $this->addColumn('db_table_columns', 'on_update_action', $this->string(20)->notNull()->defaultValue('RESTRICT'));
            }
        }

        $dbTables = $this->db->schema->getTableSchema('db_tables', true);
        if ($dbTables !== null) {
            if (!$this->hasColumn('db_tables', 'table_status')) {
                $this->addColumn('db_tables', 'table_status', $this->string(20)->notNull()->defaultValue('pending'));
            }
            if (!$this->hasColumn('db_tables', 'last_error_message')) {
                $this->addColumn('db_tables', 'last_error_message', $this->text());
            }
        }
    }

    public function safeDown()
    {
        echo "m260523_000001_repair_table_builder_metadata_state cannot be safely reverted.\n";
        return false;
    }
}
