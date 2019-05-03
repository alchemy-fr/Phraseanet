FROM php:7.1-fpm-stretch as phraseanet_prod

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
        supervisor \
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
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/log/supervisor
    #&& chown -R app: /var/log/supervisor

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Node Installation (node + yarn)
# Reference :
# https://linuxize.com/post/how-to-install-node-js-on-ubuntu-18.04/
# https://yarnpkg.com/lang/en/docs/install/#debian-stable
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - \
    && apt install nodejs \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update && apt-get install -y --no-install-recommends yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/

RUN mkdir /entrypoint /var/alchemy \
    && useradd -u 1000 app \
    && mkdir -p /home/app/.composer \
    && chown -R app: /home/app /var/alchemy


ADD ./docker/phraseanet/ /

WORKDIR /var/alchemy/

COPY config /var/alchemy/config
COPY grammar /var/alchemy/grammar
COPY lib /var/alchemy/lib
COPY resources /var/alchemy/resources
RUN ls -la
COPY templates-profiler /var/alchemy/templates-profiler
COPY templates /var/alchemy/templates
COPY tests /var/alchemy/tests
COPY tmp /var/alchemy/tmp
COPY www /var/alchemy/www
COPY composer.json /var/alchemy/
COPY composer.lock /var/alchemy/
COPY gulpfile.js /var/alchemy/
COPY Makefile /var/alchemy/
COPY package-lock.json /var/alchemy/
COPY package.json /var/alchemy/
COPY phpunit.xml.dist /var/alchemy/
COPY yarn.lock /var/alchemy/
RUN ls -la

RUN make install_composer
RUN make clean_assets
RUN make install_asset_dependencies
RUN make install_assets

CMD ["php-fpm"]
