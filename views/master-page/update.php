<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MasterPage */
/* @var $availableForms app\models\Form[] */

$this->title = 'Ubah Halaman: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Master Halaman', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="bg-gray-100 py-8">
    <div class="max-w-xl mx-auto px-4">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-white">edit</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Ubah Halaman</h1>
                        <p class="text-sm text-gray-500">Perbarui isi halaman, form yang ditampilkan, dan statusnya.</p>
                    </div>
                </div>
                <?= Html::a('<span class="material-symbols-outlined">arrow_back</span>', ['index'], [
                    'class' => 'w-10 h-10 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-50 no-underline'
                ]) ?>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="h-1 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
            <div class="p-8">
                <?= $this->render('_form', [
                    'model' => $model,
                    'availableForms' => $availableForms,
                ]) ?>
            </div>
        </div>
    </div>
</div>
