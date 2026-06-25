# USE CASE 3 — MODUL ABSENSI PKL

> Baca `YII_DINAMIC_CONTEXT.md` terlebih dahulu sebelum mengerjakan file ini.

---

## META

| Item | Detail |
|---|---|
| Modul | Absensi (reusable — bukan hanya PKL) |
| Aktor | Siswa, Guru Pembimbing |
| Tujuan | Mencatat & memantau kehadiran siswa selama kegiatan PKL |

---

## FITUR YANG HARUS DIBANGUN

```
1. Check In
2. Check Out
3. Validasi GPS
4. Riwayat Absensi
5. Monitoring Kehadiran
6. Rekap Kehadiran Otomatis
7. Rekap Keterlambatan
8. Dashboard Analytics
9. Export Data
10. Audit Log
```

---

## SPESIFIKASI FITUR

### 1. CHECK IN

**Data yang disimpan:**
- Lokasi GPS realtime (latitude, longitude)
- Waktu server
- Device info (jika tersedia)
- Browser info (jika tersedia)
- IP Address (jika tersedia)
- Metadata tambahan (jika tersedia)
- Status absensi *(dinamis dari database — tidak hardcode)*

> Status absensi (Hadir, Terlambat, Izin, dll) harus bisa ditambah/ubah/hapus via sistem yang ada.

---

### 2. CHECK OUT

**Data yang disimpan:**
- Waktu server
- Latitude & longitude
- Durasi kehadiran *(dihitung otomatis)*
- Catatan (opsional)
- Metadata tambahan (jika tersedia)

---

### 3. VALIDASI GPS

**Alur:**
1. Ambil lokasi perusahaan dari data penempatan aktif siswa
2. Ambil radius validasi dari **Workspace Setting**
3. Hitung jarak otomatis
4. Tentukan valid/tidak secara dinamis

> Radius & aturan validasi TIDAK boleh hardcode — semua dari Workspace Setting.

---

### 4. RIWAYAT ABSENSI

Halaman tabel dengan fitur:

| Filter / Fitur | Keterangan |
|---|---|
| Search | Realtime |
| Filter | Tanggal, rentang tanggal, status, jurusan, kelas, perusahaan, guru pembimbing, periode PKL |
| Sorting | Dinamis semua kolom |
| Pagination | Dinamis |

> Semua filter berasal dari metadata & database — tidak hardcode.

---

### 5. MONITORING KEHADIRAN

Dashboard realtime menampilkan:
- Total siswa PKL aktif
- Hadir hari ini
- Terlambat hari ini
- Belum absensi
- Izin / Sakit / Alpha *(semua dari status dinamis)*

---

### 6. REKAP KEHADIRAN OTOMATIS

Rekap dihitung dinamis berdasarkan dimensi:

- Harian / Mingguan / Bulanan / Tahunan
- Per periode PKL
- Per jurusan / kelas
- Per perusahaan
- Per guru pembimbing

---

### 7. REKAP KETERLAMBATAN

Menampilkan:
- Jumlah keterlambatan
- Total menit keterlambatan
- Ranking keterlambatan
- Statistik & trend keterlambatan

*(Semua dihitung dinamis dari database)*

---

### 8. DASHBOARD ANALYTICS

Widget analytics (query dinamis, tidak ada data statis):

- % Kehadiran & % Keterlambatan
- Kehadiran per jurusan / perusahaan
- Kehadiran per bulan / per periode PKL

---

### 9. EXPORT DATA

**Aturan:**
- Export mengikuti filter yang sedang aktif
- Format: Excel, PDF, Print
- Gunakan **export engine Yii Dinamic** jika tersedia
- Template tidak boleh hardcode

**Contoh perilaku:**
> Jika user filter: Jurusan TKJ + Bulan Januari + Status Terlambat → export hanya data tersebut.

---

### 10. AUDIT LOG

Semua aktivitas tercatat menggunakan **audit system yang sudah ada**:

- Check In / Check Out
- Edit Absensi
- Validasi
- Export
- Perubahan konfigurasi

---

## HALAMAN YANG DIBUTUHKAN

| No | Halaman | Deskripsi |
|---|---|---|
| 1 | Dashboard Absensi | Widget summary & analytics |
| 2 | Monitoring Absensi | Realtime monitoring kehadiran hari ini |
| 3 | Riwayat Absensi | Tabel dengan filter & pagination |
| 4 | Detail Absensi | Detail record per siswa |
| 5 | Export Panel | Panel export dengan filter aktif |

> Registrasikan semua halaman ke **Master Page**.
> Registrasikan semua form ke **Master Form**.
> Registrasikan semua menu ke **Master Menu**.
> Registrasikan semua tabel ke **Master Table**.

---

## UI / UX

- Ikuti design pattern yang sudah ada pada project
- Responsive & mobile friendly
- Gunakan reusable components yang sudah tersedia
- Konsisten dengan UI existing

---

## CATATAN PENTING: GENERIC MODULE

> Modul ini bukan hanya untuk PKL.
> Di masa depan bisa dipakai untuk: Magang, Kehadiran Pegawai, Event, Kunjungan Industri, dll.

**Implikasi:**
- Hindari nama class yang spesifik ke PKL/sekolah
- Hindari dependency ke SIPKL atau organisasi tertentu
- Prioritaskan komponen yang reusable