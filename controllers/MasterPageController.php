<?php

namespace app\controllers;

use Yii;
use app\models\Form;
use app\models\Project;
use app\models\MasterForm;
use app\models\MasterMenu;
use app\models\MasterPage;
use app\models\MasterDatatable;
use app\models\MasterPageChart;
use app\models\DbTable;
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
        if (in_array($action->id, ['preview-layout', 'dynamic-create', 'dynamic-update', 'ajax-save', 'dynamic-save'])) {
            $this->enableCsrfValidation = false;
        }

        // Handle CORS for actions called from srcdoc/blob iframes (origin: null)
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
            Yii::$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            Yii::$app->response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept');
            Yii::$app->response->setStatusCode(200);
            Yii::$app->end();
        }
        // Add CORS header to actual responses for iframe-source requests
        if (in_array($action->id, ['form-preview', 'preview-layout', 'card-preview'])) {
            Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $dbContext = new ActiveDatabaseContext();
        $result = $dbContext->resolveAndApply();

        if (in_array($action->id, ['dynamic-create', 'dynamic-update', 'view-dynamic', 'ajax-save', 'dynamic-save'], true)) {
            $this->ensureMasterPageAdvancedColumnsExist();
        }

        if (!empty($result['isSwitched'])) {
            Yii::$app->db->schema->refresh();
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
            'custom_html' => $db->schema->createColumnSchemaBuilder('longtext'),
            'custom_css' => $db->schema->createColumnSchemaBuilder('longtext'),
            'custom_js' => $db->schema->createColumnSchemaBuilder('longtext'),
            'page_custom_html' => $db->schema->createColumnSchemaBuilder('longtext'),
            'page_custom_css' => $db->schema->createColumnSchemaBuilder('longtext'),
            'page_custom_js' => $db->schema->createColumnSchemaBuilder('longtext'),
            'use_page_custom_code' => $db->schema->createColumnSchemaBuilder('tinyint', 1)->defaultValue(0),
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
     * Check if a page name already exists (for duplicate validation)
     */
    private function checkDuplicatePageName(string $name, $excludeId = null): bool
    {
        $query = MasterPage::find()
            ->where(['name' => $name]);
        
        if ($excludeId !== null) {
            $query->andWhere(['!=', 'id', (int)$excludeId]);
        }
        
        return $query->exists();
    }

    /**
     * Check if a slug already exists (for duplicate validation before save)
     */
    private function checkDuplicateSlug(string $slug, $excludeId = null): bool
    {
        $query = MasterPage::find()
            ->where(['slug' => $slug]);
        
        if ($excludeId !== null) {
            $query->andWhere(['!=', 'id', (int)$excludeId]);
        }
        
        return $query->exists();
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

    private function processInlineChartConfigs(int $pageId, ?string &$layoutJson): void
    {
        if (empty($layoutJson)) return;
        $blocks = json_decode($layoutJson, true);
        if (!is_array($blocks)) return;

        $changed = false;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($blocks as &$block) {
                if (!isset($block['type'], $block['props']['_chartConfig'])
                    || $block['type'] !== 'chart') {
                    continue;
                }

                $cfg = $block['props']['_chartConfig'];
                $chartId = isset($block['props']['chartId']) && $block['props']['chartId'] !== ''
                    ? (int)$block['props']['chartId']
                    : null;

                if ($chartId) {
                    $model = MasterPageChart::findOne($chartId);
                    if (!$model) continue;
                } else {
                    $model = new MasterPageChart();
                    $model->page_id = $pageId;
                }

                $model->title = $cfg['title'] ?? 'Untitled Chart';
                $model->chart_type = $cfg['chartType'] ?? 'bar';
                $model->source_type = $cfg['sourceType'] ?? 'table';
                $model->source_query = $cfg['sourceQuery'] ?? '';
                $model->table_id = !empty($cfg['tableId']) ? (int)$cfg['tableId'] : null;
                $model->label_field = $cfg['labelField'] ?? '';
                $model->value_field = $cfg['valueField'] ?? '';
                $model->aggregation = $cfg['agg'] ?? 'count';
                $model->group_by_field = $cfg['groupField'] ?? '';
                $model->is_active = 1;

                if (!$model->save()) {
                    $transaction->rollBack();
                    return;
                }

                if (!$chartId) {
                    $block['props']['chartId'] = (string)$model->id;
                }
                unset($block['props']['_chartConfig']);
                if (isset($block['props']['_chartPreview'])) {
                    unset($block['props']['_chartPreview']);
                }
                $changed = true;
            }

            if ($changed) {
                $layoutJson = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error('processInlineChartConfigs: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function updateOrphanChartPageIds(int $pageId, ?string $layoutJson): void
    {
        if (empty($layoutJson)) return;
        $blocks = json_decode($layoutJson, true);
        if (!is_array($blocks)) return;

        $chartIds = [];
        foreach ($blocks as $block) {
            if (isset($block['type'], $block['props']['chartId']) && $block['type'] === 'chart' && !empty($block['props']['chartId'])) {
                $chartIds[] = (int)$block['props']['chartId'];
            }
        }
        if (empty($chartIds)) return;

        try {
            $db = \app\models\MasterPageChart::getDb();
            $db->createCommand()->update(
                'master_page_chart',
                ['page_id' => $pageId],
                ['and', ['id' => $chartIds], ['page_id' => null]]
            )->execute();
        } catch (\Throwable $e) {
            Yii::error('updateOrphanChartPageIds: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function applyPageCustomCodePost(MasterPage $model, array $postData): void
    {
        $data = $postData['MasterPage'] ?? [];
        $useCustomCode = array_key_exists('use_page_custom_code', $data)
            ? (int)$data['use_page_custom_code'] === 1
            : (($data['page_type'] ?? MasterPage::PAGE_TYPE_BUILDER) === MasterPage::PAGE_TYPE_CUSTOM_CODE);
        $html = (string)($data['page_custom_html'] ?? $data['custom_html'] ?? '');

        // Strip the heavy window.dynamicDatatableHtml JavaScript variable from the
        // Page Source before saving. Uses brace-counting that properly skips
        // quoted strings (which may contain semicolons inside inline CSS values
        // like "padding:16px;color:red") — a simple [^;]+ regex would break on
        // the first semicolon inside any JSON string value.
        $html = $this->stripJsObjectAssignment($html, 'window.dynamicDatatableHtml');

        $css = (string)($data['page_custom_css'] ?? $data['custom_css'] ?? '');
        $js = (string)($data['page_custom_js'] ?? $data['custom_js'] ?? '');

        $model->use_page_custom_code = $useCustomCode ? 1 : 0;
        $model->page_type = $useCustomCode ? MasterPage::PAGE_TYPE_CUSTOM_CODE : MasterPage::PAGE_TYPE_BUILDER;
        $model->page_custom_html = $useCustomCode ? $html : '';
        $model->page_custom_css = $useCustomCode ? $css : '';
        $model->page_custom_js = $useCustomCode ? $js : '';
        $model->custom_html = $model->page_custom_html;
        $model->custom_css = $model->page_custom_css;
        $model->custom_js = $model->page_custom_js;
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
        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'page',
            'hero_label' => 'Dynamic Page',
            'page_title' => (string)($page->title ?? $page->name ?? 'Page'),
            'page_description' => (string)($page->description ?? ''),
            'layout' => (string)($page->layout_type ?? $page->layout ?? 'dynamic'),
            'form_count' => count($forms),
            'status' => $page->isActive() ? 'Active' : 'Nonaktif',
            'workspace_name' => $activeProject instanceof Project ? (string)$activeProject->name : 'Workspace',
        ];

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
        
        if ($pageId) {
            $page = MasterPage::findOne($pageId);
            if (!$page) {
                return ['success' => false, 'message' => 'Page not found'];
            }
        } else {
            $page = new MasterPage();
            $page->name = Yii::$app->request->post('title', 'Untitled Page');
            $slug = $this->generateSlug($page->name);
            // Ensure unique slug
            $counter = 0;
            $baseSlug = $slug;
            while (MasterPage::find()->where(['slug' => $slug])->exists()) {
                $counter++;
                $slug = $baseSlug . '-' . $counter;
            }
            $page->slug = $slug;
            $page->layout = 'dynamic';
            $page->is_active = 1;
        }

        $page->layout_json = Yii::$app->request->post('layout_json', '[]');

        if ($page->isNewRecord) {
            if (!$page->save(false)) {
                return ['success' => false, 'message' => 'Failed to create page: ' . json_encode($page->getErrors())];
            }
            $this->updateOrphanChartPageIds($page->id, $page->layout_json);
        } else {
            $this->applyPageCustomCodePost($page, [
                'MasterPage' => [
                    'custom_html' => Yii::$app->request->post('custom_html'),
                    'custom_css' => Yii::$app->request->post('custom_css'),
                    'custom_js' => Yii::$app->request->post('custom_js'),
                    'page_type' => Yii::$app->request->post('page_type', 'builder'),
                    'use_page_custom_code' => Yii::$app->request->post('use_page_custom_code', 0),
                ],
            ]);

            if (!$page->save(false)) {
                return ['success' => false, 'message' => 'Failed to save page'];
            }
            $this->updateOrphanChartPageIds($page->id, $page->layout_json);
        }

        return [
            'success' => true,
            'page_id' => (int)$page->id,
            'message' => 'Page saved successfully',
        ];
    }

    /**
     * Preview page layout (AJAX)
     */
    public function actionPreviewLayout()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $layoutJson = Yii::$app->request->post('layout_json', '{}');
        $pageId = (int)Yii::$app->request->post('page_id', Yii::$app->request->get('page_id', 0));
        $menuId = (int)Yii::$app->request->post('menu_id', Yii::$app->request->get('menu_id', 0));

        try {
            $html = $this->renderPartial('//page/_preview-layout', [
                'layoutJson' => $layoutJson,
                'pageId' => $pageId,
                'menuId' => $menuId,
            ]);
            return ['success' => true, 'html' => $html];
        } catch (\Exception $e) {
            Yii::error('Preview layout error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Strip a JavaScript object/array variable assignment from HTML by
     * counting braces/brackets and properly skipping quoted strings.
     * Handles both {...} objects and [...] arrays, including nested values.
     */
    private function stripJsObjectAssignment(string $html, string $varName): string
    {
        $pos = strpos($html, $varName);
        if ($pos === false) {
            return $html;
        }
        $start = '{';
        $end = '}';
        $bracePos = strpos($html, '{', $pos);
        $bracketPos = strpos($html, '[', $pos);
        if ($bracePos === false && $bracketPos === false) {
            return $html;
        }
        if ($bracePos !== false && ($bracketPos === false || $bracePos < $bracketPos)) {
            $openPos = $bracePos;
        } else {
            $start = '[';
            $end = ']';
            $openPos = $bracketPos;
        }
        $depth = 0;
        $inString = false;
        $len = strlen($html);
        $endPos = $openPos;
        for ($i = $openPos; $i < $len; $i++) {
            $ch = $html[$i];
            if ($inString) {
                if ($ch === '\\') { $i++; continue; }
                if ($ch === '"') { $inString = false; }
                continue;
            }
            if ($ch === '"') { $inString = true; continue; }
            if ($ch === $start) { $depth++; continue; }
            if ($ch === $end) {
                $depth--;
                if ($depth === 0) { $endPos = $i; break; }
            }
        }
        $semiPos = strpos($html, ';', $endPos);
        if ($semiPos === false) {
            return $html;
        }
        return substr_replace($html, '', $pos, $semiPos - $pos + 1);
    }

    /**
     * Find available forms
     */
    private function findAvailableForms()
    {
        $formSelect = ['id', 'form_name'];
        $formSchema = MasterForm::getTableSchema();
        foreach (['slug', 'name'] as $column) {
            if ($formSchema !== null && isset($formSchema->columns[$column])) {
                $formSelect[] = $column;
            }
        }

        // Use MasterForm scoped query so forms are resolved from active database + active project.
        $rows = MasterForm::findScoped()
            ->select($formSelect)
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

    private function findAvailableDatatables(): array
    {
        try {
            MasterDatatable::ensureStructure();
            $rows = MasterDatatable::findScoped()
                ->select(['id', 'name', 'table_id', 'columns_config', 'actions_config', 'filters_config', 'stats_config', 'workflow_config', 'exports_config', 'ownership_config', 'search_enabled', 'pagination_enabled'])
                ->andWhere(['is_active' => 1])
                ->all();

            $items = [];
            foreach ($rows as $row) {
                $actions = $row->getActionsConfigArray();
                $items[] = [
                    'id' => (int)$row->id,
                    'name' => (string)$row->name,
                    'tableId' => (int)$row->table_id,
                    'columns' => $row->getColumnsConfigArray(),
                    'actions' => array_merge($actions, [
                        'editMode' => $actions['edit_mode'] ?? 'custom',
                        'editFormId' => $actions['edit_form_id'] ?? '',
                    ]),
                    'filters' => $row->getFiltersConfigArray(),
                    'stats' => $row->getStatsConfigArray(),
                    'workflow' => $row->getWorkflowConfigArray(),
                    'exports' => $row->getExportsConfigArray(),
                    'ownership' => $row->getOwnershipConfigArray(),
                    'search' => (bool)$row->search_enabled,
                    'pagination' => (bool)$row->pagination_enabled,
                ];
            }

            return $items;
        } catch (\yii\db\Exception $e) {
            return [];
        }
    }

    private function findAvailableCharts(): array
    {
        try {
            $rows = MasterPageChart::find()
                ->select(['id', 'page_id', 'title', 'chart_type', 'table_id', 'source_type', 'source_query',
                    'label_field', 'value_field', 'aggregation', 'group_by_field'])
                ->andWhere(['is_active' => 1])
                ->orderBy(['title' => SORT_ASC])
                ->all();

            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'id' => (int)$row->id,
                    'page_id' => $row->page_id ? (int)$row->page_id : null,
                    'title' => (string)$row->title,
                    'chart_type' => (string)$row->chart_type,
                    'table_id' => (int)$row->table_id,
                    'source_type' => (string)$row->source_type,
                    'source_query' => (string)$row->source_query,
                    'label_field' => (string)$row->label_field,
                    'value_field' => (string)$row->value_field,
                    'aggregation' => (string)$row->aggregation,
                    'group_by_field' => (string)$row->group_by_field,
                ];
            }

            return $items;
        } catch (\yii\db\Exception $e) {
            return [];
        }
    }

    private function findAvailableTablesForBuilder(): array
    {
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        $effectiveUserId = (new \app\components\CommanderAuthContext())->isSuperAdmin() ? null : (int)(Yii::$app->user->id ?? 0);
        if ($effectiveUserId === 0) $effectiveUserId = null;
        
        $userTables = \app\services\TableService::getUserTables($effectiveUserId, $activeProjectId);

        $items = [];
        $relatedSchemaColumnsCache = [];
        foreach ($userTables as $table) {
            $columns = [];
            foreach ($table->columns as $column) {
                $isForeignKey = $column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key');
                $referencedTable = $isForeignKey && $column->hasAttribute('referenced_table_name')
                    ? trim((string)$column->getAttribute('referenced_table_name'))
                    : '';
                $referencedColumn = $isForeignKey && $column->hasAttribute('referenced_column_name')
                    ? trim((string)$column->getAttribute('referenced_column_name'))
                    : '';
                $relatedColumns = [];
                if ($isForeignKey && $referencedTable !== '') {
                    try {
                        if (!array_key_exists($referencedTable, $relatedSchemaColumnsCache)) {
                            $schema = Yii::$app->db->schema->getTableSchema($referencedTable, true);
                            $relatedSchemaColumnsCache[$referencedTable] = $schema !== null ? $schema->columns : [];
                        }
                        if (!empty($relatedSchemaColumnsCache[$referencedTable])) {
                            foreach ($relatedSchemaColumnsCache[$referencedTable] as $schemaColumnName => $schemaColumn) {
                                $relatedColumns[] = [
                                    'field' => (string)$schemaColumnName,
                                    'label' => (string)$schemaColumnName,
                                    'type' => (string)($schemaColumn->type ?? ''),
                                ];
                            }
                        }
                    } catch (\Throwable $e) {
                        Yii::warning('Failed to load related columns for datatable builder FK: ' . $e->getMessage(), 'master-page-builder');
                    }
                }

                $columns[] = [
                    'field' => (string)$column->name,
                    'label' => (string)($column->label ?: $column->name),
                    'type' => (string)$column->type,
                    'primary' => (bool)$column->is_primary,
                    'isForeignKey' => $isForeignKey,
                    'is_foreign_key' => $isForeignKey,
                    'referencedTable' => $referencedTable,
                    'referenced_table' => $referencedTable,
                    'referencedColumn' => $referencedColumn,
                    'referenced_column' => $referencedColumn,
                    'relatedColumns' => $relatedColumns,
                ];
            }
            $items[] = [
                'id' => (int)$table->id,
                'name' => (string)$table->name,
                'label' => (string)($table->label ?: $table->name),
                'columns' => $columns,
            ];
        }

        return $items;
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
            $html = $previewService->renderByScopedId((int)$id, (bool)$showTitle, (bool)$interactive, [
                'render_context' => (string)Yii::$app->request->get('render_context', ''),
                'page_id' => (int)Yii::$app->request->get('page_id', 0),
                'menu_id' => (int)Yii::$app->request->get('menu_id', 0),
                'component_id' => (string)Yii::$app->request->get('component_id', ''),
            ]);
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
     * Dynamic Page Builder - Create (GET only, no POST)
     */
    public function actionDynamicCreate()
    {
        $model = new MasterPage();

        return $this->render('dynamic-builder', [
            'model' => $model,
            'initialState' => !empty($model->layout_json) ? json_decode($model->layout_json, true) : [],
            'forms' => $this->findAvailableForms(),
            'datatables' => $this->findAvailableDatatables(),
            'tables' => $this->findAvailableTablesForBuilder(),
            'availableCharts' => $this->findAvailableCharts(),
            'permissionContext' => $this->buildBuilderPermissionContext($model),
        ]);
    }

    /**
     * Dynamic Page Builder - Update (GET only, no POST)
     */
    public function actionDynamicUpdate($id)
    {
        $model = $this->findModel($id);

        return $this->render('dynamic-builder', [
            'model' => $model,
            'initialState' => !empty($model->layout_json) ? json_decode($model->layout_json, true) : [],
            'forms' => $this->findAvailableForms(),
            'datatables' => $this->findAvailableDatatables(),
            'tables' => $this->findAvailableTablesForBuilder(),
            'availableCharts' => $this->findAvailableCharts(),
            'permissionContext' => $this->buildBuilderPermissionContext($model),
        ]);
    }

    /**
     * AJAX Save for Dynamic Builder — validates duplicate, saves, returns JSON
     * Does NOT reload page on error; returns error message in JSON response.
     */
    public function actionDynamicSave()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $pageId = Yii::$app->request->post('page_id');
        $title = trim(Yii::$app->request->post('title', ''));
        $layoutJson = Yii::$app->request->post('layout_json', '[]');
        $pageType = Yii::$app->request->post('page_type', 'builder');
        $useCustomCode = Yii::$app->request->post('use_page_custom_code', 0);
        $customHtml = Yii::$app->request->post('custom_html', '');

        if (empty($title)) {
            return ['success' => false, 'error' => 'Judul halaman tidak boleh kosong.'];
        }

        if ($pageId) {
            $page = MasterPage::findOne($pageId);
            if (!$page) {
                return ['success' => false, 'error' => 'Halaman tidak ditemukan.'];
            }
        } else {
            $page = new MasterPage();
            $page->is_active = 1;
            $page->layout = 'dynamic';
        }

        // Validate duplicate name
        if ($this->checkDuplicatePageName($title, $pageId)) {
            return [
                'success' => false,
                'error' => 'Nama page yang Anda masukkan sudah digunakan oleh halaman lain. Silakan gunakan nama page yang berbeda.',
            ];
        }

        $page->name = $title;

        if ($pageId) {
            // Keep existing slug for updates unless title changed significantly
        } else {
            $slug = $this->generateSlug($title);
            $counter = 0;
            $baseSlug = $slug;
            while (MasterPage::find()->where(['slug' => $slug])->exists()) {
                $counter++;
                $slug = $baseSlug . '-' . $counter;
            }
            $page->slug = $slug;
        }

        // Validate duplicate slug
        if ($this->checkDuplicateSlug($page->slug, $pageId)) {
            return [
                'success' => false,
                'error' => 'Nama page tersebut menghasilkan slug yang sudah digunakan oleh halaman lain. Silakan gunakan nama page yang berbeda.',
            ];
        }

        $page->layout_json = $layoutJson;

        $useCustomCode = (int)$useCustomCode === 1;
        $page->use_page_custom_code = $useCustomCode ? 1 : 0;
        $page->page_type = $useCustomCode ? MasterPage::PAGE_TYPE_CUSTOM_CODE : MasterPage::PAGE_TYPE_BUILDER;
        $page->page_custom_html = $useCustomCode ? $customHtml : '';
        $page->page_custom_css = '';
        $page->page_custom_js = '';
        $page->custom_html = $page->page_custom_html;
        $page->custom_css = '';
        $page->custom_js = '';

        if (!$page->save(false)) {
            return ['success' => false, 'error' => 'Gagal menyimpan halaman: ' . json_encode($page->getErrors())];
        }

        $savedLayout = $page->layout_json;
        $this->processInlineChartConfigs($page->id, $savedLayout);
        if ($savedLayout !== $page->layout_json) {
            $page->layout_json = $savedLayout;
            $page->save(false);
        }
        $this->updateOrphanChartPageIds($page->id, $page->layout_json);

        return [
            'success' => true,
            'page_id' => (int)$page->id,
            'message' => $pageId ? 'Halaman berhasil diperbarui!' : 'Halaman berhasil dibuat!',
        ];
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
        if (method_exists($page, 'loadAssignedFormIds')) {
            $page->loadAssignedFormIds();
        }
        $menu = MasterMenu::find()
            ->where(['page_id' => (int)$page->id, 'is_active' => MasterMenu::STATUS_ACTIVE])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->one();

        $this->layout = 'main';
        if (method_exists($page, 'loadAssignedFormIds')) {
            $page->loadAssignedFormIds();
        }
        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'page',
            'hero_label' => 'Dynamic Page',
            'page_title' => (string)($page->title ?? $page->name ?? 'Page'),
            'page_description' => (string)($page->description ?? ''),
            'layout' => (string)($page->layout_type ?? $page->layout ?? 'dynamic'),
            'form_count' => is_array($page->assignedForms ?? null) ? count($page->assignedForms) : 0,
            'status' => $page->isActive() ? 'Active' : 'Nonaktif',
            'workspace_name' => $activeProject instanceof Project ? (string)$activeProject->name : 'Workspace',
        ];
        
        return $this->render('@app/views/master-page/_dynamic_render', [
            'layoutJson' => $layoutJson,
            'customHtml' => $page->page_custom_html ?? $page->custom_html ?? null,
            'customCss' => $page->page_custom_css ?? $page->custom_css ?? null,
            'customJs' => $page->page_custom_js ?? $page->custom_js ?? null,
            'pageType' => !empty($page->use_page_custom_code) ? MasterPage::PAGE_TYPE_CUSTOM_CODE : ($page->page_type ?? 'builder'),
            'pageKey' => $page->slug ?? (string)$page->id,
            'pageId' => (int)$page->id,
            'menuId' => $menu !== null ? (int)$menu->id : 0,
        ]);
    }

    public function actionPreviewLive($id)
    {
        $page = $this->findModel((int)$id);
        $layoutJson = !empty($page->layout_json) ? $page->layout_json : '[]';

        $this->layout = 'main';
        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'page',
            'hero_label' => 'Dynamic Page',
            'page_title' => (string)($page->title ?? $page->name ?? 'Page'),
            'page_description' => (string)($page->description ?? ''),
            'layout' => (string)($page->layout_type ?? $page->layout ?? 'dynamic'),
            'form_count' => is_array($page->assignedForms ?? null) ? count($page->assignedForms) : 0,
            'status' => $page->isActive() ? 'Active' : 'Nonaktif',
            'workspace_name' => $activeProject instanceof Project ? (string)$activeProject->name : 'Workspace',
        ];

        return $this->render('@app/views/master-page/_dynamic_render', [
            'layoutJson' => $layoutJson,
            'customHtml' => $page->page_custom_html ?? $page->custom_html ?? null,
            'customCss' => $page->page_custom_css ?? $page->custom_css ?? null,
            'customJs' => $page->page_custom_js ?? $page->custom_js ?? null,
            'pageType' => !empty($page->use_page_custom_code) ? MasterPage::PAGE_TYPE_CUSTOM_CODE : ($page->page_type ?? 'builder'),
            'pageKey' => $page->slug ?? (string)$page->id,
            'pageId' => (int)$page->id,
            'menuId' => 0,
        ]);
    }
}
