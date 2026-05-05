<?php

use yii\db\Migration;

class m260515_000002_fix_master_page_columns extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('master_page');
        
        if ($table && !$table->getColumn('title')) {
            $this->addColumn('master_page', 'title', $this->string(255)->null());
        }
        if ($table && !$table->getColumn('is_active')) {
            $this->addColumn('master_page', 'is_active', $this->tinyInteger(1)->defaultValue(1));
        }
    }

    public function safeDown()
    {
        // Don't drop
    }
}