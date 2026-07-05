<?php

use yii\db\Migration;

class m260703_000001_create_master_page_chart_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('master_page_chart', true) !== null) {
            return;
        }

        $this->createTable('master_page_chart', [
            'id' => $this->primaryKey(),
            'page_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'subtitle' => $this->string(255)->null(),
            'chart_type' => $this->string(50)->notNull()->defaultValue('bar'),
            'table_id' => $this->integer()->notNull(),
            'table_name' => $this->string(255)->null(),
            'source_type' => $this->string(50)->notNull()->defaultValue('table'),
            'source_query' => $this->text()->null(),
            'position' => $this->integer()->notNull()->defaultValue(0),
            'height' => $this->integer()->notNull()->defaultValue(300),
            'theme' => $this->string(20)->notNull()->defaultValue('light'),
            'palette' => $this->string(50)->notNull()->defaultValue('modern'),
            'animation' => $this->string(20)->notNull()->defaultValue('fade'),
            'label_field' => $this->string(255)->null(),
            'value_field' => $this->string(255)->null(),
            'aggregation' => $this->string(20)->notNull()->defaultValue('count'),
            'group_by_field' => $this->string(255)->null(),
            'sort_field' => $this->string(255)->null(),
            'sort_direction' => $this->string(4)->notNull()->defaultValue('asc'),
            'limit' => $this->integer()->notNull()->defaultValue(10),
            'show_legend' => $this->boolean()->notNull()->defaultValue(true),
            'show_label' => $this->boolean()->notNull()->defaultValue(false),
            'show_toolbar' => $this->boolean()->notNull()->defaultValue(false),
            'show_grid' => $this->boolean()->notNull()->defaultValue(true),
            'show_total' => $this->boolean()->notNull()->defaultValue(false),
            'filter_config' => $this->text()->null(),
            'series_config' => $this->text()->null(),
            'extra_config' => $this->text()->null(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-chart-page', 'master_page_chart', 'page_id');
        $this->createIndex('idx-chart-table', 'master_page_chart', 'table_id');
        $this->createIndex('idx-chart-active', 'master_page_chart', 'is_active');

        $isSqlite = $this->db->driverName === 'sqlite';
        if (!$isSqlite) {
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

    public function safeDown()
    {
        if ($this->db->getTableSchema('master_page_chart', true) === null) {
            return;
        }
        $isSqlite = $this->db->driverName === 'sqlite';
        if (!$isSqlite) {
            try {
                $this->dropForeignKey('fk-chart-page_id', 'master_page_chart');
            } catch (\Exception $e) {}
        }
        $this->dropTable('master_page_chart');
    }
}
