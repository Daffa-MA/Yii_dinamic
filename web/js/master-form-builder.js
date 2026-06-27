    // ===== DEBOUNCE HELPER (BUG 1 FIX) =====
    // Fungsi debounce untuk mencegah infinite loop / re-render berlebihan
    function debounce(fn, delay) {
        let timer = null;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // ===== THROTTLE HELPER (BUG 1 FIX) =====
    // Fungsi throttle untuk membatasi frekuensi eksekusi fungsi
    function throttle(fn, limit) {
        let inThrottle = false;
        return function(...args) {
            if (!inThrottle) {
                fn.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    // ===== RE-RENDER GUARD (BUG 1 FIX) =====
    // Mencegah renderPropsPanel() memanggil dirinya sendiri secara rekursif
    let _renderPropsPanelGuard = false;
    let _renderPropsPanelLock = null; // BUG 1 FIX: Lock untuk prevent double render
    
    // ===== RENDER LOCK (BUG 1 FIX) =====
    // Lock yang lebih robust untuk mencegah double render
    function acquireRenderLock(timeout) {
        if (_renderPropsPanelLock) {
            return false;
        }
        _renderPropsPanelLock = setTimeout(() => {
            _renderPropsPanelLock = null;
        }, timeout || 100);
        return true;
    }
    
    function releaseRenderLock() {
        if (_renderPropsPanelLock) {
            clearTimeout(_renderPropsPanelLock);
            _renderPropsPanelLock = null;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const dropZone = document.getElementById('canvas-drop-zone');
        const container = document.getElementById('fields-container');
        const placeholder = document.getElementById('canvas-placeholder');
        const propsPanel = document.getElementById('properties-panel');
        const formDataInput = document.getElementById('form-data-input');
        const fieldCountHint = document.getElementById('field-count-hint');
        const componentItems = document.querySelectorAll('.component-item');

        let formFields = [];
        let selectedIndex = null;
        let currentDevice = 'desktop';
        let dropdownSourceTables = [];
        const dropdownSourceColumnsCache = {};

        // Initialize dropdown sources when page loads
        ensureDropdownSourceTablesLoaded().then(tables => {
            console.log('Dropdown tables loaded:', tables.length);
        }).catch(err => {
            console.error('Failed to load dropdown tables:', err);
        });
        let gpsCameraMetadataLoadToken = 0;

        // ===== BUG 2 FIX: Load existing form data dengan safe JSON.parse + try-catch =====
        // **KRITIKAL**: Original code tidak pernah memanggil renderFields() setelah parse data,
        // sehingga data ada di memory tapi DOM canvas tetap kosong (blank canvas).
        // User terpaksa drag satu component dulu untuk trigger render.
        function loadExistingFormData() {
            try {
                var existingDataRaw = formDataInput ? formDataInput.value : '[]';
                // BUG 2 FIX: JSON.parse dengan try-catch agar tidak crash
                var existingData;
                try {
                    existingData = JSON.parse(existingDataRaw);
                } catch (parseErr) {
                    console.error('BUG 2 FIX: Gagal parse JSON form data:', parseErr);
                    existingData = null;
                }
                // PERBAIKAN BUG 2: Handle double-encoded JSON string dari update.php lama
                if (typeof existingData === 'string' && existingData.trim() !== '') {
                    try {
                        existingData = JSON.parse(existingData);
                    } catch (innerParseErr) {
                        console.error('BUG 2 FIX: Gagal parse JSON nested form data:', innerParseErr);
                        existingData = null;
                    }
                }
                if (existingData) {
                    if (!Array.isArray(existingData) && typeof existingData === 'object') {
                        // Format: { fields: [...] }
                        formFields = Array.isArray(existingData.fields) ? JSON.parse(JSON.stringify(existingData.fields)) : [];
                    } else if (Array.isArray(existingData) && existingData.length > 0) {
                        // Format langsung: [{...}, {...}]
                        formFields = JSON.parse(JSON.stringify(existingData));
                    }
                    // Normalisasi setiap field dengan error handling
                    formFields = formFields.map(function(field, fieldIndex) {
                        try {
                            return normalizeFieldState(field);
                        } catch (normErr) {
                            console.error('BUG 2 FIX: Gagal normalisasi field index ' + fieldIndex + ':', normErr, field);
                            return field;
                        }
                    });
                    try {
                        removeSystemFieldsFromState();
                    } catch (removeErr) {
                        console.error('BUG 2 FIX: Gagal remove system fields:', removeErr);
                    }
                }
            } catch (err) {
                console.error('BUG 2 FIX: Gagal load form data:', err);
                formFields = [];
            }
        }

        // PERBAIKAN BUG 1 & 2: Jangan panggil renderFields() di sini — fieldIcons belum didefinisikan (TDZ).
        // Initial render dipindah ke initializeBuilderFromStoredData() di akhir DOMContentLoaded.

        // Field Configuration
        const fieldConfig = {
            text: {
                label: 'Text Input',
                inputType: 'text',
                placeholder: 'Masukkan teks...'
            },
            email: {
                label: 'Email',
                inputType: 'email',
                placeholder: 'email@example.com'
            },
            password: {
                label: 'Password',
                inputType: 'password',
                placeholder: ''
            },
            number: {
                label: 'Number',
                inputType: 'number',
                placeholder: ''
            },
            tel: {
                label: 'Phone',
                inputType: 'tel',
                placeholder: '+62 xxx'
            },
            url: {
                label: 'URL',
                inputType: 'url',
                placeholder: 'https://...'
            },
            textarea: {
                label: 'Textarea',
                inputType: 'textarea',
                rows: 4,
                placeholder: 'Masukkan teks panjang...'
            },
            select: {
                label: 'Dropdown',
                inputType: 'select',
                option_source: 'manual',
                dropdown_source: 'static_options',
                options: [{
                    value: '',
                    label: 'Pilih...'
                }, {
                    value: 'opt1',
                    label: 'Opsi 1'
                }]
            },
            radio: {
                label: 'Radio Group',
                inputType: 'radio',
                option_source: 'manual',
                dropdown_source: 'static_options',
                options: [{
                    value: 'opt1',
                    label: 'Opsi 1'
                }, {
                    value: 'opt2',
                    label: 'Opsi 2'
                }]
            },
            checkbox: {
                label: 'Checkbox',
                inputType: 'checkbox',
                labelText: 'Centang ini'
            },
            checkboxes: {
                label: 'Checkboxes',
                inputType: 'checkboxes',
                option_source: 'manual',
                dropdown_source: 'static_options',
                options: [{
                    value: 'opt1',
                    label: 'Opsi 1'
                }, {
                    value: 'opt2',
                    label: 'Opsi 2'
                }]
            },
            date: {
                label: 'Date',
                inputType: 'date'
            },
            time: {
                label: 'Time',
                inputType: 'time'
            },
            datetime: {
                label: 'Date Time',
                inputType: 'datetime-local'
            },
            file: {
                label: 'File Upload',
                inputType: 'file'
            },
            gps_camera: {
                label: 'GPS Camera',
                inputType: 'gps_camera',
                auto_capture_gps: true,
                auto_capture_timestamp: true,
                preview_image: true,
                gps_camera_bindings: []
            },
            hidden: {
                label: 'Hidden',
                inputType: 'hidden'
            }
        };

        // PERBAIKAN BUG 1: Debounce/throttle untuk render canvas & props panel
        let _isRenderingFields = false;
        const debouncedRenderFields = debounce(function() {
            renderFieldsImmediate();
        }, 120);
        const throttledRenderPropsPanel = throttle(function(field) {
            renderPropsPanelImmediate(field);
        }, 150);

        function renderFieldsImmediate() {
            if (_isRenderingFields) {
                return;
            }
            if (!acquireRenderLock(150)) {
                debouncedRenderFields();
                return;
            }
            _isRenderingFields = true;
            try {
                renderFields();
            } finally {
                _isRenderingFields = false;
                releaseRenderLock();
            }
        }

        function scheduleRenderFields() {
            debouncedRenderFields();
        }

        function renderPropsPanelImmediate(field) {
            if (!acquireRenderLock(120)) {
                return;
            }
            try {
                renderPropsPanel(field);
            } finally {
                releaseRenderLock();
            }
        }

        // Field Icons
        const fieldIcons = {
            text: 'text_fields',
            email: 'email',
            password: 'lock',
            number: 'pin',
            tel: 'phone',
            url: 'link',
            textarea: 'notes',
            select: 'arrow_drop_down_circle',
            radio: 'radio_button_checked',
            checkbox: 'check_box',
            checkboxes: 'checklist',
            date: 'calendar_today',
            time: 'schedule',
            datetime: 'event',
            gps_camera: 'photo_camera',
            file: 'upload_file',
            hidden: 'visibility_off'
        };

        // Device Switching
        window.setDevice = function(device) {
            currentDevice = device;
            const frame = document.getElementById('canvas-frame');
            if (frame) {
                frame.classList.remove('device-desktop', 'device-tablet', 'device-mobile');
                frame.classList.add('device-' + device);
            }
            document.querySelectorAll('.device-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.device === device);
            });
        };

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function escapeAttr(value) {
            return escapeHtml(value);
        }

        function cssEscape(value) {
            return window.CSS && CSS.escape ? CSS.escape(value) : String(value || '').replace(/"/g, '\\"');
        }


        function getDropdownSourceMode(field) {
            return field.dropdown_source === 'table' ? 'table' : 'manual';
        }

        function getOptionSourceMode(field) {
            if (!field || typeof field !== 'object') {
                return 'manual';
            }
            if (field.option_source === 'preset' || field.option_preset) {
                return 'preset';
            }
            if (field.option_source === 'table' || field.dropdown_source === 'table') {
                return 'table';
            }
            return 'manual';
        }

        function hasResolvedRelationConfig(field) {
            if (!field || typeof field !== 'object') {
                return false;
            }

            const relationConfig = field.relation_config && typeof field.relation_config === 'object' ?
                field.relation_config :
                (field.relationConfig && typeof field.relationConfig === 'object' ? field.relationConfig : {});

            const localColumn = String(field.local_column || field.name || field.field_name || field.field_key || relationConfig.local_column || '').trim();
            const referencedTable = String(field.fk_referenced_table || field.source_table_name || field.referenced_table_name || relationConfig.referenced_table || relationConfig.referenced_table_name || '').trim();
            const referencedValueColumn = String(field.fk_referenced_column || field.value_column || field.dropdown_value_column || field.referenced_value_column || field.referenced_column_name || relationConfig.referenced_value_column || relationConfig.value_column || relationConfig.referenced_column || '').trim();

            return localColumn !== '' && referencedTable !== '' && referencedValueColumn !== '';
        }

        function syncRelationConfig(field) {
            if (!field || typeof field !== 'object') {
                return field;
            }

            const localColumn = String(field.name || field.field_name || field.field_key || field.column_name || field.local_column || '').trim();
            const referencedTable = String(field.fk_referenced_table || field.source_table_name || field.referenced_table_name || '').trim();
            const referencedValueColumn = String(field.fk_referenced_column || field.value_column || field.dropdown_value_column || field.referenced_column_name || '').trim();
            const displayColumn = String(field.fk_display_column || field.label_column || field.dropdown_label_column || '').trim();

            field.local_column = localColumn;
            field.source_column = localColumn;
            field.source_column_name = field.source_column_name || localColumn;
            field.referenced_table_name = referencedTable;
            field.referenced_value_column = referencedValueColumn;
            field.referenced_column_name = referencedValueColumn;
            field.display_column = displayColumn;
            field.value_column = referencedValueColumn;
            field.dropdown_value_column = referencedValueColumn;
            field.label_column = displayColumn;
            field.dropdown_label_column = displayColumn;
            field.fk_referenced_table = referencedTable;
            field.fk_referenced_column = referencedValueColumn;
            field.fk_display_column = displayColumn;
            field.relation_table_name = referencedTable;
            field.relation_target_column = localColumn;
            field.relation_value_column = referencedValueColumn;
            field.relation_display_column = displayColumn;
            field.relation_config = Object.assign({}, field.relation_config || {}, {
                local_column: localColumn,
                source_column: localColumn,
                column_name: localColumn,
                referenced_table: referencedTable,
                referenced_table_name: referencedTable,
                referenced_value_column: referencedValueColumn,
                referenced_column: referencedValueColumn,
                referenced_column_name: referencedValueColumn,
                value_column: referencedValueColumn,
                display_column: displayColumn,
                display_column_name: displayColumn
            });

            return field;
        }

        function ensureRelationPickerConfig(field) {
            if (!field || typeof field !== 'object') return {};
            const picker = field.picker_config && typeof field.picker_config === 'object' ? field.picker_config : {};
            const displayColumn = field.fk_display_column || field.label_column || field.dropdown_label_column || field.fk_referenced_column || field.value_column || 'id';
            const valueColumn = field.fk_referenced_column || field.value_column || field.dropdown_value_column || 'id';
            field.picker_mode = field.picker_mode || 'dropdown';
            field.picker_config = Object.assign({
                main_table: field.fk_referenced_table || field.source_table_name || '',
                value_column: valueColumn,
                display_column: displayColumn,
                search_columns: displayColumn ? [displayColumn] : [],
                display_columns: Array.from(new Set([displayColumn, valueColumn].filter(Boolean))),
                picker_fk_display_columns: {},
                search_target: 'display_only',
                page_size: 10
            }, picker);
            if (!field.picker_config.picker_fk_display_columns || typeof field.picker_config.picker_fk_display_columns !== 'object' || Array.isArray(field.picker_config.picker_fk_display_columns)) {
                field.picker_config.picker_fk_display_columns = {};
            }
            return field.picker_config;
        }

        function normalizeRelationPickerColumnList(value) {
            if (Array.isArray(value)) {
                return value.map(item => String(item || '').trim()).filter(Boolean);
            }

            return String(value || '')
                .split(',')
                .map(item => item.trim())
                .filter(Boolean);
        }

        function isSensitivePickerDisplayColumn(columnName) {
            return /password|token|secret|remember|auth|salt|hash|api_key/i.test(String(columnName || ''));
        }

        function isSafePickerDisplayColumnMeta(column) {
            const columnName = String(column && column.name ? column.name : '').trim();
            if (!columnName || isSensitivePickerDisplayColumn(columnName)) {
                return false;
            }
            return true;
        }

        function findRelationPickerColumnMeta(field, columnName) {
            const tableId = resolveForeignKeyReferencedTableId(field);
            const columns = dropdownSourceColumnsCache[String(tableId)] || [];
            return columns.find(column => String(column.name || '') === String(columnName || '')) || null;
        }

        function findDropdownTableIdByName(tableName) {
            const table = findDropdownTableByName(tableName);
            return table ? String(table.id || '') : '';
        }

        function getReferencedColumnsSync(tableName) {
            const tableId = findDropdownTableIdByName(tableName);
            return tableId ? (dropdownSourceColumnsCache[String(tableId)] || []) : [];
        }

        function autoDetectRelationDisplayColumn(columns, valueColumn) {
            const normalizedValue = String(valueColumn || '').toLowerCase();
            const priorities = ['nama', 'name', 'title', 'label', 'kode', 'code', 'tier', 'kategori', 'status'];
            for (const candidate of priorities) {
                const match = columns.find(column => {
                    const name = String(column.name || '').toLowerCase();
                    return name === candidate && name !== normalizedValue && isSafePickerDisplayColumnMeta(column);
                });
                if (match) {
                    return match.name;
                }
            }
            return '';
        }

        function buildReferencedDisplayColumnOptions(tableName, selectedColumn) {
            const columns = getReferencedColumnsSync(tableName);
            let html = '<option value="">Pilih display column...</option>';
            columns.filter(isSafePickerDisplayColumnMeta).forEach(column => {
                const columnName = String(column.name || '').trim();
                html += '<option value="' + escapeAttr(columnName) + '"' + boolAttr('selected', String(selectedColumn || '') === columnName) + '>' + escapeHtml((column.label || columnName) + ' (' + columnName + ')') + '</option>';
            });
            return html;
        }

        function buildRelationPickerFkDisplayEditor(field, columnName, columnMeta) {
            const picker = ensureRelationPickerConfig(field);
            const mapping = picker.picker_fk_display_columns[columnName] || {};
            const referencedTable = String(columnMeta.referenced_table_name || columnMeta.referenced_table || '').trim();
            const referencedColumn = String(columnMeta.referenced_column_name || columnMeta.referenced_column || '').trim();
            const mode = mapping.mode === 'relation_display' ? 'relation_display' : 'raw_id';
            const displayColumn = String(mapping.display_column || '').trim();
            const options = buildReferencedDisplayColumnOptions(referencedTable, displayColumn);
            const hasReferencedColumns = getReferencedColumnsSync(referencedTable).length > 0;

            return '<div style="margin-top:10px;padding:10px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;">' +
                '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;">' +
                '<span style="font-size:11px;font-weight:700;color:#92400e;background:#fef3c7;border-radius:999px;padding:2px 8px;">FK</span>' +
                '<span style="font-size:11px;color:#64748b;">' + escapeHtml(referencedTable + '.' + referencedColumn) + '</span>' +
                '</div>' +
                '<label class="prop-label">Display Mode</label>' +
                '<select class="prop-select" data-picker-fk-mode="' + escapeAttr(columnName) + '" onchange="toggleRelationPickerFkDisplayMode(\'' + escapeAttr(columnName) + '\', this.value)">' +
                '<option value="raw_id"' + boolAttr('selected', mode === 'raw_id') + '>Raw ID</option>' +
                '<option value="relation_display"' + boolAttr('selected', mode === 'relation_display') + '>Display dari Relasi</option>' +
                '</select>' +
                '<div data-picker-fk-relation-panel="' + escapeAttr(columnName) + '" style="' + (mode === 'relation_display' ? '' : 'display:none;') + 'margin-top:10px;">' +
                '<label class="prop-label">Referenced Table</label>' +
                '<input type="text" class="prop-input" value="' + escapeAttr(referencedTable) + '" data-picker-fk-ref-table="' + escapeAttr(columnName) + '" readonly style="background:#eef2f7;">' +
                '<label class="prop-label" style="margin-top:8px;">Display Column</label>' +
                (hasReferencedColumns ?
                    '<select class="prop-select" data-picker-fk-display-column="' + escapeAttr(columnName) + '">' + options + '</select>' :
                    '<div style="font-size:12px;color:#b45309;line-height:1.5;">Kolom table relasi belum termuat.</div>') +
                '</div>' +
                '</div>';
        }

        function buildRelationPickerColumnChecklist(field, kind) {
            const picker = ensureRelationPickerConfig(field);
            const tableId = field.source_table_id || field.dropdown_table_id || '';
            const columns = dropdownSourceColumnsCache[String(tableId)] || [];
            const selected = new Set(normalizeRelationPickerColumnList(picker[kind + '_columns']));

            if (!tableId) {
                return '<div style="font-size:12px;color:#b45309;line-height:1.5;">Table relasi belum dipilih.</div>';
            }

            if (!columns.length) {
                return '<div style="font-size:12px;color:#64748b;line-height:1.5;">Kolom table belum termuat. Simpan atau pilih table terlebih dulu.</div>';
            }

            return columns.map(column => {
                const columnName = String(column.name || '').trim();
                if (!columnName) {
                    return '';
                }
                const label = column.label || columnName;
                const checked = selected.has(columnName);
                const filterText = (String(label) + ' ' + columnName).toLowerCase();
                const isFkDisplayColumn = kind === 'display' && !!column.is_foreign_key && String(column.referenced_table_name || column.referenced_table || '').trim() !== '';

                return '<div class="relation-picker-column-item" data-relation-picker-column-item="' + kind + '" data-relation-picker-column-text="' + escapeAttr(filterText) + '" style="padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;">' +
                    '<label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">' +
                    '<input type="checkbox" data-relation-picker-kind="' + kind + '" value="' + escapeAttr(columnName) + '" ' + (checked ? 'checked' : '') + ' style="margin-top:3px;width:16px;height:16px;">' +
                    '<span style="display:flex;flex-direction:column;gap:2px;min-width:0;">' +
                    '<span style="font-weight:600;color:#0f172a;">' + escapeHtml(label) + (isFkDisplayColumn ? ' <span style="font-size:10px;color:#92400e;background:#fef3c7;border-radius:999px;padding:2px 6px;">FK</span>' : '') + '</span>' +
                    '<span style="font-size:11px;color:#64748b;">' + escapeHtml(columnName) + '</span>' +
                    '</span>' +
                    '</label>' +
                    (isFkDisplayColumn ? buildRelationPickerFkDisplayEditor(field, columnName, column) : '') +
                    '</div>';
            }).filter(Boolean).join('');
        }

        function getRelationPickerChecklistContainer(kind) {
            return document.getElementById('relation-picker-' + kind + '-columns');
        }

        window.filterRelationPickerColumnsModal = function(kind, query) {
            const container = getRelationPickerChecklistContainer(kind);
            if (!container) return;
            const needle = String(query || '').trim().toLowerCase();
            container.querySelectorAll('[data-relation-picker-column-item="' + kind + '"]').forEach(function(item) {
                const text = String(item.getAttribute('data-relation-picker-column-text') || '').toLowerCase();
                item.style.display = needle === '' || text.indexOf(needle) !== -1 ? 'flex' : 'none';
            });
        };

        window.setRelationPickerColumnsSelection = function(kind, checked) {
            const container = getRelationPickerChecklistContainer(kind);
            if (!container) return;
            container.querySelectorAll('[data-relation-picker-kind="' + kind + '"]').forEach(function(input) {
                input.checked = !!checked;
            });
        };

        window.toggleRelationPickerFkDisplayMode = function(columnName, mode) {
            const panel = document.querySelector('[data-picker-fk-relation-panel="' + cssEscape(columnName) + '"]');
            if (panel) {
                panel.style.display = mode === 'relation_display' ? '' : 'none';
            }
        };

        function collectRelationPickerFkDisplayColumns(modal, field, displayColumns) {
            const picker = ensureRelationPickerConfig(field);
            const mapping = {};
            displayColumns.forEach(function(columnName) {
                const columnMeta = findRelationPickerColumnMeta(field, columnName);
                if (!columnMeta || !columnMeta.is_foreign_key) {
                    return;
                }
                const referencedTable = String(columnMeta.referenced_table_name || columnMeta.referenced_table || '').trim();
                const referencedColumn = String(columnMeta.referenced_column_name || columnMeta.referenced_column || '').trim();
                const modeInput = modal.querySelector('[data-picker-fk-mode="' + cssEscape(columnName) + '"]');
                const displayInput = modal.querySelector('[data-picker-fk-display-column="' + cssEscape(columnName) + '"]');
                const mode = modeInput && modeInput.value === 'relation_display' ? 'relation_display' : 'raw_id';
                const displayColumn = displayInput ? String(displayInput.value || '').trim() : '';
                mapping[columnName] = {
                    mode: mode,
                    referenced_table: referencedTable,
                    referenced_column: referencedColumn,
                    display_column: mode === 'relation_display' ? displayColumn : ''
                };
            });
            picker.picker_fk_display_columns = mapping;
            return mapping;
        }

        function ensureRelationPickerReferencedColumnsLoaded(field) {
            const tableId = field.source_table_id || field.dropdown_table_id || '';
            const columns = dropdownSourceColumnsCache[String(tableId)] || [];
            const promises = columns.filter(column => !!column && !!column.is_foreign_key)
                .map(column => String(column.referenced_table_name || column.referenced_table || '').trim())
                .filter(Boolean)
                .map(function(tableName) {
                    const referencedTableId = findDropdownTableIdByName(tableName);
                    return referencedTableId ? ensureDropdownSourceColumnsLoaded(referencedTableId) : Promise.resolve([]);
                });
            return Promise.all(promises);
        }

        function buildAutoRelationPickerFkDisplayColumns(field, displayColumns) {
            const mapping = {};
            displayColumns.forEach(function(columnName) {
                const columnMeta = findRelationPickerColumnMeta(field, columnName);
                if (!columnMeta || !columnMeta.is_foreign_key) {
                    return;
                }
                const referencedTable = String(columnMeta.referenced_table_name || columnMeta.referenced_table || '').trim();
                const referencedColumn = String(columnMeta.referenced_column_name || columnMeta.referenced_column || '').trim();
                const referencedColumns = getReferencedColumnsSync(referencedTable);
                const detectedDisplayColumn = autoDetectRelationDisplayColumn(referencedColumns, referencedColumn);
                mapping[columnName] = {
                    mode: detectedDisplayColumn ? 'relation_display' : 'raw_id',
                    referenced_table: referencedTable,
                    referenced_column: referencedColumn,
                    display_column: detectedDisplayColumn || ''
                };
            });
            return mapping;
        }

        window.setRelationPickerMode = function(mode) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.picker_mode = ['dropdown', 'autocomplete', 'modal_picker', 'autocomplete_with_modal'].includes(mode) ? mode : 'dropdown';
            ensureRelationPickerConfig(field);
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.updateRelationPickerConfig = function(key, value) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            const config = ensureRelationPickerConfig(field);
            if (key === 'search_columns' || key === 'display_columns') {
                config[key] = String(value || '').split(',').map(item => item.trim()).filter(Boolean);
                config.search_target = 'custom';
            } else if (key === 'search_target') {
                const displayColumn = String(field.picker_config && field.picker_config.display_column ? field.picker_config.display_column : field.fk_display_column || field.label_column || field.dropdown_label_column || field.fk_referenced_column || field.value_column || 'id').trim();
                const valueColumn = String(field.picker_config && field.picker_config.value_column ? field.picker_config.value_column : field.fk_referenced_column || field.value_column || field.dropdown_value_column || 'id').trim();
                config.search_target = value || 'custom';
                if (config.search_target === 'value_only') {
                    config.search_columns = valueColumn ? [valueColumn] : [];
                } else if (config.search_target === 'display_only') {
                    config.search_columns = displayColumn ? [displayColumn] : [];
                } else if (config.search_target === 'value_and_display') {
                    config.search_columns = Array.from(new Set([valueColumn, displayColumn].filter(Boolean)));
                }
            } else if (key === 'page_size') {
                config[key] = Math.max(1, Math.min(50, parseInt(value || '10', 10) || 10));
            } else {
                config[key] = value;
            }
            updateData();
            renderPropsPanel(field);
        };

        window.generateRelationPickerConfig = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.picker_config = {};
            ensureRelationTableContext(field).then(function() {
                const picker = ensureRelationPickerConfig(field);
                return ensureRelationPickerReferencedColumnsLoaded(field).then(function() {
                    picker.picker_fk_display_columns = buildAutoRelationPickerFkDisplayColumns(field, normalizeRelationPickerColumnList(picker.display_columns));
                    renderPropsPanel(field);
                    updateData();
                });
            });
        };

        window.openRelationPickerColumnsModal = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const modal = document.getElementById('relation-picker-columns-modal');
            if (!modal) return;
            const field = formFields[selectedIndex];
            ensureRelationTableContext(field).then(function() {
                return ensureRelationPickerReferencedColumnsLoaded(field);
            }).then(function() {
                if (selectedIndex === null || formFields[selectedIndex] !== field) return;
                renderPropsPanel(field);
                const refreshedModal = document.getElementById('relation-picker-columns-modal');
                if (refreshedModal) {
                    refreshedModal.style.display = 'flex';
                }
            });
        };

        window.closeRelationPickerColumnsModal = function() {
            const modal = document.getElementById('relation-picker-columns-modal');
            if (!modal) return;
            modal.style.display = 'none';
        };

        window.applyRelationPickerColumnsModal = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            const picker = ensureRelationPickerConfig(field);
            const modal = document.getElementById('relation-picker-columns-modal');
            if (!modal) return;

            const searchColumns = Array.from(modal.querySelectorAll('[data-relation-picker-kind="search"]:checked')).map(function(input) {
                return String(input.value || '').trim();
            }).filter(Boolean);
            const displayColumns = Array.from(modal.querySelectorAll('[data-relation-picker-kind="display"]:checked')).map(function(input) {
                return String(input.value || '').trim();
            }).filter(Boolean);
            const pageSizeInput = modal.querySelector('#relation-picker-page-size');

            picker.search_columns = searchColumns;
            picker.display_columns = displayColumns;
            collectRelationPickerFkDisplayColumns(modal, field, displayColumns);
            picker.page_size = Math.max(1, Math.min(50, parseInt(pageSizeInput ? pageSizeInput.value : '10', 10) || 10));
            picker.search_target = 'custom';

            updateData();
            renderPropsPanel(field);
            modal.style.display = 'none';
        };

        function normalizeRelationMetadata(field) {
            if (!field || typeof field !== 'object') {
                return field;
            }

            const relationConfig = field.relation_config && typeof field.relation_config === 'object' ?
                field.relation_config :
                (field.relationConfig && typeof field.relationConfig === 'object' ? field.relationConfig : {});

            const fieldName = String(field.name || field.field_name || field.field_key || field.column_name || '').trim();
            if (fieldName) {
                field.name = fieldName;
                field.field_name = fieldName;
                field.field_key = fieldName;
                field.column_name = fieldName;
            }

            field.source_table_id = field.source_table_id || field.dropdown_table_id || '';
            field.source_table_name = field.source_table_name || field.fk_referenced_table || field.referenced_table_name || relationConfig.referenced_table || relationConfig.referenced_table_name || '';
            field.value_column = field.value_column || field.dropdown_value_column || field.fk_referenced_column || field.referenced_value_column || field.referenced_column_name || relationConfig.referenced_value_column || relationConfig.value_column || relationConfig.referenced_column || relationConfig.referenced_column_name || '';
            field.label_column = field.label_column || field.dropdown_label_column || field.fk_display_column || relationConfig.display_column || relationConfig.display_column_name || '';
            field.fk_referenced_table = field.fk_referenced_table || field.source_table_name || field.referenced_table_name || relationConfig.referenced_table || relationConfig.referenced_table_name || '';
            field.fk_referenced_column = field.fk_referenced_column || field.value_column || field.referenced_value_column || field.referenced_column_name || relationConfig.referenced_value_column || relationConfig.value_column || relationConfig.referenced_column || relationConfig.referenced_column_name || '';
            field.fk_display_column = field.fk_display_column || field.label_column || relationConfig.display_column || relationConfig.display_column_name || '';
            field.relation_table_name = field.relation_table_name || field.source_table_name || field.fk_referenced_table || '';
            field.relation_target_column = field.relation_target_column || relationConfig.local_column || relationConfig.source_column || field.name || '';
            field.relation_value_column = field.relation_value_column || field.value_column || field.fk_referenced_column || relationConfig.referenced_value_column || relationConfig.value_column || '';
            field.relation_display_column = field.relation_display_column || field.label_column || field.fk_display_column || relationConfig.display_column || relationConfig.display_column_name || '';

            if (field.is_foreign_key || String(field.fk_referenced_table || '').trim() !== '' || Array.isArray(field.fk_options)) {
                field.is_foreign_key = true;
            }
            if (!hasResolvedRelationConfig(field)) {
                field.is_foreign_key = false;
            }

            return syncRelationConfig(field);
        }

        function isRelationField(field) {
            return !!field && (
                hasResolvedRelationConfig(field) ||
                (!!field.is_foreign_key && String(field.fk_referenced_table || '').trim() !== '')
            );
        }

        function normalizeFieldState(field) {
            field = normalizeRelationMetadata(field);
            if (!field || typeof field !== 'object') {
                return field;
            }

            const columnType = normalizeColumnType(
                field.source_column_db_type ||
                field.source_column_column_type ||
                field.source_column_data_type ||
                field.column_type ||
                field.db_type ||
                field.data_type ||
                field.source_column_type ||
                field.base_type ||
                field.type ||
                field.inputType ||
                ''
            );
            const length = parseInt(field.source_column_length || field.length || field.size || field.precision || '', 10);

            if (isBooleanColumnType(columnType, length)) {
                field.type = 'boolean';
                field.inputType = 'boolean';
            }

            if (!field.type && field.inputType) {
                field.type = field.inputType;
            }
            if (!field.inputType && field.type) {
                field.inputType = field.type;
            }

            if (typeof field.type === 'string') {
                field.type = field.type.toLowerCase();
            }
            if (typeof field.inputType === 'string') {
                field.inputType = field.inputType.toLowerCase();
            }
            if (field.type === 'checkboxes') {
                field.inputType = 'checkboxes';
            }

            if (field.type === 'select' && isRelationField(field)) {
                field.options = Array.isArray(field.fk_options) && field.fk_options.length > 0 ?
                    field.fk_options :
                    (Array.isArray(field.options) ? field.options : []);
            }

            if (['select', 'radio', 'checkboxes'].includes(field.type)) {
                if (!field.option_source) {
                    field.option_source = getOptionSourceMode(field);
                }
                if (field.option_source === 'preset' && field.option_preset === 'calendar_months') {
                    field = applyOptionPreset(field, 'calendar_months');
                } else if (field.option_source === 'manual') {
                    field.dropdown_source = field.dropdown_source === 'table' ? 'static_options' : (field.dropdown_source || 'static_options');
                }
            }

            if (field.type === 'gps_camera' || Array.isArray(field.gps_camera_bindings)) {
                field = normalizeGpsCameraBindingState(field);
            }

            return field;
        }

        function getFieldConfiguredOptions(field) {
            field = normalizeFieldState(field);
            if (!field) return [];

            if (isRelationField(field)) {
                if (Array.isArray(field.fk_options) && field.fk_options.length > 0) {
                    return field.fk_options;
                }
                if (Array.isArray(field.options) && field.options.length > 0) {
                    return field.options;
                }
                return [];
            }

            if (field.dropdown_source === 'table' && Array.isArray(field.options) && field.options.length > 0) {
                return field.options;
            }

            if (Array.isArray(field.options) && field.options.length > 0) {
                return field.options;
            }

            return [];
        }

        function normalizeChoiceOptions(field) {
            field = normalizeFieldState(field);
            if (isRelationField(field)) {
                return getFieldConfiguredOptions(field);
            }
            if (!Array.isArray(field.options) || field.options.length === 0) {
                field.options = [{
                        value: 'opt1',
                        label: 'Opsi 1'
                    },
                    {
                        value: 'opt2',
                        label: 'Opsi 2'
                    }
                ];
            }
            field.options = field.options.map((opt, index) => ({
                value: opt.value ?? ('opt' + (index + 1)),
                label: opt.label ?? ('Opsi ' + (index + 1))
            }));
            return field.options;
        }

        function buildMonthOptions() {
            return [{
                    value: '01',
                    label: 'Januari'
                },
                {
                    value: '02',
                    label: 'Februari'
                },
                {
                    value: '03',
                    label: 'Maret'
                },
                {
                    value: '04',
                    label: 'April'
                },
                {
                    value: '05',
                    label: 'Mei'
                },
                {
                    value: '06',
                    label: 'Juni'
                },
                {
                    value: '07',
                    label: 'Juli'
                },
                {
                    value: '08',
                    label: 'Agustus'
                },
                {
                    value: '09',
                    label: 'September'
                },
                {
                    value: '10',
                    label: 'Oktober'
                },
                {
                    value: '11',
                    label: 'November'
                },
                {
                    value: '12',
                    label: 'Desember'
                }
            ];
        }

        function applyOptionPreset(field, preset) {
            if (!field || preset !== 'calendar_months') {
                return field;
            }
            field.option_source = 'preset';
            field.option_preset = 'calendar_months';
            field.dropdown_source = 'preset';
            field.options = buildMonthOptions();
            return field;
        }

        function getCurrentBuilderTableId() {
            const hiddenInput = document.getElementById('table-id-input');
            const selector = document.getElementById('table-selector');
            const rawValue = (hiddenInput && hiddenInput.value) || (selector && selector.value) || '';
            const parsed = parseInt(rawValue, 10);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
        }

        function isGenericDropdownName(value) {
            const normalized = String(value || '').trim().toLowerCase();
            if (!normalized) {
                return true;
            }
            return /^(field|select|dropdown|pilihan)(_\d+)?$/.test(normalized);
        }

        function isGenericDropdownLabel(value) {
            const normalized = String(value || '').trim().toLowerCase();
            if (!normalized) {
                return true;
            }
            return ['dropdown', 'select', 'pilihan', 'opsi', 'dropdown manual'].includes(normalized);
        }

        function getCurrentTableForeignKeyColumnsSync() {
            const tableId = getCurrentBuilderTableId();
            if (!tableId) {
                return [];
            }
            const columns = dropdownSourceColumnsCache[String(tableId)] || [];
            return columns.filter(column => !!column && !!column.id && !!column.is_foreign_key);
        }

        function buildCurrentTableForeignKeyOptions(selectedColumnId) {
            const columns = getCurrentTableForeignKeyColumnsSync();
            let html = '<option value="">Pilih kolom foreign key...</option>';
            columns.forEach(column => {
                const label = column.label || column.name;
                html += '<option value="' + escapeAttr(column.id) + '"' + boolAttr('selected', String(selectedColumnId || '') === String(column.id)) + '>' + escapeHtml(label + ' (' + column.name + ')') + '</option>';
            });
            return html;
        }

        function getCurrentTableForeignKeyPanelState() {
            const currentTableId = getCurrentBuilderTableId();
            const cacheKey = String(currentTableId || '');
            const isLoaded = !!(currentTableId && dropdownSourceColumnsCache[cacheKey]);
            const columns = currentTableId ? getCurrentTableForeignKeyColumnsSync() : [];
            return {
                tableId: currentTableId,
                isLoaded: isLoaded,
                columns: columns,
            };
        }

        function buildDropdownColumnOptions(field, selectedColumn) {
            const tableId = field.source_table_id || field.dropdown_table_id || (field.fk_referenced_table ? findTableIdByName(field.fk_referenced_table) : '') || '';
            // Load columns if not in cache
            if (tableId && !dropdownSourceColumnsCache[String(tableId)]) {
                ensureDropdownSourceColumnsLoaded(tableId).then(columns => {
                    // Refresh the dropdown after columns are loaded
                    const selectElement = document.querySelector('[name="fk_display_column"]');
                    if (selectElement) {
                        selectElement.innerHTML = buildDropdownColumnOptions(field, selectedColumn);
                    }
                });
            }
            const columns = dropdownSourceColumnsCache[String(tableId)] || [];
            let html = '<option value="">Pilih kolom...</option>';
            columns.forEach(column => {
                html += '<option value="' + escapeAttr(column.name) + '"' + boolAttr('selected', String(selectedColumn || '') === String(column.name)) + '>' + escapeHtml((column.label || column.name) + ' (' + column.name + ')') + '</option>';
            });
            return html;
        }

        function buildGpsCameraTableOptions(selectedTableId) {
            let html = '<option value="">Pilih table...</option>';
            dropdownSourceTables.forEach(table => {
                const tableId = String(table.id || '');
                const tableLabel = String(table.label || table.name || 'Table');
                html += '<option value="' + escapeAttr(tableId) + '"' + boolAttr('selected', String(selectedTableId || '') === tableId) + '>' + escapeHtml(tableLabel + ' (' + (table.name || tableId) + ')') + '</option>';
            });
            return html;
        }

        function buildGpsCameraColumnOptions(field, selectedColumnId) {
            const tableId = String(field.target_table_id || '');
            if (!tableId) {
                return '<option value="">Pilih table terlebih dulu</option>';
            }
            const columns = dropdownSourceColumnsCache[tableId] || [];
            if (columns.length === 0) {
                return '<option value="">Memuat kolom...</option>';
            }
            let html = '<option value="">Pilih column...</option>';
            columns.forEach(column => {
                const columnId = String(column.id || '');
                const columnLabel = String(column.label || column.name || 'Column');
                html += '<option value="' + escapeAttr(columnId) + '"' + boolAttr('selected', String(selectedColumnId || '') === columnId) + '>' + escapeHtml(columnLabel + ' (' + (column.name || columnId) + ')') + '</option>';
            });
            return html;
        }

        function buildGpsCameraDataKeyOptions(selectedDataKey) {
            const dataKeys = [{
                    value: 'latitude',
                    label: 'Bind ke Latitude'
                },
                {
                    value: 'longitude',
                    label: 'Bind ke Longitude'
                },
                {
                    value: 'gps_accuracy',
                    label: 'Bind ke Akurasi GPS'
                },
                {
                    value: 'location_text',
                    label: 'Bind ke Lokasi Wilayah'
                },
                {
                    value: 'location_address',
                    label: 'Bind ke Alamat Lengkap'
                },
                {
                    value: 'captured_date',
                    label: 'Bind ke Tanggal Jepret'
                },
                {
                    value: 'captured_time',
                    label: 'Bind ke Jam Jepret'
                },
                {
                    value: 'captured_at',
                    label: 'Bind ke Waktu Jepret'
                },
                {
                    value: 'photo_path',
                    label: 'Bind ke Path Foto'
                },
                {
                    value: 'photo_url',
                    label: 'Bind ke URL Foto'
                },
                {
                    value: 'photo_name',
                    label: 'Bind ke Nama File'
                },
                {
                    value: 'photo_mime',
                    label: 'Bind ke Tipe MIME'
                },
                {
                    value: 'photo_size',
                    label: 'Bind ke Ukuran File (bytes)'
                },
            ];
            let html = '<option value="">Pilih data...</option>';
            dataKeys.forEach(key => {
                html += '<option value="' + escapeAttr(key.value) + '"' + boolAttr('selected', String(selectedDataKey || '') === key.value) + '>' + escapeHtml(key.label) + '</option>';
            });
            return html;
        }

        function normalizeGpsCameraBindingState(field) {
            if (!field || typeof field !== 'object') {
                return field;
            }

            let bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings.slice() : [];
            if (bindings.length === 0 && (field.target_table_id || field.target_column_id || field.target_table_name || field.target_column_name)) {
                bindings = [{
                    data_key: field.gps_camera_data_key || 'photo_path',
                    target_table_id: field.target_table_id || '',
                    target_table_name: field.target_table_name || '',
                    target_column_id: field.target_column_id || '',
                    target_column_name: field.target_column_name || ''
                }];
            }

            bindings = bindings.map(binding => ({
                data_key: String(binding && (binding.data_key || binding.source_key || 'photo_path') || 'photo_path').trim() || 'photo_path',
                target_table_id: String(binding && binding.target_table_id ? binding.target_table_id : ''),
                target_table_name: String(binding && binding.target_table_name ? binding.target_table_name : ''),
                target_column_id: String(binding && binding.target_column_id ? binding.target_column_id : ''),
                target_column_name: String(binding && binding.target_column_name ? binding.target_column_name : '')
            }));

            if (bindings.length === 0) {
                bindings = [{
                    data_key: 'photo_path',
                    target_table_id: '',
                    target_table_name: '',
                    target_column_id: '',
                    target_column_name: ''
                }];
            }

            field.gps_camera_bindings = bindings;
            field.target_mappings = bindings.slice();
            const firstBinding = bindings[0] || {};
            field.gps_camera_data_key = firstBinding.data_key || field.gps_camera_data_key || 'photo_path';
            field.target_table_id = firstBinding.target_table_id || field.target_table_id || '';
            field.target_table_name = firstBinding.target_table_name || field.target_table_name || '';
            field.target_column_id = firstBinding.target_column_id || field.target_column_id || '';
            field.target_column_name = firstBinding.target_column_name || field.target_column_name || '';
            return field;
        }

        function buildGpsCameraColumnOptionsForTable(tableId, selectedColumnId) {
            const normalizedTableId = String(tableId || '');
            if (!normalizedTableId) {
                return '<option value="">Pilih table terlebih dulu</option>';
            }
            const columns = dropdownSourceColumnsCache[normalizedTableId] || [];
            if (columns.length === 0) {
                return '<option value="">Memuat kolom...</option>';
            }
            let html = '<option value="">Pilih column...</option>';
            columns.forEach(column => {
                const columnId = String(column.id || '');
                const columnLabel = String(column.label || column.name || 'Column');
                html += '<option value="' + escapeAttr(columnId) + '"' + boolAttr('selected', String(selectedColumnId || '') === columnId) + '>' + escapeHtml(columnLabel + ' (' + (column.name || columnId) + ')') + '</option>';
            });
            return html;
        }

        function buildGpsCameraBindingRows(field) {
            field = normalizeGpsCameraBindingState(field);
            const bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings : [];
            if (bindings.length === 0) {
                return '<div style="font-size:12px;color:#64748b;">Belum ada binding.</div>';
            }

            return bindings.map((binding, index) => {
                const rowTableId = String(binding.target_table_id || '');
                const rowColumnId = String(binding.target_column_id || '');
                return '<div class="gps-camera-binding-row" data-gps-camera-binding-row="' + index + '" style="display:grid;gap:10px;padding:12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;margin-bottom:10px;">' +
                    '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">' +
                    '<strong style="font-size:12px;color:#0f172a;">Binding ' + (index + 1) + '</strong>' +
                    '<button type="button" class="prop-option-remove" onclick="removeGpsCameraBindingRow(' + index + ')" title="Hapus binding" style="width:28px;height:28px;">&times;</button>' +
                    '</div>' +
                    '<div class="prop-group"><label class="prop-label">Bind ke Data</label><select class="prop-select" onchange="setGpsCameraBindingDataKey(' + index + ', this.value)">' + buildGpsCameraDataKeyOptions(binding.data_key || 'photo_path') + '</select></div>' +
                    '<div class="prop-group"><label class="prop-label">Target Table</label><select class="prop-select" onchange="setGpsCameraBindingTable(' + index + ', this.value)">' + buildGpsCameraTableOptions(rowTableId) + '</select></div>' +
                    '<div class="prop-group"><label class="prop-label">Target Column</label><select class="prop-select" onchange="setGpsCameraBindingColumn(' + index + ', this.value)">' + buildGpsCameraColumnOptionsForTable(rowTableId, rowColumnId) + '</select></div>' +
                    '</div>';
            }).join('');
        }

        function findDropdownTableById(tableId) {
            const normalized = String(tableId || '').trim();
            if (!normalized) {
                return null;
            }
            return dropdownSourceTables.find(table => String(table.id || '') === normalized) || null;
        }

        function findDropdownTableByName(tableName) {
            const normalized = String(tableName || '').trim().toLowerCase();
            if (!normalized) {
                return null;
            }
            return dropdownSourceTables.find(table => String(table.name || '').trim().toLowerCase() === normalized) || null;
        }

        function resolveForeignKeyReferencedTableId(field) {
            const directTableId = String(field.source_table_id || field.dropdown_table_id || '').trim();
            if (directTableId) {
                return directTableId;
            }

            const referencedTableName = String(field.fk_referenced_table || field.source_table_name || field.referenced_table_name || '').trim();
            if (!referencedTableName) {
                return '';
            }

            const table = findDropdownTableByName(referencedTableName);
            return table ? String(table.id || '') : '';
        }

        function getPreferredDisplayColumn(columns, tableName, valueColumn, preferredColumn) {
            const normalizedValue = String(valueColumn || '').trim().toLowerCase();
            const normalizedPreferred = String(preferredColumn || '').trim().toLowerCase();
            const normalizedTable = String(tableName || '').trim().toLowerCase();
            const priorities = ['name', 'nama', 'title', 'label'];
            if (normalizedTable) {
                priorities.push('nama_' + normalizedTable);
            }
            priorities.push('kode');

            if (normalizedPreferred && columns.some(column => String(column.name || '').toLowerCase() === normalizedPreferred)) {
                return preferredColumn;
            }

            const preferredMatch = priorities.find(candidate => candidate !== normalizedValue && columns.some(column => String(column.name || '').toLowerCase() === candidate));
            if (preferredMatch) {
                return preferredMatch;
            }

            const readableColumn = columns.find(column => {
                const columnName = String(column.name || '').trim().toLowerCase();
                if (!columnName || columnName === normalizedValue || column.is_primary) {
                    return false;
                }
                if (['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'].includes(columnName)) {
                    return false;
                }
                if (columnName.endsWith('_id')) {
                    return false;
                }
                return ['string', 'integer', 'double'].includes(String(column.php_type || '').toLowerCase());
            });
            if (readableColumn) {
                return readableColumn.name;
            }

            const fallback = columns.find(column => String(column.name || '').trim() !== '' && String(column.name || '').trim().toLowerCase() !== normalizedValue);
            return fallback ? fallback.name : (valueColumn || '');
        }

        function ensureRelationTableContext(field) {
            field = normalizeFieldState(field);
            return ensureDropdownSourceTablesLoaded().then(function() {
                if (!field.source_table_id && field.fk_referenced_table) {
                    const table = findDropdownTableByName(field.fk_referenced_table);
                    if (table) {
                        field.source_table_id = parseInt(table.id, 10);
                        field.dropdown_table_id = field.source_table_id;
                        field.source_table_name = table.name;
                    }
                }

                const tableId = field.source_table_id || field.dropdown_table_id || '';
                if (!tableId) {
                    return [];
                }

                return ensureDropdownSourceColumnsLoaded(tableId).then(function(columns) {
                    const hasValueColumn = columns.some(column => String(column.name || '') === String(field.value_column || field.fk_referenced_column || ''));
                    const hasDisplayColumn = columns.some(column => String(column.name || '') === String(field.label_column || field.fk_display_column || ''));
                    const primaryColumn = columns.find(column => column.is_primary) || columns.find(column => String(column.name || '').toLowerCase() === 'id') || columns[0] || null;
                    if ((!field.value_column || !hasValueColumn) && primaryColumn) {
                        field.value_column = primaryColumn.name;
                    }
                    if (!field.fk_referenced_column && field.value_column) {
                        field.fk_referenced_column = field.value_column;
                    }
                    const displayColumn = getPreferredDisplayColumn(columns, field.fk_referenced_table || field.source_table_name, field.value_column || field.fk_referenced_column, field.fk_display_column || field.label_column);
                    if ((!field.label_column || !hasDisplayColumn) && displayColumn) {
                        field.label_column = displayColumn;
                    }
                    if ((!field.fk_display_column || !hasDisplayColumn) && displayColumn) {
                        field.fk_display_column = displayColumn;
                    }
                    syncRelationConfig(field);
                    return columns;
                });
            });
        }

        function findTableIdByName(tableName) {
            if (!tableName) return '';
            if (dropdownSourceTables.length === 0 && window.dropdownSourceTables && window.dropdownSourceTables.length > 0) {
                dropdownSourceTables = window.dropdownSourceTables;
            }
            const found = dropdownSourceTables.find(t => String(t.name || '').toLowerCase() === String(tableName).toLowerCase());
            return found ? String(found.id) : '';
        }

        function ensureDropdownSourceTablesLoaded() {
            if (dropdownSourceTables.length > 0) return Promise.resolve(dropdownSourceTables);
            return fetch('/tables/get-tables?t=' + Date.now(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    dropdownSourceTables = Array.isArray(data.tables) ? data.tables : [];
                    return dropdownSourceTables;
                })
                .catch(() => []);
        }

        function ensureDropdownSourceColumnsLoaded(tableId) {
            tableId = String(tableId || '');
            if (!tableId) return Promise.resolve([]);
            if (dropdownSourceColumnsCache[tableId]) return Promise.resolve(dropdownSourceColumnsCache[tableId]);
            return fetch('/tables/columns/' + encodeURIComponent(tableId) + '?t=' + Date.now(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    dropdownSourceColumnsCache[tableId] = Array.isArray(data.columns) ? data.columns : [];
                    return dropdownSourceColumnsCache[tableId];
                })
                .catch(() => []);
        }

        function ensureGpsCameraMetadataLoaded(field) {
            field = normalizeGpsCameraBindingState(field);
            if (!field || typeof field !== 'object') {
                return Promise.resolve([]);
            }
            if (field.__gps_camera_metadata_loaded) {
                return Promise.resolve([]);
            }
            if (field.__gps_camera_metadata_loading) {
                return field.__gps_camera_metadata_loading;
            }

            const currentToken = ++gpsCameraMetadataLoadToken;
            field.__gps_camera_metadata_loading = ensureDropdownSourceTablesLoaded().then(function() {
                if (currentToken !== gpsCameraMetadataLoadToken) {
                    return [];
                }

                const bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings : [];
                const tableIds = Array.from(new Set(bindings.map(function(binding) {
                    return String(binding && binding.target_table_id ? binding.target_table_id : '');
                }).filter(Boolean)));
                if (tableIds.length === 0 && field.target_table_id) {
                    tableIds.push(String(field.target_table_id));
                }

                return Promise.all(tableIds.map(function(tableId) {
                    return ensureDropdownSourceColumnsLoaded(tableId);
                }));
            }).then(function() {
                if (currentToken !== gpsCameraMetadataLoadToken) {
                    return;
                }
                field.__gps_camera_metadata_loaded = true;
                field.__gps_camera_metadata_loading = null;
                if (selectedIndex !== null && formFields[selectedIndex] === field) {
                    // PERBAIKAN BUG 1: Guard agar async GPS metadata tidak memicu render loop
                    if (_renderPropsPanelGuard) {
                        return;
                    }
                    _renderPropsPanelGuard = true;
                    throttledRenderPropsPanel(formFields[selectedIndex]);
                    _renderPropsPanelGuard = false;
                }
            }).catch(function() {
                field.__gps_camera_metadata_loading = null;
                return [];
            });

            return field.__gps_camera_metadata_loading;
        }

        function ensureCurrentTableForeignKeyColumnsLoaded() {
            const tableId = getCurrentBuilderTableId();
            if (!tableId) {
                return Promise.resolve([]);
            }
            return ensureDropdownSourceColumnsLoaded(tableId).then(function(columns) {
                return (Array.isArray(columns) ? columns : []).filter(column => {
                    return !!column && !!column.id && !!column.is_foreign_key;
                });
            });
        }

        function resetDropdownTableSourceField(field) {
            delete field.source_column_id;
            delete field.local_column;
            delete field.source_table_id;
            delete field.dropdown_table_id;
            delete field.source_table_name;
            delete field.value_column;
            delete field.label_column;
            delete field.dropdown_value_column;
            delete field.dropdown_label_column;
            delete field.fk_options;
            delete field.fk_referenced_table;
            delete field.fk_referenced_column;
            delete field.fk_display_column;
            delete field.relation_table_name;
            delete field.relation_target_column;
            delete field.relation_value_column;
            delete field.relation_display_column;
            delete field.relation_config;
            delete field.relationConfig;
            delete field.dynamic_options_loaded;
            field.is_foreign_key = false;
        }

        function applyForeignKeyColumnToDropdownField(field, fkColumn, fkData) {
            const relationLabel = fkColumn.label || fkColumn.name;
            if (isGenericDropdownName(field.name) || !field.name) {
                field.name = fkColumn.name;
                field.field_name = fkColumn.name;
                field.field_key = fkColumn.name;
            }
            if (isGenericDropdownLabel(field.label)) {
                field.label = relationLabel;
            }

            field.option_source = 'table';
            field.dropdown_source = 'table';
            field.is_foreign_key = true;
            field.source_column_id = fkColumn.id;
            field.local_column = fkColumn.name;
            field.column_name = fkColumn.name;
            field.field_name = fkColumn.name;
            field.field_key = fkColumn.name;
            field.name = fkColumn.name;
            field.source_table_id = fkData.referenced_table_id || field.source_table_id || '';
            field.dropdown_table_id = field.source_table_id || '';
            field.source_table_name = fkData.referenced_table || fkColumn.referenced_table_name || '';
            field.fk_referenced_table = fkData.referenced_table || fkColumn.referenced_table_name || '';
            field.fk_referenced_column = fkData.referenced_value_column || fkColumn.referenced_column_name || '';
            field.value_column = field.fk_referenced_column;
            field.dropdown_value_column = field.fk_referenced_column;
            field.fk_display_column = fkData.display_column || field.fk_display_column || '';
            field.label_column = field.fk_display_column || '';
            field.dropdown_label_column = field.label_column || '';
            field.relation_table_name = field.fk_referenced_table || '';
            field.relation_target_column = field.local_column || field.name || '';
            field.relation_value_column = field.fk_referenced_column || '';
            field.relation_display_column = field.fk_display_column || '';
            field.options = Array.isArray(fkData.options) ? fkData.options : [];
            field.fk_options = Array.isArray(fkData.options) ? fkData.options : [];
            field.dynamic_options_loaded = true;
            syncRelationConfig(field);
        }

        function loadDropdownSourceFromForeignKey(field, columnId) {
            const targetColumnId = parseInt(columnId, 10);
            const fkColumn = getCurrentTableForeignKeyColumnsSync().find(column => String(column.id) === String(targetColumnId));
            if (!fkColumn) {
                return Promise.resolve(false);
            }

            return fetch('/tables/foreign-key-options/' + encodeURIComponent(targetColumnId) + '?t=' + Date.now(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Gagal memuat opsi foreign key.');
                    }
                    applyForeignKeyColumnToDropdownField(field, fkColumn, data);
                    return true;
                });
        }

        function refreshDropdownOptionsFromTable(field) {
            field = normalizeFieldState(field);
            return ensureRelationTableContext(field).then(function() {
                const tableId = field.source_table_id || field.dropdown_table_id || '';
                const valueColumn = field.value_column || field.dropdown_value_column || field.fk_referenced_column || '';
                const labelColumn = field.label_column || field.dropdown_label_column || field.fk_display_column || '';
                if (!tableId || !valueColumn || !labelColumn) return [];

                const url = '/tables/dropdown-options/' + encodeURIComponent(tableId) +
                    '?value_column=' + encodeURIComponent(valueColumn) +
                    '&label_column=' + encodeURIComponent(labelColumn) +
                    '&t=' + Date.now();
                return fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && Array.isArray(data.options)) {
                            field.options = data.options;
                            field.fk_options = data.options;
                            field.fk_referenced_table = field.fk_referenced_table || field.source_table_name || data.table_name || '';
                            field.source_table_name = field.source_table_name || data.table_name || '';
                            field.value_column = data.value_column || valueColumn;
                            field.dropdown_value_column = field.value_column;
                            field.fk_referenced_column = field.value_column;
                            field.label_column = data.label_column || labelColumn;
                            field.dropdown_label_column = field.label_column;
                            field.fk_display_column = field.label_column;
                            field.dynamic_options_loaded = true;
                            syncRelationConfig(field);
                            return data.options;
                        }
                        field.options = [];
                        field.fk_options = [];
                        syncRelationConfig(field);
                        return [];
                    })
                    .catch(() => []);
            });
        }

        function attr(name, value) {
            if (value === undefined || value === null || value === '') return '';
            return ' ' + name + '="' + escapeAttr(value) + '"';
        }

        function boolAttr(name, enabled) {
            return enabled ? ' ' + name : '';
        }

        function buildSelectOptionsMarkup(field) {
            const options = getFieldConfiguredOptions(field);
            if (!options.length) {
                return '';
            }

            return options.map(function(opt) {
                const value = escapeAttr(opt.value ?? '');
                const label = escapeHtml(opt.label ?? opt.value ?? '');
                if (!value) return '';
                return '<option value="' + value + '">' + label + '</option>';
            }).join('');
        }

        function isRelationSelectField(field) {
            return !!field && String(field.type || '').toLowerCase() === 'select' && (
                hasResolvedRelationConfig(field) ||
                (!!field.is_foreign_key && String(field.fk_referenced_table || '').trim() !== '')
            );
        }

        function looksLikeDummySelectCode(code) {
            const normalized = String(code || '');
            return normalized.includes('Opsi 1') && normalized.includes('Opsi 2') && normalized.includes('<select');
        }

        // Render Preview Input
        function renderPreview(field) {
            if (!field) return '';
            field = normalizeFieldState(field);

            // Check for custom code
            if (field.customHtml || field.customCss || field.customJs) {
                const id = 'preview-' + field.id;
                const srcDoc = '<!DOCTYPE html><html><head><style>' + (field.customCss || '') + '</style></head><body>' + (field.customHtml || '') + '<script>' + (field.customJs || '') + '<\/script></body></html>';
                return `<div class="field-preview" style="padding:0;background:transparent;border:none;">` +
                    `<iframe id="${id}" srcdoc="${srcDoc.replace(/"/g, '&quot;')}" style="width:100%;min-height:80px;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;" sandbox="allow-scripts"></iframe>` +
                    `</div>`;
            }

            const componentType = String(field.type || field.field_type || '').toLowerCase();
            const type = componentType === 'checkboxes' ?
                'checkboxes' :
                String(field.inputType || field.type || 'text').toLowerCase();
            const placeholders = {
                text: 'Input text...',
                email: 'email@example.com',
                password: '******',
                number: '0',
                tel: '+62 xxx',
                url: 'https://',
                textarea: 'Enter text...',
                select: 'Pilih opsi...',
                date: 'Pilih tanggal',
                time: 'Pilih waktu',
                datetime: 'Pilih tanggal & waktu',
            };
            const inputType = {
                text: 'text',
                email: 'email',
                password: 'password',
                number: 'number',
                tel: 'tel',
                url: 'url',
                textarea: 'textarea',
                select: 'select',
                radio: 'radio',
                checkbox: 'checkbox',
                checkboxes: 'checkboxes',
                date: 'date',
                time: 'time',
                datetime: 'datetime-local',
                file: 'file',
                hidden: 'hidden'
            };

            const commonAttrs = attr('placeholder', field.placeholder || placeholders[type] || '') +
                attr('value', field.default_value || '') +
                attr('name', field.name || '') +
                attr('minlength', field.min_length) +
                attr('maxlength', field.max_length) +
                attr('pattern', field.pattern) +
                boolAttr('required', field.required) +
                boolAttr('readonly', field.readonly) +
                boolAttr('disabled', true);

            if (type === 'select') {
                const options = getFieldConfiguredOptions(field);
                let optionsHtml = '<option value="">-- Pilih --</option>';
                if (options.length > 0) {
                    options.forEach(opt => {
                        const value = String(opt.value ?? '');
                        if (!value) return;
                        optionsHtml += '<option value="' + escapeAttr(value) + '"' + boolAttr('selected', String(field.default_value || '') === value) + '>' + escapeHtml(opt.label ?? value) + '</option>';
                    });
                }
                return '<div class="field-preview"><select' + attr('name', field.name || '') + boolAttr('required', field.required) + boolAttr('disabled', true) + '>' + optionsHtml + '</select></div>';
            }

            if (type === 'radio' || type === 'checkboxes') {
                const options = normalizeChoiceOptions(field);
                const optionHtml = options.map((opt, index) => {
                    const inputType = type === 'radio' ? 'radio' : 'checkbox';
                    return '<label style="display:flex;align-items:center;gap:8px;margin:6px 0;color:#475569;">' +
                        '<input type="' + inputType + '" name="' + escapeAttr(field.name || field.id || 'option_group') + '"' + boolAttr('checked', String(field.default_value || '') === String(opt.value) || (index === 0 && type === 'radio' && !field.default_value)) + boolAttr('disabled', true) + '>' +
                        '<span>' + escapeHtml(opt.label) + '</span>' +
                        '</label>';
                }).join('');
                return '<div class="field-preview">' + optionHtml + '</div>';
            }

            if (type === 'textarea') {
                return '<div class="field-preview"><textarea rows="' + escapeAttr(field.rows || 4) + '"' + commonAttrs + '>' + escapeHtml(field.default_value || '') + '</textarea></div>';
            }

            if (type === 'checkbox') {
                return '<div class="field-preview"><label style="display:flex;align-items:center;gap:8px;color:#475569;"><input type="checkbox"' + attr('name', field.name || '') + boolAttr('checked', field.default_checked) + boolAttr('required', field.required) + boolAttr('disabled', true) + '><span>' + escapeHtml(field.labelText || field.label || 'Checkbox') + '</span></label></div>';
            }

            if (type === 'boolean') {
                const checked = String(field.default_value || '') === '1' || String(field.default_value || '').toLowerCase() === 'true';
                return '<div class="field-preview"><label class="form-check form-switch" style="display:flex;align-items:center;gap:8px;color:#475569;">' +
                    '<input type="checkbox" class="form-check-input" ' + boolAttr('checked', checked) + boolAttr('disabled', true) + '>' +
                    '<span>' + escapeHtml(field.label || field.labelText || 'Aktif / Nonaktif') + '</span>' +
                    '</label></div>';
            }

            if (type === 'gps_camera') {
                return '<div class="field-preview" style="display:flex;flex-direction:column;gap:10px;">' +
                    '<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">' +
                    '<button type="button" style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:none;border-radius:10px;background:#4f46e5;color:#fff;font-weight:700;">Ambil Foto</button>' +
                    '<span style="font-size:12px;color:#64748b;">' + escapeHtml(field.target_table_name || field.target_column_name || 'GPS Camera') + '</span>' +
                    '</div>' +
                    '<div style="padding:12px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;color:#64748b;font-size:12px;">Foto + metadata lokasi dan waktu</div>' +
                    '</div>';
            }

            if (type === 'file') {
                return '<div class="field-preview"><input type="file"' + attr('name', field.name || '') + attr('accept', field.accept) + boolAttr('multiple', field.multiple) + boolAttr('required', field.required) + boolAttr('disabled', true) + '></div>';
            }

            if (type === 'hidden') {
                return '<div class="field-preview" style="background:#f8fafc;color:#64748b;">Hidden value: ' + escapeHtml(field.default_value || '(empty)') + '</div>';
            }

            const numericAttrs = type === 'number' ? attr('min', field.min) + attr('max', field.max) + attr('step', field.step) : '';
            const dateAttrs = ['date', 'time', 'datetime'].includes(type) ? attr('min', field.min) + attr('max', field.max) : '';

            return '<div class="field-preview"><input type="' + escapeAttr(inputType[type] || 'text') + '"' + commonAttrs + numericAttrs + dateAttrs + '></div>';
        }

        // Initialize Sortable
        function initSortable() {
            if (!container) return;
            if (window.formSortableInstance) {
                window.formSortableInstance.destroy();
            }
            window.formSortableInstance = new Sortable(container, {
                animation: 150,
                handle: '.field-drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    const item = formFields.splice(evt.oldIndex, 1)[0];
                    formFields.splice(evt.newIndex, 0, item);
                    selectedIndex = evt.newIndex;
                    renderFields();
                    updateData();
                }
            });
        }

        // Select Field
        window.selectField = function(index) {
            if (index === null || index === undefined) return;
            selectedIndex = index;
            // PERBAIKAN BUG 1: Debounce render canvas agar tidak freeze saat klik cepat
            scheduleRenderFields();
            if (formFields[index]) {
                throttledRenderPropsPanel(formFields[index]);
            }
        };

        // Render Properties Panel (Design Tab)
        function renderPropsPanel(field) {
            // ===== BUG 1 FIX: Guard untuk mencegah infinite loop =====
            // Jika lock masih aktif, abort render untuk mencegah stack overflow
            if (_renderPropsPanelLock) {
                return;
            }
            // Jika guard aktif, abaikan (mencegah rekursi)
            if (_renderPropsPanelGuard) {
                return;
            }
            
            field = normalizeFieldState(field);
            const panel = document.getElementById('properties-panel');
            if (!panel) return;

            if (!field) {
                panel.innerHTML = '<div class="no-selection"><span class="material-symbols-outlined">touch_app</span><p style="font-size:14px">Pilih field untuk edit</p></div>';
                return;
            }

            const icons = {
                text: 'text_fields',
                email: 'email',
                password: 'lock',
                number: 'pin',
                tel: 'phone',
                url: 'link',
                textarea: 'notes',
                select: 'arrow_drop_down_circle',
                radio: 'radio_button_checked',
                checkbox: 'check_box',
                checkboxes: 'checklist',
                boolean: 'toggle_on',
                date: 'calendar_today',
                time: 'schedule',
                datetime: 'event',
                gps_camera: 'photo_camera',
                file: 'upload_file',
                hidden: 'visibility_off'
            };

            let html = '<div class="prop-header"><span class="material-symbols-outlined">' + (icons[field.type] || 'text_fields') + '</span><span class="block-type-badge">' + field.type + '</span></div>';

            html += '<div class="prop-section"><div class="prop-section-title">Label & Name</div>';
            html += '<div class="prop-group"><label class="prop-label">Label</label><input type="text" class="prop-input" value="' + escapeAttr(field.label || '') + '" data-prop="label" onchange="updateFieldProp(\'label\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Name (Database)</label><input type="text" class="prop-input" value="' + escapeAttr(field.name || '') + '" data-prop="name" onchange="updateFieldProp(\'name\', this.value)"></div>';
            html += '<div class="prop-group"><label class="prop-label">Placeholder</label><input type="text" class="prop-input" value="' + escapeAttr(field.placeholder || '') + '" data-prop="placeholder" onchange="updateFieldProp(\'placeholder\', this.value)"></div></div>';

            html += '<div class="prop-section"><div class="prop-section-title">Konfigurasi</div>';
            html += '<div class="prop-group"><label class="prop-label">Tipe Input</label><select class="prop-select" data-prop="type" onchange="updateFieldProp(\'type\', this.value)">';
            const types = ['text', 'email', 'password', 'number', 'tel', 'url', 'textarea', 'select', 'radio', 'checkbox', 'checkboxes', 'boolean', 'date', 'time', 'datetime', 'file', 'gps_camera', 'hidden'];
            const labels = ['Text Input', 'Email', 'Password', 'Number', 'Phone/Tel', 'URL', 'Textarea', 'Dropdown Select', 'Radio Button', 'Checkbox', 'Checkboxes', 'Switch Toggle', 'Date', 'Time', 'Date Time', 'File Upload', 'GPS Camera', 'Hidden'];
            types.forEach((t, i) => {
                html += '<option value="' + t + '" ' + (field.type === t ? 'selected' : '') + '>' + labels[i] + '</option>';
            });
            html += '</select></div>';
            html += '<div class="prop-group"><label class="prop-label">Default Value</label><input type="text" class="prop-input" value="' + escapeAttr(field.default_value || '') + '" data-prop="default_value" onchange="updateFieldProp(\'default_value\', this.value)"></div></div>';

            if (['text', 'email', 'password', 'tel', 'url'].includes(field.type)) {
                html += '<div class="prop-section"><div class="prop-section-title">Input Rules</div>';
                html += '<div class="prop-group"><label class="prop-label">Min Length</label><input type="number" class="prop-input" min="0" value="' + escapeAttr(field.min_length || '') + '" onchange="updateFieldProp(\'min_length\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-label">Max Length</label><input type="number" class="prop-input" min="1" value="' + escapeAttr(field.max_length || '') + '" onchange="updateFieldProp(\'max_length\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-label">Pattern Regex</label><input type="text" class="prop-input" value="' + escapeAttr(field.pattern || '') + '" placeholder="e.g. [A-Za-z0-9]+" onchange="updateFieldProp(\'pattern\', this.value)"></div>';
                html += '</div>';
            }

            if (field.type === 'number') {
                html += '<div class="prop-section"><div class="prop-section-title">Number Rules</div>';
                html += '<div class="prop-group"><label class="prop-label">Min</label><input type="number" class="prop-input" value="' + escapeAttr(field.min || '') + '" onchange="updateFieldProp(\'min\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-label">Max</label><input type="number" class="prop-input" value="' + escapeAttr(field.max || '') + '" onchange="updateFieldProp(\'max\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-label">Step</label><input type="text" class="prop-input" value="' + escapeAttr(field.step || '') + '" placeholder="1, 0.01, any" onchange="updateFieldProp(\'step\', this.value)"></div>';
                html += '</div>';
            }

            if (field.type === 'textarea') {
                html += '<div class="prop-section"><div class="prop-section-title">Textarea</div>';
                html += '<div class="prop-group"><label class="prop-label">Rows</label><input type="number" class="prop-input" min="2" max="20" value="' + escapeAttr(field.rows || 4) + '" onchange="updateFieldProp(\'rows\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-label">Max Length</label><input type="number" class="prop-input" min="1" value="' + escapeAttr(field.max_length || '') + '" onchange="updateFieldProp(\'max_length\', this.value)"></div>';
                html += '</div>';
            }

            if (['date', 'time', 'datetime'].includes(field.type)) {
                html += '<div class="prop-section"><div class="prop-section-title">Range</div>';
                html += '<div class="prop-group"><label class="prop-label">Min</label><input type="' + (field.type === 'datetime' ? 'datetime-local' : field.type) + '" class="prop-input" value="' + escapeAttr(field.min || '') + '" onchange="updateFieldProp(\'min\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-label">Max</label><input type="' + (field.type === 'datetime' ? 'datetime-local' : field.type) + '" class="prop-input" value="' + escapeAttr(field.max || '') + '" onchange="updateFieldProp(\'max\', this.value)"></div>';
                html += '</div>';
            }

            if (field.type === 'file') {
                html += '<div class="prop-section"><div class="prop-section-title">File Upload</div>';
                html += '<div class="prop-group"><label class="prop-label">Accept</label><input type="text" class="prop-input" value="' + escapeAttr(field.accept || '') + '" placeholder=".jpg,.png,application/pdf" onchange="updateFieldProp(\'accept\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.multiple ? 'checked' : '') + ' onchange="updateFieldProp(\'multiple\', this.checked)">Allow Multiple Files</label></div>';
                html += '</div>';
            }

            if (field.type === 'gps_camera') {
                html += '<div class="prop-section"><div class="prop-section-title">Binding Multi Kolom</div>';
                html += '<small style="display:block;color:#64748b;line-height:1.5;margin-bottom:10px;">Satu capture dapat dipetakan ke beberapa kolom target dari metadata table yang tersedia.</small>';
                html += '<div id="gps-camera-bindings-panel">' + buildGpsCameraBindingRows(field) + '</div>';
                html += '<div class="prop-group"><button type="button" class="prop-option-add" onclick="addGpsCameraBindingRow()">+ Tambah Binding</button></div>';
                html += '</div>';
                html += '<div class="prop-section"><div class="prop-section-title">Capture</div>';
                html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.auto_capture_gps !== false ? 'checked' : '') + ' onchange="updateFieldProp(\'auto_capture_gps\', this.checked)">Auto Capture GPS</label></div>';
                html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.auto_capture_timestamp !== false ? 'checked' : '') + ' onchange="updateFieldProp(\'auto_capture_timestamp\', this.checked)">Auto Capture Timestamp</label></div>';
                html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.preview_image !== false ? 'checked' : '') + ' onchange="updateFieldProp(\'preview_image\', this.checked)">Preview Image</label></div>';
                html += '</div>';
            }

            if (field.type === 'checkbox') {
                html += '<div class="prop-section"><div class="prop-section-title">Checkbox</div>';
                html += '<div class="prop-group"><label class="prop-label">Checkbox Text</label><input type="text" class="prop-input" value="' + escapeAttr(field.labelText || '') + '" placeholder="Centang ini" onchange="updateFieldProp(\'labelText\', this.value)"></div>';
                html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.default_checked ? 'checked' : '') + ' onchange="updateFieldProp(\'default_checked\', this.checked)">Checked by Default</label></div>';
                html += '</div>';
            }

            if (field.type === 'checkboxes') {
                html += '<div class="prop-section"><div class="prop-section-title">Checkboxes</div>';
                html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.saveAsMultipleRows ? 'checked' : '') + ' onchange="updateFieldProp(\'saveAsMultipleRows\', this.checked)">Save each selected value as separate row</label></div>';
                html += '</div>';
            }

            if (['select', 'radio', 'checkboxes'].includes(field.type)) {
                const sourceMode = getOptionSourceMode(field);
                html += '<div class="prop-section"><div class="prop-section-title">Option Source</div>';
                html += '<div class="prop-group"><label class="prop-label">Sumber Opsi</label><select class="prop-select" onchange="setOptionSourceMode(this.value)">';
                html += '<option value="manual"' + boolAttr('selected', sourceMode === 'manual') + '>Manual</option>';
                html += '<option value="preset"' + boolAttr('selected', sourceMode === 'preset') + '>Preset</option>';
                html += '<option value="table"' + boolAttr('selected', sourceMode === 'table') + '>Dari Table</option>';
                html += '</select></div>';
                if (sourceMode === 'preset') {
                    html += '<div class="prop-group"><label class="prop-label">Preset</label><select class="prop-select" onchange="setOptionPreset(this.value)">';
                    html += '<option value="calendar_months"' + boolAttr('selected', field.option_preset === 'calendar_months') + '>Calendar Months / Bulan Kalender</option>';
                    html += '</select></div>';
                    html += '<small style="display:block;color:#64748b;line-height:1.5;">Value yang disimpan ke database: 01 sampai 12. Label hanya untuk tampilan.</small>';
                }
                if (sourceMode === 'table') {
                    const fkPanelState = getCurrentTableForeignKeyPanelState();
                    if (!fkPanelState.tableId) {
                        html += '<small style="display:block;color:#b45309;line-height:1.5;">Pilih target table form terlebih dulu. Source dari table hanya tersedia jika form sudah memakai table aktif.</small>';
                    } else if (!fkPanelState.isLoaded) {
                        html += '<small style="display:block;color:#64748b;line-height:1.5;">Memuat kolom foreign key dari table aktif...</small>';
                    } else if (fkPanelState.columns.length === 0) {
                        html += '<small style="display:block;color:#b45309;line-height:1.5;">Table aktif tidak memiliki kolom foreign key. Ambil dari table tidak tersedia untuk dropdown ini.</small>';
                    } else {
                        html += '<div class="prop-group"><label class="prop-label">Kolom FK dari Table Aktif</label><select class="prop-select" onchange="setDropdownSourceForeignKey(this.value)">' + buildCurrentTableForeignKeyOptions(field.source_column_id) + '</select></div>';
                        html += '<small style="display:block;color:#64748b;line-height:1.5;">Pilih salah satu kolom FK dari table aktif. Setelah dipilih, dropdown akan memakai data relasi itu.</small>';
                    }
                }
                html += '</div>';
            }

            if (['select', 'radio', 'checkboxes'].includes(field.type) && !field.is_foreign_key && getOptionSourceMode(field) === 'manual') {
                const options = normalizeChoiceOptions(field);
                html += '<div class="prop-section"><div class="prop-section-title">Options</div>';
                html += '<div class="prop-group">';
                html += '<div class="prop-options-list">';
                options.forEach((opt, index) => {
                    html += '<div class="prop-option-row">';
                    html += '<input type="text" class="prop-input" value="' + escapeAttr(opt.label) + '" placeholder="Label" onchange="updateFieldOption(' + index + ', \'label\', this.value)">';
                    html += '<input type="text" class="prop-input" value="' + escapeAttr(opt.value) + '" placeholder="Value" onchange="updateFieldOption(' + index + ', \'value\', this.value)">';
                    html += '<button type="button" class="prop-option-remove" onclick="removeFieldOption(' + index + ')" title="Remove option"><span class="material-symbols-outlined" style="font-size:16px;">close</span></button>';
                    html += '</div>';
                });
                html += '</div>';
                html += '<button type="button" class="prop-option-add" onclick="addFieldOption()">+ Add option</button>';
                html += '</div></div>';
            }

            html += '<div class="prop-section"><div class="prop-section-title">Validasi</div>';
            html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.required ? 'checked' : '') + ' data-prop="required" onchange="updateFieldProp(\'required\', this.checked)">Wajib Diisi (Required)</label></div>';
            html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.readonly ? 'checked' : '') + ' data-prop="readonly" onchange="updateFieldProp(\'readonly\', this.checked)">Read-only</label></div>';
            html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.disabled ? 'checked' : '') + ' data-prop="disabled" onchange="updateFieldProp(\'disabled\', this.checked)">Disabled</label></div></div>';

            if (field.type === 'select' && field.is_foreign_key) {
                html += '<div class="prop-section"><div class="prop-section-title">Foreign Key</div>';
                html += '<div class="prop-group"><label class="prop-label">Local Column / Kolom Form</label><input type="text" class="prop-input" value="' + escapeAttr(field.local_column || field.name || '-') + '" readonly style="background:#f1f5f9;"></div>';
                html += '<div class="prop-group"><label class="prop-label">Referenced Table</label><input type="text" class="prop-input" value="' + escapeAttr(field.fk_referenced_table || '-') + '" readonly style="background:#f1f5f9;"></div>';
                html += '<div class="prop-group"><label class="prop-label">Referenced Value Column</label><input type="text" class="prop-input" value="' + escapeAttr(field.fk_referenced_column || field.value_column || field.dropdown_value_column || '-') + '" readonly style="background:#f1f5f9;"></div>';
                html += '<div class="prop-group"><label class="prop-label">Display Column</label><select class="prop-select" name="fk_display_column" onchange="setForeignKeyColumn(\'display\', this.value)">' + buildDropdownColumnOptions(field, field.fk_display_column || field.label_column || field.dropdown_label_column) + '</select></div>';
                html += '<div class="prop-group"><button type="button" class="prop-option-add" onclick="reloadForeignKeyOptions()">Refresh dropdown relasi</button></div>';
                const pickerConfig = ensureRelationPickerConfig(field);
                html += '<div class="prop-section-title" style="padding:10px 0 0;">Relation Picker</div>';
                html += '<div class="prop-group"><label class="prop-label">Picker Mode</label><select class="prop-select" onchange="setRelationPickerMode(this.value)">';
                ['dropdown', 'autocomplete', 'modal_picker', 'autocomplete_with_modal'].forEach(mode => {
                    html += '<option value="' + mode + '"' + boolAttr('selected', (field.picker_mode || 'dropdown') === mode) + '>' + mode.replace(/_/g, ' ') + '</option>';
                });
                html += '</select></div>';
                html += '<div class="prop-group"><label class="prop-label">Search Target</label><select class="prop-select" onchange="updateRelationPickerConfig(\'search_target\', this.value)">';
                html += '<option value="display_only"' + boolAttr('selected', (pickerConfig.search_target || 'display_only') === 'display_only') + '>Display column / label</option>';
                html += '<option value="value_only"' + boolAttr('selected', (pickerConfig.search_target || '') === 'value_only') + '>Value column / ID</option>';
                html += '<option value="value_and_display"' + boolAttr('selected', (pickerConfig.search_target || '') === 'value_and_display') + '>Value + display</option>';
                html += '<option value="custom"' + boolAttr('selected', (pickerConfig.search_target || '') === 'custom') + '>Custom columns</option>';
                html += '</select></div>';
                html += '<div class="prop-group"><label class="prop-label">Quick Search Preset</label><div style="display:flex;flex-wrap:wrap;gap:8px;">';
                html += '<button type="button" class="prop-option-add" onclick="updateRelationPickerConfig(\'search_target\', \'display_only\')">Display</button>';
                html += '<button type="button" class="prop-option-add" onclick="updateRelationPickerConfig(\'search_target\', \'value_only\')">ID / Value</button>';
                html += '<button type="button" class="prop-option-add" onclick="updateRelationPickerConfig(\'search_target\', \'value_and_display\')">Keduanya</button>';
                html += '<button type="button" class="prop-option-add" onclick="updateRelationPickerConfig(\'search_target\', \'custom\')">Custom</button>';
                html += '</div></div>';
                html += '<div class="prop-group" style="' + ((pickerConfig.search_target || 'display_only') === 'custom' ? '' : 'display:none;') + '"><label class="prop-label">Search Columns</label><input type="text" class="prop-input" value="' + escapeAttr((pickerConfig.search_columns || []).join(', ')) + '" onchange="updateRelationPickerConfig(\'search_columns\', this.value)" placeholder="nama, kode, email"></div>';
                html += '<div class="prop-group"><label class="prop-label">Display Columns</label><input type="text" class="prop-input" value="' + escapeAttr((pickerConfig.display_columns || []).join(', ')) + '" onchange="updateRelationPickerConfig(\'display_columns\', this.value)" placeholder="id, nama, kode"></div>';
                html += '<div class="prop-group"><label class="prop-label">Page Size</label><input type="number" min="1" max="50" class="prop-input" value="' + escapeAttr(pickerConfig.page_size || 10) + '" onchange="updateRelationPickerConfig(\'page_size\', this.value)"></div>';
                html += '<div class="prop-group"><button type="button" class="prop-option-add" onclick="openRelationPickerColumnsModal()">Atur kolom modal</button></div>';
                html += '<div class="prop-group"><button type="button" class="prop-option-add" onclick="generateRelationPickerConfig()">Generate picker config</button></div>';
                html += '<div id="relation-picker-columns-modal" class="relation-picker-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,0.55);align-items:center;justify-content:center;padding:24px;">';
                html += '<div style="width:min(920px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(15,23,42,0.24);">';
                html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e2e8f0;">';
                html += '<div>';
                html += '<div style="font-size:18px;font-weight:700;color:#0f172a;">Relation Picker Columns & Modal FK Display</div>';
                html += '<div style="font-size:12px;color:#64748b;">Pilih kolom modal dan atur kolom FK agar tampil sebagai raw ID atau display dari relasi.</div>';
                html += '</div>';
                html += '<button type="button" class="prop-option-remove" onclick="closeRelationPickerColumnsModal()" style="width:36px;height:36px;">&times;</button>';
                html += '</div>';
                html += '<div style="padding:22px;display:grid;grid-template-columns:1fr 1fr;gap:20px;">';
                html += '<div>';
                html += '<div class="prop-section-title" style="margin-bottom:10px;">Search Columns</div>';
                html += '<div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">';
                html += '<input type="text" class="prop-input" placeholder="Cari kolom..." oninput="filterRelationPickerColumnsModal(\'search\', this.value)" style="flex:1;min-width:0;">';
                html += '<button type="button" class="prop-option-add" onclick="setRelationPickerColumnsSelection(\'search\', true)">Select all</button>';
                html += '<button type="button" class="prop-option-add" onclick="setRelationPickerColumnsSelection(\'search\', false)">Clear</button>';
                html += '</div>';
                html += '<div id="relation-picker-search-columns" style="display:grid;gap:10px;">' + buildRelationPickerColumnChecklist(field, 'search') + '</div>';
                html += '</div>';
                html += '<div>';
                html += '<div class="prop-section-title" style="margin-bottom:10px;">Display Columns / Modal FK Display</div>';
                html += '<div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">';
                html += '<input type="text" class="prop-input" placeholder="Cari kolom..." oninput="filterRelationPickerColumnsModal(\'display\', this.value)" style="flex:1;min-width:0;">';
                html += '<button type="button" class="prop-option-add" onclick="setRelationPickerColumnsSelection(\'display\', true)">Select all</button>';
                html += '<button type="button" class="prop-option-add" onclick="setRelationPickerColumnsSelection(\'display\', false)">Clear</button>';
                html += '</div>';
                html += '<div id="relation-picker-display-columns" style="display:grid;gap:10px;">' + buildRelationPickerColumnChecklist(field, 'display') + '</div>';
                html += '</div>';
                html += '</div>';
                html += '<div style="padding:0 22px 22px;">';
                html += '<div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;">';
                html += '<div style="flex:1;">';
                html += '<label class="prop-label">Page Size</label>';
                html += '<input type="number" min="1" max="50" class="prop-input" id="relation-picker-page-size" value="' + escapeAttr(pickerConfig.page_size || 10) + '">';
                html += '</div>';
                html += '<div style="align-self:flex-end;display:flex;gap:10px;">';
                html += '<button type="button" class="prop-option-add" onclick="closeRelationPickerColumnsModal()">Cancel</button>';
                html += '<button type="button" class="prop-option-add" onclick="applyRelationPickerColumnsModal()">Apply Columns</button>';
                html += '</div>';
                html += '</div>';
                html += '<small style="display:block;color:#64748b;line-height:1.5;">Kolom yang dipilih di sini akan dipakai oleh modal picker untuk menampilkan baris data relasi.</small>';
                html += '</div>';
                html += '</div>';
                if (field.fk_options && field.fk_options.length > 0) {
                    html += '<div class="prop-group"><label class="prop-label">Options (' + field.fk_options.length + ' items)</label><div style="max-height:120px;overflow-y:auto;font-size:11px;color:#64748b;">';
                    field.fk_options.forEach(opt => {
                        html += '<div style="padding:2px 0;">' + opt.label + ' (value: ' + opt.value + ')</div>';
                    });
                    html += '</div></div>';
                } else {
                    html += '<div class="prop-group"><label class="prop-label">Options</label><span class="fk-options-loading">Loading options...</span></div>';
                }
                html += '</div>';
            }

            html += '<div class="prop-section"><div class="prop-section-title">System</div>';
            html += '<div class="prop-group"><label class="prop-checkbox"><input type="checkbox" ' + (field.excluded ? 'checked' : '') + ' data-prop="excluded" onchange="updateFieldProp(\'excluded\', this.checked)">Exclude from Form (Hide)</label></div>';
            if (field.is_primary) {
                html += '<div class="prop-group"><span class="field-badge-auto">Primary Key</span></div>';
            }
            if (field.is_auto_increment) {
                html += '<div class="prop-group"><span class="field-badge-auto">Auto Increment</span></div>';
            }
            html += '</div>';

            propsPanel.innerHTML = html;

            if (field.type === 'gps_camera') {
                ensureGpsCameraMetadataLoaded(field);
            }

            // ===== BUG 1 FIX: Cegah infinite loop pada renderPropsPanel =====
            // Guard: jika sedang dalam proses renderPropsPanel yang sama, jangan panggil lagi
            if (!_renderPropsPanelGuard && field.type === 'select' && field.is_foreign_key) {
                const tableId = resolveForeignKeyReferencedTableId(field);
                ensureDropdownSourceTablesLoaded().then(function() {
                    if (selectedIndex === null || formFields[selectedIndex] !== field) return;
                    if (tableId && !dropdownSourceColumnsCache[String(tableId)]) {
                        ensureDropdownSourceColumnsLoaded(tableId).then(function() {
                            if (selectedIndex !== null && formFields[selectedIndex] === field) {
                                // BUG 1 FIX: Gunakan flag guard untuk mencegah stack overflow
                                if (_renderPropsPanelGuard) return;
                                _renderPropsPanelGuard = true;
                                renderPropsPanel(field);
                                _renderPropsPanelGuard = false;
                            }
                        });
                    } else if (tableId) {
                        // BUG 1 FIX: Jika data sudah di cache, TIDAK perlu re-render ulang propsPanel
                        // karena data sudah tersedia, cukup update dropdown display column via DOM
                        // tanpa memanggil renderPropsPanel lagi (yang menyebabkan infinite loop)
                        const displaySelect = panel.querySelector('[name="fk_display_column"]');
                        if (displaySelect && !displaySelect.hasChildNodes()) {
                            displaySelect.innerHTML = buildDropdownColumnOptions(field, field.fk_display_column || field.label_column || field.dropdown_label_column);
                        }
                    }
                });
            }

        }

        // Update Field Property
        // ===== BUG 1 FIX: Debounce updateFieldProp untuk mencegah render berlebihan =====
        const debouncedUpdateFieldProp = debounce(function(propName, value) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            formFields[selectedIndex][propName] = value;
            normalizeFieldState(formFields[selectedIndex]);
            const rerenderPanelProps = new Set([
                'type', 'readonly', 'disabled', 'required', 'excluded',
                'default_checked', 'saveAsMultipleRows', 'option_source',
                'option_preset', 'source_column_id', 'source_table_id',
                'label_column', 'value_column', 'picker_mode',
                'target_table_id', 'target_column_id', 'auto_capture_gps',
                'auto_capture_timestamp', 'preview_image'
            ]);
            if (propName === 'type') {
                formFields[selectedIndex].inputType = getInputType(value);
                if (['select', 'radio', 'checkboxes'].includes(value)) {
                    if (!formFields[selectedIndex].option_source) {
                        formFields[selectedIndex].option_source = 'manual';
                        formFields[selectedIndex].dropdown_source = 'static_options';
                    }
                    normalizeChoiceOptions(formFields[selectedIndex]);
                } else if (value === 'gps_camera') {
                    formFields[selectedIndex].auto_capture_gps = formFields[selectedIndex].auto_capture_gps !== false;
                    formFields[selectedIndex].auto_capture_timestamp = formFields[selectedIndex].auto_capture_timestamp !== false;
                    formFields[selectedIndex].preview_image = formFields[selectedIndex].preview_image !== false;
                }
                if (value !== 'select') {
                    if (formFields[selectedIndex].option_source !== 'table') {
                        formFields[selectedIndex].dropdown_source = 'static_options';
                    }
                }
            }
            renderFieldsImmediate();
            if (rerenderPanelProps.has(propName)) {
                throttledRenderPropsPanel(formFields[selectedIndex]);
            }
            updateData();
        }, 100); // 100ms debounce delay
        
        window.updateFieldProp = function(propName, value) {
            // Langsung update value, baru debounce render
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            formFields[selectedIndex][propName] = value;
            updateData(); // Langsung update data agar tidak hilang
            
            // Debounce render UI
            debouncedUpdateFieldProp(propName, value);
        };

        window.setForeignKeyColumn = function(kind, value) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            if (kind === 'display') {
                field.label_column = value;
                field.dropdown_label_column = value;
                field.fk_display_column = value;
            }
            syncRelationConfig(field);
            refreshDropdownOptionsFromTable(field).then(function() {
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
            });
        };

        window.reloadForeignKeyOptions = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            refreshDropdownOptionsFromTable(field).then(function() {
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
            });
        };

        window.setDropdownSourceMode = function(mode) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.option_source = mode === 'table' ? 'table' : 'manual';
            field.dropdown_source = mode === 'table' ? 'table' : 'static_options';
            field.option_preset = '';
            if (mode !== 'table') {
                resetDropdownTableSourceField(field);
                normalizeChoiceOptions(field);
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
                return;
            }

            ensureCurrentTableForeignKeyColumnsLoaded().then(function(columns) {
                if (!field.source_column_id) {
                    field.is_foreign_key = false;
                }
                renderPropsPanel(field);
                updateData();
            });
        };

        window.setOptionSourceMode = function(mode) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.option_source = mode;

            if (mode === 'preset') {
                applyOptionPreset(field, field.option_preset || 'calendar_months');
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
                return;
            }

            if (mode === 'table') {
                field.dropdown_source = 'table';
                field.option_preset = '';
                field.options = [];
                ensureCurrentTableForeignKeyColumnsLoaded().then(function() {
                    if (!field.source_column_id) {
                        field.is_foreign_key = false;
                    }
                    normalizeFieldState(field);
                    renderFields();
                    renderPropsPanel(field);
                    updateData();
                });
                return;
            }

            field.option_source = 'manual';
            field.option_preset = '';
            field.dropdown_source = 'static_options';
            resetDropdownTableSourceField(field);
            normalizeChoiceOptions(field);
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.setOptionPreset = function(preset) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            applyOptionPreset(field, preset);
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.setDropdownSourceForeignKey = function(columnId) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            if (!columnId) {
                field.option_source = 'table';
                field.dropdown_source = 'table';
                field.source_column_id = '';
                resetDropdownTableSourceField(field);
                field.dropdown_source = 'table';
                field.options = [];
                renderFields();
                renderPropsPanel(field);
                updateData();
                return;
            }

            loadDropdownSourceFromForeignKey(field, columnId)
                .then(function() {
                    normalizeFieldState(field);
                    renderFields();
                    renderPropsPanel(field);
                    updateData();
                })
                .catch(function() {
                    alert('Gagal memuat konfigurasi foreign key dari kolom yang dipilih.');
                });
        };

        window.setDropdownSourceTable = function(tableId) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            const table = dropdownSourceTables.find(item => String(item.id) === String(tableId));
            field.option_source = 'table';
            field.dropdown_source = 'table';
            field.source_table_id = tableId ? parseInt(tableId, 10) : '';
            field.source_table_name = table ? table.name : '';
            field.value_column = '';
            field.label_column = '';
            field.options = [];
            field.fk_options = [];
            field.fk_referenced_table = table ? table.name : '';
            field.fk_referenced_column = '';
            field.fk_display_column = '';
            field.relation_table_name = table ? table.name : '';
            field.relation_target_column = field.name || '';
            field.relation_value_column = '';
            field.relation_display_column = '';
            field.is_foreign_key = true;
            ensureDropdownSourceColumnsLoaded(tableId).then(function(columns) {
                const primary = columns.find(column => column.is_primary) || columns.find(column => String(column.name || '').toLowerCase() === 'id') || columns[0] || null;
                const labelColumn = getPreferredDisplayColumn(columns, field.fk_referenced_table || field.source_table_name, primary ? primary.name : '', '');
                if (primary) field.value_column = primary.name;
                if (labelColumn) field.label_column = labelColumn;
                field.fk_referenced_column = field.value_column || '';
                field.fk_display_column = field.label_column || '';
                field.relation_value_column = field.value_column || '';
                field.relation_display_column = field.label_column || '';
                return refreshDropdownOptionsFromTable(field);
            }).then(function() {
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
            });
        };

        window.setGpsCameraTargetTable = function(tableId) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            setGpsCameraBindingTable(0, tableId);
        };

        window.setGpsCameraTargetColumn = function(columnId) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            setGpsCameraBindingColumn(0, columnId);
        };

        window.addGpsCameraBindingRow = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.gps_camera_bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings : [];
            field.gps_camera_bindings.push({
                data_key: 'photo_path',
                target_table_id: field.target_table_id || '',
                target_table_name: field.target_table_name || '',
                target_column_id: '',
                target_column_name: ''
            });
            normalizeGpsCameraBindingState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.removeGpsCameraBindingRow = function(index) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            const bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings.slice() : [];
            if (bindings.length <= 1) {
                bindings[0] = {
                    data_key: 'photo_path',
                    target_table_id: '',
                    target_table_name: '',
                    target_column_id: '',
                    target_column_name: ''
                };
            } else {
                bindings.splice(index, 1);
            }
            field.gps_camera_bindings = bindings;
            normalizeGpsCameraBindingState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.setGpsCameraBindingDataKey = function(index, dataKey) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.gps_camera_bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings : [];
            if (!field.gps_camera_bindings[index]) return;
            field.gps_camera_bindings[index].data_key = String(dataKey || '').trim() || 'photo_path';
            normalizeGpsCameraBindingState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.setGpsCameraBindingTable = function(index, tableId) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            const table = findDropdownTableById(tableId);
            field.gps_camera_bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings : [];
            if (!field.gps_camera_bindings[index]) return;
            field.gps_camera_bindings[index].target_table_id = tableId ? String(tableId) : '';
            field.gps_camera_bindings[index].target_table_name = table ? String(table.name || table.label || '') : '';
            field.gps_camera_bindings[index].target_column_id = '';
            field.gps_camera_bindings[index].target_column_name = '';
            normalizeGpsCameraBindingState(field);
            if (tableId) {
                ensureDropdownSourceColumnsLoaded(tableId).then(function() {
                    if (selectedIndex !== null && formFields[selectedIndex] === field) {
                        renderFields();
                        renderPropsPanel(field);
                        updateData();
                    }
                });
                return;
            }
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.setGpsCameraBindingColumn = function(index, columnId) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.gps_camera_bindings = Array.isArray(field.gps_camera_bindings) ? field.gps_camera_bindings : [];
            const binding = field.gps_camera_bindings[index];
            if (!binding) return;
            const tableId = String(binding.target_table_id || '');
            const columns = dropdownSourceColumnsCache[tableId] || [];
            const column = columns.find(item => String(item.id || '') === String(columnId)) || null;
            binding.target_column_id = columnId ? String(columnId) : '';
            binding.target_column_name = column ? String(column.name || column.label || '') : '';
            normalizeGpsCameraBindingState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.setDropdownSourceColumn = function(propName, value) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            field.option_source = 'table';
            field.dropdown_source = 'table';
            field[propName] = value;
            refreshDropdownOptionsFromTable(field).then(function() {
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
            });
        };

        window.reloadDropdownSourceOptions = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            refreshDropdownOptionsFromTable(field).then(function() {
                normalizeFieldState(field);
                renderFields();
                renderPropsPanel(field);
                updateData();
            });
        };

        window.updateFieldOption = function(index, key, value) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            normalizeChoiceOptions(field);
            if (!field.options[index]) return;
            field.options[index][key] = value;
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.addFieldOption = function() {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            const options = normalizeChoiceOptions(field);
            const nextIndex = options.length + 1;
            options.push({
                value: 'opt' + nextIndex,
                label: 'Opsi ' + nextIndex
            });
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        window.removeFieldOption = function(index) {
            if (selectedIndex === null || !formFields[selectedIndex]) return;
            const field = formFields[selectedIndex];
            normalizeChoiceOptions(field);
            if (field.options.length <= 1) return;
            field.options.splice(index, 1);
            normalizeFieldState(field);
            renderFields();
            renderPropsPanel(field);
            updateData();
        };

        // Reset Field Code
        window.resetFieldCode = function() {
            if (!formFields[selectedIndex]) return;
            if (confirm('Reset custom code field ini ke base template?')) {
                delete formFields[selectedIndex].customHtml;
                delete formFields[selectedIndex].customCss;
                delete formFields[selectedIndex].customJs;
                renderFields();
                updateData();
            }
        };

        window.resetPageSourceCode = function() {
            if (!confirm('Reset Page Source ke template generated terbaru?')) return;
            fullFormCustomHtml = generatePageSource();
            fullFormCustomCss = '';
            fullFormCustomJs = '';
            if (monacoEditor) {
                isSyncingCode = true;
                monacoEditor.setValue(fullFormCustomHtml);
                isSyncingCode = false;
            }
            updateCustomCodeInputs();
            renderCanvasMode();
        };

        // Tab Switching Logic
        document.querySelectorAll('.prop-tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                var tab = this.dataset.tab;
                document.querySelectorAll('.prop-tab-btn').forEach(function(b) {
                    b.classList.toggle('active', b === btn);
                    b.style.borderBottomColor = (b.classList.contains('active')) ? '#6366f1' : 'transparent';
                });
                document.querySelectorAll('.prop-tab-content').forEach(function(c) {
                    c.style.display = (c.id === 'properties-' + tab + '-tab') ? 'flex' : 'none';
                });
                if (tab === 'code') {
                    initMonacoEditor();
                }
            });
        });

        // Monaco Editor Logic
        var monacoEditor = null;
        var currentCodeLang = 'html';
        var isSyncingCode = false;
        var activeCodeScope = 'component';
        var fullFormCustomHtml = '';
        var fullFormCustomCss = '';
        var fullFormCustomJs = '';

        window.initMonacoEditor = function() {
            if (monacoEditor) {
                if (activeCodeScope === 'page') {
                    loadFormCustomCodeFromState();
                } else {
                    loadFieldCodeFromState();
                }
                renderCanvasMode();
                return;
            }

            require.config({
                paths: {
                    vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs'
                }
            });
            require(['vs/editor/editor.main'], function() {
                monacoEditor = monaco.editor.create(document.getElementById('monaco-editor-container'), {
                    value: '',
                    language: 'html',
                    theme: 'vs-dark',
                    automaticLayout: true,
                    minimap: {
                        enabled: false
                    },
                    fontSize: 12,
                    lineNumbers: 'on',
                    scrollBeyondLastLine: false,
                    padding: {
                        top: 10
                    }
                });

                monacoEditor.onDidChangeModelContent(function() {
                    if (isSyncingCode) return;
                    if (activeCodeScope === 'page') {
                        updateFormCustomCodeInState();
                    } else {
                        updateFieldCodeInState();
                    }
                });

                if (activeCodeScope === 'page') {
                    loadFormCustomCodeFromState();
                } else {
                    loadFieldCodeFromState();
                }
                setCodeScope(activeCodeScope);
            });
        };

        window.loadFieldCodeFromState = function() {
            if (!monacoEditor || !formFields[selectedIndex]) return;

            var field = formFields[selectedIndex];
            isSyncingCode = true;

            var codeKey = 'custom' + currentCodeLang.charAt(0).toUpperCase() + currentCodeLang.slice(1);
            var code = field[codeKey] || '';
            if (isRelationSelectField(field) && looksLikeDummySelectCode(code)) {
                code = '';
            }

            // Load base code template if no custom code exists
            if (!code) {
                code = getFieldBaseCode(field.type, currentCodeLang);
            }

            monacoEditor.setValue(code || '');
            isSyncingCode = false;
        };

        // Base code templates per field type
        function getFieldBaseCode(fieldType, lang) {
            var baseCodeTemplates = {
                text: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="text" name="{name}" class="field-input" placeholder="{placeholder}" />\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                    js: ''
                },
                email: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="email" name="{name}" class="field-input" placeholder="{placeholder}" />\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                    js: ''
                },
                password: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="password" name="{name}" class="field-input" />\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                    js: ''
                },
                number: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="number" name="{name}" class="field-input" placeholder="{placeholder}" />\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                    js: ''
                },
                textarea: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <textarea name="{name}" class="field-textarea" rows="4" placeholder="{placeholder}"></textarea>\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-textarea {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  resize: vertical;\n}',
                    js: ''
                },
                select: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <select name="{name}" class="field-select">\n    <option value="">Pilih...</option>\n    {options}\n  </select>\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-select {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n  background: white;\n}',
                    js: ''
                },
                checkbox: {
                    html: '<div class="field-wrapper">\n  <label class="field-checkbox">\n    <input type="checkbox" name="{name}" />\n    <span>{label}</span>\n  </label>\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-checkbox {\n  display: flex;\n  align-items: center;\n  gap: 8px;\n  cursor: pointer;\n}\n.field-checkbox input {\n  width: 18px;\n  height: 18px;\n}',
                    js: ''
                },
                date: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <input type="date" name="{name}" class="field-input" />\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.field-input {\n  width: 100%;\n  padding: 10px 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 8px;\n}',
                    js: ''
                },
                gps_camera: {
                    html: '<div class="field-wrapper gps-camera-field" data-gps-camera-component="1">\n  <label class="field-label">{label}</label>\n  <input type="hidden" name="{name}" value="" data-gps-camera-payload />\n  <input type="file" name="__gps_camera_file_{name}" accept="image/*" capture="environment" class="gps-camera-file" hidden />\n  <div class="gps-camera-box">\n    <button type="button" class="gps-camera-trigger">Ambil Foto</button>\n    <button type="button" class="gps-camera-clear">Reset</button>\n    <span class="gps-camera-status">Foto dan GPS akan disiapkan otomatis.</span>\n  </div>\n  <img class="gps-camera-preview" alt="Preview foto" hidden />\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.gps-camera-box {\n  display: flex;\n  flex-wrap: wrap;\n  gap: 10px;\n  align-items: center;\n  padding: 12px;\n  border: 1px solid #e2e8f0;\n  border-radius: 12px;\n  background: #f8fafc;\n}\n.gps-camera-trigger,\n.gps-camera-clear {\n  padding: 10px 14px;\n  border-radius: 10px;\n  border: 1px solid #cbd5e1;\n  background: #fff;\n  font-weight: 700;\n}\n.gps-camera-trigger {\n  background: #4f46e5;\n  border-color: #4f46e5;\n  color: #fff;\n}\n.gps-camera-preview {\n  display: block;\n  max-width: 100%;\n  margin-top: 10px;\n  border-radius: 12px;\n}\n.gps-camera-status {\n  font-size: 12px;\n  color: #64748b;\n}',
                    js: '(function(){if(window.__gpsCameraComponentBinder)return;window.__gpsCameraComponentBinder=true;function setPayload(wrapper,payload){var input=wrapper.querySelector(\"[data-gps-camera-payload]\");if(input){input.value=JSON.stringify(payload||{});}var status=wrapper.querySelector(\".gps-camera-status\");if(status){var text=[];if(payload.photo_name)text.push(payload.photo_name);if(payload.latitude||payload.longitude)text.push((payload.latitude||\"-\") + \", \" + (payload.longitude||\"-\"));status.textContent=text.join(\" | \")||\"Foto dan GPS akan disiapkan otomatis.\";}}function setPreview(wrapper,src){var preview=wrapper.querySelector(\".gps-camera-preview\");if(!preview)return;if(src){preview.src=src;preview.hidden=false;}else{preview.removeAttribute(\"src\");preview.hidden=true;}}function fileToDataUrl(file){return new Promise(function(resolve,reject){var reader=new FileReader();reader.onload=function(){resolve(String(reader.result||\"\"));};reader.onerror=function(){reject(reader.error||new Error(\"read_error\"));};reader.readAsDataURL(file);});}function captureGps(){if(!navigator.geolocation){return Promise.resolve({});}return new Promise(function(resolve){navigator.geolocation.getCurrentPosition(function(position){resolve({latitude:position.coords.latitude,longitude:position.coords.longitude,gps_accuracy:position.coords.accuracy});},function(){resolve({});},{enableHighAccuracy:true,maximumAge:0,timeout:10000});});}async function handleFile(wrapper,file){if(!file){setPayload(wrapper,{});setPreview(wrapper,\"\");return;}var imageSrc=\"\";try{imageSrc=await fileToDataUrl(file);}catch(e){}var gps=await captureGps();setPayload(wrapper,{photo_name:file.name||\"\",photo_mime:file.type||\"\",photo_size:file.size||0,photo_data:imageSrc,latitude:gps.latitude||\"\",longitude:gps.longitude||\"\",gps_accuracy:gps.gps_accuracy||\"\",captured_date:\"\",captured_time:\"\",captured_at_server:\"\"});if(imageSrc){setPreview(wrapper,imageSrc);}}document.addEventListener(\"click\",function(event){var trigger=event.target.closest(\".gps-camera-trigger\");if(trigger){var wrapper=trigger.closest(\".gps-camera-field\");var input=wrapper&&wrapper.querySelector(\".gps-camera-file\");if(input){input.click();}event.preventDefault();return;}var clearBtn=event.target.closest(\".gps-camera-clear\");if(clearBtn){var clearWrapper=clearBtn.closest(\".gps-camera-field\");if(clearWrapper){var input=clearWrapper.querySelector(\".gps-camera-file\");if(input)input.value=\"\";setPayload(clearWrapper,{});setPreview(clearWrapper,\"\");}event.preventDefault();}});document.addEventListener(\"change\",function(event){var input=event.target.closest(\".gps-camera-file\");if(!input)return;var wrapper=input.closest(\".gps-camera-field\");if(!wrapper)return;handleFile(wrapper,input.files&&input.files[0]?input.files[0]:null);});})();'
                },
                file: {
                    html: '<div class="field-wrapper">\n  <label class="field-label">{label}</label>\n  <div class="file-upload">\n    <input type="file" name="{name}" class="field-input" />\n    <span class="file-hint">Klik atau drag file ke sini</span>\n  </div>\n</div>',
                    css: '.field-wrapper {\n  margin-bottom: 16px;\n}\n.field-label {\n  display: block;\n  font-weight: 600;\n  margin-bottom: 6px;\n}\n.file-upload {\n  border: 2px dashed #e2e8f0;\n  border-radius: 8px;\n  padding: 24px;\n  text-align: center;\n}\n.file-hint {\n  display: block;\n  color: #94a3b8;\n  font-size: 13px;\n  margin-top: 8px;\n}',
                    js: ''
                }
            };

            var template = baseCodeTemplates[fieldType] || baseCodeTemplates.text;
            var code = template[lang] || '';

            // Replace placeholders with field values
            if (formFields[selectedIndex]) {
                code = code.replace(/{label}/g, formFields[selectedIndex].label || 'Label');
                code = code.replace(/{placeholder}/g, formFields[selectedIndex].placeholder || '');
                code = code.replace(/{name}/g, formFields[selectedIndex].name || getFieldTokenName(formFields[selectedIndex], selectedIndex));
                code = code.replace(/{type}/g, formFields[selectedIndex].type || 'text');
            }

            return code;
        }

        window.updateFieldCodeInState = function() {
            if (!monacoEditor || !formFields[selectedIndex]) return;

            var code = monacoEditor.getValue();
            var codeKey = 'custom' + currentCodeLang.charAt(0).toUpperCase() + currentCodeLang.slice(1);
            formFields[selectedIndex][codeKey] = code;

            clearTimeout(window.fieldCodeUpdateTimer);
            window.fieldCodeUpdateTimer = setTimeout(function() {
                updateData();
            }, 500);
        };

        window.switchCodeLang = function(lang) {
            currentCodeLang = lang;
            document.querySelectorAll('.code-lang-btn').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.lang === lang);
                btn.style.background = btn.classList.contains('active') ? '#6366f1' : 'transparent';
                btn.style.color = btn.classList.contains('active') ? 'white' : '#94a3b8';
                btn.style.borderColor = btn.classList.contains('active') ? '#6366f1' : '#475569';
            });

            if (monacoEditor && typeof monaco !== 'undefined') {
                var model = monacoEditor.getModel();
                if (model) {
                    var language = currentCodeLang === 'js' ? 'javascript' : currentCodeLang;
                    monaco.editor.setModelLanguage(model, language);
                }
            }

            loadFieldCodeFromState();
        };

        window.setCodeScope = function(scope) {
            activeCodeScope = scope === 'page' ? 'page' : 'component';
            document.querySelectorAll('.code-scope-btn').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.scope === activeCodeScope);
            });
            const hint = document.getElementById('code-mode-hint');
            if (hint) {
                hint.textContent = activeCodeScope === 'page' ?
                    'Preview canvas memakai Page Source ini. HTML/CSS di sini akan menggantikan UI builder default.' :
                    'Edit custom code untuk field yang dipilih (HTML/CSS/JS terpisah).';
            }
            const tools = document.getElementById('component-code-tools');
            if (tools) {
                tools.style.display = activeCodeScope === 'page' ? 'none' : 'flex';
            }
            const pageTools = document.getElementById('page-code-tools');
            if (pageTools) {
                pageTools.style.display = activeCodeScope === 'page' ? 'flex' : 'none';
            }

            // Handle code scope switching
            if (activeCodeScope === 'page') {
                // Generate and display page source
                if (monacoEditor) {
                    const pageSource = fullFormCustomHtml || generatePageSource();
                    isSyncingCode = true;
                    monacoEditor.setValue(pageSource);
                    isSyncingCode = false;

                    // Update language to HTML and hide component-specific tools
                    if (typeof monaco !== 'undefined') {
                        var model = monacoEditor.getModel();
                        if (model) {
                            monaco.editor.setModelLanguage(model, 'html');
                        }
                    }
                }
            } else if (activeCodeScope === 'component') {
                // Show component code
                if (monacoEditor && formFields[selectedIndex]) {
                    loadFieldCodeFromState();
                }
            }
            renderCanvasMode();
        };

        function updateFormCustomCodeInState() {
            if (!monacoEditor) return;
            fullFormCustomHtml = monacoEditor.getValue();
            fullFormCustomCss = '';
            fullFormCustomJs = '';
            updateCustomCodeInputs();
            renderCanvasMode();
        }

        function loadFormCustomCodeFromState() {
            if (!monacoEditor) return;
            isSyncingCode = true;
            monacoEditor.setValue(fullFormCustomHtml || generatePageSource());
            isSyncingCode = false;
            renderCanvasMode();
        }

        function updateCustomCodeInputs() {
            const useInput = document.getElementById('use-custom-code-input');
            const htmlInput = document.getElementById('custom-html-input');
            const cssInput = document.getElementById('custom-css-input');
            const jsInput = document.getElementById('custom-js-input');
            const useCustom = activeCodeScope === 'page' && (fullFormCustomHtml || '').trim() !== '';
            if (useInput) useInput.value = useCustom ? '1' : '0';
            if (htmlInput) htmlInput.value = useCustom ? fullFormCustomHtml : '';
            if (cssInput) cssInput.value = useCustom ? fullFormCustomCss : '';
            if (jsInput) jsInput.value = useCustom ? fullFormCustomJs : '';
        }

        function getCustomSourceForCanvas() {
            const source = activeCodeScope === 'page' && monacoEditor ? monacoEditor.getValue() : fullFormCustomHtml;
            return resolveFormSourceTokens((source || '').trim() || generatePageSource());
        }

        function humanizeFieldName(value) {
            return String(value || '')
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/\b\w/g, function(match) {
                    return match.toUpperCase();
                });
        }

        function getFieldTokenName(field, index) {
            return String(field.name || field.field_name || field.column_name || field.id || ('field_' + (index + 1)))
                .trim()
                .replace(/[^a-zA-Z0-9_]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        function getFieldLabel(field, index) {
            return field.label || field.field_label || field.labelText || humanizeFieldName(getFieldTokenName(field, index));
        }

        function getFieldPlaceholder(field, index) {
            if (field.placeholder) return field.placeholder;
            const type = field.type || 'text';
            if (type === 'date') return '';
            if (type === 'select') return 'Pilih ' + getFieldLabel(field, index).toLowerCase();
            return 'Masukkan ' + getFieldLabel(field, index).toLowerCase();
        }

        function escapeRegExp(value) {
            return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function applyFieldTokensToCode(code, field, index) {
            let resolved = String(code || '')
                .replace(/\{label\}/g, getFieldLabel(field, index))
                .replace(/\{placeholder\}/g, getFieldPlaceholder(field, index))
                .replace(/\{name\}/g, field.name || getFieldTokenName(field, index))
                .replace(/\{type\}/g, field.type || 'text');
            if (String(code || '').indexOf('{options}') !== -1) {
                resolved = resolved.replace(/\{options\}/g, buildSelectOptionsMarkup(field));
            }
            return resolved;
        }

        function normalizeGeneratedFieldMarkup(markup, field, index) {
            let resolved = String(markup || '');
            const fieldName = field.name || getFieldTokenName(field, index);
            const fieldLabel = getFieldLabel(field, index);
            const fieldPlaceholder = getFieldPlaceholder(field, index);

            resolved = resolved.replace(/(<label\b[^>]*class="field-label"[^>]*>)([\s\S]*?)(<\/label>)/i, function(_, open, _text, close) {
                return open + escapeHtml(fieldLabel) + close;
            });

            resolved = resolved.replace(/<(input|select|textarea)\b([^>]*)\bname=(["'])(.*?)\3([^>]*)>/i, function(match, tag, before, quote, _name, after) {
                return '<' + tag + before + 'name=' + quote + escapeAttr(fieldName) + quote + after + '>';
            });

            if (String(field.type || '').toLowerCase() === 'select') {
                const optionsMarkup = buildSelectOptionsMarkup(field);
                const placeholderLabel = escapeHtml(field.placeholder || 'Pilih...');
                resolved = resolved.replace(/<select\b([\s\S]*?)>[\s\S]*?<\/select>/i, function(_match, attrs) {
                    return '<select' + attrs + '>\n    <option value="">' + placeholderLabel + '</option>' + (optionsMarkup ? '\n    ' + optionsMarkup.split('\n').join('\n    ') + '\n  ' : '\n  ') + '</select>';
                });
            } else if (resolved.includes('placeholder=')) {
                resolved = resolved.replace(/placeholder=(["'])(.*?)\1/i, 'placeholder="' + escapeAttr(fieldPlaceholder) + '"');
            } else if (/<(input|textarea)\b/i.test(resolved) && String(field.type || '').toLowerCase() !== 'date') {
                resolved = resolved.replace(/<(input|textarea)\b([^>]*)>/i, function(match, tag, attrs) {
                    if (tag.toLowerCase() === 'input' || tag.toLowerCase() === 'textarea') {
                        return '<' + tag + attrs + ' placeholder="' + escapeAttr(fieldPlaceholder) + '">';
                    }
                    return match;
                });
            }

            return resolved;
        }

        function resolveFormSourceTokens(source) {
            let resolved = String(source || '');

            formFields.forEach(function(field, index) {
                const name = getFieldTokenName(field, index);
                const label = getFieldLabel(field, index);
                const placeholder = getFieldPlaceholder(field, index);
                resolved = resolved
                    .replace(new RegExp('\\{' + name + '_label\\}', 'g'), label)
                    .replace(new RegExp('\\{' + name + '_placeholder\\}', 'g'), placeholder)
                    .replace(new RegExp('\\{' + name + '_name\\}', 'g'), field.name || name)
                    .replace(new RegExp('\\{' + name + '_id\\}', 'g'), field.id || name);

                const fieldName = field.name || name;
                const namePattern = escapeRegExp(fieldName);
                const labelPattern = new RegExp('(<label\\b[^>]*>)\\{label\\}(<\\/label>[\\s\\S]*?<(?:input|select|textarea)\\b(?=[^>]*\\bname=[\"\\\']' + namePattern + '[\"\\\']))', 'gi');
                const placeholderPattern = new RegExp('(<(?:input|textarea)\\b(?=[^>]*\\bname=[\"\\\']' + namePattern + '[\"\\\'])(?=[^>]*\\bplaceholder=[\"\\\'])[^>]*\\bplaceholder=[\"\\\'])\\{placeholder\\}([\"\\\'][^>]*>)', 'gi');
                resolved = resolved
                    .replace(labelPattern, '$1' + label + '$2')
                    .replace(placeholderPattern, '$1' + placeholder + '$2');
            });

            return resolved;
        }

        function renderCanvasMode() {
            const workspace = document.querySelector('.builder-workspace');
            if (!workspace || !workspace.parentNode) return;
            const generatorToolbar = workspace.previousElementSibling;

            let preview = document.getElementById('custom-code-canvas-preview');
            if (activeCodeScope !== 'page') {
                if (generatorToolbar) {
                    generatorToolbar.style.display = '';
                }
                workspace.style.display = '';
                if (preview) preview.remove();
                return;
            }

            if (generatorToolbar) {
                generatorToolbar.style.display = 'none';
            }
            workspace.style.display = 'none';
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'custom-code-canvas-preview';
                preview.style.cssText = 'display:flex;flex:1 1 auto;min-height:620px;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,0.08);';
                workspace.parentNode.insertBefore(preview, workspace.nextSibling);
            }

            preview.innerHTML = '';
            const iframe = document.createElement('iframe');
            iframe.title = 'Custom Code Preview';
            iframe.srcdoc = getCustomSourceForCanvas();
            iframe.setAttribute('sandbox', 'allow-scripts allow-forms allow-same-origin');
            iframe.style.cssText = 'display:block;width:100%;height:100%;min-height:620px;border:0;background:#fff;';
            preview.appendChild(iframe);
        }

        function logSubmitPayload(form) {
            if (!window.console || !console.debug) return;
            const payload = new FormData(form);
            console.debug('MasterForm custom code payload', {
                use_custom_code: payload.get('MasterForm[use_custom_code]'),
                custom_html_length: String(payload.get('MasterForm[custom_html]') || '').length,
                custom_css_length: String(payload.get('MasterForm[custom_css]') || '').length,
                custom_js_length: String(payload.get('MasterForm[custom_js]') || '').length
            });
        }

        // Generate full page HTML source from all fields
        function generatePageSource() {
            const lines = [];
            lines.push('<!-- Generated Form Layout -->');
            lines.push('<form class="auto-generated-form" method="POST">');
            lines.push('  <div class="form-container" style="max-width: 600px; margin: 0 auto;">');

            formFields.forEach((field, index) => {
                if (field.excluded) return;
                lines.push('');
                lines.push('    <!-- Field ' + (index + 1) + ': ' + field.label + ' -->');

                if (field.customHtml && !(isRelationSelectField(field) && looksLikeDummySelectCode(field.customHtml))) {
                    lines.push('    ' + normalizeGeneratedFieldMarkup(applyFieldTokensToCode(field.customHtml, field, index), field, index).split('\n').join('\n    '));
                } else {
                    // Use base template
                    const baseCode = getFieldBaseCode(field.type, 'html');
                    lines.push('    ' + normalizeGeneratedFieldMarkup(applyFieldTokensToCode(baseCode, field, index), field, index).split('\n').join('\n    '));
                }
            });

            lines.push('');
            lines.push('    <!-- Submit Button -->');
            lines.push('    <div style="margin-top: 24px;">');
            lines.push('      <button type="submit" class="btn-submit" style="width: 100%; padding: 12px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">');
            lines.push('        Submit');
            lines.push('      </button>');
            lines.push('    </div>');
            lines.push('  </div>');
            lines.push('</form>');
            lines.push('');
            lines.push('<!-- Embedded Styles -->');
            lines.push('<style>');
            lines.push('.form-container { padding: 24px; background: #ffffff; border-radius: 12px; }');

            // Collect all custom CSS
            formFields.forEach((field, index) => {
                if (field.customCss) {
                    lines.push('');
                    lines.push('/* Field ' + (index + 1) + ' */');
                    lines.push(field.customCss);
                } else {
                    const baseCode = getFieldBaseCode(field.type, 'css');
                    if (baseCode) {
                        lines.push('');
                        lines.push('/* Field ' + (index + 1) + ' Default Styles */');
                        lines.push(baseCode);
                    }
                }
            });

            lines.push('</style>');

            // Check if there's any JS
            const hasJs = formFields.some(f => f.customJs);
            if (hasJs) {
                lines.push('');
                lines.push('<!-- Embedded Scripts -->');
                lines.push('<script>');
                formFields.forEach((field, index) => {
                    if (field.customJs) {
                        lines.push('');
                        lines.push('// Field ' + (index + 1) + ' - ' + field.label);
                        lines.push(field.customJs);
                    }
                });
                lines.push('<\\/script>');
            }

            return lines.join('\n');
        }


        // Add direct and delegated handlers so dynamically refreshed panels still switch renderer.
        document.querySelectorAll('.code-scope-btn').forEach(function(btn) {
            btn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                setCodeScope(this.dataset.scope);
            });
        });
        document.addEventListener('click', function(event) {
            const scopeButton = event.target.closest('.code-scope-btn');
            if (!scopeButton) return;
            event.preventDefault();
            setCodeScope(scopeButton.dataset.scope);
        });

        // Render Fields
        function renderFields() {
            if (activeCodeScope === 'page') {
                renderCanvasMode();
            }

            if (formFields.length === 0) {
                if (placeholder) placeholder.style.display = 'block';
                if (container) container.innerHTML = '';
                if (fieldCountHint) fieldCountHint.textContent = '0 fields';
                if (window.formSortableInstance) {
                    window.formSortableInstance.destroy();
                    window.formSortableInstance = null;
                }
                renderPropsPanel(null);
                return;
            }

            if (placeholder) placeholder.style.display = 'none';
// PERBAIKAN BUG 2: try-catch per field
            const renderedItems = [];
            formFields.forEach(function(field, i) {
                try {
                    const selected = selectedIndex === i ? 'selected' : '';
                    const isExcluded = field.excluded === true;
                    renderedItems.push(
                        '<div class="field-item ' + selected + '" data-index="' + i + '" data-field-id="' + (field.id || ('field_' + i)) + '">' +
                        '<div class="field-item-header">' +
                        '<div class="field-item-label">' +
                        '<span class="material-symbols-outlined field-drag-handle" data-drag="' + i + '">drag_indicator</span>' +
                        '<span class="material-symbols-outlined">' + (fieldIcons[field.type] || 'text_fields') + '</span>' +
                        (field.label || field.name || ('Field ' + (i + 1))) +
                        (field.required ? '<span class="field-item-required">*</span>' : '') +
                        (field.is_foreign_key ? '<span class="field-badge-fk">FK</span>' : '') +
                        '</div>' +
                        '<div class="field-actions">' +
                        (isExcluded ? '<span class="field-badge-auto" style="margin-right:4px;">Hidden</span>' : '') +
                        '<button class="field-actions-btn" data-duplicate="' + i + '" title="Duplicate"><span class="material-symbols-outlined">content_copy</span></button>' +
                        '<button class="field-actions-btn" data-settings="' + i + '" title="Settings"><span class="material-symbols-outlined">tune</span></button>' +
                        '<button class="field-actions-btn delete" data-delete="' + i + '" title="Delete"><span class="material-symbols-outlined">delete</span></button>' +
                        '</div>' +
                        '</div>' +
                        (isExcluded ? '<div class="field-preview" style="background:#fef3c7;border-color:#fcd34d;"><span style="color:#92400e;font-size:12px;">Field disembunyikan dari form (excluded)</span></div>' : renderPreview(field)) +
                        '<div class="field-name">Name: ' + (field.name || '') + (field.is_foreign_key ? ' <span class="field-badge-fk">FK ' + (field.fk_referenced_table || '') + '</span>' : '') + '</div>' +
                        '</div>'
                    );
                } catch (fieldRenderErr) {
                    console.error('BUG 2 FIX: Gagal render field index ' + i + ':', fieldRenderErr, field);
                    renderedItems.push(
                        '<div class="field-item field-item-error" data-index="' + i + '">' +
                        '<div class="field-preview" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">' +
                        'Field #' + (i + 1) + ' gagal dirender: ' + escapeHtml(field.label || field.name || field.type || 'unknown') +
                        '</div></div>'
                    );
                }
            });

            if (container) {
                container.innerHTML = renderedItems.join('');
            }
            if (fieldCountHint) {
                fieldCountHint.textContent = formFields.length + ' field' + (formFields.length > 1 ? 's' : '');
            }

            // Event listeners
            container.querySelectorAll('.field-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (e.target.dataset.delete || e.target.closest('[data-delete]')) return;
                    selectField(parseInt(this.dataset.index));
                });
            });

            container.querySelectorAll('[data-delete]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    deleteField(parseInt(this.dataset.delete));
                });
            });

            container.querySelectorAll('[data-duplicate]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    duplicateField(parseInt(this.dataset.duplicate));
                });
            });

            container.querySelectorAll('[data-settings]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    selectField(parseInt(this.dataset.settings));
                });
            });

            initSortable();
        }

        // Duplicate Field
        function duplicateField(index) {
            if (index >= 0 && index < formFields.length) {
                const original = formFields[index];
                const newField = JSON.parse(JSON.stringify(original));
                newField.id = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                newField.name = original.name + '_copy';
                newField.label = original.label + ' (Copy)';
                normalizeFieldState(newField);
                formFields.splice(index + 1, 0, newField);
                renderFields();
                updateData();
            }
        }

        // Delete Field
        function deleteField(index) {
            if (index >= 0 && index < formFields.length) {
                formFields.splice(index, 1);
                if (selectedIndex === index) selectedIndex = null;
                else if (selectedIndex > index) selectedIndex--;
                renderFields();
                updateData();
            }
        }

        // Add Field
        function addField(type, props = {}) {
            const cfg = fieldConfig[type];
            if (!cfg) return;

            const field = {
                id: 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                type: type,
                inputType: cfg.inputType,
                label: props.label || cfg.label,
                name: props.name || 'field_' + (formFields.length + 1),
                required: props.required || false,
                placeholder: props.placeholder || cfg.placeholder || '',
                ...(cfg.options ? {
                    options: [...cfg.options]
                } : {}),
                rows: cfg.rows || props.rows
            };
            if (type === 'gps_camera') {
                field.auto_capture_gps = props.auto_capture_gps !== undefined ? props.auto_capture_gps : true;
                field.auto_capture_timestamp = props.auto_capture_timestamp !== undefined ? props.auto_capture_timestamp : true;
                field.preview_image = props.preview_image !== undefined ? props.preview_image : true;
                field.target_table_id = props.target_table_id || '';
                field.target_table_name = props.target_table_name || '';
                field.target_column_id = props.target_column_id || '';
                field.target_column_name = props.target_column_name || '';
                field.gps_camera_bindings = Array.isArray(props.gps_camera_bindings) ? props.gps_camera_bindings : [];
            }
            normalizeFieldState(field);

            formFields.push(field);
            renderFields();
            updateData();
            selectField(formFields.length - 1);
        }

        // Update Data
        function updateData() {
            const removedSystemFields = removeSystemFieldsFromState();
            const input = document.getElementById('form-data-input');
            if (input) {
                formFields = formFields.map(normalizeFieldState);
                input.value = JSON.stringify({
                    fields: formFields
                });
            }
            if (removedSystemFields) {
                renderFields();
                if (selectedIndex === null && propsPanel) {
                    renderPropsPanel(null);
                }
            }
        }

        function removeSystemFieldsFromState() {
            const beforeCount = formFields.length;
            formFields = formFields.map(normalizeFieldState);
            formFields = formFields.filter(field => !isSystemField(field.name || field.field_name || field.field_key));
            if (formFields.length !== beforeCount || (selectedIndex !== null && !formFields[selectedIndex])) {
                selectedIndex = null;
            }
            return formFields.length !== beforeCount;
        }

        // Drag handlers
        componentItems.forEach(item => {
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('fieldType', this.dataset.fieldType);
            });
            item.addEventListener('click', function() {
                addField(this.dataset.fieldType);
            });
        });
        renderPropsPanel(null);

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function() {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const fieldType = e.dataTransfer.getData('fieldType');
            if (fieldType) addField(fieldType);
        });

        // Form submit
        document.getElementById('master-form-form').addEventListener('submit', function(e) {
            const formName = this.querySelector('[name="MasterForm[form_name]"]').value.trim();
            if (!formName) {
                e.preventDefault();
                alert('Masukkan nama form');
                return;
            }
            const customSource = activeCodeScope === 'page' && monacoEditor ? monacoEditor.getValue().trim() : (fullFormCustomHtml || '').trim();
            if (formFields.length === 0 && customSource === '') {
                e.preventDefault();
                alert('Tambahkan minimal satu field atau custom code');
                return;
            }

            const slug = formName.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');

            let slugInput = this.querySelector('input[name="auto_slug"]');
            if (!slugInput) {
                slugInput = document.createElement('input');
                slugInput.type = 'hidden';
                slugInput.name = 'MasterForm[slug]';
                this.appendChild(slugInput);
            }
            slugInput.value = slug;

            updateData();
            if (activeCodeScope === 'page' && monacoEditor) {
                updateFormCustomCodeInState();
            } else {
                updateCustomCodeInputs();
            }
            logSubmitPayload(this);
        });

        // Load tables
        fetch('/tables/get-tables', {
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(res => res.json())
            .then(data => {
                const selector = document.getElementById('table-selector');
                const currentTableId = getCurrentBuilderTableId();
                if (data.tables && data.tables.length > 0) {
                    data.tables.forEach(table => {
                        const opt = document.createElement('option');
                        opt.value = table.id;
                        opt.textContent = table.label;
                        opt.dataset.name = table.name;
                        selector.appendChild(opt);
                    });
                }

                if (currentTableId) {
                    selector.value = String(currentTableId);
                    document.getElementById('table-id-input').value = String(currentTableId);
                    ensureDropdownSourceColumnsLoaded(currentTableId);
                }
            });

        // Generate fields from table
        document.getElementById('generate-from-table').addEventListener('click', function() {
            const tableId = parseInt(document.getElementById('table-selector').value, 10);
            if (!tableId || isNaN(tableId)) {
                alert('Pilih tabel terlebih dahulu');
                return;
            }

            document.getElementById('table-id-input').value = tableId;

            fetch('/tables/columns/' + tableId + '?t=' + Date.now(), {
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    if (data.columns && data.columns.length > 0) {
                        formFields = [];
                        let fkPromises = [];

                        data.columns.forEach(col => {
                            const isPrimaryKey = !!(col.is_primary);
                            const isAutoIncrement = !!(col.is_auto_increment);
                            const isForeignKey = !!(col.is_foreign_key);

                            if (col.is_system_field || isSystemField(col.name) || isPrimaryKey || isAutoIncrement) {
                                return;
                            }

                            const colName = (col.name || '').toLowerCase();

                            let fieldType = 'text';
                            const colType = normalizeColumnType(col.db_type || col.column_type || col.data_type || col.base_type || col.type || '');

                            if (isForeignKey) {
                                fieldType = 'select';
                            } else if (isBooleanColumnType(colType, parseInt(col.length || col.max_length || '', 10))) {
                                fieldType = 'boolean';
                            } else if (colType.includes('int') || colType.includes('decimal') || colType.includes('float') || colType.includes('double') || colType === 'tinyint') {
                                fieldType = 'number';
                            } else if (colType.includes('text') || colType.includes('varchar') || colType.includes('char')) {
                                if (colName.includes('email')) fieldType = 'email';
                                else if (colName.includes('url') || colName.includes('website')) fieldType = 'url';
                                else if (colName.includes('phone') || colName.includes('telepon')) fieldType = 'tel';
                            } else {
                                fieldType = getFieldTypeFromColumnType(colType);
                            }

                            const fieldId = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                            const fieldData = {
                                id: fieldId,
                                type: fieldType,
                                inputType: getInputType(fieldType),
                                label: col.label || col.name,
                                name: col.name,
                                field_name: col.name,
                                field_key: col.name,
                                column_name: col.name,
                                required: !col.is_nullable,
                                placeholder: '',
                                default_value: col.default_value || '',
                                excluded: false,
                                source_column_id: col.id,
                                source_column_db_type: col.db_type || '',
                                source_column_column_type: col.column_type || col.db_type || '',
                                source_column_data_type: col.data_type || '',
                                source_column_length: col.length || col.max_length || 0,
                                source_column_type: colType,
                                is_foreign_key: isForeignKey,
                                is_primary: isPrimaryKey,
                                is_auto_increment: isAutoIncrement,
                                local_column: col.name,
                                fk_referenced_table: isForeignKey ? col.referenced_table_name : null,
                                fk_referenced_column: isForeignKey ? col.referenced_column_name : null,
                                fk_display_column: isForeignKey ? col.referenced_column_name : null,
                                relation_table_name: isForeignKey ? col.referenced_table_name : null,
                                relation_target_column: col.name,
                                relation_value_column: isForeignKey ? col.referenced_column_name : null,
                                relation_display_column: isForeignKey ? col.referenced_column_name : null,
                                relation_config: isForeignKey ? {
                                    local_column: col.name,
                                    source_column: col.name,
                                    column_name: col.name,
                                    referenced_table: col.referenced_table_name || '',
                                    referenced_table_name: col.referenced_table_name || '',
                                    referenced_value_column: col.referenced_column_name || '',
                                    referenced_column: col.referenced_column_name || '',
                                    referenced_column_name: col.referenced_column_name || '',
                                    value_column: col.referenced_column_name || '',
                                    display_column: col.referenced_column_name || '',
                                    display_column_name: col.referenced_column_name || ''
                                } : null,
                                fk_options: isForeignKey ? [] : null,
                                fk_options_loaded: false,
                            };

                            normalizeFieldState(fieldData);
                            formFields.push(fieldData);

                            if (isForeignKey && col.id) {
                                fkPromises.push(
                                    fetch('/tables/foreign-key-options/' + col.id + '?t=' + Date.now(), {
                                        headers: {
                                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(fkData => {
                                        if (fkData.success && fkData.options) {
                                            const fkField = formFields.find(f => f.source_column_id === col.id);
                                            if (fkField) {
                                                fkField.local_column = fkData.local_column || fkField.name;
                                                fkField.source_table_id = fkData.referenced_table_id || fkField.source_table_id || '';
                                                fkField.dropdown_table_id = fkField.source_table_id || fkField.dropdown_table_id || '';
                                                fkField.source_table_name = fkData.referenced_table || fkField.source_table_name || '';
                                                fkField.fk_options = fkData.options;
                                                fkField.fk_options_loaded = true;
                                                fkField.fk_display_column = fkData.display_column;
                                                fkField.fk_referenced_table = fkData.referenced_table;
                                                fkField.fk_referenced_column = fkData.referenced_value_column || col.referenced_column_name || fkField.fk_referenced_column;
                                                fkField.value_column = fkField.fk_referenced_column;
                                                fkField.dropdown_value_column = fkField.fk_referenced_column;
                                                fkField.label_column = fkData.display_column || fkField.label_column;
                                                fkField.dropdown_label_column = fkField.label_column;
                                                fkField.relation_table_name = fkData.referenced_table;
                                                fkField.relation_display_column = fkData.display_column;
                                                fkField.relation_value_column = fkField.fk_referenced_column;
                                                fkField.relation_config = Object.assign({}, fkField.relation_config || {}, {
                                                    local_column: fkField.local_column || fkField.name,
                                                    source_column: fkField.local_column || fkField.name,
                                                    column_name: fkField.local_column || fkField.name,
                                                    referenced_table: fkField.fk_referenced_table || '',
                                                    referenced_table_name: fkField.fk_referenced_table || '',
                                                    referenced_value_column: fkField.fk_referenced_column || '',
                                                    referenced_column: fkField.fk_referenced_column || '',
                                                    referenced_column_name: fkField.fk_referenced_column || '',
                                                    value_column: fkField.fk_referenced_column || '',
                                                    display_column: fkField.fk_display_column || '',
                                                    display_column_name: fkField.fk_display_column || ''
                                                });
                                            }
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Error loading FK options for column', col.name, ':', err);
                                    })
                                );
                            }
                        });

                        Promise.all(fkPromises).then(() => {
                            renderFields();
                            updateData();
                        });

                        const formNameInput = document.querySelector('[name="MasterForm[form_name]"]');
                        if (!formNameInput.value && data.table_label) {
                            formNameInput.value = data.table_label + ' Form';
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching columns:', err);
                    alert('Gagal mengambil kolom tabel');
                });
        });

        function getInputType(fieldType) {
            const types = {
                text: 'text',
                email: 'email',
                password: 'password',
                number: 'number',
                tel: 'tel',
                url: 'url',
                textarea: 'textarea',
                select: 'select',
                radio: 'radio',
                checkbox: 'checkbox',
                checkboxes: 'checkboxes',
                boolean: 'boolean',
                date: 'date',
                time: 'time',
                datetime: 'datetime-local',
                file: 'file',
                gps_camera: 'gps_camera',
                hidden: 'hidden'
            };
            return types[fieldType] || 'text';
        }

        function getFieldTypeFromColumnType(columnType) {
            const normalizedType = normalizeColumnType(columnType);
            if (isBooleanColumnType(normalizedType)) return 'boolean';
            if (normalizedType.startsWith('date')) return 'date';
            if (normalizedType.startsWith('time')) return 'time';
            if (normalizedType === 'datetime' || normalizedType === 'timestamp') return 'datetime';
            return 'text';
        }

        function normalizeColumnType(columnType) {
            return String(columnType || '')
                .trim()
                .toLowerCase()
                .replace(/\s+(unsigned|zerofill)\b/g, '')
                .replace(/\s+/g, '');
        }

        function isBooleanColumnType(columnType, length) {
            const normalizedType = normalizeColumnType(columnType);
            if (['bool', 'boolean'].includes(normalizedType)) {
                return true;
            }
            if (normalizedType === 'bit(1)' || normalizedType === 'tinyint(1)') {
                return true;
            }
            if (normalizedType === 'tinyint' && Number(length) === 1) {
                return true;
            }
            return false;
        }

        function isSystemField(fieldName) {
            const normalizedName = String(fieldName || '').trim().toLowerCase();
            const systemFields = [
                'created_by',
                'updated_by',
                'deleted_by',
                'created_at',
                'updated_at',
                'deleted_at',
                'created_ip',
                'updated_ip'
            ];

            return systemFields.includes(normalizedName);
        }

        // PERBAIKAN BUG 2: Inisialisasi builder SETELAH semua helper & fieldIcons siap
        function initializeBuilderFromStoredData() {
            loadExistingFormData();
            renderFieldsImmediate();
            if (formFields.length > 0) {
                selectedIndex = 0;
                throttledRenderPropsPanel(formFields[0]);
            } else {
                renderPropsPanel(null);
            }
        }

        initializeBuilderFromStoredData();
    });
