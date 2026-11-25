# Phraseanet end-to-end tests — Cypress 16.3.0 (Node 26, Chrome 157, Firefox 160, Edge 157).
# The image already ships Cypress and its browsers: only the project files are added.
FROM cypress/included:16.3.0

# socat relays localhost:<port> to the Phraseanet instance (see entrypoint.sh).
RUN apt-get update \
 && apt-get install -y --no-install-recommends socat \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /e2e

# No extra npm dependency: the specs only use Cypress itself.
COPY package.json cypress.config.js entrypoint.sh ./
COPY cypress ./cypress
RUN chmod +x /e2e/entrypoint.sh

# Extra arguments of `docker compose run cypress ...` (--spec, --browser…) go to `cypress run`.
ENTRYPOINT ["/e2e/entrypoint.sh"]
