<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var array $schema */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Fill Form: ' . $model->name;
?>

<div class="form-render">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="bi bi-file-earmark-text"></i> <?= Html::encode($model->name) ?></h3>
                </div>
                <div class="card-body p-4">
                    <?php if (Yii::$app->session->hasFlash('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (Yii::$app->session->hasFlash('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> <?= Yii::$app->session->getFlash('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($schema)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">⚠️</div>
                            <h3 class="empty-state-title">Form belum siap</h3>
                            <p class="empty-state-text">Form ini belum memiliki field. Silakan hubungi pembuat form.</p>
                        </div>
                    <?php else: ?>
                        <?php $form = ActiveForm::begin([
                            'action' => ['form/submit', 'id' => $model->id],
                            'method' => 'post',
                        ]); ?>

                        <?php foreach ($schema as $field): ?>
                            <div class="mb-3">
                                <?= Html::label($field['label'], $field['name'], ['class' => 'form-label']) ?>
                                
                                <?php if ($field['type'] === 'text'): ?>
                                    <?= Html::input('text', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => $field['label'],
                                        'required' => true
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'number'): ?>
                                    <?= Html::input('number', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => $field['label'],
                                        'required' => true
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'email'): ?>
                                    <?= Html::input('email', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => 'email@example.com',
                                        'required' => true
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'textarea'): ?>
                                    <?= Html::textarea($field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => $field['label'],
                                        'rows' => 4,
                                        'required' => true
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'date'): ?>
                                    <?= Html::input('date', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'required' => true
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'select'): ?>
                                    <?= Html::dropDownList($field['name'], null, [
                                        '' => '-- Select --',
                                        'Option 1' => 'Option 1',
                                        'Option 2' => 'Option 2',
                                        'Option 3' => 'Option 3',
                                    ], [
                                        'id' => $field['name'],
                                        'class' => 'form-select',
                                        'required' => true
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <div class="form-check">
                                        <?= Html::checkbox($field['name'], false, [
                                            'id' => $field['name'],
                                            'class' => 'form-check-input'
                                        ]) ?>
                                        <?= Html::label($field['label'], $field['name'], ['class' => 'form-check-label']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-group mt-4">
                            <?= Html::submitButton('<i class="bi bi-send"></i> Submit', [
                                'class' => 'btn btn-success btn-lg'
                            ]) ?>
                            <?= Html::a('<i class="bi bi-arrow-left"></i> Back', ['form/view', 'id' => $model->id], [
                                'class' => 'btn btn-secondary btn-lg'
                            ]) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
