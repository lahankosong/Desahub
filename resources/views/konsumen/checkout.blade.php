@extends('layouts.konsumen')

@section('content')

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="fw-bold mb-1" style="font-family: var(--font-judul);">{{ $produk->nama }}</div>
        <small class="text-muted">🏪 {{ $produk->outlet?->nama ?? 'Warung' }}</small>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted">Harga satuan</span>
            <span class="fw-bold" style="font-family: var(--font-judul);">Rp{{ number_format($produk->harga, 0, ',', '.') }} / {{ $produk->satuan }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="text-muted">Jumlah</span>
            <span class="fw-bold">{{ $qty }} {{ $produk->satuan }}</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold">Total</span>
            <span class="fw-bold fs-5" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);">Rp{{ number_format($produk->harga * $qty, 0, ',', '.') }}</span>
        </div>
    </div>
</div>

<form method="POST" action="/konsumen/checkout">
    @csrf
    <input type="hidden" name="produk_id" value="{{ $produk->id }}">
    <input type="hidden" name="qty" value="{{ $qty }}">

    {{-- Metode Pengiriman --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3" style="font-family: var(--font-judul); font-size: 0.85rem;">Metode Pengiriman</h6>
            <div class="d-flex gap-2">
                <label class="flex-fill card border-0 shadow-sm" style="cursor: pointer;">
                    <div class="card-body text-center py-3" id="opt-ambil">
                        <input type="radio" name="metode_pengiriman" value="ambil_sendiri" checked class="d-none" onchange="togglePengiriman()">
                        <i class="bi bi-shop fs-4" style="color: var(--warna-aksen-utama);"></i>
                        <div class="mt-1 fw-semibold" style="font-size: 0.8rem;">Ambil Sendiri</div>
                        <small class="text-muted" style="font-size: 0.65rem;">Datang langsung ke warung</small>
                    </div>
                </label>
                <label class="flex-fill card border-0 shadow-sm" style="cursor: pointer;">
                    <div class="card-body text-center py-3" id="opt-antar">
                        <input type="radio" name="metode_pengiriman" value="diantar_kurir" class="d-none" onchange="togglePengiriman()">
                        <i class="bi bi-truck fs-4" style="color: var(--warna-aksen-utama);"></i>
                        <div class="mt-1 fw-semibold" style="font-size: 0.8rem;">Diantar Kurir</div>
                        <small class="text-muted" style="font-size: 0.65rem;">Dikirim ke alamat</small>
                    </div>
                </label>
            </div>
            <div id="alamat-antar-section" class="mt-3 d-none">
                <label class="form-label fw-semibold" style="font-size: 0.8rem;">Alamat Antar</label>
                <textarea name="alamat_antar" class="form-control" rows="2" placeholder="Jl. Melati No. 5, RT 01/RW 02"
                          style="border-color: var(--warna-netral-garis); background: #fff;"></textarea>
            </div>
        </div>
    </div>

    {{-- Metode Pembayaran --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-3" style="font-family: var(--font-judul); font-size: 0.85rem;">Metode Pembayaran</h6>
            <div class="d-flex flex-column gap-2">
                <label class="card border-0 shadow-sm" style="cursor: pointer;">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="metode_pembayaran" value="cod" checked style="accent-color: var(--warna-aksen-utama);">
                            <div>
                                <div class="fw-semibold" style="font-size: 0.85rem;">💵 COD (Bayar di Tempat)</div>
                                <small class="text-muted" style="font-size: 0.65rem;">Bayar tunai saat barang diterima</small>
                            </div>
                        </div>
                    </div>
                </label>
                <label class="card border-0 shadow-sm" style="cursor: pointer;">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="metode_pembayaran" value="transfer" style="accent-color: var(--warna-aksen-utama);">
                            <div>
                                <div class="fw-semibold" style="font-size: 0.85rem;">🏦 Transfer Bank</div>
                                <small class="text-muted" style="font-size: 0.65rem;">Transfer ke rekening warung</small>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- Catatan --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold" style="font-size: 0.8rem;">Catatan (opsional)</label>
            <textarea name="catatan" class="form-control" rows="2" placeholder="Misal: warna, ukuran, atau permintaan khusus"
                      style="border-color: var(--warna-netral-garis); background: #fff;"></textarea>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-3 py-2 mb-3" style="font-size: 0.8rem;">
            {{ $errors->first() }}
        </div>
    @endif

    <button type="submit" class="btn rounded-pill fw-semibold w-100 mb-4"
            style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
        <i class="bi bi-check-lg me-1"></i> Buat Pesanan · Rp{{ number_format($produk->harga * $qty, 0, ',', '.') }}
    </button>
</form>

<nav class="fixed-bottom bg-white border-top shadow" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="d-flex justify-content-around py-2">
        <a href="{{ route('konsumen.dashboard') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-house-door fs-5"></i><div style="font-size: 0.65rem;">Beranda</div>
        </a>
        <a href="{{ route('konsumen.outlet') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-shop fs-5"></i><div style="font-size: 0.65rem;">Warung</div>
        </a>
        <a href="{{ route('konsumen.order') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-receipt fs-5"></i><div style="font-size: 0.65rem;">Order</div>
        </a>
        <a href="#" class="text-decoration-none text-center text-muted">
            <i class="bi bi-person fs-5"></i><div style="font-size: 0.65rem;">Profil</div>
        </a>
    </div>
</nav>
<div style="height: 70px;"></div>

<script>
    function togglePengiriman() {
        const antar = document.querySelector('input[value="diantar_kurir"]');
        const section = document.getElementById('alamat-antar-section');
        const optAmbil = document.getElementById('opt-ambil');
        const optAntar = document.getElementById('opt-antar');

        if (antar.checked) {
            section.classList.remove('d-none');
            optAntar.style.border = '2px solid var(--warna-aksen-utama)';
            optAmbil.style.border = '';
        } else {
            section.classList.add('d-none');
            optAmbil.style.border = '2px solid var(--warna-aksen-utama)';
            optAntar.style.border = '';
        }
    }
    // Init: set active border on default selection
    document.getElementById('opt-ambil').style.border = '2px solid var(--warna-aksen-utama)';
</script>
@endsection