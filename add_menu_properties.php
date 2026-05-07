<?php

/**
 * Migration script to add flexible UI/UX properties to master_menu table
 * Run this script to add new columns to existing databases
 * 
 * Usage: php add_menu_properties.php
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENV_PROD') or define('YII_ENV_PROD', false);
defined('YII_ENV_DEV') or define('YII_ENV_DEV', true);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/console.php';
$app = new yii\console\Application($config);

$db = Yii::$app->db;
$schema = $db->getTableSchema('master_menu', true);

if ($schema === null) {
    echo "Error: master_menu table not found!\n";
    exit(1);
}

$columnsToAdd = [
    'target' => ['type' => 'string', 'length' => 20, 'default' => '_self'],
    'action_type' => ['type' => 'string', 'length' => 20, 'default' => 'link'],
    'button_text' => ['type' => 'string', 'length' => 100],
    'button_style' => ['type' => 'string', 'length' => 30, 'default' => 'primary'],
    'button_size' => ['type' => 'string', 'length' => 10, 'default' => 'md'],
    'button_icon' => ['type' => 'string', 'length' => 50],
    'button_full_width' => ['type' => 'integer', 'length' => 1, 'default' => 0],
    'css_class' => ['type' => 'string', 'length' => 255],
    'css_style' => ['type' => 'text'],
    'custom_html' => ['type' => 'text'],
    'badge_text' => ['type' => 'string', 'length' => 100],
    'badge_style' => ['type' => 'string', 'length' => 30, 'default' => 'primary'],
    'show_tooltip' => ['type' => 'string', 'length' => 255],
    'tooltip_position' => ['type' => 'string', 'length' => 10, 'default' => 'top'],
    'animation_type' => ['type' => 'string', 'length' => 20, 'default' => 'none'],
    'animation_duration' => ['type' => 'integer', 'default' => 300],
    'icon_position' => ['type' => 'string', 'length' => 10, 'default' => 'left'],
    'sort_priority' => ['type' => 'integer', 'default' => 0],
    'visibility_roles' => ['type' => 'string', 'length' => 255],
    'visibility_condition' => ['type' => 'text'],
    'metadata' => ['type' => 'text'],
    
    // Border properties
    'border_style' => ['type' => 'string', 'length' => 20, 'default' => 'none'],
    'border_width' => ['type' => 'string', 'length' => 20, 'default' => '1px'],
    'border_color' => ['type' => 'string', 'length' => 20, 'default' => '#000000'],
    'border_position' => ['type' => 'string', 'length' => 20, 'default' => 'all'],
    'border_radius' => ['type' => 'string', 'length' => 10, 'default' => 'none'],
    'border_radius_size' => ['type' => 'string', 'length' => 20],
];

$added = 0;
$skipped = 0;

foreach ($columnsToAdd as $column => $config) {
    if (isset($schema->columns[$column])) {
        echo "Skipping: {$column} (already exists)\n";
        $skipped++;
        continue;
    }

    try {
        $columnSchema = $db->schema->createColumnSchemaBuilder($config['type'], $config['length'] ?? null);
        
        if (isset($config['default'])) {
            $columnSchema->defaultValue($config['default']);
        }
        
        $db->createCommand()->addColumn('master_menu', $column, $columnSchema)->execute();
        echo "Added: {$column}\n";
        $added++;
    } catch (\Exception $e) {
        echo "Error adding {$column}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Added: {$added} columns\n";
echo "Skipped: {$skipped} columns\n";
echo "Done!\n";