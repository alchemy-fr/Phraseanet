#!/bin/bash

envsubst < /php.ini.sample > /usr/local/etc/php/php.ini
envsubst < /php-fpm.conf.sample > /usr/local/etc/php-fpm.conf
php-fpm
