<?php

use yii\db\Migration;

class m260503_000000_create_master_page_table extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        $this->createTable('master_page', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->defaultValue(null),
            'layout_type' => $this->string(50)->defaultValue('default')->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create index for layout_type
        $this->createIndex('idx-master_page-layout_type', 'master_page', 'layout_type');

        if (!$isSqlite) {
            // No foreign keys needed for master_page as it's a standalone table
        }
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'sqlite') {
            // No foreign keys to drop
        }
        
        $this->dropIndex('idx-master_page-layout_type', 'master_page');
        $this->dropTable('master_page');
    }
}