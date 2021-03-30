#!/bin/bash

set -e

DOCKER_DIR="./docker/phraseanet"

mkdir -p "${APP_DIR}/tmp/locks" \
    && chown -R app:app "${APP_DIR}/tmp"

envsubst < "${DOCKER_DIR}/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "${DOCKER_DIR}/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

if [ -f /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml ]; then
  if [ ! -d $IMAGEMAGICK_POLICY_TEMPORARY_PATH ]; then
    echo "$IMAGEMAGICK_POLICY_TEMPORARY_PATH does not exist lets create it"
    mkdir -p $IMAGEMAGICK_POLICY_TEMPORARY_PATH
  fi
  sed -i "s/domain=\"resource\" name=\"memory\" value=\".*\"/domain=\"resource\" name=\"memory\" value=\"$IMAGEMAGICK_POLICY_MEMORY\"/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"map\" value=\".*\"/domain=\"resource\" name=\"map\" value=\"$IMAGEMAGICK_POLICY_MAP\"/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"width\" value=\".*\"/domain=\"resource\" name=\"width\" value=\"$IMAGEMAGICK_POLICY_WIDTH\"/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"height\" value=\".*\"/domain=\"resource\" name=\"height\" value=\"$IMAGEMAGICK_POLICY_HEIGHT\"/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"disk\" value=\".*\"/domain=\"resource\" name=\"disk\" value=\"$IMAGEMAGICK_POLICY_DISK\"/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"area\" value=\".*\"/domain=\"resource\" name=\"area\" value=\"$IMAGEMAGICK_POLICY_AREA\"/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/.*domain=\"resource\" name=\"temporary-path\" value=\".*/<domain=\"resource\" name=\"temporary-path\" value=\"\\$IMAGEMAGICK_POLICY_TEMPORARY_PATH\" \/\>/g" /etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
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
  rm - f /usr/local/etc/php/conf.d/newrelic.ini 
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

rm -rf bin/run-worker.sh
if [ ! -z "$PHRASEANET_EXPLODE_WORKER" ] && [ ${PHRASEANET_EXPLODE_WORKER} == "1" ]; then
  for i in `env | grep PHRASEANET_WORKER_ | cut -d'=' -f1`
   do
      queue_name="$(echo $i | cut -d'_' -f3)"
      m=$i
      command="bin/console worker:execute --queue-name=$queue_name -m ${!m} &"
      echo $command >> bin/run-worker.sh
   done

  echo 'WORKER_NB_QUEUES=`env | grep PHRASEANET_WORKER_ | wc -l`
        WORKER_LOOP_VALUE=20s
    while true;
    do
      sleep $WORKER_LOOP_VALUE
      nb_process=`ps faux | grep "worker:execute" | grep php | wc -l`
      date_time_process=`date +"%Y-%m-%d %H:%M:%S"`
      echo $date_time_process "-" $nb_process "running workers"
      if [ $nb_process -lt $WORKER_NB_QUEUES ]
        then
          exit 1
          break
      fi
    done  ' >> bin/run-worker.sh
else
  command="bin/console worker:execute"
  echo $command >> bin/run-worker.sh
fi

runuser -u app -- $@
