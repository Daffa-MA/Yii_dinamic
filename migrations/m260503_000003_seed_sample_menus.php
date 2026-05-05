<?php

use yii\db\Migration;

/**
 * Seed sample menus for sidebar navigation testing
 */
class m260503_000003_seed_sample_menus extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');
        
        // Insert parent menus first
        $this->insert('master_menu', [
            'parent_id' => null,
            'page_id' => null,
            'name' => 'Manajemen Konten',
            'icon' => 'inventory_2',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_menu', [
            'parent_id' => null,
            'page_id' => null,
            'name' => 'Produk',
            'icon' => 'category',
            'sort_order' => 2,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_menu', [
            'parent_id' => null,
            'page_id' => null,
            'name' => 'Forms',
            'icon' => 'description',
            'sort_order' => 3,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // Get inserted parent IDs
        $parent1 = Yii::$app->db->createCommand("SELECT id FROM master_menu WHERE name='Manajemen Konten' ORDER BY id DESC LIMIT 1")->queryScalar();
        $parent2 = Yii::$app->db->createCommand("SELECT id FROM master_menu WHERE name='Produk' ORDER BY id DESC LIMIT 1")->queryScalar();
        $parent3 = Yii::$app->db->createCommand("SELECT id FROM master_menu WHERE name='Forms' ORDER BY id DESC LIMIT 1")->queryScalar();
        
        // Insert child menus for "Manajemen Konten"
        $this->insert('master_menu', [
            'parent_id' => $parent1,
            'page_id' => null,
            'name' => 'Halaman',
            'icon' => 'article',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_menu', [
            'parent_id' => $parent1,
            'page_id' => null,
            'name' => 'Menu Utama',
            'icon' => 'menu',
            'sort_order' => 2,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // Get the "Halaman" child ID
        $halamanId = Yii::$app->db->createCommand("SELECT id FROM master_menu WHERE name='Halaman' AND parent_id={$parent1} ORDER BY id DESC LIMIT 1")->queryScalar();
        
        // Get all master_page IDs
        $pages = Yii::$app->db->createCommand("SELECT id, title FROM master_page")->queryAll();
        
        foreach ($pages as $idx => $page) {
            $this->insert('master_menu', [
                'parent_id' => $halamanId,
                'page_id' => $page['id'],
                'name' => $page['title'],
                'icon' => 'web',
                'sort_order' => $idx + 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        // Insert child menus for "Produk"
        $this->insert('master_menu', [
            'parent_id' => $parent2,
            'page_id' => null,
            'name' => 'Kategori Produk',
            'icon' => 'local_offer',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_menu', [
            'parent_id' => $parent2,
            'page_id' => null,
            'name' => 'Daftar Produk',
            'icon' => 'inventory',
            'sort_order' => 2,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // Insert child menus for "Forms"
        $this->insert('master_menu', [
            'parent_id' => $parent3,
            'page_id' => null,
            'name' => 'Semua Form',
            'icon' => 'list_alt',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $kategoriId = Yii::$app->db->createCommand("SELECT id FROM master_menu WHERE name='Kategori Produk' ORDER BY id DESC LIMIT 1")->queryScalar();
        
        // Insert sub-child for Kategori (grandchildren)
        $this->insert('master_menu', [
            'parent_id' => $kategoriId,
            'page_id' => null,
            'name' => 'Sub Kategori',
            'icon' => 'subdirectory_arrow_right',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // Add standalone menu items
        $this->insert('master_menu', [
            'parent_id' => null,
            'page_id' => null,
            'name' => 'Settings',
            'icon' => 'settings',
            'sort_order' => 99,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('master_menu');
    }
}