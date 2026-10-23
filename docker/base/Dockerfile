#########################################################################
# Phraseanet base image: PHP 8.5 + media binaries
#########################################################################

FROM php:8.5-fpm-trixie

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

RUN useradd -u 1000 -m app \
    && mkdir -p /entrypoint /var/alchemy /home/app/.composer \
    && chown -R app: /home/app /var/alchemy
