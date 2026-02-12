const CACHE_NAME = 'arthalekha-v1';

const PRECACHE_ASSETS = [
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Network-first for navigation requests (HTML pages)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(request))
        );
        return;
    }

    // Stale-while-revalidate for assets (JS, CSS, images)
    if (
        request.destination === 'script' ||
        request.destination === 'style' ||
        request.destination === 'image' ||
        request.destination === 'font'
    ) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) =>
                cache.match(request).then((cachedResponse) => {
                    const fetchPromise = fetch(request).then((networkResponse) => {
                        if (networkResponse.ok) {
                            cache.put(request, networkResponse.clone());
                        }
                        return networkResponse;
                    });

                    return cachedResponse || fetchPromise;
                })
            )
        );
        return;
    }
});
