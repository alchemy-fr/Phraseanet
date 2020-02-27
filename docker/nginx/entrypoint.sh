#!/bin/sh

set -xe

cat nginx.conf.sample | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" > /etc/nginx/conf.d/default.conf

exec "$@"
