<?php

use yii\db\Migration;

class m260508_000001_seed_child_menus extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('master_menu', true);
        if (!$tableSchema || !$tableSchema->getColumn('is_active')) {
            echo "master_menu table not found or missing is_active column\n";
            return;
        }
        
        // Check existing menus
        $dataMaster = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Data Master', 'parent_id' => null])->one();
        $akademik = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Akademik', 'parent_id' => null])->one();
        $laporan = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Laporan', 'parent_id' => null])->one();
        
        if ($dataMaster) {
            // Add children if not exists
            $exists = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Data Siswa', 'parent_id' => $dataMaster['id']])->one();
            if (!$exists) {
                $this->insert('master_menu', [
                    'parent_id' => $dataMaster['id'],
                    'page_id' => null,
                    'name' => 'Data Siswa',
                    'icon' => 'person',
                    'sort_order' => 1,
                    'is_active' => 1,
                ]);
                echo "Added Data Siswa\n";
            }
            
            $exists = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Data Guru', 'parent_id' => $dataMaster['id']])->one();
            if (!$exists) {
                $this->insert('master_menu', [
                    'parent_id' => $dataMaster['id'],
                    'page_id' => null,
                    'name' => 'Data Guru',
                    'icon' => 'groups',
                    'sort_order' => 2,
                    'is_active' => 1,
                ]);
                echo "Added Data Guru\n";
            }
        }
        
        if ($akademik) {
            $exists = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Nilai Siswa', 'parent_id' => $akademik['id']])->one();
            if (!$exists) {
                $this->insert('master_menu', [
                    'parent_id' => $akademik['id'],
                    'page_id' => null,
                    'name' => 'Nilai Siswa',
                    'icon' => 'grade',
                    'sort_order' => 1,
                    'is_active' => 1,
                ]);
                echo "Added Nilai Siswa\n";
            }
            
            $exists = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Input Nilai', 'parent_id' => $akademik['id']])->one();
            if (!$exists) {
                $this->insert('master_menu', [
                    'parent_id' => $akademik['id'],
                    'page_id' => null,
                    'name' => 'Input Nilai',
                    'icon' => 'edit_note',
                    'sort_order' => 2,
                    'is_active' => 1,
                ]);
                echo "Added Input Nilai\n";
            }
            
            $exists = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Absensi', 'parent_id' => $akademik['id']])->one();
            if (!$exists) {
                $this->insert('master_menu', [
                    'parent_id' => $akademik['id'],
                    'page_id' => null,
                    'name' => 'Absensi',
                    'icon' => 'event_available',
                    'sort_order' => 3,
                    'is_active' => 1,
                ]);
                echo "Added Absensi\n";
            }
        }
        
        if ($laporan) {
            $exists = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Raport', 'parent_id' => $laporan['id']])->one();
            if (!$exists) {
                $this->insert('master_menu', [
                    'parent_id' => $laporan['id'],
                    'page_id' => null,
                    'name' => 'Raport',
                    'icon' => 'article',
                    'sort_order' => 1,
                    'is_active' => 1,
                ]);
                echo "Added Raport\n";
            }
        }
        
        echo "Done seeding child menus!\n";
    }

    public function safeDown()
    {
        echo "Nothing to rollback\n";
    }
}