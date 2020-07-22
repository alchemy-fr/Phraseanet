#!/bin/sh

set -xe

cat /nginx.conf.sample | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" | sed "s/\$GATEWAY_SEND_TIMEOUT/$GATEWAY_SEND_TIMEOUT/g"  | sed "s/\$GATEWAY_FASTCGI_TIMEOUT/$GATEWAY_FASTCGI_TIMEOUT/g" | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" | sed "s/\$GATEWAY_PROXY_TIMEOUT/$GATEWAY_PROXY_TIMEOUT/g"  > /etc/nginx/conf.d/default.conf
cat /fastcgi_timeout.conf  | sed "s/\$GATEWAY_FASTCGI_TIMEOUT/$GATEWAY_FASTCGI_TIMEOUT/g" > /etc/nginx/fastcgi_extended_params
exec "$@"
