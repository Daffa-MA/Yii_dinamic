# SECURITY-02 — Audit & Hardening Workspace Login (Project Login)

> Status: **SELESAI — SEMUA PERUBAHAN DIIMPLEMENTASIKAN & TERVERIFIKASI**
> Tanggal audit: 18 Agustus 2026
> Lingkup: batas autentikasi workspace (`project/login`, `project/change-password`, `project/access-denied`, `project/logout`)
> Status keseluruhan: **VERIFIED** (console 42/42 workspace + 51/51 commander, verifikasi HTTP nyata)

---

## 1. Ringkasan Eksekutif

Audit menyeluruh dilakukan pada seluruh batas **Workspace Login** aplikasi dynamic-forms
(login workspace per-project melalui domain workspace). Lima (5) temuan nyata ditemukan dan
diperbaiki dengan perubahan minimal yang tidak mengubah arsitektur dynamic multi-database,
tidak menambah dependensi, dan tidak mengubah skema database:

| # | Temuan | Severity | Status |
|---|--------|----------|--------|
| FIX-01 | Session Fixation pada `ProjectAuthContext::login()` | **Tinggi** | Diperbaiki |
| FIX-02 | Pengalihan database autentikasi melalui `?database=`/sesi | **Sedang** | Diperbaiki |
| FIX-03 | Tidak ada brute-force protection per-username pada login workspace | **Sedang** | Diperbaiki |
| FIX-04 | `ProjectUser::validatePassword()` bisa throw + tidak ada equalizer waktu | **Sedang** | Diperbaiki |
| FIX-05 | Akun nonaktif (`status != 1`) dapat membuat sesi auth di waktu login | **Sedang** | Diperbaiki |
| FIX-06 | Logout dapat di-trigger via GET | **Rendah** | Diperbaiki |

Seluruh perubahan diuji ulang: suite console **42/42 lulus**, suite SECURITY-01 **51/51 lulus**
(tidak ada regresi), dan verifikasi HTTP nyata pada `127.0.0.1:8091` semuanya lulus.

---

## 2. Lingkup & Metodologi

Metodologi yang digunakan:
1. **Eksplorasi kode dulu, baru modifikasi** — seluruh rute, controller, model, dan service
   yang terlibat dipetakan sebelum menyentuh kode.
2. **Tanpa hardcoding** id project, username, atau role pada logika produksi.
3. **Tanpa perubahan perilaku yang tidak perlu** — fitur perpindahan database dashboard
   (`?database=`) dipertahankan; hanya pengaruhnya pada batas *autentikasi* yang dipatok.
4. **Tanpa perubahan skema database** — semua perbaikan hanya kode aplikasi.
5. **Pesan error generik** — pesan gagal login tidak membedakan penyebab.
6. **Reuse service yang sudah ada** — `CommanderLoginLimiter`, `ActiveDatabaseContext`,
   `ProjectAuthContext` digunakan kembali (diperluas, bukan diduplikasi).
7. **Uji regresi penuh** setelah perubahan.

Kredensial/entitas uji (sementara, dihapus setelah uji): project 22 (`test` workspace,
domain `test.appforge.web.id`, database `test`) dan project 19 (`RT33`, database `rt33`)
untuk uji isolasi antar-workspace. User uji `secws_*` dibuat di database project dan dihapus
di akhir pengujian.

---

## 3. Arsitektur Workspace Login

- Rute: `project/login/<id:\d+>` → `controllers/ProjectController.php::actionLogin($id)`
  (baris 886-1029). Rute public lain: `project/change-password`, `project/access-denied`,
  `project/logout` (PUBLIC_ROUTES pada `DomainProjectResolver` & `ProjectAccessBootstrap`).
- Login workspace hanya bisa diakses pada **domain workspace** (`isWorkspaceDomain()`).
  Pada domain root, pengunjung (guest) menuju project lain akan mendapat 404 lewat
  `ProjectController::findAccessibleProject()` (baris 165-192).
- Form login: `models/ProjectLoginForm.php` — validasi username+password, lookup user via
  `ProjectUser::findByUsername()` (exact match, tanpa alias).
- Autentikasi sesi: `components/ProjectAuthContext.php` — key sesi `project_auth_<projectId>`;
  `isAuthenticated()` bernilai true juga bagi Commander superadmin.
- Database per-project: `components/ActiveDatabaseContext.php` — `resolveAndApply()` memilih
  database aktif dari `?database=`/`?db=`, sesi `active_dashboard_dashboard`, atau database
  project aktif (berdasarkan `ActiveProjectContext`).
- Akses menu/role: `ProjectPermissionService::canAccessRoute()` (baris 171) dan
  `resolveAccessibleLandingRoute()` (baris 594); role diambil ulang per request dari database
  project (tidak ada privilege yang di-cache di sesi).
- CSRF aktif secara default (Yii2); form login memakai `ActiveForm` (token `_csrf`).

---

## 4. Alur Autentikasi Workspace

1. Request masuk pada domain workspace → `DomainProjectResolver` (bootstrap
   `Application::EVENT_BEFORE_ACTION`) menyelesaikan project dari `custom_domain`, lalu
   mengabaikan rute public (`project/login`, dst.).
2. `actionLogin($id)` → `findAccessibleProject()` memvalidasi project dapat diakses
   (superadmin, cocok custom-domain, atau pemilik project via `user_id`).
3. `ActiveDatabaseContext::resolveAndApply(true)` — **patok database ke project aktif**
   (FIX-02).
4. `CommanderAuthContext::isSuperAdmin()` → bila superadmin, langsung redirect `/dashboard`.
5. Guest: render form login (GET) atau proses POST (CSRF + validasi + limiter).
6. Berhasil → `ProjectAuthContext::login()` menulis sesi + **regenerasi session ID**
   (FIX-01) → `resolveAccessibleLandingRoute()` menentukan rute awal.
7. Setiap request berikutnya: `ProjectAuthContext::loadUser()` mengecek ulang `status == 1`
   dan **mem-`pin` database ke project aktif** sebelum lookup identitas (FIX-02).

---

## 5. Isolasi Antar-Workspace

- Sesi autentikasi dikunci per-project (`project_auth_<projectId>`); autentikasi project A
  tidak otomatis memberi akses project B — project B tetap butuh login sendiri.
- `findAccessibleProject()` membatasi project yang bisa diakses: pengguna non-superadmin
  hanya bisa login ke project miliknya (`user_id`) atau project yang cocok dengan
  custom-domain host yang sedang dikunjungi.
- **Kerentanan yang ditemukan (FIX-02):** parameter `?database=` dan sesi
  `active_dashboard_database` sebelumnya dapat mengarahkan *di database mana identitas
  dicari/dipindahkan* pada batas autentikasi. Kini seluruh resolusi identitas di-patok ke
  database project aktif (`resolveAndApply(true)`), sehingga sebuah workspace tidak bisa
  mengautentikasi/mengubah identitasnya di database workspace lain.
- Batas Commander: `ProjectPermissionService::canAccessRoute()` (baris 171-198) hanya
  memberi akses menu superadmin bila sesi Commander `isSuperAdmin()`. Pemutakhiran role
  langsung terbaca karena role selalu dibaca ulang dari database per request.

**Hasil uji:** login workspace di project 22 tetap berhasil memakai database project 22
meskipun `?database=rt33` dikirim (diuji console + HTTP nyata). Username yang sama
(`secws_ok`) yang dibuat dengan password berbeda di database `test` dan `rt33` — login
menggunakan password versi `test` hanya berhasil dari `test`.

---

## 6. Resolusi Role & Eskalasi Hak Akses

- Role disimpan di kolom `users.role` di database project, dibaca **ulang setiap request**
  oleh `ProjectAuthContext::loadUser()` (baris 167-187). Tidak ada cache role di sesi,
  sehingga perubahan role berpengaruh langsung.
- Akses menu dicek melalui `ProjectPermissionService::canAccessRoute()` terhadap tabel
  `role_access`/`master_menu` di database project. Guest/non-role → akses ditolak.
- `findAccessibleProject()` (baris 165-192) mencegah guest menebak `id` project lain pada
  domain root: non-superadmin tidak bisa melewati kepemilikan `user_id`.
- **Tidak ditemukan jalur eskalasi** yang dapat dipicu dari login workspace. Identitas dan
  role diambil dari database project yang sama (kini dipatok), dan sesi hanya menyimpan
  salinan *dekoratif* `role` untuk keperluan tampilan; keputusan akses selalu memakai
  database.

---

## 7. Manajemen Sesi

**Temuan FIX-01 (Tinggi — Session Fixation):** `ProjectAuthContext::login()` menulis sesi
tanpa mengganti session ID. Jika penyerang mengetahui/membagikan sebuah session ID
pre-autentikasi, setelah korban login, sesi dengan ID tersebut menjadi sesi terautentikasi
(fixation). 

**Perbaikan:** `login()` sekarang memanggil `regenerateSessionId()` setelah menulis data
sesi (`components/ProjectAuthContext.php` baris 58-93), mirip pola yang sudah diterapkan
pada Commander (`CommanderAuthContext::regenerateSessionId()`). Best-effort: bila store
menolak regenerasi, hasil autentikasi tetap dipertahankan dan kegagalan hanya di-log.

**Fakta lain yang diverifikasi:**
- `loadUser()` memvalidasi ulang `status == 1` pada setiap request (baris 182-184).
- Logout memakai `ProjectAuthContext::logout()` → menghapus key sesi project
  (`actionLogout`, baris 1136-1165) dan me-reset cache `currentIdentity`.
- FIX-06 (Rendah): `actionLogout` sebelumnya dapat diakses via GET. Semua UI memakai form
  POST (diverifikasi di `_sidebar.php` dan `_logout_button.php`, tidak ada `<a href>` GET).
  Kini `VerbFilter` membatasi `logout => ['post']` (`ProjectController` baris 458-464).
- Sesi dihapus total (`session->destroy()`) tidak dilakukan saat logout workspace agar
  autentikasi Commander/workspace lain tetap utuh — sesuai desain multi-workspace.

**Hasil uji:** HTTP nyata — PHPSESSID berubah antara GET form dan POST login sukses
(`qtgmu...` → `ebka...`); GET `/project/logout` → **405**; POST `/project/logout` → 302;
`/dashboard` setelah logout → 302 ke `project/login` (tidak lagi 200).

---

## 8. Brute Force & Rate Limiting

**Temuan FIX-03 (Sedang):** login workspace tidak memiliki proteksi per-username.
`AppSecurityBootstrap` hanya memberikan bucket `auth` global (~12 request/5 menit per IP+UA)
sebagai baseline.

**Perbaikan:** limiter yang sudah ada (`CommanderLoginLimiter`) diaktifkan pada
`actionLogin` POST dengan **scope per-workspace**:
- `CommanderLoginLimiter` diberi argumen konstruktor opsional `?string $scope`
  (`components/CommanderLoginLimiter.php` baris 39-50). Scope mem-`namespace` hanya counter
  **per-username** (`key('user', ...)` → `md5(scope|username)`, baris 124-131); counter IP
  tetap global. Backward-compatible: tanpa scope, perilaku identik dengan SECURITY-01.
- `actionLogin` (baris 975-1019):
  - scope `'project:' . $projectId` — username yang sama di workspace lain **tidak berbagi**
    counter, sehingga tidak bisa dilakukan lockout lintas-workspace;
  - `isLocked()` dicek **sebelum** validasi; bila terkunci → flash error generik
    ("Terlalu banyak percobaan login…") + render form, tanpa memproses kredensial;
  - `onSuccess()` setelah login sukses → counter username dibersihkan;
  - `onFailure()` untuk setiap percobaan gagal yang di-submit (username/password non-kosong;
    percobaan kosong tidak dihitung, mencegah serangan denial dengan submit kosong);
  - window: 5 percobaan / 15 menit; lockout selalu berakhir sendiri (TTL).

**Catatan:** pada mesin dev Windows ini, FileCache aplikasi memakai keyPrefix
`yii-dynamic:` (karakter `:` membuat file cache gagal di NTFS) sehingga limiter **fail-open**
di dev. Di produksi (Linux/Redis/cache normal) limiter berfungsi normal. Hal yang sama
sudah didokumentasikan pada SECURITY-01.

**Hasil uji:** limiter terkunci setelah 5 kegagalan; scope `project:22` dan `project:19`
tidak saling terkunci (dari IP bersih); IP lock global; flood username/password kosong tidak
mengunci; percobaan valid saat terkunci **ditolak** di controller (renders form, tanpa sesi);
login valid dari IP bersih tetap berhasil setelah `onSuccess`.

---

## 9. CSRF

- CSRF diaktifkan secara default oleh Yii2 (`enableCsrfValidation`). Form login memakai
  `ActiveForm::begin()` yang menyuntikkan `_csrf`; tombol logout di sidebar juga memakai
  form POST dengan `_csrf`.
- Verifikasi HTTP nyata: POST login tanpa `_csrf` yang valid → **400 Bad Request**;
  POST dengan `_csrf` dari halaman form → diproses. Tidak ada aksi mutasi pada batas login
  yang dapat di-trigger tanpa token CSRF yang valid.
- **Tidak ada temuan CSRF** pada batas workspace login.

---

## 10. Enumerasi User & Kebocoran Informasi

**Temuan FIX-04 (Sedang — timing oracle + crash):**
- `ProjectUser::validatePassword()` (sebelumnya) memanggil `security->validatePassword()`
  tanpa guard. Untuk user dengan `password_hash` kosong/korup, PHP dapat melempar
  `InvalidArgumentException` → **HTTP 500** (crash), yang sekaligus menandakan keberadaan
  akun.
- `ProjectLoginForm::validatePassword()` tidak menjalankan hash dummy untuk username yang
  tidak dikenal → respons jauh lebih cepat untuk username tak dikenal dibanding username
  terdaftar (timing-based enumeration).

**Perbaikan:**
- `models/ProjectUser.php` — `validatePassword()` kini: password kosong → `false`;
  hash kosong → `false`; bungkus dengan try/catch `InvalidArgumentException` → `false`
  (baris 56-69).
- `models/ProjectLoginForm.php` — untuk username tak dikenal, tetap menjalankan
  `security->validatePassword($password, self::DUMMY_HASH)` agar waktu respons setara
  (baris 39-46). `DUMMY_HASH` adalah hash bcrypt cost-13 dari string acak, sama seperti
  yang dipakai `models/LoginForm.php` (Commander).

**Temuan FIX-05 (Sedang — akun nonaktif):** sebelumnya, user dengan `status != 1` dan
password benar tetap melewati `validate()` (password valid) dan **membuat sesi** di
`actionLogin`; akun baru ditolak pada request berikutnya oleh `loadUser()`. Selain
menyimpang dari kebijakan akun nonaktif, ini memberi sinyal bahwa kredensial benar.

**Perbaikan:** `actionLogin` hanya memanggil `login()` bila `(int)$user->status === 1`
(baris 993). Untuk user nonaktif/unknown dengan password valid, ditambahkan error generik
"Username atau password salah." (baris 1011-1015) sehingga **tidak bisa dibedakan** dari
password salah.

**Hasil uji:** unknown user, wrong password, disabled user → semuanya 200, pesan generik
yang sama, tanpa sesi. HTTP nyata: `secws_http_dis` (status 0) dengan password benar → 200
+ pesan generik, tidak ada redirect. Empty password → tidak melempar. Hash korup → tidak
melempar.

---

## 11. Open Redirect & Return URL

- `return_url` pada form login dilewatkan ke `ProjectPermissionService::resolveAccessibleLandingRoute()`
  → `normalizeIncomingRoute()` (baris 1157) yang hanya memakai bagian *path* dari
  `parse_url`, lalu `Url::to([$route])` → URL selalu internal. Host/query di-strip.
- **Tidak ada open redirect** yang dapat dieksploitasi dari `return_url` pada batas login.
- Redirect setelah login selalu ke rute internal (`/dashboard`, `/project/access-denied`,
  rute menu role), diverifikasi pada HTTP nyata (Location internal).

---

## 12. Penanganan Password & Remember Me

- Hash password: bcrypt via `Yii::$app->security` (cost 13), format `$2y$13$…`, kompatibel
  antar Commander dan workspace. Tidak ada penyimpanan plaintext.
- `must_change_password` ditampilkan sebagai warning setelah login (password default), dan
  wajib diganti di `project/change-password` (dicek `ProjectAuthContext::requiresPasswordChange()`).
- Tidak ada fitur "remember me" pada workspace login; sesi adalah cookie session
  (hilang saat browser ditutup). Sesi workspace tidak menggunakan cookie persistent
  autologin → tidak ada risiko token remember-me yang bocor.
- Password tidak pernah di-log; `onFailure` hanya mencatat counter, bukan kredensial.
- Setelah login sukses, `$model->password` dikosongkan sebelum render.

---

## 13. Status Akun & Status Workspace

- Akun nonaktif (`status != 1`): tidak bisa membuat sesi login (FIX-05); jika sesi lama
  masih ada, `loadUser()` menolak di request berikutnya (baris 182-184).
- Workspace tidak memiliki "status" terpisah pada login; akses project dikontrol oleh
  `findAccessibleProject()` (superadmin / custom-domain / pemilik). Bila role belum punya
  akses menu, `resolveAccessibleLandingRoute()` mengembalikan null → redirect
  `project/access-denied` (perilaku yang sudah ada, dipertahankan).
- `project/login` (tanpa id) pada domain workspace menyelesaikan project dari
  `resolveWorkspaceProjectIdFromDomain()` → `findAccessibleProject()` → aman dari penebakan id.

---

## 14. Batas Commander vs Workspace

- Commander (`site/login`) dan workspace (`project/login`) adalah dua jalur autentikasi
  terpisah dengan sesi terpisah: `CommanderAuthContext` (key sesi Commander) vs
  `ProjectAuthContext` (`project_auth_<id>`).
- Sesi Commander superadmin **melewati** login workspace (bypass ke `/dashboard`, lihat
  `DomainProjectResolver` baris 94-107) — perilaku yang sudah dirancang dan diverifikasi di
  SECURITY-01.
- `project/logout` tidak menghapus sesi Commander; sebaliknya, saat superadmin logout di
  workspace, ia diarahkan ke `/site/logout` (actionLogout baris 1138-1140).
- `CommanderLoginLimiter` dipakai bersama (limiter yang sama) — perubahan scope-nya
  backward-compatible dan tidak mengubah perilaku Commander (suite SECURITY-01 tetap
  **51/51 lulus**).

---

## 15. Temuan & Analisis Risiko

| Kode | Deskripsi | Severity | Dampak sebelum perbaikan |
|------|-----------|----------|--------------------------|
| FIX-01 | Session fixation pada login workspace | Tinggi | Penyerang yang memegang session ID pre-login dapat menjadi session terautentikasi korban |
| FIX-02 | `?database=`/sesi dapat mengarahkan database autentikasi | Sedang | Klien dapat membuat identitas dicari di database workspace lain (kebingungan identitas lintas-workspace) |
| FIX-03 | Tidak ada proteksi brute-force per-username | Sedang | Tebak password tanpa batas (hanya bucket global IP+UA) |
| FIX-04 | `validatePassword` bisa crash (500) + timing oracle | Sedang | DoS ringan + enumerasi username via waktu respons |
| FIX-05 | Akun nonaktif dapat membuat sesi login | Sedang | Akun dinonaktifkan tetap bisa meng-autentikasi; sinyal kredensial benar |
| FIX-06 | Logout via GET | Rendah | Logout dapat dipicu silang-situs (CSRF-style) bila ada link GET |

Severity dikalibrasi dengan fakta arsitektur: project adalah entitas *multi-tenant* yang
terisolasi per database/domain, sehingga eksploitasi lintas-workspace memerlukan akses ke
domain workspace itu sendiri.

---

## 16. Perubahan Kode yang Diimplementasikan

| File | Perubahan | Baris |
|------|-----------|-------|
| `components/ProjectAuthContext.php` | `login()` kini meregenerasi session ID; `loadUser()` memanggil `resolveAndApply(true)` sebelum lookup identitas | 58-93, 167-187 |
| `components/ActiveDatabaseContext.php` | `resolveAndApply(bool $pinToActiveProject = false)` — saat `true`, `?database=`/`db=` dan sesi `active_dashboard_database` diabaikan; database diambil dari project aktif | 34-53 |
| `controllers/ProjectController.php` | `actionLogin`: pin DB (`resolveAndApply(true)`), limiter scope `project:<id>` (`isLocked`→`onSuccess`/`onFailure`), gate `status === 1`, error generik untuk akun nonaktif; `actionChangePassword` & `actionAccessDenied` juga pin DB; `VerbFilter` membatasi `logout => ['post']` | 950, 975-1019, 1083, 1202, 458-464 |
| `components/CommanderLoginLimiter.php` | Argumen konstruktor opsional `?string $scope`; counter per-username di-namespace (IP tetap global) | 39-50, 124-131 |
| `models/ProjectUser.php` | `validatePassword()` di-hardening (guard kosong + try/catch `InvalidArgumentException`) | 56-69 |
| `models/ProjectLoginForm.php` | Timing equalizer `DUMMY_HASH` untuk username tidak dikenal; `use Yii` | 19, 39-46 |

Tidak ada perubahan skema database. Tidak ada dependensi baru. Tidak ada hardcoding
id/username/role di logika produksi.

---

## 17. Hasil Pengujian Regresi

**Suite console workspace (baru):** `workspace_auth_test.php` — **42/42 lulus**:
- Login valid → 302 + **session ID diregenerasi** + identitas benar (database project).
- Pinning DB: `?database=rt33` tidak mengalihkan identitas (user `secws_ok` di `test`
  vs password berbeda di `rt33`).
- Wrong password / unknown user / disabled user → ditolak, pesan generik sama, tanpa sesi.
- Empty password & hash korup → tidak melempar.
- Logout GET → 405; logout POST → 302 + sesi workspace dibersihkan.
- Limiter: lock setelah 5 gagal; scope antar-workspace tidak saling mengunci (IP bersih);
  IP global; flood kosong tidak mengunci; percobaan valid saat lock ditolak; IP bersih +
  `onSuccess` → sukses.

**Suite console SECURITY-01 (regresi):** `commander_auth_test.php` — **51/51 lulus**
(perubahan limiter backward-compatible).

**Verifikasi HTTP nyata (php -S 127.0.0.1:8091, Host: test.appforge.web.id):**
- GET `/project/login/22` → 200 (form + `_csrf`).
- POST login valid → **302**, PHPSESSID berubah (anti-fixation).
- POST login valid + `?database=rt33` → **302** (identitas tetap dari database project).
- POST password salah → 200 + pesan generik; user nonaktif → 200 + pesan generik.
- GET `/project/logout` → **405 Method Not Allowed**.
- POST `/project/logout` → 302; `/dashboard` setelah logout → 302 ke `project/login`.

`php -l` bersih untuk seluruh 6 file yang diubah.

---

## 18. Risiko Residual, Rekomendasi & Kesimpulan

**Risiko residual (dokumentasi, bukan yang baru diperkenalkan):**
1. **Limiter fail-open di dev Windows** — FileCache dengan keyPrefix `yii-dynamic:` gagal di
   NTFS. Di produksi (Linux/Redis/cache normal) limiter berfungsi. Jangan menurunkan ke
   environment tanpa cache yang berfungsi tanpa menyadari konsekuensinya.
2. **Fitur perpindahan database dashboard (`?database=`) tetap aktif** untuk penjelajahan
   data (fitur inti). Kini **identitas/autentikasi selalu di-patok** ke project aktif,
   tetapi data pada halaman dashboard masih mengikuti `?database=`/sesi. Disarankan fase
   berikutnya: batasi parameter `?database=` ke peran admin/superadmin dan kunci sesi
   `active_dashboard_database` per-project.
3. **Fallback sesi legacy `project_app_auth:<id>`** masih dibaca oleh
   `ProjectAuthContext::getSessionData()` (baris 157-165) untuk kompatibilitas. Bila tidak
   lagi dipakai, fallback dapat dihapus di fase berikutnya.
4. **Detail logging** (`DomainDebugLogger`, `RedirectDebugLogger`, dsb.) mengikuti pola
   aplikasi; di produksi pastikan level log tidak membocorkan host/path sensitif.
5. **`site/logout` Commander** masih GET-reachable (di luar cakupan SECURITY-02; UI memakai
   POST). Dapat dibatasi `post` pada fase berikutnya dengan pertimbangan UX multi-workspace.

**Kesimpulan / Verdict:** Seluruh temuan pada batas Workspace Login yang masuk akal untuk
diperbaiki pada audit ini telah ditutup dengan perubahan minimal dan terverifikasi.
Workspace login kini: (a) kebal session fixation, (b) identitas terikat ke database project
sendiri, (c) dilindungi brute-force per-workspace + per-IP, (d) tidak bocorkan keberadaan
user via waktu/crash, (e) menolak akun nonaktif secara konsisten, dan (f) logout hanya via
POST. Tidak ditemukan jalur eskalasi role, open redirect, atau kebocoran CSRF pada batas ini.
Status: **VERIFIED**.