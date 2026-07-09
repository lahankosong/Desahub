<?php

namespace Modules\Warung\app\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Warung\app\Models\Produk;
use Modules\Warung\app\Events\KetersediaanBerubah;

class ProdukController extends Controller
{
    /**
     * List produk outlet — GET /api/outlets/{outlet_id}/produk
     */
    public function index(int $outletId)
    {
        $produk = Produk::where('outlet_id', $outletId)->paginate(20);
        return response()->json($produk);
    }

    /**
     * Detail produk — GET /api/produk/{id}
     */
    public function show(int $id)
    {
        $produk = Produk::with('outlet')->findOrFail($id);
        return response()->json($produk);
    }

    /**
     * Tambah produk — POST /api/produk
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'outlet_id'  => 'required|exists:outlets,id',
            'nama'       => 'required|string|max:200',
            'harga'      => 'required|numeric|min:0',
            'satuan'     => 'string|max:20',
            'deskripsi'  => 'nullable|string',
            'barcode'    => 'nullable|string|max:50',
            'qty_awal'   => 'nullable|integer|min:0',
        ]);

        $produk = Produk::create([
            'outlet_id' => $valid['outlet_id'],
            'nama'      => $valid['nama'],
            'harga'     => $valid['harga'],
            'satuan'    => $valid['satuan'] ?? 'pcs',
            'deskripsi' => $valid['deskripsi'] ?? null,
            'barcode'   => $valid['barcode'] ?? null,
        ]);

        // Set stok awal
        $qty = $valid['qty_awal'] ?? 0;
        HasKetersediaanLog::tambahCache(Produk::class, $produk->id, $qty);
        HasKetersediaanLog::catatPergerakan(
            Produk::class, $produk->id, $valid['outlet_id'],
            $qty, 'restock', null
        );

        return response()->json($produk, 201);
    }

    /**
     * Update produk — PUT /api/produk/{id}
     */
    public function update(Request $request, int $id)
    {
        $produk = Produk::findOrFail($id);
        $valid = $request->validate([
            'nama'      => 'string|max:200',
            'harga'     => 'numeric|min:0',
            'satuan'    => 'string|max:20',
            'deskripsi' => 'nullable|string',
            'barcode'   => 'nullable|string|max:50',
        ]);
        $produk->update($valid);
        return response()->json($produk);
    }

    /**
     * Restock — POST /api/produk/{id}/restock
     */
    public function restock(Request $request, int $id)
    {
        $valid = $request->validate(['qty' => 'required|integer|min:1']);
        $produk = Produk::findOrFail($id);

        HasKetersediaanLog::tambahCache(Produk::class, $id, $valid['qty']);
        HasKetersediaanLog::catatPergerakan(
            Produk::class, $id, $produk->outlet_id, $valid['qty'], 'restock', null
        );

        KetersediaanBerubah::dispatch(
            Produk::class, $id, $produk->outlet_id, $valid['qty'], null,
            'restock', null, now()->toDateTimeString()
        );

        return response()->json(['message' => 'Restock berhasil', 'qty' => $valid['qty']]);
    }
}