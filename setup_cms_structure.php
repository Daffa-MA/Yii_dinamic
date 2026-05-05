<?php
/**
 * Script untuk setup struktur database yang proper untuk Dynamic CMS
 * termasuk foreign keys dan constraint
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$db = Yii::$app->db;

echo "=== SETUP DYNAMIC CMS DATABASE STRUCTURE ===\n\n";

try {
    // 1. Add Foreign Keys to master_menu
    echo "1. Setting up master_menu foreign keys...\n";
    
    try {
        $db->createCommand("ALTER TABLE master_menu 
            ADD CONSTRAINT fk_master_menu_parent 
            FOREIGN KEY (parent_id) REFERENCES master_menu(id) 
            ON DELETE SET NULL ON UPDATE CASCADE")->execute();
        echo "   - FK parent_id added\n";
    } catch (Exception $e) {
        echo "   - FK parent_id: " . $e->getMessage() . "\n";
    }
    
    try {
        $db->createCommand("ALTER TABLE master_menu 
            ADD CONSTRAINT fk_master_menu_page 
            FOREIGN KEY (page_id) REFERENCES master_page(id) 
            ON DELETE SET NULL ON UPDATE CASCADE")->execute();
        echo "   - FK page_id added\n";
    } catch (Exception $e) {
        echo "   - FK page_id: " . $e->getMessage() . "\n";
    }
    
    // 2. Add Foreign Keys to page_forms
    echo "\n2. Setting up page_forms foreign keys...\n";
    
    try {
        $db->createCommand("ALTER TABLE page_forms 
            ADD CONSTRAINT fk_page_forms_page 
            FOREIGN KEY (page_id) REFERENCES master_page(id) 
            ON DELETE CASCADE ON UPDATE CASCADE")->execute();
        echo "   - FK page_id added\n";
    } catch (Exception $e) {
        echo "   - FK page_id: " . $e->getMessage() . "\n";
    }
    
    try {
        $db->createCommand("ALTER TABLE page_forms 
            ADD CONSTRAINT fk_page_forms_form 
            FOREIGN KEY (form_id) REFERENCES form(id) 
            ON DELETE CASCADE ON UPDATE CASCADE")->execute();
        echo "   - FK form_id added\n";
    } catch (Exception $e) {
        echo "   - FK form_id: " . $e->getMessage() . "\n";
    }
    
    // 3. Add unique index to master_page.slug
    echo "\n3. Setting up indexes...\n";
    
    try {
        $db->createCommand("ALTER TABLE master_page ADD UNIQUE INDEX idx_master_page_slug (slug)")->execute();
        echo "   - Unique index on slug added\n";
    } catch (Exception $e) {
        echo "   - Unique index: " . $e->getMessage() . "\n";
    }
    
    // 4. Create indexes for better performance
    try {
        $db->createCommand("CREATE INDEX idx_master_menu_parent ON master_menu(parent_id)")->execute();
        $db->createCommand("CREATE INDEX idx_master_menu_type ON master_menu(type)")->execute();
        $db->createCommand("CREATE INDEX idx_master_menu_is_active ON master_menu(is_active)")->execute();
        $db->createCommand("CREATE INDEX idx_master_menu_order ON master_menu(`order`)")->execute();
        $db->createCommand("CREATE INDEX idx_page_forms_page_form ON page_forms(page_id, form_id)")->execute();
        echo "   - Performance indexes created\n";
    } catch (Exception $e) {
        echo "   - Indexes: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== DATABASE STRUCTURE SETUP COMPLETE ===\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}