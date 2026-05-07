# 🔧 Missing Columns Fix & Preventive Guide

## Problem yang Diselesaikan

Error: `UnknownPropertyException: Getting unknown property: app\models\MasterMenu::menu_key`

**Root Cause:** Kolom `menu_key` (dan kolom lain) tidak ada di tabel `master_menu` di database project

## Solusi yang Diimplementasikan

### 1. **Updated MasterMenu Model** (`models/MasterMenu.php`)

```php
public function __get($name)
{
    if (($name === 'icon' || $name === 'menu_key') && !$this->hasAttribute($name)) {
        return null;
    }
    return parent::__get($name);
}
```

Ini membuat model return `null` untuk `icon` dan `menu_key` jika column tidak ada, instead of throwing error.

### 2. **Enhanced DatabaseSchemaInitializer** (`components/DatabaseSchemaInitializer.php`)

Ditambahkan check untuk `menu_key` column dalam `ensureColumnsExist()`:

```php
// Ensure menu_key column di master_menu
if ($schema !== null && !isset($schema->columns['menu_key'])) {
    $this->connection->createCommand()->addColumn(
        'master_menu',
        'menu_key',
        $this->connection->schema->createColumnSchemaBuilder('string', 50)
    )->execute();
}
```

### 3. **New Console Command** (`commands/DbInitController.php`)

**Command Baru:** `actionAddMissingColumns()`

```bash
php yii db-init/add-missing-columns
```

Fungsi:
- Scan semua project databases
- Cek kolom yang missing: `icon`, `menu_key`, `type`, `route`
- Auto-add kolom yang tidak ada
- Report hasil untuk setiap project

---

## Kolom-Kolom yang Dipastikan Ada

### master_menu Table Columns:

| Column | Type | Required | Notes |
|--------|------|----------|-------|
| id | INT PK | ✅ | Auto-generated |
| parent_id | INT | ❌ | Foreign Key |
| page_id | INT | ❌ | Foreign Key |
| name | VARCHAR(100) | ✅ | Menu name |
| icon | VARCHAR(50) | ❌ | Material Design Icon |
| type | VARCHAR(20) | ✅ | group/page/route |
| route | VARCHAR(255) | ❌ | URL route |
| **menu_key** | VARCHAR(50) | ❌ | **Unique menu identifier** |
| sort_order | INT | ✅ | Default 0 |
| order | INT | ✅ | Default 0 |
| is_active | INT | ✅ | Default 1 |
| created_at | TIMESTAMP | ✅ | Auto-set |
| updated_at | TIMESTAMP | ✅ | Auto-update |

---

## Console Commands Reference

### 1. Setup All Projects (Recommended First Time)
```bash
php yii db-init/setup-all
```
✅ Creates missing tables  
✅ Adds missing columns  
✅ Inserts default data  

### 2. Add Missing Columns Only
```bash
php yii db-init/add-missing-columns
```
✅ Adds: `icon`, `menu_key`, `type`, `route`  
✅ Smart skip jika kolom sudah ada  
✅ Fast for existing databases  

### 3. Setup Icon Column
```bash
php yii db-init/setup-icon
```
✅ Legacy command  
✅ Only for `icon` column  

---

## Workflow: Creating New Master Menu Item

Sekarang proses create/update master-menu harus berjalan tanpa error:

```
1. User klik "Buat Menu Baru"
   └─ Form muncul dengan icon picker

2. Isi form:
   ├─ Nama: "Dashboard"
   ├─ Icon: [Pilih dari dropdown]
   ├─ Type: "Page"
   └─ Halaman: "Dashboard"

3. Submit form
   └─ Model validate → save → INSERT ke database

4. ✅ Berhasil! Menu tersimpan dengan icon
```

---

## Preventive Measures

### A. Before Production Deployment

```bash
# 1. Setup semua project databases
php yii db-init/setup-all

# 2. Add missing columns sebagai safety net
php yii db-init/add-missing-columns

# 3. Verify via phpMyAdmin
# - Check master_menu table di setiap project DB
# - Verifikasi kolom: icon, menu_key, type, route ada
```

### B. When Creating New Project

Sekarang otomatis handled:
- ✅ DatabaseSchemaInitializer create all tables
- ✅ All columns included
- ✅ Default data inserted
- ✅ Foreign keys setup

### C. Database Monitoring

Check for missing columns regularly:

```bash
# Via command
php yii db-init/add-missing-columns

# Via SQL (phpMyAdmin)
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'master_menu' AND TABLE_SCHEMA = 'database_name';
```

---

## Error Prevention Checklist

Untuk mencegah error serupa di masa depan:

- [ ] Model `__get()` method handle missing columns gracefully
- [ ] DatabaseSchemaInitializer auto-add missing columns
- [ ] Console command untuk verify & fix columns
- [ ] Rule validation include all used attributes
- [ ] attributeLabels() include all used attributes
- [ ] fields() include all used attributes

---

## Troubleshooting Guide

### Error: "UnknownPropertyException: Getting unknown property"

**Solution:**
1. Jalankan command:
   ```bash
   php yii db-init/add-missing-columns
   ```

2. Clear browser cache & refresh page

3. Test create/update again

### Error: "Column doesn't exist"

**Check:**
```bash
# List all columns
php yii db-init/add-missing-columns

# Or via phpMyAdmin - check table structure
```

### Multiple Missing Columns

**Quick Fix:**
```bash
php yii db-init/setup-all
```
(Command ini smart - hanya add yang missing)

---

## Files Modified/Created

```
✨ CREATED:
   commands/DbInitController.php (console commands)
   
📝 MODIFIED:
   models/MasterMenu.php (__get method)
   components/DatabaseSchemaInitializer.php (ensureColumnsExist)
   
🔄 NO BREAKING CHANGES - Fully backward compatible!
```

---

## Implementation Status

✅ **Model:** `MasterMenu` - Handle missing properties gracefully  
✅ **Database:** `DatabaseSchemaInitializer` - Auto-create columns  
✅ **Commands:** `DbInitController` - Console tools for verification  
✅ **Tested:** All 3 projects successfully fixed  
✅ **Documentation:** Comprehensive guide created  

---

## Future-Proofing

Untuk prevent issues baru:

### 1. Always Use Model Rules
```php
public function rules()
{
    return [
        [['menu_key'], 'string', 'max' => 50], // ✅ Include in rules
    ];
}
```

### 2. Handle Missing Attributes in Model
```php
public function __get($name)
{
    if (!$this->hasAttribute($name)) {
        return null; // ✅ Handle gracefully
    }
    return parent::__get($name);
}
```

### 3. Maintain DatabaseSchemaInitializer
Setiap fitur baru yang perlu kolom database:
- [ ] Add column creation di `createMasterMenuTable()` (or relevant table)
- [ ] Add column check di `ensureColumnsExist()`
- [ ] Update console command help

---

## Command Usage Examples

### Example 1: Fresh Setup
```bash
# Terminal
cd /path/to/project
php yii db-init/setup-all

# Output
=== Database Schema Initialization for All Projects ===
📁 Ditemukan 3 project(s)
Processing: Project A
  ✅ Database berhasil di-setup
✅ Success: 3
```

### Example 2: Just Add Missing Columns
```bash
php yii db-init/add-missing-columns

# Output
=== Add Missing Columns to master_menu ===
Processing: Sekolah (DB: sekolah)
  ✅ Kolom ditambahkan: menu_key
✅ Success: 3
```

### Example 3: One-Off Fix
```bash
# Jika Anda perlu add single column:
# Direct SQL (use dengan hati-hati!)
ALTER TABLE master_menu ADD COLUMN menu_key VARCHAR(50);
```

---

## Testing After Fix

1. **Navigate to:** http://localhost:8080/master-menu/create

2. **Test Create:**
   - Isi form dengan valid data
   - Pilih icon dari dropdown
   - Click Submit
   - Verify sukses (no error, record created)

3. **Test Update:**
   - Navigate ke existing menu
   - Update data
   - Verify sukses

4. **Verify Database:**
   - phpMyAdmin → Check master_menu table
   - Confirm kolom: icon, menu_key ada
   - Check data terisi dengan benar

---

## Support

Untuk masalah:
1. Run `php yii db-init/add-missing-columns`
2. Check console output untuk details
3. Refer ke section Troubleshooting di atas

**Semua error type terkait missing columns seharusnya sudah teratasi!** ✅
