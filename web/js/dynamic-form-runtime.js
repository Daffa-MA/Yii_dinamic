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
            '.relation-picker-table tbody tr:hover{background:#f8fafc}'
        ].join('');
        document.head.appendChild(style);
    }

    var pickerDataUrl = '/master-form/relation-picker-data';
    var pickerSearchUrl = '/master-form/relation-picker-search';
    var pickerState = { fieldName: '', formId: '', page: 1, hasNext: false, form: null };

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
            var row = event.target.closest('tr[data-value]');
            if (!row) {
                return;
            }
            setPickerValue(pickerState.form, pickerState.fieldName, row.getAttribute('data-value'), row.getAttribute('data-label'));
            closePicker();
        });
        return modal;
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

    function setPickerValue(form, fieldName, value, label) {
        if (!form || !fieldName) {
            return;
        }
        var hidden = form.querySelector('[data-relation-picker-value="' + cssEscape(fieldName) + '"]');
        var display = form.querySelector('.relation-picker-display[data-field-name="' + cssEscape(fieldName) + '"]');
        if (hidden) {
            hidden.value = value || '';
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (display) {
            display.value = label || '';
        }
        setPickerStatus(form, fieldName, value ? 'Dipilih: ' + (label || value) : '');
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
                var rows = Array.isArray(data.rows) ? data.rows : [];
                pickerState.hasNext = !!(data.pagination && data.pagination.has_next);
                if (pageInfo) {
                    pageInfo.textContent = 'Halaman ' + pickerState.page + ' - ' + ((data.pagination && data.pagination.total) || 0) + ' data';
                }
                if (!rows.length) {
                    content.innerHTML = '<div style="padding:14px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;border-radius:12px;">No data available<br><small>This table does not have any data yet.</small></div>';
                    return;
                }
                var keys = Object.keys(rows[0].display || {});
                content.innerHTML = '<table class="relation-picker-table"><thead><tr>' +
                    keys.map(function(key) { return '<th>' + escapeHtml(key) + '</th>'; }).join('') +
                    '</tr></thead><tbody>' +
                    rows.map(function(row) {
                        return '<tr data-value="' + escapeHtml(row.value) + '" data-label="' + escapeHtml(row.label) + '">' +
                            keys.map(function(key) { return '<td>' + escapeHtml(row.display[key]) + '</td>'; }).join('') +
                        '</tr>';
                    }).join('') +
                    '</tbody></table>';
            })
            .catch(function(error) {
                content.innerHTML = '<div style="padding:14px;border:1px solid #fecaca;background:#fff1f2;color:#9f1239;border-radius:12px;">' + escapeHtml(error.message || 'Gagal memuat data.') + '</div>';
            });
    }

    function bindForm(form) {
        if (!form || form.dataset.dynamicRuntimeBound === '1') {
            return;
        }
        form.dataset.dynamicRuntimeBound = '1';
        installStyle();

        form.querySelectorAll('.relation-picker-display').forEach(function(input) {
            input.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                var fieldName = input.getAttribute('data-field-name') || '';
                var formId = input.getAttribute('data-form-id') || form.getAttribute('data-form-id') || '';
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
                        var matches = data && Array.isArray(data.matches) ? data.matches : [];
                        if (matches.length === 1) {
                            setPickerValue(form, fieldName, matches[0].value, matches[0].label || matches[0].display_text);
                        } else if (matches.length > 1) {
                            openPicker(form, fieldName, formId, query);
                        } else {
                            setPickerStatus(form, fieldName, 'Data tidak ditemukan.');
                        }
                    })
                    .catch(function() { setPickerStatus(form, fieldName, 'Gagal mencari data.'); });
            });
        });

        form.querySelectorAll('[data-relation-picker-open], .relation-picker-button').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                var fieldName = button.getAttribute('data-relation-picker-open') || button.getAttribute('data-field-name') || button.getAttribute('data-picker-field') || '';
                var input = form.querySelector('.relation-picker-display[data-field-name="' + cssEscape(fieldName) + '"]');
                openPicker(form, fieldName, input ? (input.getAttribute('data-form-id') || '') : (form.getAttribute('data-form-id') || ''), input ? input.value : '');
            });
        });
    }

    window.DynamicFormRuntime = {
        __assetRuntime: true,
        init: function(root) {
            var scope = root || document;
            if (scope.matches && scope.matches('form.dynamic-embedded-form')) {
                bindForm(scope);
            }
            if (scope.querySelectorAll) {
                scope.querySelectorAll('form.dynamic-embedded-form').forEach(bindForm);
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
