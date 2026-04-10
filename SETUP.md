# Dynamic Form Builder - Yii2

Sistem Form Builder Dinamis menggunakan Yii2 Framework + MySQL

## Fitur

- ✅ Sistem login (database-backed authentication)
- ✅ Dashboard setelah login
- ✅ Buat form baru
- ✅ Tambah field tanpa reload (JS): text, number, textarea
- ✅ Live preview form builder
- ✅ Hapus field
- ✅ Schema disimpan sebagai JSON
- ✅ Render form dari JSON ke HTML
- ✅ Submit form dan simpan ke database (JSON)
- ✅ Lihat submissions

## Struktur Database

- **users** - id, username, password_hash, auth_key
- **forms** - id, user_id, name, schema_json
- **form_submissions** - id, form_id, user_id, data_json

## Instalasi

### 1. Install Dependencies

```bash
composer install
```

### 2. Setup Database

Buat database MySQL bernama `yii2basic`:

```sql
CREATE DATABASE yii2basic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Konfigurasi Database

Edit file `config/db.php`:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => '', // sesuaikan password MySQL
    'charset' => 'utf8',
];
```

### 4. Jalankan Migrations

```bash
# Windows
yii migrate

# Linux/Mac
./yii migrate
```

Ketik `yes` saat diminta konfirmasi.

Ini akan membuat:
- Tabel `users` (dengan user default: admin/admin123)
- Tabel `forms`
- Tabel `form_submissions`

### 5. Start Web Server

**Option A: PHP Built-in Server**

```bash
# Windows
php -S localhost:8080 -t web

# Linux/Mac  
php -S localhost:8080 -t web/
```

**Option B: XAMPP/WAMP**

- Copy project ke folder htdocs
- Akses via `http://localhost/Yii_dinamic/web/`

### 6. Akses Aplikasi

Buka browser: `http://localhost:8080`

**Login Default:**
- Username: `admin`
- Password: `admin123`

## Cara Pakai

### 1. Login
- Masuk dengan akun admin/admin123

### 2. Dashboard
- Lihat semua form yang sudah dibuat
- Lihat submissions terbaru

### 3. Buat Form Baru
- Klik **Create New Form**
- Isi nama form
- Tambah field:
  - Pilih tipe (text/number/textarea)
  - Isi label
  - Isi name (auto lowercase + underscore)
  - Klik **Add**
- Lihat preview realtime di panel kanan
- Hapus field jika perlu
- Klik **Save Form**

### 4. Render Form
- Klik tombol **Fill** pada form
- Isi field yang tersedia
- Klik **Submit**

### 5. Lihat Submissions
- Klik **View** pada form
- Klik **View All Submissions**

## Struktur Folder

```
Yii_dinamic/
├── assets/
│   ├── AppAsset.php
│   └── FormBuilderAsset.php      # Asset untuk form builder
├── config/
│   ├── web.php                   # Web app config (URL rules)
│   ├── console.php               # Console config (migrations)
│   └── db.php                    # Database config
├── controllers/
│   ├── SiteController.php        # Auth + Dashboard
│   └── FormController.php        # CRUD Form + Render + Submit
├── migrations/
│   ├── m240101_000001_create_users_table.php
│   ├── m240101_000002_create_forms_table.php
│   └── m240101_000003_create_form_submissions_table.php
├── models/
│   ├── User.php                  # DB-backed User model
│   ├── LoginForm.php             # Login form model
│   ├── Form.php                  # Form model
│   └── FormSubmission.php        # Submission model
├── views/
│   ├── layouts/main.php
│   ├── site/
│   │   ├── login.php
│   │   └── dashboard.php
│   └── form/
│       ├── index.php             # List forms
│       ├── create.php            # Form builder (create)
│       ├── update.php            # Form builder (update)
│       ├── view.php              # View form details
│       ├── render.php            # Render form dari JSON
│       └── submissions.php       # Lihat submissions
└── web/
    └── .htaccess                 # URL rewriting
```

## JSON Schema Example

```json
[
  { "type": "text", "label": "Nama", "name": "nama" },
  { "type": "number", "label": "Umur", "name": "umur" },
  { "type": "textarea", "label": "Alamat", "name": "alamat" }
]
```

## Tech Stack

- **Backend**: Yii2 Framework (PHP 7.4+)
- **Database**: MySQL
- **Frontend**: Bootstrap 5, jQuery (via Yii assets), Vanilla JS
- **Auth**: Yii2 User Component (session-based)

## API Endpoints (URL)

- `GET /` - Home (redirect ke dashboard/login)
- `GET /login` - Login page
- `POST /site/logout` - Logout
- `GET /dashboard` - Dashboard
- `GET /form` - List forms
- `GET /form/create` - Form builder
- `POST /form/create` - Save form
- `GET /form/update/<id>` - Edit form
- `POST /form/update/<id>` - Update form
- `GET /form/view/<id>` - View form details
- `GET /form/render/<id>` - Render form
- `POST /form/submit/<id>` - Submit form data
- `GET /form/submissions/<id>` - View submissions

## Default User

Setelah migration, tersedia:
- **Username**: admin
- **Password**: admin123

## Notes

- Semua field required saat submit form
- Field name otomatis lowercase + underscore
- Duplicate field name tidak diperbolehkan
- Schema JSON disimpan di hidden input saat form builder submit
- Submissions disimpan sebagai JSON (flexible schema)
