<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Login';

$this->registerCss(
    <<<CSS
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap');

body.login-page {
    margin: 0;
    padding: 0;
    background: #f7f9fb;
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.material-symbols-filled {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.login-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    position: relative;
    overflow: hidden;
    background: #f7f9fb;
}

/* Decorative Background */
.bg-decoration {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    opacity: 0.4;
}

.bg-orb-1 {
    position: absolute;
    top: -10%;
    right: -5%;
    width: 40rem;
    height: 40rem;
    border-radius: 50%;
    background: rgba(0, 81, 176, 0.05);
    filter: blur(3rem);
}

.bg-orb-2 {
    position: absolute;
    bottom: -10%;
    left: -5%;
    width: 35rem;
    height: 35rem;
    border-radius: 50%;
    background: rgba(148, 55, 0, 0.05);
    filter: blur(3rem);
}

.bg-grid {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, #c3c6d7 1px, transparent 1px);
    background-size: 40px 40px;
    opacity: 0.2;
}

/* Container */
.login-container {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 440px;
}

/* Brand Section */
.brand-section {
    margin-bottom: 40px;
    text-align: center;
}

.brand-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #0051b0 0%, #0f69dc 100%);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 81, 176, 0.2);
    margin-bottom: 24px;
}

.brand-icon .material-symbols-outlined {
    color: white;
    font-size: 28px;
}

.brand-title {
    font-family: 'Manrope', sans-serif;
    font-weight: 800;
    font-size: 30px;
    letter-spacing: -0.5px;
    color: #191c1e;
    margin: 0;
}

.brand-subtitle {
    margin-top: 8px;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 13px;
    letter-spacing: 0.5px;
    color: #434655;
}

/* Card */
.login-card {
    background: #ffffff;
    border-radius: 12px;
    outline: 1px solid rgba(195, 198, 215, 0.15);
    padding: 40px;
    box-shadow: 0 12px 40px rgba(25, 28, 30, 0.06);
}

@media (min-width: 768px) {
    .login-card {
        padding: 48px;
    }
}

/* Header */
.card-header-section {
    margin-bottom: 32px;
}

.card-title {
    font-family: 'Manrope', sans-serif;
    font-weight: 700;
    font-size: 20px;
    color: #191c1e;
    margin: 0;
}

.card-description {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #434655;
    margin-top: 4px;
}

/* Form Styles */
.form-section {
    margin-bottom: 24px;
}

.form-label-custom {
    display: block;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #434655;
    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 12px;
    display: flex;
    align-items: center;
    pointer-events: none;
    color: #737686;
    transition: color 0.2s;
}

.input-icon .material-symbols-outlined {
    font-size: 20px;
}

.login-input {
    display: block;
    width: 100%;
    padding: 12px 16px 12px 40px;
    background: #ffffff;
    border: 1px solid rgba(195, 198, 215, 0.3);
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #191c1e;
    transition: all 0.2s;
    box-sizing: border-box;
}

.login-input::placeholder {
    color: rgba(115, 118, 134, 0.5);
}

.login-input:focus {
    border-color: #0051b0;
    box-shadow: 0 0 0 3px rgba(0, 81, 176, 0.1);
    outline: none;
}

.login-input:hover {
    border-color: rgba(195, 198, 215, 0.5);
}

.password-input {
    padding-right: 40px;
}

.toggle-password-btn {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 12px;
    display: flex;
    align-items: center;
    background: none;
    border: none;
    cursor: pointer;
    color: #737686;
    transition: color 0.2s;
    padding: 0;
}

.toggle-password-btn:hover {
    color: #191c1e;
}

.toggle-password-btn .material-symbols-outlined {
    font-size: 20px;
}

/* Password Header with Link */
.password-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.forgot-link {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: #0051b0;
    text-decoration: none;
    transition: color 0.2s;
}

.forgot-link:hover {
    color: #0f69dc;
}

/* Remember Me */
.remember-section {
    display: flex;
    align-items: center;
    margin-bottom: 24px;
}

.remember-checkbox {
    height: 16px;
    width: 16px;
    border-radius: 4px;
    border-color: #c3c6d7;
    cursor: pointer;
    accent-color: #0051b0;
}

.remember-label {
    margin-left: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #505f76;
    cursor: pointer;
    user-select: none;
}

/* Submit Button */
.submit-btn {
    width: 100%;
    background: linear-gradient(135deg, #0051b0 0%, #0f69dc 100%);
    color: white;
    font-family: 'Manrope', sans-serif;
    font-weight: 700;
    font-size: 15px;
    padding: 14px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 81, 176, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.submit-btn:hover {
    box-shadow: 0 8px 24px rgba(0, 81, 176, 0.3);
    transform: translateY(-1px);
}

.submit-btn:active {
    transform: scale(0.98);
}

.submit-btn .material-symbols-outlined {
    font-size: 20px;
}

/* Demo Info */
.demo-info {
    margin-top: 16px;
    padding: 12px;
    background: #eceef0;
    border-radius: 8px;
    text-align: center;
}

.demo-info p {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #434655;
    margin: 0;
}

.demo-info strong {
    font-weight: 600;
}

/* Divider */
.divider-section {
    margin-top: 32px;
    position: relative;
}

.divider-line {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
}

.divider-line::before {
    content: '';
    width: 100%;
    border-top: 1px solid rgba(195, 198, 215, 0.3);
}

.divider-text-wrapper {
    position: relative;
    display: flex;
    justify-content: center;
}

.divider-text {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #737686;
    background: #ffffff;
    padding: 0 16px;
}

/* SSO Buttons */
.sso-section {
    margin-top: 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.sso-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #f2f4f6;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #191c1e;
    cursor: pointer;
    transition: all 0.2s;
}

.sso-btn:hover {
    background: #e6e8ea;
    transform: scale(0.95);
}

.sso-btn:active {
    transform: scale(0.95);
}

/* Footer */
.footer-section {
    margin-top: 32px;
    text-align: center;
}

.footer-text {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #434655;
}

.footer-link {
    font-weight: 700;
    color: #0051b0;
    text-decoration: none;
    margin-left: 4px;
    transition: color 0.2s;
}

.footer-link:hover {
    text-decoration: underline;
    text-underline-offset: 4px;
}

.footer-bottom {
    margin-top: 24px;
    display: flex;
    justify-content: center;
    gap: 24px;
}

.footer-bottom a {
    font-family: 'Inter', sans-serif;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #737686;
    text-decoration: none;
    transition: color 0.2s;
}

.footer-bottom a:hover {
    color: #434655;
}

/* Error Message */
.error-message {
    font-size: 12px;
    color: #ba1a1a;
    margin-top: 4px;
}

/* Yii2 Validation Error */
.has-error .login-input {
    border-color: #ba1a1a;
}

.has-error .login-input:focus {
    box-shadow: 0 0 0 3px rgba(186, 26, 26, 0.1);
}
CSS,
    ['key' => 'login-custom']
);
?>

<div class="login-wrapper">
    <!-- Decorative Background -->
    <div class="bg-decoration">
        <div class="bg-orb-1"></div>
        <div class="bg-orb-2"></div>
        <div class="bg-grid"></div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <!-- Brand Identity Section -->
        <div class="brand-section">
            <div class="brand-icon">
                <span class="material-symbols-outlined material-symbols-filled">architecture</span>
            </div>
            <h1 class="brand-title">Architectural Editor</h1>
            <p class="brand-subtitle">DESIGN SYSTEM &amp; FORM ENGINE</p>
        </div>

        <!-- Main Login Card -->
        <div class="login-card">
            <header class="card-header-section">
                <h2 class="card-title">Welcome back</h2>
                <p class="card-description">Enter your credentials to access the workspace.</p>
            </header>

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'fieldConfig' => [
                    'template' => "{input}\n{error}",
                    'errorOptions' => ['class' => 'error-message', 'tag' => 'div'],
                ],
            ]); ?>

            <!-- Username Field -->
            <div class="form-section">
                <label class="form-label-custom">Username</label>
                <?= $form->field($model, 'username')->textInput([
                    'autofocus' => true,
                    'class' => 'login-input',
                    'placeholder' => 'architect.alpha'
                ])->label(false) ?>
            </div>

            <!-- Password Field -->
            <div class="form-section">
                <div class="password-header">
                    <label class="form-label-custom" style="margin: 0;">Password</label>
                    <a href="#" class="forgot-link">Forgot Password?</a>
                </div>
                <?= $form->field($model, 'password')->passwordInput([
                    'class' => 'login-input password-input',
                    'placeholder' => '••••••••',
                    'id' => 'password-field'
                ])->label(false) ?>
                <button type="button" class="toggle-password-btn" onclick="togglePassword()">
                    <span class="material-symbols-outlined" id="visibility-icon">visibility</span>
                </button>
            </div>

            <!-- Remember Me -->
            <div class="remember-section">
                <?= $form->field($model, 'rememberMe')->checkbox([
                    'class' => 'remember-checkbox',
                    'label' => '<label class="remember-label" for="LoginForm-rememberMe">Remember this device</label>',
                    'template' => "{input}\n{label}\n{error}",
                    'uncheck' => false
                ])->label(false) ?>
            </div>

            <!-- Submit Button -->
            <div>
                <?= Html::submitButton('<span>Sign In</span><span class="material-symbols-outlined">arrow_forward</span>', [
                    'class' => 'submit-btn',
                    'name' => 'login-button',
                    'encode' => false
                ]) ?>
            </div>

            <!-- Demo Account Info -->
            <div class="demo-info">
                <p><strong>Demo Account:</strong> admin / admin123</p>
            </div>

            <?php ActiveForm::end(); ?>

            <!-- Divider -->
            <div class="divider-section">
                <div class="divider-line"></div>
                <div class="divider-text-wrapper">
                    <span class="divider-text">or continue with</span>
                </div>
            </div>

            <!-- SSO Options -->
            <div class="sso-section">
                <button type="button" class="sso-btn">
                    <svg class="h-5 w-5" width="20" height="20" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"></path>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335"></path>
                    </svg>
                    <span>Google</span>
                </button>
                <button type="button" class="sso-btn">
                    <svg class="h-5 w-5 fill-current" width="20" height="20" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.477 2 12c0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.341-3.369-1.341-.454-1.152-1.11-1.459-1.11-1.459-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482C19.138 20.161 22 16.416 22 12c0-5.523-4.477-10-10-10z"></path>
                    </svg>
                    <span>GitHub</span>
                </button>
            </div>
        </div>

        <!-- Footer Context -->
        <footer class="footer-section">
            <p class="footer-text">
                Don't have an account?
                <a href="#" class="footer-link">Request Workspace</a>
            </p>
            <div class="footer-bottom">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">v2.4.0</a>
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
JS;
$this->registerJs($script);
?>