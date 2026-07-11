@extends('layouts.warung')

@section('content')

{{-- Header + Badge Tier --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0" style="font-family: var(--font-judul);">{{ $outlet?->nama ?? 'Warung Saya' }}</h4>
        <small class="text-muted">{{ $outlet?->alamat ?? 'Lengkapi profil outlet Anda' }}</small>
    </div>
    <span class="badge rounded-pill px-3 py-2 fs-6"
          style="background-color: var(--warna-aksen-kedua); color: #fff;">
        ● {{ strtoupper($warung?->tier ?? 'Biasa') }}
    </span>
</div>

{{-- Notifikasi --}}
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="card border-0 shadow-sm" style="background-color: #FFF8E1;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bell-fill fs-4" style="color: var(--warna-aksen-utama);"></i>
                    <div>
                        <div class="fs-4 fw-bold" style="font-family: var(--font-judul);">{{ $orderBaru }}</div>
                        <small>Order Baru</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 shadow-sm" style="background-color: #FFEBEE;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-4" style="color: var(--warna-peringatan);"></i>
                    <div>
                        <div class="fs-4 fw-bold" style="font-family: var(--font-judul);">{{ $stokTipis }}</div>
                        <small>Stok Tipis</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Ringkasan Hari Ini --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="text-muted mb-3">Ringkasan Hari Ini</h6>
        <div class="row text-center">
            <div class="col-6 border-end">
                <div class="fs-3 fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);">{{ $orderHariIni }}</div>
                <small class="text-muted">Order Masuk</small>
            </div>
            <div class="col-6">
                <div class="fs-3 fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-kedua);">Rp{{ number_format($omzetHariIni, 0, ',', '.') }}</div>
                <small class="text-muted">Omzet Hari Ini</small>
            </div>
        </div>
    </div>
</div>

{{-- Kurir Aktif & Top Product --}}
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul); font-size: 0.9rem;">
                    <i class="bi bi-truck me-1" style="color: var(--warna-aksen-kedua);"></i>
                    Kurir Aktif
                </h6>
                @php
                    $kurirAktif = \Modules\Auth\app\Models\KurirProfile::where('is_online', true)->count();
                @endphp
                <div class="text-center py-3">
                    <div class="fs-2 fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-kedua);">{{ $kurirAktif }}</div>
                    <small class="text-muted">Kurir Online</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul); font-size: 0.9rem;">
                    <i class="bi bi-trophy me-1" style="color: var(--warna-aksen-utama);"></i>
                    Top Product
                </h6>
                @php
                    $topProduk = null;
                    if ($outlet) {
                        $topProduk = \Modules\Order\app\Models\OrderItem::select('nama_produk', \Illuminate\Support\Facades\DB::raw('SUM(qty) as total_qty'))
                            ->whereHas('order', function($q) use ($outlet) {
                                $q->where('outlet_id', $outlet->id)->whereIn('status', ['selesai', 'diantar', 'diambil_kurir']);
                            })
                            ->groupBy('nama_produk')
                            ->orderBy('total_qty', 'desc')
                            ->first();
                    }
                @endphp
                @if ($topProduk)
                    <div class="text-center py-3">
                        <div class="fw-bold mb-1" style="font-size: 0.85rem;">{{ $topProduk->nama_produk }}</div>
                        <small class="text-muted">{{ $topProduk->total_qty }}x terjual</small>
                    </div>
                @else
                    <div class="text-center py-3 text-muted" style="font-size: 0.85rem;">Belum ada data</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tombol Cepat --}}
<div class="row g-3 mb-4">
    <div class="col-6">
        <a href="{{ route('warung.order-masuk') }}"
           class="card border-0 shadow-sm text-decoration-none h-100"
           style="background-color: var(--warna-aksen-utama);">
            <div class="card-body text-center text-white py-4">
                <i class="bi bi-receipt display-6"></i>
                <div class="mt-2 fw-semibold">Order Masuk</div>
            </div>
        </a>
    </div>
    <div class="col-6">
        <a href="{{ route('warung.kelola-produk') }}"
           class="card border-0 shadow-sm text-decoration-none h-100"
           style="background-color: var(--warna-aksen-kedua);">
            <div class="card-body text-center text-white py-4">
                <i class="bi bi-box-seam display-6"></i>
                <div class="mt-2 fw-semibold">{{ $totalProduk }} Produk</div>
            </div>
        </a>
    </div>
</div>

@endsection