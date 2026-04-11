<?php

/** @var yii\web\View $this */
/** @var app\models\DbTable[] $tables */

use yii\bootstrap5\Html;

$this->title = 'Database Tables';

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
                    'accent-1': '#06b6d4',
                    'accent-2': '#8b5cf6',
                    'accent-3': '#f97316',
                    'accent-4': '#10b981',
                    'accent-5': '#ec4899',
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

<body class="bg-gradient-to-br from-[#f9fafb] via-[#f3f4f6] to-[#ede9fe] font-body text-on-surface" style="background-attachment: fixed;">

<!-- Top Navigation Bar -->
<nav class="fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-gradient-to-r from-[#ffffff]/80 via-[#f8fafd]/80 to-[#eff5ff]/80 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]">
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

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'tables']) ?>

<!-- Main Content -->
<main class="pl-64 pt-24 min-h-screen">
    <div class="max-w-[1400px] mx-auto px-8 py-8">
        <!-- Header -->
        <div class="flex justify-between items-end mb-10">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-surface-tint/10 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-surface-tint" style="font-variation-settings: 'FILL' 1;">table_chart</span>
                    </div>
                    <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight">Database Tables</h1>
                </div>
                <p class="text-on-surface-variant font-medium">Create and manage database tables for your forms.</p>
            </div>
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create Table', ['table-builder/create'], [
                'class' => 'bg-gradient-to-r from-surface-tint to-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
            ]) ?>
        </div>

        <?php if (empty($tables)): ?>
            <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-blue-300 shadow-sm">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-md">
                    <span class="material-symbols-outlined text-5xl text-blue-600">table_chart</span>
                </div>
                <h3 class="text-2xl font-extrabold mb-2 text-gray-900">No tables yet</h3>
                <p class="text-gray-600 mb-6 font-medium">Create your first database table to store form data</p>
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Create First Table', ['table-builder/create'], [
                    'class' => 'bg-gradient-to-r from-surface-tint to-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline inline-flex'
                ]) ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($tables as $item): ?>
                    <?php $table = $item['table']; $columns = $item['columns']; ?>
                    <div class="bg-white hover:shadow-lg transition-all duration-300 rounded-2xl overflow-hidden border border-gray-200" 
                         style="border-left: 5px solid <?= ['#4d44e3', '#06b6d4', '#8b5cf6', '#f97316', '#10b981'][$loop->index % 5] ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <!-- Color accent bar -->
                        <?php 
                        $colorBg = ['bg-indigo-50', 'bg-cyan-50', 'bg-purple-50', 'bg-orange-50', 'bg-emerald-50'][$loop->index % 5];
                        $colorAccent = ['#4d44e3', '#06b6d4', '#8b5cf6', '#f97316', '#10b981'][$loop->index % 5];
                        ?>
                        <div class="<?= $colorBg ?> px-6 py-4 border-b border-gray-100" style="border-left: 5px solid <?= $colorAccent ?>;">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-gradient-to-br from-surface-tint/10 to-primary-container/10 rounded-xl flex items-center justify-center border border-surface-tint/20">
                                    <span class="material-symbols-outlined text-surface-tint">table_chart</span>
                                </div>
                                <?php if ($table->is_created): ?>
                                    <span class="text-xs font-bold text-secondary px-2 py-1 bg-secondary/10 rounded-full flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                        CREATED
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs font-bold text-tertiary px-2 py-1 bg-tertiary/10 rounded-full flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">pending</span>
                                        PENDING
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-lg font-bold mb-1"><?= Html::encode($table->name) ?></h3>
                            <p class="text-xs text-on-surface-variant font-medium mb-6"><?= Html::encode($table->label) ?></p>
                            <?php if ($table->description): ?>
                                <p class="text-sm text-on-surface-variant mb-4"><?= Html::encode($table->description) ?></p>
                            <?php endif; ?>
                            <div class="flex items-center gap-6 py-4 border-b border-gray-100 mb-2" style="background: linear-gradient(to right, rgba(<?= ['77,68,227', '6,182,212', '139,92,246', '249,115,22', '16,185,129'][$loop->index % 5] ?>, 0.03), transparent);">
                                <div>
                                    <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Columns</p>
                                    <p class="text-lg font-bold font-headline text-surface-tint"><?= count($columns) ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-outline font-bold uppercase tracking-widest mb-1">Engine</p>
                                    <p class="text-lg font-bold font-headline text-primary-container"><?= $table->engine ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach (array_slice($columns, 0, 3) as $col): ?>
                                    <span class="text-[10px] font-medium bg-surface-container px-2 py-1 rounded-full"><?= Html::encode($col->name) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($columns) > 3): ?>
                                    <span class="text-[10px] font-medium bg-surface-container px-2 py-1 rounded-full">+<?= count($columns) - 3 ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                        $footerBg = ['bg-indigo-50/50', 'bg-cyan-50/50', 'bg-purple-50/50', 'bg-orange-50/50', 'bg-emerald-50/50'][$loop->index % 5];
                        ?>
                        <div class="<?= $footerBg ?> px-4 py-4 flex items-center justify-between border-t border-gray-100">
                            <div class="flex gap-1 flex-wrap">
                                <?= Html::a('<span class="material-symbols-outlined text-[18px]">visibility</span> View', ['table-builder/view', 'id' => $table->id], [
                                    'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                    'title' => 'View Table Details'
                                ]) ?>
                                <?= Html::a('<span class="material-symbols-outlined text-[18px]">edit</span> Edit', ['table-builder/update', 'id' => $table->id], [
                                    'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                    'title' => 'Edit Table'
                                ]) ?>
                                <?php if (!$table->is_created): ?>
                                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">play_arrow</span> Create', ['table-builder/execute-sql', 'id' => $table->id], [
                                        'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-secondary hover:text-secondary transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                        'title' => 'Create Table in Database',
                                        'data' => [
                                            'confirm' => 'Create this table in the database?',
                                            'method' => 'post',
                                        ]
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                            <?= Html::a('<span class="material-symbols-outlined text-[18px]">delete</span>', ['table-builder/delete', 'id' => $table->id], [
                                'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-error hover:text-error hover:bg-error/5 transition-all inline-flex no-underline text-on-surface-variant',
                                'title' => 'Delete Table',
                                'data' => [
                                    'confirm' => 'Are you sure you want to delete this table? All data will be lost.',
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
</body>
