#!/bin/bash

set -xe

chown -R app:app /var/alchemy/Phraseanet/config
chown -R app:app /var/alchemy/Phraseanet/datas
chown -R app:app /var/alchemy/Phraseanet/www/thumbnails
FILE=/var/alchemy/Phraseanet/config/configuration.yml
if [ -f "$FILE" ]; then
    echo "$FILE exist, skip setup."
else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c '/auto-install.sh'
fi

php-fpm
