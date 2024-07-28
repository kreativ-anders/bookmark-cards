// cypress/integration/register_spec.js

describe('Register Test', () => {
  it('should open the website, click register, fill form and submit', () => {

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


  });
});
