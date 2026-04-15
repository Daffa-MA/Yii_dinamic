<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\models\Form;
use app\models\FormSubmission;
use app\models\PublishedForm;

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
                        'actions' => ['render', 'submit', 'public-render', 'success'],
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
        $model->schema_js = '[]';

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->name = trim((string) $model->name);
            if ($model->name === '') {
                $model->name = 'Untitled Page ' . date('Y-m-d H:i:s');
            }

            // Handle multi-page form and custom design data
            $pagesData = Yii::$app->request->post('form_pages');
            
            // DEBUG: Log what we received
            if (YII_DEBUG && $pagesData) {
                Yii::info('=== FORM SUBMIT DEBUG ===', 'app');
                Yii::info('Raw form_pages: ' . $pagesData, 'app');
                $decoded = json_decode($pagesData, true);
                if ($decoded) {
                    Yii::info('Custom Design received: ' . json_encode($decoded['customDesign'] ?? 'NOT SET'), 'app');
                }
            }
            
            if ($pagesData) {
                $decoded = json_decode($pagesData, true);
                if ($decoded && isset($decoded['pages'])) {
                    // Merge all pages blocks into schema_js for backward compatibility
                    $allBlocks = [];
                    foreach ($decoded['pages'] as $page) {
                        if (isset($page['blocks'])) {
                            $allBlocks = array_merge($allBlocks, $page['blocks']);
                        }
                    }
                    $model->schema_js = json_encode([
                        'pages' => $decoded['pages'],
                        'customDesign' => $decoded['customDesign'] ?? [],
                        'blocks' => $allBlocks
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                // Fallback: If form_pages is empty, try to create structure from blocks if they exist
                // This handles cases where custom design was set but form_pages wasn't populated
                $existingSchema = json_decode($model->schema_js, true) ?: [];
                if (!isset($existingSchema['pages'])) {
                    // Convert old format to new format with empty customDesign
                    $model->schema_js = json_encode([
                        'pages' => [[
                            'id' => 'page_1',
                            'name' => 'Page 1',
                            'blocks' => is_array($existingSchema) ? $existingSchema : []
                        ]],
                        'customDesign' => [],
                        'blocks' => is_array($existingSchema) ? $existingSchema : []
                    ], JSON_UNESCAPED_UNICODE);
                }
            }
            
            if ($model->save()) {
                $shouldPublish = (bool) Yii::$app->request->post('publish_now', false);

                if ($shouldPublish) {
                    $publishedForm = PublishedForm::find()
                        ->where(['form_id' => $model->id, 'user_id' => Yii::$app->user->id])
                        ->one();

                    if ($publishedForm === null) {
                        $publishedForm = new PublishedForm();
                        $publishedForm->user_id = Yii::$app->user->id;
                        $publishedForm->form_id = $model->id;
                    }

                    $publishedForm->name = $model->name;

                    if ($publishedForm->save()) {
                        Yii::$app->session->setFlash('success', 'Form created and published successfully!');
                        return $this->redirect(['published-form/index']);
                    }

                    Yii::error('Failed to publish form after create: ' . print_r($publishedForm->errors, true), 'app');
                    Yii::$app->session->setFlash('warning', 'Form created, but failed to publish. Please try publish again from the form page.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }

                Yii::$app->session->setFlash('success', 'Form created successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $firstError = $model->getFirstErrors();
            $errorMessage = !empty($firstError) ? reset($firstError) : 'Failed to create form. Please check input data.';
            Yii::$app->session->setFlash('error', $errorMessage);
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
            $model->name = trim((string) $model->name);
            if ($model->name === '') {
                $model->name = 'Untitled Page ' . $model->id;
            }
            
            // Handle multi-page form and custom design data
            $pagesData = Yii::$app->request->post('form_pages');
            if ($pagesData) {
                $decoded = json_decode($pagesData, true);
                if ($decoded && isset($decoded['pages'])) {
                    // Merge all pages blocks into schema_js for backward compatibility
                    $allBlocks = [];
                    foreach ($decoded['pages'] as $page) {
                        if (isset($page['blocks'])) {
                            $allBlocks = array_merge($allBlocks, $page['blocks']);
                        }
                    }
                    $model->schema_js = json_encode([
                        'pages' => $decoded['pages'],
                        'customDesign' => $decoded['customDesign'] ?? [],
                        'blocks' => $allBlocks
                    ], JSON_UNESCAPED_UNICODE);
                }
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Form updated successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $firstError = $model->getFirstErrors();
            $errorMessage = !empty($firstError) ? reset($firstError) : 'Failed to update form. Please check input data.';
            Yii::$app->session->setFlash('error', $errorMessage);
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
     * Render form for public access (no auth required)
     */
    public function actionPublicRender($id)
    {
        $model = $this->findModel($id, false);
        $schema = $model->getSchema();

        return $this->render('public-render', [
            'model' => $model,
            'schema' => $schema,
        ]);
    }

    /**
     * Save custom design via AJAX
     */
    public function actionSaveDesign($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $rawBody = Yii::$app->request->getRawBody();
            $data = json_decode($rawBody, true);

            if (!$data) {
                return ['success' => false, 'error' => 'Invalid JSON data'];
            }

            if ($id > 0) {
                // Update existing form
                $model = $this->findModel($id);
            } else {
                // For new forms, we can't save yet - user needs to create the form first
                return ['success' => false, 'error' => 'Please save the form first before saving design'];
            }

            // Get all blocks from pages
            $allBlocks = [];
            if (isset($data['pages'])) {
                foreach ($data['pages'] as $page) {
                    if (isset($page['blocks'])) {
                        $allBlocks = array_merge($allBlocks, $page['blocks']);
                    }
                }
            }

            // Save to schema_js with custom design
            $model->schema_js = json_encode([
                'pages' => $data['pages'] ?? [],
                'customDesign' => $data['customDesign'] ?? [],
                'blocks' => $allBlocks
            ], JSON_UNESCAPED_UNICODE);

            if ($model->save()) {
                return ['success' => true, 'message' => 'Design saved successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to save design', 'errors' => $model->errors];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
                $name = $field['name'] ?? $field['label'] ?? '';
                if ($name) {
                    $data[$name] = Yii::$app->request->post($name, '');
                }
            }

            // Auto inject Firebase user data from hidden fields
            if (Yii::$app->request->post('user_email')) {
                $data['_firebase_email'] = Yii::$app->request->post('user_email');
            }
            if (Yii::$app->request->post('user_name')) {
                $data['_firebase_name'] = Yii::$app->request->post('user_name');
            }
            if (Yii::$app->request->post('firebase_uid')) {
                $data['_firebase_uid'] = Yii::$app->request->post('firebase_uid');
            }

            // Auto inject Yii logged in user data if available
            if (!Yii::$app->user->isGuest) {
                $data['_user_id'] = Yii::$app->user->id;
                $data['_user_email'] = Yii::$app->user->identity->email;
                $data['_user_name'] = Yii::$app->user->identity->username;
            }

            $submission = new FormSubmission();
            $submission->form_id = $id;
            $submission->user_id = !Yii::$app->user->isGuest ? Yii::$app->user->id : null;
            $submission->firebase_uid = Yii::$app->request->post('firebase_uid');
            $submission->firebase_email = Yii::$app->request->post('user_email');
            $submission->firebase_name = Yii::$app->request->post('user_name');
            $submission->data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

            if ($submission->save()) {
                // Redirect to success page for guest users
                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['success', 'id' => $id]);
                }
                Yii::$app->session->setFlash('success', 'Form submitted successfully!');
            } else {
                $errors = $submission->getFirstErrors();
                $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Failed to submit form. Please try again.';
                
                // For guest users, redirect back to public render with error
                if (Yii::$app->user->isGuest) {
                    Yii::$app->session->setFlash('error', $errorMessage);
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                Yii::$app->session->setFlash('error', $errorMessage);
            }
            
            // Redirect back to public render for guest users, or regular render for logged-in users
            if (Yii::$app->user->isGuest) {
                return $this->redirect(['public-render', 'id' => $id]);
            }
            return $this->redirect(['render', 'id' => $id]);
        }

        // If not POST, redirect appropriately
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['public-render', 'id' => $id]);
        }
        return $this->redirect(['render', 'id' => $id]);
    }

    /**
     * Success page after form submission (for public users)
     */
    public function actionSuccess($id)
    {
        $model = $this->findModel($id, false);

        return $this->render('success', [
            'model' => $model,
        ]);
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
        $newForm->schema_js = $model->schema_js;

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
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

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

        Yii::info('GetTableColumns: tableId=' . var_export($tableId, true) . ', body=' . $body, 'app');

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
     * Publish a form (creates a published form entry)
     */
    public function actionPublish($id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        // Support both GET id and POST form_id
        $formId = Yii::$app->request->post('form_id', $id);

        Yii::info("Publish action called with formId: $formId, isAjax: " . (Yii::$app->request->isAjax ? 'yes' : 'no'), 'app');

        if (!$formId) {
            return ['success' => false, 'message' => 'Form ID is required.'];
        }

        try {
            $form = $this->findModel($formId);

            // Check if form_pages is sent (from publish modal with custom design)
            $pagesData = Yii::$app->request->post('form_pages');
            if ($pagesData) {
                Yii::info('Publish modal sent form_pages data', 'app');
                Yii::info('Raw form_pages: ' . $pagesData, 'app');
                $decoded = json_decode($pagesData, true);
                if ($decoded) {
                    Yii::info('Decoded form_pages: ' . json_encode($decoded), 'app');
                    
                    // Handle pages - use decoded pages or create default
                    $pages = $decoded['pages'] ?? [];
                    
                    // Handle customDesign - ensure it's an object, not array
                    $customDesign = $decoded['customDesign'] ?? [];
                    if (empty($customDesign) || !is_array($customDesign) || !isset($customDesign['css'])) {
                        // Ensure customDesign is an object with proper keys
                        $customDesign = [
                            'css' => $customDesign['css'] ?? '',
                            'htmlBefore' => $customDesign['htmlBefore'] ?? '',
                            'htmlAfter' => $customDesign['htmlAfter'] ?? '',
                            'js' => $customDesign['js'] ?? ''
                        ];
                    }
                    
                    Yii::info('CustomDesign after processing: ' . json_encode($customDesign), 'app');
                    
                    // Merge all pages blocks into schema_js for backward compatibility
                    $allBlocks = [];
                    foreach ($pages as $page) {
                        if (isset($page['blocks'])) {
                            $allBlocks = array_merge($allBlocks, $page['blocks']);
                        }
                    }
                    
                    // Save custom design to form's schema_js
                    $form->schema_js = json_encode([
                        'pages' => $pages,
                        'customDesign' => $customDesign,
                        'blocks' => $allBlocks
                    ], JSON_UNESCAPED_UNICODE);
                    
                    if (!$form->save()) {
                        Yii::error('Failed to save form with custom design: ' . print_r($form->errors, true), 'app');
                        return ['success' => false, 'message' => 'Failed to save form design: ' . implode(', ', $form->getFirstErrors())];
                    }
                    
                    Yii::info('✅ Form saved successfully with custom design', 'app');
                }
            }

            // Check if already published
            $existingPublished = PublishedForm::find()
                ->where(['form_id' => $formId, 'user_id' => Yii::$app->user->id])
                ->one();

            if (Yii::$app->request->isPost) {
                $name = Yii::$app->request->post('name', $form->name);

                Yii::info("Publishing form with name: $name, formId: $formId", 'app');

                if ($existingPublished) {
                    // Update existing published form
                    $existingPublished->name = $name;
                    if ($existingPublished->save()) {
                        $baseUrl = $this->getPublicUrl();
                        $formUrl = $baseUrl . '/form/public-render/' . $formId;

                        return [
                            'success' => true,
                            'message' => 'Form published successfully!',
                            'publicUrl' => $formUrl
                        ];
                    } else {
                        Yii::error('Failed to update published form: ' . print_r($existingPublished->errors, true), 'app');
                        return ['success' => false, 'message' => 'Failed to update: ' . implode(', ', $existingPublished->getFirstErrors())];
                    }
                } else {
                    // Create new published form
                    $publishedForm = new PublishedForm();
                    $publishedForm->user_id = Yii::$app->user->id;
                    $publishedForm->form_id = $formId;
                    $publishedForm->name = $name;

                    if ($publishedForm->save()) {
                        $baseUrl = $this->getPublicUrl();
                        $formUrl = $baseUrl . '/form/public-render/' . $formId;

                        return [
                            'success' => true,
                            'message' => 'Form published successfully!',
                            'publicUrl' => $formUrl
                        ];
                    } else {
                        Yii::error('Failed to create published form: ' . print_r($publishedForm->errors, true), 'app');
                        return ['success' => false, 'message' => 'Failed to publish: ' . implode(', ', $publishedForm->getFirstErrors())];
                    }
                }
            }

            // For GET requests, return form info
            return [
                'success' => true,
                'form' => [
                    'id' => $form->id,
                    'name' => $form->name,
                    'published' => $existingPublished !== null,
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Publish error: ' . $e->getMessage(), 'app');
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
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
