<?php

use yii\db\Migration;

class m260702_000001_add_related_display_column_to_db_table_columns extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'db_table_columns',
            'related_display_column',
            $this->string(100)
        );
    }

    public function safeDown()
    {
        $this->dropColumn('db_table_columns', 'related_display_column');
    }
}
