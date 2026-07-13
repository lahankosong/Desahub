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
        <div class="card border-0 shadow-sm" style="background-color: #FFF8E1; cursor: pointer;" onclick="window.location.href='{{ route('warung.order-masuk') }}'">
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
        <div class="card border-0 shadow-sm" style="background-color: #FFEBEE; cursor: pointer;" onclick="bukaModalStokTipis()">
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
            <div class="col-4 border-end">
                <div class="fs-3 fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);">{{ $orderHariIni }}</div>
                <small class="text-muted">Order Masuk</small>
            </div>
            <div class="col-4 border-end">
                <div class="fs-3 fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-kedua); cursor: pointer;" onclick="bukaModalOmzet()">Rp{{ number_format($omzetHariIni, 0, ',', '.') }}</div>
                <small class="text-muted" style="cursor: pointer;" onclick="bukaModalOmzet()">Omzet Hari Ini</small>
            </div>
            <div class="col-4">
                <div class="fs-3 fw-bold" style="font-family: var(--font-judul); color: #FF9800; cursor: pointer;" onclick="bukaModalPiutang()">Rp{{ number_format($totalPiutang, 0, ',', '.') }}</div>
                <small class="text-muted" style="cursor: pointer;" onclick="bukaModalPiutang()">Piutang Aktif</small>
            </div>
        </div>
    </div>
</div>

{{-- Kurir Aktif & Top Product --}}
<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="card border-0 shadow-sm h-100" style="cursor: pointer;" onclick="bukaModalKurir()">
            <div class="card-body">
                <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul); font-size: 0.9rem;">
                    <i class="bi bi-truck me-1" style="color: var(--warna-aksen-kedua);"></i>
                    Kurir Aktif
                </h6>
                @php
                    $kurirAktif = $kurirOnline->count();
                @endphp
                <div class="text-center py-3">
                    <div class="fs-2 fw-bold" style="font-family: var(--font-judul); color: var(--warna-aksen-kedua);">{{ $kurirAktif }}</div>
                    <small class="text-muted">Kurir Online</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 shadow-sm h-100" style="cursor: pointer;" onclick="bukaModalTopProduk()">
            <div class="card-body">
                <h6 class="fw-semibold mb-3" style="font-family: var(--font-judul); font-size: 0.9rem;">
                    <i class="bi bi-trophy me-1" style="color: var(--warna-aksen-utama);"></i>
                    Top Product
                </h6>
                @php
                    $topProduk = $topProdukList->first();
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

{{-- ========== MODAL: STOK TIPIS ========== --}}
<div class="modal fade" id="modalStokTipis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-exclamation-triangle me-1" style="color: var(--warna-peringatan);"></i>Stok Tipis
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                @if (count($stokTipisList) > 0)
                    @foreach ($stokTipisList as $item)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold" style="font-size: 0.85rem;">{{ $item['nama'] }}</div>
                                <small class="text-muted">{{ $item['satuan'] ?? 'pcs' }}</small>
                            </div>
                            <span class="badge rounded-pill px-3 py-1" style="background-color: var(--warna-peringatan); color: #fff; font-size: 0.8rem;">
                                {{ $item['stok'] }} tersisa
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-muted py-3" style="font-size: 0.85rem;">
                        <i class="bi bi-check-circle me-1" style="color: var(--warna-aksen-kedua);"></i>Semua stok aman
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: OMZET HARI INI ========== --}}
<div class="modal fade" id="modalOmzet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-graph-up me-1" style="color: var(--warna-aksen-kedua);"></i>Produk Terjual Hari Ini
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                @if ($produkTerjualHariIni->count() > 0)
                    <div class="mb-2 text-end">
                        <small class="fw-bold" style="color: var(--warna-aksen-kedua);">Total: Rp{{ number_format($omzetHariIni, 0, ',', '.') }}</small>
                    </div>
                    @foreach ($produkTerjualHariIni as $item)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold" style="font-size: 0.85rem;">{{ $item->nama_produk }}</div>
                                <small class="text-muted">{{ $item->total_qty }}x terjual</small>
                            </div>
                            <span class="fw-bold" style="color: var(--warna-aksen-utama); font-size: 0.85rem;">
                                Rp{{ number_format($item->total_nilai, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-muted py-3" style="font-size: 0.85rem;">
                        <i class="bi bi-inbox me-1"></i>Belum ada penjualan hari ini
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: PIUTANG ========== --}}
<div class="modal fade" id="modalPiutang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-clock-history me-1" style="color: #FF9800;"></i>Piutang Aktif
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                @if ($piutangList->count() > 0)
                    <div class="mb-2 text-end">
                        <small class="fw-bold" style="color: #FF9800;">Total: Rp{{ number_format($totalPiutang, 0, ',', '.') }}</small>
                    </div>
                    @foreach ($piutangList as $p)
                        @php $sisa = $p->jumlah - $p->terbayar; @endphp
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold" style="font-size: 0.85rem;">{{ $p->pelanggan?->nama ?? 'Unknown' }}</div>
                                <small class="text-muted">
                                    Jatuh tempo: {{ $p->jatuh_tempo?->format('d M Y') ?? '-' }}
                                    @if ($p->jatuh_tempo && $p->jatuh_tempo->isPast())
                                        <span class="text-danger">(Lewat)</span>
                                    @endif
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold" style="color: #FF9800; font-size: 0.85rem;">Rp{{ number_format($sisa, 0, ',', '.') }}</div>
                                <small class="text-muted">dari Rp{{ number_format($p->jumlah, 0, ',', '.') }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-muted py-3" style="font-size: 0.85rem;">
                        <i class="bi bi-check-circle me-1" style="color: var(--warna-aksen-kedua);"></i>Tidak ada piutang aktif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: KURIR AKTIF ========== --}}
<div class="modal fade" id="modalKurir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-truck me-1" style="color: var(--warna-aksen-kedua);"></i>Daftar Kurir
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                <div class="fw-bold mb-1 text-success" style="font-size: 0.8rem;">Online ({{ $kurirOnline->count() }})</div>
                @forelse ($kurirOnline as $k)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 0.85rem;">
                        <span>{{ $k->user?->name ?? 'Kurir' }}</span>
                        <span class="badge bg-success rounded-pill" style="font-size: 0.7rem;">Online</span>
                    </div>
                @empty
                    <div class="text-muted text-center py-2" style="font-size: 0.75rem;">Tidak ada kurir online</div>
                @endforelse

                <div class="fw-bold mt-3 mb-1 text-secondary" style="font-size: 0.8rem;">Offline ({{ $kurirOffline->count() }})</div>
                @forelse ($kurirOffline as $k)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size: 0.85rem; opacity: 0.7;">
                        <span>{{ $k->user?->name ?? 'Kurir' }}</span>
                        <span class="badge bg-secondary rounded-pill" style="font-size: 0.7rem;">Offline</span>
                    </div>
                @empty
                    <div class="text-muted text-center py-2" style="font-size: 0.75rem;">Tidak ada kurir offline</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: TOP PRODUK ========== --}}
<div class="modal fade" id="modalTopProduk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-trophy me-1" style="color: var(--warna-aksen-utama);"></i>Produk Terlaris
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                @if ($topProdukList->count() > 0)
                    @foreach ($topProdukList as $i => $item)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="color: {{ $i == 0 ? '#FFD700' : ($i == 1 ? '#C0C0C0' : ($i == 2 ? '#CD7F32' : '#999')) }}; font-size: 1rem;">
                                    #{{ $i + 1 }}
                                </span>
                                <div>
                                    <div class="fw-semibold" style="font-size: 0.85rem;">{{ $item->nama_produk }}</div>
                                </div>
                            </div>
                            <span class="badge rounded-pill px-3 py-1" style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.75rem;">
                                {{ $item->total_qty }}x terjual
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center text-muted py-3" style="font-size: 0.85rem;">
                        <i class="bi bi-inbox me-1"></i>Belum ada data penjualan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function bukaModalStokTipis() {
    new bootstrap.Modal(document.getElementById('modalStokTipis')).show();
}
function bukaModalOmzet() {
    new bootstrap.Modal(document.getElementById('modalOmzet')).show();
}
function bukaModalPiutang() {
    new bootstrap.Modal(document.getElementById('modalPiutang')).show();
}
function bukaModalTopProduk() {
    new bootstrap.Modal(document.getElementById('modalTopProduk')).show();
}
function bukaModalKurir() {
    new bootstrap.Modal(document.getElementById('modalKurir')).show();
}
</script>

@endsection