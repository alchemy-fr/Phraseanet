#!/bin/bash

set -e

curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

composer install --ignore-platform-reqs --no-interaction

FILE=config/configuration.yml

while [[ ! -e "$FILE" ]]
do
sleep 10
/var/alchemy/Phraseanet/bin/setup system:install \
    --email=test@test.fr \
    --password=test \
    --db-host=db \
    --db-port=3306 \
    --db-user=root \
    --db-password=root \
    --db-template=DublinCore \
    --appbox=ab_master \
    --databox=db_databox1 \
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
