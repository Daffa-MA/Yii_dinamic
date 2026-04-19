<?php

use yii\db\Migration;

class m260420_000005_add_foreign_key_metadata_to_db_table_columns extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'db_table_columns',
            'is_foreign_key',
            $this->boolean()->notNull()->defaultValue(false)
        );
        $this->addColumn('db_table_columns', 'referenced_table_name', $this->string(100));
        $this->addColumn('db_table_columns', 'referenced_column_name', $this->string(100));
        $this->addColumn(
            'db_table_columns',
            'on_delete_action',
            $this->string(20)->notNull()->defaultValue('RESTRICT')
        );
        $this->addColumn(
            'db_table_columns',
            'on_update_action',
            $this->string(20)->notNull()->defaultValue('RESTRICT')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('db_table_columns', 'on_update_action');
        $this->dropColumn('db_table_columns', 'on_delete_action');
        $this->dropColumn('db_table_columns', 'referenced_column_name');
        $this->dropColumn('db_table_columns', 'referenced_table_name');
        $this->dropColumn('db_table_columns', 'is_foreign_key');
    }
}
