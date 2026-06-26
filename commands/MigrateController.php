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

                // Switch the current app DB to the project one
                Yii::$app->set('db', $projectDb);

                // Instantiate ONLY our specific migration file and run it
                $migration = new \app\migrations\m260626_100000_ensure_foreign_key_metadata_columns_exist();
                $migration->db = $projectDb;

                if ($migration->safeUp() === false) {
                    $this->stdout("Migration for {$dbName} completed, no changes were needed (columns already exist).\n", Console::FG_YELLOW);
                } else {
                    $this->stdout("Successfully applied our fix migration to {$dbName}\n", Console::FG_GREEN);
                }

                // Restore the original database connection
                Yii::$app->set('db', $originalDb);
            } catch (\Throwable $e) {
                Yii::$app->set('db', $originalDb); // Always restore the connection
                $this->stderr("Error migrating database {$dbName}: " . $e->getMessage() . "\n", Console::FG_RED);
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
