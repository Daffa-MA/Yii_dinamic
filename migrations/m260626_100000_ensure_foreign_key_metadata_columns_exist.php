<?php

use yii\db\Migration;

/**
 * Class m260626_100000_ensure_foreign_key_metadata_columns_exist
 * 
 * This migration ensures that the db_table_columns table has the necessary columns 
 * for foreign key metadata. It checks for the existence of each column before 
 * adding it, making it safe to run even if the columns already exist.
 */
class m260626_100000_ensure_foreign_key_metadata_columns_exist extends Migration
{
    private const TABLE_NAME = 'db_table_columns';

    /**
     * Checks if a column exists in the table.
     *
     * @param string $columnName
     * @return bool
     */
    private function hasColumn(string $columnName): bool
    {
        $schema = $this->db->schema->getTableSchema(self::TABLE_NAME, true);
        return $schema !== null && isset($schema->columns[$columnName]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if table exists before proceeding
        if ($this->db->schema->getTableSchema(self::TABLE_NAME, true) === null) {
            echo "Table '" . self::TABLE_NAME . "' does not exist. Skipping migration.\n";
            return;
        }

        // Add 'is_foreign_key' column if it doesn't exist
        if (!$this->hasColumn('is_foreign_key')) {
            $this->addColumn(
                self::TABLE_NAME,
                'is_foreign_key',
                $this->boolean()->notNull()->defaultValue(false)
            );
        }

        // Add 'referenced_table_name' column if it doesn't exist
        if (!$this->hasColumn('referenced_table_name')) {
            $this->addColumn(self::TABLE_NAME, 'referenced_table_name', $this->string(100));
        }

        // Add 'referenced_column_name' column if it doesn't exist
        if (!$this->hasColumn('referenced_column_name')) {
            $this->addColumn(self::TABLE_NAME, 'referenced_column_name', $this->string(100));
        }

        // Add 'on_delete_action' column if it doesn't exist
        if (!$this->hasColumn('on_delete_action')) {
            $this->addColumn(
                self::TABLE_NAME,
                'on_delete_action',
                $this->string(20)->notNull()->defaultValue('RESTRICT')
            );
        }

        // Add 'on_update_action' column if it doesn't exist
        if (!$this->hasColumn('on_update_action')) {
            $this->addColumn(
                self::TABLE_NAME,
                'on_update_action',
                $this->string(20)->notNull()->defaultValue('RESTRICT')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration is for ensuring columns exist. The safeDown method could 
        // drop them, but it's safer to leave them to avoid accidental data loss 
        // if another migration depends on them. For completeness, the drop logic
        // is included but commented out.

        /*
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
        */

        echo "m260626_100000_ensure_foreign_key_metadata_columns_exist does not support reverting.\n";
        return false;
    }
}
