<?php

use yii\db\Migration;

class m260420_000007_add_dashboard_performance_indexes extends Migration
{
    public function safeUp()
    {
        $this->addIndexIfMissing('db_table_columns', 'idx_db_table_columns_table_sort', ['table_id', 'sort_order']);
        $this->addIndexIfMissing('forms', 'idx_forms_user_project_created_id', ['user_id', 'project_id', 'created_at', 'id']);
        $this->addIndexIfMissing('db_tables', 'idx_db_tables_user_project_created_id', ['user_id', 'project_id', 'created_at', 'id']);
        $this->addIndexIfMissing('projects', 'idx_projects_user_created_id', ['user_id', 'created_at', 'id']);
        $this->addIndexIfMissing('published_forms', 'idx_published_forms_form_user_created_id', ['form_id', 'user_id', 'created_at', 'id']);
    }

    public function safeDown()
    {
        $this->dropIndexIfExists('published_forms', 'idx_published_forms_form_user_created_id');
        $this->dropIndexIfExists('projects', 'idx_projects_user_created_id');
        $this->dropIndexIfExists('db_tables', 'idx_db_tables_user_project_created_id');
        $this->dropIndexIfExists('forms', 'idx_forms_user_project_created_id');
        $this->dropIndexIfExists('db_table_columns', 'idx_db_table_columns_table_sort');
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns): void
    {
        $tableSchema = $this->db->schema->getTableSchema($tableName, true);
        if ($tableSchema === null) {
            return;
        }

        foreach ($columns as $columnName) {
            if (!isset($tableSchema->columns[$columnName])) {
                return;
            }
        }

        foreach ($this->db->schema->getTableIndexes($tableName) as $index) {
            if ($index->getColumnNames() === $columns) {
                return;
            }
        }

        $this->createIndex($indexName, $tableName, $columns);
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        try {
            $this->dropIndex($indexName, $tableName);
        } catch (\Throwable $e) {
            // Ignore missing index during rollback.
        }
    }
}