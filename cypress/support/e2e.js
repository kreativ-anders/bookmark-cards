// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')
// The UpUp service worker (offline mode, loaded for logged-in users) intercepts
// navigations and hangs Cypress' proxy. Stub it out and drop any registered worker.
beforeEach(() => {
  cy.intercept('GET', '/upup.min.js', { body: 'window.UpUp = { start: function () {} };', headers: { 'content-type': 'application/javascript' } });
  cy.intercept('GET', '/upup.sw.min.js', { statusCode: 404, body: '' });
});

Cypress.on('window:before:load', (win) => {
  if (win.navigator.serviceWorker) {
    win.navigator.serviceWorker.getRegistrations().then((registrations) => registrations.forEach((r) => r.unregister()));
  }
});
