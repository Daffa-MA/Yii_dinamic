<?php

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

echo "Creating missing tables...\n\n";

// Create master_page_form table
$tableExists = $db->getTableSchema('master_page_form', true);
if ($tableExists === null) {
    echo "Creating master_page_form table...\n";
    $db->createCommand()->createTable('master_page_form', [
        'id' => $db->schema->createColumnSchemaBuilder('pk'),
        'page_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
        'form_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
        'sort_order' => $db->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
        'created_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
        'updated_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
    ])->execute();
    
    $db->createCommand()->createIndex('idx-master_page_form-page_id', 'master_page_form', 'page_id')->execute();
    $db->createCommand()->createIndex('idx-master_page_form-form_id', 'master_page_form', 'form_id')->execute();
    echo "OK!\n";
} else {
    echo "master_page_form already exists.\n";
}

// Also ensure master_menu has required columns
$menuColumns = [
    'target' => ['string', 20, '_self'],
    'action_type' => ['string', 20, 'link'],
    'button_text' => ['string', 100, null],
    'button_style' => ['string', 30, 'primary'],
    'button_size' => ['string', 10, 'md'],
    'button_icon' => ['string', 50, null],
    'button_full_width' => ['integer', 1, 0],
    'css_class' => ['string', 255, null],
    'css_style' => ['text', null, null],
    'custom_html' => ['text', null, null],
    'badge_text' => ['string', 100, null],
    'badge_style' => ['string', 30, 'primary'],
    'show_tooltip' => ['string', 255, null],
    'tooltip_position' => ['string', 10, 'top'],
    'animation_type' => ['string', 20, 'none'],
    'animation_duration' => ['integer', null, 300],
    'icon_position' => ['string', 10, 'left'],
    'sort_priority' => ['integer', null, 0],
    'visibility_roles' => ['string', 255, null],
    'visibility_condition' => ['text', null, null],
    'metadata' => ['text', null, null],
    'border_style' => ['string', 20, 'none'],
    'border_width' => ['string', 20, '1px'],
    'border_color' => ['string', 20, '#000000'],
    'border_position' => ['string', 20, 'all'],
    'border_radius' => ['string', 10, 'none'],
    'border_radius_size' => ['string', 20, null],
];

$schema = $db->getTableSchema('master_menu', true);
if ($schema) {
    echo "\nChecking master_menu columns...\n";
    foreach ($menuColumns as $col => $config) {
        if (!isset($schema->columns[$col])) {
            $type = $config[0];
            $length = $config[1];
            $default = $config[2];
            
            $colSchema = $db->schema->createColumnSchemaBuilder($type, $length);
            if ($default !== null) {
                $colSchema->defaultValue($default);
            }
            $db->createCommand()->addColumn('master_menu', $col, $colSchema)->execute();
            echo "Added: $col\n";
        }
    }
}

echo "\nDone!\n";