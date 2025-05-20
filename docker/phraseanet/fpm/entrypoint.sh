#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
envsubst < "docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf" > /usr/local/etc/php-fpm.d/zz-docker.conf

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

if [ -f /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml ]; then
  if [ ! -d $IMAGEMAGICK_POLICY_TEMPORARY_PATH ]; then
    echo "$IMAGEMAGICK_POLICY_TEMPORARY_PATH does not exist lets create it"
    mkdir -p $IMAGEMAGICK_POLICY_TEMPORARY_PATH
  fi
  sed -i '/domain=\"resource\" name=\"memory\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"memory\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"map\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"map\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"width\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"width\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"height\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"height\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"disk\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"disk\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"area\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"area\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"temporary-path\"/s/<!--//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i '/domain=\"resource\" name=\"temporary-path\"/s/-->//g' /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"memory\" value=\".*\"/domain=\"resource\" name=\"memory\" value=\"$IMAGEMAGICK_POLICY_MEMORY\"/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"map\" value=\".*\"/domain=\"resource\" name=\"map\" value=\"$IMAGEMAGICK_POLICY_MAP\"/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"width\" value=\".*\"/domain=\"resource\" name=\"width\" value=\"$IMAGEMAGICK_POLICY_WIDTH\"/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"height\" value=\".*\"/domain=\"resource\" name=\"height\" value=\"$IMAGEMAGICK_POLICY_HEIGHT\"/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"disk\" value=\".*\"/domain=\"resource\" name=\"disk\" value=\"$IMAGEMAGICK_POLICY_DISK\"/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/domain=\"resource\" name=\"area\" value=\".*\"/domain=\"resource\" name=\"area\" value=\"$IMAGEMAGICK_POLICY_AREA\"/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  sed -i "s/.*domain=\"resource\" name=\"temporary-path\" value=\".*/<domain=\"resource\" name=\"temporary-path\" value=\"$(echo $IMAGEMAGICK_POLICY_TEMPORARY_PATH | sed "s/\//\\\\\//g")\" \/\>/g" /usr/local/etc/ImageMagick-$IMAGEMAGICK_POLICY_VERSION/policy.xml
  echo `date +"%Y-%m-%d %H:%M:%S"` " - ImageMagick policy.xml updated"
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

./docker/phraseanet/plugins/console init

chown -R app:app cache
echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on cache/ repository"

if [ -d "plugins/" ];then
chown -R app:app plugins
echo `date +"%Y-%m-%d %H:%M:%S"` " - chown APP:APP on plugins/ repository"
fi

echo `date +"%Y-%m-%d %H:%M:%S"` " - End of fpm entrypoint.sh"

bash -e docker-php-entrypoint $@
