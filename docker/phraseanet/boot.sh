#!/bin/bash

chown -R app:app /var/alchemy/Phraseanet/config
FILE=/var/alchemy/Phraseanet/config/configuration.yml
if [ -f "$FILE" ]; then
    echo "$FILE exist, skip setup."
else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c '/auto-install.sh'
fi

php-fpm
