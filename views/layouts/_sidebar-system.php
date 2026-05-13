<?php
use yii\helpers\Html;
use yii\helpers\Url;

// Helper functions for exact route matching
function normalizePath($path) {
    if ($path === null || $path === false) {
        return '/';
    }
    $normalized = strtolower(trim((string) $path));
    $normalized = rtrim($normalized, '/');
    return empty($normalized) ? '/' : $normalized;
}

function routesMatchExactly($currentRoute, $menuRoute) {
    return normalizePath($currentRoute) === normalizePath($menuRoute);
}

$currentRoute = Yii::$app->controller->route;
$isActive = function($route) use ($currentRoute) {
    return routesMatchExactly($currentRoute, $route);
};
?>

<style>
.system-sidebar {
    width: 260px;
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
    color: white;
    padding: 20px;
    min-height: 100vh;
}

.system-sidebar h3 {
    margin: 0 0 5px;
    font-size: 14px;
    font-weight: 700;
    color: #f8fafc;
}

.system-sidebar > p {
    margin: 0 0 20px;
    color: #94a3b8;
    font-size: 12px;
}

.system-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.system-sidebar li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    color: #cbd5e1;
    text-decoration: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    background: transparent;
}

.system-sidebar li a .material-symbols-outlined {
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
    transition: all 0.2s ease;
}

.system-sidebar li a:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    transform: translateX(3px);
}

.system-sidebar li a:hover .material-symbols-outlined {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}

.system-sidebar li a.active {
    background: linear-gradient(135deg, #2563eb 0%, #06b6d4 50%, #0ea5e9 100%);
    color: white !important;
    font-weight: 600;
    border: none;
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.28);
    transform: translateX(3px);
}

.system-sidebar li a.active .material-symbols-outlined {
    color: white !important;
    background: rgba(255, 255, 255, 0.2);
}

.system-sidebar li a.active:hover {
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.32);
}

.system-sidebar li a.active:hover .material-symbols-outlined {
    background: rgba(255, 255, 255, 0.25);
}
</style>

<aside class="system-sidebar">
    <h3>System Builder</h3>
    <p>CMS Administration</p>
    
    <ul>
        <li>
            <a href="<?= Url::to(['master-menu/index']) ?>" class="<?= $isActive('master-menu') ? 'active' : '' ?>">
                <span class="material-symbols-outlined">list_alt</span>
                <span>Master Menu</span>
            </a>
        </li>
        <li>
            <a href="<?= Url::to(['master-page/index']) ?>" class="<?= $isActive('master-page') ? 'active' : '' ?>">
                <span class="material-symbols-outlined">description</span>
                <span>Master Page</span>
            </a>
        </li>
        <li>
            <a href="<?= Url::to(['master-form/index']) ?>" class="<?= $isActive('master-form') ? 'active' : '' ?>">
                <span class="material-symbols-outlined">dynamic_form</span>
                <span>Master Form</span>
            </a>
        </li>
        <li>
            <a href="<?= Url::to(['table-builder/index']) ?>" class="<?= $isActive('table-builder') ? 'active' : '' ?>">
                <span class="material-symbols-outlined">table_chart</span>
                <span>Master Table</span>
            </a>
        </li>
    </ul>
</aside>