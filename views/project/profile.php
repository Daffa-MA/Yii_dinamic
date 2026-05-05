<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var app\models\Project $project */
/** @var int $totalForms */
/** @var int $totalSubmissions */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'My Profile - ' . Html::encode($project->name);
$this->registerJs("document.body.classList.add('project-page-v4');", \yii\web\View::POS_READY);

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
?>

<style>
    .project-page-v4 {
        background:
            radial-gradient(circle at top right, rgba(15, 118, 110, 0.10), transparent 24%),
            radial-gradient(circle at left center, rgba(30, 64, 175, 0.08), transparent 26%),
            linear-gradient(180deg, #f8fafc 0%, #f4f7fb 100%);
        color: #0f172a;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .project-page-v4 main#main > .container {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .profile-shell {
        min-height: 100vh;
        padding-left: var(--app-sidebar-width, 16rem);
        position: relative;
        transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .profile-shell::before,
    .profile-shell::after {
        content: '';
        position: absolute;
        inset: auto;
        pointer-events: none;
        border-radius: 999px;
        filter: blur(28px);
        opacity: 0.6;
    }

    .profile-shell::before {
        top: 7rem;
        right: 2rem;
        width: 16rem;
        height: 16rem;
        background: rgba(15, 118, 110, 0.12);
    }

    .profile-shell::after {
        left: 1rem;
        bottom: 8rem;
        width: 18rem;
        height: 18rem;
        background: rgba(30, 64, 175, 0.08);
    }

    .profile-main-content {
        position: relative;
        z-index: 1;
        padding: 2rem 0 3rem;
    }

    .profile-container {
        max-width: 1480px;
    }

    .profile-surface {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.82);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(18px);
    }

    .profile-surface::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.34), transparent 38%);
        pointer-events: none;
    }

    .profile-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.7fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .profile-hero-copy {
        padding: 2rem;
    }

    .profile-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.6rem 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        background: rgba(255, 255, 255, 0.84);
        color: #334155;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .profile-kicker .material-symbols-outlined {
        font-size: 1rem;
        color: #0f766e;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .profile-title {
        margin: 1.4rem 0 1rem;
        max-width: 12ch;
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: clamp(2.5rem, 4.8vw, 4.2rem);
        line-height: 1.02;
        font-weight: 800;
        letter-spacing: -0.05em;
        color: #0f172a;
    }

    .profile-title-accent {
        color: #0f766e;
    }

    .profile-hero-text {
        max-width: 42rem;
        margin: 0;
        color: #475569;
        font-size: 1.02rem;
        line-height: 1.8;
    }

    .profile-stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1.75rem;
    }

    .profile-stat-card {
        border-radius: 22px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.9));
        padding: 1rem 1.05rem;
    }

    .profile-stat-label {
        display: block;
        margin-bottom: 0.45rem;
        color: #64748b;
        font-size: 0.73rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .profile-stat-value {
        display: block;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .profile-stat-hint {
        display: block;
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .profile-hero-side {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .profile-spotlight {
        padding: 1.75rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, 0.16), transparent 30%),
            linear-gradient(145deg, #0f172a 0%, #162033 60%, #1c2f43 100%);
        border-color: rgba(148, 163, 184, 0.08);
        color: #fff;
    }

    .profile-spotlight::before {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), transparent 42%);
    }

    .profile-spotlight-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .profile-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2rem;
        padding: 0 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-chip .material-symbols-outlined {
        font-size: 1rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .profile-status-dot {
        width: 0.65rem;
        height: 0.65rem;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
    }

    .profile-spotlight-title {
        margin: 0 0 0.85rem;
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .profile-spotlight-text {
        margin: 0;
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.95rem;
        line-height: 1.7;
    }

    .profile-spotlight-meta {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .profile-spotlight-meta-card {
        padding: 0.9rem;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.06);
    }

    .profile-spotlight-meta-card span {
        display: block;
        color: rgba(255, 255, 255, 0.58);
        font-size: 0.73rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-spotlight-meta-card strong {
        display: block;
        margin-top: 0.35rem;
        color: #fff;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .profile-panels {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .profile-panel {
        padding: 1.75rem;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 22px;
        border: 1px solid rgba(148, 163, 184, 0.18);
    }

    .profile-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .profile-panel-title {
        font-family: 'Manrope', 'Inter', sans-serif;
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
    }

    .profile-panel-icon {
        width: 2.75rem;
        height: 2.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: linear-gradient(135deg, #0f172a 0%, #1f2937 100%);
        color: #fff;
    }

    .profile-panel-icon .material-symbols-outlined {
        font-size: 1.35rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 24;
    }

    .profile-panel-icon.teal {
        background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
    }

    .profile-panel-icon.purple {
        background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    }

    .profile-panel-icon.amber {
        background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    }

    .profile-info-list {
        display: flex;
        flex-direction: column;
    }

    .profile-info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .profile-info-item:last-child {
        border-bottom: none;
    }

    .profile-info-item-left {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .profile-info-item-right {
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .profile-info-item-right code {
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        background: #f1f5f9;
        font-size: 0.85rem;
        font-family: 'Inter', sans-serif;
    }

    .profile-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .profile-status-badge::before {
        content: '';
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 50%;
        background: #10b981;
    }

    .profile-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .profile-field {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .profile-field.full {
        grid-column: span 2;
    }

    .profile-field label {
        color: #475569;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .profile-input {
        width: 100%;
        padding: 0.8rem 1rem;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #fff;
        color: #0f172a;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .profile-input:focus {
        outline: none;
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .profile-input::placeholder {
        color: #94a3b8;
    }

    .profile-input-hint {
        color: #64748b;
        font-size: 0.8rem;
    }

    .profile-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        margin-top: 1.5rem;
    }

    .profile-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        min-height: 3.35rem;
        padding: 0 1.25rem;
        border-radius: 16px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
    }

    .profile-button:hover {
        transform: translateY(-2px);
    }

    .profile-button .material-symbols-outlined {
        font-size: 1.15rem;
        font-variation-settings: 'FILL' 1, 'wght' 500, 'GRAD' 0, 'opsz' 20;
    }

    .profile-button-primary {
        border: 1px solid #0f172a;
        background: linear-gradient(135deg, #0f172a 0%, #1f2937 100%);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.16);
        color: #fff;
    }

    .profile-button-primary:hover {
        color: #fff;
        box-shadow: 0 22px 42px rgba(15, 23, 42, 0.22);
    }

    .profile-button-secondary {
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(255, 255, 255, 0.88);
        color: #0f172a;
    }

    .profile-button-secondary:hover {
        color: #0f172a;
        border-color: rgba(15, 118, 110, 0.28);
        box-shadow: 0 16px 30px rgba(148, 163, 184, 0.14);
    }

    .profile-quick-links {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .profile-quick-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(255, 255, 255, 0.84);
        color: #0f172a;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .profile-quick-link:hover {
        border-color: rgba(15, 118, 110, 0.28);
        transform: translateX(4px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        color: #0f172a;
    }

    .profile-quick-link .material-symbols-outlined {
        font-size: 1.25rem;
        color: #64748b;
    }

    @media (max-width: 1199.98px) {
        .profile-hero {
            grid-template-columns: 1fr;
        }

        .profile-panels {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .profile-shell {
            padding-left: 0;
        }

        .profile-main-content {
            padding-top: 1.5rem;
        }

        .profile-hero-copy,
        .profile-panel,
        .profile-spotlight {
            padding: 1.4rem;
        }

        .profile-stat-grid {
            grid-template-columns: 1fr;
        }

        .profile-form-grid {
            grid-template-columns: 1fr;
        }

        .profile-field.full {
            grid-column: span 1;
        }
    }

    @media (max-width: 767.98px) {
        .profile-container {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .profile-title {
            max-width: none;
        }

        .profile-spotlight-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'profile', 'sidebarVariant' => 'minimal']) ?>

<div class="profile-shell">
    <section class="profile-main-content">
        <div class="container-fluid profile-container">
            <section class="profile-hero">
                <div class="profile-surface profile-hero-copy">
                    <span class="profile-kicker">
                        <span class="material-symbols-outlined">person</span>
                        My Profile
                    </span>

                    <h1 class="profile-title">
                        Profil <span class="profile-title-accent">Workspace.</span>
                    </h1>

                    <p class="profile-hero-text">
                        Welcome back, <strong><?= Html::encode($user->username) ?></strong>. 
                        Kelola informasi akun dan password Anda di halaman ini.
                    </p>

                    <div class="profile-stat-grid">
                        <div class="profile-stat-card">
                            <span class="profile-stat-label">Total Forms</span>
                            <strong class="profile-stat-value"><?= $totalForms ?></strong>
                            <span class="profile-stat-hint">Form yang telah Anda buat.</span>
                        </div>
                        <div class="profile-stat-card">
                            <span class="profile-stat-label">Total Submissions</span>
                            <strong class="profile-stat-value"><?= $totalSubmissions ?></strong>
                            <span class="profile-stat-hint">Data masuk dari form Anda.</span>
                        </div>
                        <div class="profile-stat-card">
                            <span class="profile-stat-label">Member Since</span>
                            <strong class="profile-stat-value"><?= date('M Y', strtotime($user->created_at)) ?></strong>
                            <span class="profile-stat-hint">Bergabung sejak.</span>
                        </div>
                    </div>
                </div>

                <div class="profile-hero-side">
                    <aside class="profile-surface profile-spotlight">
                        <div class="profile-spotlight-top">
                            <span class="profile-chip">
                                <span class="material-symbols-outlined">folder</span>
                                Active Project
                            </span>
                            <span class="profile-status-dot" aria-hidden="true"></span>
                        </div>

                        <h2 class="profile-spotlight-title"><?= Html::encode($project->name) ?></h2>
                        <p class="profile-spotlight-text">
                            Project ini adalah workspace aktif Anda saat ini. 
                            Semua form dan tabel yang Anda buat akan terhubung dengan project ini.
                        </p>

                        <div class="profile-spotlight-meta">
                            <div class="profile-spotlight-meta-card">
                                <span>Project ID</span>
                                <strong><?= $project->id ?></strong>
                            </div>
                            <div class="profile-spotlight-meta-card">
                                <span>Status</span>
                                <strong>Active</strong>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <div class="profile-panels">
                <div class="profile-panel">
                    <div class="profile-panel-head">
                        <h2 class="profile-panel-title">Account Details</h2>
                        <div class="profile-panel-icon">
                            <span class="material-symbols-outlined">account_circle</span>
                        </div>
                    </div>

                    <div class="profile-info-list">
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Username</span>
                            <span class="profile-info-item-right"><?= Html::encode($user->username) ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">User ID</span>
                            <span class="profile-info-item-right"><code><?= $user->id ?></code></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Account Status</span>
                            <span class="profile-info-item-right">
                                <span class="profile-status-badge">Active</span>
                            </span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Last Updated</span>
                            <span class="profile-info-item-right"><?= date('M d, Y H:i', strtotime($user->updated_at)) ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-panel">
                    <div class="profile-panel-head">
                        <h2 class="profile-panel-title">Change Password</h2>
                        <div class="profile-panel-icon teal">
                            <span class="material-symbols-outlined">lock</span>
                        </div>
                    </div>

                    <?php $form = ActiveForm::begin([
                        'action' => ['site/change-password'],
                        'method' => 'post',
                    ]); ?>
                    <div class="profile-form-grid">
                        <div class="profile-field">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="profile-input" placeholder="Enter current password" required>
                        </div>
                        <div class="profile-field">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="profile-input" placeholder="Enter new password" minlength="6" required>
                            <span class="profile-input-hint">Minimum 6 characters</span>
                        </div>
                        <div class="profile-field full">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="profile-input" placeholder="Confirm new password" minlength="6" required>
                        </div>
                    </div>
                    <div class="profile-action-row">
                        <button type="submit" class="profile-button profile-button-primary">
                            <span class="material-symbols-outlined">lock</span>
                            <span>Update Password</span>
                        </button>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>

                <div class="profile-panel">
                    <div class="profile-panel-head">
                        <h2 class="profile-panel-title">Project Info</h2>
                        <div class="profile-panel-icon purple">
                            <span class="material-symbols-outlined">folder</span>
                        </div>
                    </div>

                    <div class="profile-info-list">
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Project Name</span>
                            <span class="profile-info-item-right"><?= Html::encode($project->name) ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Project ID</span>
                            <span class="profile-info-item-right"><code><?= $project->id ?></code></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Total Forms</span>
                            <span class="profile-info-item-right"><?= $totalForms ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="profile-info-item-left">Total Submissions</span>
                            <span class="profile-info-item-right"><?= $totalSubmissions ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-panel">
                    <div class="profile-panel-head">
                        <h2 class="profile-panel-title">Quick Actions</h2>
                        <div class="profile-panel-icon amber">
                            <span class="material-symbols-outlined">bolt</span>
                        </div>
                    </div>

                    <div class="profile-quick-links">
                        <?= Html::a('<span>Create New Form</span><span class="material-symbols-outlined">arrow_forward</span>', ['form/create'], ['class' => 'profile-quick-link']) ?>
                        <?= Html::a('<span>Create Table</span><span class="material-symbols-outlined">arrow_forward</span>', ['table-builder/create'], ['class' => 'profile-quick-link']) ?>
                        <?= Html::a('<span>View Forms</span><span class="material-symbols-outlined">arrow_forward</span>', ['form/index'], ['class' => 'profile-quick-link']) ?>
                        <?= Html::a('<span>View Tables</span><span class="material-symbols-outlined">arrow_forward</span>', ['table-builder/index'], ['class' => 'profile-quick-link']) ?>
                        <?= Html::a('<span>Back to Projects</span><span class="material-symbols-outlined">arrow_forward</span>', ['project/index'], ['class' => 'profile-quick-link']) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>