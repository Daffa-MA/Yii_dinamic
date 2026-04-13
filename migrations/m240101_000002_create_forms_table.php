<?php

use yii\db\Migration;

class m240101_000002_create_forms_table extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        $this->createTable('forms', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'schema_json' => $this->text()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create index on user_id
        $this->createIndex('idx-forms-user_id', 'forms', 'user_id');

        if (!$isSqlite) {
            $this->addForeignKey('fk-forms-user_id', 'forms', 'user_id', 'users', 'id', 'CASCADE');
        }
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-forms-user_id', 'forms');
        }
        $this->dropIndex('idx-forms-user_id', 'forms');
        $this->dropTable('forms');
    }
}
