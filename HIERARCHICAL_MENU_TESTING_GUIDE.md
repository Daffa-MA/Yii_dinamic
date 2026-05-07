# 🧪 Testing Guide - Hierarchical Menu System

## ✅ Pre-Test Checklist

- [ ] Files sudah ter-create/modify (check di bawah)
- [ ] No PHP compilation errors
- [ ] Database sudah ter-setup dengan tabel `master_menu`
- [ ] `parent_id` column exists di `master_menu` table

---

## 📁 Files Status

### ✨ Created Files:
- `helpers/MasterMenuTreeBuilder.php` - Tree building logic
- `HIERARCHICAL_MENU_IMPLEMENTATION.md` - Full documentation
- `HIERARCHICAL_MENU_QUICK_REFERENCE.md` - Quick reference
- `HIERARCHICAL_MENU_TESTING_GUIDE.md` - This file

### 📝 Modified Files:
- `controllers/MasterMenuController.php` - Enhanced actionIndex()
- `views/master-menu/index.php` - New hierarchical display

### ❌ NOT Modified (Safe):
- Database schema
- Sidebar global display
- Other controllers
- CRUD operations logic

---

## 🚀 Test Steps

### Step 1: Access Master Menu Page
1. Open browser: `http://yourapp.local/master-menu`
2. Should see new hierarchical table layout
3. No errors in console

**Expected:**
- Table with columns: Urutan, Menu, Parent, Tipe, Halaman, Status, Aksi
- Empty state if no data
- "Tambah Menu" button visible

### Step 2: Create Root Menu
1. Click **"Tambah Menu"** button
2. Fill form:
   - **Nama Menu**: `Dashboard`
   - **Icon**: `dashboard`
   - **Tipe**: `group`
   - **Parent**: (leave empty/don't select) ← KEY!
   - **Urutan**: `1`
3. Click **Save**

**Expected:**
- Redirect to Master Menu page
- Success message displayed
- New row visible in table with:
  - Name: "Dashboard"
  - Parent column shows "✓ Root Menu" (green badge)
  - No indentation

### Step 3: Create Submenu
1. Click **"Tambah Menu"** button again
2. Fill form:
   - **Nama Menu**: `Statistics`
   - **Icon**: `analytics`
   - **Tipe**: `page`
   - **Parent**: **Select "Dashboard"** ← CRITICAL!
   - **Halaman**: (select any page)
   - **Urutan**: `1`
3. Click **Save**

**Expected:**
- Redirect to Master Menu page
- Success message displayed
- Two rows now visible:
  ```
  Dashboard (no indent)     [✓ Root Menu]  [2 submenu] badge
    └ Statistics (indented) [→ Dashboard]
  ```

### Step 4: Create More Submenus
1. Repeat Step 3 with:
   - **Nama Menu**: `Settings`
   - **Parent**: `Dashboard`
   - **Urutan**: `2`

2. Repeat Step 3 with:
   - **Nama Menu**: `Users`
   - **Parent**: `Dashboard`
   - **Urutan**: `3`

**Expected:**
- Dashboard now shows `3 submenu` badge
- Three child items below with indentation:
  ```
  Dashboard                     [✓ Root Menu]  [3 submenu]
    └ Statistics (1)            [→ Dashboard]
    └ Settings (2)              [→ Dashboard]
    └ Users (3)                 [→ Dashboard]
  ```

### Step 5: Create Another Root Menu
1. Click **"Tambah Menu"**
2. Fill form:
   - **Nama Menu**: `Profil`
   - **Icon**: `person`
   - **Tipe**: `group`
   - **Parent**: (leave empty)
   - **Urutan**: `2`
3. Click **Save**

**Expected:**
- Profil appears after Dashboard in list
- Shows `✓ Root Menu` badge
- No indentation
- Dashboard still shows 3 submenu children:
  ```
  Dashboard                     [✓ Root Menu]  [3 submenu]
    └ Statistics
    └ Settings
    └ Users
  Profil                        [✓ Root Menu]
  ```

### Step 6: Create Child of Child (Multi-level)
1. Click **"Tambah Menu"**
2. Fill form:
   - **Nama Menu**: `Permissions`
   - **Icon**: `lock`
   - **Tipe**: `page`
   - **Parent**: **Select "Settings"** ← Child of child!
   - **Urutan**: `1`
3. Click **Save**

**Expected:**
- Permissions appears indented UNDER Settings (double indent)
- Visual tree shows:
  ```
  Dashboard                     [✓ Root Menu]  [3 submenu]
    └ Statistics                [→ Dashboard]
    └ Settings                  [→ Dashboard]  [1 submenu]
       └ Permissions (double)   [→ Settings]
    └ Users                     [→ Dashboard]
  ```

---

## 📊 Visual Verification Checklist

### Menu Structure Display
- [ ] Root menus have NO indentation
- [ ] Child menus have indentation (└ prefix)
- [ ] Grand-child menus have double indentation
- [ ] Each level is visually distinct

### Badges & Indicators
- [ ] Root menus show green "✓ Root Menu" badge
- [ ] Root menus with children show blue "X submenu" badge
- [ ] Parent column shows relationship info
- [ ] Type badges show correct colors (Purple/Blue/Green)

### Row Styling
- [ ] Root menu rows have light gray background
- [ ] Child menu rows have lighter gray background
- [ ] Hover effect changes background

### UI Elements
- [ ] All CRUD buttons (Edit, Delete) visible
- [ ] Status toggle buttons work
- [ ] Sort order numbers show correctly
- [ ] Icons display properly

### Summary Footer
- [ ] Shows total menu count
- [ ] Shows root count
- [ ] Shows submenu count
- [ ] Shows active count

---

## 🧪 Functionality Tests

### Test 1: Edit Menu
1. Click Edit button on any menu
2. Change name to "Dashboard Updated"
3. Change parent if desired
4. Click Save
5. Check if changes appear in list

**Expected:**
- Updated name shows in table
- Hierarchy updates if parent changed
- No errors

### Test 2: Toggle Status
1. Click Status toggle button
2. Should change from ON to OFF
3. Row should become semi-transparent with line-through
4. Click again to re-enable

**Expected:**
- Visual change immediate
- Status updates in database

### Test 3: Delete Menu
1. Click Delete button on any submenu (not root)
2. Confirm deletion
3. Check if parent's submenu count decreases

**Expected:**
- Menu removed from list
- Parent still shows correct submenu count
- No errors

### Test 4: Reorder
1. Change sort_order values
2. Refresh page
3. Menu order should reflect sort_order

**Expected:**
- Menus reorder correctly
- Hierarchy maintained
- Order consistent with sort_order field

---

## 🔍 Database Verification

### Check Data Structure
```sql
-- Run in database client:
SELECT 
    id, 
    parent_id, 
    name, 
    icon, 
    type,
    sort_order,
    is_active
FROM master_menu
ORDER BY parent_id, sort_order;
```

**Expected Output Example:**
```
id | parent_id | name         | icon       | type   | sort | active
1  | NULL      | Dashboard    | dashboard  | group  | 1    | 1
2  | 1         | Statistics   | analytics  | page   | 1    | 1
3  | 1         | Settings     | settings   | page   | 2    | 1
4  | 3         | Permissions  | lock       | page   | 1    | 1
5  | 1         | Users        | group      | page   | 3    | 1
6  | NULL      | Profil       | person     | group  | 2    | 1
```

### Check Relations
```php
// In controller or console:
$root = MasterMenu::findOne(1);  // Dashboard
echo $root->name;  // "Dashboard"
echo $root->parent_id;  // null

$child = MasterMenu::findOne(2);  // Statistics
echo $child->name;  // "Statistics"
echo $child->parent_id;  // 1 (Dashboard)
echo $child->parent->name;  // "Dashboard"
```

---

## ⚡ Performance Check

### Query Count
1. Open Master Menu page
2. Open DevTools Network tab
3. Count total HTTP requests
4. Should be minimal (NOT N+1 queries)

**Expected:**
- Single database query with eager-loading
- No additional queries per item
- Page loads quickly (<200ms)

### Large Dataset Test (Optional)
1. Create 100+ menus with hierarchy
2. Master Menu page should still load quickly
3. No timeouts or memory errors

**Expected:**
- Responsive even with large data
- Tree building efficient
- View rendering fast

---

## 🐛 Troubleshooting

### Issue: Submenu not showing under parent

**Check:**
1. Is parent_id set correctly in database?
   ```sql
   SELECT parent_id FROM master_menu WHERE id = <submenu_id>;
   ```
2. Is parent menu ID correct?
   ```sql
   SELECT id, name FROM master_menu WHERE id = <parent_id>;
   ```
3. Are relations eager-loaded in controller?
   - Check: `->with(['parent', 'page'])`

**Fix:**
```bash
php yii db-init/add-missing-columns
```

### Issue: Page not loading / blank

**Check:**
1. Any PHP errors in logs?
   ```bash
   tail -f runtime/logs/app.log
   ```
2. Is MasterMenuTreeBuilder imported?
3. Are tree methods callable?

**Test:**
```php
// In console:
php yii
>>> $menus = \app\models\MasterMenu::find()->all();
>>> $tree = \app\helpers\MasterMenuTreeBuilder::buildTree($menus);
>>> print_r($tree);
```

### Issue: Visual hierarchy looks wrong

**Check:**
1. CSS classes applied correctly?
2. Indentation calculation: `level * 20`px
3. Tree lines displaying?

**Fix:**
- Change indent in view: `padding-left: <?= $level * 24 ?>px;`
- Change tree char: `<?= $i === $level - 1 ? '→' : '  ' ?>`

---

## ✅ Sign-Off Checklist

- [ ] All files created/modified
- [ ] No PHP errors
- [ ] Master Menu page loads
- [ ] Can create root menu (parent = empty)
- [ ] Can create submenu (parent = root)
- [ ] Hierarchy displays correctly
- [ ] Edit/Delete/Status toggle works
- [ ] Summary footer shows correct counts
- [ ] Database has correct parent_id values
- [ ] Performance is good
- [ ] Visual hierarchy is clear

---

## 📞 Final Notes

### If everything passes:
✅ System is **PRODUCTION READY**

### If issues found:
1. Check error logs
2. Verify database schema
3. Ensure relations are eager-loaded
4. Check helper class implementation
5. Debug tree building logic

### Next Steps:
1. Run tests above
2. Fix any issues
3. Deploy to staging
4. Do UAT with actual data
5. Deploy to production

---

## 🔗 References

- Full Docs: `HIERARCHICAL_MENU_IMPLEMENTATION.md`
- Quick Ref: `HIERARCHICAL_MENU_QUICK_REFERENCE.md`
- Code: `helpers/MasterMenuTreeBuilder.php`
- Controller: `controllers/MasterMenuController.php`
- View: `views/master-menu/index.php`

---

**Test Status: READY** ✨
