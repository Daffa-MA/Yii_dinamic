<?php

use yii\db\Migration;

/**
 * Migration for Form Placement and Menu System
 * Handles form-to-menu mapping and dynamic routing
 */
class m250511_000001_form_placement_menu extends Migration
{
    public function safeUp()
    {
        $this->db = Yii::$app->db;
        
        // Create sidebar_menu table
        $this->createTable('{{%sidebar_menu}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->null(),
            'user_id' => $this->integer()->notNull(),
            'project_id' => $this->integer()->null(),
            'label' => $this->string(100)->notNull(),
            'icon' => $this->string(50)->null()->defaultValue('circle'),
            'route' => $this->string(255)->null(),
            'url' => $this->string(255)->null(),
            'type' => $this->string(20)->notNull()->defaultValue('link'),
            'target' => $this->string(10)->null()->defaultValue('_self'),
            'visibility' => $this->string(20)->notNull()->defaultValue('authenticated'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'params' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        
        $this->createIndex('idx_sidebar_menu_parent', '{{%sidebar_menu}}', 'parent_id');
        $this->createIndex('idx_sidebar_menu_user', '{{%sidebar_menu}}', 'user_id');
        $this->createIndex('idx_sidebar_menu_sort', '{{%sidebar_menu}}', 'sort_order');
        
        $this->addForeignKey('fk_sidebar_menu_parent', '{{%sidebar_menu}}', 'parent_id', '{{%sidebar_menu}}', 'id', 'SET NULL', 'CASCADE');
        
        // Create form_placement table
        $this->createTable('{{%form_placement}}', [
            'id' => $this->primaryKey(),
            'form_id' => $this->integer()->notNull(),
            'menu_id' => $this->integer()->null(),
            'page_id' => $this->integer()->null(),
            'page_title' => $this->string(255)->null(),
            'page_slug' => $this->string(255)->null()->unique(),
            'route_path' => $this->string(255)->null()->unique(),
            'show_in_menu' => $this->boolean()->notNull()->defaultValue(true),
            'show_in_navbar' => $this->boolean()->notNull()->defaultValue(false),
            'show_in_sidebar' => $this->boolean()->notNull()->defaultValue(true),
            'is_public' => $this->boolean()->notNull()->defaultValue(false),
            'is_published' => $this->boolean()->notNull()->defaultValue(true),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'meta_title' => $this->string(255)->null(),
            'meta_description' => $this->text()->null(),
            'layout_template' => $this->string(50)->null()->defaultValue('default'),
            'custom_css' => $this->text()->null(),
            'custom_js' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');
        
        $this->createIndex('idx_form_placement_form', '{{%form_placement}}', 'form_id');
        $this->createIndex('idx_form_placement_menu', '{{%form_placement}}', 'menu_id');
        $this->createIndex('idx_form_placement_slug', '{{%form_placement}}', 'page_slug');
        $this->createIndex('idx_form_placement_route', '{{%form_placement}}', 'route_path');
        
        $this->addForeignKey('fk_form_placement_form', '{{%form_placement}}', 'form_id', '{{%master_form}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_form_placement_menu', '{{%form_placement}}', 'menu_id', '{{%sidebar_menu}}', 'id', 'SET NULL', 'CASCADE');
        
        return true;
    }

    public function safeDown()
    {
        $this->db = Yii::$app->db;
        
        $this->dropForeignKey('fk_form_placement_menu', '{{%form_placement}}');
        $this->dropForeignKey('fk_form_placement_form', '{{%form_placement}}');
        $this->dropForeignKey('fk_sidebar_menu_parent', '{{%sidebar_menu}}');
        
        $this->dropTable('{{%form_placement}}');
        $this->dropTable('{{%sidebar_menu}}');
        
        return true;
    }
}