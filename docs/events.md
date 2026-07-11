# Event Registry — Platform Commerce Lokal

Registry pusat semua Event yang dipancarkan antar-modul. Wajib diupdate setiap kali ada Event baru ditambahkan, agar tidak ada modul yang jalan sendiri-sendiri tanpa tercatat.

Platform ini generik multi-vertikal (Warung sebagai vertikal pertama; Apotik, Warung Makan, Toko Bangunan, Toko Pupuk menyusul). Event & payload di bawah pakai istilah netral (`outlet`, `sellable`) agar berlaku untuk semua vertikal — bukan spesifik Warung.

## Aturan (lihat detail alasan di project.md)
1. Additive-only — field lama tidak boleh dihapus/diubah
2. Payload = DTO eksplisit, bukan Eloquent Model utuh
3. Bawa ID, bukan objek relasi bersarang
4. Setiap Event baru WAJIB ditambahkan ke tabel di bawah sebelum dipakai
5. Payload tidak boleh berasumsi bentuk produk spesifik satu vertikal — pakai `sellable_type` + `sellable_id` (polymorphic), bukan `produk_id` yang mengasumsikan tabel produk tunggal
6. Harga DAN nama item dalam payload event (`harga_satuan`, `nama_produk`, dst) selalu berupa nilai SNAPSHOT hasil resolusi `Sellable::getHarga(qty)`/`getNama()` pada saat transaksi terjadi — bukan referensi yang di-JOIN ulang oleh Listener/laporan nanti. Ini penting untuk vertikal berharga bertingkat (mis. Warung Grosir) DAN supaya riwayat order tidak berubah kalau nama/harga produk diedit belakangan (ditemukan sebagai bug nyata: laporan Top Produk gagal karena kolom nama produk tidak pernah disnapshot — lihat project.md).

---

## Daftar Event Aktif

### `OrderDibuat`
- **Dipancarkan oleh:** Modul Order (lewat layanan bersama `BuatOrder` — dipakai baik checkout Konsumen maupun POS Warung, lihat project.md)
- **Kapan:** saat checkout diselesaikan (Konsumen->Warung Biasa online/ambil-sendiri di MVP, POS walk-in di Warung, atau Warung Biasa->Warung Grosir untuk B2B mulai Fase 2)
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | order_id | int | |
  | outlet_id | int | outlet penjual, generik berlaku semua vertikal |
  | buyer_type | string | `Konsumen` \| `Outlet` \| `Umum` (`Umum` khusus POS — pembeli walk-in tanpa akun) |
  | buyer_id | int, nullable | null kalau buyer_type=Umum |
  | jenis_transaksi | string | `online` \| `pos` |
  | metode_pengiriman | string, nullable | `diantar_kurir` \| `ambil_sendiri`, null kalau jenis_transaksi=pos |
  | items | array | `[{sellable_type, sellable_id, nama_produk, qty, harga_satuan}]` — `nama_produk` snapshot, WAJIB diisi saat order dibuat, JANGAN di-JOIN ulang ke tabel produk saat menampilkan riwayat |
  | total_harga | decimal | |
  | metode_pembayaran | string | `cod` \| `transfer` \| `dp` \| `tunai_pos` |
  | dibuat_pada | datetime | |
- **Validasi sebelum dipancarkan:** Order memanggil kontrak `BuyerEligibilityPolicy` milik outlet penjual — kalau outlet punya aturan pembeli khusus (mis. Warung Grosir), request checkout ditolak sebelum event ini dipancarkan.
- **Listener terdaftar (MVP):**
  - `Modules/Kurir` — munculkan order baru ke daftar kurir yang online (HANYA kalau `metode_pengiriman=diantar_kurir`; POS dan ambil-sendiri tidak memicu ini)
  - `Modules/Warung` — trigger `prosesPengurangan()` pada tiap item (implementasi `Sellable`), yang akan memancarkan `KetersediaanBerubah`

### `KetersediaanBerubah`
*(sebelumnya bernama `StokBerubah` — direname karena "stok" tidak berlaku universal; sebagian vertikal seperti Warung Makan pakai status tersedia/habis, bukan angka)*
- **Dipancarkan oleh:** Modul vertikal (implementasi `Sellable`) / Order
- **Kapan:** setiap kali ketersediaan item berubah (penjualan, restock, koreksi manual, toggle tersedia/habis)
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | sellable_type | string | model vertikal, mis. `Warung\Produk`, `Apotik\Obat` |
  | sellable_id | int | |
  | outlet_id | int | |
  | jumlah_perubahan | int, nullable | untuk vertikal berbasis stok angka; boleh negatif (penjualan) atau positif (restock) |
  | status_tersedia | bool, nullable | untuk vertikal non-stok (mis. Warung Makan: tersedia/habis) |
  | alasan | string | `penjualan` \| `restock` \| `koreksi` \| `toggle_status` |
  | referensi_id | int, nullable | mis. order_id penyebab perubahan |
  | terjadi_pada | datetime | |
- **Catatan:** Event ini adalah SATU-SATUNYA cara ketersediaan berubah. Tidak boleh ada kode yang UPDATE langsung kolom stok/status di tabel produk — nilai akhir = SUM (vertikal stok angka) atau status terakhir (vertikal non-stok) dari seluruh log event ini (lihat strategi resolusi konflik di project.md).
- **Listener terdaftar (MVP):** belum ada — dicatat sebagai log saja. Fase 3 (prediksi stok habis) akan jadi listener pertama.

### `PembayaranDiterima`
- **Dipancarkan oleh:** Modul Payment
- **Kapan:** saat pembayaran (COD/transfer/DP/nanti QRIS) dikonfirmasi diterima. **Untuk POS (`metode_pembayaran=tunai_pos`): dipancarkan BERSAMAAN dengan `OrderDibuat`**, karena uang tunai diterima seketika di kasir — tidak menunggu konfirmasi terpisah seperti alur COD-diantar-Kurir.
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | order_id | int | |
  | metode | string | |
  | jumlah | decimal | |
  | status | string | `lunas` \| `sebagian` (untuk DP) |
  | diterima_pada | datetime | |
- **Listener terdaftar (MVP):**
  - `Modules/Order` — ubah status order jadi `selesai`

### `WarungDisetujuiGrosir`
*(Fase 2 — belum diimplementasikan, dicatat sekarang karena payload sudah disepakati)*
- **Dipancarkan oleh:** Modul Sales
- **Kapan:** saat Sales menyetujui permohonan Warung Biasa naik jadi Warung Grosir (minimal 1 approval)
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | outlet_id | int | Warung yang diajukan naik tier |
  | sales_id | int | FK sales_profiles yang menyetujui |
  | catatan | string, nullable | |
  | terjadi_pada | datetime | |
- **Listener terdaftar:**
  - `Modules/Warung` — update cache `tier` di `warung_detail` jadi `grosir`, catat entri `jenis=disetujui` ke `warung_grosir_approvals`

### `WarungDicabutGrosir`
*(Fase 2 — belum diimplementasikan, pasangan dari WarungDisetujuiGrosir)*
- **Dipancarkan oleh:** Modul Sales (atau Admin)
- **Kapan:** saat status Grosir sebuah warung dicabut (hubungan dengan Sales putus, atau penyalahgunaan)
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | outlet_id | int | |
  | dicabut_oleh_type | string | `Sales` \| `Admin` |
  | dicabut_oleh_id | int | |
  | alasan | string | |
  | terjadi_pada | datetime | |
- **Catatan:** status tier BUKAN hasil SUM seperti stok — nilai `tier` = entri TERBARU (berdasarkan `terjadi_pada`) di antara `WarungDisetujuiGrosir`/`WarungDicabutGrosir`, state terakhir menang.
- **Listener terdaftar:**
  - `Modules/Warung` — update cache `tier` di `warung_detail` jadi `biasa`, catat entri `jenis=dicabut` ke `warung_grosir_approvals` (riwayat approval lama tidak dihapus)

### `OrderDibatalkan`
- **Dipancarkan oleh:** Modul Order
- **Kapan:** pembatalan oleh Konsumen/Warung/Kurir/Admin, atau gagal kirim setelah retry
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | order_id | int | |
  | dibatalkan_oleh_type | string | `Konsumen` \| `Warung` \| `Kurir` \| `Admin` |
  | dibatalkan_oleh_id | int | |
  | alasan | string | |
  | terjadi_pada | datetime | |
- **Listener terdaftar (MVP):**
  - `Modules/Warung` — kembalikan ketersediaan lewat entri kompensasi baru di `ketersediaan_movements` (jumlah_perubahan positif, alasan=`pembatalan`), BUKAN edit/hapus entri lama
  - `Modules/Order` — set status jadi `dibatalkan`

---

## Event untuk vertikal masa depan (belum diimplementasikan, dicatat sebagai rencana)

### `LaporanCompliancePerluDibuat`
*(rencana — dipicu secara internal oleh Modul Compliance, bukan dipancarkan modul lain)*
- **Dipancarkan oleh:** Modul Compliance, sebagai reaksi terhadap `OrderDibuat`/`PembayaranDiterima` yang melibatkan item `ComplianceReportable` (Apotik, Toko Pupuk)
- **Kapan:** saat order/pembayaran melibatkan item yang wajib dilaporkan ke pemerintah
- **Payload:** belum dirancang detail — menunggu riset kebutuhan pelaporan resep obat (Apotik) dan pupuk bersubsidi by NIK (Toko Pupuk)
- **Listener terdaftar:** (belum ada)

---

## Template untuk Event baru

```
### `NamaEvent`
- **Dipancarkan oleh:** Modul X
- **Kapan:** [kondisi pemicu]
- **Payload:**
  | Field | Tipe | Keterangan |
  |---|---|---|
  | ... | ... | ... |
- **Listener terdaftar:** [Modul Y - alasan]
```