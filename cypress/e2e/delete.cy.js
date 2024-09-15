// cypress/integration/register_spec.js

describe('Delete Test', () => {
  it('should open the website, click settings, and delete account', () => {


    // Replace with actual credentials or use environment variables for security
    cy.register('test@example.com', 'Password123');
    cy.login('test@example.com', 'Password123');


    // Open the website
    cy.visit('https://bookmark-cards.test/');

    // Click the user settings button to open the modal
    cy.get('#user').click();

    cy.get('#userModal > article > footer > form > input[type=submit][name=delete]').click()

    cy.on('window:confirm', (t) => {
      //assertions
      expect(t).to.contains('This action cannot be revert! Are you sure?');
      return false;
    })

    // Fill in the email and password in the modal
    //cy.get('#registerModal input[type=email]').type('test@example.com');
    //cy.get('#registerModal input[name="password"]').type('Password123');

    // Check AGB
    //cy.get('#registerModal input[name="tos"]').check();

    // Click the register button in the modal
    //cy.get('#registerModal input[name="register"]').click();


  });
});
