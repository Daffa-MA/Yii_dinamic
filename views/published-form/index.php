<?php

/** @var yii\web\View $this */
/** @var app\models\Form[] $forms */

use yii\bootstrap5\Html;

$this->title = 'Data Form';
$this->registerJs("document.body.classList.add('dashboard-main-page');", \yii\web\View::POS_READY);
$this->registerJs("document.body.classList.add('font-body', 'text-on-surface'); document.body.style.backgroundAttachment = 'fixed'; document.body.style.background = 'linear-gradient(135deg, #f9fafb 0%, #f3f4f6 55%, #ede9fe 100%)';", \yii\web\View::POS_READY);

// Styles for dashboard layout
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap');
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap');

// QR Code library
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', ['position' => \yii\web\View::POS_HEAD]);
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

    /* Line clamp utility for text truncation */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        box-orient: vertical;
        overflow: hidden;
    }
</style>

    <!-- Top Navigation Bar -->
    <nav class="app-shell-nav fixed top-0 right-0 z-50 flex items-center justify-between px-8 h-20 bg-gradient-to-r from-[#ffffff]/80 via-[#f8fafd]/80 to-[#f0f4f9]/80 backdrop-blur-xl shadow-[0_20px_40px_rgba(11,28,48,0.06)]" style="left: var(--app-sidebar-width, 16rem); transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
        <div class="flex items-center gap-6">
            <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-full gap-3 min-w-[320px]">
                <span class="material-symbols-outlined text-outline text-[20px]">search</span>
                <input class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline/60" placeholder="Search published forms..." type="text" />
            </div>
            <?= Html::a('<span class="material-symbols-outlined text-[18px]">folder_open</span> Projects', ['project/index'], [
                'class' => 'text-on-surface-variant hover:text-on-surface px-4 py-2 rounded-lg hover:bg-surface-container-high transition-all flex items-center gap-2 text-sm font-medium no-underline',
                'encode' => false
            ]) ?>
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
    <main class="app-shell-main pt-6 min-h-screen" style="padding-left: var(--app-sidebar-width, 16rem); transition: padding-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">
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
                    <p class="text-on-surface-variant font-medium">Manage all forms and their publish status.</p>
                </div>
                <?= Html::a('<span class="material-symbols-outlined text-[18px]">add</span> Publish New Form', ['published-form/create'], [
                    'class' => 'bg-gradient-to-r from-primary-container to-primary text-white px-6 py-2.5 rounded-xl font-semibold flex items-center gap-2 hover:shadow-lg transition-all active:scale-95 text-sm no-underline'
                ]) ?>
            </div>

            <!-- Search Bar -->
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-md p-6 mb-8 border border-gray-200" style="border-left: 4px solid #4f46e5;">
                <?= Html::beginForm(['published-form/index'], 'get', ['class' => 'flex gap-3']) ?>
                <div class="flex items-center bg-surface-container-high px-4 py-2 rounded-xl gap-3 flex-1">
                    <span class="material-symbols-outlined text-outline">search</span>
                    <?= Html::input('text', 'q', $search ?? null, [
                        'class' => 'bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-outline',
                        'placeholder' => 'Search forms by form name or published name...',
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

            <?php if (empty($forms)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-violet-300 shadow-sm">
                    <div class="w-20 h-20 bg-gradient-to-br from-violet-100 to-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-md">
                        <span class="material-symbols-outlined text-5xl text-violet-600">public</span>
                    </div>
                    <h3 class="text-2xl font-extrabold mb-2 text-gray-900">No forms yet</h3>
                    <p class="text-gray-600 mb-6 font-medium">Create a form to get started</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($forms as $index => $form): ?>
                        <?php
                        $publishedForm = null;
                        if (!empty($form->publishedForms)) {
                            $publishedForm = $form->publishedForms[0];
                        }
                        $isPublished = $publishedForm !== null;

                        // Color themes for cards
                        $colorAccent = ['#4f46e5', '#06b6d4', '#8b5cf6', '#f97316', '#10b981'][$index % 5];
                        $colorBg = ['bg-violet-50', 'bg-cyan-50', 'bg-purple-50', 'bg-orange-50', 'bg-emerald-50'][$index % 5];
                        ?>
                        <div class="group bg-white hover:shadow-xl transition-all duration-300 rounded-2xl overflow-hidden border border-gray-200 hover:border-primary-container/30"
                            style="border-left: 5px solid <?= $colorAccent ?>;">
                            <!-- Header with icon and status -->
                            <div class="<?= $colorBg ?> px-6 py-5 border-b border-gray-100">
                                <div class="flex justify-between items-start">
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-container/10 to-primary/10 rounded-xl flex items-center justify-center border border-primary-container/20 shadow-sm group-hover:scale-110 transition-transform duration-300">
                                        <span class="material-symbols-outlined text-primary-container text-2xl">public</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 bg-white/80 px-2.5 py-1 rounded-full shadow-sm">
                                        <span class="w-2 h-2 rounded-full <?= $isPublished ? 'bg-secondary animate-pulse' : 'bg-outline' ?>"></span>
                                        <span class="text-[10px] font-bold uppercase tracking-tight <?= $isPublished ? 'text-secondary' : 'text-outline' ?>">
                                            <?= $isPublished ? 'Published' : 'Draft' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-6">
                                <h3 class="text-lg font-bold mb-2 text-on-surface line-clamp-2 group-hover:text-primary-container transition-colors">
                                    <?= Html::encode($isPublished ? $publishedForm->name : $form->name) ?>
                                </h3>
                                <p class="text-xs text-on-surface-variant font-medium mb-4">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[14px] text-outline">description</span>
                                        <?= Html::encode($form->name) ?>
                                    </span>
                                </p>

                                <!-- URL Slug -->
                                <div class="bg-surface-container rounded-lg px-4 py-3 mb-4">
                                    <p class="text-[10px] text-outline font-bold uppercase tracking-wider mb-1">URL Slug</p>
                                    <code class="text-xs font-mono text-primary-container"><?= Html::encode($isPublished ? $publishedForm->slug : '-') ?></code>
                                </div>

                                <!-- Created Date -->
                                <p class="text-xs text-on-surface-variant mb-6">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[14px] text-outline">calendar_today</span>
                                        <?= Yii::$app->formatter->asDate($isPublished ? $publishedForm->created_at : $form->created_at) ?>
                                    </span>
                                </p>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <?= Html::a('<span class="material-symbols-outlined text-[16px]">list_alt</span>', ['form/submissions', 'id' => $form->id], [
                                        'class' => 'w-9 h-9 flex items-center justify-center bg-primary-container text-white rounded-lg hover:bg-primary-container/90 transition-all no-underline text-xs',
                                        'title' => 'View Submissions / Jawaban User',
                                    ]) ?>
                                    <?= Html::a('<span class="material-symbols-outlined text-[16px]">open_in_new</span>', ['form/public-render', 'id' => $form->id], [
                                        'class' => 'w-9 h-9 flex items-center justify-center bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-all no-underline text-xs',
                                        'title' => 'View Public Form',
                                        'target' => '_blank'
                                    ]) ?>
                                    <?php if ($isPublished): ?>
                                        <button onclick="showPublicUrlModal(<?= $publishedForm->id ?>)"
                                            class="w-9 h-9 flex items-center justify-center bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container hover:bg-primary-container/5 transition-all text-xs text-on-surface-variant"
                                            title="Copy Public URL">
                                            <span class="material-symbols-outlined text-[16px]">link</span>
                                        </button>
                                        <?= Html::a('<span class="material-symbols-outlined text-[16px]">edit</span>', ['published-form/update', 'id' => $publishedForm->id], [
                                            'class' => 'w-9 h-9 flex items-center justify-center bg-white border border-outline-variant rounded-lg hover:border-primary-container hover:text-primary-container hover:bg-primary-container/5 transition-all no-underline text-xs text-on-surface-variant',
                                            'title' => 'Edit Settings'
                                        ]) ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[16px]">delete</span>', ['published-form/delete', 'id' => $publishedForm->id], [
                                            'class' => 'w-9 h-9 flex items-center justify-center bg-white border border-outline-variant rounded-lg hover:border-error hover:text-error hover:bg-error/5 transition-all no-underline text-xs text-on-surface-variant',
                                            'title' => 'Unpublish',
                                            'data' => [
                                                'confirm' => 'Are you sure you want to unpublish this form? The public link will no longer work.',
                                                'method' => 'post',
                                            ]
                                        ]) ?>
                                    <?php else: ?>
                                        <?= Html::a('<span class="material-symbols-outlined text-[16px]">publish</span>', ['published-form/create', 'form_id' => $form->id], [
                                            'class' => 'h-9 px-3 inline-flex items-center justify-center bg-white border border-outline-variant rounded-lg hover:border-secondary hover:text-secondary hover:bg-secondary/5 transition-all no-underline text-xs text-on-surface-variant font-semibold',
                                            'title' => 'Publish Form'
                                        ]) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Public URL Modal -->
    <div id="publicUrlModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closePublicUrlModal()"></div>

        <!-- Modal Panel -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-secondary to-green-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-white text-2xl">public</span>
                        <h3 class="text-xl font-bold text-white" id="modal-title">Form Published</h3>
                    </div>
                    <button onclick="closePublicUrlModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <span class="material-symbols-outlined text-2xl">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-8">
                    <!-- Success Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-600 text-5xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div class="text-center mb-8">
                        <h4 class="text-2xl font-bold text-gray-900 mb-2">Form Published Successfully!</h4>
                        <p class="text-gray-600 text-sm">Your form is now live and accessible via the link below.</p>
                    </div>

                    <!-- Public URL Section -->
                    <div class="mb-6">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Public Form Link</label>
                        <div class="flex gap-2">
                            <input type="text" id="publicUrlInput" readonly
                                class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-container"
                                value="Loading...">
                            <button onclick="copyPublicUrl()" id="copyUrlBtn"
                                class="px-6 py-3 bg-primary-container text-white rounded-xl font-semibold hover:bg-primary transition-all flex items-center gap-2 whitespace-nowrap">
                                <span class="material-symbols-outlined text-lg">content_copy</span>
                                <span>Copy</span>
                            </button>
                        </div>
                    </div>

                    <!-- QR Code Section -->
                    <div class="bg-gray-50 rounded-2xl p-6 text-center">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">QR Code</label>
                        <div class="inline-block bg-white p-4 rounded-xl shadow-md">
                            <div id="qrcode" class="flex items-center justify-center"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">Scan this QR code to access the form</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 flex gap-3">
                    <a href="#" id="openFormBtn" target="_blank"
                        class="flex-1 bg-secondary text-white px-6 py-3 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-secondary/90 transition-all no-underline">
                        <span class="material-symbols-outlined text-lg">open_in_new</span>
                        Open Form
                    </a>
                    <button onclick="closePublicUrlModal()"
                        class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPublicUrl = '';

        function showPublicUrlModal(publishedFormId) {
            // Show modal
            document.getElementById('publicUrlModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Reset QR code
            document.getElementById('qrcode').innerHTML = '';

            // Fetch public URL from server
            fetch('<?= \yii\helpers\Url::to(['published-form/get-public-url', 'id' => '__ID__']) ?>'.replace('__ID__', publishedFormId))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentPublicUrl = data.url;

                        // Update URL input
                        document.getElementById('publicUrlInput').value = data.url;

                        // Update Open Form button
                        document.getElementById('openFormBtn').href = data.url;

                        // Generate QR code
                        if (typeof QRCode !== 'undefined') {
                            new QRCode(document.getElementById('qrcode'), {
                                text: data.url,
                                width: 180,
                                height: 180,
                                colorDark: '#0b1c30',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        }
                    } else {
                        alert('Failed to get public URL');
                        closePublicUrlModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching public URL');
                    closePublicUrlModal();
                });
        }

        function closePublicUrlModal() {
            document.getElementById('publicUrlModal').classList.add('hidden');
            document.body.style.overflow = '';
            currentPublicUrl = '';
        }

        function copyPublicUrl() {
            if (!currentPublicUrl) return;

            navigator.clipboard.writeText(currentPublicUrl).then(() => {
                // Show success feedback
                const btn = document.getElementById('copyUrlBtn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-outlined text-lg">check</span><span>Copied!</span>';
                btn.classList.add('bg-green-600');
                btn.classList.remove('bg-primary-container');

                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-primary-container');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy URL');
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePublicUrlModal();
            }
        });
    </script>
