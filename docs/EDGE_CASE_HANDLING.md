# Edge Case Handling - Dynamic CMS

## 📋 Overview

Sistem menangani 5 edge cases utama:

| # | Edge Case | Handling | Location |
|---|-----------|----------|----------|
| 1 | Circular menu (A → B → A) | Ditolak dengan error | MenuService, EdgeCaseValidator |
| 2 | Menu tanpa page & route | Validasi: hanya group yang boleh | MenuService |
| 3 | Page tanpa form | Warning/info (valid) | EdgeCaseValidator |
| 4 | Menu nonaktif | Tidak tampil di sidebar | SidebarService |
| 5 | Page nonaktif | Menu ada tapi tidak bisa dibuka | PageDisplayService |

---

## 1. Circular Menu (A → B → A)

### Problem
```
A (parent: B)
  ↓
B (parent: A)
  ↓
→ Infinite loop!
```

### Solution
```php
// Di MenuService::checkCircularParent()
$visited = [];
while ($parentId) {
    if (in_array($parentId, $visited)) return true; // Circular detected
    $visited[] = $parentId;
    $parent = MasterMenu::findOne($parentId);
    $parentId = $parent->parent_id;
}
```

### Example Response
```json
{
    "success": false,
    "error": "Tidak boleh ada circular parent",
    "code": "CIRCULAR_MENU",
    "validation": {
        "is_valid": false,
        "errors": [{
            "type": "circular_parent",
            "severity": "error",
            "message": "Menu tidak bisa menjadi ancestor dirinya sendiri"
        }]
    }
}
```

---

## 2. Menu Tanpa Page & Route

### Problem
- Menu type `page` wajib punya `page_id`
- Menu type `route` wajib punya `route`
- Menu type `group` TIDAK boleh punya keduanya

### Validation Rules
```php
// Di MenuService::validateMenu()
switch ($menu->type) {
    case 'group':
        // WAJIB KOSONG
        if (!empty($menu->page_id)) 
            error("Group tidak boleh terhubung ke halaman");
        if (!empty($menu->route)) 
            error("Group tidak boleh menggunakan route");
        break;
        
    case 'page':
        // WAJIB ADA
        if (empty($menu->page_id)) 
            error("Page wajib memilih halaman");
        break;
        
    case 'route':
        // WAJIB ADA
        if (empty($menu->route)) 
            error("Route wajib填写 URL");
        break;
}
```

### Example Error Messages
```
✗ "Menu tipe Group tidak boleh terhubung ke halaman"
✗ "Menu tipe Page wajib memilih Halaman"
✗ "Menu tipe Route wajib填写 URL"
```

---

## 3. Page Tanpa Form (Valid)

### Problem
- Page dengan 0 form adalah valid (halaman kosong)
- Tapi perlu informasikan ke user

### Handling
```php
// Di EdgeCaseValidator::validatePage()
$pageForms = PageForms::find()->where(['page_id' => $page->id])->count();
if ($pageForms == 0) {
    $warnings[] = [
        "type" => "no_forms",
        "severity" => "info",
        "message" => "Halaman ini belum memiliki form",
        "suggestion" => "Tambahkan form dari menu Master Page > Edit"
    ];
}
```

### Example Response
```json
{
    "exists": true,
    "page": {"id": 8, "title": "Data Siswa", "is_active": 1},
    "validation": {
        "is_valid": true,
        "warnings": [{
            "type": "no_forms",
            "severity": "info",
            "message": "Halaman ini belum memiliki form. Halaman akan kosong.",
            "suggestion": "Tambahkan form dari menu Master Page > Edit."
        }],
        "page_forms_count": 0
    }
}
```

---

## 4. Menu Nonaktif (Tidak Tampil di Sidebar)

### Problem
- Menu dengan `is_active = 0` tetap di database tapi tidak boleh muncul di sidebar

### Solution
```php
// Di SidebarService::getMenuTree()
$menus = MasterMenu::find()
    ->where(['is_active' => 1])  // FILTER INACTIVE
    ->orderBy(['order' => SORT_ASC])
    ->all();
```

### Usage
```php
$sidebarService = new SidebarService();

// Default: hanya aktif
$tree = $sidebarService->getMenuTree(true);

// Include nonaktif (untuk admin)
$tree = $sidebarService->getMenuTree(false);
```

### Get Valid Menus Only
```php
$validator = new EdgeCaseValidator();
$result = $validator->getSidebarValidMenus();
// Returns: { valid_menus: [...], total: 22 }
```

---

## 5. Page Nonaktif (Menu Ada Tapi Tidak Bisa Dibuka)

### Problem
- Menu terhubung ke page yang `is_active = 0`
- Menu tetap tampil di sidebar tapi tidak bisa diklik/dibuka

### Solution - Enhanced Response
```php
// Di PageDisplayService::handlePageType()
if ($page && $page->is_active != 1) {
    return [
        "success" => false,
        "type" => "page",
        "action" => "warning",
        "menu" => [...],
        "page" => [
            "id" => $page->id,
            "title" => $page->title,
            "is_active" => false,
        ],
        "warning" => "Halaman ini sedang tidak aktif",
        "code" => "PAGE_INACTIVE",
        "suggestion" => "Hubungi administrator untuk mengaktifkan",
        "can_activate" => true,
        "activate_url" => "/master-page/toggle?id=" . $page->id,
    ];
}
```

### Example Response
```json
{
    "success": false,
    "type": "page",
    "action": "warning",
    "menu": {
        "id": 5,
        "name": "Data Siswa",
        "page_id": 2
    },
    "page": {
        "id": 2,
        "title": "Data Siswa",
        "is_active": false
    },
    "warning": "Halaman ini sedang tidak aktif. Silakan hubungi administrator untuk mengaktifkan.",
    "code": "PAGE_INACTIVE",
    "suggestion": "Anda bisa tetap mengakses menu ini tapi tidak dapat melihat isi halaman.",
    "can_activate": true,
    "activate_url": "/master-page/toggle?id=2"
}
```

### Frontend Handler
```javascript
// Pseudo code
function handlePageResponse(response) {
    if (!response.success) {
        if (response.code === 'PAGE_INACTIVE') {
            showWarningModal({
                title: 'Halaman Nonaktif',
                message: response.warning,
                suggestion: response.suggestion,
                actions: [
                    { 
                        label: 'Aktifkan Halaman', 
                        url: response.activate_url,
                        style: 'primary'
                    },
                    { 
                        label: 'Tutup', 
                        action: 'close',
                        style: 'secondary'
                    }
                ]
            });
        }
    }
}
```

---

## 🔧 Quick Validation Helpers

### Check Menu Status
```php
$result = EdgeCaseValidator::quickMenuCheck(5);
// Returns: {
//   exists: true,
//   menu: { id, name, type, is_active, has_page, page_active },
//   validation: { is_valid, errors, warnings }
// }
```

### Check Page Status
```php
$result = EdgeCaseValidator::quickPageCheck(8);
// Returns: {
//   exists: true,
//   page: { id, title, is_active },
//   validation: { is_valid, errors, warnings, page_forms_count }
// }
```

### Get All Inactive Page Menus
```php
$menus = EdgeCaseValidator::getInactivePageMenus();
// Returns: [{
//   menu_id, menu_name, 
//   page_id, page_title, 
//   activate_url
// }]
```

---

## 📊 Test Results

```
=== EDGE CASE VALIDATOR TEST ===

1. GET SIDEBAR VALID MENUS (filter inactive)
   Total valid menus: 22

2. GET INACTIVE PAGE MENUS (warning)
   ✓ Tidak ada menu dengan page nonaktif

3. QUICK MENU CHECK
   {
     "menu": { "id": 5, "name": "Data Siswa", "type": "page", "is_active": 1 },
     "validation": { "is_valid": true, "errors": [], "warnings": [] }
   }

4. QUICK PAGE CHECK
   {
     "page": { "id": 8, "title": "Data Siswa", "is_active": 1 },
     "validation": { "is_valid": true, "page_forms_count": 1 }
   }
```

---

## ✅ Summary

| Edge Case | Status | Error/Warning |
|-----------|--------|---------------|
| Circular menu | ✅ Ditolak | Error |
| Menu tanpa page/route | ✅ Validasi | Error |
| Page tanpa form | ✅ Allowed | Info |
| Menu nonaktif | ✅ Filtered | - |
| Page nonaktif | ✅ Warning | Warning + activation link |