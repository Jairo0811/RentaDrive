const CACHE_NAME = 'rentadrive-v2';
const STATIC_ASSETS = [
    '/images/rentadrive-mark.png',
    '/images/rentadrive-logo-dark.png',
    '/manifest.webmanifest',
];
const STATIC_PREFIXES = [
    '/build/assets/',
    '/images/',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)),
        )),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || ! event.request.url.startsWith(self.location.origin)) {
        return;
    }

    const url = new URL(event.request.url);
    const isStaticAsset = STATIC_ASSETS.includes(url.pathname)
        || STATIC_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));

    // Nunca almacenar HTML, dashboard, datos operativos ni URLs privadas con tokens.
    if (! isStaticAsset || event.request.mode === 'navigate') {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(event.request).then((response) => {
                if (! response.ok || response.type !== 'basic') {
                    return response;
                }

                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));

                return response;
            });
        }),
    );
});
