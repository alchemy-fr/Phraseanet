# Phraseanet under Traefik

Doc status : Beta

Traefik documentation 

https://doc.traefik.io/traefik/providers/docker/

## Traekik Provide by Phrasea Stack

Requirement 

- A phrasea stack up.

https://github.com/alchemy-fr/phrasea

Phraseanet stack and Phrasea Stack need to be in separate subnet
You need to set several Phraseanet's and Phrasea env var for using the Traefik include in Phrasea Stack.

### Phraseanet side 

- Add `docker-compose.under-phrasea.yml` to `COMPOSE_FILES`

- Add `gateway-traefik` and remove `gateway-classic` from `COMPOSE_PROFILES`

- Set `PHRASEA_DOMAIN` with the name of network set Phrasea side 

- Set `PHRASEANET_TRUSTED_PROXIES`  with the phrasea subnet eg: `PHRASEANET_TRUSTED_PROXIES=172.30.0.0/16`


### Phrasea side 

- Set `PHRASEANET_BASE_URL` and `PHRASEANET_APP_OAUTH_TOKEN` in accordance of your Phraseanet's setting



## Traekik provide by Phraseanet Stack

Not implemented





