#!/bin/bash

set -e

mkdir -p /var/alchemy/Phraseanet/tmp/locks \
    && chown -R app:app /var/alchemy/Phraseanet/tmp

envsubst < /php.ini.sample > /usr/local/etc/php/php.ini
envsubst < /php-fpm.conf.sample > /usr/local/etc/php-fpm.conf

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

bash -e docker-php-entrypoint $@
