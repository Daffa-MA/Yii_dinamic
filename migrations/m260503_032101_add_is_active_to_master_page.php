<?php

use yii\db\Migration;

/**
 * Class m260503_032101_add_is_active_to_master_page
 */
class m260503_032101_add_is_active_to_master_page extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%master_page}}', 'is_active', $this->tinyInteger(1)->defaultValue(1));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%master_page}}', 'is_active');
    }
}
