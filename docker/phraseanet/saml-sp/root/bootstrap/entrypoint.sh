#!/bin/bash

set -e

echo `date +"%Y-%m-%d %H:%M:%S"` " - Starting simplesamlphp sp entrypoint."

if [[ -n $SAML_ALLOW_DEBUG || $SAML_ALLOW_DEBUG == "true" ]]; then
echo "SAML DEBUG IS ENABLED - TO ADD DEFAULT CONFIG"
cp -Rf /var/www/simplesamlphp-1.10.0/config-templates/* /var/www/simplesamlphp-1.10.0/config/
cp -Rf /bootstrap/conf.d/phrasea.*  /var/www/simplesamlphp-1.10.0/cert/

fi

envsubst < "/bootstrap/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst \$SAML_PHRASEANET_HOST < "/bootstrap/config/config.php" > /var/www/simplesamlphp-1.10.0/config/config.php

if [[ -n $SAML_SP_AUTHSOURCES ]]; then
    echo "Pas de variable"
    envsubst \$SAML_SP_AUTHSOURCES < "/bootstrap/config/authsources.php" > /var/www/simplesamlphp-1.10.0/config/authsources.php
else
    export SAML_SP_AUTHSOURCES=$(cat /bootstrap/conf.d/phrasea-sp)
    echo "variable par defaut" $SAML_SP_AUTHSOURCES 
    envsubst \$SAML_SP_AUTHSOURCES < "/bootstrap/config/authsources.php" > /var/www/simplesamlphp-1.10.0/config/authsources.php    
fi

# envsubst < "/bootstrap/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
chmod +x /bootstrap/bin/start-servers.sh

bash -e docker-php-entrypoint $@
