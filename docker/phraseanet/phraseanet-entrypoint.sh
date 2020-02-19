#!/bin/bash

set -e

envsubst < /php.ini.sample > /usr/local/etc/php/php.ini
envsubst < /php-fpm.conf.sample > /usr/local/etc/php-fpm.conf
echo "XDEBUG=$XDEBUG"
if [ $XDEBUG = "ON" ]; then
  echo "XDEBUG IS ENABLED. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
  echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  echo "xdebug.remote_host=$XDEBUG_SERVER" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  echo "xdebug.remote_port=$XDEBUG_REMOTE_PORT" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
  #echo "xdebug.idekey=11896" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
fi

bash -e docker-php-entrypoint $@
