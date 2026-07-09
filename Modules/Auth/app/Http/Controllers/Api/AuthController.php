<?php

namespace Modules\Auth\app\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Auth\app\Models\OutletProfile;
use Modules\Auth\app\Models\KonsumenProfile;
use Modules\Auth\app\Models\KurirProfile;

class AuthController extends Controller
{
    /**
     * Registrasi user baru (OTP simulation — langsung aktif di MVP).
     */
    public function register(Request $request)
    {
        $valid = $request->validate([
            'nama'     => 'required|string|max:100',
            'hp'       => 'required|string|min:10|max:15|unique:users,hp',
            'password' => 'required|string|min:6',
            'peran'    => 'required|in:outlet,konsumen,kurir',
        ]);

        $user = User::create([
            'nama'     => $valid['nama'],
            'hp'       => $valid['hp'],
            'password' => $valid['password'],
        ]);

        // Buat profil sesuai peran
        match ($valid['peran']) {
            'outlet'   => OutletProfile::create(['user_id' => $user->id]),
            'konsumen' => KonsumenProfile::create(['user_id' => $user->id]),
            'kurir'    => KurirProfile::create(['user_id' => $user->id]),
        };

        $token = $user->createToken('mobile', [$valid['peran']])->plainTextToken;

        return response()->json([
            'user'  => ['id' => $user->id, 'nama' => $user->nama, 'hp' => $user->hp, 'peran' => $valid['peran']],
            'token' => $token,
        ], 201);
    }

    /**
     * Login — return Sanctum token.
     */
    public function login(Request $request)
    {
        $valid = $request->validate([
            'hp'       => 'required|string',
            'password' => 'required|string',
            'peran'    => 'required|in:outlet,konsumen,kurir',
        ]);

        $user = User::where('hp', $valid['hp'])->first();

        if (! $user || ! Hash::check($valid['password'], $user->password)) {
            return response()->json(['message' => 'HP atau password salah'], 401);
        }

        $token = $user->createToken('mobile', [$valid['peran']])->plainTextToken;

        return response()->json([
            'user'  => ['id' => $user->id, 'nama' => $user->nama, 'hp' => $user->hp, 'peran' => $valid['peran']],
            'token' => $token,
        ]);
    }

    /**
     * Logout — hapus token aktual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Profil user saat ini.
     */
    public function profil(Request $request)
    {
        $user = $request->user();
        $data = ['id' => $user->id, 'nama' => $user->nama, 'hp' => $user->hp];

        if ($user->outletProfile) {
            $data['outlet_profile'] = $user->outletProfile->id;
        }
        if ($user->konsumenProfile) {
            $data['konsumen_profile'] = $user->konsumenProfile->id;
        }
        if ($user->kurirProfile) {
            $data['kurir_profile'] = ['id' => $user->kurirProfile->id, 'is_online' => $user->kurirProfile->is_online];
        }

        return response()->json($data);
    }
}