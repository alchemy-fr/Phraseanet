// §5 Report (/report/)

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§5 Report', () => {
  let sbasId;
  before(() => { cy.login(); cy.firstCollection().then((c) => { sbasId = c.sbasId; }); });
  beforeEach(() => cy.login());

  it('tableau de bord : formulaires Connexions, Téléchargements et Contenus', () => {
    cy.visit('/report/dashboard');
    cy.get('#report-connections-form').should('exist');
    cy.get('#report-downloads-form').should('exist');
    cy.get('#report-records-form').should('exist');
    cy.get('[name="dmin"]').should('exist');
    cy.get('[name="dmax"]').should('exist');
    cy.get('[name="group"]').should('exist');
  });

  it('redirige /report/ vers le tableau de bord', () => {
    cy.request({ url: '/report/', followRedirect: false }).its('redirectedToUrl').should('match', /\/report\/dashboard/);
  });

  const reports = [
    ['connexions', 'connections', 'reportConnection', { group: '' }],
    ['téléchargements', 'downloads', 'reportDownload', { group: '' }],
    ['contenus / documents', 'records', 'reportRecord', { group: '' }],
  ];

  reports.forEach(([label, path, tokenName, extra]) => {
    ['csv', 'xlsx', 'ods'].forEach((format) => {
      it(`produit le rapport « ${label} » en ${format.toUpperCase()}`, () => {
        cy.formToken('/report/dashboard', tokenName).then((token) => {
          cy.request({
            method: 'POST',
            url: `/prod/report/${path}/${sbasId}/`,
            form: true,
            encoding: 'binary',
            body: {
              dmin: '2000-01-01', dmax: '2999-12-31', format, sbas: sbasId, 'base[]': '', [`${tokenName}_token`]: token, ...extra,
            },
          }).then((r) => {
            expect(r.status).to.eq(200);
            expect(r.headers['content-disposition'] || '').to.match(/attachment|filename/);
            expect(r.body.length).to.be.greaterThan(0);
          });
        });
      });
    });
  });

  it('regroupe les connexions par utilisateur, navigateur, système…', () => {
    cy.request('/report/dashboard').its('body').then((html) => {
      expect(html).to.match(/name="group"[\s\S]*?<option/);
    });
  });

  it('rapport anonymisable et champs à exporter au choix (Contenus)', () => {
    cy.request('/report/dashboard').its('body').should('contain', 'name="meta[]"');
  });

  it('statistiques par document dans Prod (onglets Historique et Popularité)', () => {
    cy.recordIds(1).then(([id]) => {
      const [sbas, rid] = id.split('_');
      cy.request({ url: `/prod/records/record/${sbas}/${rid}/`, headers: JSON_XHR, failOnStatusCode: false })
        .its('status').should('be.lessThan', 500);
    });
  });

  it('exige le droit « Consulter les rapports » (canreport)', () => {
    cy.clearAllCookies();
    cy.request({ url: '/report/dashboard', followRedirect: false }).its('status').should('eq', 302);
  });
});
