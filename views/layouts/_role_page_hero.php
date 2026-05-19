<?php

use yii\helpers\Html;

/** @var array $hero */

if (empty($hero['should_render'])) {
    return;
}

$username = (string)($hero['username'] ?? 'User');
$role = (string)($hero['role'] ?? 'user');
$pageTitle = (string)($hero['page_title'] ?? 'Halaman');
$workspaceName = (string)($hero['workspace_name'] ?? 'Workspace');
$status = (string)($hero['status'] ?? 'Active');
$description = (string)($hero['description'] ?? '');
?>

<section class="role-page-hero">
    <div class="role-page-hero__icon">
        <span class="material-symbols-outlined role-page-hero__icon-mark">waving_hand</span>
    </div>
    <div class="role-page-hero__body">
        <div class="role-page-hero__title-row">
            <h1 class="role-page-hero__title">Halo, <?= Html::encode($username) ?></h1>
            <span class="role-page-hero__status"><?= Html::encode($status) ?></span>
        </div>
        <p class="role-page-hero__text">
            Selamat datang di halaman <?= Html::encode($pageTitle) ?>. Silakan gunakan halaman ini sesuai kebutuhan Anda.
        </p>
        <div class="role-page-hero__meta">
            <span><strong>Role:</strong> <?= Html::encode($role) ?></span>
            <span><strong>Workspace:</strong> <?= Html::encode($workspaceName) ?></span>
        </div>
        <?php if ($description !== ''): ?>
            <div class="role-page-hero__note"><?= Html::encode($description) ?></div>
        <?php endif; ?>
    </div>
</section>

<style>
    .role-page-hero {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 16px 18px;
        margin: 0 0 18px;
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .role-page-hero__icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 18px;
        line-height: 1;
    }

    .role-page-hero__icon-mark {
        font-size: 20px;
        color: #334155;
    }

    .role-page-hero__body {
        min-width: 0;
        flex: 1;
    }

    .role-page-hero__title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .role-page-hero__title {
        margin: 0;
        font-size: 20px;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
    }

    .role-page-hero__status {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        color: #047857;
        font-size: 12px;
        font-weight: 700;
    }

    .role-page-hero__text {
        margin: 8px 0 0;
        color: #334155;
        font-size: 14px;
        line-height: 1.6;
    }

    .role-page-hero__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 18px;
        margin-top: 10px;
        color: #475569;
        font-size: 13px;
    }

    .role-page-hero__meta strong {
        color: #0f172a;
    }

    .role-page-hero__note {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e2e8f0;
        color: #475569;
        font-size: 13px;
        line-height: 1.5;
    }

    @media (max-width: 640px) {
        .role-page-hero {
            padding: 14px;
        }

        .role-page-hero__title {
            font-size: 18px;
        }
    }
</style>
