<?php

use yii\db\Migration;

/**
 * Class m260503_032100_add_is_active_to_master_menu
 */
class m260503_032100_add_is_active_to_master_menu extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%master_menu}}', 'is_active', $this->tinyInteger(1)->defaultValue(1)->after('sort_order'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%master_menu}}', 'is_active');
    }
}
