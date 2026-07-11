# Project: Platform Commerce Lokal (Ekosistem Warung sebagai vertikal pertama)

> **Nama produk resmi: Derum** — "Belanja Deket Rumah" (rebranding dari nama kerja "Desahub", per Sesi 14 implementasi Juli 2026). Dokumen ini tetap pakai istilah generik "Outlet"/"Platform" untuk arsitektur, "Derum" dipakai di lapisan branding/UI saja.

## Visi
Membangun **platform commerce lokal modular** yang menghubungkan:
Distributor/Supplier -> Sales -> Outlet -> (Konsumen & Kurir Lokal)

"Outlet" adalah istilah generik untuk entitas bisnis yang menjual — bisa warung sembako, apotik, warung makan, toko bangunan, toko pupuk, dst. **Warung sembako adalah vertikal pertama yang dibangun**, tapi fondasi arsitektur didesain agar vertikal lain tinggal dipasang sebagai modul baru tanpa mengubah modul inti (Order, Payment, Kurir, Auth).

Radius operasional awal: maksimal 1 km per outlet.

**Catatan skala:** Apotik dan Toko Pupuk punya kewajiban pelaporan ke pemerintah (resep obat, pupuk bersubsidi by NIK petani) — ini bukan sekadar "vertikal lain", tapi butuh lapisan compliance terpisah (lihat bagian Kontrak Compliance).

---

## Stack Teknis (Keputusan Arsitektur)

| Layer | Teknologi | Alasan |
|---|---|---|
| Backend/API | Laravel (REST API) di Rumahweb shared hosting | Konsisten dengan project lain, beban realtime dipindah ke client sehingga shared hosting tetap layak dipakai |
| Frontend | Flutter (multi-app: Outlet, Konsumen, Kurir — app Outlet bisa punya varian UI per vertikal) | Satu codebase -> Android & iOS, cocok untuk pola offline-first |
| Local storage (client) | SQLite / Hive / Drift | Cache lokal agar app tetap jalan tanpa koneksi |
| Sinkronisasi | REST API, sync saat jaringan stabil | Tidak butuh websocket persisten -> tidak butuh VPS |

**Catatan penting:** karena pola offline-first ini, server TIDAK perlu mempertahankan koneksi terbuka (no websocket). Ini yang membuat Laravel di shared hosting Rumahweb tetap layak dipakai sebagai backend, konsisten dengan pola kerja project-project Andi yang lain.

---

## Arsitektur Modular (Plug-and-Play)

**Prinsip:** pengembangan baru = tambah konektor (Listener), TIDAK BOLEH modifikasi modul yang sudah ada.

### Mekanisme
- Komunikasi antar-modul lewat **Event & Listener**, bukan pemanggilan langsung antar-Controller/Model.
- Modul inti memancarkan Event (`OrderDibuat`, `StokBerubah`, `PembayaranDiterima`) tanpa tahu siapa yang mendengarkan.
- Modul/fitur baru cukup daftar Listener baru untuk Event yang sudah ada -> kode modul lama tidak pernah disentuh.
- Contoh penerapan ke roadmap: QRIS (Fase 4) = Listener baru untuk `PembayaranDiterima` yang sudah ada sejak Fase 1, bukan modifikasi modul Payment/Order.

### Struktur folder (per-modul, bukan per-layer)
```
Modules/
  Outlet/           (generik: profil, GPS, verifikasi — dipakai semua vertikal)
  Warung/           (vertikal 1: produk sembako, implementasi Sellable)
  Apotik/           (vertikal masa depan: implementasi Sellable + ComplianceReportable)
  WarungMakan/      (vertikal masa depan: implementasi Sellable, tanpa stok angka)
  TokoBangunan/     (vertikal masa depan: implementasi Sellable, satuan campur)
  TokoPupuk/        (vertikal masa depan: implementasi Sellable + ComplianceReportable)
  Order/            (generik, TIDAK tahu isi produk — hanya sellable_type + sellable_id)
  Kurir/
  Sales/
  Distributor/
  Payment/          (tempat COD, transfer, QRIS dst masuk sebagai listener/connector)
  Compliance/        (listener untuk vertikal ComplianceReportable — Apotik, TokoPupuk)
```
Kandidat tooling: `nwidart/laravel-modules` (composer package). Aman dipakai karena workflow deploy Andi = build lokal (termasuk `composer install`) lalu upload folder jadi (termasuk vendor) ke cPanel — tidak butuh SSH untuk composer.

### Kontrak `Sellable` (jawaban untuk "apa itu produk secara generik")
Setiap model produk per-vertikal WAJIB mengimplementasikan kontrak ini. Order hanya menyimpan referensi polymorphic (`sellable_type` + `sellable_id`), tidak pernah tahu isi produk:

| Method | Fungsi |
|---|---|
| `getNama()` | nama item yang dijual |
| `getHarga(qty)` | harga saat ini, terima parameter qty supaya vertikal yang butuh harga bertingkat (mis. Warung Grosir) bisa terapkan price-break; vertikal biasa cukup selalu return harga tetap terlepas qty |
| `getSatuan()` | pcs / kg / sak / porsi, dst |
| `cekTersedia(qty)` | boleh dibeli atau tidak — stok fisik ATAU status tersedia/habis, tergantung vertikal |
| `prosesPengurangan(qty, referensiId)` | logika spesifik vertikal: Warung/Apotik kurangi stok fisik, WarungMakan bisa no-op (bukan stok angka) |

Karena "stok" tidak berlaku universal, event terkait diberi nama netral: **`KetersediaanBerubah`** (bukan `StokBerubah`) — lihat `events.md`.

### Klarifikasi: "Business Agnostic Architecture" vs Pola Sellable-per-Vertikal
`aturan_bisnis.md` (disusun terpisah) menyebut filosofi *"Engine tidak mengenal Warung/Apotek — 1 tabel Product generik untuk semua bisnis"*. Ini perlu diluruskan supaya tidak disalahartikan secara literal:

- **Yang BENAR dan konsisten dengan arsitektur kita:** "Business Agnostic" berarti modul INTI (Order, Payment, Kurir, Auth) tidak perlu tahu jenis bisnis apa yang sedang ditransaksikan — itu persis tujuan kontrak `Sellable` (Order cuma pegang `sellable_type`+`sellable_id`, tidak peduli isinya).
- **Yang SALAH kalau diterapkan literal:** membuat 1 tabel `products` universal untuk semua vertikal. Ini bertentangan dengan alasan awal kita bikin kontrak `Sellable` sebagai INTERFACE (bukan tabel bersama) — supaya tiap vertikal bisa punya struktur data yang benar-benar beda (Apotik butuh `no_resep`/`expiry`/`batch`, Warung Makan tidak punya konsep stok angka sama sekali). Tabel `warung_produk` yang sudah dipakai di implementasi (bukan `products` generik) SUDAH BENAR mengikuti pola kontrak, bukan pola tabel tunggal.
- **Kesimpulan:** lanjutkan pola vertikal punya tabel/model sendiri yang mengimplementasikan `Sellable`, JANGAN membuat tabel produk generik lintas-vertikal.

### Kontrak `BuyerEligibilityPolicy` (opsional, jawaban untuk "siapa boleh beli dari outlet ini")
Default: semua outlet boleh dibeli siapa saja (Konsumen maupun Outlet lain). Outlet yang punya aturan pembeli khusus (mis. Warung Grosir — lihat bagian Tingkatan Warung) mengimplementasikan kontrak ini untuk override:

| Method | Fungsi |
|---|---|
| `bolehDibeliOleh(buyerType, buyerId)` | `true`/`false` — dicek Order saat checkout, sebelum `OrderDibuat` dipancarkan |

Order module memanggil kontrak ini (Order boleh bergantung ke Outlet karena keduanya modul inti/Core, bukan pelanggaran prinsip "tidak modifikasi modul lama" — prinsip itu melindungi Order dari perlu tahu detail vertikal atau aturan bisnis spesifik, bukan melarang Order memanggil layer Outlet yang memang jadi fondasinya).

### Kontrak `ComplianceReportable` (opsional, khusus vertikal teregulasi)
Hanya diimplementasikan oleh vertikal yang punya kewajiban pelaporan pemerintah (Apotik: resep obat, TokoPupuk: subsidi by NIK petani). Modul **Compliance** mendengarkan Event `OrderDibuat`/`PembayaranDiterima` yang SUDAH ADA, cek `instanceof ComplianceReportable`, lalu catat ke `compliance_reports` (log append-only). Modul Order/Payment tidak pernah disentuh — vertikal teregulasi baru cukup nambah Listener baru.

### Sisi Flutter
Pola sama: 1 package "core" (auth, API client, model dasar) dipakai bersama oleh app Outlet/Konsumen/Kurir. Fitur spesifik per app/vertikal = package terpisah yang plug ke core tanpa mengubah core.

### Kontrak Event (wajib dipatuhi agar plug-and-play beneran jalan)
1. **Additive-only** — field yang sudah ada di sebuah Event tidak boleh dihapus/diubah tipe/nama. Perubahan = tambah field baru (nullable), field lama tetap ada.
2. **Payload = data eksplisit (DTO-style)**, bukan Eloquent Model utuh — supaya perubahan skema tabel tidak diam-diam merusak Listener.
3. **Bawa ID, bukan objek relasi bersarang** — Listener yang butuh data lengkap query sendiri ke tabel terkait.
4. **Didaftarkan di registry pusat** — dicatat di `events.md` (file terpisah, karena bakal terus tumbuh tiap fase).

Detail skema Event per-modul: lihat `events.md`.

### Batasan yang perlu diingat
- Event-driven bukan berarti async/queue — di shared hosting tanpa worker persisten, Listener bisa dijalankan sync (langsung saat Event terjadi) atau lewat cron-based queue (`queue:work` dipicu cron job cPanel per beberapa menit) kalau butuh proses lebih berat/lambat.
- Modularitas ini mengurangi risiko modifikasi, tapi tetap butuh **kontrak Event yang stabil** sejak awal — kalau struktur data Event berubah, modul yang mendengarkan bisa ikut rusak. Ini yang perlu disepakati di awal desain tiap modul.

---

## Strategi Resolusi Konflik Sinkronisasi

Data dibagi 2 kategori berdasarkan risiko overwrite:

1. **Field non-kritikal** (nama produk, deskripsi, harga, profil outlet, dll)
   -> Resolusi: **timestamp terbaru menang** (last-write-wins). Aman karena nilainya absolut, tidak akumulatif.

2. **Field kritikal berbasis kuantitas** (stok/ketersediaan, saldo, tempo pembayaran)
   -> Resolusi: **TIDAK BOLEH** disimpan sebagai angka absolut yang ditimpa.
   -> Disimpan sebagai **log transaksi/pergerakan** (append-only), mis. tabel `ketersediaan_movements` (+5 / -5, atau status tersedia/habis untuk vertikal non-stok), lalu nilai akhir = SUM/status terakhir dari seluruh log.
   -> Pola ini konsisten dengan cara Andi menjaga audit ledger Brimola di Margosystem — append-only, tidak pernah overwrite langsung.

Alasan: timestamp murni untuk field kuantitas berisiko kehilangan update yang sah saat dua perubahan terjadi hampir bersamaan dari sisi offline berbeda (lihat contoh kasus di last_update.md).

---

## Roadmap Fase (Warung sebagai vertikal pertama; vertikal lain menyusul sebagai modul plug-in)

### Fase 1 — MVP Warung (0–4 Bulan) — SCOPE FINAL (disepakati)

**Wajib dibangun (1 alur transaksi lengkap):**

| Modul | Fitur wajib |
|---|---|
| Outlet (vertikal Warung) | Registrasi + verifikasi dasar (OTP HP), profil + GPS, input produk manual (harga, ketersediaan) |
| Konsumen | Registrasi, cari outlet radius 1km, keranjang, checkout **COD saja** |
| Kurir | Online/offline, terima order, update status antar (diambil -> diantar -> selesai) |
| Sistem | Radius 1km (Haversine), dashboard admin minimal (verifikasi outlet, pantau transaksi) |

**Ditunda ke Fase 1.5 (menyusul setelah alur COD stabil, ditambah sebagai konektor baru — bukti konsep plug-and-play jalan):**
- ~~Scan barcode~~ — **SELESAI diimplementasikan** (QuaggaJS scan kamera + lookup Open Food Facts sebagai fallback, karena tidak ada database barcode produk Indonesia yang open-source & lengkap)
- Transfer & DP (= Listener baru untuk `PembayaranDiterima`, tanpa ubah modul Order/Payment yang sudah ada)
- Notifikasi push realtime (sementara polling/refresh manual sesuai pola offline-first)

**Alasan pemotongan:** 1 alur sempit dulu supaya kalau ada bug, sumbernya jelas — bukan tercampur di antara banyak fitur baru sekaligus.

### Fase 2 — Supply Chain (4–8 Bulan)
- Modul Sales: profil, area kerja, outlet binaan, chat, promo, target penjualan
- Modul Distributor: katalog grosir, harga, promo, persetujuan order, monitoring sales
- Modul Outlet: order stok, riwayat pembelian, tempo pembayaran, multi-sales per akun
- **Tingkatan Warung (Biasa vs Grosir)** — lihat bagian detail di bawah

### Fase 3 — Smart Platform (8–12 Bulan)
- AI barcode, OCR nota, prediksi stok habis, rekomendasi restock, dashboard analitik
- Butuh API pihak ketiga -> perlu rencana biaya (siapa yang nanggung: outlet premium?)

### Fase 4 — Super App
- QRIS, loyalty, voucher, pinjaman modal, integrasi ERP distributor, API publik

### Fase 5+ — Ekspansi Vertikal (jangka panjang, fondasi sudah disiapkan sejak Fase 1)
- Vertikal baru dipasang sebagai modul yang mengimplementasikan kontrak `Sellable` (+ `ComplianceReportable` kalau teregulasi): Apotik, Warung Makan, Toko Bangunan, Toko Pupuk
- Modul Order/Payment/Kurir/Auth TIDAK dimodifikasi saat vertikal baru ditambah — murni penambahan modul + Listener
- Apotik & Toko Pupuk butuh riset kebutuhan pelaporan pemerintah secara spesifik sebelum implementasi (belum dibahas detail)

---

## Domain Bisnis Tambahan (dari `aturan_bisnis.md`, direkonsiliasi ke roadmap)

Dokumen `aturan_bisnis.md` memperkenalkan 13 domain bisnis (Dashboard, Produk, Inventory, Supplier, Order, Delivery, Customer, Finance, Employee, Report, AI, Notification, Warung Intelligence). Sebagian besar sudah cocok dengan roadmap yang ada; ini pemetaannya:

| Domain baru | Status rekonsiliasi |
|---|---|
| **Dashboard = daftar pekerjaan hari ini** (bukan cuma statistik) | Prinsip diadopsi — lihat pembaruan di `desain_ui.md` |
| **Finance Rules** (jurnal sederhana: kas masuk/keluar, piutang/hutang, laba kotor) | Lebih luas dari `cod_settlements` yang sudah ada — **butuh sesi desain terpisah**, jangan diimplementasikan diam-diam sebagai perluasan `cod_settlements` begitu saja karena ini pembukuan penuh, bukan sekadar rekonsiliasi COD |
| **CRM Rules** (membership, poin, voucher, review) | Cocok masuk Fase 1.5+ sesuai roadmap yang sudah ada, belum didesain detail |
| **Notification Rules** (10 trigger) | Cocok masuk Fase 1.5 (notifikasi push, sudah disebut di scope Fase 1.5), daftar 10 trigger di `aturan_bisnis.md` bisa jadi acuan detail nanti |
| **AI Rules / Warung Intelligence** (5 kategori: Demand/Inventory/Customer/Supplier/Profit) | Konsisten dengan Fase 3 "Smart Platform" yang sudah ada, mempertajam detail bukan mengubah timeline |
| **Report Rules** (top product, omzet, margin) | Bisa diturunkan dari tabel yang sudah ada (`orders`, `ketersediaan_movements`) tanpa tabel baru — murni lapisan query/laporan |
| **Employee Rules** (Kasir/Gudang/Admin per outlet, multi-user per outlet) | **DITUNDA** — dikonfirmasi tidak perlu disiapkan skemanya sekarang, tetap 1 pemilik per outlet (`outlets.owner_user_id`) untuk saat ini. Kalau nanti dibutuhkan, akan butuh tabel baru `outlet_staff` (pivot: outlet_id, user_id, role, permissions) — dicatat sebagai referensi masa depan, BUKAN dibangun sekarang. |

---

## Tingkatan Warung: Biasa vs Grosir

**Aturan bisnis (disepakati):** Sales dari Supplier/Distributor hanya berhubungan dengan Warung Grosir. Warung Biasa naik jadi Warung Grosir dengan syarat minimal 1 approval dari Sales terdaftar. Warung Grosir **hanya boleh di-order oleh Warung Biasa** — tertutup total untuk Konsumen maupun pihak lain, untuk melindungi ekosistem (mencegah Konsumen langsung "membajak" harga grosir yang seharusnya untuk pedagang, dan menjaga insentif Sales tetap relevan).

**Skema data:**
```
warung_detail
  ...
  tier (enum: biasa, grosir)        <- field CACHE, bukan sumber kebenaran

warung_grosir_approvals             <- log append-only, sumber kebenaran tier grosir
  id, outlet_id (FK), sales_id (FK sales_profiles), jenis (disetujui | dicabut), catatan, terjadi_pada
```
`tier` tidak pernah diedit manual — hanya di-update lewat Event, konsisten dengan pola append-only yang sudah dipakai untuk ketersediaan/stok.

**Nuansa agregasi:** BEDA dengan stok (yang nilainya = SUM semua log), tier itu **status terkini = entri TERBARU** di `warung_grosir_approvals` (state terakhir menang, bukan dijumlah). Ini dicatat eksplisit supaya tidak disamakan logikanya dengan pola stok saat implementasi.

**Pencabutan status:** approval bukan status permanen — hubungan dengan Sales bisa putus atau ada penyalahgunaan. Event `WarungDicabutGrosir` (pasangan dari `WarungDisetujuiGrosir`) mengembalikan `tier` ke `biasa` lewat entri baru `jenis = dicabut`, bukan menghapus riwayat approval lama.

**Proteksi dari penyalahgunaan status Grosir:** karena Grosir dapat privilege harga khusus, ada risiko orang bikin akun Warung Biasa palsu cuma untuk akses harga grosir. Ditambah syarat: pembeli harus outlet dengan `level_verifikasi = terverifikasi` (verifikasi lokasi usaha oleh admin, bukan sekadar OTP HP saat registrasi) — bukan cuma soal tipe outlet.

**Perubahan skema Order (penting, dilakukan sekarang selagi masih desain, belum ada kode):**
`konsumen_id` digeneralisasi jadi **`buyer_type` + `buyer_id`** (polymorphic — bisa `Konsumen` atau `Outlet`), supaya Order bisa menangani baik pembelian Konsumen->Warung Biasa (MVP) maupun Warung Biasa->Warung Grosir (Fase 2 B2B) tanpa desain ulang. Detail payload event terupdate di `events.md`.

**Enforcement lewat kontrak `BuyerEligibilityPolicy`:** Warung Grosir mengimplementasikan `bolehDibeliOleh()` untuk hanya mengizinkan `buyer_type = Outlet` dengan vertikal `warung` aktif, `tier = biasa`, DAN `level_verifikasi = terverifikasi`. Order memanggil kontrak ini saat checkout — modul Order sendiri tidak berisi logika "apa itu grosir".

**Harga bertingkat:** Warung Grosir bisa implementasi `getHarga(qty)` di kontrak `Sellable` untuk price-break (mis. beli 1 lusin lebih murah per pcs dibanding beli 1 pcs) — lihat kontrak Sellable di atas.

**Event terkait:** `WarungDisetujuiGrosir` dan `WarungDicabutGrosir` — dipancarkan Modul Sales (Fase 2) saat approve/cabut, didengar Modul Warung (update cache `tier` + catat log). Detail payload di `events.md`.

---

## Integritas Transaksi (kritis, wajib ada sebelum MVP jalan)

### 1. Race Condition Pengurangan Stok (paling kritis)
Pola `cekTersedia()` lalu `prosesPengurangan()` terpisah rentan oversell kalau 2 pembeli checkout produk yang sama nyaris bersamaan. Solusi: `prosesPengurangan()` dibungkus 1 DB transaction dengan **UPDATE atomik bergerbang**, bukan check-then-act di level aplikasi:
```sql
UPDATE ketersediaan_cache
SET qty = qty - :jumlah
WHERE sellable_type = :type AND sellable_id = :id AND qty >= :jumlah
```
Kalau `affected_rows = 0` → tolak (stok tidak cukup saat itu juga, race condition otomatis tercegah oleh DB, bukan oleh logika aplikasi). Insert ke log `ketersediaan_movements` (append-only, untuk audit & resolusi konflik sync offline) dilakukan di transaction yang sama dengan UPDATE cache ini.

**Catatan:** ini melengkapi (bukan mengganti) strategi resolusi konflik sinkronisasi offline yang sudah disepakati — `ketersediaan_cache` melindungi dari race condition real-time di server, log append-only tetap sumber kebenaran untuk sinkronisasi lintas device offline.

### 2. Rebutan Order Antar-Kurir
Klaim order harus atomik, bukan dicek dulu baru diupdate:
```sql
UPDATE orders SET kurir_id = :kurir_id, status = 'diambil'
WHERE id = :order_id AND kurir_id IS NULL
```
`affected_rows = 0` → order sudah diklaim kurir lain, tampilkan pesan tersebut. Kurir pertama yang berhasil UPDATE otomatis menang tanpa perlu locking eksplisit di aplikasi.

### 3. State Machine Order (formal — diperbarui Sesi 12)
| Status | Bisa lanjut ke | Dipicu oleh |
|---|---|---|
| `dibuat` | `diambil_kurir`, `selesai` (ambil sendiri), `dibatalkan` | Sistem → Kurir claim / Warung konfirmasi ambil sendiri / Batal |
| `diambil_kurir` | `diantar`, `dibatalkan` | Kurir update / pembatalan |
| `diantar` | `selesai`, `gagal_kirim` | Kurir konfirmasi sampai / gagal |
| `selesai` | *(final)* | — |
| `dibatalkan` | *(final)* | — |
| `gagal_kirim` | `dibatalkan` | Admin/Kurir |

Transisi di luar tabel ini DITOLAK di level aplikasi (bukan hanya diasumsikan tidak akan terjadi).

**Klarifikasi penting (ditemukan sebagai bug saat implementasi, Sesi 13 log implementasi):** untuk order `diantar_kurir`, konfirmasi Warung **TIDAK BOLEH** memicu transisi ke `diambil_kurir` — status tetap `dibuat` setelah dikonfirmasi Warung. HANYA klaim atomik oleh Kurir (`WHERE status='dibuat' AND kurir_id IS NULL AND metode_pengiriman='diantar_kurir'`) yang boleh memicu transisi ini. Kalau Warung ikut memicu transisi saat konfirmasi, order akan "terkunci" sebelum sempat diklaim Kurir manapun — bertentangan dengan desain rebutan-order atomik di poin 2.

**Jalur pendek `dibuat` → `selesai` langsung** (tanpa Kurir): berlaku untuk **ambil sendiri** (`metode_pengiriman = ambil_sendiri`) — Warung klik "✔ Selesai", status langsung final, pembayaran dicatat ke `cod_settlements` (semi-POS).

### 4. Alur Pembatalan/Kegagalan (Event baru)
`OrderDibatalkan`:
- **Payload:** `order_id`, `dibatalkan_oleh_type` (`Konsumen`/`Warung`/`Kurir`/`Admin`), `alasan`, `terjadi_pada`
- **Listener:** `Modules/Warung` — kembalikan ketersediaan lewat entri KOMPENSASI baru di `ketersediaan_movements` (jumlah_perubahan positif, alasan=`pembatalan`, referensi_id=order_id) — BUKAN menghapus/mengedit entri pengurangan yang lama, konsisten prinsip append-only.
- **Listener:** `Modules/Order` — set status jadi `dibatalkan`.

### 5. Rekonsiliasi Uang COD
MVP disederhanakan: tabel `cod_settlements` (append-only) mencatat siapa pegang uang siapa:
```
cod_settlements
  id, order_id, kurir_id, jumlah_diterima, status_setor (belum_disetor/sudah_disetor), dicatat_pada, dicatat_oleh (warung/kurir/admin)
```
- **Semi-POS:** untuk order "ambil sendiri", Warung mencatat pembayaran langsung ke `cod_settlements` saat klik "✔ Selesai". `kurir_id` NULL karena tidak ada kurir.
- **Diantar kurir:** kurir yang mencatat COD saat serah terima barang ke konsumen.
- Admin menyetor manual di dashboard untuk MVP. Ini memastikan ada TEMPAT pencatatan sejak awal.

### 6. Metode Pengiriman
Dua opsi saat checkout:
| Metode | State Machine | Pembayaran |
|---|---|---|
| **Ambil Sendiri** (`ambil_sendiri`) | `dibuat` → `selesai` (langsung) | COD/transfer di warung |
| **Diantar Kurir** (`diantar_kurir`) | `dibuat` → `diambil_kurir` → `diantar` → `selesai` | COD saat terima / transfer |

Perubahan skema: `orders` punya 2 kolom baru:
- `jenis_transaksi` (enum: `online`, `pos`) — default `online`
- `metode_pengiriman` (enum: `diantar_kurir`, `ambil_sendiri`) — nullable untuk POS
- `alamat_antar` — text, wajib hanya jika `metode_pengiriman = diantar_kurir`
- `catatan` — text opsional

### 7. Alamat Terstruktur + GPS
Tabel `outlets` diperluas:
```
outlets
  ...,
  provinsi, kabupaten, kecamatan, desa_kelurahan, rt, rw, kode_pos,
  lat (decimal 10,7), lng (decimal 10,7)
```
Urutan hierarki: Provinsi → Kabupaten/Kota → Kecamatan → Desa/Kelurahan → RT → RW → Kode Pos.

GPS wajib untuk pencarian radius — warung tanpa GPS tidak muncul di hasil pencarian radius.

### 8. Snapshot Nama Produk di `order_items` (bug ditemukan Sesi 16, dari log error `produk_nama` tidak ditemukan)
`order_items` cuma menyimpan referensi polymorphic (`sellable_type`+`sellable_id`) sesuai desain kontrak `Sellable` — TIDAK ada kolom nama produk. Ini menyebabkan laporan "Top Produk"/riwayat order gagal saat mencoba query kolom `produk_nama` yang memang tidak pernah ada.

**Perbaikan (konsisten dengan pola `harga_satuan` yang SUDAH snapshot):**
```sql
ALTER TABLE order_items ADD COLUMN nama_produk VARCHAR(255) NOT NULL AFTER sellable_id;
```
- Diisi via layanan `BuatOrder` saat item ditambahkan ke order — ambil dari `Sellable::getNama()` SAAT ITU JUGA, sama seperti `harga_satuan` diambil dari `getHarga(qty)` saat itu juga.
- **Kenapa snapshot, bukan JOIN ke `warung_produk` saat baca:** kalau nama produk diedit/dihapus warung belakangan, riwayat order lama HARUS tetap menampilkan nama sesuai kondisi saat transaksi terjadi — sama alasannya dengan kenapa harga di-snapshot, bukan re-lookup.
- Laporan Top Produk (Warung & Konsumen) cukup `GROUP BY nama_produk` dari `order_items`, tidak perlu JOIN ke tabel produk asal sama sekali.
- **Data lama (order_items yang sudah ada sebelum migration ini) tidak punya nilai — perlu backfill manual** (JOIN satu kali ke `warung_produk` berdasarkan `sellable_id` untuk isi kolom baru, HANYA untuk data historis yang sudah ada; setelahnya kolom ini selalu diisi otomatis oleh `BuatOrder`).

### 9. Pencarian Radius (Konsumen)
- **Default radius 1km** — menggunakan GPS dari profil konsumen (`konsumen_profiles.lat/lng`)
- **Radius slider** — 1-50km, geser −/+
- **Haversine + bounding-box** — optimasi query MySQL tanpa index spasial
- **GPS prompt** — hanya muncul jika radius > 1km dan belum ada GPS input
- **Reverse geocode** — konversi koordinat ke nama desa/kecamatan dari data outlet terdekat

### 10. Harga Beli + Margin
Tabel `warung_produk` punya kolom `harga_beli` (nullable decimal). View Warung menampilkan margin otomatis: `Margin: +RpXX.XXX` (harga jual - harga beli).

---

## Syarat & Ketentuan Pengguna
Draf lengkap ada di `syarat_ketentuan.md`. Prinsip inti: Platform belum menerapkan skema bisnis/keuntungan, sehingga seluruh aktivitas dan transaksi tiap peran (Outlet, Konsumen, Kurir, Sales, Distributor) menjadi tanggung jawab masing-masing pengguna. Kewajiban Platform dibatasi pada perlindungan data pengguna lewat enkripsi.

**Perlu diingat:** draf ini belum direview secara hukum — ada kewajiban dari UU Pelindungan Data Pribadi (UU 27/2022) yang kemungkinan lebih luas dari sekadar enkripsi (lihat catatan lengkap di `syarat_ketentuan.md`).

---

## Fitur Chat Konsumen–Outlet (baru, Sesi 15)

**Kebutuhan:** Warung perlu bisa dihubungi konsumen yang bertanya (sebelum atau saat order). Sebelumnya CRM Rules di `aturan_bisnis.md` menyebut "Chat" sekilas tanpa desain detail — ini pertama kali dirancang konkret.

**Keputusan desain:**
- **Scope: inbox per pasangan Konsumen–Outlet**, bukan per-order. Alasan: pertanyaan sering muncul SEBELUM order dibuat (mis. "ready gak barangnya?"), jadi mengikat chat ke `order_id` akan memaksa ada order dulu sebelum bisa tanya — tidak realistis.
- **Bukan real-time** — konsisten dengan batasan Livewire yang sudah disepakati (butuh koneksi server, tidak ada websocket). Pesan baru muncul lewat refresh/polling ringan (interval beberapa detik), bukan push instan.
- **Notifikasi pesan masuk** memakai jalur yang sama dengan Notification Rules yang sudah direncanakan (Fase 1.5) — bukan sistem notifikasi terpisah.

**Skema data:**
```
percakapan
  id, outlet_id (FK), konsumen_id (FK users), dibuat_pada

pesan
  id, percakapan_id (FK), pengirim_type (Konsumen|Outlet), pengirim_id,
  isi_pesan, dibaca_pada (nullable), dikirim_pada
```

**Event baru:** `PesanDikirim`
- Dipancarkan Modul Chat saat pesan baru dikirim (dari Konsumen atau Outlet)
- Payload: `percakapan_id`, `pengirim_type`, `pengirim_id`, `isi_pesan`, `dikirim_pada`
- Listener: notifikasi ke pihak lawan bicara (masuk daftar notifikasi dashboard)

**Modul:** `Modules/Chat` (baru) — Fase 1.5, konsisten dengan timeline CRM Rules yang sudah ada di roadmap.

---

## Model Bisnis
1. Komisi transaksi
2. Langganan Premium Warung
3. Langganan Distributor
4. Iklan produk
5. Data insight anonim

---

## Skema Autentikasi Lintas App

**Mekanisme:** Laravel Sanctum (token ringan, cocok untuk shared hosting dibanding Passport yang butuh OAuth server penuh).

**Catatan implementasi (Sesi 13 log implementasi):** OTP awalnya didesain via SMS ke HP, tapi diganti pragmatis jadi **Email OTP** (Gmail SMTP, gratis) karena SMS gateway berbayar. Ditambah juga **Google Login** (Laravel Socialite, gratis) sebagai opsi alternatif — user baru dari Google otomatis dianggap terverifikasi (`no_hp_verified_at` diisi `now()` karena Google sudah verifikasi identitas). Prinsip desain (1 users + profil per-peran, Sanctum untuk token API) tidak berubah — ini cuma penggantian metode verifikasi awal.

**Prinsip:** 1 identitas dasar (1 akun) per orang, peran disimpan sebagai profil terpisah yang ditempel ke akun — bukan 1 kolom `role` tunggal.

```
users                    <- identitas dasar: nama, HP, password
  ├── outlet_profiles    <- FK user_id, generik untuk pemilik outlet apapun vertikalnya
  │     └── (ekstensi per-vertikal: warung_detail, apotik_detail, dst — nempel ke outlet_profiles, bukan ke users)
  ├── konsumen_profiles  <- FK user_id (atau default tanpa profil khusus)
  ├── kurir_profiles     <- FK user_id, hanya ada kalau user jadi kurir
  └── sales_profiles     <- FK user_id, ditambah nanti Fase 2 (modul baru, TIDAK ubah tabel users)
```

**Alasan:** satu orang bisa punya lebih dari satu peran (kurir yang juga konsumen, pemilik outlet yang nanti terhubung ke sales), dan tiap peran punya kebutuhan verifikasi berbeda (outlet: lokasi usaha + dokumen spesifik vertikal, kurir: kendaraan, sales: terikat distributor). Kalau dipaksa 1 kolom `role`, data campur aduk dan sulit diverifikasi per peran.

**Token & scope:** token Sanctum diberi ability/scope sesuai peran aktif yang dipakai app tertentu. Token dari app Kurir cuma bisa akses endpoint kurir, meski user yang sama juga punya profil Outlet — tidak otomatis bisa akses endpoint outlet tanpa login ulang sebagai peran itu.

**Konsistensi dengan arsitektur modular:** nambah peran baru (Sales, Distributor di Fase 2) = nambah tabel `*_profiles` + modul baru, tanpa ubah tabel `users` atau modul yang sudah ada. Sejalan dengan prinsip "konektor baru tanpa modifikasi".

---

## Modul Teknis Final — MVP Warung

**Skema `outlets` generik + multi-vertikal + ekstensi vertikal:**
```
outlets
  id, owner_user_id (FK users), nama, lat, lng, alamat,
  level_verifikasi (enum: dasar, terverifikasi), dibuat_pada
  -- CATATAN: tidak ada kolom tipe_vertikal tunggal, lihat outlet_vertikal di bawah

outlet_vertikal                <- pivot: 1 outlet bisa >1 vertikal (mis. warung + jual pulsa)
  id, outlet_id (FK), vertikal (enum: warung, apotik, warung_makan, toko_bangunan, toko_pupuk, ...),
  status (aktif/nonaktif), aktif_sejak
  -- unique(outlet_id, vertikal): satu outlet tidak boleh punya baris ganda untuk vertikal yang sama

warung_detail                  <- ekstensi vertikal Warung, FK outlet_id
  outlet_id, jam_buka, jam_tutup, kategori_warung,
  tier (enum: biasa, grosir)   <- field CACHE, sumber kebenaran di warung_grosir_approvals (lihat bagian Tingkatan Warung)
```
`level_verifikasi` di `outlets` beda dari OTP HP saat registrasi — ini verifikasi lokasi usaha oleh admin, prasyarat untuk transaksi Grosir (lihat bagian Tingkatan Warung).

**Daftar modul & isinya:**

| Modul | Isi |
|---|---|
| `Modules/Core` | Bukan modul bisnis — kontrak `Sellable` & `ComplianceReportable`, trait log ketersediaan (SUM-based), helper query radius |
| `Modules/Auth` | `users`, Sanctum, `outlet_profiles`/`konsumen_profiles`/`kurir_profiles`, registrasi + OTP |
| `Modules/Outlet` | Tabel `outlets` generik + `outlet_vertikal` (pivot multi-vertikal), verifikasi lokasi (`level_verifikasi`), pencarian radius 1km |
| `Modules/Warung` | `warung_detail`, `produk` (implementasi `Sellable`) — vertikal pertama |
| `Modules/Order` | Order, order_items (polymorphic sellable), buyer polymorphic (`buyer_type`+`buyer_id`: Konsumen di MVP, Outlet untuk B2B Fase 2), checkout, pancarkan `OrderDibuat` |
| `Modules/Payment` | Konfirmasi COD saat serah terima, pancarkan `PembayaranDiterima` |
| `Modules/Kurir` | Status online/offline, terima order, update status antar |

**Mapping Event untuk MVP** (detail payload di `events.md`):
- `OrderDibuat` → didengar Kurir (munculkan order baru ke kurir available), didengar Warung (trigger `prosesPengurangan` → memancarkan `KetersediaanBerubah`)
- `KetersediaanBerubah` → dicatat sebagai log, belum ada listener lain di MVP (Fase 3: prediksi stok habis akan dengar ini nanti)
- `PembayaranDiterima` → didengar Order (ubah status jadi `selesai`)

**Query radius 1km (Haversine, dioptimasi bounding-box karena shared hosting tanpa index spasial andal):**
```sql
SELECT *, (
  6371 * acos(
    cos(radians(:lat)) * cos(radians(lat)) *
    cos(radians(lng) - radians(:lng)) +
    sin(radians(:lat)) * sin(radians(lat))
  )
) AS jarak_km
FROM outlets
WHERE lat BETWEEN :lat_min AND :lat_max
  AND lng BETWEEN :lng_min AND :lng_max
HAVING jarak_km <= 1
ORDER BY jarak_km ASC
```
Bounding box (`:lat_min/max`, `:lng_min/max`) dihitung di aplikasi (±1km) sebelum query, supaya `WHERE` bisa pakai index biasa pada kolom `lat`/`lng` — `HAVING` baru menyaring jarak presisi dari himpunan kecil hasil bounding box.

---

## Struktur Menu Per Role (Visi Lengkap)

Berikut adalah visi lengkap menu/fitur untuk setiap role. Yang sudah diimplementasikan ditandai ✅.

### Halaman Warung
```
Dashboard
  ✅ Omzet Hari Ini
  ✅ Order Hari Ini
  ✅ Produk Habis / Stok Tipis
  ⏳ Kurir Aktif
  ⏳ Sales Datang Hari Ini
  ⏳ Restock
  ⏳ Pendapatan Minggu Ini
  ⏳ Top Product

Produk
  ✅ Tambah Produk (input manual)
  ⏳ Scan Barcode
  ⏳ Upload Foto
  ⏳ Kategori
  ✅ Harga (jual + beli + margin)
  ✅ Stok
  ⏳ Diskon
  ⏳ Bundle

Inventory
  ✅ Stok (via ketersediaan_cache + ketersediaan_movements)
  ⏳ Mutasi
  ⏳ Barang Masuk
  ⏳ Barang Keluar
  ⏳ Penyesuaian
  ⏳ Expired
  ⏳ Forecast

Order
  ✅ Order Baru (filter: Semua, Dibuat, Diproses, Selesai, Dibatalkan)
  ⏳ Dikirim
  ⏳ Konfirmasi Pengiriman

Supplier
  ⏳ Sales
  ⏳ Distributor
  ⏳ Order ke Supplier
  ⏳ Promo
  ⏳ Chat
  ⏳ Invoice

Pelanggan
  ⏳ Favorit
  ⏳ Membership
  ⏳ Riwayat
  ⏳ Voucher
  ⏳ Review

Keuangan
  ✅ Pendapatan (via omzet dashboard + cod_settlements)
  ⏳ Pengeluaran
  ⏳ Laba
  ⏳ Piutang
  ⏳ Tempo
  ⏳ Kas

AI
  ⏳ Rekomendasi Restock
  ⏳ Prediksi Penjualan
  ⏳ Produk Lambat
  ⏳ Produk Cepat
  ⏳ Harga Kompetitor
```

### Halaman Konsumen
```
Dashboard
  ✅ Warung Terdekat (radius slider 1-50km)
  ✅ Produk dari warung dalam radius
  ⏳ Promo
  ⏳ Produk Favorit
  ⏳ Order Terakhir
  ⏳ Repeat Order
  ⏳ Langganan

Marketplace
  ✅ Cari (search bar)
  ⏳ Kategori
  ⏳ Flash Sale
  ✅ Warung Terdekat (Haversine)
  ⏳ Diskon
  ⏳ Voucher

Checkout
  ✅ Ringkasan Produk
  ✅ Alamat (alamat_antar)
  ✅ Metode Pengiriman (ambil sendiri / diantar kurir)
  ✅ Metode Pembayaran (COD / transfer)
  ✅ Catatan

Akun
  ⏳ Poin
  ⏳ Membership
  ⏳ Wishlist
  ⏳ Notifikasi
  ⏳ Chat
  ⏳ Review
```

### Halaman Kurir
```
Dashboard
  ✅ Online/Offline Toggle
  ⏳ Pendapatan
  ⏳ Order Masuk
  ⏳ Rating
  ⏳ Saldo

Delivery
  ⏳ Ambil Barang
  ⏳ Antar
  ⏳ Navigasi
  ⏳ OTP
  ⏳ Foto Bukti

Keuangan
  ⏳ Pendapatan
  ⏳ Bonus
  ⏳ Insentif
  ⏳ Withdraw

AI
  ⏳ Jam Ramai
  ⏳ Area Ramai
  ⏳ Prediksi Order
```

### Halaman Sales (Fase 2)
```
Dashboard
  ⏳ Target
  ⏳ Omzet
  ⏳ Warung Aktif
  ⏳ Warung Baru
  ⏳ Warung Tidak Aktif

Warung
  ⏳ Daftar Warung
  ⏳ Lokasi
  ⏳ Status
  ⏳ Visit
  ⏳ Chat
  ⏳ Riwayat

Produk
  ⏳ Promo
  ⏳ Harga
  ⏳ Katalog
  ⏳ Stok Distributor

Order
  ⏳ Order Baru
  ⏳ Approval
  ⏳ Invoice
  ⏳ Pengiriman

AI Sales
  ⏳ Warung Yang Perlu Dikunjungi
  ⏳ Produk Yang Harus Ditawarkan
  ⏳ Prediksi Order Minggu Ini
  ⏳ Rekomendasi Promo
```

---

## 🧠 Control Center — Pusat Kendali Ekosistem

Control Center adalah "otak" Commerce Engine — halaman pusat kendali yang menampilkan widget dan informasi sesuai peran pengguna. Bukan untuk satu jenis pengguna, tetapi menjadi fondasi tampilan yang sama dengan konten berbeda per peran.

### Widget Control Center
| Widget | Deskripsi | Pemakai |
|---|---|---|
| **Activity Feed** | Aliran aktivitas real-time: order baru, stok berubah, pembayaran, pengiriman | Semua |
| **Notifications** | Notifikasi penting: order perlu diproses, stok habis, pembayaran diterima | Semua |
| **Tasks** | Tugas hari ini: order harus diproses (warung), kunjungan sales (sales), pengiriman (kurir) | Semua |
| **Calendar** | Jadwal: pengiriman, restock, kunjungan sales | Semua |
| **Quick Actions** | Tindakan cepat: scan barcode, tambah produk, buat order, hubungi pelanggan | Semua |
| **AI Assistant** | Asisten yang memahami konteks pengguna, memberikan rekomendasi dan insight | Semua |

### Arsitektur Engine-Based (bukan Page-Based)

Daripada membangun per halaman, platform dibangun berdasarkan **Engine** yang melayani banyak aplikasi sekaligus:

| Engine | Digunakan Oleh | Status |
|---|---|---|
| **Identity Engine** | Semua pengguna | ✅ Auth module (Sanctum, register, login, OTP) |
| **Catalog Engine** | Warung, Sales, Konsumen | ✅ Warung module (produk CRUD, Sellable contract) |
| **Inventory Engine** | Warung, Distributor | ✅ Core module (ketersediaan_cache + movements) |
| **Order Engine** | Warung, Konsumen, Sales | ✅ Order module (state machine, checkout) |
| **Logistics Engine** | Kurir, Warung | ⏳ Kurir module (klaim atomik siap, UI siap) |
| **Payment Engine** | Semua pengguna | ✅ Payment module (cod_settlements, semi-POS) |
| **CRM Engine** | Warung, Konsumen | ⏳ Fase 1.5+ |
| **Analytics Engine** | Warung, Sales, Admin | ⏳ Fase 3 |
| **AI Engine** | Semua pengguna | ⏳ Fase 3+ |

**Keunggulan arsitektur Engine-Based:**
- **Satu engine, banyak aplikasi** — Order Engine yang sama dipakai Warung (order masuk), Konsumen (checkout), Sales (approval), Kurir (klaim)
- **Vertikal baru = aturan bisnis + UI spesifik** — ApotekOS atau BakeryOS tidak perlu bangun ulang sistem dari nol, cukup ganti aturan bisnis dan antarmuka
- **Konsisten dengan arsitektur modular** — setiap Engine = modul Laravel yang berkomunikasi lewat Event & Listener, bukan pemanggilan langsung
- **Widget Control Center** — setiap Engine menyediakan data untuk Activity Feed, Tasks, Calendar, dan AI Assistant sesuai peran

### Status Implementasi Engine (per Sesi 12)
```
Identity Engine    ████████████ ✅ 100%   Auth, register, login, OTP, profil
Catalog Engine     ████████░░░░ ✅ 80%    Produk CRUD, Sellable, harga, satuan
Inventory Engine   ████████░░░░ ✅ 80%    Ketersediaan cache + log, stok real
Order Engine       ████████░░░░ ✅ 80%    State machine, checkout, metode kirim
Logistics Engine   ██░░░░░░░░░░ ⏳ 20%    Model + klaim atomik siap, UI siap
Payment Engine     ██████░░░░░░ ✅ 60%    COD settlement + semi-POS
CRM Engine         ░░░░░░░░░░░░ ⏳ 0%     Belum dimulai (Fase 1.5+)
Analytics Engine   ░░░░░░░░░░░░ ⏳ 0%     Belum dimulai (Fase 3)
AI Engine          ░░░░░░░░░░░░ ⏳ 0%     Belum dimulai (Fase 3+)
```

---

## Open Questions (belum diputuskan, perlu dibahas lanjut)
- [ ] Riset kebutuhan pelaporan pemerintah untuk Apotik (resep obat) dan Toko Pupuk (subsidi by NIK) — belum diriset, baru prinsip arsitektur (`ComplianceReportable`) yang disiapkan, ini Fase 5+ jadi tidak memblokir MVP

## Sudah Diputuskan (arsip singkat, detail lihat last_update.md)
- [x] Scope final Fase 1 MVP: 1 alur transaksi vertikal Warung (Outlet -> Konsumen -> Kurir, COD saja), barcode/transfer/DP/push notif ditunda ke Fase 1.5
- [x] Metode pembayaran MVP: COD saja
- [x] Skema autentikasi: Laravel Sanctum, 1 users + profil per-peran (multi-peran diperbolehkan)
- [x] Platform digeneralisasi jadi multi-vertikal (Warung sebagai vertikal pertama): kontrak `Sellable` untuk produk, kontrak `ComplianceReportable` untuk vertikal teregulasi (Apotik, Toko Pupuk), istilah `Warung` -> `Outlet` di level generik, event `StokBerubah` -> `KetersediaanBerubah`
- [x] Modul teknis final MVP + mapping Event + skema `outlets` + query Haversine (lihat bagian di atas)
- [x] Aturan Tingkatan Warung Biasa vs Grosir: Warung Grosir hanya bisa di-order Warung Biasa (B2B tertutup), naik tier via approval Sales minimal 1x, Order digeneralisasi pakai `buyer_type`+`buyer_id` polymorphic sejak desain awal