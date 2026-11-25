// Domain helpers: create / delete the objects the tests work on, through the same
// endpoints (and CSRF tokens) the Phraseanet UI uses.

const JSON_XHR = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };

const post = (url, body, extra = {}) => cy.request({
  method: 'POST', url, body, form: true, headers: JSON_XHR, ...extra,
});

/** First databox / collection of the instance, read from the Admin tree. */
Cypress.Commands.add('firstCollection', () => cy.request('/admin/').then(({ body }) => {
  const sbas = body.match(/href="\/admin\/databox\/(\d+)\/"/);
  const bas = body.match(/href="\/admin\/collection\/(\d+)\/"/);
  expect(sbas, 'a databox').to.not.equal(null);
  expect(bas, 'a collection').to.not.equal(null);
  return { sbasId: Number(sbas[1]), baseId: Number(bas[1]) };
}));

// ---------------------------------------------------------------- baskets

/** Creates a basket (optionally holding records "sbas_record") and yields its id. */
Cypress.Commands.add('createBasket', (name, records = []) => cy.formToken('/prod/baskets/create/', 'prodCreateBasket', { xhr: true })
  .then((token) => post('/prod/baskets/', {
    name, description: `${name} (cypress)`, lst: records.join(';'), prodCreateBasket_token: token,
  })).then(({ body }) => {
    expect(body.success, body.message).to.eq(true);
    return body.basket.id;
  }));

Cypress.Commands.add('deleteBasket', (id) => post(`/prod/baskets/${id}/delete/`, {}, { failOnStatusCode: false }));

/** Lists the baskets of the WorkZone as [{ id, name }]. */
Cypress.Commands.add('workzoneBaskets', () => cy.request({ url: '/prod/WorkZone/', headers: JSON_XHR }).then(({ body }) => {
  const out = [];
  const re = /id="SSTT_(\d+)"[\s\S]*?class="basketTips"[^>]*>\s*([^<]*)/g;
  let m;
  // eslint-disable-next-line no-cond-assign
  while ((m = re.exec(body))) out.push({ id: Number(m[1]), name: m[2].trim() });
  return out;
}));

// ---------------------------------------------------------------- stories

/** Creates a story in a collection and yields { sbasId, recordId }. */
Cypress.Commands.add('createStory', (title, baseId, records = []) => cy.formToken('/prod/story/create/', 'prodCreateStory', { xhr: true })
  .then((token) => post('/prod/story/', {
    base_id: baseId, 'name[]': title, lst: records.join(';'), prodCreateStory_token: token,
  })).then(({ body }) => {
    expect(body.success, body.message).to.eq(true);
    return { sbasId: body.story.sbas_id, recordId: body.story.record_id };
  }));

/** Deletes records (a story included). Phraseanet moves them to _TRASH_ when it exists. */
Cypress.Commands.add('deleteRecords', (ids, { twice = true } = {}) => {
  const once = () => cy.formToken('/prod/records/delete/what/', 'prodDeleteRecord', { method: 'POST', body: { lst: ids.join(';') }, xhr: true })
    .then((token) => post('/prod/records/delete/', {
      lst: ids.join(';'), prodDeleteRecord_token: token,
    }, { failOnStatusCode: false }));
  once();
  // A record already in the trash collection is deleted for good the second time.
  if (twice) once();
});

// ---------------------------------------------------------------- user lists

Cypress.Commands.add('createUserList', (name) => post('/prod/lists/list/', { name }).then(({ body }) => {
  expect(body.success, body.message).to.eq(true);
  return body.list_id;
}));

Cypress.Commands.add('deleteUserList', (id) => post(`/prod/lists/list/${id}/delete/`, {}, { failOnStatusCode: false }));

// ---------------------------------------------------------------- feeds (Admin)

/** Creates a publication feed in Admin and yields its id. */
Cypress.Commands.add('createFeed', (title, { isPublic = false } = {}) => cy.request({
  method: 'POST', url: '/admin/publications/create/', form: true, body: { title, subtitle: `${title} subtitle`, public: isPublic ? 1 : 0 },
}).then(() => cy.request('/admin/publications/list/')).then(({ body }) => {
  const re = new RegExp(`href="/admin/publications/feed/(\\d+)/"[^>]*>\\s*${title.replace(/[-]/g, '\\-')}`);
  const m = body.match(re);
  expect(m, `feed ${title} listed`).to.not.equal(null);
  return Number(m[1]);
}));

Cypress.Commands.add('deleteFeed', (id) => cy.request({
  method: 'POST', url: `/admin/publications/feed/${id}/delete/`, failOnStatusCode: false,
}));

// ---------------------------------------------------------------- users (Admin)

/** Creates a user from Admin > Users and yields its id. */
Cypress.Commands.add('createUser', (email) => cy.request({
  method: 'POST', url: '/admin/users/create/', form: true, body: { value: email, template: '' }, headers: JSON_XHR,
}).then(({ body }) => {
  expect(body.error, body.message).to.eq(false);
  return Number(body.data);
}));

Cypress.Commands.add('deleteUser', (id) => cy.request({
  method: 'POST', url: '/admin/users/delete/', form: true, body: { users: String(id) }, headers: JSON_XHR, failOnStatusCode: false,
}));
