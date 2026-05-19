<?php

use yii\helpers\Html;

/** @var array $hero */

if (empty($hero['should_render'])) {
    return;
}

$pageTitle = (string)($hero['page_title'] ?? 'Halaman');
$username = (string)($hero['username'] ?? 'User');
$role = (string)($hero['role'] ?? 'user');
$workspaceName = (string)($hero['workspace_name'] ?? 'Workspace');
$status = (string)($hero['status'] ?? 'Active');
$welcomeLine = (string)($hero['description'] ?? '');
?>

<div class="mb-6 overflow-hidden rounded-[28px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)] md:p-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                    <span class="material-symbols-outlined text-[16px]">description</span>
                    <?= Html::encode($pageTitle) ?>
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="material-symbols-outlined text-[16px]">workspaces</span>
                    <?= Html::encode($workspaceName) ?>
                </span>
            </div>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 md:text-[28px]">Halo, <?= Html::encode($username) ?></h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                <?= Html::encode($welcomeLine !== '' ? $welcomeLine : 'Selamat datang di halaman ' . $pageTitle . '. Silakan gunakan halaman ini sesuai kebutuhan Anda.') ?>
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[360px]">
            <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Role</p>
                <p class="mt-2 text-sm font-semibold text-slate-900"><?= Html::encode($role) ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Workspace</p>
                <p class="mt-2 truncate text-sm font-semibold text-slate-900"><?= Html::encode($workspaceName) ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status</p>
                <p class="mt-2">
                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <?= Html::encode($status) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>
