@extends('layouts.konsumen')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="font-family: var(--font-judul);">Profil Saya</h6>
                <form method="POST" action="{{ route('konsumen.profil.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="{{ old('nama', auth()->user()->nama) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', auth()->user()->no_hp) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
                    </div>
                    <button type="submit" class="btn w-100 fw-semibold" style="background-color: var(--warna-aksen-utama); color: #fff;">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="font-family: var(--font-judul);">Ganti Password</h6>
                <form method="POST" action="{{ route('konsumen.profil.password') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Password Lama</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Konfirmasi Password Baru</label>
                        <input type="password" name="new_password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn w-100 fw-semibold" style="background-color: var(--warna-aksen-kedua); color: #fff;">Ganti Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection