# SidebarService - Menu Rendering from Database

## 📦 Service Overview

```php
$service = new \app\services\SidebarService();
```

---

## 🔧 Methods

### 1. Get Menu Tree (Array)
```php
$tree = $service->getMenuTree(true);
// Returns: [
//   [
//     'id' => 1,
//     'name' => 'Dashboard',
//     'type' => 'route',
//     'url' => '/site/dashboard',
//     'children' => [...submenus]
//   ],
//   ...
// ]
```

### 2. Get JSON Output
```php
$json = $service->getMenuJson(true);
// Returns JSON string dengan nested structure
```

### 3. Render HTML
```php
$html = $service->renderHtml();
// Returns HTML untuk sidebar:
// <div class="menu-item dropdown">
//   <a href="#" class="menu-toggle">...</a>
//   <div class="submenu">...</div>
// </div>
```

### 4. Get URL by Type
```php
$url = $service->getMenuUrl($menuModel);
// type=route → return route string
// type=page → return ['/page/view', 'id' => ...]
// type=group → return '#'
```

### 5. Get Breadcrumb
```php
$path = $service->getBreadcrumb(5);
// Returns: [
//   ['id' => 1, 'name' => 'Akademik', 'url' => '...'],
//   ['id' => 5, 'name' => 'Nilai Siswa', 'url' => '...']
// ]
```

### 6. Get Root Menus Only
```php
$roots = $service->getRootMenus(true);
// Returns hanya menu tanpa parent
```

### 7. Get Children
```php
$children = $service->getChildren(1, true);
// Returns children dari parent_id = 1
```

### 8. Counts
```php
$menuCount = $service->countActiveMenus();    // 22
$pageCount = $service->countActivePages();   // 11
```

---

## 🌳 Output Structure (JSON)

```json
[
  {
    "id": 15,
    "name": "Dashboard",
    "type": "route",
    "icon": "dashboard",
    "url": "/site/dashboard",
    "page_id": null,
    "route": "/site/dashboard",
    "parent_id": null,
    "order": 1,
    "has_children": false,
    "children": []
  },
  {
    "id": 3,
    "name": "Akademik",
    "type": "page",
    "icon": "school",
    "url": "#",
    "parent_id": null,
    "order": 3,
    "has_children": true,
    "children": [
      {
        "id": 8,
        "name": "Nilai Siswa",
        "type": "group",
        "url": "#",
        "parent_id": 3,
        "order": 1,
        "has_children": false,
        "children": []
      },
      {
        "id": 9,
        "name": "Input Nilai",
        "type": "group",
        "parent_id": 3,
        "order": 2,
        "has_children": true,
        "children": [
          {
            "id": 11,
            "name": "Nilai Harian",
            "type": "group",
            "parent_id": 9,
            "has_children": false
          }
        ]
      }
    ]
  }
]
```

---

## 📊 Rendered HTML Structure

```html
<!-- Menu tanpa child → Link -->
<a href="/site/dashboard" class="menu-link">
  <span class="icon">dashboard</span>
  <span class="label">Dashboard</span>
</a>

<!-- Menu dengan child → Dropdown -->
<div class="menu-item dropdown">
  <a href="#" class="menu-toggle">
    <span class="icon">school</span>
    <span class="label">Akademik</span>
    <span class="arrow">▼</span>
  </a>
  <div class="submenu">
    <a href="#" class="menu-link">
      <span class="icon">grade</span>
      <span class="label">Nilai Siswa</span>
    </a>
    <div class="menu-item dropdown">
      <a href="#" class="menu-toggle">
        <span class="icon">edit_note</span>
        <span class="label">Input Nilai</span>
        <span class="arrow">▼</span>
      </a>
      <div class="submenu">
        <a href="#" class="menu-link">
          <span class="icon">calendar_today</span>
          <span class="label">Nilai Harian</span>
        </a>
      </div>
    </div>
  </div>
</div>
```

---

## 🔄 Recursive Logic

```php
function buildTree($menus, $parentId = null): array
{
    $tree = [];
    
    foreach ($menus as $menu) {
        // Cek apakah ini child dari $parentId
        if ($menu->parent_id == $parentId) {
            // Build node
            $node = buildNode($menu);
            
            // RECURSIVE: Cari children
            $children = buildTree($menus, $menu->id);
            
            if (!empty($children)) {
                $node['children'] = $children;
                $node['has_children'] = true;
            }
            
            $tree[] = $node;
        }
    }
    
    return $tree;
}
```

---

## ✅ Features

| Feature | Status |
|---------|--------|
| Ambil menu aktif dari DB | ✅ |
| Hierarchical tree (parent-child) | ✅ |
| Urutkan berdasarkan `order` | ✅ |
| Unlimited nesting (recursive) | ✅ |
| JSON output | ✅ |
| HTML render | ✅ |
| Breadcrumb path | ✅ |
| Get by parent | ✅ |
| Count menu/pages | ✅ |

---

## 📁 Files

```
services/
├── MenuService.php       ← CRUD Menu + Validasi
├── PageService.php       ← CRUD Page + Forms
└── SidebarService.php   ← Rendering + Tree (INI)
```

---

## 🚀 Usage Example

```php
// Di Controller atau View
$sidebarService = new \app\services\SidebarService();

// Option 1: Langsung render HTML
echo $sidebarService->renderHtml();

// Option 2: Get JSON untuk API
$json = $sidebarService->getMenuJson(true);
return $this->asJson(['success' => true, 'data' => $json]);

// Option 3: Get array untuk processing
$tree = $sidebarService->getMenuTree(true);
// Process sendiri...
```