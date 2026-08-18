<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Commander Login';

$form = ActiveForm::begin([
    'id' => 'login-form',
    'fieldConfig' => [
        'template' => "{input}\n{error}",
        'errorOptions' => ['class' => 'mt-1 text-sm text-red-600', 'tag' => 'div'],
    ],
]);
?>

<div class="space-y-4">
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
        </div>
    <?php endif; ?>

    <div>
        <label for="loginform-username" class="mb-2 block text-sm font-semibold text-slate-700">Username</label>
        <?= $form->field($model, 'username')->textInput([
            'class' => 'w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-slate-900',
            'placeholder' => 'Masukkan username',
            'autocomplete' => 'username',
            'autofocus' => true,
        ])->label(false) ?>
    </div>

    <div>
        <label for="loginform-password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
        <?= $form->field($model, 'password')->passwordInput([
            'class' => 'w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-slate-900',
            'placeholder' => 'Masukkan password',
            'autocomplete' => 'current-password',
        ])->label(false) ?>
    </div>

    <button type="submit" class="w-full rounded-2xl bg-slate-950 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">
        Login
    </button>
</div>

<?php ActiveForm::end(); ?>
