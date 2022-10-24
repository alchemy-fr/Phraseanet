#bin/bash

## Phraseanet application Database setting

  echo `date +"%Y-%m-%d %H:%M:%S"` - "Applying infrastructure stack setup to Phraseanet Database connexion"

  bin/setup system:config set main.database.host $PHRASEANET_DB_HOST
  bin/setup system:config set main.database.port $PHRASEANET_DB_PORT
  bin/setup system:config set main.database.user $PHRASEANET_DB_USER
  bin/setup system:config set main.database.password $PHRASEANET_DB_PASSWORD

## Phraseanet application Elasticsearch setting 

echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet Elastisearch"

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

# Create elasticsearch index
##  /var/alchemy/Phraseanet/bin/console searchengine:index -c

echo "Ended setting elasticsearch MIGRATION configuration"
  

## Phraseanet application cache setting
  echo `date +"%Y-%m-%d %H:%M:%S"` - "Applying infrastructure stack setup to Phraseanet cache"
  echo `date +"%Y-%m-%d %H:%M:%S"` - "Cache Type is $PHRASEANET_CACHE_TYPE"
  bin/setup system:config set main.cache.options.host $PHRASEANET_CACHE_HOST
  bin/setup system:config set main.cache.options.port $PHRASEANET_CACHE_PORT
  bin/setup system:config set main.cache.options.namespace $PHRASEANET_HOSTNAME
  bin/setup system:config set main.cache.type $PHRASEANET_CACHE_TYPE

## Phraseanet application session setting 

  bin/setup system:config set main.session.type "native"
  bin/setup system:config set main.session.ttl "86400"

  echo `date +"%Y-%m-%d %H:%M:%S"` " - Phraseanet PHP session manager is Native by redis"    

## Phraseanet application worker setting

  echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet setting RABBITMQ"
  bin/setup system:config set workers.queue.worker-queue.host $PHRASEANET_RABBITMQ_HOST
  bin/setup system:config set workers.queue.worker-queue.port $PHRASEANET_RABBITMQ_PORT
  bin/setup system:config set workers.queue.worker-queue.vhost $PHRASEANET_RABBITMQ_VHOST
  bin/setup system:config set workers.queue.worker-queue.ssl $PHRASEANET_RABBITMQ_SSL
  bin/setup system:config set workers.queue.worker-queue.heartbeat $PHRASEANET_RABBITMQ_HEARTBEAT
  bin/setup system:config set workers.queue.worker-queue.user $PHRASEANET_RABBITMQ_USER
  bin/setup system:config set workers.queue.worker-queue.password $PHRASEANET_RABBITMQ_PASSWORD

##     
    
echo `date +"%Y-%m-%d %H:%M:%S"` " - End of datastore migration - Check databases in \"sbas\" table in Application Box"






