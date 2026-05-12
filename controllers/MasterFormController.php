<?php

namespace app\controllers;

use Yii;
use app\models\MasterForm;
use app\models\MasterPage;
use app\models\DbTable;
use app\components\ActiveDatabaseContext;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class MasterFormController extends Controller
{
    public $layout = 'dashboard';

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => MasterForm::find()->with('page'),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new MasterForm();

        if ($model->load(Yii::$app->request->post())) {
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            
            if (!empty($model->table_id)) {
                $model->table_id = (int)$model->table_id;
            }
            
            if (empty($model->slug) && !empty($model->form_name)) {
                $model->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $model->form_name)));
            }
            
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'pages' => MasterPage::find()->all(),
        ]);
    }
    
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            
            if (!empty($model->table_id)) {
                $model->table_id = (int)$model->table_id;
            }
            
            if (empty($model->slug) && !empty($model->form_name)) {
                $model->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $model->form_name)));
            }
            
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'pages' => MasterPage::find()->all(),
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }
    
    public function actionDuplicate($id)
    {
        $source = $this->findModel($id);
        
        $copy = new MasterForm();
        $copy->form_name = $source->form_name . ' (Copy)';
        $copy->form_data = $source->form_data;
        $copy->form_type = $source->form_type ?? '-';
        $copy->page_id = $source->page_id;
        $copy->table_id = $source->table_id;
        $copy->is_active = 0;
        
        if ($copy->save()) {
            return $this->redirect(['view', 'id' => $copy->id]);
        }
        
        return $this->redirect(['view', 'id' => $source->id]);
    }
    
    public function actionPreview($id)
    {
        $model = $this->findModel($id);
        return $this->render('preview', [
            'model' => $model,
        ]);
    }
    
    public function actionSubmit($id)
    {
        $model = $this->findModel($id);
        
        if (Yii::$app->request->isPost) {
            // APPLY DATABASE CONTEXT - ini kunci fix!
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $db = Yii::$app->db;
            $dbDsn = $db->dsn;
            
            \Yii::info([
                '=== SUBMIT DEBUG ===' => true,
                'original_dsn' => $dbDsn,
                'database_context' => $dbContext,
            ], 'submit_debug');
            
            $formData = $model->form_data;
            if (is_string($formData)) {
                $formData = json_decode($formData, true) ?? [];
            }
            
            $tableId = $model->table_id;
            if (!$tableId) {
                Yii::$app->session->setFlash('error', 'Target table not configured for this form.');
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $dbTable = DbTable::findOne($tableId);
            if (!$dbTable) {
                Yii::$app->session->setFlash('error', 'Target table metadata not found.');
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $tableName = $dbTable->name;
            \Yii::info("Target table: $tableName, DB: $dbDsn", 'submit_debug');
            
            $columns = $db->schema->getTableSchema($tableName, true);
            if (!$columns) {
                Yii::$app->session->setFlash('error', 'Target table "' . $tableName . '" not found in database "' . $dbDsn . '".');
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $colNames = array_keys($columns->columns);
            \Yii::info("Table columns found: " . implode(', ', $colNames), 'submit_debug');
            
            $postData = Yii::$app->request->post();
            \Yii::info("POST data received: " . json_encode(array_keys($postData)), 'submit_debug');
            
            $insertData = [];
            
            foreach ($formData as $field) {
                $fieldName = $field['name'] ?? null;
                $fieldType = $field['type'] ?? 'text';
                $isExcluded = !empty($field['excluded']);
                
                if (!$fieldName || $isExcluded) {
                    continue;
                }
                
                $postedValue = $postData[$fieldName] ?? null;
                
                if ($fieldType === 'checkboxes') {
                    $values = is_array($postedValue) ? $postedValue : ($postedValue ? [$postedValue] : []);
                    if (!empty($values)) {
                        $insertData[$fieldName] = implode(',', $values);
                    }
                } elseif ($postedValue !== null && $postedValue !== '') {
                    $insertData[$fieldName] = $postedValue;
                }
            }
            
            if (!empty($insertData)) {
                try {
                    $dbDsn = $db->dsn;
                    \Yii::info("=== SUBMIT DEBUG ===", 'submit_debug');
                    \Yii::info("DB DSN: $dbDsn", 'submit_debug');
                    \Yii::info("Target table: $tableName", 'submit_debug');
                    \Yii::info("Data to insert: " . json_encode($insertData), 'submit_debug');
                    
                    $cmd = $db->createCommand()->insert($tableName, $insertData);
                    $sql = $cmd->getSql();
                    \Yii::info("SQL: $sql", 'submit_debug');
                    
                    $cmd->execute();
                    
                    \Yii::info("Insert executed successfully", 'submit_debug');
                    
                    $colNames = array_keys($columns->columns);
                    $orderBy = in_array('id', $colNames) ? 'ORDER BY id DESC' : (in_array('created_at', $colNames) ? 'ORDER BY created_at DESC' : '');
                    if ($orderBy) {
                        $checkRows = $db->createCommand("SELECT * FROM $tableName $orderBy LIMIT 1")->queryAll();
                        \Yii::info("Last row after insert: " . json_encode($checkRows), 'submit_debug');
                    }
                    
                    Yii::$app->session->setFlash('success', 'Data saved! Fields: ' . implode(', ', array_keys($insertData)));
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('error', 'Save failed: ' . $e->getMessage());
                }
            } else {
                $postedFieldNames = array_keys($postData);
                $formFieldNames = array_column($formData, 'name');
                Yii::$app->session->setFlash('warning', 'No data extracted. POST: ' . implode(', ', $postedFieldNames) . ' | Form fields: ' . implode(', ', $formFieldNames));
            }
            
            return $this->redirect(['preview', 'id' => $id]);
        }
        
        return $this->redirect(['preview', 'id' => $id]);
    }
    
    protected function findModel($id)
    {
        if (($model = MasterForm::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}