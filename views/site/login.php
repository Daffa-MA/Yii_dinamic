<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Login';

$this->registerCss(
    <<<CSS
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body.login-page {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    background: #fafafa;
    color: #1a1a1a;
}

.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.login-container {
    width: 100%;
    max-width: 420px;
}

/* Brand Section */
.brand-section {
    text-align: center;
    margin-bottom: 48px;
}

.brand-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #1a1a1a;
    border-radius: 12px;
    margin-bottom: 20px;
}

.brand-icon .material-symbols-outlined {
    color: white;
    font-size: 24px;
}

.brand-title {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    letter-spacing: -0.5px;
    margin-bottom: 6px;
}

.brand-subtitle {
    font-size: 13px;
    font-weight: 400;
    color: #6b7280;
    letter-spacing: 0.5px;
}

/* Card */
.login-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    border: 1px solid #e5e7eb;
}

/* Header */
.card-header-section {
    margin-bottom: 32px;
}

.card-title {
    font-size: 22px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 6px;
    letter-spacing: -0.3px;
}

.card-description {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
}

/* Form Styles */
.form-section {
    margin-bottom: 24px;
}

.form-label-custom {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
    display: flex;
    align-items: center;
}

.input-icon .material-symbols-outlined {
    font-size: 18px;
}

.login-input {
    display: block;
    width: 100%;
    padding: 12px 14px 12px 44px;
    background: #fafafa;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #1a1a1a;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.login-input::placeholder {
    color: #9ca3af;
}

.login-input:focus {
    border-color: #1a1a1a;
    background: white;
    outline: none;
}

.login-input:hover:not(:focus) {
    border-color: #d1d5db;
}

.input-wrapper:focus-within .input-icon {
    color: #1a1a1a;
}

.password-input {
    padding-right: 44px;
}

.toggle-password-btn {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    transition: color 0.2s ease;
}

.toggle-password-btn:hover {
    color: #1a1a1a;
}

.toggle-password-btn .material-symbols-outlined {
    font-size: 18px;
}

/* Password Header with Link */
.password-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.forgot-link {
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #1a1a1a;
}

/* Remember Me */
.remember-section {
    display: flex;
    align-items: center;
    margin-bottom: 28px;
}

.remember-section .remember-checkbox {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    cursor: pointer;
    accent-color: #1a1a1a;
    margin: 0;
}

.remember-section .remember-label {
    margin-left: 10px;
    font-size: 14px;
    font-weight: 400;
    color: #374151;
    cursor: pointer;
    user-select: none;
}

/* Submit Button */
.submit-btn {
    width: 100%;
    background: #1a1a1a;
    color: white;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 14px;
    padding: 12px 20px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.submit-btn:hover {
    background: #000;
    transform: translateY(-1px);
}

.submit-btn:active {
    transform: translateY(0);
}

.submit-btn .material-symbols-outlined {
    font-size: 16px;
    line-height: 1;
}

/* Demo Info */
.demo-info {
    margin-top: 20px;
    padding: 12px 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    text-align: center;
}

.demo-info p {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

.demo-info strong {
    font-weight: 500;
    color: #374151;
}

/* Divider */
.divider-section {
    margin-top: 28px;
    position: relative;
    display: flex;
    align-items: center;
}

.divider-section::before {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.divider-section::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.divider-text {
    padding: 0 12px;
    font-size: 12px;
    font-weight: 500;
    color: #9ca3af;
    white-space: nowrap;
}

/* SSO Buttons */
.sso-section {
    margin-top: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.sso-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 16px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sso-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.sso-btn:active {
    background: #f3f4f6;
}

/* Footer */
.footer-section {
    margin-top: 32px;
    text-align: center;
}

.footer-text {
    font-size: 14px;
    color: #6b7280;
}

.footer-link {
    font-weight: 500;
    color: #1a1a1a;
    text-decoration: none;
    margin-left: 4px;
}

.footer-link:hover {
    text-decoration: underline;
}

.footer-bottom {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 20px;
}

.footer-bottom a {
    font-size: 12px;
    color: #9ca3af;
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-bottom a:hover {
    color: #6b7280;
}

/* Error Message */
.error-message {
    font-size: 12px;
    color: #dc2626;
    margin-top: 6px;
    font-weight: 400;
}

/* Yii2 Validation Error */
.has-error .login-input {
    border-color: #fca5a5;
    background: #fef2f2;
}

.has-error .login-input:focus {
    border-color: #dc2626;
    background: white;
}

/* Responsive */
@media (max-width: 480px) {
    .login-card {
        padding: 32px 24px;
    }
    
    .sso-section {
        grid-template-columns: 1fr;
    }
}
CSS,
    ['key' => 'login-custom']
);
?>

<div class="login-wrapper">
    <!-- Login Container -->
    <div class="login-container">
        <!-- Brand Identity Section -->
        <div class="brand-section">
            <div class="brand-icon">
                <span class="material-symbols-outlined">architecture</span>
            </div>
            <h1 class="brand-title">Architectural Editor</h1>
            <p class="brand-subtitle">FORM BUILDER</p>
        </div>

        <!-- Main Login Card -->
        <div class="login-card">
            <header class="card-header-section">
                <h2 class="card-title">Sign in</h2>
                <p class="card-description">Welcome back! Please enter your details.</p>
            </header>

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{input}\n{error}",
                    'errorOptions' => ['class' => 'error-message', 'tag' => 'div'],
                ],
                'options' => [
                    'autocomplete' => 'off'
                ]
            ]); ?>

            <!-- Username Field -->
            <div class="form-section">
                <label class="form-label-custom">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <span class="material-symbols-outlined">person</span>
                    </span>
                    <?= $form->field($model, 'username')->textInput([
                        'autofocus' => true,
                        'class' => 'login-input',
                        'placeholder' => 'Enter your username'
                    ])->label(false) ?>
                </div>
            </div>

            <!-- Password Field -->
            <div class="form-section">
                <div class="password-header">
                    <label class="form-label-custom" style="margin: 0;">Password</label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <span class="material-symbols-outlined">lock</span>
                    </span>
                    <?= $form->field($model, 'password')->passwordInput([
                        'class' => 'login-input password-input',
                        'placeholder' => 'Enter your password',
                        'id' => 'password-field'
                    ])->label(false) ?>
                    <button type="button" class="toggle-password-btn" onclick="togglePassword()">
                        <span class="material-symbols-outlined" id="visibility-icon">visibility</span>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="remember-section">
                <?= $form->field($model, 'rememberMe')->checkbox([
                    'class' => 'remember-checkbox',
                    'label' => '<span class="remember-label">Remember me</span>',
                    'template' => "{input}\n{label}\n{error}",
                    'uncheck' => false
                ])->label(false) ?>
            </div>

            <!-- Submit Button -->
            <div style="margin-top: 28px;">
                <?= Html::button('Sign in →', [
                    'type' => 'submit',
                    'class' => 'submit-btn',
                    'name' => 'login-button',
                    'onclick' => 'document.getElementById("login-form").submit();'
                ]) ?>
            </div>

            <!-- Demo Account Info -->
            <div class="demo-info">
                <p>Demo: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>

            <?php ActiveForm::end(); ?>

            <!-- Divider -->
            <div class="divider-section">
                <span class="divider-text">or continue with</span>
            </div>

            <!-- SSO Options -->
            <div class="sso-section">
                <button type="button" class="sso-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"></path>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335"></path>
                    </svg>
                    <span>Google</span>
                </button>
                <button type="button" class="sso-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.341-3.369-1.341-.454-1.152-1.11-1.459-1.11-1.459-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482C19.138 20.161 22 16.416 22 12c0-5.523-4.477-10-10-10z"></path>
                    </svg>
                    <span>GitHub</span>
                </button>
            </div>
        </div>

        <!-- Footer Context -->
        <footer class="footer-section">
            <p class="footer-text">
                Don't have an account?<a href="#" class="footer-link">Sign up</a>
            </p>
            <div class="footer-bottom">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </footer>
    </div>
</div>

<?php
$script = <<<JS
function togglePassword() {
    const passwordField = document.getElementById('password-field');
    const visibilityIcon = document.getElementById('visibility-icon');
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        visibilityIcon.textContent = 'visibility_off';
    } else {
        passwordField.type = 'password';
        visibilityIcon.textContent = 'visibility';
    }
}

// Debug form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitted');
            const formData = new FormData(form);
            console.log('Form data:', Object.fromEntries(formData));
        });
    }
});
JS;
$this->registerJs($script);
?>