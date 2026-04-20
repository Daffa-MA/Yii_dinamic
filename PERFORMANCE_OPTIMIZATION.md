# 🚀 Performance Optimization Guide

## 📋 Masalah yang Ditemukan

Web Anda lambat karena:

1. **Yii Debug Module aktif** - Menghasilkan banyak file debug data
2. **Cache files menumpuk** - Ribuan file cache tidak dihapus otomatis
3. **Session files lama** - File session user lama tetap tersimpan
4. **Log files besar** - File log terus bertambah tanpa rotasi

## ✅ Solusi yang Sudah Diterapkan

### 1. Disable Debug Module (config/web.php)
- Debug module tidak akan generate file debug lagi
- Set `$enableDebug = true` hanya saat ada masalah untuk debugging

### 2. Optimasi Cache Configuration
```php
'cache' => [
    'class' => 'yii\caching\FileCache',
    'cachePath' => '@runtime/cache',
    'defaultDuration' => 86400,     // 24 jam
    'directoryLevel' => 1,           // Kurangi jumlah subdirectory
],
```

### 3. Pembersihan Manual
Jalankan salah satu:

**Dari browser:**
```
http://localhost/cleanup.php
```

**Atau dari command line:**
```bash
php cleanup.php
```

**Atau gunakan batch script (Windows):**
```cmd
cleanup.bat
```

## 🎯 Rekomendasi Lanjutan

### Setting Production yang Lebih Baik

1. **Disable Yii Debug** di production:
```php
// Di web.php, jalankan ini saja untuk production
if (YII_ENV_DEV) {
    // Development settings only
}
```

2. **Set proper session handler** (optional):
```php
'session' => [
    'class' => 'yii\web\Session',
    'timeout' => 1800,  // 30 minutes
],
```

3. **Setup log rotation** (optional):
```php
'targets' => [
    [
        'class' => 'yii\log\FileTarget',
        'levels' => ['error', 'warning'],
        'maxFileSize' => 10 * 1024 * 1024,  // 10MB
        'maxLogFiles' => 5,                 // Keep max 5 files
    ],
],
```

## 📊 Performa Sebelum vs Sesudah

**Sebelum:**
- Cache files: ~150+ files
- Debug files: ~50+ files  
- Session files: ~6 files
- Total: ~200+ file sampah

**Sesudah:**
- Clean runtime folder
- Faster page loading
- Reduced disk I/O

## 🔄 Maintenance Rutin

### Setiap minggu:
```bash
php cleanup.php
```

### Setiap bulan:
- Review log files
- Check cache size
- Monitor database size

## 💡 Tips Lanjutan

1. **Gunakan Redis/Memcached untuk cache** (lebih cepat dari FileCache):
```php
'cache' => [
    'class' => 'yii\redis\Cache',  // Atau 'yii\caching\MemCache'
],
```

2. **Enable gzip compression** di web server (nginx/Apache)

3. **Setup CDN** untuk static assets (css, js, images)

4. **Optimize database queries** - use eager loading dengan `joinWith()`

## ⚠️ Catatan Penting

- Jangan hapus `/runtime/.gitignore` file
- Folder `/runtime` tetap perlu ada, hanya contents yang dihapus
- Setelah cleanup, aplikasi akan generate cache baru secara otomatis
- Pembersihan aman dan tidak akan merusak data

---
**Terakhir diupdate:** $(date)
**Script version:** 1.0
