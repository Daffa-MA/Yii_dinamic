<?php
/**
 * Setup master_menu and master_page tables for all project databases
 * 
 * Usage: php setup_master_tables.php
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENV_DEV') or define('YII_ENV_DEV', true);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/config/db.php';

use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\models\Project;

// Set up Yii application
$config = require __DIR__ . '/config/web.php';
new \yii\web\Application($config);

echo "=== Setup Master Tables for All Projects ===\n\n";

$dbContext = new ActiveDatabaseContext();
$projects = Project::find()->all();

if (empty($projects)) {
    echo "No projects found.\n";
    exit;
}

echo "Found " . count($projects) . " projects.\n\n";

foreach ($projects as $project) {
    echo "Processing project: {$project->name} (ID: {$project->id})\n";
    
    try {
        // Get database name for this project
        $dbName = $project->getAttribute('database_name') ?? null;
        
        if (!$dbName) {
            // Try to resolve database name like the controller does
            $controller = new \app\controllers\ProjectController('project', Yii::$app);
            $dbName = $controller->resolveProjectDatabaseName($project);
        }
        
        echo "  Database: {$dbName}\n";
        
        // Connect to project database
        $projectDb = new \yii\db\Connection([
            'dsn' => "mysql:host=127.0.0.1;dbname={$dbName}",
            'username' => Yii::$app->db->username,
            'password' => Yii::$app->db->password,
        ]);
        
        $projectDb->open();
        
        // Check if tables exist
        $menuTableExists = $projectDb->getTableSchema('master_menu', true) !== null;
        $pageTableExists = $projectDb->getTableSchema('master_page', true) !== null;
        
        if ($menuTableExists && $pageTableExists) {
            echo "  ✓ Tables already exist, skipping.\n";
        } else {
            // Create master_page table
            if (!$pageTableExists) {
                $projectDb->createCommand()->createTable('master_page', [
                    'id' => $projectDb->schema->createColumnSchemaBuilder('pk'),
                    'title' => $projectDb->schema->createColumnSchemaBuilder('string', 255)->notNull(),
                    'description' => $projectDb->schema->createColumnSchemaBuilder('string', 500),
                    'layout_type' => $projectDb->schema->createColumnSchemaBuilder('string', 50)->defaultValue('single_column'),
                    'is_active' => $projectDb->schema->createColumnSchemaBuilder('integer', 1)->defaultValue(1),
                    'created_at' => $projectDb->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
                    'updated_at' => $projectDb->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
                ])->execute();
                
                echo "  + Created master_page table\n";
            }
            
            // Create master_menu table
            if (!$menuTableExists) {
                $projectDb->createCommand()->createTable('master_menu', [
                    'id' => $projectDb->schema->createColumnSchemaBuilder('pk'),
                    'parent_id' => $projectDb->schema->createColumnSchemaBuilder('integer'),
                    'page_id' => $projectDb->schema->createColumnSchemaBuilder('integer'),
                    'name' => $projectDb->schema->createColumnSchemaBuilder('string', 100)->notNull(),
                    'icon' => $projectDb->schema->createColumnSchemaBuilder('string', 50),
                    'sort_order' => $projectDb->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
                    'is_active' => $projectDb->schema->createColumnSchemaBuilder('integer', 1)->defaultValue(1),
                    'created_at' => $projectDb->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
                    'updated_at' => $projectDb->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
                ])->execute();
                
                echo "  + Created master_menu table\n";
            }
            
            // Insert default dashboard data
            $dashboardPage = (new \yii\db\Query())->from('master_page')->where(['title' => 'Dashboard'])->one($projectDb);
            if ($dashboardPage === null) {
                $projectDb->createCommand()->insert('master_page', [
                    'title' => 'Dashboard',
                    'description' => 'Halaman utama project',
                    'layout_type' => 'single_column',
                    'is_active' => 1,
                ])->execute();
                echo "  + Inserted default Dashboard page\n";
            }
            
            $dashboardMenu = (new \yii\db\Query())->from('master_menu')->where(['name' => 'Dashboard'])->one($projectDb);
            if ($dashboardMenu === null) {
                $projectDb->createCommand()->insert('master_menu', [
                    'parent_id' => null,
                    'page_id' => 1,
                    'name' => 'Dashboard',
                    'icon' => 'dashboard',
                    'sort_order' => 1,
                    'is_active' => 1,
                ])->execute();
                echo "  + Inserted default Dashboard menu\n";
            }
            
            echo "  ✓ Setup complete!\n";
        }
        
    } catch (\Throwable $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Done ===\n";
