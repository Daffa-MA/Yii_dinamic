<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
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
                    'upload-logo' => ['POST'],
                    'remove-logo' => ['POST'],
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
    
    public function actionUploadLogo()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->loadSettings();
        
        $uploadedFile = UploadedFile::getInstanceByName('workspace_logo_image');
        
        if (!$uploadedFile) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower($uploadedFile->getExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WEBP'];
        }
        
        $maxSize = 2 * 1024 * 1024;
        if ($uploadedFile->size > $maxSize) {
            return ['success' => false, 'message' => 'File too large. Maximum size: 2MB'];
        }
        
        $uploadDir = Yii::getAlias('@webroot/uploads/workspace/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        if ($uploadedFile->saveAs($filePath)) {
            $oldLogo = $model->workspace_logo_image;
            if ($oldLogo && file_exists($uploadDir . $oldLogo)) {
                @unlink($uploadDir . $oldLogo);
            }
            
            $model->workspace_logo_image = $fileName;
            $model->save();
            
            return [
                'success' => true, 
                'message' => 'Logo uploaded successfully',
                'logoUrl' => Yii::getAlias('@web/uploads/workspace/') . $fileName,
                'logoFile' => $fileName
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    public function actionRemoveLogo()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->loadSettings();
        
        if ($model->workspace_logo_image) {
            $uploadDir = Yii::getAlias('@webroot/uploads/workspace/');
            $filePath = $uploadDir . $model->workspace_logo_image;
            
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            $model->workspace_logo_image = null;
            $model->save();
            
            return ['success' => true, 'message' => 'Logo removed successfully'];
        }
        
        return ['success' => false, 'message' => 'No logo to remove'];
    }
    
    private function loadSettings()
    {
        $model = new \app\models\WorkspaceSettings();
        $model->loadFromSession();
        return $model;
    }
}