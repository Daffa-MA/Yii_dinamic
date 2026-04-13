<?php

use yii\db\Migration;

class m240101_000006_create_notification_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('notifications', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'message' => $this->text()->notNull(),
            'type' => $this->string(50)->notNull()->defaultValue('info'), // info, success, warning, error
            'icon' => $this->string(100)->null(), // Material icon or image URL
            'action_text' => $this->string(100)->null(), // Text for action button (e.g., "View", "Chat")
            'action_url' => $this->string(255)->null(), // URL for action button
            'is_read' => $this->boolean()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create index on user_id
        $this->createIndex('idx_notifications_user_id', 'notifications', 'user_id');

        // Add foreign key to users table
        $this->addForeignKey(
            'fk_notifications_user_id',
            'notifications',
            'user_id',
            'users',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_notifications_user_id', 'notifications');
        $this->dropIndex('idx_notifications_user_id', 'notifications');
        $this->dropTable('notifications');
    }
}
