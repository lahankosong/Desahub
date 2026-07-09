<?php

namespace Modules\Kurir\app\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\app\Models\KurirProfile;
use Modules\Order\app\Models\Order;

class KurirController extends Controller
{
    /**
     * Set online/offline.
     */
    public function toggleOnline(Request $request)
    {
        $valid = $request->validate([
            'online' => 'required|boolean',
            'lat'    => 'nullable|numeric',
            'lng'    => 'nullable|numeric',
        ]);

        $kurir = KurirProfile::where('user_id', $request->user()->id)->firstOrFail();

        $kurir->update([
            'is_online'       => $valid['online'],
            'lat'             => $valid['lat'] ?? $kurir->lat,
            'lng'             => $valid['lng'] ?? $kurir->lng,
            'terakhir_online' => now(),
        ]);

        return response()->json(['is_online' => $kurir->is_online]);
    }

    /**
     * List order yang bisa diklaim (status 'dibuat', belum ada kurir).
     */
    public function availableOrders()
    {
        $orders = Order::with('outlet')
            ->where('status', 'dibuat')
            ->whereNull('kurir_id')
            ->orderByDesc('dibuat_pada')
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Klaim order — UPDATE atomik, cegah rebutan.
     * POST /api/kurir/orders/{id}/klaim
     */
    public function klaimOrder(int $id, Request $request)
    {
        $kurir = KurirProfile::where('user_id', $request->user()->id)->firstOrFail();
        $berhasil = Order::klaimOlehKurir($id, $kurir->id);

        if (! $berhasil) {
            return response()->json(['message' => 'Order sudah diklaim kurir lain'], 409);
        }

        return response()->json(Order::find($id));
    }

    /**
     * Update status antar.
     * POST /api/kurir/orders/{id}/update-status
     * Body: { "status": "diantar" | "selesai" }
     */
    public function updateStatus(int $id, Request $request)
    {
        $valid = $request->validate([
            'status' => 'required|in:diambil_kurir,diantar,selesai,gagal_kirim',
        ]);

        $order = Order::findOrFail($id);

        if ($order->kurir_id !== KurirProfile::where('user_id', $request->user()->id)->value('id')) {
            return response()->json(['message' => 'Order ini bukan milik Anda'], 403);
        }

        if (! $order->canTransitionTo($valid['status'])) {
            return response()->json(['message' => "Transisi {$order->status} -> {$valid['status']} tidak diizinkan"], 422);
        }

        $order->transitionTo($valid['status']);

        return response()->json($order);
    }

    /**
     * List order yang sedang/sudah saya tangani.
     */
    public function myOrders(Request $request)
    {
        $kurir = KurirProfile::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(
            Order::with('outlet')
                ->where('kurir_id', $kurir->id)
                ->orderByDesc('dibuat_pada')
                ->paginate(20)
        );
    }
}