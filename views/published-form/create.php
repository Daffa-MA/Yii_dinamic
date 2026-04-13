<?php

/** @var yii\web\View $this */
/** @var app\models\PublishedForm $model */
/** @var app\models\Form[] $forms */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Publish Form';

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap');
?>

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
                    'outline-variant': '#c7c4d8',
                    'outline': '#777587',
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

<body class="bg-gradient-to-br from-[#f9fafb] via-[#f3f4f6] to-[#ede9fe] font-body text-on-surface">

    <nav class="fixed top-0 left-64 right-0 z-50 flex items-center justify-between px-8 h-20 bg-gradient-to-r from-[#ffffff]/80 via-[#f8fafd]/80 to-[#f0f4f9]/80 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]">
        <div class="flex items-center gap-3">
            <a href="<?= \yii\helpers\Url::to(['published-form/index']) ?>" class="text-on-surface-variant hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="text-xl font-bold font-headline">Publish Form</h1>
        </div>
        <div class="flex items-center gap-3 pl-4">
            <img alt="User profile" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDVAIMYF86keTHH4bWWsnc6u7upwBRSEk3FvJFjB4wyGvQxjTaWJjqAU9nx7f2YPKnoZKb-kP7pNyFHtjhAggRn0nsW21oGtSNUI1fZH-ddxHG35QnLC24RHVvAReNpdG-gdZoInGl6MMsS4OZcWCyLWu52BmaCzV6K8eTFMqMQMQ_HGc2dgYa6qxFdVSNOiJlHTjCPfpP1KqoRirRptx2ymuk_BKJrEm5kDTAWz-vU_e_4_Qeu3S25BXTLNpFN1WxftL-z6kTgLw" />
        </div>
    </nav>

    <?= $this->render('../layouts/_sidebar', ['activeMenu' => 'published-forms']) ?>

    <main class="pl-64 pt-6 min-h-screen">
        <div class="max-w-[800px] mx-auto px-8 py-8">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                <div class="bg-gradient-to-r from-primary-container/10 to-primary/5 px-8 py-6 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-container/10 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary-container" style="font-variation-settings: 'FILL' 1;">public</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold font-headline text-on-surface">Publish Form</h2>
                            <p class="text-sm text-on-surface-variant">Enter a name and select a form to publish</p>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <?php $form = ActiveForm::begin([
                        'options' => ['class' => 'space-y-6'],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'block text-sm font-semibold text-on-surface mb-2'],
                            'inputOptions' => [
                                'class' => 'w-full px-4 py-3 bg-surface-container-high border border-outline-variant rounded-xl text-sm focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all',
                            ],
                        ],
                    ]) ?>

                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Enter published form name...',
                    ]) ?>

                    <?= $form->field($model, 'form_id')->dropDownList(
                        \yii\helpers\ArrayHelper::map($forms, 'id', 'name'),
                        [
                            'prompt' => 'Select a form to publish...',
                            'class' => 'w-full px-4 py-3 bg-surface-container-high border border-outline-variant rounded-xl text-sm focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all',
                        ]
                    ) ?>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                        <?= Html::a('Cancel', ['published-form/index'], [
                            'class' => 'px-6 py-3 bg-surface-container text-on-surface-variant rounded-xl font-semibold hover:bg-surface-container-high transition-all text-sm no-underline'
                        ]) ?>
                        <?= Html::submitButton('<span class="material-symbols-outlined text-[18px]">public</span> Publish Form', [
                            'class' => 'bg-gradient-to-r from-primary-container to-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm border-0 cursor-pointer'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end() ?>
                </div>
            </div>
        </div>
    </main>
</body>
