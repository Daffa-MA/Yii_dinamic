<?php

use app\models\MasterPage;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Master Halaman';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);

$toggleBaseUrl = Url::to(['toggle', 'id' => '']);
?>
<div class="mx-auto max-w-7xl px-4 pb-12 pt-2">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-primary-container">Master Data</p>
            <h1 class="font-headline text-2xl font-extrabold tracking-tight text-on-surface md:text-3xl"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-on-surface-variant">Buat halaman dinamis, pilih form asli yang ingin ditampilkan, lalu hubungkan ke menu sidebar.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?= Html::a(
                '<span class="material-symbols-outlined text-[20px]">add</span> Tambah Halaman',
                ['create'],
                ['class' => 'inline-flex items-center gap-2 rounded-xl bg-primary-container px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-container/25 no-underline transition hover:opacity-95']
            ) ?>
            <?= Html::a(
                '<span class="material-symbols-outlined text-[20px]">menu</span> Master Menu',
                ['/master-menu/index'],
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
            'emptyText' => '<div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">article</span>
                <p class="text-lg font-semibold text-slate-600 mb-1">Belum ada halaman</p>
                <p class="text-sm text-slate-500 mb-4">Buat halaman pertama Anda untuk menampilkan form di sidebar.</p>
                ' . Html::a(
                    '<span class="material-symbols-outlined text-lg">add</span> Tambah Halaman',
                    ['create'],
                    ['class' => 'inline-flex items-center gap-2 rounded-xl bg-primary-container px-5 py-2.5 text-sm font-semibold text-white no-underline hover:opacity-90']
                ) . '
            </div>',
            'pager' => [
                'options' => ['class' => 'pagination flex flex-wrap gap-1'],
                'linkOptions' => ['class' => 'rounded-lg border border-slate-200 px-3 py-1.5 no-underline hover:bg-slate-100'],
                'activePageCssClass' => 'border-primary-container bg-primary-container/10 text-primary-container font-semibold',
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn', 'contentOptions' => ['class' => 'px-4 py-3 text-slate-500'], 'headerOptions' => ['class' => 'px-4 py-3']],
                [
                    'attribute' => 'name',
                    'label' => 'Judul',
                    'contentOptions' => ['class' => 'px-4 py-3 font-semibold text-on-surface'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                ],
                [
                    'attribute' => 'layout',
                    'label' => 'Layout',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'format' => 'raw',
'value' => static function ($model) {
                        // Use model directly but with safe access
                        $name = null;
                        if (method_exists($model, 'getAttribute')) {
                            $name = $model->getAttribute('name');
                        } elseif (isset($model['name'])) {
                            $name = $model['name'];
                        } else {
                            try {
                                $attrs = $model->getAttributes();
                                $name = $attrs['name'] ?? null;
                            } catch (\Exception $e) {
                                $name = null;
                            }
                        }
                        return $name ?: '-';
                    },
                ],
                [
                    'label' => 'Form Terpasang',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'px-4 py-3 text-slate-600'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function (MasterPage $model) {
                        return '<span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Lihat detail</span>';
                    },
                ],
                [
                    'attribute' => 'description',
                    'label' => 'Deskripsi',
                    'contentOptions' => ['class' => 'max-w-md px-4 py-3 text-slate-600'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $attrs = $model instanceof \yii\db\ActiveRecord ? $model->getAttributes() : (array) $model;
                        $desc = $attrs['description'] ?? '';
                        if (empty($desc)) {
                            return '-';
                        }
                        return Html::encode(StringHelper::truncate(strip_tags($desc), 120));
                    },
                ],
                [
                    'attribute' => 'is_active',
                    'label' => 'Status',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $attrs = $model instanceof \yii\db\ActiveRecord ? $model->getAttributes() : (array) $model;
                        $isActive = isset($attrs['is_active']) && $attrs['is_active'] == 1;
                        return Html::beginForm(['toggle', 'id' => $attrs['id']], 'post', ['style' => 'display:inline'])
                            . Html::submitButton(
                                '<span class="material-symbols-outlined text-xl">' . ($isActive ? 'toggle_on' : 'toggle_off') . '</span>',
                                ['class' => 'rounded-lg px-2 py-1 text-xs font-bold transition ' . ($isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')]
                            )
                            . Html::endForm();
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
                    'template' => '{preview} {builder} {view} {update} {delete}',
                    'buttons' => [
                        'preview' => static function ($url, MasterPage $model) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">open_in_new</span>',
                                ['/page/view', 'id' => $model->id],
                                ['class' => 'mr-1 inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 no-underline hover:border-primary-container hover:text-primary-container', 'title' => 'Buka halaman dinamis', 'target' => '_blank']
                            );
                        },
                        'view' => static function ($url) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">visibility</span>',
                                $url,
                                ['class' => 'mr-1 inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 no-underline hover:border-primary-container hover:text-primary-container', 'title' => 'Lihat']
                            );
                        },
                        'update' => static function ($url) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">edit</span>',
                                $url,
                                ['class' => 'mr-1 inline-flex rounded-lg border border-slate-200 p-2 text-slate-600 no-underline hover:border-primary-container hover:text-primary-container', 'title' => 'Ubah']
                            );
                        },
                        'delete' => static function ($url) {
                            return Html::a(
                                '<span class="material-symbols-outlined text-[18px]">delete</span>',
                                $url,
                                [
                                    'class' => 'inline-flex rounded-lg border border-slate-200 p-2 text-rose-600 no-underline hover:bg-rose-50',
                                    'title' => 'Hapus',
                                    'data-confirm' => 'Hapus halaman ini?',
                                    'data-method' => 'post',
                                ]
                            );
                        },
                    ],
                    'urlCreator' => static function ($action, MasterPage $model) {
                        return Url::toRoute([$action, 'id' => $model->id]);
                    },
                ],
            ],
        ]); ?>
    </div>
</div>
