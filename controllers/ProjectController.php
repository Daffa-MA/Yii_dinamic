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
use app\models\DbTableColumn;
use app\models\DbTable;
use app\models\ProjectLoginForm;
use app\models\ProjectUser;
use app\models\User;
use app\models\WorkspaceSettings;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\components\ProjectSchema;
use app\components\DatabaseSchemaInitializer;

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

    private function isCommanderSuperAdmin(): bool
    {
        return (new CommanderAuthContext())->isSuperAdmin();
    }

    private function buildAccessibleProjectQuery(): \yii\db\ActiveQuery
    {
        $query = Project::find();
        if (!$this->isCommanderSuperAdmin()) {
            $query->where(['user_id' => Yii::$app->user->id]);
        }

        return $query;
    }

    private function findAccessibleProject(int $projectId): ?Project
    {
        if ($projectId <= 0) {
            return null;
        }

        if ($this->isCommanderSuperAdmin()) {
            return Project::findOne($projectId);
        }

        $resolvedDomainProjectId = (new ActiveProjectContext())->getResolvedDomainProjectId();
        if ($resolvedDomainProjectId !== null && $resolvedDomainProjectId === $projectId) {
            return Project::findOne($projectId);
        }

        return Project::findOne([
            'id' => $projectId,
            'user_id' => Yii::$app->user->id,
        ]);
    }

    private function ensureProjectDatabase(Project $project, bool $mustBeNew = false): string
    {
        $databaseName = $this->resolveProjectDatabaseName($project);
        $databaseContext = new ActiveDatabaseContext();

        if ($mustBeNew && $databaseContext->databaseExistsOnCurrentServer($databaseName)) {
            throw new \RuntimeException("Nama database '{$databaseName}' sudah ada. Gunakan nama project lain yang unik.");
        }

        $databaseContext->createDatabase($databaseName);
        
        // Initialize database schema with all required tables
        DatabaseSchemaInitializer::initializeProjectDatabase($databaseName);
        $this->ensureDefaultAppMetadata($project);
        
        return $databaseName;
    }

    private function ensureDefaultAppMetadata(Project $project): void
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return;
        }

        $table = DbTable::findOne([
            'user_id' => (int)$project->user_id,
            'project_id' => (int)$project->id,
            'name' => 'users',
        ]);

        if ($table === null) {
            $table = new DbTable();
            $table->user_id = (int)$project->user_id;
            if ($table->hasAttribute('project_id')) {
                $table->project_id = (int)$project->id;
            }
            $table->name = 'users';
            $table->label = 'Users';
            $table->description = 'Default application users table';
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->is_created = 1;

            if (!$table->save()) {
                Yii::warning('Failed to create default users metadata: ' . implode(', ', $table->getErrorSummary(true)), 'app');
                return;
            }
        } else {
            $table->is_created = 1;
            $table->save(false, ['is_created']);
        }

        $defaultColumns = [
            [
                'name' => 'id',
                'label' => 'ID',
                'type' => 'INT',
                'length' => 11,
                'is_nullable' => 0,
                'is_primary' => 1,
                'is_unique' => 1,
                'is_auto_increment' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'VARCHAR',
                'length' => 255,
                'is_nullable' => 0,
                'sort_order' => 2,
            ],
            [
                'name' => 'username',
                'label' => 'Username',
                'type' => 'VARCHAR',
                'length' => 100,
                'is_nullable' => 0,
                'is_unique' => 1,
                'sort_order' => 3,
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'VARCHAR',
                'length' => 255,
                'is_nullable' => 0,
                'is_unique' => 1,
                'sort_order' => 4,
            ],
            [
                'name' => 'password_hash',
                'label' => 'Password Hash',
                'type' => 'VARCHAR',
                'length' => 255,
                'is_nullable' => 0,
                'sort_order' => 5,
            ],
            [
                'name' => 'role',
                'label' => 'Role',
                'type' => 'VARCHAR',
                'length' => 50,
                'is_nullable' => 0,
                'sort_order' => 6,
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'INT',
                'length' => 11,
                'is_nullable' => 0,
                'sort_order' => 7,
            ],
            [
                'name' => 'must_change_password',
                'label' => 'Must Change Password',
                'type' => 'TINYINT',
                'length' => 1,
                'is_nullable' => 0,
                'sort_order' => 8,
            ],
            [
                'name' => 'created_at',
                'label' => 'Created At',
                'type' => 'TIMESTAMP',
                'is_nullable' => 1,
                'sort_order' => 9,
            ],
            [
                'name' => 'updated_at',
                'label' => 'Updated At',
                'type' => 'TIMESTAMP',
                'is_nullable' => 1,
                'sort_order' => 10,
            ],
        ];

        foreach ($defaultColumns as $columnData) {
            $column = DbTableColumn::findOne([
                'table_id' => $table->id,
                'name' => $columnData['name'],
            ]);

            if ($column === null) {
                $column = new DbTableColumn();
                $column->table_id = (int)$table->id;
                $column->name = $columnData['name'];
                $column->label = $columnData['label'];
                $column->type = $columnData['type'];
                $column->length = $columnData['length'] ?? null;
                $column->is_nullable = (bool)($columnData['is_nullable'] ?? false);
                $column->is_primary = (bool)($columnData['is_primary'] ?? false);
                $column->is_unique = (bool)($columnData['is_unique'] ?? false);
                if ($column->hasAttribute('is_auto_increment')) {
                    $column->setAttribute('is_auto_increment', (bool)($columnData['is_auto_increment'] ?? false));
                }
                $column->sort_order = (int)($columnData['sort_order'] ?? 0);
                if (!$column->save()) {
                    Yii::warning('Failed to create default users metadata column: ' . implode(', ', $column->getErrorSummary(true)), 'app');
                }
            }
        }
    }

private function insertDefaultCmsData($newDb): void
    {
        $pagesTable = $newDb->getTableSchema('master_page', true);
        $menusTable = $newDb->getTableSchema('master_menu', true);
        
        if (!$pagesTable || !$menusTable) {
            return;
        }
        
        $hasPages = (new Query())->from('master_page')->exists($newDb);
        if (!$hasPages) {
            $newDb->createCommand()->batchInsert('master_page', ['name', 'slug', 'layout', 'description', 'is_active'], [
                ['Dashboard', 'dashboard', 'single_column', 'Halaman utama dashboard', 1],
                ['Profil', 'profil', 'default', 'Halaman profil perusahaan', 1],
                ['Layanan', 'layanan', 'grid', 'Daftar layanan', 1],
                ['Kontak', 'kontak', 'contact', 'Form kontak', 1],
                ['Artikel', 'artikel', 'blog', 'Daftar artikel', 1],
            ])->execute();
        }
        
        $hasMenus = (new Query())->from('master_menu')->exists($newDb);
        if (!$hasMenus) {
            $newDb->createCommand()->batchInsert('master_menu', ['name', 'icon', 'type', 'page_id', 'sort_order', 'order', 'is_active'], [
                ['Dashboard', 'dashboard', 'page', 1, 1, 1, 1],
                ['Profil', 'person', 'page', 2, 2, 2, 1],
                ['Layanan', 'shopping_cart', 'page', 3, 3, 3, 1],
                ['Kontak', 'mail', 'page', 4, 4, 4, 1],
                ['Artikel', 'article', 'page', 5, 5, 5, 1],
            ])->execute();
            
            $newDb->createCommand()->batchInsert('master_menu', ['name', 'icon', 'type', 'parent_id', 'sort_order', 'order', 'is_active'], [
                ['Pengaturan', 'settings', 'group', null, 10, 10, 1],
            ])->execute();
            
            $newDb->createCommand()->batchInsert('master_menu', ['name', 'icon', 'type', 'parent_id', 'route', 'sort_order', 'order', 'is_active'], [
                ['General', 'tune', 'route', 6, '/settings/general', 1, 1, 1],
                ['Akun', 'account_circle', 'route', 6, '/settings/account', 2, 2, 1],
                ['Notifikasi', 'notifications', 'route', 6, '/settings/notifications', 3, 3, 1],
            ])->execute();
        }
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

        Project::ensureProjectStructure();
        $context = new ActiveProjectContext();
        $model = new Project();
        $model->user_id = Yii::$app->user->id;

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->user_id = Yii::$app->user->id;
                $model->slug = Project::buildProjectSlug((string)$model->name);
                $model->custom_domain = Project::buildProjectDomainFromSlug((string)$model->slug);
                $model->domain_status = 'active';
                $model->domain_verified_at = date('Y-m-d H:i:s');

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
                    $domainHint = " Domain otomatis '{$model->custom_domain}' tersimpan dan aktif.";
                    Yii::$app->session->setFlash('success', "Project berhasil dibuat dan dipilih. {$serverHint}{$backupHint}{$domainHint}");

                    return $this->redirectToProjectLogin((int)$model->id, ['site/dashboard']);
                }

                Yii::$app->session->setFlash('error', implode(', ', $model->getFirstErrors()) ?: 'Gagal membuat project.');
            } else {
                Yii::$app->session->setFlash('error', 'Gagal memproses data form.');
            }
        }

        // Pagination setup
        $pageSize = 6;
        $query = $this->buildAccessibleProjectQuery()
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
        
        $totalCount = $query->count();
        $pagination = new Pagination([
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

        $project = $this->findAccessibleProject((int)$id);
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
        if ($this->isCommanderSuperAdmin()) {
            return $this->redirect(['site/dashboard']);
        }

        return $this->redirectToProjectLogin((int)$project->id, ['site/dashboard']);
    }

    public function actionUpdate($id)
    {
        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Workspace project belum tersedia di database saat ini.');
            return $this->redirect(['project/index']);
        }

        Project::ensureProjectStructure();

        $project = $this->findAccessibleProject((int)$id);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        if (Yii::$app->request->isPost && $project->load(Yii::$app->request->post())) {
            $project->slug = Project::buildProjectSlug((string)$project->name, (int)$project->id);
            $project->custom_domain = Project::buildProjectDomainFromSlug((string)$project->slug);
            $project->domain_status = 'active';
            $project->domain_verified_at = date('Y-m-d H:i:s');

            if ($project->save()) {
                Yii::$app->session->setFlash('success', 'Project settings berhasil disimpan.');
                return $this->redirect(['project/update', 'id' => $project->id]);
            }

            Yii::$app->session->setFlash('error', implode(', ', $project->getFirstErrors()) ?: 'Gagal menyimpan project settings.');
        }

        return $this->render('update', [
            'project' => $project,
        ]);
    }

    public function actionDelete($id)
    {
        $project = $this->findAccessibleProject((int)$id);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        try {
            $this->deleteProjectDatabase($project);
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', "Database project gagal dihapus: {$e->getMessage()}");
            return $this->redirect(['project/index']);
        }

        $projectName = $project->name;
        $project->delete();

        $activeContext = new ActiveProjectContext();
        $activeProjectId = $activeContext->getActiveProjectId();
        if ($activeProjectId == (int)$id) {
            $activeContext->clear();
        }

        Yii::$app->session->setFlash('success', "Project '{$projectName}' dan database-nya telah dihapus.");
        return $this->redirect(['project/index']);
    }

    private function deleteProjectDatabase($project)
    {
        $projectDatabase = $this->resolveProjectDatabaseName($project);
        if ($projectDatabase === null) {
            return;
        }

        $db = Yii::$app->db;
        $databaseName = $db->createCommand('SELECT DATABASE()')->queryScalar();
        if ($databaseName === $projectDatabase) {
            throw new \Exception('Cannot delete currently active database.');
        }

        $driver = $db->driverName;
        if ($driver === 'mysql') {
            Yii::$app->db->createCommand("DROP DATABASE IF EXISTS `{$projectDatabase}`")->execute();
        } elseif ($driver === 'sqlite') {
            $sqliteFile = Yii::getAlias('@runtime/') . 'databases/' . $projectDatabase . '.db';
            if (file_exists($sqliteFile)) {
                unlink($sqliteFile);
            }
        }
    }

    private function redirectToProjectLogin(int $projectId, array $defaultReturnUrl = ['site/dashboard'])
    {
        $session = Yii::$app->session;
        $returnUrl = (string)$session->get('project_required_return_url', '');
        $session->remove('project_required_return_url');

        $targetReturnUrl = $returnUrl !== '' ? $returnUrl : Yii::$app->urlManager->createUrl($defaultReturnUrl);
        $authContext = new ProjectAuthContext();
        $commanderAuth = new CommanderAuthContext();

        if ($commanderAuth->isSuperAdmin()) {
            return $this->redirect(['site/dashboard']);
        }

        if ($authContext->isAuthenticated($projectId)) {
            $permissionService = new \app\components\ProjectPermissionService();
            $landingRoute = $permissionService->resolveAccessibleLandingRoute($projectId, $targetReturnUrl);
            if ($landingRoute !== null) {
                return $this->redirect($landingRoute);
            }

            return $this->redirect(['project/access-denied', 'id' => $projectId]);
        }

        return $this->redirect([
            'project/login',
            'id' => $projectId,
            'return_url' => $targetReturnUrl,
        ]);
    }

    public function actionLogin($id = null)
    {
        $this->layout = 'project-login';

        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Project context belum tersedia. Jalankan migrasi terbaru terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        $projectId = (int)($id ?: Yii::$app->request->get('project_id', 0));
        $context = new ActiveProjectContext();

        if ($projectId > 0) {
            $project = $this->findAccessibleProject($projectId);
            if ($project === null) {
                throw new NotFoundHttpException('Project not found.');
            }

            $context->setActiveProject($projectId);
            try {
                $this->ensureProjectDatabase($project);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Project database belum siap: ' . $e->getMessage());
                return $this->redirect(['project/index']);
            }
        }

        $activeProjectId = $context->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->setFlash('warning', 'Pilih project terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        $project = $this->findAccessibleProject((int)$activeProjectId);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        (new ActiveDatabaseContext())->resolveAndApply();
        $workspaceSettings = new WorkspaceSettings();
        $workspaceSettings->loadFromSession();

        $commanderAuth = new CommanderAuthContext();
        if ($commanderAuth->isSuperAdmin()) {
            return $this->redirect(['site/dashboard']);
        }

        $authContext = new ProjectAuthContext();
        $forceLogin = Yii::$app->request->get('force_login', '0') === '1';
        if ($authContext->isAuthenticated($activeProjectId) && !$forceLogin) {
            return $this->redirectToProjectLogin($activeProjectId, ['site/dashboard']);
        }

        $model = new ProjectLoginForm();
        $returnUrl = (string)Yii::$app->request->post('return_url', Yii::$app->request->get('return_url', ''));

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $user = $model->getUser();
            if ($user !== null) {
                $authContext->login($project, $user);

                if ((int)$user->must_change_password === 1) {
                    Yii::$app->session->setFlash('warning', 'Anda masih menggunakan password default. Disarankan segera mengganti password.');
                }

                $permissionService = new \app\components\ProjectPermissionService();
                $landingRoute = $permissionService->resolveAccessibleLandingRoute($projectId, $returnUrl);
                if ($landingRoute !== null) {
                    return $this->redirect($landingRoute);
                }

                Yii::$app->session->setFlash('warning', 'Role Anda belum memiliki akses. Hubungi admin.');
                return $this->redirect(['project/access-denied', 'id' => $projectId]);
            }
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
            'project' => $project,
            'workspaceSettings' => $workspaceSettings,
            'returnUrl' => $returnUrl,
        ]);
    }

    public function actionChangePassword($id = null)
    {
        $this->layout = 'project-login';

        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Project context belum tersedia.');
            return $this->redirect(['project/index']);
        }

        $projectId = (int)($id ?: Yii::$app->request->get('project_id', 0));
        if ($projectId <= 0) {
            $projectId = (new ActiveProjectContext())->getActiveProjectId() ?? 0;
        }

        if ($projectId <= 0) {
            Yii::$app->session->setFlash('warning', 'Pilih project terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        $project = $this->findAccessibleProject($projectId);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        (new ActiveProjectContext())->setActiveProject($projectId);
        (new ActiveDatabaseContext())->resolveAndApply();

        $commanderAuth = new CommanderAuthContext();
        if ($commanderAuth->isSuperAdmin()) {
            return $this->redirectToProjectLogin($projectId, ['site/dashboard']);
        }

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return $this->redirect(['project/login', 'id' => $projectId]);
        }

        $currentPassword = (string)Yii::$app->request->post('current_password', '');
        $newPassword = (string)Yii::$app->request->post('new_password', '');
        $confirmPassword = (string)Yii::$app->request->post('confirm_password', '');
        $returnUrl = (string)Yii::$app->request->post('return_url', Yii::$app->request->get('return_url', ''));

        if (Yii::$app->request->isPost) {
            if (!$user->validatePassword($currentPassword)) {
                Yii::$app->session->setFlash('error', 'Password saat ini salah.');
            } elseif (strlen($newPassword) < 6) {
                Yii::$app->session->setFlash('error', 'Password baru minimal 6 karakter.');
            } elseif ($newPassword !== $confirmPassword) {
                Yii::$app->session->setFlash('error', 'Konfirmasi password tidak sama.');
            } else {
                $user->setPassword($newPassword);
                $user->must_change_password = 0;
                if ($user->save(false)) {
                    $authContext->login($project, $user);
                    Yii::$app->session->setFlash('success', 'Password berhasil diganti.');

                    $permissionService = new \app\components\ProjectPermissionService();
                    $landingRoute = $permissionService->resolveAccessibleLandingRoute($projectId, $returnUrl);
                    if ($landingRoute !== null) {
                        return $this->redirect($landingRoute);
                    }

                    Yii::$app->session->setFlash('warning', 'Role Anda belum memiliki akses. Hubungi admin.');
                    return $this->redirect(['project/access-denied', 'id' => $projectId]);
                }

                Yii::$app->session->setFlash('error', 'Gagal menyimpan password baru.');
            }
        }

        return $this->render('change-password', [
            'project' => $project,
            'user' => $user,
            'returnUrl' => $returnUrl,
        ]);
    }

    public function actionLogout()
    {
        return $this->redirect(['site/logout']);
    }

    public function actionAccessDenied($id = null)
    {
        $this->layout = 'project-login';

        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Project context belum tersedia.');
            return $this->redirect(['project/index']);
        }

        $projectId = (int)($id ?: Yii::$app->request->get('project_id', 0));
        if ($projectId <= 0) {
            $projectId = (new ActiveProjectContext())->getActiveProjectId() ?? 0;
        }

        if ($projectId <= 0) {
            Yii::$app->session->setFlash('warning', 'Pilih project terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        $project = $this->findAccessibleProject($projectId);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        (new ActiveProjectContext())->setActiveProject($projectId);
        (new ActiveDatabaseContext())->resolveAndApply();

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return $this->redirect(['project/login', 'id' => $projectId]);
        }

        $workspaceSettings = new WorkspaceSettings();
        $workspaceSettings->loadFromSession();

        $permissionService = new \app\components\ProjectPermissionService();
        $landingRoute = $permissionService->resolveAccessibleLandingRoute($projectId);

        return $this->render('access-denied', [
            'project' => $project,
            'user' => $user,
            'workspaceSettings' => $workspaceSettings,
            'landingRoute' => $landingRoute,
        ]);
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

        $project = $this->findAccessibleProject((int)$activeProjectId);
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

        $project = $this->findAccessibleProject((int)$activeProjectId);
        if ($project === null) {
            throw new NotFoundHttpException('Project not found.');
        }

        $isCommanderSuperAdmin = (new CommanderAuthContext())->isSuperAdmin();
        $baseQuery = (new Query())
            ->from(['fs' => FormSubmission::tableName()])
            ->innerJoin(['f' => Form::tableName()], 'f.id = fs.form_id')
            ->andWhere(['f.project_id' => $activeProjectId])
            ->andWhere(['not', ['fs.firebase_uid' => null]])
            ->andWhere(['<>', 'fs.firebase_uid', '']);
        if (!$isCommanderSuperAdmin) {
            $baseQuery->andWhere(['f.user_id' => Yii::$app->user->id]);
        }

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
