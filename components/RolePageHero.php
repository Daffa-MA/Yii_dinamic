<?php

namespace app\components;

use app\models\Project;
use Yii;

class RolePageHero
{
    /**
     * @return array{should_render:bool,eyebrow?:string,username?:string,role?:string,page_title?:string,workspace_name?:string,status?:string,description?:string,info?:string}
     */
    public function build(string $pageTitle = '', ?int $projectId = null): array
    {
        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return ['should_render' => false];
        }

        $projectContext = new ActiveProjectContext();
        $resolvedProjectId = $projectId ?? $projectContext->getActiveProjectId();
        $projectAuth = new ProjectAuthContext();
        $user = $resolvedProjectId !== null ? $projectAuth->getAuthenticatedUser($resolvedProjectId) : null;

        if ($user === null) {
            $identity = Yii::$app->user->identity;
            if ($identity === null) {
                return ['should_render' => false];
            }

            $role = strtolower(trim((string)($identity->role ?? '')));
            if ($role === '' || in_array($role, ['admin', 'superadmin'], true)) {
                return ['should_render' => false];
            }

            $username = trim((string)($identity->username ?? $identity->name ?? 'User'));
        } else {
            $role = strtolower(trim((string)$user->role));
            if (in_array($role, ['admin', 'superadmin'], true)) {
                return ['should_render' => false];
            }

            $username = trim((string)($user->username ?: $user->name ?: 'User'));
        }

        $workspaceName = 'Workspace';
        if ($resolvedProjectId !== null) {
            $project = Project::findOne($resolvedProjectId);
            if ($project instanceof Project && trim((string)$project->name) !== '') {
                $workspaceName = (string)$project->name;
            }
        }

        $resolvedTitle = trim($pageTitle);
        if ($resolvedTitle === '') {
            $resolvedTitle = $this->resolveTitleFromRoute((string)(Yii::$app->controller->route ?? ''));
        }
        if ($resolvedTitle === '') {
            $resolvedTitle = 'Halaman';
        }

        return [
            'should_render' => true,
            'eyebrow' => 'Sekolah Negeri',
            'username' => $username,
            'role' => $role,
            'page_title' => $resolvedTitle,
            'workspace_name' => $workspaceName,
            'status' => 'Active',
            'description' => "Selamat datang di halaman {$resolvedTitle}. Silakan gunakan halaman ini sesuai kebutuhan Anda.",
            'info' => 'Akses informasi dan fitur yang tersedia untuk role Anda.',
        ];
    }

    private function resolveTitleFromRoute(string $route): string
    {
        $route = trim(strtolower($route), '/');
        if ($route === '') {
            return '';
        }

        $map = [
            'site/dashboard' => 'Dashboard',
            'site/profile' => 'Profil',
            'project/profile' => 'Profil',
            'page/view' => 'Page',
            'master-form/preview' => 'Form Siswa',
            'master-form/submit' => 'Form Siswa',
            'form/view' => 'Form',
            'published-form/index' => 'Page Siswa',
            'site/contact' => 'Kontak',
            'site/about' => 'Artikel',
        ];

        if (isset($map[$route])) {
            return $map[$route];
        }

        $segments = array_filter(array_map('ucfirst', preg_split('/[\/_-]+/', $route) ?: []));
        return trim(implode(' ', $segments));
    }
}
