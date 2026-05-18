<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use app\helpers\FormSystemFieldHelper;

/**
 * @var $this yii\web\View
 * @var $placement app\models\FormPlacement
 * @var $form app\models\MasterForm
 */

$this->title = $placement->meta_title ?: $placement->page_title ?: $form->form_name;
if ($placement->meta_description) {
    $this->registerMetaTag(['name' => 'description', 'content' => $placement->meta_description]);
}

$formData = Json::decode($form->form_data ?? '[]');
$fields = is_array($formData) ? $formData : [];

$layoutTemplate = $placement->layout_template ?? 'default';

// Register custom CSS
if ($placement->custom_css) {
    $cssId = 'form-custom-css';
    $this->registerCss($placement->custom_css, ['type' => 'text/css', 'id' => $cssId]);
}

// Register custom JS
if ($placement->custom_js) {
    $jsId = 'form-custom-js';
    $this->registerJs($placement->custom_js, View::POS_END, $jsId);
}

$layoutTemplates = [
    'default' => 'bg-gray-50 min-h-screen py-8',
    'minimal' => 'bg-white min-h-screen',
    'boxed' => 'bg-gray-100 min-h-screen py-8',
    'centered' => 'bg-gray-50 min-h-screen flex items-center justify-center',
];

$containerClass = $layoutTemplates[$layoutTemplate] ?? $layoutTemplates['default'];
?>
<style>
    .form-renderer {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .form-header {
        padding: 32px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
    }
    
    .form-body {
        padding: 32px;
    }
    
    .form-field {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .form-label.required::after {
        content: ' *';
        color: #ef4444;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: white;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-input::placeholder {
        color: #9ca3af;
    }
    
    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 20px;
        padding-right: 40px;
    }
    
    .form-checkbox-group,
    .form-radio-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .form-checkbox-item,
    .form-radio-item {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    
    .form-checkbox-item input,
    .form-radio-item input {
        width: 18px;
        height: 18px;
        accent-color: #6366f1;
        cursor: pointer;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-form {
        padding: 14px 28px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }
    
    .btn-secondary {
        background: white;
        color: #374151;
        border: 1px solid #e5e7eb;
    }
    
    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .field-preview-iframe {
        width: 100%;
        min-height: 100px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: white;
    }
    
    .alert {
        padding: 16px 20px;
        border-radius: 10px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    
    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    
    @media (max-width: 640px) {
        .form-header,
        .form-body {
            padding: 24px 20px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn-form {
            width: 100%;
        }
    }
</style>

<div class="<?= $containerClass ?>">
    <div class="form-renderer">
        <div class="form-card">
            <?php if ($placement->page_title): ?>
            <div class="form-header">
                <h1 class="text-2xl font-bold mb-2"><?= Html::encode($placement->page_title) ?></h1>
                <?php if ($placement->meta_description): ?>
                <p class="opacity-90"><?= Html::encode($placement->meta_description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="form-body">
                <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="alert alert-success">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <?= Yii::$app->session->getFlash('success') ?>
                </div>
                <?php endif; ?>
                
                <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="alert alert-error">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?= Yii::$app->session->getFlash('error') ?>
                </div>
                <?php endif; ?>
                
                <?= Html::beginForm($placement->route_path, 'POST', ['id' => 'dynamic-form']) ?>
                
                <div id="form-fields-container">
                    <?php foreach ($fields as $index => $field): ?>
                        <?php
                        $type = FormSystemFieldHelper::resolveFieldInputType($field);
                        $label = $field['label'] ?? $field['name'] ?? 'Field ' . ($index + 1);
                        $name = $field['name'] ?? 'field_' . $index;
                        $required = $field['required'] ?? false;
                        $placeholder = $field['placeholder'] ?? '';
                        $defaultValue = $field['default_value'] ?? '';

                        if (FormSystemFieldHelper::isSystemFieldData($field)) {
                            continue;
                        }
                        
                        // Check for custom code
                        $customHtml = $field['customHtml'] ?? null;
                        $customCss = $field['customCss'] ?? null;
                        $customJs = $field['customJs'] ?? null;
                        
                        if ($customHtml || $customCss || $customJs):
                            $fieldId = 'field-' . ($field['id'] ?? $index);
                            $srcDoc = '<!DOCTYPE html><html><head>';
                            if ($customCss) {
                                $srcDoc .= '<style>' . $customCss . '</style>';
                            }
                            $srcDoc .= '</head><body>';
                            $srcDoc .= $customHtml;
                            $srcDoc .= '<script>' . ($customJs ?? '') . '<\/script></body></html>';
                            ?>
                            <div class="form-field" data-field-id="<?= $fieldId ?>">
                                <iframe 
                                    class="field-preview-iframe"
                                    srcdoc="<?= Html::encode($srcDoc) ?>"
                                    sandbox="allow-scripts allow-forms"
                                ></iframe>
                            </div>
                        <?php else: ?>
                            <div class="form-field">
                                <?php if ($type === 'hidden'): ?>
                                    <?= Html::hiddenInput($name, $defaultValue) ?>
                                
                                <?php elseif ($type === 'text' || $type === 'email' || $type === 'password' || $type === 'number' || $type === 'tel' || $type === 'url'): ?>
                                    <?= Html::label($label, $name, ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <?= Html::input($type, $name, $defaultValue, [
                                        'class' => 'form-input',
                                        'placeholder' => $placeholder,
                                        'required' => $required,
                                    ]) ?>
                                
                                <?php elseif ($type === 'textarea'): ?>
                                    <?= Html::label($label, $name, ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <?= Html::textarea($name, $defaultValue, [
                                        'class' => 'form-input form-textarea',
                                        'placeholder' => $placeholder,
                                        'required' => $required,
                                        'rows' => $field['rows'] ?? 4,
                                    ]) ?>
                                
                                <?php elseif ($type === 'select'): ?>
                                    <?= Html::label($label, $name, ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <?= Html::dropDownList($name, $defaultValue, 
                                        array_column($field['options'] ?? [['value' => '', 'label' => 'Pilih...']], 'value', 'label'),
                                        ['class' => 'form-input form-select', 'required' => $required]
                                    ) ?>
                                
                                <?php elseif ($type === 'checkbox'): ?>
                                    <label class="form-checkbox-item">
                                        <?= Html::checkbox($name, $defaultValue, ['class' => 'form-input']) ?>
                                        <span><?= Html::encode($label) ?></span>
                                    </label>
                                
                                <?php elseif ($type === 'checkboxes'): ?>
                                    <?= Html::label($label, '', ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <div class="form-checkbox-group">
                                        <?php foreach ($field['options'] ?? [] as $opt): ?>
                                            <label class="form-checkbox-item">
                                                <?= Html::checkbox($name . '[]', false, ['class' => 'form-input', 'value' => $opt['value'] ?? '']) ?>
                                                <span><?= Html::encode($opt['label'] ?? '') ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <?php elseif ($type === 'radio'): ?>
                                    <?= Html::label($label, '', ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <div class="form-radio-group">
                                        <?php foreach ($field['options'] ?? [] as $opt): ?>
                                            <label class="form-radio-item">
                                                <?= Html::radio($name, false, ['class' => 'form-input', 'value' => $opt['value'] ?? '']) ?>
                                                <span><?= Html::encode($opt['label'] ?? '') ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                
                                <?php elseif ($type === 'date' || $type === 'time' || $type === 'datetime-local'): ?>
                                    <?= Html::label($label, $name, ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <?= Html::input($type, $name, $defaultValue, [
                                        'class' => 'form-input',
                                        'required' => $required,
                                    ]) ?>
                                
                                <?php elseif ($type === 'file'): ?>
                                    <?= Html::label($label, $name, ['class' => 'form-label' . ($required ? ' required' : '')]) ?>
                                    <?= Html::fileInput($name, null, [
                                        'class' => 'form-input',
                                        'required' => $required,
                                    ]) ?>
                                
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-actions">
                    <?= Html::submitButton('Simpan', ['class' => 'btn-form btn-primary']) ?>
                    <?= Html::button('Reset', ['type' => 'reset', 'class' => 'btn-form btn-secondary']) ?>
                </div>
                
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
document.getElementById('dynamic-form')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...';
    }
});
JS;
$this->registerJs($js, View::POS_END);
?>
