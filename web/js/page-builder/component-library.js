/**
 * COMPONENT LIBRARY
 * 
 * Mendefinisikan semua tipe komponen yang tersedia
 */

const COMPONENT_LIBRARY = {
    // Layout Components
    section: {
        name: 'Section',
        icon: '📦',
        category: 'layout',
        defaultProps: {
            backgroundColor: '#ffffff',
            padding: '20px',
        },
        canHaveChildren: true,
    },
    row: {
        name: 'Row',
        icon: '↔️',
        category: 'layout',
        defaultProps: {
            display: 'flex',
            gap: '10px',
        },
        canHaveChildren: true,
    },
    column: {
        name: 'Column',
        icon: '⬇️',
        category: 'layout',
        defaultProps: {
            flex: 1,
        },
        canHaveChildren: true,
    },

    // Content Components
    text: {
        name: 'Text',
        icon: '📝',
        category: 'content',
        defaultProps: {
            content: 'Enter text here',
            fontSize: '16px',
            color: '#000000',
        },
        canHaveChildren: false,
    },
    heading: {
        name: 'Heading',
        icon: '📌',
        category: 'content',
        defaultProps: {
            content: 'Heading',
            level: 'h2',
            color: '#333333',
        },
        canHaveChildren: false,
    },
    image: {
        name: 'Image',
        icon: '🖼️',
        category: 'content',
        defaultProps: {
            src: '/images/placeholder.png',
            alt: 'Image',
            width: '300px',
            height: '200px',
        },
        canHaveChildren: false,
    },
    button: {
        name: 'Button',
        icon: '🔘',
        category: 'content',
        defaultProps: {
            text: 'Click me',
            link: '',
            backgroundColor: '#007bff',
            color: '#ffffff',
            padding: '10px 20px',
        },
        canHaveChildren: false,
    },

    // Advanced Components
    form: {
        name: 'Form',
        icon: '📋',
        category: 'advanced',
        defaultProps: {
            action: '/submit',
            method: 'POST',
        },
        fields: [],
        canHaveChildren: false,
    },
    custom: {
        name: 'Custom Component',
        icon: '⚙️',
        category: 'advanced',
        defaultProps: {
            componentName: 'CustomComponent',
            data: {},
        },
        canHaveChildren: true,
    },
};

/**
 * Get component definition
 */
function getComponentDef(type) {
    return COMPONENT_LIBRARY[type] || null;
}

/**
 * Get all components
 */
function getAllComponents() {
    return COMPONENT_LIBRARY;
}

/**
 * Get components by category
 */
function getComponentsByCategory(category) {
    return Object.entries(COMPONENT_LIBRARY)
        .filter(([_, def]) => def.category === category)
        .map(([type, def]) => ({ type, ...def }));
}

/**
 * Create new node
 */
function createNode(type, overrides = {}) {
    const def = getComponentDef(type);
    if (!def) {
        console.error(`Unknown component type: ${type}`);
        return null;
    }

    const node = {
        id: `${type}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        type,
        props: { ...def.defaultProps, ...overrides },
    };

    if (def.canHaveChildren) {
        node.children = [];
    }

    if (type === 'form' && def.fields) {
        node.fields = def.fields;
    }

    return node;
}

/**
 * Validate component can have children
 */
function canHaveChildren(type) {
    const def = getComponentDef(type);
    return def && def.canHaveChildren;
}
