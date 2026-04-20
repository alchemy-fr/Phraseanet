
FROM alchemyfr/phraseanet-base:1.2.3 AS builder

COPY --from=composer:2.1.6 /usr/bin/composer /usr/bin/composer

# Node Installation (node + yarn)

RUN cd /tmp \
    && curl -O https://nodejs.org/download/release/v10.24.1/node-v10.24.1-linux-x64.tar.gz \
    && tar -xvf node-v10.24.1-linux-x64.tar.gz \
    && cp -Rf node-v10.24.1-linux-x64/* /usr/ \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        yarn \
        nano \
        vim \
        iputils-ping \
        zsh \
        ssh \
        telnet \
        autoconf \
        libtool \
        python3 \
        pkg-config \
    && apt-get clean \
    && git clone https://github.com/robbyrussell/oh-my-zsh.git /bootstrap/.oh-my-zsh \
    && mkdir -p /var/alchemy/Phraseanet \
    && chown -R app:app /var/alchemy

# Set the php memory_limit
RUN echo 'memory_limit = 2048M' >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini

WORKDIR /var/alchemy/Phraseanet

USER app

# Warm up composer cache for faster builds
COPY docker/caching/composer.* ./
RUN composer install --prefer-dist --no-dev --no-progress --classmap-authoritative --no-interaction --no-scripts
#    && rm -rf vendor composer.*
# End warm up

COPY --chown=app  . .

RUN make install

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

FROM builder AS phraseanet-setup

USER root
COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/setup/entrypoint.sh"]
CMD []


#########################################################################
# Phraseanet web application image
#########################################################################

FROM builder AS phraseanet-fpm

USER root
COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/fpm/entrypoint.sh"]
CMD ["php-fpm", "-F"]

#########################################################################
# Phraseanet worker application image
#########################################################################

FROM builder AS phraseanet-worker

USER root
COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
RUN apt-get update
RUN apt-get install -y --no-install-recommends  supervisor
RUN apt-get install -y --no-install-recommends  logrotate 
RUN mkdir -p /var/log/supervisor \
    && chown -R app: /var/log/supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* 

COPY ./docker/phraseanet/worker/supervisor.conf /etc/supervisor/
COPY ./docker/phraseanet/worker/logrotate/worker /etc/logrotate.d/

RUN chmod 644 /etc/logrotate.d/worker

ENTRYPOINT ["docker/phraseanet/worker/entrypoint.sh"]
CMD ["/bin/bash", "bin/run-worker.sh"]

#########################################################################
# phraseanet-nginx
#########################################################################

FROM nginx:1.27.2-alpine AS phraseanet-nginx
RUN adduser --uid 1000 --disabled-password app
RUN apk add --update apache2-utils \
    && rm -rf /var/cache/apk/*
ADD ./docker/nginx/root /
COPY --from=builder /var/alchemy/Phraseanet/www /var/alchemy/Phraseanet/www

ENTRYPOINT ["/entrypoint.sh"]

CMD ["nginx", "-g", "daemon off;"]
HEALTHCHECK CMD wget --spider http://127.0.0.1/login || nginx -s reload || exit 1

#########################################################################
# phraseanet adapted simplesaml service provider 
#########################################################################

FROM builder AS phraseanet-saml-sp
USER root
RUN apt-get update \
    && apt-get install -y \
        apt-transport-https \
        ca-certificates \
        gnupg2 \
        wget \
        nginx \
        zlib1g-dev \
        automake \
        git \
        libmcrypt-dev \
        libzmq3-dev \
        libtool \
        locales \
        gettext \
        mcrypt \
        libldap2-dev \
    && curl -Ls https://github.com/simplesamlphp/simplesamlphp/releases/download/simplesamlphp-1.10.0/simplesamlphp-1.10.0.tar.gz | tar xzvf - -C /var/www/
ADD ./docker/phraseanet/saml-sp/root /
ENTRYPOINT ["/bootstrap/entrypoint.sh"]
CMD ["/bootstrap/bin/start-servers.sh"]
HEALTHCHECK CMD wget --spider http://127.0.0.1/ || nginx -s reload || exit
