<?php

use yii\helpers\Html;

/** @var app\models\MasterDatatable $model */
/** @var app\models\DbTable[] $tables */
/** @var app\models\MasterForm[] $forms */

$this->title = $model->isNewRecord ? 'Create Master Datatable' : 'Edit Master Datatable';
$columnsConfig = $model->getColumnsConfigArray();
$actionsConfig = $model->getActionsConfigArray();
$filtersConfig = $model->getFiltersConfigArray();
$statsConfig = $model->getStatsConfigArray();
$workflowConfig = $model->getWorkflowConfigArray();
$ownershipConfig = $model->getOwnershipConfigArray();
$selectedTableId = (int)$model->table_id;
$forms = $forms ?? [];
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

        <div class="mb-5 rounded-2xl border border-slate-200">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="m-0 text-sm font-bold text-slate-900">Filters & Statistics</h2>
                <p class="m-0 mt-1 text-xs text-slate-500">Pilih field yang bisa difilter dan field yang akan dihitung sebagai statistik/grouping.</p>
            </div>
            <div class="grid gap-4 p-4 md:grid-cols-2">
                <div>
                    <h3 class="mb-3 text-sm font-bold text-slate-700">Filters</h3>
                    <div class="space-y-2">
                        <?php foreach ($tables as $table): ?>
                            <?php foreach ($table->columns as $column): ?>
                                <?php
                                $existing = null;
                                foreach ($filtersConfig as $item) {
                                    if (($item['field'] ?? '') === $column->name) {
                                        $existing = $item;
                                        break;
                                    }
                                }
                                $isSelectedTable = (int)$table->id === $selectedTableId;
                                ?>
                                <div class="column-row rounded-xl border border-slate-100 p-3" data-table-id="<?= (int)$table->id ?>" style="<?= $isSelectedTable ? '' : 'display:none;' ?>">
                                    <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input type="hidden" name="MasterDatatable[filters][<?= Html::encode($column->name) ?>][field]" value="<?= Html::encode($column->name) ?>" <?= $isSelectedTable ? '' : 'disabled' ?>>
                                        <input type="checkbox" name="MasterDatatable[filters][<?= Html::encode($column->name) ?>][enabled]" value="1" <?= $existing ? 'checked' : '' ?> <?= $isSelectedTable ? '' : 'disabled' ?>>
                                        <?= Html::encode($column->name) ?>
                                    </label>
                                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[filters][<?= Html::encode($column->name) ?>][label]" value="<?= Html::encode($existing['label'] ?? ($column->label ?: $column->name)) ?>" placeholder="Filter label" <?= $isSelectedTable ? '' : 'disabled' ?>>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h3 class="mb-3 text-sm font-bold text-slate-700">Statistics</h3>
                    <div class="space-y-2">
                        <?php foreach ($tables as $table): ?>
                            <?php foreach ($table->columns as $column): ?>
                                <?php
                                $existing = null;
                                foreach ($statsConfig as $item) {
                                    if (($item['field'] ?? '') === $column->name) {
                                        $existing = $item;
                                        break;
                                    }
                                }
                                $isSelectedTable = (int)$table->id === $selectedTableId;
                                ?>
                                <div class="column-row rounded-xl border border-slate-100 p-3" data-table-id="<?= (int)$table->id ?>" style="<?= $isSelectedTable ? '' : 'display:none;' ?>">
                                    <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input type="hidden" name="MasterDatatable[stats][<?= Html::encode($column->name) ?>][field]" value="<?= Html::encode($column->name) ?>" <?= $isSelectedTable ? '' : 'disabled' ?>>
                                        <input type="checkbox" name="MasterDatatable[stats][<?= Html::encode($column->name) ?>][enabled]" value="1" <?= $existing ? 'checked' : '' ?> <?= $isSelectedTable ? '' : 'disabled' ?>>
                                        <?= Html::encode($column->name) ?>
                                    </label>
                                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[stats][<?= Html::encode($column->name) ?>][label]" value="<?= Html::encode($existing['label'] ?? ($column->label ?: $column->name)) ?>" placeholder="Statistic label" <?= $isSelectedTable ? '' : 'disabled' ?>>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 grid gap-3 md:grid-cols-5">
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[search_enabled]" value="1" <?= $model->isNewRecord || $model->search_enabled ? 'checked' : '' ?>> Search</label>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[pagination_enabled]" value="1" <?= $model->isNewRecord || $model->pagination_enabled ? 'checked' : '' ?>> Pagination</label>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[ownership][enabled]" value="1" <?= !empty($ownershipConfig['enabled']) ? 'checked' : '' ?>> Hanya milik user</label>
            <p class="col-span-full text-xs font-normal text-slate-500">Saat diaktifkan, halaman hanya menampilkan data milik user yang sedang masuk. Penentuan "milik user" diterapkan secara otomatis menggunakan identitas pengguna aktif dan relasi tabel yang sudah terhubung di framework, jadi tidak perlu diatur manual.</p>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[actions][view]" value="1" <?= !empty($actionsConfig['view']) ? 'checked' : '' ?>> View</label>
            <div class="rounded-xl border border-slate-200 p-3 text-sm font-semibold">
                <label class="block"><input type="checkbox" name="MasterDatatable[actions][edit]" value="1" <?= !empty($actionsConfig['edit']) ? 'checked' : '' ?>> Edit</label>
                <select class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700" name="MasterDatatable[actions][edit_mode]">
                    <option value="custom" <?= (($actionsConfig['edit_mode'] ?? 'custom') === 'custom') ? 'selected' : '' ?>>Custom form modal</option>
                    <option value="default" <?= (($actionsConfig['edit_mode'] ?? 'custom') === 'default') ? 'selected' : '' ?>>Default modal</option>
                </select>
                <select class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700" name="MasterDatatable[actions][edit_form_id]">
                    <option value="">Pilih form edit</option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?= (int)$form['id'] ?>" <?= ((int)($actionsConfig['edit_form_id'] ?? 0) === (int)$form['id']) ? 'selected' : '' ?>>
                            <?= Html::encode($form['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-2 text-xs font-normal leading-5 text-slate-500">Pilih tampilan modal edit yang akan dipakai saat admin membuka action Edit.</p>
            </div>
            <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold"><input type="checkbox" name="MasterDatatable[actions][delete]" value="1" <?= !empty($actionsConfig['delete']) ? 'checked' : '' ?>> Delete</label>
        </div>

        <!-- Export Settings Section - Professional & Clean Design -->
        <div class="mb-6 rounded-2xl border border-slate-200 p-4">
            <h2 class="m-0 text-sm font-bold text-slate-900">Export Settings</h2>
            <p class="mt-1 text-xs text-slate-500">Aktifkan tombol export data yang rapi dan profesional untuk datatable ini.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-4">
                <label class="group relative flex flex-col items-center gap-2 rounded-xl border-2 border-slate-200 p-4 text-sm font-semibold transition-all duration-200 hover:border-emerald-300 hover:bg-emerald-50">
                    <input type="checkbox" name="MasterDatatable[exports][csv]" value="1" class="peer sr-only">
                    <div class="peer-checked:bg-emerald-600 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 transition-all duration-200 group-hover:bg-emerald-100">
                        <svg class="h-5 w-5 text-slate-500 group-hover:text-emerald-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-slate-700 group-hover:text-emerald-700 peer-checked:text-emerald-700">CSV</span>
                    <span class="text-[10px] font-normal text-slate-400">Export CSV</span>
                </label>
                <label class="group relative flex flex-col items-center gap-2 rounded-xl border-2 border-slate-200 p-4 text-sm font-semibold transition-all duration-200 hover:border-green-300 hover:bg-green-50">
                    <input type="checkbox" name="MasterDatatable[exports][excel]" value="1" class="peer sr-only">
                    <div class="peer-checked:bg-green-600 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 transition-all duration-200 group-hover:bg-green-100">
                        <svg class="h-5 w-5 text-slate-500 group-hover:text-green-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-slate-700 group-hover:text-green-700 peer-checked:text-green-700">Excel</span>
                    <span class="text-[10px] font-normal text-slate-400">Export Excel</span>
                </label>
                <label class="group relative flex flex-col items-center gap-2 rounded-xl border-2 border-slate-200 p-4 text-sm font-semibold transition-all duration-200 hover:border-red-300 hover:bg-red-50">
                    <input type="checkbox" name="MasterDatatable[exports][pdf]" value="1" class="peer sr-only">
                    <div class="peer-checked:bg-red-600 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 transition-all duration-200 group-hover:bg-red-100">
                        <svg class="h-5 w-5 text-slate-500 group-hover:text-red-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0011.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-slate-700 group-hover:text-red-700 peer-checked:text-red-700">PDF</span>
                    <span class="text-[10px] font-normal text-slate-400">Export PDF</span>
                </label>
                <label class="group relative flex flex-col items-center gap-2 rounded-xl border-2 border-slate-200 p-4 text-sm font-semibold transition-all duration-200 hover:border-blue-300 hover:bg-blue-50">
                    <input type="checkbox" name="MasterDatatable[exports][print]" value="1" class="peer sr-only">
                    <div class="peer-checked:bg-blue-600 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 transition-all duration-200 group-hover:bg-blue-100">
                        <svg class="h-5 w-5 text-slate-500 group-hover:text-blue-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <span class="text-slate-700 group-hover:text-blue-700 peer-checked:text-blue-700">Print</span>
                    <span class="text-[10px] font-normal text-slate-400">Cetak Laporan</span>
                </label>
            </div>
        </div>

        <label class="mb-6 block rounded-xl border border-slate-200 p-3 text-sm font-semibold">
            <input type="checkbox" name="MasterDatatable[is_active]" value="1" <?= $model->isNewRecord || $model->is_active ? 'checked' : '' ?>> Active
        </label>

        <div class="mb-6 rounded-2xl border border-slate-200 p-4">
            <h2 class="m-0 text-sm font-bold text-slate-900">Workflow Approval</h2>
            <p class="mt-1 text-xs text-slate-500">Aktifkan untuk proses persetujuan generic berbasis field status pada source table.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-5">
                <label class="rounded-xl border border-slate-200 p-3 text-sm font-semibold">
                    <input type="checkbox" name="MasterDatatable[workflow][approval_enabled]" value="1" <?= !empty($workflowConfig['approval_enabled']) ? 'checked' : '' ?>> Approval
                </label>
                <input class="rounded-xl border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[workflow][status_field]" value="<?= Html::encode($workflowConfig['status_field'] ?? '') ?>" placeholder="status field">
                <input class="rounded-xl border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[workflow][pending_value]" value="<?= Html::encode($workflowConfig['pending_value'] ?? 'pending') ?>" placeholder="pending value">
                <input class="rounded-xl border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[workflow][approved_value]" value="<?= Html::encode($workflowConfig['approved_value'] ?? 'approved') ?>" placeholder="approved value">
                <input class="rounded-xl border border-slate-300 px-3 py-2 text-sm" name="MasterDatatable[workflow][button_label]" value="<?= Html::encode($workflowConfig['button_label'] ?? 'Approve') ?>" placeholder="button label">
            </div>
        </div>

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