/**
 * CARD WIDGET - Dashboard Builder Card Component
 * 
 * Full metadata-driven card widget with:
 * - Icon Picker (search, categories, preview)
 * - Color Picker (HEX, RGB, RGBA, HSL)
 * - Dynamic Data Source (Database, API, Static, etc.)
 * - Filter Builder (AND/OR/Nested)
 * - Live Preview
 * - All configuration from registry/engine
 */

class CardWidget {
    constructor() {
        this.config = null;
        this.iconPickerInstance = null;
        this.colorPickerInstance = null;
        this.filterBuilderInstance = null;
        this.selectedBlockId = null;
        this.cardConfigCache = null;
        this.previewTimeout = null;
        this.loadedLibraries = {};
    }

    async init() {
        await this.loadConfig();
        this.setupCardPreview();
        if (this.config) {
            this.refreshAllCardPreviews();
        }
    }

    async loadConfig() {
        try {
            const baseUrl = window.cardConfigBaseUrl || '/card';
            const resp = await fetch(`${baseUrl}/get-config`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await resp.json();
            if (result.success) {
                this.config = result.data;
                window.cardWidgetConfig = this.config;
            }
        } catch (e) {
        }
    }

    setupCardPreview() {
        const canvas = document.getElementById('canvas');
        if (canvas) {
            const observer = new MutationObserver(() => {
                this.refreshAllCardPreviews();
            });
            observer.observe(canvas, { childList: true, subtree: true, attributes: false });
        }
    }

    refreshAllCardPreviews() {
        if (!this.config) return;
        document.querySelectorAll('[data-card-preview]').forEach(el => {
            const blockId = el.dataset.cardPreview;
            const state = window.pageState || [];
            const block = this.findBlock(state, blockId);
            if (block) {
                el.innerHTML = this.renderCardPreview(block.props);
                if (window.IconRegistry) window.IconRegistry.afterRender(el);
            }
        });
    }

    renderCardPreview(props) {
        const shadowMap = {
            'none': 'none',
            'sm': '0 1px 2px rgba(0,0,0,0.05)',
            'md': '0 4px 6px -1px rgba(0,0,0,0.1)',
            'lg': '0 10px 15px -3px rgba(0,0,0,0.1)',
            'xl': '0 20px 25px -5px rgba(0,0,0,0.1)',
            '2xl': '0 25px 50px -12px rgba(0,0,0,0.25)',
            'inner': 'inset 0 2px 4px rgba(0,0,0,0.05)',
            'colored': '0 10px 15px -3px rgba(99,102,241,0.2)',
        };

        const padding = (props.padding || '24') + 'px';
        const borderRadius = (props.borderRadius || '12') + 'px';
        const shadow = shadowMap[props.shadow] || shadowMap.md;
        const bg = this.getBackgroundStyle(props);
        const border = props.border && props.border !== 'none'
            ? `1px ${props.border} ${props.borderColor || '#e2e8f0'}`
            : 'none';
        const align = props.alignment || 'left';
        const width = props.width ? (props.width + '%') : '100%';
        const height = props.height && props.height !== 'auto' ? props.height + 'px' : 'auto';
        const textColor = props.textColor || '#1e293b';
        const fontSize = (props.fontSize || '16') + 'px';
        const fontWeight = props.fontWeight || '400';
        const fontFamily = props.fontFamily || '';
        const lineHeight = props.lineHeight || '1.5';

        let iconHtml = '';
        if (props.showIcon !== false && props.icon) {
            const iconSize = props.iconSize || '48';
            const iconColor = props.iconColor || '#6366f1';
            const iconBg = props.iconBackground || '';
            const iconShape = props.iconShape || 'none';
            const iconOpacity = (parseInt(props.iconOpacity) || 100) / 100;
            const iconRotation = props.iconRotation || '0';
            const shapeCss = iconShape === 'circle' ? 'border-radius:50%;' :
                iconShape === 'rounded' ? 'border-radius:12px;' :
                iconShape === 'square' ? 'border-radius:4px;' : '';
            const bgCss = iconBg ? `background:${iconBg};padding:12px;${shapeCss}` : '';

            const iconLib = props.iconLibrary || 'heroicons';
            const iconHtmlContent = window.IconRegistry
                ? window.IconRegistry.renderIcon(iconLib, props.icon, {
                    size: parseInt(iconSize),
                    color: iconColor,
                    fill: props.iconFill,
                    weight: props.iconWeight
                  })
                : `<span style="font-size:${iconSize}px;color:${iconColor}">${props.icon}</span>`;

            iconHtml = `
                <div style="text-align:${align};margin-bottom:12px;opacity:${iconOpacity};">
                    <span style="display:inline-flex;align-items:center;justify-content:center;${bgCss}transform:rotate(${iconRotation}deg);">
                        ${iconHtmlContent}
                    </span>
                </div>
            `;
        }

        let contentHtml = '';
        if (props.showTitle !== false && props.title) {
            contentHtml += `<div style="font-size:${fontSize};font-weight:${fontWeight || '700'};color:${textColor};line-height:${lineHeight};${fontFamily ? 'font-family:' + fontFamily + ';' : ''}margin-bottom:${props.subtitle ? '4px' : '8px'}">${this.escapeHtml(props.title)}</div>`;
        }
        if (props.showSubtitle !== false && props.subtitle) {
            contentHtml += `<div style="font-size:${Math.max(parseInt(fontSize) - 2, 12)}px;color:${textColor}cc;margin-bottom:8px">${this.escapeHtml(props.subtitle)}</div>`;
        }
        const descText = props.description || props.content || '';
        if (props.showDescription !== false && descText) {
            contentHtml += `<div style="font-size:${Math.max(parseInt(fontSize) - 4, 12)}px;color:${textColor}99;margin-bottom:8px">${this.escapeHtml(descText)}</div>`;
        }
        if (props.showValue !== false && props.datasource !== 'static') {
            const valueDisplay = props._previewValue || '--';
            contentHtml += `<div style="font-size:${Math.max(parseInt(fontSize) + 8, 24)}px;font-weight:700;color:${textColor};margin-top:8px">${valueDisplay}</div>`;
        }

        return `
            <div style="width:${width};height:${height};padding:${padding};background:${bg};border-radius:${borderRadius};box-shadow:${shadow};border:${border};text-align:${align};">
                ${iconHtml}
                ${contentHtml}
                <div style="margin-top:12px;font-size:11px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:8px;">
                    <span>${props.datasource === 'database' ? (props.aggregate || 'COUNT') : props.datasource}</span>
                    ${props.tableName ? ' · ' + this.escapeHtml(props.tableName) : ''}
                    ${props.column ? ' · ' + this.escapeHtml(props.column) : ''}
                </div>
            </div>
        `;
    }

    getBackgroundStyle(props) {
        const bgType = props.bgType || 'solid';
        switch (bgType) {
            case 'solid':
                return props.bgColor || '#ffffff';
            case 'gradient':
                return props.bgGradient || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            case 'image':
                return props.bgImage ? `url(${props.bgImage}) center/cover no-repeat` : '#ffffff';
            case 'pattern':
                return props.bgColor || '#ffffff';
            case 'glass':
                return `rgba(255,255,255,${props.bgBlur ? 0.15 : 0.25})`;
            case 'transparent':
                return 'transparent';
            default:
                return props.bgColor || '#ffffff';
        }
    }

    getIconCssClass(library, iconName) {
        return window.IconRegistry ? window.IconRegistry.getCssClass(library, iconName) : 'hero-icon';
    }

    buildCardPreviewHtml(props) {
        const container = document.createElement('div');
        container.style.cssText = 'position:relative;border-radius:12px;overflow:hidden;transition:all 0.2s;';
        container.innerHTML = this.renderCardPreview(props);
        return container.innerHTML;
    }

    async refreshCardBlockData(blockId) {
        if (!blockId) return;
        const state = window.pageState || [];
        const block = this.findBlock(state, blockId);
        if (!block || block.type !== 'card') return;
        if (block.props.datasource !== 'database') return;

        try {
            const baseUrl = window.cardConfigBaseUrl || '/card';
            const url = `${baseUrl}/preview`;
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ config: block.props })
            });
            if (!resp.ok) {
                return;
            }
            const result = await resp.json();
            if (result.success && result.data) {
                block.props._previewValue = result.data.formatted || result.data.value;
                this.triggerRender(blockId);
            } else if (result.data && result.data.formatted) {
                block.props._previewValue = result.data.formatted;
                block.props._previewError = null;
                this.triggerRender(blockId);
            }
        } catch (e) {
        }
    }

    findBlock(state, blockId) {
        for (let block of state) {
            if (block.id === blockId) return block;
            if (block.children) {
                const found = this.findBlock(block.children, blockId);
                if (found) return found;
            }
        }
        return null;
    }

    triggerRender(blockId) {
        const el = document.querySelector(`[data-card-preview="${blockId}"]`);
        if (el) {
            const state = window.pageState || [];
            const block = this.findBlock(state, blockId);
            if (block) {
                el.innerHTML = this.renderCardPreview(block.props);
                if (window.IconRegistry) window.IconRegistry.afterRender(el);
            }
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
}


/**
 * ICON PICKER - Modern icon selection component
 * Figma / FlutterFlow style with live preview, search, categories
 */
class IconPicker {
    constructor(options = {}) {
        this.container = options.container || null;
        this.onSelect = options.onSelect || (() => {});
        this.currentValue = options.value || '';
        this.currentLibrary = options.library || 'heroicons';
        this.recentlyUsed = JSON.parse(localStorage.getItem('iconPicker_recent') || '[]');
        this.favorites = JSON.parse(localStorage.getItem('iconPicker_favorites') || '[]');
        this.searchResults = [];
        this.isOpen = false;
        this.allIcons = [];
        this._activeTab = 'all';
    }

    async open(container, options = {}) {
        if (this.isOpen) return;
        this.isOpen = true;
        this.currentValue = options.value || this.currentValue;
        this.currentLibrary = options.library || this.currentLibrary;
        this.onSelect = options.onSelect || this.onSelect;
        this._activeTab = 'all';

        const overlay = document.createElement('div');
        overlay.className = 'ip-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999;background:rgba(15,23,42,0.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;animation:ipFadeIn 0.2s ease;';
        overlay.onclick = (e) => { if (e.target === overlay) this.close(); };

        const panel = document.createElement('div');
        panel.style.cssText = 'background:#fff;border-radius:20px;width:800px;max-width:92vw;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 32px 64px -16px rgba(0,0,0,0.3);animation:ipSlideUp 0.25s cubic-bezier(0.16,1,0.3,1);overflow:hidden;';

        panel.innerHTML = `
        <style>
        @keyframes ipFadeIn{from{opacity:0}to{opacity:1}}
        @keyframes ipSlideUp{from{opacity:0;transform:translateY(30px) scale(0.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        @keyframes ipPulse{0%,100%{opacity:1}50%{opacity:0.5}}
        .ip-header{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
        .ip-header-title{font-weight:700;font-size:16px;color:#0f172a;display:flex;align-items:center;gap:10px;}
        .ip-header-actions{display:flex;gap:8px;}
        .ip-header-btn{padding:7px 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:12px;font-weight:500;color:#475569;transition:all 0.15s;display:flex;align-items:center;gap:5px;}
        .ip-header-btn:hover{border-color:#c7d2fe;background:#eef2ff;color:#4338ca;}
        .ip-header-btn.active{background:#6366f1;color:#fff;border-color:#6366f1;}
        .ip-header-btn.danger{border-color:#fecaca;color:#ef4444;}
        .ip-header-btn.danger:hover{background:#fef2f2;border-color:#fca5a5;}
        .ip-search-wrap{padding:14px 24px;border-bottom:1px solid #f1f5f9;flex-shrink:0;}
        .ip-search-wrap .ip-search-inner{display:flex;align-items:center;background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;padding:0 14px;transition:border-color 0.2s;}
        .ip-search-wrap .ip-search-inner:focus-within{border-color:#6366f1;background:#fff;}
        .ip-search-wrap .ip-search-inner .ip-search-icon{color:#94a3b8;font-size:18px;margin-right:10px;flex-shrink:0;}
        .ip-search-wrap .ip-search-inner input{border:none;background:transparent;padding:11px 0;font-size:14px;outline:none;width:100%;color:#0f172a;}
        .ip-search-wrap .ip-search-inner input::placeholder{color:#94a3b8;}
        .ip-search-wrap .ip-search-inner .ip-clear-search{color:#94a3b8;cursor:pointer;padding:4px;border-radius:6px;display:none;}
        .ip-search-wrap .ip-search-inner .ip-clear-search:hover{background:#e2e8f0;color:#475569;}
        .ip-lib-bar{padding:10px 24px;display:flex;gap:6px;overflow-x:auto;border-bottom:1px solid #f1f5f9;flex-shrink:0;scrollbar-width:none;}
        .ip-lib-bar::-webkit-scrollbar{display:none;}
        .ip-lib-btn{padding:6px 16px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;font-size:12px;font-weight:500;cursor:pointer;white-space:nowrap;transition:all 0.15s;color:#475569;flex-shrink:0;}
        .ip-lib-btn:hover{border-color:#c7d2fe;background:#eef2ff;color:#4338ca;}
        .ip-lib-btn.active{background:#6366f1;color:#fff;border-color:#6366f1;}
        .ip-cat-bar{padding:8px 24px;display:flex;gap:5px;overflow-x:auto;border-bottom:1px solid #f1f5f9;flex-shrink:0;scrollbar-width:none;}
        .ip-cat-bar::-webkit-scrollbar{display:none;}
        .ip-cat-btn{padding:4px 14px;border-radius:6px;border:none;background:transparent;font-size:12px;cursor:pointer;white-space:nowrap;transition:all 0.15s;color:#64748b;}
        .ip-cat-btn:hover{background:#f1f5f9;color:#334155;}
        .ip-cat-btn.active{background:#eef2ff;color:#4338ca;font-weight:600;}
        .ip-grid-wrap{flex:1;overflow:hidden;position:relative;}
        .ip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(60px,1fr));gap:6px;padding:16px 24px;overflow-y:auto;max-height:400px;}
        .ip-icon-item{width:60px;height:60px;display:flex;align-items:center;justify-content:center;border-radius:12px;border:2px solid transparent;cursor:pointer;transition:all 0.12s;position:relative;color:#475569;}
        .ip-icon-item i,.ip-icon-item span{pointer-events:none;transition:transform 0.15s;}
        .ip-icon-item:hover{background:#eef2ff;border-color:#c7d2fe;transform:translateY(-1px);box-shadow:0 4px 12px rgba(99,102,241,0.1);}
        .ip-icon-item:hover i,.ip-icon-item:hover span{transform:scale(1.1);}
        .ip-icon-item.selected{background:#eef2ff;border-color:#6366f1;box-shadow:0 4px 12px rgba(99,102,241,0.2);}
        .ip-icon-item.selected i,.ip-icon-item.selected span{color:#4338ca;}
        .ip-icon-item .ip-fav-star{position:absolute;top:2px;right:2px;font-size:10px;color:#f59e0b;opacity:0;transition:opacity 0.15s;pointer-events:none;}
        .ip-icon-item.is-fav .ip-fav-star{opacity:1;}
        .ip-icon-item .ip-tooltip{position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);font-size:9px;color:#fff;background:#0f172a;padding:2px 8px;border-radius:4px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity 0.15s;z-index:2;}
        .ip-icon-item:hover .ip-tooltip{opacity:1;}
        .ip-empty{grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8;}
        .ip-empty .ip-empty-icon{font-size:56px;display:block;margin-bottom:16px;opacity:0.5;}
        .ip-empty .ip-empty-text{font-size:15px;font-weight:600;color:#64748b;margin-bottom:4px;}
        .ip-empty .ip-empty-hint{font-size:13px;color:#94a3b8;}
        .ip-footer{padding:14px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;background:#fafafa;}
        .ip-preview{display:flex;align-items:center;gap:14px;}
        .ip-preview-box{width:48px;height:48px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:28px;color:#4338ca;flex-shrink:0;transition:all 0.2s;}
        .ip-preview-info .ip-preview-name{font-size:14px;font-weight:600;color:#0f172a;}
        .ip-preview-info .ip-preview-lib{font-size:12px;color:#94a3b8;margin-top:2px;}
        .ip-preview-info .ip-preview-none{font-size:13px;color:#94a3b8;font-weight:400;}
        .ip-footer-actions{display:flex;gap:8px;}
        .ip-btn{padding:9px 22px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;border:none;}
        .ip-btn-secondary{background:#fff;border:1px solid #e2e8f0;color:#475569;}
        .ip-btn-secondary:hover{background:#f8fafc;border-color:#cbd5e1;}
        .ip-btn-primary{background:#6366f1;color:#fff;}
        .ip-btn-primary:hover{background:#4f46e5;transform:translateY(-1px);box-shadow:0 4px 12px rgba(99,102,241,0.3);}
        .ip-loading{display:flex;align-items:center;justify-content:center;padding:40px;color:#94a3b8;font-size:14px;}
        .ip-spinner{width:20px;height:20px;border:2px solid #e2e8f0;border-top-color:#6366f1;border-radius:50%;animation:ipSpin 0.6s linear infinite;margin-right:10px;display:inline-block;vertical-align:middle;}
        @keyframes ipSpin{to{transform:rotate(360deg)}}
        </style>
        <div class="ip-header">
            <div class="ip-header-title">
                <span style="font-size:22px;">🎨</span>
                <span>Pilih Icon</span>
            </div>
            <div class="ip-header-actions">
                <button class="ip-header-btn" data-tab="all">Semua</button>
                <button class="ip-header-btn" data-tab="favorites">★ Favorites <span class="fav-count">${this.favorites.length}</span></button>
                <button class="ip-header-btn" data-tab="recent">🕐 Recent</button>
                <button class="ip-header-btn danger" data-tab="clear">✕ Clear</button>
            </div>
        </div>
        <div class="ip-search-wrap">
            <div class="ip-search-inner">
                <span class="ip-search-icon">🔍</span>
                <input type="text" class="ip-search-input" placeholder="Cari icon... (cth: user, chart, setting, home, dll)" autofocus>
                <span class="ip-clear-search" style="display:none;">✕</span>
            </div>
        </div>
        <div class="ip-lib-bar"></div>
        <div class="ip-cat-bar"></div>
        <div class="ip-grid-wrap">
            <div class="ip-grid"></div>
        </div>
        <div class="ip-footer">
            <div class="ip-preview">
                <div class="ip-preview-box">
                    ${this._renderPreviewIcon(this.currentLibrary, this.currentValue || 'star')}
                </div>
                <div class="ip-preview-info">
                    <div class="ip-preview-name">${this.currentValue || 'Belum ada icon dipilih'}</div>
                    <div class="ip-preview-lib">${this.getLibraryLabel(this.currentLibrary)}</div>
                </div>
            </div>
            <div class="ip-footer-actions">
                <button class="ip-btn ip-btn-secondary">Batal</button>
                <button class="ip-btn ip-btn-primary" ${this.currentValue ? '' : 'disabled'}>Pilih Icon</button>
            </div>
        </div>
        `;

        overlay.appendChild(panel);
        document.body.appendChild(overlay);

        this.overlay = overlay;
        this.panel = panel;
        this.gridEl = panel.querySelector('.ip-grid');
        this.searchInput = panel.querySelector('.ip-search-input');
        this.libBar = panel.querySelector('.ip-lib-bar');
        this.catBar = panel.querySelector('.ip-cat-bar');
        this.previewBox = panel.querySelector('.ip-preview-box');
        this.previewName = panel.querySelector('.ip-preview-name');
        this.previewLib = panel.querySelector('.ip-preview-lib');
        this.clearBtn = panel.querySelector('.ip-clear-search');

        this.setupLibBar();
        this.setupHandlers();
        await this.loadIcons(this.currentLibrary);
        this.renderIcons();

        if (window.IconRegistry && this.currentLibrary === 'heroicons') {
            var svg = this.previewBox.querySelector('svg');
            var iconName = this.currentValue || 'star';
            if (svg && iconName) {
                window.IconRegistry.fetchAndRender(iconName, svg);
            }
        } else if (window.IconRegistry) {
            window.IconRegistry.afterRender(this.previewBox);
        }

        setTimeout(() => this.searchInput.focus(), 150);
    }

    close() {
        if (this.overlay) {
            this.overlay.style.opacity = '0';
            this.overlay.style.transition = 'opacity 0.15s';
            setTimeout(() => { if (this.overlay) { this.overlay.remove(); this.overlay = null; } }, 150);
        }
        this.isOpen = false;
    }

    getLibraryLabel(lib) {
        const labels = {
            'material-symbols': 'Material Symbols',
            'tabler': 'Tabler Icons',
            'heroicons': 'Heroicons',
            'lucide': 'Lucide',
            'phosphor': 'Phosphor',
            'remix': 'Remix Icon',
            'font-awesome': 'Font Awesome',
            'bootstrap-icons': 'Bootstrap Icons',
            'feather': 'Feather'
        };
        return labels[lib] || lib;
    }

    setupLibBar() {
        const allowed = ['heroicons', 'lucide'];
        const libs = (window.cardWidgetConfig?.iconLibraries || []).filter(lib => allowed.includes(lib.value));
        if (libs.length === 0) {
            libs.push({ value: 'heroicons', label: 'Heroicons' }, { value: 'lucide', label: 'Lucide' });
        }
        if (!libs.find(l => l.value === this.currentLibrary)) {
            this.currentLibrary = libs[0]?.value || 'heroicons';
        }
        this.libBar.innerHTML = libs.map(lib => `
            <button class="ip-lib-btn ${lib.value === this.currentLibrary ? 'active' : ''}" data-lib="${lib.value}">${lib.label}</button>
        `).join('');
    }

    setupHandlers() {
        const qs = (s) => this.panel.querySelector(s);

        qs('.ip-btn-secondary').onclick = () => this.close();
        qs('.ip-btn-primary').onclick = () => {
            if (this.currentValue) {
                this.addToRecent(this.currentValue);
                this.onSelect(this.currentValue);
                this.close();
            }
        };

        this.panel.querySelectorAll('.ip-header-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                this.panel.querySelectorAll('.ip-header-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                if (tab === 'all') { this._activeTab = 'all'; this.renderIcons(); }
                else if (tab === 'favorites') { this._activeTab = 'favorites'; this.showFavorites(); }
                else if (tab === 'recent') { this._activeTab = 'recent'; this.showRecent(); }
                else if (tab === 'clear') { this.currentValue = ''; this.onSelect(''); this.close(); }
            });
        });

        this.searchInput.addEventListener('input', () => {
            this.clearBtn.style.display = this.searchInput.value ? 'flex' : 'none';
            clearTimeout(this._searchTm);
            this._searchTm = setTimeout(() => this.renderIcons(), 120);
        });

        this.clearBtn.addEventListener('click', () => {
            this.searchInput.value = '';
            this.clearBtn.style.display = 'none';
            this.renderIcons();
            this.searchInput.focus();
        });

        this.libBar.addEventListener('click', (e) => {
            const btn = e.target.closest('.ip-lib-btn');
            if (!btn) return;
            this.libBar.querySelectorAll('.ip-lib-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            this.currentLibrary = btn.dataset.lib;
            this.loadIcons(this.currentLibrary).then(() => this.renderIcons());
        });
    }

    async loadIcons(library) {
        if (this.allIcons.length > 0 && this._currentLib === library) return;
        this._currentLib = library;
        this.gridEl.innerHTML = '<div class="ip-loading"><span class="ip-spinner"></span>Memuat icon...</div>';

        try {
            const baseUrl = window.cardConfigBaseUrl || '/card';
            const resp = await fetch(`${baseUrl}/search-icons?library=${encodeURIComponent(library)}&query=`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await resp.json();
            this.allIcons = result.success ? result.data : [];
        } catch (e) {
            this.allIcons = [];
        }
    }

    _buildIconHtml(icon) {
        const isSelected = icon.name === this.currentValue;
        const isFav = this.favorites.includes(icon.name);
        let innerHtml = '';

        const lib = this.currentLibrary;
        if (lib === 'lucide') {
            innerHtml = `<i data-lucide="${icon.name}" style="width:24px;height:24px;"></i>`;
        } else if (lib === 'heroicons') {
            innerHtml = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" data-heroicon="${icon.name}"><rect x="2" y="2" width="20" height="20" rx="4" fill="currentColor" opacity="0.1"/><circle cx="12" cy="12" r="7" stroke="currentColor" stroke-dasharray="4 3" opacity="0.4"/><path d="M12 7v5m0 4h.01" stroke="currentColor" stroke-width="2" opacity="0.5"/></svg>`;
        } else {
            innerHtml = `<span style="font-size:22px;">${icon.name}</span>`;
        }

        return `<div class="ip-icon-item${isSelected ? ' selected' : ''}${isFav ? ' favorite' : ''}" data-icon="${icon.name}" title="${icon.name}">${innerHtml}</div>`;
    }

    renderIcons() {
        const query = this.searchInput.value.toLowerCase().trim();
        let icons = this.allIcons;

        if (query) {
            icons = icons.filter(icon => {
                const name = icon.name.toLowerCase();
                const terms = (icon.searchTerms || '').toLowerCase();
                return name.includes(query) || terms.includes(query);
            });
        }

        if (icons.length === 0) {
            this.gridEl.innerHTML = `<div class="ip-empty">
                <div class="ip-empty-icon">${query ? '🔍' : '📦'}</div>
                <div class="ip-empty-text">${query ? `Tidak ditemukan icon untuk "${query}"` : 'Tidak ada icon tersedia'}</div>
                <div class="ip-empty-hint">${query ? 'Coba kata kunci lain' : 'Pilih library icon lain'}</div>
            </div>`;
            this.catBar.innerHTML = '';
            return;
        }

        const categories = [...new Set(icons.map(i => i.category))];
        this._updateCategories(categories);

        this.gridEl.innerHTML = icons.map(icon => this._buildIconHtml(icon)).join('');
        this._attachIconEvents();
        if (window.IconRegistry) window.IconRegistry.afterRender(this.gridEl);
    }

    _attachIconEvents() {
        this.gridEl.querySelectorAll('.ip-icon-item').forEach(el => {
            el.addEventListener('click', () => this._selectIcon(el.dataset.icon));
            el.addEventListener('mouseenter', () => this._updatePreview(el.dataset.icon));
        });
    }

    _selectIcon(iconName) {
        this.currentValue = iconName;
        this.gridEl.querySelectorAll('.ip-icon-item').forEach(el => {
            el.classList.toggle('selected', el.dataset.icon === iconName);
        });
        const btn = this.panel.querySelector('.ip-btn-primary');
        if (btn) btn.disabled = false;
        this._updatePreview(iconName);
    }

    _updatePreview(iconName) {
        if (!iconName) return;
        const lib = this.currentLibrary;
        this.previewName.textContent = iconName;
        this.previewLib.textContent = this.getLibraryLabel(lib);

        if (lib === 'heroicons') {
            var url = 'https://cdn.jsdelivr.net/npm/heroicons@2/24/outline/' + encodeURIComponent(iconName) + '.svg';
            var self = this;
            fetch(url)
                .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
                .then(function(svg) {
                    var match = svg.match(/<svg[^>]*>([\s\S]*?)<\/svg>/i);
                    var inner = match ? match[1].trim() : '';
                    if (inner) {
                        self.previewBox.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">' + inner + '</svg>';
                    } else {
                        self.previewBox.innerHTML = '<span style="color:#ff9800;font-weight:bold;">?</span>';
                    }
                })
                .catch(function(err) {
                    self.previewBox.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="9" stroke="#ef4444" stroke-dasharray="3 3"/><path d="M12 8v4m0 4h.01" stroke="#ef4444" stroke-width="2.5"/></svg>';
                });
            return;
        }

        try {
            var html = this._renderPreviewIcon(lib, iconName);
            if (!html || html.length < 10) {
                html = '<span style="color:red;font-size:28px;">?</span>';
            }
            this.previewBox.innerHTML = html;
            if (window.IconRegistry && lib !== 'heroicons') {
                window.IconRegistry.afterRender(this.previewBox);
            }
        } catch (e) {
            this.previewBox.innerHTML = '<span style="color:red;font-size:28px;">ERR</span>';
        }
    }

    _renderPreviewIcon(lib, name) {
        if (lib === 'heroicons') {
            if (window.IconRegistry && window.IconRegistry.isHeroiconCached(name)) {
                var cached = window.IconRegistry.getCachedHeroicon(name);
                if (cached) {
                    return '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' + cached + '</svg>';
                }
            }
            return '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-heroicon="' + name + '"><rect x="2" y="2" width="20" height="20" rx="4" fill="#6366f1" opacity="0.08"/><circle cx="12" cy="12" r="7" stroke="#6366f1" stroke-dasharray="4 3" opacity="0.3"/><path d="M12 7v5m0 4h.01" stroke="#6366f1" stroke-width="2" opacity="0.5"/></svg>';
        }
        if (lib === 'lucide' && window.IconRegistry) {
            return window.IconRegistry.renderIcon(lib, name, { size: 28, color: '#6366f1' });
        }
        return `<span style="font-size:18px;">${name ? name.charAt(0).toUpperCase() : '?'}</span>`;
    }

    _updateCategories(categories) {
        if (this.searchInput.value.trim()) { this.catBar.innerHTML = ''; return; }
        const active = this.catBar.dataset.active || '';
        this.catBar.innerHTML = `<button class="ip-cat-btn ${!active ? 'active' : ''}" data-cat="">All</button>` +
            categories.map(c => `<button class="ip-cat-btn ${c === active ? 'active' : ''}" data-cat="${c}">${c}</button>`).join('');

        this.catBar.querySelectorAll('.ip-cat-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.catBar.querySelectorAll('.ip-cat-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.catBar.dataset.active = btn.dataset.cat;
                this._filterByCategory(btn.dataset.cat);
            });
        });
    }

    _filterByCategory(category) {
        if (!category) { this.renderIcons(); return; }
        const query = this.searchInput.value.toLowerCase().trim();
        let icons = this.allIcons.filter(icon => icon.category === category);
        if (query) {
            icons = icons.filter(icon => {
                const name = icon.name.toLowerCase();
                const terms = (icon.searchTerms || '').toLowerCase();
                return name.includes(query) || terms.includes(query);
            });
        }
        this.gridEl.innerHTML = icons.map(icon => this._buildIconHtml(icon)).join('');
        this._attachIconEvents();
    }

    showFavorites() {
        if (this.favorites.length === 0) {
            this.gridEl.innerHTML = `<div class="ip-empty"><div class="ip-empty-icon">★</div><div class="ip-empty-text">Belum ada favorit</div><div class="ip-empty-hint">Klik ★ pada icon untuk menambah ke favorit</div></div>`;
            this.catBar.innerHTML = '';
            return;
        }
        const icons = this.allIcons.filter(icon => this.favorites.includes(icon.name));
        this.gridEl.innerHTML = icons.map(icon => this._buildIconHtml(icon)).join('');
        this._attachIconEvents();
        this.catBar.innerHTML = '';
    }

    showRecent() {
        if (this.recentlyUsed.length === 0) {
            this.gridEl.innerHTML = `<div class="ip-empty"><div class="ip-empty-icon">🕐</div><div class="ip-empty-text">Belum ada icon yang baru digunakan</div></div>`;
            this.catBar.innerHTML = '';
            return;
        }
        const icons = this.allIcons.filter(icon => this.recentlyUsed.includes(icon.name));
        this.gridEl.innerHTML = icons.map(icon => this._buildIconHtml(icon)).join('');
        this._attachIconEvents();
        this.catBar.innerHTML = '';
    }

    addToRecent(iconName) {
        if (!iconName) return;
        this.recentlyUsed = [iconName, ...this.recentlyUsed.filter(i => i !== iconName)].slice(0, 20);
        localStorage.setItem('iconPicker_recent', JSON.stringify(this.recentlyUsed));
    }

    toggleFavorite(iconName) {
        if (this.favorites.includes(iconName)) {
            this.favorites = this.favorites.filter(i => i !== iconName);
        } else {
            this.favorites.push(iconName);
        }
        localStorage.setItem('iconPicker_favorites', JSON.stringify(this.favorites));
    }
}


/**
 * COLOR PICKER - Modern color picker
 * Supports HEX, RGB, RGBA, HSL, Opacity, Presets
 */
class ColorPicker {
    constructor(options = {}) {
        this.container = options.container || null;
        this.onChange = options.onChange || (() => {});
        this.currentValue = options.value || '#6366f1';
        this.isOpen = false;
    }

    open(container, options = {}) {
        if (this.isOpen) return;
        this.isOpen = true;
        this.currentValue = options.value || this.currentValue;
        this.onChange = options.onChange || this.onChange;
        this.format = this.detectFormat(this.currentValue);

        const popup = document.createElement('div');
        popup.className = 'card-color-picker-popup';
        popup.style.cssText = `
            position:absolute;z-index:9999;background:#fff;border-radius:12px;
            padding:16px;width:260px;box-shadow:0 20px 60px rgba(0,0,0,0.15);
            border:1px solid #e2e8f0;animation:fadeIn 0.15s ease;
        `;

        const rgb = this.hexToRgb(this.currentValue);
        let r = rgb.r, g = rgb.g, b = rgb.b, a = 1;

        popup.innerHTML = `
            <div style="margin-bottom:12px;">
                <div style="width:100%;height:160px;border-radius:8px;background:linear-gradient(to top,#000,transparent),linear-gradient(to right,#fff,transparent),linear-gradient(135deg,red,yellow,lime,cyan,blue,magenta,red);position:relative;cursor:crosshair;" class="cp-canvas">
                    <div class="cp-dot" style="position:absolute;width:14px;height:14px;border:2px solid #fff;border-radius:50%;box-shadow:0 0 4px rgba(0,0,0,0.3);transform:translate(-50%,-50%);pointer-events:none;left:70%;top:30%;"></div>
                </div>
                <div style="margin-top:8px;">
                    <input type="range" class="cp-hue" min="0" max="360" value="${this.rgbToHsl(r,g,b).h}" style="width:100%;height:8px;border-radius:4px;background:linear-gradient(to right,red,yellow,lime,cyan,blue,magenta,red);-webkit-appearance:none;appearance:none;cursor:pointer;">
                </div>
                <div style="margin-top:6px;">
                    <input type="range" class="cp-opacity" min="0" max="100" value="${a * 100}" style="width:100%;height:8px;border-radius:4px;background:linear-gradient(to right,transparent,${this.currentValue});-webkit-appearance:none;appearance:none;cursor:pointer;">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <div style="display:flex;gap:4px;flex-wrap:wrap;" class="cp-format-btns">
                    <button class="cp-format-btn ${this.format === 'hex' ? 'active' : ''}" data-format="hex" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;background:${this.format === 'hex' ? '#eef2ff' : '#fff'};cursor:pointer;font-size:11px;">HEX</button>
                    <button class="cp-format-btn ${this.format === 'rgb' ? 'active' : ''}" data-format="rgb" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;background:${this.format === 'rgb' ? '#eef2ff' : '#fff'};cursor:pointer;font-size:11px;">RGB</button>
                    <button class="cp-format-btn ${this.format === 'rgba' ? 'active' : ''}" data-format="rgba" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;background:${this.format === 'rgba' ? '#eef2ff' : '#fff'};cursor:pointer;font-size:11px;">RGBA</button>
                    <button class="cp-format-btn ${this.format === 'hsl' ? 'active' : ''}" data-format="hsl" style="padding:4px 8px;border-radius:6px;border:1px solid #e2e8f0;background:${this.format === 'hsl' ? '#eef2ff' : '#fff'};cursor:pointer;font-size:11px;">HSL</button>
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <input type="text" class="cp-value-input" value="${this.currentValue}" style="width:100%;padding:8px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;">
            </div>
            <div style="margin-bottom:6px;">
                <div style="font-size:11px;color:#94a3b8;margin-bottom:6px;">Preset Colors</div>
                <div style="display:flex;gap:4px;flex-wrap:wrap;" class="cp-presets">
                    ${['#ef4444','#f97316','#eab308','#22c55e','#06b6d4','#6366f1','#a855f7','#ec4899','#78716c','#64748b','#1e293b','#ffffff'].map(c => `
                        <div class="cp-preset-color" data-color="${c}" style="width:24px;height:24px;border-radius:6px;background:${c};cursor:pointer;border:2px solid ${c === '#ffffff' ? '#e2e8f0' : 'transparent'};"></div>
                    `).join('')}
                </div>
            </div>
        `;

        popup.style.position = 'absolute';
        const rect = container.getBoundingClientRect();
        popup.style.left = Math.min(rect.left, window.innerWidth - 280) + 'px';
        popup.style.top = (rect.bottom + 4) + 'px';

        this.popup = popup;
        container.appendChild(popup);
        this.bindEvents(container);

        setTimeout(() => {
            const closeHandler = (e) => {
                if (!popup.contains(e.target) && e.target !== container && !container.contains(e.target)) {
                    this.close();
                    document.removeEventListener('click', closeHandler);
                }
            };
            setTimeout(() => document.addEventListener('click', closeHandler), 0);
        }, 0);
    }

    close() {
        if (this.popup) {
            this.popup.remove();
            this.popup = null;
        }
        this.isOpen = false;
    }

    bindEvents(container) {
        const canvas = this.popup.querySelector('.cp-canvas');
        const dot = this.popup.querySelector('.cp-dot');
        const hueSlider = this.popup.querySelector('.cp-hue');
        const opacitySlider = this.popup.querySelector('.cp-opacity');
        const valueInput = this.popup.querySelector('.cp-value-input');
        const formatBtns = this.popup.querySelectorAll('.cp-format-btn');
        const presets = this.popup.querySelectorAll('.cp-preset-color');

        canvas.addEventListener('click', (e) => {
            const rect = canvas.getBoundingClientRect();
            const x = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            const y = Math.max(0, Math.min(1, (e.clientY - rect.top) / rect.height));
            dot.style.left = (x * 100) + '%';
            dot.style.top = (y * 100) + '%';
            this.updateFromCanvas(x, y);
        });

        hueSlider.addEventListener('input', () => {
            const hue = parseInt(hueSlider.value);
            const hsl = this.hexToHsl(this.currentValue);
            const color = this.hslToHex(hue, hsl.s, hsl.l);
            this.updateColor(color);
        });

        opacitySlider.addEventListener('input', () => {
            const opacity = parseInt(opacitySlider.value) / 100;
            const rgb = this.hexToRgb(this.currentValue);
            this.currentValue = `rgba(${rgb.r},${rgb.g},${rgb.b},${opacity.toFixed(2)})`;
            this.format = 'rgba';
            this.updateDisplay();
        });

        valueInput.addEventListener('input', () => {
            this.currentValue = valueInput.value;
            this.format = this.detectFormat(this.currentValue);
            this.updateDisplay();
        });

        formatBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.format = btn.dataset.format;
                formatBtns.forEach(b => { b.style.background = '#fff'; b.classList.remove('active'); });
                btn.style.background = '#eef2ff';
                btn.classList.add('active');
                this.updateDisplay();
            });
        });

        presets.forEach(el => {
            el.addEventListener('click', () => {
                this.currentValue = el.dataset.color;
                this.format = this.detectFormat(this.currentValue);
                this.updateDisplay();
            });
        });
    }

    updateFromCanvas(x, y) {
        const hue = parseInt(this.popup.querySelector('.cp-hue').value);
        const sat = x * 100;
        const lig = (1 - y) * 100;
        this.currentValue = this.hslToHex(hue, sat, lig);
        this.format = 'hex';
        this.updateDisplay();
    }

    updateColor(color) {
        this.currentValue = color;
        this.updateDisplay();
    }

    updateDisplay() {
        const valueInput = this.popup?.querySelector('.cp-value-input');
        if (!valueInput) return;

        switch (this.format) {
            case 'hex':
                valueInput.value = this.toHex(this.currentValue);
                break;
            case 'rgb': {
                const c = this.toRgb(this.currentValue);
                valueInput.value = c;
                break;
            }
            case 'rgba': {
                const c = this.toRgba(this.currentValue);
                valueInput.value = c;
                break;
            }
            case 'hsl': {
                const c = this.toHsl(this.currentValue);
                valueInput.value = c;
                break;
            }
            default:
                valueInput.value = this.currentValue;
        }

        this.popup.querySelector('.cp-opacity').style.background = `linear-gradient(to right,transparent,${this.currentValue})`;
        this.currentValue = valueInput.value;
        this.onChange(this.currentValue);
    }

    detectFormat(color) {
        if (!color) return 'hex';
        if (color.startsWith('rgba')) return 'rgba';
        if (color.startsWith('rgb')) return 'rgb';
        if (color.startsWith('hsl')) return 'hsl';
        return 'hex';
    }

    hexToRgb(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
        const num = parseInt(hex, 16);
        return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
    }

    rgbToHsl(r, g, b) {
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;
        if (max === min) { h = s = 0; }
        else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) * 60; break;
                case g: h = ((b - r) / d + 2) * 60; break;
                case b: h = ((r - g) / d + 4) * 60; break;
            }
        }
        return { h: Math.round(h || 0), s: Math.round(s * 100), l: Math.round(l * 100) };
    }

    hexToHsl(hex) {
        const rgb = this.hexToRgb(hex);
        return this.rgbToHsl(rgb.r, rgb.g, rgb.b);
    }

    hslToHex(h, s, l) {
        s /= 100; l /= 100;
        const a = s * Math.min(l, 1 - l);
        const f = (n) => {
            const k = (n + h / 30) % 12;
            const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
            return Math.round(255 * color).toString(16).padStart(2, '0');
        };
        return `#${f(0)}${f(8)}${f(4)}`;
    }

    toHex(color) {
        if (color.startsWith('#')) return color;
        const rgb = this.extractRgb(color);
        if (!rgb) return '#6366f1';
        return `#${rgb.r.toString(16).padStart(2,'0')}${rgb.g.toString(16).padStart(2,'0')}${rgb.b.toString(16).padStart(2,'0')}`;
    }

    toRgb(color) {
        const rgb = this.extractRgb(color);
        if (!rgb) return 'rgb(99, 102, 241)';
        return `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
    }

    toRgba(color) {
        const c = this.extractRgba(color);
        if (c) return `rgba(${c.r}, ${c.g}, ${c.b}, ${c.a})`;
        const rgb = this.extractRgb(color);
        return rgb ? `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 1)` : 'rgba(99, 102, 241, 1)';
    }

    toHsl(color) {
        const rgb = this.extractRgb(color);
        if (!rgb) return 'hsl(239, 84%, 67%)';
        const hsl = this.rgbToHsl(rgb.r, rgb.g, rgb.b);
        return `hsl(${hsl.h}, ${hsl.s}%, ${hsl.l}%)`;
    }

    extractRgb(color) {
        if (color.startsWith('#')) return this.hexToRgb(color);
        const match = color.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
        if (match) return { r: parseInt(match[1]), g: parseInt(match[2]), b: parseInt(match[3]) };
        const match2 = color.match(/rgba\((\d+),\s*(\d+),\s*(\d+)/);
        if (match2) return { r: parseInt(match2[1]), g: parseInt(match2[2]), b: parseInt(match2[3]) };
        const match3 = color.match(/hsl\((\d+),\s*(\d+)%,\s*(\d+)%\)/);
        if (match3) return this.hslToRgb(parseInt(match3[1]), parseInt(match3[2]), parseInt(match3[3]));
        return null;
    }

    extractRgba(color) {
        const match = color.match(/rgba\((\d+),\s*(\d+),\s*(\d+),\s*([\d.]+)\)/);
        if (match) return { r: parseInt(match[1]), g: parseInt(match[2]), b: parseInt(match[3]), a: parseFloat(match[4]) };
        return null;
    }

    hslToRgb(h, s, l) {
        s /= 100; l /= 100;
        const a = s * Math.min(l, 1 - l);
        const f = (n) => {
            const k = (n + h / 30) % 12;
            return l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
        };
        return { r: Math.round(255 * f(0)), g: Math.round(255 * f(8)), b: Math.round(255 * f(4)) };
    }
}


/**
 * FILTER BUILDER - Dynamic filter builder
 * Supports AND/OR, nested groups, LIKE, IN, BETWEEN, etc.
 */
class FilterBuilder {
    constructor(options = {}) {
        this.container = options.container || null;
        this.onChange = options.onChange || (() => {});
        this.filters = options.filters || [];
        this.availableColumns = options.columns || [];
        this.operators = options.operators || [];
    }

    build(container, options = {}) {
        this.container = container;
        this.filters = options.filters || this.filters || [];
        this.availableColumns = options.columns || this.availableColumns || [];
        this.operators = options.operators || window.cardWidgetConfig?.filterOperators || [];
        this.onChange = options.onChange || this.onChange;
        this.render();
    }

    render() {
        if (!this.container) return;
        this.container.innerHTML = `
            <div class="filter-builder">
                <div class="filter-group-container" style="border:1px solid #e2e8f0;border-radius:10px;padding:12px;background:#fafafa;">
                    <div class="filter-logic-bar" style="display:flex;gap:8px;margin-bottom:10px;align-items:center;">
                        <span style="font-size:12px;color:#64748b;">Match:</span>
                        <button class="filter-logic-btn ${this._getGroupLogic() === 'AND' ? 'active' : ''}" data-logic="AND" style="padding:4px 12px;border-radius:6px;border:1px solid #e2e8f0;background:${this._getGroupLogic() === 'AND' ? '#6366f1' : '#fff'};color:${this._getGroupLogic() === 'AND' ? '#fff' : '#475569'};cursor:pointer;font-size:12px;">AND</button>
                        <button class="filter-logic-btn ${this._getGroupLogic() === 'OR' ? 'active' : ''}" data-logic="OR" style="padding:4px 12px;border-radius:6px;border:1px solid #e2e8f0;background:${this._getGroupLogic() === 'OR' ? '#6366f1' : '#fff'};color:${this._getGroupLogic() === 'OR' ? '#fff' : '#475569'};cursor:pointer;font-size:12px;">OR</button>
                        <button class="filter-add-btn" style="margin-left:auto;padding:4px 12px;border-radius:6px;border:none;background:#6366f1;color:#fff;cursor:pointer;font-size:12px;">+ Add Filter</button>
                        <button class="filter-add-group-btn" style="padding:4px 12px;border-radius:6px;border:1px dashed #6366f1;background:#eef2ff;color:#6366f1;cursor:pointer;font-size:12px;">+ Add Group</button>
                    </div>
                    <div class="filter-rows"></div>
                </div>
            </div>
        `;

        this.bindEvents();
        this.renderRows();
    }

    _getGroupLogic() {
        if (this.filters.length === 0) return 'AND';
        const first = this.filters[0];
        if (first && first.groupOperator) return first.groupOperator;
        return 'AND';
    }

    bindEvents() {
        this.container.querySelector('.filter-logic-bar').addEventListener('click', (e) => {
            const btn = e.target.closest('.filter-logic-btn');
            if (btn) {
                this.container.querySelectorAll('.filter-logic-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.container.querySelectorAll('.filter-logic-btn').forEach(b => {
                    b.style.background = b.dataset.logic === btn.dataset.logic ? '#6366f1' : '#fff';
                    b.style.color = b.dataset.logic === btn.dataset.logic ? '#fff' : '#475569';
                });
                this._setAllGroupLogic(btn.dataset.logic);
            }
        });

        this.container.querySelector('.filter-add-btn').addEventListener('click', () => this.addFilter());
        this.container.querySelector('.filter-add-group-btn').addEventListener('click', () => this.addGroup());
    }

    _setAllGroupLogic(logic) {
        if (this.filters.length > 0 && this.filters[0].groupOperator !== undefined) {
            this.filters[0].groupOperator = logic;
        } else {
            this.filters.unshift({ groupOperator: logic, conditions: [...this.filters] });
            this.filters = [this.filters[0]];
        }
        this.emitChange();
    }

    renderRows() {
        const container = this.container.querySelector('.filter-rows');
        if (!container) return;

        const filters = this._getConditions();
        if (filters.length === 0) {
            container.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px;">No filters yet. Click "+ Add Filter" to start.</div>';
            return;
        }

        container.innerHTML = filters.map((filter, index) => {
            if (filter.group) {
                return this.renderGroupRow(filter, index);
            }
            return this.renderFilterRow(filter, index);
        }).join('');

        this.attachRowEvents(container);
    }

    _getConditions() {
        if (this.filters.length === 0) return [];
        const first = this.filters[0];
        if (first && first.conditions) return first.conditions;
        return this.filters;
    }

    renderFilterRow(filter, index) {
        const colOptions = this.availableColumns.map(c =>
            `<option value="${c.name}" ${filter.field === c.name ? 'selected' : ''}>${c.label || c.name}</option>`
        ).join('');

        const opOptions = this.operators.map(op =>
            `<option value="${op.value}" ${filter.operator === op.value ? 'selected' : ''}>${op.label}</option>`
        ).join('');

        const operator = this.operators.find(op => op.value === filter.operator);
        const requiresValue = operator ? operator.requiresValue !== false : true;

        const valueHtml = requiresValue ? `
            <input type="text" class="filter-value-input" value="${filter.value || ''}" placeholder="Value" style="flex:1;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none;">
        ` : '';

        return `
            <div class="filter-row" data-index="${index}" style="display:flex;gap:6px;align-items:center;margin-bottom:6px;">
                <span style="color:#94a3b8;font-size:10px;width:30px;">${index === 0 ? 'Where' : (this._getGroupLogic() === 'OR' ? 'OR' : 'AND')}</span>
                <select class="filter-field-select" style="flex:1.5;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none;">${colOptions}</select>
                <select class="filter-operator-select" style="flex:1;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none;">${opOptions}</select>
                ${valueHtml}
                <button class="filter-remove-btn" style="padding:4px 8px;border-radius:6px;border:none;background:#fee2e2;color:#ef4444;cursor:pointer;font-size:14px;">✕</button>
            </div>
        `;
    }

    renderGroupRow(filter, index) {
        const subFilters = filter.group || [];
        return `
            <div class="filter-group-row" data-index="${index}" style="border:1px dashed #c7d2fe;border-radius:8px;padding:10px;margin-bottom:6px;background:#f8faff;">
                <div style="display:flex;gap:6px;margin-bottom:6px;align-items:center;">
                    <span style="font-size:11px;color:#6366f1;font-weight:600;">Nested Group</span>
                    <button class="filter-group-logic-and" style="padding:2px 8px;border-radius:4px;border:1px solid #e2e8f0;background:${filter.groupOperator === 'AND' ? '#6366f1' : '#fff'};color:${filter.groupOperator === 'AND' ? '#fff' : '#475569'};cursor:pointer;font-size:11px;">AND</button>
                    <button class="filter-group-logic-or" style="padding:2px 8px;border-radius:4px;border:1px solid #e2e8f0;background:${filter.groupOperator === 'OR' ? '#6366f1' : '#fff'};color:${filter.groupOperator === 'OR' ? '#fff' : '#475569'};cursor:pointer;font-size:11px;">OR</button>
                    <button class="filter-group-remove-btn" style="margin-left:auto;padding:2px 6px;border-radius:4px;border:none;background:#fee2e2;color:#ef4444;cursor:pointer;font-size:12px;">✕ Group</button>
                </div>
                <div class="filter-group-children">
                    ${subFilters.map((sf, si) => this.renderFilterRow(sf, `${index}_${si}`)).join('')}
                </div>
                <button class="filter-group-add-btn" style="margin-top:6px;padding:4px 10px;border-radius:6px;border:1px dashed #6366f1;background:transparent;color:#6366f1;cursor:pointer;font-size:11px;">+ Add to Group</button>
            </div>
        `;
    }

    attachRowEvents(container) {
        container.querySelectorAll('.filter-remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('.filter-row');
                const index = parseInt(row.dataset.index);
                this.removeFilter(index);
            });
        });

        container.querySelectorAll('.filter-field-select, .filter-operator-select, .filter-value-input').forEach(el => {
            el.addEventListener('change', () => this.emitChange());
            el.addEventListener('input', () => this.emitChange());
        });

        container.querySelectorAll('.filter-group-remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('.filter-group-row');
                const index = parseInt(row.dataset.index);
                this.removeGroup(index);
            });
        });

        container.querySelectorAll('.filter-group-add-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const parent = btn.closest('.filter-group-row');
                const children = parent.querySelector('.filter-group-children');
                const newIndex = children.children.length;
                const newFilter = { field: '', operator: '=', value: '' };
                const rowHtml = this.renderFilterRow(newFilter, `new_${Date.now()}`);
                children.insertAdjacentHTML('beforeend', rowHtml);
                this.attachRowEvents(container);
                this.emitChange();
            });
        });
    }

    addFilter() {
        const conditions = this._getConditions();
        conditions.push({ field: '', operator: '=', value: '' });
        this.normalizeState();
        this.render();
        this.emitChange();
    }

    addGroup() {
        const conditions = this._getConditions();
        conditions.push({ group: [], groupOperator: 'AND' });
        this.normalizeState();
        this.render();
        this.emitChange();
    }

    removeFilter(index) {
        const conditions = this._getConditions();
        conditions.splice(index, 1);
        this.normalizeState();
        this.render();
        this.emitChange();
    }

    removeGroup(index) {
        const conditions = this._getConditions();
        conditions.splice(index, 1);
        this.normalizeState();
        this.render();
        this.emitChange();
    }

    normalizeState() {
        const container = this.container;
        if (!container) return;
        const rows = container.querySelectorAll('.filter-row');
        const conditions = [];

        rows.forEach(row => {
            const fieldSelect = row.querySelector('.filter-field-select');
            const operatorSelect = row.querySelector('.filter-operator-select');
            const valueInput = row.querySelector('.filter-value-input');

            conditions.push({
                field: fieldSelect ? fieldSelect.value : '',
                operator: operatorSelect ? operatorSelect.value : '=',
                value: valueInput ? valueInput.value : '',
            });
        });

        const logicBtns = container.querySelectorAll('.filter-logic-btn');
        const activeLogic = container.querySelector('.filter-logic-btn.active');
        const logic = activeLogic ? activeLogic.dataset.logic : 'AND';

        if (this.filters.length > 0 && this.filters[0].conditions !== undefined) {
            this.filters[0].conditions = conditions;
            this.filters[0].groupOperator = logic;
        } else {
            this.filters = [{ groupOperator: logic, conditions: conditions }];
        }
    }

    getValue() {
        this.normalizeState();
        return this.filters;
    }

    emitChange() {
        clearTimeout(this._changeTimeout);
        this._changeTimeout = setTimeout(() => {
            this.normalizeState();
            this.onChange(this.getValue());
        }, 100);
    }
}


/**
 * CARD PROPERTIES ENGINE - Complete card properties panel
 * Integrates with the page builder's renderProperties function
 */
class CardPropertiesEngine {
    constructor() {
        this.cardWidget = window.cardWidgetInstance || new CardWidget();
        this.iconPicker = new IconPicker();
        this.colorPicker = new ColorPicker();
        this.filterBuilder = new FilterBuilder();
        this._filterBlockId = null;
    }

    static render(blockId, props) {
        const engine = new CardPropertiesEngine();
        engine._filterBlockId = blockId;
        return engine._buildHtml(blockId, props);
    }

    _buildHtml(blockId, props) {
        return `
            <div class="card-properties">
                ${this.renderSection('General', this.renderGeneralFields(blockId, props))}
                ${this.renderSection('Icon', this.renderIconFields(blockId, props))}
                ${this.renderSection('Layout', this.renderLayoutFields(blockId, props))}
                ${this.renderSection('Typography', this.renderTypographyFields(blockId, props))}
                ${this.renderSection('Background', this.renderBackgroundFields(blockId, props))}
                ${this.renderSection('Data Source', this.renderDataSourceFields(blockId, props))}
                ${this.renderFilterSection(blockId, props)}
                ${this.renderSection('Output Format', this.renderOutputFormatFields(blockId, props))}
                ${this.renderSection('Refresh', this.renderRefreshFields(blockId, props))}
                ${this.renderSection('Live Preview', this.renderPreviewSection(blockId, props))}
            </div>
        `;
    }

    renderSection(title, content) {
        const id = 'prop-section-' + title.toLowerCase().replace(/\s+/g, '-');
        return `
            <div class="prop-section card-prop-section" style="border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;overflow:hidden;">
                <div class="prop-section-header" onclick="CardPropertiesEngine.toggleSection('${id}')" style="padding:10px 14px;background:#f8fafc;cursor:pointer;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;user-select:none;">
                    <span style="font-weight:600;font-size:13px;color:#1e293b;">${title}</span>
                    <span style="color:#94a3b8;font-size:10px;">▾</span>
                </div>
                <div id="${id}" class="prop-section-body" style="padding:12px 14px;display:block;">
                    ${content}
                </div>
            </div>
        `;
    }

    static toggleSection(id) {
        const el = document.getElementById(id);
        if (el) {
            const isHidden = el.style.display === 'none';
            el.style.display = isHidden ? 'block' : 'none';
            const header = el.closest('.card-prop-section')?.querySelector('.prop-section-header span:last-child');
            if (header) header.textContent = isHidden ? '▾' : '▸';
        }
    }

    renderGeneralFields(blockId, props) {
        return `
            <div class="prop-group">
                <label class="prop-label">Title</label>
                <input type="text" class="prop-input" value="${this.esc(props.title || '')}" onchange="CardPropertiesEngine.update('${blockId}', 'title', this.value)" placeholder="Card title">
            </div>
            <div class="prop-group">
                <label class="prop-label">Subtitle</label>
                <input type="text" class="prop-input" value="${this.esc(props.subtitle || '')}" onchange="CardPropertiesEngine.update('${blockId}', 'subtitle', this.value)" placeholder="Card subtitle">
            </div>
            <div class="prop-group">
                <label class="prop-label">Description</label>
                <textarea class="prop-textarea" rows="2" onchange="CardPropertiesEngine.update('${blockId}', 'description', this.value)" placeholder="Card description">${this.esc(props.description || '')}</textarea>
            </div>
            <div class="prop-checkbox-row" style="display:flex;gap:12px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" ${props.showTitle !== false ? 'checked' : ''} onchange="CardPropertiesEngine.update('${blockId}', 'showTitle', this.checked)"> Title
                </label>
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" ${props.showSubtitle !== false ? 'checked' : ''} onchange="CardPropertiesEngine.update('${blockId}', 'showSubtitle', this.checked)"> Subtitle
                </label>
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" ${props.showDescription !== false ? 'checked' : ''} onchange="CardPropertiesEngine.update('${blockId}', 'showDescription', this.checked)"> Description
                </label>
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;">
                    <input type="checkbox" ${props.showValue !== false ? 'checked' : ''} onchange="CardPropertiesEngine.update('${blockId}', 'showValue', this.checked)"> Value
                </label>
            </div>
        `;
    }

    renderIconFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const allowed = ['heroicons', 'lucide'];
        const libOptions = (config?.iconLibraries || []).filter(lib => allowed.includes(lib.value)).map(lib =>
            `<option value="${lib.value}" ${(props.iconLibrary || 'heroicons') === lib.value ? 'selected' : ''}>${lib.label}</option>`
        ).join('');

        const shapeOptions = (config?.iconShapes || []).map(s =>
            `<option value="${s.value}" ${(props.iconShape || 'none') === s.value ? 'selected' : ''}>${s.label}</option>`
        ).join('');

        return `
            <div class="prop-checkbox-group">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" ${props.showIcon !== false ? 'checked' : ''} onchange="CardPropertiesEngine.update('${blockId}', 'showIcon', this.checked)">
                    Show Icon
                </label>
            </div>
            <div class="prop-group">
                <label class="prop-label">Library</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'iconLibrary', this.value);CardPropertiesEngine.update('${blockId}', 'icon', 'star');CardPropertiesEngine.refreshIconPicker('${blockId}')">${libOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Icon</label>
                <div class="icon-picker-trigger" onclick="CardPropertiesEngine.openIconPicker('${blockId}')" style="padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:12px;transition:border-color 0.2s;">
                    ${(window.IconRegistry && (props.iconLibrary === 'heroicons' || props.iconLibrary === 'lucide'))
                        ? window.IconRegistry.renderIcon(props.iconLibrary, props.icon || 'star', { size: 28, color: props.iconColor || '#6366f1' })
                        : `<span style="font-size:28px;color:${props.iconColor || '#6366f1'};">${props.icon || ''}</span>`}
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">${props.icon || 'Click to select icon'}</div>
                        <div style="font-size:11px;color:#94a3b8;">Click to browse icons</div>
                    </div>
                </div>
            </div>
            <div class="prop-group">
                <label class="prop-label">Icon Color</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="color-swatch-trigger" onclick="CardPropertiesEngine.openColorPicker('${blockId}', 'iconColor', this)" style="width:36px;height:36px;border-radius:8px;background:${props.iconColor || '#6366f1'};cursor:pointer;border:2px solid #e2e8f0;"></div>
                    <input type="text" class="prop-input" style="flex:1;" value="${props.iconColor || '#6366f1'}" onchange="CardPropertiesEngine.update('${blockId}', 'iconColor', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                </div>
            </div>
            <div class="prop-group">
                <label class="prop-label">Size: ${props.iconSize || '48'}px</label>
                <input type="range" class="prop-slider" min="16" max="96" value="${props.iconSize || '48'}" onchange="CardPropertiesEngine.update('${blockId}', 'iconSize', this.value);this.previousElementSibling.textContent='Size: '+this.value+'px';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Weight: ${props.iconWeight || '400'}</label>
                <input type="range" class="prop-slider" min="100" max="700" step="100" value="${props.iconWeight || '400'}" onchange="CardPropertiesEngine.update('${blockId}', 'iconWeight', this.value);this.previousElementSibling.textContent='Weight: '+this.value;CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Shape</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'iconShape', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${shapeOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Icon Background</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="color-swatch-trigger" onclick="CardPropertiesEngine.openColorPicker('${blockId}', 'iconBackground', this)" style="width:36px;height:36px;border-radius:8px;background:${props.iconBackground || 'transparent'};cursor:pointer;border:2px solid #e2e8f0;"></div>
                    <input type="text" class="prop-input" style="flex:1;" value="${props.iconBackground || ''}" onchange="CardPropertiesEngine.update('${blockId}', 'iconBackground', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                </div>
            </div>
            <div class="prop-group">
                <label class="prop-label">Rotation: ${props.iconRotation || '0'}°</label>
                <input type="range" class="prop-slider" min="0" max="360" value="${props.iconRotation || '0'}" onchange="CardPropertiesEngine.update('${blockId}', 'iconRotation', this.value);this.previousElementSibling.textContent='Rotation: '+this.value+'°';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Opacity: ${props.iconOpacity || '100'}%</label>
                <input type="range" class="prop-slider" min="0" max="100" value="${props.iconOpacity || '100'}" onchange="CardPropertiesEngine.update('${blockId}', 'iconOpacity', this.value);this.previousElementSibling.textContent='Opacity: '+this.value+'%';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-checkbox-group">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" ${props.iconFill ? 'checked' : ''} onchange="CardPropertiesEngine.update('${blockId}', 'iconFill', this.checked)">
                    Filled Style
                </label>
            </div>
        `;
    }

    renderLayoutFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const shadowOptions = (config?.shadowOptions || []).map(s =>
            `<option value="${s.value}" ${(props.shadow || 'md') === s.value ? 'selected' : ''}>${s.label}</option>`
        ).join('');
        const borderOptions = (config?.borderOptions || []).map(b =>
            `<option value="${b.value}" ${(props.border || 'none') === b.value ? 'selected' : ''}>${b.label}</option>`
        ).join('');
        const alignOptions = (config?.alignOptions || []).map(a =>
            `<option value="${a.value}" ${(props.alignment || 'left') === a.value ? 'selected' : ''}>${a.label}</option>`
        ).join('');

        return `
            <div class="prop-group">
                <label class="prop-label">Kolom per Baris</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'columns', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                    <option value="1" ${(props.columns || '1') === '1' ? 'selected' : ''}>1 Kolom (Full)</option>
                    <option value="2" ${props.columns === '2' ? 'selected' : ''}>2 Kolom</option>
                    <option value="3" ${props.columns === '3' ? 'selected' : ''}>3 Kolom</option>
                    <option value="4" ${props.columns === '4' ? 'selected' : ''}>4 Kolom</option>
                </select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Width: ${props.width || '100'}%</label>
                <input type="range" class="prop-slider" min="25" max="100" step="5" value="${props.width || '100'}" onchange="CardPropertiesEngine.update('${blockId}', 'width', this.value);this.previousElementSibling.textContent='Width: '+this.value+'%';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Height</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'height', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                    <option value="auto" ${(props.height || 'auto') === 'auto' ? 'selected' : ''}>Auto</option>
                    <option value="100" ${props.height === '100' ? 'selected' : ''}>100px</option>
                    <option value="150" ${props.height === '150' ? 'selected' : ''}>150px</option>
                    <option value="200" ${props.height === '200' ? 'selected' : ''}>200px</option>
                    <option value="250" ${props.height === '250' ? 'selected' : ''}>250px</option>
                    <option value="300" ${props.height === '300' ? 'selected' : ''}>300px</option>
                </select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Padding: ${props.padding || '24'}px</label>
                <input type="range" class="prop-slider" min="0" max="48" value="${props.padding || '24'}" onchange="CardPropertiesEngine.update('${blockId}', 'padding', this.value);this.previousElementSibling.textContent='Padding: '+this.value+'px';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Border Radius: ${props.borderRadius || '12'}px</label>
                <input type="range" class="prop-slider" min="0" max="32" value="${props.borderRadius || '12'}" onchange="CardPropertiesEngine.update('${blockId}', 'borderRadius', this.value);this.previousElementSibling.textContent='Border Radius: '+this.value+'px';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Shadow</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'shadow', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${shadowOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Border</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'border', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${borderOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Border Color</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="color-swatch-trigger" onclick="CardPropertiesEngine.openColorPicker('${blockId}', 'borderColor', this)" style="width:36px;height:36px;border-radius:8px;background:${props.borderColor || '#e2e8f0'};cursor:pointer;border:2px solid #e2e8f0;"></div>
                    <input type="text" class="prop-input" style="flex:1;" value="${props.borderColor || '#e2e8f0'}" onchange="CardPropertiesEngine.update('${blockId}', 'borderColor', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                </div>
            </div>
            <div class="prop-group">
                <label class="prop-label">Alignment</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'alignment', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${alignOptions}</select>
            </div>
        `;
    }

    renderTypographyFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const weightOptions = (config?.fontWeightOptions || []).map(w =>
            `<option value="${w.value}" ${(props.fontWeight || '400') === w.value ? 'selected' : ''}>${w.label}</option>`
        ).join('');
        const familyOptions = (config?.fontFamilyOptions || []).map(f =>
            `<option value="${f.value}" ${(props.fontFamily || '') === f.value ? 'selected' : ''}>${f.label}</option>`
        ).join('');

        return `
            <div class="prop-group">
                <label class="prop-label">Font Size: ${props.fontSize || '16'}px</label>
                <input type="range" class="prop-slider" min="10" max="32" value="${props.fontSize || '16'}" onchange="CardPropertiesEngine.update('${blockId}', 'fontSize', this.value);this.previousElementSibling.textContent='Font Size: '+this.value+'px';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Font Weight</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'fontWeight', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${weightOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Font Family</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'fontFamily', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${familyOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Line Height: ${props.lineHeight || '1.5'}</label>
                <input type="range" class="prop-slider" min="1" max="2.5" step="0.1" value="${props.lineHeight || '1.5'}" onchange="CardPropertiesEngine.update('${blockId}', 'lineHeight', this.value);this.previousElementSibling.textContent='Line Height: '+this.value;CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Text Color</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="color-swatch-trigger" onclick="CardPropertiesEngine.openColorPicker('${blockId}', 'textColor', this)" style="width:36px;height:36px;border-radius:8px;background:${props.textColor || '#1e293b'};cursor:pointer;border:2px solid #e2e8f0;"></div>
                    <input type="text" class="prop-input" style="flex:1;" value="${props.textColor || '#1e293b'}" onchange="CardPropertiesEngine.update('${blockId}', 'textColor', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                </div>
            </div>
        `;
    }

    renderBackgroundFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const bgTypes = (config?.bgTypes || []).map(t =>
            `<option value="${t.value}" ${(props.bgType || 'solid') === t.value ? 'selected' : ''}>${t.label}</option>`
        ).join('');
        const isGradient = props.bgType === 'gradient';

        return `
            <div class="prop-group">
                <label class="prop-label">Background Type</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'bgType', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${bgTypes}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Background Color</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div class="color-swatch-trigger" onclick="CardPropertiesEngine.openColorPicker('${blockId}', 'bgColor', this)" style="width:36px;height:36px;border-radius:8px;background:${props.bgColor || '#ffffff'};cursor:pointer;border:2px solid #e2e8f0;"></div>
                    <input type="text" class="prop-input" style="flex:1;" value="${props.bgColor || '#ffffff'}" onchange="CardPropertiesEngine.update('${blockId}', 'bgColor', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                </div>
            </div>
            <div class="prop-group" ${isGradient ? '' : 'style="display:none;"'} id="bg-gradient-group-${blockId}">
                <label class="prop-label">Gradient</label>
                <input type="text" class="prop-input" value="${props.bgGradient || ''}" onchange="CardPropertiesEngine.update('${blockId}', 'bgGradient', this.value);CardPropertiesEngine.refreshPreview('${blockId}')" placeholder="linear-gradient(135deg, #667eea, #764ba2)">
            </div>
            <div class="prop-group" id="bg-blur-group-${blockId}" ${props.bgType === 'glass' ? '' : 'style="display:none;"'}>
                <label class="prop-label">Blur: ${props.bgBlur || '0'}px</label>
                <input type="range" class="prop-slider" min="0" max="20" value="${props.bgBlur || '0'}" onchange="CardPropertiesEngine.update('${blockId}', 'bgBlur', this.value);this.previousElementSibling.textContent='Blur: '+this.value+'px';CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Quick Gradients</label>
                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                    ${['linear-gradient(135deg,#667eea,#764ba2)','linear-gradient(135deg,#f093fb,#f5576c)','linear-gradient(135deg,#4facfe,#00f2fe)','linear-gradient(135deg,#43e97b,#38f9d7)','linear-gradient(135deg,#fa709a,#fee140)','linear-gradient(135deg,#a18cd1,#fbc2eb)'].map(g => `
                        <div onclick="CardPropertiesEngine.update('${blockId}', 'bgGradient', '${g}');CardPropertiesEngine.update('${blockId}', 'bgType', 'gradient');CardPropertiesEngine.refreshPreview('${blockId}')" style="width:32px;height:32px;border-radius:8px;background:${g};cursor:pointer;border:2px solid #e2e8f0;"></div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    renderDataSourceFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const dsOptions = (config?.datasources || []).map(d =>
            `<option value="${d.value}" ${(props.datasource || 'static') === d.value ? 'selected' : ''}>${d.label}</option>`
        ).join('');
        const aggOptions = (config?.aggregates || []).map(a =>
            `<option value="${a.value}" ${(props.aggregate || 'COUNT') === a.value ? 'selected' : ''}>${a.label}</option>`
        ).join('');
        const tableOptions = (config?.tables || []).map(t =>
            `<option value="${t.id}" ${String(props.tableId) === String(t.id) ? 'selected' : ''}>${t.label} (${t.name})</option>`
        ).join('');

        return `
            <div class="prop-group">
                <label class="prop-label">Data Source</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'datasource', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${dsOptions}</select>
            </div>
            <div class="prop-group" id="card-table-group-${blockId}" ${props.datasource === 'database' ? '' : 'style="display:none;"'}>
                <label class="prop-label">Table</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'tableId', this.value);CardPropertiesEngine.update('${blockId}', 'tableName', this.options[this.selectedIndex].text.split(' (')[0]);CardPropertiesEngine.loadColumns('${blockId}');CardPropertiesEngine.refreshPreview('${blockId}')">
                    <option value="">-- Select Table --</option>
                    ${tableOptions}
                </select>
            </div>
            <div class="prop-group" id="card-aggregate-group-${blockId}" ${props.datasource === 'database' ? '' : 'style="display:none;"'}>
                <label class="prop-label">Aggregate</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'aggregate', this.value);CardPropertiesEngine.loadColumns('${blockId}');CardPropertiesEngine.refreshPreview('${blockId}')">${aggOptions}</select>
            </div>
            <div class="prop-group" id="card-column-group-${blockId}" ${props.datasource === 'database' && props.aggregate !== 'COUNT' && props.aggregate !== 'CUSTOM' ? '' : 'style="display:none;"'}>
                <label class="prop-label">Column</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'column', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                    <option value="">-- Select Column --</option>
                </select>
            </div>
            <div class="prop-group" id="card-customsql-group-${blockId}" ${props.datasource === 'database' && props.aggregate === 'CUSTOM' ? '' : 'style="display:none;"'}>
                <label class="prop-label">Custom SQL Expression</label>
                <textarea class="prop-input prop-textarea" rows="3" placeholder="COUNT(CASE WHEN status = 'hadir' THEN 1 END)" onchange="CardPropertiesEngine.update('${blockId}', 'customSql', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${this.esc(props.customSql || '')}</textarea>
                <small style="color:#64748b;font-size:11px;display:block;margin-top:4px;">Gunakan ekspresi SQL valid, misal: <code>COUNT(CASE WHEN status = 'hadir' THEN 1 END)</code> atau <code>SUM(CASE WHEN status = 'telat' THEN 1 ELSE 0 END)</code></small>
            </div>
        `;
    }

    renderFilterSection(blockId, props) {
        return `
            <div class="prop-section card-prop-section" style="border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;overflow:hidden;">
                <div class="prop-section-header" onclick="CardPropertiesEngine.toggleSection('filter-section-${blockId}')" style="padding:10px 14px;background:#f8fafc;cursor:pointer;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;user-select:none;">
                    <span style="font-weight:600;font-size:13px;color:#1e293b;">Filter Builder</span>
                    <span style="color:#94a3b8;font-size:10px;">▾</span>
                </div>
                <div id="filter-section-${blockId}" class="prop-section-body" style="padding:12px 14px;display:block;">
                    <div id="filter-builder-container-${blockId}"></div>
                </div>
            </div>
        `;
    }

    renderOutputFormatFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const formatOptions = (config?.formats || []).map(f =>
            `<option value="${f.value}" ${(props.outputFormat || 'auto') === f.value ? 'selected' : ''}>${f.label}</option>`
        ).join('');
        const localeOptions = ['id-ID','en-US','en-GB','de-DE','fr-FR','ja-JP','zh-CN','ar-SA'].map(l =>
            `<option value="${l}" ${(props.numberLocale || 'id-ID') === l ? 'selected' : ''}>${l}</option>`
        ).join('');

        return `
            <div class="prop-group">
                <label class="prop-label">Format</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'outputFormat', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${formatOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Decimal Places: ${props.numberDecimal || '0'}</label>
                <input type="range" class="prop-slider" min="0" max="6" value="${props.numberDecimal || '0'}" onchange="CardPropertiesEngine.update('${blockId}', 'numberDecimal', this.value);this.previousElementSibling.textContent='Decimal Places: '+this.value;CardPropertiesEngine.refreshPreview('${blockId}')">
            </div>
            <div class="prop-group">
                <label class="prop-label">Thousand Separator</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'numberSeparator', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">
                    <option value="," ${(props.numberSeparator || ',') === ',' ? 'selected' : ''}>Comma (,)</option>
                    <option value="." ${props.numberSeparator === '.' ? 'selected' : ''}>Dot (.)</option>
                    <option value=" " ${props.numberSeparator === ' ' ? 'selected' : ''}>Space ( )</option>
                    <option value="'" ${props.numberSeparator === "'" ? 'selected' : ''}>Apostrophe (')</option>
                </select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Prefix</label>
                <input type="text" class="prop-input" value="${props.numberPrefix || ''}" onchange="CardPropertiesEngine.update('${blockId}', 'numberPrefix', this.value);CardPropertiesEngine.refreshPreview('${blockId}')" placeholder="e.g. Rp">
            </div>
            <div class="prop-group">
                <label class="prop-label">Suffix</label>
                <input type="text" class="prop-input" value="${props.numberSuffix || ''}" onchange="CardPropertiesEngine.update('${blockId}', 'numberSuffix', this.value);CardPropertiesEngine.refreshPreview('${blockId}')" placeholder="e.g. items">
            </div>
            <div class="prop-group">
                <label class="prop-label">Locale</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'numberLocale', this.value);CardPropertiesEngine.refreshPreview('${blockId}')">${localeOptions}</select>
            </div>
        `;
    }

    renderRefreshFields(blockId, props) {
        const config = window.cardWidgetConfig;
        const refreshOptions = (config?.refreshStrategies || []).map(r =>
            `<option value="${r.value}" ${(props.refresh || 'page_load') === r.value ? 'selected' : ''}>${r.label}</option>`
        ).join('');
        const intervalOptions = (config?.refreshIntervals || []).map(i =>
            `<option value="${i.value}" ${String(props.refreshInterval || '30') === String(i.value) ? 'selected' : ''}>${i.label}</option>`
        ).join('');

        return `
            <div class="prop-group">
                <label class="prop-label">Refresh Strategy</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'refresh', this.value)">${refreshOptions}</select>
            </div>
            <div class="prop-group" id="card-interval-group-${blockId}">
                <label class="prop-label">Interval</label>
                <select class="prop-select" onchange="CardPropertiesEngine.update('${blockId}', 'refreshInterval', this.value)">${intervalOptions}</select>
            </div>
            <div class="prop-group">
                <label class="prop-label">Cache TTL: ${props.cacheTtl || '300'}s</label>
                <input type="range" class="prop-slider" min="0" max="3600" step="60" value="${props.cacheTtl || '300'}" onchange="CardPropertiesEngine.update('${blockId}', 'cacheTtl', this.value);this.previousElementSibling.textContent='Cache TTL: '+this.value+'s'">
            </div>
        `;
    }

    renderPreviewSection(blockId, props) {
        return `
            <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#f8fafc;">
                <div data-card-preview="${blockId}">
                    ${window.cardWidgetInstance ? window.cardWidgetInstance.renderCardPreview(props) : ''}
                </div>
            </div>
        `;
    }

    static update(blockId, key, value) {
        if (typeof updateProp === 'function') {
            updateProp(blockId, key, value);
        } else if (window.pageState) {
            const state = window.pageState;
            const block = CardPropertiesEngine._findBlock(state, blockId);
            if (block) {
                if (!block.props) block.props = {};
                block.props[key] = value;
                if (window.renderBuilder) renderBuilder(state);
                if (window.renderProperties) renderProperties(blockId);
            }
        }
        if (window.cardWidgetInstance) {
            clearTimeout(window.cardWidgetInstance.previewTimeout);
            window.cardWidgetInstance.previewTimeout = setTimeout(() => {
                window.cardWidgetInstance.triggerRender(blockId);
                if (typeof window.cardWidgetInstance.refreshCardBlockData === 'function') {
                    window.cardWidgetInstance.refreshCardBlockData(blockId);
                }
            }, 300);
        }
    }

    static async openIconPicker(blockId) {
        const state = window.pageState || [];
        const block = CardPropertiesEngine._findBlock(state, blockId);
        if (!block) return;

        const picker = new IconPicker();
        const iconContainer = document.querySelector('.icon-picker-trigger');

        await picker.open(document.body, {
            value: block.props.icon || '',
            library: block.props.iconLibrary || 'heroicons',
            onSelect: (iconName) => {
                CardPropertiesEngine.update(blockId, 'icon', iconName);
                const trigger = document.querySelector('.icon-picker-trigger');
                if (trigger) {
                    const lib = block.props.iconLibrary || 'heroicons';
                    const iconEl = trigger.querySelector('span');
                    const nameEl = trigger.querySelector('div div:first-child');
                    if (iconEl) {
                        if (window.IconRegistry && (lib === 'heroicons' || lib === 'lucide')) {
                            iconEl.outerHTML = window.IconRegistry.renderIcon(lib, iconName || 'star', { size: 28, color: block.props.iconColor || '#6366f1' });
                        } else {
                            iconEl.className = CardPropertiesEngine.getIconCssClass(lib, iconName);
                            iconEl.textContent = iconName || 'add_circle';
                        }
                    }
                    if (nameEl) {
                        nameEl.textContent = iconName || 'Click to select icon';
                    }
                    if (window.IconRegistry) window.IconRegistry.afterRender(trigger);
                }
            }
        });
    }

    static openColorPicker(blockId, key, triggerEl) {
        const state = window.pageState || [];
        const block = CardPropertiesEngine._findBlock(state, blockId);
        if (!block) return;

        const picker = new ColorPicker();
        picker.open(triggerEl, {
            value: block.props[key] || '#6366f1',
            onChange: (color) => {
                CardPropertiesEngine.update(blockId, key, color);
                if (triggerEl) triggerEl.style.background = color;
                const input = triggerEl?.parentElement?.querySelector('.prop-input');
                if (input) input.value = color;
            }
        });
    }

    static async loadColumns(blockId) {
        const state = window.pageState || [];
        const block = CardPropertiesEngine._findBlock(state, blockId);
        if (!block || !block.props.tableId) return;

        try {
            const baseUrl = window.cardConfigBaseUrl || '/card';
            const resp = await fetch(`${baseUrl}/get-columns?tableId=${block.props.tableId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await resp.json();
            const columns = result.success ? result.data : [];

            const select = document.querySelector(`#card-column-group-${blockId} select`);
            if (!select) return;

            const aggConfig = window.cardWidgetConfig?.aggregates?.find(a => a.value === block.props.aggregate);
            const numericOnly = aggConfig?.numericOnly || false;
            const filtered = numericOnly ? columns.filter(c => c.isNumeric) : columns;

            select.innerHTML = '<option value="">-- Select Column --</option>' +
                filtered.map(c => `<option value="${c.name}" ${block.props.column === c.name ? 'selected' : ''}>${c.label}</option>`).join('');

            CardPropertiesEngine._updateColumnVisibility(blockId);
        } catch (e) {
        }
    }

    static _updateColumnVisibility(blockId) {
        const state = window.pageState || [];
        const block = CardPropertiesEngine._findBlock(state, blockId);
        if (!block) return;

        const colGroup = document.getElementById(`card-column-group-${blockId}`);
        if (colGroup) {
            colGroup.style.display = block.props.datasource === 'database' && block.props.aggregate !== 'COUNT' && block.props.aggregate !== 'CUSTOM' ? '' : 'none';
        }
        const customSqlGroup = document.getElementById(`card-customsql-group-${blockId}`);
        if (customSqlGroup) {
            customSqlGroup.style.display = block.props.datasource === 'database' && block.props.aggregate === 'CUSTOM' ? '' : 'none';
        }
        const tableGroup = document.getElementById(`card-table-group-${blockId}`);
        if (tableGroup) {
            tableGroup.style.display = block.props.datasource === 'database' ? '' : 'none';
        }
        const aggGroup = document.getElementById(`card-aggregate-group-${blockId}`);
        if (aggGroup) {
            aggGroup.style.display = block.props.datasource === 'database' ? '' : 'none';
        }
    }

    static refreshFilterBuilder(blockId) {
        const state = window.pageState || [];
        const block = CardPropertiesEngine._findBlock(state, blockId);
        if (!block) return;

        const filters = CardPropertiesEngine._parseFilters(block.props.filterJson);
        const container = document.getElementById(`filter-builder-container-${blockId}`);
        if (!container) return;

        const columns = [];
        if (block.props.tableId && window.cardWidgetConfig?.tables) {
            const table = window.cardWidgetConfig.tables.find(t => String(t.id) === String(block.props.tableId));
            if (table) {
                const baseUrl = window.cardConfigBaseUrl || '/card';
                fetch(`${baseUrl}/get-columns?tableId=${block.props.tableId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        const fb = new FilterBuilder();
                        fb.build(container, {
                            filters: filters,
                            columns: result.data,
                            operators: window.cardWidgetConfig?.filterOperators || [],
                            onChange: (newFilters) => {
                                if (typeof updateProp === 'function') {
                                    updateProp(blockId, 'filterJson', JSON.stringify(newFilters));
                                }
                            }
                        });
                    }
                });
                return;
            }
        }

        const fb = new FilterBuilder();
        fb.build(container, {
            filters: filters,
            columns: columns,
            operators: window.cardWidgetConfig?.filterOperators || [],
            onChange: (newFilters) => {
                if (typeof updateProp === 'function') {
                    updateProp(blockId, 'filterJson', JSON.stringify(newFilters));
                }
            }
        });
    }

    static _parseFilters(filterJson) {
        if (!filterJson) return [];
        try {
            const parsed = JSON.parse(filterJson);
            if (Array.isArray(parsed)) return parsed;
            if (parsed && parsed.conditions) return [parsed];
            return [];
        } catch (e) {
            return [];
        }
    }

    static refreshPreview(blockId) {
        if (window.cardWidgetInstance) {
            clearTimeout(window.cardWidgetInstance.previewTimeout);
            window.cardWidgetInstance.previewTimeout = setTimeout(() => {
                window.cardWidgetInstance.triggerRender(blockId);
                window.cardWidgetInstance.refreshCardBlockData(blockId);
            }, 200);
        }
    }

    static refreshIconPicker(blockId) {
        const state = window.pageState || [];
        const block = CardPropertiesEngine._findBlock(state, blockId);
        if (!block) return;
        const trigger = document.querySelector('.icon-picker-trigger');
        if (trigger) {
            const iconEl = trigger.querySelector('span');
            if (iconEl) {
                const lib = block.props.iconLibrary || 'heroicons';
                const iconName = block.props.icon || 'star';
                if (window.IconRegistry && (lib === 'heroicons' || lib === 'lucide')) {
                    iconEl.outerHTML = window.IconRegistry.renderIcon(lib, iconName, { size: 28, color: block.props.iconColor || '#6366f1' });
                } else {
                    iconEl.className = CardPropertiesEngine.getIconCssClass(lib, iconName);
                    iconEl.textContent = iconName;
                }
                if (window.IconRegistry) window.IconRegistry.afterRender(trigger);
            }
        }
    }

    static getIconCssClass(library, iconName) {
        return window.IconRegistry ? window.IconRegistry.getCssClass(library, iconName) : 'hero-icon';
    }

    static _findBlock(state, blockId) {
        for (let block of state) {
            if (block.id === blockId) return block;
            if (block.children) {
                const found = CardPropertiesEngine._findBlock(block.children, blockId);
                if (found) return found;
            }
        }
        return null;
    }

    esc(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
}


/**
 * Initialize Card Widget
 */
// Immediately expose CardPropertiesEngine globally
window.CardPropertiesEngine = CardPropertiesEngine;

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.cardWidgetInstance === 'undefined') {
            window.cardWidgetInstance = new CardWidget();
            window.cardWidgetInstance.init();
        }
        window.cardPropertiesEngine = new CardPropertiesEngine();
    });
} else {
    if (typeof window.cardWidgetInstance === 'undefined') {
        window.cardWidgetInstance = new CardWidget();
        window.cardWidgetInstance.init();
    }
    window.cardPropertiesEngine = new CardPropertiesEngine();
}
