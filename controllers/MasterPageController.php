<?php

namespace app\controllers;

use Yii;
use app\models\Form;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\PageForms;
use app\services\PageService;
use app\components\ActiveDatabaseContext;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class MasterPageController extends Controller
{
    public $layout = 'dashboard';
    private $pageService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->pageService = new PageService();
    }

    public function beforeAction($action)
    {
        $dbContext = new ActiveDatabaseContext();
        $dbContext->resolveAndApply();
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
                ],
            ],
        ];
    }

    /**
     * List all pages
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => MasterPage::find()->orderBy(['id' => SORT_ASC]),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create new page
     */
    public function actionCreate()
    {
        $model = new MasterPage();

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            $formIds = Yii::$app->request->post('formIds', []);
            
            // Check for "connect to menu" flag
            $connectToMenu = isset($postData['connect_to_menu']) && $postData['connect_to_menu'] == 1;
            $menuName = $postData['menu_name'] ?? '';
            $menuParentId = !empty($postData['menu_parent_id']) ? (int)$postData['menu_parent_id'] : null;
            
            $result = $this->pageService->createPage($postData, $formIds);
            
            if ($result['success']) {
                // Create menu if checkbox is checked
                if ($connectToMenu && !empty($menuName) && $result['model']) {
                    $page = $result['model'];
                    $this->createMenuFromPage($page, $menuName, $menuParentId);
                }
                
                Yii::$app->session->setFlash('success', $result['message']);
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
                $model->load($postData);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'availableForms' => $this->findAvailableForms(),
            'layoutOptions' => PageService::getLayoutOptions(),
        ]);
    }

    /**
     * Create menu from page
     */
    private function createMenuFromPage(MasterPage $page, string $menuName, ?int $parentId): bool
    {
        $menu = new MasterMenu();
        $menu->name = $menuName;
        $menu->type = 'page';
        $menu->page_id = $page->id;
        $menu->parent_id = $parentId;
        $menu->is_active = 1;
        $menu->sort_order = MasterMenu::find()->max('sort_order') + 1;
        
        if ($menu->save(false)) {
            Yii::$app->session->setFlash('info', "Menu '{$menuName}' berhasil dibuat dan terhubung ke halaman.");
            return true;
        }
        
        return false;
    }

    /**
     * Update page
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // Load current forms
        $currentForms = $this->pageService->getPageForms($id);
        $model->formIds = array_map(function($pf) { return $pf->form_id; }, $currentForms);

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            $formIds = Yii::$app->request->post('formIds', []);
            
            $result = $this->pageService->updatePage($id, $postData, $formIds);
            
            if ($result['success']) {
                Yii::$app->session->setFlash('success', $result['message']);
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
                $model->load($postData);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'availableForms' => $this->findAvailableForms(),
            'currentForms' => $currentForms,
            'layoutOptions' => PageService::getLayoutOptions(),
        ]);
    }

    /**
     * Delete page
     */
    public function actionDelete($id)
    {
        $result = $this->pageService->deletePage($id);
        
        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Toggle page status
     */
    public function actionToggle($id)
    {
        $result = $this->pageService->toggleStatus($id);
        
        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
        }
        
        return $this->redirect(['index']);
    }

    /**
     * View page with forms
     */
    public function actionView($id)
    {
        $page = $this->findModel($id);
        $pageForms = $this->pageService->getPageForms($id);
        
        // Get form models
        $forms = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $forms[] = $form;
            }
        }

        return $this->render('view', [
            'page' => $page,
            'forms' => $forms,
        ]);
    }

    /**
     * Add form to page (AJAX)
     */
    public function actionAddForm()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $pageId = Yii::$app->request->post('pageId');
        $formId = Yii::$app->request->post('formId');
        
        $result = $this->pageService->addFormToPage($pageId, $formId);
        
        return $result;
    }

    /**
     * Remove form from page (AJAX)
     */
    public function actionRemoveForm()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $pageId = Yii::$app->request->post('pageId');
        $formId = Yii::$app->request->post('formId');
        
        $result = $this->pageService->removeFormFromPage($pageId, $formId);
        
        return $result;
    }

    /**
     * Page Builder - Visual drag & drop layout builder
     */
    public function actionBuilder($id)
    {
        $page = $this->findModel($id);
        $availableForms = $this->findAvailableForms();
        
        if (Yii::$app->request->isPost) {
            $layoutJson = Yii::$app->request->post('layout_json');
            $page->layout_json = $layoutJson;
            
            if ($page->save(false)) {
                Yii::$app->session->setFlash('success', 'Layout halaman berhasil disimpan.');
                return $this->redirect(['builder', 'id' => $id]);
            } else {
                Yii::$app->session->setFlash('error', 'Gagal menyimpan layout.');
            }
        }
        
        return $this->render('builder', [
            'page' => $page,
            'availableForms' => $availableForms,
        ]);
    }

    /**
     * Save layout (AJAX)
     */
    public function actionSaveLayout()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $pageId = Yii::$app->request->post('page_id');
        $layoutJson = Yii::$app->request->post('layout_json');
        
        $page = MasterPage::findOne($pageId);
        if (!$page) {
            return ['success' => false, 'message' => 'Halaman tidak ditemukan'];
        }
        
        $page->layout_json = $layoutJson;
        
        if ($page->save(false)) {
            return ['success' => true, 'message' => 'Layout disimpan'];
        }
        
        return ['success' => false, 'message' => 'Gagal menyimpan layout'];
    }

    /**
     * Preview page layout (AJAX)
     */
    public function actionPreviewLayout()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $layoutJson = Yii::$app->request->post('layout_json', '{}');
        
        return ['success' => true, 'html' => $this->renderPartial('//page/_preview-layout', [
            'layoutJson' => $layoutJson,
        ])];
    }

    /**
     * Find available forms
     */
    private function findAvailableForms()
    {
        return Form::find()
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /**
     * Find model
     */
    protected function findModel($id)
    {
        if (($model = MasterPage::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Halaman tidak ditemukan.');
    }
}