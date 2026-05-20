/**
 * RENDER ENGINE - Builder View
 * 
 * Mengubah state menjadi DOM untuk editor
 */

class RenderEngine {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.selectedNodeId = null;
        this.draggedNodeId = null;
    }

    /**
     * Render builder view dari state
     */
    render(state) {
        if (!this.container) {
            console.error('Container not found');
            return;
        }

        this.container.innerHTML = '';
        
        state.forEach(node => {
            const element = this.renderNode(node, null);
            this.container.appendChild(element);
        });

        this.attachEventListeners();
    }

    /**
     * Render single node
     */
    renderNode(node, parentId) {
        const wrapper = document.createElement('div');
        wrapper.className = 'builder-node';
        wrapper.dataset.nodeId = node.id;
        wrapper.dataset.nodeType = node.type;
        wrapper.data = node;

        // Apply styles dari props
        if (node.props) {
            Object.entries(node.props).forEach(([key, value]) => {
                if (typeof value === 'string' || typeof value === 'number') {
                    const cssKey = this.camelToCss(key);
                    wrapper.style[key] = value;
                }
            });
        }

        // Render content based on type
        switch (node.type) {
            case 'section':
            case 'row':
            case 'column':
                this.renderContainer(wrapper, node);
                break;
            case 'text':
                wrapper.innerHTML = `<p>${this.escapeHtml(node.props.content)}</p>`;
                break;
            case 'heading':
                const level = node.props.level || 'h2';
                wrapper.innerHTML = `<${level}>${this.escapeHtml(node.props.content)}</${level}>`;
                break;
            case 'image':
                wrapper.innerHTML = `<img src="${node.props.src}" alt="${node.props.alt}" style="width:${node.props.width};height:${node.props.height};">`;
                break;
            case 'button':
                wrapper.innerHTML = node.props.link && node.props.link !== '#'
                    ? `<a href="${node.props.link}" class="btn btn-primary" style="background-color:${node.props.backgroundColor};color:${node.props.color};">${this.escapeHtml(node.props.text)}</a>`
                    : `<button type="button" class="btn btn-primary" style="background-color:${node.props.backgroundColor};color:${node.props.color};">${this.escapeHtml(node.props.text)}</button>`;
                break;
            case 'form':
                this.renderForm(wrapper, node);
                break;
            default:
                wrapper.innerHTML = `<div class="placeholder">[${node.type}]</div>`;
        }

        // Add controls
        this.addNodeControls(wrapper, node);

        return wrapper;
    }

    /**
     * Render container (section, row, column)
     */
    renderContainer(wrapper, node) {
        wrapper.classList.add('builder-container');
        wrapper.innerHTML = '<div class="children-container"></div>';
        
        const container = wrapper.querySelector('.children-container');
        
        if (node.children && node.children.length > 0) {
            node.children.forEach(child => {
                const childElement = this.renderNode(child, node.id);
                container.appendChild(childElement);
            });
        } else {
            container.innerHTML = '<div class="placeholder">Drop components here</div>';
        }
    }

    /**
     * Render form
     */
    renderForm(wrapper, node) {
        wrapper.classList.add('builder-form');
        
        let html = `<form action="${node.props.action}" method="${node.props.method}" class="builder-form-content">`;
        
        if (node.fields && node.fields.length > 0) {
            node.fields.forEach(field => {
                html += this.renderFormField(field);
            });
        } else {
            html += '<p class="text-muted">No fields. Edit to add fields.</p>';
        }
        
        html += '<button type="submit" class="btn btn-primary mt-3">Submit</button></form>';
        
        wrapper.innerHTML = html;
    }

    /**
     * Render single form field
     */
    renderFormField(field) {
        const id = field.id || `field-${Date.now()}`;
        let html = `<div class="mb-3"><label for="${id}" class="form-label">${this.escapeHtml(field.label || 'Label')}</label>`;
        
        switch (field.type) {
            case 'input':
                html += `<input type="text" class="form-control" id="${id}" name="${field.name || 'field'}" placeholder="${field.placeholder || ''}">`;
                break;
            case 'email':
                html += `<input type="email" class="form-control" id="${id}" name="${field.name || 'email'}" placeholder="Email">`;
                break;
            case 'textarea':
                html += `<textarea class="form-control" id="${id}" name="${field.name || 'message'}" rows="4"></textarea>`;
                break;
            case 'select':
                html += `<select class="form-select" id="${id}" name="${field.name || 'select'}">`;
                if (field.options && field.options.length > 0) {
                    field.options.forEach(opt => {
                        html += `<option value="${opt.value}">${this.escapeHtml(opt.label)}</option>`;
                    });
                }
                html += `</select>`;
                break;
            case 'checkbox':
                html += `<div class="form-check"><input type="checkbox" class="form-check-input" id="${id}" name="${field.name || 'checkbox'}"><label class="form-check-label" for="${id}">${this.escapeHtml(field.label || 'Checkbox')}</label></div>`;
                break;
            default:
                html += `<input type="text" class="form-control" id="${id}">`;
        }
        
        html += '</div>';
        return html;
    }

    /**
     * Add controls ke node
     */
    addNodeControls(wrapper, node) {
        const controls = document.createElement('div');
        controls.className = 'builder-node-controls';
        
        // Select button
        const selectBtn = document.createElement('button');
        selectBtn.className = 'btn btn-sm btn-outline-primary';
        selectBtn.innerHTML = '✓ Select';
        selectBtn.onclick = (e) => {
            e.stopPropagation();
            this.selectNode(node.id, wrapper);
        };
        controls.appendChild(selectBtn);
        
        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-outline-danger';
        deleteBtn.innerHTML = '✕ Delete';
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            window.pageState.deleteNode(node.id);
        };
        controls.appendChild(deleteBtn);
        
        wrapper.appendChild(controls);
    }

    /**
     * Select node
     */
    selectNode(nodeId, element) {
        // Deselect previous
        document.querySelectorAll('.builder-node.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Select new
        if (element) {
            element.classList.add('selected');
        }
        
        this.selectedNodeId = nodeId;
        
        // Emit event
        window.dispatchEvent(new CustomEvent('nodeSelected', { detail: { nodeId } }));
    }

    /**
     * Helper: camelCase to css-case
     */
    camelToCss(str) {
        return str.replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`);
    }

    /**
     * Helper: Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        document.querySelectorAll('.builder-node').forEach(el => {
            // Drag events
            el.draggable = true;
            el.addEventListener('dragstart', (e) => this.onDragStart(e));
            el.addEventListener('dragover', (e) => this.onDragOver(e));
            el.addEventListener('drop', (e) => this.onDrop(e));
            el.addEventListener('dragend', (e) => this.onDragEnd(e));
        });
    }

    /**
     * Drag start handler
     */
    onDragStart(e) {
        this.draggedNodeId = e.target.dataset.nodeId;
        e.dataTransfer.effectAllowed = 'move';
        e.target.classList.add('dragging');
    }

    /**
     * Drag over handler
     */
    onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        e.target.classList.add('drag-over');
    }

    /**
     * Drop handler
     */
    onDrop(e) {
        e.preventDefault();
        const targetNodeId = e.target.closest('.builder-node')?.dataset.nodeId;
        
        if (this.draggedNodeId && targetNodeId && this.draggedNodeId !== targetNodeId) {
            // Check if target can have children
            const targetNode = this.findNodeInState(window.pageState.getState(), targetNodeId);
            if (targetNode && canHaveChildren(targetNode.type)) {
                window.pageState.moveNode(this.draggedNodeId, targetNodeId);
            }
        }
        
        document.querySelectorAll('.builder-node').forEach(el => {
            el.classList.remove('drag-over');
        });
    }

    /**
     * Drag end handler
     */
    onDragEnd(e) {
        document.querySelectorAll('.builder-node').forEach(el => {
            el.classList.remove('dragging', 'drag-over');
        });
        this.draggedNodeId = null;
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
}

// Global instance
window.renderEngine = null;
