// §6.1 Client — §6.2 Barre de menu et compte — §6.3 Développeurs — §6.4 API
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§6.1 Client', () => {
  beforeEach(() => cy.login());

  it('l’ancienne interface Client renvoie vers Prod', () => {
    cy.request({ url: '/client/', followRedirect: false }).its('redirectedToUrl').should('match', /\/prod\//);
  });
});

describe('§6.2 Barre de menu et compte', () => {
  beforeEach(() => cy.login());

  it('barre de menu : applications, notifications, compte, aide, déconnexion', () => {
    cy.visitProd();
    cy.get('#mainMenu').within(() => {
      cy.get('a[href="/prod/"]').should('exist');
      cy.get('a[href="/admin/"]').should('exist');
      cy.get('a[href="/report/dashboard"]').should('exist');
      cy.get('a[href="/lightbox/"]').should('exist');
      cy.get('a[href="/account/"]').should('exist');
      cy.get('a[href^="/login/logout/"]').should('exist');
    });
    cy.get('#notification_box, #notification-title').should('exist');
    cy.get('#help-trigger').should('exist');
  });

  it('onglet Informations : identité, coordonnées, notifications par e-mail, favori FTP', () => {
    cy.request('/account/').its('body').then((html) => {
      ['form_firstname', 'form_lastname', 'form_address', 'form_zip', 'form_company', 'form_phone'].forEach((f) => expect(html, f).to.contain(`name="${f}"`));
      ['form_addressFTP', 'form_loginFTP', 'form_pwdFTP', 'form_destFTP', 'form_passifFTP'].forEach((f) => expect(html, f).to.contain(`name="${f}"`));
      expect(html).to.match(/name="notifications\[eventsmanager_notify_push\]"/);
      expect(html).to.contain('userAccount_token');
    });
  });

  it('enregistre les informations du compte', () => {
    cy.request('/account/').then(({ body }) => {
      const token = body.match(/name="userAccount_token"\s+value="([^"]+)"/)[1];
      const val = (n) => ((body.match(new RegExp(`name="${n}"[^>]*value="([^"]*)"`)) || [])[1] || '');
      cy.request({
        method: 'POST', url: '/account/', form: true, followRedirect: false,
        body: {
          userAccount_token: token,
          form_gender: '0',
          form_firstname: val('form_firstname'),
          form_lastname: val('form_lastname'),
          form_address: val('form_address'),
          form_zip: val('form_zip'),
          form_geonameid: val('form_geonameid'),
          form_function: val('form_function'),
          form_company: val('form_company'),
          form_activity: val('form_activity'),
          form_phone: val('form_phone'),
          form_fax: val('form_fax'),
          form_addressFTP: val('form_addressFTP'),
          form_loginFTP: val('form_loginFTP'),
          form_pwdFTP: '',
          form_destFTP: val('form_destFTP'),
          form_prefixFTPfolder: val('form_prefixFTPfolder'),
        },
      }).its('status').should('be.oneOf', [200, 302]);
    });
  });

  it('changement d’e-mail et de mot de passe : formulaires', () => {
    cy.getOk('/account/reset-email/');
    cy.getOk('/account/reset-password/');
  });

  it('onglet Accès : demande d’accès à des bases ou collections', () => {
    cy.getOk('/account/access/');
  });

  it('onglet Sessions : date, IP, navigateur et fin de session', () => {
    cy.request('/account/security/sessions/').its('body').should('match', /\d+\.\d+\.\d+\.\d+/);
  });

  it('onglet Applications : applications tierces autorisées', () => {
    cy.getOk('/account/security/applications/');
  });

  it('suppression du compte : écran de confirmation', () => {
    cy.request({ url: '/account/delete/process', followRedirect: false, failOnStatusCode: false }).its('status').should('be.lessThan', 500);
  });
});

describe('§6.3 Développeurs et §6.4 API', () => {
  let appId;
  let token;
  const name = uid('cy-app');

  const api = (method, url, body) => cy.request({
    method, url, body, form: method !== 'GET' && !!body, headers: { Authorization: `OAuth ${token}`, Accept: 'application/json' }, failOnStatusCode: false,
  });

  beforeEach(() => cy.login());
  after(() => {
    if (appId) { cy.login(); cy.request({ method: 'DELETE', url: `/developers/application/${appId}/`, headers: JSON_XHR, failOnStatusCode: false }); }
  });

  it('liste les applications et propose d’en créer une', () => {
    cy.request('/developers/applications/').its('body').should('contain', '/developers/application/new/');
    cy.request('/developers/application/new/').its('body').then((html) => {
      ['name', 'description', 'website', 'type', 'callback', 'newApplication_token'].forEach((f) => expect(html, f).to.contain(`name="${f}"`));
    });
  });

  it('crée une application OAuth2', () => {
    cy.formToken('/developers/application/new/', 'newApplication').then((t) => {
      cy.request({
        method: 'POST', url: '/developers/application/', form: true, followRedirect: false,
        body: {
          name, description: 'cypress', website: 'example.com', 'scheme-website': 'https://', type: 'web',
          callback: 'example.com/callback', 'scheme-callback': 'https://', newApplication_token: t,
        },
      }).then((r) => {
        expect(r.status).to.eq(302);
        appId = Number(r.redirectedToUrl.match(/\/developers\/application\/(\d+)\//)[1]);
        cy.request(`/developers/application/${appId}/`).its('body').should('contain', name);
      });
    });
  });

  it('génère un jeton d’accès', () => {
    cy.xhr('POST', `/developers/application/${appId}/access_token/`, {}).then(({ body }) => {
      expect(body.success).to.eq(true);
      expect(body.token).to.be.a('string').and.not.be.empty;
      token = body.token;
    });
  });

  it('définit l’URL de callback et autorise le grant password', () => {
    cy.xhr('POST', `/developers/application/${appId}/callback/`, { callback: 'https://example.com/cb' }).its('body.success').should('eq', true);
    cy.xhr('POST', `/developers/application/${appId}/authorize_grant_password/`, { grant: 1 }).its('body.success').should('eq', true);
  });

  it('webhooks : URL, activation et événements écoutés', () => {
    cy.xhr('POST', `/developers/application/${appId}/webhook/`, { webhook: 'https://example.com/hook' }).its('body.success').should('eq', true);
    cy.xhr('POST', `/developers/application/${appId}/active-webhook`, { action: 'activate' }).its('body.success').should('eq', true);
    ['record.created', 'record.deleted', 'record.metadata_changed', 'user.created'].forEach((event) => {
      cy.xhr('POST', `/developers/application/${appId}/listened-event?event_name=${event}`, { action: 'add' }).its('status').should('eq', 200);
    });
    cy.xhr('POST', `/developers/application/${appId}/active-webhook`, { action: 'deactivate' }).its('body.success').should('eq', true);
  });

  it('API : point d’entrée /api/ (versions disponibles)', () => {
    cy.request({ url: '/api/', headers: { Accept: 'application/json' } }).its('body').then((b) => {
      expect(JSON.stringify(b)).to.contain('/api/v1/');
    });
  });

  it('API : refuse un appel sans jeton', () => {
    cy.request({ url: '/api/v1/me/', headers: { Accept: 'application/json' }, failOnStatusCode: false }).its('status').should('eq', 401);
  });

  it('API v1 : Me', () => {
    api('GET', '/api/v1/me/').then((r) => {
      expect(r.status).to.eq(200);
      expect(JSON.stringify(r.body.response)).to.contain('admin@phraseanet.local');
    });
  });

  it('API v1 : Databox (liste, collections, statuts, métadonnées, CGU)', () => {
    api('GET', '/api/v1/databoxes/list/').then((r) => {
      expect(r.status).to.eq(200);
      const list = Object.values(r.body.response.databoxes);
      expect(list.length, 'databoxes').to.be.greaterThan(0);
      const id = list[0].databox_id;
      ['collections', 'status', 'metadatas', 'termsOfUse'].forEach((p) => api('GET', `/api/v1/databoxes/${id}/${p}/`).its('status').should('eq', 200));
    });
  });

  it('API v1 : Search et Records (fiche, légende, métadonnées, statuts, embed, related)', () => {
    api('POST', '/api/v1/search/', { query: '', search_type: 0 }).then((r) => {
      expect(r.status).to.eq(200);
      const results = r.body.response.results;
      const rec = (results.records || results)[0];
      expect(rec, 'a record').to.exist;
      const base = `/api/v1/records/${rec.databox_id}/${rec.record_id}`;
      [`${base}/`, `${base}/caption/`, `${base}/metadatas/`, `${base}/status/`, `${base}/embed/`, `${base}/related/`]
        .forEach((u) => api('GET', u).its('status').should('eq', 200));
    });
    api('POST', '/api/v1/records/search/', { query: '' }).its('status').should('eq', 200);
  });

  it('API v1 : Baskets (liste, création, renommage, suppression)', () => {
    api('GET', '/api/v1/baskets/list/').its('status').should('eq', 200);
    api('POST', '/api/v1/baskets/add/', { name: uid('cy-api-basket') }).then((r) => {
      expect(r.status).to.eq(200);
      const id = r.body.response.basket.basket_id;
      api('POST', `/api/v1/baskets/${id}/setname/`, { name: 'renamed' }).its('status').should('eq', 200);
      api('GET', `/api/v1/baskets/${id}/content/`).its('status').should('eq', 200);
      api('POST', `/api/v1/baskets/${id}/delete/`).its('status').should('eq', 200);
    });
  });

  it('API v1 : Feeds (liste, contenu agrégé)', () => {
    api('GET', '/api/v1/feeds/list/').its('status').should('eq', 200);
    api('GET', '/api/v1/feeds/content/').its('status').should('eq', 200);
  });

  it('API v1 : Quarantine', () => {
    api('GET', '/api/v1/quarantine/list/').its('status').should('eq', 200);
  });

  it('API v1 : Monitor (application, scheduler, tâches)', () => {
    api('GET', '/api/v1/monitor/phraseanet/').its('status').should('eq', 200);
    api('GET', '/api/v1/monitor/scheduler/').its('status').should('eq', 200);
    api('GET', '/api/v1/monitor/tasks/').its('status').should('eq', 200);
  });

  it('API v2 : Search et Orders', () => {
    api('POST', '/api/v2/search/', { query: '' }).its('status').should('eq', 200);
    api('GET', '/api/v2/orders/').its('status').should('eq', 200);
  });

  it('API v3 : hello, search et records', () => {
    api('GET', '/api/v3/hello/').its('status').should('eq', 200);
    api('POST', '/api/v3/search/', { query: '' }).its('status').should('eq', 200);
    api('GET', '/api/v3/databoxes/subdefs/').its('status').should('eq', 200);
  });

  it('OAuth2 : le jeton peut aussi se passer en paramètre oauth_token', () => {
    cy.request({ url: `/api/v1/me/?oauth_token=${token}`, headers: { Accept: 'application/json' } }).its('status').should('eq', 200);
  });

  it('OAuth2 : la page d’autorisation existe', () => {
    cy.request({ url: '/api/oauthv2/authorize?response_type=code&client_id=unknown&redirect_uri=https://example.com', failOnStatusCode: false })
      .its('status').should('be.lessThan', 500);
  });

  it('supprime l’application', () => {
    cy.request({ method: 'DELETE', url: `/developers/application/${appId}/`, headers: JSON_XHR }).its('body.success').should('eq', true);
    cy.request({ url: `/api/v1/me/?oauth_token=${token}`, headers: { Accept: 'application/json' }, failOnStatusCode: false })
      .its('status').should('eq', 401);
    cy.then(() => { appId = null; });
  });
});
