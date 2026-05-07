/**
 * PROPERTIES PANEL - Edit komponen yang dipilih
 */

class PropertiesPanel {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.currentNodeId = null;
    }

    /**
     * Show properties untuk node
     */
    showProperties(nodeId) {
        this.currentNodeId = nodeId;
        
        const state = window.pageState.getState();
        const node = this.findNodeInState(state, nodeId);
        
        if (!node) {
            this.container.innerHTML = '<p class="text-muted">Select a component to edit</p>';
            return;
        }

        let html = `<div class="properties-panel">
            <h5>${this.getComponentName(node.type)}</h5>
            <hr>
        `;

        // Render properties based on type
        switch (node.type) {
            case 'text':
                html += this.renderTextProperties(node);
                break;
            case 'heading':
                html += this.renderHeadingProperties(node);
                break;
            case 'image':
                html += this.renderImageProperties(node);
                break;
            case 'button':
                html += this.renderButtonProperties(node);
                break;
            case 'section':
            case 'row':
            case 'column':
                html += this.renderLayoutProperties(node);
                break;
            case 'form':
                html += this.renderFormProperties(node);
                break;
            default:
                html += '<p class="text-muted">No properties available</p>';
        }

        html += '</div>';
        this.container.innerHTML = html;
    }

    /**
     * Render properties untuk text
     */
    renderTextProperties(node) {
        const content = node.props?.content || '';
        const fontSize = node.props?.fontSize || '16px';
        const color = node.props?.color || '#000000';

        return `
            <div class="mb-3">
                <label class="form-label">Content:</label>
                <textarea class="form-control" rows="3" onchange="window.propsPanel.updateProp('content', this.value)">${this.escapeHtml(content)}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Font Size:</label>
                <input type="text" class="form-control" value="${fontSize}" onchange="window.propsPanel.updateProp('fontSize', this.value)" placeholder="e.g., 16px">
            </div>

            <div class="mb-3">
                <label class="form-label">Color:</label>
                <input type="color" class="form-control form-control-color" value="${color}" onchange="window.propsPanel.updateProp('color', this.value)">
            </div>
        `;
    }

    /**
     * Render properties untuk heading
     */
    renderHeadingProperties(node) {
        const content = node.props?.content || '';
        const level = node.props?.level || 'h2';
        const color = node.props?.color || '#333333';

        return `
            <div class="mb-3">
                <label class="form-label">Content:</label>
                <input type="text" class="form-control" value="${this.escapeHtml(content)}" onchange="window.propsPanel.updateProp('content', this.value)">
            </div>

            <div class="mb-3">
                <label class="form-label">Level:</label>
                <select class="form-select" onchange="window.propsPanel.updateProp('level', this.value)">
                    <option value="h1" ${level === 'h1' ? 'selected' : ''}>H1</option>
                    <option value="h2" ${level === 'h2' ? 'selected' : ''}>H2</option>
                    <option value="h3" ${level === 'h3' ? 'selected' : ''}>H3</option>
                    <option value="h4" ${level === 'h4' ? 'selected' : ''}>H4</option>
                    <option value="h5" ${level === 'h5' ? 'selected' : ''}>H5</option>
                    <option value="h6" ${level === 'h6' ? 'selected' : ''}>H6</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Color:</label>
                <input type="color" class="form-control form-control-color" value="${color}" onchange="window.propsPanel.updateProp('color', this.value)">
            </div>
        `;
    }

    /**
     * Render properties untuk image
     */
    renderImageProperties(node) {
        const src = node.props?.src || '';
        const alt = node.props?.alt || '';
        const width = node.props?.width || '300px';
        const height = node.props?.height || '200px';

        return `
            <div class="mb-3">
                <label class="form-label">Image URL:</label>
                <input type="text" class="form-control" value="${this.escapeHtml(src)}" onchange="window.propsPanel.updateProp('src', this.value)" placeholder="URL atau path">
            </div>

            <div class="mb-3">
                <label class="form-label">Alt Text:</label>
                <input type="text" class="form-control" value="${this.escapeHtml(alt)}" onchange="window.propsPanel.updateProp('alt', this.value)">
            </div>

            <div class="mb-3">
                <label class="form-label">Width:</label>
                <input type="text" class="form-control" value="${width}" onchange="window.propsPanel.updateProp('width', this.value)" placeholder="e.g., 300px or 100%">
            </div>

            <div class="mb-3">
                <label class="form-label">Height:</label>
                <input type="text" class="form-control" value="${height}" onchange="window.propsPanel.updateProp('height', this.value)" placeholder="e.g., 200px">
            </div>
        `;
    }

    /**
     * Render properties untuk button
     */
    renderButtonProperties(node) {
        const text = node.props?.text || '';
        const link = node.props?.link || '#';
        const backgroundColor = node.props?.backgroundColor || '#007bff';
        const color = node.props?.color || '#ffffff';
        const padding = node.props?.padding || '10px 20px';

        return `
            <div class="mb-3">
                <label class="form-label">Button Text:</label>
                <input type="text" class="form-control" value="${this.escapeHtml(text)}" onchange="window.propsPanel.updateProp('text', this.value)">
            </div>

            <div class="mb-3">
                <label class="form-label">Link:</label>
                <input type="text" class="form-control" value="${this.escapeHtml(link)}" onchange="window.propsPanel.updateProp('link', this.value)" placeholder="URL atau route">
            </div>

            <div class="mb-3">
                <label class="form-label">Background Color:</label>
                <input type="color" class="form-control form-control-color" value="${backgroundColor}" onchange="window.propsPanel.updateProp('backgroundColor', this.value)">
            </div>

            <div class="mb-3">
                <label class="form-label">Text Color:</label>
                <input type="color" class="form-control form-control-color" value="${color}" onchange="window.propsPanel.updateProp('color', this.value)">
            </div>

            <div class="mb-3">
                <label class="form-label">Padding:</label>
                <input type="text" class="form-control" value="${padding}" onchange="window.propsPanel.updateProp('padding', this.value)" placeholder="e.g., 10px 20px">
            </div>
        `;
    }

    /**
     * Render properties untuk layout
     */
    renderLayoutProperties(node) {
        const backgroundColor = node.props?.backgroundColor || '#ffffff';
        const padding = node.props?.padding || '20px';

        return `
            <div class="mb-3">
                <label class="form-label">Background Color:</label>
                <input type="color" class="form-control form-control-color" value="${backgroundColor}" onchange="window.propsPanel.updateProp('backgroundColor', this.value)">
            </div>

            <div class="mb-3">
                <label class="form-label">Padding:</label>
                <input type="text" class="form-control" value="${padding}" onchange="window.propsPanel.updateProp('padding', this.value)" placeholder="e.g., 20px">
            </div>
        `;
    }

    /**
     * Render properties untuk form
     */
    renderFormProperties(node) {
        const action = node.props?.action || '/submit';
        const method = node.props?.method || 'POST';

        return `
            <div class="mb-3">
                <label class="form-label">Form Action:</label>
                <input type="text" class="form-control" value="${this.escapeHtml(action)}" onchange="window.propsPanel.updateProp('action', this.value)" placeholder="/submit">
            </div>

            <div class="mb-3">
                <label class="form-label">Method:</label>
                <select class="form-select" onchange="window.propsPanel.updateProp('method', this.value)">
                    <option value="GET" ${method === 'GET' ? 'selected' : ''}>GET</option>
                    <option value="POST" ${method === 'POST' ? 'selected' : ''}>POST</option>
                </select>
            </div>

            <div class="mb-3">
                <button class="btn btn-primary btn-sm" onclick="window.formBuilder.showFormBuilder('${node.id}')">Edit Fields</button>
            </div>
        `;
    }

    /**
     * Update property
     */
    updateProp(propName, value) {
        window.pageState.updateNode(this.currentNodeId, {
            [propName]: value,
        });
    }

    /**
     * Get component name
     */
    getComponentName(type) {
        const def = getComponentDef(type);
        return def ? def.name : type;
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
window.propsPanel = null;
