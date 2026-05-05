<?php

use yii\db\Migration;

/**
 * Setup master_menu and master_page tables for existing projects
 */
class yii2basic_m250503_setup_master_tables extends Migration
{
    public function safeUp()
    {
        // Check if master_menu table exists
        $menuTableExists = $this->db->getTableSchema('master_menu', true) !== null;
        $pageTableExists = $this->db->getTableSchema('master_page', true) !== null;
        
        if (!$menuTableExists) {
            $this->createTable('master_menu', [
                'id' => $this->primaryKey(),
                'parent_id' => $this->integer(),
                'page_id' => $this->integer(),
                'name' => $this->string(100)->notNull(),
                'icon' => $this->string(50),
                'sort_order' => $this->integer()->defaultValue(0),
                'is_active' => $this->integer(1)->defaultValue(1),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);
            
            $this->addForeignKey(
                'fk_master_menu_parent',
                'master_menu',
                'parent_id',
                'master_menu',
                'id',
                'SET NULL',
                'CASCADE'
            );
            
            $this->addForeignKey(
                'fk_master_menu_page',
                'master_menu',
                'page_id',
                'master_page',
                'id',
                'SET NULL',
                'CASCADE'
            );
            
            $this->createIndex('idx_master_menu_parent', 'master_menu', 'parent_id');
            $this->createIndex('idx_master_menu_page', 'master_menu', 'page_id');
            $this->createIndex('idx_master_menu_sort', 'master_menu', 'sort_order');
            $this->createIndex('idx_master_menu_active', 'master_menu', 'is_active');
            
            echo "Created master_menu table\n";
        } else {
            echo "master_menu table already exists, skipping\n";
        }
        
        if (!$pageTableExists) {
            $this->createTable('master_page', [
                'id' => $this->primaryKey(),
                'title' => $this->string(255)->notNull(),
                'description' => $this->string(500),
                'layout_type' => $this->string(50)->defaultValue('single_column'),
                'is_active' => $this->integer(1)->defaultValue(1),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);
            
            $this->createIndex('idx_master_page_active', 'master_page', 'is_active');
            
            echo "Created master_page table\n";
        } else {
            echo "master_page table already exists, skipping\n";
        }
        
        // Insert default dashboard data if tables are empty
        if ($menuTableExists && $pageTableExists) {
            $dashboardPage = (new \yii\db\Query())
                ->from('master_page')
                ->where(['title' => 'Dashboard'])
                ->one();
            
            if ($dashboardPage === null) {
                $this->insert('master_page', [
                    'title' => 'Dashboard',
                    'description' => 'Halaman utama project',
                    'layout_type' => 'single_column',
                    'is_active' => 1,
                ]);
                echo "Inserted default Dashboard page\n";
            }
            
            $dashboardMenu = (new \yii\db\Query())
                ->from('master_menu')
                ->where(['name' => 'Dashboard'])
                ->one();
            
            if ($dashboardMenu === null) {
                $this->insert('master_menu', [
                    'parent_id' => null,
                    'page_id' => 1,
                    'name' => 'Dashboard',
                    'icon' => 'dashboard',
                    'sort_order' => 1,
                    'is_active' => 1,
                ]);
                echo "Inserted default Dashboard menu\n";
            }
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_master_menu_page', 'master_menu');
        $this->dropForeignKey('fk_master_menu_parent', 'master_menu');
        $this->dropTable('master_menu');
        $this->dropTable('master_page');
    }
}
