<?php
/**
 * @var string $layoutJson
 * @var string|null $customHtml
 * @var string|null $customCss
 * @var string|null $customJs
 * @var string $pageType
 */

$isCustomCode = ($pageType ?? 'builder') === 'custom_code';

// Prioritize persisted full-page custom source when available.
if (!empty(trim((string) $customHtml))) {
    // Check if it looks like a complete HTML document
    $isCompleteDoc = strpos(trim($customHtml), '<!DOCTYPE') === 0 || 
                     strpos(trim($customHtml), '<html') === 0;

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
</style>

<div class="dynamic-page-container" id="dynamic-content"></div>

<?php
$js = "
window.dynamicPageState = " . \yii\helpers\Json::htmlEncode($state) . ";

function renderBlockSafe(block) {
    const props = (block && block.props) ? block.props : {};
    const type = block ? block.type : null;

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
        iframe.setAttribute('sandbox', 'allow-scripts');
        
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
            
            const a = document.createElement('a');
            const buttonUrl = props.url || '';
            const style = props.style || 'primary';
            
            // Handle empty/null URL - prevent navigation
            if (!buttonUrl || buttonUrl === '#' || buttonUrl === '#nothing') {
                a.href = 'javascript:void(0)';
                a.dataset.noNavigate = '1';
            } else {
                a.href = buttonUrl;
                a.target = '_blank';
            }
            
            a.style.display = props.fullWidth ? 'block' : 'inline-block';
            a.style.padding = props.size === 'lg' ? '12px 32px' : (props.size === 'sm' ? '8px 16px' : '10px 24px');
            a.style.borderRadius = '8px';
            a.style.textDecoration = 'none';
            a.style.fontWeight = '600';
            a.style.fontSize = '14px';
            a.style.cursor = 'pointer';
            
            if (style === 'primary') {
                a.style.backgroundColor = '#4f46e5';
                a.style.color = 'white';
            } else if (style === 'secondary') {
                a.style.backgroundColor = '#4b5563';
                a.style.color = 'white';
            } else if (style === 'outline') {
                a.style.border = '2px solid #4f46e5';
                a.style.color = '#4f46e5';
            } else {
                a.style.color = '#4f46e5';
            }
            
            a.textContent = props.text || '';
            
            // Prevent parent navigation for buttons without valid URL
            a.addEventListener('click', function(e) {
                if (this.dataset.noNavigate === '1') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
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
            el.innerHTML = `<div class=\"dynamic-form-slot\" data-form-id=\"\${formId}\" data-show-title=\"\${showTitle}\"><div style=\"font-size:12px;color:#64748b;\">Loading form...</div></div>`;
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

    // Prevent all links from navigating within iframe (prevents 404)
    container.querySelectorAll('a').forEach(link => {
        const href = link.getAttribute('href');
        // If link is empty, '#', or not an external URL, prevent navigation
        if (!href || href === '#' || href === 'javascript:void(0)' || !href.startsWith('http')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        } else {
            // External links should open in new tab
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });

    hydrateDynamicForms(container);
});

function hydrateDynamicForms(root) {
    const slots = root.querySelectorAll('.dynamic-form-slot[data-form-id]');
    if (!slots.length) return;

    slots.forEach((slot) => {
        const formId = slot.getAttribute('data-form-id');
        const showTitle = slot.getAttribute('data-show-title') === '1' ? '1' : '0';
        if (!formId) {
            slot.innerHTML = '<div style=\"font-size:12px;color:#9a3412;\">Form belum dipilih.</div>';
            return;
        }

        fetch('/master-page/form-preview?id=' + encodeURIComponent(formId) + '&showTitle=' + showTitle + '&interactive=1', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then((res) => res.text())
            .then((raw) => {
                let data = null;
                try { data = JSON.parse(raw); } catch (e) { data = null; }
                if (!data || !data.success) {
                    slot.innerHTML = '<div style=\"font-size:12px;color:#9f1239;\">Gagal memuat form preview.</div>';
                    return;
                }
                slot.innerHTML = data.html || '';
                bindEmbeddedFormSubmit(slot);
            })
            .catch(() => {
                slot.innerHTML = '<div style=\"font-size:12px;color:#9f1239;\">Gagal memuat form preview.</div>';
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
