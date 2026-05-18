<?php

namespace app\components;

use app\models\Project;
use Yii;
use yii\base\ActionEvent;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\helpers\Html;
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

    private const DEFAULT_HOST_SUFFIXES = [];

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
                $context = new ActiveProjectContext();
                $context->clearResolvedDomainProject();
                $context->setSuperAdminMode(false);
                return;
            }

            if (!$domainContext->isWorkspaceDomain($host) && !$this->isInfrastructureHost($host)) {
                RedirectDebugLogger::log('non_workspace_host_no_project_autodetect');
                return;
            }

            $prefix = $domainContext->extractWorkspacePrefix($host);
            $project = Project::findByCustomDomain($host);
            DomainDebugLogger::log($host, $prefix, $project, $project === null ? 'workspace_project_not_found' : 'workspace_project_resolved');
            if ($project === null) {
                $context = new ActiveProjectContext();
                $context->clearResolvedDomainProject();
                $context->setSuperAdminMode(false);
                if ($this->isInfrastructureHost($host)) {
                    return;
                }

                if ($domainContext->isWorkspaceDomain($host)) {
                    $this->renderWorkspaceNotFound($event, $host, $prefix);
                }
                return;
            }

            $context = new ActiveProjectContext();
            $context->setResolvedDomainProject((int)$project->id);
            try {
                (new ActiveDatabaseContext())->resolveAndApply();
            } catch (\Throwable $e) {
                DomainDebugLogger::log($host, $prefix, $project, 'active_database_apply_failed: ' . $e->getMessage());
            }

            if ((new CommanderAuthContext())->isSuperAdmin()) {
                $context->setActiveProject((int)$project->id);
                $context->setSuperAdminMode(true);
                (new ActiveDatabaseContext())->resolveAndApply();
            } else {
                $context->setSuperAdminMode(false);
            }

            AuthContextDebugLogger::log('domain_resolver_project_bound', [
                'host' => $host,
                'project_id' => (int)$project->id,
                'route' => $route,
                'superadmin' => (new CommanderAuthContext())->isSuperAdmin(),
            ]);

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

    private function renderWorkspaceNotFound(ActionEvent $event, string $host, string $prefix): void
    {
        $event->isValid = false;
        $event->handled = true;

        Yii::$app->response->statusCode = 404;
        Yii::$app->response->content = '<!doctype html><html lang="id"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Workspace not found</title>'
            . '<style>body{margin:0;font-family:"Avenir Next","Segoe UI",sans-serif;background:#f6f5f0;color:#171717;}'
            . '.wrap{min-height:100vh;display:grid;place-items:center;padding:32px}.card{max-width:680px;width:100%;background:#fff;border:1px solid #e5e1d8;border-radius:28px;box-shadow:0 24px 80px rgba(23,23,23,.08);padding:36px}'
            . '.eyebrow{font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#8a6f2a;margin-bottom:14px}.title{font-size:34px;line-height:1.05;margin:0 0 12px}.text{color:#606060;margin:0 0 24px;line-height:1.6}'
            . '.row{display:flex;gap:12px;border-top:1px solid #eee9df;padding-top:16px;margin-top:16px}.label{width:100px;color:#777;font-size:13px}.value{font-weight:700;word-break:break-all}</style></head><body>'
            . '<main class="wrap"><section class="card"><div class="eyebrow">Domain Debug</div>'
            . '<h1 class="title">Workspace not found</h1>'
            . '<p class="text">Domain ini sudah masuk ke resolver workspace, tetapi tidak ada project aktif yang cocok dengan prefix domain.</p>'
            . '<div class="row"><div class="label">Host</div><div class="value">' . Html::encode($host) . '</div></div>'
            . '<div class="row"><div class="label">Prefix</div><div class="value">' . Html::encode($prefix !== '' ? $prefix : '-') . '</div></div>'
            . '</section></main></body></html>';
    }
}
