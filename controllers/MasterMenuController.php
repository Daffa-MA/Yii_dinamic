<?php

namespace app\controllers;

use Yii;
use app\models\MasterMenu;
use app\models\MasterPage;
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
        Yii::$app->db->schema->refresh();
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
        $dataProvider = new ActiveDataProvider([
            'query' => MasterMenu::find()->with(['parent', 'page'])->orderBy(['order' => SORT_ASC, 'sort_order' => SORT_ASC]),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create new menu
     */
    public function actionCreate()
    {
        $model = new MasterMenu();
        $maxOrder = MasterMenu::find()->max('[[sort_order]]');
        $model->sort_order = ($maxOrder ?? 0) + 1;
        $model->type = 'page'; // Default

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            // Use service for validation and creation
            $result = $this->menuService->createMenu($postData);

            if ($result['success']) {
                Yii::$app->session->setFlash('success', $result['message']);
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
                $model->load($postData);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'pages' => MasterPage::getActivePages(),
            'menuItems' => MasterMenu::find()->all(),
        ]);
    }

    /**
     * Update menu
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();

            $result = $this->menuService->updateMenu($id, $postData);

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
            'pages' => MasterPage::getActivePages(),
            'menuItems' => MasterMenu::find()->where(['!=', 'id', $id])->all(),
        ]);
    }

    /**
     * Delete menu
     */
    public function actionDelete($id)
    {
        $result = $this->menuService->deleteMenu($id);

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
        }

        return $this->redirect(['index']);
    }

    /**
     * Toggle menu status
     */
    public function actionToggle($id)
    {
        $result = $this->menuService->toggleStatus($id);

        if ($result['success']) {
            Yii::$app->session->setFlash('success', $result['message']);
        } else {
            Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
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
