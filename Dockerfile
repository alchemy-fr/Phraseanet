FROM php:7.0-fpm-stretch as builder

RUN apt-get update \
    && apt-get install -y \
        apt-transport-https \
        ca-certificates \
        gnupg2 \
    && apt-get update \
    && apt-get install -y --no-install-recommends zlib1g-dev \
        git \
        ghostscript \
        gpac \
        imagemagick \
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
    && pecl install redis amqp-1.9.3 zmq-beta imagick-beta \
    && docker-php-ext-enable redis amqp zmq imagick \
    && pecl clear-cache \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/*

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Node Installation (node + yarn)
# Reference :
# https://linuxize.com/post/how-to-install-node-js-on-ubuntu-18.04/
# https://yarnpkg.com/lang/en/docs/install/#debian-stable
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && apt install -y nodejs \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update && apt-get install -y --no-install-recommends yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/

RUN mkdir /entrypoint /var/alchemy \
    && useradd -u 1000 app \
    && mkdir -p /home/app/.composer \
    && chown -R app: /home/app /var/alchemy

WORKDIR /var/alchemy/

COPY gulpfile.js /var/alchemy/
COPY Makefile /var/alchemy/
COPY package.json /var/alchemy/
COPY phpunit.xml.dist /var/alchemy/
COPY yarn.lock /var/alchemy/
COPY bin /var/alchemy/bin
COPY composer.json /var/alchemy/
COPY composer.lock /var/alchemy/
RUN make install_composer
COPY resources /var/alchemy/resources
COPY www /var/alchemy/www
RUN make clean_assets
RUN make install_asset_dependencies
RUN make install_assets

ADD ./docker/phraseanet/ /
COPY lib /var/alchemy/lib
COPY tmp /var/alchemy/tmp
COPY config /var/alchemy/config
COPY grammar /var/alchemy/grammar
COPY templates-profiler /var/alchemy/templates-profiler
COPY templates /var/alchemy/templates
COPY tests /var/alchemy/tests

# Phraseanet
FROM php:7.0-fpm-stretch as phraseanet-fpm
RUN apt-get update \
    && apt-get install -y \
        apt-transport-https \
        ca-certificates \
        gnupg2 \
    && apt-get update \
    && apt-get install -y --no-install-recommends zlib1g-dev \
        gettext \
        git \
        ghostscript \
        gpac \
        imagemagick \
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
    && pecl install redis amqp-1.9.3 zmq-beta imagick-beta \
    && docker-php-ext-enable redis amqp zmq imagick \
    && pecl clear-cache \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir /entrypoint /var/alchemy \
    && useradd -u 1000 app \
    && mkdir -p /home/app/.composer \
    && chown -R app: /home/app /var/alchemy

COPY --from=builder --chown=app /var/alchemy /var/alchemy/Phraseanet
ADD ./docker/phraseanet/ /
RUN mkdir -p /var/alchemy/Phraseanet/logs \
    && chmod -R 777 /var/alchemy/Phraseanet/logs \
    && mkdir -p /var/alchemy/Phraseanet/cache \
    && chmod -R 777 /var/alchemy/Phraseanet/cache \
    && mkdir -p /var/alchemy/Phraseanet/datas \
    && chmod -R 777 /var/alchemy/Phraseanet/datas \
    && mkdir -p /var/alchemy/Phraseanet/tmp \
    && chmod -R 777 /var/alchemy/Phraseanet/tmp \
    && mkdir -p /var/alchemy/Phraseanet/www/custom \
    && chmod -R 777 /var/alchemy/Phraseanet/www/custom \
    && mkdir -p /var/alchemy/Phraseanet/config \
    && chmod -R 777 /var/alchemy/Phraseanet/config
WORKDIR /var/alchemy/Phraseanet
ENTRYPOINT ["/phraseanet-entrypoint.sh"]
CMD ["/boot.sh"]

# phraseanet-worker
FROM phraseanet-fpm as phraseanet-worker
CMD ["/worker-boot.sh"]

# phraseanet-nginx
FROM nginx:1.15 as phraseanet-nginx
RUN useradd -u 1000 app
ADD ./docker/nginx/ /
COPY --from=builder /var/alchemy/www /var/alchemy/Phraseanet/www
CMD ["/boot.sh"]
