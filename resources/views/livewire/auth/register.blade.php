<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center">Daftar — {{ ucfirst($role) }}</h4>

                @if ($success)
                    <div class="alert alert-success">
                        {{ $success }}
                        <div class="mt-2">
                            <a href="/{{ $role }}/verify-otp" wire:navigate class="btn btn-sm btn-outline-success">
                                Verifikasi OTP
                            </a>
                        </div>
                    </div>
                @else
                    @if ($error)
                        <div class="alert alert-danger">{{ $error }}</div>
                    @endif

                    <form wire:submit="register">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" wire:model="nama" class="form-control" placeholder="Nama lengkap" required>
                            @error('nama') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" wire:model="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                            @error('no_hp') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (opsional)</label>
                            <input type="email" wire:model="email" class="form-control" placeholder="email@example.com">
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" wire:model="password" class="form-control" required>
                            @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Daftar</button>
                    </form>
                @endif

                <p class="mt-3 text-center mb-0">
                    Sudah punya akun?
                    <a href="/{{ $role }}/login" wire:navigate>Login</a>
                </p>
            </div>
        </div>
    </div>
</div>