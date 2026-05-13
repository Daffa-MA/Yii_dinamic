<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class WorkspaceSettingsController extends Controller
{
    public $layout = 'dashboard';
    
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save' => ['POST'],
                ],
            ],
        ];
    }
    
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        
        $dbContext = new \app\components\ActiveDatabaseContext();
        $dbContext->resolveAndApply();
        Yii::$app->db->schema->refresh();
        
        return true;
    }
    
    public function actionIndex()
    {
        $model = $this->loadSettings();
        
        return $this->render('index', [
            'model' => $model,
        ]);
    }
    
    public function actionSave()
    {
        $model = $this->loadSettings();
        
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                $model->save();
                return ['success' => true, 'message' => 'Pengaturan berhasil disimpan!'];
            }
            return ['success' => false, 'errors' => $model->errors];
        }
        
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Pengaturan berhasil disimpan!');
                return $this->redirect(['index']);
            }
        }
        
        return $this->render('index', [
            'model' => $model,
        ]);
    }
    
    public function actionReset()
    {
        $model = $this->loadSettings();
        $model->reset();
        return $this->redirect(['index']);
    }
    
    private function loadSettings()
    {
        $model = new \app\models\WorkspaceSettings();
        $model->loadFromSession();
        return $model;
    }
}