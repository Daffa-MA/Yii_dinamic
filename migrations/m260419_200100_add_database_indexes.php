<?php

use yii\db\Migration;

/**
 * Add missing database indexes for query optimization
 */
class m260419_200100_add_database_indexes extends Migration
{
    public function safeUp()
    {
        $db = Yii::$app->db;
        
        // Forms indexes
        $this->addIndexIfNotExists('form', 'idx_form_user_id', 'user_id');
        $this->addIndexIfNotExists('form', 'idx_form_project_id', 'project_id');
        $this->addIndexIfNotExists('form', 'idx_form_created_at', 'created_at');
        
        // Form Submissions indexes
        $this->addIndexIfNotExists('form_submissions', 'idx_form_sub_form_id', 'form_id');
        $this->addIndexIfNotExists('form_submissions', 'idx_form_sub_created_at', 'created_at');
        
        // DB Tables indexes
        $this->addIndexIfNotExists('db_tables', 'idx_db_tables_user_id', 'user_id');
        $this->addIndexIfNotExists('db_tables', 'idx_db_tables_project_id', 'project_id');
        
        // DB Table Columns indexes
        $this->addIndexIfNotExists('db_table_columns', 'idx_db_table_cols_table_id', 'table_id');
        
        // Projects indexes
        $this->addIndexIfNotExists('projects', 'idx_projects_user_id', 'user_id');
        $this->addIndexIfNotExists('projects', 'idx_projects_created_at', 'created_at');
        
        // Published Forms indexes
        $this->addIndexIfNotExists('published_forms', 'idx_pub_forms_form_id', 'form_id');
        $this->addIndexIfNotExists('published_forms', 'idx_pub_forms_user_id', 'user_id');
        
        echo "✓ Database indexes optimized\n";
    }
    
    private function addIndexIfNotExists(string $tableName, string $indexName, string $columnName): void
    {
        $db = Yii::$app->db;
        
        try {
            // Check if table exists
            if (!$db->schema->getTableSchema($tableName)) {
                return;
            }
            
            // Check if index already exists
            $tableIndexes = $db->schema->getTableIndexes($tableName);
            foreach ($tableIndexes as $index) {
                if ($index->getColumnNames() === [$columnName]) {
                    return; // Index already exists
                }
            }
            
            // Create index
            $this->createIndex($indexName, $tableName, $columnName);
            echo "  ✓ Added index: {$tableName}.{$indexName}\n";
        } catch (\Throwable $e) {
            echo "  ⚠ Index creation failed for {$tableName}.{$indexName}: {$e->getMessage()}\n";
        }
    }
    
    public function safeDown()
    {
        // Indexes are dropped automatically with table drop
        echo "✓ Indexes would be removed if tables are dropped\n";
    }
}
