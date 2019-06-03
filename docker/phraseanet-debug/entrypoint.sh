#!/bin/sh

set -e

if [ ! -z ${DOCKER_XDEBUG_ENABLED} ]; then
    . usr-bin/docker-xdebug-enable
fi

exec "$@"
