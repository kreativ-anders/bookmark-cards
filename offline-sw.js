// Offline service worker for logged-in users (registered by offline-cache.js, replaces UpUp.js).
// Network first for same-origin GET requests; when the network fails, answer from the cache and
// fall back to the offline page for navigations. What to cache comes from the page via postMessage.

const CACHE_PREFIX = 'bookmark-cards-offline-';
const LEGACY_PREFIX = 'upup-cache';
// cache key of the offline page, stored apart from its real URL
const OFFLINE_KEY = '/__offline-page';
// never intercepted: Kirby panel/API and the logout route
const BYPASS = /^\/(panel|api|logout)(\/|$)/;

self.addEventListener('install', () => self.skipWaiting());

// a new worker takes over open tabs right away and drops the caches of UpUp.js (they hold user.json)
self.addEventListener('activate', event => {
  event.waitUntil(Promise.all([deleteCaches(name => name.startsWith(LEGACY_PREFIX)), self.clients.claim()]));
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin || BYPASS.test(url.pathname) || request.headers.has('range')) return;

  event.respondWith(fetch(request).catch(err => fromCache(request).then(response => {
    if (response) return response;
    throw err;
  })));
});

function fromCache(request) {
  const accept = request.headers.get('accept') || '';
  if (request.mode === 'navigate' || accept.includes('text/html')) {
    return caches.match(request, { ignoreSearch: true }).then(response => response || caches.match(OFFLINE_KEY));
  }
  // pages load assets with ?v=<mtime>, the cache holds them without
  return caches.match(request, { ignoreSearch: true });
}

// { action: 'cache', settings: { version, offlinePage, assets } }, answered on the transferred port with { ok }
self.addEventListener('message', event => {
  const data = event.data;
  if (!data || data.action !== 'cache' || event.origin !== self.location.origin) return;
  const port = event.ports && event.ports[0];
  event.waitUntil(cacheAll(data.settings).then(
    () => port && port.postMessage({ ok: true }),
    err => port && port.postMessage({ ok: false, error: String(err && err.message || err) })
  ));
});

function cacheAll(settings) {
  const version = settings && String(settings.version || '');
  if (!/^[\w-]{1,64}$/.test(version)) return Promise.reject(new Error('invalid version'));
  const page = sameOrigin(settings.offlinePage);
  if (!page) return Promise.reject(new Error('invalid offline page'));
  const assets = (Array.isArray(settings.assets) ? settings.assets : []).map(sameOrigin).filter(Boolean);
  const name = CACHE_PREFIX + version;

  return caches.open(name).then(cache => {
    // a missing asset (e.g. a deleted logo) must not cost the rest of the offline cache
    const stored = Promise.all(assets.map(url => load(url).then(response => cache.put(url, response)).catch(() => {})));
    // without the offline page the new cache is useless: keep the previous one
    return Promise.all([stored, load(page).then(response => cache.put(OFFLINE_KEY, response))])
      .catch(err => caches.delete(name).then(() => { throw err; }));
  }).then(() => deleteCaches(other => other !== name && (other.startsWith(CACHE_PREFIX) || other.startsWith(LEGACY_PREFIX))));
}

// only real 200 answers are cached, not errors or a redirect to the login page
function load(url) {
  return fetch(url, { credentials: 'same-origin', cache: 'no-cache' }).then(response => {
    if (!response.ok || response.redirected) throw new Error(url + ': ' + response.status);
    return response;
  });
}

function sameOrigin(value) {
  if (typeof value !== 'string' || !value) return null;
  try {
    const url = new URL(value, self.location.origin + '/');
    return url.origin === self.location.origin ? url.href : null;
  } catch (e) {
    return null;
  }
}

function deleteCaches(match) {
  return caches.keys().then(names => Promise.all(names.filter(match).map(name => caches.delete(name))));
}
