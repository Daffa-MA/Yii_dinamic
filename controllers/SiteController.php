<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\Form;
use app\models\Project;
use app\models\DbTable;
use app\models\FormSubmission;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;

class SiteController extends Controller
{
    private function redirectAfterAuthentication()
    {
        // Always redirect to projects page first after login
        return $this->redirect(['project/index']);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'only' => ['logout', 'dashboard', 'profile', 'change-password'],
                'rules' => [
                    [
                        'actions' => ['logout', 'dashboard', 'profile', 'change-password'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Homepage
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirectAfterAuthentication();
        }
        return $this->redirect(['login']);
    }

    /**
     * Dashboard
     */
    public function actionDashboard()
    {
        $projectContext = new ActiveProjectContext();
        $projectContextEnabled = ProjectSchema::supportsProjectContext();
        $activeProjectId = $projectContextEnabled ? $projectContext->getActiveProjectId() : null;
        if ($projectContextEnabled && $activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola table/form.');
            return $this->redirect(['project/index']);
        }

        $databaseContext = (new ActiveDatabaseContext())->resolveAndApply();
        if (!empty($databaseContext['switchError'])) {
            Yii::$app->session->setFlash('warning', $databaseContext['switchError']);
        }

        $userId = Yii::$app->user->id;
        $activeProject = null;
        $projectDatabaseName = null;
        if ($projectContextEnabled && $activeProjectId !== null) {
            $activeProject = Project::findOne(['id' => $activeProjectId, 'user_id' => $userId]);
            // Get the project's database name
            if ($activeProject !== null) {
                $projectController = new ProjectController('project', Yii::$app);
                $projectDatabaseName = $projectController->resolveProjectDatabaseName($activeProject);
            }
        }
        $schemaColumn = Form::getSchemaStorageColumn();
        $cacheSuffix = '-' . ($databaseContext['activeDatabase'] ?? 'default');
        if ($projectContextEnabled && $activeProjectId !== null) {
            $cacheSuffix .= '-project-' . $activeProjectId;
        }

        $dashboardStats = Yii::$app->cache->getOrSet('dashboard-stats-' . $userId . $cacheSuffix, function () use ($userId, $activeProjectId, $projectContextEnabled) {
            $formFilter = ['user_id' => $userId];
            $submissionFormFilter = ['forms.user_id' => $userId];
            if ($projectContextEnabled && $activeProjectId !== null) {
                $formFilter['project_id'] = $activeProjectId;
                $submissionFormFilter['forms.project_id'] = $activeProjectId;
            }

            $totalForms = Form::find()->where($formFilter)->count();
            $totalSubmissions = FormSubmission::find()
                ->innerJoin('forms', 'forms.id = form_submissions.form_id')
                ->where($submissionFormFilter)
                ->count();
            $todaySubmissions = FormSubmission::find()
                ->innerJoin('forms', 'forms.id = form_submissions.form_id')
                ->where($submissionFormFilter)
                ->andWhere(['>=', 'form_submissions.created_at', date('Y-m-d 00:00:00')])
                ->count();

            return [
                'totalForms' => $totalForms,
                'totalSubmissions' => $totalSubmissions,
                'todaySubmissions' => $todaySubmissions,
            ];
        }, 30);

        $totalForms = $dashboardStats['totalForms'];
        $totalSubmissions = $dashboardStats['totalSubmissions'];
        $todaySubmissions = $dashboardStats['todaySubmissions'];

        $recentFormsQuery = Form::find()
            ->select(['id'])
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(5);
        if ($projectContextEnabled && $activeProjectId !== null) {
            $recentFormsQuery->andWhere(['project_id' => $activeProjectId]);
        }
        $recentForms = $recentFormsQuery->all();

        $submissionCountSubQuery = FormSubmission::find()
            ->select(['form_id', 'submission_count' => 'COUNT(*)'])
            ->groupBy('form_id');

        $formsQuery = Form::find()
            ->alias('f')
            ->select([
                'f.id',
                'f.user_id',
                'f.name',
                'schema_js' => new \yii\db\Expression('f.' . $schemaColumn),
                'f.created_at',
                'submission_count' => new \yii\db\Expression('COALESCE(fs_count.submission_count, 0)'),
            ])
            ->leftJoin(['fs_count' => $submissionCountSubQuery], 'fs_count.form_id = f.id')
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC])
            ->limit(6);
        $formsQuery->where(['f.user_id' => $userId]);
        if ($projectContextEnabled && $activeProjectId !== null) {
            $formsQuery->andWhere(['f.project_id' => $activeProjectId]);
        }
        $forms = $formsQuery->all();

        $recentSubmissionsQuery = FormSubmission::find()
            ->select(['form_submissions.id', 'form_submissions.form_id', 'form_submissions.created_at'])
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->with([
                'form' => function ($q) {
                    $q->select(['id', 'name']);
                }
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10);
        $recentSubmissionsQuery->where(['forms.user_id' => $userId]);
        if ($projectContextEnabled && $activeProjectId !== null) {
            $recentSubmissionsQuery->andWhere(['forms.project_id' => $activeProjectId]);
        }
        $recentSubmissions = $recentSubmissionsQuery->all();

        $recentFormIds = array_unique(array_map(function ($submission) {
            return (int) $submission->form_id;
        }, $recentSubmissions));

        $formSubmissionCounts = [];
        if (!empty($recentFormIds)) {
            $countRows = FormSubmission::find()
                ->select(['form_id', 'total' => 'COUNT(*)'])
                ->where(['form_id' => $recentFormIds])
                ->groupBy('form_id')
                ->asArray()
                ->all();

            foreach ($countRows as $row) {
                $formSubmissionCounts[(int) $row['form_id']] = (int) $row['total'];
            }
        }

        // Use project database name if available, otherwise use the general active database
        $displayDatabase = $projectDatabaseName ?: ($databaseContext['activeDatabase'] ?? 'default');
        $databaseTableQuery = DbTable::find()->where(['user_id' => $userId]);
        if ($projectContextEnabled && $activeProjectId !== null) {
            $databaseTableQuery->andWhere(['project_id' => $activeProjectId]);
        }
        $databaseTableCount = (int) $databaseTableQuery->count();

        return $this->render('dashboard', [
            'forms' => $forms,
            'recentSubmissions' => $recentSubmissions,
            'formSubmissionCounts' => $formSubmissionCounts,
            'totalForms' => $totalForms,
            'totalSubmissions' => $totalSubmissions,
            'todaySubmissions' => $todaySubmissions,
            'recentForms' => $recentForms,
            'databaseContext' => $databaseContext,
            'activeProject' => $activeProject,
            'projectDatabaseName' => $displayDatabase,
            'databaseTableCount' => $databaseTableCount,
        ]);
    }

    /**
     * Login
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirectAfterAuthentication();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirectAfterAuthentication();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        // Destroy identity from session
        Yii::$app->user->logout(true);

        // Clear session completely
        Yii::$app->session->destroy();

        // Clear cookies
        $cookies = Yii::$app->response->cookies;
        $cookies->remove('_identity');
        $cookies->remove('_csrf');

        return $this->redirect(['site/login']);
    }

    /**
     * User profile
     */
    public function actionProfile()
    {
        $user = Yii::$app->user->identity;
        $totalForms = Form::find()->where(['user_id' => $user->id])->count();
        $totalSubmissions = FormSubmission::find()
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->where(['forms.user_id' => $user->id])
            ->count();

        return $this->render('profile', [
            'user' => $user,
            'totalForms' => $totalForms,
            'totalSubmissions' => $totalSubmissions,
        ]);
    }

    /**
     * Change password
     */
    public function actionChangePassword()
    {
        if (Yii::$app->request->isPost) {
            $user = Yii::$app->user->identity;
            $currentPassword = Yii::$app->request->post('current_password');
            $newPassword = Yii::$app->request->post('new_password');
            $confirmPassword = Yii::$app->request->post('confirm_password');

            if (!$user->validatePassword($currentPassword)) {
                Yii::$app->session->setFlash('error', 'Current password is incorrect.');
            } elseif (strlen($newPassword) < 6) {
                Yii::$app->session->setFlash('error', 'New password must be at least 6 characters.');
            } elseif ($newPassword !== $confirmPassword) {
                Yii::$app->session->setFlash('error', 'Passwords do not match.');
            } else {
                $user->setPassword($newPassword);
                if ($user->save(false)) {
                    Yii::$app->session->setFlash('success', 'Password changed successfully!');
                    return $this->redirect(['profile']);
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to change password.');
                }
            }
        }

        return $this->redirect(['profile']);
    }
}
