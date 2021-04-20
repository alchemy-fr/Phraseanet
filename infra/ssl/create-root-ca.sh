#!/bin/bash
set -e

mkdir -p ~/ssl/
openssl genrsa -des3 -out ~/ssl/AlchemyRootCA.key 2048
openssl req -x509 -new -nodes -key ~/ssl/AlchemyRootCA.key -sha256 -days 1825 \
    -subj "/C=FR/ST=France/O=Alchemy, Inc./CN=Alchemy" \
    -out ~/ssl/AlchemyRootCA.pem

echo "Done."
