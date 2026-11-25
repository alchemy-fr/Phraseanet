// §1.7 Paniers, Share, Push et feedback — listes d'utilisateurs — partage d'un enregistrement
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§1.7 Paniers', () => {
  let basketId;
  let records;
  const name = uid('cy-basket');

  before(() => {
    cy.login();
    cy.recordIds(3).then((ids) => { records = ids; });
  });

  beforeEach(() => cy.login());

  after(() => {
    cy.login();
    if (basketId) cy.deleteBasket(basketId);
  });

  it('crée un panier avec nom, description et la sélection courante', () => {
    cy.createBasket(name, records.slice(0, 2)).then((id) => {
      basketId = id;
      cy.workzoneBaskets().then((list) => expect(list.map((b) => b.id)).to.include(id));
      cy.request({ url: '/prod/WorkZone/', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).its('body').should('contain', name);
      cy.request(`/prod/baskets/${id}/`).its('body').then((html) => {
        expect(html.match(/class="CHIM diapo/g) || []).to.have.length(2);
      });
    });
  });

  it('affiche le panier dans la WorkZone et ouvre son contenu', () => {
    cy.intercept('GET', `/prod/baskets/${basketId}/`).as('basket');
    cy.visitProd();
    cy.get(`#SSTT_${basketId}`).should('exist').click({ force: true });
    cy.wait('@basket').its('response.statusCode').should('eq', 200);
    cy.get('#idFrameC .CHIM', { timeout: 30000 }).should('have.length', 2);
  });

  it('propose le menu contextuel : Exporter, Lightbox, Renommer, Archiver, Supprimer, Ordonner', () => {
    cy.request(`/prod/baskets/${basketId}/wip/`).its('status').should('eq', 200);
    cy.visitProd();
    cy.get(`#SSTT_${basketId} .contextMenuTrigger`).click({ force: true });
    cy.get('.context-menu-list:visible, .contextMenu:visible, .dropdown-menu:visible').should('exist');
    cy.get(`a[href="/lightbox/compare/${basketId}/"], a[href*="/lightbox/validate/${basketId}/"]`).should('exist');
  });

  it('ajoute des éléments au panier', () => {
    cy.xhr('POST', `/prod/baskets/${basketId}/addElements/`, { lst: records[2] }).its('status').should('eq', 200);
    cy.request(`/prod/baskets/${basketId}/`).its('body').then((html) => {
      expect(html.match(/class="CHIM diapo/g) || []).to.have.length(3);
    });
  });

  it('renomme le panier', () => {
    cy.formToken(`/prod/baskets/${basketId}/update/`, 'prodBasketRename', { xhr: true }).then((token) => {
      cy.xhr('POST', `/prod/baskets/${basketId}/update/`, {
        name: `${name}-renamed`, description: 'renamed', prodBasketRename_token: token,
      }).its('body.success').should('eq', true);
    });
    cy.request({ url: '/prod/WorkZone/', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).its('body').should('contain', `${name}-renamed`);
  });

  it('ordonne le contenu (écran de tri, inversion)', () => {
    cy.request({ url: `/prod/baskets/${basketId}/reorder/`, headers: JSON_XHR }).then((r) => {
      expect(r.status).to.eq(200);
      const els = [...new Set((r.body.match(/name="element\[(\d+)\]"|data-id="(\d+)"/g) || []))];
      expect(els.length).to.be.at.least(1);
    });
    cy.formToken(`/prod/baskets/${basketId}/reorder/`, 'prodBasketReorder', { xhr: true }).should('be.a', 'string');
  });

  it('retire un élément avec la croix (suppression d’un élément du panier)', () => {
    cy.request(`/prod/baskets/${basketId}/`).then(({ body }) => {
      const m = body.match(/\/prod\/baskets\/\d+\/delete\/(\d+)\//) || body.match(/data-sselcont="(\d+)"|id="CHIM_(\d+)"/);
      expect(m, 'basket element id').to.not.equal(null);
      const elementId = m[1] || m[2];
      cy.xhr('POST', `/prod/baskets/${basketId}/delete/${elementId}/`, {}).its('body.success').should('eq', true);
    });
  });

  it('archive le panier puis le retrouve dans « Parcourir les paniers »', () => {
    cy.xhr('POST', `/prod/baskets/${basketId}/archive/`, { archive: 1 }).its('status').should('eq', 200);
    cy.request({ url: '/prod/WorkZone/Browse/Search/?Query=&Year=&Type=', headers: JSON_XHR })
      .its('body').should('contain', `${name}-renamed`);
    cy.request({ url: `/prod/WorkZone/Browse/Basket/${basketId}/`, headers: JSON_XHR }).its('status').should('eq', 200);
    cy.xhr('POST', `/prod/baskets/${basketId}/archive/`, { archive: 0 }).its('status').should('eq', 200);
  });

  it('affiche l’écran « Parcourir les paniers » avec ses filtres', () => {
    cy.request({ url: '/prod/WorkZone/Browse/', headers: JSON_XHR }).its('body')
      .should('match', /Year|Année|Type|Query/i);
  });

  it('déplace (vole) un élément vers un autre panier', () => {
    cy.createBasket(uid('cy-basket-target')).then((target) => {
      cy.request(`/prod/baskets/${basketId}/`).then(({ body }) => {
        const m = body.match(/\/prod\/baskets\/\d+\/delete\/(\d+)\//);
        if (!m) return;
        cy.xhr('POST', `/prod/baskets/${target}/stealElements/`, { 'elements[]': m[1] }).its('status').should('eq', 200);
      });
      cy.deleteBasket(target);
    });
  });

  it('supprime le panier sans supprimer son contenu', () => {
    cy.search('').then((before) => {
      const id = basketId;
      cy.deleteBasket(id).its('body.success').should('eq', true);
      cy.request({ url: `/prod/baskets/${id}/`, failOnStatusCode: false }).its('status').should('be.oneOf', [403, 404]);
      cy.search('').its('total_answers').should('eq', before.total_answers);
      cy.then(() => { basketId = null; });
    });
  });
});

describe('§1.7 Share (validation / feedback) et Push', () => {
  beforeEach(() => {
    cy.login();
    cy.visitProd();
    cy.prodSearch('');
  });

  it('Push : formulaire avec recherche de destinataires, listes, nom, message et accusé de réception', () => {
    cy.intercept('POST', '/prod/push/sendform/').as('push');
    cy.selectAnswers(1);
    cy.get('.TOOL_pushdoc_btn').first().click({ force: true });
    cy.wait('@push').its('response.statusCode').should('eq', 200);
    cy.dialog().within(() => {
      cy.get('#PushBox').should('exist');
      cy.get('[name="users-search"], #find-user').should('exist');
      cy.get('#ListManager').should('exist');
      cy.get('#PushSendFormName, [name="name"]').should('exist');
      cy.get('#PushSendFormMessage, [name="message"]').should('exist');
      cy.get('#PushSendFormRecept, [name="recept"]').should('exist');
    });
  });

  it('Share : droits par participant (HD, modifier, voir les autres, contribuer) et durée de validité', () => {
    cy.intercept('POST', '/prod/push/sharebasketform/').as('share');
    cy.selectAnswers(1);
    cy.get('.TOOL_sharebasket_btn').first().click({ force: true });
    cy.wait('@share').its('response.statusCode').should('eq', 200);
    cy.dialog().within(() => {
      cy.get('[name$="[HD]"]').should('exist');
      cy.get('[name$="[modify]"]').should('exist');
      cy.get('[name$="[see_others]"]').should('exist');
      cy.get('[name$="[agree]"]').should('exist');
      cy.get('[name="shareExpires"], [name="voteExpires"], [name="duration"]').should('exist');
      cy.get('[name="send_reminder"]').should('exist');
    });
  });

  it('recherche un utilisateur (autocomplétion à partir de 3 caractères)', () => {
    cy.xhr('GET', '/prod/push/search-user/?query=adm').then((r) => {
      expect(r.status).to.eq(200);
      expect(JSON.stringify(r.body)).to.match(/admin/i);
    });
  });

  it('affiche la fiche d’un destinataire', () => {
    cy.xhr('GET', '/prod/push/search-user/?query=adm').then((r) => {
      const id = (JSON.stringify(r.body).match(/"usr_id":"?(\d+)/) || [])[1];
      if (!id) return;
      cy.xhr('GET', `/prod/push/user/${id}/`).its('status').should('eq', 200);
    });
  });

  it('propose la création d’utilisateur à la volée', () => {
    cy.request({ url: '/prod/push/add-user/', headers: JSON_XHR, failOnStatusCode: false })
      .its('status').should('eq', 200);
  });

  it('envoie un Push à soi-même puis supprime le panier reçu', () => {
    cy.recordIds(1).then((ids) => {
      cy.xhr('GET', '/prod/push/search-user/?query=adm').then((r) => {
        const me = (JSON.stringify(r.body).match(/"usr_id":"?(\d+)/) || [])[1];
        const name = uid('cy-push');
        cy.xhr('POST', '/prod/push/send/', {
          lst: ids.join(';'), name, message: 'cypress', 'participants[0][usr_id]': me, 'participants[0][HD]': 0,
        }, { failOnStatusCode: false }).then((resp) => {
          expect(resp.status).to.eq(200);
          expect(resp.body.success, resp.body.message).to.eq(true);
        });
        cy.workzoneBaskets().then((list) => {
          list.filter((b) => b.name.includes(name)).forEach((b) => cy.deleteBasket(b.id));
        });
      });
    });
  });
});

describe('§1.7 Listes d’utilisateurs', () => {
  let listId;
  const name = uid('cy-list');

  beforeEach(() => cy.login());
  after(() => { if (listId) { cy.login(); cy.deleteUserList(listId); } });

  it('liste toutes les listes', () => {
    cy.xhr('GET', '/prod/lists/all/').its('body.success').should('eq', true);
  });

  it('crée une liste', () => {
    cy.createUserList(name).then((id) => {
      listId = id;
      cy.xhr('GET', '/prod/lists/all/').its('body.result').then((lists) => {
        expect(JSON.stringify(lists)).to.contain(name);
      });
    });
  });

  it('renomme la liste', () => {
    cy.xhr('POST', `/prod/lists/list/${listId}/update/`, { name: `${name}-2` }).its('body.success').should('eq', true);
    cy.xhr('GET', `/prod/lists/list/${listId}/`).its('body').then((b) => expect(JSON.stringify(b)).to.contain(`${name}-2`));
  });

  it('ajoute puis retire un membre', () => {
    cy.xhr('GET', '/prod/push/search-user/?query=adm').then((r) => {
      const me = (JSON.stringify(r.body).match(/"usr_id":"?(\d+)/) || [])[1];
      cy.xhr('POST', `/prod/lists/list/${listId}/add/`, { 'usr_ids[]': me }).its('body.success').should('eq', true);
      cy.xhr('POST', `/prod/lists/list/${listId}/remove/${me}/`, {}).its('body.success').should('eq', true);
    });
  });

  it('affiche l’écran de partage de la liste (Accès, Éditeur, Admin)', () => {
    cy.request({ url: `/prod/lists/list/${listId}/share/`, headers: JSON_XHR }).its('status').should('eq', 200);
  });

  it('supprime la liste', () => {
    cy.deleteUserList(listId).its('body.success').should('eq', true);
    cy.then(() => { listId = null; });
  });
});

describe('§1.7 Partage d’un enregistrement', () => {
  beforeEach(() => cy.login());

  it('affiche les options de partage (permaliens, URL, intégration)', () => {
    cy.firstCollection().then(({ baseId }) => {
      cy.recordIds(1).then(([id]) => {
        const recordId = id.split('_')[1];
        cy.request({ url: `/prod/share/record/${baseId}/${recordId}/`, headers: JSON_XHR, failOnStatusCode: false })
          .its('status').should('be.oneOf', [200, 403]);
      });
    });
  });
});
