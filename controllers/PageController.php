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

        return $this->render('view', [
            'page' => $page,
            'forms' => $page->assignedForms,
        ]);
    }
}
