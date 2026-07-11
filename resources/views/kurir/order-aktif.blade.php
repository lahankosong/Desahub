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

@if ($orders->count() > 0)
    <div class="d-flex flex-column gap-2">
        @foreach ($orders as $order)
            @php
                $statusColors = ['diambil_kurir' => '#E8A23C', 'diantar' => '#1976D2'];
                $statusLabels = ['diambil_kurir' => 'Diambil', 'diantar' => 'Sedang Diantar'];
                $color = $statusColors[$order->status] ?? '#9E9E9E';
                $label = $statusLabels[$order->status] ?? $order->status;
            @endphp
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold" style="font-family: var(--font-judul); font-size: 0.85rem;">Order #{{ $order->id }}</div>
                            <small class="text-muted" style="font-size: 0.7rem;">🏪 {{ $order->outlet?->nama ?? 'Warung' }}</small>
                        </div>
                        <span class="badge rounded-pill px-2 py-0" style="background-color: {{ $color }}; color: #fff; font-size: 0.65rem;">● {{ $label }}</span>
                    </div>
                    @if ($order->alamat_antar)
                        <div class="mb-2"><small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-geo-alt me-1"></i>Antar ke: {{ $order->alamat_antar }}</small></div>
                    @endif
                    <div class="mb-2">
                        <small style="font-size: 0.65rem; color: {{ $order->metode_pembayaran === 'cod' ? 'var(--warna-aksen-utama)' : '#1976D2' }};">
                            {{ $order->metode_pembayaran === 'cod' ? '💵 COD' : '🏦 Transfer' }}
                        </small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);">Rp{{ number_format($order->total_harga, 0, ',', '.') }}</span>
                        <div class="d-flex gap-2">
                            @if ($order->status === 'diambil_kurir')
                                <form method="POST" action="{{ route('kurir.order.update-status', $order->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="diantar">
                                    <button class="btn btn-sm rounded-pill px-3 fw-semibold"
                                            style="background-color: #1976D2; color: #fff; border: none; font-size: 0.7rem;">
                                        Sedang Diantar
                                    </button>
                                </form>
                            @elseif ($order->status === 'diantar')
                                <form method="POST" action="{{ route('kurir.order.update-status', $order->id) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="selesai">
                                    <button class="btn btn-sm rounded-pill px-3 fw-semibold"
                                            style="background-color: var(--warna-aksen-kedua); color: #fff; border: none; font-size: 0.7rem;">
                                        ✔ Selesai
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-box2 display-3" style="color: var(--warna-netral-garis);"></i>
        <p class="mt-3 text-muted">Belum ada order yang diambil</p>
        <a href="{{ route('kurir.order-tersedia') }}" class="btn btn-sm rounded-pill px-4" style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
            Lihat Order Tersedia
        </a>
    </div>
@endif

<nav class="fixed-bottom bg-white border-top shadow" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="d-flex justify-content-around py-2">
        <a href="{{ route('kurir.dashboard') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-house-door fs-5"></i><div style="font-size: 0.65rem;">Beranda</div>
        </a>
        <a href="{{ route('kurir.order-tersedia') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-list-check fs-5"></i><div style="font-size: 0.65rem;">Order</div>
        </a>
        <a href="{{ route('kurir.order-aktif') }}" class="text-decoration-none text-center active" style="color: var(--warna-aksen-utama);">
            <i class="bi bi-box-arrow-in-right fs-5"></i><div style="font-size: 0.65rem;">Aktif</div>
        </a>
        <a href="#" class="text-decoration-none text-center text-muted">
            <i class="bi bi-person fs-5"></i><div style="font-size: 0.65rem;">Profil</div>
        </a>
    </div>
</nav>
<div style="height: 70px;"></div>
@endsection