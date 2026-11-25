// §3.3 Bases — §3.4 Collections — §3.5 Structure — §3.6 Sous-définitions
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§3.3 Bases (databox)', () => {
  let sbasId;
  before(() => { cy.login(); cy.firstCollection().then((c) => { sbasId = c.sbasId; }); });
  beforeEach(() => cy.login());

  it('liste les bases et propose d’en créer ou d’en monter une', () => {
    cy.request('/admin/databoxes/').its('body').then((html) => {
      expect(html).to.contain(`/admin/databox/${sbasId}/`);
      expect(html).to.match(/action="\/admin\/databoxes\/"|action="\/admin\/databoxes\/mount\/"/);
    });
  });

  it('affiche une base : nom d’affichage, libellés, indexabilité, purge des logs, démontage, suppression, logo PDF', () => {
    cy.request(`/admin/databox/${sbasId}/`).its('body').then((html) => {
      ['view-name', 'labels', 'indexable', 'clear-logs', 'unmount', 'delete', 'reindex', 'logo/delete']
        .forEach((a) => expect(html, a).to.contain(`/admin/databox/${sbasId}/${a}/`));
      expect(html).to.contain('name="newLogoPdf"');
    });
  });

  it('affiche les informations : nombre de documents et détails', () => {
    cy.getOk(`/admin/databox/${sbasId}/informations/documents/`, { xhr: true });
    cy.getOk(`/admin/databox/${sbasId}/informations/details/`, { xhr: true });
  });

  it('CGU de la base : un texte par langue et l’option de les imposer aux inscrits', () => {
    cy.request(`/admin/databox/${sbasId}/cgus/`).its('body').then((html) => {
      expect(html).to.match(/name="TOU\[|name="cgu\[|textarea/i);
      expect(html).to.match(/valid|accept/i);
    });
  });

  it('ordre des collections : monter, descendre, alphabétique', () => {
    cy.request(`/admin/databox/${sbasId}/collections/order/`).its('body').should('match', /order|ordre/i);
  });

  it('formulaire de nouvelle collection avec option de duplication des droits', () => {
    cy.request(`/admin/databox/${sbasId}/collection/`).its('body').then((html) => {
      expect(html).to.contain('name="name"');
      expect(html).to.match(/othcollsel|ccusrothercoll/);
    });
  });
});

describe('§3.4 Collections — cycle de vie complet sur une collection de test', () => {
  let sbasId;
  let baseId;
  const name = uid('cycoll');

  before(() => { cy.login(); cy.firstCollection().then((c) => { sbasId = c.sbasId; }); });
  beforeEach(() => cy.login());
  after(() => {
    if (baseId) { cy.login(); cy.request({ method: 'POST', url: `/admin/collection/${baseId}/delete/`, headers: JSON_XHR, failOnStatusCode: false }); }
  });

  it('crée une collection', () => {
    cy.request({
      method: 'POST', url: `/admin/databox/${sbasId}/collection/`, form: true, followRedirect: false, body: { name },
    }).then((r) => {
      expect(r.status).to.eq(302);
      const m = r.redirectedToUrl.match(/\/admin\/collection\/(\d+)\//);
      expect(m, r.redirectedToUrl).to.not.equal(null);
      baseId = Number(m[1]);
    });
  });

  it('affiche la collection : identité, cycle de vie, images, commandes, publication', () => {
    cy.request(`/admin/collection/${baseId}/`).its('body').then((html) => {
      expect(html).to.contain(name);
      ['order/admins', 'publication/display', 'labels', 'rename', 'disabled', 'empty', 'delete']
        .forEach((a) => expect(html, a).to.contain(`/admin/collection/${baseId}/${a}/`));
      ['newLogo', 'newWm', 'newStamp'].forEach((f) => expect(html, f).to.contain(`name="${f}"`));
    });
  });

  it('modifie les libellés par langue', () => {
    cy.request({
      method: 'POST', url: `/admin/collection/${baseId}/labels/`, form: true, headers: JSON_XHR,
      body: { 'labels[fr]': `${name}-fr`, 'labels[en]': `${name}-en`, 'labels[de]': '', 'labels[nl]': '' },
    }).its('body.success').should('eq', true);
  });

  it('renomme la collection', () => {
    cy.request({
      method: 'POST', url: `/admin/collection/${baseId}/rename/`, form: true, headers: JSON_XHR, body: { name: `${name}r` },
    }).its('body.success').should('eq', true);
  });

  it('désactive puis réactive la collection', () => {
    cy.request({ method: 'POST', url: `/admin/collection/${baseId}/disabled/`, headers: JSON_XHR }).its('body.success').should('eq', true);
    cy.request({ method: 'POST', url: `/admin/collection/${baseId}/enable/`, headers: JSON_XHR }).its('body.success').should('eq', true);
  });

  it('affiche le détail : enregistrements, documents, sous-résolutions', () => {
    cy.getOk(`/admin/collection/${baseId}/informations/details/`, { xhr: true });
  });

  it('règle l’affichage dans les publications (marquage)', () => {
    cy.request({
      method: 'POST', url: `/admin/collection/${baseId}/publication/display/`, form: true, headers: JSON_XHR, body: { pub_wm: 'none' },
    }).its('body.success').should('eq', true);
  });

  it('définit les gestionnaires de commandes', () => {
    cy.request({
      method: 'POST', url: `/admin/collection/${baseId}/order/admins/`, form: true, followRedirect: false, body: { 'admins[]': 1 },
    }).its('redirectedToUrl').should('match', /success=1/);
  });

  it('valeurs suggérées : écran graphique / XML et enregistrement', () => {
    cy.request(`/admin/collection/${baseId}/suggested-values/`).its('body').should('match', /<textarea|str|suggested/i);
  });

  it('vide la collection', () => {
    cy.request({ method: 'POST', url: `/admin/collection/${baseId}/empty/`, headers: JSON_XHR }).its('body.success').should('eq', true);
  });

  it('supprime la collection (seulement si elle est vide)', () => {
    cy.request({ method: 'POST', url: `/admin/collection/${baseId}/delete/`, headers: JSON_XHR }).its('body.success').should('eq', true);
    cy.request({ url: `/admin/collection/${baseId}/`, failOnStatusCode: false }).its('status').should('be.oneOf', [403, 404, 500]);
    cy.then(() => { baseId = null; });
  });
});

describe('§3.5 Structure', () => {
  let sbasId;
  before(() => { cy.login(); cy.firstCollection().then((c) => { sbasId = c.sbasId; }); });
  beforeEach(() => cy.login());

  it('liste les champs (API JSON de l’écran Champs)', () => {
    cy.request({ url: `/admin/fields/${sbasId}/fields`, headers: JSON_XHR }).then(({ body }) => {
      expect(body).to.be.an('array').and.not.be.empty;
      const f = body[0];
      ['name', 'type', 'multi', 'required', 'readonly', 'indexable', 'business', 'aggregable', 'tbranch', 'thumbtitle', 'report', 'dces-element', 'tag', 'sorter']
        .forEach((k) => expect(f, k).to.have.property(k));
    });
  });

  it('affiche un champ', () => {
    cy.request({ url: `/admin/fields/${sbasId}/fields`, headers: JSON_XHR }).then(({ body }) => {
      cy.request({ url: `/admin/fields/${sbasId}/fields/${body[0].id}`, headers: JSON_XHR }).its('body.name').should('eq', body[0].name);
    });
  });

  it('source des données : recherche de tags EXIF / IPTC / XMP', () => {
    cy.request({ url: '/admin/fields/tags/search?term=IPTC', headers: JSON_XHR }).its('body').should('be.an', 'array');
  });

  it('vocabulaires contrôlés et équivalents Dublin Core', () => {
    cy.request({ url: '/admin/fields/vocabularies', headers: JSON_XHR }).its('body').should('be.an', 'array');
    cy.request({ url: '/admin/fields/dc-fields', headers: JSON_XHR }).its('body').should('be.an', 'array').and('not.be.empty');
  });

  it('crée puis supprime un champ', () => {
    const name = `Cy${Date.now().toString(36)}`;
    // Clones an existing field definition (all mandatory keys) under a new name.
    cy.request({ url: `/admin/fields/${sbasId}/fields`, headers: JSON_XHR }).then(({ body: fields }) => {
      const model = { ...fields.find((f) => f.type === 'string') || fields[0] };
      delete model.id;
      return cy.request({
        method: 'POST', url: `/admin/fields/${sbasId}/fields`, headers: { ...JSON_XHR, 'Content-Type': 'application/json' },
        body: {
          ...model, name, tag: '', sorter: 9999, thumbtitle: '0', 'dces-element': null,
        },
      });
    }).then(({ body }) => {
      expect(body.name).to.eq(name);
      cy.request({ method: 'DELETE', url: `/admin/fields/${sbasId}/fields/${body.id}`, headers: JSON_XHR }).its('status').should('be.within', 200, 299);
    });
  });

  it('affiche l’écran graphique des champs', () => {
    cy.getOk(`/admin/fields/${sbasId}`);
  });

  it('bits de statut : liste et écran d’édition', () => {
    cy.request(`/admin/statusbit/${sbasId}/`).its('body').then((html) => {
      const bit = (html.match(/\/admin\/statusbit\/\d+\/status\/(\d+)\//) || [])[1];
      expect(bit, 'a status bit').to.exist;
      cy.request(`/admin/statusbit/${sbasId}/status/${bit}/`).its('body').should('match', /label|icon|searchable|printable/i);
    });
  });

  it('structure XML de la base', () => {
    cy.request(`/admin/structure/${sbasId}/`).its('body').should('match', /&lt;record|<record|textarea/);
  });
});

describe('§3.6 Sous-définitions', () => {
  let sbasId;
  before(() => { cy.login(); cy.firstCollection().then((c) => { sbasId = c.sbasId; }); });
  beforeEach(() => cy.login());

  it('liste les sous-définitions par type de média, avec thumbnail et preview', () => {
    cy.request(`/admin/subdefs/${sbasId}/`).its('body').then((html) => {
      ['image', 'video', 'audio', 'document'].forEach((t) => expect(html, t).to.match(new RegExp(`name="${t}_`)));
      expect(html).to.contain('thumbnail');
      expect(html).to.contain('preview');
    });
  });

  it('expose les réglages : taille, résolution, qualité, codecs, filigrane, diffusion', () => {
    cy.request(`/admin/subdefs/${sbasId}/`).its('body').then((html) => {
      ['[size]', '[resolution]', '[quality]', '[watermark]', '[acodec]', '_downloadable', '_class', '[devices][]']
        .forEach((k) => expect(html, k).to.contain(k));
    });
  });

  it('propose l’ajout d’une sous-définition', () => {
    cy.request(`/admin/subdefs/${sbasId}/`).its('body').should('contain', 'add_subdef[name]');
  });
});
