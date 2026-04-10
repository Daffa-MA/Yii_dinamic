<?php

use yii\db\Migration;

class m240101_000006_add_table_id_to_forms extends Migration
{
    public function safeUp()
    {
        $this->addColumn('forms', 'table_id', $this->integer()->after('user_id'));
        $this->addColumn('forms', 'storage_type', $this->string(20)->defaultValue('json')->after('table_id'));
        
        $this->createIndex('idx-forms-table_id', 'forms', 'table_id');
        $this->addForeignKey('fk-forms-table_id', 'forms', 'table_id', 'db_tables', 'id', 'SET NULL');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-forms-table_id', 'forms');
        $this->dropIndex('idx-forms-table_id', 'forms');
        $this->dropColumn('forms', 'storage_type');
        $this->dropColumn('forms', 'table_id');
    }
}
