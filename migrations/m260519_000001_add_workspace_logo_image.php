<?php

use yii\db\Migration;

class m260519_000001_add_workspace_logo_image extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('workspace_settings', true);
        if ($schema !== null && !isset($schema->columns['workspace_logo_image'])) {
            $this->addColumn('workspace_settings', 'workspace_logo_image', $this->string(500)->defaultValue(null));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('workspace_settings', true);
        if ($schema !== null && isset($schema->columns['workspace_logo_image'])) {
            $this->dropColumn('workspace_settings', 'workspace_logo_image');
        }
    }
}
