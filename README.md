# Yii Dynamic Form Builder

A production-ready **dynamic form builder** built with **Yii2 + MySQL**, with:

- form/page builder
- custom public render page
- public submission flow
- Firebase login on public form
- form publishing and shareable links

---

## Tech Stack

- PHP 7.2+
- Yii2 (`yiisoft/yii2`)
- MySQL
- Bootstrap 5 + custom JS
- Firebase Auth (public form login)

---

## Key Features

- Multi-page dynamic forms (`schema_js`)
- Public form endpoint: `/form/public-render/<id>`
- Login gate on public-render (Firebase)
- Auto-capture Firebase identity in submissions
- Form submissions stored in DB (`form_submissions`)
- Published forms module + public URL generator
- Table builder module (`db_tables`, `db_table_columns`)

---

## Quick Start (Local)

1. Install dependency:

```bash
composer install
```

2. Copy env:

```bash
copy .env.example .env
```

3. Set DB in `.env` (local MySQL or Railway `MYSQL_PUBLIC_URL`).

4. Run migration:

```bash
php yii migrate --interactive=0
```

5. Start app:

```bash
php -S localhost:8080 -t web
```

6. Open:
   `http://localhost:8080`

Default user (from migration):  
`admin / admin123`

---

## Environment Variables

Use `.env`:

```env
YII_DB_DRIVER=mysql
MYSQL_PUBLIC_URL=

YII_DB_HOST=127.0.0.1
YII_DB_PORT=3306
YII_DB_NAME=yii2basic
YII_DB_USER=root
YII_DB_PASSWORD=

APP_URL=
```

### Dual Database (Local Master + Railway Backup)

- Gunakan `YII_DB_FORCE_LOCAL_PRIMARY=1` agar localhost tetap jadi **master** baca/tulis.
- Isi `YII_DB_BACKUP_URL` (atau `MYSQL_PUBLIC_URL`) untuk koneksi backup Railway.
- Auto-sync write master -> backup aktif saat `YII_DB_BACKUP_SYNC` tidak diisi atau bukan `0`.
- Jika ingin request gagal saat backup gagal sinkron, set `YII_DB_BACKUP_SYNC_STRICT=1`.
- Untuk failover otomatis ke backup saat master tidak bisa diakses, set `YII_DB_FAILOVER_TO_BACKUP=1` (sinkronisasi akan mencoba arah sebaliknya saat mode failover aktif).

### Priority for DB connection

`config/db.php` prioritizes:

1. `DATABASE_PUBLIC_URL`, `MYSQL_PUBLIC_URL`, `RAILWAY_*_PUBLIC_URL`
2. `DATABASE_URL`, `MYSQL_URL`, `RAILWAY_*_URL`
3. `YII_DB_HOST/YII_DB_PORT/...` fallback

---

## Railway Deployment

1. Deploy app service.
2. Set app Variables:
   - `MYSQL_PUBLIC_URL` (full mysql URL)
   - `APP_URL` (your Railway app domain, e.g. `https://your-app.up.railway.app`)
3. Redeploy app.
4. Run migration against Railway DB:

```bash
php yii migrate --interactive=0
```

---

## Firebase (Public Login)

If Google login shows `auth/unauthorized-domain`:

1. Firebase Console → Authentication → Settings
2. Authorized domains → add your domain:
   - `yiidinamic-production.up.railway.app` (or your current domain)
3. Save and retry login.

---

## Common Errors

| Error                             | Cause                           | Fix                                       |
| --------------------------------- | ------------------------------- | ----------------------------------------- |
| `SQLSTATE[HY000] [2002]`          | DB host/URL not reachable       | Set correct `MYSQL_PUBLIC_URL` in app env |
| `The table does not exist: users` | Migration not run on active DB  | Run `php yii migrate --interactive=0`     |
| `auth/unauthorized-domain`        | Firebase domain not whitelisted | Add domain in Firebase Authorized domains |

---

## Important Routes

- `/dashboard`
- `/form`
- `/form/create`
- `/form/view/<id>`
- `/form/public-render/<id>`
- `/form/submit/<id>`
- `/form/submissions/<id>`
- `/published-form`

---

## Project Structure (Main)

```text
assets/
config/
controllers/
migrations/
models/
views/
web/
```

---

## Notes

- Public form now uses Firebase auth gate before showing form content.
- Submit button is shown only after login and placed at the bottom of visible form content.
- Keep secrets only in environment variables, never commit them.
