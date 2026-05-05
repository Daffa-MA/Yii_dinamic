<?php

use yii\db\Migration;

/**
 * Create master_menu and master_page tables for project databases
 */
class y250503_000001_create_master_menu_page_tables extends Migration
{
    public function safeUp()
    {
        // Create master_page table
        $this->createTable('{{%master_page}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->string(500)->null(),
            'layout_type' => $this->string(50)->defaultValue('single_column'),
            'is_active' => $this->integer(1)->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        // Create master_menu table
        $this->createTable('{{%master_menu}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->null(),
            'page_id' => $this->integer()->null(),
            'name' => $this->string(100)->notNull(),
            'icon' => $this->string(50)->null(),
            'sort_order' => $this->integer()->defaultValue(0),
            'is_active' => $this->integer(1)->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        // Add foreign keys
        $this->addForeignKey(
            'fk_master_menu_parent',
            '{{%master_menu}}',
            'parent_id',
            '{{%master_menu}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_master_menu_page',
            '{{%master_menu}}',
            'page_id',
            '{{%master_page}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Insert default menus
        $this->insert('{{%master_page}}', [
            'title' => 'Dashboard',
            'description' => 'Halaman utama project',
            'layout_type' => 'single_column',
            'is_active' => 1,
        ]);
        
        $pageId = 1; // Dashboard page ID

        // Insert default menus
        $this->insert('{{%master_menu}}', [
            'parent_id' => null,
            'page_id' => $pageId,
            'name' => 'Dashboard',
            'icon' => 'dashboard',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_master_menu_page', '{{%master_menu}}');
        $this->dropForeignKey('fk_master_menu_parent', '{{%master_menu}}');
        $this->dropTable('{{%master_menu}}');
        $this->dropTable('{{%master_page}}');
    }
}
