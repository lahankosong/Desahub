@extends('layouts.konsumen')

@section('content')

{{-- Search --}}
<div class="mb-2">
    <div class="input-group input-group-sm">
        <span class="input-group-text" style="background: #fff; border-color: var(--warna-netral-garis);">
            <i class="bi bi-search"></i>
        </span>
        <input type="text" class="form-control" placeholder="Cari produk..."
               style="border-color: var(--warna-netral-garis); background: #fff;"
               id="search-input" value="{{ request('q') }}"
               onkeydown="if(event.key==='Enter')cariProduk(this.value)">
        <button class="btn" style="background-color: var(--warna-aksen-utama); color: #fff; border: none;"
                onclick="cariProduk(document.getElementById('search-input').value)">
            <i class="bi bi-search"></i>
        </button>
    </div>
</div>

{{-- Radius Slider (compact) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm p-0" onclick="ubahRadius(-1)" {{ $radius <= 1 ? 'disabled' : '' }}
                    style="width: 28px; height: 28px; background-color: {{ $radius > 1 ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; color: #fff; border: none; border-radius: 50%; font-size: 1rem; line-height: 1;">−</button>
            <input type="range" id="radius-slider" class="form-range flex-grow-1" min="1" max="50" value="{{ $radius }}"
                   onchange="gantiRadius(this.value)" style="accent-color: var(--warna-aksen-utama); height: 4px;">
            <button class="btn btn-sm p-0" onclick="ubahRadius(1)" {{ $radius >= 50 ? 'disabled' : '' }}
                    style="width: 28px; height: 28px; background-color: {{ $radius < 50 ? 'var(--warna-aksen-utama)' : 'var(--warna-netral-garis)' }}; color: #fff; border: none; border-radius: 50%; font-size: 1rem; line-height: 1;">+</button>
            <span class="fw-bold ms-1" style="font-family: var(--font-judul); font-size: 0.8rem; color: var(--warna-aksen-utama); min-width: 35px;">{{ $radius }}km</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted" style="font-size: 0.65rem;">
                @if ($hasGps)
                    {{ $outletCount }} warung · {{ $produkList->count() }} produk
                @else
                    {{ $produkList->count() }} produk terbaru
                @endif
            </small>
            <small style="font-size: 0.65rem; color: var(--warna-aksen-kedua);">
                @if ($lokasiNama)
                    📍 {{ $lokasiNama }}
                @elseif ($fromProfile)
                    📍 dari profil
                @endif
            </small>
        </div>
    </div>
</div>

{{-- Jika radius > 1 dan tidak ada GPS input (perlu aktivasi) --}}
@if ($radius > 1 && !$requestHasGps)
    <div id="gps-prompt" class="alert rounded-3 mb-3 py-2" style="background-color: #FFF8E1; border: 1px solid var(--warna-aksen-utama); font-size: 0.8rem;">
        <div class="d-flex align-items-center justify-content-between">
            <span><i class="bi bi-geo-alt-fill me-1" style="color: var(--warna-aksen-utama);"></i> Radius >1km butuh GPS aktif</span>
            <button class="btn btn-sm rounded-pill px-3" onclick="detectLocation()"
                    style="background-color: var(--warna-aksen-utama); color: #fff; border: none; font-size: 0.7rem;">
                Aktifkan
            </button>
        </div>
    </div>
@endif

{{-- Daftar Produk --}}
<div class="d-flex flex-column gap-2 mb-4">
    @forelse ($produkList as $p)
        <div class="card border-0 shadow-sm" style="{{ !$p['tersedia'] ? 'opacity: 0.5;' : '' }}">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="fw-bold text-truncate" style="font-family: var(--font-judul); font-size: 0.9rem;">{{ $p['nama'] }}</div>
                        <small class="text-muted" style="font-size: 0.7rem;">🏪 {{ $p['outlet_nama'] }}</small>
                        <div class="mt-1 d-flex align-items-center gap-2">
                            @if ($p['tersedia'])
                                @if ($p['stok'] <= 2)
                                    <span class="badge rounded-pill px-2 py-0" style="background-color: var(--warna-peringatan); color: #fff; font-size: 0.6rem;">Hampir Habis</span>
                                @else
                                    <span class="badge rounded-pill px-2 py-0" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.6rem;">Tersedia</span>
                                @endif
                            @else
                                <span class="badge rounded-pill px-2 py-0" style="background-color: #9E9E9E; color: #fff; font-size: 0.6rem;">Habis</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end ms-2 flex-shrink-0">
                        <div class="fw-bold" style="font-family: var(--font-judul); font-size: 0.95rem; color: {{ $p['tersedia'] ? 'var(--warna-aksen-utama)' : '#9E9E9E' }};">
                            Rp{{ number_format($p['harga'], 0, ',', '.') }}
                        </div>
                        @if ($p['tersedia'])
                            <a href="{{ route('konsumen.checkout', ['produk_id' => $p['id']]) }}"
                               class="btn btn-sm rounded-pill mt-1 px-3 text-decoration-none"
                               style="background-color: var(--warna-aksen-utama); color: #fff; border: none; font-size: 0.65rem;">
                                + Pesan
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-4">
            <i class="bi bi-box display-6" style="color: var(--warna-netral-garis);"></i>
            <p class="mt-2 text-muted" style="font-size: 0.85rem;">
                @if ($hasGps)
                    Belum ada produk dalam {{ $radius }}km
                @else
                    Belum ada produk terdaftar
                @endif
            </p>
            @if ($hasGps)
                <button class="btn btn-sm rounded-pill px-3" onclick="gantiRadius(20)"
                        style="background-color: var(--warna-aksen-utama); color: #fff; border: none; font-size: 0.75rem;">
                    Perluas ke 20km
                </button>
            @endif
        </div>
    @endforelse
</div>

<script>
    const baseLat = {{ $lat }};
    const baseLng = {{ $lng }};

    function cariProduk(q) {
        const params = new URLSearchParams(window.location.search);
        if (q) params.set('q', q); else params.delete('q');
        if (baseLat && baseLng) { params.set('lat', baseLat); params.set('lng', baseLng); }
        window.location.href = '?' + params.toString();
    }

    function gantiRadius(r) {
        r = Math.max(1, Math.min(50, parseInt(r)));
        const params = new URLSearchParams(window.location.search);
        params.set('radius', r);
        if (baseLat && baseLng) { params.set('lat', baseLat); params.set('lng', baseLng); }
        window.location.href = '?' + params.toString();
    }

    function ubahRadius(delta) {
        const slider = document.getElementById('radius-slider');
        gantiRadius(parseInt(slider.value) + delta);
    }

    function detectLocation() {
        if (!navigator.geolocation) { alert('Geolokasi tidak didukung.'); return; }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude.toFixed(7);
                const lng = pos.coords.longitude.toFixed(7);
                const rad = document.getElementById('radius-slider').value || 3;
                window.location.href = `?lat=${lat}&lng=${lng}&radius=${rad}`;
            },
            (err) => alert('Gagal: ' + err.message),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
</script>
@endsection