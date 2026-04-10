<?php

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */

use yii\bootstrap5\Html;

$this->title = 'My Forms';
?>

<div class="form-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-file-earmark-text"></i> My Forms</h1>
        <?= Html::a('<i class="bi bi-plus-circle"></i> Create New Form', ['form/create'], [
            'class' => 'btn btn-success btn-lg'
        ]) ?>
    </div>

    <!-- Search Bar -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <?= Html::beginForm(['form/index'], 'get', ['class' => 'd-flex']) ?>
            <div class="input-group">
                <?= Html::input('text', 'q', $search ?? null, [
                    'class' => 'form-control',
                    'placeholder' => 'Search forms by name...',
                ]) ?>
                <?= Html::submitButton('<i class="bi bi-search"></i> Search', [
                    'class' => 'btn btn-primary'
                ]) ?>
                <?php if ($search): ?>
                    <?= Html::a('<i class="bi bi-x-circle"></i> Clear', ['form/index'], [
                        'class' => 'btn btn-outline-secondary'
                    ]) ?>
                <?php endif; ?>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>

    <?php if (empty($forms)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3 class="empty-state-title">Belum ada form</h3>
            <p class="empty-state-text">Mulai buat form pertama Anda sekarang.</p>
            <?= Html::a('<i class="bi bi-plus-circle"></i> Buat Form Pertama', ['form/create'], [
                'class' => 'btn btn-success btn-lg'
            ]) ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover shadow">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Form Name</th>
                        <th>Fields</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <tr>
                            <td><?= $form->id ?></td>
                            <td><?= Html::encode($form->name) ?></td>
                            <td><span class="badge bg-info"><?= count($form->getSchema()) ?></span></td>
                            <td><?= date('M d, Y H:i', strtotime($form->created_at)) ?></td>
                            <td>
                                <?= Html::a('<i class="bi bi-eye"></i> View', ['form/view', 'id' => $form->id], [
                                    'class' => 'btn btn-sm btn-primary'
                                ]) ?>
                                <?= Html::a('<i class="bi bi-pencil"></i> Edit', ['form/update', 'id' => $form->id], [
                                    'class' => 'btn btn-sm btn-warning'
                                ]) ?>
                                <?= Html::a('<i class="bi bi-copy"></i> Copy', ['form/duplicate', 'id' => $form->id], [
                                    'class' => 'btn btn-sm btn-info',
                                    'data' => [
                                        'method' => 'post',
                                        'confirm' => 'Duplicate this form?',
                                    ],
                                ]) ?>
                                <?= Html::a('<i class="bi bi-play-fill"></i> Fill', ['form/render', 'id' => $form->id], [
                                    'class' => 'btn btn-sm btn-success'
                                ]) ?>
                                <?= Html::a('<i class="bi bi-trash"></i>', ['form/delete', 'id' => $form->id], [
                                    'class' => 'btn btn-sm btn-danger',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to delete this form?',
                                        'method' => 'post',
                                    ],
                                ]) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
