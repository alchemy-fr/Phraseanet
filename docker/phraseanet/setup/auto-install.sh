#!/bin/bash

set -e

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

 # Bus configuration for scheduler & worker
bin/setup system:config set workers.queue.worker-queue.registry alchemy_worker.queue_registry

# elasticsearch settings

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

if [ -z "$PHRASEANET_ELASTICSEARCH_SHARD" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.shard 3
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.shard $PHRASEANET_ELASTICSEARCH_SHARD
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_REPLICAS" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.replicas 0
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.replicas $PHRASEANET_ELASTICSEARCH_REPLICAS
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_MINSCORE" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.minScore 2
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.minScore $PHRASEANET_ELASTICSEARCH_MINSCORE
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_HIGHLIGHT" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.highlight true
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.highlight $PHRASEANET_ELASTICSEARCH_HIGHLIGHT
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_MAXRESULTWINDOW" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.maxResultWindow 500000
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.maxResultWindow $PHRASEANET_ELASTICSEARCH_MAXRESULTWINDOW
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_POPULATEORDER" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.populate_order MODIFICATION_DATE
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.populate_order $PHRASEANET_ELASTICSEARCH_POPULATEORDER
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_FACET_BASE" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._base.limit 10
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._base.limit $PHRASEANET_ELASTICSEARCH_FACET_BASE
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_FACET_COLLECTION" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._collection.limit 10
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._collection.limit $PHRASEANET_ELASTICSEARCH_FACET_COLLECTION
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_FACET_DOCTYPE" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._doctype.limit 10
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._doctype.limit $PHRASEANET_ELASTICSEARCH_FACET_DOCTYPE
fi

if [ -z "$PHRASEANET_ELASTICSEARCH_FACET_ORIENTATION" ]; then
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._thumbnail_orientation.limit 10
else
/var/alchemy/Phraseanet/bin/setup system:config set main.search-engine.options.facets._thumbnail_orientation.limit $PHRASEANET_ELASTICSEARCH_FACET_ORIENTATION
fi

echo "Ended setting elasticsearch configuration"

/var/alchemy/Phraseanet/bin/console compile:configuration

# Create elasticsearch index
/var/alchemy/Phraseanet/bin/console searchengine:index -c

# Create _TRASH_ collection on first databox
/var/alchemy/Phraseanet/bin/console collection:create 1 Public -d 1
/var/alchemy/Phraseanet/bin/console collection:create 1 Private -d 1
/var/alchemy/Phraseanet/bin/console collection:create 1 _TRASH_ -d 1
