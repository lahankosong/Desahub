/**
 * Lapis 3: Antrian Aksi Kritis (Write Queue)
 * Antrian aksi penting saat offline — Kurir update status antar,
 * Warung toggle ketersediaan. Disinkron otomatis saat online kembali.
 */
(function () {
    'use strict';

    const QUEUE_KEY = 'desahub_write_queue';
    const API_BASE = '/api/v1'; // endpoint backend yang sudah ada

    let queue = [];
    let syncing = false;

    // Load queue dari localStorage
    try {
        const raw = localStorage.getItem(QUEUE_KEY);
        if (raw) queue = JSON.parse(raw);
    } catch (e) {
        queue = [];
    }

    function persist() {
        localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    }

    function isOnline() {
        return navigator.onLine !== false;
    }

    /**
     * Tambah aksi ke antrian.
     * @param {string} method  HTTP method (POST, PUT)
     * @param {string} url     Endpoint relatif (tanpa API_BASE)
     * @param {object} data    Payload
     */
    window.enqueueAction = function (method, url, data) {
        const item = {
            id: Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
            method: method,
            url: url,
            data: data,
            created: Date.now(),
            retries: 0,
        };
        queue.push(item);
        persist();
        if (isOnline()) flushQueue();
    };

    /**
     * Kirim semua item antrian ke server, hapus yang sukses.
     */
    async function flushQueue() {
        if (syncing || queue.length === 0 || !isOnline()) return;
        syncing = true;

        const failed = [];
        for (const item of queue) {
            try {
                const res = await fetch(API_BASE + item.url, {
                    method: item.method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(item.data),
                });

                if (!res.ok) {
                    // Konflik atau error server — coba lagi nanti
                    item.retries++;
                    if (item.retries < 5) failed.push(item);
                }
                // sukses → tidak dimasukkan kembali
            } catch (e) {
                item.retries++;
                if (item.retries < 5) failed.push(item);
            }
        }

        queue = failed;
        persist();
        syncing = false;
    }

    // Sync saat kembali online
    window.addEventListener('online', flushQueue);

    // Sync saat halaman dimuat (kalau online)
    if (isOnline()) flushQueue();

    // Expose buat debug
    window.getQueueStatus = function () {
        return {
            pending: queue.length,
            online: isOnline(),
            syncing: syncing,
        };
    };
})();