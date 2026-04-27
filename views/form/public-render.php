<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var array $schema */
/** @var array $fkConfig */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\HtmlPurifier;
use yii\helpers\Url;

$this->title = $model->name;
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJs("document.body.style.minHeight = '100vh';", \yii\web\View::POS_READY);
$fkConfig = isset($fkConfig) && is_array($fkConfig) ? $fkConfig : [];
$fkConfigJson = json_encode($fkConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Parse schema to get pages and custom design
$schemaData = json_decode($model->schema_js, true);
$pages = $schemaData['pages'] ?? null;
$customDesign = $schemaData['customDesign'] ?? [];
$blocks = $schemaData['blocks'] ?? $schema;

$sanitizeCustomCss = static function (string $css): string {
    $css = trim($css);
    if ($css === '') {
        return '';
    }

    $css = preg_replace('/<\\/?style\\b[^>]*>/i', '', $css) ?? $css;
    $css = preg_replace('/@import\\s+/i', '', $css) ?? $css;
    $css = preg_replace('/expression\\s*\\(/i', '', $css) ?? $css;
    $css = preg_replace('/javascript\\s*:/i', '', $css) ?? $css;
    $css = preg_replace('/vbscript\\s*:/i', '', $css) ?? $css;
    $css = preg_replace('/behavior\\s*:/i', '', $css) ?? $css;
    $css = preg_replace('/-moz-binding\\s*:/i', '', $css) ?? $css;
    $css = preg_replace('/url\\s*\\(\\s*[\'"]?\\s*(javascript|vbscript)\\s*:/i', 'url(', $css) ?? $css;

    return trim($css);
};

$sanitizeCustomHtml = static function (string $html): string {
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    return HtmlPurifier::process($html, [
        'HTML.SafeIframe' => false,
        'URI.DisableExternalResources' => false,
        'URI.DisableResources' => false,
        'Attr.EnableID' => false,
        'HTML.Allowed' => implode(',', [
            'div', 'span', 'p', 'br', 'hr',
            'strong', 'b', 'em', 'i', 'u',
            'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'code', 'pre',
            'a[href|title|target|rel]',
            'img[src|alt|title|width|height]',
        ]),
        'Attr.AllowedFrameTargets' => ['_blank'],
        'AutoFormat.RemoveEmpty' => true,
    ]);
};

// If no pages structure, use old schema format
if (!$pages) {
    $pages = [
        [
            'id' => 'page_1',
            'name' => 'Page 1',
            'blocks' => is_array($schema) ? $schema : []
        ]
    ];
} else {
    // Backward-safe fallback:
    // if pages exist but all are empty while flattened blocks still exist, use those blocks.
    $hasPageBlocks = false;
    foreach ($pages as $page) {
        if (!empty($page['blocks']) && is_array($page['blocks'])) {
            $hasPageBlocks = true;
            break;
        }
    }
    if (!$hasPageBlocks && !empty($blocks) && is_array($blocks)) {
        $pages[0]['blocks'] = $blocks;
    }
}

// Extract custom design
$customCSS = $sanitizeCustomCss((string)($customDesign['css'] ?? ''));
$customHTMLBefore = $sanitizeCustomHtml((string)($customDesign['htmlBefore'] ?? ''));
$customHTMLAfter = $sanitizeCustomHtml((string)($customDesign['htmlAfter'] ?? ''));
$customJS = '';

// Determine if we should use custom design (if any custom design element exists)
$hasCustomDesign = !empty($customCSS) || !empty($customHTMLBefore) || !empty($customHTMLAfter) || !empty($customJS);

?>

<!-- Always load Firebase (required for auth/submission logic) -->
<script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-auth-compat.js"></script>

<?php if (!$hasCustomDesign): ?>
<script>
    if (window.console) {
        window.console.log = function() {};
        window.console.info = function() {};
        window.console.debug = function() {};
    }

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
<?php $this->registerJs("document.body.style.background = 'linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%)';", \yii\web\View::POS_READY); ?>
    <div class="max-w-2xl mx-auto px-4 py-12">
<?php else: ?>
<?php endif; ?>

        <!-- Default Firebase Login Panel (always available for guest users) -->
        <div id="firebaseLoginPanel" style="display: block; max-width: 420px; margin: 48px auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); padding: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 8px;">Login untuk melanjutkan</h2>
                <p style="margin: 0; color: #6b7280;">Silakan login sebelum mengisi form ini</p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button id="googleLoginBtn" type="button" style="width: 100%; border: 1px solid #d1d5db; background: #fff; color: #111827; border-radius: 12px; padding: 12px; cursor: pointer; font-weight: 600;">
                    Login dengan Google
                </button>
                <input id="loginEmail" type="email" placeholder="Email" style="width: 100%; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px;">
                <input id="loginPassword" type="password" placeholder="Password" style="width: 100%; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px;">
                <button id="emailLoginBtn" type="button" style="width: 100%; border: none; background: #4f46e5; color: #fff; border-radius: 12px; padding: 12px; cursor: pointer; font-weight: 700;">
                    Login dengan Email
                </button>
            </div>
        </div>

        <div id="formContent" style="display: none;">
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

                        <?php if (!$hasCustomDesign): ?>
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
                                    $fkMeta = (is_string($fieldName) && isset($fkConfig[$fieldName]) && is_array($fkConfig[$fieldName])) ? $fkConfig[$fieldName] : null;

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

                                            <?php if ($fkMeta !== null): ?>
                                                <?php
                                                $fkOptions = isset($fkMeta['options']) && is_array($fkMeta['options']) ? $fkMeta['options'] : [];
                                                $quickAddFields = isset($fkMeta['quickAddFields']) && is_array($fkMeta['quickAddFields']) ? $fkMeta['quickAddFields'] : [];
                                                ?>
                                                <div class="flex items-center gap-2">
                                                    <select name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                                        data-fk-field="<?= Html::encode($fieldName) ?>"
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                                        <option value=""><?= Html::encode($placeholder ?: 'Pilih salah satu...') ?></option>
                                                        <?php foreach ($fkOptions as $fkOption): ?>
                                                            <option value="<?= Html::encode((string)($fkOption['value'] ?? '')) ?>">
                                                                <?= Html::encode((string)($fkOption['label'] ?? '')) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button"
                                                        class="quick-add-btn px-3 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-sm font-bold text-gray-700"
                                                        data-fk-field="<?= Html::encode($fieldName) ?>"
                                                        data-fk-label="<?= Html::encode($fieldLabel) ?>"
                                                        <?= empty($quickAddFields) ? 'style="display:none;"' : '' ?>
                                                        title="Tambah Baru">+</button>
                                                </div>

                                            <?php elseif ($fieldType === 'text-input'): ?>
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
                        <?php endif; ?>
                        
                        <!-- Custom HTML After Form -->
                        <?php if ($customHTMLAfter): ?>
                            <?= $customHTMLAfter ?>
                        <?php endif; ?>

                        <!-- Default Submit Button (always at very bottom of visible form content) -->
                        <?php if (!$hasCustomDesign): ?>
                            <div class="mt-8 pt-6 border-t border-gray-200">
                                <button id="submit-btn" type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold text-lg rounded-xl hover:shadow-xl transition-all transform hover:-translate-y-1">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Form
                                </button>
                            </div>
                        <?php else: ?>
                            <div style="margin-top:24px;">
                                <button id="submit-btn" type="submit" style="width:100%;padding:14px 20px;border:none;border-radius:12px;background:linear-gradient(135deg,#16a34a,#22c55e);color:#ffffff;font-size:16px;font-weight:700;cursor:pointer;box-shadow:0 12px 24px rgba(34,197,94,0.28);transition:all .2s ease;">
                                    Submit Form
                                </button>
                            </div>
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
        </div>

    <div id="fkQuickAddModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b px-5 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Tambah Data Referensi</h3>
                <button type="button" id="fkQuickAddClose" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form id="fkQuickAddForm" class="px-5 py-4 space-y-4">
                <input type="hidden" id="fkQuickAddField" name="field" value="">
                <div id="fkQuickAddFields"></div>
                <div class="flex items-center justify-end gap-2 border-t pt-4">
                    <button type="button" id="fkQuickAddCancel" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-white font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

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
        const fkConfigMap = <?= $fkConfigJson ?: '{}' ?>;
        const fkQuickAddUrl = <?= json_encode(Url::to(['form/fk-quick-add', 'id' => $model->id])) ?>;
        const fkOptionsUrl = <?= json_encode(Url::to(['form/fk-options', 'id' => $model->id])) ?>;

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
        const form = document.querySelector('form');
        const quickAddModal = document.getElementById('fkQuickAddModal');
        const quickAddClose = document.getElementById('fkQuickAddClose');
        const quickAddCancel = document.getElementById('fkQuickAddCancel');
        const quickAddForm = document.getElementById('fkQuickAddForm');
        const quickAddFieldInput = document.getElementById('fkQuickAddField');
        const quickAddFieldsContainer = document.getElementById('fkQuickAddFields');

        function renderQuickAddFields(fieldName) {
            if (!quickAddFieldsContainer) return;
            const config = fkConfigMap[fieldName] || {};
            const fields = Array.isArray(config.quickAddFields) ? config.quickAddFields : [];
            quickAddFieldsContainer.innerHTML = '';

            if (fields.length === 0) {
                quickAddFieldsContainer.innerHTML = '<p class="text-sm text-gray-500">Field tambahan tidak diperlukan.</p>';
                return;
            }

            fields.forEach((item) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'space-y-1';
                const label = document.createElement('label');
                label.className = 'block text-sm font-medium text-gray-700';
                label.textContent = item.label || item.name;
                const input = document.createElement('input');
                input.type = item.inputType || 'text';
                input.name = item.name;
                input.required = !!item.required;
                input.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent';
                wrapper.appendChild(label);
                wrapper.appendChild(input);
                quickAddFieldsContainer.appendChild(wrapper);
            });
        }

        function openQuickAddModal(fieldName) {
            if (!quickAddModal || !quickAddForm || !quickAddFieldInput) return;
            quickAddFieldInput.value = fieldName;
            renderQuickAddFields(fieldName);
            quickAddModal.classList.remove('hidden');
            quickAddModal.classList.add('flex');
        }

        function closeQuickAddModal() {
            if (!quickAddModal || !quickAddForm) return;
            quickAddModal.classList.remove('flex');
            quickAddModal.classList.add('hidden');
            quickAddForm.reset();
        }

        async function refreshFkOptions(fieldName) {
            const targetSelect = document.querySelector('select[data-fk-field="' + fieldName + '"]');
            if (!targetSelect) return;

            const payload = new URLSearchParams();
            payload.append('field', fieldName);
            payload.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

            const response = await fetch(fkOptionsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: payload.toString()
            });
            const result = await response.json();
            if (!result.success || !Array.isArray(result.options)) return;

            targetSelect.querySelectorAll('option:not([value=""])').forEach((opt) => opt.remove());
            result.options.forEach((opt) => {
                const optionEl = document.createElement('option');
                optionEl.value = String(opt.value ?? '');
                optionEl.textContent = String(opt.label ?? '');
                targetSelect.appendChild(optionEl);
            });
        }

        function upsertHiddenInput(name, value) {
            if (!form) return;
            let input = form.querySelector('input[name="' + name + '"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.appendChild(input);
            }
            input.value = value || '';
        }

        function setAuthView(isLoggedIn) {
            if (loginPanel) {
                loginPanel.style.display = isLoggedIn ? 'none' : 'block';
            }
            if (formContent) {
                formContent.style.display = isLoggedIn ? 'block' : 'none';
            }
        }

        // Auth State Observer
        auth.onAuthStateChanged(function(user) {
            currentUser = user;
            if (user) {
                // User is signed in
                console.log('✅ User logged in:', user.email);
                setAuthView(true);

                // Inject user data into form
                upsertHiddenInput('user_email', user.email);
                upsertHiddenInput('user_name', user.displayName || user.email);
                upsertHiddenInput('firebase_uid', user.uid);

            } else {
                // No user is signed in
                console.log('🔒 No user logged in, showing login panel');
                setAuthView(false);
            }
        });

        // Google Login
        if (googleLoginBtn) {
            googleLoginBtn.addEventListener('click', function() {
                const provider = new firebase.auth.GoogleAuthProvider();
                auth.signInWithPopup(provider)
                    .catch(function(error) {
                        console.error('Google login error:', error);
                        alert('Login gagal: ' + error.message);
                    });
            });
        }

        // Email/Password Login
        if (emailLoginBtn && loginEmail && loginPassword) {
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
        }

        // Form submission handler
        document.addEventListener('DOMContentLoaded', function() {
            const submitBtn = document.getElementById('submitBtn') || document.querySelector('button[type="submit"]');
            document.querySelectorAll('.quick-add-btn').forEach((button) => {
                button.addEventListener('click', function() {
                    const fieldName = this.getAttribute('data-fk-field');
                    if (!fieldName) return;
                    openQuickAddModal(fieldName);
                });
            });

            if (quickAddClose) {
                quickAddClose.addEventListener('click', closeQuickAddModal);
            }
            if (quickAddCancel) {
                quickAddCancel.addEventListener('click', closeQuickAddModal);
            }
            if (quickAddModal) {
                quickAddModal.addEventListener('click', function(event) {
                    if (event.target === quickAddModal) {
                        closeQuickAddModal();
                    }
                });
            }
            if (quickAddForm && quickAddFieldInput) {
                quickAddForm.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const fieldName = quickAddFieldInput.value;
                    if (!fieldName) return;

                    const formData = new FormData(quickAddForm);
                    const payload = {};
                    formData.forEach((value, key) => {
                        if (key !== 'field') {
                            payload[key] = String(value).trim();
                        }
                    });

                    try {
                        const requestBody = new URLSearchParams();
                        requestBody.append('field', fieldName);
                        requestBody.append('payload', JSON.stringify(payload));
                        requestBody.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

                        const response = await fetch(fkQuickAddUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: requestBody.toString()
                        });
                        const result = await response.json();
                        if (!result.success) {
                            alert(result.message || 'Gagal menambah data baru.');
                            return;
                        }

                        await refreshFkOptions(fieldName);
                        const targetSelect = document.querySelector('select[data-fk-field="' + fieldName + '"]');
                        if (targetSelect && result.option && result.option.value !== undefined) {
                            targetSelect.value = String(result.option.value);
                        }
                        closeQuickAddModal();
                    } catch (error) {
                        alert('Gagal menambah data baru.');
                    }
                });
            }

            if (!form || !submitBtn) {
                return;
            }

            function hasAtLeastOneUserInput(formEl) {
                const ignoredNames = new Set([
                    '<?= Yii::$app->request->csrfParam ?>',
                    'user_email',
                    'user_name',
                    'firebase_uid'
                ]);
                const formData = new FormData(formEl);

                for (const [name, value] of formData.entries()) {
                    if (!name || ignoredNames.has(name)) {
                        continue;
                    }

                    const field = formEl.elements.namedItem(name);
                    if (field && !Array.isArray(field) && field.type === 'hidden') {
                        continue;
                    }

                    if (value instanceof File) {
                        if (value.name && value.name.trim() !== '') {
                            return true;
                        }
                        continue;
                    }

                    if (String(value).trim() !== '') {
                        return true;
                    }
                }

                return false;
            }

            form.addEventListener('submit', function(e) {
                if (!hasAtLeastOneUserInput(form)) {
                    e.preventDefault();
                    alert('Form belum diisi. Isi minimal satu field sebelum submit.');
                    return;
                }

                console.log('🚀 Form submit triggered!');
                console.log('📤 Submitting to:', form.action);

                // Disable button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Mengirim...';
                submitBtn.style.opacity = '0.7';
                submitBtn.style.pointerEvents = 'none';
            });

            submitBtn.addEventListener('click', function(e) {
                if (!hasAtLeastOneUserInput(form)) {
                    e.preventDefault();
                    alert('Form belum diisi. Isi minimal satu field sebelum submit.');
                    return;
                }
            });
        });
    </script>
    
