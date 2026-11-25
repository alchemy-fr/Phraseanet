#!/bin/bash

set -e

echo `date +"%Y-%m-%d %H:%M:%S"` " - Starting simplesamlphp sp entrypoint."

SSP_DIR=/var/www/simplesamlphp

# simplesamlphp sessions are stored in redis, shared with phraseanet
export SAML_REDIS_HOST=${SAML_REDIS_HOST:-redis-session}
export SAML_REDIS_PORT=${SAML_REDIS_PORT:-6379}

if [[ $SAML_ALLOW_DEBUG == "true" ]]; then
    echo "SAML DEBUG IS ENABLED - TO ADD DEFAULT CERTIFICATE"
    if [[ ! -f $SSP_DIR/cert/phrasea.pem ]]; then
        cp -f /bootstrap/conf.d/phrasea.* $SSP_DIR/cert/
    fi
    export SAML_DEBUG=true
else
    export SAML_DEBUG=false
fi

# config.php is shared with phraseanet (config/plugins/saml-authentication/config.php) :
# a default one is generated only if it does not exist yet, an existing file is never modified
if [[ ! -f $SSP_DIR/config/config.php ]]; then
    if [[ -z $SAML_SECRET_SALT ]]; then
        export SAML_SECRET_SALT=$(tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 64)
    fi
    if [[ -z $SAML_ADMIN_PASSWORD ]]; then
        export SAML_ADMIN_PASSWORD=$(tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c 32)
        echo "random simplesamlphp admin password set in config.php (auth.adminpassword)"
    fi
    case "$SAML_PHRASEANET_HOST" in
        https://*) export SAML_COOKIE_SECURE=true SAML_COOKIE_SAMESITE="'None'" ;;
        *)         export SAML_COOKIE_SECURE=false SAML_COOKIE_SAMESITE=null ;;
    esac

    envsubst '${SAML_PHRASEANET_HOST} ${SAML_SECRET_SALT} ${SAML_ADMIN_PASSWORD} ${SAML_DEBUG} ${SAML_COOKIE_SECURE} ${SAML_COOKIE_SAMESITE} ${SAML_REDIS_HOST} ${SAML_REDIS_PORT}' \
        < /bootstrap/config/config.php > $SSP_DIR/config/config.php
    chmod 644 $SSP_DIR/config/config.php
    echo "default simplesamlphp config.php generated"
else
    echo "existing simplesamlphp config.php kept unchanged"
fi

if [[ -z $SAML_SP_AUTHSOURCES ]]; then
    export SAML_SP_AUTHSOURCES=$(cat /bootstrap/conf.d/phrasea-sp)
    echo "using default authsource : " $SAML_SP_AUTHSOURCES
fi
envsubst '${SAML_SP_AUTHSOURCES}' < /bootstrap/config/authsources.php > $SSP_DIR/config/authsources.php

chmod 644 $SSP_DIR/config/authsources.php

chmod +x /bootstrap/bin/start-servers.sh

bash -e docker-php-entrypoint $@
