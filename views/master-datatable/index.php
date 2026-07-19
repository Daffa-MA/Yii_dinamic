<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var app\models\MasterDatatable[] $models */
/** @var string|null $search */

$this->title = 'Master Datatable';
?>

<div class="mx-auto max-w-7xl px-6 py-8">
    <div class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h1 class="m-0 text-2xl font-bold text-slate-900">Master Datatable</h1>
            <p class="mt-1 text-sm text-slate-500">Preset table view yang bisa dipakai ulang di custom page builder.</p>
        </div>
        <?= Html::a('Create Datatable', ['create'], ['class' => 'rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white no-underline']) ?>
    </div>

    <form method="get" action="<?= Url::to(['master-datatable/index']) ?>" class="mb-4">
        <div class="flex gap-2">
            <input type="text" name="q" value="<?= Html::encode($search ?? '') ?>" placeholder="Cari datatable..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" autocomplete="off">
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white">Cari</button>
            <?php if ($search): ?>
                <?= Html::a('Clear', ['index'], ['class' => 'rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-600 no-underline']) ?>
            <?php endif; ?>
        </div>
    </form>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>

    <div class="grid gap-4">
        <?php if ($search && empty($models)): ?>
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
                <strong class="block text-slate-900">Hasil tidak ditemukan.</strong>
                Tidak ada datatable yang cocok dengan "<strong><?= Html::encode($search) ?></strong>".
            </div>
        <?php elseif (empty($models)): ?>
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
                <strong class="block text-slate-900">No datatable presets yet.</strong>
                Buat preset pertama untuk digunakan di halaman dinamis.
            </div>
        <?php endif; ?>
        <?php foreach ($models as $model): ?>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="m-0 text-lg font-bold text-slate-900"><?= Html::encode($model->name) ?></h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Source: <?= Html::encode($model->table->label ?? $model->table->name ?? ('Table #' . $model->table_id)) ?>
                            · <?= $model->is_active ? 'Active' : 'Inactive' ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 no-underline']) ?>
                        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                            'class' => 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 no-underline',
                            'data-method' => 'post',
                            'data-confirm' => 'Delete this datatable preset?',
                        ]) ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
