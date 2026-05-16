<?php

namespace app\controllers;

use Yii;
use app\models\Form;
use app\models\MasterForm;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\services\PageService;
use app\services\DynamicFormPreviewService;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\ProjectPermissionService;
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
        $pageId = Yii::$app->request->post('pageId');
        $title = Yii::$app->request->post('title');
        $slug = Yii::$app->request->post('slug');
        $content = Yii::$app->request->post('content');

        if (!$pageId) {
            return $this->redirect(['visual-update', 'id' => $pageId ?? 0, 'saveError' => 1]);
        }

        $page = MasterPage::findOne($pageId);
        if (!$page) {
            return $this->redirect(['visual-update', 'id' => $pageId, 'saveError' => 1]);
        }

        $page->title = $title ?: $page->title;
        $page->slug = $slug ?: $page->slug;
        $page->layout_json = $content;

        if ($page->save(false)) {
            Yii::$app->session->setFlash('success', 'Halaman berhasil disimpan!');
            return $this->redirect(['index']);
        }

        return $this->redirect(['visual-update', 'id' => $pageId, 'saveError' => 1]);
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

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $result;
        }

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

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return $result;
        }

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
        $previewUrl = Yii::$app->urlManager->createUrl(['master-page/preview-live', 'id' => $page->id]);
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
        // Use MasterForm scoped query so forms are resolved from active database + active project.
        $rows = MasterForm::findScoped()
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $permissionService = new ProjectPermissionService();
        $forms = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = trim((string) ($row->form_name ?? ''));
            if ($name === '' && isset($row->name)) {
                $name = trim((string) $row->name);
            }
            if ($name === '') {
                $name = 'Form #' . $id;
            }

            if (!$permissionService->canAccessForm($row)) {
                continue;
            }

            $forms[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $forms;
    }

    private function buildBuilderPermissionContext(?MasterPage $model = null): array
    {
        $permissionService = new ProjectPermissionService();
        $pageKey = $model !== null ? ($model->slug ?: $model->name ?: (string)$model->id) : 'page';
        $pageKey = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string)$pageKey), '-'));
        $pageKey = $pageKey !== '' ? $pageKey : 'page';

        $builderKeys = [
            'builder.global.access',
            'builder.palette.access',
            'builder.tools.access',
            'builder.drag.access',
            'builder.actions.access',
            'builder.forms.access',
            'builder.page.' . $pageKey . '.access',
        ];

        return [
            'pageKey' => $pageKey,
            'canAccessBuilder' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'builder.page.' . $pageKey . '.access']),
            'canAccessPalette' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'builder.palette.access', 'builder.page.' . $pageKey . '.access']),
            'canAccessTools' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'builder.tools.access', 'builder.page.' . $pageKey . '.access']),
            'canDragComponents' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'builder.drag.access', 'builder.page.' . $pageKey . '.access']),
            'canAccessActions' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'builder.actions.access', 'builder.page.' . $pageKey . '.access']),
            'canAccessForms' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'builder.forms.access', 'builder.page.' . $pageKey . '.access']),
            'canCreatePage' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'action.page.create']),
            'canEditPage' => $permissionService->canAccessPermissionKeys(['builder.global.access', 'action.page.edit', 'builder.page.' . $pageKey . '.access']),
            'canManageComponents' => $permissionService->canAccessPermissionKeys($builderKeys),
        ];
    }

    public function actionFormPreview($id, $showTitle = 1, $interactive = 0)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $previewService = new DynamicFormPreviewService();
            $html = $previewService->renderByScopedId((int)$id, (bool)$showTitle, (bool)$interactive);
            return ['success' => true, 'html' => $html];
        } catch (\Throwable $e) {
            Yii::error('Form preview failed: ' . $e->getMessage(), 'master-page-form-preview');
            return ['success' => false, 'message' => 'Gagal memuat preview form.'];
        }
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
            'permissionContext' => $this->buildBuilderPermissionContext($model),
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
                    Yii::$app->session->setFlash('success', 'Halaman berhasil diperbarui!');
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('error', 'Gagal memperbarui halaman. Errors: ' . json_encode($model->getErrors()));
                    // Redirect with error flag to reopen save dialog
                    return $this->redirect(['dynamic-update', 'id' => $id, 'saveError' => 1]);
                }
        }

        return $this->render('dynamic-builder', [
            'model' => $model,
            'initialState' => !empty($model->layout_json) ? json_decode($model->layout_json, true) : [],
            'forms' => $this->findAvailableForms(),
            'permissionContext' => $this->buildBuilderPermissionContext($model),
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
        $page = MasterPage::findOne(['slug' => $slug]);

        if (!$page) {
            throw new NotFoundHttpException('Halaman tidak ditemukan.');
        }

        if ((int)$page->is_active !== 1) {
            $this->layout = 'main';
            $noticeHtml = '
                <div style="max-width:760px;margin:64px auto;padding:28px;border:1px solid #e2e8f0;border-radius:14px;background:#ffffff;box-shadow:0 10px 30px rgba(15,23,42,.06);">
                    <div style="font-size:20px;font-weight:700;color:#0f172a;margin-bottom:8px;">Halaman belum dipublikasikan</div>
                    <div style="font-size:14px;color:#475569;line-height:1.6;">
                        Halaman ini sedang berstatus nonaktif (unpublish), sehingga tidak ditampilkan sebagai halaman publik.
                    </div>
                </div>';

            return $this->render('@app/views/master-page/_dynamic_render', [
                'layoutJson' => '[]',
                'customHtml' => $noticeHtml,
                'customCss' => null,
                'customJs' => null,
                'pageType' => 'custom_code',
            ]);
        }

        $layoutJson = !empty($page->layout_json) ? $page->layout_json : '[]';

        $this->layout = 'main';
        
        return $this->render('@app/views/master-page/_dynamic_render', [
            'layoutJson' => $layoutJson,
            'customHtml' => $page->custom_html ?? null,
            'customCss' => $page->custom_css ?? null,
            'customJs' => $page->custom_js ?? null,
            'pageType' => $page->page_type ?? 'builder',
            'pageKey' => $page->slug ?? (string)$page->id,
        ]);
    }

    public function actionPreviewLive($id)
    {
        $page = $this->findModel((int)$id);
        $layoutJson = !empty($page->layout_json) ? $page->layout_json : '[]';

        $this->layout = 'main';

        return $this->render('@app/views/master-page/_dynamic_render', [
            'layoutJson' => $layoutJson,
            'customHtml' => $page->custom_html ?? null,
            'customCss' => $page->custom_css ?? null,
            'customJs' => $page->custom_js ?? null,
            'pageType' => $page->page_type ?? 'builder',
            'pageKey' => $page->slug ?? (string)$page->id,
        ]);
    }
}
