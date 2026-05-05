<?php

use app\models\Form;
use yii\helpers\Html;
use yii\db\ActiveRecord;

/* @var $this yii\web\View */
/* @var $page app\models\MasterPage */
/* @var $forms Form[] */

// Get safe attributes
if (isset($page) && $page instanceof ActiveRecord) {
    $attrs = $page->getAttributes();
} else {
    $attrs = isset($page) ? (array) $page : [];
}

$pageName = $attrs['name'] ?? 'Page';
$pageLayout = $attrs['layout'] ?? '-';
$pageDesc = $attrs['description'] ?? '';
$pageId = $attrs['id'] ?? null;
$pageIsActive = isset($attrs['is_active']) && $attrs['is_active'] == 1;

$this->title = $pageName;
$this->params['breadcrumbs'][] = ['label' => 'Master Halaman', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="bg-gray-100 py-8">
    <div class="mx-auto max-w-4xl px-4">
        <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white">
                        <span class="material-symbols-outlined">dashboard_customize</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900"><?= Html::encode($pageName) ?></h1>
                        <p class="mt-1 text-sm text-slate-500">Detail halaman dinamis dan daftar form yang akan tampil di menu ini.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?= Html::a('Preview Halaman', ['/page/view', 'id' => $pageId], [
                        'class' => 'rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white no-underline transition hover:bg-indigo-700',
                        'target' => '_blank',
                    ]) ?>
                    <?= Html::a('Ubah', ['update', 'id' => $pageId], [
                        'class' => 'rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 no-underline transition hover:bg-slate-50',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-slate-400">Konfigurasi</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Layout</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900"><?= Html::encode($pageLayout) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <p class="mt-1">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $pageIsActive ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $pageIsActive ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Deskripsi</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600"><?= !empty($pageDesc) ? Html::encode($pageDesc) : 'Belum ada deskripsi.' ?></p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-slate-400">Form Terpasang</h2>
                <?php if (!empty($forms)): ?>
                    <div class="mt-4 flex flex-col gap-3">
                        <?php foreach ($forms as $form): ?>
                            <?php 
                            $formAttrs = $form instanceof ActiveRecord ? $form->getAttributes() : (array) $form;
                            $formName = $formAttrs['name'] ?? 'Form';
                            $formId = $formAttrs['id'] ?? null;
                            ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 p-3">
                                <span class="font-medium text-slate-700"><?= Html::encode($formName) ?></span>
                                <?= Html::a('Lihat', ['/form/view', 'id' => $formId], ['class' => 'text-sm text-indigo-600 no-underline hover:underline']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-4 text-sm text-slate-500">Belum ada form dipasang.</p>
                <?php endif; ?>
                
                <div class="mt-6 border-t border-slate-100 pt-4">
                    <?= Html::a('+ Tambah Form', ['/form/create', 'page_id' => $pageId], [
                        'class' => 'block rounded-xl bg-indigo-50 px-4 py-2.5 text-center text-sm font-semibold text-indigo-600 no-underline transition hover:bg-indigo-100',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>