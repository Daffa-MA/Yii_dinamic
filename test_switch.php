<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

use app\models\Project;
use yii\db\Connection;

echo "Initial DB DSN: " . Yii::$app->db->dsn . "\n";
echo "Metadata DB DSN: " . Yii::$app->metadataDb->dsn . "\n";

echo "Testing Project lookup (primary)... ";
try {
    $count = Project::find()->count();
    echo "Success: $count projects found.\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}

echo "Simulating switch of 'db' component...\n";
$newDb = Yii::createObject([
    'class' => Connection::class,
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=sekolah',
    'username' => 'root',
    'password' => '',
]);
Yii::$app->set('db', $newDb);

echo "Switched DB DSN: " . Yii::$app->db->dsn . "\n";
echo "Metadata DB DSN (should be same): " . Yii::$app->metadataDb->dsn . "\n";

echo "Testing Project lookup after switch... ";
try {
    $count = Project::find()->count();
    echo "Success: $count projects found.\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
