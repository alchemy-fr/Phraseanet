## Description

A scripts collection for migration froma a stack and another one
this script maybe require 


### requirement 


A Phraseanet deployed with version 4.1.6 or higher on docker-compose or K8S
and the migrated instance need to be in 4.1 or higher version 

### Instruction usage 

Script can be play in a running FPM container with app users
Before execution of they script keep a copie your existing source and destination "configuration.yml" files
and place the configuration file need to be migrate in place of the current "configuration.yml" file

### Datastore_conf

 Update configuration.yml in accordance of containers env value for :

  - Databases
  - Elasticsearch 
  - Redis app cache
  - Rabbitmq 
  - Redis session 


### Email_conf

Update SMTP connectivity in configuration.yml in accordance of containers env values  


### network_conf

Update network conectivity in configuration.yml in accordance of containers env values 

### binairies and storage path

Update the binaries path in accordance of the current docker stack 



