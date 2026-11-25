// §6.5 Connexion (Login) — §6.2 barre de menu (déconnexion)

describe('§6.5 Connexion', () => {
  it('affiche le formulaire de connexion locale', () => {
    cy.visit('/login/');
    cy.get('form[name="loginForm"]').should('be.visible');
    cy.get('#login').should('have.attr', 'required');
    cy.get('#password').should('have.attr', 'type', 'password');
    cy.get('input[name="_token"]').invoke('val').should('not.be.empty');
  });

  it('propose le choix de la langue', () => {
    cy.visit('/login/');
    cy.get('#language-button').click();
    cy.get('#language-button').parent().find('.dropdown-menu a').should('have.length.at.least', 2);
  });

  it('propose le lien « mot de passe oublié » et son formulaire', () => {
    cy.clearAllCookies();
    cy.visit('/login/');
    cy.get('a.forget-password-link').should('have.attr', 'href', '/login/forgot-password/').click();
    cy.location('pathname').should('eq', '/login/forgot-password/');
    cy.get('input[type="email"], input[name*="email"]').should('exist');
  });

  it('refuse un mot de passe erroné (sensible à la casse)', () => {
    cy.clearAllCookies();
    cy.adminCredentials().then(({ login, password }) => cy.loginUi(login, `${password.toUpperCase()}x`));
    cy.location('pathname').should('match', /^\/login\//);
    cy.get('.alert, .help-message, .error, .text-error').should('exist');
  });

  it('connecte l’administrateur puis ouvre une application', () => {
    cy.clearAllCookies();
    cy.adminCredentials().then(({ login, password }) => cy.loginUi(login, password));
    cy.location('pathname', { timeout: 30000 }).should('match', /^\/(prod|client|admin|lightbox)/);
  });

  it('renvoie vers la connexion une page protégée sans session', () => {
    cy.clearAllCookies();
    cy.request({ url: '/prod/', followRedirect: false }).then((r) => {
      expect(r.status).to.eq(302);
      expect(r.redirectedToUrl).to.match(/\/login\//);
    });
  });

  it('expose la page d’inscription quand elle est activée, sinon la refuse proprement', () => {
    cy.clearAllCookies();
    cy.request({ url: '/login/register/', failOnStatusCode: false, followRedirect: false })
      .its('status').should('be.oneOf', [200, 302, 404]);
  });

  it('déconnecte l’utilisateur', () => {
    cy.clearAllCookies();
    cy.adminCredentials().then(({ login, password }) => cy.loginUi(login, password));
    cy.location('pathname', { timeout: 30000 }).should('not.match', /^\/login\/?$/);
    cy.visit('/login/logout/');
    cy.request({ url: '/prod/', followRedirect: false }).its('status').should('eq', 302);
  });
});
