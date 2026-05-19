<?php

/** @var yii\web\View $this */
/** @var app\models\User|null $user */
/** @var string $username */
/** @var string $email */
/** @var string $role */
/** @var string $status */
/** @var string $loggedInAt */
/** @var string $sessionKey */

use yii\bootstrap5\Html;

$this->title = 'My Account';

$username = trim((string)($username ?? 'superadmin'));
$email = trim((string)($email ?? ''));
$role = strtolower(trim((string)($role ?? 'superadmin')));
$status = trim((string)($status ?? 'Active'));
$loggedInAt = trim((string)($loggedInAt ?? ''));
$sessionKey = trim((string)($sessionKey ?? 'commander_auth'));
$avatarInitial = strtoupper(substr($username !== '' ? $username : 'S', 0, 1));
$roleLabel = $role === 'superadmin' ? 'Superadmin' : ucfirst(str_replace(['_', '-'], ' ', $role));
$memberSince = $user !== null && !empty($user->created_at) ? date('d M Y', strtotime((string)$user->created_at)) : '-';
$lastUpdated = $user !== null && !empty($user->updated_at) ? date('d M Y H:i', strtotime((string)$user->updated_at)) : '-';
?>

<style>
    .commander-profile-page {
        min-height: calc(100vh - 96px);
        margin: -1rem -0.75rem 0;
        padding: 34px 18px 64px;
        background: #f8fafc;
        color: #0f172a;
    }

    .commander-profile-wrap {
        width: min(1040px, 100%);
        margin: 0 auto;
    }

    .commander-profile-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 24px;
    }

    .commander-profile-title {
        margin: 0 0 7px;
        color: #111827;
        font-size: 30px;
        line-height: 1.2;
        font-weight: 750;
        letter-spacing: 0;
    }

    .commander-profile-subtitle {
        margin: 0;
        max-width: 560px;
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
    }

    .commander-profile-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 20px;
        align-items: start;
    }

    .commander-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        overflow: hidden;
    }

    .commander-card-body {
        padding: 26px;
    }

    .commander-account-head {
        display: flex;
        align-items: center;
        gap: 16px;
        padding-bottom: 22px;
        margin-bottom: 22px;
        border-bottom: 1px solid #e5e7eb;
    }

    .commander-avatar {
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        flex: 0 0 auto;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 20px;
        font-weight: 750;
    }

    .commander-account-title {
        margin: 0 0 6px;
        color: #111827;
        font-size: 18px;
        font-weight: 700;
    }

    .commander-account-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .commander-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 24px;
        padding: 0 9px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 650;
    }

    .commander-badge.is-role {
        border-color: #c7d2fe;
        background: #eef2ff;
        color: #3730a3;
    }

    .commander-badge.is-active {
        border-color: #bbf7d0;
        background: #ecfdf5;
        color: #047857;
    }

    .commander-info-list {
        display: grid;
        gap: 0;
    }

    .commander-info-row {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        padding: 14px 0;
        border-bottom: 1px solid #eef2f7;
    }

    .commander-info-row:last-child {
        border-bottom: 0;
    }

    .commander-info-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }

    .commander-info-value {
        color: #111827;
        font-size: 14px;
        font-weight: 650;
        text-align: right;
        word-break: break-word;
    }

    .commander-section-title {
        margin: 0 0 14px;
        color: #111827;
        font-size: 16px;
        font-weight: 700;
    }

    .commander-access-list {
        display: grid;
        gap: 11px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .commander-access-list li {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #334155;
        font-size: 14px;
        line-height: 1.45;
    }

    .commander-access-dot {
        width: 7px;
        height: 7px;
        flex: 0 0 auto;
        border-radius: 999px;
        background: #10b981;
    }

    .commander-actions {
        display: grid;
        gap: 10px;
        margin-top: 22px;
    }

    .commander-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0 14px;
        border-radius: 10px;
        border: 1px solid transparent;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease;
    }

    .commander-btn-primary {
        background: #111827;
        color: #ffffff;
    }

    .commander-btn-primary:hover {
        background: #1f2937;
        color: #ffffff;
    }

    .commander-btn-secondary {
        background: #ffffff;
        color: #111827;
        border-color: #e2e8f0;
    }

    .commander-btn-secondary:hover {
        background: #f9fafb;
        border-color: #cbd5e1;
        color: #111827;
    }

    .commander-session-note {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }

    @media (max-width: 991.98px) {
        .commander-profile-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .commander-profile-header,
        .commander-info-row {
            display: block;
        }

        .commander-info-value {
            display: block;
            margin-top: 5px;
            text-align: left;
        }
    }
</style>

<section class="commander-profile-page">
    <div class="commander-profile-wrap">
        <header class="commander-profile-header">
            <div>
                <h1 class="commander-profile-title">My Account</h1>
                <p class="commander-profile-subtitle">Kelola informasi akun Commander Anda.</p>
            </div>
            <?= Html::a('Back to Project List', (new \app\components\DomainContext())->projectListUrl(), ['class' => 'commander-btn commander-btn-secondary']) ?>
        </header>

        <div class="commander-profile-grid">
            <main class="commander-card">
                <div class="commander-card-body">
                    <div class="commander-account-head">
                        <div class="commander-avatar"><?= Html::encode($avatarInitial) ?></div>
                        <div>
                            <h2 class="commander-account-title">Commander Account</h2>
                            <div class="commander-account-meta">
                                <span class="commander-badge is-role"><?= Html::encode($roleLabel) ?></span>
                                <span class="commander-badge is-active"><?= Html::encode($status) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="commander-info-list">
                        <div class="commander-info-row">
                            <span class="commander-info-label">Username</span>
                            <span class="commander-info-value"><?= Html::encode($username) ?></span>
                        </div>
                        <div class="commander-info-row">
                            <span class="commander-info-label">Email</span>
                            <span class="commander-info-value"><?= Html::encode($email !== '' ? $email : '-') ?></span>
                        </div>
                        <div class="commander-info-row">
                            <span class="commander-info-label">Role</span>
                            <span class="commander-info-value"><?= Html::encode($roleLabel) ?></span>
                        </div>
                        <div class="commander-info-row">
                            <span class="commander-info-label">Status</span>
                            <span class="commander-info-value"><?= Html::encode($status) ?></span>
                        </div>
                        <div class="commander-info-row">
                            <span class="commander-info-label">Member Since</span>
                            <span class="commander-info-value"><?= Html::encode($memberSince) ?></span>
                        </div>
                        <div class="commander-info-row">
                            <span class="commander-info-label">Last Updated</span>
                            <span class="commander-info-value"><?= Html::encode($lastUpdated) ?></span>
                        </div>
                    </div>
                </div>
            </main>

            <aside class="commander-card">
                <div class="commander-card-body">
                    <h2 class="commander-section-title">Access Summary</h2>
                    <ul class="commander-access-list">
                        <li><span class="commander-access-dot"></span><span>Can manage all projects</span></li>
                        <li><span class="commander-access-dot"></span><span>Can access all workspaces</span></li>
                        <li><span class="commander-access-dot"></span><span>Can bypass workspace login</span></li>
                        <li><span class="commander-access-dot"></span><span>Can configure AppForge</span></li>
                    </ul>

                    <div class="commander-session-note">
                        <strong>Commander Session</strong><br>
                        Session: <?= Html::encode($sessionKey) ?><br>
                        Login: <?= Html::encode($loggedInAt !== '' ? $loggedInAt : '-') ?>
                    </div>

                    <div class="commander-actions">
                        <?= Html::a('Back to Project List', (new \app\components\DomainContext())->projectListUrl(), ['class' => 'commander-btn commander-btn-primary']) ?>
                        <?= $this->render('../layouts/_logout_button', [
                            'url' => ['/site/logout'],
                            'label' => 'Logout',
                            'icon' => '',
                            'buttonClass' => 'commander-btn commander-btn-secondary',
                            'buttonStyle' => 'width:100%;',
                            'formStyle' => 'margin:0;',
                        ]) ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
