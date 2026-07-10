# Activity Log — Desahub

> **Tujuan:** Mencatat semua aktivitas teknis (coding, error, perbaikan, deployment) agar saat ganti komputer bisa langsung tahu progres terakhir, error yang masih terbuka, dan apa yang sudah dikerjakan.
>
> **Aturan:** Setiap entri wajib tulis: **tanggal, komputer yang dipakai (jika ganti), aktivitas, error (jika ada), perbaikan/solusi.** Update entri terbaru di paling atas.
>
> **File terkait:**
> - Keputusan arsitektur & desain → `last_update.md`
> - Spesifikasi teknis lengkap → `project.md`
> - Registry event → `events.md`
> - Syarat & ketentuan → `syarat_ketentuan.md`
> - Cara akses & URL → `cara_akses.md`

---

## Format Entri

```
### [YYYY-MM-DD] [Komputer: ...]

**Aktivitas:**
- [x] Selesai: ...
- [ ] Sedang dikerjakan: ...
- [ ] Belum: ...

**Error ditemukan:**
| # | Error | Penyebab | Solusi | Status |
|---|-------|----------|--------|--------|
| 1 | ... | ... | ... | ✅ / 🔧 / ❌ |

**Catatan / Blocker:** ...
```

---

## Log Aktivitas

### [2026-07-10] [Komputer: Laptop 1] — ✅ Debug Auth + Pivot Flutter→PWA

**Aktivitas:**
- [x] **Debug registrasi & login Flutter gagal** — investigasi mendalam mengapa semua endpoint auth return error
- [x] Ditemukan 3 akar masalah:
  1. Flutter client (auth_service.dart) salah URL (`/v1/register` → `/v1/auth/register`) dan field name (`hp` → `no_hp`)
  2. Backend Laravel: `.env` tidak ada, `APP_KEY` kosong, `bootstrap/cache` corrupt (double-slash namespace `Modules//Core//ap...`), `DB_HOST=127.0.0.1` timeout
  3. Server `php artisan serve` tidak berjalan
- [x] **Fix Flutter 5 file:** `auth_service.dart` (URL+field), `login_screen.dart` ×3 (konsumen/outlet/kurir — `hp:` → `noHp:`, hapus `peran:`)
- [x] **Fix Backend:** copy `.env.example` → `.env`, generate `APP_KEY`, ubah `DB_HOST=localhost`, bersihkan `bootstrap/cache/`, `composer dump-autoload`, create DB `desahub`, migrate (10 tabel sukses), publish `config/sanctum.php`
- [x] **Fix Routes:** prefix `v1` → `api/v1` di `Modules/Auth/routes/api.php`, tambah backward-compatible routes (tanpa `/auth/` path)
- [x] **Keputusan: Pivot dari Flutter ke PWA** (sesuai `versi_pwa.md` Sesi 9 & 10 di `last_update.md`) — Andi kesulitan Flutter, sepakat pakai Blade + session-based dulu, Flutter dilanjutkan setelah PWA tervalidasi
- [x] **Install Livewire:** `composer require livewire/livewire` (v4.3.3 terinstall)
- [x] **PWA — Backend:**
  - `app/Http/Controllers/WebAuthController.php` — session-based auth: showLogin, login, showRegister, register, showVerifyOtp, verifyOtp, logout
  - `routes/web.php` — 36 routes: `/warung/*`, `/konsumen/*`, `/kurir/*` (GET+POST login, register, verify-otp; GET dashboard)
- [x] **PWA — Layout 3 role:** `resources/views/layouts/warung.blade.php` (🔴), `konsumen.blade.php` (🔵), `kurir.blade.php` (🟢) — masing-masing dengan manifest + Service Worker scope sendiri
- [x] **PWA — Auth views:** `resources/views/auth/login.blade.php`, `register.blade.php`, `verify-otp.blade.php` — Blode form session-based (CSRF protected)
- [x] **PWA — Dashboard views:** `resources/views/warung/dashboard.blade.php`, `konsumen/dashboard.blade.php`, `kurir/dashboard.blade.php` — placeholder siap diisi fitur
- [x] **PWA — Service Worker:** `public/sw.js` — App Shell Caching (Lapis 1), network-first untuk HTML, cache-first untuk static
- [x] **PWA — Manifests:** `public/manifest-warung.json`, `manifest-konsumen.json`, `manifest-kurir.json`
- [x] **PWA — Offline JS:** `public/js/cache-snapshot.js` (Lapis 2 — read-only cache localStorage), `public/js/write-queue.js` (Lapis 3 — antrian aksi kritis IndexedDB + auto-sync)
- [x] **Update `docs/cara_akses.md`:** ganti panduan Flutter → panduan PWA (URL, install sebagai app, arsitektur offline, troubleshooting)
- [x] 36 routes terkonfirmasi terdaftar (`php artisan route:list`)
- [x] Livewire components Auth (Login, Register, VerifyOtp) dibuat di `app/Livewire/Auth/` — siap dipakai untuk komponen interaktif nanti
- [ ] Sedang dikerjakan: lanjut `Modules/Outlet`

**Error yang ditemukan & difix:**
| # | Error | Penyebab | Solusi | Status |
|---|-------|----------|--------|--------|
| 11 | Flutter `ERR_CONNECTION_REFUSED` | Server `php artisan serve` tidak jalan | Jalankan `php artisan serve --port=8000` | ✅ |
| 12 | Flutter registrasi & login selalu gagal | URL endpoint salah (`/v1/register` vs `/v1/auth/register`) + field name mismatch (`hp` vs `no_hp`) | Fix 5 file Dart + tambah backward-compat routes | ✅ |
| 13 | Laravel `500 Class not found` (double-slash namespace) | `bootstrap/cache` corrupt — path jadi `Modules//Core//ap...` | Hapus `bootstrap/cache/*`, `composer dump-autoload` | ✅ |
| 14 | `No application encryption key` | `.env` tidak ada, `APP_KEY` kosong | Copy `.env.example` → `.env`, `php artisan key:generate` | ✅ |
| 15 | MySQL connection timeout 60s (`MySqlConnection.php:47`) | `DB_HOST=127.0.0.1` gagal resolve di XAMPP | Ganti ke `DB_HOST=localhost` | ✅ |
| 16 | Livewire `make:livewire` command not found | Composer install belum selesai | Selesai (v4.3.3 terinstall) | ✅ |
| 17 | Route `warung/login` 404 | Route prefix mismatch (`/v1` vs `/api/v1`) | Fix `Modules/Auth/routes/api.php` prefix + tambah backward-compat routes | ✅ |

**Catatan penting:**
- Flutter (`desahub_flutter/`) **DINONAKTIFKAN SEMENTARA** — tidak dikerjakan, fokus development di PWA
- Backend API (Sanctum) **TETAP JALAN** — untuk Lapis 3 write queue + integrasi masa depan
- Livewire v4.3.3 sudah terinstall — siap dipakai untuk komponen interaktif (dashboard Warung, pencarian outlet, dll) tanpa full-page reload
- Routes: 36 routes total — 14 API + 22 PWA web

---

### [2026-07-10] [Komputer: Laptop 1] — ✅ Core + Auth selesai, DB & migrasi OK

**Aktivitas:**
- [x] Membuat file `activity.md` untuk tracking aktivitas lintas komputer
- [x] Membuat file `cara_akses.md` — panduan URL, endpoint API, menjalankan Flutter, akses database, troubleshooting
- [x] Fix error #2: buat `Modules/Core/app/Providers/CoreServiceProvider.php`
- [x] Fix error #3, #4, #5: nonaktifkan modul yang belum siap
- [x] **`Modules/Core` SELESAI** — 3 kontrak, trait, helper radius, 4 Event DTO
- [x] MySQL XAMPP hidup → database `desahub` dibuat, `migrate:fresh` sukses
- [x] Konsolidasi migration: semua migration dipusatkan di `database/migrations/`, hapus duplikat dari modul
- [x] **`Modules/Auth` SELESAI** — 11 file baru:
  - `app/Providers/AuthServiceProvider.php`
  - `app/Models/User.php` — Sanctum HasApiTokens + relasi ke Outlet/Konsumen/Kurir profiles
  - `app/Models/OutletProfile.php` — generik untuk pemilik outlet
  - `app/Models/KonsumenProfile.php` — default semua user
  - `app/Models/KurirProfile.php` — kurir dengan status online/GPS
  - `app/Http/Controllers/AuthController.php` — register, login, verify OTP, me, logout
  - `routes/api.php` — prefix v1: POST auth/register, auth/login, auth/verify-otp, GET auth/me, POST auth/logout
  - `database/migrations/2026_07_10_000002_create_users_table.php` — users + personal_access_tokens (Sanctum)
  - `database/migrations/2026_07_10_000003_create_auth_profiles_tables.php` — outlet/konsumen/kurir_profiles
  - (config/auth.php diupdate: api guard, User model)
- [ ] Sedang dikerjakan: **`Modules/Outlet`**

**Error yang sudah fix:**
| # | Error | Status |
|---|-------|--------|
| 1 | `composer dump-autoload` timeout | 🔧 (bersihkan cache jika perlu) |
| 2 | `CoreServiceProvider` not found | ✅ |
| 3 | `RouteServiceProvider` not found (Auth) | ✅ |
| 4 | `AdminServiceProvider` not found | ✅ |
| 5 | `AdminDatabaseSeeder` not found | ✅ |
| 6 | Unknown database 'desahub' | ✅ |
| 7 | No application encryption key | ✅ |
| 8 | Maximum execution time 60s | ✅ (pakai XAMPP Apache) |
| 9 | Table 'sessions' doesn't exist | ✅ |
| 10 | Tinker syntax error | ✅ (bukan bug) |

**Database — tabel yang sudah dibuat:**
- `users` — nama, no_hp, password, email, OTP, soft deletes
- `personal_access_tokens` — Sanctum
- `outlet_profiles` — FK users, NIK, foto KTP
- `konsumen_profiles` — FK users, alamat, GPS
- `kurir_profiles` — FK users, is_online, GPS, kendaraan
- `ketersediaan_cache` — sellable polymorphic + qty (unique)
- `ketersediaan_movements` — log append-only
- `cache`, `jobs` — default Laravel
- `sessions` — default

**Lanjutan:**
1. `Modules/Outlet` — tabel outlets, outlet_vertikal, verifikasi
2. `Modules/Warung` — implementasi Sellable pertama
3. `Modules/Order` — buyer polymorphic, checkout, state machine
4. `Modules/Payment` — COD, event PembayaranDiterima
5. `Modules/Kurir` — online/offline, claim order atomik

---

## Checklist Setup Komputer Baru

Saat pindah ke komputer lain, lakukan ini berurutan:

1. **Clone repo:** `git clone https://github.com/lahankosong/Desahub.git`
2. **Install dependencies:** `composer install` dan `npm install` (jika Flutter ingin dijalankan)
3. **Copy `.env`:** `cp .env.example .env` lalu edit konfigurasi database lokal
4. **Generate key:** `php artisan key:generate`
5. **Buat database:** buat database `desahub` di MySQL (XAMPP/Laragon)
6. **Jalankan migrasi:** `php artisan migrate`
7. **Check modul status:** `php artisan module:list`
8. **Baca log terbaru:** cek `docs/activity.md` entri paling atas untuk tahu progres terakhir
9. **Check error:** cek `storage/logs/laravel.log` untuk error baru yang mungkin muncul setelah setup

---

## Status Keseluruhan (per 10 Juli 2026)

| Area | Status |
|------|--------|
| Arsitektur & desain | ✅ Selesai (10 sesi diskusi, lihat `last_update.md`) |
| Spesifikasi teknis | ✅ Selesai (lihat `project.md`) |
| Event registry | ✅ Selesai (lihat `events.md`) |
| Syarat & ketentuan | ✅ Draft awal (lihat `syarat_ketentuan.md`) |
| `Modules/Core` | ✅ Selesai (3 kontrak + trait + helper + 4 Event DTO) |
| `Modules/Auth` | ✅ Selesai (User model + Sanctum + 3 profil + AuthController + routes) |
| PWA — WebAuthController | ✅ Selesai (session-based auth: login, register, verify-otp) |
| PWA — Layouts (3 role) | ✅ Selesai (warung, konsumen, kurir) |
| PWA — Auth views | ✅ Selesai (login, register, verify-otp) |
| PWA — Dashboards | ✅ Selesai (placeholder warung/konsumen/kurir) |
| PWA — Service Worker | ✅ Selesai (`sw.js` — Lapis 1 offline) |
| PWA — Manifests (3) | ✅ Selesai (manifest-warung, -konsumen, -kurir) |
| PWA — Offline JS | ✅ Selesai (cache-snapshot.js, write-queue.js) |
| Livewire (v4.3.3) | ✅ Terinstall — siap untuk komponen interaktif |
| `Modules/Outlet` | 🔧 Sedang dikerjakan (berikutnya) |
| `Modules/Warung` | ❌ Belum dimulai |
| `Modules/Order` | ❌ Belum dimulai |
| `Modules/Payment` | ❌ Belum dimulai |
| `Modules/Kurir` | ❌ Belum dimulai |
| Flutter (app_konsumen) | ⏸️ Dinonaktifkan sementara (PWA dulu) |
| Flutter (app_kurir) | ⏸️ Dinonaktifkan sementara |
| Flutter (app_outlet) | ⏸️ Dinonaktifkan sementara |
| Database | ✅ 10 tabel terbuat (migrate sukses) |
| Server | ✅ `php artisan serve --port=8000` |