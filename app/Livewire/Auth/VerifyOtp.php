<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Modules\Auth\app\Models\User;

class VerifyOtp extends Component
{
    public $userId = 0;
    public $otp_code = '';
    public $error = '';
    public $success = '';
    public $role = 'konsumen';

    public function mount(string $role = 'konsumen'): void
    {
        $this->role = $role;
    }

    public function verify(): void
    {
        $this->validate([
            'userId' => 'required|exists:users,id',
            'otp_code' => 'required|string|size:6',
        ]);

        $user = User::find($this->userId);

        if ($user->no_hp_verified_at) {
            $this->error = 'No HP sudah diverifikasi. Silakan login.';
            return;
        }

        if ($user->otp_code !== $this->otp_code) {
            $this->error = 'OTP tidak cocok.';
            return;
        }

        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            $this->error = 'OTP sudah kadaluarsa. Silakan register ulang.';
            return;
        }

        $user->update([
            'no_hp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $this->success = 'Verifikasi berhasil! Silakan login.';
    }

    public function render()
    {
        return view('livewire.auth.verify-otp')
            ->layout("layouts.{$this->role}");
    }
}