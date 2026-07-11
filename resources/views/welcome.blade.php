<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Derum — Belanja Deket Rumah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --warna-dasar: #FAF6ED;
            --warna-teks: #2B2622;
            --warna-aksen-utama: #E8A23C;
            --warna-aksen-kedua: #1F5C4F;
            --font-judul: 'Space Grotesk', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            background-color: var(--warna-dasar);
            color: var(--warna-teks);
            font-family: var(--font-body);
        }
        h1, h2, h3 { font-family: var(--font-judul); font-weight: 700; }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex flex-column align-items-center justify-content-center text-center px-3">
        <div style="font-size: 4rem; margin-bottom: 0.5rem;">🏪</div>
        <h1>Derum</h1>
        <p class="lead mb-4" style="color: var(--warna-aksen-kedua); font-weight: 600;">Belanja Deket Rumah</p>
        <p class="text-muted mb-4" style="max-width: 320px;">Temukan warung dan produk di sekitar Anda. Pesan online, ambil sendiri atau diantar kurir.</p>
        <div class="d-flex gap-3 flex-wrap justify-content-center">
            <a href="/konsumen/login" class="btn btn-lg rounded-pill px-5" style="background-color: var(--warna-aksen-utama); color: #fff; border: none; font-weight: 600;">Belanja</a>
            <a href="/warung/login" class="btn btn-lg rounded-pill px-5" style="background-color: var(--warna-aksen-kedua); color: #fff; border: none; font-weight: 600;">Jualan</a>
            <a href="/kurir/login" class="btn btn-lg rounded-pill px-5" style="background-color: #1976D2; color: #fff; border: none; font-weight: 600;">Antar</a>
        </div>
        <p class="mt-4 small text-muted">&copy; {{ date('Y') }} Derum</p>
    </div>
</body>
</html>