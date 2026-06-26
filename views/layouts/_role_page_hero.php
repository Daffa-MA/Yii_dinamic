<?php

use yii\helpers\Html;

/** @var array $hero */

if (empty($hero['should_render'])) {
    return;
}

$variant = (string)($hero['variant'] ?? 'user-page');
$icon = (string)($hero['icon'] ?? 'description');
$title = (string)($hero['title'] ?? 'Halaman');
$subtitle = (string)($hero['subtitle'] ?? '');
$description = (string)($hero['description'] ?? '');
$workspaceName = (string)($hero['workspace_name'] ?? 'Workspace');
$username = (string)($hero['username'] ?? 'User');
$role = (string)($hero['role'] ?? 'user');
$status = (string)($hero['status'] ?? 'Active');
$layout = (string)($hero['layout'] ?? 'builder');
$formCount = (int)($hero['form_count'] ?? 0);
$info = (string)($hero['info'] ?? '');
?>

<?php if ($variant === 'admin-page' || $variant === 'admin-dashboard'): ?>
    <div class="mb-6 overflow-hidden rounded-[24px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-5 py-5 shadow-[0_16px_36px_rgba(15,23,42,0.07)] md:px-6 md:py-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 w-full lg:max-w-3xl">
                <div class="flex items-start gap-4">
                    <div class="mt-0.5 flex h-11 w-11 flex-none items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-indigo-700">
                        <span class="material-symbols-outlined text-[22px]"><?= Html::encode($icon) ?></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                <?= Html::encode($title) ?>
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                <span class="material-symbols-outlined text-[16px]">workspaces</span>
                                <?= Html::encode($workspaceName) ?>
                            </span>
                        </div>
                        <h1 class="mt-3 text-[22px] font-bold tracking-tight text-slate-900 md:text-[26px] break-words max-w-full"><?= Html::encode($title === '' ? $subtitle : $title) ?></h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            <?= Html::encode($subtitle !== '' ? $subtitle : ($description !== '' ? $description : 'Halaman dinamis yang dibangun menggunakan page builder.')) ?>
                        </p>
                        <?php if ($description !== '' && $description !== $subtitle): ?>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                <?= Html::encode($description) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[360px]">
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Layout</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900"><?= Html::encode($layout !== '' ? $layout : 'builder') ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Form</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900"><?= (int)$formCount ?> item</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status</p>
                    <p class="mt-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= strtolower($status) === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                            <?= Html::encode($status) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="mb-6 overflow-hidden rounded-[24px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-5 py-5 shadow-[0_16px_36px_rgba(15,23,42,0.07)] md:px-6 md:py-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 max-w-3xl">
                <div class="flex items-start gap-4">
                    <div class="mt-0.5 flex h-11 w-11 flex-none items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700">
                        <span class="material-symbols-outlined text-[22px]"><?= Html::encode($icon) ?></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                <?= Html::encode($title) ?>
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                <span class="material-symbols-outlined text-[16px]">workspaces</span>
                                <?= Html::encode($workspaceName) ?>
                            </span>
                        </div>
                        <h1 class="mt-3 text-[22px] font-bold tracking-tight text-slate-900 md:text-[26px]">Halo, <?= Html::encode($username) ?></h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            <?= Html::encode($description !== '' ? $description : 'Selamat datang di halaman ' . $title . '. Silakan gunakan halaman ini sesuai kebutuhan Anda.') ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[360px]">
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
<?php endif; ?>