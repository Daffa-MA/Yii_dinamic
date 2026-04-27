<?php

/**
 * @var string $activeMenu - Which menu item is active: 'home', 'dashboard', 'projects', 'projects-firebase-users', 'forms', 'tables', 'profile'
 */
use yii\bootstrap5\Html;
use app\components\ProjectSchema;

$activeMenu = $activeMenu ?? '';
$activeDatabase = Yii::$app->session->get('active_dashboard_database');
$activeProject = null;
$sidebarVariant = $sidebarVariant ?? (in_array($activeMenu, ['projects', 'projects-firebase-users'], true) ? 'minimal' : 'full');
$isMinimalSidebar = $sidebarVariant === 'minimal';
$headerBadge = $isMinimalSidebar ? 'Project Hub' : 'Workspace';
$headerTitle = $isMinimalSidebar ? 'Navigasi Project' : 'Projects';
$headerSubtitle = $isMinimalSidebar ? 'Pintu masuk workspace' : 'Beranda & navigasi';
$projectNavLabel = $isMinimalSidebar ? 'Home Project' : 'Projects';
$profileNavLabel = $isMinimalSidebar ? 'Akun Saya' : 'Profile';
$logoutLabel = $isMinimalSidebar ? 'Keluar Workspace' : 'Sign Out';
$activeProjectLabel = $isMinimalSidebar ? 'Project Aktif' : 'Active Project';
$activeDatabaseLabel = $isMinimalSidebar ? 'Database Aktif' : 'Database';

if (!Yii::$app->user->isGuest) {
    if (ProjectSchema::supportsProjectContext()) {
        $activeProjectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
        if ($activeProjectId !== null) {
            $activeProject = \app\models\Project::findOne(['id' => $activeProjectId, 'user_id' => Yii::$app->user->id]);
        }
    }
}
?>

<style>
    body.has-app-sidebar {
        --app-sidebar-expanded-width: 16rem;
        --app-sidebar-collapsed-width: 5.25rem;
        --app-sidebar-width: var(--app-sidebar-expanded-width);
    }

    body.has-app-sidebar.app-sidebar-collapsed {
        --app-sidebar-width: var(--app-sidebar-collapsed-width);
    }

    body.has-app-sidebar .left-64 {
        left: var(--app-sidebar-width) !important;
        transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.has-app-sidebar .pl-64 {
        padding-left: var(--app-sidebar-width) !important;
        transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.has-app-sidebar .project-home-shell {
        padding-left: var(--app-sidebar-width) !important;
        transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.has-app-sidebar main#main>.container>.alert {
        margin-left: var(--app-sidebar-width) !important;
        width: calc(100% - var(--app-sidebar-width)) !important;
        transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1), width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: var(--app-sidebar-width, var(--app-sidebar-expanded-width, 16rem));
        height: 100vh;
        z-index: 60;
        background: linear-gradient(180deg, #07111f 0%, #0b1220 48%, #111827 100%);
        border-right: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 12px 0 32px rgba(2, 6, 23, 0.18);
        color: #e2e8f0;
        display: flex;
        flex-direction: column;
        padding: 0;
        overflow-y: auto;
        overflow-x: hidden;
        transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
    }

    .app-sidebar-toggle {
        position: absolute;
        top: 18px;
        right: -14px;
        width: 30px;
        height: 30px;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #f8fafc;
        color: #1e293b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.24);
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        cursor: pointer;
        z-index: 2;
    }

    .app-sidebar-toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.3);
        background: #ffffff;
    }

    .app-sidebar-toggle .material-symbols-outlined {
        font-size: 18px;
        line-height: 1;
    }

    /* Header */
    .app-sidebar-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 16px 16px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        flex-shrink: 0;
    }

    .app-sidebar-header-link {
        text-decoration: none;
        color: inherit;
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .app-sidebar-header-link:hover {
        background: rgba(255, 255, 255, 0.04);
    }

    .app-sidebar-header-link.active {
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.18) 0%, rgba(14, 165, 233, 0.12) 100%);
    }

    .app-sidebar-header-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 12px 24px rgba(79, 70, 229, 0.28);
    }

    .app-sidebar-header-icon .material-symbols-outlined {
        color: white;
        font-size: 22px;
    }

    .app-sidebar-header-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin-bottom: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(148, 163, 184, 0.18);
        color: #cbd5e1;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .app-sidebar-header-text h2 {
        font-size: 15px;
        font-weight: 800;
        color: #f8fafc;
        margin: 0;
    }

    .app-sidebar-header-text p {
        font-size: 12px;
        color: #94a3b8;
        margin: 4px 0 0;
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .app-sidebar-header-text {
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    /* Context */
    .app-sidebar-context {
        padding: 14px 12px 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        flex-shrink: 0;
        max-height: 260px;
        transition: opacity 0.2s ease, max-height 0.2s ease, padding 0.2s ease, border-color 0.2s ease;
        overflow: hidden;
    }

    .app-sidebar-context-item-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        font-weight: 700;
        margin: 0;
    }

    .app-sidebar-context-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(15, 23, 42, 0.72);
        color: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, 0.14);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .app-sidebar-context-item .material-symbols-outlined {
        width: 26px;
        height: 26px;
        min-width: 26px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: #bfdbfe;
        background: rgba(79, 70, 229, 0.18);
    }

    /* Navigation */
    .app-sidebar-nav {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px;
        overflow-y: auto;
        min-height: 0;
    }

    .app-sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        color: #cbd5e1;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        min-height: 44px;
    }

    .app-sidebar-sublink {
        margin-left: 18px;
        min-height: 40px;
        padding-top: 10px;
        padding-bottom: 10px;
        font-size: 13px;
    }

    .app-sidebar-link .material-symbols-outlined {
        width: 24px;
        height: 24px;
        min-width: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #94a3b8;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
    }

    .app-sidebar-link-text {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: opacity 0.2s ease, width 0.2s ease;
    }

    .app-sidebar-link:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #ffffff;
        transform: translateX(3px);
    }

    .app-sidebar-link.active {
        background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
        color: white;
        box-shadow: 0 16px 30px rgba(37, 99, 235, 0.34);
        border-color: rgba(255, 255, 255, 0.12);
    }

    .app-sidebar-link.active .material-symbols-outlined,
    .app-sidebar-link:hover .material-symbols-outlined {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.12);
    }

    /* Footer */
    .app-sidebar-footer {
        padding: 12px;
        border-top: 1px solid rgba(148, 163, 184, 0.14);
        flex-shrink: 0;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.1) 0%, rgba(15, 23, 42, 0.22) 100%);
    }

    .app-sidebar-logout {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        color: #cbd5e1;
        transition: all 0.2s ease;
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: rgba(15, 23, 42, 0.68);
        cursor: pointer;
        min-height: 44px;
        width: 100%;
    }

    .app-sidebar-logout .material-symbols-outlined {
        width: 24px;
        height: 24px;
        min-width: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fda4af;
        background: rgba(251, 113, 133, 0.12);
        border-radius: 8px;
    }

    .app-sidebar-logout:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fecaca;
        transform: translateX(2px);
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar {
        box-shadow: 6px 0 18px rgba(2, 6, 23, 0.22);
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-header {
        justify-content: center;
        padding: 18px 10px 14px;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-header-text {
        opacity: 0;
        transform: translateX(-8px);
        width: 0;
        pointer-events: none;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-context {
        opacity: 0;
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        border-bottom-color: transparent;
        pointer-events: none;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-nav {
        padding: 12px 10px;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-link {
        justify-content: center;
        gap: 0;
        padding: 12px 10px;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-sublink {
        margin-left: 0;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-link:hover {
        transform: none;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-link-text {
        opacity: 0;
        width: 0;
        flex: 0 0 0;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-footer {
        padding: 12px 10px;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-logout {
        justify-content: center;
        gap: 0;
        padding: 12px 10px;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-logout:hover {
        transform: none;
    }

    /* Light theme for dashboard pages */
    body.dashboard-main-page .app-sidebar {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 48%, #eef2f7 100%);
        border-right: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: 12px 0 32px rgba(2, 6, 23, 0.08);
        color: #0f172a;
    }

    body.dashboard-main-page .app-sidebar-toggle {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-color: rgba(148, 163, 184, 0.3);
        color: #0f172a;
    }

    body.dashboard-main-page .app-sidebar-toggle:hover {
        background: #ffffff;
    }

    body.dashboard-main-page .app-sidebar-header {
        border-bottom-color: rgba(148, 163, 184, 0.18);
    }

    body.dashboard-main-page .app-sidebar-header-badge {
        background: rgba(59, 130, 246, 0.08);
        border-color: rgba(59, 130, 246, 0.24);
        color: #1d4ed8;
    }

    body.dashboard-main-page .app-sidebar-header-text h2 {
        color: #0f172a;
    }

    body.dashboard-main-page .app-sidebar-header-text p {
        color: #64748b;
    }

    body.dashboard-main-page .app-sidebar-context {
        border-bottom-color: rgba(148, 163, 184, 0.18);
    }

    body.dashboard-main-page .app-sidebar-context-item-label {
        color: #475569;
    }

    body.dashboard-main-page .app-sidebar-context-item {
        background: rgba(255, 255, 255, 0.7);
        border-color: rgba(148, 163, 184, 0.24);
        color: #0f172a;
    }

    body.dashboard-main-page .app-sidebar-context-item .material-symbols-outlined {
        color: #2563eb;
        background: rgba(59, 130, 246, 0.12);
    }

    body.dashboard-main-page .app-sidebar-link {
        color: #475569;
        border-color: rgba(148, 163, 184, 0.06);
    }

    body.dashboard-main-page .app-sidebar-link .material-symbols-outlined {
        color: #64748b;
        background: rgba(148, 163, 184, 0.08);
    }

    body.dashboard-main-page .app-sidebar-link:hover {
        background: rgba(148, 163, 184, 0.08);
        color: #0f172a;
    }

    body.dashboard-main-page .app-sidebar-link:hover .material-symbols-outlined {
        color: #1d4ed8;
        background: rgba(59, 130, 246, 0.12);
    }

    body.dashboard-main-page .app-sidebar-link.active {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 58%, #0284c7 100%);
        color: #ffffff;
        box-shadow: 0 12px 20px rgba(37, 99, 235, 0.28);
        border-color: rgba(59, 130, 246, 0.28);
    }

    body.dashboard-main-page .app-sidebar-link.active .material-symbols-outlined {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.24);
    }

    body.dashboard-main-page .app-sidebar-footer {
        border-top: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(248, 250, 252, 0.6);
    }

    body.dashboard-main-page .app-sidebar-logout {
        background: rgba(248, 250, 252, 0.8);
        border-color: rgba(148, 163, 184, 0.22);
        color: #475569;
    }

    body.dashboard-main-page .app-sidebar-logout .material-symbols-outlined {
        color: #f43f5e;
        background: rgba(244, 63, 94, 0.1);
    }

    body.dashboard-main-page .app-sidebar-logout:hover {
        background: #fee2e2;
        border-color: #fecaca;
        color: #991b1b;
    }

</style>

<aside class="app-sidebar">
    <button type="button" class="app-sidebar-toggle" data-sidebar-toggle aria-label="Tutup sidebar" aria-expanded="true" title="Tutup sidebar">
        <span class="material-symbols-outlined">chevron_left</span>
    </button>

    <!-- Header -->
    <div class="app-sidebar-header">
        <div class="app-sidebar-header-icon">
            <span class="material-symbols-outlined">folder_open</span>
        </div>
        <div class="app-sidebar-header-text">
            <span class="app-sidebar-header-badge"><?= Html::encode($headerBadge) ?></span>
            <h2><?= Html::encode($headerTitle) ?></h2>
            <p><?= Html::encode($headerSubtitle) ?></p>
        </div>
    </div>

    <!-- Context Info -->
    <?php if (!empty($activeDatabase) || $activeProject !== null): ?>
        <div class="app-sidebar-context">
            <?php if ($activeProject !== null): ?>
                <div>
                    <span class="app-sidebar-context-item-label"><?= Html::encode($activeProjectLabel) ?></span>
                    <div class="app-sidebar-context-item">
                        <span class="material-symbols-outlined">folder_open</span>
                        <span><?= Html::encode($activeProject->name) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($activeDatabase)): ?>
                <div>
                    <span class="app-sidebar-context-item-label"><?= Html::encode($activeDatabaseLabel) ?></span>
                    <div class="app-sidebar-context-item">
                        <span class="material-symbols-outlined">database</span>
                        <span><?= Html::encode($activeDatabase) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="app-sidebar-nav">
        <?php if ($sidebarVariant === 'full'): ?>
            <a href="<?= \yii\helpers\Url::to(['site/dashboard']) ?>" class="app-sidebar-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="app-sidebar-link-text">Dashboard</span>
            </a>
            <a href="<?= \yii\helpers\Url::to(['form/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'forms' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">description</span>
                <span class="app-sidebar-link-text">Forms</span>
            </a>
            <a href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'tables' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">table_chart</span>
                <span class="app-sidebar-link-text">Tables</span>
            </a>
            <a href="<?= \yii\helpers\Url::to(['published-form/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'published-forms' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">public</span>
                <span class="app-sidebar-link-text">Data Form</span>
            </a>
        <?php else: ?>
            <a href="<?= \yii\helpers\Url::to(['project/index']) ?>" class="app-sidebar-link <?= in_array($activeMenu, ['projects', 'projects-firebase-users'], true) ? 'active' : '' ?>">
                <span class="material-symbols-outlined">folder_open</span>
                <span class="app-sidebar-link-text"><?= Html::encode($projectNavLabel) ?></span>
            </a>

            <a href="<?= \yii\helpers\Url::to(['project/firebase-users']) ?>" class="app-sidebar-link app-sidebar-sublink <?= $activeMenu === 'projects-firebase-users' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">group</span>
                <span class="app-sidebar-link-text">User Firebase</span>
            </a>
        <?php endif; ?>
        <a href="<?= \yii\helpers\Url::to($sidebarVariant === 'minimal' ? ['project/profile'] : ['site/profile']) ?>" class="app-sidebar-link <?= $activeMenu === 'profile' ? 'active' : '' ?>">
            <span class="material-symbols-outlined">person</span>
            <span class="app-sidebar-link-text"><?= Html::encode($profileNavLabel) ?></span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="app-sidebar-footer">
        <?= Html::a(
            '<span class="material-symbols-outlined">logout</span><span class="app-sidebar-link-text">' . Html::encode($logoutLabel) . '</span>',
            ['site/logout'],
            [
                'class' => 'app-sidebar-logout',
                'data' => ['method' => 'post'],
                'encode' => false
            ]
        ) ?>
    </div>
</aside>

<script>
    (() => {
        const body = document.body;
        const sidebar = document.querySelector('.app-sidebar');
        if (!body || !sidebar) {
            return;
        }

        body.classList.add('has-app-sidebar');

        const toggleButton = sidebar.querySelector('[data-sidebar-toggle]');
        const toggleIcon = toggleButton ? toggleButton.querySelector('.material-symbols-outlined') : null;
        const storageKey = 'app.sidebar.collapsed';

        const applyCollapsedState = (collapsed) => {
            body.classList.toggle('app-sidebar-collapsed', collapsed);

            if (toggleButton) {
                const label = collapsed ? 'Buka sidebar' : 'Tutup sidebar';
                toggleButton.setAttribute('aria-expanded', String(!collapsed));
                toggleButton.setAttribute('aria-label', label);
                toggleButton.setAttribute('title', label);
            }

            if (toggleIcon) {
                toggleIcon.textContent = collapsed ? 'chevron_right' : 'chevron_left';
            }
        };

        const initialCollapsed = localStorage.getItem(storageKey) === '1';
        applyCollapsedState(initialCollapsed);

        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                const nextCollapsed = !body.classList.contains('app-sidebar-collapsed');
                applyCollapsedState(nextCollapsed);
                localStorage.setItem(storageKey, nextCollapsed ? '1' : '0');
            });
        }
    })();
</script>
