<?php
/**
 * @var string $layoutJson
 */

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
    .dynamic-page-container .text-center { text-align: center; }
    .dynamic-page-container .text-gray-700 { color: #374151; }
    .dynamic-page-container .mx-auto { margin-left: auto; margin-right: auto; }
    .dynamic-page-container .rounded { border-radius: 0.5rem; }
    .dynamic-page-container .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
    .dynamic-page-container .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .dynamic-page-container .inline-block { display: inline-block; }
    .dynamic-page-container .p-4 { padding: 1rem; }
    .dynamic-page-container .bg-blue-50 { background-color: #eff6ff; }
    .dynamic-page-container .bg-indigo-600 { background-color: #4f46e5; }
    .dynamic-page-container .text-white { color: white; }
    .dynamic-page-container .border { border-width: 1px; }
    .dynamic-page-container .border-indigo-600 { border-color: #4f46e5; }
    .dynamic-page-container .text-indigo-600 { color: #4f46e5; }
    .dynamic-page-container .bg-gray-600 { background-color: #4b5563; }
    .dynamic-page-container .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .dynamic-page-container .border-rounded { border-radius: 0.5rem; }
    .dynamic-page-container .font-bold { font-weight: 700; }
    .dynamic-page-container .bg-white { background-color: white; }
</style>

<div class="dynamic-page-container" id="dynamic-content"></div>

<?php
$js = "
window.dynamicPageState = " . \yii\helpers\Json::htmlEncode($state) . ";

function renderBlockSafe(block) {
    const props = (block && block.props) ? block.props : {};
    const type = block ? block.type : null;

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
            return el;
        }
        case 'button': {
            const wrap = document.createElement('div');
            wrap.className = 'mb-4 text-center';
            
            const a = document.createElement('a');
            a.href = props.url || '#';
            const colors = {
                primary: 'bg-indigo-600 text-white',
                secondary: 'bg-gray-600 text-white',
                outline: 'border border-indigo-600 text-indigo-600',
                ghost: 'text-indigo-600'
            };
            const colorClass = colors[props.style] || colors.primary;
            a.style.display = 'inline-block';
            a.style.padding = props.size === 'lg' ? '12px 32px' : (props.size === 'sm' ? '8px 16px' : '10px 24px');
            a.style.borderRadius = '8px';
            a.style.textDecoration = 'none';
            a.style.fontWeight = '600';
            a.style.fontSize = '14px';
            
            if (colorClass === 'bg-indigo-600 text-white') {
                a.style.backgroundColor = '#4f46e5';
                a.style.color = 'white';
            } else if (colorClass === 'bg-gray-600 text-white') {
                a.style.backgroundColor = '#4b5563';
                a.style.color = 'white';
            } else if (colorClass === 'border border-indigo-600 text-indigo-600') {
                a.style.border = '2px solid #4f46e5';
                a.style.color = '#4f46e5';
            } else {
                a.style.color = '#4f46e5';
            }
            
            a.textContent = props.text || '';
            wrap.appendChild(a);
            return wrap;
        }
        case 'form': {
            const el = document.createElement('div');
            el.className = 'mb-4 p-4 bg-blue-50 rounded';
            el.innerHTML = '<div style=\"color:#1e40af;font-weight:600;\">Form: #' + (props.formId || 'Belum pilih') + '</div>';
            return el;
        }
        case 'card': {
            const el = document.createElement('div');
            el.className = 'mb-4 p-4 bg-white border rounded shadow-sm';
            el.style.borderRadius = '12px';
            el.style.backgroundColor = props.bgColor || '#ffffff';
            el.style.padding = (props.padding || '20') + 'px';
            
            const h4 = document.createElement('h4');
            h4.className = 'font-bold mb-2';
            h4.style.color = '#1e293b';
            h4.style.fontSize = '16px';
            h4.textContent = props.title || '';
            
            const p = document.createElement('p');
            p.style.color = '#64748b';
            p.style.fontSize = '14px';
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
            el.className = 'my-4';
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
            wrap.style.padding = (props.padding || '20') + 'px';
            
            for (let i = 0; i < (props.columns || 3); i++) {
                const col = document.createElement('div');
                col.style.padding = '30px';
                col.style.backgroundColor = '#f8fafc';
                col.style.border = '2px dashed #e2e8f0';
                col.style.borderRadius = '8px';
                col.style.textAlign = 'center';
                col.style.color = '#94a3b8';
                col.textContent = 'Kolom ' + (i + 1);
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
            el.style.borderRadius = '8px';
            el.innerHTML = '<div style=\"color:#94a3b8;text-align:center;\">📦 Section</div>';
            return el;
        }
        case 'video': {
            const el = document.createElement('div');
            el.className = 'mb-4';
            el.style.width = (props.width || '100') + '%';
            el.style.aspectRatio = props.aspectRatio || '16/9';
            el.style.backgroundColor = '#000';
            el.style.borderRadius = '12px';
            el.style.display = 'flex';
            el.style.alignItems = 'center';
            el.style.justifyContent = 'center';
            el.style.color = 'white';
            
            if (!props.url) {
                el.innerHTML = '<div style=\"text-align:center;\">🎬 Masukkan URL video</div>';
            } else {
                el.innerHTML = '<div style=\"text-align:center;\">▶️ Video</div>';
            }
            return el;
        }
        default:
            return document.createTextNode('');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dynamic-content');
    if (!container || !window.dynamicPageState || !Array.isArray(window.dynamicPageState)) {
        container.innerHTML = '<p style=\"text-align:center;color:#94a3b8;\">Belum ada konten</p>';
        return;
    }

    container.innerHTML = '';
    for (const block of window.dynamicPageState) {
        container.appendChild(renderBlockSafe(block));
    }
});
";

$this->registerJs($js, \yii\web\View::POS_END);