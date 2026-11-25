import './commands';
import './phraseanet';

// Phraseanet ships legacy jQuery plugins that raise harmless errors on some screens.
// A test fails on its own assertions (HTTP status, DOM, data), not on third-party script noise.
Cypress.on('uncaught:exception', () => false);

before(() => {
  cy.env(['ADMIN_PASSWORD'], { log: false }).then(({ ADMIN_PASSWORD }) => {
    if (!ADMIN_PASSWORD) throw new Error('CYPRESS_ADMIN_PASSWORD is not set (Phraseanet admin password).');
  });
});
