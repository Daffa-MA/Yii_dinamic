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
    transition: all 0.2s;
    font-size: 14px;
    font-weight: 500;
}

.menu-link:hover, .menu-toggle:hover {
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    transform: translateX(4px);
}

.menu-link.active {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: white;
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
}

.menu-link:hover .material-symbols-outlined,
.menu-toggle:hover .material-symbols-outlined {
    color: #4f46e5;
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
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
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