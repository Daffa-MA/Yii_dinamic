<?php

use yii\db\Migration;

class m260512_000001_add_icon_to_master_menu extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_menu');
        
        if ($tableSchema !== null && !$this->db->getTableSchema('master_menu')->getColumn('icon')) {
            $this->addColumn('master_menu', 'icon', $this->string(50)->defaultValue(null)->after('name'));
        }
    }

    public function safeDown()
    {
        // Jangan hapus column karena mungkin sudah ada datanya
    }
}