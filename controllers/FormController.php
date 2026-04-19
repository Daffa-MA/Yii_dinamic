<?php

namespace app\controllers;

use Yii;
use app\components\RelationMapper;
use yii\db\IntegrityException;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use app\models\Form;
use app\models\FormSubmission;
use app\models\PublishedForm;
use app\models\DbTable;
use app\components\ActiveProjectContext;
use app\components\ProjectSchema;

class FormController extends Controller
{
    /** @var RelationMapper|null */
    private $relationMapper;

    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    private function assignActiveProject(Form $model): void
    {
        if (!$model->hasAttribute('project_id')) {
            return;
        }

        $activeProjectId = $this->getActiveProjectId();
        $model->project_id = $activeProjectId !== null ? (int)$activeProjectId : null;
    }

    private function shouldBypassProjectContext(string $actionId): bool
    {
        return in_array($actionId, ['render', 'submit', 'public-render', 'success', 'fk-options', 'fk-quick-add'], true);
    }

    private function getRelationMapper(): RelationMapper
    {
        if ($this->relationMapper === null) {
            $this->relationMapper = new RelationMapper();
        }

        return $this->relationMapper;
    }

    private function resolveFormTargetTableId(Form $form): int
    {
        if (method_exists($form, 'getEffectiveTableId')) {
            return (int)($form->getEffectiveTableId() ?? 0);
        }

        if ($form->hasAttribute('db_table_id')) {
            $newId = (int)$form->getAttribute('db_table_id');
            if ($newId > 0) {
                return $newId;
            }
        }

        return (int)$form->table_id;
    }

    private function shouldInsertDirectlyToTable(Form $form): bool
    {
        if (method_exists($form, 'shouldInsertToTargetTable')) {
            return $form->shouldInsertToTargetTable();
        }

        if ($form->hasAttribute('insert_to_table')) {
            return (int)$form->getAttribute('insert_to_table') === 1;
        }

        return $form->storage_type === 'database';
    }

    private function findTargetTableForForm(Form $form): ?DbTable
    {
        $tableId = $this->resolveFormTargetTableId($form);
        if ($tableId <= 0) {
            return null;
        }

        $criteria = [
            'id' => $tableId,
            'user_id' => (int)$form->user_id,
        ];
        if (ProjectSchema::supportsProjectContext() && $form->hasAttribute('project_id')) {
            $criteria['project_id'] = (int)$form->project_id;
        }

        return DbTable::findOne($criteria);
    }

    private function ensureGuestCanAccessPublicForm(Form $form): void
    {
        if (!Yii::$app->user->isGuest) {
            return;
        }

        $isPublished = PublishedForm::find()
            ->where(['form_id' => (int)$form->id])
            ->exists();
        if (!$isPublished) {
            throw new NotFoundHttpException('The requested form does not exist.');
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getForeignKeyConfigForForm(Form $form): array
    {
        $targetTable = $this->findTargetTableForForm($form);
        if ($targetTable === null || !(bool)$targetTable->is_created) {
            return [];
        }

        return $this->getRelationMapper()->buildForeignKeyConfig($targetTable);
    }

    private function buildFriendlyIntegrityErrorMessage(IntegrityException $exception, Form $form): string
    {
        $message = $exception->getMessage();
        $isForeignKeyError = stripos($message, 'foreign key constraint fails') !== false;
        if (!$isForeignKeyError) {
            return 'Data gagal disimpan karena constraint database. Mohon periksa kembali input Anda.';
        }

        $fieldLabel = null;
        $constraintName = null;
        $columnName = null;
        if (preg_match('/CONSTRAINT [`"]?([^`"\\s]+)[`"]?/i', $message, $constraintMatch)) {
            $constraintName = $constraintMatch[1];
        }
        if (preg_match('/FOREIGN KEY \\(`([^`]+)`\\)/i', $message, $columnMatch)) {
            $columnName = $columnMatch[1];
        }

        $fkConfig = $this->getForeignKeyConfigForForm($form);
        foreach ($fkConfig as $field => $config) {
            $configConstraint = isset($config['constraintName']) ? (string)$config['constraintName'] : '';
            $configField = isset($config['field']) ? (string)$config['field'] : (string)$field;

            if ($constraintName !== null && $configConstraint !== '' && strcasecmp($configConstraint, $constraintName) === 0) {
                $fieldLabel = isset($config['fieldLabel']) ? (string)$config['fieldLabel'] : $configField;
                break;
            }
            if ($columnName !== null && strcasecmp($configField, $columnName) === 0) {
                $fieldLabel = isset($config['fieldLabel']) ? (string)$config['fieldLabel'] : $configField;
                break;
            }
        }

        if ($fieldLabel === null && $columnName !== null) {
            $fieldLabel = ucwords(str_replace('_', ' ', $columnName));
        }
        if ($fieldLabel === null || trim($fieldLabel) === '') {
            return 'Data relasi yang dipilih tidak valid. Pastikan memilih data yang tersedia.';
        }

        return "Data pada field '{$fieldLabel}' tidak valid. Pastikan memilih data yang tersedia.";
    }

    private function resolveInsertedReferenceValue(string $tableName, string $referencedColumn, array $insertData): ?string
    {
        if (array_key_exists($referencedColumn, $insertData)) {
            $explicitValue = $insertData[$referencedColumn];
            if ($explicitValue !== null && $explicitValue !== '') {
                return (string)$explicitValue;
            }
        }

        $lastInsertId = (string)Yii::$app->db->getLastInsertID();
        if ($lastInsertId !== '') {
            return $lastInsertId;
        }

        if (empty($insertData)) {
            return null;
        }

        $query = (new Query())
            ->select([$referencedColumn])
            ->from($tableName);
        foreach ($insertData as $columnName => $columnValue) {
            $query->andWhere([$columnName => $columnValue]);
        }

        $resolvedValue = $query
            ->orderBy([$referencedColumn => SORT_DESC])
            ->scalar();

        if ($resolvedValue === false || $resolvedValue === null || $resolvedValue === '') {
            return null;
        }

        return (string)$resolvedValue;
    }

    /**
     * Increment columns are system-generated and should not be mapped to form inputs.
     */
    private function isIncrementColumn(array $column): bool
    {
        if (!empty($column['is_auto_increment'])) {
            return true;
        }

        $type = strtoupper((string)($column['type'] ?? ''));
        $isIntegerType = in_array($type, ['INT', 'BIGINT', 'TINYINT'], true);
        return $isIntegerType && !empty($column['is_primary']);
    }

    /**
     * Capture user-input fields that are posted outside generated schema mapping.
     */
    private function mergeAdditionalPostedInputs(array $data): array
    {
        $post = Yii::$app->request->post();
        $reservedKeys = [
            Yii::$app->request->csrfParam,
            'user_email',
            'user_name',
            'firebase_uid',
            'form_pages',
            'publish_now',
            'form_id',
        ];

        foreach ($post as $key => $value) {
            if (!is_string($key) || in_array($key, $reservedKeys, true) || array_key_exists($key, $data)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $data[$key] = (string) $value;
            }
        }

        // Capture uploaded file names so custom drag-drop file inputs are still recorded.
        foreach ($_FILES as $key => $fileMeta) {
            if (!is_string($key) || in_array($key, $reservedKeys, true) || array_key_exists($key, $data)) {
                continue;
            }

            $fileName = $this->extractUploadedFileNames($fileMeta['name'] ?? null, $fileMeta['error'] ?? null);
            if ($fileName !== null) {
                $data[$key] = $fileName;
            }
        }

        return $data;
    }

    /**
     * Convert PHP $_FILES name/error structure into storable value.
     */
    private function extractUploadedFileNames($name, $error): ?string
    {
        if (is_array($name)) {
            $collected = [];
            foreach ($name as $idx => $childName) {
                $childError = is_array($error) && array_key_exists($idx, $error) ? $error[$idx] : UPLOAD_ERR_NO_FILE;
                $resolved = $this->extractUploadedFileNames($childName, $childError);
                if ($resolved !== null && $resolved !== '') {
                    $collected[] = $resolved;
                }
            }

            return !empty($collected) ? json_encode($collected, JSON_UNESCAPED_UNICODE) : null;
        }

        if ($error === UPLOAD_ERR_NO_FILE || $name === null || $name === '') {
            return null;
        }

        return (string) $name;
    }

    private function normalizeInputKey(string $key): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9_]+/i', '_', $key), '_'));
    }

    private function castValueForTableColumn($value, array $column)
    {
        if ($value === null) {
            return null;
        }

        $type = strtoupper((string)($column['type'] ?? ''));
        $isNullable = !empty($column['is_nullable']);

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' && $isNullable) {
                return null;
            }
        }

        if ($type === 'JSON') {
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if (is_string($value)) {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $value;
                }
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (in_array($type, ['INT', 'BIGINT', 'TINYINT', 'BOOLEAN'], true)) {
            if ($value === 'on') {
                return 1;
            }
            if (is_numeric($value)) {
                return (int)$value;
            }
            return $value;
        }

        if (in_array($type, ['DECIMAL', 'FLOAT'], true) && is_numeric($value)) {
            return (float)$value;
        }

        return $value;
    }

    /**
     * Save submission into the selected custom table when mapping exists.
     */
    private function persistSubmissionToCustomTable(Form $form, array $data, ?int $targetTableId = null): bool
    {
        $tableId = $targetTableId !== null ? (int)$targetTableId : $this->resolveFormTargetTableId($form);
        if ($tableId <= 0) {
            return false;
        }

        $tableCriteria = ['id' => $tableId, 'user_id' => $form->user_id];
        if (ProjectSchema::supportsProjectContext() && $form->hasAttribute('project_id')) {
            $tableCriteria['project_id'] = $form->project_id;
        }

        $table = DbTable::findOne($tableCriteria);
        if ($table === null) {
            throw new \RuntimeException('Target table metadata was not found.');
        }
        if (!(bool)$table->is_created) {
            throw new \RuntimeException("Target table '{$table->name}' has not been created in database.");
        }

        $tableSchema = Yii::$app->db->schema->getTableSchema($table->name, true);
        if ($tableSchema === null) {
            throw new \RuntimeException("Physical table '{$table->name}' was not found in database.");
        }

        $columns = $table->getColumns()
            ->orderBy(['sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        $dataLookup = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $dataLookup[$key] = $value;
            $dataLookup[$this->normalizeInputKey($key)] = $value;
        }

        $insertData = [];
        foreach ($columns as $column) {
            if ($this->isIncrementColumn($column)) {
                continue;
            }

            $columnName = (string)($column['name'] ?? '');
            if ($columnName === '' || !isset($tableSchema->columns[$columnName])) {
                continue;
            }

            if (array_key_exists($columnName, $dataLookup)) {
                $insertData[$columnName] = $this->castValueForTableColumn($dataLookup[$columnName], $column);
                continue;
            }

            $normalizedColumnName = $this->normalizeInputKey($columnName);
            if (array_key_exists($normalizedColumnName, $dataLookup)) {
                $insertData[$columnName] = $this->castValueForTableColumn($dataLookup[$normalizedColumnName], $column);
            }
        }

        if (empty($insertData)) {
            return false;
        }

        Yii::$app->db->createCommand()->insert($table->name, $insertData)->execute();
        return true;
    }

    /**
     * Extract blocks from mixed schema shapes (pages, blocks, or legacy array).
     */
    private function extractBlocksFromSchema($schemaData): array
    {
        if (!is_array($schemaData)) {
            return [];
        }

        if (isset($schemaData['pages']) && is_array($schemaData['pages'])) {
            $allBlocks = [];
            foreach ($schemaData['pages'] as $page) {
                if (is_array($page) && isset($page['blocks']) && is_array($page['blocks'])) {
                    $allBlocks = array_merge($allBlocks, $page['blocks']);
                }
            }
            return $allBlocks;
        }

        if (isset($schemaData['blocks']) && is_array($schemaData['blocks'])) {
            return $schemaData['blocks'];
        }

        return array_values($schemaData) === $schemaData ? $schemaData : [];
    }

    /**
     * Normalize builder payload into canonical schema_js shape.
     */
    private function normalizeBuilderSchema(?string $pagesData, ?string $rawSchema): string
    {
        $decodedPagesData = null;
        if (is_string($pagesData) && trim($pagesData) !== '') {
            $decoded = json_decode($pagesData, true);
            if (is_array($decoded)) {
                $decodedPagesData = $decoded;
            }
        }

        $decodedRawSchema = [];
        if (is_string($rawSchema) && trim($rawSchema) !== '') {
            $decoded = json_decode($rawSchema, true);
            if (is_array($decoded)) {
                $decodedRawSchema = $decoded;
            }
        }

        $pages = [];
        if (is_array($decodedPagesData) && isset($decodedPagesData['pages']) && is_array($decodedPagesData['pages'])) {
            $pages = $decodedPagesData['pages'];
        }

        $customDesign = [];
        if (is_array($decodedPagesData) && isset($decodedPagesData['customDesign']) && is_array($decodedPagesData['customDesign'])) {
            $customDesign = $decodedPagesData['customDesign'];
        } elseif (isset($decodedRawSchema['customDesign']) && is_array($decodedRawSchema['customDesign'])) {
            $customDesign = $decodedRawSchema['customDesign'];
        }

        $rawBlocks = $this->extractBlocksFromSchema($decodedRawSchema);

        if (empty($pages)) {
            $pages = [[
                'id' => 'page_1',
                'name' => 'Page 1',
                'blocks' => $rawBlocks,
            ]];
        } else {
            $hasAnyPageBlocks = false;
            foreach ($pages as $index => $page) {
                if (!is_array($page)) {
                    $page = [];
                }

                $pageBlocks = isset($page['blocks']) && is_array($page['blocks']) ? array_values($page['blocks']) : [];
                if (!empty($pageBlocks)) {
                    $hasAnyPageBlocks = true;
                }

                $pages[$index] = [
                    'id' => !empty($page['id']) ? (string)$page['id'] : 'page_' . ($index + 1),
                    'name' => !empty($page['name']) ? (string)$page['name'] : 'Page ' . ($index + 1),
                    'blocks' => $pageBlocks,
                ];
            }

            if (!$hasAnyPageBlocks && !empty($rawBlocks)) {
                $pages[0]['blocks'] = $rawBlocks;
            }
        }

        $allBlocks = [];
        foreach ($pages as $page) {
            if (isset($page['blocks']) && is_array($page['blocks'])) {
                $allBlocks = array_merge($allBlocks, $page['blocks']);
            }
        }

        if (empty($allBlocks) && !empty($rawBlocks)) {
            $allBlocks = $rawBlocks;
        }

        return json_encode([
            'pages' => $pages,
            'customDesign' => $customDesign,
            'blocks' => $allBlocks,
        ], JSON_UNESCAPED_UNICODE);
    }

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
                        'actions' => ['render', 'submit', 'public-render', 'success', 'fk-options', 'fk-quick-add'],
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

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user->isGuest || $this->shouldBypassProjectContext($action->id)) {
            return true;
        }

        if (!ProjectSchema::supportsProjectContext()) {
            return true;
        }

        $activeProjectId = $this->getActiveProjectId();
        if ($activeProjectId === null) {
            Yii::$app->session->set('project_required_return_url', Yii::$app->request->url);
            Yii::$app->session->setFlash('warning', 'Pilih atau buat project terlebih dahulu sebelum mengelola form.');
            $this->redirect(['project/index']);
            return false;
        }

        return true;
    }

    /**
     * List all forms
     */
    public function actionIndex()
    {
        $activeProjectId = $this->getActiveProjectId();
        $schemaColumn = Form::getSchemaStorageColumn();

        $submissionCountSubQuery = FormSubmission::find()
            ->select(['form_id', 'submission_count' => 'COUNT(*)'])
            ->groupBy('form_id');

        $query = Form::find()
            ->alias('f')
            ->select([
                'f.id',
                'f.user_id',
                'f.name',
                'schema_js' => new \yii\db\Expression('f.' . $schemaColumn),
                'f.created_at',
                'submission_count' => new \yii\db\Expression('COALESCE(fs_count.submission_count, 0)'),
            ])
            ->leftJoin(['fs_count' => $submissionCountSubQuery], 'fs_count.form_id = f.id')
            ->where(['f.user_id' => Yii::$app->user->id])
            ->orderBy(['f.created_at' => SORT_DESC, 'f.id' => SORT_DESC]);
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $query->andWhere(['f.project_id' => $activeProjectId]);
        }

        // Search functionality
        $search = Yii::$app->request->get('q');
        if ($search) {
            $query->andWhere(['like', 'f.name', $search]);
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
        $this->assignActiveProject($model);
        $model->schema_js = '[]';
        if ($model->hasAttribute('insert_to_table')) {
            $model->setAttribute('insert_to_table', 0);
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->user_id = Yii::$app->user->id;
            $this->assignActiveProject($model);
            $model->name = trim((string) $model->name);
            if ($model->name === '') {
                $model->name = 'Untitled Page ' . date('Y-m-d H:i:s');
            }

            // Handle multi-page form and custom design data
            $pagesData = Yii::$app->request->post('form_pages');
            $postedFormData = Yii::$app->request->post($model->formName(), []);
            $rawSchema = isset($postedFormData['schema_js']) ? (string)$postedFormData['schema_js'] : (string)$model->schema_js;
            
            // DEBUG: Log what we received
            if (YII_DEBUG && $pagesData) {
                Yii::info('=== FORM SUBMIT DEBUG ===', 'app');
                Yii::info('Raw form_pages: ' . $pagesData, 'app');
                $decoded = json_decode($pagesData, true);
                if ($decoded) {
                    Yii::info('Custom Design received: ' . json_encode($decoded['customDesign'] ?? 'NOT SET'), 'app');
                }
            }
            
            $model->schema_js = $this->normalizeBuilderSchema($pagesData, $rawSchema);
            
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
            $model->user_id = Yii::$app->user->id;
            $this->assignActiveProject($model);
            $model->name = trim((string) $model->name);
            if ($model->name === '') {
                $model->name = 'Untitled Page ' . $model->id;
            }
            
            // Handle multi-page form and custom design data
            $pagesData = Yii::$app->request->post('form_pages');
            $postedFormData = Yii::$app->request->post($model->formName(), []);
            $rawSchema = isset($postedFormData['schema_js']) ? (string)$postedFormData['schema_js'] : (string)$model->schema_js;
            if ($pagesData) {
                $model->schema_js = $this->normalizeBuilderSchema($pagesData, $rawSchema);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Form updated successfully!');
                return $this->redirect(['view', 'id' => $model->id]);
            }

            $firstError = $model->getFirstErrors();
            $errorMessage = !empty($firstError) ? reset($firstError) : 'Failed to update form. Please check input data.';
            Yii::$app->session->setFlash('error', $errorMessage);
        }

        // Use the same builder UI as create page so edit experience stays identical.
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * View form details
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $schema = $model->getSchema();
        $totalSubmissions = (int) FormSubmission::find()
            ->where(['form_id' => $id])
            ->count();

        $recentSubmissions = FormSubmission::find()
            ->select(['id', 'form_id', 'created_at'])
            ->where(['form_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();

        return $this->render('view', [
            'model' => $model,
            'schema' => $schema,
            'totalSubmissions' => $totalSubmissions,
            'recentSubmissions' => $recentSubmissions,
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
        $this->ensureGuestCanAccessPublicForm($model);
        $schema = $model->getSchema();
        $fkConfig = [];

        try {
            $fkConfig = $this->getForeignKeyConfigForForm($model);
        } catch (\Throwable $e) {
            Yii::warning('Failed to resolve foreign key config for public-render: ' . $e->getMessage(), 'app');
        }

        return $this->render('public-render', [
            'model' => $model,
            'schema' => $schema,
            'fkConfig' => $fkConfig,
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
        $this->ensureGuestCanAccessPublicForm($model);
        $schema = $model->getSchema();

        if (Yii::$app->request->isPost) {
            $data = [];
            foreach ($schema as $field) {
                $name = $field['name'] ?? $field['label'] ?? '';
                if ($name) {
                    $data[$name] = Yii::$app->request->post($name, '');
                }
            }

            $data = $this->mergeAdditionalPostedInputs($data);

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

            $targetTableId = $this->resolveFormTargetTableId($model);
            $insertDirectlyToTable = $this->shouldInsertDirectlyToTable($model);
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $storedToTable = false;
                if ($insertDirectlyToTable) {
                    if ($targetTableId <= 0) {
                        throw new \RuntimeException('Form ini belum terhubung ke tabel database tujuan.');
                    }

                    $storedToTable = $this->persistSubmissionToCustomTable($model, $data, $targetTableId);
                    if (!$storedToTable) {
                        throw new \RuntimeException('Tidak ada field yang cocok untuk disimpan ke tabel target.');
                    }
                }

                if (!$insertDirectlyToTable) {
                    $submission = new FormSubmission();
                    $submission->form_id = $id;
                    $submission->user_id = !Yii::$app->user->isGuest ? Yii::$app->user->id : null;
                    $submission->firebase_uid = Yii::$app->request->post('firebase_uid');
                    $submission->firebase_email = Yii::$app->request->post('user_email');
                    $submission->firebase_name = Yii::$app->request->post('user_name');
                    $submission->data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

                    if (!$submission->save()) {
                        $errors = $submission->getFirstErrors();
                        $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Failed to submit form. Please try again.';
                        throw new \RuntimeException($errorMessage);
                    }
                }

                $transaction->commit();

                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['success', 'id' => $id]);
                }
                Yii::$app->session->setFlash('success', 'Form submitted successfully!');
                return $this->redirect(['render', 'id' => $id]);
            } catch (IntegrityException $e) {
                $transaction->rollBack();
                Yii::warning('IntegrityException on form submit: ' . $e->getMessage(), 'app');
                Yii::$app->session->setFlash('error', $this->buildFriendlyIntegrityErrorMessage($e, $model));

                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                return $this->redirect(['render', 'id' => $id]);
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());

                if (Yii::$app->user->isGuest) {
                    return $this->redirect(['public-render', 'id' => $id]);
                }
                return $this->redirect(['render', 'id' => $id]);
            }
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
        $this->ensureGuestCanAccessPublicForm($model);

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
        $this->assignActiveProject($newForm);
        $newForm->name = $model->name . ' (Copy)';
        $newForm->schema_js = $model->schema_js;
        $newForm->table_id = $model->table_id;
        $newForm->storage_type = $model->storage_type;
        if ($newForm->hasAttribute('db_table_id') && $model->hasAttribute('db_table_id')) {
            $newForm->setAttribute('db_table_id', $model->getAttribute('db_table_id'));
        }
        if ($newForm->hasAttribute('insert_to_table') && $model->hasAttribute('insert_to_table')) {
            $newForm->setAttribute('insert_to_table', (int)$model->getAttribute('insert_to_table'));
        }

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
        $tableCriteria = [
            'id' => (int)$tableId,
            'user_id' => Yii::$app->user->id,
        ];
        if (ProjectSchema::supportsProjectContext()) {
            $tableCriteria['project_id'] = $this->getActiveProjectId();
        }
        $table = \app\models\DbTable::findOne($tableCriteria);
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

        $columns = array_values(array_filter($columns, function ($column) {
            return !$this->isIncrementColumn($column);
        }));

        if (empty($columns)) {
            return [
                'success' => false,
                'error' => 'No input columns found',
                'message' => 'All columns are system-generated (e.g., auto increment) or no columns are defined.',
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

    public function actionFkOptions($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $field = (string)Yii::$app->request->post('field', Yii::$app->request->get('field', ''));
        if ($field === '') {
            return ['success' => false, 'message' => 'Field is required.'];
        }

        $fkConfig = $this->getForeignKeyConfigForForm($model);
        if (!isset($fkConfig[$field])) {
            return ['success' => false, 'message' => 'Field relasi tidak ditemukan.'];
        }

        return [
            'success' => true,
            'field' => $field,
            'options' => $fkConfig[$field]['options'] ?? [],
        ];
    }

    public function actionFkQuickAdd($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id, false);
        $this->ensureGuestCanAccessPublicForm($model);
        $field = (string)Yii::$app->request->post('field', '');
        $payload = Yii::$app->request->post('payload', []);
        if (is_string($payload) && trim($payload) !== '') {
            $decodedPayload = json_decode($payload, true);
            if (is_array($decodedPayload)) {
                $payload = $decodedPayload;
            }
        }
        if (!is_array($payload)) {
            $payload = [];
        }
        if ($field === '') {
            return ['success' => false, 'message' => 'Field relasi wajib diisi.'];
        }

        $fkConfig = $this->getForeignKeyConfigForForm($model);
        if (!isset($fkConfig[$field])) {
            return ['success' => false, 'message' => 'Konfigurasi relasi untuk field tidak ditemukan.'];
        }

        $config = $fkConfig[$field];
        $referencedTable = (string)($config['referencedTable'] ?? '');
        $referencedColumn = (string)($config['referencedColumn'] ?? '');
        $displayColumn = isset($config['displayColumn']) ? (string)$config['displayColumn'] : '';
        if ($referencedTable === '' || $referencedColumn === '') {
            return ['success' => false, 'message' => 'Konfigurasi referensi tidak valid.'];
        }

        $tableSchema = Yii::$app->db->schema->getTableSchema($referencedTable, true);
        if ($tableSchema === null) {
            return ['success' => false, 'message' => 'Tabel referensi tidak ditemukan.'];
        }

        $quickAddFields = [];
        foreach (($config['quickAddFields'] ?? []) as $quickField) {
            $fieldName = (string)($quickField['name'] ?? '');
            if ($fieldName !== '' && isset($tableSchema->columns[$fieldName])) {
                $quickAddFields[$fieldName] = true;
            }
        }

        $insertData = [];
        foreach ($quickAddFields as $fieldName => $enabled) {
            if (!$enabled) {
                continue;
            }
            $value = array_key_exists($fieldName, $payload) ? $payload[$fieldName] : null;
            $value = is_string($value) ? trim($value) : $value;
            if ($value === null || $value === '') {
                return [
                    'success' => false,
                    'message' => "Field '{$fieldName}' wajib diisi sebelum menambah data baru.",
                ];
            }
            $insertData[$fieldName] = $value;
        }

        if ($displayColumn !== '' && !array_key_exists($displayColumn, $insertData) && array_key_exists($displayColumn, $payload)) {
            $displayValue = is_string($payload[$displayColumn]) ? trim((string)$payload[$displayColumn]) : $payload[$displayColumn];
            if ($displayValue !== null && $displayValue !== '') {
                $insertData[$displayColumn] = $displayValue;
            }
        }

        if (empty($insertData)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dapat disimpan.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->insert($referencedTable, $insertData)->execute();
            $transaction->commit();
        } catch (IntegrityException $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Data gagal disimpan karena melanggar aturan relasi.'];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Gagal menambah data baru.'];
        }

        $newValue = $this->resolveInsertedReferenceValue($referencedTable, $referencedColumn, $insertData);
        if ($newValue === null || $newValue === '') {
            return ['success' => false, 'message' => 'Data berhasil ditambah, tetapi nilai referensi baru tidak dapat ditentukan.'];
        }

        $newLabel = '';
        if ($displayColumn !== '' && array_key_exists($displayColumn, $insertData)) {
            $newLabel = (string)$insertData[$displayColumn];
        }
        if ($newLabel === '') {
            $newLabel = 'Record #' . $newValue;
        }

        return [
            'success' => true,
            'option' => [
                'value' => $newValue,
                'label' => $newLabel,
            ],
            'field' => $field,
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

                    $form->schema_js = $this->normalizeBuilderSchema($pagesData, (string)$form->schema_js);
                    
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
        $id = (int)$id;

        if (!$checkOwnership) {
            $model = Form::findOne($id);
            if ($model !== null) {
                return $model;
            }
            throw new NotFoundHttpException('The requested form does not exist.');
        }

        $criteria = [
            'id' => $id,
            'user_id' => Yii::$app->user->id,
        ];

        $activeProjectId = $this->getActiveProjectId();
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $criteria['project_id'] = $activeProjectId;
        }

        if (($model = Form::findOne($criteria)) !== null) {
            return $model;
        }

        if (Form::find()->where(['id' => $id])->exists()) {
            throw new ForbiddenHttpException('You are not allowed to access this form in current project.');
        }

        throw new NotFoundHttpException('The requested form does not exist.');
    }
}
