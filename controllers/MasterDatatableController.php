<?php

namespace app\controllers;

use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectSchema;
use app\models\DbTable;
use app\models\MasterDatatable;
use app\services\MasterDatatableRenderService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

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
            'models' => MasterDatatable::findScoped()->with('table')->all(),
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
        $editMode = strtolower(trim((string)($post['actions']['edit_mode'] ?? 'custom')));
        if (!in_array($editMode, ['custom', 'default'], true)) {
            $editMode = 'custom';
        }
        $model->actions_config = json_encode([
            'view' => !empty($post['actions']['view']),
            'edit' => !empty($post['actions']['edit']),
            'delete' => !empty($post['actions']['delete']),
            'edit_mode' => $editMode,
        ]);

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

    private function findAvailableTables(): array
    {
        $query = DbTable::find()->with('columns')->orderBy(['label' => SORT_ASC, 'name' => SORT_ASC]);
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        if (ProjectSchema::supportsProjectContext() && $activeProjectId !== null) {
            $query->andWhere(['project_id' => $activeProjectId]);
        }
        if (!(new CommanderAuthContext())->isSuperAdmin() && !Yii::$app->user->isGuest) {
            $query->andWhere(['user_id' => Yii::$app->user->id]);
        }
        return $query->all();
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
