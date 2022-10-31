## Description

Status : EXPERIMENTAL, WIP

A scripts collection for migration from a stack (eg: docker-compose) to another one 


### requirement 

#### configuration.yml 
Target: a Phraseanet deployed with version 4.1.6 or higher on docker-compose or Kubernetes
Source : a configuration.yml from a 4.1 version or higher version 

### Instruction.

Scripts can be play in a running FPM container with app users
Before execution of they script keep a copie your existing source and destination "configuration.yml" files
and place the configuration file need to be migrate in place of the current "configuration.yml" file

After execution of all script you need to `rm config/configuration-compiled.yml`
and compile-configuration `bin/setup system:config compile`

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



