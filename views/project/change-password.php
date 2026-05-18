<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\ProjectUser $user */
/** @var string $returnUrl */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Ganti Password Aplikasi';

$projectName = Html::encode($project->name);
$username = Html::encode($user->username);

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
    background: linear-gradient(135deg, #4f46e5 0%, #2563eb 100%);
    overflow: hidden;
    flex-shrink: 0;
}
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
    border-radius: 28px;
    background: rgba(255,255,255,0.96);
    color: #0f172a;
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
    color: #0f172a;
}
.project-login-input {
    width: 100%;
    min-height: 3.25rem;
    border-radius: 28px;
    border: 1px solid rgba(148,163,184,0.22);
    background: rgba(255,255,255,0.92);
    padding: 0.9rem 1rem;
    color: #0f172a;
}
.project-login-submit {
    min-height: 3.35rem;
    border: none;
    border-radius: 28px;
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    color: #fff;
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

<div class="project-login-shell">
    <section class="project-login-hero" style="background: linear-gradient(135deg, #07111f 0%, #111827 100%);">
        <div class="project-login-brand">
            <div class="project-login-brand-box">
                <span class="material-symbols-outlined">lock</span>
            </div>
            <div>
                <div style="font-size:0.72rem;letter-spacing:0.16em;text-transform:uppercase;color:rgba(255,255,255,0.6);font-weight:800;">Security</div>
                <div style="font-size:1rem;font-weight:800;">Password Reset</div>
            </div>
        </div>

        <h1 class="project-login-title">Ganti Password</h1>
        <p class="project-login-subtitle">Akun default harus diganti sebelum akses workspace dibuka. Proses ini hanya berlaku untuk aplikasi aktif.</p>
        <div class="project-login-kicker"><?= $projectName ?></div>

        <div class="project-login-meta">
            <div class="project-login-meta-card">
                <span>User</span>
                <strong><?= $username ?></strong>
            </div>
            <div class="project-login-meta-card">
                <span>Status</span>
                <strong>Must change password</strong>
            </div>
        </div>
    </section>

    <section class="project-login-panel">
        <div class="project-login-card">
            <h2>Ganti Password Default</h2>
            <p>Gunakan password saat ini, lalu simpan password baru untuk melanjutkan ke dashboard aplikasi.</p>

            <?php $form = ActiveForm::begin([
                'id' => 'project-change-password-form',
                'fieldConfig' => [
                    'template' => "{input}\n{error}",
                    'errorOptions' => ['class' => 'text-sm text-red-600 mt-1', 'tag' => 'div'],
                ],
            ]); ?>

            <div class="project-login-form">
                <div class="project-login-field">
                    <label>Password Saat Ini</label>
                    <input type="password" name="current_password" required class="project-login-input">
                </div>
                <div class="project-login-field">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" required minlength="6" class="project-login-input">
                </div>
                <div class="project-login-field">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required minlength="6" class="project-login-input">
                </div>

                <input type="hidden" name="return_url" value="<?= Html::encode($returnUrl) ?>">

                <button type="submit" class="project-login-submit">Simpan Password Baru</button>
            </div>

            <div class="project-login-helper">
                Setelah password diganti, session aplikasi tetap aktif dan Anda akan diarahkan ke return URL jika tersedia.
            </div>

            <div class="project-login-footer">
                <?= Html::a('Kembali ke login aplikasi', ['project/login', 'id' => $project->id, 'return_url' => $returnUrl]) ?>
                <?php if ((new \app\components\CommanderAuthContext())->isSuperAdmin()): ?>
                    <?= Html::a('Project list', (new \app\components\DomainContext())->projectListUrl()) ?>
                <?php endif; ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </section>
</div>
