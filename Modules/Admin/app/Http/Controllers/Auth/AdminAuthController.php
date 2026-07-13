<?php

namespace Modules\Admin\app\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\app\Models\User;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cari user by no_hp (bukan 'hp')
        $admin = User::where('no_hp', $credentials['username'])->first();

        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_nama' => $admin->nama]);
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['username' => 'Username atau password salah']);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->with(['state' => 'admin'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('admin.login')->withErrors(['google' => 'Gagal login dengan Google.']);
        }

        // Cari user by google_id atau email
        $admin = User::where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        $isAdminEmail = (strtolower($googleUser->email) === strtolower(env('ADMIN_EMAIL', '')));

        if (!$admin) {
            // Admin baru: daftarkan otomatis
            $admin = User::create([
                'nama'      => $googleUser->name,
                'email'     => $googleUser->email,
                'google_id' => $googleUser->id,
                'no_hp'     => null,
                'password'  => Hash::make(\Illuminate\Support\Str::random(32)),
                'is_admin'  => $isAdminEmail,
            ]);
        } else {
            $update = ['google_id' => $googleUser->id];
            // Jika email cocok dengan ADMIN_EMAIL, set is_admin = true
            if ($isAdminEmail && !$admin->is_admin) {
                $update['is_admin'] = true;
            }
            $admin->update($update);
        }

        // Cek apakah user adalah admin
        if (!$admin->is_admin) {
            return redirect()->route('admin.login')->withErrors(['akses' => 'Email Anda tidak terdaftar sebagai admin.']);
        }

        session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_nama' => $admin->nama]);
        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        session()->forget(['admin_logged_in', 'admin_id', 'admin_nama']);
        return redirect()->route('admin.login');
    }
}