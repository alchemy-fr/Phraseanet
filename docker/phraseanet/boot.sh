#!/bin/bash

envsubst < /php.ini.sample > /usr/local/etc/php/php.ini
envsubst < /php-fpm.conf.sample > /usr/local/etc/php-fpm.conf

FILE=/var/alchemy/Phraseanet/config/configuration.yml
if [ -f "$FILE" ]; then
    echo "$FILE exist, skip setup."
else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c '/auto-install.sh'
fi

php-fpm
