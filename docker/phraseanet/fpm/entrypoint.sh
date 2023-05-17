#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
cat docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf  | sed "s/\$REQUEST_TERMINATE_TIMEOUT/$REQUEST_TERMINATE_TIMEOUT/g" > /usr/local/etc/php-fpm.d/zz-docker.conf

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi


if [[ $NEWRELIC_ENABLED = "true" ]]; then
  echo `date +"%Y-%m-%d %H:%M:%S"` " - NewRelic daemon and PHP agent setup."
  sed -i -e "s/REPLACE_WITH_REAL_KEY/$NEWRELIC_LICENSE_KEY/" \
  -e "s/newrelic.appname[[:space:]]=[[:space:]].*/newrelic.appname=\"$NEWRELIC_APP_NAME\"/" \
  -e '$anewrelic.distributed_tracing_enabled=true' \
  $(php -r "echo(PHP_CONFIG_FILE_SCAN_DIR);")/newrelic.ini
  
  echo "setup of Newrelic agent log forward"
  echo "newrelic.appname = \"$NEWRELIC_APP_NAME\"" > /etc/newrelic/newrelic.cfg
  echo "newrelic.license = \"$NEWRELIC_LICENSE_KEY\"" >> /etc/newrelic/newrelic.cfg
  service newrelic-daemon start
  echo "Newrelic setup of daemon and PHP agent done"
else
  echo `date +"%Y-%m-%d %H:%M:%S"` " - Newrelic extension deactivation."
  rm -f /usr/local/etc/php/conf.d/newrelic.ini 
fi

if [[ $BLACKFIRE_ENABLED = "true" ]]; then
  echo `date +"%Y-%m-%d %H:%M:%S"` " - BlackFire setup."
  blackfire-agent --register --server-id=$BLACKFIRE_SERVER_ID --server-token=$BLACKFIRE_SERVER_TOKEN
  service blackfire-agent start
  echo "Blackfire setup done"
else
    echo `date +"%Y-%m-%d %H:%M:%S"` " - blackfire extension deactivation."
    rm -f /usr/local/etc/php/conf.d/zz-blackfire.ini
fi


./docker/phraseanet/plugins/console init

chown -R app:app cache
echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on cache/ repository"

#    config \
#    tmp \
#    logs \
#    www


if [ -d "plugins/" ];then
chown -R app:app plugins
echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on plugins/ repository"
fi

echo `date +"%Y-%m-%d %H:%M:%S"` " - End of fpm entrypoint.sh"

bash -e docker-php-entrypoint $@
