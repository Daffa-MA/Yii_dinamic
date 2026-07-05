<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Chart - ' . $page->title;
$this->params['breadcrumbs'][] = ['label' => 'Halaman', 'url' => ['/master-page/index']];
$this->params['breadcrumbs'][] = ['label' => $page->title, 'url' => ['/master-page/update', 'id' => $page->id]];
$this->params['breadcrumbs'][] = 'Chart';
?>
<div class="master-chart-index">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chart Dashboard</h1>
            <p class="text-sm text-slate-500">Halaman: <strong><?= Html::encode($page->title) ?></strong></p>
        </div>
        <a href="<?= Url::to(['create', 'page_id' => $page->id]) ?>" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white no-underline transition hover:bg-indigo-700">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span>
            Tambah Chart
        </a>
    </div>

    <?php if (empty($charts)): ?>
        <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-50 text-slate-400">
                <span class="material-symbols-outlined text-3xl">bar_chart</span>
            </div>
            <h3 class="mt-4 text-lg font-bold text-slate-800">Belum Ada Chart</h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                Tambahkan chart untuk menampilkan visualisasi data di halaman ini.
            </p>
            <div class="mt-6">
                <a href="<?= Url::to(['create', 'page_id' => $page->id]) ?>" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white no-underline transition hover:bg-indigo-700">
                    <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                    Tambah Chart Pertama
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($charts as $chart): ?>
                <?php $config = $chart->getRenderConfig(); ?>
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-bold text-slate-900"><?= Html::encode($chart->title) ?></h3>
                                <?php if ($chart->subtitle): ?>
                                    <p class="mt-0.5 truncate text-xs text-slate-500"><?= Html::encode($chart->subtitle) ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700"><?= Html::encode($chart->chart_type) ?></span>
                        </div>
                    </div>
                    <div class="px-5 py-4 text-xs text-slate-500 space-y-1">
                        <div>Table: <span class="font-medium text-slate-700"><?= Html::encode($config['table_name'] ?: 'ID ' . $chart->table_id) ?></span></div>
                        <div>Agregasi: <span class="font-medium text-slate-700"><?= Html::encode($chart->aggregation) ?></span></div>
                        <div>Group By: <span class="font-medium text-slate-700"><?= Html::encode($chart->group_by_field ?: '-') ?></span></div>
                        <div>Posisi: <span class="font-medium text-slate-700"><?= (int)$chart->position ?></span></div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
                        <a href="<?= Url::to(['update', 'id' => $chart->id]) ?>" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Edit</a>
                        <a href="<?= Url::to(['delete', 'id' => $chart->id]) ?>" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50" data-confirm="Hapus chart ini?">Hapus</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
