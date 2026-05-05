<?php

use yii\db\Migration;

class m260504_000001_create_master_page_form_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('master_page_form', true) !== null) {
            return;
        }

        $this->createTable('master_page_form', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->notNull(),
            'form_id' => $this->integer()->notNull(),
            'sort_order' => $this->integer()->defaultValue(0)->notNull(),
            'is_active' => $this->tinyInteger(1)->defaultValue(1)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-master_page_form-page_id', 'master_page_form', 'page_id');
        $this->createIndex('idx-master_page_form-form_id', 'master_page_form', 'form_id');
        $this->createIndex('idx-master_page_form-sort_order', 'master_page_form', 'sort_order');
        $this->createIndex('ux-master_page_form-page_form', 'master_page_form', ['page_id', 'form_id'], true);

        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey(
                'fk-master_page_form-page_id',
                'master_page_form',
                'page_id',
                'master_page',
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                'fk-master_page_form-form_id',
                'master_page_form',
                'form_id',
                'forms',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('master_page_form', true) === null) {
            return;
        }

        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-master_page_form-page_id', 'master_page_form');
            $this->dropForeignKey('fk-master_page_form-form_id', 'master_page_form');
        }

        $this->dropIndex('ux-master_page_form-page_form', 'master_page_form');
        $this->dropIndex('idx-master_page_form-sort_order', 'master_page_form');
        $this->dropIndex('idx-master_page_form-form_id', 'master_page_form');
        $this->dropIndex('idx-master_page_form-page_id', 'master_page_form');
        $this->dropTable('master_page_form');
    }
}
