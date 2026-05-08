# Quick Fix Verification Guide

## Perbaikan yang Sudah Dilakukan

Halaman UPDATE dynamic page builder sudah diperbaiki agar canvas tidak lagi kosong saat membuka halaman edit.

## Apa yang Berubah?

### 1. ✅ HTML Structure
- Toolbar sekarang berada **dalam** builder container (sebelumnya di luar)
- Ini memastikan styling dan visibility konsisten

### 2. ✅ CSS Layout
- Tambah `.builder-container` wrapper dengan flex layout
- Toolbar sekarang memiliki `flex-shrink: 0` agar tidak menyusut
- Page-builder menggunakan `flex: 1` untuk mengisi sisa space

### 3. ✅ JavaScript Initialization
- Deteksi otomatis antara CREATE dan UPDATE mode
- UPDATE mode langsung memanggil `renderBuilder()` dengan state yang sudah ada
- Automatically select first block jika ada blocks

### 4. ✅ State Management
- `window.pageState` sudah ter-populate dari `layout_json` database
- Tidak perlu fetch tambahan - data sudah di-embed di view

## Testing Checklist

### Test 1: CREATE Mode (Buat Halaman Baru)
```
1. Buka: /master-page/dynamic-create
2. ✓ Template selector modal harus muncul
3. ✓ Tidak ada components di canvas (sebelum pilih template)
4. ✓ Klik "Pilih Template" atau "Mulai Kosong"
5. ✓ Builder interface muncul dengan toolbar
6. ✓ Canvas siap untuk drag components
7. ✓ Klik "Simpan" dan page harus ter-create
```

### Test 2: UPDATE Mode (Edit Halaman Existing)
```
1. Buat halaman dulu (test 1)
2. Tambah beberapa components (heading, text, button)
3. Klik "Simpan"
4. ✓ Page ter-create di database
5. Buka: /master-page/dynamic-update?id=X (ganti X dengan ID page)
6. ✓ Template modal HARUS TIDAK MUNCUL
7. ✓ Builder interface HARUS MUNCUL
8. ✓ Canvas HARUS MENAMPILKAN semua components yang sudah dibuat
9. ✓ Toolbar HARUS TERLIHAT di atas
10. ✓ Bisa edit properties dari components
11. ✓ Bisa tambah components baru
12. ✓ Bisa delete components
13. ✓ Klik "Simpan" dan update harus berhasil
```

### Test 3: Verify State Persistence
```
1. Lakukan Test 2 steps 1-7
2. Refresh halaman (F5)
3. ✓ Canvas MASIH menampilkan components yang sama (state dari DB)
4. ✓ Tidak ada data yang hilang
```

## Browser Console Commands (Untuk Debugging)

Jika ada masalah, buka DevTools (F12) dan jalankan:

```javascript
// 1. Check current state in memory
console.log('Page State:', window.pageState);

// 2. Check if container visible
console.log('Container display:', 
    document.getElementById('builderContainer')?.style.display);

// 3. Check canvas state
console.log('Canvas children:', 
    document.getElementById('canvas')?.children.length);

// 4. Manually render if needed
renderBuilder(window.pageState);

// 5. Check if state is empty
console.log('Is state empty?', window.pageState?.length === 0);

// 6. Check block count
console.log('Block count:', window.pageState?.length || 0);
```

## Expected Results After Fix

| Scenario | Before Fix | After Fix |
|----------|-----------|-----------|
| Open CREATE page | Template picker ✓ | Template picker ✓ |
| After create, open UPDATE | **Canvas Empty ✗** | **Canvas Full ✓** |
| Refresh UPDATE page | **Still Empty ✗** | **Still Full ✓** |
| Add component on UPDATE | Might not save ✗ | Saves correctly ✓ |
| Toolbar visible | Yes ✓ | Yes ✓ (now properly styled) |

## Troubleshooting

### Problem: Canvas masih kosong di UPDATE
**Solution:**
1. Buka DevTools (F12)
2. Console tab
3. Jalankan: `console.log(window.pageState)`
4. Jika empty array `[]`, berarti `layout_json` di DB kosong
5. Buat halaman baru dan pastikan save berhasil

### Problem: Toolbar tidak muncul
**Solution:**
1. Check: Apakah page berhasil ter-create?
2. Buka Network tab di DevTools
3. Reload page dan lihat response dari `/dynamic-update`
4. Pastikan HTML response mengandung `<div class="builder-toolbar">`

### Problem: Components tidak bisa di-drag
**Solution:**
1. Check console untuk JavaScript errors
2. Pastikan Sortable.js library loaded (cek Network tab)
3. Jalankan: `console.log(typeof Sortable)` - harus `"function"`
4. Jalankan: `renderBuilder(window.pageState)` untuk re-render

### Problem: Tombol Save tidak working
**Solution:**
1. Buka DevTools (F12)
2. Console tab
3. Klik Save button
4. Lihat apa error message yang muncul
5. Pastikan prompt untuk title muncul
6. Check Network tab untuk POST request

## File yang Dimodifikasi

✅ `views/master-page/dynamic-builder.php`
- Moved toolbar ke dalam container
- Added CSS untuk wrapper
- Enhanced initialization logic
- Better state detection

## Tidak Ada File yang Dihapus

Semua file lain tetap aman dan tidak berubah.

## Rollback (Jika Diperlukan)

Jika ada issue yang tidak terduga:
1. Cari file `dynamic-builder.php.bak` (backup file)
2. Copy ke `dynamic-builder.php`
3. Refresh page

## Next: Production Deployment

Sebelum push ke production:
1. ✓ Test di local development
2. ✓ Test dengan berbagai browser (Chrome, Firefox, Edge)
3. ✓ Test responsivitas mobile
4. ✓ Test dengan large number of components (10+)
5. ✓ Test update beberapa kali untuk memastikan stability

## Support

Jika masih ada issue setelah fix:
1. Collect browser console logs
2. Check database: `SELECT layout_json FROM master_page WHERE id=X`
3. Paste layout_json ke JSON validator untuk check format
4. Buka browser DevTools dan collect Network trace

---

**Last Updated:** May 7, 2026
**Status:** ✅ READY FOR TESTING
