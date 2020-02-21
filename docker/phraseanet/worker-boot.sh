#!/bin/bash

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

mkdir /var/alchemy/Phraseanet/tmp/locks && chown -R app:app /var/alchemy/Phraseanet/tmp
runuser app -c 'php /var/alchemy/Phraseanet/bin/console task-manager:scheduler:run'
