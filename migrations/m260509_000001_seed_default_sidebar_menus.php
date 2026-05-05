<?php

use yii\db\Migration;

class m260509_000001_seed_default_sidebar_menus extends Migration
{
    public function safeUp()
    {
        $now = date('Y-m-d H:i:s');
        
        $this->batchInsert('master_menu', 
            ['name', 'type', 'icon', 'route', 'is_active', 'sort_order', 'menu_key', 'created_at', 'updated_at'],
            [
                [
                    'name' => 'Dashboard',
                    'type' => 'route',
                    'icon' => 'dashboard',
                    'route' => '/site/dashboard',
                    'is_active' => 1,
                    'sort_order' => 1,
                    'menu_key' => 'dashboard',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Table Builder',
                    'type' => 'route',
                    'icon' => 'table_chart',
                    'route' => '/table-builder/index',
                    'is_active' => 1,
                    'sort_order' => 2,
                    'menu_key' => 'table-builder',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Forms',
                    'type' => 'route',
                    'icon' => 'description',
                    'route' => '/form/index',
                    'is_active' => 1,
                    'sort_order' => 3,
                    'menu_key' => 'forms',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Profile',
                    'type' => 'route',
                    'icon' => 'person',
                    'route' => '/site/profile',
                    'is_active' => 1,
                    'sort_order' => 4,
                    'menu_key' => 'profile',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Master Data',
                    'type' => 'route',
                    'icon' => 'storage',
                    'route' => '/master-menu/index',
                    'is_active' => 1,
                    'sort_order' => 5,
                    'menu_key' => 'master-data',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]
        );
    }

    public function safeDown()
    {
        $menuKeys = ['dashboard', 'table-builder', 'forms', 'profile', 'master-data'];
        foreach ($menuKeys as $key) {
            $this->delete('master_menu', ['menu_key' => $key]);
        }
    }
}