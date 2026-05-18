<?php

namespace app\services;

use Yii;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\MasterForm;
use app\models\PageForms;
use app\models\Form;
use app\services\DynamicFormPreviewService;
use yii\helpers\Url;

/**
 * PageDisplayService - Handle menu click dan page rendering
 * 
 * Flow:
 * 1. Ambil menu berdasarkan id / slug
 * 2. Cek type - handle berbeda sesuai type
 * 3. Jika page → ambil page + forms
 * 4. Return structure untuk frontend
 */
class PageDisplayService
{
    /**
     * Handle menu click - return page data atau redirect URL
     * 
     * @param int|string $identifier - Menu ID atau slug
     * @return array
     */
    public function handleMenuClick($identifier): array
    {
        // Get menu
        $menu = $this->getMenu($identifier);
        
        if (!$menu) {
            return [
                'success' => false,
                'error' => 'Menu tidak ditemukan',
                'code' => 'MENU_NOT_FOUND'
            ];
        }

        // Handle berdasarkan type
        return $this->handleMenuType($menu);
    }

    /**
     * Get menu by ID atau slug
     * 
     * @param int|string $identifier
     * @return MasterMenu|null
     */
    public function getMenu($identifier): ?MasterMenu
    {
        if (is_numeric($identifier)) {
            return MasterMenu::findOne(['id' => $identifier, 'is_active' => 1]);
        }
        
        // Try by menu_key (slug-like)
        return MasterMenu::findOne(['menu_key' => $identifier, 'is_active' => 1]);
    }

    /**
     * Handle menu berdasarkan type
     * 
     * @param MasterMenu $menu
     * @return array
     */
    public function handleMenuType(MasterMenu $menu): array
    {
        switch ($menu->type) {
            case 'group':
                return $this->handleGroupType($menu);

            case 'route':
                return $this->handleRouteType($menu);

            case 'page':
                return $this->handlePageType($menu);

            case 'form':
                return $this->handleFormType($menu);

            default:
                return [
                    'success' => false,
                    'error' => 'Tipe menu tidak valid',
                    'code' => 'INVALID_TYPE'
                ];
        }
    }

    private function handleFormType(MasterMenu $menu): array
    {
        if (empty($menu->form_id)) {
            return [
                'success' => false,
                'error' => 'Form belum dipilih untuk menu ini',
                'code' => 'FORM_NOT_SET'
            ];
        }

        $form = MasterForm::findByIdScoped($menu->form_id);
        if (!$form) {
            return [
                'success' => false,
                'error' => 'Form tidak ditemukan',
                'code' => 'FORM_NOT_FOUND'
            ];
        }

        return [
            'success' => true,
            'type' => 'form',
            'action' => 'redirect',
            'redirect_url' => Url::to(['/master-form/preview', 'id' => $form->id]),
            'form_name' => $form->form_name,
            'form_id' => $form->id
        ];
    }

    /**
     * Handle Group type - tidak bisa dibuka, hanya dropdown
     * 
     * @param MasterMenu $menu
     * @return array
     */
    private function handleGroupType(MasterMenu $menu): array
    {
        // Get children untuk dropdown
        $children = MasterMenu::find()
            ->where(['parent_id' => $menu->id, 'is_active' => 1])
            ->orderBy(['order' => SORT_ASC])
            ->all();
        
        $childrenData = [];
        foreach ($children as $child) {
            $childrenData[] = [
                'id' => $child->id,
                'name' => $child->name,
                'type' => $child->type,
                'icon' => $child->icon,
                'url' => $this->getMenuUrl($child),
            ];
        }
        
        return [
            'success' => true,
            'type' => 'group',
            'action' => 'dropdown',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon,
            ],
            'children' => $childrenData,
            'message' => 'Menu ini adalah grup. Silakan pilih submenu.',
        ];
    }

    /**
     * Handle Route type - redirect ke URL
     * 
     * @param MasterMenu $menu
     * @return array
     */
    private function handleRouteType(MasterMenu $menu): array
    {
        $url = $this->getMenuUrl($menu);
        
        return [
            'success' => true,
            'type' => 'route',
            'action' => 'redirect',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon,
            ],
            'redirect_url' => $url,
            'message' => 'Redirect ke: ' . $url,
        ];
    }

    /**
     * Handle Page type - tampilkan halaman dengan form(s)
     * 
     * @param MasterMenu $menu
     * @return array
     */
    private function handlePageType(MasterMenu $menu): array
    {
        if (empty($menu->page_id)) {
            return [
                'success' => false,
                'error' => 'Menu page belum terhubung dengan halaman',
                'code' => 'PAGE_NOT_LINKED'
            ];
        }

        // Get page data
        $page = MasterPage::findOne($menu->page_id);
        
        // Case: Page tidak ditemukan
        if (!$page) {
            return [
                'success' => false,
                'type' => 'page',
                'action' => 'error',
                'menu' => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'page_id' => $menu->page_id,
                ],
                'error' => 'Halaman tidak ditemukan. Menu mungkin perlu diarahkan ke halaman lain.',
                'code' => 'PAGE_NOT_FOUND',
                'suggestion' => 'Hubungi administrator untuk memperbaiki tautan halaman.'
            ];
        }
        
        // Case: Page nonaktif
        if ($page->is_active != 1) {
            return [
                'success' => false,
                'type' => 'page',
                'action' => 'warning',
                'menu' => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'page_id' => $menu->page_id,
                ],
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'is_active' => false,
                ],
                'warning' => 'Halaman ini sedang tidak aktif. Silakan hubungi administrator untuk mengaktifkan.',
                'code' => 'PAGE_INACTIVE',
                'suggestion' => 'Anda bisa tetap mengakses menu ini tapi tidak dapat melihat isi halaman.',
                'can_activate' => true,
                'activate_url' => '/master-page/toggle?id=' . $page->id,
            ];
        }

        // Get forms dari page_forms (urut berdasarkan order)
        $pageForms = PageForms::find()
            ->where(['page_id' => $page->id])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        // Get form models
        $formsData = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $schema = method_exists($form, 'getSchema') ? $form->getSchema() : [];
                $formsData[] = [
                    'id' => $form->id,
                    'name' => $form->name,
                    'description' => $form->description ?? '',
                    'order' => $pf->order,
                    'schema' => is_array($schema) ? $schema : [],
                    'schema_json' => $form->schema_js ?? $form->schema_json ?? '{}',
                ];
            }
        }

        // Determine render mode
        $renderMode = $this->determineRenderMode($formsData);

        return [
            'success' => true,
            'type' => 'page',
            'action' => 'render_page',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon,
            ],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'layout' => $page->layout,
                'description' => $page->description,
                'page_type' => $page->page_type ?? MasterPage::PAGE_TYPE_BUILDER,
                'custom_html' => $page->custom_html ?? '',
                'custom_css' => $page->custom_css ?? '',
                'custom_js' => $page->custom_js ?? '',
                'page_custom_html' => $page->page_custom_html ?? '',
                'page_custom_css' => $page->page_custom_css ?? '',
                'page_custom_js' => $page->page_custom_js ?? '',
                'use_page_custom_code' => $page->use_page_custom_code ?? 0,
            ],
            'render' => [
                'mode' => $renderMode['mode'],
                'form_count' => count($formsData),
            ],
            'forms' => $formsData,
        ];
    }

    /**
     * Determine render mode berdasarkan jumlah form
     * 
     * @param array $forms
     * @return array
     */
    private function determineRenderMode(array $forms): array
    {
        $count = count($forms);
        
        if ($count === 0) {
            return [
                'mode' => 'empty',
                'message' => 'Belum ada form di halaman ini',
            ];
        }
        
        if ($count === 1) {
            return [
                'mode' => 'single',
                'form_id' => $forms[0]['id'],
                'message' => 'Tampilkan form langsung',
            ];
        }
        
        // More than 1 form → show as tabs
        return [
            'mode' => 'tabs',
            'tabs' => array_map(function($f) { return ['id' => $f['id'], 'name' => $f['name'], 'order' => $f['order']]; }, $forms),
            'message' => 'Tampilkan dalam ' . $count . ' tabs',
        ];
    }

    /**
     * Get menu URL berdasarkan type
     * 
     * @param MasterMenu $menu
     * @return string
     */
    public function getMenuUrl(MasterMenu $menu): string
    {
        switch ($menu->type) {
            case 'route':
                if (!empty($menu->route)) {
                    return $menu->route[0] === '/' 
                        ? $menu->route 
                        : '/' . ltrim($menu->route, '/');
                }
                return '#';
                
            case 'page':
                if (!empty($menu->page_id)) {
                    return '/page/view?id=' . $menu->page_id;
                }
                return '#';
            
            case 'form':
                if (!empty($menu->form_id)) {
                    return '/master-form/preview?id=' . $menu->form_id;
                }
                return '#';
                
            default:
                return '#';
        }
    }

    /**
     * Get page by ID atau slug
     * 
     * @param int|string $identifier
     * @return MasterPage|null
     */
    public function getPage($identifier): ?MasterPage
    {
        if (is_numeric($identifier)) {
            return MasterPage::findOne(['id' => $identifier, 'is_active' => 1]);
        }
        
        return MasterPage::findOne(['slug' => $identifier, 'is_active' => 1]);
    }

    /**
     * Get forms untuk page
     * 
     * @param int $pageId
     * @return array
     */
    public function getPageForms(int $pageId): array
    {
        $pageForms = PageForms::find()
            ->where(['page_id' => $pageId])
            ->orderBy(['order' => SORT_ASC])
            ->all();
        
        $forms = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $forms[] = $form;
            }
        }
        
        return $forms;
    }

    /**
     * Render page sebagai HTML (untuk API response)
     * 
     * @param array $pageData - Data dari handleMenuClick
     * @return string
     */
    public function renderPageHtml(array $pageData): string
    {
        if (!$pageData['success'] || $pageData['type'] !== 'page') {
            return '<div class="alert alert-error">Halaman tidak tersedia</div>';
        }

        $page = $pageData['page'];
        $forms = $pageData['forms'];
        $render = $pageData['render'];

        if ($this->hasCustomPageSource($page)) {
            return $this->renderCustomPageSource($page);
        }

        $html = '<div class="page-content">';
        
        // Page Header
        $html .= '<div class="page-header mb-4">';
        $html .= '<h1 class="text-2xl font-bold">' . htmlspecialchars($page['title']) . '</h1>';
        if (!empty($page['description'])) {
            $html .= '<p class="text-gray-600">' . htmlspecialchars($page['description']) . '</p>';
        }
        $html .= '</div>';

        // Render based on mode
        if ($render['mode'] === 'empty') {
            $html .= '<div class="alert alert-info">Belum ada form di halaman ini</div>';
        } 
        elseif ($render['mode'] === 'single') {
            $form = $forms[0];
            $html .= $this->renderSingleForm($form);
        } 
        elseif ($render['mode'] === 'tabs') {
            $html .= $this->renderTabs($pageData);
        }

        $html .= '</div>';
        
        return $html;
    }

    private function hasCustomPageSource(array $page): bool
    {
        return !empty($page['use_page_custom_code'])
            || (($page['page_type'] ?? MasterPage::PAGE_TYPE_BUILDER) === MasterPage::PAGE_TYPE_CUSTOM_CODE);
    }

    private function renderCustomPageSource(array $page): string
    {
        $customHtml = (string)(($page['page_custom_html'] ?? '') !== '' ? $page['page_custom_html'] : ($page['custom_html'] ?? ''));
        $customCss = trim((string)(($page['page_custom_css'] ?? '') !== '' ? $page['page_custom_css'] : ($page['custom_css'] ?? '')));
        $customJs = trim((string)(($page['page_custom_js'] ?? '') !== '' ? $page['page_custom_js'] : ($page['custom_js'] ?? '')));

        $customHtml = preg_replace_callback('/\{\{\s*form\s*:\s*(\d+)\s*\}\}/i', static function (array $matches): string {
            return (new DynamicFormPreviewService())->renderByScopedId((int)$matches[1], true, true, [
                'render_context' => 'page_content',
            ]);
        }, $customHtml);

        $html = '';
        if ($customHtml !== '' && (preg_match('/^\s*(<!doctype html|<html)\b/i', $customHtml) === 1)) {
            return $customHtml;
        }

        if ($customCss !== '') {
            $html .= '<style>' . $customCss . '</style>';
        }

        $html .= $customHtml;

        if ($customJs !== '') {
            $html .= '<script>(function(){try{' . $customJs . '}catch(e){console.error(e);}})();</script>';
        }

        return $html !== '' ? $html : '<div class="alert alert-info">Tidak ada custom content untuk halaman ini.</div>';
    }

    /**
     * Render single form
     */
    private function renderSingleForm(Form $form): string
    {
        return '<div class="form-render" data-form-id="' . $form->id . '">'
             . '<h3>' . htmlspecialchars($form->name) . '</h3>'
             . '<pre>' . htmlspecialchars($form->schema) . '</pre>'
             . '</div>';
    }

    /**
     * Render tabs untuk multiple forms
     */
    private function renderTabs(array $pageData): string
    {
        $tabs = $pageData['render']['tabs'];
        $forms = $pageData['forms'];
        
        $html = '<div class="tabs-container">';
        
        // Tab headers
        $html .= '<div class="tabs-header flex border-b mb-4">';
        foreach ($tabs as $index => $tab) {
            $active = $index === 0 ? 'active border-b-2 border-blue-500' : '';
            $html .= '<button class="tab-btn px-4 py-2 ' . $active . '" data-tab="' . $tab['id'] . '">'
                  . htmlspecialchars($tab['name']) 
                  . '</button>';
        }
        $html .= '</div>';
        
        // Tab contents
        $html .= '<div class="tabs-content">';
        foreach ($forms as $index => $form) {
            $display = $index === 0 ? 'block' : 'none';
            $html .= '<div class="tab-panel" id="tab-content-' . $form->id . '" style="display:' . $display . '">'
                  . '<h3>' . htmlspecialchars($form->name) . '</h3>'
                  . '<pre>' . htmlspecialchars($form->schema) . '</pre>'
                  . '</div>';
        }
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Handle menu click dengan project scope
     * 
     * @param int $menuId
     * @param int $projectId
     * @return array
     */
    public function handleMenuClickWithProject(int $menuId, int $projectId): array
    {
        // Get menu with project filter
        $menu = MasterMenu::find()
            ->where(['id' => $menuId, 'project_id' => $projectId])
            ->one();
        
        if (!$menu) {
            return [
                'success' => false,
                'error' => 'Menu tidak ditemukan di project ini',
                'code' => 'MENU_NOT_FOUND'
            ];
        }

        // Handle based on type
        return $this->handleMenuTypeWithProject($menu, $projectId);
    }

    /**
     * Handle menu type dengan project context
     */
    private function handleMenuTypeWithProject($menu, int $projectId): array
    {
        // Recovery path for legacy rows where form menu was saved as page with empty page_id
        if ($menu->type === 'page' && empty($menu->page_id) && !empty($menu->form_id)) {
            return $this->handleFormTypeWithProject($menu, $projectId);
        }

        switch ($menu->type) {
            case 'group':
                return $this->handleGroupTypeWithProject($menu, $projectId);
                
            case 'route':
                return $this->handleRouteType($menu);
                
            case 'page':
                return $this->handlePageTypeWithProject($menu, $projectId);

            case 'form':
                return $this->handleFormTypeWithProject($menu, $projectId);
                
            default:
                return [
                    'success' => false,
                    'error' => 'Tipe menu tidak valid',
                    'code' => 'INVALID_TYPE'
            ];
        }
    }

    /**
     * Handle Form type with project context
     */
    private function handleFormTypeWithProject($menu, int $projectId): array
    {
        if (empty($menu->form_id)) {
            return [
                'success' => false,
                'error' => 'Menu form belum terhubung dengan formulir',
                'code' => 'FORM_NOT_LINKED'
            ];
        }

        $form = MasterForm::findByIdScoped($menu->form_id);

        if (!$form || (int)$form->is_active !== 1) {
            return [
                'success' => false,
                'error' => $form ? 'Form tidak aktif' : 'Form tidak ditemukan',
                'code' => $form ? 'FORM_INACTIVE' : 'FORM_NOT_FOUND'
            ];
        }

        return [
            'success' => true,
            'type' => 'form',
            'action' => 'redirect',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon,
            ],
            'form' => [
                'id' => $form->id,
                'name' => $form->form_name,
            ],
            'redirect_url' => Url::to(['/master-form/preview', 'id' => $form->id]),
        ];
    }

    /**
     * Handle Group type with project context
     */
    private function handleGroupTypeWithProject($menu, int $projectId): array
    {
        $children = MasterMenu::find()
            ->where(['parent_id' => $menu->id, 'project_id' => $projectId, 'is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
        
        $childrenData = [];
        foreach ($children as $child) {
            $childrenData[] = [
                'id' => $child->id,
                'name' => $child->name,
                'type' => $child->type,
                'icon' => $child->icon,
                'url' => $this->getMenuUrl($child),
            ];
        }
        
        return [
            'success' => true,
            'type' => 'group',
            'action' => 'dropdown',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon,
            ],
            'children' => $childrenData,
            'message' => 'Menu ini adalah grup. Silakan pilih submenu.',
        ];
    }

    /**
     * Handle Page type with project context
     */
    private function handlePageTypeWithProject($menu, int $projectId): array
    {
        if (empty($menu->page_id)) {
            return [
                'success' => false,
                'error' => 'Menu page belum terhubung dengan halaman',
                'code' => 'PAGE_NOT_LINKED'
            ];
        }

        // Get page with project filter
        $page = MasterPage::find()
            ->where(['id' => $menu->page_id, 'project_id' => $projectId])
            ->one();
        
        if (!$page || $page->is_active != 1) {
            return [
                'success' => false,
                'error' => $page ? 'Halaman tidak aktif' : 'Halaman tidak ditemukan',
                'code' => $page ? 'PAGE_INACTIVE' : 'PAGE_NOT_FOUND'
            ];
        }

        // Get forms from page_forms with project filter
        $pageForms = PageForms::find()
            ->where(['page_id' => $page->id, 'project_id' => $projectId])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $formsData = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $schema = method_exists($form, 'getSchema') ? $form->getSchema() : [];
                $formsData[] = [
                    'id' => $form->id,
                    'name' => $form->name,
                    'description' => $form->description ?? '',
                    'order' => $pf->order,
                    'schema' => is_array($schema) ? $schema : [],
                    'schema_json' => $form->schema_js ?? $form->schema_json ?? '{}',
                ];
            }
        }

        $renderMode = $this->determineRenderMode($formsData);

        return [
            'success' => true,
            'type' => 'page',
            'action' => 'render_page',
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon,
            ],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'layout' => $page->layout,
                'description' => $page->description,
                'page_type' => $page->page_type ?? MasterPage::PAGE_TYPE_BUILDER,
                'custom_html' => $page->custom_html ?? '',
                'custom_css' => $page->custom_css ?? '',
                'custom_js' => $page->custom_js ?? '',
                'page_custom_html' => $page->page_custom_html ?? '',
                'page_custom_css' => $page->page_custom_css ?? '',
                'page_custom_js' => $page->page_custom_js ?? '',
                'use_page_custom_code' => $page->use_page_custom_code ?? 0,
            ],
            'render' => [
                'mode' => $renderMode['mode'],
                'form_count' => count($formsData),
            ],
            'forms' => $formsData,
        ];
    }

    /**
     * Get page with project context
     */
    public function getPageWithProject(int $pageId, int $projectId): array
    {
        $page = MasterPage::find()
            ->where(['id' => $pageId, 'project_id' => $projectId, 'is_active' => 1])
            ->one();

        if (!$page) {
            return [
                'success' => false,
                'error' => 'Halaman tidak ditemukan',
                'code' => 'PAGE_NOT_FOUND'
            ];
        }

        // Get forms
        $pageForms = PageForms::find()
            ->where(['page_id' => $pageId, 'project_id' => $projectId])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $formsData = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $schema = method_exists($form, 'getSchema') ? $form->getSchema() : [];
                $formsData[] = [
                    'id' => $form->id,
                    'name' => $form->name,
                    'description' => $form->description ?? '',
                    'order' => $pf->order,
                    'schema' => is_array($schema) ? $schema : [],
                    'schema_json' => $form->schema_js ?? $form->schema_json ?? '{}',
                ];
            }
        }

        $renderMode = $this->determineRenderMode($formsData);

        return [
            'success' => true,
            'type' => 'page',
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'layout' => $page->layout,
                'description' => $page->description,
            ],
            'render' => [
                'mode' => $renderMode['mode'],
                'form_count' => count($formsData),
            ],
            'forms' => $formsData,
        ];
    }

    /**
     * Get forms for page with project context
     */
    public function getPageFormsWithProject(int $pageId, int $projectId): array
    {
        $pageForms = PageForms::find()
            ->where(['page_id' => $pageId, 'project_id' => $projectId])
            ->orderBy(['order' => SORT_ASC])
            ->all();

        $forms = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $forms[] = $form;
            }
        }

        return $forms;
    }
}
