<?php
use yii\helpers\Json;
use app\services\MasterDatatableRenderService;
$pageId = (int)($pageId ?? 0);
$menuId = (int)($menuId ?? 0);

$layoutJson = Json::decode($layoutJson, true);

// Pre-render datatable blocks so the Page Source preview in the builder
// shows real backend-rendered datatables instead of placeholders.
// This HTML is embedded in the page source but gets stripped before
// saving to the database (see applyPageCustomCodePost).
$datatableHtmlByBlock = [];
$datatableRenderer = null;
if (is_array($layoutJson)) {
    foreach ($layoutJson as $block) {
        if (!is_array($block) || ($block['type'] ?? '') !== 'datatable') {
            continue;
        }
        $blockId = $block['id'] ?? '';
        if (!$blockId) continue;
        try {
            if ($datatableRenderer === null) {
                $datatableRenderer = new MasterDatatableRenderService();
            }
            $datatableHtmlByBlock[$blockId] = $datatableRenderer->renderFromConfig((array)($block['props'] ?? []), [
                'page_id' => $pageId,
                'menu_id' => $menuId,
            ]);
        } catch (\Exception $e) {
            \Yii::warning('Preview-layout datatable render failed: ' . $e->getMessage(), 'app');
            $datatableHtmlByBlock[$blockId] = '<div style="padding:16px;border:1px solid #fecaca;background:#fff1f2;color:#9f1239;border-radius:12px;">Datatable tidak dapat ditampilkan.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Halaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
<?php
$iconCssMap = [
    'material-symbols' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
    'tabler' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.10.0/dist/tabler-icons.min.css',
    'phosphor' => 'https://cdn.jsdelivr.net/npm/phosphor-icons@2.1.1/src/css/phosphor.css',
    'remix' => 'https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css',
    'font-awesome' => 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/css/all.min.css',
    'bootstrap-icons' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
];
$neededLibs = ['material-symbols']; // always include fallback
if (is_array($layoutJson)) {
    foreach ($layoutJson as $blk) {
        if (is_array($blk) && ($blk['type'] ?? '') === 'card' && !empty($blk['props']['iconLibrary'])) {
            $neededLibs[] = $blk['props']['iconLibrary'];
        }
    }
}
$neededLibs = array_unique($neededLibs);
foreach ($neededLibs as $lib) {
    if (isset($iconCssMap[$lib])) {
        echo '    <link rel="stylesheet" href="' . htmlspecialchars($iconCssMap[$lib]) . '">' . "\n";
    }
}
?>
    <script src="/js/dynamic-form-runtime.js"></script>
    <style>
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
        .relation-picker-search { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:14px; }
        .relation-picker-table { width:100%; border-collapse:collapse; }
        .relation-picker-table th,.relation-picker-table td { padding:10px 12px; border-bottom:1px solid #eef2f7; text-align:left; font-size:13px; }
        .relation-picker-table tbody tr { cursor:pointer; }
        .relation-picker-table tbody tr:hover { background:#f8fafc; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <div id="preview-content">
            <!-- Content will be rendered by JavaScript -->
        </div>
    </div>

    <script>
        window.pageState = <?= Json::encode($layoutJson) ?>;
        window.dynamicDatatableHtml = <?= Json::htmlEncode((object)$datatableHtmlByBlock) ?>;
        
        function renderBlock(block) {
            const props = block.props || {};
            switch(block.type) {
                case "heading":
                    const tag = props.level || "h2";
                    return `<${tag} class="text-${props.color?.replace('#', '') || 'gray-900'} text-${props.fontSize || '4'}xl font-bold mb-6 text-${props.align || 'left'}">${props.text || ''}</${tag}>`;
                case "text":
                    return `<p class="text-${props.color?.replace('#', '') || 'gray-700'} text-${props.fontSize || 'base'} leading-${props.lineHeight || 'relaxed'} mb-6">${props.content || ''}</p>`;
                case "image":
                    if (!props.src) return '';
                    return `<img src="${props.src}" alt="${props.alt || ''}" class="mx-auto my-6 rounded-lg ${props.align === 'center' ? 'mx-auto' : props.align === 'left' ? 'mr-0' : 'ml-0'} w-full max-w-xs" style="width: ${props.width || '100'}%; border-radius: ${props.borderRadius || '0'}px;">`;
                case "button":
                    const linkMode = String(props.linkMode || props.link_mode || '').toLowerCase();
                    const isUiOnly = linkMode === 'ui_only' || props.uiOnly === true || props.ui_only === true;
                    const pageId = props.pageId || props.page_id || '';
                    let href = '';
                    let target = String(props.target || '').trim();
                    if (!isUiOnly) {
                        if (linkMode === 'page' && pageId) {
                            href = '/page/view?id=' + encodeURIComponent(pageId);
                            if (!target) {
                                target = '_blank';
                            }
                        } else {
                            href = String(props.url || props.href || '').trim();
                        }
                    }
                    const colors = { 
                        primary: 'bg-blue-600 text-white', 
                        secondary: 'bg-gray-600 text-white', 
                        outline: 'border border-blue-600 text-blue-600 hover:bg-blue-50',
                        ghost: 'text-blue-600 hover:bg-blue-50'
                    };
                    if (isUiOnly || !href) {
                        return `<div class="text-${props.align || 'center'} my-6"><button type="button" class="inline-block px-6 py-3 rounded font-medium ${colors[props.style] || colors.primary} ${props.fullWidth ? 'w-full' : ''}" style="cursor:default;" aria-disabled="true">${props.text || ''}</button></div>`;
                    }
                    return `<div class="text-${props.align || 'center'} my-6"><a href="${href}" class="inline-block px-6 py-3 rounded font-medium ${colors[props.style] || colors.primary} ${props.fullWidth ? 'w-full' : ''}"${target ? ' target="' + target + '"' : ''}${target === '_blank' ? ' rel="noopener noreferrer"' : ''}>${props.text || ''}</a></div>`;
                case "card":
                    const blockId = block.id || ('card-' + Math.random().toString(36).slice(2));
                    const iconLib = props.iconLibrary || 'material-symbols';
                    const iconClass = ({'material-symbols':'material-symbols-outlined','tabler':'ti ti-' + (props.icon||''),'heroicons':'hero-icon hero-' + (props.icon||''),'lucide':'lucide lucide-' + (props.icon||''),'phosphor':'ph ph-' + (props.icon||''),'remix':'ri ri-' + (props.icon||''),'font-awesome':'fa-solid fa-' + (props.icon||''),'bootstrap-icons':'bi bi-' + (props.icon||''),'feather':'feather feather-' + (props.icon||'')})[iconLib] || 'material-symbols-outlined';
                    const iconContent = iconLib === 'material-symbols' ? (props.icon || '') : '';
                    const cardIcon = props.icon ? `<span class="${iconClass}" style="font-size:${props.iconSize || '48'}px;color:${props.iconColor || '#6366f1'};margin-bottom:12px;display:block;">${iconContent}</span>` : '';
                    const cardContent = props.description || props.content || '';
                    const cardValue = (props.showValue !== false && props.datasource === 'database') ? (props._previewValue || '--') : '';
                    return `<div data-card-id="${blockId}" data-datasource="${props.datasource || ''}" class="bg-white rounded-lg shadow-md p-6" style="text-align:${props.alignment || 'left'};${props.bgColor && props.bgColor !== '#ffffff' ? 'background:' + props.bgColor + ';' : ''}${props.showShadow ? '' : 'border:1px solid #e2e8f0;'}box-shadow:${props.showShadow ? '0 4px 12px rgba(0,0,0,0.08)' : 'none'};">
                        ${cardIcon}
                        <h3 class="text-lg font-bold mb-1" style="color:${props.textColor || '#1e293b'};">${props.title || ''}</h3>
                        ${props.subtitle ? `<div style="font-size:14px;color:${props.textColor || '#1e293b'}cc;margin-bottom:8px;">${props.subtitle}</div>` : ''}
                        ${cardValue ? `<div class="card-value" style="font-size:${Math.max(parseInt(props.fontSize || '16') + 8, 24)}px;font-weight:700;color:${props.textColor || '#1e293b'};margin-top:8px;line-height:1.2;">${cardValue}</div>` : ''}
                        ${cardContent ? `<p style="margin:0;color:${props.textColor || '#1e293b'}99;font-size:14px;">${cardContent}</p>` : ''}
                    </div>`;
                case "spacer":
                    return `<div class="h-${props.height || '8'} my-4"></div>`;
                case "divider":
                    return `<hr class="my-6 border-t-${props.thickness || '2'} border-${props.color?.replace('#', '') || 'gray-200'}">`;
                case "grid":
                    return `<div class="grid grid-cols-${props.columns || '3'} gap-${props.gap || '4'} p-4 bg-gray-50 rounded-lg">
                        <div class="p-4 bg-white rounded border">${props.content || 'Column 1'}</div>
                        <div class="p-4 bg-white rounded border">${props.content || 'Column 2'}</div>
                        <div class="p-4 bg-white rounded border">${props.content || 'Column 3'}</div>
                    </div>`;
                case "form":
                    const componentId = block.id || ('form-' + Math.random().toString(36).slice(2));
                    return `<div class="dynamic-form-slot p-3 bg-white rounded-lg border border-slate-200" data-form-id="${props.formId || ''}" data-show-title="${props.showTitle ? '1' : '0'}" data-component-id="${componentId}">
                        <div class="text-xs text-slate-500">Loading form...</div>
                    </div>`;
                case "datatable": {
                    const blockId = block.id || '';
                    const dtHtml = (window.dynamicDatatableHtml && window.dynamicDatatableHtml[blockId])
                        ? window.dynamicDatatableHtml[blockId]
                        : '<div class="dt-placeholder" style="padding:32px;text-align:center;color:#94a3b8;font-size:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;">Memuat datatable...</div>';
                    return dtHtml;
                }
                default:
                    return `<div class="p-4 bg-yellow-100 border border-yellow-300 rounded">Unknown block: ${block.type}</div>`;
            }
        }

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
                    .map((item) => item.trim())
                    .filter(Boolean);
            }

            function getPickerMeta(form, fieldName) {
                if (!form || !fieldName) {
                    return { searchColumns: [], displayColumns: [], pageSize: 10 };
                }
                const wrapper = form.querySelector('.relation-picker-wrapper[data-field-name="' + cssEscape(fieldName) + '"]');
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

        function executeScripts(root) {
            root.querySelectorAll('script').forEach(function(oldScript) {
                var newScript = document.createElement('script');
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

        function loadCardData(container) {
            var cardBlocks = [];
            (function collectCards(blocks) {
                (blocks || []).forEach(function(b) {
                    if (b.type === 'card' && b.props && b.props.datasource === 'database') {
                        cardBlocks.push(b);
                    }
                    if (b.children) collectCards(b.children);
                });
            })(window.pageState);

            cardBlocks.forEach(function(block) {
                var url = window.cardPreviewUrl || '/card/preview';
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ config: block.props })
                })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success && result.data) {
                        block.props.__liveValue = result.data.formatted || result.data.value;
                        var valueEl = container.querySelector('[data-card-id="' + block.id + '"] .card-value');
                        if (valueEl) {
                            valueEl.textContent = block.props.__liveValue;
                        }
                    }
                })
                .catch(function(err) { console.warn('[CardWidget] Data fetch error:', err); });
            });
        }

        function renderBlocks(blocks) {
            var html = '';
            var i = 0;
            while (i < blocks.length) {
                var block = blocks[i];
                var cardColumns = (block.type === 'card') ? parseInt(block.props?.columns || '1', 10) : 1;
                if (block.type === 'card' && cardColumns > 1) {
                    var j = i;
                    while (j < blocks.length && blocks[j].type === 'card' && parseInt(blocks[j].props?.columns || '1', 10) === cardColumns) { j++; }
                    var gap = 16;
                    var colWidth = (100 / cardColumns) - (gap * (cardColumns - 1) / cardColumns);
                    html += '<div class="card-row" style="display:flex;flex-wrap:wrap;gap:' + gap + 'px;width:100%;box-sizing:border-box;margin-bottom:12px;">';
                    for (var k = i; k < j; k++) {
                        html += '<div style="width:' + colWidth + '%;flex:0 0 ' + colWidth + '%;max-width:' + colWidth + '%;box-sizing:border-box;">' + renderBlock(blocks[k]) + '</div>';
                    }
                    html += '</div>';
                    i = j;
                } else {
                    html += renderBlock(block);
                    i++;
                }
            }
            return html;
        }

        document.addEventListener("DOMContentLoaded", function() {
            if (window.DynamicFormRuntime && window.DynamicFormRuntime.__assetRuntime) {
                const container = document.getElementById("preview-content");
                if (container && window.pageState) {
                    container.innerHTML = renderBlocks(window.pageState);
                    hydrateDynamicForms(container);
                    executeScripts(container);
                    loadCardData(container);
                }
                return;
            }

            const container = document.getElementById("preview-content");
            if (container && window.pageState) {
                container.innerHTML = renderBlocks(window.pageState);
                hydrateDynamicForms(container);
                executeScripts(container);
                loadCardData(container);
            }
        });

        function hydrateDynamicForms(root) {
            const slots = root.querySelectorAll('.dynamic-form-slot[data-form-id]');
            if (!slots.length) return;
            window.dynamicFormPreviewFetchCache = window.dynamicFormPreviewFetchCache || {};

            slots.forEach((slot) => {
                if (slot.dataset.formPreviewLoaded === '1' || slot.dataset.formPreviewLoading === '1') return;
                const formId = slot.getAttribute('data-form-id');
                const showTitle = slot.getAttribute('data-show-title') === '1' ? '1' : '0';
                if (!formId) {
                    slot.innerHTML = '<div class="text-xs text-amber-700">Form belum dipilih.</div>';
                    return;
                }

                let url = '/master-page/form-preview?id=' + encodeURIComponent(formId) + '&showTitle=' + showTitle + '&interactive=1&render_context=page_content';
                if (<?= (int)$pageId ?> > 0) {
                    url += '&page_id=<?= (int)$pageId ?>';
                }
                if (<?= (int)$menuId ?> > 0) {
                    url += '&menu_id=<?= (int)$menuId ?>';
                }
                const componentId = slot.getAttribute('data-component-id') || '';
                if (componentId) {
                    url += '&component_id=' + encodeURIComponent(componentId);
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
                        slot.innerHTML = '<div class="text-xs text-rose-700">Gagal memuat form preview.</div>';
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
                    slot.innerHTML = '<div class="text-xs text-rose-700">Gagal memuat form preview.</div>';
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
    </script>
</body>
</html>
