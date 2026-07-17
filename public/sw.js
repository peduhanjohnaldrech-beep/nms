const CACHE = 'nms-v1';
const STATIC = [
  '/css/bootstrap.min.css',
  '/css/bootstrap-icons.css',
  '/css/app.css',
  '/js/bootstrap.bundle.min.js',
  '/js/app.js',
  '/img/logo.jpg',
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(STATIC)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Cache-first for static assets
  if (STATIC.some(p => url.pathname === p)) {
    e.respondWith(
      caches.match(e.request).then(r => r || fetch(e.request))
    );
    return;
  }

  // Network-first for everything else (PHP pages, API)
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});
