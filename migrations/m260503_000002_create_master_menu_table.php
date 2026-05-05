<?php

use yii\db\Migration;

class m260503_000002_create_master_menu_table extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        $this->createTable('master_menu', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer()->defaultValue(null),
            'page_id' => $this->integer()->defaultValue(null),
            'name' => $this->string(100)->notNull(),
            'icon' => $this->string(50)->defaultValue(null),
            'sort_order' => $this->integer()->defaultValue(0)->notNull(),
            'status' => $this->tinyInteger(1)->defaultValue(1)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create index for parent_id (for nested structure)
        $this->createIndex('idx-master_menu-parent_id', 'master_menu', 'parent_id');
        
        // Create index for page_id
        $this->createIndex('idx-master_menu-page_id', 'master_menu', 'page_id');
        
        // Create index for sort_order
        $this->createIndex('idx-master_menu-sort_order', 'master_menu', 'sort_order');
        
        // Create index for status
        $this->createIndex('idx-master_menu-status', 'master_menu', 'status');

        if (!$isSqlite) {
            // Foreign key constraint for parent_id (self-referencing)
            $this->addForeignKey(
                'fk-master_menu-parent_id',
                'master_menu',
                'parent_id',
                'master_menu',
                'id',
                'SET NULL',
                'CASCADE'
            );
            
            // Foreign key constraint for page_id
            $this->addForeignKey(
                'fk-master_menu-page_id',
                'master_menu',
                'page_id',
                'master_page',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-master_menu-page_id', 'master_menu');
            $this->dropForeignKey('fk-master_menu-parent_id', 'master_menu');
        }
        
        $this->dropIndex('idx-master_menu-status', 'master_menu');
        $this->dropIndex('idx-master_menu-sort_order', 'master_menu');
        $this->dropIndex('idx-master_menu-page_id', 'master_menu');
        $this->dropIndex('idx-master_menu-parent_id', 'master_menu');
        
        $this->dropTable('master_menu');
    }
}