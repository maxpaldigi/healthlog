const CACHE = 'healthyme-__CACHE_VERSION__';
const SHELL = ['/manifest.json', '/icon-192.png', '/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin || url.pathname === '/sw.js') {
        return;
    }

    if (url.pathname.startsWith('/build/')) {
        event.respondWith(cacheFirst(request));

        return;
    }

    event.respondWith(networkFirst(request, isPageRequest(request)));
});

self.addEventListener('sync', (event) => {
    if (event.tag === 'healthyme-sync-readings') {
        event.waitUntil(
            self.clients
                .matchAll({ type: 'window', includeUncontrolled: true })
                .then((clients) => {
                    clients.forEach((client) =>
                        client.postMessage({ type: 'sync-readings' }),
                    );
                }),
        );
    }
});

self.addEventListener('push', (event) => {
    let payload = { title: 'HealthyMe', body: 'Time to add a reading.', url: '/' };

    try {
        if (event.data) {
            payload = { ...payload, ...event.data.json() };
        }
    } catch (error) {
        // Keep the default payload.
    }

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: '/icon-192.png',
            badge: '/icon-192.png',
            data: { url: payload.url || '/' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                for (const client of clients) {
                    if ('focus' in client) {
                        client.navigate(url);

                        return client.focus();
                    }
                }

                return self.clients.openWindow(url);
            }),
    );
});

function isPageRequest(request) {
    return (
        request.mode === 'navigate' ||
        request.destination === 'document' ||
        request.headers.get('X-Inertia') === 'true'
    );
}

async function cacheFirst(request) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(CACHE);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        return new Response('HealthyMe needs a connection to open.', {
            status: 503,
            statusText: 'Offline',
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}

async function networkFirst(request, page) {
    try {
        const response = await fetch(request);

        if (response.ok && !page) {
            const cache = await caches.open(CACHE);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cached = await caches.match(request);

        if (cached) {
            return cached;
        }

        return new Response('HealthyMe needs a connection to open.', {
            status: 503,
            statusText: 'Offline',
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}
