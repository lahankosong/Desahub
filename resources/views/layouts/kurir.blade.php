<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest-kurir.json">
    <meta name="theme-color" content="#1F5C4F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Derum Kurir">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Derum — Kurir</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --warna-dasar: #FAF6ED;
            --warna-teks: #2B2622;
            --warna-aksen-utama: #E8A23C;
            --warna-aksen-kedua: #1F5C4F;
            --warna-peringatan: #C4482E;
            --warna-netral-garis: #DCD3C2;
            --font-judul: 'Space Grotesk', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            background-color: var(--warna-dasar);
            color: var(--warna-teks);
            font-family: var(--font-body);
            padding-bottom: 80px;
        }
        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: var(--font-judul);
            font-weight: 700;
        }
        .navbar { font-family: var(--font-body); }
        .card { border-color: var(--warna-netral-garis); background: #fff; }
        .btn-primary, .btn-success, .btn-danger { font-family: var(--font-body); font-weight: 600; }
        .btn-primary {
            background-color: var(--warna-aksen-utama);
            border-color: var(--warna-aksen-utama);
            color: #fff;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #d4922e;
            border-color: #d4922e;
        }
        .bg-success {
            background-color: var(--warna-aksen-kedua) !important;
        }
        .btn-outline-light { border-color: rgba(255,255,255,0.6); color: #fff; }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid var(--warna-netral-garis);
            box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 1000;
        }
        .bottom-nav a {
            text-decoration: none;
            text-align: center;
            color: #999;
            font-size: 0.7rem;
            padding: 8px 0;
            flex: 1;
        }
        .bottom-nav a.active { color: var(--warna-aksen-kedua); }
        .bottom-nav a i { font-size: 1.2rem; display: block; margin-bottom: 2px; }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-loading { pointer-events: none; opacity: 0.7; }
    </style>
    @livewireStyles
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?scope=/kurir', { scope: '/kurir/' });
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function() {
                    var btn = this.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.classList.add('btn-loading');
                        btn.innerHTML = '<span class="loading-spinner me-1"></span> ' + btn.textContent.trim();
                    }
                });
            });
        });
    </script>
</head>
<body>
    <x-offline-banner />

    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="/kurir">🏍️ Derum Kurir</a>
            @auth
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-white small">{{ auth()->user()->nama }}</span>
                <form method="POST" action="/logout" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Keluar</button>
                </form>
            </div>
            @endauth
        </div>
    </nav>

    <main class="container my-4">
        @yield('content')
    </main>

    {{-- Bottom Navigation --}}
    <nav class="bottom-nav d-flex justify-content-around">
        <a href="{{ route('kurir.dashboard') }}" class="{{ request()->routeIs('kurir.dashboard') ? 'active' : '' }}">
            <i class="bi bi-house-door{{ request()->routeIs('kurir.dashboard') ? '-fill' : '' }}"></i>Beranda
        </a>
        <a href="{{ route('kurir.order-tersedia') }}" class="{{ request()->routeIs('kurir.order-tersedia') ? 'active' : '' }}">
            <i class="bi bi-box{{ request()->routeIs('kurir.order-tersedia') ? '-fill' : '' }}"></i>Tersedia
        </a>
        <a href="{{ route('kurir.order-aktif') }}" class="{{ request()->routeIs('kurir.order-aktif') ? 'active' : '' }}">
            <i class="bi bi-bicycle"></i>Aktif
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    <script src="/js/cache-snapshot.js"></script>
    <script src="/js/write-queue.js"></script>
</body>
</html>