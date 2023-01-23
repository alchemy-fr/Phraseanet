#!/bin/bash

set -eo pipefail
shopt -s nullglob

envsubst < /custom.cnf > /etc/mysql/conf.d/custom.cnf
docker-entrypoint.sh "$@"
