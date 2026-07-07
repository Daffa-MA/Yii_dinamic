<?php

namespace app\controllers;

use app\components\ActiveDatabaseContext;
use app\components\ActiveProjectContext;
use app\services\MasterChartService;
use app\models\MasterPage;
use app\models\MasterPageChart;
use app\models\DbTable;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class MasterChartController extends Controller
{
    private $chartService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->chartService = new MasterChartService();
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['data', 'tables', 'fields', 'chart-data', 'quick-create'], true)) {
            $this->enableCsrfValidation = false;
        }

        $dbContext = new ActiveDatabaseContext();
        $result = $dbContext->resolveAndApply();
        if (!empty($result['isSwitched'])) {
            Yii::$app->db->schema->refresh();
        }
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['data'],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'create', 'update', 'delete', 'tables', 'fields', 'quick-create'],
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex($page_id)
    {
        $page = MasterPage::findOne($page_id);
        if (!$page) throw new NotFoundHttpException('Page not found.');

        $charts = MasterPageChart::find()
            ->where(['page_id' => $page_id])
            ->orderBy(['position' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'page' => $page,
            'charts' => $charts,
        ]);
    }

    public function actionData(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $params = Yii::$app->request->get();
        $chartFilters = [];
        foreach ($params as $key => $value) {
            if (strpos($key, 'dt_') === 0 || $key === 'search' || $key === 'filter') {
                $chartFilters[$key] = $value;
            }
        }

        return $this->chartService->buildChartData($id, $chartFilters);
    }

    public function actionCreate($page_id = null)
    {
        if ($page_id) {
            $page = MasterPage::findOne($page_id);
            if (!$page) throw new NotFoundHttpException('Page not found.');
            $model = new MasterPageChart(['page_id' => $page_id]);
        } else {
            $page = null;
            $model = new MasterPageChart();
        }

        $tables = $this->getTableList();
        $pages = MasterPage::find()->select(['id', 'name'])->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();

        if ($model->load(Yii::$app->request->post())) {
            if (!$model->page_id) {
                $model->addError('page_id', 'Pilih halaman terlebih dahulu.');
            } else {
                $this->processJsonFields($model);
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Chart berhasil ditambahkan.');
                    return $this->redirect(['update', 'id' => $model->id]);
                }
            }
        }

        return $this->render('form', [
            'model' => $model,
            'page' => $page,
            'pages' => $pages,
            'tables' => $tables,
            'isNew' => true,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = MasterPageChart::findOne($id);
        if (!$model) throw new NotFoundHttpException('Chart not found.');

        $page = $model->page;
        $tables = $this->getTableList();

        if ($model->load(Yii::$app->request->post())) {
            $this->processJsonFields($model);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Chart berhasil diupdate.');
                return $this->redirect(['update', 'id' => $model->id]);
            }
        }

        return $this->render('form', [
            'model' => $model,
            'page' => $page,
            'tables' => $tables,
            'isNew' => false,
        ]);
    }

    public function actionDelete($id)
    {
        $model = MasterPageChart::findOne($id);
        if (!$model) throw new NotFoundHttpException('Chart not found.');

        $pageId = $model->page_id;
        $model->delete();

        Yii::$app->session->setFlash('success', 'Chart berhasil dihapus.');
        return $this->redirect(['index', 'page_id' => $pageId]);
    }

    public function actionTables()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $result = $this->getTableList();
        Yii::error('actionTables returning: ' . json_encode($result), 'chart');
        return $result;
    }

    public function actionFields($table_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $table = DbTable::findOne($table_id);
        if (!$table) return [];

        $schema = Yii::$app->db->getTableSchema($table->name, true);
        if (!$schema) return [];

        $fields = [];
        foreach ($schema->columns as $col) {
            $fields[] = [
                'name' => $col->name,
                'type' => $col->type,
                'is_numeric' => in_array($col->type, ['integer', 'decimal', 'float', 'double', 'bigint', 'smallint']),
                'is_string' => in_array($col->type, ['string', 'text']),
                'is_date' => in_array($col->type, ['date', 'datetime', 'timestamp']),
            ];
        }
        return $fields;
    }

    public function actionQuickCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $post = Yii::$app->request->post();
            $pageId = $post['page_id'] ?? null;

            if (!$pageId) {
                return ['success' => false, 'errors' => ['page_id' => ['Halaman tidak ditemukan. Simpan halaman terlebih dahulu.']]];
            }

            $model = new MasterPageChart();
            $model->page_id = (int)$pageId;
            $model->title = $post['title'] ?? 'Untitled Chart';
            $model->chart_type = $post['chart_type'] ?? 'bar';
            $model->table_id = $post['table_id'] ?? null;
            $model->label_field = $post['label_field'] ?? '';
            $model->value_field = $post['value_field'] ?? '';
            $model->aggregation = $post['aggregation'] ?? 'count';
            $model->group_by_field = $post['group_by_field'] ?? '';
            $model->is_active = 1;
            $model->position = 0;

            if ($model->save()) {
                return [
                    'success' => true,
                    'chart' => [
                        'id' => (int)$model->id,
                        'page_id' => (int)$model->page_id,
                        'title' => $model->title,
                        'chart_type' => $model->chart_type,
                        'table_id' => $model->table_id ? (int)$model->table_id : null,
                    ],
                ];
            }

            return ['success' => false, 'errors' => $model->getErrors()];
        } catch (\Throwable $e) {
            Yii::error('quick-create error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
            return ['success' => false, 'message' => 'Gagal membuat chart: ' . $e->getMessage()];
        }
    }

    private function getTableList(): array
    {
        $activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
        $result = [];

        $metaDb = Yii::$app->get('metadataDb', false);
        if (!$metaDb) {
            $metaDb = Yii::$app->db;
        }

        try {
            $rows = $metaDb->createCommand()
                ->select(['id', 'name', 'label', 'project_id'])
                ->from('db_tables')
                ->orderBy(['name' => SORT_ASC])
                ->all();

            foreach ($rows as $row) {
                if ($activeProjectId && (int)$row['project_id'] !== $activeProjectId) {
                    continue;
                }
                $label = !empty($row['label']) ? $row['label'] : $row['name'];
                $result[(int)$row['id']] = $label;
            }
        } catch (\Throwable $e) {
            Yii::error('getTableList error: ' . $e->getMessage(), __METHOD__);
        }

        return $result;
    }

    private function processJsonFields(MasterPageChart $model): void
    {
        foreach (['series_config', 'filter_config', 'extra_config'] as $field) {
            $value = $model->$field;
            if (is_array($value)) {
                $model->$field = json_encode($value);
            } elseif (is_string($value) && !empty($value)) {
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $model->$field = null;
                }
            }
        }
    }
}
