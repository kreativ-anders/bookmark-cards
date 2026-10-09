describe('Authentication', () => {
  const password = 'Password123';
  const newPassword = 'NewPass456';
  let email;

  beforeEach(() => {
    cy.uniqueEmail('cy-auth').then((e) => { email = e; });
  });

  it('registers, logs out, logs in and deletes the account', () => {
    cy.register(email, password);

    cy.logout();

    cy.login(email, password);
    cy.get('#user').should('be.visible');

    cy.deleteAccount();

    // deleted account can no longer log in
    cy.login(email, password);
    cy.get('#user').should('not.exist');
  });

  it('logs in with special characters in the password', () => {
    const special = `Pa&ss<wo>rd"'123`;

    cy.register(email, special);
    cy.logout();

    cy.login(email, special);
    cy.get('#user').should('be.visible');

    cy.deleteAccount();
  });

  it('rejects a wrong password', () => {
    cy.register(email, password);
    cy.logout();

    cy.login(email, 'WrongPassword1');
    cy.get('#user').should('not.exist');

    // cleanup
    cy.login(email, password);
    cy.deleteAccount();
  });

  it('changes the password', () => {
    cy.register(email, password);

    cy.get('#user').click();
    cy.get('#userModal').within(() => {
      cy.get('input[name="password"]').type(newPassword, { log: false });
      cy.get('input[name="update"][value="Change Password"]').click();
    });

    cy.logout();

    cy.login(email, password);
    cy.get('#user').should('not.exist');

    cy.login(email, newPassword);
    cy.get('#user').should('be.visible');

    cy.deleteAccount();
  });
});
