# Dynamic CMS Backend Services

## 📦 Services Architecture

```
Controller → Service → Model/Database
```

### MenuService (`services/MenuService.php`)
Bertanggung jawab untuk operasi CRUD menu dengan validasi lengkap.

#### Method:
```php
$service = new \app\services\MenuService();

// 1. Validate menu before save
$validation = $service->validateMenu($menuModel);
// Returns: ['success' => bool, 'errors' => []]

// 2. Create menu with validation
$result = $service->createMenu($data);
// $data = ['name' => '...', 'type' => 'group|page|route', 'page_id' => ..., 'route' => '...', ...]
// Returns: ['success' => bool, 'model' => $menu, 'message' => '...', 'errors' => []]

// 3. Update menu
$result = $service->updateMenu($id, $data);

// 4. Delete menu (cascade children will be set to parent_id = null)
$result = $service->deleteMenu($id);

// 5. Toggle status
$result = $service->toggleStatus($id);

// 6. Get menu tree for sidebar
$tree = $service->getMenuTree($activeOnly = true);

// 7. Reorder menus
$result = $service->reorder([['id' => 1, 'order' => 1], ...]);
```

#### Validasi Rules:
- **Group**:Tidak boleh ada `page_id` atau `route`
- **Page**:Wajib ada `page_id`, tidak boleh ada `route`
- **Route**:Wajib ada `route`, tidak boleh ada `page_id`
- **Parent**:Tidak boleh sama dengan `id` sendiri
- **Circular**:Tidak boleh ada circular parent (A → B → A)

---

### PageService (`services/PageService.php`)
Bertanggung jawab untuk operasi halaman dan relasi dengan form.

#### Method:
```php
$service = new \app\services\PageService();

// 1. Get layout options
$layouts = $service::getLayoutOptions();
// Returns: ['default' => 'Default', 'list' => 'List View', ...]

// 2. Validate page
$validation = $service->validatePage($pageModel);

// 3. Create page with forms
$result = $service->createPage($data, $formIds = []);
// $formIds = [1, 2, 3] (array of form IDs)

// 4. Update page with form sync
$result = $service->updatePage($id, $data, $formIds = []);

// 5. Sync forms (delete old + insert new)
$service->syncForms($pageId, $formIds);

// 6. Add single form to page
$result = $service->addFormToPage($pageId, $formId, $order = 0);

// 7. Remove form from page
$result = $service->removeFormFromPage($pageId, $formId);

// 8. Get forms for a page
$forms = $service->getPageForms($pageId);

// 9. Delete page (page_forms deleted automatically by CASCADE)
$result = $service->deletePage($id);

// 10. Toggle status
$result = $service->toggleStatus($id);

// 11. Get active pages for dropdown
$pages = $service::getActivePagesList();
// Returns: [1 => 'Dashboard', 2 => 'Data Siswa', ...]
```

---

## 🔄 Usage in Controllers

### MasterMenuController
```php
public function actionCreate()
{
    $result = $this->menuService->createMenu(Yii::$app->request->post());
    
    if ($result['success']) {
        Yii::$app->session->setFlash('success', $result['message']);
    } else {
        Yii::$app->session->setFlash('error', implode('<br>', $result['errors']));
    }
}
```

### MasterPageController
```php
public function actionCreate()
{
    $formIds = Yii::$app->request->post('formIds', []);
    $result = $this->pageService->createPage(Yii::$app->request->post(), $formIds);
    
    // Form sync handled automatically
}
```

---

## ✅ Benefits

1. **Clean Validation**: Semua rules di satu tempat (service)
2. **Reusable**: Bisa dipakai di console, API, atau controller lain
3. **Testable**: Mudah di-unit test
4. **Scalable**: Tambah validasi baru cukup di service
5. **Error Handling**: Konsisten return format `['success' => bool, 'errors' => [], ...]`

---

## 📁 File Structure

```
Yii_dinamic/
├── services/
│   ├── MenuService.php      ← Menu operations
│   └── PageService.php       ← Page + Form operations
├── controllers/
│   ├── MasterMenuController.php   ← Uses MenuService
│   └── MasterPageController.php    ← Uses PageService
├── models/
│   ├── MasterMenu.php
│   ├── MasterPage.php
│   └── PageForms.php
└── docs/
    └── CMS_SERVICES.md
```

---

## 🧪 Testing

```bash
# Run service tests
php test_services.php

# Output:
# 1. Testing MenuService...
#    ✓ MenuService loaded
#    ✓ Validation works
# 
# 2. Testing PageService...
#    ✓ PageService loaded
#    ✓ Layout options: 6 layouts
# 
# 3. Testing PageForms model...
#    ✓ PageForms model works
```

---

## 🚀 Next Steps

1. Tambahkan unit tests untuk service
2. Tambahkan event/hook system (beforeCreate, afterSave, etc.)
3. Tambahkan caching untuk getMenuTree
4. Tambahkan logging untuk audit trail
5. Tambahkan API endpoint jika needed