<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\OtpMail;
use Modules\Auth\app\Models\User;
use Modules\Auth\app\Models\KonsumenProfile;
use Modules\Auth\app\Models\OutletProfile;
use Modules\Auth\app\Models\KurirProfile;

class WebAuthController extends Controller
{
    /**
     * Ambil role dari URL segment pertama (warung, konsumen, or kurir).
     */
    private function getRole(Request $request): string
    {
        return $request->segment(1);
    }

    /**
     * Tampilkan halaman login untuk role tertentu.
     */
    public function showLogin(Request $request)
    {
        $role = $this->getRole($request);
        return view("auth.login", ['role' => $role]);
    }

    /**
     * Proses login (session-based, PWA).
     */
    public function login(Request $request)
    {
        $role = $this->getRole($request);

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
    public function showRegister(Request $request)
    {
        $role = $this->getRole($request);
        return view("auth.register", ['role' => $role]);
    }

    /**
     * Proses registrasi.
     */
    public function register(Request $request)
    {
        $role = $this->getRole($request);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'no_hp' => 'required|string|min:10|max:15|unique:users,no_hp',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        KonsumenProfile::create(['user_id' => $user->id]);

        // Kirim OTP via Email
        try {
            Mail::to($user->email)->send(new OtpMail($otp, $user->nama));
        } catch (\Exception $e) {
            // Fallback: tetap lanjut, OTP bisa dilihat di halaman verify
            \Log::warning("Gagal kirim email OTP: {$e->getMessage()}");
        }

        return redirect("/{$role}/verify-otp")
            ->with('user_id', $user->id)
            ->with('otp_code', $otp)
            ->with('no_hp', $validated['no_hp'])
            ->with('email', $user->email);
    }

    /**
     * Tampilkan halaman verifikasi OTP.
     */
    public function showVerifyOtp(Request $request)
    {
        $role = $this->getRole($request);
        return view("auth.verify-otp", [
            'role' => $role,
            'userId' => session('user_id'),
            'otpCode' => session('otp_code'),
        ]);
    }

    /**
     * Proses verifikasi OTP.
     */
    public function verifyOtp(Request $request)
    {
        $role = $this->getRole($request);

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
     * Redirect ke Google untuk login.
     */
    public function redirectToGoogle(Request $request)
    {
        $role = $this->getRole($request);
        session(['google_auth_role' => $role]);
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback dari Google setelah user setuju.
     */
    public function handleGoogleCallback(Request $request)
    {
        $role = session('google_auth_role', 'konsumen');

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect("/{$role}/login")->withErrors(['google' => 'Gagal login dengan Google.']);
        }

        // Cari user berdasarkan google_id atau email
        $user = User::where('google_id', $googleUser->id)->first()
            ?? User::where('email', $googleUser->email)->first();

        if ($user) {
            // Update google_id jika belum ada
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->id]);
            }
        } else {
            // Buat user baru + profile sesuai role
            $user = User::create([
                'nama' => $googleUser->name ?? $googleUser->email,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'no_hp' => 'google_' . $googleUser->id, // placeholder, bisa diupdate nanti
                'password' => Hash::make(uniqid()),
                'no_hp_verified_at' => now(), // Google sudah verify
            ]);

            // Buat profile sesuai role yang dipilih saat login
            if ($role === 'warung') {
                OutletProfile::create(['user_id' => $user->id]);
            } elseif ($role === 'kurir') {
                KurirProfile::create(['user_id' => $user->id]);
            } else {
                // default: konsumen
                KonsumenProfile::create(['user_id' => $user->id]);
            }
        }

        Auth::guard('web')->login($user);
        session(['active_role' => $role]);

        return redirect("/{$role}");
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