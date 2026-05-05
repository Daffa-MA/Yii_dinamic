<?php

use yii\db\Migration;

class m260505_000002_seed_initial_menus extends Migration
{
    public function safeUp()
    {
        $menuTable = $this->db->getTableSchema('master_menu', true);
        $pageTable = $this->db->getTableSchema('master_page', true);
        $formTable = $this->db->getTableSchema('master_form', true);

        if ($menuTable && !$menuTable->getColumn('status')) {
            $this->addColumn('{{%master_menu}}', 'status', $this->tinyInteger(1)->defaultValue(1));
        }

        if ($pageTable && !$pageTable->getColumn('is_active')) {
            $this->addColumn('{{%master_page}}', 'is_active', $this->tinyInteger(1)->defaultValue(1));
        }

        if ($formTable && !$formTable->getColumn('is_active')) {
            $this->addColumn('{{%master_form}}', 'is_active', $this->tinyInteger(1)->defaultValue(1));
        }

        $existingMenus = (new \yii\db\Query())->from('master_menu')->count('*', $this->db);
        if ($existingMenus == 0) {
            $this->insert('master_menu', [
                'name' => 'Dashboard',
                'icon' => 'dashboard',
                'sort_order' => 1,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Master Data',
                'icon' => 'folder',
                'sort_order' => 2,
                'status' => 1,
            ]);

            $dashboardId = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Dashboard'])->one($this->db)['id'];
            $masterDataId = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Master Data'])->one($this->db)['id'];

            $this->insert('master_menu', [
                'name' => 'Profil',
                'icon' => 'person',
                'parent_id' => $dashboardId,
                'sort_order' => 1,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Data Siswa',
                'icon' => 'school',
                'parent_id' => $masterDataId,
                'sort_order' => 1,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Data Guru',
                'icon' => 'groups',
                'parent_id' => $masterDataId,
                'sort_order' => 2,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Akademik',
                'icon' => 'calendar_today',
                'sort_order' => 3,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Input Nilai',
                'icon' => 'edit_note',
                'parent_id' => (new \yii\db\Query())->from('master_menu')->where(['name' => 'Akademik'])->one($this->db)['id'],
                'sort_order' => 1,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Absensi',
                'icon' => 'event_available',
                'parent_id' => (new \yii\db\Query())->from('master_menu')->where(['name' => 'Akademik'])->one($this->db)['id'],
                'sort_order' => 2,
                'status' => 1,
            ]);

            $this->insert('master_menu', [
                'name' => 'Laporan',
                'icon' => 'description',
                'sort_order' => 4,
                'status' => 1,
            ]);
        }

        $existingPages = (new \yii\db\Query())->from('master_page')->count('*', $this->db);
        if ($existingPages == 0) {
            $this->insert('master_page', [
                'title' => 'Dashboard',
                'description' => 'Halaman utama dashboard',
                'layout_type' => 'dashboard',
                'is_active' => 1,
            ]);

            $this->insert('master_page', [
                'title' => 'Data Siswa',
                'description' => 'Kelola data siswa',
                'layout_type' => 'list',
                'is_active' => 1,
            ]);

            $this->insert('master_page', [
                'title' => 'Data Guru',
                'description' => 'Kelola data guru',
                'layout_type' => 'list',
                'is_active' => 1,
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('master_menu', []);
        $this->delete('master_page', []);
    }
}