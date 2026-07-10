# Cara Akses — Desahub (PWA)

> Tujuan: panduan cepat untuk menjalankan dan mengakses PWA Desahub (Blade + session-based).

---

## 1. Backend (Laravel)

### Prasyarat
- XAMPP harus jalan: **Apache** + **MySQL** (buka XAMPP Control Panel → Start keduanya)
- PHP 8.2+ sudah di PATH (via XAMPP)
- Composer sudah terinstall

### Menjalankan Server Development
```bash
cd c:\xampp\htdocs\Desahub
php artisan serve --port=8000
```
URL: `http://127.0.0.1:8000`

> **Note:** Pakai `php artisan serve` untuk development. Di production, arahkan Apache/Nginx ke `public/`.

### Artisan Commands Penting
```bash
cd c:\xampp\htdocs\Desahub

php artisan module:list      # Cek status modul
php artisan migrate           # Jalankan migrasi
php artisan migrate:fresh     # Reset database
php artisan route:list        # Cek semua route terdaftar
php artisan key:generate      # Generate APP_KEY
php artisan optimize:clear    # Clear semua cache
composer dump-autoload        # Refresh autoload
```

---

## 2. PWA — Multi-Role Access

Desahub PWA adalah **1 aplikasi Laravel** dengan 3 role berbeda, diakses lewat URL prefix:

| Role | URL | Warna Theme | Deskripsi |
|------|-----|------------|-----------|
| Warung | `/warung` | 🔴 Merah | Kelola produk & lihat order masuk |
| Konsumen | `/konsumen` | 🔵 Biru | Cari warung, checkout COD |
| Kurir | `/kurir` | 🟢 Hijau | Klaim order & update status antar |

### Halaman Auth (shared, per role)

| Path | Keterangan |
|------|------------|
| `/{role}/login` | Halaman login |
| `/{role}/register` | Halaman register |
| `/{role}/verify-otp` | Verifikasi OTP |

### Halaman Protected (setelah login)

| Path | Keterangan |
|------|------------|
| `/warung` | Dashboard Warung |
| `/konsumen` | Dashboard Konsumen |
| `/kurir` | Dashboard Kurir |
| `POST /logout` | Logout (shared) |

---

## 3. Install Sebagai App (PWA)

Setiap role bisa di-install sebagai app terpisah di layar HP:

1. Buka `http://127.0.0.1:8000/{role}` di Chrome Android / Safari iOS
2. Chrome: tap ⋮ → "Add to Home Screen"
3. Safari: tap Share → "Add to Home Screen"

Hasil: 3 ikon berbeda di layar HP (Warung, Belanja, Kurir).

### File PWA

| File | Fungsi |
|------|--------|
| `public/sw.js` | Service Worker — App Shell Caching |
| `public/manifest-warung.json` | Manifest untuk role Warung |
| `public/manifest-konsumen.json` | Manifest untuk role Konsumen |
| `public/manifest-kurir.json` | Manifest untuk role Kurir |
| `public/js/cache-snapshot.js` | Lapis 2: Read-only cache offline |
| `public/js/write-queue.js` | Lapis 3: Antrian aksi kritis offline |

---

## 4. Arsitektur Offline 3 Lapis

### Lapis 1 — App Shell Caching (Service Worker)
Precache aset statis. Saat offline, app tetap terbuka menampilkan UI + pesan status, bukan error browser kosong.

### Lapis 2 — Data Terakhir Terlihat
Snapshot data terakhir disimpan di `localStorage`. Saat offline, data ini ditampilkan read-only.

Gunakan di JS:
```js
saveSnapshot({ orders: [...] });   // simpan
let snap = getSnapshot();          // baca
```

### Lapis 3 — Antrian Aksi Kritis (Write Queue)
Hanya untuk aksi kritis: Kurir update status antar, Warung toggle ketersediaan.

Gunakan di JS:
```js
enqueueAction('POST', '/kurir/orders/123/update-status', {
    status: 'diantar'
});
```

Aksi masuk queue saat offline, otomatis dikirim ke server saat online kembali.

---

## 5. API Endpoints (Backward Compatible)

Semua endpoint API tetap jalan (untuk Lapis 3 write queue + integrasi eksternal):

| Method | Endpoint | Auth | Keterangan |
|--------|----------|------|------------|
| `POST` | `/api/v1/auth/register` | Public | Registrasi user baru |
| `POST` | `/api/v1/auth/login` | Public | Login (token Sanctum) |
| `POST` | `/api/v1/auth/verify-otp` | Public | Verifikasi OTP |
| `GET` | `/api/v1/auth/me` | Sanctum | Profil user |
| `POST` | `/api/v1/auth/logout` | Sanctum | Revoke token |

### Contoh Request (cURL)
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"nama":"Budi","no_hp":"081234567890","password":"rahasia123"}'
```

---

## 6. Database

### Akses MySQL
```bash
# phpMyAdmin (GUI)
http://localhost/phpmyadmin
# Login: root / (tanpa password)

# Command line
c:\xampp\mysql\bin\mysql -u root
```

### Database & Tabel
- **Database:** `desahub`
- Tabel: `users`, `personal_access_tokens`, `outlet_profiles`, `konsumen_profiles`, `kurir_profiles`, `ketersediaan_cache`, `ketersediaan_movements`, `sessions`, `cache`, `jobs`

---

## 7. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Connection refused` (MySQL) | Start MySQL di XAMPP Control Panel |
| `No application encryption key` | `php artisan key:generate` |
| `Table not found` | `php artisan migrate` |
| `404 Not Found` | Cek URL prefix: `/warung`, `/konsumen`, `/kurir` |
| `419 CSRF token mismatch` | Form POST harus pakai `@csrf` |
| `500 Server Error` | Cek `storage/logs/laravel.log` |
| `Class not found` | `composer dump-autoload` |
| `php artisan` timeout | Bersihkan `bootstrap/cache/`, jalankan `php artisan optimize:clear` |

---

## 8. Flutter (Dinonaktifkan Sementara)

Folder `desahub_flutter/` masih ada tapi **TIDAK** dikerjakan untuk saat ini. Fokus development ada di PWA (Blade + session). Flutter akan dilanjutkan setelah PWA tervalidasi — backend (API + Modules) tidak berubah, tinggal tambah client Flutter baru yang konsumsi endpoint yang sama.