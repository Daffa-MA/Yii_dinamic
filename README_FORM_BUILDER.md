# Dynamic Form Builder - Complete Code Structure

## 📁 Complete File List

### Database & Migrations
```
✅ migrations/m240101_000001_create_users_table.php
✅ migrations/m240101_000002_create_forms_table.php
✅ migrations/m240101_000003_create_form_submissions_table.php
✅ database_setup.sql (Manual SQL script)
```

### Models
```
✅ models/User.php - Database-backed User model with auth
✅ models/LoginForm.php - Login form validation
✅ models/Form.php - Form definition model
✅ models/FormSubmission.php - Form submission model
```

### Controllers
```
✅ controllers/SiteController.php - Login, Logout, Dashboard
✅ controllers/FormController.php - CRUD Form + Render + Submit
```

### Views
```
✅ views/layouts/main.php - Updated navbar (Dashboard, Forms)
✅ views/site/login.php - Login page
✅ views/site/dashboard.php - Dashboard with forms list
✅ views/form/index.php - List all forms
✅ views/form/create.php - Form Builder (Create)
✅ views/form/update.php - Form Builder (Update)
✅ views/form/view.php - View form details
✅ views/form/render.php - Render form from JSON
✅ views/form/submissions.php - View all submissions
```

### Assets & Config
```
✅ assets/AppAsset.php - Main assets
✅ assets/FormBuilderAsset.php - Form builder assets
✅ config/web.php - URL rules, pretty URLs enabled
✅ config/console.php - Migration configuration
✅ web/.htaccess - URL rewriting
```

### Documentation
```
✅ SETUP.md - Complete setup guide
✅ README_FORM_BUILDER.md - This file
✅ setup.bat - Windows setup script
```

---

## 🚀 Quick Start

### 1. Install Dependencies
```bash
composer install
```

### 2. Setup Database

**Option A: Using SQL Script (Recommended)**
```bash
# Open phpMyAdmin or MySQL CLI
mysql -u root -p < database_setup.sql
```

**Option B: Using Yii Migration (if PDO driver available)**
```bash
php yii migrate
```

### 3. Configure Database
Edit `config/db.php`:
```php
'dsn' => 'mysql:host=localhost;dbname=yii2basic',
'username' => 'root',
'password' => 'your_password',
```

### 4. Start Server
```bash
php -S localhost:8080 -t web
```

### 5. Login
- URL: http://localhost:8080
- Username: `admin`
- Password: `admin123`

---

## 🗂️ Database Schema

### users
```sql
id              INT PRIMARY KEY
username        VARCHAR(100) UNIQUE
password_hash   VARCHAR(255)
auth_key        VARCHAR(32)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### forms
```sql
id              INT PRIMARY KEY
user_id         INT (FK -> users.id)
name            VARCHAR(255)
schema_json     TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### form_submissions
```sql
id              INT PRIMARY KEY
form_id         INT (FK -> forms.id)
user_id         INT (FK -> users.id)
data_json       TEXT
created_at      TIMESTAMP
```

---

## 🔌 URL Routes

| URL | Action |
|-----|--------|
| `/` | Home (redirect to dashboard/login) |
| `/login` | Login page |
| `/dashboard` | Dashboard |
| `/form` | List all forms |
| `/form/create` | Create form (builder) |
| `/form/update/1` | Update form #1 |
| `/form/view/1` | View form #1 details |
| `/form/render/1` | Render form #1 for filling |
| `/form/submit/1` | Submit form #1 data |
| `/form/submissions/1` | View all submissions for form #1 |

---

## 📝 JSON Schema Example

Stored in `forms.schema_json`:

```json
[
  {
    "type": "text",
    "label": "Nama Lengkap",
    "name": "nama_lengkap"
  },
  {
    "type": "number",
    "label": "Umur",
    "name": "umur"
  },
  {
    "type": "textarea",
    "label": "Alamat",
    "name": "alamat"
  }
]
```

Submission data stored in `form_submissions.data_json`:

```json
{
  "nama_lengkap": "John Doe",
  "umur": "25",
  "alamat": "Jl. Contoh No. 123"
}
```

---

## 🎨 Form Builder Features

### Left Panel (Builder)
- Form name input
- Field type selector (text/number/textarea)
- Label & Name inputs
- Add Field button
- Fields list with delete button

### Right Panel (Live Preview)
- Real-time form preview
- Updates when fields are added/removed
- Shows exactly how the form will look

### JavaScript Features
- ✅ Add field without page reload
- ✅ Remove field with confirmation
- ✅ Duplicate name validation
- ✅ Auto lowercase + underscore for field names
- ✅ Live preview rendering
- ✅ JSON serialization to hidden input

---

## 🔐 Authentication Flow

1. User visits `/login`
2. Enters username & password
3. `LoginForm` validates against `users` table
4. Password verified using `Yii::$app->security->validatePassword()`
5. Session created via `Yii::$app->user->login()`
6. Redirected to `/dashboard`
7. Access control via `AccessControl` filter on protected routes

---

## 📊 Code Highlights

### Form Controller - Submit Action
```php
public function actionSubmit($id)
{
    $model = $this->findModel($id);
    $schema = $model->getSchema();

    if (Yii::$app->request->isPost) {
        $data = [];
        foreach ($schema as $field) {
            $name = $field['name'];
            $data[$name] = Yii::$app->request->post($name, '');
        }

        $submission = new FormSubmission();
        $submission->form_id = $id;
        $submission->user_id = Yii::$app->user->id;
        $submission->data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($submission->save()) {
            Yii::$app->session->setFlash('success', 'Form submitted!');
            return $this->redirect(['render', 'id' => $id]);
        }
    }
}
```

### Render Form from JSON
```php
<?php foreach ($schema as $field): ?>
    <div class="mb-3">
        <?= Html::label($field['label'], $field['name'], ['class' => 'form-label']) ?>
        
        <?php if ($field['type'] === 'text'): ?>
            <?= Html::input('text', $field['name'], null, [
                'class' => 'form-control',
                'required' => true
            ]) ?>
        <?php elseif ($field['type'] === 'number'): ?>
            <?= Html::input('number', $field['name'], null, [
                'class' => 'form-control',
                'required' => true
            ]) ?>
        <?php elseif ($field['type'] === 'textarea'): ?>
            <?= Html::textarea($field['name'], null, [
                'class' => 'form-control',
                'rows' => 4,
                'required' => true
            ]) ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
```

---

## ✅ Default Credentials

After running migration:
- **Username**: `admin`
- **Password**: `admin123`

---

## 🛠️ Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Yii2 Basic (~2.0.45) |
| Language | PHP 7.4+ |
| Database | MySQL 5.7+ |
| Frontend | Bootstrap 5 |
| JavaScript | Vanilla JS (no jQuery dependency) |
| Auth | Yii2 User Component |
| Assets | Yii Asset Bundle |

---

## 📋 Next Steps (Optional Enhancements)

- [ ] Add more field types (checkbox, radio, dropdown, file)
- [ ] Field validation rules in schema
- [ ] Required/optional field toggle
- [ ] Export submissions to CSV/Excel
- [ ] Public form sharing (anonymous submissions)
- [ ] Form templates
- [ ] Drag & drop field reordering
- [ ] Multi-page forms
- [ ] Form analytics

---

## 🐛 Troubleshooting

**Issue: "could not find driver"**
- Enable PDO MySQL extension in `php.ini`
- Uncomment: `extension=pdo_mysql`
- Restart web server

**Issue: "Table doesn't exist"**
- Run the migration or SQL script
- Check database name in `config/db.php`

**Issue: "404 Not Found"**
- Ensure `.htaccess` exists in `web/` folder
- Enable `mod_rewrite` in Apache
- Check URL rules in `config/web.php`

**Issue: "Login doesn't work"**
- Verify user exists: `SELECT * FROM users;`
- Reset password in Yii console:
  ```php
  echo Yii::$app->security->generatePasswordHash('admin123');
  UPDATE users SET password_hash = '<hash>' WHERE username = 'admin';
  ```

---

## 📞 Support

For issues or questions, check:
- `SETUP.md` - Complete setup guide
- `database_setup.sql` - Database schema
- Yii2 documentation: https://www.yiiframework.com/doc/guide/2.0/en

---

**Status**: ✅ Production Ready
**Last Updated**: 2026-04-09
