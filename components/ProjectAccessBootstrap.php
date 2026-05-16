<?php

namespace app\components;

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
            $route = trim((string)Yii::$app->requestedRoute, '/');
            if ($this->isPublicRoute($route)) {
                return;
            }

            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            if (!$this->isProtectedRoute($route)) {
                return;
            }

            if ($activeProjectId === null) {
                $event->isValid = false;
                $event->handled = true;
                Yii::$app->response->redirect(Url::to(['project/index']));
                return;
            }

            $commanderAuth = new CommanderAuthContext();
            if ($commanderAuth->isSuperAdmin()) {
                (new ActiveDatabaseContext())->resolveAndApply();
                return;
            }

            (new ActiveDatabaseContext())->resolveAndApply();
            $authContext = new ProjectAuthContext();
            if ($authContext->isAuthenticated($activeProjectId)) {
                if ($authContext->requiresPasswordChange($activeProjectId)) {
                    Yii::$app->session->setFlash('warning', 'Anda masih menggunakan password default. Disarankan segera mengganti password.');
                }

                if (!(new ProjectPermissionService())->canAccessRoute($route, $activeProjectId)) {
                    $event->isValid = false;
                    $event->handled = true;
                    Yii::$app->session->setFlash('error', 'Akses ditolak untuk role aplikasi Anda.');
                    Yii::$app->response->redirect(Url::to(['project/access-denied', 'id' => $activeProjectId]));
                    return;
                }

                return;
            }

            $event->isValid = false;
            $event->handled = true;
            $loginUrl = Url::to([
                'project/login',
                'id' => $activeProjectId,
                'return_url' => Yii::$app->request->url,
            ]);
            Yii::$app->response->redirect($loginUrl);
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

    private function isProtectedRoute(string $route): bool
    {
        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if ($route === $prefix || strpos($route, $prefix . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}
