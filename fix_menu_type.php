<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

// Update menu ID 5 (Data Siswa - group) to become type=page
$menu = \app\models\MasterMenu::findOne(5);
if ($menu) {
    $menu->type = 'page';
    $menu->save(false);
    echo "Updated menu ID 5 to type=page\n";
}

// Update menu ID 8 (Nilai Siswa - group) to become type=page  
$menu2 = \app\models\MasterMenu::findOne(8);
if ($menu2) {
    $menu2->type = 'page';
    $menu2->save(false);
    echo "Updated menu ID 8 to type=page\n";
}

echo "\nDone!";