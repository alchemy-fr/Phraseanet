#!/bin/bash

set -xe

if [ $INSTALL_ACCOUNT_EMAIL = ""]; then
    echo "INSTALL_ACCOUNT_EMAIL var is not set."
    exit 1
fi

if [ $INSTALL_ACCOUNT_PASSWORD = ""]; then
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
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.base_aggregate_limit 10
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.collection_aggregate_limit 10
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.doctype_aggregate_limit 10

## Redis
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.host redis
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.port 6379
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.options.domain $INSTALL_SERVER_NAME
/var/alchemy/Phraseanet/bin/setup system:config set main.cache.type redis

# RabbitMQ

bin/setup system:config set rabbitmq.server.host rabbitmq
bin/setup system:config set rabbitmq.server.port 5672
bin/setup system:config set rabbitmq.server.user $INSTALL_RABBITMQ_USER
bin/setup system:config set rabbitmq.server.password $INSTALL_RABBITMQ_PASSWORD
bin/setup system:config set rabbitmq.server.vhost /


/var/alchemy/Phraseanet/bin/console searchengine:index -c

## enable API and disable ssl on it
/var/alchemy/Phraseanet/bin/setup system:config set registry.api-clients.api-enabled true
/var/alchemy/Phraseanet/bin/setup system:config set main.api_require_ssl false
/var/alchemy/Phraseanet/bin/console compile:configuration
