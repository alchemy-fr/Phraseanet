#!/bin/bash

set -xe

if [ -z "$PHRASEANET_ADMIN_ACCOUNT_EMAIL" ]; then
    echo "PHRASEANET_ADMIN_ACCOUNT_EMAIL, Phraseanet admin account  var is not set."
    exit 1
fi

if [ -z "$PHRASEANET_ADMIN_ACCOUNT_PASSWORD " ]; then
    echo "$PHRASEANET_ADMIN_ACCOUNT_PASSWORD,  Phaseanet admin password var is not set."
    exit 1
fi

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
    --server-name=$PHRASEANET_SERVER_NAME \
    --download-path=$PHRASEANET_DOWNLOAD_DIR \
    --lazaret-path=$PHRASEANET_LAZARET_DIR \
    --caption-path=$PHRASEANET_CAPTION_DIR \
    --worker-tmp-files=$PHRASEANET_WORKER_TMP \
    --data-path=/var/alchemy/Phraseanet/datas -y

 # Bus configuration for scheduler & worker
bin/setup system:config set workers.queue.worker-queue.registry alchemy_worker.queue_registry 
bin/setup system:config set workers.queue.worker-queue.host rabbitmq
bin/setup system:config set workers.queue.worker-queue.port 5672 
bin/setup system:config set workers.queue.worker-queue.user $PHRASEANET_RABBITMQ_USER
bin/setup system:config set workers.queue.worker-queue.password $PHRASEANET_RABBITMQ_PASSWORD
bin/setup system:config set workers.queue.worker-queue.vhost /

/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.host elasticsearch
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.minScore 2
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.minScore 2
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._base.limit 10
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._collection.limit 10
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._doctype.limit 10

## Redis
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.host redis
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.port 6379
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.namespace $PHRASEANET_SERVER_NAME
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.type redis

## enable API and disable ssl on it
/var/alchemy/Phraseanet/bin/setup system:config set registry.api-clients.api-enabled $PHRASEANET_API_ENABLED
/var/alchemy/Phraseanet/bin/setup system:config set registry.api-clients.api-require-ssl $PHRASEANET_API_SSL
/var/alchemy/Phraseanet/bin/setup system:config set registry.api-clients.api-auth-token-header-only $PHRASEANET_API_AUTH_TOKEN_HEADER_ONLY

## Trusted proxie setting 

if [[ -n $PHRASEANET_TRUSTED_PROXIES ]]; then
        bin/setup system:config add trusted-proxies $PHRASEANET_TRUSTED_PROXIES
fi


## set instance title
bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME


/var/alchemy/Phraseanet/bin/console compile:configuration

# Create elasticsearch index
/var/alchemy/Phraseanet/bin/console searchengine:index -c

# Create _TRASH_ collection on first databox
/var/alchemy/Phraseanet/bin/console collection:create 1 Public -d 1
/var/alchemy/Phraseanet/bin/console collection:create 1 Private -d 1
/var/alchemy/Phraseanet/bin/console collection:create 1 _TRASH_ -d 1
