<?php

use yii\db\Migration;

class m260514_000002_add_workspace_logo_dimensions extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('workspace_settings', true);
        if ($schema !== null && !isset($schema->columns['workspace_logo_width'])) {
            $this->addColumn('workspace_settings', 'workspace_logo_width', $this->integer()->defaultValue(120));
        }
        if ($schema !== null && !isset($schema->columns['workspace_logo_height'])) {
            $this->addColumn('workspace_settings', 'workspace_logo_height', $this->integer()->defaultValue(120));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('workspace_settings', true);
        if ($schema !== null && isset($schema->columns['workspace_logo_width'])) {
            $this->dropColumn('workspace_settings', 'workspace_logo_width');
        }
        if ($schema !== null && isset($schema->columns['workspace_logo_height'])) {
            $this->dropColumn('workspace_settings', 'workspace_logo_height');
        }
    }
}
