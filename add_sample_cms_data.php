<?php
/**
 * Add sample data for Dynamic CMS
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$db = Yii::$app->db;

echo "=== ADDING SAMPLE DATA FOR DYNAMIC CMS ===\n\n";

try {
    // Add FK for form_id
    echo "1. Adding FK to forms table...\n";
    try {
        $db->createCommand("ALTER TABLE page_forms 
            ADD CONSTRAINT fk_page_forms_form 
            FOREIGN KEY (form_id) REFERENCES forms(id) 
            ON DELETE CASCADE ON UPDATE CASCADE")->execute();
        echo "   - FK form_id added successfully\n";
    } catch (Exception $e) {
        echo "   - FK form_id: " . $e->getMessage() . "\n";
    }
    
    // 2. Add Sample Pages
    echo "\n2. Adding sample pages...\n";
    
    $pages = [
        ['title' => 'Dashboard', 'slug' => 'dashboard', 'layout' => 'dashboard', 'description' => 'Halaman utama dashboard'],
        ['title' => 'Data Siswa', 'slug' => 'data-siswa', 'layout' => 'list', 'description' => 'Kelola data siswa'],
        ['title' => 'Data Guru', 'slug' => 'data-guru', 'layout' => 'list', 'description' => 'Kelola data guru'],
        ['title' => 'Absensi', 'slug' => 'absensi', 'layout' => 'form', 'description' => 'Pencatatan absensi'],
        ['title' => 'Nilai Siswa', 'slug' => 'nilai-siswa', 'layout' => 'list', 'description' => 'Penilaian siswa'],
    ];
    
    $pageIds = [];
    foreach ($pages as $p) {
        // Check if exists
        $exists = $db->createCommand("SELECT id FROM master_page WHERE slug = '{$p['slug']}'")->queryScalar();
        if (!$exists) {
            $db->createCommand()->insert('master_page', [
                'title' => $p['title'],
                'slug' => $p['slug'],
                'layout' => $p['layout'],
                'description' => $p['description'],
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->execute();
            $pageIds[$p['slug']] = $db->getLastInsertID();
            echo "   - Added: {$p['title']}\n";
        } else {
            $pageIds[$p['slug']] = $exists;
            echo "   - Exists: {$p['title']}\n";
        }
    }
    
    // 3. Add Sample Menus
    echo "\n3. Adding sample menus...\n";
    
    // First clear old menus for clean start (optional - comment out if you want to keep existing)
    // $db->createCommand("DELETE FROM master_menu")->execute();
    
    $menus = [
        // Main menus
        ['name' => 'Dashboard', 'type' => 'route', 'route' => '/site/dashboard', 'order' => 1, 'icon' => 'dashboard'],
        ['name' => 'Data Master', 'type' => 'group', 'order' => 2, 'icon' => 'folder'],
        ['name' => 'Akademik', 'type' => 'group', 'order' => 3, 'icon' => 'school'],
        ['name' => 'Laporan', 'type' => 'group', 'order' => 4, 'icon' => 'assessment'],
        
        // Submenu - Data Master children
        ['name' => 'Data Siswa', 'type' => 'page', 'page_id' => $pageIds['data-siswa'], 'parent_name' => 'Data Master', 'order' => 1, 'icon' => 'person'],
        ['name' => 'Data Guru', 'type' => 'page', 'page_id' => $pageIds['data-guru'], 'parent_name' => 'Data Master', 'order' => 2, 'icon' => 'group'],
        
        // Submenu - Akademik children
        ['name' => 'Absensi', 'type' => 'page', 'page_id' => $pageIds['absensi'], 'parent_name' => 'Akademik', 'order' => 1, 'icon' => 'event_available'],
        ['name' => 'Nilai Siswa', 'type' => 'page', 'page_id' => $pageIds['nilai-siswa'], 'parent_name' => 'Akademik', 'order' => 2, 'icon' => 'grade'],
    ];
    
    // Get existing menu IDs for parent lookup
    $existingMenus = $db->createCommand("SELECT id, name FROM master_menu")->queryAll();
    $menuIdByName = array_combine(array_column($existingMenus, 'name'), array_column($existingMenus, 'id'));
    
    $createdIds = [];
    foreach ($menus as $m) {
        $parentId = null;
        if (!empty($m['parent_name'])) {
            $parentId = $menuIdByName[$m['parent_name']] ?? null;
        }
        
        // Check if exists by name
        $exists = $db->createCommand("SELECT id FROM master_menu WHERE name = '{$m['name']}'")->queryScalar();
        
        if (!$exists) {
            $db->createCommand()->insert('master_menu', [
                'parent_id' => $parentId,
                'type' => $m['type'],
                'page_id' => $m['page_id'] ?? null,
                'route' => $m['route'] ?? null,
                'name' => $m['name'],
                'icon' => $m['icon'] ?? 'folder',
                'sort_order' => $m['order'],
                'order' => $m['order'],
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->execute();
            
            $newId = $db->getLastInsertID();
            $menuIdByName[$m['name']] = $newId;
            $createdIds[] = $newId;
            echo "   - Added: {$m['name']} (type: {$m['type']})" . ($parentId ? " [submenu]" : "") . "\n";
        } else {
            $menuIdByName[$m['name']] = $exists;
            echo "   - Exists: {$m['name']}\n";
        }
    }
    
    // Update parent_id for children that were just created
    foreach ($menus as $m) {
        if (!empty($m['parent_name']) && !empty($createdIds)) {
            // This is simplified - in production you'd use proper IDs
        }
    }
    
    // 4. Add Sample page_forms relationships (if forms exist)
    echo "\n4. Setting up page-form relationships...\n";
    
    // Check if forms exist
    $formCount = $db->createCommand("SELECT COUNT(*) FROM forms")->queryScalar();
    if ($formCount > 0) {
        // Get first form for demo
        $firstFormId = $db->createCommand("SELECT id FROM forms LIMIT 1")->queryScalar();
        
        // Link form to page
        if ($pageIds['data-siswa'] && $firstFormId) {
            $linkExists = $db->createCommand("SELECT id FROM page_forms WHERE page_id = {$pageIds['data-siswa']} AND form_id = {$firstFormId}")->queryScalar();
            if (!$linkExists) {
                $db->createCommand()->insert('page_forms', [
                    'page_id' => $pageIds['data-siswa'],
                    'form_id' => $firstFormId,
                    'order' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ])->execute();
                echo "   - Linked form to Data Siswa page\n";
            }
        }
    } else {
        echo "   - No forms found, skipping page_forms\n";
    }
    
    echo "\n=== SAMPLE DATA ADDED ===\n\n";
    
    // Show summary
    echo "=== SUMMARY ===\n";
    echo "Pages: " . $db->createCommand("SELECT COUNT(*) FROM master_page WHERE is_active = 1")->queryScalar() . " active\n";
    echo "Menus: " . $db->createCommand("SELECT COUNT(*) FROM master_menu WHERE is_active = 1")->queryScalar() . " active\n";
    echo "Page-Form links: " . $db->createCommand("SELECT COUNT(*) FROM page_forms")->queryScalar() . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}