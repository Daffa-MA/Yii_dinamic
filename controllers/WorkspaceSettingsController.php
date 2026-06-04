<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\db\Query;
use app\components\WorkspaceMediaStorage;
use app\components\ProjectPermissionRegistry;

class WorkspaceSettingsController extends Controller
{
    public $layout = 'dashboard';
    
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save' => ['POST'],
                    'upload-logo' => ['POST'],
                    'remove-logo' => ['POST'],
                ],
            ],
        ];
    }
    
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        
        $dbContext = new \app\components\ActiveDatabaseContext();
        $dbContext->resolveAndApply();
        
        return true;
    }
    
    public function actionIndex()
    {
        $model = $this->loadSettings();
        
        return $this->render('index', [
            'model' => $model,
        ]);
    }
    
    public function actionSave()
    {
        $model = $this->loadSettings();
        $oldLoginBackground = trim((string)$model->login_background_image);
        $uploadedLoginBackground = UploadedFile::getInstance($model, 'login_background_upload');
        if ($uploadedLoginBackground !== null) {
            $model->login_background_upload = $uploadedLoginBackground;
        }
        
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($uploadedLoginBackground !== null) {
                    $uploadResult = $this->storeLoginBackgroundUpload($model, $uploadedLoginBackground);
                    if (!$uploadResult['success']) {
                        return $uploadResult;
                    }
                } elseif ($oldLoginBackground !== '' && !preg_match('#^https?://#i', $oldLoginBackground)) {
                    $newValue = trim((string)$model->login_background_image);
                    if ($newValue === '' || $newValue !== $oldLoginBackground) {
                        $this->deleteWorkspaceMediaFile($oldLoginBackground);
                    }
                }
                $model->save();
                return ['success' => true, 'message' => 'Pengaturan berhasil disimpan!'];
            }
            return ['success' => false, 'errors' => $model->errors];
        }
        
        if ($model->load(Yii::$app->request->post())) {
            if ($model->validate()) {
                if ($uploadedLoginBackground !== null) {
                    $uploadResult = $this->storeLoginBackgroundUpload($model, $uploadedLoginBackground);
                    if (!$uploadResult['success']) {
                        Yii::$app->session->setFlash('error', $uploadResult['message'] ?? 'Gagal mengunggah background login.');
                        return $this->redirect(['index']);
                    }
                } elseif ($oldLoginBackground !== '' && !preg_match('#^https?://#i', $oldLoginBackground)) {
                    $newValue = trim((string)$model->login_background_image);
                    if ($newValue === '' || $newValue !== $oldLoginBackground) {
                        $this->deleteWorkspaceMediaFile($oldLoginBackground);
                    }
                }

                if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Pengaturan berhasil disimpan!');
                return $this->redirect(['index']);
                }
            }
        }
        
        return $this->render('index', [
            'model' => $model,
        ]);
    }
    
    public function actionReset()
    {
        $model = $this->loadSettings();
        $model->reset();
        return $this->redirect(['index']);
    }

    public function actionPermissions()
    {
        $this->ensureRoleAccessTable();

        $db = Yii::$app->db;
        $roleOptions = $this->loadWorkspaceRoles();
        $selectedRoleName = strtolower(trim((string)Yii::$app->request->get('role_name', '')));
        if ($selectedRoleName === '') {
            $selectedRoleName = $roleOptions[0]['name'] ?? 'admin';
        }
        $availableRoleNames = array_column($roleOptions, 'name');
        if (!in_array($selectedRoleName, $availableRoleNames, true)) {
            $selectedRoleName = $roleOptions[0]['name'] ?? 'admin';
        }

        if (Yii::$app->request->isPost && (string)Yii::$app->request->post('access_action', '') === 'save_access') {
            $selectedRoleName = strtolower(trim((string)Yii::$app->request->post('role_name', $selectedRoleName)));
            if (!in_array($selectedRoleName, $availableRoleNames, true)) {
                $selectedRoleName = $roleOptions[0]['name'] ?? 'admin';
            }
            try {
                $this->saveRoleAccessMatrix($selectedRoleName);
                Yii::$app->session->setFlash('success', 'Akses role berhasil disimpan.');
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Gagal menyimpan akses role: ' . $e->getMessage());
            }

            return $this->redirect([
                'permissions',
                'role_name' => $selectedRoleName,
            ]);
        }

        $catalog = $this->buildSimpleAccessCatalog();
        $permissionService = new \app\components\ProjectPermissionService();
        $roleAccessMap = $this->loadRoleAccessMap($selectedRoleName);
        $catalog = $this->decorateCatalogWithState($catalog, $selectedRoleName, $permissionService, $roleAccessMap);
        $preview = $this->buildAccessPreview($catalog);

        return $this->render('permissions', [
            'roles' => $roleOptions,
            'selectedRoleName' => $selectedRoleName,
            'catalog' => $catalog,
            'preview' => $preview,
        ]);
    }

    public function actionUsers()
    {
        $db = Yii::$app->db;

        if ($db->getTableSchema('roles', true) === null || $db->getTableSchema('users', true) === null) {
            Yii::$app->session->setFlash('error', 'Tabel user/role belum tersedia di database aplikasi ini.');
            return $this->redirect(['permissions']);
        }

        if (Yii::$app->request->isPost) {
            $action = (string)Yii::$app->request->post('permission_action', '');
            try {
                if ($action === 'assign_user_role') {
                    $this->assignUserRole();
                }
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Gagal menyimpan user role: ' . $e->getMessage());
            }

            return $this->redirect([
                'users',
                'role_name' => Yii::$app->request->post('assigned_role_name', Yii::$app->request->post('role_name', 'admin')),
            ]);
        }

        $roles = (new Query())->from('roles')->where(['not in', 'name', ['super_admin', 'superadmin']])->orderBy(['name' => SORT_ASC])->all($db);
        $users = (new Query())->from('users')->orderBy(['name' => SORT_ASC])->all($db);
        $selectedRoleName = strtolower(trim((string)Yii::$app->request->get('role_name', 'admin')));
        if (in_array($selectedRoleName, ['super_admin', 'superadmin'], true)) {
            $selectedRoleName = 'admin';
        }

        return $this->render('users', [
            'roles' => $roles,
            'users' => $users,
            'selectedRoleName' => $selectedRoleName,
        ]);
    }

    public function actionPermissionInspector()
    {
        return $this->redirect(['permissions']);
    }

    private function ensureRoleAccessTable(): void
    {
        $db = Yii::$app->db;
        if ($db->getTableSchema('role_access', true) !== null) {
            return;
        }

        $db->createCommand()->createTable('role_access', [
            'id' => $db->schema->createColumnSchemaBuilder('pk'),
            'role' => $db->schema->createColumnSchemaBuilder('string', 50)->notNull(),
            'access_type' => $db->schema->createColumnSchemaBuilder('string', 50)->notNull(),
            'access_key' => $db->schema->createColumnSchemaBuilder('string', 150)->notNull(),
            'can_access' => $db->schema->createColumnSchemaBuilder('tinyint', 1)->notNull()->defaultValue(1),
            'created_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ])->execute();

        $db->createCommand()->createIndex('idx-role_access-role', 'role_access', 'role')->execute();
        $db->createCommand()->createIndex('idx-role_access-type', 'role_access', 'access_type')->execute();
        $db->createCommand()->createIndex('idx-role_access-key', 'role_access', 'access_key')->execute();
        $db->createCommand()->createIndex('uq-role_access-role-type-key', 'role_access', ['role', 'access_type', 'access_key'], true)->execute();
    }

    /**
     * @return array<int, array{name:string,label:string}>
     */
    private function loadWorkspaceRoles(): array
    {
        $db = Yii::$app->db;
        $rows = (new Query())
            ->select(['role'])
            ->from('users')
            ->where(['and', ['not', ['role' => null]], ['<>', 'role', '']])
            ->groupBy(['role'])
            ->orderBy(['role' => SORT_ASC])
            ->all($db);

        $roles = [];
        foreach ($rows as $row) {
            $role = strtolower(trim((string)($row['role'] ?? '')));
            if ($role === '' || in_array($role, ['super_admin', 'superadmin'], true)) {
                continue;
            }

            $roles[] = [
                'name' => $role,
                'label' => ucfirst(str_replace(['_', '-'], ' ', $role)),
            ];
        }

        if (empty($roles)) {
            $roles[] = [
                'name' => 'admin',
                'label' => 'Admin',
            ];
        }

        return $roles;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildSimpleAccessCatalog(): array
    {
        $db = Yii::$app->db;
        $schema = $db->schema;

        $menuColumns = [];
        $menuSchema = $schema->getTableSchema('master_menu', true);
        if ($menuSchema !== null) {
            $menuColumns = array_keys($menuSchema->columns);
        }

        $pageColumns = [];
        $pageSchema = $schema->getTableSchema('master_page', true);
        if ($pageSchema !== null) {
            $pageColumns = array_keys($pageSchema->columns);
        }

        $menuSelect = array_values(array_intersect(['id', 'name', 'menu_key', 'route', 'type', 'is_active', 'sort_order'], $menuColumns));
        if (empty($menuSelect)) {
            $menuSelect = ['id', 'name'];
        }
        $menuOrder = [];
        if (in_array('sort_order', $menuColumns, true)) {
            $menuOrder['sort_order'] = SORT_ASC;
        }
        $menuOrder['name'] = SORT_ASC;

        $pageSelect = array_values(array_intersect(['id', 'name', 'title', 'slug', 'is_active', 'sort_order'], $pageColumns));
        if (empty($pageSelect)) {
            $pageSelect = ['id', 'name'];
        }
        $pageOrder = [];
        if (in_array('sort_order', $pageColumns, true)) {
            $pageOrder['sort_order'] = SORT_ASC;
        }
        if (in_array('name', $pageColumns, true)) {
            $pageOrder['name'] = SORT_ASC;
        } elseif (in_array('title', $pageColumns, true)) {
            $pageOrder['title'] = SORT_ASC;
        } else {
            $pageOrder['id'] = SORT_ASC;
        }

        $menuQuery = (new Query())->from('master_menu')->select($menuSelect);
        if (in_array('is_active', $menuColumns, true)) {
            $menuQuery->where(['<>', 'is_active', 0]);
        }
        $menus = $menuSchema !== null ? $menuQuery->orderBy($menuOrder)->all($db) : [];

        $pageQuery = (new Query())->from('master_page')->select($pageSelect);
        if (in_array('is_active', $pageColumns, true)) {
            $pageQuery->where(['<>', 'is_active', 0]);
        }
        $pages = $pageSchema !== null ? $pageQuery->orderBy($pageOrder)->all($db) : [];

        return [
            'menu' => array_values(array_map(function (array $menu): array {
                $name = trim((string)($menu['name'] ?? ''));
                $menuKey = $this->normalizeAccessKey((string)($menu['menu_key'] ?? ($name !== '' ? $name : ($menu['route'] ?? 'menu'))));
                return [
                    'type' => 'menu',
                    'key' => $menuKey,
                    'label' => $name !== '' ? $name : ucfirst(str_replace('-', ' ', $menuKey)),
                    'description' => 'Menu aplikasi yang tampil di sidebar.',
                ];
            }, $menus)),
            'page' => array_values(array_map(function (array $page): array {
                $label = trim((string)($page['name'] ?? $page['title'] ?? ''));
                $pageKey = $this->normalizeAccessKey((string)($page['slug'] ?? ($label !== '' ? $label : ($page['id'] ?? 'page'))));
                return [
                    'type' => 'page',
                    'key' => $pageKey,
                    'label' => $label !== '' ? $label : ucfirst(str_replace('-', ' ', $pageKey)),
                    'description' => 'Halaman yang boleh dibuka oleh role ini.',
                ];
            }, $pages)),
            'system_builder' => [
                [
                    'type' => 'system_builder',
                    'key' => 'master_menu',
                    'label' => 'Master Menu',
                    'description' => 'Kelola menu aplikasi.',
                ],
                [
                    'type' => 'system_builder',
                    'key' => 'master_page',
                    'label' => 'Master Page',
                    'description' => 'Kelola halaman aplikasi.',
                ],
                [
                    'type' => 'system_builder',
                    'key' => 'master_form',
                    'label' => 'Master Form',
                    'description' => 'Kelola form aplikasi.',
                ],
                [
                    'type' => 'system_builder',
                    'key' => 'master_table',
                    'label' => 'Master Table',
                    'description' => 'Kelola tabel aktif aplikasi.',
                ],
                [
                    'type' => 'system_builder',
                    'key' => 'workspace_settings',
                    'label' => 'Workspace Settings',
                    'description' => 'Pengaturan workspace aktif.',
                ],
            ],
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $catalog
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function decorateCatalogWithState(array $catalog, string $role, \app\components\ProjectPermissionService $permissionService, array $roleAccessMap): array
    {
        foreach ($catalog as $type => $items) {
            foreach ($items as $index => $item) {
                $key = (string)($item['key'] ?? '');
                $checked = isset($roleAccessMap[$type][$key])
                    ? (int)$roleAccessMap[$type][$key] === 1
                    : $this->resolveAccessStateForItem($permissionService, $type, $key);
                $catalog[$type][$index]['checked'] = $checked;
            }
        }

        return $catalog;
    }

    /**
     * @param array<string, array<string, int>> $roleAccessMap
     */
    private function resolveAccessStateForItem(\app\components\ProjectPermissionService $permissionService, string $type, string $key): bool
    {
        if ($type === 'menu') {
            return $permissionService->canAccessPermissionKeys([
                "menu.{$key}.view",
                "menu.{$key}.create",
                "menu.{$key}.edit",
                "menu.{$key}.delete",
            ]);
        }

        if ($type === 'page') {
            return $permissionService->canAccessPermissionKeys([
                "page.{$key}.view",
                "builder.page.{$key}.access",
            ]);
        }

        if ($type === 'system_builder') {
            $route = null;
            switch ($key) {
                case 'master_menu':
                    $route = 'master-menu/index';
                    break;
                case 'master_page':
                    $route = 'master-page/index';
                    break;
                case 'master_form':
                    $route = 'master-form/index';
                    break;
                case 'master_table':
                    $route = 'table-builder/index';
                    break;
                case 'workspace_settings':
                    $route = 'settings/workspace';
                    break;
            }

            if ($route !== null) {
                return $permissionService->canAccessRoute($route);
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function loadRoleAccessMap(string $role): array
    {
        $db = Yii::$app->db;
        $schema = $db->schema;
        if ($schema->getTableSchema('role_access', true) === null) {
            return [];
        }

        $rows = (new Query())
            ->from('role_access')
            ->where(['role' => $role])
            ->all($db);

        $map = [];
        foreach ($rows as $row) {
            $type = strtolower(trim((string)($row['access_type'] ?? '')));
            $key = strtolower(trim((string)($row['access_key'] ?? '')));
            if ($type === '' || $key === '') {
                continue;
            }
            $map[$type][$key] = (int)($row['can_access'] ?? 0);
        }

        return $map;
    }

    private function saveRoleAccessMatrix(string $role): void
    {
        $db = Yii::$app->db;
        $catalog = $this->buildSimpleAccessCatalog();
        $postedAccess = (array)Yii::$app->request->post('access', []);
        $now = date('Y-m-d H:i:s');

        $db->createCommand()->delete('role_access', ['role' => $role])->execute();

        foreach ($catalog as $type => $items) {
            foreach ($items as $item) {
                $key = strtolower(trim((string)($item['key'] ?? '')));
                if ($key === '') {
                    continue;
                }

                $canAccess = !empty($postedAccess[$type][$key]);
                $db->createCommand()->insert('role_access', [
                    'role' => $role,
                    'access_type' => $type,
                    'access_key' => $key,
                    'can_access' => $canAccess ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }
        }
    }

    private function buildAccessPreview(array $catalog): array
    {
        $preview = [
            'menu' => [],
            'page' => [],
            'system_builder' => [],
            'hidden' => [],
        ];

        foreach ($catalog['menu'] ?? [] as $item) {
            if (!empty($item['checked'])) {
                $preview['menu'][] = $item['label'];
            } else {
                $preview['hidden'][] = $item['label'];
            }
        }

        foreach ($catalog['page'] ?? [] as $item) {
            if (!empty($item['checked'])) {
                $preview['page'][] = $item['label'];
            }
        }

        foreach ($catalog['system_builder'] ?? [] as $item) {
            if (!empty($item['checked'])) {
                $preview['system_builder'][] = $item['label'];
            }
        }

        return $preview;
    }

    private function normalizeAccessKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[?#].*$/', '', $value) ?? $value;
        $value = preg_replace('/[\/\s]+/', '-', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9\-_]+/', '-', $value) ?? $value;
        $value = preg_replace('/-+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value !== '' ? $value : 'item';
    }

    public function actionUploadLogo()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->loadSettings();
        
        $uploadedFile = UploadedFile::getInstanceByName('workspace_logo_image');
        
        if (!$uploadedFile) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower($uploadedFile->getExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WEBP'];
        }
        
        $maxSize = 2 * 1024 * 1024;
        if ($uploadedFile->size > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size: 2MB'];
        }
        
        $storage = new WorkspaceMediaStorage();
        $relativeDir = $this->workspaceMediaRelativeDir('workspace-logo');
        $uploadResult = $storage->storeUploadedFile($uploadedFile, 'logo', $relativeDir);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }

        $oldLogo = $model->workspace_logo_image;
        if ($oldLogo) {
            $this->deleteWorkspaceMediaFile($oldLogo);
        }

        $model->workspace_logo_image = (string)($uploadResult['relative_path'] ?? '');
        if (!$model->save()) {
            $storage->delete((string)($uploadResult['relative_path'] ?? ''));
            return ['success' => false, 'message' => 'Logo tersimpan di disk, tetapi gagal disimpan ke database.'];
        }

        return [
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'logoUrl' => $model->getWorkspaceLogoAsset()['url'],
            'logoFile' => (string)($uploadResult['relative_path'] ?? ''),
        ];
    }
    
    public function actionRemoveLogo()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->loadSettings();
        
        if ($model->workspace_logo_image) {
            $this->deleteWorkspaceMediaFile((string)$model->workspace_logo_image);
            
            $model->workspace_logo_image = null;
            if (!$model->save()) {
                return ['success' => false, 'message' => 'Gagal menyimpan perubahan logo ke database'];
            }
            
            return ['success' => true, 'message' => 'Logo removed successfully'];
        }
        
        return ['success' => false, 'message' => 'No logo to remove'];
    }
    
    private function loadSettings()
    {
        $model = new \app\models\WorkspaceSettings();
        $model->loadFromDatabase();
        return $model;
    }

    /**
     * @return array{success:bool,message:string,login_background_image?:string}
     */
    private function storeLoginBackgroundUpload(\app\models\WorkspaceSettings $model, UploadedFile $uploadedFile): array
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'];
        $extension = strtolower($uploadedFile->getExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'message' => 'Format background login tidak didukung. Gunakan JPG, PNG, WEBP, GIF, MP4, WEBM, atau OGG.'];
        }

        $maxSize = 20 * 1024 * 1024;
        if ($uploadedFile->size > $maxSize) {
            return ['success' => false, 'message' => 'File terlalu besar. Maksimal 20MB.'];
        }

        $storage = new WorkspaceMediaStorage();
        $relativeDir = $this->workspaceMediaRelativeDir('login-background');
        $uploadResult = $storage->storeUploadedFile($uploadedFile, 'login_bg', $relativeDir);
        if (!$uploadResult['success']) {
            return ['success' => false, 'message' => (string)($uploadResult['message'] ?? 'Gagal menyimpan file background login.')];
        }

        $oldValue = trim((string)$model->login_background_image);
        if ($oldValue !== '' && !preg_match('#^https?://#i', $oldValue)) {
            $this->deleteWorkspaceMediaFile($oldValue);
        }

        $model->login_background_image = (string)($uploadResult['relative_path'] ?? '');

        return [
            'success' => true,
            'message' => 'Background login berhasil diunggah.',
            'login_background_image' => (string)($uploadResult['relative_path'] ?? ''),
        ];
    }

    private function workspaceMediaRelativeDir(string $category = 'project-assets'): string
    {
        $category = preg_replace('#[^a-zA-Z0-9_\-]#', '', trim($category)) ?: 'project-assets';
        $projectId = null;
        if (class_exists('\app\components\ActiveProjectContext') && class_exists('\app\components\ProjectSchema') && \app\components\ProjectSchema::supportsProjectContext()) {
            $projectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
        }

        return $category . '/' . ($projectId !== null && $projectId > 0 ? ((int)$projectId . '/') : 'global/');
    }

    private function deleteWorkspaceMediaFile(string $value): void
    {
        (new WorkspaceMediaStorage())->delete($value);
    }

    private function isCommanderOnlyRole(string $roleName): bool
    {
        return in_array(strtolower(trim($roleName)), ['super_admin', 'superadmin'], true);
    }

    private function createRole(): void
    {
        $db = Yii::$app->db;
        $roleName = strtolower(trim((string)Yii::$app->request->post('role_name', '')));
        $roleDescription = trim((string)Yii::$app->request->post('role_description', ''));

        if ($roleName === '') {
            Yii::$app->session->setFlash('error', 'Nama role tidak boleh kosong.');
            return;
        }

        if ($this->isCommanderOnlyRole($roleName)) {
            Yii::$app->session->setFlash('warning', 'Role superadmin hanya boleh digunakan di Commander.');
            return;
        }

        $exists = (new Query())->from('roles')->where(['name' => $roleName])->exists($db);
        if ($exists) {
            Yii::$app->session->setFlash('warning', "Role '{$roleName}' sudah ada.");
            return;
        }

        $db->createCommand()->insert('roles', [
            'name' => $roleName,
            'description' => $roleDescription !== '' ? $roleDescription : null,
            'is_system' => 0,
        ])->execute();

        Yii::$app->session->setFlash('success', "Role '{$roleName}' berhasil dibuat.");
    }

    private function updateRole(): void
    {
        $db = Yii::$app->db;
        $oldRoleName = strtolower(trim((string)Yii::$app->request->post('old_role_name', '')));
        $roleName = strtolower(trim((string)Yii::$app->request->post('role_name', '')));
        $roleDescription = trim((string)Yii::$app->request->post('role_description', ''));

        if ($oldRoleName === '' || $roleName === '') {
            Yii::$app->session->setFlash('error', 'Role tidak valid.');
            return;
        }

        $role = (new Query())->from('roles')->where(['name' => $oldRoleName])->one($db);
        if (!$role) {
            Yii::$app->session->setFlash('error', 'Role tidak ditemukan.');
            return;
        }

        if ($this->isCommanderOnlyRole($oldRoleName) || $this->isCommanderOnlyRole($roleName)) {
            Yii::$app->session->setFlash('warning', 'Role superadmin tidak bisa diubah dari workspace aplikasi.');
            return;
        }

        if ((int)($role['is_system'] ?? 0) === 1 && $roleName !== $oldRoleName) {
            Yii::$app->session->setFlash('warning', 'Role sistem tidak boleh diganti namanya.');
            return;
        }

        $duplicate = (new Query())->from('roles')->where(['name' => $roleName])->andWhere(['!=', 'name', $oldRoleName])->exists($db);
        if ($duplicate) {
            Yii::$app->session->setFlash('error', "Role '{$roleName}' sudah dipakai.");
            return;
        }

        $db->createCommand()->update('roles', [
            'name' => $roleName,
            'description' => $roleDescription !== '' ? $roleDescription : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['name' => $oldRoleName])->execute();

        if ($roleName !== $oldRoleName) {
            $db->createCommand()->update('users', ['role' => $roleName], ['role' => $oldRoleName])->execute();
        }

        Yii::$app->session->setFlash('success', "Role '{$oldRoleName}' berhasil diperbarui.");
    }

    private function deleteRole(): void
    {
        $db = Yii::$app->db;
        $roleName = strtolower(trim((string)Yii::$app->request->post('role_name', '')));

        if ($roleName === '') {
            Yii::$app->session->setFlash('error', 'Role tidak valid.');
            return;
        }

        $role = (new Query())->from('roles')->where(['name' => $roleName])->one($db);
        if (!$role) {
            Yii::$app->session->setFlash('error', 'Role tidak ditemukan.');
            return;
        }

        if ((int)($role['is_system'] ?? 0) === 1 || in_array($roleName, ['admin', 'visitor', 'super_admin', 'superadmin'], true)) {
            Yii::$app->session->setFlash('warning', 'Role sistem tidak boleh dihapus.');
            return;
        }

        $fallbackRoleName = (new Query())->from('roles')->where(['name' => 'visitor'])->exists($db) ? 'visitor' : 'admin';
        $db->createCommand()->update('users', ['role' => $fallbackRoleName], ['role' => $roleName])->execute();

        $roleId = (int)$role['id'];
        $db->createCommand()->delete('role_permissions', ['role_id' => $roleId])->execute();
        $db->createCommand()->delete('roles', ['id' => $roleId])->execute();

        Yii::$app->session->setFlash('success', "Role '{$roleName}' berhasil dihapus. User terkait dipindahkan ke '{$fallbackRoleName}'.");
    }

    private function assignUserRole(): void
    {
        $db = Yii::$app->db;
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $roleName = strtolower(trim((string)Yii::$app->request->post('assigned_role_name', '')));

        if ($userId <= 0 || $roleName === '') {
            Yii::$app->session->setFlash('error', 'User atau role tidak valid.');
            return;
        }

        if ($this->isCommanderOnlyRole($roleName)) {
            Yii::$app->session->setFlash('warning', 'Role superadmin tidak bisa di-assign dari workspace aplikasi.');
            return;
        }

        $roleExists = (new Query())->from('roles')->where(['name' => $roleName])->exists($db);
        if (!$roleExists) {
            Yii::$app->session->setFlash('error', 'Role tujuan tidak ditemukan.');
            return;
        }

        $updated = $db->createCommand()->update('users', [
            'role' => $roleName,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $userId])->execute();

        if ($updated > 0) {
            Yii::$app->session->setFlash('success', 'Role user berhasil diubah.');
        } else {
            Yii::$app->session->setFlash('error', 'User tidak ditemukan.');
        }
    }

    private function savePermissionMatrix(): void
    {
        $db = Yii::$app->db;
        $roleName = strtolower(trim((string)Yii::$app->request->post('role_name', '')));
        $permissionKeys = array_values(array_unique(array_filter(array_map('trim', (array)Yii::$app->request->post('permission_keys', [])))));

        if ($roleName === '') {
            Yii::$app->session->setFlash('error', 'Role tidak valid.');
            return;
        }

        if ($this->isCommanderOnlyRole($roleName)) {
            Yii::$app->session->setFlash('warning', 'Permission matrix superadmin dikelola di Commander, bukan workspace aplikasi.');
            return;
        }

        $role = (new Query())->from('roles')->where(['name' => $roleName])->one($db);
        if (!$role) {
            Yii::$app->session->setFlash('error', 'Role tidak ditemukan.');
            return;
        }

        $permissionIds = empty($permissionKeys)
            ? []
            : (new Query())->select('id')->from('permissions')->where(['permission_key' => $permissionKeys])->column($db);

        $db->createCommand()->delete('role_permissions', ['role_id' => (int)$role['id']])->execute();
        foreach ($permissionIds as $permissionId) {
            $db->createCommand()->insert('role_permissions', [
                'role_id' => (int)$role['id'],
                'permission_id' => (int)$permissionId,
            ])->execute();
        }

        Yii::$app->session->setFlash('success', "Permission role '{$roleName}' berhasil disimpan.");
    }

    private function createPermission(): void
    {
        $db = Yii::$app->db;
        $permissionKey = strtolower(trim((string)Yii::$app->request->post('permission_key', '')));
        $permissionLabel = trim((string)Yii::$app->request->post('permission_label', ''));
        $permissionType = strtolower(trim((string)Yii::$app->request->post('permission_type', 'feature')));

        if ($permissionKey === '' || $permissionLabel === '') {
            Yii::$app->session->setFlash('error', 'Permission key dan label wajib diisi.');
            return;
        }

        $exists = (new Query())->from('permissions')->where(['permission_key' => $permissionKey])->exists($db);
        if ($exists) {
            Yii::$app->session->setFlash('warning', "Permission '{$permissionKey}' sudah ada.");
            return;
        }

        $db->createCommand()->insert('permissions', [
            'permission_key' => $permissionKey,
            'label' => $permissionLabel,
            'permission_type' => in_array($permissionType, ['menu', 'page', 'form', 'route', 'builder', 'feature'], true) ? $permissionType : 'feature',
            'description' => null,
        ])->execute();

        Yii::$app->session->setFlash('success', "Permission '{$permissionKey}' berhasil dibuat.");
    }

    private function resyncWorkspacePermissions(): void
    {
        $registry = new ProjectPermissionRegistry();
        $count = 0;

        foreach (\app\models\MasterMenu::find()->all() as $menu) {
            $count += count($registry->syncMenuPermissions($menu));
        }
        foreach (\app\models\MasterPage::find()->all() as $page) {
            $count += count($registry->syncPagePermissions($page));
        }
        foreach (\app\models\MasterForm::findScoped()->all() as $form) {
            $count += count($registry->syncFormPermissions($form));
        }

        Yii::$app->session->setFlash('success', "Permission workspace berhasil disinkronkan ({$count} item).");
    }

    private function ensureWorkspaceModulePermissions(): void
    {
        $registry = new ProjectPermissionRegistry();
        foreach ([
            'master-menu/index' => 'Master Menu',
            'master-page/index' => 'Master Page',
            'master-form/index' => 'Master Form',
            'table-builder/index' => 'Master Table',
            'settings/workspace' => 'Workspace Settings',
            'workspace-settings/index' => 'Workspace Settings',
        ] as $route => $label) {
            $registry->syncRoutePermissions($route, $label);
        }
    }

    private function buildPermissionUiCatalog(array $permissions, array $masterMenus, array $masterPages): array
    {
        $permissionLookup = [];
        foreach ($permissions as $permission) {
            $key = strtolower((string)($permission['permission_key'] ?? ''));
            if ($key !== '') {
                $permissionLookup[$key] = $permission;
            }
        }

        $normalize = static function (string $value): string {
            $value = strtolower(trim($value));
            $value = preg_replace('/\s+menu$/', '', $value) ?? $value;
            $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
            $value = preg_replace('/-+/', '-', $value) ?? $value;
            return trim($value, '-');
        };

        $menuItems = [];
        foreach ($masterMenus as $menu) {
            $name = trim((string)($menu['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $base = $normalize((string)($menu['menu_key'] ?? $name));
            if ($base === '') {
                continue;
            }

            $keys = [];
            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $permissionKey = "menu.{$base}.{$action}";
                if (isset($permissionLookup[$permissionKey])) {
                    $keys[] = $permissionKey;
                }
            }

            $menuItems[] = [
                'label' => $name,
                'keys' => $keys,
                'permission_count' => count($keys),
            ];
        }

        $pageItems = [];
        foreach ($masterPages as $page) {
            $title = trim((string)($page['name'] ?? ''));
            if ($title === '') {
                continue;
            }
            $base = $normalize((string)($page['slug'] ?? $title));
            if ($base === '') {
                continue;
            }

            $keys = [];
            foreach (["page.{$base}.view", "builder.page.{$base}.access"] as $permissionKey) {
                if (isset($permissionLookup[$permissionKey])) {
                    $keys[] = $permissionKey;
                }
            }

            $pageItems[] = [
                'label' => $title,
                'keys' => $keys,
                'permission_count' => count($keys),
            ];
        }

        $systemBuilderItems = [];
        $systemBuilderMap = [
            'Master Menu' => ['master-menu/index'],
            'Master Page' => ['master-page/index'],
            'Master Form' => ['master-form/index'],
            'Master Table' => ['table-builder/index'],
            'Workspace Settings' => ['settings/workspace', 'workspace-settings/index'],
        ];
        foreach ($systemBuilderMap as $label => $routes) {
            $keys = [];
            foreach ($routes as $route) {
                $permissionKey = 'route.' . $this->normalizeRouteKey($route) . '.access';
                if (isset($permissionLookup[$permissionKey])) {
                    $keys[] = $permissionKey;
                }
            }

            $systemBuilderItems[] = [
                'label' => $label,
                'keys' => array_values(array_unique($keys)),
                'permission_count' => count(array_unique($keys)),
            ];
        }

        return [
            'menu' => $menuItems,
            'page' => $pageItems,
            'builder' => $systemBuilderItems,
        ];
    }

    private function normalizeRouteKey(string $route): string
    {
        $route = trim($route);
        $route = preg_replace('/[?#].*$/', '', $route);
        $route = trim($route, '/');
        if ($route === '') {
            return '';
        }

        $segments = array_filter(array_map(function ($segment) {
            $segment = strtolower(trim((string)$segment));
            $segment = preg_replace('/[^a-z0-9]+/', '-', $segment) ?? $segment;
            $segment = preg_replace('/-+/', '-', $segment) ?? $segment;
            return trim($segment, '-');
        }, explode('/', $route)));

        return implode('.', $segments);
    }

    /**
     * @param array<int, array<string, mixed>> $permissions
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupPermissions(array $permissions): array
    {
        $groups = [];
        foreach ($permissions as $permission) {
            $type = strtolower((string)($permission['permission_type'] ?? 'feature'));
            $groups[$type][] = $permission;
        }

        return $groups;
    }

    /**
     * @param array<int, array<string, mixed>> $permissions
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupInspectorPermissions(array $permissions): array
    {
        $groups = [
            'menu' => [],
            'page' => [],
            'form' => [],
            'route' => [],
            'builder' => [],
            'feature' => [],
        ];

        foreach ($permissions as $permission) {
            $key = strtolower((string)($permission['permission_key'] ?? ''));
            if (strpos($key, 'menu.') === 0) {
                $groups['menu'][] = $permission;
            } elseif (strpos($key, 'page.') === 0 || strpos($key, 'component.page.') === 0) {
                $groups['page'][] = $permission;
            } elseif (strpos($key, 'form.') === 0 || strpos($key, 'component.form.') === 0) {
                $groups['form'][] = $permission;
            } elseif (strpos($key, 'route.') === 0) {
                $groups['route'][] = $permission;
            } elseif (strpos($key, 'builder.') === 0) {
                $groups['builder'][] = $permission;
            } else {
                $groups['feature'][] = $permission;
            }
        }

        return $groups;
    }
}
