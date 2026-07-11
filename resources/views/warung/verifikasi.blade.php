@extends('layouts.warung')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3" style="font-family: var(--font-judul);">Verifikasi Outlet</h6>
                <p class="text-muted mb-3" style="font-size: 0.85rem;">
                    Upload dokumen untuk verifikasi lokasi usaha Anda.
                </p>
                <div class="alert alert-info mb-3" style="font-size: 0.85rem;">
                    Status: <strong>{{ $outlet->level_verifikasi ?? 'dasar' }}</strong>
                </div>
                <form method="POST" action="{{ route('warung.verifikasi.submit') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Dokumen Usaha</label>
                        <input type="file" name="dokumen_usaha" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.85rem;">Foto Lokasi</label>
                        <input type="file" name="foto_lokasi" class="form-control">
                    </div>
                    <button type="submit" class="btn w-100 fw-semibold" style="background-color: var(--warna-aksen-kedua); color: #fff;">
                        Kirim Verifikasi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection