<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\Form;
use app\models\FormSubmission;
use app\components\ActiveDatabaseContext;

class SiteController extends Controller
{
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
            return $this->redirect(['dashboard']);
        }
        return $this->redirect(['login']);
    }

    /**
     * Dashboard
     */
    public function actionDashboard()
    {
        $databaseContext = (new ActiveDatabaseContext())->resolveAndApply();
        if (!empty($databaseContext['switchError'])) {
            Yii::$app->session->setFlash('warning', $databaseContext['switchError']);
        }

        $userId = Yii::$app->user->id;
        $schemaColumn = Form::getSchemaStorageColumn();
        $cacheSuffix = '-' . ($databaseContext['activeDatabase'] ?? 'default');

        $dashboardStats = Yii::$app->cache->getOrSet('dashboard-stats-' . $userId . $cacheSuffix, function () use ($userId) {
            $totalForms = Form::find()->where(['user_id' => $userId])->count();
            $totalSubmissions = FormSubmission::find()
                ->innerJoin('forms', 'forms.id = form_submissions.form_id')
                ->where(['forms.user_id' => $userId])
                ->count();
            $todaySubmissions = FormSubmission::find()
                ->innerJoin('forms', 'forms.id = form_submissions.form_id')
                ->where(['forms.user_id' => $userId])
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

        $recentForms = Form::find()
            ->select(['id'])
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(5)
            ->all();

        $submissionCountSubQuery = FormSubmission::find()
            ->select(['form_id', 'submission_count' => 'COUNT(*)'])
            ->groupBy('form_id');

        $forms = Form::find()
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
            ->where(['f.user_id' => $userId])
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC])
            ->limit(6)
            ->all();

        $recentSubmissions = FormSubmission::find()
            ->select(['form_submissions.id', 'form_submissions.form_id', 'form_submissions.created_at'])
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->where(['forms.user_id' => $userId])
            ->with([
                'form' => function ($q) {
                    $q->select(['id', 'name']);
                }
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

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

        return $this->render('dashboard', [
            'forms' => $forms,
            'recentSubmissions' => $recentSubmissions,
            'formSubmissionCounts' => $formSubmissionCounts,
            'totalForms' => $totalForms,
            'totalSubmissions' => $totalSubmissions,
            'todaySubmissions' => $todaySubmissions,
            'recentForms' => $recentForms,
            'databaseContext' => $databaseContext,
        ]);
    }

    /**
     * Login
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['dashboard']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['dashboard']);
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
