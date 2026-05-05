<?php

namespace app\services;

use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\PageForms;

/**
 * EdgeCaseValidator - Validasi untuk edge cases dalam CMS
 * 
 * Edge cases yang ditangani:
 * 1. Circular menu (A → B → A)
 * 2. Menu tanpa page & route (hanya untuk group)
 * 3. Page tanpa form (valid - page kosong)
 * 4. Menu nonaktif (tidak tampil di sidebar)
 * 5. Page nonaktif (menu ada tapi tidak bisa dibuka)
 */
class EdgeCaseValidator
{
    /**
     * Validasi complete menu sebelum create/update
     * Return array dengan semua edge case checks
     * 
     * @param MasterMenu $menu
     * @return array
     */
    public function validateMenu(MasterMenu $menu): array
    {
        $issues = [];
        $warnings = [];
        
        // Edge Case 1: Circular Parent (A → B → A)
        $circularCheck = $this->checkCircularParent($menu);
        if ($circularCheck['is_circular']) {
            $issues[] = [
                'type' => 'circular_parent',
                'severity' => 'error',
                'message' => 'Tidak boleh ada circular parent. Menu tidak bisa menjadi ancestor dirinya sendiri.',
                'code' => 'CIRCULAR_MENU',
                'path' => $circularCheck['path'],
            ];
        }
        
        // Edge Case 2: Menu tanpa page & route (non-group)
        if ($menu->type !== 'group') {
            if ($menu->type === 'page' && empty($menu->page_id)) {
                $issues[] = [
                    'type' => 'missing_page',
                    'severity' => 'error',
                    'message' => 'Menu tipe Page wajib terhubung ke halaman.',
                    'code' => 'PAGE_REQUIRED',
                ];
            }
            if ($menu->type === 'route' && empty($menu->route)) {
                $issues[] = [
                    'type' => 'missing_route',
                    'severity' => 'error',
                    'message' => 'Menu tipe Route wajib mengisi URL.',
                    'code' => 'ROUTE_REQUIRED',
                ];
            }
        }
        
        // Edge Case 5: Page nonaktif warning
        if (!empty($menu->page_id)) {
            $page = MasterPage::findOne($menu->page_id);
            if ($page && $page->is_active != 1) {
                $warnings[] = [
                    'type' => 'page_inactive',
                    'severity' => 'warning',
                    'message' => "Halaman '{$page->title}' sedang tidak aktif.",
                    'code' => 'PAGE_INACTIVE',
                    'page_id' => $page->id,
                    'suggestion' => 'Menu akan tetap tampil di sidebar tapi tidak bisa dibuka.',
                ];
            }
        }
        
        return [
            'is_valid' => empty($issues),
            'errors' => $issues,
            'warnings' => $warnings,
            'summary' => $this->buildSummary($issues, $warnings),
        ];
    }
    
    /**
     * Cek circular parent dengan traversal
     * 
     * @param MasterMenu $menu
     * @return array
     */
    public function checkCircularParent(MasterMenu $menu): array
    {
        if (empty($menu->parent_id)) {
            return ['is_circular' => false, 'path' => []];
        }
        
        $path = [];
        $visited = [];
        $currentParentId = $menu->parent_id;
        
        while ($currentParentId) {
            // Prevent infinite loop
            if (in_array($currentParentId, $visited)) {
                return ['is_circular' => true, 'path' => $path];
            }
            
            $visited[] = $currentParentId;
            $parent = MasterMenu::findOne($currentParentId);
            
            if (!$parent) {
                break;
            }
            
            $path[] = [
                'id' => $parent->id,
                'name' => $parent->name,
            ];
            
            // If parent is the original menu itself
            if ($parent->id == $menu->id) {
                return ['is_circular' => true, 'path' => $path];
            }
            
            $currentParentId = $parent->parent_id;
        }
        
        return ['is_circular' => false, 'path' => $path];
    }
    
    /**
     * Validate page - check edge cases
     * 
     * @param MasterPage $page
     * @return array
     */
    public function validatePage(MasterPage $page): array
    {
        $issues = [];
        $warnings = [];
        
        // Edge Case 3: Page tanpa form (valid - ini adalah warning/info, bukan error)
        $pageForms = PageForms::find()->where(['page_id' => $page->id])->count();
        if ($pageForms == 0) {
            $warnings[] = [
                'type' => 'no_forms',
                'severity' => 'info',
                'message' => 'Halaman ini belum memiliki form. Halaman akan kosong.',
                'code' => 'PAGE_EMPTY',
                'form_count' => 0,
                'suggestion' => 'Tambahkan form dari menu Master Page > Edit.',
            ];
        }
        
        // Check connected menus
        $connectedMenus = MasterMenu::find()
            ->where(['page_id' => $page->id])
            ->all();
        
        $inactiveMenus = array_filter($connectedMenus, function($m) { return $m->is_active != 1; });
        
        if (!empty($inactiveMenus)) {
            $warnings[] = [
                'type' => 'inactive_menus',
                'severity' => 'warning',
                'message' => count($inactiveMenus) . ' menu terhubung ke halaman ini sedang nonaktif.',
                'code' => 'INACTIVE_MENUS',
            ];
        }
        
        return [
            'is_valid' => empty($issues),
            'errors' => $issues,
            'warnings' => $warnings,
            'page_forms_count' => $pageForms,
            'connected_menus_count' => count($connectedMenus),
            'active_menus_count' => count($connectedMenus) - count($inactiveMenus),
        ];
    }
    
    /**
     * Get semua menus yang tidak bisa dibuka (linked ke page nonaktif)
     * 
     * @return array
     */
    public function getInactivePageMenus(): array
    {
        $menus = MasterMenu::find()
            ->where(['type' => 'page'])
            ->andWhere(['is_active' => 1])
            ->all();
        
        $inactivePageMenus = [];
        
        foreach ($menus as $menu) {
            if (!empty($menu->page_id)) {
                $page = MasterPage::findOne($menu->page_id);
                if ($page && $page->is_active != 1) {
                    $inactivePageMenus[] = [
                        'menu_id' => $menu->id,
                        'menu_name' => $menu->name,
                        'page_id' => $page->id,
                        'page_title' => $page->title,
                        'activate_url' => '/master-page/toggle?id=' . $page->id,
                    ];
                }
            }
        }
        
        return $inactivePageMenus;
    }
    
    /**
     * Get menus yang valid untuk sidebar (exclude inactive)
     * 
     * @return array
     */
    public function getSidebarValidMenus(): array
    {
        $menus = MasterMenu::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'order' => SORT_ASC])
            ->all();
        
        $valid = [];
        $withWarnings = [];
        
        foreach ($menus as $menu) {
            $valid[] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'type' => $menu->type,
                'has_warning' => false,
            ];
        }
        
        return [
            'valid_menus' => $valid,
            'menus_with_warnings' => $withWarnings,
            'total' => count($valid),
        ];
    }
    
    /**
     * Build human-readable summary
     */
    private function buildSummary(array $errors, array $warnings): string
    {
        $parts = [];
        
        if (count($errors) > 0) {
            $parts[] = count($errors) . ' error(s)';
        }
        
        if (count($warnings) > 0) {
            $parts[] = count($warnings) . ' warning(s)';
        }
        
        if (empty($parts)) {
            return 'Valid';
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Static helper untuk quick validation
     * 
     * @param int $menuId
     * @return array
     */
    public static function quickMenuCheck(int $menuId): array
    {
        $validator = new self();
        $menu = MasterMenu::findOne($menuId);
        
        if (!$menu) {
            return [
                'exists' => false,
                'message' => 'Menu tidak ditemukan',
            ];
        }
        
        return [
            'exists' => true,
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'type' => $menu->type,
                'is_active' => $menu->is_active,
                'has_page' => !empty($menu->page_id),
                'page_active' => $menu->page_id 
                    ? (MasterPage::findOne($menu->page_id)?->is_active ?? false) 
                    : null,
            ],
            'validation' => $validator->validateMenu($menu),
        ];
    }
    
    /**
     * Static helper untuk quick page check
     * 
     * @param int $pageId
     * @return array
     */
    public static function quickPageCheck(int $pageId): array
    {
        $validator = new self();
        $page = MasterPage::findOne($pageId);
        
        if (!$page) {
            return [
                'exists' => false,
                'message' => 'Halaman tidak ditemukan',
            ];
        }
        
        return [
            'exists' => true,
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'is_active' => $page->is_active,
            ],
            'validation' => $validator->validatePage($page),
        ];
    }
}