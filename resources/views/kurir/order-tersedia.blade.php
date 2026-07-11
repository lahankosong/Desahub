@extends('layouts.kurir')

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 py-2 mb-3" style="font-size: 0.8rem;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 py-2 mb-3" style="font-size: 0.8rem;">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<p class="text-muted mb-3" style="font-size: 0.85rem;">Anda harus Online untuk melihat dan mengambil order</p>

@if ($orders->count() > 0)
    <div class="d-flex flex-column gap-2">
        @foreach ($orders as $order)
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold" style="font-family: var(--font-judul); font-size: 0.85rem;">Order #{{ $order->id }}</div>
                            <small class="text-muted" style="font-size: 0.7rem;">🏪 {{ $order->outlet?->nama ?? 'Warung' }} · {{ $order->created_at->format('H:i') }}</small>
                            @if ($order->alamat_antar)
                                <div class="mt-1"><small class="text-muted" style="font-size: 0.65rem;"><i class="bi bi-geo-alt me-1"></i>{{ $order->alamat_antar }}</small></div>
                            @endif
                            <small class="text-muted mt-1 d-block" style="font-size: 0.65rem;">
                                {{ $order->metode_pembayaran === 'cod' ? '💵 COD' : '🏦 Transfer' }}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold" style="font-family: var(--font-judul); font-size: 0.9rem; color: var(--warna-aksen-utama);">
                                Rp{{ number_format($order->total_harga, 0, ',', '.') }}
                            </span>
                            <form method="POST" action="{{ route('kurir.order.klaim', $order->id) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-sm rounded-pill px-3 fw-semibold"
                                        style="background-color: var(--warna-aksen-kedua); color: #fff; border: none; font-size: 0.7rem;">
                                    AMBIL ORDER
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-inbox display-3" style="color: var(--warna-netral-garis);"></i>
        <p class="mt-3 text-muted">Belum ada order tersedia di sekitar Anda</p>
    </div>
@endif

<nav class="fixed-bottom bg-white border-top shadow" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="d-flex justify-content-around py-2">
        <a href="{{ route('kurir.dashboard') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-house-door fs-5"></i><div style="font-size: 0.65rem;">Beranda</div>
        </a>
        <a href="{{ route('kurir.order-tersedia') }}" class="text-decoration-none text-center active" style="color: var(--warna-aksen-utama);">
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