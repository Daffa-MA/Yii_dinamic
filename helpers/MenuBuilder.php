<?php
/**
 * Menu Builder Functions
 * 
 * Kumpulan function untuk build menu tree dari database
 * Compatible dengan PHP 7.2 (tanpa arrow function)
 * 
 * Usage:
 *   $tree = buildMenuTree($menus);
 *   $html = renderMenuHtml($tree);
 */

namespace app\helpers;

use Yii;
use app\models\MasterMenu;

/**
 * Build menu tree dari flat array
 * RECURSIVE function
 * 
 * @param array $menus - Array MasterMenu models
 * @param int|null $parentId - Parent ID untuk filtering
 * @return array - Nested tree structure
 */
function buildMenuTree(array $menus, $parentId = null)
{
    $tree = [];
    
    foreach ($menus as $menu) {
        $menuParentId = $menu->parent_id ?? null;
        
        $isChild = false;
        if ($parentId === null && $menuParentId === null) {
            $isChild = true;
        } elseif ($parentId !== null && $menuParentId == $parentId) {
            $isChild = true;
        }
        
        if ($isChild) {
            $node = buildMenuNode($menu);
            
            $children = buildMenuTree($menus, $menu->id);
            
            if (!empty($children)) {
                $node['children'] = $children;
                $node['has_children'] = true;
            } else {
                $node['has_children'] = false;
            }
            
            $tree[] = $node;
        }
    }
    
    return $tree;
}

/**
 * Build single menu node
 * 
 * @param MasterMenu $menu
 * @return array
 */
function buildMenuNode($menu)
{
    return [
        'id' => (int) $menu->id,
        'name' => $menu->name,
        'type' => $menu->type,
        'icon' => $menu->icon ?: 'folder',
        'url' => resolveMenuUrl($menu),
        'page_id' => $menu->page_id ? (int) $menu->page_id : null,
        'route' => $menu->route,
        'parent_id' => $menu->parent_id ? (int) $menu->parent_id : null,
        'order' => (int) ($menu->order ?? $menu->sort_order),
        'has_children' => false,
        'children' => [],
    ];
}

/**
 * Resolve URL berdasarkan tipe menu
 * 
 * @param MasterMenu $menu
 * @return string
 */
function resolveMenuUrl($menu)
{
    switch ($menu->type) {
        case MasterMenu::TYPE_ROUTE:
            if (!empty($menu->route)) {
                return $menu->route[0] === '/' 
                    ? $menu->route 
                    : '/' . ltrim($menu->route, '/');
            }
            return '#';
            
        case MasterMenu::TYPE_PAGE:
            if (!empty($menu->page_id)) {
                return \yii\helpers\Url::to(['/page/view', 'id' => $menu->page_id]);
            }
            return '#';
            
        case MasterMenu::TYPE_GROUP:
        default:
            return '#';
    }
}

/**
 * Render HTML dari menu tree
 * RECURSIVE function
 * 
 * @param array $tree
 * @param int $level
 * @return string
 */
function renderMenuHtml(array $tree, $level = 0)
{
    $html = '';
    $indent = str_repeat('    ', $level);
    
    foreach ($tree as $item) {
        $hasChildren = !empty($item['children']);
        $url = $item['url'];
        $name = htmlspecialchars($item['name']);
        $icon = htmlspecialchars($item['icon'] ?: 'folder');
        
        if ($hasChildren) {
            $html .= $indent . '<div class="menu-group">' . "\n";
            $html .= $indent . '    <a href="#" class="menu-toggle">' . "\n";
            $html .= $indent . '        <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
            $html .= $indent . '        <span class="menu-label">' . $name . '</span>' . "\n";
            $html .= $indent . '        <span class="menu-arrow">▼</span>' . "\n";
            $html .= $indent . '    </a>' . "\n";
            $html .= $indent . '    <div class="submenu">' . "\n";
            $html .= renderMenuHtml($item['children'], $level + 1);
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

/**
 * Get all active menus from database
 * 
 * @return array
 */
function getActiveMenus()
{
    return MasterMenu::find()
        ->where(['is_active' => MasterMenu::STATUS_ACTIVE])
        ->orderBy(['sort_order' => SORT_ASC, 'order' => SORT_ASC])
        ->all();
}

/**
 * Get menu tree dari database
 * 
 * @param bool $activeOnly
 * @return array
 */
function getMenuTreeFromDb($activeOnly = true)
{
    $query = MasterMenu::find()
        ->orderBy(['sort_order' => SORT_ASC, 'order' => SORT_ASC]);
    
    if ($activeOnly) {
        $query->where(['is_active' => MasterMenu::STATUS_ACTIVE]);
    }
    
    $menus = $query->all();
    return buildMenuTree($menus);
}

/**
 * Get breadcrumb path dari menu ID
 * 
 * @param int $menuId
 * @return array
 */
function getMenuBreadcrumb($menuId)
{
    $path = [];
    $currentId = $menuId;
    
    while ($currentId !== null) {
        $menu = MasterMenu::findOne($currentId);
        if ($menu) {
            $path[] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'url' => resolveMenuUrl($menu),
            ];
            $currentId = $menu->parent_id;
        } else {
            break;
        }
    }
    
    return array_reverse($path);
}

/**
 * Get root menus (tanpa parent)
 * 
 * @param bool $activeOnly
 * @return array
 */
function getRootMenus($activeOnly = true)
{
    $condition = $activeOnly ? ['is_active' => 1] : [];
    
    return MasterMenu::find()
        ->where($condition)
        ->andWhere(['parent_id' => null])
        ->orderBy(['sort_order' => SORT_ASC])
        ->all();
}

/**
 * Get children dari parent
 * 
 * @param int $parentId
 * @param bool $activeOnly
 * @return array
 */
function getMenuChildren($parentId, $activeOnly = true)
{
    $condition = ['parent_id' => $parentId];
    if ($activeOnly) {
        $condition['is_active'] = 1;
    }
    
    return MasterMenu::find()
        ->where($condition)
        ->orderBy(['sort_order' => SORT_ASC])
        ->all();
}