<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\db\Expression;
use yii\db\Query;
use yii\data\Pagination;
use app\models\Project;
use app\models\Form;
use app\models\FormSubmission;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;

class ProjectController extends Controller
{
    /** @var array<string, bool> */
    private static $databaseExistsCache = [];

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
            $this->databaseExistsCached($databaseContext, $legacyDatabaseName)
            && !$this->databaseExistsCached($databaseContext, $customDatabaseName)
        ) {
            return $legacyDatabaseName;
        }

        return $customDatabaseName;
    }

    private function databaseExistsCached(ActiveDatabaseContext $databaseContext, string $databaseName): bool
    {
        if ($databaseName === '') {
            return false;
        }

        if (!array_key_exists($databaseName, self::$databaseExistsCache)) {
            self::$databaseExistsCache[$databaseName] = $databaseContext->databaseExistsOnCurrentServer($databaseName);
        }

        return self::$databaseExistsCache[$databaseName];
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
                    $dbHostHint = (new ActiveDatabaseContext())->mysqlHostFromConnection();
                    $backupHint = Yii::$app->has('dbBackup')
                        ? ' Nama database yang sama juga dicoba dibuat di server backup (Railway/remote). Sinkronisasi perubahan data berjalan otomatis dari master ke backup.'
                        : '';
                    $serverHint = $dbHostHint !== ''
                        ? "Database baru '{$databaseName}' dibuat di server MySQL {$dbHostHint}. Di phpMyAdmin, pastikan Anda terhubung ke host yang sama agar database tampil di sidebar kiri (refresh daftar database bila perlu)."
                        : "Database baru '{$databaseName}' sudah dibuat. Di phpMyAdmin, pastikan koneksi ke server MySQL yang sama dengan aplikasi ini, lalu refresh daftar database.";
                    Yii::$app->session->setFlash('success', "Project berhasil dibuat dan dipilih. {$serverHint}{$backupHint}");

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
        
        $totalCount = $query->count();
        $pagination = new \yii\data\Pagination([
            'totalCount' => $totalCount,
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
            'projectCount' => $totalCount,
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

        $dbHostHint = (new ActiveDatabaseContext())->mysqlHostFromConnection();
        $hostSuffix = $dbHostHint !== '' ? " (server: {$dbHostHint})" : '';
        Yii::$app->session->setFlash('success', "{$project->name} aktif. Database project: {$databaseName}{$hostSuffix}.");
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

    public function actionProfile()
    {
        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Project context not available.');
            return $this->redirect(['site/profile']);
        }

        $context = new ActiveProjectContext();
        $activeProjectId = $context->getActiveProjectId();
        
        if ($activeProjectId === null) {
            Yii::$app->session->setFlash('warning', 'No active project selected.');
            return $this->redirect(['project/index']);
        }

        $project = Project::findOne(['id' => $activeProjectId, 'user_id' => Yii::$app->user->id]);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        $user = Yii::$app->user->identity;
        $totalForms = Form::find()->where(['user_id' => $user->id])->count();
        $totalSubmissions = FormSubmission::find()
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->where(['forms.user_id' => $user->id])
            ->count();

        return $this->render('profile', [
            'user' => $user,
            'project' => $project,
            'totalForms' => $totalForms,
            'totalSubmissions' => $totalSubmissions,
        ]);
    }

    public function actionFirebaseUsers()
    {
        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Project context belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        $context = new ActiveProjectContext();
        $activeProjectId = $context->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->setFlash('warning', 'Pilih project aktif terlebih dahulu untuk melihat user login Firebase.');
            return $this->redirect(['project/index']);
        }

        $project = Project::findOne(['id' => $activeProjectId, 'user_id' => Yii::$app->user->id]);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        $baseQuery = (new Query())
            ->from(['fs' => FormSubmission::tableName()])
            ->innerJoin(['f' => Form::tableName()], 'f.id = fs.form_id')
            ->where(['f.user_id' => Yii::$app->user->id])
            ->andWhere(['f.project_id' => $activeProjectId])
            ->andWhere(['not', ['fs.firebase_uid' => null]])
            ->andWhere(['<>', 'fs.firebase_uid', '']);

        $groupedQuery = (clone $baseQuery)
            ->select([
                'firebase_uid' => 'fs.firebase_uid',
                'firebase_email' => new Expression("MAX(NULLIF(TRIM(fs.firebase_email), ''))"),
                'firebase_name' => new Expression("MAX(NULLIF(TRIM(fs.firebase_name), ''))"),
                'submission_count' => new Expression('COUNT(*)'),
                'form_count' => new Expression('COUNT(DISTINCT fs.form_id)'),
                'last_login_at' => new Expression('MAX(fs.created_at)'),
            ])
            ->groupBy(['fs.firebase_uid']);

        $totalCount = (int)(new Query())
            ->from(['firebase_users' => $groupedQuery])
            ->count('*');

        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 20,
        ]);

        $firebaseUsers = (clone $groupedQuery)
            ->orderBy(['last_login_at' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('firebase-users', [
            'project' => $project,
            'firebaseUsers' => $firebaseUsers,
            'pagination' => $pagination,
            'totalFirebaseUsers' => $totalCount,
        ]);
    }
}
