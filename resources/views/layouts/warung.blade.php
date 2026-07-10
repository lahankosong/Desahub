<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest-warung.json">
    <meta name="theme-color" content="#dc3545">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Warung">
    <title>Desahub — Warung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @livewireStyles
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?scope=/warung', { scope: '/warung/' });
        }
    </script>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="/warung">🏪 Desahub Warung</a>
            @auth
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-white small">{{ auth()->user()->nama }}</span>
                <a href="/logout" class="btn btn-outline-light btn-sm">Keluar</a>
            </div>
            @endauth
        </div>
    </nav>

    <main class="container my-4">
        {{ $slot }}
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    <script src="/js/cache-snapshot.js"></script>
    <script src="/js/write-queue.js"></script>
</body>
</html>