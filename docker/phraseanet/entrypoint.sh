#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf

chown -R app:app \
    config \
    datas \
    tmp \
    logs \
    www/thumbnails

FILE=config/configuration.yml

if [ -f "$FILE" ]; then
    bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME
    echo "$FILE exists, skip setup."
else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c docker/phraseanet/auto-install.sh
fi

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

./docker/phraseanet/plugins/console init

bash -e docker-php-entrypoint $@
