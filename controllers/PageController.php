<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\MasterMenu;
use app\models\MasterPage;
use yii\web\NotFoundHttpException;

class PageController extends Controller
{
    public $layout = 'dashboard';
    
    private $pageService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->pageService = new \app\services\PageService();
    }

    public function beforeAction($action)
    {
        // Apply database context
        $dbContext = new \app\components\ActiveDatabaseContext();
        $dbContext->resolveAndApply();
        
        // Ensure tables exist
        $this->ensureTablesExist();
        
        Yii::$app->db->schema->refresh();
        return parent::beforeAction($action);
    }
    
    private function ensureTablesExist()
    {
        $db = Yii::$app->db;
        $schema = $db->getTableSchema('master_page_form', true);
        
        if ($schema === null) {
            // Create master_page_form table
            $db->createCommand()->createTable('master_page_form', [
                'id' => $db->schema->createColumnSchemaBuilder('pk'),
                'page_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
                'form_id' => $db->schema->createColumnSchemaBuilder('integer')->notNull(),
                'sort_order' => $db->schema->createColumnSchemaBuilder('integer')->defaultValue(0),
                'created_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $db->schema->createColumnSchemaBuilder('timestamp')->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ])->execute();
            
            $db->createCommand()->createIndex('idx-mpf-page_id', 'master_page_form', 'page_id')->execute();
            $db->createCommand()->createIndex('idx-mpf-form_id', 'master_page_form', 'form_id')->execute();
        }
        
        $db->schema->refresh();
    }

    /**
     * Render dynamic page by ID
     */
    public function actionView($id)
    {
        $page = MasterPage::findOne($id);

        if ($page === null) {
            throw new NotFoundHttpException('Halaman tidak ditemukan.');
        }

        $menu = MasterMenu::find()
            ->where(['page_id' => (int) $id, 'is_active' => MasterMenu::STATUS_ACTIVE])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->one();
        if ($menu !== null) {
            Yii::$app->session->set('active_menu', $menu->id);
        }

        $page->loadAssignedFormIds();
        $activeProject = (new ActiveProjectContext())->getActiveProject();
        $this->view->params['workspacePageHero'] = [
            'scope' => 'page',
            'hero_label' => 'Dynamic Page',
            'page_title' => (string)$page->title,
            'page_description' => (string)($page->description ?? ''),
            'layout' => (string)($page->layout_type ?? ''),
            'form_count' => count($page->assignedForms),
            'status' => $page->isActive() ? 'Active' : 'Nonaktif',
            'workspace_name' => $activeProject !== null ? (string)$activeProject->name : 'Workspace',
        ];

        return $this->render('view', [
            'page' => $page,
            'forms' => $page->assignedForms,
        ]);
    }
}
