# Dynamic Visual Page Builder - Quick Start Guide

**Status:** ✅ PRODUCTION READY  
**PHP Version:** 7.2+  
**Framework:** Yii2  
**Architecture:** Single Source of Truth (JavaScript State)

---

## 🚀 Fitur Utama

✅ Drag & drop interface  
✅ Live editing dengan property panel  
✅ Form builder dinamis  
✅ Component library (Section, Row, Column, Text, Heading, Image, Button, Form)  
✅ Undo/Redo functionality  
✅ Save & export capabilities  
✅ Real-time preview  
✅ Responsive design  

---

## 📋 Cara Penggunaan

### 1. **Membuat Halaman Baru Dengan Visual Builder**

```
URL: /master-page/visual-create
```

- Pilih komponen dari panel kiri
- Drag & drop ke canvas
- Klik komponen untuk edit properties di panel kanan
- Klik "Save Page" untuk menyimpan

### 2. **Mengedit Halaman Existing**

```
URL: /master-page/visual-update?id={pageId}
```

- Semua komponen halaman yang sudah ada akan di-load
- Edit komponen dengan klik + modifikasi properties
- Drag untuk mengubah urutan
- Klik "Update Page" untuk menyimpan

### 3. **Mengakses Builder Legacy**

```
URL: /master-page/builder?id={pageId}
```

- Advanced builder untuk halaman yang sudah dibuat
- Support untuk lebih banyak component types
- Device preview (Desktop/Tablet/Mobile)

---

## 🏗️ Arsitektur Sistem

### Single Source of Truth (JavaScript State)

Seluruh state halaman disimpan dalam satu object JavaScript:

```javascript
window.pageState = {
  components: [
    {
      id: "section-1",
      type: "section",
      props: { backgroundColor: "#fff", padding: "20px" },
      children: [
        {
          id: "heading-1",
          type: "heading",
          props: { content: "Judul", level: "h2", color: "#000" }
        },
        {
          id: "form-1",
          type: "form",
          props: { action: "/submit", method: "POST" },
          fields: [
            { id: "f1", type: "input", label: "Nama", name: "nama" }
          ]
        }
      ]
    }
  ]
}
```

### Component Types

| Type | Category | Props | Children |
|------|----------|-------|----------|
| section | layout | backgroundColor, padding | ✅ Yes |
| row | layout | display, gap | ✅ Yes |
| column | layout | flex | ✅ Yes |
| text | content | content, fontSize, color | ❌ No |
| heading | content | content, level, color | ❌ No |
| image | content | src, alt, width, height | ❌ No |
| button | content | text, link, backgroundColor, color, padding | ❌ No |
| form | advanced | action, method | ❌ No (has fields) |

---

## 📁 File Structure

```
web/
├── js/page-builder/
│   ├── state-manager.js          # State management (undo/redo)
│   ├── component-library.js      # Component definitions
│   ├── render-engine.js          # Builder view renderer
│   ├── properties-panel.js       # Property editor
│   ├── form-builder.js           # Form field editor
│   ├── frontend-renderer.js      # Frontend HTML generator
│   └── builder.js                # Main coordinator
│
├── css/
│   └── page-builder.css          # All styling
│
views/master-page/
├── visual-create.php             # Create page view
├── visual-update.php             # Update page view
└── (visual-builder.php - legacy)

assets/
└── PageBuilderAsset.php          # Asset bundle registration

controllers/
└── MasterPageController.php      # Routes & actions
    - actionVisualCreate()        # GET /master-page/visual-create
    - actionVisualUpdate($id)     # GET /master-page/visual-update?id=X
    - actionVisualSave()          # POST /master-page/visual-save (AJAX)
    - actionBuilder($id)          # GET /master-page/builder?id=X (legacy)
```

---

## 🔌 API Endpoints

### Create New Page
```
POST /master-page/visual-create-submit
Body: {
  title: string,
  slug: string,
  content: JSON string (page state)
}
Response: Redirect to index on success
```

### Update Page
```
POST /master-page/visual-save
Body: {
  pageId: int,
  title: string (optional),
  slug: string (optional),
  content: JSON string
}
Response: { success: true/false, message: string, pageId: int }
```

### Save Layout (Legacy)
```
POST /master-page/save-layout
Body: {
  page_id: int,
  layout_json: JSON string
}
Response: { success: true/false, message: string }
```

---

## 🎨 JavaScript API

### StateManager
```javascript
// Get current state
const state = window.pageState.getState();

// Add component
window.pageState.addNode(parentId, nodeData);

// Update component
window.pageState.updateNode(nodeId, { prop: value });

// Delete component
window.pageState.deleteNode(nodeId);

// Undo/Redo
window.pageState.undo();
window.pageState.redo();

// Subscribe to changes
window.pageState.subscribe((state) => {
  console.log('State changed:', state);
});
```

### PageBuilder
```javascript
// Initialize builder
window.pageBuilder = new PageBuilder({
  pageId: 123,
  initialData: [],
  mode: 'create' // or 'update'
});

// Save page
window.pageBuilder.savePage();        // create mode
window.pageBuilder.savePageUpdate();  // update mode

// Preview
window.pageBuilder.previewPage();

// Export
window.pageBuilder.exportJSON();

// Undo/Redo
window.pageBuilder.undo();
window.pageBuilder.redo();
```

### RenderEngine
```javascript
// Render state to DOM
window.renderEngine.render(state);

// Select node
window.renderEngine.selectNode(nodeId, element);
```

### PropertiesPanel
```javascript
// Show properties for node
window.propsPanel.showProperties(nodeId);

// Update property
window.propsPanel.updateProp(propName, value);
```

### FrontendRenderer
```javascript
// Render for frontend (no editor controls)
FrontendRenderer.render(state, 'container-id');

// Generate HTML
const html = FrontendRenderer.renderNode(node);
```

---

## 🔧 Integrasi dengan Yii2

### 1. Register Asset di View
```php
use app\assets\PageBuilderAsset;

PageBuilderAsset::register($this);
```

### 2. Initialize PageBuilder
```php
$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
  window.pageBuilder = new PageBuilder({
    pageId: {$model->id},
    initialData: {$initialData},
    mode: 'update'
  });
});
JS;
$this->registerJs($js);
```

### 3. Handle Form Submission
```php
<?= Html::beginForm('visual-save', 'post', ['id' => 'page-save-form']) ?>
  <?= Html::hiddenInput('pageId', $model->id) ?>
  <?= Html::hiddenInput('content', null, ['id' => 'save-content']) ?>
<?= Html::endForm() ?>
```

---

## 💾 Database Schema

Model `MasterPage` memiliki field:
```sql
- id (PK)
- title (string)
- slug (string)
- layout_json (text)  -- JSON state disimpan di sini
- is_active (int)
- created_at (timestamp)
- updated_at (timestamp)
```

State di-serialize sebagai JSON string untuk penyimpanan database.

---

## ⚡ Performance Tips

1. **Lazy Load Components**
   - Panel komponen tidak render semua sekaligus
   - Load saat dibutuhkan

2. **Debounce Updates**
   - Property changes di-debounce untuk avoid frequent renders
   - Undo/redo track minimal state changes

3. **Optimize CSS**
   - CSS Grid untuk layout
   - CSS transitions untuk smooth UI
   - Minimal selector complexity

4. **Cache State**
   - State disimpan dalam memory
   - History limited untuk avoid memory leak

---

## 🐛 Troubleshooting

### Page tidak ter-render
- Pastikan PageBuilderAsset di-register
- Check browser console untuk JavaScript errors
- Verify JSON format di layout_json field

### Komponen tidak ter-drag
- Pastikan Sortable.js ter-load (untuk legacy builder)
- Check drag handlers di render-engine.js
- Browser support untuk drag & drop

### State tidak ter-save
- Check actionVisualSave route accessible
- Verify form submission POST data
- Check database write permissions

### Property tidak ter-update
- Ensure PropertyPanel event listeners attached
- Verify prop keys match component schema
- Check StateManager subscription triggered

---

## 📚 Dokumentasi Lengkap

Lihat file berikut untuk dokumentasi lebih detail:
- `DYNAMIC_VISUAL_PAGE_BUILDER_DOCS.md` - Complete API reference
- `VISUAL_PAGE_BUILDER_TESTING_GUIDE.md` - Testing procedures
- `DEPLOYMENT_CHECKLIST.md` - Production deployment

---

## ✅ Compatibility

- **PHP:** 7.2+ (tested with 7.2, 7.4)
- **Yii:** 2.0+
- **Browsers:** Chrome/Firefox/Safari/Edge (ES6 support required)
- **Database:** MySQL 5.7+, SQLite 3+, PostgreSQL 9.6+

---

## 🎓 Next Steps

1. ✅ Create a new page at `/master-page/visual-create`
2. ✅ Drag components and edit properties
3. ✅ Save page
4. ✅ View saved page
5. ✅ Update page at `/master-page/visual-update`

**Selamat menggunakan Dynamic Visual Page Builder! 🚀**
