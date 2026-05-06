<?php

use yii\db\Migration;

class m260510_000001_add_project_id extends Migration
{
    public function safeUp()
    {
        // Add project_id to master_menu (check if not exists)
        $menuSchema = $this->db->getSchema()->getTableSchema('master_menu');
        if (!isset($menuSchema->columns['project_id'])) {
            $this->addColumn('master_menu', 'project_id', $this->integer()->null());
            $this->addForeignKey(
                'fk_master_menu_project',
                'master_menu',
                'project_id',
                'projects',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->createIndex('idx_master_menu_project', 'master_menu', 'project_id');
        }

        // Add project_id to master_page
        $pageSchema = $this->db->getSchema()->getTableSchema('master_page');
        if (!isset($pageSchema->columns['project_id'])) {
            $this->addColumn('master_page', 'project_id', $this->integer()->null());
            $this->addForeignKey(
                'fk_master_page_project',
                'master_page',
                'project_id',
                'projects',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->createIndex('idx_master_page_project', 'master_page', 'project_id');
        }

        // Add project_id to page_forms (if table exists)
        $pfSchema = $this->db->getSchema()->getTableSchema('page_forms');
        if ($pfSchema !== null && !isset($pfSchema->columns['project_id'])) {
            $this->addColumn('page_forms', 'project_id', $this->integer()->null());
            $this->addForeignKey(
                'fk_page_forms_project',
                'page_forms',
                'project_id',
                'projects',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->createIndex('idx_page_forms_project', 'page_forms', 'project_id');
        }
        
        echo "Migration completed: project_id columns added\n";
    }

    public function safeDown()
    {
        // Drop foreign keys and indexes only if columns exist
        try {
            $pfSchema = $this->db->getSchema()->getTableSchema('page_forms');
            if (isset($pfSchema->columns['project_id'])) {
                $this->dropForeignKey('fk_page_forms_project', 'page_forms');
                $this->dropIndex('idx_page_forms_project', 'page_forms');
                $this->dropColumn('page_forms', 'project_id');
            }
        } catch (\Exception $e) {}

        try {
            $pageSchema = $this->db->getSchema()->getTableSchema('master_page');
            if (isset($pageSchema->columns['project_id'])) {
                $this->dropForeignKey('fk_master_page_project', 'master_page');
                $this->dropIndex('idx_master_page_project', 'master_page');
                $this->dropColumn('master_page', 'project_id');
            }
        } catch (\Exception $e) {}

        try {
            $menuSchema = $this->db->getSchema()->getTableSchema('master_menu');
            if (isset($menuSchema->columns['project_id'])) {
                $this->dropForeignKey('fk_master_menu_project', 'master_menu');
                $this->dropIndex('idx_master_menu_project', 'master_menu');
                $this->dropColumn('master_menu', 'project_id');
            }
        } catch (\Exception $e) {}
        
        echo "Migration reverted: project_id columns dropped\n";
    }
}