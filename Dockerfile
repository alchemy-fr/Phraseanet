#########################################################################
# Phraseanet base image: PHP 8.5 + media binaries
#########################################################################

FROM php:8.5-fpm-trixie AS phraseanet-base

ARG FFMPEG_VERSION=7.1.2

ENV XDEBUG_ENABLED=0

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN sed -i 's/^Components: main$/Components: main contrib non-free/' /etc/apt/sources.list.d/debian.sources \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        wget \
        git \
        unzip \
        xz-utils \
        locales \
        gettext \
        nano \
        ghostscript \
        inkscape \
        imagemagick \
        libheif1 \
        libheif-plugin-libde265 \
        librsvg2-bin \
        poppler-utils \
        dcraw \
        python3-uno \
        python3-setuptools \
        libreoffice-base-core \
        libreoffice-impress \
        libreoffice-calc \
        libreoffice-math \
        libreoffice-writer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL --retry 5 -o /usr/local/bin/unoconv https://raw.githubusercontent.com/unoconv/unoconv/0.9.0/unoconv \
    && sed -i '1s|.*|#!/usr/bin/python3|' /usr/local/bin/unoconv \
    && chmod +x /usr/local/bin/unoconv

# MP4Box (gpac) is not packaged in Debian trixie
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends build-essential pkg-config zlib1g-dev; \
    git clone --depth=1 --branch v2.4.0 https://github.com/gpac/gpac.git /tmp/gpac; \
    cd /tmp/gpac; \
    ./configure --static-bin; \
    make -j"$(nproc)"; \
    install -m 755 bin/gcc/MP4Box /usr/local/bin/MP4Box; \
    cd /; \
    rm -rf /tmp/gpac; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark > /dev/null; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    MP4Box -version

RUN install-php-extensions \
        amqp \
        bcmath \
        exif \
        ftp \
        gd \
        gettext \
        imagick \
        intl \
        ldap \
        pcntl \
        pdo_mysql \
        redis \
        sockets \
        xsl \
        zip \
        xdebug \
    && rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# php-zmq has no PHP 8.5 compatible release: zend_exception_get_default() was removed from the engine
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends $PHPIZE_DEPS libzmq3-dev; \
    git clone https://github.com/zeromq/php-zmq.git /tmp/php-zmq; \
    cd /tmp/php-zmq; \
    git checkout 616b6c64ffd3866ed038615494306dd464ab53fc; \
    sed -i 's/zend_exception_get_default()/zend_ce_exception/g' zmq.c; \
    phpize; \
    ./configure; \
    make -j"$(nproc)"; \
    make install; \
    docker-php-ext-enable zmq; \
    cd /; \
    rm -rf /tmp/php-zmq; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark libzmq5 > /dev/null; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    php -r 'exit(class_exists("ZMQContext") ? 0 : 1);'

# ffmpeg is built from source to get libfdk_aac, which Debian does not ship in its ffmpeg build
RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        build-essential pkg-config nasm yasm \
        libfdk-aac-dev libgsm1-dev libmp3lame-dev libtheora-dev libvorbis-dev libvpx-dev \
        libfreetype-dev libopus-dev libx264-dev libxvidcore-dev zlib1g-dev libdc1394-dev \
        libopencore-amrnb-dev libopencore-amrwb-dev; \
    curl -fsSL --retry 5 -o /tmp/ffmpeg.tar.xz "https://ffmpeg.org/releases/ffmpeg-${FFMPEG_VERSION}.tar.xz"; \
    mkdir /tmp/ffmpeg; \
    tar xJf /tmp/ffmpeg.tar.xz -C /tmp/ffmpeg --strip-components=1; \
    cd /tmp/ffmpeg; \
    ./configure \
        --enable-gpl --enable-nonfree --enable-version3 \
        --enable-libfdk-aac --enable-libgsm --enable-libmp3lame --enable-libtheora --enable-libvorbis \
        --enable-libvpx --enable-libfreetype --enable-libopus --enable-libx264 --enable-libxvid \
        --enable-libdc1394 --enable-libopencore-amrnb --enable-libopencore-amrwb \
        --enable-zlib --enable-postproc --enable-swscale --enable-pthreads \
        --disable-doc --disable-debug; \
    make -j"$(nproc)"; \
    make install; \
    cd /; \
    rm -rf /tmp/ffmpeg /tmp/ffmpeg.tar.xz; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark > /dev/null; \
    find /usr/local/bin -name 'ff*' -type f -exec ldd '{}' ';' \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
        | sort -u | xargs -r dpkg-query --search | cut -d: -f1 | sort -u | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*; \
    ffmpeg -hide_banner -encoders | grep -q libfdk_aac

# the entrypoints tune the ImageMagick policy from this location
RUN ln -s /etc/ImageMagick-7 /usr/local/etc/ImageMagick-7

RUN useradd -u 1000 -m app \
    && mkdir -p /entrypoint /var/alchemy /home/app/.composer \
    && chown -R app: /home/app /var/alchemy

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

# the SAML service provider is a standalone test tool built on simplesamlphp 1.10 which requires PHP 7
FROM alchemyfr/phraseanet-base:1.3.0 AS phraseanet-saml-sp
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
