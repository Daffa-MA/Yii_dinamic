(function () {
    'use strict';

    var LIBRARY_CONFIG = {
        'material-symbols': { type: 'font', cssClass: 'material-symbols-outlined' },
        'tabler': { type: 'font', cssClassPrefix: 'ti ti-' },
        'heroicons': { type: 'svg-heroicon', cssClassPrefix: 'hero-icon hero-' },
        'lucide': { type: 'svg-lucide', cssClassPrefix: 'lucide lucide-' },
        'phosphor': { type: 'font', cssClassPrefix: 'ph ph-' },
        'remix': { type: 'font', cssClassPrefix: 'ri ri-' },
        'font-awesome': { type: 'font', cssClassPrefix: 'fa-solid fa-' },
        'bootstrap-icons': { type: 'font', cssClassPrefix: 'bi bi-' },
        'feather': { type: 'font', cssClassPrefix: 'feather feather-' }
    };

    var _svgCache = {};
    var _inFlight = {};
    var _pendingUpdates = {};

    function _updateElements(cacheKey, escapedName, inner) {
        _svgCache[cacheKey] = inner;
        delete _inFlight[cacheKey];
        var els = document.querySelectorAll('[data-heroicon="' + escapedName + '"]');
        for (var i = 0; i < els.length; i++) {
            els[i].innerHTML = inner;
        }
        var pending = _pendingUpdates[cacheKey];
        if (pending) {
            for (var i = 0; i < pending.length; i++) {
                pending[i].innerHTML = inner;
            }
            delete _pendingUpdates[cacheKey];
        }
    }

    function IconRegistry() {
        this._ready = false;
        this._lucideLoaded = false;
    }

    IconRegistry.prototype.init = function () {
        var self = this;
        if (window.lucide) {
            self._lucideLoaded = true;
            self._ready = true;
            return;
        }
        var s = document.createElement('script');
        s.src = 'https://unpkg.com/lucide@latest';
        s.onload = function () {
            self._lucideLoaded = true;
            self._ready = true;
            self._processPending();
        };
        s.onerror = function () {
            self._ready = true;
        };
        document.head.appendChild(s);
    };

    IconRegistry.prototype._processPending = function () {
        if (!this._pendingContainers) return;
        for (var i = 0; i < this._pendingContainers.length; i++) {
            try {
                lucide.createIcons({ root: this._pendingContainers[i] || document.body });
            } catch (e) { }
        }
        this._pendingContainers = null;
    };

    IconRegistry.prototype.getConfig = function (library) {
        return LIBRARY_CONFIG[library] || LIBRARY_CONFIG['material-symbols'];
    };

    IconRegistry.prototype.getCssClass = function (library, iconName) {
        var cfg = this.getConfig(library);
        if (cfg.cssClass) return cfg.cssClass;
        if (cfg.cssClassPrefix) return cfg.cssClassPrefix + (iconName || '');
        return 'material-symbols-outlined';
    };

    IconRegistry.prototype.renderIcon = function (library, iconName, options) {
        options = options || {};
        var lib = library || 'material-symbols';
        var name = iconName || '';
        var size = options.size || 48;
        var color = options.color || '#6366f1';
        var cfg = this.getConfig(lib);

        switch (cfg.type) {
            case 'font':
                return this._renderFontIcon(lib, cfg, name, size, color, options);
            case 'svg-lucide':
                return this._renderLucideIcon(name, size, color);
            case 'svg-heroicon':
                return this._renderHeroicon(name, size, color);
            default:
                return '<span style="font-size:' + size + 'px;color:' + color + '">' + this._esc(name) + '</span>';
        }
    };

    IconRegistry.prototype._renderFontIcon = function (lib, cfg, name, size, color, options) {
        var cssClass = this.getCssClass(lib, name);
        if (lib === 'material-symbols') {
            var fill = options.fill ? 1 : 0;
            var weight = options.weight || 400;
            var grad = options.grad || 0;
            return '<span class="' + cssClass + '" style="font-size:' + size + 'px;color:' + color + ';font-variation-settings:\'FILL\' ' + fill + ',\'wght\' ' + weight + ',\'GRAD\' ' + grad + '">' + this._esc(name) + '</span>';
        }
        return '<i class="' + cssClass + '" style="font-size:' + size + 'px;color:' + color + '"></i>';
    };

    IconRegistry.prototype._renderLucideIcon = function (name, size, color) {
        var cssClass = this.getCssClass('lucide', name);
        return '<i class="' + cssClass + '" style="display:inline-block;width:' + size + 'px;height:' + size + 'px;color:' + color + '" data-lucide="' + this._esc(name) + '"></i>';
    };

    IconRegistry.prototype._renderHeroicon = function (name, size, color) {
        var cacheKey = 'heroicons:' + name;
        var cached = _svgCache[cacheKey];
        if (cached) {
            return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' + cached + '</svg>';
        }
        if (!_inFlight[cacheKey]) {
            _inFlight[cacheKey] = true;
            this._doFetchHeroicon(name, cacheKey);
        }
        return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-heroicon="' + this._esc(name) + '"><rect x="2" y="2" width="20" height="20" rx="5" fill="' + color + '" opacity="0.15"/><circle cx="12" cy="12" r="8" stroke="' + color + '" stroke-dasharray="3 3" opacity="0.6"/><path d="M12 8v4m0 4h.01" stroke="' + color + '" stroke-width="2.5" opacity="0.7"/></svg>';
    };

    IconRegistry.prototype._doFetchHeroicon = function (name, cacheKey) {
        var self = this;
        var url = 'https://cdn.jsdelivr.net/npm/heroicons@2/24/outline/' + encodeURIComponent(name) + '.svg';
        fetch(url)
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (svgText) {
                var match = svgText.match(/<svg[^>]*>([\s\S]*?)<\/svg>/i);
                var inner = match ? match[1].trim() : '';
                if (!inner) {
                    inner = '<circle cx="12" cy="12" r="9" opacity="0.2"></circle>';
                }
                _updateElements(cacheKey, self._esc(name), inner);
            })
            .catch(function () {
                var inner = '<circle cx="12" cy="12" r="9" opacity="0.2"></circle>';
                _updateElements(cacheKey, self._esc(name), inner);
            });
    };

    IconRegistry.prototype.fetchAndRender = function (name, el) {
        var cacheKey = 'heroicons:' + name;
        var cached = _svgCache[cacheKey];
        if (cached) {
            el.innerHTML = cached;
            return;
        }
        if (!_pendingUpdates[cacheKey]) _pendingUpdates[cacheKey] = [];
        _pendingUpdates[cacheKey].push(el);
        if (!_inFlight[cacheKey]) {
            _inFlight[cacheKey] = true;
            this._doFetchHeroicon(name, cacheKey);
        }
    };

    IconRegistry.prototype.createElement = function (library, iconName, options) {
        var div = document.createElement('div');
        div.innerHTML = this.renderIcon(library, iconName, options);
        return div.firstElementChild;
    };

    IconRegistry.prototype.afterRender = function (container) {
        var self = this;
        container = container || document.body;

        // Trigger heroicon fetches for any placeholder SVGs in container
        var heroEls = container.querySelectorAll('[data-heroicon]');
        for (var i = 0; i < heroEls.length; i++) {
            var name = heroEls[i].getAttribute('data-heroicon');
            if (name) {
                var cacheKey = 'heroicons:' + name;
                if (_svgCache[cacheKey]) {
                    heroEls[i].innerHTML = _svgCache[cacheKey];
                } else if (!_inFlight[cacheKey]) {
                    _inFlight[cacheKey] = true;
                    self._doFetchHeroicon(name, cacheKey);
                }
            }
        }

        // Lucide: create icons ONLY if container has lucide elements
        var lucideEls = container.querySelectorAll('[data-lucide]');
        if (lucideEls.length > 0) {
            if (window.lucide && this._lucideLoaded) {
                try {
                    lucide.createIcons({ root: container });
                } catch (e) { }
            } else {
                if (!this._pendingContainers) this._pendingContainers = [];
                this._pendingContainers.push(container);
            }
        }
    };

    IconRegistry.prototype._esc = function (text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    };

    IconRegistry.prototype.getCachedHeroicon = function (name) {
        return _svgCache['heroicons:' + name] || null;
    };

    IconRegistry.prototype.isHeroiconCached = function (name) {
        return !!_svgCache['heroicons:' + name];
    };

    window.IconRegistry = new IconRegistry();
    window.IconRegistry.init();
})();
