# Database Schema Auto-Setup Guide

## Overview

Sistem ini otomatis membuat dan menginisialisasi struktur database lengkap untuk setiap project baru tanpa perlu setup manual. Setiap database project akan langsung memiliki:

- ✅ Tabel `master_menu` dengan kolom `icon`
- ✅ Tabel `master_page` 
- ✅ Tabel `page_forms`
- ✅ Foreign keys dan indexes
- ✅ Data default (menu dan pages)

---

## Komponen Utama

### 1. **DatabaseSchemaInitializer** (`components/DatabaseSchemaInitializer.php`)

Component yang bertanggung jawab untuk inisialisasi otomatis struktur database.

**Method Utama:**
- `initializeProjectDatabase(string $databaseName)` - Entry point untuk inisialisasi database baru

**Fitur:**
- Membuat tabel `master_menu`, `master_page`, `page_forms` secara otomatis
- Menambahkan kolom `icon` ke `master_menu`
- Setup indexes dan foreign keys
- Insert data default (menu dan pages)
- Backward compatible dengan existing databases

### 2. **ProjectController Enhancement** (`controllers/ProjectController.php`)

Diupdate untuk menggunakan `DatabaseSchemaInitializer`:

```php
// Ketika project baru dibuat:
DatabaseSchemaInitializer::initializeProjectDatabase($databaseName);
```

Alur:
1. User membuat project baru
2. Database baru dibuat di MySQL
3. `DatabaseSchemaInitializer` otomatis membuat semua tabel dan data
4. Project langsung siap digunakan

### 3. **Console Commands** (`commands/DbInitController.php`)

Untuk setup existing projects yang belum lengkap.

---

## Cara Penggunaan

### A. Project Baru (Otomatis)

Ketika user membuat project baru via interface:

1. Klik "Buat Project Baru"
2. Isi nama project
3. Klik "Simpan dan Gunakan"
4. ✅ Database otomatis dibuat dan di-setup lengkap

**Tidak perlu manual setup lagi!**

---

### B. Setup Existing Projects (Manual)

Jika Anda sudah punya project yang belum ter-setup dengan benar:

#### Option 1: Setup Semua Projects Sekaligus

```bash
php yii db-init/setup-all
```

Output:
```
=== Database Schema Initialization for All Projects ===

📁 Ditemukan 3 project(s)

Processing: Project A (ID: 1, DB: project_a)
  ✅ Database berhasil di-setup

Processing: Project B (ID: 2, DB: project_b)
  ✅ Database berhasil di-setup

Processing: My Store (ID: 3, DB: my_store)
  ✅ Database berhasil di-setup

=== Setup Complete ===
✅ Success: 3
```

#### Option 2: Hanya Tambahkan Kolom Icon

Jika tabel sudah ada tapi hanya kolom `icon` yang kurang:

```bash
php yii db-init/setup-icon
```

Output:
```
=== Add Icon Column to master_menu ===

📁 Ditemukan 3 project(s)

Processing: Project A (ID: 1, DB: project_a)
  ✅ Kolom icon berhasil ditambahkan

Processing: Project B (ID: 2, DB: project_b)
  ℹ️  Kolom icon sudah ada, skip...

Processing: My Store (ID: 3, DB: my_store)
  ✅ Kolom icon berhasil ditambahkan

=== Setup Complete ===
✅ Success: 2
ℹ️  Skipped: 1
```

---

## Struktur Database yang Dibuat

### Tabel `master_menu`

```sql
CREATE TABLE master_menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT,
    page_id INT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),  -- ✨ KOLOM BARU
    type VARCHAR(20) DEFAULT 'page',
    route VARCHAR(255),
    menu_key VARCHAR(50),
    sort_order INT DEFAULT 0,
    order INT DEFAULT 0,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_parent_id (parent_id),
    KEY idx_type (type),
    KEY idx_page_id (page_id),
    KEY idx_is_active (is_active),
    KEY idx_sort_order (sort_order),
    KEY idx_order (order),
    
    FOREIGN KEY fk_parent (parent_id) REFERENCES master_menu(id) ON DELETE SET NULL,
    FOREIGN KEY fk_page (page_id) REFERENCES master_page(id) ON DELETE SET NULL
);
```

### Tabel `master_page`

```sql
CREATE TABLE master_page (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    layout VARCHAR(50) DEFAULT 'default',
    description TEXT,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_slug (slug),
    KEY idx_is_active (is_active)
);
```

### Tabel `page_forms`

```sql
CREATE TABLE page_forms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    form_id INT NOT NULL,
    order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_page_id (page_id),
    KEY idx_form_id (form_id),
    KEY idx_order (order),
    
    FOREIGN KEY fk_page (page_id) REFERENCES master_page(id) ON DELETE CASCADE,
    FOREIGN KEY fk_form (form_id) REFERENCES forms(id) ON DELETE CASCADE
);
```

---

## Data Default yang Di-Insert

### Default Pages
1. **Dashboard** (slug: dashboard)
2. **Profil** (slug: profil)
3. **Layanan** (slug: layanan)
4. **Kontak** (slug: kontak)
5. **Artikel** (slug: artikel)

### Default Menus
```
📦 Dashboard (icon: dashboard) → Page 1
📦 Profil (icon: person) → Page 2
📦 Layanan (icon: shopping_cart) → Page 3
📦 Kontak (icon: mail) → Page 4
📦 Artikel (icon: article) → Page 5
📁 Pengaturan (Group)
   ├─ General (icon: tune) → /settings/general
   ├─ Akun (icon: account_circle) → /settings/account
   └─ Notifikasi (icon: notifications) → /settings/notifications
```

---

## Available Icons

Kolom `icon` menggunakan Material Design Icons. Available icons:

```
dashboard, home, settings, person, group, description, article,
folder, folder_open, insert_drive_file, image, video_library,
build, analytics, assessment, inbox, mail, shopping_cart, payment,
inventory, store, notifications, chat, help, info, check_circle,
schedule, calendar_today, visibility, edit, delete, add, search,
download, upload, link, share, lock, public, menu, list, grid_view,
code, terminal, extension, widgets, category, pie_chart, bar_chart,
timeline, school, groups, event_available, grade, tune, 
account_circle, dan lainnya...
```

---

## Alur Proses

### Ketika Project Baru Dibuat

```
┌─ User Creates Project
│
├─ ProjectController::actionCreate()
│
├─ Save Project Model (ke metadata DB)
│
├─ ActiveDatabaseContext::createDatabase()
│  └─ CREATE DATABASE project_name
│
├─ DatabaseSchemaInitializer::initializeProjectDatabase()
│  │
│  ├─ createAllTables()
│  │  ├─ createMasterPageTable()
│  │  ├─ createMasterMenuTable()
│  │  └─ createPageFormsTable()
│  │
│  ├─ ensureColumnsExist()
│  │  └─ Add icon column if missing
│  │
│  └─ insertDefaultData()
│     ├─ insertDefaultPages()
│     └─ insertDefaultMenus()
│
└─ Set Active Project → Dashboard
```

---

## Troubleshooting

### Problem: Column 'icon' doesn't exist

**Solution:**
```bash
php yii db-init/setup-icon
```

### Problem: Tabel tidak ada di database baru

**Solution:**
```bash
php yii db-init/setup-all
```

### Problem: Foreign keys error

Ensure:
1. `master_page` table exists
2. `master_menu` table exists
3. InnoDB engine is used
4. Both tables dalam satu database

---

## Best Practices

### 1. Sebelum Production

Jalankan setup command untuk memastikan semua project sudah lengkap:

```bash
php yii db-init/setup-all
```

### 2. Monitoring

Periksa apakah kolom `icon` ada di setiap database:

```bash
# Via command line
php yii db-init/setup-icon

# Via phpMyAdmin
SELECT * FROM master_menu WHERE icon IS NULL;
```

### 3. Backup Sebelum Migrasi

Jika Anda punya banyak project:

```bash
# Backup all project databases
mysqldump -u root --databases project_1 project_2 > backup.sql
```

---

## Migration Path untuk Existing Systems

### Scenario 1: Sudah Ada Database Tapi Icon Belum Ada

```bash
php yii db-init/setup-icon
```

### Scenario 2: Tidak Ada master_menu Table

```bash
php yii db-init/setup-all
```

### Scenario 3: Partial Setup (Beberapa Ada, Beberapa Tidak)

```bash
php yii db-init/setup-all
# Command ini smart - hanya setup yang belum ada
```

---

## File-File yang Dimodifikasi/Dibuat

```
✨ NEW FILES:
├── components/DatabaseSchemaInitializer.php    (auto-setup logic)
└── commands/DbInitController.php                (console commands)

📝 MODIFIED FILES:
├── controllers/ProjectController.php            (use initializer)
└── models/MasterMenu.php                        (icon field added)

🔄 NO BREAKING CHANGES - Fully backward compatible!
```

---

## FAQ

**Q: Apakah existing project yang tidak ter-setup akan error?**
A: Ya, sampai Anda jalankan `php yii db-init/setup-icon` atau `php yii db-init/setup-all`

**Q: Bisakah saya customize default menus?**
A: Ya, edit method `insertDefaultMenus()` di `DatabaseSchemaInitializer.php`

**Q: Apakah ini works dengan SQLite?**
A: Sistem ini terutama untuk MySQL. Untuk SQLite, perlu penyesuaian terpisah.

**Q: Bagaimana jika icon column sudah ada tapi kosong?**
A: Data masih bisa disimpan, tapi sidebar tidak akan menampilkan icon. Update manual atau jalankan update script.

---

## Support

Untuk update atau modifikasi, hubungi dev team atau edit file initializer sesuai kebutuhan.
