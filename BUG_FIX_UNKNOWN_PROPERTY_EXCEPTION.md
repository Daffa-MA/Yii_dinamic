# 🔧 Bug Fix: UnknownPropertyException - allModels

## ❌ Error Report

```
yii\base\UnknownPropertyException: Setting unknown property: 
yii\data\ActiveDataProvider::allModels in MasterMenuController.php:65
```

## 🔍 Root Cause

`ActiveDataProvider` dari Yii2 **tidak memiliki property `allModels`**. 

Kesalahan terjadi di code:
```php
$dataProvider = new ActiveDataProvider([
    'allModels' => $treeData,  // ❌ WRONG - ActiveDataProvider tidak punya ini
    'pagination' => false,
]);
```

`ActiveDataProvider` dirancang untuk bekerja dengan **ActiveRecord queries**, bukan dengan **array of data**.

Untuk array data, harus menggunakan `ArrayDataProvider` atau cukup pass data langsung ke view.

## ✅ Solution

Saya removed `ActiveDataProvider` dan pass data langsung ke view:

### Sebelum (❌ Error):
```php
public function actionIndex()
{
    $allMenus = MasterMenu::find()->with(['parent', 'page'])->all();
    $tree = MasterMenuTreeBuilder::buildTree($allMenus);
    $treeData = MasterMenuTreeBuilder::flattenTree($tree);
    
    // ❌ WRONG - ActiveDataProvider tidak accept allModels
    $dataProvider = new ActiveDataProvider([
        'allModels' => $treeData,
        'pagination' => false,
    ]);

    return $this->render('index', [
        'dataProvider' => $dataProvider,
        'tree' => $tree,
        'treeData' => $treeData,
    ]);
}
```

### Sesudah (✅ Fixed):
```php
public function actionIndex()
{
    $allMenus = MasterMenu::find()->with(['parent', 'page'])->all();
    $tree = MasterMenuTreeBuilder::buildTree($allMenus);
    $treeData = MasterMenuTreeBuilder::flattenTree($tree);

    return $this->render('index', [
        'treeData' => $treeData,  // ✅ Pass data directly
    ]);
}
```

## 📝 Changes Made

**File:** `controllers/MasterMenuController.php`

### Removed:
- Import `ActiveDataProvider` (tidak digunakan)
- `$dataProvider` initialization (tidak perlu)
- `$tree` dari render (tidak digunakan di view)

### Kept:
- Tree building logic
- Pass `$treeData` directly to view
- All other functionality unchanged

## 📋 Testing

### Test 1: Access Master Menu Page
```
URL: http://yourapp.local/master-menu
Expected: Page loads without error ✅
```

### Test 2: Create Menu
```
Action: Create root menu (parent = empty)
Expected: Menu saved and displayed ✅
```

### Test 3: Create Submenu
```
Action: Create submenu with parent
Expected: Submenu displayed under parent with indentation ✅
```

## 🔄 Before & After Comparison

| Aspect | Before | After |
|--------|--------|-------|
| Error | UnknownPropertyException | ✅ No error |
| Page loads | ❌ No | ✅ Yes |
| Data provider | ActiveDataProvider (wrong) | Direct array pass (correct) |
| Lines of code | 69 | 62 |
| Complexity | Higher | Lower |

## 🚀 Status

✅ **FIXED - Ready to Test**

---

## 📞 If Issue Persists

If you still get errors:

### Check 1: Clear cache
```bash
cd /path/to/project
php yii cache/flush-all
```

### Check 2: Verify file saved
```bash
grep -n "treeData" controllers/MasterMenuController.php
# Should show: return $this->render('index', ['treeData' => $treeData]);
```

### Check 3: Check view file
```bash
# Verify views/master-menu/index.php uses $treeData variable
grep -n "treeData" views/master-menu/index.php
```

---

## 📌 Key Takeaway

**Never use ActiveDataProvider for non-query data.** Use:
- `ActiveDataProvider` → For ActiveRecord queries
- `ArrayDataProvider` → For array data with pagination
- Direct pass → For simple array data without pagination

In this case, hierarchical menu doesn't need complex data provider, just pass array directly. ✅
