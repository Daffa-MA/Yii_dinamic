<?php

use yii\db\Migration;

/**
 * Seed sample pages for master_page table
 */
class m260503_000004_seed_sample_pages extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');
        
        // Insert sample pages
        $this->insert('master_page', [
            'title' => 'Dashboard',
            'description' => 'Halaman dashboard utama',
            'layout_type' => 'internal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_page', [
            'title' => 'Profil Perusahaan',
            'description' => 'Halaman profil perusahaan',
            'layout_type' => 'internal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_page', [
            'title' => 'Tentang Kami',
            'description' => 'Halaman tentang kami',
            'layout_type' => 'public',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_page', [
            'title' => 'Layanan',
            'description' => 'Halaman daftar layanan',
            'layout_type' => 'public',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        $this->insert('master_page', [
            'title' => 'Kontak',
            'description' => 'Halaman kontak',
            'layout_type' => 'public',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('master_page');
    }
}