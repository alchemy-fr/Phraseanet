#!/bin/sh
# Runs Cypress on the host (outside Docker) with the credentials of the local stack.
#
#   ./scripts/local.sh open          # interactive window: pick a spec, watch the browser
#   ./scripts/local.sh run [args]    # headless run, e.g. --spec 'cypress/e2e/01-prod/*.cy.js'
#
# The admin login, password and port are read from the repository .env unless they are
# already set (CYPRESS_ADMIN_LOGIN, CYPRESS_ADMIN_PASSWORD, CYPRESS_BASE_URL).
set -e

cd "$(dirname "$0")/.."
ENV_FILE=../../.env

env_value() {
  [ -f "$ENV_FILE" ] && grep -E "^$1=" "$ENV_FILE" | tail -1 | cut -d= -f2- | sed 's/[[:space:]]*#.*$//'
}

port=$(env_value PHRASEANET_APP_PORT)
export CYPRESS_BASE_URL="${CYPRESS_BASE_URL:-http://localhost:${port:-8082}}"
export CYPRESS_ADMIN_LOGIN="${CYPRESS_ADMIN_LOGIN:-$(env_value PHRASEANET_ADMIN_ACCOUNT_EMAIL)}"
export CYPRESS_ADMIN_PASSWORD="${CYPRESS_ADMIN_PASSWORD:-$(env_value PHRASEANET_ADMIN_ACCOUNT_PASSWORD)}"

if [ -z "$CYPRESS_ADMIN_PASSWORD" ]; then
  echo "Admin password not found: set CYPRESS_ADMIN_PASSWORD or PHRASEANET_ADMIN_ACCOUNT_PASSWORD in $ENV_FILE" >&2
  exit 2
fi

# Under `npm run`, PATH also holds the node_modules/.bin of parent folders (the repository
# root ships an old npm): use the npm that launched us, and the local Cypress binary.
if [ -n "$npm_execpath" ]; then npm_cmd="node $npm_execpath"; else npm_cmd=npm; fi
[ -x node_modules/.bin/cypress ] || $npm_cmd install --no-audit --no-fund

# Terminals embedded in Electron-based editors (VS Code…) export ELECTRON_RUN_AS_NODE,
# which makes the Cypress app reject its own options ("bad option: --no-sandbox").
unset ELECTRON_RUN_AS_NODE

mode="${1:-open}"
[ $# -gt 0 ] && shift
echo "Cypress $mode on $CYPRESS_BASE_URL as ${CYPRESS_ADMIN_LOGIN:-admin@phraseanet.local}"
exec ./node_modules/.bin/cypress "$mode" --e2e "$@"
