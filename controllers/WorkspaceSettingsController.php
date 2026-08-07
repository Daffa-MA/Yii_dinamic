<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\db\Query;
use app\components\WorkspaceMediaStorage;
use app\models\ProjectUser;
use app\services\UserMappingService;

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
                    'upload-favicon' => ['POST'],
                    'remove-favicon' => ['POST'],
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

        return $this->render('index', array_merge(
            ['model' => $model],
            $this->buildAuthenticationViewData()
        ));
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
        
        return $this->render('index', array_merge(
            ['model' => $model],
            $this->buildAuthenticationViewData()
        ));
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

        \app\components\DatabaseSchemaInitializer::ensureProjectAuthColumns($db);

        if ($db->getTableSchema('users', true) === null) {
            Yii::$app->session->setFlash('error', 'Tabel users belum tersedia di database aplikasi ini.');
            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isPost) {
            $action = (string)Yii::$app->request->post('permission_action', '');
            try {
                if ($action === 'save_user') {
                    $savedUserId = $this->saveUserAccount();
                    if ($savedUserId > 0) {
                        $this->invalidateUserStatsCache($db);
                        return $this->redirect(['users', 'user_id' => $savedUserId]);
                    }
                } elseif ($action === 'bulk_user_action') {
                    $this->bulkUserAction();
                    $this->invalidateUserStatsCache($db);
                    return $this->redirect(['users'] + $this->usersRedirectParams());
                }
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Gagal menyimpan akun: ' . $e->getMessage());
            }

            return $this->redirect(['users'] + $this->usersRedirectParams());
        }

        $roles = $this->loadWorkspaceRoles();

        // ---- server-side search / filter / sort / pagination ----
        $filters = $this->usersFilterState();
        $pageSize = 30;

        $countQuery = (new Query())->from('users');
        $countQuery = $this->applyUsersConditions($countQuery, $filters, $db);
        $totalUsers = (int)$countQuery->count('*', $db);

        $query = (new Query())->from('users');
        $query = $this->applyUsersConditions($query, $filters, $db);
        $query->orderBy($this->usersSortOrder($filters['sort']));

        $pageCount = max(1, (int)ceil($totalUsers / $pageSize));
        $currentPage = min(max(1, (int)$filters['page']), $pageCount);
        $offset = ($currentPage - 1) * $pageSize;
        $users = $query->offset($offset)->limit($pageSize)->all($db);

        // ---- stats (cached global aggregates; independent of filters) ----
        $userStats = $this->loadUserStats($db);

        // Distinct data types used across accounts (for the Entity filter).
        $entityRows = (new Query())
            ->select(['identity_table'])
            ->from('users')
            ->where(['and', ['not', ['identity_table' => null]], ['<>', 'identity_table', '']])
            ->distinct()
            ->all($db);
        $entityFilterOptions = [];
        $identityEntities = (new UserMappingService())->getEntities();
        $entityLabelByName = [];
        foreach ($identityEntities as $entity) {
            $entityLabelByName[(string)$entity['name']] = (string)($entity['label'] ?? $entity['name']);
        }
        foreach ($entityRows as $row) {
            $name = strtolower(trim((string)($row['identity_table'] ?? '')));
            if ($name === '') {
                continue;
            }
            $entityFilterOptions[$name] = $entityLabelByName[$name] ?? $name;
        }

        $isNew = (string)Yii::$app->request->get('new', '') === '1';
        $selectedUserId = (int)Yii::$app->request->get('user_id', 0);

        $selectedUser = null;
        if ($isNew) {
            $selectedUserId = 0;
        } elseif ($selectedUserId > 0) {
            foreach ($users as $user) {
                if ((int)$user['id'] === $selectedUserId) {
                    $selectedUser = $user;
                    break;
                }
            }
            if ($selectedUser === null) {
                $selectedUserId = 0;
            }
        }

        if ($selectedUserId === 0 && !empty($users)) {
            $selectedUser = $users[0];
            $selectedUserId = (int)$selectedUser['id'];
        }

        $mappingService = new UserMappingService();
        $projectId = $mappingService->getActiveProjectId();

        $selectedMappingInfo = null;
        if ($selectedUser !== null) {
            $selectedMappingInfo = $mappingService->getMappingDisplayLabel($projectId, (int)$selectedUser['id']);
        }

        return $this->render('users', [
            'users' => $users,
            'roles' => $roles,
            'selectedUserId' => $selectedUserId,
            'selectedUser' => $selectedUser,
            'identityEntities' => $identityEntities,
            'entityFilterOptions' => $entityFilterOptions,
            'selectedMappingInfo' => $selectedMappingInfo,
            'userStats' => $userStats,
            'filters' => $filters,
            'pagination' => [
                'total' => $totalUsers,
                'page' => $currentPage,
                'page_size' => $pageSize,
                'pages' => $pageCount,
                'has_prev' => $currentPage > 1,
                'has_next' => $currentPage < $pageCount,
            ],
        ]);
    }

    /**
     * Normalize the list filter state from query params.
     *
     * @return array{q: string, role: string, status: string, mapping: string, entity: string, sort: string, page: int}
     */
    private function usersFilterState(): array
    {
        $mappingValues = ['', 'connected', 'pending', 'attention'];
        $sortValues = ['name_asc', 'name_desc', 'created_desc', 'created_asc', 'updated_desc'];
        $mapping = strtolower(trim((string)Yii::$app->request->get('mapping', '')));
        $sort = strtolower(trim((string)Yii::$app->request->get('sort', 'created_desc')));
        if (!in_array($mapping, $mappingValues, true)) {
            $mapping = '';
        }
        if (!in_array($sort, $sortValues, true)) {
            $sort = 'created_desc';
        }
        return [
            'q' => mb_substr(trim((string)Yii::$app->request->get('q', '')), 0, 120),
            'role' => strtolower(trim((string)Yii::$app->request->get('role', ''))),
            'status' => (string)Yii::$app->request->get('status', ''),
            'mapping' => $mapping,
            'entity' => strtolower(trim((string)Yii::$app->request->get('entity', ''))),
            'sort' => $sort,
            'page' => max(1, (int)Yii::$app->request->get('page', 1)),
        ];
    }

    private function applyUsersConditions(Query $query, array $filters, \yii\db\Connection $db): Query
    {
        if ($filters['q'] !== '') {
            if (!$this->applyUserFulltextSearch($query, $db, $filters['q'])) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
                $query->andWhere([
                    'or',
                    ['like', 'name', $like],
                    ['like', 'username', $like],
                    ['like', 'email', $like],
                    ['like', 'role', $like],
                    ['like', 'identity_table', $like],
                    ['like', 'identity_record_id', $like],
                ]);
            }
        }
        if ($filters['role'] !== '') {
            $query->andWhere(['role' => $filters['role']]);
        }
        if ($filters['status'] === '1') {
            $query->andWhere(['status' => 1]);
        } elseif ($filters['status'] === '0') {
            $query->andWhere(['or', ['status' => 0], ['status' => null]]);
        }
        if ($filters['mapping'] === 'connected') {
            $query->andWhere([
                'and',
                ['not', ['identity_table' => null]],
                ['<>', 'identity_table', ''],
                ['not', ['identity_record_id' => null]],
                ['<>', 'identity_record_id', ''],
            ]);
        } elseif ($filters['mapping'] === 'pending') {
            $query->andWhere([
                'or',
                ['identity_table' => null],
                ['identity_table' => ''],
                ['identity_record_id' => null],
                ['identity_record_id' => ''],
            ]);
        } elseif ($filters['mapping'] === 'attention') {
            $query->andWhere(['status' => 0]);
        }
        if ($filters['entity'] !== '') {
            $query->andWhere(['identity_table' => $filters['entity']]);
        }
        return $query;
    }

    private function usersSortOrder(string $sort): array
    {
        switch ($sort) {
            case 'name_asc':
                return ['name' => SORT_ASC, 'id' => SORT_ASC];
            case 'name_desc':
                return ['name' => SORT_DESC, 'id' => SORT_DESC];
            case 'created_asc':
                return ['created_at' => SORT_ASC, 'id' => SORT_ASC];
            case 'updated_desc':
                return ['updated_at' => SORT_DESC, 'id' => SORT_DESC];
            case 'created_desc':
            default:
                return ['created_at' => SORT_DESC, 'id' => SORT_DESC];
        }
    }

    /**
     * Global user counts (independent of the current filter/page) used by the
     * summary cards. Cached briefly so browsing/paginating does not re-run four
     * COUNT queries. Invalidated on user mutations in this controller.
     *
     * @return array{total:int,active:int,inactive:int,needs_attention:int,connected:int,pending:int}
     */
    private function loadUserStats(\yii\db\Connection $db): array
    {
        $key = 'ws-users-stats-' . md5((string)$db->dsn);

        $cache = null;
        if (Yii::$app->has('cache')) {
            $cache = Yii::$app->cache;
            $cached = $cache->get($key);
            if (is_array($cached)) {
                return $cached + [
                    'total' => 0, 'active' => 0, 'inactive' => 0,
                    'needs_attention' => 0, 'connected' => 0, 'pending' => 0,
                ];
            }
        }

        $stats = [
            'total' => (int)(new Query())->from('users')->count('*', $db),
            'active' => (int)(new Query())->from('users')->where(['status' => 1])->count('*', $db),
            'inactive' => (int)(new Query())
                ->from('users')
                ->where(['or', ['status' => 0], ['status' => null]])
                ->count('*', $db),
            'connected' => (int)(new Query())
                ->from('users')
                ->where([
                    'and',
                    ['not', ['identity_table' => null]],
                    ['<>', 'identity_table', ''],
                    ['not', ['identity_record_id' => null]],
                    ['<>', 'identity_record_id', ''],
                ])
                ->count('*', $db),
        ];
        $stats['needs_attention'] = $stats['inactive'];
        $stats['pending'] = max(0, $stats['total'] - $stats['connected']);

        if ($cache !== null) {
            $cache->set($key, $stats, 60);
        }

        return $stats;
    }

    private function invalidateUserStatsCache(\yii\db\Connection $db): void
    {
        if (Yii::$app->has('cache')) {
            Yii::$app->cache->delete('ws-users-stats-' . md5((string)$db->dsn));
        }
    }

    /**
     * Fast index-backed account search via a FULLTEXT MATCH on
     * (name, username, email). Returns false (→ LIKE fallback) when the table
     * has no FULLTEXT index yet or the query is too short for FULLTEXT (tokens
     * shorter than the engine's minimum token length match nothing there).
     */
    private function applyUserFulltextSearch(Query $query, \yii\db\Connection $db, string $term): bool
    {
        $tokens = preg_split('/\s+/', trim($term)) ?: [];
        $tokens = array_values(array_filter($tokens, fn($t) => $t !== ''));
        if (empty($tokens)) {
            return false;
        }

        $phrases = [];
        foreach ($tokens as $token) {
            $safe = preg_replace('/[^a-z0-9_.-]/i', '', (string)$token);
            $safe = trim($safe, '_.-');
            if (strlen($safe) < 3) {
                return false;
            }
            $phrases[] = '+' . $safe . '*';
        }
        $match = implode(' ', $phrases);
        if ($match === '') {
            return false;
        }

        if (!$this->userTableHasFulltextIndex($db)) {
            return false;
        }

        $query->andWhere(new \yii\db\Expression(
            'MATCH(name, username, email) AGAINST (:q IN BOOLEAN MODE)',
            [':q' => $match]
        ));
        return true;
    }

    private function userTableHasFulltextIndex(\yii\db\Connection $db): bool
    {
        try {
            $rows = $db->createCommand('SHOW INDEX FROM `users`')->queryAll();
            foreach ($rows as $row) {
                if (strtolower(trim((string)($row['Index_type'] ?? ''))) === 'fulltext') {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    /**
     * Wizard entry point: Generate Accounts. Renders the step-by-step flow with
     * the source tables, username-column candidates and runtime role options
     * resolved from metadata / users.role (all framework metadata, never
     * hardcoded to a module).
     */
    public function actionGenerateAccounts()
    {
        $service = new \app\services\GenerateAccountsService();
        $projectId = $service->getActiveProjectId();

        $tables = $service->getSourceTables($projectId);
        $columns = [];
        $selectedTable = strtolower(trim((string)Yii::$app->request->get('table', '')));
        foreach ($tables as $table) {
            if ((string)$table['name'] === $selectedTable) {
                $columns = $service->getColumnsForTable($selectedTable);
                break;
            }
        }

        return $this->render('generate-accounts', [
            'service' => $service,
            'tables' => $tables,
            'columns' => $columns,
            'selectedTable' => $selectedTable,
            'roles' => $service->getRoles(),
            'role' => (string)Yii::$app->request->get('role', ''),
            'usernameColumn' => (string)Yii::$app->request->get('column', ''),
            'emailDomain' => $service->getEmailDomain(),
        ]);
    }

    /**
     * JSON: metadata-driven username columns for a chosen source table.
     */
    public function actionGenerateAccountColumns()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $table = strtolower(trim((string)Yii::$app->request->get('table', '')));
        if ($table === '') {
            return ['success' => false, 'message' => 'Tabel tidak valid.', 'columns' => []];
        }

        $service = new \app\services\GenerateAccountsService();
        return ['success' => true, 'columns' => $service->getColumnsForTable($table)];
    }

    /**
     * JSON: dry-run preview (counts) before generating.
     */
    public function actionGenerateAccountsPreview()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Metode tidak valid.'];
        }

        $table = strtolower(trim((string)Yii::$app->request->post('table', '')));
        $column = strtolower(trim((string)Yii::$app->request->post('username_column', '')));

        $service = new \app\services\GenerateAccountsService();
        return $service->preview($table, $column);
    }

    /**
     * JSON: execute the mass account generation inside batched transactions.
     */
    public function actionGenerateAccountsRun()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Formate tidak valid.'];
        }

        $table = strtolower(trim((string)Yii::$app->request->post('table', '')));
        $column = strtolower(trim((string)Yii::$app->request->post('username_column', '')));
        $role = strtolower(trim((string)Yii::$app->request->post('role', 'admin')));
        $passwordMode = (string)Yii::$app->request->post('password_mode', 'fixed') === 'random' ? 'random' : 'fixed';
        $fixedPassword = (string)Yii::$app->request->post('password', '123456');
        $emailDomain = strtolower(trim((string)Yii::$app->request->post('email_domain', '')));

        $service = new \app\services\GenerateAccountsService();
        return $service->generate($table, $column, $role, $passwordMode, $fixedPassword, $emailDomain);
    }

    /**
     * Create or update an account. Every account attribute is handled in one
     * flow: name, username, email, password, role, status, and (optional)
     * Identity Mapping. Role is written straight to users.role; the mapping is
     * delegated to UserMappingService so the runtime contract is preserved.
     *
     * @return int The saved user id (0 = nothing saved).
     */
    /**
     * POST: perform a bulk action over the selected account ids. Operations:
     * activate, disable, role (bulk_role), reset_password (bulk_password or
     * random), delete. Each selected id is processed independently so a bad row
     * never aborts the rest.
     */
    private function bulkUserAction(): void
    {
        $ids = Yii::$app->request->post('user_ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        if (empty($ids)) {
            Yii::$app->session->setFlash('warning', 'Tidak ada akun yang dipilih.');
            return;
        }

        $operation = (string)Yii::$app->request->post('bulk_operation', '');
        if ($operation === 'activate') {
            $n = Yii::$app->db->createCommand()->update('users', ['status' => 1], ['id' => $ids])->execute();
            Yii::$app->session->setFlash('success', $n . ' akun diaktifkan.');
            return;
        }
        if ($operation === 'disable') {
            $n = Yii::$app->db->createCommand()->update('users', ['status' => 0], ['id' => $ids])->execute();
            Yii::$app->session->setFlash('success', $n . ' akun dinonaktifkan.');
            return;
        }
        if ($operation === 'role') {
            $role = strtolower(trim((string)Yii::$app->request->post('bulk_role', '')));
            if ($role === '' || $this->isCommanderOnlyRole($role)) {
                Yii::$app->session->setFlash('warning', 'Role tidak valid untuk operasi massal.');
                return;
            }
            $n = Yii::$app->db->createCommand()->update('users', ['role' => $role], ['id' => $ids])->execute();
            Yii::$app->session->setFlash('success', $n . ' akun diubah role menjadi ' . $role . '.');
            return;
        }
        if ($operation === 'reset_password') {
            $password = (string)Yii::$app->request->post('bulk_password', '');
            $useRandom = (string)Yii::$app->request->post('bulk_random_password', '') === '1';
            $changed = 0;
            foreach ($ids as $id) {
                $user = ProjectUser::findOne($id);
                if ($user === null) {
                    continue;
                }
                $pwd = $useRandom ? Yii::$app->security->generateRandomString(10) : ($password !== '' ? $password : '123456');
                $user->setPassword($pwd);
                $user->must_change_password = 1;
                $user->save(false);
                $changed++;
            }
            Yii::$app->session->setFlash('success', $changed . ' akun telah diatur ulang kata sandinya (wajib ganti saat login).');
            return;
        }
        if ($operation === 'delete') {
            $deleted = 0;
            foreach ($ids as $id) {
                $user = ProjectUser::findOne($id);
                if ($user !== null) {
                    $user->delete();
                    $deleted++;
                }
            }
            Yii::$app->session->setFlash('success', $deleted . ' akun dihapus.');
            return;
        }

        Yii::$app->session->setFlash('warning', 'Operasi massal tidak dikenal.');
    }

    /**
     * Query params to keep the list view stable after a POST redirect.
     */
    private function usersRedirectParams(): array
    {
        $params = [];
        foreach (['q', 'role', 'status', 'mapping', 'entity', 'sort', 'page'] as $key) {
            $value = Yii::$app->request->post($key, Yii::$app->request->get($key, ''));
            if ($value !== '' && $value !== null) {
                $params[$key] = (string)$value;
            }
        }
        return $params;
    }

    private function saveUserAccount(): int
    {
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $username = strtolower(trim((string)Yii::$app->request->post('username', '')));
        $email = strtolower(trim((string)Yii::$app->request->post('email', '')));
        $password = (string)Yii::$app->request->post('password', '');
        $role = strtolower(trim((string)Yii::$app->request->post('role', 'admin')));
        $status = in_array((int)Yii::$app->request->post('status', 1), [0, 1], true) ? (int)Yii::$app->request->post('status', 1) : 1;
        $entityTable = strtolower(trim((string)Yii::$app->request->post('entity_table', '')));
        $recordId = trim((string)Yii::$app->request->post('record_id', ''));
        $clearMapping = (string)Yii::$app->request->post('clear_mapping', '') === '1';

        if ($this->isCommanderOnlyRole($role)) {
            Yii::$app->session->setFlash('warning', 'Role superadmin hanya boleh digunakan di Commander.');
            return 0;
        }

        if ($userId > 0) {
            $user = ProjectUser::findOne($userId);
            if ($user === null) {
                Yii::$app->session->setFlash('error', 'Akun tidak ditemukan.');
                return 0;
            }
        } else {
            $user = new ProjectUser();
        }

        $user->name = trim((string)Yii::$app->request->post('name', '')) !== '' ? trim((string)Yii::$app->request->post('name', '')) : $username;
        $user->username = $username;
        $user->email = $email !== '' ? $email : $user->generateDefaultEmail();
        $user->role = $role;
        $user->status = $status;

        if ($password !== '') {
            $user->setPassword($password);
        } elseif ($user->isNewRecord) {
            Yii::$app->session->setFlash('error', 'Password wajib diisi saat membuat akun baru.');
            return 0;
        }

        if (!$user->validate()) {
            Yii::$app->session->setFlash('error', 'Validasi gagal: ' . implode('; ', array_values($user->getFirstErrors())));
            return 0;
        }

        $user->save(false);

        $savedUserId = (int)$user->id;
        $mappingService = new UserMappingService();
        $projectId = $mappingService->getActiveProjectId();

        if ($clearMapping) {
            $mappingService->clearMapping($projectId, $savedUserId);
        } elseif ($entityTable !== '' && $recordId !== '') {
            $mappingResult = $mappingService->saveMapping($projectId, $savedUserId, $entityTable, $recordId);
            if (!($mappingResult['success'] ?? false)) {
                Yii::$app->session->setFlash('error', 'Akun disimpan, tetapi hubungan data gagal: ' . ($mappingResult['message'] ?? 'Terjadi kesalahan.'));
            }
        } elseif ($entityTable !== '') {
            $mappingService->clearMapping($projectId, $savedUserId);
        }

        Yii::$app->session->setFlash('success', $userId > 0 ? 'Akun berhasil diperbarui.' : 'Akun berhasil dibuat.');
        return $savedUserId;
    }

    /**
     * JSON: paginated, searchable records of an entity table for the "Data"
     * field. Delegates to UserMappingService (the single source of truth for
     * mapping resolution); the display column comes from the framework's
     * RelationMapper. Only the PK + display column are selected, never SELECT *.
     *
     * Request params: entity (required), q (keyword, alias: search),
     * page (default 1), page_size (alias: limit, default 50).
     */
    public function actionUserMappingRecords()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $entity = strtolower(trim((string)Yii::$app->request->get('entity', '')));
        $search = trim((string)Yii::$app->request->get('search', ''));
        $q = trim((string)Yii::$app->request->get('q', ''));
        if ($search === '' && $q !== '') {
            $search = $q;
        }
        $page = max(1, (int)Yii::$app->request->get('page', 1));
        $pageSize = max(1, min(200, (int)Yii::$app->request->get('page_size', Yii::$app->request->get('limit', 50))));

        $service = new UserMappingService();
        $projectId = $service->getActiveProjectId();

        $empty = [
            'success' => false,
            'entity' => $entity,
            'pk_column' => '',
            'display_column' => '',
            'rows' => [],
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => 0,
                'has_next' => false,
            ],
        ];

        if ($entity === '') {
            $empty['message'] = 'Parameter entity wajib diisi.';
            return $empty;
        }

        $knownEntities = [];
        foreach ($service->getEntities($projectId) as $item) {
            $knownEntities[(string)$item['name']] = true;
        }
        if (!isset($knownEntities[$entity])) {
            $empty['message'] = 'Jenis data "' . $entity . '" tidak dikenali pada metadata.';
            return $empty;
        }

        return $service->getRecordsForEntity($entity, $search, $page, $pageSize, $projectId);
    }

    public function actionPermissionInspector()
    {
        return $this->redirect(['permissions']);
    }

    /**
     * Debug panel for Current Identity. Read-only: it never resolves the
     * identity itself, it only reads CurrentIdentityContext (which is the
     * single source of truth) and the authenticated workspace user.
     */
    public function actionIdentityDebug()
    {
        $currentIdentity = null;
        if (Yii::$app->has('currentIdentity')) {
            $currentIdentity = Yii::$app->get('currentIdentity');
        }

        $diagnosis = [];
        $authUser = null;
        if ($currentIdentity !== null && method_exists($currentIdentity, 'diagnose')) {
            $diagnosis = $currentIdentity->diagnose();

            $projectId = $diagnosis['project_id'] ?? null;
            if ($projectId !== null) {
                $authUser = (new \app\components\ProjectAuthContext())->getAuthenticatedUser((int)$projectId);
            }
        }

        return $this->render('identity-debug', [
            'diagnosis' => $diagnosis,
            'authUser' => $authUser,
            'componentAvailable' => $currentIdentity !== null,
        ]);
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
            if (!\app\services\GenerateAccountsService::isValidRoleName($role)) {
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
            'menu' => $this->uniqueAccessCatalogEntries(array_values(array_map(function (array $menu): array {
                $name = trim((string)($menu['name'] ?? ''));
                $menuKey = $this->normalizeAccessKey((string)($menu['menu_key'] ?? ($name !== '' ? $name : ($menu['route'] ?? 'menu'))));
                return [
                    'type' => 'menu',
                    'key' => $menuKey,
                    'label' => $name !== '' ? $name : ucfirst(str_replace('-', ' ', $menuKey)),
                    'description' => 'Menu aplikasi yang tampil di sidebar.',
                ];
            }, $menus))),
            'page' => $this->uniqueAccessCatalogEntries(array_values(array_map(function (array $page): array {
                $label = trim((string)($page['name'] ?? $page['title'] ?? ''));
                $pageKey = $this->normalizeAccessKey((string)($page['slug'] ?? ($label !== '' ? $label : ($page['id'] ?? 'page'))));
                return [
                    'type' => 'page',
                    'key' => $pageKey,
                    'label' => $label !== '' ? $label : ucfirst(str_replace('-', ' ', $pageKey)),
                    'description' => 'Halaman yang boleh dibuka oleh role ini.',
                ];
            }, $pages))),
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

        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete('role_access', ['role' => $role])->execute();

            $seen = [];
            foreach ($catalog as $type => $items) {
                foreach ($items as $item) {
                    $key = strtolower(trim((string)($item['key'] ?? '')));
                    if ($key === '') {
                        continue;
                    }

                    $comboKey = $type . "\x00" . $key;
                    if (isset($seen[$comboKey])) {
                        continue;
                    }
                    $seen[$comboKey] = true;

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

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deduplicate catalog entries by their normalized access key so the same
     * (access_type, access_key) combination is never emitted twice. Source rows
     * (master_menu, master_page) may normalize to the same key; the first
     * occurrence wins to preserve ordering.
     *
     * @param array<int, array<string, mixed>> $entries
     * @return array<int, array<string, mixed>>
     */
    private function uniqueAccessCatalogEntries(array $entries): array
    {
        $seen = [];
        $unique = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $key = strtolower(trim((string)($entry['key'] ?? '')));
            if ($key === '') {
                continue;
            }

            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $unique[] = $entry;
        }

        return $unique;
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
    
    public function actionUploadFavicon()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->loadSettings();
        
        $uploadedFile = UploadedFile::getInstanceByName('workspace_favicon_image');
        
        if (!$uploadedFile) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        $allowedExtensions = ['ico', 'png', 'jpg', 'jpeg', 'svg', 'webp'];
        $extension = strtolower($uploadedFile->getExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed: ICO, PNG, JPG, JPEG, SVG, WEBP'];
        }
        
        $maxSize = 2 * 1024 * 1024;
        if ($uploadedFile->size > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size: 2MB'];
        }
        
        $storage = new WorkspaceMediaStorage();
        $relativeDir = $this->workspaceMediaRelativeDir('workspace-favicon');
        $uploadResult = $storage->storeUploadedFile($uploadedFile, 'favicon', $relativeDir);
        if (!$uploadResult['success']) {
            return $uploadResult;
        }

        $oldFavicon = $model->workspace_favicon;
        if ($oldFavicon) {
            $this->deleteWorkspaceMediaFile($oldFavicon);
        }

        $model->workspace_favicon = (string)($uploadResult['relative_path'] ?? '');
        if (!$model->save()) {
            $storage->delete((string)($uploadResult['relative_path'] ?? ''));
            return ['success' => false, 'message' => 'Favicon tersimpan di disk, tetapi gagal disimpan ke database.'];
        }

        return [
            'success' => true,
            'message' => 'Favicon uploaded successfully',
            'faviconUrl' => $model->getFaviconAsset()['url'],
            'faviconFile' => (string)($uploadResult['relative_path'] ?? ''),
        ];
    }
    
    public function actionRemoveFavicon()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->loadSettings();
        
        if ($model->workspace_favicon) {
            $this->deleteWorkspaceMediaFile((string)$model->workspace_favicon);
            
            $model->workspace_favicon = null;
            if (!$model->save()) {
                return ['success' => false, 'message' => 'Gagal menyimpan perubahan favicon ke database'];
            }
            
            return ['success' => true, 'message' => 'Favicon removed successfully'];
        }
        
        return ['success' => false, 'message' => 'No favicon to remove'];
    }
    
    private function loadSettings()
    {
        $model = new \app\models\WorkspaceSettings();
        $model->loadFromDatabase();
        return $model;
    }

    /**
     * Build the view data needed for the Authentication section of Workspace
     * Settings. Workspace Settings no longer decides identity: authentication
     * is global (table `users`) and each account is mapped to its domain record
     * on the User Management page. This only reports read-only status.
     *
     * @return array{
     *   authUser: \app\models\ProjectUser|null,
     *   authRuntime: array<string, mixed>,
     * }
     */
    private function buildAuthenticationViewData(): array
    {
        $authUser = $this->getIdentityAuthUser();

        return [
            'authUser' => $authUser,
            'authRuntime' => $this->buildIdentityRuntimeStatusWithUser($authUser),
        ];
    }

    private function getIdentityAuthUser(): ?\app\models\ProjectUser
    {
        try {
            if (!class_exists(\app\components\ActiveProjectContext::class) || !class_exists(\app\components\ProjectSchema::class) || !\app\components\ProjectSchema::supportsProjectContext()) {
                return null;
            }
            $projectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
            if ($projectId === null || $projectId <= 0) {
                return null;
            }
            return (new \app\components\ProjectAuthContext())->getAuthenticatedUser((int)$projectId);
        } catch (\Throwable $e) {
            Yii::warning('Identity auth user lookup failed: ' . $e->getMessage(), 'current-identity');
            return null;
        }
    }

    /**
     * Diagnose the active identity once, passing the already-resolved user id so
     * the resolver does not perform a second user lookup.
     *
     * @return array<string, mixed>
     */
    private function buildIdentityRuntimeStatusWithUser(?\app\models\ProjectUser $authUser): array
    {
        if (!Yii::$app->has('currentIdentity')) {
            return ['status' => 'component_missing', 'reason' => 'Komponen currentIdentity tidak terdaftar.'];
        }

        try {
            $projectId = null;
            if (class_exists(\app\components\ActiveProjectContext::class) && class_exists(\app\components\ProjectSchema::class) && \app\components\ProjectSchema::supportsProjectContext()) {
                $activeProjectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
                $projectId = $activeProjectId !== null && $activeProjectId > 0 ? (int)$activeProjectId : null;
            }
            return Yii::$app->currentIdentity->diagnose($projectId, $authUser !== null ? (int)$authUser->id : null);
        } catch (\Throwable $e) {
            Yii::warning('Identity runtime status failed: ' . $e->getMessage(), 'current-identity');
            return ['status' => 'error', 'reason' => 'Status runtime tidak dapat dimuat.'];
        }
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
