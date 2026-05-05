<?php

use yii\db\Migration;

class m260510_000001_add_status_to_master_menu extends Migration
{
    public function safeUp()
    {
        $table = Yii::$app->db->getSchema()->getTableSchema('master_menu');
        if (!isset($table->columns['status'])) {
            $this->addColumn('master_menu', 'status', $this->tinyInteger(1)->notNull()->defaultValue(1));
        }
    }

    public function safeDown()
    {
        // Do nothing - don't drop column
    }
}