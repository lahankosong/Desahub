<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Core\app\Traits\HasRounding;
use Modules\Outlet\app\Models\Outlet;
use Modules\Warung\app\Models\Produk;
use Modules\Warung\app\Models\ProdukMaster;
use Modules\Warung\app\Models\HargaHistory;

class ProdukWebController extends Controller
{
    /**
     * Simpan produk baru — POST /warung/kelola-produk
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'nama'       => 'required|string|max:200',
            'harga'      => 'required|numeric|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'satuan'     => 'required|string|max:20',
            'deskripsi'  => 'nullable|string',
            'barcode'    => 'nullable|string|max:50|unique:warung_produk,barcode',
            'foto'       => 'nullable|string|max:500',
            'kategori'   => 'nullable|string|max:100',
            'diskon'     => 'nullable|integer|min:0|max:100',
            'bundle'     => 'nullable|string|max:200',
            'stok'       => 'required|integer|min:0',
        ]);

        // Ambil outlet milik user yang sedang login
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();

        if (! $outlet) {
            return back()->withErrors(['outlet' => 'Anda belum punya outlet. Silakan daftarkan outlet terlebih dahulu.']);
        }

        $produk = Produk::create([
            'outlet_id'  => $outlet->id,
            'nama'       => $valid['nama'],
            'harga'      => $valid['harga'],
            'harga_beli' => $valid['harga_beli'] ?? null,
            'satuan'     => $valid['satuan'],
            'deskripsi'  => $valid['deskripsi'] ?? null,
            'barcode'    => $valid['barcode'] ?? null,
            'foto'       => $valid['foto'] ?? null,
            'kategori'   => $valid['kategori'] ?? null,
            'diskon'     => $valid['diskon'] ?? 0,
            'bundle'     => $valid['bundle'] ?? null,
        ]);

        // Set stok awal via ketersediaan log
        $qty = $valid['stok'];
        HasKetersediaanLog::tambahCache(Produk::class, $produk->id, $qty);
        HasKetersediaanLog::catatPergerakan(
            Produk::class, $produk->id, $outlet->id,
            $qty, 'restock', null
        );

        return redirect()->route('warung.kelola-produk')->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Update produk — PUT /warung/kelola-produk/{id}
     */
    public function update(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $valid = $request->validate([
            'nama'       => 'required|string|max:200',
            'harga'      => 'required|numeric|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'satuan'     => 'required|string|max:20',
            'deskripsi'  => 'nullable|string',
            'barcode'    => 'nullable|string|max:50|unique:warung_produk,barcode,' . $id,
            'foto'       => 'nullable|string|max:500',
            'kategori'   => 'nullable|string|max:100',
            'stok'       => 'nullable|integer|min:0',
        ]);

        $produk->update($valid);

        // Update stok jika field stok dikirim
        if ($request->has('stok')) {
            $stokBaru = (int) $valid['stok'];
            $stokLama = HasKetersediaanLog::getKetersediaanCache(Produk::class, $produk->id);
            $selisih = $stokBaru - $stokLama;

            if ($selisih !== 0) {
                HasKetersediaanLog::tambahCache(Produk::class, $produk->id, $selisih);
                HasKetersediaanLog::catatPergerakan(
                    Produk::class, $produk->id, $produk->outlet_id,
                    $selisih, 'koreksi', null
                );
            }
        }

        return redirect()->route('warung.kelola-produk')->with('success', 'Produk berhasil diupdate.');
    }

    /**
     * Toggle ketersediaan via AJAX — POST /warung/kelola-produk/{id}/toggle
     */
    public function toggle(Request $request, $id)
    {
        return response()->json(['message' => 'Toggle berhasil']);
    }

    /**
     * Restock produk — POST /warung/kelola-produk/{id}/restock
     * 
     * ════════════════════════════════════════════════════════════════
     * WEIGHTED AVERAGE COST (AVCO)
     * ════════════════════════════════════════════════════════════════
     * Saat restok dengan harga_beli_baru, harga_beli produk dihitung
     * ulang dengan rumus rata-rata tertimbang:
     *
     *   avco = ((stok_lama × harga_beli_lama) + (qty_baru × harga_beli_baru))
     *          ─────────────────────────────────────────────────────────────
     *                           (stok_lama + qty_baru)
     *
     * Contoh:
     *   Stok 2 × harga_beli 5.000 = 10.000
     *   Restok 100 × harga_beli 5.125 = 512.500
     *   AVCO = (10.000 + 512.500) / (2 + 100) = 5.122,55
     */
    public function restock(Request $request, $id)
    {
        $valid = $request->validate([
            'qty'             => 'required|integer|min:1',
            'harga_beli_baru' => 'nullable|numeric|min:0',
        ]);

        $produk = Produk::findOrFail($id);

        // Hitung AVCO jika harga_beli_baru dikirim
        if ($request->has('harga_beli_baru') && $valid['harga_beli_baru'] !== null) {
            $stokLama    = HasKetersediaanLog::getKetersediaanCache(Produk::class, $produk->id);
            $hargaBeliLama = (float) ($produk->harga_beli ?? 0);
            $hargaBeliBaru = (float) $valid['harga_beli_baru'];
            $qtyBaru       = (int) $valid['qty'];

            // Total nilai persediaan lama + baru
            $nilaiLama = $stokLama * $hargaBeliLama;
            $nilaiBaru = $qtyBaru * $hargaBeliBaru;
            $totalStok = $stokLama + $qtyBaru;
            $avco      = $totalStok > 0
                ? ($nilaiLama + $nilaiBaru) / $totalStok
                : $hargaBeliBaru;

            // Update harga_beli produk dengan AVCO (bulatkan 2 desimal)
            $produk->update(['harga_beli' => round($avco, 2)]);
        }

        // Tambah stok cache
        HasKetersediaanLog::tambahCache(Produk::class, $produk->id, $valid['qty']);
        HasKetersediaanLog::catatPergerakan(
            Produk::class, $produk->id, $produk->outlet_id,
            $valid['qty'], 'restock', null
        );

        $msg = "Stok {$produk->nama} bertambah +{$valid['qty']}.";
        if ($request->has('harga_beli_baru') && $valid['harga_beli_baru'] !== null) {
            $msg .= " Harga beli AVCO: Rp" . number_format($produk->fresh()->harga_beli, 0, ',', '.');
        }

        // JSON response untuk AJAX request (dari modal restok)
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route('warung.kelola-produk')->with('success', $msg);
    }

    /**
     * Transaksi POS — POST /warung/pos/transaksi
     * Buat order POS: cash (buyer_type=Umum, selesai) atau tempo (buyer_type=PelangganWarung, piutang).
     */
    public function posTransaksi(Request $request)
    {
        $valid = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|integer|exists:warung_produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'metode' => 'required|in:cash,tempo',
            'pelanggan_id' => 'nullable|integer|exists:pelanggan_warung,id',
            'jatuh_tempo' => 'nullable|date|after_or_equal:today',
            'catatan_tempo' => 'nullable|string|max:500',
        ]);

        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return response()->json(['success' => false, 'message' => 'Outlet tidak ditemukan'], 404);
        }

        // Validasi stok
        foreach ($valid['items'] as $item) {
            $produk = Produk::findOrFail($item['produk_id']);
            $stok = HasKetersediaanLog::getKetersediaanCache(Produk::class, $produk->id);
            if ($stok < $item['qty']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok {$produk->nama} tidak cukup (tersedia: {$stok})"
                ], 422);
            }
        }

        $isTempo = ($valid['metode'] === 'tempo');
        $buyerType = $isTempo ? 'PelangganWarung' : 'Umum';
        $buyerId = $isTempo ? ($valid['pelanggan_id'] ?? null) : null;
        $metodePembayaran = $isTempo ? 'tempo' : 'tunai_pos';
        $status = $isTempo ? 'selesai' : 'selesai'; // POS selalu selesai

        if ($isTempo && !$buyerId) {
            return response()->json(['success' => false, 'message' => 'Pilih pelanggan untuk transaksi tempo'], 422);
        }

        // Buat order POS
        $order = \Modules\Order\app\Models\Order::create([
            'outlet_id' => $outlet->id,
            'buyer_type' => $buyerType,
            'buyer_id' => $buyerId,
            'total_harga' => $valid['total'],
            'metode_pembayaran' => $metodePembayaran,
            'metode_pengiriman' => null,
            'jenis_transaksi' => 'pos',
            'status' => $status,
            'dibuat_pada' => now(),
        ]);

        // Buat order items + kurangi stok
        foreach ($valid['items'] as $item) {
            $produk = Produk::findOrFail($item['produk_id']);

            \Modules\Order\app\Models\OrderItem::create([
                'order_id' => $order->id,
                'sellable_type' => Produk::class,
                'sellable_id' => $produk->id,
                'qty' => $item['qty'],
                'harga_satuan' => $item['harga_satuan'],
                'nama_produk' => $produk->nama,
            ]);

            // Kurangi stok atomik
            HasKetersediaanLog::tambahCache(Produk::class, $produk->id, -$item['qty']);
            HasKetersediaanLog::catatPergerakan(
                Produk::class, $produk->id, $outlet->id,
                -$item['qty'], 'penjualan', $order->id
            );
        }

        // Jika tempo, buat piutang
        $piutang = null;
        if ($isTempo) {
            $jatuhTempo = $valid['jatuh_tempo'] ?? now()->addDays(7)->toDateString();
            $piutang = \Modules\Warung\app\Models\Piutang::create([
                'outlet_id' => $outlet->id,
                'pelanggan_warung_id' => $buyerId,
                'order_id' => $order->id,
                'jumlah' => $valid['total'],
                'terbayar' => 0,
                'jatuh_tempo' => $jatuhTempo,
                'status' => 'aktif',
                'catatan' => $valid['catatan_tempo'] ?? null,
            ]);
        }

        // Emit events
        \Modules\Order\app\Events\OrderDibuat::dispatch($order);
        \Modules\Payment\app\Events\PembayaranDiterima::dispatch($order, $metodePembayaran, $valid['total'], $isTempo ? 'tempo' : 'lunas');

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'piutang' => $piutang,
            'metode' => $valid['metode'],
        ]);
    }

    /**
     * Lookup produk by barcode — GET /warung/produk/barcode/{barcode}
     * Cari di database lokal, fallback ke Open Food Facts API (gratis).
     */
    public function lookupByBarcode($barcode)
    {
        // 1. Cari di database lokal
        $produk = Produk::where('barcode', $barcode)->first();
        if ($produk) {
            return response()->json([
                'found'      => true,
                'source'     => 'local',
                'id'         => $produk->id,
                'nama'       => $produk->nama,
                'deskripsi'  => $produk->deskripsi,
                'harga'      => $produk->harga,
                'harga_beli' => $produk->harga_beli,
                'satuan'     => $produk->satuan,
            ]);
        }

        // 2. Fallback: Open Food Facts (gratis, cakupan internasional)
        try {
            $url = "https://world.openfoodfacts.org/api/v0/product/{$barcode}.json";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_USER_AGENT     => 'Derum/1.0',
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);

            if ($resp) {
                $data = json_decode($resp, true);
                if (!empty($data['product'])) {
                    $p = $data['product'];
                    $nama = $p['product_name'] ?? ($p['product_name_id'] ?? null);
                    if ($nama) {
                        return response()->json([
                            'found'     => true,
                            'source'    => 'openfoodfacts',
                            'nama'      => $nama,
                            'deskripsi' => $p['brands'] ?? '',
                            'harga'     => null,
                            'satuan'    => 'pcs',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // silent fail
        }

        return response()->json(['found' => false]);
    }
}
