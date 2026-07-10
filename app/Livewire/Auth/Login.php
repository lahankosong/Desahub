<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\app\Models\User;

class Login extends Component
{
    public $no_hp = '';
    public $password = '';
    public $error = '';
    public $role = 'konsumen'; // default, akan di-override dari route

    protected $rules = [
        'no_hp' => 'required|string|min:10',
        'password' => 'required|string|min:6',
    ];

    public function mount(string $role = 'konsumen'): void
    {
        $this->role = $role;
    }

    public function login(): void
    {
        $this->validate();

        // Gunakan guard 'web' (session-based, sesuai PWA)
        if (Auth::guard('web')->attempt(['no_hp' => $this->no_hp, 'password' => $this->password])) {
            $user = Auth::user();
            session(['active_role' => $this->role]);
            $this->redirectRoute("{$this->role}.dashboard");
            return;
        }

        $this->error = 'No HP atau password salah.';
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout("layouts.{$this->role}");
    }
}