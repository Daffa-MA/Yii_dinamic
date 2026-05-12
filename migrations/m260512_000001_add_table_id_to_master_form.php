<?php

use yii\db\Migration;

class m260512_000001_add_table_id_to_master_form extends Migration
{
    public function up()
    {
        $this->addColumn('{{%master_form}}', 'table_id', $this->integer()->null()->after('page_id'));
        $this->addForeignKey(
            'fk_master_form_table_id',
            '{{%master_form}}',
            'table_id',
            '{{%db_tables}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey('fk_master_form_table_id', '{{%master_form}}');
        $this->dropColumn('{{%master_form}}', 'table_id');
    }
}
