<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Outlet\app\Models\Outlet;
use Modules\Warung\app\Models\PelangganWarung;
use Modules\Warung\app\Models\Piutang;

class PosController extends Controller
{
    /**
     * Daftar pelanggan warung — GET /warung/pos/pelanggan
     */
    public function daftarPelanggan()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (!$outlet) {
            return response()->json([]);
        }
        $pelanggan = PelangganWarung::where('outlet_id', $outlet->id)
            ->orderBy('nama')
            ->get()
            ->map(function ($p) {
                return [
                    'id'                => $p->id,
                    'nama'              => $p->nama,
                    'no_hp'             => $p->no_hp,
                    'catatan'           => $p->catatan,
                    'total_utang_aktif' => $p->totalUtangAktif(),
                ];
            });

        return response()->json($pelanggan);
    }

    /**
     * Tambah pelanggan baru — POST /warung/pos/pelanggan
     */
    public function tambahPelanggan(Request $request)
    {
        $valid = $request->validate([
            'nama'   => 'required|string|max:200',
            'no_hp'  => 'nullable|string|max:20',
            'catatan' => 'nullable|string|max:500',
        ]);

        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (!$outlet) {
            return response()->json(['success' => false, 'message' => 'Outlet tidak ditemukan'], 404);
        }

        $pelanggan = PelangganWarung::create([
            'outlet_id' => $outlet->id,
            'nama'      => $valid['nama'],
            'no_hp'     => $valid['no_hp'] ?? null,
            'catatan'   => $valid['catatan'] ?? null,
        ]);

        return response()->json(['success' => true, 'pelanggan' => $pelanggan]);
    }

    /**
     * Daftar piutang aktif — GET /warung/pos/piutang
     */
    public function daftarPiutang()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (!$outlet) {
            return response()->json([]);
        }
        return response()->json(
            Piutang::with('pelanggan')
                ->where('outlet_id', $outlet->id)
                ->where('status', 'aktif')
                ->orderBy('jatuh_tempo')
                ->get()
        );
    }

    /**
     * Catat pembayaran piutang — POST /warung/pos/piutang/{id}/bayar
     */
    public function bayarPiutang(Request $request, $id)
    {
        $valid = $request->validate([
            'jumlah' => 'required|numeric|min:1',
        ]);

        $piutang = Piutang::findOrFail($id);
        if ($piutang->status !== 'aktif') {
            return response()->json(['success' => false, 'message' => 'Piutang sudah lunas atau tidak aktif'], 422);
        }

        $baru = $piutang->terbayar + $valid['jumlah'];
        if ($baru > $piutang->jumlah) {
            return response()->json(['success' => false, 'message' => 'Jumlah bayar melebihi sisa piutang'], 422);
        }

        $piutang->terbayar = $baru;
        if ($baru >= $piutang->jumlah) {
            $piutang->status = 'lunas';
        }
        $piutang->save();

        return response()->json(['success' => true, 'piutang' => $piutang->fresh('pelanggan')]);
    }
}