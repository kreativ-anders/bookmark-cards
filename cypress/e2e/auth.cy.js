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
    cy.get('.alert-error').should('contain', 'Invalid email or password');
    cy.get('input[name="email"]').should('have.value', email);

    // cleanup
    cy.login(email, password);
    cy.deleteAccount();
  });

  it('changes the password', () => {
    cy.register(email, password);

    cy.get('#user').click();
    cy.location('pathname').should('eq', '/user');
    cy.get('input[name="password"]').type(newPassword, { log: false });
    cy.get('input[name="update"][value="Change Password"]').click();
    cy.get('.alert-success').should('contain', 'Your password has been changed!');

    cy.logout();

    cy.login(email, password);
    cy.get('#user').should('not.exist');

    cy.login(email, newPassword);
    cy.get('#user').should('be.visible');

    cy.deleteAccount();
  });

  it('changes the email', () => {
    cy.register(email, password);
    const newEmail = email.replace('cy-auth', 'cy-auth-new');

    cy.visit('/user');
    cy.get('input[name="email"]').clear().type(newEmail);
    cy.get('input[name="update"][value="Change Email"]').click();
    cy.get('.alert-success').should('contain', 'Your email has been changed!');
    cy.get('main header').should('contain', newEmail);

    cy.logout();
    cy.login(newEmail, password);
    cy.get('#user').should('be.visible');

    cy.deleteAccount();
  });

  it('protects the settings page and rejects requests without CSRF token', () => {
    cy.visit('/user');
    cy.location('pathname').should('eq', '/login');

    cy.register(email, password);
    cy.request({ method: 'POST', url: '/user', form: true, body: { delete: 'Delete Account' } })
      .its('body').should('contain', 'Invalid CSRF token!');
    cy.visit('/');
    cy.get('#user').should('be.visible'); // still exists

    cy.deleteAccount();
  });
});
