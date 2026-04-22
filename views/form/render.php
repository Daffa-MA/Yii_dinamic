<?php

/** @var yii\web\View $this */
/** @var app\models\Form $model */
/** @var array $schema */
/** @var array $fkConfig */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;

$this->title = 'Fill Form: ' . $model->name;
$this->registerJs("document.body.classList.add('font-body', 'text-on-surface'); document.body.style.background = '#e5e9f0';", \yii\web\View::POS_READY);
$fkConfig = isset($fkConfig) && is_array($fkConfig) ? $fkConfig : [];
$fkConfigJson = json_encode($fkConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Styles for dashboard layout
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap');
$this->registerJsFile('https://cdn.tailwindcss.com', ['position' => \yii\web\View::POS_HEAD]);
?>

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
                                <?php
                                $fieldName = $field['name'] ?? $field['label'] ?? '';
                                $fieldLabel = $field['label'] ?? $fieldName;
                                $required = !empty($field['required']);
                                $placeholder = $field['placeholder'] ?? $fieldLabel;
                                $options = $required ? 'required' : '';
                                $fkMeta = (is_string($fieldName) && isset($fkConfig[$fieldName]) && is_array($fkConfig[$fieldName])) ? $fkConfig[$fieldName] : null;
                                ?>
                                <div>
                                    <label class="block text-sm font-medium text-on-surface mb-2"><?= Html::encode($fieldLabel) ?><?= $required ? ' <span class="text-error">*</span>' : '' ?></label>

                                    <?php if ($fkMeta !== null): ?>
                                        <?php
                                        $fkOptions = isset($fkMeta['options']) && is_array($fkMeta['options']) ? $fkMeta['options'] : [];
                                        $quickAddFields = isset($fkMeta['quickAddFields']) && is_array($fkMeta['quickAddFields']) ? $fkMeta['quickAddFields'] : [];
                                        ?>
                                        <div class="flex items-center gap-2">
                                            <select name="<?= Html::encode($fieldName) ?>" <?= $options ?>
                                                data-fk-field="<?= Html::encode($fieldName) ?>"
                                                class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container transition-all">
                                                <option value=""><?= Html::encode($placeholder ?: 'Select option...') ?></option>
                                                <?php foreach ($fkOptions as $fkOption): ?>
                                                    <option value="<?= Html::encode((string)($fkOption['value'] ?? '')) ?>">
                                                        <?= Html::encode((string)($fkOption['label'] ?? '')) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (!empty($quickAddFields)): ?>
                                                <button type="button"
                                                    class="quick-add-btn px-4 py-3 rounded-xl border border-outline-variant bg-surface-container hover:bg-surface-container-high text-sm font-bold text-on-surface-variant transition-colors"
                                                    data-fk-field="<?= Html::encode($fieldName) ?>"
                                                    data-fk-label="<?= Html::encode($fieldLabel) ?>"
                                                    title="Tambah Baru">+</button>
                                            <?php endif; ?>
                                        </div>

                                    <?php elseif ($field['type'] === 'text'): ?>
                                        <input type="text" name="<?= Html::encode($fieldName) ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="<?= Html::encode($placeholder) ?>" <?= $options ?>>

                                    <?php elseif ($field['type'] === 'number'): ?>
                                        <input type="number" name="<?= Html::encode($fieldName) ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="<?= Html::encode($placeholder) ?>" <?= $options ?>>

                                    <?php elseif ($field['type'] === 'email'): ?>
                                        <input type="email" name="<?= Html::encode($fieldName) ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="email@example.com" <?= $options ?>>

                                    <?php elseif ($field['type'] === 'textarea'): ?>
                                        <textarea name="<?= Html::encode($fieldName) ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" placeholder="<?= Html::encode($placeholder) ?>" rows="4" <?= $options ?>></textarea>

                                    <?php elseif ($field['type'] === 'date'): ?>
                                        <input type="date" name="<?= Html::encode($fieldName) ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" <?= $options ?>>

                                    <?php elseif ($field['type'] === 'select'): ?>
                                        <select name="<?= Html::encode($fieldName) ?>" class="w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container" <?= $options ?>>
                                            <option value=""><?= Html::encode($placeholder ?: '-- Select --') ?></option>
                                            <?php
                                            $optionsList = [];
                                            if (isset($field['options'])) {
                                                if (is_string($field['options'])) {
                                                    $optionsList = array_filter(array_map('trim', explode("\n", $field['options'])));
                                                } elseif (is_array($field['options'])) {
                                                    $optionsList = $field['options'];
                                                }
                                            }
                                            foreach ($optionsList as $opt):
                                            ?>
                                                <option value="<?= Html::encode(trim($opt)) ?>"><?= Html::encode(trim($opt)) ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                    <?php elseif ($field['type'] === 'checkbox'): ?>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="<?= Html::encode($fieldName) ?>" value="1" class="w-5 h-5 rounded border-outline-variant text-primary-container focus:ring-primary-container/20" <?= $options ?>>
                                            <span class="text-sm font-medium"><?= Html::encode($field['text'] ?? $fieldLabel) ?></span>
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

    <div id="fkQuickAddModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden animate-slide-up">
            <div class="flex items-center justify-between border-b px-6 py-4 bg-surface-container">
                <h3 class="text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-container">add_circle</span>
                    Tambah Data Baru
                </h3>
                <button type="button" id="fkQuickAddClose" class="text-outline hover:text-on-surface text-2xl transition-colors">&times;</button>
            </div>
            <form id="fkQuickAddForm" class="px-6 py-6 space-y-4">
                <input type="hidden" id="fkQuickAddField" name="field" value="">
                <div id="fkQuickAddFields" class="space-y-4"></div>
                <div class="flex items-center justify-end gap-3 border-t pt-6 mt-4">
                    <button type="button" id="fkQuickAddCancel" class="px-5 py-2.5 rounded-xl bg-surface-container text-on-surface-variant font-semibold hover:bg-surface-container-high transition-all">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-container text-white font-bold hover:shadow-lg transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">save</span> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fkConfigMap = <?= $fkConfigJson ?: '{}' ?>;
        const fkQuickAddUrl = <?= json_encode(Url::to(['form/fk-quick-add', 'id' => $model->id])) ?>;
        const fkOptionsUrl = <?= json_encode(Url::to(['form/fk-options', 'id' => $model->id])) ?>;

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
                quickAddFieldsContainer.innerHTML = '<p class="text-sm text-outline">Field tambahan tidak diperlukan.</p>';
                return;
            }

            fields.forEach((item) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'space-y-2';
                const label = document.createElement('label');
                label.className = 'block text-sm font-semibold text-on-surface';
                label.textContent = item.label || item.name;
                const input = document.createElement('input');
                input.type = item.inputType || 'text';
                input.name = item.name;
                input.required = !!item.required;
                input.className = 'w-full px-4 py-3 bg-surface-container border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container/20 focus:border-primary-container transition-all';
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

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.quick-add-btn').forEach((button) => {
                button.addEventListener('click', function() {
                    const fieldName = this.getAttribute('data-fk-field');
                    if (!fieldName) return;
                    openQuickAddModal(fieldName);
                });
            });

            if (quickAddClose) quickAddClose.addEventListener('click', closeQuickAddModal);
            if (quickAddCancel) quickAddCancel.addEventListener('click', closeQuickAddModal);
            if (quickAddModal) {
                quickAddModal.addEventListener('click', function(event) {
                    if (event.target === quickAddModal) closeQuickAddModal();
                });
            }

            if (quickAddForm) {
                quickAddForm.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const fieldName = quickAddFieldInput.value;
                    if (!fieldName) return;

                    const submitBtn = quickAddForm.querySelector('button[type="submit"]');
                    const originalHTML = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '⏳ Menyimpan...';

                    const formData = new FormData(quickAddForm);
                    const payload = {};
                    formData.forEach((value, key) => {
                        if (key !== 'field') payload[key] = String(value).trim();
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
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHTML;
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
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHTML;
                    }
                });
            }
        });
    </script>

    <!-- Notification System -->
    <script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>
