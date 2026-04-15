<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\PublishedForm;
use app\models\Form;

/**
 * PublishedFormController implements CRUD operations for published forms
 */
class PublishedFormController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['get-public-url'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all published forms
     */
    public function actionIndex()
    {
        $query = PublishedForm::find()
            ->with('form')
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC]);

        // Search functionality
        $search = Yii::$app->request->get('q');
        if ($search) {
            $query->andWhere(['like', 'name', $search]);
        }

        $publishedForms = $query->all();

        return $this->render('index', [
            'publishedForms' => $publishedForms,
            'search' => $search,
        ]);
    }

    /**
     * Creates a new published form
     */
    public function actionCreate()
    {
        $model = new PublishedForm();
        $model->user_id = Yii::$app->user->id;

        // Get form_id from query parameter (from publish button)
        $formId = Yii::$app->request->get('form_id');
        if ($formId) {
            $model->form_id = $formId;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Form published successfully!');
            return $this->redirect(['index']);
        }

        // Get available forms for dropdown
        $forms = Form::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return $this->render('create', [
            'model' => $model,
            'forms' => $forms,
        ]);
    }

    /**
     * Updates an existing published form
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Published form updated successfully!');
            return $this->redirect(['index']);
        }

        // Get available forms for dropdown
        $forms = Form::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return $this->render('update', [
            'model' => $model,
            'forms' => $forms,
        ]);
    }

    /**
     * Deletes an existing published form
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Published form deleted successfully!');

        return $this->redirect(['index']);
    }

    /**
     * Finds the PublishedForm model based on its primary key value.
     *
     * @param integer $id
     * @return PublishedForm the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PublishedForm::findOne(['id' => $id, 'user_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested published form does not exist.');
    }

    /**
     * Get public URL and QR code for a published form (AJAX)
     */
    public function actionGetPublicUrl($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $model = $this->findModel($id);
        
        $baseUrl = $this->getPublicUrl();
        $formUrl = $baseUrl . '/form/public-render/' . $model->form_id;
        
        return [
            'success' => true,
            'url' => $formUrl,
            'formName' => $model->name,
            'slug' => $model->slug,
        ];
    }

    /**
     * Get the application base URL for public links.
     * APP_URL is used first (Railway), then current request host.
     * @return string
     */
    protected function getPublicUrl()
    {
        $baseUrl = getenv('APP_URL');
        if (!$baseUrl) {
            $railwayDomain = getenv('RAILWAY_PUBLIC_DOMAIN') ?: getenv('RAILWAY_STATIC_URL');
            if ($railwayDomain) {
                $baseUrl = preg_match('/^https?:\/\//i', $railwayDomain)
                    ? $railwayDomain
                    : 'https://' . $railwayDomain;
            }
        }

        if (!$baseUrl) {
            $baseUrl = Yii::$app->request->hostInfo;
        }

        return rtrim($baseUrl, '/');
    }
}
