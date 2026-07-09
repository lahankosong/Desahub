<?php

namespace Modules\Admin\app\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = DB::table('orders')
            ->select('orders.*', 'outlets.nama as outlet_nama')
            ->leftJoin('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->orderBy('orders.dibuat_pada', 'desc')
            ->paginate(20);

        return view('admin::orders.index', compact('orders'));
    }
}