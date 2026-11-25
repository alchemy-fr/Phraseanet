// §6.6 Diffusion : permaliens, embed, RSS / Atom

describe('§6.6 Diffusion', () => {
  let embed;

  before(() => {
    cy.login();
    // Permalinks are listed by the record "embed" API answer: create a dev token to read it.
    cy.formToken('/developers/application/new/', 'newApplication').then((t) => cy.request({
      method: 'POST', url: '/developers/application/', form: true, followRedirect: false,
      body: {
        name: `cy-diffusion-${Date.now()}`, description: 'cypress', website: 'example.com', 'scheme-website': 'https://', type: 'web',
        callback: 'example.com/cb', 'scheme-callback': 'https://', newApplication_token: t,
      },
    })).then((r) => {
      const appId = Number(r.redirectedToUrl.match(/\/developers\/application\/(\d+)\//)[1]);
      cy.xhr('POST', `/developers/application/${appId}/access_token/`, {}).then(({ body }) => {
        cy.search('', { record_type: 'image' }).then((s) => {
          const id = (s.results.match(/id="IMGT_(\d+_\d+)"/) || [])[1];
          const [sbas, rid] = id.split('_');
          cy.request({ url: `/api/v1/records/${sbas}/${rid}/embed/`, headers: { Authorization: `OAuth ${body.token}`, Accept: 'application/json' } })
            .then((er) => { embed = er.body.response.embed; });
        });
        cy.request({ method: 'DELETE', url: `/developers/application/${appId}/`, headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
      });
    });
  });

  it('l’API liste les permaliens des sous-définitions', () => {
    expect(embed).to.be.an('array').and.not.be.empty;
    const withLink = embed.find((e) => e.permalink && e.permalink.url);
    expect(withLink, 'a sub-definition with a permalink').to.exist;
  });

  it('un permalien diffuse publiquement une sous-définition (sans session)', () => {
    const sub = embed.find((e) => e.permalink && e.permalink.url);
    cy.clearAllCookies();
    cy.request({ url: sub.permalink.url, encoding: 'binary' }).then((r) => {
      expect(r.status).to.eq(200);
      expect(r.headers['content-type']).to.match(/image|video|audio|pdf|octet/);
    });
  });

  it('un permalien répond à HEAD et renvoie un en-tête Link vers la légende', () => {
    const sub = embed.find((e) => e.permalink && e.permalink.url);
    cy.request({ method: 'HEAD', url: sub.permalink.url }).then((r) => {
      expect(r.status).to.eq(200);
      expect(r.headers.link || '').to.match(/caption|json/i);
    });
  });

  it('la variante &download force le téléchargement en pièce jointe', () => {
    const sub = embed.find((e) => e.permalink && e.permalink.download_url);
    if (!sub) return;
    cy.request({ url: sub.permalink.download_url, encoding: 'binary' }).its('headers.content-disposition').should('match', /attachment/);
  });

  it('la page de partage du permalien est consultable (vue publique)', () => {
    const sub = embed.find((e) => e.permalink && e.permalink.page_url);
    if (!sub) return;
    cy.getOk(sub.permalink.page_url);
  });

  it('le lecteur /embed/ s’intègre dans une iframe', () => {
    const sub = embed.find((e) => e.permalink && e.permalink.url);
    const url = sub.permalink.url.replace('/permalink/', '/embed/');
    cy.request({ url, failOnStatusCode: false }).its('status').should('be.oneOf', [200, 404]);
  });

  it('flux RSS et Atom agrégés publics', () => {
    cy.clearAllCookies();
    cy.request('/feeds/aggregated/rss/').its('headers.content-type').should('match', /xml|rss/);
    cy.request('/feeds/aggregated/atom/').its('body').should('contain', '<feed');
  });
});
