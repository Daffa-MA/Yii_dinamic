<?php

namespace app\controllers;

use Yii;
use app\models\MasterMenu;
use app\models\MasterPage;
use yii\helpers\Url;
use app\services\MenuService;
use app\components\ActiveDatabaseContext;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class MasterMenuController extends Controller
{
    public $layout = 'dashboard';
    private $menuService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->menuService = new MenuService();
    }

    public function beforeAction($action)
    {
        $dbContext = new ActiveDatabaseContext();
        $result = $dbContext->resolveAndApply();
        if (!empty($result['isSwitched'])) {
            Yii::$app->db->schema->refresh();
        }
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'toggle' => ['POST', 'GET'],
                    'reorder' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * List all menus
     */
    public function actionIndex()
    {
        // Ensure columns exist
        MasterMenu::ensureColumnsExist();
        
        // Get all menus for tree building
        $menus = MasterMenu::find()
            ->with(['parent', 'page'])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
        
        // Build tree data
        $treeData = $this->buildTreeData($menus);
        
        $dataProvider = new ActiveDataProvider([
            'query' => MasterMenu::find()->with(['parent', 'page'])->orderBy(['sort_order' => SORT_ASC]),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'treeData' => $treeData,
        ]);
    }
    
    private function buildTreeData($menus)
    {
        $tree = [];
        $this->buildTreeRecursive($menus, null, 0, $tree);
        return $tree;
    }

    private function flattenErrors($errors): array
    {
        $messages = [];

        if ($errors instanceof \yii\base\Model) {
            $errors = $errors->getErrors();
        }

        if (!is_array($errors)) {
            $text = trim((string)$errors);
            return $text !== '' ? [$text] : [];
        }

        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                foreach ($this->flattenErrors($value) as $nestedMessage) {
                    $messages[] = $nestedMessage;
                }
                continue;
            }

            $text = trim((string)$value);
            if ($text !== '') {
                $messages[] = $text;
            } elseif (!is_int($key) && !is_string($value)) {
                $messages[] = trim((string)$key);
            }
        }

        return array_values(array_unique(array_filter($messages)));
    }
    
    private function buildTreeRecursive($menus, $parentId, $level, &$tree)
    {
        foreach ($menus as $menu) {
            if ($menu->parent_id == $parentId) {
                $children = array_filter($menus, function($m) use ($menu) {
                    return $m->parent_id == $menu->id;
                });
                
                $tree[] = [
                    'model' => $menu,
                    'level' => $level,
                    'isRoot' => $parentId === null,
                    'hasChildren' => count($children) > 0,
                    'childCount' => count($children),
                ];
                
                $this->buildTreeRecursive($menus, $menu->id, $level + 1, $tree);
            }
        }
    }

    /**
     * Create new menu
     */
    public function actionCreate()
    {
        MasterMenu::ensureColumnsExist();
        $model = new MasterMenu();
        
        // Get current max sort order for new menu
        $maxOrder = MasterMenu::find()->select('MAX([[sort_order]]) as max_order')->scalar();
        $model->sort_order = ($maxOrder ? (int)$maxOrder : 0) + 1;
        $model->type = 'page'; // Default type
        $model->is_active = 1; // Active by default

        if (Yii::$app->request->isPost) {
            // Ensure columns exist before loading
            MasterMenu::ensureColumnsExist();
            
            $postData = Yii::$app->request->post();
            
            // Debug: log what was submitted - specifically check form_id
            \Yii::info('POST data keys: ' . implode(', ', array_keys($postData)), 'menu-debug');
            \Yii::info('MasterMenu POST: ' . json_encode($postData['MasterMenu'] ?? []), 'menu-debug');
            
            // Check what name the form_id field is using
            $formIdSubmitted = $postData['MasterMenu']['form_id'] ?? $postData['form_id'] ?? 'NOT_IN_POST';
            \Yii::info('form_id SUBMITTED value: ' . $formIdSubmitted, 'menu-debug');
            
            if ($model->load($postData)) {
                \Yii::info('Model after load - type: ' . ($model->type ?? 'null') . ', form_id: ' . ($model->form_id ?? 'null') . ', page_id: ' . ($model->page_id ?? 'null'), 'menu-debug');
                
                // Normalize critical type/form fields from raw POST to avoid stale default "page"
                $postedType = trim((string)($postData['MasterMenu']['type'] ?? ''));
                $postedFormId = $postData['MasterMenu']['form_id'] ?? null;
                if ($postedType !== '') {
                    $model->type = $postedType;
                }
                if ($model->type === MasterMenu::TYPE_FORM && $postedFormId !== null && $postedFormId !== '') {
                    $model->form_id = (int)$postedFormId;
                }
                if ($model->type === MasterMenu::TYPE_FORM && empty($postedFormId) && !empty($postData['MasterMenu']['page_id'])) {
                    $model->type = MasterMenu::TYPE_PAGE;
                }
                
                // Force set form_id from POST if not loaded
                if (empty($model->form_id) && !empty($postData['MasterMenu']['form_id'])) {
                    $model->form_id = $postData['MasterMenu']['form_id'];
                    \Yii::info('Force set form_id to: ' . $model->form_id, 'menu-debug');
                }
                
                // Ensure sort_order is set
                if (empty($model->sort_order) || $model->sort_order <= 0) {
                    $maxOrder = MasterMenu::find()->select('MAX([[sort_order]]) as max_order')->scalar();
                    $model->sort_order = ($maxOrder ? (int)$maxOrder : 0) + 1;
                }
                
                // Debug: check if model is valid
                if ($model->validate()) {
                    \Yii::info('Model validation passed', 'menu-debug');
                } else {
                    \Yii::info('Model validation errors: ' . json_encode($model->getErrors()), 'menu-debug');
                }
                
                if ($model->save()) {
                    \Yii::info('Menu SAVED - id: ' . $model->id . ', type: ' . $model->type . ', form_id: ' . ($model->form_id ?? 'null'), 'menu-debug');
                    Yii::$app->session->setFlash('success', 'Menu berhasil dibuat!');
                    return $this->redirect(['index']);
                } else {
                    $errorMsg = implode('; ', $this->flattenErrors($model->getErrors()));
                    Yii::$app->session->setFlash('error', 'Gagal menyimpan menu: ' . $errorMsg);
                }
            } else {
                \Yii::info('Model load FAILED - post data not loaded properly', 'menu-debug');
                Yii::$app->session->setFlash('error', 'Data tidak valid - mohon periksa kembali form Anda');
            }
        }

        // Get data for dropdowns - only active items
        $menuItems = MasterMenu::find()->where(['is_active' => 1])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $pages = MasterPage::getActivePages();

        return $this->render('create', [
            'model' => $model,
            'pages' => $pages,
            'menuItems' => $menuItems,
        ]);
    }

    /**
     * Update menu
     */
    public function actionUpdate($id)
    {
        MasterMenu::ensureColumnsExist();
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            $result = $this->menuService->updateMenu($id, $postData);

            if ($result['success']) {
                Yii::$app->session->setFlash('success', $result['message']);
                return $this->redirect(['index']);
            } else {
                $messages = $this->flattenErrors($result['errors'] ?? []);
                Yii::$app->session->setFlash('error', implode('<br>', $messages ?: ['Gagal memperbarui menu.']));
                if (isset($result['model']) && $result['model'] instanceof MasterMenu) {
                    $model = $result['model'];
                } else {
                    $model->load($postData);
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'pages' => MasterPage::getActivePages(),
            'menuItems' => MasterMenu::find()->where(['!=', 'id', $id])->all(),
        ]);
    }

    /**
     * Delete menu
     */
    public function actionDelete($id)
    {
        MasterMenu::ensureColumnsExist();
        $result = $this->menuService->deleteMenu($id);

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            $messages = $this->flattenErrors($result['errors'] ?? []);
            Yii::$app->session->setFlash('error', implode('<br>', $messages ?: ['Gagal menghapus menu.']));
        }

        return $this->redirect(['index']);
    }

    /**
     * Toggle menu status
     */
    public function actionToggle($id)
    {
        MasterMenu::ensureColumnsExist();
        $result = $this->menuService->toggleStatus($id);

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            $messages = $this->flattenErrors($result['errors'] ?? []);
            Yii::$app->session->setFlash('error', implode('<br>', $messages ?: ['Gagal mengubah status menu.']));
        }

        return $this->redirect(['index']);
    }

    /**
     * Reorder menus
     */
    public function actionReorder()
    {
        $orderData = Yii::$app->request->post('order', []);

        $result = $this->menuService->reorder($orderData);

        Yii::$app->session->setFlash(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );

        return $this->redirect(['index']);
    }

    /**
     * Get all menus for dropdown (AJAX)
     */
    public function actionGetAllMenus()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Ensure columns exist
        MasterMenu::ensureColumnsExist();

        $menus = MasterMenu::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $result = [];
        $this->buildMenuFlatList($menus, null, 0, $result);

        return [
            'success' => true,
            'menus' => $result,
        ];
    }

    /**
     * Build flat menu list with depth
     */
    private function buildMenuFlatList($menus, $parentId, $depth, &$result)
    {
        foreach ($menus as $menu) {
            if ($menu->parent_id == $parentId) {
                $result[] = [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'depth' => $depth,
                ];
                $this->buildMenuFlatList($menus, $menu->id, $depth + 1, $result);
            }
        }
    }

    /**
     * Get menu tree for AJAX/JSON
     */
    public function actionGetTree()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $activeOnly = Yii::$app->request->get('active', true);

        return [
            'success' => true,
            'tree' => $this->menuService->getMenuTree($activeOnly)
        ];
    }

    /**
     * Resolve fallback link for menu items that still render as "#"
     */
    public function actionResolveLink($id)
    {
        $menu = $this->findModel($id);

        if (!empty($menu->form_id)) {
            return $this->redirect(['/master-form/preview', 'id' => $menu->form_id]);
        }

        if ($menu->type === MasterMenu::TYPE_PAGE && !empty($menu->page_id)) {
            return $this->redirect(['/page/view', 'id' => $menu->page_id]);
        }

        if ($menu->type === MasterMenu::TYPE_ROUTE && !empty($menu->route)) {
            $route = $menu->route[0] === '/' ? $menu->route : '/' . ltrim($menu->route, '/');
            return $this->redirect($route);
        }

        Yii::$app->session->setFlash('error', 'Link menu belum terhubung ke halaman/form.');
        return $this->redirect(Url::previous() ?: ['index']);
    }

    /**
     * Find model
     */
    protected function findModel($id)
    {
        if (($model = MasterMenu::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Menu tidak ditemukan.');
    }
}
