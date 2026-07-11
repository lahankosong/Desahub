@extends('layouts.warung')

@section('content')
<div id="pos-app" class="pos-container">
    {{-- Offline Banner --}}
    <div id="pos-offline-banner" class="alert alert-warning py-2 mb-3 d-none" style="background-color: #FFF3E0; border-color: #FF9800; color: #E65100; font-size: 0.85rem;">
        <i class="bi bi-wifi-off me-1"></i> Sedang Offline — transaksi tersimpan lokal
    </div>

    {{-- Search Bar --}}
    <div class="mb-3">
        <div class="input-group">
            <span class="input-group-text" style="background-color: #fff; border-color: var(--warna-netral-garis);">
                <i class="bi bi-search" style="color: var(--warna-aksen-utama);"></i>
            </span>
            <input type="text" id="pos-search" class="form-control" placeholder="Cari produk..." 
                   style="border-color: var(--warna-netral-garis); background: #fff;"
                   oninput="filterProduk(this.value)">
        </div>
    </div>

    {{-- Grid Produk --}}
    <div class="row g-2 mb-3" id="pos-grid">
        @foreach ($produkList as $p)
            @php
                $stok = $p['stok'] ?? 0;
                $tersedia = $stok > 0;
            @endphp
            <div class="col-6 col-md-4 col-lg-3 produk-item" 
                 data-nama="{{ strtolower($p['nama']) }}"
                 onclick="{{ $tersedia ? 'tambahKeranjang(' . $p['id'] . ', \'' . addslashes($p['nama']) . '\', ' . $p['harga'] . ')' : '' }}">
                <div class="card border-0 shadow-sm h-100 {{ !$tersedia ? 'opacity-50' : '' }}" 
                     style="cursor: {{ $tersedia ? 'pointer' : 'not-allowed' }}; background: #fff;">
                    <div class="card-body p-3">
                        <div class="fw-semibold mb-1" style="font-family: var(--font-judul); font-size: 0.9rem;">
                            {{ $p['nama'] }}
                        </div>
                        <div class="fw-bold mb-2" style="font-family: var(--font-judul); color: var(--warna-aksen-utama); font-size: 1rem;">
                            Rp{{ number_format($p['harga'], 0, ',', '.') }}
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" style="font-size: 0.75rem;">
                                Stok: {{ $stok }} {{ $p['satuan'] ?? 'pcs' }}
                            </small>
                            @if ($tersedia)
                                <span class="badge rounded-pill px-2 py-1" style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 0.65rem;">
                                    Tersedia
                                </span>
                            @else
                                <span class="badge rounded-pill px-2 py-1" style="background-color: #9E9E9E; color: #fff; font-size: 0.65rem;">
                                    Habis
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Keranjang Floating Button --}}
    <div class="position-fixed bottom-0 start-0 end-0 p-3" style="background: linear-gradient(to top, #fff 0%, rgba(255,255,255,0.9) 80%, transparent 100%); z-index: 999;">
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

    {{-- Modal Keranjang --}}
    <div class="modal fade" id="keranjangModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" style="font-family: var(--font-judul);">Keranjang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="keranjang-items" class="mb-3">
                        <p class="text-center text-muted py-3" style="font-size: 0.85rem;">Keranjang kosong</p>
                    </div>
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold">Total:</span>
                            <span class="fw-bold fs-5" style="font-family: var(--font-judul); color: var(--warna-aksen-utama);" id="modal-total">
                                Rp0
                            </span>
                        </div>
                        <button class="btn w-100 fw-semibold rounded-pill py-3"
                                style="background-color: var(--warna-aksen-kedua); color: #fff; font-size: 1rem;"
                                onclick="bayarTunai()">
                            <i class="bi bi-cash me-2"></i> BAYAR TUNAI
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi --}}
    <div class="modal fade" id="konfirmasiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body py-4">
                    <i class="bi bi-check-circle display-1 mb-3" style="color: var(--warna-aksen-kedua);"></i>
                    <h5 class="fw-bold mb-2" style="font-family: var(--font-judul);">Transaksi Selesai</h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">Rp<span id="konfirmasi-total">0</span> diterima</p>
                    <button class="btn fw-semibold rounded-pill px-4"
                            style="background-color: var(--warna-aksen-utama); color: #fff;"
                            onclick="transaksiBaru()">
                        Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Sync Indicator --}}
    <div id="sync-indicator" class="position-fixed top-0 start-0 end-0 p-2 text-center d-none" 
         style="background-color: #FFF3E0; color: #E65100; font-size: 0.8rem; z-index: 9999;">
        <i class="bi bi-arrow-repeat spin me-1"></i> Menunggu sinkron...
    </div>
</div>

<style>
.pos-container { padding-bottom: 100px; }
.produk-item { transition: transform 0.2s; }
.produk-item:active { transform: scale(0.95); }
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 1.5s linear infinite;
}
</style>

<script>
// ===== STATE =====
let keranjang = [];
let produkStok = {};

@foreach ($produkList as $p)
    produkStok[{{ $p['id'] }}] = {{ $p['stok'] ?? 0 }};
@endforeach

// ===== FUNGSI KERANJANG =====
function tambahKeranjang(id, nama, harga) {
    const stokTersedia = produkStok[id] || 0;
    const existing = keranjang.find(item => item.id === id);
    
    if (existing) {
        if (existing.qty >= stokTersedia) {
            alert('Stok tidak cukup!');
            return;
        }
        existing.qty++;
    } else {
        if (stokTersedia <= 0) {
            alert('Stok habis!');
            return;
        }
        keranjang.push({ id, nama, harga, qty: 1 });
    }
    
    updateKeranjangUI();
}

function updateQty(id, delta) {
    const item = keranjang.find(item => item.id === id);
    if (!item) return;
    
    item.qty += delta;
    if (item.qty <= 0) {
        keranjang = keranjang.filter(i => i.id !== id);
    }
    
    updateKeranjangUI();
    renderKeranjangItems();
}

function updateKeranjangUI() {
    const count = keranjang.reduce((sum, item) => sum + item.qty, 0);
    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    document.getElementById('keranjang-count').textContent = count;
    document.getElementById('keranjang-total').textContent = 'Rp' + total.toLocaleString('id-ID');
    document.getElementById('modal-total').textContent = total.toLocaleString('id-ID');
}

function renderKeranjangItems() {
    const container = document.getElementById('keranjang-items');
    
    if (keranjang.length === 0) {
        container.innerHTML = '<p class="text-center text-muted py-3" style="font-size: 0.85rem;">Keranjang kosong</p>';
        return;
    }
    
    container.innerHTML = keranjang.map(item => `
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div class="flex-grow-1">
                <div class="fw-semibold" style="font-size: 0.9rem;">${item.nama}</div>
                <small class="text-muted" style="font-size: 0.75rem;">Rp${item.harga.toLocaleString('id-ID')} / pcs</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm rounded-circle" style="width: 28px; height: 28px; padding: 0; background-color: var(--warna-netral-garis); border: none;"
                        onclick="updateQty(${item.id}, -1)">−</button>
                <span class="fw-semibold" style="min-width: 20px; text-align: center;">${item.qty}</span>
                <button class="btn btn-sm rounded-circle" style="width: 28px; height: 28px; padding: 0; background-color: var(--warna-aksen-utama); color: #fff; border: none;"
                        onclick="updateQty(${item.id}, 1)">+</button>
            </div>
        </div>
    `).join('');
}

function bukaKeranjang() {
    if (keranjang.length === 0) {
        alert('Keranjang kosong!');
        return;
    }
    renderKeranjangItems();
    new bootstrap.Modal(document.getElementById('keranjangModal')).show();
}

// ===== PEMBAYARAN =====
function bayarTunai() {
    if (keranjang.length === 0) return;
    
    const total = keranjang.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    const items = keranjang.map(item => ({
        produk_id: item.id,
        qty: item.qty,
        harga_satuan: item.harga
    }));
    
    // Tampilkan loading
    const btn = document.querySelector('#keranjangModal .btn-primary, #keranjangModal .btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';
    }
    
    fetch('/warung/pos/transaksi', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ items, total })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Tutup modal keranjang
            bootstrap.Modal.getInstance(document.getElementById('keranjangModal'))?.hide();
            
            // Tampilkan konfirmasi
            document.getElementById('konfirmasi-total').textContent = total.toLocaleString('id-ID');
            new bootstrap.Modal(document.getElementById('konfirmasiModal')).show();
            
            // Reset keranjang
            keranjang = [];
            updateKeranjangUI();
        } else {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Gagal memproses transaksi');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cash me-2"></i> BAYAR TUNAI';
        }
    });
}

function transaksiBaru() {
    bootstrap.Modal.getInstance(document.getElementById('konfirmasiModal'))?.hide();
}

// ===== SEARCH =====
function filterProduk(keyword) {
    const items = document.querySelectorAll('.produk-item');
    const lower = keyword.toLowerCase();
    
    items.forEach(item => {
        const nama = item.dataset.nama || '';
        item.style.display = nama.includes(lower) ? '' : 'none';
    });
}

// ===== OFFLINE DETECTION =====
function updateOfflineStatus() {
    const banner = document.getElementById('pos-offline-banner');
    if (!navigator.onLine) {
        banner.classList.remove('d-none');
    } else {
        banner.classList.add('d-none');
    }
}

window.addEventListener('online', updateOfflineStatus);
window.addEventListener('offline', updateOfflineStatus);
document.addEventListener('DOMContentLoaded', updateOfflineStatus);
</script>
@endsection