<?php

namespace app\services;

use Yii;
use app\models\MasterMenu;

/**
 * SidebarService - Logic untuk render sidebar menu dari database
 * 
 * Fitur:
 * - Ambil menu aktif dari database
 * - Build hierarchical tree (parent-child)
 * - Urutkan berdasarkan field 'order'
 * - Support unlimited nesting (recursive)
 * - Output JSON structure
 * - Render HTML untuk sidebar
 */
class SidebarService
{
    /**
     * Ambil semua menu aktif dan build tree
     * 
     * @param bool $activeOnly - Hanya menu aktif
     * @return array - Nested tree structure
     */
    public function getMenuTree(bool $activeOnly = true): array
    {
        return MasterMenu::getMenuTree($activeOnly);
    }

    /**
     * Ambil menu tree berdasarkan project_id
     * Digunakan untuk multi-project dashboard
     * 
     * @param int $projectId - ID project
     * @param bool $activeOnly - Hanya menu aktif
     * @return array - Nested tree structure
     */
    public function getMenuTreeByProject(int $projectId, bool $activeOnly = true): array
    {
        return MasterMenu::getMenuTree($activeOnly);
    }

    /**
     * Build tree dari flat array
     * Menggunakan recursive approach untuk support unlimited nesting
     * 
     * @param array $menus - Flat array dari database
     * @param int|null $parentId - Parent ID untuk filtering
     * @return array - Nested tree
     */
    public function buildTree(array $menus, ?int $parentId = null): array
    {
        $tree = [];
        
        foreach ($menus as $menu) {
            // Cek apakah ini child dari parentId yang diminta
            $menuParentId = $menu->parent_id ?? null;
            
            // Handle comparison (jika parent_id null, harusnya null juga)
            $isChild = false;
            if ($parentId === null && $menuParentId === null) {
                $isChild = true;
            } elseif ($parentId !== null && $menuParentId == $parentId) {
                $isChild = true;
            }
            
            if ($isChild) {
                // Build node untuk menu ini
                $node = $this->buildNode($menu);
                
                // RECURSIVE: Cari children untuk menu ini
                $children = $this->buildTree($menus, $menu->id);
                
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
     * Build single node dari model
     * 
     * @param MasterMenu $menu
     * @return array
     */
    private function buildNode(MasterMenu $menu): array
    {
        // Determine URL berdasarkan type
        $url = $this->getMenuUrl($menu);
        
        return [
            'id' => (int) $menu->id,
            'name' => $menu->name,
            'type' => $menu->type,
            'icon' => $menu->icon ?? 'folder',
            'url' => $url,
            'page_id' => $menu->page_id ? (int) $menu->page_id : null,
            'form_id' => $menu->form_id ? (int) $menu->form_id : null,
            'route' => $menu->route,
            'parent_id' => $menu->parent_id ? (int) $menu->parent_id : null,
            'order' => (int) ($menu->order ?? $menu->sort_order),
            'menu_key' => $menu->menu_key,
            // Will be populated by recursive call
            'has_children' => false,
            'children' => [],
        ];
    }

    /**
     * Get URL berdasarkan type menu
     * 
     * @param MasterMenu $menu
     * @return string|array - URL string atau array untuk Yii2 Url::to()
     */
    public function getMenuUrl(MasterMenu $menu): string
    {
        // Recovery path: page-without-page_id but with form_id should behave as form
        if (!empty($menu->form_id) && ($menu->type === 'form' || ($menu->type === 'page' && empty($menu->page_id)))) {
            return '/master-form/preview?id=' . $menu->form_id;
        }

        switch ($menu->type) {
            case 'route':
                // Direct URL - pastikan ada leading slash
                if (!empty($menu->route)) {
                    return $menu->route[0] === '/' 
                        ? $menu->route 
                        : '/' . ltrim($menu->route, '/');
                }
                return '#';
                
            case 'page':
                // Link ke page view
                if (!empty($menu->page_id)) {
                    return '/page/view?id=' . $menu->page_id;
                }
                return '#';

            case 'form':
                // Link ke form preview (dynamic form builder existing)
                if (!empty($menu->form_id)) {
                    return '/master-form/preview?id=' . $menu->form_id;
                }
                return '#';
                
            case 'group':
            default:
                // Group tidak punya URL langsung
                return '#';
        }
    }

    /**
     * Get menu tree sebagai JSON
     * 
     * @param bool $activeOnly
     * @return string - JSON encoded
     */
    public function getMenuJson(bool $activeOnly = true): string
    {
        $tree = $this->getMenuTree($activeOnly);
        return json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Render HTML sidebar dari tree
     * 
     * @param array|null $tree - Jika null, akan build dari DB
     * @return string - HTML output
     */
    public function renderHtml(?array $tree = null): string
    {
        if ($tree === null) {
            $tree = $this->getMenuTree(true);
        }
        
        return $this->renderMenuItems($tree);
    }

    /**
     * Render menu items recursively
     * 
     * @param array $items - Tree structure
     * @param int $level - Nesting level
     * @return string - HTML
     */
    private function renderMenuItems(array $items, int $level = 0): string
    {
        $html = '';
        
        foreach ($items as $item) {
            $hasChildren = !empty($item['children']);
            $indent = str_repeat('  ', $level);
            
            if ($hasChildren) {
                // Menu dengan children → dropdown
                $url = $item['url'] === '#' ? '#' : $item['url'];
                $html .= $indent . '<div class="menu-item dropdown">' . "\n";
                $html .= $indent . '  <a href="' . $url . '" class="menu-toggle">' . "\n";
                $html .= $indent . '    <span class="icon">' . $item['icon'] . '</span>' . "\n";
                $html .= $indent . '    <span class="label">' . $item['name'] . '</span>' . "\n";
                $html .= $indent . '    <span class="arrow">▼</span>' . "\n";
                $html .= $indent . '  </a>' . "\n";
                $html .= $indent . '  <div class="submenu">' . "\n";
                $html .= $this->renderMenuItems($item['children'], $level + 1);
                $html .= $indent . '  </div>' . "\n";
                $html .= $indent . '</div>' . "\n";
            } else {
                // Menu tanpa children → link
                $url = $item['url'];
                $html .= $indent . '<a href="' . $url . '" class="menu-link">' . "\n";
                $html .= $indent . '  <span class="icon">' . $item['icon'] . '</span>' . "\n";
                $html .= $indent . '  <span class="label">' . $item['name'] . '</span>' . "\n";
                $html .= $indent . '</a>' . "\n";
            }
        }
        
        return $html;
    }

    /**
     * Get breadcrumb/path dari menu ke root
     * 
     * @param int $menuId
     * @return array - Path dari root ke menu
     */
    public function getBreadcrumb(int $menuId): array
    {
        $path = [];
        $currentId = $menuId;
        
        while ($currentId !== null) {
            $menu = MasterMenu::findOne($currentId);
            if ($menu) {
                $path[] = [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'url' => $this->getMenuUrl($menu),
                ];
                $currentId = $menu->parent_id;
            } else {
                break;
            }
        }
        
        return array_reverse($path);
    }

    /**
     * Get all root menus (menus without parent)
     * 
     * @param bool $activeOnly
     * @return array
     */
    public function getRootMenus(bool $activeOnly = true): array
    {
        $condition = $activeOnly ? ['is_active' => 1] : [];
        
        return MasterMenu::find()
            ->where($condition)
            ->andWhere(['parent_id' => null])
            ->orderBy(['order' => SORT_ASC])
            ->all();
    }

    /**
     * Get children of a menu
     * 
     * @param int $parentId
     * @param bool $activeOnly
     * @return array
     */
    public function getChildren(int $parentId, bool $activeOnly = true): array
    {
        $condition = ['parent_id' => $parentId];
        if ($activeOnly) {
            $condition['is_active'] = 1;
        }
        
        return MasterMenu::find()
            ->where($condition)
            ->orderBy(['order' => SORT_ASC])
            ->all();
    }

    /**
     * Count total active menus
     * 
     * @return int
     */
    public function countActiveMenus(): int
    {
        return MasterMenu::find()
            ->where(['is_active' => 1])
            ->count();
    }

    /**
     * Count total pages
     * 
     * @param bool $activeOnly
     * @return int
     */
    public function countActivePages(bool $activeOnly = true): int
    {
        return \app\models\MasterPage::find()
            ->where($activeOnly ? ['is_active' => 1] : [])
            ->count();
    }
}
