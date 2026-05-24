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
    private const DB_TABLE_COLUMNS_TABLE = 'db_table_columns';

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
     * Repair foreign key metadata columns in db_table_columns.
     *
     * Usage:
     *   php yii db-init/repair-fk-metadata sekolah_negeri
     *   php yii db-init/repair-fk-metadata all
     */
    public function actionRepairFkMetadata(?string $databaseName = null): int
    {
        $this->stdout("=== Repair FK metadata columns ===\n\n");

        $databaseNames = [];
        if (in_array(strtolower(trim((string)$databaseName)), ['all', '*'], true)) {
            $projects = Project::find()->all();
            $projectController = new \app\controllers\ProjectController();
            foreach ($projects as $project) {
                $databaseNames[] = $projectController->resolveProjectDatabaseName($project);
            }
            $databaseNames = array_values(array_unique(array_filter($databaseNames)));
        } elseif ($databaseName !== null && trim($databaseName) !== '') {
            $databaseNames[] = trim($databaseName);
        } else {
            $databaseNames[] = $this->resolveCurrentDatabaseName(Yii::$app->db);
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($databaseNames as $name) {
            try {
                $this->stdout("Processing database: {$name}\n");
                $db = $this->getProjectConnection($name);
                $this->repairFkMetadataOnConnection($db);
                $this->stdout("  OK: FK metadata columns ready\n");
                $successCount++;
            } catch (\Throwable $e) {
                $this->stderr("  ERROR: " . $e->getMessage() . "\n");
                $errorCount++;
            }
        }

        $this->stdout("\nDone. Success: {$successCount}, Failed: {$errorCount}\n");
        return $errorCount > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }

    private function repairFkMetadataOnConnection(\yii\db\Connection $db): void
    {
        $this->ensureTableBuilderMetadataTables($db);

        $schema = $db->schema->getTableSchema(self::DB_TABLE_COLUMNS_TABLE, true);
        if ($schema === null) {
            throw new \RuntimeException("Table '" . self::DB_TABLE_COLUMNS_TABLE . "' does not exist.");
        }

        $columns = $schema->columns;
        if (!isset($columns['is_foreign_key'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'is_foreign_key',
                $db->schema->createColumnSchemaBuilder('boolean')->notNull()->defaultValue(false)
            )->execute();
        }

        if (!isset($columns['referenced_table_name'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'referenced_table_name',
                $db->schema->createColumnSchemaBuilder('string', 100)
            )->execute();
        }

        if (!isset($columns['referenced_column_name'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'referenced_column_name',
                $db->schema->createColumnSchemaBuilder('string', 100)
            )->execute();
        }

        if (!isset($columns['on_delete_action'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'on_delete_action',
                $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('RESTRICT')
            )->execute();
        }

        if (!isset($columns['on_update_action'])) {
            $db->createCommand()->addColumn(
                self::DB_TABLE_COLUMNS_TABLE,
                'on_update_action',
                $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('RESTRICT')
            )->execute();
        }

        $db->schema->refresh();
        $db->schema->refreshTableSchema(self::DB_TABLE_COLUMNS_TABLE);
    }

    private function ensureTableBuilderMetadataTables(\yii\db\Connection $db): void
    {
        if ($db->schema->getTableSchema('db_tables', true) === null) {
            $db->createCommand()->createTable('db_tables', [
                'id' => $db->schema->createColumnSchemaBuilder('pk'),
                'user_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
                'project_id' => $db->schema->createColumnSchemaBuilder('integer'),
                'name' => $db->schema->createColumnSchemaBuilder('string', 100)->notNull(),
                'label' => $db->schema->createColumnSchemaBuilder('string', 255)->notNull(),
                'description' => $db->schema->createColumnSchemaBuilder('text'),
                'engine' => $db->schema->createColumnSchemaBuilder('string', 20)->defaultValue('InnoDB'),
                'charset' => $db->schema->createColumnSchemaBuilder('string', 20)->defaultValue('utf8mb4'),
                'collation' => $db->schema->createColumnSchemaBuilder('string', 50)->defaultValue('utf8mb4_unicode_ci'),
                'is_created' => $db->schema->createColumnSchemaBuilder('boolean')->notNull()->defaultValue(false),
                'table_status' => $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('pending'),
                'last_error_message' => $db->schema->createColumnSchemaBuilder('text'),
                'created_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ])->execute();
            $this->createIndexIfMissing($db, 'db_tables', 'idx-db_tables-user_id', ['user_id']);
            $this->createIndexIfMissing($db, 'db_tables', 'idx-db_tables-project_id', ['project_id']);
            $this->createIndexIfMissing($db, 'db_tables', 'uq-db_tables-user_project_name', ['user_id', 'project_id', 'name'], true);
        }

        if ($db->schema->getTableSchema(self::DB_TABLE_COLUMNS_TABLE, true) === null) {
            $db->createCommand()->createTable(self::DB_TABLE_COLUMNS_TABLE, [
                'id' => $db->schema->createColumnSchemaBuilder('pk'),
                'table_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
                'name' => $db->schema->createColumnSchemaBuilder('string', 100)->notNull(),
                'label' => $db->schema->createColumnSchemaBuilder('string', 255)->notNull(),
                'type' => $db->schema->createColumnSchemaBuilder('string', 50)->notNull(),
                'length' => $db->schema->createColumnSchemaBuilder('integer'),
                'is_nullable' => $db->schema->createColumnSchemaBuilder('boolean')->defaultValue(true),
                'is_primary' => $db->schema->createColumnSchemaBuilder('boolean')->defaultValue(false),
                'is_unique' => $db->schema->createColumnSchemaBuilder('boolean')->defaultValue(false),
                'is_auto_increment' => $db->schema->createColumnSchemaBuilder('boolean')->notNull()->defaultValue(false),
                'is_foreign_key' => $db->schema->createColumnSchemaBuilder('boolean')->notNull()->defaultValue(false),
                'referenced_table_name' => $db->schema->createColumnSchemaBuilder('string', 100),
                'referenced_column_name' => $db->schema->createColumnSchemaBuilder('string', 100),
                'on_delete_action' => $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('RESTRICT'),
                'on_update_action' => $db->schema->createColumnSchemaBuilder('string', 20)->notNull()->defaultValue('RESTRICT'),
                'default_value' => $db->schema->createColumnSchemaBuilder('string', 255),
                'comment' => $db->schema->createColumnSchemaBuilder('text'),
                'enum_values' => $db->schema->createColumnSchemaBuilder('text'),
                'sort_order' => $db->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
                'created_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
            ])->execute();
            $this->createIndexIfMissing($db, self::DB_TABLE_COLUMNS_TABLE, 'idx-db_table_columns-table_id', ['table_id']);
        }

        $db->schema->refresh();
    }

    private function createIndexIfMissing(\yii\db\Connection $db, string $tableName, string $indexName, array $columns, bool $unique = false): void
    {
        try {
            $db->createCommand()->createIndex($indexName, $tableName, $columns, $unique)->execute();
        } catch (\Throwable $e) {
        }
    }

    private function resolveCurrentDatabaseName(\yii\db\Connection $db): string
    {
        if (preg_match('/dbname=([^;]+)/i', (string)$db->dsn, $matches) === 1) {
            return trim((string)$matches[1]);
        }

        return trim((string)$db->createCommand('SELECT DATABASE()')->queryScalar());
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
