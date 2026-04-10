# Quick Reference Card

## 🚀 Quick Start (3 Steps)

```bash
# 1. Install dependencies
composer install

# 2. Setup database (use phpMyAdmin or MySQL CLI)
mysql -u root -p < database_setup.sql

# 3. Start server
php -S localhost:8080 -t web
```

**Login**: admin / admin123

---

## 📂 Key Files Created

| File | Purpose |
|------|---------|
| `models/User.php` | User authentication |
| `models/Form.php` | Form definition |
| `models/FormSubmission.php` | Form submissions |
| `controllers/SiteController.php` | Login + Dashboard |
| `controllers/FormController.php` | Form CRUD + Render |
| `views/form/create.php` | Form Builder UI |
| `views/form/render.php` | Render form from JSON |

---

## 🗃️ Database Tables

- **users** - User accounts
- **forms** - Form definitions (schema as JSON)
- **form_submissions** - Submitted form data (data as JSON)

---

## 🌐 Main URLs

| URL | Description |
|-----|-------------|
| http://localhost:8080/login | Login |
| http://localhost:8080/dashboard | Dashboard |
| http://localhost:8080/form | List Forms |
| http://localhost:8080/form/create | Create Form |

---

## 📝 JSON Schema Example

```json
[
  { "type": "text", "label": "Nama", "name": "nama" },
  { "type": "number", "label": "Umur", "name": "umur" },
  { "type": "textarea", "label": "Alamat", "name": "alamat" }
]
```

---

## 🔧 Config Files

- `config/db.php` - Database connection
- `config/web.php` - URL routes
- `web/.htaccess` - Pretty URLs

---

## ⚡ Features

✅ Login system (database-backed)  
✅ Dashboard after login  
✅ Create/Edit forms with live preview  
✅ Add fields: text, number, textarea  
✅ Delete fields  
✅ Schema stored as JSON  
✅ Render form from JSON  
✅ Submit & save to database  
✅ View submissions  

---

**Full docs**: See `SETUP.md` and `README_FORM_BUILDER.md`
