# Desain UI — Platform Commerce Lokal (PWA)

## Landasan Desain
Dipakai oleh 3 jenis orang di lapangan, bukan pengguna kantoran:
- **Pemilik Warung** — sering bukan digital native, butuh tampilan yang jelas dan besar, bukan dashboard rumit.
- **Kurir** — dipakai sambil jalan/naik motor, sering di luar ruangan (kontras tinggi wajib), tangan mungkin masih pakai sarung tangan (target sentuh besar).
- **Konsumen** — pengguna umum, ekspektasi seperti app belanja online biasa.

Desain diarahkan ke nuansa **warung/pasar lokal** — bukan biru korporat generik. Elemen signature: **sistem chip status** yang konsisten di 3 app (status order, status ketersediaan, badge tier warung) — dipilih karena ini bukan hiasan, tapi representasi visual langsung dari state machine Order dan tier Warung yang sudah kita desain di backend.

---

## Token Desain

### Warna
| Token | Hex | Dipakai untuk |
|---|---|---|
| `warna-dasar` | `#FAF6ED` | Latar belakang utama, hangat bukan putih steril |
| `warna-teks` | `#2B2622` | Teks utama, charcoal hangat |
| `warna-aksen-utama` | `#E8A23C` | Aksi utama (kuning kunyit/atap warung) — tombol CTA, highlight |
| `warna-aksen-kedua` | `#1F5C4F` | Status positif/selesai (hijau daun/sayur segar) |
| `warna-peringatan` | `#C4482E` | Status urgent/gagal/dibatalkan (merah-oranye, beda dari terracotta pucat yang umum dipakai AI) |
| `warna-netral-garis` | `#DCD3C2` | Border, divider |

### Tipografi
| Peran | Font | Alasan |
|---|---|---|
| Judul/Angka besar (harga, status) | **Space Grotesk** (bold) | Tegas seperti tulisan papan harga warung, bukan serif elegan yang tidak relevan |
| Body/teks umum | **Plus Jakarta Sans** | Terbaca jelas di layar kecil, familiar untuk konten Bahasa Indonesia |
| Angka/data (harga, stok, waktu) | **Space Grotesk** tabular-nums | Supaya kolom harga & stok rata angka, gampang dipindai mata cepat |

### Sistem Chip Status (elemen signature, dipakai konsisten di 3 app)
Bentuk pil, warna mengikuti makna, bukan dekorasi:
```
[● Dibuat]       abu-abu netral
[● Diambil Kurir] kuning aksen-utama
[● Diantar]       biru-teal muda
[● Selesai]       hijau aksen-kedua
[● Dibatalkan]    merah peringatan
[● Grosir]        badge emas kecil di nama warung (bukan warna status, tapi identitas tier)
```

---

## Komponen Bersama (dipakai lintas 3 role)

| Komponen | Fungsi | Catatan |
|---|---|---|
| `StatusChip` | Tampilkan status order/ketersediaan/tier | Satu komponen Livewire, terima param `jenis` + `nilai` |
| `OfflineBanner` | Pita di atas layar saat tidak ada koneksi | Lapis 1 arsitektur offline — selalu tampil kalau `navigator.onLine === false` |
| `SyncIndicator` | Ikon kecil di aksi yang masih antri sinkron | Lapis 3 — dipasang di tombol yang barusan diklik offline (mis. "Menunggu sinkron...") |
| `KartuOutlet` / `KartuProduk` | Card dengan foto, nama, harga/jarak | Dipakai di pencarian Konsumen & daftar produk Warung |
| `EmptyState` | Ilustrasi + teks + aksi saat data kosong | Selalu punya CTA, bukan cuma "tidak ada data" |
| `BottomNav` | Navigasi utama per role, 3–4 item | Beda isi per role, style sama |

---

## Halaman per Role

### 1. App Konsumen

**Beranda / Cari Outlet**
```
┌─────────────────────────┐
│ [Cari warung...]      🔍│
├─────────────────────────┤
│ Warung dalam 1km         │
│ ┌───────────────────┐   │
│ │ 🏪 Warung Bu Siti   │   │
│ │ 350m · Buka          │   │
│ └───────────────────┘   │
│ ┌───────────────────┐   │
│ │ 🏪 Toko Pak Jono    │   │
│ │ 700m · Buka          │   │
│ └───────────────────┘   │
├─────────────────────────┤
│ 🏠 Beranda  📦 Order  👤 │
└─────────────────────────┘
```
- Kalau offline: `OfflineBanner` tampil, daftar outlet pakai snapshot terakhir (Lapis 2), badge "data terakhir dilihat jam 14:03"
- Empty state: "Belum ada warung terdaftar di radius ini" + tombol perluas radius (kalau fitur itu ada nanti)

**Detail Outlet & Keranjang**
```
┌─────────────────────────┐
│ 🏪 Warung Bu Siti         │
│ ⭐ terverifikasi          │
├─────────────────────────┤
│ Beras 5kg      Rp65.000  │
│ [− 1 +]  [+ Keranjang]   │
├─────────────────────────┤
│ Minyak Goreng  Rp32.000  │
│ [Habis]  (disabled)      │
├─────────────────────────┤
│ [Lihat Keranjang (2)] 🛒 │
└─────────────────────────┘
```
- Produk habis: tombol disabled + `StatusChip` "Habis", bukan disembunyikan (transparansi ketersediaan)

**Checkout**
```
┌─────────────────────────┐
│ Alamat Antar              │
│ [Jl. Melati No.5 ...]     │
├─────────────────────────┤
│ Metode Bayar               │
│ (•) COD (Bayar di tempat) │
├─────────────────────────┤
│ Total: Rp97.000            │
│ [Buat Pesanan]              │
└─────────────────────────┘
```
- WAJIB online (sesuai keputusan `versi_pwa.md`) — kalau offline, tombol disabled + pesan "Checkout butuh koneksi internet"

**Riwayat & Detail Order**
```
┌─────────────────────────┐
│ Pesanan #1234              │
│ [● Diantar]                │
│                             │
│ ○ Dibuat        14:02      │
│ ● Diambil Kurir 14:10      │
│ ● Diantar       14:15      │
│ ○ Selesai                  │
├─────────────────────────┤
│ Kurir: Budi · 🏍 B 1234 XX  │
└─────────────────────────┘
```
- Timeline vertikal = representasi langsung state machine Order, titik terisi = status terlewati

**Laporan Konsumen** *(didokumentasikan retroaktif — dibangun langsung di implementasi, belum ada di desain awal)*
```
┌─────────────────────────┐
│ Laporan Belanja    [Bulan▾]│
├─────────────────────────┤
│ Total Order: 12             │
│ Total Belanja: Rp1.240.000  │
├─────────────────────────┤
│ 📊 Grafik Mingguan          │
│ (bar chart pengeluaran)     │
├─────────────────────────┤
│ Top 5 Produk Favorit        │
│ 1. Beras 5kg (x8)            │
│ 2. Minyak Goreng (x5)        │
├─────────────────────────┤
│ 10 Order Terakhir            │
│ (list ringkas)                │
└─────────────────────────┘
```
- Diakses lewat tombol "Profil" di bottom nav Konsumen
- Data murni turunan dari tabel `orders`/`order_items` yang sudah ada — tidak perlu tabel baru

---

### 2. App Warung

**Dashboard**
```
┌─────────────────────────┐
│ Warung Bu Siti  [● Biasa] │
├─────────────────────────┤
│ 🔔 3 Order Baru            │
│ ⚠️ 2 Produk Hampir Habis   │
├─────────────────────────┤
│ Order Hari Ini: 12          │
│ Omzet Hari Ini: Rp850rb     │
├─────────────────────────┤
│ 🏠  📦Order  (POS)  🛍Produk  👤 │
└─────────────────────────┘
```
- Badge tier (`Biasa`/`Grosir`) selalu terlihat di header — pengingat visual status akun
- **Prinsip (dari `aturan_bisnis.md`, direkonsiliasi):** Dashboard Warung BUKAN sekadar statistik pasif — tapi **daftar pekerjaan yang harus segera dilakukan hari ini** (order baru butuh respon, stok yang perlu direstock, dst). Wireframe di atas sudah mengarah ke sini (notifikasi di atas statistik), prinsip ini jadi acuan eksplisit saat menambah widget baru ke dashboard nanti.
- `(POS)` = tombol bulat menonjol di tengah bottom nav, beda ukuran/style dari 4 item lain — akses cepat 1 tap ke layar kasir (desain lengkap di `pos.md`)

**Order Masuk**
```
┌─────────────────────────┐
│ Order #1234    [● Dibuat] │
│ 🏍 Diantar Kurir           │
│ 2x Beras, 1x Minyak       │
│ Rp97.000                  │
│ [Konfirmasi] [Tolak]      │
├─────────────────────────┤
│ Order #1235    [● Dibuat] │
│ 🚶 Ambil Sendiri           │
│ 1x Gula 1kg                │
│ Rp16.000                  │
│ [Konfirmasi] [Tolak]      │
└─────────────────────────┘
```
- Badge metode pengiriman (🏍 Diantar Kurir / 🚶 Ambil Sendiri) membedakan order yang perlu diteruskan ke Kurir vs yang tinggal ditunggu diambil langsung — keduanya tetap muncul di layar yang sama, transaksi POS TIDAK muncul di sini (POS langsung selesai, tidak ada tahap "menunggu")

**Kelola Produk**
```
┌─────────────────────────┐
│ [+ Tambah Produk]          │
├─────────────────────────┤
│ Beras 5kg      Rp65.000    │
│ Stok: 12   [●Tersedia ⇄]   │
├─────────────────────────┤
│ Minyak Goreng  Rp32.000    │
│ Stok: 0    [●Habis ⇄]      │
└─────────────────────────┘
```
- Toggle ketersediaan = aksi kritis Lapis 3 (offline queue). Kalau diklik offline, langsung tampil `SyncIndicator` "Tersimpan, menunggu sinkron"

---

### 3. App Kurir

**Status Utama (halaman pembuka, bukan tersembunyi di menu)**
```
┌─────────────────────────┐
│                            │
│      [● OFFLINE]           │
│   ( Ketuk untuk Online )   │
│                            │
├─────────────────────────┤
│ 🏠  📋Order  📜Riwayat  👤  │
└─────────────────────────┘
```
- Toggle online/offline jadi elemen PALING besar di layar — ini keputusan yang dipakai kurir tiap hari, bukan dikubur di setting

**Order Tersedia**
```
┌─────────────────────────┐
│ Order #1234                │
│ Warung Bu Siti → 800m      │
│ Ongkir: Rp8.000             │
│ [ AMBIL ORDER ]              │  <- tombol besar, sekali tap
└─────────────────────────┘
```
- Kalau order sudah diklaim kurir lain: tombol berubah jadi "Sudah diambil" (bukan hilang tiba-tiba, supaya kurir tahu kenapa)

**Order Aktif**
```
┌─────────────────────────┐
│ [● Diambil Kurir]          │
│ 📍 Antar ke: Jl. Melati 5    │
│ 📞 Hubungi Konsumen          │
├─────────────────────────┤
│ [ TANDAI: SEDANG DIANTAR ]  │
└─────────────────────────┘
```
- Tombol update status = aksi kritis Lapis 3. Offline tetap bisa ditekan, `SyncIndicator` muncul, tersinkron otomatis begitu sinyal kembali

---

## Pemetaan ke Struktur Folder Livewire (dari `versi_pwa.md`)
```
resources/views/livewire/
  konsumen/beranda.blade.php
  konsumen/detail-outlet.blade.php
  konsumen/keranjang.blade.php
  konsumen/checkout.blade.php
  konsumen/riwayat-order.blade.php
  konsumen/detail-order.blade.php

  warung/dashboard.blade.php
  warung/order-masuk.blade.php
  warung/kelola-produk.blade.php

  kurir/status.blade.php
  kurir/order-tersedia.blade.php
  kurir/order-aktif.blade.php

  shared/status-chip.blade.php
  shared/offline-banner.blade.php
  shared/sync-indicator.blade.php
  shared/kartu-outlet.blade.php
  shared/empty-state.blade.php
```

---

## Belum Dirancang (lanjutkan sesi berikutnya)
- Halaman Profil/Akun tiap role (edit data, ganti password)
- Halaman verifikasi outlet (upload dokumen lokasi usaha) — terkait `level_verifikasi`
- Tampilan Riwayat Transaksi/Rekonsiliasi COD untuk Kurir
- Desain visual detail (spacing scale, radius, shadow) — token di atas baru warna & tipografi
