<?php

use yii\db\Expression;
use yii\db\Migration;

class m260419_000004_add_db_insert_fields_to_forms extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->schema->getTableSchema('forms', true);
        if ($tableSchema === null) {
            throw new \RuntimeException("Table 'forms' was not found.");
        }

        if (!isset($tableSchema->columns['db_table_id'])) {
            $this->addColumn('forms', 'db_table_id', $this->integer()->null());
        }

        if (!isset($tableSchema->columns['insert_to_table'])) {
            $this->addColumn('forms', 'insert_to_table', $this->boolean()->notNull()->defaultValue(0));
        }

        try {
            $this->createIndex('idx-forms-db_table_id', 'forms', 'db_table_id');
        } catch (\Throwable $e) {
            // Index may already exist on some environments.
        }

        if ($this->db->driverName !== 'sqlite') {
            try {
                $this->addForeignKey('fk-forms-db_table_id', 'forms', 'db_table_id', 'db_tables', 'id', 'SET NULL', 'CASCADE');
            } catch (\Throwable $e) {
                // Foreign key may already exist.
            }
        }

        $tableSchema = $this->db->schema->getTableSchema('forms', true);
        if ($tableSchema !== null && isset($tableSchema->columns['table_id']) && isset($tableSchema->columns['db_table_id'])) {
            $this->update(
                'forms',
                ['db_table_id' => new Expression('table_id')],
                ['and', ['db_table_id' => null], ['not', ['table_id' => null]]]
            );
        }

        $tableSchema = $this->db->schema->getTableSchema('forms', true);
        if ($tableSchema !== null && isset($tableSchema->columns['storage_type']) && isset($tableSchema->columns['insert_to_table'])) {
            $this->update(
                'forms',
                ['insert_to_table' => new Expression("CASE WHEN storage_type = 'database' THEN 1 ELSE 0 END")]
            );
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->schema->getTableSchema('forms', true);
        if ($tableSchema === null) {
            return;
        }

        if ($this->db->driverName !== 'sqlite' && isset($tableSchema->columns['db_table_id'])) {
            try {
                $this->dropForeignKey('fk-forms-db_table_id', 'forms');
            } catch (\Throwable $e) {
                // Foreign key might already be removed.
            }
        }

        try {
            $this->dropIndex('idx-forms-db_table_id', 'forms');
        } catch (\Throwable $e) {
            // Index might already be removed.
        }

        $tableSchema = $this->db->schema->getTableSchema('forms', true);
        if ($tableSchema !== null && isset($tableSchema->columns['insert_to_table'])) {
            $this->dropColumn('forms', 'insert_to_table');
        }
        if ($tableSchema !== null && isset($tableSchema->columns['db_table_id'])) {
            $this->dropColumn('forms', 'db_table_id');
        }
    }
}
