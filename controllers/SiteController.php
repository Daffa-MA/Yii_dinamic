<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\Form;
use app\models\FormSubmission;

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
                'only' => ['logout', 'dashboard'],
                'rules' => [
                    [
                        'actions' => ['logout', 'dashboard'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
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
        $userId = Yii::$app->user->id;
        
        // Statistics
        $totalForms = Form::find()->where(['user_id' => $userId])->count();
        $totalSubmissions = FormSubmission::find()
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->where(['forms.user_id' => $userId])
            ->count();
        $recentForms = Form::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();
        $todaySubmissions = FormSubmission::find()
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->where(['forms.user_id' => $userId])
            ->andWhere(['>=', 'form_submissions.created_at', date('Y-m-d 00:00:00')])
            ->count();

        $forms = Form::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $recentSubmissions = FormSubmission::find()
            ->innerJoin('forms', 'forms.id = form_submissions.form_id')
            ->where(['forms.user_id' => $userId])
            ->with('form')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('dashboard', [
            'forms' => $forms,
            'recentSubmissions' => $recentSubmissions,
            'totalForms' => $totalForms,
            'totalSubmissions' => $totalSubmissions,
            'todaySubmissions' => $todaySubmissions,
            'recentForms' => $recentForms,
        ]);
    }

    /**
     * Login
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
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
        Yii::$app->user->logout();
        return $this->goHome();
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
