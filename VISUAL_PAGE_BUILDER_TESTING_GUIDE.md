# Dynamic Visual Page Builder - Testing & Setup Guide

**Date:** May 6, 2026  
**Status:** ✅ COMPLETE & READY FOR TESTING  
**Architecture:** Single Source of Truth (JavaScript State)

---

## 🚀 Quick Start

### 1. Access the Visual Builder

#### Create New Page
- Navigate to: `/master-page/visual-create`
- Or click "Create with Visual Builder" in Master Pages list

#### Edit Existing Page
- Navigate to: `/master-page/visual-update/{page-id}`
- Or click "Edit" button on a page then "Edit in Visual Builder"

---

## 📋 Features Overview

### ✅ Implemented

1. **Drag & Drop Components**
   - Layout: Section, Row, Column
   - Content: Text, Heading, Image, Button
   - Advanced: Form
   - All components fully draggable

2. **Live Editing**
   - Real-time property panel
   - Type-specific editors for each component
   - Color picker, text input, dropdown controls
   - Immediate visual feedback

3. **Form Builder**
   - Dynamic field addition
   - Supported types: input, email, textarea, select, checkbox
   - Field customization: label, name, placeholder, options

4. **State Management**
   - Undo/Redo functionality
   - History tracking
   - JSON export/import

5. **Save & Persistence**
   - Save to database
   - Load existing layouts
   - JSON storage in `layout_json` field

---

## 🧪 Testing Scenarios

### Test 1: Create New Page with Visual Builder

**Steps:**
1. Go to `/master-page/index`
2. Click "Create with Visual Builder" or go to `/master-page/visual-create`
3. Add components:
   - Drag "Section" from left panel to canvas
   - Drag "Heading" into the section
   - Drag "Text" component
   - Drag "Button" component
4. Edit properties:
   - Select Heading, change text to "Welcome"
   - Select Button, change text to "Click Me", set link to "#"
5. Click "Save Page"
6. Enter page title and slug
7. Verify page is created in `/master-page/index`

**Expected Result:** ✅ Page created with layout saved as JSON

---

### Test 2: Edit Existing Page

**Steps:**
1. Go to `/master-page/index`
2. Find a page and click "Edit"
3. Click "Edit in Visual Builder" or go to `/master-page/visual-update/{id}`
4. Modify existing components
5. Add new components
6. Click "Update Page"

**Expected Result:** ✅ Page layout updated with new components

---

### Test 3: Drag & Drop Functionality

**Steps:**
1. Create a page with Section containing Text and Heading
2. Try dragging Text component inside Heading
3. Try dragging Heading to a different section
4. Try deleting components

**Expected Result:**
- ✅ Components draggable between valid containers
- ✅ Invalid drops prevented (can't nest text in text)
- ✅ Delete functionality works

---

### Test 4: Undo/Redo

**Steps:**
1. Create page with multiple components
2. Add a component, then click Undo button
3. Verify component is removed
4. Click Redo button
5. Verify component is restored

**Expected Result:** ✅ Undo/Redo history works properly

---

### Test 5: Form Builder

**Steps:**
1. Add a Form component to page
2. Select the form in properties panel
3. Click "Edit Form Fields"
4. Add field: Text input named "name"
5. Add field: Email input named "email"
6. Add field: Select with options (Option 1, Option 2)
7. Save form
8. Verify fields appear in canvas

**Expected Result:** ✅ Form fields display properly

---

### Test 6: Property Editing

**Steps:**
1. Add Heading to page
2. Select heading, see properties panel
3. Change text, font size, color
4. Verify changes immediately on canvas
5. Change background color of Section
6. Verify visual update

**Expected Result:** ✅ All properties update in real-time

---

### Test 7: Preview Mode

**Steps:**
1. Create page with components
2. Click "Preview" button (if available)
3. New window opens showing page
4. Verify layout looks correct without editor controls

**Expected Result:** ✅ Preview shows clean page without editor UI

---

### Test 8: Export JSON

**Steps:**
1. Create page with components
2. Click "Export" button
3. File downloads: `page-builder-export.json`
4. Open file and verify it contains valid JSON

**Expected Result:** ✅ Valid JSON exported with all components

---

## 🔧 File Structure

```
web/
├── js/page-builder/
│   ├── state-manager.js         ← State management
│   ├── component-library.js     ← Component definitions
│   ├── render-engine.js         ← Render to DOM
│   ├── properties-panel.js      ← Property editor
│   ├── form-builder.js          ← Form field builder
│   ├── frontend-renderer.js     ← Final output rendering
│   └── builder.js               ← Main coordinator
│
└── css/
    └── page-builder.css         ← Styling

assets/
└── PageBuilderAsset.php         ← Asset bundle

views/master-page/
├── visual-create.php            ← Create builder view
├── visual-update.php            ← Update builder view
├── builder.php                  ← Legacy builder view
└── visual-builder.php           ← Generic builder view

controllers/
└── MasterPageController.php     ← Backend actions
    ├── actionVisualCreate()     ← Create page
    ├── actionVisualUpdate()     ← Edit page
    ├── actionVisualSave()       ← AJAX save
    └── actionVisualCreateSubmit() ← Form submit
```

---

## 🔌 Database Schema

```sql
master_page table:
- id (INT PK)
- title (VARCHAR 255)
- slug (VARCHAR 255)
- layout (VARCHAR 255)
- layout_json (LONGTEXT)  ← Builder state stored here
- description (TEXT)
- is_active (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

**Note:** The `layout_json` field stores the complete page builder state as JSON.

---

## 🎯 Component Types

### Layout Components
- **Section** - Container for organizing content
- **Row** - Horizontal flex container
- **Column** - Vertical section within row

### Content Components
- **Text** - Paragraph text
- **Heading** - Heading (h1-h6)
- **Image** - Image with src, alt, dimensions
- **Button** - Clickable button with link

### Advanced Components
- **Form** - Dynamic form with fields

---

## 🛠️ Common Tasks

### Add New Component Type

1. **Update `component-library.js`:**
```javascript
customComponent: {
    name: 'Custom',
    icon: '🎨',
    category: 'advanced',
    defaultProps: { /* ... */ },
    canHaveChildren: false,
}
```

2. **Update `render-engine.js`:**
```javascript
case 'customComponent':
    // Render logic
    break;
```

3. **Update `frontend-renderer.js`:**
```javascript
case 'customComponent':
    // Frontend render logic
    break;
```

4. **Update `properties-panel.js`:**
```javascript
case 'customComponent':
    html += this.renderCustomProperties(node);
    break;
```

### Change Styling

Edit `/web/css/page-builder.css` for:
- 3-panel layout dimensions
- Canvas styling
- Component appearance
- Responsive design

### Modify Save Behavior

Edit `builder.js` methods:
- `savePage()` - Create mode
- `savePageUpdate()` - Update mode
- `saveViaAjax()` - Alternative save

---

## 🐛 Troubleshooting

### Issue: Components not appearing on canvas
**Solution:** Check browser console for JavaScript errors. Ensure PageBuilderAsset is registered.

### Issue: Drag & drop not working
**Solution:** Verify `render-engine.js` event listeners are attached. Check for JavaScript errors.

### Issue: Properties not updating
**Solution:** Ensure node is properly selected. Check PropertiesPanel update logic.

### Issue: Save fails silently
**Solution:** Check browser console and network tab. Verify controller action exists and returns JSON.

### Issue: Page doesn't load existing layout
**Solution:** Verify `layout_json` field in database contains valid JSON. Check PHP errorlog.

---

## 📊 Performance Notes

- **Large pages:** Monitor browser memory with 50+ components
- **History size:** Undo/redo stores entire state snapshots
- **Real-time updates:** Throttle frequent updates if needed

---

## 🔐 Security Considerations

- ✅ CSRF token protection
- ✅ Server-side validation on save
- ✅ JSON content sanitized on display
- ✅ HTML content escaped in frontend

---

## 📱 Browser Compatibility

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

---

## 🎓 Next Steps

1. **Run Tests:** Follow testing scenarios above
2. **Verify Integration:** Check create/update workflows
3. **Customize:** Add custom components as needed
4. **Deploy:** Copy all files to production
5. **Monitor:** Check logs for any errors

---

## 📞 Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Check PHP error log for backend errors
3. Verify all JavaScript files are loaded
4. Ensure PageBuilderAsset is registered in view
5. Check database `layout_json` field for valid JSON

---

**Status:** ✅ Ready for Production Testing
