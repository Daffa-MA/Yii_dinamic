<?php
use yii\helpers\Json;
$pageId = (int)($pageId ?? 0);
$menuId = (int)($menuId ?? 0);

$layoutJson = Json::decode($layoutJson, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Halaman</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <div id="preview-content">
            <!-- Content will be rendered by JavaScript -->
        </div>
    </div>

    <script>
        window.pageState = <?= Json::encode($layoutJson) ?>;
        
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
                    const colors = { 
                        primary: 'bg-blue-600 text-white', 
                        secondary: 'bg-gray-600 text-white', 
                        outline: 'border border-blue-600 text-blue-600 hover:bg-blue-50',
                        ghost: 'text-blue-600 hover:bg-blue-50'
                    };
                    return `<div class="text-${props.align || 'center'} my-6"><a href="${props.url || '#'}" class="inline-block px-6 py-3 rounded font-medium ${colors[props.style] || colors.primary} ${props.fullWidth ? 'w-full' : ''}">${props.text || ''}</a></div>`;
                case "card":
                    return `<div class="bg-white rounded-lg shadow-md p-6 ${props.bgColor && props.bgColor !== '#ffffff' ? 'bg-' + props.bgColor.replace('#', '') : ''} ${props.showShadow ? 'shadow' : 'border'} ${props.showShadow ? '' : 'border border-gray-200'}">
                        <h3 class="text-lg font-bold mb-3 text-gray-900">${props.title || ''}</h3>
                        <p class="text-gray-700">${props.content || ''}</p>
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
                    return `<div class="dynamic-form-slot p-3 bg-white rounded-lg border border-slate-200" data-form-id="${props.formId || ''}" data-show-title="${props.showTitle ? '1' : '0'}">
                        <div class="text-xs text-slate-500">Loading form...</div>
                    </div>`;
                default:
                    return `<div class="p-4 bg-yellow-100 border border-yellow-300 rounded">Unknown block: ${block.type}</div>`;
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById("preview-content");
            if (container && window.pageState) {
                container.innerHTML = window.pageState.map(renderBlock).join("");
                hydrateDynamicForms(container);
            }
        });

        function hydrateDynamicForms(root) {
            const slots = root.querySelectorAll('.dynamic-form-slot[data-form-id]');
            if (!slots.length) return;

            slots.forEach((slot) => {
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

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then((res) => res.text())
                .then((raw) => {
                    let data = null;
                    try { data = JSON.parse(raw); } catch (e) { data = null; }
                    if (!data || !data.success) {
                        slot.innerHTML = '<div class="text-xs text-rose-700">Gagal memuat form preview.</div>';
                        return;
                    }
                    slot.innerHTML = data.html || '';
                    bindEmbeddedFormSubmit(slot);
                })
                .catch(() => {
                    slot.innerHTML = '<div class="text-xs text-rose-700">Gagal memuat form preview.</div>';
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
