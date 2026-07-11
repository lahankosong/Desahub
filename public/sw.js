// Service Worker — Desahub PWA (App Shell Caching)
// Scope ditentukan per-role via registration di layout masing-masing

const CACHE_NAME = 'desahub-pwa-v2';
const ASSETS_TO_CACHE = [
    '/',
    // File spesifik ditambahkan setelah `npm run build` nanti
    // Standard JS/CSS offline tools
    '/js/cache-snapshot.js',
    '/js/write-queue.js',
];

// Install: cache app shell statis (gagal satu = gagal semua, jadi pastikan semua file ada)
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE).catch((err) => {
                console.warn('[SW] cache.addAll gagal (mungkin file belum ada):', err.message);
                // Jangan block instalasi — biarkan SW tetap aktif meski ada asset yg gagal di-cache
                return Promise.resolve();
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate: bersihkan cache lama
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: cache-first untuk aset statis, network-first untuk HTML dinamis
self.addEventListener('fetch', (event) => {
    // Skip non-GET
    if (event.request.method !== 'GET') return;

    // HTML: network-first (biar Livewire tetap dapat data terbaru)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const cloned = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, cloned));
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request).then((cached) => {
                        return cached || new Response(
                            '<html><body style="font-family:sans-serif;text-align:center;padding-top:80px;"><h2>📡 Tidak ada koneksi</h2><p>Silakan coba lagi saat online.</p></body></html>',
                            { headers: { 'Content-Type': 'text/html' } }
                        );
                    });
                })
        );
        return;
    }

    // Static assets: cache-first
    event.respondWith(
        caches.match(event.request).then((cached) => {
            return cached || fetch(event.request).then((response) => {
                const cloned = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, cloned));
                return response;
            });
        })
    );
});