# 📊 Panduan Hierarchical Menu System

## 🎯 Deskripsi Sistem

Sistem menu hirarki dinamis ini membangun struktur menu berbasis relasi **parent-child** dari database `master_menu`. Setiap menu dapat memiliki:

- **Level 1 (Root)**: Menu tanpa parent (`parent_id = NULL`) - ditampilkan sebagai menu utama
- **Level 2+ (Child)**: Menu dengan parent - ditampilkan sebagai submenu dengan indentasi visual

---

## 📋 Struktur Database

### Tabel: `master_menu`

```sql
CREATE TABLE master_menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NULL,                    -- Relasi ke menu parent
    page_id INT NULL,                      -- Link ke halaman
    name VARCHAR(100) NOT NULL,            -- Nama menu
    icon VARCHAR(50),                      -- Material Symbol icon
    type VARCHAR(20) NOT NULL,             -- 'group', 'page', 'route'
    route VARCHAR(255),                    -- Custom route/URL
    menu_key VARCHAR(50),                  -- Unique identifier
    sort_order INT,                        -- Urutan tampilan
    order INT,                             -- Alternative order field
    is_active INT DEFAULT 1,               -- Status aktif/non-aktif
    created_at TIMESTAMP,                  -- Waktu dibuat
    updated_at TIMESTAMP,                  -- Waktu diupdate
    
    -- Foreign Keys
    FOREIGN KEY (parent_id) REFERENCES master_menu(id),
    FOREIGN KEY (page_id) REFERENCES master_page(id)
);
```

---

## 🏗️ Komponen Sistem

### 1. **Model: `MasterMenu`**
Lokasi: `models/MasterMenu.php`

**Relasi Parent-Child:**
```php
public function getParent()       // Get parent menu
public function getChildren()     // Get child menus
```

**Hierarchical Methods:**
```php
// Build complete hierarchical tree
public static function getMenuTree($activeOnly = true)

// Check if menu has children
public function hasChildren(): bool

// Get menu URL (supports TYPE_ROUTE, TYPE_PAGE, TYPE_GROUP)
public function getUrl()

// Helper methods
public function isGroup(): bool   // Tipe GROUP (folder/container)
public function isPage(): bool    // Tipe PAGE (link ke halaman)
public function isRoute(): bool   // Tipe ROUTE (custom URL)
```

### 2. **Component: `DynamicSidebar`**
Lokasi: `components/DynamicSidebar.php`

**Fungsi:**
- Build hierarchical tree dari database
- Render HTML dengan proper nesting
- Track active menu item
- Support expand/collapse children

**Key Methods:**
```php
public function getMenuTree()                 // Get tree structure
private function buildTree($menus, $parentId) // Recursive tree builder
public function renderSidebar()               // Render complete HTML
private function renderMenuItems(...)         // Recursive HTML renderer
```

### 3. **Helper: `MenuBuilder`**
Lokasi: `helpers/MenuBuilder.php`

**Fungsi:**
- Alternative menu tree builder
- Render menu HTML dengan proper indentation
- URL resolver untuk berbagai tipe menu

**Functions:**
```php
buildMenuTree(array $menus, $parentId = null)  // Build tree recursively
buildMenuNode($menu)                           // Build single node
resolveMenuUrl($menu)                          // Resolve URL based on type
renderMenuHtml(array $tree, $level = 0)        // Render HTML tree
```

### 4. **View: `_sidebar.php`**
Lokasi: `views/layouts/_sidebar.php`

**Fungsi:**
- Render sidebar dengan menu tree
- Handle active menu highlighting
- Support expand/collapse animation

---

## 📐 Alur Data & Logika

### Data Flow: Database → UI

```
┌─────────────────────────────────────────────────┐
│ master_menu table (Flat Array dari DB)          │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│ MasterMenu::getMenuTree()                       │
│ - Filter active items                          │
│ - Sort by sort_order                           │
│ - Call buildTree()                             │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│ Hierarchical Tree Array (Nested Structure)      │
│                                                 │
│ [                                               │
│   {                                             │
│     id: 1,                                      │
│     name: "Dashboard",                          │
│     parent_id: null,          ← ROOT LEVEL     │
│     children: null                              │
│   },                                            │
│   {                                             │
│     id: 2,                                      │
│     name: "Settings",                           │
│     parent_id: null,          ← ROOT LEVEL     │
│     children: [                                 │
│       {                                         │
│         id: 3,                                  │
│         name: "General",                        │
│         parent_id: 2,         ← CHILD LEVEL   │
│         children: null                          │
│       },                                        │
│       { id: 4, name: "Account", ... }          │
│     ]                                           │
│   }                                             │
│ ]                                               │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│ Render Sidebar (Recursive Rendering)            │
│ - Level 0: Menu utama (root items)              │
│ - Level 1: Submenu dengan indentasi             │
│ - Level 2+: Nested submenu (jika ada)           │
└──────────────┬──────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│ HTML Output dengan CSS Classes:                 │
│                                                 │
│ <ul class="sidebar-menu">                       │
│   <li class="treeview">                         │
│     <a href="#" class="app-sidebar-link">      │
│       <span>Settings</span>                     │
│     </a>                                        │
│     <ul class="treeview-menu expanded">         │
│       <li>                                      │
│         <a href="#" class="app-sidebar-link">  │
│           <span>General</span>                  │
│         </a>                                    │
│       </li>                                     │
│     </ul>                                       │
│   </li>                                         │
│ </ul>                                           │
└─────────────────────────────────────────────────┘
```

---

## 🎨 Rendering & Visual Hierarchy

### Menu Tanpa Child (Leaf Node)

```php
// Output HTML
<li>
  <a href="/page/view?id=1" class="app-sidebar-link">
    <span class="material-symbols-outlined">dashboard</span>
    <span>Dashboard</span>
  </a>
</li>
```

**Visual:**
```
📊 Dashboard
```

### Menu Dengan Child (Parent/Group)

```php
// Output HTML
<li class="treeview expanded">
  <a href="#" class="app-sidebar-link has-children">
    <span class="material-symbols-outlined">settings</span>
    <span>Settings</span>
    <span class="pull-right-container">
      <i class="fa fa-angle-left"></i>
    </span>
  </a>
  
  <ul class="treeview-menu expanded">
    <!-- Child items rendered here with recursion -->
    <li>
      <a href="/page/view?id=3">General</a>
    </li>
    <li>
      <a href="/page/view?id=4">Account</a>
    </li>
  </ul>
</li>
```

**Visual:**
```
⚙️  Settings ▼
    ├─ General
    ├─ Account
    └─ Notifications
```

---

## 💾 Tipe Menu

### 1. **TYPE_GROUP** (Folder/Container)
```php
const TYPE_GROUP = 'group';
```
- **Kegunaan**: Container untuk child menus
- **Parent_id**: Bisa punya parent (untuk nested groups)
- **Page_id**: NULL (tidak link ke halaman)
- **Route**: NULL
- **Render**: Sebagai collapsible item dengan children

**Contoh:**
```
"Settings" (TYPE_GROUP)
├─ "General" (TYPE_PAGE)
├─ "Account" (TYPE_PAGE)
└─ "Advanced" (TYPE_GROUP)
   ├─ "Database" (TYPE_PAGE)
   └─ "API" (TYPE_ROUTE)
```

### 2. **TYPE_PAGE** (Link ke Halaman)
```php
const TYPE_PAGE = 'page';
```
- **Kegunaan**: Link ke halaman dinamis dari `master_page`
- **Page_id**: REQUIRED (ID halaman target)
- **Route**: NULL
- **URL Resolution**: `/page/view?id={page_id}`

**Contoh:**
```php
[
  'id' => 3,
  'name' => 'Dashboard',
  'type' => 'page',
  'page_id' => 1,
  'parent_id' => null,
  'children' => null
]
```

### 3. **TYPE_ROUTE** (Custom Route)
```php
const TYPE_ROUTE = 'route';
```
- **Kegunaan**: Custom route/URL (misalnya ke controller action)
- **Route**: REQUIRED (URL lengkap)
- **Page_id**: NULL
- **URL Resolution**: Route langsung

**Contoh:**
```php
[
  'id' => 5,
  'name' => 'User Management',
  'type' => 'route',
  'route' => '/user/index',
  'parent_id' => null,
  'children' => null
]
```

---

## 🔍 Contoh Real-World

### Struktur Database Minimal

```sql
-- ROOT LEVEL (parent_id = NULL)
INSERT INTO master_menu (name, icon, type, parent_id, sort_order) VALUES
('Dashboard', 'dashboard', 'page', NULL, 1),
('Pengaturan', 'settings', 'group', NULL, 2),
('Profil', 'person', 'page', NULL, 3);

-- CHILD LEVEL (parent_id = ID menu parent)
INSERT INTO master_menu (name, icon, type, parent_id, sort_order) VALUES
('General', 'tune', 'page', 2, 1),      -- parent_id = 2 (Pengaturan)
('Akun', 'account_circle', 'page', 2, 2),
('Notifikasi', 'notifications', 'page', 2, 3);

-- NESTED LEVEL (parent_id = ID submenu)
INSERT INTO master_menu (name, icon, type, parent_id, sort_order) VALUES
('Database', 'storage', 'page', 2, 4),
('Keamanan', 'security', 'group', 2, 5);

-- Submenu dari "Keamanan"
INSERT INTO master_menu (name, icon, type, parent_id, sort_order) VALUES
('Two Factor Auth', 'verified_user', 'page', <ID Keamanan>, 1);
```

### Output Tree Structure

```json
[
  {
    "id": 1,
    "name": "Dashboard",
    "icon": "dashboard",
    "type": "page",
    "parent_id": null,
    "has_children": false,
    "children": null
  },
  {
    "id": 2,
    "name": "Pengaturan",
    "icon": "settings",
    "type": "group",
    "parent_id": null,
    "has_children": true,
    "children": [
      {
        "id": 3,
        "name": "General",
        "icon": "tune",
        "type": "page",
        "parent_id": 2,
        "has_children": false,
        "children": null
      },
      {
        "id": 4,
        "name": "Akun",
        "icon": "account_circle",
        "type": "page",
        "parent_id": 2,
        "has_children": false,
        "children": null
      },
      {
        "id": 5,
        "name": "Keamanan",
        "icon": "security",
        "type": "group",
        "parent_id": 2,
        "has_children": true,
        "children": [
          {
            "id": 6,
            "name": "Two Factor Auth",
            "icon": "verified_user",
            "type": "page",
            "parent_id": 5,
            "has_children": false,
            "children": null
          }
        ]
      }
    ]
  },
  {
    "id": 7,
    "name": "Profil",
    "icon": "person",
    "type": "page",
    "parent_id": null,
    "has_children": false,
    "children": null
  }
]
```

### Visual Output (Sidebar)

```
📊 Dashboard
⚙️  Pengaturan ▼
    ├─ 🎚️  General
    ├─ 👤 Akun
    ├─ 🔐 Keamanan ▼
    │  └─ ✓️  Two Factor Auth
    └─ 🔔 Notifikasi
👤 Profil
```

---

## 📝 Penggunaan di View/Template

### Dalam Layout (main.php, admin.php, dll)

```php
<?php
use app\components\DynamicSidebar;

// Get sidebar component
$sidebar = new DynamicSidebar();

// Get complete menu tree
$menuTree = $sidebar->getMenuTree();

// Or render directly
echo $sidebar->renderSidebar();
?>
```

### Render Custom

```php
<?php
use app\models\MasterMenu;

// Get tree
$tree = MasterMenu::getMenuTree(true); // true = active only

// Render menggunakan view partial
echo $this->render('_menu-tree', [
    'items' => $tree,
    'activeMenu' => $activeMenu ?? ''
]);
?>
```

### Dalam Controller

```php
<?php
namespace app\controllers;

use app\models\MasterMenu;
use Yii;

class SomeController extends Controller
{
    public function actionIndex()
    {
        // Get active menu tree
        $menuTree = MasterMenu::getMenuTree(true);
        
        // Get specific submenu
        $settingsMenu = MasterMenu::findOne(['name' => 'Settings']);
        $children = $settingsMenu->getChildren()->all();
        
        return $this->render('index', [
            'menuTree' => $menuTree,
            'settingsMenu' => $settingsMenu,
            'children' => $children
        ]);
    }
}
?>
```

---

## 🔧 Tips & Best Practices

### ✅ Best Practices

1. **Gunakan `menu_key` untuk Unique Identifier**
   ```php
   // Good
   'settings' → 'General', 'Account', 'Notifications'
   'profile' → menu tunggal
   ```

2. **Sort Order Otomatis**
   - Setiap menu baru otomatis dapat `sort_order` tertinggi + 1
   - Update sesuai kebutuhan tampilan

3. **Icon dari Material Symbols**
   - Gunakan icon names yang valid dari Material Symbols
   - Fallback ke 'folder' jika tidak ada

4. **URL Resolution Otomatis**
   - TYPE_PAGE: otomatis ke `/page/view?id={page_id}`
   - TYPE_ROUTE: gunakan route langsung
   - TYPE_GROUP: tidak punya URL (# atau javascript:void(0))

5. **Parent-Child Validation**
   ```php
   // Hindari circular reference
   // Menu tidak boleh jadi parent dari ancestor-nya sendiri
   
   // Validasi di MasterMenu model:
   ['parent_id', 'validateNoCircularReference']
   ```

### ⚠️ Common Issues & Solutions

| Issue | Penyebab | Solusi |
|-------|---------|--------|
| Menu tidak tampil | `is_active = 0` | Set `is_active = 1` |
| Submenu tidak nested | `parent_id = NULL` | Set `parent_id` ke menu parent |
| Icon tidak muncul | Icon name invalid | Gunakan valid Material Symbol name |
| Recursive infinite loop | Circular reference | Pastikan tidak ada parent = child |
| Wrong sort order | Manual sort_order | Reset dengan migration atau script |

---

## 🚀 Advanced Features

### 1. Multi-Level Nesting
Sistem support unlimited nesting level:

```
Level 0: Dashboard
Level 1: Settings
Level 2: Advanced
Level 3: Database
Level 4: Connection
Level 5: ... (unlimited)
```

### 2. Conditional Menu Visibility
```php
// Custom logic di renderMenuItems
if ($item['type'] === 'admin-only' && !Yii::$app->user->can('isAdmin')) {
    continue; // Skip rendering
}
```

### 3. Menu Caching
```php
// DynamicSidebar cache menu tree
private $_menuCache = null;

public function getMenuTree()
{
    if ($this->_menuCache !== null) {
        return $this->_menuCache;
    }
    // ... build tree ...
    $this->_menuCache = $tree;
    return $tree;
}
```

### 4. Active Menu Highlighting
```php
// Automatic detection of active menu
private function isActive($item, $currentRoute)
{
    if (!empty($item['url'])) {
        $route = $item['url'];
        if (is_array($route) && isset($route[0])) {
            $routeStr = trim($route[0], '/');
            if (strpos($currentRoute, $routeStr) === 0) {
                return true; // Mark as active
            }
        }
    }
    return false;
}
```

---

## 📚 File References

| File | Fungsi |
|------|--------|
| `models/MasterMenu.php` | Model dengan logic hierarchical |
| `components/DynamicSidebar.php` | Component untuk render sidebar |
| `helpers/MenuBuilder.php` | Helper functions untuk menu |
| `views/layouts/_sidebar.php` | View untuk sidebar |
| `views/layouts/_menu-item.php` | View untuk individual menu item |

---

## ✨ Kesimpulan

Sistem hierarchical menu Anda:
- ✅ **Dinamis**: Fully database-driven, no hardcoding
- ✅ **Fleksibel**: Support unlimited nesting levels
- ✅ **Scalable**: Optimized dengan caching
- ✅ **User-Friendly**: Auto-detect parent-child relations
- ✅ **Production-Ready**: Tested dan robust

Struktur parent-child otomatis di-render sebagai hierarchical navigation yang intuitif dan user-friendly! 🎉
