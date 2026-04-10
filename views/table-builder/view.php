<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable $model */
/** @var app\models\DbTableColumn[] $columns */
/** @var array $tableData */

use yii\bootstrap5\Html;

$this->title = 'Table: ' . $model->name;
?>

<div class="table-builder-view" style="animation: fadeInUp 0.6s ease;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">🗄️ <?= Html::encode($model->name) ?></h1>
            <p class="text-muted mb-0"><?= Html::encode($model->label) ?></p>
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-pencil"></i> Edit', ['table-builder/update', 'id' => $model->id], [
                'class' => 'btn btn-warning'
            ]) ?>
            <?php if (!$model->is_created): ?>
                <?= Html::a('<i class="bi bi-play-fill"></i> Execute SQL', ['table-builder/execute-sql', 'id' => $model->id], [
                    'class' => 'btn btn-success',
                    'data' => ['confirm' => 'Create this table in database?']
                ]) ?>
            <?php else: ?>
                <span class="badge bg-success btn" style="cursor:default"><i class="bi bi-check-circle"></i> Created in DB</span>
            <?php endif; ?>
            <?= Html::a('<i class="bi bi-arrow-left"></i> Back', ['table-builder/index'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>
    </div>

    <?php if ($model->description): ?>
        <div class="card mb-4" style="animation: fadeIn 0.8s ease;">
            <div class="card-body">
                <p class="mb-0 text-muted"><?= Html::encode($model->description) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Table Data -->
        <div class="col-lg-12">
            <div class="card mb-4" style="animation: fadeInUp 0.6s ease 0.2s both;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table"></i> Table Data (<?= count($tableData) ?> rows)</h5>
                    <?php if ($model->is_created): ?>
                        <button class="btn btn-sm btn-light" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (!$model->is_created): ?>
                        <div class="text-center text-muted py-5">
                            <p>Table not created in database yet. Click "Execute SQL" to create it.</p>
                        </div>
                    <?php elseif (empty($tableData)): ?>
                        <div class="text-center text-muted py-5">
                            <div style="font-size:48px;margin-bottom:12px;">📭</div>
                            <p>No data in this table yet.</p>
                            <p class="small">Use forms to add data, or insert via SQL.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <?php foreach ($columns as $col): ?>
                                            <th>
                                                <?= Html::encode($col->name) ?>
                                                <small class="text-muted d-block"><?= $col->type ?><?= $col->length ? '('.$col->length.')' : '' ?></small>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableData as $rowIndex => $row): ?>
                                        <tr>
                                            <td><?= $rowIndex + 1 ?></td>
                                            <?php foreach ($columns as $col): ?>
                                                <td>
                                                    <?php
                                                    $value = $row[$col->name] ?? '-';
                                                    if ($value === null) {
                                                        echo '<span class="text-muted">NULL</span>';
                                                    } elseif ($col->type === 'BOOLEAN' || $col->type === 'TINYINT') {
                                                        echo $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
                                                    } elseif (is_array($value)) {
                                                        echo '<code>' . Html::encode(json_encode($value)) . '</code>';
                                                    } else {
                                                        echo Html::encode($value);
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columns Structure -->
        <div class="col-md-8">
            <div class="card" style="animation: fadeInUp 0.6s ease 0.3s both;">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Columns (<?= count($columns) ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($columns)): ?>
                        <div class="text-center text-muted py-5">
                            <p>No columns defined.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Nullable</th>
                                    <th>Default</th>
                                    <th>Flags</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columns as $index => $col): ?>
                                    <tr style="animation: fadeIn 0.3s ease <?= $index * 0.05 ?>s both;">
                                        <td><?= $index + 1 ?></td>
                                        <td><code><?= Html::encode($col->name) ?></code></td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= Html::encode($col->type) ?>
                                                <?php if ($col->length): ?>
                                                    (<?= $col->length ?>)
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td><?= $col->is_nullable ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>' ?></td>
                                        <td><code><?= Html::encode($col->default_value ?: 'NULL') ?></code></td>
                                        <td>
                                            <?php if ($col->is_primary): ?><span class="badge bg-primary me-1">PK</span><?php endif; ?>
                                            <?php if ($col->is_unique): ?><span class="badge bg-info">UQ</span><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Table Info -->
        <div class="col-md-4">
            <div class="card mb-4" style="animation: fadeInUp 0.6s ease 0.4s both;">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Table Info</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th width="120">Engine</th><td><?= $model->engine ?></td></tr>
                        <tr><th>Charset</th><td><?= $model->charset ?></td></tr>
                        <tr><th>Collation</th><td><?= $model->collation ?></td></tr>
                        <tr><th>Status</th><td><?= $model->is_created ? '<span class="text-success">Created</span>' : '<span class="text-warning">Pending</span>' ?></td></tr>
                        <tr><th>Rows</th><td><?= count($tableData) ?></td></tr>
                        <tr><th>Created</th><td><?= date('M d, Y H:i', strtotime($model->created_at)) ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card" style="animation: fadeInUp 0.6s ease 0.5s both;">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-code-slash"></i> SQL</h6>
                </div>
                <div class="card-body">
                    <pre id="sql-preview" class="bg-dark text-light p-3 rounded" style="font-size:12px;max-height:300px;overflow:auto;">Loading...</pre>
                    <button class="btn btn-sm btn-outline-light mt-2" onclick="copySQL()">
                        <i class="bi bi-clipboard"></i> Copy SQL
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load SQL preview
fetch('<?= \yii\helpers\Url::to(["table-builder/preview-sql", "id" => $model->id]) ?>')
    .then(r => r.json())
    .then(data => {
        document.getElementById('sql-preview').textContent = data.sql;
    });

function copySQL() {
    const sql = document.getElementById('sql-preview').textContent;
    navigator.clipboard.writeText(sql).then(() => {
        alert('SQL copied to clipboard!');
    });
}
</script>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
