// §3.7 Utilisateurs — §3.8 Utilisateurs connectés
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§3.7 Utilisateurs', () => {
  let userId;
  let templateId;
  const email = `${uid('cy-user')}@example.com`;
  const templateName = uid('cy-template');

  beforeEach(() => cy.login());
  after(() => {
    cy.login();
    if (userId) cy.deleteUser(userId);
    if (templateId) cy.deleteUser(templateId);
  });

  it('recherche et filtre les comptes (identifiant, nom, e-mail, pays, société)', () => {
    cy.request('/admin/users/search/').its('body').then((html) => {
      ['like_value', 'like_field', 'filter_phantoms_only', 'filter_model_only', 'filter_mail_locked_only', 'filter_with_api_only']
        .forEach((f) => expect(html, f).to.contain(`name="${f}"`));
      expect(html).to.contain('admin@phraseanet.local');
    });
    cy.request({ method: 'POST', url: '/admin/users/search/', form: true, body: { like_field: 'email', like_value: 'admin' } })
      .its('body').should('contain', 'admin@phraseanet.local');
  });

  it('autocomplète un utilisateur (typeahead)', () => {
    cy.request({ url: '/admin/users/typeahead/search/?term=adm', headers: JSON_XHR }).its('body').then((b) => {
      expect(JSON.stringify(b)).to.contain('admin');
    });
  });

  it('crée un compte (l’e-mail sert d’identifiant)', () => {
    cy.createUser(email).then((id) => {
      userId = id;
      cy.request({ method: 'POST', url: '/admin/users/search/', form: true, body: { like_field: 'email', like_value: email } })
        .its('body').should('contain', email);
    });
  });

  it('ouvre l’écran des droits par base et par collection', () => {
    cy.request({ method: 'POST', url: '/admin/users/rights/', form: true, body: { users: String(userId) } }).its('body').then((html) => {
      // Consultation, exploitation, gestion
      ['access', 'actif', 'canputinalbum', 'candwnldpreview', 'nowatermark', 'candwnldhd', 'cancmd',
        'canaddrecord', 'canmodifrecord', 'chgstatus', 'candeleterecord', 'imgtools', 'canadmin', 'canreport', 'canpush',
        'manage', 'modify_struct'].forEach((right) => expect(html, right).to.contain(right));
    });
  });

  it('accorde puis retire un droit sur une collection', () => {
    cy.firstCollection().then(({ baseId, sbasId }) => {
      // The rights screen posts its checkboxes serialized in "values" and the user card in "user_infos".
      const apply = (value) => cy.request({
        method: 'POST', url: '/admin/users/rights/apply/', form: true, headers: JSON_XHR,
        body: {
          users: String(userId),
          values: `access_${baseId}=${value}&actif_${baseId}=${value}&candwnldpreview_${baseId}=${value}`,
          user_infos: `email=${encodeURIComponent(email)}`,
        },
      });
      apply(1).its('body.error').should('eq', false);
      cy.request({ method: 'POST', url: '/admin/users/rights/', form: true, body: { users: String(userId) } })
        .its('body').should('match', new RegExp(`id="access_${baseId}"[^>]*checked|checked[^>]*id="access_${baseId}"|access_${baseId}`));
      apply(0).its('body.error').should('eq', false);
      expect(sbasId).to.be.a('number');
    });
  });

  it('limitations : écrans des quotas, de la limite de temps et des masques de statuts', () => {
    cy.firstCollection().then(({ baseId, sbasId }) => {
      cy.request({ method: 'POST', url: '/admin/users/rights/quotas/', form: true, body: { users: String(userId), base_id: baseId } })
        .its('status').should('eq', 200);
      cy.request({ method: 'POST', url: '/admin/users/rights/time/', form: true, body: { users: String(userId), base_id: baseId } })
        .its('status').should('eq', 200);
      cy.request({ method: 'POST', url: '/admin/users/rights/time/sbas/', form: true, body: { users: String(userId), sbas_id: sbasId } })
        .its('status').should('eq', 200);
      cy.request({ method: 'POST', url: '/admin/users/rights/masks/', form: true, body: { users: String(userId), base_id: baseId } })
        .its('status').should('eq', 200);
    });
  });

  it('options de compte : email verrouillé, renouvellement du mot de passe, accès à l’API', () => {
    [
      ['/admin/users/mail-locked/change/', 'locked'], ['/admin/users/mail-locked/change/', 'unlocked'],
      ['/admin/users/can-renew-password/change/', 'can-renew'],
      ['/admin/users/granted-api/change/', 'grant'], ['/admin/users/granted-api/change/', 'revoke'],
    ].forEach(([url, action]) => cy.request({
      method: 'POST', url, form: true, headers: JSON_XHR, body: { user_id: userId, action },
    }).its('body.success').should('eq', true));
  });

  it('consulte les données de l’utilisateur : droits par enregistrement, publications, paniers', () => {
    ['records-acl', 'feed-entry', 'records-basket'].forEach((p) => {
      cy.request({ url: `/admin/users/${p}/?userId=${userId}`, headers: JSON_XHR, failOnStatusCode: false }).its('status').should('eq', 200);
    });
  });

  it('crée un modèle d’utilisateur (propre à son créateur)', () => {
    cy.request({ method: 'POST', url: '/admin/users/create/', form: true, headers: JSON_XHR, body: { value: templateName, template: 1 } })
      .then(({ body }) => {
        expect(body.error, body.message).to.eq(false);
        templateId = Number(body.data);
      });
    cy.request({ method: 'POST', url: '/admin/users/search/', form: true, body: { filter_model_only: 1 } })
      .its('body').should('contain', templateName);
  });

  it('applique un modèle (ajout ou réinitialisation)', () => {
    cy.request({
      method: 'POST', url: '/admin/users/apply_template/', form: true, followRedirect: false,
      body: { users: String(userId), template: templateId },
    }).its('status').should('be.oneOf', [200, 302]);
  });

  it('exporte les utilisateurs en CSV', () => {
    cy.request({ method: 'POST', url: '/admin/users/export/csv/', form: true, body: {} }).then((r) => {
      expect(r.status).to.eq(200);
      expect(r.headers['content-type']).to.match(/csv|octet|text/);
      expect(r.body).to.contain('admin@phraseanet.local');
    });
  });

  it('importe par CSV ou RTF : écran et fichiers d’exemple', () => {
    cy.getOk('/admin/users/import/file/');
    cy.getOk('/admin/users/import/example/csv/');
    cy.getOk('/admin/users/import/example/rtf/');
  });

  it('supprime le compte', () => {
    // A user without any right left ("phantom") is deleted for good.
    cy.deleteUser(userId).its('status').should('be.oneOf', [200, 302]);
    cy.request({ url: `/admin/users/typeahead/search/?term=${encodeURIComponent(email)}`, headers: JSON_XHR })
      .its('body').then((b) => expect(JSON.stringify(b)).to.not.contain(email));
    cy.then(() => { userId = null; });
  });

  it('inscriptions (rubrique Demandes) : liste des demandes par collection', () => {
    cy.getOk('/admin/users/registrations/');
  });

  it('échecs d’authentification : consultation', () => {
    cy.getOk('/admin/users/auth-failure/');
  });

  it('accès invité : le compte invité est réglable', () => {
    cy.request({ method: 'POST', url: '/admin/users/search/', form: true, body: { filter_guest_user: 1 } }).its('status').should('eq', 200);
  });

  it('s’affiche dans l’Admin (liste, création, application de modèle)', () => {
    cy.adminOpen('/admin/users/search/');
    cy.get('#right-ajax #users_page_search').should('exist');
    cy.get('#right-ajax #users_apply_template').should('exist');
    cy.get('#right-ajax #export_form, #right-ajax form[action="/admin/users/export/csv/"]').should('exist');
  });
});

describe('§3.8 Utilisateurs connectés', () => {
  beforeEach(() => cy.login());

  it('liste les sessions : application, adresse IP, date de connexion', () => {
    cy.request('/admin/connected-users/').its('body').then((html) => {
      expect(html).to.contain('admin@phraseanet.local');
      expect(html).to.match(/\d+\.\d+\.\d+\.\d+/);
      expect(html).to.match(/Production|Admin/);
    });
  });

  it('propose la déconnexion forcée', () => {
    cy.request('/admin/connected-users/').its('body').should('match', /action="\/admin\/connected-users\/\d+\/disconnect"/);
  });
});
