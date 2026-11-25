const { defineConfig } = require('cypress');

module.exports = defineConfig({
  // Top level (not in `e2e`): a value in `e2e` would win over `--config video=true`.
  video: false,
  e2e: {
    baseUrl: process.env.CYPRESS_BASE_URL || 'http://localhost:8082',
    specPattern: 'cypress/e2e/**/*.cy.js',
    supportFile: 'cypress/support/e2e.js',
    viewportWidth: 1600,
    viewportHeight: 950,
    defaultCommandTimeout: 15000,
    requestTimeout: 20000,
    responseTimeout: 60000,
    pageLoadTimeout: 90000,
    // Phraseanet pages load many legacy scripts: Cypress must not fail on errors it does not own.
    chromeWebSecurity: false,
    screenshotOnRunFailure: true,
    retries: { runMode: Number(process.env.CYPRESS_RETRIES || 1), openMode: 0 },
    // Specs run in alphabetical order (00-login … 99-nettoyage): each one creates and
    // deletes its own data; 99-nettoyage removes what an interrupted run left behind.
  },
  // Secrets: read in the specs with cy.env() (Cypress 16). CYPRESS_ADMIN_PASSWORD overrides.
  env: {
    ADMIN_PASSWORD: '',
  },
  // Non-sensitive values, readable synchronously with Cypress.expose().
  expose: {
    ADMIN_LOGIN: process.env.CYPRESS_ADMIN_LOGIN || 'admin@phraseanet.local',
    ALLOW_RECORD_WRITES: process.env.CYPRESS_ALLOW_RECORD_WRITES || '0',
  },
});
