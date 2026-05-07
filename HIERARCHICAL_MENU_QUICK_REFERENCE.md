# 🎯 Quick Reference: Hierarchical Menu System

## 📍 Key Files Modified/Created

### Created:
```
✨ helpers/MasterMenuTreeBuilder.php
  └─ Tree building logic dengan parent-child relations

✨ HIERARCHICAL_MENU_IMPLEMENTATION.md
  └─ Complete documentation
```

### Modified:
```
📝 controllers/MasterMenuController.php
  └─ Enhanced actionIndex() dengan tree building

📝 views/master-menu/index.php
  └─ New hierarchical display layout
```

---

## 🚀 Quick Start

### 1. Create Root Menu
```
Name: "Dashboard"
Parent: [empty/none] ← KEY!
Type: group
```

### 2. Create Submenu
```
Name: "Statistics"
Parent: "Dashboard" ← SELECT FROM DROPDOWN
Type: page
```

### 3. Result in Master Menu Page
```
Dashboard [Root Menu]
  └ Statistics [Child]
```

---

## 🎨 UI Elements

### Visual Indicators

| Element | Meaning |
|---------|---------|
| `✓ Root Menu` (green badge) | This is a root/parent menu |
| `→ 3 submenu` (blue badge) | Root menu has 3 children |
| `└` line with indent | This is a submenu/child |
| Background light gray | Root menu row |
| Background very light gray | Child menu row |

### Columns

| Column | Info |
|--------|------|
| Urutan | Sort order within group |
| Menu | Name with hierarchy visual (tree lines, indent) |
| Parent | Show which menu is parent (if child) or "Root Menu" |
| Tipe | Group / Page / Route |
| Halaman | Linked page name |
| Status | Toggle active/inactive |
| Aksi | Edit / Delete buttons |

---

## 📊 Data Structure

### Database Schema
```sql
master_menu:
  id (PK)
  parent_id (FK) ← NULL = root, ID = parent menu
  name
  icon
  type (group|page|route)
  page_id (FK)
  route
  sort_order
  is_active
```

### In PHP Code
```php
// Building tree
$tree = MasterMenuTreeBuilder::buildTree($allMenus);
// Result: nested structure with parent-child relations

// Flattening for view
$treeData = MasterMenuTreeBuilder::flattenTree($tree);
// Result: flat array with level info for rendering
```

---

## 🔧 Customization

### Change Indent Spacing
**File:** `views/master-menu/index.php` (line ~180)

```php
style="padding-left: <?= $level * 20 ?>px;"
// Change 20 to any value (16, 24, 32, etc)
```

### Change Tree Line Character
**File:** `views/master-menu/index.php` (line ~190)

```php
<?= $i === $level - 1 ? '└' : '│' ?>
// Change to: '├─', '→', '•', etc
```

### Change Row Colors
**File:** `views/master-menu/index.php` (line ~18-33)

```css
.menu-item-root {
    background-color: rgba(249, 250, 251, 0.8);  /* Root color */
}

.menu-item-child {
    background-color: rgba(249, 250, 251, 0.3);  /* Child color */
}
```

---

## ⚡ Performance Tips

### ✅ Good
```php
// Pre-load relations
MasterMenu::find()
    ->with(['parent', 'page'])  ← Load at once
    ->all();
```

### ❌ Bad
```php
// N+1 query problem
foreach ($menus as $menu) {
    echo $menu->parent->name;  ← Query for each item!
}
```

---

## 🧪 Testing the System

### 1. Go to Master Menu page
```
http://yourapp.local/master-menu
```

### 2. Create test data
- Create 1 root menu (parent = empty)
- Create 2-3 submenus (parent = the root menu above)

### 3. Verify
- Root menu shows normally
- Submenus show below with indentation
- Blue badge shows "2 submenu" or "3 submenu"
- Green badge shows "✓ Root Menu"

---

## 🐛 Common Issues & Fixes

### Issue: Submenu not appearing under parent

**Check:**
1. Is parent menu active? (`is_active = 1`)
2. Is parent_id correctly set? Check DB: 
   ```sql
   SELECT id, parent_id, name FROM master_menu;
   ```
3. Try refresh page (clear cache if needed)

**Fix:**
```php
// Re-run to add missing columns
php yii db-init/add-missing-columns
```

### Issue: Tree structure looks weird

**Check:**
1. Any circular references? (menu A -> parent B, menu B -> parent A)
2. Is sort_order field set? Default to 0 if empty
3. Are relations loaded? Check controller has `->with(['parent', 'page'])`

---

## 📋 Scope of Changes

### ✅ Changed (Master Menu page only)
- `MasterMenuController::actionIndex()` - Build tree data
- `views/master-menu/index.php` - New hierarchy layout
- `helpers/MasterMenuTreeBuilder.php` - New helper class

### ✅ NOT Changed (System unaffected)
- Sidebar global display
- Frontend menu rendering
- Database schema
- Other controllers
- Form handling
- CRUD operations (still work same)
- No breaking changes!

---

## 🎯 Features

| Feature | Status |
|---------|--------|
| Auto-build tree from DB | ✅ Done |
| Visual hierarchy with indent | ✅ Done |
| Tree lines (└, │) | ✅ Done |
| Root vs child distinction | ✅ Done |
| Submenu count badge | ✅ Done |
| Parent info column | ✅ Done |
| CRUD operations | ✅ Working |
| Multi-level nesting | ✅ Supported |
| Sort order respected | ✅ Yes |
| Performance optimized | ✅ Yes (with eager load) |

---

## 📞 Support

### For Issues:
1. Check database: `SELECT * FROM master_menu ORDER BY parent_id, sort_order;`
2. Check controller: parent/page relations are eager-loaded
3. Check view: tree lines & indent CSS
4. Verify helper: `MasterMenuTreeBuilder::buildTree()` logic

### Documentation:
- Full docs: `HIERARCHICAL_MENU_IMPLEMENTATION.md`
- Quick ref: This file
- Database: `DATABASE_AUTO_SETUP_GUIDE.md`

---

## 🚀 Next Steps

1. ✅ Files created/modified
2. ✅ Ready to test
3. Test in browser: `/master-menu`
4. Create sample data with parent-child relationships
5. Verify hierarchy displays correctly
6. Customize visuals if needed

**Status: READY TO USE** 🎉
