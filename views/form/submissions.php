<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var yii\data\Pagination $pages */
/** @var app\models\FormSubmission[] $submissions */

use yii\bootstrap5\Html;
use yii\widgets\LinkPager;

$this->title = 'Submissions: ' . $model->name;

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
</script>

<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>

<body class="bg-surface font-body text-on-surface">

    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-[#e5e9f0]/70 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]">
        <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-full gap-3 min-w-[320px]">
            <span class="material-symbols-outlined text-outline text-[20px]">search</span>
            <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline/60" placeholder="Search forms, analytics, or users..." type="text" />
        </div>
        <div class="flex items-center gap-4">
            <button class="material-symbols-outlined text-on-surface-variant hover:bg-slate-100 p-2 rounded-full transition-colors">notifications</button>
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
    <main class="pl-64 pt-6 min-h-screen">
        <div class="max-w-[1400px] mx-auto px-8 py-8">
            <!-- Header -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="<?= \yii\helpers\Url::to(['form/view', 'id' => $model->id]) ?>" class="text-on-surface-variant hover:text-on-surface transition-colors">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </a>
                        <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight">Submissions for <?= Html::encode($model->name) ?></h1>
                    </div>
                    <p class="text-on-surface-variant font-medium">View all form responses and data entries.</p>
                </div>
                <div class="flex items-center gap-3">
                    <?= Html::a('<span class="material-symbols-outlined text-[18px]">download</span> Export CSV', ['form/export', 'id' => $model->id], [
                        'class' => 'bg-secondary/10 text-secondary px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-secondary/20 transition-all active:scale-95 text-sm no-underline'
                    ]) ?>
                </div>
            </div>

            <?php if (empty($submissions)): ?>
                <div class="bg-surface-container-lowest rounded-xl p-12 text-center border-t border-outline-variant/10">
                    <span class="material-symbols-outlined text-6xl text-outline mb-4">inbox</span>
                    <h3 class="text-2xl font-bold mb-2">No submissions yet</h3>
                    <p class="text-on-surface-variant">No one has filled out this form yet.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($submissions as $submission): ?>
                        <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                            <div class="p-6 border-b border-surface-container-low flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-primary-container">event</span>
                                    <span class="text-sm font-semibold"><?= date('M d, Y H:i:s', strtotime($submission->created_at)) ?></span>
                                </div>
                                <span class="bg-primary-container/10 text-primary-container px-3 py-1 rounded-full text-xs font-bold">Submission #<?= $submission->id ?></span>
                            </div>
                            <div class="p-6">
                                <table class="w-full">
                                    <tbody class="divide-y divide-surface-container-low">
                                        <?php
                                        $data = $submission->getData();
                                        $schema = $model->getSchema();
                                        foreach ($schema as $field):
                                        ?>
                                            <tr>
                                                <td class="py-4 text-sm font-medium text-on-surface-variant w-48"><?= Html::encode($field['label']) ?></td>
                                                <td class="py-4 text-sm font-semibold"><?= Html::encode($data[$field['name']] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 text-center">
                    <?= LinkPager::widget([
                        'pagination' => $pages,
                        'options' => ['class' => 'flex justify-center gap-1'],
                        'linkOptions' => ['class' => 'px-4 py-2 bg-surface-container rounded-xl text-sm font-medium hover:bg-surface-container-high transition-colors no-underline'],
                        'activePageCssClass' => 'bg-primary-container text-white',
                    ]) ?>
                    <p class="text-sm text-on-surface-variant mt-4">Showing <?= count($submissions) ?> of <?= $pages->totalCount ?> submissions</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
