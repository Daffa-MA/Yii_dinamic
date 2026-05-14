<?php

use yii\helpers\Html;

/* @var $model app\models\MasterForm */

$projectId = $model->hasAttribute('project_id') ? (int)$model->project_id : null;
$databaseContext = (string)($model->database_context ?? '-');
?>
<div class="view-card" style="margin-bottom:24px;">
    <div class="view-card-header">
        <div class="view-card-icon"><span class="material-symbols-outlined">hub</span></div>
        <div class="view-card-title">Runtime Context</div>
    </div>
    <div class="view-card-body">
        <div class="view-info-row">
            <span class="view-info-label">Database Context</span>
            <span class="view-type-badge"><?= Html::encode($databaseContext) ?></span>
        </div>
        <div class="view-info-row">
            <span class="view-info-label">Workspace / Project</span>
            <span class="view-type-badge"><?= $projectId > 0 ? 'Project #' . $projectId : 'No Project' ?></span>
        </div>
    </div>
</div>

