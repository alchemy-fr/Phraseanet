#!/bin/bash

set -e

DOCKER_DIR="./docker/phraseanet"

mkdir -p "${APP_DIR}/tmp/locks" \
    && chown -R app:app "${APP_DIR}/tmp"

envsubst < "${DOCKER_DIR}/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "${DOCKER_DIR}/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

runuser -u app -- $@
