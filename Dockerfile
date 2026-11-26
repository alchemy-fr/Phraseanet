#########################################################################
# Phraseanet base image: PHP 8.5 + media binaries
# built and published from https://github.com/alchemy-fr/phraseanet-base-images
#########################################################################

ARG PHRASEANET_BASE_IMAGE=alchemyfr/phraseanet-base:2.0.0

FROM ${PHRASEANET_BASE_IMAGE} AS phraseanet-base

#########################################################################
# Phraseanet builder image
#########################################################################

FROM phraseanet-base AS builder

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node Installation (node + yarn)

RUN cd /tmp \
    && curl -O https://nodejs.org/download/release/v10.24.1/node-v10.24.1-linux-x64.tar.gz \
    && tar -xf node-v10.24.1-linux-x64.tar.gz \
    && cp -Rf node-v10.24.1-linux-x64/* /usr/ \
    && rm -rf node-v10.24.1-linux-x64 node-v10.24.1-linux-x64.tar.gz \
    && npm install -g yarn@1.22.22 \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        vim \
        iputils-ping \
        zsh \
        ssh \
        telnet \
        autoconf \
        libtool \
        python3 \
        pkg-config \
        make \
        g++ \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists \
    && git clone https://github.com/robbyrussell/oh-my-zsh.git /bootstrap/.oh-my-zsh \
    && mkdir -p /var/alchemy/Phraseanet \
    && chown -R app:app /var/alchemy

# Set the php memory_limit
RUN echo 'memory_limit = 2048M' >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini

WORKDIR /var/alchemy/Phraseanet

USER app

# Warm up composer cache for faster builds
COPY docker/caching/composer.* ./
COPY --chown=app packages ./packages
COPY --chown=app patches ./patches
RUN composer install --prefer-dist --no-dev --no-progress --classmap-authoritative --no-interaction --no-scripts
#    && rm -rf vendor composer.*
# End warm up

COPY --chown=app  . .

# GitHub release downloads (phantomjs) send response headers larger than the 8 KB accepted by default by node 10
RUN NODE_OPTIONS=--max-http-header-size=65536 make install

ADD ./docker/builder/root /

# SSH Private repo
ARG SSH_PRIVATE_KEY
ARG PHRASEANET_PLUGINS

RUN ( \
        test ! -z "${SSH_PRIVATE_KEY}" \
        && mkdir -p ~/.ssh \
        && echo "${SSH_PRIVATE_KEY}" > ~/.ssh/id_rsa \
        # make sure github domain.com is accepted
        && ssh-keyscan -H github.com >> ~/.ssh/known_hosts \
        && chmod 600 ~/.ssh/id_rsa \
    ) || echo "Skip SSH key"

RUN ./docker/phraseanet/plugins/console install

ENTRYPOINT ["/bootstrap/entrypoint.sh"]

CMD []

#########################################################################
# Phraseanet install and setup application image
#########################################################################

FROM phraseanet-base AS phraseanet-setup

COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/setup/entrypoint.sh"]
CMD []


#########################################################################
# Phraseanet web application image
#########################################################################

FROM phraseanet-base AS phraseanet-fpm

COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/fpm/entrypoint.sh"]
CMD ["php-fpm", "-F"]

#########################################################################
# Phraseanet worker application image
#########################################################################

FROM phraseanet-base AS phraseanet-worker

COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet

RUN apt-get update
RUN apt-get install -y --no-install-recommends  supervisor
RUN apt-get install -y --no-install-recommends  logrotate 
RUN mkdir -p /var/log/supervisor \
    && chown -R app: /var/log/supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists 

COPY ./docker/phraseanet/worker/supervisor.conf /etc/supervisor/
COPY ./docker/phraseanet/worker/logrotate/worker /etc/logrotate.d/

RUN chmod 644 /etc/logrotate.d/worker

ENTRYPOINT ["docker/phraseanet/worker/entrypoint.sh"]
CMD ["/bin/bash", "bin/run-worker.sh"]

#########################################################################
# phraseanet-nginx
#########################################################################

FROM nginx:1.30.7-alpine AS phraseanet-nginx
RUN adduser --uid 1000 --disabled-password app
RUN apk add --no-cache apache2-utils
ADD ./docker/nginx/root /
COPY --from=builder /var/alchemy/Phraseanet/www /var/alchemy/Phraseanet/www

ENTRYPOINT ["/entrypoint.sh"]

CMD ["nginx", "-g", "daemon off;"]
HEALTHCHECK CMD wget --spider http://127.0.0.1/login || nginx -s reload || exit 1

#########################################################################
# phraseanet adapted simplesaml service provider 
#########################################################################

# the SAML service provider (simplesamlphp 2.x), shares its sessions with phraseanet (saml-authentication plugin) through redis
FROM php:8.5-fpm-trixie AS phraseanet-saml-sp

ARG SIMPLESAMLPHP_VERSION=2.5.4
ARG SIMPLESAMLPHP_SHA256=d2159c8ea2cd802a1c31fd32551e40e9eba9451b69fdd0164f99cb2fbf7cdb42

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        wget \
        unzip \
        nginx \
        gettext-base \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions redis intl bcmath ldap \
    && curl -fsSL -o /tmp/simplesamlphp.tar.gz https://github.com/simplesamlphp/simplesamlphp/releases/download/v${SIMPLESAMLPHP_VERSION}/simplesamlphp-${SIMPLESAMLPHP_VERSION}-full.tar.gz \
    && echo "${SIMPLESAMLPHP_SHA256}  /tmp/simplesamlphp.tar.gz" | sha256sum -c - \
    && tar xzf /tmp/simplesamlphp.tar.gz -C /var/www/ \
    && mv /var/www/simplesamlphp-${SIMPLESAMLPHP_VERSION} /var/www/simplesamlphp \
    && rm /tmp/simplesamlphp.tar.gz \
    && cd /var/www/simplesamlphp \
    && COMPOSER_ALLOW_SUPERUSER=1 composer require --no-interaction --no-progress --update-no-dev --optimize-autoloader "predis/predis:~3.3" \
    && rm -rf /root/.composer/cache \
    && mkdir -p /var/www/simplesamlphp/log /var/www/simplesamlphp/data /tmp/simplesaml \
    && chown -R www-data:www-data /var/www/simplesamlphp/log /var/www/simplesamlphp/data /tmp/simplesaml
ADD ./docker/phraseanet/saml-sp/root /
ENTRYPOINT ["/bootstrap/entrypoint.sh"]
CMD ["/bootstrap/bin/start-servers.sh"]
HEALTHCHECK CMD wget -q --spider http://127.0.0.1:8080/simplesaml/ || exit 1
