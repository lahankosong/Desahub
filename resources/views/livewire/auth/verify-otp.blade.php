<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center">Verifikasi OTP — {{ ucfirst($role) }}</h4>

                @if ($success)
                    <div class="alert alert-success">
                        {{ $success }}
                        <div class="mt-2">
                            <a href="/{{ $role }}/login" wire:navigate class="btn btn-sm btn-outline-success">
                                Login Sekarang
                            </a>
                        </div>
                    </div>
                @else
                    @if ($error)
                        <div class="alert alert-danger">{{ $error }}</div>
                    @endif

                    <form wire:submit="verify">
                        <div class="mb-3">
                            <label class="form-label">User ID (dari registrasi)</label>
                            <input type="number" wire:model="userId" class="form-control" required>
                            @error('userId') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode OTP (6 digit)</label>
                            <input type="text" wire:model="otp_code" class="form-control" maxlength="6" placeholder="123456" required>
                            @error('otp_code') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Verifikasi</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>