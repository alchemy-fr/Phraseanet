#!/bin/bash

set -e

envsubst < /php.ini.sample > /usr/local/etc/php/php.ini
envsubst < /php-fpm.conf.sample > /usr/local/etc/php-fpm.conf

chown -R app:app /var/alchemy/Phraseanet/config
chown -R app:app /var/alchemy/Phraseanet/datas
chown -R app:app /var/alchemy/Phraseanet/tmp
chown -R app:app /var/alchemy/Phraseanet/www/thumbnails
FILE=/var/alchemy/Phraseanet/config/configuration.yml
if [ -f "$FILE" ]; then
    echo "$FILE exists, skip setup."
else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c '/phraseanet/auto-install.sh'
fi

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

bash -e docker-php-entrypoint $@
