<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

$this->title = $isNew ? 'Tambah Chart' : 'Edit Chart: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Halaman', 'url' => ['/master-page/index']];
$this->params['breadcrumbs'][] = ['label' => $model->page_id && $model->page ? $model->page->title : 'Pilih Halaman', 'url' => ['index', 'page_id' => $model->page_id]];
$this->params['breadcrumbs'][] = $isNew ? 'Tambah Chart' : 'Edit Chart';
?>
<div class="master-chart-form max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900"><?= Html::encode($this->title) ?></h1>
        <p class="text-sm text-slate-500">Halaman: <strong><?= $model->page ? Html::encode($model->page->title) : 'Belum dipilih' ?></strong></p>
    </div>

    <?php $form = \yii\widgets\ActiveForm::begin(['options' => ['class' => 'space-y-6']]); ?>

    <?php if (!$model->page_id && isset($pages)): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Pilih Halaman</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $form->field($model, 'page_id')->dropDownList(
                \yii\helpers\ArrayHelper::map($pages, 'id', 'name'),
                ['prompt' => '-- Pilih Halaman --', 'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']
            ) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Informasi Chart</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $form->field($model, 'title')->textInput(['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'subtitle')->textInput(['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'chart_type')->dropDownList([
                'bar' => 'Bar Vertical',
                'bar_horizontal' => 'Bar Horizontal',
                'line' => 'Line',
                'area' => 'Area',
                'pie' => 'Pie',
                'donut' => 'Donut',
                'radar' => 'Radar',
                'polar_area' => 'Polar Area',
                'bubble' => 'Bubble',
                'scatter' => 'Scatter',
                'stacked_bar' => 'Stacked Bar',
                'stacked_area' => 'Stacked Area',
                'mixed' => 'Mixed (Bar + Line)',
                'multi_series' => 'Multi Series',
            ], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'position')->textInput(['type' => 'number', 'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'height')->textInput(['type' => 'number', 'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'is_active')->dropDownList([1 => 'Aktif', 0 => 'Nonaktif'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Sumber Data</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $form->field($model, 'table_id')->dropDownList($tables, ['prompt' => '-- Pilih Tabel --', 'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-table-id']) ?>
            <?= $form->field($model, 'source_type')->dropDownList([
                'table' => 'Table',
                'query' => 'Custom Query',
            ], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-source-type']) ?>
            <?= $form->field($model, 'source_query')->textarea(['rows' => 4, 'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-mono focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-source-query', 'style' => $model->source_type === 'query' ? '' : 'display:none;']) ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm chart-fields-section">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Field & Agregasi</h3>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $form->field($model, 'label_field')->textInput(['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-label-field']) ?>
            <?= $form->field($model, 'value_field')->textInput(['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-value-field']) ?>
            <?= $form->field($model, 'aggregation')->dropDownList([
                'count' => 'Count',
                'sum' => 'Sum',
                'avg' => 'Average',
                'min' => 'Minimum',
                'max' => 'Maximum',
                'count_distinct' => 'Count Distinct',
            ], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'group_by_field')->textInput(['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-group-field']) ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Sortir & Batas</h3>
        <div class="grid gap-5 sm:grid-cols-3">
            <?= $form->field($model, 'sort_field')->textInput(['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100', 'id' => 'chart-sort-field']) ?>
            <?= $form->field($model, 'sort_direction')->dropDownList(['asc' => 'Ascending', 'desc' => 'Descending'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'limit')->textInput(['type' => 'number', 'class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-slate-800">Tampilan</h3>
        <div class="grid gap-5 sm:grid-cols-3">
            <?= $form->field($model, 'theme')->dropDownList(['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'palette')->dropDownList([
                'modern' => 'Modern',
                'material' => 'Material',
                'pastel' => 'Pastel',
                'dark' => 'Dark',
                'gradient' => 'Gradient',
                'random' => 'Random',
            ], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'animation')->dropDownList([
                'fade' => 'Fade In',
                'zoom' => 'Zoom',
                'slide' => 'Slide',
                'bounce' => 'Bounce',
                'none' => 'None',
            ], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
        </div>
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?= $form->field($model, 'show_legend')->dropDownList([1 => 'Ya', 0 => 'Tidak'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'show_label')->dropDownList([1 => 'Ya', 0 => 'Tidak'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'show_toolbar')->dropDownList([1 => 'Ya', 0 => 'Tidak'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'show_grid')->dropDownList([1 => 'Ya', 0 => 'Tidak'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
            <?= $form->field($model, 'show_total')->dropDownList([1 => 'Ya', 0 => 'Tidak'], ['class' => 'w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100']) ?>
        </div>
    </div>

    <div class="flex items-center justify-between gap-4">
        <a href="<?= $model->page_id ? Url::to(['index', 'page_id' => $model->page_id]) : Url::to(['/master-page/index']) ?>" class="rounded-xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
            <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">save</span>
            <?= $isNew ? 'Simpan Chart' : 'Update Chart' ?>
        </button>
    </div>

    <?php \yii\widgets\ActiveForm::end(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tableSelect = document.getElementById('chart-table-id');
    var sourceType = document.getElementById('chart-source-type');
    var sourceQuery = document.getElementById('chart-source-query');
    var labelField = document.getElementById('chart-label-field');
    var valueField = document.getElementById('chart-value-field');
    var groupField = document.getElementById('chart-group-field');
    var sortField = document.getElementById('chart-sort-field');

    function toggleSourceQuery() {
        if (sourceType && sourceQuery) {
            sourceQuery.style.display = sourceType.value === 'query' ? '' : 'none';
        }
    }

    function loadFields() {
        var tableId = tableSelect ? tableSelect.value : '';
        if (!tableId) return;
        fetch('/master-chart/fields?table_id=' + encodeURIComponent(tableId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(fields) {
            if (!Array.isArray(fields)) return;
            var stringFields = fields.filter(function(f) { return f.is_string; }).map(function(f) { return f.name; });
            var numericFields = fields.filter(function(f) { return f.is_numeric; }).map(function(f) { return f.name; });
            var allFields = fields.map(function(f) { return f.name; });

            if (!labelField.value && stringFields.length) labelField.value = stringFields[0];
            if (!valueField.value && numericFields.length) valueField.value = numericFields[0];
            if (!groupField.value && stringFields.length) groupField.value = stringFields[0];
            if (!sortField.value && allFields.length) sortField.value = allFields[0];
        })
        .catch(function() {});
    }

    if (sourceType) sourceType.addEventListener('change', toggleSourceQuery);
    if (tableSelect) tableSelect.addEventListener('change', loadFields);
    toggleSourceQuery();
    if (tableSelect && tableSelect.value) loadFields();
});
</script>
