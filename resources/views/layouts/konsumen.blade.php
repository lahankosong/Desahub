<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest-konsumen.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Konsumen">
    <title>Desahub — Konsumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @livewireStyles
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js?scope=/konsumen', { scope: '/konsumen/' });
        }
    </script>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/konsumen">🛒 Desahub Belanja</a>
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