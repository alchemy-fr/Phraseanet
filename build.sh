#!/bin/bash

# nginx server
docker build --target phraseanet-nginx -t local/phraseanet-nginx:$1 .

# php-fpm application
docker build --target phraseanet-fpm -t local/phraseanet-fpm:$1 .

# worker
docker build --target phraseanet-worker -t local/phraseanet-worker:$1 .

# php-fpm application (with XDEBUG)
docker build --target phraseanet-fpm-xdebug -t local/phraseanet-fpm-xdebug:$1 .

# worker (with XDEBUG)
docker build --target phraseanet-worker-xdebug -t local/phraseanet-worker-xdebug:$1 .
