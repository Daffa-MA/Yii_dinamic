<?php

use yii\db\Migration;

class m260519_000001_add_workspace_logo_image extends Migration
{
    public function safeUp()
    {
        $this->addColumn('workspace_settings', 'workspace_logo_image', $this->string(500)->defaultValue(null));
    }

    public function safeDown()
    {
        $this->dropColumn('workspace_settings', 'workspace_logo_image');
    }
}