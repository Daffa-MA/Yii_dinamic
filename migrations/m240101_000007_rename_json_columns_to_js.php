<?php

use yii\db\Migration;

class m240101_000007_rename_json_columns_to_js extends Migration
{
    public function safeUp()
    {
        // Intentionally left as no-op.
        // Current models/controllers use schema_json and data_json.
        return true;
    }

    public function safeDown()
    {
        return true;
    }
}
