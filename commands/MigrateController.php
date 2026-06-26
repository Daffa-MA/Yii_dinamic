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
                $databasesToMigrate[$dbName] = $project->name;
            }
        }

        if (empty($databasesToMigrate)) {
            $this->stdout("No existing project databases found to migrate.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("The following project databases will be migrated:\n", Console::FG_YELLOW);
        foreach ($databasesToMigrate as $dbName => $projectName) {
            $this->stdout("- {$dbName} (Project: {$projectName})\n");
        }
        $this->stdout("\n");

        if ($this->interactive) {
            if (!$this->confirm("Apply migrations to all " . count($databasesToMigrate) . " databases?")) {
                return ExitCode::OK;
            }
        }

        $originalDb = Yii::$app->getDb();

        foreach ($databasesToMigrate as $dbName => $projectName) {
            $this->stdout("\n--- Migrating database: {$dbName} (Project: {$projectName}) ---\n", Console::FG_CYAN);

            try {
                // Create a new DB connection for the project database
                $projectDb = new Connection([
                    'dsn' => $this->replaceDatabaseInDsn($originalDb->dsn, $dbName),
                    'username' => $originalDb->username,
                    'password' => $originalDb->password,
                    'charset' => $originalDb->charset,
                ]);

                // Execute the fix directly here, no external files
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
                    $this->stdout("Table {$tableName} not found in {$dbName}, skipping.\n", Console::FG_YELLOW);
                }

                $this->stdout("Successfully processed database: {$dbName}\n", Console::FG_GREEN);
            } catch (\Throwable $e) {
                $this->stderr("Error processing database {$dbName}: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        $this->stdout("\nAll project databases have been processed.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function replaceDatabaseInDsn(string $dsn, string $databaseName): string
    {
        if (preg_match('/dbname=([^;]+)/i', $dsn)) {
            return (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $databaseName, $dsn, 1);
        }
        return rtrim($dsn, ';') . ';dbname=' . $databaseName;
    }
}
