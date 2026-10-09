// Custom commands for bookmark-cards
// https://on.cypress.io/custom-commands

// Unique e-mail per test run, so tests never collide with existing accounts
Cypress.Commands.add('uniqueEmail', (prefix = 'cypress') => {
  return cy.wrap(`${prefix}-${Date.now()}-${Cypress._.random(1e6)}@example.com`);
});

// Block analytics requests (Pirsch) during tests
Cypress.Commands.add('blockAnalytics', () => {
  cy.intercept({ url: 'https://api.pirsch.io/**' }, { statusCode: 204, body: '' });
});

Cypress.Commands.add('register', (email, password) => {
  cy.blockAnalytics();
  cy.visit('/');
  cy.get('header #register').click();
  cy.get('#registerModal').should('be.visible').within(() => {
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password, { log: false });
    cy.get('input[name="tos"]').check();
    cy.get('input[name="register"]').click();
  });
  cy.get('#user').should('be.visible');
});

Cypress.Commands.add('login', (email, password) => {
  cy.blockAnalytics();
  cy.visit('/');
  cy.get('#login').click();
  cy.get('#loginModal').should('be.visible').within(() => {
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password, { log: false });
    cy.get('input[name="login"]').click();
  });
});

Cypress.Commands.add('logout', () => {
  cy.get('#logout').click();
  cy.get('#login').should('be.visible');
});

// Deletes the logged-in account (also deletes the Stripe test customer via hook)
Cypress.Commands.add('deleteAccount', () => {
  cy.on('window:confirm', () => true);
  cy.visit('/');
  cy.get('#user').click();
  cy.get('#userModal input[name="delete"]').click();
  cy.get('#login').should('be.visible');
});

Cypress.Commands.add('addBookmark', (title, link, tags = '') => {
  cy.get('#s_title').type(title);
  cy.get('#s_link').type(link);
  if (tags) cy.get('#s_tags').type(tags);
  cy.get('#jumbotron button[type="submit"]').click();
});

Cypress.Commands.add('card', (title) => {
  return cy.contains('#bookmarks article .card-title', title).parents('article');
});
