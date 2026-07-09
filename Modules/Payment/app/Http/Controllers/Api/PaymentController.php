<?php

namespace Modules\Payment\app\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payment\app\Events\PembayaranDiterima;
use Modules\Order\app\Models\Order;

class PaymentController extends Controller
{
    /**
     * Konfirmasi pembayaran diterima (COD, transfer, DP, QRIS).
     * POST /api/payments/konfirmasi
     *
     * Body: {
     *   "order_id": 1,
     *   "metode": "cod",
     *   "jumlah": 50000,
     *   "status": "lunas",
     *   "kurir_id": 1  // hanya untuk COD — mencatat settlement
     * }
     */
    public function konfirmasi(Request $request)
    {
        $valid = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'metode'   => 'required|in:cod,transfer,dp,qris',
            'jumlah'   => 'required|numeric|min:0',
            'status'   => 'required|in:lunas,sebagian',
            'kurir_id' => 'nullable|integer|exists:kurir_profiles,id',
        ]);

        // Kalau COD, catat di cod_settlements
        if ($valid['metode'] === 'cod' && $valid['kurir_id']) {
            \DB::table('cod_settlements')->insert([
                'order_id'        => $valid['order_id'],
                'kurir_id'        => $valid['kurir_id'],
                'jumlah_diterima'  => $valid['jumlah'],
                'status_setor'    => 'belum_disetor',
                'dicatat_pada'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Emit PembayaranDiterima -> listener ubah status order jadi selesai
        PembayaranDiterima::dispatch(
            order_id: $valid['order_id'],
            metode: $valid['metode'],
            jumlah: $valid['jumlah'],
            status: $valid['status'],
            diterima_pada: now()->toDateTimeString()
        );

        return response()->json(['message' => 'Pembayaran dikonfirmasi']);
    }
}