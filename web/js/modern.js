/**
 * Modern UI/UX - Interactive Features & Animations
 * Drag-drop, animations, and enhanced interactions
 */

class FormBuilderUI {
    constructor() {
        this.draggingElement = null;
        this.draggedData = null;
        this.fieldCounter = 0;
        this.init();
    }

    init() {
        this.setupDragDrop();
        this.setupFieldManagement();
        this.setupAnimations();
        this.setupFormPreview();
        this.observeDOM();
    }

    // ====== DRAG & DROP ======
    
    setupDragDrop() {
        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('field-type')) {
                this.handleFieldDragStart(e);
            } else if (e.target.classList.contains('form-field')) {
                this.handleFieldDragStart(e);
            }
        });

        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (e.target.classList.contains('form-preview') || 
                e.target.closest('.form-preview')) {
                this.handleDragOver(e);
            }
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.target.classList.contains('form-preview') || 
                e.target.closest('.form-preview')) {
                this.handleDrop(e);
            }
        });

        document.addEventListener('dragend', (e) => {
            this.handleDragEnd(e);
        });

        document.addEventListener('dragleave', (e) => {
            if (e.target.classList.contains('form-preview')) {
                e.target.style.backgroundColor = '';
            }
        });
    }

    handleFieldDragStart(e) {
        this.draggingElement = e.target;
        const fieldType = e.target.dataset.fieldType || e.target.textContent.toLowerCase();
        
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('fieldType', fieldType);
        
        // Add visual feedback
        e.target.classList.add('dragging');
        e.target.style.opacity = '0.5';
        
        // Add drag image
        const img = new Image();
        e.dataTransfer.setDragImage(img, 0, 0);
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        
        const formPreview = e.target.closest('.form-preview');
        if (formPreview) {
            formPreview.style.backgroundColor = 'rgba(37, 99, 235, 0.05)';
            formPreview.style.borderColor = 'rgba(37, 99, 235, 0.2)';
        }
    }

    handleDrop(e) {
        e.preventDefault();
        
        const formPreview = e.target.closest('.form-preview');
        const fieldType = e.dataTransfer.getData('fieldType');
        
        if (formPreview && fieldType) {
            this.addFieldToForm(fieldType, formPreview);
        }
        
        if (formPreview) {
            formPreview.style.backgroundColor = '';
            formPreview.style.borderColor = '';
        }
    }

    handleDragEnd(e) {
        if (this.draggingElement) {
            this.draggingElement.classList.remove('dragging');
            this.draggingElement.style.opacity = '';
            this.draggingElement = null;
        }
    }

    // ====== FIELD MANAGEMENT ======

    addFieldToForm(fieldType, container) {
        this.fieldCounter++;
        
        const field = document.createElement('div');
        field.className = 'form-field animate-slide-in-up';
        field.dataset.fieldId = `field_${this.fieldCounter}`;
        field.draggable = true;
        
        const label = fieldType.charAt(0).toUpperCase() + fieldType.slice(1);
        const fieldName = `${fieldType}_${this.fieldCounter}`;

        field.innerHTML = `
            <div class="form-field-header">
                <div class="form-field-label">${label} Field</div>
                <div class="form-field-actions">
                    <button class="form-field-action btn-edit" title="Edit" onclick="formBuilder.editField(this)">
                        <i class="fas fa-pencil"></i>
                    </button>
                    <button class="form-field-action btn-delete" title="Delete" onclick="formBuilder.deleteField(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="form-field-content">
                <label class="form-label" style="display: block; margin-bottom: 8px;">
                    <input type="text" class="form-control form-control-sm" placeholder="Field Label" value="${label}" style="margin-bottom: 4px;">
                    <input type="checkbox" checked> Required
                </label>
                ${this.getFieldInput(fieldType)}
            </div>
        `;

        // Add smooth animation
        container.appendChild(field);
        
        // Trigger animation
        field.style.animation = 'none';
        field.offsetHeight; // Trigger reflow
        field.style.animation = 'slideInUp 0.3s ease-out';

        // Setup field interactions
        this.setupFieldInteractions(field);
        
        // Update form schema
        this.updateFormSchema();
    }

    getFieldInput(fieldType) {
        const inputs = {
            'text': '<input type="text" class="form-control form-field-input" placeholder="Enter text...">',
            'number': '<input type="number" class="form-control form-field-input" placeholder="Enter number...">',
            'email': '<input type="email" class="form-control form-field-input" placeholder="Enter email...">',
            'textarea': '<textarea class="form-control form-field-input" rows="3" placeholder="Enter text..."></textarea>',
            'select': '<select class="form-control form-field-input"><option>Option 1</option><option>Option 2</option></select>',
            'checkbox': '<div><label><input type="checkbox"> Option 1</label><label><input type="checkbox"> Option 2</label></div>',
            'radio': '<div><label><input type="radio" name="radio"> Option 1</label><label><input type="radio" name="radio"> Option 2</label></div>',
            'date': '<input type="date" class="form-control form-field-input">',
        };
        
        return inputs[fieldType] || inputs['text'];
    }

    setupFieldInteractions(field) {
        // Drag reordering
        field.addEventListener('dragstart', (e) => {
            this.draggingElement = field;
            e.dataTransfer.effectAllowed = 'move';
            field.classList.add('dragging');
        });

        field.addEventListener('dragend', () => {
            field.classList.remove('dragging');
        });

        // Hover effects
        field.addEventListener('mouseenter', () => {
            field.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.2)';
        });

        field.addEventListener('mouseleave', () => {
            field.style.boxShadow = '';
        });
    }

    deleteField(button) {
        const field = button.closest('.form-field');
        
        // Add fade animation before removal
        field.style.animation = 'fadeOut 0.3s ease-out forwards';
        
        setTimeout(() => {
            field.remove();
            this.updateFormSchema();
        }, 300);
    }

    editField(button) {
        const field = button.closest('.form-field');
        field.classList.add('editing');
        
        // Show edit mode
        const inputs = field.querySelectorAll('input[type="text"], textarea, select');
        inputs[0]?.focus();
    }

    updateFormSchema() {
        const fields = document.querySelectorAll('.form-field');
        const schema = Array.from(fields).map(field => ({
            id: field.dataset.fieldId,
            type: this.getFieldType(field),
            label: field.querySelector('[placeholder*="Label"]')?.value || 'Field',
            required: field.querySelector('input[type="checkbox"]')?.checked || false,
        }));
        
        // Update hidden input or send to server
        const schemaInput = document.querySelector('input[name="FormSchema"]');
        if (schemaInput) {
            schemaInput.value = JSON.stringify(schema);
        }
    }

    getFieldType(field) {
        const inputs = [
            'text', 'number', 'email', 'textarea', 'select', 
            'checkbox', 'radio', 'date', 'file'
        ];
        
        for (let type of inputs) {
            if (field.querySelector(`input[type="${type}"], ${type}`)) {
                return type;
            }
        }
        
        return 'text';
    }

    // ====== FORM PREVIEW ======

    setupFormPreview() {
        const canvas = document.querySelector('.form-builder-canvas');
        
        if (canvas) {
            // Show empty state
            if (canvas.querySelector('.form-preview').children.length === 0) {
                this.showEmptyState(canvas.querySelector('.form-preview'));
            }
        }
    }

    showEmptyState(container) {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-canvas animate-fade-in';
        emptyState.innerHTML = `
            <div class="empty-canvas-icon">📋</div>
            <div class="empty-canvas-text">No Fields Yet</div>
            <div class="empty-canvas-hint">Drag field types from the left to create your form</div>
        `;
        
        container.appendChild(emptyState);
    }

    // ====== ANIMATIONS ======

    setupAnimations() {
        // Intersection observer for fade-in animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.card, .form-card, .stat-card').forEach(el => {
            observer.observe(el);
        });

        // Stagger animations for list items
        this.staggerElements('.form-card', 0.1);
        this.staggerElements('.stat-card', 0.1);
    }

    staggerElements(selector, delay) {
        const elements = document.querySelectorAll(selector);
        elements.forEach((el, index) => {
            el.style.animation = `slideInUp 0.4s ease-out ${index * delay}s both`;
        });
    }

    // ====== DOM OBSERVER ======

    observeDOM() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList && node.classList.contains('form-field')) {
                                this.setupFieldInteractions(node);
                            }
                        }
                    });
                }
            });
        });

        const formPreview = document.querySelector('.form-preview');
        if (formPreview) {
            observer.observe(formPreview, {
                childList: true,
                subtree: true
            });
        }
    }
}

// ====== SMOOTH SCROLLING ======

class SmoothScroller {
    constructor() {
        this.setupSmoothScroll();
    }

    setupSmoothScroll() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    }
}

// ====== FORM INTERACTIONS ======

class FormInteractions {
    constructor() {
        this.init();
    }

    init() {
        this.setupFormHover();
        this.setupInputAnimations();
        this.setupValidation();
        this.setupSubmitAnimation();
    }

    setupFormHover() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('mouseenter', () => {
                form.style.boxShadow = 'var(--shadow-lg)';
            });
            form.addEventListener('mouseleave', () => {
                form.style.boxShadow = '';
            });
        });
    }

    setupInputAnimations() {
        const inputs = document.querySelectorAll('.form-control, .form-field-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'scale(1)';
            });
        });
    }

    setupValidation() {
        const form = document.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const inputs = form.querySelectorAll('[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    this.showFieldError(input);
                }
            });

            if (!isValid) {
                e.preventDefault();
                this.showValidationError();
            }
        });
    }

    showFieldError(input) {
        input.classList.add('is-invalid');
        input.style.borderColor = 'var(--danger)';
        input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';

        setTimeout(() => {
            input.classList.remove('is-invalid');
            input.style.borderColor = '';
            input.style.boxShadow = '';
        }, 3000);
    }

    showValidationError() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger animate-slide-in-up';
        alert.innerHTML = '⚠️ Please fill in all required fields';
        
        const form = document.querySelector('form');
        form.parentElement.insertBefore(alert, form);

        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    }

    setupSubmitAnimation() {
        const submitBtns = document.querySelectorAll('button[type="submit"]');
        submitBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const originalText = btn.textContent;
                btn.innerHTML = '<span class="loading-spinner" style="width: 16px; height: 16px; border-width: 2px;"></span> Saving...';
                btn.disabled = true;

                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 1000);
            });
        });
    }
}

// ====== MODAL ANIMATIONS ======

class ModalManager {
    constructor() {
        this.setupModals();
    }

    setupModals() {
        document.addEventListener('show.bs.modal', (e) => {
            e.target.style.animation = 'fadeIn 0.3s ease-out';
        });

        document.addEventListener('hide.bs.modal', (e) => {
            e.target.style.animation = 'fadeOut 0.3s ease-out';
        });
    }
}

// ====== PAGE TRANSITIONS ======

class PageTransitions {
    constructor() {
        this.setupPageLoad();
        this.setupPageExit();
    }

    setupPageLoad() {
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.animation = 'fadeIn 0.5s ease-out';
            
            // Add entrance animations to elements
            const elements = document.querySelectorAll('[data-animate]');
            elements.forEach((el, idx) => {
                el.style.animation = `${el.dataset.animate} 0.5s ease-out ${idx * 0.1}s both`;
            });
        });
    }

    setupPageExit() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a:not([href^="#"]):not([target="_blank"])');
            if (link && !link.href.includes('javascript:')) {
                // Optional: Add exit animation
                // document.body.style.animation = 'fadeOut 0.3s ease-out';
            }
        });
    }
}

// ====== NOTIFICATIONS ======

class NotificationManager {
    static show(message, type = 'info', duration = 3000) {
        const alertClass = `alert-${type}`;
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} animate-slide-in-up`;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';
        alert.innerHTML = `<strong>${type.toUpperCase()}:</strong> ${message}`;

        document.body.appendChild(alert);

        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(() => alert.remove(), 300);
        }, duration);
    }
}

// ====== INITIALIZE ON PAGE LOAD ======

document.addEventListener('DOMContentLoaded', () => {
    // Global form builder instance
    window.formBuilder = new FormBuilderUI();
    
    // Other services
    new SmoothScroller();
    new FormInteractions();
    new ModalManager();
    new PageTransitions();

    // Add fade out animation keyframes if not present
    if (!document.querySelector('style#animations')) {
        const style = document.createElement('style');
        style.id = 'animations';
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
});

// Expose notification manager globally
window.notify = NotificationManager;
