@extends('layouts.warung')

@section('content')

@if (! $data)
    <div class="text-center py-5">
        <i class="bi bi-shop display-3" style="color: var(--warna-netral-garis);"></i>
        <p class="mt-3 text-muted">Daftarkan outlet terlebih dahulu untuk melihat laporan</p>
        <a href="{{ route('warung.profil') }}" class="btn btn-sm rounded-pill px-4"
           style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">Daftarkan Outlet</a>
    </div>
@else
    {{-- Omzet --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Omzet Hari Ini</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 0.9rem; color: var(--warna-aksen-utama);">Rp{{ number_format($data['omzetHariIni'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Minggu Ini</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 0.9rem; color: var(--warna-aksen-kedua);">Rp{{ number_format($data['omzetMingguIni'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Bulan Ini</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 0.9rem; color: var(--warna-peringatan);">Rp{{ number_format($data['omzetBulanIni'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Order --}}
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Order Hari Ini</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 1.2rem;">{{ $data['totalOrderHariIni'] }} order</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Order Minggu Ini</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 1.2rem;">{{ $data['totalOrderMingguIni'] }} order</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Produk & Margin --}}
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Total Produk</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 1.2rem; color: var(--warna-aksen-utama);">{{ $data['totalProduk'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <small class="text-muted" style="font-size: 0.65rem;">Produk Habis</small>
                    <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 1.2rem; color: var(--warna-peringatan);">{{ $data['produkHabis'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <small class="text-muted" style="font-size: 0.65rem;">Estimasi Margin (stok × margin per produk)</small>
            <div class="fw-bold mt-1" style="font-family: var(--font-judul); font-size: 1.2rem; color: var(--warna-aksen-kedua);">Rp{{ number_format($data['totalMargin'], 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Top Produk --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2" style="font-family: var(--font-judul); font-size: 0.8rem;">Top 5 Produk</h6>
            @if ($data['topProduk']->count() > 0)
                @foreach ($data['topProduk'] as $tp)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small style="font-size: 0.75rem;" class="text-truncate" style="max-width: 60%;">{{ $tp['nama'] }}</small>
                        <small class="text-muted" style="font-size: 0.7rem;">{{ $tp['total_qty'] }}x · Rp{{ number_format($tp['total_omzet'], 0, ',', '.') }}</small>
                    </div>
                @endforeach
            @else
                <small class="text-muted">Belum ada data penjualan</small>
            @endif
        </div>
    </div>

    {{-- COD Settlements --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2" style="font-family: var(--font-judul); font-size: 0.8rem;">Kas COD</h6>
            <div class="row">
                <div class="col-6">
                    <small class="text-muted" style="font-size: 0.65rem;">Belum Disetor</small>
                    <div class="fw-bold" style="font-family: var(--font-judul); color: var(--warna-peringatan);">Rp{{ number_format($data['codBelumDisetor'], 0, ',', '.') }}</div>
                </div>
                <div class="col-6">
                    <small class="text-muted" style="font-size: 0.65rem;">Sudah Disetor</small>
                    <div class="fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-kedua);">Rp{{ number_format($data['codSudahDisetor'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
@endif

<nav class="fixed-bottom bg-white border-top shadow" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="d-flex justify-content-around py-2">
        <a href="{{ route('warung.dashboard') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-house-door fs-5"></i><div style="font-size: 0.65rem;">Beranda</div>
        </a>
        <a href="{{ route('warung.order-masuk') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-receipt fs-5"></i><div style="font-size: 0.65rem;">Order</div>
        </a>
        <a href="{{ route('warung.kelola-produk') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-box-seam fs-5"></i><div style="font-size: 0.65rem;">Produk</div>
        </a>
        <a href="{{ route('warung.laporan') }}" class="text-decoration-none text-center active" style="color: var(--warna-aksen-utama);">
            <i class="bi bi-graph-up fs-5"></i><div style="font-size: 0.65rem;">Laporan</div>
        </a>
        <a href="{{ route('warung.profil') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-person fs-5"></i><div style="font-size: 0.65rem;">Profil</div>
        </a>
    </div>
</nav>
<div style="height: 70px;"></div>
@endsection