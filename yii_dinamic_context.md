# YII DINAMIC — KONTEKS & ATURAN GLOBAL

## 1. IDENTITAS PROJECT

- **Project utama**: Yii Dinamic
- **SIPKL** adalah salah satu modul di dalam Yii Dinamic — bukan aplikasi standalone
- Seluruh fitur baru harus **bisa dipakai ulang oleh modul lain**

---

## 2. ARSITEKTUR INTI

| Komponen | Fungsi |
|---|---|
| Master Page | Registrasi & manajemen halaman |
| Master Form | Registrasi & manajemen form |
| Master Menu | Registrasi & manajemen menu |
| Master Table | Registrasi & manajemen tabel dinamis |
| Workspace Setting | Konfigurasi per workspace |

> **Sebelum membuat komponen baru** → cek apakah sudah tersedia di salah satu komponen di atas. Jika belum ada, integrasikan ke sana — jangan buat solusi lepas/hardcoded.

---

## 3. LARANGAN KERAS (ANTI-HARDCODE POLICY)

**Jangan pernah hardcode nilai apapun**, termasuk:

- Nama: sekolah, modul, jurusan, kelas, perusahaan, status, role, permission, menu, widget
- Angka: radius GPS, batas keterlambatan, tahun ajaran, periode
- Konfigurasi: workflow, label UI, template export, validation rule, threshold

**Semua nilai harus berasal dari:**
- Database
- Dynamic Metadata
- Workspace Setting
- Master Form / Master Page / Master Table / Master Menu
- Existing Configuration Engine

---

## 4. ATURAN MIGRATION & DATABASE

### Wajib dilakukan SEBELUM membuat migration baru:

1. **Cek existing lookup/status table** — Yii Dinamic kemungkinan sudah punya tabel konfigurasi/lookup dinamis. Data seperti "status kehadiran" (Hadir, Terlambat, Izin, dll) adalah konfigurasi, bukan struktur tetap. Daftarkan ke sistem lookup yang sudah ada, **jangan buat tabel baru**.

2. **Cek existing model** — sebelum membuat `User::class`, `Status::class`, dll, cek model yang sudah ada di project. Gunakan model existing, jangan duplikasi.

3. **Cek namespace yang benar** — jangan asumsi `app\models`. Ikuti namespace yang sudah dipakai project Yii Dinamic.

4. **Nama tabel harus mengikuti konvensi project** — tanyakan/cek konvensi penamaan tabel yang sudah ada sebelum membuat migration.

### Pola yang SALAH (contoh nyata yang harus dihindari):

```php
// ❌ SALAH — membuat tabel status baru yang hardcoded
Schema::create('attendance_status', function (Blueprint $table) {
    $table->string('name', 100);   // hardcode kolom
    $table->string('color', 20);   // hardcode kolom
});

// ❌ SALAH — asumsi namespace
use app\models\User;

// ❌ SALAH — asumsi nama tabel
public static function tableName() { return 'attendance_status'; }
```

### Pola yang BENAR:

```php
// ✅ BENAR — status kehadiran → daftarkan ke sistem lookup/master data yang sudah ada
// ✅ BENAR — gunakan model & namespace yang sudah ada di project
// ✅ BENAR — nama tabel dari konfigurasi atau tanyakan konvensi project terlebih dahulu
```

---

## 5. ATURAN PENGEMBANGAN

### DO ✅
- Gunakan komponen yang sudah ada sebelum membuat baru
- **Tanya/cek struktur project yang sudah ada sebelum mulai coding**
- Registrasikan page/form/menu/table/config baru ke sistem yang sudah ada
- Buat implementasi yang reusable lintas modul
- Ikuti design pattern yang sudah ada

### DON'T ❌
- Membuat project/solusi baru di luar ekosistem Yii Dinamic
- **Langsung membuat migration tanpa mengecek existing table & model terlebih dahulu**
- **Membuat tabel untuk data konfigurasi/status yang seharusnya masuk ke sistem lookup existing**
- **Mengasumsi namespace, nama model, atau nama tabel tanpa konfirmasi**
- Mengubah fitur yang sudah berjalan
- Refactor besar pada modul lain
- Menghapus kode yang sudah ada
- Membuat nama class/dependency yang spesifik ke SIPKL, sekolah, atau organisasi tertentu
- Membuat dummy/mock data atau dummy implementation
- Duplikasi logic yang sudah ada

---

## 6. PERMISSION SYSTEM

Gunakan permission system yang sudah ada. Jangan hardcode role atau permission.

| Aktor | Akses |
|---|---|
| Siswa | Check In, Check Out, lihat absensi sendiri |
| Guru Pembimbing | Monitoring & rekap siswa bimbingan |
| Pokja | Monitoring seluruh siswa |
| Admin | Full access |

---

## 7. FUTURE READY

Struktur harus siap untuk integrasi berikut **tanpa refactor besar**:

- Face Recognition / QR / NFC / Mobile Attendance
- Approval Attendance
- WhatsApp / Email / Push Notification
- AI Analytics & Pattern Detection

---

## 8. STANDAR OUTPUT

Setiap implementasi wajib memenuhi:

- [ ] Zero hardcoded value
- [ ] Zero dummy/mock implementation
- [ ] Zero duplicate logic
- [ ] Zero breaking changes pada modul lain
- [ ] Mengikuti arsitektur Yii Dinamic
- [ ] Production ready

**Jika ada beberapa pilihan implementasi** → pilih yang paling: Dynamic → Configurable → Reusable → Scalable → Maintainable.