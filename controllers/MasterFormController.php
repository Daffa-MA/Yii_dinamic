<?php

namespace app\controllers;

use Yii;
use app\models\MasterForm;
use app\models\MasterPage;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * MasterFormController implements the CRUD actions for MasterForm model.
 */
class MasterFormController extends Controller
{
    public $layout = 'dashboard';

    /**
     * {@inheritdoc}
     */
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

    /**
     * Lists all MasterForm models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => MasterForm::find()->with('page'),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single MasterForm model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new MasterForm model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new MasterForm();

        if ($model->load(Yii::$app->request->post())) {
            // Decode JSON if submitted as string
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            
            // Auto-generate slug from form_name if not provided
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

    /**
     * Updates an existing MasterForm model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
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

    /**
     * Deletes an existing MasterForm model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the MasterForm model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return MasterForm the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = MasterForm::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
