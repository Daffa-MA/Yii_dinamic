<?php

use yii\db\Migration;

class m260413_000001_create_published_forms_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('published_forms', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'form_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-published_forms-user_id', 'published_forms', 'user_id');
        $this->createIndex('idx-published_forms-form_id', 'published_forms', 'form_id');
        $this->createIndex('idx-published_forms-slug', 'published_forms', 'slug');

        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey('fk-published_forms-user_id', 'published_forms', 'user_id', 'users', 'id', 'CASCADE');
            $this->addForeignKey('fk-published_forms-form_id', 'published_forms', 'form_id', 'forms', 'id', 'CASCADE');
        }
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-published_forms-form_id', 'published_forms');
            $this->dropForeignKey('fk-published_forms-user_id', 'published_forms');
        }
        $this->dropIndex('idx-published_forms-slug', 'published_forms');
        $this->dropIndex('idx-published_forms-form_id', 'published_forms');
        $this->dropIndex('idx-published_forms-user_id', 'published_forms');
        $this->dropTable('published_forms');
    }
}
