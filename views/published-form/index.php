<?php

/** @var yii\web\View $this */
/** @var app\models\PublishedForm[] $publishedForms */

use yii\bootstrap5\Html;

$this->title = 'Data Form';

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
            <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline/60" placeholder="Search published forms..." type="text" />
        </div>
        <div class="flex items-center gap-4">
            <button class="material-symbols-outlined text-on-surface-variant hover:bg-slate-100 p-2 rounded-full transition-colors">notifications</button>
            <div class="h-8 w-px bg-outline-variant/30"></div>
            <div class="flex items-center gap-3 pl-4">
                <img alt="User profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDVAIMYF86keTHH4bWWsnc6u7upwBRSEk3FvJFjB4wyGvQxjTaWJjqAU9nx7f2YPKnoZKb-kP7pNyFHtjhAggRn0nsW21oGtSNUI1fZH-ddxHG35QnLC24RHVvAReNpdG-gdZoInGl6MMsS4OZcWCyLWu52BmaCzV6K8eTFMqMQMQ_HGc2dgYa6qxFdVSNOiJlHTjCPfpP1KqoRirRptx2ymuk_BKJrEm5kDTAWz-vU_e_4_Qeu3S25BXTLNpFN1WxftL-z6kTgLw" />
            </div>
        </div>
    </nav>

    <?= $this->render('../layouts/_sidebar', ['activeMenu' => 'published-forms']) ?>

    <!-- Main Content -->
    <main class="app-shell-main pl-64 pt-6 min-h-screen">
        <div class="max-w-[1400px] mx-auto px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-primary-container/10 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">public</span>
                        </div>
                        <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight">Data Form</h1>
                    </div>
                    <p class="text-on-surface-variant font-medium">Manage your published forms.</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-md p-6 mb-8 border border-gray-200" style="border-left: 4px solid #4f46e5;">
                <?= Html::beginForm(['published-form/index'], 'get', ['class' => 'flex gap-3']) ?>
                <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-xl gap-3 flex-1">
                    <span class="material-symbols-outlined text-outline">search</span>
                    <?= Html::input('text', 'q', $search ?? null, [
                        'class' => 'bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline',
                        'placeholder' => 'Search published forms by name...',
                    ]) ?>
                </div>
                <?= Html::submitButton('<span class="material-symbols-outlined text-[18px]">search</span> Search', [
                    'class' => 'bg-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm'
                ]) ?>
                <?php if ($search): ?>
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">close</span> Clear', ['published-form/index'], [
                        'class' => 'bg-surface-container text-on-surface-variant px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all text-sm no-underline'
                    ]) ?>
                <?php endif; ?>
                <?= Html::endForm() ?>
            </div>

            <?php if (empty($publishedForms)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-violet-300 shadow-sm">
                    <div class="w-20 h-20 bg-gradient-to-br from-violet-100 to-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-md">
                        <span class="material-symbols-outlined text-5xl text-violet-600">public</span>
                    </div>
                    <h3 class="text-2xl font-extrabold mb-2 text-gray-900">No published forms yet</h3>
                    <p class="text-gray-600 mb-6 font-medium">Publish a form to get started</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-primary-container/5 to-primary/5 border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest text-outline">Name</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest text-outline">URL</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest text-outline">Created At</th>
                                <th class="px-6 py-4 text-right text-[10px] font-bold uppercase tracking-widest text-outline">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($publishedForms as $publishedForm): ?>
                                <tr class="hover:bg-surface-container-low/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-primary-container/10 rounded-lg flex items-center justify-center">
                                                <span class="material-symbols-outlined text-primary-container text-[20px]">description</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-on-surface"><?= Html::encode($publishedForm->name) ?></p>
                                                <p class="text-xs text-on-surface-variant"><?= Html::encode($publishedForm->form->name ?? 'Unknown Form') ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <code class="text-xs bg-surface-container px-2 py-1 rounded"><?= Html::encode($publishedForm->slug) ?></code>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-on-surface-variant"><?= Yii::$app->formatter->asDate($publishedForm->created_at, 'medium') ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <?= Html::a('<span class="material-symbols-outlined text-[18px]">open_in_new</span> View', ['form/render', 'id' => $publishedForm->form_id], [
                                                'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant',
                                                'target' => '_blank'
                                            ]) ?>
                                            <?= Html::a('<span class="material-symbols-outlined text-[18px]">edit</span> Edit', ['published-form/update', 'id' => $publishedForm->id], [
                                                'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-secondary hover:text-secondary transition-all inline-flex items-center gap-1.5 no-underline text-xs font-semibold text-on-surface-variant'
                                            ]) ?>
                                            <?= Html::a('<span class="material-symbols-outlined text-[18px]">delete</span>', ['published-form/delete', 'id' => $publishedForm->id], [
                                                'class' => 'px-3 py-2 bg-white border border-outline-variant rounded-lg hover:border-error hover:text-error hover:bg-error/5 transition-all inline-flex no-underline text-on-surface-variant',
                                                'data' => [
                                                    'confirm' => 'Are you sure you want to delete this published form?',
                                                    'method' => 'post',
                                                ]
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
