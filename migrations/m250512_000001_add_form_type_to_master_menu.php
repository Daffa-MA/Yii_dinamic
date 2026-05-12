<?php

use yii\db\Migration;

class m250512_000001_add_form_type_to_master_menu extends Migration
{
    public function safeUp()
    {
        $this->db = Yii::$app->db;
        
        $this->addColumn('{{%master_menu}}', 'form_id', $this->integer()->null()->after('page_id'));
        $this->addForeignKey('fk_master_menu_form', '{{%master_menu}}', 'form_id', '{{%master_form}}', 'id', 'SET NULL', 'CASCADE');
        
        return true;
    }

    public function safeDown()
    {
        $this->db = Yii::$app->db;
        
        $this->dropForeignKey('fk_master_menu_form', '{{%master_menu}}');
        $this->dropColumn('{{%master_menu}}', 'form_id');
        
        return true;
    }
}
