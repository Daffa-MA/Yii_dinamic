<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== FINAL VERIFICATION ===\n\n";

// 1. Pages
echo "📄 PAGES (master_page):\n";
$pages = Yii::$app->db->createCommand("SELECT id, title, slug, layout, is_active FROM master_page WHERE is_active = 1 ORDER BY id LIMIT 10")->queryAll();
foreach ($pages as $p) {
    echo "   [{$p['id']}] {$p['title']} | slug: {$p['slug']} | layout: {$p['layout']}\n";
}

// 2. Menus with hierarchy
echo "\n📋 MENUS (master_menu) - Active:\n";
$menus = Yii::$app->db->createCommand("
    SELECT 
        m.id, 
        m.name, 
        m.type, 
        m.parent_id, 
        m.page_id, 
        m.route, 
        m.order,
        m.is_active,
        p.name as parent_name
    FROM master_menu m
    LEFT JOIN master_menu p ON m.parent_id = p.id
    WHERE m.is_active = 1
    ORDER BY m.order, m.sort_order
    LIMIT 15
")->queryAll();

foreach ($menus as $m) {
    $indent = $m['parent_id'] ? "   ↳ " : "   • ";
    $target = '';
    if ($m['type'] === 'page' && $m['page_id']) {
        $target = " → Page #{$m['page_id']}";
    } elseif ($m['type'] === 'route' && $m['route']) {
        $target = " → {$m['route']}";
    } elseif ($m['type'] === 'group') {
        $target = " → [Group/Dropdown]";
    }
    echo "{$indent}{$m['name']} (type: {$m['type']}){$target}\n";
}

// 3. Page forms
echo "\n🔗 PAGE-FORMS (page_forms):\n";
$pf = Yii::$app->db->createCommand("SELECT pf.id, pf.page_id, pf.form_id, pf.order, m.title as page_title FROM page_forms pf LEFT JOIN master_page m ON pf.page_id = m.id LIMIT 5")->queryAll();
foreach ($pf as $f) {
    echo "   Page #{$f['page_id']} ({$f['page_title']}) → Form #{$f['form_id']} | order: {$f['order']}\n";
}

echo "\n=== DONE ===\n";