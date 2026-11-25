// Runs last: removes whatever an interrupted run may have left behind.
// Only objects whose name starts with the suite prefixes ("cy-", "cycoll") are touched.

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
const PREFIX = /\b(cy-[a-z]+-|cycoll)/;

/** Ids found by `idRe` in `html` whose following text carries a suite name. */
const idsNear = (html, idRe, span = 600) => {
  const ids = new Set();
  for (const m of html.matchAll(idRe)) {
    if (PREFIX.test(html.slice(m.index, m.index + span))) ids.add(m[1]);
  }
  return [...ids];
};

describe('Nettoyage des données de test', () => {
  beforeEach(() => cy.login());

  it('supprime les paniers de test', () => {
    cy.workzoneBaskets().then((list) => list.filter((b) => PREFIX.test(b.name)).forEach((b) => cy.deleteBasket(b.id)));
  });

  it('supprime les listes d’utilisateurs de test', () => {
    cy.xhr('GET', '/prod/lists/all/').its('body.result').then((lists) => {
      (lists || []).filter((l) => PREFIX.test(l.name || '')).forEach((l) => cy.deleteUserList(l.list_id));
    });
  });

  it('supprime les fils de publication de test', () => {
    cy.request('/admin/publications/list/').its('body').then((html) => {
      idsNear(html, /href="\/admin\/publications\/feed\/(\d+)\/"/g, 300).forEach((id) => cy.deleteFeed(id));
    });
  });

  it('supprime les applications OAuth de test', () => {
    cy.request('/developers/applications/').its('body').then((html) => {
      idsNear(html, /\/developers\/application\/(\d+)\//g, 300).forEach((id) => cy.request({
        method: 'DELETE', url: `/developers/application/${id}/`, headers: JSON_XHR, failOnStatusCode: false,
      }));
    });
  });

  it('supprime les utilisateurs de test', () => {
    cy.request({ url: '/admin/users/typeahead/search/?term=cy-', headers: JSON_XHR }).its('body').then((users) => {
      (users || []).filter((u) => /@example\.com$/.test(u.email || '') || /^cy-template/.test(u.login || ''))
        .forEach((u) => cy.deleteUser(u.id));
    });
  });

  it('supprime les collections de test (seulement si elles sont vides)', () => {
    // Never empties anything: Phraseanet refuses to delete a collection that still holds records.
    cy.request('/admin/').its('body').then((html) => {
      [...html.matchAll(/href="\/admin\/collection\/(\d+)\/"[^>]*>\s*(?:<[^>]+>\s*)*cycoll/g)].forEach((m) => {
        cy.request({ method: 'POST', url: `/admin/collection/${m[1]}/delete/`, headers: JSON_XHR, failOnStatusCode: false });
      });
    });
  });
});
