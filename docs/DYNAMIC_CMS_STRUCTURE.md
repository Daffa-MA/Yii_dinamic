# Dynamic CMS Database Structure

## 📋 Ringkasan Struktur

Sistem Dynamic CMS ini terdiri dari 3 tabel utama dengan relasi parent-child:

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   menus     │       │   pages     │       │   forms     │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id          │       │ id          │       │ id          │
│ name        │       │ title       │       │ name        │
│ parent_id   │◄──────│ slug        │       │ ...         │
│ type        │       │ layout      │       └─────────────┘
│ page_id     │◄──────│ description │            ▲
│ route       │       │ is_active   │            │
│ order       │       └─────────────┘            │
│ is_active   │                ▲                 │
└─────────────┘                │                 │
       │                        │                 │
       │              ┌──────────┴──────────┐      │
       │              │    page_forms      │      │
       │              ├────────────────────┤      │
       └──────────────│ page_id (FK)      │──────┘
                      │ form_id (FK)      │
                      │ order             │
                      └───────────────────┘
```

---

## 1. Table: menus (master_menu)

### Struktur:
| Column | Type | Nullable | Keterangan |
|--------|------|----------|------------|
| id | INT | NO | Primary Key, Auto Increment |
| parent_id | INT | YES | Self-reference ke menus.id |
| type | ENUM('group','page','route') | NO | Jenis menu |
| page_id | INT | YES | FK ke pages.id |
| route | VARCHAR(255) | YES | URL custom |
| name | VARCHAR(100) | NO | Nama menu |
| icon | VARCHAR(50) | YES | Icon material symbols |
| sort_order | INT | NO | Urutan lama |
| order | INT | NO | Urutan baru |
| is_active | TINYINT(1) | NO | Status aktif (1/0) |
| created_at | TIMESTAMP | NO | Waktu dibuat |
| updated_at | TIMESTAMP | NO | Waktu update |

### Type Rules:
- **Group**: Tidak boleh punya page_id atau route. Untuk menu dropdown/parent.
- **Page**: Wajib punya page_id. Mengarah ke halaman dinamis.
- **Route**: Wajib punya route. URL langsung ke controller/action.

### Foreign Keys:
- `parent_id` → `menus(id)` ON DELETE SET NULL ON UPDATE CASCADE
- `page_id` → `pages(id)` ON DELETE SET NULL ON UPDATE CASCADE

---

## 2. Table: pages (master_page)

### Struktur:
| Column | Type | Nullable | Keterangan |
|--------|------|----------|------------|
| id | INT | NO | Primary Key, Auto Increment |
| title | VARCHAR(255) | NO | Judul halaman |
| slug | VARCHAR(100) | YES | Unique URL slug |
| layout | VARCHAR(50) | YES | Layout type (default/list/form/dashboard/blank) |
| description | TEXT | YES | Deskripsi halaman |
| layout_type | VARCHAR(50) | YES | Legacy field |
| is_active | TINYINT(1) | NO | Status aktif (1/0) |
| created_at | TIMESTAMP | NO | Waktu dibuat |
| updated_at | TIMESTAMP | NO | Waktu update |

### Layout Options:
- `default` - Layout default
- `list` - Tampilan list/tabel
- `form` - Tampilan form
- `dashboard` - Tampilan dashboard
- `blank` - Kosong (bisa dikustom)
- `two_column` - Dua kolom

---

## 3. Table: page_forms (Relasi Many-to-Many)

### Struktur:
| Column | Type | Nullable | Keterangan |
|--------|------|----------|------------|
| id | INT | NO | Primary Key, Auto Increment |
| page_id | INT | NO | FK ke pages.id |
| form_id | INT | NO | FK ke forms.id |
| order | INT | NO | Urutan tampil form di halaman |
| created_at | TIMESTAMP | NO | Waktu dibuat |

### Foreign Keys:
- `page_id` → `pages(id)` ON DELETE CASCADE ON UPDATE CASCADE
- `form_id` → `forms(id)` ON DELETE CASCADE ON UPDATE CASCADE

---

## 🔗 Relasi

### 1. Menu → Page (Many-to-One)
```
Menu.type = 'page' → Menu.page_id → Page.id
```
Contoh: Menu "Data Siswa" type=page, page_id=2 → ke Page id=2

### 2. Menu → Route (Direct)
```
Menu.type = 'route' → Menu.route = '/site/dashboard'
```
Contoh: Menu "Dashboard" type=route, route=/site/dashboard

### 3. Menu → Child Menu (Self-Reference)
```
Menu.parent_id → Menu.id
```
Contoh: Menu "Data Siswa" parent_id=1 (Data Master)

### 4. Page → Forms (One-to-Many via page_forms)
```
Page.id → page_forms.page_id → page_forms.form_id → Form.id
```
Contoh: Page "Data Siswa" punya 2 form (Form absensi, Form nilai)

---

## 📝 Contoh Data

### Pages:
| id | title | slug | layout |
|----|-------|------|--------|
| 1 | Dashboard | dashboard | dashboard |
| 2 | Data Siswa | data-siswa | list |
| 3 | Data Guru | data-guru | list |
| 4 | Absensi | absensi | form |
| 5 | Nilai Siswa | nilai-siswa | list |

### Menus:
| id | name | type | parent_id | page_id | route | order |
|----|------|------|------------|---------|-------|-------|
| 1 | Dashboard | route | NULL | NULL | /site/dashboard | 1 |
| 2 | Data Master | group | NULL | NULL | NULL | 2 |
| 3 | Akademik | group | NULL | NULL | NULL | 3 |
| 4 | Data Siswa | page | 2 | 2 | NULL | 1 |
| 5 | Data Guru | page | 2 | 3 | NULL | 2 |
| 6 | Absensi | page | 3 | 4 | NULL | 1 |
| 7 | Nilai Siswa | page | 3 | 5 | NULL | 2 |

---

## ⚡ Cara Penggunaan

### 1. Buat Halaman Baru
```sql
INSERT INTO master_page (title, slug, layout, description, is_active) 
VALUES ('Laporan Bulanan', 'laporan-bulanan', 'list', 'Laporan bulanan akademik', 1);
```

### 2. Buat Menu (Type: Page)
```sql
INSERT INTO master_menu (name, type, page_id, icon, order, is_active) 
VALUES ('Laporan Bulanan', 'page', 6, 'assessment', 5, 1);
```

### 3. Buat Menu (Type: Route)
```sql
INSERT INTO master_menu (name, type, route, icon, order, is_active) 
VALUES ('Settings', 'route', '/site/settings', 'settings', 10, 1);
```

### 4. Buat Menu Parent (Group)
```sql
INSERT INTO master_menu (name, type, icon, order, is_active) 
VALUES ('Pengaturan', 'group', 'settings', 20, 1);
-- Kemudian buat submenu dengan parent_id = id menu Pengaturan
```

### 5. Link Form ke Page
```sql
INSERT INTO page_forms (page_id, form_id, order) 
VALUES (2, 1, 1);
```

---

## 🔒 Constraint & Validation

Di application level (Model):
1. **Menu Type = 'group'**: page_id = NULL, route = NULL
2. **Menu Type = 'page'**: page_id = WAJIB, route = NULL
3. **Menu Type = 'route'**: route = WAJIB, page_id = NULL

Di database level:
- Foreign keys untuk referential integrity
- Unique index pada pages.slug
- Indexes untuk performance (parent_id, type, is_active, order)

---

## 🚀 Fitur yang Didukung

1. ✅ Menu dinamis dari database
2. ✅ Parent-child hierarchy (submenu)
3. ✅ Tiga tipe menu: Group, Page, Route
4. ✅ Halaman dinamis dengan layout berbeda
5. ✅ Multiple forms per halaman dengan urutan
6. ✅ Toggle aktif/non-aktif menu dan halaman
7. ✅ URL slug untuk pages
8. ✅ Icon support untuk menu

---

## 📁 File Penting

- Model: `models/MasterMenu.php`, `models/MasterPage.php`, `models/PageForms.php`
- Controller: `controllers/MasterMenuController.php`, `controllers/MasterPageController.php`
- View: `views/master-menu/`, `views/master-page/`
- Sidebar: `views/layouts/_sidebar.php`