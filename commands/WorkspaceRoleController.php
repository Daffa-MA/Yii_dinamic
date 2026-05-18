<?php

namespace app\commands;

use app\models\Project;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Query;

class WorkspaceRoleController extends Controller
{
    public $projectId;
    public $dryRun = true;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['projectId', 'dryRun']);
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'p' => 'projectId',
        ]);
    }

    public function actionCleanupSuperadmin(): int
    {
        Project::ensureProjectStructure();

        $query = Project::find()->orderBy(['id' => SORT_ASC]);
        if ($this->projectId !== null && (int)$this->projectId > 0) {
            $query->andWhere(['id' => (int)$this->projectId]);
        }

        $projects = $query->all();
        if (empty($projects)) {
            $this->stdout("No projects found.\n");
            return ExitCode::OK;
        }

        $dryRun = filter_var($this->dryRun, FILTER_VALIDATE_BOOLEAN);
        foreach ($projects as $project) {
            $databaseName = $this->resolveProjectDatabaseName($project);
            if ($databaseName === '') {
                $this->stdout("Project #{$project->id}: database not found, skipped.\n");
                continue;
            }

            $connection = $this->createProjectConnection($databaseName);
            $usersSchema = $connection->getTableSchema('users', true);
            if ($usersSchema === null || !isset($usersSchema->columns['role'])) {
                $this->stdout("Project #{$project->id} ({$databaseName}): users.role missing, skipped.\n");
                continue;
            }

            $rows = (new Query())
                ->from('users')
                ->where(['role' => ['superadmin', 'super_admin']])
                ->all($connection);

            if (empty($rows)) {
                $this->stdout("Project #{$project->id} ({$databaseName}): clean.\n");
                continue;
            }

            $this->stdout("Project #{$project->id} ({$databaseName}): found " . count($rows) . " workspace superadmin user(s).\n");
            if ($dryRun) {
                continue;
            }

            $adminExists = (new Query())->from('users')->where(['username' => 'admin'])->exists($connection);
            foreach ($rows as $row) {
                $updates = ['role' => 'admin'];
                if (!$adminExists && strtolower((string)($row['username'] ?? '')) === 'superadmin') {
                    $updates['username'] = 'admin';
                    $adminExists = true;
                }

                $connection->createCommand()->update('users', $updates, ['id' => (int)$row['id']])->execute();
            }

            $this->stdout("Project #{$project->id} ({$databaseName}): converted to admin.\n");
        }

        if ($dryRun) {
            $this->stdout("Dry run only. Re-run with --dryRun=0 to apply changes.\n");
        }

        return ExitCode::OK;
    }

    private function resolveProjectDatabaseName(Project $project): string
    {
        $legacy = sprintf('proj_u%d_p%d', (int)$project->user_id, (int)$project->id);
        $custom = $this->buildCustomProjectDatabaseName((string)$project->name);

        $legacyExists = $this->databaseExists($legacy);
        $customExists = $this->databaseExists($custom);

        if ($legacyExists && !$customExists) {
            return $legacy;
        }

        if ($customExists) {
            return $custom;
        }

        return $legacyExists ? $legacy : '';
    }

    private function buildCustomProjectDatabaseName(string $projectName): string
    {
        $normalized = strtolower(trim($projectName));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            $normalized = 'project';
        }

        if (preg_match('/^[0-9]/', $normalized) === 1) {
            $normalized = 'project_' . $normalized;
        }

        return strlen($normalized) > 64 ? rtrim(substr($normalized, 0, 64), '_') : $normalized;
    }

    private function databaseExists(string $databaseName): bool
    {
        if ($databaseName === '' || preg_match('/^[a-zA-Z0-9_]+$/', $databaseName) !== 1) {
            return false;
        }

        return (new Query())
            ->from('INFORMATION_SCHEMA.SCHEMATA')
            ->where(['SCHEMA_NAME' => $databaseName])
            ->exists(Yii::$app->db);
    }

    private function createProjectConnection(string $databaseName): Connection
    {
        $base = Yii::$app->db;
        $dsn = preg_match('/dbname=([^;]+)/i', $base->dsn)
            ? (string)preg_replace('/dbname=([^;]+)/i', 'dbname=' . $databaseName, $base->dsn, 1)
            : rtrim($base->dsn, ';') . ';dbname=' . $databaseName;

        $connection = Yii::createObject([
            'class' => Connection::class,
            'dsn' => $dsn,
            'username' => $base->username,
            'password' => $base->password,
            'charset' => $base->charset,
        ]);
        $connection->open();

        return $connection;
    }
}
