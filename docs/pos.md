# Desain POS (Point of Sale) — Warung

## Tujuan
Melayani pembeli yang datang langsung ke lokasi TANPA lewat app (tidak install, tidak pesan duluan). Berbeda dari "Ambil Sendiri" (yang tetap order lewat app, cuma pengambilan di lokasi) — POS murni diinput manual oleh Warung saat transaksi terjadi.

## Prinsip Teknis (wajib dipatuhi saat implementasi)
1. **Tidak ada jalur stok terpisah.** POS memanggil layanan bersama `BuatOrder` yang sama dengan checkout Konsumen — lewat `Sellable::prosesPengurangan()`. Ini mencegah race condition oversell antara penjualan online dan tatap muka (lihat `project.md` bagian Integritas Transaksi).
2. **Transaksi POS langsung `selesai`.** Tidak ada Kurir, tidak ada status menunggu — `OrderDibuat` dan `PembayaranDiterima` dipancarkan bersamaan karena uang tunai diterima seketika.
3. **Boleh dipakai offline (Lapis 3).** Transaksi tersimpan lokal dulu kalau sinyal hilang, disinkron otomatis begitu koneksi kembali — beda dari checkout Konsumen yang wajib online.
4. **`buyer_type = Umum`** — pembeli walk-in tidak punya akun/profil, tidak perlu dan tidak boleh dipaksa didaftarkan.

---

## Alur Layar

### 1. Halaman Utama POS — Grid Produk
```
┌─────────────────────────┐
│ ← Kasir        🔴OFFLINE  │  <- OfflineBanner tampil kalau relevan
│ [Cari produk...]      🔍 │
├─────────────────────────┤
│ ┌────────┐ ┌────────┐   │
│ │ Beras   │ │ Minyak  │   │
│ │ 5kg     │ │ Goreng  │   │
│ │ Rp65rb  │ │ Rp32rb  │   │
│ │ Stok 12 │ │ Stok 0  │   │  <- disabled/abu-abu kalau habis
│ └────────┘ └────────┘   │
│ ┌────────┐ ┌────────┐   │
│ │ Gula    │ │ Telur   │   │
│ │ 1kg     │ │ 1kg     │   │
│ │ Rp16rb  │ │ Rp28rb  │   │
│ │ Stok 20 │ │ Stok 8  │   │
│ └────────┘ └────────┘   │
├─────────────────────────┤
│ Keranjang: 2 item · Rp97rb│
│ [ LIHAT KERANJANG ]        │
└─────────────────────────┘
```
- Tap kartu produk = langsung tambah 1 ke keranjang (tanpa halaman detail terpisah — beda dari alur Konsumen yang perlu lihat detail dulu, karena Warung sudah hafal produknya sendiri)
- Tap & tahan (long-press) = buka stepper qty langsung dari grid, untuk beli banyak tanpa masuk keranjang dulu
- Produk yang sering terjual tampil di atas grid (bukan alfabetis) — prioritas kecepatan transaksi

### 2. Keranjang & Pembayaran
```
┌─────────────────────────┐
│ ← Keranjang                │
├─────────────────────────┤
│ Beras 5kg                  │
│ [− 1 +]  Rp65.000          │
├─────────────────────────┤
│ Gula 1kg                    │
│ [− 2 +]  Rp32.000          │
├─────────────────────────┤
│ Total: Rp97.000             │
├─────────────────────────┤
│  [ BAYAR TUNAI ]            │  <- tombol besar, 1 tap = selesai
└─────────────────────────┘
```
- Default metode bayar = tunai (`tunai_pos`) — sesuai realita transaksi tatap muka. Metode lain (QRIS dst) menyusul kalau dibutuhkan, tidak masuk MVP POS
- Tidak ada input alamat/kontak — sesuai prinsip `buyer_type=Umum`, tidak memaksa data yang tidak relevan

### 3. Konfirmasi & Struk
```
┌─────────────────────────┐
│      ✅ Transaksi Selesai   │
│                            │
│ Rp97.000 diterima          │
│                            │
│ [ Transaksi Baru ]          │
│ [ Bagikan Struk ]           │  <- opsional, belum dirancang detail
└─────────────────────────┘
```
- Setelah konfirmasi, keranjang otomatis kosong, kembali ke Grid Produk — supaya kasir siap untuk pembeli berikutnya tanpa langkah tambahan

### 4. Kondisi Offline
```
┌─────────────────────────┐
│ 🔴 Sedang Offline           │
│ Transaksi tetap tersimpan   │
├─────────────────────────┤
│ ...grid produk sama...     │
├─────────────────────────┤
│ [ BAYAR TUNAI ]             │  <- tetap aktif, TIDAK disabled
└─────────────────────────┘
```
Setelah tekan "BAYAR TUNAI" saat offline:
```
┌─────────────────────────┐
│  ⏳ Tersimpan, menunggu    │
│     sinkron...              │
│                            │
│ [ Transaksi Baru ]           │
└─────────────────────────┘
```
- `SyncIndicator` kecil muncul di badge notifikasi (mis. ikon jam pasir di header) selama masih ada transaksi POS yang belum sinkron — supaya Warung tahu masih ada yang "menggantung", bukan diam-diam hilang
- **Catatan risiko yang perlu Warung pahami:** kalau ada 2 transaksi offline yang kebetulan menghabiskan stok produk yang sama (mis. warung offline lama, produk laris terjual berkali-kali), sinkronisasi bisa saja menampilkan stok minus sesaat setelah online kembali — sistem akan catat sebagai anomali di log `ketersediaan_movements` untuk ditinjau, BUKAN menolak transaksi yang sudah terjadi di dunia nyata (uang sudah diterima, barang sudah diserahkan, transaksi tidak bisa "dibatalkan" begitu saja).

---

## Komponen Baru (tambahan dari `desain_ui.md`)

| Komponen | Fungsi |
|---|---|
| `KasirGridProduk` | Grid produk dengan tap-to-add, prioritas produk sering terjual |
| `KasirKeranjang` | Ringkasan keranjang + tombol bayar besar |
| `KasirKonfirmasi` | Layar sukses + reset ke transaksi baru |

## Pemetaan Folder Livewire
```
resources/views/livewire/
  warung/pos/grid-produk.blade.php
  warung/pos/keranjang.blade.php
  warung/pos/konfirmasi.blade.php
resources/js/offline/
  pos-write-queue.js          <- turunan write-queue.js, khusus transaksi POS
```

---

## Belum Dirancang (lanjutkan sesi berikutnya)
- Fitur "Bagikan Struk" (WhatsApp/print) — baru ide, belum ada alur detail
- Tampilan riwayat transaksi POS harian (rekap kasir, terpisah dari riwayat order online)
- Dukungan metode bayar non-tunai di POS (QRIS dst)
- Penanganan anomali stok minus pasca-sync offline (siapa yang meninjau, bagaimana ditampilkan ke Warung)
