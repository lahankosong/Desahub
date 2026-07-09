<?php

namespace Modules\Admin\app\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = DB::table('outlets')
            ->select('outlets.*')
            ->selectRaw('(SELECT GROUP_CONCAT(vertikal) FROM outlet_vertikal WHERE outlet_vertikal.outlet_id = outlets.id) as vertikals')
            ->orderBy('outlets.created_at', 'desc')
            ->paginate(20);

        return view('admin::outlets.index', compact('outlets'));
    }

    public function verify(Request $request, int $id)
    {
        DB::table('outlets')
            ->where('id', $id)
            ->update([
                'level_verifikasi' => 'terverifikasi',
                'updated_at'       => now(),
            ]);

        return back()->with('success', 'Outlet berhasil diverifikasi');
    }
}