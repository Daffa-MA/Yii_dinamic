<?php

namespace app\components;

use Yii;
use yii\db\Connection;

/**
 * DatabaseSchemaInitializer - Inisialisasi otomatis struktur database untuk project baru
 * 
 * Memastikan setiap database project memiliki struktur lengkap termasuk:
 * - master_menu (dengan kolom icon)
 * - master_page
 * - page_forms
 * Dan data default yang dibutuhkan
 */
class DatabaseSchemaInitializer
{
    /** @var Connection */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Initialize database schema lengkap untuk project baru
     * 
     * @param string $databaseName Nama database project
     * @throws \Exception
     */
    /**
     * @param string $databaseName
     * @throws \Exception
     */
    public static function initializeProjectDatabase($databaseName)
    {
        $config = Yii::$app->db->dsn;
        $username = Yii::$app->db->username;
        $password = Yii::$app->db->password;

        // Parse DSN untuk get host dan port
        preg_match('/host=([^;]+)/', $config, $hostMatch);
        preg_match('/port=([^;]+)/', $config, $portMatch);
        
        $host = $hostMatch[1] ?? 'localhost';
        $port = !empty($portMatch[1]) ? (int)$portMatch[1] : 3306;

        $dsn = "mysql:host={$host};port={$port};dbname={$databaseName}";
        
        $connection = new Connection([
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
        ]);

        $initializer = new self($connection);
        $initializer->createAllTables();
        $initializer->insertDefaultData();
    }

    /**
     * Buat semua tabel yang diperlukan
     * 
     * @throws \Exception
     */
    private function createAllTables(): void
    {
        $this->createMasterPageTable();
        $this->createMasterMenuTable();
        $this->createPageFormsTable();
        $this->ensureColumnsExist();
    }

    /**
     * Buat tabel master_page
     */
    private function createMasterPageTable(): void
    {
        if ($this->connection->getTableSchema('master_page', true) !== null) {
            return;
        }

        $this->connection->createCommand()->createTable('master_page', [
            'id' => $this->connection->schema->createColumnSchemaBuilder('pk'),
            'name' => $this->connection->schema->createColumnSchemaBuilder('string', 255)->notNull(),
            'slug' => $this->connection->schema->createColumnSchemaBuilder('string', 100)->notNull()->unique(),
            'layout' => $this->connection->schema->createColumnSchemaBuilder('string', 50)->defaultValue('default'),
            'description' => $this->connection->schema->createColumnSchemaBuilder('text'),
            'is_active' => $this->connection->schema->createColumnSchemaBuilder('integer', 1)->defaultValue(1),
            'created_at' => $this->connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ])->execute();

        $this->connection->createCommand()->createIndex('idx-master_page-slug', 'master_page', 'slug', true)->execute();
        $this->connection->createCommand()->createIndex('idx-master_page-is_active', 'master_page', 'is_active')->execute();
    }

    /**
     * Buat tabel master_menu dengan kolom icon
     */
    private function createMasterMenuTable(): void
    {
        if ($this->connection->getTableSchema('master_menu', true) !== null) {
            return;
        }

        $this->connection->createCommand()->createTable('master_menu', [
            'id' => $this->connection->schema->createColumnSchemaBuilder('pk'),
            'parent_id' => $this->connection->schema->createColumnSchemaBuilder('integer'),
            'page_id' => $this->connection->schema->createColumnSchemaBuilder('integer'),
            'name' => $this->connection->schema->createColumnSchemaBuilder('string', 100)->notNull(),
            'icon' => $this->connection->schema->createColumnSchemaBuilder('string', 50),
            'type' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('page'),
            'route' => $this->connection->schema->createColumnSchemaBuilder('string', 255),
            'menu_key' => $this->connection->schema->createColumnSchemaBuilder('string', 50),
            'sort_order' => $this->connection->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
            'order' => $this->connection->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
            'is_active' => $this->connection->schema->createColumnSchemaBuilder('integer', 1)->defaultValue(1),
            'created_at' => $this->connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            
            // New flexible properties for UI/UX customization
            'target' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('_self'),
            'action_type' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('link'),
            'button_text' => $this->connection->schema->createColumnSchemaBuilder('string', 100),
            'button_style' => $this->connection->schema->createColumnSchemaBuilder('string', 30)->defaultValue('primary'),
            'button_size' => $this->connection->schema->createColumnSchemaBuilder('string', 10)->defaultValue('md'),
            'button_icon' => $this->connection->schema->createColumnSchemaBuilder('string', 50),
            'button_full_width' => $this->connection->schema->createColumnSchemaBuilder('integer', 1)->defaultValue(0),
            'css_class' => $this->connection->schema->createColumnSchemaBuilder('string', 255),
            'css_style' => $this->connection->schema->createColumnSchemaBuilder('text'),
            'custom_html' => $this->connection->schema->createColumnSchemaBuilder('text'),
            'badge_text' => $this->connection->schema->createColumnSchemaBuilder('string', 100),
            'badge_style' => $this->connection->schema->createColumnSchemaBuilder('string', 30)->defaultValue('primary'),
            'show_tooltip' => $this->connection->schema->createColumnSchemaBuilder('string', 255),
            'tooltip_position' => $this->connection->schema->createColumnSchemaBuilder('string', 10)->defaultValue('top'),
            'animation_type' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('none'),
            'animation_duration' => $this->connection->schema->createColumnSchemaBuilder('integer')->defaultValue(300),
            'icon_position' => $this->connection->schema->createColumnSchemaBuilder('string', 10)->defaultValue('left'),
            'sort_priority' => $this->connection->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
            'visibility_roles' => $this->connection->schema->createColumnSchemaBuilder('string', 255),
            'visibility_condition' => $this->connection->schema->createColumnSchemaBuilder('text'),
            'metadata' => $this->connection->schema->createColumnSchemaBuilder('text'),
            
            // Border properties
            'border_style' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('none'),
            'border_width' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('1px'),
            'border_color' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('#000000'),
            'border_position' => $this->connection->schema->createColumnSchemaBuilder('string', 20)->defaultValue('all'),
            'border_radius' => $this->connection->schema->createColumnSchemaBuilder('string', 10)->defaultValue('none'),
            'border_radius_size' => $this->connection->schema->createColumnSchemaBuilder('string', 20),
        ])->execute();

        // Create indexes
        $this->connection->createCommand()->createIndex('idx-master_menu-parent_id', 'master_menu', 'parent_id')->execute();
        $this->connection->createCommand()->createIndex('idx-master_menu-type', 'master_menu', 'type')->execute();
        $this->connection->createCommand()->createIndex('idx-master_menu-page_id', 'master_menu', 'page_id')->execute();
        $this->connection->createCommand()->createIndex('idx-master_menu-is_active', 'master_menu', 'is_active')->execute();
        $this->connection->createCommand()->createIndex('idx-master_menu-sort_order', 'master_menu', 'sort_order')->execute();
        $this->connection->createCommand()->createIndex('idx-master_menu-order', 'master_menu', 'order')->execute();

        // Add foreign keys
        try {
            $this->connection->createCommand()->addForeignKey(
                'fk-master_menu-parent',
                'master_menu',
                'parent_id',
                'master_menu',
                'id',
                'SET NULL',
                'CASCADE'
            )->execute();
        } catch (\Exception $e) {
            // FK mungkin sudah ada
        }

        try {
            $this->connection->createCommand()->addForeignKey(
                'fk-master_menu-page',
                'master_menu',
                'page_id',
                'master_page',
                'id',
                'SET NULL',
                'CASCADE'
            )->execute();
        } catch (\Exception $e) {
            // FK mungkin sudah ada
        }
    }

    /**
     * Buat tabel page_forms
     */
    private function createPageFormsTable(): void
    {
        if ($this->connection->getTableSchema('page_forms', true) !== null) {
            return;
        }

        $this->connection->createCommand()->createTable('page_forms', [
            'id' => $this->connection->schema->createColumnSchemaBuilder('pk'),
            'page_id' => $this->connection->schema->createColumnSchemaBuilder('integer')->notNull(),
            'form_id' => $this->connection->schema->createColumnSchemaBuilder('integer')->notNull(),
            'order' => $this->connection->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
            'created_at' => $this->connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->connection->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ])->execute();

        $this->connection->createCommand()->createIndex('idx-page_forms-page_id', 'page_forms', 'page_id')->execute();
        $this->connection->createCommand()->createIndex('idx-page_forms-form_id', 'page_forms', 'form_id')->execute();
        $this->connection->createCommand()->createIndex('idx-page_forms-order', 'page_forms', 'order')->execute();

        try {
            $this->connection->createCommand()->addForeignKey(
                'fk-page_forms-page',
                'page_forms',
                'page_id',
                'master_page',
                'id',
                'CASCADE',
                'CASCADE'
            )->execute();
        } catch (\Exception $e) {
            // FK mungkin sudah ada
        }
    }

    /**
     * Pastikan semua kolom yang diperlukan ada
     * Ini untuk backward compatibility dengan existing databases
     */
    private function ensureColumnsExist(): void
    {
        $schema = $this->connection->getTableSchema('master_menu', true);
        if ($schema === null) {
            return;
        }

        $columnsToAdd = [
            'icon' => ['type' => 'string', 'length' => 50, 'after' => 'name'],
            'type' => ['type' => 'string', 'length' => 20, 'default' => 'page'],
            'route' => ['type' => 'string', 'length' => 255],
            'menu_key' => ['type' => 'string', 'length' => 50],
            
            // New flexible properties for UI/UX customization
            'target' => ['type' => 'string', 'length' => 20, 'default' => '_self'],
            'action_type' => ['type' => 'string', 'length' => 20, 'default' => 'link'],
            'button_text' => ['type' => 'string', 'length' => 100],
            'button_style' => ['type' => 'string', 'length' => 30, 'default' => 'primary'],
            'button_size' => ['type' => 'string', 'length' => 10, 'default' => 'md'],
            'button_icon' => ['type' => 'string', 'length' => 50],
            'button_full_width' => ['type' => 'integer', 'length' => 1, 'default' => 0],
            'css_class' => ['type' => 'string', 'length' => 255],
            'css_style' => ['type' => 'text'],
            'custom_html' => ['type' => 'text'],
            'badge_text' => ['type' => 'string', 'length' => 100],
            'badge_style' => ['type' => 'string', 'length' => 30, 'default' => 'primary'],
            'show_tooltip' => ['type' => 'string', 'length' => 255],
            'tooltip_position' => ['type' => 'string', 'length' => 10, 'default' => 'top'],
            'animation_type' => ['type' => 'string', 'length' => 20, 'default' => 'none'],
            'animation_duration' => ['type' => 'integer', 'default' => 300],
            'icon_position' => ['type' => 'string', 'length' => 10, 'default' => 'left'],
            'sort_priority' => ['type' => 'integer', 'default' => 0],
            'visibility_roles' => ['type' => 'string', 'length' => 255],
            'visibility_condition' => ['type' => 'text'],
            'metadata' => ['type' => 'text'],
            
            // Border properties
            'border_style' => ['type' => 'string', 'length' => 20, 'default' => 'none'],
            'border_width' => ['type' => 'string', 'length' => 20, 'default' => '1px'],
            'border_color' => ['type' => 'string', 'length' => 20, 'default' => '#000000'],
            'border_position' => ['type' => 'string', 'length' => 20, 'default' => 'all'],
            'border_radius' => ['type' => 'string', 'length' => 10, 'default' => 'none'],
            'border_radius_size' => ['type' => 'string', 'length' => 20],
        ];

        foreach ($columnsToAdd as $column => $config) {
            if (!isset($schema->columns[$column])) {
                $columnSchema = $this->connection->schema->createColumnSchemaBuilder($config['type'], $config['length'] ?? null);
                
                if (isset($config['default'])) {
                    $columnSchema->defaultValue($config['default']);
                }
                
                if (isset($config['after'])) {
                    $columnSchema->after($config['after']);
                }
                
                $this->connection->createCommand()->addColumn('master_menu', $column, $columnSchema)->execute();
            }
        }
    }

    /**
     * Insert default data ke database baru
     */
    private function insertDefaultData(): void
    {
        $this->insertDefaultPages();
        $this->insertDefaultMenus();
    }

    /**
     * Insert default pages
     */
    private function insertDefaultPages(): void
    {
        $pageTable = $this->connection->getTableSchema('master_page', true);
        if ($pageTable === null) {
            return;
        }

        $existingCount = (new \yii\db\Query())
            ->from('master_page')
            ->count('*', $this->connection);

        if ($existingCount > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->connection->createCommand()->batchInsert(
            'master_page',
            ['name', 'slug', 'layout', 'description', 'is_active', 'created_at', 'updated_at'],
            [
                ['Dashboard', 'dashboard', 'single_column', 'Halaman utama dashboard', 1, $now, $now],
                ['Profil', 'profil', 'default', 'Halaman profil perusahaan', 1, $now, $now],
                ['Layanan', 'layanan', 'grid', 'Daftar layanan', 1, $now, $now],
                ['Kontak', 'kontak', 'contact', 'Form kontak', 1, $now, $now],
                ['Artikel', 'artikel', 'blog', 'Daftar artikel', 1, $now, $now],
            ]
        )->execute();
    }

    /**
     * Insert default menus
     */
    private function insertDefaultMenus(): void
    {
        $menuTable = $this->connection->getTableSchema('master_menu', true);
        if ($menuTable === null) {
            return;
        }

        $existingCount = (new \yii\db\Query())
            ->from('master_menu')
            ->count('*', $this->connection);

        if ($existingCount > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $menus = [
            ['name' => 'Dashboard', 'icon' => 'dashboard', 'type' => 'page', 'page_id' => 1, 'sort_order' => 1, 'menu_key' => 'dashboard', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Profil', 'icon' => 'person', 'type' => 'page', 'page_id' => 2, 'sort_order' => 2, 'menu_key' => 'profil', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Layanan', 'icon' => 'shopping_cart', 'type' => 'page', 'page_id' => 3, 'sort_order' => 3, 'menu_key' => 'layanan', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kontak', 'icon' => 'mail', 'type' => 'page', 'page_id' => 4, 'sort_order' => 4, 'menu_key' => 'kontak', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Artikel', 'icon' => 'article', 'type' => 'page', 'page_id' => 5, 'sort_order' => 5, 'menu_key' => 'artikel', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($menus as $menu) {
            $this->connection->createCommand()->insert('master_menu', $menu)->execute();
        }
    }
}
