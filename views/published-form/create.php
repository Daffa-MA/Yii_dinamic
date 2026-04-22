<?php

/** @var yii\web\View $this */
/** @var app\models\PublishedForm $model */
/** @var app\models\Form[] $forms */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;

$this->title = 'Publish Form';
$hasAvailableForms = !empty($forms);
$publishResult = Yii::$app->session->getFlash('publishResult');
$this->registerJs("document.body.classList.add('dashboard-main-page', 'font-body', 'text-on-surface'); document.body.style.background = 'linear-gradient(135deg, #f9fafb 0%, #f3f4f6 55%, #ede9fe 100%)';", \yii\web\View::POS_READY);

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

    <nav class="app-shell-nav fixed top-0 right-0 z-50 flex items-center justify-between px-8 h-20 bg-gradient-to-r from-[#ffffff]/80 via-[#f8fafd]/80 to-[#f0f4f9]/80 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]" style="left: var(--app-sidebar-width, 16rem); transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
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

    <main class="app-shell-main pt-6 min-h-screen" style="padding-left: var(--app-sidebar-width, 16rem); transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
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
                        'action' => ['published-form/create'],
                        'method' => 'post',
                        'options' => ['class' => 'space-y-6', 'id' => 'publish-form-create'],
                        'enableClientValidation' => false,
                        'enableAjaxValidation' => false,
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'block text-sm font-semibold text-on-surface mb-2'],
                            'inputOptions' => [
                                'class' => 'w-full px-4 py-3 bg-surface-container-high border border-outline-variant rounded-xl text-sm focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all',
                            ],
                        ],
                    ]) ?>

                    <?= $form->errorSummary($model, [
                        'class' => 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700',
                    ]) ?>

                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'Enter published form name...',
                    ]) ?>

                    <?= $form->field($model, 'form_id')->dropDownList(
                        ArrayHelper::map($forms, 'id', 'name'),
                        [
                            'prompt' => $hasAvailableForms ? 'Select a form to publish...' : 'All forms are already published',
                            'class' => 'w-full px-4 py-3 bg-surface-container-high border border-outline-variant rounded-xl text-sm focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 transition-all',
                        ]
                    ) ?>

                    <?php if (!$hasAvailableForms): ?>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Semua form sudah berstatus <strong>published</strong>. Tidak ada form baru yang bisa dipilih.
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                        <?= Html::a('Cancel', ['published-form/index'], [
                            'class' => 'px-6 py-3 bg-surface-container text-on-surface-variant rounded-xl font-semibold hover:bg-surface-container-high transition-all text-sm no-underline'
                        ]) ?>
                        <?= Html::submitButton('<span class="material-symbols-outlined text-[18px]">public</span> Publish Form', [
                            'type' => 'submit',
                            'class' => 'bg-gradient-to-r from-primary-container to-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm border-0 cursor-pointer',
                            'id' => 'btn-submit-publish-create',
                            'disabled' => !$hasAvailableForms,
                            'name' => 'publish_submit',
                            'value' => '1',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end() ?>
                </div>
            </div>
        </div>
    </main>

    <div id="publish-result-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;max-width:600px;width:90%;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="background:linear-gradient(135deg,#006c49,#00a773);color:#fff;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
                <h3 style="margin:0;font-size:18px;display:flex;align-items:center;gap:8px;">
                    <span class="material-symbols-outlined">public</span>
                    <span>Form Published</span>
                </h3>
                <button type="button" onclick="closePublishResultModal()" style="background:transparent;border:none;color:#fff;cursor:pointer;font-size:24px;padding:4px;">&times;</button>
            </div>
            <div style="padding:24px;">
                <div style="text-align:center;margin-bottom:24px;">
                    <div style="width:64px;height:64px;background:#d1f2eb;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <span class="material-symbols-outlined" style="font-size:36px;color:#198754;">check_circle</span>
                    </div>
                    <h4 style="margin:0 0 8px;font-size:20px;color:#0b1c30;">Form Published Successfully!</h4>
                    <p style="margin:0;font-size:14px;color:#464555;">Your form is now live and accessible via the link below.</p>
                </div>

                <div style="background:#f8fafd;border:2px solid #e8eef7;border-radius:12px;padding:16px;margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#464555;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Public Form Link</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" id="public-link-input-create" readonly value=""
                            style="flex:1;padding:10px 14px;border:1px solid #c7c4d8;border-radius:8px;font-size:13px;background:#fff;color:#0b1c30;font-family:monospace;">
                        <button type="button" onclick="copyPublicLinkCreate(event)"
                            style="padding:10px 16px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;">
                            <span class="material-symbols-outlined" style="font-size:16px;">content_copy</span>
                            Copy
                        </button>
                    </div>
                </div>

                <div style="background:#f8fafd;border:2px solid #e8eef7;border-radius:12px;padding:16px;margin-bottom:20px;text-align:center;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#464555;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">QR Code</label>
                    <div id="qrcode-create" style="display:inline-block;padding:16px;background:#fff;border-radius:12px;border:1px solid #e8eef7;"></div>
                    <p style="margin:12px 0 0;font-size:12px;color:#464555;">Scan this QR code to access the form</p>
                </div>

                <div style="display:flex;gap:12px;">
                    <a href="#" id="open-form-link-create" target="_blank"
                        style="flex:1;padding:12px 24px;background:linear-gradient(135deg,#006c49,#00a773);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;text-align:center;">
                        <span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span>
                        Open Form
                    </a>
                    <button type="button" onclick="closePublishResultModal()"
                        style="flex:1;padding:12px 24px;background:#f0f4f9;border:none;border-radius:12px;font-size:14px;font-weight:600;color:#464555;cursor:pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        const publishFormCreate = document.getElementById('publish-form-create');
        const submitBtnCreate = document.getElementById('btn-submit-publish-create');

        function showPublishResultModal(url) {
            const modal = document.getElementById('publish-result-modal');
            const input = document.getElementById('public-link-input-create');
            const openLink = document.getElementById('open-form-link-create');
            const qrcodeContainer = document.getElementById('qrcode-create');

            input.value = url;
            openLink.href = url;
            qrcodeContainer.innerHTML = '';

            if (typeof QRCode !== 'undefined') {
                new QRCode(qrcodeContainer, {
                    text: url,
                    width: 180,
                    height: 180,
                    colorDark: '#0b1c30',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            modal.style.display = 'flex';
        }

        function closePublishResultModal() {
            document.getElementById('publish-result-modal').style.display = 'none';
        }

        function copyPublicLinkCreate(event) {
            const input = document.getElementById('public-link-input-create');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">check</span> Copied!';
                btn.style.background = 'linear-gradient(135deg,#198754,#20c997)';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = 'linear-gradient(135deg,#4f46e5,#6366f1)';
                }, 2000);
            }).catch(() => {
                alert('Failed to copy link. Please copy manually.');
            });
        }

        function setPublishCreateLoading(isLoading) {
            if (!submitBtnCreate) return;
            submitBtnCreate.disabled = isLoading || <?= $hasAvailableForms ? 'false' : 'true' ?>;
            submitBtnCreate.innerHTML = isLoading
                ? '<span class="material-symbols-outlined text-[18px]">sync</span> Publishing...'
                : '<span class="material-symbols-outlined text-[18px]">public</span> Publish Form';
        }

        function clearPublishErrors() {
            const existingSummary = publishFormCreate ? publishFormCreate.querySelector('.yii-error-summary') : null;
            if (existingSummary) {
                existingSummary.remove();
            }
        }

        function showPublishErrors(message) {
            if (!publishFormCreate) return;
            clearPublishErrors();
            const summary = document.createElement('div');
            summary.className = 'yii-error-summary rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-4';
            summary.innerHTML = '<ul class="mb-0"><li>' + (message || 'Failed to publish form.') + '</li></ul>';
            publishFormCreate.prepend(summary);
        }

        async function submitPublishCreateAjax(event) {
            event.preventDefault();
            if (!publishFormCreate || !submitBtnCreate || submitBtnCreate.disabled) {
                return false;
            }

            clearPublishErrors();
            setPublishCreateLoading(true);

            try {
                const formData = new FormData(publishFormCreate);
                const response = await fetch(publishFormCreate.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams(formData).toString(),
                });

                const data = await response.json();
                if (data && data.success && data.url) {
                    showPublishResultModal(data.url);
                    if (publishFormCreate.reset) {
                        publishFormCreate.reset();
                    }
                    return false;
                }

                showPublishErrors((data && data.message) ? data.message : 'Failed to publish form.');
                return false;
            } catch (error) {
                showPublishErrors('Failed to publish form. Please try again.');
                return false;
            } finally {
                setPublishCreateLoading(false);
            }
        }

        if (publishFormCreate && submitBtnCreate) {
            publishFormCreate.addEventListener('submit', submitPublishCreateAjax);
        }

        const publishResultModal = document.getElementById('publish-result-modal');
        if (publishResultModal) {
            publishResultModal.addEventListener('click', function(event) {
                if (event.target === this) {
                    closePublishResultModal();
                }
            });
        }

        <?php if (!empty($publishResult['url'])): ?>
        showPublishResultModal(<?= json_encode($publishResult['url']) ?>);
        <?php endif; ?>
    </script>
