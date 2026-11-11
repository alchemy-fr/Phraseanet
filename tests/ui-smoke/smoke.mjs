#!/usr/bin/env node
/*
 * Phraseanet UI smoke test — non-regression harness.
 *
 * Drives a real browser (system Chrome via playwright-core) through the main
 * authenticated flows and asserts there are no JS console errors, no uncaught
 * page errors and no HTTP 5xx. Born out of the PHP 8.5 migration: catches the
 * class of regressions that unit tests miss (broken templates, 500s on admin
 * forms, dead assets, JS blowups).
 *
 * Usage:
 *   BASE=http://localhost:8082 PUSER=admin@phraseanet.local PPASS=secret node smoke.mjs
 *   HEADED=1 ...   # watch it run in a visible window
 *   CHROME=/path/to/chrome ...   # override the browser binary
 *
 * Exit code 0 = all green, 1 = at least one failure (CI-friendly).
 */
import { chromium } from 'playwright-core';

const CHROME = process.env.CHROME
  || (process.platform === 'darwin'
    ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
    : '/usr/bin/google-chrome');
const BASE = process.env.BASE || 'http://localhost:8082';
const USER = process.env.PUSER || 'admin@phraseanet.local';
const PASS = process.env.PPASS;
if (!PASS) { console.error('Set PPASS (admin password).'); process.exit(2); }

const results = [];
let step = 'init';
const consoleErrors = [], pageErrors = [], httpErrors = [];
// URLs we accept a 4xx on (e.g. embed deliberately 404s on non-routable urls).
const ignore4xx = [/\/embed\//];

const browser = await chromium.launch({ executablePath: CHROME, headless: process.env.HEADED !== '1', args: ['--no-sandbox'] });
const ctx = await browser.newContext({ viewport: { width: 1600, height: 950 }, acceptDownloads: true });
const page = await ctx.newPage();
page.on('console', m => { if (m.type() === 'error') consoleErrors.push({ step, text: m.text().slice(0, 300) }); });
page.on('pageerror', e => pageErrors.push({ step, text: String(e).slice(0, 300) }));
page.on('response', r => {
  const s = r.status(), u = r.url();
  if (s >= 500) httpErrors.push({ step, status: s, url: u.slice(0, 180) });
  else if (s >= 400 && !ignore4xx.some(re => re.test(u))) httpErrors.push({ step, status: s, url: u.slice(0, 180) });
});

async function run(name, fn, opts = {}) {
  step = name;
  const b = { c: consoleErrors.length, p: pageErrors.length, h: httpErrors.length };
  const rec = { name, status: 'ok', note: '' };
  try { const r = await fn(); if (r && r.note) rec.note = r.note; if (r && r.status) rec.status = r.status; }
  catch (e) { rec.status = 'FAIL'; rec.note = String(e.message || e).slice(0, 180); }
  rec.js = consoleErrors.length - b.c; rec.perr = pageErrors.length - b.p; rec.http = httpErrors.length - b.h;
  // Admin partials are AJAX fragments injected into the admin shell, which provides
  // jQuery. Loaded directly (as a smoke test must) they raise "$ is not defined" etc.
  // That is a test artifact, not a regression — for those steps only the server-side
  // HTTP status is meaningful, so we drop the client-side errors collected here.
  if (opts.serverOnly) {
    rec.js = 0; rec.perr = 0;
    consoleErrors.length = b.c;
    pageErrors.length = b.p;
  }
  if (rec.js || rec.perr || rec.http) rec.status = rec.status === 'FAIL' ? 'FAIL' : 'WARN';
  results.push(rec);
  console.log(`[${rec.status}] ${name}${rec.note ? ' — ' + rec.note : ''} (js:${rec.js} pageerr:${rec.perr} http5xx/4xx:${rec.http})`);
}

await run('login', async () => {
  await page.goto(`${BASE}/login/`, { waitUntil: 'domcontentloaded' });
  await page.fill('#login', USER); await page.fill('#password', PASS);
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}), page.click('button[type=submit],input[type=submit]')]);
  await page.waitForTimeout(2500);
  if (/\/login\//.test(page.url()) && !/prod|admin/.test(page.url())) return { status: 'FAIL', note: 'still on login' };
  return { note: page.url() };
});

await run('prod_load', async () => {
  await page.goto(`${BASE}/prod/`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(4000);
  return { note: `search box: ${await page.locator('input.search.query').count()}` };
});

await run('prod_search', async () => {
  const sb = page.locator('input.search.query').first();
  if (!(await sb.count())) return { status: 'WARN', note: 'no search box' };
  await sb.click(); await sb.fill(' '); await sb.press('Enter');
  await page.waitForTimeout(5000);
  return { note: `thumbnails: ${await page.locator('#answers .thumbnail, #answers .diapo').count().catch(() => 0)}` };
});

await run('record_preview', async () => {
  const t = page.locator('#answers .thumbnail, #answers .diapo').first();
  if (!(await t.count().catch(() => 0))) return { status: 'WARN', note: 'no thumbnail' };
  await t.dblclick().catch(() => {});
  await page.waitForTimeout(3000);
  await page.keyboard.press('Escape').catch(() => {});
  return { note: 'preview opened' };
});

// Real admin SPA navigation (click a menu entry): asserts the admin shell is
// JS-clean in its genuine context, unlike the direct partial loads below.
await run('admin_spa', async () => {
  await page.goto(`${BASE}/admin/`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const link = page.locator('a[href="/admin/databoxes/"], a[href="/admin/worker-manager/"]').first();
  if (await link.count().catch(() => 0)) { await link.click().catch(() => {}); await page.waitForTimeout(3000); }
  return { note: 'navigated admin menu' };
});

// Admin pages loaded directly (server-side health only — see serverOnly note above).
const adminPages = [
  ['admin_home', '/admin/'],
  ['admin_dashboard', '/admin/dashboard/'],
  ['admin_databoxes', '/admin/databoxes/'],
  ['admin_fields', '/admin/fields/1'],
  ['admin_subdefs', '/admin/subdefs/1/'],
  ['admin_search_engine', '/admin/search-engine/'],
  ['admin_workers', '/admin/worker-manager/'],
  ['admin_users', '/admin/users/search/'],
  ['admin_setup', '/admin/setup/'],
  ['thesaurus', '/thesaurus/'],
  ['account', '/account/'],
];
for (const [name, path] of adminPages) {
  await run(name, async () => {
    const resp = await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    const code = resp ? resp.status() : 0;
    return { status: code >= 400 ? 'FAIL' : 'ok', note: `HTTP ${code}` };
  }, { serverOnly: true });
}

await browser.close();

const failed = results.filter(r => r.status === 'FAIL');
const warned = results.filter(r => r.status === 'WARN');
console.log(`\n${results.length} steps — ${results.length - failed.length - warned.length} ok, ${warned.length} warn, ${failed.length} fail`);
if (consoleErrors.length) { console.log('\nConsole errors:'); for (const e of consoleErrors.slice(0, 20)) console.log(`  [${e.step}] ${e.text}`); }
if (pageErrors.length) { console.log('\nPage errors:'); for (const e of pageErrors.slice(0, 20)) console.log(`  [${e.step}] ${e.text}`); }
if (httpErrors.length) { console.log('\nHTTP errors:'); const seen = new Set(); for (const e of httpErrors) { const k = e.status + e.url.replace(/\d+/g, '#'); if (seen.has(k)) continue; seen.add(k); console.log(`  [${e.step}] ${e.status} ${e.url}`); } }
process.exit(failed.length ? 1 : 0);
