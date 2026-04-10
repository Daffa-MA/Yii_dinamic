<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var yii\data\Pagination $pages */
/** @var app\models\FormSubmission[] $submissions */

use yii\bootstrap5\Html;
use yii\widgets\LinkPager;

$this->title = 'Submissions: ' . $model->name;
?>

<div class="form-submissions">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-inbox"></i> Submissions for <?= Html::encode($model->name) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-download"></i> Export CSV', ['form/export', 'id' => $model->id], [
                'class' => 'btn btn-success'
            ]) ?>
            <?= Html::a('<i class="bi bi-arrow-left"></i> Back', ['form/view', 'id' => $model->id], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No submissions yet for this form.
        </div>
    <?php else: ?>
        <?php foreach ($submissions as $submission): ?>
            <div class="card shadow mb-3 submission-item">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><i class="bi bi-calendar"></i> Submitted: <?= date('M d, Y H:i:s', strtotime($submission->created_at)) ?></strong>
                        <span class="badge bg-primary">Submission #<?= $submission->id ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <?php 
                                $data = $submission->getData();
                                $schema = $model->getSchema();
                                foreach ($schema as $field): 
                                ?>
                                    <tr>
                                        <th width="200"><?= Html::encode($field['label']) ?></th>
                                        <td><?= Html::encode($data[$field['name']] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="text-center mt-4">
            <?= LinkPager::widget([
                'pagination' => $pages,
                'options' => ['class' => 'pagination justify-content-center'],
            ]) ?>
        </div>
        
        <div class="text-center text-muted mt-2">
            Showing <?= count($submissions) ?> of <?= $pages->totalCount ?> submissions
        </div>
    <?php endif; ?>
</div>
