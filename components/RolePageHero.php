<?php

namespace app\components;

use app\models\Project;
use Yii;

class RolePageHero
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $pageTitle = '', array $context = [], ?int $projectId = null, string $route = ''): array
    {
        $projectContext = new ActiveProjectContext();
        $resolvedProjectId = $projectId ?? $projectContext->getActiveProjectId();
        $projectAuth = new ProjectAuthContext();
        $user = $resolvedProjectId !== null ? $projectAuth->getAuthenticatedUser($resolvedProjectId) : null;
        $identity = Yii::$app->user->identity;
        $commanderSuperAdmin = (new CommanderAuthContext())->isSuperAdmin();

        if ($user === null && $identity === null) {
            return ['should_render' => false];
        }

        if ($user !== null) {
            $role = strtolower(trim((string)$user->role));
            $username = trim((string)($user->username ?: $user->name ?: 'User'));
        } else {
            $role = strtolower(trim((string)($identity->role ?? '')));
            $username = trim((string)($identity->username ?? $identity->name ?? 'User'));
        }

        if ($role === '' && !$commanderSuperAdmin) {
            return ['should_render' => false];
        }

        $isAdminRole = $commanderSuperAdmin || in_array($role, ['admin', 'superadmin'], true);
        $scope = strtolower(trim((string)($context['scope'] ?? 'page')));
        if ($scope === '') {
            $scope = 'page';
        }

        $workspaceName = trim((string)($context['workspace_name'] ?? ''));
        if ($workspaceName === '' && $resolvedProjectId !== null) {
            $project = Project::findOne($resolvedProjectId);
            if ($project instanceof Project && trim((string)$project->name) !== '') {
                $workspaceName = (string)$project->name;
            }
        }
        if ($workspaceName === '') {
            $workspaceName = 'Workspace';
        }

        $resolvedTitle = trim((string)($context['page_title'] ?? $pageTitle));
        if ($resolvedTitle === '') {
            $resolvedTitle = $this->resolveTitleFromRoute($route !== '' ? $route : (string)(Yii::$app->controller->route ?? ''));
        }
        if ($resolvedTitle === '') {
            $resolvedTitle = 'Halaman';
        }

        $pageDescription = trim((string)($context['page_description'] ?? ''));
        $layout = trim((string)($context['layout'] ?? $context['page_layout'] ?? ''));
        $formCount = (int)($context['form_count'] ?? 0);
        $status = trim((string)($context['status'] ?? 'Active'));
        if ($status === '') {
            $status = 'Active';
        }

        $heroLabel = trim((string)($context['hero_label'] ?? ''));
        if ($heroLabel === '') {
            $heroLabel = $scope === 'form' ? 'Dynamic Form' : 'Dynamic Page';
        }

        $defaultDescription = $scope === 'dashboard'
            ? 'Selamat datang di halaman Dashboard. Silakan gunakan halaman ini sesuai kebutuhan Anda.'
            : "Selamat datang di halaman {$resolvedTitle}. Silakan gunakan halaman ini sesuai kebutuhan Anda.";

        if ($isAdminRole && $scope === 'dashboard') {
            return [
                'should_render' => true,
                'variant' => 'admin-dashboard',
                'icon' => 'dashboard',
                'title' => $resolvedTitle,
                'subtitle' => trim((string)($context['subtitle'] ?? 'Ringkasan workspace dan shortcut utama.')),
                'description' => $pageDescription !== '' ? $pageDescription : $defaultDescription,
                'workspace_name' => $workspaceName,
                'username' => $username,
                'role' => $role !== '' ? $role : 'admin',
                'status' => $status,
                'layout' => $layout !== '' ? $layout : 'dashboard',
                'form_count' => $formCount,
            ];
        }

        if (!$isAdminRole && $scope === 'dashboard') {
            return [
                'should_render' => true,
                'variant' => 'user-dashboard',
                'icon' => 'description',
                'title' => $resolvedTitle,
                'subtitle' => $workspaceName,
                'description' => $pageDescription !== '' ? $pageDescription : $defaultDescription,
                'workspace_name' => $workspaceName,
                'username' => $username,
                'role' => $role !== '' ? $role : 'user',
                'status' => $status,
            ];
        }

        if ($isAdminRole) {
            return [
                'should_render' => true,
                'variant' => 'admin-page',
                'icon' => 'dashboard_customize',
                'title' => $heroLabel,
                'subtitle' => $resolvedTitle,
                'description' => $pageDescription !== '' ? $pageDescription : 'Halaman dinamis yang dibangun menggunakan page builder.',
                'workspace_name' => $workspaceName,
                'username' => $username,
                'role' => $role !== '' ? $role : 'admin',
                'status' => $status,
                'layout' => $layout !== '' ? $layout : 'builder',
                'form_count' => $formCount,
            ];
        }

        return [
            'should_render' => true,
            'variant' => 'user-page',
            'icon' => 'description',
            'title' => $resolvedTitle,
            'subtitle' => $workspaceName,
            'username' => $username,
            'role' => $role,
            'workspace_name' => $workspaceName,
            'status' => $status,
            'description' => $pageDescription !== '' ? $pageDescription : $defaultDescription,
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
