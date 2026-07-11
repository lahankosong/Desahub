@extends('layouts.konsumen')

@section('content')

{{-- Ringkasan Utama --}}
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="card border-0 shadow-sm" style="background-color: #FFF8E1;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-receipt fs-4" style="color: var(--warna-aksen-utama);"></i>
                    <div>
                        <div class="fs-4 fw-bold" style="font-family: var(--font-judul);">{{ $totalOrder }}</div>
                        <small>Total Order</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 shadow-sm" style="background-color: #E8F5E9;">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-currency-dollar fs-4" style="color: var(--warna-aksen-kedua);"></i>
                    <div>
                        <div class="fs-4 fw-bold" style="font-family: var(--font-judul);">Rp{{ number_format($totalBelanja, 0, ',', '.') }}</div>
                        <small>Total Belanja</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Bulan --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2">
            <label class="fw-semibold mb-0" style="font-size: 0.85rem;">Periode:</label>
            <select id="bulan-filter" class="form-select form-select-sm" style="max-width: 200px; border-color: var(--warna-netral-garis);"
                    onchange="gantiBulan(this.value)">
                @php
                    $bulanList = [];
                    for ($i = 0; $i < 12; $i++) {
                        $bulanList[] = date('Y-m', strtotime("-$i months"));
                    }
                @endphp
                @foreach ($bulanList as $bulan)
                    <option value="{{ $bulan }}" {{ $bulan == $bulanFilter ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $bulan)->format('F Y') }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

{{-- Grafik Pengeluaran Mingguan --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul);">Pengeluaran Mingguan</h6>
        <div class="d-flex align-items-end gap-2" style="height: 150px;">
            @foreach ($grafikMingguan as $minggu)
                @php
                    $max = max($grafikMingguan->max('total'), 1);
                    $height = ($minggu->total / $max) * 100;
                @endphp
                <div class="flex-grow-1 text-center">
                    <div class="mx-auto rounded-pill" style="width: 100%; height: {{ $height }}px; background-color: var(--warna-aksen-utama); opacity: 0.8;"></div>
                    <small class="d-block mt-1 text-muted" style="font-size: 0.65rem;">{{ $minggu->label }}</small>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Top 5 Produk Favorit --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul);">Top 5 Produk Favorit</h6>
        @forelse ($topProduk as $item)
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-2 py-1" style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.75rem;">{{ $loop->iteration }}</span>
                    <span style="font-size: 0.85rem;">{{ $item['nama'] }}</span>
                </div>
                <span class="fw-semibold" style="font-size: 0.85rem; color: var(--warna-aksen-kedua);">{{ $item['total_qty'] }}x</span>
            </div>
        @empty
            <p class="text-muted text-center py-3" style="font-size: 0.85rem;">Belum ada data</p>
        @endforelse
    </div>
</div>

{{-- Riwayat Order Terakhir --}}
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul);">Riwayat Order Terakhir</h6>
        @forelse ($riwayatOrder as $order)
            <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                <div>
                    <div class="fw-semibold" style="font-size: 0.9rem;">Order #{{ $order->id }}</div>
                    <small class="text-muted" style="font-size: 0.75rem;">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                    <div style="font-size: 0.8rem;">{{ $order->outlet?->nama ?? 'Warung' }}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);">Rp{{ number_format($order->total_harga, 0, ',', '.') }}</div>
                    <span class="badge rounded-pill px-2 py-0" style="background-color: {{ $statusColors[$order->status] ?? '#9E9E9E' }}; color: #fff; font-size: 0.65rem;">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-4">
                <i class="bi bi-receipt display-6" style="color: var(--warna-netral-garis);"></i>
                <p class="mt-2 text-muted" style="font-size: 0.85rem;">Belum ada riwayat order</p>
            </div>
        @endforelse
    </div>
</div>

<script>
function gantiBulan(bulan) {
    window.location.href = '?bulan=' + bulan;
}
</script>

@endsection