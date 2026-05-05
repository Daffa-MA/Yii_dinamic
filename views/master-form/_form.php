<?php

use app\models\MasterPage;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\MasterForm */

$pages = MasterPage::find()->where(['is_active' => 1])->all();
$pageList = \yii\helpers\ArrayHelper::map($pages, 'id', 'title');
?>

<?php $form = ActiveForm::begin(); ?>

<div class="space-y-5">
    <?= $form->field($model, 'page_id', [
        'options' => ['class' => '']
    ])->dropDownList($pageList, [
        'prompt' => 'Select page',
        'class' => 'w-full px-4 py-3.5 border border-gray-200 rounded-lg text-gray-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 appearance-none cursor-pointer'
    ])->label('Page', ['class' => 'block text-sm font-medium text-gray-700 mb-1.5']) ?>
    
    <?= $form->field($model, 'form_name', [
        'options' => ['class' => '']
    ])->textInput([
        'maxlength' => true,
        'placeholder' => 'Enter form name',
        'class' => 'w-full px-4 py-3.5 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500'
    ])->label('Form Name', ['class' => 'block text-sm font-medium text-gray-700 mb-1.5']) ?>
    
    <?= $form->field($model, 'slug', [
        'options' => ['class' => '']
    ])->textInput([
        'maxlength' => true,
        'placeholder' => 'auto-generated-from-name',
        'class' => 'w-full px-4 py-3.5 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500'
    ])->label('Slug', ['class' => 'block text-sm font-medium text-gray-700 mb-1.5']) ?>
</div>

<div class="mt-8 pt-6 border-t border-gray-100 flex items-center gap-3">
    <?= Html::submitButton('Save Form', [
        'class' => 'px-8 py-3.5 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 active:bg-emerald-800 transition-colors duration-200 shadow-sm'
    ]) ?>
    <?= Html::a('Cancel', ['index'], [
        'class' => 'px-8 py-3.5 bg-white text-gray-600 font-medium rounded-lg border border-gray-200 hover:bg-gray-50 hover:border-gray-300 active:bg-gray-100 transition-all duration-200 no-underline'
    ]) ?>
</div>

<?php ActiveForm::end(); ?>
