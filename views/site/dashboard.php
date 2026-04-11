<?php

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */
/** @var app\models\FormSubmission[] $recentSubmissions */

use yii\bootstrap5\Html;
use yii\bootstrap5\HtmlPurifier;

$this->title = 'Dashboard';
?>

<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-800: #1f2937;
    --gray-900: #111827;
}

.site-dashboard {
    animation: fadeIn 0.6s ease;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    animation: slideDown 0.6s ease;
}

.dashboard-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.dashboard-header h1 i {
    color: var(--primary);
}

/* STATS CARDS */
.dashboard-stats {
    margin-bottom: 40px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.stat-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s ease 0.1s both;
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12) !important;
}

.stat-card-primary {
    background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
    color: white;
}

.stat-card-success {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    color: white;
}

.stat-card-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    color: white;
}

.stat-card-info {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    color: white;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.stat-card-body {
    position: relative;
    z-index: 1;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-info {
    flex: 1;
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    font-weight: 500;
    opacity: 0.9;
}

.stat-icon {
    font-size: 40px;
    opacity: 0.3;
    flex-shrink: 0;
}

/* FORMS SECTION */
.forms-section {
    animation: fadeInUp 0.6s ease 0.2s both;
}

.section-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 20px 24px;
    border-radius: 12px 12px 0 0;
    border: none;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.section-header i {
    font-size: 20px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    display: block;
}

.empty-state-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.empty-state-text {
    color: var(--gray-600);
    margin-bottom: 24px;
}

/* FORM CARDS */
.forms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.form-card {
    border: 1px solid var(--gray-200);
    border-radius: 12px;
    transition: all 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: fadeInUp 0.6s ease both;
}

.form-card:nth-child(1) { animation-delay: 0.1s; }
.form-card:nth-child(2) { animation-delay: 0.2s; }
.form-card:nth-child(3) { animation-delay: 0.3s; }
.form-card:nth-child(n+4) { animation-delay: 0.4s; }

.form-card:hover {
    border-color: var(--primary);
    box-shadow: 0 12px 32px rgba(99, 102, 241, 0.15);
    transform: translateY(-4px);
}

.form-card-header {
    padding: 20px;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.form-card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-card-title i {
    color: var(--primary);
}

.form-card-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    color: var(--gray-600);
}

.form-card-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-card-meta-item i {
    width: 16px;
    text-align: center;
    color: var(--primary);
}

.form-card-body {
    flex: 1;
    padding: 20px;
}

.form-card-footer {
    padding: 16px 20px;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
    align-items: stretch;
    overflow-x: auto;
}

.form-card-btn {
    flex: 1;
    min-width: 70px;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 8px;
    border: 1px solid var(--gray-300);
    background: white;
    color: var(--gray-600);
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.form-card-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: #f0f4ff;
}

.form-card-btn-delete {
    border-color: #fecaca;
    color: #dc2626;
    background: white;
}

.form-card-btn-delete:hover {
    border-color: #ef4444;
    color: white;
    background: #ef4444;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    transform: scale(1.05);
}

/* SUBMISSIONS TABLE */
.submissions-section {
    animation: fadeInUp 0.6s ease 0.3s both;
}

.table-card {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.submissions-table {
    margin-bottom: 0;
}

.submissions-table thead th {
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    font-weight: 600;
    color: var(--gray-800);
    padding: 16px 20px;
}

.submissions-table tbody tr {
    border-bottom: 1px solid var(--gray-100);
    transition: background 0.2s;
}

.submissions-table tbody tr:hover {
    background: var(--gray-50);
}

.submissions-table td {
    padding: 16px 20px;
    vertical-align: middle;
}

.view-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, var(--info), #60a5fa);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.view-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* ANIMATIONS */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .dashboard-header h1 {
        font-size: 24px;
    }

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .forms-grid {
        grid-template-columns: 1fr;
    }

    .table-responsive {
        font-size: 13px;
    }
}
</style>

<div class="site-dashboard">
    <div class="dashboard-header">
        <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
        <?= Html::a('<i class="bi bi-plus-circle"></i> Create New Form', ['form/create'], [
            'class' => 'btn btn-lg',
            'style' => 'background: linear-gradient(135deg, #10b981, #34d399); color: white; border: none; font-weight: 600; border-radius: 8px; padding: 12px 24px;'
        ]) ?>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-stats">
        <div class="stat-card stat-card-primary shadow-sm">
            <div class="stat-card-body">
                <div class="stat-info">
                    <div class="stat-number"><?= $totalForms ?></div>
                    <div class="stat-label">Total Forms</div>
                </div>
                <i class="bi bi-file-earmark-text stat-icon"></i>
            </div>
        </div>
        
        <div class="stat-card stat-card-success shadow-sm">
            <div class="stat-card-body">
                <div class="stat-info">
                    <div class="stat-number"><?= $totalSubmissions ?></div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <i class="bi bi-inbox stat-icon"></i>
            </div>
        </div>
        
        <div class="stat-card stat-card-warning shadow-sm">
            <div class="stat-card-body">
                <div class="stat-info">
                    <div class="stat-number"><?= $todaySubmissions ?></div>
                    <div class="stat-label">Today's Submissions</div>
                </div>
                <i class="bi bi-calendar-check stat-icon"></i>
            </div>
        </div>
        
        <div class="stat-card stat-card-info shadow-sm">
            <div class="stat-card-body">
                <div class="stat-info">
                    <div class="stat-number"><?= count($recentForms) ?></div>
                    <div class="stat-label">Recent Forms</div>
                </div>
                <i class="bi bi-clock-history stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- My Forms Section -->
    <div class="forms-section">
        <div class="card border-0 shadow-sm table-card">
            <div class="section-header">
                <i class="bi bi-collection"></i> My Forms
            </div>
            
            <?php if (count($forms) == 0): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-slash empty-state-icon text-muted"></i>
                    <h3 class="empty-state-title">No forms yet</h3>
                    <p class="empty-state-text">Start creating your first form to get started</p>
                    <?= Html::a('<i class="bi bi-plus-circle"></i> Create Your First Form', ['form/create'], [
                        'class' => 'btn',
                        'style' => 'background: linear-gradient(135deg, #6366f1, #818cf8); color: white; border: none; font-weight: 500; border-radius: 8px; padding: 10px 20px;'
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="forms-grid p-4">
                    <?php foreach ($forms as $form): ?>
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5 class="form-card-title">
                                    <i class="bi bi-file-earmark-text"></i> <?= Html::encode($form->name) ?>
                                </h5>
                                <div class="form-card-meta">
                                    <div class="form-card-meta-item">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?= Yii::$app->formatter->asDate($form->created_at) ?></span>
                                    </div>
                                    <div class="form-card-meta-item">
                                        <i class="bi bi-sliders"></i>
                                        <span><?= count(json_decode($form->schema_js ?? '[]', true)) ?> Fields</span>
                                    </div>
                                    <div class="form-card-meta-item">
                                        <i class="bi bi-cursor-text"></i>
                                        <span><?= $form->submissions ? count($form->submissions) : 0 ?> Submissions</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-card-body">
                                <?php
                                $blocks = json_decode($form->schema_js ?? '[]', true);
                                $blockCount = count($blocks);
                                ?>
                                <p class="text-muted" style="font-size: 13px; margin: 0;">
                                    <strong><?= $blockCount ?></strong> block<?= $blockCount !== 1 ? 's' : '' ?> configured and ready to use
                                </p>
                            </div>
                            <div class="form-card-footer">
                                <?= Html::a(
                                    '<i class="bi bi-eye"></i> View',
                                    ['form/view', 'id' => $form->id],
                                    ['class' => 'form-card-btn']
                                ) ?>
                                <?= Html::a(
                                    '<i class="bi bi-pencil-square"></i> Edit',
                                    ['form/update', 'id' => $form->id],
                                    ['class' => 'form-card-btn']
                                ) ?>
                                <?= Html::a(
                                    '<i class="bi bi-play-fill"></i> Fill',
                                    ['form/render', 'id' => $form->id],
                                    ['class' => 'form-card-btn']
                                ) ?>
                                <?= Html::a(
                                    '<i class="bi bi-trash"></i> Delete',
                                    ['form/delete', 'id' => $form->id],
                                    [
                                        'class' => 'form-card-btn form-card-btn-delete',
                                        'data' => [
                                            'confirm' => 'Are you sure you want to delete this form? This action cannot be undone.',
                                            'method' => 'post',
                                        ]
                                    ]
                                ) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Submissions Section -->
    <?php if (!empty($recentSubmissions)): ?>
    <div class="submissions-section mt-4">
        <div class="card border-0 shadow-sm table-card">
            <div class="section-header">
                <i class="bi bi-inbox"></i> Recent Submissions
            </div>
            <div style="overflow-x: auto;">
                <table class="submissions-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;"><i class="bi bi-file-earmark"></i> Form</th>
                            <th style="width: 25%;"><i class="bi bi-calendar2"></i> Submitted</th>
                            <th style="width: 25%;"><i class="bi bi-person"></i> Submissions</th>
                            <th style="width: 20%; text-align: right;"><i class="bi bi-actions"></i> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSubmissions as $submission): ?>
                        <tr>
                            <td>
                                <strong><?= Html::encode($submission->form->name) ?></strong>
                            </td>
                            <td>
                                <span class="text-muted"><?= Yii::$app->formatter->asRelativeTime($submission->created_at) ?></span>
                            </td>
                            <td>
                                <span class="badge" style="background: linear-gradient(135deg, #6366f1, #818cf8); color: white; font-size: 12px; padding: 6px 12px;">
                                    <?= $submission->form->submissions ? count($submission->form->submissions) : 0 ?> responses
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <?= Html::a(
                                    '<i class="bi bi-eye"></i> View Submissions',
                                    ['form/submissions', 'id' => $submission->form_id],
                                    ['class' => 'view-btn']
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

