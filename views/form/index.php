<?php

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */

use yii\bootstrap5\Html;

$this->title = 'My Forms';
?>

<style>
    body {
        background: linear-gradient(135deg, #f0f4ff 0%, #fff5e6 100%) !important;
        min-height: 100vh;
    }
    
    .form-index {
        padding: 40px 20px;
    }
</style>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4" style="padding: 0 20px; margin-bottom: 40px;">
    <div>
        <h1 class="display-5" style="font-weight: 700; color: #1f2937; margin-bottom: 8px;">
            <i class="fas fa-list" style="margin-right: 12px; color: #2563eb;"></i>My Forms
        </h1>
        <p style="color: #6b7280; margin: 0;">Manage and edit your forms</p>
    </div>
    <?= Html::a('<i class="fas fa-plus" style="margin-right: 8px;"></i> Create New Form', ['form/create'], [
        'class' => 'btn btn-primary btn-lg animate-fade-in',
        'style' => 'border-radius: 10px; padding: 12px 28px; font-weight: 600;'
    ]) ?>

</div>

<!-- Search Bar -->
<div style="padding: 0 20px; margin-bottom: 30px;">
    <div class="card" style="border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div class="card-body" style="padding: 20px;">
            <?= Html::beginForm(['form/index'], 'get', ['class' => 'd-flex gap-2']) ?>
            <div style="flex: 1;">
                <?= Html::input('text', 'q', $search ?? null, [
                    'class' => 'form-control',
                    'placeholder' => 'Search forms by name...',
                    'style' => 'border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 16px; font-size: 14px;'
                ]) ?>
            </div>
            <?= Html::submitButton('<i class="fas fa-search"></i> Search', [
                'class' => 'btn btn-primary',
                'style' => 'border-radius: 8px; padding: 10px 24px; font-weight: 600;'
            ]) ?>
            <?php if ($search): ?>
                <?= Html::a('<i class="fas fa-times"></i> Clear', ['form/index'], [
                    'class' => 'btn btn-outline-secondary',
                    'style' => 'border-radius: 8px; padding: 10px 24px; font-weight: 600;'
                ]) ?>
            <?php endif; ?>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<?php if (empty($forms)): ?>
    <!-- Empty State -->
    <div style="text-align: center; padding: 80px 20px;">
        <div style="font-size: 80px; margin-bottom: 24px; animation: bounce 2s ease-in-out infinite;">📋</div>
        <h3 style="font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 12px;">No Forms Yet</h3>
        <p style="font-size: 16px; color: #6b7280; margin-bottom: 32px;">Create your first form to get started</p>
        <?= Html::a('<i class="fas fa-sparkles"></i> Create Your First Form', ['form/create'], [
            'class' => 'btn btn-primary btn-lg',
            'style' => 'border-radius: 10px; padding: 14px 32px; font-weight: 600; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);'
        ]) ?>
    </div>
<?php else: ?>
    <!-- Forms Grid -->
    <div class="form-list-grid" style="padding: 0 20px;">
        <?php foreach ($forms as $form): ?>
            <?php 
                $fieldCount = count($form->getSchema());
                $submissionCount = count($form->submissions) ?? 0;
                $formDate = date('M d, Y', strtotime($form->created_at));
            ?>
            <div class="form-card animate-slide-in-up" style="animation-delay: 0s;">
                <!-- Card Header with Gradient -->
                <div class="form-card-header" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
                    <div class="form-card-title" title="<?= Html::encode($form->name) ?>">
                        <i class="fas fa-file-alt" style="margin-right: 8px;"></i> <?= Html::encode($form->name) ?>
                    </div>
                    <div class="form-card-meta">
                        Created on <?= $formDate ?>
                    </div>
                </div>

                <!-- Card Body - Stats -->
                <div class="form-card-body">
                    <div class="form-card-stat">
                        <span class="form-card-stat-label"><i class="fas fa-cube" style="margin-right: 6px;"></i> Fields</span>
                        <span class="form-card-stat-value"><?= $fieldCount ?></span>
                    </div>
                    <div class="form-card-stat">
                        <span class="form-card-stat-label"><i class="fas fa-inbox" style="margin-right: 6px;"></i> Submissions</span>
                        <span class="form-card-stat-value"><?= $submissionCount ?></span>
                    </div>
                    <div class="form-card-stat">
                        <span class="form-card-stat-label"><i class="fas fa-database" style="margin-right: 6px;"></i> Type</span>
                        <span class="form-card-stat-value">
                            <span class="badge" style="background: #dbeafe; color: #1e40af; font-size: 11px; padding: 4px 8px;">
                                <?= ucfirst($form->storage_type) ?>
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Card Footer - Actions -->
                <div class="form-card-footer">
                    <?= Html::a('<i class="fas fa-eye"></i> View', ['form/view', 'id' => $form->id], [
                        'class' => 'btn btn-sm btn-outline-primary',
                        'style' => 'flex: 1; border-radius: 6px; font-weight: 600;'
                    ]) ?>
                    <?= Html::a('<i class="fas fa-edit"></i> Edit', ['form/update', 'id' => $form->id], [
                        'class' => 'btn btn-sm btn-primary',
                        'style' => 'flex: 1; border-radius: 6px; font-weight: 600;'
                    ]) ?>
                    <div class="btn-group btn-group-sm" style="flex: 1;">
                        <?= Html::a('<i class="fas fa-play"></i>', ['form/render', 'id' => $form->id], [
                            'class' => 'btn btn-success',
                            'title' => 'Fill Form',
                            'style' => 'border-radius: 6px 0 0 6px; font-weight: 600;'
                        ]) ?>
                        <?= Html::a('<i class="fas fa-trash"></i>', ['form/delete', 'id' => $form->id], [
                            'class' => 'btn btn-danger',
                            'title' => 'Delete',
                            'style' => 'border-radius: 0 6px 6px 0; font-weight: 600;',
                            'data' => [
                                'confirm' => 'Delete this form?',
                                'method' => 'post',
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


