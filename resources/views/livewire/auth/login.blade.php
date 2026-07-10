<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center">Login — {{ ucfirst($role) }}</h4>

                @if ($error)
                    <div class="alert alert-danger">{{ $error }}</div>
                @endif

                <form wire:submit="login">
                    <div class="mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" wire:model="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        @error('no_hp') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" wire:model="password" class="form-control" required>
                        @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <p class="mt-3 text-center mb-0">
                    Belum punya akun?
                    <a href="/{{ $role }}/register" wire:navigate>Daftar</a>
                </p>
            </div>
        </div>
    </div>
</div>