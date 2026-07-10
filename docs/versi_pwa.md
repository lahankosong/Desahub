# Versi Uji Coba: PWA (Progressive Web App)

## Konteks & Tujuan
Pengembangan Flutter dirasa berat oleh Andi. Untuk uji coba dan validasi konsep, dibangun dulu versi **PWA berbasis Laravel** — memanfaatkan skill yang sudah terbukti di project EMINOR dan Margonoandi Fanbase. Flutter tetap jadi opsi jangka panjang kalau PWA sudah tervalidasi dan butuh pengalaman native yang lebih dalam.

**Prinsip utama:** ini murni **penggantian lapisan presentasi (frontend)**. Semua yang sudah didesain di `project.md` dan `events.md` — modul `Core`/`Auth`/`Outlet`/`Warung`/`Order`/`Payment`/`Kurir`, kontrak `Sellable`/`BuyerEligibilityPolicy`/`ComplianceReportable`, state machine Order, strategi resolusi konflik, aturan Warung Grosir — **semuanya tetap berlaku apa adanya**. Backend tidak didesain ulang.

---

## Stack Frontend
**Blade + Livewire + Alpine.js** — dipilih karena paling ringan dan konsisten dengan skill Laravel yang sudah dikuasai, dibanding SPA penuh (Vue/React) yang perlu belajar ekosistem JS baru.

## Kejujuran Soal Offline & Livewire
Livewire bekerja dengan render ulang komponen lewat request ke server — secara arsitektur **butuh koneksi**. Tidak realistis membuat seluruh app Livewire jalan offline tanpa mengubahnya jadi arsitektur lain sepenuhnya.

**Solusi:** pisahkan tegas mana yang boleh butuh koneksi vs mana yang wajib tetap jalan offline, alih-alih memaksa semuanya offline atau menyerah sepenuhnya pada online-only.

### Arsitektur Offline 3 Lapis

**Lapis 1 — App Shell Caching (Service Worker)**
- Precache aset statis (CSS/JS/ikon) dan shell HTML dasar.
- Efeknya: PWA tetap terbuka menampilkan UI + pesan status "offline", BUKAN error browser kosong, walau tanpa koneksi sama sekali.

**Lapis 2 — Data Terakhir Terlihat (read-only cache)**
- Snapshot data yang terakhir di-render Livewire (daftar order, status pengiriman, daftar produk) disimpan ke `localStorage`/IndexedDB lewat Alpine.js saat online.
- Saat offline, data ini ditampilkan **read-only** — pengguna bisa lihat kondisi terakhir, tapi tidak bisa berinteraksi lewat Livewire (karena memang butuh server).

**Lapis 3 — Antrian Aksi Kritis (write queue, TERPISAH dari Livewire)**
- Hanya untuk aksi yang benar-benar krusial saat sinyal hilang:
  - Kurir update status antar (`diambil` → `diantar` → `selesai`)
  - Warung toggle ketersediaan produk cepat
- Dibangun pakai JS biasa (bukan Livewire call): aksi disimpan ke IndexedDB saat offline, ditandai `pending_sync`, otomatis dikirim ke endpoint API biasa (bukan lewat Livewire) begitu `navigator.onLine` kembali `true`.
- Endpoint API untuk sinkronisasi ini **konsisten dengan strategi resolusi konflik** yang sudah disepakati di `project.md` — kalau ada 2 update offline yang konflik, berlaku aturan yang sama (additive log untuk ketersediaan, dst).

**Yang TIDAK masuk offline dasar (butuh koneksi, dan itu OK):** checkout/pembuatan order baru, pendaftaran akun, pencarian outlet radius 1km, approval Sales. Semua ini secara wajar butuh validasi server real-time (cek stok, cek radius, dst), jadi tidak masuk akal dipaksa offline.

---

## Struktur Multi-Role dalam 1 Aplikasi Laravel
Berbeda dari rencana 3 app Flutter terpisah, PWA ini **1 aplikasi Laravel**, dibedakan lewat route prefix, masing-masing punya `manifest.json` dan scope Service Worker sendiri — supaya tetap bisa di-"install" sebagai ikon terpisah di layar HP:

```
/warung/*      -> manifest warung.json, install sebagai "App Warung"
/konsumen/*    -> manifest konsumen.json, install sebagai "App Konsumen"
/kurir/*       -> manifest kurir.json, install sebagai "App Kurir"
```
Komponen Livewire yang generik (mis. autentikasi, pencarian outlet) bisa dipakai bareng lintas role; komponen spesifik role (dashboard Warung, form order Konsumen, klaim order Kurir) dipisah per folder view.

## Struktur Folder View
```
resources/views/
  livewire/
    auth/                  <- shared: login, register, OTP
    warung/                <- dashboard produk, ketersediaan, order masuk
    konsumen/               <- cari outlet, keranjang, checkout, riwayat order
    kurir/                  <- daftar order tersedia, klaim, update status antar
  layouts/
    warung.blade.php        <- shell + manifest warung.json
    konsumen.blade.php
    kurir.blade.php
resources/js/
  offline/
    write-queue.js          <- Lapis 3: antrian aksi kritis (vanilla JS, terpisah dari Livewire)
    cache-snapshot.js        <- Lapis 2: simpan/baca snapshot data terakhir
public/
  sw.js                      <- Service Worker: Lapis 1 (app shell caching)
  manifest-warung.json
  manifest-konsumen.json
  manifest-kurir.json
```

---

## Alur Pengembangan Bertahap (mengikuti scope MVP yang sudah disepakati)

1. **Setup dasar PWA** — `sw.js` (app shell caching), 3 manifest per role, layout dasar per role
2. **Auth & Outlet** — login/register/OTP (shared), profil Warung + GPS
3. **Warung: Produk & Ketersediaan** — input manual produk, toggle ketersediaan (dengan Lapis 3 offline queue)
4. **Konsumen: Cari & Checkout** — pencarian radius 1km, keranjang, checkout COD (murni online, sesuai catatan di atas)
5. **Kurir: Klaim & Antar** — daftar order tersedia, klaim (atomik, sesuai desain rebutan-order), update status antar (dengan Lapis 3 offline queue)
6. **Uji integrasi end-to-end** — 1 alur penuh: Warung pasang produk → Konsumen order → Kurir klaim & antar → COD selesai
7. **Validasi offline** — matikan koneksi di tengah alur, pastikan Lapis 1–3 bekerja sesuai desain di atas

---

## Catatan
- Modul backend (`Modules/Core`, dst) yang sedang di-scaffold tetap dilanjutkan sesuai rencana — PWA ini konsumen dari API/Livewire yang sama.
- Kalau nanti Flutter tetap dikembangkan setelah validasi PWA berhasil, backend tidak perlu diubah — tinggal tambah client baru (Flutter) yang konsumsi kontrak/Event yang sama, konsisten dengan prinsip modular yang sudah kita sepakati sejak awal.
