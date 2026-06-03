<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use app\models\DbTable;
use app\components\SystemFieldService;
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
$targetTable = null;
if (!empty($model->table_id)) {
    $targetTable = DbTable::findOne((int)$model->table_id);
}
if ($targetTable !== null) {
    $targetSchema = Yii::$app->db->schema->getTableSchema((string)$targetTable->name, true);
    $existingFieldNames = [];
    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldName = trim((string)($field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
        if ($fieldName !== '') {
            $existingFieldNames[strtolower($fieldName)] = true;
        }
    }

    $targetColumns = $targetTable->getColumns()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
    foreach ($targetColumns as $column) {
        $columnName = trim((string)$column->name);
        if ($columnName === '' || isset($existingFieldNames[strtolower($columnName)])) {
            continue;
        }
        if (SystemFieldService::shouldHideFromForm($column)) {
            continue;
        }

        $isForeignKey = $column->hasAttribute('is_foreign_key') && (bool)$column->getAttribute('is_foreign_key');
        $schemaColumn = $targetSchema !== null ? ($targetSchema->columns[$columnName] ?? null) : null;
        $rawColumnType = (string)($schemaColumn !== null ? ($schemaColumn->dbType ?? $schemaColumn->type ?? '') : ($column->type ?? ''));
        $fieldSeed = [
            'name' => $columnName,
            'field_name' => $columnName,
            'field_key' => $columnName,
            'column_name' => $columnName,
            'source_column_db_type' => $rawColumnType,
            'source_column_column_type' => $rawColumnType,
            'source_column_data_type' => (string)($schemaColumn !== null ? ($schemaColumn->type ?? '') : ''),
            'source_column_type' => (string)($column->type ?? ''),
            'source_column_length' => $schemaColumn !== null ? (int)($schemaColumn->size ?? 0) : (int)($column->length ?? 0),
        ];
        $fieldType = $isForeignKey ? 'select' : FormSystemFieldHelper::resolveFieldInputType($fieldSeed);

        $referencedTable = $isForeignKey && $column->hasAttribute('referenced_table_name')
            ? trim((string)$column->getAttribute('referenced_table_name'))
            : '';
        $referencedColumn = $isForeignKey && $column->hasAttribute('referenced_column_name')
            ? trim((string)$column->getAttribute('referenced_column_name'))
            : '';

        $fields[] = [
            'id' => 'preview_autofill_' . $column->id,
            'name' => $columnName,
            'field_name' => $columnName,
            'field_key' => $columnName,
            'column_name' => $columnName,
            'label' => (string)($column->label ?: $columnName),
            'field_label' => (string)($column->label ?: $columnName),
            'type' => $fieldType,
            'inputType' => $fieldType === 'datetime' ? 'datetime-local' : $fieldType,
            'required' => !$column->is_nullable,
            'excluded' => false,
            'source_column_id' => (int)$column->id,
            'source_column_name' => $columnName,
            'source_column_type' => (string)($column->type ?? ''),
            'source_column_db_type' => $rawColumnType,
            'source_column_column_type' => $rawColumnType,
            'source_column_data_type' => (string)($schemaColumn !== null ? ($schemaColumn->type ?? '') : ''),
            'source_column_length' => $schemaColumn !== null ? (int)($schemaColumn->size ?? 0) : (int)($column->length ?? 0),
            'is_foreign_key' => $isForeignKey,
            'fk_referenced_table' => $referencedTable !== '' ? $referencedTable : null,
            'fk_referenced_column' => $referencedColumn !== '' ? $referencedColumn : null,
            'value_column' => $referencedColumn !== '' ? $referencedColumn : null,
            'relation_config' => $isForeignKey && $referencedTable !== '' && $referencedColumn !== '' ? [
                'local_column' => $columnName,
                'source_column' => $columnName,
                'column_name' => $columnName,
                'referenced_table' => $referencedTable,
                'referenced_table_name' => $referencedTable,
                'referenced_value_column' => $referencedColumn,
                'referenced_column' => $referencedColumn,
                'referenced_column_name' => $referencedColumn,
                'value_column' => $referencedColumn,
            ] : null,
        ];
        $existingFieldNames[strtolower($columnName)] = true;
    }
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

.relation-picker-wrapper {
    width: 100%;
}

.relation-picker-input-group,
.relation-picker-row {
    display: flex;
    gap: 8px;
    align-items: stretch;
    width: 100%;
}

.relation-picker-input-group .preview-input,
.relation-picker-row .preview-input {
    flex: 1;
    min-width: 0;
}

.relation-picker-button,
.relation-picker-btn {
    border: 1px solid #dbe3ef;
    background: #ffffff;
    color: #334155;
    border-radius: 12px;
    padding: 0 14px;
    font-weight: 700;
    cursor: pointer;
}

.relation-picker-status {
    margin-top: 6px;
    font-size: 12px;
    color: #64748b;
}

.relation-picker-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 12000;
    background: rgba(15, 23, 42, .48);
    backdrop-filter: blur(4px);
}

.relation-picker-modal.open {
    display: flex;
}

.relation-picker-panel {
    width: min(860px, 100%);
    max-height: min(680px, 88vh);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    box-shadow: 0 28px 90px rgba(15, 23, 42, .28);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.relation-picker-head,
.relation-picker-foot {
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}

.relation-picker-foot {
    border-bottom: 0;
    border-top: 1px solid #e2e8f0;
}

.relation-picker-body {
    padding: 16px 18px;
    overflow: auto;
}

.relation-picker-table {
    width: 100%;
    border-collapse: collapse;
}

.relation-picker-table th,
.relation-picker-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eef2f7;
    text-align: left;
    font-size: 13px;
}

.relation-picker-table tr {
    cursor: pointer;
}

.relation-picker-table tbody tr:hover {
    background: #f8fafc;
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
                    $fieldRelationConfig = $field['relation_config'] ?? null;
                    if (is_string($fieldRelationConfig)) {
                        $fieldRelationConfig = json_decode($fieldRelationConfig, true);
                    }
                    if (!is_array($fieldRelationConfig)) {
                        $fieldRelationConfig = [];
                    }
                    $type = strtolower(trim((string)($field['inputType'] ?? FormSystemFieldHelper::resolveFieldInputType($field))));
                    $label = trim((string)($field['resolved_label'] ?? $field['label'] ?? $field['field_label'] ?? $field['name'] ?? $field['field_name'] ?? 'Field'));
                    $name = trim((string)($field['resolved_name'] ?? $field['resolved_column_name'] ?? $field['name'] ?? $field['field_name'] ?? $field['field_key'] ?? $field['column_name'] ?? ''));
                    $required = !empty($field['required']);
                    $placeholder = $field['placeholder'] ?? '';
                    $defaultValue = $field['default_value'] ?? '';
                    $isFk = !empty($field['is_foreign_key']) || strtolower((string)($field['componentType'] ?? '')) === 'foreign_key';
                    if ($isFk) {
                        $canonicalFkName = trim((string)($fieldRelationConfig['local_column'] ?? $fieldRelationConfig['source_column'] ?? $fieldRelationConfig['column_name'] ?? $field['source_column_name'] ?? $field['local_column'] ?? $field['source_column'] ?? $field['column_name'] ?? $field['name'] ?? $field['field_name'] ?? ''));
                        if ($canonicalFkName !== '') {
                            $name = $canonicalFkName;
                        }
                    }
                    if ($name === '') {
                        $name = 'field_' . uniqid();
                    }
                    $isExcluded = !empty($field['excluded']);
                    $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                    $fieldCustomHtml = trim((string)($field['customHtml'] ?? ''));
                    $fieldCustomCss = trim((string)($field['customCss'] ?? ''));
                    $fieldCustomJs = trim((string)($field['customJs'] ?? ''));

                    if ($isFk) {
                        Yii::info([
                            'preview_fk_render' => true,
                            'label' => $label,
                            'submit_name' => $name,
                            'relation_config' => $fieldRelationConfig,
                            'options_count' => count($options),
                        ], 'form-render-fields');
                    }

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
                            $pickerMode = $isFk ? strtolower(trim((string)($field['picker_mode'] ?? 'dropdown'))) : 'dropdown';
                            $allowedPickerModes = ['dropdown', 'autocomplete', 'modal_picker', 'autocomplete_with_modal'];
                            if (!in_array($pickerMode, $allowedPickerModes, true)) {
                                $pickerMode = 'dropdown';
                            }
                            $selectedDisplay = $defaultValue !== '' && isset($optionsList[(string)$defaultValue]) ? $optionsList[(string)$defaultValue] : '';
                            $pickerConfig = is_array($field['picker_config'] ?? null) ? $field['picker_config'] : [];
                            $searchColumns = array_values(array_filter(array_map('trim', (array)($pickerConfig['search_columns'] ?? []))));
                            $displayColumns = array_values(array_filter(array_map('trim', (array)($pickerConfig['display_columns'] ?? []))));
                            $searchSummary = $searchColumns !== [] ? Html::encode(implode(', ', array_slice($searchColumns, 0, 4))) : Html::encode($pickerMode === 'autocomplete_with_modal' ? 'Mengikuti pengaturan modal' : 'Tidak disetel');
                            $displaySummary = $displayColumns !== [] ? Html::encode(implode(', ', array_slice($displayColumns, 0, 4))) : Html::encode($pickerMode === 'autocomplete_with_modal' ? 'Mengikuti pengaturan modal' : 'Tidak disetel');
                            $pageSize = max(1, min(50, (int)($pickerConfig['page_size'] ?? 10)));
                            ?>
                            <?php if ($isFk && $pickerMode !== 'dropdown'): ?>
                                <?= Html::hiddenInput($name, $defaultValue, [
                                    'class' => 'relation-picker-value',
                                    'data-relation-picker-value' => $name,
                                ]) ?>
                                <div class="relation-picker-row">
                                    <?= Html::textInput('__fk_display_' . $name, $selectedDisplay, [
                                        'class' => 'preview-input relation-picker-display',
                                        'placeholder' => $placeholder ?: 'Cari ' . $label . '...',
                                        'required' => $required,
                                        'readonly' => $pickerMode === 'modal_picker',
                                        'data-form-id' => (int)$model->id,
                                        'data-field-name' => $name,
                                        'data-picker-mode' => $pickerMode,
                                    ]) ?>
                                    <?php if ($pickerMode === 'modal_picker' || $pickerMode === 'autocomplete_with_modal'): ?>
                                        <button type="button" class="relation-picker-btn relation-picker-button" data-relation-picker-open="<?= Html::encode($name) ?>" data-field-name="<?= Html::encode($name) ?>" data-picker-field="<?= Html::encode($name) ?>">Pilih</button>
                                    <?php endif; ?>
                                </div>
                                <div class="relation-picker-status" data-relation-picker-status="<?= Html::encode($name) ?>">
                                    Tekan Enter untuk mencari data.
                                </div>
                                <div class="relation-picker-config-summary" style="margin-top:8px;padding:8px 10px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;font-size:12px;color:#475569;line-height:1.5;">
                                    <div><strong>Search columns:</strong> <?= $searchSummary ?></div>
                                    <div><strong>Display columns:</strong> <?= $displaySummary ?></div>
                                    <div><strong>Page size:</strong> <?= $pageSize ?></div>
                                </div>
                            <?php else: ?>
                                <?= Html::dropDownList($name, $defaultValue, $optionsList, [
                                    'class' => 'preview-input preview-select',
                                    'required' => $required,
                                    'data-fk-submit-name' => $isFk ? $name : null,
                                ]) ?>
                            <?php endif; ?>
                            <?php if ($isFk): ?>
                                <div class="preview-fk-badge">
                                    <span class="material-symbols-outlined" style="font-size:12px;">link</span>
                                    Foreign Key - Data loaded from referenced table
                                </div>
                            <?php endif; ?>
                        
                        <?php elseif ($type === 'boolean'): ?>
                            <?php $booleanChecked = (string)$defaultValue === '1' || strtolower((string)$defaultValue) === 'true'; ?>
                            <div class="preview-checkbox-item form-check form-switch">
                                <?= Html::checkbox($name, $booleanChecked, [
                                    'class' => 'preview-input form-check-input',
                                    'uncheck' => '0',
                                    'value' => '1',
                                ]) ?>
                                <span class="preview-label" style="margin-bottom:0;"><?= Html::encode($label) ?></span>
                            </div>
                        
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

<div class="relation-picker-modal" id="relationPickerModal" aria-hidden="true">
    <div class="relation-picker-panel">
        <div class="relation-picker-head">
            <strong id="relationPickerTitle">Pilih Data</strong>
            <button type="button" class="relation-picker-btn" id="relationPickerClose">Tutup</button>
        </div>
        <div class="relation-picker-body">
            <input type="text" class="preview-input" id="relationPickerSearch" placeholder="Cari data...">
            <div id="relationPickerContent" style="margin-top:14px;"></div>
        </div>
        <div class="relation-picker-foot">
            <button type="button" class="relation-picker-btn" id="relationPickerPrev">Sebelumnya</button>
            <span id="relationPickerPageInfo" style="font-size:13px;color:#64748b;"></span>
            <button type="button" class="relation-picker-btn" id="relationPickerNext">Berikutnya</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var previewForm = document.getElementById('preview-form');
    if (!previewForm) {
        return;
    }

    var pickerDataUrl = <?= Json::encode(Url::to(['relation-picker-data'])) ?>;
    var pickerSearchUrl = <?= Json::encode(Url::to(['relation-picker-search'])) ?>;
    var pickerState = { fieldName: '', formId: '', page: 1, hasNext: false };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setPickerStatus(fieldName, text) {
        var status = previewForm.querySelector('[data-relation-picker-status="' + CSS.escape(fieldName) + '"]');
        if (status) status.textContent = text || '';
    }

    function setPickerValue(fieldName, value, label) {
        var hidden = previewForm.querySelector('[data-relation-picker-value="' + CSS.escape(fieldName) + '"]');
        var display = previewForm.querySelector('.relation-picker-display[data-field-name="' + CSS.escape(fieldName) + '"]');
        if (hidden) {
            hidden.value = value || '';
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (display) display.value = label || '';
        setPickerStatus(fieldName, value ? 'Dipilih: ' + (label || value) : '');
    }

    function buildPickerUrl(baseUrl, formId, fieldName, query, page, limit) {
        var params = new URLSearchParams({
            form_id: formId,
            field_name: fieldName,
            q: query || ''
        });
        if (page) params.set('page', page);
        if (limit) params.set('limit', limit);
        return baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + params.toString();
    }

    function renderPickerRows(data) {
        var content = document.getElementById('relationPickerContent');
        var pageInfo = document.getElementById('relationPickerPageInfo');
        var rows = data && Array.isArray(data.rows) ? data.rows : [];
        pickerState.hasNext = !!(data && data.pagination && data.pagination.has_next);
        if (pageInfo) pageInfo.textContent = 'Halaman ' + pickerState.page + ' - ' + ((data.pagination && data.pagination.total) || 0) + ' data';

        if (!rows.length) {
            content.innerHTML = '<div class="preview-alert warning" style="margin:0;">No data available<br><small>This table does not have any data yet.</small></div>';
            return;
        }

        var keys = Object.keys(rows[0].display || {});
        content.innerHTML = '<table class="relation-picker-table"><thead><tr>' +
            keys.map(function(key) { return '<th>' + escapeHtml(key) + '</th>'; }).join('') +
            '</tr></thead><tbody>' +
            rows.map(function(row) {
                return '<tr data-value="' + escapeHtml(row.value) + '" data-label="' + escapeHtml(row.label) + '">' +
                    keys.map(function(key) { return '<td>' + escapeHtml(row.display[key]) + '</td>'; }).join('') +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    function openPicker(fieldName, formId, query) {
        pickerState = { fieldName: fieldName, formId: formId, page: 1, hasNext: false };
        var modal = document.getElementById('relationPickerModal');
        var search = document.getElementById('relationPickerSearch');
        if (search) search.value = query || '';
        if (modal) modal.classList.add('open');
        loadPickerPage();
    }

    function closePicker() {
        var modal = document.getElementById('relationPickerModal');
        if (modal) modal.classList.remove('open');
    }

    function loadPickerPage() {
        var search = document.getElementById('relationPickerSearch');
        var query = search ? search.value : '';
        fetch(buildPickerUrl(pickerDataUrl, pickerState.formId, pickerState.fieldName, query, pickerState.page))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.success) throw new Error(data.message || 'Gagal memuat data');
                renderPickerRows(data);
            })
            .catch(function(error) {
                document.getElementById('relationPickerContent').innerHTML = '<div class="preview-alert danger" style="margin:0;">' + escapeHtml(error.message) + '</div>';
            });
    }

    previewForm.querySelectorAll('.relation-picker-display').forEach(function(input) {
        input.addEventListener('keydown', function(event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            var fieldName = input.getAttribute('data-field-name');
            var formId = input.getAttribute('data-form-id');
            var mode = input.getAttribute('data-picker-mode');
            var query = input.value || '';

            if (mode === 'modal_picker' || mode === 'autocomplete_with_modal') {
                openPicker(fieldName, formId, query);
                return;
            }

            setPickerStatus(fieldName, 'Mencari data...');
            fetch(buildPickerUrl(pickerSearchUrl, formId, fieldName, query, null, 10))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var matches = data && Array.isArray(data.matches) ? data.matches : [];
                    if (matches.length === 1) {
                        setPickerValue(fieldName, matches[0].value, matches[0].label);
                    } else if (matches.length > 1) {
                        openPicker(fieldName, formId, query);
                    } else {
                        setPickerStatus(fieldName, 'Data tidak ditemukan.');
                    }
                })
                .catch(function() { setPickerStatus(fieldName, 'Gagal mencari data.'); });
        });
    });

    previewForm.querySelectorAll('[data-relation-picker-open]').forEach(function(button) {
        button.addEventListener('click', function() {
            var fieldName = button.getAttribute('data-relation-picker-open');
            var input = previewForm.querySelector('.relation-picker-display[data-field-name="' + CSS.escape(fieldName) + '"]');
            openPicker(fieldName, input ? input.getAttribute('data-form-id') : '', input ? input.value : '');
        });
    });

    document.getElementById('relationPickerClose')?.addEventListener('click', closePicker);
    document.getElementById('relationPickerPrev')?.addEventListener('click', function() {
        if (pickerState.page > 1) {
            pickerState.page -= 1;
            loadPickerPage();
        }
    });
    document.getElementById('relationPickerNext')?.addEventListener('click', function() {
        if (pickerState.hasNext) {
            pickerState.page += 1;
            loadPickerPage();
        }
    });
    document.getElementById('relationPickerSearch')?.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            pickerState.page = 1;
            loadPickerPage();
        }
    });
    document.getElementById('relationPickerContent')?.addEventListener('click', function(event) {
        var row = event.target.closest('tr[data-value]');
        if (!row) return;
        setPickerValue(pickerState.fieldName, row.getAttribute('data-value'), row.getAttribute('data-label'));
        closePicker();
    });

    previewForm.addEventListener('submit', function() {
        previewForm.querySelectorAll('select[data-fk-submit-name]').forEach(function(select) {
            var submitName = select.getAttribute('data-fk-submit-name');
            if (!submitName) {
                return;
            }

            var hiddenName = '__fk_submit_' + submitName;
            var hiddenInput = previewForm.querySelector('input[name="' + hiddenName + '"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = hiddenName;
                previewForm.appendChild(hiddenInput);
            }

            hiddenInput.value = select.value || '';
        });
    });
});
</script>
