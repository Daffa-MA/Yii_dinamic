<?php

namespace app\controllers;

use Yii;
use app\models\Form;
use app\models\MasterMenu;
use app\models\MasterPage;
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
        // Disable CSRF for specific actions
        if (in_array($action->id, ['preview-layout', 'dynamic-create', 'dynamic-update'])) {
            $this->enableCsrfValidation = false;
        }
        
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

    /**
     * Dynamic Page Builder - Create
     */
    public function actionDynamicCreate()
    {
        $model = new MasterPage();

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            // Handle title -> name mapping
            $title = $postData['MasterPage']['title'] ?? 'Untitled Page';
            $model->name = $title;
            $model->slug = $this->generateSlug($title);
            $model->layout_json = $postData['MasterPage']['layout_json'] ?? '[]';
            $model->layout = 'dynamic';
            $model->is_active = 1;

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Halaman berhasil dibuat!');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Gagal membuat halaman. Errors: ' . json_encode($model->getErrors()));
            }
        }

        return $this->render('dynamic-builder', [
            'model' => $model,
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

            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Halaman berhasil disimpan!');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Gagal menyimpan halaman. Errors: ' . json_encode($model->getErrors()));
            }
        }

        return $this->render('dynamic-builder', [
            'model' => $model,
        ]);
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

        return $this->renderDynamicPage($layoutJson);
    }

    /**
     * Render JSON content as HTML (for preview/render)
     */
    private function renderDynamicPage($layoutJson)
    {
        $this->layout = 'main';

        // Decode & validate to avoid breaking JS when $layoutJson is not valid JSON
        $state = json_decode($layoutJson, true);
        if (!is_array($state)) {
            $state = [];
        }

        $this->registerJs("
            // dynamicPageState is injected as JSON (safe for JS parsing)
            window.dynamicPageState = " . \yii\helpers\Json::htmlEncode($state) . ";

            function renderBlockSafe(block) {
                const props = (block && block.props) ? block.props : {};
                const type = block ? block.type : null;

                // Build DOM nodes instead of huge template strings to prevent JS parse issues
                switch (type) {
                    case \"heading\": {
                        const el = document.createElement(props.level || \"h2\");
                        el.className = \"mb-4\";
                        el.textContent = props.text || \"\";
                        return el;
                    }
                    case \"text\": {
                        const el = document.createElement(\"div\");
                        el.className = \"mb-4 text-gray-700\";
                        el.textContent = props.content || \"\";
                        return el;
                    }
                    case \"image\": {
                        if (!props.src) return document.createTextNode(\"\");
                        const el = document.createElement(\"img\");
                        el.src = props.src;
                        el.alt = props.alt || \"\";
                        el.className = \"mb-4 mx-auto\";
                        return el;
                    }
                    case \"button\": {
                        const wrap = document.createElement(\"div\");
                        wrap.className = \"mb-4 text-center\";

                        const a = document.createElement(\"a\");
                        a.href = props.url || \"#\";
                        const colors = {
                            primary: \"bg-indigo-600 text-white\",
                            secondary: \"bg-gray-600 text-white\",
                            outline: \"border border-indigo-600 text-indigo-600\"
                        };
                        a.className = \"inline-block px-6 py-2 rounded \" + (colors[props.style] || colors.primary);
                        a.textContent = props.text || \"\";
                        wrap.appendChild(a);
                        return wrap;
                    }
                    case \"form\": {
                        const el = document.createElement(\"div\");
                        el.className = \"mb-4 p-4 bg-blue-50 rounded\";
                        el.textContent = \"Form: #\" + (props.formId || \"Belum pilih\");
                        return el;
                    }
                    case \"card\": {
                        const el = document.createElement(\"div\");
                        el.className = \"mb-4 p-4 border rounded shadow-sm\";

                        const h4 = document.createElement(\"h4\");
                        h4.className = \"font-bold\";
                        h4.textContent = props.title || \"\";

                        const p = document.createElement(\"p\");
                        p.textContent = props.content || \"\";

                        el.appendChild(h4);
                        el.appendChild(p);
                        return el;
                    }
                    case \"spacer\": {
                        const el = document.createElement(\"div\");
                        el.style.height = \"32px\";
                        return el;
                    }
                    case \"divider\": {
                        const el = document.createElement(\"hr\");
                        el.className = \"my-4\";
                        return el;
                    }
                    case \"grid\": {
                        const wrap = document.createElement(\"div\");
                        wrap.className = \"grid grid-cols-2 gap-4 mb-4\";

                        const c1 = document.createElement(\"div\");
                        c1.className = \"bg-gray-50 p-4 rounded\";
                        c1.textContent = \"Kolom 1\";

                        const c2 = document.createElement(\"div\");
                        c2.className = \"bg-gray-50 p-4 rounded\";
                        c2.textContent = \"Kolom 2\";

                        wrap.appendChild(c1);
                        wrap.appendChild(c2);
                        return wrap;
                    }
                    default:
                        return document.createTextNode(\"\");
                }
            }

            document.addEventListener(\"DOMContentLoaded\", function() {
                const container = document.getElementById(\"dynamic-content\");
                if (!container || !window.dynamicPageState || !Array.isArray(window.dynamicPageState)) return;

                container.innerHTML = \"\";
                for (const block of window.dynamicPageState) {
                    container.appendChild(renderBlockSafe(block));
                }
            });
        ", \yii\web\View::POS_END);

        return $this->render('@app/views/master-page/_dynamic_render');
    }
}
