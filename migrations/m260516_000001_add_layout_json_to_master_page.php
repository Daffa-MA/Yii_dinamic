<?php

use yii\db\Migration;

class m260516_000001_add_layout_json_to_master_page extends Migration
{
    public function safeUp()
    {
        $this->addColumn('master_page', 'layout_json', $this->text()->null());
        
        $this->addColumn('master_page_form', 'component_config', $this->text()->null());
        
        $this->createIndex('idx-master_page-layout_json', 'master_page', 'layout_json(100)');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-master_page-layout_json', 'master_page');
        
        $this->dropColumn('master_page_form', 'component_config');
        
        $this->dropColumn('master_page', 'layout_json');
    }
}