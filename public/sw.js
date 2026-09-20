const STATIC_CACHE = 'emprendimientoos-static-v1';
const PRECACHE_URLS = [
    '/manifest.webmanifest',
    '/favicon.ico',
    '/icons/app-icon-180.png',
    '/icons/app-icon-192.png',
    '/icons/app-icon-512.png',
    '/icons/maskable-512.png',
];

const isApprovedStaticPath = (pathname) =>
    pathname === '/manifest.webmanifest' ||
    pathname === '/favicon.ico' ||
    pathname.startsWith('/icons/') ||
    pathname.startsWith('/build/');

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) =>
                Promise.all(
                    cacheNames
                        .filter((cacheName) =>
                            cacheName.startsWith('emprendimientoos-static-') &&
                            cacheName !== STATIC_CACHE,
                        )
                        .map((cacheName) => caches.delete(cacheName)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || event.request.mode === 'navigate') return;

    const requestUrl = new URL(event.request.url);
    if (requestUrl.origin !== self.location.origin || !isApprovedStaticPath(requestUrl.pathname)) return;

    event.respondWith(
        caches.open(STATIC_CACHE).then(async (cache) => {
            const cachedResponse = await cache.match(event.request);
            if (cachedResponse) return cachedResponse;

            const networkResponse = await fetch(event.request);
            if (networkResponse.ok) {
                await cache.put(event.request, networkResponse.clone());
            }

            return networkResponse;
        }),
    );
});
