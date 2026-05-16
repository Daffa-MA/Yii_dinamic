# Cara Verifikasi Warna Sidebar Header

## Masalah yang Diperbaiki
Warna teks header sidebar (badge "WORKSPACE", judul "Projects", dan subtitle "Beranda & navigasi") sekarang bisa diubah melalui Workspace Settings.

## Langkah-Langkah Verifikasi

### 1. Hard Refresh Browser (PENTING!)
Ini akan menghapus cache CSS lama dan memuat versi baru.

**Windows/Linux**: Tekan `Ctrl + Shift + R`
**Mac**: Tekan `Cmd + Shift + R`

### 2. Cek HTML Source
1. Klik kanan di halaman → "View Page Source" atau tekan `Ctrl + U`
2. Cari teks `<!-- CACHE BUSTER: v2.0`
3. Lihat baris debug di bawahnya:
   ```html
   <!-- sidebarTextColor: #1e60be -->
   <!-- sidebarTextMuted: #0066f5 -->
   ```
4. Pastikan warna yang ditampilkan sesuai dengan yang Anda set di Workspace Settings

### 3. Inspect Element (Opsional)
1. Buka DevTools dengan menekan `F12`
2. Klik kanan pada teks "Projects" di sidebar → "Inspect Element"
3. Lihat di panel Styles, cari elemen `<h2>`
4. Pastikan ada style inline: `color: #1e60be !important;`
5. Pastikan tidak ada CSS class yang override warna ini

### 4. Test Ubah Warna
1. Buka halaman **Workspace Settings**
2. Ubah "Text Color" ke warna lain (misalnya `#ff0000` untuk merah)
3. Klik "Save Settings"
4. **Hard refresh** halaman (Ctrl + Shift + R)
5. Teks "Projects" seharusnya berubah menjadi merah

## Apa yang Sudah Diperbaiki?

### Sebelum Fix:
- CSS class `.app-sidebar-header-text h2` punya property `color` yang override inline style
- Walaupun inline style pakai `!important`, tetap tidak berpengaruh karena CSS class lebih spesifik

### Sesudah Fix:
- Property `color` dihapus dari CSS class
- Inline style dengan `!important` sekarang berfungsi
- Warna diambil dari Workspace Settings

## Elemen yang Terpengaruh

1. **Badge "WORKSPACE"** → Menggunakan "Muted Text Color"
2. **Judul "Projects"** → Menggunakan "Text Color"
3. **Subtitle "Beranda & navigasi"** → Menggunakan "Muted Text Color"

## Troubleshooting

### Warna masih tidak berubah?

**Solusi 1: Clear Cache Browser Sepenuhnya**
- Chrome: Settings → Privacy and security → Clear browsing data → Pilih "Cached images and files"
- Firefox: Settings → Privacy & Security → Clear Data → Pilih "Cached Web Content"

**Solusi 2: Coba Mode Incognito/Private**
Buka halaman di mode incognito untuk memastikan tidak ada cache atau extension yang mengganggu.

**Solusi 3: Restart PHP Service**
Jika menggunakan opcache, restart PHP-FPM atau Apache:
```bash
# Untuk PHP-FPM
sudo systemctl restart php-fpm

# Untuk Apache
sudo systemctl restart apache2
```

**Solusi 4: Cek Database**
Jalankan script verifikasi:
```bash
php verify_sidebar_colors.php
```

Atau cek database langsung:
```sql
SELECT sidebar_text_color, sidebar_text_muted 
FROM workspace_settings 
WHERE setting_key = 'default';
```

### Warna di HTML source masih lama?

Ini berarti file PHP belum ter-update. Cek:
1. File permission (apakah web server bisa baca file?)
2. Timestamp file (kapan terakhir dimodifikasi?)
3. PHP opcache (coba restart PHP service)

### Warna berubah tapi tidak sesuai yang di-set?

1. Cek apakah Anda di halaman project list (`/project/index`)
   - Halaman ini menggunakan warna hardcoded, bukan dari settings
2. Cek apakah ada typo di kode warna (harus format `#RRGGBB`)
3. Cek debug comment di HTML source untuk melihat warna yang sebenarnya diload

## Informasi Teknis

### Alur Data Warna:
```
Workspace Settings UI 
  ↓ (save)
Database & Session
  ↓ (load)
PHP Variables ($sidebarTextColor, $sidebarTextMuted)
  ↓ (render)
Inline Styles (style="color: #1e60be !important")
  ↓ (apply)
Browser Rendering
```

### CSS Specificity:
- **Inline style dengan !important**: Prioritas tertinggi ✅
- **CSS class selector**: Prioritas lebih rendah
- Karena property `color` dihapus dari CSS class, inline style sekarang berfungsi

## File yang Dimodifikasi

1. `views/layouts/_sidebar.php`
   - Baris ~382: `.app-sidebar-header-badge` - property `color` dihapus
   - Baris ~397: `.app-sidebar-header-text h2` - property `color` dihapus
   - Baris ~403: `.app-sidebar-header-text p` - property `color` dihapus
   - Ditambahkan cache buster dan debug output

## Hasil yang Diharapkan

✅ Warna teks header berubah sesuai Workspace Settings
✅ Perubahan terlihat setelah hard refresh
✅ Tidak ada konflik CSS di DevTools
✅ Debug comment menampilkan warna yang benar
✅ Warna tetap tersimpan setelah reload

## Butuh Bantuan?

Jika masih ada masalah, kirimkan:
1. Screenshot DevTools yang menunjukkan computed styles untuk elemen `<h2>`
2. Screenshot HTML source yang menunjukkan bagian debug comments
3. Output dari `php verify_sidebar_colors.php`
4. Nama dan versi browser yang digunakan
