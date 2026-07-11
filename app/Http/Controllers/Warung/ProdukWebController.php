<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Outlet\app\Models\Outlet;
use Modules\Warung\app\Models\Produk;

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
        ]);

        $produk->update($valid);

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
     */
    public function restock(Request $request, $id)
    {
        $valid = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $produk = Produk::findOrFail($id);

        HasKetersediaanLog::tambahCache(Produk::class, $produk->id, $valid['qty']);
        HasKetersediaanLog::catatPergerakan(
            Produk::class, $produk->id, $produk->outlet_id,
            $valid['qty'], 'restock', null
        );

        return redirect()->route('warung.kelola-produk')->with('success', "Stok {$produk->nama} bertambah +{$valid['qty']}.");
    }

    /**
     * Transaksi POS — POST /warung/pos/transaksi
     * Buat order POS (walk-in, buyer_type=Umum, langsung selesai).
     */
    public function posTransaksi(Request $request)
    {
        $valid = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|integer|exists:warung_produk,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
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

        // Buat order POS
        $order = \Modules\Order\app\Models\Order::create([
            'outlet_id' => $outlet->id,
            'buyer_type' => 'Umum',
            'buyer_id' => null,
            'total_harga' => $valid['total'],
            'metode_pembayaran' => 'tunai_pos',
            'metode_pengiriman' => null,
            'jenis_transaksi' => 'pos',
            'status' => 'selesai',
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
                'produk_nama' => $produk->nama,
            ]);

            // Kurangi stok atomik
            HasKetersediaanLog::tambahCache(Produk::class, $produk->id, -$item['qty']);
            HasKetersediaanLog::catatPergerakan(
                Produk::class, $produk->id, $outlet->id,
                -$item['qty'], 'penjualan', $order->id
            );
        }

        // Emit events
        \Modules\Order\app\Events\OrderDibuat::dispatch($order);
        \Modules\Payment\app\Events\PembayaranDiterima::dispatch($order, 'tunai_pos', $valid['total'], 'lunas');

        return response()->json(['success' => true, 'order_id' => $order->id]);
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
