<?php

use yii\db\Migration;

class m260412_000008_fix_db_tables_unique_index extends Migration
{
    public function safeUp()
    {
        try {
            $this->dropIndex('name', 'db_tables');
        } catch (\Throwable $e) {
            // Ignore when the legacy unique index does not exist.
        }

        try {
            $this->createIndex('uq-db_tables-user_id-name', 'db_tables', ['user_id', 'name'], true);
        } catch (\Throwable $e) {
            // Ignore when the composite unique index already exists.
        }
    }

    public function safeDown()
    {
        try {
            $this->dropIndex('uq-db_tables-user_id-name', 'db_tables');
        } catch (\Throwable $e) {
            // Ignore when the index was already removed.
        }

        try {
            $this->createIndex('name', 'db_tables', 'name', true);
        } catch (\Throwable $e) {
            // Ignore when the legacy index already exists.
        }
    }
}
