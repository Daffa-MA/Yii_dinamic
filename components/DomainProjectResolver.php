<?php

namespace app\components;

use app\models\Project;
use Yii;
use yii\base\ActionEvent;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\helpers\Url;

class DomainProjectResolver implements BootstrapInterface
{
    private const PUBLIC_ROUTES = [
        'site/error',
        'site/login',
        'site/logout',
        'project/index',
        'project/select',
        'project/login',
        'project/access-denied',
        'project/change-password',
        'project/logout',
        'project-list',
    ];

    private const DEFAULT_HOSTS = [
        'localhost',
        '127.0.0.1',
    ];

    private const DEFAULT_HOST_SUFFIXES = [
        '.sslip.io',
    ];

    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
            $domainContext = new DomainContext();
            $host = $domainContext->currentHost();
            if ($host === '') {
                return;
            }

            $route = trim((string)Yii::$app->requestedRoute, '/');
            $isIgnoredRoute = $this->isIgnoredRoute($route);

            if ($domainContext->isRootDomain($host)) {
                (new ActiveProjectContext())->clearResolvedDomainProject();
                return;
            }

            if (!$domainContext->isWorkspaceDomain($host) && !$this->isInfrastructureHost($host)) {
                RedirectDebugLogger::log('non_workspace_host_no_project_autodetect');
                return;
            }

            $project = Project::findByCustomDomain($host);
            if ($project === null) {
                if ($this->isInfrastructureHost($host)) {
                    return;
                }

                if ($this->shouldFallbackToProjectList($route)) {
                    $targetUrl = Url::to(['project/index']);
                    if ($this->redirectSafely($event, $targetUrl, 'workspace_domain_without_project')) {
                        Yii::$app->session->setFlash('warning', 'Project untuk domain ini belum diatur.');
                    }
                }
                return;
            }

            $context = new ActiveProjectContext();
            $context->setResolvedDomainProject((int)$project->id);

            if ((new CommanderAuthContext())->isSuperAdmin()) {
                $context->setActiveProject((int)$project->id);
                (new ActiveDatabaseContext())->resolveAndApply();
            }

            if ($isIgnoredRoute) {
                return;
            }

            $authContext = new ProjectAuthContext();
            if ($route === '' || $route === 'site/index' || $route === 'site/login') {
                if ((new CommanderAuthContext())->isSuperAdmin()) {
                    $this->redirectSafely($event, Url::to(['site/dashboard']), 'workspace_superadmin_dashboard', (int)$project->id);
                    return;
                }

                if ($authContext->isAuthenticated((int)$project->id)) {
                    $this->redirectSafely($event, Url::to(['site/dashboard']), 'workspace_project_authenticated_dashboard', (int)$project->id);
                    return;
                }

                $this->redirectSafely($event, Url::to(['project/login', 'id' => (int)$project->id]), 'workspace_project_login_required', (int)$project->id);
                return;
            }

            if ($route === 'project/index' && !(new CommanderAuthContext())->isSuperAdmin()) {
                if ($authContext->isAuthenticated((int)$project->id)) {
                    $this->redirectSafely($event, Url::to(['site/dashboard']), 'workspace_project_list_authenticated_dashboard', (int)$project->id);
                    return;
                }

                $this->redirectSafely($event, Url::to(['project/login', 'id' => (int)$project->id]), 'workspace_project_list_login_required', (int)$project->id);
            }
        });
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $host = trim($host);

        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host;
    }

    private function shouldFallbackToProjectList(string $route): bool
    {
        return $route === '' || $route === 'site/index' || $route === 'site/login' || $route === 'project/index';
    }

    private function isIgnoredRoute(string $route): bool
    {
        if ($route === '') {
            return false;
        }

        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if ($route === $publicRoute || strpos($route, $publicRoute . '/') === 0) {
                return true;
            }
        }

        if ($route === 'assets' || strpos($route, 'assets/') === 0) {
            return true;
        }

        if ($route === 'debug' || strpos($route, 'debug/') === 0) {
            return true;
        }

        if ($route === 'gii' || strpos($route, 'gii/') === 0) {
            return true;
        }

        return false;
    }

    private function isInfrastructureHost(string $host): bool
    {
        if (in_array($host, self::DEFAULT_HOSTS, true)) {
            return true;
        }

        foreach (self::DEFAULT_HOST_SUFFIXES as $suffix) {
            if ($suffix !== '' && substr($host, -strlen($suffix)) === $suffix) {
                return true;
            }
        }

        return false;
    }

    private function shouldRedirectTo(string $targetUrl): bool
    {
        $currentUrl = trim((string)Yii::$app->request->url, '/');
        $targetUrl = trim($targetUrl, '/');

        return $currentUrl !== $targetUrl;
    }

    private function redirectSafely(ActionEvent $event, string $targetUrl, string $reason, ?int $projectId = null): bool
    {
        if (!$this->shouldRedirectTo($targetUrl)) {
            RedirectDebugLogger::log($reason . '_skipped_same_url', $targetUrl, $projectId);
            return false;
        }

        $event->isValid = false;
        $event->handled = true;
        RedirectDebugLogger::log($reason, $targetUrl, $projectId);
        Yii::$app->response->redirect($targetUrl);
        return true;
    }
}
