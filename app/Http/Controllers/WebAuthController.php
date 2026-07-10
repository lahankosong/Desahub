<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\app\Models\User;
use Modules\Auth\app\Models\KonsumenProfile;

class WebAuthController extends Controller
{
    /**
     * Tampilkan halaman login untuk role tertentu.
     */
    public function showLogin(string $role)
    {
        return view("auth.login", ['role' => $role]);
    }

    /**
     * Proses login (session-based, PWA).
     */
    public function login(Request $request, string $role)
    {
        $validated = $request->validate([
            'no_hp' => 'required|string|min:10',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::guard('web')->attempt([
            'no_hp' => $validated['no_hp'],
            'password' => $validated['password'],
        ])) {
            session(['active_role' => $role]);
            return redirect("/{$role}");
        }

        return back()->withErrors(['login' => 'No HP atau password salah.'])->withInput();
    }

    /**
     * Tampilkan halaman register.
     */
    public function showRegister(string $role)
    {
        return view("auth.register", ['role' => $role]);
    }

    /**
     * Proses registrasi.
     */
    public function register(Request $request, string $role)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|min:10|max:15|unique:users,no_hp',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|unique:users,email',
        ]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'] ?? null,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        KonsumenProfile::create(['user_id' => $user->id]);

        return redirect("/{$role}/verify-otp")
            ->with('user_id', $user->id)
            ->with('otp_code', $otp);
    }

    /**
     * Tampilkan halaman verifikasi OTP.
     */
    public function showVerifyOtp(string $role)
    {
        return view("auth.verify-otp", [
            'role' => $role,
            'userId' => session('user_id'),
            'otpCode' => session('otp_code'),
        ]);
    }

    /**
     * Proses verifikasi OTP.
     */
    public function verifyOtp(Request $request, string $role)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::find($validated['user_id']);

        if ($user->no_hp_verified_at) {
            return back()->withErrors(['otp' => 'No HP sudah diverifikasi. Silakan login.']);
        }

        if ($user->otp_code !== $validated['otp_code']) {
            return back()->withErrors(['otp' => 'OTP tidak cocok.']);
        }

        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP sudah kadaluarsa. Silakan register ulang.']);
        }

        $user->update([
            'no_hp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return redirect("/{$role}/login")->with('success', 'Verifikasi berhasil! Silakan login.');
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}