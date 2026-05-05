<?php

use yii\db\Migration;

class m260503_000001_create_master_form_table extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        $this->createTable('master_form', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->defaultValue(null),
            'form_name' => $this->string(255)->notNull(),
            'form_data' => $this->json()->notNull(),
            'slug' => $this->string(100)->notNull()->unique(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create index for page_id
        $this->createIndex('idx-master_form-page_id', 'master_form', 'page_id');
        
        // Create index for slug
        $this->createIndex('idx-master_form-slug', 'master_form', 'slug');

        if (!$isSqlite) {
            // Foreign key constraint for page_id
            $this->addForeignKey(
                'fk-master_form-page_id',
                'master_form',
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
            $this->dropForeignKey('fk-master_form-page_id', 'master_form');
        }
        
        $this->dropIndex('idx-master_form-slug', 'master_form');
        $this->dropIndex('idx-master_form-page_id', 'master_form');
        
        $this->dropTable('master_form');
    }
}