<?php

use app\models\Form;
use app\models\MasterPage;
use app\models\MasterForm;
use app\models\MasterPageChart;
use app\components\ActiveProjectContext;
use app\components\CommanderAuthContext;
use app\components\ProjectAuthContext;
use app\services\DynamicFormPreviewService;
use app\services\FormRenderService;
use app\services\MasterDatatableRenderService;
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
$isWorkspaceAdmin = $isCommanderSuperAdmin || in_array($workspaceRole, ['admin', 'superadmin', 'super_admin'], true);
$displayUsername = $isCommanderSuperAdmin
    ? 'Superadmin'
    : trim((string)($projectAuthUser->username ?? $projectAuthUser->name ?? 'Pengguna'));
$displayRole = $isCommanderSuperAdmin ? 'Superadmin' : ($workspaceRole !== '' ? ucfirst($workspaceRole) : 'User');
$workspaceName = $activeProject !== null ? (string)$activeProject->name : 'Workspace';
$emptyStateTitle = $isWorkspaceAdmin ? 'Belum ada konten' : 'Informasi belum tersedia';
$emptyStateDescription = $isWorkspaceAdmin
    ? 'Halaman ini siap digunakan tetapi belum memiliki konten. Tambahkan konten melalui Master Halaman.'
    : 'Halaman ini belum memiliki konten untuk ditampilkan. Silakan hubungi admin workspace jika halaman ini seharusnya berisi informasi.';

$this->registerJsFile('/js/dynamic-form-runtime.js', ['position' => \yii\web\View::POS_END]);

// Icon CDN CSS for card widget icons
$this->registerCssFile('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/phosphor-icons@2.1.1/src/css/phosphor.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/css/all.min.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
$this->registerCssFile(\yii\helpers\Url::to('@web/css/card-widget.css'));

$cardPreviewUrl = \yii\helpers\Url::to(['/card/preview']);

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
<script src="/js/dynamic-form-runtime.js"></script>
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
    $datatableService = new MasterDatatableRenderService();
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
        $customHtml = preg_replace_callback('/\{\{\s*datatable\s*:\s*(\d+)\s*\}\}/i', static function (array $matches) use ($datatableService, $page, $activeMenuId): string {
            try {
                return $datatableService->renderByPresetId((int)$matches[1], [
                    'render_context' => 'page_content',
                    'page_id' => (int)$page->id,
                    'menu_id' => $activeMenuId,
                ]);
            } catch (\Throwable $e) {
                Yii::warning('Failed to render embedded datatable on page view: ' . $e->getMessage(), 'app');
                return '<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Datatable tidak dapat ditampilkan.</div>';
            }
        }, $customHtml) ?? $customHtml;
    } catch (\Throwable $e) {
        Yii::warning('Failed to expand custom page tokens on page view: ' . $e->getMessage(), 'app');
    }

    // Pre-render datatable blocks from layout_json and inject as window.dynamicDatatableHtml
    // so the page source's JavaScript renderBlock() can access server-rendered datatable HTML.
    $dtLayout = json_decode($page->layout_json ?? '[]', true);
    $dtHtmlByBlock = [];
    if (is_array($dtLayout)) {
        $dtRenderer = new MasterDatatableRenderService();
        foreach ($dtLayout as $dtBlock) {
            if (!is_array($dtBlock) || ($dtBlock['type'] ?? '') !== 'datatable') {
                continue;
            }
            $blockId = (string)($dtBlock['id'] ?? '');
            if ($blockId === '') {
                continue;
            }
            try {
                $dtHtmlByBlock[$blockId] = $dtRenderer->renderFromConfig((array)($dtBlock['props'] ?? []), [
                    'page_id' => (int)$page->id,
                    'menu_id' => $activeMenuId,
                ]);
            } catch (\Throwable $e) {
                Yii::warning('Failed to render datatable on page view: ' . $e->getMessage(), 'app');
                $dtHtmlByBlock[$blockId] = '<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Datatable tidak dapat ditampilkan.</div>';
            }
        }
    }
    if (!empty($dtHtmlByBlock)) {
        $dtScript = '<script>window.dynamicDatatableHtml = ' . \yii\helpers\Json::htmlEncode($dtHtmlByBlock) . ';</script>';
        $customHtml = preg_replace('/<head\b[^>]*>/i', '$0' . $dtScript, $customHtml, 1);
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
                $fallbackFormModel = MasterForm::findOne($fallbackFormId);
                try {
                    $submissionRequestId = bin2hex(random_bytes(16));
                } catch (\Throwable $e) {
                    $submissionRequestId = uniqid('submit_', true);
                }
                $customHtml = FormRenderService::prepareCustomFormSubmission($customHtml, $fallbackFormId, [
                    '_embedded' => '1',
                    'render_context' => 'page_content',
                    'page_id' => (string)(int)$page->id,
                    'menu_id' => $activeMenuId > 0 ? (string)$activeMenuId : '',
                    'project_id' => $activeProjectId !== null ? (string)$activeProjectId : '',
                    'workspace_role' => $workspaceRole,
                    '_submit_request_id' => $submissionRequestId,
                    '_datatable_target_table_id' => $fallbackFormModel && $fallbackFormModel->hasAttribute('table_id')
                        ? (string)(int)$fallbackFormModel->table_id
                        : '',
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
        '$0<base href="' . Html::encode(Yii::$app->request->absoluteUrl) . '">',
        $customSourceDoc,
        1
    ) ?? $customSourceDoc;
    $customSourceDoc = $injectLinkHandler($customSourceDoc);

    // Inject executeScripts to re-execute <script> tags from dynamically injected
    // datatable HTML (scripts are not executed when set via innerHTML).
    // Wait for DOMContentLoaded so #preview-content has been populated by the
    // page source's own DOMContentLoaded handler before we re-execute scripts.
    $executeHelper = '<script>
(function() {
    function executeScripts(root) {
        root.querySelectorAll("script").forEach(function(oldScript) {
            var newScript = document.createElement("script");
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            if (oldScript.parentNode) {
                oldScript.parentNode.replaceChild(newScript, oldScript);
            }
        });
    }
    function run() {
        var container = document.getElementById("preview-content");
        if (container) {
            executeScripts(container);
        }
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", run);
    } else {
        run();
    }
    var attempts = 0;
    var timer = setInterval(function() {
        attempts++;
        run();
        if (attempts >= 20) clearInterval(timer);
    }, 150);
})();
</script>';
    $customSourceDoc = preg_replace('/<\/body>\s*<\/html>\s*$/i', $executeHelper . "\n</body>\n</html>", $customSourceDoc);
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
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-downloads allow-modals allow-top-navigation"
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

            function getIframeDocument() {
                var iframe = document.querySelector('[data-custom-page-source-iframe]');
                if (!iframe || !iframe.contentWindow) {
                    return null;
                }
                try {
                    return iframe.contentWindow.document || null;
                } catch (error) {
                    return null;
                }
            }

            function escapeAttr(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function toStringValue(value) {
                if (value === null || value === undefined || value === '') {
                    return '';
                }
                if (Array.isArray(value)) {
                    return value.join(', ');
                }
                if (typeof value === 'object') {
                    try {
                        return JSON.stringify(value);
                    } catch (error) {
                        return '';
                    }
                }
                return String(value);
            }

            function buildRowKey(rowData, primaryKeys) {
                var rowKey = {};
                (primaryKeys || []).forEach(function(key) {
                    if (Object.prototype.hasOwnProperty.call(rowData || {}, key)) {
                        rowKey[key] = rowData[key];
                    }
                });
                return rowKey;
            }

            function buildRowHtml(columns, rowData, rowDisplayData, primaryKeys, hasActions, deleteUrl, csrfParam, csrfToken) {
                var rowKey = buildRowKey(rowData, primaryKeys);
                var html = '<tr data-row-key="' + escapeAttr(JSON.stringify(rowKey || {})) + '" data-row-values="' + escapeAttr(JSON.stringify(rowData || {})) + '" data-row-display-values="' + escapeAttr(JSON.stringify(rowDisplayData || {})) + '">';
                (columns || []).forEach(function(column) {
                    var field = String(column.field || '');
                    var value = Object.prototype.hasOwnProperty.call(rowDisplayData || {}, field)
                        ? rowDisplayData[field]
                        : (Object.prototype.hasOwnProperty.call(rowData || {}, field) ? rowData[field] : '');
                    html += '<td>' + escapeHtml(toStringValue(value)) + '</td>';
                });
                if (hasActions) {
                    html += '<td><div class="dt-actions">';
                    html += '<button type="button" class="dt-btn" data-row-action="view">View</button>';
                    html += '<button type="button" class="dt-btn" data-row-action="edit">Edit</button>';
                    if (deleteUrl) {
                        html += '<form method="post" action="' + escapeAttr(deleteUrl) + '" onsubmit="return confirm(\'Delete this row?\');">' +
                            '<input type="hidden" name="' + escapeAttr(csrfParam || '_csrf') + '" value="' + escapeAttr(csrfToken || '') + '">' +
                            '<input type="hidden" name="row_key" value="' + escapeAttr(JSON.stringify(rowKey || {})) + '">' +
                            '<button class="dt-btn dt-btn-danger" type="submit">Delete</button>' +
                        '</form>';
                    }
                    html += '</div></td>';
                }
                html += '</tr>';
                return html;
            }

            async function reloadDatatableElement(root) {
                var reloadUrl = root ? root.getAttribute('data-reload-url') : '';
                if (!reloadUrl) {
                    return false;
                }

                var response = await fetch(reloadUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                var result = await response.json();
                if (!result || !result.success) {
                    return false;
                }

                var tbody = root.querySelector('tbody');
                if (tbody && typeof result.tbodyHtml === 'string') {
                    tbody.innerHTML = result.tbodyHtml;
                }
                var subtitle = root.querySelector('[data-datatable-subtitle]');
                if (subtitle && result.subtitle) {
                    subtitle.textContent = result.subtitle;
                }
                return true;
            }

            async function refreshDatatableInIframe(data) {
                if (data && data.duplicate) {
                    return false;
                }
                var doc = getIframeDocument();
                if (!doc) {
                    return false;
                }

                var targetTableId = data && data.targetTableId ? String(data.targetTableId) : '';
                var targetTableName = data && data.targetTableName ? String(data.targetTableName) : '';
                var roots = [];
                doc.querySelectorAll('[data-component="datatable"], .master-datatable').forEach(function(root) {
                    var matchesId = targetTableId !== '' && String(root.getAttribute('data-datatable-table-id') || '') === targetTableId;
                    var matchesName = targetTableName !== '' && String(root.getAttribute('data-table') || '') === targetTableName;
                    if (matchesId || matchesName || (targetTableId === '' && targetTableName === '')) {
                        roots.push(root);
                    }
                });
                if (!roots.length) {
                    return false;
                }

                var refreshed = false;
                for (var i = 0; i < roots.length; i += 1) {
                    refreshed = await reloadDatatableElement(roots[i]) || refreshed;
                }
                return refreshed;
            }

            window.addEventListener('message', function(event) {
                var data = event && event.data ? event.data : null;
                if (!data || data.type !== 'custom-form-submit-success') {
                    return;
                }

                showSubmitToast('success', 'Data berhasil dikirim.');
                refreshDatatableInIframe(data).catch(function(error) {
                    console.error(error);
                });
            });

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
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals"
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
                        $blockId = $item['id'] ?? '';
                        $cardTitle = $props['title'] ?? '';
                        $cardSubtitle = $props['subtitle'] ?? '';
                        $cardDesc = $props['description'] ?? '';
                        $cardBgColor = $props['bgColor'] ?? '#ffffff';
                        $cardPadding = ($props['padding'] ?? '24') . 'px';
                        $cardRadius = ($props['borderRadius'] ?? '12') . 'px';
                        $cardWidth = ($props['width'] ?? '100') . '%';
                        $cardAlign = $props['alignment'] ?? 'left';
                        $cardTextColor = $props['textColor'] ?? '#1e293b';
                        $cardFontSize = ($props['fontSize'] ?? '16') . 'px';
                        $cardFontWeight = $props['fontWeight'] ?? '400';
                        $cardFontFamily = $props['fontFamily'] ?? '';
                        $cardShadowMap = ['none' => 'none', 'sm' => '0 1px 2px rgba(0,0,0,0.05)', 'md' => '0 4px 6px -1px rgba(0,0,0,0.1)', 'lg' => '0 10px 15px -3px rgba(0,0,0,0.1)', 'xl' => '0 20px 25px -5px rgba(0,0,0,0.1)', '2xl' => '0 25px 50px -12px rgba(0,0,0,0.25)', 'inner' => 'inset 0 2px 4px rgba(0,0,0,0.05)'];
                        $cardShadow = $cardShadowMap[$props['shadow'] ?? 'md'] ?? $cardShadowMap['md'];
                        $cardBorder = ($props['border'] ?? 'none') !== 'none' ? '1px ' . ($props['border'] ?? 'none') . ' ' . ($props['borderColor'] ?? '#e2e8f0') : 'none';

                        // Background
                        $cardBg = $cardBgColor;
                        $cardGlass = false;
                        if (($props['bgType'] ?? 'solid') === 'gradient' && !empty($props['bgGradient'])) {
                            $cardBg = $props['bgGradient'];
                        } elseif (($props['bgType'] ?? 'solid') === 'image' && !empty($props['bgImage'])) {
                            $cardBg = 'url(' . $props['bgImage'] . ') center/cover no-repeat';
                        } elseif (($props['bgType'] ?? 'solid') === 'glass') {
                            $cardBg = 'rgba(255,255,255,0.15)';
                            $cardGlass = true;
                        } elseif (($props['bgType'] ?? 'solid') === 'transparent') {
                            $cardBg = 'transparent';
                        }

                        $cardStyles = "width:{$cardWidth};padding:{$cardPadding};background:{$cardBg};border-radius:{$cardRadius};box-shadow:{$cardShadow};border:{$cardBorder};text-align:{$cardAlign};";
                        if ($cardFontFamily) $cardStyles .= "font-family:{$cardFontFamily};";
                        $cardCssClasses = 'card-widget' . ($cardGlass ? ' card-glass' : '');
                        echo "<div class=\"{$cardCssClasses}\" style=\"{$cardStyles}\"" . ($blockId ? " data-card-id=\"" . htmlspecialchars($blockId) . "\"" : "") . ">";

                        // Icon
                        if (($props['showIcon'] ?? true) !== false && !empty($props['icon'])) {
                            $iconLib = $props['iconLibrary'] ?? 'material-symbols';
                            $iconName = $props['icon'];
                            $iconSize = ($props['iconSize'] ?? '48') . 'px';
                            $iconColor = $props['iconColor'] ?? '#6366f1';
                            $iconOpacity = (intval($props['iconOpacity'] ?? 100) / 100);
                            $iconBg = $props['iconBackground'] ?? '';
                            $iconShape = $props['iconShape'] ?? 'none';
                            $iconRotation = $props['iconRotation'] ?? '0';
                            $iconWeight = $props['iconWeight'] ?? '400';
                            $iconFill = !empty($props['iconFill']);

                            $iconCssClass = 'material-symbols-outlined';
                            if ($iconLib === 'tabler') $iconCssClass = 'ti ti-' . $iconName;
                            elseif ($iconLib === 'heroicons') $iconCssClass = 'hero-icon hero-' . $iconName;
                            elseif ($iconLib === 'lucide') $iconCssClass = 'lucide lucide-' . $iconName;
                            elseif ($iconLib === 'phosphor') $iconCssClass = 'ph ph-' . $iconName;
                            elseif ($iconLib === 'remix') $iconCssClass = 'ri ri-' . $iconName;
                            elseif ($iconLib === 'font-awesome') $iconCssClass = 'fa-solid fa-' . $iconName;
                            elseif ($iconLib === 'bootstrap-icons') $iconCssClass = 'bi bi-' . $iconName;

                            $iconExtraStyle = "font-size:{$iconSize};color:{$iconColor};opacity:{$iconOpacity};";
                            if ($iconLib === 'material-symbols') {
                                $iconExtraStyle .= "font-variation-settings:'FILL' " . ($iconFill ? 1 : 0) . ", 'wght' " . $iconWeight . ", 'GRAD' 0;";
                            }
                            if ($iconBg) {
                                $shapeCss = '';
                                if ($iconShape === 'circle') $shapeCss = 'border-radius:50%;';
                                elseif ($iconShape === 'rounded') $shapeCss = 'border-radius:12px;';
                                elseif ($iconShape === 'square') $shapeCss = 'border-radius:4px;';
                                $iconExtraStyle .= "background:{$iconBg};padding:12px;display:inline-flex;align-items:center;justify-content:center;{$shapeCss}";
                            }
                            $iconWrapStyle = "margin-bottom:12px;text-align:{$cardAlign};opacity:{$iconOpacity};";
                            if ($iconRotation && $iconRotation !== '0') {
                                $iconExtraStyle .= "transform:rotate({$iconRotation}deg);";
                            }
                            echo "<div style=\"{$iconWrapStyle}\"><span class=\"{$iconCssClass} card-icon-wrapper\" style=\"{$iconExtraStyle}\">" . htmlspecialchars($iconName) . "</span></div>";
                        }

                        // Title
                        if (($props['showTitle'] ?? true) !== false && $cardTitle) {
                            $titleStyle = "font-size:{$cardFontSize};font-weight:700;color:{$cardTextColor};line-height:" . ($props['lineHeight'] ?? '1.5') . ";margin-bottom:" . ($cardSubtitle ? '4px' : '8px') . ";";
                            echo "<div style=\"{$titleStyle}\">" . htmlspecialchars($cardTitle) . "</div>";
                        }

                        // Subtitle
                        if (($props['showSubtitle'] ?? true) !== false && $cardSubtitle) {
                            $subStyle = "font-size:" . max(intval($cardFontSize) - 2, 12) . "px;color:{$cardTextColor}cc;margin-bottom:8px;";
                            echo "<div style=\"{$subStyle}\">" . htmlspecialchars($cardSubtitle) . "</div>";
                        }

                        // Description
                        if (($props['showDescription'] ?? true) !== false && $cardDesc) {
                            $descStyle = "font-size:" . max(intval($cardFontSize) - 4, 12) . "px;color:{$cardTextColor}99;margin-bottom:8px;";
                            echo "<div style=\"{$descStyle}\">" . htmlspecialchars($cardDesc) . "</div>";
                        }

                        // Value (from data source) - show placeholder initially, JS will update
                        if (($props['showValue'] ?? true) !== false && ($props['datasource'] ?? '') === 'database') {
                            $valStyle = "font-size:" . max(intval($cardFontSize) + 8, 24) . "px;font-weight:700;color:{$cardTextColor};margin-top:8px;line-height:1.2;";
                            $previewVal = $props['_previewValue'] ?? '';
                            echo "<div class=\"card-value\" style=\"{$valStyle}\">" . ($previewVal ? htmlspecialchars($previewVal) : '--') . "</div>";
                        }

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

                    case 'datatable':
                        $datatableService = new MasterDatatableRenderService();
                        echo $datatableService->renderFromConfig($props, [
                            'page_id' => (int)$page->id,
                            'menu_id' => $activeMenuId,
                        ]);
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
                        
                    case 'chart':
                        $chartId = $props['chartId'] ?? '';
                        if ($chartId) {
                            echo "<div data-master-chart=\"" . (int)$chartId . "\" data-chart-height=\"" . ((int)($props['height'] ?? 300)) . "\" style=\"min-height:200px;margin:1rem 0;\"></div>";
                        }
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
// Collect card configs for live data loading
$cardConfigs = [];
if ($hasBuilderContent && is_array($layoutData)) {
    foreach ($layoutData as $item) {
        if (is_array($item) && ($item['type'] ?? '') === 'card' && ($item['props']['datasource'] ?? '') === 'database') {
            $cardConfigs[] = [
                'id' => $item['id'] ?? '',
                'props' => $item['props'] ?? [],
            ];
        }
    }
}
$cardConfigsJson = \yii\helpers\Json::htmlEncode($cardConfigs);

$chartRenderJs = <<<'CHARTJS'
(function() {
    function ensureApexCharts(callback) {
        if (typeof ApexCharts !== 'undefined') { callback(); return; }
        window._pageChartRetryCount = (window._pageChartRetryCount || 0) + 1;
        if (window._pageChartRetryCount > 15) return;
        if (!window._pageChartApexLoading) {
            window._pageChartApexLoading = true;
            var origDefine = window.define;
            window.define = void 0;
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/apexcharts@4.5.0/dist/apexcharts.min.js';
            s.async = true;
            s.onload = function() { window.define = origDefine; setTimeout(callback, 300); };
            s.onerror = function() { window.define = origDefine; };
            document.body.appendChild(s);
        } else {
            setTimeout(callback, 500);
        }
    }

    function renderPageCharts() {
        var containers = document.querySelectorAll('[data-master-chart]');
        if (!containers.length) return;
        ensureApexCharts(function() {
            containers.forEach(function(container) {
                var chartId = container.getAttribute('data-master-chart');
                if (!chartId || container._chartRendered) return;
                container._chartRendered = true;
                var chartHeight = container.getAttribute('data-chart-height') || '300';
                container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:' + chartHeight + 'px;background:#f8fafc;color:#94a3b8;font-size:13px;">Memuat chart...</div>';
                fetch('/master-chart/data?id=' + encodeURIComponent(chartId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(function(data) {
                    if (!data || !data.success || !data.config) {
                        container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:' + chartHeight + 'px;background:#fef2f2;color:#991b1b;font-size:13px;">Gagal memuat chart</div>';
                        return;
                    }
                    renderPageChart(container, chartId, data, chartHeight);
                })
                .catch(function() {
                    container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:' + chartHeight + 'px;background:#fef2f2;color:#991b1b;font-size:13px;">Gagal terhubung ke server</div>';
                });
            });
        });
    }

    function renderPageChart(container, chartId, data, chartHeight) {
        var config = data.config;
        var chartData = data.chart;
        var palette = data.palette || [];
        var chartType = config.chart_type || 'bar';
        var apexType = mapPageChartType(chartType);
        var height = parseInt(chartHeight || config.height || 300);
        var theme = config.theme || 'light';
        var isDark = theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        var series = chartData.series || [];
        var labels = chartData.labels || [];
        var options = {
            chart: { type: apexType, height: height, toolbar: { show: !!config.show_toolbar }, animations: { enabled: config.animation !== 'none' }, background: 'transparent', foreColor: isDark ? '#e2e8f0' : '#64748b' },
            series: series, labels: labels, colors: palette.length ? palette : undefined,
            dataLabels: { enabled: !!config.show_label },
            legend: { show: !!config.show_legend, position: 'bottom', fontSize: '12px', labels: { colors: isDark ? '#e2e8f0' : '#64748b' } },
            grid: { show: !!config.show_grid, borderColor: isDark ? '#334155' : '#e2e8f0' },
            stroke: { show: true, curve: 'smooth', width: chartType === 'line' || chartType === 'area' ? 2 : 0 },
            fill: { opacity: chartType === 'area' || chartType === 'stacked_area' ? 0.5 : 1 },
            plotOptions: {
                bar: { horizontal: chartType === 'bar_horizontal', barHeight: '70%', columnWidth: '60%', borderRadius: 4 },
                pie: { donut: { labels: { show: !!config.show_total, total: { show: !!config.show_total, label: 'Total', formatter: function() { return chartData.total || 0; } } } } }
            },
            tooltip: { enabled: true, theme: isDark ? 'dark' : 'light' },
            noData: { text: 'Tidak ada data', align: 'center', verticalAlign: 'middle', style: { fontSize: '14px', color: '#94a3b8' } }
        };
        if (chartType === 'radar') { options.plotOptions = { radar: { polygons: { strokeColors: isDark ? '#334155' : '#e2e8f0', connectorColors: isDark ? '#334155' : '#e2e8f0' } } }; options.stroke.colors = palette; options.fill.opacity = 0.3; options.markers = { size: 4 }; }
        if (chartType === 'polar_area') { options.chart.type = 'polarArea'; options.stroke.show = false; options.fill.opacity = 0.8; }
        if (chartType === 'bubble' || chartType === 'scatter') { options.chart.zoom = { enabled: true, type: 'xy' }; }
        if (chartType === 'stacked_bar' || chartType === 'stacked_area') { options.chart.stacked = true; if (options.plotOptions && options.plotOptions.bar) options.plotOptions.bar.stacked = true; }
        var chartEl = document.createElement('div');
        chartEl.id = 'chart-' + (config.id || chartId);
        container.innerHTML = '';
        container.appendChild(chartEl);
        try { var ch = new ApexCharts(chartEl, options); ch.render().catch(function(){}); } catch (e) { container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:' + height + 'px;background:#fef2f2;color:#991b1b;font-size:13px;">Gagal render chart</div>'; }
    }

    function mapPageChartType(type) {
        var map = { bar:'bar', bar_horizontal:'bar', line:'line', area:'area', pie:'pie', donut:'donut', radar:'radar', polar_area:'polarArea', bubble:'bubble', scatter:'scatter', stacked_bar:'bar', stacked_area:'area', mixed:'line', multi_series:'bar' };
        return map[type] || 'bar';
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderPageCharts);
    } else {
        renderPageCharts();
    }
})();
CHARTJS;
$this->registerJs($chartRenderJs, \yii\web\View::POS_END);

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

// Card widget live data loading
$cardDataJs = <<<CARDJS
(function() {
    var cardConfigs = {$cardConfigsJson};
    var previewUrl = '{$cardPreviewUrl}';

    if (!cardConfigs || !cardConfigs.length) return;

    cardConfigs.forEach(function(config) {
        if (!config.id || !config.props) return;

        fetch(previewUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ config: config.props })
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.success && result.data) {
                var value = result.data.formatted || result.data.value;
                var valueEl = document.querySelector('.card-widget[data-card-id="' + config.id + '"] .card-value');
                if (valueEl) {
                    valueEl.textContent = value;
                }
            }
        })
        .catch(function(err) { console.warn('[CardWidget] Data fetch error:', err); });
    });
})();
CARDJS;
if (!empty($cardConfigs)) {
    $this->registerJs($cardDataJs, \yii\web\View::POS_END);
}
?>
