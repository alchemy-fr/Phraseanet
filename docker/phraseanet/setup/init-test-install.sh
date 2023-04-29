#!/bin/bash

set -e

curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

composer install --ignore-platform-reqs --no-interaction



if [ -z "$PHRASEANET_ADMIN_ACCOUNT_EMAIL" ]; then
    echo "PHRASEANET_ADMIN_ACCOUNT_EMAIL, Phraseanet admin account  var is not set."
    exit 1
fi

if [ -z "$PHRASEANET_ADMIN_ACCOUNT_PASSWORD " ]; then
    echo "$PHRASEANET_ADMIN_ACCOUNT_PASSWORD,  Phaseanet admin password var is not set."
    exit 1
fi

FILE=config/configuration.yml

while [[ ! -e "$FILE" ]]
do
sleep 10
/var/alchemy/Phraseanet/bin/setup system:install \
    --email=$PHRASEANET_ADMIN_ACCOUNT_EMAIL \
    --password=$PHRASEANET_ADMIN_ACCOUNT_PASSWORD \
    --db-host=$PHRASEANET_DB_HOST \
    --db-port=$PHRASEANET_DB_PORT \
    --db-user=$PHRASEANET_DB_USER \
    --db-password=$PHRASEANET_DB_PASSWORD \
    --db-template=$INSTALL_DB_TEMPLATE \
    --appbox=$INSTALL_APPBOX \
    --databox=$INSTALL_DATABOX \
    --server-name=$PHRASEANET_BASE_URL \
    --download-path=$PHRASEANET_DOWNLOAD_DIR \
    --lazaret-path=$PHRASEANET_LAZARET_DIR \
    --caption-path=$PHRASEANET_CAPTION_DIR \
    --worker-tmp-files=$PHRASEANET_WORKER_TMP \
    --data-path=/var/alchemy/Phraseanet/datas -y
done

/var/alchemy/Phraseanet/bin/setup system:config set workers.queue.worker-queue.registry alchemy_worker.queue_registry

echo "Setting Elasticsearch configuration"

if [ -z "$PHRASEANET_ELASTICSEARCH_HOST" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.host elasticsearch
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.host $PHRASEANET_ELASTICSEARCH_HOST
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_PORT" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.port 9200
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.port $PHRASEANET_ELASTICSEARCH_PORT
fi

sleep 5

/var/alchemy/Phraseanet/bin/console searchengine:index -dc --force

/var/alchemy/Phraseanet/bin/developer ini:setup-tests-dbs -v

/var/alchemy/Phraseanet/bin/developer phraseanet:regenerate-sqlite -v

/var/alchemy/Phraseanet/bin/developer phraseanet:generate-js-fixtures -v
