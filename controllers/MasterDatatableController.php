<?php

namespace app\controllers;

use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectSchema;
use app\models\DbTable;
use app\models\MasterForm;
use app\models\MasterDatatable;
use app\services\MasterDatatableRenderService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MasterDatatableController extends Controller
{
    public $layout = 'dashboard';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'delete-row' => ['post'],
                    'approve-row' => ['post'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        (new ActiveDatabaseContext())->resolveAndApply();
        MasterDatatable::ensureStructure();
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'models' => MasterDatatable::findScoped()
                ->select(['id', 'name', 'table_id', 'is_active', 'actions_config'])
                ->with(['table' => static function ($query): void {
                    $query->select(['id', 'name', 'label']);
                }])
                ->orderBy(['id' => SORT_DESC])
                ->all(),
        ]);
    }

    public function actionCreate()
    {
        $model = new MasterDatatable();
        $this->assignContext($model);

        if ($this->saveFromPost($model)) {
            Yii::$app->session->setFlash('success', 'Master Datatable berhasil dibuat.');
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
            'tables' => $this->findAvailableTables(),
            'forms' => $this->findAvailableForms(),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel((int)$id);
        if ($this->saveFromPost($model)) {
            Yii::$app->session->setFlash('success', 'Master Datatable berhasil diperbarui.');
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
            'tables' => $this->findAvailableTables(),
            'forms' => $this->findAvailableForms(),
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel((int)$id)->delete();
        Yii::$app->session->setFlash('success', 'Master Datatable berhasil dihapus.');
        return $this->redirect(['index']);
    }

    public function actionDeleteRow($table_id)
    {
        $rowKey = json_decode((string)Yii::$app->request->post('row_key', '{}'), true);
        $rowKey = is_array($rowKey) ? $rowKey : [];
        $deleted = (new MasterDatatableRenderService())->deleteRow((int)$table_id, $rowKey);
        Yii::$app->session->setFlash($deleted ? 'success' : 'error', $deleted ? 'Data berhasil dihapus.' : 'Data gagal dihapus.');
        return $this->redirect(Yii::$app->request->referrer ?: ['/dashboard']);
    }

    public function actionApproveRow($id)
    {
        $model = $this->findModel((int)$id);
        $rowKey = json_decode((string)Yii::$app->request->post('row_key', '{}'), true);
        $rowKey = is_array($rowKey) ? $rowKey : [];
        $approved = (new MasterDatatableRenderService())->approveRow($model, $rowKey);
        Yii::$app->session->setFlash($approved ? 'success' : 'error', $approved ? 'Data berhasil diproses.' : 'Data gagal diproses.');
        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    public function actionExport($id, $format = 'csv')
    {
        $model = $this->findModel((int)$id);
        return (new MasterDatatableRenderService())->exportPreset($model, (string)$format);
    }

    public function actionReload($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            return (new MasterDatatableRenderService())->renderAjaxByPresetId((int)$id);
        } catch (\Throwable $e) {
            Yii::warning('Failed to reload master datatable: ' . $e->getMessage(), 'master-datatable');
            return [
                'success' => false,
                'message' => 'Datatable gagal dimuat ulang.',
            ];
        }
    }

    private function saveFromPost(MasterDatatable $model): bool
    {
        if (!Yii::$app->request->isPost) {
            return false;
        }

        $post = Yii::$app->request->post('MasterDatatable', []);
        $this->assignContext($model);
        $model->name = trim((string)($post['name'] ?? $model->name));
        $model->table_id = (int)($post['table_id'] ?? $model->table_id);
        $model->search_enabled = !empty($post['search_enabled']) ? 1 : 0;
        $model->pagination_enabled = !empty($post['pagination_enabled']) ? 1 : 0;
        $model->is_active = !empty($post['is_active']) ? 1 : 0;
        $model->columns_config = $this->normalizeColumnsConfig($post['columns'] ?? []);
        $model->filters_config = $this->normalizeListConfig($post['filters'] ?? [], ['field', 'label']);
        $model->stats_config = $this->normalizeListConfig($post['stats'] ?? [], ['field', 'label']);
        $editMode = strtolower(trim((string)($post['actions']['edit_mode'] ?? 'custom')));
        if (!in_array($editMode, ['custom', 'default'], true)) {
            $editMode = 'custom';
        }
        $editFormId = (int)($post['actions']['edit_form_id'] ?? $post['actions']['editFormId'] ?? 0);
        if ($editMode !== 'custom') {
            $editFormId = 0;
        }
        $model->actions_config = json_encode([
            'view' => !empty($post['actions']['view']),
            'edit' => !empty($post['actions']['edit']),
            'delete' => !empty($post['actions']['delete']),
            'edit_mode' => $editMode,
            'edit_form_id' => $editFormId > 0 ? $editFormId : '',
        ]);
        $model->workflow_config = json_encode([
            'approval_enabled' => !empty($post['workflow']['approval_enabled']),
            'status_field' => trim((string)($post['workflow']['status_field'] ?? '')),
            'approved_value' => trim((string)($post['workflow']['approved_value'] ?? 'approved')),
            'pending_value' => trim((string)($post['workflow']['pending_value'] ?? 'pending')),
            'button_label' => trim((string)($post['workflow']['button_label'] ?? 'Approve')) ?: 'Approve',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $model->save();
    }

    private function assignContext(MasterDatatable $model): void
    {
        if ((int)$model->user_id <= 0) {
            $model->user_id = Yii::$app->user->id ?: 1;
        }
        if (ProjectSchema::supportsProjectContext()) {
            $model->project_id = (new ActiveProjectContext())->getActiveProjectId();
        }
    }

    private function normalizeColumnsConfig($columns): string
    {
        $result = [];
        if (is_array($columns)) {
            foreach ($columns as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $field = trim((string)($item['field'] ?? ''));
                if ($field === '') {
                    continue;
                }
                $result[] = [
                    'field' => $field,
                    'label' => trim((string)($item['label'] ?? $field)),
                    'visible' => !empty($item['visible']),
                ];
            }
        }
        return json_encode($result);
    }

    private function normalizeListConfig($items, array $requiredKeys): string
    {
        $result = [];
        if (!is_array($items)) {
            return '[]';
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (empty($item['enabled']) && empty($item['visible'])) {
                continue;
            }

            $normalized = [];
            foreach ($requiredKeys as $key) {
                $normalized[$key] = trim((string)($item[$key] ?? ''));
            }
            if (($normalized['field'] ?? '') === '') {
                continue;
            }
            if (($normalized['label'] ?? '') === '') {
                $normalized['label'] = $normalized['field'];
            }
            $result[] = $normalized;
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function findAvailableTables(): array
    {
        $tableSelect = ['id', 'name', 'label', 'user_id'];
        $dbTableSchema = DbTable::getTableSchema();
        if ($dbTableSchema !== null && isset($dbTableSchema->columns['project_id'])) {
            $tableSelect[] = 'project_id';
        }

        $query = DbTable::find()
            ->select($tableSelect)
            ->with(['columns' => static function ($query): void {
                $query->select(['id', 'table_id', 'name', 'label', 'sort_order'])
                    ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
            }])
            ->orderBy(['label' => SORT_ASC, 'name' => SORT_ASC]);
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $query->andWhere(['project_id' => $activeProjectId]);
        }
        if (!(new CommanderAuthContext())->isSuperAdmin() && !Yii::$app->user->isGuest) {
            $query->andWhere(['user_id' => Yii::$app->user->id]);
        }
        return $query->all();
    }

    private function findAvailableForms(): array
    {
        $formSelect = ['id', 'form_name', 'user_id'];
        $formSchema = MasterForm::getTableSchema();
        foreach (['name', 'project_id'] as $column) {
            if ($formSchema !== null && isset($formSchema->columns[$column])) {
                $formSelect[] = $column;
            }
        }

        $query = MasterForm::findScoped()
            ->select($formSelect)
            ->orderBy(['form_name' => SORT_ASC, 'id' => SORT_ASC]);
        if (!(new CommanderAuthContext())->isSuperAdmin() && !Yii::$app->user->isGuest) {
            $query->andWhere(['user_id' => Yii::$app->user->id]);
        }

        $items = [];
        foreach ($query->all() as $form) {
            $items[] = [
                'id' => (int)$form->id,
                'name' => (string)($form->form_name ?: $form->name ?: ('Form #' . $form->id)),
            ];
        }

        return $items;
    }

    private function findModel(int $id): MasterDatatable
    {
        $model = MasterDatatable::findScoped()->andWhere(['id' => $id])->one();
        if ($model instanceof MasterDatatable) {
            return $model;
        }
        throw new NotFoundHttpException('Master Datatable tidak ditemukan.');
    }
}
