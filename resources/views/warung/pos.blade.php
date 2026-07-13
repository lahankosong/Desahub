@extends('layouts.warung')

@section('content')
<div id="pos-app" class="pos-container">

    {{-- Header Scanner --}}
    <div class="pos-header">
        <div class="d-flex align-items-center gap-2">
            <div class="input-group flex-grow-1">
                <span class="input-group-text" style="background: #fff; border-color: #ddd; cursor: pointer; padding: 0 12px;" onclick="toggleBarcodeCamera()">
                    <i class="bi bi-upc-scan" style="color: var(--warna-aksen-utama); font-size: 1.2rem;"></i>
                </span>
                <input type="text" id="pos-barcode-input" class="form-control" placeholder="Scan / cari produk..."
                       style="border-color: #ddd; font-size: 0.95rem; height: 44px;"
                       autofocus
                       onkeydown="handleBarcodeKey(event)">
                <button class="btn" style="background: var(--warna-aksen-utama); color: #fff; border: none; height: 44px; padding: 0 14px;" onclick="cariManual()">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        {{-- Camera scanner --}}
        <div id="camera-scan-container" class="d-none mt-2 position-relative" style="border-radius: 10px; overflow: hidden; background: #000;">
            <video id="camera-video" class="w-100" autoplay playsinline style="max-height: 160px; object-fit: cover;"></video>
            <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-1 rounded-circle" style="width: 28px; height: 28px; padding: 0;" onclick="stopCamera()">
                <i class="bi bi-x"></i>
            </button>
            <div id="camera-status" class="position-absolute bottom-0 start-0 end-0 p-1 text-center text-white" style="background: rgba(0,0,0,0.6); font-size: 0.7rem;">
                Arahkan ke barcode
            </div>
        </div>

        {{-- Offline Banner --}}
        <div id="pos-offline-banner" class="alert alert-warning py-1 mb-1 d-none" style="font-size: 0.75rem; padding: 4px 10px;">
            <i class="bi bi-wifi-off me-1"></i> Offline — transaksi tersimpan lokal
        </div>
    </div>

    {{-- Main POS: Compact Layout --}}
    <div class="pos-body">
        {{-- Cart Items --}}
        <div class="cart-container">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold" style="font-size: 0.8rem; color: #666;">
                    <i class="bi bi-cart3 me-1"></i>Belanjaan
                </span>
                <span class="badge bg-secondary" id="item-count" style="font-size: 0.7rem;">0</span>
            </div>

            <div class="cart-items" id="cart-items">
                <div class="text-center text-muted py-3" style="font-size: 0.8rem;">
                    <i class="bi bi-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 4px;"></i>
                    Kosong
                </div>
            </div>
        </div>

        {{-- Payment Section --}}
        <div class="payment-section">
            {{-- Total --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold" style="font-size: 1rem;">Total</span>
                <span class="fw-bold" style="font-size: 1.3rem; color: var(--warna-aksen-utama);" id="keranjang-total">Rp0</span>
            </div>

            {{-- Metode --}}
            <div class="d-flex gap-1 mb-2">
                <button class="btn btn-sm flex-grow-1 fw-semibold metode-btn active" data-metode="cash"
                        style="background: var(--warna-aksen-utama); color: #fff; font-size: 0.75rem; padding: 6px 0;"
                        onclick="pilihMetode('cash')">
                    <i class="bi bi-cash-stack me-1"></i> TUNAI
                </button>
                <button class="btn btn-sm flex-grow-1 fw-semibold metode-btn" data-metode="tempo"
                        style="background: #E0E0E0; color: #333; font-size: 0.75rem; padding: 6px 0;"
                        onclick="pilihMetode('tempo')">
                    <i class="bi bi-calendar-check me-1"></i> TEMPO
                </button>
            </div>

            {{-- Cash Input --}}
            <div id="cash-input-area">
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="background: #fff; font-size: 0.8rem;">Rp</span>
                    <input type="number" id="cash-diterima" class="form-control text-end fw-bold"
                           style="font-size: 1rem; height: 36px;"
                           placeholder="0"
                           min="0"
                           oninput="hitungKembalian()">
                </div>
                <div id="kembalian-area" class="d-flex justify-content-between mt-1" style="display: none;">
                    <span style="font-size: 0.75rem;">Kembalian:</span>
                    <span class="fw-bold" style="color: var(--warna-aksen-kedua); font-size: 0.9rem;" id="kembalian-text">Rp0</span>
                </div>
                <div class="d-flex gap-1 mt-1 flex-wrap" id="cash-suggestions"></div>
            </div>

            {{-- Tempo Area --}}
            <div id="tempo-area" style="display: none;">
                <div class="d-flex gap-1">
                    <select id="pelanggan-select" class="form-select form-select-sm" onchange="onPelangganChange()" style="font-size: 0.75rem; height: 32px;">
                        <option value="">-- Pilih Pelanggan --</option>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="bukaModalTambahPelanggan()" style="padding: 0 10px; height: 32px;">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div id="pelanggan-info" class="mt-1 d-none" style="font-size: 0.7rem; background: #f5f5f5; border-radius: 6px; padding: 4px 8px;">
                    <span id="pelanggan-info-nama" class="fw-semibold"></span>
                    <span class="text-muted ms-2" id="pelanggan-info-hp"></span>
                    <span class="text-danger ms-2" id="pelanggan-info-utang"></span>
                </div>
                <div class="d-flex gap-1 mt-1">
                    <input type="date" id="jatuh-tempo-input" class="form-control form-control-sm"
                           value="{{ now()->addDays(7)->toDateString() }}"
                           style="font-size: 0.7rem; height: 32px;">
                    <input type="text" id="catatan-tempo-input" class="form-control form-control-sm" placeholder="Catatan" style="font-size: 0.7rem; height: 32px; max-width: 120px;">
                </div>
            </div>

            {{-- Bayar Button --}}
            <button id="btn-bayar" class="btn w-100 fw-semibold rounded-pill mt-2"
                    style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.9rem; height: 42px;"
                    onclick="prosesPembayaran()"
                    disabled>
                <i class="bi bi-check-circle me-1"></i> BAYAR
            </button>
        </div>
    </div>

    {{-- ========== MODAL: KONFIRMASI ========== --}}
    <div class="modal fade" id="konfirmasiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center">
                <div class="modal-body py-3">
                    <i id="konfirmasi-icon" class="bi bi-check-circle display-4 mb-2" style="color: var(--warna-aksen-kedua);"></i>
                    <h6 class="fw-bold mb-1" id="konfirmasi-judul">Transaksi Selesai</h6>
                    <p class="text-muted mb-1" style="font-size: 0.85rem;" id="konfirmasi-total-text"></p>
                    <p class="text-muted mb-2" style="font-size: 0.75rem;" id="konfirmasi-sub-text"></p>
                    <button class="btn fw-semibold rounded-pill px-3"
                            style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.85rem; height: 36px;"
                            onclick="transaksiBaru()">
                        <i class="bi bi-arrow-repeat me-1"></i> Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: TAMBAH PELANGGAN ========== --}}
    <div class="modal fade" id="tambahPelangganModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                        <i class="bi bi-person-plus me-1"></i>Tambah Pelanggan
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-2">
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Nama *</label>
                        <input type="text" id="pelanggan-nama-input" class="form-control form-control-sm" placeholder="Nama pelanggan">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">No. HP</label>
                        <input type="text" id="pelanggan-hp-input" class="form-control form-control-sm" placeholder="0812-xxxx-xxxx">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" style="font-size: 0.8rem;">Catatan</label>
                        <input type="text" id="pelanggan-catatan-input" class="form-control form-control-sm" placeholder="Catatan">
                    </div>
                    <div id="tambah-pelanggan-error" class="alert alert-danger d-none py-1" style="font-size: 0.75rem;"></div>
                    <button class="btn w-100 fw-semibold rounded-pill"
                            style="background-color: var(--warna-aksen-utama); color: #fff; font-size: 0.85rem; height: 36px;"
                            onclick="simpanPelangganBaru()">
                        <i class="bi bi-check-lg me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: CARI MANUAL ========== --}}
    <div class="modal fade" id="manualBarcodeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                        <i class="bi bi-search me-1"></i>Cari Produk
                    </h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-2">
                    <input type="text" id="manual-search-input" class="form-control form-control-sm mb-2"
                           placeholder="Ketik barcode / nama produk..."
                           autocomplete="off"
                           oninput="cariManualLive()"
                           onkeydown="if(event.key==='Enter')cariManualSubmit()">
                    <div id="manual-search-result" class="mt-1" style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.pos-container {
    height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 8px 12px;
    background: #f5f5f5;
    overflow: hidden;
}

.pos-header {
    flex-shrink: 0;
}

.pos-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 0;
}

.cart-container {
    flex: 1;
    background: #fff;
    border-radius: 10px;
    padding: 8px 10px;
    min-height: 0;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    margin: 0 -10px;
    padding: 0 10px;
}

.cart-items::-webkit-scrollbar {
    width: 3px;
}
.cart-items::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.8rem;
}
.cart-item:last-child {
    border-bottom: none;
}

.cart-item .item-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cart-item .item-qty {
    display: flex;
    align-items: center;
    gap: 2px;
}
.cart-item .item-qty button {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    font-size: 0.8rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
}
.cart-item .item-qty .minus {
    background: #ffebee;
    color: #d32f2f;
}
.cart-item .item-qty .plus {
    background: var(--warna-aksen-utama);
    color: #fff;
}
.cart-item .item-qty .qty-num {
    min-width: 20px;
    text-align: center;
    font-weight: 600;
    font-size: 0.8rem;
}
.cart-item .item-price {
    font-weight: 600;
    color: var(--warna-aksen-utama);
    font-size: 0.8rem;
    min-width: 70px;
    text-align: right;
}

.payment-section {
    flex-shrink: 0;
    background: #fff;
    border-radius: 10px;
    padding: 10px 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.metode-btn.active {
    background-color: var(--warna-aksen-utama) !important;
    color: #fff !important;
}
.metode-btn:not(.active) {
    background-color: #E0E0E0 !important;
    color: #333 !important;
}

#cash-suggestions .cash-suggest-btn {
    font-size: 0.7rem;
    padding: 2px 10px;
    border-radius: 12px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
}
#cash-suggestions .cash-suggest-btn:hover {
    background: var(--warna-aksen-utama);
    color: #fff;
    border-color: var(--warna-aksen-utama);
}

#pos-barcode-input:focus {
    border-color: var(--warna-aksen-utama) !important;
    box-shadow: 0 0 0 0.15rem rgba(56, 142, 60, 0.15);
}

/* Modal kecil untuk smartphone */
.modal-sm .modal-content {
    border-radius: 14px;
}

/* Hapus spinner number input */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    -moz-appearance: textfield;
}
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

// Populate produkData from server
@foreach ($produkList as $p)
    produkData[{{ $p['id'] }}] = {
        id: {{ $p['id'] }},
        nama: "{{ addslashes($p['nama']) }}",
        barcode: "{{ $p['barcode'] ?? '' }}",
        harga: {{ $p['harga'] }},
        stok: {{ $p['stok'] ?? 0 }},
    };
@endforeach

// =============================================
// BARCODE SCANNER
// =============================================

if ('BarcodeDetector' in window) {
    barcodeDetector = new BarcodeDetector({
        formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e']
    });
}

function handleBarcodeKey(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const code = document.getElementById('pos-barcode-input').value.trim();
        if (code) cariByBarcode(code);
    }
}

async function toggleBarcodeCamera() {
    const container = document.getElementById('camera-scan-container');
    if (!container.classList.contains('d-none')) { stopCamera(); return; }

    if (!barcodeDetector) {
        alert('Browser tidak mendukung scan kamera. Gunakan Chrome.');
        cariManual();
        return;
    }

    try {
        container.classList.remove('d-none');
        const video = document.getElementById('camera-video');
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 480 }, height: { ideal: 360 } }
        });
        video.srcObject = cameraStream;
        video.play();
        document.getElementById('camera-status').textContent = 'Arahkan ke barcode...';
        scanLoop(video);
    } catch (err) {
        container.classList.add('d-none');
        alert('Tidak bisa akses kamera. Gunakan input manual.');
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
    if (!cameraStream) return;
    try {
        const barcodes = await barcodeDetector.detect(video);
        if (barcodes.length > 0) {
            const code = barcodes[0].rawValue;
            document.getElementById('camera-status').textContent = '✓ ' + code;
            await new Promise(r => setTimeout(r, 400));
            cariByBarcode(code);
            stopCamera();
            return;
        }
    } catch (e) {}
    if (cameraStream) requestAnimationFrame(() => scanLoop(video));
}

function cariManual() {
    document.getElementById('manual-search-input').value = '';
    document.getElementById('manual-search-result').innerHTML = '';
    new bootstrap.Modal(document.getElementById('manualBarcodeModal')).show();
    setTimeout(() => document.getElementById('manual-search-input').focus(), 300);
}

function cariManualLive() {
    const val = document.getElementById('manual-search-input').value.trim();
    const resultDiv = document.getElementById('manual-search-result');

    if (!val) {
        resultDiv.innerHTML = '';
        return;
    }

    // Cari berdasarkan barcode dulu
    if (/^\d{8,14}$/.test(val)) {
        const match = Object.values(produkData).find(p => p.barcode === val);
        if (match) {
            resultDiv.innerHTML = `<div class="d-flex justify-content-between py-1 border-bottom" style="cursor:pointer; background:#f0fdf4;" onclick="tambahKeranjang(${match.id}); bootstrap.Modal.getInstance(document.getElementById('manualBarcodeModal'))?.hide();">
                <span><i class="bi bi-upc-scan me-1 text-muted"></i>${match.nama}</span>
                <span class="fw-bold" style="color: var(--warna-aksen-utama);">Rp${match.harga.toLocaleString('id-ID')}</span>
            </div>`;
            return;
        }
    }

    // Cari berdasarkan nama (case-insensitive)
    const found = Object.values(produkData).filter(p =>
        p.nama.toLowerCase().includes(val.toLowerCase())
    );

    if (found.length === 0) {
        resultDiv.innerHTML = '<div class="text-muted py-2" style="font-size:0.8rem;"><i class="bi bi-inbox me-1"></i>Tidak ditemukan</div>';
    } else {
        resultDiv.innerHTML = found.map(p =>
            `<div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="cursor:pointer;" onclick="tambahKeranjang(${p.id}); bootstrap.Modal.getInstance(document.getElementById('manualBarcodeModal'))?.hide();">
                <div>
                    <div class="fw-semibold" style="font-size:0.85rem;">${p.nama}</div>
                    <small class="text-muted">Stok: ${p.stok}</small>
                </div>
                <span class="fw-bold" style="color: var(--warna-aksen-utama); font-size:0.85rem;">Rp${p.harga.toLocaleString('id-ID')}</span>
            </div>`
        ).join('');
    }
}

function cariManualSubmit() {
    const val = document.getElementById('manual-search-input').value.trim();
    if (!val) return;

    if (/^\d{8,14}$/.test(val)) {
        cariByBarcode(val);
        bootstrap.Modal.getInstance(document.getElementById('manualBarcodeModal'))?.hide();
    } else {
        const found = Object.values(produkData).filter(p =>
            p.nama.toLowerCase().includes(val.toLowerCase())
        );
        if (found.length === 1) {
            tambahKeranjang(found[0].id);
            bootstrap.Modal.getInstance(document.getElementById('manualBarcodeModal'))?.hide();
        }
        // Jika lebih dari 1, biarkan user pilih dari hasil live search
    }
}

function cariByBarcode(code) {
    const match = Object.values(produkData).find(p => p.barcode === code);
    if (match) {
        tambahKeranjang(match.id);
        document.getElementById('pos-barcode-input').value = '';
        return;
    }

    if (navigator.onLine) {
        fetch(`/warung/produk/barcode/${encodeURIComponent(code)}`)
            .then(r => r.json())
            .then(data => {
                if (data.found && data.harga) {
                    tambahKeranjangDariServer(data);
                    document.getElementById('pos-barcode-input').value = '';
                } else {
                    alert('Produk tidak ditemukan.');
                }
            })
            .catch(() => alert('Gagal mencari barcode.'));
    } else {
        alert('Barcode tidak ditemukan di data offline.');
    }
}

function tambahKeranjangDariServer(data) {
    produkData[data.id] = {
        id: data.id,
        nama: data.nama,
        barcode: data.barcode || '',
        harga: data.harga,
        stok: data.stok || 0,
    };
    tambahKeranjang(data.id);
}

// =============================================
// KERANJANG
// =============================================

function tambahKeranjang(id) {
    const produk = produkData[id];
    if (!produk) return;
    if (produk.stok <= 0) { alert('Stok habis!'); return; }

    const existing = keranjang.find(item => item.id === id);
    if (existing) {
        if (existing.qty >= produk.stok) { alert('Stok tidak cukup!'); return; }
        existing.qty++;
    } else {
        keranjang.push({ id: id, nama: produk.nama, harga: produk.harga, qty: 1 });
    }
    updateKeranjangUI();
    // Auto-focus back to barcode input for next scan
    setTimeout(() => {
        const input = document.getElementById('pos-barcode-input');
        if (input) {
            input.focus();
            input.select();
        }
    }, 100);
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
        alert('Stok maks: ' + produk.stok);
    }
    updateKeranjangUI();
}

function updateKeranjangUI() {
    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    document.getElementById('keranjang-total').textContent = 'Rp' + total.toLocaleString('id-ID');
    document.getElementById('item-count').textContent = keranjang.length;

    // Render cart items compact
    const container = document.getElementById('cart-items');
    if (keranjang.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-3" style="font-size:0.8rem;">
            <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:4px;"></i>Kosong
        </div>`;
    } else {
        container.innerHTML = keranjang.map(item => `
            <div class="cart-item">
                <span class="item-name">${item.nama}</span>
                <div class="item-qty">
                    <button class="minus" onclick="updateQty(${item.id}, -1)">−</button>
                    <span class="qty-num">${item.qty}</span>
                    <button class="plus" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <span class="item-price">Rp${(item.harga * item.qty).toLocaleString('id-ID')}</span>
            </div>
        `).join('');
    }

    updateCashSuggestions(total);
    updateBayarButtonState();
}

// =============================================
// METODE & PEMBAYARAN
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

function updateCashSuggestions(total) {
    const container = document.getElementById('cash-suggestions');
    if (!total || total <= 0) { container.innerHTML = ''; return; }

    const roundTo = (val, nearest) => Math.ceil(val / nearest) * nearest;
    const suggestions = [total];
    [1000, 5000, 10000, 50000, 100000].forEach(n => {
        const r = roundTo(total, n);
        if (!suggestions.includes(r)) suggestions.push(r);
    });

    container.innerHTML = suggestions.sort((a,b) => a-b).slice(0,5).map(s =>
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
        area.style.display = 'flex';
        document.getElementById('kembalian-text').textContent = 'Rp' + kembali.toLocaleString('id-ID');
        document.getElementById('kembalian-text').style.color = kembali >= 0 ? 'var(--warna-aksen-kedua)' : '#d32f2f';
    } else {
        area.style.display = 'none';
    }
    updateBayarButtonState();
}

function updateBayarButtonState() {
    const btn = document.getElementById('btn-bayar');
    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    if (keranjang.length === 0) { btn.disabled = true; return; }

    if (metodeAktif === 'cash') {
        const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
        btn.disabled = cash < total;
    } else {
        btn.disabled = !document.getElementById('pelanggan-select').value;
    }
}

// =============================================
// PELANGGAN
// =============================================

async function loadPelangganCache() {
    if (pelangganCache.length > 0) return;
    if (navigator.onLine) {
        try {
            const resp = await fetch('/warung/pos/pelanggan');
            if (resp.ok) {
                pelangganCache = await resp.json();
                populatePelangganSelect();
            }
        } catch (e) {}
    }
    const cached = localStorage.getItem('pos_pelanggan_cache');
    if (cached && pelangganCache.length === 0) {
        try { pelangganCache = JSON.parse(cached); populatePelangganSelect(); } catch (e) {}
    }
}

function populatePelangganSelect() {
    const select = document.getElementById('pelanggan-select');
    const currentVal = select.value;
    select.innerHTML = '<option value="">-- Pilih --</option>';
    pelangganCache.forEach(p => {
        select.innerHTML += `<option value="${p.id}">${p.nama}</option>`;
    });
    if (currentVal) select.value = currentVal;
}

function onPelangganChange() {
    const id = document.getElementById('pelanggan-select').value;
    const infoDiv = document.getElementById('pelanggan-info');
    if (!id) { infoDiv.classList.add('d-none'); updateBayarButtonState(); return; }

    const p = pelangganCache.find(x => x.id == id);
    if (p) {
        document.getElementById('pelanggan-info-nama').textContent = p.nama;
        document.getElementById('pelanggan-info-hp').textContent = p.no_hp || '';
        document.getElementById('pelanggan-info-utang').textContent = p.total_utang_aktif > 0
            ? 'Utang: Rp' + parseFloat(p.total_utang_aktif).toLocaleString('id-ID')
            : '';
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
        document.getElementById('tambah-pelanggan-error').textContent = 'Nama wajib diisi.';
        document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
        return;
    }
    if (!navigator.onLine) {
        document.getElementById('tambah-pelanggan-error').textContent = 'Harus online.';
        document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
        return;
    }

    const btn = document.querySelector('#tambahPelangganModal button:last-child');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    try {
        const resp = await fetch('/warung/pos/pelanggan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
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
            localStorage.setItem('pos_pelanggan_cache', JSON.stringify(pelangganCache));
            populatePelangganSelect();
            document.getElementById('pelanggan-select').value = data.pelanggan.id;
            onPelangganChange();
            bootstrap.Modal.getInstance(document.getElementById('tambahPelangganModal'))?.hide();
        } else {
            document.getElementById('tambah-pelanggan-error').textContent = data.message || 'Gagal.';
            document.getElementById('tambah-pelanggan-error').classList.remove('d-none');
        }
    } catch (e) {
        document.getElementById('tambah-pelanggan-error').textContent = 'Gagal terhubung.';
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

    let payload = { items, total, metode: metodeAktif, pelanggan_id: null, jatuh_tempo: null, catatan_tempo: null };

    if (metodeAktif === 'tempo') {
        payload.pelanggan_id = parseInt(document.getElementById('pelanggan-select').value);
        payload.jatuh_tempo = document.getElementById('jatuh-tempo-input').value;
        payload.catatan_tempo = document.getElementById('catatan-tempo-input').value.trim() || null;
    }

    if (metodeAktif === 'cash') {
        const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
        if (cash < total) { alert('Uang kurang!'); return; }
    }

    const btn = document.getElementById('btn-bayar');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    try {
        const resp = await fetch('/warung/pos/transaksi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();

        if (data.success) {
            const icon = document.getElementById('konfirmasi-icon');
            const judul = document.getElementById('konfirmasi-judul');
            const totalText = document.getElementById('konfirmasi-total-text');
            const subText = document.getElementById('konfirmasi-sub-text');

            if (metodeAktif === 'tempo') {
                icon.className = 'bi bi-clock-history display-4 mb-2';
                icon.style.color = '#FF9800';
                judul.textContent = 'Tempo Tercatat';
                totalText.textContent = 'Rp' + total.toLocaleString('id-ID') + ' (piutang)';
                const p = pelangganCache.find(x => x.id == payload.pelanggan_id);
                subText.textContent = p ? p.nama : '';
            } else {
                icon.className = 'bi bi-check-circle display-4 mb-2';
                icon.style.color = 'var(--warna-aksen-kedua)';
                judul.textContent = 'Selesai!';
                totalText.textContent = 'Rp' + total.toLocaleString('id-ID');
                const cash = parseInt(document.getElementById('cash-diterima').value) || 0;
                const kembali = cash - total;
                subText.textContent = kembali > 0 ? 'Kembali: Rp' + kembali.toLocaleString('id-ID') : '';
            }

            new bootstrap.Modal(document.getElementById('konfirmasiModal')).show();

            // Update stok
            keranjang.forEach(item => {
                if (produkData[item.id]) produkData[item.id].stok -= item.qty;
            });

            keranjang = [];
            updateKeranjangUI();
            document.getElementById('cash-diterima').value = '';
            document.getElementById('kembalian-area').style.display = 'none';

        } else {
            alert('Gagal: ' + (data.message || 'Error'));
        }
    } catch (err) {
        alert('Gagal memproses. Cek koneksi.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        updateBayarButtonState();
    }
}

function transaksiBaru() {
    bootstrap.Modal.getInstance(document.getElementById('konfirmasiModal'))?.hide();
    keranjang = [];
    updateKeranjangUI();
    document.getElementById('pos-barcode-input').value = '';
    document.getElementById('pos-barcode-input').focus();
    document.getElementById('cash-diterima').value = '';
    document.getElementById('kembalian-area').style.display = 'none';
}

// =============================================
// OFFLINE
// =============================================

function updateOfflineStatus() {
    document.getElementById('pos-offline-banner').classList.toggle('d-none', navigator.onLine);
}
window.addEventListener('online', () => { updateOfflineStatus(); pelangganCache = []; loadPelangganCache(); });
window.addEventListener('offline', updateOfflineStatus);

document.addEventListener('DOMContentLoaded', () => {
    updateOfflineStatus();
    setTimeout(() => document.getElementById('pos-barcode-input').focus(), 300);
    loadPelangganCache();
});
</script>
@endsection