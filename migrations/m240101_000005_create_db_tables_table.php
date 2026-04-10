<?php

use yii\db\Migration;

class m240101_000005_create_db_tables_table extends Migration
{
    public function safeUp()
    {
        // Tables metadata
        $this->createTable('db_tables', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull()->unique(),
            'label' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'engine' => $this->string(20)->defaultValue('InnoDB'),
            'charset' => $this->string(20)->defaultValue('utf8mb4'),
            'collation' => $this->string(50)->defaultValue('utf8mb4_unicode_ci'),
            'is_created' => $this->boolean()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-db_tables-user_id', 'db_tables', 'user_id');
        $this->addForeignKey('fk-db_tables-user_id', 'db_tables', 'user_id', 'users', 'id', 'CASCADE');

        // Table columns metadata
        $this->createTable('db_table_columns', [
            'id' => $this->primaryKey(),
            'table_id' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull(),
            'label' => $this->string(255)->notNull(),
            'type' => $this->string(50)->notNull(),
            'length' => $this->integer(),
            'is_nullable' => $this->boolean()->defaultValue(true),
            'is_primary' => $this->boolean()->defaultValue(false),
            'is_unique' => $this->boolean()->defaultValue(false),
            'default_value' => $this->string(255),
            'comment' => $this->text(),
            'sort_order' => $this->integer()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-db_table_columns-table_id', 'db_table_columns', 'table_id');
        $this->addForeignKey('fk-db_table_columns-table_id', 'db_table_columns', 'table_id', 'db_tables', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-db_table_columns-table_id', 'db_table_columns');
        $this->dropIndex('idx-db_table_columns-table_id', 'db_table_columns');
        $this->dropTable('db_table_columns');
        
        $this->dropForeignKey('fk-db_tables-user_id', 'db_tables');
        $this->dropIndex('idx-db_tables-user_id', 'db_tables');
        $this->dropTable('db_tables');
    }
}
