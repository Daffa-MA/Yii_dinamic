<?php
/**
 * Layout User dengan Sidebar User (DINAMIS)
 * 
 * UNTUK HALAMAN USER BIASA
 * Sidebar DIAMBIL DARI DATABASE (tabel master_menu)
 */

use yii\helpers\Html;
use app\components\UserSidebar;

$sidebar = new UserSidebar();
$menuTree = $sidebar->getMenuTree(true);
$currentRoute = Yii::$app->controller->route;

function isActiveRoute($itemUrl, $currentRoute) {
    if (empty($itemUrl) || $itemUrl === '#') return false;
    if (is_array($itemUrl)) {
        $url = $itemUrl[0] ?? '';
        return strpos($currentRoute, $url) === 0;
    }
    return strpos($currentRoute, trim($itemUrl, '/')) === 0;
}
?>

<aside class="user-sidebar">
    <div class="user-sidebar-header">
        <h2>Application</h2>
        <p>Navigasi Utama</p>
    </div>
    
    <nav class="user-sidebar-nav">
        <?php if (empty($menuTree)): ?>
            <div class="no-menu">
                <p>Belum ada menu aktif.</p>
                <p style="font-size: 11px; color: #94a3b8;">Hubungi admin untuk menambahkan menu.</p>
            </div>
        <?php else: ?>
            <?php
            \Yii::info('Menu tree JSON: ' . json_encode($menuTree), 'menu-debug');
            ?>
            <?= $sidebar->renderHtml($menuTree) ?>
        <?php endif; ?>
    </nav>
</aside>

<style>
.user-sidebar {
    width: 260px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    color: #1e293b;
    padding: 0;
    flex-shrink: 0;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
}

.user-sidebar-header {
    padding: 24px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: white;
}

.user-sidebar-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.user-sidebar-header p {
    font-size: 12px;
    color: #64748b;
}

.user-sidebar-nav {
    padding: 16px 12px;
}

.no-menu {
    padding: 20px;
    text-align: center;
    color: #94a3b8;
}

.no-menu p {
    margin-bottom: 8px;
}

/* Menu Link */
.menu-link, .menu-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #475569;
    text-decoration: none;
    border-radius: 10px;
    margin-bottom: 4px;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
}

.menu-link:hover, .menu-toggle:hover {
    background: rgba(37, 99, 235, 0.08);
    color: #2563eb;
    transform: translateX(3px);
}

/* ACTIVE STATE - Modern SaaS Style */
.menu-link.active, .menu-link.is-active {
    background: linear-gradient(135deg, #2563eb 0%, #06b6d4 50%, #0ea5e9 100%);
    color: white !important;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(37, 99, 235, 0.24);
}

.menu-link.active:hover {
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.32);
}

.menu-link .material-symbols-outlined,
.menu-toggle .material-symbols-outlined {
    font-size: 20px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    background: rgba(148, 163, 184, 0.08);
    border-radius: 8px;
    transition: all 0.2s ease;
}

.menu-link:hover .material-symbols-outlined,
.menu-toggle:hover .material-symbols-outlined {
    color: #2563eb;
    background: rgba(37, 99, 235, 0.12);
}

.menu-link.active .material-symbols-outlined,
.menu-link.active:hover .material-symbols-outlined {
    color: white;
    background: rgba(255, 255, 255, 0.2);
}

.menu-label {
    flex: 1;
}

.menu-arrow {
    font-family: 'Material Symbols Outlined';
    font-size: 18px;
    transition: transform 0.2s;
}

.menu-toggle[aria-expanded="true"] .menu-arrow {
    transform: rotate(180deg);
}

/* Submenu */
.submenu {
    display: none;
    padding-left: 20px;
    margin-top: 4px;
    border-left: 1px solid #e2e8f0;
}

.submenu.open {
    display: block;
}

.menu-group.open > .submenu {
    display: block;
}

.menu-group.open > .menu-toggle {
    background: rgba(37, 99, 235, 0.08);
    color: #2563eb;
}

.menu-group.open > .menu-toggle .material-symbols-outlined {
    color: #2563eb;
    background: rgba(37, 99, 235, 0.12);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.menu-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var group = toggle.parentElement;
            group.classList.toggle('open');
            var isOpen = group.classList.contains('open');
            toggle.setAttribute('aria-expanded', isOpen);
        });
    });
});
</script>
