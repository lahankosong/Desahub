<?php

namespace Modules\Admin\app\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_outlets'        => DB::table('outlets')->count(),
            'pending_verification'  => DB::table('outlets')->where('level_verifikasi', 'dasar')->count(),
            'total_orders'         => DB::table('orders')->count(),
            'orders_hari_ini'      => DB::table('orders')->whereDate('dibuat_pada', today())->count(),
            'orders_dibuat'        => DB::table('orders')->where('status', 'dibuat')->count(),
            'orders_selesai'       => DB::table('orders')->where('status', 'selesai')->count(),
            'total_kurir'          => DB::table('kurir_profiles')->count(),
            'kurir_online'         => DB::table('kurir_profiles')->where('is_online', true)->count(),
            'cod_belum_disetor'    => DB::table('cod_settlements')->where('status_setor', 'belum_disetor')->sum('jumlah_diterima') ?? 0,
            'total_produk'         => DB::table('warung_produk')->count(),
        ];

        return view('admin::dashboard.index', compact('stats'));
    }
}