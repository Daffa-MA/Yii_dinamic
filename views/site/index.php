<?php

/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'My Yii Application';

// Styles for dashboard layout
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap');
$this->registerJsFile('https://cdn.tailwindcss.com?plugins=forms,container-queries');
$this->registerJs("
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    'on-surface': '#0b1c30',
                    'on-surface-variant': '#464555',
                    'surface': '#e5e9f0',
                    'surface-container-lowest': '#ffffff',
                    'surface-container-low': '#eff4ff',
                    'surface-container': '#e5eeff',
                    'surface-container-high': '#dce9ff',
                    'primary-container': '#4f46e5',
                    'primary': '#3525cd',
                    'secondary': '#006c49',
                    'tertiary': '#7e3000',
                    'surface-tint': '#4d44e3',
                    'outline-variant': '#c7c4d8',
                    'outline': '#777587',
                    'error': '#ba1a1a',
                },
                fontFamily: {
                    'headline': ['Manrope'],
                    'body': ['Inter'],
                }
            }
        }
    }
", \yii\web\View::POS_HEAD);
?>

<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>

<body class="bg-surface font-body text-on-surface">

<!-- Top Navigation Bar -->
<nav class="fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-[#e5e9f0]/70 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]">
    <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-full gap-3 min-w-[320px]">
        <span class="material-symbols-outlined text-outline text-[20px]">search</span>
        <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline/60" placeholder="Search forms, analytics, or users..." type="text"/>
    </div>
    <div class="flex items-center gap-4">
        <button class="material-symbols-outlined text-on-surface-variant hover:bg-slate-100 p-2 rounded-full transition-colors">notifications</button>
        <div class="h-8 w-px bg-outline-variant/30"></div>
        <a href="<?= \yii\helpers\Url::to(['form/create']) ?>" class="bg-primary-container text-white px-6 py-2.5 rounded-full font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline">
            <span class="material-symbols-outlined text-[18px]">add</span> Create New Form
        </a>
        <div class="flex items-center gap-3 pl-4">
            <img alt="User profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDVAIMYF86keTHH4bWWsnc6u7upwBRSEk3FvJFjB4wyGvQxjTaWJjqAU9nx7f2YPKnoZKb-kP7pNyFHtjhAggRn0nsW21oGtSNUI1fZH-ddxHG35QnLC24RHVvAReNpdG-gdZoInGl6MMsS4OZcWCyLWu52BmaCzV6K8eTFMqMQMQ_HGc2dgYa6qxFdVSNOiJlHTjCPfpP1KqoRirRptx2ymuk_BKJrEm5kDTAWz-vU_e_4_Qeu3S25BXTLNpFN1WxftL-z6kTgLw"/>
        </div>
    </div>
</nav>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'home']) ?>

<!-- Main Content -->
<main class="pl-64 pt-24 min-h-screen">
    <div class="max-w-[1400px] mx-auto px-8 py-8">
        <!-- Hero Section -->
        <div class="text-center py-16 mb-12">
            <div class="w-20 h-20 bg-primary-container/10 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-4xl text-primary-container" style="font-variation-settings: 'FILL' 1;">rocket_launch</span>
            </div>
            <h1 class="text-4xl font-extrabold text-on-surface font-headline tracking-tight mb-4">Welcome to Your Application</h1>
            <p class="text-on-surface-variant text-lg max-w-2xl mx-auto">Build powerful forms, manage submissions, and create database tables with our intelligent form builder platform.</p>
        </div>

        <!-- Feature Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0_20px_40px_rgba(11,28,48,0.03)] border-t-2 border-primary-container/20 group hover:shadow-[0_24px_48px_rgba(11,28,48,0.08)] transition-all">
                <div class="w-14 h-14 bg-primary-container/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-2xl text-primary-container" style="font-variation-settings: 'FILL' 1;">description</span>
                </div>
                <h3 class="text-xl font-bold mb-3 font-headline">Form Builder</h3>
                <p class="text-on-surface-variant text-sm mb-6">Create beautiful forms with our drag-and-drop visual builder. Add fields, customize layouts, and publish in minutes.</p>
                <a href="<?= \yii\helpers\Url::to(['form/index']) ?>" class="text-primary-container font-semibold text-sm flex items-center gap-1 hover:underline no-underline">
                    Manage Forms <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0_20px_40px_rgba(11,28,48,0.03)] border-t-2 border-secondary/20 group hover:shadow-[0_24px_48px_rgba(11,28,48,0.08)] transition-all">
                <div class="w-14 h-14 bg-secondary/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-2xl text-secondary" style="font-variation-settings: 'FILL' 1;">inbox</span>
                </div>
                <h3 class="text-xl font-bold mb-3 font-headline">Submissions</h3>
                <p class="text-on-surface-variant text-sm mb-6">Track and manage all form responses in one place. Export data, filter results, and analyze submissions effortlessly.</p>
                <a href="<?= \yii\helpers\Url::to(['form/index']) ?>" class="text-secondary font-semibold text-sm flex items-center gap-1 hover:underline no-underline">
                    View Submissions <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0_20px_40px_rgba(11,28,48,0.03)] border-t-2 border-surface-tint/20 group hover:shadow-[0_24px_48px_rgba(11,28,48,0.08)] transition-all">
                <div class="w-14 h-14 bg-surface-tint/10 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-2xl text-surface-tint" style="font-variation-settings: 'FILL' 1;">table_chart</span>
                </div>
                <h3 class="text-xl font-bold mb-3 font-headline">Database Tables</h3>
                <p class="text-on-surface-variant text-sm mb-6">Design database schemas visually. Create tables, define columns, and execute SQL with a simple click.</p>
                <a href="<?= \yii\helpers\Url::to(['table-builder/index']) ?>" class="text-surface-tint font-semibold text-sm flex items-center gap-1 hover:underline no-underline">
                    Manage Tables <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0_20px_40px_rgba(11,28,48,0.03)] border-t border-outline-variant/10">
            <h2 class="text-xl font-bold font-headline mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?= Html::a('<span class="material-symbols-outlined text-[20px]">add</span> Create Form', ['form/create'], [
                    'class' => 'flex items-center justify-center gap-2 p-4 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                ]) ?>
                <?= Html::a('<span class="material-symbols-outlined text-[20px]">dashboard</span> Dashboard', ['site/dashboard'], [
                    'class' => 'flex items-center justify-center gap-2 p-4 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                ]) ?>
                <?= Html::a('<span class="material-symbols-outlined text-[20px]">person</span> Profile', ['site/profile'], [
                    'class' => 'flex items-center justify-center gap-2 p-4 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                ]) ?>
                <?= Html::a('<span class="material-symbols-outlined text-[20px]">table_chart</span> Tables', ['table-builder/index'], [
                    'class' => 'flex items-center justify-center gap-2 p-4 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                ]) ?>
            </div>
        </div>
    </div>
</main>
</body>
