<?php
/**
 * @var string $activeMenu - Which menu item is active: 'home', 'dashboard', 'forms', 'tables', 'profile'
 */
$activeMenu = $activeMenu ?? '';
?>

<!-- Sidebar Navigation -->
<aside class="fixed left-0 top-0 pt-24 h-screen w-64 border-r border-slate-200/15 bg-white flex flex-col py-6 px-4 z-40">
    <div class="flex items-center gap-3 px-4 mb-8">
        <div class="w-8 h-8 bg-gradient-to-br from-primary-container to-primary rounded-lg flex items-center justify-center text-white shadow-md">
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
        </div>
        <div>
            <h2 class="text-sm font-bold text-[#0b1c30] font-headline">Editorial</h2>
            <p class="text-[10px] uppercase tracking-wider text-outline">Intelligent Atmosphere</p>
        </div>
    </div>
    <nav class="flex-1 space-y-1">
        <a class="flex items-center gap-3 <?= $activeMenu === 'home' ? 'bg-gradient-to-r from-primary-container/10 to-primary/10 text-primary-container' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['site/index']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'home' ? 'text-primary-container' : '' ?>" <?= $activeMenu === 'home' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>home</span>
            Home
        </a>
        <a class="flex items-center gap-3 <?= $activeMenu === 'dashboard' ? 'bg-gradient-to-r from-primary-container/10 to-primary/10 text-primary-container' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['site/dashboard']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'dashboard' ? 'text-primary-container' : '' ?>" <?= $activeMenu === 'dashboard' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>dashboard</span>
            Dashboard
        </a>
        <a class="flex items-center gap-3 <?= $activeMenu === 'forms' ? 'bg-gradient-to-r from-primary-container/10 to-primary/10 text-primary-container' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['form/index']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'forms' ? 'text-primary-container' : '' ?>" <?= $activeMenu === 'forms' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>description</span>
            Forms
        </a>
        <a class="flex items-center gap-3 <?= $activeMenu === 'tables' ? 'bg-gradient-to-r from-surface-tint/10 to-primary-container/10 text-surface-tint' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'tables' ? 'text-surface-tint' : '' ?>" <?= $activeMenu === 'tables' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>table_chart</span>
            Tables
        </a>
        <a class="flex items-center gap-3 <?= $activeMenu === 'profile' ? 'bg-gradient-to-r from-secondary/10 to-secondary/20 text-secondary' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['site/profile']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'profile' ? 'text-secondary' : '' ?>" <?= $activeMenu === 'profile' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>person</span>
            Profile
        </a>
    </nav>
    <div class="mt-auto space-y-1 pt-6 border-t border-slate-100">
        <?= yii\bootstrap5\Html::a('<span class="material-symbols-outlined">logout</span> Sign Out', ['site/logout'], [
            'class' => 'flex items-center gap-3 text-slate-600 px-4 py-3 hover:bg-error/10 hover:text-error rounded-xl transition-all',
            'data' => [
                'method' => 'post',
            ],
            'style' => 'cursor: pointer;'
        ]) ?>
    </div>
</aside>
