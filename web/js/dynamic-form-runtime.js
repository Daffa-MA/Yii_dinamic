(function() {
    if (window.DynamicFormRuntime && window.DynamicFormRuntime.__assetRuntime) {
        return;
    }

    function installStyle() {
        if (document.getElementById('dynamic-form-runtime-style')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'dynamic-form-runtime-style';
        style.textContent = [
            '.relation-picker-wrapper{width:100%}',
            '.relation-picker-input-group,.relation-picker-row{display:flex;gap:8px;align-items:stretch;width:100%}',
            '.relation-picker-input-group .dynamic-form-input,.relation-picker-row .dynamic-form-input{flex:1;min-width:0}',
            '.relation-picker-btn,.relation-picker-button{display:inline-flex;align-items:center;justify-content:center;min-height:38px;border:1px solid #dbe3ef;background:#fff;color:#334155;border-radius:10px;padding:0 14px;font-weight:700;cursor:pointer;white-space:nowrap;text-decoration:none}',
            '.relation-picker-status{margin-top:6px;font-size:12px;color:#64748b}',
            '.relation-picker-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:20px;z-index:12000;background:rgba(15,23,42,.48);backdrop-filter:blur(4px)}',
            '.relation-picker-modal.open{display:flex}',
            '.relation-picker-panel{width:min(860px,100%);max-height:min(680px,88vh);background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 28px 90px rgba(15,23,42,.28);overflow:hidden;display:flex;flex-direction:column}',
            '.relation-picker-head,.relation-picker-foot{padding:14px 18px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;gap:12px;align-items:center}',
            '.relation-picker-foot{border-bottom:0;border-top:1px solid #e2e8f0}',
            '.relation-picker-body{padding:16px 18px;overflow:auto}',
            '.relation-picker-search{width:100%;border:1px solid #cbd5e1;border-radius:12px;padding:10px 12px;font-size:14px}',
            '.relation-picker-table{width:100%;border-collapse:collapse}',
            '.relation-picker-table th,.relation-picker-table td{padding:10px 12px;border-bottom:1px solid #eef2f7;text-align:left;font-size:13px}',
            '.relation-picker-table tbody tr{cursor:pointer}',
            '.relation-picker-table tbody tr:hover{background:#f8fafc}',
            '.relation-picker-detail{margin-top:10px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;color:#334155}',
            '.relation-picker-detail-title{font-weight:700;margin-bottom:8px}',
            '.relation-picker-detail-grid{display:grid;gap:6px}'
        ].join('');
        document.head.appendChild(style);
    }

    var pickerDataUrl = '/master-form/relation-picker-data';
    var pickerSearchUrl = '/master-form/relation-picker-search';
    var pickerAutofillUrl = '/master-form/resolve-autofill';
    var pickerState = { fieldName: '', formId: '', page: 1, hasNext: false, form: null };
    var isDevRuntime = /^(localhost|127\.0\.0\.1)$/i.test(window.location.hostname) || window.YII_DEBUG === true;

    function devDebug() {
        if (!isDevRuntime || !window.console || !console.debug) {
            return;
        }
        console.debug.apply(console, arguments);
    }

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

    function isArray(value) {
        return Object.prototype.toString.call(value) === '[object Array]';
    }

    function getFormId(form) {
        if (!form) {
            return '';
        }

        return String(form.getAttribute('data-form-id') || form.getAttribute('data-dynamic-form-id') || '');
    }

    function parseJson(value) {
        if (!value) {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch (e) {
            return null;
        }
    }

    function buildPickerUrl(baseUrl, formId, fieldName, query, page, limit) {
        var params = new URLSearchParams({ form_id: formId, field_name: fieldName, q: query || '' });
        if (page) {
            params.set('page', page);
        }
        if (limit) {
            params.set('limit', limit);
        }
        return baseUrl + '?' + params.toString();
    }

    function setPickerStatus(form, fieldName, text) {
        var status = form ? form.querySelector('[data-relation-picker-status="' + cssEscape(fieldName) + '"]') : null;
        if (status) {
            status.textContent = text || '';
        }
    }

    function getPickerHiddenInput(form, fieldName) {
        if (!form || !fieldName) {
            return null;
        }

        return form.querySelector(
            '[data-relation-picker-value="' + cssEscape(fieldName) + '"]' +
            ', input[type="hidden"][name="' + cssEscape(fieldName) + '"]'
        );
    }

    function getPickerDisplayInput(form, fieldName) {
        if (!form || !fieldName) {
            return null;
        }

        return form.querySelector(
            '.relation-picker-display[data-field-name="' + cssEscape(fieldName) + '"]' +
            ', [data-relation-picker-display-for="' + cssEscape(fieldName) + '"]' +
            ', [data-display-for="' + cssEscape(fieldName) + '"]' +
            ', input[name="__fk_display_' + cssEscape(fieldName) + '"]'
        );
    }

    function getPickerDetailContainer(form, fieldName) {
        if (!form || !fieldName) {
            return null;
        }

        return form.querySelector(
            '[data-relation-picker-detail="' + cssEscape(fieldName) + '"]' +
            ', [data-relation-picker-detail-for="' + cssEscape(fieldName) + '"]'
        );
    }

    function ensurePickerDetailContainer(form, fieldName) {
        var existing = getPickerDetailContainer(form, fieldName);
        if (existing) {
            return existing;
        }

        var hidden = getPickerHiddenInput(form, fieldName);
        var wrapper = hidden ? hidden.closest('.relation-picker-wrapper, .relation-picker-row, .relation-picker-input-group') : null;
        if (!wrapper) {
            return null;
        }

        var container = document.createElement('div');
        container.className = 'relation-picker-detail';
        container.setAttribute('data-relation-picker-detail', fieldName);
        container.hidden = true;
        wrapper.insertAdjacentElement('afterend', container);
        return container;
    }

    function setControlValue(control, value, options) {
        if (!control) {
            return;
        }

        var opts = options || {};
        var normalizedValue = value;
        if (normalizedValue === null || normalizedValue === undefined) {
            normalizedValue = '';
        }

        var tagName = String(control.tagName || '').toLowerCase();
        var type = String(control.type || '').toLowerCase();
        var isMultiple = !!control.multiple || /\[\]$/.test(String(control.name || ''));

        if (tagName === 'select') {
            if (isArray(normalizedValue)) {
                Array.prototype.forEach.call(control.options || [], function(option) {
                    option.selected = normalizedValue.indexOf(option.value) !== -1;
                });
            } else {
                control.value = String(normalizedValue);
            }
        } else if (type === 'checkbox') {
            if (isArray(normalizedValue)) {
                control.checked = normalizedValue.indexOf(control.value) !== -1;
            } else if (typeof normalizedValue === 'boolean') {
                control.checked = normalizedValue;
            } else {
                var normalizedCheckboxValue = String(normalizedValue).toLowerCase();
                control.checked = normalizedCheckboxValue === '1' || normalizedCheckboxValue === 'true' || normalizedCheckboxValue === control.value;
            }
        } else if (type === 'radio') {
            var radioValue = String(normalizedValue);
            var radios = control.form ? control.form.querySelectorAll('input[type="radio"][name="' + cssEscape(control.name) + '"]') : [];
            Array.prototype.forEach.call(radios, function(radio) {
                radio.checked = radio.value === radioValue;
            });
        } else if (isMultiple && isArray(normalizedValue)) {
            control.value = normalizedValue.join(',');
        } else {
            control.value = String(normalizedValue);
        }

        if (opts.markReadonly) {
            if (tagName === 'input' || tagName === 'textarea') {
                control.readOnly = true;
                control.setAttribute('aria-readonly', 'true');
            } else if (tagName === 'select') {
                control.disabled = true;
                control.setAttribute('aria-readonly', 'true');
            }
        }

        if (opts.dispatchEvents !== false) {
            control.dispatchEvent(new Event('input', { bubbles: true }));
            if (opts.fromAutofill) {
                control.dispatchEvent(new CustomEvent('change', {
                    bubbles: true,
                    detail: { fromAutofill: true }
                }));
            } else {
                control.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    function renderDetailItems(items) {
        if (!isArray(items) || items.length === 0) {
            return '<div style="font-size:13px;color:#64748b;">Data detail tidak tersedia.</div>';
        }

        return '<div class="relation-picker-detail-grid">' + items.map(function(item) {
            var label = item && item.label !== undefined ? item.label : '';
            var value = item && item.value !== undefined ? item.value : '';
            return '<div style="display:flex;gap:10px;justify-content:space-between;align-items:flex-start;">' +
                '<div style="font-weight:600;">' + escapeHtml(label) + '</div>' +
                '<div style="text-align:right;word-break:break-word;">' + escapeHtml(value) + '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function renderPickerDetailCard(form, fieldName, display) {
        var container = ensurePickerDetailContainer(form, fieldName);
        if (!container) {
            return;
        }

        if (!display || display.enabled === false) {
            container.hidden = true;
            container.innerHTML = '';
            return;
        }

        var title = display.detail_title || display.title || 'Detail';
        var items = isArray(display.items) ? display.items : [];
        container.innerHTML = '<div class="relation-picker-detail-title">' + escapeHtml(title) + '</div>' + renderDetailItems(items);
        container.hidden = false;
    }

    function applyAutofillValues(form, triggerField, values, display) {
        if (!form || !values) {
            return;
        }

        Object.keys(values).forEach(function(fieldName) {
            var value = values[fieldName];
            var controls = form.querySelectorAll(
                '[name="' + cssEscape(fieldName) + '"]' +
                ', [name="' + cssEscape(fieldName) + '[]"]' +
                ', [data-autofill-target="' + cssEscape(fieldName) + '"]'
            );

            Array.prototype.forEach.call(controls, function(control) {
                if (!control) {
                    return;
                }

                if (control.getAttribute('data-autofill-skip') === '1') {
                    return;
                }

                var behavior = String(control.getAttribute('data-field-behavior') || '').toLowerCase();
                var lockControl = behavior === 'readonly' || behavior === 'display_only';
                setControlValue(control, value, {
                    dispatchEvents: true,
                    markReadonly: lockControl,
                    fromAutofill: true
                });

                if (lockControl) {
                    if (String(control.tagName || '').toLowerCase() === 'select') {
                        var mirrorName = control.name || fieldName;
                        var mirror = control.form ? control.form.querySelector('input[type="hidden"][data-autofill-mirror-for="' + cssEscape(mirrorName) + '"]') : null;
                        if (!mirror) {
                            mirror = document.createElement('input');
                            mirror.type = 'hidden';
                            mirror.name = mirrorName;
                            mirror.setAttribute('data-autofill-mirror-for', mirrorName);
                            control.insertAdjacentElement('afterend', mirror);
                        }
                        mirror.value = isArray(value) ? value.join(',') : String(value == null ? '' : value);
                    }
                }
            });
        });

        if (display) {
            renderPickerDetailCard(form, triggerField, display);
        }
    }

    function applyAutofillResponse(form, triggerField, triggerValue) {
        if (!form || !triggerField || triggerValue === '' || triggerValue === null || triggerValue === undefined) {
            return;
        }

        var formId = getFormId(form);
        if (!formId) {
            return;
        }

        var url = new URL(pickerAutofillUrl, window.location.origin);
        url.searchParams.set('form_id', formId);
        url.searchParams.set('trigger_field', triggerField);
        url.searchParams.set('trigger_value', String(triggerValue));

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) || 'Data relasi tidak ditemukan.');
                }

                applyAutofillValues(form, triggerField, data.values || {}, data.display || null);
            })
            .catch(function(error) {
                var message = error && error.message ? error.message : 'Data relasi tidak ditemukan.';
                setPickerStatus(form, triggerField, message);
            });
    }

    function selectRelationPickerValue(form, fieldName, selected) {
        if (!form || !fieldName) {
            return;
        }

        var hidden = getPickerHiddenInput(form, fieldName);
        var display = getPickerDisplayInput(form, fieldName);
        var value = selected && selected.value !== undefined ? selected.value : '';
        var label = selected && selected.label !== undefined ? selected.label : (selected && selected.display_text !== undefined ? selected.display_text : value);

        if (hidden) {
            hidden.dataset.selectedValue = String(value == null ? '' : value);
            hidden.dataset.selectedLabel = String(label == null ? '' : label);
            hidden.dataset.selectedData = selected ? JSON.stringify(selected) : '';
            hidden.value = value == null ? '' : String(value);
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            hidden.dispatchEvent(new CustomEvent('relation-picker:selected', {
                bubbles: true,
                detail: selected || { value: value, label: label }
            }));
        }

        if (display) {
            display.value = label == null ? '' : String(label);
            display.dispatchEvent(new Event('input', { bubbles: true }));
            display.dispatchEvent(new Event('change', { bubbles: true }));
        }

        setPickerStatus(form, fieldName, value ? 'Dipilih: ' + (label || value) : '');

    }

    function ensureModal() {
        var modal = document.getElementById('dynamicRelationPickerModal');
        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.id = 'dynamicRelationPickerModal';
        modal.className = 'relation-picker-modal';
        modal.innerHTML =
            '<div class="relation-picker-panel">' +
                '<div class="relation-picker-head"><strong>Pilih Data</strong><button type="button" class="relation-picker-btn" data-picker-close>Tutup</button></div>' +
                '<div class="relation-picker-body"><input type="text" class="relation-picker-search" data-picker-search placeholder="Cari data..."><div data-picker-content style="margin-top:14px;"></div></div>' +
                '<div class="relation-picker-foot"><button type="button" class="relation-picker-btn" data-picker-prev>Sebelumnya</button><span data-picker-page style="font-size:13px;color:#64748b;"></span><button type="button" class="relation-picker-btn" data-picker-next>Berikutnya</button></div>' +
            '</div>';
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
            var row = event.target && event.target.closest ? event.target.closest('tr[data-value]') : null;
            if (!row) {
                return;
            }

            var selected = parseJson(row.getAttribute('data-selected')) || {
                value: row.getAttribute('data-value'),
                label: row.getAttribute('data-label'),
                display: []
            };
            selectRelationPickerValue(pickerState.form, pickerState.fieldName, selected);
            closePicker();
        });

        return modal;
    }

    function openPicker(form, fieldName, formId, query) {
        pickerState = { fieldName: fieldName, formId: formId, page: 1, hasNext: false, form: form };
        var modal = ensureModal();
        var search = modal.querySelector('[data-picker-search]');
        if (search) {
            search.value = query || '';
        }
        modal.classList.add('open');
        loadPickerPage();
    }

    function closePicker() {
        var modal = document.getElementById('dynamicRelationPickerModal');
        if (modal) {
            modal.classList.remove('open');
        }
    }

    function loadPickerPage() {
        var modal = ensureModal();
        var search = modal.querySelector('[data-picker-search]');
        var content = modal.querySelector('[data-picker-content]');
        var pageInfo = modal.querySelector('[data-picker-page]');
        var query = search ? search.value : '';
        content.innerHTML = '<div style="font-size:13px;color:#64748b;">Loading...</div>';

        fetch(buildPickerUrl(pickerDataUrl, pickerState.formId, pickerState.fieldName, query, pickerState.page), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) || 'Gagal memuat data');
                }

                var rows = isArray(data.rows) ? data.rows : [];
                pickerState.hasNext = !!(data.pagination && data.pagination.has_next);
                if (pageInfo) {
                    pageInfo.textContent = 'Halaman ' + pickerState.page + ' - ' + ((data.pagination && data.pagination.total) || 0) + ' data';
                }

                if (!rows.length) {
                    content.innerHTML = '<div style="padding:14px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;border-radius:12px;">Tidak ada data yang cocok.</div>';
                    return;
                }

                var keys = Object.keys(rows[0].display || {});
                content.innerHTML = '<table class="relation-picker-table"><thead><tr>' +
                    keys.map(function(key) { return '<th>' + escapeHtml(key) + '</th>'; }).join('') +
                    '</tr></thead><tbody>' +
                    rows.map(function(row) {
                        return '<tr data-value="' + escapeHtml(row.value) + '" data-label="' + escapeHtml(row.label) + '" data-selected="' + escapeHtml(JSON.stringify(row)) + '">' +
                            keys.map(function(key) {
                                return '<td>' + escapeHtml((row.display || {})[key]) + '</td>';
                            }).join('') +
                        '</tr>';
                    }).join('') +
                    '</tbody></table>';
            })
            .catch(function(error) {
                content.innerHTML = '<div style="padding:14px;border:1px solid #fecaca;background:#fff1f2;color:#9f1239;border-radius:12px;">' + escapeHtml((error && error.message) || 'Gagal memuat data.') + '</div>';
            });
    }

    function handlePickerButtonClick(button, event) {
        if (!button) {
            return;
        }

        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        var form = findDynamicFormFromButton(button);
        if (!form) {
            return;
        }

        bindForm(form);
        var wrapper = button.closest ? button.closest('.relation-picker-wrapper') : null;
        var fieldName = button.getAttribute('data-relation-picker-open') || button.getAttribute('data-field-name') || button.getAttribute('data-picker-field') || (wrapper ? wrapper.getAttribute('data-field-name') : '') || '';
        var input = getPickerDisplayInput(form, fieldName);
        openPicker(form, fieldName, input ? (input.getAttribute('data-form-id') || getFormId(form)) : getFormId(form), input ? input.value : '');
    }

    function findDynamicFormFromButton(button) {
        if (!button || !button.closest) {
            return null;
        }

        return button.closest('[data-dynamic-form-instance]') || button.closest('form.dynamic-embedded-form') || button.closest('form');
    }

    function handleRelationPickerHiddenChange(hidden, event) {
        if (!hidden) {
            return;
        }

        if (event && event.detail && event.detail.fromAutofill) {
            return;
        }

        var form = hidden.closest('form');
        if (!form) {
            return;
        }

        var fieldName = hidden.getAttribute('data-relation-picker-value') || hidden.name || '';
        if (!fieldName) {
            return;
        }

        var value = hidden.value || '';
        if (value === '') {
            var detail = getPickerDetailContainer(form, fieldName);
            if (detail) {
                detail.hidden = true;
                detail.innerHTML = '';
            }
            setPickerStatus(form, fieldName, '');
            return;
        }

        applyAutofillResponse(form, fieldName, value);
    }

    function bindForm(form) {
        if (!form || form.dataset.dynamicRuntimeBound === '1') {
            return;
        }

        form.dataset.dynamicRuntimeBound = '1';
        form.dataset.dynamicFormInitialized = '1';
        installStyle();

        devDebug('Initializing dynamic form', form.getAttribute('data-dynamic-form-instance') || form.id || getFormId(form) || '');

        Array.prototype.forEach.call(form.querySelectorAll('.relation-picker-display'), function(input) {
            if (input.dataset.dynamicRuntimeBound === '1') {
                return;
            }

            input.dataset.dynamicRuntimeBound = '1';
            input.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                var fieldName = input.getAttribute('data-field-name') || '';
                var formId = input.getAttribute('data-form-id') || getFormId(form) || '';
                var mode = input.getAttribute('data-picker-mode') || 'autocomplete';
                var query = input.value || '';
                if (mode === 'modal_picker' || mode === 'autocomplete_with_modal') {
                    openPicker(form, fieldName, formId, query);
                    return;
                }

                setPickerStatus(form, fieldName, 'Mencari data...');
                fetch(buildPickerUrl(pickerSearchUrl, formId, fieldName, query, null, 10), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (!data || data.success === false) {
                            throw new Error((data && data.message) || 'Gagal mencari data.');
                        }

                        var matches = data && isArray(data.matches) ? data.matches : [];
                        if (matches.length === 1) {
                            selectRelationPickerValue(form, fieldName, matches[0]);
                        } else if (matches.length > 1) {
                            openPicker(form, fieldName, formId, query);
                        } else {
                            setPickerStatus(form, fieldName, 'Data tidak ditemukan.');
                        }
                    })
                    .catch(function(error) {
                        setPickerStatus(form, fieldName, (error && error.message) ? error.message : 'Gagal mencari data.');
                    });
            });
        });

        Array.prototype.forEach.call(form.querySelectorAll('.relation-picker-value'), function(hidden) {
            if (hidden.dataset.dynamicRuntimeBound === '1') {
                return;
            }

            hidden.dataset.dynamicRuntimeBound = '1';
            hidden.addEventListener('change', function(event) {
                handleRelationPickerHiddenChange(hidden, event);
            });

            if (hidden.value) {
                handleRelationPickerHiddenChange(hidden, { detail: {} });
            }
        });

        Array.prototype.forEach.call(form.querySelectorAll('[data-relation-picker-open], .relation-picker-button'), function(button) {
            if (button.dataset.dynamicRuntimeBound === '1') {
                return;
            }

            button.dataset.dynamicRuntimeBound = '1';
            button.addEventListener('click', function(event) {
                handlePickerButtonClick(button, event);
            });
        });
    }

    document.addEventListener('click', function(event) {
        var button = event.target && event.target.closest ? event.target.closest('.relation-picker-button, [data-relation-picker-open]') : null;
        if (!button) {
            return;
        }

        handlePickerButtonClick(button, event);
    }, true);

    window.DynamicFormRuntime = {
        __assetRuntime: true,
        init: function(root) {
            var scope = root || document;
            if (scope.matches && scope.matches('form')) {
                bindForm(scope);
            }
            if (scope.querySelectorAll) {
                Array.prototype.forEach.call(scope.querySelectorAll('form.dynamic-embedded-form, [data-dynamic-form-instance], form'), function(form) {
                    if (form.tagName === 'FORM') {
                        bindForm(form);
                    }
                });
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.DynamicFormRuntime.init(document);
        });
    } else {
        window.DynamicFormRuntime.init(document);
    }
})();
