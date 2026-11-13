# Phraseanet UI smoke test

A real-browser non-regression harness. It logs in and walks the main authenticated
flows (production load, search, record preview) plus the server-rendered admin
pages, asserting **no JS console errors, no uncaught page errors and no HTTP 5xx**.

It was written during the PHP 8.5 migration to catch the class of regressions unit
tests miss: broken Twig templates, 500s on admin forms (e.g. the search-engine
`ElasticsearchOptions` form bug), dead assets, and JavaScript blow-ups.

## Requirements

- Node ≥ 18
- Google Chrome installed (the harness drives the system Chrome, no browser download).
  Override the binary with `CHROME=/path/to/chrome`.

## Install & run

```bash
cd tests/ui-smoke
PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install
BASE=http://localhost:8082 PUSER=admin@phraseanet.local PPASS='<password>' npm run smoke
```

- `HEADED=1` opens a visible window so you can watch the run.
- Exit code `0` = all green, `1` = at least one failure (CI-friendly).

## What it checks

| Step | Assertion |
|------|-----------|
| login | lands outside `/login/` |
| prod_load / prod_search / record_preview | pages work, search returns results, no JS errors |
| admin_* (home, dashboard, databoxes, fields, subdefs, search-engine, workers, users, setup) | HTTP 200, JS-clean |
| thesaurus, account | HTTP 200 |

`/embed/` 4xx responses are intentionally ignored (embed returns a clean 404 on
non-routable urls by design).

## Extending

Add a `run('name', async () => { ... })` block in `smoke.mjs`. Return
`{ status, note }`; any console/page/HTTP error accumulated during the step is
attributed to it automatically.
