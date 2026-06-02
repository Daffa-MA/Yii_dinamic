<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\db\ActiveRecord;

/* @var $this yii\web\View */
/* @var $model app\models\MasterForm */
/* @var $activityLogs app\models\MasterFormActivityLog[] */

if ($model instanceof ActiveRecord) {
    $attrs = $model->getAttributes();
} else {
    $attrs = (array) $model;
}

$formName = $attrs['form_name'] ?? 'Form';
$formType = $attrs['form_type'] ?? '-';
$databaseContext = $attrs['database_context'] ?? '-';
$customCodeMode = !empty($attrs['custom_code_mode']) ? 'Enabled' : 'Disabled';
$formDataRaw = $attrs['form_data'] ?? '';
$formData = [];
if (is_string($formDataRaw)) {
    $formData = json_decode($formDataRaw, true) ?? [];
} elseif (is_array($formDataRaw)) {
    $formData = $formDataRaw;
}
$fields = [];
if (method_exists($model, 'getFields')) {
    $fields = $model->getFields()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
}
$fieldCount = !empty($fields) ? count($fields) : count($formData);
$layoutCount = method_exists($model, 'getLayouts') ? $model->getLayouts()->count() : 0;
$flashes = Yii::$app->session->getAllFlashes();
$formId = $attrs['id'] ?? null;
$pageId = $attrs['page_id'] ?? null;
$tableId = $attrs['table_id'] ?? null;
$isActive = isset($attrs['is_active']) && $attrs['is_active'] == 1;
$createdAt = $attrs['created_at'] ?? '-';
$updatedAt = $attrs['updated_at'] ?? '-';
$slug = $attrs['slug'] ?? '';
$activityLogs = $activityLogs ?? [];

$tableName = null;
if ($tableId) {
    $tableModel = \app\models\DbTable::findOne($tableId);
    $tableName = $tableModel ? $tableModel->name : null;
}

$this->title = $formName;
$this->params['breadcrumbs'][] = ['label' => 'Forms', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.view-page {
    min-height: 100vh;
    background: #f8fafc;
}

.view-header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 0;
}

.view-header-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 24px 32px;
}

.view-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748b;
    margin-bottom: 16px;
}

.view-breadcrumb a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.15s;
}

.view-breadcrumb a:hover {
    color: #334155;
}

.view-breadcrumb span {
    color: #cbd5e1;
}

.view-header-main {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
}

.view-header-info {
    flex: 1;
}

.view-header-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #f1f5f9;
    border-radius: 12px;
    margin-bottom: 16px;
}

.view-header-icon .material-symbols-outlined {
    font-size: 24px;
    color: #475569;
}

.view-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    margin: 0 0 8px 0;
}

.view-subtitle {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.view-header-actions {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
}

.view-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s ease;
    cursor: pointer;
    border: none;
}

.view-btn-primary {
    background: #0f172a;
    color: white;
}

.view-btn-primary:hover {
    background: #1e293b;
}

.view-btn-danger {
    background: white;
    color: #dc2626;
    border: 1px solid #fee2e2;
}

.view-btn-danger:hover {
    background: #fef2f2;
    border-color: #fecaca;
}

.view-btn .material-symbols-outlined {
    font-size: 18px;
}

.view-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 32px;
}

.view-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.view-stat-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.2s ease;
}

.view-stat-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.view-notice {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 16px;
    border: 1px solid transparent;
}

.view-notice.success {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}

.view-notice.warning {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}

.view-notice.info {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
}

.view-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.view-stat-icon.fields {
    background: #eff6ff;
}

.view-stat-icon.fields .material-symbols-outlined {
    color: #3b82f6;
}

.view-stat-icon.submissions {
    background: #f0fdf4;
}

.view-stat-icon.submissions .material-symbols-outlined {
    color: #22c55e;
}

.view-stat-icon.date {
    background: #fef3c7;
}

.view-stat-icon.date .material-symbols-outlined {
    color: #f59e0b;
}

.view-stat-info {
    flex: 1;
    min-width: 0;
}

.view-stat-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.view-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
}

.view-main-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    margin-bottom: 32px;
}

.view-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.2s ease;
}

.view-card:hover {
    border-color: #cbd5e1;
}

.view-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbfc;
}

.view-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
}

.view-card-icon .material-symbols-outlined {
    font-size: 18px;
    color: #475569;
}

.view-card-title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.view-card-body {
    padding: 8px 0;
}

.view-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.15s;
}

.view-info-row:last-child {
    border-bottom: none;
}

.view-info-row:hover {
    background: #fafbfc;
}

.view-info-label {
    font-size: 13px;
    color: #64748b;
}

.view-info-value {
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    text-align: right;
}

.view-info-value.mono {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
}

.view-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.view-status-badge.active {
    background: #f0fdf4;
    color: #16a34a;
}

.view-status-badge.inactive {
    background: #f1f5f9;
    color: #64748b;
}

.view-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.view-status-dot.active {
    background: #22c55e;
}

.view-status-dot.inactive {
    background: #94a3b8;
}

.view-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #475569;
}

.view-actions-list {
    padding: 12px 16px;
}

.view-action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #334155;
    transition: all 0.15s ease;
    margin-bottom: 4px;
}

.view-action-item:hover {
    background: #f8fafc;
}

.view-action-item:last-child {
    margin-bottom: 0;
}

.view-action-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.view-action-icon .material-symbols-outlined {
    font-size: 18px;
    color: #475569;
}

.view-action-icon.preview .material-symbols-outlined {
    color: #3b82f6;
}

.view-action-icon.submissions .material-symbols-outlined {
    color: #22c55e;
}

.view-action-icon.clone .material-symbols-outlined {
    color: #8b5cf6;
}

.view-action-text {
    flex: 1;
}

.view-action-title {
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
}

.view-action-desc {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}

.view-section-divider {
    height: 1px;
    background: linear-gradient(to right, transparent, #e2e8f0, transparent);
    margin: 24px 0;
}

.view-fields-section {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.view-fields-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbfc;
}

.view-fields-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.view-fields-title-text {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.view-fields-count {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 10px;
}

.view-fields-grid {
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    max-height: 500px;
    overflow-y: auto;
}

.view-field-card {
    background: #fafbfc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    transition: all 0.15s ease;
}

.view-field-card:hover {
    border-color: #cbd5e1;
    background: white;
}

.view-field-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.view-field-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.view-field-icon .material-symbols-outlined {
    font-size: 16px;
}

.view-field-icon.text,
.view-field-icon.email,
.view-field-icon.password,
.view-field-icon.number {
    background: #eff6ff;
}

.view-field-icon.text .material-symbols-outlined,
.view-field-icon.email .material-symbols-outlined,
.view-field-icon.password .material-symbols-outlined,
.view-field-icon.number .material-symbols-outlined {
    color: #3b82f6;
}

.view-field-icon.select {
    background: #f0fdf4;
}

.view-field-icon.select .material-symbols-outlined {
    color: #22c55e;
}

.view-field-icon.checkbox,
.view-field-icon.radio {
    background: #fef3c7;
}

.view-field-icon.checkbox .material-symbols-outlined,
.view-field-icon.radio .material-symbols-outlined {
    color: #f59e0b;
}

.view-field-icon.date,
.view-field-icon.time,
.view-field-icon.datetime {
    background: #fae8ff;
}

.view-field-icon.date .material-symbols-outlined,
.view-field-icon.time .material-symbols-outlined,
.view-field-icon.datetime .material-symbols-outlined {
    color: #a855f7;
}

.view-field-icon.textarea {
    background: #f0fdfa;
}

.view-field-icon.textarea .material-symbols-outlined {
    color: #14b8a6;
}

.view-field-icon.file {
    background: #fff7ed;
}

.view-field-icon.file .material-symbols-outlined {
    color: #f97316;
}

.view-field-icon.hidden {
    background: #f1f5f9;
}

.view-field-icon.hidden .material-symbols-outlined {
    color: #94a3b8;
}

.view-field-icon.custom {
    background: #fdf2f8;
}

.view-field-icon.custom .material-symbols-outlined {
    color: #ec4899;
}

.view-field-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
}

.view-field-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.view-field-type-badge {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #475569;
    text-transform: uppercase;
}

.view-field-required {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 4px;
    background: #fef2f2;
    color: #dc2626;
}

.view-field-fk {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 4px;
    background: #fef3c7;
    color: #92400e;
}

.view-field-excluded {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 4px;
    background: #f1f5f9;
    color: #64748b;
}

.view-field-info {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 8px;
}

.view-field-info span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.view-empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #64748b;
}

.view-empty-state .material-symbols-outlined {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.view-empty-state p {
    font-size: 14px;
    margin: 0;
}

@media (max-width: 1024px) {
    .view-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .view-header-content {
        padding: 20px 16px;
    }
    
    .view-content {
        padding: 20px 16px;
    }
    
    .view-stats {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .view-header-main {
        flex-direction: column;
    }
    
    .view-header-actions {
        width: 100%;
    }
    
    .view-header-actions .view-btn {
        flex: 1;
        justify-content: center;
    }
    
    .view-fields-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="view-page">
    <!-- Header -->
    <div class="view-header">
        <div class="view-header-content">
            <div class="view-breadcrumb">
                <a href="<?= \yii\helpers\Url::to(['index']) ?>">Forms</a>
                <span class="material-symbols-outlined" style="font-size:14px;">chevron_right</span>
                <span><?= Html::encode($formName) ?></span>
            </div>
            
            <div class="view-header-main">
                <div class="view-header-info">
                    <div class="view-header-icon">
                        <span class="material-symbols-outlined">description</span>
                    </div>
                    <h1 class="view-title"><?= Html::encode($formName) ?></h1>
                    <p class="view-subtitle">Form configuration and field management</p>
                </div>
                
                <div class="view-header-actions">
                    <?= Html::a(
                        '<span class="material-symbols-outlined">edit</span> Edit Form',
                        ['update', 'id' => $formId],
                        ['class' => 'view-btn view-btn-primary']
                    ) ?>
                    <?= Html::a(
                        '<span class="material-symbols-outlined">delete</span> Delete',
                        ['delete', 'id' => $formId],
                        [
                            'class' => 'view-btn view-btn-danger',
                            'data' => [
                                'confirm' => 'Are you sure you want to delete this form?',
                                'method' => 'post',
                            ]
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="view-content">
        <?php foreach ($flashes as $flashType => $flashMessages): ?>
            <?php foreach ((array)$flashMessages as $flashMessage): ?>
                <div class="view-notice <?= Html::encode($flashType) ?>">
                    <span class="material-symbols-outlined">notifications</span>
                    <div><?= Html::encode(is_array($flashMessage) ? Json::encode($flashMessage) : $flashMessage) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <?= $this->render('_status_panel', ['model' => $model]) ?>
        <?= $this->render('_activity_timeline', ['activityLogs' => $activityLogs]) ?>

        <!-- Stats Row -->
        <div class="view-stats">
            <div class="view-stat-card">
                <div class="view-stat-icon fields">
                    <span class="material-symbols-outlined">widgets</span>
                </div>
                <div class="view-stat-info">
                    <div class="view-stat-label">Total Fields</div>
                    <div class="view-stat-value"><?= $fieldCount ?></div>
                </div>
            </div>
            
            <div class="view-stat-card">
                <div class="view-stat-icon submissions">
                    <span class="material-symbols-outlined">inbox</span>
                </div>
                <div class="view-stat-info">
                    <div class="view-stat-label">Submissions</div>
                    <div class="view-stat-value">-</div>
                </div>
            </div>
            
            <div class="view-stat-card">
                <div class="view-stat-icon date">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div class="view-stat-info">
                    <div class="view-stat-label">Last Modified</div>
                    <div class="view-stat-value" style="font-size:14px; font-weight:600;"><?= $updatedAt ?></div>
                </div>
            </div>
        </div>
        
        <!-- Main Grid -->
        <div class="view-main-grid">
            <!-- Form Information -->
            <div class="view-card">
                <div class="view-card-header">
                    <div class="view-card-icon">
                        <span class="material-symbols-outlined">info</span>
                    </div>
                    <div class="view-card-title">Form Information</div>
                </div>
                <div class="view-card-body">
                    <div class="view-info-row">
                        <span class="view-info-label">Form ID</span>
                        <span class="view-info-value mono">#<?= $formId ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Form Name</span>
                        <span class="view-info-value"><?= Html::encode($formName) ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Form Type</span>
                        <span class="view-type-badge"><?= Html::encode($formType) ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Database Context</span>
                        <span class="view-info-value mono"><?= Html::encode($databaseContext) ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Target Table</span>
                        <span class="view-info-value mono" style="<?= $tableName ? 'color:#16a34a;' : '' ?>"><?= $tableName ?: '<span style="color:#94a3b8;">Not set</span>' ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Custom Code</span>
                        <span class="view-type-badge"><?= Html::encode($customCodeMode) ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Status</span>
                        <?php if ($isActive): ?>
                            <span class="view-status-badge active">
                                <span class="view-status-dot active"></span>
                                Active
                            </span>
                        <?php else: ?>
                            <span class="view-status-badge inactive">
                                <span class="view-status-dot inactive"></span>
                                Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">URL Slug</span>
                        <span class="view-info-value mono" style="font-size:11px;"><?= Html::encode($slug ?: '-') ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Created</span>
                        <span class="view-info-value"><?= $createdAt ?></span>
                    </div>
                    <div class="view-info-row">
                        <span class="view-info-label">Last Updated</span>
                        <span class="view-info-value"><?= $updatedAt ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="view-card">
                <div class="view-card-header">
                    <div class="view-card-icon">
                        <span class="material-symbols-outlined">bolt</span>
                    </div>
                    <div class="view-card-title">Quick Actions</div>
                </div>
                <div class="view-actions-list">
                    <?= Html::a(
                        '<div class="view-action-icon preview"><span class="material-symbols-outlined">visibility</span></div>' .
                        '<div class="view-action-text"><div class="view-action-title">Preview Form</div><div class="view-action-desc">Open form in new tab</div></div>',
                        ['/master-form/preview', 'id' => $formId],
                        ['class' => 'view-action-item', 'target' => '_blank']
                    ) ?>
                    
                    <?= Html::a(
                        '<div class="view-action-icon submissions"><span class="material-symbols-outlined">inbox</span></div>' .
                        '<div class="view-action-text"><div class="view-action-title">Edit Fields</div><div class="view-action-desc">Modify form fields</div></div>',
                        ['/master-form/update', 'id' => $formId],
                        ['class' => 'view-action-item']
                    ) ?>
                    
                    <?= Html::a(
                        '<div class="view-action-icon clone"><span class="material-symbols-outlined">content_copy</span></div>' .
                        '<div class="view-action-text"><div class="view-action-title">Duplicate Form</div><div class="view-action-desc">Create a copy of this form</div></div>',
                        ['/master-form/duplicate', 'id' => $formId],
                        ['class' => 'view-action-item']
                    ) ?>
                </div>
            </div>
        </div>
        
        <!-- Field Preview Section -->
        <div class="view-fields-section">
            <div class="view-fields-header">
                <div class="view-fields-title">
                    <div class="view-card-icon">
                        <span class="material-symbols-outlined">view_agenda</span>
                    </div>
                    <div class="view-fields-title-text">Field Preview</div>
                    <div class="view-fields-count"><?= $fieldCount ?> fields</div>
                    <div class="view-fields-count"><?= $layoutCount ?> layouts</div>
                </div>
            </div>
            
            <?php if (!empty($fields)): ?>
                <div class="view-fields-grid">
                    <?php foreach ($fields as $field): ?>
                        <?php
                        $fieldData = $field instanceof \yii\db\ActiveRecord ? $field->getAttributes() : (array)$field;
                        $type = trim((string)($fieldData['field_type'] ?? $fieldData['type'] ?? 'text'));
                        $label = trim((string)($fieldData['resolved_label'] ?? $fieldData['field_label'] ?? $fieldData['label'] ?? $fieldData['field_name'] ?? 'Field'));
                        $name = trim((string)($fieldData['resolved_name'] ?? $fieldData['field_name'] ?? $fieldData['field_key'] ?? $fieldData['name'] ?? ''));
                        $required = !empty($fieldData['is_required'] ?? $fieldData['required'] ?? false);
                        $isFk = !empty($fieldData['foreign_key_table'] ?? false) || !empty($fieldData['is_foreign_key'] ?? false) || !empty($fieldData['fk_referenced_table'] ?? false);
                        $isExcluded = !empty($fieldData['excluded'] ?? false);
                        $fkTable = trim((string)($fieldData['foreign_key_table'] ?? $fieldData['fk_referenced_table'] ?? ''));
                        $fieldSettings = [];
                        if (!empty($fieldData['field_settings'])) {
                            $fieldSettings = is_string($fieldData['field_settings']) ? (json_decode($fieldData['field_settings'], true) ?? []) : (array)$fieldData['field_settings'];
                        } elseif (!empty($fieldData['field_config'])) {
                            $fieldSettings = is_string($fieldData['field_config']) ? (json_decode($fieldData['field_config'], true) ?? []) : (array)$fieldData['field_config'];
                        }
                        $optionsCount = 0;
                        if (isset($fieldSettings['options']) && is_array($fieldSettings['options'])) {
                            $optionsCount = count($fieldSettings['options']);
                        } elseif (isset($fieldSettings['fk_options']) && is_array($fieldSettings['fk_options'])) {
                            $optionsCount = count($fieldSettings['fk_options']);
                        }
                        
                        $iconMap = [
                            'text' => 'text_fields',
                            'email' => 'email',
                            'password' => 'lock',
                            'number' => 'pin',
                            'tel' => 'phone',
                            'url' => 'link',
                            'textarea' => 'notes',
                            'select' => 'arrow_drop_down_circle',
                            'checkbox' => 'check_box',
                            'checkboxes' => 'checklist',
                            'radio' => 'radio_button_checked',
                            'date' => 'calendar_today',
                            'time' => 'schedule',
                            'datetime' => 'event',
                            'boolean' => 'toggle_on',
                            'file' => 'upload_file',
                            'hidden' => 'visibility_off',
                        ];
                        $icon = $iconMap[$type] ?? 'text_fields';
                        ?>
                        <div class="view-field-card">
                            <div class="view-field-header">
                                <div class="view-field-icon <?= Html::encode($type) ?>">
                                    <span class="material-symbols-outlined"><?= $icon ?></span>
                                </div>
                                <div class="view-field-name"><?= Html::encode($label) ?></div>
                            </div>
                            <div class="view-field-meta">
                                <span class="view-field-type-badge"><?= Html::encode($type) ?></span>
                                <?php if ($required): ?>
                                    <span class="view-field-required">Required</span>
                                <?php endif; ?>
                                <?php if ($isFk): ?>
                                    <span class="view-field-fk">FK → <?= Html::encode($fkTable) ?></span>
                                <?php endif; ?>
                                <?php if ($isExcluded): ?>
                                    <span class="view-field-excluded">Hidden</span>
                                <?php endif; ?>
                            </div>
                            <div class="view-field-info">
                                <span>
                                    <span class="material-symbols-outlined" style="font-size:12px;">label</span>
                                    <?= Html::encode($name) ?>
                                </span>
                                <?php if ($optionsCount > 0): ?>
                                    <span style="margin-left:12px;">
                                        <span class="material-symbols-outlined" style="font-size:12px;">list</span>
                                        <?= $optionsCount ?> options
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="view-empty-state">
                    <span class="material-symbols-outlined">inbox</span>
                    <p>No fields defined yet. Edit this form to add fields.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
