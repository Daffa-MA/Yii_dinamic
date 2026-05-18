<?php

use yii\db\Migration;

class m260518_000002_add_custom_code_flags_and_page_aliases extends Migration
{
    public function safeUp()
    {
        $this->addColumnIfMissing('{{%master_form_layouts}}', 'use_custom_code', $this->tinyInteger(1)->defaultValue(0));
        $this->addColumnIfMissing('{{%master_page}}', 'page_custom_html', $this->text()->null());
        $this->addColumnIfMissing('{{%master_page}}', 'page_custom_css', $this->text()->null());
        $this->addColumnIfMissing('{{%master_page}}', 'page_custom_js', $this->text()->null());
        $this->addColumnIfMissing('{{%master_page}}', 'use_page_custom_code', $this->tinyInteger(1)->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumnIfExists('{{%master_page}}', 'use_page_custom_code');
        $this->dropColumnIfExists('{{%master_page}}', 'page_custom_js');
        $this->dropColumnIfExists('{{%master_page}}', 'page_custom_css');
        $this->dropColumnIfExists('{{%master_page}}', 'page_custom_html');
        $this->dropColumnIfExists('{{%master_form_layouts}}', 'use_custom_code');
    }

    private function addColumnIfMissing(string $table, string $column, $type): void
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema !== null && !isset($schema->columns[$column])) {
            $this->addColumn($table, $column, $type);
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $schema = $this->db->schema->getTableSchema($table, true);
        if ($schema !== null && isset($schema->columns[$column])) {
            $this->dropColumn($table, $column);
        }
    }
}
