<?php

namespace Modules\Auth\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Auth\app\Models\KonsumenProfile;

class AuthController extends Controller
{
    /**
     * Registrasi user baru.
     * Membuat user + default konsumen profile.
     * Mengirim OTP verifikasi.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|unique:users,no_hp|min:10|max:15',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|unique:users,email',
        ]);

        $user = User::create([
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'] ?? null,
            'otp_code' => $this->generateOtp(),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Default: setiap user punya konsumen profile
        KonsumenProfile::create([
            'user_id' => $user->id,
        ]);

        // TODO fase 1.5: kirim OTP via SMS gateway (untuk MVP, return di response)

        return response()->json([
            'message' => 'Registrasi berhasil. Gunakan OTP untuk verifikasi no HP.',
            'user_id' => $user->id,
            'otp_code' => $user->otp_code, // TODO: hapus setelah SMS gateway jalan
        ], 201);
    }

    /**
     * Login dengan no_hp + password.
     * Return Sanctum token dengan ability sesuai peran.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_hp' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('no_hp', $validated['no_hp'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'No HP atau password salah.'], 401);
        }

        $token = $user->createToken('api-token', $this->getAbilities($user));

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'no_hp' => $user->no_hp,
                'no_hp_verified' => $user->no_hp_verified_at !== null,
                'roles' => [
                    'konsumen' => $user->hasRole('konsumen'),
                    'outlet' => $user->hasRole('outlet'),
                    'kurir' => $user->hasRole('kurir'),
                ],
            ],
        ]);
    }

    /**
     * Verifikasi OTP setelah registrasi.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::find($validated['user_id']);

        if ($user->no_hp_verified_at) {
            return response()->json(['message' => 'No HP sudah diverifikasi.'], 400);
        }

        if ($user->otp_code !== $validated['otp_code']) {
            return response()->json(['message' => 'OTP tidak cocok.'], 400);
        }

        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP sudah kadaluarsa. Minta OTP baru.'], 400);
        }

        $user->update([
            'no_hp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'No HP berhasil diverifikasi.']);
    }

    /**
     * Get current user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['konsumenProfile', 'outletProfile', 'kurirProfile']);

        return response()->json([
            'id' => $user->id,
            'nama' => $user->nama,
            'no_hp' => $user->no_hp,
            'email' => $user->email,
            'no_hp_verified' => $user->no_hp_verified_at !== null,
            'roles' => [
                'konsumen' => $user->konsumenProfile ? ['id' => $user->konsumenProfile->id, 'alamat' => $user->konsumenProfile->alamat] : null,
                'outlet' => $user->outletProfile ? ['id' => $user->outletProfile->id] : null,
                'kurir' => $user->kurirProfile ? ['id' => $user->kurirProfile->id, 'is_online' => $user->kurirProfile->is_online] : null,
            ],
        ]);
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Tentukan ability token sesuai peran yang dimiliki user.
     */
    private function getAbilities(User $user): array
    {
        $abilities = [];

        if ($user->hasRole('konsumen')) {
            $abilities[] = 'konsumen';
        }
        if ($user->hasRole('outlet')) {
            $abilities[] = 'outlet';
        }
        if ($user->hasRole('kurir')) {
            $abilities[] = 'kurir';
        }

        return $abilities;
    }

    /**
     * Generate 6-digit OTP.
     */
    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}