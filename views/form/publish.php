<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var app\models\PublishedForm|null $publishedForm */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$formName = $publishedForm ? $publishedForm->name : $model->name;
?>

<div class="p-6">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-primary-container/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">public</span>
        </div>
        <div>
            <h3 class="text-lg font-bold font-headline text-on-surface"><?= $publishedForm ? 'Update Published Form' : 'Publish Form' ?></h3>
            <p class="text-sm text-on-surface-variant">Enter a name for your published form</p>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'action' => ['form/publish', 'id' => $model->id],
        'options' => ['id' => 'publish-form'],
    ]) ?>

    <?= $form->field($model, 'name')->textInput([
        'name' => 'name',
        'value' => $formName,
        'maxlength' => true,
        'placeholder' => 'Enter published form name...',
        'class' => 'form-control'
    ])->label('Published Name') ?>

    <div class="alert alert-info mt-3" style="background: #e5eeff; border-left: 4px solid #4f46e5;">
        <p class="text-sm mb-0"><strong>Note:</strong> This will publish your form and make it accessible via a public URL.</p>
    </div>

    <div class="d-flex justify-content-end gap-3 mt-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <?= Html::submitButton('<span class="material-symbols-outlined text-[18px]">public</span> ' . ($publishedForm ? 'Update' : 'Publish'), [
            'class' => 'btn btn-primary',
            'id' => 'publish-btn'
        ]) ?>
    </div>

    <?php ActiveForm::end() ?>
</div>
