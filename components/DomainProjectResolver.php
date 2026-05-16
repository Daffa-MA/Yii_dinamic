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
    ];

    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
            $host = $this->normalizeHost((string)Yii::$app->request->getHostName());
            if ($host === '') {
                return;
            }

            $project = Project::findByCustomDomain($host);
            if ($project === null) {
                if ($this->shouldFallbackToProjectList($host)) {
                    $route = trim((string)Yii::$app->requestedRoute, '/');
                    if ($route === '' || $route === 'site/index' || $route === 'site/login' || $route === 'project/index') {
                        $event->isValid = false;
                        $event->handled = true;
                        Yii::$app->session->setFlash('warning', 'Project untuk domain ini belum diatur.');
                        Yii::$app->response->redirect(Url::to(['project/index']));
                    }
                }
                return;
            }

            $context = new ActiveProjectContext();
            $context->setResolvedDomainProject((int)$project->id);

            if (!Yii::$app->user->isGuest || (new CommanderAuthContext())->isSuperAdmin()) {
                $context->setActiveProject((int)$project->id);
                (new ActiveDatabaseContext())->resolveAndApply();
            }

            $route = trim((string)Yii::$app->requestedRoute, '/');
            $authContext = new ProjectAuthContext();
            if ($route === '' || $route === 'site/index' || $route === 'site/login') {
                $event->isValid = false;
                $event->handled = true;

                if ((new CommanderAuthContext())->isSuperAdmin()) {
                    Yii::$app->response->redirect(Url::to(['site/dashboard']));
                    return;
                }

                if ($authContext->isAuthenticated((int)$project->id)) {
                    Yii::$app->response->redirect(Url::to(['site/dashboard']));
                    return;
                }

                Yii::$app->response->redirect(Url::to(['project/login', 'id' => (int)$project->id]));
                return;
            }

            if ($route === 'project/index' && !(new CommanderAuthContext())->isSuperAdmin()) {
                $event->isValid = false;
                $event->handled = true;
                if ($authContext->isAuthenticated((int)$project->id)) {
                    Yii::$app->response->redirect(Url::to(['site/dashboard']));
                    return;
                }

                Yii::$app->response->redirect(Url::to(['project/login', 'id' => (int)$project->id]));
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

    private function shouldFallbackToProjectList(string $host): bool
    {
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return false;
        }

        return strpos($host, '.') !== false;
    }
}
