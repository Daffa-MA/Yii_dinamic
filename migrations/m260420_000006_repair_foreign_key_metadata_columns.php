<?php

use yii\db\Migration;

class m260420_000006_repair_foreign_key_metadata_columns extends Migration
{
    private const TABLE_NAME = 'db_table_columns';

    private function hasColumn(string $columnName): bool
    {
        $schema = $this->db->schema->getTableSchema(self::TABLE_NAME, true);
        return $schema !== null && isset($schema->columns[$columnName]);
    }

    public function safeUp()
    {
        if ($this->db->schema->getTableSchema(self::TABLE_NAME, true) === null) {
            throw new \RuntimeException("Table '" . self::TABLE_NAME . "' does not exist.");
        }

        if (!$this->hasColumn('is_foreign_key')) {
            $this->addColumn(
                self::TABLE_NAME,
                'is_foreign_key',
                $this->boolean()->notNull()->defaultValue(false)
            );
        }

        if (!$this->hasColumn('referenced_table_name')) {
            $this->addColumn(self::TABLE_NAME, 'referenced_table_name', $this->string(100));
        }

        if (!$this->hasColumn('referenced_column_name')) {
            $this->addColumn(self::TABLE_NAME, 'referenced_column_name', $this->string(100));
        }

        if (!$this->hasColumn('on_delete_action')) {
            $this->addColumn(
                self::TABLE_NAME,
                'on_delete_action',
                $this->string(20)->notNull()->defaultValue('RESTRICT')
            );
        }

        if (!$this->hasColumn('on_update_action')) {
            $this->addColumn(
                self::TABLE_NAME,
                'on_update_action',
                $this->string(20)->notNull()->defaultValue('RESTRICT')
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema(self::TABLE_NAME, true) === null) {
            return;
        }

        if ($this->hasColumn('on_update_action')) {
            $this->dropColumn(self::TABLE_NAME, 'on_update_action');
        }

        if ($this->hasColumn('on_delete_action')) {
            $this->dropColumn(self::TABLE_NAME, 'on_delete_action');
        }

        if ($this->hasColumn('referenced_column_name')) {
            $this->dropColumn(self::TABLE_NAME, 'referenced_column_name');
        }

        if ($this->hasColumn('referenced_table_name')) {
            $this->dropColumn(self::TABLE_NAME, 'referenced_table_name');
        }

        if ($this->hasColumn('is_foreign_key')) {
            $this->dropColumn(self::TABLE_NAME, 'is_foreign_key');
        }
    }
}

