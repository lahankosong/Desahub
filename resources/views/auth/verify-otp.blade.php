@extends('layouts.' . $role)

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h4 class="mb-3 text-center">Derum — {{ ucfirst($role) }}</h4>

                @php $otpDev = session('otp_code'); $email = session('email'); @endphp
                @if ($otpDev)
                    <div class="alert alert-info text-center mb-3 py-2" style="font-size: 0.9rem;">
                        <strong>🔑 Kode OTP (DEV):</strong>
                        <span style="font-family: monospace; font-size: 1.5rem; letter-spacing: 4px;">{{ $otpDev }}</span>
                        @if ($email)
                            <br><small class="text-muted">📧 Dikirim ke: <strong>{{ $email }}</strong> (cek inbox/spam)</small>
                        @endif
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
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
@endsection