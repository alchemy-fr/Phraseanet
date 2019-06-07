#!/bin/bash

cat nginx.conf.sample | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" > /etc/nginx/nginx.conf
nginx -g "daemon off;"
