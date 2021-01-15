#!/bin/bash

set -e

envsubst < "docker/phraseanet/php.ini.sample" > /usr/local/etc/php/php.ini
envsubst < "docker/phraseanet/php-fpm.conf.sample" > /usr/local/etc/php-fpm.conf
cat docker/phraseanet/root/usr/local/etc/php-fpm.d/zz-docker.conf  | sed "s/\$REQUEST_TERMINATE_TIMEOUT/$REQUEST_TERMINATE_TIMEOUT/g" > /usr/local/etc/php-fpm.d/zz-docker.conf



FILE=config/configuration.yml

if [ -f "$FILE" ]; then
    echo "$FILE exists, skip setup."
    if [[ $PHRASEANET_PROJECT_NAME ]]; then
        bin/setup system:config set registry.general.title $PHRASEANET_PROJECT_NAME
    fi
    if [[ -n $PHRASEANET_TRUSTED_PROXIES ]]; then
        for i in $(echo $PHRASEANET_TRUSTED_PROXIES | sed "s/,/ /g")
          do
            bin/setup system:config add trusted-proxies $i
         done
    fi

    bin/setup system:config set main.binaries.ffmpeg_timeout $PHRASEANET_FFMPEG_TIMEOUT
    bin/setup system:config set main.binaries.ffprobe_timeout $PHRASEANET_FFPROBE_TIMEOUT
    bin/setup system:config set main.binaries.gs_timeout $PHRASEANET_GS_TIMEOUT
    bin/setup system:config set main.binaries.mp4box_timeout $PHRASEANET_MP4BOX_TIMEOUT
    bin/setup system:config set main.binaries.swftools_timeout $PHRASEANET_SWFTOOLS_TIMEOUT
    bin/setup system:config set main.binaries.unoconv_timeout $PHRASEANET_UNOCON_TIMEOUT
    bin/setup system:config set main.binaries.exiftool_timeout $PHRASEANET_EXIFTOOL_TIMEOUT
    
    bin/setup system:config set registry.api-clients.api-enabled $PHRASEANET_API_ENABLED
    bin/setup system:config set registry.api-clients.api-require-ssl $PHRASEANET_API_SSL
    bin/setup system:config set registry.api-clients.api-auth-token-header-only $PHRASEANET_API_AUTH_TOKEN_HEADER_ONLY


    if [[ $PHRASEANET_SMTP_ENABLED && $PHRASEANET_SMTP_ENABLED = true ]]; then
        bin/setup system:config set registry.email.smtp-enabled $PHRASEANET_SMTP_ENABLED
        bin/setup system:config set registry.email.smtp-auth-enabled $PHRASEANET_SMTP_AUTH_ENABLED
        bin/setup system:config set registry.email.smtp-secure-mode $PHRASEANET_SMTP_SECURE_MODE
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
    echo `date +"%Y-%m-%d %H:%M:%S"` " - config/configuration.yml update by Phraseanet entrypoint.sh Finished !"
else
    echo "$FILE doesn't exist, entering setup..."

    chown app:app \
        cache \
        config \
        tmp \
        logs \
        www \
        datas

    runuser app -c docker/phraseanet/auto-install.sh
   echo `date +"%Y-%m-%d %H:%M:%S"` " - End of Phraseanet Installation"
fi

if [ ${XDEBUG_ENABLED} == "1" ]; then
    echo "XDEBUG is enabled. YOU MAY KEEP THIS FEATURE DISABLED IN PRODUCTION."
    docker-php-ext-enable xdebug
fi

./docker/phraseanet/plugins/console init
rm -Rf cache/*
chmod 600 config/configuration.yml

chown -R app:app \
    cache \
    config \
    tmp \
    logs \
    www
    

if [ -d "plugins/" ];then
chown -R app:app plugins;
fi

chown -R app:app datas && echo `date +"%Y-%m-%d %H:%M:%S"` " - Finished chown on datas by entreypoint" &
echo `date +"%Y-%m-%d %H:%M:%S"` " - Finished runnning Phraseanet entrypoint.sh"

bash -e docker-php-entrypoint $@
