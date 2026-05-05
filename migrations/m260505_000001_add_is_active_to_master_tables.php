<?php

use yii\db\Migration;

class m260505_000001_add_is_active_to_master_tables extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_menu', true);
        if ($tableSchema && !$tableSchema->getColumn('status')) {
            $this->addColumn('{{%master_menu}}', 'status', $this->tinyInteger(1)->defaultValue(1));
        }

        $tableSchema = $this->db->getTableSchema('master_page', true);
        if ($tableSchema && !$tableSchema->getColumn('is_active')) {
            $this->addColumn('{{%master_page}}', 'is_active', $this->tinyInteger(1)->defaultValue(1));
        }

        $tableSchema = $this->db->getTableSchema('master_form', true);
        if ($tableSchema && !$tableSchema->getColumn('is_active')) {
            $this->addColumn('{{%master_form}}', 'is_active', $this->tinyInteger(1)->defaultValue(1));
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%master_menu}}', 'status');
        $this->dropColumn('{{%master_page}}', 'is_active');
        $this->dropColumn('{{%master_form}}', 'is_active');
    }
}