<?php

namespace App\Http\Controllers\Kurir;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\KurirProfile;
use Modules\Core\app\Helpers\RadiusHelper;
use Modules\Order\app\Models\Order;
use Modules\Outlet\app\Models\Outlet;

class KurirWebController extends Controller
{
    /**
     * Riwayat Transaksi COD — GET /kurir/riwayat-transaksi
     */
    public function riwayatTransaksi()
    {
        $user = auth()->user();
        $kurirId = $user?->kurirProfile?->id ?? null;
        
        $transaksi = $kurirId
            ? \Modules\Payment\app\Models\CodSettlement::where('kurir_id', $kurirId)
                ->orderBy('dicatat_pada', 'desc')
                ->take(20)
                ->get()
            : collect();

        return view('kurir.riwayat-transaksi', ['transaksi' => $transaksi]);
    }

    /**
     * Order Aktif — GET /kurir/order-aktif
     */
    public function index()
    {
        $user = Auth::user();
        $kurir = KurirProfile::where('user_id', $user->id)->first();

        $orderTersedia = Order::where('status', 'dibuat')->whereNull('kurir_id')->count();
        $orderAktif = $kurir
            ? Order::where('kurir_id', $kurir->id)->whereIn('status', ['diambil_kurir', 'diantar'])->count()
            : 0;

        return view('kurir.dashboard', [
            'kurir'          => $kurir,
            'orderTersedia'  => $orderTersedia,
            'orderAktif'     => $orderAktif,
        ]);
    }

    /**
     * Toggle online/offline — POST /kurir/toggle-online
     */
    public function toggleOnline(Request $request)
    {
        $user = Auth::user();
        $kurir = KurirProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['is_online' => true, 'lat' => 0, 'lng' => 0]
        );

        $online = $request->boolean('online', !$kurir->is_online);

        $kurir->update([
            'is_online'       => $online,
            'lat'             => $request->input('lat', $kurir->lat),
            'lng'             => $request->input('lng', $kurir->lng),
            'terakhir_online' => now(),
        ]);

        return back()->with('success', $online ? 'Anda sekarang Online' : 'Anda sekarang Offline');
    }

    /**
     * Daftar order tersedia — GET /kurir/order-tersedia
     */
    public function orderTersedia()
    {
        $user = Auth::user();
        $kurir = KurirProfile::where('user_id', $user->id)->first();

        $query = Order::with('outlet')
            ->where('status', 'dibuat')
            ->whereNull('kurir_id')
            ->where('metode_pengiriman', 'diantar_kurir');

        // Filter radius jika kurir punya GPS
        if ($kurir && $kurir->lat != 0 && $kurir->lng != 0) {
            $haversine = RadiusHelper::getHaversineExpression($kurir->lat, $kurir->lng, 'outlets.lat', 'outlets.lng');
            $box = RadiusHelper::hitungBoundingBox($kurir->lat, $kurir->lng, 5); // radius 5km

            $outletIds = Outlet::select('id')
                ->whereBetween('lat', [$box['lat_min'], $box['lat_max']])
                ->whereBetween('lng', [$box['lng_min'], $box['lng_max']])
                ->whereRaw("({$haversine}) <= 5")
                ->pluck('id');

            $query->whereIn('outlet_id', $outletIds);
        }

        $orders = $query->orderByDesc('dibuat_pada')->take(30)->get();

        return view('kurir.order-tersedia', ['orders' => $orders]);
    }

    /**
     * Klaim order — POST /kurir/order/{id}/klaim
     */
    public function klaimOrder($id)
    {
        $user = Auth::user();
        $kurir = KurirProfile::where('user_id', $user->id)->first();

        if (! $kurir || ! $kurir->is_online) {
            return back()->withErrors(['kurir' => 'Anda harus Online untuk mengambil order.']);
        }

        $berhasil = Order::klaimOlehKurir($id, $kurir->id);

        if (! $berhasil) {
            return back()->withErrors(['order' => 'Order sudah diklaim kurir lain.']);
        }

        return redirect()->route('kurir.order-aktif')->with('success', "Order #{$id} berhasil diklaim!");
    }

    /**
     * Daftar order aktif — GET /kurir/order-aktif
     */
    public function orderAktif()
    {
        $user = Auth::user();
        $kurir = KurirProfile::where('user_id', $user->id)->first();

        if (! $kurir) {
            return view('kurir.order-aktif', ['orders' => collect()]);
        }

        $orders = Order::with('outlet')
            ->where('kurir_id', $kurir->id)
            ->whereIn('status', ['diambil_kurir', 'diantar'])
            ->orderByDesc('dibuat_pada')
            ->take(20)
            ->get();

        return view('kurir.order-aktif', ['orders' => $orders]);
    }

    /**
     * Update status order — POST /kurir/order/{id}/update-status
     */
    public function updateStatus(Request $request, $id)
    {
        $valid = $request->validate([
            'status' => 'required|in:diantar,selesai,gagal_kirim',
        ]);

        $user = Auth::user();
        $order = Order::findOrFail($id);
        $kurir = KurirProfile::where('user_id', $user->id)->first();

        if (! $kurir || $order->kurir_id !== $kurir->id) {
            return back()->withErrors(['order' => 'Order ini bukan milik Anda.']);
        }

        if (! $order->canTransitionTo($valid['status'])) {
            return back()->withErrors(['order' => "Transisi tidak diizinkan."]);
        }

        $order->transitionTo($valid['status']);

        // Catat pembayaran COD saat order selesai
        if ($valid['status'] === 'selesai' && $order->metode_pembayaran === 'cod') {
            DB::table('cod_settlements')->insert([
                'order_id'        => $order->id,
                'kurir_id'        => $kurir->id,
                'jumlah_diterima' => $order->total_harga,
                'status_setor'    => 'belum_disetor',
                'dicatat_oleh'    => 'kurir',
                'dicatat_pada'    => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $labels = ['diantar' => 'sedang diantar', 'selesai' => 'selesai & pembayaran dicatat', 'gagal_kirim' => 'gagal dikirim'];
        $label = $labels[$valid['status']] ?? $valid['status'];

        return redirect()->route('kurir.order-aktif')->with('success', "Order #{$id} {$label}.");
    }
}