<?php

namespace app\commands;

use app\services\DynamicModuleBlueprintService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class DynamicModuleController extends Controller
{
    public $userId;
    public $projectId;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['userId', 'projectId']);
    }

    public function actionApply(string $file): int
    {
        $path = Yii::getAlias($file);
        if (!is_file($path)) {
            $this->stderr("Blueprint file not found: {$file}\n");
            return ExitCode::DATAERR;
        }

        $blueprint = require $path;
        if (!is_array($blueprint)) {
            $this->stderr("Blueprint must return an array.\n");
            return ExitCode::DATAERR;
        }

        $result = (new DynamicModuleBlueprintService())->apply(
            $blueprint,
            $this->userId !== null ? (int)$this->userId : null,
            $this->projectId !== null ? (int)$this->projectId : null
        );
        $this->stdout("Dynamic module applied.\n");
        $this->stdout(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        return ExitCode::OK;
    }
}
