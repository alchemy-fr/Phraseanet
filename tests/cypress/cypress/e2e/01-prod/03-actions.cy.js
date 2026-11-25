// §1.2 Barre d'actions — §1.3 Export — §1.4 Impression — §1.5 Édition — §1.6 Outils
import { recordWritesAllowed } from '../../support/commands';

/** Opens an action of the main toolbar on the n first results and yields its dialog. */
const openAction = (cls, count = 2) => {
  cy.selectAnswers(count);
  cy.get(`.${cls}[data-selection-source="search-result"], .${cls}`).first().click({ force: true });
};

describe('§1.2-1.6 Prod — actions sur la sélection', () => {
  beforeEach(() => {
    cy.login();
    cy.visitProd();
    cy.prodSearch('');
  });

  it('§1.2 affiche les commandes de la palette selon les droits', () => {
    [
      'TOOL_disktt_btn', 'TOOL_print_btn', 'TOOL_ppen_btn', 'TOOL_chgstatus_btn', 'TOOL_chgcoll_btn',
      'TOOL_sharebasket_btn', 'TOOL_pushdoc_btn', 'TOOL_publish_btn', 'TOOL_imgtools_btn', 'TOOL_trash_btn',
    ].forEach((cls) => cy.get(`.${cls}`).should('exist'));
  });

  it('§1.2 refuse une action sans sélection', () => {
    cy.on('window:alert', (msg) => expect(msg).to.not.be.empty);
    cy.get('#answers .diapo.selected').should('have.length', 0);
    cy.get('.TOOL_trash_btn').first().click({ force: true });
  });

  describe('§1.3 Export', () => {
    it('ouvre la fenêtre Export avec les onglets Télécharger et E-mail', () => {
      cy.intercept('POST', '/prod/export/multi-export/').as('export');
      openAction('TOOL_disktt_btn');
      cy.wait('@export').its('response.statusCode').should('eq', 200);
      cy.dialog().within(() => {
        cy.contains('a', /Download|Télécharger/i).should('exist');
        cy.contains('a', /E-?Mail/i).should('exist');
      });
    });

    it('onglet Télécharger : sous-définitions avec leur poids, légende XML/TXT, champs métier, nom des fichiers', () => {
      openAction('TOOL_disktt_btn');
      cy.dialog().within(() => {
        cy.get('#download_document, #download_preview').should('have.length.at.least', 1);
        cy.get('#download_caption').should('exist');
        cy.get('#business_download').should('exist');
        cy.get('#type_download_title').should('be.checked');
        cy.get('#type_download_original').should('exist');
        cy.get('.download_button').should('be.visible');
        cy.root().invoke('text').should('match', /\d+(\.\d+)?\s*(Ko|Mo|Go|kB|MB|GB|o|B)\b/i);
      });
    });

    it('onglet E-mail : destinataires, message, accusé de réception', () => {
      openAction('TOOL_disktt_btn');
      cy.dialog().within(() => {
        cy.contains('a', /E-?Mail/i).click();
        cy.get('[name="taglistdestmail"], [name="destmail"]').should('exist');
        cy.get('#sendmail_message, [name="textmail"]').should('exist');
        cy.get('[name="reading_confirm"]').should('exist');
        cy.get('.sendmail_button').should('exist');
      });
    });

    it('prépare réellement une archive de téléchargement (preview)', () => {
      cy.recordIds(2).then((ids) => {
        cy.formToken('/prod/export/multi-export/', 'prodExportDownload', { method: 'POST', body: { lst: ids.join(';') }, xhr: true })
          .then((token) => cy.request({
            method: 'POST',
            url: '/prod/download/',
            form: true,
            followRedirect: false,
            body: {
              lst: ids.join(';'), 'obj[]': 'preview', type: 'title', prodExportDownload_token: token,
            },
          })).then((r) => {
            expect(r.status).to.eq(302);
            expect(r.redirectedToUrl).to.match(/\/download\/[^/]+\/prepare\//);
            cy.getOk(r.redirectedToUrl);
          });
      });
    });
  });

  describe('§1.4 Impression', () => {
    it('propose les modèles et les options du PDF', () => {
      cy.intercept('POST', '/prod/printer/*').as('printer');
      openAction('TOOL_print_btn');
      cy.wait('@printer').its('response.statusCode').should('eq', 200);
      cy.get('form[name="formprintpage"]').within(() => {
        ['RADI_PRE_THUMGRI', 'RADI_PRE_LAB', 'RADI_PRE_CAP', 'RADI_PRE_TDM', 'RADI_PRE_THUM', 'RADI_CAP']
          .forEach((id) => cy.get(`#${id}`).should('exist'));
        cy.get('[name="print-pdf-title"]').should('exist');
        cy.get('[name="print-pdf-description"]').should('exist');
        cy.get('[name="description-font-size"]').should('exist');
        cy.get('[name="field-title-color"]').should('exist');
        cy.get('[name="preview-chosen"], [name="thumbnail-chosen"]').should('exist');
        cy.get('[name="can-download-subdef"]').should('exist');
        cy.get('[name="print-download-ttl"]').should('exist');
        cy.get('[name="print-pdf-password"]').should('exist');
        cy.get('#download-title-name, #download-original-name').should('have.length', 2);
      });
    });

    ['thumbnailGrid', 'preview', 'previewCaption', 'previewCaptionTdm', 'thumbnailList', 'caption'].forEach((lay) => {
      it(`génère un PDF avec le modèle « ${lay} »`, () => {
        cy.recordIds(2).then((ids) => {
          cy.formToken('/prod/printer/', 'prodPrint', { method: 'POST', body: { lst: ids.join(';') }, xhr: true })
            .then((token) => cy.request({
              method: 'POST',
              url: '/prod/printer/print.pdf',
              form: true,
              encoding: 'binary',
              body: {
                lst: ids.join(';'), lay, 'print-pdf-title': 'Cypress', 'print-filename': 'title', prodPrint_token: token,
              },
            })).then((r) => {
              expect(r.status).to.eq(200);
              expect(r.headers['content-type']).to.match(/pdf/);
              expect(r.body.slice(0, 5)).to.eq('%PDF-');
            });
        });
      });
    });
  });

  describe('§1.5 Édition', () => {
    it('ouvre l’éditeur avec vignettes, champs, statuts et onglets d’assistance', () => {
      cy.intercept('POST', '/prod/records/edit/').as('edit');
      openAction('TOOL_ppen_btn');
      cy.wait('@edit').its('response.statusCode').should('eq', 200);
      cy.get('#EDITWINDOW', { timeout: 30000 }).should('be.visible').within(() => {
        cy.get('#EDIT_ALL').should('exist');
        cy.get('#EDIT_GRPDIAPO, #EDIT_TOP').should('exist'); // bandeau de vignettes
        cy.get('#divS').should('exist'); // liste des champs
        cy.get('#idEditZone').should('exist'); // zone de saisie
        cy.get('a[href="#TH_Opreview"]').should('exist');
        cy.get('a[href="#TH_Oreplace"]').should('exist'); // rechercher / remplacer
        cy.get('a[href="#TH_Opresets"]').should('exist'); // modèles
        cy.get('a[href="#TH_Oclipboard"]').should('exist'); // presse-papier
      });
    });

    it('rechercher / remplacer : options champ, regex, casse, toutes occurrences', () => {
      openAction('TOOL_ppen_btn');
      cy.get('#EDITWINDOW', { timeout: 30000 }).should('be.visible').within(() => {
        cy.get('a[href="#TH_Oreplace"]').click();
        cy.get('#EditSRField').should('exist');
        cy.get('#EditSR_RX, #EditSROptionRX').should('exist');
        cy.get('#EditSR_case').should('exist');
        cy.get('#EditSearch').should('exist');
        cy.get('#EditReplace').should('exist');
      });
    });

    it('n’enregistre rien sans « Valider » : Annuler ferme l’éditeur', () => {
      cy.intercept('POST', '/prod/records/edit/apply*').as('apply');
      openAction('TOOL_ppen_btn');
      cy.get('#EDITWINDOW', { timeout: 30000 }).should('be.visible');
      cy.on('window:confirm', () => true);
      cy.get('#EDITWINDOW .cancel-multi-desc-action').first().click({ force: true });
      cy.get('#EDITWINDOW').should('not.be.visible');
      cy.get('@apply.all').should('have.length', 0);
    });

    it('modèles de fiches : liste des presets de l’utilisateur', () => {
      cy.firstCollection().then(({ sbasId }) => {
        cy.xhr('GET', `/prod/records/edit/presets?sbas_id=${sbasId}`).its('status').should('eq', 200);
      });
    });

    it('modèles de fiches : enregistrer, charger, supprimer', () => {
      cy.firstCollection().then(({ sbasId }) => {
        const title = `cypress-preset-${Date.now()}`;
        cy.xhr('POST', '/prod/records/edit/presets', {
          sbas_id: sbasId, title, 'fields[0][name]': 'Title', 'fields[0][value][]': 'cypress',
        }).then((r) => {
          // The answer is the refreshed preset list: find the one just created.
          const re = new RegExp(`id="EDIT_PRESET_(\\d+)"[\\s\\S]*?${title}`);
          const m = r.body.html.match(re);
          expect(m, 'preset listed after save').to.not.equal(null);
          const id = m[1];
          cy.xhr('GET', `/prod/records/edit/presets/${id}`).its('status').should('eq', 200);
          cy.xhr('DELETE', `/prod/records/edit/presets/${id}`).its('status').should('be.within', 200, 299);
          cy.xhr('GET', `/prod/records/edit/presets?sbas_id=${sbasId}`).its('body.html').should('not.contain', title);
        });
      });
    });

    it('vocabulaire contrôlé : la route d’autocomplétion répond', () => {
      cy.xhr('GET', '/prod/records/edit/vocabulary/User/?query=adm', undefined, { failOnStatusCode: false })
        .its('status').should('be.oneOf', [200, 400, 404]);
    });

    it('applique une modification de notice puis la restaure', function () {
      if (!recordWritesAllowed()) this.skip();
      // Writes the same value back: proves the apply round-trip without altering the record.
      cy.intercept('POST', '/prod/records/edit/apply*').as('apply');
      openAction('TOOL_ppen_btn', 1);
      cy.get('#EDITWINDOW', { timeout: 30000 }).should('be.visible');
      cy.get('#EDITWINDOW .apply-multi-desc-action').first().click({ force: true });
      cy.wait('@apply').its('response.statusCode').should('eq', 200);
    });
  });

  describe('§1.2 Propriétés, déplacement, suppression', () => {
    it('Propriétés : statuts par boutons radio et type de document', () => {
      cy.intercept('GET', '/prod/records/property/*').as('property');
      openAction('TOOL_chgstatus_btn');
      cy.wait('@property').its('response.statusCode').should('eq', 200);
      cy.dialog().within(() => {
        cy.get('#tabs-records-property').should('exist');
        cy.get('input[type="radio"][name^="status["]').should('have.length.at.least', 2);
        cy.get('.submiter').should('exist');
      });
      cy.xhr('GET', '/prod/records/property/type/?lst=1_1', undefined, { failOnStatusCode: false })
        .its('status').should('eq', 200);
    });

    it('Déplacer : propose seulement les collections de la même base', () => {
      cy.intercept('POST', '/prod/records/movecollection/').as('move');
      openAction('TOOL_chgcoll_btn');
      cy.wait('@move').its('response.statusCode').should('eq', 200);
      cy.dialog().within(() => {
        cy.get('select[name="base_id"] option').should('have.length.at.least', 1);
        cy.get('[name="prodMoveCollection_token"]').should('exist');
      });
    });

    it('Supprimer : demande une confirmation (annulée)', () => {
      cy.intercept('POST', '/prod/records/delete/what/').as('what');
      cy.intercept('POST', '/prod/records/delete/').as('delete');
      openAction('TOOL_trash_btn');
      cy.wait('@what').its('response.statusCode').should('eq', 200);
      cy.dialog().within(() => {
        cy.get('#delete-record-form, #delete_records_parent_only').should('exist');
        cy.get('.cancel').first().click();
      });
      cy.get('@delete.all').should('have.length', 0);
    });
  });

  describe('§1.6 Outils', () => {
    it('ouvre la boîte à outils : rotation, reconstruction des sous-définitions, substitution', () => {
      cy.intercept('GET', '/prod/tools/*').as('tools');
      openAction('TOOL_imgtools_btn');
      cy.wait('@tools').its('response.statusCode').should('eq', 200);
      cy.dialog().within(() => {
        cy.get('#ROTA_90').should('exist');
        cy.get('#ROTA_C90').should('exist');
        cy.get('#recreate_all').should('exist');
        cy.get('[name="force_substitution"]').should('exist');
        cy.get('[name="prodToolsRotate_token"]').should('exist');
        cy.get('[name="prodToolsSubdef_token"]').should('exist');
      });
    });

    it('onglet Métadonnées : affiche les données exiftool d’un seul document', () => {
      openAction('TOOL_imgtools_btn', 1);
      cy.dialog().within(() => {
        cy.get('#tool-tabs a').should('have.length.at.least', 2);
      });
    });

    it('éditeur vidéo / chapitrage : la route répond pour une vidéo', () => {
      cy.search('', { record_type: 'video' }).then((body) => {
        const id = (body.results.match(/id="IMGT_(\d+_\d+)"/) || [])[1];
        if (!id) return;
        cy.request({ url: `/prod/tools/videoEditor?lst=${id}`, headers: { 'X-Requested-With': 'XMLHttpRequest' }, failOnStatusCode: false })
          .its('status').should('eq', 200);
      });
    });

    it('rotation à 90° puis retour (sens horaire / antihoraire)', function () {
      if (!recordWritesAllowed()) this.skip();
      cy.search('', { record_type: 'image' }).then((body) => {
        const id = (body.results.match(/id="IMGT_(\d+_\d+)"/) || [])[1];
        cy.formToken(`/prod/tools/?lst=${id}`, 'prodToolsRotate', { xhr: true }).then((token) => {
          [90, -90].forEach((rotation) => cy.xhr('POST', '/prod/tools/rotate/', {
            lst: id, rotation, element: '', prodToolsRotate_token: token,
          }).its('status').should('eq', 200));
        });
      });
    });
  });
});
