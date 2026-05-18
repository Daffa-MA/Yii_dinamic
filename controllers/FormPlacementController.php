<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\FormPlacement;
use app\models\SidebarMenu;
use app\models\MasterForm;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\models\ProjectUser;
use app\components\ProjectSchema;

/**
 * FormPlacement Controller
 * Handles form placement, menu management, and page routing
 */
class FormPlacementController extends Controller
{
    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    private function findScopedForm($formId): ?MasterForm
    {
        return MasterForm::findByIdScoped($formId);
    }

    private function getWorkspaceAuthenticatedUser(?int $projectId = null): ?ProjectUser
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        $resolvedProjectId = $projectId ?? $this->getActiveProjectId();
        if ($resolvedProjectId === null) {
            return null;
        }

        return (new ProjectAuthContext())->getAuthenticatedUser($resolvedProjectId);
    }

    private function getEffectiveUserId(): ?int
    {
        $workspaceUser = $this->getWorkspaceAuthenticatedUser();
        if ($workspaceUser !== null) {
            return (int)$workspaceUser->id;
        }

        if (!Yii::$app->user->isGuest && Yii::$app->user->id !== null) {
            return (int)Yii::$app->user->id;
        }

        return null;
    }

    private function canAccessFormPlacementController(): bool
    {
        if ((new CommanderAuthContext())->isSuperAdmin()) {
            return true;
        }

        return $this->getWorkspaceAuthenticatedUser() !== null;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        (new \app\components\ActiveDatabaseContext())->resolveAndApply();
        Yii::$app->db->schema->refresh();

        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = $this->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola form placement.');
            $this->redirect(['project/index']);
            return false;
        }

        return true;
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            return $this->canAccessFormPlacementController();
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Get menu tree for sidebar
     */
    public function actionGetMenuTree()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $userId = $this->getEffectiveUserId();
            $tree = SidebarMenu::getMenuTree(null, $userId);
            
            return [
                'success' => true,
                'tree' => $tree,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create or update placement for a form
     */
    public function actionSavePlacement($form_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $form = $this->findScopedForm($form_id);
            if (!$form) {
                throw new NotFoundHttpException('Form not found');
            }

            $post = Yii::$app->request->post();
            
            $placement = FormPlacement::find()->where(['form_id' => $form_id])->one();
            if (!$placement) {
                $placement = new FormPlacement();
                $placement->form_id = $form_id;
            }

            $placement->load($post);
            
            if (empty($placement->page_slug)) {
                $placement->page_slug = FormPlacement::generateSlug($placement->page_title ?: $form->form_name);
            }
            if (empty($placement->route_path)) {
                $placement->route_path = FormPlacement::generateRoute($placement->page_slug);
            }

            if ($placement->save()) {
                if ($placement->show_in_sidebar && $placement->page_title) {
                    $this->syncMenu($placement);
                }
                
                return [
                    'success' => true,
                    'placement' => $placement,
                    'message' => 'Placement saved successfully',
                ];
            }

            return [
                'success' => false,
                'errors' => $placement->errors,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get placement by form ID
     */
    public function actionGetPlacement($form_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $placement = FormPlacement::find()->where(['form_id' => $form_id])->one();
            if ($placement !== null) {
                $placementForm = $this->findScopedForm($placement->form_id);
                if ($placementForm === null) {
                    return [
                        'success' => false,
                        'error' => 'Form not found',
                    ];
                }
            }
            
            return [
                'success' => true,
                'placement' => $placement,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create new menu item
     */
    public function actionCreateMenu()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $post = Yii::$app->request->post();
            
            $menu = new SidebarMenu();
            $menu->load($post);
            $effectiveUserId = $this->getEffectiveUserId();
            if ($effectiveUserId !== null) {
                $menu->user_id = $effectiveUserId;
            }
            
            if (isset($post['form_id'])) {
                $form = $this->findScopedForm($post['form_id']);
                if ($form) {
                    $menu->route = '/form/' . ($post['page_slug'] ?? $form->slug);
                }
            }

            if ($menu->save()) {
                return [
                    'success' => true,
                    'menu' => $menu,
                    'message' => 'Menu created successfully',
                ];
            }

            return [
                'success' => false,
                'errors' => $menu->errors,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update menu item
     */
    public function actionUpdateMenu($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $menu = SidebarMenu::findOne($id);
            if (!$menu) {
                throw new NotFoundHttpException('Menu not found');
            }

            $post = Yii::$app->request->post();
            $menu->load($post);

            if ($menu->save()) {
                return [
                    'success' => true,
                    'menu' => $menu,
                ];
            }

            return [
                'success' => false,
                'errors' => $menu->errors,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete menu item
     */
    public function actionDeleteMenu($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $menu = SidebarMenu::findOne($id);
            if (!$menu) {
                throw new NotFoundHttpException('Menu not found');
            }

            $menu->delete();

            return [
                'success' => true,
                'message' => 'Menu deleted successfully',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get menu dropdown list
     */
    public function actionGetMenuList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $userId = $this->getEffectiveUserId();
            $excludeId = Yii::$app->request->get('exclude_id');
            
            $items = SidebarMenu::getDropdownItems($excludeId, $userId);

            return [
                'success' => true,
                'items' => $items,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update menu order
     */
    public function actionUpdateOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $post = Yii::$app->request->post('items', []);
            $parentId = Yii::$app->request->post('parent_id');
            
            SidebarMenu::updateOrder($post, $parentId);

            return [
                'success' => true,
                'message' => 'Menu order updated',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Render form page by slug
     */
    public function actionView($slug)
    {
        $placement = FormPlacement::findBySlug($slug);
        
        if (!$placement) {
            throw new NotFoundHttpException('Page not found');
        }

        $form = $placement->form;
        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        return $this->render('@app/views/form-renderer/view', [
            'placement' => $placement,
            'form' => $form,
        ]);
    }

    /**
     * Sync menu with placement
     */
    private function syncMenu(FormPlacement $placement)
    {
        if ($placement->menu_id) {
            $menu = SidebarMenu::findOne($placement->menu_id);
        } else {
            $menu = new SidebarMenu();
            $menu->user_id = $placement->form->user_id ?? $this->getEffectiveUserId();
            $menu->type = SidebarMenu::TYPE_LINK;
        }

        $menu->label = $placement->page_title ?: $placement->form->form_name;
        $menu->route = $placement->route_path;
        $menu->visibility = $placement->is_public ? SidebarMenu::VISIBILITY_PUBLIC : SidebarMenu::VISIBILITY_AUTHENTICATED;
        $menu->is_active = true;

        if ($menu->save()) {
            $placement->menu_id = $menu->id;
            $placement->save(false);
        }
    }

    /**
     * Get available icons
     */
    public function actionGetIcons()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $icons = [
            'ti ti-home' => 'Home',
            'ti ti-file-text' => 'Document',
            'ti ti-users' => 'Users',
            'ti ti-user' => 'User',
            'ti ti-settings' => 'Settings',
            'ti ti-database' => 'Database',
            'ti ti-chart-bar' => 'Chart',
            'ti ti-calendar' => 'Calendar',
            'ti ti-mail' => 'Mail',
            'ti ti-bell' => 'Notification',
            'ti ti-search' => 'Search',
            'ti ti-plus' => 'Add',
            'ti ti-edit' => 'Edit',
            'ti ti-trash' => 'Delete',
            'ti ti-eye' => 'View',
            'ti ti-download' => 'Download',
            'ti ti-upload' => 'Upload',
            'ti ti-check' => 'Check',
            'ti ti-x' => 'Close',
            'ti ti-menu' => 'Menu',
            'ti ti-dots' => 'More',
            'ti ti-folder' => 'Folder',
            'ti ti-folder-open' => 'Folder Open',
            'ti ti-star' => 'Star',
            'ti ti-heart' => 'Heart',
            'ti ti-lock' => 'Lock',
            'ti ti-unlock' => 'Unlock',
            'ti ti-key' => 'Key',
            'ti ti-cog' => 'Cog',
            'ti ti-tool' => 'Tool',
            'ti ti-package' => 'Package',
            'ti ti-truck' => 'Truck',
            'ti ti-phone' => 'Phone',
            'ti ti-device-mobile' => 'Mobile',
            'ti ti-world' => 'World',
            'ti ti-globe' => 'Globe',
            'ti ti-map' => 'Map',
            'ti ti-location' => 'Location',
            'ti ti-camera' => 'Camera',
            'ti ti-image' => 'Image',
            'ti ti-music' => 'Music',
            'ti ti-video' => 'Video',
            'ti ti-document' => 'Document',
            'ti ti-clipboard' => 'Clipboard',
            'ti ti-list' => 'List',
            'ti ti-report' => 'Report',
            'ti ti-shopping-cart' => 'Cart',
            'ti ti-credit-card' => 'Payment',
            'ti ti-gift' => 'Gift',
            'ti ti-flag' => 'Flag',
            'ti ti-bookmark' => 'Bookmark',
            'ti ti-share' => 'Share',
            'ti ti-brand-tabler' => 'Brand',
            'ti ti-forms' => 'Forms',
            'ti ti-layout' => 'Layout',
            'ti ti-columns' => 'Columns',
            'ti ti-sidebar' => 'Sidebar',
            'ti ti-stack' => 'Stack',
        ];

        return [
            'success' => true,
            'icons' => $icons,
        ];
    }
}
