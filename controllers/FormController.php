<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\models\Form;
use app\models\FormSubmission;

class FormController extends Controller
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
                        'actions' => ['render', 'submit'],
                        'allow' => true,
                        'roles' => ['?', '@'], // Allow both guests and authenticated users
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'], // All other actions require login
                    ],
                ],
            ],
        ];
    }

    /**
     * List all forms
     */
    public function actionIndex()
    {
        $query = Form::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC]);
        
        // Search functionality
        $search = Yii::$app->request->get('q');
        if ($search) {
            $query->andWhere(['like', 'name', $search]);
        }
        
        $forms = $query->all();

        return $this->render('index', [
            'forms' => $forms,
            'search' => $search,
        ]);
    }

    /**
     * Create new form
     */
    public function actionCreate()
    {
        $model = new Form();
        $model->user_id = Yii::$app->user->id;
        $model->schema_json = '[]';

        if ($model->load(Yii::$app->request->post())) {
            $schema = Yii::$app->request->post('FormSchema', '[]');
            $model->schema_json = $schema;
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Form created successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Update existing form
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $schema = Yii::$app->request->post('FormSchema', '[]');
            $model->schema_json = $schema;
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Form updated successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * View form details
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $submissions = FormSubmission::find()
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Delete form
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Form deleted successfully!');

        return $this->redirect(['index']);
    }

    /**
     * Render form for public/submission
     */
    public function actionRender($id)
    {
        $model = $this->findModel($id, false);
        $schema = $model->getSchema();

        return $this->render('render', [
            'model' => $model,
            'schema' => $schema,
        ]);
    }

    /**
     * Submit form data
     */
    public function actionSubmit($id)
    {
        $model = $this->findModel($id, false);
        $schema = $model->getSchema();

        if (Yii::$app->request->isPost) {
            $data = [];
            foreach ($schema as $field) {
                $name = $field['name'];
                $data[$name] = Yii::$app->request->post($name, '');
            }

            $submission = new FormSubmission();
            $submission->form_id = $id;
            $submission->user_id = !Yii::$app->user->isGuest ? Yii::$app->user->id : null;
            $submission->data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

            if ($submission->save()) {
                Yii::$app->session->setFlash('success', 'Form submitted successfully!');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to submit form. Please try again.');
            }
            return $this->redirect(['render', 'id' => $id]);
        }

        return $this->redirect(['render', 'id' => $id]);
    }

    /**
     * View submissions for a form
     */
    public function actionSubmissions($id)
    {
        $model = $this->findModel($id);

        $query = FormSubmission::find()
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC]);
        
        $countQuery = clone $query;
        $pages = new \yii\data\Pagination([
            'totalCount' => $countQuery->count(),
            'defaultPageSize' => 10,
        ]);
        
        $submissions = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        return $this->render('submissions', [
            'model' => $model,
            'submissions' => $submissions,
            'pages' => $pages,
        ]);
    }

    /**
     * Duplicate a form
     */
    public function actionDuplicate($id)
    {
        $model = $this->findModel($id);
        
        $newForm = new Form();
        $newForm->user_id = Yii::$app->user->id;
        $newForm->name = $model->name . ' (Copy)';
        $newForm->schema_json = $model->schema_json;
        
        if ($newForm->save()) {
            Yii::$app->session->setFlash('success', 'Form duplicated successfully!');
            return $this->redirect(['view', 'id' => $newForm->id]);
        }
        
        Yii::$app->session->setFlash('error', 'Failed to duplicate form.');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Export submissions to CSV
     */
    public function actionExport($id)
    {
        $model = $this->findModel($id);
        $schema = $model->getSchema();
        
        $submissions = FormSubmission::find()
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $model->name . '_submissions_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header row
        $headers = ['ID', 'Submitted At'];
        foreach ($schema as $field) {
            $headers[] = $field['label'] . ' (' . $field['name'] . ')';
        }
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($submissions as $submission) {
            $data = $submission->getData();
            $row = [$submission->id, $submission->created_at];
            foreach ($schema as $field) {
                $row[] = $data[$field['name']] ?? '';
            }
            fputcsv($output, $row);
        }
        
        fclose($output);
        Yii::$app->end();
    }

    /**
     * Get table columns for auto-generating form fields via AJAX
     */
    public function actionGetTableColumns()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Get table_id from JSON body (not form post data)
        $body = Yii::$app->request->getRawBody();
        $data = json_decode($body, true);
        $tableId = $data['table_id'] ?? Yii::$app->request->post('table_id');
        
        \Yii::info('GetTableColumns: tableId=' . var_export($tableId, true) . ', body=' . $body, 'app');
        
        if (!$tableId) {
            return [
                'success' => false, 
                'error' => 'No table selected',
                'message' => 'Please select a table from the dropdown',
                'debug' => ['received_table_id' => $tableId, 'body' => $body]
            ];
        }

        // Verify table belongs to current user
        $table = \app\models\DbTable::findOne(['id' => (int)$tableId, 'user_id' => Yii::$app->user->id]);
        if (!$table) {
            return [
                'success' => false, 
                'error' => 'Table not found or access denied',
                'message' => 'The selected table does not exist or you do not have permission to access it'
            ];
        }

        // Get all columns ordered by sort_order
        $columns = $table->getColumns()
            ->orderBy(['sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        if (empty($columns)) {
            return [
                'success' => false,
                'error' => 'No columns found',
                'message' => 'The selected table has no columns defined. Please add columns first.',
                'table_name' => $table->name,
                'table_id' => $table->id
            ];
        }

        return [
            'success' => true,
            'columns' => $columns,
            'table_id' => $table->id,
            'table_name' => $table->name,
            'column_count' => count($columns)
        ];
    }

    /**
     * Finds the Form model based on its primary key value.
     *
     * @param integer $id
     * @return Form the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     * @throws ForbiddenHttpException if user does not own the form
     */
    protected function findModel($id, $checkOwnership = true)
    {
        if (($model = Form::findOne($id)) !== null) {
            if ($checkOwnership && $model->user_id != Yii::$app->user->id) {
                throw new ForbiddenHttpException('You are not allowed to access this form.');
            }
            return $model;
        }

        throw new NotFoundHttpException('The requested form does not exist.');
    }
}
