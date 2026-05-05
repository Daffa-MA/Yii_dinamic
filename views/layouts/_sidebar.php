<?php
use yii\bootstrap5\Html;
use app\components\ProjectSchema;
use app\models\MasterMenu;
use app\models\MasterPage;

$this->registerJsFile('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['position' => \yii\web\View::POS_END]);

$activeMenu = $activeMenu ?? '';
$activeDatabase = Yii::$app->session->get('active_dashboard_database');
$activeProject = null;

// Detect if we're on project-list page (minimal sidebar)
$currentRoute = Yii::$app->controller->route;
$isProjectListPage = ($currentRoute === 'project/index' || $currentRoute === 'project-list/index');
$sidebarVariant = $isProjectListPage ? 'minimal' : 'full';
$isMinimalSidebar = $sidebarVariant === 'minimal';
$headerBadge = $isMinimalSidebar ? 'Project Hub' : 'Workspace';
$headerTitle = $isMinimalSidebar ? 'Navigasi Project' : 'Projects';
$headerSubtitle = $isMinimalSidebar ? 'Pintu masuk workspace' : 'Beranda & navigasi';
$projectNavLabel = $isMinimalSidebar ? 'Projects' : 'Projects';
$profileNavLabel = $isMinimalSidebar ? 'Akun Saya' : 'Profile';
$logoutLabel = $isMinimalSidebar ? 'Keluar Workspace' : 'Sign Out';
$activeProjectLabel = $isMinimalSidebar ? 'Project Aktif' : 'Active Project';
$activeDatabaseLabel = $isMinimalSidebar ? 'Database Aktif' : 'Database';

// Resolve database context first so we can query the correct database
$dbContext = new \app\components\ActiveDatabaseContext();
$dbContext->resolveAndApply();

if (!Yii::$app->user->isGuest) {
    if (ProjectSchema::supportsProjectContext()) {
        $activeProjectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
        if ($activeProjectId !== null) {
            $activeProject = \app\models\Project::findOne(['id' => $activeProjectId, 'user_id' => Yii::$app->user->id]);
        }
    }
}

$menuItems = [];
try {
    $menuItems = MasterMenu::getMenuTree(true);
} catch (\Exception $e) {
    Yii::error('Failed to load menu tree: ' . $e->getMessage());
}

$reservedMenuKeys = [
    'home',
    'dashboard',
    'firebase-users',
    'forms',
    'tables',
    'published-forms',
    'profile',
    'projects',
];

$sessionActiveMenu = (string) Yii::$app->session->get('active_menu', '');
$dynamicActiveMenu = (string) $activeMenu;

if ($dynamicActiveMenu === '') {
    $dynamicActiveMenu = $sessionActiveMenu;
}

if (!function_exists('renderDynamicSidebarTree')) {
    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{html: string, active: bool}
     */
    function renderDynamicSidebarTree(array $items, string $activeMenu, string $sessionActiveMenu): array
    {
        $html = '';
        $hasActiveBranch = false;

        foreach ($items as $item) {
            $itemId = isset($item['id']) ? (string) $item['id'] : '';
            $itemName = strtolower(trim((string) ($item['name'] ?? '')));
            $hasChildren = !empty($item['children']);
            $icon = htmlspecialchars((string) ($item['icon'] ?? 'folder'), ENT_QUOTES, 'UTF-8');

            $matchesItem = static function (string $candidate) use ($itemId, $itemName): bool {
                $normalized = strtolower(trim($candidate));
                if ($normalized === '') {
                    return false;
                }

                return ($itemId !== '' && ($normalized === $itemId || $normalized === 'menu-' . $itemId))
                    || ($itemName !== '' && $normalized === $itemName);
            };

            $childState = ['html' => '', 'active' => false];
            if ($hasChildren) {
                $childState = renderDynamicSidebarTree($item['children'], $activeMenu, $sessionActiveMenu);
            }

            $isCurrent = $matchesItem($activeMenu) || $matchesItem($sessionActiveMenu);
            $isActiveBranch = $isCurrent || $childState['active'];
            $hasActiveBranch = $hasActiveBranch || $isActiveBranch;

            $linkClasses = 'app-sidebar-link' . ($hasChildren ? ' has-children' : '') . ($isActiveBranch ? ' active' : '') . ($hasChildren && $isActiveBranch ? ' expanded' : '');

            if ($hasChildren) {
                $html .= '<a href="#" class="' . $linkClasses . '" data-menu-id="' . htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') . '">' . "\n";
                $html .= '    <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
                $html .= '    <span class="app-sidebar-link-text">' . htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>' . "\n";
                $html .= '    <span class="app-sidebar-chevron material-symbols-outlined" style="margin-left:auto">expand_more</span>' . "\n";
                $html .= '</a>' . "\n";
                $html .= '<div class="sub-menu">' . "\n";
                $html .= $childState['html'];
                $html .= '</div>' . "\n";
                continue;
            }

            $url = '#';
            if (is_array($item['url'] ?? null) && !empty($item['url'])) {
                $url = \yii\helpers\Url::to($item['url']);
            } elseif (is_string($item['url'] ?? null) && $item['url'] !== '' && $item['url'] !== '#') {
                $url = \yii\helpers\Url::to($item['url']);
            }

            $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="' . $linkClasses . '" data-menu-id="' . htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            $html .= '    <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
            $html .= '    <span class="app-sidebar-link-text">' . htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>' . "\n";
            $html .= '</a>' . "\n";
        }

        return [
            'html' => $html,
            'active' => $hasActiveBranch,
        ];
    }
}

$dynamicMenuTree = renderDynamicSidebarTree($menuItems, $dynamicActiveMenu, $dynamicActiveMenu);
?>

<style>
    .app-sidebar-nav * {
        pointer-events: auto !important;
    }
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
        position: relative;
        z-index: 10;
    }

    .app-sidebar-link {
        display: flex;
        position: relative;
        z-index: 20;
        pointer-events: auto;
        cursor: pointer;
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

    .app-sidebar-link.has-children {
        justify-content: space-between;
    }

    .sub-menu {
        display: none;
        flex-direction: column;
        gap: 6px;
        margin-top: 6px;
        margin-left: 16px;
        padding-left: 8px;
        border-left: 1px solid rgba(255,255,255,0.1);
    }

    .app-sidebar-link.has-children.expanded + .sub-menu {
        display: flex;
    }

    .app-sidebar-link.has-children.expanded .app-sidebar-chevron {
        transform: rotate(180deg);
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
        border-color: #fecaca;
        color: #991b1b;
        transform: translateX(2px);
    }

    .app-sidebar-system-builder {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        margin-top: 4px;
    }

    body.has-app-sidebar.app-sidebar-collapsed .app-sidebar-system-builder {
        display: none;
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
        <!-- Projects Page - Minimal: Hardcoded Only -->
        <?php if ($sidebarVariant === 'minimal'): ?>
            <!-- Projects (clickable) -->
            <a href="<?= \yii\helpers\Url::to(['project/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'projects' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">folder_open</span>
                <span class="app-sidebar-link-text"><?= Html::encode($projectNavLabel) ?></span>
            </a>
            
            <!-- Akun Saya -->
            <a href="<?= \yii\helpers\Url::to(['site/profile']) ?>" class="app-sidebar-link <?= $activeMenu === 'profile' ? 'active' : '' ?>">
                <span class="material-symbols-outlined">person</span>
                <span class="app-sidebar-link-text"><?= Html::encode($profileNavLabel) ?></span>
            </a>
         
<!-- Full Sidebar - Dashboard Pages -->
        <?php else: ?>
            <?php 
            // Build menu tree with parent-child hierarchy and type support
            // Use ActiveQuery instead of raw SQL to avoid column issues
            $menuModels = \app\models\MasterMenu::find()
                ->where(['is_active' => 1])
                ->orderBy(['sort_order' => SORT_ASC])
                ->all();
            
            // Convert to array for easier processing
            $allMenus = [];
            foreach ($menuModels as $m) {
                $allMenus[] = [
                    'id' => $m->getAttribute('id'),
                    'parent_id' => $m->getAttribute('parent_id'),
                    'type' => $m->getAttribute('type'),
                    'page_id' => $m->getAttribute('page_id'),
                    'name' => $m->getAttribute('name'),
                    'icon' => $m->getAttribute('icon'),
                    'route' => $m->getAttribute('route'),
                ];
            }
            
            // Build tree structure
            $menuTree = [];
            $menuMap = [];
            foreach ($allMenus as $m) {
                $menuMap[$m['id']] = $m;
                $menuMap[$m['id']]['children'] = [];
            }
            foreach ($allMenus as $m) {
                if ($m['parent_id'] && isset($menuMap[$m['parent_id']])) {
                    $menuMap[$m['parent_id']]['children'][] = $m;
                } else {
                    $menuTree[] = &$menuMap[$m['id']];
                }
            }
            
            function renderMenuItem($item, &$menuMap) {
                $icon = $item['icon'] ?: 'folder';
                $type = $item['type'] ?? 'page';
                $route = $item['route'] ?? '';
                $pageId = $item['page_id'] ?? null;
                
                // Determine URL based on type
                $url = '#';
                if ($type === 'route' && !empty($route)) {
                    $url = $route[0] === '/' ? $route : '/' . ltrim($route, '/');
                } elseif ($type === 'page' && !empty($pageId)) {
                    $url = ['/page/view', 'id' => $pageId];
                }
                
                $hasChildren = !empty($item['children']) || $type === 'group';
                
                if ($hasChildren) {
                    echo '<a href="#" class="app-sidebar-link has-children" data-menu-id="' . $item['id'] . '">';
                    echo '<span class="material-symbols-outlined">' . Html::encode($icon) . '</span>';
                    echo '<span class="app-sidebar-link-text">' . Html::encode($item['name']) . '</span>';
                    echo '<span class="app-sidebar-chevron material-symbols-outlined" style="margin-left:auto">expand_more</span>';
                    echo '</a>';
                    echo '<div class="sub-menu">';
                    if (!empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            renderMenuItem($child, $menuMap);
                        }
                    }
                    echo '</div>';
                } else {
                    $urlFinal = is_array($url) ? \yii\helpers\Url::to($url) : $url;
                    echo '<a href="' . Html::encode($urlFinal) . '" class="app-sidebar-link" data-menu-id="' . $item['id'] . '">';
                    echo '<span class="material-symbols-outlined">' . Html::encode($icon) . '</span>';
                    echo '<span class="app-sidebar-link-text">' . Html::encode($item['name']) . '</span>';
                    echo '</a>';
                }
            }
            
            if (!empty($menuTree)) {
                foreach ($menuTree as $topMenu) {
                    renderMenuItem($topMenu, $menuMap);
                }
            } else {
                echo '<div style="color: #94a3b8; padding: 10px; text-align: center; font-size: 12px;">No active menus</div>';
            }
            ?>
<?php endif; ?>
            <!-- SYSTEM BUILDER - HARDCODED (di bawah menu dinamis) -->
            <?php if ($sidebarVariant === 'full'): ?>
                <div style="border-top: 1px solid rgba(148, 163, 184, 0.14); margin: 12px 0;"></div>
                <div class="app-sidebar-system-builder" style="padding: 0 14px;">
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">System Builder</span>
                </div>
                <a href="<?= \yii\helpers\Url::to(['master-menu/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'master-menu' ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">list_alt</span>
                    <span class="app-sidebar-link-text">Master Menu</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['master-page/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'master-page' ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">description</span>
                    <span class="app-sidebar-link-text">Master Page</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['master-form/index']) ?>" class="app-sidebar-link <?= $activeMenu === 'master-form' ? 'active' : '' ?>">
                    <span class="material-symbols-outlined">dynamic_form</span>
                    <span class="app-sidebar-link-text">Master Form</span>
                </a>
            <?php endif; ?>
    </nav>

<!-- Footer -->
    <div class="app-sidebar-footer <?= $sidebarVariant === 'minimal' ? 'mt-auto' : '' ?>">
        <?php if ($sidebarVariant === 'minimal'): ?>
            <!-- Minimal mode - Red logout button -->
            <?= Html::beginForm(['/site/logout'], 'post') ?>
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors text-sm font-medium">
                    <span class="material-symbols-outlined">logout</span>
                    <span><?= Html::encode($logoutLabel) ?></span>
                </button>
            <?= Html::endForm() ?>
        <?php else: ?>
            <?= Html::a(
                '<span class="material-symbols-outlined">logout</span><span class="app-sidebar-link-text">' . Html::encode($logoutLabel) . '</span>',
                ['site/logout'],
                [
                    'class' => 'app-sidebar-logout',
                    'data' => ['method' => 'post'],
                    'encode' => false
                ]
            ) ?>
        <?php endif; ?>
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

        sidebar.querySelectorAll('.app-sidebar-link.has-children').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();

                if (body.classList.contains('app-sidebar-collapsed')) {
                    body.classList.remove('app-sidebar-collapsed');
                    localStorage.setItem(storageKey, '0');
                    applyCollapsedState(false);
                }

                link.classList.toggle('expanded');
            });
        });
    })();
</script>
