<?php

use yii\db\Migration;

class m260417_000002_add_auto_increment_to_db_table_columns extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'db_table_columns',
            'is_auto_increment',
            $this->boolean()->notNull()->defaultValue(false)->after('is_unique')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('db_table_columns', 'is_auto_increment');
    }
}
