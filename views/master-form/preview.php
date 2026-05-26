<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use app\models\DbTable;
use app\helpers\FormSystemFieldHelper;
use app\services\FormRenderService;

/* @var $this yii\web\View */
/* @var $model app\models\MasterForm */
/* @var $renderPayload array|null */

$renderPayload = $renderPayload ?? null;
$fields = is_array($renderPayload['fields'] ?? null) ? $renderPayload['fields'] : [];
if (empty($fields)) {
    $formData = $model->form_data ?? [];
    if (is_string($formData)) {
        $formData = json_decode($formData, true) ?? [];
    }
    $fields = is_array($formData) ? $formData : [];
}
$rawPreviewFields = array_values(array_filter($fields, static fn($field) => is_array($field)));
$previewFieldDebug = [];
$fields = [];
foreach (FormSystemFieldHelper::filterFields($rawPreviewFields) as $index => $field) {
    $normalized = FormRenderService::normalizeFieldForRender($field, (int)$index);
    $relationConfig = $normalized['relation_config'] ?? null;
    if (is_string($relationConfig)) {
        $relationConfig = json_decode($relationConfig, true);
    }
    $previewFieldDebug[] = [
        'field_name' => $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? null,
        'source_column_id' => $field['source_column_id'] ?? null,
        'is_system_field' => FormSystemFieldHelper::isSystemFieldData($field),
        'is_excluded' => !empty($field['excluded']),
        'is_fk' => FormRenderService::isRelationField($normalized),
        'relation_config' => $relationConfig,
        'referenced_table' => $normalized['fk_referenced_table'] ?? null,
        'referenced_value_column' => $normalized['fk_referenced_column'] ?? $normalized['value_column'] ?? null,
        'display_column' => $normalized['fk_display_column'] ?? $normalized['display_column'] ?? null,
    ];
    if (FormRenderService::isRelationField($normalized) && FormRenderService::looksLikeFallbackFieldName($normalized['name'] ?? '')) {
        $resolvedName = FormRenderService::resolveFkNameFromField($normalized);
        if ($resolvedName !== null && $resolvedName !== '' && !FormRenderService::looksLikeFallbackFieldName($resolvedName)) {
            $normalized['name'] = $resolvedName;
            $normalized['field_name'] = $resolvedName;
            $normalized['field_key'] = $resolvedName;
            $normalized['column_name'] = $resolvedName;
            $normalized['resolved_name'] = $resolvedName;
            $normalized['resolved_column_name'] = $resolvedName;
        }
    }
    $fields[] = FormRenderService::resolveDynamicChoiceOptions($normalized);
}
Yii::info([
    'form_id' => (int)$model->id,
    'target_table' => $model->table_id ?? null,
    'raw_fields_count' => count($rawPreviewFields),
    'preview_field_debug' => $previewFieldDebug,
    'normalized_fields' => array_map(static function (array $field): array {
        return [
            'name' => $field['name'] ?? null,
            'column_name' => $field['column_name'] ?? null,
            'label' => $field['label'] ?? null,
            'type' => $field['type'] ?? null,
            'inputType' => $field['inputType'] ?? null,
            'relation_config' => $field['relation_config'] ?? null,
            'is_foreign_key' => !empty($field['is_foreign_key']),
            'options_count' => is_array($field['options'] ?? null) ? count($field['options']) : 0,
        ];
    }, $fields),
], 'form-render-fields');
$formName = $model->form_name ?? 'Form';
$formRenderService = new FormRenderService();
$hasCustomCode = $formRenderService->hasCustomCodePayload($renderPayload, $model);
$customHtml = trim((string)($renderPayload['customHtml'] ?? ''));
$customCss = trim((string)($renderPayload['customCss'] ?? ''));
$customJs = trim((string)($renderPayload['customJs'] ?? ''));
$shouldRenderCustom = $hasCustomCode;

if ($hasCustomCode) {
    echo $formRenderService->renderCustomCodeOnly($renderPayload);
    return;
}

$tableName = null;
if ($model->table_id) {
    $dbTable = DbTable::findOne($model->table_id);
    $tableName = $dbTable ? $dbTable->name : null;
}

$this->title = 'Preview: ' . $formName;
?>

<style>
.preview-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
    min-height: 100vh;
    background: #f8fafc;
}

.preview-header {
    text-align: center;
    margin-bottom: 32px;
}

.preview-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #fef3c7;
    color: #92400e;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 16px;
}

.preview-badge.target-table {
    background: #f0fdf4;
    color: #16a34a;
}

.preview-badge .material-symbols-outlined {
    font-size: 14px;
}

.preview-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 8px 0;
}

.preview-subtitle {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.preview-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.preview-card-header {
    padding: 24px 32px;
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
    color: white;
}

.preview-card-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.preview-card-body {
    padding: 32px;
}

.preview-field {
    margin-bottom: 24px;
}

.preview-field:last-child {
    margin-bottom: 0;
}

.preview-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 14px;
}

.preview-label.required::after {
    content: ' *';
    color: #dc2626;
}

.preview-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: white;
    color: #374151;
    box-sizing: border-box;
}

.preview-input:focus {
    outline: none;
    border-color: #64748b;
    box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.1);
}

.preview-input::placeholder {
    color: #9ca3af;
}

.preview-textarea {
    min-height: 120px;
    resize: vertical;
}

.preview-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 20px;
    padding-right: 40px;
}

.preview-checkbox-group,
.preview-radio-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.preview-checkbox-item,
.preview-radio-item {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.preview-checkbox-item input,
.preview-radio-item input {
    width: 18px;
    height: 18px;
    accent-color: #475569;
    cursor: pointer;
}

.preview-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.preview-btn {
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
    border: none;
}

.preview-btn-primary {
    background: #0f172a;
    color: white;
}

.preview-btn-primary:hover {
    background: #1e293b;
}

.preview-btn-secondary {
    background: white;
    color: #475569;
    border: 1px solid #e5e7eb;
}

.preview-btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.preview-fk-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #64748b;
    margin-top: 6px;
}

.preview-excluded {
    padding: 16px 20px;
    background: #f1f5f9;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
    text-align: center;
    color: #64748b;
    font-size: 13px;
}

.preview-empty {
    text-align: center;
    padding: 48px 24px;
    color: #64748b;
}

.preview-empty .material-symbols-outlined {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.preview-alert {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-alert.success {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.preview-alert.error {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.preview-alert.warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.preview-alert .material-symbols-outlined {
    font-size: 20px;
}

@media (max-width: 640px) {
    .preview-card-body {
        padding: 24px 20px;
    }
    
    .preview-actions {
        flex-direction: column;
    }
    
    .preview-btn {
        width: 100%;
    }
}
</style>

<div class="preview-page">
    <div class="preview-header">
        <div class="preview-badge">
            <span class="material-symbols-outlined">visibility</span>
            Preview Mode
        </div>
        <?php if ($tableName): ?>
        <div class="preview-badge target-table">
            <span class="material-symbols-outlined">storage</span>
            Target: <?= Html::encode($tableName) ?>
        </div>
        <?php endif; ?>
        <h1 class="preview-title"><?= Html::encode($formName) ?></h1>
        <p class="preview-subtitle"><?= $tableName ? 'This form will save data to table: ' . Html::encode($tableName) : 'Preview mode - no database target configured' ?></p>
    </div>
    
    <?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="preview-alert success">
        <span class="material-symbols-outlined">check_circle</span>
        <?= Yii::$app->session->getFlash('success') ?>
    </div>
    <?php endif; ?>
    
    <?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="preview-alert error">
        <span class="material-symbols-outlined">error</span>
        <?= Yii::$app->session->getFlash('error') ?>
    </div>
    <?php endif; ?>
    
    <?php if (Yii::$app->session->hasFlash('warning')): ?>
    <div class="preview-alert warning">
        <span class="material-symbols-outlined">warning</span>
        <?= Yii::$app->session->getFlash('warning') ?>
    </div>
    <?php endif; ?>
    
    <div class="preview-card">
        <div class="preview-card-header">
            <h2 class="preview-card-title"><?= Html::encode($formName) ?></h2>
        </div>
        
        <div class="preview-card-body">
            <?php if ($shouldRenderCustom): ?>
                <!-- Custom Code Mode: Render only custom HTML/CSS/JS -->
                <?php if (!empty($customCss)): ?>
                    <style><?= $customCss ?></style>
                <?php endif; ?>
                <div class="mb-3">
                    <?= $customHtml ?>
                </div>
                <?php if (!empty($customJs)): ?>
                    <script>
                        (function(){
                            const run = function(){ <?= $customJs ?> };
                            try { run(); } catch (e) { console.error(e); }
                        })();
                    </script>
                <?php endif; ?>
            <?php else: ?>
                <!-- Default Form Builder Mode: Render form fields -->
                <?php if (!empty($fields)): ?>
                <?= Html::beginForm(['submit', 'id' => $model->id], 'POST', ['id' => 'preview-form']) ?>
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                
                <?php foreach ($fields as $field): ?>
                    <?php
                    if (!is_array($field)) {
                        continue;
                    }
                    $type = trim((string)($field['inputType'] ?? FormSystemFieldHelper::resolveFieldInputType($field)));
                    $label = trim((string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['name'] ?? $field['field_name'] ?? 'Field'));
                    $name = trim((string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
                    if ($name === '') {
                        $name = 'field_' . uniqid();
                    }
                    $required = !empty($field['required']);
                    $placeholder = $field['placeholder'] ?? '';
                    $defaultValue = $field['default_value'] ?? '';
                    $isFk = !empty($field['is_foreign_key']) || strtolower((string)($field['componentType'] ?? '')) === 'foreign_key';
                    $isExcluded = !empty($field['excluded']);
                    $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                    $fieldCustomHtml = trim((string)($field['customHtml'] ?? ''));
                    $fieldCustomCss = trim((string)($field['customCss'] ?? ''));
                    $fieldCustomJs = trim((string)($field['customJs'] ?? ''));

                    if (FormSystemFieldHelper::isSystemFieldData($field)) {
                        continue;
                    }

                    if ($isExcluded) {
                        echo '<div class="preview-field">';
                        echo '<div class="preview-excluded">Field "' . Html::encode($label) . '" is hidden (excluded from form)</div>';
                        echo '</div>';
                        continue;
                    }
                    ?>
                    
                    <div class="preview-field">
                        <?php if ($fieldCustomHtml !== '' || $fieldCustomCss !== '' || $fieldCustomJs !== ''): ?>
                            <?php
                            $srcDoc = '<!DOCTYPE html><html><head><style>' . $fieldCustomCss . '</style></head><body>' . $fieldCustomHtml . '<script>' . $fieldCustomJs . '<\/script></body></html>';
                            ?>
                            <iframe class="field-preview-iframe" srcdoc="<?= Html::encode($srcDoc) ?>" sandbox="allow-scripts"></iframe>

                        <?php elseif ($type === 'hidden'): ?>
                            <?= Html::hiddenInput($name, $defaultValue) ?>
                        
                        <?php elseif ($type === 'text' || $type === 'email' || $type === 'password' || $type === 'number' || $type === 'tel' || $type === 'url'): ?>
                            <?= Html::label($label, $name, ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <?= Html::input($type, $name, $defaultValue, [
                                'class' => 'preview-input',
                                'placeholder' => $placeholder,
                                'required' => $required,
                            ]) ?>
                        
                        <?php elseif ($type === 'textarea'): ?>
                            <?= Html::label($label, $name, ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <?= Html::textarea($name, $defaultValue, [
                                'class' => 'preview-input preview-textarea',
                                'placeholder' => $placeholder,
                                'required' => $required,
                                'rows' => $field['rows'] ?? 4,
                            ]) ?>
                        
                        <?php elseif ($type === 'select'): ?>
                            <?= Html::label($label, $name, ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <?php
                            $optionsList = ['' => '-- Pilih --'];
                            foreach ($options as $opt) {
                                if (!is_array($opt)) {
                                    continue;
                                }
                                $optionValue = (string)($opt['value'] ?? '');
                                if ($optionValue === '') {
                                    continue;
                                }
                                $optionsList[$optionValue] = (string)($opt['label'] ?? $optionValue);
                            }
                            ?>
                            <?= Html::dropDownList($name, $defaultValue, $optionsList, ['class' => 'preview-input preview-select', 'required' => $required]) ?>
                            <?php if ($isFk): ?>
                                <div class="preview-fk-badge">
                                    <span class="material-symbols-outlined" style="font-size:12px;">link</span>
                                    Foreign Key - Data loaded from referenced table
                                </div>
                            <?php endif; ?>
                        
                        <?php elseif ($type === 'checkbox'): ?>
                            <label class="preview-checkbox-item">
                                <?= Html::checkbox($name, $defaultValue, ['class' => 'preview-input']) ?>
                                <span class="preview-label" style="margin-bottom:0;"><?= Html::encode($label) ?></span>
                            </label>
                        
                        <?php elseif ($type === 'checkboxes'): ?>
                            <?= Html::label($label, '', ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <div class="preview-checkbox-group">
                                <?php foreach ($options as $opt): ?>
                                    <?php if (!is_array($opt) || trim((string)($opt['value'] ?? '')) === '') continue; ?>
                                    <label class="preview-checkbox-item">
                                        <?= Html::checkbox($name . '[]', false, ['class' => 'preview-input', 'value' => $opt['value'] ?? '']) ?>
                                        <span><?= Html::encode($opt['label'] ?? '') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php elseif ($type === 'radio'): ?>
                            <?= Html::label($label, '', ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <div class="preview-radio-group">
                                <?php foreach ($options as $opt): ?>
                                    <?php if (!is_array($opt) || trim((string)($opt['value'] ?? '')) === '') continue; ?>
                                    <label class="preview-radio-item">
                                        <?= Html::radio($name, false, ['class' => 'preview-input', 'value' => $opt['value'] ?? '']) ?>
                                        <span><?= Html::encode($opt['label'] ?? '') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php elseif ($type === 'date' || $type === 'time' || $type === 'datetime' || $type === 'datetime-local'): ?>
                            <?= Html::label($label, $name, ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <?= Html::input($type === 'datetime' ? 'datetime-local' : $type, $name, $defaultValue, ['class' => 'preview-input', 'required' => $required]) ?>
                        
                        <?php elseif ($type === 'file'): ?>
                            <?= Html::label($label, $name, ['class' => 'preview-label' . ($required ? ' required' : '')]) ?>
                            <?= Html::fileInput($name, null, ['class' => 'preview-input', 'required' => $required]) ?>
                        
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($tableName): ?>
                <div class="preview-actions">
                    <?= Html::button('Reset', ['type' => 'reset', 'class' => 'preview-btn preview-btn-secondary']) ?>
                    <?= Html::submitButton('Simpan Data', ['class' => 'preview-btn preview-btn-primary']) ?>
                </div>
                <?php else: ?>
                <div class="preview-alert warning" style="margin-top:24px;">
                    <span class="material-symbols-outlined">info</span>
                    Cannot submit: No target table configured for this form.
                </div>
                <?php endif; ?>
                
                <?= Html::endForm() ?>
                <?php else: ?>
                    <div class="preview-empty">
                        <span class="material-symbols-outlined">inbox</span>
                        <p>No fields defined yet.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="text-align:center;margin-top:24px;">
        <?= Html::a('← Back to Form Details', ['view', 'id' => $model->id], [
            'class' => 'preview-btn preview-btn-secondary',
            'style' => 'padding: 10px 20px; font-size: 13px;'
        ]) ?>
    </div>
</div>
