<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Project;
use app\components\ActiveDatabaseContext;
use app\components\DatabaseSchemaInitializer;

/**
 * Database Schema Initialization Console Command
 * 
 * Usage:
 *   php yii db-init/setup-all      - Setup semua existing project databases
 *   php yii db-init/setup-icon     - Tambahkan kolom icon ke master_menu yang existing
 */
class DbInitController extends Controller
{
    public $defaultAction = 'setup-all';

    /**
     * Setup struktur database untuk semua existing projects
     * 
     * @return int
     */
    public function actionSetupAll()
    {
        $this->stdout("=== Database Schema Initialization for All Projects ===\n\n");

        $projects = Project::find()->all();

        if (empty($projects)) {
            $this->stdout("❌ Tidak ada projects ditemukan.\n");
            return ExitCode::OK;
        }

        $this->stdout("📁 Ditemukan " . count($projects) . " project(s)\n\n");

        $databaseContext = new ActiveDatabaseContext();
        $projectController = new \app\controllers\ProjectController();
        
        $successCount = 0;
        $errorCount = 0;

        foreach ($projects as $project) {
            try {
                $databaseName = $projectController->resolveProjectDatabaseName($project);
                
                $this->stdout("Processing: " . $project->name . " (ID: {$project->id}, DB: {$databaseName})\n", 'info');

                if (!$databaseContext->databaseExistsOnCurrentServer($databaseName)) {
                    $this->stdout("  ⚠️  Database tidak ada, skip...\n");
                    continue;
                }

                // Initialize database schema
                DatabaseSchemaInitializer::initializeProjectDatabase($databaseName);

                $this->stdout("  ✅ Database berhasil di-setup\n", 'success');
                $successCount++;
            } catch (\Exception $e) {
                $this->stdout("  ❌ Error: " . $e->getMessage() . "\n", 'error');
                $errorCount++;
            }
        }

        $this->stdout("\n=== Setup Complete ===\n", 'info');
        $this->stdout("✅ Success: $successCount\n", 'success');
        if ($errorCount > 0) {
            $this->stdout("❌ Failed: $errorCount\n", 'error');
        }

        return ExitCode::OK;
    }    /**
     * Tambahkan kolom icon ke master_menu yang existing
     * 
     * @return int
     */
    public function actionSetupIcon()
    {
        $this->stdout("=== Add Icon Column to master_menu ===\n\n");

        $projects = Project::find()->all();

        if (empty($projects)) {
            $this->stdout("❌ Tidak ada projects ditemukan.\n");
            return ExitCode::OK;
        }

        $this->stdout("📁 Ditemukan " . count($projects) . " project(s)\n\n");

        $databaseContext = new ActiveDatabaseContext();
        $projectController = new \app\controllers\ProjectController();
        
        $successCount = 0;
        $errorCount = 0;
        $skipCount = 0;

        foreach ($projects as $project) {
            try {
                $databaseName = $projectController->resolveProjectDatabaseName($project);
                
                $this->stdout("Processing: " . $project->name . " (ID: {$project->id}, DB: {$databaseName})\n", 'info');

                if (!$databaseContext->databaseExistsOnCurrentServer($databaseName)) {
                    $this->stdout("  ⚠️  Database tidak ada, skip...\n");
                    $skipCount++;
                    continue;
                }

                // Connect to project database
                $projectDb = $this->getProjectConnection($databaseName);
                
                // Check if master_menu exists
                $schema = $projectDb->getTableSchema('master_menu', true);
                if ($schema === null) {
                    $this->stdout("  ⚠️  master_menu table tidak ada, skip...\n");
                    $skipCount++;
                    continue;
                }

                // Check if icon column already exists
                if (isset($schema->columns['icon'])) {
                    $this->stdout("  ℹ️  Kolom icon sudah ada, skip...\n");
                    $skipCount++;
                    continue;
                }

                // Add icon column
                $projectDb->createCommand()->addColumn(
                    'master_menu',
                    'icon',
                    $projectDb->schema->createColumnSchemaBuilder('string', 50)->after('name')
                )->execute();

                $this->stdout("  ✅ Kolom icon berhasil ditambahkan\n", 'success');
                $successCount++;
            } catch (\Exception $e) {
                $this->stdout("  ❌ Error: " . $e->getMessage() . "\n", 'error');
                $errorCount++;
            }
        }

        $this->stdout("\n=== Setup Complete ===\n", 'info');
        $this->stdout("✅ Success: $successCount\n", 'success');
        if ($skipCount > 0) {
            $this->stdout("ℹ️  Skipped: $skipCount\n");
        }
        if ($errorCount > 0) {
            $this->stdout("❌ Failed: $errorCount\n", 'error');
        }

        return ExitCode::OK;
    }

    /**
     * Tambahkan kolom menu_key dan kolom lainnya yang mungkin missing
     * 
     * @return int
     */
    public function actionAddMissingColumns()
    {
        $this->stdout("=== Add Missing Columns to master_menu ===\n\n");

        $projects = Project::find()->all();

        if (empty($projects)) {
            $this->stdout("❌ Tidak ada projects ditemukan.\n");
            return ExitCode::OK;
        }

        $this->stdout("📁 Ditemukan " . count($projects) . " project(s)\n\n");

        $databaseContext = new ActiveDatabaseContext();
        $projectController = new \app\controllers\ProjectController();
        
        $successCount = 0;
        $errorCount = 0;
        $skipCount = 0;
        
        $columnsToCheck = ['icon', 'menu_key', 'type', 'route'];

        foreach ($projects as $project) {
            try {
                $databaseName = $projectController->resolveProjectDatabaseName($project);
                
                $this->stdout("Processing: " . $project->name . " (ID: {$project->id}, DB: {$databaseName})\n", 'info');

                if (!$databaseContext->databaseExistsOnCurrentServer($databaseName)) {
                    $this->stdout("  ⚠️  Database tidak ada, skip...\n");
                    $skipCount++;
                    continue;
                }

                // Connect to project database
                $projectDb = $this->getProjectConnection($databaseName);
                
                // Check if master_menu exists
                $schema = $projectDb->getTableSchema('master_menu', true);
                if ($schema === null) {
                    $this->stdout("  ⚠️  master_menu table tidak ada, skip...\n");
                    $skipCount++;
                    continue;
                }

                $addedColumns = [];
                
                // Add missing columns
                foreach ($columnsToCheck as $columnName) {
                    if (!isset($schema->columns[$columnName])) {
                        try {
                            if ($columnName === 'icon') {
                                $projectDb->createCommand()->addColumn('master_menu', $columnName, $projectDb->schema->createColumnSchemaBuilder('string', 50)->after('name'))->execute();
                            } elseif ($columnName === 'menu_key') {
                                $projectDb->createCommand()->addColumn('master_menu', $columnName, $projectDb->schema->createColumnSchemaBuilder('string', 50))->execute();
                            } elseif ($columnName === 'type') {
                                $projectDb->createCommand()->addColumn('master_menu', $columnName, $projectDb->schema->createColumnSchemaBuilder('string', 20)->defaultValue('page'))->execute();
                            } elseif ($columnName === 'route') {
                                $projectDb->createCommand()->addColumn('master_menu', $columnName, $projectDb->schema->createColumnSchemaBuilder('string', 255))->execute();
                            }
                            $addedColumns[] = $columnName;
                        } catch (\Exception $e) {
                            // Kolom mungkin sudah ada atau error lain
                        }
                    }
                }
                
                if (!empty($addedColumns)) {
                    $this->stdout("  ✅ Kolom ditambahkan: " . implode(', ', $addedColumns) . "\n", 'success');
                    $successCount++;
                } else {
                    $this->stdout("  ℹ️  Semua kolom sudah ada\n");
                    $skipCount++;
                }
            } catch (\Exception $e) {
                $this->stdout("  ❌ Error: " . $e->getMessage() . "\n", 'error');
                $errorCount++;
            }
        }

        $this->stdout("\n=== Setup Complete ===\n", 'info');
        $this->stdout("✅ Success: $successCount\n", 'success');
        if ($skipCount > 0) {
            $this->stdout("ℹ️  Skipped: $skipCount\n");
        }
        if ($errorCount > 0) {
            $this->stdout("❌ Failed: $errorCount\n", 'error');
        }

        return ExitCode::OK;
    }

    /**
     * Get database connection untuk project
     * 
     * @param string $databaseName
     * @return \yii\db\Connection
     */
    private function getProjectConnection(string $databaseName): \yii\db\Connection
    {
        // Parse DSN untuk get host dan port
        $mainDb = Yii::$app->db;
        preg_match('/host=([^;]+)/', $mainDb->dsn, $hostMatch);
        preg_match('/port=([^;]+)/', $mainDb->dsn, $portMatch);
        
        $host = $hostMatch[1] ?? 'localhost';
        $port = !empty($portMatch[1]) ? (int)$portMatch[1] : 3306;

        $dsn = "mysql:host={$host};port={$port};dbname={$databaseName}";
        
        return new \yii\db\Connection([
            'dsn' => $dsn,
            'username' => $mainDb->username,
            'password' => $mainDb->password,
        ]);
    }
}
