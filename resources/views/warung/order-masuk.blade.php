@extends('layouts.warung')

@section('content')
{{-- Order Masuk Warung — data dari database --}}

<div class="d-flex align-items-center justify-content-between mb-2.5">
    @php $baru = $orders->where('status', 'dibuat')->count(); @endphp
    @if ($baru > 0)
        <span class="badge rounded-pill px-2.5 py-0.5" style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.65rem;">
            {{ $baru }} Baru
        </span>
    @endif
</div>

{{-- Success / Error Messages --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-2 py-1.5 px-2 mb-2" role="alert" style="font-size: 0.75rem;">
        {{ session('success') }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-2 py-1.5 px-2 mb-2" role="alert" style="font-size: 0.75rem;">
        {{ $errors->first() }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filter Tab --}}
<div class="d-flex gap-1 mb-2.5 overflow-auto flex-nowrap" style="scrollbar-width: none; -ms-overflow-style: none;">
    <a href="?status=&jenis=" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none fw-medium {{ !request('status') && !request('jenis') ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ !request('status') && !request('jenis') ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ !request('status') && !request('jenis') ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       Semua
    </a>
    <a href="?status=&jenis=pos" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none {{ request('jenis') === 'pos' ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ request('jenis') === 'pos' ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ request('jenis') === 'pos' ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       🏪 POS
    </a>
    <a href="?status=&jenis=online" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none {{ request('jenis') === 'online' ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ request('jenis') === 'online' ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ request('jenis') === 'online' ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       🛒 Online
    </a>
    <a href="?status=dibuat" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none {{ request('status') === 'dibuat' ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ request('status') === 'dibuat' ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ request('status') === 'dibuat' ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       Baru
    </a>
    <a href="?status=diambil_kurir" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none {{ request('status') === 'diambil_kurir' ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ request('status') === 'diambil_kurir' ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ request('status') === 'diambil_kurir' ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       Diproses
    </a>
    <a href="?status=selesai" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none {{ request('status') === 'selesai' ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ request('status') === 'selesai' ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ request('status') === 'selesai' ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       Selesai
    </a>
    <a href="?status=dibatalkan" 
       class="btn btn-sm rounded-pill px-2.5 py-0.5 text-decoration-none {{ request('status') === 'dibatalkan' ? 'text-white' : 'text-secondary' }}"
       style="background-color: {{ request('status') === 'dibatalkan' ? 'var(--warna-aksen-utama)' : '#fff' }}; border: 1px solid {{ request('status') === 'dibatalkan' ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; font-size: 0.7rem;">
       Batal
    </a>
</div>

{{-- Daftar Order --}}
<div class="d-flex flex-column gap-2">
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
        <div class="card border-0 shadow-sm rounded-2 {{ $isFinished ? 'opacity-75' : '' }}">
            <div class="card-body py-2 px-2.5">
                <!-- Header: Order ID + Status -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <span class="fw-bold" style="font-family: var(--font-judul); font-size: 0.85rem;">
                            #{{ $order->id }}
                            @if ($order->jenis_transaksi === 'pos')
                                <span class="badge bg-success ms-1" style="font-size:0.6rem; padding: 1px 5px;">POS</span>
                            @else
                                <span class="badge bg-primary ms-1" style="font-size:0.6rem; padding: 1px 5px;">ONLINE</span>
                            @endif
                        </span>
                        <small class="text-muted ms-1" style="font-size:0.65rem;">
                            {{ $order->created_at->format('H:i') }}
                        </small>
                    </div>
                    <span class="badge rounded-pill px-2 py-0.5"
                          style="background-color: {{ $color }}; color: #fff; font-size: 0.7rem;">
                        ● {{ $label }}
                    </span>
                </div>

                <!-- Buyer Info -->
                <div class="mb-1" style="font-size:0.7rem;">
                    @if ($order->jenis_transaksi === 'pos' && $order->buyer_type === 'Umum')
                        🏪 POS (Tunai)
                    @elseif ($order->jenis_transaksi === 'pos' && $order->buyer_type === 'PelangganWarung')
                        🏪 {{ $order->buyer?->nama ?? 'Pelanggan' }}
                    @elseif ($order->buyer_type === 'Konsumen')
                        👤 {{ $order->buyer?->nama ?? 'Konsumen' }}
                    @else
                        👤 {{ $order->buyer_type }} #{{ $order->buyer_id }}
                    @endif
                </div>

                <!-- Badges: Pengiriman & Pembayaran -->
                <div class="mb-1.5 d-flex flex-wrap align-items-center gap-0.5">
                    @if ($order->jenis_transaksi === 'pos')
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.6rem;">
                            🏪 Kasir
                        </span>
                    @elseif ($order->metode_pengiriman === 'ambil_sendiri')
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.6rem;">
                            🏪 Ambil Sendiri
                        </span>
                    @elseif ($order->metode_pengiriman === 'diantar_kurir')
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: #1976D2; color: #fff; font-size: 0.6rem;">
                            🚚 Antar Kurir
                        </span>
                    @endif

                    @if ($order->metode_pembayaran === 'cod')
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.6rem;">
                            💵 COD
                        </span>
                    @else
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: #1976D2; color: #fff; font-size: 0.6rem;">
                            🏦 {{ strtoupper($order->metode_pembayaran) }}
                        </span>
                    @endif

                    @php $settlement = $order->settlement ?? null; @endphp
                    @if ($settlement)
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.6rem;">
                            ✅ Rp{{ number_format($settlement->jumlah_diterima, 0, ',', '.') }}
                        </span>
                    @elseif ($order->status === 'dibuat')
                        <span class="badge rounded-pill px-1.5 py-0" style="background-color: #9E9E9E; color: #fff; font-size: 0.6rem;">
                            ⏳ Belum Bayar
                        </span>
                    @endif
                </div>

                <!-- Alamat (if any) -->
                @if ($order->alamat_antar)
                    <div class="text-muted mb-1" style="font-size:0.65rem;">
                        <i class="bi bi-geo-alt me-1"></i>{{ $order->alamat_antar }}
                    </div>
                @endif

                <!-- Items List -->
                <div class="mb-1.5" style="font-size:0.75rem;">
                    @foreach ($order->items as $item)
                        <div class="text-secondary">{{ $item->qty }}× {{ $item->nama_produk ?? ($item->sellable?->getNama() ?? 'Produk #'.$item->sellable_id) }}</div>
                    @endforeach
                </div>

                <!-- Footer: Total + Actions -->
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="border-color: var(--warna-netral-garis);">
                    <span class="fw-bold" style="font-family: var(--font-judul); color: {{ $isFinished ? '#9E9E9E' : 'var(--warna-aksen-utama)' }}; font-size:0.85rem;">
                        Rp{{ number_format($order->total_harga, 0, ',', '.') }}
                    </span>
                    <div class="d-flex gap-1 align-items-center">
                        <button class="btn btn-sm fw-medium rounded-pill px-2 py-0.5"
                                style="background-color: #fff; color: var(--warna-teks); border: 1px solid var(--warna-netral-garis); font-size:0.65rem;"
                                onclick="showStruk({{ $order->id }})">
                            🖨️ Struk
                        </button>

                        @if ($order->status === 'dibuat')
                            <form method="POST" action="{{ route('warung.order.konfirmasi', $order->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm fw-medium rounded-pill px-2 py-0.5"
                                        style="background-color: var(--warna-aksen-kedua); color: #fff; border: none; font-size:0.65rem;">
                                    {{ $order->metode_pengiriman === 'ambil_sendiri' ? '✔ Selesai' : '✅ Konfirm' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('warung.order.tolak', $order->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm fw-medium rounded-pill px-2 py-0.5"
                                        style="background-color: #fff; color: var(--warna-peringatan); border: 1px solid var(--warna-peringatan); font-size:0.65rem;">
                                    ✕ Tolak
                                </button>
                            </form>
                        @elseif ($order->status === 'diambil_kurir')
                            <small class="text-muted" style="font-size:0.65rem;">⏳ Kurir</small>
                        @elseif ($order->status === 'selesai')
                            <small class="text-muted" style="font-size:0.65rem;">
                                <i class="bi bi-check-circle-fill me-0.5" style="color: var(--warna-aksen-kedua);"></i>
                                {{ $order->selesai_pada?->format('H:i') }}
                            </small>
                        @elseif ($order->status === 'dibatalkan')
                            <small class="text-muted" style="font-size:0.65rem; color: var(--warna-peringatan);">
                                ✕ {{ $order->dibatalkan_pada?->format('H:i') }}
                            </small>
                        @else
                            <small class="text-muted" style="font-size:0.65rem;">{{ $label }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4">
            <i class="bi bi-inbox display-4" style="color: var(--warna-netral-garis);"></i>
            <p class="mt-2 text-muted mb-2" style="font-size: 0.85rem;">Belum ada order masuk</p>
            <a href="{{ route('warung.kelola-produk') }}" class="btn btn-sm rounded-pill px-3 py-1"
               style="background-color: var(--warna-aksen-utama); color: #fff; border: none; font-size: 0.75rem;">
                Kelola Produk
            </a>
        </div>
    @endforelse
</div>


{{-- ========== MODAL: STRUK / NOTA ========== --}}
<div class="modal fade" id="strukModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-1.5">
                <h6 class="modal-title fw-bold mb-0" style="font-size: 0.85rem;">
                    <i class="bi bi-receipt me-1"></i> Struk
                </h6>
                <div class="d-flex gap-1.5">
                    <button class="btn btn-sm btn-outline-primary py-0.5 px-2" onclick="printStruk()" title="Print" style="font-size: 0.7rem;">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button class="btn btn-sm btn-outline-success py-0.5 px-2" onclick="downloadStruk()" title="Download PNG" style="font-size: 0.7rem;">
                        <i class="bi bi-download"></i> PNG
                    </button>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body py-2" style="max-height: 80vh; overflow-y: auto;">
                <div id="struk-container" class="text-center" style="font-family: 'Courier New', monospace; font-size: 0.7rem; line-height: 1.3;">
                    <div class="text-center py-4">
                        <span class="spinner-border spinner-border-sm me-1"></span> Memuat...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function showStruk(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('strukModal'));
    const container = document.getElementById('struk-container');
    container.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-1"></span> Memuat...</div>';
    modal.show();

    try {
        const resp = await fetch(`/warung/pos/struk/${orderId}`);
        if (!resp.ok) {
            container.innerHTML = '<div class="alert alert-danger py-2" style="font-size:0.75rem;">Gagal memuat struk.</div>';
            return;
        }
        const html = await resp.text();
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger py-2" style="font-size:0.75rem;">Gagal: ' + e.message + '</div>';
    }
}

function printStruk() {
    const container = document.getElementById('struk-container');
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    printWindow.document.write(`
        <html>
        <head>
            <title>Struk Pembelian</title>
            <style>
                body { font-family: 'Courier New', monospace; font-size: 11px; margin: 0; padding: 8px; }
                .receipt { max-width: 280px; margin: 0 auto; }
                .text-center { text-align: center; }
                .fw-bold { font-weight: bold; }
                .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 4px; }
            </style>
        </head>
        <body>
            <div class="receipt">${container.innerHTML}</div>
            <script>window.onload = function() { window.print(); }<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

async function downloadStruk() {
    const container = document.getElementById('struk-container');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 350;
    canvas.height = 700;

    const text = container.innerText;
    const lines = text.split('\n');
    ctx.fillStyle = '#000';
    ctx.font = '11px monospace';
    let y = 18;
    lines.forEach(line => {
        ctx.fillText(line, 8, y);
        y += 15;
    });

    const link = document.createElement('a');
    link.download = `struk-${Date.now()}.png`;
    link.href = canvas.toDataURL();
    link.click();
}
</script>

@endsection