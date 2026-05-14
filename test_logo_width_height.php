<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$db = Yii::$app->db;

// Query workspace_settings table
$result = $db->createCommand('SELECT * FROM workspace_settings LIMIT 1')->queryOne();

echo "<h2>Workspace Settings in Database:</h2>";
echo "<pre>";
print_r($result);
echo "</pre>";

// Query specifically for logo dimensions
if ($result) {
    echo "<h2>Logo Dimensions:</h2>";
    echo "workspace_logo_width: " . ($result['workspace_logo_width'] ?? 'NOT SET') . "<br>";
    echo "workspace_logo_height: " . ($result['workspace_logo_height'] ?? 'NOT SET') . "<br>";
}

// Check model
$model = new \app\models\WorkspaceSettings();
$model->loadFromSession();
echo "<h2>Model Properties:</h2>";
echo "workspace_logo_width: " . ($model->workspace_logo_width ?? 'NULL') . "<br>";
echo "workspace_logo_height: " . ($model->workspace_logo_height ?? 'NULL') . "<br>";
echo "All model attributes:<br>";
echo "<pre>";
print_r($model->attributes);
echo "</pre>";
?>
