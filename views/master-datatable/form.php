<?php

use yii\helpers\Html;

/** @var app\models\MasterDatatable $model */
/** @var app\models\DbTable[] $tables */

$this->title = $model->isNewRecord ? 'Create Master Datatable' : 'Edit Master Datatable';
$columnsConfig = $model->getColumnsConfigArray();
$actionsConfig = $model->getActionsConfigArray();
$selectedTableId = (int)$model->table_id;
?>

<div class="mx-auto max-w-5xl px-6 py-8">
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h1 class="m-0 text-2xl font-bold text-slate-900"><?= Html::encode($this->title) ?></h1>
        <p class="mt-1 text-sm text-slate-500">Pilih source table, kolom yang tampil, label header, dan action yang tersedia.</p>
    </div>

    <form method="post" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <input type="hidden" name="<?= Html::encode(Yii::$app->request->csrfParam) ?>" value="<?= Html::encode(Yii::$app->request->csrfToken) ?>">

        <div class="mb-5">
            <label class="mb-2 block text-sm font-bold text-slate-700">Datatable Name</label>
            <input class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" name="MasterDatatable[name]" value="<?= Html::encode($model->name) ?>" required>
        </div>

        <div class="mb-5">
            <label class="mb-2 block text-sm font-bold text-slate-700">Source Table</label>
            <select class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" name="MasterDatatable[table_id]" id="source-table" required>
                <option value="">Select table</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?= (int)$table->id ?>" <?= $selectedTableId === (int)$table->id ? 'selected' : '' ?>><?= Html::encode(($table->label ?: $table->name) . ' (' . $table->name . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-5 rounded-2xl border border-slate-200">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="m-0 text-sm font-bold text-slate-900">Columns</h2>
            </div>
            <div class="divide-y divide-slate-100" id="columns-panel">
                <?php foreach ($tables as $table): ?>
                    <?php foreach ($table->columns as $column): ?>
                        <?php
                        $existing = null;
                        foreach ($columnsConfig as $item) {
                            if (($item['field'] ?? '') === $column->name) {
                                $existing = $item;
                                break;
                            }
                        }
                        $visible = $existing ? !empty($existing['visible']) : ((int)$table->id === $selectedTableId);
                        $label = $existing['label'] ?? ($column->label ?: $column->name);
                        ?>
                        <?php $isSelectedTable = (int)$table->id === $selectedTableId; ?>
                        <div class="column-row flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center" data-table-id="<?= (int)$table->id ?>" style="<?= $isSelectedTable ? '' : 'display:none;' ?>">
                            <label class="flex min-w-[220px] items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="hidden" name="MasterDatatable[columns][<?= Html::encode($column->name) ?>][field]" value="<?= Html::encode($column->name) ?>" <?= $isSelectedTable ? '' : 'disabled' ?>>
                                <input type="checkbox" name="MasterDatatable[columns][<?= Html::encode($column->name) ?>][visible]" value="1" <?= $visible ? 'checked' : '' ?> <?= $isSelectedTable ? '' : 'disabled' ?>>
                                <?= Html::encode($column->name) ?>
                            </label>
                            <input class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[columns][<?= Html::encode($column->name) ?>][label]" value="<?= Html::encode($label) ?>" placeholder="Custom header" <?= $isSelectedTable ? '' : 'disabled' ?>>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-6 grid gap-3 md:grid-cols-5">
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[search_enabled]" value="1" <?= $model->isNewRecord || $model->search_enabled ? 'checked' : '' ?>> Search</label>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[pagination_enabled]" value="1" <?= $model->isNewRecord || $model->pagination_enabled ? 'checked' : '' ?>> Pagination</label>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[actions][view]" value="1" <?= !empty($actionsConfig['view']) ? 'checked' : '' ?>> View</label>
            <div class="rounded-xl border border-slate-200 p-3 text-sm font-semibold">
                <label class="block"><input type="checkbox" name="MasterDatatable[actions][edit]" value="1" <?= !empty($actionsConfig['edit']) ? 'checked' : '' ?>> Edit</label>
                <select class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700" name="MasterDatatable[actions][edit_mode]">
                    <option value="custom" <?= (($actionsConfig['edit_mode'] ?? 'custom') === 'custom') ? 'selected' : '' ?>>Custom form modal</option>
                    <option value="default" <?= (($actionsConfig['edit_mode'] ?? 'custom') === 'default') ? 'selected' : '' ?>>Default modal</option>
                </select>
                <p class="mt-2 text-xs font-normal leading-5 text-slate-500">Pilih tampilan modal edit yang akan dipakai saat admin membuka action Edit.</p>
            </div>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[actions][delete]" value="1" <?= !empty($actionsConfig['delete']) ? 'checked' : '' ?>> Delete</label>
        </div>

        <label class="mb-6 block rounded-xl border border-slate-200 p-3 text-sm font-semibold">
            <input type="checkbox" name="MasterDatatable[is_active]" value="1" <?= $model->isNewRecord || $model->is_active ? 'checked' : '' ?>> Active
        </label>

        <div class="flex gap-3">
            <button class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white" type="submit">Save Datatable</button>
            <?= Html::a('Cancel', ['index'], ['class' => 'rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 no-underline']) ?>
        </div>
    </form>
</div>

<script>
function syncDatatableColumnRows() {
    const id = document.getElementById('source-table')?.value || '';
    document.querySelectorAll('.column-row').forEach(function(row) {
        const active = row.dataset.tableId === id;
        row.style.display = active ? '' : 'none';
        row.querySelectorAll('input,select,textarea').forEach(function(input) {
            input.disabled = !active;
        });
    });
}
document.getElementById('source-table')?.addEventListener('change', function() {
    syncDatatableColumnRows();
});
syncDatatableColumnRows();
</script>
