// §3.1 Tableau de bord — §3.2 Paramétrage général

describe('§3 Admin — navigation', () => {
  beforeEach(() => cy.login());

  it('affiche l’arbre de navigation avec toutes les rubriques', () => {
    cy.visit('/admin/');
    cy.get('#FNDR').within(() => {
      [
        '/admin/dashboard/', '/admin/setup/', '/admin/connected-users/', '/admin/users/search/',
        '/admin/users/registrations/', '/admin/users/auth-failure/', '/admin/publications/list/',
        '/admin/task-manager/tasks', '/admin/worker-manager/', '/admin/search-engine/', '/admin/databoxes/',
        '/admin/plugins/', '/admin/inspector/', '/admin/phraseanet-service/',
      ].forEach((href) => cy.get(`a[href="${href}"]`).should('exist'));
    });
  });

  it('charge une rubrique dans le panneau de droite', () => {
    cy.adminOpen('/admin/dashboard/');
    cy.get('#right-ajax').should('contain.text', 'Cache');
  });
});

describe('§3.1 Tableau de bord', () => {
  beforeEach(() => cy.login());

  it('propose de vider le cache et les sessions', () => {
    cy.request('/admin/dashboard/').its('body').then((html) => {
      expect(html).to.contain('action="/admin/dashboard/flush-cache/"');
      expect(html).to.contain('action="/admin/dashboard/flush-session/"');
    });
  });

  it('vide le cache', () => {
    cy.request({ method: 'POST', url: '/admin/dashboard/flush-cache/', followRedirect: false })
      .its('status').should('be.oneOf', [200, 302]);
  });

  it('propose l’envoi d’un mail de test', () => {
    cy.request('/admin/dashboard/').its('body').should('contain', 'action="/admin/dashboard/send-mail-test/"');
  });

  it('gère les administrateurs (ajout, réinitialisation des droits)', () => {
    cy.request('/admin/dashboard/').its('body').then((html) => {
      expect(html).to.contain('action="/admin/dashboard/add-admins/"');
      expect(html).to.contain('action="/admin/dashboard/reset-admin-rights/"');
      expect(html).to.match(/name="admins\[\]"/);
    });
  });

  it('affiche l’état de la configuration (fichiers, exécutables, PHP, cache, moteur)', () => {
    cy.request('/admin/dashboard/').its('body').then((html) => {
      ['Binaries', 'Cache Server', 'Filesystem', 'PHP', 'Search Engine', 'System'].forEach((section) => {
        expect(html).to.contain(section);
      });
    });
  });
});

describe('§3.2 Paramétrage général', () => {
  beforeEach(() => cy.login());

  it('affiche toutes les sections du formulaire', () => {
    cy.request('/admin/setup/').its('body').then((html) => {
      [
        'general', 'modules', 'actions', 'ftp', 'registration', 'maintenance',
        'api-clients', 'webservices', 'executables', 'searchengine', 'email', 'custom-links',
      ].forEach((section) => expect(html, section).to.match(new RegExp(`name="${section}\\[`)));
      expect(html).to.contain('id="setupForm"');
    });
  });

  it('General : titre, mots-clés, description, analytics, indexation, page d’accueil, TTL, logo', () => {
    cy.request('/admin/setup/').its('body').then((html) => {
      ['title', 'keywords', 'description', 'analytics', 'allow-indexation', 'home-presentation-mode', 'default-subdef-url-ttl', 'personalize-logo-choice']
        .forEach((f) => expect(html, f).to.contain(`name="general[${f}]`));
    });
  });

  it('Modules : thésaurus, reportages, substitutions, rapport anonyme', () => {
    cy.request('/admin/setup/').its('body').then((html) => {
      ['thesaurus', 'stories', 'doc-substitution', 'thumb-substitution', 'anonymous-report']
        .forEach((f) => expect(html, f).to.contain(`name="modules[${f}]"`));
    });
  });

  it('Actions : taille max de téléchargement, validation, export, outils sociaux, Push', () => {
    cy.request('/admin/setup/').its('body').then((html) => {
      ['download-max-size', 'validation-expiration-days', 'auth-required-for-export', 'tou-validation-required-for-export',
        'social-tools', 'enable-push-authentication', 'enable-feed-notification', 'download-link-validity']
        .forEach((f) => expect(html, f).to.contain(`name="actions[${f}]"`));
    });
  });

  it('Email : émetteur, préfixe, SMTP', () => {
    cy.request('/admin/setup/').its('body').then((html) => {
      ['emitter-email', 'prefix', 'smtp-enabled', 'smtp-host', 'smtp-port', 'smtp-secure-mode', 'smtp-auth-enabled']
        .forEach((f) => expect(html, f).to.contain(`name="email[${f}]"`));
    });
  });

  it('s’affiche dans l’Admin et se protège par un jeton', () => {
    cy.adminOpen('/admin/setup/');
    cy.get('#right-ajax #setupForm').should('exist');
    cy.get('#right-ajax #setupForm input[name$="[_token]"], #right-ajax #setupForm input[name="_token"]').should('exist');
  });
});
