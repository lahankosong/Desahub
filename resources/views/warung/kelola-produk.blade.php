@extends('layouts.warung')

@section('content')
{{-- Kelola Produk Warung — terhubung ke database --}}

<div class="d-flex align-items-center justify-content-between mb-3">
{{-- title removed --}}
    <button class="btn btn-sm rounded-pill px-3 fw-semibold"
            style="background-color: var(--warna-aksen-utama); color: #fff; border: none;"
            onclick="toggleFormTambah()">
        <i class="bi bi-plus-lg me-1"></i> Tambah
    </button>
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

{{-- ========== FORM TAMBAH PRODUK ========== --}}
<div class="card border-0 shadow-sm mb-4 d-none" id="form-produk">
    <div class="card-body">
        <h5 class="mb-3" style="font-family: var(--font-judul);" id="form-title">Tambah Produk Baru</h5>
        <form method="POST" action="{{ route('warung.produk.store') }}" id="produk-form">
            @csrf
            <input type="hidden" id="produk-method" name="_method" value="POST">
            <input type="hidden" id="produk-id" name="produk_id" value="">

            {{-- Barcode --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Barcode <span class="text-muted fw-normal">(opsional)</span></label>
                <div class="input-group">
                    <input type="text" name="barcode" id="produk-barcode" class="form-control" placeholder="Scan atau ketik barcode"
                           style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
                    <button type="button" class="btn" onclick="bukaScanBarcode()"
                            style="background-color: var(--warna-aksen-kedua); color: #fff; border: none;">
                        <i class="bi bi-upc-scan"></i> Scan
                    </button>
                </div>
                <small class="text-muted" style="font-size: 0.7rem;">Barcode otomatis cari nama produk dari database (jika ada)</small>
            </div>

            {{-- Nama Produk --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Nama Produk</label>
                <input type="text" name="nama" id="produk-nama" class="form-control" placeholder="Contoh: Beras 5kg" required
                       style="border-color: var(--warna-netral-garis); background: #fff;">
            </div>

            {{-- Deskripsi --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="deskripsi" id="produk-deskripsi" class="form-control" placeholder="Contoh: Beras premium kualitas terbaik"
                       style="border-color: var(--warna-netral-garis); background: #fff;">
            </div>

            {{-- Harga Jual & Satuan --}}
            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Harga Jual (Rp)</label>
                    <input type="number" name="harga" id="produk-harga" class="form-control" placeholder="65000" required min="100"
                           style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
                </div>
                <div class="col-5">
                    <label class="form-label fw-semibold" style="font-size: 0.85rem;">Satuan</label>
                    <select name="satuan" id="produk-satuan" class="form-select" style="border-color: var(--warna-netral-garis); background: #fff;">
                        @foreach (['kg', 'liter', 'pcs', 'bungkus', 'botol', 'sachet', 'ikat', 'butir'] as $sat)
                            <option value="{{ $sat }}">{{ $sat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Harga Beli --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Harga Beli (Rp) <span class="text-muted fw-normal">— untuk hitung margin</span></label>
                <input type="number" name="harga_beli" id="produk-harga-beli" class="form-control" placeholder="50000" min="0"
                       style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
            </div>

            {{-- Stok Awal --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Stok Awal</label>
                <input type="number" name="stok" id="produk-stok" class="form-control" placeholder="10" required min="0"
                       style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
            </div>

            {{-- Diskon --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Diskon (%) <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="number" name="diskon" id="produk-diskon" class="form-control" placeholder="0" min="0" max="100"
                       style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
                <small class="text-muted" style="font-size: 0.7rem;">Kosongkan atau 0 jika tidak ada diskon</small>
            </div>

            {{-- Bundle --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Bundle <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="bundle" id="produk-bundle" class="form-control" placeholder="Contoh: 2x1, 3x5000"
                       style="border-color: var(--warna-netral-garis); background: #fff; font-family: var(--font-judul);">
                <small class="text-muted" style="font-size: 0.7rem;">Format: qty1xharga1, qty2xharga2</small>
            </div>

            {{-- Tombol --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-semibold rounded-pill px-4 flex-grow-1"
                        style="background-color: var(--warna-aksen-utama); color: #fff; border: none;">
                    <i class="bi bi-check-lg me-1"></i> <span id="btn-label">Simpan</span>
                </button>
                <button type="button" class="btn fw-semibold rounded-pill px-4"
                        style="background-color: #fff; color: var(--warna-teks); border: 1px solid var(--warna-netral-garis);"
                        onclick="toggleFormTambah()">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========== DAFTAR PRODUK (dari database) ========== --}}
<div class="d-flex flex-column gap-3" id="daftar-produk">
    @forelse ($produkList as $p)
        @php
            $stok = $p['stok'] ?? 0;
            $tersedia = $p['tersedia'] ?? ($stok > 0);
            if ($stok <= 0) $tersedia = false;
        @endphp
        <div class="card border-0 shadow-sm produk-card" data-id="{{ $p['id'] }}" style="{{ !$tersedia ? 'opacity: 0.6;' : '' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-bold" style="font-family: var(--font-judul);">{{ $p['nama'] }}</div>
                        <small class="text-muted">{{ $p['deskripsi'] ?? '' }}</small>
                    </div>
                    <div class="text-end">
                        <span class="fw-bold fs-5 d-block" style="font-family: var(--font-judul); color: {{ $tersedia ? 'var(--warna-aksen-utama)' : '#9E9E9E' }};">
                            Rp{{ number_format($p['harga'], 0, ',', '.') }}
                        </span>
                        @if (!empty($p['harga_beli']) && $p['harga_beli'] > 0)
                            @php $margin = $p['harga'] - $p['harga_beli']; @endphp
                            <small class="text-muted" style="font-size: 0.7rem;">
                                Margin: {{ $margin >= 0 ? '+' : '' }}Rp{{ number_format($margin, 0, ',', '.') }}
                            </small>
                        @endif
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        @if ($tersedia)
                            @if ($stok <= 2)
                                <span class="badge rounded-pill px-3 py-1" style="background-color: var(--warna-peringatan); color: #fff; font-size: 0.75rem;">● Hampir Habis</span>
                            @else
                                <span class="badge rounded-pill px-3 py-1" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.75rem;">● Tersedia</span>
                            @endif
                        @else
                            <span class="badge rounded-pill px-3 py-1" style="background-color: #9E9E9E; color: #fff; font-size: 0.75rem;">● Habis</span>
                        @endif
                        <small class="text-muted">Stok: {{ $stok }} {{ $p['satuan'] ?? 'pcs' }}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" style="border-color: var(--warna-netral-garis); font-size: 0.75rem;"
                                onclick="editProduk({{ $p['id'] }}, '{{ $p['nama'] }}', '{{ $p['deskripsi'] ?? '' }}', {{ $p['harga'] }}, {{ $p['harga_beli'] ?? 0 }}, '{{ $p['satuan'] ?? 'pcs' }}', '{{ $p['barcode'] ?? '' }}', {{ $stok }})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success rounded-pill" style="border-color: var(--warna-aksen-kedua); font-size: 0.75rem;"
                                onclick="bukaRestok({{ $p['id'] }}, '{{ $p['nama'] }}', {{ $p['harga_beli'] ?? 0 }})" title="Restok">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input produk-toggle" type="checkbox" {{ $tersedia ? 'checked' : '' }}
                                   onchange="toggleKetersediaan(this, {{ $p['id'] }})"
                                   style="{{ $tersedia ? 'background-color: var(--warna-aksen-kedua); border-color: var(--warna-aksen-kedua);' : '' }} cursor: pointer;">
                        </div>
                    </div>
                </div>
                {{-- SyncIndicator for offline toggle --}}
                <div class="sync-indicator mt-2 d-none" id="sync-produk-{{ $p['id'] }}"
                     style="font-size: 0.75rem; color: var(--warna-aksen-utama);">
                    <i class="bi bi-arrow-repeat spin me-1"></i> Menunggu sinkron...
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5" id="empty-state">
            <i class="bi bi-box display-3" style="color: var(--warna-netral-garis);"></i>
            <p class="mt-3 text-muted">Belum ada produk terdaftar</p>
            <button class="btn btn-sm rounded-pill px-4" style="background-color: var(--warna-aksen-utama); color: #fff; border: none;"
                    onclick="toggleFormTambah()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Produk Pertama
            </button>
        </div>
    @endforelse
</div>

@endsection

{{-- ========== SCRIPTS (UI only, data dari database) ========== --}}
<script>
    // ===== FORM TOGGLE =====
    function toggleFormTambah() {
        const form = document.getElementById('form-produk');
        const isHidden = form.classList.contains('d-none');

        if (isHidden) {
            resetForm();
            form.classList.remove('d-none');
            document.getElementById('produk-nama').focus();
        } else {
            form.classList.add('d-none');
        }
    }

    function resetForm() {
        document.getElementById('form-title').textContent = 'Tambah Produk Baru';
        document.getElementById('btn-label').textContent = 'Simpan';
        document.getElementById('produk-method').value = 'POST';
        document.getElementById('produk-id').value = '';
        document.getElementById('produk-nama').value = '';
        document.getElementById('produk-deskripsi').value = '';
        document.getElementById('produk-harga').value = '';
        document.getElementById('produk-satuan').value = 'kg';
        document.getElementById('produk-harga-beli').value = '';
        document.getElementById('produk-stok').value = '';
        document.getElementById('produk-form').action = '{{ route('warung.produk.store') }}';
    }

    function editProduk(id, nama, deskripsi, harga, hargaBeli, satuan, barcode, stok) {
        const form = document.getElementById('form-produk');
        document.getElementById('form-title').textContent = 'Edit Produk #' + id;
        document.getElementById('btn-label').textContent = 'Update';
        document.getElementById('produk-method').value = 'PUT';
        document.getElementById('produk-id').value = id;
        document.getElementById('produk-nama').value = nama;
        document.getElementById('produk-deskripsi').value = deskripsi;
        document.getElementById('produk-harga').value = harga;
        document.getElementById('produk-satuan').value = satuan;
        document.getElementById('produk-barcode').value = barcode || '';
        document.getElementById('produk-harga-beli').value = hargaBeli || '';
        document.getElementById('produk-stok').value = stok;
        // Gunakan action update (produk_id disubmit sebagai path param)
        document.getElementById('produk-form').action = '/warung/kelola-produk/' + id;

        form.classList.remove('d-none');
        document.getElementById('produk-nama').focus();
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ===== TOGGLE KETERSEDIAAN (offline queue) =====
    function toggleKetersediaan(checkbox, produkId) {
        const syncEl = document.getElementById('sync-produk-' + produkId);

        if (!navigator.onLine) {
            if (syncEl) syncEl.classList.remove('d-none');

            const queue = JSON.parse(localStorage.getItem('desahub_write_queue') || '[]');
            queue.push({
                type: 'toggle_ketersediaan',
                produkId: produkId,
                tersedia: checkbox.checked,
                timestamp: Date.now()
            });
            localStorage.setItem('desahub_write_queue', JSON.stringify(queue));
        } else {
            // Online: langsung POST ke endpoint toggle
            fetch('/warung/kelola-produk/' + produkId + '/toggle', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tersedia: checkbox.checked })
            }).then(() => {
                if (syncEl) syncEl.classList.add('d-none');
            }).catch(() => {
                // fallback: antri
                if (syncEl) syncEl.classList.remove('d-none');
            });
        }
    }

    // ===== Animasi spin =====
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin {
            display: inline-block;
            animation: spin 1.5s linear infinite;
        }
    `;
    document.head.appendChild(style);

    // ===== SCAN BARCODE (QuaggaJS) =====
    let scannerActive = false;

    function bukaScanBarcode() {
        const modal = new bootstrap.Modal(document.getElementById('scanModal'));
        modal.show();
        setTimeout(initScanner, 500);
    }

    function initScanner() {
        if (scannerActive) return;
        scannerActive = true;

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#scanner-container'),
                constraints: { facingMode: "environment" }
            },
            decoder: {
                readers: [
                    "ean_reader", "ean_8_reader", "upc_reader", "upc_e_reader",
                    "code_128_reader", "code_39_reader", "code_39_vin_reader",
                    "codabar_reader", "i2of5_reader"
                ]
            }
        }, function(err) {
            if (err) {
                console.error(err);
                document.getElementById('scan-status').textContent = 'Gagal akses kamera: ' + err;
                return;
            }
            Quagga.start();
        });

        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            if (code) {
                document.getElementById('produk-barcode').value = code;
                document.getElementById('scan-status').textContent = 'Barcode terdeteksi: ' + code;
                cariProdukByBarcode(code);
                setTimeout(() => {
                    Quagga.stop();
                    scannerActive = false;
                    bootstrap.Modal.getInstance(document.getElementById('scanModal')).hide();
                }, 800);
            }
        });
    }

    function cariProdukByBarcode(barcode) {
        fetch('/warung/produk/barcode/' + encodeURIComponent(barcode), {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                document.getElementById('produk-nama').value = data.nama;
                if (data.harga) document.getElementById('produk-harga').value = data.harga;
                if (data.satuan) document.getElementById('produk-satuan').value = data.satuan;
                if (data.deskripsi) document.getElementById('produk-deskripsi').value = data.deskripsi;
            }
        })
        .catch(() => {});
    }

    // ===== RESTOK MODAL =====
    let restokProdukId = null;

    function bukaRestok(id, nama, hargaBeliLama) {
        restokProdukId = id;
        document.getElementById('restok-produk-nama').textContent = nama;
        document.getElementById('restok-harga-beli-lama').textContent = 'Rp' + Number(hargaBeliLama).toLocaleString('id-ID');
        document.getElementById('restok-qty').value = '';
        document.getElementById('restok-harga-beli-baru').value = '';
        document.getElementById('restok-error').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('restokModal')).show();
        setTimeout(() => document.getElementById('restok-qty').focus(), 300);
    }

    async function prosesRestok() {
        const qty = parseInt(document.getElementById('restok-qty').value) || 0;
        if (qty <= 0) {
            document.getElementById('restok-error').textContent = 'Jumlah stok harus diisi.';
            document.getElementById('restok-error').classList.remove('d-none');
            return;
        }

        const hargaBeliBaru = document.getElementById('restok-harga-beli-baru').value.trim();
        const btn = document.getElementById('btn-restok-submit');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
            const body = { qty: qty };
            if (hargaBeliBaru) body.harga_beli_baru = parseFloat(hargaBeliBaru);

            const resp = await fetch('/warung/kelola-produk/' + restokProdukId + '/restock', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body)
            });

            const data = await resp.json();

            if (data.success) {
                // Sukses — reload halaman untuk tampilkan stok terbaru
                bootstrap.Modal.getInstance(document.getElementById('restokModal'))?.hide();
                location.reload();
            } else {
                document.getElementById('restok-error').textContent = data.message || 'Gagal.';
                document.getElementById('restok-error').classList.remove('d-none');
            }
        } catch (e) {
            document.getElementById('restok-error').textContent = 'Gagal terhubung ke server.';
            document.getElementById('restok-error').classList.remove('d-none');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>

{{-- ========== MODAL RESTOK ========== --}}
<div class="modal fade" id="restokModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-plus-circle me-1" style="color: var(--warna-aksen-kedua);"></i>Restok
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-2">
                <p class="mb-2 fw-semibold" style="font-size: 0.85rem;" id="restok-produk-nama"></p>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Harga Beli Sebelumnya</label>
                    <div class="text-muted" style="font-size: 0.9rem; font-weight: 600;" id="restok-harga-beli-lama">Rp0</div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Jumlah Stok Baru *</label>
                    <input type="number" id="restok-qty" class="form-control form-control-sm" placeholder="Contoh: 100" min="1">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size: 0.8rem;">Harga Beli Baru <span class="text-muted fw-normal">(Rp)</span></label>
                    <input type="number" id="restok-harga-beli-baru" class="form-control form-control-sm" placeholder="Kosongi jika harga tetap" min="0">
                    <small class="text-muted" style="font-size: 0.7rem;">AVCO: harga beli akan dihitung rata-rata tertimbang</small>
                </div>
                <div id="restok-error" class="alert alert-danger d-none py-1" style="font-size: 0.75rem;"></div>
                <button class="btn w-100 fw-semibold rounded-pill"
                        style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.85rem; height: 36px;"
                        id="btn-restok-submit"
                        onclick="prosesRestok()">
                    <i class="bi bi-check-lg me-1"></i> Restok
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL SCAN BARCODE ========== --}}
<div class="modal fade" id="scanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-family: var(--font-judul);">Scan Barcode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="scanner-container" style="width: 100%; min-height: 240px; background: #000; border-radius: 8px; overflow: hidden;"></div>
                <p class="text-center mt-2 mb-0" id="scan-status" style="font-size: 0.8rem; color: var(--warna-aksen-kedua);">
                    Arahkan kamera ke barcode produk...
                </p>
            </div>
        </div>
    </div>
</div>

{{-- QuaggaJS --}}
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
