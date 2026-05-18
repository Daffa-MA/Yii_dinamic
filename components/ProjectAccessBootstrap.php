<?php

namespace app\components;

use app\models\Project;
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

                if ($this->isAllowedEmbeddedPageFormSubmit($route, $activeProjectId)) {
                    AuthContextDebugLogger::log('workspace_embedded_page_form_allowed', [
                        'route' => $route,
                        'active_project_id' => $activeProjectId,
                        'page_id' => (int)Yii::$app->request->post('page_id', 0),
                        'form_id' => (int)Yii::$app->request->get('id', 0),
                    ]);
                    return;
                }

                if (!(new ProjectPermissionService())->canAccessRoute($route, $activeProjectId)) {
                    Yii::$app->session->setFlash('error', 'Akses ditolak untuk role aplikasi Anda.');
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

        if ((string)Yii::$app->request->post('render_context', '') !== 'page_content') {
            return false;
        }

        $formId = (int)Yii::$app->request->get('id', 0);
        $pageId = (int)Yii::$app->request->post('page_id', 0);

        return (new ProjectPermissionService())->canUseFormAsPageContent($formId, $pageId, $activeProjectId);
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
