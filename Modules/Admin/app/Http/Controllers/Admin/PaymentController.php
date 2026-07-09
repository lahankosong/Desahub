<?php

namespace Modules\Admin\app\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $settlements = DB::table('cod_settlements')
            ->select('cod_settlements.*', 'orders.outlet_id', 'outlets.nama as outlet_nama')
            ->leftJoin('orders', 'cod_settlements.order_id', '=', 'orders.id')
            ->leftJoin('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->orderBy('cod_settlements.dicatat_pada', 'desc')
            ->paginate(20);

        $total_belum_disetor = DB::table('cod_settlements')
            ->where('status_setor', 'belum_disetor')
            ->sum('jumlah_diterima') ?? 0;

        return view('admin::payments.index', compact('settlements', 'total_belum_disetor'));
    }

    public function setor(Request $request, int $id)
    {
        DB::table('cod_settlements')
            ->where('id', $id)
            ->update([
                'status_setor' => 'sudah_disetor',
                'updated_at'   => now(),
            ]);

        return back()->with('success', 'Penyetoran COD dicatat sukses');
    }
}