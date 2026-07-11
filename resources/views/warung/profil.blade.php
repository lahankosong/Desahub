@extends('layouts.warung')

@section('content')
{{-- Profil Warung — Akun + Outlet + Password --}}

{{-- title removed --}}

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

{{-- ===== TAB NAVIGASI ===== --}}
<ul class="nav nav-pills mb-4 gap-2" id="profil-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4 fw-semibold"
                style="color: var(--warna-aksen-utama); background-color: #fff; border: 2px solid var(--warna-aksen-utama);"
                id="tab-akun" data-bs-toggle="pill" data-bs-target="#pane-akun" type="button" role="tab">
            Akun
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 fw-semibold"
                style="color: var(--warna-teks); background-color: #fff; border: 1px solid var(--warna-netral-garis);"
                id="tab-outlet" data-bs-toggle="pill" data-bs-target="#pane-outlet" type="button" role="tab">
            Outlet
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 fw-semibold"
                style="color: var(--warna-teks); background-color: #fff; border: 1px solid var(--warna-netral-garis);"
                id="tab-password" data-bs-toggle="pill" data-bs-target="#pane-password" type="button" role="tab">
            Password
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ===== TAB: AKUN ===== --}}
    <div class="tab-pane fade show active" id="pane-akun" role="tabpanel">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h5 class="mb-3" style="font-family: var(--font-judul);">Data Akun</h5>
                <form method="POST" action="{{ route('warung.profil.akun') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem;">Nama</label>
                        <input type="text" name="nama" class="form-control" value="{{ old('nama', $user->nama) }}" required
                               style="border-color: var(--warna-netral-garis); background: #fff;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem;">No HP</label>
                        <input type="text" class="form-control" value="{{ $user->no_hp }}" disabled
                               style="border-color: var(--warna-netral-garis); background: #f5f5f5;">
                        <small class="text-muted">No HP tidak dapat diubah langsung. Hubungi admin jika perlu ubah.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem;">Email <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}"
                               style="border-color: var(--warna-netral-garis); background: #fff;">
                    </div>

                    <button type="submit" class="btn rounded-pill px-4 fw-semibold w-100"
                            style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
                        <i class="bi bi-check-lg me-1"></i> Simpan Akun
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== TAB: OUTLET ===== --}}
    <div class="tab-pane fade" id="pane-outlet" role="tabpanel">
        @if ($outlet)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0" style="font-family: var(--font-judul);">Data Outlet</h5>
                        <span class="badge rounded-pill px-3 py-1"
                              style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.75rem;">
                            ● {{ $warung?->tier ? strtoupper($warung->tier) : 'BIASA' }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('warung.profil.outlet') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Nama Warung</label>
                            <input type="text" name="nama" class="form-control" value="{{ old('nama', $outlet->nama) }}" required
                                   style="border-color: var(--warna-netral-garis); background: #fff;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" required
                                      style="border-color: var(--warna-netral-garis); background: #fff;">{{ old('alamat', $outlet->alamat) }}</textarea>
                        </div>

                        {{-- Alamat Terstruktur (cascading dropdown dari database wilayah) --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Provinsi</label>
                                <select name="provinsi" id="provinsi-select" class="form-select"
                                        style="border-color: var(--warna-netral-garis); background: #fff;">
                                    <option value="">Pilih Provinsi...</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Kabupaten/Kota</label>
                                <select name="kabupaten" id="kabupaten-select" class="form-select"
                                        style="border-color: var(--warna-netral-garis); background: #fff;">
                                    <option value="">Pilih Kab/Kota...</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Kecamatan</label>
                                <select name="kecamatan" id="kecamatan-select" class="form-select"
                                        style="border-color: var(--warna-netral-garis); background: #fff;">
                                    <option value="">Pilih Kecamatan...</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Desa/Kelurahan</label>
                                <select name="desa_kelurahan" id="desa-select" class="form-select"
                                        style="border-color: var(--warna-netral-garis); background: #fff;">
                                    <option value="">Pilih Desa...</option>
                                </select>
                            </div>
                        </div>
                        {{-- Hidden inputs untuk nama wilayah (dikirim ke server) --}}
                        <input type="hidden" name="provinsi_nama" id="provinsi-nama" value="{{ old('provinsi', $outlet->provinsi) }}">
                        <input type="hidden" name="kabupaten_nama" id="kabupaten-nama" value="{{ old('kabupaten', $outlet->kabupaten) }}">
                        <input type="hidden" name="kecamatan_nama" id="kecamatan-nama" value="{{ old('kecamatan', $outlet->kecamatan) }}">
                        <input type="hidden" name="desa_kelurahan_nama" id="desa-nama" value="{{ old('desa_kelurahan', $outlet->desa_kelurahan) }}">
                        <div class="row g-2 mb-3">
                            <div class="col-3">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">RT</label>
                                <input type="text" name="rt" class="form-control" value="{{ old('rt', $outlet->rt) }}"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                            <div class="col-3">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">RW</label>
                                <input type="text" name="rw" class="form-control" value="{{ old('rw', $outlet->rw) }}"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                            <div class="col-3">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Kode Pos</label>
                                <input type="text" name="kode_pos" class="form-control" value="{{ old('kode_pos', $outlet->kode_pos) }}"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                        </div>

                        {{-- GPS --}}
                        <div class="mb-3 p-3 rounded-3" style="background-color: rgba(232,162,60,0.05);">
                            <small class="text-muted mb-2 d-block"><i class="bi bi-geo-alt me-1"></i> Koordinat GPS</small>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Latitude</label>
                                    <input type="number" step="any" name="lat" class="form-control" value="{{ old('lat', $outlet->lat) }}"
                                           placeholder="-7.250445" style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Longitude</label>
                                    <input type="number" step="any" name="lng" class="form-control" value="{{ old('lng', $outlet->lng) }}"
                                           placeholder="112.768845" style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm rounded-pill mt-2" onclick="getCurrentLocation()"
                                    style="background-color: var(--warna-aksen-utama); color: #fff; border: none; font-size: 0.75rem;">
                                <i class="bi bi-crosshair me-1"></i> Ambil Lokasi Saat Ini
                            </button>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Jam Buka</label>
                                <input type="time" name="jam_buka" class="form-control"
                                       value="{{ old('jam_buka', $warung?->jam_buka ? substr($warung->jam_buka, 0, 5) : '') }}"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Jam Tutup</label>
                                <input type="time" name="jam_tutup" class="form-control"
                                       value="{{ old('jam_tutup', $warung?->jam_tutup ? substr($warung->jam_tutup, 0, 5) : '') }}"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Kategori Warung</label>
                            <select name="kategori_warung" class="form-select" style="border-color: var(--warna-netral-garis); background: #fff;">
                                @foreach (['sembako', 'kelontong', 'sayur', 'buah', 'sembako & kelontong', 'lainnya'] as $kat)
                                    <option value="{{ $kat }}" {{ old('kategori_warung', $warung?->kategori_warung) === $kat ? 'selected' : '' }}>
                                        {{ ucfirst($kat) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 p-3 rounded-3" style="background-color: rgba(31,92,79,0.05);">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Verifikasi: <strong>{{ $outlet->level_verifikasi ?? 'dasar' }}</strong>
                                — untuk naik ke "terverifikasi", admin perlu memverifikasi lokasi usaha.
                            </small>
                        </div>

                        <button type="submit" class="btn rounded-pill px-4 fw-semibold w-100"
                                style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
                            <i class="bi bi-check-lg me-1"></i> Simpan Outlet
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-3" style="font-family: var(--font-judul);">Daftarkan Outlet Baru</h5>
                    <p class="text-muted mb-3" style="font-size: 0.85rem;">Anda belum punya outlet. Isi data di bawah untuk mendaftarkan warung Anda.</p>
                    <form method="POST" action="{{ route('warung.profil.outlet') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Nama Warung</label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Warung Bu Siti" required
                                   style="border-color: var(--warna-netral-garis); background: #fff;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" placeholder="Jl. Melati No. 5, Desa Sukamaju" required
                                      style="border-color: var(--warna-netral-garis); background: #fff;"></textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Jam Buka</label>
                                <input type="time" name="jam_buka" class="form-control"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Jam Tutup</label>
                                <input type="time" name="jam_tutup" class="form-control"
                                       style="border-color: var(--warna-netral-garis); background: #fff;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Kategori Warung</label>
                            <select name="kategori_warung" class="form-select" style="border-color: var(--warna-netral-garis); background: #fff;">
                                @foreach (['sembako', 'kelontong', 'sayur', 'buah', 'sembako & kelontong', 'lainnya'] as $kat)
                                    <option value="{{ $kat }}">{{ ucfirst($kat) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn rounded-pill px-4 fw-semibold w-100"
                                style="background-color: var(--warna-aksen-kedua); color: #fff; border: none;">
                            <i class="bi bi-plus-lg me-1"></i> Daftarkan Outlet
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    {{-- ===== TAB: PASSWORD ===== --}}
    <div class="tab-pane fade" id="pane-password" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3" style="font-family: var(--font-judul);">Ganti Password</h5>
                <form method="POST" action="{{ route('warung.profil.password') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem;">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required
                               style="border-color: var(--warna-netral-garis); background: #fff;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem;">Password Baru</label>
                        <input type="password" name="password" class="form-control" required minlength="6"
                               style="border-color: var(--warna-netral-garis); background: #fff;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 0.85rem;">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="6"
                               style="border-color: var(--warna-netral-garis); background: #fff;">
                    </div>

                    <button type="submit" class="btn rounded-pill px-4 fw-semibold w-100"
                            style="background-color: var(--warna-peringatan); color: #fff; border: none;">
                        <i class="bi bi-shield-lock me-1"></i> Ganti Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>


<script>
// ===== Cascading Wilayah Dropdowns =====
let savedProvinsi = '{{ $outlet->provinsi ?? "" }}';
let savedKabupaten = '{{ $outlet->kabupaten ?? "" }}';
let savedKecamatan = '{{ $outlet->kecamatan ?? "" }}';
let savedDesa = '{{ $outlet->desa_kelurahan ?? "" }}';

async function loadProvinsi() {
    const resp = await fetch('/api/wilayah/provinsi');
    const data = await resp.json();
    const sel = document.getElementById('provinsi-select');
    data.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.nama;
        opt.dataset.kode = p.kode;
        opt.textContent = p.nama;
        sel.appendChild(opt);
    });
    if (savedProvinsi) { sel.value = savedProvinsi; loadKabupaten(savedProvinsi); }
}

async function loadKabupaten(provNama) {
    const provOpt = [...document.getElementById('provinsi-select').options].find(o => o.value === provNama);
    if (!provOpt?.dataset.kode) return;
    resetSelect('kabupaten-select'); resetSelect('kecamatan-select'); resetSelect('desa-select');
    const resp = await fetch('/api/wilayah/kabupaten?provinsi_kode=' + provOpt.dataset.kode);
    const data = await resp.json();
    const sel = document.getElementById('kabupaten-select');
    data.forEach(k => {
        const opt = document.createElement('option');
        opt.value = k.nama; opt.dataset.kode = k.kode; opt.textContent = k.nama;
        sel.appendChild(opt);
    });
    if (savedKabupaten) { sel.value = savedKabupaten; loadKecamatan(savedKabupaten); }
}

async function loadKecamatan(kabNama) {
    const kabOpt = [...document.getElementById('kabupaten-select').options].find(o => o.value === kabNama);
    if (!kabOpt?.dataset.kode) return;
    resetSelect('kecamatan-select'); resetSelect('desa-select');
    const resp = await fetch('/api/wilayah/kecamatan?kabupaten_kode=' + kabOpt.dataset.kode);
    const data = await resp.json();
    const sel = document.getElementById('kecamatan-select');
    data.forEach(k => {
        const opt = document.createElement('option');
        opt.value = k.nama; opt.dataset.kode = k.kode; opt.textContent = k.nama;
        sel.appendChild(opt);
    });
    if (savedKecamatan) { sel.value = savedKecamatan; loadDesa(savedKecamatan); }
}

async function loadDesa(kecNama) {
    const kecOpt = [...document.getElementById('kecamatan-select').options].find(o => o.value === kecNama);
    if (!kecOpt?.dataset.kode) return;
    resetSelect('desa-select');
    const resp = await fetch('/api/wilayah/desa?kecamatan_kode=' + kecOpt.dataset.kode);
    const data = await resp.json();
    const sel = document.getElementById('desa-select');
    data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.nama; opt.dataset.kode = d.kode; opt.textContent = d.nama;
        sel.appendChild(opt);
    });
    if (savedDesa) sel.value = savedDesa;
}

function resetSelect(id) { document.getElementById(id).innerHTML = '<option value="">Pilih...</option>'; }

document.addEventListener('DOMContentLoaded', function() {
    const provSel = document.getElementById('provinsi-select');
    if (!provSel) return; // no outlet yet, skip wilayah init
    
    provSel.addEventListener('change', function() {
        document.getElementById('provinsi-nama').value = this.value;
        this.value ? loadKabupaten(this.value) : (resetSelect('kabupaten-select'), resetSelect('kecamatan-select'), resetSelect('desa-select'));
    });
    document.getElementById('kabupaten-select').addEventListener('change', function() {
        document.getElementById('kabupaten-nama').value = this.value;
        this.value ? loadKecamatan(this.value) : (resetSelect('kecamatan-select'), resetSelect('desa-select'));
    });
    document.getElementById('kecamatan-select').addEventListener('change', function() {
        document.getElementById('kecamatan-nama').value = this.value;
        this.value ? loadDesa(this.value) : resetSelect('desa-select');
    });
    document.getElementById('desa-select').addEventListener('change', function() {
        document.getElementById('desa-nama').value = this.value;
    });
    
    loadProvinsi();
});

// ===== GPS Geolocation =====
function getCurrentLocation() {
    if (!navigator.geolocation) {
        alert('Geolokasi tidak didukung browser ini.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            document.querySelector('input[name="lat"]').value = pos.coords.latitude.toFixed(7);
            document.querySelector('input[name="lng"]').value = pos.coords.longitude.toFixed(7);
        },
        (err) => alert('Gagal mengambil lokasi: ' + err.message),
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>
@endsection
