<x-layouts.warung>
    <div class="text-center py-5">
        <i class="bi bi-shop display-1 text-danger"></i>
        <h2 class="mt-3">Dashboard Warung</h2>
        <p class="text-muted">Selamat datang, {{ auth()->user()->nama }}!</p>
        <p class="text-muted">Fitur kelola produk, lihat order masuk, dan atur ketersediaan akan hadir di sini.</p>
    </div>
</x-layouts.warung>