<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var app\models\FormSubmission[] $submissions */

use yii\bootstrap5\Html;

$this->title = 'Form: ' . $model->name;

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

<?= $this->render('../layouts/_sidebar', ['activeMenu' => 'forms']) ?>

<!-- Main Content -->
<main class="pl-64 pt-24 min-h-screen">
    <div class="max-w-[1400px] mx-auto px-8 py-8">
        <!-- Header -->
        <div class="flex justify-between items-end mb-10">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="<?= \yii\helpers\Url::to(['form/index']) ?>" class="text-on-surface-variant hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight"><?= Html::encode($model->name) ?></h1>
                </div>
                <p class="text-on-surface-variant font-medium">View form details and submissions.</p>
            </div>
            <div class="flex items-center gap-3">
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">content_copy</span> Duplicate', ['form/duplicate', 'id' => $model->id], [
                    'class' => 'bg-surface-container text-on-surface px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all active:scale-95 text-sm no-underline',
                    'data' => [
                        'confirm' => 'Duplicate this form with all fields?',
                        'method' => 'post',
                    ]
                ]) ?>
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">edit</span> Edit', ['form/update', 'id' => $model->id], [
                    'class' => 'bg-surface-container text-on-surface px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all active:scale-95 text-sm no-underline'
                ]) ?>
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">input</span> Fill Form', ['form/render', 'id' => $model->id], [
                    'class' => 'bg-primary-container text-white px-4 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
                ]) ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Form Details -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">info</span>
                        <h2 class="text-xl font-bold font-headline">Form Details</h2>
                    </div>
                </div>
                <div class="p-8">
                    <table class="w-full">
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant w-40">Form Name</td>
                            <td class="py-4 text-sm font-semibold"><?= Html::encode($model->name) ?></td>
                        </tr>
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Created At</td>
                            <td class="py-4 text-sm font-semibold"><?= date('M d, Y H:i:s', strtotime($model->created_at)) ?></td>
                        </tr>
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Updated At</td>
                            <td class="py-4 text-sm font-semibold"><?= date('M d, Y H:i:s', strtotime($model->updated_at)) ?></td>
                        </tr>
                        <tr class="border-b border-surface-container-low">
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Total Fields</td>
                            <td class="py-4"><span class="bg-primary-container/10 text-primary-container px-3 py-1 rounded-full text-xs font-bold"><?= count($model->getSchema()) ?></span></td>
                        </tr>
                        <tr>
                            <td class="py-4 text-sm font-medium text-on-surface-variant">Total Submissions</td>
                            <td class="py-4"><span class="bg-secondary/10 text-secondary px-3 py-1 rounded-full text-xs font-bold"><?= count($submissions) ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Form Schema -->
            <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                <div class="p-8 border-b border-surface-container-low">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">list</span>
                        <h2 class="text-xl font-bold font-headline">Form Schema</h2>
                    </div>
                </div>
                <div class="p-8">
                    <?php if (empty($model->getSchema())): ?>
                        <p class="text-on-surface-variant text-center py-8">No fields defined.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($model->getSchema() as $index => $field): ?>
                                <div class="flex items-center justify-between p-4 bg-surface-container rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 bg-primary-container/10 rounded-lg flex items-center justify-center text-primary-container text-sm font-bold"><?= $index + 1 ?></span>
                                        <div>
                                            <p class="text-sm font-semibold"><?= Html::encode($field['label']) ?></p>
                                            <p class="text-xs text-on-surface-variant"><code><?= Html::encode($field['name']) ?></code></p>
                                        </div>
                                    </div>
                                    <span class="bg-surface-container-high text-on-surface-variant px-3 py-1 rounded-full text-xs font-medium"><?= Html::encode($field['type']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submissions -->
        <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
            <div class="p-8 border-b border-surface-container-low flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-surface-tint" style="font-variation-settings: 'FILL' 1;">inbox</span>
                    <h2 class="text-xl font-bold font-headline">Recent Submissions</h2>
                </div>
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">list</span> View All Submissions', ['form/submissions', 'id' => $model->id], [
                    'class' => 'bg-primary-container/10 text-primary-container px-4 py-2 rounded-xl text-sm font-bold hover:bg-primary-container/20 transition-colors no-underline flex items-center gap-2'
                ]) ?>
            </div>
            <div class="p-8">
                <?php if (empty($submissions)): ?>
                    <p class="text-on-surface-variant text-center py-8">No submissions yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-surface-container-low">
                                    <th class="py-4 text-[10px] font-bold uppercase tracking-widest text-outline">ID</th>
                                    <th class="py-4 text-[10px] font-bold uppercase tracking-widest text-outline">Submitted At</th>
                                    <th class="py-4 text-[10px] font-bold uppercase tracking-widest text-outline text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                <?php foreach (array_slice($submissions, 0, 5) as $submission): ?>
                                <tr class="hover:bg-surface-container-low/20 transition-colors">
                                    <td class="py-4 text-sm font-medium"><?= $submission->id ?></td>
                                    <td class="py-4 text-sm text-on-surface-variant"><?= date('M d, Y H:i:s', strtotime($submission->created_at)) ?></td>
                                    <td class="py-4 text-right">
                                        <?= Html::a('<span class="material-symbols-outlined text-[18px]">visibility</span> View', ['form/submissions', 'id' => $model->id], [
                                            'class' => 'text-primary-container font-bold text-sm hover:underline no-underline flex items-center gap-1 justify-end'
                                        ]) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
