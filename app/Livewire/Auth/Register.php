<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Modules\Auth\app\Models\User;
use Modules\Auth\app\Models\KonsumenProfile;
use Illuminate\Support\Facades\Hash;

class Register extends Component
{
    public $nama = '';
    public $no_hp = '';
    public $password = '';
    public $email = '';
    public $error = '';
    public $success = '';
    public $otp_code = '';
    public $userId = null;
    public $role = 'konsumen';

    protected $rules = [
        'nama' => 'required|string|max:255',
        'no_hp' => 'required|string|min:10|max:15|unique:users,no_hp',
        'password' => 'required|string|min:6',
        'email' => 'nullable|email|unique:users,email',
    ];

    public function mount(string $role = 'konsumen'): void
    {
        $this->role = $role;
    }

    public function register(): void
    {
        $this->validate();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'nama' => $this->nama,
            'no_hp' => $this->no_hp,
            'password' => Hash::make($this->password),
            'email' => $this->email ?: null,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Setiap user default punya konsumen profile
        KonsumenProfile::create(['user_id' => $user->id]);

        $this->userId = $user->id;
        $this->otp_code = $otp;
        $this->success = 'Registrasi berhasil! OTP: ' . $otp;
        $this->reset(['nama', 'no_hp', 'password', 'email', 'error']);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout("layouts.{$this->role}");
    }
}