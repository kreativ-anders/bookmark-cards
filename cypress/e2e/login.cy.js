// cypress/integration/register_spec.js

describe('Login Test', () => {
  it('should open the website, click login, fill form and submit', () => {

    cy.login('test@example.com', 'Password123');
      
  });
});
