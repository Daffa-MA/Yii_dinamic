<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Master Menu';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', ['position' => \yii\web\View::POS_HEAD]);
?>
<div class="max-w-7xl mx-auto px-4 py-8">

<div class="mb-8 flex items-center justify-between">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-blue-600">Master Data</p>
            <h1 class="text-2xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
            <p class="mt-1 text-sm text-gray-500">Kelola menu sidebar, urutan, dropdown parent-child, dan tautan ke halaman dinamis.</p>
        </div>
        <div class="flex items-center gap-3">
            <?= Html::a(
                '<span class="material-symbols-outlined text-lg">add</span> Tambah Menu',
                ['create'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-gray-800']
            ) ?>
            <?= Html::a(
                '<span class="material-symbols-outlined text-lg">article</span> Master Halaman',
                ['/master-page/index'],
                ['class' => 'inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 no-underline hover:border-gray-300 hover:bg-gray-50']
            ) ?>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'w-full'],
            'headerRowOptions' => ['class' => 'bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500'],
            'rowOptions' => static function () {
                return ['class' => 'border-b border-gray-50 hover:bg-gray-50/50'];
            },
            'layout' => "<div class=\"overflow-x-auto\">{items}</div>\n<div class=\"flex items-center justify-between border-t border-gray-100 px-4 py-3 text-xs text-gray-500\">{summary}{pager}</div>",
            'emptyText' => '<div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">menu</span>
                <p class="text-lg font-semibold text-gray-600 mb-1">Belum ada menu</p>
                <p class="text-sm text-gray-500 mb-4">Buat menu pertama untuk menampilkan di sidebar.</p>
                ' . Html::a(
                    '<span class="material-symbols-outlined text-lg">add</span> Tambah Menu',
                    ['create'],
                    ['class' => 'inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-gray-800']
                ) . '
            </div>',
            'pager' => [
                'options' => ['class' => 'flex gap-1'],
                'linkOptions' => ['class' => 'rounded-lg border border-gray-200 px-3 py-1 hover:bg-gray-50'],
                'activePageCssClass' => 'bg-gray-900 text-white border-gray-900',
            ],
            'columns' => [
                [
                    'attribute' => 'sort_order',
                    'label' => 'Urutan',
                    'contentOptions' => ['class' => 'px-4 py-3 text-gray-600'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                ],
                [
                    'attribute' => 'name',
                    'label' => 'Menu',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $indent = $model->parent_id ? '<span class="text-gray-300">&nbsp;&nbsp;&nbsp;&rdsh;</span> ' : '';
                        $icon = isset($model['icon']) ? $model['icon'] : 'folder';

                        return $indent
                            . '<span class="material-symbols-outlined mr-2 align-middle text-base text-gray-400">' . Html::encode($icon) . '</span>'
                            . '<span class="font-medium text-gray-900">' . Html::encode($model->name) . '</span>';
                    },
                ],
                [
                    'attribute' => 'parent_id',
                    'label' => 'Parent',
                    'contentOptions' => ['class' => 'px-4 py-3 text-gray-600'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $attrs = $model->getAttributes();
                        $parentId = $attrs['parent_id'] ?? null;
                        
                        if (!$parentId) {
                            return '-';
                        }
                        
                        $parentModel = \app\models\MasterMenu::findOne($parentId);
                        if (!$parentModel) {
                            return '-';
                        }
                        
                        return Html::encode($parentModel->getAttribute('name') ?? 'Parent');
                    },
                ],
                [
                    'attribute' => 'type',
                    'label' => 'Tipe',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $type = $model->type ?? 'group';
                        $labels = [
                            'group' => '<span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">Group</span>',
                            'page' => '<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">Page</span>',
                            'route' => '<span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Route</span>',
                        ];
                        return $labels[$type] ?? '-';
                    },
                ],
                [
                    'attribute' => 'page_id',
                    'label' => 'Halaman',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $attrs = $model->getAttributes();
                        $pageId = $attrs['page_id'] ?? null;
                        
                        if (!$pageId) {
                            return '<span class="text-gray-300">-</span>';
                        }
                        
                        $pageModel = \app\models\MasterPage::findOne($pageId);
                        if (!$pageModel) {
                            return '<span class="text-gray-300">-</span>';
                        }
                        
                        $pageAttrs = $pageModel->getAttributes();
                        $pageTitle = $pageAttrs['title'] ?? 'Untitled';
                        
                        return Html::a(
                            Html::encode($pageTitle),
                            ['/page/view', 'id' => $pageId],
                            ['class' => 'font-medium text-blue-600 no-underline hover:underline', 'target' => '_blank']
                        );
                    },
                ],
                [
                    'attribute' => 'is_active',
                    'label' => 'Status',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'px-4 py-3'],
                    'headerOptions' => ['class' => 'px-4 py-3'],
                    'value' => static function ($model) {
                        $attrs = $model->getAttributes();
                        $isActive = (int) ($attrs['is_active'] ?? 0) === 1;
                        return Html::beginForm(['toggle', 'id' => $model->id], 'post', ['style' => 'display:inline'])
                            . Html::submitButton(
                                '<span class="material-symbols-outlined text-xl">' . ($isActive ? 'toggle_on' : 'toggle_off') . '</span>',
                                ['class' => 'rounded-lg px-2 py-1 text-xs font-bold transition ' . ($isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500')]
                            )
                            . Html::endForm();
                    },
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'contentOptions' => ['class' => 'px-4 py-3 text-right'],
                    'headerOptions' => ['class' => 'px-4 py-3 text-right'],
                    'template' => '{update} {delete}',
                    'buttons' => [
                        'update' => static function ($url) {
                            return Html::a(
                                '<span class="material-symbols-outlined">edit</span>',
                                $url,
                                ['class' => 'inline-flex rounded-lg p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600', 'title' => 'Edit']
                            );
                        },
                        'delete' => static function ($url) {
                            return Html::a(
                                '<span class="material-symbols-outlined">delete</span>',
                                $url,
                                ['class' => 'inline-flex rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600', 'title' => 'Hapus', 'data-confirm' => 'Hapus menu ini?', 'data-method' => 'post']
                            );
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
