<?php

use yii\db\Migration;

class m240101_000007_rename_json_columns_to_js extends Migration
{
    public function safeUp()
    {
        // This migration was applied but the file was lost
        // It likely renamed schema_json to schema_js
        $tableSchema = $this->db->getTableSchema('forms');
        
        if (isset($tableSchema->columns['schema_json']) && !isset($tableSchema->columns['schema_js'])) {
            $this->renameColumn('forms', 'schema_json', 'schema_js');
        }
        
        if (isset($tableSchema->columns['data_json']) && !isset($tableSchema->columns['data_js'])) {
            $this->renameColumn('form_submissions', 'data_json', 'data_js');
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('forms');
        
        if (isset($tableSchema->columns['schema_js']) && !isset($tableSchema->columns['schema_json'])) {
            $this->renameColumn('forms', 'schema_js', 'schema_json');
        }
        
        if (isset($tableSchema->columns['data_js']) && !isset($tableSchema->columns['data_json'])) {
            $this->renameColumn('form_submissions', 'data_js', 'data_json');
        }
    }
}
