@extends('layouts.warung')

@section('content')
<div id="pos-app" class="pos-container">

    {{-- Header — Scanner + Search --}}
    <div class="pos-header mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="input-group flex-grow-1">
                <span class="input-group-text" style="background: #fff; border-color: var(--warna-netral-garis); cursor: pointer;" onclick="toggleBarcodeCamera()" id="barcode-icon" title="Scan Barcode">
                    <i class="bi bi-upc-scan" style="color: var(--warna-aksen-utama);"></i>
                </span>
                <input type="text" id="pos-barcode-input" class="form-control" placeholder="Scan barcode / cari produk..."
                       style="border-color: var(--warna-netral-garis); background: #fff;"
                       autofocus
                       onkeydown="handleBarcodeKey(event)">
                <button class="btn" style="background: var(--warna-aksen-utama); color: #fff; border: none;" onclick="cariManual()" title="Cari Produk">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        {{-- Camera scanner container (hidden by default) --}}
        <div id="camera-scan-container" class="d-none mb-2 position-relative" style="border-radius: 12px; overflow: hidden; background: #000;">
            <video id="camera-video" class="w-100" autoplay playsinline style="max-height: 200px; object-fit: cover;"></video>
            <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle" style="width: 32px; height: 32px; padding: 0;" onclick="stopCamera()" title="Tutup Kamera">
                <i class="bi bi-x"></i>
            </button>
            <div id="camera-status" class="position-absolute bottom-0 start-0 end-0 p-2 text-center text-white" style="background: rgba(0,0,0,0.6); font-size: 0.75rem;">
                Arahkan kamera ke barcode
            </div>
        </div>

        {{-- Offline Banner --}}
        <div id="pos-offline-banner" class="alert alert-warning py-2 mb-2 d-none" style="background-color: #FFF3E0; border-color: #FF9800; color: #E65100; font-size: 0.8rem;">
            <i class="bi bi-wifi-off me-1"></i> Sedang Offline — transaksi tersimpan lokal
        </div>

        {{-- Sync Pending Indicator --}}
        <div id="sync-pending-badge" class="d-none text-center py-1 mb-2 rounded" style="background: #FFF3E0; color: #E65100; font-size: 0.75rem;">
            <i class="bi bi-hourglass-split me-1"></i> <span id="sync-pending-count">0</span> transaksi menunggu sinkron
        </div>
    </div>

    {{-- Grid Produk --}}
    <div class="row g-2 mb-3" id="pos-grid">
        @foreach ($produkList as $p)
            @php
                $stok = $p['stok'] ?? 0;
                $tersedia = $stok > 0;
                $barcode = $p['barcode'] ?? '';
            @endphp
            <div class="col-6 col-md-4 col-lg-3 col-xl-2 produk-item"
                 data-id="{{ $p['id'] }}"
                 data-nama="{{ strtolower($p['nama']) }}"
                 data-barcode="{{ $barcode }}"
                 data-harga="{{ $p['harga'] }}"
                 data-stok="{{ $stok }}"
                 onclick="{{ $tersedia ? 'tambahKeranjang(' . $p['id'] . ')' : '' }}">
                <div class="card border-0 shadow-sm h-100 {{ !$tersedia ? 'opacity-50' : '' }}"
                     style="cursor: {{ $tersedia ? 'pointer' : 'not-allowed' }}; background: #fff; transition: background 0.15s;">
                    <div class="card-body p-2 p-md-3">
                        <div class="fw-semibold mb-1 text-truncate" style="font-family: var(--font-judul); font-size: 0.85rem;">
                            {{ $p['nama'] }}
                        </div>
                        <div class="fw-bold mb-1" style="font-family: var(--font-judul); color: var(--warna-aksen-utama); font-size: 0.95rem;">
                            Rp{{ number_format($p['harga'], 0, ',', '.') }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="font-size: 0.7rem;">
                                Stok: {{ $stok }}
                            </small>
                            @if ($barcode)
                                <small class="text-muted" style="font-size: 0.65rem;" title="Barcode: {{ $barcode }}">📊</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if (empty($produkList) || count($produkList) === 0)
        <div class="text-center text-muted py-5">
            <i class="bi bi-box-seam display-3 d-block mb-2"></i>
            <p>Belum ada produk. Tambahkan di <a href="{{ route('warung.kelola-produk') }}">Kelola Produk</a>.</p>
        </div>
    @endif

    {{-- Keranjang Floating Button --}}
    <div class="position-fixed bottom-0 start-0 end-0 p-3" style="background: linear-gradient(to top, #fff 0%, rgba(255,255,255,0.9) 80%, transparent 100%); z-index: 990;">
        <button class="btn w-100 fw-semibold rounded-pill py-3 d-flex align-items-center justify-content-between px-4"
                style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                onclick="bukaKeranjang()">
            <span>
                <i class="bi bi-cart3 me-2"></i>
                Keranjang: <span id="keranjang-count">0</span> item
            </span>
            <span class="fw-bold" id="keranjang-total">Rp0</span>
        </button>
    </div>

    {{-- ========== MODAL: KERANJANG ========== --}}
    <div class="modal fade" id="keranjangModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="font-family: var(--font-judul);">
                        <i class="bi bi-cart3 me-2"></i>Keranjang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetMetodePembayaran()"></button>
                </div>
                <div class="modal-body" id="keranjang-body">
                    {{-- Items --}}
                    <div id="keranjang-items" class="mb-3">
                        <p class="text-center text-muted py-3" style="font-size: 0.85rem;">Keranjang kosong</p>
                    </div>

                    {{-- Subtotal / Total Rinci --}}
                    <div class="border-top pt-3" id="keranjang-rincian" style="display: none;">
                        <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
                            <span class="text-muted">Subtotal</span>
                            <span id="subtotal-text">Rp0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size: 0.85rem;">
                            <span class="text-muted">Total Item</span>
                            <span id="total-item-text">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                            <span class="fw-bold fs-6">TOTAL</span>
                            <span class="fw-bold fs-4" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);" id="modal-total">
                                Rp0
                            </span>
                        </div>

                        {{-- Metode: Cash / Tempo --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Metode Pembayaran</label>
                            <div class="d-flex gap-2">
                                <button class="btn flex-grow-1 fw-semibold metode-btn active" data-metode="cash"
                                        style="background: var(--warna-aksen-utama); color: #fff;"
                                        onclick="pilihMetode('cash')">
                                    <i class="bi bi-cash-stack me-1"></i> TUNAI
                                </button>
                                <button class="btn flex-grow-1 fw-semibold metode-btn" data-metode="tempo"
                                        style="background: #E0E0E0; color: #333;"
                                        onclick="pilihMetode('tempo')">
                                    <i class="bi bi-calendar-check me-1"></i> TEMPO
                                </button>
                            </div>
                        </div>

                        {{-- Input Cash Amount --}}
                        <div id="cash-input-area" class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.85rem;">Uang Diterima (Cash)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text" style="background: #fff;">Rp</span>
                                <input type="number" id="cash-diterima" class="form-control text-end fw-bold"
                                       style="font-size: 1.2rem;"
                                       placeholder="0"
                                       min="0"
                                       oninput="hitungKembalian()">
                            </div>
                            <div class="d-flex justify-content-between mt-2" id="kembalian-area" style="display: none !important;">
                                <span class="fw-semibold" style="font-size: 0.85rem;">Kembalian:</span>
                                <span class="fw-bold fs-5" style="color: var(--warna-aksen-kedua);" id="kembalian-text">Rp0</span>
                            </div>
                            {{-- Quick Cash Suggestions --}}
                            <div class="d-flex gap-1 mt-2 flex-wrap" id="cash-suggestions"></div>
                        </div>

                        {{-- Tempo: Pilih Pelanggan --}}
                        <div id="tempo-area" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Pilih Pelanggan</label>
                                <div class="input-group">
                                    <select id="pelanggan-select" class="form-select" onchange="onPelangganChange()">
                                        <option value="">-- Pilih Pelanggan --</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" onclick="bukaModalTambahPelanggan()" title="Tambah Pelanggan Baru">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div id="pelanggan-info" class="mt-2 d-none" style="font-size: 0.8rem; background: #f5f5f5; border-radius: 8px; padding: 8px 12px;">
                                    <div><strong id="pelanggan-info-nama"></strong></div>
                                    <div class="text-muted" id="pelanggan-info-hp"></div>
                                    <div class="text-danger" id="pelanggan-info-utang"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Jatuh Tempo</label>
                                <input type="date" id="jatuh-tempo-input" class="form-control"
                                       value="{{ now()->addDays(7)->toDateString() }}"
                                       min="{{ now()->toDateString() }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 0.85rem;">Catatan (opsional)</label>
                                <input type="text" id="catatan-tempo-input" class="form-control" placeholder="Contoh: Bayar minggu depan" maxlength="500">
                            </div>
                            <div class="alert alert-warning py-2" style="font-size: 0.8rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Transaksi tempo akan dicatat sebagai <strong>piutang</strong> dan muncul di pengingat pelanggan.
                            </div>
                        </div>

                        {{-- Tombol Bayar --}}
                        <button id="btn-bayar" class="btn w-100 fw-semibold rounded-pill py-3"
                                style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 1rem;"
                                onclick="prosesPembayaran()"
                                disabled>
                            <i class="bi bi-check-circle me-2"></i> BAYAR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: KONFIRMASI ========== --}}
    <div class="modal fade" id="konfirmasiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body py-4">
                    <i id="konfirmasi-icon" class="bi bi-check-circle display-1 mb-3" style="color: var(--warna-aksen-kedua);"></i>
                    <h5 class="fw-bold mb-2" style="font-family: var(--font-judul);" id="konfirmasi-judul">Transaksi Selesai</h5>
                    <p class="text-muted mb-1" style="font-size: 0.9rem;" id="konfirmasi-total-text">Rp0 diterima</p>
                    <p class="text-muted mb-3" style="font-size: 0.8rem;" id="konfirmasi-sub-text"></p>
                    <button class="btn fw-semibold rounded-pill px-4"
                            style="background-color: var(--warna-aksen-utama); color: #fff;"
                            onclick="transaksiBaru()">
                        <i class="bi bi-arrow-repeat me-1"></i> Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: TAMBAH PELANGGAN ========== --}}
    <div class="modal fade" id="tambahPelangganModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="font-family: var(--font-judul);">
                        <i class="bi bi-person-plus me-2"></i>Tambah Pelanggan Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Pelanggan *</label>
                        <input type="text" id="pelanggan-nama-input" class="form-control" placeholder="Nama pelanggan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">No. HP (opsional)</label>
                        <input type="text" id="pelanggan-hp-input" class="form-control" placeholder="0812-xxxx-xxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan (opsional)</label>
                        <input type="text" id="pelanggan-catatan-input" class="form-control" placeholder="Rumah RT 03, dll.">
                    </div>
                    <div id="tambah-pelanggan-error" class="alert alert-danger d-none py-2" style="font-size: 0.8rem;"></div>
                    <button class="btn w-100 fw-semibold rounded-pill py-2"
                            style="background-color: var(--warna-aksen-utama); color: #fff;"
                            onclick="simpanPelangganBaru()">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pelanggan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Sync Indicator Overlay --}}
    <div id="sync-indicator" class="position-fixed top-0 start-0 end-0 p-2 text-center d-none"
         style="background-color: #FFF3E0; color: #E65100; font-size: 0.8rem; z-index: 9999; display: none;">
        <i class="bi bi-arrow-repeat spin me-1"></i> Menunggu sinkron...
    </div>

    {{-- Camera Overlay for manual barcode entry --}}
    <div class="modal fade" id="manualBarcodeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="font-family: var(--font-judul);">Cari / Masukkan Barcode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size: 0.85rem;">Ketik barcode atau nama produk:</p>
                    <input type="text" id="manual-search-input" class="form-control form-control-lg mb-2"
                           placeholder="Barcode / nama produk..."
                           onkeydown="if(event.key==='Enter')cariManualSubmit()">
                    <div id="manual-search-result" class="mt-2" style="font-size: 0.85rem;"></div>
                    <button class="btn w-100 fw-semibold rounded-pill mt-2"
                            style="background-color: var(--warna-aksen-utama); color: #fff;"
                            onclick="cariManualSubmit()">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.pos-container {
    padding-bottom: 110px;
}
.produk-item {
    transition: transform 0.15s;
    cursor: pointer;
}
.produk-item:active {
    transform: scale(0.96);
}
.metode-btn.active {
    background-color: var(--warna-aksen-utama) !important;
    color: #fff !important;
}
.metode-btn:not(.active) {
    background-color: #E0E0E0 !important;
    color: #333 !important;
}
#pos-barcode-input:focus {
    border-color: var(--warna-aksen-utama) !important;
    box-shadow: 0 0 0 0.2rem rgba(56, 142, 60, 0.15);
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 1.5s linear infinite;
}
.cash-suggest-btn {
    font-size: 0.8rem;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--warna-netral-garis);
    background: #fff;
    cursor: pointer;
}
.cash-suggest-btn:hover {
    background: var(--warna-aksen-utama);
    color: #fff;
    border-color: var(--warna-aksen-utama);
}
.keranjang-qty-btn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    font-size: 1.1rem;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
}
.keranjang-qty-btn.minus {
    background: #ffebee;
    color: #d32f2f;
}
.keranjang-qty-btn.plus {
    background: var(--warna-aksen-utama);
    color: #fff;
}
.keranjang-qty-btn.minus:hover { background: #ffcdd2; }
.keranjang-qty-btn.plus:hover { background: var(--warna-aksen-kedua); }
</style>

<script>
// =============================================
// POS STATE
// =============================================
let keranjang = [];
let produkData = {};
let pelangganCache = [];
let metodeAktif = 'cash';
let cameraStream = null;
let barcodeDetector = null;
let syncPendingCount = 0;

// Populate produkData from server-rendered grid
document.querySelectorAll('.produk-item').forEach(el => {
    const id = parseInt(el.dataset.id);
    produkData[id] = {
        id: id,
        nama: el.dataset.nama || '',
        barcode: el.dataset.barcode || '',
        harga: parseInt(el.dataset.harga) || 0,
        stok: parseInt(el.dataset.stok) || 0,
    };
});

// =============================================
// BARCODE SCANNER
// =============================================

// Initialize BarcodeDetector API if available (Chrome 88+)
if ('BarcodeDetector' in window) {
    barcodeDetector = new BarcodeDetector({
        formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'qr_code']
    });
}

// Handle input from hardware barcode scanner (emulates keyboard, ends with Enter)
function handleBarcodeKey(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const code = document.getElementById('pos-barcode-input').value.trim();
        if (code) {
            cariByBarcode(code);
        }
    }
}

// Toggle camera scanner
async function toggleBarcodeCamera() {
    const container = document.getElementById('camera-scan-container');
    if (!container.classList.contains('d-none')) {
        stopCamera();
        return;
    }

    if (!barcodeDetector) {
        alert('Browser Anda tidak mendukung BarcodeDetector API. Gunakan Chrome 88+ atau masukkan barcode manual.');
        cariManual();
        return;
    }

    try {
        container.classList.remove('d-none');
        const video = document.getElementById('camera-video');
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
        });
        video.srcObject = cameraStream;
        video.play();

        document.getElementById('camera-status').textContent = 'Arahkan kamera ke barcode...';
        scanLoop(video);
    } catch (err) {
        console.error('Camera error:', err);
        container.classList.add('d-none');
        if (err.name === 'NotAllowedError') {
            alert('Izin kamera ditolak. Silakan izinkan akses kamera atau gunakan input manual.');
        } else {
            alert('Tidak dapat mengakses kamera. Gunakan input manual.');
        }
        cariManual();
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    document.getElementById('camera-scan-container').classList.add('d-none');
}

async function scanLoop(video) {
    if (!cameraStream) return; // stopped

    try {
        const barcodes = await barcodeDetector.detect(video);
        if (barcodes.length > 0) {
            const code = barcodes[0].rawValue;
            document.getElementById('camera-status').textContent = 'Barcode terdeteksi: ' + code;
            document.getElementById('pos-barcode-input').value = code;

            // Brief pause so user can see the detection
            await new Promise(r => setTimeout(r, 600));

            cariByBarcode(code);
            stopCamera();
            return;
        }
    } catch (e) {
        // detection may fail, just retry
    }

    if (cameraStream) {
        requestAnimationFrame(() => scanLoop(video));
    }
}

// Manual barcode/search prompt
function cariManual() {
    document.getElementById('manual-search-input').value = '';
    document.getElementById('manual-search-result').innerHTML = '';
    new bootstrap.Modal(document.getElementById('manualBarcodeModal')).show();
    setTimeout(() => {
        document.getElementById('manual-search-input').focus();
    }, 300);
}

function cariManualSubmit() {
    const val = document.getElementById('manual-search-input').value.trim();
    if (!val) return;

    // Check if it looks like a barcode (digits only, reasonable length)
    if (/^\d{8,14}$/.test(val)) {
        cariByBarcode(val);
    } else {
        // Search by name
        const found = Object.values(produkData).filter(p =>
            p.nama.toLowerCase().includes(val.toLowerCase())
        );
        const resultDiv = document.getElementById('manual-search-result');
        if (found.length === 0) {
            resultDiv.innerHTML = '<div class="text-danger">Produk tidak ditemukan.</div>';
        } else if (found.length === 1) {
            resultDiv.innerHTML = `<div class="text-success">Menambahkan: <strong>${found[0].nama}</strong></div>`;
            tambahKeranjang(found[0].id);
            bootstrap.Modal.getInstance(document.getElementById('manualBarcodeModal'))?.hide();
        } else {
            resultDiv.innerHTML = found.map(p =>
                `<div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom cursor-pointer"
                      onclick="tambahKeranjang(${p.id}); bootstrap.Modal.getInstance(document.getElementById('manualBarcodeModal'))?.hide();"
                      style="cursor:pointer;">
                    <span>${p.nama}</span>
                    <span class="fw-bold" style="color: var(--warna-aksen-utama);">Rp${p.harga.toLocaleString('id-ID')}</span>
                </div>`
            ).join('');
        }
    }
}

// Barcode lookup — CLIENT SIDE first
function cariByBarcode(code) {
    // 1. Search in local produkData (offline-compatible)
    const match = Object.values(produkData).find(p => p.barcode === code);

    if (match) {
        tambahKeranjang(match.id);
        document.getElementById('pos-barcode-input').value = '';
        // Flash the matched product card
        const el = document.querySelector(`.produk-item[data-id="${match.id}"]`);
        if (el) {
            el.style.transform = 'scale(1.05)';
            el.style.boxShadow = '0 0 16px rgba(56,142,60,0.4)';
            setTimeout(() => {
                el.style.transform = '';
                el.style.boxShadow = '';
            }, 500);
        }
        return;
    }

    // 2. Try server lookup (online only)
    if (navigator.onLine) {
        fetch(`/warung/produk/barcode/${encodeURIComponent(code)}`)
            .then(r => r.json())
            .then(data => {
                if (data.found) {
                    if (data.harga) {
                        // Has price, add to keranjang
                        alert(`Produk ditemukan: ${data.nama} (${data.source})`);
                        document.getElementById('pos-barcode-input').value = '';
                    } else {
                        // Found but no price (e.g., OpenFoodFacts), inform user
                        alert(`Barcode dikenali: ${data.nama}. Silakan tambahkan produk ini di Kelola Produk untuk mengisi harga.`);
                    }
                } else {
                    alert('Produk dengan barcode ini tidak ditemukan.');
                    document.getElementById('pos-barcode-input').value = '';
                }
            })
            .catch(() => {
                alert('Gagal mencari barcode. Coba lagi.');
            });
    } else {
        alert('Barcode tidak ditemukan di data offline. Silakan cari manual atau coba lagi saat online.');
    }
}

// =============================================
// KERANJANG (CART)
// =============================================

function tambahKeranjang(id) {
    const produk = produkData[id];
    if (!produk) return;

    if (produk.stok <= 0) {
        alert('Stok habis!');
        return;
    }

    const existing = keranjang.find(item => item.id === id);
    if (existing) {
        if (existing.qty >= produk.stok) {
            alert('Stok tidak cukup!');
            return;
        }
        existing.qty++;
    } else {
        keranjang.push({
            id: id,
            nama: produk.nama,
            harga: produk.harga,
            qty: 1,
        });
    }

    updateKeranjangUI();
}

function updateQty(id, delta) {
    const item = keranjang.find(i => i.id === id);
    if (!item) return;

    const produk = produkData[id];
    item.qty += delta;

    if (item.qty <= 0) {
        keranjang = keranjang.filter(i => i.id !== id);
    } else if (produk && item.qty > produk.stok) {
        item.qty = produk.stok;
        alert('Stok maksimal: ' + produk.stok);
    }

    updateKeranjangUI();
    renderKeranjangItems();
    if (keranjang.length === 0) {
        bootstrap.Modal.getInstance(document.getElementById('keranjangModal'))?.hide();
    }
}

function updateKeranjangUI() {
    const totalItems = keranjang.reduce((sum, item) => sum + item.qty, 0);
    const totalHarga = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);

    document.getElementById('keranjang-count').textContent = totalItems;
    document.getElementById('keranjang-total').textContent = 'Rp' + totalHarga.toLocaleString('id-ID');
    document.getElementById('modal-total').textContent = totalHarga.toLocaleString('id-ID');
    document.getElementById('subtotal-text').textContent = 'Rp' + totalHarga.toLocaleString('id-ID');
    document.getElementById('total-item-text').textContent = totalItems;

    // Update cash suggestions
    updateCashSuggestions(totalHarga);

    // Enable/disable bayar button based on metode
    updateBayarButtonState();

    // Show/hide rincian
    const rincian = document.getElementById('keranjang-rincian');
    rincian.style.display = keranjang.length > 0 ? '' : 'none';
}

function renderKeranjangItems() {
    const container = document.getElementById('keranjang-items');

    if (keranjang.length === 0) {
        container.innerHTML = '<p class="text-center text-muted py-3" style="font-size: 0.85rem;">Keranjang kosong</p>';
        return;
    }

    container.innerHTML = keranjang.map(item => {
        const sub = item.harga * item.qty;
        return `
            <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-semibold text-truncate" style="font-size: 0.85rem;">${item.nama}</div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted" style="font-size: 0.75rem;">Rp${item.harga.toLocaleString('id-ID')}</small>
                        <small class="fw-bold" style="color: var(--warna-aksen-utama); font-size: 0.8rem;">
                            = Rp${sub.toLocaleString('id-ID')}
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                    <button class="keranjang-qty-btn minus" onclick="updateQty(${item.id}, -1)" title="Kurangi">−</button>
                    <span class="fw-semibold" style="min-width: 24px; text-align: center; font-size: 0.9rem;">${item.qty}</span>
                    <button class="keranjang-qty-btn plus" onclick="updateQty(${item.id}, 1)" title="Tambah">+</button>
                </div>
            </div>
        `;
    }).join('');
}

function bukaKeranjang() {
    if (keranjang.length === 0) {
        alert('Keranjang kosong!');
        return;
    }
    renderKeranjangItems();
    updateKeranjangUI();
    loadPelangganCache();
    resetCashInput();
    updateBayarButtonState();
    new bootstrap.Modal(document.getElementById('keranjangModal')).show();
}

// =============================================
// METODE PEMBAYARAN
// =============================================

function pilihMetode(metode) {
    metodeAktif = metode;

    document.querySelectorAll('.metode-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.metode === metode);
    });

    document.getElementById('cash-input-area').style.display = metode === 'cash' ? '' : 'none';
    document.getElementById('tempo-area').style.display = metode === 'tempo' ? '' : 'none';

    updateBayarButtonState();
}

function resetMetodePembayaran() {
    // Don't reset metode, just close
}

function updateCashSuggestions(total) {
    const container = document.getElementById('cash-suggestions');
    if (!total || total <= 0) {
        container.innerHTML = '';
        return;
    }

    // Round up suggestions
    const roundTo = (val, nearest) => Math.ceil(val / nearest) * nearest;
    const suggestions = [];

    // Exact
    suggestions.push(total);

    // Round to nearest 1,000
    const r1k = roundTo(total, 1000);
    if (r1k !== total) suggestions.push(r1k);

    // Round to nearest 5,000
    const r5k = roundTo(total, 5000);
    if (!suggestions.includes(r5k)) suggestions.push(r5k);

    // Round to nearest 10,000
    const r10k = roundTo(total, 10000);
    if (!suggestions.includes(r10k)) suggestions.push(r10k);

    // Round to nearest 50,000
    const r50k = roundTo(total, 50000);
    if (!suggestions.includes(r50k)) suggestions.push(r50k);

    // Round to nearest 100,000
    const r100k = roundTo(total, 100000);
    if (!suggestions.includes(r100k)) suggestions.push(r100k);

    // Sort, limit to 5
    const unique = [...new Set(suggestions)].sort((a, b) => a - b).slice(0, 5);

    container.innerHTML = unique.map(s =>
        `<button class="cash-suggest-btn" onclick="setCashAmount(${s})">Rp${s.toLocaleString('id-ID')}</button>`
    ).join('');
}

function setCashAmount(amount) {
    document.getElementById('cash-diterima').value = amount;
    hitungKembalian();
}

function hitungKembalian() {
    const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    const kembali = cash - total;

    const area = document.getElementById('kembalian-area');
    if (cash > 0) {
        area.style.display = 'flex !important';
        document.getElementById('kembalian-text').textContent = 'Rp' + kembali.toLocaleString('id-ID');
        document.getElementById('kembalian-text').style.color = kembali >= 0 ? 'var(--warna-aksen-kedua)' : '#d32f2f';
    } else {
        area.style.display = 'none !important';
    }

    updateBayarButtonState();
}

function resetCashInput() {
    document.getElementById('cash-diterima').value = '';
    document.getElementById('kembalian-area').style.display = 'none !important';
    updateCashSuggestions(keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0));
}

function updateBayarButtonState() {
    const btn = document.getElementById('btn-bayar');
    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);

    if (keranjang.length === 0) {
        btn.disabled = true;
        return;
    }

    if (metodeAktif === 'cash') {
        const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
        btn.disabled = cash < total;
    } else {
        // Tempo: need pelanggan selected
        const pelangganId = document.getElementById('pelanggan-select').value;
        btn.disabled = !pelangganId;
    }
}

// =============================================
// PELANGGAN (for tempo)
// =============================================

async function loadPelangganCache() {
    if (pelangganCache.length > 0) return; // already loaded

    if (navigator.onLine) {
        try {
            const resp = await fetch('/warung/pos/pelanggan');
            if (resp.ok) {
                pelangganCache = await resp.json();
                populatePelangganSelect();
            }
        } catch (e) {
            console.warn('Gagal load pelanggan:', e);
        }
    }

    // Try to load from localStorage cache
    const cached = localStorage.getItem('pos_pelanggan_cache');
    if (cached && pelangganCache.length === 0) {
        try {
            pelangganCache = JSON.parse(cached);
            populatePelangganSelect();
        } catch (e) {}
    }
}

function populatePelangganSelect() {
    const select = document.getElementById('pelanggan-select');
    const currentVal = select.value;
    select.innerHTML = '<option value="">-- Pilih Pelanggan --</option>';
    pelangganCache.forEach(p => {
        select.innerHTML += `<option value="${p.id}">${p.nama}${p.no_hp ? ' (' + p.no_hp + ')' : ''}</option>`;
    });
    if (currentVal) select.value = currentVal;
}

function onPelangganChange() {
    const id = document.getElementById('pelanggan-select').value;
    const infoDiv = document.getElementById('pelanggan-info');

    if (!id) {
        infoDiv.classList.add('d-none');
        updateBayarButtonState();
        return;
    }

    const p = pelangganCache.find(x => x.id == id);
    if (p) {
        document.getElementById('pelanggan-info-nama').textContent = p.nama;
        document.getElementById('pelanggan-info-hp').textContent = p.no_hp || 'Tanpa No. HP';
        document.getElementById('pelanggan-info-utang').textContent = p.total_utang_aktif > 0
            ? 'Utang aktif: Rp' + parseFloat(p.total_utang_aktif).toLocaleString('id-ID')
            : 'Tidak ada utang aktif';
        infoDiv.classList.remove('d-none');
    }

    updateBayarButtonState();
}

function bukaModalTambahPelanggan() {
    document.getElementById('pelanggan-nama-input').value = '';
    document.getElementById('pelanggan-hp-input').value = '';
    document.getElementById('pelanggan-catatan-input').value = '';
    document.getElementById('tambah-pelanggan-error').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('tambahPelangganModal')).show();
}

async function simpanPelangganBaru() {
    const nama = document.getElementById('pelanggan-nama-input').value.trim();
    if (!nama) {
        document.getElementById('tambah-pelanggan-error').textContent = 'Nama pelanggan wajib diisi.';
        document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
        return;
    }

    if (!navigator.onLine) {
        document.getElementById('tambah-pelanggan-error').textContent = 'Tambah pelanggan baru hanya bisa saat online. Silakan coba lagi nanti.';
        document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
        return;
    }

    const btn = document.querySelector('#tambahPelangganModal .btn-primary, #tambahPelangganModal button:last-child');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';

    try {
        const resp = await fetch('/warung/pos/pelanggan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                nama: nama,
                no_hp: document.getElementById('pelanggan-hp-input').value.trim(),
                catatan: document.getElementById('pelanggan-catatan-input').value.trim(),
            })
        });

        const data = await resp.json();
        if (data.success) {
            pelangganCache.push(data.pelanggan);
            pelangganCache.sort((a, b) => a.nama.localeCompare(b.nama));
            localStorage.setItem('pos_pelanggan_cache', JSON.stringify(pelangganCache));
            populatePelangganSelect();
            document.getElementById('pelanggan-select').value = data.pelanggan.id;
            onPelangganChange();
            bootstrap.Modal.getInstance(document.getElementById('tambahPelangganModal'))?.hide();
        } else {
            document.getElementById('tambah-pelanggan-error').textContent = data.message || 'Gagal menyimpan pelanggan.';
            document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
        }
    } catch (e) {
        document.getElementById('tambah-pelanggan-error').textContent = 'Gagal terhubung ke server.';
        document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

// =============================================
// PROSES PEMBAYARAN
// =============================================

async function prosesPembayaran() {
    if (keranjang.length === 0) return;

    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    const items = keranjang.map(item => ({
        produk_id: item.id,
        qty: item.qty,
        harga_satuan: item.harga,
    }));

    let payload = {
        items: items,
        total: total,
        metode: metodeAktif,
        pelanggan_id: null,
        jatuh_tempo: null,
        catatan_tempo: null,
    };

    if (metodeAktif === 'tempo') {
        payload.pelanggan_id = parseInt(document.getElementById('pelanggan-select').value);
        payload.jatuh_tempo = document.getElementById('jatuh-tempo-input').value;
        payload.catatan_tempo = document.getElementById('catatan-tempo-input').value.trim() || null;
    }

    // Validate cash input
    if (metodeAktif === 'cash') {
        const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
        if (cash < total) {
            alert('Uang yang diterima kurang dari total!');
            return;
        }
    }

    // Disable button
    const btn = document.getElementById('btn-bayar');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

    try {
        const resp = await fetch('/warung/pos/transaksi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        const data = await resp.json();

        if (data.success) {
            // Close keranjang modal
            bootstrap.Modal.getInstance(document.getElementById('keranjangModal'))?.hide();

            // Show konfirmasi
            const konfirmasiTotal = document.getElementById('konfirmasi-total-text');
            const konfirmasiIcon = document.getElementById('konfirmasi-icon');
            const konfirmasiJudul = document.getElementById('konfirmasi-judul');
            const konfirmasiSub = document.getElementById('konfirmasi-sub-text');

            if (metodeAktif === 'tempo') {
                konfirmasiIcon.className = 'bi bi-clock-history display-1 mb-3';
                konfirmasiIcon.style.color = '#FF9800';
                konfirmasiJudul.textContent = 'Transaksi Tempo Tercatat';
                konfirmasiTotal.textContent = 'Rp' + total.toLocaleString('id-ID') + ' (piutang)';
                const p = pelangganCache.find(x => x.id == payload.pelanggan_id);
                konfirmasiSub.textContent = 'Pelanggan: ' + (p ? p.nama : '') + ' | Jatuh tempo: ' + (payload.jatuh_tempo || '-');
            } else {
                konfirmasiIcon.className = 'bi bi-check-circle display-1 mb-3';
                konfirmasiIcon.style.color = 'var(--warna-aksen-kedua)';
                konfirmasiJudul.textContent = 'Transaksi Selesai';
                konfirmasiTotal.textContent = 'Rp' + total.toLocaleString('id-ID') + ' diterima';
                const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
                const kembali = cash - total;
                konfirmasiSub.textContent = kembali > 0 ? 'Kembalian: Rp' + kembali.toLocaleString('id-ID') : '';
            }

            new bootstrap.Modal(document.getElementById('konfirmasiModal')).show();

            // Update stok cache locally
            keranjang.forEach(item => {
                if (produkData[item.id]) {
                    produkData[item.id].stok -= item.qty;
                    if (produkData[item.id].stok < 0) produkData[item.id].stok = 0;

                    // Update data-stok on grid element
                    const el = document.querySelector(`.produk-item[data-id="${item.id}"]`);
                    if (el) {
                        el.dataset.stok = produkData[item.id].stok;
                        const stokEl = el.querySelector('small.text-muted');
                        if (stokEl) stokEl.textContent = 'Stok: ' + produkData[item.id].stok;
                        if (produkData[item.id].stok <= 0) {
                            el.classList.add('opacity-50');
                            el.querySelector('.card').style.cursor = 'not-allowed';
                            el.onclick = null;
                        }
                    }
                }
            });

            // Reset keranjang
            keranjang = [];
            updateKeranjangUI();
            resetMetodePembayaran();

        } else {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
        }
    } catch (err) {
        console.error(err);
        alert('Gagal memproses transaksi. Pastikan koneksi internet tersedia.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        updateBayarButtonState();
    }
}

// =============================================
// TRANSAKSI BARU
// =============================================

function transaksiBaru() {
    bootstrap.Modal.getInstance(document.getElementById('konfirmasiModal'))?.hide();
    keranjang = [];
    updateKeranjangUI();
    renderKeranjangItems();
    document.getElementById('pos-barcode-input').value = '';
    document.getElementById('pos-barcode-input').focus();
    resetCashInput();
    resetMetodePembayaran();

    // Reload page to get fresh stock data after a few transactions
    const now = Date.now();
    if (!window._lastReload) window._lastReload = now;
    if (now - window._lastReload > 300000) { // 5 minutes
        window.location.reload();
    }
    window._lastReload = now;
}

// =============================================
// OFFLINE DETECTION
// =============================================

function updateOfflineStatus() {
    const banner = document.getElementById('pos-offline-banner');
    if (!navigator.onLine) {
        banner.classList.remove('d-none');
    } else {
        banner.classList.add('d-none');
    }
}

window.addEventListener('online', () => {
    updateOfflineStatus();
    // Reload pelanggan cache when back online
    pelangganCache = [];
    loadPelangganCache();
    // Update produkData stok from server
    fetch(window.location.href)
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            doc.querySelectorAll('.produk-item').forEach(el => {
                const id = parseInt(el.dataset.id);
                if (produkData[id]) {
                    produkData[id].stok = parseInt(el.dataset.stok) || 0;
                }
            });
            // Update grid visuals
            Object.entries(produkData).forEach(([id, data]) => {
                const el = document.querySelector(`.produk-item[data-id="${id}"]`);
                if (el) {
                    el.dataset.stok = data.stok;
                    const stokEl = el.querySelector('small.text-muted');
                    if (stokEl) stokEl.textContent = 'Stok: ' + data.stok;
                    if (data.stok <= 0) {
                        el.classList.add('opacity-50');
                        el.querySelector('.card').style.cursor = 'not-allowed';
                        el.onclick = null;
                    } else {
                        el.classList.remove('opacity-50');
                        el.querySelector('.card').style.cursor = 'pointer';
                        el.onclick = function() { tambahKeranjang(parseInt(id)); };
                    }
                }
            });
        })
        .catch(() => {});
});

window.addEventListener('offline', updateOfflineStatus);
document.addEventListener('DOMContentLoaded', () => {
    updateOfflineStatus();

    // Focus barcode input immediately
    setTimeout(() => {
        document.getElementById('pos-barcode-input').focus();
    }, 500);

    // Load pelanggan cache
    loadPelangganCache();
});
</script>
@endsection