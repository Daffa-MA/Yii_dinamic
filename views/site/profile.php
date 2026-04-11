<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var int $totalForms */
/** @var int $totalSubmissions */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'My Profile';

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
        <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create New Form', ['form/create'], [
            'class' => 'bg-primary-container text-white px-6 py-2.5 rounded-full font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
        ]) ?>
        <div class="flex items-center gap-3 pl-4">
            <img alt="User profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDVAIMYF86keTHH4bWWsnc6u7upwBRSEk3FvJFjB4wyGvQxjTaWJjqAU9nx7f2YPKnoZKb-kP7pNyFHtjhAggRn0nsW21oGtSNUI1fZH-ddxHG35QnLC24RHVvAReNpdG-gdZoInGl6MMsS4OZcWCyLWu52BmaCzV6K8eTFMqMQMQ_HGc2dgYa6qxFdVSNOiJlHTjCPfpP1KqoRirRptx2ymuk_BKJrEm5kDTAWz-vU_e_4_Qeu3S25BXTLNpFN1WxftL-z6kTgLw"/>
        </div>
    </div>
</nav>

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'profile']) ?>

<!-- Main Content -->
<main class="pl-64 pt-24 min-h-screen">
    <div class="max-w-[1400px] mx-auto px-8 py-8">
        <!-- Header -->
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight mb-2">My Profile</h1>
                <p class="text-on-surface-variant font-medium">Manage your account settings and preferences.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Profile Information -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">person</span>
                        <h2 class="text-xl font-bold font-headline">Profile Information</h2>
                    </div>
                </div>
                <div class="p-8">
                    <table class="w-full">
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Username</td>
                            <td class="py-4 text-sm font-semibold"><?= Html::encode($user->username) ?></td>
                        </tr>
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant">User ID</td>
                            <td class="py-4"><code class="bg-surface-container px-2 py-1 rounded text-sm"><?= $user->id ?></code></td>
                        </tr>
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Member Since</td>
                            <td class="py-4 text-sm font-semibold"><?= date('F d, Y', strtotime($user->created_at)) ?></td>
                        </tr>
                        <tr>
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Last Updated</td>
                            <td class="py-4 text-sm font-semibold"><?= date('F d, Y H:i', strtotime($user->updated_at)) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">analytics</span>
                        <h2 class="text-xl font-bold font-headline">Statistics</h2>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="text-center p-6 bg-surface-container rounded-xl">
                            <div class="text-4xl font-extrabold text-primary-container font-headline"><?= $totalForms ?></div>
                            <div class="text-sm text-on-surface-variant font-medium mt-2">Total Forms</div>
                        </div>
                        <div class="text-center p-6 bg-surface-container rounded-xl">
                            <div class="text-4xl font-extrabold text-secondary font-headline"><?= $totalSubmissions ?></div>
                            <div class="text-sm text-on-surface-variant font-medium mt-2">Total Submissions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Change Password -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-tertiary" style="font-variation-settings: 'FILL' 1;">lock</span>
                        <h2 class="text-xl font-bold font-headline">Change Password</h2>
                    </div>
                </div>
                <div class="p-8">
                    <?php $form = ActiveForm::begin([
                        'action' => ['site/change-password'],
                        'method' => 'post',
                    ]); ?>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-on-surface-variant mb-2">Current Password</label>
                            <input type="password" name="current_password" class="w-full px-4 py-2.5 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-on-surface-variant mb-2">New Password</label>
                            <input type="password" name="new_password" class="w-full px-4 py-2.5 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" required minlength="6">
                            <p class="text-xs text-on-surface-variant mt-1">Minimum 6 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-on-surface-variant mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full px-4 py-2.5 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="mt-6 bg-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm">
                        <span class="material-symbols-outlined text-[18px]">save</span> Change Password
                    </button>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-surface-tint" style="font-variation-settings: 'FILL' 1;">link</span>
                        <h2 class="text-xl font-bold font-headline">Quick Links</h2>
                    </div>
                </div>
                <div class="p-8">
                    <div class="space-y-2">
                        <?= Html::a('<span class="material-symbols-outlined text-[20px]">dashboard</span> Dashboard', ['site/dashboard'], [
                            'class' => 'flex items-center gap-3 px-4 py-3 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                        ]) ?>
                        <?= Html::a('<span class="material-symbols-outlined text-[20px]">description</span> My Forms', ['form/index'], [
                            'class' => 'flex items-center gap-3 px-4 py-3 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                        ]) ?>
                        <?= Html::a('<span class="material-symbols-outlined text-[20px]">table_chart</span> Tables', ['table-builder/index'], [
                            'class' => 'flex items-center gap-3 px-4 py-3 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                        ]) ?>
                        <?= Html::a('<span class="material-symbols-outlined text-[20px]">add</span> Create New Form', ['form/create'], [
                            'class' => 'flex items-center gap-3 px-4 py-3 bg-surface-container rounded-xl text-on-surface font-medium hover:bg-surface-container-high transition-all no-underline'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
