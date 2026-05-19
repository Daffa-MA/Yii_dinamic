<?php

/** @var yii\web\View $this */
/** @var app\models\Project $project */
/** @var app\models\ProjectUser $user */
/** @var app\models\WorkspaceSettings $workspaceSettings */
/** @var string|null $landingRoute */

use yii\bootstrap5\Html;

$projectName = Html::encode((string)$project->name);
$username = Html::encode((string)$user->username);
$role = Html::encode((string)$user->role);
$landingRoute = $landingRoute ?? null;

$this->registerCss(<<<CSS
.access-denied-shell {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1.08fr 0.92fr;
    background:
        radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 28%),
        radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.06), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
    color: #0f172a;
    font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
}
.access-denied-hero {
    padding: clamp(2rem, 5vw, 5rem);
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.access-denied-hero::before,
.access-denied-hero::after {
    content: '';
    position: absolute;
    border-radius: 999px;
    filter: blur(14px);
    pointer-events: none;
}
.access-denied-hero::before {
    width: 24rem;
    height: 24rem;
    top: -6rem;
    left: -8rem;
    background: rgba(37, 99, 235, 0.12);
}
.access-denied-hero::after {
    width: 18rem;
    height: 18rem;
    right: -5rem;
    bottom: -4rem;
    background: rgba(15, 23, 42, 0.06);
}
.access-denied-brand {
    display: inline-flex;
    align-items: center;
    gap: .9rem;
    padding: .85rem 1rem;
    border-radius: 20px;
    background: rgba(255,255,255,0.72);
    border: 1px solid rgba(148,163,184,0.25);
    box-shadow: 0 14px 45px rgba(15,23,42,.06);
    width: fit-content;
    position: relative;
    z-index: 1;
    backdrop-filter: blur(16px);
}
.access-denied-mark {
    width: 3rem;
    height: 3rem;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1d4ed8 0%, #0f172a 100%);
    color: #fff;
    flex-shrink: 0;
}
.access-denied-mark .material-symbols-outlined {
    font-size: 1.4rem;
}
.access-denied-kicker {
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #64748b;
}
.access-denied-title {
    margin: 1.4rem 0 .85rem;
    font-size: clamp(2.4rem, 4.8vw, 4.6rem);
    line-height: 1.02;
    letter-spacing: -0.05em;
    font-weight: 900;
    max-width: 11ch;
}
.access-denied-subtitle {
    max-width: 42rem;
    margin: 0;
    font-size: 1.02rem;
    line-height: 1.75;
    color: #475569;
}
.access-denied-meta {
    margin-top: 1.6rem;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    max-width: 32rem;
}
.access-denied-meta-card {
    background: rgba(255,255,255,0.84);
    border: 1px solid rgba(148,163,184,0.2);
    border-radius: 18px;
    padding: 1rem 1.1rem;
    box-shadow: 0 10px 30px rgba(15,23,42,.05);
}
.access-denied-meta-card span {
    display: block;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: .5rem;
}
.access-denied-meta-card strong {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    word-break: break-word;
}
.access-denied-panel {
    padding: clamp(1.2rem, 3vw, 2.2rem);
    display: flex;
    align-items: center;
    justify-content: center;
}
.access-denied-card {
    width: min(100%, 560px);
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(148,163,184,0.22);
    border-radius: 28px;
    box-shadow: 0 24px 70px rgba(15,23,42,.08);
    padding: 1.8rem;
    backdrop-filter: blur(18px);
}
.access-denied-badge {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .45rem .8rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: .82rem;
    font-weight: 700;
}
.access-denied-card h2 {
    margin: 1rem 0 .55rem;
    font-size: 1.6rem;
    letter-spacing: -0.03em;
    color: #0f172a;
}
.access-denied-card p {
    margin: 0;
    color: #475569;
    line-height: 1.75;
}
.access-denied-note {
    margin-top: 1.2rem;
    border-radius: 20px;
    padding: 1rem 1.05rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.access-denied-note-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: .6rem;
}
.access-denied-note-title .material-symbols-outlined {
    font-size: 1.1rem;
    color: #1d4ed8;
}
.access-denied-list {
    margin: 0;
    padding-left: 1.1rem;
    color: #475569;
    line-height: 1.7;
}
.access-denied-actions {
    margin-top: 1.5rem;
    display: grid;
    gap: .8rem;
}
.access-denied-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    min-height: 48px;
    border-radius: 16px;
    border: 1px solid transparent;
    text-decoration: none;
    font-weight: 800;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.access-denied-btn:hover {
    transform: translateY(-1px);
}
.access-denied-btn-primary {
    background: linear-gradient(135deg, #1d4ed8 0%, #0f172a 100%);
    color: #fff;
    box-shadow: 0 14px 34px rgba(29,78,216,.22);
}
.access-denied-btn-secondary {
    background: rgba(15,23,42,.04);
    color: #0f172a;
    border-color: rgba(148,163,184,.28);
}
.access-denied-footer {
    margin-top: 1rem;
    text-align: center;
    color: #64748b;
    font-size: .92rem;
}
.access-denied-footer a {
    color: #1d4ed8;
    font-weight: 700;
    text-decoration: none;
}
@media (max-width: 980px) {
    .access-denied-shell { grid-template-columns: 1fr; }
    .access-denied-hero { padding-bottom: 1rem; }
    .access-denied-title { max-width: 14ch; }
}
@media (max-width: 640px) {
    .access-denied-meta { grid-template-columns: 1fr; }
    .access-denied-card { padding: 1.35rem; border-radius: 22px; }
}
CSS);
?>

<div class="access-denied-shell">
    <section class="access-denied-hero">
        <div class="access-denied-brand">
            <div class="access-denied-mark">
                <span class="material-symbols-outlined">lock</span>
            </div>
            <div>
                <div class="access-denied-kicker"><?= $projectName ?></div>
                <div style="margin-top:.2rem; color:#334155; font-weight:600;">Workspace access</div>
            </div>
        </div>

        <h1 class="access-denied-title">Akses belum diatur</h1>
        <p class="access-denied-subtitle">
            Login berhasil, tetapi role Anda belum punya halaman workspace yang boleh dibuka. Ini bukan gagal login.
            Aplikasi berhenti di sini karena permission untuk role tersebut masih kosong atau belum diarahkan ke landing page yang aman.
        </p>

        <div class="access-denied-meta">
            <div class="access-denied-meta-card">
                <span>User</span>
                <strong><?= $username ?></strong>
            </div>
            <div class="access-denied-meta-card">
                <span>Role</span>
                <strong><?= $role ?></strong>
            </div>
        </div>
    </section>

    <section class="access-denied-panel">
        <div class="access-denied-card">
            <div class="access-denied-badge">
                <span class="material-symbols-outlined" style="font-size: 1rem;">shield_lock</span>
                Permission required
            </div>

            <h2>Role ini belum punya akses aktif</h2>
            <p>Admin perlu menentukan menu, page, atau System Builder mana yang boleh dibuka untuk role ini.</p>

            <div class="access-denied-note">
                <div class="access-denied-note-title">
                    <span class="material-symbols-outlined">tips_and_updates</span>
                    Yang perlu dilakukan admin
                </div>
                <ul class="access-denied-list">
                    <li>centang akses role di <strong>/settings/workspace/permissions</strong></li>
                    <li>aktifkan akses menu/page yang relevan</li>
                    <li>simpan perubahan lalu login ulang jika perlu</li>
                </ul>
            </div>

            <div class="access-denied-actions">
                <?php if ($landingRoute !== null): ?>
                    <?= Html::a(
                        'Buka halaman yang tersedia',
                        $landingRoute,
                        ['class' => 'access-denied-btn access-denied-btn-primary']
                    ) ?>
                <?php endif; ?>

                <?= Html::a(
                    'Coba login lagi',
                    ['project/login', 'id' => $project->id, 'force_login' => 1],
                    ['class' => 'access-denied-btn access-denied-btn-secondary']
                ) ?>

                <?= $this->render('../layouts/_logout_button', [
                    'label' => 'Logout',
                    'icon' => '',
                    'buttonClass' => 'access-denied-btn access-denied-btn-secondary',
                    'formStyle' => 'margin:0;',
                ]) ?>
            </div>

            <?php if ((new \app\components\CommanderAuthContext())->isSuperAdmin()): ?>
                <div class="access-denied-footer">
                    Masih belum ada akses? <a href="<?= Html::encode((new \app\components\DomainContext())->projectListUrl()) ?>">Pilih project lain</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
