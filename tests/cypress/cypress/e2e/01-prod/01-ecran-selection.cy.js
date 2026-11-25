// §1.0 Organisation de l'écran et sélection

describe('§1.0 Prod — écran et sélection', () => {
  beforeEach(() => {
    cy.login();
    cy.visitProd();
    cy.prodSearch('');
  });

  it('affiche les quatre zones : recherche, résultats, WorkZone, palette d’actions', () => {
    cy.get('#searchForm').should('be.visible');
    cy.get('#answers').should('be.visible');
    cy.get('#idFrameC').should('exist');
    cy.get('.TOOL_disktt_btn').should('exist');
  });

  it('affiche la WorkZone avec ses onglets Paniers, Facettes et Thésaurus', () => {
    cy.get('#idFrameC').within(() => {
      cy.get('#baskets').should('exist');
      cy.get('#proposals').should('exist');
      cy.get('#thesaurus_tab').should('exist');
    });
  });

  it('affiche le nombre de réponses et la pagination', () => {
    cy.get('#answers_status, #tool_results').should('exist');
    cy.get('#nbrecsel').should('exist');
  });

  it('affiche des vignettes avec titre, type et collection', () => {
    cy.get('#answers .diapo').first().as('thumb');
    cy.get('@thumb').should('have.attr', 'data-id').and('match', /^\d+_\d+$/);
    cy.get('@thumb').invoke('attr', 'class').should('match', /type-(image|video|audio|document|flash)/);
  });

  it('sélectionne un document par clic, puis une plage (Maj+clic) et des documents non contigus (Ctrl+clic)', () => {
    cy.get('#answers .diapo').its('length').then((n) => {
      cy.get('#answers .diapo').eq(0).click({ force: true });
      cy.get('#answers .diapo.selected').should('have.length', 1);
      cy.get('#nbrecsel').should('have.text', '1');

      const last = Math.min(n - 1, 2);
      cy.get('body').type('{shift}', { release: false });
      cy.get('#answers .diapo').eq(last).click({ force: true });
      cy.get('body').type('{shift}'); // release
      cy.get('#answers .diapo.selected').should('have.length', last + 1);

      cy.get('#answers .diapo').eq(1).click({ ctrlKey: true, force: true });
      cy.get('#answers .diapo.selected').should('have.length', last);
    });
  });

  it('propose la sélection globale et par type de média', () => {
    cy.get('#selectCase').parent().find('.dropdown-toggle').click({ force: true });
    cy.get('.answer_selector.all_selector').click({ force: true });
    cy.get('#answers .diapo').its('length').then((n) => {
      cy.get('#answers .diapo.selected').should('have.length', n);
    });
    cy.get('.answer_selector.none_selector').click({ force: true });
    cy.get('#answers .diapo.selected').should('have.length', 0);

    cy.get('#answers .diapo.type-image').its('length').then((images) => {
      cy.get('.answer_selector.image_selector').click({ force: true });
      cy.get('#answers .diapo.selected').should('have.length', images);
    });
  });

  it('ouvre la vue détaillée au double-clic avec ses onglets, puis la ferme par Échap', () => {
    cy.intercept('POST', '/prod/records/').as('record');
    cy.get('#answers .diapo').first().dblclick({ force: true });
    cy.wait('@record');
    cy.get('#PREVIEWBOX', { timeout: 30000 }).should('be.visible');
    cy.get('#PREVIEWBOX').within(() => {
      cy.get('a[href="#PREVIEWIMGDESCINNER-BOX"]').should('exist'); // Notice
      cy.get('a[href="#HISTORICOPS-BOX"]').should('exist'); // Historique
      cy.get('a[href="#popularity-BOX"]').should('exist'); // Popularité
      cy.get('a[href="#NOTICE-BOX"]').should('exist'); // Feedback
      cy.get('#PREVIEWOTHERSINNER, #PREVIEWCURRENTCONT').should('exist'); // bandeau
    });
    cy.get('body').type('{esc}');
    cy.get('#PREVIEWBOX').should('not.be.visible');
  });

  it('navigue dans la vue détaillée avec les flèches (bandeau answer-train)', () => {
    cy.intercept('POST', '/prod/records/').as('record');
    cy.intercept('POST', '/prod/query/answer-train/').as('train');
    cy.get('#answers .diapo').first().dblclick({ force: true });
    cy.wait('@record');
    cy.wait('@train').its('response.statusCode').should('eq', 200);
    cy.get('#PREVIEWCURRENTCONT li').should('have.length.at.least', 1);
    cy.get('#PREVIEWBOX').should('be.visible');
    cy.get('#PREVIEWCURRENTCONT, #PREVIEWBOX').first().then(() => {
      cy.get('body').type('{rightarrow}');
      cy.wait('@record').its('response.statusCode').should('eq', 200);
    });
    cy.get('body').type('{esc}');
  });
});
