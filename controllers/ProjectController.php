<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Project;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;

class ProjectController extends Controller
{
    private function buildLegacyProjectDatabaseName(Project $project): string
    {
        return sprintf('proj_u%d_p%d', (int)$project->user_id, (int)$project->id);
    }

    private function sanitizeDatabaseNameBase(string $name): string
    {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            $normalized = 'project';
        }

        if (preg_match('/^[0-9]/', $normalized) === 1) {
            $normalized = 'project_' . $normalized;
        }

        return $normalized;
    }

    private function buildCustomProjectDatabaseName(Project $project): string
    {
        $base = $this->sanitizeDatabaseNameBase((string)$project->name);
        $maxDatabaseLength = 64;

        if (strlen($base) > $maxDatabaseLength) {
            $base = rtrim(substr($base, 0, $maxDatabaseLength), '_');
        }

        if ($base === '') {
            $base = 'project';
        }

        return $base;
    }

    public function resolveProjectDatabaseName(Project $project): string
    {
        $databaseContext = new ActiveDatabaseContext();
        $legacyDatabaseName = $this->buildLegacyProjectDatabaseName($project);
        $customDatabaseName = $this->buildCustomProjectDatabaseName($project);

        // Backward compatibility: keep using old generated DB name if it already exists.
        if (
            $databaseContext->databaseExistsOnCurrentServer($legacyDatabaseName)
            && !$databaseContext->databaseExistsOnCurrentServer($customDatabaseName)
        ) {
            return $legacyDatabaseName;
        }

        return $customDatabaseName;
    }

    private function ensureProjectDatabase(Project $project, bool $mustBeNew = false): string
    {
        $databaseName = $this->resolveProjectDatabaseName($project);
        $databaseContext = new ActiveDatabaseContext();

        if ($mustBeNew && $databaseContext->databaseExistsOnCurrentServer($databaseName)) {
            throw new \RuntimeException("Nama database '{$databaseName}' sudah ada. Gunakan nama project lain yang unik.");
        }

        $databaseContext->createDatabase($databaseName);
        return $databaseName;
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Workspace project belum tersedia di database saat ini. Jalankan migrasi terbaru untuk mengaktifkan fitur project.');
            return $this->redirect(['site/dashboard']);
        }

        $context = new ActiveProjectContext();
        $model = new Project();
        $model->user_id = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->user_id = Yii::$app->user->id;

                if ($model->save()) {
                    try {
                        $databaseName = $this->ensureProjectDatabase($model, true);
                    } catch (\Throwable $e) {
                        $projectName = $model->name;
                        $model->delete();
                        Yii::$app->session->setFlash('error', "Project '{$projectName}' gagal dibuat karena database baru tidak bisa dibuat: {$e->getMessage()}");
                        return $this->redirect(['project/index']);
                    }

                    $context->setActiveProject((int)$model->id);
                    Yii::$app->session->setFlash('success', "Project berhasil dibuat dan dipilih. Database baru '{$databaseName}' juga sudah dibuat.");

                    return $this->redirectAfterProjectSelected();
                }

                Yii::$app->session->setFlash('error', implode(', ', $model->getFirstErrors()) ?: 'Gagal membuat project.');
            } else {
                Yii::$app->session->setFlash('error', 'Gagal memproses data form.');
            }
        }

        // Pagination setup
        $pageSize = 6;
        $query = Project::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
        
        $pagination = new \yii\data\Pagination([
            'totalCount' => $query->count(),
            'pageSize' => $pageSize,
        ]);

        $projects = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $projectDatabases = [];
        foreach ($projects as $project) {
            $projectDatabases[(int)$project->id] = $this->resolveProjectDatabaseName($project);
        }

        return $this->render('index', [
            'model' => $model,
            'projects' => $projects,
            'activeProject' => $context->getActiveProject(),
            'activeProjectId' => $context->getActiveProjectId(),
            'projectCount' => (new \yii\data\Pagination(['totalCount' => Project::find()->where(['user_id' => Yii::$app->user->id])->count(), 'pageSize' => $pageSize]))->totalCount,
            'projectDatabases' => $projectDatabases,
            'pagination' => $pagination,
        ]);
    }

    public function actionSelect($id)
    {
        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Workspace project belum tersedia di database saat ini.');
            return $this->redirect(['site/dashboard']);
        }

        $project = Project::findOne(['id' => (int)$id, 'user_id' => Yii::$app->user->id]);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        try {
            $databaseName = $this->ensureProjectDatabase($project);
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', "Project ditemukan, tapi database project gagal disiapkan: {$e->getMessage()}");
            return $this->redirect(['project/index']);
        }

        $context = new ActiveProjectContext();
        $context->setActiveProject((int)$project->id);

        Yii::$app->session->setFlash('success', "{$project->name} aktif. Database project: {$databaseName}.");
        return $this->redirectAfterProjectSelected();
    }

    private function redirectAfterProjectSelected()
    {
        $session = Yii::$app->session;
        $returnUrl = (string)$session->get('project_required_return_url', '');
        $session->remove('project_required_return_url');

        if ($returnUrl !== '') {
            return $this->redirect($returnUrl);
        }

        return $this->redirect(['site/dashboard']);
    }
}
