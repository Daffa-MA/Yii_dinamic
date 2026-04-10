<?php

use yii\db\Migration;

class m240101_000001_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('users', [
            'id' => $this->primaryKey(),
            'username' => $this->string(100)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Insert default admin user (password: admin123)
        $this->insert('users', [
            'username' => 'admin',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('users');
    }
}
