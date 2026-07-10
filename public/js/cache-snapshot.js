/**
 * Lapis 2: Data Terakhir Terlihat (Read-Only Cache)
 * Menyimpan snapshot data terakhir ke localStorage saat online.
 * Saat offline, data ini bisa dibaca untuk menampilkan kondisi terakhir.
 */
(function () {
    'use strict';

    const KEY = 'desahub_snapshot';

    window.saveSnapshot = function (data) {
        try {
            const snapshot = {
                timestamp: Date.now(),
                data: data,
            };
            localStorage.setItem(KEY, JSON.stringify(snapshot));
        } catch (e) {
            // localStorage penuh atau tidak tersedia
        }
    };

    window.getSnapshot = function () {
        try {
            const raw = localStorage.getItem(KEY);
            if (!raw) return null;
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    };

    window.clearSnapshot = function () {
        localStorage.removeItem(KEY);
    };
})();