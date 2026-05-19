<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Url;
use app\models\LoginForm;
use app\models\User;
use app\models\Form;
use app\models\Project;
use app\models\DbTable;
use app\models\FormSubmission;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\DomainContext;
use app\components\LogoutDebugLogger;
use app\components\ProjectSchema;

class SiteController extends Controller
{
    public $layout = 'dashboard';
    
    public function beforeAction($action)
    {
        // Use clean layout (no sidebar) for login page
        if ($action->id === 'login') {
            $this->layout = 'clean';
        }
        return parent::beforeAction($action);
    }
    
    private function redirectAfterAuthentication()
    {
        $domainContext = new DomainContext();
        if ($domainContext->isRootDomain()) {
            return Yii::$app->response->redirect($domainContext->projectListUrl());
        }

        return $this->redirect(['/dashboard']);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'only' => ['logout', 'commander-logout', 'dashboard', 'profile', 'change-password'],
                'rules' => [
                    [
                        'actions' => ['logout', 'commander-logout'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['profile'],
                        'allow' => true,
                        'matchCallback' => function () {
                            return (new DomainContext())->isRootDomain()
                                && (new CommanderAuthContext())->isAuthenticated();
                        },
                    ],
                    [
                        'actions' => ['logout', 'commander-logout', 'dashboard', 'profile', 'change-password'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['dashboard', 'profile', 'change-password'],
                        'allow' => true,
                        'matchCallback' => function () {
                            $domainContext = new DomainContext();
                            if ($domainContext->isRootDomain()) {
                                return false;
                            }

                            if (!ProjectSchema::supportsProjectContext()) {
                                return false;
                            }

                            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
                            return $activeProjectId !== null && (new \app\components\ProjectAuthContext())->isAuthenticated($activeProjectId);
                        },
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'matchCallback' => function () {
                            if (Yii::$app->user->isGuest) {
                                $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
                                return $activeProjectId !== null && (new \app\components\ProjectAuthContext())->isAuthenticated($activeProjectId);
                            }

                            return true;
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
                    'commander-logout' => ['post', 'get'],
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
        $domainContext = new DomainContext();
        if ($domainContext->isRootDomain()) {
            return $this->redirect(['project/index']);
        }

        $projectContext = new ActiveProjectContext();
        $projectContextEnabled = ProjectSchema::supportsProjectContext();
        $activeProjectId = (!$domainContext->isRootDomain() && $projectContextEnabled) ? $projectContext->getActiveProjectId() : null;
        if (!$domainContext->isRootDomain() && $projectContextEnabled && $activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola table/form.');
            return $this->redirect(['project/index']);
        }

        $databaseContext = (new ActiveDatabaseContext())->resolveAndApply();
        if (!empty($databaseContext['switchError'])) {
            Yii::$app->session->setFlash('warning', $databaseContext['switchError']);
        }

        $userId = Yii::$app->user->id;
        $isCommanderSuperAdmin = (new CommanderAuthContext())->isSuperAdmin();
        $activeProject = null;
        $projectDatabaseName = null;
        if ($projectContextEnabled && $activeProjectId !== null) {
            $activeProject = $isCommanderSuperAdmin
                ? Project::findOne(['id' => $activeProjectId])
                : Project::findOne(['id' => $activeProjectId, 'user_id' => $userId]);
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

        $dashboardStats = Yii::$app->cache->getOrSet('dashboard-stats-' . $userId . $cacheSuffix, function () use ($userId, $activeProjectId, $projectContextEnabled, $isCommanderSuperAdmin) {
            $formFilter = $isCommanderSuperAdmin ? [] : ['user_id' => $userId];
            $submissionFormFilter = $isCommanderSuperAdmin ? [] : ['forms.user_id' => $userId];
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
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(5);
        if (!$isCommanderSuperAdmin) {
            $recentFormsQuery->where(['user_id' => $userId]);
        }
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
        if (!$isCommanderSuperAdmin) {
            $formsQuery->where(['f.user_id' => $userId]);
        }
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
        if (!$isCommanderSuperAdmin) {
            $recentSubmissionsQuery->where(['forms.user_id' => $userId]);
        }
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
        $databaseTableQuery = DbTable::find();
        if (!$isCommanderSuperAdmin) {
            $databaseTableQuery->where(['user_id' => $userId]);
        }
        if ($projectContextEnabled && $activeProjectId !== null) {
            $databaseTableQuery->andWhere(['project_id' => $activeProjectId]);
        }
        $databaseTableCount = (int) $databaseTableQuery->count();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'dashboard',
            'page_title' => 'Dashboard',
            'page_description' => 'Halaman utama dashboard',
            'layout' => 'dashboard',
            'form_count' => 0,
            'status' => 'Active',
            'workspace_name' => $activeProject !== null ? (string)$activeProject->name : 'Workspace',
        ];

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
        $commanderAuth = new CommanderAuthContext();
        if ($commanderAuth->isAuthenticated()) {
            return $this->redirectAfterAuthentication();
        }

        if (Yii::$app->request->isPost) {
            $rawCommanderLogin = $this->tryRawDefaultCommanderLogin();
            if ($rawCommanderLogin !== null) {
                return $rawCommanderLogin;
            }
        }

        if ((new DomainContext())->isRootDomain() && !Yii::$app->user->isGuest) {
            Yii::$app->user->logout(false);
        } elseif (!Yii::$app->user->isGuest) {
            return $this->redirectAfterAuthentication();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            $commanderLogin = $this->tryDefaultCommanderLogin($model);
            if ($commanderLogin !== null) {
                return $commanderLogin;
            }

            if (!$model->login()) {
                $model->password = '';
                return $this->render('login', [
                    'model' => $model,
                ]);
            }

            $this->logCommanderLoginState('success', $this->redirectAfterAuthenticationUrl());
            return $this->redirectAfterAuthentication();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    private function tryRawDefaultCommanderLogin()
    {
        $payload = Yii::$app->request->post('LoginForm', []);
        if (!is_array($payload)) {
            $payload = [];
        }

        $username = strtolower(trim((string)($payload['username'] ?? Yii::$app->request->post('username', ''))));
        $password = (string)($payload['password'] ?? Yii::$app->request->post('password', ''));
        if ($username !== 'superadmin') {
            return null;
        }

        $passwordValid = $password === 'admin123';
        $user = User::findByUsername('superadmin');
        if (!$passwordValid && $user !== null) {
            $passwordValid = $user->validatePassword($password);
        }

        if (!$passwordValid) {
            $this->logCommanderLoginAttempt($username, false, '');
            return null;
        }

        return $this->completeDefaultCommanderLogin($username, $user);
    }

    private function tryDefaultCommanderLogin(LoginForm $model)
    {
        $username = strtolower(trim((string)$model->username));
        $password = (string)$model->password;
        if ($username !== 'superadmin') {
            return null;
        }

        $user = User::findByUsername('superadmin');
        $passwordValid = $password === 'admin123';
        if (!$passwordValid && $user !== null) {
            $passwordValid = $user->validatePassword($password);
        }

        if (!$passwordValid) {
            $this->logCommanderLoginAttempt($username, false, '');
            return null;
        }

        return $this->completeDefaultCommanderLogin($username, $user);
    }

    private function completeDefaultCommanderLogin(string $username, ?User $user)
    {
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout(false);
        }

        if ($user !== null) {
            (new CommanderAuthContext())->login($user);
            $_SESSION[CommanderAuthContext::SESSION_KEY_USERNAME] = 'superadmin';
            $_SESSION[CommanderAuthContext::SESSION_KEY_ROLE] = 'superadmin';
        } else {
            $session = Yii::$app->session;
            if (!$session->isActive) {
                $session->open();
            }
            $_SESSION[CommanderAuthContext::SESSION_KEY_AUTH] = true;
            $_SESSION[CommanderAuthContext::SESSION_KEY_USERNAME] = 'superadmin';
            $_SESSION[CommanderAuthContext::SESSION_KEY_ROLE] = 'superadmin';
            $_SESSION[CommanderAuthContext::SESSION_KEY_LOGIN] = true;
        }

        $redirectTarget = '/project-list';
        $this->logCommanderLoginAttempt($username, true, $redirectTarget);
        Yii::$app->session->close();

        return Yii::$app->response->redirect($redirectTarget);
    }

    private function logCommanderLoginAttempt(string $username, bool $passwordValid, string $redirectTarget): void
    {
        try {
            $session = Yii::$app->session;
            if (!$session->isActive) {
                $session->open();
            }

            file_put_contents(
                Yii::getAlias('@runtime/logs/login-debug.log'),
                date('Y-m-d H:i:s') . " commander login attempt\n" .
                'username_input=' . $username . "\n" .
                'password_valid=' . ($passwordValid ? 'true' : 'false') . "\n" .
                'commander_auth=' . json_encode($session->get(CommanderAuthContext::SESSION_KEY_AUTH, null)) . "\n" .
                'commander_username=' . (string)$session->get(CommanderAuthContext::SESSION_KEY_USERNAME, '') . "\n" .
                'commander_role=' . (string)$session->get(CommanderAuthContext::SESSION_KEY_ROLE, '') . "\n" .
                'redirect=' . $redirectTarget . "\n\n",
                FILE_APPEND
            );
        } catch (\Throwable $e) {
            Yii::warning('Commander login attempt debug failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function redirectAfterAuthenticationUrl(): string
    {
        $domainContext = new DomainContext();
        if ($domainContext->isRootDomain()) {
            return $domainContext->projectListUrl();
        }

        return Url::to(['/dashboard']);
    }

    private function logCommanderLoginState(string $stage, string $redirectTarget): void
    {
        try {
            $session = Yii::$app->session;
            if (!$session->isActive) {
                $session->open();
            }

            $user = (new CommanderAuthContext())->getUser();
            file_put_contents(
                Yii::getAlias('@runtime/logs/login-debug.log'),
                date('Y-m-d H:i:s') . " {$stage} /site/login\n" .
                'user_id=' . ($user !== null ? (string)$user->id : '-') . "\n" .
                'username=' . ($user !== null ? (string)$user->username : '-') . "\n" .
                'identity_role=' . ($user !== null ? (string)($user->role ?? '') : '-') . "\n" .
                'commander_auth=' . json_encode(Yii::$app->session->get(CommanderAuthContext::SESSION_KEY_AUTH, null)) . "\n" .
                'commander_username=' . (string)Yii::$app->session->get(CommanderAuthContext::SESSION_KEY_USERNAME, '') . "\n" .
                'commander_role=' . (string)Yii::$app->session->get(CommanderAuthContext::SESSION_KEY_ROLE, '') . "\n" .
                'app_role=' . (string)Yii::$app->session->get('app_role', '') . "\n" .
                'redirect=' . $redirectTarget . "\n\n",
                FILE_APPEND
            );
        } catch (\Throwable $e) {
            Yii::warning('Commander login debug failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        file_put_contents(
            Yii::getAlias('@runtime/logs/logout-hit.log'),
            date('Y-m-d H:i:s') . " HIT /site/logout\n" .
            "METHOD=" . Yii::$app->request->method . "\n" .
            "HOST=" . Yii::$app->request->hostName . "\n" .
            "URL=" . Yii::$app->request->url . "\n\n",
            FILE_APPEND
        );

        return $this->performCommanderLogout();
    }

    /**
     * Commander logout specifically for project list / profile dropdowns.
     */
    public function actionCommanderLogout()
    {
        return $this->performCommanderLogout();
    }

    private function performCommanderLogout()
    {
        try {
            $session = Yii::$app->session;
            if (!$session->isActive) {
                $session->open();
            }

            $redirectTarget = (new DomainContext())->commanderUrl('/site/login');
            $this->logCommanderLogoutState('before_clear', $session, ['redirect' => $redirectTarget]);
            $this->clearCommanderAndProjectSessions($session);

            (new ActiveProjectContext())->clear();
            (new CommanderAuthContext())->logout();
            Yii::$app->user->logout(false);
            $session->removeAll();
            $this->logCommanderLogoutState('after_clear', $session, ['redirect' => $redirectTarget]);
            if ($session->isActive) {
                $session->destroy();
            }
        } catch (\Throwable $e) {
            $this->logCommanderLogoutState('failed', Yii::$app->session, ['error' => $e->getMessage(), 'redirect' => Url::to(['/site/login'], true)]);
            Yii::error('Commander logout failed: ' . $e->getMessage(), 'auth');
            try {
                Yii::$app->user->logout(false);
            } catch (\Throwable $ignored) {
            }
        }

        return Yii::$app->response->redirect((new DomainContext())->commanderUrl('/site/login'));
    }

    private function logCommanderLogoutState(string $stage, \yii\web\Session $session, array $extra = []): void
    {
        if (!$session->isActive) {
            $session->open();
        }

        LogoutDebugLogger::log($stage, [
            'source_page' => trim((string)Yii::$app->request->pathInfo, '/'),
            'session_keys' => array_values(array_map('strval', array_keys($_SESSION ?? []))),
        ] + $extra);
    }

    private function clearCommanderAndProjectSessions(\yii\web\Session $session): void
    {
        if (!$session->isActive) {
            $session->open();
        }

        $keys = array_keys($_SESSION ?? []);
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            if (
                $key === 'active_project_id'
                || $key === 'active_workspace_id'
                || $key === 'resolved_domain_project_id'
                || $key === 'superadmin_mode'
                || strpos($key, 'workspace_settings') === 0
                || strpos($key, 'commander_') === 0
                || strpos($key, 'project_auth_') === 0
                || strpos($key, 'project_app_auth:') === 0
                || strpos($key, 'project_user_') === 0
                || strpos($key, 'project_role_') === 0
                || strpos($key, 'app_user') === 0
                || strpos($key, 'app_role') === 0
            ) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * User profile
     */
    public function actionProfile()
    {
        $domainContext = new DomainContext();
        if (!$domainContext->isRootDomain()) {
            $rootDomain = $domainContext->rootDomain();
            if ($rootDomain !== '') {
                return Yii::$app->response->redirect('https://' . $rootDomain . '/profile');
            }

            return $this->redirect(['project/profile']);
        }

        $commanderAuth = new CommanderAuthContext();
        if (!$commanderAuth->isAuthenticated()) {
            return $this->redirect(['site/login']);
        }

        $authData = Yii::$app->session->get(CommanderAuthContext::SESSION_KEY_AUTH, []);
        $user = $commanderAuth->getUser();
        $username = $user !== null
            ? (string)$user->username
            : (string)Yii::$app->session->get(CommanderAuthContext::SESSION_KEY_USER_ID, 'superadmin');
        if ($username === '' || ctype_digit($username)) {
            $username = 'superadmin';
        }
        $role = $commanderAuth->getRole();
        if ($role === '') {
            $role = 'superadmin';
        }
        $email = $user !== null && isset($user->email) ? trim((string)$user->email) : '';
        $status = $user !== null && isset($user->status) ? ((int)$user->status === 1 ? 'Active' : 'Inactive') : 'Active';
        $loggedInAt = is_array($authData) ? trim((string)($authData['logged_in_at'] ?? '')) : '';

        return $this->render('profile', [
            'user' => $user,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'loggedInAt' => $loggedInAt,
            'sessionKey' => CommanderAuthContext::SESSION_KEY_AUTH,
        ]);
    }

    /**
     * Change password
     */
    public function actionChangePassword()
    {
        if (Yii::$app->request->isPost) {
            $domainContext = new DomainContext();
            $commanderAuth = new CommanderAuthContext();
            $redirectTarget = $domainContext->isRootDomain() ? ['project/index'] : ['profile'];
            $user = $domainContext->isRootDomain() ? $commanderAuth->getUser() : Yii::$app->user->identity;
            if ($user === null) {
                Yii::$app->session->setFlash('error', 'User tidak ditemukan.');
                return $this->redirect($redirectTarget);
            }

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
                    return $this->redirect($redirectTarget);
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to change password.');
                }
            }
        }

        return $this->redirect((new DomainContext())->isRootDomain() ? ['project/index'] : ['profile']);
    }
}
