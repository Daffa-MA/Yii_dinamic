<?php

use yii\db\Migration;

class m260421_000001_add_enum_values_to_db_table_columns extends Migration
{
    public function safeUp()
    {
        $this->addColumn('db_table_columns', 'enum_values', $this->text());
    }

    public function safeDown()
    {
        $this->dropColumn('db_table_columns', 'enum_values');
    }
}
