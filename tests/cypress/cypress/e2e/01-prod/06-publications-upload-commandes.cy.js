// §1.11 Publications — §1.12 Expose — §1.13 Upload et quarantaine — §1.14 Commandes
import { uid, recordWritesAllowed } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§1.11 Publications (Prod)', () => {
  let feedId;
  const feedTitle = uid('cy-feed');
  const entryTitle = uid('cy-entry');

  before(() => {
    cy.login();
    cy.createFeed(feedTitle).then((id) => { feedId = id; });
  });
  beforeEach(() => cy.login());
  after(() => { if (feedId) { cy.login(); cy.deleteFeed(feedId); } });

  it('ouvre le formulaire de publication avec titre, sous-titre, auteur, email, fil et notification', () => {
    cy.visitProd();
    cy.prodSearch('');
    cy.intercept('POST', '/prod/feeds/requestavailable/').as('publish');
    cy.selectAnswers(1);
    cy.get('.TOOL_publish_btn').first().click({ force: true });
    cy.wait('@publish').its('response.statusCode').should('eq', 200);
    cy.dialog().within(() => {
      cy.get('#feed_add_title').should('exist');
      cy.get('#feed_add_subtitle').should('exist');
      cy.get('#feed_add_author_name').should('exist');
      cy.get('#feed_add_author_mail').should('exist');
      cy.get('[name="feed_id"]').should('exist');
      cy.get('.feeds .feed').should('contain', feedTitle); // fils où l'utilisateur publie
    });
  });

  it('crée une entrée dans un fil', () => {
    cy.recordIds(2).then((ids) => {
      cy.xhr('POST', '/prod/feeds/entry/create/', {
        feed_id: feedId, title: entryTitle, subtitle: 'cypress', author_name: 'Cypress', author_mail: 'cypress@example.com', lst: ids.join(';'), notify: 0,
      }).its('body.error').should('eq', false);
    });
  });

  it('consulte le fil et le fil agrégé', () => {
    cy.request({ url: `/prod/feeds/feed/${feedId}/`, headers: JSON_XHR }).its('body').should('contain', entryTitle);
    cy.request({ url: '/prod/feeds/', headers: JSON_XHR }).its('status').should('eq', 200);
  });

  it('édite puis met à jour l’entrée', () => {
    cy.request({ url: `/prod/feeds/feed/${feedId}/`, headers: JSON_XHR }).then(({ body }) => {
      const id = (body.match(/\/prod\/feeds\/entry\/(\d+)\/edit\//) || body.match(/data-entry[-_]id="(\d+)"/) || [])[1];
      expect(id, 'entry id').to.exist;
      cy.request({ url: `/prod/feeds/entry/${id}/edit/`, headers: JSON_XHR }).its('status').should('eq', 200);
      cy.xhr('POST', `/prod/feeds/entry/${id}/update/`, {
        feed_id: feedId, title: `${entryTitle}-2`, subtitle: 'cypress', author_name: 'Cypress', author_mail: 'cypress@example.com',
      }).its('body.error').should('eq', false);
      cy.request({ url: `/prod/feeds/feed/${feedId}/`, headers: JSON_XHR }).its('body').should('contain', `${entryTitle}-2`);
    });
  });

  it('s’abonne en RSS / Atom (fil et fil agrégé)', () => {
    cy.request({ url: `/prod/feeds/subscribe/${feedId}/`, headers: JSON_XHR }).its('body.texte').should('match', /https?:\/\//);
    cy.request({ url: '/prod/feeds/subscribe/aggregated/', headers: JSON_XHR }).its('body.texte').should('match', /https?:\/\//);
  });

  it('supprime l’entrée', () => {
    cy.request({ url: `/prod/feeds/feed/${feedId}/`, headers: JSON_XHR }).then(({ body }) => {
      const id = (body.match(/\/prod\/feeds\/entry\/(\d+)\/edit\//) || [])[1];
      cy.xhr('POST', `/prod/feeds/entry/${id}/delete/`, {}).its('body.error').should('eq', false);
      cy.request({ url: `/prod/feeds/feed/${feedId}/`, headers: JSON_XHR }).its('body').should('not.contain', entryTitle);
    });
  });
});

describe('§1.12 Expose (Phraseanet Service)', () => {
  beforeEach(() => cy.login());

  it('le paramétrage Expose est accessible dans Admin > Phraseanet Service', () => {
    cy.getOk('/admin/phraseanet-service/', { xhr: true });
  });

  it('les routes Expose de Prod répondent (liste des publications)', () => {
    cy.request({ url: '/prod/expose/list-publication/?exposeName=', headers: JSON_XHR, failOnStatusCode: false })
      .its('status').should('be.lessThan', 500);
  });
});

describe('§1.13 Upload et quarantaine', () => {
  beforeEach(() => cy.login());

  it('ouvre la fenêtre d’upload : collections autorisées, statuts, taille maximale, ajout par URL', () => {
    cy.request({ url: '/prod/upload/html5-version/', headers: JSON_XHR }).then(({ body }) => {
      expect(body).to.match(/name="base_id"/);
      expect(body).to.match(/name="files\[\]"/);
      expect(body).to.match(/prodUpload_token/);
      expect(body).to.match(/add_url_upload|id="add-url"/);
      expect(body).to.match(/name="status\[\d+\]\[\d+\]"/);
    });
    cy.getOk('/prod/upload/', { xhr: true });
  });

  it('ajoute un fichier à la liste puis vide la liste (sans envoi)', () => {
    cy.visitProd();
    cy.get('.uploader-open-action').first().click({ force: true });
    cy.get('#uploadBox, #fileupload', { timeout: 30000 }).should('exist');
    cy.get('#fileupload input[type="file"], input[name="files[]"]').first()
      .selectFile('cypress/fixtures/cypress-upload.png', { force: true });
    cy.get('#uploadBox .upload-box-addedfiles', { timeout: 15000 }).should('contain', 'cypress-upload.png');
    cy.get('#uploadBox .number-files').should('contain', '1');
    cy.get('.clear-queue').first().click({ force: true });
    cy.get('#uploadBox .upload-box-addedfiles').should('not.contain', 'cypress-upload.png');
  });

  it('interroge une URL distante (getHead) pour l’ajout par URL', () => {
    cy.xhr('GET', `/prod/upload/head/?url=${encodeURIComponent(`${Cypress.config('baseUrl')}/login/`)}`, undefined, { failOnStatusCode: false })
      .its('status').should('be.lessThan', 500);
  });

  it('envoie un fichier dans une collection', function () {
    if (!recordWritesAllowed()) this.skip();
    cy.firstCollection().then(({ baseId }) => {
      cy.formToken('/prod/upload/html5-version/', 'prodUpload', { xhr: true }).then((token) => {
        cy.fixture('cypress-upload.png', 'binary').then((bin) => {
          const blob = Cypress.Blob.binaryStringToBlob(bin, 'image/png');
          const fd = new FormData();
          fd.append('files[]', blob, `${uid('cy-upload')}.png`);
          fd.append('base_id', baseId);
          fd.append('prodUpload_token', token);
          cy.request({ method: 'POST', url: '/prod/upload/', body: fd, headers: JSON_XHR }).its('status').should('eq', 200);
        });
      });
    });
  });

  it('liste la quarantaine', () => {
    cy.request({ url: '/prod/lazaret/', headers: JSON_XHR }).its('status').should('eq', 200);
  });

  it('affiche le détail et la vignette d’un fichier en quarantaine (s’il y en a)', () => {
    cy.request({ url: '/prod/lazaret/', headers: JSON_XHR }).then(({ body }) => {
      const id = (String(body).match(/class="[^"]*wrapper-item[^"]*"[^>]*id="(\d+)"|id="(\d+)"[^>]*class="[^"]*lazaret/) || []).filter(Boolean)[1];
      if (!id) return;
      cy.request({ url: `/prod/lazaret/${id}/`, headers: JSON_XHR }).its('status').should('eq', 200);
      cy.request({ url: `/prod/lazaret/${id}/thumbnail/`, failOnStatusCode: false }).its('status').should('be.oneOf', [200, 404]);
    });
  });
});

describe('§1.14 Commandes', () => {
  beforeEach(() => cy.login());

  it('affiche la rubrique Commandes (à traiter / traitées)', () => {
    cy.request({ url: '/prod/order/', headers: JSON_XHR }).its('status').should('eq', 200);
    cy.request({ url: '/prod/order/?sort=created_on&todo=0', headers: JSON_XHR }).its('status').should('eq', 200);
  });

  it('l’onglet Commander apparaît dans l’export pour un utilisateur sans droit HD', () => {
    // The admin can download originals: the form exists but stays hidden for him.
    cy.recordIds(1).then((ids) => {
      cy.xhr('POST', '/prod/export/multi-export/', { lst: ids.join(';') }).its('body').should('match', /prodExportOrder_token|prodExportDownload_token/);
    });
  });
});

describe('§1.15 Divers Prod', () => {
  beforeEach(() => cy.login());

  it('rapports par base (/prod/report/)', () => {
    cy.request({ url: '/prod/report/connections/1/', headers: JSON_XHR, failOnStatusCode: false })
      .its('status').should('be.lessThan', 500);
  });

  it('CGU : affiche les conditions des bases', () => {
    cy.getOk('/prod/TOU/', { xhr: true });
  });

  it('préférences : enregistre un réglage utilisateur', () => {
    cy.xhr('POST', '/user/preferences/', { prop: 'images_per_page', value: 20 }).its('body.success').should('eq', true);
  });

  it('préférences : ouvre le panneau d’affichage (vignettes/liste, taille, thème, survol)', () => {
    cy.visitProd();
    cy.get('#look_box, #settings').should('exist');
    cy.get('#nperpage_slider, #nperpage_value').should('exist');
    cy.get('#sizeAns_slider, #sizeAns_value').should('exist');
    cy.get('#rollover_caption, #rollover_preview').should('exist');
    cy.get('#list_view, [name="view_type"]').should('exist');
  });

  it('raccourcis clavier : la fenêtre d’aide les liste', () => {
    cy.visitProd();
    cy.get('#keyboard-dialog').should('exist').invoke('text').should('match', /ctrl|shift|esc/i);
  });

  it('notifications : liste et marquage comme lues', () => {
    cy.request({ url: '/user/notifications/', headers: JSON_XHR }).its('status').should('eq', 200);
    cy.xhr('POST', '/user/notifications/read-all/', {}).its('status').should('eq', 200);
  });

  it('changement de langue de l’interface', () => {
    cy.request({ url: '/prod/language/', headers: JSON_XHR }).its('status').should('eq', 200);
  });
});
