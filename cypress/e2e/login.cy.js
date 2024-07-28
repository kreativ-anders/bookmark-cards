// cypress/integration/register_spec.js

describe('Login Test', () => {
  it('should open the website, click login, fill form and submit', () => {

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
    cy.get('#loginModal input[type=email]').type('test@example.com');
    cy.get('#loginModal input[name="password"]').type('Password123');

    // Click the login button in the modal
    cy.get('#loginModal input[name="login"]').click();

    // Click the user setting button to open the modal
    cy.get('#user').should('be.visible').click();
      
  });
});
