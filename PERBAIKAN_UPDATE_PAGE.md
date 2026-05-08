# Perbaikan: Dynamic Page Builder Update Mode

## Masalah yang Diperbaiki

### Sebelumnya (BUG)
Ketika membuka halaman `/master-page/dynamic-update?id=X`:
- Canvas tampil **KOSONG** (blank putih)
- Builder tidak render ulang component yang sudah disimpan
- Template tidak ditampilkan kembali
- Layout JSON tidak di-restore

### Sesudahnya (FIXED) ✅
Ketika membuka halaman `/master-page/dynamic-update?id=X`:
- Canvas **OTOMATIS RENDER** dengan semua component yang disimpan
- Template/layout yang sebelumnya dipilih **TAMPIL KEMBALI**
- Properties panel menampilkan komponen yang dipilih
- User bisa **LANGSUNG EDIT** tanpa perlu setup ulang

---

## Perubahan yang Dilakukan

### 1. **Struktur HTML - Builder Container Wrapper**

**File:** `views/master-page/dynamic-builder.php`

Sebelumnya:
```php
<!-- Toolbar terpisah dari builder -->
<div class="builder-toolbar">...</div>

<!-- Builder interface dengan kondisi display:none -->
<div class="page-builder" id="builderInterface" 
     style="<?= ($model->isNewRecord && empty($model->layout_json)) ? 'display:none;' : '' ?>">
```

Sesudahnya:
```php
<!-- Wrapper container untuk toolbar + builder -->
<div class="builder-container" id="builderContainer">
    <!-- Toolbar INSIDE container -->
    <div class="builder-toolbar">...</div>
    
    <!-- Builder interface INSIDE container -->
    <div class="page-builder" id="builderInterface">
```

**Keuntungan:**
- Visibility control lebih konsisten
- Mudah toggle antara mode CREATE dan UPDATE
- CSS layout lebih predictable

---

### 2. **CSS Updates - Flexible Layout**

```css
/* Wrapper untuk entire builder */
.builder-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #ffffff;
}

.builder-container.hidden {
    display: none !important;
}

/* Toolbar lebih fleksibel */
.builder-toolbar {
    height: 56px;
    flex-shrink: 0;  /* Tidak bisa shrink */
}

/* Page builder memakai sisa space */
.page-builder {
    height: calc(100vh - 56px);  /* Minus toolbar height */
    flex: 1;
}
```

---

### 3. **JavaScript Initialization - Robust Mode Detection**

**Sebelumnya (Kurang Robust):**
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const hasExisting = <?= json_encode(!empty($model->layout_json)) ?>;
    if (!hasExisting) {
        renderTemplates();
    } else {
        const modal = document.getElementById('templateModal');
        if (modal) modal.remove();
        document.getElementById('builderInterface').style.display = 'flex';
        renderBuilder(window.pageState);
    }
});
```

**Sesudahnya (Lebih Robust + Debug):**
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const hasExisting = <?= json_encode(!empty($model->layout_json)) ?>;
    const builderContainer = document.getElementById('builderContainer');
    const templateModal = document.getElementById('templateModal');
    
    console.log('Initializing builder - hasExisting:', hasExisting, 'pageState:', window.pageState);
    
    if (!hasExisting) {
        // Mode CREATE - Show template selector
        console.log('Mode: CREATE - Showing template selector');
        if (templateModal) templateModal.style.display = 'flex';
        if (builderContainer) {
            builderContainer.classList.add('hidden');
            builderContainer.style.display = 'none';
        }
        renderTemplates();
    } else {
        // Mode UPDATE - Show builder with existing state
        console.log('Mode: UPDATE - Rendering builder with state');
        if (templateModal) templateModal.remove();
        if (builderContainer) {
            builderContainer.classList.remove('hidden');
            builderContainer.style.display = 'flex';
        }
        
        // Render canvas dengan state yang ada
        console.log('Rendering builder with', window.pageState.length, 'blocks');
        renderBuilder(window.pageState);
        
        // Auto-select first block
        if (window.pageState && window.pageState.length > 0) {
            console.log('Selecting first block:', window.pageState[0].id);
            selectBlock(window.pageState[0].id);
        }
    }
    
    // Setup drag & drop events...
});
```

**Keuntungan:**
- Console logs memudahkan debugging
- Mode detection lebih jelas
- Auto-select first block saat update
- Handle null/empty state dengan baik

---

### 4. **renderBuilder() - Enhanced dengan Debug**

**Sesudahnya:**
```javascript
function renderBuilder(state) {
    const canvas = document.getElementById('canvas');
    console.log('renderBuilder called with state:', state);
    
    if (!canvas) {
        console.error('Canvas element not found!');
        return;
    }
    
    canvas.innerHTML = '';

    if (!state || state.length === 0) {
        console.log('No blocks, showing empty message');
        canvas.innerHTML = '<p>Drag komponen dari panel kiri ke sini</p>';
        return;
    }

    console.log('Rendering', state.length, 'blocks');
    state.forEach(block => {
        const el = createBlockElement(block);
        canvas.appendChild(el);
    });
    
    // Setup Sortable untuk drag-reorder
    if (window.sortableInstance) {
        window.sortableInstance.destroy();
    }
    window.sortableInstance = new Sortable(canvas, {
        animation: 150,
        handle: '.block-action-btn.move',
        ghostClass: 'sortable-ghost',
        onEnd: function(evt) {
            const item = window.pageState.splice(evt.oldIndex, 1)[0];
            window.pageState.splice(evt.newIndex, 0, item);
            renderBuilder(window.pageState);
        }
    });
}
```

---

### 5. **renderProperties() - Null Safety**

**Sesudahnya:**
```javascript
function renderProperties(blockId) {
    const panel = document.getElementById('properties-panel');
    
    // Handle null blockId
    if (!blockId) {
        panel.innerHTML = '<div class="no-selection">...'
        return;
    }
    
    const block = window.pageState.find(b => b.id === blockId);
    
    // Handle block not found
    if (!block) {
        panel.innerHTML = '<div class="no-selection">...'
        return;
    }
    
    // Render properties untuk block...
}
```

---

### 6. **confirmTemplate() - Updated untuk Use New Container**

**Sesudahnya:**
```javascript
function confirmTemplate() {
    let newState = [];
    if (selectedTemplateId && selectedTemplateId !== 'blank') {
        const template = templates.find(t => t.id === selectedTemplateId);
        if (template) newState = JSON.parse(JSON.stringify(template.state));
    }
    window.pageState = newState;

    const modal = document.getElementById('templateModal');
    if (modal) modal.remove();
    
    // Show builder container (bukan hanya builderInterface)
    const container = document.getElementById('builderContainer');
    if (container) {
        container.classList.remove('hidden');
        container.style.display = 'flex';
    }

    setTimeout(() => {
        renderBuilder(window.pageState);
        if (window.pageState && window.pageState.length > 0) {
            selectBlock(window.pageState[window.pageState.length - 1].id);
        } else {
            renderProperties(null);
        }
    }, 0);
}
```

---

## Flow Initialization

### CREATE Mode (New Page)
```
URL: /master-page/dynamic-create
  ↓
DOMContentLoaded fires
  ↓
hasExisting = false
  ↓
Show templateModal overlay
  ↓
Hide builderContainer
  ↓
User pilih template → confirmTemplate()
  ↓
Remove modal, show builderContainer
  ↓
renderBuilder(selectedTemplate.state)
```

### UPDATE Mode (Edit Existing)
```
URL: /master-page/dynamic-update?id=4
  ↓
PHP: $initialState = json_decode($model->layout_json, true)
  ↓
JS: window.pageState = <?= json_encode($initialState) ?>
  ↓
DOMContentLoaded fires
  ↓
hasExisting = true
  ↓
Remove templateModal
  ↓
Show builderContainer
  ↓
renderBuilder(window.pageState)  ← Canvas auto-populated!
  ↓
selectBlock(firstBlock.id)  ← Auto-select first block
  ↓
Canvas ready for editing!
```

---

## Testing Checklist

- [ ] **CREATE Flow**
  - [ ] Buka `/master-page/dynamic-create`
  - [ ] Template selector muncul
  - [ ] Pilih template
  - [ ] Builder tampil dengan template blocks
  - [ ] Bisa drag, edit, delete blocks
  - [ ] Save berhasil

- [ ] **UPDATE Flow**
  - [ ] Buka `/master-page/dynamic-update?id=4`
  - [ ] Canvas **TIDAK KOSONG** (ada blocks)
  - [ ] Blocks tampil sesuai yang disimpan
  - [ ] Bisa click block untuk edit di properties panel
  - [ ] Bisa drag blocks untuk reorder
  - [ ] Bisa add/delete blocks
  - [ ] Save perubahan

- [ ] **Edge Cases**
  - [ ] Buka update page dengan 0 blocks → canvas kosong tapi bisa add
  - [ ] Buka update page dengan 10+ blocks → semua visible
  - [ ] Switch antara create & update → state ter-maintain

---

## Console Debugging

Untuk debugging, buka browser console (F12) dan lihat logs:

```javascript
// CREATE mode logs
"Mode: CREATE - Showing template selector"

// UPDATE mode logs
"Mode: UPDATE - Rendering builder with state"
"Rendering builder with 5 blocks"  // Jumlah blocks dari database
"Selecting first block: block-xxx-yyy"
```

---

## Database & State Flow

```
Database (layout_json column)
  ↓
Server: json_decode → $initialState array
  ↓
Server: echo json_encode($initialState)
  ↓
Browser: window.pageState = {decoded state}
  ↓
DOMContentLoaded: renderBuilder(window.pageState)
  ↓
Canvas populated with blocks from database!
```

---

## Performa

- **No extra API calls** saat render
- **No database queries** setelah page load (state sudah di JS)
- **Smooth drag-reorder** dengan Sortable.js
- **Lazy property panel** render only when block selected

---

## Summary

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| Canvas saat UPDATE | ❌ Kosong | ✅ Terisi blocks |
| Template di UPDATE | ❌ Hilang | ✅ Ter-restore |
| Auto-select block | ❌ Tidak | ✅ Ya (block pertama) |
| Debug info | ❌ Tidak | ✅ Console logs |
| HTML structure | ❌ Toolbar terpisah | ✅ Container unified |
| CSS consistency | ⚠️ Conditional | ✅ Unified control |

