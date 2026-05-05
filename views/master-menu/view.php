<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
?>

<!-- Header -->
<div class="bg-white rounded-xl shadow-md p-6 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-white"><?= isset($model['icon']) ? $model['icon'] : 'menu' ?></span>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900"><?= Html::encode($model->name) ?></h1>
                <p class="text-sm text-gray-500">Menu details</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::a('<span class="material-symbols-outlined text-sm">edit</span> Edit', ['update', 'id' => $model->id], [
                'class' => 'px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors no-underline flex items-center gap-2'
            ]) ?>
            <?= Html::a('<span class="material-symbols-outlined text-sm">arrow_back</span>', ['index'], [
                'class' => 'w-10 h-10 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-50 no-underline'
            ]) ?>
        </div>
    </div>
</div>

<!-- Details Card -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'attribute' => 'name',
                'label' => 'Nama Menu',
            ],
            [
                'attribute' => 'parent_id',
                'label' => 'Parent Menu',
                'value' => $model->parent ? Html::encode($model->parent->name) : '-',
            ],
            [
                'attribute' => 'type',
                'label' => 'Tipe Menu',
                'value' => $model->type ?? 'group',
            ],
            [
                'attribute' => 'page_id',
                'label' => 'Halaman',
                'value' => $model->page ? Html::encode($model->page->title) : '-',
            ],
            [
                'attribute' => 'route',
                'label' => 'Route',
                'value' => $model->route ?: '-',
            ],
            [
                'attribute' => 'icon',
                'label' => 'Icon',
                'format' => 'raw',
                'value' => isset($model['icon']) ? Html::encode($model['icon']) : '-',
            ],
            [
                'attribute' => 'sort_order',
                'label' => 'Urutan',
            ],
            [
                'attribute' => 'is_active',
                'label' => 'Status',
                'value' => $model->is_active ? 'Aktif' : 'Nonaktif',
            ],
            [
                'attribute' => 'created_at',
                'label' => 'Dibuat',
                'value' => \Yii::$app->formatter->asDatetime($model->created_at),
            ],
            [
                'attribute' => 'updated_at',
                'label' => 'Diupdate',
                'value' => \Yii::$app->formatter->asDatetime($model->updated_at),
            ],
        ],
        'options' => ['class' => 'table table-striped'],
    ]) ?>
</div>