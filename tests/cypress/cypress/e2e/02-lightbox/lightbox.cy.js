// §2 Lightbox — consultation collaborative et validation
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§2 Lightbox', () => {
  let basketId;
  let me;
  let elementIds = [];
  const name = uid('cy-validation');

  before(() => {
    cy.login();
    cy.xhr('GET', '/prod/push/search-user/?query=adm').then((r) => {
      me = (JSON.stringify(r.body).match(/"usr_id":"?(\d+)/) || [])[1];
    });
    cy.recordIds(2).then((ids) => cy.createBasket(name, ids)).then((id) => {
      basketId = id;
      // Demande de validation (Share avec feedback) adressée à soi-même.
      cy.formToken('/prod/push/sharebasketform/', 'prodShareBasket', { method: 'POST', body: { ssel: id }, xhr: true })
        .then((token) => cy.xhr('POST', '/prod/push/sharebasket/', {
          ssel: id,
          name,
          message: 'cypress',
          isFeedback: 1,
          'participants[0][usr_id]': me,
          'participants[0][agree]': 1,
          'participants[0][HD]': 1,
          'participants[0][see_others]': 1,
          'participants[0][modify]': 1,
          prodShareBasket_token: token,
        })).its('body.success').should('eq', true);
      // Participants are added by the "shareBasket" worker: wait until the vote is reachable.
      const waitVote = (left) => cy.request({ url: `/lightbox/validate/${id}/`, failOnStatusCode: false }).then((r) => {
        if (r.status === 200) return;
        if (left <= 0) {
          throw new Error('Validation basket never became reachable: is the Phraseanet worker (queue "shareBasket") running?');
        }
        cy.wait(2000, { log: false });
        waitVote(left - 1);
      });
      waitVote(30);
    });
  });

  beforeEach(() => cy.login());

  after(() => { if (basketId) { cy.login(); cy.deleteBasket(basketId); } });

  it('liste les paniers et validations reçus', () => {
    cy.request('/lightbox/').its('body').should('contain', name);
  });

  it('affiche l’écran de validation : barre d’action, table lumineuse, bandeau de vignettes', () => {
    cy.visit(`/lightbox/validate/${basketId}/`);
    cy.get('body').should('contain', name);
    cy.get('img').should('have.length.at.least', 2); // vignettes du bandeau
    cy.get('.agree, .disagree, [class*="agree"]').should('exist'); // boutons de vote
  });

  it('liste les éléments du panier (GET_ELEMENTS)', () => {
    cy.request({ url: `/lightbox/ajax/GET_ELEMENTS/${basketId}/`, headers: JSON_XHR }).then(({ body }) => {
      const text = JSON.stringify(body);
      elementIds = [...new Set((text.match(/"(?:id|sselcont_id|basket_element_id)":"?(\d+)/g) || []).map((s) => s.match(/(\d+)$/)[1]))];
      if (!elementIds.length) {
        cy.request(`/lightbox/validate/${basketId}/`).then((r) => {
          elementIds = [...new Set((r.body.match(/sselcont_(\d+)|LOAD_BASKET_ELEMENT\/(\d+)|data-sselcont-id="(\d+)"/g) || []).map((s) => s.match(/(\d+)\D*$/)[1]))];
          expect(elementIds.length, 'basket elements').to.be.at.least(1);
        });
      }
    });
  });

  it('affiche un élément (LOAD_BASKET_ELEMENT)', () => {
    cy.request({ url: `/lightbox/ajax/LOAD_BASKET_ELEMENT/${elementIds[0]}/`, headers: JSON_XHR }).its('status').should('eq', 200);
  });

  it('compare deux éléments côte à côte', () => {
    cy.getOk(`/lightbox/compare/${basketId}/`);
  });

  it('vote d’accord puis pas d’accord sur un élément', () => {
    cy.xhr('POST', `/lightbox/ajax/SET_ELEMENT_AGREEMENT/${elementIds[0]}/`, { agreement: 1 }).its('body.error').should('eq', false);
    cy.xhr('POST', `/lightbox/ajax/SET_ELEMENT_AGREEMENT/${elementIds[0]}/`, { agreement: -1 }).its('body.error').should('eq', false);
  });

  it('annote un élément (formulaire de note puis enregistrement)', () => {
    cy.request({ url: `/lightbox/ajax/NOTE_FORM/${elementIds[0]}/`, headers: JSON_XHR }).its('status').should('eq', 200);
    cy.formToken(`/lightbox/validate/${basketId}/`, 'lightbox').then((token) => {
      cy.xhr('POST', `/lightbox/ajax/SET_NOTE/${elementIds[0]}/`, { note: 'cypress note', lightbox_token: token })
        .its('status').should('eq', 200);
    });
  });

  it('affiche le rapport de validation', () => {
    cy.request({ url: `/lightbox/ajax/LOAD_REPORT/${basketId}/`, headers: JSON_XHR }).its('status').should('eq', 200);
  });

  it('envoie son rapport (SET_RELEASE)', () => {
    cy.xhr('POST', `/lightbox/ajax/SET_RELEASE/${basketId}/`, {}).its('body.error').should('eq', false);
  });

  it('propose le diaporama et le téléchargement', () => {
    cy.visit(`/lightbox/validate/${basketId}/`);
    cy.get('body').invoke('html').should('match', /slideshow|diaporama|play/i);
    cy.get('body').invoke('html').should('match', /download|export|basket_downloader|telecharg/i);
  });

  it('consulte une entrée de publication', () => {
    const feedTitle = uid('cy-lb-feed');
    cy.createFeed(feedTitle).then((feedId) => {
      cy.recordIds(1).then((ids) => cy.xhr('POST', '/prod/feeds/entry/create/', {
        feed_id: feedId, title: feedTitle, subtitle: '', author_name: 'Cypress', author_mail: 'cypress@example.com', lst: ids.join(';'), notify: 0,
      }));
      cy.request({ url: `/prod/feeds/feed/${feedId}/`, headers: JSON_XHR }).then(({ body }) => {
        const entryId = (body.match(/\/prod\/feeds\/entry\/(\d+)\/edit\//) || [])[1];
        cy.getOk(`/lightbox/feeds/entry/${entryId}/`);
      });
      cy.deleteFeed(feedId);
    });
  });

  it('s’ouvre aussi depuis le menu contextuel d’un panier de Prod', () => {
    cy.visitProd();
    cy.get(`a[href="/lightbox/compare/${basketId}/"], a[href="/lightbox/validate/${basketId}/"]`).should('exist');
  });
});
