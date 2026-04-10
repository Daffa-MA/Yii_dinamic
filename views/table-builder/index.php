<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable[] $tables */

use yii\bootstrap5\Html;

$this->title = 'Database Tables';
?>

<div class="table-builder-index" style="animation: fadeInUp 0.6s ease;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">🗄️ Database Tables</h1>
            <p class="text-muted mb-0">Create and manage database tables for your forms</p>
        </div>
        <?= Html::a('<i class="bi bi-plus-circle"></i> Create Table', ['table-builder/create'], [
            'class' => 'btn btn-primary btn-lg',
            'style' => 'animation: pulse 2s infinite;'
        ]) ?>
    </div>

    <?php if (empty($tables)): ?>
        <div class="empty-state" style="animation: fadeIn 0.8s ease;">
            <div style="font-size: 64px; margin-bottom: 20px;">🗃️</div>
            <h3 class="empty-state-title">No tables yet</h3>
            <p class="empty-state-text">Create your first database table to store form data</p>
            <?= Html::a('<i class="bi bi-plus-circle"></i> Create First Table', ['table-builder/create'], [
                'class' => 'btn btn-success btn-lg'
            ]) ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($tables as $item): ?>
                <?php $table = $item['table']; $columns = $item['columns']; ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card table-card" style="animation: fadeInUp 0.6s ease;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="bi bi-table text-primary"></i> 
                                        <?= Html::encode($table->name) ?>
                                    </h5>
                                    <small class="text-muted"><?= Html::encode($table->label) ?></small>
                                </div>
                                <?php if ($table->is_created): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Created</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($table->description): ?>
                                <p class="card-text text-muted small mb-3"><?= Html::encode($table->description) ?></p>
                            <?php endif; ?>
                            
                            <div class="table-stats mb-3">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="bi bi-list-ul"></i> <?= count($columns) ?> columns</span>
                                    <span><i class="bi bi-gear"></i> <?= $table->engine ?></span>
                                </div>
                            </div>
                            
                            <div class="columns-preview mb-3">
                                <?php foreach (array_slice($columns, 0, 3) as $col): ?>
                                    <span class="badge bg-light text-dark me-1 mb-1">
                                        <?= Html::encode($col->name) ?>
                                        <small class="text-muted">(<?= $col->type ?>)</small>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($columns) > 3): ?>
                                    <span class="badge bg-secondary">+<?= count($columns) - 3 ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <div class="d-flex gap-2" style="flex-wrap: wrap;">
                                <?= Html::a('<i class="bi bi-eye"></i> View', ['table-builder/view', 'id' => $table->id], [
                                    'class' => 'btn btn-sm btn-outline-primary flex-grow-1',
                                ]) ?>
                                <?= Html::a('<i class="bi bi-pencil"></i> Edit', ['table-builder/update', 'id' => $table->id], [
                                    'class' => 'btn btn-sm btn-outline-warning flex-grow-1',
                                ]) ?>
                                <?php if (!$table->is_created): ?>
                                    <?= Html::a('<i class="bi bi-play-fill"></i> Create', ['table-builder/execute-sql', 'id' => $table->id], [
                                        'class' => 'btn btn-sm btn-outline-success flex-grow-1',
                                        'data' => ['confirm' => 'Create this table in database?']
                                    ]) ?>
                                <?php endif; ?>
                                <?= Html::a('<i class="bi bi-trash"></i> Delete', ['table-builder/delete', 'id' => $table->id], [
                                    'class' => 'btn btn-sm btn-outline-danger flex-grow-1',
                                    'data' => ['confirm' => 'Delete this table?', 'method' => 'post']
                                ]) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.table-card {
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

.table-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    border-color: #2563eb;
}

.table-card .card-footer {
    padding: 1rem;
}

.table-card .btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.table-card .btn-outline-primary:hover {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
}

.table-card .btn-outline-warning:hover {
    background-color: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

.table-card .btn-outline-success:hover {
    background-color: #10b981;
    color: white;
    border-color: #10b981;
}

.table-card .btn-outline-danger:hover {
    background-color: #ef4444;
    color: white;
    border-color: #ef4444;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.empty-state-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 10px;
}

.empty-state-text {
    color: #6b7280;
    margin-bottom: 20px;
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
</style>
