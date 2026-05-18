<?php
use yii\helpers\Html;
use app\models\WorkspaceSettings;
use app\components\ProjectAuthContext;
use app\components\CommanderAuthContext;
use app\components\DomainContext;

$workspaceSettings = new WorkspaceSettings();
$workspaceSettings->loadFromDatabase();
$cssVars = $workspaceSettings->getCssVars();

$activeDatabase = Yii::$app->session->get('active_dashboard_database');
$activeProject = null;
$activeProjectId = null;
$projectAuthUser = null;
if (\app\components\ProjectSchema::supportsProjectContext()) {
    $activeProjectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
    if ($activeProjectId !== null) {
        $activeProject = \app\models\Project::findOne(['id' => $activeProjectId]);
        $projectAuthUser = (new ProjectAuthContext())->getAuthenticatedUser($activeProjectId);
    }
}

$currentRoute = Yii::$app->controller->route;
$pageTitle = $this->title ?? 'Dashboard';

$breadcrumbs = [];
$breadcrumbs[] = ['label' => 'Home', 'url' => ['site/dashboard']];
$breadcrumbs[] = ['label' => $pageTitle];

$profileUsername = 'User';
$profileRole = 'Member';
$profileAvatar = 'U';
$commanderAuth = new CommanderAuthContext();
$domainContext = new DomainContext();
$isRootDomain = $domainContext->isRootDomain();
$isWorkspaceDomain = $domainContext->isWorkspaceDomain();
$canOpenProjectList = $commanderAuth->isSuperAdmin();
$projectListUrl = $domainContext->projectListUrl();
if ($isRootDomain) {
    $commanderUser = $commanderAuth->getUser();
    if ($commanderUser !== null) {
        $profileUsername = (string)($commanderUser->username ?? 'admin');
        $profileRole = 'Superadmin';
        $profileAvatar = strtoupper(substr($profileUsername, 0, 1));
    } elseif ($commanderAuth->isAuthenticated()) {
        $profileUsername = 'admin';
        $profileRole = 'Superadmin';
        $profileAvatar = 'A';
    }
} elseif ($commanderAuth->isSuperAdmin() && $isWorkspaceDomain) {
    $commanderUser = $commanderAuth->getUser();
    if ($commanderUser !== null) {
        $profileUsername = (string)($commanderUser->username ?? 'admin');
        $profileAvatar = strtoupper(substr($profileUsername, 0, 1));
    } else {
        $profileUsername = 'admin';
        $profileAvatar = 'A';
    }
    $profileRole = 'Commander Mode';
} elseif ($projectAuthUser !== null) {
    $profileUsername = (string)$projectAuthUser->username;
    $profileRole = $projectAuthUser->role !== '' ? ucfirst(str_replace(['_', '-'], ' ', (string)$projectAuthUser->role)) : 'Admin';
    $profileAvatar = strtoupper(substr($profileUsername, 0, 1));
} elseif ($commanderAuth->isAuthenticated()) {
    $commanderUser = $commanderAuth->getUser();
    $profileUsername = $commanderUser !== null ? (string)($commanderUser->username ?? 'User') : 'User';
    $commanderRole = $commanderAuth->getRole();
    $profileRole = $commanderRole === 'superadmin'
        ? ($isWorkspaceDomain ? 'Commander Mode' : 'Superadmin')
        : ucfirst(str_replace(['_', '-'], ' ', $commanderRole !== '' ? $commanderRole : 'Commander'));
    $profileAvatar = strtoupper(substr($profileUsername, 0, 1));
} elseif ($isWorkspaceDomain && $activeProjectId !== null) {
    $profileUsername = 'Workspace';
    $profileRole = 'Active Session';
}
?>

<style>
    .app-topnav {
        position: fixed;
        top: 0;
        left: var(--app-sidebar-width, 16rem);
        right: 0;
        height: 64px;
        background: var(--ws-topnav-bg);
        border-bottom: 1px solid var(--ws-topnav-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        z-index: 50;
        transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    
    body.has-app-sidebar.app-sidebar-collapsed .app-topnav {
        left: var(--app-sidebar-collapsed-width, 5.25rem);
    }
    
    body:not(.dashboard-main-page) .app-topnav {
        display: none;
    }
    
    .app-topnav-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .app-topnav-projects-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 10px;
        border: 1px solid var(--ws-topnav-border-color);
        background: #ffffff;
        color: var(--ws-topnav-text-color);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .app-topnav-projects-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    
    .app-topnav-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--ws-topnav-text-color);
    }
    
    .app-topnav-breadcrumb a {
        color: #94a3b8;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .app-topnav-breadcrumb a:hover {
        color: #4f46e5;
    }
    
    .app-topnav-breadcrumb .separator {
        color: #cbd5e1;
    }
    
    .app-topnav-breadcrumb .current {
        font-weight: 600;
        color: var(--ws-topnav-text-color);
    }
    
    .app-topnav-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .app-topnav-search {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .app-topnav-search input {
        width: 280px;
        height: 40px;
        padding: 0 16px 0 40px;
        border: 1px solid var(--ws-topnav-border-color);
        border-radius: 10px;
        font-size: 14px;
        background: #f8fafc;
        color: var(--ws-topnav-text-color);
        transition: all 0.2s;
    }
    
    .app-topnav-search input:focus {
        outline: none;
        border-color: #3b82f6;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .app-topnav-search input::placeholder {
        color: #94a3b8;
    }
    
    .app-topnav-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid var(--ws-topnav-border-color);
        background: #ffffff;
        color: var(--ws-topnav-text-color);
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        text-decoration: none;
    }
    
    .app-topnav-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    
    .app-topnav-btn .material-symbols-outlined {
        font-size: 20px;
    }
    
    .app-topnav-btn .badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
    }
    
    .app-topnav-divider {
        width: 1px;
        height: 32px;
        background: var(--ws-topnav-border-color);
    }
    
    .app-topnav-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .app-topnav-profile:hover {
        background: #f1f5f9;
    }
    
    .app-topnav-profile .avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--ws-workspace-logo-bg) 0%, var(--ws-workspace-logo-bg) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 14px;
    }
    
    .app-topnav-profile .info {
        display: flex;
        flex-direction: column;
    }
    
    .app-topnav-profile .name {
        font-size: 14px;
        font-weight: 600;
        color: var(--ws-topnav-text-color);
    }
    
    .app-topnav-profile .role {
        font-size: 12px;
        color: #94a3b8;
    }
    
    @media (max-width: 768px) {
        .app-topnav-search {
            display: none;
        }
        
        .app-topnav {
            padding: 0 16px;
        }
    }
</style>

<div class="app-topnav">
    <div class="app-topnav-left">
        <?php if ($canOpenProjectList): ?>
            <a href="<?= Html::encode($projectListUrl) ?>" class="app-topnav-projects-btn" title="Kembali ke Project List">
                <span class="material-symbols-outlined">folder</span>
                Kembali ke Project List
            </a>
        <?php endif; ?>
        
        <div class="app-topnav-breadcrumb">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i > 0): ?>
                    <span class="separator">/</span>
                <?php endif; ?>
                <?php if (isset($crumb['url'])): ?>
                    <a href="<?= \yii\helpers\Url::to($crumb['url']) ?>"><?= Html::encode($crumb['label']) ?></a>
                <?php else: ?>
                    <span class="current"><?= Html::encode($crumb['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="app-topnav-right">
        <div class="app-topnav-search">
            <span class="material-symbols-outlined">search</span>
            <input type="text" placeholder="Search..." />
        </div>
        
        <a href="#" class="app-topnav-btn" title="Notifications">
            <span class="material-symbols-outlined">notifications</span>
            <span class="badge">3</span>
        </a>
        
        <a href="#" class="app-topnav-btn" title="Help">
            <span class="material-symbols-outlined">help</span>
        </a>
        
        <div class="app-topnav-divider"></div>
        
        <a href="<?= \yii\helpers\Url::to(['settings/workspace']) ?>" class="app-topnav-btn" title="Workspace Settings">
            <span class="material-symbols-outlined">settings</span>
        </a>
        
        <div class="app-topnav-profile">
            <div class="avatar">
                <?= Html::encode($profileAvatar) ?>
            </div>
            <div class="info">
                <span class="name"><?= Html::encode($profileUsername) ?></span>
                <span class="role"><?= Html::encode($profileRole) ?></span>
            </div>
        </div>
    </div>
</div>
