# Aturan Bisnis — Desahub Commerce Engine

> **Dokumen ini adalah fondasi seluruh platform.**  
> Engine tidak mengenal "Warung", "Apotek", atau "Laundry".  
> Engine hanya mengenal: Business, User, Product, Inventory, Order, Delivery, Payment, Supplier.  
> Besok Apotek = Business. Laundry = Business. Ini disebut **Business Agnostic Architecture**.

---

## Filosofi Commerce Engine

Jangan bertanya *"Halaman warung memiliki fitur apa?"*  
Tapi bertanya: *"Warung setiap hari melakukan aktivitas apa?"*

Karena WarungOS harus mengikuti kehidupan pemilik warung, bukan memaksa pemilik warung mengikuti aplikasi.

### Daily Journey Warung
```
06.00    Buka Warung
    ↓
        Cek stok
    ↓
        Barang datang
    ↓
        Input stok
    ↓
        Pelanggan datang
    ↓
        Penjualan
    ↓
        Restock
    ↓
        Kurir mengambil barang
    ↓
        Sales datang
    ↓
        Pembelian ke Distributor
    ↓
        Tutup Kas
    ↓
        Laporan Harian
```

Kalau seluruh aktivitas ini bisa dilakukan di aplikasi, maka **WarungOS menjadi Operating System**, bukan sekadar aplikasi.

---

## 8 Commerce Engine

Satu engine melayani banyak aplikasi sekaligus. Vertikal baru (ApotekOS, BakeryOS) tinggal ganti aturan bisnis + UI.

| Engine | Deskripsi | Modul Laravel |
|---|---|---|
| **Identity Engine** | Semua user: Consumer, Merchant, Courier, Sales, Supplier, Admin — semua role, bukan tabel berbeda | `Modules/Auth` |
| **Catalog Engine** | Kategori, Brand, SKU, Barcode, Harga, Foto, Varian, Promo, Bundle, Diskon | `Modules/Warung` |
| **Inventory Engine** | Stock, Warehouse, Movement, Adjustment, Purchase, Transfer, Expiry, Batch, Forecast | `Modules/Core` (ketersediaan) |
| **Order Engine** | Cart, Checkout, Invoice, Payment, Delivery, Refund, Cancel, History | `Modules/Order` |
| **Logistics Engine** | Kurir, GPS, Route, Delivery, Dispatch, Tracking, Radius, ETA, Proof of Delivery | `Modules/Kurir` |
| **Payment Engine** | Cash, COD, QRIS, Transfer, E-Wallet, Tempo, PayLater, Refund | `Modules/Payment` |
| **CRM Engine** | Customer, Membership, Point, Voucher, Coupon, Favorite, Review, Chat | `Modules/CRM` (Fase 1.5+) |
| **Analytics Engine** | Sales, Top Product, Top Customer, Heatmap, Forecast, Dashboard, Insight, AI | `Modules/Analytics` (Fase 3) |

---

## 13 Domain Business Rules untuk Warung

### 1. Dashboard Rules

Dashboard bukan sekadar statistik — **dashboard adalah daftar pekerjaan hari ini.**

```
Hari ini:
✓ 5 Order Baru
✓ 2 Barang Habis
✓ 1 Barang Expired
✓ Sales ABC datang jam 13.00
✓ 3 Barang harus Restock
✓ Kurir sedang mengantar
✓ Pendapatan Hari Ini
✓ Piutang Jatuh Tempo
```

**Business Rules:**
- Dashboard selalu menampilkan pekerjaan yang harus segera dilakukan.
- Widget dapat disusun ulang sesuai kebutuhan.
- Dashboard berubah berdasarkan jam operasional.
- Semua data berasal dari transaksi — bukan input manual.

---

### 2. Produk Rules

**Status Produk:** `Draft` → `Aktif` → `Nonaktif` / `Habis` / `Expired`

**Business Rules:**
- Barcode harus unik.
- Nama produk boleh sama.
- Satu produk memiliki banyak foto.
- Harga bisa berubah & memiliki riwayat.
- Produk dapat dijual walaupun stok minus (opsional per outlet).
- Produk bisa memiliki varian (contoh: Indomie — Ayam, Soto, Rendang — satu parent product).

---

### 3. Inventory Rules

**Stok adalah nyawa warung.**

Semua perubahan stok HARUS tercatat. Tidak boleh ada UPDATE langsung.

```
+20   Barang Masuk
-2    Penjualan
-1    Rusak
+10   Retur
```

Semua menjadi **Stock Movement** → `SUM` = Current Stock.

**Business Rules:**
- Append-only: tidak pernah overwrite angka stok langsung.
- Setiap pergerakan tercatat: `sellable_type`, `sellable_id`, `jumlah_perubahan`, `alasan`, `referensi_id`, `waktu`.
- Offline: antrian write queue → sync saat online, resolusi konflik pakai log.
- Race condition: UPDATE atomik bergerbang (`WHERE qty >= jumlah`).
- Ketersediaan = `SUM(jumlah_perubahan)` dari tabel `ketersediaan_movements`.

---

### 4. Supplier Rules

```
Supplier
  ↓
  Sales (perwakilan)
    ↓
    Produk + Promo
      ↓
      Warung (outlet)
```

**Business Rules:**
- Supplier memiliki banyak sales.
- Sales menangani banyak warung.
- Warung dapat mengikuti banyak sales/supplier.
- Supplier dapat menentukan area distribusi.
- Supplier dapat mengatur jadwal kunjungan sales.

---

### 5. Order Rules

Order mempunyai lifecycle (state machine):

```
Draft
  ↓
Menunggu Pembayaran
  ↓
Diproses
  ↓
Disiapkan
  ↓
Menunggu Kurir
  ↓
Dikirim
  ↓
Selesai
  ↓
Retur (jika ada)
```

**Business Rules:**
- Tidak boleh lompat status — transisi invalid ditolak aplikasi.
- Jalur pendek: `dibuat → selesai` untuk **ambil sendiri**.
- `dibuat → diambil_kurir → diantar → selesai` untuk **diantar kurir**.
- Checkout wajib online (validasi stok real-time).
- Buyer polymorphic: `Konsumen` atau `Outlet` (B2B Warung Biasa → Warung Grosir di Fase 2).
- Setiap item di order = referensi polymorphic `sellable_type` + `sellable_id` — Order tidak tahu isi produk.
- Harga yang tercatat = hasil resolusi `Sellable::getHarga(qty)` saat transaksi.

---

### 6. Delivery Rules

**Business Rules:**
- Radius 1km default — di luar radius tidak bisa delivery.
- Kurir hanya melihat order di radiusnya (GPS-based filter).
- Klaim order oleh kurir = UPDATE atomik (`WHERE kurir_id IS NULL`) — bukan check-then-act.
- Opsi pengiriman: **Diantar Kurir** atau **Ambil Sendiri**.
- Alamat antar wajib jika diantar kurir.
- GPS wajib untuk pencarian radius — outlet tanpa GPS tidak muncul.

---

### 7. Customer Rules

Setiap pelanggan memiliki:
- Favorite Product
- Favorite Warung
- Membership
- Point
- Voucher
- History

Label pelanggan: **Langganan**, **VIP**, **Piutang**, **Blacklist**.

**Business Rules:**
- Poin otomatis bertambah setiap transaksi selesai.
- Voucher hanya berlaku di outlet tertentu (atau semua, tergantung konfigurasi).
- Riwayat pembelian tidak bisa dihapus.
- Pelanggan bisa memberikan review + rating.

---

### 8. Finance Rules

Bukan ERP rumit. Cukup:
- Kas Masuk
- Kas Keluar
- Piutang
- Hutang
- Laba Kotor
- Biaya
- Saldo

**Business Rules:**
- Setiap transaksi otomatis menghasilkan jurnal sederhana:
  - Penjualan → Kas Bertambah → Stok Berkurang
- Semua laporan berasal dari transaksi — tidak ada input manual.
- COD: dicatat di `cod_settlements` (append-only).
- Settlements punya status: `belum_disetor` / `sudah_disetor`.
- Pencatatan oleh: `warung` / `kurir` / `admin`.

---

### 9. Employee Rules

Warung juga punya pegawai dengan role:
- Owner
- Kasir
- Gudang
- Admin
- Kurir Internal

**Business Rules:**
- Hak akses berbeda per role.
- Owner bisa melihat semua laporan.
- Kasir hanya bisa transaksi penjualan.
- Gudang hanya bisa kelola stok.

---

### 10. Report Rules

Semua laporan berasal dari transaksi — **tidak ada input manual.**

| Laporan | Sumber Data |
|---|---|
| Top Product | `orders` + `order_items` |
| Top Customer | `orders` grouped by `buyer_id` |
| Omzet | `orders` SUM `total_harga` (status `selesai`) |
| Stok Tersisa | `ketersediaan_movements` SUM |
| Margin | `warung_produk.harga` - `warung_produk.harga_beli` |

---

### 11. AI Rules (Business Advisor)

AI bukan chatbot — AI adalah **Business Advisor** yang memberikan rekomendasi berdasarkan data operasional.

Contoh:
> *"Pak, hari ini stok minyak tinggal 2. Biasanya stok habis besok. Mau dibuatkan order ke supplier?"*

**Jenis AI:**
- **Demand Intelligence** — jam tersibuk, hari paling ramai, produk naik/turun.
- **Inventory Intelligence** — risiko habis 3 hari, produk mengendap, nilai stok mati.
- **Customer Intelligence** — pelanggan loyal, yang mulai jarang belanja, rekomendasi voucher.
- **Supplier Intelligence** — supplier harga terbaik, pengiriman tercepat, promo paling menguntungkan.
- **Profit Intelligence** — margin per produk, kategori paling untung, produk ramai margin rendah.

---

### 12. Notification Rules

Warung mendapat notifikasi jika:
| Event | Notifikasi |
|---|---|
| Stok habis | 🔔 "Stok [produk] habis. Restock?" |
| Order masuk | 🔔 "Order baru #1234 dari [konsumen]" |
| Kurir datang | 🔔 "Kurir [nama] tiba untuk ambil order #1234" |
| Pembayaran diterima | 🔔 "Pembayaran #1234 diterima" |
| Sales datang | 🔔 "Sales [nama] terjadwal hari ini jam 13:00" |
| Promo supplier | 🔔 "Supplier [nama] memberi promo [nama]" |
| Harga distributor berubah | 🔔 "Harga [produk] dari distributor naik/turun" |
| Review pelanggan | ⭐ "Pelanggan memberi rating [bintang]" |
| Piutang jatuh tempo | 🔔 "Piutang [nama] jatuh tempo hari ini" |
| Barang mendekati expired | 🔔 "[produk] akan expired dalam 3 hari" |

---

### 13. Warung Intelligence

Ini bukan laporan biasa. Warung Intelligence menggabungkan seluruh data operasional untuk menghasilkan rekomendasi.

| Kategori | Insight |
|---|---|
| **Demand Intelligence** | Produk yang penjualannya naik/turun, jam tersibuk, hari paling ramai |
| **Inventory Intelligence** | Produk berisiko habis dalam 3 hari, terlalu lama tidak terjual, stok mengendap |
| **Customer Intelligence** | Pelanggan paling loyal, mulai jarang belanja, rekomendasi voucher retensi |
| **Supplier Intelligence** | Supplier harga terbaik, pengiriman tercepat, riwayat promo menguntungkan |
| **Profit Intelligence** | Margin per produk, kategori paling untung, produk ramai margin rendah |

---

## Prinsip Utama Setiap Modul

Setiap modul/halaman di WarungOS harus menjawab 3 pertanyaan:

1. **Apa yang terjadi?** — Data operasional: order, stok, pembayaran, pengiriman
2. **Mengapa itu terjadi?** — Analitik: tren, pola, penyebab
3. **Apa yang sebaiknya dilakukan?** — Rekomendasi AI dan tindakan cepat

Jika setiap halaman mengikuti pola tersebut, aplikasi tidak hanya menjadi alat pencatatan, tetapi **benar-benar menjadi asisten operasional yang membantu pemilik warung mengambil keputusan setiap hari.**

---

## Penerapan Aturan Bisnis per Role

Aturan bisnis di atas bisa diterapkan ke **3 role sekaligus** karena setiap domain melibatkan banyak peran:

### Warung (Merchant)

| Domain | Penerapan |
|---|---|
| **Dashboard Rules** | Dashboard adalah daftar pekerjaan hari ini: order baru, stok habis, kurir datang, sales datang, pendapatan |
| **Produk Rules** | Kelola produk (CRUD), status (Draft/Aktif/Habis/Expired), barcode, varian, riwayat harga |
| **Inventory Rules** | Append-only stock movement, restock, penjualan otomatis kurangi stok |
| **Supplier Rules** | Supplier → Sales → Warung: hierarki supply chain |
| **Order Rules** | Terima order dari Konsumen, konfirmasi, tolak, selesaikan (ambil sendiri) |
| **Delivery Rules** | Lihat status kurir, alamat antar konsumen |
| **Customer Rules** | Label pelanggan (Langganan/VIP/Piutang), riwayat pembelian, review |
| **Finance Rules** | Kas, omzet, piutang, COD settlements, semi-POS |
| **Employee Rules** | Multi-role: Owner, Kasir, Gudang — akses berbeda |
| **Report Rules** | Top product, top customer, omzet, margin |
| **Notification Rules** | 10 trigger notifikasi: stok habis, order masuk, kurir datang, pembayaran diterima, dll |
| **AI Rules** | Rekomendasi restock, prediksi penjualan, deteksi produk lambat/cepat |
| **Warung Intelligence** | Demand, Inventory, Customer, Supplier, Profit intelligence |

### Konsumen

| Domain | Penerapan |
|---|---|
| **Dashboard Rules** | Dashboard menampilkan warung terdekat (radius), produk, promo, order terakhir |
| **Produk Rules** | Lihat katalog produk dari warung dalam radius |
| **Order Rules** | Checkout: pilih produk → metode pengiriman (ambil sendiri/diantar) → metode bayar (COD/transfer) |
| **Delivery Rules** | Alamat antar wajib jika diantar kurir, lihat status pengiriman |
| **Customer Rules** | Semua aturan customer berlaku: membership, poin, voucher, riwayat, review |
| **Finance Rules** | Lihat riwayat pembayaran, status COD |
| **Notification Rules** | Notifikasi: order dikonfirmasi, kurir dalam perjalanan, pembayaran diterima |
| **AI Rules** | Rekomendasi produk berdasarkan riwayat belanja, repeat order |

### Kurir

| Domain | Penerapan |
|---|---|
| **Dashboard Rules** | Dashboard: status online/offline, order tersedia, order aktif, pendapatan hari ini |
| **Order Rules** | Klaim order (atomik), update status (diambil → diantar → selesai) |
| **Delivery Rules** | Semua aturan delivery berlaku: radius, GPS, klaim atomik, alamat antar |
| **Finance Rules** | COD settlement: kurir catat uang diterima, status setor |
| **Notification Rules** | Notifikasi: order baru tersedia, alamat antar, pembayaran diterima |
| **AI Rules** | Prediksi jam ramai, area ramai, estimasi pendapatan |

---

## Status Implementasi per Domain (per Sesi 12)

| Domain | Status | Coverage |
|---|---|---|
| 01 Dashboard | ✅ Terimplementasi | Omzet, Order, Stok Tipis |
| 02 Produk | ✅ Terimplementasi | CRUD, harga jual+beli, satuan, margin |
| 03 Inventory | ✅ Terimplementasi | Ketersediaan cache + movements, append-only |
| 04 Sales | ⏳ Fase 2 | — |
| 05 Supplier | ⏳ Fase 2 | — |
| 06 Order | ✅ Terimplementasi | State machine, checkout, metode kirim & bayar |
| 07 Delivery | 🔧 Sebagian | Radius Haversine, UI Kurir siap, klaim atomik siap |
| 08 Customer | ⏳ Fase 1.5+ | — |
| 09 Finance | ✅ Terimplementasi | COD settlements, semi-POS, omzet dashboard |
| 10 Employee | ⏳ Fase 1.5+ | — |
| 11 Report | 🔧 Sebagian | Dashboard ringkasan, cod_settlements |
| 12 AI | ⏳ Fase 3 | — |
| 13 Notification | ⏳ Fase 1.5+ | — |

---

## Implementasi Engine (Mapping ke Modul Laravel)

| Engine | Modul Laravel | Tabel Utama | Status |
|---|---|---|---|
| Identity Engine | `Modules/Auth` | `users`, `*_profiles` | ✅ |
| Catalog Engine | `Modules/Warung` | `warung_produk` | ✅ |
| Inventory Engine | `Modules/Core` | `ketersediaan_cache`, `ketersediaan_movements` | ✅ |
| Order Engine | `Modules/Order` | `orders`, `order_items` | ✅ |
| Logistics Engine | `Modules/Kurir` | `kurir_profiles` | 🔧 |
| Payment Engine | `Modules/Payment` | `cod_settlements` | ✅ |
| CRM Engine | (belum) | — | ⏳ |
| Analytics Engine | (belum) | — | ⏳ |