<?php

use yii\db\Migration;

class m260513_000001_add_project_id_to_master_form extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_form', true);

        if ($tableSchema && !$tableSchema->getColumn('project_id')) {
            $this->addColumn('master_form', 'project_id', $this->integer()->null());
            $this->createIndex('idx-master_form-project_id', 'master_form', 'project_id');

            if ($this->db->driverName !== 'sqlite') {
                $this->addForeignKey('fk-master_form-project_id', 'master_form', 'project_id', 'projects', 'id', 'CASCADE');
            }
        }
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-master_form-project_id', 'master_form');
        }

        $this->dropIndex('idx-master_form-project_id', 'master_form');
        $this->dropColumn('master_form', 'project_id');
    }
}
