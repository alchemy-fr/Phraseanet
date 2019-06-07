#!/bin/bash

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
/var/alchemy/Phraseanet/bin/console compile:configuration
/var/alchemy/Phraseanet/bin/console searchengine:index -c

## enable API and disable ssl on it
/var/alchemy/Phraseanet/bin/setup system:config set registry.api-clients.api-enabled true
/var/alchemy/Phraseanet/bin/setup system:config set main.api_require_ssl false
/var/alchemy/Phraseanet/bin/console comp:conf
