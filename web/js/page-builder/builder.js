/**
 * MAIN BUILDER - Mengintegrasikan semua komponen
 */

class PageBuilder {
    constructor(config = {}) {
        this.config = config;
        this.selectedNodeId = null;

        // Initialize components
        window.renderEngine = new RenderEngine('builder-canvas');
        window.propsPanel = new PropertiesPanel('properties-panel');
        window.formBuilder = new FormBuilder('form-builder-panel');

        this.init();
    }

    /**
     * Initialize builder
     */
    init() {
        // Subscribe to state changes
        window.pageState.subscribe((state) => {
            this.onStateChange(state);
        });

        // Listen for node selection
        window.addEventListener('nodeSelected', (e) => {
            this.onNodeSelected(e.detail.nodeId);
        });

        // Load initial data jika ada
        if (this.config.initialData) {
            try {
                const state = typeof this.config.initialData === 'string'
                    ? JSON.parse(this.config.initialData)
                    : this.config.initialData;
                window.pageState.setState(state);
            } catch (e) {
                console.error('Failed to load initial data:', e);
            }
        }

        this.setupUI();
    }

    /**
     * Setup builder UI
     */
    setupUI() {
        // Setup component library
        this.setupComponentLibrary();

        // Setup toolbar
        this.setupToolbar();

        // Render initial
        window.renderEngine.render(window.pageState.getState());
    }

    /**
     * Setup component library (left panel)
     */
    setupComponentLibrary() {
        const libContainer = document.getElementById('component-library');
        if (!libContainer) return;

        libContainer.innerHTML = '<h5>Components</h5>';

        const categories = ['layout', 'content', 'advanced'];
        categories.forEach(category => {
            const components = getComponentsByCategory(category);
            
            const categorySection = document.createElement('div');
            categorySection.className = 'mb-3';

            const categoryTitle = document.createElement('h6');
            categoryTitle.textContent = this.getCategoryName(category);
            categoryTitle.className = 'text-muted small';
            categorySection.appendChild(categoryTitle);

            components.forEach(component => {
                const btn = document.createElement('button');
                btn.className = 'btn btn-outline-secondary btn-sm d-block w-100 text-start mb-2';
                btn.innerHTML = `${component.icon} ${component.name}`;
                btn.onclick = () => this.addComponent(component.type);
                categorySection.appendChild(btn);
            });

            libContainer.appendChild(categorySection);
        });
    }

    /**
     * Setup toolbar
     */
    setupToolbar() {
        const toolbar = document.getElementById('builder-toolbar');
        if (!toolbar) return;

        toolbar.innerHTML = `
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.pageBuilder.undo()" title="Undo">
                    ↶ Undo
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.pageBuilder.redo()" title="Redo">
                    ↷ Redo
                </button>
            </div>

            <div class="ms-2 btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="window.pageBuilder.savePage()" title="Save">
                    💾 Save
                </button>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="window.pageBuilder.previewPage()" title="Preview">
                    👁 Preview
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.pageBuilder.exportJSON()" title="Export">
                    ⬇ Export
                </button>
            </div>
        `;
    }

    /**
     * Add component
     */
    addComponent(type) {
        const selectedNodeId = window.renderEngine.selectedNodeId;
        const node = createNode(type);
        
        if (node) {
            window.pageState.addNode(selectedNodeId || null, node);
        }
    }

    /**
     * Handle state change
     */
    onStateChange(state) {
        window.renderEngine.render(state);
    }

    /**
     * Handle node selection
     */
    onNodeSelected(nodeId) {
        this.selectedNodeId = nodeId;
        window.propsPanel.showProperties(nodeId);
    }

    /**
     * Undo
     */
    undo() {
        window.pageState.undo();
    }    /**
     * Redo
     */
    redo() {
        window.pageState.redo();
    }

    /**
     * Save page (for create mode)
     */
    savePage() {
        const state = window.pageState.getState();
        const stateJSON = JSON.stringify(state);

        // Get title and slug from input
        const title = document.querySelector('input[name="title"]')?.value || prompt('Enter page title:');
        if (!title) return;

        const slug = document.querySelector('input[name="slug"]')?.value || this.generateSlug(title);

        // Submit ke backend
        const form = document.getElementById('page-save-form');
        if (form) {
            document.getElementById('save-title').value = title;
            document.getElementById('save-slug').value = slug;
            document.getElementById('save-content').value = stateJSON;
            form.submit();
        }
    }

    /**
     * Save page update (for update mode)
     */
    savePageUpdate() {
        const state = window.pageState.getState();
        const stateJSON = JSON.stringify(state);
        const pageId = this.config.pageId;

        if (!pageId) {
            alert('Page ID not set');
            return;
        }

        const form = document.getElementById('page-save-form');
        if (form) {
            document.getElementById('save-content').value = stateJSON;
            form.submit();
        }
    }

    /**
     * Generate slug dari title
     */
    generateSlug(title) {
        return title
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /**
     * Save via AJAX
     */
    saveViaAjax(pageId, stateJSON) {
        fetch(`/master-page/save/${pageId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                content: stateJSON,
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Page saved successfully!');
            } else {
                alert('Error saving page: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            alert('Error saving page');
        });
    }

    /**
     * Preview page
     */
    previewPage() {
        const state = window.pageState.getState();
        const html = this.generatePreviewHTML(state);

        const previewWindow = window.open('', 'preview', 'width=1200,height=800');
        previewWindow.document.write(html);
        previewWindow.document.close();
    }

    /**
     * Generate preview HTML
     */
    generatePreviewHTML(state) {
        const stateJSON = JSON.stringify(state);

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
                    .page-section { padding: 20px; }
                    .page-row { display: flex; gap: 10px; }
                    .page-column { flex: 1; }
                </style>
            </head>
            <body class="bg-light">
                <div id="page-content"></div>

                <script>
                    // Render frontend
                    const state = ${stateJSON};
                    const FrontendRenderer = ${FrontendRenderer.toString()};
                    FrontendRenderer.render(state, 'page-content');
                </script>
            </body>
            </html>
        `;
    }

    /**
     * Export JSON
     */
    exportJSON() {
        const state = window.pageState.getState();
        const json = JSON.stringify(state, null, 2);
        
        // Trigger download
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'page-builder-export.json';
        a.click();
        URL.revokeObjectURL(url);
    }

    /**
     * Helper: Get category name
     */
    getCategoryName(category) {
        const names = {
            layout: 'Layout',
            content: 'Content',
            advanced: 'Advanced',
        };
        return names[category] || category;
    }
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Will be initialized by view with config
    // window.pageBuilder = new PageBuilder(config);
});
