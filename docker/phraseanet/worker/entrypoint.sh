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

runuser -u app -- $@
