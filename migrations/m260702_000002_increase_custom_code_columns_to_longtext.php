<?php

use yii\db\Migration;

/**
 * Increase custom code columns to longtext to support large Page Source
 * content (e.g. datatable pre-rendered HTML with CSS, JS, modals).
 * 
 * The previous TEXT type (max 64KB) is too small when the Page Source
 * includes server-rendered datatable HTML from MasterDatatableRenderService.
 */
class m260702_000002_increase_custom_code_columns_to_longtext extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%master_page}}', 'custom_html', $this->db->schema->createColumnSchemaBuilder('longtext'));
        $this->alterColumn('{{%master_page}}', 'custom_css', $this->db->schema->createColumnSchemaBuilder('longtext'));
        $this->alterColumn('{{%master_page}}', 'custom_js', $this->db->schema->createColumnSchemaBuilder('longtext'));
        $this->alterColumn('{{%master_page}}', 'page_custom_html', $this->db->schema->createColumnSchemaBuilder('longtext'));
        $this->alterColumn('{{%master_page}}', 'page_custom_css', $this->db->schema->createColumnSchemaBuilder('longtext'));
        $this->alterColumn('{{%master_page}}', 'page_custom_js', $this->db->schema->createColumnSchemaBuilder('longtext'));
    }

    public function safeDown()
    {
        $this->alterColumn('{{%master_page}}', 'custom_html', $this->text()->null());
        $this->alterColumn('{{%master_page}}', 'custom_css', $this->text()->null());
        $this->alterColumn('{{%master_page}}', 'custom_js', $this->text()->null());
        $this->alterColumn('{{%master_page}}', 'page_custom_html', $this->text()->null());
        $this->alterColumn('{{%master_page}}', 'page_custom_css', $this->text()->null());
        $this->alterColumn('{{%master_page}}', 'page_custom_js', $this->text()->null());
    }
}
