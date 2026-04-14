<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var array $schema */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Fill Form: ' . $model->name;

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
    <main class="pl-64 pt-6 min-h-screen">
        <div class="max-w-[800px] mx-auto px-8 py-8">
            <!-- Header -->
            <div class="mb-10">
                <div class="flex items-center gap-3 mb-2">
                    <a href="<?= \yii\helpers\Url::to(['form/view', 'id' => $model->id]) ?>" class="text-on-surface-variant hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="text-3xl font-extrabold text-on-surface font-headline tracking-tight"><?= Html::encode($model->name) ?></h1>
                </div>
                <p class="text-on-surface-variant font-medium">Fill out the form below and submit your response.</p>
            </div>

            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="bg-secondary/10 text-secondary px-6 py-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                    <?= Yii::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="bg-error/10 text-error px-6 py-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">error</span>
                    <?= Yii::$app->session->getFlash('error') ?>
                </div>
            <?php endif; ?>

            <?php if (empty($schema)): ?>
                <div class="bg-surface-container-lowest rounded-xl p-12 text-center border-t border-outline-variant/10">
                    <span class="material-symbols-outlined text-6xl text-outline mb-4">warning</span>
                    <h3 class="text-2xl font-bold mb-2">Form not ready</h3>
                    <p class="text-on-surface-variant">This form doesn't have any fields yet. Please contact the form creator.</p>
                </div>
            <?php else: ?>
                <div class="bg-surface-container-lowest rounded-xl shadow-[0_20px_40px_rgba(11,28,48,0.03)] overflow-hidden border-t border-outline-variant/10">
                    <div class="p-8">
                        <?php $form = ActiveForm::begin([
                            'action' => ['form/submit', 'id' => $model->id],
                            'method' => 'post',
                        ]); ?>

                        <div class="space-y-6">
                            <?php foreach ($schema as $field): ?>
                                <div>
                                    <label class="block text-sm font-medium text-on-surface mb-2"><?= Html::encode($field['label'] ?? $field['name'] ?? 'Field') ?></label>

                                    <?php if ($field['type'] === 'text'): ?>
                                        <input type="text" name="<?= Html::encode($field['name'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="<?= Html::encode($field['label'] ?? '') ?>" required>

                                    <?php elseif ($field['type'] === 'number'): ?>
                                        <input type="number" name="<?= Html::encode($field['name'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="<?= Html::encode($field['label'] ?? '') ?>" required>

                                    <?php elseif ($field['type'] === 'email'): ?>
                                        <input type="email" name="<?= Html::encode($field['name'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="email@example.com" required>

                                    <?php elseif ($field['type'] === 'textarea'): ?>
                                        <textarea name="<?= Html::encode($field['name'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="<?= Html::encode($field['label'] ?? '') ?>" rows="4" required></textarea>

                                    <?php elseif ($field['type'] === 'date'): ?>
                                        <input type="date" name="<?= Html::encode($field['name'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" required>

                                    <?php elseif ($field['type'] === 'select'): ?>
                                        <select name="<?= Html::encode($field['name'] ?? '') ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" required>
                                            <option value="">-- Select --</option>
                                            <option value="Option 1">Option 1</option>
                                            <option value="Option 2">Option 2</option>
                                            <option value="Option 3">Option 3</option>
                                        </select>

                                    <?php elseif ($field['type'] === 'checkbox'): ?>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="<?= Html::encode($field['name'] ?? '') ?>" class="w-5 h-5 rounded border-outline-variant text-primary-container focus:ring-primary-container/20">
                                            <span class="text-sm font-medium"><?= Html::encode($field['label'] ?? '') ?></span>
                                        </label>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex gap-3 mt-8 pt-6 border-t border-surface-container-low">
                            <button type="submit" class="bg-primary-container text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm">
                                <span class="material-symbols-outlined text-[18px]">send</span> Submit
                            </button>
                            <a href="<?= \yii\helpers\Url::to(['form/view', 'id' => $model->id]) ?>" class="bg-surface-container text-on-surface-variant px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-high transition-all text-sm no-underline">
                                <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back
                            </a>
                        </div>

                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Notification System -->
    <script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>
</body>
