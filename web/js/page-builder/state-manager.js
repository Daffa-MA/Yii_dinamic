/**
 * STATE MANAGER - Single Source of Truth
 * 
 * Semua halaman dikontrol oleh state JavaScript
 * Bukan HTML statis, semua dynamic
 */

class StateManager {
    constructor(initialState = []) {
        this.state = initialState;
        this.listeners = [];
        this.history = [JSON.parse(JSON.stringify(initialState))];
        this.historyIndex = 0;
    }

    /**
     * Get current state
     */
    getState() {
        return this.state;
    }

    /**
     * Update state
     */
    setState(newState) {
        this.state = JSON.parse(JSON.stringify(newState));
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Add node ke state
     */
    addNode(parentId, node) {
        if (!parentId) {
            // Root level
            this.state.push(node);
        } else {
            // Nested
            this.findAndUpdate(this.state, parentId, (parent) => {
                if (!parent.children) parent.children = [];
                parent.children.push(node);
            });
        }
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Update node properties
     */
    updateNode(nodeId, updates) {
        this.findAndUpdate(this.state, nodeId, (node) => {
            Object.assign(node.props = node.props || {}, updates);
        });
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Delete node
     */
    deleteNode(nodeId) {
        this.deleteRecursive(this.state, nodeId);
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Reorder nodes
     */
    reorderNodes(parentId, newOrder) {
        if (!parentId) {
            // Root level
            this.state = newOrder;
        } else {
            this.findAndUpdate(this.state, parentId, (parent) => {
                parent.children = newOrder;
            });
        }
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Move node antar parent
     */
    moveNode(nodeId, newParentId) {
        let nodeToMove = null;
        
        // Find and remove from current parent
        this.findParent(this.state, nodeId, (parent, index) => {
            nodeToMove = parent.children.splice(index, 1)[0];
        });
        
        if (!nodeToMove) return;
        
        // Add ke parent baru
        if (!newParentId) {
            this.state.push(nodeToMove);
        } else {
            this.findAndUpdate(this.state, newParentId, (parent) => {
                if (!parent.children) parent.children = [];
                parent.children.push(nodeToMove);
            });
        }
        
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Update form fields
     */
    updateFormFields(formId, fields) {
        this.findAndUpdate(this.state, formId, (node) => {
            if (node.type === 'form') {
                node.fields = fields;
            }
        });
        this.addToHistory();
        this.notifyListeners();
    }

    /**
     * Helper: Find node and update
     */
    findAndUpdate(nodes, nodeId, callback) {
        for (let node of nodes) {
            if (node.id === nodeId) {
                callback(node);
                return true;
            }
            if (node.children && this.findAndUpdate(node.children, nodeId, callback)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper: Find parent of node
     */
    findParent(nodes, nodeId, callback) {
        for (let node of nodes) {
            if (node.children) {
                const index = node.children.findIndex(child => child.id === nodeId);
                if (index !== -1) {
                    callback(node, index);
                    return true;
                }
                if (this.findParent(node.children, nodeId, callback)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Helper: Delete node recursively
     */
    deleteRecursive(nodes, nodeId) {
        for (let i = 0; i < nodes.length; i++) {
            if (nodes[i].id === nodeId) {
                nodes.splice(i, 1);
                return true;
            }
            if (nodes[i].children && this.deleteRecursive(nodes[i].children, nodeId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Undo
     */
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.state = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.notifyListeners();
        }
    }

    /**
     * Redo
     */
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.state = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.notifyListeners();
        }
    }

    /**
     * Add to history
     */
    addToHistory() {
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(JSON.parse(JSON.stringify(this.state)));
        this.historyIndex++;
    }

    /**
     * Subscribe to changes
     */
    subscribe(callback) {
        this.listeners.push(callback);
        return () => {
            this.listeners = this.listeners.filter(l => l !== callback);
        };
    }

    /**
     * Notify all listeners
     */
    notifyListeners() {
        this.listeners.forEach(listener => listener(this.state));
    }

    /**
     * Export state as JSON
     */
    export() {
        return JSON.stringify(this.state, null, 2);
    }

    /**
     * Import state dari JSON
     */
    import(jsonString) {
        try {
            this.setState(JSON.parse(jsonString));
            return true;
        } catch (e) {
            console.error('Invalid JSON:', e);
            return false;
        }
    }
}

// Global instance
window.pageState = new StateManager();
