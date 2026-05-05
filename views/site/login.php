<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$form = ActiveForm::begin([
    'id' => 'login-form',
    'fieldConfig' => [
        'template' => "{input}\n{error}",
        'errorOptions' => ['class' => 'text-xs text-red-600 mt-1', 'tag' => 'div'],
    ],
]);
?>

<!-- Card Header -->
<div class="text-center mb-6">
    <h2 class="text-2xl font-semibold text-on-surface">Sign in</h2>
</div>

<form class="space-y-4">

<!-- Username -->
<div class="space-y-1">
    <label class="text-sm font-semibold text-on-surface-variant block" for="loginform-username">Username</label>
    <div class="relative group">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-secondary">
            <span class="material-symbols-outlined text-lg">person</span>
        </div>
        <?= $form->field($model, 'username')->textInput([
            'class' => 'block w-full pl-10 pr-3 py-3 bg-surface-container-low border border-outline-variant rounded-lg text-sm text-on-surface focus:ring-0 focus:border-primary transition-colors duration-200',
            'placeholder' => 'Enter your username',
            'autofocus' => true,
        ])->label(false) ?>
    </div>
</div>

<!-- Password -->
<div class="space-y-1">
    <div class="flex justify-between items-center">
        <label class="text-sm font-semibold text-on-surface-variant" for="loginform-password">Password</label>
        <a href="#" class="text-sm text-primary hover:underline transition-all">Forgot password?</a>
    </div>
    <div class="relative group">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-secondary">
            <span class="material-symbols-outlined text-lg">lock</span>
        </div>
        <?= $form->field($model, 'password')->passwordInput([
            'class' => 'block w-full pl-10 pr-10 py-3 bg-surface-container-low border border-outline-variant rounded-lg text-sm text-on-surface focus:ring-0 focus:border-primary transition-colors duration-200',
            'placeholder' => '••••••••',
            'id' => 'password-field',
        ])->label(false) ?>
        <button type="button" class="absolute inset-y-0 right-3 flex items-center text-secondary cursor-pointer hover:text-on-surface transition-colors" onclick="togglePassword()">
            <span class="material-symbols-outlined text-lg" id="visibility-icon">visibility</span>
        </button>
    </div>
</div>

<!-- Remember Me -->
<div class="flex items-center">
    <input type="hidden" name="LoginForm[rememberMe]" value="0">
    <input type="checkbox" id="loginform-rememberme" name="LoginForm[rememberMe]" value="1" <?= $model->rememberMe ? 'checked' : '' ?> class="w-4 h-4 rounded border border-outline-variant text-primary focus:ring-0 cursor-pointer">
    <label for="loginform-rememberme" class="ml-2 text-sm font-semibold text-secondary select-none cursor-pointer">Remember me</label>
</div>

<!-- Sign In Button -->
<button type="submit" class="w-full flex items-center justify-center gap-2 bg-primary text-on-primary py-3 rounded-lg text-sm font-semibold hover:opacity-90 active:scale-[0.98] transition-all duration-200 group">
    Sign in
    <span class="material-symbols-outlined text-lg group-hover:translate-x-1 transition-transform">arrow_forward</span>
</button>

</form>

<?php ActiveForm::end(); ?>

<!-- Divider -->
<div class="relative flex items-center py-2">
    <div class="flex-grow border-t border-outline-variant"></div>
    <span class="flex-shrink mx-3 text-xs font-medium text-on-tertiary-container uppercase tracking-widest">or continue with</span>
    <div class="flex-grow border-t border-outline-variant"></div>
</div>

<!-- Social Logins -->
<div class="grid grid-cols-2 gap-3 mb-6">
    <!-- Google -->
    <button type="button" class="flex items-center justify-center gap-2 border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low py-3 rounded-lg text-sm font-semibold transition-colors duration-200">
        <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335"/>
        </svg>
        Google
    </button>
    
    <!-- GitHub -->
    <button type="button" class="flex items-center justify-center gap-2 border border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low py-3 rounded-lg text-sm font-semibold transition-colors duration-200">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
        </svg>
        GitHub
    </button>
</div>

<!-- Footer -->
<div class="text-center mt-6">
    <p class="text-sm text-secondary">
        Don't have an account? 
        <a href="#" class="text-primary text-sm font-semibold hover:underline transition-all">Sign up</a>
    </p>
</div>