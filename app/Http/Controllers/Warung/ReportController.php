<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Traits\HasKetersediaanLog;
use Modules\Outlet\app\Models\Outlet;
use Modules\Order\app\Models\Order;
use Modules\Warung\app\Models\Produk;

class ReportController extends Controller
{
    /**
     * Halaman laporan warung — GET /warung/laporan
     */
    public function index()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return view('warung.laporan', ['data' => null]);
        }

        // Omzet hari ini
        $omzetHariIni = Order::where('outlet_id', $outlet->id)
            ->where('status', 'selesai')
            ->whereDate('created_at', now()->toDateString())
            ->sum('total_harga');

        // Omzet minggu ini
        $omzetMingguIni = Order::where('outlet_id', $outlet->id)
            ->where('status', 'selesai')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total_harga');

        // Omzet bulan ini
        $omzetBulanIni = Order::where('outlet_id', $outlet->id)
            ->where('status', 'selesai')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_harga');

        // Total order
        $totalOrderHariIni = Order::where('outlet_id', $outlet->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        $totalOrderMingguIni = Order::where('outlet_id', $outlet->id)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        // Top produk
        $topProduk = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.outlet_id', $outlet->id)
            ->where('orders.status', 'selesai')
            ->select('order_items.sellable_id', DB::raw('SUM(order_items.qty) as total_qty'), DB::raw('SUM(order_items.qty * order_items.harga_satuan) as total_omzet'))
            ->groupBy('order_items.sellable_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get()
            ->map(function ($item) {
                $produk = Produk::find($item->sellable_id);
                return [
                    'nama'       => $produk?->nama ?? "Produk #{$item->sellable_id}",
                    'total_qty'  => $item->total_qty,
                    'total_omzet' => $item->total_omzet,
                ];
            });

        // COD settlements
        $codBelumDisetor = DB::table('cod_settlements')
            ->join('orders', 'cod_settlements.order_id', '=', 'orders.id')
            ->where('orders.outlet_id', $outlet->id)
            ->where('cod_settlements.status_setor', 'belum_disetor')
            ->sum('cod_settlements.jumlah_diterima');

        $codSudahDisetor = DB::table('cod_settlements')
            ->join('orders', 'cod_settlements.order_id', '=', 'orders.id')
            ->where('orders.outlet_id', $outlet->id)
            ->where('cod_settlements.status_setor', 'sudah_disetor')
            ->sum('cod_settlements.jumlah_diterima');

        // Margin total
        $produkList = Produk::where('outlet_id', $outlet->id)->get();
        $totalMargin = $produkList->sum(function ($p) {
            $hargaBeli = $p->harga_beli ?? 0;
            if ($hargaBeli <= 0) return 0;
            $stok = HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id);
            return ($p->harga - $hargaBeli) * $stok;
        });

        return view('warung.laporan', [
            'data' => [
                'outlet'            => $outlet,
                'omzetHariIni'      => $omzetHariIni,
                'omzetMingguIni'    => $omzetMingguIni,
                'omzetBulanIni'     => $omzetBulanIni,
                'totalOrderHariIni' => $totalOrderHariIni,
                'totalOrderMingguIni' => $totalOrderMingguIni,
                'topProduk'         => $topProduk,
                'codBelumDisetor'   => $codBelumDisetor,
                'codSudahDisetor'   => $codSudahDisetor,
                'totalMargin'       => $totalMargin,
                'totalProduk'       => $produkList->count(),
                'produkHabis'       => $produkList->filter(fn($p) => HasKetersediaanLog::getKetersediaanCache(Produk::class, $p->id) <= 0)->count(),
            ],
        ]);
    }
}