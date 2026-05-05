<?php

use yii\db\Migration;

class m260511_000001_update_menu_system_structure extends Migration
{
    public function safeUp()
    {
        // 1. Add type column to master_menu (group, page, route)
        $this->execute("ALTER TABLE master_menu ADD COLUMN type ENUM('group', 'page', 'route') DEFAULT 'page' AFTER parent_id");
        
        // 2. Add order column (alias for sort_order but more standard)
        $this->execute("ALTER TABLE master_menu ADD COLUMN `order` INT NOT NULL DEFAULT 0 AFTER is_active");
        $this->execute("UPDATE master_menu SET `order` = sort_order WHERE `order` = 0");
        
        // 3. Add slug to master_page
        $this->execute("ALTER TABLE master_page ADD COLUMN slug VARCHAR(100) DEFAULT NULL AFTER title");
        
        // 4. Rename layout_type to layout for consistency
        if (!$this->hasColumn('master_page', 'layout')) {
            $this->execute("ALTER TABLE master_page ADD COLUMN layout VARCHAR(50) DEFAULT 'default' AFTER slug");
        }
        
        // 5. Create page_forms table (linking pages to forms with order)
        $this->createTable('page_forms', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->notNull(),
            'form_id' => $this->integer()->notNull(),
            'order' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        $this->createIndex('idx-page_forms-page_id', 'page_forms', 'page_id');
        $this->createIndex('idx-page_forms-form_id', 'page_forms', 'form_id');
        
        // Foreign keys
        $this->addForeignKey(
            'fk-page_forms-page_id',
            'page_forms',
            'page_id',
            'master_page',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // 6. Set default type for existing menus based on their content
        // If has children -> group
        // If has page_id -> page
        // If has route -> route
        $this->execute("UPDATE master_menu SET type = 'group' WHERE parent_id IS NOT NULL AND parent_id != ''");
        $this->execute("UPDATE master_menu SET type = 'page' WHERE (page_id IS NOT NULL AND page_id != '') AND (type = 'page' OR type = 'page')");
        $this->execute("UPDATE master_menu SET type = 'route' WHERE (route IS NOT NULL AND route != '') AND type = 'page'");
        
        // Default type for remaining
        $this->execute("UPDATE master_menu SET type = 'group' WHERE type = 'page' AND (parent_id IS NULL OR parent_id = '')");
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-page_forms-page_id', 'page_forms');
        $this->dropIndex('idx-page_forms-form_id', 'page_forms');
        $this->dropIndex('idx-page_forms-page_id', 'page_forms');
        $this->dropTable('page_forms');
        
        $this->dropColumn('master_page', 'layout');
        $this->dropColumn('master_page', 'slug');
        $this->dropColumn('master_menu', 'order');
        $this->dropColumn('master_menu', 'type');
    }
}