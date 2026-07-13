@extends('layouts.warung')

@section('content')
{{-- Order Masuk Warung — data dari database --}}

<div class="d-flex align-items-center justify-content-between mb-3">
{{-- title removed --}}
    @php $baru = $orders->where('status', 'dibuat')->count(); @endphp
    @if ($baru > 0)
        <span class="badge rounded-pill px-3 py-1" style="background-color: var(--warna-aksen-utama); color: #fff;">
            {{ $baru }} Baru
        </span>
    @endif
</div>

{{-- Success / Error Messages --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filter Tab --}}
<div class="d-flex gap-2 mb-4 overflow-auto" style="scrollbar-width: none;">
    <a href="?status=" class="btn btn-sm rounded-pill px-3 fw-semibold text-decoration-none {{ !request('status') ? '' : '' }}"
       style="background-color: {{ !request('status') ? 'var(--warna-aksen-utama)' : '#fff' }}; color: {{ !request('status') ? '#fff' : 'var(--warna-teks)' }}; border: 1px solid var(--warna-netral-garis);">Semua</a>
    <a href="?status=dibuat" class="btn btn-sm rounded-pill px-3 text-decoration-none {{ request('status') === 'dibuat' ? '' : '' }}"
       style="background-color: {{ request('status') === 'dibuat' ? 'var(--warna-aksen-utama)' : '#fff' }}; color: {{ request('status') === 'dibuat' ? '#fff' : 'var(--warna-teks)' }}; border: 1px solid var(--warna-netral-garis);">Baru</a>
    <a href="?status=diambil_kurir" class="btn btn-sm rounded-pill px-3 text-decoration-none {{ request('status') === 'diambil_kurir' ? '' : '' }}"
       style="background-color: {{ request('status') === 'diambil_kurir' ? 'var(--warna-aksen-utama)' : '#fff' }}; color: {{ request('status') === 'diambil_kurir' ? '#fff' : 'var(--warna-teks)' }}; border: 1px solid var(--warna-netral-garis);">Diproses</a>
    <a href="?status=selesai" class="btn btn-sm rounded-pill px-3 text-decoration-none {{ request('status') === 'selesai' ? '' : '' }}"
       style="background-color: {{ request('status') === 'selesai' ? 'var(--warna-aksen-utama)' : '#fff' }}; color: {{ request('status') === 'selesai' ? '#fff' : 'var(--warna-teks)' }}; border: 1px solid var(--warna-netral-garis);">Selesai</a>
    <a href="?status=dibatalkan" class="btn btn-sm rounded-pill px-3 text-decoration-none {{ request('status') === 'dibatalkan' ? '' : '' }}"
       style="background-color: {{ request('status') === 'dibatalkan' ? 'var(--warna-aksen-utama)' : '#fff' }}; color: {{ request('status') === 'dibatalkan' ? '#fff' : 'var(--warna-teks)' }}; border: 1px solid var(--warna-netral-garis);">Dibatalkan</a>
</div>

{{-- Daftar Order --}}
<div class="d-flex flex-column gap-3">
    @php
        $statusColors = [
            'dibuat' => '#E8A23C',
            'diambil_kurir' => 'var(--warna-aksen-utama)',
            'diantar' => '#1976D2',
            'selesai' => 'var(--warna-aksen-kedua)',
            'dibatalkan' => 'var(--warna-peringatan)',
            'gagal_kirim' => 'var(--warna-peringatan)',
        ];
        $statusLabels = [
            'dibuat' => 'Baru',
            'diambil_kurir' => 'Diproses',
            'diantar' => 'Diantar',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            'gagal_kirim' => 'Gagal Kirim',
        ];
    @endphp

    @forelse ($orders as $order)
        @php
            $color = $statusColors[$order->status] ?? '#9E9E9E';
            $label = $statusLabels[$order->status] ?? $order->status;
            $isFinished = in_array($order->status, ['selesai', 'dibatalkan', 'gagal_kirim']);
        @endphp
        <div class="card border-0 shadow-sm" style="{{ $isFinished ? 'opacity: 0.7;' : '' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold" style="font-family: var(--font-judul);">Order #{{ $order->id }}</div>
                        <small class="text-muted">
                            {{ $order->created_at->format('H:i') }} ·
                            @if ($order->jenis_transaksi === 'pos' && $order->buyer_type === 'Umum')
                                🏪 POS (tunai)
                            @elseif ($order->jenis_transaksi === 'pos' && $order->buyer_type === 'PelangganWarung')
                                🏪 POS · {{ $order->buyer?->nama ?? 'Pelanggan' }}
                            @elseif ($order->buyer_type === 'Konsumen')
                                👤 {{ $order->buyer?->nama ?? 'Konsumen #'.$order->buyer_id }}
                            @else
                                👤 {{ $order->buyer_type }} #{{ $order->buyer_id }}
                            @endif
                        </small>
                    </div>
                    <span class="badge rounded-pill px-3 py-1"
                          style="background-color: {{ $color }}; color: #fff; font-size: 0.75rem;">
                        ● {{ $label }}
                    </span>
                </div>
                {{-- Info Pengiriman + Pembayaran --}}
                <div class="mb-2 d-flex flex-wrap align-items-center gap-1">
                    @if ($order->jenis_transaksi === 'pos')
                        <span class="badge rounded-pill px-2 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.65rem;">
                            🏪 Kasir
                        </span>
                    @elseif ($order->metode_pengiriman === 'ambil_sendiri')
                        <span class="badge rounded-pill px-2 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.65rem;">
                            🏪 Ambil Sendiri
                        </span>
                    @elseif ($order->metode_pengiriman === 'diantar_kurir')
                        <span class="badge rounded-pill px-2 py-0" style="background-color: #1976D2; color: #fff; font-size: 0.65rem;">
                            🚚 Diantar Kurir
                        </span>
                    @endif
                    @if ($order->metode_pembayaran === 'cod')
                        <span class="badge rounded-pill px-2 py-0" style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.65rem;">
                            💵 COD
                        </span>
                    @else
                        <span class="badge rounded-pill px-2 py-0" style="background-color: #1976D2; color: #fff; font-size: 0.65rem;">
                            🏦 {{ strtoupper($order->metode_pembayaran) }}
                        </span>
                    @endif
                    {{-- Settlement — di-eager load dari controller: Order::with(['items','settlement']) --}}
                    @php $settlement = $order->settlement ?? null; @endphp
                    @if ($settlement)
                        <span class="badge rounded-pill px-2 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.65rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Dibayar Rp{{ number_format($settlement->jumlah_diterima, 0, ',', '.') }}
                        </span>
                    @elseif ($order->status === 'dibuat')
                        <span class="badge rounded-pill px-2 py-0" style="background-color: #9E9E9E; color: #fff; font-size: 0.65rem;">
                            ⏳ Blm Dibayar
                        </span>
                    @endif
                    @if ($order->alamat_antar)
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;"><i class="bi bi-geo-alt me-1"></i>{{ $order->alamat_antar }}</small>
                    @endif
                </div>
                <div class="mb-3" style="font-size: 0.9rem;">
                    @foreach ($order->items as $item)
                        <div>{{ $item->qty }}x {{ $item->nama_produk ?? ($item->sellable?->getNama() ?? 'Produk #'.$item->sellable_id) }}</div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="font-family: var(--font-judul); color: {{ $isFinished ? '#9E9E9E' : 'var(--warna-aksen-utama)' }};">
                        Rp{{ number_format($order->total_harga, 0, ',', '.') }}
                    </span>
                    @if ($order->status === 'dibuat')
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('warung.order.konfirmasi', $order->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm fw-semibold rounded-pill px-3"
                                        style="background-color: var(--warna-aksen-kedua); color: #fff; border: none;">
                                    {{ $order->metode_pengiriman === 'ambil_sendiri' ? '✔ Selesai' : 'Konfirmasi' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('warung.order.tolak', $order->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm fw-semibold rounded-pill px-3"
                                        style="background-color: #fff; color: var(--warna-peringatan); border: 1px solid var(--warna-peringatan);">
                                    Tolak
                                </button>
                            </form>
                        </div>
                    @elseif ($order->status === 'diambil_kurir')
                        <small class="text-muted">⏳ Menunggu kurir</small>
                    @elseif ($order->status === 'selesai')
                        <small class="text-muted"><i class="bi bi-check-circle-fill me-1" style="color: var(--warna-aksen-kedua);"></i>Selesai {{ $order->selesai_pada?->format('H:i') }}</small>
                    @elseif ($order->status === 'dibatalkan')
                        <small class="text-muted" style="color: var(--warna-peringatan);">Dibatalkan {{ $order->dibatalkan_pada?->format('H:i') }}</small>
                    @else
                        <small class="text-muted">{{ $label }}</small>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <i class="bi bi-inbox display-3" style="color: var(--warna-netral-garis);"></i>
            <p class="mt-3 text-muted">Belum ada order masuk</p>
            <a href="{{ route('warung.kelola-produk') }}" class="btn btn-sm rounded-pill px-4"
               style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
                Kelola Produk Dulu
            </a>
        </div>
    @endforelse
</div>

@endsection