<?php

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */
/** @var app\models\FormSubmission[] $recentSubmissions */
/** @var array $formSubmissionCounts */
/** @var array $databaseContext */

use yii\bootstrap5\Html;

$this->title = 'Dashboard';
$activeDatabase = $databaseContext['activeDatabase'] ?? 'default';
$isDatabaseSwitched = (bool)($databaseContext['isSwitched'] ?? false);
?>

<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect" />
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap" rel="stylesheet" />
<!-- Icons -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
<!-- Tailwind CSS v3 -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'on-secondary-fixed': '#002113',
                    'outline-variant': '#c7c4d8',
                    'secondary-fixed-dim': '#4edea3',
                    'surface-variant': '#d3e4fe',
                    'on-tertiary': '#ffffff',
                    'error-container': '#ffdad6',
                    'on-tertiary-container': '#ffd2be',
                    'surface-dim': '#cbdbf5',
                    'on-error': '#ffffff',
                    'secondary-container': '#6cf8bb',
                    'on-primary-container': '#dad7ff',
                    'primary-fixed-dim': '#c3c0ff',
                    'on-tertiary-fixed': '#351000',
                    'primary': '#3525cd',
                    'background': '#e5e9f0',
                    'inverse-surface': '#213145',
                    'surface-container-low': '#eff4ff',
                    'surface-container-high': '#dce9ff',
                    'tertiary-fixed': '#ffdbcc',
                    'on-secondary-container': '#00714d',
                    'surface-container-highest': '#d3e4fe',
                    'tertiary-fixed-dim': '#ffb695',
                    'on-primary-fixed': '#0f0069',
                    'on-surface-variant': '#464555',
                    'on-secondary': '#ffffff',
                    'surface-tint': '#4d44e3',
                    'on-primary': '#ffffff',
                    'on-tertiary-fixed-variant': '#7b2f00',
                    'surface': '#e5e9f0',
                    'primary-container': '#4f46e5',
                    'surface-container-lowest': '#ffffff',
                    'error': '#ba1a1a',
                    'inverse-on-surface': '#eaf1ff',
                    'secondary-fixed': '#6ffbbe',
                    'surface-container': '#e5eeff',
                    'primary-fixed': '#e2dfff',
                    'on-primary-fixed-variant': '#3323cc',
                    'surface-bright': '#e5e9f0',
                    'secondary': '#006c49',
                    'tertiary-container': '#a44100',
                    'on-error-container': '#93000a',
                    'on-surface': '#0b1c30',
                    'on-background': '#0b1c30',
                    'outline': '#777587',
                    'inverse-primary': '#c3c0ff',
                    'on-secondary-fixed-variant': '#005236',
                    'tertiary': '#7e3000'
                },
                borderRadius: {
                    'DEFAULT': '0.25rem',
                    'lg': '0.5rem',
                    'xl': '1.5rem',
                    'full': '9999px'
                },
                fontFamily: {
                    'headline': ['Manrope'],
                    'body': ['Inter'],
                    'label': ['Inter']
                }
            }
        }
    }
</script>

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .tonal-transition {
        transition: background-color 0.3s ease;
    }
</style>

<body class="bg-surface font-body text-on-surface">
    <!-- Top Navigation Bar -->
    <nav class="app-shell-nav fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-[#e5e9f0]/70 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)] tonal-transition">
        <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-full gap-3 min-w-[320px]">
            <span class="material-symbols-outlined text-outline text-[20px]">search</span>
            <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline/60" placeholder="Search forms, analytics, or users..." type="text" />
        </div>
        <div class="flex items-center gap-4">
            <button class="notification-button material-symbols-outlined text-on-surface-variant hover:bg-slate-100 p-2 rounded-full transition-colors">notifications</button>
            <div class="h-8 w-px bg-outline-variant/30"></div>
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create New Form', ['form/create'], [
                'class' => 'bg-primary-container text-white px-6 py-2.5 rounded-full font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
            ]) ?>
            <div class="flex items-center gap-3 pl-4">
                <img alt="User profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDVAIMYF86keTHH4bWWsnc6u7upwBRSEk3FvJFjB4wyGvQxjTaWJjqAU9nx7f2YPKnoZKb-kP7pNyFHtjhAggRn0nsW21oGtSNUI1fZH-ddxHG35QnLC24RHVvAReNpdG-gdZoInGl6MMsS4OZcWCyLWu52BmaCzV6K8eTFMqMQMQ_HGc2dgYa6qxFdVSNOiJlHTjCPfpP1KqoRirRptx2ymuk_BKJrEm5kDTAWz-vU_e_4_Qeu3S25BXTLNpFN1WxftL-z6kTgLw" />
            </div>
        </div>
    </nav>

    <?= $this->render('../layouts/_sidebar', ['activeMenu' => 'dashboard']) ?>

    <!-- Main Content Canvas -->
    <main class="app-shell-main pl-64 pt-6 min-h-screen">
        <div class="max-w-[1400px] mx-auto px-8 py-8">
            <!-- Header Section -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-primary-container/10 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">dashboard</span>
                        </div>
                        <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight">Workspace Overview</h1>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold <?= $isDatabaseSwitched ? 'bg-secondary/10 text-secondary border border-secondary/20' : 'bg-surface-container-high text-on-surface-variant border border-outline-variant/30' ?>">
                            <span class="material-symbols-outlined text-[15px]">database</span>
                            <?= Html::encode($activeDatabase) ?>
                        </span>
                    </div>
                    <p class="text-on-surface-variant font-medium">
                        Dashboard sedang membaca data dari database aktif:
                        <span class="font-bold text-on-surface"><?= Html::encode($activeDatabase) ?></span>.
                    </p>
                </div>
                <form method="get" action="<?= \yii\helpers\Url::to(['site/dashboard']) ?>" class="flex items-center gap-2">
                    <input type="text" name="database" value="<?= Html::encode($activeDatabase) ?>" placeholder="nama_database"
                        class="px-3 py-2 bg-surface-container-high border border-outline-variant rounded-xl text-sm focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all w-[180px]" />
                    <button type="submit" class="px-4 py-2 bg-primary-container text-white rounded-xl font-semibold text-sm hover:shadow-lg transition-all">
                        Apply
                    </button>
                </form>
            </div>

            <!-- Bento Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <!-- Card 1: Indigo -->
                <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] relative overflow-hidden group border border-outline-variant/30 hover:border-primary-container/40 transition-all">
                    <div class="h-1 bg-gradient-to-r from-primary-container to-primary absolute top-0 left-0 right-0"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10 mt-2">
                        <div class="p-3 bg-gradient-to-br from-primary-container/10 to-primary/10 rounded-xl border border-primary-container/20">
                            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">description</span>
                        </div>
                        <span class="text-xs font-bold text-primary-container px-2 py-1 bg-primary-container/10 rounded-full">ACTIVE</span>
                    </div>
                    <p class="text-sm font-medium text-on-surface-variant mb-1 relative z-10">Total Forms</p>
                    <h3 class="text-3xl font-extrabold text-primary-container font-headline relative z-10"><?= $totalForms ?></h3>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-700">
                        <span class="material-symbols-outlined text-9xl">description</span>
                    </div>
                </div>
                <!-- Card 2: Emerald -->
                <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] relative overflow-hidden group border border-outline-variant/30 hover:border-secondary/40 transition-all">
                    <div class="h-1 bg-gradient-to-r from-secondary to-emerald-400 absolute top-0 left-0 right-0"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10 mt-2">
                        <div class="p-3 bg-gradient-to-br from-secondary/10 to-emerald-400/10 rounded-xl border border-secondary/20">
                            <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">assignment_turned_in</span>
                        </div>
                        <span class="text-xs font-bold text-secondary px-2 py-1 bg-secondary/10 rounded-full">+8.2%</span>
                    </div>
                    <p class="text-sm font-medium text-on-surface-variant mb-1 relative z-10">Total Submissions</p>
                    <h3 class="text-3xl font-extrabold text-secondary font-headline relative z-10"><?= $totalSubmissions ?></h3>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-700">
                        <span class="material-symbols-outlined text-9xl">assignment_turned_in</span>
                    </div>
                </div>
                <!-- Card 3: Amber/Tertiary -->
                <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] relative overflow-hidden group border border-outline-variant/30 hover:border-tertiary/40 transition-all">
                    <div class="h-1 bg-gradient-to-r from-tertiary to-amber-400 absolute top-0 left-0 right-0"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10 mt-2">
                        <div class="p-3 bg-gradient-to-br from-tertiary/10 to-amber-400/10 rounded-xl border border-tertiary/20">
                            <span class="material-symbols-outlined text-tertiary" style="font-variation-settings: 'FILL' 1;">bolt</span>
                        </div>
                        <span class="text-xs font-bold text-tertiary px-2 py-1 bg-tertiary/10 rounded-full">REALTIME</span>
                    </div>
                    <p class="text-sm font-medium text-on-surface-variant mb-1 relative z-10">Today's Submissions</p>
                    <h3 class="text-3xl font-extrabold text-tertiary font-headline relative z-10"><?= $todaySubmissions ?></h3>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-700">
                        <span class="material-symbols-outlined text-9xl">bolt</span>
                    </div>
                </div>
                <!-- Card 4: Blue -->
                <div class="bg-surface-container-lowest p-6 rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] relative overflow-hidden group border border-outline-variant/30 hover:border-surface-tint/40 transition-all">
                    <div class="h-1 bg-gradient-to-r from-surface-tint to-blue-400 absolute top-0 left-0 right-0"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10 mt-2">
                        <div class="p-3 bg-gradient-to-br from-surface-tint/10 to-blue-400/10 rounded-xl border border-surface-tint/20">
                            <span class="material-symbols-outlined text-surface-tint" style="font-variation-settings: 'FILL' 1;">new_releases</span>
                        </div>
                        <span class="text-xs font-bold text-surface-tint px-2 py-1 bg-surface-tint/10 rounded-full">RECENT</span>
                    </div>
                    <p class="text-sm font-medium text-on-surface-variant mb-1 relative z-10">Draft Forms</p>
                    <h3 class="text-3xl font-extrabold text-surface-tint font-headline relative z-10"><?= count($recentForms) ?></h3>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-700">
                        <span class="material-symbols-outlined text-9xl">new_releases</span>
                    </div>
                </div>
            </div>

            <!-- Forms Section -->
            <div class="mb-14">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold font-headline">My Forms</h2>
                    <a href="<?= \yii\helpers\Url::to(['form/index']) ?>" class="text-sm font-semibold text-primary-container flex items-center gap-1 hover:underline no-underline">
                        View All <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>

                <?php if (count($forms) == 0): ?>
                    <div class="bg-surface-container-lowest rounded-xl p-12 text-center">
                        <span class="material-symbols-outlined text-6xl text-outline mb-4">description</span>
                        <h3 class="text-2xl font-bold mb-2">No forms yet</h3>
                        <p class="text-on-surface-variant mb-6">Start creating your first form to get started</p>
                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create Your First Form', ['form/create'], [
                            'class' => 'bg-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline inline-flex'
                        ]) ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($forms as $form): ?>
                            <?php
                            $blocks = json_decode($form->schema_js ?? '[]', true);
                            $blockCount = count($blocks);
                            $submissionCount = (int) ($form->submission_count ?? 0);
                            $completionRate = $submissionCount > 0 ? '88%' : '—';
                            ?>
                            <!-- Form Card -->
                            <div class="bg-surface-container-lowest rounded-xl overflow-hidden group hover:shadow-[0_24px_48px_rgba(11,28,48,0.12)] transition-all border border-outline-variant/30 hover:border-primary-container/30">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100">
                                            <span class="material-symbols-outlined text-primary-container">contact_page</span>
                                        </div>
                                        <div class="flex gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                                            <span class="text-[10px] font-bold text-secondary uppercase tracking-tight">Active</span>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold mb-1"><?= Html::encode($form->name) ?></h3>
                                    <p class="text-xs text-on-surface-variant font-medium mb-6">Created <?= Yii::$app->formatter->asDate($form->created_at) ?></p>
                                    <div class="flex items-center gap-8 py-4 border-y border-surface-container-low mb-6">
                                        <div>
                                            <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Fields</p>
                                            <p class="text-lg font-bold font-headline"><?= $blockCount ?></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Responses</p>
                                            <p class="text-lg font-bold font-headline"><?= number_format($submissionCount) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Completion</p>
                                            <p class="text-lg font-bold font-headline text-secondary"><?= $completionRate ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-surface-container-low/30 px-4 py-4 flex items-center justify-between border-t border-surface-container">
                                    <div class="flex gap-1 flex-wrap">
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">visibility</span> View', ['form/view', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'View Form Details'
                                        ]) ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">edit</span> Edit', ['form/update', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'Edit Form'
                                        ]) ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">list</span> Data', ['form/submissions', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'View Submissions'
                                        ]) ?>
                                    </div>
                                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">delete</span>', ['form/delete', 'id' => $form->id], [
                                        'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-error hover:text-error hover:bg-error/5 transition-all inline-flex no-underline text-on-surface-variant',
                                        'title' => 'Delete Form',
                                        'data' => [
                                            'confirm' => 'Are you sure you want to delete this form? All submissions will be lost.',
                                            'method' => 'post',
                                        ]
                                    ]) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Submissions Table -->
            <?php if (!empty($recentSubmissions)): ?>
                <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                    <div class="p-8 border-b border-surface-container-low flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold font-headline">Recent Submissions</h2>
                            <p class="text-sm text-on-surface-variant">Track new data entries as they arrive.</p>
                        </div>
                        <div class="flex gap-3">
                            <button class="px-4 py-2 bg-surface-container-low text-on-surface text-sm font-semibold rounded-xl hover:bg-surface-container-high transition-colors">Export CSV</button>
                            <a href="<?= \yii\helpers\Url::to(['site/submissions']) ?>" class="px-4 py-2 bg-primary-container/10 text-primary-container text-sm font-bold rounded-xl hover:bg-primary-container/20 transition-colors no-underline">View All Submissions</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-surface-container-low/50">
                                    <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-outline">Form Name</th>
                                    <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-outline">Submitted Time</th>
                                    <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-outline text-center">Responses</th>
                                    <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-outline text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                <?php foreach ($recentSubmissions as $submission): ?>
                                    <tr class="hover:bg-surface-container-low/20 transition-colors">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-primary-container/10 flex items-center justify-center text-primary-container">
                                                    <span class="material-symbols-outlined text-[18px]">contact_page</span>
                                                </div>
                                                <span class="font-semibold text-on-surface"><?= Html::encode($submission->form->name) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5 text-sm text-on-surface-variant font-medium"><?= Yii::$app->formatter->asRelativeTime($submission->created_at) ?></td>
                                        <td class="px-8 py-5 text-center">
                                            <span class="inline-flex items-center justify-center bg-secondary/10 text-secondary px-3 py-1 rounded-full text-xs font-bold ring-1 ring-secondary/20">
                                                <?= isset($formSubmissionCounts[$submission->form_id]) ? $formSubmissionCounts[$submission->form_id] : 0 ?> Total
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <a href="<?= \yii\helpers\Url::to(['form/submissions', 'id' => $submission->form_id]) ?>" class="text-primary-container font-bold text-sm hover:underline no-underline">View Entry</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Notification System -->
    <script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>
</body>
