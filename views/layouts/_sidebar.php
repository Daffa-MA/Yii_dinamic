<?php
use yii\bootstrap5\Html;
use app\components\ProjectSchema;
use app\components\ProjectAuthContext;
use app\components\CommanderAuthContext;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\WorkspaceSettings;

$workspaceSettings = new WorkspaceSettings();
$workspaceSettings->loadFromSession();
$cssVars = $workspaceSettings->getCssVars();

// Helper function for exact route matching
function normalizePath($path) {
    if ($path === null || $path === false) {
        return '/';
    }
    $normalized = strtolower(trim((string) $path));
    $normalized = rtrim($normalized, '/');
    return empty($normalized) ? '/' : $normalized;
}

// Check if routes match exactly
function routesMatchExactly($currentRoute, $menuRoute) {
    $current = normalizePath($currentRoute);
    $target = normalizePath($menuRoute);
    return $current === $target;
}

$activeMenu = $activeMenu ?? '';
$currentRoute = Yii::$app->controller->route;

// Auto-detect active menu untuk System Builder routes
$systemBuilderRoutes = [
    'master-menu' => 'master-menu',
    'master-page' => 'master-page', 
    'master-form' => 'master-form',
    'table-builder' => 'table-builder'
];

foreach ($systemBuilderRoutes as $prefix => $menuKey) {
    if (strpos($currentRoute, $prefix) === 0) {
        $activeMenu = $menuKey;
        break;
    }
}

$activeDatabase = Yii::$app->session->get('active_dashboard_database');
$activeProject = null;
$activeProjectId = null;
$projectAuthUser = null;

// Hardcoded selector pages must stay isolated from workspace DB/theme switching.
$isProjectListPage = ($currentRoute === 'project/index' || $currentRoute === 'project-list/index');
$shouldResolveWorkspaceDatabase = !$isProjectListPage;
$sidebarVariant = $isProjectListPage ? 'minimal' : 'full';
$isMinimalSidebar = $sidebarVariant === 'minimal';

// Use workspace settings or defaults
$headerBadge = $isMinimalSidebar ? $cssVars['workspace-badge'] ?? 'Project Hub' : ($cssVars['workspace-badge'] ?? 'Workspace');
$headerTitle = $isMinimalSidebar ? 'Navigasi Project' : ($cssVars['workspace-title'] ?? 'Project List');
$headerSubtitle = $isMinimalSidebar ? 'Pintu masuk workspace' : ($cssVars['workspace-subtitle'] ?? 'Beranda & navigasi');
$projectNavLabel = $isMinimalSidebar ? 'Projects' : 'Project List';
$profileNavLabel = $isMinimalSidebar ? 'Akun Saya' : 'Profile';
$activeProjectLabel = $isMinimalSidebar ? 'Project Aktif' : 'Active Project';
$activeDatabaseLabel = $isMinimalSidebar ? 'Database Aktif' : 'Database';

// Resolve database context only for dynamic workspace layouts.
// Project selector pages must remain on the neutral/default database.
if ($shouldResolveWorkspaceDatabase) {
    $dbContext = new \app\components\ActiveDatabaseContext();
    $dbContext->resolveAndApply();
}

if (!Yii::$app->user->isGuest) {
    if (ProjectSchema::supportsProjectContext()) {
        $activeProjectId = (new \app\components\ActiveProjectContext())->getActiveProjectId();
        if ($activeProjectId !== null) {
            $activeProject = \app\models\Project::findOne(['id' => $activeProjectId, 'user_id' => Yii::$app->user->id]);
            $projectAuthUser = (new ProjectAuthContext())->getAuthenticatedUser($activeProjectId);
        }
    }
}

$commanderAuth = new CommanderAuthContext();
$canOpenProjectList = $commanderAuth->isSuperAdmin();
$this->registerJsFile('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['position' => \yii\web\View::POS_END]);

$logoutUrl = \yii\helpers\Url::to(['site/logout']);
$projectListUrl = \yii\helpers\Url::to(['project/index']);

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

// REMOVED: No more session-based active menu tracking
// Only route-based exact matching is used now
$dynamicActiveMenu = '';

$currentRoute = Yii::$app->controller->route;

// REWRITTEN: Use EXACT route matching only - NO name/label matching
if (!function_exists('renderDynamicSidebarTree')) {
    /**
     * @param array<int, array<string, mixed>> $items
     * @param string $currentRoute
     * @return array{html: string, active: bool}
     */
    function renderDynamicSidebarTree(array $items, string $currentRoute): array
    {
        $html = '';
        $hasActiveBranch = false;
        
        // Track activated items to prevent duplicates
        $activatedRoutes = [];
        $activatedPageIds = [];
        $activatedFormIds = [];
        
        // Normalize path helper
        $normalizePath = function($path) {
            if ($path === null || $path === false) return '/';
            $normalized = strtolower(trim((string) $path));
            $normalized = rtrim($normalized, '/');
            return empty($normalized) ? '/' : $normalized;
        };
        
        // Check if current route EXACTLY matches menu route
        $routeMatches = function($menuRoute) use ($currentRoute, $normalizePath) {
            if (empty($menuRoute)) return false;
            return $normalizePath($currentRoute) === $normalizePath($menuRoute);
        };
        
        // Check if menu is active based on EXACT route only, with duplicate prevention
        $isMenuActive = function($item) use ($routeMatches, &$activatedRoutes, &$activatedPageIds, &$activatedFormIds) {
            $type = $item['type'] ?? '';
            $route = $item['route'] ?? '';
            $pageId = $item['page_id'] ?? null;
            $formId = $item['form_id'] ?? null;
            
            if ($type === 'route' && !empty($route)) {
                // EXACT route matching - prevent duplicate routes
                $normalizedRoute = $normalizePath($route);
                if (in_array($normalizedRoute, $activatedRoutes)) {
                    return false; // Another menu already matched this exact route
                }
                if ($routeMatches($route)) {
                    $activatedRoutes[] = $normalizedRoute;
                    return true;
                }
            }
            
            if ($type === 'page' && !empty($pageId)) {
                // Page matching - prevent duplicate page_ids
                if (in_array($pageId, $activatedPageIds)) {
                    return false;
                }
                if ($routeMatches('page/view')) {
                    $pageIdFromRoute = Yii::$app->request->get('id');
                    if ($pageIdFromRoute == $pageId) {
                        $activatedPageIds[] = $pageId;
                        return true;
                    }
                }
            }
            
            if ($type === 'form' && !empty($formId)) {
                // Form matching - prevent duplicate form_ids
                if (in_array($formId, $activatedFormIds)) {
                    return false;
                }
                if ($routeMatches('master-form/preview') || $routeMatches('form/view')) {
                    $formIdFromRoute = Yii::$app->request->get('id');
                    if ($formIdFromRoute == $formId) {
                        $activatedFormIds[] = $formId;
                        return true;
                    }
                }
            }
            
            return false;
        };
        
        // Check if any child has active state (recursive)
        $hasActiveChild = function($item) use (&$hasActiveChild, $isMenuActive) {
            if ($isMenuActive($item)) return true;
            $children = $item['children'] ?? [];
            if (!empty($children) && is_array($children)) {
                foreach ($children as $child) {
                    if ($hasActiveChild($child)) return true;
                }
            }
            return false;
        };

        foreach ($items as $item) {
            $itemId = isset($item['id']) ? (string) $item['id'] : '';
            $hasChildren = !empty($item['children']) || ($item['type'] ?? '') === 'group';
            $icon = htmlspecialchars((string) ($item['icon'] ?? 'folder'), ENT_QUOTES, 'UTF-8');

            // EXACT route matching only - NO name comparison
            $isCurrentActive = $isMenuActive($item);
            $childActive = $hasChildren ? $hasActiveChild($item) : false;
            $isActiveBranch = $isCurrentActive || $childActive;
            $hasActiveBranch = $hasActiveBranch || $isActiveBranch;

            $linkClasses = 'app-sidebar-link';
            if ($hasChildren) $linkClasses .= ' has-children';
            if ($isActiveBranch) $linkClasses .= ' active';
            if ($hasChildren && $childActive && !$isCurrentActive) $linkClasses .= ' parent-has-active';
            if ($hasChildren && ($isCurrentActive || $childActive)) $linkClasses .= ' expanded';

            if ($hasChildren) {
                $html .= '<a href="#" class="' . $linkClasses . '" data-menu-id="' . htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') . '">' . "\n";
                $html .= '    <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
                $html .= '    <span class="app-sidebar-link-text">' . htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>' . "\n";
                $html .= '    <span class="app-sidebar-chevron material-symbols-outlined" style="margin-left:auto">expand_more</span>' . "\n";
                $html .= '</a>' . "\n";
                $html .= '<div class="sub-menu">' . "\n";
                $html .= renderDynamicSidebarTree($item['children'] ?? [], $currentRoute)['html'];
                $html .= '</div>' . "\n";
                continue;
            }

            // Build URL for leaf menu
            $url = '#';
            if (is_array($item['url'] ?? null) && !empty($item['url'])) {
                $url = \yii\helpers\Url::to($item['url']);
            } elseif (is_string($item['url'] ?? null) && $item['url'] !== '' && $item['url'] !== '#') {
                $url = \yii\helpers\Url::to($item['url']);
            } elseif (!empty($item['form_id'])) {
                $url = \yii\helpers\Url::to(['/master-form/preview', 'id' => (int) $item['form_id']]);
            } elseif (($item['type'] ?? '') !== 'group' && !empty($itemId)) {
                $url = \yii\helpers\Url::to(['/master-menu/resolve-link', 'id' => (int) $itemId]);
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

?>

<style data-sidebar-version="2.0">
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
        background: linear-gradient(180deg, var(--ws-sidebar-bg-start) 0%, var(--ws-sidebar-bg-end) 100%);
        border-right: 1px solid var(--ws-sidebar-border-color);
        box-shadow: 12px 0 32px rgba(2, 6, 23, 0.18);
        color: var(--ws-sidebar-text-color);
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
        border-bottom: 1px solid var(--ws-sidebar-border-color);
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
        background: linear-gradient(135deg, var(--ws-workspace-logo-bg) 0%, var(--ws-workspace-logo-bg) 100%);
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
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        /* color removed - using inline style */
    }

    .app-sidebar-header-text h2 {
        font-size: 15px;
        font-weight: 800;
        margin: 0;
        /* color removed - using inline style */
    }

    .app-sidebar-header-text p {
        font-size: 12px;
        margin: 4px 0 0;
        transition: opacity 0.2s ease, transform 0.2s ease;
        /* color removed - using inline style */
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
        border-bottom: 1px solid var(--ws-sidebar-border-color);
        flex-shrink: 0;
        max-height: 260px;
        transition: opacity 0.2s ease, max-height 0.2s ease, padding 0.2s ease, border-color 0.2s ease;
        overflow: hidden;
    }

    .app-sidebar-context-item-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--ws-sidebar-text-muted);
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
        color: var(--ws-sidebar-text-color);
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
        color: var(--ws-sidebar-text-color);
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        transition: all 0.2s ease;
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
        background: var(--ws-sidebar-hover-bg);
        color: var(--ws-sidebar-hover-text);
        transform: translateX(3px);
    }

    /* ACTIVE STATE - Modern SaaS Style (Unified for all menus) */
    .app-sidebar-link.active,
    .app-sidebar-link.is-active {
        background: linear-gradient(135deg, var(--ws-sidebar-active-bg-start) 0%, var(--ws-sidebar-active-bg-end) 50%, var(--ws-sidebar-active-bg-end) 100%);
        color: var(--ws-sidebar-active-text) !important;
        font-weight: 600;
        border: none;
        box-shadow: var(--ws-sidebar-active-shadow);
        transform: translateX(3px);
    }

    .app-sidebar-link.active .material-symbols-outlined,
    .app-sidebar-link.is-active .material-symbols-outlined {
        color: white !important;
        background: rgba(255, 255, 255, 0.2);
    }

    .app-sidebar-link.active:hover .material-symbols-outlined,
    .app-sidebar-link.is-active:hover .material-symbols-outlined {
        background: rgba(255, 255, 255, 0.25);
    }

    /* Parent group with active child - subtle highlight */
    .app-sidebar-link.parent-has-active {
        background: rgba(255, 255, 255, 0.04);
        color: var(--ws-sidebar-text-color);
    }

    .app-sidebar-link.parent-has-active .material-symbols-outlined {
        color: var(--ws-sidebar-text-muted);
        background: rgba(255, 255, 255, 0.08);
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
        border-left: 1px solid var(--ws-sidebar-border-color);
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
        border-top: 1px solid var(--ws-sidebar-border-color);
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
        color: var(--ws-sidebar-text-color);
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
        background: linear-gradient(180deg, var(--ws-light-sidebar-bg) 0%, var(--ws-light-sidebar-bg) 48%, var(--ws-light-sidebar-bg) 100%);
        border-right: 1px solid var(--ws-light-sidebar-border);
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
        /* color removed - using inline style from workspace settings */
        border-color: rgba(148, 163, 184, 0.06);
    }

body.dashboard-main-page .app-sidebar-link .material-symbols-outlined {
        color: var(--ws-sidebar-text-color);
        background: rgba(148, 163, 184, 0.08);
    }
    
    body.dashboard-main-page .app-sidebar-link:hover {
        background: rgba(148, 163, 184, 0.08);
        color: var(--ws-sidebar-hover-text);
    }
    
    body.dashboard-main-page .app-sidebar-link:hover .material-symbols-outlined {
        color: var(--ws-sidebar-hover-text);
        background: rgba(59, 130, 246, 0.12);
    }

    /* Light Theme Active State - Unified */
    body.dashboard-main-page .app-sidebar-link.active,
    body.dashboard-main-page .app-sidebar-link.is-active {
        background: linear-gradient(135deg, var(--ws-sidebar-active-bg-start) 0%, var(--ws-sidebar-active-bg-end) 50%, var(--ws-sidebar-active-bg-end) 100%);
        color: var(--ws-sidebar-active-text) !important;
        font-weight: 600;
        border: none;
        box-shadow: var(--ws-sidebar-active-shadow);
        transform: translateX(3px);
    }

    body.dashboard-main-page .app-sidebar-link.active .material-symbols-outlined,
    body.dashboard-main-page .app-sidebar-link.is-active .material-symbols-outlined {
        color: white !important;
        background: rgba(255, 255, 255, 0.2);
    }

    body.dashboard-main-page .app-sidebar-link.active:hover,
    body.dashboard-main-page .app-sidebar-link.is-active:hover {
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.32);
    }

    body.dashboard-main-page .app-sidebar-link.parent-has-active {
        background: rgba(59, 130, 246, 0.06);
        color: #1e293b;
    }

    body.dashboard-main-page .app-sidebar-link.parent-has-active .material-symbols-outlined {
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }

    body.dashboard-main-page .app-sidebar-footer {
        border-top: 1px solid var(--ws-light-sidebar-border);
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

    /* Hardcoded minimal sidebar for project list */
    body.project-page-v4 .app-sidebar {
        background: linear-gradient(180deg, #0b1220 0%, #111827 56%, #0f172a 100%);
        border-right: 1px solid rgba(148, 163, 184, 0.12);
        box-shadow: 12px 0 32px rgba(2, 6, 23, 0.28);
        color: #e5e7eb;
    }

    body.project-page-v4 .app-sidebar-toggle {
        background: linear-gradient(135deg, #1f2937 0%, #0f172a 100%);
        border-color: rgba(148, 163, 184, 0.18);
        color: #e5e7eb;
    }

    body.project-page-v4 .app-sidebar-toggle:hover {
        background: #172033;
    }

    body.project-page-v4 .app-sidebar-header {
        border-bottom-color: rgba(148, 163, 184, 0.12);
    }

    body.project-page-v4 .app-sidebar-header-badge {
        background: rgba(148, 163, 184, 0.08);
        border-color: rgba(148, 163, 184, 0.14);
    }

    body.project-page-v4 .app-sidebar-context {
        border-bottom-color: rgba(148, 163, 184, 0.12);
    }

    body.project-page-v4 .app-sidebar-context-item-label {
        color: #94a3b8;
    }

    body.project-page-v4 .app-sidebar-context-item {
        background: rgba(15, 23, 42, 0.72);
        border-color: rgba(148, 163, 184, 0.12);
        color: #e5e7eb;
    }

    body.project-page-v4 .app-sidebar-context-item .material-symbols-outlined {
        color: #cbd5e1;
        background: rgba(148, 163, 184, 0.12);
    }

    body.project-page-v4 .app-sidebar-link {
        /* color removed - using inline style from workspace settings */
        border-color: rgba(148, 163, 184, 0.04);
    }

body.project-page-v4 .app-sidebar-link .material-symbols-outlined {
        color: var(--ws-sidebar-text-color);
        background: rgba(148, 163, 184, 0.08);
    }
    
    body.project-page-v4 .app-sidebar-link:hover {
        background: rgba(148, 163, 184, 0.10);
        color: var(--ws-sidebar-hover-text);
    }
    
    body.project-page-v4 .app-sidebar-link:hover .material-symbols-outlined {
        color: var(--ws-sidebar-hover-text);
        background: rgba(148, 163, 184, 0.16);
    }

    body.project-page-v4 .app-sidebar-link.active,
    body.project-page-v4 .app-sidebar-link.is-active {
        background: linear-gradient(135deg, #1f2937 0%, #334155 100%);
        color: #f8fafc !important;
        font-weight: 600;
        border: none;
        box-shadow: 0 10px 24px rgba(2, 6, 23, 0.28);
        transform: translateX(3px);
    }

    body.project-page-v4 .app-sidebar-link.active .material-symbols-outlined,
    body.project-page-v4 .app-sidebar-link.is-active .material-symbols-outlined {
        color: #f8fafc !important;
        background: rgba(255, 255, 255, 0.12);
    }

    body.project-page-v4 .app-sidebar-link.parent-has-active {
        background: rgba(148, 163, 184, 0.08);
        color: #e2e8f0;
    }

    body.project-page-v4 .app-sidebar-link.parent-has-active .material-symbols-outlined {
        color: #cbd5e1;
        background: rgba(148, 163, 184, 0.10);
    }

    body.project-page-v4 .app-sidebar-footer {
        border-top-color: rgba(148, 163, 184, 0.12);
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.18) 0%, rgba(15, 23, 42, 0.34) 100%);
    }

    body.project-page-v4 .app-sidebar-logout {
        background: rgba(15, 23, 42, 0.72);
        border-color: rgba(148, 163, 184, 0.12);
        color: #cbd5e1;
    }

    body.project-page-v4 .app-sidebar-logout .material-symbols-outlined {
        color: #fb7185;
        background: rgba(251, 113, 133, 0.12);
    }

    body.project-page-v4 .app-sidebar-logout:hover {
        background: rgba(30, 41, 59, 0.96);
        border-color: rgba(148, 163, 184, 0.16);
        color: #f8fafc;
    }

</style>

<?php
$sidebarBgStart = $isMinimalSidebar ? '#0b1220' : ($cssVars['sidebar-bg-start'] ?? '#07111f');
$sidebarBgEnd = $isMinimalSidebar ? '#111827' : ($cssVars['sidebar-bg-end'] ?? '#111827');
$sidebarBorderColor = $isMinimalSidebar ? 'rgba(148, 163, 184, 0.12)' : ($cssVars['sidebar-border-color'] ?? 'rgba(148, 163, 184, 0.16)');
$sidebarTextColor = $isMinimalSidebar ? '#e5e7eb' : ($cssVars['sidebar-text-color'] ?? '#e2e8f0');
$sidebarTextMuted = $isMinimalSidebar ? '#94a3b8' : ($cssVars['sidebar-text-muted'] ?? '#94a3b8');
$sidebarHoverBg = $isMinimalSidebar ? 'rgba(148, 163, 184, 0.10)' : ($cssVars['sidebar-hover-bg'] ?? 'rgba(255, 255, 255, 0.08)');
$sidebarHoverText = $isMinimalSidebar ? '#f8fafc' : ($cssVars['sidebar-hover-text'] ?? '#ffffff');
$sidebarActiveBgStart = $isMinimalSidebar ? '#1f2937' : ($cssVars['sidebar-active-bg-start'] ?? '#2563eb');
$sidebarActiveBgEnd = $isMinimalSidebar ? '#334155' : ($cssVars['sidebar-active-bg-end'] ?? '#06b6d4');
$sidebarActiveText = $isMinimalSidebar ? '#f8fafc' : ($cssVars['sidebar-active-text'] ?? '#ffffff');
$sidebarActiveShadow = $isMinimalSidebar ? '0 10px 24px rgba(2, 6, 23, 0.28)' : ($cssVars['sidebar-active-shadow'] ?? '0 8px 24px rgba(37, 99, 235, 0.28)');
$logoBg = $isMinimalSidebar ? '#334155' : ($cssVars['workspace-logo-bg'] ?? '#4f46e5');
$logoImage = $cssVars['workspace-logo-image'] ?? null;

// Debug: Log color values
Yii::info('Sidebar Text Color: ' . $sidebarTextColor, 'sidebar-debug');
Yii::info('Sidebar Text Muted: ' . $sidebarTextMuted, 'sidebar-debug');
Yii::info('CSS Vars sidebar-text-color: ' . ($cssVars['sidebar-text-color'] ?? 'NOT SET'), 'sidebar-debug');
Yii::info('CSS Vars sidebar-text-muted: ' . ($cssVars['sidebar-text-muted'] ?? 'NOT SET'), 'sidebar-debug');
Yii::info('Is Minimal Sidebar: ' . ($isMinimalSidebar ? 'YES' : 'NO'), 'sidebar-debug');
Yii::info('Current Route: ' . $currentRoute, 'sidebar-debug');
?>

<!-- Debug Info: Text Colors -->
<!-- sidebarTextColor: <?= Html::encode($sidebarTextColor) ?> -->
<!-- sidebarTextMuted: <?= Html::encode($sidebarTextMuted) ?> -->
<!-- cssVars sidebar-text-color: <?= Html::encode($cssVars['sidebar-text-color'] ?? 'NOT SET') ?> -->
<!-- cssVars sidebar-text-muted: <?= Html::encode($cssVars['sidebar-text-muted'] ?? 'NOT SET') ?> -->
<!-- isMinimalSidebar: <?= $isMinimalSidebar ? 'YES' : 'NO' ?> -->
<!-- Current Route: <?= Html::encode($currentRoute) ?> -->
<!-- CACHE BUSTER: v2.0 - <?= date('Y-m-d H:i:s') ?> -->

<aside class="app-sidebar" style="background: linear-gradient(180deg, <?= Html::encode($sidebarBgStart) ?> 0%, <?= Html::encode($sidebarBgEnd) ?> 100%); border-color: <?= Html::encode($sidebarBorderColor) ?>; color: <?= Html::encode($sidebarTextColor) ?>;">
    <button type="button" class="app-sidebar-toggle" data-sidebar-toggle aria-label="Tutup sidebar" aria-expanded="true" title="Tutup sidebar">
        <span class="material-symbols-outlined">chevron_left</span>
    </button>

    <!-- Header -->
    <div class="app-sidebar-header" style="border-color: <?= Html::encode($sidebarBorderColor) ?>;">
        <div id="sidebar-logo-box" class="app-sidebar-header-icon" style="width: <?= Html::encode($workspaceSettings->workspace_logo_width ?? 44) ?>px; height: <?= Html::encode($workspaceSettings->workspace_logo_height ?? 44) ?>px; background: <?= !empty($logoImage) ? 'transparent' : 'linear-gradient(135deg, ' . Html::encode($logoBg) . ' 0%, ' . Html::encode($logoBg) . ' 100%)' ?>; box-shadow: <?= !empty($logoImage) ? 'none' : '0 12px 24px rgba(79, 70, 229, 0.28)' ?>; font-size: <?= round((($workspaceSettings->workspace_logo_width ?? 44) / 44) * 22) ?>px;">
            <?php if (!empty($logoImage)): ?>
                <img id="sidebar-logo-image" src="<?= Yii::getAlias('@web/uploads/workspace/') . Html::encode($logoImage) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 14px;">
            <?php else: ?>
                <span id="sidebar-logo-icon" class="material-symbols-outlined"><?= Html::encode($cssVars['workspace-logo-icon'] ?? 'folder_open') ?></span>
            <?php endif; ?>
        </div>
        <div class="app-sidebar-header-text">
            <span class="app-sidebar-header-badge" style="color: <?= Html::encode($sidebarTextMuted) ?> !important; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(148, 163, 184, 0.18);"><?= Html::encode($headerBadge) ?></span>
            <h2 style="color: <?= Html::encode($sidebarTextColor) ?> !important; font-size: 15px; font-weight: 800; margin: 0;"><?= Html::encode($headerTitle) ?></h2>
            <p style="color: <?= Html::encode($sidebarTextMuted) ?> !important; font-size: 12px; margin: 4px 0 0;"><?= Html::encode($headerSubtitle) ?></p>
        </div>
    </div>

    <!-- Context Info -->
    <?php if (!empty($activeDatabase) || $activeProject !== null): ?>
        <div class="app-sidebar-context">
            <?php if ($activeProject !== null): ?>
                <div>
                    <span class="app-sidebar-context-item-label" style="color: <?= Html::encode($sidebarTextMuted) ?>;"><?= Html::encode($activeProjectLabel) ?></span>
                    <div class="app-sidebar-context-item" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                        <span class="material-symbols-outlined">folder_open</span>
                        <span><?= Html::encode($activeProject->name) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($activeDatabase)): ?>
                <div>
                    <span class="app-sidebar-context-item-label" style="color: <?= Html::encode($sidebarTextMuted) ?>;"><?= Html::encode($activeDatabaseLabel) ?></span>
                    <div class="app-sidebar-context-item" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                        <span class="material-symbols-outlined">database</span>
                        <span><?= Html::encode($activeDatabase) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<!-- Navigation -->
    <nav class="app-sidebar-nav" style="color: <?= Html::encode($sidebarTextColor) ?>;">
        <!-- Projects Page - Minimal: Hardcoded Only -->
        <?php if ($sidebarVariant === 'minimal'): ?>
            <!-- Projects (clickable) -->
            <a href="<?= \yii\helpers\Url::to(['project/index']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'project/index') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                <span class="material-symbols-outlined">folder_open</span>
                <span class="app-sidebar-link-text"><?= Html::encode($projectNavLabel) ?></span>
            </a>
            
            <!-- Akun Saya -->
            <a href="<?= \yii\helpers\Url::to(['site/profile']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'site/profile') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
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
                    'form_id' => $m->getAttribute('form_id'),
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
            
            // Track which specific menu items/IDs have been activated to prevent duplicates
            $activatedIds = [];
            $activatedPageIds = [];
            $activatedFormIds = [];
            $activatedRoutes = [];
            
            // Helper closures for EXACT route matching
            $normalizePath = function($path) {
                if ($path === null || $path === false) return '/';
                $normalized = strtolower(trim((string) $path));
                $normalized = rtrim($normalized, '/');
                return empty($normalized) ? '/' : $normalized;
            };
            
            $routeMatches = function($menuRoute) use ($currentRoute, $normalizePath) {
                if (empty($menuRoute)) return false;
                return $normalizePath($currentRoute) === $normalizePath($menuRoute);
            };
            
            // Check if menu is active based on EXACT route only, with duplicate prevention
            $isMenuActive = function($item) use ($routeMatches, $normalizePath, &$activatedIds, &$activatedPageIds, &$activatedFormIds, &$activatedRoutes) {
                $type = $item['type'] ?? '';
                $route = $item['route'] ?? '';
                $pageId = $item['page_id'] ?? null;
                $formId = $item['form_id'] ?? null;
                $itemId = $item['id'] ?? null;
                
                // Prevent same item from being checked twice
                if ($itemId && in_array($itemId, $activatedIds)) {
                    return false;
                }
                
                if ($type === 'route' && !empty($route)) {
                    // EXACT route matching - prevent duplicate routes
                    $normalizedRoute = $normalizePath($route);
                    if (in_array($normalizedRoute, $activatedRoutes)) {
                        return false; // Another menu already matched this exact route
                    }
                    if ($routeMatches($route)) {
                        $activatedIds[] = $itemId;
                        $activatedRoutes[] = $normalizedRoute;
                        return true;
                    }
                }
                
                if ($type === 'page' && !empty($pageId)) {
                    // Page matching - prevent duplicate page_ids
                    if (in_array($pageId, $activatedPageIds)) {
                        return false; // Another menu already matched this page
                    }
                    if ($routeMatches('page/view')) {
                        $pageIdFromRoute = Yii::$app->request->get('id');
                        if ($pageIdFromRoute == $pageId) {
                            $activatedIds[] = $itemId;
                            $activatedPageIds[] = $pageId;
                            return true;
                        }
                    }
                }
                
                if ($type === 'form' && !empty($formId)) {
                    // Form matching - prevent duplicate form_ids
                    if (in_array($formId, $activatedFormIds)) {
                        return false;
                    }
                    if ($routeMatches('master-form/preview') || $routeMatches('form/view')) {
                        $formIdFromRoute = Yii::$app->request->get('id');
                        if ($formIdFromRoute == $formId) {
                            $activatedIds[] = $itemId;
                            $activatedFormIds[] = $formId;
                            return true;
                        }
                    }
                }
                
                return false;
            };
            
            // Check if any child has active state (recursive, also tracks duplicates)
            $hasActiveChild = function($item) use (&$hasActiveChild, $isMenuActive, $routeMatches, $normalizePath, &$activatedIds, &$activatedPageIds, &$activatedFormIds, &$activatedRoutes) {
                // Check current item first
                $type = $item['type'] ?? '';
                $route = $item['route'] ?? '';
                $pageId = $item['page_id'] ?? null;
                $formId = $item['form_id'] ?? null;
                $itemId = $item['id'] ?? null;
                
                if ($type === 'route' && !empty($route) && $routeMatches($route)) {
                    return true;
                }
                if ($type === 'page' && !empty($pageId) && $routeMatches('page/view')) {
                    $pageIdFromRoute = Yii::$app->request->get('id');
                    if ($pageIdFromRoute == $pageId) {
                        return true;
                    }
                }
                if ($type === 'form' && !empty($formId) && ($routeMatches('master-form/preview') || $routeMatches('form/view'))) {
                    $formIdFromRoute = Yii::$app->request->get('id');
                    if ($formIdFromRoute == $formId) {
                        return true;
                    }
                }
                
                // Check children recursively
                $children = $item['children'] ?? [];
                if (!empty($children) && is_array($children)) {
                    foreach ($children as $child) {
                        if ($hasActiveChild($child)) return true;
                    }
                }
                return false;
            };
            
            // Render menu item with EXACT route matching only
            $renderMenuItem = function($item, &$menuMap) use (&$renderMenuItem, $isMenuActive, $hasActiveChild, $sidebarTextColor) {
                $icon = $item['icon'] ?: 'folder';
                $type = $item['type'] ?? 'page';
                $route = $item['route'] ?? '';
                $pageId = $item['page_id'] ?? null;
                $formId = $item['form_id'] ?? null;
                $itemId = $item['id'] ?? null;
                $hasChildren = !empty($item['children']) || $type === 'group';
                
                $url = '#';
                if ($type === 'route' && !empty($route)) {
                    $url = $route[0] === '/' ? $route : '/' . ltrim($route, '/');
                } elseif ($type === 'form' && !empty($formId)) {
                    $url = ['/master-form/preview', 'id' => $formId];
                } elseif ($type === 'page' && !empty($pageId)) {
                    $url = ['/page/view', 'id' => $pageId];
                } elseif ($type !== 'group' && !empty($itemId)) {
                    $url = ['/master-menu/resolve-link', 'id' => $itemId];
                }
                
                // EXACT route matching ONLY - NO name/label comparison
                $isActive = $isMenuActive($item);
                $childHasActive = $hasChildren && $hasActiveChild($item);
                
                // Build link classes
                $linkClass = 'app-sidebar-link';
                if ($hasChildren) {
                    $linkClass .= ' has-children';
                    if ($childHasActive && !$isActive) $linkClass .= ' parent-has-active';
                    if ($isActive || $childHasActive) $linkClass .= ' expanded';
                }
                if ($isActive) {
                    $linkClass .= ' active';
                }
                
                if ($hasChildren) {
                    echo '<a href="#" class="' . Html::encode($linkClass) . '" data-menu-id="' . $itemId . '" style="color: ' . Html::encode($sidebarTextColor) . ' !important;">';
                    echo '<span class="material-symbols-outlined">' . Html::encode($icon) . '</span>';
                    echo '<span class="app-sidebar-link-text">' . Html::encode($item['name']) . '</span>';
                    echo '<span class="app-sidebar-chevron material-symbols-outlined" style="margin-left:auto">expand_more</span>';
                    echo '</a>';
                    echo '<div class="sub-menu">';
                    if (!empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            $renderMenuItem($child, $menuMap);
                        }
                    }
                    echo '</div>';
                } else {
                    $urlFinal = is_array($url) ? \yii\helpers\Url::to($url) : $url;
                    echo '<a href="' . Html::encode($urlFinal) . '" class="' . Html::encode($linkClass) . '" data-menu-id="' . $itemId . '" style="color: ' . Html::encode($sidebarTextColor) . ' !important;">';
                    echo '<span class="material-symbols-outlined">' . Html::encode($icon) . '</span>';
                    echo '<span class="app-sidebar-link-text">' . Html::encode($item['name']) . '</span>';
                    echo '</a>';
                }
            };
            
            if (!empty($menuTree)) {
                foreach ($menuTree as $topMenu) {
                    $renderMenuItem($topMenu, $menuMap);
                }
            } else {
                echo '<div style="color: #94a3b8; padding: 10px; text-align: center; font-size: 12px;">No active menus</div>';
            }
            
            // Dynamic Menu dari sidebar_menu table (Form Placements) - only if table exists
            try {
                $dynamicMenus = \app\models\SidebarMenu::find()
                    ->where(['is_active' => 1, 'parent_id' => null])
                    ->andWhere(['or', ['user_id' => Yii::$app->user->id], ['user_id' => null]])
                    ->orderBy(['sort_order' => SORT_ASC])
                    ->limit(100)
                    ->all();
            } catch (\Exception $e) {
                $dynamicMenus = [];
            }
            
            try {
                $formPlacements = \app\models\FormPlacement::find()
                    ->where(['show_in_sidebar' => 1, 'is_published' => 1])
                    ->with('form')
                    ->limit(50)
                    ->all();
            } catch (\Exception $e) {
                $formPlacements = [];
            }
            
            // Detect active for form placements - EXACT route matching
            $isFormPlacementActive = function($placement) use ($currentRoute) {
                $slug = $placement->page_slug ?? '';
                if (routesMatchExactly($currentRoute, 'form-placement/view')) {
                    $slugFromRoute = Yii::$app->request->get('slug');
                    if ($slugFromRoute === $slug) {
                        return true;
                    }
                }
                return false;
            };
            
            if (!empty($dynamicMenus) || !empty($formPlacements)):
            ?>
                <div style="border-top: 1px solid rgba(148, 163, 184, 0.14); margin: 12px 0;"></div>
                <div style="padding: 0 14px; margin-bottom: 8px;">
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Custom Pages</span>
                </div>
                <?php foreach ($formPlacements as $placement): ?>
                    <?php 
                    $form = $placement->form;
                    $label = $placement->page_title ?: ($form ? $form->form_name : 'Form');
                    $icon = $placement->icon ?: 'article';
                    $url = \yii\helpers\Url::to(['/form-placement/view', 'slug' => $placement->page_slug]);
                    $isActive = $isFormPlacementActive($placement);
                    ?>
                    <a href="<?= Html::encode($url) ?>" class="app-sidebar-link <?= $isActive ? 'active' : '' ?>" data-menu-id="form-<?= $placement->id ?>">
                        <span class="ti <?= Html::encode($icon) ?>"></span>
                        <span class="app-sidebar-link-text"><?= Html::encode($label) ?></span>
                    </a>
                <?php endforeach; ?>
                
                <?php foreach ($dynamicMenus as $menu): ?>
                    <?php 
                    $icon = $menu->icon ?: 'link';
                    $url = $menu->route ? \yii\helpers\Url::to([$menu->route]) : ($menu->url ?: '#');
                    $children = $menu->getActiveChildren();
                    $hasChildren = !empty($children);
                    
                    // Check if route matches current route - EXACT matching only
                    $isActive = false;
                    if ($menu->route) {
                        if (routesMatchExactly($currentRoute, $menu->route)) {
                            $isActive = true;
                        }
                    }
                    ?>
                    <?php if ($hasChildren): ?>
                        <a href="#" class="app-sidebar-link has-children <?= $isActive ? 'parent-has-active' : '' ?>" data-menu-id="menu-<?= $menu->id ?>">
                            <span class="ti <?= Html::encode($icon) ?>"></span>
                            <span class="app-sidebar-link-text"><?= Html::encode($menu->label) ?></span>
                            <span class="app-sidebar-chevron material-symbols-outlined" style="margin-left:auto">expand_more</span>
                        </a>
                        <div class="sub-menu">
                            <?php foreach ($children as $child): ?>
                                <?php 
                                $childUrl = $child->route ? \yii\helpers\Url::to([$child->route]) : ($child->url ?: '#');
                                $childIsActive = false;
                                if ($child->route) {
                                    if (routesMatchExactly($currentRoute, $child->route)) {
                                        $childIsActive = true;
                                    }
                                }
                                ?>
                                <a href="<?= Html::encode($childUrl) ?>" class="app-sidebar-link <?= $childIsActive ? 'active' : '' ?>" data-menu-id="menu-<?= $child->id ?>">
                                    <span class="ti <?= Html::encode($child->icon ?: 'link') ?>"></span>
                                    <span class="app-sidebar-link-text"><?= Html::encode($child->label) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <a href="<?= Html::encode($url) ?>" class="app-sidebar-link <?= $isActive ? 'active' : '' ?>" data-menu-id="menu-<?= $menu->id ?>">
                            <span class="ti <?= Html::encode($icon) ?>"></span>
                            <span class="app-sidebar-link-text"><?= Html::encode($menu->label) ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
<?php endif; ?>
            <!-- SYSTEM BUILDER - HARDCODED (di bawah menu dinamis) -->
            <?php if ($sidebarVariant === 'full'): ?>
                <div style="border-top: 1px solid <?= Html::encode($sidebarBorderColor) ?>; margin: 12px 0;"></div>
                <div class="app-sidebar-system-builder" style="padding: 0 14px;">
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: <?= Html::encode($sidebarTextMuted) ?>;">Admin Tools</span>
                </div>
                <a href="<?= \yii\helpers\Url::to(['master-menu/index']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'master-menu/index') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                    <span class="material-symbols-outlined">list_alt</span>
                    <span class="app-sidebar-link-text">Master Menu</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['master-page/index']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'master-page/index') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                    <span class="material-symbols-outlined">description</span>
                    <span class="app-sidebar-link-text">Master Page</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['master-form/index']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'master-form/index') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                    <span class="material-symbols-outlined">dynamic_form</span>
                    <span class="app-sidebar-link-text">Master Form</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'table-builder/index') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                    <span class="material-symbols-outlined">table_chart</span>
                    <span class="app-sidebar-link-text">Master Table</span>
                </a>
                <a href="<?= \yii\helpers\Url::to(['workspace-settings/index']) ?>" class="app-sidebar-link <?= routesMatchExactly($currentRoute, 'workspace-settings/index') ? 'active' : '' ?>" style="color: <?= Html::encode($sidebarTextColor) ?>;">
                    <span class="material-symbols-outlined">palette</span>
                    <span class="app-sidebar-link-text">Workspace Settings</span>
                </a>
            <?php endif; ?>
    </nav>

<!-- Footer -->
    <div class="app-sidebar-footer <?= $sidebarVariant === 'minimal' ? 'mt-auto' : '' ?>">
        <?php if ($canOpenProjectList): ?>
            <div style="display:grid;gap:8px;margin-bottom:10px;">
                <?= Html::a(
                    '<span class="material-symbols-outlined">folder</span><span class="app-sidebar-link-text">Kembali ke Project List</span>',
                    $projectListUrl,
                    [
                        'class' => 'app-sidebar-logout',
                        'style' => 'color: ' . Html::encode($sidebarTextColor),
                        'encode' => false
                    ]
                ) ?>
            </div>
        <?php endif; ?>

        <div style="display:grid;gap:8px;">
            <?= Html::beginForm($logoutUrl, 'post') ?>
                <button type="submit" class="app-sidebar-logout" style="color: <?= Html::encode($sidebarTextColor) ?>; width:100%; text-align:left;">
                    <span class="material-symbols-outlined">logout</span><span class="app-sidebar-link-text">Logout</span>
                </button>
            <?= Html::endForm() ?>
        </div>
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
