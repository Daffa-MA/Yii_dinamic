<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\services\SidebarService;
use app\services\PageDisplayService;

/**
 * DashboardController - Dynamic project dashboard
 * 
 * /workspace-dashboard/{project_id}
 * - Ambil menu dari database berdasarkan project_id
 * - Render sidebar menu (parent-child)
 * - Handle menu click (group/page/route)
 * - Render page dengan forms
 */
class DashboardController extends Controller
{
    public $layout = 'dashboard';
    private $sidebarService;
    private $pageDisplayService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->sidebarService = new SidebarService();
        $this->pageDisplayService = new PageDisplayService();
    }

    /**
     * Main dashboard page - show sidebar and page content
     * URL: /workspace-dashboard/index or /workspace-dashboard/{project_id}
     */
    public function actionIndex($project_id)
    {
        // Validate project exists
        $project = $this->findProject($project_id);
        
        // Get menu tree for this project
        $menuTree = $this->sidebarService->getMenuTreeByProject($project_id, true);
        
        // Get project info for sidebar
        $projectInfo = [
            'id' => $project['id'],
            'name' => $project['name'],
        ];

        return $this->render('index', [
            'project_id' => $project_id,
            'project' => $projectInfo,
            'menuTree' => $menuTree,
        ]);
    }

    /**
     * Handle menu click - return JSON for AJAX
     * URL: /workspace-dashboard/handle-menu?project_id=X&menu_id=Y
     */
    public function actionHandleMenu($project_id, $menu_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Validate project
        $project = $this->findProject($project_id);
        
        // Validate menu belongs to this project
        $menu = $this->findMenu($menu_id, $project_id);
        
        // Handle menu click based on type
        $result = $this->pageDisplayService->handleMenuClickWithProject($menu_id, $project_id);
        
        return $result;
    }

    /**
     * Get forms for a page (AJAX)
     * URL: /workspace-dashboard/get-forms?project_id=X&page_id=Y
     */
    public function actionGetForms($project_id, $page_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Validate project
        $project = $this->findProject($project_id);
        
        // Get forms for page
        $forms = $this->pageDisplayService->getPageFormsWithProject($page_id, $project_id);
        
        return [
            'success' => true,
            'forms' => $forms,
            'count' => count($forms),
        ];
    }

    /**
     * Render page content
     * URL: /workspace-dashboard/render-page?project_id=X&page_id=Y
     */
    public function actionRenderPage($project_id, $page_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Validate project
        $project = $this->findProject($project_id);
        
        // Get page data
        $pageData = $this->pageDisplayService->getPageWithProject($page_id, $project_id);
        
        if (!$pageData['success']) {
            return $pageData;
        }

        // Render HTML
        $html = $this->pageDisplayService->renderPageHtml($pageData);
        
        return [
            'success' => true,
            'html' => $html,
            'page' => $pageData['page'],
            'render' => $pageData['render'],
            'forms' => $pageData['forms'],
        ];
    }

    /**
     * Find project by ID
     */
    private function findProject($project_id)
    {
        $project = Yii::$app->db->createCommand(
            "SELECT * FROM projects WHERE id = :id"
        )->bindParam(':id', $project_id)->queryOne();

        if (!$project) {
            throw new NotFoundHttpException('Project tidak ditemukan.');
        }

        return $project;
    }

    /**
     * Find menu by ID and project_id
     */
    private function findMenu($menu_id, $project_id)
    {
        $menu = Yii::$app->db->createCommand(
            "SELECT * FROM master_menu WHERE id = :id AND project_id = :project_id AND is_active = 1"
        )->bindParam(':id', $menu_id)->bindParam(':project_id', $project_id)->queryOne();

        if (!$menu) {
            throw new NotFoundHttpException('Menu tidak ditemukan atau tidak aktif.');
        }

        return $menu;
    }
}
