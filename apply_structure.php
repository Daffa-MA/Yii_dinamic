<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$db = Yii::$app->db;

try {
    // 1. Add type column to master_menu (group, page, route)
    echo "Adding type column to master_menu...\n";
    try {
        $db->createCommand("ALTER TABLE master_menu ADD COLUMN type ENUM('group', 'page', 'route') DEFAULT 'page' AFTER parent_id")->execute();
        echo "  - type column added\n";
    } catch (Exception $e) {
        echo "  - type column already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 2. Add order column
    echo "Adding order column to master_menu...\n";
    try {
        $db->createCommand("ALTER TABLE master_menu ADD COLUMN `order` INT NOT NULL DEFAULT 0 AFTER is_active")->execute();
        $db->createCommand("UPDATE master_menu SET `order` = sort_order WHERE `order` = 0")->execute();
        echo "  - order column added\n";
    } catch (Exception $e) {
        echo "  - order column already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 3. Add slug to master_page
    echo "Adding slug column to master_page...\n";
    try {
        $db->createCommand("ALTER TABLE master_page ADD COLUMN slug VARCHAR(100) DEFAULT NULL AFTER title")->execute();
        echo "  - slug column added\n";
    } catch (Exception $e) {
        echo "  - slug column already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 4. Add layout column to master_page
    echo "Adding layout column to master_page...\n";
    try {
        $db->createCommand("ALTER TABLE master_page ADD COLUMN layout VARCHAR(50) DEFAULT 'default' AFTER slug")->execute();
        echo "  - layout column added\n";
    } catch (Exception $e) {
        echo "  - layout column already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 5. Create page_forms table
    echo "Creating page_forms table...\n";
    try {
        $db->createCommand("CREATE TABLE IF NOT EXISTS page_forms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_id INT NOT NULL,
            form_id INT NOT NULL,
            `order` INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )")->execute();
        $db->createCommand("CREATE INDEX idx_page_forms_page ON page_forms(page_id)")->execute();
        $db->createCommand("CREATE INDEX idx_page_forms_form ON page_forms(form_id)")->execute();
        echo "  - page_forms table created\n";
    } catch (Exception $e) {
        echo "  - page_forms table error: " . $e->getMessage() . "\n";
    }
    
    // 6. Set default type for existing menus
    echo "Updating menu types...\n";
    $db->createCommand("UPDATE master_menu SET type = 'group' WHERE parent_id IS NOT NULL AND parent_id != ''")->execute();
    $db->createCommand("UPDATE master_menu SET type = 'route' WHERE route IS NOT NULL AND route != ''")->execute();
    $db->createCommand("UPDATE master_menu SET type = 'page' WHERE type IS NULL OR type = ''")->execute();
    $db->createCommand("UPDATE master_menu SET type = 'group' WHERE type = 'page' AND (parent_id IS NOT NULL AND parent_id != '')")->execute();
    echo "  - menu types updated\n";
    
    echo "\n=== DONE ===\n";
    
    // Show new structure
    echo "\n=== New master_menu structure ===\n";
    $cols = $db->createCommand("SHOW COLUMNS FROM master_menu")->queryAll();
    foreach ($cols as $col) {
        echo "- " . $col['Field'] . "\n";
    }
    
    echo "\n=== New master_page structure ===\n";
    $cols = $db->createCommand("SHOW COLUMNS FROM master_page")->queryAll();
    foreach ($cols as $col) {
        echo "- " . $col['Field'] . "\n";
    }
    
    echo "\n=== page_forms structure ===\n";
    $cols = $db->createCommand("SHOW COLUMNS FROM page_forms")->queryAll();
    foreach ($cols as $col) {
        echo "- " . $col['Field'] . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}