<?php

namespace app\components;

use app\models\Project;
use app\models\MasterMenu;
use app\models\MasterPage;
use Yii;
use yii\base\ActionEvent;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\helpers\Url;

class ProjectAccessBootstrap implements BootstrapInterface
{
    private const PUBLIC_ROUTES = [
        'site/error',
        'site/login',
        'site/index',
        'site/contact',
        'site/about',
        'project/index',
        'project/select',
        'project/login',
        'project/access-denied',
        'project/logout',
        'project/change-password',
        'project/profile',
    ];

    private const PROTECTED_PREFIXES = [
        'site/dashboard',
        'dashboard',
        'table-builder',
        'workspace-settings',
        'master-form',
        'master-menu',
        'master-page',
        'form-placement',
        'published-form',
        'page',
        'form/create',
        'form/update',
        'form/delete',
        'form/index',
        'form/submissions',
        'form/export',
    ];

    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
            if ((new DomainContext())->isRootDomain()) {
                return;
            }

            $route = trim((string)Yii::$app->requestedRoute, '/');
            if ($this->isPublicRoute($route)) {
                return;
            }

            $commanderAuth = new CommanderAuthContext();
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            if (!$this->isProtectedRoute($route)) {
                return;
            }

            if ($commanderAuth->isSuperAdmin()) {
                if ($activeProjectId === null) {
                    $host = (new DomainContext())->currentHost();
                    $project = Project::findByCustomDomain($host);
                    if ($project !== null) {
                        $projectContext = new ActiveProjectContext();
                        $projectContext->setResolvedDomainProject((int)$project->id);
                        $projectContext->setActiveProject((int)$project->id);
                        $projectContext->setSuperAdminMode(true);
                        (new ActiveDatabaseContext())->resolveAndApply();
                        $activeProjectId = (int)$project->id;
                    }
                }

                AuthContextDebugLogger::log('workspace_superadmin_bypass', [
                    'route' => $route,
                    'active_project_id' => $activeProjectId,
                ]);
                return;
            }

            if ($activeProjectId === null) {
                AuthContextDebugLogger::log('workspace_redirect_project_index_missing_project', [
                    'route' => $route,
                ]);
                $this->redirectSafely($event, Url::to(['project/index']), 'protected_route_without_active_project');
                return;
            }

            (new ActiveDatabaseContext())->resolveAndApply();
            $authContext = new ProjectAuthContext();
                if ($authContext->isAuthenticated($activeProjectId)) {
                    if ($authContext->requiresPasswordChange($activeProjectId)) {
                        Yii::$app->session->setFlash('warning', 'Anda masih menggunakan password default. Disarankan segera mengganti password.');
                    }

                    if ($this->isAllowedEmbeddedPageFormPreview($route, $activeProjectId) || $this->isAllowedEmbeddedPageFormSubmit($route, $activeProjectId)) {
                        AuthContextDebugLogger::log('workspace_embedded_page_form_allowed', $this->buildEmbeddedFormDebugContext($route, $activeProjectId, true, true, 'page_content_authorized'));
                        return;
                    }

                    if ($this->isAllowedWorkspaceDashboardMenuAction($route, $activeProjectId)) {
                        AuthContextDebugLogger::log('workspace_dashboard_menu_action_allowed', $this->buildEmbeddedFormDebugContext($route, $activeProjectId, true, true, 'menu_visible_allowed'));
                        return;
                    }

                    if (!(new ProjectPermissionService())->canAccessRoute($route, $activeProjectId)) {
                        FormFlowDebugLogger::logAuth($this->buildFormAuthLogPayload(
                            $activeProjectId,
                            $this->resolveEmbeddedPageId(),
                            (int)Yii::$app->request->get('id', Yii::$app->request->post('id', 0)),
                            (string)Yii::$app->request->get('render_context', Yii::$app->request->post('render_context', '')),
                            (int)Yii::$app->request->post('_embedded', 0) === 1 || (string)Yii::$app->request->get('render_context', '') === 'page_content',
                            false,
                            false,
                            'ProjectAccessBootstrap',
                            'route_permission_denied'
                        ));
                        AuthContextDebugLogger::log('workspace_route_access_denied', $this->buildEmbeddedFormDebugContext($route, $activeProjectId, false, false, 'route_permission_denied'));
                        if (!Yii::$app->request->isAjax) {
                            Yii::$app->session->setFlash('error', 'Akses ditolak untuk role aplikasi Anda.');
                        }
                        $this->redirectSafely($event, Url::to(['project/access-denied', 'id' => $activeProjectId]), 'project_route_access_denied', $activeProjectId);
                        return;
                    }

                AuthContextDebugLogger::log('workspace_project_auth_allowed', [
                    'route' => $route,
                    'active_project_id' => $activeProjectId,
                    'project_auth' => true,
                ]);
                return;
            }

            AuthContextDebugLogger::log('workspace_project_auth_required', [
                'route' => $route,
                'active_project_id' => $activeProjectId,
            ]);
            $loginUrl = Url::to([
                'project/login',
                'id' => $activeProjectId,
                'return_url' => Yii::$app->request->url,
            ]);
            $this->redirectSafely($event, $loginUrl, 'project_auth_required', $activeProjectId, $authContext->getSessionKey($activeProjectId));
        });
    }

    private function isPublicRoute(string $route): bool
    {
        if ($route === '') {
            return true;
        }

        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if ($route === $publicRoute || strpos($route, $publicRoute . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedEmbeddedPageFormSubmit(string $route, int $activeProjectId): bool
    {
        if ($route !== 'master-form/submit' || !Yii::$app->request->isPost) {
            return false;
        }

        $renderContext = (string)Yii::$app->request->post('render_context', Yii::$app->request->get('render_context', ''));
        if ($renderContext === '' && ($this->resolveEmbeddedPageId() > 0 || $this->resolveEmbeddedMenuId() > 0)) {
            $renderContext = 'page_content';
        }
        if ($renderContext !== 'page_content') {
            return false;
        }

        $formId = (int)Yii::$app->request->get('id', Yii::$app->request->post('id', 0));
        $pageId = $this->resolveEmbeddedPageId();
        $permissionService = new ProjectPermissionService();
        $pageAuthorized = false;
        if ($pageId > 0) {
            $page = MasterPage::findOne($pageId);
            if ($page instanceof MasterPage) {
                $pageAuthorized = $permissionService->canAccessPage($page, $activeProjectId);
            }
        }
        $formAuthorized = $formId > 0 && $pageId > 0
            ? ($permissionService->canUseFormAsPageContent($formId, $pageId, $activeProjectId)
                || $permissionService->canUseLegacyFormAsPageContent($formId, $pageId, $activeProjectId))
            : false;

        if ($pageAuthorized || $formAuthorized) {
            FormFlowDebugLogger::logAuth($this->buildFormAuthLogPayload(
                $activeProjectId,
                $pageId,
                $formId,
                'page_content',
                true,
                $pageAuthorized || $formAuthorized,
                $formAuthorized,
                '',
                'allowed_by_page_context'
            ));
            return true;
        }

        FormFlowDebugLogger::logAuth($this->buildFormAuthLogPayload(
            $activeProjectId,
            $pageId,
            $formId,
            'page_content',
            true,
            $pageAuthorized,
            $formAuthorized,
            'ProjectAccessBootstrap',
            'embedded_submit_page_not_authorized'
        ));
        return false;
    }

    private function isAllowedEmbeddedPageFormPreview(string $route, int $activeProjectId): bool
    {
        if ($route !== 'master-page/form-preview' || Yii::$app->request->isPost) {
            return false;
        }

        $renderContext = (string)Yii::$app->request->get('render_context', Yii::$app->request->post('render_context', ''));
        if ($renderContext === '' && ($this->resolveEmbeddedPageId() > 0 || $this->resolveEmbeddedMenuId() > 0)) {
            $renderContext = 'page_content';
        }
        if ($renderContext !== 'page_content') {
            return false;
        }

        $formId = (int)Yii::$app->request->get('id', 0);
        $pageId = $this->resolveEmbeddedPageId();
        $permissionService = new ProjectPermissionService();
        $pageAuthorized = false;
        if ($pageId > 0) {
            $page = MasterPage::findOne($pageId);
            if ($page instanceof MasterPage) {
                $pageAuthorized = $permissionService->canAccessPage($page, $activeProjectId);
            }
        }
        $formAuthorized = $formId > 0 && $pageId > 0
            ? ($permissionService->canUseFormAsPageContent($formId, $pageId, $activeProjectId)
                || $permissionService->canUseLegacyFormAsPageContent($formId, $pageId, $activeProjectId))
            : false;

        if ($pageId <= 0) {
            FormFlowDebugLogger::logAuth($this->buildFormAuthLogPayload(
                $activeProjectId,
                $pageId,
                $formId,
                'page_content',
                true,
                false,
                false,
                'ProjectAccessBootstrap',
                'embedded_preview_missing_page_context'
            ));
            return false;
        }

        if ($pageAuthorized || $formAuthorized) {
            FormFlowDebugLogger::logAuth($this->buildFormAuthLogPayload(
                $activeProjectId,
                $pageId,
                $formId,
                'page_content',
                true,
                $pageAuthorized || $formAuthorized,
                $formAuthorized,
                '',
                'allowed_by_page_context'
            ));
            return true;
        }

        FormFlowDebugLogger::logAuth($this->buildFormAuthLogPayload(
            $activeProjectId,
            $pageId,
            $formId,
            'page_content',
            true,
            $pageAuthorized,
            $formAuthorized,
            'ProjectAccessBootstrap',
            'embedded_preview_page_not_authorized'
        ));
        return false;
    }

    private function isAllowedWorkspaceDashboardMenuAction(string $route, int $activeProjectId): bool
    {
        if (!in_array($route, ['dashboard/handle-menu', 'dashboard/get-forms', 'dashboard/render-page'], true)) {
            return false;
        }

        $menuId = (int)Yii::$app->request->get('menu_id', Yii::$app->request->post('menu_id', 0));
        if ($menuId <= 0) {
            return false;
        }

        $menu = MasterMenu::findOne($menuId);
        if (!$menu instanceof MasterMenu) {
            return false;
        }

        return (new ProjectPermissionService())->canAccessMenu($menu->toArray(), $activeProjectId);
    }

    private function resolveEmbeddedPageId(): int
    {
        $pageId = (int)Yii::$app->request->get('page_id', Yii::$app->request->post('page_id', 0));
        if ($pageId > 0) {
            return $pageId;
        }

        $menuId = $this->resolveEmbeddedMenuId();
        if ($menuId <= 0) {
            return 0;
        }

        $menu = MasterMenu::findOne($menuId);
        if ($menu === null || empty($menu->page_id)) {
            return $this->resolveEmbeddedPageIdFromReferer();
        }

        return (int)$menu->page_id;
    }

    private function resolveEmbeddedMenuId(): int
    {
        $menuId = (int)Yii::$app->request->get('menu_id', Yii::$app->request->post('menu_id', 0));
        if ($menuId > 0) {
            return $menuId;
        }

        $activeMenuId = (int)Yii::$app->session->get('active_menu', 0);
        if ($activeMenuId > 0) {
            return $activeMenuId;
        }

        $refererPath = $this->resolveRefererPath();
        if ($refererPath === '') {
            return 0;
        }

        $menu = MasterMenu::find()
            ->where(['is_active' => 1])
            ->andWhere(['route' => $refererPath])
            ->one();
        if ($menu instanceof MasterMenu) {
            return (int)$menu->id;
        }

        $menu = MasterMenu::find()
            ->where(['is_active' => 1])
            ->andWhere(['route' => ltrim($refererPath, '/')])
            ->one();
        if ($menu instanceof MasterMenu) {
            return (int)$menu->id;
        }

        return 0;
    }

    private function resolveEmbeddedPageIdFromReferer(): int
    {
        $refererPath = $this->resolveRefererPath();
        if ($refererPath === '') {
            return 0;
        }

        $path = trim($refererPath, '/');
        if ($path === '') {
            return 0;
        }

        $menu = MasterMenu::find()
            ->where(['is_active' => 1])
            ->andWhere(['route' => $path])
            ->one();
        if ($menu instanceof MasterMenu && !empty($menu->page_id)) {
            return (int)$menu->page_id;
        }

        $slug = basename($path);
        if ($slug !== '') {
            $page = MasterPage::findOne(['slug' => $slug]);
            if ($page instanceof MasterPage) {
                return (int)$page->id;
            }
        }

        return 0;
    }

    private function resolveRefererPath(): string
    {
        $referer = (string)Yii::$app->request->referrer;
        if ($referer === '') {
            return '';
        }

        $path = (string)parse_url($referer, PHP_URL_PATH);
        $path = trim($path, '/');
        if (strpos($path, 'index.php/') === 0) {
            $path = substr($path, strlen('index.php/'));
        }

        return trim($path, '/');
    }

    private function buildEmbeddedFormDebugContext(string $route, int $activeProjectId, bool $pageAuthorized, bool $formAuthorized, string $reason): array
    {
        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($activeProjectId);

        return [
            'route' => $route,
            'host' => (new DomainContext())->currentHost(),
            'project_id' => $activeProjectId,
            'role' => $user !== null ? strtolower(trim((string)$user->role)) : '',
            'page_id' => $this->resolveEmbeddedPageId(),
            'menu_id' => (int)Yii::$app->request->get('menu_id', Yii::$app->request->post('menu_id', 0)),
            'form_id' => (int)Yii::$app->request->get('id', Yii::$app->request->post('id', 0)),
            'render_context' => (string)Yii::$app->request->get('render_context', Yii::$app->request->post('render_context', '')),
            'page_authorized' => $pageAuthorized,
            'form_authorized' => $formAuthorized,
            'reason' => $reason,
        ];
    }

    private function buildFormAuthLogPayload(
        int $projectId,
        int $pageId,
        int $formId,
        string $renderContext,
        bool $embedded,
        bool $pageAuthorized,
        bool $formAuthorized,
        string $denySource,
        string $denyReason
    ): array {
        $authContext = new ProjectAuthContext();
        $user = $authContext->getAuthenticatedUser($projectId);

        return [
            'host' => (new DomainContext())->currentHost(),
            'project_id' => $projectId,
            'role' => $user !== null ? strtolower(trim((string)$user->role)) : '',
            'page_id' => $pageId,
            'form_id' => $formId,
            'render_context' => $renderContext,
            'embedded' => $embedded,
            'page_authorized' => $pageAuthorized,
            'form_authorized' => $formAuthorized,
            'deny_source' => $denySource,
            'deny_reason' => $denyReason,
        ];
    }

    private function isProtectedRoute(string $route): bool
    {
        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if ($route === $prefix || strpos($route, $prefix . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    private function redirectSafely(ActionEvent $event, string $targetUrl, string $reason, ?int $projectId = null, string $sessionKey = ''): void
    {
        $currentUrl = trim((string)Yii::$app->request->url, '/');
        $targetPath = trim((string)parse_url($targetUrl, PHP_URL_PATH), '/');
        $targetUrlComparable = trim($targetUrl, '/');

        if ($currentUrl === $targetPath || $currentUrl === $targetUrlComparable) {
            RedirectDebugLogger::log($reason . '_skipped_same_url', $targetUrl, $projectId, $sessionKey);
            return;
        }

        $event->isValid = false;
        $event->handled = true;
        RedirectDebugLogger::log($reason, $targetUrl, $projectId, $sessionKey);
        Yii::$app->response->redirect($targetUrl);
    }
}
