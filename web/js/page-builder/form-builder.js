/**
 * FORM BUILDER - Untuk mengedit form fields secara dynamic
 */

class FormBuilder {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.currentFormId = null;
    }

    /**
     * Show form builder for specific form node
     */
    showFormBuilder(formId) {
        this.currentFormId = formId;
        
        const state = window.pageState.getState();
        const form = this.findNodeInState(state, formId);
        
        if (!form || form.type !== 'form') {
            console.error('Form not found');
            return;
        }

        this.renderFormBuilder(form);
    }

    /**
     * Render form builder UI
     */
    renderFormBuilder(form) {
        let html = `
            <div class="form-builder-container">
                <h5>Form Fields Editor</h5>
                
                <div class="form-fields-list mb-3">
                    <label class="form-label">Fields:</label>
                    <div id="fields-list" class="border p-2" style="max-height: 300px; overflow-y: auto;">
        `;

        if (!form.fields || form.fields.length === 0) {
            html += '<p class="text-muted text-center py-2">No fields. Add one below.</p>';
        } else {
            form.fields.forEach((field, index) => {
                html += `
                    <div class="field-item p-2 mb-2 border rounded d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${this.escapeHtml(field.label || 'Field')}</strong>
                            <span class="badge bg-secondary">${field.type}</span>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="window.formBuilder.editField(${index})">Edit</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="window.formBuilder.removeField(${index})">Delete</button>
                        </div>
                    </div>
                `;
            });
        }

        html += `
                    </div>
                </div>

                <div class="field-type-selector mb-3">
                    <label class="form-label">Add New Field:</label>
                    <select id="new-field-type" class="form-select" onchange="window.formBuilder.onFieldTypeChange()">
                        <option value="">Select field type...</option>
                        <option value="input">Text Input</option>
                        <option value="email">Email</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select Dropdown</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                </div>

                <div id="field-editor" style="display:none;">
                    <h6>Field Properties</h6>
                    
                    <div class="mb-2">
                        <label class="form-label">Field Label:</label>
                        <input type="text" id="field-label" class="form-control" placeholder="e.g., Full Name">
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Field Name:</label>
                        <input type="text" id="field-name" class="form-control" placeholder="e.g., full_name">
                    </div>
                    
                    <div id="field-placeholder" class="mb-2" style="display:none;">
                        <label class="form-label">Placeholder:</label>
                        <input type="text" id="field-placeholder-input" class="form-control">
                    </div>

                    <div id="field-options" class="mb-2" style="display:none;">
                        <label class="form-label">Options (one per line):</label>
                        <textarea id="field-options-textarea" class="form-control" rows="4" placeholder="value1|Label 1&#10;value2|Label 2"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" onclick="window.formBuilder.saveField()">Save Field</button>
                        <button class="btn btn-secondary btn-sm" onclick="window.formBuilder.cancelEdit()">Cancel</button>
                    </div>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    /**
     * Field type change handler
     */
    onFieldTypeChange() {
        const type = document.getElementById('new-field-type').value;
        const editor = document.getElementById('field-editor');
        
        if (type) {
            editor.style.display = 'block';
            this.showFieldOptions(type);
        } else {
            editor.style.display = 'none';
        }
    }

    /**
     * Show options untuk field type tertentu
     */
    showFieldOptions(type) {
        const placeholderDiv = document.getElementById('field-placeholder');
        const optionsDiv = document.getElementById('field-options');
        
        // Reset visibility
        placeholderDiv.style.display = 'none';
        optionsDiv.style.display = 'none';
        
        // Show based on type
        if (['input', 'email', 'textarea'].includes(type)) {
            placeholderDiv.style.display = 'block';
        }
        
        if (type === 'select') {
            optionsDiv.style.display = 'block';
        }
    }

    /**
     * Save new field
     */
    saveField() {
        const type = document.getElementById('new-field-type').value;
        const label = document.getElementById('field-label').value;
        const name = document.getElementById('field-name').value;
        
        if (!type || !label || !name) {
            alert('Please fill all required fields');
            return;
        }
        
        const field = {
            id: `field-${Date.now()}`,
            type,
            label,
            name,
        };
        
        // Add type-specific properties
        if (['input', 'email', 'textarea'].includes(type)) {
            field.placeholder = document.getElementById('field-placeholder-input').value;
        }
        
        if (type === 'select') {
            const optionsText = document.getElementById('field-options-textarea').value;
            field.options = optionsText.split('\n')
                .map(line => {
                    const [value, label] = line.split('|').map(s => s.trim());
                    return { value, label };
                })
                .filter(opt => opt.value && opt.label);
        }
        
        // Add to form
        const state = window.pageState.getState();
        const form = this.findNodeInState(state, this.currentFormId);
        
        if (form) {
            if (!form.fields) form.fields = [];
            form.fields.push(field);
            window.pageState.setState(state);
        }
        
        // Reset and re-render
        document.getElementById('new-field-type').value = '';
        document.getElementById('field-editor').style.display = 'none';
        this.showFormBuilder(this.currentFormId);
    }

    /**
     * Remove field
     */
    removeField(index) {
        if (confirm('Remove this field?')) {
            const state = window.pageState.getState();
            const form = this.findNodeInState(state, this.currentFormId);
            
            if (form && form.fields) {
                form.fields.splice(index, 1);
                window.pageState.setState(state);
                this.showFormBuilder(this.currentFormId);
            }
        }
    }

    /**
     * Edit field
     */
    editField(index) {
        const state = window.pageState.getState();
        const form = this.findNodeInState(state, this.currentFormId);
        
        if (form && form.fields && form.fields[index]) {
            const field = form.fields[index];
            
            // Populate form
            document.getElementById('new-field-type').value = field.type;
            document.getElementById('field-label').value = field.label || '';
            document.getElementById('field-name').value = field.name || '';
            
            if (field.placeholder) {
                document.getElementById('field-placeholder-input').value = field.placeholder;
            }
            
            if (field.options && field.options.length > 0) {
                const optionsText = field.options
                    .map(opt => `${opt.value}|${opt.label}`)
                    .join('\n');
                document.getElementById('field-options-textarea').value = optionsText;
            }
            
            this.showFieldOptions(field.type);
            document.getElementById('field-editor').style.display = 'block';
            
            // Change button to update
            const saveBtn = document.querySelector('[onclick="window.formBuilder.saveField()"]');
            if (saveBtn) {
                saveBtn.onclick = () => this.updateField(index);
                saveBtn.textContent = 'Update Field';
            }
        }
    }

    /**
     * Update existing field
     */
    updateField(index) {
        const type = document.getElementById('new-field-type').value;
        const label = document.getElementById('field-label').value;
        const name = document.getElementById('field-name').value;
        
        if (!type || !label || !name) {
            alert('Please fill all required fields');
            return;
        }
        
        const state = window.pageState.getState();
        const form = this.findNodeInState(state, this.currentFormId);
        
        if (form && form.fields && form.fields[index]) {
            const field = form.fields[index];
            field.type = type;
            field.label = label;
            field.name = name;
            
            if (['input', 'email', 'textarea'].includes(type)) {
                field.placeholder = document.getElementById('field-placeholder-input').value;
            }
            
            if (type === 'select') {
                const optionsText = document.getElementById('field-options-textarea').value;
                field.options = optionsText.split('\n')
                    .map(line => {
                        const [value, label] = line.split('|').map(s => s.trim());
                        return { value, label };
                    })
                    .filter(opt => opt.value && opt.label);
            }
            
            window.pageState.setState(state);
            this.showFormBuilder(this.currentFormId);
        }
    }

    /**
     * Cancel edit
     */
    cancelEdit() {
        document.getElementById('new-field-type').value = '';
        document.getElementById('field-editor').style.display = 'none';
        this.showFormBuilder(this.currentFormId);
    }

    /**
     * Helper: Find node dalam state
     */
    findNodeInState(state, nodeId) {
        for (let node of state) {
            if (node.id === nodeId) return node;
            if (node.children) {
                const found = this.findNodeInState(node.children, nodeId);
                if (found) return found;
            }
        }
        return null;
    }

    /**
     * Helper: Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance
window.formBuilder = null;
