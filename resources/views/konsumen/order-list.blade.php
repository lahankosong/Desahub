@extends('layouts.konsumen')

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3 py-2" role="alert" style="font-size: 0.8rem;">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@forelse ($orders as $order)
    @php
        $statusColors = ['dibuat' => '#E8A23C', 'diambil_kurir' => '#1976D2', 'diantar' => '#1976D2', 'selesai' => '#1F5C4F', 'dibatalkan' => '#C4482E', 'gagal_kirim' => '#C4482E'];
        $statusLabels = ['dibuat' => 'Menunggu Konfirmasi', 'diambil_kurir' => 'Diambil Kurir', 'diantar' => 'Sedang Diantar', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan', 'gagal_kirim' => 'Gagal Kirim'];
        $color = $statusColors[$order->status] ?? '#9E9E9E';
        $label = $statusLabels[$order->status] ?? $order->status;
    @endphp
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-bold" style="font-family: var(--font-judul); font-size: 0.85rem;">Order #{{ $order->id }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">🏪 {{ $order->outlet?->nama ?? 'Warung' }} · {{ $order->created_at->format('d/m H:i') }}</small>
                </div>
                <span class="badge rounded-pill px-2 py-0" style="background-color: {{ $color }}; color: #fff; font-size: 0.65rem;">● {{ $label }}</span>
            </div>
            <div class="mt-1" style="font-size: 0.8rem;">
                @foreach ($order->items as $item)
                    @php
                        $produk = $item->sellable;
                        $namaProduk = $produk ? $produk->getNama() : ('Produk #' . $item->sellable_id);
                    @endphp
                    {{ $item->qty }}x {{ $namaProduk }} &nbsp;
                @endforeach
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="fw-bold" style="font-family: var(--font-judul); font-size: 0.85rem; color: var(--warna-aksen-utama);">
                    Rp{{ number_format($order->total_harga, 0, ',', '.') }}
                </span>
                <div class="text-end">
                    <small class="text-muted d-block" style="font-size: 0.65rem;">
                        {{ $order->metode_pengiriman === 'diantar_kurir' ? '🚚 Diantar' : '🏪 Ambil Sendiri' }}
                    </small>
                    <small style="font-size: 0.6rem; color: {{ $order->metode_pembayaran === 'cod' ? 'var(--warna-aksen-utama)' : '#1976D2' }};">
                        {{ $order->metode_pembayaran === 'cod' ? '💵 COD' : '🏦 Transfer' }}
                        @if ($order->status === 'selesai')
                            <span style="color: var(--warna-aksen-kedua);">✔ Lunas</span>
                        @elseif ($order->status === 'dibuat')
                            <span style="color: #9E9E9E;">· Menunggu konfirmasi warung</span>
                        @else
                            <span style="color: #9E9E9E;">⏳</span>
                        @endif
                    </small>
                </div>
            </div>
            {{-- Instruksi untuk ambil sendiri + status dibuat --}}
            @if ($order->metode_pengiriman === 'ambil_sendiri' && $order->status === 'dibuat')
                <div class="alert rounded-3 mt-2 mb-0 py-1 px-2" style="background-color: #FFF8E1; border: 1px solid var(--warna-aksen-utama); font-size: 0.65rem;">
                    <i class="bi bi-info-circle me-1"></i> Silakan datang ke <strong>{{ $order->outlet?->nama ?? 'warung' }}</strong> untuk membayar ({{ $order->metode_pembayaran === 'cod' ? 'tunai' : 'transfer' }}). Warung akan konfirmasi setelah pembayaran.
                </div>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-5">
        <i class="bi bi-receipt display-3" style="color: var(--warna-netral-garis);"></i>
        <p class="mt-3 text-muted" style="font-size: 0.85rem;">Belum ada pesanan</p>
        <a href="{{ route('konsumen.dashboard') }}" class="btn btn-sm rounded-pill px-4"
           style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
            Cari Produk
        </a>
    </div>
@endforelse

@endsection