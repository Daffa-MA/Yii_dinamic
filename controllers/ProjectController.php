<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
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
use app\components\DomainContext;
use app\components\ActiveProjectContext;
use app\components\AuthContextDebugLogger;
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

    private function resolveProjectDomainPrefix(Project $project): string
    {
        $defaultPrefix = Project::normalizeSlug((string)$project->slug);
        if ($defaultPrefix === '') {
            $defaultPrefix = Project::normalizeSlug((string)$project->name);
        }

        if ($defaultPrefix === '') {
            $defaultPrefix = 'project';
        }

        if (!$this->isCommanderSuperAdmin()) {
            return $defaultPrefix;
        }

        $customPrefix = Project::normalizeDomainPrefix((string)$project->custom_domain_prefix);
        if ($customPrefix === '') {
            $customPrefix = Project::extractProjectDomainPrefix((string)$project->custom_domain);
        }

        if ($customPrefix === '') {
            return $defaultPrefix;
        }

        if (strlen($customPrefix) > 63) {
            $customPrefix = substr($customPrefix, 0, 63);
        }

        return $customPrefix;
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

        $domainContext = new DomainContext();
        if ($domainContext->isWorkspaceDomain()) {
            $hostProject = Project::findByCustomDomain($domainContext->currentHost());
            if ($hostProject !== null && (int)$hostProject->id === $projectId) {
                return $hostProject;
            }
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

    private function resolveWorkspaceProjectIdFromDomain(): ?int
    {
        $domainContext = new DomainContext();
        if (!$domainContext->isWorkspaceDomain()) {
            return null;
        }

        $host = $domainContext->currentHost();
        if ($host === '') {
            return null;
        }

        $project = Project::findByCustomDomain($host);
        return $project !== null ? (int)$project->id : null;
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
                        'actions' => ['index'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['login', 'access-denied', 'change-password', 'logout'],
                        'allow' => true,
                    ],
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
        if (!$this->isCommanderSuperAdmin()) {
            throw new ForbiddenHttpException('Akses project list hanya untuk Commander superadmin.');
        }

        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Workspace project belum tersedia di database saat ini. Jalankan migrasi terbaru untuk mengaktifkan fitur project.');
            return $this->redirect(['/dashboard']);
        }

        Project::ensureProjectStructure();
        $context = new ActiveProjectContext();
        $model = new Project();
        $model->user_id = Yii::$app->user->id;
        $isCommanderSuperAdmin = $this->isCommanderSuperAdmin();
        $model->custom_domain_prefix = Project::normalizeSlug((string)($model->custom_domain_prefix ?: $model->name));

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post())) {
                $model->user_id = Yii::$app->user->id;
                $model->slug = Project::buildProjectSlug((string)$model->name);
                $domainPrefix = $this->resolveProjectDomainPrefix($model);
                $model->custom_domain_prefix = $domainPrefix;
                $model->custom_domain = Project::buildProjectDomainFromPrefix($domainPrefix);
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

                    return $this->redirectToProjectLogin((int)$model->id, ['/dashboard']);
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
            'isCommanderSuperAdmin' => $isCommanderSuperAdmin,
        ]);
    }

    public function actionSelect($id)
    {
        if (!ProjectSchema::supportsProjectContext()) {
            Yii::$app->session->setFlash('warning', 'Workspace project belum tersedia di database saat ini.');
            return $this->redirect(['/dashboard']);
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
            $workspaceUrl = $project->getWorkspaceUrl('/dashboard');
            AuthContextDebugLogger::log('commander_open_workspace', [
                'project_id' => (int)$project->id,
                'target_project_domain' => (string)($project->custom_domain ?? ''),
                'workspace_url' => $workspaceUrl,
                'commander_superadmin' => true,
            ]);
            if ($workspaceUrl !== null) {
                return $this->redirect($workspaceUrl);
            }

            Yii::$app->session->setFlash('warning', 'Domain project belum diset. Silakan lengkapi custom domain terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        return $this->redirectToProjectLogin((int)$project->id, ['/dashboard']);
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
            $domainPrefix = $this->resolveProjectDomainPrefix($project);
            $project->custom_domain_prefix = $domainPrefix;
            $project->custom_domain = Project::buildProjectDomainFromPrefix($domainPrefix);
            $project->domain_status = 'active';
            $project->domain_verified_at = date('Y-m-d H:i:s');

            if ($project->save()) {
                Yii::$app->session->setFlash('success', 'Project settings berhasil disimpan.');
                return $this->redirect(['project/update', 'id' => $project->id]);
            }

            Yii::$app->session->setFlash('error', implode(', ', $project->getFirstErrors()) ?: 'Gagal menyimpan project settings.');
        }

        if (trim((string)$project->custom_domain_prefix) === '') {
            $project->custom_domain_prefix = Project::extractProjectDomainPrefix((string)$project->custom_domain);
        }

        return $this->render('update', [
            'project' => $project,
            'isCommanderSuperAdmin' => $this->isCommanderSuperAdmin(),
            'databaseName' => $this->resolveProjectDatabaseName($project),
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

    private function redirectToProjectLogin(int $projectId, array $defaultReturnUrl = ['/dashboard'])
    {
        $session = Yii::$app->session;
        $returnUrl = (string)$session->get('project_required_return_url', '');
        $session->remove('project_required_return_url');

        $targetReturnUrl = $returnUrl !== '' ? $returnUrl : Yii::$app->urlManager->createUrl($defaultReturnUrl);
        $authContext = new ProjectAuthContext();
        $commanderAuth = new CommanderAuthContext();

        if ($commanderAuth->isSuperAdmin()) {
            $project = Project::findOne(['id' => $projectId]);
            if ($project !== null) {
                $workspaceUrl = $project->getWorkspaceUrl('/dashboard');
                AuthContextDebugLogger::log('redirect_to_workspace_for_commander', [
                    'project_id' => $projectId,
                    'target_project_domain' => (string)($project->custom_domain ?? ''),
                    'workspace_url' => $workspaceUrl,
                    'requested_return_url' => $targetReturnUrl,
                    'commander_superadmin' => true,
                ]);
                if ($workspaceUrl !== null) {
                    return $this->redirect($workspaceUrl);
                }
            }

            Yii::$app->session->setFlash('warning', 'Domain project belum diset. Silakan lengkapi custom domain terlebih dahulu.');
            return $this->redirect(['project/index']);
        }

        if ($authContext->isAuthenticated($projectId)) {
            $permissionService = new \app\components\ProjectPermissionService();
            $landingRoute = $permissionService->resolveAccessibleLandingRoute($projectId, $targetReturnUrl);
            if ($landingRoute !== null) {
                return $this->redirect($landingRoute);
            }

            return $this->redirect(['project/access-denied', 'id' => $projectId]);
        }

        AuthContextDebugLogger::log('redirect_to_project_login', [
            'project_id' => $projectId,
            'target_return_url' => $targetReturnUrl,
            'commander_authenticated' => $commanderAuth->isAuthenticated(),
            'commander_role' => $commanderAuth->getRole(),
            'commander_superadmin' => $commanderAuth->isSuperAdmin(),
        ]);
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
        } else {
            $resolvedProjectId = $context->getResolvedDomainProjectId() ?? $this->resolveWorkspaceProjectIdFromDomain();
            if ($resolvedProjectId !== null) {
                $projectId = $resolvedProjectId;
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
        }

        $activeProjectId = $context->getActiveProjectId();
        if ($activeProjectId === null) {
            $fallbackProjectId = $this->resolveWorkspaceProjectIdFromDomain();
            if ($fallbackProjectId !== null) {
                $projectId = $fallbackProjectId;
                $context->setActiveProject($projectId);
                $activeProjectId = $projectId;
            }
        }

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
        $workspaceSettings->loadForProjectLogin((int)$activeProjectId);
        $this->logProjectLoginBackgroundContext((int)$activeProjectId, $workspaceSettings);

        $commanderAuth = new CommanderAuthContext();
        if ($commanderAuth->isSuperAdmin()) {
            return $this->redirect(['/dashboard']);
        }

        $authContext = new ProjectAuthContext();
        $forceLogin = Yii::$app->request->get('force_login', '0') === '1';
        if ($authContext->isAuthenticated($activeProjectId) && !$forceLogin) {
            return $this->redirectToProjectLogin($activeProjectId, ['/dashboard']);
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

                Yii::$app->session->setFlash('warning', 'Role Anda belum memiliki akses menu.');
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

    private function logProjectLoginBackgroundContext(int $projectId, WorkspaceSettings $workspaceSettings): void
    {
        try {
            $debug = $workspaceSettings->getLoginBackgroundDebug();
            $debug['project_id'] = $projectId;
            $debug['host'] = Yii::$app->request->getHostName();
            $debug['route'] = Yii::$app->requestedRoute;
            $debug['active_database'] = $this->resolveCurrentDatabaseName(Yii::$app->db);
            AuthContextDebugLogger::log('project_login_background_context', $debug);
        } catch (\Throwable $e) {
            Yii::warning('Project login background debug failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function resolveCurrentDatabaseName(\yii\db\Connection $connection): string
    {
        if (preg_match('/dbname=([^;]+)/i', $connection->dsn, $matches) === 1) {
            return trim((string)$matches[1]);
        }

        try {
            return trim((string)$connection->createCommand('SELECT DATABASE()')->queryScalar());
        } catch (\Throwable $e) {
            return '';
        }
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
            return $this->redirectToProjectLogin($projectId, ['/dashboard']);
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

                    Yii::$app->session->setFlash('warning', 'Role Anda belum memiliki akses menu.');
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
        $context = new ActiveProjectContext();
        $projectId = $context->getActiveProjectId() ?? (int)Yii::$app->request->get('id', 0);

        if ($projectId <= 0) {
            $domainContext = new DomainContext();
            if ($domainContext->isWorkspaceDomain()) {
                $project = Project::findByCustomDomain($domainContext->currentHost());
                if ($project !== null) {
                    $projectId = (int)$project->id;
                }
            }
        }

        if ($projectId > 0) {
            (new ProjectAuthContext())->logout($projectId);
            $context->clear();
            if ((string)Yii::$app->request->get('reason', '') === 'access_denied') {
                Yii::$app->session->setFlash('warning', 'Anda sudah logout karena role akun ini belum memiliki akses aktif. Silakan login dengan akun lain atau minta admin mengatur permission role.');
            }
            return $this->redirect(['project/login', 'id' => $projectId, 'force_login' => 1]);
        }

        return $this->redirect(['project/index']);
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
            $projectId = (new ActiveProjectContext())->getResolvedDomainProjectId()
                ?? $this->resolveWorkspaceProjectIdFromDomain()
                ?? (new ActiveProjectContext())->getActiveProjectId()
                ?? 0;
        }

        if ($projectId <= 0) {
            $domainContext = new DomainContext();
            if ($domainContext->isWorkspaceDomain()) {
                Yii::$app->session->setFlash('warning', 'Workspace aktif belum terdeteksi, silakan login ulang.');
                return $this->redirect(['project/login']);
            }

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
        $workspaceSettings->loadFromDatabase();

        $permissionService = new \app\components\ProjectPermissionService();
        $landingRoute = $permissionService->resolveAccessibleLandingRoute($projectId);
        if ($landingRoute !== null) {
            return $this->redirect($landingRoute);
        }

        return $this->render('access-denied', [
            'project' => $project,
            'user' => $user,
            'workspaceSettings' => $workspaceSettings,
            'landingRoute' => $landingRoute,
        ]);
    }

    public function actionProfile()
    {
        $rootDomain = (new DomainContext())->rootDomain();
        if ($rootDomain === '') {
            return $this->redirect(['site/profile']);
        }

        return Yii::$app->response->redirect('https://' . $rootDomain . '/profile');
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
