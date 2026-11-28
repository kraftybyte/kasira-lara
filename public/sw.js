// Service worker minimal: syarat "Tambahkan ke layar utama" di Android. Tidak menyimpan cache apa pun,
// supaya halaman kasir/dapur selalu data terbaru.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});
