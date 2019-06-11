ARG phraseanet
FROM $phraseanet

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        iproute2 \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && pecl clear-cache

ADD ./docker/phraseanet-debug/ /

RUN chmod +x /entrypoint.sh /usr/local/bin/docker-*

ENTRYPOINT ["/entrypoint.sh"]

CMD ["php-fpm"]
