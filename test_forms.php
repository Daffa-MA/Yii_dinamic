<?php
/**
 * Test PageDisplayService - with forms
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$service = new \app\services\PageDisplayService();

// Get page 8 which has forms
echo "=== PAGE 8 (dengan forms) ===\n\n";
$page = $service->getPage(8);
if ($page) {
    echo "Page: {$page->title}\n";
    echo "Slug: {$page->slug}\n";
}

// Get forms for page 8
echo "\n=== FORMS for Page 8 ===\n\n";
$forms = $service->getPageForms(8);
echo "Total forms: " . count($forms) . "\n";
foreach ($forms as $form) {
    echo "  - ID: {$form->id}, Name: {$form->name}\n";
    echo "    Schema (first 200 chars): " . substr($form->schema_js ?? '', 0, 200) . "\n\n";
}

// Test get page by slug
echo "=== GET PAGE by slug 'data-siswa' ===\n\n";
$pageBySlug = $service->getPage('data-siswa');
if ($pageBySlug) {
    echo "Page: {$pageBySlug->title}\n";
    echo "ID: {$pageBySlug->id}\n";
}

echo "\n=== DONE ===\n";