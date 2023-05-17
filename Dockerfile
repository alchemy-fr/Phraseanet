#########################################################################
# This image contains every build tools that will be used by the builder and
# the app images (usefull in dev mode)
#########################################################################

FROM php:7.0-fpm-stretch as phraseanet-system

ENV FFMPEG_VERSION=4.2.2

RUN echo "deb http://archive.debian.org/debian stretch main non-free" > /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y \
        apt-transport-https \
        ca-certificates \
        gnupg2 \
        wget \
    && wget -O certs.deb http://ftp.fr.debian.org/debian/pool/main/c/ca-certificates/ca-certificates_20210119_all.deb \
    && dpkg --fsys-tarfile certs.deb | tar -xOf - ./usr/share/ca-certificates/mozilla/ISRG_Root_X1.crt > /usr/local/share/ca-certificates/ISRG_Root_X1.crt \
    && rm -rf /usr/share/ca-certificates/mozilla/DST_Root_CA_X3.crt \
    && update-ca-certificates --fresh \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        zlib1g-dev \
        automake \
        git \
        ghostscript \
        gpac \
        imagemagick \
        ufraw \
        inkscape \
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
        libtool \
        locales \
        gettext \
        mcrypt \
        swftools \
        unoconv \
        unzip \
        poppler-utils \
        libreoffice-base-core \
        libreoffice-impress \
        libreoffice-calc \
        libreoffice-math \
        libreoffice-writer \                                                                 
        libreoffice-pdfimport \
        # heic
        libde265-dev \
        libopenjp2-7-dev \
        librsvg2-dev \
        libwebp-dev \
        # End heic
        # FFmpeg
        yasm \
        libvorbis-dev \
        texi2html \
        nasm \
        zlib1g-dev \
        libx264-dev \
        libfdk-aac-dev \
        libopus-dev \
        libvpx-dev \
        libmp3lame-dev \
        libogg-dev \
        libopencore-amrnb-dev \
        libopencore-amrwb-dev \
        libdc1394-22-dev \
        libx11-dev \
        libswscale-dev \
        libpostproc-dev \
        libxvidcore-dev \
        libtheora-dev \
        libgsm1-dev \
        libfreetype6-dev \
        libldap2-dev \ 
        # End FFmpeg \
        nano \
    && update-locale "LANG=fr_FR.UTF-8 UTF-8" \
    && dpkg-reconfigure --frontend noninteractive locales \
    # --- jq and libs for php-ext-jq \
    && mkdir /tmp/libjq \
    && git clone https://github.com/stedolan/jq.git /tmp/libjq \
    && cd /tmp/libjq \
    && git submodule update --init \
    && autoreconf -fi \
    && ./configure --with-oniguruma=builtin --disable-maintainer-mode \
    && make -j8 \
    && make check \
    && make install \
    # --- end of jq \
    && mkdir /tmp/libheif \
    && git clone https://github.com/strukturag/libheif.git /tmp/libheif \
    && cd /tmp/libheif \
    && git checkout v1.13.0 \
    && ./autogen.sh \
    && ./configure \
    && make \
    && make install
RUN echo "BUILDING AND INSTALLING IMAGEMAGICK" \
    && git clone https://github.com/ImageMagick/ImageMagick.git /tmp/ImageMagick \
    && cd /tmp/ImageMagick \
    && git checkout 7.1.0-39 \
    && ./configure \
    && make \
    && make install 
RUN echo "BUILDING PHP PECL EXTENTIONS" \
    && ldconfig \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) ldap \
    && docker-php-ext-install zip exif iconv mbstring pcntl sockets xsl intl pdo_mysql gettext bcmath mcrypt \
    # --- extension jq - sources _must_ be in /usr/src/php/ext/ for the docker-php-ext-install script to find it \
    && mkdir -p /usr/src/php/ext/ \
    && git clone --depth=1 https://github.com/kjdev/php-ext-jq.git /usr/src/php/ext/php-ext-jq \
    && docker-php-ext-install php-ext-jq \
    # --- end of extension jq \
    && pecl install \
        redis \
        amqp-1.9.3 \
        zmq-beta \
        imagick-beta \
        xdebug-2.6.1 \
    && docker-php-ext-enable redis amqp zmq imagick opcache \
    && pecl clear-cache \
    && docker-php-source delete
RUN echo "BUILDING AND INSTALLING FFMPEG" \
    && mkdir /tmp/ffmpeg \
    && curl -s https://ffmpeg.org/releases/ffmpeg-${FFMPEG_VERSION}.tar.bz2 | tar jxf - -C /tmp/ffmpeg \
    && ( \
        cd /tmp/ffmpeg/ffmpeg-${FFMPEG_VERSION} \
        && ./configure \
            --enable-gpl \
            --enable-nonfree \
            --enable-libfdk-aac \
            --enable-libfdk_aac \
            --enable-libgsm \
            --enable-libmp3lame \
            --enable-libtheora \
            --enable-libvorbis \
            --enable-libvpx \
            --enable-libfreetype \
            --enable-libopus \
            --enable-libx264 \
            --enable-libxvid \
            --enable-zlib \
            --enable-postproc \
            --enable-swscale \
            --enable-pthreads \
            --enable-libdc1394 \
            --enable-version3 \
            --enable-libopencore-amrnb \
            --enable-libopencore-amrwb \
        && make \
        && make install \
        && make distclean \
    )
    #&& rm -rf /tmp/ffmpeg
RUN echo "INSTALLING NEWRELIC AND BLACKFIRE EXTENTIONS" \
    && echo 'deb http://apt.newrelic.com/debian/ newrelic non-free' | tee /etc/apt/sources.list.d/newrelic.list \
    && curl -o- https://download.newrelic.com/548C16BF.gpg | apt-key add - \
    && apt-get update \ 
    && apt-get install -y newrelic-php5 \ 
    && NR_INSTALL_SILENT=1 newrelic-install install \
    && touch /etc/newrelic/newrelic.cfg \
    && curl -o- https://packages.blackfire.io/gpg.key |apt-key add - \
    && echo "deb http://packages.blackfire.io/debian any main" |tee /etc/apt/sources.list.d/blackfire.list \
    && apt update \
    && apt install blackfire-agent \
    && apt install blackfire-php
RUN echo "FINALIZING BUILD AND CLEANING" \
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

COPY --from=composer:2.1.6 /usr/bin/composer /usr/bin/composer

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
        nano \
        vim \
        iputils-ping \
        zsh \
        ssh \
        telnet \
        autoconf \
        libtool \
        python \
        pkg-config \
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
RUN composer install --prefer-dist --no-dev --no-progress --classmap-authoritative --no-interaction --no-scripts \
    && rm -rf vendor composer.*
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

FROM phraseanet-system as phraseanet-setup

COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/setup/entrypoint.sh"]
CMD []


#########################################################################
# Phraseanet web application image
#########################################################################

FROM phraseanet-system as phraseanet-fpm

COPY --from=builder --chown=app /var/alchemy/Phraseanet /var/alchemy/Phraseanet
ADD ./docker/phraseanet/root /
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["docker/phraseanet/fpm/entrypoint.sh"]
CMD ["php-fpm", "-F"]

#########################################################################
# Phraseanet worker application image
#########################################################################

FROM phraseanet-fpm as phraseanet-worker
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        supervisor \
        logrotate \
    && mkdir -p /var/log/supervisor \
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

FROM nginx:1.17.8-alpine as phraseanet-nginx
RUN adduser --uid 1000 --disabled-password app
ADD ./docker/nginx/root /
COPY --from=builder /var/alchemy/Phraseanet/www /var/alchemy/Phraseanet/www

ENTRYPOINT ["/entrypoint.sh"]

CMD ["nginx", "-g", "daemon off;"]
HEALTHCHECK CMD wget --spider http://127.0.0.1/login || nginx -s reload || exit 1

#########################################################################
# phrasaseanet adapted simplesaml service provider 
#########################################################################

FROM php:7.0-fpm-stretch as phraseanet-saml-sp
RUN adduser --uid 1000 --disabled-password app
RUN echo "deb http://archive.debian.org/debian stretch main non-free" > /etc/apt/sources.list \
    && apt-get update \
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
    && curl -Ls https://github.com/simplesamlphp/simplesamlphp/releases/download/simplesamlphp-1.10.0/simplesamlphp-1.10.0.tar.gz | tar xzvf - -C /var/www/ \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) ldap \
    && docker-php-ext-install zip mbstring pdo_mysql gettext mcrypt \
    && pecl install \
        redis \
    && docker-php-ext-enable redis \
    && pecl clear-cache \
    && docker-php-source delete
ADD ./docker/phraseanet/saml-sp/root /
ENTRYPOINT ["/bootstrap/entrypoint.sh"]
CMD ["/bootstrap/bin/start-servers.sh"]
HEALTHCHECK CMD wget --spider http://127.0.0.1/ || nginx -s reload || exit 

