<?php

namespace App\Http\Controllers\Warung;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Outlet\app\Models\Outlet;
use Modules\Warung\app\Models\WarungDetail;

class ProfilWebController extends Controller
{
    /**
     * Verifikasi outlet — GET /warung/verifikasi
     */
    public function verifikasi()
    {
        $outlet = Outlet::where('owner_user_id', Auth::id())->first();
        return view('warung.verifikasi', ['outlet' => $outlet]);
    }

    /**
     * Submit verifikasi — POST /warung/verifikasi
     */
    public function submitVerifikasi(Request $request)
    {
        $valid = $request->validate([
            'dokumen_usaha' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'foto_lokasi' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'catatan' => 'nullable|string|max:500',
        ]);

        // TODO: Upload file ke storage, update outlet->level_verifikasi = 'menunggu'
        // Untuk MVP, simpan path saja
        
        return back()->with('success', 'Dokumen verifikasi berhasil dikirim. Tim kami akan memeriksa dalam 1-2 hari kerja.');
    }

    /**
     * Save outlet — POST /warung/profil/outlet
     */
    public function index()
    {
        $user = Auth::user();
        $outlet = Outlet::with('warungDetail')->where('owner_user_id', $user->id)->first();
        $warung = $outlet?->warungDetail;

        return view('warung.profil', [
            'user'   => $user,
            'outlet' => $outlet,
            'warung' => $warung,
        ]);
    }

    /**
     * Update data akun (nama, email) — POST /warung/profil/akun
     */
    public function updateAkun(Request $request)
    {
        $valid = $request->validate([
            'nama'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $user->update($valid);

        return redirect()->route('warung.profil')->with('success', 'Data akun berhasil diupdate.');
    }

    /**
     * Update password — POST /warung/profil/password
     */
    public function updatePassword(Request $request)
    {
        $valid = $request->validate([
            'password_lama' => 'required|string',
            'password'      => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (! Hash::check($valid['password_lama'], $user->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak cocok.']);
        }

        $user->update(['password' => Hash::make($valid['password'])]);

        return redirect()->route('warung.profil')->with('success', 'Password berhasil diubah.');
    }

    /**
     * Simpan outlet (daftar baru atau update) — POST /warung/profil/outlet
     */
    public function saveOutlet(Request $request)
    {
        $valid = $request->validate([
            'nama'            => 'required|string|max:255',
            'alamat'          => 'required|string|max:500',
            'provinsi'        => 'nullable|string|max:100',
            'kabupaten'       => 'nullable|string|max:100',
            'kecamatan'       => 'nullable|string|max:100',
            'desa_kelurahan'  => 'nullable|string|max:100',
            'rt'              => 'nullable|string|max:10',
            'rw'              => 'nullable|string|max:10',
            'kode_pos'        => 'nullable|string|max:10',
            'lat'             => 'nullable|numeric|min:-90|max:90',
            'lng'             => 'nullable|numeric|min:-180|max:180',
            'jam_buka'        => 'nullable|date_format:H:i',
            'jam_tutup'       => 'nullable|date_format:H:i',
            'kategori_warung' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $outlet = Outlet::where('owner_user_id', $user->id)->first();

        $isNew = false;
        if (! $outlet) {
            // Daftar outlet baru
            $outlet = Outlet::create([
                'owner_user_id'   => $user->id,
                'nama'            => $valid['nama'],
                'alamat'          => $valid['alamat'],
                'provinsi'        => $valid['provinsi'] ?? null,
                'kabupaten'       => $valid['kabupaten'] ?? null,
                'kecamatan'       => $valid['kecamatan'] ?? null,
                'desa_kelurahan'  => $valid['desa_kelurahan'] ?? null,
                'rt'              => $valid['rt'] ?? null,
                'rw'              => $valid['rw'] ?? null,
                'kode_pos'        => $valid['kode_pos'] ?? null,
                'lat'             => $valid['lat'] ?? 0,
                'lng'             => $valid['lng'] ?? 0,
                'level_verifikasi' => 'dasar',
            ]);
            $isNew = true;
        } else {
            // Update outlet
            $outlet->update([
                'nama'           => $valid['nama'],
                'alamat'         => $valid['alamat'],
                'provinsi'       => $valid['provinsi'] ?? null,
                'kabupaten'      => $valid['kabupaten'] ?? null,
                'kecamatan'      => $valid['kecamatan'] ?? null,
                'desa_kelurahan' => $valid['desa_kelurahan'] ?? null,
                'rt'             => $valid['rt'] ?? null,
                'rw'             => $valid['rw'] ?? null,
                'kode_pos'       => $valid['kode_pos'] ?? null,
                'lat'            => $valid['lat'] ?? $outlet->lat,
                'lng'            => $valid['lng'] ?? $outlet->lng,
            ]);
        }

        // Buat atau update detail warung
        $warung = $outlet->warungDetail;
        if (! $warung) {
            $warung = WarungDetail::create([
                'outlet_id'        => $outlet->id,
                'jam_buka'         => $valid['jam_buka'] ?? null,
                'jam_tutup'        => $valid['jam_tutup'] ?? null,
                'kategori_warung'  => $valid['kategori_warung'] ?? null,
                'tier'             => 'biasa',
            ]);
        } else {
            $warung->update([
                'jam_buka'        => $valid['jam_buka'] ?? null,
                'jam_tutup'       => $valid['jam_tutup'] ?? null,
                'kategori_warung' => $valid['kategori_warung'] ?? null,
            ]);
        }

        $msg = $isNew ? 'Outlet berhasil didaftarkan!' : 'Data outlet berhasil diupdate.';
        return redirect()->route('warung.profil')->with('success', $msg);
    }
}