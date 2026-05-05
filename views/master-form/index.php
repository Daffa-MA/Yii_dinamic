<?php

use app\models\MasterForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Master Form';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);
?>
<div class="mx-auto max-w-7xl px-4 pb-12 pt-2">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-primary-container">Master Data</p>
            <h1 class="font-headline text-2xl font-extrabold tracking-tight text-on-surface md:text-3xl"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-on-surface-variant">Definisi form (slug + JSON) yang dapat dipasang ke halaman master.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?= Html::a(
                '<span class="material-symbols-outlined text-[20px]">add</span> Tambah Form',
                ['create'],
                ['class' => 'inline-flex items-center gap-2 rounded-xl bg-primary-container px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-container/25 no-underline transition hover:opacity-95']
            ) ?>
            <?= Html::a(
                '<span class="material-symbols-outlined text-[20px]">article</span> Master Halaman',
                ['/master-page/index'],
                ['class' => 'inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-5 py-2.5 text-sm font-semibold text-on-surface shadow-sm no-underline transition hover:border-primary-container/40 hover:text-primary-container']
            ) ?>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/50 bg-white shadow-[0_20px_50px_rgba(11,28,48,0.06)]">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'w-full min-w-full border-collapse text-left text-sm'],
            'headerRowOptions' => ['class' => 'border-b border-slate-200/90 bg-slate-50/90 text-xs font-bold uppercase tracking-wider text-slate-500'],
            'rowOptions' => static function () {
                return ['class' => 'border-b border-slate-100/90 transition hover:bg-slate-50/80'];
            },
            'layout' => "<div class=\"overflow-x-auto\">{items}</div>\n<div class=\"flex flex-col gap-2 border-t border-slate-100 p-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between\">{summary}{pager}</div>",
            'pager' => [
                'options' => ['class' => 'pagination flex flex-wrap gap-1'],
                'linkOptions' => ['class' => 'rounded-lg border border-slate-200 px-3 py-1.5 no-underline hover:bg-slate-100'],
                'activePageCssClass' => 'border-primary-container bg-primary-container/10 text-primary-container font-semibold',
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn', 'contentOptions' => ['class' => 'px-4 py-3 text-slate-500'], 'headerOptions' => ['class' => 'px-4 py-3']],
                [
                    'attribute' => 'form_name',
                    'label' => 'Nama',
                    'contentOptions' => ['class' => 'px-4 py-3 font-semibold text-on-surface'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                ],
                [
                    'attribute' => 'slug',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'format' => 'raw',
                    'value' => static function ($model) {
                        return '<code class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">' . Html::encode($model->slug) . '</code>';
                    },
                ],
                [
                    'attribute' => 'page_id',
                    'label' => 'Halaman',
                    'contentOptions' => ['class' => 'px-4 py-3 text-slate-600'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        return $model->page ? Html::encode($model->page->title) : '—';
                    },
                ],
                [
                    'attribute' => 'updated_at',
                    'format' => ['datetime', 'format' => 'php:Y-m-d H:i'],
                    'contentOptions' => ['class' => 'whitespace-nowrap px-4 py-3 text-slate-600'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'contentOptions' => ['class' => 'whitespace-nowrap px-4 py-3 text-right'],
                    'headerOptions' => ['class' => 'px-4 py-3 text-right'],
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => static function ($url, $model, $key) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">visibility</span>',
                                $url,
                                ['class' => 'mr-1 inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 no-underline hover:border-primary-container hover:text-primary-container', 'title' => 'Lihat']
                            );
                        },
                        'update' => static function ($url, $model, $key) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">edit</span>',
                                $url,
                                ['class' => 'mr-1 inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 no-underline hover:border-primary-container hover:text-primary-container', 'title' => 'Ubah']
                            );
                        },
                        'delete' => static function ($url, $model, $key) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">delete</span>',
                                $url,
                                [
                                    'class' => 'inline-flex rounded-lg border border-slate-200 p-2 text-rose-600 no-underline hover:bg-rose-50',
                                    'title' => 'Hapus',
                                    'data-confirm' => 'Hapus master form ini?',
                                    'data-method' => 'post',
                                ]
                            );
                        },
                    ],
                    'urlCreator' => static function ($action, MasterForm $model) {
                        return Url::toRoute([$action, 'id' => $model->id]);
                    },
                ],
            ],
        ]); ?>
    </div>
</div>
