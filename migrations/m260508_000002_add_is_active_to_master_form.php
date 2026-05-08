<?php

use yii\db\Migration;

class m260508_000002_add_is_active_to_master_form extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_form', true);
        
        if ($tableSchema && !$tableSchema->getColumn('is_active')) {
            $this->addColumn('master_form', 'is_active', $this->tinyInteger(1)->defaultValue(1));
            $this->createIndex('idx-master_form-is_active', 'master_form', 'is_active');
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx-master_form-is_active', 'master_form');
        $this->dropColumn('master_form', 'is_active');
    }
}