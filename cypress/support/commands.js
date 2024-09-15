// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
Cypress.Commands.add('login', (username, password) => {
  cy.visit('https://bookmark-cards.test/'); // Adjust the URL to your login page

  // Intercept and block specific background requests
  cy.intercept({
    method: 'GET',
    url: 'https://api.pirsch.io/hit?**', // Modify this to the specific request you want to block
  }, {
    statusCode: 400,
    body: {}, // Provide an appropriate response body if necessary
  }).as('blockedRequest');

  // Open the website
  cy.visit('https://bookmark-cards.test/');

  // Click the login button to open the modal
  cy.get('#login').click();

  // Fill in the email and password in the modal
  cy.get('#loginModal input[type=email]').type(username);
  cy.get('#loginModal input[name="password"]').type(password);

  // Click the login button in the modal
  cy.get('#loginModal input[name="login"]').click();

  // Click the user setting button to open the modal
  cy.get('#user').should('be.visible');
});

Cypress.Commands.add('register', (username, password) => {
  cy.visit('https://bookmark-cards.test/'); // Adjust the URL to your login page

  // Intercept and block specific background requests
  cy.intercept({
    method: 'GET',
    url: 'https://api.pirsch.io/hit?**', // Modify this to the specific request you want to block
  }, {
    statusCode: 400,
    body: {}, // Provide an appropriate response body if necessary
  }).as('blockedRequest');

  // Open the website
  cy.visit('https://bookmark-cards.test/');

  // Click the register button to open the modal
  cy.get('#register').click();

  // Fill in the email and password in the modal
  cy.get('#registerModal input[type=email]').type('test@example.com');
  cy.get('#registerModal input[name="password"]').type('Password123');

  // Check AGB
  cy.get('#registerModal input[name="tos"]').check();

  // Click the register button in the modal
  cy.get('#registerModal input[name="register"]').click();

  cy.get('#logout').click();
});

//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })