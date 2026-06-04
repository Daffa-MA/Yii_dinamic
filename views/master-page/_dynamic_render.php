<?php
/**
 * @var string $layoutJson
 * @var string|null $customHtml
 * @var string|null $customCss
 * @var string|null $customJs
 * @var string $pageType
 * @var string|null $pageKey
 */

$isCustomCode = ($pageType ?? 'builder') === 'custom_code';
$pageKey = $pageKey ?? 'page';
$pageId = (int)($pageId ?? 0);
$menuId = (int)($menuId ?? 0);
$permissionRegistry = new \app\components\ProjectPermissionRegistry();

// Prioritize persisted full-page custom source when available.
$customHtml = trim((string) ($customHtml ?? ''));
$customCss = trim((string) ($customCss ?? ''));
$customJs = trim((string) ($customJs ?? ''));
$hasCustomPageSource = $isCustomCode && ($customHtml !== '' || $customCss !== '' || $customJs !== '');

if ($hasCustomPageSource) {
    $formRenderer = new \app\services\DynamicFormPreviewService();
    $datatableRenderer = new \app\services\MasterDatatableRenderService();
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

    function navigate(url, target) {
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
        }
        event.preventDefault();
        event.stopPropagation();
        navigate(href, target);
    }, true);
})();
</script>
HTML;

        if (stripos($source, '</body>') !== false) {
            return preg_replace('~</body>~i', $script . "\n</body>", $source, 1) ?? ($source . $script);
        }

        return $source . $script;
    };
    $replaceFormTokens = static function (string $source) use ($formRenderer, $datatableRenderer, $pageId, $menuId): string {
        $source = preg_replace_callback('/\{\{\s*form\s*:\s*(\d+)\s*\}\}/i', static function (array $matches) use ($formRenderer, $pageId, $menuId): string {
            try {
                return $formRenderer->renderByScopedId((int)$matches[1], true, true, [
                    'render_context' => 'page_content',
                    'page_id' => $pageId,
                    'menu_id' => $menuId,
                ]);
            } catch (\Throwable $e) {
                Yii::warning('Failed to render embedded form in custom page renderer: ' . $e->getMessage(), 'app');
                return '<div style="padding:12px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;border-radius:10px;">Form tidak dapat ditampilkan.</div>';
            }
        }, $source) ?? $source;

        return preg_replace_callback('/\{\{\s*datatable\s*:\s*(\d+)\s*\}\}/i', static function (array $matches) use ($datatableRenderer, $pageId, $menuId): string {
            try {
                return $datatableRenderer->renderByPresetId((int)$matches[1], [
                    'render_context' => 'page_content',
                    'page_id' => $pageId,
                    'menu_id' => $menuId,
                ]);
            } catch (\Throwable $e) {
                Yii::warning('Failed to render embedded datatable in custom page renderer: ' . $e->getMessage(), 'app');
                return '<div style="padding:12px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;border-radius:10px;">Datatable tidak dapat ditampilkan.</div>';
            }
        }, $source) ?? $source;
    };

    try {
        $customHtml = $replaceFormTokens($customHtml);
        $customHtml = $injectLinkHandler($customHtml);
    } catch (\Throwable $e) {
        Yii::warning('Failed to expand custom page form tokens: ' . $e->getMessage(), 'app');
    }

    // Check if it looks like a complete HTML document
    $isCompleteDoc = strpos($customHtml, '<!DOCTYPE') === 0 ||
                     strpos($customHtml, '<html') === 0;

    if ($isCompleteDoc) {
        echo $customHtml;
    } else {
        ?>
        <style><?= $customCss ?? '' ?></style>
        <div id="modern-page-content">
            <?= $customHtml ?>
        </div>
        <?php if (!empty($customJs)): ?>
        <script><?= $customJs ?></script>
        <?php endif;
    }
    return;
}

// Fallback to legacy JSON-based renderer
$state = json_decode($layoutJson, true);
if (!is_array($state)) {
    $state = [];
}

$state = $permissionRegistry->filterPageState($state, $pageKey);
$datatableHtmlByBlock = [];
$datatableRenderer = new \app\services\MasterDatatableRenderService();
foreach ($state as $block) {
    if (!is_array($block) || ($block['type'] ?? '') !== 'datatable') {
        continue;
    }
    $blockId = (string)($block['id'] ?? '');
    if ($blockId === '') {
        continue;
    }
    try {
        $datatableHtmlByBlock[$blockId] = $datatableRenderer->renderFromConfig((array)($block['props'] ?? []), [
            'page_id' => $pageId,
            'menu_id' => $menuId,
        ]);
    } catch (\Throwable $e) {
        Yii::warning('Failed to render datatable block: ' . $e->getMessage(), 'master-page-datatable');
        $datatableHtmlByBlock[$blockId] = '<div style="padding:16px;border:1px solid #fecaca;background:#fff1f2;color:#9f1239;border-radius:12px;">Datatable tidak dapat ditampilkan.</div>';
    }
}
?>
<style>
    .dynamic-page-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    .dynamic-page-container .mb-4 { margin-bottom: 1rem; }
    .dynamic-page-container .mb-8 { margin-bottom: 2rem; }
    .dynamic-page-container .text-center { text-align: center; }
    .dynamic-page-container .text-gray-700 { color: #374151; }
    .dynamic-page-container .mx-auto { margin-left: auto; margin-right: auto; }
    .dynamic-page-container .rounded { border-radius: 0.5rem; }
    .dynamic-page-container .rounded-xl { border-radius: 0.75rem; }
    .dynamic-page-container .rounded-2xl { border-radius: 1rem; }
    .dynamic-page-container .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .dynamic-page-container .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .dynamic-page-container .p-4 { padding: 1rem; }
    .dynamic-page-container .p-6 { padding: 1.5rem; }
    .dynamic-page-container .inline-block { display: inline-block; }
    .dynamic-page-container .block { display: block; }
    .dynamic-page-container .bg-blue-50 { background-color: #eff6ff; }
    .dynamic-page-container .bg-indigo-600 { background-color: #4f46e5; }
    .dynamic-page-container .bg-white { background-color: white; }
    .dynamic-page-container .text-white { color: white; }
    .dynamic-page-container .border { border-width: 1px; }
    .dynamic-page-container .border-gray-200 { border-color: #e5e7eb; }
    .dynamic-page-container .border-indigo-600 { border-color: #4f46e5; }
    .dynamic-page-container .text-indigo-600 { color: #4f46e5; }
    .dynamic-page-container .bg-gray-600 { background-color: #4b5563; }
    .dynamic-page-container .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .dynamic-page-container .font-bold { font-weight: 700; }
    .relation-picker-wrapper { width:100%; }
    .relation-picker-input-group,
    .relation-picker-row { display:flex; gap:8px; align-items:stretch; width:100%; }
    .relation-picker-input-group .dynamic-form-input,
    .relation-picker-row .dynamic-form-input { flex:1; min-width:0; }
    .relation-picker-btn,
    .relation-picker-button { display:inline-flex; align-items:center; justify-content:center; min-height:38px; border:1px solid #dbe3ef; background:#fff; color:#334155; border-radius:10px; padding:0 14px; font-weight:700; cursor:pointer; white-space:nowrap; text-decoration:none; }
    .relation-picker-status { margin-top:6px; font-size:12px; color:#64748b; }
    .relation-picker-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:20px; z-index:12000; background:rgba(15,23,42,.48); backdrop-filter:blur(4px); }
    .relation-picker-modal.open { display:flex; }
    .relation-picker-panel { width:min(860px,100%); max-height:min(680px,88vh); background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 28px 90px rgba(15,23,42,.28); overflow:hidden; display:flex; flex-direction:column; }
    .relation-picker-head,.relation-picker-foot { padding:14px 18px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; gap:12px; align-items:center; }
    .relation-picker-foot { border-bottom:0; border-top:1px solid #e2e8f0; }
    .relation-picker-body { padding:16px 18px; overflow:auto; }
    .relation-picker-table { width:100%; border-collapse:collapse; }
    .relation-picker-table th,.relation-picker-table td { padding:10px 12px; border-bottom:1px solid #eef2f7; text-align:left; font-size:13px; }
    .relation-picker-table tbody tr { cursor:pointer; }
    .relation-picker-table tbody tr:hover { background:#f8fafc; }
</style>

<div class="dynamic-page-container" id="dynamic-content"></div>

<?php
$dynamicFormRuntimeJs = <<<'JS'
window.DynamicFormRuntime = window.DynamicFormRuntime || (function() {
    const pickerDataUrl = '/master-form/relation-picker-data';
    const pickerSearchUrl = '/master-form/relation-picker-search';
    let pickerState = { fieldName: '', formId: '', page: 1, hasNext: false, form: null };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function cssEscape(value) {
        return window.CSS && CSS.escape ? CSS.escape(value) : String(value).replace(/"/g, '\\"');
    }

    function parsePickerList(value) {
        return String(value || '')
            .split(',')
            .map(function(item) { return item.trim(); })
            .filter(Boolean);
    }

    function getPickerMeta(form, fieldName) {
        if (!form || !fieldName) {
            return { searchColumns: [], displayColumns: [], pageSize: 10 };
        }
        var wrapper = form.querySelector('.relation-picker-wrapper[data-field-name="' + cssEscape(fieldName) + '"]');
        if (!wrapper) {
            return { searchColumns: [], displayColumns: [], pageSize: 10 };
        }
        return {
            searchColumns: parsePickerList(wrapper.getAttribute('data-picker-search-columns')),
            displayColumns: parsePickerList(wrapper.getAttribute('data-picker-display-columns')),
            pageSize: Math.max(1, Math.min(50, parseInt(wrapper.getAttribute('data-picker-page-size') || '10', 10) || 10))
        };
    }

    function setPickerModalSummary(modal, meta) {
        return;
    }

    function ensureModal() {
        let modal = document.getElementById('dynamicRelationPickerModal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'dynamicRelationPickerModal';
        modal.className = 'relation-picker-modal';
                modal.innerHTML = `
            <div class="relation-picker-panel">
                <div class="relation-picker-head">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;width:100%;">
                        <strong>Pilih Data</strong>
                        <button type="button" class="relation-picker-btn" data-picker-close>Tutup</button>
                    </div>
                </div>
                <div class="relation-picker-body">
                    <input type="text" class="relation-picker-search" data-picker-search placeholder="Cari data...">
                    <div data-picker-content style="margin-top:14px;"></div>
                </div>
                <div class="relation-picker-foot">
                    <button type="button" class="relation-picker-btn" data-picker-prev>Sebelumnya</button>
                    <span data-picker-page style="font-size:13px;color:#64748b;"></span>
                    <button type="button" class="relation-picker-btn" data-picker-next>Berikutnya</button>
                </div>
            </div>`;
        document.body.appendChild(modal);

        modal.querySelector('[data-picker-close]').addEventListener('click', closePicker);
        modal.querySelector('[data-picker-prev]').addEventListener('click', function() {
            if (pickerState.page > 1) {
                pickerState.page -= 1;
                loadPickerPage();
            }
        });
        modal.querySelector('[data-picker-next]').addEventListener('click', function() {
            if (pickerState.hasNext) {
                pickerState.page += 1;
                loadPickerPage();
            }
        });
        modal.querySelector('[data-picker-search]').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                pickerState.page = 1;
                loadPickerPage();
            }
        });
        modal.querySelector('[data-picker-content]').addEventListener('click', function(event) {
            const row = event.target.closest('tr[data-value]');
            if (!row) return;
            setPickerValue(pickerState.form, pickerState.fieldName, row.getAttribute('data-value'), row.getAttribute('data-label'));
            closePicker();
        });

        return modal;
    }

    function buildPickerUrl(baseUrl, formId, fieldName, query, page, limit) {
        const params = new URLSearchParams({ form_id: formId, field_name: fieldName, q: query || '' });
        if (page) params.set('page', page);
        if (limit) params.set('limit', limit);
        return baseUrl + '?' + params.toString();
    }

    function setPickerStatus(form, fieldName, text) {
        const status = form ? form.querySelector('[data-relation-picker-status="' + cssEscape(fieldName) + '"]') : null;
        if (status) status.textContent = text || '';
    }

    function setPickerValue(form, fieldName, value, label) {
        if (!form || !fieldName) return;
        const hidden = form.querySelector('[data-relation-picker-value="' + cssEscape(fieldName) + '"]');
        const display = form.querySelector('.relation-picker-display[data-field-name="' + cssEscape(fieldName) + '"]');
        if (hidden) {
            hidden.value = value || '';
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (display) display.value = label || '';
        setPickerStatus(form, fieldName, value ? 'Dipilih: ' + (label || value) : '');
    }

    function openPicker(form, fieldName, formId, query) {
        pickerState = { fieldName: fieldName, formId: formId, page: 1, hasNext: false, form: form };
        const modal = ensureModal();
        const search = modal.querySelector('[data-picker-search]');
        if (search) search.value = query || '';
        setPickerModalSummary(modal, getPickerMeta(form, fieldName));
        modal.classList.add('open');
        loadPickerPage();
    }

    function closePicker() {
        const modal = document.getElementById('dynamicRelationPickerModal');
        if (modal) modal.classList.remove('open');
    }

    function loadPickerPage() {
        const modal = ensureModal();
        const search = modal.querySelector('[data-picker-search]');
        const content = modal.querySelector('[data-picker-content]');
        const pageInfo = modal.querySelector('[data-picker-page]');
        const query = search ? search.value : '';
        content.innerHTML = '<div style="font-size:13px;color:#64748b;">Loading...</div>';
        setPickerModalSummary(modal, getPickerMeta(pickerState.form, pickerState.fieldName));
        fetch(buildPickerUrl(pickerDataUrl, pickerState.formId, pickerState.fieldName, query, pickerState.page), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data || !data.success) throw new Error((data && data.message) || 'Gagal memuat data');
                const rows = Array.isArray(data.rows) ? data.rows : [];
                pickerState.hasNext = !!(data.pagination && data.pagination.has_next);
                if (pageInfo) pageInfo.textContent = 'Halaman ' + pickerState.page + ' - ' + ((data.pagination && data.pagination.total) || 0) + ' data';
                if (!rows.length) {
                    content.innerHTML = '<div style="padding:14px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;border-radius:12px;">No data available<br><small>This table does not have any data yet.</small></div>';
                    return;
                }
                const keys = Object.keys(rows[0].display || {});
                content.innerHTML = '<table class="relation-picker-table"><thead><tr>' +
                    keys.map((key) => '<th>' + escapeHtml(key) + '</th>').join('') +
                    '</tr></thead><tbody>' +
                    rows.map((row) => '<tr data-value="' + escapeHtml(row.value) + '" data-label="' + escapeHtml(row.label) + '">' +
                        keys.map((key) => '<td>' + escapeHtml(row.display[key]) + '</td>').join('') +
                    '</tr>').join('') +
                    '</tbody></table>';
            })
            .catch((error) => {
                content.innerHTML = '<div style="padding:14px;border:1px solid #fecaca;background:#fff1f2;color:#9f1239;border-radius:12px;">' + escapeHtml(error.message || 'Gagal memuat data.') + '</div>';
            });
    }

    function bindForm(form) {
        if (!form || form.dataset.dynamicRuntimeBound === '1') return;
        form.dataset.dynamicRuntimeBound = '1';

        form.querySelectorAll('.relation-picker-display').forEach((input) => {
            input.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                const fieldName = input.getAttribute('data-field-name') || '';
                const formId = input.getAttribute('data-form-id') || form.getAttribute('data-form-id') || '';
                const mode = input.getAttribute('data-picker-mode') || 'autocomplete';
                const query = input.value || '';
                if (mode === 'modal_picker' || mode === 'autocomplete_with_modal') {
                    openPicker(form, fieldName, formId, query);
                    return;
                }
                setPickerStatus(form, fieldName, 'Mencari data...');
                fetch(buildPickerUrl(pickerSearchUrl, formId, fieldName, query, null, 10), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then((res) => res.json())
                    .then((data) => {
                        const matches = data && Array.isArray(data.matches) ? data.matches : [];
                        if (matches.length === 1) {
                            setPickerValue(form, fieldName, matches[0].value, matches[0].label || matches[0].display_text);
                        } else if (matches.length > 1) {
                            openPicker(form, fieldName, formId, query);
                        } else {
                            setPickerStatus(form, fieldName, 'Data tidak ditemukan.');
                        }
                    })
                    .catch(() => setPickerStatus(form, fieldName, 'Gagal mencari data.'));
            });
        });

        form.querySelectorAll('[data-relation-picker-open], .relation-picker-button').forEach((button) => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const fieldName = button.getAttribute('data-relation-picker-open') || button.getAttribute('data-field-name') || button.getAttribute('data-picker-field') || '';
                const input = form.querySelector('.relation-picker-display[data-field-name="' + cssEscape(fieldName) + '"]');
                openPicker(form, fieldName, input ? (input.getAttribute('data-form-id') || '') : (form.getAttribute('data-form-id') || ''), input ? input.value : '');
            });
        });

        form.addEventListener('submit', function() {
            form.querySelectorAll('select[data-fk-submit-name]').forEach(function(select) {
                const submitName = select.getAttribute('data-fk-submit-name');
                if (!submitName) return;
                let hiddenInput = form.querySelector('input[name="__fk_submit_' + submitName + '"]');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = '__fk_submit_' + submitName;
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = select.value || '';
            });
        });
    }

    return {
        init: function(root) {
            const scope = root || document;
            if (scope.matches && scope.matches('form.dynamic-embedded-form')) {
                bindForm(scope);
            }
            scope.querySelectorAll && scope.querySelectorAll('form.dynamic-embedded-form').forEach(bindForm);
        }
    };
})();
JS;

$this->registerJs($dynamicFormRuntimeJs, \yii\web\View::POS_END);

$js = "
window.dynamicPageState = " . \yii\helpers\Json::htmlEncode($state) . ";
window.dynamicDatatableHtml = " . \yii\helpers\Json::htmlEncode($datatableHtmlByBlock) . ";

function renderBlockSafe(block) {
    const props = (block && block.props) ? block.props : {};
    const type = block ? block.type : null;

    function normalizeValue(value) {
        return (value === null || value === undefined) ? '' : String(value).trim();
    }

    function resolveButtonPageHref(buttonProps) {
        const pageId = normalizeValue(buttonProps.pageId || buttonProps.page_id);
        if (!pageId) {
            return '';
        }

        return '/page/view/' + encodeURIComponent(pageId);
    }

    function resolveButtonHref(buttonProps) {
        const reservedTargets = ['_blank', '_self', '_parent', '_top'];
        const linkMode = normalizeValue(buttonProps.linkMode || buttonProps.link_mode);
        const pageHref = resolveButtonPageHref(buttonProps);
        if (linkMode === 'ui_only') {
            return '';
        }
        if (linkMode === 'page' && pageHref) {
            return pageHref;
        }

        if (pageHref && !normalizeValue(buttonProps.url) && !normalizeValue(buttonProps.href)) {
            return pageHref;
        }

        const href = normalizeValue(buttonProps.href);
        const url = normalizeValue(buttonProps.url);
        const route = normalizeValue(buttonProps.route);
        const action = normalizeValue(buttonProps.action);
        const resolvedHref = [href, url, route, action].find((value) => value && value !== '#') || '';
        if (resolvedHref) {
            return resolvedHref;
        }

        if (linkMode === 'page') {
            return pageHref;
        }

        const target = normalizeValue(buttonProps.target);
        if (target && !reservedTargets.includes(target)) {
            return target;
        }

        return '';
    }

    function isUiOnlyButton(buttonProps) {
        const linkMode = normalizeValue(buttonProps.linkMode || buttonProps.link_mode);
        return linkMode === 'ui_only' || buttonProps.uiOnly === true || buttonProps.ui_only === true;
    }

    function resolveButtonTarget(buttonProps) {
        const target = normalizeValue(buttonProps.target);
        if (['_blank', '_self', '_parent', '_top'].includes(target)) {
            return target;
        }

        const linkMode = normalizeValue(buttonProps.linkMode || buttonProps.link_mode);
        const pageHref = resolveButtonPageHref(buttonProps);
        if (linkMode === 'page' && pageHref) {
            return '_blank';
        }

        return '';
    }

    function isExternalDestination(value) {
        const normalized = normalizeValue(value);
        return /^(https?:|mailto:|tel:)/i.test(normalized);
    }

    // Handle Block-level Custom Code
    if (props.customHtml || props.customCss || props.customJs) {
        const wrap = document.createElement('div');
        wrap.className = 'mb-8 custom-block-wrap';
        
        const srcDoc = `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { margin: 0; padding: 0; font-family: sans-serif; overflow: hidden; }
                    \${props.customCss || ''}
                </style>
            </head>
            <body>
                <div id=\"root\">\${props.customHtml || ''}</div>
                <script>
                    (function() {
                        try {
                            \${props.customJs || ''}
                        } catch (e) { console.error(e); }
                    })();
                    function updateHeight() {
                        window.parent.postMessage({
                            type: 'resize',
                            blockId: '\${block.id}',
                            height: document.documentElement.scrollHeight
                        }, '*');
                    }
                    window.onload = updateHeight;
                    new ResizeObserver(updateHeight).observe(document.body);
                </` + `script>
            </body>
            </html>
        `;
        
        const iframe = document.createElement('iframe');
        iframe.id = `iframe-\${block.id}`;
        iframe.srcdoc = srcDoc;
        iframe.style.width = '100%';
        iframe.style.border = 'none';
        iframe.style.overflow = 'hidden';
        iframe.style.display = 'block';
        iframe.setAttribute('sandbox', 'allow-scripts allow-popups allow-popups-to-escape-sandbox');
        
        wrap.appendChild(iframe);
        return wrap;
    }

    switch (type) {
        case 'heading': {
            const el = document.createElement(props.level || 'h2');
            el.className = 'mb-4';
            el.style.textAlign = props.align || 'left';
            el.style.fontSize = (props.fontSize || '24') + 'px';
            el.style.fontWeight = '700';
            el.style.color = props.color || '#1e293b';
            el.textContent = props.text || '';
            return el;
        }
        case 'text': {
            const el = document.createElement('div');
            el.className = 'mb-4';
            el.style.fontSize = (props.fontSize || '15') + 'px';
            el.style.lineHeight = props.lineHeight || '1.6';
            el.style.color = props.color || '#475569';
            el.style.textAlign = props.align || 'left';
            el.style.whiteSpace = 'pre-wrap';
            el.textContent = props.content || '';
            return el;
        }
        case 'image': {
            if (!props.src) return document.createTextNode('');
            const el = document.createElement('img');
            el.src = props.src;
            el.alt = props.alt || '';
            el.className = 'mb-4 mx-auto';
            el.style.width = (props.width || '100') + '%';
            el.style.borderRadius = (props.borderRadius || '8') + 'px';
            el.style.display = 'block';
            if (props.align === 'center') el.style.margin = '0 auto 1rem';
            else if (props.align === 'right') el.style.margin = '0 0 1rem auto';
            return el;
        }
        case 'button': {
            const wrap = document.createElement('div');
            wrap.className = 'mb-4';
            wrap.style.textAlign = props.align || 'center';

            const isUiOnly = isUiOnlyButton(props);
            const buttonHref = resolveButtonHref(props);
            const buttonTarget = resolveButtonTarget(props);
            const style = props.style || 'primary';

            const applyButtonStyles = function(el) {
                el.style.display = props.fullWidth ? 'block' : 'inline-block';
                el.style.padding = props.size === 'lg' ? '12px 32px' : (props.size === 'sm' ? '8px 16px' : '10px 24px');
                el.style.borderRadius = '8px';
                el.style.textDecoration = 'none';
                el.style.fontWeight = '600';
                el.style.fontSize = '14px';
                el.style.cursor = 'pointer';
            };

            if (style === 'primary') {
                // keep
            } else if (style === 'secondary') {
            } else if (style === 'outline') {
            }

            if (isUiOnly) {
                const button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('aria-disabled', 'true');
                applyButtonStyles(button);
                button.style.cursor = 'default';
                button.style.pointerEvents = 'none';
                if (style === 'primary') {
                    button.style.backgroundColor = '#4f46e5';
                    button.style.color = 'white';
                    button.style.border = 'none';
                } else if (style === 'secondary') {
                    button.style.backgroundColor = '#4b5563';
                    button.style.color = 'white';
                    button.style.border = 'none';
                } else if (style === 'outline') {
                    button.style.backgroundColor = 'transparent';
                    button.style.border = '2px solid #4f46e5';
                    button.style.color = '#4f46e5';
                } else {
                    button.style.backgroundColor = '#4f46e5';
                    button.style.color = 'white';
                    button.style.border = 'none';
                }
                button.textContent = props.text || 'Button';
                wrap.appendChild(button);
                return wrap;
            }

            if (!buttonHref) {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.emptyAction = 'true';
                applyButtonStyles(button);
                if (style === 'primary') {
                    button.style.backgroundColor = '#4f46e5';
                    button.style.color = 'white';
                    button.style.border = 'none';
                } else if (style === 'secondary') {
                    button.style.backgroundColor = '#4b5563';
                    button.style.color = 'white';
                    button.style.border = 'none';
                } else if (style === 'outline') {
                    button.style.backgroundColor = 'transparent';
                    button.style.border = '2px solid #4f46e5';
                    button.style.color = '#4f46e5';
                } else {
                    button.style.backgroundColor = '#4f46e5';
                    button.style.color = 'white';
                    button.style.border = 'none';
                }
                button.textContent = props.text || 'Button';
                wrap.appendChild(button);
                return wrap;
            }

            const a = document.createElement('a');
            a.href = buttonHref;
            const resolvedButtonTarget = buttonTarget || (isExternalDestination(buttonHref) ? '_blank' : '');
            if (resolvedButtonTarget) {
                a.target = resolvedButtonTarget;
                if (resolvedButtonTarget === '_blank') {
                    a.rel = 'noopener noreferrer';
                }
            }
            applyButtonStyles(a);
            if (style === 'primary') {
                a.style.backgroundColor = '#4f46e5';
                a.style.color = 'white';
            } else if (style === 'secondary') {
                a.style.backgroundColor = '#4b5563';
                a.style.color = 'white';
            } else if (style === 'outline') {
                a.style.backgroundColor = 'transparent';
                a.style.border = '2px solid #4f46e5';
                a.style.color = '#4f46e5';
            } else {
                a.style.backgroundColor = '#4f46e5';
                a.style.color = 'white';
            }
            a.textContent = props.text || 'Button';
            wrap.appendChild(a);
            return wrap;
        }
        case 'form': {
            const el = document.createElement('div');
            el.className = 'mb-4 p-6 bg-white border border-gray-200 rounded-xl shadow-sm';
            el.style.maxWidth = '600px';
            el.style.margin = '0 auto 1.5rem';
            const formId = props.formId || '';
            const showTitle = props.showTitle ? '1' : '0';
            const componentId = (block && block.id) ? String(block.id) : ('form-' + Math.random().toString(36).slice(2));
            el.innerHTML = `<div class=\"dynamic-form-slot\" data-form-id=\"\${formId}\" data-show-title=\"\${showTitle}\" data-component-id=\"\${componentId}\"><div style=\"font-size:12px;color:#64748b;\">Loading form...</div></div>`;
            return el;
        }
        case 'datatable': {
            const el = document.createElement('div');
            el.className = 'mb-8 dynamic-datatable-slot';
            el.innerHTML = (window.dynamicDatatableHtml && window.dynamicDatatableHtml[block.id])
                ? window.dynamicDatatableHtml[block.id]
                : '<div style=\"padding:24px;border:1px solid #e2e8f0;border-radius:16px;background:#fff;text-align:center;color:#64748b;\"><strong style=\"display:block;color:#0f172a;\">No data available</strong>This table does not have any data yet.</div>';
            return el;
        }
        case 'card': {
            const el = document.createElement('div');
            el.className = 'mb-4 p-6 bg-white border rounded-xl shadow-sm';
            el.style.backgroundColor = props.bgColor || '#ffffff';
            el.style.padding = (props.padding || '20') + 'px';
            
            const h4 = document.createElement('h4');
            h4.className = 'font-bold mb-2';
            h4.style.color = '#1e293b';
            h4.style.fontSize = '18px';
            h4.textContent = props.title || '';
            
            const p = document.createElement('p');
            p.style.color = '#64748b';
            p.style.fontSize = '15px';
            p.style.margin = '0';
            p.textContent = props.content || '';
            
            el.appendChild(h4);
            el.appendChild(p);
            return el;
        }
        case 'spacer': {
            const el = document.createElement('div');
            el.style.height = (props.height || '32') + 'px';
            return el;
        }
        case 'divider': {
            const el = document.createElement('hr');
            el.style.border = 'none';
            el.style.borderTop = (props.thickness || '2') + 'px solid ' + (props.color || '#e2e8f0');
            el.style.margin = (props.margin || '16') + 'px 0';
            return el;
        }
        case 'grid': {
            const wrap = document.createElement('div');
            wrap.className = 'mb-4';
            wrap.style.display = 'grid';
            wrap.style.gridTemplateColumns = 'repeat(' + (props.columns || 3) + ', 1fr)';
            wrap.style.gap = (props.gap || '16') + 'px';
            
            for (let i = 0; i < (props.columns || 3); i++) {
                const col = document.createElement('div');
                col.style.padding = '20px';
                col.style.backgroundColor = '#f8fafc';
                col.style.border = '1px solid #e2e8f0';
                col.style.borderRadius = '8px';
                col.style.minHeight = '60px';
                wrap.appendChild(col);
            }
            return wrap;
        }
        case 'section': {
            const el = document.createElement('div');
            el.className = 'mb-4';
            el.style.padding = (props.padding || '40') + 'px';
            el.style.margin = (props.margin || '0') + 'px';
            el.style.backgroundColor = props.background || '#ffffff';
            el.style.borderRadius = '12px';
            return el;
        }
        case 'video': {
            const wrap = document.createElement('div');
            wrap.className = 'mb-4';
            wrap.style.width = (props.width || '100') + '%';
            
            const container = document.createElement('div');
            container.style.position = 'relative';
            container.style.paddingTop = (props.aspectRatio === '4/3' ? '75%' : '56.25%') + '%';
            container.style.backgroundColor = '#000';
            container.style.borderRadius = '12px';
            container.style.overflow = 'hidden';
            
            if (props.url) {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'absolute';
                iframe.style.top = '0';
                iframe.style.left = '0';
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                
                let videoUrl = props.url;
                if (videoUrl.includes('youtube.com/watch?v=')) videoUrl = videoUrl.replace('watch?v=', 'embed/');
                else if (videoUrl.includes('youtu.be/')) videoUrl = videoUrl.replace('youtu.be/', 'youtube.com/embed/');
                
                iframe.src = videoUrl;
                container.appendChild(iframe);
            }
            
            wrap.appendChild(container);
            return wrap;
        }
        default:
            return document.createTextNode('');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dynamic-content');
    if (!container || !window.dynamicPageState || !Array.isArray(window.dynamicPageState)) {
        return;
    }

    container.innerHTML = '';
    for (const block of window.dynamicPageState) {
        container.appendChild(renderBlockSafe(block));
    }

    const showEmptyDestinationToast = function(message) {
        const existing = document.getElementById('empty-action-toast');
        if (existing) {
            existing.remove();
        }

        const toast = document.createElement('div');
        toast.id = 'empty-action-toast';
        toast.setAttribute('role', 'status');
        toast.style.cssText = [
            'position:fixed',
            'right:20px',
            'bottom:20px',
            'z-index:99999',
            'max-width:360px',
            'padding:14px 16px',
            'border-radius:14px',
            'background:#0f172a',
            'color:#fff',
            'box-shadow:0 18px 40px rgba(15,23,42,.22)',
            'font-size:14px',
            'line-height:1.5'
        ].join(';');
        toast.textContent = message || 'Button destination is not configured.';
        document.body.appendChild(toast);
        setTimeout(function() {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 2400);
    };

    container.addEventListener('click', function(event) {
        const emptyAction = event.target.closest('[data-empty-action=\'true\']');
        if (!emptyAction) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        showEmptyDestinationToast('Button destination is not configured.');
    });

    hydrateDynamicForms(container);
});

function hydrateDynamicForms(root) {
    const slots = root.querySelectorAll('.dynamic-form-slot[data-form-id]');
    if (!slots.length) return;
    window.dynamicFormPreviewFetchCache = window.dynamicFormPreviewFetchCache || {};

    slots.forEach((slot) => {
        if (slot.dataset.formPreviewLoaded === '1' || slot.dataset.formPreviewLoading === '1') return;
        const formId = slot.getAttribute('data-form-id');
        const showTitle = slot.getAttribute('data-show-title') === '1' ? '1' : '0';
        const componentId = slot.getAttribute('data-component-id') || '';
        if (!formId) {
            slot.innerHTML = '<div style=\"font-size:12px;color:#9a3412;\">Form belum dipilih.</div>';
            return;
        }

        let url = '/master-page/form-preview?id=' + encodeURIComponent(formId) + '&showTitle=' + showTitle + '&interactive=1&render_context=page_content';
        if (componentId) {
            url += '&component_id=' + encodeURIComponent(componentId);
        }
        if (<?= (int)$pageId ?> > 0) {
            url += '&page_id=<?= (int)$pageId ?>';
        }
        if (<?= (int)$menuId ?> > 0) {
            url += '&menu_id=<?= (int)$menuId ?>';
        }

        slot.dataset.formPreviewLoading = '1';
        window.dynamicFormPreviewFetchCache[url] = window.dynamicFormPreviewFetchCache[url] || fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then((res) => res.text());

        window.dynamicFormPreviewFetchCache[url]
            .then((raw) => {
                let data = null;
                try { data = JSON.parse(raw); } catch (e) { data = null; }
                if (!data || !data.success) {
                    slot.innerHTML = '<div style=\"font-size:12px;color:#9f1239;\">Gagal memuat form preview.</div>';
                    slot.dataset.formPreviewLoading = '0';
                    return;
                }
                slot.innerHTML = data.html || '';
                slot.dataset.formPreviewLoaded = '1';
                slot.dataset.formPreviewLoading = '0';
                bindEmbeddedFormSubmit(slot);
                if (window.DynamicFormRuntime && typeof window.DynamicFormRuntime.init === 'function') {
                    window.DynamicFormRuntime.init(slot);
                }
            })
            .catch(() => {
                slot.innerHTML = '<div style=\"font-size:12px;color:#9f1239;\">Gagal memuat form preview.</div>';
                slot.dataset.formPreviewLoading = '0';
                delete window.dynamicFormPreviewFetchCache[url];
            });
    });
}

function bindEmbeddedFormSubmit(root) {
    const form = root.querySelector('form.dynamic-embedded-form');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageBox = form.querySelector('.dynamic-form-submit-message');
        const submitBtn = form.querySelector('button[type=\"submit\"]');
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

// Global Message Handler for Iframe Resizing
window.addEventListener('message', (e) => {
    if (e.data && e.type === 'resize' && e.data.blockId) {
        const iframe = document.getElementById(`iframe-\${e.data.blockId}`);
        if (iframe) {
            iframe.style.height = e.data.height + 'px';
        }
    }
});
";

$this->registerJs($js, \yii\web\View::POS_END);
