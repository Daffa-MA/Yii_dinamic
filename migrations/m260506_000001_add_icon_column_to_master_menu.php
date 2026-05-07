<?php

use yii\db\Migration;

class m260506_000001_add_icon_column_to_master_menu extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_menu');
        
        if ($tableSchema !== null) {
            // Check if column doesn't already exist
            if (!isset($tableSchema->columns['icon'])) {
                $this->addColumn('master_menu', 'icon', $this->string(50)->defaultValue(null));
                echo "Column 'icon' added to 'master_menu' table.\n";
            } else {
                echo "Column 'icon' already exists in 'master_menu' table.\n";
            }
        } else {
            echo "Table 'master_menu' not found.\n";
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('master_menu') !== null) {
            if (isset($this->db->getTableSchema('master_menu')->columns['icon'])) {
                $this->dropColumn('master_menu', 'icon');
            }
        }
    }
}
