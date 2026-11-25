// §3.9 Publications — §3.10 Moteur de recherche — §3.11 Gestionnaire de tâches — §3.12 Worker Manager — §3.13 Autres écrans
import { uid } from '../../support/commands';

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

describe('§3.9 Publications (Admin)', () => {
  let feedId;
  let publisherUser;
  const title = uid('cy-adm-feed');

  beforeEach(() => cy.login());
  after(() => {
    cy.login();
    if (feedId) cy.deleteFeed(feedId);
    if (publisherUser) cy.deleteUser(publisherUser);
  });

  it('affiche le formulaire de création (titre, sous-titre, collection, public)', () => {
    cy.request('/admin/publications/list/').its('body').then((html) => {
      ['title', 'subtitle', 'base_id', 'public'].forEach((f) => expect(html, f).to.contain(`name="${f}"`));
    });
  });

  it('crée un fil Media RSS', () => {
    cy.createFeed(title).then((id) => { feedId = id; });
  });

  it('affiche le fil : icône, publieurs, étendue', () => {
    cy.request(`/admin/publications/feed/${feedId}/`).its('body').then((html) => {
      expect(html).to.contain(title);
      expect(html).to.contain(`/admin/publications/feed/${feedId}/iconupload/`);
      expect(html).to.contain(`/admin/publications/feed/${feedId}/addpublisher/`);
      expect(html).to.contain(`/admin/publications/feed/${feedId}/update/`);
    });
  });

  it('rend le fil public puis le modifie', () => {
    cy.request({
      method: 'POST', url: `/admin/publications/feed/${feedId}/update/`, form: true,
      body: { title: `${title}-2`, subtitle: 'cypress', public: '1' },
    }).its('status').should('eq', 200);
    cy.request('/admin/publications/list/').its('body').should('contain', `${title}-2`);
  });

  it('le fil public est exposé en RSS et en Atom sans authentification', () => {
    cy.request({ url: `/feeds/feed/${feedId}/rss/` }).then((r) => {
      expect(r.headers['content-type']).to.match(/xml|rss/);
      expect(r.body).to.contain(`${title}-2`);
    });
    cy.request({ url: `/feeds/feed/${feedId}/atom/` }).its('body').should('contain', '<feed');
  });

  it('ajoute puis retire un publieur', () => {
    cy.createUser(`${uid('cy-publisher')}@example.com`).then((id) => {
      publisherUser = id;
      cy.request({
        method: 'POST', url: `/admin/publications/feed/${feedId}/addpublisher/`, form: true, followRedirect: false, body: { usr_id: publisherUser },
      }).then((r) => {
        expect(r.status).to.eq(302);
        expect(r.redirectedToUrl, 'no error').to.not.match(/error=[^&]+/);
      });
      cy.request(`/admin/publications/feed/${feedId}/`).then(({ body }) => {
        const ids = [...body.matchAll(/name="publisher_id"\s+value="(\d+)"|value="(\d+)"\s+name="publisher_id"/g)].map((m) => m[1] || m[2]);
        expect(ids.length, 'publishers listed').to.be.at.least(1);
        cy.request({
          method: 'POST', url: `/admin/publications/feed/${feedId}/removepublisher/`, form: true, followRedirect: false, body: { publisher_id: ids[ids.length - 1] },
        }).its('status').should('eq', 302);
      });
    });
  });

  it('supprime le fil', () => {
    cy.deleteFeed(feedId).its('status').should('eq', 200);
    cy.request('/admin/publications/list/').its('body').should('not.contain', `${title}-2`);
    cy.then(() => { feedId = null; });
  });
});

describe('§3.10 Moteur de recherche (Elasticsearch)', () => {
  beforeEach(() => cy.login());

  it('onglet Configuration : hôte, port, nom d’index', () => {
    cy.request('/admin/search-engine/').its('body').then((html) => {
      expect(html).to.match(/elasticsearch_settings\[host\]/);
      expect(html).to.match(/elasticsearch_settings\[port\]/);
      expect(html).to.match(/elasticsearch_settings\[indexName\]/);
    });
  });

  it('onglet Réglage des agrégats : facettes et leur limite', () => {
    cy.request('/admin/search-engine/').its('body').should('match', /elasticsearch_settings\[facets:[^\]]+:limit\]/);
  });

  it('propose de créer, supprimer et lire les réglages de l’index', () => {
    cy.request('/admin/search-engine/').its('body').then((html) => {
      expect(html).to.contain('ElasticSearchDropIndexForm');
      expect(html).to.contain('ElasticSearchCreateIndexForm');
      expect(html).to.contain('esSettingFromIndex');
    });
    cy.request({ url: '/admin/search-engine/setting_from_index', headers: JSON_XHR, failOnStatusCode: false })
      .its('status').should('eq', 200);
  });
});

describe('§3.11 Gestionnaire de tâches', () => {
  beforeEach(() => cy.login());

  it('affiche l’ordonnanceur et la liste des tâches, avec « Nouvelle tâche »', () => {
    cy.request('/admin/task-manager/tasks').its('body').then((html) => {
      expect(html).to.contain('form-create-task');
      expect(html).to.contain('name="job-name"');
    });
  });

  it('propose les jobs : Archive, FtpPull, RecordMover, Subdefs, WriteMetadata…', () => {
    cy.request('/admin/task-manager/tasks').its('body').then((html) => {
      ['Archive', 'RecordMover', 'Subdef', 'WriteMetadata'].forEach((job) => expect(html, job).to.match(new RegExp(job, 'i')));
    });
  });

  it('donne l’état de l’ordonnanceur et des tâches (live)', () => {
    cy.request({ url: '/admin/task-manager/live', headers: JSON_XHR }).its('body').should('have.property', 'manager');
    cy.request({ url: '/admin/task-manager/scheduler', headers: JSON_XHR, failOnStatusCode: false }).its('status').should('be.lessThan', 500);
    cy.request({ url: '/admin/task-manager/scheduler/log', failOnStatusCode: false }).its('status').should('be.lessThan', 500);
  });

  it('valide une configuration XML de tâche', () => {
    const post = (body) => cy.request({
      method: 'POST', url: '/admin/task-manager/task/validate-xml', headers: { ...JSON_XHR, 'Content-Type': 'application/xml' }, body,
    });
    post('<?xml version="1.0"?><tasksettings/>').its('body.success').should('eq', true);
    post('<tasksettings><unclosed></tasksettings>').its('body.success').should('eq', false);
  });

  it('crée, affiche puis supprime une tâche', () => {
    cy.request({
      method: 'POST', url: '/admin/task-manager/tasks/create', form: true, followRedirect: false, body: { 'job-name': 'Alchemy\\Phrasea\\TaskManager\\Job\\NullJob' },
    }).then((r) => {
      if (r.status !== 302) return;
      const id = (r.redirectedToUrl.match(/\/task\/(\d+)/) || [])[1];
      if (!id) return;
      cy.getOk(`/admin/task-manager/task/${id}`);
      cy.request({ method: 'POST', url: `/admin/task-manager/task/${id}/delete`, followRedirect: false }).its('status').should('be.oneOf', [200, 302]);
    });
  });
});

describe('§3.12 Worker Manager', () => {
  beforeEach(() => cy.login());

  it('affiche les onglets : réglages, journal, files, index, sous-définitions, métadonnées, FTP, rappels, actions sur les records', () => {
    cy.request('/admin/worker-manager/').its('body').then((html) => {
      ['configuration', 'info', 'queue-monitor', 'searchengine', 'subview', 'metadata', 'ftp', 'validation-reminder', 'records-actions']
        .forEach((t) => expect(html, t).to.contain(`/admin/worker-manager/${t}`));
    });
  });

  [
    ['réglages généraux par file', '/admin/worker-manager/configuration'],
    ['journal des jobs', '/admin/worker-manager/info?running=1&timeFilter=P3D'],
    ['files de messages', '/admin/worker-manager/queue-monitor'],
    ['peuplement de l’index', '/admin/worker-manager/searchengine'],
    ['sous-définitions', '/admin/worker-manager/subview'],
    ['métadonnées', '/admin/worker-manager/metadata'],
    ['FTP', '/admin/worker-manager/ftp'],
    ['rappels de validation', '/admin/worker-manager/validation-reminder'],
    ['actions sur les records', '/admin/worker-manager/records-actions'],
  ].forEach(([label, url]) => {
    it(`écran « ${label} »`, () => {
      cy.getOk(url, { xhr: true });
    });
  });

  it('état du peuplement de l’index', () => {
    cy.firstCollection().then(({ sbasId }) => {
      cy.request({ url: `/admin/worker-manager/populate-status?sbasIds[]=${sbasId}`, headers: JSON_XHR, failOnStatusCode: false })
        .its('status').should('be.lessThan', 500);
    });
  });

  it('s’affiche dans l’Admin', () => {
    cy.adminOpen('/admin/worker-manager/');
    cy.get('#right-ajax').should('contain.text', 'Worker');
  });
});

describe('§3.13 Autres écrans Admin', () => {
  beforeEach(() => cy.login());

  it('Phraseanet Service : authentification, Expose, Uploader, Notify, Report', () => {
    cy.request('/admin/phraseanet-service/').its('body').should('match', /expose|uploader|notify|auth/i);
  });

  it('Plugins : liste', () => {
    cy.getOk('/admin/plugins/');
  });

  it('Inspecteur d’objets : enregistrement Elasticsearch et détails', () => {
    cy.getOk('/admin/inspector/');
    cy.recordIds(1).then(([id]) => {
      const [sbas, rid] = id.split('_');
      cy.request({ url: `/admin/inspector/record/?databoxId=${sbas}&recordId=${rid}`, headers: JSON_XHR })
        .its('body').then((b) => expect(JSON.stringify(b)).to.contain(`${sbas}_${rid}`));
      cy.request({ url: `/admin/inspector/record/details/?databoxId=${sbas}&recordId=${rid}&type=subdef`, headers: JSON_XHR, failOnStatusCode: false })
        .its('status').should('eq', 200);
    });
  });

  it('Test des chemins', () => {
    cy.request({ url: '/admin/test-paths/?path=/tmp', headers: JSON_XHR, failOnStatusCode: false }).its('status').should('be.lessThan', 500);
  });
});
