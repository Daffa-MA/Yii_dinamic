<?php

namespace app\components;

use app\models\MasterForm;
use app\models\MasterPage;
use app\models\Form;
use Yii;
use yii\db\Query;
use yii\helpers\Url;

class ProjectPermissionService
{
    private function isCommanderSuperAdmin(): bool
    {
        return (new CommanderAuthContext())->isSuperAdmin();
    }

    /**
     * @return array{visible:bool,access:bool,deny_reason:string,reason:string,role:string,user_id:int|null,menu_name:string,route:string,menu_key:string}
     */
    private function resolveMenuAccessState(array $menu, ?int $projectId = null): array
    {
        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        $role = $user !== null ? strtolower(trim((string)$user->role)) : '';
        $userId = $user !== null ? (int)$user->id : null;
        $menuName = trim((string)($menu['name'] ?? ''));
        $route = trim((string)($menu['route'] ?? ''), '/');
        $menuKey = $this->resolveMenuKey($menu);

        if ($this->isCommanderSuperAdmin()) {
            return $this->buildMenuAccessState(true, true, 'commander_superadmin', $role, $userId, $menuName, $route, $menuKey);
        }

        if ($user === null) {
            return $this->buildMenuAccessState(false, false, 'no_authenticated_user', $role, $userId, $menuName, $route, $menuKey);
        }

        if ($this->isAdminRole($role)) {
            return $this->buildMenuAccessState(true, true, 'admin_role', $role, $userId, $menuName, $route, $menuKey);
        }

        $roles = trim((string)($menu['visibility_roles'] ?? ''));
        if ($roles !== '') {
            $allowed = array_map('trim', explode(',', strtolower($roles)));
            if (in_array($role, $allowed, true)) {
                return $this->buildMenuAccessState(true, true, 'visibility_roles_match', $role, $userId, $menuName, $route, $menuKey);
            }
        }

        if ($menuKey === '') {
            return $this->buildMenuAccessState(false, false, 'menu_key_missing', $role, $userId, $menuName, $route, $menuKey);
        }

        if ($this->roleAccessTableHasRows($role) && $this->hasRoleAccess($role, 'menu', $menuKey)) {
            return $this->buildMenuAccessState(true, true, 'role_access_table_menu_match', $role, $userId, $menuName, $route, $menuKey);
        }

        $permissionKeys = [
            "menu.{$menuKey}.view",
            "menu.{$menuKey}.create",
            "menu.{$menuKey}.edit",
            "menu.{$menuKey}.delete",
        ];

        $menuType = strtolower(trim((string)($menu['type'] ?? '')));
        if ($menuType === 'route' && $route !== '') {
            $permissionKeys[] = 'route.' . $this->normalizeRouteKey($route) . '.access';
        }

        $pageId = (int)($menu['page_id'] ?? 0);
        if ($pageId > 0) {
            $page = MasterPage::findOne($pageId);
            if ($page instanceof MasterPage) {
                $pageKey = $this->resolvePageKey($page);
                $permissionKeys[] = "page.{$pageKey}.view";
                $permissionKeys[] = "builder.page.{$pageKey}.access";
            }
        }

        $formId = (int)($menu['form_id'] ?? 0);
        if ($formId > 0) {
            $form = MasterForm::findByIdScoped($formId);
            if ($form instanceof MasterForm) {
                $formKey = $this->resolveFormKey($form);
                $permissionKeys[] = "form.{$formKey}.view";
                $permissionKeys[] = "form.{$formKey}.submit";
            }
        }

        if ($this->legacyHasAnyPermission($role, $permissionKeys)) {
            return $this->buildMenuAccessState(true, true, 'legacy_permission_match', $role, $userId, $menuName, $route, $menuKey);
        }

        $denyReason = $menuType === 'route'
            ? 'legacy_permission_missing_for_route_menu'
            : 'legacy_permission_missing_for_menu';

        return $this->buildMenuAccessState(false, false, $denyReason, $role, $userId, $menuName, $route, $menuKey);
    }

    /**
     * @return array{visible:bool,access:bool,deny_reason:string,reason:string,role:string,user_id:int|null,menu_name:string,route:string,menu_key:string}
     */
    private function buildMenuAccessState(bool $visible, bool $access, string $reason, string $role, ?int $userId, string $menuName, string $route, string $menuKey): array
    {
        return [
            'visible' => $visible,
            'access' => $access,
            'deny_reason' => $visible ? '' : $reason,
            'reason' => $reason,
            'role' => $role,
            'user_id' => $userId,
            'menu_name' => $menuName,
            'route' => $route,
            'menu_key' => $menuKey,
        ];
    }

    private function logPermissionDebug(string $scope, array $state, string $route = ''): void
    {
        PermissionDebugLogger::log([
            'scope' => $scope,
            'user_id' => $state['user_id'] ?? null,
            'role' => $state['role'] ?? '',
            'route' => $route !== '' ? $route : ($state['route'] ?? ''),
            'menu_name' => $state['menu_name'] ?? '',
            'menu_key' => $state['menu_key'] ?? '',
            'visible_result' => (bool)($state['visible'] ?? false),
            'access_result' => (bool)($state['access'] ?? false),
            'deny_reason' => (string)($state['deny_reason'] ?? $state['reason'] ?? ''),
        ]);
    }

    public function canAccessRoute(string $route, ?int $projectId = null): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return false;
        }

        $route = trim(preg_replace('/[?#].*$/', '', $route), '/');
        $role = strtolower(trim((string)$user->role));
        if ($this->isAdminRole($role)) {
            return true;
        }

        if ($route === '') {
            return false;
        }

        if ($this->canAccessRouteViaMenu($route, $projectId)) {
            return true;
        }

        $simpleAccess = $this->resolveRouteAccess($route);
        if ($simpleAccess !== null && $this->roleAccessTableHasRows($role)) {
            if ($this->hasRoleAccess($role, $simpleAccess['type'], $simpleAccess['key'])) {
                return true;
            }
        }

        $permissionKeys = $this->buildRoutePermissionKeys($route, $projectId);
        $allowed = $this->canAccessPermissionKeys($permissionKeys, $projectId);
        if (!$allowed) {
            PermissionDebugLogger::log([
                'scope' => 'route_access',
                'user_id' => (int)$user->id,
                'role' => $role,
                'route' => trim($route, '/'),
                'menu_name' => '',
                'menu_key' => '',
                'visible_result' => false,
                'access_result' => false,
                'deny_reason' => 'route_permission_missing',
            ]);
        }

        return $allowed;
    }

    public function canAccessMenu(array $menu, ?int $projectId = null): bool
    {
        $state = $this->resolveMenuAccessState($menu, $projectId);
        $this->logPermissionDebug('menu_visibility', $state);
        return (bool)($state['visible'] ?? false);
    }

    public function canAccessPage(MasterPage $page, ?int $projectId = null): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return false;
        }

        $role = strtolower(trim((string)$user->role));
        if ($this->isAdminRole($role)) {
            return true;
        }

        $pageKey = $this->resolvePageKey($page);
        if ($pageKey === '') {
            return false;
        }

        if ($this->roleAccessTableHasRows($role) && $this->hasRoleAccess($role, 'page', $pageKey)) {
            return true;
        }

        return $this->legacyHasAnyPermission($role, [
            "page.{$pageKey}.view",
            "builder.page.{$pageKey}.access",
        ]) || $this->canAccessMenuForPage((int)$page->id, $projectId);
    }

    public function canAccessForm(MasterForm $form, ?int $projectId = null): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return false;
        }

        $role = strtolower(trim((string)$user->role));
        if ($this->isAdminRole($role)) {
            return true;
        }

        $formKey = $this->resolveFormKey($form);
        if ($formKey === '') {
            return false;
        }

        if ($this->roleAccessTableHasRows($role)) {
            return $this->hasRoleAccess($role, 'form', $formKey);
        }

        return $this->legacyHasAnyPermission($role, [
            "form.{$formKey}.view",
            "form.{$formKey}.submit",
            "builder.form.{$formKey}.access",
        ]);
    }

    public function canUseFormAsPageContent(int $formId, int $pageId, ?int $projectId = null): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        if ($formId <= 0 || $pageId <= 0) {
            return false;
        }

        $page = MasterPage::findOne($pageId);
        $form = MasterForm::findByIdScoped($formId);
        if (!$page instanceof MasterPage || !$form instanceof MasterForm) {
            return false;
        }

        if (!$this->canAccessPage($page, $projectId) && !$this->canAccessMenuForPage($pageId, $projectId)) {
            return false;
        }

        return true;
    }

    public function canUseLegacyFormAsPageContent(int $formId, int $pageId, ?int $projectId = null): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        if ($formId <= 0 || $pageId <= 0) {
            return false;
        }

        $page = MasterPage::findOne($pageId);
        if (!$page instanceof MasterPage) {
            return false;
        }

        $formQuery = Form::find()->where(['id' => $formId]);
        if ($projectId !== null && Form::getTableSchema() !== null && isset(Form::getTableSchema()->columns['project_id'])) {
            $formQuery->andWhere(['project_id' => (int)$projectId]);
        }

        $form = $formQuery->one();
        if (!$form instanceof Form) {
            return false;
        }

        if (!$this->canAccessPage($page, $projectId) && !$this->canAccessMenuForPage($pageId, $projectId)) {
            return false;
        }

        return true;
    }

    public function canAccessPermissionKeys(array $permissionKeys, ?int $projectId = null): bool
    {
        if ($this->isCommanderSuperAdmin()) {
            return true;
        }

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return false;
        }

        $role = strtolower(trim((string)$user->role));
        if ($this->isAdminRole($role)) {
            return true;
        }

        $permissionKeys = array_values(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $permissionKeys)));

        if (empty($permissionKeys)) {
            return false;
        }

        $simpleCandidates = [];
        $legacyCandidates = [];
        foreach ($permissionKeys as $permissionKey) {
            $mapped = $this->mapPermissionKeyToRoleAccess($permissionKey);
            if (!empty($mapped)) {
                foreach ($mapped as $candidate) {
                    $simpleCandidates[] = $candidate;
                }
                continue;
            }

            $legacyCandidates[] = $permissionKey;
        }

        if ($this->roleAccessTableHasRows($role) && !empty($simpleCandidates)) {
            foreach ($simpleCandidates as $candidate) {
                if ($this->hasRoleAccess($role, (string)$candidate['type'], (string)$candidate['key'])) {
                    return true;
                }
            }

            if (!empty($legacyCandidates)) {
                return $this->legacyHasAnyPermission($role, $legacyCandidates);
            }

            return false;
        }

        if (!empty($simpleCandidates)) {
            foreach ($simpleCandidates as $candidate) {
                if ($this->hasRoleAccess($role, (string)$candidate['type'], (string)$candidate['key'])) {
                    return true;
                }
            }
        }

        return $this->legacyHasAnyPermission($role, array_merge($legacyCandidates, $permissionKeys));
    }

    public function resolveAccessibleLandingRoute(?int $projectId = null, ?string $preferredRoute = null): ?string
    {
        if ($this->isCommanderSuperAdmin()) {
            $preferredRoute = $this->normalizeIncomingRoute($preferredRoute);
            return $preferredRoute !== '' ? Url::to([$preferredRoute]) : Url::to(['site/dashboard']);
        }

        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);
        if ($user === null) {
            return null;
        }

        $role = strtolower(trim((string)$user->role));
        if ($this->isAdminRole($role)) {
            return Url::to(['site/dashboard']);
        }

        $candidates = [];
        $preferredRoute = $this->normalizeIncomingRoute($preferredRoute);
        if ($preferredRoute !== '') {
            $candidates[] = $preferredRoute;
        }

        $candidates = array_merge($candidates, [
            'site/dashboard',
            'workspace-settings/index',
            'master-menu/index',
            'master-page/index',
            'master-form/index',
            'table-builder/index',
        ]);

        $candidates = array_values(array_unique(array_filter($candidates)));
        foreach ($candidates as $candidate) {
            if ($this->canAccessRoute($candidate, $projectId)) {
                return Url::to([$candidate]);
            }
        }

        return null;
    }

    private function hasRoleAccess(string $role, string $accessType, string $accessKey): bool
    {
        $schema = Yii::$app->db->schema;
        if ($schema->getTableSchema('role_access', true) === null) {
            return false;
        }

        return (new Query())
            ->from('role_access')
            ->where([
                'role' => $role,
                'access_type' => strtolower(trim($accessType)),
                'access_key' => strtolower(trim($accessKey)),
                'can_access' => 1,
            ])
            ->exists(Yii::$app->db);
    }

    private function canAccessMenuForPage(int $pageId, ?int $projectId = null): bool
    {
        $schema = Yii::$app->db->schema;
        if ($schema->getTableSchema('master_menu', true) === null) {
            return false;
        }

        $menus = (new Query())
            ->from('master_menu')
            ->where(['page_id' => $pageId])
            ->all(Yii::$app->db);

        foreach ($menus as $menu) {
            if ($this->canAccessMenu($menu, $projectId)) {
                return true;
            }
        }

        return false;
    }

    private function pageContainsForm(MasterPage $page, int $formId): bool
    {
        $db = Yii::$app->db;
        foreach (['master_page_form', 'page_forms'] as $tableName) {
            if ($db->schema->getTableSchema($tableName, true) === null) {
                continue;
            }

            if ((new Query())->from($tableName)->where(['page_id' => (int)$page->id, 'form_id' => $formId])->exists($db)) {
                return true;
            }
        }

        $layoutData = json_decode((string)($page->layout_json ?? ''), true);
        return is_array($layoutData) && $this->layoutContainsForm($layoutData, $formId);
    }

    private function pageContainsLegacyForm(MasterPage $page, int $formId): bool
    {
        $db = Yii::$app->db;
        if ($db->schema->getTableSchema('master_page_form', true) !== null) {
            if ((new Query())->from('master_page_form')->where([
                'page_id' => (int)$page->id,
                'form_id' => $formId,
            ])->exists($db)) {
                return true;
            }
        }

        return $this->pageContainsForm($page, $formId);
    }

    private function layoutContainsForm(array $items, int $formId): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $props = is_array($item['props'] ?? null) ? $item['props'] : [];
            $candidate = (int)($props['formId'] ?? $props['form_id'] ?? $item['form_id'] ?? 0);
            if ($candidate === $formId) {
                return true;
            }

            foreach (['children', 'items', 'columns', 'blocks'] as $childKey) {
                if (isset($item[$childKey]) && is_array($item[$childKey]) && $this->layoutContainsForm($item[$childKey], $formId)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function roleAccessTableHasRows(string $role): bool
    {
        $schema = Yii::$app->db->schema;
        if ($schema->getTableSchema('role_access', true) === null) {
            return false;
        }

        return (new Query())
            ->from('role_access')
            ->where(['role' => $role])
            ->exists(Yii::$app->db);
    }

    private function legacyHasAnyPermission(string $role, array $permissionKeys): bool
    {
        $permissionKeys = array_values(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $permissionKeys)));

        if (empty($permissionKeys)) {
            return false;
        }

        $schema = Yii::$app->db->schema;
        if ($schema->getTableSchema('roles', true) === null || $schema->getTableSchema('permissions', true) === null || $schema->getTableSchema('role_permissions', true) === null) {
            return false;
        }

        $roleId = (new Query())
            ->select('id')
            ->from('roles')
            ->where(['name' => $role])
            ->scalar(Yii::$app->db);

        if (!$roleId) {
            return false;
        }

        $permissionIds = (new Query())
            ->select('id')
            ->from('permissions')
            ->where(['permission_key' => $permissionKeys])
            ->column(Yii::$app->db);

        if (empty($permissionIds)) {
            return false;
        }

        return (new Query())
            ->from('role_permissions')
            ->where([
                'role_id' => (int)$roleId,
                'permission_id' => $permissionIds,
            ])
            ->exists(Yii::$app->db);
    }

    /**
     * @return array<int, array{type:string,key:string}>
     */
    private function mapPermissionKeyToRoleAccess(string $permissionKey): array
    {
        $permissionKey = strtolower(trim($permissionKey));
        if ($permissionKey === '') {
            return [];
        }

        if (preg_match('/^menu\.([^.]+)\.(view|create|edit|delete)$/', $permissionKey, $matches)) {
            return [[
                'type' => 'menu',
                'key' => $matches[1],
            ]];
        }

        if (preg_match('/^(?:page|builder\.page)\.([^.]+)\.(view|access)$/', $permissionKey, $matches)) {
            return [[
                'type' => 'page',
                'key' => $matches[1],
            ]];
        }

        if (preg_match('/^(?:form|builder\.form)\.([^.]+)\.(view|submit|access)$/', $permissionKey, $matches)) {
            return [[
                'type' => 'form',
                'key' => $matches[1],
            ]];
        }

        if (preg_match('/^component\.(?:page|form)\.([^.]+)\.[^.]+\.view$/', $permissionKey, $matches)) {
            $base = $matches[1];
            return [[
                'type' => 'page',
                'key' => $base,
            ]];
        }

        if (preg_match('/^route\.([^.]+(?:\.[^.]+)*)\.access$/', $permissionKey, $matches)) {
            $routeKey = $matches[1];
            $mapped = $this->mapRouteKeyToSimpleAccess($routeKey);
            if ($mapped !== null) {
                return [$mapped];
            }

            return [[
                'type' => 'route',
                'key' => $routeKey,
            ]];
        }

        if ($permissionKey === 'builder.global.access'
            || $permissionKey === 'builder.palette.access'
            || $permissionKey === 'builder.tools.access'
            || $permissionKey === 'builder.drag.access'
            || $permissionKey === 'builder.actions.access'
            || $permissionKey === 'builder.forms.access'
            || $permissionKey === 'action.page.create'
            || $permissionKey === 'action.page.edit') {
            return [[
                'type' => 'system_builder',
                'key' => 'master_page',
            ]];
        }

        if (preg_match('/^builder\.page\.[^.]+\.access$/', $permissionKey)) {
            return [[
                'type' => 'system_builder',
                'key' => 'master_page',
            ]];
        }

        if (preg_match('/^builder\.form\.[^.]+\.access$/', $permissionKey)) {
            return [[
                'type' => 'system_builder',
                'key' => 'master_form',
            ]];
        }

        return [];
    }

    private function mapRouteKeyToSimpleAccess(string $routeKey): ?array
    {
        $routeKey = strtolower(trim($routeKey));
        if ($routeKey === '') {
            return null;
        }

        $routeKey = preg_replace('/[?#].*$/', '', $routeKey) ?? $routeKey;
        $routeKey = trim($routeKey, '/');

        $systemBuilderRoutes = [
            'master-menu.index' => 'master_menu',
            'master-menu.create' => 'master_menu',
            'master-menu.update' => 'master_menu',
            'master-page.index' => 'master_page',
            'master-page.create' => 'master_page',
            'master-page.update' => 'master_page',
            'master-page.builder' => 'master_page',
            'master-page.dynamic-create' => 'master_page',
            'master-page.dynamic-update' => 'master_page',
            'master-form.index' => 'master_form',
            'master-form.create' => 'master_form',
            'master-form.update' => 'master_form',
            'table-builder.index' => 'master_table',
            'table-builder.create' => 'master_table',
            'table-builder.update' => 'master_table',
            'settings.workspace' => 'workspace_settings',
            'settings.workspace.permissions' => 'workspace_settings',
            'settings.workspace.permission-inspector' => 'workspace_settings',
            'settings.workspace.users' => 'workspace_settings',
            'workspace-settings.index' => 'workspace_settings',
            'workspace-settings.permissions' => 'workspace_settings',
            'workspace-settings.permission-inspector' => 'workspace_settings',
            'workspace-settings.users' => 'workspace_settings',
        ];

        if (isset($systemBuilderRoutes[$routeKey])) {
            return [
                'type' => 'system_builder',
                'key' => $systemBuilderRoutes[$routeKey],
            ];
        }

        return null;
    }

    private function resolveRouteAccess(string $route): ?array
    {
        $route = trim($route, '/');
        if ($route === '') {
            return null;
        }

        $normalizedRoute = strtolower($route);
        if (in_array($normalizedRoute, ['site/dashboard', 'dashboard'], true)) {
            return [
                'type' => 'menu',
                'key' => 'dashboard',
            ];
        }

        $simple = $this->mapRouteKeyToSimpleAccess(str_replace('/', '.', $normalizedRoute));
        if ($simple !== null) {
            return $simple;
        }

        $request = Yii::$app->request;
        $routeOnly = trim($normalizedRoute, '/');

        if (in_array($routeOnly, ['page/view', 'page/view-dynamic', 'master-page/view-dynamic', 'master-page/preview-live'], true)) {
            $pageId = (int)$request->get('id', 0);
            if ($pageId > 0) {
                $page = MasterPage::findOne($pageId);
                if ($page instanceof MasterPage) {
                    return [
                        'type' => 'page',
                        'key' => $this->resolvePageKey($page),
                    ];
                }
            }
        }

        if (in_array($routeOnly, ['master-form/preview', 'master-form/submit'], true)) {
            return [
                'type' => 'system_builder',
                'key' => 'master_form',
            ];
        }

        if ($routeOnly === 'form/view') {
            $formId = (int)$request->get('id', 0);
            if ($formId > 0) {
                $form = MasterForm::findByIdScoped($formId);
                if ($form instanceof MasterForm) {
                    return [
                        'type' => 'form',
                        'key' => $this->resolveFormKey($form),
                    ];
                }
            }
        }

        return null;
    }

    private function resolveMenuKey(array $menu): string
    {
        $menuKey = trim((string)($menu['menu_key'] ?? ''));
        $menuName = trim((string)($menu['name'] ?? ''));
        $route = trim((string)($menu['route'] ?? ''), '/');

        if ($menuKey === '') {
            $menuKey = $menuName !== '' ? $menuName : ($route !== '' ? $route : 'menu');
        }

        return $this->normalizeSegment($menuKey);
    }

    private function resolvePageKey(MasterPage $page): string
    {
        return $this->normalizeSegment((string)($page->slug ?: $page->title ?: $page->name ?: $page->id));
    }

    private function resolveFormKey(MasterForm $form): string
    {
        return $this->normalizeSegment((string)($form->slug ?: $form->form_name ?: $form->name ?: $form->id));
    }

    private function isAdminRole(string $role): bool
    {
        return $role === 'admin';
    }

    private function normalizeSegment(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[?#].*$/', '', $value) ?? $value;
        $value = preg_replace('/[\/\s]+/', '-', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9\-_]+/', '-', $value) ?? $value;
        $value = preg_replace('/-+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value !== '' ? $value : 'item';
    }

    private function buildRoutePermissionKeys(string $route, ?int $projectId = null): array
    {
        $permissionKeys = [];
        $normalizedRoute = trim(preg_replace('/[?#].*$/', '', $route), '/');
        $routeKey = $this->normalizeRouteKey($normalizedRoute);
        $requestPath = trim((string)Yii::$app->request->pathInfo, '/');
        $requestPathKey = $this->normalizeRouteKey($requestPath);

        if ($routeKey !== '') {
            $permissionKeys[] = 'route.' . $routeKey . '.access';
            $permissionKeys[] = $routeKey;
        }

        if ($requestPathKey !== '' && $requestPathKey !== $routeKey) {
            $permissionKeys[] = 'route.' . $requestPathKey . '.access';
            $permissionKeys[] = $requestPathKey;
        }

        $request = Yii::$app->request;
        $routeOnly = trim($normalizedRoute, '/');

        if ($routeOnly === 'page/view' || $routeOnly === 'page/view-dynamic' || $routeOnly === 'master-page/view-dynamic' || $routeOnly === 'master-page/preview-live') {
            $page = $this->resolvePageFromRequest($routeOnly);
            if ($page instanceof MasterPage) {
                $base = $this->resolvePageKey($page);
                $permissionKeys[] = "page.{$base}.view";
                $permissionKeys[] = "builder.page.{$base}.access";
                foreach ($this->collectMenuKeysForPage($page) as $menuKey) {
                    $permissionKeys[] = "menu.{$menuKey}.view";
                    $permissionKeys[] = "menu.{$menuKey}.create";
                    $permissionKeys[] = "menu.{$menuKey}.edit";
                    $permissionKeys[] = "menu.{$menuKey}.delete";
                }
            }
        }

        if (in_array($routeOnly, [
            'master-page/dynamic-create',
            'master-page/dynamic-update',
            'master-page/create',
            'master-page/update',
            'master-page/builder',
            'table-builder/index',
            'table-builder/create',
            'table-builder/update',
            'master-menu/index',
            'master-menu/create',
            'master-menu/update',
            'master-form/index',
            'master-form/create',
            'master-form/update',
            'master-form/preview',
            'master-form/submit',
        ], true)) {
            $permissionKeys[] = 'builder.global.access';
            $permissionKeys[] = 'builder.palette.access';
            $permissionKeys[] = 'builder.tools.access';
            $permissionKeys[] = 'builder.forms.access';
        }

        if ($routeOnly === 'master-form/preview' || $routeOnly === 'master-form/submit') {
            $formId = (int)$request->get('id', 0);
            if ($formId > 0) {
                $form = MasterForm::findByIdScoped($formId);
                if ($form instanceof MasterForm) {
                    $base = $this->resolveFormKey($form);
                    $permissionKeys[] = "builder.form.{$base}.access";
                }
            }
        }

        if ($routeOnly === 'form/view') {
            $formId = (int)$request->get('id', 0);
            if ($formId > 0) {
                $form = MasterForm::findByIdScoped($formId);
                if ($form instanceof MasterForm) {
                    $base = $this->resolveFormKey($form);
                    $permissionKeys[] = "form.{$base}.view";
                    $permissionKeys[] = "form.{$base}.submit";
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $permissionKeys))));
    }

    private function normalizeRouteKey(string $route): string
    {
        $route = trim($route);
        $route = preg_replace('/[?#].*$/', '', $route) ?? $route;
        $route = trim($route, '/');
        if ($route === '') {
            return '';
        }

        $segments = array_filter(array_map(function ($segment) {
            return $this->normalizeSegment($segment);
        }, explode('/', $route)));

        return implode('.', $segments);
    }

    private function normalizeIncomingRoute(?string $route): string
    {
        $route = trim((string)$route);
        if ($route === '') {
            return '';
        }

        $path = parse_url($route, PHP_URL_PATH);
        $route = is_string($path) && $path !== '' ? $path : $route;
        $route = trim($route, '/');

        if ($route === '') {
            return '';
        }

        return trim(str_replace('/', '/', $route), '/');
    }

    /**
     * @return array<int, string>
     */
    private function collectMenuKeysForPage(MasterPage $page): array
    {
        $schema = Yii::$app->db->schema;
        if ($schema->getTableSchema('master_menu', true) === null) {
            return [];
        }

        $menus = (new Query())
            ->from('master_menu')
            ->where(['page_id' => (int)$page->id])
            ->all(Yii::$app->db);

        $menuKeys = [];
        foreach ($menus as $menu) {
            if (!is_array($menu)) {
                continue;
            }

            $menuKey = $this->resolveMenuKey($menu);
            if ($menuKey !== '') {
                $menuKeys[] = $menuKey;
            }
        }

        return array_values(array_unique($menuKeys));
    }

    private function canAccessRouteViaMenu(string $route, ?int $projectId = null): bool
    {
        $schema = Yii::$app->db->schema;
        if ($schema->getTableSchema('master_menu', true) === null) {
            return false;
        }

        $normalizedRoute = '/' . ltrim(trim($route, '/'), '/');
        $requestPath = '/' . ltrim(trim((string)Yii::$app->request->pathInfo, '/'), '/');
        $routeVariants = array_values(array_unique(array_filter([$normalizedRoute, $requestPath])));
        if (in_array('/site/dashboard', $routeVariants, true)) {
            $routeVariants[] = '/dashboard';
        }
        if (in_array('/dashboard', $routeVariants, true)) {
            $routeVariants[] = '/site/dashboard';
        }

        $menus = (new Query())
            ->from('master_menu')
            ->where(['is_active' => 1])
            ->all(Yii::$app->db);

        foreach ($menus as $menu) {
            if (!is_array($menu)) {
                continue;
            }

            $menuType = strtolower(trim((string)($menu['type'] ?? '')));
            $menuMatches = false;

            if ($menuType === 'page') {
                $menuPageId = (int)($menu['page_id'] ?? 0);
                $menuMatches = $menuPageId > 0
                    && in_array('/page/view', $routeVariants, true)
                    && (int)Yii::$app->request->get('id', 0) === $menuPageId;
            } elseif ($menuType === 'form') {
                $menuFormId = (int)($menu['form_id'] ?? 0);
                $menuMatches = $menuFormId > 0
                    && (in_array('/master-form/preview', $routeVariants, true) || in_array('/form/view', $routeVariants, true))
                    && (int)Yii::$app->request->get('id', 0) === $menuFormId;
            } else {
                $menuRoute = '/' . ltrim(trim((string)($menu['route'] ?? ''), '/'), '/');
                $menuMatches = in_array($menuRoute, $routeVariants, true);
            }

            if (!$menuMatches) {
                continue;
            }

            $state = $this->resolveMenuAccessState($menu, $projectId);
            $this->logPermissionDebug('route_menu_match', $state, $route);
            if (($state['visible'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    private function resolvePageFromRequest(string $routeOnly): ?MasterPage
    {
        $request = Yii::$app->request;
        $pageId = (int)$request->get('id', 0);
        if ($routeOnly === 'page/view' && $pageId > 0) {
            return MasterPage::findOne($pageId);
        }

        $slug = trim((string)$request->get('slug', $request->get('page', '')));
        if ($slug !== '') {
            $page = MasterPage::findOne(['slug' => $slug]);
            if ($page instanceof MasterPage) {
                return $page;
            }
        }

        if ($pageId > 0) {
            return MasterPage::findOne($pageId);
        }

        return null;
    }
}
