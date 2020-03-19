
#########################################################################
# This image contains every build tools that will be used by the builder and
# the app images (usefull in dev mode)
#########################################################################

FROM php:7.0-fpm-stretch as phraseanet-system

RUN apt-get update \
    && apt-get install -y \
        apt-transport-https \
        ca-certificates \
        gnupg2 \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        zlib1g-dev \
        git \
        ghostscript \
        gpac \
        imagemagick \
        gettext \
        libav-tools \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libmcrypt-dev \
        libpng-dev \
        librabbitmq-dev \
        libssl-dev \
        libxslt-dev \
        libzmq3-dev \
        locales \
        mcrypt \
        swftools \
        unoconv \
        unzip \
        xpdf \
    && update-locale "LANG=fr_FR.UTF-8 UTF-8" \
    && dpkg-reconfigure --frontend noninteractive locales \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install zip exif iconv mbstring pcntl sockets xsl intl pdo_mysql gettext bcmath mcrypt \
    && pecl install \
        redis \
        amqp-1.9.3 \
        zmq-beta \
        imagick-beta \
        xdebug-2.6.1 \
    && docker-php-ext-enable redis amqp zmq imagick \
    && pecl clear-cache \
    && docker-php-source delete \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists \
    && mkdir /entrypoint /var/alchemy \
    && useradd -u 1000 app \
    && mkdir -p /home/app/.composer \
    && chown -R app: /home/app /var/alchemy

ENV XDEBUG_ENABLED=0

#########################################################################
# This image is used to build the apps
#########################################################################

FROM phraseanet-system as builder

COPY --from=composer:1.9.1 /usr/bin/composer /usr/bin/composer

# Node Installation (node + yarn)
# Reference :
# https://linuxize.com/post/how-to-install-node-js-on-ubuntu-18.04/
# https://yarnpkg.com/lang/en/docs/install/#debian-stable
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        nodejs \
        yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists \
    && mkdir -p /var/alchemy/Phraseanet \
    && chown -R app:app /var/alchemy

RUN npm install -g bower recess

WORKDIR /var/alchemy/Phraseanet

USER app

# Warm up composer cache for faster builds
COPY docker/caching/composer.* ./
RUN composer install --prefer-dist --no-dev --no-progress --no-suggest --classmap-authoritative --no-interaction --no-scripts \
    && rm -rf vendor composer.*
# End warm up

COPY --chown=app  . .

RUN rm -rf docker/phraseanet/root \
    && make install

ADD docker/phraseanet/ /

#########################################################################
# Phraseanet web application image
#########################################################################

FROM phraseanet-system as phraseanet-fpm

COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/entrypoint.sh"]
CMD ["php-fpm", "-F"]

#########################################################################
# Phraseanet worker application image
#########################################################################

FROM phraseanet-fpm as phraseanet-worker
ENTRYPOINT ["docker/phraseanet/worker/entrypoint.sh"]
CMD ["bin/console", "task-manager:scheduler:run"]

#########################################################################
# phraseanet-nginx
#########################################################################

FROM nginx:1.17.8-alpine as phraseanet-nginx
RUN adduser --uid 1000 --disabled-password app
ADD ./docker/nginx/root /
COPY --from=builder /var/alchemy/Phraseanet/www /var/alchemy/Phraseanet/www

ENTRYPOINT ["/entrypoint.sh"]

CMD ["nginx", "-g", "daemon off;"]
