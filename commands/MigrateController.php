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
            return;
        }

        $this->stdout("Found " . count($pending) . " pending Yii migration(s) to apply.\n", Console::FG_YELLOW);
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
                $this->stdout("FAILED\n", Console::FG_RED);
                $this->stderr("  Error: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        $this->stdout("Yii migrations complete for this project.\n", Console::FG_GREEN);
    }

    private function replaceDatabaseInDsn(string $dsn, string $databaseName): string
    {
        if (preg_match('/dbname=([^;]+)/i', $dsn)) {
            return (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $databaseName, $dsn, 1);
        }
        return rtrim($dsn, ';') . ';dbname=' . $databaseName;
    }
}
