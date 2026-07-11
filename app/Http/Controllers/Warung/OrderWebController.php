<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Outlet\app\Models\Outlet;
use Modules\Order\app\Models\Order;

class OrderWebController extends Controller
{
    /**
     * List order masuk untuk outlet user — GET /warung/order-masuk
     */
    public function index(Request $request)
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();

        if (! $outlet) {
            return view('warung.order-masuk', ['orders' => collect()]);
        }

        $query = Order::with(['items', 'outlet'])
            ->where('outlet_id', $outlet->id);

        // Filter berdasarkan status dari tab
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->take(50)->get();

        return view('warung.order-masuk', [
            'orders' => $orders,
        ]);
    }

    /**
     * Konfirmasi order — POST /warung/order/{id}/konfirmasi
     * - Ambil sendiri: langsung selesai + catat pembayaran
     * - Diantar kurir: warung hanya acknowledge, status tetap 'dibuat' untuk diklaim kurir
     */
    public function konfirmasi($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'dibuat') {
            return back()->withErrors(['order' => 'Order sudah diproses.']);
        }

        if ($order->metode_pengiriman === 'ambil_sendiri') {
            $order->transitionTo('selesai');
            // Catat pembayaran COD/transfer di tempat (semi-POS)
            $this->catatPembayaran($order);
            return redirect()->route('warung.order-masuk')->with('success', "Order #{$id} diselesaikan & pembayaran dicatat (ambil sendiri).");
        }

        // diantar_kurir: warung acknowledge, status tetap 'dibuat' menunggu kurir klaim
        return redirect()->route('warung.order-masuk')->with('success', "Order #{$id} dikonfirmasi — menunggu kurir mengambil.");
    }

    /**
     * Catat pembayaran ke cod_settlements (semi-POS).
     * Untuk ambil sendiri: dicatat oleh warung tanpa kurir.
     */
    private function catatPembayaran(Order $order): void
    {
        $metode = $order->metode_pembayaran;
        $jumlah = $order->total_harga;

        // Hanya catat jika COD (transfer dicatat manual nanti)
        if ($metode === 'cod') {
            DB::table('cod_settlements')->insert([
                'order_id'       => $order->id,
                'kurir_id'       => $order->kurir_id, // nullable untuk ambil sendiri
                'jumlah_diterima' => $jumlah,
                'status_setor'   => 'belum_disetor',
                'dicatat_oleh'   => 'warung',
                'dicatat_pada'   => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    /**
     * Tolak/batalkan order — POST /warung/order/{id}/tolak
     */
    public function tolak(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (! in_array($order->status, ['dibuat', 'diambil_kurir'])) {
            return back()->withErrors(['order' => 'Order tidak bisa ditolak karena sudah '.$order->status.'.']);
        }

        $order->transitionTo('dibatalkan');
        $order->emitOrderDibatalkan('Outlet', Auth::id(), $request->input('alasan', 'Ditolak oleh warung'));

        return redirect()->route('warung.order-masuk')->with('success', "Order #{$id} ditolak.");
    }
}