<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\services\CardConfigService;
use app\services\AggregateRegistry;
use app\services\DatasourceRegistry;
use app\services\FilterRegistry;
use app\services\FormatterRegistry;
use app\services\IconRegistry;
use app\services\RefreshRegistry;
use app\services\WidgetRegistry;
use app\services\IconDataService;

class CardController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        $dbContext = new \app\components\ActiveDatabaseContext();
        $dbContext->resolveAndApply();
        return parent::beforeAction($action);
    }

    public function actionGetConfig()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $service = new CardConfigService();
        return [
            'success' => true,
            'data' => $service->getBuilderConfig(),
        ];
    }

    public function actionGetTables()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $service = new CardConfigService();
        return [
            'success' => true,
            'data' => $service->getAvailableTables(),
        ];
    }

    public function actionGetColumns($tableId = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $tableId = $tableId ?: Yii::$app->request->get('tableId');
        if (!$tableId) {
            return ['success' => false, 'data' => []];
        }

        $service = new CardConfigService();
        return [
            'success' => true,
            'data' => $service->getTableColumns($tableId),
        ];
    }

    public function actionPreview()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $body = json_decode(Yii::$app->request->getRawBody(), true);
        $config = $body['config'] ?? Yii::$app->request->post('config');
        if (!$config) {
            return ['success' => false, 'data' => null];
        }

        $service = new CardConfigService();
        return [
            'success' => true,
            'data' => $service->getCardPreviewData($config),
        ];
    }

    public function actionSearchIcons($query = '', $library = 'heroicons')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = $query ?: Yii::$app->request->get('query', '');
        $library = $library ?: Yii::$app->request->get('library', 'heroicons');

        $icons = $this->searchIconsFromLibrary($query, $library);

        return [
            'success' => true,
            'data' => $icons,
        ];
    }

    private function searchIconsFromLibrary($query, $library)
    {
        $service = new IconDataService();
        return $service->searchIcons($query, $library, 9999);
    }

    public function actionGetRegistries()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'success' => true,
            'data' => [
                'widgets' => array_keys(WidgetRegistry::getInstance()->getAll()),
                'datasources' => DatasourceRegistry::getInstance()->getOptions(),
                'aggregates' => AggregateRegistry::getInstance()->getOptions(),
                'filters' => FilterRegistry::getInstance()->getOperatorOptions(),
                'formatters' => FormatterRegistry::getInstance()->getOptions(),
                'refresh' => RefreshRegistry::getInstance()->getOptions(),
                'iconLibraries' => IconRegistry::getInstance()->getLibraryOptions(),
            ],
        ];
    }
}
