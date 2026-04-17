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
        $userId = Yii::$app->user->id;
        $query = Form::find()
            ->alias('f')
            ->select(['f.id', 'f.user_id', 'f.name', 'f.created_at'])
            ->where(['f.user_id' => $userId])
            ->with([
                'publishedForms' => function ($q) use ($userId) {
                    $q->select(['id', 'user_id', 'form_id', 'name', 'slug', 'created_at'])
                        ->andWhere(['user_id' => $userId])
                        ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
                }
            ])
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC]);

        // Search functionality
        $search = Yii::$app->request->get('q');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'f.name', $search],
                [
                    'exists',
                    PublishedForm::find()
                        ->alias('pf_search')
                        ->select('1')
                        ->where('pf_search.form_id = f.id')
                        ->andWhere(['pf_search.user_id' => $userId])
                        ->andWhere(['like', 'pf_search.name', $search]),
                ],
            ]);
        }

        $forms = $query->all();

        return $this->render('index', [
            'forms' => $forms,
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
        $request = Yii::$app->request;
        $isAjax = $request->isAjax;

        $availableFormsQuery = Form::find()
            ->alias('f')
            ->select(['f.id', 'f.name', 'f.created_at'])
            ->leftJoin(
                ['pf' => PublishedForm::tableName()],
                'pf.form_id = f.id AND pf.user_id = :userId',
                [':userId' => Yii::$app->user->id]
            )
            ->where(['f.user_id' => Yii::$app->user->id])
            ->andWhere(['pf.id' => null])
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC]);

        // Get form_id from query parameter (from publish button)
        $formId = $request->get('form_id');
        if ($formId) {
            $requestedFormId = (int) $formId;
            $isAvailable = (clone $availableFormsQuery)->andWhere(['f.id' => $requestedFormId])->exists();
            if ($isAvailable) {
                $model->form_id = $requestedFormId;
            } else {
                Yii::$app->session->setFlash('warning', 'Selected form is already published or unavailable.');
            }
        }

        if ($request->isPost) {
            $posted = $request->post($model->formName(), []);
            if (empty($posted)) {
                // Fallback for non-ActiveForm shaped payload.
                $posted = [
                    'name' => $request->post('name'),
                    'form_id' => $request->post('form_id'),
                ];
            }

            $model->setAttributes($posted);
            $selectedFormId = (int) $model->form_id;
            $isAvailable = $selectedFormId > 0
                ? (clone $availableFormsQuery)->andWhere(['f.id' => $selectedFormId])->exists()
                : false;

            if (!$isAvailable) {
                $model->addError('form_id', 'Selected form is already published or unavailable.');
            } elseif ($model->save()) {
                $baseUrl = $this->getPublicUrl();
                $formUrl = $baseUrl . '/form/public-render/' . $model->form_id;

                if ($isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return [
                        'success' => true,
                        'publishedFormId' => $model->id,
                        'url' => $formUrl,
                        'formName' => $model->name,
                    ];
                }

                Yii::$app->session->setFlash('publishResult', [
                    'url' => $formUrl,
                    'formName' => $model->name,
                ]);
                return $this->redirect(['create']);
            }

            if ($isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return [
                    'success' => false,
                    'message' => implode(', ', $model->getFirstErrors()) ?: 'Failed to publish form.',
                    'errors' => $model->getErrors(),
                ];
            }

            Yii::$app->session->setFlash('error', implode(', ', $model->getFirstErrors()) ?: 'Failed to publish form.');
        }

        // Get available forms for dropdown
        $forms = $availableFormsQuery->all();

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
