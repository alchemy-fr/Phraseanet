#!/bin/bash

set -xe

# nginx server
docker build --target phraseanet-nginx -t local/phraseanet-nginx:$1 .

# php-fpm application
docker build --target phraseanet-fpm -t local/phraseanet-fpm:$1 .

# worker
docker build --target phraseanet-worker -t local/phraseanet-worker:$1 .

