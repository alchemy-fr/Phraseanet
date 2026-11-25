// §1.8 Reportages — §1.9 WorkZone — §1.10 Prévisualisation et infobulles
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§1.8 Reportages', () => {
  let story;
  let records;
  const title = uid('cy-story');

  before(() => {
    cy.login();
    cy.recordIds(3).then((ids) => { records = ids; });
  });
  beforeEach(() => cy.login());
  after(() => {
    if (story) { cy.login(); cy.deleteRecords([`${story.sbasId}_${story.recordId}`]); }
  });

  it('affiche le formulaire « Nouveau reportage » (collection, titre, sélection courante)', () => {
    cy.request({ url: '/prod/story/create/', headers: JSON_XHR }).then(({ body }) => {
      expect(body).to.match(/name="base_id"/);
      expect(body).to.match(/name="name/);
      expect(body).to.match(/prodCreateStory_token/);
    });
  });

  it('crée un reportage avec deux documents', () => {
    cy.firstCollection().then(({ baseId }) => {
      cy.createStory(title, baseId, records.slice(0, 2)).then((s) => {
        story = s;
        cy.request({ url: `/prod/story/${s.sbasId}/${s.recordId}/`, headers: JSON_XHR }).its('status').should('eq', 200);
      });
    });
  });

  it('est recherchable en mode Reportage (c’est un enregistrement)', () => {
    cy.searchUntil('', { search_type: 1 }, (b) => b.results.includes(`IMGT_${story.sbasId}_${story.recordId}`));
  });

  it('ajoute un élément au reportage', () => {
    cy.xhr('POST', `/prod/story/${story.sbasId}/${story.recordId}/addElements/`, { lst: records[2] })
      .its('status').should('eq', 200);
  });

  it('réordonne les éléments (écran de tri)', () => {
    cy.request({ url: `/prod/story/${story.sbasId}/${story.recordId}/reorder/`, headers: JSON_XHR })
      .its('body').should('contain', 'prodStoryReorder_token');
  });

  it('retire un élément du reportage', () => {
    const [sbas, rid] = records[2].split('_');
    cy.xhr('POST', `/prod/story/${story.sbasId}/${story.recordId}/delete/${sbas}/${rid}/`, {})
      .its('status').should('eq', 200);
  });

  it('se détache puis se rattache à la WorkZone', () => {
    cy.xhr('POST', `/prod/WorkZone/detachStory/${story.sbasId}/${story.recordId}/`, {}).its('status').should('eq', 200);
    cy.xhr('POST', '/prod/WorkZone/attachStories/', { stories: [`${story.sbasId}_${story.recordId}`] })
      .its('status').should('eq', 200);
  });

  it('apparaît dans la WorkZone', () => {
    cy.request({ url: '/prod/WorkZone/', headers: JSON_XHR }).its('body')
      .should('contain', `${story.sbasId}_${story.recordId}`);
  });

  it('se supprime (case « supprimer aussi les médias » non cochée)', () => {
    const id = `${story.sbasId}_${story.recordId}`;
    cy.search('').then((before) => {
      cy.deleteRecords([id]);
      cy.searchUntil('', { search_type: 1 }, (b) => !b.results.includes(`IMGT_${id}`));
      cy.search('').its('total_answers').should('eq', before.total_answers);
      cy.then(() => { story = null; });
    });
  });
});

describe('§1.9 WorkZone', () => {
  beforeEach(() => {
    cy.login();
    cy.visitProd();
  });

  it('affiche les onglets et le contenu des paniers', () => {
    cy.get('#idFrameC').within(() => {
      cy.get('#baskets').should('exist');
      cy.get('#basket-filter, #basket_menu_trigger').should('exist');
    });
  });

  it('est masquable (bouton rétractable)', () => {
    cy.get('#retractableButton').should('exist').click({ force: true });
    cy.get('#idFrameC').invoke('outerWidth').should('be.lessThan', 200);
    cy.get('#retractableButton').click({ force: true });
  });

  it('propose les préférences d’affichage des paniers (statuts, notices, titres)', () => {
    cy.get('#basket_preferences, #basket_status_display, #basket_caption_display, #basket_title_display').should('exist');
  });

  it('filtre la liste par type (paniers, reportages, Push, Share, feedback)', () => {
    ['basketFilter_basket', 'basketFilter_story', 'basketFilter_push', 'basketFilter_share', 'basketFilter_feedback']
      .forEach((id) => cy.get(`#${id}`).should('exist'));
  });

  it('se recharge par la route WorkZone', () => {
    cy.request({ url: '/prod/WorkZone/', headers: JSON_XHR }).its('status').should('eq', 200);
  });
});

describe('§1.10 Prévisualisation et infobulles', () => {
  beforeEach(() => cy.login());

  it('renvoie les données de preview d’un enregistrement', () => {
    cy.recordIds(1).then(([id]) => {
      const [sbas, rid] = id.split('_');
      cy.request({ url: `/prod/records/record/${sbas}/${rid}/`, headers: JSON_XHR, failOnStatusCode: false })
        .its('status').should('be.oneOf', [200, 302]);
    });
  });

  it('infobulles : légende, preview et données techniques', () => {
    cy.recordIds(1).then(([id]) => {
      const [sbas, rid] = id.split('_');
      ['caption', 'preview', 'tc_datas'].forEach((kind) => {
        cy.request({ url: `/prod/tooltip/${kind}/${sbas}/${rid}/answer/0/`, headers: JSON_XHR, failOnStatusCode: false })
          .its('status').should('eq', 200);
      });
    });
  });

  it('lecteur intégré : la preview utilise le lecteur embed (Video.js / pdf.js)', () => {
    cy.visitProd();
    cy.prodSearch('');
    cy.intercept('POST', '/prod/records/').as('record');
    cy.get('#answers .diapo').first().dblclick({ force: true });
    cy.wait('@record');
    cy.get('#PREVIEWIMGCONT iframe, #PREVIEWIMGCONT img, #phraseanet-embed-preview-frame', { timeout: 30000 }).should('exist');
  });

  it('renouvelle l’URL d’une sous-définition (renew-url)', () => {
    cy.recordIds(1).then(([id]) => {
      const [sbas, rid] = id.split('_');
      cy.xhr('POST', '/prod/records/renew-url/', { 'records[]': id, databox_id: sbas, record_id: rid }, { failOnStatusCode: false })
        .its('status').should('be.within', 200, 299);
    });
  });
});
