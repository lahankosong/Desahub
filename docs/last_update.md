# Last Update — Ekosistem Warung

## Sesi 27 — Arsitektur Tiga Lapisan Produk + Chat Polling + Order Refactor (13 Juli 2026)

### Konteks
User meminta integrasi file referensi dari `docs/` (chat polling + order refactoring) lalu dilanjutkan implementasi arsitektur tiga lapisan produk sesuai spesifikasi: produk_master (katalog global), warung_produk (outlet lokal), kategori berjenjang, rounding rules, HET, dan harga history.

### A. Chat Real-Time Auto-Polling
- **View `chat.blade.php`:** ditambah JavaScript polling `fetch()` tiap 8 detik ke endpoint `/warung/chat/{id}/polling?after={last_id}` — pesan baru muncul otomatis tanpa reload halaman, auto-scroll ke bawah, escape HTML untuk keamanan XSS, mobile responsive (auto-scroll ke panel chat)
- **Controller `ChatWebController`:** method `polling()` return JSON hanya pesan dengan `id > after`, auto-tandai `dibaca_pada` untuk pesan dari Konsumen
- **Route:** `GET /warung/chat/{id}/polling` → `ChatWebController@polling`

### B. Refactoring Order — Eloquent Relations Elegan
- **Model `CodSettlement` dibuat** (`Modules/Payment/app/Models/CodSettlement.php`) — table `cod_settlements`
- **Model `Order`:**
  - Relasi `settlement()`: `hasOne(CodSettlement::class, 'order_id')`
  - Relasi `buyer()`: polymorphic manual — `Konsumen` → `belongsTo(User)`, `PelangganWarung` → `belongsTo(PelangganWarung)`, lainnya → null
  - **Bug ditemukan:** `buyer()` return null untuk `buyer_type='Umum'` → tidak bisa di-eager-load karena Eloquent butuh instance `Relation`, bukan null
- **Controller `OrderWebController`:**
  - Sebelum: `Order::with(['items', 'outlet'])` + manual `DB::table('cod_settlements')->whereIn(...)->keyBy('order_id')`
  - Sesudah: `Order::with(['items', 'outlet', 'settlement'])` — eager loading Eloquent murni, hapus query manual
  - `'buyer'` tidak di-include di `with()` (karena return null untuk Umum) — blade tetap akses via accessor `getBuyerAttribute()`
- **View `order-masuk.blade.php`:**
  - `$settlements->get($order->id)` → `$order->settlement` (relasi Eloquent langsung)
  - Tampil nama buyer via `$order->buyer?->nama`
  - Badge 🏪 Kasir untuk transaksi POS (`jenis_transaksi === 'pos'`)

### C. Arsitektur Tiga Lapisan Produk

**Spesifikasi dari user:**

| Lapisan | Tabel | Isi |
|---------|-------|-----|
| 1 — Server (global) | `produk_master` | barcode UNIQUE, nama, varian, netto, foto, HET, kategori_id FK, created_by_outlet_id FK |
| 2 — Outlet lokal | `warung_produk` | `produk_master_id` FK nullable, `harga` (jual), `harga_beli`, `stok`, `diskon`, `bundle` |
| 3 — Browser cache | localStorage | Snapshot master untuk produk yang dijual outlet, sync saat online via write-queue |

**Kategori berjenjang (self-referencing):**
```
kategoris: id, nama, parent_id (nullable)
  Level 0: Kategori (parent_id = null)
    Level 1: Sub Kategori (parent_id → kategori)
      Level 2: Item (parent_id → sub)
```

**Rounding rules:**
- Desimal 00-25 → bulatkan ke 0 (bawah)
- Desimal 26-75 → bulatkan ke 50
- Desimal 76-99 → bulatkan ke 100 (atas)
- Contoh: 4561→4550, 4576→4600, 4525→4500

**HET (Harga Eceran Tertinggi):** ditampilkan sebagai referensi di UI, outlet bisa set harga jual di bawah HET

**Harga history:** `harga_produk_history` — log setiap kali outlet ubah harga jual (untuk hitung rata-rata)

### File yang dibuat (9 baru)

| File | Deskripsi |
|------|-----------|
| `database/migrations/...000001_create_kategoris_table.php` | Tabel `kategoris` self-referencing |
| `database/migrations/...000002_create_produk_master_table.php` | Tabel `produk_master` (katalog global) |
| `database/migrations/...000003_add_master_ref_to_warung_produk.php` | Tambah `produk_master_id` FK nullable |
| `database/migrations/...000004_create_harga_produk_history_table.php` | Log perubahan harga_jual |
| `Modules/Warung/app/Models/Kategori.php` | Model: parent(), children(), produkMaster() |
| `Modules/Warung/app/Models/ProdukMaster.php` | Model: findByBarcode(), daftarkan(), relasi |
| `Modules/Warung/app/Models/HargaHistory.php` | Model: warungProduk() |
| `Modules/Core/app/Traits/HasRounding.php` | Trait: bulatkanHarga() |
| `Modules/Payment/app/Models/CodSettlement.php` | Model: table cod_settlements |

### File diubah (7)

| File | Perubahan |
|------|-----------|
| `resources/views/warung/chat.blade.php` | JS polling 8 detik + auto-scroll + escape HTML |
| `app/Http/Controllers/Warung/ChatWebController.php` | Method polling() |
| `routes/web.php` | Route polling chat |
| `Modules/Order/app/Models/Order.php` | Relasi settlement() + buyer() |
| `app/Http/Controllers/Warung/OrderWebController.php` | Eager loading Eloquent, hapus DB::table manual |
| `resources/views/warung/order-masuk.blade.php` | $order->settlement, buyer name, badge Kasir |
| `Modules/Warung/app/Models/Produk.php` | produk_master_id fillable, relasi baru |
| `app/Http/Controllers/Warung/ProdukWebController.php` | Import HasRounding, ProdukMaster, HargaHistory |

### Error ditemukan & difix
| # | Error | Penyebab | Solusi | Status |
|---|-------|----------|--------|--------|
| 45 | `Call to a member function addEagerConstraints() on null` | `Order::with(['buyer'])` — buyer() return null untuk Umum | Hapus 'buyer' dari with(), pakai accessor | ✅ |

### Catatan
- **Backward compatible** — `produk_master_id` nullable, outlet tetap bisa buat produk manual tanpa master
- Jalankan `php artisan migrate` untuk 4 migration baru
- Browser cache (Lapis 3) sudah ada via `localStorage` write-queue di JS `kelola-produk.blade.php`
- HET belum ditampilkan di UI (perlu update `kelola-produk.blade.php` form & card — task terpisah)

---

## Sesi 25 — Review & Fix PosController.php (Web Session, 12 Juli 2026)

### Konteks
Andi upload `PosController.php` versi dari Web Session sebelumnya (Sesi 17) yang belum sempat diterapkan ke project. Diminta dicek kesesuaiannya dengan kondisi project aktual setelah Sesi 24.

### Temuan
File `PosController.php` versi Web Session mengandung **bug yang persis sama dengan Error #40 yang sudah difix di Sesi 24** (`OrderDibuat::dispatch($order)` dengan object, bukan `emitOrderDibuat()`). Namun setelah dicek lebih teliti, versi yang diupload ternyata sudah menggunakan `$order->emitOrderDibuat()` — artinya file ini sudah merupakan versi yang lebih baru dari versi yang sempat menyebabkan Error #40.

### Perubahan pada PosController.php sebelum diterapkan

1. **`PembayaranDiterima::dispatch(...)` di-comment** — karena signature event ini tidak diketahui persis dari sisi web session. Ditandai dengan komentar jelas supaya Andi verifikasi dulu sebelum uncomment. Jika event sudah ada dan signature-nya cocok, uncomment dan sesuaikan parameter.

2. **Catatan deployment ditambahkan** di atas method `transaksi()`:
   - Route `/warung/pos/transaksi` harus diarahkan ke `PosController@transaksi`, bukan ke `ProdukWebController@posTransaksi` yang lama
   - Jangan apply migration `2026_07_11_000001` dan `2026_07_11_000002` dari Web Session — sudah ditangani di Sesi 23/24 (migration 000005-000008)

3. **`emitOrderDibuat()`** sudah benar — tidak diubah.

### Yang masih perlu dilakukan setelah apply PosController.php
- [ ] Verifikasi signature `PembayaranDiterima` event lalu uncomment dispatch-nya kalau perlu
- [ ] Pastikan route `/warung/pos/transaksi` menunjuk ke `PosController@transaksi`
- [ ] Test alur cash dan tempo end-to-end di browser

### File yang diupdate
- `app/Http/Controllers/Warung/PosController.php` — komentar deployment + PembayaranDiterima di-comment

---

## Sesi 17 — Fix produk_nama + Modul Chat Warung (11 Juli 2026)

### Konteks
Lanjutan Sesi 16 (bug `produk_nama` tidak ditemukan). User minta: (1) selesaikan fix error `produk_nama`, (2) hapus menu Profil di bottom nav warung, ganti dengan menu Chat untuk komunikasi dengan konsumen terkait stok produk dll.

### Fix 1: Error `produk_nama` column not found
- **Root cause:** query Top Product di `resources/views/warung/dashboard.blade.php` pakai kolom `produk_nama` yang tidak pernah ada — kolom asli (dari Sesi 16) bernama `nama_produk` (snapshot, diisi via `Sellable::getNama()` saat transaksi).
- **Perbaikan:** 3 occurence di dashboard diubah `produk_nama` → `nama_produk` (select, groupBy, dan akses `$topProduk->nama_produk`).
- `php artisan view:clear` dijalankan supaya compiled view lama tidak disajikan.

### Fix 2: Bottom Nav Warung — Profil → Chat
- Di `resources/views/layouts/warung.blade.php`, item bottom nav **Profil dihapus**, diganti **Chat** (`route('warung.chat')`, ikon `bi-chat-dots`).
- Profil TETAP ada di top-nav dropdown (Pengaturan Profil + Keluar) — tidak dihapus, cuma dipindah dari bottom nav per desain Sesi 15.
- Bottom nav sekarang: **Beranda | Order | POS (tengah) | Produk | Chat** — persis desain Sesi 15.

### Fitur Baru: Modul Chat Konsumen-Outlet (Sesi 15, baru diimplementasikan)
Sesi 15 cuma mendesain Chat, belum diimplementasikan. Sekarang dibangun:
- **Migration** `database/migrations/2026_07_11_000004_create_chat_tables.php`: tabel `percakapan` (outlet_id, konsumen_id, unique pair) + `pesan` (percakapan_id, pengirim_type/id, isi_pesan, dibaca_pada, dikirim_pada).
- **Model** `app/Models/Chat/Percakapan.php` & `app/Models/Chat/Pesan.php` (relasi + accessor `belumDibacaOutlet`, method `tandaiDibaca()`).
- **Controller** `app/Http/Controllers/Warung/ChatWebController.php`: inbox (`index`), buka percakapan + tandai baca (`show`), kirim pesan dari Outlet (`kirim`).
- **Route** `warung.chat`, `warung.chat.show`, `warung.chat.kirim` di `routes/web.php`.
- **View** `resources/views/warung/chat.blade.php`: layout 2-panel (inbox kiri, panel pesan kanan) dengan badge unread per percakapan.
- `php artisan migrate` sukses — tabel `percakapan` & `pesan` terbuat.

**Catatan:** Chat dari sisi Konsumen BELUM dibuat (belum ada UI kirim pesan dari app Konsumen ke outlet). Ini cuma sisi Outlet (nerima + membalas). Perlu sesi terpisah untuk sisi Konsumen + event `PesanDikirim` (notifikasi) kalau mau lengkap.

### Status
- [x] Error `produk_nama` resolved
- [x] Bottom nav: Profil → Chat
- [x] Modul Chat sisi Outlet jalan (inbox + balas)
- [ ] Sisi Konsumen + event PesanDikirim (belum dikerjakan)

## Sesi 24 — Perbaikan POS + Fix Error Pembayaran (12 Juli 2026)

### Konteks
Setelah Sesi 23, ditemukan error 500 saat proses pembayaran POS dan UI yang kurang optimal untuk workflow kasir.

### Perbaikan
- **Fix error 500 pembayaran:** `OrderDibuat` event dipanggil dengan object `$order` bukan parameter terpisah. Diperbaiki dengan mengganti ke `$order->emitOrderDibuat()` di `PosController`.
- **Hapus grid produk** di POS — fokus ke barcode scan saja, tidak perlu tampilan produk.
- **Layout compact:** barcode input di header, keranjang di atas, payment di bawah.
- **Auto-focus barcode input** setelah menambah item / klik +/- qty — untuk workflow scan cepat berurutan.
- **Hapus duplicate migrations** yang menyebabkan error `Column already exists`.

### Status
- [x] Error 500 pembayaran diperbaiki
- [x] UI POS dirombak jadi layout compact
- [x] Auto-focus barcode input berfungsi
- [x] Semua migration berjalan sukses

### Perbaikan tambahan di order-masuk.blade.php
- **N+1 query fixed:** Eager load `cod_settlements` di controller, kirim sebagai `$settlements` ke view
- **nama_produk snapshot:** Ganti `$item->sellable->getNama()` ke `$item->nama_produk` (field snapshot yang sudah ada)
- **buyer_type display:** Tambah kondisi untuk `Umum` (POS Walk-in) dan `PelangganWarung` (Tempo)

## Sesi 23 — Overhaul POS Kasir + PelangganWarung + Piutang (11 Juli 2026)

### Konteks
POS `/warung/pos` v1 (Sesi 15) hanya basic: grid tap-to-add + bayar tunai. User minta dirombak jadi layaknya sistem kasir sungguhan: scan barcode, qty stepper, cash management, tempo (piutang) dengan pelanggan warung sendiri.

### Keputusan Desain

**PelangganWarung — buku pelanggan MILIK warung sendiri, bukan akun platform:**
- Tabel `pelanggan_warung`: nama, no_hp, catatan — simpel, tidak perlu registrasi app.
- Alasan: banyak pelanggan tempo adalah tetangga tanpa smartphone.
- `buyer_type` baru: `PelangganWarung` (sebelumnya hanya `Konsumen`, `Outlet`, `Umum`).

**Piutang — tabel terpisah dari `cod_settlements`:**
- `cod_settlements` = urusan uang kurir (COD).
- `piutang` = urusan piutang warung sendiri: jumlah, terbayar, sisa (stored generated), jatuh_tempo, status (aktif/lunas/gagal_bayar).
- Terhubung ke notifikasi "Piutang Jatuh Tempo" yang sudah direncanakan di roadmap Notification Rules.

**Scan barcode WAJIB client-side (browser), bukan API call:**
- Konsisten dengan keputusan POS harus bisa offline (Lapis 3).
- Client-side data (`produkData` dari render server) dicocokkan dulu.
- Fallback API server hanya jika barcode tidak ada di data lokal.
- Hardware scanner: emulasi keyboard (Enter key) → langsung cari.
- Kamera scanner: BarcodeDetector API (Chrome 88+), real-time detection tanpa sentuh server.

**Tambah pelanggan baru saat tempo: online-only (jarang terjadi):**
- Pilih pelanggan existing: offline (dari cache localStorage).

### Perubahan Schema
```sql
-- pelanggan_warung: buku pelanggan sederhana
CREATE TABLE pelanggan_warung (
    id, outlet_id FK, nama, no_hp, catatan, timestamps
);

-- piutang: terpisah dari cod_settlements
CREATE TABLE piutang (
    id, outlet_id FK, pelanggan_warung_id FK, order_id FK nullable,
    jumlah, terbayar, sisa GENERATED ALWAYS AS (jumlah - terbayar),
    jatuh_tempo DATE, status ENUM('aktif','lunas','gagal_bayar'),
    catatan, timestamps
);

-- orders: buyer_id nullable + enum metode_pembayaran tambah tunai_pos, tempo
ALTER TABLE orders MODIFY buyer_id BIGINT NULL;
ALTER TABLE orders MODIFY metode_pembayaran ENUM('cod','transfer','dp','qris','tunai_pos','tempo');
```

### File baru (7) + diubah (4)
| File | Deskripsi |
|------|-----------|
| `Modules/Warung/database/migrations/...000005_create_pelanggan_warung_table.php` | Tabel pelanggan_warung |
| `Modules/Warung/database/migrations/...000006_create_piutang_table.php` | Tabel piutang |
| `Modules/Warung/app/Models/PelangganWarung.php` | Model + totalUtangAktif() |
| `Modules/Warung/app/Models/Piutang.php` | Model + scopes |
| `app/Http/Controllers/Warung/PosController.php` | API pelanggan & piutang |
| `Modules/Order/database/migrations/...000007_add_pos_fields_to_orders.php` | buyer_id nullable + enum |
| `Modules/Order/database/migrations/...000008_make_nama_produk_nullable.php` | Bug fix #39 |
| `resources/views/warung/pos.blade.php` | Overhaul total (~1000 baris) |
| `app/Http/Controllers/Warung/ProdukWebController.php` | posTransaksi() rewrite |
| `app/Http/Controllers/Konsumen/CheckoutController.php` | Tambah nama_produk |
| `routes/web.php` | 4 route + barcode di produkList |

### Status
- [x] Scan barcode client-side (hardware + kamera + manual)
- [x] Keranjang qty stepper + subtotal + total
- [x] Cash: input uang diterima + kembalian + quick suggestions
- [x] Tempo: pilih PelangganWarung + jatuh_tempo + catatan → piutang
- [x] Offline banner + cache pelanggan
- [x] `php artisan migrate` sukses
- [ ] Notifikasi "Piutang Jatuh Tempo" (belum, masih di roadmap)
- [ ] Riwayat transaksi POS harian (belum)

## Sesi 1 — Diskusi Arsitektur Awal (9 Juli 2026)

### Konteks
Roadmap awal (Roadmap_Ekosistem_Warung.md) di-review. Diskusi difokuskan pada kelayakan stack sebelum masuk coding, karena project ini lebih kompleks dari project Laravel biasa (multi-role: Warung, Konsumen, Kurir, Sales, Distributor).

### Keputusan Arsitektur

**1. Stack final:**
- Backend: Laravel REST API, tetap di Rumahweb shared hosting
- Frontend: Flutter (multi-app, offline-first)
- Awalnya sempat dipertimbangkan pindah ke VPS/stack lain karena kekhawatiran realtime butuh websocket persisten. Setelah didiskusikan, ternyata TIDAK perlu — karena pola offline-first (client cache + sync saat online) menghilangkan kebutuhan koneksi persisten. Server cukup jawab REST API biasa.

**2. Strategi sinkronisasi data (poin kritis):**
- Awalnya diusulkan "timestamp terbaru menang" untuk SEMUA field.
- Ditemukan risiko: untuk field kuantitas (stok), timestamp-based last-write-wins bisa MENIMPA perubahan valid yang terjadi hampir bersamaan dari device offline berbeda → data stok jadi salah tanpa terdeteksi.
- Contoh kasus: warung jual 5 barcode offline jam 10:00 (stok 20→15 tercatat lokal), ada proses lain update stok jadi 18 jam 10:05 tapi baru sync jam 10:10. Kalau "timestamp terbaru menang", update jam 10:05 (=18) menimpa hasil penjualan jam 10:00 (=15) — padahal keduanya seharusnya digabung, bukan saling timpa.
- **Solusi disepakati:** field kuantitas (stok, saldo, tempo pembayaran) disimpan sebagai log transaksi append-only (tabel `stock_movements` dsb), nilai akhir = SUM dari log. Field non-kuantitas (harga, deskripsi, profil) tetap pakai timestamp last-write-wins.
- Pola ini konsisten dengan prinsip append-only yang sudah dipakai di audit ledger Brimola (Margosystem).

### Belum Diputuskan (lanjutkan sesi berikutnya)
- Scope final Fase 1 MVP (saat ini dianggap masih terlalu luas untuk solo dev 0-4 bulan)
- Metode perhitungan radius 1km di query database
- Skema autentikasi lintas 3 app Flutter (satu sistem auth Laravel atau terpisah)
- Urutan metode pembayaran mana yang jalan duluan di MVP

## Sesi 2 — Arsitektur Modular & Kontrak Event (9 Juli 2026)

### Keputusan: Arsitektur Modular Plug-and-Play
- Komunikasi antar-modul lewat Event & Listener, bukan pemanggilan langsung antar-Controller/Model.
- Modul baru = Listener baru untuk Event yang sudah ada. Modul lama tidak pernah dimodifikasi.
- Struktur folder per-modul (`Modules/Warung`, `Modules/Order`, dst), kandidat tooling `nwidart/laravel-modules`.
- Dikonfirmasi aman untuk workflow deploy Andi (build lokal termasuk composer install, lalu upload folder jadi termasuk vendor ke cPanel — tidak butuh SSH).

### Keputusan: Kontrak Event
1. Additive-only — field lama tidak boleh dihapus/diubah.
2. Payload = DTO eksplisit, bukan Eloquent Model utuh (supaya perubahan skema tabel tidak diam-diam merusak Listener).
3. Bawa ID, bukan objek relasi bersarang.
4. Semua Event dicatat di `events.md` (registry terpisah dari project.md karena akan terus tumbuh).
- Event pertama yang didefinisikan: `OrderDibuat`, `StokBerubah`, `PembayaranDiterima` — skema lengkap ada di events.md.

### Keputusan: Scope Fase 1 MVP (final)
- Fokus 1 alur transaksi lengkap: Warung pasang produk (input manual, belum barcode) -> Konsumen cari warung radius 1km & checkout COD -> Kurir terima & antar order.
- Ditunda ke Fase 1.5: scan barcode, metode pembayaran transfer/DP, notifikasi push realtime.
- Alasan: modul dan event dijaga tetap minimal supaya kalau ada bug, sumbernya jelas dari 1 alur ini, bukan tercampur dengan banyak fitur baru sekaligus. Penambahan transfer/DP nanti otomatis jadi bukti konsep plug-and-play jalan (cuma tambah Listener baru untuk `PembayaranDiterima`, modul Order/Payment tidak disentuh).

### Belum Diputuskan (lanjutkan sesi berikutnya)
- Detail query Haversine untuk radius 1km di MySQL
- Skema autentikasi lintas 3 app Flutter (satu sistem auth Laravel atau terpisah)
- Daftar modul teknis final untuk MVP + mapping Event per modul (folder `Modules/...`)

## Sesi 3 — Skema Autentikasi (9 Juli 2026)

### Keputusan: Laravel Sanctum + 1 users, profil per-peran
- Dipilih Sanctum (bukan Passport) karena lebih ringan, cocok shared hosting, sesuai kebutuhan token API untuk 3 app Flutter.
- 1 akun (`users`) per orang, peran disimpan sebagai profil terpisah (`warung_profiles`, `konsumen_profiles`, `kurir_profiles`, nanti `sales_profiles`) — bukan 1 kolom `role`.
- Alasan: dikonfirmasi bahwa platform akan meluas ke Sales/Distributor (sudah ada di roadmap Fase 2), dan realistis satu orang bisa pegang lebih dari satu peran (mis. kurir yang juga konsumen). Kolom `role` tunggal akan bikin data campur aduk saat orang punya peran ganda dengan kebutuhan verifikasi berbeda-beda.
- Token Sanctum diberi ability/scope sesuai peran aktif per app — token dari app Kurir tidak otomatis bisa akses endpoint Warung meski user sama.
- Konsisten dengan arsitektur modular: peran baru = tabel profil baru + modul baru, tabel `users` dan modul lain tidak disentuh.

### Belum Diputuskan (lanjutkan sesi berikutnya)
- Detail query Haversine untuk radius 1km di MySQL
- Daftar modul teknis final untuk MVP + mapping Event per modul (folder `Modules/...`)

## Sesi 4 — Generalisasi Multi-Vertikal (9 Juli 2026)

### Konteks
Andi menyampaikan bahwa platform ini direncanakan berkembang lebih luas dari sekadar warung sembako — target jangka panjang mencakup Apotik, Warung Makan, Toko Bangunan, Toko Pupuk, dll, dengan fondasi modular yang sama. Diputuskan untuk mendesain kontrak generik SEKARANG, sebelum modul Warung mulai dibangun, supaya tidak perlu bongkar ulang skema nanti.

### Keputusan: Rename ke istilah generik
- `Warung` (sebagai entitas bisnis) -> **`Outlet`** di level generik. Warung tetap jadi vertikal pertama, tapi implementasinya lewat modul `Warung` yang menempel ke `Outlet`.
- Tabel `outlet_profiles` (generik) + ekstensi per-vertikal (`warung_detail`, `apotik_detail`, dst) — pola sama seperti `users` + `*_profiles` di skema auth.
- Event `StokBerubah` -> **`KetersediaanBerubah`** (karena "stok" tidak berlaku universal — Warung Makan pakai status tersedia/habis, bukan angka stok).
- Field `warung_id`/`produk_id` di event -> `outlet_id`/`sellable_type`+`sellable_id` (polymorphic, generik).

### Keputusan: Kontrak `Sellable`
- Setiap model produk per-vertikal WAJIB implementasi: `getNama()`, `getHarga()`, `getSatuan()`, `cekTersedia(qty)`, `prosesPengurangan(qty, referensiId)`.
- Order hanya pegang `sellable_type` + `sellable_id` (polymorphic Laravel), tidak pernah tahu isi produk. Ini yang membuat vertikal baru bisa ditambah tanpa modifikasi modul Order.

### Keputusan: Kontrak `ComplianceReportable`
- Opsional, hanya untuk vertikal teregulasi (Apotik: resep obat, Toko Pupuk: subsidi by NIK petani — dikonfirmasi ini prioritas jangka panjang Andi).
- Modul `Compliance` baru cukup mendengarkan Event `OrderDibuat`/`PembayaranDiterima` yang sudah ada, cek `instanceof ComplianceReportable`, catat ke `compliance_reports` (append-only). Modul Order/Payment tidak disentuh.
- Detail kebutuhan pelaporan pemerintah belum diriset — baru prinsip arsitekturnya yang disiapkan.

### File yang diupdate
- `project.md`: judul & visi digeneralisasi, struktur folder modul ditambah vertikal masa depan, kontrak Sellable & ComplianceReportable ditambahkan, skema auth & sinkronisasi disesuaikan istilah generik, roadmap ditambah Fase 5+ (Ekspansi Vertikal)
- `events.md`: `StokBerubah` -> `KetersediaanBerubah` dengan payload generik, field `warung_id`/`produk_id` digeneralisasi, ditambah rencana event `LaporanCompliancePerluDibuat`

### Belum Diputuskan (lanjutkan sesi berikutnya)
- Detail query Haversine untuk radius 1km di MySQL
- Daftar modul teknis final untuk MVP Warung + mapping Event per modul (folder `Modules/...`)
- Riset kebutuhan pelaporan pemerintah spesifik untuk Apotik dan Toko Pupuk
- Skema kolom detail untuk `outlet_profiles` generik vs ekstensi per-vertikal

## Sesi 5 — Modul Teknis Final MVP, Skema Outlet, Query Haversine (9 Juli 2026)

### Keputusan: Skema `outlets` generik
```
outlets: id, owner_user_id (FK users), nama, tipe_vertikal, lat, lng, alamat, status_verifikasi, dibuat_pada
warung_detail: outlet_id (FK), jam_buka, jam_tutup, kategori_warung
```

### Keputusan: Daftar modul teknis MVP
`Modules/Core` (kontrak Sellable & ComplianceReportable, trait log ketersediaan, helper radius), `Modules/Auth`, `Modules/Outlet`, `Modules/Warung`, `Modules/Order`, `Modules/Payment`, `Modules/Kurir`.

### Keputusan: Mapping Listener MVP (dicatat di events.md)
- `OrderDibuat` -> didengar Kurir (munculkan order) & Warung (trigger prosesPengurangan -> emit KetersediaanBerubah)
- `KetersediaanBerubah` -> belum ada listener MVP, baru dicatat sebagai log (listener pertama nanti Fase 3: prediksi stok habis)
- `PembayaranDiterima` -> didengar Order (ubah status jadi selesai)

### Keputusan: Query radius 1km
Haversine dengan optimasi bounding-box (filter WHERE lat/lng pakai index biasa dulu, baru HAVING menyaring jarak presisi) — perlu karena shared hosting tidak punya index spasial andal. Detail rumus di project.md.

### Status
Open question tersisa hanya riset kebutuhan pelaporan pemerintah Apotik/Toko Pupuk — ini Fase 5+, tidak memblokir mulai coding MVP Warung. Fondasi arsitektur (project.md + events.md) sudah cukup lengkap untuk mulai implementasi modul pertama.

## Sesi 6 — Aturan Tingkatan Warung: Biasa vs Grosir (9 Juli 2026)

### Konteks
Andi menambahkan aturan bisnis: Sales dari Supplier/Distributor hanya berhubungan dengan Warung Grosir. Warung Biasa naik jadi Warung Grosir dengan syarat minimal 1 approval dari Sales terdaftar. Dikonfirmasi tegas: Warung Grosir hanya boleh di-order oleh Warung Biasa — tertutup total untuk Konsumen dan pihak lain, untuk melindungi ekosistem.

### Keputusan: Generalisasi field pembeli di Order (dilakukan sekarang, sebelum ada kode)
- `konsumen_id` -> **`buyer_type` + `buyer_id`** (polymorphic: `Konsumen` atau `Outlet`), supaya Order bisa menangani baik Konsumen->Warung Biasa (MVP) maupun Warung Biasa->Warung Grosir (B2B Fase 2) tanpa desain ulang nanti.
- Alasan dilakukan sekarang: karena belum ada kode, perubahan skema masih aman. Kalau ditunda sampai modul Order sudah jadi, ini akan melanggar aturan additive-only yang sudah disepakati.

### Keputusan: Skema tingkatan Warung
```
warung_detail.tier (enum: biasa, grosir)   <- field cache, bukan sumber kebenaran
warung_grosir_approvals                     <- log append-only, sumber kebenaran
  id, outlet_id, sales_id, catatan, disetujui_pada
```
`tier` di-update otomatis lewat Event, bukan diedit manual — konsisten pola append-only yang sudah dipakai di ketersediaan/stok.

### Keputusan: Kontrak baru `BuyerEligibilityPolicy`
- Pola sama seperti `Sellable` — default semua outlet boleh dibeli siapa saja, Warung Grosir override untuk membatasi hanya `Outlet` bertipe `warung` dengan `tier = biasa`.
- Order memanggil kontrak ini saat checkout (validasi sebelum `OrderDibuat` dipancarkan). Order tidak berisi logika "apa itu grosir" — logika itu tetap di Modul Warung.
- Dicatat eksplisit: Order boleh bergantung ke Outlet karena keduanya modul inti/Core — prinsip "tidak modifikasi modul lama" melindungi dari Order perlu tahu detail vertikal, bukan melarang pemanggilan ke layer fondasinya.

### Keputusan: Event baru `WarungDisetujuiGrosir`
- Dipancarkan Modul Sales (Fase 2), didengar Modul Warung (update cache tier + catat log approval). Payload sudah dirancang di events.md meski implementasi Fase 2.

### File yang diupdate
- `project.md`: kontrak `BuyerEligibilityPolicy` ditambahkan, bagian baru "Tingkatan Warung: Biasa vs Grosir", skema Order module diupdate
- `events.md`: payload `OrderDibuat` digeneralisasi jadi buyer polymorphic + validasi BuyerEligibilityPolicy dicatat, event `WarungDisetujuiGrosir` ditambahkan

## Sesi 7 — Penyempurnaan Aturan Warung Grosir + Multi-Vertikal Outlet (9 Juli 2026)

### Konteks
Andi minta ide tambahan untuk memperkuat desain Tingkatan Warung. Diajukan 4 celah, 3 langsung didesain, 1 (multi-vertikal per outlet) dikonfirmasi dulu ke Andi karena dampak skema lebih besar — dikonfirmasi YA relevan (mis. warung yang juga jual pulsa).

### Keputusan 1: Pencabutan status Grosir
- Event baru `WarungDicabutGrosir`, pasangan dari `WarungDisetujuiGrosir`.
- Nuansa penting: tier BUKAN pola SUM seperti stok — nilainya = entri TERBARU di log `warung_grosir_approvals` (state terakhir menang). Dicatat eksplisit di events.md supaya tidak disamakan logikanya dengan pola stok.
- `warung_grosir_approvals` dapat kolom `jenis` (disetujui/dicabut), riwayat lama tidak dihapus saat dicabut.

### Keputusan 2: Harga bertingkat (tiered pricing)
- Kontrak `Sellable::getHarga()` -> `getHarga(qty)`, dilakukan sekarang (belum ada kode) supaya vertikal Grosir bisa implementasi price-break tanpa desain ulang nanti.
- Ditambah aturan #6 di events.md: payload harga di event selalu hasil resolusi dari `getHarga(qty)` saat transaksi, bukan referensi yang dihitung ulang oleh listener.

### Keputusan 3: Proteksi penyalahgunaan status Grosir
- Tambah `level_verifikasi` (dasar/terverifikasi) di tabel `outlets`, terpisah dari OTP HP saat registrasi — ini verifikasi lokasi usaha oleh admin.
- `BuyerEligibilityPolicy` Warung Grosir diperketat: pembeli harus Outlet warung tier biasa DAN level_verifikasi=terverifikasi.

### Keputusan 4: Multi-vertikal per outlet (dikonfirmasi relevan)
- `outlets.tipe_vertikal` (kolom tunggal) diganti tabel pivot `outlet_vertikal` (outlet_id, vertikal, status, aktif_sejak) dengan unique(outlet_id, vertikal) — satu outlet bisa punya lebih dari satu vertikal aktif sekaligus (mis. warung + jual pulsa).
- Detail table per-vertikal (warung_detail, dst) tetap terhubung via outlet_id seperti sebelumnya, tidak berubah.

### File yang diupdate
- `project.md`: kontrak Sellable (getHarga qty), skema outlets+outlet_vertikal (pivot), bagian Tingkatan Warung diperluas (pencabutan, proteksi verifikasi, harga bertingkat), Modul Outlet diupdate
- `events.md`: event `WarungDicabutGrosir` ditambahkan, aturan #6 (harga = hasil resolusi), nuansa state-terbaru untuk tier

### Status
Semua open question utama sudah terjawab. Fondasi arsitektur (project.md + events.md) sudah cukup matang untuk mulai implementasi modul pertama (`Modules/Core`).

## Sesi 8 — Integritas Transaksi + Syarat & Ketentuan (9 Juli 2026)

### Keputusan: 5 masalah integritas transaksi (semua wajib sebelum MVP jalan kecuali disebutkan lain)
1. **Race condition stok** — ditambah `ketersediaan_cache` table dengan UPDATE atomik bergerbang (`WHERE qty >= jumlah`), dibungkus 1 DB transaction bersama insert log `ketersediaan_movements`. Melengkapi (bukan mengganti) strategi resolusi konflik sync offline yang sudah ada.
2. **Rebutan order antar-kurir** — klaim order pakai UPDATE atomik `WHERE kurir_id IS NULL`, bukan check-then-act.
3. **State machine Order formal** — tabel transisi status resmi didefinisikan (dibuat/diambil_kurir/diantar/selesai/dibatalkan/gagal_kirim), transisi di luar itu ditolak di aplikasi.
4. **Alur pembatalan** — Event baru `OrderDibatalkan`, listener Warung mengembalikan ketersediaan lewat entri kompensasi (bukan edit log lama), listener Order set status dibatalkan.
5. **Rekonsiliasi COD** — tabel `cod_settlements` (append-only) sebagai tempat pencatatan uang tunai yang dipegang kurir, penyetoran dicatat manual admin untuk MVP (belum otomatis).

### Keputusan: Syarat & Ketentuan Pengguna
- Dibuat file baru `syarat_ketentuan.md`.
- Prinsip: Platform = penyedia sarana teknis, bukan pihak transaksi. Karena belum ada skema bisnis/keuntungan, semua aktivitas & transaksi tiap role jadi tanggung jawab masing-masing pengguna. Kewajiban Platform dibatasi pada perlindungan data lewat enkripsi.
- **Peringatan diberikan ke Andi:** ini bukan nasihat hukum, dan UU PDP (27/2022) kemungkinan mewajibkan lebih dari sekadar enkripsi (consent, hak akses/hapus data, kewajiban lapor kebocoran). Draf perlu direview yang paham hukum sebelum dipublikasikan ke pengguna nyata. Poin ini dicatat eksplisit di dalam syarat_ketentuan.md sendiri sebagai bagian "Belum Dibahas".

### File yang diupdate/dibuat
- `project.md`: bagian baru "Integritas Transaksi" (5 poin di atas), referensi ke syarat_ketentuan.md
- `events.md`: event `OrderDibatalkan` ditambahkan
- `syarat_ketentuan.md`: file baru, draf syarat & ketentuan untuk semua role

### Status
Fondasi arsitektur + integritas transaksi + kerangka legal dasar sudah tercatat. Siap untuk mulai implementasi modul pertama.

## Sesi 9 — Pivot ke Versi Uji Coba PWA (9 Juli 2026)

### Konteks
Andi kesulitan mengembangkan frontend Flutter. Diputuskan membangun versi uji coba PWA berbasis Laravel dulu (memanfaatkan skill yang sudah terbukti di EMINOR & Margonoandi Fanbase), Flutter tetap opsi jangka panjang.

### Keputusan: Stack & prinsip
- Stack: Blade + Livewire + Alpine.js (dipilih karena paling ringan, konsisten skill Laravel yang sudah dikuasai).
- Prinsip: PWA murni ganti lapisan presentasi. Semua desain backend (Modules/Core dkk, kontrak Sellable/BuyerEligibilityPolicy/ComplianceReportable, event, state machine Order) TIDAK berubah.

### Gesekan teknis yang diluruskan: Livewire vs offline
- Livewire secara arsitektur butuh koneksi (render ulang lewat request server) — tidak bisa dipaksa 100% offline tanpa ganti arsitektur total.
- Andi tetap minta offline dasar sejak awal (bukan ditunda). Solusi kompromi: Arsitektur Offline 3 Lapis:
  1. App Shell Caching (Service Worker) — app tetap terbuka walau offline, bukan error browser
  2. Data terakhir terlihat (read-only cache via localStorage/IndexedDB + Alpine)
  3. Antrian aksi kritis (write queue, terpisah dari Livewire, pakai JS biasa) — HANYA untuk Kurir update status antar & Warung toggle ketersediaan, disinkron otomatis saat online kembali
- Checkout, registrasi, pencarian radius, approval Sales TETAP wajib online (masuk akal karena butuh validasi server real-time).

### Keputusan: Struktur multi-role dalam 1 app Laravel
- Beda dari rencana 3 app Flutter terpisah — 1 aplikasi Laravel, dibedakan route prefix (/warung, /konsumen, /kurir), masing-masing punya manifest.json & scope Service Worker sendiri supaya tetap bisa di-install terpisah sebagai ikon HP.

### File yang dibuat
- `versi_pwa.md` (baru): stack, arsitektur offline 3 lapis, struktur folder, alur pengembangan bertahap 7 tahap mengikuti scope MVP yang sudah disepakati sebelumnya

### Status
Scaffolding `Modules/Core` (backend) tetap dilanjutkan seperti rencana — PWA ini konsumen dari kontrak/Event yang sama. Kalau Flutter dilanjutkan nanti setelah PWA tervalidasi, backend tidak perlu diubah.

## Sesi 10 — Sinkronisasi Progres Implementasi & Checkpoint Transisi Flutter->PWA (10 Juli 2026)

### Konteks
Andi upload `activity.md` dan `cara_akses.md` dari sesi Claude Code (implementasi nyata di repo `github.com/lahankosong/Desahub`, lokal via XAMPP). Progres ditemukan sudah jauh lebih maju dari terakhir dibahas di sini.

### Progres implementasi aktual (per 10 Juli 2026, dari activity.md)
- `Modules/Core` SELESAI: 3 kontrak (Sellable, ComplianceReportable, BuyerEligibilityPolicy), trait log ketersediaan, helper radius, 4 Event DTO (KetersediaanBerubah, OrderDibuat, PembayaranDiterima, OrderDibatalkan) — konsisten dengan desain di project.md/events.md.
- `Modules/Auth` SELESAI: User model + Sanctum, OutletProfile/KonsumenProfile/KurirProfile, AuthController (register/login/verify-otp/me/logout), routes prefix `/api/v1`.
- Database `desahub` dibuat, 10 tabel (users, personal_access_tokens, outlet_profiles, konsumen_profiles, kurir_profiles, ketersediaan_cache, ketersediaan_movements, cache, jobs, sessions), migrate:fresh sukses.
- Ditemukan ketidaksesuaian: `cara_akses.md` masih berisi panduan lengkap 3 app Flutter (app_konsumen/app_kurir/app_outlet), padahal sesi terakhir di sini sudah sepakat pivot ke PWA.

### Klarifikasi dari Andi
**Titik activity.md ini adalah CHECKPOINT saat Andi menyerah mengerjakan Flutter** — dokumen belum sempat diupdate ke arah PWA. Dikonfirmasi: **PWA (Blade+Livewire+Alpine, sesuai `versi_pwa.md`) adalah arah yang benar mulai sekarang**, bukan Flutter.

### Perhatian keamanan yang disampaikan ke Andi
Repo `Desahub` publik di GitHub. Mengingat riwayat insiden serupa di project Margonoandi Fanbase (deployment script dengan key hardcoded ter-expose), Andi diingatkan untuk memastikan `.env` ada di `.gitignore` dan tidak ada kredensial/APP_KEY yang ter-commit ke repo publik ini.

### Status & Instruksi Lanjutan untuk Claude Code
Karena sesi Claude Code (implementasi) dan sesi ini (arsitektur) tidak berbagi konteks otomatis, Andi perlu membawa ringkasan berikut ke Claude Code:
1. **Hentikan setup Flutter** — `desahub_flutter/` tidak dilanjutkan untuk saat ini (bukan dihapus, cukup tidak dikerjakan).
2. **Lanjutkan backend seperti rencana:** `Modules/Outlet` berikutnya (tabel `outlets` + `outlet_vertikal` pivot + `level_verifikasi`), lalu `Modules/Warung`, `Modules/Order`, `Modules/Payment`, `Modules/Kurir` — urutan tidak berubah, hanya konsumennya nanti PWA bukan Flutter.
3. **Tambahkan frontend PWA** mengikuti `versi_pwa.md`: Blade + Livewire + Alpine, struktur `/warung`, `/konsumen`, `/kurir` dengan manifest & Service Worker masing-masing, offline 3 lapis (app shell caching, read-only cache, write queue khusus aksi kritis).
4. **Update `cara_akses.md`** — ganti/tambahkan bagian panduan Flutter dengan panduan menjalankan PWA (npm run dev/build, cara akses tiap role via route prefix, cara test offline).
5. **Cek keamanan:** pastikan `.env` tidak ter-commit ke repo publik `Desahub`.

## Sesi 11 — Desain UI Modular untuk PWA (10 Juli 2026)

### Keputusan: Token desain & landasan
- Dijangkarkan ke dunia nyata warung/pasar lokal (bukan dashboard SaaS generik), mempertimbangkan 3 profil pengguna berbeda: pemilik warung (kurang tech-savvy), kurir (dipakai sambil jalan/naik motor, kontras tinggi & target sentuh besar wajib), konsumen (ekspektasi seperti app belanja biasa).
- Warna: warna-dasar #FAF6ED, warna-teks #2B2622, warna-aksen-utama #E8A23C (kuning kunyit), warna-aksen-kedua #1F5C4F (hijau), warna-peringatan #C4482E.
- Tipografi: Space Grotesk (judul/angka), Plus Jakarta Sans (body).
- Elemen signature: sistem StatusChip — representasi visual langsung dari state machine Order & tier Warung yang sudah didesain di backend (bukan dekorasi).

### Keputusan: Halaman per role + komponen bersama
- Komponen bersama: StatusChip, OfflineBanner (Lapis 1), SyncIndicator (Lapis 3), KartuOutlet/KartuProduk, EmptyState, BottomNav.
- Konsumen: Beranda/cari, Detail Outlet+Keranjang, Checkout (wajib online), Riwayat & Detail Order (timeline = state machine).
- Warung: Dashboard (badge tier selalu terlihat), Order Masuk, Kelola Produk (toggle ketersediaan = aksi Lapis 3 offline).
- Kurir: Status Online/Offline (halaman pembuka, elemen terbesar), Order Tersedia (tombol ambil besar, sekali tap), Order Aktif (update status = aksi Lapis 3 offline).
- Semua wireframe dan komponen dipetakan langsung ke struktur folder Livewire dari versi_pwa.md.

### File yang dibuat
- `desain_ui.md` (baru): token desain, breakdown halaman per role dengan wireframe ASCII, komponen modular, pemetaan ke folder Livewire

### Belum dirancang (dicatat di file)
- Halaman Profil/Akun, verifikasi outlet, riwayat rekonsiliasi COD Kurir, token desain detail (spacing/radius/shadow)

## Sesi 12 — Fitur POS untuk Transaksi Walk-in (10 Juli 2026)

### Konteks
Andi minta fitur POS untuk pembeli yang datang langsung tanpa lewat app. Diskusi mengungkap 2 pola berbeda yang sempat tercampur:
- **Pola 1 "Ambil Sendiri"**: pesan lewat app dulu, datang & bayar di lokasi — BUKAN POS, cukup field `metode_pengiriman=ambil_sendiri` di Order yang sudah ada
- **Pola 2 "POS murni"**: pembeli tanpa app sama sekali, diinput manual oleh Warung

### Keputusan: Penempatan menu
- POS dapat tombol bulat menonjol di TENGAH bottom nav Warung (akses cepat 1 tap), BUKAN menggantikan beranda — beranda (dashboard) tetap ada karena "ambil sendiri" (yang ternyata jadi pola tatap-muka paling umum) sudah otomatis masuk alur Order Masuk biasa.
- Insight tambahan dari Andi: kurir kemungkinan besar jalan kaki/sepeda (Warung Biasa, area padat), motor/becak (Warung Grosir, kuantitas besar) — dicatat sebagai catatan fleksibilitas `kurir_profiles.kendaraan`.

### Keputusan: POS tidak boleh punya jalur stok terpisah
- Ditambahkan layanan bersama `BuatOrder` — dipanggil baik dari checkout Konsumen (online/ambil-sendiri) maupun POS Warung, satu-satunya pintu masuk ke `Sellable::prosesPengurangan()`. Mencegah duplikasi logika yang bisa reintroduce race condition oversell yang sudah diperbaiki sebelumnya.
- Order dapat field baru: `jenis_transaksi` (online/pos), `metode_pengiriman` (diantar_kurir/ambil_sendiri, nullable), `buyer_type` dapat opsi baru `Umum` (walk-in tanpa akun).
- State machine Order dapat jalur pendek `dibuat -> selesai` langsung (tanpa Kurir) untuk POS dan ambil-sendiri.
- `PembayaranDiterima` untuk POS dipancarkan BERSAMAAN dengan `OrderDibuat` (uang tunai diterima seketika), beda dari alur COD-diantar-Kurir yang menunggu konfirmasi terpisah.

### Keputusan: POS boleh dipakai offline (Lapis 3)
- Beda dari checkout online Konsumen (wajib koneksi) — POS masuk daftar aksi kritis offline karena dipakai terus-menerus di kasir sepanjang hari.
- Risiko diakui secara eksplisit di pos.md: transaksi offline yang bentrok bisa menyebabkan stok minus sesaat setelah sync — dicatat sebagai anomali di log, BUKAN ditolak (karena transaksi dunia nyata sudah terjadi, uang & barang sudah berpindah tangan).

### File yang dibuat/diupdate
- `pos.md` (baru): alur lengkap 4 layar POS (grid produk, keranjang, konfirmasi, kondisi offline), komponen baru, pemetaan folder Livewire
- `project.md`: state machine diupdate (jalur pendek POS/ambil-sendiri), bagian baru "Layanan Bersama BuatOrder", field POS baru, catatan kendaraan Kurir
- `events.md`: payload `OrderDibuat` diupdate (jenis_transaksi, metode_pengiriman, buyer_type=Umum), catatan `PembayaranDiterima` instan untuk POS
- `versi_pwa.md`: POS ditambahkan ke daftar aksi kritis Lapis 3
- `desain_ui.md`: bottom nav Warung dapat tombol POS bulat tengah, Order Masuk dapat badge metode pengiriman

## Sesi 13 — Rekonsiliasi dengan `aturan_bisnis.md` (10 Juli 2026)

### Konteks
Andi upload `project.md` (versi terbaru dari Claude Code, sudah jauh berkembang: alamat terstruktur+GPS, radius slider, harga beli+margin, Struktur Menu Per Role, Control Center/Engine-based framing) dan `aturan_bisnis.md` (dokumen baru dari sesi Claude Code lain, framing "Business Agnostic Architecture" + 8 Commerce Engine + 13 Domain Business Rules). Diminta merekonsiliasi keduanya dengan konsep yang sudah dibangun di sini.

### File project.md diupdate ke versi terbaru
`project.md` yang dikelola di sini di-replace dengan versi upload (lebih maju), lalu ditambah rekonsiliasi di atasnya.

### Temuan 1: Ketegangan arsitektur — diluruskan
`aturan_bisnis.md` menyatakan filosofi "1 tabel Product generik untuk semua bisnis" (Business Agnostic literal). Ini BERTENTANGAN dengan alasan awal kontrak `Sellable` dibuat sebagai interface (bukan tabel bersama) — supaya tiap vertikal bisa punya struktur data berbeda total (Apotik: resep/expiry/batch, WarungMakan: tanpa stok angka).
- **Diluruskan:** "Business Agnostic" yang benar = modul INTI (Order/Payment/Kurir/Auth) tidak perlu tahu jenis bisnis, itu memang tujuan Sellable. Bukan berarti bikin 1 tabel `products` universal.
- Bukti pendukung: implementasi aktual sudah pakai tabel `warung_produk` (vertikal-spesifik), bukan `products` generik — jadi kemungkinan cuma framing bahasa yang kelewat literal, sudah diklarifikasi tertulis di project.md supaya tidak jadi salah kaprah nanti saat bangun vertikal lain.

### Temuan 2: Employee Rules — dikonfirmasi ditunda
`aturan_bisnis.md` memperkenalkan multi-pegawai per outlet (Owner/Kasir/Gudang/Admin/Kurir Internal) — belum ada di skema kita (`outlets.owner_user_id` asumsi 1 pemilik). Ditanya ke Andi: **ditunda**, tetap 1 pemilik per outlet untuk sekarang. Dicatat sebagai referensi masa depan (tabel `outlet_staff` pivot) di project.md, TIDAK dibangun sekarang.

### Temuan 3: Domain bisnis baru lain — direkonsiliasi ke roadmap
- Dashboard = daftar pekerjaan hari ini (bukan cuma statistik) — diadopsi, ditambahkan ke desain_ui.md
- Finance Rules (jurnal sederhana, laba kotor, piutang/hutang) — lebih luas dari cod_settlements yang ada, ditandai BUTUH SESI DESAIN TERPISAH, tidak diimplementasikan diam-diam
- CRM, Notification, AI/Warung Intelligence — konsisten dengan Fase 1.5/Fase 3 yang sudah ada, tinggal mempertajam detail
- Report Rules — bisa diturunkan dari tabel yang sudah ada, tidak butuh tabel baru

### File yang diupdate
- `project.md`: diganti dengan versi terbaru dari Claude Code + bagian klarifikasi Business Agnostic + bagian "Domain Bisnis Tambahan" (tabel rekonsiliasi 7 domain)
- `desain_ui.md`: prinsip dashboard-sebagai-daftar-pekerjaan ditambahkan ke bagian Dashboard Warung

### Catatan
`events.md`, `syarat_ketentuan.md`, `versi_pwa.md`, `pos.md` yang dikelola di sini belum tentu sinkron dengan perkembangan di Claude Code (cuma project.md yang diupload kali ini) — kalau ada perbedaan serupa ditemukan di file lain, perlu proses rekonsiliasi yang sama.

## Sesi 14 — Sinkronisasi dengan Log Implementasi `last_update_pwa.md` (11 Juli 2026)

### PENTING: Percabangan penomoran sesi
`last_update_pwa.md` (dikelola Claude Code) ternyata FORK dari file ini persis di Sesi 10 — sesi 1-10 identik, tapi sejak itu kedua file jalan independen dengan nomor sesi yang SAMA tapi ISI BERBEDA:
- File ini (last_update.md, sesi web): Sesi 11 = Desain UI Modular, Sesi 12 = POS, Sesi 13 = Rekonsiliasi aturan_bisnis.md
- last_update_pwa.md (sesi Claude Code): Sesi 11 = PWA Full-Stack, Sesi 12 = Bug Fix + Email OTP + Google Login, Sesi 13 = Rebranding Derum, Sesi 14 = Barcode+POS+Laporan Konsumen

**Konvensi ke depan:** nomor sesi TIDAK saling merujuk antar 2 file ini sejak titik fork (Sesi 10). `last_update.md` = keputusan arsitektur/desain dari sesi web; `last_update_pwa.md` = log implementasi teknis dari Claude Code. Kalau perlu rujukan silang, sebut nama file + judul sesi, bukan nomor saja.

### Sinkronisasi temuan dari activity.md & last_update_pwa.md (implementasi s.d. 11 Juli 2026)
- **Rebranding: Desahub → Derum** ("Belanja Deket Rumah") — dicatat sebagai nama produk resmi di project.md, arsitektur tetap pakai istilah generik.
- **Konfirmasi kuat:** implementasi POS (buyer_type=Umum, jenis_transaksi=pos, emit OrderDibuat+PembayaranDiterima bersamaan, offline-capable) PERSIS sesuai desain `pos.md` — tidak ada penyimpangan.
- **Barcode produk SELESAI diimplementasikan** (QuaggaJS + fallback Open Food Facts) — status roadmap diupdate dari "ditunda Fase 1.5" jadi selesai.
- **Laporan Konsumen** — halaman baru yang tidak pernah didesain di sini, dibangun langsung di implementasi. Didokumentasikan retroaktif ke `desain_ui.md`.
- **Klarifikasi kritis state machine:** ditemukan sebagai bug nyata saat implementasi — konfirmasi Warung untuk order `diantar_kurir` TIDAK BOLEH memicu transisi ke `diambil_kurir` (harus tetap `dibuat`), HANYA klaim atomik Kurir yang boleh. Kalau dilanggar, order "terkunci" sebelum sempat diklaim Kurir manapun. Ditambahkan sebagai klarifikasi eksplisit di project.md supaya tidak terulang.
- **Email OTP + Google Login** — pengganti pragmatis untuk OTP HP murni (gratis, tidak butuh SMS gateway berbayar). Prinsip desain auth (1 users + profil per-peran, Sanctum) tidak berubah, cuma metode verifikasi awal.

### File yang diupdate
- `project.md`: catatan rebranding Derum, klarifikasi state machine (siapa boleh trigger diambil_kurir), status barcode, catatan Email OTP/Google Login
- `desain_ui.md`: halaman baru "Laporan Konsumen" didokumentasikan retroaktif

### Status
Fondasi arsitektur & desain di sini (last_update.md, project.md, events.md, desain_ui.md, pos.md, versi_pwa.md, syarat_ketentuan.md) sudah cukup sinkron dengan implementasi aktual per 11 Juli 2026. `events.md`, `versi_pwa.md`, `syarat_ketentuan.md` masih berpotensi punya perbedaan lebih lanjut yang belum diverifikasi (belum ada versi terbaru file-file itu yang diupload untuk dibandingkan).

## Sesi 15 — Reorganisasi Navigasi Warung + Fitur Chat Baru (11 Juli 2026)

### Keputusan: Chat Konsumen-Outlet (baru, belum pernah didesain sebelumnya)
- Scope: inbox per pasangan Konsumen-Outlet (bukan per-order) — supaya pertanyaan pra-order tetap bisa ditanyakan tanpa perlu order dibuat dulu.
- Bukan real-time, konsisten batasan Livewire — polling ringan, bukan websocket.
- Skema: tabel `percakapan` (outlet_id, konsumen_id) + `pesan` (percakapan_id, pengirim_type/id, isi_pesan, dibaca_pada).
- Event baru `PesanDikirim`, modul baru `Modules/Chat` (Fase 1.5, selaras timeline CRM Rules yang sudah ada).

### Keputusan: Reorganisasi navigasi Warung
- Laporan digabung ke Beranda (bukan tab terpisah lagi) — isi laporan (grafik omzet, top produk) jadi bagian scroll-down Beranda.
- Produk ditambahkan ke bottom nav (sebelumnya halaman "Kelola Produk" ada tapi tidak di bottom nav).
- Profil dipindah dari bottom nav ke top nav sebagai dropdown (isi: Pengaturan Profil + Keluar).
- Top nav tidak lagi tampilkan nama pribadi pemilik — diganti nama outlet/warung (`auth()->user()->outlet?->nama`).
- Bottom nav baru (5 slot simetris): Beranda | Order | POS (tengah) | Produk | Chat.

### File yang diupdate/dibuat
- `project.md`: desain lengkap fitur Chat (skema data, event, modul)
- `desain_ui.md`: top nav baru (nama outlet + dropdown profil), bottom nav baru (5 slot), Dashboard diperluas dengan ringkasan laporan, wireframe halaman Chat baru
- `warung.blade.php`: kode nav diupdate sesuai desain di atas

### Catatan penting — perlu diverifikasi/ditambahkan di sisi implementasi (Claude Code)
Karena saya tidak punya akses ke `routes/web.php` dan `User.php` model yang sebenarnya, beberapa hal di kode `warung.blade.php` yang saya buat masih ASUMSI dan perlu dicek:
1. **Route `warung.produk` dan `warung.chat`** — nama route ini saya asumsikan mengikuti pola yang sudah ada (`warung.dashboard`, `warung.order-masuk`, dst). Perlu dicek/dibuat di `routes/web.php` sesuai nama controller & method yang sebenarnya dipakai (kemungkinan `warung.kelola-produk` sudah ada dengan nama lain).
2. **Relasi `outlet()` di User model** — kode pakai `auth()->user()->outlet?->nama`. Kalau relasi ini belum ada di `app/Models/User.php`, perlu ditambahkan: `hasOne(\Modules\Outlet\app\Models\Outlet::class, 'owner_user_id')`.
3. **Variabel `$chatBelumDibaca`** — dipakai untuk badge notifikasi di ikon Chat. Supaya tersedia di semua halaman (bukan cuma yang eksplisit passing variabel ini), disarankan pakai Laravel View Composer, bukan passing manual di tiap controller.

### Status
Modul `Chat` belum diimplementasikan — ini baru desain awal + reorganisasi nav. Implementasi backend Chat (migration, model, controller, routes) belum dikerjakan.

## Sesi 16 — Debug Error `produk_nama` via Analisis SQL Dump (11 Juli 2026)

### Konteks
Andi upload `desahub.sql` (dump database aktual) untuk menelusuri error "produk_nama column not found" yang muncul saat kerja di Claude Code.

### Root cause ditemukan
`order_items` memang TIDAK PERNAH punya kolom nama produk — cuma `sellable_type`+`sellable_id`+`qty`+`harga_satuan` (sesuai desain kontrak `Sellable`, Order tidak boleh tahu isi produk). Nama produk cuma ada di `warung_produk.nama`. Error terjadi karena ada query (kemungkinan untuk fitur Laporan/Top Produk) yang mengasumsikan kolom `produk_nama` ada langsung di `order_items`, padahal tidak pernah dibuat.

### Keputusan: Tambah kolom snapshot `nama_produk`, bukan JOIN
- **Opsi ditolak:** JOIN `order_items` ke `warung_produk` saat baca — berisiko riwayat order berubah nama kalau produk diedit/dihapus warung belakangan.
- **Opsi dipilih:** `ALTER TABLE order_items ADD COLUMN nama_produk VARCHAR(255)`, diisi via `BuatOrder` dari `Sellable::getNama()` SAAT transaksi terjadi — pola SAMA PERSIS dengan `harga_satuan` yang sudah snapshot (bukan live-lookup). Konsistensi ini yang jadi alasan utama pemilihan opsi.
- Data lama (order_items sebelum migration ini) perlu backfill manual sekali (JOIN satu kali ke warung_produk), bukan solusi permanen.

### File yang diupdate
- `project.md`: bagian baru "8. Snapshot Nama Produk di order_items" di Integritas Transaksi (mengisi celah penomoran yang sebelumnya bolong di 1-10)
- `events.md`: aturan #6 diperluas eksplisit mencakup nama (bukan cuma harga), payload `OrderDibuat.items` ditambah field `nama_produk`

### Status
Ini murni temuan bug + rekomendasi perbaikan skema — implementasi migration & update `BuatOrder` service belum dikerjakan, perlu dibawa ke Claude Code.