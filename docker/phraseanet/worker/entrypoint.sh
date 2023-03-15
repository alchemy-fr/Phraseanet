#!/bin/bash

set -e

HEARTBEAT_INTERVAL=20
APP_DIR="/var/alchemy/Phraseanet"
DOCKER_DIR="./docker/phraseanet"
PHR_USER=app

mkdir -p "${APP_DIR}/tmp/locks" \
    && chown -R app:app "${APP_DIR}/tmp" \
    && chown -R app:app "${APP_DIR}/tmp/locks"


envsubst < "${DOCKER_DIR}/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "${DOCKER_DIR}/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

./docker/phraseanet/plugins/console init

# rm -Rf cache/*

chown -R app:app cache
echo `date +"%Y-%m-%d %H:%M:%S"` " - chown app:app on cache/ repository"

#    config \
#    tmp \
#    logs \
#    www


if [ -d "plugins/" ];then
chown -R app:app plugins
echo `date +"%Y-%m-%d %H:%M:%S"` " - chown app:app on plugins/ repository"
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

rm -rf bin/run-worker.sh

if [ ! -z "$PHRASEANET_SCHEDULER" ] ; then
  command="bin/console task-manager:scheduler:run"
  echo $command >> bin/run-worker.sh
  echo "Phraseanet workers container will be launched in Scheduler mode with bin/console task-manager:scheduler:run"
else
  if [ ! -z "$PHRASEANET_EXPLODE_WORKER" ] && [ ${PHRASEANET_EXPLODE_WORKER} == "1" ]; then
    if [ ! -z "$PHRASEANET_WORKERS_LAUNCH_METHOD" ] && [ ${PHRASEANET_WORKERS_LAUNCH_METHOD} == "supervisor" ]; then
      echo "Multiples Phraseanet workers will be launched by supervisor"
      for i in `env | grep PHRASEANET_WORKER_ | cut -d'=' -f1`
      do
        worker_job_file="$(echo $i | cut -d'_' -f3).conf"
        if [ ${!i} -gt "0" ] ; then
          envsubst < "/var/alchemy/Phraseanet/docker/phraseanet/worker/supervisor_conf.d/$worker_job_file" > /etc/supervisor/conf.d/$worker_job_file
          echo "Add worker: " $worker_job_file " -- with parallelism: (-m) " ${!i}
        else
          echo "No worker defined for: " $worker_job_file " -- because parallelism (-m) is set to : " ${!i}
        fi
      done
      command="/usr/bin/supervisord -n -c /etc/supervisor/supervisor.conf"
      echo $command >> bin/run-worker.sh
      PHR_USER=root
    else
      echo "Multiples Phraseanet workers will be launched with bin/console worker:execute"
      NBR_WORKERS=0

      echo "bin/console worker:heartbeat --heartbeat ${HEARTBEAT_INTERVAL} &" >> bin/run-worker.sh

      for i in `env | grep PHRASEANET_WORKER_ | cut -d'=' -f1`
      do
        queue_name="$(echo $i | cut -d'_' -f3)"
        m=$i
        if [ ${!m} -gt "0" ] ; then
          command="bin/console worker:execute --queue-name=$queue_name -m ${!m} &"
          echo $command >> bin/run-worker.sh
          echo "Worker " $queue_name " defined with parallelism " ${!m}
          NBR_WORKERS=$(expr $NBR_WORKERS + 1)
        else
          echo "No worker defined for: " $m " -- because parallelism (-m) is set to : " ${!m}
        fi
      done

      echo $NBR_WORKERS " workers defined"
      echo $NBR_WORKERS > bin/workers_count.txt
      chown root:app bin/workers_count.txt
      chmod 760 bin/workers_count.txt
      echo "HEARTBEAT_INTERVAL=${HEARTBEAT_INTERVAL}" >> bin/run-worker.sh
      echo '
NBR_WORKERS=$(< bin/workers_count.txt)
sleep 1 # let worker:heartbeat fail before process check

function check() {
  nb_process=`ps faux | grep "worker:execute" | grep php | wc -l`
  nb_heartbeat=`ps faux | grep "worker:heartbeat" | grep php | wc -l`
  date_time_process=`date +"%Y-%m-%d %H:%M:%S"`
  if [ -z $STACK_NAME ]; then
    echo $date_time_process "-" $nb_process "running workers"
  fi
  if [ $nb_process -lt $NBR_WORKERS ]; then
    echo "One or more worker:execute is not running, exiting..."
    exit 1
  elif [ $nb_heartbeat -lt 1 ]; then
    echo "worker:heartbeat is not running, exiting..."
    exit 1
  fi
}

# early check
check

while true; do
  sleep ${HEARTBEAT_INTERVAL}s
  check
done' >> bin/run-worker.sh
    fi
  else
    command="bin/console worker:execute"
    echo $command >> bin/run-worker.sh
  fi
fi

if [ ! -z "$PHRASEANET_SCHEDULER" ] ; then
  tail -F "${APP_DIR}/logs/scheduler.log" &
  tail -F "${APP_DIR}/logs/task.log" &
else
  tail -F "${APP_DIR}/logs/worker_service.log" &
fi

runuser -u $PHR_USER -- $@
