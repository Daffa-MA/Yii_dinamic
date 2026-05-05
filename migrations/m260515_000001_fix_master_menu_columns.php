<?php

use yii\db\Migration;

class m260515_000001_fix_master_menu_columns extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('master_menu');
        
        if ($table && !$table->getColumn('icon')) {
            $this->addColumn('master_menu', 'icon', $this->string(50)->null());
        }
    }

    public function safeDown()
    {
        // Don't drop - data mungkin sudah ada
    }
}