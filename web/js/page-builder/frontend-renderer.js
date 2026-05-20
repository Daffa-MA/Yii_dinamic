/**
 * FRONTEND RENDERER - Render final halaman untuk user
 */

class FrontendRenderer {
    /**
     * Render halaman dari state
     */
    static render(state, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        state.forEach(node => {
            const element = this.renderNode(node);
            container.appendChild(element);
        });
    }

    /**
     * Render single node
     */
    static renderNode(node) {
        const element = this.createElementFromNode(node);
        
        // Apply inline styles
        if (node.props) {
            Object.entries(node.props).forEach(([key, value]) => {
                if (typeof value === 'string' || typeof value === 'number') {
                    try {
                        element.style[key] = value;
                    } catch (e) {
                        // Skip invalid style properties
                    }
                }
            });
        }

        return element;
    }

    /**
     * Create DOM element based on node type
     */
    static createElementFromNode(node) {
        let element;

        switch (node.type) {
            case 'section':
                element = document.createElement('section');
                element.className = 'page-section';
                if (node.children) {
                    node.children.forEach(child => {
                        element.appendChild(this.renderNode(child));
                    });
                }
                break;

            case 'row':
                element = document.createElement('div');
                element.className = 'page-row';
                element.style.display = 'flex';
                element.style.gap = '10px';
                if (node.children) {
                    node.children.forEach(child => {
                        element.appendChild(this.renderNode(child));
                    });
                }
                break;

            case 'column':
                element = document.createElement('div');
                element.className = 'page-column';
                element.style.flex = '1';
                if (node.children) {
                    node.children.forEach(child => {
                        element.appendChild(this.renderNode(child));
                    });
                }
                break;

            case 'text':
                element = document.createElement('p');
                element.className = 'page-text';
                element.textContent = node.props?.content || '';
                break;

            case 'heading':
                const level = node.props?.level || 'h2';
                element = document.createElement(level);
                element.className = 'page-heading';
                element.textContent = node.props?.content || '';
                break;

            case 'image':
                element = document.createElement('img');
                element.className = 'page-image';
                element.src = node.props?.src || '';
                element.alt = node.props?.alt || '';
                break;

            case 'button':
                element = document.createElement('a');
                element.className = 'btn btn-primary page-button';
                if (node.props?.link && node.props.link !== '#') {
                    element.href = node.props.link;
                } else {
                    element.removeAttribute('href');
                    element.setAttribute('role', 'button');
                }
                element.textContent = node.props?.text || 'Button';
                break;

            case 'form':
                element = this.createFormElement(node);
                break;

            default:
                element = document.createElement('div');
                element.className = 'page-unknown';
                element.textContent = `[${node.type}]`;
        }

        return element;
    }

    /**
     * Create form element
     */
    static createFormElement(node) {
        const form = document.createElement('form');
        form.className = 'page-form';
        form.action = node.props?.action || '/submit';
        form.method = node.props?.method || 'POST';

        if (node.fields && node.fields.length > 0) {
            node.fields.forEach(field => {
                const fieldGroup = this.createFormFieldElement(field);
                form.appendChild(fieldGroup);
            });
        }

        // Submit button
        const submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.className = 'btn btn-primary mt-3';
        submitBtn.textContent = 'Submit';
        form.appendChild(submitBtn);

        return form;
    }

    /**
     * Create single form field
     */
    static createFormFieldElement(field) {
        const group = document.createElement('div');
        group.className = 'mb-3';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.setAttribute('for', field.id);
        label.textContent = field.label || 'Field';
        group.appendChild(label);

        let input;

        switch (field.type) {
            case 'input':
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.id = field.id;
                input.name = field.name || 'field';
                input.placeholder = field.placeholder || '';
                break;

            case 'email':
                input = document.createElement('input');
                input.type = 'email';
                input.className = 'form-control';
                input.id = field.id;
                input.name = field.name || 'email';
                input.placeholder = 'Email';
                break;

            case 'textarea':
                input = document.createElement('textarea');
                input.className = 'form-control';
                input.id = field.id;
                input.name = field.name || 'message';
                input.rows = 4;
                break;

            case 'select':
                input = document.createElement('select');
                input.className = 'form-select';
                input.id = field.id;
                input.name = field.name || 'select';
                
                if (field.options && field.options.length > 0) {
                    field.options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt.value;
                        option.textContent = opt.label;
                        input.appendChild(option);
                    });
                }
                break;

            case 'checkbox':
                group.className = 'form-check mb-3';
                input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input';
                input.id = field.id;
                input.name = field.name || 'checkbox';
                
                group.appendChild(input);
                
                const checkLabel = document.createElement('label');
                checkLabel.className = 'form-check-label';
                checkLabel.setAttribute('for', field.id);
                checkLabel.textContent = field.label || 'Checkbox';
                group.appendChild(checkLabel);
                
                return group;

            default:
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.id = field.id;
        }

        if (input) {
            group.appendChild(input);
        }

        return group;
    }
}

// Global reference
window.FrontendRenderer = FrontendRenderer;
