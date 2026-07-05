<?php

namespace app\commands;

use app\components\ActiveDatabaseContext;
use app\models\Project;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\helpers\Console;

/**
 * Manages migrations for all project databases.
 * 
 * This controller ensures migrations are only applied ONCE per database.
 * Before running any migration, it syncs the tracking table with the actual
 * database schema to prevent re-applying already-existing changes.
 */
class MigrateController extends Controller
{
    /**
     * @var bool Whether to run the migrations in non-interactive mode.
     */
    public $interactive = true;

    public function options($actionID)
    {
        return ['interactive'];
    }

    public function optionAliases()
    {
        return ['i' => 'interactive'];
    }

    /**
     * Applies all new migrations to all project databases.
     * @return int Exit code
     */
    public function actionAll()
    {
        $projects = Project::find()->all();
        if (empty($projects)) {
            $this->stdout("No projects found.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $dbManager = new ActiveDatabaseContext();
        $defaultDbConnection = Yii::$app->db;
        $dbNameBuilder = new \ReflectionMethod(ActiveDatabaseContext::class, 'buildCustomProjectDatabaseName');
        $dbNameBuilder->setAccessible(true);

        $databasesToMigrate = [];
        foreach ($projects as $project) {
            $dbName = $dbNameBuilder->invoke($dbManager, $project->name);
            if ($dbManager->databaseExistsOnCurrentServer($dbName)) {
                $databasesToMigrate[$dbName] = ['project' => $project, 'dbName' => $dbName];
            }
        }

        if (empty($databasesToMigrate)) {
            $this->stdout("No existing project databases found to migrate.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("The following project databases will be migrated:\n", Console::FG_YELLOW);
        foreach ($databasesToMigrate as $info) {
            $this->stdout("- {$info['dbName']} (Project: {$info['project']->name})\n");
        }
        $this->stdout("\n");

        if ($this->interactive) {
            if (!$this->confirm("Apply migrations to all " . count($databasesToMigrate) . " databases?")) {
                return ExitCode::OK;
            }
        }

        $originalDb = Yii::$app->getDb();

        foreach ($databasesToMigrate as $info) {
            $dbName = $info['dbName'];
            $project = $info['project'];
            $this->stdout("\n--- Migrating database: {$dbName} (Project: {$project->name}) ---\n", Console::FG_CYAN);

            try {
                $projectDb = new Connection([
                    'dsn' => $this->replaceDatabaseInDsn($originalDb->dsn, $dbName),
                    'username' => $originalDb->username,
                    'password' => $originalDb->password,
                    'charset' => $originalDb->charset,
                ]);

                $this->applyDbTableStructureFixes($projectDb, $dbName);

                $this->runYiiMigrationsForProject($projectDb, $project);

                $this->stdout("Successfully processed database: {$dbName}\n", Console::FG_GREEN);
            } catch (\Throwable $e) {
                $this->stderr("Error processing database {$dbName}: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        $this->stdout("\nAll project databases have been processed.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function applyDbTableStructureFixes(Connection $projectDb, string $dbName): void
    {
        $this->stdout("Checking for missing foreign key columns...\n", Console::FG_YELLOW);
        $tableName = 'db_table_columns';
        $schema = $projectDb->schema->getTableSchema($tableName, true);
        $columnsAdded = 0;

        if ($schema !== null) {
            $columnsToCheck = [
                'is_foreign_key' => "ADD COLUMN `is_foreign_key` TINYINT(1) DEFAULT 0",
                'referenced_table_name' => "ADD COLUMN `referenced_table_name` VARCHAR(255) NULL",
                'referenced_column_name' => "ADD COLUMN `referenced_column_name` VARCHAR(255) NULL",
                'on_delete_action' => "ADD COLUMN `on_delete_action` VARCHAR(50) NULL",
                'on_update_action' => "ADD COLUMN `on_update_action` VARCHAR(50) NULL",
                'related_display_column' => "ADD COLUMN `related_display_column` VARCHAR(100) NULL",
            ];

            $alterStatements = [];
            foreach ($columnsToCheck as $colName => $addSql) {
                if (!isset($schema->columns[$colName])) {
                    $alterStatements[] = $addSql;
                    $columnsAdded++;
                }
            }

            if (!empty($alterStatements)) {
                $sql = "ALTER TABLE `{$tableName}` " . implode(', ', $alterStatements);
                $projectDb->createCommand($sql)->execute();
                $this->stdout("Added {$columnsAdded} missing columns to {$tableName}.\n", Console::FG_GREEN);
            } else {
                $this->stdout("All columns already exist. No changes needed.\n", Console::FG_CYAN);
            }
        } else {
            $this->stdout("Table {$tableName} not found. Creating the full table builder structure...\n", Console::FG_YELLOW);

            $createTablesSql = "CREATE TABLE IF NOT EXISTS `db_tables` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `display_name` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $projectDb->createCommand($createTablesSql)->execute();

            $createColumnsSql = "CREATE TABLE IF NOT EXISTS `db_table_columns` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `db_table_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `is_nullable` TINYINT(1) DEFAULT 0,
                `is_primary_key` TINYINT(1) DEFAULT 0,
                `is_foreign_key` TINYINT(1) DEFAULT 0,
                `referenced_table_name` VARCHAR(255) NULL,
                `referenced_column_name` VARCHAR(255) NULL,
                `on_delete_action` VARCHAR(50) NULL,
                `on_update_action` VARCHAR(50) NULL,
                `related_display_column` VARCHAR(100) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`db_table_id`) REFERENCES `db_tables`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $projectDb->createCommand($createColumnsSql)->execute();

            $this->stdout("Successfully created full table builder structure in {$dbName}.\n", Console::FG_GREEN);
        }
    }

    /**
     * Runs Yii migrations for a specific project database.
     * 
     * Two-phase approach:
     * 1. Sync: Detect already-applied migrations (table/column already exists)
     *    and mark them in the tracking table without re-running.
     * 2. Apply: Only run migrations that are truly new.
     */
    private function runYiiMigrationsForProject(Connection $projectDb, Project $project): void
    {
        $migrationTable = 'migration';

        $this->stdout("Checking standard Yii migrations...\n", Console::FG_YELLOW);

        // Scan migration files
        $migrationPaths = [Yii::getAlias('@app/migrations')];
        $files = [];
        foreach ($migrationPaths as $path) {
            if (!is_dir($path)) continue;
            $handle = opendir($path);
            if ($handle === false) continue;
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') continue;
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($fullPath)) {
                    $files[$matches[1]] = $matches[1];
                }
            }
            closedir($handle);
        }
        ksort($files);
        $available = array_values($files);

        if (empty($available)) {
            $this->stdout("No migration files found in @app/migrations.\n", Console::FG_CYAN);
            return;
        }

        // Ensure migration tracking table exists
        $tableSchema = $projectDb->schema->getTableSchema($migrationTable, true);
        if ($tableSchema === null) {
            $createTableSql = "CREATE TABLE `{$migrationTable}` (
                `version` VARCHAR(180) NOT NULL PRIMARY KEY,
                `apply_time` INT(11) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $projectDb->createCommand($createTableSql)->execute();
            $this->stdout("Created migration tracking table: {$migrationTable}\n", Console::FG_GREEN);
            $applied = [];
        } else {
            $appliedRows = $projectDb->createCommand("SELECT version FROM `{$migrationTable}`")->queryAll();
            $applied = array_map(function ($row) { return $row['version']; }, $appliedRows);
        }

        $pending = array_diff($available, $applied);

        if (empty($pending)) {
            $this->stdout("All Yii migrations are already applied. ({$migrationTable})\n", Console::FG_CYAN);
            $this->cleanupDuplicateSeedData($projectDb);
            return;
        }

        // Phase 1: Sync — detect migrations whose effects are already in the schema
        $pending = $this->syncAlreadyAppliedMigrations($projectDb, $migrationTable, $pending);

        if (empty($pending)) {
            $this->stdout("All migrations were already present in schema. Synced to tracking table.\n", Console::FG_CYAN);
            $this->cleanupDuplicateSeedData($projectDb);
            return;
        }

        // Phase 2: Apply truly pending migrations
        $this->stdout("Found " . count($pending) . " truly pending Yii migration(s) to apply.\n", Console::FG_YELLOW);
        foreach ($pending as $migrationName) {
            $this->stdout("  - {$migrationName}\n");
        }

        foreach ($pending as $migrationName) {
            $className = $migrationName;
            $filePath = null;
            foreach ($migrationPaths as $path) {
                $candidate = $path . DIRECTORY_SEPARATOR . $migrationName . '.php';
                if (is_file($candidate)) {
                    $filePath = $candidate;
                    break;
                }
            }
            if ($filePath === null) continue;

            $this->stdout("Applying {$migrationName}... ", Console::FG_YELLOW);
            try {
                require_once $filePath;
                if (!class_exists($className)) {
                    $this->stdout("FAILED (class {$className} not found)\n", Console::FG_RED);
                    continue;
                }

                $migration = new $className();
                $migration->db = $projectDb;

                $result = $migration->safeUp();

                $projectDb->createCommand()->insert($migrationTable, [
                    'version' => $migrationName,
                    'apply_time' => time(),
                ])->execute();

                $this->stdout("OK\n", Console::FG_GREEN);
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
                
                // "Already exists" errors are safe — the schema already has this change
                if ($this->isAcceptableReapplyError($errorMsg)) {
                    $projectDb->createCommand()->insert($migrationTable, [
                        'version' => $migrationName,
                        'apply_time' => time(),
                    ])->execute();
                    $this->stdout("SKIPPED (already exists in schema)\n", Console::FG_CYAN);
                } else {
                    $this->stdout("FAILED\n", Console::FG_RED);
                    $this->stderr("  Error: " . $errorMsg . "\n", Console::FG_RED);
                }
            }
        }

        $this->cleanupDuplicateSeedData($projectDb);

        $this->stdout("Yii migrations complete for this project.\n", Console::FG_GREEN);
    }

    /**
     * Cleans up duplicate menu entries caused by seed migrations that were
     * accidentally re-run. Only removes rows with known seed menu_key values
     * when duplicates exist, keeping the original (lowest ID) row.
     */
    private function cleanupDuplicateSeedData(Connection $projectDb): void
    {
        $knownSeedKeys = ['dashboard', 'table-builder', 'forms', 'profile', 'master-data'];
        $foundAny = false;

        foreach ($knownSeedKeys as $menuKey) {
            $schema = $projectDb->schema->getTableSchema('master_menu', true);
            if ($schema === null) return;

            $rows = $projectDb->createCommand(
                "SELECT id FROM master_menu WHERE menu_key = :key ORDER BY id ASC",
                [':key' => $menuKey]
            )->queryAll();

            if (count($rows) <= 1) continue;
            if (!$foundAny) {
                $foundAny = true;
                $this->stdout("\nFound duplicate seed menu entries.\n", Console::FG_YELLOW);
            }

            $deleteIds = [];
            for ($i = 1; $i < count($rows); $i++) {
                $deleteIds[] = (int)$rows[$i]['id'];
            }
            $idsStr = implode(',', $deleteIds);
            $this->stdout("  menu_key='{$menuKey}': keeping ID={$rows[0]['id']}, deleting IDs ({$idsStr})\n", Console::FG_YELLOW);
        }

        if (!$foundAny) return;

        if ($this->interactive) {
            if (!$this->confirm("\nDelete these duplicate menu rows? Only rows from seed defaults, custom menus are NOT affected.")) {
                $this->stdout("Skipped cleanup.\n", Console::FG_CYAN);
                return;
            }
        }

        foreach ($knownSeedKeys as $menuKey) {
            $rows = $projectDb->createCommand(
                "SELECT id FROM master_menu WHERE menu_key = :key ORDER BY id ASC",
                [':key' => $menuKey]
            )->queryAll();
            if (count($rows) <= 1) continue;

            $deleteIds = [];
            for ($i = 1; $i < count($rows); $i++) {
                $deleteIds[] = (int)$rows[$i]['id'];
            }
            if (!empty($deleteIds)) {
                $idsStr = implode(',', $deleteIds);
                $deleted = $projectDb->createCommand(
                    "DELETE FROM master_menu WHERE id IN ({$idsStr})"
                )->execute();
                $this->stdout("  Cleaned {$deleted} duplicate(s) for menu_key='{$menuKey}'\n", Console::FG_CYAN);
            }
        }
    }

    /**
     * Syncs the migration tracking table by detecting migrations whose
     * effects are already present in the database schema.
     * 
     * This prevents re-running migrations that modify existing structures
     * or seed data that has already been inserted.
     */
    private function syncAlreadyAppliedMigrations(Connection $projectDb, string $migrationTable, array $pending): array
    {
        $synced = [];
        $remaining = [];

        foreach ($pending as $migrationName) {
            if ($this->isMigrationAlreadyApplied($projectDb, $migrationName)) {
                try {
                    $projectDb->createCommand()->insert($migrationTable, [
                        'version' => $migrationName,
                        'apply_time' => time(),
                    ])->execute();
                    $synced[] = $migrationName;
                    $this->stdout("  ✓ Synced (already applied): {$migrationName}\n", Console::FG_CYAN);
                } catch (\Throwable $e) {
                    // Race condition: another process may have inserted it
                    $remaining[] = $migrationName;
                }
            } else {
                $remaining[] = $migrationName;
            }
        }

        if (!empty($synced)) {
            $this->stdout("Synced " . count($synced) . " already-applied migrations to tracking table.\n", Console::FG_GREEN);
        }

        return $remaining;
    }

    /**
     * Determines if a migration has already been applied to the database
     * by examining the actual schema (tables, columns, data).
     */
    private function isMigrationAlreadyApplied(Connection $projectDb, string $migrationName): bool
    {
        // Detect seed data migrations by name pattern
        if (preg_match('/seed|insert|sample|initial|seed_default/i', $migrationName)) {
            return $this->isSeedMigrationApplied($projectDb, $migrationName);
        }

        // Detect "create xxx table" pattern
        if (preg_match('/create_(\w+)_table/', $migrationName, $m)) {
            $tableName = $this->normalizeTableName($m[1]);
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            return $schema !== null;
        }

        // Detect "add xxx to yyy" pattern (ADD COLUMN)
        if (preg_match('/add_(\w+)_to_(\w+)/', $migrationName, $m)) {
            $columnName = $m[1];
            $tableName = $this->normalizeTableName($m[2]);
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            return $schema !== null && $schema->getColumn($columnName) !== null;
        }

        // Detect "ensure xxx exist" pattern
        if (preg_match('/ensure_(\w+)_exist/', $migrationName, $m)) {
            $tableName = $this->normalizeTableName($m[1]);
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            return $schema !== null;
        }

        // Detect "fix xxx columns" or "repair xxx"
        if (preg_match('/fix_(\w+)_columns/', $migrationName, $m)) {
            $tableName = $this->normalizeTableName($m[1]);
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            return $schema !== null;
        }

        // Detect "increase xxx columns to longtext" 
        if (preg_match('/increase_(\w+)_columns_to_(\w+)/', $migrationName, $m)) {
            $tableName = $this->normalizeTableName($m[1]);
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            return $schema !== null;
        }

        // Detect "add index" pattern
        if (preg_match('/add_(\w+)_index/', $migrationName) || preg_match('/create_(\w+)_index/', $migrationName)) {
            // Index detection is complex; assume not applied and let safeUp handle it
            return false;
        }

        // Detect "rename" pattern
        if (preg_match('/rename_(\w+)_to_(\w+)/', $migrationName)) {
            return false; // Can't easily detect renames
        }

        // For "update" or "repair" patterns, check if the target table exists
        if (preg_match('/update_(\w+)|repair_(\w+)/', $migrationName, $m)) {
            $tableName = $this->normalizeTableName($m[1] ?? $m[2]);
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            if ($schema !== null) {
                // Table exists; check if this migration is a data migration
                // by looking for specific known columns
                if (stripos($migrationName, 'menu') !== false && $schema->getColumn('type') !== null) {
                    return true;
                }
                if (stripos($migrationName, 'page') !== false && $schema->getColumn('page_type') !== null) {
                    return true;
                }
            }
            return false;
        }

        // For "add_foreign_key" or "drop_foreign_key" — let it execute
        if (stripos($migrationName, 'foreign_key') !== false) {
            return false;
        }

        // Default: can't determine, will try to run it
        return false;
    }

    /**
     * Checks if a seed data migration has already been applied by
     * verifying if the target tables already contain data.
     */
    private function isSeedMigrationApplied(Connection $projectDb, string $migrationName): bool
    {
        $targetTables = $this->getSeedTargetTables($migrationName);

        if (empty($targetTables)) {
            return false;
        }

        foreach ($targetTables as $tableName) {
            $schema = $projectDb->schema->getTableSchema($tableName, true);
            if ($schema === null) {
                continue;
            }

            try {
                $count = (int) $projectDb->createCommand("SELECT COUNT(*) FROM `{$tableName}`")->queryScalar();
                if ($count > 0) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Table might not exist yet
                continue;
            }
        }

        return false;
    }

    /**
     * Maps migration names to the tables they seed with initial data.
     */
    private function getSeedTargetTables(string $migrationName): array
    {
        $name = strtolower($migrationName);

        if (stripos($name, 'menu') !== false) {
            return ['master_menu', 'sidebar_menu'];
        }
        if (stripos($name, 'page') !== false) {
            return ['master_page'];
        }
        if (stripos($name, 'notification') !== false) {
            return ['notifications'];
        }
        if (stripos($name, 'kategori') !== false) {
            return ['kategori'];
        }
        if (stripos($name, 'sidebar') !== false) {
            return ['sidebar_menu'];
        }

        return [];
    }

    /**
     * Normalizes table names from migration name segments.
     * Converts singular to plural and adds underscores for compound names.
     */
    private function normalizeTableName(string $name): string
    {
        $name = str_replace('_', '', $name);
        
        $map = [
            'masterpage' => 'master_page',
            'masterform' => 'master_form',
            'mastermenu' => 'master_menu',
            'masterpageform' => 'master_page_form',
            'masterformactivitylog' => 'master_form_activity_log',
            'publishedforms' => 'published_forms',
            'dbtablecolumns' => 'db_table_columns',
            'dbtables' => 'db_tables',
            'formsubmissions' => 'form_submissions',
            'produkkategori' => 'produk_kategori',
            'workspacesettings' => 'workspace_settings',
            'mastermenus' => 'master_menu',
            'masterpages' => 'master_page',
            'masterforms' => 'master_form',
            'notifications' => 'notifications',
        ];
        
        return $map[strtolower($name)] ?? $name;
    }

    /**
     * Checks if a migration error is caused by already-existing schema elements,
     * which means the migration's effect is already present and we can safely skip it.
     */
    private function isAcceptableReapplyError(string $errorMessage): bool
    {
        $patterns = [
            'Base table or view already exists',
            'Table already exists',
            'Column already exists',
            'Duplicate column name',
            'Duplicate entry',
            '42S01', // MySQL: Table already exists
            '42S21', // MySQL: Column already exists
        ];

        foreach ($patterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function replaceDatabaseInDsn(string $dsn, string $databaseName): string
    {
        if (preg_match('/dbname=([^;]+)/i', $dsn)) {
            return (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $databaseName, $dsn, 1);
        }
        return rtrim($dsn, ';') . ';dbname=' . $databaseName;
    }
}
