<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var array $schema */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = $model->name;

// Parse schema to get pages and custom design
$schemaData = json_decode($model->schema_js, true);
$pages = $schemaData['pages'] ?? null;
$customDesign = $schemaData['customDesign'] ?? [];
$blocks = $schemaData['blocks'] ?? $schema;

// If no pages structure, use old schema format
if (!$pages) {
    $pages = [
        [
            'id' => 'page_1',
            'name' => 'Page 1',
            'blocks' => is_array($schema) ? $schema : []
        ]
    ];
}

// Extract custom design
$customCSS = $customDesign['css'] ?? '';
$customHTMLBefore = $customDesign['htmlBefore'] ?? '';
$customHTMLAfter = $customDesign['htmlAfter'] ?? '';
$customJS = $customDesign['js'] ?? '';

// Determine if we should use custom design (if any custom design element exists)
$hasCustomDesign = !empty($customCSS) || !empty($customHTMLBefore) || !empty($customHTMLAfter) || !empty($customJS);

// DEBUG: Log what's in the database
if (YII_DEBUG) {
    echo '<!-- DEBUG schema_js: ' . htmlspecialchars($model->schema_js) . ' -->';
    echo '<!-- DEBUG customDesign: ' . htmlspecialchars(json_encode($customDesign)) . ' -->';
    echo '<!-- DEBUG hasCustomDesign: ' . ($hasCustomDesign ? 'true' : 'false') . ' -->';
}
?>

<!-- Always load Firebase (required for auth/submission logic) -->
<script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-auth-compat.js"></script>

<?php if (!$hasCustomDesign): ?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'primary': '#4f46e5',
                    'primary-dark': '#4338ca',
                    'success': '#10b981',
                },
                animation: {
                    'fade-in': 'fadeIn 0.6s ease-out',
                    'slide-up': 'slideUp 0.6s ease-out',
                    'slide-left': 'slideLeft 0.6s ease-out',
                    'slide-right': 'slideRight 0.6s ease-out',
                    'scale-in': 'scaleIn 0.6s ease-out',
                },
                keyframes: {
                    fadeIn: {
                        '0%': {
                            opacity: '0'
                        },
                        '100%': {
                            opacity: '1'
                        },
                    },
                    slideUp: {
                        '0%': {
                            opacity: '0',
                            transform: 'translateY(20px)'
                        },
                        '100%': {
                            opacity: '1',
                            transform: 'translateY(0)'
                        },
                    },
                    slideLeft: {
                        '0%': {
                            opacity: '0',
                            transform: 'translateX(-20px)'
                        },
                        '100%': {
                            opacity: '1',
                            transform: 'translateX(0)'
                        },
                    },
                    slideRight: {
                        '0%': {
                            opacity: '0',
                            transform: 'translateX(20px)'
                        },
                        '100%': {
                            opacity: '1',
                            transform: 'translateX(0)'
                        },
                    },
                    scaleIn: {
                        '0%': {
                            opacity: '0',
                            transform: 'scale(0.95)'
                        },
                        '100%': {
                            opacity: '1',
                            transform: 'scale(1)'
                        },
                    },
                }
            }
        }
    }
</script>
<?php endif; ?>

<?php if ($customCSS): ?>
<style>
<?= $customCSS ?>
</style>
<?php endif; ?>

<?php 
// Determine if we should use custom design (if any custom design element exists)
$hasCustomDesign = !empty($customCSS) || !empty($customHTMLBefore) || !empty($customHTMLAfter) || !empty($customJS);
?>

<?php if (!$hasCustomDesign): ?>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-12">
<?php else: ?>
<body>
<?php endif; ?>

        <!-- Custom HTML Before Form -->
        <?php if ($customHTMLBefore): ?>
            <?= $customHTMLBefore ?>
        <?php endif; ?>

        <!-- Form Card - Only show if NO custom HTML -->
        <?php if (!$customHTMLBefore && !$customHTMLAfter): ?>
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary to-primary-dark text-white p-8">
                <h1 class="text-3xl font-bold mb-2"><?= Html::encode($model->name) ?></h1>
                <p class="text-blue-100">Silakan isi form di bawah ini</p>
            </div>

            <!-- Flash Messages -->
            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="mx-8 mt-6 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl flex items-center gap-3">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium"><?= Yii::$app->session->getFlash('success') ?></span>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="mx-8 mt-6 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl flex items-center gap-3">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium"><?= Yii::$app->session->getFlash('error') ?></span>
                </div>
            <?php endif; ?>

            <!-- Firebase Login Panel -->
            <div id="firebaseLoginPanel" class="p-8 hidden">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Login untuk melanjutkan</h2>
                    <p class="text-gray-600">Silakan login sebelum mengisi form ini</p>
                </div>

                <div class="max-w-sm mx-auto space-y-4">
                    <button id="googleLoginBtn" class="w-full flex items-center justify-center gap-3 bg-white border border-gray-300 rounded-xl py-3 px-6 hover:bg-gray-50 transition-all font-medium">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Login dengan Google
                    </button>

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">atau</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <input id="loginEmail" type="email" placeholder="Email" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary">
                        <input id="loginPassword" type="password" placeholder="Password" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary">
                        <button id="emailLoginBtn" class="w-full bg-primary text-white rounded-xl py-3 px-6 hover:bg-primary-dark transition-all font-semibold">
                            Login dengan Email
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Fields -->
            <div id="formContent" class="p-8 hidden">
        <?php endif; ?>

                <?php if (empty($pages)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-medium">Form ini belum memiliki field</p>
                        <p class="text-sm">Hubungi administrator untuk informasi lebih lanjut</p>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= \yii\helpers\Url::to(['form/submit', 'id' => $model->id]) ?>" class="<?= !$hasCustomDesign ? 'space-y-6' : '' ?>" enctype="multipart/form-data" id="multi-page-form">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

                        <!-- Page Content Container -->
                        <div id="page-content-container">
                            <!-- Pages will be rendered here -->
                        </div>

                        <!-- Submit Button - Always at bottom of form -->
                        <div class="mt-8 pt-6 border-t <?= $hasCustomDesign ? 'border-gray-300' : 'border-gray-200' ?>">
                            <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold text-lg rounded-xl hover:shadow-xl transition-all transform hover:-translate-y-1">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Form
                            </button>
                        </div>
                        
                        <?php
                        // Render all pages (first page visible, others hidden)
                        foreach ($pages as $pageIndex => $page):
                            $pageBlocks = $page['blocks'] ?? [];
                        ?>
                        <div class="form-page <?= $pageIndex === 0 ? '' : 'hidden' ?>" data-page-index="<?= $pageIndex ?>" id="page-<?= $page['id'] ?>">
                            <?php foreach ($pageBlocks as $field): ?>
                                <?php
                                $fieldType = $field['type'] ?? 'text';
                                $fieldName = $field['name'] ?? $field['label'] ?? '';
                                $fieldLabel = $field['label'] ?? $fieldName;
                                $required = !empty($field['required']);
                                $placeholder = $field['placeholder'] ?? '';
                                $content = $field['content'] ?? '';
                                $options = $required ? 'required' : '';
                                $width = $field['width'] ?? 'full';
                                $animation = $field['animation'] ?? '';

                                // Width classes
                                $widthClass = 'w-full';
                                if ($width === 'half') $widthClass = 'w-1/2';
                                elseif ($width === 'third') $widthClass = 'w-1/3';
                                elseif ($width === 'quarter') $widthClass = 'w-1/4';

                                // Animation classes
                                $animationClass = '';
                                if ($animation === 'fade-in') $animationClass = 'animate-fade-in';
                                elseif ($animation === 'slide-up') $animationClass = 'animate-slide-up';
                                elseif ($animation === 'slide-left') $animationClass = 'animate-slide-left';
                                elseif ($animation === 'slide-right') $animationClass = 'animate-slide-right';
                                elseif ($animation === 'scale-in') $animationClass = 'animate-scale-in';

                                // Skip layout/container types
                                $skipTypes = ['container', 'columns', 'grid', 'section'];

                                if (in_array($fieldType, $skipTypes)) {
                                    continue;
                                }

                                // Render display/content blocks
                                if ($fieldType === 'heading'):
                                    $level = $field['level'] ?? 'h2';
                                    $align = $field['align'] ?? 'left';
                                    $alignClass = $align === 'center' ? 'text-center' : ($align === 'right' ? 'text-right' : 'text-left');
                                    $tag = in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $level : 'h2';
                                ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <<?= $tag ?> class="text-2xl font-bold text-gray-900 <?= $alignClass ?>">
                                            <?= Html::encode($content ?: $fieldLabel) ?>
                                        </<?= $tag ?>>
                                    </div>

                                <?php elseif ($fieldType === 'text' || $fieldType === 'richtext'): ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <?php if ($fieldType === 'richtext'): ?>
                                            <div class="prose max-w-none text-gray-700"><?= $content ?></div>
                                        <?php else: ?>
                                            <p class="text-gray-700 leading-relaxed"><?= nl2br(Html::encode($content)) ?></p>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($fieldType === 'divider'): ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> my-6">
                                        <hr class="border-gray-300">
                                    </div>

                                <?php elseif ($fieldType === 'spacer'): ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?>" style="height: <?= intval($field['height'] ?? 32) ?>px;"></div>

                                <?php elseif ($fieldType === 'image'): ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <?php if (!empty($field['src'])): ?>
                                            <img src="<?= Html::encode($field['src']) ?>"
                                                alt="<?= Html::encode($field['alt'] ?? '') ?>"
                                                class="w-full h-auto rounded-xl shadow-md">
                                            <?php if (!empty($field['caption'])): ?>
                                                <p class="text-sm text-gray-500 text-center mt-2"><?= Html::encode($field['caption']) ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($fieldType === 'video'): ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <?php if (!empty($field['url'])): ?>
                                            <div class="relative w-full" style="padding-bottom: 56.25%;">
                                                <iframe src="<?= Html::encode($field['url']) ?>"
                                                    class="absolute top-0 left-0 w-full h-full rounded-xl"
                                                    frameborder="0"
                                                    allow="autoplay; encrypted-media"
                                                    allowfullscreen></iframe>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                <?php elseif ($fieldType === 'alert'): ?>
                                    <?php
                                    $variant = $field['variant'] ?? 'info';
                                    $alertClasses = [
                                        'info' => 'bg-blue-50 border-blue-200 text-blue-700',
                                        'success' => 'bg-green-50 border-green-200 text-green-700',
                                        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                                        'error' => 'bg-red-50 border-red-200 text-red-700',
                                    ];
                                    $alertClass = $alertClasses[$variant] ?? $alertClasses['info'];
                                    ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <div class="border rounded-xl px-6 py-4 <?= $alertClass ?>">
                                            <p class="font-medium"><?= Html::encode($field['message'] ?? '') ?></p>
                                        </div>
                                    </div>

                                <?php elseif ($fieldType === 'button'): ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <a href="<?= Html::encode($field['url'] ?? '#') ?>"
                                            class="inline-block bg-gradient-to-r from-primary to-primary-dark text-white font-semibold py-3 px-6 rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 no-underline">
                                            <?= Html::encode($field['text'] ?? 'Click Me') ?>
                                        </a>
                                    </div>

                                <?php elseif ($fieldType === 'hidden'): ?>
                                    <input type="hidden" name="<?= Html::encode($field['name'] ?? '') ?>" value="<?= Html::encode($field['value'] ?? '') ?>">

                                <?php elseif ($fieldType === 'submit'): ?>
                                    <!-- Submit button handled by navigation -->

                                <?php else: ?>
                                    <div class="<?= $widthClass ?> <?= $animationClass ?> mb-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <?= Html::encode($fieldLabel) ?><?= $required ? ' <span class="text-red-500">*</span>' : '' ?>
                                        </label>

                                        <?php if ($fieldType === 'text-input'): ?>
                                            <input type="text" name="<?= Html::encode($fieldName) ?>"
                                                <?= $options ?>
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                                placeholder="<?= Html::encode($placeholder) ?>">

                                        <?php elseif ($fieldType === 'textarea'): ?>
                                            <textarea name="<?= Html::encode($fieldName) ?>" rows="4" <?= $options ?>
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"
                                                placeholder="<?= Html::encode($placeholder) ?>"></textarea>

                                    <?php elseif ($fieldType === 'email'): ?>
                                        <input type="email" name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                            placeholder="<?= Html::encode($placeholder) ?>">

                                    <?php elseif ($fieldType === 'number'): ?>
                                        <input type="number" name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                            placeholder="<?= Html::encode($placeholder) ?>">

                                    <?php elseif ($fieldType === 'password'): ?>
                                        <input type="password" name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                            placeholder="<?= Html::encode($placeholder) ?>">

                                    <?php elseif ($fieldType === 'date'): ?>
                                        <input type="date" name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all">

                                    <?php elseif ($fieldType === 'select'): ?>
                                        <select name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                            <option value=""><?= Html::encode($placeholder ?: 'Pilih salah satu...') ?></option>
                                            <?php
                                            // Handle options - can be string with newlines or array
                                            $optionsList = [];
                                            if (isset($field['options'])) {
                                                if (is_string($field['options'])) {
                                                    $optionsList = array_filter(array_map('trim', explode("\n", $field['options'])));
                                                } elseif (is_array($field['options'])) {
                                                    $optionsList = $field['options'];
                                                }
                                            }
                                            foreach ($optionsList as $option):
                                            ?>
                                                <option value="<?= Html::encode(trim($option)) ?>"><?= Html::encode(trim($option)) ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($fieldType === 'checkbox'): ?>
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" name="<?= Html::encode($fieldName) ?>" value="1" <?= $options ?>
                                                class="w-5 h-5 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-0 transition-all">
                                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">
                                                <?= Html::encode($field['text'] ?? $fieldLabel) ?>
                                            </span>
                                        </label>

                                    <?php elseif ($fieldType === 'radio'): ?>
                                        <div class="space-y-2">
                                            <?php
                                            // Handle options - can be string with newlines or array
                                            $radioOptions = [];
                                            if (isset($field['options'])) {
                                                if (is_string($field['options'])) {
                                                    $radioOptions = array_filter(array_map('trim', explode("\n", $field['options'])));
                                                } elseif (is_array($field['options'])) {
                                                    $radioOptions = $field['options'];
                                                }
                                            }
                                            foreach ($radioOptions as $option):
                                            ?>
                                                <label class="flex items-center gap-3 cursor-pointer group">
                                                    <input type="radio" name="<?= Html::encode($fieldName) ?>" value="<?= Html::encode(trim($option)) ?>" <?= $options ?>
                                                        class="w-5 h-5 border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-0 transition-all">
                                                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">
                                                        <?= Html::encode(trim($option)) ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                    <?php elseif ($fieldType === 'file'): ?>
                                        <input type="file" name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            accept="<?= Html::encode($field['accept'] ?? '*') ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white file:hover:bg-primary-dark file:cursor-pointer">

                                    <?php else: ?>
                                        <input type="text" name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                            placeholder="<?= Html::encode($placeholder) ?>">
                                    <?php endif; ?>

                                    <?php if (!empty($field['help'])): ?>
                                        <p class="mt-1 text-xs text-gray-500"><?= Html::encode($field['help']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Custom HTML After Form -->
                        <?php if ($customHTMLAfter): ?>
                            <?= $customHTMLAfter ?>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            <?php if (!$customHTMLBefore && !$customHTMLAfter): ?>
            </div>
        </div>
            <?php endif; ?>

        <!-- Footer - Only show if NO custom HTML -->
        <?php if (!$customHTMLBefore && !$customHTMLAfter): ?>
        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>Powered by <span class="font-semibold text-primary">TableForge</span></p>
        </div>
    </div>
        <?php endif; ?>

    <!-- Page Navigation JavaScript -->
    <?php if (count($pages) > 1): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentPageIndex = 0;
        const totalPages = <?= count($pages) ?>;
        const pages = <?= json_encode(array_column($pages, 'id')) ?>;
        
        const prevBtn = document.getElementById('prev-page-btn');
        const nextBtn = document.getElementById('next-page-btn');
        const submitBtn = document.getElementById('submit-btn');
        const pageIndicators = document.querySelectorAll('.page-indicator');
        
        function updatePageNavigation() {
            // Show/hide pages
            document.querySelectorAll('.form-page').forEach(function(page, index) {
                if (index === currentPageIndex) {
                    page.classList.remove('hidden');
                } else {
                    page.classList.add('hidden');
                }
            });
            
            // Update buttons
            if (prevBtn) {
                if (currentPageIndex === 0) {
                    prevBtn.classList.add('hidden');
                } else {
                    prevBtn.classList.remove('hidden');
                }
            }
            
            if (nextBtn && submitBtn) {
                if (currentPageIndex === totalPages - 1) {
                    nextBtn.classList.add('hidden');
                    submitBtn.classList.remove('hidden');
                } else {
                    nextBtn.classList.remove('hidden');
                    submitBtn.classList.add('hidden');
                }
            }
            
            // Update indicators
            pageIndicators.forEach(function(indicator, index) {
                if (index === currentPageIndex) {
                    indicator.classList.remove('bg-gray-300');
                    indicator.classList.add('bg-primary');
                } else {
                    indicator.classList.remove('bg-primary');
                    indicator.classList.add('bg-gray-300');
                }
            });
        }
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentPageIndex > 0) {
                    currentPageIndex--;
                    updatePageNavigation();
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentPageIndex < totalPages - 1) {
                    currentPageIndex++;
                    updatePageNavigation();
                }
            });
        }
        
        // Initialize
        updatePageNavigation();
    });
    </script>
    <?php endif; ?>

    <script>
        // Firebase Configuration - Sudah otomatis diisi untuk project TableForge
        const firebaseConfig = {
            apiKey: "AIzaSyCJAQkNbZ-Uor-dW93knoeuwdGsrARI3ow",
            authDomain: "tableforge-659f6.firebaseapp.com",
            projectId: "tableforge-659f6",
            storageBucket: "tableforge-659f6.firebasestorage.app",
            messagingSenderId: "368537173798",
            appId: "1:368537173798:web:19c16fe9836f7f7b7a0be7"
        };

        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        let currentUser = null;

        // DOM Elements
        const loginPanel = document.getElementById('firebaseLoginPanel');
        const formContent = document.getElementById('formContent');
        const googleLoginBtn = document.getElementById('googleLoginBtn');
        const emailLoginBtn = document.getElementById('emailLoginBtn');
        const loginEmail = document.getElementById('loginEmail');
        const loginPassword = document.getElementById('loginPassword');

        // Auth State Observer
        auth.onAuthStateChanged(function(user) {
            currentUser = user;
            if (user) {
                // User is signed in
                console.log('✅ User logged in:', user.email);
                loginPanel.classList.add('hidden');
                formContent.classList.remove('hidden');

                // Inject user data into form
                const hiddenEmail = document.createElement('input');
                hiddenEmail.type = 'hidden';
                hiddenEmail.name = 'user_email';
                hiddenEmail.value = user.email;

                const hiddenName = document.createElement('input');
                hiddenName.type = 'hidden';
                hiddenName.name = 'user_name';
                hiddenName.value = user.displayName || user.email;

                const hiddenUid = document.createElement('input');
                hiddenUid.type = 'hidden';
                hiddenUid.name = 'firebase_uid';
                hiddenUid.value = user.uid;

                const form = document.querySelector('form');
                form.appendChild(hiddenEmail);
                form.appendChild(hiddenName);
                form.appendChild(hiddenUid);

            } else {
                // No user is signed in
                console.log('🔒 No user logged in, showing login panel');
                loginPanel.classList.remove('hidden');
                formContent.classList.add('hidden');
            }
        });

        // Google Login
        googleLoginBtn.addEventListener('click', function() {
            const provider = new firebase.auth.GoogleAuthProvider();
            auth.signInWithPopup(provider)
                .catch(function(error) {
                    console.error('Google login error:', error);
                    alert('Login gagal: ' + error.message);
                });
        });

        // Email/Password Login
        emailLoginBtn.addEventListener('click', function() {
            const email = loginEmail.value;
            const password = loginPassword.value;

            if (!email || !password) {
                alert('Mohon isi email dan password');
                return;
            }

            auth.signInWithEmailAndPassword(email, password)
                .catch(function(error) {
                    console.error('Email login error:', error);
                    alert('Login gagal: ' + error.message);
                });
        });

        // Form submission handler
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');

            if (!form || !submitBtn) {
                console.error('Form or submit button not found!');
                return;
            }

            console.log('✅ Form submission handler attached');

            form.addEventListener('submit', function(e) {
                console.log('🚀 Form submit triggered!');
                console.log('📤 Submitting to:', form.action);

                // Disable button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Mengirim...';
                submitBtn.style.opacity = '0.7';
                submitBtn.style.pointerEvents = 'none';
            });

            submitBtn.addEventListener('click', function(e) {
                if (form.checkValidity()) {
                    form.submit();
                }
            });
        });
    </script>
    
    <!-- Custom JavaScript -->
    <?php if ($customJS): ?>
    <script>
    <?= $customJS ?>
    </script>
    <?php endif; ?>
</body>
