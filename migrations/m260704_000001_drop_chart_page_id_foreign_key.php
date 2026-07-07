<?php

use yii\db\Migration;

class m260704_000001_drop_chart_page_id_foreign_key extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_page_chart', true);
        if ($tableSchema === null) {
            return;
        }

        $fkName = 'fk-chart-page_id';
        $fks = $tableSchema->foreignKeys;
        $exists = false;
        foreach ($fks as $name => $fk) {
            if ($name === $fkName) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            $this->dropForeignKey($fkName, 'master_page_chart');
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('master_page_chart', true);
        if ($tableSchema === null) {
            return;
        }

        $fks = $tableSchema->foreignKeys;
        $exists = false;
        foreach ($fks as $name => $fk) {
            if ($name === 'fk-chart-page_id') {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->addForeignKey(
                'fk-chart-page_id',
                'master_page_chart',
                'page_id',
                'master_page',
                'id',
                'CASCADE'
            );
        }
    }
}
