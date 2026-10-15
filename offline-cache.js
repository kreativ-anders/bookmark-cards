// Registers the offline service worker (offline-sw.js, replaces UpUp.js) and tells it what to cache.
// OfflineCache.start({ version, offlinePage, assets }) resolves true once the worker has cached
// everything, false otherwise. window.OfflineCache is null without service worker support.

(function () {
  const sw = navigator.serviceWorker;
  if (!sw) {
    window.OfflineCache = null;
    return;
  }

  // registering a new script URL replaces the old registration (UpUp) of the same scope,
  // so the settings must go to the new worker once it is active, not to the old one
  function activated(worker) {
    return new Promise((resolve, reject) => {
      if (!worker) return reject(new Error('no service worker'));
      const check = () => {
        if (worker.state === 'activated') resolve(worker);
        else if (worker.state === 'redundant') reject(new Error('service worker install failed'));
      };
      worker.addEventListener('statechange', check);
      check();
    });
  }

  function send(worker, message) {
    return new Promise(resolve => {
      const channel = new MessageChannel();
      channel.port1.onmessage = event => resolve(event.data || { ok: false });
      worker.postMessage(message, [channel.port2]);
    });
  }

  window.OfflineCache = {
    start(settings) {
      settings = settings || {};
      return sw.register(settings.serviceWorker || '/offline-sw.min.js', { scope: '/', updateViaCache: 'none' })
        .then(registration => activated(registration.installing || registration.waiting || registration.active))
        .then(worker => send(worker, {
          action: 'cache',
          settings: { version: settings.version, offlinePage: settings.offlinePage, assets: settings.assets || [] }
        }))
        .then(result => {
          if (!result.ok) console.warn('Offline cache failed:', result.error);
          return !!result.ok;
        })
        .catch(err => {
          console.warn('Offline cache failed:', err && err.message);
          return false;
        });
    }
  };
})();
