<?php

use yii\db\Migration;

class m260514_210000_create_master_form_activity_log_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('master_form_activity_log', true) !== null) {
            return;
        }

        $this->createTable('master_form_activity_log', [
            'id' => $this->primaryKey(),
            'form_id' => $this->integer()->notNull(),
            'project_id' => $this->integer()->null(),
            'database_context' => $this->string(100)->null(),
            'event_type' => $this->string(100)->notNull(),
            'status' => $this->string(30)->notNull(),
            'message' => $this->text()->null(),
            'meta_json' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-mf-activity-form', 'master_form_activity_log', 'form_id');
        $this->createIndex('idx-mf-activity-project', 'master_form_activity_log', 'project_id');
        $this->createIndex('idx-mf-activity-event', 'master_form_activity_log', 'event_type');

        // Add foreign key constraints for referential integrity
        $isSqlite = $this->db->driverName === 'sqlite';
        if (!$isSqlite) {
            $this->addForeignKey(
                'fk-mf_activity-form_id',
                'master_form_activity_log',
                'form_id',
                'master_form',
                'id',
                'CASCADE'
            );
            
            $this->addForeignKey(
                'fk-mf_activity-project_id',
                'master_form_activity_log',
                'project_id',
                'projects',
                'id',
                'SET NULL'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('master_form_activity_log', true) === null) {
            return;
        }
        
        // Drop foreign keys first if not SQLite
        $isSqlite = $this->db->driverName === 'sqlite';
        if (!$isSqlite) {
            try {
                $this->dropForeignKey('fk-mf_activity-form_id', 'master_form_activity_log');
            } catch (\Exception $e) {
                // FK might not exist
            }
            
            try {
                $this->dropForeignKey('fk-mf_activity-project_id', 'master_form_activity_log');
            } catch (\Exception $e) {
                // FK might not exist
            }
        }
        
        $this->dropTable('master_form_activity_log');
    }
}

