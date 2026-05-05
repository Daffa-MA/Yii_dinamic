<?php
/**
 * Test PageDisplayService - Handle menu click logic
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

echo "=== PAGE DISPLAY SERVICE TEST ===\n\n";

$service = new \app\services\PageDisplayService();

// Test: Handle Menu Click - Page type (menu ID 5 = Data Siswa, type=page, page_id=2)
echo "📋 HANDLE CLICK - PAGE TYPE (ID 5 - Data Siswa):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$result = $service->handleMenuClick(5);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Test: Handle Menu Click - Page type (menu ID 8 = Nilai Siswa, type=page, page_id=3)
echo "📋 HANDLE CLICK - PAGE TYPE (ID 8 - Nilai Siswa):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$result2 = $service->handleMenuClick(8);
echo json_encode($result2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "=== TEST COMPLETE ===\n";