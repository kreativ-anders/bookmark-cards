describe('Bookmarks', () => {
  const password = 'Password123';

  beforeEach(() => {
    cy.uniqueEmail('cy-bookmarks').then((email) => cy.register(email, password));
  });

  afterEach(() => {
    cy.deleteAccount(password);
  });

  it('adds, edits and deletes a bookmark', () => {
    cy.addBookmark('GitHub', 'https://github.com', 'dev, code');

    cy.card('GitHub').within(() => {
      cy.get('a.card-title').should('have.attr', 'href', 'https://github.com');
      cy.get('span.tag').should('have.length', 2);
    });

    // edit
    cy.card('GitHub').find('button.edit').click();
    cy.get('#changeModal').should('be.visible').within(() => {
      cy.get('#title').clear().type('GitHub Inc');
      cy.get('#link').clear().type('https://github.com/kreativ-anders');
      cy.get('#tags').clear().type('git');
      cy.get('button[type="submit"]').click();
    });

    cy.card('GitHub Inc').within(() => {
      cy.get('a.card-title').should('have.attr', 'href', 'https://github.com/kreativ-anders');
      cy.get('span.tag').should('have.length', 1).and('contain', 'git');
    });

    // delete
    cy.card('GitHub Inc').find('button.delete').click();
    cy.get('#bookmarks article').should('have.length', 0);
  });

  it('exports bookmarks as JSON and CSV', () => {
    cy.addBookmark('Foo & Bär', 'https://example.org/?a=1&b=2', 'test');

    cy.request('/user.json').its('body').then((body) => {
      const data = typeof body === 'string' ? JSON.parse(body) : body;
      expect(data.User.Subscription).to.eq('Free');
      expect(data.Bookmarks).to.deep.eq([
        { title: 'Foo & Bär', link: 'https://example.org/?a=1&b=2', tags: 'test' },
      ]);
    });

    cy.request('/user.csv').its('body')
      .should('contain', 'Title;Link;Tags;')
      .and('contain', 'Foo & Bär;https://example.org/?a=1&b=2;test;');
  });

  it('shows the matching brand logo on the card', () => {
    cy.addBookmark('Buy me a coffee', 'https://buymeacoffee.com');
    cy.addBookmark('Xyzzy Tool', 'https://example.org');

    cy.card('Buy me a coffee')
      .should('have.css', 'background-image')
      .and('contain', '/assets/brand-names/buymeacoffee.svg');
    // no logo: main.js paints a random gradient instead
    cy.card('Xyzzy Tool')
      .should('have.css', 'background-image')
      .and('not.contain', '/assets/brand-names/');
  });

  it('rejects bookmark changes without CSRF token', () => {
    cy.addBookmark('GitHub', 'https://github.com');

    cy.request({ method: 'POST', url: '/', form: true, body: { c_title: 'Evil', c_link: 'https://evil.example' } })
      .its('body').should('contain', 'Invalid CSRF token!');
    cy.request({ method: 'POST', url: '/', form: true, body: { d_bookmark: '0' } })
      .its('body').should('contain', 'Invalid CSRF token!');
    cy.request({ method: 'POST', url: '/', form: true, body: { u_id: '0', u_title: 'Hacked', u_link: 'https://evil.example' } })
      .its('body').should('contain', 'Invalid CSRF token!');

    cy.visit('/');
    cy.get('#bookmarks article').should('have.length', 1);
    cy.card('GitHub').should('exist');
  });

  // POST to the home controller with the page's CSRF token
  const post = (body) => cy.get('#jumbotron input[name="csrf"]').invoke('val').then((csrf) =>
    cy.request({ method: 'POST', url: '/', form: true, body: { csrf, ...body } }));

  it('rejects script links and keeps other links unchanged', () => {
    cy.visit('/');
    post({ c_title: 'Evil', c_link: 'javascript:alert(1)' })
      .its('body').should('contain', 'Please enter a title and a valid web link!');
    post({ c_title: 'Mail', c_link: 'mailto:jane@example.com' });

    cy.visit('/');
    cy.get('#bookmarks article').should('have.length', 1);
    cy.card('Mail').find('a.card-title').should('have.attr', 'href', 'mailto:jane@example.com');
  });

  it('does not add a bookmark twice on reload (POST/REDIRECT/GET)', () => {
    cy.addBookmark('GitHub', 'https://github.com');
    cy.location('pathname').should('eq', '/');
    cy.reload();
    cy.get('#bookmarks article').should('have.length', 1);
  });

  it('ignores updates of bookmarks that do not exist', () => {
    cy.addBookmark('GitHub', 'https://github.com');
    post({ u_id: '5', u_title: 'Ghost', u_link: 'https://ghost.example' })
      .its('body').should('contain', 'This bookmark has changed in the meantime.');

    cy.visit('/');
    cy.get('#bookmarks article').should('have.length', 1);
  });

  it('deletes the right bookmark although the index is stale', () => {
    cy.addBookmark('First', 'https://first.example');
    cy.addBookmark('Second', 'https://second.example');

    // a 2nd tab still shows "Second" at index 1, then "First" gets deleted
    cy.card('Second').find('input[name="d_hash"]').invoke('val').then((hash) => {
      cy.card('First').find('button.delete').click();
      post({ d_bookmark: '1', d_hash: hash });
    });

    cy.visit('/');
    cy.get('#bookmarks article').should('have.length', 0);
  });

  it('enforces the free plan limit', () => {
    cy.visit('/');
    cy.get('#jumbotron input[name="csrf"]').invoke('val').then((csrf) => {
      // noPremiumLimit of the local config
      Cypress._.times(24, (i) =>
        cy.request({ method: 'POST', url: '/', form: true, body: { csrf, c_title: `Bookmark ${i}`, c_link: `https://example.org/${i}` } }));
    });

    cy.addBookmark('One too many', 'https://example.org/too-many');
    cy.get('#bookmarks article').should('have.length', 25);
    cy.card('BECOME PREMIUM').should('exist');

    // as before the refactoring: every further add becomes another "become premium" card
    cy.addBookmark('Another one', 'https://example.org/another');
    cy.get('#bookmarks article').should('have.length', 26);
    cy.contains('#bookmarks article .card-title', 'Another one').should('not.exist');
  });

  it('filters bookmarks by tag', () => {
    cy.addBookmark('GitHub', 'https://github.com', 'dev');
    cy.addBookmark('Apple', 'https://apple.com', 'tech');

    cy.card('GitHub').find('span.tag').contains('dev').click();
    cy.card('GitHub').should('be.visible');
    cy.card('Apple').should('not.be.visible');
  });
});
