# 🎨 Dynamic Visual Page Builder - Dokumentasi

**Status:** ✅ COMPLETE & PRODUCTION READY  
**Architecture:** Single Source of Truth (JavaScript State)  
**Scope:** `/master-page` only (ISOLATED)

---

## 🎯 Overview

Sistem **Dynamic Visual Page Builder** yang menggunakan konsep **Puck Editor** untuk membuat halaman secara visual dengan:

✅ Drag & drop interface  
✅ Live preview editing  
✅ Component library  
✅ Form builder dinamis  
✅ Property editor real-time  
✅ Undo/Redo functionality  
✅ Save & export  

---

## 🏗️ Arsitektur

### Single Source of Truth (State)

```javascript
// State adalah single source of truth
let pageState = [
  {
    id: "section-1",
    type: "section",
    props: { backgroundColor: "#fff" },
    children: [
      {
        id: "heading-1",
        type: "heading",
        props: { content: "Judul", level: "h2" }
      },
      {
        id: "form-1",
        type: "form",
        props: { action: "/submit", method: "POST" },
        fields: [
          { id: "f1", type: "input", label: "Nama" }
        ]
      }
    ]
  }
];
```

**Filosofi:**
- ❌ NO hardcoded HTML
- ❌ NO static templates
- ✅ State → Render → DOM
- ✅ Change state → Auto re-render

---

## 📁 File Structure

```
project/
├── assets/
│   └── PageBuilderAsset.php          (Register JS/CSS)
│
├── web/
│   ├── js/page-builder/
│   │   ├── state-manager.js          (State management)
│   │   ├── component-library.js      (Component definitions)
│   │   ├── render-engine.js          (Builder render)
│   │   ├── properties-panel.js       (Property editor)
│   │   ├── form-builder.js           (Form fields editor)
│   │   ├── frontend-renderer.js      (Frontend render)
│   │   └── builder.js                (Main coordinator)
│   │
│   └── css/
│       └── page-builder.css          (Styling)
│
├── views/master-page/
│   └── visual-builder.php            (Builder UI)
│
└── controllers/
    └── MasterPageController.php      (Handle save/load)
```

---

## 🔄 Data Flow

```
┌─────────────────────────────────────────────────┐
│                  USER ACTION                    │
│  (Drag, Drop, Edit, Delete, Add)               │
└────────────────┬────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────┐
│               STATE MANAGER                     │
│  Update state → notify listeners               │
└────────────────┬────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────┐
│            RENDER ENGINE                        │
│  Loop state → create DOM nodes                 │
│  Apply styles → attach listeners               │
└────────────────┬────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────┐
│             BUILDER CANVAS                      │
│  Display with drag/drop, selection, controls   │
└─────────────────────────────────────────────────┘

SAVE:  state → JSON → Backend → Database
LOAD:  Database → JSON → state → Render
PREVIEW: state → FrontendRenderer → HTML
```

---

## 🧩 Component Types

### Layout Components
```javascript
{
  section: { canHaveChildren: true },
  row: { canHaveChildren: true },
  column: { canHaveChildren: true }
}
```

### Content Components
```javascript
{
  text: { content: "text", fontSize: "16px", color: "#000" },
  heading: { content: "heading", level: "h2" },
  image: { src: "url", alt: "text", width: "300px" },
  button: { text: "click", link: "#", backgroundColor: "#007bff" }
}
```

### Advanced Components
```javascript
{
  form: {
    action: "/submit",
    method: "POST",
    fields: []  // Dinamis
  }
}
```

---

## 💾 JavaScript Classes

### 1. StateManager
**Fungsi:** Manage state dengan subscribe/notify pattern

```javascript
const state = new StateManager([]);

// Add node
state.addNode(parentId, node);

// Update properties
state.updateNode(nodeId, { color: "red" });

// Delete node
state.deleteNode(nodeId);

// Move node
state.moveNode(nodeId, newParentId);

// Subscribe to changes
state.subscribe((newState) => {
  console.log('State changed:', newState);
});

// Undo/Redo
state.undo();
state.redo();
```

### 2. RenderEngine
**Fungsi:** Render state menjadi DOM untuk editor

```javascript
const engine = new RenderEngine('container-id');

// Render dari state
engine.render(state);

// Drag & drop support
// - onDragStart
// - onDragOver
// - onDrop
```

### 3. PropertiesPanel
**Fungsi:** Edit properties dari node terpilih

```javascript
const props = new PropertiesPanel('panel-id');

// Show properties untuk node
props.showProperties(nodeId);

// Auto-update saat input berubah
```

### 4. FormBuilder
**Fungsi:** Edit form fields secara dinamis

```javascript
const fb = new FormBuilder('panel-id');

// Show form editor
fb.showFormBuilder(formId);

// Add/Edit/Remove fields
// Supported types: input, email, textarea, select, checkbox
```

### 5. PageBuilder
**Fungsi:** Koordinator utama, mengintegrasikan semua komponen

```javascript
const builder = new PageBuilder(config);

// Add component
builder.addComponent('heading');

// Save halaman
builder.savePage();

// Preview
builder.previewPage();

// Export JSON
builder.exportJSON();
```

---

## 🎨 UI Layout

### 3-Panel Layout

```
┌─────────────────────────────────────────────────────────┐
│                      TOOLBAR                           │
│  [Undo] [Redo] | [Save] [Preview] [Export]            │
├──────────┬────────────────────────┬────────────────────┤
│ LIBRARY  │     CANVAS             │   PROPERTIES       │
│          │   (drag & drop)        │   (edit props)     │
│ Section  │   ┌──────────────────┐ │ ┌────────────────┐ │
│ Row      │   │                  │ │ │ Select a       │ │
│ Column   │   │    Page Content  │ │ │ component to   │ │
│ -------- │   │                  │ │ │ edit            │ │
│ Text     │   │ (rendered state) │ │ │ ───────────    │ │
│ Heading  │   │                  │ │ │ Component Name │ │
│ Image    │   └──────────────────┘ │ │ Color: ▮▮▮▮▮ │ │
│ Button   │                         │ │ Font Size: 16px│ │
│ -------- │                         │ │ ───────────    │ │
│ Form     │                         │ │ [Save Field]   │ │
│          │                         │ │ [Cancel]       │ │
└──────────┴────────────────────────┴────────────────────┘
```

### Left Panel (Component Library)
- Kategorisasi: Layout, Content, Advanced
- Drag ke canvas untuk add
- Click untuk select parent

### Center Panel (Canvas)
- Render state menjadi DOM
- Drag nodes untuk reorder/move
- Click untuk select
- Hover untuk show controls

### Right Panel (Properties)
- Edit props dari selected node
- Auto-update state
- Form field editor jika form
- Real-time preview

---

## 💡 How to Use

### 1. Membuat Page Baru

```bash
GET /master-page/visual-builder
```

UI akan menampilkan:
- Empty canvas
- Component library
- Properties panel

### 2. Add Component

```
Click component di library
→ Select parent (atau root)
→ Component ditambahkan ke state
→ Canvas re-render
```

### 3. Edit Component

```
Click component di canvas
→ Properties panel update
→ Edit properties
→ State auto-update
→ Canvas re-render
```

### 4. Add Form

```
Add form component
→ Right panel show "Edit Fields"
→ Click "Edit Fields"
→ Form builder modal/panel
→ Add fields (input, email, textarea, select, checkbox)
→ Save
```

### 5. Save Page

```
Click "Save" button
→ state di-stringify jadi JSON
→ POST ke backend
→ Backend save ke database (master_page.content)
→ Redirect to view
```

### 6. Preview

```
Click "Preview" button
→ Open new window
→ Frontend renderer render state jadi HTML
→ Display preview
```

---

## 🔌 Backend Integration

### Controller Method

```php
// MasterPageController.php

public function actionVisualBuilder()
{
    $model = new MasterPage();
    
    return $this->render('visual-builder', [
        'model' => $model,
    ]);
}

public function actionSave($id)
{
    $model = MasterPage::findOne($id);
    $content = Yii::$app->request->post('content');
    
    $model->content = $content; // JSON dari builder state
    
    if ($model->save()) {
        return ['success' => true];
    }
    return ['success' => false, 'message' => implode(', ', array_values($model->errors))];
}
```

### Database Schema

```sql
master_page:
  id
  title
  slug
  content (TEXT) ← JSON dari state
  status
  created_at
  updated_at
```

---

## 📦 Export/Import

### Export to JSON

```javascript
// Auto-generated JSON file
{
  "version": "1.0",
  "createdAt": "2024-01-01",
  "state": [
    { id: "...", type: "...", ... }
  ]
}
```

### Import from JSON

```javascript
// Parse JSON → set state → render
const json = JSON.parse(fileContent);
pageState.setState(json.state);
```

---

## 🎯 Examples

### Example 1: Simple Page

```javascript
[
  {
    id: "sec1",
    type: "section",
    props: { backgroundColor: "#fff" },
    children: [
      {
        id: "h1",
        type: "heading",
        props: { content: "Welcome", level: "h1" }
      },
      {
        id: "p1",
        type: "text",
        props: { content: "This is a simple page" }
      }
    ]
  }
]
```

### Example 2: Page with Form

```javascript
[
  {
    id: "sec1",
    type: "section",
    children: [
      {
        id: "form1",
        type: "form",
        props: { action: "/contact", method: "POST" },
        fields: [
          { id: "f1", type: "input", label: "Name", name: "name" },
          { id: "f2", type: "email", label: "Email", name: "email" },
          { id: "f3", type: "textarea", label: "Message", name: "msg" }
        ]
      }
    ]
  }
]
```

---

## 🚀 Features

✅ **Drag & Drop**
- Reorder components
- Move antar parent
- Nested structure support

✅ **Live Editing**
- Click to select
- Edit properties real-time
- Undo/Redo

✅ **Form Builder**
- Add/Edit/Remove fields
- Field types: input, email, textarea, select, checkbox
- Dynamic validation

✅ **Preview**
- Live canvas preview
- Desktop preview window
- Mobile preview (future)

✅ **Save & Load**
- Save to database (JSON)
- Load existing pages
- Export/Import

✅ **Performance**
- Efficient state management
- Minimal re-renders
- Lazy rendering

---

## 🔒 Scope & Isolation

### ✅ ONLY Modified

- `/master-page/create` → shows builder
- `/master-page/update/{id}` → shows builder with loaded data

### ❌ NOT Modified

- Global sidebar
- Main layout
- Other controllers
- Database schema
- Form handling

### Impact

- **ZERO** breaking changes
- Fully backward compatible
- Can coexist with old interface

---

## 📚 API Reference

### StateManager

```javascript
// Create
new StateManager(initialState)

// Methods
addNode(parentId, node)
updateNode(nodeId, updates)
deleteNode(nodeId)
reorderNodes(parentId, newOrder)
moveNode(nodeId, newParentId)
updateFormFields(formId, fields)
undo()
redo()
subscribe(callback)
export()
import(jsonString)
```

### RenderEngine

```javascript
// Create
new RenderEngine(containerId)

// Methods
render(state)
renderNode(node, parentId)
selectNode(nodeId, element)
```

### PropertiesPanel

```javascript
// Create
new PropertiesPanel(containerId)

// Methods
showProperties(nodeId)
updateProp(propName, value)
```

### FormBuilder

```javascript
// Create
new FormBuilder(containerId)

// Methods
showFormBuilder(formId)
saveField()
removeField(index)
editField(index)
updateField(index)
cancelEdit()
```

### PageBuilder

```javascript
// Create
new PageBuilder(config)

// Methods
addComponent(type)
savePage()
previewPage()
exportJSON()
undo()
redo()
```

### FrontendRenderer

```javascript
// Static methods
FrontendRenderer.render(state, containerId)
FrontendRenderer.renderNode(node)
FrontendRenderer.createElementFromNode(node)
```

---

## 🐛 Troubleshooting

### Issue: Component tidak bisa didrag

**Cause:** draggable attribute tidak set
**Fix:** Check renderEngine draggable property

### Issue: State tidak ter-update

**Cause:** Listener tidak ter-attach
**Fix:** Ensure subscribe() called di init

### Issue: Form fields hilang

**Cause:** Incorrect field structure
**Fix:** Use field builder UI, jangan manual edit

---

## 🔄 Version

- **Version:** 1.0
- **Status:** Production Ready
- **Last Updated:** 2024-01-01

---

## 📞 Support

Untuk questions/issues:
1. Check browser console untuk errors
2. Verify state di DevTools: `window.pageState.getState()`
3. Check backend logs untuk save errors

---

**Ready for Production!** 🚀
