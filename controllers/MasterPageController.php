<?php

namespace app\controllers;

use Yii;
use app\models\Form;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\services\PageService;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class MasterPageController extends Controller
{
    public $layout = 'dashboard';
    private $pageService;

    public function __construct(string $id, \yii\base\Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->pageService = new PageService();
    }

    public function beforeAction($action)
    {
        // Disable CSRF for specific actions
        if (in_array($action->id, ['preview-layout', 'dynamic-create', 'dynamic-update'])) {
            $this->enableCsrfValidation = false;
        }

        $dbContext = new ActiveDatabaseContext();
        $dbContext->resolveAndApply();

        if (in_array($action->id, ['dynamic-create', 'dynamic-update', 'view-dynamic', 'ajax-save'], true)) {
            $this->ensureMasterPageAdvancedColumnsExist();
        }

        return parent::beforeAction($action);
    }

    private function ensureMasterPageAdvancedColumnsExist(): void
    {
        $db = Yii::$app->db;
        $table = '{{%master_page}}';
        $schema = $db->schema->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        $columns = [
            'page_type' => $db->schema->createColumnSchemaBuilder('string', 50)->defaultValue(MasterPage::PAGE_TYPE_BUILDER),
            'custom_html' => $db->schema->createColumnSchemaBuilder('text'),
            'custom_css' => $db->schema->createColumnSchemaBuilder('text'),
            'custom_js' => $db->schema->createColumnSchemaBuilder('text'),
        ];

        foreach ($columns as $name => $definition) {
            if (!isset($schema->columns[$name])) {
                $db->createCommand()->addColumn($table, $name, $definition)->execute();
                $db->schema->refreshTableSchema($table);
                $schema = $db->schema->getTableSchema($table, true);
            }
        }
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
     * Create new page - redirect to dynamic builder
     */
    public function actionCreate()
    {
        return $this->redirect(['dynamic-create']);
    }

    /**
     * Create page dengan visual builder
     */
    public function actionVisualCreate()
    {
        $model = new MasterPage();

        return $this->render('visual-create', [
            'model' => $model,
        ]);
    }

    /**
     * Update page dengan visual builder
     */
    public function actionVisualUpdate($id)
    {
        $model = $this->findModel($id);

        return $this->render('visual-update', [
            'model' => $model,
        ]);
    }

    /**
     * Save page content dari visual builder (AJAX)
     */
    public function actionVisualSave()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $pageId = Yii::$app->request->post('pageId');
        $title = Yii::$app->request->post('title');
        $slug = Yii::$app->request->post('slug');
        $content = Yii::$app->request->post('content');

        if (!$pageId) {
            return ['success' => false, 'message' => 'Page ID is required'];
        }

        $page = MasterPage::findOne($pageId);
        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        $page->title = $title ?: $page->title;
        $page->slug = $slug ?: $page->slug;
        $page->layout_json = $content;

        if ($page->save(false)) {
            return ['success' => true, 'message' => 'Page saved successfully', 'pageId' => $page->id];
        }

        return ['success' => false, 'message' => 'Failed to save page'];
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
     * Generate URL-friendly slug from title
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $existing = MasterPage::find()->where(['like', 'slug', $slug . '%'])->count();
        if ($existing > 0) {
            $slug .= '-' . ($existing + 1);
        }

        return $slug;
    }

    /**
     * Update page - redirect to dynamic builder
     */
    public function actionUpdate($id)
    {
        return $this->redirect(['dynamic-update', 'id' => $id]);
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
     * Duplicate page (AJAX)
     */
    public function actionDuplicate($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $sourcePage = $this->findModel($id);

        $newPage = new MasterPage();
        $newPage->name = $sourcePage->name . ' (Copy)';
        $newPage->slug = $this->generateSlug($sourcePage->name) . '-copy-' . time();
        $newPage->layout = $sourcePage->layout;
        $newPage->layout_json = $sourcePage->layout_json;
        $newPage->description = $sourcePage->description;
        $newPage->is_active = 0;

        if ($newPage->save(false)) {
            return ['success' => true, 'message' => 'Page duplicated successfully', 'newId' => $newPage->id];
        }

        return ['success' => false, 'message' => 'Failed to duplicate page'];
    }

    /**
     * View page - Page Inspector/Control Panel
     */
    public function actionView($id)
    {
        $page = $this->findModel($id);
        
        // Get attached forms
        $pageForms = $this->pageService->getPageForms($id);
        $forms = [];
        foreach ($pageForms as $pf) {
            $form = Form::findOne($pf->form_id);
            if ($form) {
                $forms[] = $form;
            }
        }

        // Parse layout_json to get components
        $components = [];
        $layoutJson = $page->layout_json;
        if (!empty($layoutJson)) {
            $decoded = json_decode($layoutJson, true);
            if (is_array($decoded)) {
                $components = $decoded;
            }
        }

        // Get related menus
        $menus = MasterMenu::find()->where(['page_id' => $id])->all();

        // Preview URL
        $previewUrl = '/page/' . $page->slug;
        $editUrl = ['dynamic-update', 'id' => $page->id];
        $liveUrl = $previewUrl;

        return $this->render('view-inspector', [
            'page' => $page,
            'forms' => $forms,
            'components' => $components,
            'menus' => $menus,
            'previewUrl' => $previewUrl,
            'editUrl' => $editUrl,
            'liveUrl' => $liveUrl,
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
     * Page Builder - Redirect to dynamic builder
     */
    public function actionBuilder($id)
    {
        return $this->redirect(['dynamic-update', 'id' => $id]);
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
     * AJAX Save for Modern Builder
     */
    public function actionAjaxSave()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $pageId = Yii::$app->request->post('page_id');
        $page = MasterPage::findOne($pageId);
        
        if (!$page) {
            return ['success' => false, 'message' => 'Page not found'];
        }

        $page->layout_json = Yii::$app->request->post('layout_json');
        $page->custom_html = Yii::$app->request->post('custom_html');
        $page->custom_css = Yii::$app->request->post('custom_css');
        $page->custom_js = Yii::$app->request->post('custom_js');
        $page->page_type = Yii::$app->request->post('page_type', 'builder');

        if ($page->save(false)) {
            return ['success' => true, 'message' => 'Page published successfully'];
        }

        return ['success' => false, 'message' => 'Failed to save page'];
    }

    /**
     * Preview page layout (AJAX)
     */
    public function actionPreviewLayout()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $layoutJson = Yii::$app->request->post('layout_json', '{}');

        try {
            $html = $this->renderPartial('//page/_preview-layout', [
                'layoutJson' => $layoutJson,
            ]);
            return ['success' => true, 'html' => $html];
        } catch (\Exception $e) {
            Yii::error('Preview layout error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Find available forms
     */
    private function findAvailableForms()
    {
        $query = Form::find()
            ->orderBy(['id' => SORT_ASC]);

        if (ProjectSchema::supportsProjectContext()) {
            $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
            if ($activeProjectId !== null) {
                $query->andWhere(['project_id' => $activeProjectId]);
            }
        }

        $rows = $query->all();

        $forms = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = trim((string) ($row->name ?? ''));
            if ($name === '' && isset($row->form_name)) {
                $name = trim((string) $row->form_name);
            }
            if ($name === '') {
                $name = 'Form #' . $id;
            }

            $forms[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $forms;
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

    /**
     * Dynamic Page Builder - Create
     */
    public function actionDynamicCreate()
    {
        $model = new MasterPage();

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            // Load data using Yii's model loading mechanism
            if ($model->load($postData)) {
                // title is mapped to name via __set in model
                if (empty($model->name)) {
                    $model->name = 'Untitled Page';
                }
                $model->slug = $this->generateSlug($model->name);
                $model->layout = 'dynamic';
                $model->is_active = 1;

                // Ensure layout_json is saved properly
                if (isset($postData['MasterPage']['layout_json'])) {
                    $model->layout_json = $postData['MasterPage']['layout_json'];
                }

                // Handle page_type and custom code
                if (isset($postData['MasterPage']['page_type'])) {
                    $model->page_type = $postData['MasterPage']['page_type'];
                }

                if (isset($postData['MasterPage']['custom_html'])) {
                    $model->custom_html = $postData['MasterPage']['custom_html'];
                }

                if (isset($postData['MasterPage']['custom_css'])) {
                    $model->custom_css = $postData['MasterPage']['custom_css'];
                }

                if (isset($postData['MasterPage']['custom_js'])) {
                    $model->custom_js = $postData['MasterPage']['custom_js'];
                }

                if ($model->save(false)) {
                    Yii::$app->session->setFlash('success', 'Halaman berhasil dibuat!');
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('error', 'Gagal membuat halaman. Errors: ' . json_encode($model->getErrors()));
                }
            } else {
                // Fallback: manual assignment if load fails
                $title = $postData['MasterPage']['title'] ?? 'Untitled Page';
                $model->name = $title;
                $model->slug = $this->generateSlug($title);
                $model->layout = 'dynamic';
                $model->is_active = 1;

                if (isset($postData['MasterPage']['layout_json'])) {
                    $model->layout_json = $postData['MasterPage']['layout_json'];
                } else {
                    $model->layout_json = '[]';
                }

                if (isset($postData['MasterPage']['page_type'])) {
                    $model->page_type = $postData['MasterPage']['page_type'];
                }

                if (isset($postData['MasterPage']['custom_html'])) {
                    $model->custom_html = $postData['MasterPage']['custom_html'];
                }

                if (isset($postData['MasterPage']['custom_css'])) {
                    $model->custom_css = $postData['MasterPage']['custom_css'];
                }

                if (isset($postData['MasterPage']['custom_js'])) {
                    $model->custom_js = $postData['MasterPage']['custom_js'];
                }

                if ($model->save(false)) {
                    Yii::$app->session->setFlash('success', 'Halaman berhasil dibuat!');
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('error', 'Gagal membuat halaman. Errors: ' . json_encode($model->getErrors()));
                }
            }
        }

        return $this->render('dynamic-builder', [
            'model' => $model,
            'initialState' => !empty($model->layout_json) ? json_decode($model->layout_json, true) : [],
            'forms' => $this->findAvailableForms(),
        ]);
    }

    /**
     * Dynamic Page Builder - Update
     */
    public function actionDynamicUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            // Handle title -> name mapping
            if (isset($postData['MasterPage']['title'])) {
                $model->name = $postData['MasterPage']['title'];
            }

            // Handle layout_json
            if (isset($postData['MasterPage']['layout_json'])) {
                $model->layout_json = $postData['MasterPage']['layout_json'];
            }

            if (isset($postData['MasterPage']['page_type'])) {
                $model->page_type = $postData['MasterPage']['page_type'];
            }

            if (isset($postData['MasterPage']['custom_html'])) {
                $model->custom_html = $postData['MasterPage']['custom_html'];
            }

            if (isset($postData['MasterPage']['custom_css'])) {
                $model->custom_css = $postData['MasterPage']['custom_css'];
            }

            if (isset($postData['MasterPage']['custom_js'])) {
                $model->custom_js = $postData['MasterPage']['custom_js'];
            }

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Halaman berhasil disimpan!');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Gagal menyimpan halaman. Errors: ' . json_encode($model->getErrors()));
            }
        }

        return $this->render('dynamic-builder', [
            'model' => $model,
            'initialState' => !empty($model->layout_json) ? json_decode($model->layout_json, true) : [],
            'forms' => $this->findAvailableForms(),
        ]);
    }

    /**
     * Get pages list for dropdown (AJAX)
     */
    public function actionGetPages()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $pages = MasterPage::find()
            ->select(['id', 'name', 'slug'])
            ->where(['is_active' => 1])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return [
            'success' => true,
            'pages' => array_map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name ?: ('Page ' . $p->id),
                    'slug' => $p->slug,
                ];
            }, $pages),
        ];
    }

    /**
     * Render dynamic page content for frontend
     */
    public function actionViewDynamic($slug)
    {
        $page = MasterPage::findOne(['slug' => $slug, 'is_active' => 1]);

        if (!$page) {
            throw new NotFoundHttpException('Halaman tidak ditemukan.');
        }

        $layoutJson = !empty($page->layout_json) ? $page->layout_json : '[]';

        $this->layout = 'main';
        
        return $this->render('@app/views/master-page/_dynamic_render', [
            'layoutJson' => $layoutJson,
            'customHtml' => $page->custom_html ?? null,
            'customCss' => $page->custom_css ?? null,
            'customJs' => $page->custom_js ?? null,
            'pageType' => $page->page_type ?? 'builder',
        ]);
    }
}
