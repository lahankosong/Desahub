@extends('layouts.konsumen')

@section('content')

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
        <small class="text-muted" style="font-size: 0.65rem;">{{ $outlets->count() }} warung ditemukan</small>
    </div>
</div>

@if (!$hasGps)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-geo-alt display-3" style="color: var(--warna-aksen-utama);"></i>
            <h5 class="mt-3" style="font-family: var(--font-judul);">Aktifkan Lokasi</h5>
            <p class="text-muted">Izinkan akses lokasi untuk melihat warung dalam radius {{ $radius }}km</p>
            <button class="btn rounded-pill px-4 fw-semibold" onclick="detectLocation()"
                    style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
                <i class="bi bi-geo-alt-fill me-1"></i> Aktifkan GPS
            </button>
        </div>
    </div>
@elseif ($outlets->count() > 0)
    <div class="d-flex flex-column gap-2">
        @foreach ($outlets as $o)
            <a href="#" class="text-decoration-none">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-bold text-truncate" style="font-family: var(--font-judul); font-size: 0.9rem;">{{ $o->nama }}</div>
                                <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                    {{ $o->desa_kelurahan ?? '' }}{{ $o->kecamatan ? ", {$o->kecamatan}" : '' }}
                                </small>
                            </div>
                            <div class="text-end ms-2 flex-shrink-0">
                                <div class="fw-bold" style="font-family: var(--font-judul); font-size: 0.9rem; color: var(--warna-aksen-utama);">
                                    {{ number_format($o->jarak_km, 2) }}
                                </div>
                                <small class="text-muted" style="font-size: 0.65rem;">km</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-shop display-3" style="color: var(--warna-netral-garis);"></i>
            <p class="mt-3 text-muted">Belum ada warung dalam radius {{ $radius }}km</p>
            <a href="?lat={{ $lat }}&lng={{ $lng }}&radius=20" class="btn btn-sm rounded-pill px-4"
               style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
                <i class="bi bi-arrow-repeat me-1"></i> Perluas ke 20km
            </a>
        </div>
    </div>
@endif

<nav class="fixed-bottom bg-white border-top shadow" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="d-flex justify-content-around py-2">
        <a href="{{ route('konsumen.dashboard') }}" class="text-decoration-none text-center text-muted">
            <i class="bi bi-house-door fs-5"></i><div style="font-size: 0.65rem;">Beranda</div>
        </a>
        <a href="{{ route('konsumen.outlet') }}" class="text-decoration-none text-center active" style="color: var(--warna-aksen-utama);">
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
    const baseLat = {{ $lat }};
    const baseLng = {{ $lng }};

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
        if (!navigator.geolocation) return alert('Geolokasi tidak didukung.');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude.toFixed(7);
                const lng = pos.coords.longitude.toFixed(7);
                const rad = document.getElementById('radius-slider').value || 1;
                window.location.href = `?lat=${lat}&lng=${lng}&radius=${rad}`;
            },
            (err) => alert('Gagal: ' + err.message),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
</script>
@endsection