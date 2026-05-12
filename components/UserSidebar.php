<?php
/**
 * User Sidebar (DINAMIS)
 * 
 * SIDEBAR INI UNTUK USER BIASA
 * DIAMBIL DARI DATABASE (tabel master_menu)
 * 
 * Fitur:
 * - Support parent-child (unlimited nesting)
 * - Urutkan berdasarkan field 'order'
 * - Hanya tampil jika is_active = true
 * - Tipe menu: group, page, route
 */

namespace app\components;

use Yii;
use app\models\MasterMenu;
use yii\base\Component;
use yii\helpers\Url;

class UserSidebar extends Component
{
    /**
     * Ambil menu tree dari database
     * 
     * @param bool $activeOnly - Hanya menu aktif
     * @return array - Nested tree structure
     */
    public function getMenuTree(bool $activeOnly = true): array
    {
        $query = MasterMenu::find()
            ->select(['id', 'parent_id', 'name', 'type', 'icon', 'route', 'page_id', 'form_id', 'sort_order', 'is_active'])
            ->orderBy(['sort_order' => SORT_ASC, 'order' => SORT_ASC]);
        
        if ($activeOnly) {
            $query->where(['is_active' => MasterMenu::STATUS_ACTIVE]);
        }
        
        $menus = $query->all();
        return $this->buildTree($menus);
    }
    
    /**
     * Build tree dari flat array
     * RECURSIVE function untuk parent-child hierarchy
     * 
     * @param array $menus - Flat array dari database
     * @param int|null $parentId - Parent ID saat ini
     * @return array - Nested tree
     */
    public function buildTree(array $menus, ?int $parentId = null): array
    {
        $tree = [];
        
        foreach ($menus as $menu) {
            // Cek apakah ini child dari parentId yang diminta
            $menuParentId = $menu->parent_id ?? null;
            
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
     * Build single node dari model MasterMenu
     * 
     * @param MasterMenu $menu
     * @return array
     */
    private function buildNode(MasterMenu $menu): array
    {
        return [
            'id' => (int) $menu->id,
            'name' => $menu->name,
            'type' => $menu->type,
            'icon' => $menu->icon ?? 'folder',
            'url' => $this->resolveUrl($menu),
            'page_id' => $menu->page_id ? (int) $menu->page_id : null,
            'form_id' => $menu->form_id ? (int) $menu->form_id : null,
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
     * @return string|array
     */
    public function resolveUrl(MasterMenu $menu): string
    {
        // Debug log
        \Yii::info('resolveUrl UserSidebar - type: ' . ($menu->type ?? 'null') . ', form_id: ' . ($menu->form_id ?? 'null') . ', page_id: ' . ($menu->page_id ?? 'null'), 'menu-url-debug');
        
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
                    return Url::to(['/page/view', 'id' => $menu->page_id]);
                }
                return '#';

            case MasterMenu::TYPE_FORM:
                \Yii::info('resolveUrl - TYPE_FORM case, form_id: ' . ($menu->form_id ?? 'null'), 'menu-url-debug');
                if (!empty($menu->form_id)) {
                    return Url::to(['/master-form/preview', 'id' => $menu->form_id]);
                }
                return '#';

            case MasterMenu::TYPE_GROUP:
            default:
                return '#';
        }
    }
    
    /**
     * Render HTML sidebar dari tree
     * RECURSIVE rendering
     * 
     * @param array|null $tree - Jika null, build dari DB
     * @return string - HTML output
     */
    public function renderHtml(?array $tree = null): string
    {
        if ($tree === null) {
            $tree = $this->getMenuTree(true);
        }
        
        return $this->renderMenuItems($tree, 0);
    }
    
    /**
     * Render menu items RECURSIVE
     * 
     * @param array $items - Tree structure
     * @param int $level - Nesting level
     * @return string - HTML
     */
    private function renderMenuItems(array $items, int $level = 0): string
    {
        $html = '';
        $indent = str_repeat('    ', $level);
        
        foreach ($items as $item) {
            $hasChildren = !empty($item['children']);
            $url = $item['url'];
            $name = htmlspecialchars($item['name']);
            $icon = htmlspecialchars($item['icon']);
            
            if ($hasChildren) {
                // Menu dengan children → dropdown/submenu
                $html .= $indent . '<div class="menu-group">' . "\n";
                $html .= $indent . '    <a href="#" class="menu-toggle">' . "\n";
                $html .= $indent . '        <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
                $html .= $indent . '        <span class="menu-label">' . $name . '</span>' . "\n";
                $html .= $indent . '        <span class="menu-arrow">expand_more</span>' . "\n";
                $html .= $indent . '    </a>' . "\n";
                $html .= $indent . '    <div class="submenu">' . "\n";
                $html .= $this->renderMenuItems($item['children'], $level + 1);
                $html .= $indent . '    </div>' . "\n";
                $html .= $indent . '</div>' . "\n";
            } else {
                // Menu tanpa children → link
                $html .= $indent . '<a href="' . $url . '" class="menu-link">' . "\n";
                $html .= $indent . '    <span class="material-symbols-outlined">' . $icon . '</span>' . "\n";
                $html .= $indent . '    <span class="menu-label">' . $name . '</span>' . "\n";
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
                    'url' => $this->resolveUrl($menu),
                ];
                $currentId = $menu->parent_id;
            } else {
                break;
            }
        }
        
        return array_reverse($path);
    }
    
    /**
     * Get direct children dari parent
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
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }
    
    /**
     * Get root menus (tanpa parent)
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
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }
}