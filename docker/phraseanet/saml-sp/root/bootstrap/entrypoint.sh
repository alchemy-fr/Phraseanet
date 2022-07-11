#!/bin/sh

set -e

echo `date +"%Y-%m-%d %H:%M:%S"` " - Starting simplesamlphp sp entrypoint."


envsubst < "/bootstrap/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst \$SAML_PHRASEANET_HOST < "/bootstrap/config/config.php" > /var/www/simplesamlphp-1.10.0/config/config.php
# envsubst < "/bootstrap/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
chmod +x /bootstrap/bin/start-servers.sh

bash -e docker-php-entrypoint $@
