@extends('layouts.kurir')

@section('content')

{{-- Toggle Online/Offline --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body text-center py-5">
        @php $online = $kurir && $kurir->is_online; @endphp
        <div style="font-size: 5rem; color: {{ $online ? 'var(--warna-aksen-kedua)' : 'var(--warna-netral-garis)' }};">
            <i class="bi bi-toggle-{{ $online ? 'on' : 'off' }}"></i>
        </div>
        <h4 class="mt-3" style="font-family: var(--font-judul); color: {{ $online ? 'var(--warna-aksen-kedua)' : '' }};">
            ● {{ $online ? 'ONLINE' : 'OFFLINE' }}
        </h4>
        <p class="text-muted mb-4">{{ $online ? 'Anda sedang menerima order' : 'Ketuk untuk Online dan mulai menerima order' }}</p>
        <form method="POST" action="{{ route('kurir.toggle-online') }}">
            @csrf
            <input type="hidden" name="online" value="{{ $online ? 0 : 1 }}">
            <button type="submit" class="btn rounded-pill px-5 py-3 fs-5 fw-semibold w-75"
                    style="background-color: {{ $online ? 'var(--warna-peringatan)' : 'var(--warna-netral-garis)' }}; color: #fff; border: none;">
                {{ $online ? 'Nonaktifkan' : 'Aktifkan Online' }}
            </button>
        </form>
    </div>
</div>

{{-- Ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-6">
        <a href="{{ route('kurir.order-tersedia') }}" class="card border-0 shadow-sm text-decoration-none">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-list-check fs-4" style="color: var(--warna-aksen-utama);"></i>
                    <div>
                        <div class="fs-4 fw-bold" style="font-family: var(--font-judul);">{{ $orderTersedia }}</div>
                        <small class="text-muted">Order Tersedia</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6">
        <a href="{{ route('kurir.order-aktif') }}" class="card border-0 shadow-sm text-decoration-none">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-box-arrow-in-right fs-4" style="color: var(--warna-aksen-kedua);"></i>
                    <div>
                        <div class="fs-4 fw-bold" style="font-family: var(--font-judul);">{{ $orderAktif }}</div>
                        <small class="text-muted">Aktif</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2 mb-3" style="font-size: 0.8rem;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<nav class="fixed-bottom bg-white border-top shadow" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="d-flex justify-content-around py-2">
        <a href="{{ route('kurir.dashboard') }}" class="text-decoration-none text-center active" style="color: var(--warna-aksen-utama);">
            <i class="bi bi-house-door-fill fs-5"></i><div style="font-size: 0.65rem;">Beranda</div>
        </a>
        <a href="{{ route('kurir.order-tersedia') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-list-check fs-5"></i><div style="font-size: 0.65rem;">Order</div>
        </a>
        <a href="{{ route('kurir.order-aktif') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-box-arrow-in-right fs-5"></i><div style="font-size: 0.65rem;">Aktif</div>
        </a>
        <a href="#" class="text-decoration-none text-center text-muted">
            <i class="bi bi-person fs-5"></i><div style="font-size: 0.65rem;">Profil</div>
        </a>
    </div>
</nav>
<div style="height: 70px;"></div>
@endsection