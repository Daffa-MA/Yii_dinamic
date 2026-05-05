<?php

use yii\db\Migration;

/**
 * Recreates master_page / master_form / master_menu when missing (e.g. migration history out of sync).
 */
class m260503_100000_ensure_master_tables_exist extends Migration
{
    public function safeUp()
    {
        $isSqlite = $this->db->driverName === 'sqlite';

        if ($this->db->getTableSchema('master_page', true) === null) {
            $this->createTable('master_page', [
                'id' => $this->primaryKey(),
                'title' => $this->string(255)->notNull(),
                'description' => $this->text()->defaultValue(null),
                'layout_type' => $this->string(50)->defaultValue('default')->notNull(),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('idx-master_page-layout_type', 'master_page', 'layout_type');
        }

        if ($this->db->getTableSchema('master_form', true) === null) {
            $this->createTable('master_form', [
                'id' => $this->primaryKey(),
                'page_id' => $this->integer()->defaultValue(null),
                'form_name' => $this->string(255)->notNull(),
                'form_data' => $this->json()->notNull(),
                'slug' => $this->string(100)->notNull()->unique(),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('idx-master_form-page_id', 'master_form', 'page_id');
            $this->createIndex('idx-master_form-slug', 'master_form', 'slug');
            if (!$isSqlite && $this->db->getTableSchema('master_page', true) !== null) {
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

        if ($this->db->getTableSchema('master_menu', true) === null) {
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
            $this->createIndex('idx-master_menu-parent_id', 'master_menu', 'parent_id');
            $this->createIndex('idx-master_menu-page_id', 'master_menu', 'page_id');
            $this->createIndex('idx-master_menu-sort_order', 'master_menu', 'sort_order');
            $this->createIndex('idx-master_menu-status', 'master_menu', 'status');
            if (!$isSqlite && $this->db->getTableSchema('master_menu', true) !== null) {
                $this->addForeignKey(
                    'fk-master_menu-parent_id',
                    'master_menu',
                    'parent_id',
                    'master_menu',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            }
            if (!$isSqlite && $this->db->getTableSchema('master_page', true) !== null) {
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
    }

    public function safeDown()
    {
        echo "m260503_100000_ensure_master_tables_exist cannot be reverted.\n";

        return false;
    }
}
