#bin/bash

cd "/var/alchemy/Phraseanet"

## Phraseanet application Database setting

  echo `date +"%Y-%m-%d %H:%M:%S"` - "Applying infrastructure stack setup to Phraseanet Database connexion"

  bin/setup system:config -s set main.database.host $PHRASEANET_DB_HOST
  bin/setup system:config -s set main.database.port $PHRASEANET_DB_PORT
  bin/setup system:config -s set main.database.user $PHRASEANET_DB_USER
  bin/setup system:config -s set main.database.password $PHRASEANET_DB_PASSWORD
  
  echo `date +"%Y-%m-%d %H:%M:%S"` - "setup of Phraseanet Database connexion applied"


## Phraseanet application Elasticsearch setting 

echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet Elastisearch"

  if [ -z "$PHRASEANET_ELASTICSEARCH_HOST" ]; then
	 /var/alchemy/Phraseanet/bin/setup system:config -s set main.search-engine.options.host elasticsearch
  else
	 /var/alchemy/Phraseanet/bin/setup system:config -s set main.search-engine.options.host $PHRASEANET_ELASTICSEARCH_HOST
  fi

  if [ -z "$PHRASEANET_ELASTICSEARCH_PORT" ]; then
	  /var/alchemy/Phraseanet/bin/setup system:config -s set main.search-engine.options.port 9200
  else
	  /var/alchemy/Phraseanet/bin/setup system:config -s set main.search-engine.options.port $PHRASEANET_ELASTICSEARCH_PORT
  fi

   echo `date +"%Y-%m-%d %H:%M:%S"` - "setup of Phraseanet elasticsearch applied"

# Create elasticsearch index
##  /var/alchemy/Phraseanet/bin/console searchengine:index -c

  
## Phraseanet application cache setting
  echo `date +"%Y-%m-%d %H:%M:%S"` - "Applying infrastructure stack setup to Phraseanet application cache"

  bin/setup system:config -s set main.cache.options.host $PHRASEANET_CACHE_HOST
  bin/setup system:config -s set main.cache.options.port $PHRASEANET_CACHE_PORT
  bin/setup system:config -s set main.cache.options.namespace $PHRASEANET_HOSTNAME
  bin/setup system:config -s set main.cache.type $PHRASEANET_CACHE_TYPE

  echo `date +"%Y-%m-%d %H:%M:%S"` - "setup of Phraseanet application cache applied" 


## Phraseanet application session setting 
  echo `date +"%Y-%m-%d %H:%M:%S"` - "Applying infrastructure stack setup to Phraseanet session cache"
 
  bin/setup system:config -s set main.session.type "native"
  bin/setup system:config -s set main.session.ttl "86400"

  echo `date +"%Y-%m-%d %H:%M:%S"` - "setup of Phraseanet session cache applied"  


## Phraseanet application worker setting

  echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet setting RABBITMQ"

  bin/setup system:config -s set workers.queue.worker-queue.host $PHRASEANET_RABBITMQ_HOST
  bin/setup system:config -s set workers.queue.worker-queue.port $PHRASEANET_RABBITMQ_PORT
  bin/setup system:config -s set workers.queue.worker-queue.vhost $PHRASEANET_RABBITMQ_VHOST
  bin/setup system:config -s set workers.queue.worker-queue.ssl $PHRASEANET_RABBITMQ_SSL
  bin/setup system:config -s set workers.queue.worker-queue.heartbeat $PHRASEANET_RABBITMQ_HEARTBEAT
  bin/setup system:config -s set workers.queue.worker-queue.user $PHRASEANET_RABBITMQ_USER
  bin/setup system:config -s set workers.queue.worker-queue.password $PHRASEANET_RABBITMQ_PASSWORD

echo `date +"%Y-%m-%d %H:%M:%S"` " - setup of Phraseanet setting RABBITMQ applied"     
 
cd -   

echo `date +"%Y-%m-%d %H:%M:%S"` " - End of datastore migration - Check databases in \"sbas\" table in Application Box"
echo "The configuration file is not compile"
echo "to compile use : bin/setup system:config compile"






