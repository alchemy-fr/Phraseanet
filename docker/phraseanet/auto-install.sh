#!/bin/bash

set -xe

if [ -z "$INSTALL_ACCOUNT_EMAIL" ]; then
    echo "INSTALL_ACCOUNT_EMAIL var is not set."
    exit 1
fi

if [ -z "$INSTALL_ACCOUNT_PASSWORD" ]; then
    echo "INSTALL_ACCOUNT_PASSWORD var is not set."
    exit 1
fi

/var/alchemy/Phraseanet/bin/setup system:install \
    --email=$INSTALL_ACCOUNT_EMAIL \
    --password=$INSTALL_ACCOUNT_PASSWORD \
    --db-host=$INSTALL_DB_HOST \
    --db-port=$INSTALL_DB_PORT \
    --db-user=$INSTALL_DB_USER \
    --db-password=$INSTALL_DB_PASSWORD \
    --db-template=$INSTALL_DB_TEMPLATE \
    --appbox=$INSTALL_APPBOX \
    --databox=$INSTALL_DATABOX \
    --server-name=$INSTALL_SERVER_NAME \
    --data-path=/var/alchemy/Phraseanet/datas -y

/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.host elasticsearch
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.minScore 2
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.minScore 2
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._base.limit 10
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._collection.limit 10
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._doctype.limit 10

## Redis
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.host redis
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.port 6379
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.namespace $INSTALL_SERVER_NAME
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.type redis

# Bus configuration for scheduler & worker
bin/setup system:config set workers.queue.worker-queue.registry alchemy_worker.queue_registry 
bin/setup system:config set workers.queue.worker-queue.host rabbitmq
bin/setup system:config set workers.queue.worker-queue.port 5672 
bin/setup system:config set workers.queue.worker-queue.user $INSTALL_RABBITMQ_USER
bin/setup system:config set workers.queue.worker-queue.password $INSTALL_RABBITMQ_PASSWORD
bin/setup system:config set workers.queue.worker-queue.vhost /

# Create elasticsearch index
/var/alchemy/Phraseanet/bin/console searchengine:index -c

## enable API and disable ssl on it
/var/alchemy/Phraseanet/bin/setup system:config set registry.api-clients.api-enabled true
/var/alchemy/Phraseanet/bin/setup system:config set main.api_require_ssl false

# set instance title
bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME

/var/alchemy/Phraseanet/bin/console compile:configuration
