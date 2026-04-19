<?php

use yii\db\Migration;

/**
 * Register existing produk and kategori tables in db_tables
 */
class m260419_140200_register_produk_kategori_tables extends Migration
{
    public function safeUp()
    {
        $db = Yii::$app->db;
        
        // Check if tables exist in database
        $tableSchema = $db->schema->getTableSchema('produk');
        if (!$tableSchema) {
            echo "⚠ Table 'produk' not found in database. Skipping registration.\n";
            return;
        }
        
        // Get user_id (assume admin user with id=1)
        $userId = 1;
        
        // Check if user table exists first
        $userTableSchema = $db->schema->getTableSchema('user');
        if ($userTableSchema) {
            // Only check if user exists if table exists
            $userExists = (new \yii\db\Query())
                ->from('user')
                ->where(['id' => $userId])
                ->exists();
            
            if (!$userExists) {
                echo "⚠ User ID 1 not found. Please manually register tables in UI.\n";
                return;
            }
        } else {
            echo "⚠ User table doesn't exist. Skipping user-specific registration.\n";
            echo "   You can manually register tables in Table Builder UI later.\n";
            return;
        }
        
        // Register kategori table if not exists
        $kategoriExists = (new \yii\db\Query())
            ->from('db_tables')
            ->where(['name' => 'kategori', 'user_id' => $userId])
            ->exists();
        
        if (!$kategoriExists) {
            $this->insert('db_tables', [
                'name' => 'kategori',
                'label' => 'Kategori',
                'user_id' => $userId,
                'project_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            $kategoriTableId = $db->lastInsertID;
            $this->registerColumnsForTable('kategori', $kategoriTableId);
            echo "✓ Table 'kategori' registered in db_tables\n";
        }
        
        // Register produk table if not exists
        $produkExists = (new \yii\db\Query())
            ->from('db_tables')
            ->where(['name' => 'produk', 'user_id' => $userId])
            ->exists();
        
        if (!$produkExists) {
            $this->insert('db_tables', [
                'name' => 'produk',
                'label' => 'Produk',
                'user_id' => $userId,
                'project_id' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            $produkTableId = $db->lastInsertID;
            $this->registerColumnsForTable('produk', $produkTableId);
            echo "✓ Table 'produk' registered in db_tables\n";
        }
    }
    
    private function registerColumnsForTable($tableName, $tableId)
    {
        $db = Yii::$app->db;
        $tableSchema = $db->schema->getTableSchema($tableName, true);
        
        if (!$tableSchema) {
            return;
        }
        
        $sortOrder = 1;
        foreach ($tableSchema->columns as $column) {
            $columnName = $column->name;
            
            // Skip if already exists
            $exists = (new \yii\db\Query())
                ->from('db_table_columns')
                ->where(['table_id' => $tableId, 'name' => $columnName])
                ->exists();
            
            if ($exists) {
                continue;
            }
            
            $columnType = $this->getColumnType($column);
            $isPrimary = $column->isPrimaryKey ? 1 : 0;
            $isAutoIncrement = $column->autoIncrement ? 1 : 0;
            $isNullable = !$column->allowNull ? 0 : 1;
            
            $this->insert('db_table_columns', [
                'table_id' => $tableId,
                'name' => $columnName,
                'label' => $this->humanizeColumnName($columnName),
                'type' => $columnType,
                'nullable' => $isNullable,
                'default' => $column->defaultValue,
                'is_primary_key' => $isPrimary,
                'is_auto_increment' => $isAutoIncrement,
                'sort_order' => $sortOrder++,
            ]);
        }
    }
    
    private function getColumnType($column)
    {
        $phpType = $column->phpType;
        $dbType = $column->dbType;
        
        if ($phpType === 'integer') {
            return 'INT';
        } elseif ($phpType === 'double') {
            return 'DECIMAL';
        } elseif ($phpType === 'boolean') {
            return 'TINYINT';
        } elseif ($phpType === 'string') {
            return 'VARCHAR';
        } else {
            return $dbType ?: 'VARCHAR';
        }
    }
    
    private function humanizeColumnName($columnName)
    {
        return ucwords(str_replace('_', ' ', $columnName));
    }
    
    public function safeDown()
    {
        // Delete registered tables (keep data for safety)
        $this->delete('db_table_columns', ['table_id' => new \yii\db\Expression('(SELECT id FROM db_tables WHERE name IN ("kategori", "produk"))')]);
        $this->delete('db_tables', ['name' => ['kategori', 'produk']]);
        
        echo "✓ Table registrations removed\n";
    }
}
