<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';
$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$projects = Yii::$app->db->createCommand('SELECT * FROM projects')->queryAll();

echo "=== EXISTING PROJECTS ===\n\n";
if (empty($projects)) {
    echo "No projects found. Let's create sample projects.\n";
    
    // Create sample projects
    $now = date('Y-m-d H:i:s');
    
    Yii::$app->db->createCommand()->insert('projects', [
        'name' => 'Project A',
        'description' => 'Sample project A for testing',
        'created_at' => $now,
        'updated_at' => $now,
    ])->execute();
    
    Yii::$app->db->createCommand()->insert('projects', [
        'name' => 'Project B',
        'description' => 'Sample project B for testing',
        'created_at' => $now,
        'updated_at' => $now,
    ])->execute();
    
    $projects = Yii::$app->db->createCommand('SELECT * FROM projects')->queryAll();
}

foreach ($projects as $p) {
    echo "ID: {$p['id']}\n";
    echo "Name: {$p['name']}\n";
    echo "Description: {$p['description']}\n";
    echo "---\n";
}