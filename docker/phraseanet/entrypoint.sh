#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
cat docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf  | sed "s/\$REQUEST_TERMINATE_TIMEOUT/$REQUEST_TERMINATE_TIMEOUT/g" > /usr/local/etc/php-fpm.d/zz-docker.conf

chown -R app:app \
    cache \
    config \
    datas \
    tmp \
    logs \
    www

FILE=config/configuration.yml

if [ -f "$FILE" ]; then
    echo "$FILE exists, skip setup."
    if [[ $PHRASEANET_PROJECT_NAME ]]; then
        bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME
    fi
    if [[ -n $PHRASEANET_TRUSTED_PROXY ]]; then
        bin/setup system:config add trusted-proxies $PHRASEANET_TRUSTED_PROXY
    fi
    bin/setup system:config set main.binaries.ffmpeg_timeout $PHRASEANET_FFMPEG_TIMEOUT
    bin/setup system:config set main.binaries.ffprobe_timeout $PHRASEANET_FFPROBE_TIMEOUT
    bin/setup system:config set main.binaries.gs_timeout $PHRASEANET_GS_TIMEOUT
    bin/setup system:config set main.binaries.mp4box_timeout $PHRASEANET_MP4BOX_TIMEOUT
    bin/setup system:config set main.binaries.swftools_timeout $PHRASEANET_SWFTOOLS_TIMEOUT
    bin/setup system:config set main.binaries.unoconv_timeout $PHRASEANET_UNOCON_TIMEOUT
    bin/setup system:config set main.binaries.exiftool_timeout $PHRASEANET_EXIFTOOL_TIMEOUT

    if [[ $PHRASEANET_SMTP_ENABLED && $PHRASEANET_SMTP_ENABLED = true ]]; then
        bin/setup system:config set registry.email.smtp-enabled $PHRASEANET_SMTP_ENABLED
        bin/setup system:config set registry.email.smtp-auth-enabled $PHRASEANET_SMTP_AUTH_ENABLED
        bin/setup system:config set registry.email.smtp-auth-secure-mode $PHRASEANET_SMTP_SECURE_MODE
        bin/setup system:config set registry.email.smtp-host $PHRASEANET_SMTP_HOST
        bin/setup system:config set registry.email.smtp-port $PHRASEANET_SMTP_PORT
        bin/setup system:config set registry.email.smtp-user $PHRASEANET_SMTP_USER
        bin/setup system:config set registry.email.smtp-password $PHRASEANET_SMTP_PASSWORD
        bin/setup system:config set registry.email.emitter-email $PHRASEANET_EMITTER_EMAIL
        bin/setup system:config set registry.email.prefix $PHRASEANET_MAIL_OBJECT_PREFIX
    fi
    if [[ -n ${PHRASEANET_ADMIN_ACCOUNT_ID} && $PHRASEANET_ADMIN_ACCOUNT_ID =~ ^[0-9]+$ ]]; then
       bin/console user:password --user_id=$PHRASEANET_ADMIN_ACCOUNT_ID --password $PHRASEANET_ADMIN_ACCOUNT_PASSWORD -y
    fi

else
    echo "$FILE doesn't exist, entering setup..."
    runuser app -c docker/phraseanet/auto-install.sh
fi

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

./docker/phraseanet/plugins/console init
#rm -Rf cache/

chown -R app:app \
    cache \
    config \
    datas \
    tmp \
    logs \
    www

if [ -d "plugins/" ];then
chown -R app:app plugins;
fi

bash -e docker-php-entrypoint $@
