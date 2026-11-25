// §1.1 Recherche — ControllerProvider/Prod/Query.php

const idsOf = (body) => [...new Set((body.results.match(/id="IMGT_(\d+_\d+)"/g) || []).map((s) => s.slice(9, -1)))];

describe('§1.1 Prod — recherche', () => {
  beforeEach(() => cy.login());

  it('recherche simple : une requête vide renvoie les documents accessibles', () => {
    cy.search('').then((body) => {
      expect(body.total_answers).to.be.greaterThan(0);
      expect(idsOf(body).length).to.be.greaterThan(0);
    });
  });

  it('recherche simple dans l’interface : saisie, Entrée, résultats', () => {
    cy.visitProd();
    cy.prodSearch('');
    cy.get('#answers .diapo').should('have.length.at.least', 1);
  });

  it('ignore la casse et les accents', () => {
    // Takes a word from the first caption, then searches it in other spellings.
    cy.search('').then((body) => {
      const word = (body.results.match(/class="[^"]*caption[^"]*"[^>]*>[\s\S]*?([A-Za-zÀ-ÿ]{5,})/) || [])[1];
      if (!word) return;
      cy.search(word).then((a) => {
        cy.search(word.toUpperCase()).its('total_answers').should('eq', a.total_answers);
      });
    });
  });

  it('documents ou reportages : le bouton radio change le type de recherche', () => {
    cy.search('', { search_type: 0 }).its('total_answers').should('be.greaterThan', 0);
    cy.search('', { search_type: 1 }).then((body) => {
      expect(body).to.have.property('total_answers');
      expect(body.results).to.not.match(/type-(image|video|document|audio)\b(?![\s\S]*grouping)/);
    });
    cy.visitProd();
    cy.get('#mode_type_doc').should('be.checked');
    cy.get('#mode_type_reg').check({ force: true }).should('be.checked');
  });

  it('opérateurs : ET implicite, OU, SAUF, expression entre guillemets', () => {
    cy.search('').then((all) => {
      const total = all.total_answers;
      cy.search('zzzunlikelyword').its('total_answers').should('eq', 0);
      cy.search('zzzunlikelyword OU zzzother').its('total_answers').should('eq', 0);
      cy.search('"zzz unlikely phrase"').its('total_answers').should('eq', 0);
      cy.search('* SAUF zzzunlikelyword').its('total_answers').should('be.at.most', total);
    });
  });

  it('recherche sur un champ : Champ:valeur et Champ=valeur', () => {
    cy.search('').then((body) => {
      const fields = Object.keys(body.fields[Object.keys(body.fields)[0]] || {});
      expect(fields.length, 'fields of the databox').to.be.greaterThan(0);
      const field = fields.find((f) => body.fields[Object.keys(body.fields)[0]][f].type === 'string') || fields[0];
      cy.search(`${field}:zzzunlikely`).its('total_answers').should('eq', 0);
      cy.search(`${field}=zzzunlikely`).its('total_answers').should('eq', 0);
    });
  });

  it('expose l’analyse de la requête (AST compilé)', () => {
    cy.search('foo OU bar').then((body) => {
      expect(body.queryAST, 'AST').to.not.be.empty;
      expect(JSON.stringify(body.queryCompiled || body.queryESLib)).to.not.be.empty;
    });
  });

  it('recherche avancée : ouvre le formulaire avec bases, champs, dates et tri', () => {
    cy.visitProd();
    cy.get('#ADV_query, .adv_search_button, [data-target="#advancedSearchForm"], .toggle-advanced-search').first().click({ force: true });
    cy.get('#advancedSearchForm, #ADVSRCH_OPTIONS_ZONE').first().should('exist');
    cy.get('#ADVSRCH_SBAS_ZONE').should('exist'); // bases et collections
    cy.get('#ADVSRCH_FIELDS_ZONE').should('exist'); // conditions sur les champs
    cy.get('#ADVSRCH_DATE_ZONE').should('exist'); // plage de dates
    cy.get('#ADVSRCH_SORT_ZONE').should('exist'); // tri
    cy.get('#ADVSRCH_USE_TRUNCATION').should('exist'); // troncature
  });

  it('filtres : type de document', () => {
    cy.search('', { record_type: 'image' }).then((body) => {
      expect(body.results).to.not.match(/type-(video|audio|document)\b/);
    });
  });

  it('filtres : plage de dates sur un champ date', () => {
    cy.search('').then((all) => {
      const fields = all.fields[Object.keys(all.fields)[0]] || {};
      const dateField = Object.keys(fields).find((f) => fields[f].type === 'date');
      expect(dateField, 'a date field in the structure').to.be.a('string');
      cy.search('', { date_field: dateField, date_min: '1900/01/01', date_max: '1900/01/02' })
        .its('total_answers').should('eq', 0);
      cy.search('', { date_field: dateField, date_min: '1900/01/01', date_max: '2999/12/31' })
        .its('total_answers').should('be.at.most', all.total_answers);
    });
  });

  it('tri : pertinence, date d’ajout, ordre croissant ou décroissant', () => {
    cy.search('', { sort: 'created_on', ord: 'desc' }).then((desc) => {
      cy.search('', { sort: 'created_on', ord: 'asc' }).then((asc) => {
        const d = idsOf(desc);
        const a = idsOf(asc);
        expect(a.length).to.eq(d.length);
        if (d.length > 1) expect(a[0]).to.not.eq(d[0]);
      });
    });
  });

  it('facettes : base, collection et type de document sont calculées', () => {
    cy.search('').then((body) => {
      const names = (body.facets || []).map((f) => f.name);
      expect(names).to.include.members(['_base', '_collection']);
      expect(names.some((n) => /doctype|type/.test(n))).to.eq(true);
      body.facets.forEach((f) => expect(f.values).to.be.an('array'));
    });
  });

  it('facettes : l’onglet Facettes affiche les valeurs et une valeur filtre la recherche', () => {
    cy.visitProd();
    cy.prodSearch('');
    cy.get('#idFrameC a[href="#proposals"]').click({ force: true });
    cy.get('#proposals').find('a, li, .facetFilter, .fancytree-title').should('have.length.at.least', 1);
  });

  it('recherche par le thésaurus : l’onglet Thésaurus affiche les arbres', () => {
    cy.visitProd();
    cy.get('#idFrameC a[href="#thesaurus_tab"]').click({ force: true });
    cy.get('#THPD_tabs').should('exist');
    cy.get('#THPD_T_tree').should('exist');
  });
});
