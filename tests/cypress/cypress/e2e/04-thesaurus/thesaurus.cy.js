// §4 Thésaurus (/thesaurus, /xmlhttp)

describe('§4 Thésaurus', () => {
  let sbasId;
  let termId;
  const term = `cyterm${Date.now().toString(36)}`;
  const th = (path) => `/thesaurus/${path}${path.includes('?') ? '&' : '?'}bid=${sbasId}&piv=fr`;

  let thesaurusLoaded = false;

  before(() => {
    cy.login();
    cy.firstCollection().then((c) => {
      sbasId = c.sbasId;
      // The module is optional: a databox may have no thesaurus document yet ("Unable to load thesaurus").
      cy.request({
        method: 'POST', url: '/xmlhttp/openbranches_prod.h.php', form: true, failOnStatusCode: false,
        body: { bid: sbasId, lng: 'fr', t: '', mod: 'TREE', method: '' },
      }).then((r) => {
        thesaurusLoaded = r.status === 200;
        if (!thesaurusLoaded) cy.log(`No thesaurus on databox ${sbasId}: tests that need terms are skipped`);
      });
    });
  });
  beforeEach(() => cy.login());
  after(() => {
    if (termId) { cy.login(); cy.request({ url: th(`xmlhttp/delts.x.php?id=${termId}`), failOnStatusCode: false }); }
  });

  it('accès : choix de la base et de la langue de travail', () => {
    cy.visit('/thesaurus/');
    cy.get('form[name="fBase"]').within(() => {
      cy.get('[name="bid"]').should('exist');
    });
  });

  it('écran en deux volets : candidats à gauche, arbre du thésaurus à droite', () => {
    cy.request(th('thesaurus.php')).its('body').then((html) => {
      expect(html).to.contain('form name="fTh"');
      expect(html).to.contain('ckShowRejected'); // afficher les termes refusés
      expect(html).to.match(/name="term"/); // chercher dans l'arbre
    });
  });

  it('crée un terme spécifique (TS) sans réindexer', function () {
    if (!thesaurusLoaded) this.skip(); // no thesaurus document on this databox
    cy.request(th(`xmlhttp/newts.x.php?pid=T&t=${term}&sylng=fr&reindex=0`)).then(({ body }) => {
      // An empty <refresh_list/> means the databox has no thesaurus document to write in.
      expect(body, 'thesaurus initialised on this databox (newts refreshed the tree)').to.match(/<refresh /);
    });
    cy.request(th('xmlhttp/getterm.x.php?id=T')).its('body').then((xml) => {
      const m = xml.match(new RegExp(`id="(T[\\d.]+)"[^>]*>[\\s\\S]*?${term}`)) || xml.match(/<ts[^>]*id="(T[\d.]+)"/);
      expect(m, `term ${term} in tree`).to.not.equal(null);
      termId = m[1];
    });
  });

  it('ouvre les branches de l’arbre', function () {
    if (!termId) this.skip(); // needs the term created above
    cy.request(th('xmlhttp/openbranches.x.php?id=T')).its('body').should('contain', term);
  });

  it('affiche les propriétés d’un terme', function () {
    if (!termId) this.skip(); // needs the term created above
    cy.request(th(`properties.php?id=${termId}`)).its('status').should('eq', 200);
  });

  it('ajoute un synonyme dans une autre langue', function () {
    if (!termId) this.skip(); // needs the term created above
    cy.request(th(`xmlhttp/newsy.x.php?id=${termId}&t=${term}-en&sylng=en&reindex=0`)).its('status').should('eq', 200);
    cy.request(th(`xmlhttp/getterm.x.php?id=${termId}`)).its('body').should('contain', `${term}-en`);
  });

  it('formulaire « nouveau terme » : saisie, validation, annulation', () => {
    cy.request(th(`newterm.php?pid=${termId || 'T'}&typ=TS`)).its('body').then((html) => {
      expect(html).to.contain('New specific term');
      expect(html).to.contain('id="submit_button"');
      expect(html).to.contain('id="cancel_button"');
    });
  });

  it('recherche un candidat', () => {
    cy.request(th(`xmlhttp/searchcandidate.x.php?pid=T&t=${term}`)).its('status').should('eq', 200);
  });

  it('lien champ ↔ branche : assistant « Lier au champ »', function () {
    if (!termId) this.skip(); // needs the term created above
    cy.request(th(`linkfield.php?tid=${termId}`)).its('status').should('eq', 200);
  });

  it('export au format texte et topics', () => {
    cy.request(th('export_text_dlg.php?typ=TH')).its('status').should('eq', 200);
    cy.request(th('export_text.php?typ=TH&dlg=0')).its('status').should('eq', 200);
    cy.request(th('export_topics_dlg.php?typ=TH')).its('status').should('eq', 200);
    cy.request(th('export_topics.php?typ=TH')).its('status').should('eq', 200);
  });

  it('import : fenêtre d’import d’un fichier texte tabulé', () => {
    cy.request(th('import_dlg.php?id=T')).its('body').should('match', /<input[^>]*type="file"|name="fil"/i);
  });

  it('onglet Thésaurus de Prod : arbre et recherche', function () {
    if (!thesaurusLoaded) this.skip(); // no thesaurus document on this databox
    // Same calls as the Prod client (components/thesaurus, recordEditor thesaurusDatasource).
    cy.request({
      method: 'POST', url: '/xmlhttp/openbranches_prod.h.php', form: true, failOnStatusCode: false,
      body: { bid: sbasId, lng: 'fr', t: '', mod: 'TREE', method: '' },
    }).its('status').should('eq', 200);
    cy.request({ url: `/xmlhttp/search_term_prod.j.php?sbid=${sbasId}&typ=TH&id=T&t=${term}`, failOnStatusCode: false }).its('status').should('eq', 200);
  });

  it('supprime le terme (delts)', function () {
    if (!termId) this.skip(); // needs the term created above
    cy.request(th(`xmlhttp/delts.x.php?id=${termId}`)).its('status').should('eq', 200);
    cy.request(th('xmlhttp/getterm.x.php?id=T')).its('body').should('not.contain', `>${term}<`);
    cy.then(() => { termId = null; });
  });
});
