<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var array $schema */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Fill Form: ' . $model->name;

// Register FontAwesome
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
?>

<style>
    body {
        background: linear-gradient(135deg, #f0f4ff 0%, #fff5e6 100%) !important;
        min-height: 100vh;
    }
    
    .form-render {
        padding: 40px 20px;
    }
    
    .form-render .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        animation: slideInUp 0.5s ease-out;
    }
    
    .form-render .card-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        padding: 32px;
        border: none;
    }
    
    .form-render .card-header h3 {
        font-weight: 700;
        font-size: 28px;
        color: white;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .form-render .card-body {
        padding: 40px;
        background: white;
    }
    
    .form-field-group {
        margin-bottom: 28px;
        animation: slideInUp 0.4s ease-out;
    }
    
    .form-field-group:hover {
        transform: translateY(-2px);
    }
    
    .form-label {
        font-weight: 600;
        color: #1f2937;
        font-size: 15px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .form-control, .form-select {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 15px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fafbfc;
    }
    
    .form-control:hover, .form-select:hover {
        border-color: #d1d5db;
        background: white;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        background: white;
        outline: none;
    }
    
    .form-check {
        margin-bottom: 12px;
    }
    
    .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .form-check-input:checked {
        background-color: #2563eb;
        border-color: #2563eb;
    }
    
    .form-check-label {
        margin-left: 8px;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
    }
    
    .form-actions {
        margin-top: 40px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 14px 32px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
    
    .btn-back {
        background: white;
        color: #2563eb;
        border: 2px solid #2563eb;
        border-radius: 10px;
        padding: 12px 28px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-back:hover {
        background: #dbeafe;
        transform: translateY(-2px);
    }
    
    .alert {
        border: none;
        border-radius: 10px;
        border-left: 4px solid transparent;
        margin-bottom: 20px;
        animation: slideInUp 0.3s ease-out;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border-left-color: #10b981;
    }
    
    .alert-danger {
        background: #fee2e2;
        color: #7f1d1d;
        border-left-color: #ef4444;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="form-render">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-8">
            <div class="card">
                <!-- Header with Gradient -->
                <div class="card-header">
                    <h3>
                        <i class="fas fa-file-alt"></i> 
                        <?= Html::encode($model->name) ?>
                    </h3>
                </div>
                
                <!-- Form Body -->
                <div class="card-body">
                    <!-- Alerts -->
                    <?php if (Yii::$app->session->hasFlash('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Success!</strong> <?= Yii::$app->session->getFlash('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (Yii::$app->session->hasFlash('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> 
                            <strong>Error!</strong> <?= Yii::$app->session->getFlash('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($schema)): ?>
                        <!-- Empty State -->
                        <div style="text-align: center; padding: 60px 20px;">
                            <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
                            <h3 style="font-size: 22px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">Form Not Ready</h3>
                            <p style="color: #6b7280; margin-bottom: 24px;">This form doesn't have any fields yet. Please contact the form creator.</p>
                            <?= Html::a('<i class="fas fa-arrow-left"></i> Go Back', ['form/index'], [
                                'class' => 'btn btn-outline-primary',
                                'style' => 'border-radius: 8px; padding: 10px 24px;'
                            ]) ?>
                        </div>
                    <?php else: ?>
                        <!-- Form -->
                        <?php $form = ActiveForm::begin([
                            'action' => ['form/submit', 'id' => $model->id],
                            'method' => 'post',
                            'id' => 'dynamic-form',
                            'options' => ['class' => '']
                        ]); ?>

                        <?php foreach ($schema as $index => $field): 
                            $delay = $index * 0.05;
                        ?>
                            <div class="form-field-group" style="animation-delay: ${delay}s;">
                                <?php if ($field['type'] === 'text'): ?>
                                    <?= Html::label($field['label'] . (isset($field['required']) && $field['required'] ? ' <span style="color: #ef4444;">*</span>' : ''), $field['name'], ['class' => 'form-label', 'encode' => false]) ?>
                                    <?= Html::input('text', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => $field['label'],
                                        'required' => isset($field['required']) && $field['required']
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'number'): ?>
                                    <?= Html::label($field['label'] . (isset($field['required']) && $field['required'] ? ' <span style="color: #ef4444;">*</span>' : ''), $field['name'], ['class' => 'form-label', 'encode' => false]) ?>
                                    <?= Html::input('number', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => $field['label'],
                                        'required' => isset($field['required']) && $field['required']
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'email'): ?>
                                    <?= Html::label($field['label'] . (isset($field['required']) && $field['required'] ? ' <span style="color: #ef4444;">*</span>' : ''), $field['name'], ['class' => 'form-label', 'encode' => false]) ?>
                                    <?= Html::input('email', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => 'email@example.com',
                                        'required' => isset($field['required']) && $field['required']
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'textarea'): ?>
                                    <?= Html::label($field['label'] . (isset($field['required']) && $field['required'] ? ' <span style="color: #ef4444;">*</span>' : ''), $field['name'], ['class' => 'form-label', 'encode' => false]) ?>
                                    <?= Html::textarea($field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'placeholder' => $field['label'],
                                        'rows' => 4,
                                        'required' => isset($field['required']) && $field['required']
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'date'): ?>
                                    <?= Html::label($field['label'] . (isset($field['required']) && $field['required'] ? ' <span style="color: #ef4444;">*</span>' : ''), $field['name'], ['class' => 'form-label', 'encode' => false]) ?>
                                    <?= Html::input('date', $field['name'], null, [
                                        'id' => $field['name'],
                                        'class' => 'form-control',
                                        'required' => isset($field['required']) && $field['required']
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'select'): ?>
                                    <?= Html::label($field['label'] . (isset($field['required']) && $field['required'] ? ' <span style="color: #ef4444;">*</span>' : ''), $field['name'], ['class' => 'form-label', 'encode' => false]) ?>
                                    <?= Html::dropDownList($field['name'], null, [
                                        '' => '-- Select ' . $field['label'] . ' --',
                                        'Option 1' => 'Option 1',
                                        'Option 2' => 'Option 2',
                                        'Option 3' => 'Option 3',
                                    ], [
                                        'id' => $field['name'],
                                        'class' => 'form-select',
                                        'required' => isset($field['required']) && $field['required']
                                    ]) ?>
                                    
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <div class="form-check">
                                        <?= Html::checkbox($field['name'], false, [
                                            'id' => $field['name'],
                                            'class' => 'form-check-input'
                                        ]) ?>
                                        <?= Html::label($field['label'], $field['name'], ['class' => 'form-check-label']) ?>
                                    </div>
                                    
                                <?php elseif ($field['type'] === 'radio'): ?>
                                    <?= Html::label($field['label'], $field['name'], ['class' => 'form-label']) ?>
                                    <div class="form-check">
                                        <?= Html::radio($field['name'], false, ['value' => 'yes', 'id' => $field['name'] . '_yes', 'class' => 'form-check-input']) ?>
                                        <?= Html::label('Yes', $field['name'] . '_yes', ['class' => 'form-check-label']) ?>
                                    </div>
                                    <div class="form-check">
                                        <?= Html::radio($field['name'], false, ['value' => 'no', 'id' => $field['name'] . '_no', 'class' => 'form-check-input']) ?>
                                        <?= Html::label('No', $field['name'] . '_no', ['class' => 'form-check-label']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <?= Html::submitButton('<i class="fas fa-paper-plane"></i> Submit Form', [
                                'class' => 'btn-submit'
                            ]) ?>
                            <?= Html::a('<i class="fas fa-arrow-left"></i> Back to Forms', ['form/index'], [
                                'class' => 'btn-back'
                            ]) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

