# PageDisplayService - Handle Menu Click & Page Rendering

## 📦 Service Overview

```php
$service = new \app\services\PageDisplayService();
```

---

## 🔄 Flow Saat User Klik Menu

```
User Klik Menu
      ↓
┌─────────────────┐
│  Get Menu       │
│  (ID / Slug)    │
└────────┬────────┘
         ↓
    ┌────┴────┐
    │  Type   │
    └────┬────┘
         ↓
    ┌────┼────┬────────┐
    ↓    ↓    ↓        ↓
 GROUP  ROUTE  PAGE    ❓
    ↓    ↓    ↓
 DROPDOWN REDIRECT RENDER
         ↓
   ┌────┴────┐
   │  Page  │
   └────┬────┘
        ↓
   ┌────┴────┐
   │  Forms │
   └────┬────┘
        ↓
   ┌────┴────┐
   │ Render │
   └─────────┘
```

---

## 🔧 Methods

### 1. Handle Menu Click (Main Entry)
```php
$result = $service->handleMenuClick($menuIdOrSlug);
// Returns: [
//   'success' => true/false,
//   'type' => 'group'|'route'|'page',
//   'action' => 'dropdown'|'redirect'|'render_page',
//   ...data
// ]
```

### 2. Get Menu by ID/Slug
```php
$menu = $service->getMenu(5);           // By ID
$menu = $service->getMenu('data-siswa'); // By menu_key
// Returns: MasterMenu|null
```

### 3. Get Page by ID/Slug
```php
$page = $service->getPage(8);             // By ID
$page = $service->getPage('data-siswa');  // By slug
// Returns: MasterPage|null
```

### 4. Get Forms for Page
```php
$forms = $service->getPageForms(8);
// Returns: Form[]
```

### 5. Render HTML
```php
$html = $service->renderPageHtml($pageData);
// Returns: HTML string
```

---

## 📋 Response Examples

### ✅ Response: Route Type (Redirect)
**Input**: Menu ID 15 (Dashboard - type: route, route: /site/dashboard)

```json
{
    "success": true,
    "type": "route",
    "action": "redirect",
    "menu": {
        "id": 15,
        "name": "Dashboard",
        "icon": "dashboard"
    },
    "redirect_url": "/site/dashboard",
    "message": "Redirect ke: /site/dashboard"
}
```

**Frontend Action**: 
```javascript
if (response.action === 'redirect') {
    window.location.href = response.redirect_url;
}
```

---

### ✅ Response: Group Type (Dropdown)
**Input**: Menu ID 2 (Data Master - type: group)

```json
{
    "success": true,
    "type": "group",
    "action": "dropdown",
    "menu": {
        "id": 2,
        "name": "Data Master",
        "icon": "folder"
    },
    "children": [
        {
            "id": 5,
            "name": "Data Siswa",
            "type": "page",
            "icon": "person",
            "url": "/page/view?id=2"
        },
        {
            "id": 6,
            "name": "Data Guru",
            "type": "page",
            "icon": "school",
            "url": "/page/view?id=4"
        }
    ],
    "message": "Menu ini adalah grup. Silakan pilih submenu."
}
```

**Frontend Action**:
```javascript
if (response.action === 'dropdown') {
    // Expand dropdown, show children
    renderDropdown(response.children);
}
```

---

### ✅ Response: Page Type - Single Form
**Input**: Menu ID 8 (Nilai Siswa - type: page, page_id: 3)

Page dengan 1 form:

```json
{
    "success": true,
    "type": "page",
    "action": "render_page",
    "menu": {
        "id": 8,
        "name": "Nilai Siswa",
        "icon": "grade"
    },
    "page": {
        "id": 3,
        "title": "Nilai Siswa",
        "slug": null,
        "layout": "default",
        "description": "Formulir input dan kelola nilai"
    },
    "render": {
        "mode": "single",
        "form_count": 1,
        "form_id": 1,
        "message": "Tampilkan form langsung"
    },
    "forms": [
        {
            "id": 1,
            "name": "Form Nilai",
            "description": "",
            "order": 1,
            "schema": [
                {
                    "type": "text-input",
                    "label": "Nama Siswa",
                    "name": "nama_siswa",
                    "required": true
                },
                {
                    "type": "number",
                    "label": "Nilai",
                    "name": "nilai",
                    "min": 0,
                    "max": 100
                }
            ],
            "schema_json": "[{\"type\":\"text-input\",...}]"
        }
    ]
}
```

**Frontend Action**:
```javascript
if (response.render.mode === 'single') {
    // Render form langsung
    renderForm(response.forms[0]);
}
```

---

### ✅ Response: Page Type - Multiple Forms (Tabs)
**Input**: Page dengan 3 forms

```json
{
    "success": true,
    "type": "page",
    "action": "render_page",
    "page": {
        "id": 8,
        "title": "Data Siswa",
        "slug": "data-siswa",
        "layout": "default",
        "description": "Kelola data siswa"
    },
    "render": {
        "mode": "tabs",
        "form_count": 3,
        "tabs": [
            {"id": 1, "name": "Biodata", "order": 1},
            {"id": 2, "name": "Orang Tua", "order": 2},
            {"id": 3, "name": "Berkas", "order": 3}
        ],
        "message": "Tampilkan dalam 3 tabs"
    },
    "forms": [
        {
            "id": 1,
            "name": "Biodata",
            "order": 1,
            "schema": [...]
        },
        {
            "id": 2,
            "name": "Orang Tua", 
            "order": 2,
            "schema": [...]
        },
        {
            "id": 3,
            "name": "Berkas",
            "order": 3,
            "schema": [...]
        }
    ]
}
```

**Frontend Action**:
```javascript
if (response.render.mode === 'tabs') {
    // Render tab container
    renderTabs(response.render.tabs, response.forms);
}
```

---

### ❌ Response: Error Cases

**Menu Tidak Ditemukan**:
```json
{
    "success": false,
    "error": "Menu tidak ditemukan",
    "code": "MENU_NOT_FOUND"
}
```

**Page Tidak di-Link**:
```json
{
    "success": false,
    "error": "Menu page belum terhubung dengan halaman",
    "code": "PAGE_NOT_LINKED"
}
```

**Page Tidak Aktif**:
```json
{
    "success": false,
    "error": "Halaman tidak ditemukan atau tidak aktif",
    "code": "PAGE_INACTIVE"
}
```

---

## 🎨 Rendered HTML Structure

### Single Form Mode
```html
<div class="page-content">
    <div class="page-header mb-4">
        <h1 class="text-2xl font-bold">Nilai Siswa</h1>
        <p class="text-gray-600">Formulir input dan kelola nilai</p>
    </div>
    
    <div class="form-render" data-form-id="1">
        <!-- Form Fields dari Schema -->
        <div class="form-field">
            <label>Nama Siswa</label>
            <input type="text" name="nama_siswa" required>
        </div>
        <div class="form-field">
            <label>Nilai</label>
            <input type="number" name="nilai" min="0" max="100">
        </div>
    </div>
</div>
```

### Tabs Mode
```html
<div class="page-content">
    <div class="page-header mb-4">
        <h1>Data Siswa</h1>
        <p>Kelola data siswa</p>
    </div>
    
    <div class="tabs-container">
        <!-- Tab Headers -->
        <div class="tabs-header flex border-b mb-4">
            <button class="tab-btn active" data-tab="1">Biodata</button>
            <button class="tab-btn" data-tab="2">Orang Tua</button>
            <button class="tab-btn" data-tab="3">Berkas</button>
        </div>
        
        <!-- Tab Contents -->
        <div class="tabs-content">
            <div class="tab-panel" id="tab-content-1" style="display:block">
                <!-- Form 1 -->
            </div>
            <div class="tab-panel" id="tab-content-2" style="display:none">
                <!-- Form 2 -->
            </div>
            <div class="tab-panel" id="tab-content-3" style="display:none">
                <!-- Form 3 -->
            </div>
        </div>
    </div>
</div>
```

### Empty Page (No Forms)
```html
<div class="page-content">
    <div class="page-header mb-4">
        <h1>Data Siswa</h1>
    </div>
    
    <div class="alert alert-info">
        Belum ada form di halaman ini
    </div>
</div>
```

---

## 🔀 JavaScript Handler (Pseudo Code)

```javascript
class PageLoader {
    constructor() {
        this.service = '/api/page-display';
    }
    
    async loadPage(menuId) {
        const response = await fetch(`${this.service}?menu_id=${menuId}`);
        const data = await response.json();
        
        if (!data.success) {
            this.showError(data.error);
            return;
        }
        
        // Handle berdasarkan action
        switch (data.action) {
            case 'redirect':
                window.location.href = data.redirect_url;
                break;
                
            case 'dropdown':
                // Tampilkan dropdown children
                this.renderDropdown(data.menu, data.children);
                break;
                
            case 'render_page':
                this.renderPage(data);
                break;
        }
    }
    
    renderPage(data) {
        const page = data.page;
        const renderMode = data.render.mode;
        
        // Update breadcrumb
        this.updateBreadcrumb(data.menu);
        
        // Render content based on mode
        if (renderMode === 'single') {
            this.renderSingleForm(data.forms[0]);
        } else if (renderMode === 'tabs') {
            this.renderTabs(data.render.tabs, data.forms);
        } else if (renderMode === 'empty') {
            this.renderEmptyState();
        }
    }
    
    renderTabs(tabs, forms) {
        let html = '<div class="tabs">';
        
        // Tab buttons
        html += '<div class="tab-buttons">';
        tabs.forEach((tab, index) => {
            html += `<button class="tab-btn ${index === 0 ? 'active' : ''}" 
                        data-form-id="${tab.id}">${tab.name}</button>`;
        });
        html += '</div>';
        
        // Tab contents
        html += '<div class="tab-contents">';
        forms.forEach((form, index) => {
            html += `<div class="tab-panel ${index === 0 ? 'active' : ''}" 
                        id="form-${form.id}">`;
            html += this.renderFormSchema(form.schema);
            html += '</div>';
        });
        html += '</div>';
        
        html += '</div>';
        document.getElementById('page-content').innerHTML = html;
    }
}
```

---

## 📊 Render Mode Decision

```
┌─────────────────┐
│   Hitung Forms │
└────────┬────────┘
         ↓
    ┌────┴────┐
    │ Count   │
    └────┬────┘
         ↓
    ┌────┼────┐
    ↓    ↓    ↓
   0    1    >1
    ↓    ↓    ↓
 EMPTY SINGLE  TABS
```

| Forms Count | Mode | Frontend |
|-------------|------|----------|
| 0 | `empty` | Tampilkan "Belum ada form" |
| 1 | `single` | Render form langsung |
| >1 | `tabs` | Render tab container |

---

## ✅ Summary

| Feature | Status |
|---------|--------|
| Get menu by ID/slug | ✅ |
| Handle type: group | ✅ |
| Handle type: route | ✅ |
| Handle type: page | ✅ |
| Get page + forms | ✅ |
| Sort forms by order | ✅ |
| Render mode decision (empty/single/tabs) | ✅ |
| JSON output | ✅ |
| HTML render | ✅ |
| Error handling | ✅ |

---

## 📁 Files

```
services/
├── MenuService.php       ← CRUD Menu + Validation
├── PageService.php      ← CRUD Page + Form Sync  
├── SidebarService.php   ← Sidebar tree + JSON
└── PageDisplayService.php  ← Handle click + Render (INI)
```