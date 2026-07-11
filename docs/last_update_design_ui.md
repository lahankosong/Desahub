# Last Update — Ekosistem Warung

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
