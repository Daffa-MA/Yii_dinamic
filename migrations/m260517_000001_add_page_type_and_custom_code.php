<?php

use yii\db\Migration;

class m260517_000001_add_page_type_and_custom_code extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%master_page}}', 'page_type', $this->string(50)->defaultValue('builder')->after('layout'));
        $this->addColumn('{{%master_page}}', 'custom_html', $this->text()->null()->after('page_type'));
        $this->addColumn('{{%master_page}}', 'custom_css', $this->text()->null()->after('custom_html'));
        $this->addColumn('{{%master_page}}', 'custom_js', $this->text()->null()->after('custom_css'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%master_page}}', 'page_type');
        $this->dropColumn('{{%master_page}}', 'custom_html');
        $this->dropColumn('{{%master_page}}', 'custom_css');
        $this->dropColumn('{{%master_page}}', 'custom_js');
    }
}
