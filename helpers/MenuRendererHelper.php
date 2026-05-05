<?php
/**
 * Menu Renderer Functions
 * Compatible PHP 7.2+
 */

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Render sidebar menu dari tree array
 * 
 * @param array $menus - Nested menu tree
 * @return string
 */
function renderMenu($menus)
{
    if (empty($menus)) {
        return '';
    }
    
    $html = '<ul class="menu-list">' . "\n";
    
    foreach ($menus as $menu) {
        $html .= renderMenuItem($menu);
    }
    
    $html .= '</ul>' . "\n";
    
    return $html;
}

/**
 * Render single menu item (recursive)
 * 
 * @param array $menu
 * @return string
 */
function renderMenuItem($menu)
{
    $name = isset($menu['name']) ? $menu['name'] : '';
    $type = isset($menu['type']) ? $menu['type'] : 'page';
    $icon = isset($menu['icon']) ? $menu['icon'] : 'folder';
    $children = isset($menu['children']) ? $menu['children'] : [];
    $hasChildren = !empty($children);
    
    // Resolve URL berdasarkan type
    $url = resolveMenuUrl($menu);
    
    // Check active state
    $isActive = isMenuActive($menu);
    
    // Generate ID untuk toggle
    $menuId = isset($menu['id']) ? 'menu-' . $menu['id'] : 'menu-' . uniqid();
    
    if ($hasChildren || $type === 'group') {
        // GROUP - dengan dropdown
        $html = '<li class="menu-group">' . "\n";
        $html .= '<a href="#" class="menu-toggle ' . ($isActive ? 'active' : '') . '" data-toggle="' . $menuId . '">' . "\n";
        $html .= '<span class="material-symbols-outlined">' . Html::encode($icon) . '</span>' . "\n";
        $html .= '<span class="menu-label">' . Html::encode($name) . '</span>' . "\n";
        $html .= '<span class="menu-arrow">expand_more</span>' . "\n";
        $html .= '</a>' . "\n";
        $html .= '<ul class="submenu" id="' . $menuId . '">' . "\n";
        
        foreach ($children as $child) {
            $html .= renderMenuItem($child);
        }
        
        $html .= '</ul>' . "\n";
        $html .= '</li>' . "\n";
    } else {
        // LINK - bukan group
        $html = '<li>' . "\n";
        $html .= Html::a(
            '<span class="material-symbols-outlined">' . Html::encode($icon) . '</span>' . "\n" .
            '<span class="menu-label">' . Html::encode($name) . '</span>',
            $url,
            ['class' => 'menu-link' . ($isActive ? ' active' : '')]
        ) . "\n";
        $html .= '</li>' . "\n";
    }
    
    return $html;
}

/**
 * Resolve URL berdasarkan type menu
 * 
 * @param array $menu
 * @return array|string
 */
function resolveMenuUrl($menu)
{
    $type = isset($menu['type']) ? $menu['type'] : 'page';
    
    if ($type === 'route') {
        $route = isset($menu['route']) ? $menu['route'] : '';
        if (!empty($route)) {
            return $route[0] === '/' ? $route : '/' . ltrim($route, '/');
        }
        return '#';
    }
    
    if ($type === 'page') {
        $pageId = isset($menu['page_id']) ? $menu['page_id'] : '';
        if (!empty($pageId)) {
            return ['/page/view', 'id' => $pageId];
        }
        return '#';
    }
    
    return '#';
}

/**
 * Check apakah menu aktif (berdasarkan URL sekarang)
 * 
 * @param array $menu
 * @return bool
 */
function isMenuActive($menu)
{
    $currentRoute = Yii::$app->controller->route;
    $url = resolveMenuUrl($menu);
    
    if (is_array($url)) {
        $route = $url[0] ?? '';
        return strpos($currentRoute, $route) === 0;
    }
    
    return strpos($currentRoute, ltrim($url, '/')) === 0;
}

/**
 * Contoh penggunaan:
 * 
 * $menuTree = getMenuTree(); // dari MenuTreeHelper
 * echo renderMenu($menuTree);
 * 
 * // CSS:
 * .menu-list { list-style: none; padding: 0; margin: 0; }
 * .menu-group { margin-bottom: 4px; }
 * .menu-link, .menu-toggle {
 *     display: flex; align-items: center; gap: 12px;
 *     padding: 12px 16px; text-decoration: none;
 *     color: #333; border-radius: 8px;
 * }
 * .menu-link:hover, .menu-toggle:hover { background: #f0f0f0; }
 * .menu-link.active { background: #4f46e5; color: white; }
 * .submenu { display: none; padding-left: 16px; }
 * .submenu.show { display: block; }
 * .menu-arrow { margin-left: auto; font-size: 18px; }
 */