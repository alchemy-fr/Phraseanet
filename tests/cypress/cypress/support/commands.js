// Shared commands for the Phraseanet end-to-end suite.

// Phraseanet answers JSON only when JSON is the sole accepted type (getRequestFormat()).
const XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

/** Unique, recognisable name for the objects a test creates (and later deletes). */
export const uid = (prefix = 'cy') => `${prefix}-${Date.now().toString(36)}${Math.floor(Math.random() * 1e4)}`;

/** True when the run may modify existing records (edit, status, rotation, move…). */
export const recordWritesAllowed = () => String(Cypress.expose('ALLOW_RECORD_WRITES')) === '1';

/** Yields { login, password } of the admin account (password read with cy.env, never logged). */
Cypress.Commands.add('adminCredentials', () => cy.env(['ADMIN_PASSWORD'], { log: false })
  .then(({ ADMIN_PASSWORD }) => ({ login: Cypress.expose('ADMIN_LOGIN'), password: ADMIN_PASSWORD })));

// ---------------------------------------------------------------- authentication

/** Fills and submits the local login form. */
Cypress.Commands.add('loginUi', (login, password) => {
  cy.visit('/login/');
  cy.get('#login').clear().type(login);
  cy.get('#password').clear().type(password, { log: false });
  cy.get('form[name="loginForm"] button[type="submit"]').click();
});

/** Logs in as the admin account once and reuses the session in every spec. */
Cypress.Commands.add('login', () => {
  cy.session(['admin', Cypress.expose('ADMIN_LOGIN')], () => {
    cy.adminCredentials().then(({ login, password }) => cy.loginUi(login, password));
    cy.location('pathname', { timeout: 30000 }).should('not.match', /^\/login\/?$/);
  }, {
    cacheAcrossSpecs: true,
    validate() {
      cy.request({ url: '/prod/', followRedirect: false }).its('status').should('eq', 200);
    },
  });
});

// ---------------------------------------------------------------- HTTP helpers

/** AJAX-style request (Phraseanet answers JSON / fragments only to XHR). */
Cypress.Commands.add('xhr', (method, url, body, options = {}) => cy.request({
  method,
  url,
  body,
  form: method !== 'GET' && body !== undefined && !options.json,
  headers: { ...XHR, ...(options.headers || {}) },
  failOnStatusCode: options.failOnStatusCode !== undefined ? options.failOnStatusCode : true,
  followRedirect: options.followRedirect !== undefined ? options.followRedirect : true,
}));

/** Asserts that a page answers without a server error and returns the response. */
Cypress.Commands.add('getOk', (url, options = {}) => cy.request({
  url,
  headers: options.xhr ? XHR : {},
  failOnStatusCode: false,
  followRedirect: options.followRedirect !== undefined ? options.followRedirect : true,
}).then((resp) => {
  expect(resp.status, `GET ${url}`).to.be.within(200, 399);
  return resp;
}));

/**
 * Phraseanet protects its forms with a per-session token (`<form>_token`) that it
 * renders in the page holding the form. Reads it from that page.
 */
Cypress.Commands.add('formToken', (pageUrl, formName, options = {}) => cy.request({
  url: pageUrl,
  method: options.method || 'GET',
  body: options.body,
  form: !!options.body,
  headers: options.xhr ? XHR : {},
}).then((resp) => {
  const html = typeof resp.body === 'string' ? resp.body : JSON.stringify(resp.body);
  const re = new RegExp(`name=\\\\?"${formName}_token\\\\?"\\s+value=\\\\?"([^"\\\\]+)`);
  const m = html.match(re);
  expect(m, `${formName}_token in ${pageUrl}`).to.not.equal(null);
  return m[1];
}));

// ---------------------------------------------------------------- search

/** Runs a search through the Prod query endpoint and yields its JSON answer. */
Cypress.Commands.add('search', (qry = '', params = {}) => cy.formToken('/prod/', 'searchForm').then((token) => cy.xhr('POST', '/prod/query/', {
  qry,
  search_type: 0,
  pag: 1,
  ...params,
  searchForm_token: token,
}).its('body')));

/**
 * Re-runs a search until `predicate(body)` is true: indexing in Elasticsearch is
 * asynchronous, so a record created or deleted a moment ago shows up a bit later.
 */
Cypress.Commands.add('searchUntil', (qry, params, predicate, { tries = 20, delay = 1500 } = {}) => {
  const attempt = (left) => cy.search(qry, params).then((body) => {
    if (predicate(body)) return body;
    if (left <= 1) throw new Error(`search "${qry}" ${JSON.stringify(params)}: condition not met after ${tries} tries`);
    cy.wait(delay, { log: false });
    return attempt(left - 1);
  });
  return attempt(tries);
});

/** Yields the "sbas_record" ids (e.g. "1_15") of the first records of a search. */
Cypress.Commands.add('recordIds', (count = 3, qry = '', params = {}) => cy.search(qry, params).then((body) => {
  const ids = [...new Set((body.results.match(/id="IMGT_(\d+_\d+)"/g) || []).map((s) => s.slice(9, -1)))];
  expect(ids.length, 'records in the instance').to.be.at.least(1);
  return ids.slice(0, count);
}));

// ---------------------------------------------------------------- Prod UI

/** Opens Prod and waits for the first result page. */
Cypress.Commands.add('visitProd', () => {
  cy.intercept('POST', '/prod/query/').as('query');
  cy.visit('/prod/');
  cy.get('#EDIT_query', { timeout: 60000 }).should('exist');
  // The start page runs a first search (or shows the publications): once its answer is
  // rendered, the Prod application has bound its handlers and the UI is usable.
  cy.get('#answers', { timeout: 60000 }).children().should('have.length.at.least', 1);
});

/** Types a query in the Prod search box and waits for the answers. */
Cypress.Commands.add('prodSearch', (qry = '') => {
  cy.intercept('POST', '/prod/query/').as('query');
  cy.get('#EDIT_query').clear({ force: true });
  if (qry) cy.get('#EDIT_query').type(qry, { force: true });
  cy.get('#EDIT_query').type('{enter}', { force: true });
  cy.wait('@query').its('response.statusCode').should('eq', 200);
  cy.get('#answers .diapo', { timeout: 30000 }).should('have.length.at.least', 1);
});

/** Selects the n first thumbnails of the result area (plain click, then Ctrl+click). */
Cypress.Commands.add('selectAnswers', (count = 1) => {
  cy.get('#answers .diapo').first().click({ force: true });
  for (let i = 1; i < count; i += 1) {
    cy.get('#answers .diapo').eq(i).click({ ctrlKey: true, force: true });
  }
  cy.get('#answers .diapo.selected').should('have.length', count);
});

/** Clicks a selection action of the main toolbar (dropdown entries included). */
Cypress.Commands.add('toolbarAction', (cls) => {
  cy.get(`#answers_status, #rightFrame, body`).find(`.${cls}`).first().click({ force: true });
});

/** The visible jQuery-UI dialog (Phraseanet uses them for every action window). */
Cypress.Commands.add('dialog', () => cy.get('.ui-dialog:visible', { timeout: 30000 }).last());

/** Closes every opened jQuery-UI dialog. */
Cypress.Commands.add('closeDialogs', () => {
  cy.get('body').then(($b) => {
    $b.find('.ui-dialog:visible .ui-dialog-titlebar-close').each((_, el) => el.click());
  });
});

// ---------------------------------------------------------------- Admin UI

/** Opens Admin and loads a section of the left tree in the right panel. */
Cypress.Commands.add('adminOpen', (href) => {
  cy.intercept({ method: 'GET', pathname: href.split('?')[0] }).as('adminSection');
  cy.visit('/admin/');
  cy.get('#FNDR', { timeout: 30000 }).should('exist');
  // The shell first loads its default section: once rendered, the menu handlers are bound.
  cy.get('#right-ajax', { timeout: 30000 }).children().should('have.length.at.least', 1);
  cy.get(`#FNDR a[href="${href}"]`).first().click({ force: true });
  cy.wait('@adminSection').its('response.statusCode').should('be.lessThan', 400);
  cy.get('#right-ajax', { timeout: 30000 }).should('not.be.empty');
});
