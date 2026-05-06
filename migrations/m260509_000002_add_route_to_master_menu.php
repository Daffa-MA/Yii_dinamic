<?php

use yii\db\Migration;

class m260509_000002_add_route_to_master_menu extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_menu');
        
        if (!$tableSchema->getColumn('route')) {
            $this->addColumn('master_menu', 'route', $this->string(255)->defaultValue(null));
        }
        if (!$tableSchema->getColumn('menu_key')) {
            $this->addColumn('master_menu', 'menu_key', $this->string(50)->defaultValue(null));
        }
        
        $this->createIndex('idx-master_menu-route', 'master_menu', 'route');
        $this->createIndex('idx-master_menu-menu_key', 'master_menu', 'menu_key');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-master_menu-menu_key', 'master_menu');
        $this->dropIndex('idx-master_menu-route', 'master_menu');
        $this->dropColumn('master_menu', 'menu_key');
        $this->dropColumn('master_menu', 'route');
    }
}