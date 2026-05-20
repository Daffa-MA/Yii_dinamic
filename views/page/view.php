<?php

use app\models\Form;
use app\models\MasterPage;
use app\models\MasterForm;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\services\DynamicFormPreviewService;
use app\services\FormRenderService;
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
$emptyStateTitle = $isWorkspaceAdmin ? 'Belum ada konten' : 'Informasi belum tersedia';
$emptyStateDescription = $isWorkspaceAdmin
    ? 'Halaman ini siap digunakan tetapi belum memiliki konten. Tambahkan konten melalui Master Halaman.'
    : 'Halaman ini belum memiliki konten untuk ditampilkan. Silakan hubungi admin workspace jika halaman ini seharusnya berisi informasi.';

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

$useCustomPageSource = !empty($page->use_page_custom_code) || (($page->page_type ?? MasterPage::PAGE_TYPE_BUILDER) === MasterPage::PAGE_TYPE_CUSTOM_CODE);
$customHtml = trim((string) (($page->page_custom_html ?? '') !== '' ? $page->page_custom_html : ($page->custom_html ?? '')));
$customCss = (string) (($page->page_custom_css ?? '') !== '' ? $page->page_custom_css : ($page->custom_css ?? ''));
$customJs = (string) (($page->page_custom_js ?? '') !== '' ? $page->page_custom_js : ($page->custom_js ?? ''));
$hasCustomPageSource = $useCustomPageSource && ($customHtml !== '' || $customCss !== '' || $customJs !== '');
$customSourceDoc = '';

if ($hasCustomPageSource) {
    $injectLinkHandler = static function (string $source): string {
        $script = <<<'HTML'
<script>
(function() {
    function isExternalUrl(url) {
        return /^(https?:|mailto:|tel:)/i.test(String(url || '').trim());
    }

    function shouldHandle(url) {
        return !!url && !/^(#|javascript:)/i.test(url);
    }

    function openLink(url, target) {
        if (!shouldHandle(url)) {
            return;
        }

        try {
            if (isExternalUrl(url)) {
                window.open(url, '_blank', 'noopener,noreferrer');
                return;
            }

            if (target && target !== '_self') {
                window.open(url, target === '_blank' ? '_blank' : target, 'noopener,noreferrer');
                return;
            }

            window.location.href = url;
        } catch (e) {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    }

    function decorateExternalLinks(root) {
        var links = (root || document).querySelectorAll('a[href]');
        links.forEach(function(link) {
            var href = link.getAttribute('href') || '';
            if (!isExternalUrl(href)) {
                return;
            }

            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    }

    document.addEventListener('click', function(event) {
        var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
        if (!link) {
            return;
        }

        var href = link.getAttribute('href') || '';
        if (!shouldHandle(href)) {
            return;
        }

        var target = (link.getAttribute('target') || '').toLowerCase();
        if (isExternalUrl(href)) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
            event.preventDefault();
            event.stopPropagation();
            openLink(href, '_blank');
            return;
        }

        if (target && target !== '_self') {
            event.preventDefault();
            event.stopPropagation();
            openLink(href, target);
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            decorateExternalLinks(document);
        });
    } else {
        decorateExternalLinks(document);
    }

    if (window.MutationObserver) {
        var observer = new MutationObserver(function() {
            decorateExternalLinks(document);
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
</script>
HTML;

        if (stripos($source, '</body>') !== false) {
            return preg_replace('~</body>~i', $script . "\n</body>", $source, 1) ?? ($source . $script);
        }

        return $source . $script;
    };
    $previewService = new DynamicFormPreviewService();
    $renderedFormIds = [];
    try {
        $customHtml = preg_replace_callback('/\{\{\s*form\s*:\s*(\d+)\s*\}\}/i', static function (array $matches) use ($previewService, $page, $activeMenuId, &$renderedFormIds): string {
            $formId = (int)$matches[1];
            $renderedFormIds[] = $formId;
            try {
                return $previewService->renderByScopedId($formId, true, true, [
                    'render_context' => 'page_content',
                    'page_id' => (int)$page->id,
                    'menu_id' => $activeMenuId,
                ]);
            } catch (\Throwable $e) {
                Yii::warning('Failed to render embedded form on page view: ' . $e->getMessage(), 'app');
                return '<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Form tidak dapat ditampilkan.</div>';
            }
        }, $customHtml) ?? $customHtml;
    } catch (\Throwable $e) {
        Yii::warning('Failed to expand custom page tokens on page view: ' . $e->getMessage(), 'app');
    }

    if (stripos($customHtml, '<form') !== false) {
        $fallbackFormId = $renderedFormIds[0] ?? 0;
        if ($fallbackFormId <= 0) {
            $fallbackForm = MasterForm::find()
                ->where(['page_id' => (int)$page->id])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            $fallbackFormId = $fallbackForm ? (int)$fallbackForm->id : 0;
        }

        if ($fallbackFormId > 0) {
            try {
                $customHtml = FormRenderService::prepareCustomFormSubmission($customHtml, $fallbackFormId, [
                    '_embedded' => '1',
                    'render_context' => 'page_content',
                    'page_id' => (string)(int)$page->id,
                    'menu_id' => $activeMenuId > 0 ? (string)$activeMenuId : '',
                    'project_id' => $activeProjectId !== null ? (string)$activeProjectId : '',
                    'workspace_role' => $workspaceRole,
                ]);
            } catch (\Throwable $e) {
                Yii::warning('Failed to prepare custom form submission on page view: ' . $e->getMessage(), 'app');
            }
        }
    }

    $startsWithHtmlDoc = false;
    try {
        $startsWithHtmlDoc = preg_match('/^\s*(<!doctype html|<html)\b/i', $customHtml) === 1;
    } catch (\Throwable $e) {
        Yii::warning('Failed to detect custom page document on page view: ' . $e->getMessage(), 'app');
    }
    if ($startsWithHtmlDoc) {
        $customSourceDoc = $customHtml;
    } else {
        $customSourceDoc = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\" />\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />\n<style>{$customCss}</style>\n</head>\n<body>\n{$customHtml}\n<script>{$customJs}</script>\n</body>\n</html>";
    }

    $customSourceDoc = preg_replace(
        '/<head\b[^>]*>/i',
        '$0<base href="' . Html::encode(Yii::$app->request->hostInfo) . '/">',
        $customSourceDoc,
        1
    ) ?? $customSourceDoc;
    $customSourceDoc = $injectLinkHandler($customSourceDoc);
}

$layoutJson = $page->layout_json ?? '[]';
$layoutData = json_decode($layoutJson, true);
$hasBuilderContent = !empty($layoutData) && is_array($layoutData);

if ($hasCustomPageSource): ?>
    <div class="rounded-[28px] border border-slate-200 bg-white p-2 shadow-sm overflow-hidden">
        <iframe
            srcdoc="<?= Html::encode($customSourceDoc) ?>"
            data-custom-page-source-iframe
            class="block w-full border-0 bg-white"
            title="Custom Page Source"
            style="min-height: 780px;"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"
        ></iframe>
    </div>
    <script>
        (function() {
            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function showSubmitToast(type, message) {
                var existing = document.getElementById('page-submit-toast');
                if (existing) existing.remove();

                var isSuccess = type === 'success';
                var toast = document.createElement('div');
                toast.id = 'page-submit-toast';
                toast.setAttribute('role', 'status');
                toast.style.cssText = [
                    'position:fixed',
                    'top:22px',
                    'right:22px',
                    'z-index:2147483647',
                    'width:min(420px,calc(100vw - 32px))',
                    'background:#ffffff',
                    'color:#0f172a',
                    'border:1px solid ' + (isSuccess ? '#bbf7d0' : '#fecaca'),
                    'border-left:5px solid ' + (isSuccess ? '#22c55e' : '#ef4444'),
                    'border-radius:14px',
                    'box-shadow:0 24px 60px rgba(15,23,42,.22)',
                    'font-family:Inter,Segoe UI,Arial,sans-serif',
                    'overflow:hidden',
                    'transform:translateY(-8px)',
                    'opacity:0',
                    'transition:opacity .18s ease, transform .18s ease'
                ].join(';');

                toast.innerHTML =
                    '<div style="display:flex;gap:12px;align-items:flex-start;padding:16px 18px;">' +
                        '<div style="width:34px;height:34px;border-radius:999px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:' + (isSuccess ? '#dcfce7;color:#15803d' : '#fee2e2;color:#b91c1c') + ';font-weight:800;font-size:18px;">' + (isSuccess ? '&#10003;' : '!') + '</div>' +
                        '<div style="min-width:0;flex:1;">' +
                            '<div style="font-size:15px;font-weight:800;margin-bottom:3px;">' + (isSuccess ? 'Data berhasil dikirim' : 'Gagal mengirim data') + '</div>' +
                            '<div style="font-size:13px;line-height:1.5;color:#475569;">' + escapeHtml(message || (isSuccess ? 'Terima kasih, data sudah tersimpan.' : 'Silakan periksa kembali isian form.')) + '</div>' +
                        '</div>' +
                        '<button type="button" aria-label="Tutup" style="border:0;background:transparent;color:#94a3b8;font-size:22px;line-height:1;cursor:pointer;padding:0 0 0 8px;">&times;</button>' +
                    '</div>';

                toast.querySelector('button').addEventListener('click', function() {
                    toast.remove();
                });

                document.body.appendChild(toast);
                requestAnimationFrame(function() {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                });

                clearTimeout(window.__pageSubmitToastTimer);
                window.__pageSubmitToastTimer = setTimeout(function() {
                    if (!toast.parentNode) return;
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-8px)';
                    setTimeout(function() {
                        if (toast.parentNode) toast.remove();
                    }, 220);
                }, isSuccess ? 4200 : 6500);
            }

            function looksLikeJsonResponse(text) {
                var value = String(text || '').trim();
                return value.charAt(0) === '{' && value.indexOf('"success"') !== -1;
            }

            document.querySelectorAll('[data-custom-page-source-iframe]').forEach(function(iframe) {
                var originalSrcdoc = iframe.getAttribute('srcdoc') || '';
                iframe.addEventListener('load', function() {
                    try {
                        var doc = iframe.contentWindow && iframe.contentWindow.document;
                        var text = doc && doc.body ? doc.body.innerText : '';
                        if (!looksLikeJsonResponse(text)) return;

                        var data = JSON.parse(String(text).trim());
                        showSubmitToast(data && data.success ? 'success' : 'error', data && data.message ? data.message : '');
                        iframe.srcdoc = originalSrcdoc;
                    } catch (error) {
                    }
                });
            });
        })();
    </script>
    <?php return; ?>
<?php endif; ?>

<div class="mx-auto max-w-7xl px-4 py-8">
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
        </div>
    <?php endif; ?>

    <?php if ($isWorkspaceAdmin && count($forms) > 1): ?>
        <div class="mb-5 flex flex-wrap gap-2">
            <?php foreach ($forms as $index => $formModel): ?>
                <a href="#dynamic-form-card-<?= (int) $formModel->id ?>" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 no-underline transition hover:border-indigo-300 hover:text-indigo-700">
                    <span class="material-symbols-outlined text-sm">description</span>
                    <?= Html::encode($formModel->name ?: ('Form ' . ($index + 1))) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
