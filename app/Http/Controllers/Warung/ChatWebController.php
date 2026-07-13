<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Outlet\app\Models\Outlet;
use App\Models\Chat\Percakapan;
use App\Models\Chat\Pesan;

class ChatWebController extends Controller
{
    /**
     * Inbox percakapan milik outlet ini — GET /warung/chat
     */
    public function index()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return redirect()->route('warung.dashboard')->with('error', 'Lengkapi profil outlet dulu.');
        }

        $percakapan = Percakapan::with(['konsumen', 'pesanTerakhir'])
            ->where('outlet_id', $outlet->id)
            ->orderByDesc('dibuat_pada')
            ->get();

        return view('warung.chat', [
            'outlet'      => $outlet,
            'percakapan'  => $percakapan,
            'activeId'     => null,
            'pesanList'   => collect(),
        ]);
    }

    /**
     * Buka 1 percakapan — GET /warung/chat/{id}
     */
    public function show(int $id)
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet) {
            return redirect()->route('warung.dashboard')->with('error', 'Lengkapi profil outlet dulu.');
        }

        $percakapan = Percakapan::with(['konsumen', 'pesanTerakhir'])
            ->where('outlet_id', $outlet->id)
            ->orderByDesc('dibuat_pada')
            ->get();

        $active = Percakapan::with('pesan')
            ->where('outlet_id', $outlet->id)
            ->findOrFail($id);

        // Tandai pesan dari Konsumen sebagai sudah dibaca
        foreach ($active->pesan as $p) {
            if ($p->pengirim_type === 'Konsumen') {
                $p->tandaiDibaca();
            }
        }

        return view('warung.chat', [
            'outlet'      => $outlet,
            'percakapan'  => $percakapan,
            'activeId'     => $active->id,
            'active'       => $active,
            'pesanList'   => $active->pesan,
        ]);
    }

    /**
     * Kirim pesan dari Outlet — POST /warung/chat/{id}/kirim
     */
    public function kirim(Request $request, int $id)
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        $percakapan = Percakapan::where('outlet_id', $outlet->id)->findOrFail($id);

        $valid = $request->validate([
            'isi_pesan' => 'required|string|max:2000',
        ]);

        Pesan::create([
            'percakapan_id'  => $percakapan->id,
            'pengirim_type' => 'Outlet',
            'pengirim_id'   => $outlet->id,
            'isi_pesan'      => $valid['isi_pesan'],
            'dikirim_pada'  => now(),
        ]);

        return redirect()->route('warung.chat.show', $percakapan->id);
    }

    /**
     * Polling pesan baru — GET /warung/chat/{id}/polling?after={last_id}
     */
    public function polling(Request $request, $id)
    {
        $percakapan = Percakapan::findOrFail($id);

        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        if (! $outlet || $percakapan->outlet_id !== $outlet->id) {
            return response()->json(['pesan' => []]);
        }

        $afterId = (int) $request->query('after', 0);

        $pesan = $percakapan->pesan()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get()
            ->map(fn($msg) => [
                'id'            => $msg->id,
                'pengirim_type' => $msg->pengirim_type,
                'isi_pesan'     => $msg->isi_pesan,
                'waktu'         => $msg->dikirim_pada
                    ? $msg->dikirim_pada->format('H:i')
                    : $msg->created_at->format('H:i'),
            ]);

        // Tandai pesan dari Konsumen sebagai sudah dibaca
        if ($pesan->where('pengirim_type', 'Konsumen')->isNotEmpty()) {
            $percakapan->pesan()
                ->where('pengirim_type', 'Konsumen')
                ->whereNull('dibaca_pada')
                ->update(['dibaca_pada' => now()]);
        }

        return response()->json(['pesan' => $pesan]);
    }
}
