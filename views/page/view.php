<?php

use app\models\Form;
use app\models\MasterPage;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\services\DynamicFormPreviewService;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $page MasterPage */
/* @var $forms Form[] */

$this->title = $page->title;
$returnUrl = Url::to(['/page/view', 'id' => $page->id]);
$activeProjectId = (new ActiveProjectContext())->getActiveProjectId();
$activeProject = $activeProjectId !== null ? (new ActiveProjectContext())->getActiveProject() : null;
$projectAuthUser = $activeProjectId !== null ? (new ProjectAuthContext())->getAuthenticatedUser($activeProjectId) : null;
$activeMenuId = (int) Yii::$app->session->get('active_menu', 0);
$isCommanderSuperAdmin = (new CommanderAuthContext())->isSuperAdmin();
$workspaceRole = $projectAuthUser !== null ? strtolower(trim((string)$projectAuthUser->role)) : '';
$isWorkspaceAdmin = $isCommanderSuperAdmin || $workspaceRole === 'admin';
$displayUsername = $isCommanderSuperAdmin
    ? 'Superadmin'
    : trim((string)($projectAuthUser->username ?? $projectAuthUser->name ?? 'Pengguna'));
$displayRole = $isCommanderSuperAdmin ? 'Superadmin' : ($workspaceRole !== '' ? ucfirst($workspaceRole) : 'User');
$workspaceName = $activeProject !== null ? (string)$activeProject->name : 'Workspace';
$emptyStateTitle = $isWorkspaceAdmin ? 'Belum ada konten' : 'Belum ada informasi tersedia';
$emptyStateDescription = $isWorkspaceAdmin
    ? 'Halaman ini siap digunakan tetapi belum memiliki konten. Tambahkan konten melalui Master Halaman.'
    : 'Konten untuk halaman ini belum tersedia atau belum dipublikasikan. Silakan hubungi admin workspace apabila Anda merasa seharusnya memiliki akses.';

$layoutClasses = 'grid gap-5';
if ($page->layout_type === MasterPage::LAYOUT_DASHBOARD) {
    $layoutClasses = 'grid gap-5 xl:grid-cols-2';
} elseif ($page->layout_type === MasterPage::LAYOUT_TWO_COLUMN) {
    $layoutClasses = 'grid gap-5 lg:grid-cols-2';
} elseif ($page->layout_type === MasterPage::LAYOUT_FORM) {
    $layoutClasses = 'grid gap-5';
} elseif ($page->layout_type === MasterPage::LAYOUT_LIST) {
    $layoutClasses = 'space-y-5';
} elseif ($page->layout_type === MasterPage::LAYOUT_BLANK) {
    $layoutClasses = 'space-y-4';
}
?>

<div class="mx-auto max-w-7xl px-4 py-8">
    <div class="mb-6 overflow-hidden rounded-[28px] border border-slate-200 <?= $isWorkspaceAdmin ? 'bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.14),_transparent_36%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)]' : 'bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)]' ?> p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)] md:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <?php if ($isWorkspaceAdmin): ?>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-indigo-700">
                        <span class="material-symbols-outlined text-base">dashboard_customize</span>
                        Dynamic Page
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl"><?= Html::encode($page->title) ?></h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600"><?= $page->description !== null && $page->description !== '' ? Html::encode($page->description) : 'Halaman dinamis yang dibangun menggunakan page builder.' ?></p>
                <?php else: ?>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                        <span class="material-symbols-outlined text-base">waving_hand</span>
                        <?= Html::encode($workspaceName) ?>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Halo, <?= Html::encode($displayUsername) ?></h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                        Selamat datang di halaman <?= Html::encode($page->title) ?>. Silakan gunakan halaman ini sesuai kebutuhan Anda.
                    </p>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Akses informasi dan fitur yang tersedia untuk role Anda.</p>
                <?php endif; ?>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[360px]">
                <?php if ($isWorkspaceAdmin): ?>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Layout</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900"><?= Html::encode($page->layout_type) ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Form</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900"><?= count($forms) ?> item</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status</p>
                        <p class="mt-2">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $page->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $page->isActive() ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Role</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900"><?= Html::encode($displayRole) ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Workspace</p>
                        <p class="mt-2 truncate text-sm font-semibold text-slate-900"><?= Html::encode($workspaceName) ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-4 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Status</p>
                        <p class="mt-2">
                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php if ($isWorkspaceAdmin && count($forms) > 1): ?>
            <div class="mt-5 flex flex-wrap gap-2">
                <?php foreach ($forms as $index => $formModel): ?>
                    <a href="#dynamic-form-card-<?= (int) $formModel->id ?>" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 no-underline transition hover:border-indigo-300 hover:text-indigo-700">
                        <span class="material-symbols-outlined text-sm">description</span>
                        <?= Html::encode($formModel->name ?: ('Form ' . ($index + 1))) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php
    // Render priority:
    // 1) Full custom page source (custom_html/custom_css/custom_js)
    // 2) Builder layout_json
    // 3) Forms / empty-state
    $customHtml = trim((string) ($page->custom_html ?? ''));
    $customCss = (string) ($page->custom_css ?? '');
    $customJs = (string) ($page->custom_js ?? '');
    $hasCustomPageSource = $customHtml !== '';
    $customSourceDoc = '';

    if ($hasCustomPageSource) {
        $startsWithHtmlDoc = preg_match('/^\s*(<!doctype html|<html)\b/i', $customHtml) === 1;
        if ($startsWithHtmlDoc) {
            $customSourceDoc = $customHtml;
        } else {
            $customSourceDoc = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\" />\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />\n<style>{$customCss}</style>\n</head>\n<body>\n{$customHtml}\n<script>{$customJs}</script>\n</body>\n</html>";
        }
    }

    $layoutJson = $page->layout_json ?? '[]';
    $layoutData = json_decode($layoutJson, true);
    $hasBuilderContent = !empty($layoutData) && is_array($layoutData);
    ?>

    <?php if ($hasCustomPageSource): ?>
        <div class="rounded-[28px] border border-slate-200 bg-white p-2 shadow-sm overflow-hidden">
            <iframe
                srcdoc="<?= Html::encode($customSourceDoc) ?>"
                class="block w-full border-0 bg-white"
                title="Custom Page Source"
                style="min-height: 780px;"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
            ></iframe>
        </div>
    <?php endif; ?>
    
    <?php if (!$hasCustomPageSource && $hasBuilderContent): ?>
        <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-slate-900">Konten Halaman</h2>
            <?php foreach ($layoutData as $item): ?>
                <?php
                $type = $item['type'] ?? '';
                $props = $item['props'] ?? [];
                
                // Render based on type - matching dynamic builder
                switch ($type) {
                    case 'heading':
                        $level = $props['level'] ?? 'h2';
                        $text = $props['text'] ?? '';
                        $align = $props['align'] ?? 'left';
                        $fontSize = $props['fontSize'] ?? '24';
                        $color = $props['color'] ?? '#1e293b';
                        echo "<{$level} style='text-align:{$align};font-size:{$fontSize}px;font-weight:700;color:{$color};margin:1rem 0;'>{$text}</{$level}>";
                        break;
                        
                    case 'text':
                        $content = $props['content'] ?? '';
                        $align = $props['align'] ?? 'left';
                        $fontSize = $props['fontSize'] ?? '15';
                        $lineHeight = $props['lineHeight'] ?? '1.6';
                        $color = $props['color'] ?? '#475569';
                        echo "<div style='font-size:{$fontSize}px;line-height:{$lineHeight};color:{$color};text-align:{$align};margin:0.5rem 0;'>{$content}</div>";
                        break;
                        
                    case 'button':
                        $text = $props['text'] ?? 'Button';
                        $url = $props['url'] ?? '#';
                        $style = $props['style'] ?? 'primary';
                        $size = $props['size'] ?? 'md';
                        $align = $props['align'] ?? 'center';
                        $colors = ['primary' => '#4f46e5', 'secondary' => '#4b5563', 'outline' => '#4f46e5', 'ghost' => '#4f46e5'];
                        $bgColor = isset($colors[$style]) ? $colors[$style] : '#4f46e5';
                        $isOutline = ($style === 'outline');
                        $padding = $size === 'lg' ? '12px 32px' : ($size === 'sm' ? '8px 16px' : '10px 24px');
                        echo "<div style='text-align:{$align};margin:1rem 0;'>";
                        echo "<a href='{$url}' style='display:inline-block;padding:{$padding};border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;";
                        if ($isOutline) {
                            echo "border:2px solid {$bgColor};color:{$bgColor};background:transparent;";
                        } else {
                            echo "background-color:{$bgColor};color:white;";
                        }
                        echo "'>{$text}</a>";
                        echo "</div>";
                        break;
                        
                    case 'image':
                        $src = $props['src'] ?? '';
                        $alt = $props['alt'] ?? '';
                        $width = $props['width'] ?? '100';
                        $borderRadius = $props['borderRadius'] ?? '8';
                        $align = $props['align'] ?? 'center';
                        if ($src) {
                            echo "<div style='text-align:{$align};margin:1rem 0;'>";
                            echo "<img src='{$src}' alt='{$alt}' style='width:{$width}%;border-radius:{$borderRadius}px;max-width:100%;height:auto;' />";
                            echo "</div>";
                        }
                        break;
                        
                    case 'spacer':
                        $height = $props['height'] ?? '32';
                        echo "<div style='height:{$height}px;'></div>";
                        break;
                        
                    case 'divider':
                        $color = $props['color'] ?? '#e2e8f0';
                        $thickness = $props['thickness'] ?? '2';
                        $margin = $props['margin'] ?? '16';
                        echo "<hr style='border:none;border-top:{$thickness}px solid {$color};margin:{$margin}px 0;' />";
                        break;
                        
                    case 'card':
                        $title = $props['title'] ?? '';
                        $content = $props['content'] ?? '';
                        $bgColor = $props['bgColor'] ?? '#ffffff';
                        $padding = $props['padding'] ?? '20';
                        $showShadow = $props['showShadow'] ?? true;
                        $shadow = $showShadow ? 'box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);' : '';
                        echo "<div style='border-radius:12px;padding:{$padding}px;background:{$bgColor};border:1px solid #e2e8f0;{$shadow}margin:0.5rem 0;'>";
                        if ($title) echo "<h4 style='margin:0 0 8px;font-weight:700;font-size:16px;color:#1e293b;'>{$title}</h4>";
                        if ($content) echo "<p style='margin:0;color:#64748b;font-size:14px;'>{$content}</p>";
                        echo "</div>";
                        break;
                        
                    case 'form':
                        $formId = isset($props['formId']) ? (int)$props['formId'] : null;
                        $showTitle = !empty($props['showTitle']);
                        $previewService = new DynamicFormPreviewService();
                        echo "<div style='margin:1rem 0;'>" . $previewService->renderByScopedId($formId, $showTitle, true, [
                            'render_context' => 'page_content',
                            'page_id' => (int)$page->id,
                            'menu_id' => $activeMenuId,
                        ]) . "</div>";
                        break;
                        
                    case 'video':
                        $url = $props['url'] ?? '';
                        $width = $props['width'] ?? '100';
                        $aspectRatio = $props['aspectRatio'] ?? '16/9';
                        echo "<div style='width:{$width}%;aspect-ratio:{$aspectRatio};background:#000;border-radius:12px;margin:1rem 0;display:flex;align-items:center;justify-content:center;color:white;'>";
                        if ($url) {
                            echo "▶ Video: {$url}";
                        } else {
                            echo "Masukkan URL video";
                        }
                        echo "</div>";
                        break;
                        
                    case 'grid':
                        $columns = $props['columns'] ?? 3;
                        $gap = $props['gap'] ?? '16';
                        $padding = $props['padding'] ?? '20';
                        echo "<div style='display:grid;grid-template-columns:repeat({$columns},1fr);gap:{$gap}px;padding:{$padding}px;background:#f8fafc;border-radius:8px;margin:1rem 0;'>";
                        for ($i = 0; $i < $columns; $i++) {
                            echo "<div style='padding:30px;background:white;border:2px dashed #e2e8f0;border-radius:8px;text-align:center;color:#94a3b8;'>Kolom " . ($i + 1) . "</div>";
                        }
                        echo "</div>";
                        break;
                        
                    case 'section':
                        $background = $props['background'] ?? '#ffffff';
                        $padding = $props['padding'] ?? '40';
                        $margin = $props['margin'] ?? '0';
                        echo "<div style='padding:{$padding}px;margin:{$margin}px;background:{$background};border-radius:8px;border:1px dashed #cbd5e1;margin:1rem 0;'>";
                        echo "<span style='color:#94a3b8;'>📦 Section</span>";
                        echo "</div>";
                        break;
                        
                    default:
                        echo "<!-- Unknown type: {$type} -->";
                }
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($forms) && !$hasCustomPageSource && !$hasBuilderContent): ?>
        <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-10 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 text-slate-500 shadow-sm">
                <span class="material-symbols-outlined text-[22px]">inventory_2</span>
            </div>
            <h2 class="mt-4 text-xl font-bold text-slate-900"><?= Html::encode($emptyStateTitle) ?></h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600"><?= Html::encode($emptyStateDescription) ?></p>
            <?php if ($isWorkspaceAdmin): ?>
                <div class="mt-5">
                    <?= Html::a('Buka Master Halaman', ['/master-page/update', 'id' => $page->id], [
                        'class' => 'inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white no-underline transition hover:bg-slate-800',
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (count($forms) > 0): ?>
        <div class="<?= $layoutClasses ?>">
            <?php foreach ($forms as $index => $formModel): ?>
                <?php
                $embedUrl = Url::to([
                    '/form/render',
                    'id' => $formModel->id,
                    'embedded' => 1,
                    'render_context' => 'page_content',
                    'page_id' => (int)$page->id,
                    'menu_id' => $activeMenuId,
                    'return_url' => $returnUrl,
                ]);
                $schemaCount = count($formModel->getSchema());
                $storageLabel = $formModel->storage_type === 'database' ? 'Database' : 'JSON';
                ?>
                <section id="dynamic-form-card-<?= (int) $formModel->id ?>" class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
                    <div class="border-b border-slate-100 bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div class="flex min-w-0 items-start gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                    <span class="material-symbols-outlined">description</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="truncate text-lg font-bold text-slate-900"><?= Html::encode($formModel->name) ?></h2>
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-700"><?= Html::encode($storageLabel) ?></span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">Form ke-<?= $index + 1 ?> di halaman ini. Submit akan kembali ke halaman ini agar admin tetap di konteks yang sama.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs text-slate-500 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-center">
                                    <div class="font-semibold text-slate-900">#<?= (int) $formModel->id ?></div>
                                    <div>Form ID</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-center">
                                    <div class="font-semibold text-slate-900"><?= $schemaCount ?></div>
                                    <div>Field</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-center col-span-2 sm:col-span-1">
                                    <a href="<?= Html::encode(Url::to(['/form/view', 'id' => $formModel->id])) ?>" class="font-semibold text-indigo-700 no-underline hover:underline">Detail Form</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50">
                        <iframe
                            src="<?= Html::encode($embedUrl) ?>"
                            class="dynamic-form-iframe block w-full border-0 bg-white"
                            data-dynamic-form-iframe
                            data-form-id="<?= (int) $formModel->id ?>"
                            title="<?= Html::encode($formModel->name) ?>"
                            loading="lazy"
                            style="min-height: 780px;"
                        ></iframe>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$this->registerJs(<<<'JS'
(function() {
    function bindEmbeddedFormSubmit(form) {
        if (!form || form.dataset.bound === '1') return;
        form.dataset.bound = '1';

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageBox = form.querySelector('.dynamic-form-submit-message');
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then((res) => res.text())
            .then((raw) => {
                let data = null;
                try { data = JSON.parse(raw); } catch (e) { data = null; }
                if (!messageBox) return;
                messageBox.style.display = 'block';
                if (data && data.success) {
                    messageBox.style.background = '#ecfdf5';
                    messageBox.style.border = '1px solid #86efac';
                    messageBox.style.color = '#166534';
                    messageBox.textContent = data.message || 'Data berhasil dikirim.';
                    form.reset();
                } else {
                    messageBox.style.background = '#fef2f2';
                    messageBox.style.border = '1px solid #fecaca';
                    messageBox.style.color = '#991b1b';
                    messageBox.textContent = (data && data.message) ? data.message : 'Gagal mengirim data.';
                }
            })
            .catch(() => {
                if (!messageBox) return;
                messageBox.style.display = 'block';
                messageBox.style.background = '#fef2f2';
                messageBox.style.border = '1px solid #fecaca';
                messageBox.style.color = '#991b1b';
                messageBox.textContent = 'Gagal mengirim data.';
            })
            .finally(() => {
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }

    document.querySelectorAll('form.dynamic-embedded-form').forEach(bindEmbeddedFormSubmit);
})();
JS);
?>

<?php
$iframeResizeScript = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    const resizeIframe = (iframe) => {
        if (!iframe || !iframe.contentWindow) {
            return;
        }

        try {
            const doc = iframe.contentWindow.document;
            if (!doc || !doc.body || !doc.documentElement) {
                return;
            }

            const bodyHeight = doc.body.scrollHeight || 0;
            const documentHeight = doc.documentElement.scrollHeight || 0;
            iframe.style.height = Math.max(bodyHeight, documentHeight, 780) + 'px';
        } catch (error) {
            iframe.style.height = '980px';
        }
    };

    document.querySelectorAll('[data-dynamic-form-iframe]').forEach((iframe) => {
        iframe.addEventListener('load', function () {
            resizeIframe(iframe);
            setTimeout(() => resizeIframe(iframe), 250);
            setTimeout(() => resizeIframe(iframe), 1000);
        });
    });
});
JS;
$this->registerJs($iframeResizeScript, \yii\web\View::POS_END);
?>
