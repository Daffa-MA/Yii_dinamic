<?php

/** @var yii\web\View $this */
/** @var app\models\ProjectLoginForm $model */
/** @var app\models\Project $project */
/** @var app\models\WorkspaceSettings $workspaceSettings */
/** @var string $returnUrl */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Login Aplikasi';

$loginTheme = strtolower((string)($workspaceSettings->login_theme ?? 'dark'));
$heroBgStart = (string)($workspaceSettings->login_background_start ?? $workspaceSettings->sidebar_bg_start ?? '#07111f');
$heroBgEnd = (string)($workspaceSettings->login_background_end ?? $workspaceSettings->sidebar_bg_end ?? '#111827');
$loginCardBg = (string)($workspaceSettings->login_card_color ?? 'rgba(255,255,255,0.96)');
$loginButtonBg = (string)($workspaceSettings->login_button_color ?? $workspaceSettings->sidebar_active_bg_start ?? '#2563eb');
$loginAccent = (string)($workspaceSettings->login_accent_color ?? $workspaceSettings->workspace_logo_bg ?? '#4f46e5');
$loginTextColor = (string)($workspaceSettings->login_text_color ?? '#0f172a');
$loginRadius = (int)($workspaceSettings->login_border_radius ?? 28);
$loginBackgroundAsset = $workspaceSettings->getLoginBackgroundAsset();
$loginBgImageUrl = (string)($loginBackgroundAsset['url'] ?? '');
$loginBgType = (string)($loginBackgroundAsset['type'] ?? 'none');
$loginBgDebug = method_exists($workspaceSettings, 'getLoginBackgroundDebug') ? $workspaceSettings->getLoginBackgroundDebug() : [];
$loginBgCssUrl = str_replace(["\\", "'"], ["\\\\", "\\'"], $loginBgImageUrl);
$logoAsset = method_exists($workspaceSettings, 'getWorkspaceLogoAsset') ? $workspaceSettings->getWorkspaceLogoAsset() : ['url' => ''];
$logoImage = (string)($logoAsset['url'] ?? '');
$logoIcon = Html::encode($workspaceSettings->workspace_logo_icon ?? 'workspace_premium');
$workspaceTitle = Html::encode($workspaceSettings->workspace_title ?? $project->name);
$workspaceSubtitle = Html::encode($workspaceSettings->workspace_subtitle ?? 'Workspace aplikasi aktif');
$loginTitle = Html::encode($workspaceSettings->login_title ?? 'Login Aplikasi');
$loginSubtitle = Html::encode($workspaceSettings->login_subtitle ?? 'Masuk ke aplikasi Anda');
$buttonText = $loginTheme === 'light' ? '#0f172a' : '#ffffff';
$heroStyle = "background: linear-gradient(135deg, {$heroBgStart} 0%, {$heroBgEnd} 100%);";
if ($loginBgImageUrl !== '' && $loginBgType === 'image') {
    $heroStyle = "background-image: linear-gradient(rgba(0,0,0,.35), rgba(0,0,0,.35)), url('{$loginBgCssUrl}'); background-size: cover; background-position: center; background-repeat: no-repeat;";
}

$this->registerCss(<<<CSS
.project-login-shell {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    color: #fff;
    font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
}
.project-login-hero {
    padding: clamp(2rem, 5vw, 4.5rem);
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
    $heroStyle
}
.project-login-hero-video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    pointer-events: none;
}
.project-login-hero-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 0;
}
.project-login-hero::before,
.project-login-hero::after {
    content: '';
    position: absolute;
    border-radius: 999px;
    filter: blur(12px);
}
.project-login-hero::before {
    inset: auto auto 12% -8%;
    width: 22rem;
    height: 22rem;
    background: rgba(255,255,255,0.08);
}
.project-login-hero::after {
    top: 12%;
    right: -6%;
    width: 18rem;
    height: 18rem;
    background: rgba(255,255,255,0.06);
}
.project-login-brand {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 0.8rem 1rem;
    border-radius: 20px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.08);
    width: fit-content;
    position: relative;
    z-index: 1;
}
.project-login-brand-box {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, {$loginAccent} 0%, {$loginButtonBg} 100%);
    overflow: hidden;
    flex-shrink: 0;
}
.project-login-brand-box img { width: 100%; height: 100%; object-fit: cover; }
.project-login-brand-box .material-symbols-outlined { font-size: 1.5rem; color: #fff; }
.project-login-title {
    margin: 1.5rem 0 0.8rem;
    max-width: 13ch;
    font-size: clamp(2.6rem, 5vw, 4.8rem);
    line-height: 1;
    letter-spacing: -0.05em;
    font-weight: 900;
    position: relative;
    z-index: 1;
}
.project-login-subtitle {
    max-width: 40rem;
    color: rgba(255,255,255,0.78);
    font-size: 1.02rem;
    line-height: 1.7;
    margin: 0;
    position: relative;
    z-index: 1;
}
.project-login-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    width: fit-content;
    padding: .45rem .8rem;
    margin-top: 1.25rem;
    border-radius: 999px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.1);
    color: rgba(255,255,255,.78);
    font-size: .72rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-weight: 800;
    position: relative;
    z-index: 1;
}
.project-login-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-top: 2rem;
    max-width: 34rem;
    position: relative;
    z-index: 1;
}
.project-login-meta-card {
    padding: 1rem 1.05rem;
    border-radius: 18px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.08);
}
.project-login-meta-card span {
    display: block;
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.62);
    font-weight: 700;
}
.project-login-meta-card strong {
    display: block;
    margin-top: 0.35rem;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    word-break: break-word;
}
.project-login-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.project-login-card {
    width: 100%;
    max-width: 32rem;
    border-radius: {$loginRadius}px;
    background: {$loginCardBg};
    color: {$loginTextColor};
    box-shadow: 0 30px 60px rgba(15,23,42,0.22);
    padding: 2rem;
    border: 1px solid rgba(255,255,255,0.28);
    backdrop-filter: blur(18px);
}
.project-login-card h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 900;
    letter-spacing: -0.03em;
    color: {$loginTextColor};
}
.project-login-card p {
    margin: 0.5rem 0 0;
    color: #64748b;
    line-height: 1.7;
}
.project-login-form {
    margin-top: 1.5rem;
    display: grid;
    gap: 1rem;
}
.project-login-field label {
    display: block;
    margin-bottom: 0.45rem;
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: {$loginTextColor};
}
.project-login-input {
    width: 100%;
    min-height: 3.25rem;
    border-radius: {$loginRadius}px;
    border: 1px solid rgba(148,163,184,0.22);
    background: rgba(255,255,255,0.92);
    padding: 0.9rem 1rem;
    color: #0f172a;
}
.project-login-input:focus {
    outline: none;
    border-color: {$loginButtonBg};
    box-shadow: 0 0 0 4px rgba(37,99,235,0.10);
}
.project-login-submit {
    min-height: 3.35rem;
    border: none;
    border-radius: {$loginRadius}px;
    background: linear-gradient(135deg, {$loginButtonBg} 0%, {$loginAccent} 100%);
    color: {$buttonText};
    font-weight: 800;
    letter-spacing: 0.02em;
    box-shadow: 0 18px 32px rgba(37,99,235,0.24);
}
    .project-login-helper {
    margin-top: .9rem;
    padding: .85rem 1rem;
    border-radius: 16px;
    background: rgba(15, 23, 42, 0.04);
    color: #475569;
    line-height: 1.6;
    font-size: .92rem;
}
.project-login-footer {
    margin-top: 1rem;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: #64748b;
}
.project-login-footer a {
    color: #0f766e;
    font-weight: 700;
    text-decoration: none;
}
@media (max-width: 991.98px) {
    .project-login-shell { grid-template-columns: 1fr; }
    .project-login-hero { padding: 2rem; }
    .project-login-panel { padding: 1.25rem 1rem 2rem; }
    .project-login-meta { grid-template-columns: 1fr; }
}
CSS);
?>

<!-- project-login-background-debug <?= Html::encode(json_encode([
    'project_id' => (int)$project->id,
    'workspace_settings_id' => $loginBgDebug['workspace_settings_id'] ?? null,
    'setting_key' => $workspaceSettings->setting_key ?? null,
    'loaded_setting_key' => $loginBgDebug['loaded_setting_key'] ?? null,
    'loaded_from' => $loginBgDebug['loaded_from'] ?? null,
    'background_path' => $loginBgDebug['background_path'] ?? '',
    'generated_background_url' => $loginBgImageUrl,
    'type' => $loginBgType,
    'file_exists' => $loginBgDebug['file_exists'] ?? null,
    'fallback_reason' => $loginBgDebug['fallback_reason'] ?? '',
    'logo_path' => $loginBgDebug['logo_path'] ?? '',
    'logo_generated_url' => $logoImage,
    'logo_local_file_exists' => $loginBgDebug['logo_local_file_exists'] ?? null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?> -->

<div class="project-login-shell">
    <section class="project-login-hero">
        <?php if ($loginBgImageUrl !== '' && $loginBgType === 'video'): ?>
            <video class="project-login-hero-video" autoplay muted loop playsinline>
                <source src="<?= Html::encode($loginBgImageUrl) ?>">
            </video>
            <div class="project-login-hero-overlay"></div>
        <?php endif; ?>
        <div class="project-login-brand">
            <div class="project-login-brand-box">
                <?php if ($logoImage !== ''): ?>
                    <img src="<?= $logoImage ?>" alt="Logo">
                <?php else: ?>
                    <span class="material-symbols-outlined"><?= $logoIcon ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-size:0.72rem;letter-spacing:0.16em;text-transform:uppercase;color:rgba(255,255,255,0.6);font-weight:800;">Commander Workspace</div>
                <div style="font-size:1rem;font-weight:800;"><?= $workspaceTitle ?></div>
            </div>
        </div>

        <h1 class="project-login-title"><?= $loginTitle ?></h1>
        <p class="project-login-subtitle"><?= $loginSubtitle ?></p>
        <div class="project-login-kicker"><?= $workspaceSubtitle ?></div>

        <div class="project-login-meta">
            <div class="project-login-meta-card">
                <span>Aplikasi</span>
                <strong><?= Html::encode($project->name) ?></strong>
            </div>
        </div>
    </section>

    <section class="project-login-panel">
        <div class="project-login-card">
            <h2>Login Aplikasi</h2>
            <p>Gunakan akun yang tersimpan di database aplikasi ini. Layout ini berdiri sendiri dan tidak memakai shell admin.</p>

            <?php $form = ActiveForm::begin([
                'id' => 'project-login-form',
                'fieldConfig' => [
                    'template' => "{input}\n{error}",
                    'errorOptions' => ['class' => 'text-sm text-red-600 mt-1', 'tag' => 'div'],
                ],
            ]); ?>

            <div class="project-login-form">
                <div class="project-login-field">
                    <label for="projectloginform-username">Username</label>
                    <?= $form->field($model, 'username')->textInput([
                        'class' => 'project-login-input',
                        'placeholder' => 'admin',
                        'autofocus' => true,
                    ])->label(false) ?>
                </div>

                <div class="project-login-field">
                    <label for="projectloginform-password">Password</label>
                    <?= $form->field($model, 'password')->passwordInput([
                        'class' => 'project-login-input',
                        'placeholder' => '••••••••',
                    ])->label(false) ?>
                </div>

                <input type="hidden" name="return_url" value="<?= Html::encode($returnUrl) ?>">

                <button type="submit" class="project-login-submit">Masuk ke Aplikasi</button>
            </div>

            <?php if ((new \app\components\CommanderAuthContext())->isSuperAdmin()): ?>
                <div class="project-login-footer">
                    <?= Html::a('Kembali ke project list', (new \app\components\DomainContext())->projectListUrl()) ?>
                    <span><?= Html::encode($workspaceTitle) ?></span>
                </div>
            <?php else: ?>
                <div class="project-login-footer">
                    <span><?= Html::encode($workspaceTitle) ?></span>
                </div>
            <?php endif; ?>

            <?php ActiveForm::end(); ?>
        </div>
    </section>
</div>
