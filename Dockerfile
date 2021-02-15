#########################################################################
# This image contains every build tools that will be used by the builder and
# the app images (usefull in dev mode)
#########################################################################

FROM php:7.0-fpm-stretch as phraseanet-system

ENV FFMPEG_VERSION=4.2.2

RUN echo "deb http://deb.debian.org/debian stretch main non-free" > /etc/apt/sources.list \
    && apt-get update \
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
        # End FFmpeg
        nano \
    && update-locale "LANG=fr_FR.UTF-8 UTF-8" \
    && dpkg-reconfigure --frontend noninteractive locales \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) ldap \
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
    ) \
    && rm -rf /tmp/ffmpeg \
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

WORKDIR /var/alchemy/Phraseanet

USER app

# Warm up composer cache for faster builds
COPY docker/caching/composer.* ./
RUN composer install --prefer-dist --no-dev --no-progress --no-suggest --classmap-authoritative --no-interaction --no-scripts \
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
