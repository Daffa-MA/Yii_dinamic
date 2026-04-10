# MySQL Setup Guide

## Problem
PDO MySQL driver is now enabled, but MySQL server is not installed on your system.

## Solution: Install MySQL Server

### Option 1: Install MySQL (Recommended)

1. **Download MySQL Community Server**
   - Visit: https://dev.mysql.com/downloads/mysql/
   - Download MySQL 8.0 or 8.4 LTS for Windows
   - Or use MySQL Installer: https://dev.mysql.com/downloads/installer/

2. **Install MySQL**
   - Run the installer
   - Choose "Developer Default" or "Server only"
   - Set root password (remember this!)
   - Complete installation

3. **Add MySQL to PATH** (optional)**
   - Add `C:\Program Files\MySQL\MySQL Server 8.0\bin` to system PATH
   - Or use full path to mysql.exe

4. **Create Database**
   ```bash
   # Using MySQL CLI (adjust path if needed)
   "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p
   
   # Then run:
   CREATE DATABASE yii2basic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   ```

5. **Update config/db.php** with your MySQL password

6. **Run Migrations**
   ```bash
   cd d:\Yii_dinamic
   php yii migrate --interactive=0
   ```

7. **Start the app**
   ```bash
   php -S localhost:8080 -t web
   ```

---

### Option 2: Use XAMPP (Easier)

1. **Download XAMPP**
   - https://www.apachefriends.org/
   - Install XAMPP (includes Apache + MySQL + PHP)

2. **Start MySQL**
   - Open XAMPP Control Panel
   - Click "Start" on MySQL module

3. **Create Database via phpMyAdmin**
   - Open: http://localhost/phpmyadmin
   - Click "New" in left sidebar
   - Database name: `yii2basic`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

4. **Update config/db.php**
   ```php
   'dsn' => 'mysql:host=localhost;dbname=yii2basic',
   'username' => 'root',
   'password' => '', // XAMPP default is empty
   ```

5. **Run Migrations**
   ```bash
   cd d:\Yii_dinamic
   php yii migrate --interactive=0
   ```

6. **Start the app**
   ```bash
   php -S localhost:8080 -t web
   ```

---

### Option 3: Use Laragon (Simplest for Windows)

1. **Download Laragon**
   - https://laragon.org/download/
   - Install Laragon Full

2. **Start Laragon**
   - Click "Start All"
   - MySQL will start automatically

3. **Create Database**
   - Click "Database" button in Laragon
   - Opens phpMyAdmin
   - Create database `yii2basic`

4. **Update config/db.php**
   ```php
   'dsn' => 'mysql:host=localhost;dbname=yii2basic',
   'username' => 'root',
   'password' => '', // Laragon default
   ```

5. **Run Migrations & Start App**
   ```bash
   cd d:\Yii_dinamic
   php yii migrate --interactive=0
   php -S localhost:8080 -t web
   ```

---

## Quick Setup After MySQL is Running

### 1. Create Database (choose one method)

**Via CLI:**
```bash
mysql -u root -p -e "CREATE DATABASE yii2basic CHARACTER SET utf8mb4;"
```

**Via phpMyAdmin:**
- Go to http://localhost/phpmyadmin
- Click "New"
- Name: `yii2basic`
- Click "Create"

### 2. Configure Database Connection

Edit `d:\Yii_dinamic\config\db.php`:
```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    'username' => 'root',
    'password' => 'YOUR_MYSQL_PASSWORD', // ← Change this!
    'charset' => 'utf8',
];
```

### 3. Run Migrations
```bash
cd d:\Yii_dinamic
php yii migrate --interactive=0
```

This will create:
- ✅ users table (with admin/admin123)
- ✅ forms table
- ✅ form_submissions table

### 4. Start Application
```bash
php -S localhost:8080 -t web
```

### 5. Login
- URL: http://localhost:8080
- Username: `admin`
- Password: `admin123`

---

## Verify MySQL is Running

**Check if MySQL service is running:**
```bash
# Windows
sc query MySQL80
# or
tasklist | findstr mysql
```

**Test connection:**
```bash
mysql -u root -p -e "SELECT 1;"
```

---

## Troubleshooting

**"Access denied for user root@localhost"**
- Wrong password in config/db.php
- Update with correct MySQL root password

**"Unknown database yii2basic"**
- Database not created yet
- Run: `CREATE DATABASE yii2basic;`

**"PDO driver not found"**
- Already fixed! pdo_mysql is now enabled
- Restart your web server if still getting error

**Migration runs but tables don't exist**
- Check migration output for errors
- Run manually: `php yii migrate`

---

## Next Steps After Setup

1. ✅ Login with admin/admin123
2. ✅ Go to Dashboard
3. ✅ Create your first form
4. ✅ Add fields (text, number, textarea)
5. ✅ Save and test the form
6. ✅ Submit and view data
