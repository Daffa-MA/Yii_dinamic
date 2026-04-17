<?php

/**
 * @var string $activeMenu - Which menu item is active: 'home', 'dashboard', 'forms', 'tables', 'profile'
 */
$activeMenu = $activeMenu ?? '';
$activeDatabase = Yii::$app->session->get('active_dashboard_database');
?>

<style>
    :root {
        --app-sidebar-width: 16rem;
        --app-sidebar-collapsed-width: 5.5rem;
        --app-shell-gap: 1.5rem;
        --sidebar-transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                              padding 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-sidebar {
        transition: var(--sidebar-transition);
        overflow: hidden;
    }

    body.sidebar-collapsed .app-sidebar {
        width: var(--app-sidebar-collapsed-width);
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    /* Smooth text hide/show animation */
    .app-sidebar-brand-text,
    .app-sidebar-link-text,
    .app-sidebar-logout-text {
        opacity: 1;
        transform: translateX(0);
        transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                    transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                    max-width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 200px;
        overflow: hidden;
        white-space: nowrap;
    }

    body.sidebar-collapsed .app-sidebar-brand-text,
    body.sidebar-collapsed .app-sidebar-link-text,
    body.sidebar-collapsed .app-sidebar-logout-text {
        opacity: 0;
        transform: translateX(-15px);
        max-width: 0;
        pointer-events: none;
    }

    body.sidebar-collapsed .app-sidebar-brand {
        justify-content: center;
    }

    body.sidebar-collapsed .app-sidebar-link,
    body.sidebar-collapsed .app-sidebar-logout {
        justify-content: center;
    }

    body.sidebar-collapsed .app-shell-nav {
        left: var(--app-sidebar-collapsed-width) !important;
        transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.sidebar-collapsed .app-shell-main {
        padding-left: var(--app-sidebar-collapsed-width) !important;
        transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.sidebar-collapsed main#main > .container > .alert {
        margin-left: var(--app-sidebar-collapsed-width) !important;
        width: calc(100% - var(--app-sidebar-collapsed-width)) !important;
    }

    /* Logo toggle with smooth hover effect */
    .app-sidebar-brand {
        cursor: pointer;
        transition: opacity 0.2s ease;
        position: relative;
        user-select: none;
    }

    .app-sidebar-brand:hover {
        opacity: 0.75;
    }

    .app-sidebar-brand:active {
        transform: scale(0.98);
    }

    /* Navigation links with smooth transitions */
    .app-sidebar-link,
    .app-sidebar-logout {
        position: relative;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .app-sidebar-link:hover,
    .app-sidebar-logout:hover {
        transform: translateX(4px);
        background-color: rgba(0, 0, 0, 0.04);
    }

    body.sidebar-collapsed .app-sidebar-link:hover,
    body.sidebar-collapsed .app-sidebar-logout:hover {
        transform: none;
    }

    /* Icon centering in collapsed mode */
    body.sidebar-collapsed .app-sidebar-link .material-symbols-outlined,
    body.sidebar-collapsed .app-sidebar-logout .material-symbols-outlined {
        margin: 0 auto;
    }

    @media (max-width: 992px) {
        .app-sidebar {
            transform: translateX(0);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.sidebar-collapsed .app-sidebar {
            transform: translateX(calc(-1 * var(--app-sidebar-width)));
            width: var(--app-sidebar-width);
            padding-left: 1rem;
            padding-right: 1rem;
        }

        body.sidebar-collapsed .app-sidebar-brand-text,
        body.sidebar-collapsed .app-sidebar-link-text,
        body.sidebar-collapsed .app-sidebar-logout-text {
            opacity: 1;
            transform: translateX(0);
            max-width: 200px;
            pointer-events: auto;
        }

        body.sidebar-collapsed .app-sidebar-brand,
        body.sidebar-collapsed .app-sidebar-link,
        body.sidebar-collapsed .app-sidebar-logout {
            justify-content: flex-start;
        }

        .app-shell-nav {
            left: 0 !important;
        }

        .app-shell-main {
            padding-left: 0 !important;
        }

        main#main > .container > .alert,
        body.sidebar-collapsed main#main > .container > .alert {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
</style>

<!-- Sidebar Navigation -->
<aside class="app-sidebar fixed left-0 top-0 pt-6 h-screen w-64 border-r border-slate-200/15 bg-white flex flex-col py-6 px-4 z-40">
    <div class="flex items-center gap-3 px-4 mb-8 app-sidebar-brand" id="sidebar-logo-toggle" title="Click to toggle sidebar">
        <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-white shadow-md border border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0 transition-all duration-300">
            <img src="<?= Yii::getAlias('@web/logo.png') ?>" alt="TableForge Logo" class="w-8 h-8 object-contain">
        </div>
        <div class="app-sidebar-brand-text">
            <h2 class="text-sm font-bold text-[#0b1c30] font-headline">TableForge</h2>
            <p class="text-[10px] uppercase tracking-wider text-outline">Intelligent Atmosphere</p>
        </div>
        </div>
    </div>
    <?php if (!empty($activeDatabase)): ?>
        <div class="px-4 mb-4">
            <div class="app-sidebar-link-text inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-secondary/10 text-secondary border border-secondary/20">
                <span class="material-symbols-outlined text-[14px]">database</span>
                <?= yii\bootstrap5\Html::encode($activeDatabase) ?>
            </div>
        </div>
    <?php endif; ?>
    <nav class="flex-1 space-y-1">
        <a class="app-sidebar-link flex items-center gap-3 <?= $activeMenu === 'dashboard' ? 'bg-gradient-to-r from-primary-container/10 to-primary/10 text-primary-container' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['site/dashboard']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'dashboard' ? 'text-primary-container' : '' ?>" <?= $activeMenu === 'dashboard' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>dashboard</span>
            <span class="app-sidebar-link-text">Dashboard</span>
        </a>
        <a class="app-sidebar-link flex items-center gap-3 <?= $activeMenu === 'forms' ? 'bg-gradient-to-r from-primary-container/10 to-primary/10 text-primary-container' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['form/index']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'forms' ? 'text-primary-container' : '' ?>" <?= $activeMenu === 'forms' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>description</span>
            <span class="app-sidebar-link-text">Forms</span>
        </a>
        <a class="app-sidebar-link flex items-center gap-3 <?= $activeMenu === 'published-forms' ? 'bg-gradient-to-r from-secondary/10 to-secondary/20 text-secondary' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['published-form/index']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'published-forms' ? 'text-secondary' : '' ?>" <?= $activeMenu === 'published-forms' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>public</span>
            <span class="app-sidebar-link-text">Data Form</span>
        </a>
        <a class="app-sidebar-link flex items-center gap-3 <?= $activeMenu === 'tables' ? 'bg-gradient-to-r from-surface-tint/10 to-primary-container/10 text-surface-tint' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'tables' ? 'text-surface-tint' : '' ?>" <?= $activeMenu === 'tables' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>table_chart</span>
            <span class="app-sidebar-link-text">Tables</span>
        </a>
        <a class="app-sidebar-link flex items-center gap-3 <?= $activeMenu === 'profile' ? 'bg-gradient-to-r from-secondary/10 to-secondary/20 text-secondary' : 'text-slate-600 hover:bg-slate-100' ?> rounded-xl px-4 py-3 font-medium transition-all group" href="<?= \yii\helpers\Url::to(['site/profile']) ?>">
            <span class="material-symbols-outlined <?= $activeMenu === 'profile' ? 'text-secondary' : '' ?>" <?= $activeMenu === 'profile' ? 'style="font-variation-settings: \'FILL\' 1;"' : '' ?>>person</span>
            <span class="app-sidebar-link-text">Profile</span>
        </a>
    </nav>
    <div class="mt-auto space-y-1 pt-6 border-t border-slate-100">
        <?= yii\bootstrap5\Html::a('<span class="material-symbols-outlined">logout</span> <span class="app-sidebar-logout-text">Sign Out</span>', ['site/logout'], [
            'class' => 'app-sidebar-logout flex items-center gap-3 text-slate-600 px-4 py-3 hover:bg-error/10 hover:text-error rounded-xl transition-all',
            'data' => [
                'method' => 'post',
            ],
            'style' => 'cursor: pointer;',
            'encode' => false
        ]) ?>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const storageKey = 'app-sidebar-collapsed';
    const logoToggle = document.getElementById('sidebar-logo-toggle');

    function applySidebarState(isCollapsed) {
        document.body.classList.toggle('sidebar-collapsed', isCollapsed);
    }

    const savedState = localStorage.getItem(storageKey) === '1';
    applySidebarState(savedState);

    if (logoToggle) {
        logoToggle.addEventListener('click', function () {
            const nextState = !document.body.classList.contains('sidebar-collapsed');
            applySidebarState(nextState);
            localStorage.setItem(storageKey, nextState ? '1' : '0');
        });
    }
});
</script>
