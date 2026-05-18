<?php

namespace app\controllers;

use Yii;
use app\models\MasterForm;
use app\models\MasterFormField;
use app\models\MasterFormLayout;
use app\models\MasterFormActivityLog;
use app\models\MasterPage;
use app\models\MasterMenu;
use app\models\DbTable;
use app\models\DbTableColumn;
use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\DatabaseSchemaInitializer;
use app\components\ProjectSchema;
use app\components\ProjectPermissionService;
use app\components\SystemFieldService;
use app\helpers\FormSystemFieldHelper;
use app\components\FormFlowDebugLogger;
use app\services\FormActivityLogService;
use app\services\FormEngineService;
use app\services\FormRenderService;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class MasterFormController extends Controller
{
    public $layout = 'dashboard';
    private FormEngineService $formEngineService;
    private FormRenderService $formRenderService;
    private FormActivityLogService $activityLogService;

    public function init()
    {
        parent::init();
        $this->formEngineService = new FormEngineService();
        $this->formRenderService = new FormRenderService();
        $this->activityLogService = new FormActivityLogService();
    }

    private function assignActiveProject(MasterForm $model): void
    {
        if (!$model->hasAttribute('project_id')) {
            return;
        }

        $activeProjectId = $this->getActiveProjectId();
        $model->project_id = $activeProjectId !== null ? (int)$activeProjectId : null;
    }

    private function getActiveProjectId(): ?int
    {
        if (!ProjectSchema::supportsProjectContext()) {
            return null;
        }

        return (new ActiveProjectContext())->getActiveProjectId();
    }

    private function resolveTargetTableId(MasterForm $model): int
    {
        if ($model->hasAttribute('db_table_id')) {
            $dbTableId = (int)$model->getAttribute('db_table_id');
            if ($dbTableId > 0) {
                return $dbTableId;
            }
        }

        return (int)$model->table_id;
    }

    private function cleanSystemFieldsFromModel(MasterForm $model): bool
    {
        $original = $model->getFormDataArray();
        $clean = $this->filterSystemFieldsForModel($original, $model);
        $model->form_data = $clean;

        return $clean !== $original;
    }

    private function filterSystemFieldsForModel(array $builderData, MasterForm $model): array
    {
        $filter = function (array $fields) use ($model): array {
            $filtered = [];
            foreach ($fields as $field) {
                if (!is_array($field) || $this->isSystemFieldDataForModel($field, $model)) {
                    continue;
                }
                $filtered[] = $field;
            }
            return $filtered;
        };

        if (isset($builderData['fields']) && is_array($builderData['fields'])) {
            $builderData['fields'] = $filter($builderData['fields']);
            return $builderData;
        }

        if ($this->isListArray($builderData)) {
            return $filter($builderData);
        }

        return $builderData;
    }

    private function isSystemFieldDataForModel(array $fieldData, MasterForm $model): bool
    {
        if (FormSystemFieldHelper::isSystemFieldData($fieldData)) {
            return true;
        }

        $sourceColumnId = (int)($fieldData['source_column_id'] ?? 0);
        if ($sourceColumnId > 0) {
            $sourceColumn = DbTableColumn::findOne($sourceColumnId);
            if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                return true;
            }
        }

        if (!empty($model->table_id)) {
            $fieldName = $fieldData['name'] ?? $fieldData['field_name'] ?? $fieldData['field_key'] ?? '';
            if ($fieldName !== '') {
                $sourceColumn = DbTableColumn::find()
                    ->where(['table_id' => (int)$model->table_id, 'name' => (string)$fieldName])
                    ->one();
                if ($sourceColumn && SystemFieldService::shouldHideFromForm($sourceColumn)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function findScopedModel($id): MasterForm
    {
        $model = MasterForm::findByIdScoped($id);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    private function normalizeBuilderData(MasterForm $model): array
    {
        $formData = $this->filterSystemFieldsForModel($model->getFormDataArray(), $model);
        if (!empty($formData['fields']) && is_array($formData['fields'])) {
            return $formData;
        }

        return [
            'fields' => $formData,
        ];
    }

    private function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    private function extractFieldsFromBuilderData(array $builderData): array
    {
        if (isset($builderData['fields']) && is_array($builderData['fields'])) {
            return $builderData['fields'];
        }

        if ($this->isListArray($builderData)) {
            return $builderData;
        }

        return [];
    }

    private function normalizeFieldName(array $field, int $index): string
    {
        $name = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? ''));
        if ($name === '') {
            $name = 'field_' . ($index + 1);
        }

        return $name;
    }

    private function extractCustomCodePost(): array
    {
        $post = Yii::$app->request->post();
        $modelPost = $post['MasterForm'] ?? [];
        $useCustomCode = (int)($modelPost['use_custom_code'] ?? $post['use_custom_code'] ?? $modelPost['custom_code_mode'] ?? 0) === 1;

        return [
            'use_custom_code' => $useCustomCode ? 1 : 0,
            'custom_html' => (string)($modelPost['custom_html'] ?? $post['custom_html'] ?? ''),
            'custom_css' => (string)($modelPost['custom_css'] ?? $post['custom_css'] ?? ''),
            'custom_js' => (string)($modelPost['custom_js'] ?? $post['custom_js'] ?? ''),
        ];
    }

    private function assignCustomCodeToModel(MasterForm $model, array $customCode): void
    {
        $useCustomCode = !empty($customCode['use_custom_code']) ? 1 : 0;

        if ($model->hasAttribute('custom_html')) {
            $model->custom_html = $useCustomCode ? (string)($customCode['custom_html'] ?? '') : '';
        }
        if ($model->hasAttribute('custom_css')) {
            $model->custom_css = $useCustomCode ? (string)($customCode['custom_css'] ?? '') : '';
        }
        if ($model->hasAttribute('custom_js')) {
            $model->custom_js = $useCustomCode ? (string)($customCode['custom_js'] ?? '') : '';
        }
        if ($model->hasAttribute('use_custom_code')) {
            $model->use_custom_code = $useCustomCode;
        }
        if ($model->hasAttribute('custom_code_mode')) {
            $model->custom_code_mode = $useCustomCode;
        }
    }

    private function syncFormArchitecture(MasterForm $model, ?array $customCode = null): void
    {
        $builderData = $this->normalizeBuilderData($model);
        $fields = $this->extractFieldsFromBuilderData($builderData);
        $previousLayout = $model->getActiveLayout()->one();
        if ($customCode === null) {
            $customCode = [
                'use_custom_code' => $model->hasAttribute('use_custom_code') ? (!empty($model->use_custom_code) ? 1 : 0) : (!empty($model->custom_code_mode) ? 1 : 0),
                'custom_html' => $model->hasAttribute('custom_html') ? (string)$model->custom_html : ($previousLayout ? (string)$previousLayout->custom_html : ''),
                'custom_css' => $model->hasAttribute('custom_css') ? (string)$model->custom_css : ($previousLayout ? (string)$previousLayout->custom_css : ''),
                'custom_js' => $model->hasAttribute('custom_js') ? (string)$model->custom_js : ($previousLayout ? (string)$previousLayout->custom_js : ''),
            ];
        }

        MasterFormField::deleteAll(['form_id' => $model->id]);
        MasterFormLayout::deleteAll(['form_id' => $model->id]);

        foreach ($fields as $index => $fieldData) {
            if (!is_array($fieldData)) {
                continue;
            }

            $field = new MasterFormField();
            $fieldName = $this->normalizeFieldName($fieldData, (int)$index);
            if ($this->isSystemFieldDataForModel($fieldData, $model)) {
                continue;
            }

            $fieldType = (string)($fieldData['type'] ?? $fieldData['field_type'] ?? 'text');
            $field->form_id = (int)$model->id;
            $field->field_key = $fieldName;
            $field->field_name = $fieldName;
            $field->field_label = (string)($fieldData['label'] ?? $fieldData['field_label'] ?? ucfirst(str_replace('_', ' ', $fieldName)));
            $field->field_type = $fieldType;
            $field->component_type = (string)($fieldData['component_type'] ?? $fieldData['inputType'] ?? $fieldType);
            $field->is_required = !empty($fieldData['required'] ?? $fieldData['is_required']) ? 1 : 0;
            $field->placeholder = (string)($fieldData['placeholder'] ?? '');
            $field->default_value = isset($fieldData['default_value']) ? (string)$fieldData['default_value'] : null;
            $field->dropdown_source = (string)($fieldData['dropdown_source'] ?? (!empty($fieldData['fk_options']) ? 'foreign_key' : (!empty($fieldData['options']) ? 'static_options' : '')));
            $field->foreign_key_table = isset($fieldData['fk_referenced_table']) ? (string)$fieldData['fk_referenced_table'] : null;
            $field->foreign_key_column = isset($fieldData['fk_display_column']) ? (string)$fieldData['fk_display_column'] : null;
            $field->validation_rules = Json::encode([
                'required' => !empty($fieldData['required'] ?? $fieldData['is_required']),
                'rules' => $fieldData['validation_rules'] ?? null,
            ]);
            $field->field_config = Json::encode($fieldData);
            $field->field_settings = Json::encode($fieldData);
            $field->sort_order = (int)$index;
            $field->save(false);

        }

        $layout = new MasterFormLayout();
        $layout->form_id = (int)$model->id;
        $layout->layout_name = $model->form_name . ' Layout';
        $layout->layout_type = (string)($model->form_type ?: 'builder');
        $layout->layout_json = Json::encode([
            'form' => $model->getAttributes(),
            'builder' => $builderData,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $layout->custom_html = !empty($customCode['use_custom_code']) ? (string)($customCode['custom_html'] ?? '') : '';
        $layout->custom_css = !empty($customCode['use_custom_code']) ? (string)($customCode['custom_css'] ?? '') : '';
        $layout->custom_js = !empty($customCode['use_custom_code']) ? (string)($customCode['custom_js'] ?? '') : '';
        if ($layout->hasAttribute('use_custom_code')) {
            $layout->use_custom_code = !empty($customCode['use_custom_code']) ? 1 : 0;
        }
        $layout->builder_state = Json::encode($builderData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $layout->is_default = 1;
        $layout->sort_order = 0;
        $layout->save(false);

        $this->assignCustomCodeToModel($model, $customCode);
        $attributes = array_values(array_filter(['custom_html', 'custom_css', 'custom_js', 'use_custom_code', 'custom_code_mode'], fn($attribute) => $model->hasAttribute($attribute)));
        if (!empty($attributes)) {
            $model->save(false, $attributes);
        }
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $dbContext = new ActiveDatabaseContext();
        $dbContext->resolveAndApply();
        DatabaseSchemaInitializer::ensureMasterFormStructure(Yii::$app->db);
        Yii::$app->db->schema->refresh();

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
        $query = MasterForm::findScoped()->with('page');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findScopedModel($id);
        if ($this->cleanSystemFieldsFromModel($model)) {
            $model->save(false, ['form_data']);
            $this->syncFormArchitecture($model);
        }
        $logs = [];
        if (Yii::$app->db->getTableSchema(MasterFormActivityLog::tableName(), true) !== null) {
            $logs = MasterFormActivityLog::find()
                ->where(['form_id' => (int)$model->id])
                ->orderBy(['id' => SORT_DESC])
                ->limit(15)
                ->all();
        }
        return $this->render('view', [
            'model' => $model,
            'activityLogs' => $logs,
        ]);
    }

    public function actionCreate()
    {
        $model = new MasterForm();
        $this->assignActiveProject($model);

        if ($model->load(Yii::$app->request->post())) {
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $customCode = $this->extractCustomCodePost();
            $this->assignCustomCodeToModel($model, $customCode);
            $this->assignActiveProject($model);
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            $this->cleanSystemFieldsFromModel($model);
            
            if (!empty($model->table_id)) {
                $model->table_id = (int)$model->table_id;
            }
            
            if (empty($model->slug) && !empty($model->form_name)) {
                $model->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $model->form_name)));
            }
            if ($model->hasAttribute('database_context')) {
                $model->database_context = (string)($dbContext['activeDatabase'] ?? '');
            }
            if ($model->hasAttribute('form_type') && empty($model->form_type)) {
                $model->form_type = 'dynamic';
            }
            
            if ($model->save()) {
                $this->syncFormArchitecture($model, $customCode);
                $this->activityLogService->log($model, 'form_created', 'success', 'Form created and synced.');
                Yii::$app->session->setFlash('success', 'Form berhasil dibuat dan struktur fields/layout tersimpan.');
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
        $model = $this->findScopedModel($id);
        if ($this->cleanSystemFieldsFromModel($model)) {
            $model->save(false, ['form_data']);
            $this->syncFormArchitecture($model);
        }

        if ($model->load(Yii::$app->request->post())) {
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $customCode = $this->extractCustomCodePost();
            $this->assignCustomCodeToModel($model, $customCode);
            $this->assignActiveProject($model);
            if (is_string($model->form_data)) {
                $model->form_data = json_decode($model->form_data, true);
            }
            $this->cleanSystemFieldsFromModel($model);
            
            if (!empty($model->table_id)) {
                $model->table_id = (int)$model->table_id;
            }
            
            if (empty($model->slug) && !empty($model->form_name)) {
                $model->slug = strtolower(preg_replace('/[^\w\s-]/', '', preg_replace('/[\s_-]+/', '-', $model->form_name)));
            }
            if ($model->hasAttribute('database_context')) {
                $model->database_context = (string)($dbContext['activeDatabase'] ?? '');
            }
            
            if ($model->save()) {
                $this->syncFormArchitecture($model, $customCode);
                $this->activityLogService->log($model, 'form_updated', 'success', 'Form updated and synced.');
                Yii::$app->session->setFlash('success', 'Form berhasil diperbarui dan struktur fields/layout disinkronkan.');
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
        $model = $this->findScopedModel($id);
        MasterFormField::deleteAll(['form_id' => $model->id]);
        MasterFormLayout::deleteAll(['form_id' => $model->id]);
        $model->delete();
        return $this->redirect(['index']);
    }
    
    public function actionDuplicate($id)
    {
        $source = $this->findScopedModel($id);
        
        $copy = new MasterForm();
        $copy->form_name = $source->form_name . ' (Copy)';
        $copy->form_data = $source->form_data;
        $copy->form_type = $source->form_type ?? 'dynamic';
        $copy->database_context = $source->database_context ?? null;
        if ($copy->hasAttribute('custom_html') && $source->hasAttribute('custom_html')) {
            $copy->custom_html = $source->custom_html;
        }
        if ($copy->hasAttribute('custom_css') && $source->hasAttribute('custom_css')) {
            $copy->custom_css = $source->custom_css;
        }
        if ($copy->hasAttribute('custom_js') && $source->hasAttribute('custom_js')) {
            $copy->custom_js = $source->custom_js;
        }
        if ($copy->hasAttribute('use_custom_code') && $source->hasAttribute('use_custom_code')) {
            $copy->use_custom_code = $source->use_custom_code;
        }
        $copy->custom_code_mode = $source->custom_code_mode;
        $copy->page_id = $source->page_id;
        $copy->table_id = $source->table_id;
        $this->assignActiveProject($copy);
        $copy->is_active = 0;
        
        if ($copy->save()) {
            $this->syncFormArchitecture($copy);
            return $this->redirect(['view', 'id' => $copy->id]);
        }
        
        return $this->redirect(['view', 'id' => $source->id]);
    }
    
    public function actionPreview($id)
    {
        $model = $this->findScopedModel($id);
        if ($this->cleanSystemFieldsFromModel($model)) {
            $model->save(false, ['form_data']);
            $this->syncFormArchitecture($model);
        }
        $schema = $this->formEngineService->getResolvedFormSchema($model);
        $renderPayload = $this->formRenderService->buildRenderPayload($model, $schema['fields'], $schema['layout']);
        if (!empty($schema['autoSynced'])) {
            $this->activityLogService->log($model, 'auto_sync', 'success', 'Legacy form_data auto-synced to relational tables.');
        }
        $this->activityLogService->log($model, 'preview_opened', 'success', 'Preview opened.');
        return $this->render('preview', [
            'model' => $model,
            'renderPayload' => $renderPayload,
        ]);
    }
    
    public function actionSubmit($id)
    {
        $model = $this->findScopedModel($id);
        
        if (Yii::$app->request->isPost) {
            $isEmbedded = (int)Yii::$app->request->post('_embedded', 0) === 1;
            $isAjax = Yii::$app->request->isAjax || $isEmbedded;
            if ($isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            }

            if ($isEmbedded && !$this->canSubmitEmbeddedPageForm((int)$model->id)) {
                $message = 'Form ini belum terhubung ke halaman yang bisa Anda akses.';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }

            // APPLY DATABASE CONTEXT - ini kunci fix!
            $dbContext = (new ActiveDatabaseContext())->resolveAndApply();
            $db = Yii::$app->db;
            $dbDsn = $db->dsn;
            
            \Yii::info([
                '=== SUBMIT DEBUG ===' => true,
                'original_dsn' => $dbDsn,
                'database_context' => $dbContext,
            ], 'submit_debug');
            
            $schema = $this->formEngineService->getResolvedFormSchema($model);
            $fields = $schema['fields'];
            
            $tableId = $this->resolveTargetTableId($model);
            if (!$tableId) {
                $message = 'Target table not configured for this form.';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $dbTable = DbTable::findOne(['id' => $tableId]);
            if (ProjectSchema::supportsProjectContext() && $model->hasAttribute('project_id') && (int)$model->project_id > 0) {
                $dbTableQuery = DbTable::find()
                    ->where([
                        'id' => $tableId,
                    ])
                    ->andWhere(['project_id' => (int)$model->project_id]);
                $scopedDbTable = $dbTableQuery->one();
                if ($scopedDbTable !== null) {
                    $dbTable = $scopedDbTable;
                }
            }
            if (!$dbTable) {
                $message = 'Target table metadata not found.';
                FormFlowDebugLogger::logSubmit([
                    'host' => Yii::$app->request->hostInfo,
                    'project_id' => $this->getActiveProjectId(),
                    'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                    'form_id' => (int)$model->id,
                    'target_table_id' => $tableId,
                    'resolved_table_name' => null,
                    'metadata_found' => false,
                    'metadata_source' => 'master_form.table_id',
                    'submitted_fields' => array_keys($postData),
                    'system_fields_applied' => [],
                    'insert_result' => 'metadata_missing',
                    'error' => $message,
                ]);
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $tableName = $dbTable->name;
            \Yii::info("Target table: $tableName, DB: $dbDsn", 'submit_debug');
            
            $columns = $db->schema->getTableSchema($tableName, true);
            if (!$columns) {
                $message = 'Target table "' . $tableName . '" not found in database "' . $dbDsn . '".';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('error', $message);
                return $this->redirect(['preview', 'id' => $id]);
            }
            
            $colNames = array_keys($columns->columns);
            \Yii::info("Table columns found: " . implode(', ', $colNames), 'submit_debug');
            
            $postData = Yii::$app->request->post();
            \Yii::info("POST data received: " . json_encode(array_keys($postData)), 'submit_debug');
            
            $insertData = [];
            
            foreach ($fields as $field) {
                $fieldName = $field['name'] ?? null;
                $fieldType = $field['type'] ?? 'text';
                $isExcluded = !empty($field['excluded']);
                
                if (!$fieldName || $isExcluded || FormSystemFieldHelper::isSystemFieldData($field)) {
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

            $preSystemInsertData = $insertData;
            $insertData = SystemFieldService::applyCreateValues($insertData, $columns->columns);
            $systemFieldsApplied = array_values(array_diff(array_keys($insertData), array_keys($preSystemInsertData)));
            
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
                    FormFlowDebugLogger::logSubmit([
                        'host' => Yii::$app->request->hostInfo,
                        'project_id' => $this->getActiveProjectId(),
                        'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                        'form_id' => (int)$model->id,
                        'target_table_id' => $tableId,
                        'resolved_table_name' => $tableName,
                        'metadata_found' => true,
                        'metadata_source' => 'master_form.table_id',
                        'submitted_fields' => array_keys($postData),
                        'system_fields_applied' => $systemFieldsApplied,
                        'insert_result' => 'success',
                        'error' => null,
                    ]);
                    
                    \Yii::info("Insert executed successfully", 'submit_debug');
                    
                    $colNames = array_keys($columns->columns);
                    $orderBy = in_array('id', $colNames) ? 'ORDER BY id DESC' : (in_array('created_at', $colNames) ? 'ORDER BY created_at DESC' : '');
                    if ($orderBy) {
                        $checkRows = $db->createCommand("SELECT * FROM $tableName $orderBy LIMIT 1")->queryAll();
                        \Yii::info("Last row after insert: " . json_encode($checkRows), 'submit_debug');
                    }
                    
                    $successMessage = 'Data berhasil dikirim.';
                    if ($isAjax) {
                        return ['success' => true, 'message' => $successMessage];
                    }
                    Yii::$app->session->setFlash('success', 'Data saved! Fields: ' . implode(', ', array_keys($insertData)));
                    $this->activityLogService->log($model, 'submit', 'success', 'Submission saved to target table.', [
                        'target_table' => $tableName,
                        'fields' => array_keys($insertData),
                    ]);
                } catch (\Exception $e) {
                    $message = 'Save failed: ' . $e->getMessage();
                    FormFlowDebugLogger::logSubmit([
                        'host' => Yii::$app->request->hostInfo,
                        'project_id' => $this->getActiveProjectId(),
                        'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                        'form_id' => (int)$model->id,
                        'target_table_id' => $tableId,
                        'resolved_table_name' => $tableName,
                        'metadata_found' => true,
                        'metadata_source' => 'master_form.table_id',
                        'submitted_fields' => array_keys($postData),
                        'system_fields_applied' => $systemFieldsApplied,
                        'insert_result' => 'error',
                        'error' => $e->getMessage(),
                    ]);
                    if ($isAjax) {
                        return ['success' => false, 'message' => $message];
                    }
                    Yii::$app->session->setFlash('error', $message);
                    $this->activityLogService->log($model, 'submit', 'failed', 'Submission failed: ' . $e->getMessage(), [
                        'target_table' => $tableName,
                    ]);
                }
            } else {
                $postedFieldNames = array_keys($postData);
                $formFieldNames = array_column($fields, 'name');
                $message = 'No data extracted.';
                FormFlowDebugLogger::logSubmit([
                    'host' => Yii::$app->request->hostInfo,
                    'project_id' => $this->getActiveProjectId(),
                    'active_db' => (string)($dbContext['activeDatabase'] ?? Yii::$app->db->dsn),
                    'form_id' => (int)$model->id,
                    'target_table_id' => $tableId,
                    'resolved_table_name' => $tableName,
                    'metadata_found' => true,
                    'metadata_source' => 'master_form.table_id',
                    'submitted_fields' => $postedFieldNames,
                    'system_fields_applied' => $systemFieldsApplied,
                    'insert_result' => 'no_data',
                    'error' => $message,
                ]);
                if ($isAjax) {
                    return ['success' => false, 'message' => $message];
                }
                Yii::$app->session->setFlash('warning', 'No data extracted. POST: ' . implode(', ', $postedFieldNames) . ' | Form fields: ' . implode(', ', $formFieldNames));
                $this->activityLogService->log($model, 'submit', 'warning', 'No submission data extracted.');
            }
            
            if ($isAjax) {
                return ['success' => false, 'message' => 'Submit tidak diproses.'];
            }
            return $this->redirect(['preview', 'id' => $id]);
        }
        
        return $this->redirect(['preview', 'id' => $id]);
    }

    private function canSubmitEmbeddedPageForm(int $formId): bool
    {
        $renderContext = (string)Yii::$app->request->post('render_context', '');
        if ($renderContext !== 'page_content') {
            return true;
        }

        $pageId = (int)Yii::$app->request->post('page_id', Yii::$app->request->get('page_id', 0));
        if ($pageId <= 0) {
            $menuId = (int)Yii::$app->request->post('menu_id', Yii::$app->request->get('menu_id', 0));
            if ($menuId <= 0) {
                $menuId = (int)Yii::$app->session->get('active_menu', 0);
            }
            if ($menuId > 0) {
                $menu = MasterMenu::findOne($menuId);
                if ($menu !== null && !empty($menu->page_id)) {
                    $pageId = (int)$menu->page_id;
                }
            }
        }
        if ($formId <= 0 || $pageId <= 0) {
            return false;
        }

        $projectId = (new ActiveProjectContext())->getActiveProjectId();
        if ($projectId === null) {
            return false;
        }

        $permissionService = new ProjectPermissionService();
        return $permissionService->canUseFormAsPageContent($formId, $pageId, $projectId)
            || $permissionService->canUseLegacyFormAsPageContent($formId, $pageId, $projectId);
    }
    
}
