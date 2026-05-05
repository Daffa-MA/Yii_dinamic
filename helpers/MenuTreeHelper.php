<?php
/**
 * Menu Helper Functions
 * Compatible PHP 7.2+
 */

use app\models\MasterMenu;

/**
 * Ambil semua menu aktif dan bentuk menjadi TREE
 * 
 * @return array
 */
function getMenuTree()
{
    $menus = MasterMenu::find()
        ->where(['is_active' => 1])
        ->orderBy(['order' => SORT_ASC])
        ->all();
    
    return buildTree($menus);
}

/**
 * Rekursif: Building menu tree dari array model
 * 
 * @param array $menus - Array MasterMenu models
 * @param int|null $parentId - Parent ID untuk filtering
 * @return array
 */
function buildTree($menus, $parentId = null)
{
    $tree = [];
    
    foreach ($menus as $menu) {
        $menuParentId = $menu->parent_id ?? null;
        
        // Cek cocok tidaknya dengan parent sekarang
        if ($parentId === null && $menuParentId === null) {
            $node = buildNode($menu);
            $node['children'] = buildTree($menus, $menu->id);
            $tree[] = $node;
        } elseif ($parentId !== null && $menuParentId == $parentId) {
            $node = buildNode($menu);
            $node['children'] = buildTree($menus, $menu->id);
            $tree[] = $node;
        }
    }
    
    return $tree;
}

/**
 * Bangun single node dari model
 * 
 * @param MasterMenu $menu
 * @return array
 */
function buildNode($menu)
{
    $type = $menu->type ?? 'page';
    $url = '#';
    
    // Resolve URL berdasarkan type
    if ($type === 'route' && !empty($menu->route)) {
        $url = $menu->route[0] === '/' 
            ? $menu->route 
            : '/' . ltrim($menu->route, '/');
    } elseif ($type === 'page' && !empty($menu->page_id)) {
        $url = ['/page/view', 'id' => $menu->page_id];
    }
    
    return [
        'id' => (int) $menu->id,
        'parent_id' => $menu->parent_id ? (int) $menu->parent_id : null,
        'name' => $menu->name,
        'type' => $type,
        'icon' => $menu->icon ?? 'folder',
        'url' => $url,
        'order' => (int) ($menu->order ?? 0),
    ];
}

/**
 * Contoh penggunaan:
 * 
 * $menuTree = getMenuTree();
 * 
 * // Result:
 * [
 *     [
 *         'id' => 1,
 *         'name' => 'Beranda',
 *         'type' => 'page',
 *         'url' => '/page/view?id=1',
 *         'icon' => 'home',
 *         'children' => [
 *             [
 *                 'id' => 2,
 *                 'name' => 'Sub Menu',
 *                 'children' => []
 *             ]
 *         ]
 *     ],
 *     [
 *         'id' => 3,
 *         'name' => 'Profil',
 *         'type' => 'route',
 *         'url' => '/site/profile',
 *         'children' => []
 *     ]
 * ]
 */