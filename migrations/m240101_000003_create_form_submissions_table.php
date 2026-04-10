<?php

use yii\db\Migration;

class m240101_000003_create_form_submissions_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('form_submissions', [
            'id' => $this->primaryKey(),
            'form_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'data_json' => $this->text()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create indexes
        $this->createIndex('idx-submissions-form_id', 'form_submissions', 'form_id');
        $this->createIndex('idx-submissions-user_id', 'form_submissions', 'user_id');

        // Add foreign keys
        $this->addForeignKey('fk-submissions-form_id', 'form_submissions', 'form_id', 'forms', 'id', 'CASCADE');
        $this->addForeignKey('fk-submissions-user_id', 'form_submissions', 'user_id', 'users', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-submissions-user_id', 'form_submissions');
        $this->dropForeignKey('fk-submissions-form_id', 'form_submissions');
        $this->dropIndex('idx-submissions-user_id', 'form_submissions');
        $this->dropIndex('idx-submissions-form_id', 'form_submissions');
        $this->dropTable('form_submissions');
    }
}
