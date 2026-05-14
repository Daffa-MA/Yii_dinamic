<?php

use yii\db\Migration;

class m260514_000002_add_workspace_logo_dimensions extends Migration
{
    public function safeUp()
    {
        $this->addColumn('workspace_settings', 'workspace_logo_width', $this->integer()->defaultValue(120));
        $this->addColumn('workspace_settings', 'workspace_logo_height', $this->integer()->defaultValue(120));
    }

    public function safeDown()
    {
        $this->dropColumn('workspace_settings', 'workspace_logo_width');
        $this->dropColumn('workspace_settings', 'workspace_logo_height');
    }
}
