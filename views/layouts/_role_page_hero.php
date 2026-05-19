<?php

use yii\helpers\Html;

/** @var array $hero */

if (empty($hero['should_render'])) {
    return;
}

$eyebrow = (string)($hero['eyebrow'] ?? 'Sekolah Negeri');
$username = (string)($hero['username'] ?? 'User');
$role = (string)($hero['role'] ?? 'user');
$pageTitle = (string)($hero['page_title'] ?? 'Halaman');
$workspaceName = (string)($hero['workspace_name'] ?? 'Workspace');
$status = (string)($hero['status'] ?? 'Active');
$description = (string)($hero['description'] ?? '');
$info = (string)($hero['info'] ?? '');
?>

<section class="role-page-hero">
    <div class="role-page-hero__glow role-page-hero__glow--left"></div>
    <div class="role-page-hero__glow role-page-hero__glow--right"></div>

    <div class="role-page-hero__inner">
        <div class="role-page-hero__header">
            <div class="role-page-hero__eyebrow">
                <span class="role-page-hero__icon">👋</span>
                <span><?= Html::encode($eyebrow) ?></span>
            </div>
            <div class="role-page-hero__status">
                <span class="role-page-hero__status-dot"></span>
                <span><?= Html::encode($status) ?></span>
            </div>
        </div>

        <div class="role-page-hero__content">
            <div class="role-page-hero__copy">
                <h1 class="role-page-hero__title">Halo, <?= Html::encode($username) ?></h1>
                <p class="role-page-hero__lead">
                    Selamat datang di halaman <?= Html::encode($pageTitle) ?>. Silakan gunakan halaman ini sesuai kebutuhan Anda.
                </p>
                <p class="role-page-hero__support">
                    Akses informasi dan fitur yang tersedia untuk role Anda.
                </p>
            </div>

            <div class="role-page-hero__cards">
                <div class="role-page-hero__card">
                    <span class="role-page-hero__card-label">Role</span>
                    <span class="role-page-hero__card-value"><?= Html::encode($role) ?></span>
                </div>
                <div class="role-page-hero__card">
                    <span class="role-page-hero__card-label">Workspace</span>
                    <span class="role-page-hero__card-value"><?= Html::encode($workspaceName) ?></span>
                </div>
                <div class="role-page-hero__card">
                    <span class="role-page-hero__card-label">Status</span>
                    <span class="role-page-hero__card-value">Active</span>
                </div>
            </div>
        </div>

        <?php if ($description !== '' || $info !== ''): ?>
            <div class="role-page-hero__footer">
                <?php if ($description !== ''): ?>
                    <span><?= Html::encode($description) ?></span>
                <?php endif; ?>
                <?php if ($info !== ''): ?>
                    <span class="role-page-hero__footer-badge">Info</span>
                    <span><?= Html::encode($info) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .role-page-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 24px;
        background:
            radial-gradient(circle at top left, rgba(14, 165, 233, 0.18), transparent 32%),
            radial-gradient(circle at top right, rgba(16, 185, 129, 0.14), transparent 30%),
            linear-gradient(135deg, #f8fbff 0%, #eef4ff 100%);
        border: 1px solid rgba(148, 163, 184, 0.22);
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
    }

    .role-page-hero__glow {
        position: absolute;
        border-radius: 999px;
        filter: blur(12px);
        opacity: 0.65;
        pointer-events: none;
    }

    .role-page-hero__glow--left {
        width: 140px;
        height: 140px;
        left: -32px;
        top: -20px;
        background: rgba(59, 130, 246, 0.12);
    }

    .role-page-hero__glow--right {
        width: 180px;
        height: 180px;
        right: -50px;
        bottom: -48px;
        background: rgba(16, 185, 129, 0.12);
    }

    .role-page-hero__inner {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 18px;
    }

    .role-page-hero__header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .role-page-hero__eyebrow,
    .role-page-hero__status {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(255, 255, 255, 0.72);
        color: #0f172a;
        backdrop-filter: blur(8px);
    }

    .role-page-hero__icon {
        font-size: 18px;
    }

    .role-page-hero__status-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
    }

    .role-page-hero__content {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(260px, .9fr);
        gap: 18px;
        align-items: stretch;
    }

    .role-page-hero__copy {
        padding: 6px 2px;
    }

    .role-page-hero__title {
        margin: 0;
        font-size: clamp(26px, 4vw, 38px);
        line-height: 1.1;
        font-weight: 800;
        color: #0f172a;
    }

    .role-page-hero__lead {
        margin: 14px 0 0;
        max-width: 64ch;
        font-size: 16px;
        line-height: 1.7;
        color: #334155;
    }

    .role-page-hero__support {
        margin: 12px 0 0;
        font-size: 14px;
        color: #475569;
        font-weight: 600;
    }

    .role-page-hero__cards {
        display: grid;
        gap: 12px;
    }

    .role-page-hero__card {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 16px 18px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .role-page-hero__card-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
    }

    .role-page-hero__card-value {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }

    .role-page-hero__footer {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 12px 14px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(148, 163, 184, 0.18);
        color: #334155;
        font-size: 13px;
    }

    .role-page-hero__footer-badge {
        padding: 6px 10px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    @media (max-width: 992px) {
        .role-page-hero {
            padding: 20px;
        }

        .role-page-hero__content {
            grid-template-columns: 1fr;
        }
    }
</style>
