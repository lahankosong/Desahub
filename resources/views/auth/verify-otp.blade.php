<x-dynamic-component :component="'layouts.' . $role">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3 text-center">Verifikasi OTP — {{ ucfirst($role) }}</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="/{{ $role }}/verify-otp">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">User ID (dari registrasi)</label>
                            <input type="number" name="user_id" class="form-control"
                                   value="{{ old('user_id', session('user_id')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode OTP (6 digit)</label>
                            <input type="text" name="otp_code" class="form-control" maxlength="6"
                                   placeholder="123456" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Verifikasi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>