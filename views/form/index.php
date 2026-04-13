<?php

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */

use yii\bootstrap5\Html;

$this->title = 'My Forms';

// Styles for dashboard layout
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap');
?>

<!-- Tailwind CSS v3 -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'on-surface': '#0b1c30',
                    'on-surface-variant': '#464555',
                    'surface': '#fafbfe',
                    'surface-container-lowest': '#ffffff',
                    'surface-container-low': '#f8fafd',
                    'surface-container': '#f0f4f9',
                    'surface-container-high': '#e8eef7',
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
</script>

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>

<body class="bg-gradient-to-br from-[#f9fafb] via-[#f3f4f6] to-[#ede9fe] font-body text-on-surface" style="background-attachment: fixed;">

    <!-- Top Navigation Bar -->
    <nav class="app-shell-nav fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-gradient-to-r from-[#ffffff]/80 via-[#f8fafd]/80 to-[#f0f4f9]/80 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]">
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

    <?= $this->render('../layouts/_sidebar', ['activeMenu' => 'forms']) ?>

    <!-- Main Content -->
    <main class="app-shell-main pl-64 pt-6 min-h-screen">
        <div class="max-w-[1400px] mx-auto px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-primary-container/10 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">description</span>
                        </div>
                        <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight">My Forms</h1>
                    </div>
                    <p class="text-on-surface-variant font-medium">Manage and organize all your forms.</p>
                </div>
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create New Form', ['form/create'], [
                    'class' => 'bg-gradient-to-r from-primary-container to-primary text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
                ]) ?>
            </div>

            <!-- Search Bar -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-md p-6 mb-8 border border-gray-200" style="border-left: 4px solid #4f46e5;">
                <?= Html::beginForm(['form/index'], 'get', ['class' => 'flex gap-3']) ?>
                <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-xl gap-3 flex-1">
                    <span class="material-symbols-outlined text-outline">search</span>
                    <?= Html::input('text', 'q', $search ?? null, [
                        'class' => 'bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline',
                        'placeholder' => 'Search forms by name...',
                    ]) ?>
                </div>
                <?= Html::submitButton('<span class="material-symbols-outlined text-[18px]">search</span> Search', [
                    'class' => 'bg-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm'
                ]) ?>
                <?php if ($search): ?>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">close</span> Clear', ['form/index'], [
                        'class' => 'bg-surface-container text-on-surface-variant px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all text-sm no-underline'
                    ]) ?>
                <?php endif; ?>
                <?= Html::endForm() ?>
            </div>

            <?php if (empty($forms)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-violet-300 shadow-sm">
                    <div class="w-20 h-20 bg-gradient-to-br from-violet-100 to-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-md">
                        <span class="material-symbols-outlined text-5xl text-violet-600">description</span>
                    </div>
                    <h3 class="text-2xl font-extrabold mb-2 text-gray-900">No forms yet</h3>
                    <p class="text-gray-600 mb-6 font-medium">Start creating your first form to get started</p>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create Your First Form', ['form/create'], [
                        'class' => 'bg-gradient-to-r from-primary-container to-primary text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline inline-flex'
                    ]) ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($forms as $form): ?>
                        <?php
                        $blocks = json_decode($form->schema_json ?? '[]', true);
                        $blockCount = count($blocks);
                        $submissionCount = $form->submissions ? count($form->submissions) : 0;
                        ?>
                        <div class="bg-white hover:shadow-lg transition-all duration-300 rounded-2xl overflow-hidden border border-gray-200"
                            style="border-left: 5px solid <?= ['#4f46e5', '#06b6d4', '#8b5cf6', '#f97316', '#10b981'][$loop->index % 5] ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <!-- Color accent bar -->
                            <?php
                            $colorBg = ['bg-violet-50', 'bg-cyan-50', 'bg-purple-50', 'bg-orange-50', 'bg-emerald-50'][$loop->index % 5];
                            $colorText = ['text-violet-700', 'text-cyan-700', 'text-purple-700', 'text-orange-700', 'text-emerald-700'][$loop->index % 5];
                            $colorAccent = ['#4f46e5', '#06b6d4', '#8b5cf6', '#f97316', '#10b981'][$loop->index % 5];
                            ?>
                            <div class="<?= $colorBg ?> px-6 py-4 border-b border-gray-100" style="border-left: 5px solid <?= $colorAccent ?>;">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="w-12 h-12 bg-gradient-to-br from-primary-container/10 to-primary/10 rounded-xl flex items-center justify-center border border-primary-container/20">
                                            <span class="material-symbols-outlined text-primary-container">contact_page</span>
                                        </div>
                                        <div class="flex gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                                            <span class="text-[10px] font-bold text-secondary uppercase tracking-tight">Active</span>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold mb-1"><?= Html::encode($form->name) ?></h3>
                                    <p class="text-xs text-on-surface-variant font-medium mb-6">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px] text-outline">calendar_today</span>
                                            <?= Yii::$app->formatter->asDate($form->created_at) ?>
                                        </span>
                                    </p>
                                    <div class="flex items-center gap-8 py-4 border-b border-gray-100 mb-4" style="background: linear-gradient(to right, rgba(<?= ['79,70,229', '6,182,212', '139,92,246', '249,115,22', '16,185,129'][$loop->index % 5] ?>, 0.03), transparent);">
                                        <div>
                                            <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Fields</p>
                                            <p class="text-lg font-bold font-headline text-primary-container"><?= $blockCount ?></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Responses</p>
                                            <p class="text-lg font-bold font-headline text-secondary"><?= number_format($submissionCount) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $footerBg = ['bg-violet-50/50', 'bg-cyan-50/50', 'bg-purple-50/50', 'bg-orange-50/50', 'bg-emerald-50/50'][$loop->index % 5];
                                ?>
                                <div class="<?= $footerBg ?> px-4 py-4 flex items-center justify-between border-t border-gray-100">
                                    <div class="flex gap-1 flex-wrap">
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">visibility</span> View', ['form/view', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'View Form Details'
                                        ]) ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">edit</span> Edit', ['form/update', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'Edit Form'
                                        ]) ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">input</span> Fill', ['form/render', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-secondary hover:text-secondary transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'Fill Form'
                                        ]) ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">content_copy</span> Copy', ['form/duplicate', 'id' => $form->id], [
                                            'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-surface-tint hover:text-surface-tint transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                            'title' => 'Duplicate Form',
                                            'data' => [
                                                'confirm' => 'Duplicate this form?',
                                                'method' => 'post',
                                            ]
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
    </main>

    <!-- Notification System -->
    <script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>
</body>
