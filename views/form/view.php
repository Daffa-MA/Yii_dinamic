<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var app\models\FormSubmission[] $submissions */

use yii\bootstrap5\Html;

$this->title = 'Form: ' . $model->name;
?>

<div class="form-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-text"></i> <?= Html::encode($model->name) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-copy"></i> Duplicate', ['form/duplicate', 'id' => $model->id], [
                'class' => 'btn btn-info',
                'data' => [
                    'method' => 'post',
                    'confirm' => 'Duplicate this form with all fields?',
                ],
            ]) ?>
            <?= Html::a('<i class="bi bi-pencil"></i> Edit', ['form/update', 'id' => $model->id], [
                'class' => 'btn btn-warning'
            ]) ?>
            <?= Html::a('<i class="bi bi-play-fill"></i> Fill Form', ['form/render', 'id' => $model->id], [
                'class' => 'btn btn-success'
            ]) ?>
            <?= Html::a('<i class="bi bi-arrow-left"></i> Back', ['form/index'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Form Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Form Name</th>
                            <td><?= Html::encode($model->name) ?></td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td><?= date('M d, Y H:i:s', strtotime($model->created_at)) ?></td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td><?= date('M d, Y H:i:s', strtotime($model->updated_at)) ?></td>
                        </tr>
                        <tr>
                            <th>Total Fields</th>
                            <td><span class="badge bg-info"><?= count($model->getSchema()) ?></span></td>
                        </tr>
                        <tr>
                            <th>Total Submissions</th>
                            <td><span class="badge bg-success"><?= count($submissions) ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Form Schema</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($model->getSchema())): ?>
                        <p class="text-muted">No fields defined.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Label</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($model->getSchema() as $index => $field): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= Html::encode($field['label']) ?></td>
                                            <td><code><?= Html::encode($field['name']) ?></code></td>
                                            <td><span class="badge bg-primary"><?= Html::encode($field['type']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-inbox"></i> Submissions</h5>
                    <?= Html::a('<i class="bi bi-list"></i> View All Submissions', ['form/submissions', 'id' => $model->id], [
                        'class' => 'btn btn-sm btn-light'
                    ]) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <p class="text-muted">No submissions yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Submitted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($submissions, 0, 5) as $submission): ?>
                                        <tr>
                                            <td><?= $submission->id ?></td>
                                            <td><?= date('M d, Y H:i:s', strtotime($submission->created_at)) ?></td>
                                            <td>
                                                <?= Html::a('<i class="bi bi-eye"></i> View', ['form/submissions', 'id' => $model->id], [
                                                    'class' => 'btn btn-sm btn-info'
                                                ]) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
