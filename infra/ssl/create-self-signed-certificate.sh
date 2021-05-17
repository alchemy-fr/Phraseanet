#!/bin/bash
set -e

DOMAIN=${1:-"alchemy.local"}

PROJECT_DIR="$( cd "$(dirname "$0")" && pwd )"
SSL_DIR="/etc/nginx/ssl/${DOMAIN}"

sudo mkdir -p $SSL_DIR

sudo openssl req -new -sha256 -nodes -out $SSL_DIR/${DOMAIN}.csr -newkey rsa:2048 -keyout $SSL_DIR/${DOMAIN}.key \
    -config $PROJECT_DIR/server.csr.${DOMAIN}.cnf

sudo openssl x509 -req -in $SSL_DIR/${DOMAIN}.csr -CA ~/ssl/AlchemyRootCA.pem -CAkey ~/ssl/AlchemyRootCA.key -CAcreateserial \
    -out $SSL_DIR/${DOMAIN}.crt -days 1825 -sha256 -extfile $PROJECT_DIR/v3.${DOMAIN}.ext

sudo rm $SSL_DIR/${DOMAIN}.csr

echo "Done."
