{{-- OfflineBanner — pita di atas layar saat tidak ada koneksi (Lapis 1) --}}
<div id="offline-banner" class="d-none text-center py-2"
     style="background-color: var(--warna-peringatan); color: #fff; font-size: 0.85rem;">
    <i class="bi bi-wifi-off me-1"></i> Anda sedang offline — data yang ditampilkan adalah snapshot terakhir
</div>

<script>
    function updateOfflineBanner() {
        const banner = document.getElementById('offline-banner');
        if (!navigator.onLine) {
            banner?.classList.remove('d-none');
        } else {
            banner?.classList.add('d-none');
        }
    }
    window.addEventListener('online', updateOfflineBanner);
    window.addEventListener('offline', updateOfflineBanner);
    updateOfflineBanner();
</script>