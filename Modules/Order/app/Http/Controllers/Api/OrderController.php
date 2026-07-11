<?php

namespace Modules\Order\app\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Contracts\Sellable;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Order\app\Models\Order;

class OrderController extends Controller
{
    /**
     * List order milik pembeli (Konsumen) atau outlet.
     * GET /api/orders?outlet_id=1 atau /api/orders?buyer_id=1&buyer_type=Konsumen
     */
    public function index(Request $request)
    {
        $query = Order::with('items');

        if ($request->outlet_id) {
            $query->where('outlet_id', $request->outlet_id);
        }
        if ($request->buyer_id && $request->buyer_type) {
            $query->where('buyer_id', $request->buyer_id)
                  ->where('buyer_type', $request->buyer_type);
        }

        return response()->json($query->orderByDesc('dibuat_pada')->paginate(20));
    }

    /**
     * Detail order
     */
    public function show(int $id)
    {
        return response()->json(Order::with('items')->findOrFail($id));
    }

    /**
     * Checkout — POST /api/orders
     *
     * Payload:
     * {
     *   "outlet_id": 1,
     *   "buyer_type": "Konsumen",
     *   "buyer_id": 1,
     *   "items": [
     *     {"sellable_type": "Modules\\Warung\\app\\Models\\Produk", "sellable_id": 1, "qty": 2}
     *   ],
     *   "metode_pembayaran": "cod"
     * }
     */
    public function store(Request $request)
    {
        $valid = $request->validate([
            'outlet_id'          => 'required|exists:outlets,id',
            'buyer_type'         => 'required|in:Konsumen,Outlet',
            'buyer_id'           => 'required|integer',
            'items'              => 'required|array|min:1',
            'items.*.sellable_type' => 'required|string',
            'items.*.sellable_id'   => 'required|integer',
            'items.*.qty'            => 'required|integer|min:1',
            'metode_pembayaran'  => 'required|in:cod,transfer,dp',
        ]);

        // 1. Validasi outlet + BuyerEligibilityPolicy
        $outlet = \Modules\Outlet\app\Models\Outlet::findOrFail($valid['outlet_id']);

        if (! $outlet->bolehDibeliOleh($valid['buyer_type'], $valid['buyer_id'])) {
            return response()->json(['message' => 'Anda tidak diizinkan membeli dari outlet ini'], 403);
        }

        // 2. Resolusi harga + cek ketersediaan
        $totalHarga = 0;
        $orderItems = [];
        DB::beginTransaction();
        try {
            foreach ($valid['items'] as $item) {
                $sellableClass = $item['sellable_type'];
                $sellable = $sellableClass::findOrFail($item['sellable_id']);

                if (! $sellable instanceof Sellable) {
                    throw new \RuntimeException("{$sellableClass} tidak implement Sellable");
                }

                if (! $sellable->cekTersedia($item['qty'])) {
                    throw new \RuntimeException("{$sellable->getNama()} tidak tersedia untuk qty {$item['qty']}");
                }

                // Race condition protection: kurangi cache atomik
                $berhasil = HasKetersediaanLog::kurangiCacheAtomik($sellableClass, $item['sellable_id'], $item['qty']);
                if (! $berhasil) {
                    throw new \RuntimeException("Stok {$sellable->getNama()} tidak mencukupi");
                }

                $hargaSatuan = $sellable->getHarga($item['qty']);
                $totalHarga += $hargaSatuan * $item['qty'];

                // Catat di log
                HasKetersediaanLog::catatPergerakan(
                    $sellableClass, $item['sellable_id'], $valid['outlet_id'],
                    -$item['qty'], 'penjualan', null
                );

                $orderItems[] = [
                    'sellable_type' => $sellableClass,
                    'sellable_id'   => $item['sellable_id'],
                    'nama_produk'   => $sellable->getNama(),
                    'qty'            => $item['qty'],
                    'harga_satuan'   => $hargaSatuan,
                ];
            }

            // 3. Create order
            $order = Order::create([
                'outlet_id'         => $valid['outlet_id'],
                'buyer_type'        => $valid['buyer_type'],
                'buyer_id'          => $valid['buyer_id'],
                'total_harga'       => $totalHarga,
                'metode_pembayaran' => $valid['metode_pembayaran'],
                'status'            => 'dibuat',
                'dibuat_pada'       => now(),
            ]);

            foreach ($orderItems as $oi) {
                $order->items()->create($oi);
            }

            DB::commit();

            // Emit OrderDibuat (listener akan trigger pengurangan stok + notifikasi)
            $order->emitOrderDibuat();

            return response()->json($order->load('items'), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Batalkan order — POST /api/orders/{id}/batal
     */
    public function batal(Request $request, int $id)
    {
        $order = Order::findOrFail($id);
        $valid = $request->validate([
            'dibatalkan_oleh_type' => 'required|in:Konsumen,Warung,Kurir,Admin',
            'dibatalkan_oleh_id'   => 'required|integer',
            'alasan'              => 'required|string',
        ]);

        if (! $order->canTransitionTo('dibatalkan')) {
            return response()->json(['message' => 'Order tidak bisa dibatalkan dari status ' . $order->status], 422);
        }

        $order->transitionTo('dibatalkan');
        $order->emitOrderDibatalkan($valid['dibatalkan_oleh_type'], $valid['dibatalkan_oleh_id'], $valid['alasan']);

        return response()->json($order);
    }
}