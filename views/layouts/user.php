<?php
/**
 * Layout User dengan Sidebar Dinamis
 * 
 * UNTUK USER BIASA (non-admin)
 * Sidebar DIAMBIL DARI DATABASE
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\UserSidebar;

// Konfigurasi halaman
$this->title = $this->title ?? 'Dashboard';
$content = $content ?? '';

// Load menu tree dari database
$sidebar = new UserSidebar();
$menuTree = $sidebar->getMenuTree(true);
$currentRoute = Yii::$app->controller->route;
$rolePageHero = new \app\components\RolePageHero();
$roleHeroData = $rolePageHero->build($this->title ?? '');

// breadcrumb dari session
$breadcrumb = Yii::$app->session->get('breadcrumb', []);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@0..1,0..1&display=swap">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: #f8fafc; 
        }
        
        /* Layout */
        .user-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .user-sidebar {
            width: 260px;
            background: white;
            color: #1e293b;
            flex-shrink: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid #e2e8f0;
            box-shadow: 2px 0 8px rgba(0,0,0,0.02);
        }
        
        /* Main */
        .user-main {
            flex: 1;
            margin-left: 260px;
            padding: 24px;
        }
        
        /* Sidebar Header */
        .user-sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .user-sidebar-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .user-sidebar-header p {
            font-size: 12px;
            color: #64748b;
        }
        
        /* Navigation */
        .user-sidebar-nav {
            padding: 12px;
        }
        
        .no-menu {
            padding: 20px;
            text-align: center;
            color: #94a3b8;
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
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            text-align: left;
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
        
        .material-symbols-outlined {
            font-size: 20px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }
        
        .menu-link:hover .material-symbols-outlined,
        .menu-toggle:hover .material-symbols-outlined {
            color: inherit;
        }
        
        .menu-label {
            flex: 1;
        }
        
        .menu-arrow {
            font-family: 'Material Symbols Outlined';
            font-size: 18px;
            transition: transform 0.2s;
        }
        
        /* Submenu */
        .submenu {
            display: none;
            padding-left: 20px;
            margin-top: 4px;
            border-left: 1px solid #e2e8f0;
        }
        
        .menu-group.open > .submenu {
            display: block;
        }
        
        /* Footer */
        .user-sidebar-footer {
            padding: 16px;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }
        
        .user-logout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #dc2626;
            text-decoration: none;
            border-radius: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
        }
        
        .user-logout-btn:hover {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="user-layout">
        <!-- SIDEBAR USER (DINAMIS - dari database) -->
        <aside class="user-sidebar">
            <div class="user-sidebar-header">
                <h2>Application</h2>
                <p>Navigasi Utama</p>
            </div>
            
            <nav class="user-sidebar-nav">
                <?php if (empty($menuTree)): ?>
                    <div class="no-menu">
                        <p>Belum ada menu aktif.</p>
                        <p style="font-size: 11px;">Hubungi admin.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    // Render RECURSIVE menu
                    function renderUserMenu($items, $level = 0) {
                        $indent = str_repeat('    ', $level);
                        $html = '';
                        
                        foreach ($items as $item) {
                            $hasChildren = !empty($item['children']);
                            $url = $item['url'];
                            $name = htmlspecialchars($item['name']);
                            $icon = htmlspecialchars($item['icon'] ?: 'folder');
                            
                            if ($hasChildren) {
                                $html .= $indent . '<div class="menu-group">' . "\n";
                                $html .= $indent . '    <button type="button" class="menu-toggle" data-toggle="collapse">' . "\n";
                                $html .= $indent . '        <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
                                $html .= $indent . '        <span class="menu-label">' . $name . '</span>' . "\n";
                                $html .= $indent . '        <span class="material-symbols-outlined menu-arrow">expand_more</span>' . "\n";
                                $html .= $indent . '    </button>' . "\n";
                                $html .= $indent . '    <div class="submenu">' . "\n";
                                $html .= renderUserMenu($item['children'], $level + 1);
                                $html .= $indent . '    </div>' . "\n";
                                $html .= $indent . '</div>' . "\n";
                            } else {
                                $html .= $indent . '<a href="' . $url . '" class="menu-link">' . "\n";
                                $html .= $indent . '    <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
                                $html .= $indent . '    <span class="menu-label">' . $name . '</span>' . "\n";
                                $html .= $indent . '</a>' . "\n";
                            }
                        }
                        
                        return $html;
                    }
                    
                    echo renderUserMenu($menuTree);
                    ?>
                <?php endif; ?>
            </nav>
            
            <div class="user-sidebar-footer">
                <?= Html::beginForm(['/site/logout'], 'post') ?>
                    <button type="submit" class="user-logout-btn">
                        <span class="material-symbols-outlined">logout</span>
                        <span>Sign Out</span>
                    </button>
                <?= Html::endForm() ?>
            </div>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="user-main">
            <?php if (!empty($roleHeroData['should_render']) && $currentRoute !== 'site/dashboard'): ?>
                <?= $this->render('_role_page_hero', ['hero' => $roleHeroData]) ?>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.menu-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            var group = toggle.closest('.menu-group');
            group.classList.toggle('open');
        });
    });
});
</script>
