<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login';
?>
<div class="site-login">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Login</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted">Please fill in your credentials:</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'form-label'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'username')->textInput([
                        'autofocus' => true,
                        'class' => 'form-control',
                        'placeholder' => 'Username'
                    ]) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'class' => 'form-control',
                        'placeholder' => 'Password'
                    ]) ?>

                    <?= $form->field($model, 'rememberMe')->checkbox([
                        'template' => "<div class=\"custom-control custom-checkbox\">{input} {label}</div>\n<div class=\"col-lg-8\">{error}</div>",
                    ]) ?>

                    <div class="form-group mt-3">
                        <?= Html::submitButton('Login', [
                            'class' => 'btn btn-primary w-100',
                            'name' => 'login-button'
                        ]) ?>
                    </div>

                    <div class="alert alert-info mt-3">
                        <strong>Demo Account:</strong> admin / admin123
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
